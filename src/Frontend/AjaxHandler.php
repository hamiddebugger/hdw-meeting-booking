<?php

namespace HDW\MeetingBooking\Frontend;

use HDW\MeetingBooking\Services\ReservationManager;
use HDW\MeetingBooking\Utils\Security;

/**
 * Class AjaxHandler
 *
 * Handles all AJAX requests for the public-facing booking system.
 *
 * @package HDW\MeetingBooking\Public
 */
class AjaxHandler
{
    private ReservationManager $reservationManager;

    public function __construct()
    {
        $this->reservationManager = new ReservationManager();
    }

    /**
     * Register AJAX hooks.
     */
    public function registerHooks(): void
    {
        // Public AJAX (no login required)
        add_action('wp_ajax_nopriv_hdw_get_available_slots', [$this, 'getAvailableSlots']);
        add_action('wp_ajax_hdw_get_available_slots', [$this, 'getAvailableSlots']);

        add_action('wp_ajax_nopriv_hdw_submit_reservation', [$this, 'submitReservation']);
        add_action('wp_ajax_hdw_submit_reservation', [$this, 'submitReservation']);
    }

    /**
     * Get available time slots for a date.
     */
    public function getAvailableSlots(): void
    {
        $date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : '';

        if (!$date || !$this->isValidDate($date)) {
            Security::sendJsonError(__('Invalid date format.', 'hdw-meeting-booking'));
        }

        $selectedDate = strtotime($date);
        if ($selectedDate < strtotime('today')) {
            Security::sendJsonError(__('Cannot select a past date.', 'hdw-meeting-booking'));
        }

        $slots = $this->reservationManager->getAvailableTimeSlots($date);

        Security::sendJsonSuccess([
            'date'  => $date,
            'slots' => $slots,
        ]);
    }

    /**
     * Submit a reservation request.
     */
    public function submitReservation(): void
    {
        if (!Security::verifyNonce('hdw_booking_nonce')) {
            Security::sendJsonError(__('Security check failed. Please refresh the page.', 'hdw-meeting-booking'), 403);
        }

        $data = [
            'full_name'     => isset($_POST['full_name']) ? sanitize_text_field($_POST['full_name']) : '',
            'mobile'        => isset($_POST['mobile']) ? sanitize_text_field($_POST['mobile']) : '',
            'email'         => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
            'meeting_title' => isset($_POST['meeting_title']) ? sanitize_text_field($_POST['meeting_title']) : '',
            'meeting_date'  => isset($_POST['meeting_date']) ? sanitize_text_field($_POST['meeting_date']) : '',
            'start_time'    => isset($_POST['start_time']) ? sanitize_text_field($_POST['start_time']) : '',
            'end_time'      => isset($_POST['end_time']) ? sanitize_text_field($_POST['end_time']) : '',
            'description'   => isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '',
        ];

        $result = $this->reservationManager->createReservation($data);

        if ($result['success']) {
            Security::sendJsonSuccess([
                'message'        => $result['message'],
                'reservation_id' => $result['reservation_id'],
                'room_id'        => $result['room_id'],
            ]);
        } else {
            Security::sendJsonError($result['message']);
        }
    }

    /**
     * Validate date format.
     */
    private function isValidDate(string $date): bool
    {
        $format = 'Y-m-d';
        $datetime = \DateTime::createFromFormat($format, $date);
        return $datetime && $datetime->format($format) === $date;
    }
}
