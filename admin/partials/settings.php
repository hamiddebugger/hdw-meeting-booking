<?php
/**
 * Room Settings Page Template
 *
 * Allows administrators to configure the number of meeting rooms.
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap hdw-admin-wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="hdw-settings-container">
        <!-- Room Count Form -->
        <div class="hdw-settings-card">
            <h2><?php _e('Room Configuration', 'hdw-meeting-booking'); ?></h2>

            <form method="post" action="">
                <?php wp_nonce_field('hdw_room_settings_nonce'); ?>
                <input type="hidden" name="hdw_update_rooms" value="1" />

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="hdw_room_count">
                                <?php _e('Number of Meeting Rooms', 'hdw-meeting-booking'); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="number"
                                id="hdw_room_count"
                                name="hdw_room_count"
                                value="<?php echo esc_attr($currentCount); ?>"
                                min="1"
                                max="50"
                                class="small-text"
                                required
                            />
                            <p class="description">
                                <?php _e('Set the total number of available meeting rooms. Existing reservations will not be affected.', 'hdw-meeting-booking'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Update Rooms', 'hdw-meeting-booking')); ?>
            </form>
        </div>

        <!-- Current Rooms List -->
        <div class="hdw-settings-card">
            <h2><?php _e('Current Active Rooms', 'hdw-meeting-booking'); ?></h2>

            <?php if (empty($activeRooms)) : ?>
                <p><?php _e('No active rooms found.', 'hdw-meeting-booking'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('ID', 'hdw-meeting-booking'); ?></th>
                            <th><?php _e('Room Name', 'hdw-meeting-booking'); ?></th>
                            <th><?php _e('Room Code', 'hdw-meeting-booking'); ?></th>
                            <th><?php _e('Status', 'hdw-meeting-booking'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($activeRooms as $room) : ?>
                            <tr>
                                <td><?php echo esc_html($room->id); ?></td>
                                <td><?php echo esc_html($room->roomName); ?></td>
                                <td><code><?php echo esc_html($room->roomCode); ?></code></td>
                                <td>
                                    <span class="hdw-status-badge hdw-status-approved">
                                        <?php _e('Active', 'hdw-meeting-booking'); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
