# Business Rules — Salon Appointment Manager

> Canonical business rules document. Each rule is marked as CONFIRMED (enforced by current code), PROPOSED (should be implemented), or VIOLATED (stated in model but not enforced).

---

## How to Read This Document

- **CONFIRMED** — Rule is implemented and enforced in current application code. Evidence provided.
- **VIOLATED** — Rule appears intended (schema hints at it) but is not enforced in runtime code.
- **PROPOSED** — Rule does not currently exist; should be implemented based on domain requirements.

---

## BR-001 — Sales invoice requires an active customer

**Status:** CONFIRMED

**Rule:** A sales invoice cannot be created for an inactive customer.

**Evidence:** `SalesInvoiceController::store()`:
```php
if (!$customer || $customer->status !== 'active') {
    throw new \Exception('Selected customer is not active.');
}
```

---

## BR-002 — Every invoice line item must have an assigned provider

**Status:** CONFIRMED

**Rule:** Each line item (service or product) on a sales invoice must be attributed to an active employee as the service provider.

**Evidence:** `validateInvoiceData()`:
```php
'items.*.provider_id' => 'required|integer|exists:employees,id',
```

---

## BR-003 — Product inventory must be sufficient before sale

**Status:** CONFIRMED

**Rule:** A product cannot be sold if available inventory is insufficient.

**Evidence:** `checkInventoryAvailability()` throws exception on insufficient stock. The outer `DB::transaction` with 5 deadlock retries wraps this check.

---

## BR-004 — Product prices follow FIFO — oldest purchase batch consumed first

**Status:** CONFIRMED

**Rule:** When a product is sold, the price from the oldest purchase batch with remaining quantity is used (FIFO).

**Evidence:** `allocateProductPrices()` sorts `supplier_prices` by `created_at ASC`.

---

## BR-005 — Customer deposits are consumed FIFO

**Status:** CONFIRMED

**Rule:** When a customer's deposit is applied to an invoice, the oldest available deposit records are consumed first.

**Evidence:** `processDepositUsage()` orders `CustomerTransaction` by `created_at ASC`.

---

## BR-006 — Cashier can only create invoices for their own branch

**Status:** CONFIRMED

**Rule:** A user with the `cashier` role is restricted to creating invoices for their assigned branch.

**Evidence:** `SalesInvoiceController::create()` limits the branch dropdown, and `SalesInvoiceController::validateInvoiceData()` validates server-side that `$validated['branch_id'] === Auth::user()->employee->branch_id`, returning 403 otherwise (enforced via GAP-001 / NFR-003).

---

## BR-007 — Inventory transfer preserves source stock

**Status:** CONFIRMED

**Rule:** Stock transferred from source must be decremented at source before incrementing at destination.

**Evidence:** `InventoryTransactionController` decrements source and uses `firstOrCreate` on destination inventory before incrementing (fixed in REQ-005, BUG-004 resolved).

---

## BR-008 — New branch does not auto-receive an inventory location

**Status:** CONFIRMED (by absence)

**Rule (actual behavior):** When a branch is created, no inventory is automatically created. Staff must manually create an inventory and associate it with the branch before stock can be received.

**Assessment:** This may be intentional but is a friction point. A branch cannot receive purchase invoices until its inventory is set up.

---

## BR-009 — Purchase invoices increment inventory via upsert

**Status:** CONFIRMED

**Rule:** When a purchase invoice is saved, if a product already exists in the inventory, its quantity is incremented. If not, a new record is inserted.

**Evidence:** `PurchaseInvoice::saveDetails()` uses `updateOrCreate` on `InventoryProduct`.

---

## BR-010 — Each employee has exactly one wage record

**Status:** CONFIRMED (Fixed in REQ-004)

**Intended rule:** One `EmployeeWage` record per employee.

**Resolution:** Fixed in REQ-004 (BUG-002). Explicit duplicate creation removed from `EmployeeController::store()`, keeping sole creation in model boot event.

---

## BR-011 — Appointment edit uses route parameter for identification

**Status:** CONFIRMED (Fixed in REQ-002)

**Intended rule:** `PUT /appointments/{id}` should update the appointment identified by the route `{id}` parameter.

**Resolution:** Fixed in REQ-002 (BUG-003). `AppointmentController::update()` binds route model/parameter instead of reading unsanitized `$request->id`.

---

## BR-012 — Service price_can_change allows price override at POS

**Status:** CONFIRMED (Fixed in REQ-006)

**Intended rule:** If `service.price_can_change = true`, the cashier may set a custom price at POS.

**Resolution:** Fixed in REQ-006 (BUG-011). `processService()` respects `$service->price_can_change` and accepts custom price overrides when set.

---

## BR-013 — Appointment routes require authentication

**Status:** CONFIRMED (Fixed in REQ-001)

**Intended rule:** Only authenticated, authorized users may create, update, or delete appointments.

**Resolution:** Fixed in REQ-001 (SEC-001). Appointment routes are placed inside the `auth` and `checkRole` middleware groups.

---

## BR-P001 — Appointments cannot overlap for the same provider

**Status:** CONFIRMED (Implemented in REQ-010)

**Rule:** A provider cannot be booked for two appointments whose time windows overlap when both appointments are in an active state (requested, confirmed, checked_in, in_service).

**Overlap condition:**
```
new.start_datetime < existing.end_datetime
AND new.end_datetime > existing.start_datetime
AND new.provider_id = existing.provider_id
AND existing.status NOT IN ('cancelled', 'rejected', 'no_show', 'expired', 'completed')
```

**Priority:** Must Have

---

## BR-P002 — Appointment end time = start time + service duration

**Status:** CONFIRMED (Implemented in REQ-011)

**Rule:** When booking an appointment, the system must compute `end_datetime = start_datetime + service.duration` (in minutes). Manual end time entry should not be permitted unless the override is explicitly enabled.

**Priority:** Must Have

---

## BR-P003 — Cancelled appointment triggers deposit policy (PROPOSED)

**Status:** PROPOSED

**Rule:** If a customer cancels an appointment within the configured cancellation window (e.g., < 24 hours), the booking deposit may be forfeited per business policy. If outside the window, the deposit must be refunded or credited.

**Depends on:** Appointment status lifecycle, cancellation policy configuration, deposit at booking.

**Priority:** Should Have

---

## BR-P004 — Refunded sale reverses related commissions (PROPOSED)

**Status:** PROPOSED

**Rule:** When a sale is refunded, any commission amounts recorded on the refunded line items must be reversed (subtracted from the employee's commission for the period).

**Depends on:** Refund workflow implementation, commission calculation at invoice creation.

**Priority:** Should Have

---

## BR-P005 — Inventory movements must preserve immutable history (PROPOSED)

**Status:** PROPOSED

**Rule:** Once an inventory transaction is created, it cannot be deleted or modified. Corrections are made by creating reversing transactions.

**Current state:** `InventoryTransaction` records can be hard-deleted. No audit-proof history.

**Priority:** Should Have

---

## BR-P006 — Branch-scoped users must not access another branch's data

**Status:** CONFIRMED (Implemented in REQ-013, REQ-020, and GAP-001)

**Rule:** A user whose linked employee belongs to Branch A must not be able to read or write data belonging to Branch B (invoices, expenses, inventory, employees).

**Exception:** Users with owner/admin role may access all branches.

**Priority:** Must Have

---

## BR-P007 — Completed transactions cannot be silently modified

**Status:** CONFIRMED (Implemented in REQ-015)

**Rule:** A `sales_invoice` with `status='active'` cannot be modified without going through an explicit void/cancel or refund workflow. Modification must generate an audit record.

**Resolution:** Enforced via REQ-015 void workflow with audit trail (`voided_by`, `voided_at`, `void_reason`) and strict rollback.

**Priority:** Should Have

---

## BR-P008 — Service consumable products are deducted at invoice creation

**Status:** CONFIRMED (Implemented in REQ-014)

**Rule:** When a service is sold on an invoice, all products listed in `service_products` for that service must be deducted from inventory in proportion to service quantity.

**Resolution:** Implemented in `SalesInvoiceController` on active invoice store and draft activation via `deductServiceConsumables()`.

**Priority:** Must Have

---

## BR-P009 — Customer.last_service updated on invoice completion

**Status:** CONFIRMED (Implemented in REQ-007)

**Rule:** When a sales invoice with status `active` is created for a customer, `customers.last_service` must be updated to the invoice date.

**Resolution:** Implemented in REQ-007 across invoice store, activation, and void reversal.

**Priority:** Must Have

---

## BR-P010 — Commission calculated per service_employees configuration

**Status:** CONFIRMED (Implemented in REQ-012)

**Rule:** When a service line item is created on a sales invoice, the commission amount must be calculated using the `service_employees` record for that service/provider combination, and stored on the `sales_invoice_detail` record.

**Calculation:**
- `commission_type = 'percentage'`: `commission = (subtotal - discount) × (commission_value / 100)`
- `commission_type = 'value'`: `commission = commission_value × quantity`

**Priority:** Must Have

---

## BR-P011 — Draft invoices do not trigger inventory deduction

**Status:** CONFIRMED (Implemented in REQ-008)

**Rule:** Creating an invoice with `status='draft'` must NOT deduct inventory. Inventory deduction occurs only when the invoice transitions from `draft` to `active`.

**Resolution:** Implemented in REQ-008 for retail products and extended in REQ-014 for service consumables.

**Priority:** Must Have

---

## BR-P012 — No-show appointment may trigger a no-show fee (PROPOSED)

**Status:** PROPOSED

**Rule:** If an appointment is marked `no_show`, and the business has configured a no-show fee, the system may create a charge (expense or deduct from customer deposit).

**Depends on:** Appointment status lifecycle, no-show fee configuration.

**Priority:** Could Have

---

## BR-P013 — Expense balance = amount - paid_amount (PROPOSED)

**Status:** PROPOSED

**Rule:** The `expense.balance` field must always equal `expense.amount - expense.paid_amount`. It must be computed by the server, not entered manually.

**Current state:** `balance` appears to be manually entered.

**Priority:** Should Have

---

## Business Rule Summary Table

| Rule | Status | Priority |
|---|---|---|
| BR-001: Invoice requires active customer | CONFIRMED | — |
| BR-002: Line item requires provider | CONFIRMED | — |
| BR-003: Sufficient inventory before sale | CONFIRMED | — |
| BR-004: FIFO product pricing | CONFIRMED | — |
| BR-005: FIFO deposit consumption | CONFIRMED | — |
| BR-006: Cashier restricted to own branch | CONFIRMED | Fixed (GAP-001) |
| BR-007: Transfer preserves source stock | CONFIRMED | Fixed (REQ-005) |
| BR-008: Branch has no auto-inventory | CONFIRMED | — |
| BR-009: Purchase invoice upserts inventory | CONFIRMED | — |
| BR-010: One wage per employee | CONFIRMED | Fixed (REQ-004) |
| BR-011: Appointment edit uses route param | CONFIRMED | Fixed (REQ-002) |
| BR-012: price_can_change honored at POS | CONFIRMED | Fixed (REQ-006) |
| BR-013: Appointment routes require auth | CONFIRMED | Fixed (REQ-001) |
| BR-P001: No provider double-booking | CONFIRMED | Implemented (REQ-010) |
| BR-P002: End time = start + duration | CONFIRMED | Implemented (REQ-011) |
| BR-P003: Cancellation deposit policy | PROPOSED | Should Have |
| BR-P004: Refund reverses commissions | PROPOSED | Should Have |
| BR-P005: Inventory history is immutable | PROPOSED | Should Have |
| BR-P006: Branch data isolation | CONFIRMED | Implemented (REQ-013, REQ-020, GAP-001) |
| BR-P007: Completed invoices not modifiable | CONFIRMED | Implemented (REQ-015) |
| BR-P008: Service consumable deduction | CONFIRMED | Implemented (REQ-014) |
| BR-P009: last_service updated on invoice | CONFIRMED | Implemented (REQ-007) |
| BR-P010: Commission calculated at sale | CONFIRMED | Implemented (REQ-012) |
| BR-P011: Draft does not deduct inventory | CONFIRMED | Implemented (REQ-008) |
| BR-P012: No-show fee | PROPOSED | Could Have |
| BR-P013: Expense balance auto-computed | PROPOSED | Should Have |
