# Database Audit — Salon Appointment Manager

## Table Structure Analysis

---

### DB-001 — Appointment Dates Stored as String
- **Category:** Data Integrity / Schema Design
- **Severity:** HIGH
- **Evidence:** `appointments` migration:
  ```php
  $table->string('start_date');
  $table->string('end_date');
  ```
  In controller, Carbon is used to parse and format to `Y-m-d H:i:s`, but the column is `VARCHAR`.
- **Affected Area:** `appointments` table
- **Why It Matters:** Cannot sort, range-query, or index dates correctly on string columns. No DB-level validation of date format.
- **Confidence:** CONFIRMED
- **Recommendation:** Change to `$table->datetime('start_date')` and `$table->datetime('end_date')`.

---

### DB-002 — Appointment Table Has No Status Column
- **Category:** Schema Design / Missing Functionality
- **Severity:** HIGH
- **Evidence:** `appointments_table.php` migration has no `status` column. No enum for `pending | confirmed | completed | cancelled | no_show`.
- **Affected Area:** `appointments` table
- **Why It Matters:** Cannot track appointment lifecycle. No cancellation, completion, or no-show tracking possible.
- **Confidence:** CONFIRMED
- **Recommendation:** Add `status enum('pending','confirmed','completed','cancelled','no_show') default 'pending'`.

---

### DB-003 — Appointment Table Has No Branch Scope
- **Category:** Schema Design
- **Severity:** MEDIUM
- **Evidence:** No `branch_id` column in `appointments`.
- **Why It Matters:** Cannot filter appointments by branch. Multi-branch businesses cannot segment booking data.
- **Confidence:** CONFIRMED
- **Recommendation:** Add `branch_id` FK.

---

### DB-004 — `customer_transactions.reference_id = 0` as Placeholder
- **Category:** Data Integrity
- **Severity:** MEDIUM
- **Evidence:**
  ```php
  CustomerTransaction::create([
      'reference_type' => 'deposit',
      'reference_id' => 0, // placeholder
  ]);
  ```
  The `morphs()` migration creates `reference_type` + `reference_id` which acts as a polymorphic FK, but `reference_id=0` is not a valid FK.
- **Affected Area:** `customer_transactions` table
- **Why It Matters:** Data is inconsistent. Joins on this column will not work correctly.
- **Confidence:** CONFIRMED
- **Recommendation:** Use nullable `reference_id` and set to NULL for initial deposits.

---

### DB-005 — `balance_due` is Signed Decimal (Can Be Negative)
- **Category:** Schema Design / Business Rule
- **Severity:** LOW
- **Evidence:**
  ```php
  $table->decimal('balance_due', 15, 2); // Removes the unsigned constraint.
  ```
  Comment in migration explicitly notes this.
- **Why It Matters:** Negative balance means overpayment. The system appears to allow this intentionally but there is no business logic to handle overpayment (e.g., converting overpayment to deposit).
- **Confidence:** CONFIRMED
- **Recommendation:** Either enforce non-negative or implement overpayment → deposit conversion.

---

### DB-006 — Missing Foreign Key on `appointments.customer_id` When Status Changes
- **Category:** Data Integrity
- **Severity:** LOW
- **Evidence:** FK `customer_id → customers.id` has no `onDelete` behavior specified in appointment migration (defaults to RESTRICT). This means customers with appointments cannot be deleted — but `CustomerController::destroy()` calls hard delete with no guard.
- **Why It Matters:** Attempting to delete a customer with appointments will throw a DB integrity exception uncaught by the application.
- **Confidence:** INFERRED
- **Recommendation:** Add explicit `restrictOnDelete()` and handle the case in the controller with a user-friendly message.

---

### DB-007 — InventoryTransaction Financial Fields Nullable with Incorrect Defaults for Sales
- **Category:** Data Integrity
- **Severity:** HIGH
- **Evidence:** All financial columns in `inventory_transactions` are nullable. Sales transactions are created with `total_before_discount=0` and `net_total=0`.
- **Affected Area:** `inventory_transactions` table
- **Why It Matters:** Inventory financial reporting is impossible. The data is present but all zero.
- **Confidence:** CONFIRMED
- **Recommendation:** Populate these fields correctly during sales and purchase flows.

---

### DB-008 — SupplierPrice Has No Check for quantity ≥ 0
- **Category:** Data Integrity
- **Severity:** LOW
- **Evidence:** `supplier_prices.quantity` is `unsignedDecimal` — unsigned prevents negative values at DB level. Correct.
- **Confidence:** CONFIRMED — this one is handled correctly.
- **Recommendation:** None needed for this specific column.

---

### DB-009 — Missing Indexes on High-Query Columns
- **Category:** Performance
- **Severity:** MEDIUM
- **Evidence:**
  - `sales_invoice_details.sales_invoice_id` — no explicit index (may rely on FK index depending on MySQL version)
  - `sales_invoice_details.provider_id` — no index; heavily used in reports
  - `customer_transactions.customer_id` + `status` — compound index missing; used in EVERY deposit check
  - `sales_invoices.invoice_date` + `status` — compound index missing; used in ALL report queries
  - `expenses.paid_at` + `status` — no compound index
  - `inventory_products.product_id` — no explicit index
- **Why It Matters:** Report queries scan large datasets; performance will degrade as data grows.
- **Confidence:** INFERRED (confirmed by query patterns, index presence not directly verified in DB)
- **Recommendation:** Add composite indexes: `(customer_id, status)` on `customer_transactions`, `(invoice_date, status)` on `sales_invoices`, `(provider_id)` on `sales_invoice_details`.

---

### DB-010 — Risky Migration: Year 2099
- **Category:** Deployment Risk
- **Severity:** MEDIUM
- **Evidence:** Migration file `2099_11_03_164123_add_foreign_keys.php` — year 2099 means this migration runs AFTER all others (alphabetically). This is intentional to add FKs after dependent tables exist. However:
  - `Schema::table('users', ...)` and `Schema::table('supplier_prices', ...)` class names use `BluePrint` with capital P (works, PHP is case-insensitive on class aliases)
  - `schema::` (lowercase) is used for the users table — works due to Facade magic but is inconsistent
- **Affected Area:** `database/migrations/2099_11_03_164123_add_foreign_keys.php`
- **Why It Matters:** Non-standard date causes ordering confusion; inconsistent casing is a code quality issue.
- **Confidence:** CONFIRMED
- **Recommendation:** Move FK additions to the end of normal migration files or use a conventional naming approach.

---

### DB-011 — No Soft Deletes Anywhere
- **Category:** Data Integrity / Auditability
- **Severity:** MEDIUM
- **Evidence:** No model uses `SoftDeletes` trait. No `deleted_at` column in any migration.
- **Why It Matters:** Deleted data is permanently gone. Cannot recover accidentally deleted customers, invoices, or products. Regulatory requirements (e.g., keeping financial records) may mandate data retention.
- **Confidence:** CONFIRMED
- **Recommendation:** Add soft deletes to at minimum: `customers`, `employees`, `sales_invoices`, `products`, `services`.

---

### DB-012 — Denormalized `last_service` on Customer
- **Category:** Schema Design
- **Severity:** LOW
- **Evidence:** `customers.last_service` (date column, nullable). Not updated anywhere in the reviewed code.
- **Why It Matters:** Stale/incorrect data. The actual last service date is derivable from `sales_invoices`.
- **Confidence:** CONFIRMED (not updated programmatically)
- **Recommendation:** Either populate on invoice completion or remove and derive from `sales_invoices`.

---

### DB-013 — `employee_wages` Has No Unique Constraint on `employee_id`
- **Category:** Data Integrity
- **Severity:** HIGH
- **Evidence:** No unique constraint on `employee_id` in `employee_wages` migration. Bug TECH-012 causes duplicate rows.
- **Why It Matters:** Multiple wage records per employee cause incorrect queries.
- **Confidence:** CONFIRMED
- **Recommendation:** Add `$table->unique('employee_id')` to `employee_wages`.

---

### DB-014 — N+1 Risk in Report Queries
- **Category:** Performance
- **Severity:** HIGH
- **Evidence:**
  ```php
  $dailyDetails->with(['provider', 'service', 'product'])->get();
  // But:
  $get_biggest_provider... = SalesInvoiceDetail::...->first();
  $get_biggest_provider..._name = $result?->provider?->name ?? '';
  ```
  In `HomePageController`, `provider` is lazy-loaded after the query returns.
  In `ReportController::TotalDailyRevenues`, for each date in range: separate queries for sales, expenses, transactions — N×3 queries for N days.
- **Affected Area:** `HomePageController.php`, `ReportController.php`
- **Why It Matters:** 30-day report = 90+ queries minimum. Performance degrades linearly with date range.
- **Confidence:** CONFIRMED
- **Recommendation:** Eager load relationships; batch queries per date range instead of per day.

---

### DB-015 — Money Columns Mixed Between decimal and unsignedDecimal
- **Category:** Schema Design
- **Severity:** LOW
- **Evidence:**
  - `sales_invoices.total_amount` — `unsignedDecimal(15,2)`
  - `sales_invoices.balance_due` — `decimal(15,2)` (signed, for overpayment)
  - `expenses.amount` — `decimal(15,2)`
  - `expenses.paid_amount` — `unsignedDecimal(15,2)`
  - Various `net_total` — some signed, some unsigned
- **Why It Matters:** Inconsistent column types make it hard to reason about invariants.
- **Confidence:** CONFIRMED
- **Recommendation:** Document and standardize: use signed decimals everywhere and enforce non-negative at application layer where needed.

---

### DB-016 — Users Table `created_by` References Itself
- **Category:** Schema Design / Chicken-and-Egg
- **Severity:** MEDIUM
- **Evidence:**
  ```php
  $table->foreignId('created_by')->constrained('users', 'id')->...restrictOnDelete();
  ```
  The first user seeded cannot have a valid `created_by`.
- **Affected Area:** `users` migration
- **Why It Matters:** Database seeder must handle this carefully. Any unique constraint violation at seed time will fail.
- **Confidence:** CONFIRMED
- **Recommendation:** Make `created_by` nullable for the users table, or create a system user (id=1) first.

---

### DB-017 — `inventory_products` Has No Unique Constraint on (inventory_id, product_id)
- **Category:** Data Integrity
- **Severity:** HIGH
- **Evidence:** `PurchaseInvoice::saveDetails()` checks existence manually with `DB::table()->where()->first()` before deciding to insert or update. No DB-level unique constraint enforces this.
- **Why It Matters:** Race condition: two concurrent purchase invoices for same product+inventory can both see no existing row and both insert → duplicate rows → incorrect quantity.
- **Confidence:** CONFIRMED (no unique index in migration)
- **Recommendation:** Add `$table->unique(['inventory_id', 'product_id'])` and use `updateOrInsert()`.
