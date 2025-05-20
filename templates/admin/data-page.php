<?php
/**
 * Template for the admin data page.
 *
 * @package    EIAFuelSurcharge
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php
    // Display success/error messages
    if (isset($_GET['update']) && $_GET['update'] == 'success') {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Fuel surcharge data updated successfully.', 'eia-fuel-surcharge') . '</p></div>';
    }
    if (isset($_GET['cleared']) && $_GET['cleared'] == 'true') {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Data cleared successfully.', 'eia-fuel-surcharge') . '</p></div>';
    }
    ?>
    
    <div class="eia-fuel-surcharge-admin-container">
        <div class="eia-fuel-surcharge-main">
            <div class="eia-fuel-surcharge-box">
                <h2><?php _e('Update Fuel Surcharge Data', 'eia-fuel-surcharge'); ?></h2>
                
                <?php
                // Get scheduler instance
                $scheduler = new EIAFuelSurcharge\Core\Scheduler();
                $next_update = $scheduler->get_next_scheduled_update();
                
                if ($next_update) {
                    echo '<p><strong>' . __('Next Scheduled Update:', 'eia-fuel-surcharge') . '</strong> ';
                    echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_update);
                    echo ' (' . human_time_diff(time(), $next_update) . ' ' . __('from now', 'eia-fuel-surcharge') . ')</p>';
                } else {
                    echo '<p><em>' . __('No update currently scheduled.', 'eia-fuel-surcharge') . '</em></p>';
                }
                ?>
                
                <p><?php _e('Click the button below to manually retrieve the latest diesel price data from the EIA API and calculate updated fuel surcharge rates.', 'eia-fuel-surcharge'); ?></p>
                
                <div class="eia-update-container">
                    <button type="button" id="manual-update-button" class="button button-primary"><?php _e('Update Data', 'eia-fuel-surcharge'); ?></button>
                    <span class="spinner" style="float:none; margin-left:10px; margin-right:0;"></span>
                </div>
                
                <div id="manual-update-results" class="eia-update-results" style="margin-top: 15px;"></div>
            </div>

            <div class="eia-fuel-surcharge-box">
                <h2><?php _e('Fuel Surcharge Data', 'eia-fuel-surcharge'); ?></h2>
                
                <?php
                // Get calculator instance - FIXED: Using namespaced class
                $calculator = new EIAFuelSurcharge\Utilities\Calculator();
                
                // Get the historical data (all of it)
                global $wpdb;
                $table_name = $wpdb->prefix . 'fuel_surcharge_data';
                $data = $wpdb->get_results("SELECT * FROM $table_name ORDER BY price_date DESC", ARRAY_A);
                
                if (empty($data)) {
                    echo '<p>' . __('No fuel surcharge data available.', 'eia-fuel-surcharge') . '</p>';
                } else {
                    // Get date format from settings
                    $options = get_option('eia_fuel_surcharge_settings');
                    $date_format = isset($options['date_format']) ? $options['date_format'] : 'm/d/Y';
                    $decimals = isset($options['decimal_places']) ? $options['decimal_places'] : 2;
                    
                    echo '<div class="tablenav top">';
                    echo '<div class="alignleft actions">';
                    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block; margin-right: 10px;">';
                    echo '<input type="hidden" name="action" value="eia_fuel_surcharge_clear_data">';
                    wp_nonce_field('eia_fuel_surcharge_clear_data', 'eia_fuel_surcharge_nonce');
                    echo '<button type="submit" class="button eia-fuel-surcharge-clear-data">' . __('Clear All Data', 'eia-fuel-surcharge') . '</button>';
                    echo '</form>';
                    echo '</div>';
                    
                    echo '<div class="alignright">';
                    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
                    echo '<input type="hidden" name="action" value="eia_fuel_surcharge_export_data">';
                    wp_nonce_field('eia_fuel_surcharge_export_data', 'eia_fuel_surcharge_nonce');
                    echo '<button type="submit" class="button">' . __('Export CSV', 'eia-fuel-surcharge') . '</button>';
                    echo '</form>';
                    echo '</div>';
                    echo '<br class="clear">';
                    echo '</div>';
                    
                    echo '<table class="eia-fuel-surcharge-data-table">';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th>' . __('Date', 'eia-fuel-surcharge') . '</th>';
                    echo '<th>' . __('Diesel Price', 'eia-fuel-surcharge') . '</th>';
                    echo '<th>' . __('Surcharge Rate', 'eia-fuel-surcharge') . '</th>';
                    echo '<th>' . __('Region', 'eia-fuel-surcharge') . '</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                    
                    foreach ($data as $row) {
                        echo '<tr>';
                        echo '<td>' . date($date_format, strtotime($row['price_date'])) . '</td>';
                        echo '<td>$' . number_format($row['diesel_price'], 3) . '</td>';
                        echo '<td>' . number_format($row['surcharge_rate'], $decimals) . '%</td>';
                        echo '<td>' . ucfirst(str_replace('_', ' ', $row['region'])) . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody>';
                    echo '</table>';
                }
                ?>
            </div>
        </div>
        
        <div class="eia-fuel-surcharge-sidebar">
            <div class="eia-fuel-surcharge-box">
                <h3><?php _e('Data Statistics', 'eia-fuel-surcharge'); ?></h3>
                <?php
                if (!empty($data)) {
                    // Count total records
                    $total_records = count($data);
                    
                    // Get oldest and newest dates
                    $newest_date = date($date_format, strtotime($data[0]['price_date']));
                    $oldest_date = date($date_format, strtotime(end($data)['price_date']));
                    
                    // Calculate average diesel price and surcharge rate
                    $total_price = 0;
                    $total_rate = 0;
                    
                    foreach ($data as $row) {
                        $total_price += $row['diesel_price'];
                        $total_rate += $row['surcharge_rate'];
                    }
                    
                    $avg_price = $total_price / $total_records;
                    $avg_rate = $total_rate / $total_records;
                    
                    echo '<p>' . __('Total Records:', 'eia-fuel-surcharge') . ' ' . $total_records . '</p>';
                    echo '<p>' . __('Date Range:', 'eia-fuel-surcharge') . ' ' . $oldest_date . ' - ' . $newest_date . '</p>';
                    echo '<p>' . __('Average Diesel Price:', 'eia-fuel-surcharge') . ' $' . number_format($avg_price, 3) . '</p>';
                    echo '<p>' . __('Average Surcharge Rate:', 'eia-fuel-surcharge') . ' ' . number_format($avg_rate, $decimals) . '%</p>';

                    // Get regions count
                    $regions_count = $wpdb->get_var("SELECT COUNT(DISTINCT region) FROM $table_name");
                    echo '<p>' . __('Available Regions:', 'eia-fuel-surcharge') . ' ' . $regions_count . '</p>';
                }
                ?>
            </div>
            
            <div class="eia-fuel-surcharge-box">
                <h3><?php _e('Shortcodes', 'eia-fuel-surcharge'); ?></h3>
                <p><?php _e('Use these shortcodes to display fuel surcharge information on your site:', 'eia-fuel-surcharge'); ?></p>
                <ul>
                    <li><code>[fuel_surcharge]</code> - <?php _e('Display the current fuel surcharge rate as text.', 'eia-fuel-surcharge'); ?></li>
                    <li><code>[fuel_surcharge_table]</code> - <?php _e('Display a table of historical fuel surcharge rates.', 'eia-fuel-surcharge'); ?></li>
                </ul>
                <p><a href="<?php echo admin_url('admin.php?page=' . $this->plugin_name . '-settings'); ?>#shortcodes"><?php _e('View More Examples', 'eia-fuel-surcharge'); ?></a></p>
            </div>

            <div class="eia-fuel-surcharge-box">
                <h3><?php _e('API Status', 'eia-fuel-surcharge'); ?></h3>
                <?php
                // Get API handler
                $api_handler = new EIAFuelSurcharge\API\EIAHandler();
                
                // Get API status
                $api_status = $api_handler->get_api_status();
                
                echo '<table class="widefat">';
                echo '<tr>';
                echo '<th>' . __('API Key', 'eia-fuel-surcharge') . '</th>';
                echo '<td>';
                if ($api_status['has_api_key']) {
                    echo '<span class="dashicons dashicons-yes" style="color: green;"></span> ' . __('Configured', 'eia-fuel-surcharge');
                } else {
                    echo '<span class="dashicons dashicons-no" style="color: red;"></span> ' . __('Not configured', 'eia-fuel-surcharge');
                }
                echo '</td>';
                echo '</tr>';
                
                echo '<tr>';
                echo '<th>' . __('Last Updated', 'eia-fuel-surcharge') . '</th>';
                echo '<td>';
                if (isset($api_status['last_updated']) && $api_status['last_updated']) {
                    echo $api_status['last_updated'];
                } else {
                    echo __('Never', 'eia-fuel-surcharge');
                }
                echo '</td>';
                echo '</tr>';
                
                if (!empty($api_status['last_error'])) {
                    echo '<tr>';
                    echo '<th>' . __('Last Error', 'eia-fuel-surcharge') . '</th>';
                    echo '<td>';
                    echo '<span class="dashicons dashicons-warning" style="color: orange;"></span> ';
                    echo esc_html($api_status['last_error']['message']);
                    echo '<br><small>' . $api_status['last_error']['time'] . '</small>';
                    echo '</td>';
                    echo '</tr>';
                }
                
                echo '</table>';
                ?>
                <p style="margin-top: 10px;">
                    <a href="<?php echo admin_url('admin.php?page=' . $this->plugin_name . '-settings'); ?>" class="button button-small"><?php _e('Manage API Settings', 'eia-fuel-surcharge'); ?></a>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
/* Styles for update section */
.eia-update-container {
    display: flex;
    align-items: center;
    margin: 15px 0;
}

.eia-update-container .spinner {
    visibility: hidden;
}

.eia-update-container .spinner.is-active {
    visibility: visible;
}

.eia-update-results {
    margin-top: 15px;
    padding: 15px;
    background-color: #f9f9f9;
    border-left: 4px solid #ccc;
    display: none;
}

.eia-update-results.success {
    border-left-color: #46b450;
    background-color: #ecf7ed;
}

.eia-update-results.error {
    border-left-color: #dc3232;
    background-color: #fbeaea;
}

.eia-update-results.loading {
    border-left-color: #ffb900;
    background-color: #fff8e5;
    display: block;
}

.eia-debug-info {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #ddd;
}

.eia-debug-info-content {
    max-height: 300px;
    overflow: auto;
    background: #f6f7f7;
    padding: 10px;
    border: 1px solid #ddd;
    margin-top: 10px;
    font-family: monospace;
    font-size: 12px;
}

.eia-update-stats {
    margin-top: 10px;
    font-size: 13px;
}

.eia-update-stats ul {
    margin-left: 20px;
    list-style-type: disc;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Handle the manual update button
    $('#manual-update-button').on('click', function() {
        var $button = $(this);
        var $spinner = $button.next('.spinner');
        var $resultsContainer = $('#manual-update-results');
        var originalText = $button.text();
        
        // Clear previous results
        $resultsContainer.removeClass('success error').empty();
        
        // Show loading state
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        $resultsContainer.addClass('loading').html('<p>' + eia_fuel_surcharge_params.i18n.updating + '...</p>');
        
        // Send AJAX request
        $.ajax({
            url: eia_fuel_surcharge_params.ajax_url,
            type: 'POST',
            data: {
                action: 'eia_fuel_surcharge_manual_update_ajax',
                nonce: eia_fuel_surcharge_params.manual_update_nonce
            },
            success: function(response) {
                // Clear the loading class
                $resultsContainer.removeClass('loading');
                
                if (response.success) {
                    // Success case
                    $resultsContainer.addClass('success').empty();
                    
                    var html = '<h3>' + eia_fuel_surcharge_params.i18n.update_success + '</h3>';
                    html += '<p>' + (response.data.message || 'Data updated successfully.') + '</p>';
                    
                    // Add stats if available
                    if (response.data.stats) {
                        html += '<div class="eia-update-stats">';
                        html += '<h4>' + eia_fuel_surcharge_params.i18n.update_stats + '</h4>';
                        html += '<ul>';
                        if (response.data.stats.inserted > 0) {
                            html += '<li>' + response.data.stats.inserted + ' ' + eia_fuel_surcharge_params.i18n.records_inserted + '</li>';
                        }
                        if (response.data.stats.updated > 0) {
                            html += '<li>' + response.data.stats.updated + ' ' + eia_fuel_surcharge_params.i18n.records_updated + '</li>';
                        }
                        if (response.data.stats.skipped > 0) {
                            html += '<li>' + response.data.stats.skipped + ' ' + eia_fuel_surcharge_params.i18n.records_skipped + '</li>';
                        }
                        html += '</ul>';
                        html += '</div>';
                    }
                    
                    $resultsContainer.html(html).show();
                    
                    // Reload the page after short delay to show updated data
                    setTimeout(function() {
                        window.location.reload();
                    }, 3000);
                } else {
                    // Error case
                    $resultsContainer.addClass('error').empty();
                    
                    var html = '<h3>' + eia_fuel_surcharge_params.i18n.update_failed + '</h3>';
                    html += '<p>' + (response.data.message || 'Unknown error occurred.') + '</p>';
                    
                    // Add debug info if available
                    if (response.data.debug) {
                        html += '<div class="eia-debug-info">';
                        html += '<p><a href="#" class="eia-toggle-debug-info">' + eia_fuel_surcharge_params.i18n.show_debug + '</a></p>';
                        html += '<div class="eia-debug-info-content" style="display:none;">';
                        html += '<pre>' + JSON.stringify(response.data.debug, null, 2) + '</pre>';
                        html += '</div>';
                        html += '</div>';
                    }
                    
                    $resultsContainer.html(html).show();
                    
                    // Add toggle functionality for debug info
                    $resultsContainer.find('.eia-toggle-debug-info').on('click', function(e) {
                        e.preventDefault();
                        $(this).closest('.eia-debug-info').find('.eia-debug-info-content').slideToggle();
                    });
                }
            },
            error: function(xhr, status, error) {
                // Handle AJAX errors
                $resultsContainer.removeClass('loading').addClass('error').html(
                    '<h3>' + eia_fuel_surcharge_params.i18n.update_failed + '</h3>' +
                    '<p>' + eia_fuel_surcharge_params.i18n.ajax_error + ': ' + error + '</p>'
                ).show();
            },
            complete: function() {
                // Reset button state
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
});
</script>