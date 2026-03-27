# Phase 1: Migration Reliability Baseline - Context

**Gathered:** 2026-03-27
**Status:** Ready for planning

<domain>
## Phase Boundary

This phase hardens startup migration/seed behavior so schema and seed operations are deterministic, observable, and fail safely when critical operations fail. It does not add new product capabilities.

</domain>

<decisions>
## Implementation Decisions

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

### the agent's Discretion
- Naming conventions for migration step IDs and summary field names, as long as they remain consistent and machine-readable.
- Exact test harness tool choice for CLI integration checks, as long as commands are repeatable from repo root.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Phase and requirement definitions
- `.planning/ROADMAP.md` — Phase 1 goal, requirement mapping, and success criteria.
- `.planning/REQUIREMENTS.md` — DATA-01 through DATA-04 acceptance targets.
- `.planning/PROJECT.md` — milestone constraints and reliability-first scope.

### Existing code and risk baselines
- `.planning/codebase/CONCERNS.md` — known migration/type mismatch and startup failure-mode risks.
- `.planning/codebase/STRUCTURE.md` — runtime entrypoints and startup file layout.
- `.planning/codebase/CONVENTIONS.md` — existing migration/error-handling conventions.
- `.planning/codebase/TESTING.md` — current testing gaps and candidate verification surfaces.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `php/migrate.php`: Existing migration + seed orchestration that can be upgraded to explicit step classification and structured logging.
- `start.sh`: Canonical startup path where migrate-then-serve sequencing is enforced.

### Established Patterns
- Migration currently uses a `safeExec()` continue-on-error pattern; this phase replaces that pattern for critical paths with explicit fail-fast handling.
- Runtime is Replit-centric; startup command flow should remain compatible with current environment.

### Integration Points
- Migration behavior changes center on `php/migrate.php` and startup sequencing in `start.sh`.
- Verification hooks should be runnable from repository root and validate migration/startup command outcomes.

</code_context>

<specifics>
## Specific Ideas

- Fatal output should be structured and concise: step id/name, reason, and non-zero exit status.
- Warning and fatal outcomes should be clearly separated in logs and final summary counts.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 01-migration-reliability-baseline*
*Context gathered: 2026-03-27*
