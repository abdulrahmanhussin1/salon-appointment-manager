# Security Audit — Salon Appointment Manager

---

### SEC-001 — Appointment Routes Accessible Without Authentication
- **Category:** Security — Authentication
- **Severity:** CRITICAL
- **Evidence:**
  ```php
  // routes/web.php — outside any auth middleware group
  Route::resource('appointments', AppointmentController::class);
  ```
- **Affected Area:** All appointment CRUD endpoints: GET/POST/PUT/DELETE `/appointments/*`
- **Why It Matters:** Any anonymous user can create, modify, or delete any appointment record in the database. No authentication, no authorization, no input validation.
- **Confidence:** CONFIRMED
- **Recommendation:** Move inside `Route::prefix('admin')->middleware(['auth', 'verified', 'checkRole'])`.

---

### SEC-002 — Calendar Page Accessible Without Authentication
- **Category:** Security — Authentication
- **Severity:** MEDIUM
- **Evidence:**
  ```php
  Route::get('admin/calender', function(){
      return view('admin.calender');
  })->name('home.calender');
  ```
- **Affected Area:** `admin/calender` URL
- **Why It Matters:** The calendar view is rendered without authentication. If the view or its JavaScript fetches appointment data, appointment data is exposed. Even if it doesn't currently leak data, this is an architectural risk.
- **Confidence:** CONFIRMED (route is unguarded)
- **Recommendation:** Add `->middleware(['auth', 'verified', 'checkRole'])`.

---

### SEC-003 — `APP_DEBUG=true` in Production Environment
- **Category:** Security — Information Disclosure
- **Severity:** CRITICAL
- **Evidence:** `.env` file: `APP_DEBUG=true`, `APP_ENV=local`
- **Why It Matters:** If deployed as-is, stack traces and environment variables are exposed in HTTP responses on any error. Attackers can enumerate file paths, library versions, config values.
- **Confidence:** CONFIRMED (.env is committed and shows debug=true)
- **Recommendation:** Set `APP_DEBUG=false` and `APP_ENV=production` for any non-local deployment. Never commit `.env` to version control.

---

### SEC-004 — APP_KEY Committed to Version Control
- **Category:** Security — Secret Exposure
- **Severity:** CRITICAL
- **Evidence:** `.env` contains `APP_KEY=base64:f4WSt1y1mmBxirztB/agSmNbVlL5wXi86tuZNcBEFhQ=`. The `.env` file is tracked or present in the repository.
- **Why It Matters:** APP_KEY is used to encrypt cookies, sessions, and other encrypted data. A compromised APP_KEY allows session forgery and data decryption.
- **Confidence:** CONFIRMED
- **Recommendation:** Rotate APP_KEY before any production deployment. Add `.env` to `.gitignore` and use environment injection in CI/CD.

---

### SEC-005 — DB Password in Committed `.env`
- **Category:** Security — Secret Exposure
- **Severity:** HIGH
- **Evidence:** `.env`: `DB_PASSWORD=secret`
- **Why It Matters:** Database credentials committed to version control.
- **Confidence:** CONFIRMED
- **Recommendation:** Use secret management (environment variables, vault). Never commit credentials.

---

### SEC-006 — No Rate Limiting on Login Endpoint
- **Category:** Security — Brute Force
- **Severity:** HIGH
- **Evidence:** Auth routes in `routes/auth.php` do not have explicit rate limiting. Breeze's default `AuthenticatedSessionController` may apply a throttle, but no explicit `throttle` middleware is applied to `Route::post('login', ...)`.
- **Why It Matters:** Brute-force password attacks are possible.
- **Confidence:** INFERRED (Breeze may include built-in throttling via `RateLimiter` in `AuthServiceProvider`, but no custom rate limiter is registered in the reviewed code)
- **Recommendation:** Verify Breeze default throttling is active. Explicitly add `->middleware('throttle:login')` to the login route.

---

### SEC-007 — No Global Rate Limiting on Admin Routes
- **Category:** Security — DoS / Abuse
- **Severity:** MEDIUM
- **Evidence:** `Kernel.php` shows `web` middleware group with no throttle. The `api` group has `throttle:api`. Admin routes use `web` only.
- **Why It Matters:** Report endpoints and data-heavy admin pages could be abused without rate limiting.
- **Confidence:** CONFIRMED
- **Recommendation:** Add `throttle:60,1` or similar to admin route group.

---

### SEC-008 — File Upload Without Type/Extension Validation
- **Category:** Security — File Upload
- **Severity:** HIGH
- **Evidence:**
  ```php
  return Storage::putFileAs($directory, $request->file($fileKey),
      now()->format('Y-m-d') . '_' . str_replace(' ', '_', $request->name) . "_{$fileKey}." .
      $request->file($fileKey)->getClientOriginalExtension()
  );
  ```
  The file extension is taken from client-provided MIME type with `getClientOriginalExtension()`. No validation of allowed extensions or MIME types.
- **Affected Area:** `AppHelper::handleFileUpload()` — used for employee photos, ID cards, service images, user photos
- **Why It Matters:** An attacker can upload PHP files or other executable content. Although stored under `local` disk (not `public`), depending on server configuration, these files could be accessible and executed.
- **Confidence:** CONFIRMED
- **Recommendation:** Validate extensions with `mimes:jpeg,jpg,png,gif,webp` rule in Form Requests. Randomize filenames to prevent direct guessing. Confirm storage disk is not web-accessible.

---

### SEC-009 — No Authorization on Appointment Controller
- **Category:** Security — Authorization
- **Severity:** CRITICAL
- **Evidence:** `AppointmentController` has no `middleware()` call and is outside the `checkRole` group. Any authenticated or unauthenticated user can manipulate any appointment record.
- **Confidence:** CONFIRMED (combines SEC-001)
- **Recommendation:** See SEC-001.

---

### SEC-010 — CheckRole Middleware: Route Parameter `$id` Can Be Manipulated
- **Category:** Security — Authorization Bypass
- **Severity:** MEDIUM
- **Evidence:** `AppointmentController::update()` uses `$request->id` (body parameter) instead of the route `{id}` parameter to find the record:
  ```php
  $product = Appointment::findOrFail($request->id);
  ```
  Combined with no auth middleware, a caller can target any appointment.
- **Confidence:** CONFIRMED
- **Recommendation:** Use route model binding and the `$id` parameter from the route.

---

### SEC-011 — dd() Exposes Internal Error Messages to Browser
- **Category:** Security — Information Disclosure
- **Severity:** HIGH
- **Evidence:** `dd($th->getMessage())` in EmployeeController, ServiceController, PurchaseInvoiceController
- **Why It Matters:** Exception messages may contain table names, column names, SQL queries, or other sensitive information.
- **Confidence:** CONFIRMED
- **Recommendation:** Remove all `dd()` from production code paths. Use `Log::error()` and return generic user messages.

---

### SEC-012 — Telescope Available in Production
- **Category:** Security — Information Disclosure
- **Severity:** MEDIUM
- **Evidence:**
  ```php
  if ($this->app->environment('local') && class_exists(TelescopeServiceProvider::class)) {
      $this->app->register(TelescopeServiceProvider::class);
  }
  ```
  Guarded by `environment('local')` check. However, since `APP_ENV=local` in committed `.env`, Telescope IS active.
- **Why It Matters:** Telescope exposes all requests, queries, exceptions, mail, jobs to `/telescope` without additional auth by default.
- **Confidence:** CONFIRMED (APP_ENV=local in .env means telescope is registered)
- **Recommendation:** Set `APP_ENV=production`. Configure Telescope with an explicit gate if needed in production.

---

### SEC-013 — Sales Invoice Receipt Not Protected
- **Category:** Security — Authorization
- **Severity:** MEDIUM
- **Evidence:**
  ```php
  Route::get('admin/sales_invoices/invoice/{id}', [SalesInvoiceController::class, 'showReceipt'])
      ->name('sales_invoices.invoice');
  ```
  This route is inside the `admin` prefix but let's verify... Looking at `routes/web.php` line 58 — this route is **outside** the middleware group (it's before line 61 where the group starts).
- **Why It Matters:** Anyone can view any invoice receipt by guessing an ID.
- **Confidence:** CONFIRMED
- **Recommendation:** Move inside the auth + checkRole middleware group.

---

### SEC-014 — No CSRF Verification for API-Style Endpoints
- **Category:** Security — CSRF
- **Severity:** LOW
- **Evidence:** All form submissions use Blade + CSRF token via `@csrf`. The `VerifyCsrfToken` middleware is in the `web` group. Sales invoice creation from the POS sends AJAX with Laravel's Axios default headers (x-csrf-token). Appears correctly handled.
- **Confidence:** CONFIRMED — CSRF protection appears functional for web routes.
- **Recommendation:** No immediate action. Verify that all AJAX calls include the X-CSRF-TOKEN header.

---

### SEC-015 — No Content Security Policy Headers
- **Category:** Security — XSS Hardening
- **Severity:** MEDIUM
- **Evidence:** No CSP headers configured in Nginx or middleware.
- **Why It Matters:** XSS vulnerabilities could be exploited without CSP.
- **Confidence:** CONFIRMED (not present in reviewed config)
- **Recommendation:** Implement CSP headers via Nginx or Laravel middleware.

---

### SEC-016 — Branch Isolation Not Enforced at Model/Query Level
- **Category:** Security — Data Isolation / Multi-Tenancy
- **Severity:** HIGH
- **Evidence:** Branch filtering is done ad-hoc per controller (e.g., `HomePageController` checks `branch_id != 1`). Sales invoice creation allows any user to select any branch from the dropdown. An authenticated cashier could create invoices for a branch they don't belong to.
- **Why It Matters:** Staff at Branch A could view and manipulate data for Branch B.
- **Confidence:** CONFIRMED (no global scope enforcing branch isolation)
- **Recommendation:** Implement Global Scopes on branch-scoped models, or use a middleware that injects the user's branch into all queries.
