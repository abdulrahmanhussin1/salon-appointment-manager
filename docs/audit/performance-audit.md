# Performance Audit — Salon Appointment Manager

---

### PERF-001 — N+1 Queries in Daily Report (Per-Day Loop)
- **Category:** Performance — Database
- **Severity:** HIGH
- **Evidence:**
  ```php
  // ReportController::TotalDailyRevenues
  $dates->map(function ($date) {
      $sales = SalesInvoice::whereDate('invoice_date', $date)->...->get();
      $expenses = Expense::whereDate('paid_at', $date)->...->get();
      $transactions = CustomerTransaction::whereDate('created_at', $date)->get();
      // 3 queries per day
  });
  ```
  For a 30-day range: 90 queries minimum.
- **Affected Area:** `ReportController.php:108-132`
- **Why It Matters:** Performance degrades linearly with date range. A 1-year report = 1,095 queries.
- **Confidence:** CONFIRMED
- **Recommendation:** Use a single query with `GROUP BY DATE(invoice_date)` to aggregate all date data in one round trip.

---

### PERF-002 — N+1 in Daily Summary Report
- **Category:** Performance — Database
- **Severity:** HIGH
- **Evidence:** Similar per-day loop in `dailySummary()` with 4 queries per day (sales, purchases, invoice details with lazy-loaded relations, expenses).
- **Affected Area:** `ReportController.php:157-231`
- **Why It Matters:** Same as PERF-001; amplified by eager loading inside the loop.
- **Confidence:** CONFIRMED
- **Recommendation:** Batch queries across the entire date range.

---

### PERF-003 — HomePageController Lazy-Loads `provider` Relation
- **Category:** Performance — Database
- **Severity:** MEDIUM
- **Evidence:**
  ```php
  $get_biggest_provider..._name = $get_biggest_provider_that_have_orders_today?->provider?->name ?? '';
  ```
  After getting the `SalesInvoiceDetail`, the `provider` (Employee) is loaded via a second query.
- **Affected Area:** `HomePageController.php:44-49`
- **Why It Matters:** Triggers additional query on every dashboard load.
- **Confidence:** CONFIRMED
- **Recommendation:** Join or eager-load provider name in the initial query.

---

### PERF-004 — SalesInvoice::create() Loads All Customers with Subquery on Every POS Open
- **Category:** Performance — Database
- **Severity:** MEDIUM
- **Evidence:**
  ```php
  $customers = Customer::select(...)
      ->selectSub(function (Builder $query) {
          $query->from('customer_transactions')->...->select(DB::raw('SUM(amount)'));
      }, 'deposit')
      ->where('status', 'active')
      ->get();
  ```
  All active customers are loaded with a correlated subquery per row for deposit calculation.
- **Affected Area:** `SalesInvoiceController.php:45-55`
- **Why It Matters:** If there are 1,000 customers: 1,000 correlated subqueries executed per POS page load.
- **Confidence:** CONFIRMED
- **Recommendation:** Load customers without deposit initially; fetch deposit via AJAX when customer is selected.

---

### PERF-005 — All Active Products Loaded on POS Create with Eager-Loaded SupplierPrices
- **Category:** Performance — Database
- **Severity:** MEDIUM
- **Evidence:**
  ```php
  $products = Product::select('id', 'name', 'code', 'price_can_change')
      ->with(['supplierPrices:id,product_id,quantity,customer_price,created_at'])
      ->where('status', 'active')
      ->get()
      ->map(function ($product) { ... });
  ```
  All products + all their supplier prices loaded into PHP memory on every POS page load.
- **Affected Area:** `SalesInvoiceController.php:59-76`
- **Why It Matters:** With 500 products × average 10 price history rows = 5,000 rows loaded and then discarded (map filters to first valid price).
- **Confidence:** CONFIRMED
- **Recommendation:** Use lazy-loading via AJAX when product is selected, or use a subquery to fetch only current price per product.

---

### PERF-006 — Inventory Availability Check Sums Entire inventory_products Table
- **Category:** Performance — Database
- **Severity:** MEDIUM
- **Evidence:**
  ```php
  $totalAvailable = InventoryProduct::where('product_id', $productId)->sum('quantity');
  ```
  Sums across ALL inventories for a product — no branch/inventory filter.
- **Affected Area:** `SalesInvoiceController.php:363-366`
- **Why It Matters:** A product may have 0 quantity in the branch being sold from but 100 in a different branch. The check passes but deduction then fails. Also, index on `product_id` in `inventory_products` needs verification.
- **Confidence:** CONFIRMED
- **Recommendation:** Filter by the specific branch inventory being sold from. Add index on `product_id`.

---

### PERF-007 — FIFO Deduction Loads All inventory_products for Product into Memory
- **Category:** Performance — Database
- **Severity:** LOW
- **Evidence:**
  ```php
  $inventoryProducts = InventoryProduct::where('product_id', $productId)
      ->where('quantity', '>', 0)
      ->orderBy('created_at', 'asc')
      ->get();
  ```
  All inventory rows for this product loaded into PHP for iteration.
- **Why It Matters:** Low impact currently (usually few rows per product), but will grow if product is in many inventories.
- **Confidence:** CONFIRMED
- **Recommendation:** Acceptable for now. Add index on `(product_id, created_at)`.

---

### PERF-008 — QUEUE_CONNECTION=sync Blocks HTTP Requests
- **Category:** Performance — Architecture
- **Severity:** MEDIUM
- **Evidence:** `.env`: `QUEUE_CONNECTION=sync`
- **Why It Matters:** Any future jobs (email, PDF generation, reports) will block the HTTP request until complete.
- **Confidence:** CONFIRMED
- **Recommendation:** Switch to Redis queue driver (Redis is already configured). Add queue workers.

---

### PERF-009 — No Response Caching
- **Category:** Performance — Caching
- **Severity:** LOW
- **Evidence:** `CACHE_DRIVER=redis` but no `Cache::` calls observed in any controller. Dashboard stats, lookup tables (branches, employees, services, products) are fetched fresh on every request.
- **Confidence:** CONFIRMED
- **Recommendation:** Cache lookup tables (branches, products, services) with short TTL; cache dashboard stats.

---

### PERF-010 — SESSION_DRIVER=database Without Session Table Indexing
- **Category:** Performance — Database
- **Severity:** LOW
- **Evidence:** Sessions stored in database. The `sessions` table exists (migration present). Under high load, session reads/writes become a bottleneck.
- **Confidence:** CONFIRMED
- **Recommendation:** For higher traffic, switch to `SESSION_DRIVER=redis` (Redis already deployed). At minimum, ensure `sessions.user_id` and `sessions.last_activity` are indexed.

---

### PERF-011 — Monthly Summary Report Does Not Cache Results
- **Category:** Performance — Reporting
- **Severity:** LOW
- **Evidence:** `monthlySummary()` runs 6 separate aggregation queries across the full year on every request.
- **Confidence:** CONFIRMED
- **Recommendation:** Cache monthly aggregates with a 1-hour TTL.
