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
            var $spinner = $button.parent().find('.spinner');
            var $resultsContainer = $('#manual-update-results');
            var originalText = $button.text();
            
            // Clear previous results
            $resultsContainer.removeClass('success error').empty();
            
            // Disable the button and show loading state
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            $resultsContainer.addClass('loading').html('<p>' + (eia_fuel_surcharge_params.i18n.updating || 'Updating...') + '...</p>');
            
            // Send AJAX request
            $.ajax({
                url: eia_fuel_surcharge_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'eia_fuel_surcharge_manual_update_ajax',
                    nonce: eia_fuel_surcharge_params.manual_update_nonce
                },
                success: function(response) {
                    // Clear the loading class
                    $resultsContainer.removeClass('loading');
                    
                    // Check if response is valid
                    if (response && typeof response === 'object') {
                        if (response.success) {
                            // Success case
                            $resultsContainer.addClass('success').empty();
                            
                            var html = '<h3>' + eia_fuel_surcharge_params.i18n.update_success + '</h3>';
                            html += '<p>' + (response.data.message || 'Data updated successfully.') + '</p>';
                            
                            // Add stats if available
                            if (response.data.stats) {
                                html += '<div class="eia-update-stats">';
                                html += '<h4>' + eia_fuel_surcharge_params.i18n.update_stats + '</h4>';
                                html += '<ul>';
                                if (response.data.stats.inserted > 0) {
                                    html += '<li>' + response.data.stats.inserted + ' ' + eia_fuel_surcharge_params.i18n.records_inserted + '</li>';
                                }
                                if (response.data.stats.updated > 0) {
                                    html += '<li>' + response.data.stats.updated + ' ' + eia_fuel_surcharge_params.i18n.records_updated + '</li>';
                                }
                                if (response.data.stats.skipped > 0) {
                                    html += '<li>' + response.data.stats.skipped + ' ' + eia_fuel_surcharge_params.i18n.records_skipped + '</li>';
                                }
                                html += '</ul>';
                                html += '</div>';
                            }
                            
                            $resultsContainer.html(html).show();
                            
                            // Reload the page after short delay to show updated data
                            setTimeout(function() {
                                window.location.reload();
                            }, 3000);
                        } else {
                            // Error case
                            $resultsContainer.addClass('error').empty();
                            
                            var html = '<h3>' + eia_fuel_surcharge_params.i18n.update_failed + '</h3>';
                            html += '<p>' + (response.data.message || 'Unknown error occurred.') + '</p>';
                            
                            // Add debug info if available
                            if (response.data.debug) {
                                html += '<div class="eia-debug-info">';
                                html += '<p><a href="#" class="eia-toggle-debug-info">' + eia_fuel_surcharge_params.i18n.show_debug + '</a></p>';
                                html += '<div class="eia-debug-info-content" style="display:none;">';
                                html += '<pre>' + JSON.stringify(response.data.debug, null, 2) + '</pre>';
                                html += '</div>';
                                html += '</div>';
                            }
                            
                            $resultsContainer.html(html).show();
                            
                            // Add toggle functionality for debug info
                            $resultsContainer.find('.eia-toggle-debug-info').on('click', function(e) {
                                e.preventDefault();
                                $(this).closest('.eia-debug-info').find('.eia-debug-info-content').slideToggle();
                            });
                        }
                    } else {
                        // Invalid response
                        $resultsContainer.addClass('error').html(
                            '<h3>' + eia_fuel_surcharge_params.i18n.update_failed + '</h3>' +
                            '<p>Received invalid response from server</p>'
                        ).show();
                    }
                },
                error: function(xhr, status, error) {
                    // Handle AJAX errors
                    $resultsContainer.removeClass('loading').addClass('error').html(
                        '<h3>' + eia_fuel_surcharge_params.i18n.update_failed + '</h3>' +
                        '<p>' + eia_fuel_surcharge_params.i18n.ajax_error + ': ' + error + '</p>'
                    ).show();
                },
                complete: function() {
                    // Re-enable the button and reset spinner
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
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