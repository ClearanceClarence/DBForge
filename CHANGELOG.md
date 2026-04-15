<p align="center">
  <img src="https://raw.githubusercontent.com/ClearanceClarence/DBForge/main/assets/logo.svg" alt="DBForge" width="40">
</p>

# Changelog

All notable changes to [DBForge](https://github.com/ClearanceClarence/DBForge) are documented here.

---

## [1.4.0] — 2026-04-15

> Query history, search across tables, ER diagram, table operations, keyboard overlay, expanded tokenizer, and a full UI refresh.

### Query History
- **Session-stored history** — every query executed from the SQL tab is recorded with SQL text, target database, execution time, row count, success/error status, and timestamp. Capped at `max_query_history` (default 50).
- **Syntax-highlighted entries** — each history item runs through the tokenizer so keywords, tables, and functions are colored.
- **Click to load** — click any entry to paste the SQL into the editor with highlighting applied.
- **Delete individual items** — × button on hover removes a single entry with fade-out animation via AJAX.
- **Search/filter** — text input in the header filters entries in real-time by SQL content.
- **Clear all** — trash button with confirmation modal wipes the session history.
- **Duplicate suppression** — re-running the same query updates the existing entry instead of creating a duplicate.

### Search Across Tables
- **Global search** — new Search tab (purple, appears when a database is selected). Enter any value and it scans every table in the database.
- **Smart column selection** — searches all VARCHAR, TEXT, CHAR, INT, DECIMAL, DATE, ENUM, and SET columns. Skips BLOB and BINARY.
- **Results by table** — each matching table shown as a card with table name, matched column badges, row count, and a Browse link.
- **Inline highlighting** — search term highlighted in purple within every matching cell.
- **"View all" links** — tables with 5+ matches link to the Browse tab with the search filter pre-filled.
- **Loading state** — animated sliding progress bar while scanning.

### ER Diagram
- **Interactive SVG** — new ER Diagram tab (gold, appears when a database is selected). Renders every table with columns, PK/FK badges, types, and row counts.
- **FK relationships** — orthogonal (right-angle) lines from FK column to referenced PK column. Edge slot offsets prevent overlap when multiple lines leave the same side.
- **Drag tables** — click and drag any table to reposition. Lines redraw in real time.
- **Pan and zoom** — scroll wheel zooms centered on cursor. Click empty space to pan. `+`/`−` buttons and zoom percentage display.
- **Force-directed auto-layout** — "Auto Layout" button runs 300 iterations of a physics simulation: repulsion between all tables, attraction along FK edges, gravity toward center, cooling, damping, and overlap resolution. Connected tables cluster together.
- **Hover highlighting** — hover a table to brighten its FK lines and dim all others.
- **Fit to view** — auto-fits all tables into the viewport. Runs on initial load.
- **Double-click navigation** — double-click a table to open its Structure tab.
- **Dot-grid background** — theme-aware canvas with dot pattern.

### Table Operations
- **Rename table** — pencil button on each table row in the database overview. Modal with current name and new name input. Runs `RENAME TABLE` via AJAX.
- **Copy table** — copy button with modal. Radio toggle for "Structure + Data" or "Structure only". Runs `CREATE TABLE LIKE` + optional `INSERT SELECT`.
- **Drop table** — red trash button with danger confirmation modal showing table name and exact row count.
- All operations are CSRF-protected, read-only aware, and activity-logged.

### Keyboard Shortcut Overlay
- Press `?` anywhere (except in inputs) to open a two-column modal listing all keyboard shortcuts.
- Grouped by: General, SQL Editor, Data Browsing, Modals & Forms.
- Press `?` again or `Esc` to close. Click backdrop to dismiss.

### SQL Tokenizer
- **Expanded from ~120 to 612 tokens** — keywords (286), functions (266), types (46), constants (14).
- Covers: MySQL/MariaDB DDL, DML, DCL, transactions, window functions, JSON functions (full set), spatial functions, regex functions, replication keywords, partition syntax, LOAD DATA INFILE, prepared statements, event scheduler, and more.

### UI Polish
- **Browse query bar** — shows the exact SQL being executed with syntax highlighting via the JS tokenizer. "Edit" link opens it in the SQL tab with auto-execute.
- **Zebra striping** — alternating row backgrounds on all data tables. Theme-configurable via `--row-odd` and `--row-even` CSS variables. Added to all 10 themes.
- **Tab redesign** — clean pill-style tabs with per-category colors: green (data), amber (SQL), purple (search), gold (ER), blue (IO), gray (system). No borders.
- **Empty table state** — large icon, title, description, and "Insert first row" button. Search filter shows matched term with "Clear filter" button.
- **Column headers** — removed forced `text-transform: uppercase`. Names display as stored in the database.
- **Column types hidden by default** — Types toggle button shows them on demand. Cookie-persisted.
- **Header chips** — now show host:port, server version + charset, database count, uptime, and PHP version.
- **Structure tab** — added Collation column between Extra and Comment.

---

## [1.3.0] — 2026-04-15

> Import system, create table UI, insert row form, font control, sidebar improvements.

### Import System
- **SQL import** — upload `.sql` files with custom statement parser respecting strings and comments. "Into existing database" or "New database from file" target modes.
- **CSV import** — upload into any table with configurable delimiter, enclosure, header toggle. Dynamic AJAX table loader.
- **Drag-and-drop** — all file upload zones support click-to-browse and drag-and-drop.

### Create & Drop
- **Create table** — visual form with dynamic columns, type datalist, PK/AI/UQ/IDX checkboxes, live SQL preview.
- **Create database** — form with name, charset, collation. Sidebar "+" modal on any page.
- **Drop database** — per-row trash button. System databases protected.

### Insert Row
- **Auto-generated form** — inputs adapt to column types. AI skip with manual override. NULL checkboxes. "Insert another" batch mode.

### Fonts
- **5 font zones** — General UI, Headings, Sidebar, Table Data, SQL/Code. 28 curated fonts with live preview. Google Fonts auto-loading.

### Sidebar
- **Collapsible toggle** — chevron collapses table list via JS without page reload.
- **Spacing control** — Compact/Normal/Expanded modes. Hide row counts toggle. Cookie-persisted.

### Other
- Auto-sizing inline editor for large text cells.
- Project logo (8E square brackets + cylinder).
- Export works without database selected.
- Context-aware tabs. Tab routing normalization.

---

## [1.2.0] — 2026-04-14

> Production security, installer, settings, structure editor, FK visualization, icons, modals.

### Security — 11-Step Chain
1. Security headers  2. HTTPS enforcement  3. IP whitelist  4. Session hardening  5. Authentication (bcrypt)  6. Brute force protection  7. CSRF tokens  8. Read-only mode  9. Hidden databases  10. Query audit logging  11. `.htaccess` rules

### Installer & Settings
- 3-step first-run wizard. Full settings page writing to `config.php`.

### Structure Editor
- Inline column editing. Add/drop columns. AUTO_INCREMENT controls. FK visualization with color-coded badges. Syntax-highlighted CREATE statement.

### Browse & Data
- Bulk selection and delete. Column type toggle.

### Redesigned Pages
- Home (server overview), Info (grouped sections + danger zone), Server (performance stats), Export (cards with sizes).

### Icons & Modals
- 30+ inline SVG icons. Custom `DBForge.confirm()` / `DBForge.alert()` replacing native dialogs.

---

## [1.0.0] — 2026-04-14

> Initial release. Core database management tool.

- SQL editor with custom tokenizer, context-aware autocomplete, two-pass alias tracking.
- Paginated data grid with inline cell editing, search, sort.
- Structure tab, Info tab, Export (SQL/CSV), Server info.
- 10 built-in themes with cookie persistence.
- Database sidebar with exact `COUNT(*)` row counts.
- Pure PHP + vanilla JS. Zero external dependencies. Drop-in install.
