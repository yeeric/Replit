# CISC 332 Conference Management Dashboard

A server-rendered web application for managing conference data, built for the CISC 332 Database course at Queen's University. All interactivity is handled with HTMX — no JavaScript framework required.

## Tech Stack

- **Backend:** PHP 8.2 + PDO
- **Database:** PostgreSQL 16
- **Frontend:** HTMX 2.0, Tailwind CSS (both via CDN)
- **Deployment:** Replit autoscale

## Getting Started

### Prerequisites

- PHP 8.2 with `pdo_pgsql` extension
- PostgreSQL 16

### Running Locally

```bash
# Set database connection (or use DATABASE_URL)
export PGHOST=localhost
export PGPORT=5432
export PGDATABASE=conferencedb
export PGUSER=postgres
export PGPASSWORD=yourpassword

# Run migrations and seed data
php php/migrate.php

# Start the server
php -S 0.0.0.0:8080 php/index.php
```

Open `http://localhost:8080` in your browser.

### On Replit

Click **Run** or use the Start application workflow — it runs `bash start.sh` which migrates and starts the server automatically.

## Features

| Page | Route | Description |
|------|-------|-------------|
| Finance Overview | `/` | Revenue summary with SVG donut chart and fee breakdown |
| Committees | `/committees` | Browse sub-committee members via dropdown |
| Hotel Rooms | `/hotels` | View rooms and look up student room assignments |
| Schedule | `/schedule` | Conference sessions by date with inline editing |
| Sponsors & Companies | `/sponsors` | Manage sponsoring companies and view sponsor attendees |
| Job Board | `/jobs` | Job postings with company filter |
| Attendees | `/attendees` | Add and browse attendees by type (Student / Professional / Sponsor) |

## Project Structure

```
php/
  index.php         URL router
  db.php            PDO database connection
  layout.php        Shared HTML layout and UI helpers
  migrate.php       Schema creation and data seeding
  pages/            One file per page/feature
start.sh            Startup script (migration + PHP server)
attached_assets/    Original SQL schema reference file
```

## Database Schema

The schema uses ISA inheritance for the attendee hierarchy:

```
attendee (base)
  ├── student       → optional FK to hotelroom
  ├── professional
  └── sponsor       → required FK to company

company
  └── jobad

subcommittee ←→ committeemember  (via memberofcommittee)
session
hotelroom
```

Registration fees are set automatically by a trigger: Student = $50, Professional = $100, Sponsor = $0.

Sponsorship levels: Platinum ($10,000), Gold ($5,000), Silver ($2,500), Bronze ($1,000).
