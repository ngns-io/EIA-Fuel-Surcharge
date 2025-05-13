<?php
/**
 * Handles logging for the plugin.
 *
 * @package    EIAFuelSurcharge
 * @subpackage EIAFuelSurcharge\Utilities
 */

namespace EIAFuelSurcharge\Utilities;

class Logger {

    /**
     * Log an API request for debugging purposes.
     *
     * @since    1.0.0
     * @param    string    $request_url    The API request URL.
     */
    public function log_api_request($request_url) {
        global $wpdb;
        
        // Remove API key from URL for security
        $sanitized_url = preg_replace('/api_key=[^&]*/', 'api_key=REDACTED', $request_url);
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_logs';
        $wpdb->insert(
            $table_name,
            [
                'log_type'   => 'api_request',
                'message'    => 'API Request: ' . $sanitized_url,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
    }

    /**
     * Log an API error.
     *
     * @since    1.0.0
     * @param    string    $error_message    The error message.
     */
    public function log_api_error($error_message) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_logs';
        $wpdb->insert(
            $table_name,
            [
                'log_type'   => 'api_error',
                'message'    => 'API Error: ' . $error_message,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
    }

    /**
     * Log an API success.
     *
     * @since    1.0.0
     */
    public function log_api_success() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_logs';
        $wpdb->insert(
            $table_name,
            [
                'log_type'   => 'api_success',
                'message'    => 'API Request Successful',
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
    }

    /**
     * Log a database error.
     *
     * @since    1.0.0
     * @param    string    $error_message    The error message.
     */
    public function log_db_error($error_message) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_logs';
        $wpdb->insert(
            $table_name,
            [
                'log_type'   => 'db_error',
                'message'    => 'Database Error: ' . $error_message,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
    }

    /**
     * Log a scheduled event.
     *
     * @since    1.0.0
     * @param    int       $next_run     The timestamp for the next run.
     * @param    string    $recurrence   The recurrence.
     */
    public function log_scheduled_event($next_run, $recurrence) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_logs';
        $wpdb->insert(
            $table_name,
            [
                'log_type'   => 'schedule',
                'message'    => sprintf(
                    __('Scheduled update: %s (%s)', 'eia-fuel-surcharge'),
                    date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_run),
                    $recurrence
                ),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
    }

    /**
     * Log update start.
     *
     * @since    1.0.0
     */
    public function log_update_start() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_logs';
        $wpdb->insert(
            $table_name,
            [
                'log_type'   => 'update_start',
                'message'    => __('Starting scheduled update', 'eia-fuel-surcharge'),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
    }

    /**
     * Log update success.
     *
     * @since    1.0.0
     */
    public function log_update_success() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_logs';
        $wpdb->insert(
            $table_name,
            [
                'log_type'   => 'update_success',
                'message'    => __('Scheduled update completed successfully', 'eia-fuel-surcharge'),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
    }

    /**
     * Log update error.
     *
     * @since    1.0.0
     * @param    string    $error_message    The error message.
     */
    public function log_update_error($error_message) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_logs';
        $wpdb->insert(
            $table_name,
            [
                'log_type'   => 'update_error',
                'message'    => __('Update error: ', 'eia-fuel-surcharge') . $error_message,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
    }

    /**
     * Get logs.
     *
     * @since    1.0.0
     * @param    int     $limit     The number of logs to retrieve.
     * @param    int     $offset    The offset.
     * @return   array   The logs.
     */
    public function get_logs($limit = 50, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_logs';
        
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $limit,
                $offset
            ),
            ARRAY_A
        );
    }

    /**
     * Count logs.
     *
     * @since    1.0.0
     * @return   int   The number of logs.
     */
    public function count_logs() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_logs';
        
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    }
}