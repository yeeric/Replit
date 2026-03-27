# Phase 1: Migration Reliability Baseline - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-03-27
**Phase:** 1-Migration Reliability Baseline
**Areas discussed:** Failure policy, Seed/type strategy, Migration observability, Startup execution model, Verification scope

---

## Failure policy

| Option | Description | Selected |
|--------|-------------|----------|
| Fail-fast on critical schema/seed errors | Startup exits non-zero when critical migration/seed steps fail; only explicitly non-critical steps can warn and continue. | ✓ |
| Warn-and-continue default | Keep app booting despite migration errors unless a very small set of checks fail. | |
| Two-stage with strict mode toggle | Support both modes via env/config, with strict mode intended for deploy paths. | |

**User's choice:** Fail-fast on critical schema/seed errors
**Notes:** User also chose step-level explicit critical classification and structured fatal summary with first-error context.

---

## Seed/type strategy

| Option | Description | Selected |
|--------|-------------|----------|
| Canonical DB enum type + aligned inserts | Create/ensure enum type exists, use it consistently in schema + seed inserts, remove ambiguous casts. | ✓ |
| Text-only strategy | Drop enum behavior and store sponsor levels as plain text with check constraints. | |
| Lookup-table strategy | Normalize sponsor levels into a reference table with FK usage. | |

**User's choice:** Canonical DB enum type + aligned inserts
**Notes:** For idempotency policy, user selected delete-and-reseed with full table reset for related tables.

---

## Migration observability

| Option | Description | Selected |
|--------|-------------|----------|
| Structured step logs + final summary | Each step logs status/step/action; final block reports totals + fatal/non-fatal counts. | ✓ |
| Human-readable plain text only | Simple narrative lines without consistent fields. | |
| JSON logs | Machine-parseable JSON events for each step. | |

**User's choice:** Structured step logs + final summary
**Notes:** User chose explicit WARN classification for non-fatal outcomes and fatal logs with step id + concise reason + exit code.

---

## Startup execution model

| Option | Description | Selected |
|--------|-------------|----------|
| Always run on startup, strict-fail enabled | Keep current startup flow but make it deterministic and fail-fast for critical steps. | ✓ |
| Migrate only via explicit command | Startup serves app only; migration is a separate operator step. | |
| Environment-gated | Auto-run in dev, explicit command in production-like environments. | |

**User's choice:** Always run on startup, strict-fail enabled
**Notes:** User selected synchronous migrate-then-serve sequencing and no bypass path in Phase 1.

---

## Verification scope

| Option | Description | Selected |
|--------|-------------|----------|
| Automated repeat-run + fatal-path checks | Proof of deterministic reruns and explicit non-zero failure behavior for a simulated critical step. | ✓ |
| Manual smoke checks only | Manual verification without automated repeat-run and failure-path checks. | |
| Log review only | Validate based only on human inspection of logs. | |

**User's choice:** Automated repeat-run + fatal-path checks
**Notes:** User selected CLI-oriented integration checks against migration/startup scripts as the first verification style.

---

## the agent's Discretion

- Naming and schema for structured log fields, as long as they remain stable and clear.
- Test harness implementation details for command-level verification.

## Deferred Ideas

None.
