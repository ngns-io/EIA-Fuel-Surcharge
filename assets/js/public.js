/**
 * Public JavaScript for EIA Fuel Surcharge Display
 */

(function($) {
    'use strict';

    /**
     * When the DOM is ready
     */
    $(function() {
        // Make tables responsive
        $('.fuel-surcharge-table-container table').each(function() {
            // First, check if the table is already wrapped
            if (!$(this).parent().hasClass('fuel-surcharge-table-container')) {
                // Wrap the table in a responsive container if it's not already wrapped
                $(this).wrap('<div class="fuel-surcharge-table-container"></div>');
            }
        });

        // Add zebra-striping to tables if not already present
        $('.fuel-surcharge-table:not(.striped)').each(function() {
            $(this).find('tbody tr:odd').addClass('odd');
        });

        // Initialize tooltips for surcharge info
        if ($.fn.tooltip) {
            $('.fuel-surcharge-info').tooltip({
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
                tooltipClass: 'fuel-surcharge-tooltip'
            });
        }

        // Handle region selection in tables
        $('.fuel-surcharge-region-selector').on('change', function() {
            var region = $(this).val();
            var tableId = $(this).data('table-id');
            var tableContainer = $('#' + tableId);
            
            // Show loading indicator
            if (tableContainer.find('.fuel-surcharge-loading').length === 0) {
                tableContainer.append('<div class="fuel-surcharge-loading">' + 
                    '<span class="spinner"></span> Loading data...</div>');
            } else {
                tableContainer.find('.fuel-surcharge-loading').show();
            }
            
            // Hide the current table
            tableContainer.find('table').hide();
            
            // AJAX request to get data for the selected region
            $.ajax({
                url: fuel_surcharge_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'fuel_surcharge_get_region_data',
                    nonce: fuel_surcharge_params.nonce,
                    region: region,
                    table_id: tableId,
                    shortcode_atts: $(this).data('shortcode-atts')
                },
                success: function(response) {
                    if (response.success) {
                        // Replace the table with the new one
                        tableContainer.find('table').remove();
                        tableContainer.find('.fuel-surcharge-loading').before(response.data.html);
                    } else {
                        // Show error message
                        tableContainer.find('.fuel-surcharge-loading').before(
                            '<p class="fuel-surcharge-error">' + response.data.message + '</p>'
                        );
                    }
                },
                error: function() {
                    // Show error message
                    tableContainer.find('.fuel-surcharge-loading').before(
                        '<p class="fuel-surcharge-error">Error loading data for this region.</p>'
                    );
                },
                complete: function() {
                    // Hide loading indicator
                    tableContainer.find('.fuel-surcharge-loading').hide();
                }
            });
        });
    });

})(jQuery);