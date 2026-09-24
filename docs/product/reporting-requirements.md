# Reporting Requirements — Salon Appointment Manager

> Defines what business questions the product must be able to answer, current availability of each, and what is required to enable missing reports.

---

## Report Status Legend

| Status | Meaning |
|---|---|
| ✅ AVAILABLE | Report exists and produces correct data |
| ⚠️ PARTIAL | Report exists but data is incomplete, inaccurate, or missing filters |
| ❌ MISSING | Report does not exist |
| ❌ INCORRECT | Report exists but produces wrong data |

---

## Revenue Reports

| Business Question | Status | Notes |
|---|---|---|
| What was today's total revenue? | ⚠️ PARTIAL | Daily Revenue report exists but expense filter is cash-only |
| What is revenue for a date range? | ⚠️ PARTIAL | Available but no branch filter |
| What is revenue by branch? | ❌ MISSING | No branch filter on any report |
| What is revenue by service? | ❌ MISSING | No per-service revenue breakdown |
| What is revenue by service category? | ❌ MISSING | — |
| What is revenue by product? | ❌ MISSING | No per-product revenue breakdown |
| What is revenue by employee? | ⚠️ PARTIAL | Employee report shows subtotals, not commission |
| What is revenue by payment method? | ⚠️ PARTIAL | Cash vs. non-cash split visible in daily report |
| What was revenue this month vs. last month? | ❌ MISSING | No period comparison |
| What is the monthly revenue trend for the year? | ✅ AVAILABLE | Monthly summary chart |
| What is average transaction value (average ticket)? | ⚠️ PARTIAL | Computed in daily summary but not branch-filtered |

---

## Appointment Reports

| Business Question | Status | Notes |
|---|---|---|
| How many appointments were booked today? | ❌ MISSING | Calendar shows appointments but no count report |
| What is the appointment utilization rate? | ❌ MISSING | No status → cannot compute used vs. available slots |
| What is the cancellation rate? | ❌ MISSING | No cancellation status tracking |
| What is the no-show rate? | ❌ MISSING | No no-show status tracking |
| Which provider has the most appointments? | ❌ MISSING | No appointment analytics |
| Which service is most booked? | ❌ MISSING | — |
| What is the conversion rate (booked → completed)? | ❌ MISSING | No appointment → invoice link |

---

## Customer Reports

| Business Question | Status | Notes |
|---|---|---|
| How many new customers this month? | ✅ AVAILABLE | Monthly summary includes new customer count |
| Who are the top customers by revenue? | ❌ MISSING | No customer revenue ranking |
| Which customers haven't visited in 90+ days? | ❌ MISSING | `last_service` is never updated |
| What is the customer retention rate? | ❌ MISSING | No repeat visit tracking |
| What is a customer's visit history? | ❌ MISSING | No customer profile report with visit log |
| What is a customer's outstanding deposit balance? | ❌ MISSING | `customer_transactions` exists but no summary view |
| What is total outstanding customer deposits? | ❌ MISSING | No aggregate outstanding deposit report |
| How were customers acquired (source breakdown)? | ❌ MISSING | `added_from` field exists but no report |

---

## Staff / Commission Reports

| Business Question | Status | Notes |
|---|---|---|
| How many services did each employee deliver? | ✅ AVAILABLE | Employee summary report |
| What is each employee's revenue contribution? | ✅ AVAILABLE | Employee summary shows subtotals |
| What commission did each employee earn? | ❌ INCORRECT | Commission = revenue (placeholder; wrong) |
| What commission is owed for this period? | ❌ MISSING | No correct commission computation |
| What is each employee's performance vs. target? | ❌ MISSING | Target settings stored but not evaluated |
| Who are the top-performing employees? | ⚠️ PARTIAL | Ranking by revenue visible, not by commission |

---

## Inventory / Stock Reports

| Business Question | Status | Notes |
|---|---|---|
| What is current stock level per product? | ✅ AVAILABLE | Stock Report |
| What is current stock level per branch/inventory? | ✅ AVAILABLE | Stock Report with inventory filter |
| What is inventory valuation at cost? | ⚠️ PARTIAL | Store Balance uses latest price, not FIFO |
| What products are below reorder point? | ❌ MISSING | No reorder threshold defined |
| What stock moved in/out this period? | ⚠️ PARTIAL | Store Balance only for current month |
| What was consumed by services (consumables)? | ❌ MISSING | Consumables never deducted |
| What was damaged or wasted? | ❌ MISSING | No waste tracking |

---

## Financial / P&L Reports

| Business Question | Status | Notes |
|---|---|---|
| What are total expenses for this period? | ⚠️ PARTIAL | Monthly summary shows expense total; cash-only in daily |
| What are expenses by category? | ❌ MISSING | No category breakdown in any report |
| What are expenses by branch? | ❌ MISSING | No branch filter |
| What is the gross margin (revenue - COGS)? | ❌ MISSING | COGS not tracked in reports |
| What is net income (revenue - expenses)? | ⚠️ PARTIAL | `net_income` in monthly summary but expenses incomplete |
| What is branch-level P&L? | ❌ MISSING | No branch-filtered P&L |
| What are purchase costs this period? | ✅ AVAILABLE | Purchase totals in monthly/daily summary |
| What is the outstanding receivables (balance_due > 0)? | ❌ MISSING | No receivables report |
| What is the supplier account balance? | ❌ MISSING | `supplier_transactions` exists but no view |
| What is cash position (cash in - cash out)? | ❌ MISSING | No cash flow report |

---

## Operational Reports

| Business Question | Status | Notes |
|---|---|---|
| What is the daily cashier summary? | ⚠️ PARTIAL | Daily summary available but no shift-based isolation |
| What was the cash counted vs. system total? | ❌ MISSING | No cash reconciliation feature |
| How many draft invoices are open? | ❌ MISSING | No draft management view |
| What invoices have outstanding balances? | ❌ MISSING | No receivables aging |

---

## Required Reports — Priority Ordered

### Phase 1 (Must Have — Fixes Existing Broken Reports)

| Report | Action Required |
|---|---|
| Commission report | Fix commission calculation at invoice creation; report `SUM(commission_amount)` |
| Branch-filtered revenue report | Add `branch_id` filter to all existing report queries |
| Expense report (all methods) | Remove cash-only filter from daily revenue calculation |
| Daily summary (corrected commissions) | Replace `services_sales` with `SUM(commission_amount)` |

### Phase 2 (Should Have — Fills Critical Gaps)

| Report | Action Required |
|---|---|
| Service revenue breakdown | Group `sales_invoice_details` by `service_id` |
| Product revenue breakdown | Group `sales_invoice_details` by `product_id` |
| Top customer report | Sum invoice totals by `customer_id`, sort descending |
| Outstanding customer deposits | Sum available `customer_transactions` by customer |
| Expense by category | Group `expenses` by `expense_type_id` with branch filter |
| Gross margin (revenue - COGS) | Requires FIFO COGS computation at sale (future) |

### Phase 3 (Could Have — Advanced Analytics)

| Report | Action Required |
|---|---|
| Appointment utilization | Requires appointment status implementation |
| No-show / cancellation rates | Requires appointment status |
| Customer retention (repeat visits) | Requires `last_service` update and visit history |
| Customer acquisition source | Report on `customers.added_from` distribution |
| Cash flow statement | Sum cash in (sales cash) vs. cash out (expenses cash + supplier payments) |
| Branch P&L | Combine revenue - COGS - expenses per branch |
| Period comparison | Revenue this month vs. last month, this year vs. last |
| Attendance / payroll | Requires attendance system |
