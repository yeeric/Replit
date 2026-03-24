# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

**Start the app (port 5000 is taken by macOS ControlCenter locally — use 8080):**
```bash
php php/migrate.php          # run schema + seed once
php -S 0.0.0.0:8080 php/index.php  # start server
```

**On Replit (uses port 5000):**
```bash
bash start.sh
```

**Check the app is running:**
```bash
curl http://localhost:8080/debug-status
```

**Run migration manually (idempotent — safe to re-run):**
```bash
php php/migrate.php
```

There are no tests, no build step, and no linter configured.

## Architecture

### Request Flow

`php/index.php` is both the PHP built-in server entry point and the URL router. It parses `$_SERVER['REQUEST_URI']`, matches path prefixes, and `require`s the appropriate page handler from `php/pages/`. There is no framework.

Each page handler is self-contained: it handles **all HTTP methods for its route** (full-page GET, HTMX partial GET, POST, DELETE) in a single file, then falls through to render the full page layout via `renderLayout()` from `layout.php`.

### HTMX Pattern

Pages return either:
- **Full HTML** (initial page load) — wrapped in `renderLayout(title, activeRoute, $content)`
- **HTML fragment** (HTMX request) — a bare string of table rows, cards, or a form, returned with `echo` + `exit`

HTMX partials are triggered by `hx-get`/`hx-post`/`hx-delete` attributes on dropdowns, buttons, and forms. The request path or a query parameter distinguishes partial from full-page requests (e.g. `?id=X`, `?type=X`, `?date=X`).

### Database

- `php/db.php` — PDO singleton. Reads `DATABASE_URL` first, falls back to `PGHOST`/`PGPORT`/`PGDATABASE`/`PGUSER`/`PGPASSWORD` env vars.
- `php/migrate.php` — creates all tables (`IF NOT EXISTS`) and seeds data only when `attendee` is empty. Safe to run on every startup.
- Schema uses ISA inheritance: `attendee` is the base table; `student`, `professional`, `sponsor` each hold a FK back to `attendee.attendeeid` with `ON DELETE CASCADE`.
- `sponsor.sponsorlevel` is a PostgreSQL enum type `sponsor_level` — always cast string literals with `::sponsor_level` in raw SQL.
- All queries use PDO prepared statements (`$db->prepare(...)->execute([...])`).

### UI / Styling

- Tailwind CSS via CDN — no build step.
- Salesforce Lightning Design System color palette defined as CSS variables in `layout.php:pageHead()`. Use `sf-*` class names (e.g. `text-sf-text`, `bg-sf-bg`, `border-sf-border`).
- Modals use the native `<dialog>` element: `document.getElementById('modal-id').showModal()` / `.close()`.

## Key Files

| File | Purpose |
|------|---------|
| `php/index.php` | Router — add new routes here |
| `php/db.php` | DB connection — do not instantiate PDO directly |
| `php/layout.php` | `renderLayout()`, `pageHead()`, `sidebar()` — all shared UI |
| `php/migrate.php` | Schema + seed — extend this when adding tables or test data |
| `php/pages/*.php` | One file per page/feature; handles all methods for that route |
| `attached_assets/conference_database_postgres_*.sql` | Standalone SQL file with full schema + data (different from migrate.php — has additional tables: `Speaker`, `SpeaksAt`, `Attends`) |

## Adding a New Page

1. Create `php/pages/mypage.php`
2. Add a route in `php/index.php`: `case str_starts_with($path, '/mypage')`
3. Add a sidebar entry in `php/layout.php:sidebar()`
