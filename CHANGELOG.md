<p align="center">
  <img src="https://raw.githubusercontent.com/ClearanceClarence/DBForge/main/assets/logo.svg" alt="DBForge" width="40">
</p>

# Changelog

All notable changes to [DBForge](https://github.com/ClearanceClarence/DBForge) are documented here.

---

## [1.3.0] — 2026-04-15

> Import system, create table UI, insert row form, font control, sidebar improvements.

### Import System
- **SQL import** — Upload `.sql` files and execute every statement sequentially. Custom parser splits on `;` while respecting quoted strings, `--` comments, and `/* */` blocks. Results show success/error counts, rows affected, and timing per statement. Failed statements listed in a collapsible detail section.
- **CSV import** — Upload `.csv` into any existing table. Configurable delimiter (comma, semicolon, tab, pipe), enclosure character, and header row toggle. Columns matched by header name. Empty strings and `NULL` auto-convert to null.
- **Import target mode** — Radio selector: "Into existing database" (dropdown) or "New database from file" (for full dumps with `CREATE DATABASE`). SQL import works without any database pre-selected.
- **Drag-and-drop** — All file upload zones support click-to-browse and drag-and-drop with visual hover feedback.
- **Dynamic table loader** — CSV import has its own database dropdown that loads tables via AJAX when changed.

### Create & Drop
- **Create table** — Visual form: table name, engine (InnoDB/MyISAM/MEMORY/ARCHIVE), collation, comment. Dynamic column rows with name, type (searchable datalist, 35+ types), nullable, default, PK, AI, unique, index. Live syntax-highlighted SQL preview. AI auto-checks PK and unchecks nullable. First column pre-filled as `id INT UNSIGNED`.
- **Create database** — Form with name, charset, collation. Available on home page and via sidebar "+" button (modal on any page). Name validated to `[a-zA-Z0-9_-]`.
- **Drop database** — Per-row trash button on the home page. System databases protected. Confirmation modal.

### Insert Row
- **Auto-generated form** — Click "Insert Row" on any table. Inputs adapt to column types: `number` for INT, `date` for DATE, `datetime-local` for DATETIME, `textarea` for TEXT/JSON, `select` for ENUM. 
- **Auto-increment skip** — AI columns disabled by default with "Manual" override checkbox.
- **NULL support** — Checkbox per nullable column, checked by default when no default value exists.
- **Batch entry** — "Insert another" keeps the form open, clears fields, focuses first input after each insert.

### Fonts
- **5 font zones** — General UI, Headings, Sidebar, Table Data, SQL/Code — each independently configurable in Settings.
- **28 curated fonts** — 16 sans-serif (DM Sans, Inter, Poppins, Work Sans, IBM Plex Sans, Outfit, Sora, etc.) + 12 monospace (JetBrains Mono, Fira Code, Source Code Pro, IBM Plex Mono, etc.) + system fallbacks.
- **Live preview** — Each zone shows a preview panel that updates as you select.
- **Google Fonts auto-loading** — Selected fonts loaded on demand, no manual setup.
- **CSS variables** — `--font-body`, `--font-heading`, `--font-sidebar`, `--font-data`, `--font-mono`.

### Sidebar
- **Collapsible toggle** — Chevron on expanded database toggles the table list via JS without page reload. Click the database name to navigate, click the chevron to collapse.
- **Spacing control** — Gear icon reveals Compact / Normal / Expanded modes. Compact hides icons and tightens padding. Cookie-persisted.
- **Hide row counts** — Toggle checkbox hides the numbers next to table names.

### Editor & UI
- **Auto-sizing inline editor** — Cells with >60 chars or newlines open a resizable textarea (60–300px). `Ctrl+Enter` saves, plain `Enter` inserts newlines.
- **Project logo** — SVG "square brackets + database cylinder" mark. Used in header, login, installer, README.
- **Export without database** — Export tab shows all databases with sizes and download buttons when no database is selected.
- **Context-aware tabs** — Structure and Info only appear when a table is selected. No more empty-parameter URL duplicates.
- **Tab routing fix** — Empty `db`/`table` params normalized to null.

---

## [1.2.0] — 2026-04-14

> Production security, installer, settings, structure editor, FK visualization, icons, modals.

### Security — 11-Step Chain
1. **Security headers** — X-Content-Type-Options, X-Frame-Options, XSS Protection, HSTS, Permissions-Policy.
2. **HTTPS enforcement** — Optional 301 redirect.
3. **IP whitelist** — CIDR support, blocks before page load.
4. **Session hardening** — httponly + secure + samesite cookies, periodic ID regeneration, idle timeout.
5. **Authentication** — Themed login page, multi-user, bcrypt password hashing.
6. **Brute force protection** — IP-based lockout after configurable failed attempts.
7. **CSRF tokens** — On all POST forms and AJAX requests.
8. **Read-only mode** — Blocks INSERT, UPDATE, DELETE, DROP, ALTER at both UI and API level.
9. **Hidden databases** — Filtered from sidebar, autocomplete, and direct URL access.
10. **Query audit logging** — Timestamp, username, database, IP, duration per query.
11. **`.htaccess` rules** — Blocks config.php, includes/, logs/, hidden files. Disables directory listing.

### Installer & Settings
- **First-run wizard** — 3 steps: test database connection → create admin account (bcrypt, min 6 chars, weak password rejection) → writes `config.php`. No default credentials ever exist.
- **Settings page** — Full config editor: database connection, default theme, rows per page, all auth toggles, user management (add/remove/change passwords), IP whitelist, hidden databases. All changes write to `config.php`.

### Structure Editor
- **Inline column editing** — Pencil icon → edit name, type (searchable datalist), nullable, default, extra, comment. Save runs `ALTER TABLE ... CHANGE`.
- **Add/drop columns** — Add with position control (FIRST / AFTER / end). Drop with confirmation modal.
- **AUTO_INCREMENT** — Shows current next-ID with Set (custom value) and Reset (MAX+1) buttons.
- **FK visualization** — Gold/blue left borders on PK/FK rows. Clickable FK badges linking to referenced tables. Dedicated Foreign Keys and Referenced By tables with ON DELETE/ON UPDATE color-coded badges.
- **Syntax-highlighted CREATE** — `SHOW CREATE TABLE` output tokenized with the same engine as the SQL editor.

### Browse & Data
- **Bulk selection** — Checkbox column with select-all (indeterminate state). Bulk action bar with "Delete Selected". Single `DELETE ... WHERE IN (?)` query.
- **Column type toggle** — Button to hide/show type labels under column headers. Cookie-persisted.

### Redesigned Pages
- **Home page** — Server overview dashboard with all databases in a table: table count, rows, data/index/total size, collation, per-row actions.
- **Info page** — Header card with key stats, grouped sections (Properties, Storage, Timestamps), prominent Danger Zone with exact row counts.
- **Server page** — Grouped sections: MySQL Server, Performance, PHP Environment.
- **Export page** — Cards with row counts, size estimates, filename on download buttons. Per-table export list.

### Icons & Modals
- **30+ inline SVG icons** — Lucide-style, via `icon()` PHP helper. Used everywhere. No CDN.
- **Custom modal dialogs** — `DBForge.confirm()` / `DBForge.alert()` replace all native browser dialogs. Themed, animated, keyboard-navigable, backdrop blur.

### Changed
- Config generated by installer, not shipped as a file.
- Auth always enabled for new installs.

---

## [1.0.0] — 2026-04-14

> Initial release. Core database management tool.

### SQL Editor
- Custom tokenizer with 12 token types (keyword, function, type, table, string, number, comment, operator, identifier, backtick, variable, constant).
- Transparent textarea overlay for real-time syntax highlighting.
- Context-aware autocomplete: tables after `FROM`, scoped columns after `WHERE`, dot-notation alias resolution.
- Two-pass table/alias recognition across entire queries.
- Line numbers, auto-closing brackets/quotes, Tab indent/dedent.
- `Ctrl+Enter` execute, `Ctrl+Shift+S` focus editor.

### Data Browsing
- Paginated data grid with search, sort by column, type-aware cell styling.
- Inline cell editing with AJAX saves, NULL support, Enter/Tab/Escape shortcuts.

### Database Management
- Structure tab with column definitions and index viewer.
- Info tab with table metadata.
- Export as SQL (CREATE + INSERT) or CSV. Full database dump.
- Server info with MySQL version, uptime, connections, PHP environment.
- Database sidebar with exact `COUNT(*)` row counts.

### Theming
- 10 built-in themes: 5 light (Clean, Forge, Sand, Lavender, Solarized Light) + 5 dark (Dark Industrial, Midnight Teal, Carbon, Nord, Solarized Dark).
- Theme auto-discovery from `themes/` directory.
- Cookie-persisted theme selection.

### Architecture
- Pure PHP + vanilla JS. Zero external dependencies.
- Drop-in install on XAMPP / WAMP / MAMP / Laragon.
- PDO prepared statements throughout. `htmlspecialchars()` on all output.
