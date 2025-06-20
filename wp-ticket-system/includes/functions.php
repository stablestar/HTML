<?php
function wpts_custom_schedules( $schedules ) {
    if ( ! isset( $schedules['five_minutes'] ) ) {
        $schedules['five_minutes'] = array(
            'interval' => 300,
            'display' => __( 'Every Five Minutes' )
        );
    }
    return $schedules;
}
add_filter( 'cron_schedules', 'wpts_custom_schedules' );
