<p align="center">
  <img src="https://raw.githubusercontent.com/ClearanceClarence/DBForge/refs/heads/main/dbforge/assets/logo.svg" alt="DBForge" width="40">
</p>

# Changelog

All notable changes to [DBForge](https://github.com/ClearanceClarence/DBForge) are documented here.

---

## [1.5.0-alpha] — 2026-04-17

> Major feature release: FK drill-down, views management, row detail panel, TOTP two-factor auth, EXPLAIN visualizer, triggers, Operations tab, favorites, sidebar filter, persistent SQL drafts, and sweeping UI improvements.

### New Features

- **Foreign key drill-down** — FK columns in Browse are now clickable links. Click a value to jump to the referenced row in the target table with an exact-match filter. FK columns show a link icon (↗) in the header. A blue banner indicates when viewing filtered results, with a "Clear filter" button. Cross-database FKs supported.
- **Views management** — list, create, edit, and drop views. Views appear in the sidebar with a purple eye icon and in a dedicated "Views" panel on the database overview. View definitions are syntax-highlighted. Edit uses `CREATE OR REPLACE VIEW`. Full CRUD via AJAX.
- **Row detail panel** — click the eye button on any browse row to open a slide-out panel from the right showing every column vertically with full untruncated values. FK values are drill-down links. PK columns show a gold key icon. Close with ×, click outside, or Escape.
- **TOTP two-factor authentication** — enable from Profile modal. Generates a secret, shows a scannable QR code (rendered client-side via [qrcode-generator](https://github.com/nickmillerdev/qrcode-generator) by Kazuhiko Arase, MIT license). Verify with a 6-digit code from any TOTP app (Google Authenticator, Authy, 1Password). On login, a second step prompts for the code with auto-submit on 6 digits. ±1 time window for clock drift. Disable from Profile. Config stores `['password' => hash, 'totp_secret' => base32]` per user.
- **EXPLAIN button** — one-click query plan analysis in the SQL editor. Color-coded access types (green: const/eq_ref, blue: ref/range, amber: index, red: ALL), warning badges on Extra notes (filesort, temporary), row count highlighting, and a legend. Works on selected text or the full editor.
- **Triggers management** — list, create, edit, and drop triggers from the Structure tab. Color-coded badges for timing (BEFORE/AFTER) and event (INSERT/UPDATE/DELETE). Edit uses drop-then-recreate with rollback on failure. Trigger bodies are syntax-highlighted.
- **Operations tab** — dedicated tab (red, table-required) with: Alter Table (engine, row_format, collation, comment), Rename, Move to Database, Copy Table (cross-DB, structure/data toggle), Maintenance (Optimize, Analyze, Check, Repair with inline results), and Danger Zone (Truncate, Drop).
- **Table favorites** — star tables from the sidebar, browse toolbar, or All Tables overview. Favorites section appears at the top of the sidebar. Per-user storage in `logs/favorites.json`.
- **Sidebar table filter** — type-to-filter with match highlighting, favorites filtering, dimming of empty database groups. Press `/` to focus.
- **Persistent SQL drafts** — editor content auto-saves to `sessionStorage` per database. Restored on return, cleared after execution.
- **Profile modal** — click username chip to open. Change password (with current password verification) and 2FA setup/disable. Logout link.

### UI Improvements

- **Page headers redesigned** — 52×52 colored icon badge, subtitle, 4px accent stripe, gradient background. Per-page colors: red (Import), amber (Export), purple (Search), gold (ER), green (Info), red (Operations), blue (Server).
- **Database stat cards** — horizontal layout with colored icon on the left: Tables (green), Total Rows (blue), Data Size (amber), Index Size (purple), Total Size (gold), Collation (gray). Both server and database overviews.
- **Insert Row form redesigned** — vertical list layout with label column (200px, tinted) and input column. Scrollable at 60vh. Responsive to single-column on narrow screens.
- **Danger buttons** — solid red fill with white text everywhere. Hover deepens + adds glow.
- **Danger zone** — red border, hatched header, warning ⚠ on each row title, red hover, solid red action buttons.
- **SQL preview on import** — syntax-highlighted preview after selecting a `.sql` file. Line count, statement count, collapsible.
- **Syntax highlighting in modals** — view create/edit and trigger create/edit textareas have live syntax highlighting via `DBForge.attachHighlighter()`.
- **Column types hidden by default**, **zebra striping**, **pill tabs**, **keyboard shortcut overlay** (`?`), **empty table state** with Insert Row button.

### Themes

- **10 new themes** — Dracula, Monokai, Tokyo Night, Gruvbox Dark, Catppuccin Mocha, GitHub Dark, GitHub Light, Catppuccin Latte, Gruvbox Light, Rosé Pine Dawn, Atom One Light. Each with full component coverage: syntax tokens, buttons, data tables, panels, modals, editors, EXPLAIN badges, trigger badges, FK links, and danger zones.
- **Removed Midnight Teal** — replaced by the more distinctive GitHub Dark and Tokyo Night.
- **Theme selector reorganized** — now split into Light and Dark `<optgroup>` sections, each alphabetically sorted.
- **20 themes total** — 10 light + 10 dark, all with comprehensive CSS variable coverage for every new feature.

### Structure Tab Additions

- **Partitions panel** — queries `information_schema.PARTITIONS`. Shows partition name, method, expression, rows, and sizes.
- **Information panels** — Space Usage (Data, Index, Overhead, Effective, Total + Optimize button) and Row Statistics (Format, Collation, Engine, Next autoindex, Creation, Last update, Last check).
- **Triggers panel** — full CRUD at the bottom of Structure.

### Backend

- `Database.php`: `getViews()`, `getViewDefinition()`, `createView()`, `dropView()`, `getTriggers()`, `createTrigger()`, `dropTrigger()`, `getTableStatus()`, `getPartitions()`, `optimizeTable()`, `analyzeTable()`, `checkTable()`, `repairTable()`, `alterTableOptions()`, `moveTableToDatabase()`, `getEngines()`, `getCollations()`. Extended `browseTable()` with `fkCol`/`fkVal` exact-match filter. Extended `copyTable()` for cross-DB. Display SQL now shows actual values instead of `:placeholder`.
- `Auth.php`: TOTP support — `login()` returns `'2fa_required'`, `verify2fa()`, `is2faPending()`, `getUserTotpSecret()`, `getUserPasswordHash()`. User config supports both `'hash'` and `['password' => hash, 'totp_secret' => secret]` formats.
- `includes/TOTP.php`: Zero-dependency RFC 6238 implementation (Base32, HMAC-SHA1, time-window verification, provisioning URI).
- `includes/favorites.php`: Per-user JSON storage for starred tables.
- `templates/settings_save.php`: Extracted settings save logic for Post-Redirect-Get pattern.
- `templates/operations.php`: Full Operations tab.
- `templates/login_2fa.php`: TOTP verification page.
- `js/qr.js`: QR code generation using [qrcode-generator](https://github.com/kazuhikoarase/qrcode-generator) by Kazuhiko Arase (MIT license).
- 20+ new AJAX endpoints, all CSRF-protected and read-only aware.

### Fixes

- **Settings save now uses Post-Redirect-Get** — config changes (including hidden databases) apply immediately without manual reload.
- **Install checkbox bug** — unchecking "Hide system databases" now correctly writes `[]` instead of silently defaulting to hidden.
- `SHOW TABLE STATUS LIKE ?` prepared placeholder failure on some MySQL versions — now uses escaped string literal.
- History delete button click handler no longer blocked by inline `stopPropagation`.
- Export page missing opening `<?php` tag fixed.
- Browse query bar now shows actual parameter values instead of `:fk_val` placeholders.

### Credits

- QR code generation: [qrcode-generator](https://github.com/nickmillerdev/qrcode-generator) by Kazuhiko Arase, MIT license.

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
- **Zebra striping** — alternating row backgrounds on all data tables. Theme-configurable via `--row-odd` and `--row-even` CSS variables. Added to all 20 themes.
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
