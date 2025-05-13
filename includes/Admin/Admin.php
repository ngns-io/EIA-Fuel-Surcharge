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
        
        // Set up admin post handlers
        add_action('admin_post_eia_fuel_surcharge_manual_update', [$this, 'handle_manual_update']);
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
        // Main menu item
        add_menu_page(
            __('EIA Fuel Surcharge', 'eia-fuel-surcharge'),
            __('Fuel Surcharge', 'eia-fuel-surcharge'),
            'manage_options',
            $this->plugin_name,
            [$this, 'display_plugin_setup_page'],
            'dashicons-chart-line',
            100
        );
        
        // Settings submenu
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
        $settings_link = [
            '<a href="' . admin_url('admin.php?page=' . $this->plugin_name) . '">' . __('Settings', 'eia-fuel-surcharge') . '</a>',
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
            wp_send_json_error(['message' => __('Security check failed', 'eia-fuel-surcharge')]);
        }
        
        // Check for API key
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($api_key)) {
            wp_send_json_error(['message' => __('API key is required', 'eia-fuel-surcharge')]);
        }
        
        // Test the API connection
        $api_handler = new \EIAFuelSurcharge\API\EIAHandler();
        $test_result = $api_handler->test_api_connection($api_key);
        
        if (is_wp_error($test_result)) {
            wp_send_json_error(['message' => $test_result->get_error_message()]);
        } else {
            wp_send_json_success(['message' => __('API connection successful', 'eia-fuel-surcharge')]);
        }
    }

    /**
     * Handle manual update request.
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
        
        // Run the update
        $scheduler = new Scheduler();
        $scheduler->run_scheduled_update();
        
        // Redirect back to the admin page
        wp_redirect(add_query_arg('update', 'success', wp_get_referer()));
        exit;
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