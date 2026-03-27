# CONCERNS

## 1) Migration Type Mismatch Risk (High)
- `php/pages/sponsors.php` creates `sponsorlevel` values as plain text (`Platinum|Gold|Silver|Bronze`).
- `php/migrate.php` seed block casts to `::sponsor_level` for inserts.
- No `CREATE TYPE sponsor_level AS ENUM ...` appears in migration setup.
- Likely outcome: seed sponsor insert may fail, with failure hidden by `safeExec()` logging.
- Evidence: `php/migrate.php` sponsor seed section around lines ~280-300.

## 2) Startup Robustness Depends on Non-Fatal Migration Errors (High)
- `start.sh` always runs migration before serve.
- `safeExec()` intentionally continues after SQL errors.
- App may boot with partially applied seed/state and no hard failure signal.
- This can create non-deterministic demo data and hidden runtime issues.

## 3) CSRF Protection Not Implemented on Mutating Endpoints (Medium)
- POST/DELETE handlers accept state changes without CSRF tokens:
- `/attendees/add` (`php/pages/attendees.php`)
- `/schedule/save` (`php/pages/schedule.php`)
- `/sponsors/company` create/delete (`php/pages/sponsors.php`)
- In production deployments, this is a typical web security gap.

## 4) Debug Endpoint Exposes Environment and DB State (Medium)
- `/debug-status` returns PHP version, env vars, and DB accessibility (`php/index.php`).
- Comment labels this as production surface.
- Should be gated or disabled outside local development.

## 5) Tooling Drift / Dead Build Pipeline (Medium)
- `.replit` run command uses `npm run dev`, but app runtime is PHP via `start.sh`.
- `script/build.ts` expects Node ecosystem + `package.json`, which is absent.
- Signals stale scaffolding and potential confusion for contributors.

## 6) Error Message Exposure to UI (Low-Medium)
- Attendee insert exception path outputs raw error text to client HTML (`php/pages/attendees.php`).
- Could leak DB/internal details to end users.

## 7) Test Coverage Gap (Low-Medium)
- No automated tests for migration correctness, endpoint behavior, or UI flows.
- Manual QA is currently the only verification path.

## Recommended Priorities
1. Fix `sponsor_level` type mismatch in migration/seed logic.
2. Decide migration failure policy (fail-fast vs. warn-and-continue).
3. Add CSRF defenses for mutating routes.
4. Restrict/remove `/debug-status` in deployed environments.
5. Reconcile or remove stale Node build references.
