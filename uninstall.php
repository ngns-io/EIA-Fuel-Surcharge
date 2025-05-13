<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package    EIAFuelSurcharge
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
delete_option('eia_fuel_surcharge_settings');

// Delete database tables
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fuel_surcharge_data");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fuel_surcharge_logs");

// Clear scheduled events
wp_clear_scheduled_hook('eia_fuel_surcharge_update_event');