# Architecture Overview

## 1) Platform and Stack

- Framework: Laravel 10 (PHP 8.1+)
- Frontend approach: Blade + Laravel Mix/Webpack asset pipeline
- DB: MySQL/MariaDB (Eloquent ORM + migrations)
- Module framework: `nwidart/laravel-modules`
- UI data grids: Yajra DataTables
- Import/export: Maatwebsite Excel
- PDF: DomPDF
- Queues/scheduler: Laravel queue + `app/Console/Kernel.php`

This is a large monolithic Laravel app with modular extension packages under `Modules/`.

## 2) Top-Level Repository Shape

- `app/` - core app logic (controllers, models, observers, events, services, jobs, console commands)
- `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`, `storage/`, `tests/` - standard Laravel structure
- `Modules/` - feature modules (Payroll, Purchase, Recruit, Letter, etc.)
- `hostingercode/` - deployment mirror/copy of app tree
- `smhr/` - separate nested Laravel application

## 3) Request-to-Response Flow (Typical)

1. Route enters via `routes/web.php` (plus `web-public.php`, `web-settings.php`, `api.php`)
2. Middleware/auth/permission checks run
3. Controller method executes business flow
4. Eloquent models/repositories/services query/update DB
5. Observers/listeners trigger side effects (notifications, derived updates, logs)
6. Response returns Blade view/JSON/file download

## 4) Core App Layers

### Controllers

- Path: `app/Http/Controllers/`
- Very broad controller surface (CRM + pharma + finance + HR)
- Includes domain-specific reports, imports, and operational endpoints

### Models

- Path: `app/Models/`
- Central business entities: users, clients, leads/deals, invoices/payments, projects/tasks, attendance, leaves, pharma entities, etc.

### Views

- Path: `resources/views/`
- Blade-heavy UI with substantial domain-specific screens
- Pharma-specific views are deeply integrated in core app views

### DataTables

- Path: `app/DataTables/` and module DataTables folders
- Server-side table filtering/sorting is a major interaction layer

### Services

- Path: `app/Services/`
- Used selectively for complex or reusable domain logic (reporting, area sync, duplicate merge, etc.)

### Events, Listeners, Observers

- Paths: `app/Events/`, `app/Listeners/`, `app/Observers/`
- Important side-effect pipeline for notifications and state updates
- Observer registrations are centralized in provider mappings

### Jobs and Commands

- `app/Jobs/` - mainly import/background workflows
- `app/Console/Commands/` - many scheduled and maintenance operations

## 5) Module Layer (`Modules/`)

Active/important modules include:

- Payroll
- Purchase
- Recruit
- Letter
- Asset
- Zoom
- QRCode
- EInvoice
- CyberSecurity
- SFC
- UniversalBundle (contains additional sub-modules like RestAPI, Webhooks, SMS, etc.)

Each module generally contains:

- `Http/Controllers`
- `Entities`
- `DataTables`
- `Resources/views`
- `Routes`
- `Database/migrations`
- sometimes `Observers`, `Listeners`, `Notifications`, `Console`, `Exports`, `Imports`

## 6) Architectural Characteristics

- Highly feature-rich monolith with many direct DB interactions in controllers and DataTables
- Heavy reliance on server-rendered pages and AJAX endpoints
- Large observer/listener network means behavior can be distributed across multiple files
- Domain-specific customizations (especially pharma and payroll) are deeply embedded, not isolated into a single bounded context

## 7) Navigation Strategy for Developers

When debugging a feature:

1. Find route in `routes/` or module `Routes/web.php`
2. Open controller method
3. Check related DataTable/export/service/request class
4. Inspect model observers/listeners for side effects
5. Inspect Blade view and JS hooks under `resources/views`
6. Validate schema/migrations for expected columns

This workflow is critical in this codebase because logic is often distributed across controller + DataTable + observer + view.
