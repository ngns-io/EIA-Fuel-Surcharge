/**
 * Admin JavaScript for EIA Fuel Surcharge Display
 */

(function($) {
    'use strict';

    /**
     * When the DOM is ready
     */
    $(function() {
        // API Key testing with visual feedback
        $('#test-api-key').on('click', function(e) {
            e.preventDefault();
            var apiKey = $('#api_key').val();
            var $button = $(this);
            var $buttonText = $button.find('.button-text');
            var $spinner = $button.find('.spinner');
            var $resultsContainer = $('#api-test-results');
            var originalText = $buttonText.text();

            // Check if API key is provided
            if (!apiKey) {
                $resultsContainer.html('<div class="notice notice-error inline"><p>' + eia_fuel_surcharge_params.i18n.enter_api_key + '</p></div>');
                return;
            }

            // Clear previous results
            $resultsContainer.empty();
            
            // Show loading state
            $button.prop('disabled', true);
            $buttonText.text(eia_fuel_surcharge_params.i18n.testing);
            $spinner.addClass('is-active');
            
            // Add initial loading message to the results container
            $resultsContainer.html('<div class="eia-loading-message"><p>' + eia_fuel_surcharge_params.i18n.testing + '...</p></div>');

            // Send AJAX request
            $.ajax({
                url: eia_fuel_surcharge_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'eia_fuel_surcharge_test_api',
                    nonce: eia_fuel_surcharge_params.nonce,
                    api_key: apiKey
                },
                success: function(response) {
                    // Clear the loading message
                    $resultsContainer.empty();
                    
                    if (response.success) {
                        // Success case
                        $resultsContainer.html(response.data.html || '<div class="notice notice-success inline"><p>API connection successful!</p></div>');
                    } else {
                        // Error case
                        $resultsContainer.html(response.data.html || '<div class="notice notice-error inline"><p>' + (response.data.message || 'Error testing API connection') + '</p></div>');
                    }
                    
                    // Add toggle functionality for debug info
                    $resultsContainer.find('.eia-toggle-debug-info').on('click', function(e) {
                        e.preventDefault();
                        $(this).closest('.eia-api-debug-info').find('.eia-debug-info-content').slideToggle();
                    });
                },
                error: function(xhr, status, error) {
                    // Handle AJAX errors
                    $resultsContainer.html('<div class="notice notice-error inline"><p>' + eia_fuel_surcharge_params.i18n.ajax_error + ': ' + error + '</p></div>');
                },
                complete: function() {
                    // Reset button state
                    $button.prop('disabled', false);
                    $buttonText.text(originalText);
                    $spinner.removeClass('is-active');
                }
            });
        });

        // Update frequency change handler
        $('#update_frequency').on('change', function() {
            var frequency = $(this).val();
            
            // Show/hide appropriate fields based on frequency
            if (frequency === 'weekly') {
                $('.update-day-field').show();
                $('.update-day-of-month-field').hide();
                $('.custom-interval-field').hide();
            } else if (frequency === 'monthly') {
                $('.update-day-field').hide();
                $('.update-day-of-month-field').show();
                $('.custom-interval-field').hide();
            } else if (frequency === 'custom') {
                $('.update-day-field').hide();
                $('.update-day-of-month-field').hide();
                $('.custom-interval-field').show();
            } else {
                // Daily
                $('.update-day-field').hide();
                $('.update-day-of-month-field').hide();
                $('.custom-interval-field').hide();
            }
        });

        // Trigger change event to set initial state
        $('#update_frequency').trigger('change');

        // Confirmation for clearing data
        $('.eia-fuel-surcharge-clear-data').on('click', function(e) {
            if (!confirm(eia_fuel_surcharge_params.i18n.confirm_clear_data)) {
                e.preventDefault();
            }
        });

        // Confirmation for clearing logs
        $('.eia-fuel-surcharge-clear-logs').on('click', function(e) {
            if (!confirm(eia_fuel_surcharge_params.i18n.confirm_clear_logs)) {
                e.preventDefault();
            }
        });

        // Manual update button handler with proper error handling and detailed debug info
        $('#manual-update-button').on('click', function() {
            var $button = $(this);
            var $resultsContainer = $('#manual-update-results');
            var originalText = $button.text();
            
            // Clear previous results
            $resultsContainer.empty();
            
            // Disable the button and show loading state
            $button.prop('disabled', true).text(eia_fuel_surcharge_params.i18n.updating || 'Updating...');
            
            // Add initial loading message to the results container
            $resultsContainer.html('<div class="eia-loading-message"><p>' + (eia_fuel_surcharge_params.i18n.updating || 'Updating...') + '...</p></div>');
            
            // Send AJAX request
            $.ajax({
                url: ajaxurl, // WordPress AJAX URL
                type: 'POST',
                data: {
                    action: 'eia_fuel_surcharge_manual_update_ajax',
                    nonce: eia_fuel_surcharge_params.manual_update_nonce // Use the correct nonce
                },
                success: function(response) {
                    // Clear the loading message
                    $resultsContainer.empty();
                    
                    // Check if response is valid
                    if (response && typeof response === 'object') {
                        if (response.success) {
                            // Success case
                            $resultsContainer.html('<div class="notice notice-success inline">' +
                                '<p><strong>' + (response.data.message || 'Update successful!') + '</strong></p>' +
                                '</div>');
                        } else {
                            // Error case
                            var errorMessage = 'Update failed.';
                            
                            if (response.data && typeof response.data === 'object' && response.data.message) {
                                errorMessage = response.data.message;
                            } else if (response.data && typeof response.data === 'string') {
                                errorMessage = response.data;
                            }
                            
                            // Create detailed error display
                            var html = '<div class="notice notice-error inline">' +
                                '<p><strong>Update failed</strong></p>' +
                                '<p>' + errorMessage + '</p>' +
                                '</div>';
                            
                            // Add debug info if available
                            if (response.data && response.data.debug) {
                                html += '<div class="eia-api-debug-info">' +
                                    '<p><a href="#" class="eia-toggle-debug-info">Show/Hide Debug Information</a></p>' +
                                    '<div class="eia-debug-info-content" style="display:none;">' +
                                    '<pre>' + JSON.stringify(response.data.debug, null, 2) + '</pre>' +
                                    '</div></div>';
                            }
                            
                            $resultsContainer.html(html);
                            
                            // Add toggle functionality for debug info
                            $resultsContainer.find('.eia-toggle-debug-info').on('click', function(e) {
                                e.preventDefault();
                                $(this).closest('.eia-api-debug-info').find('.eia-debug-info-content').slideToggle();
                            });
                        }
                    } else {
                        // Invalid response
                        $resultsContainer.html('<div class="notice notice-error inline">' +
                            '<p>Received invalid response from server</p>' +
                            '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    // Handle AJAX errors
                    $resultsContainer.html('<div class="notice notice-error inline">' +
                        '<p>AJAX Error: ' + error + '</p>' +
                        '</div>');
                },
                complete: function() {
                    // Re-enable the button and restore original text
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });

        // Tabs functionality for settings page
        if ($('.eia-fuel-surcharge-tabs').length) {
            $('.eia-fuel-surcharge-tabs a').on('click', function(e) {
                e.preventDefault();
                
                // Get the target tab
                var target = $(this).attr('href').substring(1);
                
                // Update active tab
                $('.eia-fuel-surcharge-tabs a').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Show the target tab content, hide others
                $('.eia-fuel-surcharge-tab-content').hide();
                $('#' + target).show();
                
                // Store the active tab in localStorage
                if (typeof(Storage) !== "undefined") {
                    localStorage.setItem('eia_fuel_surcharge_active_tab', target);
                }
            });
            
            // Check if we have a stored active tab
            if (typeof(Storage) !== "undefined" && localStorage.getItem('eia_fuel_surcharge_active_tab')) {
                var activeTab = localStorage.getItem('eia_fuel_surcharge_active_tab');
                $('.eia-fuel-surcharge-tabs a[href="#' + activeTab + '"]').trigger('click');
            } else {
                // Default to first tab
                $('.eia-fuel-surcharge-tabs a:first').trigger('click');
            }
        }

        // Date format preview
        $('#date_format').on('input', function() {
            var format = $(this).val();
            if (format) {
                $.ajax({
                    url: eia_fuel_surcharge_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'eia_fuel_surcharge_preview_date_format',
                        nonce: eia_fuel_surcharge_params.nonce,
                        format: format
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#date-format-preview').text(response.data.preview);
                        }
                    }
                });
            }
        });

        // Text format preview
        $('#text_format').on('input', function() {
            var format = $(this).val();
            if (format) {
                $.ajax({
                    url: eia_fuel_surcharge_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'eia_fuel_surcharge_preview_text_format',
                        nonce: eia_fuel_surcharge_params.nonce,
                        format: format
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#text-format-preview').html(response.data.preview);
                        }
                    }
                });
            }
        });

        // Initialize tooltips
        if ($.fn.tooltip) {
            $('.eia-fuel-surcharge-help-tip').tooltip({
                content: function() {
                    return $(this).prop('title');
                },
                position: {
                    my: 'center bottom-5',
                    at: 'center top',
                    collision: 'flipfit'
                },
                show: {
                    duration: 100
                },
                hide: {
                    duration: 100
                },
                tooltipClass: 'eia-fuel-surcharge-tooltip'
            });
        }

        // Filter logs functionality
        $('#eia-fuel-surcharge-log-filter').on('submit', function(e) {
            // Don't need to do anything special here, just let the form submit normally
        });

        // Clear filter button
        $('#eia-fuel-surcharge-clear-filter').on('click', function(e) {
            e.preventDefault();
            window.location.href = window.location.pathname + '?page=' + $(this).data('page');
        });
    });

})(jQuery);