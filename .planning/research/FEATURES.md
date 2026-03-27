# Feature Research — Conference Management Domain

## Table Stakes (v1 Reliability Milestone)

### Data Integrity
- Deterministic migration/seed behavior
- Clear startup failure when critical schema operations fail
- Stable subtype handling for attendee role-specific data

### Admin Operations
- Reliable create/update/delete actions for attendees, sponsors, schedules
- Validation errors surfaced without leaking internals
- Idempotent operational scripts

### Security Hygiene
- CSRF protection for mutating routes
- Environment-gated diagnostics
- Safe HTML escaping and consistent input validation

### Testability
- Repeatable automated tests for high-risk routes and SQL flows
- Basic smoke tests for key routes
- Regression checks around migrations and data model rules

## Differentiators (defer unless required)

- Role-based access and authentication
- Rich analytics dashboards beyond current finance snapshot
- Audit logs and activity history UI
- Advanced scheduling (conflict detection, drag/drop planner)

## Anti-Features (explicitly avoid now)

- Full architecture rewrite while critical bugs remain
- New large modules before hardening current workflows
- Premature distributed architecture

## Dependency Notes

- Security hardening and validation should precede large feature additions
- Migration reliability should be resolved before expanding schema complexity
- Test harness should land early to protect subsequent phases
