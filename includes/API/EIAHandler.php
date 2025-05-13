<?php
/**
 * Handles all interactions with the EIA API.
 *
 * @package    EIAFuelSurcharge
 * @subpackage EIAFuelSurcharge\API
 */

namespace EIAFuelSurcharge\API;

use EIAFuelSurcharge\Utilities\Calculator;
use EIAFuelSurcharge\Utilities\Logger;

class EIAHandler {

    /**
     * The API key for EIA API.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_key    The API key for EIA API.
     */
    private $api_key;

    /**
     * The base URL for the EIA API.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_base_url    The base URL for the EIA API.
     */
    private $api_base_url = 'https://api.eia.gov/v2/';

    /**
     * Logger instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Logger    $logger    The logger instance.
     */
    private $logger;

    /**
     * Cache duration in seconds.
     *
     * @since    2.0.0
     * @access   private
     * @var      int    $cache_duration    Cache duration in seconds.
     */
    private $cache_duration = 3600; // 1 hour by default

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $options = get_option('eia_fuel_surcharge_settings');
        $this->api_key = isset($options['api_key']) ? $options['api_key'] : '';
        $this->logger = new Logger();
        
        // Set cache duration from settings if available
        if (isset($options['cache_duration'])) {
            $this->cache_duration = intval($options['cache_duration']) * 60; // Convert minutes to seconds
        }
    }

    /**
     * Fetch the latest diesel fuel price data from the EIA API.
     *
     * @since    1.0.0
     * @param    bool     $force_refresh    Whether to force a refresh from the API.
     * @return   array|WP_Error    The API response data or WP_Error on failure.
     */
    public function get_diesel_prices($force_refresh = false) {
        // Check if API key is set
        if (empty($this->api_key)) {
            return new \WP_Error('missing_api_key', __('EIA API key is missing. Please enter it in the plugin settings.', 'eia-fuel-surcharge'));
        }

        // Check cache first if not forcing refresh
        if (!$force_refresh) {
            $cached_data = get_transient('eia_fuel_surcharge_api_data');
            if ($cached_data !== false) {
                $this->logger->log('cache_hit', __('Using cached API data', 'eia-fuel-surcharge'));
                return $cached_data;
            }
        }

        // Build the API endpoint URL for on-highway diesel prices
        // Using the petroleum/pri/gnd endpoint for on-highway diesel prices
        $endpoint = 'petroleum/pri/gnd/data/';
        
        // Set query parameters
        $params = [
            'api_key'             => $this->api_key,
            'data[]'              => 'value',
            'facets[duoarea][]'   => 'DHHWY', // On-highway diesel (national average)
            'frequency'           => 'weekly',
            'sort[0][column]'     => 'period',
            'sort[0][direction]'  => 'desc',
            'offset'              => 0,
            'length'              => 5 // Get the 5 most recent records
        ];
        
        // Build the request URL
        $request_url = add_query_arg($params, $this->api_base_url . $endpoint);
        
        // Log the request (for debugging)
        $this->logger->log_api_request($request_url);
        
        // Make the API request with enhanced error handling
        $response = $this->make_api_request($request_url);
        
        // If there was an error, return it
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Cache the successful response
        set_transient('eia_fuel_surcharge_api_data', $response, $this->cache_duration);
        
        // Return the data
        return $response;
    }

    /**
     * Make an API request with enhanced error handling.
     *
     * @since    2.0.0
     * @param    string    $request_url    The API request URL.
     * @return   array|WP_Error    The API response data or WP_Error on failure.
     */
    private function make_api_request($request_url) {
        // Make the API request with retry logic
        $max_retries = 3;
        $retry_count = 0;
        
        while ($retry_count < $max_retries) {
            // Make the request
            $response = wp_remote_get($request_url, [
                'timeout'     => 15,
                'user-agent'  => 'EIA Fuel Surcharge WordPress Plugin/' . EIA_FUEL_SURCHARGE_VERSION,
                'headers'     => [
                    'Accept' => 'application/json',
                ]
            ]);
            
            // Check for connection errors
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                $this->logger->log_api_error(sprintf(
                    __('Connection error (attempt %d of %d): %s', 'eia-fuel-surcharge'),
                    $retry_count + 1,
                    $max_retries,
                    $error_message
                ));
                
                // Retry after a short delay
                $retry_count++;
                if ($retry_count < $max_retries) {
                    sleep(2); // Wait 2 seconds before retrying
                    continue;
                }
                
                return new \WP_Error('api_connection_error', $error_message);
            }
            
            // Get the response code
            $response_code = wp_remote_retrieve_response_code($response);
            
            // Check if the response code indicates success (200)
            if ($response_code === 200) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                // Check if the JSON was decoded successfully
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->logger->log_api_error('JSON decode error: ' . json_last_error_msg());
                    return new \WP_Error('json_error', 'Failed to decode API response');
                }
                
                // Check if the API returned an error
                if (isset($data['error'])) {
                    $this->logger->log_api_error('API returned error: ' . $data['error']['message']);
                    return new \WP_Error('api_error', $data['error']['message']);
                }
                
                // Log successful response
                $this->logger->log_api_success();
                
                return $data;
            }
            
            // Handle different HTTP error codes
            $body = wp_remote_retrieve_body($response);
            
            // Switch based on error code ranges
            switch (true) {
                case $response_code >= 400 && $response_code < 500:
                    // Client errors
                    $error_message = $this->parse_error_message($body, $response_code);
                    $this->logger->log_api_error("Client error ({$response_code}): {$error_message}");
                    
                    // Don't retry client errors as they're likely to be the same
                    return new \WP_Error('api_client_error', $error_message, ['status' => $response_code]);
                    
                case $response_code >= 500:
                    // Server errors - these are retriable
                    $error_message = $this->parse_error_message($body, $response_code);
                    $this->logger->log_api_error(sprintf(
                        __('Server error %d (attempt %d of %d): %s', 'eia-fuel-surcharge'),
                        $response_code,
                        $retry_count + 1,
                        $max_retries,
                        $error_message
                    ));
                    
                    // Retry after a short delay
                    $retry_count++;
                    if ($retry_count < $max_retries) {
                        sleep(3); // Wait 3 seconds before retrying
                        continue;
                    }
                    
                    return new \WP_Error('api_server_error', $error_message, ['status' => $response_code]);
                    
                default:
                    // Unexpected response code
                    $this->logger->log_api_error("Unexpected response code: {$response_code}");
                    return new \WP_Error('api_unexpected_response', "Unexpected response code: {$response_code}");
            }
        }
        
        // This shouldn't be reached, but just in case
        return new \WP_Error('api_max_retries', __('Maximum retry attempts reached', 'eia-fuel-surcharge'));
    }

    /**
     * Parse error message from API response.
     *
     * @since    2.0.0
     * @param    string    $body            The response body.
     * @param    int       $response_code   The HTTP response code.
     * @return   string    The parsed error message.
     */
    private function parse_error_message($body, $response_code) {
        $data = json_decode($body, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($data['error']['message'])) {
            return $data['error']['message'];
        }
        
        // Error messages based on common HTTP status codes
        $error_messages = [
            400 => __('Bad request - The API request was invalid', 'eia-fuel-surcharge'),
            401 => __('Unauthorized - Invalid API key', 'eia-fuel-surcharge'),
            403 => __('Forbidden - You do not have permission to access this resource', 'eia-fuel-surcharge'),
            404 => __('Not found - The requested resource was not found', 'eia-fuel-surcharge'),
            429 => __('Too many requests - You have exceeded the API rate limit', 'eia-fuel-surcharge'),
            500 => __('Internal server error - Something went wrong on the EIA server', 'eia-fuel-surcharge'),
            503 => __('Service unavailable - The EIA service is temporarily unavailable', 'eia-fuel-surcharge'),
        ];
        
        return isset($error_messages[$response_code]) ? 
            $error_messages[$response_code] : 
            sprintf(__('Error response code: %d', 'eia-fuel-surcharge'), $response_code);
    }

    /**
     * Process the API response and prepare data for storage.
     *
     * @since    1.0.0
     * @param    array    $api_data    The API response data.
     * @return   array    The processed diesel price data.
     */
    public function process_diesel_price_data($api_data) {
        $processed_data = [];
        
        // Check if the response has the expected structure
        if (!isset($api_data['response']['data']) || !is_array($api_data['response']['data'])) {
            $this->logger->log('data_processing_error', __('API response does not have the expected structure', 'eia-fuel-surcharge'));
            return $processed_data;
        }
        
        // Process each price entry
        foreach ($api_data['response']['data'] as $entry) {
            // Check for required fields
            if (!isset($entry['period']) || !isset($entry['value'])) {
                $this->logger->log('data_processing_warning', __('Skipping entry due to missing required fields', 'eia-fuel-surcharge'));
                continue;
            }
            
            // Check for valid date format (should be YYYY-MM-DD)
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $entry['period'])) {
                $this->logger->log('data_processing_warning', sprintf(
                    __('Skipping entry with invalid date format: %s', 'eia-fuel-surcharge'),
                    $entry['period']
                ));
                continue;
            }
            
            // Validate value is numeric
            if (!is_numeric($entry['value'])) {
                $this->logger->log('data_processing_warning', sprintf(
                    __('Skipping entry with non-numeric price value: %s', 'eia-fuel-surcharge'),
                    $entry['value']
                ));
                continue;
            }
            
            // Convert date from YYYY-MM-DD format to a timestamp
            $date = strtotime($entry['period']);
            
            // Add the processed data
            $processed_data[] = [
                'date'          => date('Y-m-d', $date),
                'diesel_price'  => floatval($entry['value']),
                'region'        => 'national', // Default to national average
            ];
        }
        
        if (empty($processed_data)) {
            $this->logger->log('data_processing_error', __('No valid data entries found in API response', 'eia-fuel-surcharge'));
        } else {
            $this->logger->log('data_processing_success', sprintf(
                __('Successfully processed %d data entries', 'eia-fuel-surcharge'),
                count($processed_data)
            ));
        }
        
        return $processed_data;
    }

    /**
     * Save the diesel price data to the database.
     *
     * @since    1.0.0
     * @param    array    $price_data    The processed diesel price data.
     * @return   boolean|array   True on success, array with error details on failure.
     */
    public function save_diesel_price_data($price_data) {
        global $wpdb;
        
        if (empty($price_data)) {
            return ['success' => false, 'message' => __('No data to save', 'eia-fuel-surcharge')];
        }
        
        // Get calculator instance
        $calculator = new Calculator();
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_data';
        $success = true;
        $stats = [
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0
        ];
        
        // Begin transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            foreach ($price_data as $data) {
                // Check if this date's data already exists
                $exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_name WHERE price_date = %s AND region = %s",
                    $data['date'],
                    $data['region']
                ));
                
                // Calculate the surcharge rate
                $surcharge_rate = $calculator->calculate_surcharge_rate($data['diesel_price']);
                
                if ($exists) {
                    // Update existing record
                    $updated = $wpdb->update(
                        $table_name,
                        [
                            'diesel_price'   => $data['diesel_price'],
                            'surcharge_rate' => $surcharge_rate
                        ],
                        [
                            'price_date' => $data['date'],
                            'region'     => $data['region']
                        ],
                        ['%f', '%f'],
                        ['%s', '%s']
                    );
                    
                    if ($updated === false) {
                        throw new \Exception(sprintf(
                            __('Failed to update record for date %s and region %s', 'eia-fuel-surcharge'),
                            $data['date'],
                            $data['region']
                        ));
                    } elseif ($updated === 0) {
                        // Record existed but no changes were made
                        $stats['skipped']++;
                    } else {
                        $stats['updated']++;
                    }
                } else {
                    // Insert new record
                    $inserted = $wpdb->insert(
                        $table_name,
                        [
                            'price_date'     => $data['date'],
                            'diesel_price'   => $data['diesel_price'],
                            'surcharge_rate' => $surcharge_rate,
                            'region'         => $data['region'],
                            'created_at'     => current_time('mysql')
                        ],
                        ['%s', '%f', '%f', '%s', '%s']
                    );
                    
                    if ($inserted === false) {
                        throw new \Exception(sprintf(
                            __('Failed to insert new record for date %s and region %s', 'eia-fuel-surcharge'),
                            $data['date'],
                            $data['region']
                        ));
                    } else {
                        $stats['inserted']++;
                    }
                }
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            // Log the success
            $this->logger->log('db_update_success', sprintf(
                __('Database update completed: %d records inserted, %d updated, %d skipped', 'eia-fuel-surcharge'),
                $stats['inserted'],
                $stats['updated'],
                $stats['skipped']
            ));
            
        } catch (\Exception $e) {
            // Rollback transaction on error
            $wpdb->query('ROLLBACK');
            $this->logger->log_db_error($e->getMessage());
            $success = false;
            $stats['errors']++;
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'stats' => $stats
            ];
        }
        
        return [
            'success' => $success,
            'stats' => $stats
        ];
    }

    /**
     * Test the API connection.
     *
     * @since    1.0.0
     * @param    string   $api_key    Optional. The API key to test.
     * @return   array|WP_Error    Array with status and message on success, WP_Error on failure.
     */
    public function test_api_connection($api_key = null) {
        // Use the provided API key or fallback to the stored one
        $test_api_key = $api_key ?: $this->api_key;
        
        // Check if API key is set
        if (empty($test_api_key)) {
            return new \WP_Error('missing_api_key', __('API key is required for testing.', 'eia-fuel-surcharge'));
        }
        
        // Build a minimal request to test the API
        $endpoint = 'petroleum/pri/gnd/data/';
        $params = [
            'api_key'             => $test_api_key,
            'data[]'              => 'value',
            'facets[duoarea][]'   => 'DHHWY',
            'frequency'           => 'weekly',
            'length'              => 1
        ];
        
        $request_url = add_query_arg($params, $this->api_base_url . $endpoint);
        
        // Make the API request
        $response = wp_remote_get($request_url, [
            'timeout'     => 10,
            'user-agent'  => 'EIA Fuel Surcharge WordPress Plugin/' . EIA_FUEL_SURCHARGE_VERSION,
            'headers'     => [
                'Accept' => 'application/json',
            ]
        ]);
        
        // Check for errors
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Check response code
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (isset($data['error']['message'])) {
                return new \WP_Error('api_error', $data['error']['message']);
            } else {
                return new \WP_Error('api_error', 'Error response code: ' . $response_code);
            }
        }
        
        // Parse the successful response to get API version and other details
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Return detailed success information
        return [
            'success' => true,
            'message' => __('API connection successful', 'eia-fuel-surcharge'),
            'api_version' => isset($data['apiVersion']) ? $data['apiVersion'] : __('Unknown', 'eia-fuel-surcharge'),
            'data_available' => !empty($data['response']['data']),
            'response_time' => wp_remote_retrieve_header($response, 'x-response-time'),
        ];
    }

    /**
     * Clear the API cache.
     *
     * @since    2.0.0
     * @return   boolean   True on success, false on failure.
     */
    public function clear_cache() {
        return delete_transient('eia_fuel_surcharge_api_data');
    }

    /**
     * Get API status information.
     *
     * @since    2.0.0
     * @return   array   The API status information.
     */
    public function get_api_status() {
        $status = [
            'has_api_key' => !empty($this->api_key),
            'cache_enabled' => true,
            'cache_duration' => $this->cache_duration,
            'cache_expiry' => null,
            'last_updated' => null,
            'last_error' => null,
        ];
        
        // Check if data is cached
        $cached_data = get_transient('eia_fuel_surcharge_api_data');
        if ($cached_data !== false) {
            $status['cache_exists'] = true;
            
            // Get cache expiration time
            $transients_option = get_option('_transient_timeout_eia_fuel_surcharge_api_data');
            if ($transients_option) {
                $status['cache_expiry'] = date_i18n(
                    get_option('date_format') . ' ' . get_option('time_format'),
                    $transients_option
                );
                
                // Calculate time until expiry
                $now = time();
                $seconds_to_expiry = $transients_option - $now;
                if ($seconds_to_expiry > 0) {
                    $status['cache_time_remaining'] = $this->format_time_remaining($seconds_to_expiry);
                } else {
                    $status['cache_time_remaining'] = __('Expired', 'eia-fuel-surcharge');
                }
            }
        } else {
            $status['cache_exists'] = false;
        }
        
        // Get latest log entries
        global $wpdb;
        $log_table = $wpdb->prefix . 'fuel_surcharge_logs';
        
        // Get last successful update
        $last_success = $wpdb->get_row(
            "SELECT created_at FROM $log_table 
            WHERE log_type = 'update_success' 
            ORDER BY created_at DESC LIMIT 1"
        );
        
        if ($last_success) {
            $status['last_updated'] = date_i18n(
                get_option('date_format') . ' ' . get_option('time_format'),
                strtotime($last_success->created_at)
            );
        }
        
        // Get last error
        $last_error = $wpdb->get_row(
            "SELECT message, created_at FROM $log_table 
            WHERE log_type IN ('api_error', 'update_error', 'db_error') 
            ORDER BY created_at DESC LIMIT 1"
        );
        
        if ($last_error) {
            $status['last_error'] = [
                'message' => $last_error->message,
                'time' => date_i18n(
                    get_option('date_format') . ' ' . get_option('time_format'),
                    strtotime($last_error->created_at)
                )
            ];
        }
        
        return $status;
    }

    /**
     * Format time remaining in a human-readable format.
     *
     * @since    2.0.0
     * @param    int       $seconds    Time remaining in seconds.
     * @return   string    Formatted time.
     */
    private function format_time_remaining($seconds) {
        if ($seconds < 60) {
            return sprintf(_n('%d second', '%d seconds', $seconds, 'eia-fuel-surcharge'), $seconds);
        }
        
        $minutes = floor($seconds / 60);
        if ($minutes < 60) {
            return sprintf(_n('%d minute', '%d minutes', $minutes, 'eia-fuel-surcharge'), $minutes);
        }
        
        $hours = floor($minutes / 60);
        $minutes = $minutes % 60;
        
        if ($hours < 24) {
            if ($minutes > 0) {
                return sprintf(
                    __('%d hour(s) and %d minute(s)', 'eia-fuel-surcharge'),
                    $hours,
                    $minutes
                );
            }
            return sprintf(_n('%d hour', '%d hours', $hours, 'eia-fuel-surcharge'), $hours);
        }
        
        $days = floor($hours / 24);
        $hours = $hours % 24;
        
        if ($hours > 0) {
            return sprintf(
                __('%d day(s) and %d hour(s)', 'eia-fuel-surcharge'),
                $days,
                $hours
            );
        }
        
        return sprintf(_n('%d day', '%d days', $days, 'eia-fuel-surcharge'), $days);
    }
}