<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
	exit;
}

/**
 * WCS_Cashback_Checkout
 *
 * Handles:
 * - Displaying cashback info in cart & checkout
 * - Applying cashback discount via WC fees
 * - Earning cashback on payment (only when NOT using cashback)
 * - AJAX endpoints for apply / remove cashback
 */
class WCS_Cashback_Checkout {
	private static $instance = null;

	/** Track whether each context block was already rendered (prevent duplicates) */
	private $rendered = array('cart' => false, 'checkout' => false);

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get the saved position settings from admin panel
	 */
	private function get_position_settings() {
		$settings = get_option('wcs_cashback_settings');
		if (!is_array($settings)) {
			$settings = array();
		}
		return array(
			'cart'     => isset($settings['cart_position']) ? $settings['cart_position'] : 'woocommerce_before_cart_totals',
			'checkout' => isset($settings['checkout_position']) ? $settings['checkout_position'] : 'woocommerce_review_order_before_payment',
		);
	}

	private function __construct() {
		$positions = $this->get_position_settings();

		// ── Display blocks (dynamic position from settings) ──
		if ($positions['cart'] !== 'none') {
			add_action($positions['cart'], array($this, 'display_cashback_in_cart'));
		}
		if ($positions['checkout'] !== 'none') {
			add_action($positions['checkout'], array($this, 'display_cashback_in_checkout'));
		}

		// ── Potential earning info ──────────────────────────
		// These always attach to totals table rows (inside <table>)
		if ($positions['cart'] !== 'none') {
			add_action('woocommerce_cart_totals_after_order_total', array($this, 'display_potential_earning_cart'));
		}
		if ($positions['checkout'] !== 'none') {
			add_action('woocommerce_review_order_after_order_total', array($this, 'display_potential_earning_checkout'));
		}

		// ── Cart fee (discount) ─────────────────────────────
		add_action('woocommerce_cart_calculate_fees', array($this, 'apply_cashback_to_cart'));

		// ── Order meta & processing ─────────────────────────
		add_action('woocommerce_checkout_create_order', array($this, 'save_cashback_to_order'), 20, 2);
		add_action('woocommerce_payment_complete', array($this, 'process_cashback_on_payment'));
		add_action('woocommerce_order_status_completed', array($this, 'process_cashback_on_payment'));

		// ── AJAX ────────────────────────────────────────────
		add_action('wp_ajax_wcs_apply_cashback', array($this, 'ajax_apply_cashback'));
		add_action('wp_ajax_nopriv_wcs_apply_cashback', array($this, 'ajax_apply_cashback'));
		add_action('wp_ajax_wcs_remove_cashback', array($this, 'ajax_remove_cashback'));
		add_action('wp_ajax_nopriv_wcs_remove_cashback', array($this, 'ajax_remove_cashback'));

		// Legacy endpoint kept for backward compatibility
		add_action('wp_ajax_wcs_set_cashback', array($this, 'ajax_apply_cashback'));
		add_action('wp_ajax_nopriv_wcs_set_cashback', array($this, 'ajax_apply_cashback'));

		// ── Clear session after order ───────────────────────
		add_action('woocommerce_thankyou', array($this, 'clear_session_after_order'));
	}

	/* ═══════════════════════════════════════════════════════
	 *  DISPLAY — Cart (with duplicate guard)
	 * ═══════════════════════════════════════════════════════ */
	public function display_cashback_in_cart() {
		static $done = false;
		if ($done) return;
		$done = true;
		$this->render_cashback_block('cart');
	}

	/* ═══════════════════════════════════════════════════════
	 *  DISPLAY — Checkout (with duplicate guard)
	 * ═══════════════════════════════════════════════════════ */
	public function display_cashback_in_checkout() {
		static $done = false;
		if ($done) return;
		$done = true;
		$this->render_cashback_block('checkout');
	}

	/* ═══════════════════════════════════════════════════════
	 *  DISPLAY — Potential earning (shown after order total)
	 * ═══════════════════════════════════════════════════════ */
	public function display_potential_earning_cart() {
		$this->render_potential_earning();
	}
	public function display_potential_earning_checkout() {
		$this->render_potential_earning();
	}

	/* ───────────────────────────────────────────────────────
	 *  Render potential earning row
	 * ─────────────────────────────────────────────────────── */
	private function render_potential_earning() {
		if (!class_exists('WCS_Cashback_Calculator') || !WC()->cart) {
			return;
		}

		$applied = $this->get_applied_amount();
		// If cashback is being used — no earning on this purchase
		if ($applied > 0) {
			echo '<tr class="wcs-potential-earning-row">';
			echo '<th>' . __('Кешбек з цього замовлення', 'woo-cashback-system') . '</th>';
			echo '<td><span class="wcs-no-earning">—</span> <small style="color:#999;">' . __('(не нараховується при використанні кешбеку)', 'woo-cashback-system') . '</small></td>';
			echo '</tr>';
			return;
		}

		$subtotal = floatval(WC()->cart->get_subtotal());
		$percentage = WCS_Cashback_Calculator::get_percentage($subtotal);
		$potential = WCS_Cashback_Calculator::calculate($subtotal);

		if ($potential > 0) {
			echo '<tr class="wcs-potential-earning-row">';
			echo '<th>' . __('Кешбек з цього замовлення', 'woo-cashback-system') . '</th>';
			echo '<td><span class="wcs-earn-amount">+' . wc_price($potential) . '</span> <small style="color:#999;">(' . $percentage . '%)</small></td>';
			echo '</tr>';
		}
	}

	/* ═══════════════════════════════════════════════════════
	 *  Render main cashback block (cart & checkout)
	 * ═══════════════════════════════════════════════════════ */
	private function render_cashback_block($context = 'cart') {
		// ── Duplicate guard (global-scope flag) ──
		$flag_key = 'wcs_rendered_' . $context;
		if (!empty($GLOBALS[$flag_key])) {
			return; // already rendered in this request
		}
		$GLOBALS[$flag_key] = true;

		if (!function_exists('wc_price')) {
			return;
		}

		$is_logged = is_user_logged_in();
		$user_id   = $is_logged ? get_current_user_id() : 0;

		// Get balance
		$balance = 0.00;
		if ($is_logged && class_exists('WCS_Cashback_Database')) {
			$data    = WCS_Cashback_Database::get_user_balance($user_id);
			$balance = $data ? floatval($data->balance) : 0.00;
		}

		// Cart subtotal for max calculations
		$cart_subtotal = 0.00;
		if (WC()->cart) {
			$cart_subtotal = floatval(WC()->cart->get_subtotal());
		}

		// Usage limit from settings
		$usage_pct   = class_exists('WCS_Cashback_Calculator') ? WCS_Cashback_Calculator::get_usage_limit_percentage() : 100;
		$max_allowed = min($balance, round($cart_subtotal * ($usage_pct / 100), 2));

		// Currently applied
		$applied = $this->get_applied_amount();

		// ── Render ──
		?>
		<div class="wcs-cashback-block" id="wcs-cashback-block-<?php echo esc_attr($context); ?>">
			<div class="wcs-cb-header">
				<span class="wcs-cb-icon">💰</span>
				<span class="wcs-cb-title"><?php _e('Кешбек', 'woo-cashback-system'); ?></span>
			</div>

			<?php if (!$is_logged) : ?>
				<p class="wcs-cb-login-msg">
					<?php printf(
						__('🔒 <a href="%s">Увійдіть</a>, щоб використати кешбек', 'woo-cashback-system'),
						esc_url(wp_login_url(wc_get_page_permalink('cart')))
					); ?>
				</p>
			<?php elseif ($balance <= 0) : ?>
				<div class="wcs-cb-balance-row">
					<span class="wcs-cb-label"><?php _e('Ваш баланс:', 'woo-cashback-system'); ?></span>
					<span class="wcs-cb-value"><?php echo wc_price(0); ?></span>
				</div>
				<p class="wcs-cb-empty"><?php _e('У вас поки немає накопиченого кешбеку.', 'woo-cashback-system'); ?></p>
			<?php else : ?>
				<div class="wcs-cb-balance-row">
					<span class="wcs-cb-label"><?php _e('Ваш баланс:', 'woo-cashback-system'); ?></span>
					<span class="wcs-cb-value wcs-cb-has-balance"><?php echo wc_price($balance); ?></span>
				</div>

				<?php if ($applied > 0) : ?>
					<!-- Cashback applied state -->
					<div class="wcs-cb-applied" id="wcs-cb-applied-<?php echo esc_attr($context); ?>">
						<div class="wcs-cb-applied-row">
							<span>✅ <?php _e('Використано:', 'woo-cashback-system'); ?></span>
							<span class="wcs-cb-applied-amount">-<?php echo wc_price($applied); ?></span>
						</div>
						<button type="button" class="wcs-cb-remove-btn" data-context="<?php echo esc_attr($context); ?>">
							<?php _e('Скасувати', 'woo-cashback-system'); ?>
						</button>
					</div>
					<p class="wcs-cb-no-earn-notice">
						⚠️ <?php _e('Кешбек не буде нараховано з цього замовлення', 'woo-cashback-system'); ?>
					</p>
				<?php else : ?>
					<!-- Cashback input form -->
					<div class="wcs-cb-form" id="wcs-cb-form-<?php echo esc_attr($context); ?>">
						<div class="wcs-cb-input-row">
							<label for="wcs-amount-<?php echo esc_attr($context); ?>">
								<?php _e('Сума:', 'woo-cashback-system'); ?>
							</label>
							<input
								type="number"
								id="wcs-amount-<?php echo esc_attr($context); ?>"
								class="wcs-cb-input"
								step="0.01"
								min="0"
								max="<?php echo esc_attr($max_allowed); ?>"
								value="<?php echo esc_attr($max_allowed); ?>"
								placeholder="0.00"
							/>
							<button type="button" class="wcs-cb-apply-btn" data-context="<?php echo esc_attr($context); ?>">
								<?php _e('Використати', 'woo-cashback-system'); ?>
							</button>
						</div>
						<small class="wcs-cb-max-info">
							<?php printf(
								__('Максимум: %s (до %s%% від суми замовлення)', 'woo-cashback-system'),
								wc_price($max_allowed),
								$usage_pct
							); ?>
						</small>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<div class="wcs-cb-message" id="wcs-cb-message-<?php echo esc_attr($context); ?>" style="display:none;"></div>
		</div>
		<?php
	}

	/* ═══════════════════════════════════════════════════════
	 *  Display potential cashback earning (shortcode support)
	 * ═══════════════════════════════════════════════════════ */
	public function display_potential_cashback_earning() {
		if (!class_exists('WCS_Cashback_Calculator') || !WC()->cart) {
			return;
		}

		$subtotal   = floatval(WC()->cart->get_subtotal());
		$percentage = WCS_Cashback_Calculator::get_percentage($subtotal);
		$potential   = WCS_Cashback_Calculator::calculate($subtotal);

		echo '<div class="wcs-potential-cashback">';
		echo '<strong>' . __('Потенційний кешбек:', 'woo-cashback-system') . '</strong> ';
		if ($potential > 0) {
			echo wc_price($potential) . ' (' . $percentage . '%)';
		} else {
			echo wc_price(0);
		}
		echo '</div>';
	}

	/* ═══════════════════════════════════════════════════════
	 *  CART FEE — apply cashback as negative fee
	 * ═══════════════════════════════════════════════════════ */
	public function apply_cashback_to_cart($cart) {
		if (is_admin() && !defined('DOING_AJAX')) {
			return;
		}
		if (!WC()->session) {
			return;
		}

		$amount = $this->get_applied_amount();
		if ($amount <= 0) {
			return;
		}

		$user_id = get_current_user_id();
		if (!$user_id) {
			return;
		}

		// Re-validate against current balance
		if (class_exists('WCS_Cashback_Database')) {
			$balance_data = WCS_Cashback_Database::get_user_balance($user_id);
			$balance = $balance_data ? floatval($balance_data->balance) : 0;
			if ($amount > $balance) {
				$amount = $balance;
			}
		}

		if ($amount > 0) {
			$cart->add_fee(__('Кешбек', 'woo-cashback-system'), -1 * $amount);
		}
	}

	/* ═══════════════════════════════════════════════════════
	 *  AJAX — Apply cashback
	 * ═══════════════════════════════════════════════════════ */
	public function ajax_apply_cashback() {
		// Accept both old and new nonce keys
		$nonce = '';
		if (isset($_POST['nonce'])) {
			$nonce = sanitize_text_field($_POST['nonce']);
		} elseif (isset($_POST['wcs_nonce'])) {
			$nonce = sanitize_text_field($_POST['wcs_nonce']);
		} elseif (isset($_POST['wcs_set_cashback_nonce'])) {
			$nonce = sanitize_text_field($_POST['wcs_set_cashback_nonce']);
		}

		if (!wp_verify_nonce($nonce, 'wcs_public_nonce') && !wp_verify_nonce($nonce, 'wcs_set_cashback')) {
			wp_send_json_error(array('message' => __('Невірний токен безпеки.', 'woo-cashback-system')));
		}

		if (!is_user_logged_in()) {
			wp_send_json_error(array('message' => __('Потрібно увійти в акаунт.', 'woo-cashback-system')));
		}

		$amount  = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
		$user_id = get_current_user_id();

		if ($amount <= 0) {
			$this->set_applied_amount(0);
			wp_send_json_success(array('amount' => 0, 'message' => __('Кешбек скинуто.', 'woo-cashback-system')));
		}

		// Validate against balance
		$balance = 0;
		if (class_exists('WCS_Cashback_Database')) {
			$data    = WCS_Cashback_Database::get_user_balance($user_id);
			$balance = $data ? floatval($data->balance) : 0;
		}

		// Validate against cart subtotal & usage limit
		$cart_subtotal = (WC()->cart) ? floatval(WC()->cart->get_subtotal()) : 0;
		$usage_pct     = class_exists('WCS_Cashback_Calculator') ? WCS_Cashback_Calculator::get_usage_limit_percentage() : 100;
		$max_allowed   = min($balance, round($cart_subtotal * ($usage_pct / 100), 2));

		if ($amount > $max_allowed) {
			$amount = $max_allowed;
		}

		$this->set_applied_amount($amount);

		wp_send_json_success(array(
			'amount'  => $amount,
			'message' => sprintf(__('Використано %s кешбеку!', 'woo-cashback-system'), wc_price($amount)),
		));
	}

	/* ═══════════════════════════════════════════════════════
	 *  AJAX — Remove cashback
	 * ═══════════════════════════════════════════════════════ */
	public function ajax_remove_cashback() {
		$nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
		if (!wp_verify_nonce($nonce, 'wcs_public_nonce')) {
			wp_send_json_error(array('message' => __('Невірний токен безпеки.', 'woo-cashback-system')));
		}

		$this->set_applied_amount(0);

		wp_send_json_success(array(
			'amount'  => 0,
			'message' => __('Кешбек скасовано.', 'woo-cashback-system'),
		));
	}

	/* ═══════════════════════════════════════════════════════
	 *  ORDER — Save cashback meta before order is created
	 * ═══════════════════════════════════════════════════════ */
	public function save_cashback_to_order($order, $data) {
		$applied = $this->get_applied_amount();

		if ($applied > 0) {
			$order->update_meta_data('_wcs_cashback_used', $applied);
			// Flag: cashback was used → do NOT earn
			$order->update_meta_data('_wcs_cashback_skip_earning', 'yes');
		} else {
			$order->update_meta_data('_wcs_cashback_used', 0);
			$order->update_meta_data('_wcs_cashback_skip_earning', 'no');
		}

		// Clear session
		$this->set_applied_amount(0);
	}

	/* ═══════════════════════════════════════════════════════
	 *  PAYMENT — Process cashback on completed payment
	 *
	 *  1. If customer USED cashback → deduct from balance, DO NOT earn new cashback
	 *  2. If customer did NOT use cashback → earn cashback based on tiers
	 * ═══════════════════════════════════════════════════════ */
	public function process_cashback_on_payment($order_id) {
		$order = wc_get_order($order_id);
		if (!$order) return;

		$user_id = $order->get_user_id();
		if (!$user_id) return;

		// Prevent double-processing
		if ($order->get_meta('_wcs_cashback_processed', true) === 'yes') {
			return;
		}

		if (!class_exists('WCS_Cashback_Database')) {
			return;
		}

		$cashback_used   = floatval($order->get_meta('_wcs_cashback_used', true));
		$skip_earning    = $order->get_meta('_wcs_cashback_skip_earning', true);

		// ── 1. Deduct used cashback ──
		if ($cashback_used > 0) {
			$before = floatval(WCS_Cashback_Database::get_user_balance($user_id)->balance);
			WCS_Cashback_Database::update_balance($user_id, $cashback_used, 'spent');
			$after  = floatval(WCS_Cashback_Database::get_user_balance($user_id)->balance);

			WCS_Cashback_Database::add_transaction(array(
				'user_id'          => $user_id,
				'order_id'         => $order_id,
				'transaction_type' => 'spent',
				'amount'           => $cashback_used,
				'balance_before'   => $before,
				'balance_after'    => $after,
				'order_total'      => floatval($order->get_total()),
				'description'      => sprintf('Використано кешбек в замовленні #%d', $order_id),
			));
		}

		// ── 2. Earn cashback (ONLY if NOT used) ──
		if ($skip_earning !== 'yes' && class_exists('WCS_Cashback_Calculator')) {
			// Use subtotal (before discounts) as basis for cashback calculation
			$subtotal = floatval($order->get_subtotal());
			$percentage = WCS_Cashback_Calculator::get_percentage($subtotal);
			$earned     = WCS_Cashback_Calculator::calculate($subtotal);

			if ($earned > 0) {
				// Check max limit
				$max_limit   = WCS_Cashback_Calculator::get_max_cashback_limit();
				$current_bal = floatval(WCS_Cashback_Database::get_user_balance($user_id)->balance);

				if ($max_limit > 0 && ($current_bal + $earned) > $max_limit) {
					$earned = max(0, $max_limit - $current_bal);
				}

				if ($earned > 0) {
					$before = floatval(WCS_Cashback_Database::get_user_balance($user_id)->balance);
					WCS_Cashback_Database::update_balance($user_id, $earned, 'earned');
					$after  = floatval(WCS_Cashback_Database::get_user_balance($user_id)->balance);

					WCS_Cashback_Database::add_transaction(array(
						'user_id'             => $user_id,
						'order_id'            => $order_id,
						'transaction_type'    => 'earned',
						'amount'              => $earned,
						'balance_before'      => $before,
						'balance_after'       => $after,
						'order_total'         => floatval($order->get_total()),
						'cashback_percentage' => $percentage,
						'description'         => sprintf('Нараховано %s%% кешбек з замовлення #%d', $percentage, $order_id),
					));

					// Save earned amount on order
					$order->update_meta_data('_wcs_cashback_earned', $earned);

					// Fire hook for notifications
					do_action('wcs_cashback_earned', $user_id, $earned, $order_id);
				}
			}
		}

		// Mark as processed
		$order->update_meta_data('_wcs_cashback_processed', 'yes');
		$order->save();
	}

	/* ═══════════════════════════════════════════════════════
	 *  Clear session after thank-you page
	 * ═══════════════════════════════════════════════════════ */
	public function clear_session_after_order($order_id) {
		$this->set_applied_amount(0);
	}

	/* ═══════════════════════════════════════════════════════
	 *  Helpers — session amount
	 * ═══════════════════════════════════════════════════════ */
	private function get_applied_amount() {
		if (!WC()->session) return 0;
		return floatval(WC()->session->get('wcs_applied_cashback', 0));
	}

	private function set_applied_amount($amount) {
		if (!WC()->session) return;
		WC()->session->set('wcs_applied_cashback', floatval($amount));
		// Legacy key cleanup
		WC()->session->set('wcs_cashback_to_use', floatval($amount));
	}
}
