<?php
/**
 * Cashback Calculator Class
 * Calculates cashback percentage based on tier settings
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class WCS_Cashback_Calculator {

    /**
     * Get cashback percentage for a given order subtotal
     *
     * @param float $subtotal Order subtotal
     * @return float Cashback percentage
     */
    public static function get_percentage($subtotal) {
        $subtotal = floatval($subtotal);

        // Get tier settings from WooCommerce options
        $tier_3_threshold = floatval(get_option('wcs_tier_3_threshold', 1500));
        $tier_3_percentage = floatval(get_option('wcs_tier_3_percentage', 7));
        $tier_2_threshold = floatval(get_option('wcs_tier_2_threshold', 1000));
        $tier_2_percentage = floatval(get_option('wcs_tier_2_percentage', 5));
        $tier_1_threshold = floatval(get_option('wcs_tier_1_threshold', 500));
        $tier_1_percentage = floatval(get_option('wcs_tier_1_percentage', 3));

        // Check from highest tier to lowest
        if ($subtotal >= $tier_3_threshold && $tier_3_percentage > 0) {
            return $tier_3_percentage;
        }
        if ($subtotal >= $tier_2_threshold && $tier_2_percentage > 0) {
            return $tier_2_percentage;
        }
        if ($subtotal >= $tier_1_threshold && $tier_1_percentage > 0) {
            return $tier_1_percentage;
        }

        return 0;
    }

    /**
     * Calculate cashback amount for a given subtotal
     *
     * @param float $subtotal Order subtotal
     * @return float Cashback amount
     */
    public static function calculate($subtotal) {
        $percentage = self::get_percentage($subtotal);
        if ($percentage <= 0) {
            return 0;
        }
        return round($subtotal * ($percentage / 100), 2);
    }

    /**
     * Get usage limit percentage from settings
     *
     * @return float
     */
    public static function get_usage_limit_percentage() {
        return floatval(get_option('wcs_usage_limit_percentage', 50));
    }

    /**
     * Get max cashback limit from settings
     *
     * @return float
     */
    public static function get_max_cashback_limit() {
        return floatval(get_option('wcs_max_cashback_limit', 10000));
    }

    /**
     * Get all tiers info for display
     *
     * @return array
     */
    public static function get_tiers_info() {
        $tiers = array();

        $t1_thresh = floatval(get_option('wcs_tier_1_threshold', 500));
        $t1_pct = floatval(get_option('wcs_tier_1_percentage', 3));
        if ($t1_pct > 0) {
            $tiers[] = array('threshold' => $t1_thresh, 'percentage' => $t1_pct);
        }

        $t2_thresh = floatval(get_option('wcs_tier_2_threshold', 1000));
        $t2_pct = floatval(get_option('wcs_tier_2_percentage', 5));
        if ($t2_pct > 0) {
            $tiers[] = array('threshold' => $t2_thresh, 'percentage' => $t2_pct);
        }

        $t3_thresh = floatval(get_option('wcs_tier_3_threshold', 1500));
        $t3_pct = floatval(get_option('wcs_tier_3_percentage', 7));
        if ($t3_pct > 0) {
            $tiers[] = array('threshold' => $t3_thresh, 'percentage' => $t3_pct);
        }

        return $tiers;
    }
}
