# CISC 332 Conference Management Dashboard

A full-stack conference management web application for conference organizers. Built with React, Express, PHP, and PostgreSQL.

## Architecture

- **Frontend**: React + TypeScript, Vite, TanStack Query, shadcn/ui, Tailwind CSS
- **API Backend**: PHP 8.2 (`php/router.php`) via PDO + PostgreSQL, served on port 8001
- **Web Server**: Express.js proxies all `/api/*` requests to the PHP backend via `http-proxy-middleware`
- **Database**: PostgreSQL (Replit-provisioned)
- **Routing**: wouter (client-side)
- **Forms**: react-hook-form
- **Charts**: Recharts

## Startup

The "Start application" workflow runs `bash start.sh` which:
1. Starts PHP built-in server on port 8001: `nix-shell -p php -p php82Extensions.pdo_pgsql --run "php -S 0.0.0.0:8001 -t php php/router.php"`
2. Starts Node.js/Vite dev server on port 5000: `npm run dev`

Express receives all `/api/*` requests and proxies them to the PHP server. The `fixRequestBody` middleware ensures POST/PUT request bodies are re-streamed correctly through the proxy.

## PHP Backend Files

- `php/db.php` — PDO connection helper (reads PGHOST, PGPORT, PGDATABASE, PGUSER, PGPASSWORD)
- `php/router.php` — main PHP router handling all 15 API endpoints

## Features (Pages)

1. **Finance Overview** (`/`) — Total conference intake broken down by registration fees and sponsorship amounts, with a pie chart and fee structure table
2. **Committees** (`/committees`) — Select a sub-committee from a dropdown to view its members
3. **Hotel Rooms** (`/hotels`) — Select a hotel room to see which students are assigned to it
4. **Schedule** (`/schedule`) — View sessions by day, edit session date/time/location via a modal
5. **Sponsors & Companies** (`/sponsors`) — List sponsors with their tier, manage companies (add/delete)
6. **Job Board** (`/jobs`) — Browse all job ads, filter by company
7. **Attendees** (`/attendees`) — Three-tab view (Students, Professionals, Sponsors), add new attendees with type-specific fields

## Database Schema

Tables (from SQL script):
- `company` — sponsoring companies
- `committeemember` — organizing committee members
- `subcommittee` — sub-committees with a chair member
- `hotelroom` — hotel rooms with bed counts
- `session` — conference sessions with date/time/location
- `attendee` — all conference attendees (with attendeetype enum)
- `student` — student-specific data (room assignment)
- `professional` — professional attendees
- `sponsor` — sponsor-specific data (level, company, email count)
- `speaker` — speakers (may or may not be attendees)
- `jobad` — job advertisements posted by companies
- `attends` — M:N attendee ↔ session
- `speaksat` — M:N speaker ↔ session
- `memberofcommittee` — M:N committee member ↔ sub-committee

## API Endpoints (all handled by PHP)

- `GET /api/committees` — list all sub-committees
- `GET /api/committees/:id/members` — members of a specific committee
- `GET /api/hotel-rooms` — list all hotel rooms
- `GET /api/hotel-rooms/:id/students` — students assigned to a room
- `GET /api/sessions/dates` — distinct session dates
- `GET /api/sessions?date=` — sessions (optionally filtered by date)
- `PUT /api/sessions/:id` — update session date/time/location
- `GET /api/sponsors` — sponsors with company name and level
- `GET /api/companies` — all companies
- `POST /api/companies` — add a new company
- `DELETE /api/companies/:id` — delete company and cascaded data
- `GET /api/companies/:id/jobs` — jobs posted by a company
- `GET /api/jobs` — all job ads with company name
- `GET /api/attendees` — attendees split into { students, professionals, sponsors }
- `POST /api/attendees` — create new attendee (with type-specific sub-record, wrapped in a DB transaction)
- `GET /api/stats/intake` — { registrationAmount, sponsorshipAmount }

## Key Files

- `php/db.php` — PDO connection helper
- `php/router.php` — PHP API router (all endpoints)
- `start.sh` — startup script (PHP + Node)
- `server/routes.ts` — Express proxy to PHP (`/api/*` → `http://localhost:8001`)
- `client/src/App.tsx` — client-side routing
- `client/src/pages/` — page components
- `client/src/hooks/` — data fetching hooks
- `client/src/components/layout/` — Shell + Sidebar layout
