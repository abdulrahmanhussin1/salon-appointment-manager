# HOME-DASHBOARD-SPEC — Salon Appointment Manager

> **Document purpose:** Complete, production-grade specification for redesigning the Home/Dashboard page into a professional business intelligence and operational command center.
>
> **Generated:** 2026-09-25 | Based on full repository + documentation analysis
>
> **System state at time of writing:** All 20 requirements (Phase 0–3) implemented. All 5 NFRs confirmed. 131+ feature tests passing (556+ assertions).

---

## 1. Dashboard Objectives

The dashboard must serve as the **primary command center** of the application. A user opening the dashboard must immediately understand:

1. What is happening right now at the business?
2. What happened today, and how does it compare to yesterday/last week?
3. Is the business on track financially this period?
4. Which appointments need attention (pending, arriving, no-shows)?
5. Where is revenue being generated and where is it being lost?
6. Which staff are performing well vs. underperforming?
7. What inventory items require immediate attention?
8. What actions should the user take next?

The dashboard is **not** a report page. It is an operational snapshot and an entry point to deeper workflows.

---

## 2. Target Users

The dashboard serves all internal users of the system:

| Role | Primary Need | Secondary Need |
|---|---|---|
| Business Owner (Admin) | Financial KPIs, branch comparison, staff performance | Alerts, trends |
| Branch Manager | Today's branch operations, appointments, staff | Inventory alerts, expense overview |
| Receptionist | Today's appointment schedule, upcoming bookings, check-in actions | Customer search |
| Cashier | Today's sales, payment totals, open transactions | Cash balance |
| Service Provider/Stylist | Personal schedule for today, upcoming appointments | Commission earnings |
| Inventory Employee | Stock alerts, low-stock products | Pending transfers |
| Accountant/Finance | Revenue vs. expenses, commission totals | Deposit liabilities |

**Not in scope:** Customer-facing view (no customer portal exists).

---

## 3. Role-Based Dashboard Behavior

The dashboard uses a **shared architecture with role-aware section visibility**. Not every section is visible to every role.

### 3.1 Shared Context Controls (All Roles)

All roles see:
- Greeting + user name
- Current date/time
- Business/branch name
- Notification bell
- Quick actions (role-filtered)

### 3.2 Owner / Admin

**Full dashboard.** All sections visible. Global branch selector (All Branches or specific).

Sections shown:
- All KPI cards (revenue, sales, expenses, appointments, customers, staff)
- Revenue trend chart
- Appointment overview
- Branch performance comparison
- Staff performance table
- Service/product ranking
- Inventory alerts
- Expense overview
- Recent activity
- Action-required alerts

### 3.3 Branch Manager

Branch-scoped dashboard. Auto-filtered to their branch. Cannot change branch.

Sections shown:
- KPI cards (revenue, sales, expenses, appointments — branch-scoped)
- Revenue trend (branch only)
- Today's appointment board
- Staff performance (branch only)
- Inventory alerts (branch stock only)
- Expense overview (branch only)
- Recent activity (branch only)
- Action-required alerts

Sections **hidden**:
- Branch comparison chart
- Cross-branch data

### 3.4 Receptionist

Operationally focused. No financial visibility.

Sections shown:
- Today's appointment board (primary view)
- Upcoming appointments
- Pending/unconfirmed appointments
- Quick actions (new appointment, customer search)
- Customer arrivals / check-in status
- Provider availability mini-view

Sections **hidden**:
- Revenue KPIs
- Expense data
- Financial charts
- Commission data
- Inventory

### 3.5 Cashier

POS-focused. Financial totals for today but not detailed analytics.

Sections shown:
- Today's sales totals (cash + card)
- Open/pending transactions
- Today's customer count (invoice count)
- Quick actions (new invoice)
- Recent transactions list
- Draft invoice alerts

Sections **hidden**:
- Staff commission details
- Expense breakdown
- Multi-branch data
- Inventory management

### 3.6 Service Provider / Stylist

Personal schedule and performance only.

Sections shown:
- Personal appointment schedule for today
- Next appointment details (countdown / upcoming)
- Personal service count today
- Personal commission earned today (if `is_immediate_commission`)
- Quick action: mark appointment in-service / completed

Sections **hidden**:
- All financial data (revenue, expenses)
- Other staff performance
- Inventory
- Customer analytics

### 3.7 Inventory Employee

Stock-focused view.

Sections shown:
- Low-stock / out-of-stock alerts (primary)
- Recent inventory movements
- Pending transfers
- Quick actions (stock adjustment, new purchase)

Sections **hidden**:
- Revenue/financial data
- Staff performance
- Customer data

### 3.8 Accountant / Finance

Financial accuracy focus.

Sections shown:
- Revenue KPIs (all branches if admin role)
- Expense overview
- Commission totals
- Refund totals
- Outstanding customer deposits (liability)
- Financial charts (revenue trend, expense breakdown)

Sections **hidden**:
- Appointment operational board
- Individual staff schedule
- Inventory alerts (low priority)

---

## 4. Information Architecture

Dashboard sections ordered by priority and audience frequency of use:

```
1. Dashboard Header              <- Always visible (all roles)
   - Greeting, date, branch, notifications, quick actions

2. Global Filters Bar            <- Always visible (all roles)
   - Branch selector (role-aware)
   - Date range selector
   - Refresh button

3. KPI Overview Grid             <- Primary data snapshot
   - P0: Revenue / Sales
   - P0: Appointments today
   - P0: Customers today
   - P0: Expenses today
   - P1: Net profit indicator
   - P1: Top staff / top service

4. Today's Operational Status    <- Real-time operational pulse
   - Appointment status breakdown (confirmed / in-service / completed / no-show)
   - Today's appointment timeline (compact)
   - Staff on duty today

5. Revenue Trend Chart           <- Financial performance over time
   - Line chart: revenue by day/week/month
   - Revenue composition (services vs products)

6. Appointment Analytics         <- Booking performance
   - Status distribution (donut)
   - Appointment volume trend
   - Cancellation / no-show rate

7. Customer Analytics            <- CRM snapshot
   - New vs. returning customers
   - Lapsed customers alert
   - Acquisition source breakdown

8. Branch Performance            <- Multi-branch comparison (owner/admin only)
   - Revenue by branch
   - Appointment count by branch

9. Staff Performance             <- Staff productivity
   - Revenue per staff
   - Services count per staff
   - Commission earned

10. Service & Product Ranking    <- Revenue contribution
    - Top services by revenue
    - Top products by revenue

11. Inventory Alerts             <- Operational continuity
    - Low-stock products
    - Out-of-stock products

12. Expense Overview             <- Cost control
    - Today's expenses
    - Expense by category breakdown

13. Action Required / Alerts     <- Prioritized attention items
    - Pending appointments
    - No-shows
    - Draft invoices
    - Low stock

14. Recent Activity              <- Operational log
    - Latest invoices, appointments, adjustments
```

---

## 5. Header Design

The dashboard header must always communicate:
- **Who** is using the system (user name, avatar)
- **Where** they are (branch name)
- **When** they're looking at (date/period context)

### Header Layout

```
+-------------------------------------------------------------------------+
| [Logo] Salon Manager        [Branch: Cairo - Zamalek v]   [Bell 3] [User] |
| Good morning, Sara           Today, Thursday 25 Sep 2026                |
+-------------------------------------------------------------------------+
```

### Header Elements

| Element | Description | Role Visibility |
|---|---|---|
| Business Logo | App logo from AdminPanelSetting | All |
| Greeting | Time-aware (morning/afternoon/evening) + user name | All |
| Date | Current date, localized (Arabic/English) | All |
| Branch Selector | Dropdown: specific branch or "All Branches" | Owner/Admin: all branches; Others: own branch only (read-only) |
| Notification Bell | Count badge + dropdown of recent alerts | All |
| User Avatar | Profile photo or initials fallback | All |

### Branch Selector Rules

- **Owner / Admin**: Shows all active branches + "All Branches" option
- **Branch Manager**: Shows only their assigned branch (no selector -- locked)
- **Cashier**: Shows only their assigned branch (locked)
- **Service Provider**: Shows only their assigned branch (locked)
- **Receptionist**: Shows only their assigned branch (locked)
- **Inventory Employee**: Shows only their assigned branch (locked)

---

## 6. Global Filters

The following filters affect ALL dashboard widgets simultaneously:

| Filter | Options | Default |
|---|---|---|
| Branch | All Branches / specific branch | Role-determined |
| Date Range | Today / Yesterday / This Week / This Month / Last Month / Custom | Today |
| Comparison Period | Previous period / Previous year (optional) | None |

### Filter Behavior

- Changing a global filter triggers a **partial page refresh** (fetch all widgets with new parameters)
- Filter state is stored in URL query params (`?branch_id=2&period=today`) for shareability
- Filter state persists across page refreshes (localStorage fallback)

### Widget Filter Dependency Map

| Widget | Responds to Branch | Responds to Date Range |
|---|---|---|
| Revenue KPIs | Yes | Yes |
| Appointments KPIs | Yes | Yes |
| Customer KPIs | Yes | Yes |
| Expense KPIs | Yes | Yes |
| Revenue Trend Chart | Yes | Yes (sets axis range) |
| Appointment Overview | Yes | Yes |
| Branch Performance | No (shows all) | Yes |
| Staff Performance | Yes | Yes |
| Service Ranking | Yes | Yes |
| Inventory Alerts | Yes | No (always current) |
| Expense Overview | Yes | Yes |
| Recent Activity | Yes | Yes |

---

## 7. KPI Card Inventory

### KPI Card Design Standard

Every KPI card contains:
- **Metric name** (human-readable, localized)
- **Current value** (formatted: currency / integer / percentage)
- **Comparison value** (previous period -- only if data available)
- **Change indicator** (% change + direction arrow)
- **Trend direction** (positive/negative -- semantically colored)
- **Icon** (relevant, not decorative)
- **Click-through** (links to relevant report)

### Semantic Color Rules

| Metric | Higher = | Color Logic |
|---|---|---|
| Revenue | Positive | Green up, Red down |
| Expenses | Ambiguous | Neutral |
| Cancellations | Negative | Red up, Green down |
| New Customers | Positive | Green up |
| No-shows | Negative | Red up |
| Net Profit | Positive | Green up, Red down |
| Refunds | Negative | Red up (increasing refunds = problem) |

### KPI P0 -- Critical (Always Shown to Eligible Roles)

| KPI ID | Metric | Roles | Source | Availability |
|---|---|---|---|---|
| KPI-001 | Total Revenue Today | Owner, Admin, Manager, Accountant | sales_invoices.net_total | AVAILABLE NOW |
| KPI-002 | Cash Sales Today | Owner, Admin, Manager, Cashier, Accountant | sales_invoices.paid_amount_cash | AVAILABLE NOW |
| KPI-003 | Card/Other Sales Today | Owner, Admin, Manager, Cashier, Accountant | sales_invoices.payment_method_value | AVAILABLE NOW |
| KPI-004 | Total Expenses Today | Owner, Admin, Manager, Accountant | expenses.paid_amount | AVAILABLE NOW |
| KPI-005 | Net Profit Today | Owner, Admin, Accountant | KPI-001 minus KPI-004 | AVAILABLE WITH QUERY |
| KPI-006 | Appointments Today | Owner, Admin, Manager, Receptionist | appointments COUNT | AVAILABLE NOW |
| KPI-007 | New Customers Today | Owner, Admin, Manager | customers.created_at | AVAILABLE NOW |
| KPI-008 | Total Active Customers | Owner, Admin, Manager | customers.status=active | AVAILABLE NOW |

### KPI P1 -- Important

| KPI ID | Metric | Roles | Availability |
|---|---|---|---|
| KPI-009 | Confirmed Appointments | Manager, Receptionist | AVAILABLE NOW (post REQ-009) |
| KPI-010 | Completed Appointments | Manager, Owner | AVAILABLE NOW |
| KPI-011 | Cancelled Appointments | Manager, Owner | AVAILABLE NOW |
| KPI-012 | No-Show Count | Manager, Owner | AVAILABLE NOW |
| KPI-013 | Services Revenue | Owner, Manager, Accountant | AVAILABLE NOW |
| KPI-014 | Products Revenue | Owner, Manager, Accountant | AVAILABLE NOW |
| KPI-015 | Total Commissions Earned | Owner, Manager, Accountant | AVAILABLE NOW (post REQ-012) |
| KPI-016 | Total Refunds | Owner, Manager, Accountant | AVAILABLE NOW (post REQ-019) |
| KPI-017 | Average Ticket Value | Owner, Manager, Accountant | AVAILABLE NOW |
| KPI-018 | Pending/Requested Appointments | Receptionist, Manager | AVAILABLE NOW |
| KPI-019 | Low Stock Products | Manager, Inventory | AVAILABLE AFTER BACKEND CHANGE (needs reorder_point) |
| KPI-020 | Outstanding Customer Deposits | Accountant, Owner | AVAILABLE NOW (post REQ-020) |

### KPI P2 -- Useful

| KPI ID | Metric | Roles | Availability | Notes |
|---|---|---|---|---|
| KPI-021 | Appointment Conversion Rate | Owner, Manager | AVAILABLE NOW (post REQ-017) | completed/total booked |
| KPI-022 | Cancellation Rate | Owner, Manager | AVAILABLE NOW | cancelled/total |
| KPI-023 | Active Staff Today | Manager, Owner | AVAILABLE WITH QUERY | Employees with appointments today |
| KPI-024 | Top Employee Today | Owner, Manager | AVAILABLE NOW | Max service count |
| KPI-025 | Top Service Today | Owner, Manager | AVAILABLE NOW | Most sold service |

### KPI P3 -- Deferred

| KPI ID | Metric | Availability | Reason Deferred |
|---|---|---|---|
| KPI-026 | Customer Retention Rate | NOT AVAILABLE | Requires visit-history analysis |
| KPI-027 | Customer Lifetime Value | NOT AVAILABLE | Requires aggregated visit history |
| KPI-028 | Staff Utilization % | AFTER BACKEND CHANGE | Needs working hours + appointment duration |
| KPI-029 | Gross Margin | AFTER BACKEND CHANGE | Needs COGS tracking |
| KPI-030 | Payroll Liability | NOT AVAILABLE | Payroll run not implemented |

---

## 8. Chart Inventory

### CHART-001: Revenue Trend (Line)

**Purpose:** Show revenue performance over time.
**Type:** Multi-series line chart (Services / Products / Total)
**X-Axis:** Date (day/week/month based on range)
**Data Source:** sales_invoice_details JOIN sales_invoices GROUP BY date
**Availability:** AVAILABLE NOW
**Priority:** P0

---

### CHART-002: Appointment Status Distribution (Donut)

**Purpose:** Show today's appointment breakdown by status.
**Type:** Donut chart
**Segments:** Requested / Confirmed / Checked In / In Service / Completed / Cancelled / No Show
**Data Source:** SELECT status, COUNT(*) FROM appointments WHERE DATE(start_datetime)=today GROUP BY status
**Availability:** AVAILABLE NOW (post REQ-009)
**Priority:** P0

---

### CHART-003: Revenue by Branch (Bar)

**Purpose:** Compare branches. Owner/Admin only.
**Type:** Horizontal grouped bar chart
**Data Source:** sales_invoices GROUP BY branch_id
**Availability:** AVAILABLE NOW
**Priority:** P1 (Owner/Admin only)

---

### CHART-004: Top Services by Revenue (Bar)

**Purpose:** Identify highest-revenue services.
**Type:** Horizontal bar chart (top 10)
**Data Source:** sales_invoice_details JOIN services GROUP BY service_id ORDER BY SUM(subtotal) DESC LIMIT 10
**Availability:** AVAILABLE NOW
**Priority:** P1

---

### CHART-005: Top Products by Revenue (Bar)

Same pattern as CHART-004 for product_id.
**Availability:** AVAILABLE NOW
**Priority:** P2

---

### CHART-006: Staff Performance (Ranked Table/Bar)

**Purpose:** Show relative staff revenue + commission + service count.
**Type:** Ranked table or horizontal bar
**Columns:** Name / Services Count / Revenue / Commission Earned
**Data Source:** sales_invoice_details GROUP BY provider_id
**Availability:** AVAILABLE NOW (post REQ-012)
**Priority:** P1

---

### CHART-007: Payment Method Distribution (Donut)

**Purpose:** Show how customers pay.
**Type:** Donut or stacked bar
**Segments:** Cash / Named payment methods / Deposit
**Availability:** AVAILABLE NOW
**Priority:** P2

---

### CHART-008: Customer Growth (Bar)

**Purpose:** Show new customer acquisition trend.
**Type:** Bar chart (per day/week/month)
**Availability:** AVAILABLE NOW
**Priority:** P2

---

### CHART-009: Expense by Category (Donut/Bar)

**Purpose:** Identify where money is spent.
**Data Source:** expenses JOIN expense_types GROUP BY expense_type_id
**Availability:** AVAILABLE NOW
**Priority:** P1

---

### CHART-010: Monthly Revenue Summary (Year View Bar)

**Purpose:** Full-year revenue trend for the owner.
**Type:** 12-month bar chart
**Data Source:** Reuses existing monthlySummary() in ReportController
**Availability:** AVAILABLE NOW
**Priority:** P2

---

## 9. Alert Inventory

### Alert Design Standard

Each alert contains:
- **Severity**: critical / warning / info
- **Icon** relevant to domain
- **Title**: Short, action-oriented
- **Body**: Entity name + context
- **Action button**: Deep link to resolution

### ALERT-001: Pending/Unconfirmed Appointments

**Trigger:** appointments.status='requested' AND future date
**Severity:** Warning
**Example:** 3 appointments are unconfirmed -- Ahmed (10:00), Sara (2:30)
**Availability:** AVAILABLE NOW

---

### ALERT-002: No-Shows Today

**Trigger:** appointments.status='no_show' AND DATE(start_datetime)=today
**Severity:** Info
**Availability:** AVAILABLE NOW

---

### ALERT-003: Low Stock Products

**Trigger:** inventory_products.quantity <= products.reorder_point
**Severity:** Warning
**Interim fallback:** quantity <= 5 (configurable)
**Availability:** AFTER BACKEND CHANGE (reorder_point column needed)

---

### ALERT-004: Out-of-Stock Products

**Trigger:** inventory_products.quantity = 0
**Severity:** Critical
**Availability:** AVAILABLE NOW

---

### ALERT-005: Old Draft Invoices

**Trigger:** sales_invoices.status='draft' AND created_at < NOW() - 4 hours
**Severity:** Warning
**Availability:** AVAILABLE NOW

---

### ALERT-006: High Cancellation Rate

**Trigger:** Today cancellation rate > 20% (configurable)
**Severity:** Warning
**Availability:** AVAILABLE NOW

---

### ALERT-007: Outstanding Balance Invoices

**Trigger:** sales_invoices.balance_due > 0 AND status='active'
**Severity:** Info
**Availability:** AVAILABLE NOW

---

### ALERT-008: Large Expense Day

**Trigger:** Today expenses > configurable threshold (e.g. EGP 5000)
**Severity:** Info
**Availability:** AVAILABLE NOW

---

## 10. Quick Actions

| Action | Label | Roles | Route | Priority |
|---|---|---|---|---|
| QA-001 | New Appointment | Owner, Admin, Manager, Receptionist | /admin/calender | P0 |
| QA-002 | New Invoice / POS | Owner, Admin, Manager, Cashier | /admin/sales_invoices/create | P0 |
| QA-003 | New Customer | Owner, Admin, Manager, Receptionist, Cashier | /admin/customers/create | P0 |
| QA-004 | Add Expense | Owner, Admin, Manager, Accountant | /admin/expenses/create | P1 |
| QA-005 | Stock Adjustment | Owner, Admin, Manager, Inventory | /admin/inventory_transactions/adjust | P1 |
| QA-006 | New Purchase | Owner, Admin, Manager, Inventory | /admin/purchase_invoices/create | P2 |
| QA-007 | View Reports | Owner, Admin, Accountant | /admin/reports/daily_revenues | P2 |

Rules:
- Maximum 4-5 actions visible at once
- All filtered by AppHelper::perUser() permission checks
- On mobile: collapsed into FAB (+) button

---

## 11. Today's Appointments Table

| Column | Source | Notes |
|---|---|---|
| Time | start_datetime | Formatted |
| Customer | customers.name | Clickable to profile |
| Service | services.name | |
| Provider | employees.name | |
| Status | appointments.status | Colored badge |
| Duration | services.duration | in minutes |
| Actions | -- | Confirm / Check In / Complete / No-Show / Cancel |

**Available:** AVAILABLE NOW (post REQ-009)

---

## 12. Recent Activity Feed

A scrollable, time-ordered list of the 15 most recent system events.

| Event | Description | Roles |
|---|---|---|
| Invoice created | Invoice #1045 -- EGP 450 -- Sara Khalil | All financial roles |
| Invoice voided | Invoice #1044 voided by Ahmed Hassan | Admin, Owner, Accountant |
| Refund issued | Refund #R-012 -- EGP 150 | Admin, Owner, Accountant |
| Appointment created | Leila Omar booked for 2:00 PM | Manager, Receptionist |
| Appointment completed | Sara -- Haircut completed by Ahmed | Manager |
| Appointment cancelled | 3:30 PM appointment cancelled | Manager, Receptionist |
| Stock adjustment | Hair Color 6.0 adjusted -5 units | Manager, Inventory |
| Customer registered | New customer: Nour Ibrahim | Manager, Receptionist |
| Expense added | Rent EGP 3,500 added | Admin, Owner, Accountant |

---

## 13. Drill-Down Behavior

| Widget | Drill-Down Destination |
|---|---|
| Revenue KPI | Daily Revenues Report (/admin/reports/daily_revenues) |
| Appointments KPI | Calendar (/admin/calender) |
| Customers KPI | Customer List (/admin/customers) |
| Expenses KPI | Expense List (/admin/expenses) |
| Staff Performance Table | Employee Report (/admin/reports/employee_summary) |
| Service Revenue Chart | Daily Summary Report |
| Inventory Alert row | Inventory or Adjustment form |
| Draft invoice alert | Invoice detail page |

---

## 14. Responsive Behavior

### Desktop (>= 1280px)
- KPI cards: 4 per row
- Charts: 2 per row
- All sections visible

### Laptop (1024px-1279px)
- KPI cards: 4 per row (smaller)
- Charts: 2 per row

### Tablet (768px-1023px)
- KPI cards: 2 per row
- Charts: stacked (1 per row)
- Appointments: simplified list

### Mobile (< 768px)
Priority of visible sections:
1. Quick Actions (FAB)
2. Today's appointment list
3. Revenue/expense KPIs (top 4 only)
4. Appointment status breakdown (donut)
5. Alerts (scrollable)

Hidden on mobile:
- Branch comparison chart
- Full staff performance table
- Monthly revenue chart
- Expense category chart

---

## 15. RTL / Arabic Support

| Requirement | Implementation |
|---|---|
| Layout direction | dir="rtl" on html element, toggleable per locale |
| Text alignment | All text right-aligned in RTL mode |
| Icon direction | Directional icons flip via CSS transform: scaleX(-1) |
| Charts | ApexCharts RTL: true config option |
| Numbers | Arabic-Indic numerals optional, respect user locale |
| Currency | EGP in English, Arabic symbol in Arabic mode |
| Dates | Gregorian standard; right-to-left date order in RTL |
| Tables | Column order reverses for RTL reading |

---

## 16. Loading / Empty / Error States

### Loading
- KPI Cards: skeleton loaders (grey animated bars)
- Charts: container with centered spinner
- Tables: 5 skeleton rows
- Alerts: 3 skeleton rows

### Empty States

| Widget | Empty State Message |
|---|---|
| Revenue KPI | No sales recorded for this period |
| Appointments (today) | No appointments scheduled for today |
| Staff Performance Table | No services recorded for this period |
| Inventory Alerts | All stock levels are healthy |
| Action Required | Nothing requires your attention right now |

### Error States

| Scenario | Display |
|---|---|
| Network error | Could not load data. [Retry] |
| Server error (500) | Something went wrong. Please refresh. |
| Auth error (401) | Redirect to login |
| Permission denied (403) | Widget silently hidden (graceful degradation) |

**Critical rule:** A single widget failure must NOT break other widgets. Each widget fetches independently.

---

## 17. Permission Gating

| Section | Required Permission |
|---|---|
| Revenue KPIs | reports.index |
| Financial charts | reports.index |
| Staff performance (all staff) | reports.index OR employees.index |
| Inventory alerts | inventories.index |
| Expense overview | expenses.index |
| Quick actions | Per-action permission (appointments.create, etc.) |

Apply @if(AppHelper::perUser('permission.name')) checks in Blade at section level.
Silently hide sections the user lacks permission for -- no error shown.

---

## 18. Backend Metric Requirements

### Revenue Today

```sql
SELECT
  SUM(net_total) as total_revenue,
  SUM(paid_amount_cash) as cash_revenue,
  SUM(payment_method_value) as card_revenue,
  COUNT(DISTINCT id) as invoice_count,
  COUNT(DISTINCT customer_id) as customer_count,
  AVG(net_total) as avg_ticket
FROM sales_invoices
WHERE status = 'active'
  AND invoice_date = :today
  AND branch_id = :branch
```

### Appointments Today by Status

```sql
SELECT status, COUNT(*) as count
FROM appointments
WHERE DATE(start_datetime) = :today
  AND branch_id = :branch
GROUP BY status
```

### Staff Revenue Today

```sql
SELECT
  e.name,
  COUNT(sid.id) as service_count,
  SUM(sid.subtotal) as revenue,
  SUM(sid.commission_amount) as commission
FROM sales_invoice_details sid
JOIN employees e ON e.id = sid.provider_id
JOIN sales_invoices si ON si.id = sid.sales_invoice_id
WHERE si.status = 'active'
  AND si.invoice_date = :today
  AND si.branch_id = :branch
  AND sid.service_id IS NOT NULL
GROUP BY e.id, e.name
ORDER BY revenue DESC
```

---

## 19. Dashboard API Endpoints

All endpoints require authentication and respect branch filtering via HasBranchFilter trait.

```
GET /dashboard/summary
    Returns: Revenue KPIs, appointment counts, customer counts
    Cache: 5 min per branch+date

GET /dashboard/revenue
    Returns: Revenue trend data (line chart series)
    Params: ?branch_id=&from=&to=
    Cache: 10 min

GET /dashboard/appointments
    Returns: Status breakdown (donut) + today's appointment list
    Params: ?branch_id=&date=

GET /dashboard/staff
    Returns: Staff performance table data
    Params: ?branch_id=&from=&to=
    Cache: 10 min

GET /dashboard/inventory-alerts
    Returns: Low stock + out-of-stock products
    Params: ?branch_id=
    Cache: 5 min

GET /dashboard/expenses
    Returns: Expense overview + category breakdown
    Params: ?branch_id=&from=&to=

GET /dashboard/activity
    Returns: Recent activity feed
    Params: ?branch_id=&limit=15

GET /dashboard/alerts
    Returns: Action-required items
    Params: ?branch_id=
```

### Response Format Standard

```json
{
  "data": { ... },
  "meta": {
    "branch_id": 2,
    "period": "today",
    "from": "2026-09-25",
    "to": "2026-09-25",
    "generated_at": "2026-09-25T10:00:00Z"
  }
}
```

---

## 20. Required Database Indexes

| Table | Columns | Purpose |
|---|---|---|
| sales_invoices | (invoice_date, status, branch_id) | Daily revenue queries |
| sales_invoice_details | (provider_id) | Staff revenue grouping |
| sales_invoice_details | (service_id) | Service ranking |
| sales_invoice_details | (product_id) | Product ranking |
| appointments | (start_datetime, status, branch_id) | Today's appointments |
| expenses | (paid_at, status, branch_id) | Expense aggregation |
| customer_transactions | (customer_id, status) | Deposit balance queries |
| inventory_products | (inventory_id, product_id) | Stock queries |

---

## 21. Caching Strategy

| Data | TTL | Redis Key Pattern |
|---|---|---|
| Revenue KPIs (today) | 5 minutes | dashboard:revenue:{branch}:{date} |
| Appointment status counts | 2 minutes | dashboard:appointments:{branch}:{date} |
| Staff ranking | 10 minutes | dashboard:staff:{branch}:{date} |
| Inventory alerts | 10 minutes | dashboard:inventory:{branch} |
| Monthly summary | 60 minutes | dashboard:monthly:{branch}:{year} |

Cache invalidation triggers:
- New invoice created
- Appointment status changed
- Inventory adjusted
- Expense added

---

## 22. Frontend Component Architecture

### Component Tree

```
DashboardPage
  DashboardHeader
    BusinessLogo
    GreetingText
    BranchSelector
    NotificationBell
    UserMenu

  FilterBar
    DateRangePicker
    BranchFilter (role-aware)
    RefreshButton

  QuickActions
    QuickActionButton[] (role-filtered)

  KpiGrid
    KpiCard[] (role-filtered)
      KpiIcon / KpiLabel / KpiValue / KpiChange / KpiSubtext

  TodayOperationalPanel
    AppointmentStatusBreakdown (donut chart)
    AppointmentListTable
      AppointmentRow[]

  RevenueSection
    RevenueTrendChart (line)
    RevenueCompositionChart

  BranchPerformanceSection (admin/owner only)
    BranchComparisonChart (bar)

  StaffPerformanceSection
    StaffRankingTable

  ServiceProductSection
    TopServicesChart (bar)
    TopProductsChart (bar)

  InventoryAlertsSection
    AlertCard[]

  ExpenseSection
    ExpenseKpi
    ExpenseCategoryChart (donut)

  ActionRequiredSection
    AlertCard[]

  RecentActivitySection
    ActivityFeedItem[]
```

### Technology Stack

- **Alpine.js** for reactive state (consistent with existing codebase)
- **Axios** for AJAX calls (already installed)
- **ApexCharts** for visualizations (recommended for business dashboards)
- **Tailwind CSS** for styling (already used)
- **Blade components** (x-dashboard-kpi-card, x-dashboard-chart, etc.)

---

## 23. Performance Strategy

### Loading Order (Priority-Based)

| Order | Section | Endpoint |
|---|---|---|
| 1 | KPI Summary | /dashboard/summary |
| 2 | Today's Appointments | /dashboard/appointments |
| 3 | Revenue Trend | /dashboard/revenue |
| 4 | Alerts | /dashboard/alerts |
| 5 | Inventory Alerts | /dashboard/inventory-alerts |
| 6 | Staff Performance | /dashboard/staff |
| 7 | Expense Overview | /dashboard/expenses |
| 8 | Recent Activity | /dashboard/activity |

### Approach

1. Page renders immediately with skeleton loaders for all widgets
2. Parallel AJAX calls fetch each widget independently
3. Critical path (KPI summary) loads first
4. Lazy sections load on scroll visibility (IntersectionObserver)

### PHP Optimization

- Eager loading on all joins (no N+1 queries)
- DB::raw() aggregations instead of PHP collection processing
- Result set limits (top 10 staff, top 10 services)

---

## 24. Accessibility Requirements

| Requirement | Implementation |
|---|---|
| Color not sole indicator | Status badges include text label, not just color |
| Keyboard navigable | All interactive elements reachable via Tab |
| Screen reader compatible | Charts have aria-label and data table fallbacks |
| Focus indicators | Visible focus rings on all interactive elements |
| Contrast ratio | Minimum 4.5:1 for text, 3:1 for UI components (WCAG AA) |
| Loading announcements | aria-live="polite" on loading regions |
| Language attribute | html lang="ar" or lang="en" based on locale |

---

## 25. Analytics Event Tracking

| Event | Trigger | Data |
|---|---|---|
| dashboard_viewed | Page load | role, branch_id, period |
| kpi_clicked | KPI card clicked | kpi_name, value |
| quick_action_clicked | Quick action button | action_name |
| filter_changed | Date/branch filter changed | filter_type, new_value |
| alert_clicked | Alert action button | alert_type, entity_id |
| chart_interacted | Chart hover/click | chart_name |
| drill_down_clicked | Report drill-down link | section, destination |

---

## 26. Implementation Phases

See HOME-DASHBOARD-IMPLEMENTATION.md for detailed task breakdown.

| Phase | Focus |
|---|---|
| 1 | Backend dashboard API endpoints (DashboardController) |
| 2 | Frontend shell + layout + Blade structure |
| 3 | KPI cards (connected to API) |
| 4 | Charts (ApexCharts integration) |
| 5 | Alerts + Activity Feed |
| 6 | Role-aware visibility |
| 7 | Responsive + RTL |
| 8 | Performance (caching) |
| 9 | Testing |

---

## 27. What Should NOT Be on the Dashboard

The following were deliberately excluded:

| Excluded Item | Reason |
|---|---|
| Full invoice list | Belongs in Sales Invoice report |
| All customers list | Belongs in Customer Management |
| Commission per invoice line | Too granular; belongs in commission report |
| Detailed expense list | Belongs in Expense report |
| Attendance records | Not implemented; no data |
| Payroll totals | Not implemented; schema only |
| Online booking stats | Customer portal not implemented |
| Loyalty point balances | Loyalty not implemented |
| Supplier account balances | Belongs in dedicated supplier report |
| Full stock movement history | Belongs in Store Balance Report |
| Per-customer visit history | Belongs in Customer Profile |
| Invoice line-item breakdown | Too granular for dashboard |
| Draft invoices full list | Alert count is enough; full list in invoices page |
| System user activity log | Admin/audit tool, not operational dashboard |
| Tips tracking | Not implemented |
| COGS per service/product | Requires COGS tracking; not yet implemented |
| Predictive revenue forecasting | Requires ML model; out of scope |
| Map/geolocation of branches | No geo data in system |
| Period-over-period % change | Useful but deferred to later phase |

**Core principle:** The dashboard shows the most important operational snapshot for a business day. Anything requiring deliberate analysis belongs in a dedicated report page.

---

*Document version: 1.0 | Author: Dashboard Design Analysis | Date: 2026-09-25*

