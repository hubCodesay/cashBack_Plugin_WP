<?php
/**
 * Notifications Class
 * Handles email notifications for cashback events
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WCS_Cashback_Notifications {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook into cashback events
        add_action('wcs_cashback_earned', array($this, 'notify_cashback_earned'), 10, 3);
        add_action('woocommerce_order_status_completed', array($this, 'notify_cashback_used'), 20, 1);
    }
    
    /**
     * Check if notifications are enabled
     */
    private function are_notifications_enabled() {
        $settings = get_option('wcs_cashback_settings');
        return isset($settings['enable_notifications']) && $settings['enable_notifications'] === 'yes';
    }
    
    /**
     * Notify user when cashback is earned
     */
    public function notify_cashback_earned($user_id, $cashback_amount, $order_id) {
        if (!$this->are_notifications_enabled()) {
            return;
        }
        
        $user = get_userdata($user_id);
        
        if (!$user) {
            return;
        }
        
        $order = wc_get_order($order_id);
        $balance_data = WCS_Cashback_Database::get_user_balance($user_id);
        $new_balance = floatval($balance_data->balance);
        
        $to = $user->user_email;
        $subject = sprintf(__('You earned %s cashback!', 'woo-cashback-system'), wc_price($cashback_amount));
        
        $message = $this->get_email_template('earned', array(
            'user_name' => $user->display_name,
            'cashback_amount' => wc_price($cashback_amount),
            'order_id' => $order_id,
            'order_total' => wc_price($order->get_total()),
            'new_balance' => wc_price($new_balance),
            'cashback_url' => wc_get_account_endpoint_url('cashback'),
        ));
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Notify user when cashback is used
     */
    public function notify_cashback_used($order_id) {
        if (!$this->are_notifications_enabled()) {
            return;
        }
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        $cashback_used = floatval($order->get_meta('_wcs_cashback_used', true));
        
        if ($cashback_used <= 0) {
            return;
        }
        
        $user_id = $order->get_user_id();
        $user = get_userdata($user_id);
        
        if (!$user) {
            return;
        }
        
        $balance_data = WCS_Cashback_Database::get_user_balance($user_id);
        $remaining_balance = floatval($balance_data->balance);
        
        $to = $user->user_email;
        $subject = sprintf(__('You used %s cashback on order #%s', 'woo-cashback-system'), wc_price($cashback_used), $order_id);
        
        $message = $this->get_email_template('used', array(
            'user_name' => $user->display_name,
            'cashback_used' => wc_price($cashback_used),
            'order_id' => $order_id,
            'order_total' => wc_price($order->get_total()),
            'remaining_balance' => wc_price($remaining_balance),
            'cashback_url' => wc_get_account_endpoint_url('cashback'),
        ));
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Get email template
     */
    private function get_email_template($type, $args) {
        $default_args = array(
            'user_name' => '',
            'cashback_amount' => '',
            'cashback_used' => '',
            'order_id' => '',
            'order_total' => '',
            'new_balance' => '',
            'remaining_balance' => '',
            'cashback_url' => '',
        );
        
        $args = wp_parse_args($args, $default_args);
        
        ob_start();
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    background-color: #f5f5f5;
                    margin: 0;
                    padding: 0;
                }
                .email-container {
                    max-width: 600px;
                    margin: 20px auto;
                    background-color: #ffffff;
                    padding: 30px;
                    border-radius: 5px;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                }
                .email-header {
                    text-align: center;
                    padding-bottom: 20px;
                    border-bottom: 2px solid #f0f0f0;
                }
                .email-header h1 {
                    color: #2c3e50;
                    margin: 0;
                }
                .email-content {
                    padding: 20px 0;
                }
                .highlight {
                    background-color: #e8f5e9;
                    padding: 15px;
                    border-radius: 5px;
                    margin: 20px 0;
                    text-align: center;
                }
                .highlight-amount {
                    font-size: 24px;
                    font-weight: bold;
                    color: #4caf50;
                }
                .info-box {
                    background-color: #f9f9f9;
                    padding: 15px;
                    border-left: 4px solid #2196F3;
                    margin: 15px 0;
                }
                .button {
                    display: inline-block;
                    padding: 12px 30px;
                    background-color: #4caf50;
                    color: #ffffff;
                    text-decoration: none;
                    border-radius: 5px;
                    margin: 20px 0;
                }
                .footer {
                    text-align: center;
                    padding-top: 20px;
                    border-top: 2px solid #f0f0f0;
                    color: #999;
                    font-size: 12px;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <h1><?php echo get_bloginfo('name'); ?></h1>
                </div>
                
                <div class="email-content">
                    <?php if ($type === 'earned'): ?>
                        <h2><?php _e('Congratulations! You Earned Cashback!', 'woo-cashback-system'); ?></h2>
                        
                        <p><?php printf(__('Hi %s,', 'woo-cashback-system'), esc_html($args['user_name'])); ?></p>
                        
                        <p><?php _e('Great news! You\'ve earned cashback on your recent purchase.', 'woo-cashback-system'); ?></p>
                        
                        <div class="highlight">
                            <div class="highlight-amount"><?php echo $args['cashback_amount']; ?></div>
                            <div><?php _e('Cashback Earned', 'woo-cashback-system'); ?></div>
                        </div>
                        
                        <div class="info-box">
                            <p><strong><?php _e('Order Details:', 'woo-cashback-system'); ?></strong></p>
                            <p><?php printf(__('Order Number: #%s', 'woo-cashback-system'), $args['order_id']); ?></p>
                            <p><?php printf(__('Order Total: %s', 'woo-cashback-system'), $args['order_total']); ?></p>
                            <p><?php printf(__('Your New Balance: %s', 'woo-cashback-system'), $args['new_balance']); ?></p>
                        </div>
                        
                        <p><?php _e('You can use this cashback on your next purchase at checkout!', 'woo-cashback-system'); ?></p>
                        
                        <center>
                            <a href="<?php echo esc_url($args['cashback_url']); ?>" class="button">
                                <?php _e('View My Cashback', 'woo-cashback-system'); ?>
                            </a>
                        </center>
                        
                    <?php elseif ($type === 'used'): ?>
                        <h2><?php _e('Cashback Used Successfully!', 'woo-cashback-system'); ?></h2>
                        
                        <p><?php printf(__('Hi %s,', 'woo-cashback-system'), esc_html($args['user_name'])); ?></p>
                        
                        <p><?php _e('You\'ve successfully used your cashback on your recent order.', 'woo-cashback-system'); ?></p>
                        
                        <div class="highlight">
                            <div class="highlight-amount"><?php echo $args['cashback_used']; ?></div>
                            <div><?php _e('Cashback Used', 'woo-cashback-system'); ?></div>
                        </div>
                        
                        <div class="info-box">
                            <p><strong><?php _e('Order Details:', 'woo-cashback-system'); ?></strong></p>
                            <p><?php printf(__('Order Number: #%s', 'woo-cashback-system'), $args['order_id']); ?></p>
                            <p><?php printf(__('Final Order Total: %s', 'woo-cashback-system'), $args['order_total']); ?></p>
                            <p><?php printf(__('Remaining Balance: %s', 'woo-cashback-system'), $args['remaining_balance']); ?></p>
                        </div>
                        
                        <p><?php _e('Keep shopping to earn more cashback!', 'woo-cashback-system'); ?></p>
                        
                        <center>
                            <a href="<?php echo esc_url($args['cashback_url']); ?>" class="button">
                                <?php _e('View My Cashback', 'woo-cashback-system'); ?>
                            </a>
                        </center>
                    <?php endif; ?>
                </div>
                
                <div class="footer">
                    <p><?php printf(__('This email was sent from %s', 'woo-cashback-system'), get_bloginfo('name')); ?></p>
                    <p><?php echo get_bloginfo('url'); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Send admin notification for unusual activity
     */
    public static function notify_admin_unusual_activity($user_id, $activity_type, $details) {
        $settings = get_option('wcs_cashback_settings');
        
        if (!isset($settings['enable_notifications']) || $settings['enable_notifications'] !== 'yes') {
            return;
        }
        
        $admin_email = get_option('admin_email');
        $user = get_userdata($user_id);
        
        $subject = sprintf(__('[%s] Unusual Cashback Activity Detected', 'woo-cashback-system'), get_bloginfo('name'));
        
        $message = sprintf(
            __('Unusual activity detected in the cashback system.

User: %s (ID: %d)
Activity Type: %s
Details: %s
Time: %s

Please review this activity in the admin panel.

%s', 'woo-cashback-system'),
            $user ? $user->display_name : 'Unknown',
            $user_id,
            $activity_type,
            $details,
            current_time('mysql'),
            admin_url('admin.php?page=wcs-cashback-users')
        );
        
        wp_mail($admin_email, $subject, $message);
    }
}
