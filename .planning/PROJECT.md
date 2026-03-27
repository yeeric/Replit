# CISC 332 Conference Management Dashboard

## What This Is

A server-rendered conference management dashboard for the CISC 332 database course, built with PHP, HTMX, Tailwind, and PostgreSQL. It provides organizers a single interface to view finances, manage attendees, sponsors, committees, rooms, sessions, and jobs. This milestone focuses on hardening the existing app so its current capabilities are reliable and safer to evolve.

## Core Value

Conference organizers can reliably manage core conference data and workflows from one lightweight web app without breaking data integrity.

## Requirements

### Validated

- ✓ Organizer can view finance totals and fee breakdown on a dashboard — existing
- ✓ Organizer can browse committees and their members — existing
- ✓ Organizer can browse hotel rooms and student room assignments — existing
- ✓ Organizer can view and edit conference sessions by date — existing
- ✓ Organizer can manage sponsor companies and view sponsor attendees — existing
- ✓ Organizer can browse job postings and filter by company — existing
- ✓ Organizer can browse attendees by type and register new attendees — existing
- ✓ Migration and seed process is deterministic and free of hidden failures — validated in Phase 1 (Migration Reliability Baseline)

### Active

- [ ] Mutating endpoints include baseline request hardening (CSRF and safer error handling)
- [ ] Core workflows have automated backend/integration tests for regression protection
- [ ] Runtime and tooling configuration is consistent and documented for contributors
- [ ] Debug/diagnostic surfaces are restricted to safe environments

### Out of Scope

- New major feature modules beyond existing conference domains — prioritize reliability first
- Full frontend rewrite to SPA framework — current server-rendered HTMX model is sufficient
- Multi-tenant SaaS and auth/identity redesign — not required for current course scope

## Context

The project is a brownfield PHP monolith with route handlers in `php/pages/*.php`, shared layout/helpers, and SQL-heavy handlers using PDO. A codebase map exists at `.planning/codebase/` and highlights technical risks: migration seed type mismatch around sponsor levels, non-fatal migration errors during startup, no CSRF protection on mutating routes, exposed debug endpoint, and lack of automated tests. Deployment targets Replit autoscale and startup currently runs migrations on every boot.

## Constraints

- **Tech stack**: PHP 8.2 + PDO + PostgreSQL + HTMX/Tailwind via CDN — preserve current stack to keep scope aligned with existing code
- **Environment**: Replit autoscale runtime and workflows — changes must remain deployable in this environment
- **Scope**: Course project timebox — prioritize high-risk fixes and testability over broad feature expansion
- **Compatibility**: Keep existing routes and user-facing behavior stable while hardening internals

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Treat this as brownfield hardening before major feature expansion | Existing app already delivers core workflows; reliability/security gaps are the highest risk | ✓ Confirmed in Phase 1 |
| Keep server-rendered PHP + HTMX architecture for this milestone | Fastest path to improve correctness without architectural churn | ✓ Confirmed in Phase 1 |
| Use phased requirement mapping with explicit traceability | Ensures each improvement is buildable and verifiable | ✓ In use (Phase 1 complete) |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `$gsd-transition`):
1. Requirements invalidated? -> Move to Out of Scope with reason
2. Requirements validated? -> Move to Validated with phase reference
3. New requirements emerged? -> Add to Active
4. Decisions to log? -> Add to Key Decisions
5. "What This Is" still accurate? -> Update if drifted

**After each milestone** (via `$gsd-complete-milestone`):
1. Full review of all sections
2. Core Value check - still the right priority?
3. Audit Out of Scope - reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-03-27 after phase 1 completion*
