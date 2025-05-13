<?php
/**
 * Handles settings registration and rendering.
 *
 * @package    EIAFuelSurcharge
 * @subpackage EIAFuelSurcharge\Admin
 */

namespace EIAFuelSurcharge\Admin;

use EIAFuelSurcharge\Core\Scheduler;

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
            'eia_fuel_surcharge_options',
            'eia_fuel_surcharge_settings',
            [$this, 'validate_settings']
        );

        // API Settings Section
        add_settings_section(
            'eia_fuel_surcharge_api_section',
            __('API Settings', 'eia-fuel-surcharge'),
            [$this, 'api_section_info'],
            'eia_fuel_surcharge_settings'
        );

        // API Key field
        add_settings_field(
            'api_key',
            __('EIA API Key', 'eia-fuel-surcharge'),
            [$this, 'api_key_field_callback'],
            'eia_fuel_surcharge_settings',
            'eia_fuel_surcharge_api_section'
        );
        
        // Calculation Settings Section
        add_settings_section(
            'eia_fuel_surcharge_calculation_section',
            __('Calculation Settings', 'eia-fuel-surcharge'),
            [$this, 'calculation_section_info'],
            'eia_fuel_surcharge_settings'
        );
        
        // Base threshold field
        add_settings_field(
            'base_threshold',
            __('Base Price Threshold ($)', 'eia-fuel-surcharge'),
            [$this, 'base_threshold_field_callback'],
            'eia_fuel_surcharge_settings',
            'eia_fuel_surcharge_calculation_section'
        );
        
        // Increment amount field
        add_settings_field(
            'increment_amount',
            __('Price Increment ($)', 'eia-fuel-surcharge'),
            [$this, 'increment_amount_field_callback'],
            'eia_fuel_surcharge_settings',
            'eia_fuel_surcharge_calculation_section'
        );
        
        // Percentage rate field
        add_settings_field(
            'percentage_rate',
            __('Percentage Rate (%)', 'eia-fuel-surcharge'),
            [$this, 'percentage_rate_field_callback'],
            'eia_fuel_surcharge_settings',
            'eia_fuel_surcharge_calculation_section'
        );
        
        // Schedule Settings Section
        add_settings_section(
            'eia_fuel_surcharge_schedule_section',
            __('Schedule Settings', 'eia-fuel-surcharge'),
            [$this, 'schedule_section_info'],
            'eia_fuel_surcharge_settings'
        );
        
        // Update frequency field
        add_settings_field(
            'update_frequency',
            __('Update Frequency', 'eia-fuel-surcharge'),
            [$this, 'update_frequency_field_callback'],
            'eia_fuel_surcharge_settings',
            'eia_fuel_surcharge_schedule_section'
        );
        
        // Update day field
        add_settings_field(
            'update_day',
            __('Update Day', 'eia-fuel-surcharge'),
            [$this, 'update_day_field_callback'],
            'eia_fuel_surcharge_settings',
            'eia_fuel_surcharge_schedule_section'
        );
        
        // Update time field
        add_settings_field(
            'update_time',
            __('Update Time', 'eia-fuel-surcharge'),
            [$this, 'update_time_field_callback'],
            'eia_fuel_surcharge_settings',
            'eia_fuel_surcharge_schedule_section'
        );
        
        // Display Settings Section
        add_settings_section(
            'eia_fuel_surcharge_display_section',
            __('Display Settings', 'eia-fuel-surcharge'),
            [$this, 'display_section_info'],
            'eia_fuel_surcharge_settings'
        );
        
        // Date format field
        add_settings_field(
            'date_format',
            __('Date Format', 'eia-fuel-surcharge'),
            [$this, 'date_format_field_callback'],
            'eia_fuel_surcharge_settings',
            'eia_fuel_surcharge_display_section'
        );
        
        // Decimal places field
        add_settings_field(
            'decimal_places',
            __('Decimal Places', 'eia-fuel-surcharge'),
            [$this, 'decimal_places_field_callback'],
            'eia_fuel_surcharge_settings',
            'eia_fuel_surcharge_display_section'
        );
        
        // Text format field
        add_settings_field(
            'text_format',
            __('Text Format', 'eia-fuel-surcharge'),
            [$this, 'text_format_field_callback'],
            'eia_fuel_surcharge_settings',
            'eia_fuel_surcharge_display_section'
        );
        
        // Table rows field
        add_settings_field(
            'table_rows',
            __('Default Table Rows', 'eia-fuel-surcharge'),
            [$this, 'table_rows_field_callback'],
            'eia_fuel_surcharge_settings',
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
        $validated = [];
        
        // API Key
        $validated['api_key'] = sanitize_text_field($input['api_key']);
        
        // Calculation settings
        $validated['base_threshold'] = floatval($input['base_threshold']);
        $validated['increment_amount'] = floatval($input['increment_amount']);
        $validated['percentage_rate'] = floatval($input['percentage_rate']);
        
        // Schedule settings
        $validated['update_frequency'] = sanitize_text_field($input['update_frequency']);
        $validated['update_day'] = sanitize_text_field($input['update_day']);
        $validated['update_time'] = sanitize_text_field($input['update_time']);
        
        // Display settings
        $validated['date_format'] = sanitize_text_field($input['date_format']);
        $validated['decimal_places'] = intval($input['decimal_places']);
        $validated['text_format'] = sanitize_text_field($input['text_format']);
        $validated['table_rows'] = intval($input['table_rows']);
        
        // Get the existing settings
        $existing_settings = get_option('eia_fuel_surcharge_settings');
        
        // If schedule settings have changed, update the scheduled event
        if ($existing_settings['update_frequency'] !== $validated['update_frequency'] || 
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
    
    public function base_threshold_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $base_threshold = isset($options['base_threshold']) ? $options['base_threshold'] : 1.20;
        
        echo '<input type="number" step="0.01" id="base_threshold" name="eia_fuel_surcharge_settings[base_threshold]" value="' . esc_attr($base_threshold) . '" class="small-text" />';
        echo '<p class="description">' . __('The base diesel price threshold (in dollars) at which the surcharge begins.', 'eia-fuel-surcharge') . '</p>';
    }
    
    public function increment_amount_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $increment_amount = isset($options['increment_amount']) ? $options['increment_amount'] : 0.06;
        
        echo '<input type="number" step="0.01" id="increment_amount" name="eia_fuel_surcharge_settings[increment_amount]" value="' . esc_attr($increment_amount) . '" class="small-text" />';
        echo '<p class="description">' . __('The price increment (in dollars) that triggers an increase in the surcharge percentage.', 'eia-fuel-surcharge') . '</p>';
    }
    
    public function percentage_rate_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $percentage_rate = isset($options['percentage_rate']) ? $options['percentage_rate'] : 0.5;
        
        echo '<input type="number" step="0.1" id="percentage_rate" name="eia_fuel_surcharge_settings[percentage_rate]" value="' . esc_attr($percentage_rate) . '" class="small-text" />';
        echo '<p class="description">' . __('The percentage increase in surcharge for each price increment.', 'eia-fuel-surcharge') . '</p>';
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
        
        echo '<select id="update_day" name="eia_fuel_surcharge_settings[update_day]">';
        foreach ($days as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($update_day, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('The day of the week to update (for weekly schedule).', 'eia-fuel-surcharge') . '</p>';
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
        $decimal_places = isset($options['decimal_places']) ? $options['decimal_places'] : 2;
        
        echo '<input type="number" min="0" max="4" id="decimal_places" name="eia_fuel_surcharge_settings[decimal_places]" value="' . esc_attr($decimal_places) . '" class="small-text" />';
        echo '<p class="description">' . __('Number of decimal places to display in rates.', 'eia-fuel-surcharge') . '</p>';
    }
    
    public function text_format_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $text_format = isset($options['text_format']) ? $options['text_format'] : 'Currently as of {date} the fuel surcharge is {rate}%';
        
        echo '<input type="text" id="text_format" name="eia_fuel_surcharge_settings[text_format]" value="' . esc_attr($text_format) . '" class="large-text" />';
        echo '<p class="description">' . __('Text format for displaying the fuel surcharge. Use {date} for the date and {rate} for the surcharge rate.', 'eia-fuel-surcharge') . '</p>';
    }
    
    public function table_rows_field_callback() {
        $options = get_option('eia_fuel_surcharge_settings');
        $table_rows = isset($options['table_rows']) ? $options['table_rows'] : 10;
        
        echo '<input type="number" min="1" max="100" id="table_rows" name="eia_fuel_surcharge_settings[table_rows]" value="' . esc_attr($table_rows) . '" class="small-text" />';
        echo '<p class="description">' . __('Default number of rows to display in the fuel surcharge table.', 'eia-fuel-surcharge') . '</p>';
    }
}