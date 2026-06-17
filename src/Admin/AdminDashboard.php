<?php

namespace HDW\MeetingBooking\Admin;

use HDW\MeetingBooking\Services\ReservationManager;
use HDW\MeetingBooking\Utils\Security;

/**
 * Class AdminDashboard
 *
 * Handles the main admin dashboard with reservation listing.
 * Uses custom table implementation for flexibility.
 *
 * @package HDW\MeetingBooking\Admin
 */
class AdminDashboard
{
    private ReservationManager $reservationManager;

    public function __construct()
    {
        $this->reservationManager = new ReservationManager();
    }

    /**
     * Register admin hooks.
     */
    public function registerHooks(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_hdw_update_reservation_status', [$this, 'handleStatusUpdate']);
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueueAssets(string $hook): void
    {
        if (strpos($hook, 'hdw') === false) {
            return;
        }

        wp_enqueue_style(
            'hdw-admin-css',
            HDW_MEETING_BOOKING_PLUGIN_URL . 'admin/css/admin-style.css',
            [],
            HDW_MEETING_BOOKING_VERSION
        );

        wp_enqueue_script(
            'hdw-admin-js',
            HDW_MEETING_BOOKING_PLUGIN_URL . 'admin/js/admin-script.js',
            [],
            HDW_MEETING_BOOKING_VERSION,
            true
        );

        wp_localize_script('hdw-admin-js', 'hdwAdminData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('hdw_admin_nonce'),
            'strings' => [
                'confirmApprove' => __('Are you sure you want to approve this reservation?', 'hdw-meeting-booking'),
                'confirmReject'  => __('Are you sure you want to reject this reservation?', 'hdw-meeting-booking'),
                'confirmPending' => __('Reset this reservation back to pending?', 'hdw-meeting-booking'),
                'processing'     => __('Processing...', 'hdw-meeting-booking'),
                'error'          => __('An error occurred. Please try again.', 'hdw-meeting-booking'),
            ],
        ]);
    }

    /**
     * Render the dashboard.
     */
    public function render(): void
    {
        Security::requireAdmin();

        $filters = $this->getFilters();
        $page    = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $perPage = 20;

        $result = $this->fetchReservations($filters, $page, $perPage);

        $reservations = $result['items'];
        $total        = $result['total'];
        $totalPages   = (int) ceil($total / $perPage);

        // Count by status across ALL records (not just current page)
        $statusCounts = $this->fetchStatusCounts();

        require_once HDW_MEETING_BOOKING_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }

    /**
     * Handle status update via AJAX.
     */
    public function handleStatusUpdate(): void
    {
        Security::requireAdmin();

        if (!Security::verifyAdminNonce()) {
            Security::sendJsonError(__('Invalid security token.', 'hdw-meeting-booking'), 403);
        }

        $reservationId = isset($_POST['reservation_id']) ? intval($_POST['reservation_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';

        // Normalize action verbs → status values
        $normalize = ['approve' => 'approved', 'reject' => 'rejected', 'pending' => 'pending'];
        if (isset($normalize[$status])) {
            $status = $normalize[$status];
        }

        if (!$reservationId || !in_array($status, ['approved', 'rejected', 'pending'], true)) {
            Security::sendJsonError(__('Invalid request parameters.', 'hdw-meeting-booking'));
        }

        if ($status === 'approved') {
            $result = $this->reservationManager->approveReservation($reservationId);
        } elseif ($status === 'rejected') {
            $result = $this->reservationManager->rejectReservation($reservationId);
        } else {
            $result = $this->reservationManager->resetToPending($reservationId);
        }

        if ($result['success']) {
            Security::sendJsonSuccess([
                'message'   => $result['message'],
                'status'    => $status,
                'room_name' => $result['room_name'] ?? null,
                'room_id'   => $result['room_id']   ?? null,
            ]);
        } else {
            Security::sendJsonError($result['message']);
        }
    }

    /**
     * Get filters from request.
     */
    private function getFilters(): array
    {
        $filters = [];

        if (!empty($_GET['hdw_search'])) {
            $filters['search'] = sanitize_text_field($_GET['hdw_search']);
        }

        if (!empty($_GET['hdw_date'])) {
            $filters['date'] = sanitize_text_field($_GET['hdw_date']);
        }

        if (!empty($_GET['hdw_status'])) {
            $filters['status'] = sanitize_text_field($_GET['hdw_status']);
        }

        return $filters;
    }

    /**
     * Fetch reservations with filters.
     */
    private function fetchReservations(array $filters, int $page, int $perPage): array
    {
        $repository = new \HDW\MeetingBooking\Database\ReservationRepository();
        return $repository->findWithFilters($filters, $page, $perPage);
    }

    /**
     * Fetch reservation counts grouped by status (all records, no filters).
     *
     * @return array{pending: int, approved: int, rejected: int, total: int}
     */
    private function fetchStatusCounts(): array
    {
        global $wpdb;
        $table = $wpdb->prefix . \HDW\MeetingBooking\Database\Database::TABLE_RESERVATIONS;

        $rows = $wpdb->get_results(
            "SELECT status, COUNT(*) as cnt FROM {$table} GROUP BY status",
            ARRAY_A
        );

        $counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0, 'total' => 0];
        foreach ($rows as $row) {
            $s = $row['status'];
            if (isset($counts[$s])) {
                $counts[$s] = (int) $row['cnt'];
            }
            $counts['total'] += (int) $row['cnt'];
        }

        return $counts;
    }
}
