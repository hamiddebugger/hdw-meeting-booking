<?php
/**
 * Plugin Name: HDW Meeting Room Booking System
 * Plugin URI:  https://example.com/hdw-meeting-booking
 * Description: A professional meeting room reservation system with conflict detection, room allocation algorithm, and admin dashboard.
 * Version:     1.0.0
 * Author:      HDW Developer
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: hdw-meeting-booking
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('HDW_MEETING_BOOKING_VERSION', '1.0.0');
define('HDW_MEETING_BOOKING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HDW_MEETING_BOOKING_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once HDW_MEETING_BOOKING_PLUGIN_DIR . 'vendor/autoload.php';

use HDW\MeetingBooking\Activator;
use HDW\MeetingBooking\Deactivator;
use HDW\MeetingBooking\Main;

/**
 * Fired during plugin activation.
 */
function hdw_meeting_booking_activate(): void
{
    Activator::activate();
}
register_activation_hook(__FILE__, 'hdw_meeting_booking_activate');

/**
 * Fired during plugin deactivation.
 */
function hdw_meeting_booking_deactivate(): void
{
    Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'hdw_meeting_booking_deactivate');

/**
 * Begins execution of the plugin.
 */
function hdw_meeting_booking_run(): void
{
    $plugin = new Main();
    $plugin->run();
}
hdw_meeting_booking_run();
