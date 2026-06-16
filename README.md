# HDW Meeting Room Booking System

A professional meeting room reservation system for WordPress with conflict detection, automatic room allocation, and admin dashboard.

## Features

- Interactive time slot booking form (public-facing shortcode)
- Automatic room allocation using a greedy algorithm
- Conflict detection to prevent double-bookings
- Interval Partitioning algorithm to calculate minimum rooms required
- Admin dashboard with reservation management (approve / reject / reset)
- Room configuration panel
- Allocation report page

## Requirements

- PHP >= 7.4
- WordPress >= 5.8
- MySQL >= 5.7 (InnoDB)

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Run `composer install` in the plugin root
3. Activate the plugin through the **Plugins** menu in WordPress
4. Use the shortcode `[hdw_booking_form]` on any page

## Shortcode

```
[hdw_booking_form]
```

## Development

```bash
composer install
```

## License

GPL-2.0+
