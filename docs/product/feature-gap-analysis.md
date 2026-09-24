# Feature Gap Analysis — Salon Appointment Manager

> MoSCoW prioritization using: Business Value × Operational Impact × User Frequency × Revenue Impact × Risk Reduction ÷ Implementation Complexity

---

## Prioritization Framework

Each feature is scored on:

| Dimension | Scale | Meaning |
|---|---|---|
| Business Value | 1–5 | Strategic importance to the business |
| Operational Impact | 1–5 | How much it disrupts operations if missing |
| User Frequency | 1–5 | How often this affects daily users |
| Revenue Impact | 1–5 | Direct or indirect effect on revenue |
| Risk Reduction | 1–5 | Security, data integrity, or legal risk addressed |
| Complexity | 1–5 | Implementation effort (5 = most complex) |

**Score = (BV + OI + UF + RI + RR) / C**

---

## MUST HAVE — Critical Blockers

### GAP-001: Appointment Status Lifecycle

**Problem:** Appointments have no status column. Cannot confirm, cancel, complete, or track no-shows.

**Affected users:** Receptionist, Branch Manager, Owner

**Operational scenario:** Receptionist books appointment. Customer cancels. Appointment is hard-deleted. There is no record. Owner cannot report on cancellation rate. No-show policy cannot be enforced.

**Current limitation:** `appointments` table has no `status` column. Controller has no status transitions.

**Proposed capability:** Full status machine: `requested → confirmed → checked_in → in_service → completed`. Plus terminal states: `cancelled`, `no_show`, `rejected`, `expired`.

**Business value:** 5 | **Operational impact:** 5 | **User frequency:** 5 | **Revenue impact:** 4 | **Risk reduction:** 3 | **Complexity:** 3

**Score:** (5+5+5+4+3)/3 = **7.3**

**Acceptance criteria:**
- `appointments.status` column with enum exists
- Status transitions enforced (cannot go from `completed` to `confirmed`)
- Calendar view reflects status with color coding
- Appointment can be cancelled with reason
- No-show can be marked by receptionist

---

### GAP-002: Appointment Double-Booking Prevention

**Problem:** Two appointments for the same provider can overlap in time — the system performs zero conflict checking.

**Affected users:** Receptionist, Service Provider, Customer

**Operational scenario:** Two receptionists simultaneously book "Ahmed" for 2:00–3:00 PM. Both succeed. Ahmed is double-booked. One customer arrives to find their slot taken.

**Current limitation:** `AppointmentController::store()` has no overlap query.

**Proposed capability:** On appointment create/update, check if `provider_id` has an overlapping confirmed appointment. Reject with clear error if conflict exists.

**Business value:** 5 | **Operational impact:** 5 | **User frequency:** 4 | **Revenue impact:** 4 | **Risk reduction:** 4 | **Complexity:** 2

**Score:** (5+5+4+4+4)/2 = **11.0**

**Acceptance criteria:**
- Creating an overlapping appointment shows error "Provider is not available at this time"
- Rescheduling checks for conflicts on the new time
- Existing overlaps (if any) do not block the fix (handle historical data gracefully)

---

### GAP-003: Commission Calculation at Invoice Creation

**Problem:** Commission schema is fully designed (`service_employees` with type/value/immediate flags) but never computed. Reports show revenue as commissions — completely wrong.

**Affected users:** Manager, Owner, Accountant, Service Providers

**Operational scenario:** Stylist Sara has 40% commission on haircuts. After a full week, the owner wants to calculate Sara's pay. The commission report shows Sara's revenue as her commission — owner has no accurate number without an Excel sheet.

**Current limitation:** `processService()` ignores `service_employees.commission_type/value`.

**Proposed capability:** Compute commission at invoice creation, store in `sales_invoice_details.commission_amount`. Report `SUM(commission_amount)` grouped by provider.

**Business value:** 5 | **Operational impact:** 5 | **User frequency:** 4 | **Revenue impact:** 5 | **Risk reduction:** 3 | **Complexity:** 2

**Score:** (5+5+4+5+3)/2 = **11.0**

**Acceptance criteria:**
- `sales_invoice_details.commission_amount` populated on every service line
- Employee commission report shows earned commissions (not revenue)
- Commission distinguishes `immediate` vs. `deferred` per `is_immediate_commission` flag
- Zero commission when no `service_employees` record exists for that provider/service

---

### GAP-004: Invoice Editing and Voiding

**Problem:** Once submitted, a sales invoice cannot be edited or cancelled. Any error requires creating a new invoice — the incorrect one stays in the system with no correction.

**Affected users:** Cashier, Manager, Accountant

**Operational scenario:** Cashier accidentally selects the wrong customer. The invoice cannot be fixed. The incorrect customer has a false transaction in their history. Revenue is assigned to the wrong branch.

**Current limitation:** `edit()`, `update()`, `destroy()` all return 404.

**Proposed capability:** Void invoice within a configurable time window (e.g., same day only). Voiding: reverses inventory, reverses deposit usage, marks invoice as `voided`, creates audit record.

**Business value:** 5 | **Operational impact:** 4 | **User frequency:** 3 | **Revenue impact:** 4 | **Risk reduction:** 4 | **Complexity:** 3

**Score:** (5+4+3+4+4)/3 = **6.7**

**Acceptance criteria:**
- Void action available on invoice detail view (permission-gated)
- Voiding restores inventory quantities
- Voiding restores consumed customer deposits
- Void reason required
- Voided invoice remains in system (not hard-deleted)

---

### GAP-005: Branch-Filtered Reports

**Problem:** All reports aggregate across all branches. Branch managers cannot see their own data. Owners cannot compare branches.

**Affected users:** Owner, Branch Manager, Accountant

**Current limitation:** No `WHERE branch_id = ?` filter on `ReportController`, `EmployeeReportController`, or `StoreBalanceReportController`.

**Proposed capability:** All reports include an optional branch filter. Admin/Owner sees all branches or can filter. Branch manager defaults to their branch.

**Business value:** 5 | **Operational impact:** 4 | **User frequency:** 4 | **Revenue impact:** 4 | **Risk reduction:** 3 | **Complexity:** 2

**Score:** (5+4+4+4+3)/2 = **10.0**

**Acceptance criteria:**
- All report pages have a branch filter dropdown
- Cashier/branch manager automatically filtered to their branch
- Admin/Owner can select "All Branches" or a specific branch

---

### GAP-006: Service Consumable Inventory Deduction

**Problem:** Products linked to services via `service_products` are never deducted from inventory when a service is sold. Consumable stock counts overstate reality.

**Affected users:** Inventory Manager, Accountant, Owner

**Current limitation:** `SalesInvoiceController::processService()` ignores `service_products`.

**Proposed capability:** On service sale, deduct `service_products.product_quantity × invoice_quantity` from inventory per product, using the same FIFO logic as retail product sales.

**Business value:** 4 | **Operational impact:** 4 | **User frequency:** 5 | **Revenue impact:** 3 | **Risk reduction:** 3 | **Complexity:** 2

**Score:** (4+4+5+3+3)/2 = **9.5**

**Acceptance criteria:**
- When service X is on an invoice, each product in `service_products` for X is deducted
- Deduction respects FIFO inventory batches
- Insufficient consumable stock generates a warning (but does not block sale — operational decision)
- Inventory transaction record created for consumable deduction

---

### GAP-007: Customer.last_service Update

**Problem:** `customers.last_service` field exists but is never written to. Retention reports are impossible.

**Current limitation:** No update in `SalesInvoiceController::store()`.

**Proposed capability:** When an active invoice is created for a customer, update `customers.last_service = invoice_date`.

**Business value:** 4 | **Operational impact:** 3 | **User frequency:** 5 | **Revenue impact:** 3 | **Risk reduction:** 2 | **Complexity:** 1

**Score:** (4+3+5+3+2)/1 = **17.0**

**Acceptance criteria:**
- `Customer.last_service` updated to `invoice_date` on every active invoice creation
- Not updated for draft invoices

---

### GAP-008: Secure Appointment Routes

**Problem:** Appointment CRUD routes are outside the auth middleware — unauthenticated users can create, modify, or delete any appointment.

**Current limitation:** `Route::resource('appointments', ...)` outside middleware group.

**Proposed capability:** Move appointment routes inside `auth + verified + checkRole` middleware group.

**Business value:** 5 | **Operational impact:** 3 | **User frequency:** 1 | **Revenue impact:** 1 | **Risk reduction:** 5 | **Complexity:** 1

**Score:** (5+3+1+1+5)/1 = **15.0**

**Acceptance criteria:**
- Unauthenticated requests to `/appointments/*` return 401/redirect to login
- Authenticated users without `appointments.create` permission are denied

---

### GAP-009: Inventory Transfer Silent Failure Fix

**Problem:** Stock transferred to a branch where the product doesn't exist in `inventory_products` silently disappears.

**Current limitation:** `DB::table('inventory_products')->increment()` on a non-existent row does nothing.

**Proposed capability:** `firstOrCreate` the `inventory_products` row at destination before incrementing.

**Business value:** 5 | **Operational impact:** 5 | **User frequency:** 3 | **Revenue impact:** 4 | **Risk reduction:** 5 | **Complexity:** 1

**Score:** (5+5+3+4+5)/1 = **22.0**

**Acceptance criteria:**
- Transfer to a branch that doesn't have the product yet succeeds
- Source quantity decremented; destination quantity incremented correctly

---

### GAP-010: Draft Invoice Inventory Deduction Fix

**Problem:** Draft invoices deduct inventory immediately, same as active invoices. A draft is not committed.

**Current limitation:** Same code path for `draft` and `active` in `store()`.

**Proposed capability:** Skip inventory deduction for draft invoices. Deduct only when transitioning `draft → active`.

**Business value:** 4 | **Operational impact:** 4 | **User frequency:** 2 | **Revenue impact:** 3 | **Risk reduction:** 3 | **Complexity:** 2

**Score:** (4+4+2+3+3)/2 = **8.0**

---

## SHOULD HAVE — Important Operational Additions

### GAP-011: Refund / Return Workflow

**Score:** ~6.0 | **Complexity:** 4

Refund model, reverse inventory, reverse deposit usage, reverse commission.

---

### GAP-012: Appointment → Invoice Linkage

**Score:** ~5.5 | **Complexity:** 2

Add `appointment_id` to `sales_invoices`. Enable pre-fill and conversion analytics.

---

### GAP-013: SMS/Email Appointment Reminders

**Score:** ~5.0 | **Complexity:** 3

Automated reminder 24h before appointment. Reduces no-shows by 30–50% (industry benchmark).

---

### GAP-014: Manual Stock Adjustment

**Score:** ~5.0 | **Complexity:** 2

Add adjustment transaction type. UI for manual + / - adjustment with reason code.

---

### GAP-015: Price_can_change Enforcement at POS (BUG-011)

**Score:** ~6.0 | **Complexity:** 1

One-line fix in `processService()`: use `$item['price']` when `price_can_change = true`.

---

### GAP-016: Expense Balance Auto-Calculation

**Score:** ~5.0 | **Complexity:** 1

Compute `balance = amount - paid_amount` in `ExpenseController::store/update`.

---

### GAP-017: EmployeeWage Duplicate Fix (BUG-002)

**Score:** ~10.0 | **Complexity:** 1

Remove explicit `EmployeeWage::create()` from controller — the boot event handles it.

---

### GAP-018: Payroll Run

**Score:** ~4.5 | **Complexity:** 4

Aggregate salary components + commissions + tips - penalties per employee per period.

---

### GAP-019: Outstanding Customer Deposit Report

**Score:** ~5.0 | **Complexity:** 1

Query `customer_transactions WHERE status='available'` grouped by customer.

---

## COULD HAVE — Adds Value, Not Urgent

### GAP-020: Service Package / Bundle

Multi-service bundle with single price. Increases average ticket.

### GAP-021: Customer Loyalty Points

Earn points per spend, redeem as discount. Retention mechanism.

### GAP-022: Low Stock Alerts / Reorder Points

`reorder_point` threshold on product. Alert when below threshold.

### GAP-023: Online Customer Self-Booking Portal

Customer-facing booking form. Reduces receptionist workload.

### GAP-024: Attendance / Time Tracking

Clock-in/out system. Enables overtime and penalty calculations.

### GAP-025: Cash Drawer / Shift Reconciliation

Open shift with starting cash, close with end count, system reconciles.

### GAP-026: Expense Approval Workflow

Manager approval for expenses above threshold.

---

## AVOID / UNNECESSARY

| Feature | Reason |
|---|---|
| Full accounting module (AR/AP/GL) | Integrate with QuickBooks/Xero instead; not core to salon operations |
| Online retail storefront | Out of scope for internal management tool |
| Video consultation | Outside domain |
| Prescription management | Clinic-grade; separate product decision |
| Multi-currency | Overkill for single-market operation at this scale |
| Franchise royalty management | Too early; not in scope |

---

## Prioritized Feature List

| Rank | Gap | Score | Priority |
|---|---|---|---|
| 1 | GAP-009: Inventory transfer fix | 22.0 | Must Have |
| 2 | GAP-007: last_service update | 17.0 | Must Have |
| 3 | GAP-008: Secure appointment routes | 15.0 | Must Have |
| 4 | GAP-002: Appointment double-booking | 11.0 | Must Have |
| 5 | GAP-003: Commission calculation | 11.0 | Must Have |
| 6 | GAP-005: Branch-filtered reports | 10.0 | Must Have |
| 7 | GAP-006: Service consumable deduction | 9.5 | Must Have |
| 8 | GAP-010: Draft inventory fix | 8.0 | Must Have |
| 9 | GAP-001: Appointment status lifecycle | 7.3 | Must Have |
| 10 | GAP-004: Invoice void/cancel | 6.7 | Must Have |
| 11 | GAP-017: EmployeeWage duplicate fix | ~10.0 | Should Have |
| 12 | GAP-015: price_can_change fix | ~6.0 | Should Have |
| 13 | GAP-011: Refund workflow | ~6.0 | Should Have |
| 14 | GAP-012: Appointment → invoice link | ~5.5 | Should Have |
| 15 | GAP-016: Expense balance auto-calc | ~5.0 | Should Have |
