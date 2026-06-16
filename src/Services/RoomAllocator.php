<?php

namespace HDW\MeetingBooking\Services;

use HDW\MeetingBooking\Models\Room;

/**
 * Class RoomAllocator
 *
 * Allocates reservations to available rooms using a greedy first-fit strategy.
 *
 * @package HDW\MeetingBooking\Services
 */
class RoomAllocator
{
    private RoomManager     $roomManager;
    private ConflictDetector $conflictDetector;

    public function __construct()
    {
        $this->roomManager      = new RoomManager();
        $this->conflictDetector = new ConflictDetector();
    }

    /**
     * Find the first available room for the given time slot.
     */
    public function findAvailableRoom(
        string $date,
        string $startTime,
        string $endTime,
        ?int   $excludeReservationId = null
    ): ?Room {
        foreach ( $this->roomManager->getActiveRooms() as $room ) {
            if ( $this->conflictDetector->isRoomAvailable(
                $room->id,
                $date,
                $startTime,
                $endTime,
                $excludeReservationId
            ) ) {
                return $room;
            }
        }
        return null;
    }

    /**
     * Allocate a room and return its ID, or null if none is free.
     */
    public function allocateReservation(
        string $date,
        string $startTime,
        string $endTime,
        ?int   $excludeReservationId = null
    ): ?int {
        $room = $this->findAvailableRoom( $date, $startTime, $endTime, $excludeReservationId );
        return $room ? $room->id : null;
    }
}
