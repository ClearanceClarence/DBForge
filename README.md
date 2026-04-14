<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/MariaDB-10.3+-003545?style=flat-square&logo=mariadb&logoColor=white" alt="MariaDB">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License">
  <img src="https://img.shields.io/badge/Version-1.0.0-blue?style=flat-square" alt="Version">
</p>

<h1 align="center">
  <br>
  <img src="https://raw.githubusercontent.com/ClearanceClarence/DBForge/main/assets/logo.svg" alt="DBForge" width="48">
  <br>
  DBForge
  <br>
</h1>

<p align="center">
  <strong>A lightweight, themeable database management tool for local development.</strong>
  <br>
  Built as a modern alternative to phpMyAdmin — zero dependencies, drop-in install, 10 themes.
</p>

<p align="center">
  <a href="#-installation">Installation</a> •
  <a href="#-features">Features</a> •
  <a href="#-themes">Themes</a> •
  <a href="#-sql-editor">SQL Editor</a> •
  <a href="#-configuration">Configuration</a> •
  <a href="#-custom-themes">Custom Themes</a>
</p>

---

## ⚡ Installation

**No Composer. No npm. No build step.** Just drop and go.

```bash
# Clone or download into your web server's document root
git clone https://github.com/ClearanceClarence/DBForge.git /path/to/htdocs/dbforge

# Or for XAMPP on Windows:
# Copy the dbforge/ folder to C:\xampp\htdocs\dbforge\
```

1. Make sure **Apache** and **MySQL/MariaDB** are running
2. Open `http://localhost/dbforge/`
3. That's it

> Default credentials: `root` / *(empty password)* — standard XAMPP config.
> Edit `config.php` to change.

---

## ✨ Features

### 📊 Browse & Edit Data

- Paginated data grid with **search**, **sort by any column**, and type-aware cell styling
- **Inline editing** — click any cell to edit, Enter to save, Tab to jump to the next cell
- NULL value support with a dedicated checkbox
- AJAX saves — no page reload, green flash on success, red on error
- **Delete rows** with themed confirmation dialogs (no native browser alerts)

### 🏗️ Table Structure

- Full column definitions with type, nullable, key, default, extra, collation, and comments
- Index viewer showing key names, cardinality, type, and uniqueness
- `SHOW CREATE TABLE` statement with syntax highlighting

### 🖥️ SQL Editor

- **Real-time syntax highlighting** with a custom tokenizer (12 token types)
- **Context-aware autocomplete** — suggests tables after `FROM`, scoped columns after `WHERE`
- **Table/alias recognition** — tracks `FROM`/`JOIN` aliases and highlights them throughout the query
- Line numbers, auto-closing brackets/quotes, Tab indent/dedent
- Keyboard shortcuts: `Ctrl+Enter` execute, `Ctrl+Shift+S` focus editor
- Query results render inline with the same data grid

### 📋 Database Overview

- When you select a database, see **all tables** with row counts, data/index sizes, engine, and collation
- Exact `COUNT(*)` row counts (not InnoDB estimates)
- Per-table quick actions: Browse, Structure, SQL, Export
- Database-level stats: total tables, rows, and disk usage

### 📤 Export

- Single table export as **SQL** (CREATE + INSERT) or **CSV**
- Full database export as a single SQL dump
- Direct download — no temp files

### ⚙️ Server Info

- MySQL/MariaDB version, uptime, connections, traffic stats
- InnoDB buffer pool size, total queries, data directory
- PHP environment: version, SAPI, PDO drivers, memory limit
- All databases listing with table counts and quick actions

### 🎨 10 Built-in Themes

Switch instantly via the header dropdown — your choice is saved in a cookie.

---

## 🎨 Themes

### Light Themes

| Theme | Description |
|:------|:------------|
| **Clean** *(default)* | Crisp white with blue accents — professional and neutral |
| **Forge** | White with green accents — bold and energetic |
| **Sand** | Warm beige with amber accents — easy on the eyes |
| **Lavender** | Soft gray-purple with violet accents — gentle and refined |
| **Solarized Light** | Classic Solarized palette — proven readability |

### Dark Themes

| Theme | Description |
|:------|:------------|
| **Dark Industrial** | Deep black with neon green — the hacker look |
| **Midnight Teal** | Ocean blue with teal highlights — calm and focused |
| **Carbon** | Charcoal gray with VS Code-style blue — clean dark |
| **Nord** | Arctic blues from the Nord palette — muted and balanced |
| **Solarized Dark** | Classic Solarized dark — low contrast for long sessions |

Every theme covers: surfaces, text, borders, all 12 SQL syntax token types, editor chrome, autocomplete dropdown, inline editing states, scrollbars, badges, modal dialogs, and data cell variants.

---

## 🖥️ SQL Editor

The SQL editor features a **custom-built tokenizer** and **transparent textarea overlay** for real-time syntax highlighting.

### Token Types

| Token | Example | Description |
|:------|:--------|:------------|
| `keyword` | `SELECT`, `FROM`, `WHERE` | SQL keywords (bold) |
| `function` | `COUNT()`, `COALESCE()` | Built-in functions + detected calls |
| `type` | `INT`, `VARCHAR`, `JSON` | Data types (italic) |
| `table` | `users`, `u` | Table names & aliases (underlined) |
| `string` | `'hello'` | Single/double quoted strings |
| `number` | `42`, `3.14`, `0xFF` | Integers, decimals, hex, scientific |
| `comment` | `-- note`, `/* block */` | Line and block comments |
| `operator` | `=`, `!=`, `>=`, `<=>` | Comparison and logical operators |
| `identifier` | `column_name` | Column and unresolved names |
| `backtick` | `` `table_name` `` | Backtick-quoted identifiers |
| `variable` | `@var`, `@@global` | User and system variables |
| `constant` | `NULL`, `TRUE` | SQL constants |

### Smart Autocomplete

- Fetches all tables and columns from the current database via AJAX on load
- **Context-aware suggestions:**
  - After `FROM` / `JOIN` → table names
  - After `WHERE` / `AND` / `ORDER BY` → columns **scoped to query tables**
  - After `table.` → columns for that specific table (alias-resolved)
  - After `USE` → database names
  - General typing → keywords + functions + tables
- Keyboard navigation: `↑↓` to select, `Tab`/`Enter` to accept, `Esc` to dismiss
- Shows immediately after `WHERE` / `FROM` without needing to type a character

### Table Recognition

The tokenizer does a **two-pass analysis**:
1. **Extract** — scans `FROM`, `JOIN`, `UPDATE`, `INTO` clauses to find table names and aliases
2. **Re-classify** — marks matching identifiers as `table` tokens throughout the query

This means `u.email` in a `WHERE` clause gets `u` underlined if there's a `FROM users u` earlier in the query.

---

## 🔧 Configuration

Edit `config.php`:

```php
return [
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],
    'app' => [
        'name'          => 'DBForge',
        'version'       => '1.0.0',
        'default_theme' => 'light-clean',  // Any installed theme slug
        'rows_per_page' => 50,
    ],
    'security' => [
        'require_auth' => false,   // Set true for basic auth
        'username'     => 'admin',
        'password'     => 'admin', // Change this!
    ],
];
```

---

## 🎨 Custom Themes

Creating a theme takes two files:

### 1. Create the directory

```
themes/my-theme/
├── theme.json
└── style.css
```

### 2. Add `theme.json`

```json
{
    "name": "My Theme",
    "author": "Your Name",
    "description": "A brief description.",
    "version": "1.0.0",
    "type": "dark"
}
```

### 3. Add `style.css`

Override the CSS custom properties from the base `dark-industrial` theme. You only need to set the variables that differ:

```css
:root {
    --bg-root:        #1a1a2e;
    --bg-panel:       #222240;
    --text-primary:   #e0e0f0;
    --accent:         #e94560;
    /* ... see any existing theme for the full variable list */
}

/* SQL syntax token colors */
.sql-keyword   { color: #e94560; font-weight: 600; }
.sql-function  { color: #0f3460; }
.sql-string    { color: #16c79a; }
/* ... */
```

For **light themes**, you also need to override editor and form input styles to ensure the transparent textarea overlay works correctly. See `themes/light-clean/style.css` for a complete reference.

### 4. Refresh

Your theme auto-registers and appears in the dropdown. No restart needed.

---

## ⌨️ Keyboard Shortcuts

| Shortcut | Context | Action |
|:---------|:--------|:-------|
| `Ctrl+Enter` | SQL Editor | Execute query |
| `Tab` | SQL Editor | Insert 4 spaces |
| `Shift+Tab` | SQL Editor | Remove indent |
| `Ctrl+Shift+S` | Anywhere | Focus SQL editor |
| `Enter` | Autocomplete | Accept suggestion |
| `Tab` | Autocomplete | Accept & move to next |
| `↑` `↓` | Autocomplete | Navigate suggestions |
| `Esc` | Autocomplete / Modal | Dismiss |
| `Enter` | Inline edit | Save cell |
| `Esc` | Inline edit | Cancel edit |
| `Tab` | Inline edit | Save & edit next cell |
| `Enter` | Modal dialog | Confirm action |

---

## 📁 Project Structure

```
dbforge/
├── index.php                    # Router & controller
├── config.php                   # Database & app settings
├── ajax.php                     # AJAX endpoint (autocomplete, cell updates, row deletes)
│
├── includes/
│   ├── Database.php             # PDO wrapper — browse, query, export, exact counts
│   ├── helpers.php              # Theme loader, formatters, cell styling helpers
│   └── icons.php                # Inline SVG icon library (30+ Lucide-style icons)
│
├── templates/
│   ├── layout.php               # HTML shell — header, sidebar slot, tab bar, footer
│   ├── sidebar.php              # Database/table tree with exact row counts
│   ├── browse.php               # Data grid + database overview + inline editing
│   ├── structure.php            # Column definitions, indexes, CREATE statement
│   ├── sql.php                  # SQL editor with syntax highlighting & autocomplete
│   ├── info.php                 # Table metadata cards + danger zone
│   ├── export.php               # SQL/CSV export options
│   ├── server_info.php          # Server stats, PHP env, databases list
│   └── connection_error.php     # Shown when MySQL is unreachable
│
├── js/
│   └── dbforge.js               # SQL tokenizer, highlighter, autocomplete,
│                                 # inline editing, modals, keyboard shortcuts
│
├── themes/                      # 10 built-in themes (5 light + 5 dark)
│   ├── light-clean/             # ← Default theme
│   ├── light-forge/
│   ├── light-sand/
│   ├── light-lavender/
│   ├── solarized-light/
│   ├── dark-industrial/         # Base theme — all CSS defined here
│   ├── midnight-teal/
│   ├── carbon/
│   ├── nord-dark/
│   └── solarized-dark/
│
└── README.md
```

---

## 🔒 Security

> **DBForge is designed for local development environments.**

- All SQL queries use **PDO prepared statements** with parameterized values
- Cell updates and row deletes are scoped by primary key with `LIMIT 1`
- Table/column names are escaped via backtick identifier quoting
- No user input is rendered without `htmlspecialchars()` escaping

If you need to expose DBForge on a network:

1. Enable authentication in `config.php`
2. Restrict access via `.htaccess`:
   ```apache
   # Allow only localhost
   Require ip 127.0.0.1 ::1
   ```
3. Use HTTPS if accessible beyond localhost

---

## 📋 Requirements

| Dependency | Minimum | Recommended |
|:-----------|:--------|:------------|
| PHP | 7.4 | 8.2+ |
| MySQL | 5.7 | 8.0+ |
| MariaDB | 10.3 | 10.11+ |
| Web Server | Apache 2.4 | Apache 2.4 |

Works with **XAMPP**, **WAMP**, **MAMP**, **Laragon**, or any Apache+PHP+MySQL stack. No Composer, npm, or build tools required.

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/my-feature`)
3. Commit your changes (`git commit -am 'Add my feature'`)
4. Push to the branch (`git push origin feature/my-feature`)
5. Open a Pull Request

### Theme Contributions

Community themes are welcome! Submit a PR with your `themes/my-theme/` folder containing `theme.json` and `style.css`. Make sure to cover all CSS variables and SQL token types.

---

## 📄 License

MIT License — see [LICENSE](LICENSE) for details.

---

<p align="center">
  <sub>Built with PHP, vanilla JS, and zero external dependencies.</sub>
</p>
