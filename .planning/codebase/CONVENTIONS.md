# CONVENTIONS

## Coding Style
- Uses strict PHP open tags and procedural file-level handlers (`php/pages/*.php`).
- Comments segment sections with banner separators (e.g., `// ── ... ──`).
- Variables typically short, readable names (`$db`, `$stmt`, `$rows`, `$content`).
- HTML is composed via heredoc strings and manual concatenation.

## Routing and Handler Conventions
- Full-page and HTMX partial logic coexist in each page file.
- Branching based on method + path suffix + `HTTP_HX_REQUEST` header.
- Path detection uses `parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)` consistently.

## Data Access Patterns
- Shared connection via `getDb()` singleton (`php/db.php`).
- Prepared statements are common for writes and filtered reads.
- Some direct queries for unfiltered list pages (`$db->query(...)`).
- Type casts to normalize output for rendering (`fee::text`, `date::text`).

## Error Handling Patterns
- Migration uses `safeExec()` wrapper to log and continue (`php/migrate.php`).
- Attendee create flow wraps multi-table writes in explicit transaction with rollback (`php/pages/attendees.php`).
- Some handlers return raw exception messages to client HTML on failure (`php/pages/attendees.php`).

## UI/Frontend Conventions
- Shared Salesforce-inspired palette defined in inline Tailwind config (`php/layout.php`).
- Tailwind utility classes combined with inline style attributes for hover/brand colors.
- HTMX attributes (`hx-get`, `hx-post`, `hx-delete`, `hx-target`, `hx-swap`) handle interaction flows.
- Inline JavaScript used sparingly for modal and tab-state helpers.

## Security and Output Conventions
- `htmlspecialchars()` used in multiple rendering paths for user/content escaping.
- Not all interpolated output is escaped consistently across every table cell path.
- No CSRF token pattern is implemented for mutating endpoints.

## Project/Tooling Conventions
- Runtime operations assume Replit environment defaults (`.replit`, `start.sh`).
- Migration is expected to be idempotent and safe on repeated startup (`php/migrate.php`).
- Documentation in `README.md` and `replit.md` is aligned to PHP + HTMX approach.
