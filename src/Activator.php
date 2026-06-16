<?php

namespace HDW\MeetingBooking;

use HDW\MeetingBooking\Database\Database;

/**
 * Class Activator
 *
 * Fired during plugin activation.
 * Handles database table creation and initial data setup.
 *
 * @package HDW\MeetingBooking
 */
class Activator
{
    /**
     * Activate the plugin.
     */
    public static function activate(): void
    {
        self::createDatabaseTables();
        self::setupDefaultOptions();
        self::createDefaultRooms();
    }

    /**
     * Create custom database tables using dbDelta.
     *
     * Rules followed for dbDelta compatibility:
     *  - Two spaces before PRIMARY KEY
     *  - No FOREIGN KEY constraints (managed at application level)
     *  - Each KEY on its own line with a space after the comma
     */
    private static function createDatabaseTables(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $rooms_table     = $wpdb->prefix . Database::TABLE_ROOMS;
        $res_table       = $wpdb->prefix . Database::TABLE_RESERVATIONS;

        $rooms_sql = "CREATE TABLE {$rooms_table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  room_name varchar(100) NOT NULL,
  room_code varchar(20) NOT NULL,
  is_active tinyint(1) NOT NULL DEFAULT 1,
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY room_code (room_code)
) {$charset_collate};";

        $res_sql = "CREATE TABLE {$res_table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  room_id bigint(20) unsigned DEFAULT NULL,
  full_name varchar(150) NOT NULL,
  mobile varchar(20) NOT NULL,
  email varchar(100) NOT NULL,
  meeting_title varchar(200) NOT NULL,
  meeting_date date NOT NULL,
  start_time time NOT NULL,
  end_time time NOT NULL,
  description text,
  status varchar(20) NOT NULL DEFAULT 'pending',
  created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY idx_meeting_date (meeting_date),
  KEY idx_status (status),
  KEY idx_mobile (mobile),
  KEY idx_room_date (room_id, meeting_date),
  KEY idx_time_range (meeting_date, start_time, end_time)
) {$charset_collate};";

        dbDelta( $rooms_sql );
        dbDelta( $res_sql );

        update_option( 'hdw_meeting_booking_db_version', HDW_MEETING_BOOKING_VERSION );
    }

    /**
     * Set default plugin options on first activation.
     */
    private static function setupDefaultOptions(): void
    {
        if ( false === get_option( 'hdw_total_meeting_rooms' ) ) {
            update_option( 'hdw_total_meeting_rooms', 3 );
        }
    }

    /**
     * Seed default meeting rooms based on the configured count.
     */
    private static function createDefaultRooms(): void
    {
        global $wpdb;
        $rooms_table = $wpdb->prefix . Database::TABLE_ROOMS;

        $existing = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$rooms_table}" );
        $required = (int) get_option( 'hdw_total_meeting_rooms', 3 );

        for ( $i = $existing + 1; $i <= $required; $i++ ) {
            $wpdb->insert(
                $rooms_table,
                array(
                    'room_name' => sprintf( __( 'Meeting Room %d', 'hdw-meeting-booking' ), $i ),
                    'room_code' => 'HDW-R' . str_pad( (string) $i, 3, '0', STR_PAD_LEFT ),
                    'is_active' => 1,
                ),
                array( '%s', '%s', '%d' )
            );
        }
    }
}
