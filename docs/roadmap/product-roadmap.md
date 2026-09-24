# Product Roadmap — Salon Appointment Manager

> Phased roadmap from current state to production-ready multi-branch business management system.
> Each phase has clearly defined goals, deliverables, and acceptance criteria.

---

## Current State Assessment

**Maturity level:** Early Alpha — functional in core POS and inventory purchase workflows, critically broken in appointments, commissions, reporting, and data integrity.

**What works well today:**
- Sales invoice creation (POS) with multi-item cart
- Customer deposit management (FIFO)
- Inventory purchase and FIFO deduction on sale
- Basic expense recording
- Monthly/daily revenue summary reports

**What is broken or missing that blocks production use:**
- Appointment system (no status, no conflict check, no auth)
- Commission calculation (schema-only; report shows wrong data)
- Invoice editing/voiding (404)
- Branch-filtered reports (all branches mixed)
- Service consumable deduction (never triggered)
- Critical security vulnerabilities (appointment routes unprotected, debug mode on)

---

## Phase 0 — Stabilization (Bug Fixes & Security)

**Goal:** Make the application safe, stable, and deployable without losing data or exposing security vulnerabilities.

**Timeline estimate:** 1–2 sprints (2–4 weeks)

**Deliverables:**

| # | Fix | Gap / Bug Reference | Effort |
|---|---|---|---|
| 0.1 | Move appointment routes inside auth middleware | GAP-008, SEC-001 | XS |
| 0.2 | Fix `AppointmentController::update/destroy` to use route param | BUG-003 | XS |
| 0.3 | Remove `dd()` from all catch blocks; use `Log::error()` | BUG-001 | XS |
| 0.4 | Fix `EmployeeWage` double-creation | BUG-002, GAP-017 | XS |
| 0.5 | Fix inventory transfer silent failure | BUG-004, GAP-009 | XS |
| 0.6 | Fix `price_can_change` ignored in `processService()` | BUG-011, GAP-015 | XS |
| 0.7 | Add input validation to `AppointmentController::store/update` | BUG-007 | S |
| 0.8 | Set `APP_DEBUG=false` and `APP_ENV=production` guidance | SEC-003 | XS |
| 0.9 | Fix `expense.balance` to be computed server-side | GAP-016 | XS |
| 0.10 | Update `Customer.last_service` on invoice creation | GAP-007 | XS |
| 0.11 | Fix draft invoice deducting inventory | GAP-010 | S |
| 0.12 | Fix `SalesInvoiceController::create()` branch validation (server-side) | BR-006 | S |

**Success criteria:**
- No `dd()` in any controller
- Appointment routes require authentication
- Inventory transfers never silently lose stock
- `Customer.last_service` is updated on every active invoice
- Application can be deployed to staging without data integrity risks

---

## Phase 1 — Core Operations (Appointment + Commission)

**Goal:** Make the appointment and commission systems usable. These are the two biggest gaps that prevent real operational use.

**Timeline estimate:** 2–3 sprints (4–6 weeks)

### 1A: Appointment Status Lifecycle

| # | Deliverable | Notes |
|---|---|---|
| 1A.1 | Add `status` column to `appointments` table | Migration with default `requested` |
| 1A.2 | Add `branch_id`, `notes`, `source` to `appointments` | Context for analytics |
| 1A.3 | Convert `start_date`/`end_date` to proper `DATETIME` columns | Fix string storage bug |
| 1A.4 | Implement status transitions in `AppointmentController` | Guard invalid transitions |
| 1A.5 | Implement appointment conflict check (provider double-booking) | GAP-002 |
| 1A.6 | Auto-compute `end_datetime = start + service.duration` | BR-P002 |
| 1A.7 | Update calendar view to display appointment status with color | UX |
| 1A.8 | Enable cancel with reason + no-show marking | GAP-001 terminal states |

### 1B: Commission Calculation

| # | Deliverable | Notes |
|---|---|---|
| 1B.1 | Add `commission_amount` to `sales_invoice_details` | Migration |
| 1B.2 | Compute commission in `processService()` | GAP-003 |
| 1B.3 | Commission report: per employee, per period | Replace incorrect report |
| 1B.4 | Fix `dailySummary` commission placeholder | Replace `services_sales` with `SUM(commission_amount)` |

**Success criteria:**
- Receptionist can mark appointment as confirmed, cancelled, no-show, completed
- Creating a double-booked slot shows a clear error
- Commission report shows calculated commission amounts (not revenue)
- `is_immediate_commission` flag is visible in commission report

---

## Phase 2 — Financial Integrity

**Goal:** Fix financial data accuracy and reporting so the owner and accountant can trust the numbers.

**Timeline estimate:** 2–3 sprints (4–6 weeks)

### 2A: Branch-Filtered Reports

| # | Deliverable |
|---|---|
| 2A.1 | Add branch filter to all existing report pages |
| 2A.2 | Branch manager auto-filtered to own branch |
| 2A.3 | Admin/Owner sees "All Branches" or can select one |

### 2B: Service Consumable Deduction

| # | Deliverable |
|---|---|
| 2B.1 | Extend `processService()` to deduct `service_products` from inventory |
| 2B.2 | Create `InventoryTransaction` for consumable deductions |
| 2B.3 | Warning (non-blocking) if consumable stock insufficient |

### 2C: Invoice Void / Cancel

| # | Deliverable |
|---|---|
| 2C.1 | Void invoice action (permission-gated, same-day or configurable window) |
| 2C.2 | Void reverses inventory deductions |
| 2C.3 | Void reverses customer deposit usage |
| 2C.4 | Audit record: who voided, when, reason |

### 2D: Expense and Financial Report Fixes

| # | Deliverable |
|---|---|
| 2D.1 | Fix daily revenue report to include ALL expense payment methods |
| 2D.2 | Expense breakdown report by category and branch |
| 2D.3 | Outstanding customer deposits report |
| 2D.4 | Customer `last_service` filtering for retention (lapsed customer list) |

**Success criteria:**
- Owner can see Branch A vs Branch B revenue side-by-side
- Accountant can produce a correct expense breakdown by category
- Gross margin visible (after COGS from product sales)
- Consumable inventory depletions match service volumes

---

## Phase 3 — Service Completeness

**Goal:** Fill the remaining high-value operational gaps and add the workflows missing from day-to-day use.

**Timeline estimate:** 3–4 sprints (6–8 weeks)

### 3A: Appointment → Invoice Linkage

- Add `appointment_id` to `sales_invoices`
- When completing an appointment, pre-fill POS with service, provider, customer
- Appointment auto-marked `completed` when invoice is created

### 3B: Appointment Notifications

- SMS/email confirmation on appointment creation
- 24-hour reminder before appointment
- Cancellation notification to customer
- Integration: Twilio (SMS) or email driver (configurable)

### 3C: Refund / Return Workflow

- Refund model: partial or full refund against original invoice
- Product return: restore inventory stock
- Deposit credit: customer balance restored
- Commission reversal on refunded service lines

### 3D: Manual Stock Adjustment

- Adjustment workflow with reason codes (count correction, damage, waste)
- Audit trail: who adjusted, when, before/after quantity

### 3E: Payroll Run

- Aggregate salary components per employee for a pay period
- Include computed commissions
- Deferred vs. immediate commission split
- Generate payslip per employee

**Success criteria:**
- Appointment completed in system automatically links to its invoice
- Customer receives SMS confirmation on booking
- Incorrect invoice can be voided and a replacement issued
- Stock counts match physical counts after adjustment

---

## Phase 4 — Growth Features

**Goal:** Add features that directly drive revenue and customer retention.

**Timeline estimate:** 4+ sprints (ongoing)

### 4A: Customer Loyalty Program

- Points earned per currency unit spent
- Points redeemable as discount
- Points visible on customer profile and at POS

### 4B: Service Packages / Bundles

- Bundle multiple services at a packaged price
- Package sold at POS, services tracked as delivered per visit

### 4C: Online Self-Booking Portal

- Customer-facing booking form (public URL)
- Real-time provider availability
- Service selection, time slot, booking confirmation
- Payment/deposit collection at time of booking

### 4D: Attendance / Time Tracking

- Clock-in/clock-out (web-based)
- Overtime calculation against `employee_wages.working_hours`
- Late/absence penalty deduction in payroll

### 4E: Cash Drawer / Shift Reconciliation

- Open shift with starting cash
- Close shift: system cash vs. physical count
- Discrepancy reporting

---

## Roadmap Summary Timeline

```
Month 1–2:   Phase 0 (Stabilization)
             ├── Security fixes
             ├── Bug fixes
             └── Data integrity fixes

Month 2–4:   Phase 1 (Core Operations)
             ├── Appointment status lifecycle
             ├── Double-booking prevention
             └── Commission calculation

Month 4–6:   Phase 2 (Financial Integrity)
             ├── Branch-filtered reports
             ├── Service consumable deduction
             ├── Invoice void/cancel
             └── Financial report accuracy

Month 6–9:   Phase 3 (Service Completeness)
             ├── Appointment → invoice link
             ├── Customer notifications
             ├── Refund workflow
             ├── Manual stock adjustment
             └── Payroll run

Month 9+:    Phase 4 (Growth Features)
             ├── Loyalty program
             ├── Packages/bundles
             ├── Online booking
             └── Attendance tracking
```

---

## Dependencies Between Phases

```
Phase 0 (Stabilization)
  └→ Required before Phase 1 (secure app to deploy)

Phase 1B (Commission calculation)
  └→ Required before Phase 3E (Payroll Run depends on correct commission data)

Phase 1A (Appointment status)
  └→ Required before Phase 3A (Appointment → Invoice link needs status = completed)
  └→ Required before Phase 3B (Notifications need status transitions)

Phase 2C (Invoice void)
  └→ Required before Phase 3C (Refund builds on void concepts)

Phase 2B (Service consumable deduction)
  └→ Required before Phase 3C (Refund must reverse consumable deductions)
```
