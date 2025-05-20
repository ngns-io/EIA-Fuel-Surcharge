<?php
/**
 * Handles shortcodes for fuel surcharge display.
 *
 * @package    EIAFuelSurcharge
 * @subpackage EIAFuelSurcharge\Frontend
 */

namespace EIAFuelSurcharge\Frontend;

use EIAFuelSurcharge\Utilities\Calculator;

class Shortcodes {

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
     * Display instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Display    $display    The display handler instance.
     */
    private $display;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->display = new Display();
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, EIA_FUEL_SURCHARGE_PLUGIN_URL . 'assets/css/public.css', [], $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, EIA_FUEL_SURCHARGE_PLUGIN_URL . 'assets/js/public.js', ['jquery'], $this->version, false);
    }

    /**
     * Register all shortcodes.
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode('fuel_surcharge', [$this, 'fuel_surcharge_shortcode']);
        add_shortcode('fuel_surcharge_table', [$this, 'fuel_surcharge_table_shortcode']);
    }

    /**
     * Shortcode to display the current fuel surcharge rate.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string    Formatted output of the shortcode.
     */
    public function fuel_surcharge_shortcode($atts) {
        // Extract attributes and set defaults
        $atts = shortcode_atts(
            [
                'format'          => '',
                'date_format'     => '',
                'decimals'        => null,
                'class'           => 'fuel-surcharge',
                'region'          => 'national',
                'compare'         => 'week',
                'show_comparison' => 'true',
                'show_source_link' => '' // Empty string means use global setting
            ],
            $atts,
            'fuel_surcharge'
        );
        
        // Convert string "null" to actual null for decimals
        if ($atts['decimals'] === 'null') {
            $atts['decimals'] = null;
        } elseif (is_numeric($atts['decimals'])) {
            $atts['decimals'] = intval($atts['decimals']);
        }
        
        // Check if the plugin is properly configured
        $config_check = $this->display->check_configuration();
        if ($config_check !== true) {
            return '<p class="fuel-surcharge-error">' . $config_check . '</p>';
        }
        
        // Get the latest data
        $data = $this->display->get_latest_data($atts['region']);
        
        if (!$data) {
            return '<p class="fuel-surcharge-error">' . 
                sprintf(__('No fuel surcharge data available for region: %s', 'eia-fuel-surcharge'), $atts['region']) . 
                '</p>';
        }
        
        // Format the output
        $output = $this->display->format_surcharge_rate(
            $data['surcharge_rate'],
            $atts['decimals'],
            $atts['format'],
            $atts['date_format'],
            $data['price_date']
        );
        
        // Add comparison if enabled
        if ($atts['show_comparison'] === 'true' && !empty($atts['compare'])) {
            $comparison = $this->display->get_comparison_data($data, $atts['compare']);
            if ($comparison) {
                $comparison_text = $this->display->format_comparison($comparison, $atts['decimals']);
                if (!empty($comparison_text)) {
                    $output .= ' ' . $comparison_text;
                }
            }
        }
        
        // Add source link if enabled
        if ($atts['show_source_link'] !== '') {
            // Use the shortcode attribute to override global setting
            $show_source_link = $atts['show_source_link'] === 'true';
            $source_link = $this->display->get_source_link_html($show_source_link);
            if (!empty($source_link)) {
                $output .= $source_link;
            }
        } else {
            // Use global setting
            $source_link = $this->display->get_source_link_html();
            if (!empty($source_link)) {
                $output .= $source_link;
            }
        }
        
        // Wrap the output in a div with the specified class
        return '<div class="' . esc_attr($atts['class']) . '">' . $output . '</div>';
    }

    /**
     * Shortcode to display a table of historical fuel surcharge rates.
     *
     * @since    1.0.0
     * @param    array     $atts    Shortcode attributes.
     * @return   string    Formatted output of the shortcode.
     */
    public function fuel_surcharge_table_shortcode($atts) {
        // Extract attributes and set defaults
        $atts = shortcode_atts(
            [
                'rows'            => 10,
                'date_format'     => '',
                'columns'         => 'date,price,rate',
                'order'           => 'desc',
                'class'           => 'fuel-surcharge-table',
                'region'          => 'national',
                'decimals'        => null,
                'title'           => '',
                'show_footer'     => 'false',
                'show_source_link' => '' // Empty string means use global setting
            ],
            $atts,
            'fuel_surcharge_table'
        );
        
        // Convert string "null" to actual null for decimals
        if ($atts['decimals'] === 'null') {
            $atts['decimals'] = null;
        } elseif (is_numeric($atts['decimals'])) {
            $atts['decimals'] = intval($atts['decimals']);
        }
        
        // Check if the plugin is properly configured
        $config_check = $this->display->check_configuration();
        if ($config_check !== true) {
            return '<p class="fuel-surcharge-error">' . $config_check . '</p>';
        }
        
        // Get the historical data
        $data = $this->display->get_historical_data(
            $atts['region'],
            intval($atts['rows']),
            $atts['order']
        );
        
        if (empty($data)) {
            return '<p class="fuel-surcharge-error">' . 
                sprintf(__('No fuel surcharge data available for region: %s', 'eia-fuel-surcharge'), $atts['region']) . 
                '</p>';
        }
        
        // Start building the output
        $output = '';
        
        // Add title if provided
        if (!empty($atts['title'])) {
            $output .= '<h3 class="fuel-surcharge-table-title">' . esc_html($atts['title']) . '</h3>';
        }
        
        // Generate the table HTML
        $output .= $this->display->generate_table_html(
            $data,
            $atts['date_format'],
            $atts['columns'],
            $atts['decimals'],
            $atts['class']
        );
        
        // Add a footer if requested
        if ($atts['show_footer'] === 'true') {
            $options = get_option('eia_fuel_surcharge_settings');
            $calculator = new Calculator();
            
            $output .= '<div class="fuel-surcharge-table-footer">';
            $output .= '<p class="fuel-surcharge-formula">' . 
                sprintf(
                    __('Formula: %s', 'eia-fuel-surcharge'),
                    $calculator->get_formula_description()
                ) . 
                '</p>';
                
            // Add source link if enabled (using either shortcode attribute or global setting)
            if ($atts['show_source_link'] !== '') {
                // Use the shortcode attribute to override global setting
                $show_source_link = $atts['show_source_link'] === 'true';
                if ($show_source_link) {
                    $output .= $this->display->get_source_link_html(true);
                }
            } else {
                // Use global setting for source link
                $output .= $this->display->get_source_link_html();
            }
            
            $output .= '</div>';
        } else if ($atts['show_source_link'] === 'true' || 
                 ($atts['show_source_link'] === '' && isset($options['eia_source_link']) && $options['eia_source_link'] === 'true')) {
            // If footer is not shown but source link is enabled, add it separately
            $output .= '<div class="fuel-surcharge-table-footer">';
            if ($atts['show_source_link'] === 'true') {
                $output .= $this->display->get_source_link_html(true);
            } else {
                $output .= $this->display->get_source_link_html();
            }
            $output .= '</div>';
        }
        
        return $output;
    }

    /**
     * Get comparison data for the current rate.
     *
     * @since    2.0.0
     * @param    array     $current_data    Current data.
     * @param    string    $compare_type    Type of comparison: day, week, month.
     * @return   string    Formatted comparison text.
     */
    private function get_comparison_data($current_data, $compare_type) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_data';
        $current_date = $current_data['price_date'];
        
        // Determine comparison date based on type
        switch ($compare_type) {
            case 'day':
                $compare_date = date('Y-m-d', strtotime('-1 day', strtotime($current_date)));
                $desc = __('yesterday', 'eia-fuel-surcharge');
                break;
            case 'week':
                $compare_date = date('Y-m-d', strtotime('-1 week', strtotime($current_date)));
                $desc = __('last week', 'eia-fuel-surcharge');
                break;
            case 'month':
                $compare_date = date('Y-m-d', strtotime('-1 month', strtotime($current_date)));
                $desc = __('last month', 'eia-fuel-surcharge');
                break;
            default:
                return '';
        }
        
        // Get the comparison data - find the closest date
        $compare_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name
            WHERE price_date <= %s AND region = %s
            ORDER BY price_date DESC
            LIMIT 1",
            $compare_date,
            $current_data['region']
        ), ARRAY_A);
        
        if (empty($compare_data)) {
            return '';
        }
        
        // Calculate the difference
        $diff = $current_data['surcharge_rate'] - $compare_data['surcharge_rate'];
        
        // Format the difference
        $options = get_option('eia_fuel_surcharge_settings');
        $decimals = isset($options['decimal_places']) ? intval($options['decimal_places']) : 2;
        
        if ($diff > 0) {
            return sprintf(
                __('(+%1$s%% from %2$s)', 'eia-fuel-surcharge'),
                number_format(abs($diff), $decimals),
                $desc
            );
        } elseif ($diff < 0) {
            return sprintf(
                __('(-%1$s%% from %2$s)', 'eia-fuel-surcharge'),
                number_format(abs($diff), $decimals),
                $desc
            );
        } else {
            return sprintf(
                __('(unchanged from %s)', 'eia-fuel-surcharge'),
                $desc
            );
        }
    }

    /**
     * Render a template file.
     *
     * @since    2.0.0
     * @param    string    $template    Template name.
     * @param    array     $args        Template arguments.
     * @return   string    The rendered template.
     */
    private function render_template($template, $args = []) {
        $template_file = EIA_FUEL_SURCHARGE_PLUGIN_DIR . 'templates/public/' . $template . '.php';
        
        // Check if the template file exists
        if (!file_exists($template_file)) {
            return '<p class="fuel-surcharge-error">' . 
                sprintf(__('Template file "%s" not found.', 'eia-fuel-surcharge'), $template) . 
                '</p>';
        }
        
        // Extract variables for use in the template
        extract($args);
        
        // Start output buffering
        ob_start();
        
        // Include the template file
        include $template_file;
        
        // Get the contents and clean the buffer
        $output = ob_get_clean();
        
        return $output;
    }
}