<?php
/**
 * Fired during plugin activation and deactivation.
 *
 * @package    EIAFuelSurcharge
 * @subpackage EIAFuelSurcharge\Core
 */

namespace EIAFuelSurcharge\Core;

class Activator {

    /**
     * Create necessary database tables and default settings during activation.
     *
     * @since    1.0.0
     */
    public static function activate() {
        self::create_tables();
        self::add_default_options();
        self::schedule_events();
        
        // Set a transient to redirect to the settings page after activation
        set_transient('eia_fuel_surcharge_activation_redirect', true, 30);
    }
    
    /**
     * Clear scheduled events on deactivation.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('eia_fuel_surcharge_update_event');
        
        // We're keeping database tables and options intact in case the plugin is reactivated
    }
    
    /**
     * Create the necessary database tables.
     *
     * @since    1.0.0
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create fuel surcharge data table
        $table_name = $wpdb->prefix . 'fuel_surcharge_data';
        
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            price_date DATE NOT NULL,
            diesel_price DECIMAL(10,3) NOT NULL,
            surcharge_rate DECIMAL(10,2) NOT NULL,
            region VARCHAR(50) DEFAULT 'national',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY price_date (price_date),
            KEY region (region)
        ) $charset_collate;";
        
        // Create log table
        $log_table_name = $wpdb->prefix . 'fuel_surcharge_logs';
        
        $log_sql = "CREATE TABLE $log_table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            log_type VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY log_type (log_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($log_sql);
    }
    
    /**
     * Add default plugin options.
     *
     * @since    1.0.0
     */
    private static function add_default_options() {
        // Only add options if they don't exist already
        if (false === get_option('eia_fuel_surcharge_settings')) {
            $default_options = [
                'api_key'           => '',
                'base_threshold'    => 1.20,
                'increment_amount'  => 0.06,
                'percentage_rate'   => 0.5,
                'update_frequency'  => 'weekly',
                'update_day'        => 'tuesday', // Default to Tuesday as EIA updates on Tuesday
                'update_time'       => '12:00',
                'region'            => 'national',
                'date_format'       => 'm/d/Y',
                'decimal_places'    => 2,
                'text_format'       => 'Currently as of {date} the fuel surcharge is {rate}%',
                'table_rows'        => 10
            ];
            
            add_option('eia_fuel_surcharge_settings', $default_options);
        }
    }
    
    /**
     * Set up initial scheduled events.
     *
     * @since    1.0.0
     */
    private static function schedule_events() {
        // Clear any existing schedules for this event
        wp_clear_scheduled_hook('eia_fuel_surcharge_update_event');
        
        // Set up the weekly schedule by default
        if (!wp_next_scheduled('eia_fuel_surcharge_update_event')) {
            // Schedule the first event for the next Tuesday at noon
            $start_time = strtotime('next tuesday 12:00');
            wp_schedule_event($start_time, 'weekly', 'eia_fuel_surcharge_update_event');
        }
    }
}