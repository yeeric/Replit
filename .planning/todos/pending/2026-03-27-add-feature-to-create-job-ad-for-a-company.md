---
created: 2026-03-27T07:34:17.412Z
title: Add feature to create job ad for a company
area: general
files:
  - php/pages/jobs.php
  - php/pages/sponsors.php
---

## Problem

The dashboard currently supports browsing job postings, but there is no UI/route flow to create a new job ad for a company from within the app.

## Solution

Add a create-job-ad workflow tied to company records, including form input, server-side validation, and insertion into the jobs data path.
