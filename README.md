<p align="center">
  <img src="https://raw.githubusercontent.com/ClearanceClarence/DBForge/assets/logo.svg" alt="DBForge" width="80">
</p>

<h1 align="center">DBForge</h1>

<p align="center">
  <strong>The database tool phpMyAdmin should have been.</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Version-1.3.0-4ade80?style=flat-square" alt="Version 1.3.0">
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
  <a href="#-get-started-in-60-seconds">Get Started</a> · 
  <a href="#-features">Features</a> · 
  <a href="#-security">Security</a> · 
  <a href="#-theming">Theming</a> · 
  <a href="CHANGELOG.md">Changelog</a>
</p>

---

## 💡 Why DBForge?

phpMyAdmin is everywhere, but it hasn't evolved. The UI is cluttered, the SQL editor is barebones, and deploying it securely is a chore. We've all used it because there was nothing better that was just as easy to install.

**DBForge changes that.**

| | phpMyAdmin | DBForge |
|:-|:-----------|:--------|
| **Install** | Config files, blowfish secret, download + extract | Drop folder, open browser, 3-step wizard |
| **SQL Editor** | Syntax coloring only | Real-time highlighting, autocomplete, alias tracking |
| **Edit Data** | Full page reloads | Click any cell → edit inline → AJAX save |
| **Auth** | HTTP Basic or cookie | Bcrypt passwords, brute force protection, IP whitelist, CSRF |
| **Themes** | 3 color options | 10 polished themes, custom theme API, per-zone font control |
| **Bulk Operations** | One row at a time | Checkbox select → bulk delete with `WHERE IN` |
| **Table Management** | Forms scattered across pages | Visual Create Table, structure editor, FK visualization — all inline |
| **Import** | Works, but ugly | Drag-and-drop SQL/CSV with live results, target mode selector |
| **Dependencies** | Requires Composer on newer versions | Zero. Pure PHP + vanilla JS |

---

## 🚀 Get Started in 60 Seconds

```bash
git clone https://github.com/ClearanceClarence/DBForge.git
```

Copy the `dbforge/` folder to your web root and open it in a browser.

**Step 1** — Enter your database credentials (auto-tests the connection)  
**Step 2** — Create an admin account (bcrypt-hashed, no defaults)  
**Step 3** — You're in  

Works with **XAMPP**, **WAMP**, **MAMP**, **Laragon**, or any Apache + PHP + MySQL stack.

---

## ✨ Features

### SQL Editor — Not a Textarea

A real code editor built from scratch. Custom tokenizer with 12 token types. Context-aware autocomplete that knows your tables, columns, and aliases. Two-pass analysis tracks `FROM`/`JOIN` aliases throughout the query and highlights them everywhere they appear.

| Token | Styled As | Example |
|:------|:----------|:--------|
| `keyword` | Bold | `SELECT`, `FROM`, `WHERE`, `JOIN` |
| `function` | Colored | `COUNT()`, `COALESCE()`, `NOW()` |
| `type` | Italic | `INT`, `VARCHAR`, `JSON` |
| `table` | Underlined | `users`, `u` (auto-detected aliases) |
| `string` | Green | `'hello world'` |
| `number` | Cyan | `42`, `3.14`, `0xFF` |
| `comment` | Dimmed | `-- note`, `/* block */` |

Autocomplete is context-aware: tables after `FROM`, scoped columns after `WHERE`, dot-notation resolution for `u.email` when there's a `FROM users u`. Keyboard: `↑↓` to navigate, `Tab` to accept, `Ctrl+Enter` to execute.

### Browse & Edit — No Page Reloads

Click any cell to edit. Enter saves via AJAX. Tab moves to the next cell. NULL checkbox. Green flash on success, red on error. The page never reloads.

Large text? The editor auto-detects content over 60 characters and switches from `<input>` to a resizable `<textarea>`. `Ctrl+Enter` saves in textarea mode.

**Bulk operations:** checkbox column with select-all (indeterminate state). Select 5 rows, click "Delete Selected" — one `DELETE ... WHERE IN (?,?,?,?,?)` query. Confirmation modal, not a native `confirm()`.

### Insert Rows — Without Writing SQL

Click "Insert Row" on any table. The form auto-generates from your column definitions:

- `INT` → number input
- `DATE` → date picker
- `DATETIME` → datetime-local picker
- `ENUM('a','b','c')` → dropdown with parsed values
- `TEXT` / `JSON` / `BLOB` → textarea
- `AUTO_INCREMENT` → skipped (with "Manual" override)

NULL checkbox per nullable column. "Insert another" keeps the form open for batch entry.

### Structure Editor — Visual DDL

Edit any column inline: rename, change type (searchable dropdown of 35+ MySQL types), toggle nullable, set default, change comment. Save runs `ALTER TABLE ... CHANGE`.

Add columns with position control (FIRST / AFTER / end). Drop with confirmation.

**Foreign key visualization:** gold/blue left borders on PK/FK rows, clickable badges linking to referenced tables, dedicated FK and Referenced-By tables with color-coded ON DELETE/ON UPDATE badges (CASCADE = red, RESTRICT = gray).

**AUTO_INCREMENT controls:** shows current next-ID with Set (any value) and Reset (MAX+1) buttons.

**CREATE statement** with full syntax highlighting — same tokenizer as the SQL editor.

### Create Tables & Databases — Visually

**Create Table:** define columns with name, type, nullable, default, PK, AI, unique, and index checkboxes. Live SQL preview with syntax highlighting updates as you type. Engine, collation, and comment options.

**Create Database:** form with name, character set, collation. Also accessible from the sidebar "+" button on any page (opens a modal without leaving your current view).

### Import & Export — Both Directions

**Import SQL:** upload `.sql` dumps. Custom parser splits on `;` respecting strings and comments. Choose "Into existing database" or "New database from file" for full dumps with `CREATE DATABASE`. Per-statement results with error details.

**Import CSV:** upload into any table with configurable delimiter, enclosure, header detection. Database and table dropdowns with dynamic AJAX loading.

**Export:** single table as SQL or CSV, or full database dump. Per-table export buttons. File size estimates on every download link.

**Drag-and-drop** on all file uploads.

### Settings — Everything in One Place

Database connection, default theme, rows per page, export toggle. Auth controls: require login, CSRF, HTTPS, read-only mode, query logging. User management with bcrypt passwords. IP whitelist with CIDR. Hidden databases.

**Font customization** across 5 zones: General UI, Headings, Sidebar, Table Data, SQL/Code. Curated catalog of 28 fonts (16 sans, 12 mono) with live preview. Google Fonts loaded on demand.

All changes write to `config.php`. No manual file editing needed.

---

## 🔒 Security

Not an afterthought. DBForge ships with an 11-step security chain — all opt-in so local dev stays frictionless.

| # | Layer | What It Does |
|:--|:------|:-------------|
| 1 | **Security headers** | X-Content-Type-Options, X-Frame-Options, HSTS, Permissions-Policy |
| 2 | **HTTPS enforcement** | 301 redirect from HTTP |
| 3 | **IP whitelist** | Block by IP or CIDR range before anything loads |
| 4 | **Session hardening** | httponly + secure + samesite cookies, periodic ID rotation, idle timeout |
| 5 | **Authentication** | Themed login page, multi-user, bcrypt hashes |
| 6 | **Brute force** | IP lockout after N failed attempts |
| 7 | **CSRF tokens** | Every form, every AJAX request |
| 8 | **Read-only mode** | Blocks all writes at the query level |
| 9 | **Hidden databases** | Filtered from sidebar, autocomplete, and URL access |
| 10 | **Query logging** | Every query → timestamp, user, database, IP, duration |
| 11 | **`.htaccess`** | Blocks config.php, includes/, logs/ from the web |

**First-run installer** means no default `admin/admin` ever exists. Passwords are bcrypt-hashed from the start.

```php
// Production config example
'security' => [
    'require_auth'    => true,
    'users'           => ['admin' => '$2y$10$...your_bcrypt_hash...'],
    'force_https'     => true,
    'ip_whitelist'    => ['192.168.1.0/24'],
    'hidden_databases'=> ['information_schema', 'performance_schema', 'mysql', 'sys'],
    'query_log'       => true,
],
```

---

## 🎨 Theming

### 10 Built-In Themes

| Light | Dark |
|:------|:-----|
| **Clean** — crisp white, blue accents *(default)* | **Dark Industrial** — deep black, neon green |
| **Forge** — white, green accents | **Midnight Teal** — ocean blue, teal |
| **Sand** — warm beige, amber | **Carbon** — charcoal, VS Code blue |
| **Lavender** — soft purple, violet | **Nord** — arctic blues |
| **Solarized Light** | **Solarized Dark** |

Every theme covers: surfaces, text, borders, all 12 SQL token types, editor chrome, autocomplete, inline editing states, scrollbars, badges, modals, data cell variants, and form inputs.

### Custom Themes

```
themes/my-theme/
├── theme.json    # Name, author, type (dark/light)
└── style.css     # Override CSS variables — that's it
```

Drop a folder, refresh the page, it appears in the dropdown. See any existing theme for the variable reference.

### Font Control

5 independently configurable font zones. 28 curated fonts. Live preview in settings. Google Fonts auto-loaded. System font fallbacks included.

---

## ⌨️ Keyboard Shortcuts

| Key | Action |
|:----|:-------|
| `Ctrl+Enter` | Execute SQL query |
| `Tab` / `Shift+Tab` | Indent / dedent in SQL editor |
| `Ctrl+Shift+S` | Focus SQL editor from anywhere |
| `Enter` | Save inline edit / confirm modal |
| `Esc` | Cancel edit / dismiss modal / close autocomplete |
| `Tab` | Accept autocomplete / save & next cell |
| `↑` `↓` | Navigate autocomplete suggestions |

---

## 📁 What's in the Box

```
dbforge/
├── index.php               # Router + 11-step security chain
├── install.php             # First-run setup wizard
├── ajax.php                # All AJAX endpoints (auth + CSRF)
├── .htaccess               # Apache security rules
├── includes/
│   ├── Database.php        # PDO wrapper, FK queries, import/export
│   ├── Auth.php            # Auth, CSRF, IP whitelist, rate limiting
│   ├── helpers.php         # Themes, fonts, formatters
│   └── icons.php           # 30+ inline SVG icons + logo
├── templates/              # 12 PHP templates
├── js/dbforge.js           # ~1800 lines: tokenizer, autocomplete,
│                           # inline edit, bulk select, modals, CSRF
├── themes/                 # 10 themes (5 light + 5 dark)
├── assets/logo.svg         # Project logo
└── logs/                   # Query audit logs (web-blocked)
```

**Zero external dependencies.** No jQuery, no React, no Bootstrap, no Tailwind, no Composer, no npm. Every line is in the repo.

---

## 📋 Requirements

| | Minimum | Recommended |
|:--|:--------|:------------|
| **PHP** | 7.4 | 8.2+ |
| **MySQL** | 5.7 | 8.0+ |
| **MariaDB** | 10.3 | 10.11+ |
| **Web Server** | Apache 2.4 | Apache 2.4 |

Also works with Nginx (see [Security docs](#-security) for config).

---

## 🤝 Contributing

1. Fork → branch → commit → PR
2. **Theme contributions** welcome — just add a `themes/my-theme/` folder
3. Bug reports and feature requests via Issues

---

## 📄 License

[MIT](LICENSE) — use it anywhere, modify it freely.

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
