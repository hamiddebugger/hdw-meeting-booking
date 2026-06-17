<?php

namespace HDW\MeetingBooking;

/**
 * Class Deactivator
 *
 * Fired during plugin deactivation.
 * Handles cleanup without dropping tables to preserve data.
 *
 * @package HDW\MeetingBooking
 */
class Deactivator
{
    /**
     * Deactivate the plugin.
     */
    public static function deactivate(): void
    {
        wp_clear_scheduled_hook('hdw_daily_room_report');
    }
}
