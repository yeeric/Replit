# TESTING

## Current Testing State
- No automated test suite directory found in repository root scan.
- No PHPUnit configuration (`phpunit.xml`) present.
- No Node test runner config (`jest`, `vitest`, `playwright`) present.
- No CI workflow files detected in `.github/workflows/` (directory absent).

## Existing Verification Mechanisms
- Manual runtime validation through rendered pages and HTMX interactions.
- Startup migration logs progress/errors to stdout (`php/migrate.php`).
- Optional debug endpoint `/debug-status` surfaces environment + DB connectivity (`php/index.php`).

## High-Risk Paths Lacking Tests
- Multi-table attendee insert transaction (`php/pages/attendees.php`).
- Schedule edit mutation endpoint (`php/pages/schedule.php`).
- Company add/delete endpoints (`php/pages/sponsors.php`).
- Migration seed correctness and idempotency (`php/migrate.php`).

## Data Integrity Testing Gaps
- No assertions around trigger-generated fee values in `attendee`.
- No checks for sponsor subtype constraints and seeded enum-like values.
- No fixture reset/test database lifecycle tooling.

## Suggested Minimal Test Strategy
- Add PHPUnit for server-side endpoint and query behavior tests.
- Add smoke tests for route status + key page content.
- Add database integration tests for migration idempotency and seed invariants.
- Add mutation tests covering validation errors and success responses for HTMX endpoints.

## Manual Regression Checklist (Current Practical Path)
- Run `php php/migrate.php` and confirm no critical migration failures.
- Start app via `bash start.sh` and verify each route loads.
- Validate add attendee, edit schedule, add/delete company, and filter endpoints.
- Verify database rows update as expected after each interaction.

## Key Files for Future Tests
- `php/index.php`
- `php/pages/attendees.php`
- `php/pages/schedule.php`
- `php/pages/sponsors.php`
- `php/migrate.php`
