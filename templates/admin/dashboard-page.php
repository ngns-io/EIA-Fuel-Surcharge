<?php
/**
 * Template for the admin dashboard page.
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
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Operation completed successfully.', 'eia-fuel-surcharge') . '</p></div>';
    }
    if (isset($_GET['error'])) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html(urldecode($_GET['error'])) . '</p></div>';
    }
    ?>
    
    <div class="eia-fuel-surcharge-admin-container">
        <div class="eia-fuel-surcharge-main">
            <!-- Main Content -->
            <div class="eia-fuel-surcharge-box">
                <h2><?php _e('Fuel Surcharge Dashboard', 'eia-fuel-surcharge'); ?></h2>
                
                <?php if (empty($api_status['has_api_key'])): ?>
                    <div class="notice notice-warning inline">
                        <p>
                            <?php _e('EIA API Key is required. Please enter your API key in the settings page.', 'eia-fuel-surcharge'); ?>
                            <a href="<?php echo admin_url('admin.php?page=' . $this->plugin_name . '-settings'); ?>" class="button button-small"><?php _e('Go to Settings', 'eia-fuel-surcharge'); ?></a>
                        </p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($latest_data)): ?>
                    <div class="eia-fuel-surcharge-overview">
                        <div class="eia-fuel-surcharge-stat">
                            <h3><?php _e('Current Fuel Surcharge', 'eia-fuel-surcharge'); ?></h3>
                            <div class="eia-fuel-surcharge-value"><?php echo number_format($latest_data['surcharge_rate'], 2); ?>%</div>
                            <div class="eia-fuel-surcharge-subtext">
                                <?php echo sprintf(__('as of %s', 'eia-fuel-surcharge'), date_i18n(get_option('date_format'), strtotime($latest_data['price_date']))); ?>
                            </div>
                        </div>
                        
                        <div class="eia-fuel-surcharge-stat">
                            <h3><?php _e('Diesel Price', 'eia-fuel-surcharge'); ?></h3>
                            <div class="eia-fuel-surcharge-value">$<?php echo number_format($latest_data['diesel_price'], 3); ?></div>
                            <div class="eia-fuel-surcharge-subtext">
                                <?php echo ucfirst(str_replace('_', ' ', $latest_data['region'])); ?> <?php _e('average', 'eia-fuel-surcharge'); ?>
                            </div>
                        </div>
                        
                        <div class="eia-fuel-surcharge-stat">
                            <h3><?php _e('Next Update', 'eia-fuel-surcharge'); ?></h3>
                            <div class="eia-fuel-surcharge-value" style="font-size: 1.2em;">
                                <?php 
                                if ($next_update) {
                                    echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_update);
                                } else {
                                    _e('Not scheduled', 'eia-fuel-surcharge');
                                }
                                ?>
                            </div>
                            <div class="eia-fuel-surcharge-subtext">
                                <?php 
                                if ($next_update) {
                                    echo human_time_diff(time(), $next_update) . ' ' . __('from now', 'eia-fuel-surcharge');
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="notice notice-info inline">
                        <p><?php _e('No fuel surcharge data available. Please update the data.', 'eia-fuel-surcharge'); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="eia-fuel-surcharge-actions">
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline-block; margin-right: 10px;">
                        <input type="hidden" name="action" value="eia_fuel_surcharge_manual_update">
                        <?php wp_nonce_field('eia_fuel_surcharge_manual_update', 'eia_fuel_surcharge_nonce'); ?>
                        <button type="submit" class="button button-primary"><?php _e('Update Data', 'eia-fuel-surcharge'); ?></button>
                    </form>
                    
                    <a href="<?php echo admin_url('admin.php?page=' . $this->plugin_name . '-data'); ?>" class="button"><?php _e('View All Data', 'eia-fuel-surcharge'); ?></a>
                </div>
            </div>
            
            <!-- Shortcode Usage -->
            <div class="eia-fuel-surcharge-box">
                <h2><?php _e('Shortcode Usage', 'eia-fuel-surcharge'); ?></h2>
                
                <p><?php _e('Use these shortcodes to display fuel surcharge information on your website:', 'eia-fuel-surcharge'); ?></p>
                
                <div class="eia-fuel-surcharge-shortcodes">
                    <div class="eia-fuel-surcharge-shortcode">
                        <h4><?php _e('Display Current Rate', 'eia-fuel-surcharge'); ?></h4>
                        <code>[fuel_surcharge]</code>
                        <p class="description"><?php _e('Shows the current fuel surcharge rate with date.', 'eia-fuel-surcharge'); ?></p>
                    </div>
                    
                    <div class="eia-fuel-surcharge-shortcode">
                        <h4><?php _e('Display Historical Data Table', 'eia-fuel-surcharge'); ?></h4>
                        <code>[fuel_surcharge_table rows="10"]</code>
                        <p class="description"><?php _e('Shows a table of historical fuel surcharge rates.', 'eia-fuel-surcharge'); ?></p>
                    </div>
                </div>
                
                <p><a href="<?php echo admin_url('admin.php?page=' . $this->plugin_name . '-settings'); ?>#shortcode-examples" class="button button-small"><?php _e('More Examples', 'eia-fuel-surcharge'); ?></a></p>
            </div>
            
            <!-- Recent Activity -->
            <div class="eia-fuel-surcharge-box">
                <h2><?php _e('Recent Activity', 'eia-fuel-surcharge'); ?></h2>
                
                <?php if (!empty($recent_logs)): ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Date & Time', 'eia-fuel-surcharge'); ?></th>
                                <th><?php _e('Event', 'eia-fuel-surcharge'); ?></th>
                                <th><?php _e('Details', 'eia-fuel-surcharge'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_logs as $log): ?>
                                <tr>
                                    <td><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log['created_at'])); ?></td>
                                    <td><span class="log-type-<?php echo esc_attr($log['log_type']); ?>"><?php echo esc_html($log['log_type']); ?></span></td>
                                    <td><?php echo esc_html($log['message']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <p class="description" style="margin-top: 10px;">
                        <a href="<?php echo admin_url('admin.php?page=' . $this->plugin_name . '-logs'); ?>"><?php _e('View All Logs', 'eia-fuel-surcharge'); ?> →</a>
                    </p>
                <?php else: ?>
                    <p><?php _e('No recent activity logged.', 'eia-fuel-surcharge'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="eia-fuel-surcharge-sidebar">
            <!-- API Status -->
            <div class="eia-fuel-surcharge-box">
                <h3><?php _e('API Status', 'eia-fuel-surcharge'); ?></h3>
                
                <table class="widefat">
                    <tr>
                        <th><?php _e('API Key', 'eia-fuel-surcharge'); ?></th>
                        <td>
                            <?php if ($api_status['has_api_key']): ?>
                                <span class="dashicons dashicons-yes" style="color: green;"></span> <?php _e('Configured', 'eia-fuel-surcharge'); ?>
                            <?php else: ?>
                                <span class="dashicons dashicons-no" style="color: red;"></span> <?php _e('Not configured', 'eia-fuel-surcharge'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Connection', 'eia-fuel-surcharge'); ?></th>
                        <td>
                            <?php if (isset($api_status['api_test']) && isset($api_status['api_test']['success'])): ?>
                                <?php if ($api_status['api_test']['success']): ?>
                                    <span class="dashicons dashicons-yes" style="color: green;"></span> <?php _e('Connected', 'eia-fuel-surcharge'); ?>
                                <?php else: ?>
                                    <span class="dashicons dashicons-no" style="color: red;"></span> <?php _e('Failed', 'eia-fuel-surcharge'); ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="dashicons dashicons-minus"></span> <?php _e('Unknown', 'eia-fuel-surcharge'); ?>
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
                </table>
                
                <?php if (!empty($api_status['last_error'])): ?>
                    <div class="notice notice-warning inline" style="margin-top: 10px;">
                        <p>
                            <strong><?php _e('Last Error:', 'eia-fuel-surcharge'); ?></strong>
                            <?php echo esc_html($api_status['last_error']['message']); ?>
                            <small>(<?php echo $api_status['last_error']['time']; ?>)</small>
                        </p>
                    </div>
                <?php endif; ?>
                
                <p style="margin-top: 10px;">
                    <a href="<?php echo admin_url('admin.php?page=' . $this->plugin_name . '-settings'); ?>" class="button button-small"><?php _e('Manage API Settings', 'eia-fuel-surcharge'); ?></a>
                </p>
            </div>
            
            <!-- Plugin Information -->
            <div class="eia-fuel-surcharge-box">
                <h3><?php _e('Plugin Information', 'eia-fuel-surcharge'); ?></h3>
                
                <table class="widefat">
                    <tr>
                        <th><?php _e('Version', 'eia-fuel-surcharge'); ?></th>
                        <td><?php echo $this->version; ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Records in Database', 'eia-fuel-surcharge'); ?></th>
                        <td><?php echo number_format($total_records); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Next Scheduled Update', 'eia-fuel-surcharge'); ?></th>
                        <td>
                            <?php 
                            if ($next_update) {
                                echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_update);
                            } else {
                                _e('Not scheduled', 'eia-fuel-surcharge');
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Formula', 'eia-fuel-surcharge'); ?></th>
                        <td><small><?php echo $calculator->get_formula_description(); ?></small></td>
                    </tr>
                </table>
            </div>
            
            <!-- Quick Actions -->
            <div class="eia-fuel-surcharge-box">
                <h3><?php _e('Quick Actions', 'eia-fuel-surcharge'); ?></h3>
                
                <ul class="eia-fuel-surcharge-actions-list">
                    <li>
                        <a href="<?php echo admin_url('admin.php?page=' . $this->plugin_name . '-settings'); ?>"><?php _e('Plugin Settings', 'eia-fuel-surcharge'); ?></a>
                    </li>
                    <li>
                        <a href="<?php echo admin_url('admin.php?page=' . $this->plugin_name . '-data'); ?>"><?php _e('View All Data', 'eia-fuel-surcharge'); ?></a>
                    </li>
                    <li>
                        <a href="<?php echo admin_url('admin.php?page=' . $this->plugin_name . '-logs'); ?>"><?php _e('View System Logs', 'eia-fuel-surcharge'); ?></a>
                    </li>
                    <li>
                        <a href="https://www.eia.gov/petroleum/gasdiesel/" target="_blank"><?php _e('EIA Diesel Price Data', 'eia-fuel-surcharge'); ?> <span class="dashicons dashicons-external" style="font-size: 14px;"></span></a>
                    </li>
                    <li>
                        <a href="https://www.eia.gov/opendata/" target="_blank"><?php _e('EIA API Registration', 'eia-fuel-surcharge'); ?> <span class="dashicons dashicons-external" style="font-size: 14px;"></span></a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
/* Dashboard specific styling */
.eia-fuel-surcharge-overview {
    display: flex;
    flex-wrap: wrap;
    margin: 15px -10px;
}

.eia-fuel-surcharge-stat {
    flex: 1;
    min-width: 200px;
    padding: 15px;
    margin: 0 10px 20px;
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    text-align: center;
}

.eia-fuel-surcharge-stat h3 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 14px;
    color: #555;
}

.eia-fuel-surcharge-value {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 5px;
}

.eia-fuel-surcharge-subtext {
    font-size: 12px;
    color: #777;
}

.eia-fuel-surcharge-shortcodes {
    display: flex;
    flex-wrap: wrap;
    margin: 15px -10px;
}

.eia-fuel-surcharge-shortcode {
    flex: 1;
    min-width: 200px;
    padding: 15px;
    margin: 0 10px 10px;
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
}

.eia-fuel-surcharge-shortcode h4 {
    margin-top: 0;
    margin-bottom: 10px;
}

.eia-fuel-surcharge-shortcode code {
    display: block;
    padding: 10px;
    margin-bottom: 10px;
    background: #f1f1f1;
    border: 1px solid #ddd;
}

.eia-fuel-surcharge-actions-list {
    margin-left: 0;
    padding-left: 0;
}

.eia-fuel-surcharge-actions-list li {
    margin-bottom: 8px;
    padding-left: 0;
    list-style-type: none;
}

.eia-fuel-surcharge-actions-list li:before {
    content: "→";
    display: inline-block;
    margin-right: 5px;
    color: #2271b1;
}
</style>