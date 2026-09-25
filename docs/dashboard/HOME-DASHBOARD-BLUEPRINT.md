# HOME-DASHBOARD-BLUEPRINT — Salon Appointment Manager

> **Document purpose:** Visual wireframe-level blueprint of the dashboard layout — every zone, panel, and component precisely positioned, with data bindings, visibility rules, and interaction specifications.
>
> **Companion to:** HOME-DASHBOARD-SPEC.md (why each element exists)
> **Feeds into:** HOME-DASHBOARD-IMPLEMENTATION.md (how to build each element)
>
> **Generated:** 2026-09-25

---

## BLUEPRINT LEGEND

```
[KPI]     = KPI Card widget
[CHART]   = Chart widget
[TABLE]   = Data table
[ALERT]   = Alert/notification card
[ACTION]  = Quick action button
[FEED]    = Activity feed item
[BTN]     = Interactive button
[BADGE]   = Status badge
=====     = Section divider
~~~~~     = Conditional section (role-gated)
(i)       = Information tooltip
```

---

## A. FULL DESKTOP LAYOUT — OWNER / ADMIN VIEW

This is the maximum density layout. All other roles are subsets of this view.

```
+=============================================================================================================+
|  ZONE 1: APPLICATION HEADER (always visible)                                                              |
|  [Logo]  Salon Manager                   [Branch: All Branches v]    [Refresh]   [Bell 3]  [Sara H. v]   |
|  Good morning, Sara ☀️                   Thursday, 25 September 2026                                      |
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 2: GLOBAL FILTER BAR (always visible)                                                               |
|  Period:  [Today] [Yesterday] [This Week] [This Month] [Last Month] [Custom Range v]                      |
|  Branch:  [All Branches v]       (visible only for Owner/Admin)                                           |
|  Compare: [No Comparison v]      (optional toggle for period comparison)                                  |
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 3: QUICK ACTIONS (role-filtered, max 5 visible)                                                     |
|  [+ New Appointment]  [+ New Invoice]  [+ New Customer]  [Add Expense]  [Stock Adjustment]               |
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 4: KPI OVERVIEW GRID (P0 cards always shown, P1 below on scroll or expandable)                      |
|  +--------------------+  +--------------------+  +--------------------+  +--------------------+           |
|  | [KPI-001]          |  | [KPI-002]          |  | [KPI-003]          |  | [KPI-004]          |           |
|  | Total Revenue      |  | Cash Sales         |  | Card / Non-Cash    |  | Expenses           |           |
|  | EGP 12,450.00      |  | EGP 7,200.00       |  | EGP 5,250.00       |  | EGP 1,800.00       |           |
|  | [arrow up] +8.3%   |  | [arrow up] +5.1%   |  | [arrow up] +12.4%  |  | [arrow down] -3.2% |           |
|  | vs. yesterday      |  | vs. yesterday      |  | vs. yesterday      |  | vs. yesterday      |           |
|  | [-> Reports]       |  | [-> Reports]       |  | [-> Reports]       |  | [-> Expenses]      |           |
|  +--------------------+  +--------------------+  +--------------------+  +--------------------+           |
|                                                                                                            |
|  +--------------------+  +--------------------+  +--------------------+  +--------------------+           |
|  | [KPI-005]          |  | [KPI-006]          |  | [KPI-007]          |  | [KPI-017]          |           |
|  | Net Profit         |  | Appointments Today |  | New Customers      |  | Avg Ticket         |           |
|  | EGP 10,650.00      |  | 24 total           |  | 8                  |  | EGP 312.50         |           |
|  | [arrow up] +9.1%   |  | [BADGE: 5 pending] |  | [arrow up] +33%    |  | [arrow up] +4.2%   |           |
|  | vs. yesterday      |  | [-> Calendar]      |  | vs. yesterday      |  | vs. yesterday      |           |
|  +--------------------+  +--------------------+  +--------------------+  +--------------------+           |
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 5: TODAY'S OPERATIONAL STATUS (2-column layout)                                                     |
|                                                                                                            |
|  +--------------------------------------+  +-------------------------------------------------------+      |
|  | APPOINTMENT STATUS BREAKDOWN         |  | TODAY'S APPOINTMENT SCHEDULE                          |      |
|  | [CHART-002: Donut]                   |  | [TABLE: Sortable, filterable by status]               |      |
|  |                                      |  |                                                       |      |
|  |   Confirmed   8 ■■ (teal)            |  | Time  | Customer | Service   | Provider  | Status     |      |
|  |   Completed  12 ■■■ (green)          |  | 09:00 | Sara K.  | Haircut   | Ahmed H.  | [completed]|      |
|  |   In Service  2 ■  (amber)           |  | 10:30 | Leila M. | Color     | Sara A.   | [in-svc]   |      |
|  |   Requested   3 ■  (blue)            |  | 11:00 | Nour I.  | Massage   | Yusuf M.  | [confirmed]|      |
|  |   Cancelled   2 ■  (red)             |  | 12:30 | Ahmed R. | Facial    | Sara A.   | [confirmed]|      |
|  |   No Show     1 ■  (dark red)        |  | 14:00 | Mona G.  | Manicure  | Hana K.   | [pending]  |      |
|  |                                      |  | ...                            [Show all 24 ->]       |      |
|  |   Total: 28 appointments             |  |  [Confirm] [Check In] [Complete] [No Show] [Cancel]   |      |
|  |   Conversion: 50%  (i)              |  |                                                       |      |
|  +--------------------------------------+  +-------------------------------------------------------+      |
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 6: REVENUE TREND (full width chart)                                                                  |
|                                                                                                            |
|  Revenue Trend — Last 30 Days                             [By Day v] [Services | Products | Total]        |
|  +--------------------------------------------------------------------------------------------------------+|
|  | [CHART-001: Multi-series line chart]                                                                   ||
|  |                                                                                                        ||
|  |  EGP                                                                                                   ||
|  |  15,000 |               ...                                                                            ||
|  |  12,000 |          ...     ...    Total Revenue                                                        ||
|  |   9,000 |       ...          ...   ----  Services                                                      ||
|  |   6,000 |    ...               ... ....  Products                                                      ||
|  |   3,000 | ...                                                                                          ||
|  |       0 +--Sep 1----Sep 8----Sep 15----Sep 22----Sep 25                                                ||
|  |                                                                                                        ||
|  |  Today: EGP 12,450   7-day avg: EGP 10,820   30-day avg: EGP 9,940                                   ||
|  +--------------------------------------------------------------------------------------------------------+|
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 7: TWO-COLUMN — BRANCH PERFORMANCE + EXPENSE BREAKDOWN                                              |
|  (Branch performance: Owner/Admin only)                                                                    |
|                                                                                                            |
|  +----------------------------------------------+  +---------------------------------------------------+ |
|  | REVENUE BY BRANCH [Owner/Admin only]          |  | EXPENSE BREAKDOWN                                 | |
|  | [CHART-003: Horizontal bar]                   |  | [CHART-009: Donut + legend]                       | |
|  |                                               |  |                                                   | |
|  | Cairo - Zamalek  ████████████████ EGP 7,200   |  |   Supplies       ■■■■■■ 42% EGP 756              | |
|  | Cairo - Maadi    ██████████ EGP 3,850          |  |   Utilities      ■■■■   28% EGP 504              | |
|  | Alexandria       ██████ EGP 1,400              |  |   Rent           ■■■    19% EGP 342              | |
|  |                                               |  |   Other          ■■      11% EGP 198              | |
|  | [View Full Branch Report ->]                  |  |                                                   | |
|  |                                               |  |   Total: EGP 1,800      [View All Expenses ->]   | |
|  +----------------------------------------------+  +---------------------------------------------------+ |
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 8: STAFF PERFORMANCE (full width)                                                                    |
|                                                                                                            |
|  Top Staff Today — Thursday 25 Sep 2026                              [View Full Employee Report ->]        |
|  +--------------------------------------------------------------------------------------------------------+|
|  | [TABLE: Staff Performance]                                                                             ||
|  |                                                                                                        ||
|  |  Rank | Employee      | Services | Revenue       | Commission    | Appts | Branch          | Trend    |||
|  |  #1   | Ahmed Hassan  | 8        | EGP 3,200     | EGP 320       | 5     | Zamalek         | [up]     |||
|  |  #2   | Sara Ahmed    | 6        | EGP 2,750     | EGP 275       | 4     | Zamalek         | [up]     |||
|  |  #3   | Yusuf Mohamed | 5        | EGP 1,900     | EGP 190       | 3     | Maadi           | [same]   |||
|  |  #4   | Hana Khalil   | 4        | EGP 1,600     | EGP 160       | 3     | Zamalek         | [down]   |||
|  |  #5   | Nour Ibrahim  | 3        | EGP 1,200     | EGP 120       | 2     | Alexandria      | [up]     |||
|  |                                                              [Show All Staff ->]                       |||
|  +--------------------------------------------------------------------------------------------------------+|
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 9: SERVICE AND PRODUCT RANKING (2-column)                                                            |
|                                                                                                            |
|  +----------------------------------------------+  +---------------------------------------------------+ |
|  | TOP SERVICES TODAY [CHART-004: Bar]           |  | TOP PRODUCTS TODAY [CHART-005: Bar]               | |
|  |                                               |  |                                                   | |
|  | Haircut       ██████████████ EGP 2,800 (18x)  |  | Moroccan Argan Oil  ████████ EGP 1,200 (12x)     | |
|  | Hair Color    ████████████ EGP 2,400 (8x)     |  | Keratin Treatment  ██████   EGP 900 (6x)         | |
|  | Facial        ██████████ EGP 2,000 (10x)      |  | Anti-Aging Serum   █████    EGP 750 (5x)         | |
|  | Manicure      ████████ EGP 1,600 (12x)        |  | Hair Mask          ████     EGP 600 (8x)         | |
|  | Massage       ██████ EGP 1,200 (4x)           |  | Nail Polish Set    ████     EGP 480 (6x)         | |
|  |                    [View Service Report ->]   |  |                      [View Product Report ->]     | |
|  +----------------------------------------------+  +---------------------------------------------------+ |
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 10: INVENTORY ALERTS + ACTION REQUIRED (2-column)                                                   |
|                                                                                                            |
|  +----------------------------------------------+  +---------------------------------------------------+ |
|  | INVENTORY ALERTS                              |  | ACTION REQUIRED                                   | |
|  |                                               |  |                                                   | |
|  | [ALERT-004] CRITICAL                          |  | [ALERT-001] WARNING                               | |
|  | Moroccan Argan Oil OUT OF STOCK               |  | 3 appointments are unconfirmed                    | |
|  | Cairo - Zamalek                               |  | Ahmed (10:00), Sara (2:30), Leila (4:00)          | |
|  | [Purchase Now]  [Transfer Stock]              |  | [Go to Appointments]                              | |
|  |                                               |  |                                                   | |
|  | [ALERT-003] WARNING                           |  | [ALERT-005] WARNING                               | |
|  | Hair Color 6.0 - 3 units remaining            |  | Invoice #1042 draft for 5+ hours                  | |
|  | (reorder point: 10)                           |  | Customer: Nour Ibrahim, EGP 850                   | |
|  | [Adjust Stock]  [Purchase Now]                |  | [Activate Invoice]  [Discard Draft]               | |
|  |                                               |  |                                                   | |
|  | [ALERT-003] WARNING                           |  | [ALERT-007] INFO                                  | |
|  | Anti-Aging Serum - 2 units remaining          |  | 3 invoices have outstanding balances              | |
|  | [Adjust Stock]  [Purchase Now]                |  | Total: EGP 750 unpaid                             | |
|  |                                               |  | [View Invoices]                                   | |
|  | [View All Inventory ->]                       |  |                                                   | |
|  +----------------------------------------------+  +---------------------------------------------------+ |
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 11: RECENT ACTIVITY (full width)                                                                     |
|                                                                                                            |
|  Recent Activity                                                        [View All ->]                      |
|  +--------------------------------------------------------------------------------------------------------+|
|  |  [invoice icon]   Invoice #1045 created — EGP 450 — Sara Khalil — Zamalek      5 min ago              ||
|  |  [check icon]     Appointment completed — Haircut — Ahmed Hassan               12 min ago             ||
|  |  [person icon]    New customer: Nour Ibrahim registered                         18 min ago             ||
|  |  [box icon]       Stock adjusted: Hair Color 6.0 -5 units (waste) — Yusuf      22 min ago             ||
|  |  [invoice icon]   Invoice #1044 created — EGP 320 — Leila Mohamed — Maadi      35 min ago             ||
|  |  [cancel icon]    Appointment cancelled: 3:30 PM — Sara Ahmed                  42 min ago             ||
|  |  [expense icon]   Expense added: Supplies EGP 450 — Cairo Maadi                1 hr ago               ||
|  |  [refund icon]    Refund #R-014 issued — EGP 150 — product return              1.5 hr ago             ||
|  +--------------------------------------------------------------------------------------------------------+|
+=============================================================================================================+
```

---

## B. ROLE-SPECIFIC LAYOUT VARIANTS

### B.1 Receptionist View

```
+=============================================================================================================+
|  ZONE 1: HEADER (branch locked to own branch)                                                             |
|  [Logo]  Salon Manager                   [Branch: Cairo - Zamalek]    [Bell 2]  [Dina R. v]              |
|  Good morning, Dina                      Thursday, 25 September 2026                                     |
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 2: QUICK ACTIONS (appointment-focused)                                                              |
|  [+ New Appointment]  [+ New Customer]  [Search Customer]                                                 |
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 3: KPI STRIP (operational, no financial data)                                                       |
|  +--------------------+  +--------------------+  +--------------------+  +--------------------+           |
|  | Appointments Today |  | Confirmed          |  | Pending Confirm    |  | No Shows           |           |
|  | 24                 |  | 8                  |  | 3                  |  | 1                  |           |
|  | [-> Calendar]      |  | [-> Calendar]      |  | [Confirm Now]      |  | [View]             |           |
|  +--------------------+  +--------------------+  +--------------------+  +--------------------+           |
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 4: APPOINTMENT SCHEDULE (FULL WIDTH — primary view for receptionist)                                |
|                                                                                                            |
|  Today's Appointments — Thursday 25 Sep 2026         Filter: [All] [Pending] [Confirmed] [In Service]    |
|  +--------------------------------------------------------------------------------------------------------+|
|  | Time  | Customer      | Service      | Provider    | Status         | Duration | Actions              |||
|  | 09:00 | Sara Khalil   | Haircut      | Ahmed H.    | [COMPLETED]    | 30 min   | --                   |||
|  | 10:30 | Leila Mohamed | Hair Color   | Sara A.     | [IN SERVICE]   | 90 min   | [Complete] [No Show] |||
|  | 11:00 | Nour Ibrahim  | Massage      | Yusuf M.    | [CONFIRMED]    | 60 min   | [Check In] [Cancel]  |||
|  | 12:30 | Ahmed Rashed  | Facial       | Sara A.     | [CONFIRMED]    | 45 min   | [Check In] [Cancel]  |||
|  | 14:00 | Mona Gamal    | Manicure     | Hana K.     | [REQUESTED]    | 45 min   | [Confirm] [Reject]   |||
|  | 15:30 | Yasser Ali    | Haircut      | Ahmed H.    | [REQUESTED]    | 30 min   | [Confirm] [Reject]   |||
|  | 16:00 | Rania Samir   | Massage      | Yusuf M.    | [CONFIRMED]    | 60 min   | [Check In] [Cancel]  |||
|  | 17:00 | Khaled Omar   | Hair Color   | Sara A.     | [CONFIRMED]    | 90 min   | [Check In] [Cancel]  |||
|  |                                                                     [Load More]  [View Calendar ->]    |||
|  +--------------------------------------------------------------------------------------------------------+|
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 5: UPCOMING APPOINTMENTS (tomorrow preview)                                                         |
|                                                                                                            |
|  Tomorrow's Appointments — Friday 26 Sep 2026 (8 appointments)                    [View Calendar ->]     |
|  [Timeline: 09:00 Ahmed H. | 10:30 Sara A. | 12:00 (empty) | 14:00 Yusuf M. | 16:00 Hana K.]           |
+=============================================================================================================+
```

---

### B.2 Cashier View

```
+=============================================================================================================+
|  ZONE 1: HEADER                                                                                           |
|  [Logo]  Salon Manager           [Branch: Cairo - Zamalek]    [Bell]   [Karim S. v]                      |
|  Good afternoon, Karim           Thursday, 25 September 2026                                             |
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 2: QUICK ACTIONS                                                                                    |
|  [+ New Invoice / POS]  [+ New Customer]                                                                  |
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 3: KPI STRIP (payment/sales focused)                                                                |
|  +--------------------+  +--------------------+  +--------------------+  +--------------------+           |
|  | Cash Collected     |  | Card / Non-Cash    |  | Invoices Today     |  | Draft Invoices     |           |
|  | EGP 3,200.00       |  | EGP 1,800.00       |  | 12                 |  | 1 [ALERT]          |           |
|  | (my shift)         |  | (my shift)         |  | [View ->]          |  | [Activate] [Drop]  |           |
|  +--------------------+  +--------------------+  +--------------------+  +--------------------+           |
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 4: RECENT TRANSACTIONS (my shift)                                                                   |
|                                                                                                            |
|  Recent Invoices — My Shift Today                                   [+ New Invoice]                       |
|  +--------------------------------------------------------------------------------------------------------+|
|  |  Invoice | Customer      | Total     | Method   | Status   | Time     | Action                        |||
|  |  #1045   | Sara Khalil   | EGP 450   | Cash     | ACTIVE   | 09:15    | [View] [Refund]               |||
|  |  #1044   | Leila Mohamed | EGP 890   | Card     | ACTIVE   | 10:45    | [View] [Refund]               |||
|  |  #1042   | Nour Ibrahim  | EGP 850   | --       | DRAFT    | 06:30    | [Activate] [Discard]          |||
|  |  #1041   | Ahmed Rashed  | EGP 320   | Cash     | ACTIVE   | 08:50    | [View] [Refund]               |||
|  +--------------------------------------------------------------------------------------------------------+|
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 5: OUTSTANDING BALANCES (invoices where balance_due > 0)                                            |
|  2 invoices have outstanding balances totaling EGP 450                                                    |
|  [Invoice #1038 — Yasser Ali — EGP 250 outstanding] [Invoice #1035 — Rania Samir — EGP 200 outstanding] |
+=============================================================================================================+
```

---

### B.3 Service Provider / Stylist View

```
+=============================================================================================================+
|  ZONE 1: HEADER                                                                                           |
|  [Logo]  Salon Manager           [Branch: Cairo - Zamalek]    [Bell]   [Ahmed H. v]                      |
|  Good morning, Ahmed             Thursday, 25 September 2026                                             |
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 2: MY PERSONAL KPIs                                                                                 |
|  +--------------------+  +--------------------+  +--------------------+  +--------------------+           |
|  | My Services Today  |  | My Appointments    |  | My Revenue Today   |  | My Commission      |           |
|  | 8 services         |  | 5 appointments     |  | EGP 3,200          |  | EGP 320            |           |
|  |                    |  | (3 remaining)      |  | (my services)      |  | (immediate)        |           |
|  +--------------------+  +--------------------+  +--------------------+  +--------------------+           |
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 3: MY SCHEDULE TODAY                                                                                |
|                                                                                                            |
|  My Appointments — Thursday 25 Sep 2026                                                                   |
|  +--------------------------------------------------------------------------------------------------------+|
|  |  Time   | Customer      | Service   | Status         | Duration | Action                              |||
|  |  09:00  | Sara Khalil   | Haircut   | [COMPLETED]    | 30 min   | --                                  |||
|  |  10:30  | Leila Mohamed | Manicure  | [COMPLETED]    | 45 min   | --                                  |||
|  |  12:00  | --            | --        | FREE           | --       | --                                  |||
|  |  13:30  | Ahmed Rashed  | Haircut   | [CONFIRMED]    | 30 min   | [Check In] [Start] [Complete]       |||
|  |  15:00  | Mona Gamal    | Hair Color| [CONFIRMED]    | 90 min   | [Check In]                          |||
|  |                                                                                                        |||
|  |  Next: Ahmed Rashed at 1:30 PM — Haircut (30 min) — 1 hr 22 min remaining                           |||
|  +--------------------------------------------------------------------------------------------------------+|
+=============================================================================================================+
```

---

### B.4 Inventory Employee View

```
+=============================================================================================================+
|  ZONE 1: HEADER                                                                                           |
|  [Logo]  Salon Manager           [Branch: Cairo - Zamalek]    [Bell 4]   [Yusuf I. v]                   |
|  Good morning, Yusuf             Thursday, 25 September 2026                                             |
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 2: QUICK ACTIONS                                                                                    |
|  [+ Stock Adjustment]  [+ New Purchase]  [View Transfers]                                                 |
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 3: INVENTORY STATUS KPIs                                                                            |
|  +--------------------+  +--------------------+  +--------------------+  +--------------------+           |
|  | Out of Stock       |  | Low Stock          |  | Products Tracked   |  | Today's Adjustments|           |
|  | 2 products  CRIT   |  | 5 products  WARN   |  | 148                |  | 3                  |           |
|  | [Manage ->]        |  | [Manage ->]        |  | [View All ->]      |  | [History ->]       |           |
|  +--------------------+  +--------------------+  +--------------------+  +--------------------+           |
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 4: INVENTORY ALERTS (primary content)                                                               |
|                                                                                                            |
|  Inventory Alerts — Cairo Zamalek                         [All] [Out of Stock] [Low Stock]               |
|  +--------------------------------------------------------------------------------------------------------+|
|  |  [CRITICAL] Moroccan Argan Oil — OUT OF STOCK                                                         |||
|  |  Last purchase: 2026-09-10 | Last movement: Sold 5 units on 2026-09-24                                |||
|  |  [Purchase Now]  [Transfer from Maadi]                                                                |||
|  |  --------------                                                                                       |||
|  |  [WARNING] Hair Color 6.0 — 3 units (reorder point: 10)                                              |||
|  |  Last purchase: 2026-09-01 | Supplier: Cairo Supplies Ltd                                             |||
|  |  [Adjust Stock]  [Purchase Now]                                                                       |||
|  |  --------------                                                                                       |||
|  |  [WARNING] Anti-Aging Serum — 2 units (reorder point: 8)                                             |||
|  |  Last purchase: 2026-08-15 | Supplier: Beauty Wholesale                                               |||
|  |  [Adjust Stock]  [Purchase Now]                                                                       |||
|  +--------------------------------------------------------------------------------------------------------+|
+=============================================================================================================+

+=============================================================================================================+
|  ZONE 5: RECENT INVENTORY MOVEMENTS                                                                       |
|                                                                                                            |
|  Recent Stock Movements — Today                                     [View Full History ->]                |
|  [purchase icon]  Hair Color 8.0 — +50 units — Purchase Invoice #PI-0145   08:30 AM                     |
|  [adjust icon]    Moroccan Argan Oil — -5 units — Waste adjustment          09:15 AM                     |
|  [sale icon]      Keratin Treatment — -2 units — Invoice #1045              10:05 AM                     |
|  [transfer icon]  Shampoo Argan 500ml — +20 units — Transfer from Maadi    11:30 AM                     |
+=============================================================================================================+
```

---

## C. KPI CARD DETAILED WIREFRAME

```
+--------------------------------------+
|  [icon]  Revenue Today               |
|  EGP 12,450.00                       |   <- Large, bold, currency formatted
|  ↑ +8.3% vs. yesterday               |   <- Colored arrow + % delta
|  Yesterday: EGP 11,490               |   <- Secondary comparison value
|  Services: EGP 9,200 | Products: EGP 3,250 |  <- Breakdown (optional)
|  [View Revenue Report ->]            |   <- Click-through link
+--------------------------------------+
```

### KPI Card States

```
LOADING STATE:
+--------------------------------------+
|  [____]  [________________]          |
|  [________________________________]  |   <- Skeleton shimmer animation
|  [_____________]                     |
|  [_________________________]         |
+--------------------------------------+

EMPTY STATE:
+--------------------------------------+
|  [icon]  Revenue Today               |
|  EGP 0.00                            |
|  No sales recorded today             |
|  [+ Create Invoice ->]               |
+--------------------------------------+

ERROR STATE:
+--------------------------------------+
|  [!]  Revenue Today                  |
|  Could not load                      |
|  [Retry]                             |
+--------------------------------------+
```

---

## D. APPOINTMENT STATUS BADGES

```
Requested  = [  PENDING   ]  (blue)
Confirmed  = [  CONFIRMED ]  (teal)
Checked In = [ CHECKED IN ]  (amber)
In Service = [ IN SERVICE ]  (amber, pulsing)
Completed  = [  COMPLETED ]  (green)
Cancelled  = [  CANCELLED ]  (red)
No Show    = [  NO SHOW   ]  (dark red)
Expired    = [  EXPIRED   ]  (gray)
```

---

## E. ALERT CARD WIREFRAME

```
CRITICAL:
+--------------------------------------+
| [!] CRITICAL                          |
| Moroccan Argan Oil OUT OF STOCK       |
| Cairo - Zamalek branch                |
| [Purchase Now]  [Transfer Stock]      |
+--------------------------------------+

WARNING:
+--------------------------------------+
| [triangle] WARNING                   |
| Hair Color 6.0 — 3 units remaining   |
| (Reorder point: 10)                  |
| [Purchase Now]  [Adjust Stock]       |
+--------------------------------------+

INFO:
+--------------------------------------+
| (i) INFO                             |
| 3 invoices have outstanding balances |
| Total: EGP 750                       |
| [View Invoices]                      |
+--------------------------------------+
```

---

## F. QUICK ACTION BUTTON DESIGN

```
+------------------------+
|  [+]  New Appointment  |
+------------------------+

+------------------------+
|  [receipt] New Invoice |
+------------------------+

+------------------------+
|  [person] New Customer |
+------------------------+
```

On mobile — FAB (floating action button):
```
                [+] (tapping expands to:)
         [Appointment] [Invoice] [Customer]
```

---

## G. FILTER BAR WIREFRAME

```
+============================================================+
|  Period:  [Today v]     Branch: [All Branches v]           |
|                         (only admin/owner see this)        |
|           [Custom range: 2026-09-01 to 2026-09-25]         |
|                                         [Apply] [Reset]    |
+============================================================+
```

Date range shortcuts:
```
[Today] [Yesterday] [This Week] [This Month] [Last Month] [Custom...]
```

---

## H. REVENUE TREND CHART DETAIL

```
CHART CONTROLS:
  View by: [Day] [Week] [Month]
  Series:  [Total] [Services] [Products]
  Compare: [Off] [Previous Period] [Previous Year]

CHART BODY:
  Multi-series line chart
  X-axis: dates matching selected period
  Y-axis: EGP values (left axis)
  Tooltip: shows all series values on hover

CHART FOOTER:
  KPI summary row:
  "Period total: EGP 124,500  |  Daily avg: EGP 4,150  |  Peak day: Sep 20 (EGP 15,800)"
```

---

## I. APPOINTMENT TABLE ACTIONS

Action buttons appear contextually based on current appointment status:

```
Status: requested  -> [Confirm] [Reject]
Status: confirmed  -> [Check In] [Cancel] [Reschedule]
Status: checked_in -> [Start Service] [Cancel] [No Show]
Status: in_service -> [Complete] [Cancel]
Status: completed  -> [View Invoice] (if invoice linked) [Create Invoice] (if not)
Status: cancelled  -> (read-only)
Status: no_show    -> (read-only)
Status: expired    -> (read-only)
```

---

## J. BRANCH SELECTOR (HEADER COMPONENT)

```
ADMIN / OWNER:
+---------------------------+
| [globe] All Branches   v  |
+---------------------------+
  Dropdown:
    [All Branches]            <- Shows aggregate
    [Cairo - Zamalek]
    [Cairo - Maadi]
    [Alexandria - Smouha]

BRANCH MANAGER / CASHIER / OTHERS:
+---------------------------+
| [building] Cairo - Zamalek |  <- No dropdown, locked
+---------------------------+
```

---

## K. STAFF PERFORMANCE TABLE DESIGN

```
+----+------------------+-----------+-----------+-----------+-------+-----------+-------+
| #  | Employee         | Services  | Revenue   | Commission| Appts | Branch    | Trend |
+----+------------------+-----------+-----------+-----------+-------+-----------+-------+
| 1  | Ahmed Hassan     | 8         | EGP 3,200 | EGP 320   | 5     | Zamalek   | [^]   |
| 2  | Sara Ahmed       | 6         | EGP 2,750 | EGP 275   | 4     | Zamalek   | [^]   |
| 3  | Yusuf Mohamed    | 5         | EGP 1,900 | EGP 190   | 3     | Maadi     | [-]   |
| 4  | Hana Khalil      | 4         | EGP 1,600 | EGP 160   | 3     | Zamalek   | [v]   |
+----+------------------+-----------+-----------+-----------+-------+-----------+-------+

Trend symbols:
[^] = above previous period average (green)
[-] = at previous period average (gray)
[v] = below previous period average (red)
```

---

## L. RECENT ACTIVITY FEED DESIGN

```
+------------------------------------------------------------------+
| [invoice icon]  Invoice #1045 — EGP 450 — Sara K. — Zamalek      |
|                                                         5 min ago |
+------------------------------------------------------------------+
| [check icon]    Appointment completed — Haircut by Ahmed Hassan   |
|                                                        12 min ago |
+------------------------------------------------------------------+
| [person icon]   New customer: Nour Ibrahim registered             |
|                                                        18 min ago |
+------------------------------------------------------------------+
| [box icon]      Stock adjusted: Hair Color 6.0 -5 (waste) Yusuf  |
|                                                        22 min ago |
+------------------------------------------------------------------+
| [cancel icon]   Appointment cancelled — 3:30 PM — Sara Ahmed      |
|                                                        42 min ago |
+------------------------------------------------------------------+
|                                                  [View all ->]   |
+------------------------------------------------------------------+
```

---

## M. RESPONSIVE LAYOUT BREAKPOINTS

### Desktop (>= 1280px): 12-column grid

```
Row 1: [Header 12/12]
Row 2: [Filter Bar 12/12]
Row 3: [Quick Actions 12/12]
Row 4: [KPI 3/12] [KPI 3/12] [KPI 3/12] [KPI 3/12]
Row 5: [KPI 3/12] [KPI 3/12] [KPI 3/12] [KPI 3/12]
Row 6: [Appt Status 5/12] [Appt Table 7/12]
Row 7: [Revenue Chart 12/12]
Row 8: [Branch Chart 6/12] [Expense Chart 6/12]
Row 9: [Staff Table 12/12]
Row 10: [Top Services 6/12] [Top Products 6/12]
Row 11: [Inventory Alerts 6/12] [Action Required 6/12]
Row 12: [Recent Activity 12/12]
```

### Tablet (768px-1279px): 2-column

```
Row 1: [Header 12/12]
Row 2: [Filter Bar 12/12]
Row 3: [Quick Actions 12/12]
Row 4: [KPI 6/12] [KPI 6/12]
Row 5: [KPI 6/12] [KPI 6/12]
Row 6: [Appt Status Donut 12/12]
Row 7: [Appt Table 12/12]
Row 8: [Revenue Chart 12/12]
Row 9: [Staff Table 12/12]
Row 10: [Top Services 12/12]
Row 11: [Inventory Alerts 12/12]
Row 12: [Action Required 12/12]
Row 13: [Recent Activity 12/12]
```

### Mobile (< 768px): Single column

```
Row 1: [Header (compact) 12/12]
Row 2: [Quick Actions FAB — fixed bottom right]
Row 3: [Alerts: Critical only 12/12]
Row 4: [KPI 12/12] (Revenue)
Row 5: [KPI 12/12] (Expenses)
Row 6: [Appt Status Donut 12/12] (compact)
Row 7: [Today's Appointments (simplified) 12/12]
       — shows only: Time | Customer | Status | Action
Row 8: [Recent Activity (last 5) 12/12]
--- BELOW FOLD (lazy loaded on scroll) ---
Row 9: [Revenue Chart 12/12]
Row 10: [Staff Table 12/12]
Row 11: [Inventory Alerts 12/12]
```

---

## N. SECTION VISIBILITY MATRIX

| Section | Owner/Admin | Manager | Receptionist | Cashier | Provider | Inventory |
|---|---|---|---|---|---|---|
| Header | YES | YES | YES | YES | YES | YES |
| Filter Bar | YES (all branches) | YES (own branch) | YES (own branch) | YES (own branch) | YES (own branch) | YES (own branch) |
| Quick Actions | [Appt][Invoice][Customer][Expense][Stock] | [Appt][Invoice][Customer][Expense][Stock] | [Appt][Customer] | [Invoice][Customer] | [Appt action] | [Stock][Purchase] |
| Revenue KPIs | YES | YES | NO | Cash+Card only | NO | NO |
| Net Profit KPI | YES | YES | NO | NO | NO | NO |
| Appointments KPIs | YES | YES | YES | NO | Own only | NO |
| Customer KPIs | YES | YES | NO | NO | NO | NO |
| Appointment Status Donut | YES | YES | YES | NO | Own only | NO |
| Appointment Table (all) | YES | YES | YES | NO | Own only | NO |
| Revenue Trend Chart | YES | YES | NO | NO | NO | NO |
| Branch Performance Chart | YES (admin only) | NO | NO | NO | NO | NO |
| Staff Performance Table | YES | YES | NO | NO | NO | NO |
| Top Services Chart | YES | YES | NO | NO | NO | NO |
| Top Products Chart | YES | YES | NO | NO | NO | NO |
| Inventory Alerts | YES | YES | NO | NO | NO | YES |
| Expense Overview | YES | YES | NO | NO | NO | NO |
| Action Required | YES | YES | Appt alerts only | Draft invoice only | Own appt actions | Stock alerts only |
| Recent Activity | YES (all types) | YES (branch only) | Appt events | Invoice events | Own appt | Inventory events |

---

## O. NOTIFICATION BELL DROPDOWN

```
+---------------------------+
| [Bell]  3 Notifications   |
+---------------------------+
| [!] CRITICAL              |
| Moroccan Argan Oil        |
| Out of stock - Zamalek    |
| 2 hours ago               |
+---------------------------+
| [^] WARNING               |
| Invoice #1042 draft       |
| for 5+ hours              |
| 3 hours ago               |
+---------------------------+
| (i) INFO                  |
| 3 unconfirmed appts       |
| for today                 |
| 4 hours ago               |
+---------------------------+
| [View all notifications]  |
+---------------------------+
```

---

## P. ACCESSIBILITY DESIGN NOTES

### Focus Order

1. Skip-to-content link (hidden, appears on Tab)
2. Business Logo link
3. Branch Selector
4. Notification Bell
5. User Menu
6. Filter Bar controls (period buttons, branch selector)
7. Quick Action buttons (left to right)
8. KPI cards (left to right, top to bottom)
9. Chart sections (with keyboard controls)
10. Appointment table (sortable headers, row actions)
11. Alert cards (action buttons)
12. Recent activity items

### ARIA Roles

```html
<main role="main" aria-label="Business Dashboard">
  <section aria-label="Key Performance Indicators" aria-live="polite">
    <!-- KPI cards -->
  </section>
  <section aria-label="Today's Appointments" aria-live="polite">
    <!-- Appointment table -->
  </section>
  <section aria-label="Revenue Trend">
    <!-- Chart with aria-label and data table fallback -->
  </section>
  <section aria-label="Action Required Alerts" aria-live="assertive">
    <!-- Critical alerts -->
  </section>
</main>
```

### Chart Accessibility

Every chart must have:
1. `aria-label` describing the chart and its data
2. A visually hidden data table as fallback for screen readers
3. Keyboard-navigable data points

---

## Q. EMPTY BRANCH / FIRST RUN STATE

When a branch has no data (new branch, first day):

```
+------------------------------------------------------------------+
|                                                                  |
|   [welcome illustration]                                         |
|                                                                  |
|   Welcome to your Dashboard!                                     |
|   Start by creating your first appointment or invoice.           |
|                                                                  |
|   [+ Create Appointment]    [+ Create Invoice]                   |
|                                                                  |
+------------------------------------------------------------------+
```

KPI cards show EGP 0.00 with "No data for this period" subtext rather than error states.

---

## R. MOBILE FAB (FLOATING ACTION BUTTON)

```
Position: Fixed, bottom-right, z-index: 1000

Collapsed:
  +-------+
  |   +   |
  +-------+

Expanded (tap/click + icon):
  +------------------+
  | + Appointment    |
  +------------------+
  | + Invoice        |
  +------------------+
  | + Customer       |
  +------------------+
  |   [x] Close      |
  +------------------+
```

---

*Document version: 1.0 | Author: Dashboard Design Analysis | Date: 2026-09-25*

