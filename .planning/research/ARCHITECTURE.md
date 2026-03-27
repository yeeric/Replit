# Architecture Research — Brownfield Hardening

## Current Pattern

- Front controller (`php/index.php`) dispatches to page handlers
- Page handlers contain request parsing, SQL, and HTML rendering
- Shared layout in `php/layout.php`; DB singleton in `php/db.php`

## Target Incremental Structure

### Layering (lightweight)
- Routing layer: keep existing route map
- Handler layer: keep per-page handlers, reduce mixed concerns
- Domain/query helpers: extract reusable DB operations where repeated
- Presentation layer: keep server-rendered templates/heredocs initially

### Data Flow
1. Request enters route handler
2. Input validated and normalized
3. SQL operation executes with prepared statements and transaction where needed
4. Response returns full page or HTMX fragment
5. Errors return safe, user-oriented messages + internal logs

## Build Order Implications

1. Hardening foundations (migration reliability + debug gating)
2. Security baseline (CSRF + safer error contracts)
3. Test infrastructure and high-risk path coverage
4. Developer/runtime consistency cleanup

## Integration Boundaries

- DB boundary: all writes should have explicit failure behavior
- HTTP boundary: mutating routes should enforce CSRF and validation
- Ops boundary: startup should fail clearly on unrecoverable migration errors

## Risk Concentration

- `php/migrate.php` (schema/seed correctness)
- mutating handlers in `php/pages/attendees.php`, `schedule.php`, `sponsors.php`
- inconsistent runtime configuration between `.replit` and startup scripts
