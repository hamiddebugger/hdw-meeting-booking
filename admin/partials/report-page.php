<?php
/**
 * Allocation Report Page Template
 *
 * Displays room allocation analysis using the Interval Partitioning algorithm.
 */

if (!defined('ABSPATH')) {
    exit;
}

use HDW\MeetingBooking\Services\ReservationManager;
use HDW\MeetingBooking\Services\RoomManager;
use HDW\MeetingBooking\Utils\Security;

Security::requireAdmin();

$reservationManager = new ReservationManager();
$roomManager = new RoomManager();

$selectedDate = isset($_GET['report_date']) ? sanitize_text_field($_GET['report_date']) : date('Y-m-d');
$report = $reservationManager->getAllocationReport($selectedDate);
$configuredRooms = $roomManager->getConfiguredRoomCount();
$isSufficient = $configuredRooms >= $report['minimum_rooms'];
?>

<div class="wrap hdw-admin-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Date Selector -->
    <div class="hdw-filter-bar">
        <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <input type="hidden" name="page" value="hdw-allocation-report" />

            <div class="hdw-filter-group">
                <label for="report_date"><?php _e('Select Date', 'hdw-meeting-booking'); ?></label>
                <input
                    type="date"
                    id="report_date"
                    name="report_date"
                    value="<?php echo esc_attr($selectedDate); ?>"
                />
            </div>

            <div class="hdw-filter-group hdw-filter-actions">
                <button type="submit" class="button button-primary">
                    <?php _e('Generate Report', 'hdw-meeting-booking'); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Report Summary -->
    <div class="hdw-report-summary">
        <div class="hdw-report-card <?php echo $isSufficient ? 'hdw-report-ok' : 'hdw-report-warning'; ?>">
            <h3><?php _e('Room Sufficiency Analysis', 'hdw-meeting-booking'); ?></h3>
            <div class="hdw-report-metric">
                <span class="hdw-metric-label"><?php _e('Configured Rooms:', 'hdw-meeting-booking'); ?></span>
                <span class="hdw-metric-value"><?php echo esc_html($configuredRooms); ?></span>
            </div>
            <div class="hdw-report-metric">
                <span class="hdw-metric-label"><?php _e('Minimum Required:', 'hdw-meeting-booking'); ?></span>
                <span class="hdw-metric-value hdw-highlight"><?php echo esc_html($report['minimum_rooms']); ?></span>
            </div>
            <div class="hdw-report-metric">
                <span class="hdw-metric-label"><?php _e('Total Reservations:', 'hdw-meeting-booking'); ?></span>
                <span class="hdw-metric-value"><?php echo esc_html($report['total_reservations']); ?></span>
            </div>
            <?php if ($report['peak_time']) : ?>
                <div class="hdw-report-metric">
                    <span class="hdw-metric-label"><?php _e('Peak Time:', 'hdw-meeting-booking'); ?></span>
                    <span class="hdw-metric-value"><?php echo esc_html($report['peak_time']); ?></span>
                </div>
            <?php endif; ?>

            <div class="hdw-report-status">
                <?php if ($isSufficient) : ?>
                    <span class="hdw-status-badge hdw-status-approved">
                        <?php _e('Room capacity is sufficient.', 'hdw-meeting-booking'); ?>
                    </span>
                <?php else : ?>
                    <span class="hdw-status-badge hdw-status-rejected">
                        <?php
                        printf(
                            esc_html__('Warning: Need %d more room(s) for this date!', 'hdw-meeting-booking'),
                            $report['minimum_rooms'] - $configuredRooms
                        );
                        ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Overlapping Reservations at Peak -->
    <?php if (!empty($report['overlapping_at_peak'])) : ?>
        <div class="hdw-report-card">
            <h3><?php _e('Overlapping Reservations at Peak Time', 'hdw-meeting-booking'); ?></h3>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'hdw-meeting-booking'); ?></th>
                        <th><?php _e('Meeting Title', 'hdw-meeting-booking'); ?></th>
                        <th><?php _e('Time Range', 'hdw-meeting-booking'); ?></th>
                        <th><?php _e('Requester', 'hdw-meeting-booking'); ?></th>
                        <th><?php _e('Status', 'hdw-meeting-booking'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report['overlapping_at_peak'] as $item) : ?>
                        <tr>
                            <td><?php echo esc_html($item['id']); ?></td>
                            <td><?php echo esc_html($item['title']); ?></td>
                            <td><?php echo esc_html($item['time_range']); ?></td>
                            <td><?php echo esc_html($item['requester']); ?></td>
                            <td>
                                <span class="hdw-status-badge hdw-status-<?php echo esc_attr($item['status']); ?>">
                                    <?php echo esc_html(ucfirst($item['status'])); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Algorithm Explanation -->
    <div class="hdw-report-card hdw-report-info">
        <h3><?php _e('About the Algorithm', 'hdw-meeting-booking'); ?></h3>
        <p>
            <?php _e('The Interval Partitioning algorithm (Sweep Line) calculates the minimum number of meeting rooms required by finding the maximum number of simultaneous overlapping reservations. This ensures optimal room allocation and helps identify capacity bottlenecks.', 'hdw-meeting-booking'); ?>
        </p>
        <p>
            <?php _e('Time Complexity: O(n log n) where n is the number of reservations.', 'hdw-meeting-booking'); ?>
        </p>
    </div>
</div>
