# Docker Quick Start

Run DBForge with MySQL in one command:

```bash
git clone https://github.com/ClearanceClarence/DBForge.git
cd DBForge
docker-compose up -d
```

Open **http://localhost:8080/dbforge/** in your browser.

## Installer Settings

When the installer asks for database credentials, use:

| Field    | Value              |
|:---------|:-------------------|
| Host     | `db`               |
| Port     | `3306`             |
| Username | `root`             |
| Password | `dbforge_root_pass`|

Or use the non-root user:

| Field    | Value          |
|:---------|:---------------|
| Host     | `db`           |
| Port     | `3306`         |
| Username | `dbforge`      |
| Password | `dbforge_pass` |

## Ports

- **8080** → DBForge web interface
- **3307** → MySQL (for external tools like MySQL Workbench)

## Persistence

Data persists across restarts via Docker volumes:
- `mysql-data` — database files
- `dbforge-config` — your config.php
- `dbforge-logs` — query logs, favorites, saved queries, ER layouts

## Commands

```bash
# Start
docker-compose up -d

# Stop
docker-compose down

# View logs
docker-compose logs -f dbforge

# Rebuild after updating
docker-compose build --no-cache
docker-compose up -d

# Reset everything (deletes all data)
docker-compose down -v
```

## Custom MySQL Password

Edit `docker-compose.yml` and change `MYSQL_ROOT_PASSWORD`, `MYSQL_USER`, and `MYSQL_PASSWORD` before first run. If you've already run it, reset with `docker-compose down -v` first.

## Connecting to an External Database

If you already have MySQL/MariaDB running elsewhere, you don't need the `db` service. Use only the `dbforge` service and point the installer at your existing database host.
