# STRUCTURE

## Repository Layout
- `php/` — core web app and database scripts
- `php/pages/` — feature pages and HTMX endpoint handlers
- `script/` — legacy/secondary build tooling (`build.ts`)
- `attached_assets/` — project context assets (requirements + SQL reference)
- Root docs/config: `README.md`, `.replit`, `replit.md`, `CLAUDE.md`

## PHP App Structure
- `php/index.php`: front controller and route mapping
- `php/db.php`: database bootstrap and singleton connection
- `php/layout.php`: shared HTML shell, sidebar navigation, theme
- `php/migrate.php`: schema creation, triggers, seed data
- `php/pages/dashboard.php`: finance overview
- `php/pages/committees.php`: committee/member lookup
- `php/pages/hotels.php`: room list + student lookup
- `php/pages/schedule.php`: date tabs + session editing
- `php/pages/sponsors.php`: sponsor list + company CRUD
- `php/pages/jobs.php`: job board + company filter
- `php/pages/attendees.php`: attendee tabbing + attendee creation

## Route-to-File Mapping
- `/` → `php/pages/dashboard.php`
- `/committees` → `php/pages/committees.php`
- `/hotels` → `php/pages/hotels.php`
- `/schedule` → `php/pages/schedule.php`
- `/sponsors` → `php/pages/sponsors.php`
- `/jobs` → `php/pages/jobs.php`
- `/attendees` → `php/pages/attendees.php`
- Sub-actions handled in same files via path suffix checks (e.g., `/attendees/add`, `/schedule/save`).

## Naming and Organization Patterns
- Feature handlers use plural page filenames matching route roots.
- Shared helpers extracted into small top-level files (`db.php`, `layout.php`).
- No `src/`, controller classes, service layer, or test directories present.

## Operational Files
- `start.sh` orchestrates migrate + serve flow.
- `.replit` defines modules, run/deploy commands, and workflow tasks.
- `script/build.ts` indicates leftover Node-oriented pipeline not aligned with current PHP structure.

## Data/Docs Artifacts
- `attached_assets/conference_database_postgres_1773172887457.sql`
- `attached_assets/Conference_Database_Functional_Requirement_1773172935105.md`
- `replit.md` contains architecture summary and schema notes.

## Structural Gaps
- No automated test folder (e.g., `tests/`) detected.
- No environment template file (e.g., `.env.example`) detected.
- No package manager manifest for `script/build.ts` dependencies detected.
