<?php

namespace HDW\MeetingBooking\Database;

/**
 * Class Database
 *
 * Defines database table names and provides the wpdb instance.
 * Acts as a central configuration point for all database operations.
 *
 * @package HDW\MeetingBooking\Database
 */
class Database
{
    public const TABLE_ROOMS        = 'hdw_meeting_rooms';
    public const TABLE_RESERVATIONS = 'hdw_meeting_reservations';

    /**
     * Get the global wpdb instance.
     */
    public static function getDb(): \wpdb
    {
        global $wpdb;
        return $wpdb;
    }

    /**
     * Get the fully prefixed table name.
     */
    public static function getTable( string $table ): string
    {
        global $wpdb;
        return $wpdb->prefix . $table;
    }
}
