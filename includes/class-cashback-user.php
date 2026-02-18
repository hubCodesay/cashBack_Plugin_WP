<?php
/**
 * User Dashboard Class
 * Restored minimal implementation for WCS_Cashback_User
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WCS_Cashback_User {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('woocommerce_account_menu_items', array($this, 'add_cashback_menu_item'));
        add_action('init', array($this, 'add_cashback_endpoint'));
        add_action('woocommerce_account_cashback_endpoint', array($this, 'cashback_content'));

        add_action('woocommerce_account_dashboard', array($this, 'display_dashboard_widget'));
        add_action('woocommerce_order_details_after_order_table', array($this, 'display_order_cashback_info'));
    }

    public function add_cashback_menu_item($items) {
        $new_items = array();
        foreach ($items as $key => $value) {
            $new_items[$key] = $value;
            if ($key === 'dashboard') {
                $new_items['cashback'] = '💰 Мій Кешбек';
            }
        }
        return $new_items;
    }

    public function add_cashback_endpoint() {
        add_rewrite_endpoint('cashback', EP_ROOT | EP_PAGES);
    }

    public function cashback_content() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>Будь ласка, увійдіть до свого облікового запису.</p>';
            return;
        }

        if (!class_exists('WCS_Cashback_Database')) {
            echo '<p>Cashback data unavailable.</p>';
            return;
        }

        $balance_data = WCS_Cashback_Database::get_user_balance($user_id);
        $balance = $balance_data ? floatval($balance_data->balance) : 0.00;
        $total_earned = $balance_data ? floatval($balance_data->total_earned) : 0.00;
        $total_spent = $balance_data ? floatval($balance_data->total_spent) : 0.00;

        echo '<div class="wcs-cashback-dashboard">';
        echo '<h2>💰 Мій Баланс Кешбеку</h2>';
        echo '<p><strong>' . __('Поточний баланс:', 'woo-cashback-system') . '</strong> ' . wc_price($balance) . '</p>';
        echo '<p><strong>' . __('Всього зароблено:', 'woo-cashback-system') . '</strong> ' . wc_price($total_earned) . '</p>';
        echo '<p><strong>' . __('Використано:', 'woo-cashback-system') . '</strong> ' . wc_price($total_spent) . '</p>';
        echo '</div>';
    }

    public function display_dashboard_widget() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }

        if (!class_exists('WCS_Cashback_Database')) {
            return;
        }

        $balance_data = WCS_Cashback_Database::get_user_balance($user_id);
        if (!$balance_data || !isset($balance_data->balance)) {
            return;
        }

        $balance = floatval($balance_data->balance);
        if ($balance <= 0) {
            return;
        }

        echo '<div class="wcs-dashboard-widget">';
        echo '<h3>' . __('Your Cashback', 'woo-cashback-system') . '</h3>';
        echo '<p class="wcs-widget-balance">' . wc_price($balance) . '</p>';
        echo '<p>' . __('Available to use on your next purchase!', 'woo-cashback-system') . '</p>';
        echo '<a class="button" href="' . esc_url(wc_get_account_endpoint_url('cashback')) . '">' . __('View Details', 'woo-cashback-system') . '</a>';
        echo '</div>';
    }

    public function display_order_cashback_info($order) {
        if (is_int($order)) {
            $order = wc_get_order($order);
        }
        if (!$order) {
            return;
        }

        $cashback_earned = floatval($order->get_meta('_wcs_cashback_earned', true));
        $cashback_used = floatval($order->get_meta('_wcs_cashback_used', true));

        if ($cashback_earned <= 0 && $cashback_used <= 0) {
            return;
        }

        echo '<div class="wcs-order-cashback-info">';
        if ($cashback_earned > 0) {
            echo '<p><strong>' . __('Cashback Earned:', 'woo-cashback-system') . '</strong> ' . wc_price($cashback_earned) . '</p>';
        }
        if ($cashback_used > 0) {
            echo '<p><strong>' . __('Cashback Used:', 'woo-cashback-system') . '</strong> ' . wc_price($cashback_used) . '</p>';
        }
        echo '</div>';
    }
}
