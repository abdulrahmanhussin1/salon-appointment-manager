# Technical Audit — Salon Appointment Manager

## Code Quality Analysis

---

### TECH-001 — Fat Controllers
- **Category:** Code Quality
- **Severity:** HIGH
- **Evidence:** `SalesInvoiceController` is 527 lines handling validation, inventory deduction, FIFO price allocation, deposit consumption, and transaction creation. `ReportController` is 358 lines with all business queries inline.
- **Affected Area:** `app/Http/Controllers/Admin/SalesInvoiceController.php`, `ReportController.php`
- **Why It Matters:** Business logic embedded in controllers cannot be unit tested, reused, or changed independently of HTTP concerns.
- **Confidence:** CONFIRMED
- **Recommendation:** Extract `SalesInvoiceService`, `InventoryService`, `DepositService`, `ReportQueryService` classes.

---

### TECH-002 — Business Logic in Model (saveDetails)
- **Category:** Architectural Concern
- **Severity:** MEDIUM
- **Evidence:** `PurchaseInvoice::saveDetails()` (130-line method) performs all DB operations for a purchase: creates `InventoryTransaction`, upserts `InventoryProduct`, creates `InventoryTransactionDetail`, creates `SupplierPrice`.
- **Affected Area:** `app/Models/PurchaseInvoice.php:62-128`
- **Why It Matters:** Mixing persistence coordination into a model violates single-responsibility and makes testing difficult.
- **Confidence:** CONFIRMED
- **Recommendation:** Extract to `PurchaseInvoiceService`.

---

### TECH-003 — Duplicate `use HasUserActions` in Service Model
- **Category:** Bug / Code Quality
- **Severity:** LOW
- **Evidence:**
  ```php
  class Service extends Model
  {    use HasFactory,HasUserActions;
       use HasUserActions;  // duplicate
  ```
- **Affected Area:** `app/Models/Service.php:10-12`
- **Why It Matters:** PHP does not throw an error for duplicate trait use but it is dead code and confusing.
- **Confidence:** CONFIRMED
- **Recommendation:** Remove the duplicate `use HasUserActions;` statement.

---

### TECH-004 — `dd()` Left in Production Exception Handlers
- **Category:** Bug / Production Safety
- **Severity:** CRITICAL
- **Evidence:**
  - `EmployeeController::store()`: `catch (\Throwable $th) { dd($th->getMessage()); DB::rollBack(); }`
  - `ServiceController::store()`: same pattern
  - `PurchaseInvoiceController::update()`: `catch (Exception $e) { dd($e->getMessage()); DB::rollBack(); }`
- **Affected Area:** `EmployeeController.php:112`, `ServiceController.php:114`, `PurchaseInvoiceController.php:225`
- **Why It Matters:** `dd()` halts execution BEFORE `DB::rollBack()` — transactions are never rolled back on error. Sensitive error details exposed to users. App crashes and hangs on any create/update failure.
- **Confidence:** CONFIRMED
- **Recommendation:** Remove all `dd()` calls. Ensure `rollBack()` is called before any early return. Log errors properly.

---

### TECH-005 — No Input Validation on Appointment Routes
- **Category:** Security / Bug
- **Severity:** HIGH
- **Evidence:** `AppointmentController::store()` and `::update()` use raw `Request` with no validation. Any user (or unauthenticated caller) can submit arbitrary data.
- **Affected Area:** `app/Http/Controllers/Admin/AppointmentController.php:49-63`, `78-92`
- **Why It Matters:** SQL injection (mitigated by Eloquent), but invalid data (e.g., non-existent `customer_id`, malformed dates) will cause application errors.
- **Confidence:** CONFIRMED
- **Recommendation:** Create `AppointmentRequest` form request with proper validation rules.

---

### TECH-006 — `update()` Ignores Route Model Binding
- **Category:** Bug
- **Severity:** MEDIUM
- **Evidence:**
  ```php
  public function update(Request $request, $id)
  {
      $product = Appointment::findOrFail($request->id); // uses $request->id, not $id
  ```
- **Affected Area:** `AppointmentController.php:80`
- **Why It Matters:** A user can submit `id=5` in the request body and update appointment #5 regardless of what `{id}` is in the URL. This bypasses route-based authorization.
- **Confidence:** CONFIRMED
- **Recommendation:** Use `Appointment::findOrFail($id)` (the route parameter).

---

### TECH-007 — Duplicate Deposit Usage Implementation
- **Category:** Code Quality / Architectural Concern
- **Severity:** MEDIUM
- **Evidence:** Two separate implementations for consuming customer deposits:
  1. `SalesInvoiceController::processDepositUsage()` — used in actual invoice flow
  2. `CustomerTransaction::useDepositsForInvoice()` — defined on model, not called anywhere confirmed
- **Affected Area:** `SalesInvoiceController.php:157-208`, `CustomerTransaction.php:29-66`
- **Why It Matters:** Inconsistent behavior if both paths diverge. Dead code is maintenance burden.
- **Confidence:** CONFIRMED
- **Recommendation:** Consolidate into a single method; prefer service class.

---

### TECH-008 — Unnecessary Nested Transactions in Sales Invoice Store
- **Category:** Performance / Reliability
- **Severity:** MEDIUM
- **Evidence:** `SalesInvoiceController::store()` wraps the entire operation in `DB::transaction(..., 5)`, then calls `processProduct()` which starts another `DB::transaction()` (via `DB::transaction(function() use ($item) { return $this->processProduct($item); })`), which in turn calls `DB::transaction()` inside `processProduct()`.
- **Affected Area:** `SalesInvoiceController.php:101-154`, `210-249`
- **Why It Matters:** Nested transactions create unnecessary savepoints in MySQL, can amplify deadlock probability, and make the flow hard to reason about.
- **Confidence:** CONFIRMED
- **Recommendation:** Use a single transaction wrapping all operations.

---

### TECH-009 — InventoryTransaction Created with Zero Amounts for Sales
- **Category:** Data Integrity / Bug
- **Severity:** HIGH
- **Evidence:**
  ```php
  InventoryTransaction::create([
      'transaction_type' => 'sales',
      'source_inventory_id' => $transactions[0]['inventory_id'],
      'total_before_discount' => 0, // wrong
      'net_total' => 0,             // wrong
  ]);
  ```
- **Affected Area:** `SalesInvoiceController.php:401-413`
- **Why It Matters:** Inventory transaction records for sales are incomplete — no financial value recorded. Makes inventory valuation reports impossible.
- **Confidence:** CONFIRMED
- **Recommendation:** Pass actual price values from the invoice item being processed.

---

### TECH-010 — `InventoryTransactionDetail.transaction_type` Hardcoded to `'transfer'` for Sales
- **Category:** Bug / Data Integrity
- **Severity:** MEDIUM
- **Evidence:**
  ```php
  InventoryTransactionDetail::create(['transaction_type' => 'transfer', ...])
  ```
  Called during sales deduction in `deductFromInventory()`.
- **Affected Area:** `PurchaseInvoice.php:110`, `SalesInvoiceController.php` deductFromInventory
- **Why It Matters:** Audit trail is incorrect — sales transactions are labeled as transfers.
- **Confidence:** CONFIRMED
- **Recommendation:** Use the correct type (`sales`).

---

### TECH-011 — Inventory Transfer Silently Fails for New Products in Destination
- **Category:** Bug
- **Severity:** HIGH
- **Evidence:**
  ```php
  DB::table('inventory_products')
      ->where('inventory_id', $validatedData['destination_inventory'])
      ->where('product_id', $product['product_id'])
      ->increment('quantity', $product['quantity']);
  ```
  If the product doesn't exist in the destination inventory, `increment()` affects 0 rows — quantity is not added.
- **Affected Area:** `InventoryTransactionController.php:83-86`
- **Why It Matters:** Products disappear from source without appearing in destination — stock is silently lost.
- **Confidence:** CONFIRMED
- **Recommendation:** Use `updateOrInsert()` or check existence first.

---

### TECH-012 — Employee Creation Inserts EmployeeWage Twice
- **Category:** Bug / Data Integrity
- **Severity:** HIGH
- **Evidence:** `Employee::boot()->created` event calls `EmployeeWage::create(['employee_id' => $employee->id])`. Then `EmployeeController::store()` also explicitly calls `EmployeeWage::create([...all fields...])`. Both fire on creation.
- **Affected Area:** `Employee.php:47-50`, `EmployeeController.php:75-92`
- **Why It Matters:** Two `employee_wages` rows per employee. Queries that use `->first()` will silently use the wrong one.
- **Confidence:** CONFIRMED
- **Recommendation:** Remove the `boot()` method's `EmployeeWage::create()` call, or remove the explicit call from the controller.

---

### TECH-013 — Product Code Race Condition
- **Category:** Bug / Data Integrity
- **Severity:** MEDIUM
- **Evidence:**
  ```php
  static::creating(function ($product) {
      $latestProduct = self::latest('code')->first();
      $product->code = $latestProduct ? $latestProduct->code + 1 : 100001;
  });
  ```
  Two concurrent create requests can both read the same `latestProduct` and assign the same code.
- **Affected Area:** `Product.php:46-47`
- **Why It Matters:** Unique constraint on `code` will cause a DB exception on concurrent inserts.
- **Confidence:** CONFIRMED
- **Recommendation:** Use DB-level auto-increment for code, or use a locking mechanism.

---

### TECH-014 — Invoice Number Race Condition
- **Category:** Bug / Data Integrity
- **Severity:** MEDIUM
- **Evidence:** Same pattern as product code in `PurchaseInvoice::generateInvoiceNumber()`.
- **Affected Area:** `PurchaseInvoice.php:49-58`
- **Why It Matters:** Duplicate invoice numbers on concurrent purchases.
- **Confidence:** CONFIRMED
- **Recommendation:** Use DB-level auto-increment or pessimistic locking.

---

### TECH-015 — Service Model Uses Wrong Branch Default in Migration
- **Category:** Code Quality
- **Severity:** LOW
- **Evidence:** Migration has `->default(1)` on `branch_id` FK:
  ```php
  $table->foreignId('branch_id')->constrained('branches')->default(1)->...
  ```
  Same pattern in products, employees, expenses.
- **Why It Matters:** DB defaults silently set branch to ID 1 if not provided. This can cause incorrect branch assignment.
- **Confidence:** CONFIRMED
- **Recommendation:** Remove DB-level default; enforce in application layer.

---

### TECH-016 — AppHelper References Non-Existent Models
- **Category:** Dead Code / Bug Risk
- **Severity:** LOW
- **Evidence:** `AppHelper.php` imports `App\Models\CoreGeoCity`, `CoreGeoState`, `CoreGeoCountry` — none of which exist in the codebase.
- **Affected Area:** `app/Traits/AppHelper.php:6-8`
- **Why It Matters:** Would cause `ClassNotFoundException` if these imports were referenced. Currently unused imports.
- **Confidence:** CONFIRMED
- **Recommendation:** Remove dead imports.

---

### TECH-017 — HomePageController Uses Hardcoded Branch ID 1 as Admin Sentinel
- **Category:** Architecture / Bug
- **Severity:** MEDIUM
- **Evidence:**
  ```php
  if ($userBranchId != 1 && $userBranchId != null) {
      // filter by branch
  }
  ```
- **Affected Area:** `HomePageController.php:28`
- **Why It Matters:** Business rule embedded as magic number. If branch 1 is deleted or a different branch becomes the "main" branch, the behavior silently changes.
- **Confidence:** CONFIRMED
- **Recommendation:** Use a proper admin role/permission check or a branch configuration flag.

---

### TECH-018 — Inconsistent Naming Convention (perUSer vs perUser)
- **Category:** Code Quality
- **Severity:** LOW
- **Evidence:** `CheckRole` calls `self::perUSer(...)` (capital U, S). `AppHelper` defines `perUser()`. PHP is case-insensitive for method names so it works, but is confusing.
- **Affected Area:** `CheckRole.php`, `AppHelper.php`
- **Confidence:** CONFIRMED
- **Recommendation:** Standardize to `perUser()`.

---

### TECH-019 — `Admin` Model Imported in HomePageController
- **Category:** Dead Code
- **Severity:** LOW
- **Evidence:** `use App\Models\Admin;` imported in `HomePageController` — no `Admin` model exists.
- **Affected Area:** `HomePageController.php:5`
- **Why It Matters:** Would cause class-not-found in some PHP configurations.
- **Confidence:** CONFIRMED
- **Recommendation:** Remove dead import.

---

### TECH-020 — Commission Calculation Placeholder in Reports
- **Category:** Bug / Missing Functionality
- **Severity:** HIGH (business impact)
- **Evidence:**
  ```php
  'services_commissions' => $serviceSales->sum('subtotal'), // Adjust commission calculation as needed
  ```
  Commission is set equal to gross service sales — completely wrong.
- **Affected Area:** `ReportController.php:210`
- **Why It Matters:** Commission reports show incorrect data. This is a core business metric for a salon business.
- **Confidence:** CONFIRMED
- **Recommendation:** Implement actual commission calculation using `service_employees.commission_type` and `commission_value`.

---

### TECH-021 — Service Price Used in processService Instead of Requested Price
- **Category:** Bug
- **Severity:** MEDIUM
- **Evidence:**
  ```php
  $grossTotal = $service->price * $item['quantity'];
  ```
  The item's submitted `price` is ignored. If `price_can_change=true`, the user can set a custom price in the UI, but it is discarded and the catalog price is always used.
- **Affected Area:** `SalesInvoiceController.php:308`
- **Why It Matters:** `price_can_change` flag exists but is not honored for services.
- **Confidence:** CONFIRMED
- **Recommendation:** Use `$item['price']` when `price_can_change` is true.

---

### TECH-022 — Calender Route Has No Auth Middleware
- **Category:** Security
- **Severity:** MEDIUM
- **Evidence:**
  ```php
  Route::get('admin/calender', function(){ return view('admin.calender'); })->name('home.calender');
  ```
  This route is outside the `auth` + `checkRole` group.
- **Affected Area:** `routes/web.php:50-52`
- **Why It Matters:** Calendar page (including appointment data) accessible without authentication.
- **Confidence:** CONFIRMED
- **Recommendation:** Move inside the auth middleware group.

---

### TECH-023 — Appointment Routes Not Protected
- **Category:** Security / Critical
- **Severity:** CRITICAL
- **Evidence:**
  ```php
  Route::resource('appointments', AppointmentController::class);
  ```
  This is at the top level, outside `Route::prefix('admin')->middleware(['auth', 'verified', 'checkRole'])`.
- **Why It Matters:** Anyone on the internet can create, update, and delete appointments without authenticating.
- **Confidence:** CONFIRMED
- **Recommendation:** Move appointment routes inside the authenticated middleware group.

---

### TECH-024 — `TestCase.php` Missing
- **Category:** Testing
- **Severity:** HIGH
- **Evidence:** `tests/Feature/Auth/AuthenticationTest.php` references `Tests\TestCase` which is not found. All Feature tests fail to run.
- **Why It Matters:** Cannot run any tests — CI/CD is broken, code health cannot be verified.
- **Confidence:** CONFIRMED
- **Recommendation:** Restore `tests/TestCase.php` and `tests/CreatesApplication.php` from Breeze scaffold.

---

### TECH-025 — Missing `tests/Unit/` Directory
- **Category:** Testing
- **Severity:** MEDIUM
- **Evidence:** PHPUnit reports: "Test directory not found" for Unit suite.
- **Why It Matters:** No unit test capability.
- **Confidence:** CONFIRMED
- **Recommendation:** Create `tests/Unit/` directory and add unit tests for isolated business logic.
