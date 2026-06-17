<?php

namespace HDW\MeetingBooking\Admin;

use HDW\MeetingBooking\Services\RoomManager;
use HDW\MeetingBooking\Utils\Security;

/**
 * Class SettingsPage
 *
 * Handles the room settings admin page.
 * Allows administrators to adjust the number of meeting rooms.
 *
 * @package HDW\MeetingBooking\Admin
 */
class SettingsPage
{
    private RoomManager $roomManager;

    public function __construct()
    {
        $this->roomManager = new RoomManager();
    }

    /**
     * Register settings hooks.
     */
    public function registerHooks(): void
    {
        add_action('admin_init', [$this, 'handleFormSubmission']);
    }

    /**
     * Handle form submission for room count update.
     */
    public function handleFormSubmission(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['hdw_update_rooms'])) {
            return;
        }

        if (!Security::verifyAdminNonce('hdw_room_settings_nonce')) {
            add_settings_error(
                'hdw_settings',
                'hdw_error',
                __('Security check failed. Please try again.', 'hdw-meeting-booking'),
                'error'
            );
            return;
        }

        Security::requireAdmin();

        $newCount = isset($_POST['hdw_room_count']) ? intval($_POST['hdw_room_count']) : 0;

        if ($newCount < 1) {
            add_settings_error(
                'hdw_settings',
                'hdw_error',
                __('Room count must be at least 1.', 'hdw-meeting-booking'),
                'error'
            );
            return;
        }

        if ($newCount > 50) {
            add_settings_error(
                'hdw_settings',
                'hdw_error',
                __('Maximum room count is 50.', 'hdw-meeting-booking'),
                'error'
            );
            return;
        }

        $this->roomManager->syncRoomCount($newCount);
        $this->roomManager->setConfiguredRoomCount($newCount);

        add_settings_error(
            'hdw_settings',
            'hdw_success',
            sprintf(
                __('Meeting room count updated to %d successfully.', 'hdw-meeting-booking'),
                $newCount
            ),
            'success'
        );
    }

    /**
     * Render the settings page.
     */
    public function render(): void
    {
        Security::requireAdmin();

        $currentCount = $this->roomManager->getConfiguredRoomCount();
        $activeRooms = $this->roomManager->getActiveRooms();

        require_once HDW_MEETING_BOOKING_PLUGIN_DIR . 'admin/partials/settings.php';
    }
}
