<?php
/**
 * Plugin Name: HDW Meeting Room Booking System
 * Plugin URI:  https://github.com/your-username/hdw-meeting-booking
 * Description: A professional meeting room reservation system with conflict detection, room allocation algorithm, and admin dashboard.
 * Version:     1.0.0
 * Author:      HDW Developer
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: hdw-meeting-booking
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'HDW_MEETING_BOOKING_VERSION',    '1.0.0' );
define( 'HDW_MEETING_BOOKING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'HDW_MEETING_BOOKING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once HDW_MEETING_BOOKING_PLUGIN_DIR . 'vendor/autoload.php';

use HDW\MeetingBooking\Activator;
use HDW\MeetingBooking\Deactivator;
use HDW\MeetingBooking\Main;

register_activation_hook( __FILE__, array( 'HDW\MeetingBooking\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'HDW\MeetingBooking\Deactivator', 'deactivate' ) );

function hdw_meeting_booking_run(): void {
    $plugin = new Main();
    $plugin->run();
}
hdw_meeting_booking_run();
