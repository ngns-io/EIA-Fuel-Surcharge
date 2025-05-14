<?php
/**
 * Handles admin page rendering.
 *
 * @package    EIAFuelSurcharge
 * @subpackage EIAFuelSurcharge\Admin
 */

namespace EIAFuelSurcharge\Admin;

use EIAFuelSurcharge\API\EIAHandler;
use EIAFuelSurcharge\Core\Scheduler;
use EIAFuelSurcharge\Utilities\Calculator;
use EIAFuelSurcharge\Utilities\Logger;

class Pages {

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
    }

    /**
     * Render the main dashboard page.
     *
     * @since    1.0.0
     */
    public function render_dashboard_page() {
        // Get the API handler and scheduler
        $api_handler = new EIAHandler();
        $scheduler = new Scheduler();
        $calculator = new Calculator();
        $logger = new Logger();
        
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
     * Render the settings page.
     *
     * @since    1.0.0
     */
    public function render_settings_page() {
        include_once EIA_FUEL_SURCHARGE_PLUGIN_DIR . 'templates/admin/settings-page.php';
    }

    /**
     * Render the data page.
     *
     * @since    1.0.0
     */
    public function render_data_page() {
        // Get the data
        global $wpdb;
        $table_name = $wpdb->prefix . 'fuel_surcharge_data';
        
        // Handle pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        // Get total count
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_pages = ceil($total_items / $per_page);
        
        // Get data for current page
        $data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY price_date DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        );
        
        // Get calculator instance for formula display
        $calculator = new Calculator();
        
        // Render the data page
        include_once EIA_FUEL_SURCHARGE_PLUGIN_DIR . 'templates/admin/data-page.php';
    }

    /**
     * Render the logs page.
     *
     * @since    1.0.0
     */
    public function render_logs_page() {
        // Get the logger
        $logger = new Logger();
        
        // Handle pagination and filtering
        $per_page = 30;
        $current_page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $log_type = isset($_GET['log_type']) ? sanitize_text_field($_GET['log_type']) : '';
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        
        // Build query args
        $args = [
            'limit' => $per_page,
            'offset' => ($current_page - 1) * $per_page,
            'log_type' => $log_type,
            'search' => $search
        ];
        
        // Get logs
        $logs = $logger->get_logs($args);
        
        // Get total count for pagination
        $total_items = $logger->count_logs([
            'log_type' => $log_type,
            'search' => $search
        ]);
        
        $total_pages = ceil($total_items / $per_page);
        
        // Get log type counts for filtering
        $log_type_counts = $logger->get_log_type_counts();
        
        // Render the logs page
        include_once EIA_FUEL_SURCHARGE_PLUGIN_DIR . 'templates/admin/logs-page.php';
    }

    /**
     * Render form for updating data.
     *
     * @since    1.0.0
     */
    public function render_update_form() {
        // Get the scheduler
        $scheduler = new Scheduler();
        
        // Get next scheduled update
        $next_update = $scheduler->get_next_scheduled_update();
        
        // Format the next update time if available
        if ($next_update) {
            $next_update_formatted = date_i18n(
                get_option('date_format') . ' ' . get_option('time_format'),
                $next_update
            );
        } else {
            $next_update_formatted = __('Not scheduled', 'eia-fuel-surcharge');
        }
        
        // Start output buffer
        ob_start();
        ?>
        <div class="eia-fuel-surcharge-update-form">
            <h3><?php _e('Update Fuel Surcharge Data', 'eia-fuel-surcharge'); ?></h3>
            
            <p>
                <?php _e('Next scheduled update:', 'eia-fuel-surcharge'); ?> 
                <strong><?php echo $next_update_formatted; ?></strong>
            </p>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="eia_fuel_surcharge_manual_update">
                <?php wp_nonce_field('eia_fuel_surcharge_manual_update', 'eia_fuel_surcharge_nonce'); ?>
                <p>
                    <button type="submit" class="button button-primary"><?php _e('Update Now', 'eia-fuel-surcharge'); ?></button>
                    <span class="description"><?php _e('Manually update fuel surcharge data from EIA API.', 'eia-fuel-surcharge'); ?></span>
                </p>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Render data management section.
     *
     * @since    1.0.0
     */
    public function render_data_management() {
        // Start output buffer
        ob_start();
        ?>
        <div class="eia-fuel-surcharge-data-management">
            <h3><?php _e('Data Management', 'eia-fuel-surcharge'); ?></h3>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-bottom: 15px;">
                <input type="hidden" name="action" value="eia_fuel_surcharge_export_data">
                <?php wp_nonce_field('eia_fuel_surcharge_export_data', 'eia_fuel_surcharge_nonce'); ?>
                <p>
                    <button type="submit" class="button"><?php _e('Export Data to CSV', 'eia-fuel-surcharge'); ?></button>
                    <span class="description"><?php _e('Download all fuel surcharge data as a CSV file.', 'eia-fuel-surcharge'); ?></span>
                </p>
            </form>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-bottom: 15px;">
                <input type="hidden" name="action" value="eia_fuel_surcharge_clear_data">
                <?php wp_nonce_field('eia_fuel_surcharge_clear_data', 'eia_fuel_surcharge_nonce'); ?>
                <p>
                    <button type="submit" class="button eia-fuel-surcharge-clear-data"><?php _e('Clear All Data', 'eia-fuel-surcharge'); ?></button>
                    <span class="description"><?php _e('Delete all fuel surcharge data. This cannot be undone!', 'eia-fuel-surcharge'); ?></span>
                </p>
            </form>
            
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="eia_fuel_surcharge_clear_cache">
                <?php wp_nonce_field('eia_fuel_surcharge_clear_cache', 'eia_fuel_surcharge_nonce'); ?>
                <p>
                    <button type="submit" class="button"><?php _e('Clear API Cache', 'eia-fuel-surcharge'); ?></button>
                    <span class="description"><?php _e('Clear cached API responses to ensure fresh data on next update.', 'eia-fuel-surcharge'); ?></span>
                </p>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Render shortcode examples section.
     *
     * @since    1.0.0
     */
    public function render_shortcode_examples() {
        // Start output buffer
        ob_start();
        ?>
        <div class="eia-fuel-surcharge-shortcode-examples">
            <h3><?php _e('Shortcode Examples', 'eia-fuel-surcharge'); ?></h3>
            
            <div class="shortcode-example">
                <h4><?php _e('Basic Usage', 'eia-fuel-surcharge'); ?></h4>
                <code>[fuel_surcharge]</code>
                <p class="description"><?php _e('Displays the current fuel surcharge rate using default settings.', 'eia-fuel-surcharge'); ?></p>
            </div>
            
            <div class="shortcode-example">
                <h4><?php _e('Customized Display', 'eia-fuel-surcharge'); ?></h4>
                <code>[fuel_surcharge format="Fuel Surcharge: {rate}% (as of {date})" date_format="F j, Y" decimals="1"]</code>
                <p class="description"><?php _e('Customizes the display format, date format, and decimal places.', 'eia-fuel-surcharge'); ?></p>
            </div>
            
            <div class="shortcode-example">
                <h4><?php _e('Table Display', 'eia-fuel-surcharge'); ?></h4>
                <code>[fuel_surcharge_table rows="5" date_format="m/d/Y" class="striped"]</code>
                <p class="description"><?php _e('Displays a table of the 5 most recent fuel surcharge rates.', 'eia-fuel-surcharge'); ?></p>
            </div>
            
            <div class="shortcode-example">
                <h4><?php _e('Customized Table', 'eia-fuel-surcharge'); ?></h4>
                <code>[fuel_surcharge_table rows="10" columns="date,rate" order="asc" title="Historical Fuel Surcharge Rates" show_footer="true"]</code>
                <p class="description"><?php _e('Displays a customized table with specific columns, ordering, title, and formula in the footer.', 'eia-fuel-surcharge'); ?></p>
            </div>
            
            <div class="shortcode-example">
                <h4><?php _e('Regional Display', 'eia-fuel-surcharge'); ?></h4>
                <code>[fuel_surcharge region="east_coast"]</code>
                <p class="description"><?php _e('Displays the fuel surcharge rate for a specific region.', 'eia-fuel-surcharge'); ?></p>
            </div>
            
            <p>
                <?php _e('For more shortcode options and usage examples, please refer to the', 'eia-fuel-surcharge'); ?> 
                <a href="https://example.com/eia-fuel-surcharge-documentation" target="_blank"><?php _e('plugin documentation', 'eia-fuel-surcharge'); ?></a>.
            </p>
        </div>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Render API status section.
     *
     * @since    2.0.0
     */
    public function render_api_status() {
        // Get the API handler
        $api_handler = new EIAHandler();
        
        // Get API status
        $api_status = $api_handler->get_api_status();
        
        // Start output buffer
        ob_start();
        ?>
        <div class="eia-fuel-surcharge-api-status">
            <h3><?php _e('API Status', 'eia-fuel-surcharge'); ?></h3>
            
            <table class="widefat striped">
                <tr>
                    <th><?php _e('API Key Configured', 'eia-fuel-surcharge'); ?></th>
                    <td>
                        <?php if ($api_status['has_api_key']): ?>
                            <span class="dashicons dashicons-yes" style="color: green;"></span> <?php _e('Yes', 'eia-fuel-surcharge'); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-no" style="color: red;"></span> <?php _e('No', 'eia-fuel-surcharge'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Last Updated', 'eia-fuel-surcharge'); ?></th>
                    <td>
                        <?php 
                        if ($api_status['last_updated']) {
                            echo $api_status['last_updated'];
                        } else {
                            _e('Never', 'eia-fuel-surcharge');
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Cache Status', 'eia-fuel-surcharge'); ?></th>
                    <td>
                        <?php if ($api_status['cache_enabled']): ?>
                            <span class="dashicons dashicons-yes" style="color: green;"></span> 
                            <?php printf(__('Enabled (%d items, %s duration)', 'eia-fuel-surcharge'), 
                                $api_status['cache_count'],
                                human_time_diff(0, $api_status['cache_duration'])
                            ); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-no" style="color: red;"></span> <?php _e('Disabled', 'eia-fuel-surcharge'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (!empty($api_status['last_error'])): ?>
                <tr>
                    <th><?php _e('Last Error', 'eia-fuel-surcharge'); ?></th>
                    <td>
                        <span class="dashicons dashicons-warning" style="color: orange;"></span>
                        <?php echo esc_html($api_status['last_error']['message']); ?>
                        <br>
                        <small><?php echo $api_status['last_error']['time']; ?></small>
                    </td>
                </tr>
                <?php endif; ?>
                <?php if (isset($api_status['api_test']) && isset($api_status['api_test']['success'])): ?>
                <tr>
                    <th><?php _e('API Connection Test', 'eia-fuel-surcharge'); ?></th>
                    <td>
                        <?php if ($api_status['api_test']['success']): ?>
                            <span class="dashicons dashicons-yes" style="color: green;"></span> 
                            <?php _e('Successful', 'eia-fuel-surcharge'); ?>
                            <?php if (isset($api_status['api_test']['response_time'])): ?>
                                <br><small><?php printf(__('Response time: %s', 'eia-fuel-surcharge'), $api_status['api_test']['response_time']); ?></small>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-no" style="color: red;"></span> 
                            <?php _e('Failed', 'eia-fuel-surcharge'); ?>
                            <?php if (isset($api_status['api_test']['message'])): ?>
                                <br><small><?php echo esc_html($api_status['api_test']['message']); ?></small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Render latest data section.
     *
     * @since    2.0.0
     */
    public function render_latest_data() {
        // Get the latest data
        global $wpdb;
        $table_name = $wpdb->prefix . 'fuel_surcharge_data';
        $latest_data = $wpdb->get_row("SELECT * FROM $table_name ORDER BY price_date DESC LIMIT 1", ARRAY_A);
        
        // Get calculator instance
        $calculator = new Calculator();
        
        // Start output buffer
        ob_start();
        ?>
        <div class="eia-fuel-surcharge-latest-data">
            <h3><?php _e('Latest Fuel Surcharge Data', 'eia-fuel-surcharge'); ?></h3>
            
            <?php if ($latest_data): ?>
                <table class="widefat striped">
                    <tr>
                        <th><?php _e('Date', 'eia-fuel-surcharge'); ?></th>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($latest_data['price_date'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Diesel Price', 'eia-fuel-surcharge'); ?></th>
                        <td>$<?php echo number_format($latest_data['diesel_price'], 3); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Surcharge Rate', 'eia-fuel-surcharge'); ?></th>
                        <td><?php echo number_format($latest_data['surcharge_rate'], 2); ?>%</td>
                    </tr>
                    <tr>
                        <th><?php _e('Region', 'eia-fuel-surcharge'); ?></th>
                        <td><?php echo ucfirst(str_replace('_', ' ', $latest_data['region'])); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Formula', 'eia-fuel-surcharge'); ?></th>
                        <td><?php echo $calculator->get_formula_description(); ?></td>
                    </tr>
                </table>
            <?php else: ?>
                <p><?php _e('No fuel surcharge data available.', 'eia-fuel-surcharge'); ?></p>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
}