<?php

namespace HDW\MeetingBooking\Utils;

/**
 * Class Validator
 *
 * Handles all input validation for the booking system.
 * Single Responsibility: Validate user input data.
 *
 * @package HDW\MeetingBooking\Utils
 */
class Validator
{
    /**
     * Validate reservation form data.
     *
     * @return array{is_valid: bool, errors: string[]}
     */
    public static function validateReservationData(array $data): array
    {
        $errors = [];

        if (empty($data['full_name']) || mb_strlen(trim($data['full_name'])) < 2) {
            $errors[] = __('Full name is required (minimum 2 characters).', 'hdw-meeting-booking');
        }

        if (empty($data['mobile']) || !self::isValidMobile($data['mobile'])) {
            $errors[] = __('Please enter a valid mobile number.', 'hdw-meeting-booking');
        }

        if (empty($data['email']) || !is_email($data['email'])) {
            $errors[] = __('Please enter a valid email address.', 'hdw-meeting-booking');
        }

        if (empty($data['meeting_title']) || mb_strlen(trim($data['meeting_title'])) < 2) {
            $errors[] = __('Meeting title is required (minimum 2 characters).', 'hdw-meeting-booking');
        }

        if (empty($data['meeting_date']) || !self::isValidDate($data['meeting_date'])) {
            $errors[] = __('Please select a valid meeting date.', 'hdw-meeting-booking');
        }

        if (empty($data['start_time']) || !self::isValidTime($data['start_time'])) {
            $errors[] = __('Please select a valid start time.', 'hdw-meeting-booking');
        }

        if (empty($data['end_time']) || !self::isValidTime($data['end_time'])) {
            $errors[] = __('Please select a valid end time.', 'hdw-meeting-booking');
        }

        if (!empty($data['start_time']) && !empty($data['end_time'])) {
            if ($data['start_time'] >= $data['end_time']) {
                $errors[] = __('End time must be after start time.', 'hdw-meeting-booking');
            }

            $duration = self::calculateDuration($data['start_time'], $data['end_time']);
            if ($duration < 15) {
                $errors[] = __('Meeting duration must be at least 15 minutes.', 'hdw-meeting-booking');
            }
            if ($duration > 480) {
                $errors[] = __('Meeting duration cannot exceed 8 hours.', 'hdw-meeting-booking');
            }
        }

        if (!empty($data['meeting_date']) && self::isValidDate($data['meeting_date'])) {
            $selectedDate = strtotime($data['meeting_date']);
            $today = strtotime('today');
            if ($selectedDate < $today) {
                $errors[] = __('Meeting date cannot be in the past.', 'hdw-meeting-booking');
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors'   => $errors,
        ];
    }

    /**
     * Validate mobile number (Iranian format supported).
     */
    private static function isValidMobile(string $mobile): bool
    {
        $mobile = preg_replace('/\s+/', '', $mobile);
        // Support Iranian mobile numbers and general international format
        return preg_match('/^(09\d{9}|\+98\d{10}|0\d{10})$/', $mobile) === 1;
    }

    /**
     * Validate date format (YYYY-MM-DD).
     */
    private static function isValidDate(string $date): bool
    {
        $format = 'Y-m-d';
        $datetime = \DateTime::createFromFormat($format, $date);
        return $datetime && $datetime->format($format) === $date;
    }

    /**
     * Validate time format (HH:MM).
     */
    private static function isValidTime(string $time): bool
    {
        return preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time) === 1;
    }

    /**
     * Calculate duration between two times in minutes.
     */
    private static function calculateDuration(string $start, string $end): int
    {
        $startMinutes = self::timeToMinutes($start);
        $endMinutes = self::timeToMinutes($end);
        return $endMinutes - $startMinutes;
    }

    /**
     * Convert time string to minutes.
     */
    private static function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);
        return ((int) $hours * 60) + (int) $minutes;
    }
}
