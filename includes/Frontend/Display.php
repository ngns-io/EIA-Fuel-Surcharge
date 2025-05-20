<?php
/**
 * Handles display formatting for fuel surcharge data.
 *
 * @package    EIAFuelSurcharge
 * @subpackage EIAFuelSurcharge\Frontend
 */

namespace EIAFuelSurcharge\Frontend;

class Display {

    /**
     * Format surcharge rate for display.
     *
     * @since    1.0.0
     * @param    float     $rate          The raw surcharge rate.
     * @param    int       $decimals      Number of decimal places.
     * @param    string    $format        Format string.
     * @param    string    $date_format   Date format for display.
     * @param    string    $date          The date of the rate.
     * @return   string    Formatted surcharge rate.
     */
    public function format_surcharge_rate($rate, $decimals = 2, $format = null, $date_format = null, $date = null) {
        // Get plugin settings
        $options = get_option('eia_fuel_surcharge_settings');
        
        // Use settings if parameters are not provided
        $decimals = ($decimals !== null) ? $decimals : (isset($options['decimal_places']) ? intval($options['decimal_places']) : 2);
        $format = $format ?: (isset($options['text_format']) ? $options['text_format'] : 'Currently as of {date} the fuel surcharge is {rate}%');
        $date_format = $date_format ?: (isset($options['date_format']) ? $options['date_format'] : 'm/d/Y');
        
        // If no date is provided, use current date
        $display_date = $date ? date($date_format, strtotime($date)) : date($date_format);
        
        // Format the rate with the specified number of decimal places
        $formatted_rate = number_format($rate, $decimals);
        
        // Replace placeholders in the format string
        $output = str_replace(
            ['{rate}', '{date}'],
            [$formatted_rate, $display_date],
            $format
        );
        
        return $output;
    }

    /**
     * Get the latest fuel surcharge data.
     *
     * @since    1.0.0
     * @param    string    $region    The region to get data for.
     * @return   array|false    Latest fuel surcharge data or false if none found.
     */
    public function get_latest_data($region = 'national') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_data';
        
        // Get the latest data for the specified region
        $data = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE region = %s ORDER BY price_date DESC LIMIT 1",
                $region
            ),
            ARRAY_A
        );
        
        return $data ?: false;
    }

    /**
     * Get historical fuel surcharge data.
     *
     * @since    1.0.0
     * @param    string    $region    The region to get data for.
     * @param    int       $rows      Number of rows to retrieve.
     * @param    string    $order     Order direction (ASC or DESC).
     * @return   array     Historical fuel surcharge data.
     */
    public function get_historical_data($region = 'national', $rows = 10, $order = 'DESC') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_data';
        
        // Sanitize order direction
        $order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';
        
        // Get historical data
        $data = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE region = %s ORDER BY price_date $order LIMIT %d",
                $region,
                intval($rows)
            ),
            ARRAY_A
        );
        
        return $data;
    }

    /**
     * Generate fuel surcharge table HTML.
     *
     * @since    1.0.0
     * @param    array     $data         The fuel surcharge data.
     * @param    string    $date_format  Date format for display.
     * @param    array     $columns      Columns to display.
     * @param    int       $decimals     Number of decimal places for rate.
     * @param    string    $class        CSS class for the table.
     * @return   string    HTML for the fuel surcharge table.
     */
    public function generate_table_html($data, $date_format = null, $columns = null, $decimals = null, $class = 'fuel-surcharge-table') {
        if (empty($data)) {
            return '<p class="fuel-surcharge-error">' . __('No fuel surcharge data available.', 'eia-fuel-surcharge') . '</p>';
        }
        
        // Get plugin settings
        $options = get_option('eia_fuel_surcharge_settings');
        
        // Use settings if parameters are not provided
        $date_format = $date_format ?: (isset($options['date_format']) ? $options['date_format'] : 'm/d/Y');
        $decimals = ($decimals !== null) ? $decimals : (isset($options['decimal_places']) ? intval($options['decimal_places']) : 2);
        
        // Default columns to display
        if ($columns === null) {
            $columns = ['date', 'price', 'rate'];
        } elseif (is_string($columns)) {
            $columns = explode(',', $columns);
        }
        
        // Column headers and keys
        $column_map = [
            'date' => [
                'header' => __('Date', 'eia-fuel-surcharge'),
                'key' => 'price_date'
            ],
            'price' => [
                'header' => __('Diesel Price', 'eia-fuel-surcharge'),
                'key' => 'diesel_price'
            ],
            'rate' => [
                'header' => __('Surcharge Rate', 'eia-fuel-surcharge'),
                'key' => 'surcharge_rate'
            ],
            'region' => [
                'header' => __('Region', 'eia-fuel-surcharge'),
                'key' => 'region'
            ]
        ];
        
        // Start building the table
        $html = '<div class="fuel-surcharge-table-container">';
        $html .= '<table class="' . esc_attr($class) . '">';
        
        // Table header
        $html .= '<thead><tr>';
        foreach ($columns as $column) {
            if (isset($column_map[$column])) {
                $html .= '<th>' . $column_map[$column]['header'] . '</th>';
            }
        }
        $html .= '</tr></thead>';
        
        // Table body
        $html .= '<tbody>';
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($columns as $column) {
                if (isset($column_map[$column])) {
                    $key = $column_map[$column]['key'];
                    
                    if ($column === 'date') {
                        // Format the date
                        $html .= '<td>' . date($date_format, strtotime($row[$key])) . '</td>';
                    } elseif ($column === 'price') {
                        // Format the price with dollar sign
                        $html .= '<td>$' . number_format($row[$key], 3) . '</td>';
                    } elseif ($column === 'rate') {
                        // Format the rate with percent sign
                        $html .= '<td>' . number_format($row[$key], $decimals) . '%</td>';
                    } elseif ($column === 'region') {
                        // Format the region name
                        $html .= '<td>' . ucfirst(str_replace('_', ' ', $row[$key])) . '</td>';
                    } else {
                        // Default output
                        $html .= '<td>' . esc_html($row[$key]) . '</td>';
                    }
                }
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Check if the plugin is properly configured.
     *
     * @since    1.0.0
     * @return   bool|string    True if configured, error message if not.
     */
    public function check_configuration() {
        $options = get_option('eia_fuel_surcharge_settings');
        
        // Check if API key is set
        if (empty($options['api_key'])) {
            return __('EIA API key is required. Please configure the plugin settings.', 'eia-fuel-surcharge');
        }
        
        // Check if we have any data
        global $wpdb;
        $table_name = $wpdb->prefix . 'fuel_surcharge_data';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        if (intval($count) === 0) {
            return __('No fuel surcharge data available. Please update the data in the plugin settings.', 'eia-fuel-surcharge');
        }
        
        return true;
    }

    /**
     * Get regions available in the database.
     *
     * @since    2.0.0
     * @return   array    Array of available regions.
     */
    public function get_available_regions() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_data';
        
        $regions = $wpdb->get_col("SELECT DISTINCT region FROM $table_name ORDER BY region");
        
        return $regions;
    }

    /**
     * Get the comparison with previous periods.
     *
     * @since    2.0.0
     * @param    array     $current_data    Current rate data.
     * @param    string    $period          Period to compare (day, week, month, year).
     * @return   array     Comparison data.
     */
    public function get_comparison_data($current_data, $period = 'week') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_data';
        
        if (empty($current_data) || !isset($current_data['price_date']) || !isset($current_data['region'])) {
            return false;
        }
        
        // Determine the comparison date based on period
        $compare_date_sql = '';
        switch ($period) {
            case 'day':
                $compare_date_sql = "DATE_SUB('" . $current_data['price_date'] . "', INTERVAL 1 DAY)";
                $description = __('yesterday', 'eia-fuel-surcharge');
                break;
            case 'week':
                $compare_date_sql = "DATE_SUB('" . $current_data['price_date'] . "', INTERVAL 1 WEEK)";
                $description = __('last week', 'eia-fuel-surcharge');
                break;
            case 'month':
                $compare_date_sql = "DATE_SUB('" . $current_data['price_date'] . "', INTERVAL 1 MONTH)";
                $description = __('last month', 'eia-fuel-surcharge');
                break;
            case 'year':
                $compare_date_sql = "DATE_SUB('" . $current_data['price_date'] . "', INTERVAL 1 YEAR)";
                $description = __('last year', 'eia-fuel-surcharge');
                break;
            default:
                $compare_date_sql = "DATE_SUB('" . $current_data['price_date'] . "', INTERVAL 1 WEEK)";
                $description = __('last week', 'eia-fuel-surcharge');
        }
        
        // Get the closest date before the comparison date
        $compare_data = $wpdb->get_row(
            "SELECT * FROM $table_name 
            WHERE region = '" . $current_data['region'] . "' 
            AND price_date <= " . $compare_date_sql . " 
            ORDER BY price_date DESC 
            LIMIT 1",
            ARRAY_A
        );
        
        if (!$compare_data) {
            return false;
        }
        
        // Calculate the difference
        $price_diff = $current_data['diesel_price'] - $compare_data['diesel_price'];
        $price_diff_percent = ($compare_data['diesel_price'] > 0) 
            ? ($price_diff / $compare_data['diesel_price'] * 100) 
            : 0;
            
        $rate_diff = $current_data['surcharge_rate'] - $compare_data['surcharge_rate'];
        
        return [
            'compare_date' => $compare_data['price_date'],
            'compare_price' => $compare_data['diesel_price'],
            'compare_rate' => $compare_data['surcharge_rate'],
            'price_diff' => $price_diff,
            'price_diff_percent' => $price_diff_percent,
            'rate_diff' => $rate_diff,
            'period' => $period,
            'description' => $description
        ];
    }

    /**
     * Format comparison data for display.
     *
     * @since    2.0.0
     * @param    array     $comparison    Comparison data from get_comparison_data().
     * @param    int       $decimals      Number of decimal places for rates.
     * @return   string    Formatted comparison text.
     */
    public function format_comparison($comparison, $decimals = 2) {
        if (!$comparison) {
            return '';
        }
        
        // Format the rate difference
        $rate_diff = $comparison['rate_diff'];
        $rate_diff_formatted = number_format(abs($rate_diff), $decimals);
        
        // Format the price difference
        $price_diff = $comparison['price_diff'];
        $price_diff_formatted = number_format(abs($price_diff), 3);
        $price_diff_percent = number_format(abs($comparison['price_diff_percent']), 1);
        
        // Create comparison text
        if ($rate_diff > 0) {
            return sprintf(
                __('(+%1$s%% from %2$s, diesel price up $%3$s or %4$s%%)', 'eia-fuel-surcharge'),
                $rate_diff_formatted,
                $comparison['description'],
                $price_diff_formatted,
                $price_diff_percent
            );
        } elseif ($rate_diff < 0) {
            return sprintf(
                __('(-%1$s%% from %2$s, diesel price down $%3$s or %4$s%%)', 'eia-fuel-surcharge'),
                $rate_diff_formatted,
                $comparison['description'],
                $price_diff_formatted,
                $price_diff_percent
            );
        } else {
            if ($price_diff == 0) {
                return sprintf(
                    __('(unchanged from %s)', 'eia-fuel-surcharge'),
                    $comparison['description']
                );
            } else {
                $direction = $price_diff > 0 ? __('up', 'eia-fuel-surcharge') : __('down', 'eia-fuel-surcharge');
                return sprintf(
                    __('(rate unchanged from %1$s, diesel price %2$s $%3$s or %4$s%%)', 'eia-fuel-surcharge'),
                    $comparison['description'],
                    $direction,
                    $price_diff_formatted,
                    $price_diff_percent
                );
            }
        }
    }

    /**
     * Get the HTML for the EIA source link if enabled.
     *
     * @since    2.1.0
     * @param    bool     $override    Optional. Override the global setting.
     * @return   string   HTML for the source link or empty string.
     */
    public function get_source_link_html($override = null) {
        if ($override !== null) {
            $show_source_link = $override;
        } else {
            // Get from settings
            $options = get_option('eia_fuel_surcharge_settings');
            $show_source_link = isset($options['eia_source_link']) && $options['eia_source_link'] === 'true';
        }
        
        if ($show_source_link) {
            $html = '<p class="fuel-surcharge-source">';
            $html .= __('Source: U.S. Energy Information Administration', 'eia-fuel-surcharge');
            $html .= ' <a href="https://www.eia.gov/petroleum/gasdiesel/" target="_blank">';
            $html .= __('Gasoline and Diesel Fuel Update', 'eia-fuel-surcharge');
            $html .= '</a>';
            $html .= '</p>';
            
            return $html;
        }
        
        return '';
    }

}