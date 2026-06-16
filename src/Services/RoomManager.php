<?php

namespace HDW\MeetingBooking\Services;

use HDW\MeetingBooking\Database\RoomRepository;
use HDW\MeetingBooking\Models\Room;

/**
 * Class RoomManager
 *
 * Room business logic: CRUD operations and room-count synchronisation.
 *
 * @package HDW\MeetingBooking\Services
 */
class RoomManager
{
    private RoomRepository $repository;

    public function __construct()
    {
        $this->repository = new RoomRepository();
    }

    /** @return Room[] */
    public function getActiveRooms(): array
    {
        return $this->repository->findAllActive();
    }

    /** @return Room[] */
    public function getAllRooms(): array
    {
        return $this->repository->findAll();
    }

    public function getRoom( int $id ): ?Room
    {
        return $this->repository->findById( $id );
    }

    /**
     * Synchronise the physical room count with the target number.
     * Adds rooms, reactivates soft-deleted ones, or soft-deletes extras.
     */
    public function syncRoomCount( int $target ): void
    {
        $active = $this->repository->countActive();
        $total  = $this->repository->count();

        if ( $target > $total ) {
            $this->addRooms( $target - $total );
        } elseif ( $target > $active ) {
            $this->reactivateRooms( $target - $active );
        } elseif ( $target < $active ) {
            $this->removeRooms( $active - $target );
        }
    }

    public function getConfiguredRoomCount(): int
    {
        return (int) get_option( 'hdw_total_meeting_rooms', 3 );
    }

    public function setConfiguredRoomCount( int $count ): void
    {
        update_option( 'hdw_total_meeting_rooms', max( 1, $count ) );
    }

    private function addRooms( int $count ): void
    {
        $existing = $this->repository->count();
        for ( $i = 1; $i <= $count; $i++ ) {
            $n = $existing + $i;
            $this->repository->create( array(
                'room_name' => sprintf( __( 'Meeting Room %d', 'hdw-meeting-booking' ), $n ),
                'room_code' => 'HDW-R' . str_pad( (string) $n, 3, '0', STR_PAD_LEFT ),
                'is_active' => 1,
            ) );
        }
    }

    private function removeRooms( int $count ): void
    {
        $rooms    = $this->repository->findAll();
        $to_remove = array_slice( array_reverse( $rooms ), 0, $count );
        foreach ( $to_remove as $room ) {
            $this->repository->softDelete( $room->id );
        }
    }

    private function reactivateRooms( int $count ): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'hdw_meeting_rooms';
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET is_active = 1
                 WHERE is_active = 0
                 ORDER BY id ASC
                 LIMIT %d",
                $count
            )
        );
    }
}
