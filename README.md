# HDW Meeting Room Booking System

A professional WordPress plugin for managing meeting room reservations with conflict detection, automatic room allocation, and interval partitioning algorithm.

## Features

- **Interactive Booking Form**: Calendar-based date picker with visual time slot selection
- **Conflict Detection**: Automatic detection of overlapping reservations
- **Smart Room Allocation**: Greedy algorithm assigns the first available room
- **Interval Partitioning**: Calculates minimum rooms needed using Sweep Line algorithm (O(n log n))
- **Admin Dashboard**: Full reservation management with search, filter, and status controls
- **Room Management**: Dynamically adjust the number of meeting rooms
- **Allocation Report**: Visual analysis of room capacity vs. demand


## Overall Plan — Phases and Commits
```
Phase 1 — chore: project scaffold & architecture
Phase 2 — chore: add composer autoload & gitignore  
Phase 3 — feat: implement database schema & activator
Phase 4 — feat: implement domain models
Phase 5 — feat: implement database repositories
Phase 6 — feat: implement core services (conflict detection, room allocation)
Phase 7 — feat: implement interval partitioning algorithm
Phase 8 — feat: implement reservation manager
Phase 9 — feat: implement admin panel (menu, dashboard, settings)
Phase 10 — feat: implement public booking form & shortcode
Phase 11 — feat: implement AJAX handlers
Phase 12 — style: add admin & public CSS
Phase 13 — fix: race condition with DB transaction on reservation create
Phase 14 — fix: normalize HH:MM:SS time format from MySQL
Phase 15 — docs: add README
```


## Architecture

```
hdw-meeting-booking/
├── hdw-meeting-booking.php          # Main plugin file
├── composer.json                    # PSR-4 autoloading config
├── src/                             # PHP classes (business logic)
│   ├── Main.php                     # Plugin orchestrator
│   ├── Activator.php                # Plugin activation
│   ├── Deactivator.php              # Plugin deactivation
│   ├── Database/
│   │   ├── Database.php             # Table constants & wpdb access
│   │   ├── RoomRepository.php       # Room data access
│   │   └── ReservationRepository.php # Reservation data access
│   ├── Models/
│   │   ├── Room.php                 # Room domain model
│   │   └── Reservation.php          # Reservation domain model
│   ├── Services/
│   │   ├── RoomManager.php          # Room business logic
│   │   ├── ReservationManager.php   # Reservation orchestration
│   │   ├── ConflictDetector.php     # Overlap detection
│   │   ├── RoomAllocator.php        # Room assignment logic
│   │   └── IntervalPartitioning.php # Minimum rooms algorithm
│   ├── Admin/
│   │   ├── AdminMenu.php            # Menu registration
│   │   ├── AdminDashboard.php       # Dashboard controller
│   │   └── SettingsPage.php         # Settings controller
│   ├── Public/
│   │   ├── BookingForm.php          # Shortcode & assets
│   │   └── AjaxHandler.php          # AJAX endpoints
│   └── Utils/
│       ├── Validator.php            # Input validation
│       └── Security.php             # Security utilities
├── admin/                           # Admin assets (no PHP logic)
│   ├── css/admin-style.css
│   ├── js/admin-script.js (ES6)
│   └── partials/
├── public/                          # Public assets (no PHP logic)
│   ├── css/booking-style.css
│   ├── js/booking-script.js (ES6)
│   └── partials/
└── vendor/                          # Composer autoload
```

## Technical Stack

- **PHP 7.4+** with PSR-4 autoloading via Composer
- **Vanilla ES6+ JavaScript** (no jQuery)
- **WordPress Coding Standards** with custom table prefix `hdw_`
- **Separation of Concerns**: Admin/Public assets contain no PHP logic
- **Security**: Nonce verification, prepared statements, capability checks

## Installation

1. Upload to `/wp-content/plugins/hdw-meeting-booking/`
2. Run `composer install` to generate autoloader
3. Activate in WordPress admin

## Usage

Add the booking form to any page using the shortcode:
```
[hdw_booking_form]
```

Access the admin dashboard at:
**Admin Menu > Meeting Bookings**

## Git Commit Guide (Phase-by-Phase)

### Phase 1: Plugin Bootstrap & Database
```bash
git init
git add composer.json hdw-meeting-booking.php src/Activator.php src/Deactivator.php src/Database/Database.php

git commit -m "feat: initialize plugin with PSR-4 autoloading and custom database tables

- Add composer.json with PSR-4 autoloading (HDW\\MeetingBooking namespace)
- Create main plugin file with activation/deactivation hooks
- Implement Activator with dbDelta for hdw_meeting_rooms and hdw_meeting_reservations tables
- Add indexes for optimized queries on date, status, mobile, and room_id
- Setup default 3 meeting rooms on activation

Tables created:
- wp_hdw_meeting_rooms (id, room_name, room_code, is_active, timestamps)
- wp_hdw_meeting_reservations (id, room_id, full_name, mobile, email,
  meeting_title, meeting_date, start_time, end_time, description, status, timestamps)"
```

### Phase 2: Domain Models
```bash
git add src/Models/Room.php src/Models/Reservation.php

git commit -m "feat: add domain models for Room and Reservation

- Room model with id, roomName, roomCode, isActive properties
- Reservation model with full fields and business methods:
  - overlapsWith() for interval overlap detection
  - getStatusLabel() for localized status display
  - getDurationMinutes() for calculating meeting length
- fromArray() factory methods for database row mapping
- STATUS_* constants for pending, approved, rejected states

Single Responsibility: Models contain only data and domain logic,
no database operations or presentation code."
```

### Phase 3: Repository Layer
```bash
git add src/Database/RoomRepository.php src/Database/ReservationRepository.php

git commit -m "feat: implement Repository pattern for data access

- RoomRepository: findById, findAllActive, findAll, create, update,
  softDelete, count, countActive
- ReservationRepository: findById, findWithFilters (search+date+status),
  findByDate, findOverlapping (core conflict query),
  findOverlappingForRoom, create, update, updateStatus, delete

Security:
- All queries use $wpdb->prepare() with parameterized values
- Search uses $wpdb->esc_like() for safe LIKE clauses
- Proper format specifiers for all insert/update operations

findOverlapping uses interval overlap formula:
  (start_time < new_end AND end_time > new_start)"
```

### Phase 4: Services - Room Management
```bash
git add src/Services/RoomManager.php

git commit -m "feat: add RoomManager service for room business logic

- getActiveRooms(), getAllRooms(), getRoom() for retrieval
- createRoom() for individual room creation
- addRooms() / removeRooms() for batch operations
- syncRoomCount() to align database with configured count
  - Adds new rooms when count increases
  - Reactivates inactive rooms when possible
  - Soft-deletes excess rooms when count decreases
- get/set ConfiguredRoomCount via WordPress options API

Extensibility: Uses get_option/update_option for room count,
allowing future migration to dedicated settings API."
```

### Phase 5: Services - Conflict Detection & Room Allocation
```bash
git add src/Services/ConflictDetector.php src/Services/RoomAllocator.php

git commit -m "feat: implement conflict detection and room allocation

ConflictDetector:
- findConflicts(): Get all overlapping reservations for a time range
- findConflictsForRoom(): Get conflicts for a specific room
- isRoomAvailable(): Boolean check with exclude parameter
- getConflictsByRoom(): Group conflicts by room_id
- getOccupiedSlots(): Get all occupied intervals for a date

RoomAllocator:
- findAvailableRoom(): Greedy first-fit algorithm - returns first room
  with no conflicts in the requested time range
- allocateReservation(): Returns room_id or null
- getRoomAvailabilities(): Full availability matrix for all rooms

Pending reservations are treated as occupied (business requirement)."
```

### Phase 6: Services - Interval Partitioning Algorithm
```bash
git add src/Services/IntervalPartitioning.php

git commit -m "feat: implement Interval Partitioning algorithm (Sweep Line)

- calculateMinimumRooms(): O(n log n) algorithm to find max overlap
  1. Build events: start (+1) and end (-1) points
  2. Sort by time (end before start at same time)
  3. Sweep through events tracking current overlap
  4. Maximum overlap = minimum rooms required

- getAllocationReport(): Detailed analysis with:
  - Peak time identification
  - Reservations overlapping at peak
  - Sufficiency comparison with configured rooms

Algorithm correctly handles edge case where one meeting ends
at the same time another starts (no conflict at exact boundary)."
```

### Phase 7: Services - Reservation Orchestration
```bash
git add src/Services/ReservationManager.php

git commit -m "feat: add ReservationManager orchestration service

- createReservation(): Full workflow with validation,
  room allocation, and database insertion
- approveReservation(): Re-checks availability at approval time,
  handles room changes if original room became occupied
- rejectReservation(): Status update with cleanup
- getAvailableTimeSlots(): Generates 30-min slots from 08:00-20:00
  with availability status based on occupied intervals

Flow: Validation -> Room Allocation (greedy) -> Insert (pending)
-> Admin Approval -> Re-verify allocation -> Update status

Extensibility: do_action() hooks at key points:
- hdw_reservation_created, hdw_reservation_approved,
  hdw_reservation_approved_room_changed, hdw_reservation_rejected"
```

### Phase 8: Utilities - Validation & Security
```bash
git add src/Utils/Validator.php src/Utils/Security.php

git commit -m "feat: add validation and security utilities

Validator:
- validateReservationData(): Comprehensive form validation
  - Full name (min 2 chars), mobile (Iranian/international format)
  - Email format, meeting title (min 2 chars)
  - Date format and past-date prevention
  - Time validation with 15-min minimum / 8-hour maximum duration
  - End time must be after start time

Security:
- createNonceField() / verifyNonce() for CSRF protection
- verifyAdminNonce() for admin actions
- requireAdmin() capability check
- escHtml(), escAttr(), escUrl() output escaping
- sendJsonSuccess() / sendJsonError() with security headers"
```

### Phase 9: Admin Dashboard
```bash
git add src/Admin/AdminMenu.php src/Admin/AdminDashboard.php src/Admin/SettingsPage.php

git commit -m "feat: implement admin dashboard with full management

AdminMenu:
- Top-level menu with 3 sub-pages: Reservations, Room Settings, Allocation Report

AdminDashboard:
- Reservation listing with pagination
- Filter bar: search (name/mobile), date picker, status dropdown
- Statistics cards (Total, Pending, Approved)
- Approve/Reject buttons with AJAX (nonce-verified)
- Status badges with color coding
- Re-allocation check on approval

SettingsPage:
- Form to adjust total room count (1-50)
- syncRoomCount() called on save
- List of current active rooms with codes"
```

### Phase 10: Admin Assets
```bash
git add admin/css/admin-style.css admin/js/admin-script.js admin/partials/dashboard.php admin/partials/settings.php admin/partials/report-page.php

git commit -m "feat: add admin UI templates and assets

css/admin-style.css:
- Responsive filter bar, statistics cards, status badges
- Table styling with room badges and action buttons
- Report cards with color-coded sufficiency indicators
- Loading states and animations

js/admin-script.js (ES6, no jQuery):
- Event delegation for approve/reject buttons
- Fetch API AJAX with nonce verification
- Row status update without page reload
- Toast notification system

partials/:
- dashboard.php: Full reservation table with filters
- settings.php: Room count form and active rooms list
- report-page.php: Interval partitioning analysis display"
```

### Phase 11: Public Booking Form
```bash
git add src/Public/BookingForm.php src/Public/AjaxHandler.php

git commit -m "feat: implement public booking form and AJAX handlers

BookingForm:
- [hdw_booking_form] shortcode
- Conditional asset loading (only when shortcode present)
- Localized data for JavaScript (ajaxUrl, nonce, strings)

AjaxHandler (public endpoints, no login required):
- hdw_get_available_slots: Returns 30-min slots with availability
  for the selected date (08:00-20:00 range)
- hdw_submit_reservation: Full validation and creation workflow
  with nonce verification

Security: All outputs escaped, inputs sanitized, nonces verified."
```

### Phase 12: Public Assets
```bash
git add public/css/booking-style.css public/js/booking-script.js public/partials/booking-form.php

git commit -m "feat: add public booking UI with interactive time picker

css/booking-style.css:
- Clean card-based form layout
- Visual time slot grid with availability states
  - Available (green), Occupied (gray/strikethrough)
  - Selected start (blue fill), In-range (light blue)
- Success modal with animation
- Fully responsive (mobile grid: 2 columns)

js/booking-script.js (ES6, no jQuery):
- State management for date/time selection
- Two-click time selection: first click = start, second = end
- Range validation (no occupied slots in range)
- Fetch API for loading slots dynamically
- Client-side form validation before submission
- Success modal on completion

partials/booking-form.php:
- Two-step UI: select date -> load available slots -> select range
- Hidden start_time/end_time updated by slot selection
- Fallback custom time inputs if slots fail to load"
```

### Phase 13: Plugin Orchestration
```bash
git add src/Main.php

git commit -m "feat: add main plugin orchestrator

Main.php:
- run(): Initializes all components in correct order
- initializeAdmin(): AdminMenu, AdminDashboard, SettingsPage
- initializePublic(): BookingForm, AjaxHandler
- registerHooks(): Textdomain loading

Separation of Concerns: Main.php only coordinates,
no business logic. All operations delegated to specialized classes."
```

### Phase 14: Final Polish
```bash
git add README.md

git commit -m "docs: add comprehensive README with architecture docs

- Feature overview and technical stack
- Complete folder structure diagram
- Installation instructions
- Shortcode usage guide
- Full git commit history with phase-by-phase breakdown
- Algorithm documentation for Interval Partitioning
- Security measures documentation"
```

## Security Measures

1. **CSRF Protection**: Nonce verification on all forms and AJAX requests
2. **SQL Injection Prevention**: All database queries use `$wpdb->prepare()`
3. **XSS Prevention**: All output uses `esc_html()`, `esc_attr()`, `esc_url()`
4. **Capability Checks**: Admin functions require `manage_options`
5. **Input Sanitization**: `sanitize_text_field`, `sanitize_email`, `sanitize_textarea_field`
6. **Validation**: Server-side validation for all user inputs

## Algorithms

### Conflict Detection (Interval Overlap)
Two intervals [s1, e1) and [s2, e2) overlap if:
```
s1 < e2 AND e1 > s2
```

### Room Allocation (Greedy First-Fit)
For each room in order, check availability. Assign to first available room.

### Minimum Rooms (Interval Partitioning / Sweep Line)
1. Create events: start (+1), end (-1) for each reservation
2. Sort events by time (end before start at same timestamp)
3. Track running count of active reservations
4. Maximum count = minimum rooms needed

**Time Complexity**: O(n log n)  
**Space Complexity**: O(n)

## Extensibility Hooks

```php
// After reservation is created
do_action('hdw_reservation_created', $reservationId, $roomId);

// After reservation is approved
do_action('hdw_reservation_approved', $reservationId, $roomId);

// After approval with room change
do_action('hdw_reservation_approved_room_changed', $reservationId, $newRoomId);

// After reservation is rejected
do_action('hdw_reservation_rejected', $reservationId);
```

## License

GPL-2.0+
