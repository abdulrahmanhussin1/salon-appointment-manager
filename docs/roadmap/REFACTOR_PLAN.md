# REFACTOR PLAN — Salon Appointment Manager

> **This plan must be executed AFTER Phase 0 bug fixes (REQ-001 through REQ-008) are complete.**
> Refactoring before fixing critical bugs on a codebase with zero tests is dangerous.
>
> Date Created: 2026-09-24

---

## Guiding Principles

1. **One concern per PR** — upgrade PHP separately from upgrading Laravel, upgrade Laravel separately from refactoring architecture
2. **Never mix refactor with feature work** — every PR is either: upgrade / refactor / feature — never two at once
3. **Tests before refactor** — no module is refactored until it has characterization tests covering its existing behavior
4. **No big bang** — module by module, domain by domain
5. **Backward compatible** — existing data and behavior is preserved at every step

---

## Current State (Baseline)

| Component | Current | Target |
|---|---|---|
| PHP | `^8.1` (Docker: `php:8.2-fpm`) | `^8.5` |
| Laravel | `^10.10` | `^13.0` |
| Laravel Breeze | `^1.29` | `^2.x` |
| Laravel Sanctum | `^3.3` | `^4.x` |
| Laravel Tinker | `^2.8` | `^2.x` (latest) |
| Laravel Sail | `^1.18` | (remove — using custom Docker) |
| Laravel Telescope | `^5.2` | `^5.x` (latest) |
| Laravel Pint | `^1.0` | `^1.x` (latest) |
| spatie/laravel-permission | `^6.7` | `^6.x` → evaluate `^8.x` |
| yajra/laravel-datatables | `^10.1` | `^13.0` |
| barryvdh/laravel-dompdf | `^3.0` | `^3.x` (latest) |
| livewire/livewire | `^3.0` | `^3.x` (latest) |
| realrashid/sweet-alert | `^7.2` | `^7.x` (latest) |
| phpunit/phpunit | `^10.1` | `^11.x` |
| nunomaduro/collision | `^7.0` | `^8.x` |
| Node.js (Docker) | `node:20-alpine` | `node:22-alpine` (LTS) |
| MySQL (Docker) | `mysql:8.0` | `mysql:8.4` |
| Redis (Docker) | `redis:7-alpine` | `redis:7-alpine` → `redis:8-alpine` |
| Nginx (Docker) | `nginx:alpine` | `nginx:1.27-alpine` (pinned) |
| Tailwind CSS | `^3.1.0` | `^3.x` (latest v3) |
| Vite | `^5.0.0` | `^6.x` |
| Alpine.js | `^3.4.2` | `^3.x` (latest) |

---

## Phase RF-0: Prerequisites (Do First)

> Before any upgrade begins.

### RF-0.1 — Create git branch strategy

```
main (protected)
  └─ develop
       └─ refactor/phase-1-php-upgrade
       └─ refactor/phase-2-laravel-upgrade
       └─ refactor/phase-3-dependencies
       └─ refactor/phase-4-architecture
       └─ refactor/phase-5-docker
```

Every refactor phase is a separate branch with its own PR.

### RF-0.2 — Fix TestCase.php (Blocker)

`tests/TestCase.php` is missing — ALL tests currently fail. This must be recreated before any upgrade, because the test suite is how we verify the upgrade didn't break anything.

**Action:** Create `tests/TestCase.php` with basic setup, fix Breeze-generated auth tests, confirm `php artisan test` runs green.

### RF-0.3 — Baseline test run

Run `php artisan test` and document which tests pass/fail before any upgrade. The goal is: zero new failures after upgrade.

### RF-0.4 — Tag the current state

```bash
git tag v0.1.0-pre-refactor
```

This is our rollback point.

---

## Phase RF-1: PHP Upgrade (8.1 → 8.4)

> Isolated step — only change PHP version, nothing else.

### What Changes

| File | Change |
|---|---|
| `Dockerfile` | `FROM php:8.2-fpm` → `FROM php:8.4-fpm` |
| `composer.json` | `"php": "^8.1"` → `"php": "^8.4"` |
| `docker/php/php.ini` | Add PHP 8.4-compatible opcache settings |

### PHP 8.2 → 8.4 Breaking Changes to Check

| Change | Impact on This Codebase |
|---|---|
| `${var}` string interpolation deprecated (8.2) | Search codebase — grep for `"${` |
| Implicit nullable parameters deprecated (8.4) | e.g., `function foo(string $x = null)` → `function foo(?string $x = null)` |
| `E_DEPRECATED` for dynamic properties (8.2) | Check all models for dynamic property assignment |
| `#[\Override]` attribute (8.3, optional) | Not breaking — skip for now |
| JIT improvements (8.3/8.4) | Not breaking — performance benefit |
| `array_find()` etc. new functions (8.4) | Not breaking — additive |

### Verification

```bash
docker compose build
docker compose exec app php -v          # Confirm 8.4
docker compose exec app php artisan test # All tests still pass
docker compose exec app vendor/bin/pint --test # Code style check
```

---

## Phase RF-2: Laravel Upgrade (10 → 12)

> **Must be done in two steps: 10→11 first, then 11→12.**
> Direct 10→12 is not recommended by the Laravel team.

### Step RF-2a: Laravel 10 → 11

**Key Laravel 11 changes affecting this codebase:**

| Change | Detail | Action Required |
|---|---|---|
| Slim application structure | `app/Http/Kernel.php` removed; middleware registered in `bootstrap/app.php` | Migrate `CheckRole` middleware registration |
| `routes/api.php` not auto-loaded | Now opt-in | No action (api.php is empty) |
| `config/` files reduced | Many configs removed from skeleton (still work, just not generated) | Verify custom configs still load |
| `schedule()` in `routes/console.php` | Scheduler moved | Check if any scheduled tasks exist |
| `withRouting()` in bootstrap | Routes defined in `bootstrap/app.php` | Migrate `web.php` / `auth.php` registration |
| Sanctum `^4.0` | New major | Update |
| Collision `^8.0` | New major | Update |
| Breeze `^2.0` | New major | Update |

**`composer.json` changes for L11:**
```json
"laravel/framework": "^11.0",
"laravel/sanctum": "^4.0",
"laravel/breeze": "^2.0",
"nunomaduro/collision": "^8.0"
```

### Step RF-2b: Laravel 11 → 12

**Key Laravel 12 changes affecting this codebase:**

| Change | Detail | Action Required |
|---|---|---|
| Carbon 2 → Carbon 3 | `nesbot/carbon` v3 is required | Audit Carbon usage for API changes |
| UUID v4 → v7 default | `Str::uuid()` now generates UUIDv7 | Not used in this app — no action |
| SVG not accepted by `image` validation | `image` rule no longer accepts SVG | No SVGs used — no action |
| PHP 8.2 minimum | 8.1 no longer supported | Already handled in RF-1 |

**`composer.json` changes for L12:**
```json
"laravel/framework": "^12.0"
```

### Verification After Each Step

```bash
php artisan test                          # Full test suite
php artisan route:list                    # All routes registered correctly
php artisan config:cache                  # Config compiles without error
php artisan migrate --pretend             # Migrations readable
php artisan queue:work --once             # Queue works (even on sync driver)
```

---

## Phase RF-3: Dependency Upgrades

> After Laravel upgrade is verified stable, upgrade third-party packages.
> Do one package at a time in descending order of risk.

### RF-3.1 — PHPUnit 10 → 11

```json
"phpunit/phpunit": "^11.0"
```

**Breaking changes:**
- `setUp()` / `tearDown()` must declare `void` return type
- `getMock()` signatures changed
- Check all test files

### RF-3.2 — yajra/laravel-datatables 10 → 13

```json
"yajra/laravel-datatables": "^13.0"
```

**Risk: HIGH** — Major version jump (10→13). This is the largest third-party change.

**Breaking changes to check:**
- `DataTables::of()` facade usage — verify still works
- `->make(true)` may be deprecated in favor of `->toJson()`
- `DataTable` class method signatures in `app/DataTables/`
- `->rawColumns()` behavior
- All DataTable classes in `app/DataTables/` must be reviewed

**Action:** Test each DataTable page manually after upgrade.

### RF-3.3 — spatie/laravel-permission 6 → 8

```json
"spatie/laravel-permission": "^8.0"
```

**Breaking changes:**
- `findByName()` and `findOrCreate()` now accept `BackedEnum|string` — not breaking for string usage
- Check `CheckRole` middleware for any internal spatie method calls
- Check `RoleController` for any direct model method calls

### RF-3.4 — Remaining Packages (Lower Risk)

```json
"barryvdh/laravel-dompdf": "^3.x",
"livewire/livewire": "^3.x",
"realrashid/sweet-alert": "^7.x",
"laravel/telescope": "^5.x",
"laravel/pint": "^1.x"
```

Check each package's changelog for breaking changes. Most are minor version bumps.

### RF-3.5 — Remove Laravel Sail

**Reason:** The project uses a custom Docker setup. Sail is unused and adds confusion.

```json
// Remove from require-dev:
"laravel/sail": "^1.18"
```

Also remove `docker-compose.override.yml` if it exists, and any Sail-related scripts.

---

## Phase RF-4: Node.js / Frontend Upgrade

> Separate from PHP/Laravel upgrade. Frontend changes don't affect backend behavior.

### RF-4.1 — Vite 5 → 6

```json
"vite": "^6.0"
```

**Breaking changes:**
- Some plugin APIs changed — check `vite.config.js`
- `laravel-vite-plugin` must be updated to a Vite 6 compatible version

**Verify:** `npm run build` succeeds, assets compile correctly.

### RF-4.2 — Tailwind CSS 3 (keep v3)

> **Do NOT upgrade to Tailwind v4.** Tailwind v4 is a complete rewrite with a different configuration system, JIT-by-default, and no `tailwind.config.js`. Migrating would require rewriting all CSS classes.

**Action:** Update to latest Tailwind 3.x patch:
```json
"tailwindcss": "^3.4.x"
```

### RF-4.3 — tui-calendar Replacement Evaluation

`tui-calendar@^1.15.3` is the current appointment calendar. This package is **abandoned** (last release 2021, repository archived).

**Options:**
1. **FullCalendar** (`@fullcalendar/core`) — industry standard, actively maintained, MIT license for core
2. **Toast UI Calendar v2** — a rewrite by the same team with different API

**Recommendation:** Migrate to FullCalendar during the architecture refactor phase. Not in this upgrade phase.

**Action for now:** Pin to current version. Add a note in tech debt log.

### RF-4.4 — Node.js in Docker: 20 → 22 LTS

```yaml
# docker-compose.yml npm service
image: node:22-alpine
```

---

## Phase RF-5: Docker Updates

> Update Docker infrastructure for security, performance, and modern practices.

### RF-5.1 — Dockerfile (PHP)

**Current:** `FROM php:8.2-fpm` (after RF-1 this becomes 8.4)

**Improvements:**

```dockerfile
# BEFORE
FROM php:8.2-fpm

# AFTER — multi-stage build for production optimization
FROM php:8.4-fpm AS base

# Use specific debian base for reproducibility
# Add: no-install-recommends already present ✓
# Add: COPY --chown for better layer caching
# Add: separate dev and prod stages
```

**Specific changes:**

1. **Pin Composer version** — currently `COPY --from=composer:2` (floating tag)
   ```dockerfile
   COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer
   ```

2. **Multi-stage build** — separate `dev` and `prod` targets:
   ```dockerfile
   FROM php:8.4-fpm AS base
   # ... common setup

   FROM base AS development
   # Install Xdebug for development
   RUN pecl install xdebug && docker-php-ext-enable xdebug

   FROM base AS production
   # Optimized for production — no dev tools
   ```

3. **OPcache production settings** — separate `php-prod.ini`:
   ```ini
   ; Production: disable timestamp validation for performance
   opcache.validate_timestamps = 0
   opcache.revalidate_freq = 0
   ```

4. **Add Xdebug to dev stage** for debugging and code coverage.

5. **Health check** — add to Dockerfile:
   ```dockerfile
   HEALTHCHECK --interval=30s --timeout=3s \
     CMD php-fpm-healthcheck || exit 1
   ```

### RF-5.2 — docker-compose.yml

**Current services:**

| Service | Current Image | Target Image |
|---|---|---|
| `app` | custom `php:8.2-fpm` | `php:8.4-fpm` (via Dockerfile) |
| `web` | `nginx:alpine` (floating) | `nginx:1.27-alpine` (pinned) |
| `db` | `mysql:8.0` | `mysql:8.4` |
| `redis` | `redis:7-alpine` | `redis:8-alpine` |
| `mailpit` | `axllent/mailpit:latest` (floating) | `axllent/mailpit:v1` (pinned) |
| `npm` | `node:20-alpine` | `node:22-alpine` |

**Structural improvements:**

1. **Pin all image versions** — no more `:latest` or `:alpine` floating tags in production compose

2. **Add `healthcheck` to web service:**
   ```yaml
   web:
     healthcheck:
       test: ["CMD", "wget", "-qO-", "http://localhost/up"]
       interval: 30s
       timeout: 5s
       retries: 3
   ```

3. **Add `app` service healthcheck:**
   ```yaml
   app:
     healthcheck:
       test: ["CMD", "php-fpm", "-t"]
       interval: 30s
       timeout: 5s
       retries: 3
   ```

4. **Add `docker-compose.override.yml`** for development-specific overrides (Xdebug port, volume mounts for hot-reload):
   ```yaml
   # docker-compose.override.yml (git-ignored)
   services:
     app:
       build:
         target: development
       environment:
         XDEBUG_MODE: debug
         XDEBUG_CONFIG: client_host=host.docker.internal
   ```

5. **Split compose files:**
   - `docker-compose.yml` — base (works in both dev and prod)
   - `docker-compose.override.yml` — dev overrides (auto-loaded by Docker Compose)
   - `docker-compose.prod.yml` — production overrides (explicit: `-f docker-compose.yml -f docker-compose.prod.yml`)

6. **MySQL 8.0 → 8.4:**

   > **Warning:** MySQL 8.0 → 8.4 is a major version upgrade. **Do not auto-upgrade a running database.** Requires:
   > - Dump all data first: `mysqldump`
   > - Upgrade image
   > - Restore + run `mysql_upgrade`
   > - Test all queries (some deprecated syntax removed in 8.4)

### RF-5.3 — docker/nginx/default.conf

**Improvements to current Nginx config:**

1. **Add security headers:**
   ```nginx
   add_header X-Frame-Options "SAMEORIGIN";
   add_header X-Content-Type-Options "nosniff";
   add_header X-XSS-Protection "1; mode=block";
   add_header Referrer-Policy "strict-origin-when-cross-origin";
   ```

2. **Enable Gzip compression** for assets

3. **Add rate limiting** for admin routes:
   ```nginx
   limit_req_zone $binary_remote_addr zone=admin:10m rate=30r/m;
   ```

4. **Pin to Nginx 1.27** (current stable)

### RF-5.4 — docker/php/php.ini

**Current issues:**
- `opcache.validate_timestamps = 1` — correct for development, wrong for production
- No separate dev/prod ini files

**Action:** Create two ini files:
- `docker/php/php-dev.ini` — `validate_timestamps=1`, Xdebug config
- `docker/php/php-prod.ini` — `validate_timestamps=0`, optimized memory

---

## Phase RF-6: Code Architecture Refactor

> This is the largest phase. Execute AFTER all upgrades are stable.

### RF-6.1 — PHP 8.x Modernization

Leverage features available in PHP 8.1+ that the codebase doesn't use:

| Feature | Where to Apply |
|---|---|
| **Enums** | Replace magic strings: `status` columns (active/inactive/draft), `salary_type`, `commission_type`, appointment status |
| **Readonly properties** | Data transfer objects / value objects |
| **`match` expressions** | Replace `switch` statements in middleware and controllers |
| **Named arguments** | Improve clarity in complex constructor calls |
| **Fibers** (8.1) | Not applicable — sync queue |
| **First-class callable syntax** | `Closure::fromCallable()` → `$fn(...)` |
| **`never` return type** | For methods that always throw |
| **Intersection types** | Interface combinations |

### RF-6.2 — Extract Business Logic from Controllers

Current state: all business logic is in controllers (fat controllers).

**Target state:** Controllers handle HTTP only. Business logic lives in dedicated classes.

**Pattern to adopt:** **Action classes** (single-responsibility, testable)

```
app/
  Actions/
    Appointments/
      CreateAppointmentAction.php
      UpdateAppointmentStatusAction.php
    Sales/
      CreateSalesInvoiceAction.php
      VoidSalesInvoiceAction.php
      ProcessDepositUsageAction.php
    Inventory/
      DeductInventoryAction.php
      TransferStockAction.php
      AdjustStockAction.php
    Commission/
      CalculateCommissionAction.php
```

Each Action class:
- Has a single `handle()` or `execute()` method
- Is independently unit testable
- Has no HTTP dependencies (no `Request`, no `Response`)
- Throws domain exceptions (not HTTP exceptions)

### RF-6.3 — PHP 8.1 Enums for Status Types

Replace all magic status strings with Backed Enums:

```php
// BEFORE (magic strings everywhere)
$appointment->status = 'confirmed';
if ($invoice->status === 'active') { ... }

// AFTER
$appointment->status = AppointmentStatus::Confirmed;
if ($invoice->status === SalesInvoiceStatus::Active) { ... }
```

**Enums to create:**

```
app/Enums/
  AppointmentStatus.php     (requested|confirmed|rejected|cancelled|rescheduled|checked_in|in_service|completed|no_show|expired)
  SalesInvoiceStatus.php    (active|inactive|draft|voided)
  EmployeeStatus.php        (active|inactive)
  CustomerStatus.php        (active|inactive)
  SalaryType.php            (daily|weekly|monthly|commission)
  CommissionType.php        (percentage|value)
  InventoryTransactionType.php (purchase|sales|transfer|adjustment|damage|waste)
  ProductType.php           (operation|sales)
  ExpenseStatus.php         (active|inactive)
  AddedFrom.php             (online|referral|walk_in|advertisement|direct)
```

### RF-6.4 — FormRequest Coverage

Some controllers use inline `$request->validate()` instead of FormRequests.

**Controllers with inline validation:**
- `SalesInvoiceController::store()` — `validateInvoiceData()` private method
- `ReportController::dailyRevenues()` — inline validation
- `AppointmentController` — no validation at all

**Target:** Every `store()` and `update()` uses a dedicated `FormRequest`.

### RF-6.5 — File Architecture Reorganization

**Current structure:**
```
app/Http/Controllers/Admin/   ← 30 controllers flat
app/Models/                   ← 33 models flat
```

**Target structure (domain-organized):**
```
app/
  Http/
    Controllers/
      Admin/
        Appointments/
          AppointmentController.php
        Sales/
          SalesInvoiceController.php
        Inventory/
          InventoryController.php
          InventoryTransactionController.php
        Reports/
          ReportController.php
          EmployeeReportController.php
          StockReportController.php
        Employees/
          EmployeeController.php
          EmployeeReportController.php
        ...
  Models/
    Appointment.php
    SalesInvoice.php
    ...  (keep flat — Laravel convention)
  Actions/
    Appointments/...
    Sales/...
    Inventory/...
  Enums/
    AppointmentStatus.php
    ...
  Exceptions/
    InsufficientInventoryException.php
    AppointmentConflictException.php
    ...
```

### RF-6.6 — Test Infrastructure

Current state: `tests/TestCase.php` missing. All tests broken.

**Target:** Full test coverage for every Action class and every controller.

**Test categories to create:**

```
tests/
  Unit/
    Actions/
      Appointments/
        CreateAppointmentActionTest.php
      Sales/
        ProcessDepositUsageActionTest.php
        CalculateCommissionActionTest.php
      Inventory/
        DeductInventoryActionTest.php
  Feature/
    Appointments/
      AppointmentControllerTest.php        (auth, CRUD, conflict check)
    Sales/
      SalesInvoiceControllerTest.php       (create, void, deposit)
    Reports/
      ReportControllerTest.php             (branch filter, date range)
    Auth/
      AuthenticationTest.php              (existing Breeze tests)
```

---

## Refactor Priority Order (What to Do First)

```
RF-0    Prerequisites         ← Do immediately after Phase 0 bug fixes
  └─ RF-0.1 Branch strategy
  └─ RF-0.2 Fix TestCase.php
  └─ RF-0.3 Baseline test run
  └─ RF-0.4 Tag current state

RF-1    PHP 8.1 → 8.4        ← Small, isolated, low risk
RF-2a   Laravel 10 → 11      ← Medium risk; careful with middleware
RF-2b   Laravel 11 → 12      ← Low additional risk after 11
RF-3    Dependencies          ← Package by package, yajra is highest risk
RF-4    Frontend / Node       ← Vite 6, Tailwind 3.x update
RF-5    Docker updates        ← Infrastructure; verify all services healthy

  ── STABLE UPGRADED BASELINE ──

RF-6.1  PHP 8.x Enums        ← Low risk, high value
RF-6.2  Extract to Actions   ← Module by module
RF-6.3  FormRequest coverage  ← Per controller
RF-6.4  File reorganization   ← After Actions are extracted
RF-6.5  Tests                 ← Written alongside each refactor
```

---

## Risk Register

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| yajra DataTables v13 breaking changes | High | High | Test every DataTable page after upgrade; have v10 rollback ready |
| MySQL 8.0→8.4 data incompatibility | Medium | Critical | Full DB dump before upgrade; test on copy of production data |
| Laravel 11 slim app structure breaks CheckRole middleware | High | High | Read L11 upgrade guide carefully; test auth flows |
| Carbon 3 API changes break date handling | Medium | Medium | Audit all Carbon usage before L12 upgrade |
| tui-calendar no longer works after Node upgrade | Medium | High | Test calendar page immediately after Node upgrade |
| Tailwind 3→4 accidental upgrade breaking styles | Low | Critical | Lock `"tailwindcss": "^3.4"` — never use `latest` |

---

## Definition of Done (Per Phase)

A refactor phase is complete when:

- [ ] `php artisan test` passes with zero new failures
- [ ] `php artisan route:list` shows all expected routes
- [ ] `php artisan config:cache` succeeds
- [ ] `docker compose up` starts all services healthy
- [ ] All major pages load and function correctly (manual smoke test)
- [ ] `vendor/bin/pint` reports no violations
- [ ] PR reviewed and approved
- [ ] `docs/ai/PROGRESS.md` updated
- [ ] `docs/ai/DECISIONS.md` updated with any architectural decisions
