<?php

namespace HDW\MeetingBooking\Frontend;

use HDW\MeetingBooking\Utils\Security;

/**
 * Class BookingForm
 *
 * Handles the public-facing booking form.
 * Registers shortcode and enqueues frontend assets.
 *
 * @package HDW\MeetingBooking\Public
 */
class BookingForm
{
    /**
     * Register public hooks.
     */
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_shortcode('hdw_booking_form', [$this, 'renderForm']);
    }

    /**
     * Enqueue public-facing assets.
     */
    public function enqueueAssets(): void
    {
        global $post;
        if (!is_a($post, 'WP_Post') || !has_shortcode($post->post_content, 'hdw_booking_form')) {
            return;
        }

        wp_enqueue_style(
            'hdw-public-css',
            HDW_MEETING_BOOKING_PLUGIN_URL . 'public/css/booking-style.css',
            [],
            HDW_MEETING_BOOKING_VERSION
        );

        wp_enqueue_script(
            'hdw-public-js',
            HDW_MEETING_BOOKING_PLUGIN_URL . 'public/js/booking-script.js',
            [],
            HDW_MEETING_BOOKING_VERSION,
            true
        );

        wp_localize_script('hdw-public-js', 'hdwPublicData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('hdw_booking_nonce'),
            'strings' => [
                'selectDate'      => __('Please select a date first.', 'hdw-meeting-booking'),
                'selectTime'      => __('Please select start and end times.', 'hdw-meeting-booking'),
                'submitting'      => __('Submitting...', 'hdw-meeting-booking'),
                'submit'          => __('Submit Reservation', 'hdw-meeting-booking'),
                'noSlots'         => __('No available time slots for this date.', 'hdw-meeting-booking'),
                'slotOccupied'    => __('This time slot is no longer available.', 'hdw-meeting-booking'),
                'generalError'    => __('An error occurred. Please try again.', 'hdw-meeting-booking'),
                'successTitle'    => __('Reservation Submitted!', 'hdw-meeting-booking'),
                'dateRequired'    => __('Please select a meeting date.', 'hdw-meeting-booking'),
                'nameRequired'    => __('Full name is required.', 'hdw-meeting-booking'),
                'mobileRequired'  => __('Valid mobile number is required.', 'hdw-meeting-booking'),
                'emailRequired'   => __('Valid email is required.', 'hdw-meeting-booking'),
                'titleRequired'   => __('Meeting title is required.', 'hdw-meeting-booking'),
                'timeInvalid'     => __('End time must be after start time.', 'hdw-meeting-booking'),
                'minDuration'     => __('Meeting must be at least 15 minutes.', 'hdw-meeting-booking'),
            ],
        ]);
    }

    /**
     * Render the booking form shortcode.
     */
    public function renderForm(array $atts = []): string
    {
        ob_start();
        require HDW_MEETING_BOOKING_PLUGIN_DIR . 'public/partials/booking-form.php';
        return ob_get_clean();
    }
}
