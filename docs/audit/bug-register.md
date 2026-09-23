# Bug Register — Salon Appointment Manager

This register lists confirmed bugs with their ID, severity, location, and impact. All findings are evidence-based.

---

## BUG-001 — dd() Before DB::rollBack() — Transactions Never Rolled Back on Error
- **ID:** BUG-001
- **Category:** Bug — Critical Crash
- **Severity:** CRITICAL
- **Evidence:**
  ```php
  // EmployeeController.php:112
  } catch (\Throwable $th) {
      dd($th->getMessage()); // execution stops here
      DB::rollBack();        // NEVER reached
      Alert::error(...);
  }
  ```
  Same pattern in `ServiceController.php:114` and `PurchaseInvoiceController.php:225`.
- **Affected Area:** Employee create, Service create, PurchaseInvoice update
- **Why It Matters:** On any error: (1) transaction left open/uncommitted, (2) error exposed to user, (3) alert never shown, (4) redirect never happens
- **Confidence:** CONFIRMED
- **Recommendation:** Remove `dd()`. Place `rollBack()` before any early return.

---

## BUG-002 — Employee Wage Created Twice on Employee Creation
- **ID:** BUG-002
- **Category:** Bug — Data Integrity
- **Severity:** HIGH
- **Evidence:**
  - `Employee.php:47-51` (boot method): `EmployeeWage::create(['employee_id' => $employee->id])`
  - `EmployeeController.php:75-92`: `EmployeeWage::create([...full wage data...])` immediately after
- **Affected Area:** Employee creation workflow
- **Why It Matters:** Every employee ends up with 2 wage records. `EmployeeWage::where('employee_id', $id)->first()` returns the empty auto-created one. Salary data is silently lost or ignored.
- **Confidence:** CONFIRMED
- **Recommendation:** Remove boot method's EmployeeWage::create, OR remove the explicit controller create and move all wage data into the boot event.

---

## BUG-003 — Appointment Update Uses Request Body ID Instead of Route Parameter
- **ID:** BUG-003
- **Category:** Bug — Authorization Bypass
- **Severity:** HIGH
- **Evidence:**
  ```php
  public function update(Request $request, $id)
  {
      $product = Appointment::findOrFail($request->id); // wrong: uses $request->id
  ```
- **Affected Area:** `AppointmentController.php:80`
- **Why It Matters:** An attacker can PUT to `/appointments/1` with body `{id: 99}` and update appointment #99 instead of #1. Route-based authorization is bypassed.
- **Confidence:** CONFIRMED
- **Recommendation:** Use `Appointment::findOrFail($id)`.

---

## BUG-004 — Inventory Transfer Silently Loses Stock When Product Absent from Destination
- **ID:** BUG-004
- **Category:** Bug — Data Loss
- **Severity:** HIGH
- **Evidence:**
  ```php
  DB::table('inventory_products')
      ->where('inventory_id', $dest)
      ->where('product_id', $prod)
      ->increment('quantity', $qty);  // 0 rows affected if product not in destination
  ```
- **Affected Area:** `InventoryTransactionController.php:83-86`
- **Why It Matters:** Product quantity deducted from source, never added to destination. Stock permanently lost.
- **Confidence:** CONFIRMED
- **Recommendation:** Use `updateOrInsert(['inventory_id' => $dest, 'product_id' => $prod], ['quantity' => DB::raw("quantity + $qty")])`.

---

## BUG-005 — Race Condition in Product Code Generation
- **ID:** BUG-005
- **Category:** Bug — Data Integrity
- **Severity:** MEDIUM
- **Evidence:**
  ```php
  $latestProduct = self::latest('code')->first();
  $product->code = $latestProduct ? $latestProduct->code + 1 : 100001;
  ```
- **Affected Area:** `Product.php:46-47` (boot creating event)
- **Why It Matters:** Two concurrent product creates see same `latestProduct`. Both try to insert same `code`. DB unique constraint throws error, second insert fails.
- **Confidence:** CONFIRMED
- **Recommendation:** Use DB-level auto-increment for code field, or use pessimistic locking.

---

## BUG-006 — Race Condition in Purchase Invoice Number Generation
- **ID:** BUG-006
- **Category:** Bug — Data Integrity
- **Severity:** MEDIUM
- **Evidence:** Same pattern as BUG-005 in `PurchaseInvoice::generateInvoiceNumber()`.
- **Affected Area:** `PurchaseInvoice.php:49-58`
- **Confidence:** CONFIRMED
- **Recommendation:** Use DB-level auto-increment or `SELECT ... FOR UPDATE`.

---

## BUG-007 — Appointment Routes Have No Authentication or Validation
- **ID:** BUG-007
- **Category:** Bug — Security / Data Integrity
- **Severity:** CRITICAL
- **Evidence:** `Route::resource('appointments', ...)` is outside auth middleware. `AppointmentController::store()` has zero validation rules.
- **Affected Area:** `/appointments/*` routes
- **Why It Matters:** Anyone can CRUD any appointment without login. No validation means malformed data enters the database.
- **Confidence:** CONFIRMED
- **Recommendation:** Add auth middleware. Create `AppointmentRequest` with validation.

---

## BUG-008 — Service Model: `use HasUserActions` Declared Twice
- **ID:** BUG-008
- **Category:** Bug — Dead Code
- **Severity:** LOW
- **Evidence:** `Service.php:10-13`
- **Confidence:** CONFIRMED
- **Recommendation:** Remove duplicate.

---

## BUG-009 — InventoryTransactionDetail.transaction_type = 'transfer' for Sales
- **ID:** BUG-009
- **Category:** Bug — Data Integrity
- **Severity:** MEDIUM
- **Evidence:**
  ```php
  InventoryTransactionDetail::create(['transaction_type' => 'transfer', ...])
  ```
  Called in sales deduction flow.
- **Affected Area:** `PurchaseInvoice.php:110`, sales deduction in `SalesInvoiceController`
- **Why It Matters:** Audit trail is incorrect. Stock reports will misclassify sales as transfers.
- **Confidence:** CONFIRMED
- **Recommendation:** Pass correct `transaction_type` value.

---

## BUG-010 — InventoryTransaction Financial Fields Are Zero for Sales
- **ID:** BUG-010
- **Category:** Bug — Data Integrity
- **Severity:** HIGH
- **Evidence:**
  ```php
  InventoryTransaction::create([
      'transaction_type' => 'sales',
      'source_inventory_id' => $transactions[0]['inventory_id'],
      'total_before_discount' => 0,
      'net_total' => 0,
  ]);
  ```
- **Affected Area:** `SalesInvoiceController.php:401-406`
- **Why It Matters:** Inventory-level financial reports cannot be built from this data.
- **Confidence:** CONFIRMED
- **Recommendation:** Pass actual price values.

---

## BUG-011 — Service Price Overridden by Catalog Price Even When price_can_change=true
- **ID:** BUG-011
- **Category:** Bug — Business Logic
- **Severity:** HIGH
- **Evidence:**
  ```php
  private function processService($item)
  {
      $service = Service::where('id', $item['item_id'])->...->firstOrFail();
      $grossTotal = $service->price * $item['quantity']; // ignores $item['price']
  ```
- **Affected Area:** `SalesInvoiceController.php:302-326`
- **Why It Matters:** `price_can_change` flag and custom price submitted by cashier are silently ignored. Invoice line amounts are wrong when price is customized.
- **Confidence:** CONFIRMED
- **Recommendation:** Use `$item['price']` when `$service->price_can_change` is true.

---

## BUG-012 — `last_service` on Customer Never Updated
- **ID:** BUG-012
- **Category:** Bug — Stale Data
- **Severity:** MEDIUM
- **Evidence:** `customers.last_service` column exists but no code updates it after a service is rendered (confirmed by reviewing `SalesInvoiceController::store()` which does not touch `last_service`).
- **Affected Area:** `customers` table, `SalesInvoiceController`
- **Why It Matters:** Retention metrics based on `last_service` are always wrong/null.
- **Confidence:** CONFIRMED
- **Recommendation:** Update `customer.last_service` when a sales invoice with a service is created.

---

## BUG-013 — Commission Report Calculation Is Placeholder (Always Shows Gross Sales)
- **ID:** BUG-013
- **Category:** Bug — Business Logic
- **Severity:** HIGH
- **Evidence:**
  ```php
  'services_commissions' => $serviceSales->sum('subtotal'), // Adjust commission calculation as needed
  ```
- **Affected Area:** `ReportController.php:210`
- **Why It Matters:** Commission report shows incorrect data. For a commission-based salon, this is a core business metric.
- **Confidence:** CONFIRMED
- **Recommendation:** Implement actual commission calculation using `service_employees` pivot data.

---

## BUG-014 — AppHelper References Non-Existent Model Classes
- **ID:** BUG-014
- **Category:** Bug — Dead Code / Import Error
- **Severity:** LOW
- **Evidence:**
  ```php
  use App\Models\CoreGeoCity;
  use App\Models\CoreGeoState;
  use App\Models\CoreGeoCountry;
  ```
  These classes do not exist in the codebase.
- **Affected Area:** `app/Traits/AppHelper.php:6-8`
- **Confidence:** CONFIRMED
- **Recommendation:** Remove dead imports.

---

## BUG-015 — HomePageController Imports Non-Existent `App\Models\Admin`
- **ID:** BUG-015
- **Category:** Bug — Dead Code
- **Severity:** LOW
- **Evidence:** `use App\Models\Admin;` in `HomePageController.php:5`. No `Admin` model exists.
- **Confidence:** CONFIRMED
- **Recommendation:** Remove dead import.

---

## BUG-016 — Customer Transaction's `reference_id=0` Violates Relational Integrity
- **ID:** BUG-016
- **Category:** Bug — Data Integrity
- **Severity:** MEDIUM
- **Evidence:**
  ```php
  CustomerTransaction::create([
      'reference_type' => 'deposit',
      'reference_id' => 0, // 0 is not a valid ID
  ]);
  ```
- **Affected Area:** `CustomerController.php:62`
- **Why It Matters:** Polymorphic query will return null. Any code trying to resolve `reference` morph will fail or return nothing.
- **Confidence:** CONFIRMED
- **Recommendation:** Use `nullable()` on `reference_id` and pass `null` for initial deposits.

---

## BUG-017 — TestCase.php Missing — All Tests Fail to Run
- **ID:** BUG-017
- **Category:** Bug — Testing Infrastructure
- **Severity:** HIGH
- **Evidence:**
  ```
  php artisan test --testsuite=Feature
  → An error occurred inside PHPUnit.
  → Message: Class "Tests\TestCase" not found
  ```
- **Confidence:** CONFIRMED
- **Recommendation:** Restore `tests/TestCase.php` and `tests/CreatesApplication.php`.

---

## BUG-018 — Receipt Route Outside Auth Middleware
- **ID:** BUG-018
- **Category:** Bug — Security
- **Severity:** MEDIUM
- **Evidence:** `Route::get('admin/sales_invoices/invoice/{id}', ...)` is at line 58, before the middleware group starts at line 61.
- **Confidence:** CONFIRMED
- **Recommendation:** Move inside auth middleware group.

---

## Summary Table

| ID | Severity | Description |
|---|---|---|
| BUG-001 | CRITICAL | dd() before rollBack — transactions never rolled back |
| BUG-007 | CRITICAL | Appointments unauthenticated, no validation |
| BUG-002 | HIGH | Employee wage created twice |
| BUG-003 | HIGH | Appointment update bypasses route param |
| BUG-004 | HIGH | Inventory transfer silently loses stock |
| BUG-010 | HIGH | Sales inventory transactions have zero financial values |
| BUG-011 | HIGH | service price_can_change ignored |
| BUG-013 | HIGH | Commission report is placeholder |
| BUG-017 | HIGH | All tests broken (TestCase missing) |
| BUG-005 | MEDIUM | Race condition in product code |
| BUG-006 | MEDIUM | Race condition in invoice number |
| BUG-009 | MEDIUM | InventoryTransactionDetail wrong type |
| BUG-012 | MEDIUM | last_service never updated |
| BUG-016 | MEDIUM | reference_id=0 violates relational integrity |
| BUG-018 | MEDIUM | Receipt route unprotected |
| BUG-008 | LOW | Service model duplicate trait use |
| BUG-014 | LOW | AppHelper dead imports |
| BUG-015 | LOW | HomePageController dead import |
