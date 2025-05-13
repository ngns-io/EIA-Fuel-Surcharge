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

    // ... [previous methods already provided] ...

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