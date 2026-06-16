<?php

namespace HDW\MeetingBooking\Database;

use HDW\MeetingBooking\Models\Reservation;

/**
 * Class ReservationRepository
 *
 * Handles all database operations for reservations.
 * All queries use prepared statements for security.
 *
 * @package HDW\MeetingBooking\Database
 */
class ReservationRepository
{
    private \wpdb  $db;
    private string $table;
    private string $roomsTable;

    public function __construct()
    {
        $this->db         = Database::getDb();
        $this->table      = Database::getTable( Database::TABLE_RESERVATIONS );
        $this->roomsTable = Database::getTable( Database::TABLE_ROOMS );
    }

    /**
     * Find a reservation by ID with joined room details.
     */
    public function findById( int $id ): ?Reservation
    {
        $row = $this->db->get_row(
            $this->db->prepare(
                "SELECT r.*, rm.room_name, rm.room_code
                 FROM {$this->table} r
                 LEFT JOIN {$this->roomsTable} rm ON r.room_id = rm.id
                 WHERE r.id = %d",
                $id
            ),
            ARRAY_A
        );
        return $row ? Reservation::fromArray( $row ) : null;
    }

    /**
     * Get paginated reservations with optional filters.
     *
     * @return array{items: Reservation[], total: int}
     */
    public function findWithFilters( array $filters = array(), int $page = 1, int $perPage = 20 ): array
    {
        $where  = array( '1=1' );
        $params = array();

        if ( ! empty( $filters['search'] ) ) {
            $where[]  = '(r.full_name LIKE %s OR r.mobile LIKE %s)';
            $search   = '%' . $this->db->esc_like( $filters['search'] ) . '%';
            $params[] = $search;
            $params[] = $search;
        }
        if ( ! empty( $filters['date'] ) ) {
            $where[]  = 'r.meeting_date = %s';
            $params[] = $filters['date'];
        }
        if ( ! empty( $filters['status'] ) ) {
            $where[]  = 'r.status = %s';
            $params[] = $filters['status'];
        }

        $where_clause = implode( ' AND ', $where );

        $count_sql = "SELECT COUNT(*) FROM {$this->table} r WHERE {$where_clause}";
        $total     = (int) $this->db->get_var(
            $params ? $this->db->prepare( $count_sql, ...$params ) : $count_sql
        );

        $offset      = ( $page - 1 ) * $perPage;
        $sql         = "SELECT r.*, rm.room_name, rm.room_code
                FROM {$this->table} r
                LEFT JOIN {$this->roomsTable} rm ON r.room_id = rm.id
                WHERE {$where_clause}
                ORDER BY r.meeting_date DESC, r.start_time ASC
                LIMIT %d OFFSET %d";
        $query_params = array_merge( $params, array( $perPage, $offset ) );
        $results      = $this->db->get_results(
            $this->db->prepare( $sql, ...$query_params ),
            ARRAY_A
        );

        return array(
            'items' => array_map( array( Reservation::class, 'fromArray' ), $results ?: array() ),
            'total' => $total,
        );
    }

    /**
     * Get all reservations for a specific date (optionally filtered by status).
     *
     * @return Reservation[]
     */
    public function findByDate( string $date, ?string $status = null ): array
    {
        $sql    = "SELECT r.*, rm.room_name, rm.room_code
                   FROM {$this->table} r
                   LEFT JOIN {$this->roomsTable} rm ON r.room_id = rm.id
                   WHERE r.meeting_date = %s";
        $params = array( $date );

        if ( null !== $status ) {
            $sql     .= ' AND r.status = %s';
            $params[] = $status;
        }

        $sql    .= ' ORDER BY r.start_time ASC';
        $results = $this->db->get_results(
            $this->db->prepare( $sql, ...$params ),
            ARRAY_A
        );
        return array_map( array( Reservation::class, 'fromArray' ), $results ?: array() );
    }

    /**
     * Find reservations that overlap a time range (pending + approved treated as occupied).
     *
     * Overlap condition: existing.start < newEnd AND existing.end > newStart
     *
     * @return Reservation[]
     */
    public function findOverlapping(
        string $date,
        string $startTime,
        string $endTime,
        ?int   $excludeId = null
    ): array {
        $sql    = "SELECT r.*, rm.room_name, rm.room_code
                   FROM {$this->table} r
                   LEFT JOIN {$this->roomsTable} rm ON r.room_id = rm.id
                   WHERE r.meeting_date = %s
                     AND r.status IN ('pending','approved')
                     AND r.start_time < %s
                     AND r.end_time   > %s";
        $params = array( $date, $endTime, $startTime );

        if ( null !== $excludeId ) {
            $sql     .= ' AND r.id != %d';
            $params[] = $excludeId;
        }

        $sql    .= ' ORDER BY r.start_time ASC';
        $results = $this->db->get_results(
            $this->db->prepare( $sql, ...$params ),
            ARRAY_A
        );
        return array_map( array( Reservation::class, 'fromArray' ), $results ?: array() );
    }

    /**
     * Find overlapping reservations for a specific room.
     *
     * @return Reservation[]
     */
    public function findOverlappingForRoom(
        int    $roomId,
        string $date,
        string $startTime,
        string $endTime,
        ?int   $excludeId = null
    ): array {
        $sql    = "SELECT * FROM {$this->table}
                   WHERE room_id      = %d
                     AND meeting_date = %s
                     AND status IN ('pending','approved')
                     AND start_time < %s
                     AND end_time   > %s";
        $params = array( $roomId, $date, $endTime, $startTime );

        if ( null !== $excludeId ) {
            $sql     .= ' AND id != %d';
            $params[] = $excludeId;
        }

        $results = $this->db->get_results(
            $this->db->prepare( $sql, ...$params ),
            ARRAY_A
        );
        return array_map( array( Reservation::class, 'fromArray' ), $results ?: array() );
    }

    /**
     * Create a reservation atomically within a transaction.
     *
     * Uses SELECT … FOR UPDATE to lock conflicting rows before the INSERT,
     * eliminating the check-then-act race condition under concurrent requests.
     *
     * @param  \HDW\MeetingBooking\Services\RoomAllocator $allocator
     * @return array{reservation_id: int|null, room_id: int|null}
     */
    public function createWithinTransaction(
        string $date,
        string $startTime,
        string $endTime,
        array  $data,
        \HDW\MeetingBooking\Services\RoomAllocator $allocator
    ): array {
        $this->db->query( 'START TRANSACTION' );

        // Lock all overlapping rows so no concurrent INSERT can slip through.
        $this->db->query(
            $this->db->prepare(
                "SELECT id FROM {$this->table}
                 WHERE meeting_date = %s
                   AND status IN ('pending','approved')
                   AND start_time < %s
                   AND end_time   > %s
                 FOR UPDATE",
                $date,
                $endTime,
                $startTime
            )
        );

        $room_id = $allocator->allocateReservation( $date, $startTime, $endTime );

        if ( null === $room_id ) {
            $this->db->query( 'ROLLBACK' );
            return array( 'reservation_id' => null, 'room_id' => null );
        }

        $insert_data = array(
            'full_name'     => $data['full_name'],
            'mobile'        => $data['mobile'],
            'email'         => $data['email'],
            'meeting_title' => $data['meeting_title'],
            'meeting_date'  => $data['meeting_date'],
            'start_time'    => $data['start_time'],
            'end_time'      => $data['end_time'],
            'description'   => $data['description'] ?? '',
            'status'        => $data['status']       ?? 'pending',
            'room_id'       => $room_id,
        );

        $this->db->insert(
            $this->table,
            $insert_data,
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d' )
        );

        $reservation_id = (int) $this->db->insert_id;

        if ( ! $reservation_id ) {
            $this->db->query( 'ROLLBACK' );
            return array( 'reservation_id' => null, 'room_id' => null );
        }

        $this->db->query( 'COMMIT' );
        return array( 'reservation_id' => $reservation_id, 'room_id' => $room_id );
    }

    /**
     * Update reservation status and optionally reassign a room.
     */
    public function updateStatus( int $id, string $status, ?int $roomId = null ): bool
    {
        $data    = array( 'status' => $status );
        $formats = array( '%s' );

        if ( null !== $roomId ) {
            $data['room_id'] = $roomId;
            $formats[]       = '%d';
        }

        return (bool) $this->db->update(
            $this->table,
            $data,
            array( 'id' => $id ),
            $formats,
            array( '%d' )
        );
    }

    public function delete( int $id ): bool
    {
        return (bool) $this->db->delete(
            $this->table,
            array( 'id' => $id ),
            array( '%d' )
        );
    }

    public function count(): int
    {
        return (int) $this->db->get_var( "SELECT COUNT(*) FROM {$this->table}" );
    }
}
