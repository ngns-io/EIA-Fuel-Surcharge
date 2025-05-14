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
     * Cache key prefix.
     *
     * @since    2.0.0
     * @access   private
     * @var      string    $cache_prefix    Cache key prefix.
     */
    private $cache_prefix = 'eia_fuel_surcharge_';

    /**
     * Maximum retry attempts for API calls.
     *
     * @since    2.0.0
     * @access   private
     * @var      int    $max_retries    Maximum retry attempts.
     */
    private $max_retries = 3;

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
        
        // Set max retries from settings if available
        if (isset($options['max_retries'])) {
            $this->max_retries = intval($options['max_retries']);
        }
    }

    /**
     * Get cache key for a specific endpoint and parameters.
     *
     * @since    2.0.0
     * @param    string    $endpoint    The API endpoint.
     * @param    array     $params      The query parameters.
     * @return   string    The cache key.
     */
    private function get_cache_key($endpoint, $params) {
        // Remove the API key from params for security
        if (isset($params['api_key'])) {
            unset($params['api_key']);
        }
        
        // Sort params to ensure consistent cache keys
        ksort($params);
        
        // Create a hash of the endpoint and parameters
        $param_string = json_encode($params);
        $hash = md5($endpoint . $param_string);
        
        // Return prefixed cache key
        return $this->cache_prefix . $hash;
    }

    /**
     * Get cached API response if available.
     *
     * @since    2.0.0
     * @param    string    $endpoint    The API endpoint.
     * @param    array     $params      The query parameters.
     * @return   mixed     The cached data or false if not cached.
     */
    private function get_cached_data($endpoint, $params) {
        $cache_key = $this->get_cache_key($endpoint, $params);
        return get_transient($cache_key);
    }

    /**
     * Cache API response data.
     *
     * @since    2.0.0
     * @param    string    $endpoint    The API endpoint.
     * @param    array     $params      The query parameters.
     * @param    mixed     $data        The data to cache.
     * @return   bool      True on success, false on failure.
     */
    private function cache_data($endpoint, $params, $data) {
        $cache_key = $this->get_cache_key($endpoint, $params);
        return set_transient($cache_key, $data, $this->cache_duration);
    }

    /**
     * Clear cached API response for a specific endpoint and parameters.
     *
     * @since    2.0.0
     * @param    string    $endpoint    The API endpoint.
     * @param    array     $params      The query parameters.
     * @return   bool      True on success, false on failure.
     */
    private function clear_cached_data($endpoint, $params) {
        $cache_key = $this->get_cache_key($endpoint, $params);
        return delete_transient($cache_key);
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

        // Build the API endpoint URL for on-highway diesel prices
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

        // Check cache first if not forcing refresh
        if (!$force_refresh) {
            $cached_data = $this->get_cached_data($endpoint, $params);
            if ($cached_data !== false) {
                $this->logger->log('cache_hit', __('Using cached API data', 'eia-fuel-surcharge'));
                return $cached_data;
            }
        }
        
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
        $this->cache_data($endpoint, $params, $response);
        
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
        $max_retries = $this->max_retries;
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
            
            // Check error code ranges using if-else instead of switch
            if ($response_code >= 400 && $response_code < 500) {
                // Client errors
                $error_message = $this->parse_error_message($body, $response_code);
                $this->logger->log_api_error("Client error ({$response_code}): {$error_message}");
                
                // Don't retry client errors as they're likely to be the same
                return new \WP_Error('api_client_error', $error_message, ['status' => $response_code]);
            } elseif ($response_code >= 500) {
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
            } else {
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
     * Test the API connection with detailed feedback.
     *
     * @since    2.0.0
     * @param    string   $api_key    Optional. The API key to test.
     * @return   array|WP_Error    Array with status and message on success, WP_Error on failure.
     */
    public function test_api_connection($api_key = null) {
        // Use the provided API key or fallback to the stored one
        $test_api_key = $api_key ?: $this->api_key;
        
        // Check if API key is set
        if (empty($test_api_key)) {
            return new \WP_Error(
                'missing_api_key', 
                __('API key is required for testing.', 'eia-fuel-surcharge')
            );
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
        
        // Log the test attempt
        $this->logger->log('api_test', 'Testing API connection');
        
        // Start timing the request
        $start_time = microtime(true);
        
        // Make the API request
        $response = wp_remote_get($request_url, [
            'timeout'     => 10,
            'user-agent'  => 'EIA Fuel Surcharge WordPress Plugin/' . EIA_FUEL_SURCHARGE_VERSION,
            'headers'     => [
                'Accept' => 'application/json',
            ]
        ]);
        
        // Calculate response time
        $response_time = round((microtime(true) - $start_time) * 1000); // in milliseconds
        
        // Check for errors
        if (is_wp_error($response)) {
            $this->logger->log_api_error(
                'API connection test failed: ' . $response->get_error_message(),
                ['response_time' => $response_time . 'ms']
            );
            return $response;
        }
        
        // Check response code
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($response_code !== 200) {
            $error_message = isset($data['error']['message']) 
                ? $data['error']['message'] 
                : 'Error response code: ' . $response_code;
                
            $this->logger->log_api_error(
                'API connection test failed: ' . $error_message,
                [
                    'response_code' => $response_code,
                    'response_time' => $response_time . 'ms'
                ]
            );
            
            return new \WP_Error('api_error', $error_message);
        }
        
        // Log success
        $this->logger->log_api_success([
            'response_time' => $response_time . 'ms',
            'response_code' => $response_code
        ]);
        
        // Return detailed success information
        return [
            'success' => true,
            'message' => __('API connection successful', 'eia-fuel-surcharge'),
            'api_version' => isset($data['apiVersion']) ? $data['apiVersion'] : __('Unknown', 'eia-fuel-surcharge'),
            'data_available' => !empty($data['response']['data']),
            'response_time' => $response_time . 'ms',
            'response_code' => $response_code,
            'sample_data' => !empty($data['response']['data']) ? array_slice($data['response']['data'], 0, 1) : [],
        ];
    }

    /**
     * Clear the API cache.
     *
     * @since    2.0.0
     * @return   boolean   True on success, false on failure.
     */
    public function clear_cache() {
        return $this->clear_all_cache();
    }

    /**
     * Clear all cached API data.
     *
     * @since    2.0.0
     * @return   bool    Always returns true.
     */
    public function clear_all_cache() {
        global $wpdb;
        
        // Get all transients that match our prefix
        $prefix = '_transient_' . $this->cache_prefix;
        $sql = $wpdb->prepare(
            "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s",
            $wpdb->esc_like($prefix) . '%'
        );
        
        $transients = $wpdb->get_col($sql);
        
        // Delete each matching transient
        foreach ($transients as $transient) {
            $transient_name = str_replace('_transient_', '', $transient);
            delete_transient($transient_name);
        }
        
        $this->logger->log('cache_clear', __('All API cache cleared', 'eia-fuel-surcharge'));
        
        return true;
    }

    /**
     * Get API status information.
     *
     * @since    2.0.0
     * @return   array   The API status information.
     */
    public function get_api_status() {
        global $wpdb;
        
        // Count the number of cached items
        $prefix = '_transient_' . $this->cache_prefix;
        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE %s",
            $wpdb->esc_like($prefix) . '%'
        );
        
        $cache_count = (int)$wpdb->get_var($sql);
        
        $status = [
            'has_api_key' => !empty($this->api_key),
            'cache_enabled' => true,
            'cache_duration' => $this->cache_duration,
            'cache_count' => $cache_count,
            'max_retries' => $this->max_retries,
            'last_updated' => null,
            'last_error' => null,
        ];
        
        // Get latest log entries
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
        
        // Test API connection
        if ($status['has_api_key']) {
            // Use cached test result if available to avoid too many API calls
            $test_cache_key = $this->cache_prefix . 'api_test_result';
            $cached_test = get_transient($test_cache_key);
            
            if ($cached_test === false) {
                $test_result = $this->test_api_connection();
                
                // Cache the test result for 1 hour
                set_transient($test_cache_key, $test_result, HOUR_IN_SECONDS);
                
                $status['api_test'] = $test_result;
            } else {
                $status['api_test'] = $cached_test;
                $status['api_test']['from_cache'] = true;
            }
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

/**
     * Check if the API key is valid.
     *
     * @since    2.0.0
     * @return   bool    True if the API key is valid, false otherwise.
     */
    public function is_api_key_valid() {
        if (empty($this->api_key)) {
            return false;
        }
        
        // Check cache first
        $cache_key = $this->cache_prefix . 'api_key_valid';
        $cached_result = get_transient($cache_key);
        
        if ($cached_result !== false) {
            return (bool) $cached_result;
        }
        
        // Test the API connection
        $test_result = $this->test_api_connection();
        $is_valid = !is_wp_error($test_result) && isset($test_result['success']) && $test_result['success'] === true;
        
        // Cache the result for 1 day
        set_transient($cache_key, $is_valid, DAY_IN_SECONDS);
        
        return $is_valid;
    }

    /**
     * Get the API usage statistics.
     *
     * @since    2.0.0
     * @param    int       $days    Number of days to look back.
     * @return   array     API usage statistics.
     */
    public function get_api_usage_stats($days = 30) {
        global $wpdb;
        
        $log_table = $wpdb->prefix . 'fuel_surcharge_logs';
        $stats = [];
        
        // Get start date
        $start_date = date('Y-m-d', strtotime("-{$days} days"));
        
        // Get total requests
        $total_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $log_table 
            WHERE log_type = 'api_request' 
            AND created_at >= %s",
            $start_date . ' 00:00:00'
        ));
        
        $stats['total_requests'] = (int) $total_requests;
        
        // Get successful requests
        $successful_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $log_table 
            WHERE log_type = 'api_success' 
            AND created_at >= %s",
            $start_date . ' 00:00:00'
        ));
        
        $stats['successful_requests'] = (int) $successful_requests;
        
        // Get error requests
        $error_requests = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $log_table 
            WHERE log_type = 'api_error' 
            AND created_at >= %s",
            $start_date . ' 00:00:00'
        ));
        
        $stats['error_requests'] = (int) $error_requests;
        
        // Get cache hits
        $cache_hits = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $log_table 
            WHERE log_type = 'cache_hit' 
            AND created_at >= %s",
            $start_date . ' 00:00:00'
        ));
        
        $stats['cache_hits'] = (int) $cache_hits;
        
        // Calculate success rate
        $stats['success_rate'] = $stats['total_requests'] > 0 
            ? round(($stats['successful_requests'] / $stats['total_requests']) * 100, 2) 
            : 0;
            
        // Calculate cache hit rate
        $total_lookups = $stats['total_requests'] + $stats['cache_hits'];
        $stats['cache_hit_rate'] = $total_lookups > 0 
            ? round(($stats['cache_hits'] / $total_lookups) * 100, 2) 
            : 0;
        
        // Get requests by day
        $requests_by_day = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
            FROM $log_table 
            WHERE log_type = 'api_request' 
            AND created_at >= %s 
            GROUP BY DATE(created_at) 
            ORDER BY date ASC",
            $start_date . ' 00:00:00'
        ), ARRAY_A);
        
        // Create a complete date range
        $stats['requests_by_day'] = [];
        $current_date = new \DateTime($start_date);
        $end_date = new \DateTime();
        
        while ($current_date <= $end_date) {
            $date_string = $current_date->format('Y-m-d');
            $stats['requests_by_day'][$date_string] = 0;
            $current_date->modify('+1 day');
        }
        
        // Fill in actual request counts
        foreach ($requests_by_day as $row) {
            $stats['requests_by_day'][$row['date']] = (int) $row['count'];
        }
        
        return $stats;
    }

    /**
     * Get regional diesel prices.
     *
     * @since    2.0.0
     * @param    bool      $force_refresh    Whether to force a refresh from the API.
     * @return   array|WP_Error    The regional diesel price data or WP_Error on failure.
     */
    public function get_regional_diesel_prices($force_refresh = false) {
        // Check if API key is set
        if (empty($this->api_key)) {
            return new \WP_Error('missing_api_key', __('EIA API key is missing. Please enter it in the plugin settings.', 'eia-fuel-surcharge'));
        }

        // Build the API endpoint URL for on-highway diesel prices by region
        $endpoint = 'petroleum/pri/gnd/data/';
        
        // Set query parameters for regional data
        $params = [
            'api_key'             => $this->api_key,
            'data[]'              => 'value',
            'frequency'           => 'weekly',
            'sort[0][column]'     => 'period',
            'sort[0][direction]'  => 'desc',
            'offset'              => 0,
            'length'              => 10 // Get the 10 most recent records for each region
        ];
        
        // Define regions to fetch
        $regions = [
            'DHUSR' => 'national',  // US
            'DHR10' => 'east_coast', // East Coast
            'DHR1Z' => 'new_england', // New England
            'DHR1Y' => 'central_atlantic', // Central Atlantic
            'DHR1X' => 'lower_atlantic', // Lower Atlantic
            'DHR20' => 'midwest', // Midwest
            'DHR30' => 'gulf_coast', // Gulf Coast
            'DHR40' => 'rocky_mountain', // Rocky Mountain
            'DHR50' => 'west_coast', // West Coast
            'DHR5CA' => 'california' // California
        ];
        
        $all_data = [];
        
        foreach ($regions as $region_code => $region_name) {
            // Set region-specific parameter
            $region_params = $params;
            $region_params['facets[duoarea][]'] = $region_code;
            
            // Check cache first if not forcing refresh
            if (!$force_refresh) {
                $cached_data = $this->get_cached_data($endpoint, $region_params);
                if ($cached_data !== false) {
                    $all_data[$region_name] = $cached_data;
                    continue;
                }
            }
            
            // Build the request URL
            $request_url = add_query_arg($region_params, $this->api_base_url . $endpoint);
            
            // Log the request (for debugging)
            $this->logger->log_api_request($request_url);
            
            // Make the API request with enhanced error handling
            $response = $this->make_api_request($request_url);
            
            // If there was an error, log it but continue with other regions
            if (is_wp_error($response)) {
                $this->logger->log_api_error(sprintf(
                    __('Error fetching data for region %s: %s', 'eia-fuel-surcharge'),
                    $region_name,
                    $response->get_error_message()
                ));
                continue;
            }
            
            // Cache the successful response
            $this->cache_data($endpoint, $region_params, $response);
            
            // Add to the combined data
            $all_data[$region_name] = $response;
        }
        
        // Check if we got any data
        if (empty($all_data)) {
            return new \WP_Error('no_data', __('No data was retrieved for any region', 'eia-fuel-surcharge'));
        }
        
        return $all_data;
    }

    /**
     * Process and store regional diesel price data.
     *
     * @since    2.0.0
     * @param    array     $regional_data    The regional diesel price data.
     * @return   array     Result of the operation.
     */
    public function process_and_store_regional_data($regional_data) {
        // Get calculator instance
        $calculator = new Calculator();
        
        // Combined stats for all regions
        $total_stats = [
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0
        ];
        
        foreach ($regional_data as $region_name => $api_data) {
            // Process the API response for this region
            $processed_data = [];
            
            // Check if the response has the expected structure
            if (!isset($api_data['response']['data']) || !is_array($api_data['response']['data'])) {
                $this->logger->log('data_processing_error', sprintf(
                    __('API response for region %s does not have the expected structure', 'eia-fuel-surcharge'),
                    $region_name
                ));
                continue;
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
                    'region'        => $region_name,
                ];
            }
            
            if (empty($processed_data)) {
                $this->logger->log('data_processing_warning', sprintf(
                    __('No valid data entries found in API response for region %s', 'eia-fuel-surcharge'),
                    $region_name
                ));
                continue;
            }
            
            // Save the processed data for this region
            $save_result = $this->save_diesel_price_data($processed_data);
            
            if (isset($save_result['stats'])) {
                $total_stats['inserted'] += $save_result['stats']['inserted'];
                $total_stats['updated'] += $save_result['stats']['updated'];
                $total_stats['skipped'] += $save_result['stats']['skipped'];
                $total_stats['errors'] += $save_result['stats']['errors'];
            }
        }
        
        // Log overall results
        $this->logger->log('regional_data_update', sprintf(
            __('Regional data update completed: %d inserted, %d updated, %d skipped, %d errors', 'eia-fuel-surcharge'),
            $total_stats['inserted'],
            $total_stats['updated'],
            $total_stats['skipped'],
            $total_stats['errors']
        ));
        
        return [
            'success' => ($total_stats['errors'] === 0),
            'stats' => $total_stats
        ];
    }

    /**
     * Get the latest data for all regions.
     *
     * @since    2.0.0
     * @return   array    Latest diesel price data by region.
     */
    public function get_latest_data_by_region() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_data';
        
        // Get the latest date for which we have data
        $latest_date = $wpdb->get_var("SELECT MAX(price_date) FROM $table_name");
        
        if (!$latest_date) {
            return [];
        }
        
        // Get data for all regions for this date
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE price_date = %s ORDER BY region ASC",
                $latest_date
            ),
            ARRAY_A
        );
        
        // Index by region
        $by_region = [];
        foreach ($results as $row) {
            $by_region[$row['region']] = $row;
        }
        
        return $by_region;
    }

    /**
     * Get historical data for a specific region.
     *
     * @since    2.0.0
     * @param    string    $region       The region to get data for.
     * @param    int       $limit        Maximum number of records to return.
     * @param    string    $start_date   Optional start date (YYYY-MM-DD).
     * @param    string    $end_date     Optional end date (YYYY-MM-DD).
     * @return   array     The historical data.
     */
    public function get_historical_data($region = 'national', $limit = 52, $start_date = null, $end_date = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fuel_surcharge_data';
        
        // Build the query
        $query = "SELECT * FROM $table_name WHERE region = %s";
        $query_args = [$region];
        
        // Add date range if provided
        if ($start_date) {
            $query .= " AND price_date >= %s";
            $query_args[] = $start_date;
        }
        
        if ($end_date) {
            $query .= " AND price_date <= %s";
            $query_args[] = $end_date;
        }
        
        // Add order and limit
        $query .= " ORDER BY price_date DESC";
        
        if ($limit > 0) {
            $query .= " LIMIT %d";
            $query_args[] = $limit;
        }
        
        // Prepare and execute the query
        $query = $wpdb->prepare($query, $query_args);
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Sort by date (oldest first) for easier charting
        usort($results, function($a, $b) {
            return strtotime($a['price_date']) - strtotime($b['price_date']);
        });
        
        return $results;
    }
}