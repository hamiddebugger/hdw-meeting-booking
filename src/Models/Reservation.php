<?php

namespace HDW\MeetingBooking\Models;

/**
 * Class Reservation
 *
 * Domain model representing a meeting room reservation.
 * Encapsulates reservation data and business rules.
 *
 * @package HDW\MeetingBooking\Models
 */
class Reservation
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public int     $id;
    public ?int    $roomId;
    public string  $fullName;
    public string  $mobile;
    public string  $email;
    public string  $meetingTitle;
    public string  $meetingDate;
    public string  $startTime;
    public string  $endTime;
    public string  $description;
    public string  $status;
    public string  $createdAt;
    public string  $updatedAt;

    // Joined room details
    public ?string $roomName = null;
    public ?string $roomCode = null;

    public function __construct(
        int     $id,
        ?int    $roomId,
        string  $fullName,
        string  $mobile,
        string  $email,
        string  $meetingTitle,
        string  $meetingDate,
        string  $startTime,
        string  $endTime,
        string  $description = '',
        string  $status      = self::STATUS_PENDING,
        string  $createdAt   = '',
        string  $updatedAt   = ''
    ) {
        $this->id           = $id;
        $this->roomId       = $roomId;
        $this->fullName     = $fullName;
        $this->mobile       = $mobile;
        $this->email        = $email;
        $this->meetingTitle = $meetingTitle;
        $this->meetingDate  = $meetingDate;
        $this->startTime    = $startTime;
        $this->endTime      = $endTime;
        $this->description  = $description;
        $this->status       = $status;
        $this->createdAt    = $createdAt;
        $this->updatedAt    = $updatedAt;
    }

    /**
     * Create a Reservation from a database row.
     * Normalises MySQL TIME columns from HH:MM:SS to HH:MM.
     */
    public static function fromArray( array $row ): self
    {
        $reservation = new self(
            (int) ( $row['id']            ?? 0 ),
            isset( $row['room_id'] ) ? (int) $row['room_id'] : null,
                  ( $row['full_name']     ?? '' ),
                  ( $row['mobile']        ?? '' ),
                  ( $row['email']         ?? '' ),
                  ( $row['meeting_title'] ?? '' ),
                  ( $row['meeting_date']  ?? '' ),
                  ( $row['start_time']    ?? '' ),
                  ( $row['end_time']      ?? '' ),
                  ( $row['description']   ?? '' ),
                  ( $row['status']        ?? self::STATUS_PENDING ),
                  ( $row['created_at']    ?? '' ),
                  ( $row['updated_at']    ?? '' )
        );

        $reservation->roomName = $row['room_name'] ?? null;
        $reservation->roomCode = $row['room_code'] ?? null;

        // MySQL TIME columns return HH:MM:SS — normalise to HH:MM for
        // consistent string comparisons throughout the application.
        $reservation->startTime = substr( $reservation->startTime, 0, 5 );
        $reservation->endTime   = substr( $reservation->endTime,   0, 5 );

        return $reservation;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return array(
            'id'            => $this->id,
            'room_id'       => $this->roomId,
            'full_name'     => $this->fullName,
            'mobile'        => $this->mobile,
            'email'         => $this->email,
            'meeting_title' => $this->meetingTitle,
            'meeting_date'  => $this->meetingDate,
            'start_time'    => $this->startTime,
            'end_time'      => $this->endTime,
            'description'   => $this->description,
            'status'        => $this->status,
            'created_at'    => $this->createdAt,
            'updated_at'    => $this->updatedAt,
            'room_name'     => $this->roomName,
            'room_code'     => $this->roomCode,
        );
    }

    /**
     * Check whether this reservation overlaps with a given time range.
     */
    public function overlapsWith( string $start, string $end ): bool
    {
        return $this->startTime < $end && $this->endTime > $start;
    }

    /**
     * Return a human-readable status label.
     */
    public function getStatusLabel(): string
    {
        $labels = array(
            self::STATUS_PENDING  => __( 'Pending',  'hdw-meeting-booking' ),
            self::STATUS_APPROVED => __( 'Approved', 'hdw-meeting-booking' ),
            self::STATUS_REJECTED => __( 'Rejected', 'hdw-meeting-booking' ),
        );

        return $labels[ $this->status ] ?? $this->status;
    }

    /**
     * Return meeting duration in minutes.
     */
    public function getDurationMinutes(): int
    {
        $start = strtotime( $this->meetingDate . ' ' . $this->startTime );
        $end   = strtotime( $this->meetingDate . ' ' . $this->endTime );
        return (int) ( ( $end - $start ) / 60 );
    }
}
