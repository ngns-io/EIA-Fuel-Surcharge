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
     * Maximum number of logs to keep.
     *
     * @since    2.0.0
     * @access   private
     * @var      int    $max_logs    Maximum number of logs to keep.
     */
    private $max_logs = 1000;

    /**
     * Log levels for filtering.
     *
     * @since    2.0.0
     * @access   private
     * @var      array    $log_levels    Log levels.
     */
    private $log_levels = [
        'debug'   => 0,
        'info'    => 1,
        'notice'  => 2,
        'warning' => 3,
        'error'   => 4,
        'critical' => 5
    ];

    /**
     * Constructor.
     *
     * @since   2.0.0
     */
    public function __construct() {
        // Get settings
        $options = get_option('eia_fuel_surcharge_settings');
        
        // Set max logs from settings if available
        if (isset($options['max_logs']) && intval($options['max_logs']) > 0) {
            $this->max_logs = intval($options['max_logs']);
        }
    }

    /**
     * Log a message with a specific type.
     *
     * @since    2.0.0
     * @param    string    $log_type         The log type.
     * @param    string    $message          The message to log.
     * @param    array     $additional_data  Optional additional data to log.
     * @return   bool      Whether the log was added successfully.
     */
    public function log($log_type, $message, $additional_data = []) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_logs';
        
        // Add additional data as JSON if provided
        $full_message = $message;
        if (!empty($additional_data)) {
            $json_data = json_encode($additional_data, JSON_PRETTY_PRINT);
            $full_message .= ' - ' . $json_data;
        }
        
        // Insert the log
        $result = $wpdb->insert(
            $table_name,
            [
                'log_type'   => $log_type,
                'message'    => $full_message,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
        
        // Check if we need to prune logs
        $this->prune_logs();
        
        return $result !== false;
    }

    /**
     * Log an API request for debugging purposes.
     *
     * @since    1.0.0
     * @param    string    $request_url    The API request URL.
     */
    public function log_api_request($request_url) {
        // Remove API key from URL for security
        $sanitized_url = preg_replace('/api_key=[^&]*/', 'api_key=REDACTED', $request_url);
        
        $this->log('api_request', 'API Request: ' . $sanitized_url);
    }

    /**
     * Log an API error.
     *
     * @since    1.0.0
     * @param    string    $error_message    The error message.
     * @param    array     $context          Optional context information.
     */
    public function log_api_error($error_message, $context = []) {
        $this->log('api_error', 'API Error: ' . $error_message, $context);
    }

    /**
     * Log an API success.
     *
     * @since    1.0.0
     * @param    array     $context    Optional context information.
     */
    public function log_api_success($context = []) {
        $this->log('api_success', 'API Request Successful', $context);
    }

    /**
     * Log a database error.
     *
     * @since    1.0.0
     * @param    string    $error_message    The error message.
     * @param    array     $context          Optional context information.
     */
    public function log_db_error($error_message, $context = []) {
        $this->log('db_error', 'Database Error: ' . $error_message, $context);
    }

    /**
     * Log a scheduled event.
     *
     * @since    1.0.0
     * @param    int       $next_run     The timestamp for the next run.
     * @param    string    $recurrence   The recurrence.
     */
    public function log_scheduled_event($next_run, $recurrence) {
        $this->log('schedule', sprintf(
            __('Scheduled update: %s (%s)', 'eia-fuel-surcharge'),
            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_run),
            $recurrence
        ));
    }

    /**
     * Log update start.
     *
     * @since    1.0.0
     */
    public function log_update_start() {
        $this->log('update_start', __('Starting scheduled update', 'eia-fuel-surcharge'));
    }

    /**
     * Log update success.
     *
     * @since    1.0.0
     * @param    array    $stats    Optional stats about the update.
     */
    public function log_update_success($stats = []) {
        $message = __('Scheduled update completed successfully', 'eia-fuel-surcharge');
        $this->log('update_success', $message, $stats);
    }

    /**
     * Log update error.
     *
     * @since    1.0.0
     * @param    string    $error_message    The error message.
     * @param    array     $context          Optional context information.
     */
    public function log_update_error($error_message, $context = []) {
        $this->log('update_error', __('Update error: ', 'eia-fuel-surcharge') . $error_message, $context);
    }

    /**
     * Get logs with advanced filtering and sorting.
     *
     * @since    2.0.0
     * @param    array    $args    Query arguments.
     * @return   array    The logs.
     */
    public function get_logs($args = []) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_logs';
        
        // Default arguments
        $defaults = [
            'limit'     => 50,
            'offset'    => 0,
            'order'     => 'DESC',
            'orderby'   => 'created_at',
            'log_type'  => '',
            'search'    => '',
            'date_from' => '',
            'date_to'   => '',
            'level'     => ''
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Start building the query
        $query = "SELECT * FROM $table_name WHERE 1=1";
        $query_args = [];
        
        // Filter by log type
        if (!empty($args['log_type'])) {
            $log_types = explode(',', $args['log_type']);
            if (count($log_types) === 1) {
                $query .= " AND log_type = %s";
                $query_args[] = $log_types[0];
            } else {
                $placeholders = array_fill(0, count($log_types), '%s');
                $placeholders_str = implode(', ', $placeholders);
                $query .= " AND log_type IN ($placeholders_str)";
                $query_args = array_merge($query_args, $log_types);
            }
        }
        
        // Filter by log level
        if (!empty($args['level']) && isset($this->log_levels[$args['level']])) {
            $level_value = $this->log_levels[$args['level']];
            $error_types = [];
            
            // Add all log types that match or exceed this level
            foreach ($this->log_levels as $type => $level) {
                if ($level >= $level_value) {
                    $error_types[] = $type;
                }
            }
            
            if (!empty($error_types)) {
                $placeholders = array_fill(0, count($error_types), '%s');
                $placeholders_str = implode(', ', $placeholders);
                $query .= " AND level IN ($placeholders_str)";
                $query_args = array_merge($query_args, $error_types);
            }
        }
        
        // Search in message
        if (!empty($args['search'])) {
            $query .= " AND message LIKE %s";
            $query_args[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }
        
        // Date range filter
        if (!empty($args['date_from'])) {
            $query .= " AND created_at >= %s";
            $query_args[] = $args['date_from'] . ' 00:00:00';
        }
        
        if (!empty($args['date_to'])) {
            $query .= " AND created_at <= %s";
            $query_args[] = $args['date_to'] . ' 23:59:59';
        }
        
        // Order and limit
        $valid_order = in_array(strtoupper($args['order']), ['ASC', 'DESC']) ? strtoupper($args['order']) : 'DESC';
        $valid_orderby = in_array($args['orderby'], ['id', 'log_type', 'created_at']) ? $args['orderby'] : 'created_at';
        
        $query .= " ORDER BY $valid_orderby $valid_order";
        
        if ($args['limit'] > 0) {
            $query .= " LIMIT %d OFFSET %d";
            $query_args[] = $args['limit'];
            $query_args[] = $args['offset'];
        }
        
        // Prepare the query if we have arguments
        if (!empty($query_args)) {
            $query = $wpdb->prepare($query, $query_args);
        }
        
        // Execute the query
        $logs = $wpdb->get_results($query, ARRAY_A);
        
        return $logs;
    }

    /**
     * Count logs with filtering.
     *
     * @since    2.0.0
     * @param    array    $args    Query arguments.
     * @return   int      The number of logs.
     */
    public function count_logs($args = []) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_logs';
        
        // Default arguments
        $defaults = [
            'log_type'  => '',
            'search'    => '',
            'date_from' => '',
            'date_to'   => '',
            'level'     => ''
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Start building the query
        $query = "SELECT COUNT(*) FROM $table_name WHERE 1=1";
        $query_args = [];
        
        // Filter by log type
        if (!empty($args['log_type'])) {
            $log_types = explode(',', $args['log_type']);
            if (count($log_types) === 1) {
                $query .= " AND log_type = %s";
                $query_args[] = $log_types[0];
            } else {
                $placeholders = array_fill(0, count($log_types), '%s');
                $placeholders_str = implode(', ', $placeholders);
                $query .= " AND log_type IN ($placeholders_str)";
                $query_args = array_merge($query_args, $log_types);
            }
        }
        
        // Filter by log level
        if (!empty($args['level']) && isset($this->log_levels[$args['level']])) {
            $level_value = $this->log_levels[$args['level']];
            $error_types = [];
            
            // Add all log types that match or exceed this level
            foreach ($this->log_levels as $type => $level) {
                if ($level >= $level_value) {
                    $error_types[] = $type;
                }
            }
            
            if (!empty($error_types)) {
                $placeholders = array_fill(0, count($error_types), '%s');
                $placeholders_str = implode(', ', $placeholders);
                $query .= " AND level IN ($placeholders_str)";
                $query_args = array_merge($query_args, $error_types);
            }
        }
        
        // Search in message
        if (!empty($args['search'])) {
            $query .= " AND message LIKE %s";
            $query_args[] = '%' . $wpdb->esc_like($args['search']) . '%';
        }
        
        // Date range filter
        if (!empty($args['date_from'])) {
            $query .= " AND created_at >= %s";
            $query_args[] = $args['date_from'] . ' 00:00:00';
        }
        
        if (!empty($args['date_to'])) {
            $query .= " AND created_at <= %s";
            $query_args[] = $args['date_to'] . ' 23:59:59';
        }
        
        // Prepare the query if we have arguments
        if (!empty($query_args)) {
            $query = $wpdb->prepare($query, $query_args);
        }
        
        // Execute the query
        return (int) $wpdb->get_var($query);
    }

    /**
     * Clear all logs.
     *
     * @since    2.0.0
     * @return   bool    True on success, false on failure.
     */
    public function clear_logs() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_logs';
        
        // Truncate the table
        $result = $wpdb->query("TRUNCATE TABLE $table_name");
        
        // Log this action
        if ($result !== false) {
            $wpdb->insert(
                $table_name,
                [
                    'log_type'   => 'logs_clear',
                    'message'    => __('All logs cleared', 'eia-fuel-surcharge'),
                    'created_at' => current_time('mysql')
                ],
                ['%s', '%s', '%s']
            );
            return true;
        }
        
        return false;
    }

    /**
     * Export logs to CSV.
     *
     * @since    2.0.0
     * @param    array    $args    Query arguments for filtering logs.
     * @return   string   CSV content.
     */
    public function export_logs_csv($args = []) {
        // Get logs with filtering
        $logs = $this->get_logs(array_merge($args, ['limit' => 0]));
        
        if (empty($logs)) {
            return '';
        }
        
        // Open output buffer
        ob_start();
        
        // Create CSV writer
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fputs($output, "\xEF\xBB\xBF");
        
        // Add headers
        fputcsv($output, ['ID', 'Log Type', 'Message', 'Created At']);
        
        // Add log data
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['log_type'],
                $log['message'],
                $log['created_at']
            ]);
        }
        
        // Close the file handle
        fclose($output);
        
        // Get the contents
        $csv = ob_get_clean();
        
        return $csv;
    }

    /**
     * Prune old logs to keep the table size manageable.
     *
     * @since    2.0.0
     */
    private function prune_logs() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_logs';
        
        // Count logs
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // If we have more logs than the max, delete the oldest ones
        if ($count > $this->max_logs) {
            $to_delete = $count - $this->max_logs;
            
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $table_name ORDER BY created_at ASC LIMIT %d",
                $to_delete
            ));
        }
    }

    /**
     * Get log type counts for statistics.
     *
     * @since    2.0.0
     * @return   array    Log type counts.
     */
    public function get_log_type_counts() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_logs';
        
        $results = $wpdb->get_results(
            "SELECT log_type, COUNT(*) as count 
            FROM $table_name 
            GROUP BY log_type
            ORDER BY count DESC",
            ARRAY_A
        );
        
        $counts = [];
        
        if (!empty($results)) {
            foreach ($results as $row) {
                $counts[$row['log_type']] = (int) $row['count'];
            }
        }
        
        return $counts;
    }

    /**
     * Get error logs for the last 30 days.
     *
     * @since    2.0.0
     * @return   array    Error counts by day.
     */
    public function get_error_logs_by_day() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_logs';
        
        $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as log_date, COUNT(*) as count 
            FROM $table_name 
            WHERE log_type IN ('api_error', 'update_error', 'db_error')
            AND created_at >= %s
            GROUP BY log_date
            ORDER BY log_date ASC",
            $thirty_days_ago
        ), ARRAY_A);
        
        // Fill in missing dates with zero counts
        $counts = [];
        $current_date = new \DateTime($thirty_days_ago);
        $end_date = new \DateTime();
        
        while ($current_date <= $end_date) {
            $date_string = $current_date->format('Y-m-d');
            $counts[$date_string] = 0;
            $current_date->modify('+1 day');
        }
        
        // Add actual counts
        if (!empty($results)) {
            foreach ($results as $row) {
                $counts[$row['log_date']] = (int) $row['count'];
            }
        }
        
        return $counts;
    }
}