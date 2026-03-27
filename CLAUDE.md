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

<!-- GSD:project-start source:PROJECT.md -->
## Project

**CISC 332 Conference Management Dashboard**

A server-rendered conference management dashboard for the CISC 332 database course, built with PHP, HTMX, Tailwind, and PostgreSQL. It provides organizers a single interface to view finances, manage attendees, sponsors, committees, rooms, sessions, and jobs. This milestone focuses on hardening the existing app so its current capabilities are reliable and safer to evolve.

**Core Value:** Conference organizers can reliably manage core conference data and workflows from one lightweight web app without breaking data integrity.

### Constraints

- **Tech stack**: PHP 8.2 + PDO + PostgreSQL + HTMX/Tailwind via CDN — preserve current stack to keep scope aligned with existing code
- **Environment**: Replit autoscale runtime and workflows — changes must remain deployable in this environment
- **Scope**: Course project timebox — prioritize high-risk fixes and testability over broad feature expansion
- **Compatibility**: Keep existing routes and user-facing behavior stable while hardening internals
<!-- GSD:project-end -->

<!-- GSD:stack-start source:codebase/STACK.md -->
## Technology Stack

## Runtime and Language
- Primary runtime: PHP 8.2 (`.replit`, `start.sh`)
- Primary language: PHP (`php/index.php`, `php/pages/*.php`, `php/migrate.php`)
- Shell runtime for startup/deploy: Bash (`start.sh`)
- Secondary tooling artifact: TypeScript build helper (`script/build.ts`)
## Web Application Model
- Server-rendered HTML app with PHP-built pages (`php/pages/*.php`)
- Single entry router via PHP built-in server front controller (`php/index.php`)
- HTMX used for partial updates via request headers and `hx-*` attributes (`php/layout.php`, page handlers)
- No frontend SPA framework in active runtime path
## Frontend Libraries
- HTMX 2.0.4 loaded by CDN script tag (`php/layout.php`)
- Tailwind CSS loaded by CDN (`php/layout.php`)
- Tailwind config injected inline in page head (`php/layout.php`)
## Database Stack
- PostgreSQL 16 module configured in Replit (`.replit`)
- Access layer: PDO with `pdo_pgsql` extension (`php/db.php`)
- DB source priority: `DATABASE_URL` first, fallback to `PG*` env vars (`php/db.php`)
## Deployment and Environment
- Replit deployment target: autoscale (`.replit`)
- Deploy command: `bash start.sh` (`.replit`)
- Server bind: `0.0.0.0:5000` (`start.sh`)
- Local/portable run documented in project README (`README.md`)
## Build and Tooling Observations
- `script/build.ts` references Node/Vite/esbuild stack and `package.json`.
- Current repository does not include a `package.json` at root (`find . -maxdepth 4 -type f`).
- `.replit` run command is `npm run dev`, which conflicts with the PHP-first startup path in `start.sh`.
## Key Files
- `.replit`
- `start.sh`
- `php/index.php`
- `php/db.php`
- `php/layout.php`
- `php/migrate.php`
- `script/build.ts`
- `README.md`
<!-- GSD:stack-end -->

<!-- GSD:conventions-start source:CONVENTIONS.md -->
## Conventions

## Coding Style
- Uses strict PHP open tags and procedural file-level handlers (`php/pages/*.php`).
- Comments segment sections with banner separators (e.g., `// ── ... ──`).
- Variables typically short, readable names (`$db`, `$stmt`, `$rows`, `$content`).
- HTML is composed via heredoc strings and manual concatenation.
## Routing and Handler Conventions
- Full-page and HTMX partial logic coexist in each page file.
- Branching based on method + path suffix + `HTTP_HX_REQUEST` header.
- Path detection uses `parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)` consistently.
## Data Access Patterns
- Shared connection via `getDb()` singleton (`php/db.php`).
- Prepared statements are common for writes and filtered reads.
- Some direct queries for unfiltered list pages (`$db->query(...)`).
- Type casts to normalize output for rendering (`fee::text`, `date::text`).
## Error Handling Patterns
- Migration uses `safeExec()` wrapper to log and continue (`php/migrate.php`).
- Attendee create flow wraps multi-table writes in explicit transaction with rollback (`php/pages/attendees.php`).
- Some handlers return raw exception messages to client HTML on failure (`php/pages/attendees.php`).
## UI/Frontend Conventions
- Shared Salesforce-inspired palette defined in inline Tailwind config (`php/layout.php`).
- Tailwind utility classes combined with inline style attributes for hover/brand colors.
- HTMX attributes (`hx-get`, `hx-post`, `hx-delete`, `hx-target`, `hx-swap`) handle interaction flows.
- Inline JavaScript used sparingly for modal and tab-state helpers.
## Security and Output Conventions
- `htmlspecialchars()` used in multiple rendering paths for user/content escaping.
- Not all interpolated output is escaped consistently across every table cell path.
- No CSRF token pattern is implemented for mutating endpoints.
## Project/Tooling Conventions
- Runtime operations assume Replit environment defaults (`.replit`, `start.sh`).
- Migration is expected to be idempotent and safe on repeated startup (`php/migrate.php`).
- Documentation in `README.md` and `replit.md` is aligned to PHP + HTMX approach.
<!-- GSD:conventions-end -->

<!-- GSD:architecture-start source:ARCHITECTURE.md -->
## Architecture

## High-Level Pattern
- Monolithic server-rendered PHP web app
- Front controller + route dispatch architecture (`php/index.php`)
- Page-per-feature handlers in `php/pages/`
- Shared layout helpers for page shell and navigation (`php/layout.php`)
## Request Flow
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
<!-- GSD:architecture-end -->

<!-- GSD:workflow-start source:GSD defaults -->
## GSD Workflow Enforcement

Before using Edit, Write, or other file-changing tools, start work through a GSD command so planning artifacts and execution context stay in sync.

Use these entry points:
- `/gsd:quick` for small fixes, doc updates, and ad-hoc tasks
- `/gsd:debug` for investigation and bug fixing
- `/gsd:execute-phase` for planned phase work

Do not make direct repo edits outside a GSD workflow unless the user explicitly asks to bypass it.
<!-- GSD:workflow-end -->

<!-- GSD:profile-start -->
## Developer Profile

> Profile not yet configured. Run `/gsd:profile-user` to generate your developer profile.
> This section is managed by `generate-claude-profile` -- do not edit manually.
<!-- GSD:profile-end -->
