<?php
/**
 * Uninstall Script
 * Fired when the plugin is uninstalled
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete plugin options
delete_option('wcs_cashback_settings');

// Delete user meta data
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'wcs_%'");

// Optionally delete database tables
// Uncomment the following lines if you want to remove all data when uninstalling
// WARNING: This will permanently delete all cashback data!

/*
$table_balances = $wpdb->prefix . 'wcs_cashback_balances';
$table_transactions = $wpdb->prefix . 'wcs_cashback_transactions';

$wpdb->query("DROP TABLE IF EXISTS $table_transactions");
$wpdb->query("DROP TABLE IF EXISTS $table_balances");
*/

// Clear any cached data
wp_cache_flush();
