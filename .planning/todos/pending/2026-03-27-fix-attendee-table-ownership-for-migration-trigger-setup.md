---
created: 2026-03-27T07:32:03.536Z
title: Fix attendee table ownership for migration trigger setup
area: database
files:
  - php/migrate.php
  - php/db.php
  - start.sh
---

## Problem

App startup currently fails on environments where the DB user can connect but is not the owner of the `attendee` relation. Migration aborts at `schema.fee_trigger` with `must be owner of relation attendee`, which blocks `start.sh` and prevents normal app startup.

## Solution

Define and document an ownership/privilege-safe migration path for trigger creation (and related DDL), including expected DB role requirements and a fallback strategy for non-owner runtime roles.
