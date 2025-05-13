<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    
    <?php
    // Display success/error messages
    if ( isset( $_GET['cleared'] ) && $_GET['cleared'] == 'true' ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Logs cleared successfully.', 'eia-fuel-surcharge' ) . '</p></div>';
    }
    ?>
    
    <div class="eia-fuel-surcharge-admin-container">
        <div class="eia-fuel-surcharge-main">
            <div class="eia-fuel-surcharge-box">
                <h2><?php _e( 'System Logs', 'eia-fuel-surcharge' ); ?></h2>
                
                <?php
                // Get logs from database
                global $wpdb;
                $table_name = $wpdb->prefix . 'fuel_surcharge_logs';
                
                // Handle pagination
                $per_page = 50;
                $current_page = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1;
                $offset = ( $current_page - 1 ) * $per_page;
                
                // Get total logs count
                $total_logs = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
                $total_pages = ceil( $total_logs / $per_page );
                
                // Get logs for current page
                $logs = $wpdb->get_results( 
                    $wpdb->prepare(
                        "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d",
                        $per_page,
                        $offset
                    ),
                    ARRAY_A
                );
                
                if ( empty( $logs ) ) {
                    echo '<p>' . __( 'No logs available.', 'eia-fuel-surcharge' ) . '</p>';
                } else {
                    echo '<div class="tablenav top">';
                    echo '<div class="alignleft actions">';
                    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
                    echo '<input type="hidden" name="action" value="eia_fuel_surcharge_clear_logs">';
                    wp_nonce_field( 'eia_fuel_surcharge_clear_logs', 'eia_fuel_surcharge_nonce' );
                    echo '<button type="submit" class="button eia-fuel-surcharge-clear-logs">' . __( 'Clear All Logs', 'eia-fuel-surcharge' ) . '</button>';
                    echo '</form>';
                    echo '</div>';
                    
                    // Pagination
                    if ( $total_pages > 1 ) {
                        $page_links = paginate_links( array(
                            'base' => add_query_arg( 'paged', '%#%' ),
                            'format' => '',
                            'prev_text' => __( '&laquo;', 'eia-fuel-surcharge' ),
                            'next_text' => __( '&raquo;', 'eia-fuel-surcharge' ),
                            'total' => $total_pages,
                            'current' => $current_page
                        ) );
                        
                        if ( $page_links ) {
                            echo '<div class="tablenav-pages">' . $page_links . '</div>';
                        }
                    }
                    
                    echo '<br class="clear">';
                    echo '</div>';
                    
                    echo '<table class="eia-fuel-surcharge-logs-table">';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th>' . __( 'Date & Time', 'eia-fuel-surcharge' ) . '</th>';
                    echo '<th>' . __( 'Type', 'eia-fuel-surcharge' ) . '</th>';
                    echo '<th>' . __( 'Message', 'eia-fuel-surcharge' ) . '</th>';
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                    
                    foreach ( $logs as $log ) {
                        echo '<tr>';
                        echo '<td>' . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $log['created_at'] ) ) . '</td>';
                        echo '<td><span class="log-type-' . esc_attr( $log['log_type'] ) . '">' . esc_html( $log['log_type'] ) . '</span></td>';
                        echo '<td>' . esc_html( $log['message'] ) . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody>';
                    echo '</table>';
                    
                    // Bottom pagination
                    if ( $total_pages > 1 ) {
                        echo '<div class="tablenav bottom">';
                        echo '<div class="tablenav-pages">' . $page_links . '</div>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>
        
        <div class="eia-fuel-surcharge-sidebar">
            <div class="eia-fuel-surcharge-box">
                <h3><?php _e( 'Log Types', 'eia-fuel-surcharge' ); ?></h3>
                <ul>
                    <li><span class="log-type-api_request"><?php _e( 'api_request', 'eia-fuel-surcharge' ); ?></span> - <?php _e( 'API request made to EIA', 'eia-fuel-surcharge' ); ?></li>
                    <li><span class="log-type-api_success"><?php _e( 'api_success', 'eia-fuel-surcharge' ); ?></span> - <?php _e( 'Successful API response', 'eia-fuel-surcharge' ); ?></li>
                    <li><span class="log-type-api_error"><?php _e( 'api_error', 'eia-fuel-surcharge' ); ?></span> - <?php _e( 'API request error', 'eia-fuel-surcharge' ); ?></li>
                    <li><span class="log-type-update_start"><?php _e( 'update_start', 'eia-fuel-surcharge' ); ?></span> - <?php _e( 'Data update process started', 'eia-fuel-surcharge' ); ?></li>
                    <li><span class="log-type-update_success"><?php _e( 'update_success', 'eia-fuel-surcharge' ); ?></span> - <?php _e( 'Data update completed successfully', 'eia-fuel-surcharge' ); ?></li>
                    <li><span class="log-type-update_error"><?php _e( 'update_error', 'eia-fuel-surcharge' ); ?></span> - <?php _e( 'Data update error', 'eia-fuel-surcharge' ); ?></li>
                    <li><span class="log-type-db_error"><?php _e( 'db_error', 'eia-fuel-surcharge' ); ?></span> - <?php _e( 'Database error', 'eia-fuel-surcharge' ); ?></li>
                    <li><span class="log-type-schedule"><?php _e( 'schedule', 'eia-fuel-surcharge' ); ?></span> - <?php _e( 'Update schedule changes', 'eia-fuel-surcharge' ); ?></li>
                </ul>
            </div>
            
            <div class="eia-fuel-surcharge-box">
                <h3><?php _e( 'Current Schedule', 'eia-fuel-surcharge' ); ?></h3>
                <?php
                $scheduler = new EIA_Fuel_Surcharge_Scheduler();
                $next_update = $scheduler->get_next_scheduled_update();
                
                if ( $next_update ) {
                    $date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
                    echo '<p>' . sprintf(
                        __( 'Next update scheduled for: %s', 'eia-fuel-surcharge' ),
                        date_i18n( $date_format, $next_update )
                    ) . '</p>';
                } else {
                    echo '<p>' . __( 'No update currently scheduled.', 'eia-fuel-surcharge' ) . '</p>';
                }
                ?>
            </div>
        </div>
    </div>
</div>