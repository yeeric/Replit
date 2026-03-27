# Stack Research — Conference Management Dashboard (2026)

## Recommended Baseline

- Backend runtime: PHP 8.2+ with strict typing incrementally introduced
- HTTP model: server-rendered pages with HTMX for progressive enhancement
- Data layer: PostgreSQL 16+ with migrations and explicit constraints
- Deployment target: Replit-compatible Linux runtime with deterministic startup
- Observability: structured server logs + health endpoint gated by environment

## Keep vs Change

### Keep
- Keep PHP + HTMX architecture for velocity and low operational overhead
- Keep PostgreSQL as single source of truth for transactional data
- Keep page-oriented route handlers while extracting reusable query/service helpers

### Change
- Replace startup-time "best effort" migration behavior with fail-fast policy
- Add CSRF protection and safer error response conventions on write endpoints
- Add automated tests (PHPUnit + lightweight integration tests)
- Align runtime scripts and remove stale Node build assumptions from docs/config

## Supporting Libraries to Add

- `phpunit/phpunit` for automated testing
- `vlucas/phpdotenv` (optional) for local env management consistency
- `symfony/uid` (optional) only if non-sequential external IDs become needed

## What Not to Add (for this milestone)

- Full SPA framework (React/Vue) — not needed for current scope
- ORM migration (Doctrine/Eloquent) — high churn, low immediate value
- Service mesh/microservices — unjustified complexity

## Confidence

- High: Keep PHP + HTMX + PostgreSQL
- High: Add test harness and security hardening
- Medium: Optional dotenv packaging (depends on environment policy)
