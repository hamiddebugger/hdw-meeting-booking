<?php
/**
 * Booking Form Template
 *
 * The public-facing reservation form with calendar date picker
 * and interactive time slot selection.
 */

if (!defined('ABSPATH')) {
    exit;
}

use HDW\MeetingBooking\Utils\Security;
?>

<div class="hdw-booking-container" id="hdw-booking-form">
    <h2 class="hdw-form-title"><?php _e('Reserve a Meeting Room', 'hdw-meeting-booking'); ?></h2>

    <form class="hdw-booking-form" method="post" novalidate>
        <?php echo wp_kses(Security::createNonceField('hdw_booking_nonce'), ['input' => ['type' => [], 'name' => [], 'value' => [], 'id' => []]]); ?>

        <!-- Personal Information -->
        <div class="hdw-form-section">
            <h3 class="hdw-section-title"><?php _e('Personal Information', 'hdw-meeting-booking'); ?></h3>

            <div class="hdw-form-row">
                <div class="hdw-form-group">
                    <label for="hdw_full_name">
                        <?php _e('Full Name', 'hdw-meeting-booking'); ?> <span class="hdw-required">*</span>
                    </label>
                    <input
                        type="text"
                        id="hdw_full_name"
                        name="full_name"
                        required
                        minlength="2"
                        placeholder="<?php esc_attr_e('Enter your full name', 'hdw-meeting-booking'); ?>"
                    />
                </div>

                <div class="hdw-form-group">
                    <label for="hdw_mobile">
                        <?php _e('Mobile Number', 'hdw-meeting-booking'); ?> <span class="hdw-required">*</span>
                    </label>
                    <input
                        type="tel"
                        id="hdw_mobile"
                        name="mobile"
                        required
                        placeholder="<?php esc_attr_e('e.g. 09123456789', 'hdw-meeting-booking'); ?>"
                        dir="ltr"
                    />
                </div>
            </div>

            <div class="hdw-form-group">
                <label for="hdw_email">
                    <?php _e('Email Address', 'hdw-meeting-booking'); ?> <span class="hdw-required">*</span>
                </label>
                <input
                    type="email"
                    id="hdw_email"
                    name="email"
                    required
                    placeholder="<?php esc_attr_e('your@email.com', 'hdw-meeting-booking'); ?>"
                    dir="ltr"
                />
            </div>
        </div>

        <!-- Meeting Information -->
        <div class="hdw-form-section">
            <h3 class="hdw-section-title"><?php _e('Meeting Information', 'hdw-meeting-booking'); ?></h3>

            <div class="hdw-form-group">
                <label for="hdw_meeting_title">
                    <?php _e('Meeting Title', 'hdw-meeting-booking'); ?> <span class="hdw-required">*</span>
                </label>
                <input
                    type="text"
                    id="hdw_meeting_title"
                    name="meeting_title"
                    required
                    minlength="2"
                    placeholder="<?php esc_attr_e('Enter meeting title', 'hdw-meeting-booking'); ?>"
                />
            </div>

            <!-- Date Picker -->
            <div class="hdw-form-group">
                <label for="hdw_meeting_date">
                    <?php _e('Meeting Date', 'hdw-meeting-booking'); ?> <span class="hdw-required">*</span>
                </label>
                <input
                    type="date"
                    id="hdw_meeting_date"
                    name="meeting_date"
                    required
                    min="<?php echo esc_attr(date('Y-m-d')); ?>"
                />
            </div>

            <!-- Time Slots Container -->
            <div class="hdw-form-group hdw-time-slots-wrapper" id="hdw-time-slots-wrapper" style="display: none;">
                <label><?php _e('Available Time Slots', 'hdw-meeting-booking'); ?></label>
                <div class="hdw-time-slots" id="hdw-time-slots">
                    <!-- Slots loaded via AJAX -->
                </div>
                <input type="hidden" id="hdw_start_time" name="start_time" />
                <input type="hidden" id="hdw_end_time" name="end_time" />
            </div>

            <!-- Custom Time Input (if slots don't work) -->
            <div class="hdw-form-row hdw-custom-time" id="hdw-custom-time" style="display: none;">
                <div class="hdw-form-group">
                    <label for="hdw_custom_start"><?php _e('Start Time', 'hdw-meeting-booking'); ?></label>
                    <input type="time" id="hdw_custom_start" name="custom_start" step="900" />
                </div>
                <div class="hdw-form-group">
                    <label for="hdw_custom_end"><?php _e('End Time', 'hdw-meeting-booking'); ?></label>
                    <input type="time" id="hdw_custom_end" name="custom_end" step="900" />
                </div>
            </div>

            <!-- Description -->
            <div class="hdw-form-group">
                <label for="hdw_description">
                    <?php _e('Description', 'hdw-meeting-booking'); ?>
                    <span class="hdw-optional">(<?php _e('Optional', 'hdw-meeting-booking'); ?>)</span>
                </label>
                <textarea
                    id="hdw_description"
                    name="description"
                    rows="3"
                    placeholder="<?php esc_attr_e('Additional details about the meeting...', 'hdw-meeting-booking'); ?>"
                ></textarea>
            </div>
        </div>

        <!-- Submit -->
        <div class="hdw-form-actions">
            <button type="submit" class="hdw-submit-btn" id="hdw-submit-btn">
                <?php _e('Submit Reservation Request', 'hdw-meeting-booking'); ?>
            </button>
        </div>

        <!-- Messages -->
        <div class="hdw-form-message" id="hdw-form-message" role="alert" aria-live="polite"></div>
    </form>

    <!-- Success Modal -->
    <div class="hdw-modal" id="hdw-success-modal" style="display: none;">
        <div class="hdw-modal-content">
            <div class="hdw-modal-icon">&#10004;</div>
            <h3><?php _e('Reservation Submitted!', 'hdw-meeting-booking'); ?></h3>
            <p id="hdw-modal-message"></p>
            <button type="button" class="hdw-modal-close" id="hdw-modal-close">
                <?php _e('OK', 'hdw-meeting-booking'); ?>
            </button>
        </div>
    </div>
</div>
