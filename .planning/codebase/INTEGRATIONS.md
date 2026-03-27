# INTEGRATIONS

## Database Integration
- Integrated system: PostgreSQL
- Driver/protocol: PDO `pgsql` DSN (`php/db.php`)
- Connection strategy:
- `DATABASE_URL` parsing path (`php/db.php`)
- Fallback to `PGHOST`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGPASSWORD` (`php/db.php`)

## Deployment Platform Integration
- Platform: Replit (`.replit`)
- Provisioned modules: `web`, `postgresql-16` (`.replit`)
- Deployment hooks:
- Build: no-op shell echo (`.replit`)
- Run: `bash start.sh` (`.replit`)

## Frontend CDN Dependencies
- HTMX CDN: `https://unpkg.com/htmx.org@2.0.4` (`php/layout.php`)
- Tailwind CDN: `https://cdn.tailwindcss.com` (`php/layout.php`)
- Impact: runtime depends on external CDN availability/network policy

## HTTP-Level App Integrations (Internal)
- HTMX request detection via `HTTP_HX_REQUEST` header (`php/pages/committees.php`, `php/pages/hotels.php`, `php/pages/schedule.php`, `php/pages/attendees.php`, `php/pages/jobs.php`)
- HTMX response triggers used for modal close flows (`php/pages/schedule.php`, `php/pages/attendees.php`)

## Data Model Integrations
- App features integrate across relational entities:
- Sponsors ↔ Attendees ↔ Company (`php/pages/sponsors.php`, `php/pages/attendees.php`)
- Students ↔ Hotel rooms (`php/pages/hotels.php`, `php/migrate.php`)
- Job ads ↔ Company (`php/pages/jobs.php`, `php/migrate.php`)
- Committee membership join tables (`php/pages/committees.php`, `php/migrate.php`)

## Migrations and Seeding Integration
- Migration executes at startup (`start.sh` calling `php/migrate.php`)
- Safe execution wrapper logs and continues (`php/migrate.php`)
- Seed path checks attendee row count before inserting baseline data (`php/migrate.php`)

## Explicitly Absent External Integrations
- No payment gateway integration found in repository source
- No auth provider/OAuth integration found
- No outbound REST/GraphQL client usage found in active PHP code
- No webhook receiver endpoints found

## Key Files
- `php/db.php`
- `php/migrate.php`
- `php/pages/*.php`
- `.replit`
- `start.sh`
