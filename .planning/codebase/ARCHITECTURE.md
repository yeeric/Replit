# ARCHITECTURE

## High-Level Pattern
- Monolithic server-rendered PHP web app
- Front controller + route dispatch architecture (`php/index.php`)
- Page-per-feature handlers in `php/pages/`
- Shared layout helpers for page shell and navigation (`php/layout.php`)

## Request Flow
1. Request enters built-in PHP server configured with router script (`start.sh`, `php/index.php`).
2. `php/index.php` normalizes path and matches static route map.
3. Matched page file executes and handles both full-page and HTMX partial paths.
4. Data access occurs directly in page handler through `getDb()` (`php/db.php`).
5. Response is either full HTML via `renderLayout()` or fragment HTML for HTMX target swaps.

## Routing Design
- Explicit static route map in associative array (`php/index.php`).
- Prefix match allows nested route actions like `/attendees/add` and `/schedule/save`.
- Single 404 fallback inline response for unknown routes.

## Data Access Architecture
- Minimal data layer: single `getDb(): PDO` function with static singleton (`php/db.php`).
- SQL is embedded directly in feature files; no ORM/repository abstraction.
- Prepared statements are used for most parameterized operations.
- Transaction boundary appears in attendee creation workflow (`php/pages/attendees.php`).

## UI Architecture
- Consistent shell from `renderLayout()` wrapping feature content (`php/layout.php`).
- Tailwind utility classes and inline styles build Salesforce-like system.
- HTMX mediates incremental updates without SPA state management.
- Modal/dialog interactions coordinated with HX triggers and inline JS.

## Schema and Domain Architecture
- Relational model centered on `attendee` with ISA-style subtype tables:
- `student`, `professional`, `sponsor` extend `attendee` (`php/migrate.php`).
- Supporting entities: `company`, `jobad`, `session`, `subcommittee`, `committeemember`, `memberofcommittee`, `hotelroom`.
- Trigger function auto-assigns attendee fee based on type (`php/migrate.php`).

## Startup Architecture
- Runtime bootstrap script: `start.sh`.
- Startup side effect: migration + seed runs on every startup (`php/migrate.php`).
- App then starts PHP development server bound to port 5000.

## Key Files
- `php/index.php`
- `php/layout.php`
- `php/db.php`
- `php/pages/*.php`
- `php/migrate.php`
- `start.sh`
