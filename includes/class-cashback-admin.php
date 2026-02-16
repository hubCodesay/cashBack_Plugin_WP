<?php
/**
 * Admin Settings Class
 * Manages admin settings page and user management interface
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WCS_Cashback_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'), 99);
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_wcs_update_user_balance', array($this, 'ajax_update_user_balance'));
        add_action('wp_ajax_wcs_reset_user_balance', array($this, 'ajax_reset_user_balance'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page - Cashback as top-level menu
        add_menu_page(
            'Система Кешбеку',
            'Кешбек',
            'manage_woocommerce',
            'wcs-cashback',
            array($this, 'settings_page'),
            'dashicons-money-alt',
            55.5
        );
        
        // Dashboard submenu (rename first item)
        add_submenu_page(
            'wcs-cashback',
            'Налаштування Кешбеку',
            'Налаштування',
            'manage_woocommerce',
            'wcs-cashback',
            array($this, 'settings_page')
        );
        
        // Manage users submenu
        add_submenu_page(
            'wcs-cashback',
            'Управління Користувачами',
            'Користувачі',
            'manage_woocommerce',
            'wcs-cashback-users',
            array($this, 'users_page')
        );
        
        // Statistics submenu
        add_submenu_page(
            'wcs-cashback',
            'Статистика Кешбеку',
            'Статистика',
            'manage_woocommerce',
            'wcs-cashback-stats',
            array($this, 'statistics_page')
        );
        
        // User Details Page (Hidden)
        add_submenu_page(
            null, // Hidden from menu
            'Деталі Користувача',
            'Деталі',
            'manage_woocommerce',
            'wcs-cashback-user-detail',
            array($this, 'user_detail_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('wcs_cashback_settings_group', 'wcs_cashback_settings', array($this, 'sanitize_settings'));
    }
    
    /**
     * Sanitize settings
     */
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        // Get existing settings to prevent overwriting missing fields (due to tabs)
        $current_settings = get_option('wcs_cashback_settings');
        if (!is_array($current_settings)) {
            $current_settings = array();
        }
        
        $sanitized = $current_settings;
        
        // Update fields if they are present in input
        if (isset($input['enabled'])) $sanitized['enabled'] = 'yes';
        // Handle unchecked checkbox (if we are on the page where it exists)
        elseif (isset($_POST['_wp_http_referer']) && strpos($_POST['_wp_http_referer'], 'tab=general') !== false) $sanitized['enabled'] = 'no';
        
        if (isset($input['tier_1_threshold'])) $sanitized['tier_1_threshold'] = floatval($input['tier_1_threshold']);
        if (isset($input['tier_1_percentage'])) $sanitized['tier_1_percentage'] = floatval($input['tier_1_percentage']);
        if (isset($input['tier_2_threshold'])) $sanitized['tier_2_threshold'] = floatval($input['tier_2_threshold']);
        if (isset($input['tier_2_percentage'])) $sanitized['tier_2_percentage'] = floatval($input['tier_2_percentage']);
        if (isset($input['tier_3_threshold'])) $sanitized['tier_3_threshold'] = floatval($input['tier_3_threshold']);
        if (isset($input['tier_3_percentage'])) $sanitized['tier_3_percentage'] = floatval($input['tier_3_percentage']);
        if (isset($input['max_cashback_limit'])) $sanitized['max_cashback_limit'] = floatval($input['max_cashback_limit']);
        if (isset($input['usage_limit_percentage'])) $sanitized['usage_limit_percentage'] = floatval($input['usage_limit_percentage']);
        
        if (isset($input['enable_notifications'])) $sanitized['enable_notifications'] = 'yes';
        elseif (isset($_POST['_wp_http_referer']) && strpos($_POST['_wp_http_referer'], 'tab=general') !== false) $sanitized['enable_notifications'] = 'no';
        
        // Display Settings
        if (isset($input['cart_position'])) $sanitized['cart_position'] = sanitize_text_field($input['cart_position']);
        if (isset($input['checkout_position'])) $sanitized['checkout_position'] = sanitize_text_field($input['checkout_position']);
        
        return $sanitized;
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
        
        $settings = get_option('wcs_cashback_settings');
        
        // Встановлення значень за замовчуванням, якщо налаштування ще не збережені
        if (!is_array($settings)) {
            $settings = array(
                'enabled' => 'yes',
                'tier_1_threshold' => 500,
                'tier_1_percentage' => 3,
                'tier_2_threshold' => 1000,
                'tier_2_percentage' => 5,
                'tier_3_threshold' => 1500,
                'tier_3_percentage' => 7,
                'max_cashback_limit' => 10000,
                'usage_limit_percentage' => 50,
                'enable_notifications' => 'yes',
                // New display settings
                'cart_position' => 'woocommerce_cart_totals_before_order_total',
                'checkout_position' => 'woocommerce_review_order_before_payment'
            );
        }
        
        // Ensure defaults for new settings exist (for existing installs)
        $settings['cart_position'] = isset($settings['cart_position']) ? $settings['cart_position'] : 'woocommerce_cart_totals_before_order_total';
        $settings['checkout_position'] = isset($settings['checkout_position']) ? $settings['checkout_position'] : 'woocommerce_review_order_before_payment';
        
        ?>
        <div class="wrap">
            <h1>⚙️ Налаштування Системи Кешбеку</h1>
            <p class="description">Тут ви можете налаштувати всі параметри системи кешбеку для вашого магазину</p>
            
            <h2 class="nav-tab-wrapper">
                <a href="?page=wcs-cashback&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">🛠️ Загальні</a>
                <a href="?page=wcs-cashback&tab=display" class="nav-tab <?php echo $active_tab == 'display' ? 'nav-tab-active' : ''; ?>">🎨 Вигляд</a>
            </h2>
            
            <?php settings_errors('wcs_cashback_settings'); ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('wcs_cashback_settings_group');
                
                if ($active_tab == 'general'):
                ?>
                
                <!-- GENERAL TAB CONTENT (Existing) -->
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enabled">🔌 Увімкнути Систему Кешбеку</label>
                        </th>
                        <td>
                            <input type="checkbox" name="wcs_cashback_settings[enabled]" id="enabled" value="yes" <?php checked($settings['enabled'], 'yes'); ?>>
                            <p class="description">
                                ✅ Увімкніть цей параметр, щоб активувати систему кешбеку для всіх користувачів.<br>
                                ❌ Якщо вимкнено - користувачі не зможуть заробляти або використовувати кешбек.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th colspan="2">
                            <h2>🎯 Рівні Кешбеку (Тарифи)</h2>
                            <p class="description">Налаштуйте відсотки кешбеку залежно від суми замовлення. Чим більше замовлення, тим більший відсоток кешбеку отримає клієнт.</p>
                        </th>
                    </tr>
                    
                    <tr style="background: #f0f9ff;">
                        <th scope="row">
                            <label for="tier_1_threshold">🥉 Рівень 1: Мінімальна сума замовлення (грн)</label>
                        </th>
                        <td>
                            <input type="number" step="0.01" name="wcs_cashback_settings[tier_1_threshold]" id="tier_1_threshold" value="<?php echo esc_attr($settings['tier_1_threshold']); ?>" class="regular-text" style="width: 200px;">
                            <p class="description">
                                💡 <strong>Що це:</strong> Мінімальна сума замовлення, при якій клієнт почне отримувати кешбек.<br>
                                📌 <strong>Рекомендація:</strong> 500 грн - це базовий поріг для початку нарахування кешбеку.
                            </p>
                        </td>
                    </tr>
                    
                    <tr style="background: #f0f9ff;">
                        <th scope="row">
                            <label for="tier_1_percentage">🥉 Рівень 1: Відсоток кешбеку (%)</label>
                        </th>
                        <td>
                            <input type="number" step="0.01" name="wcs_cashback_settings[tier_1_percentage]" id="tier_1_percentage" value="<?php echo esc_attr($settings['tier_1_percentage']); ?>" class="regular-text" style="width: 200px;">
                            <p class="description">
                                💡 <strong>Що це:</strong> Скільки відсотків від суми замовлення повернеться клієнту як кешбек.
                            </p>
                        </td>
                    </tr>
                    
                    <tr style="background: #fff8e1;">
                        <th scope="row">
                            <label for="tier_2_threshold">🥈 Рівень 2: Мінімальна сума замовлення (грн)</label>
                        </th>
                        <td>
                            <input type="number" step="0.01" name="wcs_cashback_settings[tier_2_threshold]" id="tier_2_threshold" value="<?php echo esc_attr($settings['tier_2_threshold']); ?>" class="regular-text" style="width: 200px;">
                        </td>
                    </tr>
                    
                    <tr style="background: #fff8e1;">
                        <th scope="row">
                            <label for="tier_2_percentage">🥈 Рівень 2: Відсоток кешбеку (%)</label>
                        </th>
                        <td>
                            <input type="number" step="0.01" name="wcs_cashback_settings[tier_2_percentage]" id="tier_2_percentage" value="<?php echo esc_attr($settings['tier_2_percentage']); ?>" class="regular-text" style="width: 200px;">
                        </td>
                    </tr>
                    
                    <tr style="background: #e8f5e9;">
                        <th scope="row">
                            <label for="tier_3_threshold">🥇 Рівень 3: Мінімальна сума замовлення (грн)</label>
                        </th>
                        <td>
                            <input type="number" step="0.01" name="wcs_cashback_settings[tier_3_threshold]" id="tier_3_threshold" value="<?php echo esc_attr($settings['tier_3_threshold']); ?>" class="regular-text" style="width: 200px;">
                        </td>
                    </tr>
                    
                    <tr style="background: #e8f5e9;">
                        <th scope="row">
                            <label for="tier_3_percentage">🥇 Рівень 3: Відсоток кешбеку (%)</label>
                        </th>
                        <td>
                            <input type="number" step="0.01" name="wcs_cashback_settings[tier_3_percentage]" id="tier_3_percentage" value="<?php echo esc_attr($settings['tier_3_percentage']); ?>" class="regular-text" style="width: 200px;">
                        </td>
                    </tr>
                    
                    <tr>
                        <th colspan="2">
                            <h2>🛡️ Обмеження та Ліміти</h2>
                        </th>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="max_cashback_limit">💰 Максимальний Ліміт Накопичення (грн)</label>
                        </th>
                        <td>
                            <input type="number" step="0.01" name="wcs_cashback_settings[max_cashback_limit]" id="max_cashback_limit" value="<?php echo esc_attr($settings['max_cashback_limit']); ?>" class="regular-text" style="width: 200px;">
                            <p class="description">Максимальна сума на балансі.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="usage_limit_percentage">🎯 Ліміт Використання за Одне Замовлення (%)</label>
                        </th>
                        <td>
                            <input type="number" step="0.01" name="wcs_cashback_settings[usage_limit_percentage]" id="usage_limit_percentage" value="<?php echo esc_attr($settings['usage_limit_percentage']); ?>" class="regular-text" style="width: 200px;">
                            <p class="description">Відсоток від суми замовлення, який можна оплатити кешбеком.</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="enable_notifications">✉️ Увімкнути Email-Сповіщення</label>
                        </th>
                        <td>
                            <input type="checkbox" name="wcs_cashback_settings[enable_notifications]" id="enable_notifications" value="yes" <?php checked($settings['enable_notifications'], 'yes'); ?>>
                            <p class="description">Сповіщати клієнтів про нарахування та списання.</p>
                        </td>
                    </tr>
                </table>
                
                <div class="wcs-info-box" style="border-left-color: #ffc107;">
                    <h3>💡 Швидкі Підказки:</h3>
                    <ul style="margin-bottom: 0;">
                        <li><strong>Базові налаштування:</strong> 500/3%, 1000/5%, 1500/7% - перевірені показники</li>
                        <li><strong>Максимальний ліміт:</strong> Розрахуйте виходячи з вашого середнього чека та кількості клієнтів</li>
                    </ul>
                </div>

                <?php elseif ($active_tab == 'display'): ?>
                
                <!-- DISPLAY TAB CONTENT (New) -->
                <table class="form-table">
                    <tr>
                        <th colspan="2">
                            <h2>🎨 Налаштування Вигляду</h2>
                            <p class="description">Виберіть, де саме відображати блоки використання кешбеку.</p>
                        </th>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cart_position">🛒 Позиція в Кошику</label>
                        </th>
                        <td>
                            <select name="wcs_cashback_settings[cart_position]" id="cart_position" style="min-width: 300px;">
                                <option value="woocommerce_cart_totals_before_order_total" <?php selected($settings['cart_position'], 'woocommerce_cart_totals_before_order_total'); ?>>В таблиці підсумків (Стандартно)</option>
                                <option value="woocommerce_before_cart_totals" <?php selected($settings['cart_position'], 'woocommerce_before_cart_totals'); ?>>Перед таблицею підсумків (Зліва/Зверху)</option>
                                <option value="woocommerce_after_cart_totals" <?php selected($settings['cart_position'], 'woocommerce_after_cart_totals'); ?>>Після таблиці підсумків</option>
                                <option value="woocommerce_before_cart" <?php selected($settings['cart_position'], 'woocommerce_before_cart'); ?>>Над кошиком (Верх сторінки)</option>
                                <option value="none" <?php selected($settings['cart_position'], 'none'); ?>>❌ Не відображати в кошику</option>
                            </select>
                            <p class="description">
                                Виберіть місце, де з'явиться блок "Ваш кешбек / Застосувати".<br>
                                💡 Найкращий варіант - "В таблиці підсумків", це виглядає найбільш органічно.
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="checkout_position">💳 Позиція при Оформленні (Checkout)</label>
                        </th>
                        <td>
                            <select name="wcs_cashback_settings[checkout_position]" id="checkout_position" style="min-width: 300px;">
                                <option value="woocommerce_review_order_before_payment" <?php selected($settings['checkout_position'], 'woocommerce_review_order_before_payment'); ?>>Перед кнопкою оплати (Стандартно)</option>
                                <option value="woocommerce_review_order_before_order_total" <?php selected($settings['checkout_position'], 'woocommerce_review_order_before_order_total'); ?>>Перед підсумком замовлення</option>
                                <option value="woocommerce_review_order_after_order_total" <?php selected($settings['checkout_position'], 'woocommerce_review_order_after_order_total'); ?>>Після підсумку замовлення</option>
                                <option value="woocommerce_before_checkout_form" <?php selected($settings['checkout_position'], 'woocommerce_before_checkout_form'); ?>>Над формою (Верх сторінки)</option>
                                <option value="none" <?php selected($settings['checkout_position'], 'none'); ?>>❌ Не відображати при оформленні</option>
                            </select>
                            <p class="description">
                                Де виводити блок використання кешбеку на сторінці оплати.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <div class="wcs-info-box" style="border-left-color: #2271b1;">
                    <h3>ℹ️ Інфо про відображення:</h3>
                    <p style="margin-bottom: 0;">
                        Якщо ви змінюєте позицію, але не бачите змін - спробуйте очистити кеш вашого браузера або плагіна кешування.<br>
                        Деякі позиції можуть виглядати по-різному в залежності від вашої теми WooCommerce.
                    </p>
                </div>
                
                <?php endif; ?>
                
                <!-- Hidden inputs for preserving tab state on save (optional but good practice) -->
                <input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr(add_query_arg('tab', $active_tab, 'admin.php?page=wcs-cashback')); ?>">
                
                <?php submit_button('💾 Зберегти Налаштування', 'primary', 'submit', true, array('style' => 'font-size: 16px; padding: 10px 30px;')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Users management page
     */
    public function users_page() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 20;
        $offset = ($paged - 1) * $per_page;
        
        $users = WCS_Cashback_Database::get_all_users_with_cashback('balance', 'DESC', $per_page, $offset);
        $total_users = WCS_Cashback_Database::count_users_with_cashback();
        $total_pages = ceil($total_users / $per_page);
        
        ?>
        <div class="wrap">
            <h1>👥 Управління Користувачами Кешбеку</h1>
            <p class="description">Перегляд, редагування балансів та індивідуальних лімітів для кожного користувача</p>
            
            <div class="wcs-info-box" style="border-left-color: #2271b1;">
                <h3>ℹ️ Що ви можете робити тут:</h3>
                <ul style="margin-bottom: 0;">
                    <li><strong>Переглядати баланси:</strong> Бачити скільки кешбеку накопичив кожен клієнт та історію транзакцій</li>
                    <li><strong>Встановлювати індивідуальні ліміти:</strong> Задавати персональні максимальні ліміти для VIP-клієнтів</li>
                    <li><strong>Скидати баланс:</strong> Обнуляти кешбек користувача (наприклад, при порушенні правил)</li>
                    <li><strong>Переглядати деталі:</strong> Докладна історія всіх операцій з кешбеком користувача</li>
                </ul>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>👤 Користувач</th>
                        <th>💰 Поточний Баланс</th>
                        <th>📈 Всього Заробив</th>
                        <th>📉 Всього Використав</th>
                        <th style="width: 200px;">🔒 Максимальний Ліміт</th>
                        <th>🕐 Останнє Оновлення</th>
                        <th style="width: 220px;">⚙️ Дії</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users): ?>
                        <?php foreach ($users as $user_data): ?>
                            <?php
                            $user = get_userdata($user_data->user_id);
                            if (!$user) continue;
                            
                            $settings = get_option('wcs_cashback_settings');
                            $global_limit = isset($settings['max_cashback_limit']) ? $settings['max_cashback_limit'] : 10000;
                            $max_limit = !empty($user_data->max_limit) ? $user_data->max_limit : $global_limit;
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($user->display_name); ?></strong><br>
                                    <small style="color: #666;"><?php echo esc_html($user->user_email); ?></small>
                                </td>
                                <td>
                                    <strong style="font-size: 15px; color: #2e7d32;"><?php echo wc_price($user_data->balance); ?></strong>
                                </td>
                                <td style="color: #1976d2;"><?php echo wc_price($user_data->total_earned); ?></td>
                                <td style="color: #d32f2f;"><?php echo wc_price($user_data->total_spent); ?></td>
                                <td>
                                    <input type="number" step="0.01" value="<?php echo esc_attr($max_limit); ?>" 
                                           class="wcs-user-max-limit" data-user-id="<?php echo $user_data->user_id; ?>" 
                                           style="width: 90px;" title="Введіть новий ліміт та натисніть 'Оновити'">
                                    <button class="button wcs-update-limit" data-user-id="<?php echo $user_data->user_id; ?>" title="Зберегти новий ліміт">
                                        💾 Оновити
                                    </button>
                                </td>
                                <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($user_data->updated_at)); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=wcs-cashback-user-detail&user_id=' . $user_data->user_id); ?>" 
                                       class="button" title="Переглянути всю історію транзакцій">
                                        📋 Деталі
                                    </a>
                                    <button class="button wcs-reset-balance" data-user-id="<?php echo $user_data->user_id; ?>" 
                                            title="Обнулити баланс кешбеку">
                                        🔄 Скинути
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                                <div style="font-size: 48px;">😔</div>
                                <p style="font-size: 16px; margin: 10px 0 0 0;">
                                    Поки що немає користувачів з кешбеком.<br>
                                    <small>Користувачі з'являться тут після першого нарахування кешбеку.</small>
                                </p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => __('&laquo; Попередня'),
                            'next_text' => __('Наступна &raquo;'),
                            'total' => $total_pages,
                            'current' => $paged,
                        ));
                        ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="wcs-info-box" style="border-left-color: #ffc107;">
                <h3>💡 Підказки по роботі з користувачами:</h3>
                <ul style="margin-bottom: 0;">
                    <li><strong>Поточний Баланс:</strong> Скільки кешбеку зараз доступно користувачу для використання</li>
                    <li><strong>Всього Заробив:</strong> Загальна сума кешбеку нарахована за весь час (включаючи вже використаний)</li>
                    <li><strong>Всього Використав:</strong> Скільки кешбеку користувач витратив на оплату замовлень</li>
                    <li><strong>Максимальний Ліміт:</strong> Встановлюйте вищі ліміти для VIP-клієнтів (наприклад, 20000 грн замість стандартних 10000 грн)</li>
                    <li><strong>Скинути баланс:</strong> Обнулює тільки поточний баланс, історія транзакцій зберігається в розділі "Деталі"</li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * User Details Page
     */
    public function user_detail_page() {
        if (!current_user_can('manage_woocommerce')) return;
        
        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        $user = get_userdata($user_id);
        
        if (!$user) {
            echo '<div class="wrap"><div class="notice notice-error"><p>Користувача не знайдено.</p></div></div>';
            return;
        }
        
        $balance_data = WCS_Cashback_Database::get_user_balance($user_id);
        $transactions = WCS_Cashback_Database::get_user_transactions($user_id, 100);
        
        // Ensure numbers
        $balance = isset($balance_data->balance) ? floatval($balance_data->balance) : 0;
        $earned = isset($balance_data->total_earned) ? floatval($balance_data->total_earned) : 0;
        $spent = isset($balance_data->total_spent) ? floatval($balance_data->total_spent) : 0;
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">👤 Кешбек: <?php echo esc_html($user->display_name); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=wcs-cashback-users'); ?>" class="page-title-action">← Назад до списку</a>
            <hr class="wp-header-end">
            
            <div class="wcs-info-box" style="margin-top: 20px; border-left-color: #2271b1;">
                <p style="margin: 0;">
                    <strong>Email:</strong> <?php echo esc_html($user->user_email); ?> | 
                    <strong>ID:</strong> <?php echo $user_id; ?> | 
                    <strong>Зареєстрований:</strong> <?php echo date_i18n(get_option('date_format'), strtotime($user->user_registered)); ?>
                </p>
            </div>
            
            <div class="wcs-stats-grid">
                 <div class="wcs-stat-box balance">
                    <h3>Поточний Баланс</h3>
                    <p class="wcs-stat-value"><?php echo wc_price($balance); ?></p>
                 </div>
                 <div class="wcs-stat-box earned">
                    <h3>Всього Зароблено</h3>
                    <p class="wcs-stat-value"><?php echo wc_price($earned); ?></p>
                 </div>
                 <div class="wcs-stat-box spent">
                    <h3>Всього Витрачено</h3>
                    <p class="wcs-stat-value"><?php echo wc_price($spent); ?></p>
                 </div>
            </div>
            
            <h2 style="margin-top: 30px; margin-bottom: 20px;">📋 Історія Транзакцій</h2>
            
            <div class="card" style="padding: 0; margin-top: 0; max-width: 100%;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Тип</th>
                            <th>Замовлення</th>
                            <th>Сума</th>
                            <th>Баланс Після</th>
                            <th>Опис</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($transactions && count($transactions) > 0): ?>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo date_i18n('d.m.Y H:i', strtotime($transaction->created_at)); ?></td>
                                    <td>
                                        <?php 
                                        $type_labels = array(
                                            'earned' => '<span class="wcs-balance-earned">✅ Нараховано</span>',
                                            'spent' => '<span class="wcs-balance-spent">💳 Витрачено</span>',
                                            'adjustment' => '<span style="color:#2271b1;">⚙️ Коригування</span>'
                                        );
                                        echo isset($type_labels[$transaction->transaction_type]) ? $type_labels[$transaction->transaction_type] : $transaction->transaction_type; 
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($transaction->order_id > 0): ?>
                                            <a href="<?php echo get_edit_post_link($transaction->order_id); ?>">#<?php echo $transaction->order_id; ?></a>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $color = ($transaction->transaction_type === 'spent') ? '#d63638' : '#00a32a';
                                        $sign = ($transaction->transaction_type === 'earned') ? '+' : ($transaction->transaction_type === 'spent' ? '-' : '');
                                        echo '<strong style="color:'.$color.';">' . $sign . wc_price($transaction->amount) . '</strong>';
                                        ?>
                                    </td>
                                    <td><strong><?php echo wc_price($transaction->balance_after); ?></strong></td>
                                    <td><?php echo esc_html($transaction->description); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; padding: 20px;">Історія транзакцій порожня.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    public function statistics_page() {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        
        $stats = WCS_Cashback_Database::get_statistics();
        
        // Перевірка на null і встановлення дефолтних значень
        if (!$stats) {
            $stats = (object) array(
                'total_balance' => 0,
                'total_earned' => 0,
                'total_spent' => 0,
                'total_users' => 0
            );
        }
        
        // Переконатися що всі властивості існують
        $stats->total_balance = isset($stats->total_balance) ? floatval($stats->total_balance) : 0;
        $stats->total_earned = isset($stats->total_earned) ? floatval($stats->total_earned) : 0;
        $stats->total_spent = isset($stats->total_spent) ? floatval($stats->total_spent) : 0;
        $stats->total_users = isset($stats->total_users) ? intval($stats->total_users) : 0;
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">📊 Статистика Системи Кешбеку</h1>
            <p class="description">Загальна інформація про роботу системи кешбеку в вашому магазині</p>
            
            <div class="wcs-info-box" style="border-left-color: #4caf50;">
                <h3>💡 Як читати статистику:</h3>
                <p style="margin: 0;">
                    <strong>Загальний Активний Баланс</strong> - це сума всього кешбеку на рахунках користувачів.<br>
                    Це ваші потенційні знижки, якщо всі користувачі вирішать витратити свій кешбек.
                </p>
            </div>
            
            <div class="wcs-stats-grid">
                <div class="wcs-stat-box balance">
                    <h3>💰 АКТИВНИЙ БАЛАНС</h3>
                    <p class="wcs-stat-value">
                        <?php echo wc_price($stats->total_balance); ?>
                    </p>
                    <div class="wcs-stat-desc">
                        <strong>Доступно клієнтам.</strong><br>Сума кешбеку на руках у всіх користувачів зараз.
                    </div>
                </div>
                
                <div class="wcs-stat-box earned">
                    <h3>📈 ВСЬОГО НАРАХОВАНО (+EARNED)</h3>
                    <p class="wcs-stat-value">
                        <?php echo wc_price($stats->total_earned); ?>
                    </p>
                    <div class="wcs-stat-desc">
                        <strong>Історичний максимум.</strong><br>Стільки бонусів ви видали за весь час роботи.
                    </div>
                </div>
                
                <div class="wcs-stat-box spent">
                    <h3>📉 ВСЬОГО ВИТРАЧЕНО (-SPENT)</h3>
                    <p class="wcs-stat-value">
                        <?php echo wc_price($stats->total_spent); ?>
                    </p>
                    <div class="wcs-stat-desc">
                        <strong>Реальна економія.</strong><br>На таку суму клієнти зменшили свої чеки.
                    </div>
                </div>
                
                <div class="wcs-stat-box users">
                    <h3>👥 КОРИСТУВАЧІВ</h3>
                    <p class="wcs-stat-value">
                        <?php echo number_format($stats->total_users); ?>
                    </p>
                    <div class="wcs-stat-desc">
                        <strong>Учасники програми.</strong><br>Кількість клієнтів, що мають історію кешбеку.
                    </div>
                </div>
            </div>
            
            <div class="wcs-info-box" style="border-left-color: #ffc107;">
                <h3>📊 Аналіз Показників:</h3>
                <ul style="margin-left: 15px;">
                    <li><strong>Коефіцієнт використання:</strong> 
                        <strong><?php 
                        $usage_rate = $stats->total_earned > 0 ? ($stats->total_spent / $stats->total_earned) * 100 : 0;
                        echo number_format($usage_rate, 1); 
                        ?>%</strong>
                        <span class="description">(Відсоток нарахованого кешбеку, який реально використовується)</span>
                    </li>
                    <li><strong>Середній баланс на користувача:</strong> 
                        <strong><?php 
                        $avg_balance = $stats->total_users > 0 ? $stats->total_balance / $stats->total_users : 0;
                        echo wc_price($avg_balance); 
                        ?></strong>
                        <span class="description">(середня сума на одному рахунку)</span>
                    </li>
                    <li><strong>Оптимальний рівень:</strong> 40-60%
                        <span class="description">(баланс між накопиченням та витратами)</span>
                    </li>
                    <li style="margin-top: 10px;"><strong>Рекомендація:</strong> 
                        <?php if ($usage_rate < 30): ?>
                            <span style="color: #d63638; font-weight: 500;">⚠️ Низький показник. Нагадайте клієнтам про кешбек через email.</span>
                        <?php elseif ($usage_rate > 70): ?>
                            <span style="color: #d63638; font-weight: 500;">⚠️ Дуже високий показник. Можливо варто знизити відсотки.</span>
                        <?php else: ?>
                            <span style="color: #00a32a; font-weight: 500;">✅ Оптимальний баланс!</span>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Update user max limit
     */
    public function ajax_update_user_balance() {
        check_ajax_referer('wcs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => '❌ Доступ заборонено'));
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $max_limit = isset($_POST['max_limit']) ? floatval($_POST['max_limit']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(array('message' => '❌ Невірний ID користувача'));
        }
        
        WCS_Cashback_Database::set_user_max_limit($user_id, $max_limit);
        
        wp_send_json_success(array('message' => '✅ Максимальний ліміт успішно оновлено'));
    }
    
    /**
     * AJAX: Reset user balance
     */
    public function ajax_reset_user_balance() {
        check_ajax_referer('wcs_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => '❌ Доступ заборонено'));
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(array('message' => '❌ Невірний ID користувача'));
        }
        
        // Get current balance
        $balance_data = WCS_Cashback_Database::get_user_balance($user_id);
        $balance_before = floatval($balance_data->balance);
        
        // Reset balance to 0
        WCS_Cashback_Database::update_balance($user_id, 0, 'adjustment');
        
        // Add transaction record
        WCS_Cashback_Database::add_transaction(array(
            'user_id' => $user_id,
            'order_id' => 0,
            'transaction_type' => 'adjustment',
            'amount' => $balance_before,
            'balance_before' => $balance_before,
            'balance_after' => 0,
            'description' => 'Баланс обнулено адміністратором',
        ));
        
        wp_send_json_success(array('message' => '✅ Баланс успішно скинуто'));
    }
}
