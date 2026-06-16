<?php

namespace HDW\MeetingBooking\Services;

use HDW\MeetingBooking\Database\ReservationRepository;
use HDW\MeetingBooking\Models\Reservation;

/**
 * Class ConflictDetector
 *
 * Detects time conflicts between reservations using interval overlap logic.
 * Also exposes room-level availability checks consumed by RoomAllocator.
 *
 * @package HDW\MeetingBooking\Services
 */
class ConflictDetector
{
    private ReservationRepository $repository;

    public function __construct()
    {
        $this->repository = new ReservationRepository();
    }

    /**
     * Find all reservations that overlap a given time range (any room).
     *
     * @return Reservation[]
     */
    public function findConflicts(
        string $date,
        string $startTime,
        string $endTime,
        ?int   $excludeReservationId = null
    ): array {
        return $this->repository->findOverlapping( $date, $startTime, $endTime, $excludeReservationId );
    }

    /**
     * Find conflicts for a specific room.
     *
     * @return Reservation[]
     */
    public function findConflictsForRoom(
        int    $roomId,
        string $date,
        string $startTime,
        string $endTime
    ): array {
        return $this->repository->findOverlappingForRoom( $roomId, $date, $startTime, $endTime );
    }

    /**
     * Check whether a specific room is free for the given time range.
     */
    public function isRoomAvailable(
        int    $roomId,
        string $date,
        string $startTime,
        string $endTime,
        ?int   $excludeReservationId = null
    ): bool {
        return empty(
            $this->repository->findOverlappingForRoom(
                $roomId,
                $date,
                $startTime,
                $endTime,
                $excludeReservationId
            )
        );
    }

    /**
     * Return occupied time intervals for a date, normalised to HH:MM.
     * Used by ReservationManager to compute slot availability.
     *
     * @return array<array{start: string, end: string, room_id: ?int}>
     */
    public function getOccupiedSlots( string $date ): array
    {
        $reservations = $this->repository->findByDate( $date );
        $slots        = array();

        foreach ( $reservations as $reservation ) {
            if ( Reservation::STATUS_REJECTED === $reservation->status ) {
                continue;
            }
            $slots[] = array(
                'start'   => substr( $reservation->startTime, 0, 5 ),
                'end'     => substr( $reservation->endTime,   0, 5 ),
                'room_id' => $reservation->roomId,
            );
        }

        return $slots;
    }

    /**
     * Count how many distinct rooms are occupied during a 30-min slot.
     * A slot is fully blocked only when this equals the total room count.
     */
    public function countRoomsOccupiedAt(
        string $slotStart,
        string $slotEnd,
        array  $occupiedSlots
    ): int {
        $seen = array();

        foreach ( $occupiedSlots as $occupied ) {
            if ( $occupied['start'] < $slotEnd && $occupied['end'] > $slotStart ) {
                $room_id          = $occupied['room_id'] ?? 0;
                $seen[ $room_id ] = true;
            }
        }

        return count( $seen );
    }
}
