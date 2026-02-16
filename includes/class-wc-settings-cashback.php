<?php
/**
 * WooCommerce Settings Tab for Cashback
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WC_Settings_Cashback')) :

class WC_Settings_Cashback extends WC_Settings_Page {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->id    = 'cashback';
        $this->label = __('Cashback', 'woo-cashback-system');
        
        parent::__construct();
    }
    
    /**
     * Get sections
     */
    public function get_sections() {
        $sections = array(
            ''           => __('General', 'woo-cashback-system'),
            'tiers'      => __('Cashback Tiers', 'woo-cashback-system'),
            'limits'     => __('Limits', 'woo-cashback-system'),
            'notifications' => __('Notifications', 'woo-cashback-system'),
        );
        
        return apply_filters('woocommerce_get_sections_' . $this->id, $sections);
    }
    
    /**
     * Get settings array
     */
    public function get_settings($current_section = '') {
        
        if ('tiers' === $current_section) {
            $settings = $this->get_tier_settings();
        } elseif ('limits' === $current_section) {
            $settings = $this->get_limit_settings();
        } elseif ('notifications' === $current_section) {
            $settings = $this->get_notification_settings();
        } else {
            $settings = $this->get_general_settings();
        }
        
        return apply_filters('woocommerce_get_settings_' . $this->id, $settings, $current_section);
    }
    
    /**
     * General settings
     */
    protected function get_general_settings() {
        return array(
            array(
                'title' => __('Cashback System', 'woo-cashback-system'),
                'type'  => 'title',
                'desc'  => __('Configure the cashback system for your store.', 'woo-cashback-system'),
                'id'    => 'wcs_general_settings'
            ),
            
            array(
                'title'   => __('Enable Cashback', 'woo-cashback-system'),
                'desc'    => __('Enable the cashback system', 'woo-cashback-system'),
                'id'      => 'wcs_cashback_enabled',
                'default' => 'yes',
                'type'    => 'checkbox',
            ),
            
            array(
                'title'    => __('Currency Symbol', 'woo-cashback-system'),
                'desc'     => __('Currency used for cashback display (uses WooCommerce currency by default)', 'woo-cashback-system'),
                'id'       => 'wcs_cashback_currency',
                'type'     => 'text',
                'default'  => get_woocommerce_currency_symbol(),
                'css'      => 'width: 100px;',
            ),
            
            array(
                'type' => 'sectionend',
                'id'   => 'wcs_general_settings'
            ),
        );
    }
    
    /**
     * Tier settings
     */
    protected function get_tier_settings() {
        return array(
            array(
                'title' => __('Cashback Tiers', 'woo-cashback-system'),
                'type'  => 'title',
                'desc'  => __('Configure cashback percentages based on order amounts.', 'woo-cashback-system'),
                'id'    => 'wcs_tier_settings'
            ),
            
            // Tier 1
            array(
                'title'    => __('Tier 1 - Order Threshold', 'woo-cashback-system'),
                'desc'     => __('Minimum order amount for Tier 1 cashback (UAH)', 'woo-cashback-system'),
                'id'       => 'wcs_tier_1_threshold',
                'type'     => 'number',
                'default'  => '500',
                'css'      => 'width: 150px;',
                'custom_attributes' => array(
                    'min'  => '0',
                    'step' => '0.01',
                ),
            ),
            
            array(
                'title'    => __('Tier 1 - Cashback %', 'woo-cashback-system'),
                'desc'     => __('Cashback percentage for Tier 1', 'woo-cashback-system'),
                'id'       => 'wcs_tier_1_percentage',
                'type'     => 'number',
                'default'  => '3',
                'css'      => 'width: 150px;',
                'custom_attributes' => array(
                    'min'  => '0',
                    'max'  => '100',
                    'step' => '0.01',
                ),
            ),
            
            // Tier 2
            array(
                'title'    => __('Tier 2 - Order Threshold', 'woo-cashback-system'),
                'desc'     => __('Minimum order amount for Tier 2 cashback (UAH)', 'woo-cashback-system'),
                'id'       => 'wcs_tier_2_threshold',
                'type'     => 'number',
                'default'  => '1000',
                'css'      => 'width: 150px;',
                'custom_attributes' => array(
                    'min'  => '0',
                    'step' => '0.01',
                ),
            ),
            
            array(
                'title'    => __('Tier 2 - Cashback %', 'woo-cashback-system'),
                'desc'     => __('Cashback percentage for Tier 2', 'woo-cashback-system'),
                'id'       => 'wcs_tier_2_percentage',
                'type'     => 'number',
                'default'  => '5',
                'css'      => 'width: 150px;',
                'custom_attributes' => array(
                    'min'  => '0',
                    'max'  => '100',
                    'step' => '0.01',
                ),
            ),
            
            // Tier 3
            array(
                'title'    => __('Tier 3 - Order Threshold', 'woo-cashback-system'),
                'desc'     => __('Minimum order amount for Tier 3 cashback (UAH)', 'woo-cashback-system'),
                'id'       => 'wcs_tier_3_threshold',
                'type'     => 'number',
                'default'  => '1500',
                'css'      => 'width: 150px;',
                'custom_attributes' => array(
                    'min'  => '0',
                    'step' => '0.01',
                ),
            ),
            
            array(
                'title'    => __('Tier 3 - Cashback %', 'woo-cashback-system'),
                'desc'     => __('Cashback percentage for Tier 3', 'woo-cashback-system'),
                'id'       => 'wcs_tier_3_percentage',
                'type'     => 'number',
                'default'  => '7',
                'css'      => 'width: 150px;',
                'custom_attributes' => array(
                    'min'  => '0',
                    'max'  => '100',
                    'step' => '0.01',
                ),
            ),
            
            array(
                'type' => 'sectionend',
                'id'   => 'wcs_tier_settings'
            ),
        );
    }
    
    /**
     * Limit settings
     */
    protected function get_limit_settings() {
        return array(
            array(
                'title' => __('Cashback Limits', 'woo-cashback-system'),
                'type'  => 'title',
                'desc'  => __('Configure maximum cashback accumulation and usage limits.', 'woo-cashback-system'),
                'id'    => 'wcs_limit_settings'
            ),
            
            array(
                'title'    => __('Maximum Cashback Limit', 'woo-cashback-system'),
                'desc'     => __('Global maximum cashback a user can accumulate (UAH)', 'woo-cashback-system'),
                'id'       => 'wcs_max_cashback_limit',
                'type'     => 'number',
                'default'  => '10000',
                'css'      => 'width: 150px;',
                'custom_attributes' => array(
                    'min'  => '0',
                    'step' => '0.01',
                ),
            ),
            
            array(
                'title'    => __('Usage Limit Percentage', 'woo-cashback-system'),
                'desc'     => __('Maximum percentage of order total that can be paid with cashback', 'woo-cashback-system'),
                'id'       => 'wcs_usage_limit_percentage',
                'type'     => 'number',
                'default'  => '50',
                'css'      => 'width: 150px;',
                'custom_attributes' => array(
                    'min'  => '0',
                    'max'  => '100',
                    'step' => '1',
                ),
            ),
            
            array(
                'type' => 'sectionend',
                'id'   => 'wcs_limit_settings'
            ),
        );
    }
    
    /**
     * Notification settings
     */
    protected function get_notification_settings() {
        return array(
            array(
                'title' => __('Email Notifications', 'woo-cashback-system'),
                'type'  => 'title',
                'desc'  => __('Configure email notifications for cashback events.', 'woo-cashback-system'),
                'id'    => 'wcs_notification_settings'
            ),
            
            array(
                'title'   => __('Enable Notifications', 'woo-cashback-system'),
                'desc'    => __('Send email notifications to users for cashback events', 'woo-cashback-system'),
                'id'      => 'wcs_enable_notifications',
                'default' => 'yes',
                'type'    => 'checkbox',
            ),
            
            array(
                'title'   => __('Notify on Earn', 'woo-cashback-system'),
                'desc'    => __('Send notification when user earns cashback', 'woo-cashback-system'),
                'id'      => 'wcs_notify_on_earn',
                'default' => 'yes',
                'type'    => 'checkbox',
            ),
            
            array(
                'title'   => __('Notify on Use', 'woo-cashback-system'),
                'desc'    => __('Send notification when user uses cashback', 'woo-cashback-system'),
                'id'      => 'wcs_notify_on_use',
                'default' => 'yes',
                'type'    => 'checkbox',
            ),
            
            array(
                'type' => 'sectionend',
                'id'   => 'wcs_notification_settings'
            ),
        );
    }
    
    /**
     * Save settings
     */
    public function save() {
        $settings = $this->get_settings();
        WC_Admin_Settings::save_fields($settings);
        
        // Sync with old settings format
        $this->sync_settings();
    }
    
    /**
     * Sync with old settings format
     */
    protected function sync_settings() {
        $old_settings = array(
            'enabled' => get_option('wcs_cashback_enabled', 'yes'),
            'tier_1_threshold' => get_option('wcs_tier_1_threshold', 500),
            'tier_1_percentage' => get_option('wcs_tier_1_percentage', 3),
            'tier_2_threshold' => get_option('wcs_tier_2_threshold', 1000),
            'tier_2_percentage' => get_option('wcs_tier_2_percentage', 5),
            'tier_3_threshold' => get_option('wcs_tier_3_threshold', 1500),
            'tier_3_percentage' => get_option('wcs_tier_3_percentage', 7),
            'max_cashback_limit' => get_option('wcs_max_cashback_limit', 10000),
            'usage_limit_percentage' => get_option('wcs_usage_limit_percentage', 50),
            'enable_notifications' => get_option('wcs_enable_notifications', 'yes'),
        );
        
        update_option('wcs_cashback_settings', $old_settings);
    }
}

endif;

return new WC_Settings_Cashback();
