<?php

namespace HDW\MeetingBooking\Utils;

/**
 * Class Security
 *
 * Provides security utilities for the plugin.
 * Handles nonces, capability checks, and data sanitization.
 *
 * @package HDW\MeetingBooking\Utils
 */
class Security
{
    /**
     * Create a nonce field for forms.
     */
    public static function createNonceField(string $action = 'hdw_booking_nonce'): string
    {
        return wp_nonce_field($action, 'hdw_nonce', true, false);
    }

    /**
     * Verify a nonce from request.
     */
    public static function verifyNonce(string $action = 'hdw_booking_nonce'): bool
    {
        $nonce = $_REQUEST['hdw_nonce'] ?? '';
        return wp_verify_nonce($nonce, $action) !== false;
    }

    /**
     * Verify admin nonce.
     */
    public static function verifyAdminNonce(string $action = 'hdw_admin_nonce'): bool
    {
        $nonce = $_REQUEST['_wpnonce'] ?? '';
        return wp_verify_nonce($nonce, $action) !== false;
    }

    /**
     * Check if current user has admin capability.
     */
    public static function requireAdmin(): void
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Access denied. You do not have permission to perform this action.', 'hdw-meeting-booking'), 403);
        }
    }

    /**
     * Sanitize output for HTML display.
     */
    public static function escHtml(string $text): string
    {
        return esc_html($text);
    }

    /**
     * Sanitize attribute for HTML.
     */
    public static function escAttr(string $text): string
    {
        return esc_attr($text);
    }

    /**
     * Sanitize URL.
     */
    public static function escUrl(string $url): string
    {
        return esc_url($url);
    }

    /**
     * Sanitize textarea content.
     */
    public static function sanitizeTextarea(string $text): string
    {
        return sanitize_textarea_field($text);
    }

    /**
     * Check if request is AJAX.
     */
    public static function isAjax(): bool
    {
        return defined('DOING_AJAX') && DOING_AJAX;
    }

    /**
     * Send JSON success response with security headers.
     *
     * @param mixed $data
     */
    public static function sendJsonSuccess($data = null): void
    {
        self::setSecurityHeaders();
        wp_send_json_success($data);
    }

    /**
     * Send JSON error response with security headers.
     */
    public static function sendJsonError(string $message, int $statusCode = 400): void
    {
        self::setSecurityHeaders();
        wp_send_json_error($message, $statusCode);
    }

    /**
     * Set security headers for AJAX responses.
     */
    private static function setSecurityHeaders(): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
        }
    }
}
