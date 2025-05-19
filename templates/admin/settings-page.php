<?php
/**
 * Template for the admin settings page.
 *
 * @package    EIAFuelSurcharge
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <div class="eia-fuel-surcharge-tabs nav-tab-wrapper">
        <a href="#api" class="nav-tab nav-tab-active"><?php _e('API Settings', 'eia-fuel-surcharge'); ?></a>
        <a href="#calculation" class="nav-tab"><?php _e('Calculation', 'eia-fuel-surcharge'); ?></a>
        <a href="#schedule" class="nav-tab"><?php _e('Schedule', 'eia-fuel-surcharge'); ?></a>
        <a href="#display" class="nav-tab"><?php _e('Display', 'eia-fuel-surcharge'); ?></a>
        <a href="#shortcodes" class="nav-tab"><?php _e('Shortcodes', 'eia-fuel-surcharge'); ?></a>
    </div>
    
    <form method="post" action="options.php">
        <?php 
        // Critical - Output nonce, action, and option_page fields
        settings_fields('eia_fuel_surcharge_options'); 
        ?>
        <input type="hidden" id="active_tab" name="active_tab" value="api">

        <!-- API Settings Tab -->
        <div id="api" class="eia-fuel-surcharge-tab-content">
            <?php do_settings_sections('eia_fuel_surcharge_api'); ?>
        </div>

        <!-- Calculation Settings Tab -->
        <div id="calculation" class="eia-fuel-surcharge-tab-content">
            <?php do_settings_sections('eia_fuel_surcharge_calculation'); ?>
        </div>

        <!-- Schedule Settings Tab -->
        <div id="schedule" class="eia-fuel-surcharge-tab-content">
            <?php do_settings_sections('eia_fuel_surcharge_schedule'); ?>
            
            <!-- Manual Update Section -->
            <h3><?php _e('Manual Update', 'eia-fuel-surcharge'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Update Now', 'eia-fuel-surcharge'); ?></th>
                    <td>
                        <?php
                        $scheduler = new EIAFuelSurcharge\Core\Scheduler();
                        $next_update = $scheduler->get_next_scheduled_update();
                        
                        if ($next_update) {
                            echo '<p><strong>' . __('Next Scheduled Update:', 'eia-fuel-surcharge') . '</strong> ';
                            echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_update);
                            echo ' (' . human_time_diff(time(), $next_update) . ' ' . __('from now', 'eia-fuel-surcharge') . ')</p>';
                        } else {
                            echo '<p><em>' . __('No update currently scheduled.', 'eia-fuel-surcharge') . '</em></p>';
                        }
                        ?>
                        <button type="button" id="manual-update-button" class="button"><?php _e('Update Now', 'eia-fuel-surcharge'); ?></button>
                        <span class="description"><?php _e('Manually trigger an update from the EIA API.', 'eia-fuel-surcharge'); ?></span>
                        <span id="manual-update-status" style="display:none; margin-left: 10px;"></span>
                        
                        <!-- Add results container similar to API test -->
                        <div id="manual-update-results" class="eia-api-test-results" style="margin-top: 15px;"></div>
                    </td>
                </tr>
            </table>
            
        </div>

        <!-- Display Settings Tab -->
        <div id="display" class="eia-fuel-surcharge-tab-content">
            <?php do_settings_sections('eia_fuel_surcharge_display'); ?>
        </div>

        <!-- Shortcodes Tab -->
        <div id="shortcodes" class="eia-fuel-surcharge-tab-content">
            <h2><?php _e('Shortcode Examples', 'eia-fuel-surcharge'); ?></h2>
            
            <div class="eia-fuel-surcharge-shortcode-examples">
                <div class="shortcode-example">
                    <h3><?php _e('Basic Usage', 'eia-fuel-surcharge'); ?></h3>
                    <code>[fuel_surcharge]</code>
                    <p class="description"><?php _e('Displays the current fuel surcharge rate using default settings.', 'eia-fuel-surcharge'); ?></p>
                </div>
                
                <div class="shortcode-example">
                    <h3><?php _e('Customized Display', 'eia-fuel-surcharge'); ?></h3>
                    <code>[fuel_surcharge format="Fuel Surcharge: {rate}% (as of {date})" date_format="F j, Y" decimals="1"]</code>
                    <p class="description"><?php _e('Customizes the display format, date format, and decimal places.', 'eia-fuel-surcharge'); ?></p>
                </div>
                
                <div class="shortcode-example">
                    <h3><?php _e('Table Display', 'eia-fuel-surcharge'); ?></h3>
                    <code>[fuel_surcharge_table rows="5" date_format="m/d/Y" class="striped"]</code>
                    <p class="description"><?php _e('Displays a table of the 5 most recent fuel surcharge rates.', 'eia-fuel-surcharge'); ?></p>
                </div>
                
                <div class="shortcode-example">
                    <h3><?php _e('Customized Table', 'eia-fuel-surcharge'); ?></h3>
                    <code>[fuel_surcharge_table rows="10" columns="date,rate" order="asc" title="Historical Fuel Surcharge Rates" show_footer="true"]</code>
                    <p class="description"><?php _e('Displays a customized table with specific columns, ordering, title, and formula in the footer.', 'eia-fuel-surcharge'); ?></p>
                </div>
                
                <div class="shortcode-example">
                    <h3><?php _e('Regional Display', 'eia-fuel-surcharge'); ?></h3>
                    <code>[fuel_surcharge region="east_coast"]</code>
                    <p class="description"><?php _e('Displays the fuel surcharge rate for a specific region.', 'eia-fuel-surcharge'); ?></p>
                </div>
            </div>
            
            <h3><?php _e('Shortcode Parameters', 'eia-fuel-surcharge'); ?></h3>
            
            <div class="eia-fuel-surcharge-parameters">
                <h4><?php _e('[fuel_surcharge] Parameters', 'eia-fuel-surcharge'); ?></h4>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Parameter', 'eia-fuel-surcharge'); ?></th>
                            <th><?php _e('Description', 'eia-fuel-surcharge'); ?></th>
                            <th><?php _e('Default', 'eia-fuel-surcharge'); ?></th>
                            <th><?php _e('Example', 'eia-fuel-surcharge'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>format</code></td>
                            <td><?php _e('Custom text format with placeholders {date}, {rate}, and {price}', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>format="Fuel Surcharge: {rate}%"</code></td>
                        </tr>
                        <tr>
                            <td><code>date_format</code></td>
                            <td><?php _e('PHP date format for displaying the date', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>date_format="F j, Y"</code></td>
                        </tr>
                        <tr>
                            <td><code>decimals</code></td>
                            <td><?php _e('Number of decimal places for the rate', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>decimals="1"</code></td>
                        </tr>
                        <tr>
                            <td><code>class</code></td>
                            <td><?php _e('CSS class for styling', 'eia-fuel-surcharge'); ?></td>
                            <td><code>fuel-surcharge</code></td>
                            <td><code>class="highlight-box"</code></td>
                        </tr>
                        <tr>
                            <td><code>region</code></td>
                            <td><?php _e('Region for the fuel price data', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>region="east_coast"</code></td>
                        </tr>
                        <tr>
                            <td><code>compare</code></td>
                            <td><?php _e('Comparison period (day, week, month, year)', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>compare="month"</code></td>
                        </tr>
                        <tr>
                            <td><code>show_comparison</code></td>
                            <td><?php _e('Whether to show comparison data', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>show_comparison="false"</code></td>
                        </tr>
                    </tbody>
                </table>
                
                <h4><?php _e('[fuel_surcharge_table] Parameters', 'eia-fuel-surcharge'); ?></h4>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Parameter', 'eia-fuel-surcharge'); ?></th>
                            <th><?php _e('Description', 'eia-fuel-surcharge'); ?></th>
                            <th><?php _e('Default', 'eia-fuel-surcharge'); ?></th>
                            <th><?php _e('Example', 'eia-fuel-surcharge'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>rows</code></td>
                            <td><?php _e('Number of rows to display', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>rows="5"</code></td>
                        </tr>
                        <tr>
                            <td><code>date_format</code></td>
                            <td><?php _e('PHP date format for displaying dates', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>date_format="F j, Y"</code></td>
                        </tr>
                        <tr>
                            <td><code>columns</code></td>
                            <td><?php _e('Columns to display (date,price,rate,region)', 'eia-fuel-surcharge'); ?></td>
                            <td><code>date,price,rate</code></td>
                            <td><code>columns="date,rate"</code></td>
                        </tr>
                        <tr>
                            <td><code>order</code></td>
                            <td><?php _e('Sort order (asc or desc)', 'eia-fuel-surcharge'); ?></td>
                            <td><code>desc</code></td>
                            <td><code>order="asc"</code></td>
                        </tr>
                        <tr>
                            <td><code>class</code></td>
                            <td><?php _e('CSS class for the table', 'eia-fuel-surcharge'); ?></td>
                            <td><code>fuel-surcharge-table</code></td>
                            <td><code>class="striped"</code></td>
                        </tr>
                        <tr>
                            <td><code>region</code></td>
                            <td><?php _e('Region for the fuel price data', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>region="midwest"</code></td>
                        </tr>
                        <tr>
                            <td><code>title</code></td>
                            <td><?php _e('Title to display above the table', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('None', 'eia-fuel-surcharge'); ?></td>
                            <td><code>title="Historical Rates"</code></td>
                        </tr>
                        <tr>
                            <td><code>show_footer</code></td>
                            <td><?php _e('Whether to show formula in footer', 'eia-fuel-surcharge'); ?></td>
                            <td><code>false</code></td>
                            <td><code>show_footer="true"</code></td>
                        </tr>
                        <tr>
                            <td><code>decimals</code></td>
                            <td><?php _e('Number of decimal places for rates', 'eia-fuel-surcharge'); ?></td>
                            <td><?php _e('Setting from plugin options', 'eia-fuel-surcharge'); ?></td>
                            <td><code>decimals="1"</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <?php submit_button(); ?>
    </form>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Tab functionality
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

        // Ensure form includes all fields even if hidden
        $('form').on('submit', function() {
            // Make sure all fields from hidden tabs are included
            return true; // Always allow form submission
        });

        // Manual update button handler
        $('#manual-update-button').on('click', function() {
            var $button = $(this);
            var $status = $('#manual-update-status');
            
            // Disable the button and show loading state
            $button.prop('disabled', true).text('<?php _e('Updating...', 'eia-fuel-surcharge'); ?>');
            $status.text('').show();
            
            // Send AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'eia_fuel_surcharge_manual_update_ajax',
                    nonce: '<?php echo wp_create_nonce('eia_fuel_surcharge_manual_update_ajax'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $status.text('<?php _e('Update successful!', 'eia-fuel-surcharge'); ?>').css('color', 'green');
                    } else {
                        $status.text(response.data || '<?php _e('Update failed.', 'eia-fuel-surcharge'); ?>').css('color', 'red');
                    }
                },
                error: function() {
                    $status.text('<?php _e('Update failed. Please try again.', 'eia-fuel-surcharge'); ?>').css('color', 'red');
                },
                complete: function() {
                    // Re-enable the button and restore original text
                    $button.prop('disabled', false).text('<?php _e('Update Now', 'eia-fuel-surcharge'); ?>');
                    
                    // Hide status after 5 seconds
                    setTimeout(function() {
                        $status.fadeOut();
                    }, 5000);
                }
            });
        });
    });
</script>

<style>
/* Settings Page Styling */
.eia-fuel-surcharge-tab-content {
    display: none;
    padding: 20px 0;
}

.eia-fuel-surcharge-shortcode-examples {
    display: flex;
    flex-wrap: wrap;
    margin: 15px -10px;
}

.shortcode-example {
    flex: 1;
    min-width: 300px;
    margin: 0 10px 20px;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 3px;
}

.shortcode-example h3 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 16px;
}

.shortcode-example code {
    display: block;
    padding: 10px;
    margin-bottom: 10px;
    background: #f1f1f1;
    border: 1px solid #ddd;
}

.formula-preview {
    padding: 10px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    margin-bottom: 5px;
}

/* Responsive adjustments */
@media screen and (max-width: 782px) {
    .shortcode-example {
        min-width: 100%;
        margin-bottom: 15px;
    }
}
</style>