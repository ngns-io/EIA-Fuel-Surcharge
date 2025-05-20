<?php
/**
 * Handles scheduling of data updates.
 *
 * @package    EIAFuelSurcharge
 * @subpackage EIAFuelSurcharge\Core
 */

namespace EIAFuelSurcharge\Core;

use EIAFuelSurcharge\API\EIAHandler;
use EIAFuelSurcharge\Utilities\Logger;

class Scheduler {

    /**
     * Logger instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Logger    $logger    The logger instance.
     */
    private $logger;

    /**
     * Initialize the class and set up WordPress hooks.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->logger = new Logger();
        
        // Register custom cron schedules
        add_filter('cron_schedules', [$this, 'add_custom_cron_schedules']);
    }

    /**
     * Add custom cron schedules.
     *
     * @since    1.0.0
     * @param    array    $schedules    The existing cron schedules.
     * @return   array    The modified cron schedules.
     */
    public function add_custom_cron_schedules($schedules) {
        // Add a monthly schedule
        $schedules['monthly'] = [
            'interval' => 30 * DAY_IN_SECONDS,
            'display'  => __('Once a Month', 'eia-fuel-surcharge')
        ];
        
        // Add custom interval schedules (could be used for "every n days")
        $options = get_option('eia_fuel_surcharge_settings');
        $custom_interval = isset($options['custom_interval']) ? intval($options['custom_interval']) : 0;
        
        if ($custom_interval > 0) {
            $schedules['custom_interval'] = [
                'interval' => $custom_interval * DAY_IN_SECONDS,
                'display'  => sprintf(__('Every %d Days', 'eia-fuel-surcharge'), $custom_interval)
            ];
        }
        
        return $schedules;
    }

    /**
     * Schedule the update event based on settings.
     *
     * @since    1.0.0
     */
    public function schedule_update() {
        // Clear any existing schedule
        $this->clear_scheduled_update();
        
        $options = get_option('eia_fuel_surcharge_settings');
        
        $frequency = isset($options['update_frequency']) ? $options['update_frequency'] : 'weekly';
        $day = isset($options['update_day']) ? $options['update_day'] : 'tuesday';
        $time = isset($options['update_time']) ? $options['update_time'] : '12:00';
        
        // Calculate the next run time based on frequency and day
        $next_run = $this->calculate_next_run_time($frequency, $day, $time, $options);
        
        // Determine the recurrence
        $recurrence = $this->get_recurrence_for_frequency($frequency);
        
        // Schedule the event
        if ($recurrence && $next_run) {
            wp_schedule_event($next_run, $recurrence, 'eia_fuel_surcharge_update_event');
            
            // Log the scheduled event
            $this->logger->log_scheduled_event($next_run, $recurrence);
        }
    }

    /**
     * Clear the scheduled update event.
     *
     * @since    1.0.0
     */
    public function clear_scheduled_update() {
        wp_clear_scheduled_hook('eia_fuel_surcharge_update_event');
    }

    /**
     * Run the scheduled update.
     *
     * @since    1.0.0
     * @param    bool      $force_refresh    Whether to force a refresh from the API.
     * @return   mixed     True on success, array with error details on failure.
     */
    public function run_scheduled_update($force_refresh = false) {
        // Get API handler
        $api_handler = new EIAHandler();
        
        // Log that the update is running
        $this->logger->log_update_start();
        
        // Get the latest diesel prices - pass force_refresh parameter
        $api_data = $api_handler->get_diesel_prices($force_refresh);
        
        // Check for API errors
        if (is_wp_error($api_data)) {
            $this->logger->log_update_error($api_data->get_error_message());
            return [
                'success' => false,
                'message' => $api_data->get_error_message()
            ];
        }
        
        // Process the API data
        $processed_data = $api_handler->process_diesel_price_data($api_data);
        
        // Check if we got any data
        if (empty($processed_data)) {
            $error_message = 'No data returned from API or data processing failed';
            $this->logger->log_update_error($error_message);
            return [
                'success' => false, 
                'message' => __($error_message, 'eia-fuel-surcharge'),
                'api_data' => $api_data
            ];
        }
        
        // Save the data to the database
        $save_result = $api_handler->save_diesel_price_data($processed_data);
        
        if (isset($save_result['success']) && $save_result['success']) {
            // Log detailed success information including statistics
            $stats = isset($save_result['stats']) ? $save_result['stats'] : [];
            $this->logger->log_update_success($stats);
            
            // Now, check if we should update regional data as well
            if ($this->should_update_regional_data()) {
                $this->update_regional_data($force_refresh);
            }
            
            return $save_result;
        } else {
            $error_message = 'Failed to save data to database';
            if (isset($save_result['message'])) {
                $error_message = $save_result['message'];
            }
            $this->logger->log_update_error($error_message);
            return [
                'success' => false,
                'message' => __($error_message, 'eia-fuel-surcharge'),
                'save_result' => $save_result
            ];
        }
    }

    /**
     * Check if regional data should be updated.
     * 
     * @since    2.0.0
     * @return   bool    True if regional data should be updated, false otherwise.
     */
    private function should_update_regional_data() {
        $options = get_option('eia_fuel_surcharge_settings');
        return isset($options['update_regions']) && $options['update_regions'] === 'true';
    }

    /**
     * Update regional data.
     * 
     * @since    2.0.0
     * @param    bool    $force_refresh    Whether to force a refresh from the API.
     * @return   array   Result of the regional data update.
     */
    private function update_regional_data($force_refresh = false) {
        $api_handler = new EIAHandler();
        
        // Log that we're updating regional data
        $this->logger->log('regional_update_start', __('Starting regional data update', 'eia-fuel-surcharge'));
        
        // Get regional diesel prices
        $regional_data = $api_handler->get_regional_diesel_prices($force_refresh);
        
        // Check for errors
        if (is_wp_error($regional_data)) {
            $error_message = $regional_data->get_error_message();
            $this->logger->log('regional_update_error', sprintf(
                __('Failed to retrieve regional data: %s', 'eia-fuel-surcharge'),
                $error_message
            ));
            return [
                'success' => false,
                'message' => $error_message
            ];
        }
        
        // Process and store the regional data
        $result = $api_handler->process_and_store_regional_data($regional_data);
        
        // Log the result
        if ($result['success']) {
            $this->logger->log('regional_update_success', __('Regional data update completed successfully', 'eia-fuel-surcharge'), $result['stats']);
        } else {
            $this->logger->log('regional_update_error', __('Regional data update failed', 'eia-fuel-surcharge'), $result);
        }
        
        return $result;
    }

    /**
     * Calculate the next run time based on frequency, day, and time.
     *
     * @since    1.0.0
     * @param    string    $frequency    The update frequency.
     * @param    string    $day          The day of the week (for weekly schedule).
     * @param    string    $time         The time of day (HH:MM).
     * @param    array     $options      The plugin options.
     * @return   int|false    The timestamp for the next run, or false on failure.
     */
    private function calculate_next_run_time($frequency, $day, $time, $options = null) {
        // Parse the time
        $time_parts = explode(':', $time);
        $hour = isset($time_parts[0]) ? intval($time_parts[0]) : 12;
        $minute = isset($time_parts[1]) ? intval($time_parts[1]) : 0;
        
        // Get the current timestamp
        $current_time = current_time('timestamp');
        
        // If options not provided, get them
        if ($options === null) {
            $options = get_option('eia_fuel_surcharge_settings');
        }
        
        switch ($frequency) {
            case 'daily':
                // Set the time for today
                $next_run = strtotime("today {$hour}:{$minute}", $current_time);
                
                // If that's in the past, use tomorrow
                if ($next_run <= $current_time) {
                    $next_run = strtotime("tomorrow {$hour}:{$minute}", $current_time);
                }
                break;
                
            case 'weekly':
                // Set the time for the next specified day
                $next_run = strtotime("next {$day} {$hour}:{$minute}", $current_time);
                break;
                
            case 'monthly':
                // Get the day of the month from settings (or use 1st day)
                $month_day = isset($options['update_day_of_month']) ? intval($options['update_day_of_month']) : 1;
                
                // Calculate next month's date
                $next_month = strtotime('+1 month', $current_time);
                $next_run = mktime($hour, $minute, 0, date('n', $next_month), $month_day, date('Y', $next_month));
                break;
                
            case 'custom':
                // Custom interval - run after X days from now
                $custom_interval = isset($options['custom_interval']) ? intval($options['custom_interval']) : 1;
                
                if ($custom_interval < 1) {
                    $custom_interval = 1;
                }
                
                // Start from today at the specified time
                $today_time = strtotime("today {$hour}:{$minute}", $current_time);
                
                // If that's in the past, start from tomorrow
                if ($today_time <= $current_time) {
                    $today_time = strtotime("tomorrow {$hour}:{$minute}", $current_time);
                }
                
                // Add the custom interval days (minus 1 because we're already starting from tomorrow if needed)
                $next_run = strtotime("+" . ($custom_interval - 1) . " days", $today_time);
                break;
                
            default:
                // Default to weekly on Tuesday
                $next_run = strtotime("next tuesday {$hour}:{$minute}", $current_time);
                break;
        }
        
        return $next_run;
    }

    /**
     * Get the WordPress cron recurrence for the given frequency.
     *
     * @since    1.0.0
     * @param    string    $frequency    The update frequency.
     * @return   string    The WordPress cron recurrence.
     */
    private function get_recurrence_for_frequency($frequency) {
        switch ($frequency) {
            case 'daily':
                return 'daily';
                
            case 'weekly':
                return 'weekly';
                
            case 'monthly':
                return 'monthly';
                
            case 'custom':
                return 'custom_interval';
                
            default:
                return 'weekly';
        }
    }

    /**
     * Get the next scheduled update time.
     *
     * @since    1.0.0
     * @return   int|false    The timestamp for the next scheduled update, or false if none is scheduled.
     */
    public function get_next_scheduled_update() {
        return wp_next_scheduled('eia_fuel_surcharge_update_event');
    }
}