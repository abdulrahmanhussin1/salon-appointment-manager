# System Overview — Salon Appointment Manager

## Application Purpose

A multi-branch business management system designed for salons, spas, beauty businesses, and clinics. It covers staff management, service catalog, product inventory, appointment booking, point-of-sale (invoicing), supplier purchasing, customer deposits, and financial reporting.

---

## A. Repository Inventory

### Framework & Runtime
| Component | Value |
|---|---|
| PHP | 8.5.x (tested on 8.5.10) |
| Requirement in composer.json | `^8.1` |
| Laravel Framework | 10.48.25 (`^10.10`) |
| Application Name | Salon Appointment Manager |

### PHP Dependencies
| Package | Version | Purpose |
|---|---|---|
| `laravel/framework` | ^10.10 | Core framework |
| `laravel/sanctum` | ^3.3 | API token authentication |
| `laravel/tinker` | ^2.8 | REPL |
| `livewire/livewire` | ^3.0 | Reactive UI components |
| `spatie/laravel-permission` | ^6.7 | Roles & permissions (RBAC) |
| `yajra/laravel-datatables` | ^10.1 | Server-side DataTables |
| `barryvdh/laravel-dompdf` | ^3.0 | PDF generation |
| `guzzlehttp/guzzle` | ^7.2 | HTTP client |
| `realrashid/sweet-alert` | ^7.2 | Flash alert notifications |

### Dev Dependencies
| Package | Version | Purpose |
|---|---|---|
| `laravel/breeze` | ^1.29 | Auth scaffolding (used to generate auth) |
| `laravel/telescope` | ^5.2 | Local request debugging |
| `laravel/pint` | ^1.0 | Code style fixer |
| `phpunit/phpunit` | ^10.1 | Testing |
| `fakerphp/faker` | ^1.9.1 | Test data generation |
| `nunomaduro/collision` | ^7.0 | Better error output |
| `spatie/laravel-ignition` | ^2.0 | Error pages |

### Frontend Stack
| Component | Value |
|---|---|
| Build Tool | Vite 5.x |
| CSS Framework | Tailwind CSS 3.x |
| JS Framework | Alpine.js 3.x |
| Calendar Component | TOAST UI Calendar (tui-calendar ^1.15.3) |
| HTTP Client | Axios ^1.6.4 |
| DataTables | Server-side via Yajra (jQuery-based, embedded in Blade) |
| UI Alerts | SweetAlert2 (via realrashid/sweet-alert) |
| PDF | DomPDF (server-side) |

### Database
| Setting | Value |
|---|---|
| Driver | MySQL |
| Database name | `salon_db` |
| Host (Docker) | `db` service |
| Port | 3306 (external: 3307) |

### Cache & Queue
| Setting | Value |
|---|---|
| Cache Driver | Redis |
| Queue Connection | `sync` (no background queue workers) |
| Session Driver | database |

### Authentication
- Laravel Breeze-generated session auth (`Auth::routes()` subset under `/admin` prefix)
- Email + password login (`/admin/login`)
- Email verification required (middleware `verified`)
- Password reset via email
- Registration routes are **commented out** — self-registration is disabled
- Custom `CheckRole` middleware enforces permission checks on all admin routes
- `remember_token` present in users table
- Sanctum installed but not actively used (API routes return placeholder JSON only)

### Authorization
- **spatie/laravel-permission v6.7** — roles + direct permissions stored in DB
- Custom `CheckRole` middleware wraps all `admin` routes; checks `auth()->user()->can($permission)`
- Helper: `AppHelper::perUser($permission)` — static wrapper around `auth()->user()->can()`
- No Laravel Policies defined; no Gates registered

### Storage
- Local disk (`FILESYSTEM_DISK=local`)
- Uploaded files stored under `uploads/images/{employees,employees/id-cards,users,services}/`
- File names: `{date}_{name}_{key}.{ext}` (via `AppHelper::handleFileUpload`)
- No cloud storage configured

### External Integrations
- None confirmed active in production
- Mailpit configured as local mail server (SMTP port 1025)
- Pusher config keys are empty — no real-time broadcasting

### Testing Stack
| Component | Value |
|---|---|
| Framework | PHPUnit 10.x |
| Feature tests | `tests/Feature/` |
| Unit tests | Directory `tests/Unit/` does not exist |
| Test DB | Not configured (uses same DB — `RefreshDatabase` trait present) |

**Test run result:**
```
Command: php artisan test --testsuite=Feature
Result: PHPUnit internal error — "Class Tests\\TestCase not found"
Cause: tests/TestCase.php is missing from the repository
Number of tests that ran: 0
```

### Deployment Configuration
- Docker Compose with services: `app` (PHP-FPM), `db` (MySQL 8), `redis`, `webserver` (Nginx), `mailpit`
- Custom `Dockerfile` based on PHP 8.x
- `.env.docker` mirrors `.env` with Docker hostnames
- `QUEUE_CONNECTION=sync` — no queue worker required
- `APP_ENV=local`, `APP_DEBUG=true` — **debug mode is enabled and must be disabled for production**

---

## B. Application Architecture

### Architectural Style
- **Monolithic MVC** (Model-View-Controller)
- No service layer, no repository pattern, no action classes
- All business logic resides in Controllers (fat controllers) and occasionally in Models
- Blade templates are the only frontend rendering mechanism (no SPA)

### Module Boundaries
The application is organized by resource/domain, not by feature module:

```
app/
  Http/
    Controllers/Admin/   ← all business logic
    Middleware/          ← auth, checkRole, setLocale
    Requests/            ← form validation
    Resources/           ← AppointmentResource only
  Models/                ← Eloquent models + some business logic
  Traits/
    AppHelper.php        ← permission check + file upload (autoloaded globally)
    HasUserActions.php   ← createdBy/updatedBy/deletedBy/openedBy relationships
  DataTables/            ← Yajra DataTable classes
  View/                  ← (directory exists, contents not examined)
database/
  migrations/
  seeders/
  factories/
resources/views/admin/   ← Blade views
routes/
  web.php                ← all web routes
  auth.php               ← Breeze auth routes
  api.php                ← empty / placeholder
```

### Business Logic Locations
- **Primary**: Controllers in `app/Http/Controllers/Admin/`
- **Secondary**: `PurchaseInvoice::saveDetails()` — purchase invoice logic in Model
- **Secondary**: `CustomerTransaction::useDepositsForInvoice()` — deposit logic in Model
- **No**: dedicated Service classes, Action classes, or Repository layer

### Validation Strategy
- Laravel Form Requests for most create/update operations (e.g., `EmployeeRequest`, `CustomerRequest`, `ServiceRequest`)
- Inline `$request->validate()` used in `SalesInvoiceController::store()` and report controllers
- No Request class for Appointment store/update (raw `Request` with no validation)

### Authorization Strategy
- `CheckRole` custom middleware (route-level)
- Uses spatie permission `->can()` under the hood
- No Policy classes, no Gate definitions
- Cashier-specific branch scoping in `SalesInvoiceController::create()`:
  ```php
  $branches = Auth::user()->hasRole('cashier')
      ? Branch::where('id', Auth::user()->employee?->branch_id)->get()
      : Branch::where('status', 'active')->get();
  ```
- Branch filtering in `HomePageController` based on `Auth::user()->employee->branch->id == 1`
  (branch ID 1 = all-access / admin branch — hardcoded magic number)

### API Architecture
- `routes/api.php` exists but returns placeholder only
- Sanctum installed but API is not built out
- `AppointmentController::index()` returns JSON (via `AppointmentResource::collection()`) but the route is accessible without authentication (`Route::resource('appointments', ...)` is outside any auth middleware)

### Frontend/Backend Boundaries
- Server-rendered Blade with embedded JavaScript
- Alpine.js for reactive interactions in Blade
- AJAX calls from Blade views to blade/API controller endpoints for dropdown population in sales invoice creation
- DataTables (jQuery) for list pages — server-side via Yajra

### Event-Driven Behavior
- No Events or Listeners defined
- No observer classes found

### Queue Usage
- `QUEUE_CONNECTION=sync` — all queued jobs run synchronously in the request cycle
- No jobs identified in `app/Jobs/` (directory not present)

### Scheduled Jobs
- `routes/console.php` exists (standard Breeze scaffold) — no scheduled commands defined

### Notification Architecture
- Email notifications: Mailpit configured locally, no production mail server confirmed
- No custom Notification classes found
- SweetAlert flash alerts for UI feedback

### File/Storage Architecture
- Files stored on local disk in `storage/app/uploads/`
- Naming: `{Y-m-d}_{name}_{field}.{ext}`
- No signed URLs, no presigned downloads, no access control on uploaded files
- Old files deleted on update (employees, users)

---

## C. Confirmed Dependencies (inter-module)

```
User → Employee (belongs to via employee_id)
Employee → Branch (belongs to)
Employee → EmployeeLevel
Employee → EmployeeWage (auto-created on Employee::created)
Employee ↔ Service (many-to-many via service_employees, with commission)
Service → ServiceCategory
Service ↔ Tool (many-to-many via service_tools)
Service ↔ Product (many-to-many via service_products)
Product → ProductCategory
Product → Supplier
Product → Unit
Product → SupplierPrice (has many — price history)
SalesInvoice → Customer, Branch, PaymentMethod
SalesInvoice → SalesInvoiceDetail (has many)
SalesInvoiceDetail → Product OR Service + Employee (provider)
PurchaseInvoice → Supplier, Branch
PurchaseInvoice → PurchaseInvoiceDetail → SupplierPrice
Inventory → Branch
Inventory → InventoryProduct (pivot: product quantities per inventory)
InventoryTransaction → Inventory (source/destination)
CustomerTransaction → Customer
Branch → Inventory (implied via saveDetails)
Expense → ExpenseType, PaymentMethod, Branch
Appointment → Customer, Employee (provider), Service
```
