<?php

namespace HDW\MeetingBooking\Services;

use HDW\MeetingBooking\Database\ReservationRepository;
use HDW\MeetingBooking\Models\Reservation;
use HDW\MeetingBooking\Utils\Validator;

/**
 * Class ReservationManager
 *
 * Central service for reservation business logic.
 * Orchestrates conflict detection, room allocation, and status management.
 *
 * @package HDW\MeetingBooking\Services
 */
class ReservationManager
{
    private ReservationRepository $repository;
    private ConflictDetector $conflictDetector;
    private RoomAllocator $roomAllocator;
    private IntervalPartitioning $intervalPartitioning;

    public function __construct()
    {
        $this->repository = new ReservationRepository();
        $this->conflictDetector = new ConflictDetector();
        $this->roomAllocator = new RoomAllocator();
        $this->intervalPartitioning = new IntervalPartitioning();
    }

    /**
     * Create a new reservation request with transaction-based race condition protection.
     * Uses SELECT ... FOR UPDATE to lock competing rows before insert.
     *
     * @return array{success: bool, reservation_id: ?int, room_id: ?int, message: string}
     */
    public function createReservation(array $data): array
    {
        $validation = Validator::validateReservationData($data);
        if (!$validation['is_valid']) {
            return [
                'success'        => false,
                'reservation_id' => null,
                'room_id'        => null,
                'message'        => implode(', ', $validation['errors']),
            ];
        }

        $date      = sanitize_text_field($data['meeting_date']);
        $startTime = sanitize_text_field($data['start_time']);
        $endTime   = sanitize_text_field($data['end_time']);

        // Wrap in a transaction so allocate + insert are atomic
        $result = $this->repository->createWithinTransaction(
            $date,
            $startTime,
            $endTime,
            [
                'full_name'     => sanitize_text_field($data['full_name']),
                'mobile'        => sanitize_text_field($data['mobile']),
                'email'         => sanitize_email($data['email']),
                'meeting_title' => sanitize_text_field($data['meeting_title']),
                'meeting_date'  => $date,
                'start_time'    => $startTime,
                'end_time'      => $endTime,
                'description'   => sanitize_textarea_field($data['description'] ?? ''),
                'status'        => Reservation::STATUS_PENDING,
            ],
            $this->roomAllocator
        );

        if ($result['room_id'] === null) {
            return [
                'success'        => false,
                'reservation_id' => null,
                'room_id'        => null,
                'message'        => __('No meeting room is available for the selected time slot. Please choose a different time.', 'hdw-meeting-booking'),
            ];
        }

        if (!$result['reservation_id']) {
            return [
                'success'        => false,
                'reservation_id' => null,
                'room_id'        => null,
                'message'        => __('Failed to save reservation. Please try again.', 'hdw-meeting-booking'),
            ];
        }

        do_action('hdw_reservation_created', $result['reservation_id'], $result['room_id']);

        return [
            'success'        => true,
            'reservation_id' => $result['reservation_id'],
            'room_id'        => $result['room_id'],
            'message'        => __('Your reservation request has been submitted and is pending approval.', 'hdw-meeting-booking'),
        ];
    }

    /**
     * Approve a reservation.
     * Re-checks room availability before approving.
     *
     * @return array{success: bool, message: string}
     */
    public function approveReservation(int $reservationId): array
    {
        $reservation = $this->repository->findById($reservationId);

        if (!$reservation) {
            return [
                'success' => false,
                'message' => __('Reservation not found.', 'hdw-meeting-booking'),
            ];
        }

        if ($reservation->status === Reservation::STATUS_APPROVED) {
            return [
                'success' => false,
                'message' => __('Reservation is already approved.', 'hdw-meeting-booking'),
            ];
        }

        // Re-verify room availability at approval time
        $isAvailable = $this->conflictDetector->isRoomAvailable(
            $reservation->roomId,
            $reservation->meetingDate,
            $reservation->startTime,
            $reservation->endTime,
            $reservationId
        );

        if (!$isAvailable) {
            // Try to find another room
            $newRoomId = $this->roomAllocator->allocateReservation(
                $reservation->meetingDate,
                $reservation->startTime,
                $reservation->endTime,
                $reservationId
            );

            if ($newRoomId === null) {
                $this->repository->updateStatus($reservationId, Reservation::STATUS_REJECTED);
                return [
                    'success' => false,
                    'message' => __('The assigned room is no longer available and no alternative room was found. The reservation has been rejected.', 'hdw-meeting-booking'),
                ];
            }

            $this->repository->updateStatus($reservationId, Reservation::STATUS_APPROVED, $newRoomId);

            do_action('hdw_reservation_approved_room_changed', $reservationId, $newRoomId);

            return [
                'success'   => true,
                'message'   => sprintf(
                    __('Reservation approved. Room changed to %s.', 'hdw-meeting-booking'),
                    $this->getRoomDisplayName($newRoomId)
                ),
                'room_id'   => $newRoomId,
                'room_name' => $this->getRoomDisplayName($newRoomId),
            ];
        }

        $this->repository->updateStatus($reservationId, Reservation::STATUS_APPROVED);

        do_action('hdw_reservation_approved', $reservationId, $reservation->roomId);

        return [
            'success'   => true,
            'message'   => __('Reservation has been approved successfully.', 'hdw-meeting-booking'),
            'room_id'   => $reservation->roomId,
            'room_name' => $reservation->roomName,
        ];
    }

    /**
     * Reject a reservation.
     *
     * @return array{success: bool, message: string}
     */
    public function rejectReservation(int $reservationId): array
    {
        $reservation = $this->repository->findById($reservationId);

        if (!$reservation) {
            return [
                'success' => false,
                'message' => __('Reservation not found.', 'hdw-meeting-booking'),
            ];
        }

        $this->repository->updateStatus($reservationId, Reservation::STATUS_REJECTED);

        do_action('hdw_reservation_rejected', $reservationId);

        return [
            'success' => true,
            'message' => __('Reservation has been rejected.', 'hdw-meeting-booking'),
        ];
    }

    /**
     * Reset a reservation back to pending.
     *
     * @return array{success: bool, message: string}
     */
    public function resetToPending(int $reservationId): array
    {
        $reservation = $this->repository->findById($reservationId);

        if (!$reservation) {
            return [
                'success' => false,
                'message' => __('Reservation not found.', 'hdw-meeting-booking'),
            ];
        }

        $this->repository->updateStatus($reservationId, Reservation::STATUS_PENDING);

        do_action('hdw_reservation_reset_pending', $reservationId);

        return [
            'success' => true,
            'message' => __('Reservation has been reset to pending.', 'hdw-meeting-booking'),
        ];
    }

    /**
     * Get minimum rooms needed for a date.
     */
    public function getMinimumRoomsNeeded(string $date): int
    {
        return $this->intervalPartitioning->calculateMinimumRooms($date);
    }

    /**
     * Get allocation report for a date.
     */
    public function getAllocationReport(string $date): array
    {
        return $this->intervalPartitioning->getAllocationReport($date);
    }

    /**
     * Get available time slots for a date.
     * A slot is unavailable only when ALL configured rooms are occupied.
     *
     * @return array<array{time: string, available: bool}>
     */
    public function getAvailableTimeSlots(string $date, int $slotDuration = 30): array
    {
        $occupiedSlots  = $this->conflictDetector->getOccupiedSlots($date);
        $totalRooms     = (int) get_option('hdw_total_meeting_rooms', 1);
        $slots          = [];

        // Generate slots from 08:00 to 20:00
        $start = 8 * 60;  // 08:00 in minutes
        $end   = 20 * 60; // 20:00 in minutes

        for ($minutes = $start; $minutes < $end; $minutes += $slotDuration) {
            $slotStart = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
            $slotEnd   = sprintf('%02d:%02d', intdiv($minutes + $slotDuration, 60), ($minutes + $slotDuration) % 60);

            $roomsOccupied = $this->conflictDetector->countRoomsOccupiedAt($slotStart, $slotEnd, $occupiedSlots);
            $isAvailable   = $roomsOccupied < $totalRooms;

            $slots[] = [
                'time'      => $slotStart . ' - ' . $slotEnd,
                'start'     => $slotStart,
                'end'       => $slotEnd,
                'available' => $isAvailable,
            ];
        }

        return $slots;
    }

    /**
     * Get room display name.
     */
    private function getRoomDisplayName(int $roomId): string
    {
        $roomManager = new RoomManager();
        $room = $roomManager->getRoom($roomId);
        return $room ? $room->roomName : __('Unknown Room', 'hdw-meeting-booking');
    }
}
