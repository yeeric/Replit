# Roadmap: CISC 332 Conference Management Dashboard

**Created:** 2026-03-26
**Mode:** Brownfield hardening milestone
**Source requirements:** `.planning/REQUIREMENTS.md`

## Summary

- Phases: 4
- v1 requirements: 15
- Requirements mapped: 15
- Coverage: 100% ✓

## Phases

| # | Phase | Goal | Requirements | Success Criteria |
|---|-------|------|--------------|------------------|
| 1 | Migration Reliability Baseline | Make startup and migration behavior deterministic and safe | DATA-01, DATA-02, DATA-03, DATA-04 | 4 |
| 2 | Security Hardening for Mutations | Add baseline request and error safety on write paths | SECU-01, SECU-02, SECU-03, SECU-04 | 4 |
| 3 | Regression Test Foundation | Add repeatable automated checks for high-risk flows | TEST-01, TEST-02, TEST-03, TEST-04 | 4 |
| 4 | Runtime & Docs Consistency | Eliminate run/build drift across scripts and docs | RUNT-01, RUNT-02, RUNT-03 | 3 |

## Phase Details

### Phase 1: Migration Reliability Baseline

Goal: Ensure schema and seed behavior is deterministic, observable, and fails safely when critical operations fail.

Requirements: DATA-01, DATA-02, DATA-03, DATA-04

**Plans:** 2 plans

Plans:
- [x] 01-01-PLAN.md — Build Phase 1 CLI validation harness (fatal-path, enum-path, log classification, repeat-run idempotency)
- [ ] 01-02-PLAN.md — Harden migration/startup behavior for fail-fast safety, enum correctness, and deterministic reseeding

**UI hint**: no

Success criteria:
1. Critical migration failures produce a non-zero startup failure path.
2. Sponsor-level seed/type path is valid and no longer error-prone.
3. Migration logs clearly separate fatal and non-fatal outcomes.
4. Repeated migration runs preserve consistent database state.

### Phase 2: Security Hardening for Mutations

Goal: Protect state-changing routes and reduce production information leakage.

Requirements: SECU-01, SECU-02, SECU-03, SECU-04

**UI hint**: no

Success criteria:
1. Mutating endpoints enforce CSRF validation.
2. User-facing errors no longer expose raw exception internals.
3. Debug endpoint is unavailable or restricted outside development.
4. Write handlers use explicit input validation for key fields.

### Phase 3: Regression Test Foundation

Goal: Establish an automated test baseline that protects migration and critical mutation flows.

Requirements: TEST-01, TEST-02, TEST-03, TEST-04

**UI hint**: no

Success criteria:
1. Test harness is runnable from repository root with documented command(s).
2. Migration behavior has automated coverage for schema/seed invariants.
3. Tests exist for attendee create, schedule edit, and sponsor company mutations.
4. Route smoke tests confirm key pages return successful responses.

### Phase 4: Runtime & Docs Consistency

Goal: Align runtime commands, scripts, and docs so contributors follow one canonical flow.

Requirements: RUNT-01, RUNT-02, RUNT-03

**UI hint**: no

Success criteria:
1. Canonical run/deploy commands are consistent across `.replit`, scripts, and README.
2. Stale/non-canonical build references are removed or explicitly scoped.
3. Contributor setup and run workflow is clear and conflict-free.

---
*Last updated: 2026-03-26 after roadmap creation*
