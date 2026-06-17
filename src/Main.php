<?php

namespace HDW\MeetingBooking;

use HDW\MeetingBooking\Admin\AdminMenu;
use HDW\MeetingBooking\Admin\AdminDashboard;
use HDW\MeetingBooking\Admin\SettingsPage;
use HDW\MeetingBooking\Frontend\BookingForm;
use HDW\MeetingBooking\Frontend\AjaxHandler;

/**
 * Class Main
 *
 * The main plugin class that orchestrates all components.
 * Responsible for loading admin and public-facing functionality.
 *
 * @package HDW\MeetingBooking
 */
class Main
{
    /**
     * Run the plugin by initializing all hooks.
     */
    public function run(): void
    {
        $this->initializeAdmin();
        $this->initializePublic();
        $this->registerHooks();
    }

    /**
     * Initialize admin-facing components.
     */
    private function initializeAdmin(): void
    {
        $adminMenu = new AdminMenu();
        $adminMenu->register();

        $adminDashboard = new AdminDashboard();
        $adminDashboard->registerHooks();

        $settingsPage = new SettingsPage();
        $settingsPage->registerHooks();
    }

    /**
     * Initialize public-facing components.
     */
    private function initializePublic(): void
    {
        $bookingForm = new BookingForm();
        $bookingForm->register();

        $ajaxHandler = new AjaxHandler();
        $ajaxHandler->registerHooks();
    }

    /**
     * Register global plugin hooks.
     */
    private function registerHooks(): void
    {
        add_action('plugins_loaded', [$this, 'loadTextdomain']);
    }

    /**
     * Load plugin textdomain for translations.
     */
    public function loadTextdomain(): void
    {
        load_plugin_textdomain(
            'hdw-meeting-booking',
            false,
            dirname(plugin_basename(HDW_MEETING_BOOKING_PLUGIN_DIR . 'hdw-meeting-booking.php')) . '/languages/'
        );
    }
}
