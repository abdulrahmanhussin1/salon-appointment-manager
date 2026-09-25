# HOME-DASHBOARD-IMPLEMENTATION — Salon Appointment Manager

> **Document purpose:** Complete, phased implementation backlog for the dashboard redesign — every task broken down into atomic, independently executable work items with exact code locations, dependencies, and acceptance criteria.
>
> **Companion to:** HOME-DASHBOARD-SPEC.md and HOME-DASHBOARD-BLUEPRINT.md
>
> **Constraint:** DO NOT modify existing application code. DO NOT create migrations. DO NOT install packages. This document is a specification only.
>
> **Generated:** 2026-09-25

---

## IMPLEMENTATION OVERVIEW

```
Phase 1: DashboardController + API Endpoints
Phase 2: Blade Structure + Layout Shell
Phase 3: KPI Card Components
Phase 4: Chart Integration (ApexCharts)
Phase 5: Appointment Operational Panel
Phase 6: Alerts + Activity Feed
Phase 7: Role-Aware Visibility
Phase 8: Responsive + RTL
Phase 9: Performance + Caching
Phase 10: Testing
```

All phases are **sequential** — each depends on the previous.

Estimated effort per task is in T-shirt sizes: XS (<30min) / S (30-60min) / M (1-3h) / L (3-8h) / XL (>8h)

---

## PHASE 1: BACKEND — DashboardController + API Endpoints

### Prerequisites

All 20 REQs must be implemented and passing before starting the dashboard. This is confirmed in PROGRESS.md.

---

### TASK 1.1: Create DashboardController

**File:** `app/Http/Controllers/Admin/DashboardController.php`

**Effort:** M

**Dependencies:** None

**Description:**

Create a new controller that extends `Controller` and uses `HasBranchFilter`. This controller consolidates all dashboard data fetching, separated from the existing `HomePageController`.

```php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\HasBranchFilter;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceDetail;
use App\Models\Expense;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Refund;
use App\Models\InventoryProduct;
use App\Models\InventoryTransaction;
use App\Models\CustomerTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use HasBranchFilter;

    // Methods: summary, revenue, appointments, staff, inventoryAlerts, expenses, activity, alerts
}
```

**Routes to register** in `routes/web.php` inside the `admin` middleware group:

```php
Route::prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/summary', [DashboardController::class, 'summary'])->name('summary');
    Route::get('/revenue', [DashboardController::class, 'revenue'])->name('revenue');
    Route::get('/appointments', [DashboardController::class, 'appointments'])->name('appointments');
    Route::get('/staff', [DashboardController::class, 'staff'])->name('staff');
    Route::get('/inventory-alerts', [DashboardController::class, 'inventoryAlerts'])->name('inventory_alerts');
    Route::get('/expenses', [DashboardController::class, 'expenses'])->name('expenses');
    Route::get('/activity', [DashboardController::class, 'activity'])->name('activity');
    Route::get('/alerts', [DashboardController::class, 'alerts'])->name('alerts');
});
```

**Permission:** All dashboard routes whitelist in `CheckRole.php` under a new rule:
```php
// In CheckRole::handle(), add to whitelist:
if (str_starts_with($routeName, 'dashboard.')) {
    return $next($request); // Auth middleware still applies; permission enforced per section in Blade
}
```

**Acceptance Criteria:**
- DashboardController exists and is loadable
- All 8 endpoints return JSON with a `data` and `meta` key
- Unauthenticated requests return 401/redirect to login
- All endpoints respect `HasBranchFilter` branch scoping

---

### TASK 1.2: Implement `summary()` Endpoint

**Method:** `DashboardController::summary(Request $request)`

**Effort:** M

**Returns:** Single aggregated JSON with all KPI values

**Response structure:**

```json
{
  "data": {
    "revenue": {
      "total": 12450.00,
      "cash": 7200.00,
      "card": 5250.00,
      "services": 9200.00,
      "products": 3250.00,
      "invoice_count": 24,
      "customer_count": 20,
      "avg_ticket": 312.50
    },
    "expenses": {
      "total": 1800.00
    },
    "net_profit": 10650.00,
    "refunds": {
      "total": 150.00,
      "count": 1
    },
    "commissions": {
      "total": 855.00
    },
    "customers": {
      "new_today": 8,
      "total_active": 1240
    },
    "appointments": {
      "total": 24,
      "requested": 3,
      "confirmed": 8,
      "checked_in": 2,
      "in_service": 2,
      "completed": 7,
      "cancelled": 1,
      "no_show": 1
    },
    "top_employee": {
      "name": "Ahmed Hassan",
      "service_count": 8,
      "revenue": 3200.00
    },
    "top_service": {
      "name": "Haircut",
      "count": 18,
      "revenue": 2800.00
    }
  },
  "meta": {
    "branch_id": null,
    "period": "today",
    "from": "2026-09-25",
    "to": "2026-09-25",
    "generated_at": "2026-09-25T10:00:00Z"
  }
}
```

**Key queries:**

```php
// Revenue
$revenue = SalesInvoice::where('status', 'active')
    ->whereDate('invoice_date', $date)
    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
    ->selectRaw('
        SUM(net_total) as total,
        SUM(paid_amount_cash) as cash,
        SUM(payment_method_value) as card,
        COUNT(*) as invoice_count,
        COUNT(DISTINCT customer_id) as customer_count,
        AVG(net_total) as avg_ticket
    ')
    ->first();

// Service/product split
$serviceRevenue = SalesInvoiceDetail::whereHas('salesInvoice', fn($q) =>
    $q->where('status','active')->whereDate('invoice_date',$date)
      ->when($branchId, fn($q) => $q->where('branch_id',$branchId))
)->whereNotNull('service_id')->sum(DB::raw('(customer_price * quantity) - ((discount/100) * customer_price * quantity)'));

// Appointments
$appointments = Appointment::whereDate('start_datetime', $date)
    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
    ->selectRaw('status, COUNT(*) as count')
    ->groupBy('status')
    ->pluck('count', 'status');
```

**Acceptance Criteria:**
- Returns valid JSON within 500ms for a single branch
- Revenue figures match the existing Daily Revenues report
- Appointment counts match the calendar view
- Net profit = revenue.total - expenses.total
- Returns zeroes for empty periods (no errors)

---

### TASK 1.3: Implement `revenue()` Endpoint

**Method:** `DashboardController::revenue(Request $request)`

**Effort:** M

**Params:** `?from=&to=&branch_id=&view_by=day|week|month`

**Returns:** Time-series data for the revenue trend chart

```json
{
  "data": {
    "labels": ["Sep 1", "Sep 2", ...],
    "services": [1200, 1500, ...],
    "products": [300, 450, ...],
    "total": [1500, 1950, ...]
  }
}
```

**Key query:**

```php
$data = DB::table('sales_invoice_details as sid')
    ->join('sales_invoices as si', 'sid.sales_invoice_id', '=', 'si.id')
    ->where('si.status', 'active')
    ->whereBetween('si.invoice_date', [$from, $to])
    ->when($branchId, fn($q) => $q->where('si.branch_id', $branchId))
    ->selectRaw("
        DATE(si.invoice_date) as day,
        SUM(CASE WHEN sid.service_id IS NOT NULL THEN sid.subtotal ELSE 0 END) as services,
        SUM(CASE WHEN sid.product_id IS NOT NULL THEN sid.subtotal ELSE 0 END) as products
    ")
    ->groupBy('day')
    ->orderBy('day')
    ->get();
```

**Acceptance Criteria:**
- Returns one data point per day for the requested range
- Handles gaps (days with zero revenue appear as 0, not missing)
- Respects branch filter
- Data matches Daily Revenues report for the same date range

---

### TASK 1.4: Implement `appointments()` Endpoint

**Method:** `DashboardController::appointments(Request $request)`

**Effort:** S

**Params:** `?date=&branch_id=`

**Returns:** Status breakdown (donut data) + today's appointment list

```json
{
  "data": {
    "status_breakdown": {
      "requested": 3,
      "confirmed": 8,
      "in_service": 2,
      "completed": 7,
      "cancelled": 1,
      "no_show": 1
    },
    "appointments": [
      {
        "id": 142,
        "start_time": "09:00",
        "customer_name": "Sara Khalil",
        "service_name": "Haircut",
        "provider_name": "Ahmed Hassan",
        "status": "completed",
        "duration_minutes": 30,
        "can_confirm": false,
        "can_check_in": false,
        "can_complete": false,
        "can_no_show": false,
        "can_cancel": false
      }
    ]
  }
}
```

**Acceptance Criteria:**
- Status breakdown matches calendar view
- Each appointment includes the correct set of `can_*` flags based on status
- Results ordered by `start_datetime ASC`
- Branch scoped correctly

---

### TASK 1.5: Implement `staff()` Endpoint

**Method:** `DashboardController::staff(Request $request)`

**Effort:** S

**Params:** `?from=&to=&branch_id=`

**Returns:** Staff performance ranking

```json
{
  "data": [
    {
      "employee_id": 5,
      "name": "Ahmed Hassan",
      "service_count": 8,
      "revenue": 3200.00,
      "commission": 320.00,
      "appointment_count": 5,
      "branch": "Zamalek"
    }
  ]
}
```

**Query:**

```php
DB::table('sales_invoice_details as sid')
    ->join('sales_invoices as si', 'sid.sales_invoice_id', '=', 'si.id')
    ->join('employees as e', 'e.id', '=', 'sid.provider_id')
    ->leftJoin('branches as b', 'b.id', '=', 'e.branch_id')
    ->where('si.status', 'active')
    ->whereBetween('si.invoice_date', [$from, $to])
    ->whereNotNull('sid.service_id')
    ->when($branchId, fn($q) => $q->where('si.branch_id', $branchId))
    ->selectRaw('e.id, e.name, b.name as branch, COUNT(sid.id) as service_count, SUM(sid.subtotal) as revenue, SUM(sid.commission_amount) as commission')
    ->groupBy('e.id', 'e.name', 'b.name')
    ->orderByDesc('revenue')
    ->limit(10)
    ->get();
```

**Acceptance Criteria:**
- Returns top 10 employees by revenue
- Commission figures reflect `commission_amount` column (not revenue — post REQ-012)
- Branch filtered
- Empty collection when no services sold in period

---

### TASK 1.6: Implement `inventoryAlerts()` Endpoint

**Method:** `DashboardController::inventoryAlerts(Request $request)`

**Effort:** S

**Params:** `?branch_id=`

**Returns:** Out-of-stock and low-stock products

```json
{
  "data": {
    "out_of_stock": [
      {
        "product_id": 12,
        "product_name": "Moroccan Argan Oil",
        "category": "Retail Products",
        "branch": "Zamalek",
        "inventory_id": 3
      }
    ],
    "low_stock": [
      {
        "product_id": 8,
        "product_name": "Hair Color 6.0",
        "current_quantity": 3,
        "threshold": 5,
        "category": "Consumables",
        "branch": "Zamalek",
        "inventory_id": 3
      }
    ]
  }
}
```

**Note:** `reorder_point` column does not yet exist on `products`. Use a hardcoded threshold of `5` units as interim. Document this in the implementation note.

**Query:**

```php
// Out of stock
$outOfStock = DB::table('inventory_products as ip')
    ->join('products as p', 'p.id', '=', 'ip.product_id')
    ->join('inventories as i', 'i.id', '=', 'ip.inventory_id')
    ->join('branches as b', 'b.id', '=', 'i.branch_id')
    ->leftJoin('product_categories as pc', 'pc.id', '=', 'p.product_category_id')
    ->where('ip.quantity', '=', 0)
    ->when($branchId, fn($q) => $q->where('b.id', $branchId))
    ->select('p.id as product_id', 'p.name as product_name', 'pc.name as category', 'b.name as branch', 'ip.inventory_id')
    ->get();

// Low stock (interim: quantity <= 5)
$lowStock = DB::table('inventory_products as ip')
    ->join('products as p', 'p.id', '=', 'ip.product_id')
    ->join('inventories as i', 'i.id', '=', 'ip.inventory_id')
    ->join('branches as b', 'b.id', '=', 'i.branch_id')
    ->leftJoin('product_categories as pc', 'pc.id', '=', 'p.product_category_id')
    ->where('ip.quantity', '>', 0)
    ->where('ip.quantity', '<=', 5)
    ->when($branchId, fn($q) => $q->where('b.id', $branchId))
    ->select('p.id as product_id', 'p.name as product_name', 'ip.quantity as current_quantity', DB::raw('5 as threshold'), 'pc.name as category', 'b.name as branch', 'ip.inventory_id')
    ->get();
```

**Acceptance Criteria:**
- Returns distinct products per branch (one row per inventory_product)
- Zero quantity = out_of_stock
- Quantity 1-5 = low_stock (interim threshold)
- Branch scoped via inventories.branch_id
- Empty arrays (not errors) when no alerts

---

### TASK 1.7: Implement `expenses()` Endpoint

**Method:** `DashboardController::expenses(Request $request)`

**Effort:** S

**Returns:** Expense totals + category breakdown

```json
{
  "data": {
    "total": 1800.00,
    "by_category": [
      { "category": "Supplies", "total": 756.00, "percentage": 42 },
      { "category": "Utilities", "total": 504.00, "percentage": 28 }
    ]
  }
}
```

**Acceptance Criteria:**
- Totals match Daily Revenues report
- Category breakdown uses `expense_types.name`
- Branch scoped
- Percentages sum to 100

---

### TASK 1.8: Implement `activity()` Endpoint

**Method:** `DashboardController::activity(Request $request)`

**Effort:** M

**Params:** `?branch_id=&limit=15`

**Description:** Aggregate recent events from multiple tables. Each event has: `type`, `description`, `entity_id`, `url`, `timestamp`.

**Event sources and their queries:**

```php
// Invoices
$invoices = SalesInvoice::where('status','active')
    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
    ->with(['customer', 'createdBy'])
    ->latest()
    ->limit($limit)
    ->get()
    ->map(fn($i) => [
        'type' => 'invoice_created',
        'description' => "Invoice #{$i->id} — EGP {$i->net_total} — {$i->customer?->name}",
        'url' => route('sales_invoices.show', $i->id),
        'timestamp' => $i->created_at,
    ]);

// Voided invoices
$voids = SalesInvoice::where('status','voided')
    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
    ->with(['customer'])
    ->latest('voided_at')
    ->limit(5)
    ->get()
    ->map(fn($i) => [
        'type' => 'invoice_voided',
        'description' => "Invoice #{$i->id} voided — {$i->customer?->name}",
        'url' => route('sales_invoices.show', $i->id),
        'timestamp' => $i->voided_at,
    ]);

// Appointments completed
$completedAppts = Appointment::where('status', 'completed')
    ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
    ->with(['customer', 'provider', 'service'])
    ->latest('updated_at')
    ->limit(5)
    ->get()
    ->map(fn($a) => [
        'type' => 'appointment_completed',
        'description' => "{$a->customer?->name} — {$a->service?->name} by {$a->provider?->name}",
        'url' => route('calender'),
        'timestamp' => $a->updated_at,
    ]);

// Refunds
$refunds = Refund::when($branchId, fn($q) => $q->where('branch_id', $branchId))
    ->latest()
    ->limit(5)
    ->get()
    ->map(fn($r) => [
        'type' => 'refund_issued',
        'description' => "Refund #{$r->refund_number} — EGP {$r->total_refund_amount}",
        'url' => route('refunds.show', $r->id),
        'timestamp' => $r->created_at,
    ]);

// New customers
$customers = Customer::latest()
    ->limit(5)
    ->get()
    ->map(fn($c) => [
        'type' => 'customer_registered',
        'description' => "New customer: {$c->name}",
        'url' => route('customers.show', $c->id),
        'timestamp' => $c->created_at,
    ]);

// Stock adjustments
$adjustments = InventoryTransaction::where('transaction_type', 'adjustment')
    ->when($branchId, fn($q) => $q->whereHas('sourceInventory', fn($sq) => $sq->where('branch_id', $branchId)))
    ->with('createdBy')
    ->latest()
    ->limit(5)
    ->get()
    ->map(fn($t) => [
        'type' => 'stock_adjusted',
        'description' => "Stock adjustment — {$t->adjustment_type} — by {$t->createdBy?->name}",
        'url' => route('inventory_transactions.history'),
        'timestamp' => $t->created_at,
    ]);

// Merge, sort by timestamp DESC, limit
$feed = $invoices->merge($voids)->merge($completedAppts)->merge($refunds)
    ->merge($customers)->merge($adjustments)
    ->sortByDesc('timestamp')
    ->take($limit)
    ->values();
```

**Acceptance Criteria:**
- Returns merged, time-sorted feed
- Each item has `type`, `description`, `url`, `timestamp`
- Timestamps formatted as ISO 8601
- Respects branch scope
- Limit parameter honored (default 15)
- Empty array when no events

---

### TASK 1.9: Implement `alerts()` Endpoint

**Method:** `DashboardController::alerts(Request $request)`

**Effort:** S

**Returns:** Actionable alerts

```json
{
  "data": [
    {
      "id": "ALERT-001-1745321",
      "type": "unconfirmed_appointments",
      "severity": "warning",
      "title": "3 appointments are unconfirmed",
      "body": "Ahmed (10:00), Sara (2:30), Leila (4:00) are waiting for confirmation.",
      "count": 3,
      "action_label": "Go to Appointments",
      "action_url": "/admin/calender"
    },
    {
      "id": "ALERT-004-12",
      "type": "out_of_stock",
      "severity": "critical",
      "title": "Moroccan Argan Oil is out of stock",
      "body": "Cairo - Zamalek",
      "count": 1,
      "action_label": "View Inventory",
      "action_url": "/admin/inventories"
    }
  ]
}
```

**Alerts to check:**

1. `unconfirmed_appointments`: `appointments.status='requested'` AND future date AND branch
2. `no_show_today`: `appointments.status='no_show'` AND today AND branch
3. `out_of_stock`: `inventory_products.quantity=0` AND branch
4. `low_stock`: `inventory_products.quantity<=5` AND branch
5. `old_draft_invoices`: `sales_invoices.status='draft'` AND `created_at < NOW()-4h` AND branch
6. `outstanding_balance_invoices`: `sales_invoices.balance_due>0` AND `status='active'` AND branch
7. `high_cancellation_rate`: today cancellation% > 20

**Acceptance Criteria:**
- Returns array of alert objects sorted by severity (critical first)
- Each alert has a unique `id`
- Respects branch scope
- No alerts = empty array

---

## PHASE 2: BLADE STRUCTURE + LAYOUT SHELL

### TASK 2.1: Update HomePageController to Use DashboardController

**File:** `app/Http/Controllers/Admin/HomePageController.php`

**Effort:** XS

**Description:** The existing `HomePageController::index()` should redirect to or call the new dashboard view. Update it to return the new dashboard Blade view instead of the old `admin.home`.

**Change:**
```php
public function index()
{
    return view('admin.home');
    // (no data passed server-side — all data fetched client-side via AJAX)
}
```

**Acceptance Criteria:**
- Visiting `/admin/home` renders the new dashboard view
- No server-side data is passed (all fetched via AJAX)

---

### TASK 2.2: Create Dashboard Blade View

**File:** `resources/views/admin/home.blade.php` (replace existing)

**Effort:** L

**Description:** Replace the existing home.blade.php with the new dashboard layout.

**Structure:**
```blade
@extends('admin.layouts.app')
@section('title', __('Dashboard'))

@section('content')
<div x-data="dashboard()" x-init="init()">

    {{-- Zone 1: Header --}}
    @include('admin.components.dashboard.header')

    {{-- Zone 2: Filter Bar --}}
    @include('admin.components.dashboard.filters')

    {{-- Zone 3: Quick Actions --}}
    @include('admin.components.dashboard.quick-actions')

    {{-- Zone 4: KPI Grid --}}
    <section aria-label="{{ __('Key Performance Indicators') }}" aria-live="polite">
        @include('admin.components.dashboard.kpi-grid')
    </section>

    {{-- Zone 5: Operational Status --}}
    @include('admin.components.dashboard.operational-status')

    {{-- Zone 6: Revenue Trend --}}
    @include('admin.components.dashboard.revenue-trend')

    {{-- Zone 7: Branch + Expense (Owner/Admin only) --}}
    @if(AppHelper::perUser('reports.index'))
        @include('admin.components.dashboard.branch-expense')
    @endif

    {{-- Zone 8: Staff Performance --}}
    @if(AppHelper::perUser('employees.index'))
        @include('admin.components.dashboard.staff-performance')
    @endif

    {{-- Zone 9: Service + Product Ranking --}}
    @if(AppHelper::perUser('reports.index'))
        @include('admin.components.dashboard.service-product-ranking')
    @endif

    {{-- Zone 10: Inventory Alerts + Action Required --}}
    @include('admin.components.dashboard.alerts-inventory')

    {{-- Zone 11: Recent Activity --}}
    @include('admin.components.dashboard.recent-activity')

</div>
@endsection
```

**Acceptance Criteria:**
- Page renders without PHP errors
- Zones are structured as Blade includes (not inline)
- Alpine.js `dashboard()` component is the reactive state manager
- Each zone is independently aria-labeled
- Page title is "Dashboard"

---

### TASK 2.3: Create Alpine.js Dashboard State Manager

**File:** `public/admin-assets/assets/js/dashboard.js` (new file)

**Effort:** L

**Description:** Central Alpine.js component managing state for all dashboard widgets.

**Structure:**
```javascript
function dashboard() {
    return {
        // State
        filters: {
            branchId: null,
            period: 'today',
            from: null,
            to: null,
        },
        loading: {
            summary: true,
            revenue: true,
            appointments: true,
            staff: true,
            inventory: true,
            expenses: true,
            activity: true,
            alerts: true,
        },
        errors: {},
        data: {
            summary: null,
            revenue: null,
            appointments: null,
            staff: null,
            inventory: null,
            expenses: null,
            activity: [],
            alerts: [],
        },

        // Initialize
        init() {
            this.loadFromUrl();
            this.fetchAll();
        },

        // Load filter state from URL
        loadFromUrl() {
            const params = new URLSearchParams(window.location.search);
            this.filters.branchId = params.get('branch_id');
            this.filters.period = params.get('period') || 'today';
        },

        // Fetch all widgets in parallel (priority order)
        fetchAll() {
            // Priority 1: Summary + Appointments (critical)
            Promise.all([
                this.fetchSummary(),
                this.fetchAppointments(),
            ]).then(() => {
                // Priority 2: Revenue + Alerts
                return Promise.all([
                    this.fetchRevenue(),
                    this.fetchAlerts(),
                    this.fetchInventory(),
                ]);
            }).then(() => {
                // Priority 3: Staff + Expenses + Activity
                Promise.all([
                    this.fetchStaff(),
                    this.fetchExpenses(),
                    this.fetchActivity(),
                ]);
            });
        },

        // Individual fetch methods
        async fetchSummary() {
            this.loading.summary = true;
            try {
                const res = await axios.get('/dashboard/summary', { params: this.getParams('today') });
                this.data.summary = res.data.data;
            } catch (e) {
                this.errors.summary = true;
            } finally {
                this.loading.summary = false;
            }
        },

        // ... repeat for each endpoint ...

        // Apply new filter
        applyFilters() {
            this.updateUrl();
            this.fetchAll();
        },

        // Update URL with current filter state
        updateUrl() {
            const params = new URLSearchParams();
            if (this.filters.branchId) params.set('branch_id', this.filters.branchId);
            params.set('period', this.filters.period);
            history.replaceState(null, '', '?' + params.toString());
        },

        // Build request params
        getParams(period) {
            return {
                branch_id: this.filters.branchId,
                period: this.filters.period || period,
                from: this.filters.from,
                to: this.filters.to,
            };
        },

        // Currency formatter
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-EG', {
                style: 'currency',
                currency: 'EGP',
                minimumFractionDigits: 2,
            }).format(amount || 0);
        },

        // Percentage change formatter
        formatChange(current, previous) {
            if (!previous || previous === 0) return null;
            return ((current - previous) / previous * 100).toFixed(1);
        },
    }
}
```

**Acceptance Criteria:**
- All fetch methods work independently (one failure doesn't block others)
- Filter changes trigger full re-fetch
- URL is updated on filter change
- Currency and number formatting is consistent
- Loading states managed per widget

---

## PHASE 3: KPI CARD COMPONENTS

### TASK 3.1: Create KPI Card Blade Component

**File:** `resources/views/components/dashboard-kpi-card.blade.php`

**Effort:** M

**Props:**
- `id` (string) - unique ID for the card
- `title` (string) - metric name (translatable)
- `icon` (string) - Bootstrap Icon class
- `icon-color` (string) - icon color class
- `loading-key` (string) - Alpine key for loading state
- `value-key` (string) - Alpine key for value
- `link` (string) - drill-down URL
- `link-label` (string) - drill-down label

**Template:**
```blade
<div id="{{ $id }}" class="card info-card h-100">
    <div class="card-body">
        {{-- Title --}}
        <h5 class="card-title">
            {{ __($title) }}
            <span>| {{ __('Today') }}</span>
        </h5>

        {{-- Loading skeleton --}}
        <template x-if="loading.{{ $loadingKey }}">
            <div class="skeleton-card">
                <div class="skeleton skeleton-line" style="width: 60%"></div>
                <div class="skeleton skeleton-line" style="width: 40%"></div>
            </div>
        </template>

        {{-- Error state --}}
        <template x-if="errors.{{ $loadingKey }} && !loading.{{ $loadingKey }}">
            <div class="text-danger small">
                <i class="bi bi-exclamation-triangle"></i>
                {{ __('Could not load') }}
                <button class="btn btn-link btn-sm p-0" @click="{{ $fetchMethod }}()">
                    {{ __('Retry') }}
                </button>
            </div>
        </template>

        {{-- Content --}}
        <template x-if="!loading.{{ $loadingKey }} && !errors.{{ $loadingKey }}">
            <div class="d-flex align-items-center">
                <div class="card-icon rounded-circle d-flex align-items-center justify-content-center"
                     style="{{ $iconBg }}">
                    <i class="bi {{ $icon }}" style="{{ $iconColor }}"></i>
                </div>
                <div class="ps-3">
                    <h6 x-text="formatCurrency({{ $valueExpr }})"></h6>
                    {{-- Change indicator (if comparison period available) --}}
                    <span x-show="{{ $changeExpr }} !== null"
                          :class="{'text-success': {{ $changeExpr }} > 0, 'text-danger': {{ $changeExpr }} < 0}"
                          class="small">
                        <i :class="{'bi-arrow-up': {{ $changeExpr }} > 0, 'bi-arrow-down': {{ $changeExpr }} < 0}" class="bi"></i>
                        <span x-text="Math.abs({{ $changeExpr }}) + '%'"></span>
                        <span class="text-muted">{{ __('vs. yesterday') }}</span>
                    </span>
                </div>
            </div>
            <a href="{{ $link }}" class="small text-muted mt-2 d-block">
                {{ $linkLabel }} &rarr;
            </a>
        </template>

    </div>
</div>
```

**Acceptance Criteria:**
- Loading state shows skeleton animation
- Error state shows retry button that calls the fetch method
- Content state shows value + change indicator
- Change indicator is green for positive values, red for negative (per semantic rules in SPEC section 7)
- Fully accessible with proper ARIA labels

---

### TASK 3.2: Create KPI Grid Include

**File:** `resources/views/admin/components/dashboard/kpi-grid.blade.php`

**Effort:** M

**Description:** Assemble the 8 KPI cards for Owner/Admin view. Use permission checks to conditionally render revenue vs. operational KPIs.

```blade
<div class="row row-cols-1 row-cols-sm-2 row-cols-md-2 row-cols-xl-4 g-4 mb-4">

    {{-- KPI-001: Total Revenue (Owner, Admin, Manager, Accountant) --}}
    @if(AppHelper::perUser('reports.index'))
    <div class="col">
        <x-dashboard-kpi-card
            id="kpi-revenue"
            title="Total Revenue"
            icon="bi-currency-dollar"
            loading-key="summary"
            value-expr="data.summary?.revenue?.total"
            change-expr="null"
            link="{{ route('report.daily_revenues') }}"
            link-label="{{ __('View Reports') }}"
        />
    </div>
    @endif

    {{-- KPI-002: Cash Sales --}}
    @if(AppHelper::perUser('reports.index') || auth()->user()->hasRole('cashier'))
    <div class="col">
        <x-dashboard-kpi-card
            id="kpi-cash"
            title="Cash Sales"
            icon="bi-cash-stack"
            loading-key="summary"
            value-expr="data.summary?.revenue?.cash"
            ...
        />
    </div>
    @endif

    {{-- ... remaining cards ... --}}

    {{-- KPI-006: Appointments Today (All operational roles) --}}
    <div class="col">
        <x-dashboard-kpi-card
            id="kpi-appointments"
            title="Appointments Today"
            icon="bi-calendar-check"
            loading-key="appointments"
            value-expr="data.summary?.appointments?.total"
            link="{{ route('calender') }}"
            link-label="{{ __('View Calendar') }}"
        />
    </div>

</div>
```

**Acceptance Criteria:**
- Only cards the user has permission to view are rendered
- All cards use the shared KPI Card component
- Responsive grid: 4 columns on xl, 2 on md, 1 on sm

---

## PHASE 4: CHARTS

### TASK 4.1: Integrate ApexCharts Library

**File:** `resources/views/admin/layouts/app.blade.php` (add to scripts section)

**Effort:** XS

**Note:** ApexCharts CDN or npm install. Check if already in package.json first.

```html
<!-- In <head> or before </body> -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@latest"></script>
```

**OR** add to `package.json`:
```json
"apexcharts": "^3.x"
```
and import in the appropriate JS entrypoint.

**Acceptance Criteria:**
- `ApexCharts` is available globally
- No JS console errors

---

### TASK 4.2: Revenue Trend Chart Component

**File:** `resources/views/admin/components/dashboard/revenue-trend.blade.php`

**Effort:** L

**Description:** Multi-series line chart using ApexCharts. Chart is initialized in Alpine.js `watch` callback when `data.revenue` changes.

```blade
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="card-title m-0">{{ __('Revenue Trend') }}</h5>
            <div class="btn-group btn-group-sm">
                <button @click="filters.viewBy='day'; fetchRevenue()" class="btn btn-outline-secondary">{{ __('Day') }}</button>
                <button @click="filters.viewBy='week'; fetchRevenue()" class="btn btn-outline-secondary">{{ __('Week') }}</button>
                <button @click="filters.viewBy='month'; fetchRevenue()" class="btn btn-outline-secondary">{{ __('Month') }}</button>
            </div>
        </div>

        {{-- Loading --}}
        <div x-show="loading.revenue" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
        </div>

        {{-- Chart container --}}
        <div x-show="!loading.revenue" id="revenue-trend-chart"></div>

        {{-- Accessible data table fallback --}}
        <table class="visually-hidden" aria-label="{{ __('Revenue Trend Data') }}">
            <thead><tr><th>Date</th><th>Services</th><th>Products</th><th>Total</th></tr></thead>
            <tbody>
                <template x-for="(item, i) in (data.revenue?.labels || [])" :key="i">
                    <tr>
                        <td x-text="item"></td>
                        <td x-text="data.revenue?.services[i]"></td>
                        <td x-text="data.revenue?.products[i]"></td>
                        <td x-text="data.revenue?.total[i]"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</div>
```

**Alpine.js Chart Initialization** (inside `dashboard.js`):

```javascript
// In fetchRevenue() after data is set:
this.$nextTick(() => {
    const options = {
        chart: { type: 'line', height: 300, toolbar: { show: false } },
        series: [
            { name: __('Total'), data: this.data.revenue.total },
            { name: __('Services'), data: this.data.revenue.services },
            { name: __('Products'), data: this.data.revenue.products },
        ],
        xaxis: { categories: this.data.revenue.labels },
        yaxis: { labels: { formatter: (val) => 'EGP ' + val.toFixed(0) } },
        colors: ['#4154f1', '#2eca6a', '#ff771d'],
        stroke: { curve: 'smooth', width: 2 },
        tooltip: { y: { formatter: (val) => 'EGP ' + val.toFixed(2) } },
        legend: { position: 'top' },
        rtl: document.documentElement.dir === 'rtl',
    };

    if (this.revenueChart) {
        this.revenueChart.updateOptions(options);
    } else {
        this.revenueChart = new ApexCharts(
            document.querySelector('#revenue-trend-chart'),
            options
        );
        this.revenueChart.render();
    }
});
```

**Acceptance Criteria:**
- Chart renders with 3 series (Total, Services, Products)
- Chart updates when filter changes
- Loading spinner shows while fetching
- RTL-aware (chart flips if page direction is RTL)
- Accessible data table is rendered but hidden from visual display

---

### TASK 4.3: Appointment Status Donut Chart

**File:** `resources/views/admin/components/dashboard/operational-status.blade.php`

**Effort:** M

**Description:** Donut chart showing appointment status breakdown. Uses same ApexCharts integration pattern.

**Chart config:**
```javascript
{
    chart: { type: 'donut', height: 280 },
    series: [requested, confirmed, in_service, completed, cancelled, no_show],
    labels: ['Requested', 'Confirmed', 'In Service', 'Completed', 'Cancelled', 'No Show'],
    colors: ['#3b82f6', '#0d9488', '#f59e0b', '#22c55e', '#ef4444', '#7f1d1d'],
    legend: { position: 'right' },
}
```

**Acceptance Criteria:**
- Correct status colors per BLUEPRINT Section D
- Updates when date/branch filter changes
- Donut center shows total appointment count
- Clicking a segment filters the appointment table by that status

---

### TASK 4.4: Branch Performance Bar Chart

**File:** Part of `resources/views/admin/components/dashboard/branch-expense.blade.php`

**Effort:** M

**Visibility:** Owner/Admin only (wrapped in `@if(AppHelper::perUser('reports.index'))`)

**Description:** Horizontal bar chart comparing revenue across branches.

**Acceptance Criteria:**
- Only visible to Owner/Admin
- Shows all branches regardless of current branch filter (branch filter selector disabled for this chart)
- Clicking a branch bar sets the global branch filter to that branch

---

### TASK 4.5: Expense Category Donut Chart

**File:** Part of `resources/views/admin/components/dashboard/branch-expense.blade.php`

**Effort:** S

**Description:** Donut chart of expense by category. Same pattern as appointment donut.

**Acceptance Criteria:**
- Filtered by current branch selection
- Updates on filter change
- Shows category name + amount + percentage in legend

---

## PHASE 5: APPOINTMENT OPERATIONAL PANEL

### TASK 5.1: Today's Appointment Table

**File:** Part of `resources/views/admin/components/dashboard/operational-status.blade.php`

**Effort:** L

**Description:** Interactive appointment table with inline status action buttons. Uses the Alpine.js `data.appointments` state.

**Key interactions:**

- **Confirm button** (status=requested):
  ```javascript
  async confirmAppointment(id) {
      await axios.patch(`/admin/appointments/${id}/confirm`);
      await this.fetchAppointments(); // refresh
  }
  ```

- **Check In button** (status=confirmed):
  ```javascript
  async checkInAppointment(id) {
      await axios.patch(`/admin/appointments/${id}/check-in`);
      await this.fetchAppointments();
  }
  ```

- **Complete button** (status=checked_in or in_service):
  Redirect to invoice creation page if no invoice exists, or just call complete endpoint.

- **No Show / Cancel**: Show confirmation modal before action

**Status badge rendering:**
```blade
<span x-bind:class="{
    'badge bg-primary': appt.status === 'requested',
    'badge bg-info text-dark': appt.status === 'confirmed',
    'badge bg-warning text-dark': appt.status === 'checked_in',
    'badge bg-warning': appt.status === 'in_service',
    'badge bg-success': appt.status === 'completed',
    'badge bg-danger': appt.status === 'cancelled',
    'badge': appt.status === 'no_show',
}" x-text="appt.status_label"></span>
```

**Acceptance Criteria:**
- Status badges match design spec colors
- Action buttons appear/disappear based on current status per BLUEPRINT Section I
- Actions call the existing appointment endpoints (no new endpoints needed)
- Table refreshes after each action
- Receptionist sees all appointments; Provider sees only their own
- Empty state shown when no appointments

---

## PHASE 6: ALERTS + ACTIVITY FEED

### TASK 6.1: Alert Cards Component

**File:** `resources/views/admin/components/dashboard/alerts-inventory.blade.php`

**Effort:** M

**Description:** Two-column layout: Inventory Alerts (left) + Action Required (right). Uses `data.alerts` and `data.inventory` state.

```blade
<div class="row g-4 mb-4">
    {{-- Inventory Alerts (role-gated) --}}
    @if(AppHelper::perUser('inventories.index'))
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">{{ __('Inventory Alerts') }}</h5>

                {{-- Loading --}}
                <template x-if="loading.inventory">
                    <div><!-- skeleton rows --></div>
                </template>

                {{-- Out of stock --}}
                <template x-for="item in data.inventory?.out_of_stock || []" :key="item.product_id + '-oos'">
                    <div class="alert alert-danger d-flex justify-content-between align-items-start">
                        <div>
                            <strong class="d-block" x-text="item.product_name"></strong>
                            <small x-text="item.branch + ' — ' + item.category"></small>
                        </div>
                        <div>
                            <a :href="`/admin/purchase_invoices/create?product_id=${item.product_id}`" class="btn btn-sm btn-danger">{{ __('Purchase') }}</a>
                        </div>
                    </div>
                </template>

                {{-- Low stock --}}
                <template x-for="item in data.inventory?.low_stock || []" :key="item.product_id + '-low'">
                    <div class="alert alert-warning d-flex justify-content-between">
                        <div>
                            <strong x-text="item.product_name"></strong>
                            <small class="d-block" x-text="`${item.current_quantity} units remaining`"></small>
                        </div>
                        <div>
                            <a :href="`/admin/inventory_transactions/adjust`" class="btn btn-sm btn-outline-warning">{{ __('Adjust') }}</a>
                        </div>
                    </div>
                </template>

                {{-- Empty state --}}
                <template x-if="!loading.inventory && data.inventory?.out_of_stock?.length === 0 && data.inventory?.low_stock?.length === 0">
                    <div class="text-center text-success py-3">
                        <i class="bi bi-check-circle fs-4"></i>
                        <p class="mt-2 mb-0">{{ __('All stock levels are healthy') }}</p>
                    </div>
                </template>
            </div>
        </div>
    </div>
    @endif

    {{-- Action Required --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">{{ __('Action Required') }}</h5>

                <template x-for="alert in data.alerts" :key="alert.id">
                    <div :class="{
                        'alert-danger': alert.severity === 'critical',
                        'alert-warning': alert.severity === 'warning',
                        'alert-info': alert.severity === 'info',
                    }" class="alert d-flex justify-content-between align-items-start">
                        <div>
                            <strong x-text="alert.title"></strong>
                            <small class="d-block" x-text="alert.body"></small>
                        </div>
                        <a :href="alert.action_url" class="btn btn-sm btn-outline-secondary ms-2" x-text="alert.action_label"></a>
                    </div>
                </template>

                <template x-if="!loading.alerts && data.alerts?.length === 0">
                    <div class="text-center text-success py-3">
                        <i class="bi bi-check-circle fs-4"></i>
                        <p class="mt-2 mb-0">{{ __('Nothing requires your attention right now') }}</p>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
```

**Acceptance Criteria:**
- Critical alerts (red) appear before warnings (yellow) before info (blue)
- Inventory alerts section is hidden if user lacks `inventories.index` permission
- Empty states shown when no alerts
- All action buttons link to correct routes

---

### TASK 6.2: Recent Activity Feed Component

**File:** `resources/views/admin/components/dashboard/recent-activity.blade.php`

**Effort:** S

**Acceptance Criteria:**
- Shows last 15 events
- Each event has a contextually appropriate icon
- Timestamps formatted as "5 min ago" (use `Carbon::diffForHumans()` on the server or moment.js/Day.js on the client)
- Activity items are role-scoped (financial events hidden from receptionist)
- "View All" link opens to a future audit log page (or the most relevant existing report)

---

## PHASE 7: ROLE-AWARE VISIBILITY

### TASK 7.1: PHP/Blade Permission Guards

**Effort:** S

**Description:** Audit all dashboard sections and ensure `@if(AppHelper::perUser(...))` guards are applied correctly per BLUEPRINT Section N.

**Checklist:**
- [ ] Revenue KPIs: `@if(AppHelper::perUser('reports.index'))`
- [ ] Net Profit KPI: Same
- [ ] Branch Comparison Chart: `@if(auth()->user()->hasRole(['owner','admin']))`
- [ ] Staff Performance: `@if(AppHelper::perUser('employees.index'))`
- [ ] Service/Product Ranking: `@if(AppHelper::perUser('reports.index'))`
- [ ] Inventory Alerts: `@if(AppHelper::perUser('inventories.index'))`
- [ ] Expense Overview: `@if(AppHelper::perUser('expenses.index'))`
- [ ] Quick Actions: Each individually guarded

**Acceptance Criteria:**
- Cashier cannot see staff commission data
- Receptionist cannot see revenue KPIs or financial charts
- Provider can only see their own appointment data
- All permission checks use `AppHelper::perUser()` (consistent with rest of app)

---

### TASK 7.2: Alpine.js Client-Side Visibility

**Effort:** S

**Description:** Some visibility is conditional on fetched data (not just permissions). Use Alpine.js `x-show` for these:

- Show "No comparison available" text for KPIs when no previous period data
- Show "New Branch" empty state if all values are zero
- Show provider-specific appointment filter when user is a stylist/provider role

**Pass user role and branch to Alpine** via a Blade variable:

```blade
<div x-data="dashboard({
    userRole: '{{ auth()->user()->roles->first()?->name }}',
    userBranchId: {{ auth()->user()->employee?->branch_id ?? 'null' }},
    canSelectAllBranches: {{ auth()->user()->can('reports.index') ? 'true' : 'false' }},
    providerId: {{ auth()->user()->employee?->id ?? 'null' }},
})">
```

**Acceptance Criteria:**
- Provider dashboard pre-filters appointments to their own `provider_id`
- Admin/Owner branch selector is enabled; others see read-only branch name

---

## PHASE 8: RESPONSIVE + RTL

### TASK 8.1: CSS/Tailwind Responsive Classes

**File:** Relevant Blade components

**Effort:** M

**Description:** Ensure every grid uses responsive Tailwind classes matching BLUEPRINT Section M:

```html
<!-- KPI Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">

<!-- Charts (2-column on desktop, 1-column on tablet/mobile) -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

<!-- Staff Performance (full width) -->
<div class="col-span-12">

<!-- Mobile: hide less important sections -->
<div class="hidden md:block">
    <!-- Branch comparison chart -->
</div>
```

**Acceptance Criteria:**
- KPI grid: 4 cols on XL, 2 on MD, 1 on SM
- Charts: 2 cols on LG+, 1 col on MD-
- Appointment table columns are simplified on mobile (hide duration, show only Time/Customer/Status/Action)
- No horizontal scrolling on mobile

---

### TASK 8.2: RTL Support

**File:** `resources/views/admin/components/dashboard/*.blade.php` + CSS

**Effort:** S

**Description:** Ensure all dashboard components work in RTL mode.

**Implementation:**
- All flex rows: `flex-row-reverse` in RTL via CSS `[dir=rtl] .flex { flex-direction: row-reverse; }`
- ApexCharts: `rtl: document.documentElement.dir === 'rtl'` in all chart configs
- KPI cards: value alignment and arrow icons must flip
- Percentage change arrows: use bidirectional-aware icons

**CSS additions:**
```css
/* RTL overrides for dashboard */
[dir=rtl] .dashboard-kpi-card .ps-3 {
    padding-left: 0;
    padding-right: 1rem;
}

[dir=rtl] .activity-feed .activity-time {
    float: left;
}

[dir=rtl] .bi-arrow-up::before {
    content: "\f148"; /* RTL-appropriate icon if needed */
}
```

**Acceptance Criteria:**
- Dashboard looks correct when `<html dir="rtl">`
- Charts have `rtl: true` config
- No text overflow issues in Arabic
- Icon directions are correct

---

### TASK 8.3: Mobile FAB (Floating Action Button)

**File:** `resources/views/admin/components/dashboard/quick-actions.blade.php`

**Effort:** S

**Description:** On mobile, replace the horizontal quick-action buttons with a FAB.

```blade
<!-- Desktop: horizontal buttons -->
<div class="hidden md:flex gap-2 mb-4">
    @if(AppHelper::perUser('appointments.create'))
        <a href="{{ route('calender') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle"></i> {{ __('New Appointment') }}
        </a>
    @endif
    {{-- ... --}}
</div>

<!-- Mobile: FAB -->
<div class="md:hidden" x-data="{ fabOpen: false }">
    <button @click="fabOpen = !fabOpen"
            class="fixed bottom-6 right-6 w-14 h-14 bg-primary rounded-full shadow-lg text-white z-50 flex items-center justify-center"
            aria-label="{{ __('Quick Actions') }}">
        <i class="bi" :class="fabOpen ? 'bi-x-lg' : 'bi-plus-lg'"></i>
    </button>

    <div x-show="fabOpen" @click.away="fabOpen = false"
         class="fixed bottom-24 right-6 flex flex-col gap-2 z-50">
        @if(AppHelper::perUser('appointments.create'))
        <a href="{{ route('calender') }}" class="btn btn-primary btn-sm shadow">
            <i class="bi bi-calendar-plus"></i> {{ __('Appointment') }}
        </a>
        @endif
        @if(AppHelper::perUser('sales_invoices.create'))
        <a href="{{ route('sales_invoices.create') }}" class="btn btn-success btn-sm shadow">
            <i class="bi bi-receipt"></i> {{ __('Invoice') }}
        </a>
        @endif
        @if(AppHelper::perUser('customers.create'))
        <a href="{{ route('customers.create') }}" class="btn btn-info btn-sm shadow">
            <i class="bi bi-person-plus"></i> {{ __('Customer') }}
        </a>
        @endif
    </div>
</div>
```

**Acceptance Criteria:**
- FAB only shown on screens < 768px (md breakpoint)
- Expands on tap, collapses on outside click
- Shows only actions the user has permission for

---

## PHASE 9: PERFORMANCE + CACHING

### TASK 9.1: Redis Cache Integration in DashboardController

**File:** `app/Http/Controllers/Admin/DashboardController.php`

**Effort:** M

**Description:** Add caching to each dashboard endpoint using `Cache::remember()`.

```php
// In summary():
$cacheKey = "dashboard:summary:{$branchId}:{$date}";
$ttl = 300; // 5 minutes

return Cache::remember($cacheKey, $ttl, function() use (...) {
    // ... query logic ...
});
```

**Cache keys:**
- `dashboard:summary:{branch}:{date}` — 5 min
- `dashboard:revenue:{branch}:{from}:{to}` — 10 min
- `dashboard:appointments:{branch}:{date}` — 2 min
- `dashboard:staff:{branch}:{from}:{to}` — 10 min
- `dashboard:inventory:{branch}` — 10 min
- `dashboard:expenses:{branch}:{from}:{to}` — 10 min
- `dashboard:activity:{branch}` — 2 min
- `dashboard:alerts:{branch}` — 2 min

**Cache invalidation:** Add `Cache::forget()` calls in the relevant controllers:

- `SalesInvoiceController::store()` and `activate()`: forget `dashboard:summary:*` and `dashboard:revenue:*` for the branch
- `AppointmentController::transition()`: forget `dashboard:appointments:*`
- `InventoryTransactionController::adjust()`: forget `dashboard:inventory:*`
- `ExpenseController::store()`: forget `dashboard:expenses:*`

**Implementation note:** Use `Cache::tags()` if Redis is configured (tags enable bulk invalidation). Fall back to individual key deletion if tags not available.

**Acceptance Criteria:**
- First request populates cache
- Subsequent requests return cached data within TTL
- Cache is invalidated on relevant data changes
- System works correctly with cache disabled (fallback to direct queries)
- Redis driver confirmed in `.env`

---

### TASK 9.2: Database Indexes

**File:** New migration `database/migrations/2026_09_26_000001_add_dashboard_indexes.php`

**Effort:** S

**Description:** Add the missing compound indexes identified in SPEC Section 20.

```php
Schema::table('sales_invoices', function (Blueprint $table) {
    $table->index(['invoice_date', 'status', 'branch_id'], 'idx_si_date_status_branch');
});

Schema::table('sales_invoice_details', function (Blueprint $table) {
    $table->index(['provider_id'], 'idx_sid_provider');
    $table->index(['service_id'], 'idx_sid_service');
    $table->index(['product_id'], 'idx_sid_product');
});

Schema::table('appointments', function (Blueprint $table) {
    $table->index(['start_datetime', 'status', 'branch_id'], 'idx_appt_start_status_branch');
});

Schema::table('expenses', function (Blueprint $table) {
    $table->index(['paid_at', 'status', 'branch_id'], 'idx_exp_paid_status_branch');
});
```

**Note:** Check if indexes already exist before adding to avoid migration errors.

**Acceptance Criteria:**
- Migration runs without errors
- `EXPLAIN SELECT ...` on dashboard queries shows indexes being used
- Query execution time for the summary endpoint < 200ms on 10k invoices

---

### TASK 9.3: IntersectionObserver Lazy Loading

**File:** `public/admin-assets/assets/js/dashboard.js`

**Effort:** S

**Description:** Defer loading of below-fold sections until they come into view.

```javascript
// In Alpine.js init():
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const section = entry.target.dataset.lazySection;
            if (section === 'staff' && !this.data.staff) this.fetchStaff();
            if (section === 'activity' && !this.data.activity.length) this.fetchActivity();
            observer.unobserve(entry.target);
        }
    });
}, { rootMargin: '200px' });

document.querySelectorAll('[data-lazy-section]').forEach(el => observer.observe(el));
```

**Blade markup:**
```blade
<div data-lazy-section="staff">
    <!-- Staff performance section -->
</div>
```

**Acceptance Criteria:**
- Staff performance and activity feed only fetch when user scrolls to them
- No loading flash (skeleton shown immediately, content loads after)
- Works on both desktop and mobile

---

## PHASE 10: TESTING

### TASK 10.1: Feature Tests for DashboardController

**File:** `tests/Feature/DashboardControllerTest.php`

**Effort:** L

**Tests to write (minimum 15):**

1. Guest access is rejected (401)
2. Authenticated user can access `/dashboard/summary`
3. Summary returns correct revenue for today
4. Summary returns correct appointment counts
5. Branch filter restricts data to correct branch
6. Admin can see all-branches data (no filter)
7. Cashier branch filter is auto-applied from their employee record
8. Revenue endpoint returns correct time series
9. Revenue endpoint handles empty date range (all zeros, no errors)
10. Appointments endpoint returns correct status breakdown
11. Appointments endpoint includes correct `can_*` flags per status
12. Staff endpoint returns correct ranking
13. Inventory alerts returns out-of-stock products
14. Inventory alerts returns empty when stock is healthy
15. Alerts endpoint returns unconfirmed appointment alert when pending appointments exist
16. Alerts endpoint returns draft invoice alert when old drafts exist
17. Activity endpoint returns merged, time-sorted events
18. All endpoints return JSON with `data` and `meta` keys
19. All endpoints handle `?branch_id=` filter correctly
20. Response time for summary endpoint < 2 seconds on realistic dataset

**Acceptance Criteria:**
- All 20 tests passing
- No skipped tests
- Tests use SQLite in-memory database (consistent with rest of test suite)
- Each test has a clear docblock explaining what it tests

---

### TASK 10.2: Browser Testing Checklist (Manual)

**Effort:** M

**Description:** Manual verification checklist for the dashboard.

```
Functional Testing:
[ ] Owner login -> all sections visible
[ ] Manager login -> branch locked, no branch comparison chart
[ ] Receptionist login -> appointments only, no revenue KPIs
[ ] Cashier login -> POS-focused view
[ ] Provider login -> own appointments only
[ ] Inventory login -> stock alerts only

Date Filter Testing:
[ ] "Today" filter shows today's data
[ ] "This Month" filter shows correct monthly totals
[ ] Custom range works
[ ] Filter persists on page refresh (URL params)

Branch Filter Testing (Owner only):
[ ] "All Branches" shows aggregate
[ ] Selecting specific branch filters all widgets
[ ] Branch filter is saved to URL

Loading State Testing:
[ ] Each widget shows skeleton while loading
[ ] Error state shows when network is offline (browser devtools)
[ ] Retry button refreshes only the failed widget

Empty State Testing:
[ ] Empty database -> all KPIs show 0 with appropriate empty messages
[ ] No appointments today -> appointment table shows empty state
[ ] No inventory alerts -> inventory section shows "all healthy"

Responsive Testing:
[ ] Desktop (1280px+): 4-col KPI grid, 2-col charts
[ ] Tablet (768px): 2-col KPI grid, 1-col charts
[ ] Mobile (375px): 1-col, FAB visible, below-fold sections hidden

RTL Testing (if applicable):
[ ] Set dir=rtl on html element -> layout mirrors correctly
[ ] Charts render with RTL axis direction
[ ] Number formatting correct

Performance Testing:
[ ] Initial page load: < 2 seconds (TTFB + first render)
[ ] Summary API: < 500ms
[ ] Charts render without visible delay
[ ] Filter change: < 1 second to show new data
```

---

## MIGRATION PLAN (Existing Home Page)

### Current State

The existing `resources/views/admin/home.blade.php` contains 801 lines of inline PHP and Blade. It fetches data server-side in `HomePageController.php` and renders 5 simple KPI cards + 3 charts.

### Migration Strategy

1. **Do not delete** the old home.blade.php immediately
2. Create the new dashboard in a separate file: `resources/views/admin/home-v2.blade.php`
3. Update `HomePageController::index()` to point to `home-v2.blade.php`
4. Test the new dashboard in staging
5. When approved, rename `home-v2.blade.php` to `home.blade.php` (replacing old)
6. Delete old file

This ensures the existing dashboard remains functional as a fallback during development.

---

## WHAT IS NOT INCLUDED IN THIS IMPLEMENTATION

The following are explicitly OUT OF SCOPE for the dashboard redesign:

| Item | Reason |
|---|---|
| Database migrations | Scope constraint — no schema changes |
| New package installation | Scope constraint — use existing or CDN |
| Reordering existing reports | Out of scope — dashboard reads existing data |
| Customer-facing portal | Out of scope — internal tool only |
| Push notifications | Requires WebSocket/Pusher — not in current stack |
| Real-time auto-refresh | Deferred to future iteration |
| Period-over-period comparison | Deferred to Phase 9+ |
| Low stock reorder_point column | Requires migration — use interim threshold of 5 |
| Predictive analytics | Out of scope |
| Map view of branches | No geo data in system |

---

## DEFERRED IMPROVEMENTS (Post-Launch)

| Improvement | When to Do | Notes |
|---|---|---|
| Add `reorder_point` column to products | After migration moratorium lifts | Unlocks ALERT-003 properly |
| Period comparison (vs. last week/month) | Phase 2+ | Requires storing historical snapshots or accepting slower queries |
| Real-time refresh (WebSocket) | Post-launch | Pusher/Soketi integration |
| Custom dashboard widget ordering (drag & drop) | Future | User preference stored in `admin_panel_settings` |
| Dashboard export (PDF/PNG) | Future | ApexCharts has built-in export |
| Email summary report (daily/weekly) | Future | Laravel Scheduler + Notification |
| Mobile app (PWA) | Future | Service worker + manifest.json |

---

*Document version: 1.0 | Author: Dashboard Design Analysis | Date: 2026-09-25*

