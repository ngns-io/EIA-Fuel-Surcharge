<?php
/**
 * Template for the admin settings page.
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
    
    <?php settings_errors(); ?>
    
    <div class="eia-fuel-surcharge-tabs nav-tab-wrapper">
        <a href="#api" class="nav-tab"><?php _e('API Settings', 'eia-fuel-surcharge'); ?></a>
        <a href="#calculation" class="nav-tab"><?php _e('Calculation', 'eia-fuel-surcharge'); ?></a>
        <a href="#schedule" class="nav-tab"><?php _e('Schedule', 'eia-fuel-surcharge'); ?></a>
        <a href="#display" class="nav-tab"><?php _e('Display', 'eia-fuel-surcharge'); ?></a>
        <a href="#shortcodes" class="nav-tab"><?php _e('Shortcodes', 'eia-fuel-surcharge'); ?></a>
    </div>
    
    <form method="post" action="options.php">
        <?php settings_fields('eia_fuel_surcharge_options'); ?>

<!-- API Settings Tab -->
        <div id="api" class="eia-fuel-surcharge-tab-content">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="api_key"><?php _e('EIA API Key', 'eia-fuel-surcharge'); ?></label>
                    </th>
                    <td>
                        <?php
                        $options = get_option('eia_fuel_surcharge_settings');
                        $api_key = isset($options['api_key']) ? $options['api_key'] : '';
                        ?>
                        <input type="text" id="api_key" name="eia_fuel_surcharge_settings[api_key]" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                        <button type="button" id="test-api-key" class="button button-secondary"><?php _e('Test API Key', 'eia-fuel-surcharge'); ?></button>
                        <p class="description">
                            <?php _e('Enter your EIA API key. If you don\'t have an API key, you can get one for free at', 'eia-fuel-surcharge'); ?> 
                            <a href="https://www.eia.gov/opendata/" target="_blank">EIA Open Data</a>.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="region"><?php _e('Default Region', 'eia-fuel-surcharge'); ?></label>
                    </th>
                    <td>
                        <?php
                        $region = isset($options['region']) ? $options['region'] : 'national';
                        ?>
                        <select id="region" name="eia_fuel_surcharge_settings[region]">
                            <option value="national" <?php selected($region, 'national'); ?>><?php _e('National Average', 'eia-fuel-surcharge'); ?></option>
                            <option value="east_coast" <?php selected($region, 'east_coast'); ?>><?php _e('East Coast', 'eia-fuel-surcharge'); ?></option>
                            <option value="new_england" <?php selected($region, 'new_england'); ?>><?php _e('New England', 'eia-fuel-surcharge'); ?></option>
                            <option value="central_atlantic" <?php selected($region, 'central_atlantic'); ?>><?php _e('Central Atlantic', 'eia-fuel-surcharge'); ?></option>
                            <option value="lower_atlantic" <?php selected($region, 'lower_atlantic'); ?>><?php _e('Lower Atlantic', 'eia-fuel-surcharge'); ?></option>
                            <option value="midwest" <?php selected($region, 'midwest'); ?>><?php _e('Midwest', 'eia-fuel-surcharge'); ?></option>
                            <option value="gulf_coast" <?php selected($region, 'gulf_coast'); ?>><?php _e('Gulf Coast', 'eia-fuel-surcharge'); ?></option>
                            <option value="rocky_mountain" <?php selected($region, 'rocky_mountain'); ?>><?php _e('Rocky Mountain', 'eia-fuel-surcharge'); ?></option>
                            <option value="west_coast" <?php selected($region, 'west_coast'); ?>><?php _e('West Coast', 'eia-fuel-surcharge'); ?></option>
                            <option value="california" <?php selected($region, 'california'); ?>><?php _e('California', 'eia-fuel-surcharge'); ?></option>
                        </select>
                        <p class="description"><?php _e('Select the default region for fuel price data.', 'eia-fuel-surcharge'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="cache_duration"><?php _e('Cache Duration (minutes)', 'eia-fuel-surcharge'); ?></label>
                    </th>
                    <td>
                        <?php
                        $cache_duration = isset($options['cache_duration']) ? intval($options['cache_duration']) : 60;
                        ?>
                        <input type="number" id="cache_duration" name="eia_fuel_surcharge_settings[cache_duration]" value="<?php echo esc_attr($cache_duration); ?>" min="5" max="1440" step="5" class="small-text" />
                        <p class="description"><?php _e('How long to cache API responses (in minutes). Minimum 5 minutes, maximum 24 hours (1440 minutes).', 'eia-fuel-surcharge'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="max_retries"><?php _e('Max API Retries', 'eia-fuel-surcharge'); ?></label>
                    </th>
                    <td>
                        <?php
                        $max_retries = isset($options['max_retries']) ? intval($options['max_retries']) : 3;
                        ?>
                        <input type="number" id="max_retries" name="eia_fuel_surcharge_settings[max_retries]" value="<?php echo esc_attr($max_retries); ?>" min="1" max="5" class="small-text" />
                        <p class="description"><?php _e('Maximum number of retry attempts for API calls in case of failure.', 'eia-fuel-surcharge'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="eia_source_link"><?php _e('Show EIA Source Link', 'eia-fuel-surcharge'); ?></label>
                    </th>
                    <td>
                        <?php
                        $eia_source_link = isset($options['eia_source_link']) ? $options['eia_source_link'] : 'true';
                        ?>
                        <label for="eia_source_link">
                            <input type="checkbox" id="eia_source_link" name="eia_fuel_surcharge_settings[eia_source_link]" value="true" <?php checked($eia_source_link, 'true'); ?> />
                            <?php _e('Display a link to the EIA website as the data source.', 'eia-fuel-surcharge'); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
<!-- Calculation Settings Tab -->
        <div id="calculation" class="eia-fuel-surcharge-tab-content">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="base_threshold"><?php _e('Base Price Threshold ($)', 'eia-fuel-surcharge'); ?></label>
                    </th>
                    <td>
                        <?php
                        $base_threshold = isset($options['base_threshold']) ? floatval($options['base_threshold']) : 1.20;
                        ?>
                        <input type="number" step="0.01" id="base_threshold" name="eia_fuel_surcharge_settings[base_threshold]" value="<?php echo esc_attr($base_threshold); ?>" class="small-text" />
                        <p class="description"><?php _e('The base diesel price threshold (in dollars) at which the surcharge begins.', 'eia-fuel-surcharge'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="increment_amount"><?php _e('Price Increment ($)', 'eia-fuel-surcharge'); ?></label>
                    </th>
                    <td>
                        <?php
                        $increment_amount = isset($options['increment_amount']) ? floatval($options['increment_amount']) : 0.06;
                        ?>
                        <input type="number" step="0.01" id="increment_amount" name="eia_fuel_surcharge_settings[increment_amount]" value="<?php echo esc_attr($increment_amount); ?>" class="small-text" />
                        <p class="description"><?php _e('The price increment (in dollars) that triggers an increase in the surcharge percentage.', 'eia-fuel-surcharge'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="percentage_rate"><?php _e('Percentage Rate (%)', 'eia-fuel-surcharge'); ?></label>
                    </th>
                    <td>
                        <?php
                        $percentage_rate = isset($options['percentage_rate']) ? floatval($options['percentage_rate']) : 0.5;
                        ?>
                        <input type="number" step="0.1" id="percentage_rate" name="eia_fuel_surcharge_settings[percentage_rate]" value="<?php echo esc_attr($percentage_rate); ?>" class="small-text" />
                        <p class="description"><?php _e('The percentage increase in surcharge for each price increment.', 'eia-fuel-surcharge'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Current Formula', 'eia-fuel-surcharge'); ?></th>
                    <td>
                        <div class="formula-preview">
                            <code>
                                <?php
                                $calculator = new EIAFuelSurcharge\Utilities\Calculator();
                                echo $calculator->get_formula_description();
                                ?>
                            </code>
                        </div>
                        <p class="description">
                            <?php _e('Formula: Surcharge = ((Diesel Price - Base Threshold) / Increment Amount) * Percentage Rate', 'eia-fuel-surcharge'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
<!-- Schedule Settings Tab -->
        <div id="schedule" class="eia-fuel-surcharge-tab-content">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="update_frequency"><?php _e('Update Frequency', 'eia-fuel-surcharge'); ?></label>
                    </th>
                    <td>
                        <?php
                        $update_frequency = isset($options['update_frequency']) ? $options['update_frequency'] : 'weekly';
                        ?>
                        <select id="update_frequency" name="eia_fuel_surcharge_settings[update_frequency]">
                            <option value="daily" <?php selected($update_frequency, 'daily'); ?>><?php _e('Daily', 'eia-fuel-surcharge'); ?></option>
                            <option value="weekly" <?php selected($update_frequency, 'weekly'); ?>><?php _e('Weekly', 'eia-fuel-surcharge'); ?></option>
                            <option value="monthly" <?php selected($update_frequency, 'monthly'); ?>><?php _e('Monthly', 'eia-fuel-surcharge'); ?></option>
                            <option value="custom" <?php selected($update_frequency, 'custom'); ?>><?php _e('Custom Interval (days)', 'eia-fuel-surcharge'); ?></option>
                        </select>
                        <p class="description"><?php _e('How often to retrieve fresh data from the EIA API.', 'eia-fuel-surcharge'); ?></p>
                        <p class="description"><?php _e('Note: EIA typically updates diesel price data weekly on Mondays.', 'eia-fuel-surcharge'); ?></p>
                    </td>
                </tr>
                <tr class="update-day-field">
                    <th scope="row">
                        <label for="update_day"><?php _e('Update Day', 'eia-fuel-surcharge'); ?></label>
                    </th>
                    <td>
                        <?php
                        $update_day = isset($options['update_day']) ? $options['update_day'] : 'tuesday';
                        ?>
                        <select id="update_day" name="eia_fuel_surcharge_settings[update_day]">
                            <option value="monday" <?php selected($update_day, 'monday'); ?>><?php _e('Monday', 'eia-fuel-surcharge'); ?></option>
                            <option value="tuesday" <?php selected($update_day, 'tuesday'); ?>><?php _e('Tuesday', 'eia-fuel-surcharge'); ?></option>
                            <option value="wednesday" <?php selected($update_day, 'wednesday'); ?>><?php _e('Wednesday', 'eia-fuel-surcharge'); ?></option>
                            <option value="thursday" <?php selected($update_day, 'thursday'); ?>><?php _e('Thursday', 'eia-fuel-surcharge'); ?></option>
                            <option value="friday" <?php selected($update_day, 'friday'); ?>><?php _e('Friday', 'eia-fuel-surcharge'); ?></option>
                            <option value="saturday" <?php selected($update_day, 'saturday'); ?>><?php _e('Saturday', 'eia-fuel-surcharge'); ?></option>
                            <option value="sunday" <?php selected($update_day, 'sunday'); ?>><?php _e('Sunday', 'eia-fuel-surcharge'); ?></option>
                        </select>
                        <p class="description"><?php _e('The day of the week to update (for weekly schedule).', 'eia-fuel-surcharge'); ?></p>
                    </td>
                </tr>
                <tr class="update-day-of-month-field" style="display: none;">
                    <th scope="row">
                        <label for="update_day_of_month"><?php _e('Day of Month', 'eia-fuel-surcharge'); ?></label>
                    </th>
                    <td>
                        <?php
                        $update_day_of_month = isset($options['update_day_of_month']) ? intval($options['update_day_of_month']) : 1;
                        ?>
                        <select id="update_day_of_month" name="eia_fuel_surcharge_settings[update_day_of_month]">
                            <?php for ($i = 1; $i <= 31; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php selected($update_day_of_month, $i); ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                            <option value="last" <?php selected($update_day_of_month, 'last'); ?>><?php _e('Last day', 'eia-fuel-surcharge'); ?></option>
                        </select>
                        <p class="description"><?php _e('The day of the month to update (for monthly schedule).', 'eia-fuel-surcharge'); ?></p>
                    </td>
                </tr>
                <tr class="custom-interval-field" style="display: none;">
                    <th scope="row">
                        <label for="custom_interval"><?php _e('Custom Interval (days)', 'eia-fuel-surcharge'); ?></label>
                    </th>
                    <td>
                        <?php
                        $custom_interval = isset($options['custom_interval']) ? intval($options['custom_interval']) : 7;
                        ?>
                        <input type="number" id="custom_interval" name="eia_fuel_surcharge_settings[custom_interval]" value="<?php echo esc_attr($custom_interval); ?>" min="1" max="90" class="small-text" />
                        <p class="description"><?php _e('Number of days between updates (for custom schedule).', 'eia-fuel-surcharge'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="update_time"><?php _e('Update Time', 'eia-fuel-surcharge'); ?></label>
                    </th>
                    <td>
                        <?php
                        $update_time = isset($options['update_time']) ? $options['update_time'] : '12:00';
                        ?>
                        <input type="time" id="update_time" name="eia_fuel_surcharge_settings[update_time]" value="<?php echo esc_attr($update_time); ?>" />
                        <p class="description"><?php _e('The time of day to update (24-hour format).', 'eia-fuel-surcharge'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Next Scheduled Update', 'eia-fuel-surcharge'); ?></th>
                    <td>
                        <?php
                        $scheduler = new EIAFuelSurcharge\Core\Scheduler();
                        $next_update = $scheduler->get_next_scheduled_update();
                        
                        if ($next_update) {
                            echo '<strong>' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_update) . '</strong>';
                            echo ' (' . human_time_diff(time(), $next_update) . ' ' . __('from now', 'eia-fuel-surcharge') . ')';
                        } else {
                            echo '<em>' . __('Not scheduled', 'eia-fuel-surcharge') . '</em>';
                        }
                        ?>
                        <p class="description">
                            <?php _e('Note: Changes to scheduling settings will take effect after saving.', 'eia-fuel-surcharge'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Manual Update', 'eia-fuel-surcharge'); ?></th>
                    <td>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-bottom: 0;">
                            <input type="hidden" name="action" value="eia_fuel_surcharge_manual_update">
                            <?php wp_nonce_field('eia_fuel_surcharge_manual_update', 'eia_fuel_surcharge_nonce'); ?>
                            <button type="submit" class="button"><?php _e('Update Now', 'eia-fuel-surcharge'); ?></button>
                            <span class="description"><?php _e('Manually trigger an update from the EIA API.', 'eia-fuel-surcharge'); ?></span>
                        </form>
                    </td>
                </tr>
            </table>
        </div>
<!-- Display Settings Tab -->
        <div id="display" class="eia-fuel-surcharge-tab-content">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="date_format"><?php _e('Date Format', 'eia-fuel-surcharge'); ?></label>
                    </th>
                    <td>
                        <?php
                        $date_format = isset($options['date_format']) ? $options['date_format'] : 'm/d/Y';
                        ?>
                        <input type="text" id="date_format" name="eia_fuel_surcharge_settings[date_format]" value="<?php echo esc_attr($date_format); ?>" class="regular-text" />
                        <div id="date-format-preview" style="margin-top: 5px;">
                            <?php echo date_i18n($date_format); ?>
                        </div>
                        <p class="description">
                            <?php _e('PHP date format for displaying dates. See', 'eia-fuel-surcharge'); ?> 
                            <a href="https://www.php.net/manual/en/datetime.format.php" target="_blank"><?php _e('PHP Date Format', 'eia-fuel-surcharge'); ?></a> 
                            <?php _e('for options.', 'eia-fuel-surcharge'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="decimal_places"><?php _e('Decimal Places', 'eia-fuel-surcharge'); ?></label>
                    </th>
                    <td>
                        <?php
                        $decimal_places = isset($options['decimal_places']) ? intval($options['decimal_places']) : 2;
                        ?>
                        <input type="number" min="0" max="4" id="decimal_places" name="eia_fuel_surcharge_settings[decimal_places]" value="<?php echo esc_attr($decimal_places); ?>" class="small-text" />
                        <p class="description"><?php _e('Number of decimal places to display in rates.', 'eia-fuel-surcharge'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="text_format"><?php _e('Text Format', 'eia-fuel-surcharge'); ?></label>
                    </th>
                    <td>
                        <?php
                        $text_format = isset($options['text_format']) ? $options['text_format'] : 'Currently as of {date} the fuel surcharge is {rate}%';
                        ?>
                        <input type="text" id="text_format" name="eia_fuel_surcharge_settings[text_format]" value="<?php echo esc_attr($text_format); ?>" class="large-text" />
                        <div id="text-format-preview" style="margin-top: 5px;">
                            <?php
                            $preview_text = str_replace(
                                ['{rate}', '{date}', '{price}'],
                                [
                                    number_format(23.5, $decimal_places),
                                    date_i18n($date_format),
                                    '$' . number_format(4.789, 3)
                                ],
                                $text_format
                            );
                            echo $preview_text;
                            ?>
                        </div>
                        <p class="description">
                            <?php _e('Text format for displaying the fuel surcharge. Use {date} for the date, {rate} for the surcharge rate, and {price} for the diesel price.', 'eia-fuel-surcharge'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="table_rows"><?php _e('Default Table Rows', 'eia-fuel-surcharge'); ?></label>
                    </th>
                    <td>
                        <?php
                        $table_rows = isset($options['table_rows']) ? intval($options['table_rows']) : 10;
                        ?>
                        <input type="number" min="1" max="100" id="table_rows" name="eia_fuel_surcharge_settings[table_rows]" value="<?php echo esc_attr($table_rows); ?>" class="small-text" />
                        <p class="description"><?php _e('Default number of rows to display in the fuel surcharge table.', 'eia-fuel-surcharge'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="show_comparison"><?php _e('Show Comparison', 'eia-fuel-surcharge'); ?></label>
                    </th>
                    <td>
                        <?php
                        $show_comparison = isset($options['show_comparison']) ? $options['show_comparison'] : 'true';
                        ?>
                        <label for="show_comparison">
                            <input type="checkbox" id="show_comparison" name="eia_fuel_surcharge_settings[show_comparison]" value="true" <?php checked($show_comparison, 'true'); ?> />
                            <?php _e('Show comparison with previous period by default.', 'eia-fuel-surcharge'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="default_comparison"><?php _e('Default Comparison Period', 'eia-fuel-surcharge'); ?></label>
                    </th>
                    <td>
                        <?php
                        $default_comparison = isset($options['default_comparison']) ? $options['default_comparison'] : 'week';
                        ?>
                        <select id="default_comparison" name="eia_fuel_surcharge_settings[default_comparison]">
                            <option value="day" <?php selected($default_comparison, 'day'); ?>><?php _e('Previous Day', 'eia-fuel-surcharge'); ?></option>
                            <option value="week" <?php selected($default_comparison, 'week'); ?>><?php _e('Previous Week', 'eia-fuel-surcharge'); ?></option>
                            <option value="month" <?php selected($default_comparison, 'month'); ?>><?php _e('Previous Month', 'eia-fuel-surcharge'); ?></option>
                            <option value="year" <?php selected($default_comparison, 'year'); ?>><?php _e('Previous Year', 'eia-fuel-surcharge'); ?></option>
                        </select>
                        <p class="description"><?php _e('The default period to compare current rates with.', 'eia-fuel-surcharge'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
<!-- Shortcodes Tab -->
        <div id="shortcodes" class="eia-fuel-surcharge-tab-content">
            <h2><?php _e('Shortcode Examples', 'eia-fuel-surcharge'); ?></h2>
            
            <div class="eia-fuel-surcharge-shortcode-examples">
                <div class="shortcode-example">
                    <h3><?php _e('Basic Usage', 'eia-fuel-surcharge'); ?></h3>
                    <code>[fuel_surcharge]</code>
                    <p class="description"><?php _e('Displays the current fuel surcharge rate using default settings.', 'eia-fuel-surcharge'); ?></p>
                </div>
                
                <div class="shortcode-example">
                    <h3><?php _e('Customized Display', 'eia-fuel-surcharge'); ?></h3>
                    <code>[fuel_surcharge format="Fuel Surcharge: {rate}% (as of {date})" date_format="F j, Y" decimals="1"]</code>
                    <p class="description"><?php _e('Customizes the display format, date format, and decimal places.', 'eia-fuel-surcharge'); ?></p>
                </div>
                
                <div class="shortcode-example">
                    <h3><?php _e('Table Display', 'eia-fuel-surcharge'); ?></h3>
                    <code>[fuel_surcharge_table rows="5" date_format="m/d/Y" class="striped"]</code>
                    <p class="description"><?php _e('Displays a table of the 5 most recent fuel surcharge rates.', 'eia-fuel-surcharge'); ?></p>
                </div>
                
                <div class="shortcode-example">
                    <h3><?php _e('Customized Table', 'eia-fuel-surcharge'); ?></h3>
                    <code>[fuel_surcharge_table rows="10" columns="date,rate" order="asc" title="Historical Fuel Surcharge Rates" show_footer="true"]</code>
                    <p class="description"><?php _e('Displays a customized table with specific columns, ordering, title, and formula in the footer.', 'eia-fuel-surcharge'); ?></p>
                </div>
                
                <div class="shortcode-example">
                    <h3><?php _e('Regional Display', 'eia-fuel-surcharge'); ?></h3>
                    <code>[fuel_surcharge region="east_coast"]</code>
                    <p class="description"><?php _e('Displays the fuel surcharge rate for a specific region.', 'eia-fuel-surcharge'); ?></p>
                </div>
            </div>
            
            <h3><?php _e('Shortcode Parameters', 'eia-fuel-surcharge'); ?></h3>
            
            <div class="eia-fuel-surcharge-parameters">
                <h4><?php _e('[fuel_surcharge] Parameters', 'eia-fuel-surcharge'); ?></h4>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Parameter', 'eia-fuel-surcharge'); ?></th>
                            <th><?php _e('Description', 'eia-fuel-surcharge'); ?></th>
                            <th><?php _e('Default', 'eia-fuel-surcharge'); ?></th>
                            <th><?php _e('Example', 'eia-fuel-surcharge'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>format</code></td>
                            <td><?php _e('Custom text format with placeholders {date}, {rate}, and {price}', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>format="Fuel Surcharge: {rate}%"</code></td>
                        </tr>
                        <tr>
                            <td><code>date_format</code></td>
                            <td><?php _e('PHP date format for displaying the date', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>date_format="F j, Y"</code></td>
                        </tr>
                        <tr>
                            <td><code>decimals</code></td>
                            <td><?php _e('Number of decimal places for the rate', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>decimals="1"</code></td>
                        </tr>
                        <tr>
                            <td><code>class</code></td>
                            <td><?php _e('CSS class for styling', 'eia-fuel-surcharge'); ?></td>
                            <td><code>fuel-surcharge</code></td>
                            <td><code>class="highlight-box"</code></td>
                        </tr>
                        <tr>
                            <td><code>region</code></td>
                            <td><?php _e('Region for the fuel price data', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>region="east_coast"</code></td>
                        </tr>
                        <tr>
                            <td><code>compare</code></td>
                            <td><?php _e('Comparison period (day, week, month, year)', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>compare="month"</code></td>
                        </tr>
                        <tr>
                            <td><code>show_comparison</code></td>
                            <td><?php _e('Whether to show comparison data', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>show_comparison="false"</code></td>
                        </tr>
                    </tbody>
                </table>
                
                <h4><?php _e('[fuel_surcharge_table] Parameters', 'eia-fuel-surcharge'); ?></h4>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Parameter', 'eia-fuel-surcharge'); ?></th>
                            <th><?php _e('Description', 'eia-fuel-surcharge'); ?></th>
                            <th><?php _e('Default', 'eia-fuel-surcharge'); ?></th>
                            <th><?php _e('Example', 'eia-fuel-surcharge'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>rows</code></td>
                            <td><?php _e('Number of rows to display', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>rows="5"</code></td>
                        </tr>
                        <tr>
                            <td><code>date_format</code></td>
                            <td><?php _e('PHP date format for displaying dates', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>date_format="F j, Y"</code></td>
                        </tr>
                        <tr>
                            <td><code>columns</code></td>
                            <td><?php _e('Columns to display (date,price,rate,region)', 'eia-fuel-surcharge'); ?></td>
                            <td><code>date,price,rate</code></td>
                            <td><code>columns="date,rate"</code></td>
                        </tr>
                        <tr>
                            <td><code>order</code></td>
                            <td><?php _e('Sort order (asc or desc)', 'eia-fuel-surcharge'); ?></td>
                            <td><code>desc</code></td>
                            <td><code>order="asc"</code></td>
                        </tr>
                        <tr>
                            <td><code>class</code></td>
                            <td><?php _e('CSS class for the table', 'eia-fuel-surcharge'); ?></td>
                            <td><code>fuel-surcharge-table</code></td>
                            <td><code>class="striped"</code></td>
                        </tr>
                        <tr>
                            <td><code>region</code></td>
                            <td><?php _e('Region for the fuel price data', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>region="midwest"</code></td>
                        </tr>
                        <tr>
                            <td><code>title</code></td>
                            <td><?php _e('Title to display above the table', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('None', 'eia-fuel-surcharge'); ?></td>
                            <td><code>title="Historical Rates"</code></td>
                        </tr>
                        <tr>
                            <td><code>show_footer</code></td>
                            <td><?php _e('Whether to show formula in footer', 'eia-fuel-surcharge'); ?></td>
                            <td><code>false</code></td>
                            <td><code>show_footer="true"</code></td>
                        </tr>
                        <tr>
                            <td><code>decimals</code></td>
                            <td><?php _e('Number of decimal places for rates', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>decimals="1"</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

<?php submit_button(); ?>
    </form>
</div>

<style>
/* Settings Page Styling */
.eia-fuel-surcharge-tab-content {
    display: none;
    padding: 20px 0;
}

.eia-fuel-surcharge-shortcode-examples {
    display: flex;
    flex-wrap: wrap;
    margin: 15px -10px;
}

.shortcode-example {
    flex: 1;
    min-width: 300px;
    margin: 0 10px 20px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 3px;
}

.shortcode-example h3 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 16px;
}

.shortcode-example code {
    display: block;
    padding: 10px;
    margin-bottom: 10px;
    background: #f1f1f1;
    border: 1px solid #ddd;
}

.formula-preview {
    padding: 10px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    margin-bottom: 5px;
}

/* Responsive adjustments */
@media screen and (max-width: 782px) {
    .shortcode-example {
        min-width: 100%;
        margin-bottom: 15px;
    }
}
</style>