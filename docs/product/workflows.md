# Business Workflows — Salon Appointment Manager

This document traces the actual implementation of major workflows as found in the codebase. Format: **Trigger → Validation → Business Logic → DB Writes → Events/Jobs → Side Effects → Response/UI**.

---

## 1. Customer Creation

**Trigger:** POST `/admin/customers` (from AJAX modal in sales invoice page, or from customer list page)

**Validation:** `CustomerRequest` — validates name, salutation, gender, email (unique), phone (unique), status, added_from, is_vip, dob, notes, address

**Business Logic:**
1. Create `Customer` record
2. If AJAX request AND `deposit > 0`:
   - Create `CustomerTransaction` with `reference_type='deposit'`, `reference_id=0`, `amount=$request->deposit`, `status='available'`

**DB Writes:**
- `customers` (insert)
- `customer_transactions` (insert, conditional)

**Events/Jobs:** None

**Side Effects:** None

**Response:**
- AJAX: JSON `{success, customer_id, customer_name, customer_phone, customer_deposit}`
- Form: Redirect back with SweetAlert success

---

## 2. Staff/User Creation

### 2a. Employee Creation

**Trigger:** POST `/admin/employees`

**Validation:** `EmployeeRequest` (name, email unique, phone, national_id unique, status, branch_id, employee_level_id, salary fields, service_id[], commission_type/value per service)

**Business Logic:**
1. Upload photo → `uploads/images/employees/`
2. Upload id_card → `uploads/images/employees/id-cards/`
3. Begin DB transaction
4. Create `Employee`
5. **Note:** `Employee::boot()->created` also fires `EmployeeWage::create()` automatically — then `EmployeeController` **also** explicitly creates `EmployeeWage`. **Double creation confirmed — BUG.**
6. If `service_id` array present: create `ServiceEmployee` records per service with commission config

**DB Writes:**
- `employees` (insert)
- `employee_wages` (insert — twice due to bug)
- `service_employees` (insert, conditional)

**Events/Jobs:** None

**Side Effects:** File upload to local storage

**Response:** Redirect back with SweetAlert success; on error: `dd($th->getMessage())` in catch block — **exposes internal errors in production**

### 2b. User (Login Account) Creation

**Trigger:** POST `/admin/users`

**Validation:** `UserRequest`

**Business Logic:**
1. Upload photo → `uploads/images/users/`
2. Create `User` with hashed password
3. Assign Role (from `role_id`)
4. Sync permissions: role permissions + any additional direct permissions from request

**DB Writes:**
- `users` (insert)
- `model_has_roles`, `model_has_permissions` (spatie tables)

**Response:** Redirect back with SweetAlert success

---

## 3. Branch Creation

**Trigger:** POST `/admin/branches`

**Validation:** `BranchRequest` (name, phone unique, email unique, status, address)

**Business Logic:** Simple `Branch::create()`

**DB Writes:** `branches` (insert)

**Note:** No inventory is auto-created for a new branch. The system assumes inventory must be created separately via `InventoryController`. This means a new branch cannot receive purchase invoices until an inventory is manually associated.

---

## 4. Product Creation

**Trigger:** POST `/admin/products`

**Validation:** `ProductRequest`

**Business Logic:**
1. Handle file upload (image)
2. `Product::create()` — code auto-generated in `creating` boot event (latest code + 1, starting at 100001)
   - **Race condition:** non-atomic code generation

**DB Writes:** `products` (insert)

**Note:** Product has no initial inventory entry — stock only appears after a purchase invoice is processed.

---

## 5. Service Creation

**Trigger:** POST `/admin/services`

**Validation:** `ServiceRequest`

**Business Logic:**
1. Handle image upload
2. Begin transaction
3. Create `Service`
4. Attach tools (if provided): create `ServiceTool` records
5. Attach products (if provided): create `ServiceProduct` records
6. Attach employees with commission (if provided): create `ServiceEmployee` records

**DB Writes:**
- `services` (insert)
- `service_tools` (insert, conditional)
- `service_products` (insert, conditional)
- `service_employees` (insert, conditional)

**Side Effects:** On catch: `dd($th->getMessage())` — **exposes errors in production**

---

## 6. Appointment Creation

**Trigger:** POST `/appointments` (route is **outside auth middleware** — unauthenticated access possible)

**Validation:** NONE — raw `Request` with no validation rules

**Business Logic:**
1. `Appointment::create()` with `customer_id`, `provider_id`, `service_id`, `start_date`, `end_date`, `created_by`

**DB Writes:** `appointments` (insert)

**Events/Jobs:** None

**Side Effects:** None

**Security Issue:** Route is not protected. No input validation. No appointment conflict checking.

---

## 7. Appointment Rescheduling

**Trigger:** PUT `/appointments/{id}`

**Validation:** NONE

**Business Logic:**
1. `Appointment::findOrFail($request->id)` — uses `$request->id`, **ignores route parameter `{id}`**
2. Update `customer_id`, `provider_id`, `service_id`, `start_date`, `end_date`, `updated_by`

**Note:** No conflict checking, no status validation, no business rules.

---

## 8. Appointment Cancellation

**Status:** NOT IMPLEMENTED. No status field in appointments table. No cancel endpoint.

---

## 9. Appointment Completion

**Status:** NOT IMPLEMENTED. No status field in appointments table.

---

## 10. No-Show Behavior

**Status:** NOT IMPLEMENTED.

---

## 11. Order (Sales Invoice) Creation

**Trigger:** POST `/admin/sales_invoices` (Ajax JSON from POS page)

**Validation (inline):** `validateInvoiceData()` — customer_id, items[], payment_method_id, deposit, invoice_date, branch_id, status, cash_payment, payment_method_value

**Business Logic:**
1. Begin outer DB transaction (with 5 deadlock retries)
2. Lock customer row for update (`lockForUpdate()`)
3. Verify customer is `active`
4. For each item:
   - **Product:** inner nested `DB::transaction()` → lock product → check inventory availability (sum of all `inventory_products`) → FIFO price allocation from `supplier_prices` → deduct inventory (FIFO by `created_at` from `inventory_products`) → create `InventoryTransaction` + `InventoryTransactionDetail`
   - **Service:** fetch service, calculate gross/discount/tax
5. Create `SalesInvoice` with totals
6. Create `SalesInvoiceDetail` records (via `createMany`)
7. If deposit > 0: `processDepositUsage()` → lock available `CustomerTransaction` rows → consume deposits FIFO → create negative-amount `CustomerTransaction` records → update `SalesInvoice.invoice_deposit` and `balance_due`

**DB Writes:**
- `sales_invoices` (insert)
- `sales_invoice_details` (insert many)
- `inventory_products` (decrement quantity, per FIFO)
- `inventory_transactions` (insert)
- `inventory_transaction_details` (insert)
- `customer_transactions` (update status, update amount, insert usage records)
- `sales_invoices` (update — invoice_deposit, balance_due)

**Events/Jobs:** None

**Side Effects:** None (no email, no notification)

**Response:** JSON `{invoice_id}`

**Known Issues:**
1. Nested `DB::transaction()` inside outer transaction for each product — unnecessary nesting, potential deadlock amplification
2. `InventoryTransaction` created with `total_before_discount=0`, `net_total=0` for sales — incomplete record
3. `InventoryTransactionDetail` uses `transaction_type='transfer'` even for sales — incorrect type
4. `source_inventory_id` is taken from `transactions[0]['inventory_id']` only (first inventory used) — multi-inventory deduction not tracked correctly in transaction header

---

## 12. Payment Flow

**Trigger:** Part of Sales Invoice creation

**Confirmed Payment Types:**
- `paid_amount_cash` — cash paid
- `payment_method_value` — non-cash method (credit card, etc.)
- `invoice_deposit` — pre-paid customer deposit applied

**Partial Payment:** Supported via `balance_due = net_total - paid_cash - paid_method - deposit`

**Deposit Usage Logic:**
1. Find `CustomerTransaction` rows with `status='available'` and `amount > 0` ordered by `created_at` (FIFO)
2. Lock with `lockForUpdate()`
3. Consume deposits sequentially: mark entire deposit as `used` if fully consumed, or reduce `amount` if partially consumed
4. Create new `CustomerTransaction` with negative `amount` and `reference_type='invoice'`

**Note:** Two separate deposit-usage implementations exist:
- `SalesInvoiceController::processDepositUsage()` — used in invoice store
- `CustomerTransaction::useDepositsForInvoice()` — defined on model, appears unused in current workflow

---

## 13. Order Cancellation / Refund

**Status:** NOT IMPLEMENTED. `SalesInvoiceController::destroy()` returns `abort(404)`. No refund workflow exists.

---

## 14. Product Inventory Movement

### Inbound (Purchase)
**Trigger:** POST `/admin/purchase_invoices`

**Logic:**
1. Create `PurchaseInvoice`
2. Call `saveDetails()` on model:
   - For each detail: create `PurchaseInvoiceDetail`, upsert `InventoryProduct` (increment if exists, insert if not), create `InventoryTransactionDetail`, create `SupplierPrice`
3. Create `SupplierTransaction`

### Outbound (Sales)
See §11 above — `deductFromInventory()` in `SalesInvoiceController`.

### Transfer
**Trigger:** POST `/admin/inventory_transactions/transfer`

**Logic:**
1. Validate source has sufficient stock
2. Decrement source `inventory_products.quantity`
3. Increment destination `inventory_products.quantity`
4. Create `InventoryTransaction` + `InventoryTransactionDetail`

**Note:** Transfer does NOT handle the case where the product doesn't exist in the destination inventory. `DB::table('inventory_products')->where(...)->increment()` on a non-existent row will silently do nothing (quantity remains zero).

---

## 15. Stock Adjustment

**Status:** No dedicated stock adjustment workflow identified. `InventoryController` likely manages inventory metadata (name, branch), not manual quantity adjustments. Manual adjustments are UNKNOWN.

---

## 16. Expenses

**Trigger:** POST `/admin/expenses`

**Validation:** `ExpenseRequest` — expense_type_id, payment_method_id, branch_id, amount, paid_amount, paid_at, status

**Business Logic:** Simple `Expense::create()`

**DB Writes:** `expenses` (insert)

**Note:** `balance = amount - paid_amount` logic is UNKNOWN — not confirmed calculated server-side; may be entered manually.

---

## 17. Reports

### Daily Revenues Report
- Input: date range
- Calculates: total services revenue (without deposit), total products revenue, total taxes, net total, cash revenue, other payment methods revenue, expenses (cash only), deposits used
- All from `SalesInvoice` + `SalesInvoiceDetail` + `Expense`

### Total Daily Revenues (Per-Day Table)
- Input: date range
- Returns one row per day: total, cash, other payments, expenses, net, deposits
- Uses `CustomerTransaction` for deposit column (all transactions, not just available ones)

### Daily Summary
- Input: date range
- Per day: services/products count and sales, purchases, expenses, employee stats, customer stats
- **Issue:** `services_commissions` is set equal to `services_sales` — commission calculation is a placeholder

### Monthly Summary
- Per-month breakdown for a year: services revenue, products revenue, expenses, net income, purchases, provider count, new customers

### Employee Service Report
- `EmployeeReportController` and `EmployeeSummaryReportController` — DataTable-based

### Stock Report
- `StockReportController` — DataTable-based

### Store Balance Report
- `StoreBalanceReportController` — DataTable-based

---

## 18. Permissions / RBAC

**System:** Spatie laravel-permission

**Assignment Flow:**
1. Admin creates Role with name, description, and assigns Permissions
2. Admin creates User, selects role + optional additional permissions
3. `CheckRole` middleware on each request:
   - Parses route name (e.g., `employees.index` → type=`employees`, page=`index`)
   - Checks `auth()->user()->can($permission)`
   - Special-case mappings (e.g., `store` checks `.create`, `update` checks `.edit`)
   - Hardcoded whitelist: `home.index`, `dashboard`, `sales_invoices.getItem`, `sales_invoices.getRelatedEmployees`

**Known Issue:**
- `CheckRole` calls `self::perUSer()` (capital U, S) but `AppHelper` defines `perUser()` (lowercase). PHP method names are case-insensitive so this works, but is inconsistent.
- Appointment routes are **outside** the auth+checkRole group entirely — no permission check.

---

## 19. Branch Switching

**Status:** NOT IMPLEMENTED as a user-facing feature. There is no branch switcher UI or session-based branch context. Branch-scoping is determined by the user's linked employee's branch.

---

## 20. Notifications

**Status:** No custom Notification classes. No email sent on appointment booking, invoice creation, or any business event. SweetAlert is used for in-page flash messages only.
