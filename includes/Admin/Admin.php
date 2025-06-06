<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    EIAFuelSurcharge
 * @subpackage EIAFuelSurcharge\Admin
 */

namespace EIAFuelSurcharge\Admin;

use EIAFuelSurcharge\Core\Scheduler;
use EIAFuelSurcharge\Utilities\Calculator;

class Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name    The name of this plugin.
     * @param    string    $version        The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Set up admin AJAX handlers
        add_action('wp_ajax_eia_fuel_surcharge_test_api', [$this, 'ajax_test_api']);
        add_action('wp_ajax_eia_fuel_surcharge_manual_update_ajax', [$this, 'ajax_manual_update']);
        
        // Set up admin post handlers
        add_action('admin_post_eia_fuel_surcharge_manual_update', [$this, 'handle_manual_update']);
        add_action('admin_post_eia_fuel_surcharge_clear_data', [$this, 'handle_clear_data']);
        add_action('admin_post_eia_fuel_surcharge_clear_logs', [$this, 'handle_clear_logs']);
        add_action('admin_post_eia_fuel_surcharge_export_data', [$this, 'handle_export_data']);
        
        // Add dashboard widget
        add_action('wp_dashboard_setup', [$this, 'register_dashboard_widgets']);

        // Hook into settings updates to trigger schedule updates
        add_action('update_option_eia_fuel_surcharge_settings', [$this, 'handle_settings_update'], 10, 3);
        
        // Add admin notices for schedule updates
        add_action('admin_notices', [$this, 'show_schedule_update_notices']);

        add_action('wp_ajax_eia_fuel_surcharge_force_reschedule', [$this, 'ajax_force_reschedule']);
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, EIA_FUEL_SURCHARGE_PLUGIN_URL . 'assets/css/admin.css', [], $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, EIA_FUEL_SURCHARGE_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], $this->version, false);
        
        // Add localized script data
        wp_localize_script($this->plugin_name, 'eia_fuel_surcharge_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('eia_fuel_surcharge_nonce'),
            'manual_update_nonce' => wp_create_nonce('eia_fuel_surcharge_manual_update_ajax'),
            'i18n'     => [
                'enter_api_key'        => __('Please enter an API key to test.', 'eia-fuel-surcharge'),
                'testing'              => __('Testing...', 'eia-fuel-surcharge'),
                'updating'             => __('Updating...', 'eia-fuel-surcharge'),
                'update_success'       => __('Update Successful!', 'eia-fuel-surcharge'),
                'update_failed'        => __('Update Failed', 'eia-fuel-surcharge'),
                'api_test_success'     => __('API connection successful!', 'eia-fuel-surcharge'),
                'api_test_error'       => __('API connection failed', 'eia-fuel-surcharge'),
                'ajax_error'           => __('Error processing request. Please try again.', 'eia-fuel-surcharge'),
                'test_api_key'         => __('Test API Key', 'eia-fuel-surcharge'),
                'confirm_clear_data'   => __('Are you sure you want to clear all fuel surcharge data? This cannot be undone.', 'eia-fuel-surcharge'),
                'confirm_clear_logs'   => __('Are you sure you want to clear all logs? This cannot be undone.', 'eia-fuel-surcharge'),
                'update_stats'         => __('Update Statistics', 'eia-fuel-surcharge'),
                'records_inserted'     => __('new records inserted', 'eia-fuel-surcharge'),
                'records_updated'      => __('existing records updated', 'eia-fuel-surcharge'),
                'records_skipped'      => __('records unchanged', 'eia-fuel-surcharge'),
                'show_debug'           => __('Show/Hide Debug Information', 'eia-fuel-surcharge'),
            ],
        ]);
    }

    /**
     * Add plugin admin menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        // Main menu item that points to a dashboard page
        add_menu_page(
            __('EIA Fuel Surcharge', 'eia-fuel-surcharge'),
            __('Fuel Surcharge', 'eia-fuel-surcharge'),
            'manage_options',
            $this->plugin_name, // Change this to point to the dashboard
            [$this, 'display_plugin_dashboard_page'], // Use the dashboard page callback
            'dashicons-chart-line',
            100
        );
        
        // Add dashboard as the first submenu to override the duplicate
        add_submenu_page(
            $this->plugin_name,
            __('Dashboard', 'eia-fuel-surcharge'),
            __('Dashboard', 'eia-fuel-surcharge'),
            'manage_options',
            $this->plugin_name,
            [$this, 'display_plugin_dashboard_page']
        );
        
        // Add settings page
        add_submenu_page(
            $this->plugin_name,
            __('Settings', 'eia-fuel-surcharge'),
            __('Settings', 'eia-fuel-surcharge'),
            'manage_options',
            $this->plugin_name . '-settings',
            [$this, 'display_plugin_settings_page']
        );
        
        // Data submenu
        add_submenu_page(
            $this->plugin_name,
            __('Data & History', 'eia-fuel-surcharge'),
            __('Data & History', 'eia-fuel-surcharge'),
            'manage_options',
            $this->plugin_name . '-data',
            [$this, 'display_plugin_data_page']
        );
        
        // Logs submenu
        add_submenu_page(
            $this->plugin_name,
            __('Logs', 'eia-fuel-surcharge'),
            __('Logs', 'eia-fuel-surcharge'),
            'manage_options',
            $this->plugin_name . '-logs',
            [$this, 'display_plugin_logs_page']
        );
    }

    /**
     * Add settings action link to the plugins page.
     *
     * @since    1.0.0
     */
    public function add_action_links($links) {
        $action_links = [
            '<a href="' . admin_url('admin.php?page=' . $this->plugin_name) . '">' . __('Dashboard', 'eia-fuel-surcharge') . '</a>',
            '<a href="' . admin_url('admin.php?page=' . $this->plugin_name . '-settings') . '">' . __('Settings', 'eia-fuel-surcharge') . '</a>',
        ];
        return array_merge($action_links, $links);
    }

    /**
     * Render the dashboard page.
     *
     * @since    1.0.0
     */
    public function display_plugin_dashboard_page() {
        // Get the API handler, scheduler, and calculator
        $api_handler = new \EIAFuelSurcharge\API\EIAHandler();
        $scheduler = new \EIAFuelSurcharge\Core\Scheduler();
        $calculator = new \EIAFuelSurcharge\Utilities\Calculator();
        $logger = new \EIAFuelSurcharge\Utilities\Logger();
        
        // Get API status
        $api_status = $api_handler->get_api_status();
        
        // Get next scheduled update
        $next_update = $scheduler->get_next_scheduled_update();
        
        // Get latest data
        global $wpdb;
        $table_name = $wpdb->prefix . 'fuel_surcharge_data';
        $latest_data = $wpdb->get_row("SELECT * FROM $table_name ORDER BY price_date DESC LIMIT 1", ARRAY_A);
        
        // Get stats
        $total_records = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Get last log entries
        $log_table = $wpdb->prefix . 'fuel_surcharge_logs';
        $recent_logs = $wpdb->get_results(
            "SELECT * FROM $log_table ORDER BY created_at DESC LIMIT 5",
            ARRAY_A
        );
        
        // Render the dashboard
        include_once EIA_FUEL_SURCHARGE_PLUGIN_DIR . 'templates/admin/dashboard-page.php';
    }

    /**
     * Render the main plugin page.
     *
     * @since    1.0.0
     */
    public function display_plugin_setup_page() {
        include_once EIA_FUEL_SURCHARGE_PLUGIN_DIR . 'templates/admin/settings-page.php';
    }
    
    /**
     * Render the settings page.
     *
     * @since    1.0.0
     */
    public function display_plugin_settings_page() {
        include_once EIA_FUEL_SURCHARGE_PLUGIN_DIR . 'templates/admin/settings-page.php';
    }
    
    /**
     * Render the data page.
     *
     * @since    1.0.0
     */
    public function display_plugin_data_page() {
        include_once EIA_FUEL_SURCHARGE_PLUGIN_DIR . 'templates/admin/data-page.php';
    }
    
    /**
     * Render the logs page.
     *
     * @since    1.0.0
     */
    public function display_plugin_logs_page() {
        include_once EIA_FUEL_SURCHARGE_PLUGIN_DIR . 'templates/admin/logs-page.php';
    }
    
    /**
     * Register all settings fields.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Create a settings object to handle all settings rendering and validation
        $settings = new Settings($this->plugin_name, $this->version);
        $settings->register();
    }

    /**
     * Register dashboard widgets.
     *
     * @since    1.0.0
     */
    public function register_dashboard_widgets() {
        // Only add dashboard widget if user has appropriate capabilities
        if (current_user_can('manage_options')) {
            wp_add_dashboard_widget(
                'eia_fuel_surcharge_dashboard_widget',
                __('Fuel Surcharge Overview', 'eia-fuel-surcharge'),
                [$this, 'display_dashboard_widget']
            );
        }
    }

    /**
     * Display the dashboard widget content.
     *
     * @since    1.0.0
     */
    public function display_dashboard_widget() {
        // Get the API handler, scheduler, and calculator
        $api_handler = new \EIAFuelSurcharge\API\EIAHandler();
        $scheduler = new \EIAFuelSurcharge\Core\Scheduler();
        $calculator = new \EIAFuelSurcharge\Utilities\Calculator();
        
        // Get API status
        $api_status = $api_handler->get_api_status();
        
        // Get next scheduled update
        $next_update = $scheduler->get_next_scheduled_update();
        
        // Get latest data
        global $wpdb;
        $table_name = $wpdb->prefix . 'fuel_surcharge_data';
        $latest_data = $wpdb->get_row("SELECT * FROM $table_name ORDER BY price_date DESC LIMIT 1", ARRAY_A);
        
        // Include the dashboard widget template
        include_once EIA_FUEL_SURCHARGE_PLUGIN_DIR . 'templates/admin/dashboard-widget.php';
    }

    /**
     * Handle API key testing via AJAX.
     *
     * @since    1.0.0
     */
    public function ajax_test_api() {
        // Check nonce
        if (!check_ajax_referer('eia_fuel_surcharge_nonce', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Security check failed', 'eia-fuel-surcharge'),
                'html' => '<div class="notice notice-error inline"><p>' . __('Security check failed', 'eia-fuel-surcharge') . '</p></div>'
            ]);
        }
        
        // Check for API key
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($api_key)) {
            wp_send_json_error([
                'message' => __('API key is required', 'eia-fuel-surcharge'),
                'html' => '<div class="notice notice-error inline"><p>' . __('API key is required', 'eia-fuel-surcharge') . '</p></div>'
            ]);
        }
        
        // Test the API connection
        $api_handler = new \EIAFuelSurcharge\API\EIAHandler();
        $test_result = $api_handler->test_api_connection($api_key);
        
        // Generate HTML for the response
        $html = '';
        
        if ($test_result['success']) {
            $html .= '<div class="notice notice-success inline">';
            $html .= '<p><strong>' . __('API connection successful!', 'eia-fuel-surcharge') . '</strong></p>';
            $html .= '<ul>';
            $html .= '<li>' . __('API Version:', 'eia-fuel-surcharge') . ' ' . esc_html($test_result['api_version']) . '</li>';
            $html .= '<li>' . __('Response Time:', 'eia-fuel-surcharge') . ' ' . esc_html($test_result['response_time']) . '</li>';
            $html .= '<li>' . __('Response Code:', 'eia-fuel-surcharge') . ' ' . esc_html($test_result['response_code']) . '</li>';
            $html .= '<li>' . __('Data Available:', 'eia-fuel-surcharge') . ' ' . ($test_result['data_available'] ? __('Yes', 'eia-fuel-surcharge') : __('No', 'eia-fuel-surcharge')) . '</li>';
            $html .= '</ul>';
            $html .= '</div>';
        } else {
            $html .= '<div class="notice notice-error inline">';
            $html .= '<p><strong>' . __('API connection failed', 'eia-fuel-surcharge') . '</strong></p>';
            $html .= '<p>' . esc_html($test_result['message']) . '</p>';
            $html .= '</div>';
        }
        
        // Add detailed debug information (collapsible)
        $html .= '<div class="eia-api-debug-info">';
        $html .= '<p><a href="#" class="eia-toggle-debug-info">' . __('Show/Hide Debug Information', 'eia-fuel-surcharge') . '</a></p>';
        $html .= '<div class="eia-debug-info-content" style="display:none;">';
        
        if (isset($test_result['debug_info']) && !empty($test_result['debug_info'])) {
            $html .= '<h4>' . __('Request Details', 'eia-fuel-surcharge') . '</h4>';
            $html .= '<pre style="background: #f5f5f5; padding: 10px; overflow: auto; max-height: 200px;">';
            // Show URL (with API key redacted)
            if (isset($test_result['debug_info']['request_url'])) {
                $html .= 'URL: ' . esc_html($test_result['debug_info']['request_url']) . "\n";
            }
            if (isset($test_result['debug_info']['request_time'])) {
                $html .= 'Time: ' . esc_html($test_result['debug_info']['request_time']) . "\n";
            }
            $html .= '</pre>';
            
            $html .= '<h4>' . __('Response Details', 'eia-fuel-surcharge') . '</h4>';
            $html .= '<pre style="background: #f5f5f5; padding: 10px; overflow: auto; max-height: 200px;">';
            if (isset($test_result['debug_info']['response_code'])) {
                $html .= 'Code: ' . esc_html($test_result['debug_info']['response_code']) . "\n";
            }
            if (isset($test_result['debug_info']['response_time'])) {
                $html .= 'Time: ' . esc_html($test_result['debug_info']['response_time']) . "\n";
            }
            if (isset($test_result['debug_info']['error'])) {
                $html .= 'Error: ' . esc_html($test_result['debug_info']['error']) . "\n";
            }
            if (isset($test_result['debug_info']['json_parse_error']) && $test_result['debug_info']['json_parse_error']) {
                $html .= 'JSON Error: ' . esc_html($test_result['debug_info']['json_parse_error']) . "\n";
            }
            
            // Show sample of response body
            if (isset($test_result['debug_info']['response_body_sample'])) {
                $html .= "\nResponse Body Sample:\n" . esc_html($test_result['debug_info']['response_body_sample']) . "\n";
            }
            
            $html .= '</pre>';
        }
        
        $html .= '</div>'; // .eia-debug-info-content
        $html .= '</div>'; // .eia-api-debug-info
        
        if ($test_result['success']) {
            wp_send_json_success([
                'message' => __('API connection successful', 'eia-fuel-surcharge'),
                'html' => $html,
                'debug_info' => $test_result['debug_info']
            ]);
        } else {
            wp_send_json_error([
                'message' => $test_result['message'],
                'html' => $html,
                'debug_info' => isset($test_result['debug_info']) ? $test_result['debug_info'] : null
            ]);
        }
    }

    /**
     * Handle manual update request via AJAX.
     *
     * @since    1.0.0
     */
    public function ajax_manual_update() {
        // Check nonce with detailed error reporting
        $nonce_check = check_ajax_referer('eia_fuel_surcharge_manual_update_ajax', 'nonce', false);
        
        if (!$nonce_check) {
            wp_send_json_error([
                'message' => __('Security check failed', 'eia-fuel-surcharge'),
                'debug' => [
                    'received' => isset($_POST['nonce']) ? 'Yes' : 'No',
                    'expected' => 'eia_fuel_surcharge_manual_update_ajax'
                ]
            ]);
            return;
        }
        
        // Check for user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('You do not have sufficient permissions to perform this action', 'eia-fuel-surcharge')
            ]);
            return;
        }
        
        // Run the update with force_refresh = true to bypass cache
        $scheduler = new Scheduler();
        $result = $scheduler->run_scheduled_update(true);
        
        // Send response
        if ($result === true) {
            wp_send_json_success([
                'message' => __('Fuel surcharge data updated successfully.', 'eia-fuel-surcharge')
            ]);
        } elseif (is_array($result) && isset($result['success']) && $result['success']) {
            wp_send_json_success([
                'message' => __('Fuel surcharge data updated successfully.', 'eia-fuel-surcharge'),
                'stats' => isset($result['stats']) ? $result['stats'] : null
            ]);
        } else {
            // Handle the case where result is an array with error info
            $error_message = __('Failed to update fuel surcharge data.', 'eia-fuel-surcharge');
            
            if (is_array($result) && isset($result['message'])) {
                $error_message = $result['message'];
            }
            
            wp_send_json_error([
                'message' => $error_message,
                'debug' => $result
            ]);
        }
    }

    /**
     * Handle manual update request via form submission.
     *
     * @since    1.0.0
     */
    public function handle_manual_update() {
        // Check for nonce
        if (!isset($_POST['eia_fuel_surcharge_nonce']) || !wp_verify_nonce($_POST['eia_fuel_surcharge_nonce'], 'eia_fuel_surcharge_manual_update')) {
            wp_die(__('Security check failed', 'eia-fuel-surcharge'));
        }
        
        // Check for user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action', 'eia-fuel-surcharge'));
        }
        
        // Run the update with force_refresh = true to bypass cache
        $scheduler = new Scheduler();
        $result = $scheduler->run_scheduled_update(true);
        
        // Check the result
        if ($result === true || (is_array($result) && isset($result['success']) && $result['success'])) {
            // Redirect back to the admin page with success message
            wp_redirect(add_query_arg('update', 'success', wp_get_referer()));
            exit;
        } else {
            // Get error message
            $error_message = __('Failed to update fuel surcharge data.', 'eia-fuel-surcharge');
            
            if (is_array($result) && isset($result['message'])) {
                $error_message = $result['message'];
            }
            
            // Redirect back with error message
            wp_redirect(add_query_arg('error', urlencode($error_message), wp_get_referer()));
            exit;
        }
    }

    /**
     * Handle clear data request.
     *
     * @since    1.0.0
     */
    public function handle_clear_data() {
        // Check for nonce
        if (!isset($_POST['eia_fuel_surcharge_nonce']) || !wp_verify_nonce($_POST['eia_fuel_surcharge_nonce'], 'eia_fuel_surcharge_clear_data')) {
            wp_die(__('Security check failed', 'eia-fuel-surcharge'));
        }
        
        // Check for user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action', 'eia-fuel-surcharge'));
        }
        
        // Clear the data
        global $wpdb;
        $table_name = $wpdb->prefix . 'fuel_surcharge_data';
        $wpdb->query("TRUNCATE TABLE $table_name");
        
        // Add a log entry
        $log_table = $wpdb->prefix . 'fuel_surcharge_logs';
        $wpdb->insert(
            $log_table,
            [
                'log_type'   => 'data_clear',
                'message'    => __('All fuel surcharge data cleared by admin', 'eia-fuel-surcharge'),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
        
        // Redirect back to the admin page
        wp_redirect(add_query_arg('cleared', 'true', wp_get_referer()));
        exit;
    }

    /**
     * Handle clear logs request.
     *
     * @since    1.0.0
     */
    public function handle_clear_logs() {
        // Check for nonce
        if (!isset($_POST['eia_fuel_surcharge_nonce']) || !wp_verify_nonce($_POST['eia_fuel_surcharge_nonce'], 'eia_fuel_surcharge_clear_logs')) {
            wp_die(__('Security check failed', 'eia-fuel-surcharge'));
        }
        
        // Check for user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action', 'eia-fuel-surcharge'));
        }
        
        // Clear the logs
        global $wpdb;
        $table_name = $wpdb->prefix . 'fuel_surcharge_logs';
        $wpdb->query("TRUNCATE TABLE $table_name");
        
        // Add a new log entry about the clearing
        $wpdb->insert(
            $table_name,
            [
                'log_type'   => 'logs_clear',
                'message'    => __('All logs cleared by admin', 'eia-fuel-surcharge'),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s']
        );
        
        // Redirect back to the admin page
        wp_redirect(add_query_arg('cleared', 'true', wp_get_referer()));
        exit;
    }

    /**
     * Handle export data request.
     *
     * @since    1.0.0
     */
    public function handle_export_data() {
        // Check for nonce
        if (!isset($_POST['eia_fuel_surcharge_nonce']) || !wp_verify_nonce($_POST['eia_fuel_surcharge_nonce'], 'eia_fuel_surcharge_export_data')) {
            wp_die(__('Security check failed', 'eia-fuel-surcharge'));
        }
        
        // Check for user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action', 'eia-fuel-surcharge'));
        }
        
        // Get the data
        global $wpdb;
        $table_name = $wpdb->prefix . 'fuel_surcharge_data';
        $data = $wpdb->get_results("SELECT * FROM $table_name ORDER BY price_date DESC", ARRAY_A);
        
        if (empty($data)) {
            wp_die(__('No data to export', 'eia-fuel-surcharge'));
        }
        
        // Set headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="fuel-surcharge-data-' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create a file pointer
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, array_keys($data[0]));
        
        // Output each row of the data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        // Close the file pointer
        fclose($output);
        exit;
    }

    /**
     * Handle settings update and trigger schedule update if needed.
     *
     * @since    2.0.0
     * @param    mixed     $old_value    The old option value.
     * @param    mixed     $value        The new option value.
     * @param    string    $option       Option name.
     */
    public function handle_settings_update($old_value, $value, $option) {
        // Only proceed if this is our settings option
        if ($option !== 'eia_fuel_surcharge_settings') {
            return;
        }
        
        // Check if schedule-related settings have changed
        $schedule_fields = ['update_frequency', 'update_day', 'update_day_of_month', 'custom_interval', 'update_time'];
        $schedule_changed = false;
        
        // If old_value is empty (first time save), always update schedule
        if (empty($old_value)) {
            $schedule_changed = true;
        } else {
            // Compare schedule-related fields
            foreach ($schedule_fields as $field) {
                $old_val = isset($old_value[$field]) ? $old_value[$field] : '';
                $new_val = isset($value[$field]) ? $value[$field] : '';
                
                if ($old_val !== $new_val) {
                    $schedule_changed = true;
                    break;
                }
            }
        }
        
        if ($schedule_changed) {
            // Update the schedule
            $scheduler = new Scheduler();
            $scheduler->schedule_update();
            
            // Set a transient to show admin notice
            set_transient('eia_fuel_surcharge_schedule_updated', true, 30);
        }
    }

    /**
     * Show admin notices for schedule updates.
     *
     * @since    2.0.0
     */
    public function show_schedule_update_notices() {
        // Check if we should show the schedule update notice
        if (get_transient('eia_fuel_surcharge_schedule_updated')) {
            delete_transient('eia_fuel_surcharge_schedule_updated');
            
            // Get the next scheduled time
            $scheduler = new Scheduler();
            $next_update = $scheduler->get_next_scheduled_update();
            
            $message = __('Schedule updated successfully!', 'eia-fuel-surcharge');
            
            if ($next_update) {
                $message .= ' ' . sprintf(
                    __('Next update scheduled for: %s', 'eia-fuel-surcharge'),
                    date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_update)
                );
            }
            
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . esc_html($message) . '</p>';
            echo '</div>';
        }
    }

    /**
     * Handle force reschedule request via AJAX.
     *
     * @since    2.0.0
     */
    public function ajax_force_reschedule() {
        // Check nonce
        if (!check_ajax_referer('eia_fuel_surcharge_force_reschedule', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Security check failed', 'eia-fuel-surcharge')
            ]);
        }
        
        // Check for user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('You do not have sufficient permissions to perform this action', 'eia-fuel-surcharge')
            ]);
        }
        
        // Force reschedule
        $scheduler = new Scheduler();
        $result = $scheduler->force_reschedule();
        
        if ($result) {
            $next_update = $scheduler->get_next_scheduled_update();
            $message = __('Schedule updated successfully!', 'eia-fuel-surcharge');
            
            if ($next_update) {
                $message .= ' ' . sprintf(
                    __('Next update scheduled for: %s', 'eia-fuel-surcharge'),
                    date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_update)
                );
            }
            
            wp_send_json_success([
                'message' => $message,
                'next_update' => $next_update
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Failed to reschedule. Please check the logs for more information.', 'eia-fuel-surcharge')
            ]);
        }
    }
}