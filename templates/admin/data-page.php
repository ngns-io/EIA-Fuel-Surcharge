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
                    echo '<input type="hidden" name="action" value="eia_fuel_surcharge_manual_update">';
                    wp_nonce_field('eia_fuel_surcharge_manual_update', 'eia_fuel_surcharge_nonce');
                    echo '<button type="submit" class="button button-primary">' . __('Update Data', 'eia-fuel-surcharge') . '</button>';
                    echo '</form>';
                    
                    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:inline-block;">';
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
                        echo '<td>' . ucfirst($row['region']) . '</td>';
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
        </div>
    </div>
</div>