# STACK

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
