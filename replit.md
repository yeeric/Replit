# CISC 332 Conference Management Dashboard

A server-rendered conference management web application built with PHP, HTMX, and Tailwind CSS. No JavaScript framework — all interactivity is handled by HTMX HTML attributes, all pages are rendered by PHP.

## Architecture

- **Frontend**: PHP-generated HTML with HTMX (CDN) for dynamic updates, Tailwind CSS (CDN) for styling
- **Backend**: PHP 8.2 with PDO + PostgreSQL (`php/` directory)
- **Database**: PostgreSQL (Replit-provisioned)
- **Server**: PHP built-in server on port 5000

## Startup

The "Start application" workflow runs `bash start.sh`:
```bash
php -S 0.0.0.0:5000 php/index.php
```

## PHP File Structure

```
php/
  index.php       - URL router, dispatches to page handlers
  db.php          - PDO connection (reads PG* env vars)
  layout.php      - renderLayout(), sidebar(), pageHead() helpers (Salesforce Lightning colours)
  pages/
    dashboard.php  - Finance Overview (SVG pie chart)
    committees.php - Committees (HTMX dropdown → members table)
    hotels.php     - Hotel Rooms (HTMX dropdown → students table)
    schedule.php   - Schedule (HTMX tabs + edit session modal)
    sponsors.php   - Sponsors & Companies (HTMX add/delete company)
    jobs.php       - Job Board (HTMX company filter)
    attendees.php  - Attendees (HTMX tabs + add attendee modal)
start.sh          - Startup script: runs PHP on port 5000
```

## Colour Scheme — Salesforce Lightning Design System

| Element | Colour |
|---|---|
| Sidebar | `#032d60` navy, active item `#0a2e5c` + left `#0176d3` border |
| Primary buttons | `#0176d3` blue |
| App background | `#f3f2f2` warm gray |
| Card / table borders | `#dddbda` |
| Primary text | `#3e3e3c` |
| Muted text | `#706e6b` |
| Fees / pay rates | `#2e844a` green |

## Features (Pages)

1. **Finance Overview** (`/`) — Stat cards + PHP-generated SVG pie chart + fee structure table
2. **Committees** (`/committees`) — Dropdown triggers HTMX fetch of members table
3. **Hotel Rooms** (`/hotels`) — Dropdown triggers HTMX fetch of assigned students
4. **Schedule** (`/schedule`) — Date tabs, session cards, edit session via modal
5. **Sponsors & Companies** (`/sponsors`) — Sponsor list + HTMX add/delete company
6. **Job Board** (`/jobs`) — Job table with HTMX company filter
7. **Attendees** (`/attendees`) — Type tabs + add attendee modal with type-specific fields

## Database Schema (PostgreSQL)

`company`, `committeemember`, `subcommittee`, `hotelroom`, `session`,
`attendee`, `student`, `professional`, `sponsor`, `speaker`,
`jobad`, `attends`, `speaksat`, `memberofcommittee`
