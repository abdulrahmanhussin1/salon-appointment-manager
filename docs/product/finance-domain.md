# Finance Domain — Salon Appointment Manager

> Audit of expense management, financial reporting, branch-level financials, and trustworthiness of current financial data.

---

## Financial Data Sources

The system derives financial data from these tables:

| Table | Role |
|---|---|
| `sales_invoices` | Revenue — services + products |
| `sales_invoice_details` | Line-level revenue breakdown |
| `expenses` | Operational expenses |
| `purchase_invoices` | Cost of goods purchased |
| `customer_transactions` | Deposit flows |
| `supplier_transactions` | Supplier payment records |
| `inventory_products` | Current stock valuation |
| `supplier_prices` | FIFO cost per product batch |

---

## Expense Model

### Schema

```sql
expenses:
  id
  expense_type_id     FK → expense_types (category)
  description         TEXT NULL
  amount              DECIMAL(15,2)      ← total expense amount
  paid_amount         DECIMAL(15,2)      ← amount actually paid
  balance             DECIMAL(15,2)      ← amount remaining unpaid
  paid_at             TIMESTAMP
  invoice_number      VARCHAR NULL
  payment_method_id   FK → payment_methods
  status              ENUM('active','inactive')
  branch_id           FK → branches
  created_by          FK → users
  updated_by          FK → users
```

### Assessment

| Capability | Status | Notes |
|---|---|---|
| Expense category | ✅ | `expense_type_id` — configurable lookup |
| Branch assignment | ✅ | `branch_id` present |
| Payment method | ✅ | FK to payment_methods |
| Date tracking | ✅ | `paid_at` timestamp |
| User accountability | ✅ | `created_by` |
| Partial payment | ✅ (schema) | `amount`, `paid_amount`, `balance` — but `balance` not auto-calculated |
| External invoice reference | ✅ | `invoice_number` |
| Recurring expenses | ❌ | Not modeled |
| Attachments (receipt photos) | ❌ | No attachment model |
| Approval workflow | ❌ | No `approved_by`, `approved_at` |
| Reporting | ⚠️ Partial | Monthly totals visible; category/branch breakdown missing |

### Critical Issue: Balance Not Auto-Calculated

The `balance` field is stored but there is no evidence it is computed server-side (`balance = amount - paid_amount`). It may be entered manually. If staff enter incorrect values, the expense record is unreliable.

### Critical Issue: Daily Report Filters Expenses by Cash Only

In `ReportController::dailyRevenues()`:
```php
$paymentMethod = PaymentMethod::where('name', 'cash')->first();
$expenses = Expense::where('payment_method_id', $paymentMethod->id)...->sum('paid_amount');
```

Only cash expenses are subtracted from revenue. Non-cash expenses (bank transfers, credit card payments) are excluded. This means:
- Net revenue figures are overstated when non-cash expenses exist
- The daily report cannot be trusted as a financial snapshot

---

## Revenue Recognition

### How Revenue Is Calculated

1. `sales_invoices.net_total` = gross - discount + tax
2. `SalesInvoiceDetail.subtotal` per line = `(price × qty) - discount + tax`
3. Deposits used are tracked in `invoice_deposit` but subtracted from `balance_due`, not from revenue

### Revenue Recognition Issues

| Issue | Impact |
|---|---|
| Deposits applied to invoice reduce balance_due but don't reduce revenue | Deposits pre-paid in prior periods appear as revenue when the service is delivered — correct behavior, but deposits received in a period are not recognized as deferred revenue |
| Tax is included in `net_total` | Revenue and tax are not cleanly separated in aggregate reports |
| `total_amount` vs `net_total` inconsistency | Some reports use `total_amount` (gross), others use `net_total` — not consistent |
| Inactive invoices are excluded from reports | ✅ Correct |
| Draft invoices — unclear | If cashier submits a draft, is it excluded from revenue? Reports filter by `status='active'` so yes — but inventory was already deducted (bug) |

---

## Branch-Level Financials

### Current State

Most financial data is branch-tagged (`branch_id` on `sales_invoices`, `expenses`, `purchase_invoices`). However:
- **No report filters by branch** — all existing reports aggregate across all branches
- The owner cannot see Branch A vs Branch B revenue
- The branch manager cannot isolate their own performance

### Required Branch-Level Reports

1. Branch revenue (services + products) by period
2. Branch expenses by period
3. Branch gross margin (revenue - COGS - expenses)
4. Branch employee performance
5. Branch inventory valuation

---

## Supplier Payment Tracking

`supplier_transactions` tracks payments to suppliers. Created when a purchase invoice is saved.

**Assessment:**
- Schema: `supplier_id`, `reference_id`, `reference_type='purchase'`, `amount`, `notes`
- No UI to view supplier account balance or outstanding payments
- No partial payment to supplier (no `paid_amount` / `balance` on supplier transaction)
- No supplier statement view

---

## Financial Reporting Trust Level

| Report | Trustworthiness | Issue |
|---|---|---|
| Daily Revenue | ⚠️ Low | Cash-only expense filter; commission placeholder |
| Total Daily Revenue | ⚠️ Low | Uses `total_amount` not `net_total`; no branch filter |
| Daily Summary | ❌ Unreliable | Commission = revenue (wrong) |
| Monthly Summary | ⚠️ Partial | No COGS; commissions omitted |
| Employee Report | ⚠️ Partial | Revenue visible; commissions not computed |
| Stock Report | ⚠️ Partial | Uses latest price, not FIFO; minor bugs |
| Store Balance Report | ⚠️ Partial | Current month only; price inconsistency |

---

## What Would Enable Trustworthy Financial Reporting

### Minimum Requirements

1. **Fix expense filter** — include all payment methods in expense totals
2. **Calculate commission properly** — at invoice creation, store `commission_amount` per line
3. **Add branch filter to all reports** — `WHERE branch_id = ?`
4. **Fix daily summary commission** — use `SUM(commission_amount)` not `SUM(subtotal)`
5. **Separate tax from revenue** — net revenue = `net_total - invoice_tax`

### Important Additions

6. **Cost of goods sold** — deduct FIFO cost from product revenue in reports
7. **Service consumable cost** — once consumable deduction is implemented, include it in service CoGS
8. **Payroll/commission reports** — accurate payroll data alongside revenue
9. **Consistent metric naming** — standardize on `net_total` vs `total_amount` across reports

### Future Additions

10. **Period comparison** — this period vs. last period
11. **Budget vs. actual** — if targets are configured
12. **Cash flow statement** — cash in (sales) vs. cash out (expenses + supplier payments)
13. **Accounts receivable** — outstanding `balance_due > 0` per customer

---

## Customer Deposit Accounting

Customer deposits represent **deferred revenue** — money received before service is delivered.

### Current Treatment

1. Deposit received → `CustomerTransaction` created with `amount > 0`, `status='available'`
2. Deposit applied to invoice → original `CustomerTransaction.status = 'used'`, new record with `amount < 0`
3. `sales_invoices.invoice_deposit` stores the amount used

### Issues

1. The deposit **receipt** at time of deposit creation is not modeled as an invoice/document — the customer has no confirmation of their deposit
2. Deposits are shown as a positive line in `TotalDailyRevenues` via `CustomerTransaction` — but deposit is only in the report if associated with that date, even if the service hasn't been delivered yet
3. No report showing total outstanding deposits (customer liability)

### Required

- Deposit receipt (document) issued at time of deposit
- Outstanding deposit report (total deposits not yet used, by customer and in aggregate)
- Deposit expiry if business policy requires it
