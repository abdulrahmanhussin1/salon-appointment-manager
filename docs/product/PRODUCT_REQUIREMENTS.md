# PRODUCT REQUIREMENTS — Salon Appointment Manager

> **This is the canonical target-state product specification.**
> A product engineer should be able to use this document as the source of truth for deciding what the software should do, without reading the entire repository.
>
> Generated: 2026-09-24 | Based on full repository analysis

---

## Product Overview

**Name:** Salon Appointment Manager
**Category:** Multi-branch salon/spa/beauty center internal management system
**Tech Stack:** Laravel 10 + PHP 8.1, MySQL, Blade + Alpine.js, Tailwind CSS

**Core Value Proposition:**
> Enable a multi-branch beauty business to manage appointments, sell services and products at POS, track staff performance and commissions, manage inventory, and review branch-level financial performance — all from a single internal dashboard.

**Target Businesses:** Salons, spas, beauty centers, barbershops, wellness centers

**Target Users (internal, staff-facing only):** Business owner, branch manager, receptionist, cashier, service provider, inventory employee, accountant

**NOT in scope:** Customer-facing portal, accounting/ERP, clinic-grade clinical records, franchise management

---

## Current System Maturity

| Domain | Maturity |
|---|---|
| POS / Sales Invoice | Functional (with bugs) |
| Customer Management | Functional |
| Employee Management | Functional (with duplicate wage bug) |
| Appointment Scheduling | Structural only — no lifecycle |
| Inventory (purchase/receive) | Functional |
| Inventory (service consumable) | Schema only — not executed |
| Commission Tracking | Schema only — not calculated |
| Financial Reporting | Partial — inaccurate commissions, no branch filter |
| Security | Critical vulnerabilities present |

---

## Requirements

Each requirement follows this format:

> **ID** | **Problem** | **Current behavior** | **Proposed behavior** | **Business reason** | **Affected modules** | **Dependencies** | **Risk** | **Complexity** | **Priority** | **Acceptance criteria**

---

### REQ-001: Secure Appointment Routes

**Problem:** Any unauthenticated user can create, modify, or delete any appointment in the system via HTTP.

**Current behavior:** `Route::resource('appointments', ...)` is placed outside the `auth` + `checkRole` middleware group. No authentication, no authorization, no validation.

**Proposed behavior:** Appointment routes moved inside `Route::prefix('admin')->middleware(['auth', 'verified', 'checkRole'])`. Form Request validation added to `store()` and `update()`.

**Business reason:** Prevents external actors from corrupting appointment data. Basic security hygiene required before any production deployment.

**Affected modules:** `routes/web.php`, `AppointmentController`, new `AppointmentRequest`

**Dependencies:** None

**Risk:** Low — moving routes inside middleware is non-breaking

**Complexity:** XS (< 1 day)

**Priority:** Must Have / Phase 0

**Acceptance criteria:**
- `GET /appointments` without login returns redirect to login
- `POST /appointments` without login returns 401 or redirect
- Authenticated user without `appointments.create` permission is denied
- `store()` validates: `customer_id`, `provider_id`, `service_id`, `start_datetime`, all required, all FK-validated

---

### REQ-002: Fix AppointmentController Using Request Body Instead of Route Parameter

**Problem:** `AppointmentController::update()` and `::destroy()` use `$request->id` (body value) instead of the route `{id}` parameter to identify the record. Any authenticated user can target any appointment.

**Current behavior:**
```php
$product = Appointment::findOrFail($request->id);
```

**Proposed behavior:**
```php
$appointment = Appointment::findOrFail($id); // route parameter
```

**Business reason:** Authorization bypass. Any user can target any record by sending a different `id` in the request body.

**Affected modules:** `AppointmentController`

**Dependencies:** REQ-001 (auth must be in place first)

**Risk:** Low

**Complexity:** XS

**Priority:** Must Have / Phase 0

**Acceptance criteria:**
- `PUT /appointments/5` updates appointment with `id=5`, regardless of any `id` value in request body
- Route model binding preferred over manual `findOrFail`

---

### REQ-003: Remove dd() from Production Code Paths

**Problem:** `dd($th->getMessage())` exists in `EmployeeController`, `ServiceController`, and `PurchaseInvoiceController` catch blocks. This crashes the application and exposes internal error details (SQL, file paths) to the browser.

**Current behavior:** On error: application outputs raw exception message to browser and halts.

**Proposed behavior:** `Log::error($th->getMessage())` + generic user-facing flash message + `DB::rollBack()` where applicable.

**Business reason:** Security (information disclosure) + data integrity (transactions not rolled back).

**Affected modules:** `EmployeeController`, `ServiceController`, `PurchaseInvoiceController`

**Dependencies:** None

**Risk:** None

**Complexity:** XS

**Priority:** Must Have / Phase 0

**Acceptance criteria:**
- No `dd()` in any catch block in the application
- Errors produce a user-friendly message via `Alert::error()`
- All DB transactions are rolled back on exception

---

### REQ-004: Fix EmployeeWage Duplicate Creation

**Problem:** Two `EmployeeWage` records are created per employee — one via `Employee::boot()->created()` and one explicitly in `EmployeeController::store()`.

**Current behavior:** Every new employee has 2 `employee_wages` rows.

**Proposed behavior:** Only one row exists per employee. Remove explicit creation from controller; rely on model boot event only.

**Business reason:** Data integrity. Wage reports or calculations will produce incorrect results if two wage rows exist.

**Affected modules:** `EmployeeController::store()`

**Dependencies:** None

**Risk:** Low (must verify boot event fires correctly)

**Complexity:** XS

**Priority:** Must Have / Phase 0

**Acceptance criteria:**
- Creating a new employee results in exactly one `employee_wages` row
- Existing duplicate records should be cleaned up (data migration)

---

### REQ-005: Fix Inventory Transfer Silent Failure

**Problem:** When transferring stock to a branch where the product has no inventory row, `DB::table('inventory_products')->increment()` silently does nothing. Source stock is decremented; destination gets nothing. Stock is lost.

**Current behavior:** Transfer "succeeds" but stock disappears.

**Proposed behavior:** Ensure destination `inventory_products` row exists (with `quantity=0`) before incrementing.

**Business reason:** Stock loss between branches corrupts all inventory reporting and financial calculations.

**Affected modules:** `InventoryTransactionController`

**Dependencies:** None

**Risk:** None

**Complexity:** XS

**Priority:** Must Have / Phase 0

**Acceptance criteria:**
- Transferring product X from Branch A to Branch B (where B has no prior stock of X) results in X being correctly available at B
- No stock is lost during transfer

---

### REQ-006: Fix price_can_change Ignored in Service Processing

**Problem:** `service.price_can_change = true` allows cashier to set a custom price at POS, but `processService()` always uses `$service->price`, ignoring the submitted price.

**Current behavior:** Custom price entered at POS is silently discarded. Invoice records base price.

**Proposed behavior:** When `price_can_change = true`, use the price submitted in `$item['price']` from the validated request.

**Business reason:** Some services (consultations, custom treatments) are priced per session. Forcing the base price creates incorrect invoices.

**Affected modules:** `SalesInvoiceController::processService()`

**Dependencies:** None

**Risk:** Low (must validate submitted price ≥ 0 in `validateInvoiceData()`)

**Complexity:** XS

**Priority:** Must Have / Phase 0

**Acceptance criteria:**
- When `service.price_can_change = true`, invoice records the price submitted by cashier
- When `service.price_can_change = false`, invoice always uses `service.price` regardless of submitted price
- Submitted price is validated as `numeric|min:0`

---

### REQ-007: Update Customer.last_service on Invoice Creation

**Problem:** `customers.last_service` field exists and is intended to track the most recent visit date, but is never updated anywhere in the codebase.

**Current behavior:** `last_service` is always NULL or set at customer creation only.

**Proposed behavior:** When an `active` sales invoice is created for a customer, set `customer.last_service = invoice_date`.

**Business reason:** Customer retention analysis, lapsed customer identification, and re-engagement campaigns all depend on knowing when a customer last visited.

**Affected modules:** `SalesInvoiceController::store()`

**Dependencies:** None

**Risk:** None

**Complexity:** XS

**Priority:** Must Have / Phase 0

**Acceptance criteria:**
- After creating an active invoice for Customer A on date 2026-01-15, `customers.last_service = '2026-01-15'`
- Draft invoices do NOT update `last_service`
- If multiple invoices exist on the same day, `last_service` reflects the date (not earlier)

---

### REQ-008: Fix Draft Invoice Inventory Deduction

**Problem:** When a sales invoice is created with `status='draft'`, inventory is deducted immediately — same as an active invoice. A draft is not committed, so inventory should not be affected.

**Current behavior:** Both draft and active invoices trigger full inventory deduction in `deductFromInventory()`.

**Proposed behavior:** Draft invoices skip inventory deduction. When a draft is transitioned to `active`, inventory deduction occurs at that point.

**Business reason:** Draft invoices represent uncommitted work-in-progress (e.g., being assembled by cashier). Deducting inventory prematurely causes stock discrepancies.

**Affected modules:** `SalesInvoiceController::store()`, new `SalesInvoiceController::activate()` action

**Dependencies:** REQ-009 (invoice status transition workflow)

**Risk:** Medium — requires a new "activate" transition endpoint

**Complexity:** S

**Priority:** Must Have / Phase 0–1

**Acceptance criteria:**
- Creating `draft` invoice does not decrement `inventory_products.quantity`
- Creating `active` invoice decrements inventory as before
- Transition from `draft` to `active` triggers inventory deduction
- Draft can be discarded without inventory impact

---

### REQ-009: Appointment Status Lifecycle

**Problem:** The `appointments` table has no `status` column. The appointment feature cannot track whether a booking is confirmed, cancelled, in-progress, or completed.

**Current behavior:** Appointments are records with a time window and three FK fields. No lifecycle management.

**Proposed behavior:** Full status machine:
- States: `requested`, `confirmed`, `rejected`, `cancelled`, `rescheduled`, `checked_in`, `in_service`, `completed`, `no_show`, `expired`
- Transitions enforced (e.g., cannot go from `completed` back to `confirmed`)
- UI: calendar color-coded by status; status actions available per appointment

**Business reason:** Without appointment status, the business cannot track cancellation rates, enforce no-show policies, auto-update `last_service`, or link appointments to invoices.

**Affected modules:** `appointments` table (migration), `AppointmentController`, calendar view

**Dependencies:** REQ-001, REQ-002

**Risk:** Medium — requires migration; existing appointment data has no status (default to `requested`)

**Complexity:** M

**Priority:** Must Have / Phase 1

**Acceptance criteria:**
- `appointments.status` column with enum exists with default `requested`
- `AppointmentController` has dedicated actions for: confirm, cancel (with reason), check-in, complete, mark no-show
- Calendar view shows appointments with status-based color coding
- Invalid transitions return 422 with clear error message
- Cancelling an appointment records `cancelled_at` and `cancellation_reason`

---

### REQ-010: Appointment Double-Booking Prevention

**Problem:** Two appointments for the same provider can overlap in time without any system error.

**Current behavior:** No overlap check in `store()` or `update()`.

**Proposed behavior:** Before creating or updating an appointment, check whether the assigned provider has an overlapping appointment in states: `requested`, `confirmed`, `checked_in`, `in_service`. Reject with a clear error if conflict found.

**Business reason:** Double-booking wastes provider time, angers customers, and damages business reputation.

**Affected modules:** `AppointmentController::store()`, `AppointmentController::update()`

**Dependencies:** REQ-009 (status must exist to determine which appointments block)

**Risk:** Low

**Complexity:** S

**Priority:** Must Have / Phase 1

**Acceptance criteria:**
- Attempting to book Provider A at 2:00–3:00 PM when Provider A is already booked 2:30–3:30 PM returns HTTP 422: "Provider is not available during the selected time"
- Rescheduling runs the same check on the new time slot
- Completed, cancelled, rejected, no-show appointments do not block new bookings

---

### REQ-011: Auto-Compute Appointment End Time from Service Duration

**Problem:** `appointment.end_date` is manually entered. `service.duration` (in minutes) has no effect on booking.

**Current behavior:** Receptionist must manually calculate end time.

**Proposed behavior:** When a service is selected at booking, `end_datetime = start_datetime + service.duration` is auto-computed. Manual override is only allowed if explicitly enabled.

**Business reason:** Manual computation is error-prone and inconsistent. Incorrect end times cause booking overlaps.

**Affected modules:** `AppointmentController::store()`, booking form (JS auto-fill)

**Dependencies:** REQ-009

**Risk:** Low

**Complexity:** S

**Priority:** Must Have / Phase 1

**Acceptance criteria:**
- Selecting service with `duration=60` and `start_datetime='2026-01-15 10:00'` auto-sets `end_datetime='2026-01-15 11:00'`
- Server-side validation enforces `end_datetime >= start_datetime + service.duration`

---

### REQ-012: Commission Calculation at Invoice Creation

**Problem:** `service_employees` has complete commission configuration (type, value, immediate flag) but `processService()` never reads it. Commission reports show revenue, not commission.

**Current behavior:** `services_commissions = services_sales` (placeholder in `ReportController`).

**Proposed behavior:**
1. Add `commission_amount DECIMAL(10,2) DEFAULT 0` to `sales_invoice_details`
2. In `processService()`, look up `service_employees` for the service/provider pair
3. Compute commission: `percentage` type → `(subtotal - discount) × rate`; `value` type → `value × quantity`
4. Store `commission_amount` on the detail record
5. Report `SUM(commission_amount)` grouped by provider

**Business reason:** Payroll decisions for commission-based staff require accurate commission data. Currently impossible from the system.

**Affected modules:** `sales_invoice_details` (migration), `SalesInvoiceController::processService()`, `ReportController`, `EmployeeReportController`

**Dependencies:** None

**Risk:** Low — additive change

**Complexity:** S

**Priority:** Must Have / Phase 1

**Acceptance criteria:**
- Every service line item on an invoice has a computed `commission_amount`
- If no `service_employees` record exists for provider+service, `commission_amount = 0`
- Commission report shows `SUM(commission_amount)` per employee, per period
- Immediate vs. deferred commissions identified via `is_immediate_commission`
- Daily summary report uses `SUM(commission_amount)` not `SUM(subtotal)` for commissions

---

### REQ-013: Branch-Filtered Reports

**Problem:** All financial and operational reports aggregate data from all branches. Branch managers cannot view their own branch. Owners cannot compare branches.

**Current behavior:** No `WHERE branch_id = ?` clause on any report query.

**Proposed behavior:**
- All report pages include a branch filter dropdown
- Cashier/branch manager role: auto-filtered to their branch, cannot override
- Manager/Admin/Owner role: can select "All Branches" or a specific branch

**Business reason:** Multi-branch operations require per-branch accountability. Without this, branch performance is invisible.

**Affected modules:** `ReportController`, `EmployeeReportController`, `EmployeeSummaryReportController`, `StockReportController`, `StoreBalanceReportController`, all report Blade views

**Dependencies:** None

**Risk:** Low — additive filter

**Complexity:** S

**Priority:** Must Have / Phase 2

**Acceptance criteria:**
- Daily Revenue report, Monthly Summary, Employee Reports, Stock Reports all have branch filter
- Selecting Branch A shows only Branch A's data
- Cashier cannot select a branch other than their own
- "All Branches" option available only for admin/owner roles

---

### REQ-014: Service Consumable Inventory Deduction

**Problem:** `service_products` links services to their consumable products with quantity-per-service, but this is never triggered when a service is sold.

**Current behavior:** Selling a service has zero inventory impact.

**Proposed behavior:** When a service is sold, for each product in `service_products` for that service:
- Deduct `product_quantity × invoice_line_quantity` from inventory (FIFO)
- Create an `InventoryTransaction` of type `service_consumption`
- If stock is insufficient: warning shown to user but sale is not blocked (configurable behavior)

**Business reason:** Service profitability cannot be measured without tracking consumable costs. Stock counts for consumables are wildly inaccurate.

**Affected modules:** `SalesInvoiceController::processService()`, `inventory_transactions` (add new type), new `AdminPanelSetting` for "block on insufficient consumable stock"

**Dependencies:** REQ-005 (transfer fix must be in place first)

**Risk:** Medium — changes sale behavior; requires testing

**Complexity:** M

**Priority:** Must Have / Phase 2

**Acceptance criteria:**
- When Service X (uses 50ml Color A) is sold, `inventory_products.quantity` for Color A decrements by 50ml × quantity
- An `InventoryTransaction` of type `service_consumption` is created
- If Color A has insufficient stock, a warning is shown but the sale can still proceed
- Draft invoices do not trigger consumable deduction

---

### REQ-015: Invoice Void / Cancel Workflow

**Problem:** No workflow exists to void or cancel a submitted invoice. `edit()`, `update()`, and `destroy()` all return 404.

**Current behavior:** Incorrect invoices remain permanently in the system.

**Proposed behavior:**
- New "Void Invoice" action on invoice detail view, permission-gated (`sales_invoices.void`)
- Void within configurable time window (default: same day; admin-configurable)
- Voiding: sets `status = 'voided'`, records `voided_by`, `voided_at`, `void_reason`
- Voiding restores inventory quantities (reverse `deductFromInventory`)
- Voiding restores customer deposit usage (reverse `processDepositUsage`)
- Void creates an immutable audit record

**Business reason:** Cashier errors require correction. Without void, the system accumulates incorrect data.

**Affected modules:** `SalesInvoice` (add `voided_by`, `voided_at`, `void_reason` columns), `SalesInvoiceController`, new `SalesInvoice::void()` method

**Dependencies:** None (can be implemented standalone)

**Risk:** Medium — must correctly reverse all side effects of invoice creation

**Complexity:** M

**Priority:** Must Have / Phase 2

**Acceptance criteria:**
- Void action is available to users with `sales_invoices.void` permission
- Voiding within the time window succeeds and reverses all inventory and deposit effects
- Voiding outside the time window is blocked with explanation
- Voided invoice is visible in list with `voided` status and audit fields
- Voided invoice cannot be voided again or paid

---

### REQ-016: Fix Daily Revenue Report Expense Calculation

**Problem:** `ReportController::dailyRevenues()` only subtracts cash expenses from revenue. Non-cash expenses are excluded.

**Current behavior:**
```php
$paymentMethod = PaymentMethod::where('name', 'cash')->first();
$expenses = Expense::where('payment_method_id', $paymentMethod->id)...
```

**Proposed behavior:** Subtract ALL active expenses for the period, regardless of payment method.

**Business reason:** Net revenue figures are overstated when non-cash expenses exist. Financial decisions based on this report are unreliable.

**Affected modules:** `ReportController::dailyRevenues()`

**Dependencies:** None

**Risk:** None

**Complexity:** XS

**Priority:** Must Have / Phase 2

**Acceptance criteria:**
- Total expenses in daily report = sum of ALL active expenses in the date range
- A separate column can show cash expenses vs. non-cash expenses if needed

---

### REQ-017: Appointment → Invoice Linkage

**Problem:** When a customer checks out after a service, there is no connection between their appointment and the generated invoice.

**Current behavior:** `sales_invoices` has no `appointment_id` column.

**Proposed behavior:**
- Add nullable `appointment_id FK → appointments` to `sales_invoices`
- When creating an invoice, optionally associate it with an open appointment
- On invoice creation from an appointment, auto-set appointment status to `completed`
- POS pre-fills customer, service, and provider from the linked appointment

**Business reason:** Enables conversion rate analytics (bookings → completed), auto-populates POS to reduce data entry errors, enables `last_service` linkage.

**Affected modules:** `sales_invoices` (migration), `SalesInvoiceController`, POS view

**Dependencies:** REQ-009 (appointment status must exist)

**Risk:** Low — additive nullable FK

**Complexity:** M

**Priority:** Should Have / Phase 3

**Acceptance criteria:**
- POS page can be opened pre-filled from an appointment
- On invoice submission, linked appointment is set to `completed`
- Appointment analytics show conversion rate: confirmed → completed

---

### REQ-018: Manual Stock Adjustment

**Problem:** No UI or workflow exists to manually correct inventory quantities (e.g., after a physical count reveals discrepancy).

**Current behavior:** Staff create fake purchase or transfer records to adjust stock — polluting transaction history.

**Proposed behavior:**
- New "Stock Adjustment" UI: select inventory, product, adjusted quantity, reason code, date, notes
- Creates `InventoryTransaction` of type `adjustment` (+ or -)
- Adjustment is visible in stock movement history

**Business reason:** Periodic stock counts reveal discrepancies. Without proper adjustment, stock accuracy drifts indefinitely.

**Affected modules:** New UI page, `InventoryTransactionController::adjust()`, `inventory_transactions` (add `adjustment` type to enum)

**Dependencies:** None

**Risk:** Low

**Complexity:** S

**Priority:** Should Have / Phase 3

**Acceptance criteria:**
- User can enter: inventory, product, quantity change (+/-), reason (count_correction/damage/waste/theft/other), date
- `inventory_products.quantity` is updated accordingly
- Adjustment creates an `InventoryTransaction` record with full audit info
- Adjustment history is visible in stock movement report

---

### REQ-019: Refund / Return Workflow

**Problem:** No workflow exists to refund a sale or return a product. Revenue once recorded cannot be reversed.

**Current behavior:** `destroy()` returns 404. No refund model.

**Proposed behavior:**
- Partial or full refund against an active invoice
- Product returns restore inventory stock
- Refunds can be: cash, credit to customer deposit, or back to original payment method
- Commission amounts on refunded lines are reversed
- Refund creates an immutable record linked to original invoice

**Business reason:** Customer satisfaction requires return capability. Financial reporting integrity requires correct reversal.

**Affected modules:** New `refunds` and `refund_details` tables, new `RefundController`, `SalesInvoice`, inventory, `CustomerTransaction`

**Dependencies:** REQ-015 (void must be separate from refund), REQ-012 (commissions must be calculated to be reversed)

**Risk:** High — complex; must correctly reverse all financial effects

**Complexity:** L

**Priority:** Should Have / Phase 3

**Acceptance criteria:**
- Full or partial refund available against an active invoice
- Product quantities restored to inventory on product return
- Customer deposit restored when deposit was used in original payment
- Commission reversed for refunded service lines
- Refund record is immutable with audit fields

---

### REQ-020: Outstanding Customer Deposit Report

**Problem:** No view shows the total deposit balance held for each customer, or the aggregate liability across all customers.

**Current behavior:** Deposit balance is shown inline when creating a new invoice, but no report exists.

**Proposed behavior:**
- Report: customer name, total deposits received, total deposits used, current balance
- Aggregate total outstanding balance for business liability tracking

**Business reason:** Deposits represent a liability to the business. Tracking them is a financial requirement.

**Affected modules:** New report in `ReportController` querying `customer_transactions`

**Dependencies:** None

**Risk:** None

**Complexity:** XS

**Priority:** Should Have / Phase 2

**Acceptance criteria:**
- Report lists all customers with a non-zero available deposit balance
- Shows: total deposited (ever), total used (ever), current balance
- Shows aggregate total at bottom

---

## Non-Functional Requirements

### NFR-001: Security

- All admin routes must require `auth`, `verified`, and `checkRole` middleware
- No `dd()` in production code paths
- `APP_DEBUG=false` in all non-local environments
- File uploads must validate extensions (jpeg, jpg, png, gif, webp only)
- Appointment routes must be inside the auth middleware group

### NFR-002: Data Integrity

- All multi-step financial operations must run inside a `DB::transaction()`
- Inventory modifications must be atomic (no partial updates)
- Completed/voided/refunded records must not be modifiable without audit trail
- No hard-delete on financial records (sales invoices, inventory transactions, expenses)

### NFR-003: Branch Isolation

- Users scoped to a branch must not be able to query, create, or modify records in other branches
- This must be enforced at the server/query level, not only in UI dropdowns
- Global scopes or middleware-level branch injection preferred over ad-hoc controller checks

### NFR-004: Auditability

- All financial records must have `created_by` and `updated_by`
- Void and refund actions must record `performed_by` and `performed_at`
- Stock adjustments must record `adjusted_by`, reason, and before/after quantities

### NFR-005: Reporting Accuracy

- Commission figures in reports must reflect calculated `commission_amount`, not service revenue
- Expense totals must include ALL payment methods, not only cash
- Branch-filtered reports must be available for all report types

---

## Requirements Traceability Matrix

| Req | Domain | Phase | Priority | Gap / Bug Ref |
|---|---|---|---|---|
| REQ-001 | Security | 0 | Must Have | GAP-008, SEC-001 |
| REQ-002 | Security | 0 | Must Have | BUG-003 |
| REQ-003 | Stability | 0 | Must Have | BUG-001 |
| REQ-004 | Data Integrity | 0 | Must Have | BUG-002, GAP-017 |
| REQ-005 | Inventory | 0 | Must Have | BUG-004, GAP-009 |
| REQ-006 | POS | 0 | Must Have | BUG-011, GAP-015 |
| REQ-007 | CRM | 0 | Must Have | GAP-007 |
| REQ-008 | POS | 0–1 | Must Have | GAP-010 |
| REQ-009 | Appointments | 1 | Must Have | GAP-001 |
| REQ-010 | Appointments | 1 | Must Have | GAP-002 |
| REQ-011 | Appointments | 1 | Must Have | BR-P002 |
| REQ-012 | Staff Economics | 1 | Must Have | GAP-003 |
| REQ-013 | Reporting | 2 | Must Have | GAP-005 |
| REQ-014 | Inventory | 2 | Must Have | GAP-006 |
| REQ-015 | POS | 2 | Must Have | GAP-004 |
| REQ-016 | Finance | 2 | Must Have | — |
| REQ-017 | Appointments | 3 | Should Have | GAP-012 |
| REQ-018 | Inventory | 3 | Should Have | GAP-014 |
| REQ-019 | POS | 3 | Should Have | GAP-011 |
| REQ-020 | Finance | 2 | Should Have | GAP-019 |
