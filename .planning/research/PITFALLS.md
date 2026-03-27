# Pitfalls Research — Brownfield Conference Dashboard

## Pitfall 1: Silent Migration Failures

- Warning signs: migration logs errors but server still starts
- Prevention: fail-fast on critical schema steps; classify optional seed errors separately
- Phase mapping: Phase 1 foundation hardening

## Pitfall 2: Security Retrofits Applied Inconsistently

- Warning signs: some POST/DELETE paths protected, others not
- Prevention: central CSRF helper + route checklist for all mutating endpoints
- Phase mapping: Phase 2 security hardening

## Pitfall 3: Tests Added Too Late

- Warning signs: frequent manual regressions and fear of refactoring
- Prevention: establish minimal test harness before broad refactors
- Phase mapping: Phase 3 testing baseline

## Pitfall 4: Tooling Drift Persists

- Warning signs: docs/commands disagree on canonical run path
- Prevention: pick one runtime path, update docs/config/scripts together
- Phase mapping: Phase 4 developer experience cleanup

## Pitfall 5: Scope Creep During Hardening

- Warning signs: new features introduced before stability goals complete
- Prevention: keep roadmap tied to reliability/security objectives with explicit out-of-scope list
- Phase mapping: all phases
