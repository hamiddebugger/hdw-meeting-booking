<?php

namespace HDW\MeetingBooking;

/**
 * Class Deactivator
 *
 * Fired during plugin deactivation.
 * Note: tables and data are preserved on deactivation.
 * Use uninstall.php for full cleanup.
 *
 * @package HDW\MeetingBooking
 */
class Deactivator
{
    /**
     * Deactivate the plugin.
     * Intentionally a no-op: user data is retained until explicit uninstall.
     */
    public static function deactivate(): void
    {
        // No cleanup on deactivation — data is preserved.
    }
}
