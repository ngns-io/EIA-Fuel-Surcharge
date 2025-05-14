<?php
/**
 * Handles fuel surcharge calculations.
 *
 * @package    EIAFuelSurcharge
 * @subpackage EIAFuelSurcharge\Utilities
 */

namespace EIAFuelSurcharge\Utilities;

class Calculator {

    /**
     * Base threshold price.
     *
     * @since    1.0.0
     * @access   private
     * @var      float    $base_threshold    The base threshold price.
     */
    private $base_threshold;

    /**
     * Increment amount.
     *
     * @since    1.0.0
     * @access   private
     * @var      float    $increment_amount    The increment amount.
     */
    private $increment_amount;

    /**
     * Percentage rate per increment.
     *
     * @since    1.0.0
     * @access   private
     * @var      float    $percentage_rate    The percentage rate per increment.
     */
    private $percentage_rate;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $options = get_option('eia_fuel_surcharge_settings');
        
        // Set defaults if options are not set
        $this->base_threshold = isset($options['base_threshold']) ? floatval($options['base_threshold']) : 1.20;
        $this->increment_amount = isset($options['increment_amount']) ? floatval($options['increment_amount']) : 0.06;
        $this->percentage_rate = isset($options['percentage_rate']) ? floatval($options['percentage_rate']) : 0.5;
    }

    /**
     * Calculate surcharge rate based on diesel price.
     *
     * Formula: Surcharge = ((Diesel Price - Base Threshold) / Increment Amount) * Percentage Rate
     *
     * @since    1.0.0
     * @param    float    $diesel_price    The diesel price.
     * @return   float    The calculated surcharge rate.
     */
    public function calculate_surcharge_rate($diesel_price) {
        // If diesel price is below threshold, no surcharge
        if ($diesel_price <= $this->base_threshold) {
            return 0.0;
        }
        
        // Calculate the price difference above threshold
        $price_difference = $diesel_price - $this->base_threshold;
        
        // Calculate the number of increments
        $increments = $price_difference / $this->increment_amount;
        
        // Calculate the surcharge rate
        $surcharge_rate = $increments * $this->percentage_rate;
        
        // Return the calculated surcharge rate
        return $surcharge_rate;
    }

    /**
     * Get the current formula parameters.
     *
     * @since    1.0.0
     * @return   array    The formula parameters.
     */
    public function get_formula_parameters() {
        return [
            'base_threshold' => $this->base_threshold,
            'increment_amount' => $this->increment_amount,
            'percentage_rate' => $this->percentage_rate
        ];
    }

    /**
     * Get a human-readable description of the formula.
     *
     * @since    1.0.0
     * @return   string    The formula description.
     */
    public function get_formula_description() {
        return sprintf(
            __('Base Price Threshold: $%1$s | For every $%2$s increase above threshold, add %3$s%% to surcharge', 'eia-fuel-surcharge'),
            number_format($this->base_threshold, 2),
            number_format($this->increment_amount, 2),
            number_format($this->percentage_rate, 1)
        );
    }

    /**
     * Calculate surcharge rates for a range of diesel prices.
     *
     * @since    2.0.0
     * @param    float    $min_price    The minimum diesel price.
     * @param    float    $max_price    The maximum diesel price.
     * @param    float    $step         The price step.
     * @return   array    The calculated surcharge rates.
     */
    public function calculate_surcharge_rate_range($min_price, $max_price, $step = 0.05) {
        $results = [];
        
        for ($price = $min_price; $price <= $max_price; $price += $step) {
            $results[] = [
                'price' => round($price, 3),
                'rate' => $this->calculate_surcharge_rate($price)
            ];
        }
        
        return $results;
    }

    /**
     * Validate surcharge parameters.
     *
     * @since    2.0.0
     * @param    array    $params    The parameters to validate.
     * @return   array    Validation results with errors if any.
     */
    public function validate_parameters($params) {
        $errors = [];
        
        // Validate base_threshold
        if (!isset($params['base_threshold']) || !is_numeric($params['base_threshold']) || $params['base_threshold'] < 0) {
            $errors['base_threshold'] = __('Base threshold must be a non-negative number', 'eia-fuel-surcharge');
        }
        
        // Validate increment_amount
        if (!isset($params['increment_amount']) || !is_numeric($params['increment_amount']) || $params['increment_amount'] <= 0) {
            $errors['increment_amount'] = __('Increment amount must be a positive number', 'eia-fuel-surcharge');
        }
        
        // Validate percentage_rate
        if (!isset($params['percentage_rate']) || !is_numeric($params['percentage_rate']) || $params['percentage_rate'] < 0) {
            $errors['percentage_rate'] = __('Percentage rate must be a non-negative number', 'eia-fuel-surcharge');
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Apply custom formula via filter if available.
     *
     * @since    2.0.0
     * @param    float    $diesel_price    The diesel price.
     * @return   float    The calculated surcharge rate.
     */
    public function apply_custom_formula($diesel_price) {
        $calculated_rate = $this->calculate_surcharge_rate($diesel_price);
        
        // Apply filter to allow custom calculation logic
        return apply_filters('eia_fuel_surcharge_rate', $calculated_rate, $diesel_price);
    }
}