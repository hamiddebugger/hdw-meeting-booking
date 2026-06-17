<?php
/**
 * Admin Dashboard Template
 *
 * Displays reservation list with search, filter, and status management.
 */

if (!defined('ABSPATH')) {
    exit;
}

use HDW\MeetingBooking\Models\Reservation;

$statuses = [
    ''               => __('All Statuses', 'hdw-meeting-booking'),
    'pending'        => __('Pending', 'hdw-meeting-booking'),
    'approved'       => __('Approved', 'hdw-meeting-booking'),
    'rejected'       => __('Rejected', 'hdw-meeting-booking'),
];

$statusClasses = [
    'pending'  => 'hdw-status-pending',
    'approved' => 'hdw-status-approved',
    'rejected' => 'hdw-status-rejected',
];

$currentStatus = isset($_GET['hdw_status']) ? sanitize_text_field($_GET['hdw_status']) : '';
$currentSearch = isset($_GET['hdw_search']) ? sanitize_text_field($_GET['hdw_search']) : '';
$currentDate = isset($_GET['hdw_date']) ? sanitize_text_field($_GET['hdw_date']) : '';
?>

<div class="wrap hdw-admin-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Filter Bar -->
    <div class="hdw-filter-bar">
        <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <input type="hidden" name="page" value="hdw-meeting-bookings" />

            <div class="hdw-filter-group">
                <label for="hdw_search"><?php _e('Search', 'hdw-meeting-booking'); ?></label>
                <input
                    type="text"
                    id="hdw_search"
                    name="hdw_search"
                    value="<?php echo esc_attr($currentSearch); ?>"
                    placeholder="<?php esc_attr_e('Name or Mobile...', 'hdw-meeting-booking'); ?>"
                />
            </div>

            <div class="hdw-filter-group">
                <label for="hdw_date"><?php _e('Date', 'hdw-meeting-booking'); ?></label>
                <input
                    type="date"
                    id="hdw_date"
                    name="hdw_date"
                    value="<?php echo esc_attr($currentDate); ?>"
                />
            </div>

            <div class="hdw-filter-group">
                <label for="hdw_status"><?php _e('Status', 'hdw-meeting-booking'); ?></label>
                <select id="hdw_status" name="hdw_status">
                    <?php foreach ($statuses as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($currentStatus, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="hdw-filter-group hdw-filter-actions">
                <button type="submit" class="button button-primary">
                    <?php _e('Filter', 'hdw-meeting-booking'); ?>
                </button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=hdw-meeting-bookings')); ?>" class="button">
                    <?php _e('Reset', 'hdw-meeting-booking'); ?>
                </a>
            </div>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="hdw-stats-cards">
        <div class="hdw-stat-card hdw-stat-total">
            <span class="hdw-stat-number"><?php echo esc_html($statusCounts['total']); ?></span>
            <span class="hdw-stat-label"><?php _e('Total', 'hdw-meeting-booking'); ?></span>
        </div>
        <div class="hdw-stat-card hdw-stat-pending">
            <span class="hdw-stat-number"><?php echo esc_html($statusCounts['pending']); ?></span>
            <span class="hdw-stat-label"><?php _e('Pending', 'hdw-meeting-booking'); ?></span>
        </div>
        <div class="hdw-stat-card hdw-stat-approved">
            <span class="hdw-stat-number"><?php echo esc_html($statusCounts['approved']); ?></span>
            <span class="hdw-stat-label"><?php _e('Approved', 'hdw-meeting-booking'); ?></span>
        </div>
        <div class="hdw-stat-card hdw-stat-rejected">
            <span class="hdw-stat-number"><?php echo esc_html($statusCounts['rejected']); ?></span>
            <span class="hdw-stat-label"><?php _e('Rejected', 'hdw-meeting-booking'); ?></span>
        </div>
    </div>

    <!-- Reservations Table -->
    <div class="hdw-table-container">
        <table class="wp-list-table widefat fixed striped hdw-reservations-table">
            <thead>
                <tr>
                    <th><?php _e('ID', 'hdw-meeting-booking'); ?></th>
                    <th><?php _e('Requester', 'hdw-meeting-booking'); ?></th>
                    <th><?php _e('Contact', 'hdw-meeting-booking'); ?></th>
                    <th><?php _e('Meeting Title', 'hdw-meeting-booking'); ?></th>
                    <th><?php _e('Date & Time', 'hdw-meeting-booking'); ?></th>
                    <th><?php _e('Room', 'hdw-meeting-booking'); ?></th>
                    <th><?php _e('Status', 'hdw-meeting-booking'); ?></th>
                    <th><?php _e('Actions', 'hdw-meeting-booking'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservations)) : ?>
                    <tr>
                        <td colspan="8" class="hdw-no-data">
                            <?php _e('No reservations found.', 'hdw-meeting-booking'); ?>
                        </td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($reservations as $reservation) : ?>
                        <tr data-reservation-id="<?php echo esc_attr($reservation->id); ?>">
                            <td><?php echo esc_html($reservation->id); ?></td>
                            <td>
                                <strong><?php echo esc_html($reservation->fullName); ?></strong>
                            </td>
                            <td>
                                <div><?php echo esc_html($reservation->mobile); ?></div>
                                <div class="hdw-email"><?php echo esc_html($reservation->email); ?></div>
                            </td>
                            <td><?php echo esc_html($reservation->meetingTitle); ?></td>
                            <td>
                                <div><?php echo esc_html($reservation->meetingDate); ?></div>
                                <div class="hdw-time-range">
                                    <?php echo esc_html($reservation->startTime . ' - ' . $reservation->endTime); ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($reservation->roomName) : ?>
                                    <span class="hdw-room-badge">
                                        <?php echo esc_html($reservation->roomName); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="hdw-room-badge hdw-room-unassigned">
                                        <?php _e('Not Assigned', 'hdw-meeting-booking'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="hdw-status-badge <?php echo esc_attr($statusClasses[$reservation->status] ?? ''); ?>">
                                    <?php echo esc_html($reservation->getStatusLabel()); ?>
                                </span>
                            </td>
                            <td>
                                <div class="hdw-action-buttons">
                                    <?php if ($reservation->status !== Reservation::STATUS_APPROVED) : ?>
                                        <button
                                            type="button"
                                            class="button button-small hdw-btn-approve"
                                            data-action="approve"
                                            data-id="<?php echo esc_attr($reservation->id); ?>"
                                        >
                                            <?php _e('Approve', 'hdw-meeting-booking'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($reservation->status !== Reservation::STATUS_REJECTED) : ?>
                                        <button
                                            type="button"
                                            class="button button-small hdw-btn-reject"
                                            data-action="reject"
                                            data-id="<?php echo esc_attr($reservation->id); ?>"
                                        >
                                            <?php _e('Reject', 'hdw-meeting-booking'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($reservation->status !== Reservation::STATUS_PENDING) : ?>
                                        <button
                                            type="button"
                                            class="button button-small hdw-btn-pending"
                                            data-action="pending"
                                            data-id="<?php echo esc_attr($reservation->id); ?>"
                                        >
                                            <?php _e('Set Pending', 'hdw-meeting-booking'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1) : ?>
        <div class="hdw-pagination">
            <?php
            $currentPage = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $baseUrl = admin_url('admin.php?page=hdw-meeting-bookings');

            if ($currentSearch) $baseUrl .= '&hdw_search=' . urlencode($currentSearch);
            if ($currentDate) $baseUrl .= '&hdw_date=' . urlencode($currentDate);
            if ($currentStatus) $baseUrl .= '&hdw_status=' . urlencode($currentStatus);

            // Previous
            if ($currentPage > 1) :
                $prevUrl = $baseUrl . '&paged=' . ($currentPage - 1);
                ?>
                <a href="<?php echo esc_url($prevUrl); ?>" class="button">&laquo; <?php _e('Previous', 'hdw-meeting-booking'); ?></a>
            <?php endif; ?>

            <span class="hdw-page-info">
                <?php
                printf(
                    esc_html__('Page %1$d of %2$d', 'hdw-meeting-booking'),
                    $currentPage,
                    $totalPages
                );
                ?>
            </span>

            <?php if ($currentPage < $totalPages) :
                $nextUrl = $baseUrl . '&paged=' . ($currentPage + 1);
                ?>
                <a href="<?php echo esc_url($nextUrl); ?>" class="button"><?php _e('Next', 'hdw-meeting-booking'); ?> &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
