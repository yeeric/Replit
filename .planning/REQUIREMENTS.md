# Requirements: CISC 332 Conference Management Dashboard

**Defined:** 2026-03-26
**Core Value:** Conference organizers can reliably manage core conference data and workflows from one lightweight web app without breaking data integrity.

## v1 Requirements

### Migration & Data Integrity

- [ ] **DATA-01**: App startup fails clearly if critical schema migration steps fail
- [ ] **DATA-02**: Sponsor seed logic uses a valid, consistent `sponsorlevel` type path
- [ ] **DATA-03**: Migration output distinguishes fatal errors from non-fatal informational messages
- [ ] **DATA-04**: Seed process is idempotent across repeated startup runs

### Security & Safety

- [ ] **SECU-01**: Mutating routes require CSRF verification for form submissions
- [ ] **SECU-02**: Server returns user-safe error messages without exposing raw exception internals
- [ ] **SECU-03**: Debug endpoint is disabled or gated in non-development environments
- [ ] **SECU-04**: Input validation is explicit for write handlers that mutate attendee, sponsor, and schedule data

### Testing & Verification

- [ ] **TEST-01**: Project includes automated test harness runnable from repository root
- [ ] **TEST-02**: Migration behavior has automated coverage for schema creation and seed idempotency
- [ ] **TEST-03**: High-risk write flows have regression tests (attendee create, schedule edit, sponsor company CRUD)
- [ ] **TEST-04**: Route-level smoke tests verify key pages respond successfully

### Runtime Consistency

- [ ] **RUNT-01**: Canonical run/deploy commands are aligned across `.replit`, scripts, and docs
- [ ] **RUNT-02**: Dead or misleading build references are removed or clearly marked non-canonical
- [ ] **RUNT-03**: Contributor docs include a single authoritative local run workflow

## v2 Requirements

### Product Expansion

- **PROD-01**: Add authentication/authorization for admin actions
- **PROD-02**: Add richer analytics and operational reporting views
- **PROD-03**: Add advanced schedule planning features (conflict checks, richer editing UX)

## Out of Scope

| Feature | Reason |
|---------|--------|
| Full SPA rewrite | High churn and not needed to address immediate reliability/security gaps |
| Multi-tenant architecture | Not required for course-scoped deployment model |
| Major new domain modules | Current priority is hardening existing workflows |

## Traceability

| Requirement | Phase | Status |
|-------------|-------|--------|
| DATA-01 | TBD | Pending |
| DATA-02 | TBD | Pending |
| DATA-03 | TBD | Pending |
| DATA-04 | TBD | Pending |
| SECU-01 | TBD | Pending |
| SECU-02 | TBD | Pending |
| SECU-03 | TBD | Pending |
| SECU-04 | TBD | Pending |
| TEST-01 | TBD | Pending |
| TEST-02 | TBD | Pending |
| TEST-03 | TBD | Pending |
| TEST-04 | TBD | Pending |
| RUNT-01 | TBD | Pending |
| RUNT-02 | TBD | Pending |
| RUNT-03 | TBD | Pending |

**Coverage:**
- v1 requirements: 15 total
- Mapped to phases: 0
- Unmapped: 15 ⚠️

---
*Requirements defined: 2026-03-26*
*Last updated: 2026-03-26 after initial definition*
