# PROJECT_CONTEXT — Salon Appointment Manager

> This document provides AI-agent context for working on this codebase. Read this before making changes.

---

## What Is This System?

A multi-branch salon/spa business management system built with Laravel 10 + PHP 8.x.

It is a **monolithic MVC web application** with server-rendered Blade views. All business logic is in controllers.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 10, PHP 8.1+ |
| Frontend | Blade, Tailwind CSS, Alpine.js, Vite |
| Database | MySQL |
| Cache | Redis (configured, not used in app code) |
| Queue | Sync (all jobs inline) |
| Auth | Laravel Breeze + spatie/laravel-permission |
| Table UI | Yajra DataTables (server-side) |
| Alerts | realrashid/sweet-alert |
| Calendar | TOAST UI Calendar (tui-calendar) |
| PDF | barryvdh/laravel-dompdf |
| File Storage | Local disk (`storage/app/uploads/`) |
| Debug | Laravel Telescope (local only) |
| Deployment | Docker Compose |

---

## Directory Guide

```
app/Http/Controllers/Admin/   ← ALL business logic (30 controllers)
app/Models/                   ← Eloquent models (33 models)
app/Http/Requests/            ← Form validation (20 Request classes)
app/Http/Middleware/CheckRole ← Custom RBAC middleware
app/Traits/AppHelper.php      ← perUser($perm), handleFileUpload() [globally autoloaded]
app/Traits/HasUserActions.php ← createdBy/updatedBy/deletedBy/openedBy [globally autoloaded]
app/DataTables/               ← Yajra DataTable classes
database/migrations/          ← 36 migrations (2014–2099)
resources/views/admin/        ← All Blade templates
routes/web.php                ← All routes
routes/auth.php               ← Breeze auth routes
```

---

## Authorization System

**Package:** spatie/laravel-permission

**How it works:**
1. Permissions are seeded (format: `resource.action`, e.g., `employees.create`)
2. Roles group permissions
3. Users assigned roles + optional extra permissions
4. `CheckRole` middleware parses route name and calls `auth()->user()->can($permission)`
5. Helper: `AppHelper::perUser($permission)` for conditional UI rendering in Blade

**Special cases in CheckRole:**
- `store` → checks `resource.create`
- `update` → checks `resource.edit`
- `multi_destroy` → checks `resource.destroy`
- Whitelisted (no permission needed): `home.index`, `dashboard`, `sales_invoices.getItem`, `sales_invoices.getRelatedEmployees`

---

## Key Entities (Quick Reference)

| Entity | Table | Soft Delete | Status |
|---|---|---|---|
| User | users | No | active/inactive |
| Employee | employees | No | active/inactive |
| EmployeeWage | employee_wages | No | N/A |
| Customer | customers | No | active/inactive |
| CustomerTransaction | customer_transactions | No | available/used |
| Branch | branches | No | active/inactive |
| Service | services | No | active/inactive |
| Product | products | No | active/inactive |
| SalesInvoice | sales_invoices | No | active/inactive/draft |
| SalesInvoiceDetail | sales_invoice_details | No | N/A |
| PurchaseInvoice | purchase_invoices | No | active/inactive |
| Inventory | inventories | No | active/inactive |
| InventoryProduct | inventory_products | No | N/A |
| InventoryTransaction | inventory_transactions | No | N/A |
| Expense | expenses | No | active/inactive |
| Appointment | appointments | No | No status column |
| SupplierPrice | supplier_prices | No | N/A |

---

## Critical Known Bugs (Must Know Before Editing)

1. **BUG-001 CRITICAL:** `dd()` left in catch blocks in EmployeeController, ServiceController, PurchaseInvoiceController — crashes app and exposes errors. Transactions are never rolled back.
2. **BUG-002 HIGH:** `EmployeeWage` is created twice per employee (boot event + controller).
3. **BUG-003 HIGH:** `AppointmentController::update()` uses `$request->id` not `$id` route param.
4. **BUG-004 HIGH:** Inventory transfer silently loses stock when product not in destination.
5. **BUG-007 CRITICAL:** Appointment routes have no auth middleware and no input validation.
6. **BUG-011 HIGH:** Service `price_can_change` flag is ignored in invoice store.
7. **BUG-017 HIGH:** `tests/TestCase.php` is missing — no tests can run.

---

## Common Patterns

### Creating a resource
1. Route: `Route::resource('name', Controller::class)` inside admin middleware group
2. Controller: `index(DataTable)`, `create()`, `store(FormRequest)`, `edit(Model)`, `update(FormRequest, Model)`, `destroy(Model)` — `show()` returns `abort(404)`
3. Request: Form Request in `app/Http/Requests/`
4. View: `resources/views/admin/pages/{resource}/`

### File upload
```php
$path = AppHelper::handleFileUpload($request, 'photo', 'uploads/images/employees', $existing);
```
Note: No extension validation is performed. Validate in Form Request manually.

### Permission check in Blade
```php
@if(AppHelper::perUser('employees.create'))
    <a href="...">Add Employee</a>
@endif
```

### Transaction pattern
```php
DB::beginTransaction();
try {
    // ...operations...
    DB::commit();
    Alert::success(...);
    return redirect()->back();
} catch (\Throwable $th) {
    DB::rollBack();
    Log::error($th->getMessage());
    Alert::error(...);
    return redirect()->back();
}
```
(Do NOT use `dd()` in catch blocks)

### FIFO inventory deduction
Happens in `SalesInvoiceController::deductFromInventory()`. Products deducted from oldest `inventory_products` records first.

### FIFO price allocation
In `SalesInvoiceController::allocateProductPrices()`. Customer prices taken from oldest `supplier_prices` records first.

---

## Branch Scoping (Incomplete)

Branch scoping is **not globally enforced**. It is applied ad-hoc:
- `HomePageController`: filters by `Auth::user()->employee->branch->id` (branch ID 1 = all branches — magic number)
- `SalesInvoiceController::create()`: cashier role restricted to own branch
- Most other controllers load all active records regardless of branch

**Do not rely on automatic branch isolation. It must be manually added.**

---

## What Is NOT Implemented

- Appointment status (no column)
- Appointment cancellation / completion / no-show
- Sales invoice editing or cancellation
- Order refund workflow
- Stock adjustment (manual)
- Branch switching UI
- Customer notifications (no email/SMS sent)
- Actual commission calculation in reports
- Unit tests (directory doesn't exist)
- API endpoints (api.php is empty)
- `last_service` date update on customer

---

## Testing

- **All tests are currently broken** — `tests/TestCase.php` is missing
- Only Feature tests exist (in `tests/Feature/` and `tests/Feature/Auth/`)
- These are Breeze-generated auth tests only
- No business logic tests exist

---

## Docker Setup

```bash
docker compose up -d       # Start all services
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

Services: `app` (PHP-FPM), `db` (MySQL 8), `redis`, `webserver` (Nginx), `mailpit`

---

## Important File Paths

| Purpose | Path |
|---|---|
| Main routes | `routes/web.php` |
| Auth routes | `routes/auth.php` |
| Permission check middleware | `app/Http/Middleware/CheckRole.php` |
| Global helper functions | `app/Traits/AppHelper.php` |
| Audit trail trait | `app/Traits/HasUserActions.php` |
| Settings model | `app/Models/AdminPanelSetting.php` |
| Purchase invoice business logic | `app/Models/PurchaseInvoice.php::saveDetails()` |
| POS / Sales invoice logic | `app/Http/Controllers/Admin/SalesInvoiceController.php` |
| Deposit logic (model) | `app/Models/CustomerTransaction.php::useDepositsForInvoice()` |
| Inventory transfer | `app/Http/Controllers/Admin/InventoryTransactionController.php` |
