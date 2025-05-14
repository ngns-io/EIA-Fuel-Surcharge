<?php
/**
 * Handles settings registration and rendering.
 *
 * @package    EIAFuelSurcharge
 * @subpackage EIAFuelSurcharge\Admin
 */

namespace EIAFuelSurcharge\Admin;

use EIAFuelSurcharge\Core\Scheduler;
use EIAFuelSurcharge\Utilities\Calculator;

class Settings {

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
     * Register all settings.
     *
     * @since    1.0.0
     */
    public function register() {
        // Register a setting
        register_setting(
            'eia_fuel_surcharge_options',  // Option group
            'eia_fuel_surcharge_settings', // Option name in the database
            [$this, 'validate_settings']   // Validation callback
        );

        // API Settings Section
        add_settings_section(
            'eia_fuel_surcharge_api_section',         // Section ID
            __('API Settings', 'eia-fuel-surcharge'), // Section title
            [$this, 'api_section_info'],              // Section callback
            'eia_fuel_surcharge_api'                  // Page slug - matches the tab ID
        );

        // API Key field
        add_settings_field(
            'api_key',                                // Field ID
            __('EIA API Key', 'eia-fuel-surcharge'),  // Field title
            [$this, 'api_key_field_callback'],        // Field callback
            'eia_fuel_surcharge_api',                 // Page slug
            'eia_fuel_surcharge_api_section'          // Section ID
        );
        
        // Region field
        add_settings_field(
            'region',
            __('Default Region', 'eia-fuel-surcharge'),
            [$this, 'region_field_callback'],
            'eia_fuel_surcharge_api',
            'eia_fuel_surcharge_api_section'
        );
        
        // Cache Duration field
        add_settings_field(
            'cache_duration',
            __('Cache Duration (minutes)', 'eia-fuel-surcharge'),
            [$this, 'cache_duration_field_callback'],
            'eia_fuel_surcharge_api',
            'eia_fuel_surcharge_api_section'
        );
        
        // Max Retries field
        add_settings_field(
            'max_retries',
            __('Max API Retries', 'eia-fuel-surcharge'),
            [$this, 'max_retries_field_callback'],
            'eia_fuel_surcharge_api',
            'eia_fuel_surcharge_api_section'
        );
        
        // EIA Source Link field
        add_settings_field(
            'eia_source_link',
            __('Show EIA Source Link', 'eia-fuel-surcharge'),
            [$this, 'eia_source_link_field_callback'],
            'eia_fuel_surcharge_api',
            'eia_fuel_surcharge_api_section'
        );

        // Calculation Settings Section
        add_settings_section(
            'eia_fuel_surcharge_calculation_section',
            __('Calculation Settings', 'eia-fuel-surcharge'),
            [$this, 'calculation_section_info'],
            'eia_fuel_surcharge_calculation'  // Page slug - matches the tab ID
        );
        
        // Base threshold field
        add_settings_field(
            'base_threshold',
            __('Base Price Threshold ($)', 'eia-fuel-surcharge'),
            [$this, 'base_threshold_field_callback'],
            'eia_fuel_surcharge_calculation',
            'eia_fuel_surcharge_calculation_section'
        );
        
        // Increment amount field
        add_settings_field(
            'increment_amount',
            __('Price Increment ($)', 'eia-fuel-surcharge'),
            [$this, 'increment_amount_field_callback'],
            'eia_fuel_surcharge_calculation',
            'eia_fuel_surcharge_calculation_section'
        );
        
        // Percentage rate field
        add_settings_field(
            'percentage_rate',
            __('Percentage Rate (%)', 'eia-fuel-surcharge'),
            [$this, 'percentage_rate_field_callback'],
            'eia_fuel_surcharge_calculation',
            'eia_fuel_surcharge_calculation_section'
        );
        
        // Current Formula field
        add_settings_field(
            'current_formula',
            __('Current Formula', 'eia-fuel-surcharge'),
            [$this, 'current_formula_field_callback'],
            'eia_fuel_surcharge_calculation',
            'eia_fuel_surcharge_calculation_section'
        );
        
        // Schedule Settings Section
        add_settings_section(
            'eia_fuel_surcharge_schedule_section',
            __('Schedule Settings', 'eia-fuel-surcharge'),
            [$this, 'schedule_section_info'],
            'eia_fuel_surcharge_schedule'  // Page slug - matches the tab ID
        );
        
        // Update frequency field
        add_settings_field(
            'update_frequency',
            __('Update Frequency', 'eia-fuel-surcharge'),
            [$this, 'update_frequency_field_callback'],
            'eia_fuel_surcharge_schedule',
            'eia_fuel_surcharge_schedule_section'
        );
        
        // Update day field (weekly)
        add_settings_field(
            'update_day',
            __('Update Day', 'eia-fuel-surcharge'),
            [$this, 'update_day_field_callback'],
            'eia_fuel_surcharge_schedule',
            'eia_fuel_surcharge_schedule_section'
        );
        
        // Update day of month field (monthly)
        add_settings_field(
            'update_day_of_month',
            __('Day of Month', 'eia-fuel-surcharge'),
            [$this, 'update_day_of_month_field_callback'],
            'eia_fuel_surcharge_schedule',
            'eia_fuel_surcharge_schedule_section'
        );
        
        // Custom interval field
        add_settings_field(
            'custom_interval',
            __('Custom Interval (days)', 'eia-fuel-surcharge'),
            [$this, 'custom_interval_field_callback'],
            'eia_fuel_surcharge_schedule',
            'eia_fuel_surcharge_schedule_section'
        );
        
        // Update time field
        add_settings_field(
            'update_time',
            __('Update Time', 'eia-fuel-surcharge'),
            [$this, 'update_time_field_callback'],
            'eia_fuel_surcharge_schedule',
            'eia_fuel_surcharge_schedule_section'
        );

        // Display Settings Section
        add_settings_section(
            'eia_fuel_surcharge_display_section',
            __('Display Settings', 'eia-fuel-surcharge'),
            [$this, 'display_section_info'],
            'eia_fuel_surcharge_display'  // Page slug - matches the tab ID
        );
        
        // Date format field
        add_settings_field(
            'date_format',
            __('Date Format', 'eia-fuel-surcharge'),
            [$this, 'date_format_field_callback'],
            'eia_fuel_surcharge_display',
            'eia_fuel_surcharge_display_section'
        );
        
        // Decimal places field
        add_settings_field(
            'decimal_places',
            __('Decimal Places', 'eia-fuel-surcharge'),
            [$this, 'decimal_places_field_callback'],
            'eia_fuel_surcharge_display',
            'eia_fuel_surcharge_display_section'
        );
        
        // Text format field
        add_settings_field(
            'text_format',
            __('Text Format', 'eia-fuel-surcharge'),
            [$this, 'text_format_field_callback'],
            'eia_fuel_surcharge_display',
            'eia_fuel_surcharge_display_section'
        );
        
        // Table rows field
        add_settings_field(
            'table_rows',
            __('Default Table Rows', 'eia-fuel-surcharge'),
            [$this, 'table_rows_field_callback'],
            'eia_fuel_surcharge_display',
            'eia_fuel_surcharge_display_section'
        );
        
        // Show comparison field
        add_settings_field(
            'show_comparison',
            __('Show Comparison', 'eia-fuel-surcharge'),
            [$this, 'show_comparison_field_callback'],
            'eia_fuel_surcharge_display',
            'eia_fuel_surcharge_display_section'
        );
        
        // Default comparison period field
        add_settings_field(
            'default_comparison',
            __('Default Comparison Period', 'eia-fuel-surcharge'),
            [$this, 'default_comparison_field_callback'],
            'eia_fuel_surcharge_display',
            'eia_fuel_surcharge_display_section'
        );
    }
    
    /**
     * Validate settings.
     *
     * @since    1.0.0
     * @param    array    $input    The settings input.
     * @return   array    The validated settings.
     */
    public function validate_settings($input) {
        // For debugging
        error_log('Validate settings called: ' . print_r($input, true));
        
        $validated = [];
        
        // API Settings
        $validated['api_key'] = isset($input['api_key']) ? sanitize_text_field($input['api_key']) : '';
        $validated['region'] = isset($input['region']) ? sanitize_text_field($input['region']) : 'national';
        $validated['cache_duration'] = isset($input['cache_duration']) ? intval($input['cache_duration']) : 60;
        $validated['max_retries'] = isset($input['max_retries']) ? intval($input['max_retries']) : 3;
        $validated['eia_source_link'] = isset($input['eia_source_link']) ? 'true' : 'false';
        
        // Calculation settings
        $validated['base_threshold'] = isset($input['base_threshold']) ? floatval($input['base_threshold']) : 1.20;
        $validated['increment_amount'] = isset($input['increment_amount']) ? floatval($input['increment_amount']) : 0.06;
        $validated['percentage_rate'] = isset($input['percentage_rate']) ? floatval($input['percentage_rate']) : 0.5;
        
        // Schedule settings
        $validated['update_frequency'] = isset($input['update_frequency']) ? sanitize_text_field($input['update_frequency']) : 'weekly';
        $validated['update_day'] = isset($input['update_day']) ? sanitize_text_field($input['update_day']) : 'tuesday';
        $validated['update_day_of_month'] = isset($input['update_day_of_month']) ? sanitize_text_field($input['update_day_of_month']) : '1';
        $validated['custom_interval'] = isset($input['custom_interval']) ? intval($input['custom_interval']) : 7;
        $validated['update_time'] = isset($input['update_time']) ? sanitize_text_field($input['update_time']) : '12:00';
        
        // Display settings
        $validated['date_format'] = isset($input['date_format']) ? sanitize_text_field($input['date_format']) : 'm/d/Y';
        $validated['decimal_places'] = isset($input['decimal_places']) ? intval($input['decimal_places']) : 2;
        $validated['text_format'] = isset($input['text_format']) ? sanitize_text_field($input['text_format']) : 'Currently as of {date} the fuel surcharge is {rate}%';
        $validated['table_rows'] = isset($input['table_rows']) ? intval($input['table_rows']) : 10;
        $validated['show_comparison'] = isset($input['show_comparison']) ? 'true' : 'false';
        $validated['default_comparison'] = isset($input['default_comparison']) ? sanitize_text_field($input['default_comparison']) : 'week';
        
        // Get the existing settings
        $existing_settings = get_option('eia_fuel_surcharge_settings', []);
        
        // If schedule settings have changed, update the scheduled event
        if (empty($existing_settings) || 
            $existing_settings['update_frequency'] !== $validated['update_frequency'] || 
            $existing_settings['update_day'] !== $validated['update_day'] ||
            $existing_settings['update_time'] !== $validated['update_time']) {
            
            // Update the schedule
            $scheduler = new Scheduler();
            $scheduler->schedule_update();
        }
        
        return $validated;
    }

    // Section info callbacks
    public function api_section_info() {
        echo '<p>' . __('Enter your EIA API Key below. If you don\'t have an API key, you can get one for free at <a href="https://www.eia.gov/opendata/" target="_blank">EIA Open Data</a>.', 'eia-fuel-surcharge') . '</p>';
    }
    
    public function calculation_section_info() {
        echo '<p>' . __('Configure how fuel surcharges are calculated based on diesel prices.', 'eia-fuel-surcharge') . '</p>';
    }
    
    public function schedule_section_info() {
        echo '<p>' . __('Configure when to retrieve new fuel price data from the EIA API.', 'eia-fuel-surcharge') . '</p>';
    }
    
    public function display_section_info() {
        echo '<p>' . __('Configure how fuel surcharge data is displayed on your site.', 'eia-fuel-surcharge') . '</p>';
    }
    
    // Field callbacks - these render the actual form fields
    public function api_key_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';
        
        echo '<input type="text" id="api_key" name="eia_fuel_surcharge_settings[api_key]" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<button type="button" id="test-api-key" class="button button-secondary">' . __('Test API Key', 'eia-fuel-surcharge') . '</button>';
        echo '<p class="description">' . __('Enter your EIA API key.', 'eia-fuel-surcharge') . '</p>';
    }
    
    public function region_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $region = isset($options['region']) ? $options['region'] : 'national';
        
        echo '<select id="region" name="eia_fuel_surcharge_settings[region]">';
        echo '<option value="national" ' . selected($region, 'national', false) . '>' . __('National Average', 'eia-fuel-surcharge') . '</option>';
        echo '<option value="east_coast" ' . selected($region, 'east_coast', false) . '>' . __('East Coast', 'eia-fuel-surcharge') . '</option>';
        echo '<option value="new_england" ' . selected($region, 'new_england', false) . '>' . __('New England', 'eia-fuel-surcharge') . '</option>';
        echo '<option value="central_atlantic" ' . selected($region, 'central_atlantic', false) . '>' . __('Central Atlantic', 'eia-fuel-surcharge') . '</option>';
        echo '<option value="lower_atlantic" ' . selected($region, 'lower_atlantic', false) . '>' . __('Lower Atlantic', 'eia-fuel-surcharge') . '</option>';
        echo '<option value="midwest" ' . selected($region, 'midwest', false) . '>' . __('Midwest', 'eia-fuel-surcharge') . '</option>';
        echo '<option value="gulf_coast" ' . selected($region, 'gulf_coast', false) . '>' . __('Gulf Coast', 'eia-fuel-surcharge') . '</option>';
        echo '<option value="rocky_mountain" ' . selected($region, 'rocky_mountain', false) . '>' . __('Rocky Mountain', 'eia-fuel-surcharge') . '</option>';
        echo '<option value="west_coast" ' . selected($region, 'west_coast', false) . '>' . __('West Coast', 'eia-fuel-surcharge') . '</option>';
        echo '<option value="california" ' . selected($region, 'california', false) . '>' . __('California', 'eia-fuel-surcharge') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('Select the default region for fuel price data.', 'eia-fuel-surcharge') . '</p>';
    }
    
    public function cache_duration_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $cache_duration = isset($options['cache_duration']) ? intval($options['cache_duration']) : 60;
        
        echo '<input type="number" id="cache_duration" name="eia_fuel_surcharge_settings[cache_duration]" value="' . esc_attr($cache_duration) . '" min="5" max="1440" step="5" class="small-text" />';
        echo '<p class="description">' . __('How long to cache API responses (in minutes). Minimum 5 minutes, maximum 24 hours (1440 minutes).', 'eia-fuel-surcharge') . '</p>';
    }
    
    public function max_retries_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $max_retries = isset($options['max_retries']) ? intval($options['max_retries']) : 3;
        
        echo '<input type="number" id="max_retries" name="eia_fuel_surcharge_settings[max_retries]" value="' . esc_attr($max_retries) . '" min="1" max="5" class="small-text" />';
        echo '<p class="description">' . __('Maximum number of retry attempts for API calls in case of failure.', 'eia-fuel-surcharge') . '</p>';
    }
    
    public function eia_source_link_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $eia_source_link = isset($options['eia_source_link']) ? $options['eia_source_link'] : 'true';
        
        echo '<label for="eia_source_link">';
        echo '<input type="checkbox" id="eia_source_link" name="eia_fuel_surcharge_settings[eia_source_link]" value="true" ' . checked($eia_source_link, 'true', false) . ' />';
        echo __('Display a link to the EIA website as the data source.', 'eia-fuel-surcharge');
        echo '</label>';
    }

    public function base_threshold_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $base_threshold = isset($options['base_threshold']) ? floatval($options['base_threshold']) : 1.20;
        
        echo '<input type="number" step="0.01" id="base_threshold" name="eia_fuel_surcharge_settings[base_threshold]" value="' . esc_attr($base_threshold) . '" class="small-text" />';
        echo '<p class="description">' . __('The base diesel price threshold (in dollars) at which the surcharge begins.', 'eia-fuel-surcharge') . '</p>';
    }
    
    public function increment_amount_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $increment_amount = isset($options['increment_amount']) ? floatval($options['increment_amount']) : 0.06;
        
        echo '<input type="number" step="0.01" id="increment_amount" name="eia_fuel_surcharge_settings[increment_amount]" value="' . esc_attr($increment_amount) . '" class="small-text" />';
        echo '<p class="description">' . __('The price increment (in dollars) that triggers an increase in the surcharge percentage.', 'eia-fuel-surcharge') . '</p>';
    }
    
    public function percentage_rate_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $percentage_rate = isset($options['percentage_rate']) ? floatval($options['percentage_rate']) : 0.5;
        
        echo '<input type="number" step="0.1" id="percentage_rate" name="eia_fuel_surcharge_settings[percentage_rate]" value="' . esc_attr($percentage_rate) . '" class="small-text" />';
        echo '<p class="description">' . __('The percentage increase in surcharge for each price increment.', 'eia-fuel-surcharge') . '</p>';
    }
    
    public function current_formula_field_callback() {
        $calculator = new Calculator();
        
        echo '<div class="formula-preview">';
        echo '<code>' . $calculator->get_formula_description() . '</code>';
        echo '</div>';
        echo '<p class="description">';
        echo __('Formula: Surcharge = ((Diesel Price - Base Threshold) / Increment Amount) * Percentage Rate', 'eia-fuel-surcharge');
        echo '</p>';
    }
    
    public function update_frequency_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $update_frequency = isset($options['update_frequency']) ? $options['update_frequency'] : 'weekly';
        
        $frequencies = [
            'daily'     => __('Daily', 'eia-fuel-surcharge'),
            'weekly'    => __('Weekly', 'eia-fuel-surcharge'),
            'monthly'   => __('Monthly', 'eia-fuel-surcharge'),
            'custom'    => __('Custom Interval (days)', 'eia-fuel-surcharge')
        ];
        
        echo '<select id="update_frequency" name="eia_fuel_surcharge_settings[update_frequency]">';
        foreach ($frequencies as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($update_frequency, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('How often to retrieve fresh data from the EIA API.', 'eia-fuel-surcharge') . '</p>';
        echo '<p class="description">' . __('Note: EIA typically updates diesel price data weekly on Mondays.', 'eia-fuel-surcharge') . '</p>';
    }
    
    public function update_day_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $update_day = isset($options['update_day']) ? $options['update_day'] : 'tuesday';
        
        $days = [
            'monday'    => __('Monday', 'eia-fuel-surcharge'),
            'tuesday'   => __('Tuesday', 'eia-fuel-surcharge'),
            'wednesday' => __('Wednesday', 'eia-fuel-surcharge'),
            'thursday'  => __('Thursday', 'eia-fuel-surcharge'),
            'friday'    => __('Friday', 'eia-fuel-surcharge'),
            'saturday'  => __('Saturday', 'eia-fuel-surcharge'),
            'sunday'    => __('Sunday', 'eia-fuel-surcharge')
        ];
        
        echo '<select id="update_day" name="eia_fuel_surcharge_settings[update_day]" class="update-day-field">';
        foreach ($days as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($update_day, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('The day of the week to update (for weekly schedule).', 'eia-fuel-surcharge') . '</p>';
    }

    public function update_day_of_month_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $update_day_of_month = isset($options['update_day_of_month']) ? $options['update_day_of_month'] : 1;
        
        echo '<select id="update_day_of_month" name="eia_fuel_surcharge_settings[update_day_of_month]" class="update-day-of-month-field">';
        for ($i = 1; $i <= 31; $i++) {
            echo '<option value="' . $i . '" ' . selected($update_day_of_month, $i, false) . '>' . $i . '</option>';
        }
        echo '<option value="last" ' . selected($update_day_of_month, 'last', false) . '>' . __('Last day', 'eia-fuel-surcharge') . '</option>';
        echo '</select>';
        echo '<p class="description">' . __('The day of the month to update (for monthly schedule).', 'eia-fuel-surcharge') . '</p>';
    }

    public function custom_interval_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $custom_interval = isset($options['custom_interval']) ? intval($options['custom_interval']) : 7;
        
        echo '<input type="number" id="custom_interval" name="eia_fuel_surcharge_settings[custom_interval]" value="' . esc_attr($custom_interval) . '" min="1" max="90" class="small-text custom-interval-field" />';
        echo '<p class="description">' . __('Number of days between updates (for custom schedule).', 'eia-fuel-surcharge') . '</p>';
    }

    public function update_time_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $update_time = isset($options['update_time']) ? $options['update_time'] : '12:00';
        
        echo '<input type="time" id="update_time" name="eia_fuel_surcharge_settings[update_time]" value="' . esc_attr($update_time) . '" />';
        echo '<p class="description">' . __('The time of day to update (24-hour format).', 'eia-fuel-surcharge') . '</p>';
    }

    public function date_format_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $date_format = isset($options['date_format']) ? $options['date_format'] : 'm/d/Y';
        
        echo '<input type="text" id="date_format" name="eia_fuel_surcharge_settings[date_format]" value="' . esc_attr($date_format) . '" class="regular-text" />';
        echo '<p class="description">' . __('PHP date format for displaying dates. See <a href="https://www.php.net/manual/en/datetime.format.php" target="_blank">PHP Date Format</a> for options.', 'eia-fuel-surcharge') . '</p>';
    }
    
    public function decimal_places_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $decimal_places = isset($options['decimal_places']) ? intval($options['decimal_places']) : 2;
        
        echo '<input type="number" min="0" max="4" id="decimal_places" name="eia_fuel_surcharge_settings[decimal_places]" value="' . esc_attr($decimal_places) . '" class="small-text" />';
        echo '<p class="description">' . __('Number of decimal places to display in rates.', 'eia-fuel-surcharge') . '</p>';
    }
    
    public function text_format_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $text_format = isset($options['text_format']) ? $options['text_format'] : 'Currently as of {date} the fuel surcharge is {rate}%';
        
        echo '<input type="text" id="text_format" name="eia_fuel_surcharge_settings[text_format]" value="' . esc_attr($text_format) . '" class="large-text" />';
        
        // Preview with sample data
        $decimal_places = isset($options['decimal_places']) ? intval($options['decimal_places']) : 2;
        $date_format = isset($options['date_format']) ? $options['date_format'] : 'm/d/Y';
        $preview_text = str_replace(
            ['{rate}', '{date}', '{price}'],
            [
                number_format(23.5, $decimal_places),
                date_i18n($date_format),
                '$' . number_format(4.789, 3)
            ],
            $text_format
        );
        
        echo '<div id="text-format-preview" style="margin-top: 5px;">' . $preview_text . '</div>';
        echo '<p class="description">' . __('Text format for displaying the fuel surcharge. Use {date} for the date, {rate} for the surcharge rate, and {price} for the diesel price.', 'eia-fuel-surcharge') . '</p>';
    }
    
    public function table_rows_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $table_rows = isset($options['table_rows']) ? intval($options['table_rows']) : 10;
        
        echo '<input type="number" min="1" max="100" id="table_rows" name="eia_fuel_surcharge_settings[table_rows]" value="' . esc_attr($table_rows) . '" class="small-text" />';
        echo '<p class="description">' . __('Default number of rows to display in the fuel surcharge table.', 'eia-fuel-surcharge') . '</p>';
    }
    
    public function show_comparison_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $show_comparison = isset($options['show_comparison']) ? $options['show_comparison'] : 'true';
        
        echo '<label for="show_comparison">';
        echo '<input type="checkbox" id="show_comparison" name="eia_fuel_surcharge_settings[show_comparison]" value="true" ' . checked($show_comparison, 'true', false) . ' />';
        echo __('Show comparison with previous period by default.', 'eia-fuel-surcharge');
        echo '</label>';
    }
    
    public function default_comparison_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $default_comparison = isset($options['default_comparison']) ? $options['default_comparison'] : 'week';
        
        $comparison_periods = [
            'day'   => __('Previous Day', 'eia-fuel-surcharge'),
            'week'  => __('Previous Week', 'eia-fuel-surcharge'),
            'month' => __('Previous Month', 'eia-fuel-surcharge'),
            'year'  => __('Previous Year', 'eia-fuel-surcharge')
        ];
        
        echo '<select id="default_comparison" name="eia_fuel_surcharge_settings[default_comparison]">';
        foreach ($comparison_periods as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($default_comparison, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('The default period to compare current rates with.', 'eia-fuel-surcharge') . '</p>';
    }
}