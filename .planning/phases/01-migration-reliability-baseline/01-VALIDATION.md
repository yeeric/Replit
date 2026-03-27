---
phase: 1
slug: migration-reliability-baseline
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-03-27
---

# Phase 1 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Bash CLI integration scripts + PHP assertion helpers |
| **Config file** | none — script-driven |
| **Quick run command** | `bash tests/phase1/quick-migration-check.sh` |
| **Full suite command** | `bash tests/phase1/full-migration-reliability.sh` |
| **Estimated runtime** | ~90 seconds |

---

## Sampling Rate

- **After every task commit:** Run `bash tests/phase1/quick-migration-check.sh`
- **After every plan wave:** Run `bash tests/phase1/full-migration-reliability.sh`
- **Before `$gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 120 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 1-01-01 | 01 | 1 | DATA-01 | integration | `bash tests/phase1/fatal-path-check.sh` | ❌ W0 | ⬜ pending |
| 1-01-02 | 01 | 1 | DATA-02 | integration | `bash tests/phase1/enum-path-check.sh` | ❌ W0 | ⬜ pending |
| 1-01-03 | 01 | 1 | DATA-03 | integration | `bash tests/phase1/log-classification-check.sh` | ❌ W0 | ⬜ pending |
| 1-01-04 | 01 | 1 | DATA-04 | integration | `bash tests/phase1/repeat-run-idempotency.sh` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/phase1/fatal-path-check.sh` — checks startup exits non-zero on critical migration failure
- [ ] `tests/phase1/enum-path-check.sh` — checks sponsor level enum path validity and inserts
- [ ] `tests/phase1/log-classification-check.sh` — checks fatal vs warn/info log separation and summary output
- [ ] `tests/phase1/repeat-run-idempotency.sh` — checks repeated migration runs converge to same DB state
- [ ] `tests/phase1/lib/assertions.sh` — shared helpers for exit code, log, and DB-state assertions

---

## Manual-Only Verifications

All phase behaviors have automated verification.

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 120s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
