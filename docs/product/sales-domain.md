# Sales Domain (POS) — Salon Appointment Manager

> Complete audit of the sales/POS workflow: what is implemented, what is broken, what is missing.

---

## POS Architecture

The POS is a **single-page JavaScript form** (Blade + Alpine.js) that submits a JSON payload to `POST /admin/sales_invoices`. It supports:
- Multi-item cart (services + products mixed)
- Customer selection with deposit balance display
- Branch selection
- Payment: cash + one non-cash method + deposit
- Draft vs. active invoice status

Invoice processing is entirely server-side in `SalesInvoiceController`.

---

## Sales Invoice Model

```sql
sales_invoices:
  id
  invoice_date        DATE
  status              ENUM('active','inactive','draft')
  total_amount        DECIMAL(15,2)   ← gross before discount/tax
  invoice_discount    DECIMAL(10,2)   ← total discount amount
  invoice_tax         DECIMAL(15,2)   ← total tax amount
  net_total           DECIMAL(15,2)   ← after discount + tax
  invoice_deposit     DECIMAL(15,2)   ← deposit consumed
  paid_amount_cash    DECIMAL(15,2)
  payment_method_id   FK → payment_methods
  payment_method_value DECIMAL(15,2)  ← non-cash payment
  balance_due         DECIMAL(15,2)   ← can be negative (overpaid)
  invoice_notes       TEXT NULL
  customer_id         FK
  branch_id           FK
  created_by          FK → users
  updated_by          FK → users (nullable)
```

---

## Feature Audit

### Cart

| Feature | Status | Notes |
|---|---|---|
| Add service line item | ✅ | Provider must be selected per line |
| Add product line item | ✅ | FIFO pricing from supplier_prices |
| Mixed service + product | ✅ | Both on same invoice |
| Quantity per line | ✅ | Min 1 |
| Per-line discount (%) | ✅ | Applied in `processProduct/processService` |
| Per-line tax (%) | ✅ | Applied per line |
| Per-invoice discount | ✅ | `invoice_discount` field |
| Remove line item | ✅ | Client-side only before submission |
| Edit line item after submit | ❌ | `edit()` returns 404 |
| Save as draft | ✅ | `status='draft'` accepted in validation |
| Price override (`price_can_change`) | ❌ BROKEN | Flag validated in request but ignored in `processService()` — always uses `service.price` |

### Product Pricing (FIFO)

Product price at POS is determined by FIFO from `supplier_prices`:
1. `allocateProductPrices()` — sorts `supplier_prices` by `created_at ASC`
2. Takes `customer_price` from oldest batch with remaining quantity
3. Multiple batches may be consumed if quantity spans batches

**Issue:** `getDetails()` endpoint for UI price preview uses **newest** price (`orderBy created_at desc`) — **opposite of FIFO**. The displayed price may differ from the actual invoice price.

### Payment Methods

| Payment Type | Status | Notes |
|---|---|---|
| Cash | ✅ | `paid_amount_cash` |
| Non-cash (single method) | ✅ | `payment_method_id` + `payment_method_value` |
| Deposit usage | ✅ | FIFO consumption from `customer_transactions` |
| Split: cash + non-cash | ✅ | Both can be > 0 simultaneously |
| Three+ payment methods | ❌ | Only one non-cash method per invoice |
| Partial payment | ✅ | `balance_due > 0` allowed |
| Overpayment | ✅ | `balance_due < 0` allowed (change given) |

### Invoice Lifecycle

| Action | Status | Notes |
|---|---|---|
| Create | ✅ | Full workflow with locking and FIFO |
| Read / View | ✅ | Index list + receipt view |
| Edit | ❌ | `edit()` returns 404 |
| Update | ❌ | `update()` returns 404 |
| Cancel / Void | ❌ | `destroy()` returns 404 |
| Refund | ❌ | No refund model or workflow |
| PDF receipt | ✅ | `showReceipt()` with DomPDF |
| Email receipt | ❌ | No email sending implemented |

### Refund Workflow (Missing)

When a sale needs to be reversed:

**Required behavior:**
1. Create a negative invoice (credit note) or explicit refund record
2. Restore inventory for returned products (re-add to stock)
3. Reverse deposit usage (restore customer balance)
4. Reverse commission for refunded items
5. Update financial reports

**Current state:** No refund model, no workflow, no UI. Must be designed from scratch.

**Proposed model:**
```sql
refunds:
  id
  original_invoice_id    FK → sales_invoices
  refund_date            DATE
  refund_reason          TEXT
  refund_amount          DECIMAL(15,2)
  refund_method          ENUM('cash','deposit_credit','original_method')
  status                 ENUM('pending','approved','processed')
  processed_by           FK → users
  created_by             FK → users
  timestamps

refund_details:
  id
  refund_id              FK → refunds
  sales_invoice_detail_id FK → sales_invoice_details
  quantity               INT
  refund_amount          DECIMAL(10,2)
```

---

## POS Workflow (End-to-End)

```
Cashier opens POS
  → Select customer (or create new inline)
  → System shows customer deposit balance
  → Select branch (if not cashier role)
  → Add service items: select service category → service → provider → qty → discount
  → Add product items: select product category → product → provider → qty → discount
  → System shows: subtotal, discount, tax, net total
  → Enter payment: cash amount + non-cash method + apply deposit
  → System shows: balance due
  → Submit → server validates → creates invoice → returns invoice_id
  → Client redirects to receipt page
```

**Friction points in current flow:**
1. Provider must be selected per line — even if one stylist served the customer for everything
2. Price shown in UI may differ from price recorded (FIFO preview mismatch)
3. If submission fails, form is not preserved — customer must re-enter everything
4. No appointment pre-fill — receptionist re-enters what's already in the appointment

---

## Draft Invoice Behavior

`status='draft'` is accepted in validation and stored. However:
- **Inventory is deducted** even for draft invoices (same code path as `active`)
- This is **incorrect** — draft means not yet committed; inventory should not be deducted until invoice is finalized

**Required behavior:** Draft → Active transition should trigger inventory deduction. Cancelling a draft should not affect inventory.

---

## Tax Handling

Tax is applied **per line item** as a percentage. There is no system-level tax configuration.

Issues:
- Tax rate must be entered per line manually — prone to inconsistency
- No VAT/GST registration number on invoice
- No tax-exclusive vs tax-inclusive pricing mode
- Invoice shows `invoice_tax` total but individual line tax percentages may differ

**Required:**
- System-level default tax rate configuration (in `admin_panel_settings`)
- Option to configure tax as inclusive or exclusive of price
- VAT/GST number on receipts

---

## Cashier Operations / Shift Management (Missing)

**Industry standard:**
- Cashier opens a shift with starting cash amount
- All transactions recorded against that shift
- At end of shift: system cash total vs. actual count reconciliation
- Discrepancy is flagged

**Current state:** No shift model. No cash drawer concept. Cashier accountability is limited to `created_by` on invoices.

**Required:**
```sql
cashier_shifts:
  id
  cashier_id (employee)
  branch_id
  opened_at
  closed_at (nullable)
  opening_cash
  closing_cash (nullable)
  system_cash_total
  discrepancy (nullable)
  notes
```

---

## Incomplete / Broken Behaviors Summary

| Issue | Severity | Detail |
|---|---|---|
| `price_can_change` ignored | HIGH | Service price always set to `service.price`; override not applied |
| Draft deducts inventory | HIGH | Inventory deducted immediately on draft creation |
| FIFO UI price mismatch | MEDIUM | Preview shows newest price, invoice charges FIFO price |
| No invoice edit | CRITICAL | Any error requires voiding and re-creating |
| No invoice void/cancel | CRITICAL | Inactive invoices exist in DB but no workflow |
| No refund | CRITICAL | Cannot process returns |
| `InventoryTransaction` amounts zero | MEDIUM | Sales transaction log is useless for auditing |
| Nested transactions in loop | LOW | Each product creates nested `DB::transaction()` inside outer — potential deadlock |
| No shift/cash reconciliation | MEDIUM | Cashier accountability gap |
