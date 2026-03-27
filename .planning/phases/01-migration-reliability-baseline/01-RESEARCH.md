# Phase 1: Migration Reliability Baseline - Research

**Researched:** 2026-03-27
**Domain:** PHP + PostgreSQL migration orchestration reliability
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
### Failure policy
- **D-01:** Use fail-fast behavior for critical schema/seed errors; startup must exit non-zero on critical failure.
- **D-02:** Classify migration/seed steps explicitly in code as critical vs non-critical (step-level classification).
- **D-03:** On critical failure, emit a structured fatal summary with failed step context and non-zero exit.

### Seed/type strategy
- **D-04:** Use a canonical database enum type for sponsor levels and align schema + inserts to that type path.
- **D-05:** Seed strategy is delete-and-reseed.
- **D-06:** Delete-and-reseed scope is full table reset for all related tables.

### Migration observability
- **D-07:** Emit structured step logs with a final summary block.
- **D-08:** Represent non-fatal outcomes as explicit `WARN` entries and track warning counts separately.
- **D-09:** Fatal logs must include step identifier, concise reason, and exit code.

### Startup execution model
- **D-10:** Run migrations on every startup with strict fail behavior enabled.
- **D-11:** Startup sequence is synchronous: migrate first, then serve only on successful migration exit.
- **D-12:** No bypass path in Phase 1.

### Verification scope
- **D-13:** Minimum completion evidence requires automated repeat-run checks plus fatal-path checks.
- **D-14:** Prioritize CLI-oriented integration checks against migration/startup scripts.

### Claude's Discretion
- Naming conventions for migration step IDs and summary field names, as long as they remain consistent and machine-readable.
- Exact test harness tool choice for CLI integration checks, as long as commands are repeatable from repo root.

### Deferred Ideas (OUT OF SCOPE)
None — discussion stayed within phase scope.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| DATA-01 | App startup fails clearly if critical schema migration steps fail | Fail-fast migration contract + startup gate (`start.sh` must stop on migrate exit != 0) |
| DATA-02 | Sponsor seed logic uses a valid, consistent `sponsorlevel` type path | Canonical enum type creation and sponsor column type alignment strategy |
| DATA-03 | Migration output distinguishes fatal errors from non-fatal informational messages | Structured step logs with severity + terminal summary schema |
| DATA-04 | Seed process is idempotent across repeated startup runs | Delete-and-reseed in transaction + deterministic reset strategy |
</phase_requirements>

## Summary

Current code already exposes the main failure mode this phase must fix: migration step errors are intentionally swallowed by `safeExec()` and startup still proceeds (`php/migrate.php:11-18`, `start.sh:4-7`). This violates deterministic startup safety and makes broken schema/seed states harder to detect. The most reliable plan is to move to a step registry with explicit criticality metadata and one strict process contract: any critical step failure returns non-zero and blocks server startup.

DATA-02 is currently brittle: `sponsor.sponsorlevel` is defined as `TEXT` with a check, but seed inserts cast to `::sponsor_level` (`php/migrate.php:107`, `php/migrate.php:285-290`) even though no enum type creation exists. Use a canonical enum-first path: ensure enum exists, define column as enum, and keep inserts typed to that enum. This removes implicit mismatch risk and aligns with locked decision D-04.

For DATA-04, delete-and-reseed is locked. To keep repeated runs deterministic, reseed should be one transactional unit with full-table reset and identity reset semantics; then reinsert canonical fixtures in dependency order. Logs should provide both per-step lines and a final machine-readable summary with separate fatal and warning counts.

**Primary recommendation:** Implement a migration runner with explicit critical step metadata, transactional delete-and-reseed, and startup gating on migration exit code before serving.

## Project Constraints (from CLAUDE.md)

- Preserve stack: PHP + PDO + PostgreSQL + HTMX/Tailwind; no framework migration.
- Runtime compatibility must remain Replit-friendly (`bash start.sh` deploy path).
- Use shared DB access via `getDb()`; do not instantiate ad-hoc PDO connections in app paths.
- Keep existing route behavior stable; this phase should harden internals, not add product features.
- No existing automated tests or lint/build pipeline can be assumed.
- GSD workflow note in `CLAUDE.md`: edits are expected through GSD commands (this research file is generated in GSD phase flow).

## Standard Stack

### Core
| Library/Tool | Version | Purpose | Why Standard |
|--------------|---------|---------|--------------|
| PHP CLI | 8.5.3 local (`php --version`), target project baseline PHP 8.2+ | Runs migration/startup scripts | Existing runtime and deployment target already depend on it |
| PDO + `pdo_pgsql` | bundled with PHP runtime | DB execution with exceptions | Already configured with `PDO::ERRMODE_EXCEPTION` in `php/db.php` |
| PostgreSQL | Replit module `postgresql-16` | Schema, enum types, seed storage | Existing schema and app behavior are PostgreSQL-specific |
| Bash | 5.2.37 local | Startup orchestration (`start.sh`) | Canonical startup entrypoint in project and Replit deploy |

### Supporting
| Library/Tool | Version | Purpose | When to Use |
|--------------|---------|---------|-------------|
| `curl` | 8.7.1 local | CLI smoke checks against startup status path | Fatal-path and repeated-run verification scripts |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Enum-backed `sponsorlevel` | `TEXT CHECK (...)` only | Easier initial DDL but weaker type guarantees and mismatch risk with typed inserts |
| Step metadata + centralized runner | Ad-hoc `safeExec` calls | Less structure, but poor fatal/non-fatal control and observability |

**Installation:**
```bash
# No new package install required for Phase 1 baseline implementation.
```

**Version verification:** N/A for package manager dependencies in this phase (runtime tools already present in repo environment).

## Architecture Patterns

### Recommended Project Structure
```text
php/
├── migrate.php        # Step registry + runner + structured summary + exit code
├── db.php             # Existing PDO bootstrap (reuse)
└── ...                # Existing app files unchanged for this phase
start.sh               # Gate server startup on migration success
tests/phase1/          # CLI verification scripts (Wave 0 additions)
```

### Pattern 1: Step Registry with Criticality
**What:** Represent migration/seed operations as ordered step objects with stable `step_id`, `critical` flag, SQL/action callback, and log label.
**When to use:** Any startup migration flow with mixed fatal and warning semantics.
**Example:**
```php
// Source: local pattern adaptation from php/migrate.php + locked decisions D-01/D-02
$steps = [
  ['id' => 'schema.hotelroom', 'critical' => true,  'sql' => 'CREATE TABLE IF NOT EXISTS hotelroom (...)'],
  ['id' => 'seed.optional_demo', 'critical' => false, 'sql' => 'INSERT ...'],
];
```

### Pattern 2: Transactional Delete-and-Reseed
**What:** Perform full-table reset and reseed in one transaction for deterministic state.
**When to use:** Locked strategy D-05/D-06 for idempotent repeated startup runs.
**Example:**
```sql
-- Source: PostgreSQL TRUNCATE docs + transaction semantics
BEGIN;
TRUNCATE TABLE sponsor, professional, student, attendee, jobad, session, company, memberofcommittee, committeemember, subcommittee, hotelroom RESTART IDENTITY CASCADE;
-- canonical inserts in dependency order
COMMIT;
```

### Pattern 3: Startup Gate on Migration Exit
**What:** Abort serve start when migration exits non-zero.
**When to use:** Every startup run (locked D-10/D-11).
**Example:**
```bash
# Source: existing start.sh flow, hardened for fail-fast
set -euo pipefail
php php/migrate.php
exec php -S 0.0.0.0:5000 php/index.php
```

### Anti-Patterns to Avoid
- **Continue-on-error for critical schema steps:** Causes partial schema and hidden boot risk.
- **Enum casts without ensuring enum type exists:** Creates runtime-dependent seed failures.
- **Seed guard based only on attendee count:** Can skip reseed while other tables are stale.
- **Non-transactional reseed reset:** Leaves inconsistent state if any insert fails mid-seed.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Type safety for sponsor levels | Custom app-side enum validation only | PostgreSQL enum type + typed column | DB enforces correctness at source of truth |
| Idempotent reset semantics | Manual per-table delete scripts with implicit ordering guesses | `TRUNCATE ... RESTART IDENTITY CASCADE` in transaction | Handles FK graph + identity reset deterministically |
| Fatal/non-fatal reporting | Free-form `echo` strings only | Structured step logs + summary object | Machine-readable verification and stable CI parsing |

**Key insight:** PostgreSQL already provides deterministic reset/type primitives; custom partial alternatives add hidden state drift and harder debugging.

## Runtime State Inventory

| Category | Items Found | Action Required |
|----------|-------------|------------------|
| Stored data | PostgreSQL tables seeded by `php/migrate.php` | **Data migration + code edit:** adopt full-table reset/reseed semantics for runtime DB state |
| Live service config | Replit deployment uses `start.sh` as run command | **Code edit:** update startup gating behavior only |
| OS-registered state | None — no local task scheduler/system service definitions in repo | None |
| Secrets/env vars | `DATABASE_URL` or `PG*` env vars drive DB connection | **Code edit:** keep variable names stable; no secret key rename needed |
| Build artifacts | None relevant to migration runtime path | None |

## Common Pitfalls

### Pitfall 1: Hidden Critical Failures
**What goes wrong:** Critical DDL/DML fails but startup still serves.
**Why it happens:** `safeExec()` logs and returns false, but caller ignores result.
**How to avoid:** Centralize step execution and aggregate failure state; exit non-zero on critical failure.
**Warning signs:** Error line appears before `[migrate] Schema ready.` and process still starts server.

### Pitfall 2: Enum Path Drift
**What goes wrong:** Sponsor seed casts to enum type that does not exist or column is mismatched type.
**Why it happens:** Mixed usage of text check and enum casts.
**How to avoid:** Ensure enum type creation step precedes sponsor table definition and use enum column type directly.
**Warning signs:** SQLSTATE errors around `sponsor_level` casts during sponsor seed.

### Pitfall 3: Non-Deterministic Reseed
**What goes wrong:** Repeated runs produce drifted IDs or partial relation state.
**Why it happens:** Partial guards (`COUNT(*)` on one table) and non-transactional insert batches.
**How to avoid:** Full reset + reseed transaction with identity reset and fixed insert order.
**Warning signs:** Re-run counts look stable in one table but subtype tables differ.

## Code Examples

Verified patterns from official sources:

### Create/Use Enum Type Path
```sql
-- Source: PostgreSQL CREATE TYPE docs
CREATE TYPE sponsor_level AS ENUM ('Platinum', 'Gold', 'Silver', 'Bronze');

CREATE TABLE sponsor (
  attendeeid INT PRIMARY KEY REFERENCES attendee(attendeeid) ON DELETE CASCADE,
  sponsorlevel sponsor_level NOT NULL,
  companyid INT NOT NULL REFERENCES company(companyid)
);
```

### Safe Enum Creation Wrapper
```sql
-- Source: PostgreSQL DO docs + CREATE TYPE syntax limitations
DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'sponsor_level') THEN
    CREATE TYPE sponsor_level AS ENUM ('Platinum', 'Gold', 'Silver', 'Bronze');
  END IF;
END$$;
```

### Structured Fatal Exit in PHP
```php
// Source: PHP exit behavior + PDO exception mode docs
if ($criticalFailure) {
  echo json_encode([
    'level' => 'FATAL',
    'step_id' => $failedStepId,
    'reason' => $reason,
    'exit_code' => 1,
  ], JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit(1);
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Ad-hoc migration `echo` and continue | Structured step status + fatal exit policy | Current reliability hardening pattern | Deterministic startup and clear observability |
| App-side text checks for enum-like fields | Database enum type as canonical constraint | Mature PostgreSQL best practice | Eliminates class of mismatch bugs |
| Partial idempotency via `ON CONFLICT` only | Explicit reset + deterministic reseed for fixture sets | Common CI/dev fixture strategy | Repeated startup converges on same dataset |

**Deprecated/outdated:**
- Continue-on-error startup migrations for critical paths: outdated for reliability-sensitive startup flows.

## Open Questions

1. **Should reseed run on every startup unconditionally or only when a known seed fingerprint differs?**
   - What we know: Locked decision mandates delete-and-reseed strategy.
   - What's unclear: Performance expectations for startup frequency in target environments.
   - Recommendation: Phase 1 keep unconditional reseed for determinism; optimize later only if startup latency becomes a measured problem.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| `php` | migration + startup scripts | ✓ | 8.5.3 | — |
| PostgreSQL server reachable from local env | execute migration/seed integration checks | ✗ (not reachable in this shell) | — | run checks in Replit runtime with provisioned DB |
| `psql` CLI | direct SQL assertions in scripts | ✗ | — | use PHP PDO-based assertion scripts |
| `bash` | startup orchestration | ✓ | 5.2.37 | — |
| `curl` | startup endpoint smoke checks | ✓ | 8.7.1 | — |

**Missing dependencies with no fallback:**
- None (can validate DB invariants via PHP/PDO if `psql` unavailable).

**Missing dependencies with fallback:**
- `psql` missing locally; use PHP one-shot scripts for verification queries.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Bash CLI integration scripts + PHP assertion helpers (new in Wave 0) |
| Config file | none — script-driven |
| Quick run command | `bash tests/phase1/quick-migration-check.sh` |
| Full suite command | `bash tests/phase1/full-migration-reliability.sh` |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| DATA-01 | critical migration failure blocks startup with non-zero exit | integration | `bash tests/phase1/fatal-path-check.sh` | ❌ Wave 0 |
| DATA-02 | sponsor level enum/type path is valid end-to-end | integration | `bash tests/phase1/enum-path-check.sh` | ❌ Wave 0 |
| DATA-03 | logs separate fatal vs warn/info with summary | integration | `bash tests/phase1/log-classification-check.sh` | ❌ Wave 0 |
| DATA-04 | repeated runs converge to same DB state | integration | `bash tests/phase1/repeat-run-idempotency.sh` | ❌ Wave 0 |

### Sampling Rate
- **Per task commit:** `bash tests/phase1/quick-migration-check.sh`
- **Per wave merge:** `bash tests/phase1/full-migration-reliability.sh`
- **Phase gate:** Full suite green before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] `tests/phase1/fatal-path-check.sh` — covers DATA-01
- [ ] `tests/phase1/enum-path-check.sh` — covers DATA-02
- [ ] `tests/phase1/log-classification-check.sh` — covers DATA-03
- [ ] `tests/phase1/repeat-run-idempotency.sh` — covers DATA-04
- [ ] `tests/phase1/lib/assertions.sh` — shared exit/log/assert helpers

## Sources

### Primary (HIGH confidence)
- Local code: `php/migrate.php`, `start.sh`, `php/db.php`, `.replit`, `CLAUDE.md`, `.planning/phases/01-migration-reliability-baseline/01-CONTEXT.md`
- PostgreSQL `CREATE TYPE` docs (current): https://www.postgresql.org/docs/current/sql-createtype.html
- PostgreSQL `CREATE TABLE` docs (current): https://www.postgresql.org/docs/current/sql-createtable.html
- PostgreSQL `DO` docs (current): https://www.postgresql.org/docs/current/sql-do.html
- PostgreSQL `ALTER TYPE` docs (current): https://www.postgresql.org/docs/current/sql-altertype.html
- PHP PDO error handling docs (current): https://www.php.net/manual/en/pdo.error-handling.php
- PHP exit docs (current): https://www.php.net/manual/en/function.exit.php

### Secondary (MEDIUM confidence)
- None.

### Tertiary (LOW confidence)
- None.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - derived from repository runtime config and local environment checks
- Architecture: HIGH - based on direct code inspection and locked CONTEXT decisions
- Pitfalls: HIGH - reproduced/observed in current code paths and validated against official docs

**Research date:** 2026-03-27
**Valid until:** 2026-04-26
