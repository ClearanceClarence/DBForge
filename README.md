<p align="center">
  <img src="https://raw.githubusercontent.com/ClearanceClarence/DBForge/refs/heads/main/dbforge/assets/logo.svg" alt="DBForge" width="80">
</p>

<h1 align="center">DBForge</h1>

<p align="center">
  <strong>The database tool phpMyAdmin should have been.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Version-1.5.0--alpha-4ade80?style=flat-square" alt="Version 1.5.0-alpha">
  <img src="https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/MariaDB-10.3+-003545?style=flat-square&logo=mariadb&logoColor=white" alt="MariaDB">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="MIT License">
  <img src="https://img.shields.io/badge/Dependencies-Zero-orange?style=flat-square" alt="Zero Dependencies">
</p>

<p align="center">
  <em>Drop a folder into your web server. Run the installer. Manage your databases.<br>No Composer. No npm. No build step. No framework. Just PHP.</em>
</p>

<br>

<p align="center">
  <a href="#-why-dbforge">Why DBForge?</a> · 
  <a href="#-get-started">Get Started</a> · 
  <a href="#-sql-editor">SQL Editor</a> · 
  <a href="#-data-browsing--editing">Browse & Edit</a> · 
  <a href="#-search-across-tables">Search</a> · 
  <a href="#-er-diagram">ER Diagram</a> · 
  <a href="#-views--triggers">Views & Triggers</a> · 
  <a href="#-query-history">History</a> · 
  <a href="#-table-management">Tables</a> · 
  <a href="#-import--export">Import/Export</a> · 
  <a href="#-security--2fa">Security & 2FA</a> · 
  <a href="#-theming--fonts">Themes</a> · 
  <a href="CHANGELOG.md">Changelog</a>
</p>

---

## 💡 Why DBForge?

phpMyAdmin ships with every shared host and XAMPP stack on the planet, but its interface hasn't meaningfully changed since 2003. The SQL editor is a `<textarea>` with coloring. Editing a row reloads the entire page. Searching means opening each table one by one. There's no way to visualize relationships. No query history. The security defaults are weak enough that the official docs tell you to restrict access at the web server level.

DBForge is a ground-up replacement that keeps the one thing phpMyAdmin gets right — drop a folder, open a browser, manage your databases — and fixes everything else.

| | phpMyAdmin | DBForge |
|:-|:-----------|:--------|
| **Install** | Download, extract, edit `config.inc.php`, set blowfish secret | Drop folder → open browser → 3-step visual wizard |
| **SQL Editor** | Textarea with syntax coloring | Custom tokenizer (612 tokens), context-aware autocomplete, EXPLAIN visualizer |
| **Edit Data** | Click Edit → full page form → submit → redirect back | Click any cell → type → Enter. AJAX save. Page never reloads |
| **FK Navigation** | Manual: note the ID, switch tables, search | Click FK value → jump to referenced row. Automatic linking |
| **Views** | Basic listing | Full CRUD with syntax-highlighted definitions, sidebar integration |
| **Triggers** | Basic listing | Create, edit (drop+recreate with rollback), drop. Color-coded badges |
| **Search** | Search within one table at a time | Search across every table in the database simultaneously |
| **ER Diagram** | Not available | Interactive SVG with drag, zoom, force-directed auto-layout, hover highlighting |
| **Query History** | Not available | Session-stored with syntax highlighting, search, click to re-run, persistent drafts |
| **Table Ops** | Scattered across Operations, Structure, and SQL tabs | Dedicated Operations tab + overview row actions. Favorites system |
| **Import** | Standard form | Drag-and-drop SQL/CSV with syntax-highlighted preview |
| **Auth** | HTTP Basic or cookie-based with manual config | Bcrypt + optional TOTP 2FA, brute force lockout, CSRF, IP whitelist |
| **Themes** | 3 color schemes | 20 polished themes (10 light, 10 dark) + custom theme API + per-zone font control |
| **Dependencies** | Requires Composer on v5.2+ | Zero. Pure PHP + vanilla JS. Entire codebase is in the repo |

---

## 🚀 Get Started

```bash
git clone https://github.com/ClearanceClarence/DBForge.git
```

Copy the `dbforge/` folder into your web root (`htdocs/`, `www/`, `public_html/`) and open it in a browser.

The **first-run installer** walks you through three steps:

1. **Database connection** — enter host, port, username, password. DBForge tests the connection before proceeding. If it fails, you see the exact PDO error with suggestions.
2. **Admin account** — choose a username and password. Passwords are bcrypt-hashed immediately. Minimum 6 characters. Common passwords like `password`, `123456`, `admin` are rejected. No default credentials are ever written to disk.
3. **Done** — `config.php` is generated and you're redirected to the login page.

Works out of the box with **XAMPP**, **WAMP**, **MAMP**, **Laragon**, **DDEV**, **Herd**, or any Apache + PHP + MySQL/MariaDB stack. No `.env` files, no Composer, no npm, no build tools.

---

## ✏️ SQL Editor

Not a `<textarea>` with color. A real code editor built from scratch in vanilla JavaScript.

### Tokenizer

The tokenizer recognizes **612 tokens** across 4 categories:

| Category | Count | Examples |
|:---------|:------|:--------|
| **Keywords** | 286 | `SELECT`, `LEFT JOIN`, `PARTITION BY`, `SQL_CALC_FOUND_ROWS`, `STRAIGHT_JOIN`, `FOR UPDATE NOWAIT`, `LOAD DATA INFILE`, `XA COMMIT` |
| **Functions** | 266 | `COUNT()`, `JSON_TABLE()`, `REGEXP_REPLACE()`, `ST_DISTANCE()`, `UUID_TO_BIN()`, `CUME_DIST()`, `AES_ENCRYPT()` |
| **Types** | 46 | `INT`, `VARCHAR`, `JSON`, `GEOMETRY`, `MULTIPOLYGON`, `SERIAL` |
| **Constants** | 14 | `TRUE`, `FALSE`, `NULL`, `CURRENT_TIMESTAMP`, `MAXVALUE`, `PI` |

Each token type gets its own color in every theme. The tokenizer handles single-quoted strings, double-quoted strings, backtick identifiers, single-line comments (`--`), block comments (`/* */`), hex literals (`0xFF`), binary literals (`0b1010`), session variables (`@@`), and user variables (`@var`).

### Autocomplete

Context-aware, not just a word list:

- Type `FROM ` → suggests all table names in the current database
- Type `WHERE u.` → suggests columns from the table aliased as `u`
- Type `JOIN orders ` → suggests `ON` and available columns
- Type `GROUP BY ` → suggests columns from tables already in the query

The system uses a two-pass approach: first pass extracts all table references and aliases from `FROM`, `JOIN`, `INTO`, and `UPDATE` clauses. Second pass re-classifies matching identifiers throughout the entire query, so `u` in `WHERE u.active = 1` gets highlighted as a table reference when there's a `FROM users u` elsewhere in the query.

### Editor Features

- Real-time syntax highlighting via a transparent `<textarea>` overlaid on a `<pre>` backdrop
- Line numbers with scroll sync
- `Ctrl+Enter` to execute
- `Tab` inserts 4 spaces, `Shift+Tab` removes indent
- Auto-closing for quotes (`'`, `"`), backticks, and parentheses
- Query results displayed inline: SELECT results as a data table, write operations as "N rows affected"
- Error display with MySQL error code
- **EXPLAIN button** — one-click query plan analysis. Results rendered in a dedicated panel with color-coded access types (green: const/eq_ref, blue: ref/range, amber: index, red: ALL), warning badges on Extra notes (filesort, temporary), row count highlighting, and a legend. Works on selected text or the full editor content
- **Persistent drafts** — editor content auto-saves to `sessionStorage` per database. Navigate away and come back — your query is still there. Cleared after successful execution

---

## 📊 Data Browsing & Editing

### Browse

Select a table and you see a paginated data grid with:

- Column headers with click-to-sort (ASC/DESC toggle)
- Type-aware cell styling: NULL values dimmed, numbers right-aligned, dates formatted, hashes truncated
- Search bar that filters across all columns with `LIKE '%term%'`
- Pagination controls with page numbers and row counts
- A **syntax-highlighted query bar** showing the exact SQL being executed with an "Edit" link that opens it in the SQL tab
- **FK drill-down** — columns with foreign keys display values as clickable links. Click to jump to the referenced row in the target table with an exact-match filter. A blue banner shows the active filter with a "Clear" button
- **Row detail panel** — click the eye button (👁) on any row to open a slide-out panel showing every column vertically with full untruncated values, column types, PK/FK badges, and FK drill-down links
- **Sidebar table filter** — type `/` or use the filter input to search tables by name with match highlighting
- **Favorites** — star tables from the sidebar, browse toolbar, or All Tables overview. Starred tables appear in a dedicated section at the top of the sidebar

### Inline Editing

Click any cell to edit it in place:

- Single-line `<input>` for short values
- Auto-switching to a resizable `<textarea>` for content over 60 characters or containing newlines (auto-grows between 60–300px)
- `Enter` saves via AJAX (green flash on success, red on error)
- `Tab` saves and moves to the next cell
- `Esc` cancels
- `Ctrl+Enter` saves in textarea mode
- NULL checkbox for nullable columns
- No page reload. Ever.

### Bulk Operations

- Checkbox column with select-all (supports indeterminate state when some rows are checked)
- Bulk action bar appears: "3 rows selected" with "Delete Selected" and "Clear"
- Delete sends a single `DELETE FROM table WHERE pk IN (?,?,?)` prepared statement
- Confirmation via themed modal dialog, not a native browser `confirm()`

### Insert Row

Click "Insert Row" in the toolbar. A form auto-generates from the table's column definitions:

| Column Type | Input |
|:------------|:------|
| `INT`, `BIGINT`, `DECIMAL` | `<input type="number">` |
| `DATE` | `<input type="date">` with native date picker |
| `DATETIME`, `TIMESTAMP` | `<input type="datetime-local">` |
| `TIME` | `<input type="time">` |
| `TEXT`, `MEDIUMTEXT`, `JSON`, `BLOB` | `<textarea>` |
| `ENUM('a','b','c')` | `<select>` with parsed values |
| `AUTO_INCREMENT` | Disabled with "AUTO" placeholder + "Manual" checkbox |
| Everything else | `<input type="text">` |

NULL checkbox per nullable column. Default values pre-filled. "Insert another" checkbox keeps the form open for batch entry — clears fields and focuses the first input after each insert. Shows the auto-generated ID on success.

### Empty Table State

Empty tables show a centered icon, "This table is empty" title, the table name, and an "Insert first row" button. Filtered views with no matches show the search term and a "Clear filter" button.

---

## 🔍 Search Across Tables

The feature phpMyAdmin doesn't have.

Open the **Search tab** (available when a database is selected), type any value, and DBForge scans every table in the database:

1. Gets all tables via `SHOW TABLE STATUS`
2. For each table, gets columns via `SHOW FULL COLUMNS`
3. Skips BLOB, BINARY, and GEOMETRY columns
4. Builds `SELECT * FROM table WHERE col1 LIKE ? OR col2 LIKE ? ... LIMIT 6` with prepared statements
5. Returns up to 5 matching rows per table (the 6th is used to detect "has more")
6. Identifies which specific columns matched by comparing `stripos` on returned values

**Results display:**
- Summary bar: "Found in 5 tables — 23+ matches across 39 tables searched"
- Per-table cards with table name, matched column badges (purple), row count, "Browse" link
- Full data rows with the search term highlighted in purple `<mark>` tags within matching cells
- Tables with 5+ matches show a "View all matches in tablename →" link that opens the Browse tab with the search filter pre-filled

Use cases: tracing foreign key values across tables, finding where an email address is stored, locating orphaned data, checking if a specific ID appears in junction tables.

---

## 🔗 ER Diagram

Interactive entity-relationship diagram rendered in pure SVG. No external charting libraries.

Open the **ER Diagram tab** and every table in the database is displayed as a card showing:
- Table name and row count in the header
- Every column with name, short type, and PK/FK badges
- PK rows tinted green, FK rows labeled with gold "FK" text
- Drop shadow for depth

**Foreign key relationships** are drawn as orthogonal (right-angle) lines from FK columns to referenced PK columns. Lines exit the table edge horizontally, turn 90° vertically, then enter the target table horizontally. Multiple lines from the same edge get progressively longer stubs to prevent overlap.

### Interaction

- **Drag** any table to reposition it. All connected lines redraw in real time.
- **Pan** by clicking empty space and dragging.
- **Zoom** with scroll wheel (centered on cursor position) or `+`/`−` buttons. Zoom percentage displayed.
- **Fit** button auto-centers all tables in the viewport.
- **Double-click** a table to navigate to its Structure tab.

### Force-Directed Auto-Layout

Click **"Auto Layout"** and a physics simulation runs:

- **Repulsion** between all table pairs (inverse square force, Coulomb-like) — prevents clustering
- **Attraction** along FK edges (spring force, Hooke's law) — groups related tables
- **Gravity** toward the center of mass — prevents drift
- **Cooling** — force strength decreases over 300 iterations so the layout settles
- **Damping** — velocity reduced each step (0.85×) for stability
- **Overlap resolution** — 10 post-simulation passes push apart any tables with bounding box intersections

Result: connected tables cluster together, unconnected tables spread to the periphery, nothing overlaps.

### Hover Highlighting

Hover any table and its FK relationship lines brighten to full opacity with thicker stroke. All unrelated lines dim to near-invisible. Instantly trace which tables are connected without reading SQL.

---

## 👁 Views & Triggers

### Views

Views are listed alongside tables in the sidebar with a purple eye icon, and in a dedicated "Views" panel on the database overview page.

- **List** — each view shows its name, definer, and syntax-highlighted SQL definition
- **Create** — modal with name field and definition textarea (with live syntax highlighting). Validates and runs `CREATE VIEW`
- **Edit** — fetches current definition via `SHOW CREATE VIEW`, extracts the SELECT body, pre-fills the modal. Saves with `CREATE OR REPLACE VIEW`
- **Drop** — danger-styled confirm modal, `DROP VIEW IF EXISTS`
- **Browse** — click a view name to browse its data just like a table

### Triggers

Triggers are managed from the Structure tab in a dedicated panel at the bottom.

- **List** — each trigger shows timing badge (BEFORE = blue, AFTER = green), event badge (INSERT = green, UPDATE = amber, DELETE = red), name, definer, and syntax-highlighted body
- **Create** — modal with name, timing dropdown, event dropdown, and body textarea with live syntax highlighting. Pre-filled with `BEGIN … END` skeleton
- **Edit** — MySQL has no `ALTER TRIGGER`, so edit performs drop-then-recreate. If the new `CREATE` fails, the original trigger is restored from a pre-drop snapshot
- **Drop** — danger-styled confirm modal

---

## 📜 Query History

Every query executed from the SQL tab is saved to the PHP session:

- **SQL text** — the full query
- **Target database** — which database it ran against
- **Execution time** — in seconds with millisecond precision
- **Row count** — rows returned (SELECT) or affected (INSERT/UPDATE/DELETE)
- **Success/error** — green check or red × icon
- **Timestamp** — displayed as relative time ("just now", "2m ago", "1h ago")

The **history panel** is a collapsible section below the SQL editor. Each entry is syntax-highlighted using the same tokenizer as the editor. Features:

- **Click to load** — click an entry and the SQL is pasted into the editor with highlighting applied. Editor scrolls into view.
- **Search/filter** — text input filters entries in real-time by SQL content
- **Delete individual** — × button on hover removes a single entry with a fade-out animation
- **Clear all** — trash button with confirmation modal wipes the session history
- **Duplicate suppression** — re-running the exact same query updates the existing entry's timestamp instead of creating a new one at the top

History is capped at `max_query_history` in config (default 50). Session-scoped — survives page navigations but clears on logout.

---

## 🏗 Table Management

### Create Table

On the database overview page, click **"Create Table"** to open a visual form:

- **Table options:** name, engine (InnoDB / MyISAM / MEMORY / ARCHIVE), collation, optional comment
- **Column rows:** dynamic add/remove. Each column has: name, type (searchable `<datalist>` with 35+ MySQL types), nullable checkbox, default value, PK, AI, Unique, Index checkboxes
- **Auto-wiring:** checking Auto Increment automatically checks Primary Key and unchecks Nullable. First column pre-filled as `id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY`
- **Live SQL preview:** a collapsible `<details>` section shows the complete `CREATE TABLE` statement, syntax-highlighted, updating on every keystroke

On submit, the SQL is sent to the `execute_sql` AJAX endpoint and on success you're redirected to the new table's Structure tab.

### Create / Drop Database

- **Create:** form with name (validated to `[a-zA-Z0-9_-]`), character set (utf8mb4, utf8, latin1, ascii, binary), and collation. Available on the home page and via the sidebar "+" button on any page (opens a modal without leaving your current view).
- **Drop:** trash button on each database row. System databases (`mysql`, `information_schema`, `performance_schema`, `sys`) are protected — no trash button rendered.

### Table Operations

Every table row in the database overview has action buttons:

| Button | Action | Implementation |
|:-------|:-------|:---------------|
| 📝 Rename | Modal with name input | `RENAME TABLE old TO new` |
| 📋 Copy | Modal with name + "Structure + Data" / "Structure only" radio | `CREATE TABLE new LIKE old` + optional `INSERT INTO new SELECT * FROM old` |
| 🗑 Drop | Danger confirmation modal with row count | `DROP TABLE name` |

All operations: CSRF-protected, blocked in read-only mode, activity-logged.

### Structure Editor

On the Structure tab, every column is editable inline:

- Click the pencil icon → fields become editable: name, type (searchable datalist), nullable, default, extra, comment
- Save runs `ALTER TABLE ... CHANGE COLUMN`
- Add columns with position control: FIRST, AFTER specific column, or at end
- Drop columns with confirmation modal
- AUTO_INCREMENT: shows current next-ID with "Set" (any value) and "Reset" (MAX+1) buttons
- Collation column shows per-column collation (from `SHOW FULL COLUMNS`)
- CREATE statement at the bottom, syntax-highlighted using the tokenizer

### Foreign Key Visualization

The Structure tab queries `information_schema.KEY_COLUMN_USAGE` and `REFERENTIAL_CONSTRAINTS` to display:

- **PK columns**: gold left border + key icon badge
- **FK columns**: blue left border + FK icon badge with clickable link to the referenced table
- **Foreign Keys table**: constraint name, column, referenced table/column, ON DELETE and ON UPDATE rules with color-coded badges (CASCADE = red, RESTRICT = gray, SET NULL = amber)
- **Referenced By table**: which other tables reference this table's columns

---

## 📥 Import & Export

### Import SQL

Upload a `.sql` file and every statement is executed sequentially. The custom parser splits on `;` while respecting:
- Single-quoted and double-quoted strings
- Single-line comments (`--`)
- Block comments (`/* */`)
- Escaped characters inside strings

**Target mode selector** — two options:
- "Into existing database" — pick from a dropdown, statements execute in that context
- "New database from file" — no database selected, the file's own `CREATE DATABASE` and `USE` statements take effect

Results show per-statement success/error counts, total rows affected, execution time. Failed statements listed in a collapsible detail section with the SQL snippet and MySQL error.

### Import CSV

Upload a `.csv` file into any existing table:
- **Database dropdown** — dynamically loads tables via AJAX when changed
- **Delimiter** — comma, semicolon, tab, or pipe
- **Enclosure** — double or single quote
- **Header row** — toggle whether the first row contains column names

CSV is parsed with `fgetcsv()` (handles multiline quoted fields). Rows inserted via prepared statements. Empty strings and "NULL" values auto-convert to null. Column count mismatches are padded/trimmed. Errors collected per row (max 20).

### Export

- **Table export** — SQL dump (`CREATE TABLE` + `INSERT`) or CSV with column headers
- **Database export** — full dump with `CREATE DATABASE IF NOT EXISTS` + `USE` + all tables
- **Export page** — shows file size estimates and row counts per table. Works without a database selected (shows all databases with one-click download)

All file uploads support **drag-and-drop** with visual hover feedback.

---

## 🔒 Security & 2FA

Not bolted on. DBForge ships with a 12-step security chain — all opt-in so local dev stays frictionless, but production stays locked down.

| # | Layer | What It Does |
|:--|:------|:-------------|
| 1 | **Security headers** | `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `X-XSS-Protection: 1`, HSTS, `Permissions-Policy` |
| 2 | **HTTPS enforcement** | Optional 301 redirect from HTTP to HTTPS |
| 3 | **IP whitelist** | Block by IP address or CIDR range (`192.168.1.0/24`). Checked before anything else loads |
| 4 | **Session hardening** | `httponly`, `secure`, `samesite=Lax` cookies. Periodic session ID regeneration. Configurable idle timeout |
| 5 | **Authentication** | Themed login page, multi-user support, bcrypt (`$2y$10$`) password hashing |
| 6 | **TOTP Two-Factor Auth** | Optional per-user TOTP (RFC 6238). Enable from Profile → scan QR code with any authenticator app (Google Authenticator, Authy, 1Password). 6-digit verification on login with ±1 time window for clock drift. Disable any time from Profile |
| 7 | **Brute force protection** | IP-based lockout after N failed attempts (configurable). Lockout duration configurable. Both password and 2FA failures count |
| 8 | **CSRF tokens** | Generated per session, validated on every POST form and every AJAX request. Meta tag in `<head>` for JS access |
| 9 | **Read-only mode** | Regex-based detection of write keywords (`INSERT`, `UPDATE`, `DELETE`, `DROP`, `ALTER`, `TRUNCATE`, `CREATE`, `GRANT`, `REVOKE`). Blocked at both UI and API level |
| 10 | **Hidden databases** | Configurable list filtered from sidebar, autocomplete, URL access, and export. Accessing a hidden DB via URL resets to home |
| 11 | **Query audit logging** | Every query logged to a file with timestamp, username, database, IP address, and execution time |
| 12 | **`.htaccess` rules** | Blocks direct web access to `config.php`, `includes/`, `logs/`, hidden files. Disables directory listing. Sets security headers at Apache level |

**First-run installer** means no default `admin/admin` ever exists. Passwords are bcrypt-hashed from the very first login.

```php
// Production config with 2FA enabled
'security' => [
    'require_auth'       => true,
    'users'              => [
        'admin' => [
            'password'    => '$2y$10$...bcrypt_hash...',
            'totp_secret' => 'BASE32ENCODEDSECRET',  // omit to disable 2FA
        ],
    ],
    'force_https'        => true,
    'ip_whitelist'       => ['10.0.0.0/8', '192.168.1.0/24'],
    'hidden_databases'   => ['information_schema', 'performance_schema', 'mysql', 'sys'],
    'read_only'          => false,
    'query_log'          => true,
    'max_login_attempts' => 5,
    'lockout_duration'   => 300,
],
```

---

## 🎨 Theming & Fonts

### 10 Built-In Themes

| Light | Dark |
|:------|:-----|
| **Atom One Light** — crisp white, vibrant blue/orange/green | **Carbon** — charcoal gray, VS Code blue |
| **Catppuccin Latte** — warm cream, soft pastel accents | **Catppuccin Mocha** — soothing pastels on warm dark |
| **Clean** — crisp white, blue accents *(default)* | **Dark Industrial** — deep black, neon green |
| **Forge** — white, green accents | **Dracula** — purple, pink, cyan on charcoal |
| **GitHub Light** — GitHub's familiar white/blue | **GitHub Dark** — GitHub's dark mode, charcoal/blue/green |
| **Gruvbox Light** — paper-warm, earthy tones | **Gruvbox Dark** — warm retro browns and oranges |
| **Lavender** — soft purple, violet accents | **Monokai** — warm yellows/greens on deep black |
| **Rosé Pine Dawn** — soft blush, muted purples | **Nord Dark** — arctic blue palette |
| **Sand** — warm beige, amber tones | **Solarized Dark** — Ethan Schoonover's classic |
| **Solarized Light** — the light companion | **Tokyo Night** — deep blue with neon cyan/purple |

20 themes total — each covers: surfaces, borders, text hierarchy, all SQL token colors (12 types), editor chrome, autocomplete, inline editing states, scrollbars, badges, modals, cell types, form inputs, buttons, panel sections, EXPLAIN badges, trigger badges, FK links, danger zones, and zebra striping.

Switch themes from the header dropdown — organized into Light and Dark groups, alphabetically sorted. Saved to cookie. Takes effect immediately, no page reload.

### Font Control

5 independently configurable font zones in Settings:

| Zone | CSS Variable | Applies To |
|:-----|:------------|:-----------|
| General UI | `--font-body` | Body text, labels, buttons, menus |
| Headings | `--font-heading` | Section titles, page headers |
| Sidebar | `--font-sidebar` | Database and table names |
| Table Data | `--font-data` | Cell values in data tables |
| SQL / Code | `--font-mono` | SQL editor, code blocks, query bar |

**28 curated fonts**: 16 sans-serif (DM Sans, Inter, Nunito Sans, Open Sans, Lato, Roboto, Source Sans 3, Outfit, Sora, Work Sans, Poppins, IBM Plex Sans + system fallbacks) and 12 monospace (JetBrains Mono, Fira Code, Source Code Pro, IBM Plex Mono, Roboto Mono, Inconsolata, Space Mono, Ubuntu Mono + system fallbacks).

Each zone has a live preview panel that updates as you select. Google Fonts are loaded on demand — only the fonts you actually choose are fetched.

### Custom Themes

```
themes/my-theme/
├── theme.json    # { "name": "My Theme", "author": "You", "type": "dark" }
└── style.css     # Override CSS variables
```

Drop a folder, refresh, it appears in the dropdown. The base theme (`dark-industrial/style.css`) defines every variable — your theme only needs to override the ones you want to change. See any existing theme for the reference.

### Zebra Striping

Alternating row backgrounds on all data tables, configurable per theme:

```css
--row-odd:  transparent;
--row-even: rgba(255, 255, 255, 0.015);  /* dark themes */
--row-even: rgba(0, 0, 0, 0.02);         /* light themes */
```

---

## ⌨️ Keyboard Shortcuts

Press `?` anywhere (except when typing in an input) to open the shortcut overlay.

| Key | Context | Action |
|:----|:--------|:-------|
| `?` | Global | Show/hide shortcut overlay |
| `Esc` | Global | Close overlay / cancel edit / dismiss modal |
| `Ctrl+Shift+S` | Global | Focus SQL editor (navigates to SQL tab if not there) |
| `Ctrl+Enter` | SQL editor | Execute query |
| `Tab` | SQL editor | Insert 4 spaces / accept autocomplete |
| `Shift+Tab` | SQL editor | Remove indent |
| `↑` `↓` | Autocomplete | Navigate suggestions |
| `Esc` | Autocomplete | Close dropdown |
| Click cell | Data table | Start inline edit |
| `Enter` | Inline edit | Save cell |
| `Tab` | Inline edit | Save and move to next cell |
| `Ctrl+Enter` | Textarea edit | Save large text cell |
| `Esc` | Inline edit | Cancel without saving |
| `Enter` | Modal | Confirm action |
| `Esc` | Modal | Cancel / dismiss |

---

## 📁 Project Structure

```
dbforge/
├── index.php                    # Router + 12-step security chain
├── config.template.php          # Template for installer
├── install.php                  # 3-step first-run wizard
├── ajax.php                     # All AJAX endpoints (auth + CSRF protected)
├── .htaccess                    # Apache security rules
├── assets/
│   └── logo.svg                 # Project logo
├── includes/
│   ├── Database.php             # PDO wrapper: browse, query, export, import,
│   │                            # FK queries, views, triggers, table ops
│   ├── Auth.php                 # Auth + TOTP 2FA, CSRF, IP whitelist,
│   │                            # brute force, logging
│   ├── TOTP.php                 # Zero-dependency RFC 6238 TOTP implementation
│   ├── favorites.php            # Per-user table favorites (JSON storage)
│   ├── helpers.php              # Theme loader, font system, formatters
│   └── icons.php                # 30+ inline SVG icons + dbforge_logo() helper
├── templates/
│   ├── layout.php               # HTML shell, header, sidebar, tabs, footer,
│   │                            # profile modal (password + 2FA setup)
│   ├── login.php                # Themed login page
│   ├── login_2fa.php            # TOTP verification page
│   ├── sidebar.php              # DB/table/view tree with collapse, filter,
│   │                            # favorites, spacing, counts
│   ├── browse.php               # Server overview / DB overview / data grid +
│   │                            # FK drill-down + row detail + views panel +
│   │                            # inline edit + bulk select + insert row +
│   │                            # create table + table operations
│   ├── structure.php            # Editable columns, FK display, indexes,
│   │                            # CREATE statement, partitions, info panels,
│   │                            # triggers CRUD
│   ├── sql.php                  # SQL editor + EXPLAIN + query history panel
│   ├── search.php               # Search across all tables
│   ├── er.php                   # Interactive ER diagram (SVG + force layout)
│   ├── operations.php           # Alter, rename, move, copy, maintenance,
│   │                            # truncate, drop
│   ├── info.php                 # Table info
│   ├── export.php               # SQL/CSV export with database picker
│   ├── import.php               # SQL/CSV import with drag-drop + preview
│   ├── server_info.php          # Server stats
│   ├── settings.php             # Full settings page + font customization
│   ├── settings_save.php        # Settings save helper (PRG pattern)
│   └── connection_error.php     # Error display
├── js/
│   ├── dbforge.js               # ~2500 lines: tokenizer (612 tokens),
│   │                            # autocomplete, inline edit, bulk select,
│   │                            # modals, CSRF, favorites, sidebar filter,
│   │                            # persistent drafts, mini-editor highlighter,
│   │                            # shortcut overlay
│   └── qr.js                    # QR code generator (qrcode-generator by
│                                # Kazuhiko Arase, MIT license)
├── themes/                      # 20 themes (10 light + 10 dark)
│   ├── dark-industrial/         # Base theme (all CSS defined here)
│   ├── atom-one-light/
│   ├── carbon/
│   ├── catppuccin-latte/
│   ├── catppuccin-mocha/
│   ├── dracula/
│   ├── github-dark/
│   ├── github-light/
│   ├── gruvbox-dark/
│   ├── gruvbox-light/
│   ├── light-clean/             # Default theme
│   ├── light-forge/
│   ├── light-lavender/
│   ├── light-sand/
│   ├── monokai/
│   ├── nord-dark/
│   ├── rose-pine-dawn/
│   ├── solarized-dark/
│   ├── solarized-light/
│   └── tokyo-night/
└── logs/                        # Query audit logs + favorites (web-blocked)
    └── favorites.json           # Per-user starred tables
```

**One external library:** [qrcode-generator](https://github.com/nickmillerdev/qrcode-generator) by Kazuhiko Arase (MIT license, 21KB minified) for TOTP 2FA QR codes. Everything else is written from scratch. No jQuery, no React, no Vue, no Bootstrap, no Tailwind, no CodeMirror, no Monaco, no D3, no Composer, no npm. The entire tool runs offline after the initial Google Fonts load (and even that is optional — it falls back to system fonts).

---

## 📋 Requirements

| | Minimum | Recommended |
|:--|:--------|:------------|
| **PHP** | 7.4 | 8.2+ |
| **MySQL** | 5.7 | 8.0+ |
| **MariaDB** | 10.3 | 10.11+ |
| **Web Server** | Apache 2.4 with `mod_rewrite` | Apache 2.4 |

PDO MySQL extension required (`php-pdo` + `php-mysql`). Sessions must be enabled. `upload_max_filesize` affects import limits.

---

## 🤝 Contributing

1. Fork → branch → commit → PR
2. **Theme contributions** especially welcome — add a `themes/your-theme/` folder
3. Bug reports and feature requests via [Issues](https://github.com/ClearanceClarence/DBForge/issues)

---

## 📄 License

[MIT](LICENSE) — use it anywhere, modify it freely, include it in commercial projects.

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for the full version history.

---

<p align="center">
  <img src="https://raw.githubusercontent.com/ClearanceClarence/DBForge/main/assets/logo.svg" alt="DBForge" width="28">
  <br>
  <sub>Built with PHP, vanilla JS, and zero external dependencies.</sub>
  <br>
  <sub>By developers, for developers.</sub>
</p>
