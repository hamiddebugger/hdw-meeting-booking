<?php

namespace HDW\MeetingBooking\Admin;

/**
 * Class AdminMenu
 *
 * Registers admin menu pages for the plugin.
 *
 * @package HDW\MeetingBooking\Admin
 */
class AdminMenu
{
    /**
     * Register admin menu hooks.
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenuPages']);
    }

    /**
     * Add menu pages to the WordPress admin.
     */
    public function addMenuPages(): void
    {
        add_menu_page(
            __('Meeting Bookings', 'hdw-meeting-booking'),
            __('Meeting Bookings', 'hdw-meeting-booking'),
            'manage_options',
            'hdw-meeting-bookings',
            [$this, 'renderDashboard'],
            'dashicons-calendar-alt',
            25
        );

        add_submenu_page(
            'hdw-meeting-bookings',
            __('All Reservations', 'hdw-meeting-booking'),
            __('All Reservations', 'hdw-meeting-booking'),
            'manage_options',
            'hdw-meeting-bookings',
            [$this, 'renderDashboard']
        );

        add_submenu_page(
            'hdw-meeting-bookings',
            __('Room Settings', 'hdw-meeting-booking'),
            __('Room Settings', 'hdw-meeting-booking'),
            'manage_options',
            'hdw-room-settings',
            [$this, 'renderSettings']
        );

        add_submenu_page(
            'hdw-meeting-bookings',
            __('Allocation Report', 'hdw-meeting-booking'),
            __('Allocation Report', 'hdw-meeting-booking'),
            'manage_options',
            'hdw-allocation-report',
            [$this, 'renderReport']
        );
    }

    /**
     * Render the dashboard page.
     */
    public function renderDashboard(): void
    {
        $dashboard = new AdminDashboard();
        $dashboard->render();
    }

    /**
     * Render the settings page.
     */
    public function renderSettings(): void
    {
        $settings = new SettingsPage();
        $settings->render();
    }

    /**
     * Render the allocation report page.
     */
    public function renderReport(): void
    {
        require_once HDW_MEETING_BOOKING_PLUGIN_DIR . 'admin/partials/report-page.php';
    }
}
