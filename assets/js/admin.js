/**
 * Admin JavaScript for EIA Fuel Surcharge Display
 */

(function($) {
    'use strict';

    /**
     * When the DOM is ready
     */
    $(function() {
        // API Key testing
        $('#test-api-key').on('click', function(e) {
            e.preventDefault();
            var apiKey = $('#api_key').val();
            var $button = $(this);
            var originalText = $button.text();

            // Check if API key is provided
            if (!apiKey) {
                alert(eia_fuel_surcharge_params.i18n.enter_api_key);
                return;
            }

            // Disable button and show loading state
            $button.prop('disabled', true).text(eia_fuel_surcharge_params.i18n.testing);

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
                    if (response.success) {
                        alert(eia_fuel_surcharge_params.i18n.api_test_success);
                    } else {
                        alert(eia_fuel_surcharge_params.i18n.api_test_error + ': ' + response.data.message);
                    }
                },
                error: function() {
                    alert(eia_fuel_surcharge_params.i18n.ajax_error);
                },
                complete: function() {
                    // Re-enable button and restore original text
                    $button.prop('disabled', false).text(originalText);
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