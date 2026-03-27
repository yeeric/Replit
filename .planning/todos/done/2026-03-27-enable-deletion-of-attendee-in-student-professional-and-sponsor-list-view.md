---
created: 2026-03-27T07:13:26.884Z
title: Enable attendee deletion in all list views
area: general
files:
  - php/pages/attendees.php
  - php/pages/sponsors.php
  - php/index.php
---

## Problem

Organizers can create attendees but cannot remove them from the student, professional, and sponsor list views. This creates data-cleanup friction and leaves no direct way to correct mistaken registrations from the UI.

## Solution

Add a delete action for attendee rows across the relevant list views with server-side validation and safe mutation handling (aligned with Phase 2 security constraints).
