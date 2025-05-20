<?php
/**
 * Template for the dashboard widget.
 *
 * @package    EIAFuelSurcharge
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="eia-fuel-surcharge-dashboard-widget">
    <?php if (empty($api_status['has_api_key'])): ?>
        <div class="notice notice-warning inline">
            <p>
                <?php _e('EIA API Key is required. Please enter your API key in the settings page.', 'eia-fuel-surcharge'); ?>
                <a href="<?php echo admin_url('admin.php?page=' . $this->plugin_name . '-settings'); ?>" class="button button-small"><?php _e('Go to Settings', 'eia-fuel-surcharge'); ?></a>
            </p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($latest_data)): ?>
        <div class="current-rate">
            <?php _e('Current Fuel Surcharge:', 'eia-fuel-surcharge'); ?>
            <strong><?php echo number_format($latest_data['surcharge_rate'], isset($options['decimal_places']) ? intval($options['decimal_places']) : 2); ?>%</strong>
            <span class="date-info">
                <?php echo sprintf(__('as of %s', 'eia-fuel-surcharge'), date_i18n(get_option('date_format'), strtotime($latest_data['price_date']))); ?>
            </span>
        </div>
        
        <p>
            <strong><?php _e('Diesel Price:', 'eia-fuel-surcharge'); ?></strong>
            $<?php echo number_format($latest_data['diesel_price'], 3); ?>
            <small>(<?php echo ucfirst(str_replace('_', ' ', $latest_data['region'])); ?>)</small>
        </p>
    <?php else: ?>
        <p><?php _e('No fuel surcharge data available. Please update the data.', 'eia-fuel-surcharge'); ?></p>
    <?php endif; ?>
    
    <p>
        <strong><?php _e('Next Update:', 'eia-fuel-surcharge'); ?></strong>
        <?php 
        if ($next_update) {
            echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_update);
            echo ' (' . human_time_diff(time(), $next_update) . ' ' . __('from now', 'eia-fuel-surcharge') . ')';
        } else {
            _e('Not scheduled', 'eia-fuel-surcharge');
        }
        ?>
    </p>
    
    <div class="eia-fuel-surcharge-actions">
        <a href="<?php echo admin_url('admin.php?page=' . $this->plugin_name . '-data'); ?>" class="button button-small">
            <span class="dashicons dashicons-update" style="font-size: 16px; vertical-align: middle; margin-right: 2px;"></span>
            <?php _e('Update Data', 'eia-fuel-surcharge'); ?>
        </a>
        
        <a href="<?php echo admin_url('admin.php?page=' . $this->plugin_name . '-settings'); ?>" class="button button-small">
            <span class="dashicons dashicons-admin-generic" style="font-size: 16px; vertical-align: middle; margin-right: 2px;"></span>
            <?php _e('Settings', 'eia-fuel-surcharge'); ?>
        </a>
    </div>

    <?php if (!empty($latest_data)): ?>
    <div class="shortcode-info" style="margin-top: 12px; padding-top: 8px; border-top: 1px solid #eee;">
        <strong><?php _e('Shortcodes:', 'eia-fuel-surcharge'); ?></strong>
        <code style="display: block; margin: 5px 0; padding: 4px; background: #f8f8f8;">[fuel_surcharge]</code>
        <code style="display: block; margin: 5px 0; padding: 4px; background: #f8f8f8;">[fuel_surcharge_table]</code>
    </div>
    <?php endif; ?>
</div>

<style>
/* Dashboard Widget Styles */
.eia-fuel-surcharge-dashboard-widget .current-rate {
    font-size: 16px;
    margin-bottom: 12px;
}

.eia-fuel-surcharge-dashboard-widget .date-info {
    display: block;
    font-size: 12px;
    color: #777;
    margin-top: 2px;
}

.eia-fuel-surcharge-dashboard-widget .eia-fuel-surcharge-actions {
    margin-top: 12px;
}

.eia-fuel-surcharge-dashboard-widget .eia-fuel-surcharge-actions a {
    margin-right: 5px;
}

.eia-fuel-surcharge-dashboard-widget .notice {
    margin: 0 0 12px;
    padding: 6px 12px;
}
</style>