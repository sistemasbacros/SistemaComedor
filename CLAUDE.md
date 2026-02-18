# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Project Is

**Sistema Comedor** — a PHP-based employee dining management system for BacroCorp. Handles meal ordering, kitchen operations, cancellations, QR check-in, and reporting. Deployed via Docker on Windows self-hosted GitHub Actions runners.

## Stack

- **Backend**: PHP 8.2-FPM with Microsoft SQL Server (`sqlsrv` extension)
- **Web Server**: Nginx (Alpine)
- **Database**: Microsoft SQL Server (multiple databases)
- **Containerization**: Docker + Docker Compose
- **Frontend**: HTML/CSS/JS (jQuery, DataTables, ECharts) — inline in PHP files
- **CI/CD**: GitHub Actions deploying to self-hosted Windows runners

## Common Commands

### Local Development (Docker)

```bash
# Start test environment
docker compose -f docker-compose.test.yml up --build -d

# Start production environment
docker compose up --build -d

# Stop containers
docker compose down

# View PHP logs
docker compose logs comedor_php

# View Nginx logs
docker compose logs comedor_nginx

# Check health
docker compose ps
```

### Deployment

Deployment is automatic via GitHub Actions:
- Push to `main` → deploys to production (`C:\deploy\ComedorProduccion`, port 80)
- Push to `dev` → deploys to test (`C:\deploy\ComedorTest`, port 8080)

The production `.env` must exist at `C:\deploy\ComedorProduccion\.env` **before** the first deploy (it's never overwritten by CI). The test environment copies `.env.example` automatically if `.env` is missing.

## Environment Configuration

Copy `.env.example` to `.env` and fill in values. The `.env` file is never committed. Key variables:

```env
APP_ENV=production        # or: development
HTTP_PORT=80
DB_COMEDOR_SERVER=...
DB_COMEDOR_DATABASE=...
DB_COMEDOR_USERNAME=...
DB_COMEDOR_PASSWORD=...
# Plus: DB_ALQUIMISTA_*, DB_BASENEW_*, DB_KPI_*, DB_TICKET_*
```

## Architecture

### Database Connections

All DB connections must use the centralized config — **never hardcode credentials**:

```php
require_once __DIR__ . '/config/database.php';
$conn = getComedorConnection();       // Primary DB
$conn = getAlquimistaConnection();    // Alquimista2024
$conn = getBaseNuevaConnection();     // BaseNueva
$conn = getKpiConnection();           // KPI
$conn = getTicketConnection();        // Ticket
```

Always close connections explicitly:
```php
closeConnection($conn, $stmt);  // helper in config/database.php
```

Raw `sqlsrv_*` functions are used directly — no ORM.

### Session / Auth Pattern

```php
session_start();
// After login:
session_regenerate_id(true);
$_SESSION['user_id'] = ...;
$_SESSION['logged_in'] = true;
$_SESSION['LOGIN_TIME'] = time();
$_SESSION['browser_fingerprint'] = md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
```

Role-based access is checked via `$_SESSION['Area']` (e.g., `DIRECCIÓN`, `COCINA`).

### API Client

`/api/Api.php` is the unified HTTP client for communicating with the Node.js backend service. Use it via static facades:

```php
require_once __DIR__ . '/api/Api.php';
$result = Api::pedidos()->misPedidos();
$result = Api::auth()->login($user, $pass);
```

Auto-detects environment (LOCAL → `localhost:3000`, DESARROLLO → `desarollo-bacros:3000`, PRODUCCION → `host.docker.internal:3000`).

### Page Organization

- **Login/Auth**: `Login2.php`, `LoginFormCancel.php`, `LoginValidarOrdenes.php`
- **Orders**: `AgendaPedidos.php`, `Menpedidos.php`, `aparta_consumo_modificado.php`
- **Kitchen**: `dchef.php`, `CocinaTotalPedidos.php`, `MenComprasCocina.php`
- **Cancellations**: `FormatCancel.php`, `FormCanAprobUpdate.php`, `check_pending_cancelations.php`
- **Reports/KPIs**: `KPI_anacomp.php`, `Consultadedatos.php`, `Desglosechecador.php`
- **Admin**: `Admiin.php`, `gestusu.php`
- **QR**: `GenerarQR.php`, `demolecturaQR.php`

### Deprecated Code

Files in `/deprecated/` are legacy API clients replaced by `/api/Api.php`. Do not use or modify them.

## Key Conventions

- **No Composer** — dependencies are not managed via Composer; all libraries are included manually or via CDN.
- **SQL patterns**: Uses SQL Server-specific syntax — `UNPIVOT` for transposing meal rows, CTEs, `CONVERT(DATE, ...)`, `DATEADD()`.
- **Security headers** are set manually at the top of most pages (`Cache-Control: no-store`, `X-Content-Type-Options: nosniff`).
- **Migrations**: If migrating a file from hardcoded DB credentials to `.env`, use scripts in `/scripts/migrate-*.php` as reference.
- **Timezone**: `America/Mexico_City` (set in `php/custom.ini` and Nginx config).
- **PHP limits**: 256MB memory, 300s max execution, 20MB upload (configured in `php/custom.ini`).
