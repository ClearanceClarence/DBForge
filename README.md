<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/MariaDB-10.3+-003545?style=flat-square&logo=mariadb&logoColor=white" alt="MariaDB">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License">
  <img src="https://img.shields.io/badge/Version-1.2.0-blue?style=flat-square" alt="Version">
</p>

<h1 align="center">DBForge</h1>

<p align="center">
  <strong>A lightweight, production-ready database management tool.</strong>
  <br>
  Modern alternative to phpMyAdmin — zero dependencies, drop-in install, 10 themes, full auth system.
</p>

<p align="center">
  <a href="#-installation">Installation</a> •
  <a href="#-features">Features</a> •
  <a href="#-themes">Themes</a> •
  <a href="#-sql-editor">SQL Editor</a> •
  <a href="#-security">Security</a> •
  <a href="#-custom-themes">Custom Themes</a> •
  <a href="CHANGELOG.md">Changelog</a>
</p>

---

## ⚡ Installation

**No Composer. No npm. No build step.** Drop and go.

```bash
git clone https://github.com/ClearanceClarence/DBForge.git /path/to/htdocs/dbforge
```

1. Make sure **Apache** and **MySQL/MariaDB** are running
2. Open `http://localhost/dbforge/`
3. The **setup wizard** runs automatically on first visit:
   - **Step 1** — Database connection (tests the connection before proceeding)
   - **Step 2** — Admin account (username + password, bcrypt hashed, no defaults)
   - **Step 3** — Done. `config.php` is generated, you're redirected to login

No default credentials. No config file to edit manually. The installer handles everything.

---

## ✨ Features

### 📊 Browse & Edit Data

- Paginated data grid with **search**, **sort by any column**, and type-aware cell styling
- **Inline editing** — click any cell to edit, Enter to save, Tab to next cell, Escape to cancel
- NULL value support with a dedicated checkbox in the edit popover
- AJAX saves — no page reload, green flash on success, red on error
- **Bulk selection** — checkbox column with select-all (indeterminate state), bulk action bar shows count, "Delete Selected" sends a single `DELETE ... WHERE IN` query
- **Column type toggle** — hide/show column types under headers, persisted to cookie
- **Row delete** with themed confirmation modals (no native browser dialogs)

### 🏗️ Table Structure

- **Editable columns** — click pencil to edit name, type, nullable, default, extra, and comment inline. Save runs `ALTER TABLE ... CHANGE`
- **Searchable type dropdown** — native datalist with 35+ common MySQL types (INT, VARCHAR(255), DECIMAL(10,2), JSON, ENUM, etc.) — type to filter or pick from suggestions
- **Add/drop columns** — add with name, type, null, default, comment, and position (FIRST / AFTER x / end). Drop with confirmation modal
- **AUTO_INCREMENT** — shows current next-ID value with Set (any value) and Reset (MAX+1) buttons
- **Primary key indicators** — gold left border + key icon on PK rows
- **Foreign key visualization** — blue left border + link icon on FK rows, clickable badges linking to referenced table
- **Foreign Keys table** — constraint name, local column → referenced table.column, ON DELETE/ON UPDATE with color-coded badges (CASCADE=red, RESTRICT=gray, SET NULL=amber)
- **Referenced By table** — reverse FK lookup showing which tables reference this one
- **Syntax-highlighted CREATE statement** — tokenized with the same engine as the SQL editor
- Index viewer with key names, cardinality, type, and uniqueness

### 🖥️ SQL Editor

- **Real-time syntax highlighting** with a custom tokenizer (12 token types)
- **Context-aware autocomplete** — tables after `FROM`, scoped columns after `WHERE`, dot-notation alias resolution
- **Table/alias recognition** — two-pass analysis tracks `FROM`/`JOIN` aliases throughout the query
- Line numbers, auto-closing brackets/quotes, Tab indent/Shift+Tab dedent
- Keyboard shortcuts: `Ctrl+Enter` execute, `Ctrl+Shift+S` focus editor
- Query results render inline with the same data grid
- Quick query links auto-execute via `&run=1` (URL cleaned after load)

### 📋 Server & Database Overview

- **Home page dashboard** — phpMyAdmin-style server overview with all databases in a table (table count, rows, data/index/total size, collation, per-row actions)
- **Database overview** — when a DB is selected but no table, shows all tables with stats and quick actions
- **Server page** — grouped sections: MySQL Server (version, hostname, charset, data dir), Performance (uptime, connections, queries, InnoDB buffer, traffic), PHP Environment (version, OS, SAPI, PDO drivers, limits)

### 📤 Export

- **Table export** — SQL dump (CREATE + INSERT) or CSV with column headers
- **Database export** — full SQL dump of all tables with CREATE DATABASE + USE statements
- **Export page** — shows file size estimates, row counts, and per-table export buttons for the whole database
- Works with or without a table selected

### ℹ️ Table Info

- Header card with table name, engine, collation, and key stats (rows, columns, indexes, total size)
- Grouped sections: Properties, Storage, Timestamps
- Quick query links
- **Danger Zone** — Truncate and Drop with full descriptions, exact row count in confirmation, and prominent buttons

### ⚙️ Settings

- **Full config editor** — database connection, default theme, rows per page, export toggle
- **Auth controls** — require login, CSRF, force HTTPS, read-only mode, query logging
- **User management** — add/remove users, change passwords (bcrypt), minimum length enforcement
- **Access control** — IP whitelist (CIDR support), hidden databases
- All changes write directly to `config.php`

### 🎨 10 Built-in Themes

Switch instantly via the header dropdown — saved to cookie.

---

## 🎨 Themes

### Light Themes

| Theme | Description |
|:------|:------------|
| **Clean** *(default)* | Crisp white with blue accents |
| **Forge** | White with green accents |
| **Sand** | Warm beige with amber accents |
| **Lavender** | Soft gray-purple with violet accents |
| **Solarized Light** | Classic Solarized palette |

### Dark Themes

| Theme | Description |
|:------|:------------|
| **Dark Industrial** | Deep black with neon green |
| **Midnight Teal** | Ocean blue with teal highlights |
| **Carbon** | Charcoal gray with VS Code-style blue |
| **Nord** | Arctic blues from the Nord palette |
| **Solarized Dark** | Classic Solarized dark |

---

## 🖥️ SQL Editor

### Token Types

| Token | Example | Description |
|:------|:--------|:------------|
| `keyword` | `SELECT`, `FROM`, `WHERE` | SQL keywords (bold) |
| `function` | `COUNT()`, `COALESCE()` | Built-in functions |
| `type` | `INT`, `VARCHAR`, `JSON` | Data types (italic) |
| `table` | `users`, `u` | Table names & aliases (underlined) |
| `string` | `'hello'` | Quoted strings |
| `number` | `42`, `3.14`, `0xFF` | Numeric literals |
| `comment` | `-- note`, `/* block */` | Comments |
| `operator` | `=`, `!=`, `>=` | Operators |
| `identifier` | `column_name` | Column names |
| `backtick` | `` `table` `` | Backtick-quoted identifiers |
| `variable` | `@var`, `@@global` | User and system variables |
| `constant` | `NULL`, `TRUE` | SQL constants |

### Autocomplete

- After `FROM` / `JOIN` → table names
- After `WHERE` / `AND` / `ORDER BY` → columns scoped to query tables
- After `table.` → columns for that table (alias-resolved)
- After `USE` → database names
- Keyboard: `↑↓` navigate, `Tab`/`Enter` accept, `Esc` dismiss

---

## 🔒 Security

Full production security stack. Everything is opt-in — local dev works with zero config.

| # | Feature | Config |
|:--|:--------|:-------|
| 1 | **Security headers** — X-Content-Type-Options, X-Frame-Options, XSS, HSTS | Always on |
| 2 | **HTTPS enforcement** — 301 redirect | `force_https` |
| 3 | **IP whitelist** — CIDR support | `ip_whitelist` |
| 4 | **Session hardening** — httponly + secure + samesite, ID regeneration, timeout | `session_lifetime` |
| 5 | **Authentication** — themed login page, multi-user, bcrypt | `require_auth` |
| 6 | **Brute force protection** — IP lockout after N attempts | `max_login_attempts` |
| 7 | **CSRF tokens** — all forms and AJAX | `csrf_enabled` |
| 8 | **Read-only mode** — blocks all write queries | `read_only` |
| 9 | **Hidden databases** — filtered everywhere | `hidden_databases` |
| 10 | **Query logging** — timestamp, user, DB, IP, duration | `query_log` |
| 11 | **`.htaccess`** — blocks config.php, includes/, logs/ | Always on |

### Production Setup

```php
'security' => [
    'require_auth'    => true,
    'users' => [
        // php -r "echo password_hash('mypassword', PASSWORD_BCRYPT);"
        'admin' => '$2y$10$your_bcrypt_hash_here',
    ],
    'force_https'      => true,
    'ip_whitelist'     => ['192.168.1.0/24'],
    'hidden_databases' => ['information_schema', 'performance_schema', 'mysql', 'sys'],
    'query_log'        => true,
],
```

---

## 🎨 Custom Themes

### 1. Create directory

```
themes/my-theme/
├── theme.json
└── style.css
```

### 2. `theme.json`

```json
{
    "name": "My Theme",
    "author": "Your Name",
    "description": "A brief description.",
    "version": "1.0.0",
    "type": "dark"
}
```

### 3. `style.css`

Override CSS custom properties from `dark-industrial`. You only need to set variables that differ. See any existing theme for reference.

### 4. Refresh

Your theme auto-registers and appears in the dropdown.

---

## ⌨️ Keyboard Shortcuts

| Shortcut | Context | Action |
|:---------|:--------|:-------|
| `Ctrl+Enter` | SQL Editor | Execute query |
| `Tab` | SQL Editor | Insert 4 spaces |
| `Shift+Tab` | SQL Editor | Remove indent |
| `Ctrl+Shift+S` | Anywhere | Focus SQL editor |
| `Enter` | Autocomplete | Accept suggestion |
| `Tab` | Autocomplete | Accept & move next |
| `↑` `↓` | Autocomplete | Navigate suggestions |
| `Esc` | Autocomplete / Modal | Dismiss |
| `Enter` | Inline edit / Modal | Save / Confirm |
| `Esc` | Inline edit | Cancel edit |
| `Tab` | Inline edit | Save & edit next cell |

---

## 📁 Project Structure

```
dbforge/
├── index.php                    # Router with security chain
├── config.template.php          # Template used by installer
├── install.php                  # First-run setup wizard
├── ajax.php                     # AJAX endpoint (auth + CSRF protected)
├── .htaccess                    # Apache security rules
│
├── includes/
│   ├── Database.php             # PDO wrapper, FK queries, exact counts
│   ├── Auth.php                 # Auth, CSRF, IP whitelist, rate limiting
│   ├── helpers.php              # Theme loader, formatters
│   └── icons.php                # 30+ inline SVG icons
│
├── templates/
│   ├── layout.php               # HTML shell, header, sidebar, tab bar
│   ├── login.php                # Themed login page
│   ├── sidebar.php              # Database/table tree
│   ├── browse.php               # Data grid, server/DB overview, bulk select
│   ├── structure.php            # Editable columns, FK viz, CREATE highlight
│   ├── sql.php                  # SQL editor with highlighting & autocomplete
│   ├── info.php                 # Table info, danger zone
│   ├── export.php               # SQL/CSV export
│   ├── server_info.php          # Server & PHP stats
│   ├── settings.php             # Full config editor
│   └── connection_error.php     # Shown when MySQL is down
│
├── js/dbforge.js                # Tokenizer, highlighter, autocomplete,
│                                # inline edit, bulk select, modals, CSRF
│
├── logs/                        # Query audit logs (web-blocked)
│
└── themes/                      # 10 themes (5 light + 5 dark)
    ├── light-clean/             # Default
    └── ...9 more
```

---

## 📋 Requirements

| Dependency | Minimum | Recommended |
|:-----------|:--------|:------------|
| PHP | 7.4 | 8.2+ |
| MySQL | 5.7 | 8.0+ |
| MariaDB | 10.3 | 10.11+ |
| Web Server | Apache 2.4 | Apache 2.4 |

Works with **XAMPP**, **WAMP**, **MAMP**, **Laragon**, or any Apache+PHP+MySQL stack.

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-feature`)
3. Commit your changes (`git commit -am 'Add my feature'`)
4. Push to the branch (`git push origin feature/my-feature`)
5. Open a Pull Request

Community themes are welcome — submit a PR with your `themes/my-theme/` folder.

---

## 📄 License

MIT License — see [LICENSE](LICENSE) for details.

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.

---

<p align="center">
  <sub>Built with PHP, vanilla JS, and zero external dependencies.</sub>
</p>
