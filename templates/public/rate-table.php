<?php
/**
 * Template for displaying a table of fuel surcharge rates.
 *
 * @package    EIAFuelSurcharge
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Variables that should be available:
// $data - Array of fuel surcharge data records
// $date_format - Format for displaying dates
// $columns - Array of columns to display
// $decimals - Number of decimal places for rates
// $class - CSS class for the table
// $title - Optional title for the table
// $show_formula - Whether to show the calculation formula in the footer

// If no data provided, show error
if (empty($data)) {
    echo '<p class="fuel-surcharge-error">' . __('No fuel surcharge data available.', 'eia-fuel-surcharge') . '</p>';
    return;
}

// Display title if provided
if (!empty($title)) {
    echo '<h3 class="fuel-surcharge-table-title">' . esc_html($title) . '</h3>';
}

// Start the table
echo '<div class="fuel-surcharge-table-container">';
echo '<table class="' . esc_attr($class) . '">';

// Column headers
$column_headers = [
    'date' => __('Date', 'eia-fuel-surcharge'),
    'price' => __('Diesel Price', 'eia-fuel-surcharge'),
    'rate' => __('Surcharge Rate', 'eia-fuel-surcharge'),
    'region' => __('Region', 'eia-fuel-surcharge')
];

// Generate table header
echo '<thead><tr>';
foreach ($columns as $column) {
    if (isset($column_headers[$column])) {
        echo '<th>' . $column_headers[$column] . '</th>';
    }
}
echo '</tr></thead>';

// Generate table body
echo '<tbody>';
foreach ($data as $row) {
    echo '<tr>';
    foreach ($columns as $column) {
        switch ($column) {
            case 'date':
                echo '<td>' . date_i18n($date_format, strtotime($row['price_date'])) . '</td>';
                break;
            case 'price':
                echo '<td>$' . number_format($row['diesel_price'], 3) . '</td>';
                break;
            case 'rate':
                echo '<td>' . number_format($row['surcharge_rate'], $decimals) . '%</td>';
                break;
            case 'region':
                // Format region name for display
                $region_name = ucfirst(str_replace('_', ' ', $row['region']));
                echo '<td>' . $region_name . '</td>';
                break;
            default:
                // Default case for custom columns
                if (isset($row[$column])) {
                    echo '<td>' . esc_html($row[$column]) . '</td>';
                } else {
                    echo '<td></td>';
                }
                break;
        }
    }
    echo '</tr>';
}
echo '</tbody>';

// Close the table
echo '</table>';
echo '</div>';

// Display formula in the footer if requested
if (!empty($show_formula) && $show_formula === true) {
    $options = get_option('eia_fuel_surcharge_settings');
    
    echo '<div class="fuel-surcharge-table-footer">';
    echo '<p class="fuel-surcharge-formula">';
    
    $base_threshold = isset($options['base_threshold']) ? floatval($options['base_threshold']) : 1.20;
    $increment_amount = isset($options['increment_amount']) ? floatval($options['increment_amount']) : 0.06;
    $percentage_rate = isset($options['percentage_rate']) ? floatval($options['percentage_rate']) : 0.5;
    
    echo sprintf(
        __('Formula: Base Price Threshold: $%1$s | For every $%2$s increase above threshold, add %3$s%% to surcharge', 'eia-fuel-surcharge'),
        number_format($base_threshold, 2),
        number_format($increment_amount, 2),
        number_format($percentage_rate, 1)
    );
    
    echo '</p>';
    
    // Add EIA source link if enabled
    if (isset($options['eia_source_link']) && $options['eia_source_link'] === 'true') {
        echo '<p class="fuel-surcharge-source">';
        echo __('Source: U.S. Energy Information Administration', 'eia-fuel-surcharge');
        echo ' <a href="https://www.eia.gov/petroleum/gasdiesel/" target="_blank">';
        echo __('Gasoline and Diesel Fuel Update', 'eia-fuel-surcharge');
        echo '</a>';
        echo '</p>';
    }
    
    echo '</div>';
}