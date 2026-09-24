# Business Domain Audit — Salon Appointment Manager

> Generated: 2026-09-24 | Analyst role: Senior Product Owner / Business Analyst / Domain Expert
> Source: Full repository analysis — migrations, controllers, models, existing docs

---

## A. Product Positioning

### What Category of Software Is This?

This is a **multi-branch salon/spa business management system** combining:
- Point-of-sale (POS) for services and retail products
- Appointment scheduling / calendar
- Staff management with commission tracking (schema only)
- Basic inventory management (purchase, transfer, FIFO deduction)
- Expense tracking
- Basic financial reporting

It is **not** a pure scheduling tool, **not** a full ERP, and **not** a customer-facing booking platform. It is an **internal operations management system** for staff/managers, with the customer only appearing as an entity within the system.

### Intended Business Type

Primary: **Salons, spas, beauty centers, barbershops**
Secondary (partial fit): **Wellness centers, multi-branch aesthetic clinics**

The schema includes `Dr` salutation for customers and `outside_price` for services, suggesting clinic or home-visit use is anticipated.

### Core Value Proposition

> Enable a multi-branch beauty business to manage appointments, sell services and products at POS, track staff performance, manage inventory, and review financial performance — all from a single internal dashboard.

### Primary Workflows (Confirmed Working)

1. Sales invoice creation (POS) — services + products in a single transaction
2. Customer deposit management — pre-payment, FIFO consumption at invoice
3. Inventory purchase — supplier → branch inventory (FIFO price tracking)
4. Inventory transfer between branches
5. Expense recording by branch
6. Employee management with service/commission assignment
7. Daily, monthly, and employee financial reports

### Secondary Workflows (Partial or Broken)

1. Appointment booking — creates records but no status, no conflict check
2. Stock reporting — present but uses inconsistent cost price logic
3. Commission reporting — schema exists, calculation is a placeholder (shows revenue, not commission)
4. Customer deposit creation — works but deposit balance is not displayed on customer profile

### Unclear Product Boundaries

- **Clinic mode**: `Dr` salutation, `outside_price`, but no consultation, diagnosis, or prescription models
- **Membership/packages**: No schema or workflow exists
- **Online booking**: `added_from='online'` exists on customer but no customer-facing portal
- **Home visit services**: `outside_price` exists on services but never selected or applied in POS

### Conflicting Concepts

| Conflict | Detail |
|---|---|
| `inventory_transactions` type `transfer` used for sales | `SalesInvoiceController` creates an `InventoryTransactionDetail` under an `InventoryTransaction` with `transaction_type=NULL` — the detail type comment says `transfer` |
| Two deposit usage implementations | `SalesInvoiceController::processDepositUsage()` and `CustomerTransaction::useDepositsForInvoice()` — only the controller version is called |
| Service `branch_id` vs global service list | Services have `branch_id` but POS loads ALL active services regardless of branch |
| `amount` vs `paid_amount` on Expense | Suggests partial payment but balance is not visibly calculated or enforced |
| `is_target` on Service and Product | No business rule using this flag was found in controllers |

---

## B. Business Model Analysis

| Business Model Element | Status | Evidence |
|---|---|---|
| Service revenue | ✅ Implemented | `sales_invoice_details.service_id`, full POS flow |
| Product retail revenue | ✅ Implemented | `sales_invoice_details.product_id`, FIFO pricing |
| Appointments | ⚠️ Partial | Schema exists, no status lifecycle |
| Walk-ins | ✅ Supported | POS works without appointment |
| Packages | ❌ Missing | No schema or workflow |
| Memberships | ❌ Missing | No schema or workflow |
| Discounts | ✅ Implemented | Per line-item discount (%) + invoice-level discount |
| Promotions | ❌ Missing | No promotion/campaign model |
| Deposits | ✅ Implemented | `customer_transactions`, FIFO consumption |
| Refunds | ❌ Missing | `destroy()` returns 404; no refund model |
| Partial payments | ✅ Implemented | `balance_due` field, cash + non-cash split |
| Multiple payment methods | ✅ Partial | Cash + one non-cash per invoice; no split across 3+ methods |
| Staff commissions | ⚠️ Schema only | `service_employees.commission_value/type` exists; reporting shows revenue not commission |
| Tips | ❌ Missing | No tip field anywhere |
| Expenses | ✅ Implemented | `expenses` table with branch, type, payment method |
| Inventory consumption | ⚠️ Partial | Products deducted on sale; service consumables not automatically triggered |
| Branch-level financials | ⚠️ Partial | `branch_id` on invoices/expenses but reports are not branch-filtered |

### Missing Financial/Business Concepts

1. **Refund/return workflow** — most critical missing financial concept
2. **Tips** — standard in salon/spa industry
3. **Staff payroll/salary disbursement** — wage schema exists but no payroll run
4. **Petty cash / cash drawer** — no shift open/close, no cash reconciliation
5. **Tax configuration** — tax is applied per-item but there's no system-level tax rate setup
6. **Profit margin per service/product** — cost vs revenue not tracked in reports
7. **Invoice voiding** — no void/cancel with audit trail (delete is 404)
8. **Package/bundle pricing** — no multi-service bundles
9. **Promotion codes** — no discount code mechanism

---

## C. Customer Lifecycle

### Current Map

```
LEAD/CUSTOMER
    ↓
 [Created in system — manual entry by receptionist]
    ↓
 BOOKING (Optional)
    ↓ [Appointment created — no confirmation, no notification]
 VISIT
    ↓ [No check-in; appointment has no status]
 SERVICE DELIVERY
    ↓ [POS invoice created by cashier — not linked to appointment]
 PAYMENT
    ↓ [Cash + non-cash + deposit; receipt printable]
 [No follow-up, no notification, no loyalty]
 RETENTION?
    ↓ [customer.last_service date exists but is never updated]
 ← NO AUTOMATED RETENTION MECHANISM
```

### Identified Gaps

| Lifecycle Stage | Gap |
|---|---|
| Lead capture | No online form, no lead funnel |
| Appointment confirmation | No SMS/email sent to customer |
| Pre-visit reminder | Not implemented |
| Check-in | No check-in action; appointment has no `checked_in` status |
| Appointment → Invoice link | No `appointment_id` on `sales_invoices` |
| Post-visit follow-up | No automated communication |
| Loyalty / retention | No points, no visit count trigger, no birthday automation |
| Customer communication history | No CRM-style interaction log |
| `last_service` update | Field exists but is never written to in code |

---

## D. Appointment Lifecycle

### Current Schema

```sql
appointments:
  id, start_date (string), end_date (string),
  customer_id, provider_id (employee), service_id,
  created_by, updated_by, timestamps
```

**No `status` column exists.**

### Implemented Transitions

| Transition | Status |
|---|---|
| Create (requested) | ✅ Implemented (no auth, no validation) |
| Update (reschedule) | ✅ Implemented (uses `$request->id` — BUG) |
| Delete | ✅ Implemented (uses `$request->id` — BUG) |
| Confirm | ❌ Not implemented |
| Check-in | ❌ Not implemented |
| In-service | ❌ Not implemented |
| Complete | ❌ Not implemented |
| Cancel | ❌ Not implemented |
| No-show | ❌ Not implemented |
| Reject | ❌ Not implemented |
| Expire | ❌ Not implemented |

### Scheduling Rules — Current vs Required

| Rule | Current State |
|---|---|
| Double-booking prevention | ❌ None — no overlap check |
| Staff availability check | ❌ None — any provider can be selected for any time |
| Service duration enforcement | ❌ `end_date` is entered manually; `service.duration` is not used to compute end |
| Buffer time between appointments | ❌ Not implemented |
| Branch capacity / rooms | ❌ No room/resource model |
| Working hours enforcement | ❌ `employee_wages.start_working_time` exists but not checked |
| Holidays / days-off | ❌ No schedule or holiday model |
| Deposit on booking | ❌ Deposits exist only on invoice, not on appointment |
| Cancellation policy | ❌ Not implemented |
| No-show fee | ❌ Not implemented |

---

## E. Multi-Branch Operations

### Branch Isolation Assessment

| Area | Isolation Status | Risk |
|---|---|---|
| Employees | Partial — `branch_id` present | Employee data leakage if not filtered |
| Services | Partial — `branch_id` present, not enforced in POS | Cashier sees all branches' services |
| Products | Partial — `branch_id` present, not enforced | Same as above |
| Sales invoices | Partial — cashier restricted to own branch only | Manager role sees all |
| Expenses | Partial — `branch_id` present, not enforced in reports | Cross-branch expense visibility |
| Inventory | Per-branch — inventory is 1:1 with branch | Better than others |
| Reports | ❌ No branch filter on most reports | Full data leakage |
| Permissions | Role-level, not branch-scoped | No per-branch role scoping |

### Magic Number Risk

`HomePageController` uses `branch_id = 1` as "all branches" sentinel — this is hardcoded and a maintenance hazard. If Branch 1 is deleted or reassigned, the logic breaks.

### Branch Switching

No branch switching UI. A user's branch is derived from their linked employee record. Admin/Owner accounts may not have a linked employee — the system falls back to showing all branches.

---

## F. Inventory Domain

### Implemented Lifecycle

| Stage | Status |
|---|---|
| Purchase (receive) | ✅ `PurchaseInvoice.saveDetails()` — creates `InventoryProduct`, `SupplierPrice` |
| Stock | ✅ Tracked in `inventory_products.quantity` |
| Transfer | ✅ `InventoryTransactionController` — but silent failure on missing destination product |
| Sell (consume) | ✅ FIFO deduction via `SalesInvoiceController.deductFromInventory()` |
| Service consumable consumption | ❌ `service_products` links exist but never triggered at service delivery |
| Manual adjustment | ❌ No adjustment UI or workflow |
| Return/damaged stock | ❌ No return or damage workflow |
| Waste | ❌ Not modeled |

### Retail vs Consumable Distinction

| Type | Schema Support | Runtime Support |
|---|---|---|
| Retail products (sold to customer) | ✅ `product.type='sales'` | ✅ Deducted on sale |
| Operational/consumable products | ✅ `product.type='operation'` | ❌ Never auto-deducted during service |
| Service-linked consumables | ✅ `service_products` pivot | ❌ Never triggered during invoice creation |

This is a **significant gap**: consumable inventory costs are never reflected in service profitability.

---

## G. Sales / POS Audit

| Feature | Status | Notes |
|---|---|---|
| Cart (multi-item) | ✅ | Services + products in one invoice |
| Mixed orders | ✅ | Service + product on same invoice |
| Product price (FIFO) | ✅ | Oldest `supplier_prices` batch used first |
| Service price override | ⚠️ Broken | `price_can_change` flag checked in UI but **ignored in `processService()`** |
| Per-item discount (%) | ✅ | |
| Per-invoice discount | ✅ | `invoice_discount` field |
| Tax per item | ✅ | Percentage-based |
| Cash payment | ✅ | `paid_amount_cash` |
| Non-cash payment | ✅ | One non-cash method per invoice |
| Split payment (cash + non-cash) | ✅ | |
| Deposit application | ✅ | FIFO consumption from `customer_transactions` |
| Partial payment / balance_due | ✅ | |
| Refunds | ❌ | Not implemented |
| Invoice cancellation | ❌ | `destroy()` returns 404 |
| Invoice editing | ❌ | `edit()/update()` returns 404 |
| Receipt | ✅ | PDF receipt view available |
| Cashier shift | ❌ | No shift open/close |
| Cash reconciliation | ❌ | No drawer management |
| Draft invoices | ✅ | `status='draft'` supported in schema and validation |

---

## H. Staff Economics

### Wage Schema (Confirmed in DB)

- `salary_type`: `daily | weekly | monthly | commission`
- `basic_salary`, `bonus_salary`, `allowance1/2/3`, `total_salary`
- `working_hours`, `start_working_time`, `overtime_rate`
- `penalty_late_hour`, `penalty_absence_day`
- `sales_target_settings`: `no | total_sales | employee_daily_service`
- `break_time`, `break_duration_minutes`

### Commission Schema (Confirmed in DB)

Per service, per employee in `service_employees`:
- `commission_type`: `percentage | value`
- `commission_value`
- `is_immediate_commission`

### What Is NOT Implemented

| Feature | Status |
|---|---|
| Commission calculation on sale | ❌ — `ReportController::dailySummary()` sets `services_commissions = services_sales` (placeholder) |
| Commission report | ❌ — Employee report shows revenue, not commission earned |
| Payroll run / disbursement | ❌ — No payroll workflow |
| Attendance / time tracking | ❌ — `finger_print_code` exists but no attendance system |
| Overtime calculation | ❌ — Schema exists, no computation |
| Penalty/absence deduction | ❌ — Schema exists, no computation |
| Tip tracking | ❌ — No tip field |
| Commission on product sales | ❌ — Commission only defined per service |
| Commission reversal on refund | ❌ — No refunds, so moot |

---

## I. Expenses / Finance

### Current Expense Model

| Field | Present | Notes |
|---|---|---|
| Category (expense_type) | ✅ | Configurable lookup table |
| Branch | ✅ | `branch_id` present |
| Payment method | ✅ | FK to `payment_methods` |
| Date | ✅ | `paid_at` timestamp |
| User (created_by) | ✅ | |
| Amount / paid_amount / balance | ✅ | Partial payment modeled |
| Invoice number | ✅ | External reference |
| Recurring expenses | ❌ | No schedule/recurrence |
| File attachments | ❌ | No attachment model |
| Approval workflow | ❌ | No approval state |
| Reporting | ⚠️ Partial | Only included in cash-only expense sum in daily report |

### Financial Reporting Trustworthiness Issues

1. **Cash-only expense filter in daily revenue report** — only expenses paid by cash are subtracted; non-cash expenses are excluded
2. **Commission calculations are placeholders** — reported commissions = total service revenue (meaningless)
3. **No COGS tracking** — product cost is not subtracted from product revenue in any report
4. **Inventory valuation uses latest supplier price** — but FIFO cost would be more accurate
5. **Deposits double-counted risk** — deposits are shown both as "used" in invoice totals and separately as a line item

---

## J. Reporting

| Report Question | Availability |
|---|---|
| Today's revenue | ✅ AVAILABLE — Daily Revenue report |
| Revenue by date range | ✅ AVAILABLE |
| Revenue by branch | ❌ MISSING — no branch filter on any report |
| Revenue by service | ❌ MISSING — no service-level revenue breakdown |
| Revenue by product | ❌ MISSING — no product-level revenue breakdown |
| Revenue by employee | ✅ PARTIAL — Employee report shows revenue but no commissions |
| Appointment utilization | ❌ MISSING — no appointment analytics |
| Cancellation rate | ❌ MISSING — no appointment status |
| No-show rate | ❌ MISSING — no no-show tracking |
| Average ticket | ⚠️ PARTIAL — `avg_customer_value` in daily summary but not per-branch |
| Customer retention | ❌ MISSING — `last_service` never updated |
| Repeat visits | ❌ MISSING |
| Top customers | ❌ MISSING |
| Product profitability | ❌ MISSING — no COGS |
| Service profitability | ❌ MISSING — no consumable cost tracking |
| Staff performance | ⚠️ PARTIAL — service/product counts visible, commissions wrong |
| Commissions | ❌ INCORRECT — shows revenue not commission |
| Expense breakdown | ⚠️ PARTIAL — monthly expense totals but no category breakdown |
| Gross margin | ❌ MISSING |
| Inventory valuation | ✅ PARTIAL — StoreBalanceReport (current month only, uses latest cost) |
| Stock movement | ✅ PARTIAL — StoreBalance shows in/out for current month |

---

## K. UX / Workflow Friction

| Friction Point | Affected User | Impact |
|---|---|---|
| Appointment not linked to invoice | Receptionist / Cashier | Must manually re-identify appointment when creating POS invoice |
| No customer notification on booking | Customer | Customer has no confirmation; calls to confirm |
| Commission not in reports | Manager / Owner | Must use external Excel to calculate staff commissions |
| No cash reconciliation | Cashier | Daily cash count done manually |
| `last_service` field never updated | Manager | Cannot filter inactive customers accurately |
| No invoice editing | Cashier | Mistake requires creating new invoice; no correction path |
| No stock adjustment | Inventory staff | Must create fake purchase/transfer to fix stock counts |
| No appointment conflict warning | Receptionist | Must manually scan calendar for conflicts |
| Reports not branch-filtered | Branch Manager | Sees all branches; cannot isolate their own data |
| Service consumable not auto-deducted | Inventory / Manager | Must reconcile manually; stock counts drift |
| No service duration auto-fill | Receptionist | Must manually compute `end_date` based on service duration |

---

## L. Competitive / Industry Research

### Industry-Standard Capabilities (Reference)

| Capability | Source Reference | Relevance | Assessment |
|---|---|---|---|
| SMS/email appointment reminders | Fresha, Vagaro, Square Appointments | High | **Essential** — reduces no-shows by 30–50% |
| Online customer self-booking | Fresha, Booksy | High | **Useful** — reduces receptionist load |
| Automated commission calculation | Vagaro, Mindbody | High | **Essential** — core staff management |
| Appointment status lifecycle | All modern platforms | Critical | **Essential** — current system is broken without it |
| Package / bundle services | Mindbody, Vagaro | Medium | **Should have** |
| Loyalty points | Fresha, Square | Medium | **Could have** |
| Payroll module | Mindbody | Medium | **Should have** (schema ready) |
| Inventory consumable tracking | Phorest, Kitomba | High | **Essential** for service profitability |
| Online retail store | Fresha, Vagaro | Low | **Could have** — depends on business type |
| Waitlist management | Fresha | Low | **Could have** |

---

## M. Feature Opportunities (MoSCoW)

### Must Have (Critical blockers for production use)

1. **Appointment status lifecycle** — without this the appointment feature is unusable in production
2. **Appointment conflict prevention** — double-booking is a business-breaking bug
3. **Invoice editing / voiding** — no business can operate without correction capability
4. **Commission calculation** — currently a placeholder; staff payroll decisions cannot be made
5. **Branch-level report filtering** — branch managers cannot see their own data
6. **Service consumable deduction** — inventory costs are untracked; CoGS is wrong

### Should Have (Important but not blocking launch)

1. **SMS/email appointment reminders** — immediate ROI on no-show reduction
2. **Refund / return workflow** — required for operational integrity
3. **Payroll run** — schema ready; calculation logic needed
4. **Appointment → Invoice linkage** — critical for business analytics
5. **Customer `last_service` update** — customer retention depends on this
6. **Cash drawer / shift reconciliation** — important for cashier accountability
7. **Tax configuration (system-level)** — currently entered per item manually

### Could Have (Adds value, not urgent)

1. **Package / bundle services** — increases average ticket
2. **Customer loyalty program** — retention improvement
3. **Waitlist management** — useful for high-demand salons
4. **Attendance / time tracking** — HR use case (schema ready)
5. **Online customer booking portal** — reduces receptionist load
6. **Expense approval workflow** — governance for larger businesses
7. **Recurring expenses** — admin time reduction

### Avoid / Unnecessary

1. **Full ERP / accounting module** — integrate with QuickBooks/Xero instead
2. **Online retail storefront** — out of scope for internal management tool
3. **Video consultation** — outside business domain
4. **Prescription management** — clinic-grade feature; not in scope

---

## N. Business Rules

### Confirmed by Current Behavior

| Rule ID | Rule | Source |
|---|---|---|
| BR-001 | A sales invoice requires an active customer | `SalesInvoiceController::store()` |
| BR-002 | All line items must have an assigned provider (employee) | `validateInvoiceData()` |
| BR-003 | Product inventory must be sufficient before sale | `checkInventoryAvailability()` |
| BR-004 | Product prices follow FIFO — oldest purchase batch consumed first | `allocateProductPrices()` |
| BR-005 | Customer deposits are consumed FIFO — oldest deposit used first | `processDepositUsage()` |
| BR-006 | A cashier can only create invoices for their own branch | `SalesInvoiceController::create()` |
| BR-007 | Inventory transfer preserves source stock | `InventoryTransactionController` |
| BR-008 | A new branch does not automatically receive an inventory location | `BranchController::store()` — no inventory creation |
| BR-009 | Purchase invoices increment `InventoryProduct.quantity` using upsert | `PurchaseInvoice::saveDetails()` |
| BR-010 | Each employee has exactly one wage record | `EmployeeController + Employee::boot()` (currently creates two — BUG) |

### Proposed Business Rules (Not Yet Enforced)

| Rule ID | Rule | Priority |
|---|---|---|
| BR-P001 | An appointment cannot overlap another confirmed appointment for the same provider | Critical |
| BR-P002 | Appointment `end_date` must equal `start_date + service.duration` | High |
| BR-P003 | A cancelled appointment triggers deposit policy (if deposit was taken) | High |
| BR-P004 | A refunded sale reverses related commissions | High |
| BR-P005 | Inventory movements must preserve an immutable history | High |
| BR-P006 | Branch-scoped users must not access another branch's data | High |
| BR-P007 | A completed transaction cannot be silently modified | High |
| BR-P008 | Service consumable products are deducted from inventory at invoice creation | High |
| BR-P009 | `Customer.last_service` must be updated when an invoice is completed | Medium |
| BR-P010 | Commission is calculated per `service_employees.commission_type/value` | Critical |
| BR-P011 | Tip is tracked separately from service revenue | Medium |
| BR-P012 | A no-show appointment may trigger a no-show fee | Medium |
| BR-P013 | A draft invoice does not trigger inventory deduction | Medium |
