<?php

namespace HDW\MeetingBooking\Models;

/**
 * Class Room
 *
 * Domain model representing a meeting room.
 *
 * @package HDW\MeetingBooking\Models
 */
class Room
{
    public int    $id;
    public string $roomName;
    public string $roomCode;
    public bool   $isActive;
    public string $createdAt;
    public string $updatedAt;

    public function __construct(
        int    $id,
        string $roomName,
        string $roomCode,
        bool   $isActive   = true,
        string $createdAt  = '',
        string $updatedAt  = ''
    ) {
        $this->id        = $id;
        $this->roomName  = $roomName;
        $this->roomCode  = $roomCode;
        $this->isActive  = $isActive;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * Create a Room instance from a database row.
     */
    public static function fromArray( array $row ): self
    {
        return new self(
            (int)    ( $row['id']         ?? 0 ),
                     ( $row['room_name']  ?? '' ),
                     ( $row['room_code']  ?? '' ),
            (bool)   ( $row['is_active']  ?? true ),
                     ( $row['created_at'] ?? '' ),
                     ( $row['updated_at'] ?? '' )
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return array(
            'id'         => $this->id,
            'room_name'  => $this->roomName,
            'room_code'  => $this->roomCode,
            'is_active'  => $this->isActive,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        );
    }
}
