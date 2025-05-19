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
        add_action('admin_post_eia_fuel_surcharge_clear_data', [$this, 'handle_clear_data']);
        add_action('admin_post_eia_fuel_surcharge_clear_logs', [$this, 'handle_clear_logs']);
        add_action('admin_post_eia_fuel_surcharge_export_data', [$this, 'handle_export_data']);
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
                'api_test_success'     => __('API connection successful!', 'eia-fuel-surcharge'),
                'api_test_error'       => __('API connection failed', 'eia-fuel-surcharge'),
                'ajax_error'           => __('Error processing request. Please try again.', 'eia-fuel-surcharge'),
                'test_api_key'         => __('Test API Key', 'eia-fuel-surcharge'),
                'confirm_clear_data'   => __('Are you sure you want to clear all fuel surcharge data? This cannot be undone.', 'eia-fuel-surcharge'),
                'confirm_clear_logs'   => __('Are you sure you want to clear all logs? This cannot be undone.', 'eia-fuel-surcharge'),
            ],
        ]);
    }

    /**
     * Add plugin admin menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        // Main menu item that points to the settings page
        add_menu_page(
            __('EIA Fuel Surcharge', 'eia-fuel-surcharge'),
            __('Fuel Surcharge', 'eia-fuel-surcharge'),
            'manage_options',
            $this->plugin_name . '-settings', // Change this to point to settings
            [$this, 'display_plugin_settings_page'], // Use the settings page callback
            'dashicons-chart-line',
            100
        );
        
        // Add settings as the first submenu to override the duplicate
        add_submenu_page(
            $this->plugin_name . '-settings',
            __('Settings', 'eia-fuel-surcharge'),
            __('Settings', 'eia-fuel-surcharge'),
            'manage_options',
            $this->plugin_name . '-settings',
            [$this, 'display_plugin_settings_page']
        );
        
        // Data submenu
        add_submenu_page(
            $this->plugin_name . '-settings', // Change parent menu
            __('Data & History', 'eia-fuel-surcharge'),
            __('Data & History', 'eia-fuel-surcharge'),
            'manage_options',
            $this->plugin_name . '-data',
            [$this, 'display_plugin_data_page']
        );
        
        // Logs submenu
        add_submenu_page(
            $this->plugin_name . '-settings', // Change parent menu
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
        $settings_link = [
            '<a href="' . admin_url('admin.php?page=' . $this->plugin_name . '-settings') . '">' . __('Settings', 'eia-fuel-surcharge') . '</a>',
        ];
        return array_merge($settings_link, $links);
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
        if ($result === true || (is_array($result) && isset($result['success']) && $result['success'])) {
            wp_send_json_success([
                'message' => __('Fuel surcharge data updated successfully.', 'eia-fuel-surcharge')
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
}