<?php

namespace HDW\MeetingBooking\Database;

use HDW\MeetingBooking\Models\Room;

/**
 * Class RoomRepository
 *
 * Handles all database operations for meeting rooms.
 * All queries use prepared statements.
 *
 * @package HDW\MeetingBooking\Database
 */
class RoomRepository
{
    private \wpdb  $db;
    private string $table;

    public function __construct()
    {
        $this->db    = Database::getDb();
        $this->table = Database::getTable( Database::TABLE_ROOMS );
    }

    /** @return Room[] */
    public function findAll(): array
    {
        $results = $this->db->get_results(
            "SELECT * FROM {$this->table} ORDER BY id ASC",
            ARRAY_A
        );
        return array_map( array( Room::class, 'fromArray' ), $results ?: array() );
    }

    /** @return Room[] */
    public function findAllActive(): array
    {
        $results = $this->db->get_results(
            "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY id ASC",
            ARRAY_A
        );
        return array_map( array( Room::class, 'fromArray' ), $results ?: array() );
    }

    public function findById( int $id ): ?Room
    {
        $row = $this->db->get_row(
            $this->db->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id ),
            ARRAY_A
        );
        return $row ? Room::fromArray( $row ) : null;
    }

    public function create( array $data ): int
    {
        $this->db->insert(
            $this->table,
            array(
                'room_name' => $data['room_name'],
                'room_code' => $data['room_code'],
                'is_active' => $data['is_active'] ?? 1,
            ),
            array( '%s', '%s', '%d' )
        );
        return (int) $this->db->insert_id;
    }

    public function softDelete( int $id ): bool
    {
        return (bool) $this->db->update(
            $this->table,
            array( 'is_active' => 0 ),
            array( 'id' => $id ),
            array( '%d' ),
            array( '%d' )
        );
    }

    public function count(): int
    {
        return (int) $this->db->get_var( "SELECT COUNT(*) FROM {$this->table}" );
    }

    public function countActive(): int
    {
        return (int) $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->table} WHERE is_active = 1"
        );
    }
}
