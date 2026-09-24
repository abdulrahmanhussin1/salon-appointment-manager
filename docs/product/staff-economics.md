# Staff Economics — Salon Appointment Manager

> Analysis of salary configuration, commission schema, and the gap between what is designed and what is actually computed at runtime.

---

## Overview

The staff economics domain is **schema-complete but runtime-incomplete**. The database supports a rich model for wages, commissions, and sales targets. None of the configured values are used to produce actual calculations — reports show revenue attributed to employees, but never the commission amounts employees have earned.

---

## Wage Model (EmployeeWage)

One `EmployeeWage` record per employee. Created automatically on employee creation (model boot event).

### Fields

| Field | Type | Meaning |
|---|---|---|
| `salary_type` | ENUM | `daily \| weekly \| monthly \| commission` |
| `basic_salary` | DECIMAL(10,2) | Base salary |
| `bonus_salary` | DECIMAL(10,2) | Fixed bonus |
| `allowance1/2/3` | DECIMAL(10,2) | Three generic allowances |
| `total_salary` | DECIMAL(10,2) | Sum field (not auto-calculated — manually entered) |
| `working_hours` | DECIMAL(10,2) | Expected daily working hours |
| `start_working_time` | TIME | Shift start time |
| `overtime_rate` | DECIMAL(10,2) | Rate per overtime hour |
| `penalty_late_hour` | DECIMAL(10,2) | Deduction per hour late |
| `penalty_absence_day` | DECIMAL(10,2) | Deduction per absent day |
| `sales_target_settings` | ENUM | `no \| total_sales \| employee_daily_service` |
| `break_time` | TIME | Break start time |
| `break_duration_minutes` | TINYINT | Break duration |

### What Is NOT Computed

| Computation | Status |
|---|---|
| `total_salary` auto-sum of components | ❌ Manual entry |
| Overtime calculation against worked hours | ❌ No time tracking |
| Penalty deduction against attendance | ❌ No attendance system |
| Sales target evaluation | ❌ `sales_target_settings` stored but never evaluated |

---

## Commission Model (ServiceEmployee)

Per-service commission settings per employee. Set in `service_employees` pivot table.

### Fields

| Field | Type | Meaning |
|---|---|---|
| `commission_type` | ENUM | `percentage \| value` |
| `commission_value` | DECIMAL(10,2) | Percentage (0–100) or fixed amount |
| `is_immediate_commission` | BOOLEAN | Whether commission is paid immediately or deferred |

### Commission Calculation Logic (What Should Happen)

When a service line item is created on a sales invoice:

```
1. Look up service_employees WHERE service_id = X AND employee_id = provider_id
2. IF commission_type = 'percentage':
     commission = (subtotal - discount) × (commission_value / 100)
   IF commission_type = 'value':
     commission = commission_value × quantity
3. Record commission against the sales_invoice_detail
4. On refund: reverse the commission
```

### Commission Calculation (What Actually Happens)

```php
// ReportController::dailySummary()
'services_commissions' => $serviceSales->sum('subtotal'),
// ← This is REVENUE, not commission. Placeholder never replaced.
```

**Result:** The commission report shows 100% of revenue as "commission" — making it completely meaningless for payroll decisions.

### Known Gap: Product Commissions

The commission schema (`service_employees`) links employees to services only. There is no mechanism for product sale commissions. If the business awards commission on product sales, this is unhandled.

---

## What Is Required (Commission System)

### 1. Persist Calculated Commission at Invoice Creation

Add a `commission_amount` column to `sales_invoice_details`:
```sql
ALTER TABLE sales_invoice_details
  ADD commission_amount DECIMAL(10,2) DEFAULT 0,
  ADD commission_type ENUM('percentage','value') NULL,
  ADD commission_value DECIMAL(10,2) DEFAULT 0;
```

At `SalesInvoiceController::processService()`:
```php
$serviceEmployee = ServiceEmployee::where('service_id', $service->id)
    ->where('employee_id', $item['provider_id'])
    ->first();

$commission = 0;
if ($serviceEmployee) {
    $commission = $serviceEmployee->commission_type === 'percentage'
        ? ($grossTotal - $discount) * ($serviceEmployee->commission_value / 100)
        : $serviceEmployee->commission_value * $item['quantity'];
}

return [
    ...
    'invoiceItem' => [..., 'commission_amount' => $commission],
];
```

### 2. Commission Report by Employee / Period

```
Employee Commission Report:
  - Employee name
  - Period (date range)
  - Services delivered count
  - Service revenue (gross)
  - Discount on services
  - Net service revenue
  - Commission rate (avg)
  - Commission earned
  - Immediate commission (paid at time of service)
  - Deferred commission (to be settled in payroll)
```

### 3. Payroll Run

A future payroll run would:
1. Aggregate `basic_salary + bonus + allowances`
2. Add deferred commissions for the period
3. Add tips (if implemented)
4. Subtract penalties (if attendance tracked)
5. Produce a payroll record per employee per period

---

## Attendance / Time Tracking (Schema Partially Ready)

`employee_wages` has:
- `start_working_time` — shift start
- `working_hours` — expected duration
- `break_time` and `break_duration_minutes`
- `penalty_late_hour` and `penalty_absence_day`

`employees` has:
- `finger_print_code` — unique nullable fingerprint ID

**What's missing:**
- No attendance/timesheet table
- No clock-in/clock-out mechanism
- No integration with fingerprint system
- No late/absence tracking

Without attendance data, overtime and penalty calculations cannot run.

---

## Sales Targets

`employee_wages.sales_target_settings` options:
- `no` — no target
- `total_sales` — employee targets based on total sales
- `employee_daily_service` — target per day

**What's missing:**
- No target amount/value configured alongside the setting
- No target tracking or comparison in reports
- No alert when employee is below target

---

## Tips (Missing)

**Industry standard:** Tips are collected at POS and attributed to the serving employee.

**Current state:** No tip field anywhere in the schema.

**Required:**
- `sales_invoice_details.tip_amount DECIMAL(10,2) DEFAULT 0`
- Or invoice-level: `sales_invoices.tip_amount` with distribution logic
- Commission and tip tracked separately for payroll clarity

---

## Commission on Refund

**Rule (industry standard):** If a sale is refunded, the commission on that sale should be reversed.

**Current state:** No refund system, so this is moot. Must be designed alongside the refund workflow.

**Required:** When a refund is processed, reverse the `commission_amount` from the `sales_invoice_details` being refunded.

---

## Staff Economics Gap Summary

| Gap | Severity | Business Impact |
|---|---|---|
| Commission not calculated at invoice | **Critical** | Payroll decisions made outside system (Excel) |
| Commission report shows revenue not commission | **Critical** | Report is misleading / unusable |
| No product commission mechanism | **High** | Product sales staff not compensated via system |
| No payroll run | **High** | System cannot generate payslips |
| No attendance / time tracking | **High** | Overtime and penalty rules cannot apply |
| `total_salary` is manual, not computed | **Medium** | Risk of data entry error |
| Sales target amount not stored | **Medium** | Target feature partially designed |
| No tip tracking | **Medium** | Tips unrecorded and untaxed |
| Commission reversal on refund | **Medium** | Depends on refund feature being built |
