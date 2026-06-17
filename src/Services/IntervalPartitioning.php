<?php

namespace HDW\MeetingBooking\Services;

use HDW\MeetingBooking\Database\ReservationRepository;
use HDW\MeetingBooking\Models\Reservation;

/**
 * Class IntervalPartitioning
 *
 * Implements the Interval Partitioning algorithm to calculate
 * the minimum number of meeting rooms required.
 *
 * Algorithm: Maximum Overlap (Sweep Line)
 * Time Complexity: O(n log n)
 *
 * @package HDW\MeetingBooking\Services
 */
class IntervalPartitioning
{
    private ReservationRepository $repository;

    public function __construct()
    {
        $this->repository = new ReservationRepository();
    }

    /**
     * Calculate the minimum number of rooms needed for a given date.
     * Considers both pending and approved reservations.
     */
    public function calculateMinimumRooms(string $date): int
    {
        $reservations = $this->repository->findByDate($date);

        // Filter out rejected reservations
        $activeReservations = array_filter($reservations, function (Reservation $r) {
            return $r->status !== Reservation::STATUS_REJECTED;
        });

        if (empty($activeReservations)) {
            return 0;
        }

        return $this->computeMaxOverlap($activeReservations);
    }

    /**
     * Calculate minimum rooms from an array of reservations.
     *
     * @param Reservation[] $reservations
     */
    public function calculateFromReservations(array $reservations): int
    {
        $activeReservations = array_filter($reservations, function (Reservation $r) {
            return $r->status !== Reservation::STATUS_REJECTED;
        });

        if (empty($activeReservations)) {
            return 0;
        }

        return $this->computeMaxOverlap($activeReservations);
    }

    /**
     * Get detailed allocation report for a date.
     * Shows which reservations overlap at each time point.
     */
    public function getAllocationReport(string $date): array
    {
        $reservations = $this->repository->findByDate($date);
        $activeReservations = array_filter($reservations, function (Reservation $r) {
            return $r->status !== Reservation::STATUS_REJECTED;
        });

        $events = $this->buildEvents($activeReservations);
        $maxOverlap = 0;
        $currentOverlap = 0;
        $peakTime = null;
        $overlappingAtPeak = [];

        foreach ($events as $event) {
            if ($event['type'] === 'start') {
                $currentOverlap++;
                if ($currentOverlap > $maxOverlap) {
                    $maxOverlap = $currentOverlap;
                    $peakTime = $event['time'];
                    $overlappingAtPeak = $this->findOverlappingAtTime($activeReservations, $event['time']);
                }
            } else {
                $currentOverlap--;
            }
        }

        return [
            'date'                => $date,
            'minimum_rooms'       => $maxOverlap,
            'total_reservations'  => count($activeReservations),
            'peak_time'           => $peakTime,
            'overlapping_at_peak' => array_map(function (Reservation $r) {
                return [
                    'id'          => $r->id,
                    'title'       => $r->meetingTitle,
                    'time_range'  => $r->startTime . ' - ' . $r->endTime,
                    'requester'   => $r->fullName,
                    'status'      => $r->status,
                ];
            }, $overlappingAtPeak),
        ];
    }

    /**
     * Compute maximum overlap using sweep line algorithm.
     *
     * @param Reservation[] $reservations
     */
    private function computeMaxOverlap(array $reservations): int
    {
        $events = $this->buildEvents($reservations);

        $maxOverlap = 0;
        $currentOverlap = 0;

        foreach ($events as $event) {
            if ($event['type'] === 'start') {
                $currentOverlap++;
                $maxOverlap = max($maxOverlap, $currentOverlap);
            } else {
                $currentOverlap--;
            }
        }

        return $maxOverlap;
    }

    /**
     * Build time-ordered events from reservations.
     *
     * @param Reservation[] $reservations
     * @return array<array{time: string, type: string}>
     */
    private function buildEvents(array $reservations): array
    {
        $events = [];

        foreach ($reservations as $reservation) {
            $events[] = [
                'time' => $reservation->startTime,
                'type' => 'start',
            ];
            $events[] = [
                'time' => $reservation->endTime,
                'type' => 'end',
            ];
        }

        // Sort by time, with end events before start events at the same time
        usort($events, function (array $a, array $b): int {
            if ($a['time'] === $b['time']) {
                // End events should come before start events at the same time
                return $a['type'] === 'end' ? -1 : 1;
            }
            return strcmp($a['time'], $b['time']);
        });

        return $events;
    }

    /**
     * Find reservations overlapping at a specific time.
     *
     * @param Reservation[] $reservations
     * @return Reservation[]
     */
    private function findOverlappingAtTime(array $reservations, string $time): array
    {
        return array_filter($reservations, function (Reservation $r) use ($time) {
            return $r->startTime <= $time && $r->endTime > $time;
        });
    }
}
