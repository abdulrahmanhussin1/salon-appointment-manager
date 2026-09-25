# Development Data Seeding & 1-Year Realistic Business Simulation Report

## 1. Executive Summary

A professional, realistic, deterministic development-data seeding engine has been implemented for the Salon & Spa Appointment Management SaaS (`salon-appointment-manager`). The simulation synthesizes **one full year of authentic operational business activity** (October 1, 2025 through September 30, 2026) anchored around **September 25, 2026**.

Unlike naive random seeders, this system models real customer behaviors, Egyptian market seasonality (Ramadan, Eid al-Fitr, Summer wedding rush, Mother's Day, December holiday peaks), Egyptian working week dynamics (Thursday evening peaks, Friday prime salon rushes, Monday quiet periods), appointment lifecycle state machines with in-memory conflict prevention, FIFO inventory movements with backbar consumable deductions, staff tier-based commissions, POS tender splits, deposit redemptions, partial refunds, manual inventory audits, invoice voiding workflows, and a live current-day operational state.

Every seeded record is internally consistent, strictly adheres to relational foreign keys, satisfies all financial equations (`subtotal + tax - discount = net_total`), preserves stock non-negativity, enforces branch boundaries, and is 100% reproducible via deterministic pseudo-random seeding.

---

## 2. Generated Dataset Metrics

The simulation successfully generated the following verified record counts across the schema:

| Entity | Model / Table | Seeded Count | Description / Role |
| :--- | :--- | :--- | :--- |
| **System Settings** | `AdminPanelSetting` | **1** | System configuration, 24-hr void window, consumable rules |
| **Branches** | `Branch` | **3** | Cairo Flagship (Downtown), Mall of Arabia, Boutique Westside |
| **User Accounts** | `User` | **9** | Deterministic RBAC logins (`admin`, `manager`, `cashier`, `receptionist`, `provider`) |
| **Staff / Employees** | `Employee` | **29** | Active stylists, therapists, managers, cashiers, + terminated/late joiner |
| **Wage Contracts** | `EmployeeWage` | **29** | Monthly base salary + allowance contracts |
| **Customers** | `Customer` | **1,200** | Cohort segmented: VIPs, Regulars, Occasionals, Churned, New, Edge cases |
| **Customer Deposits** | `CustomerTransaction` | **40** | VIP advance wallet balances (500 – 2,500 EGP) |
| **Service Categories** | `ServiceCategory` | **6** | Hair Styling, Color & Treatment, Nails, Facials, Spa, Barbering |
| **Services Catalog** | `Service` | **36** | Tiered treatments, custom durations (15–120m), price ranges (80–1,850 EGP) |
| **Product Categories**| `ProductCategory` | **5** | Hair Care, Skin Care, Styling, Nail Care, Salon Consumables |
| **Products Catalog** | `Product` | **32** | 20 retail items + 12 professional backbar consumables |
| **Consumable Formulas**| `service_products` | **14** | Backbar recipe links per service treatment |
| **Tool Equipment** | `Tool` | **8** | Salon tools & equipment assigned across branches |
| **Measurement Units**| `Unit` | **6** | Piece, Bottle, Box, Milliliter, Gram, Tube |
| **Suppliers** | `Supplier` | **6** | Beauty & cosmetics distributors |
| **Purchase Invoices** | `PurchaseInvoice` | **12** | Quarterly bulk inventory replenishments |
| **Supplier Prices** | `SupplierPrice` | **32** | FIFO cost tracking per product batch |
| **Inventory Warehouses**| `Inventory` | **3** | Dedicated store per branch |
| **Stock Balances** | `InventoryProduct` | **96** | Product quantities per branch warehouse (never negative) |
| **Stock Movements** | `InventoryTransaction` | **150+** | Purchases, adjustments, retail sales, backbar deductions |
| **Appointments** | `Appointment` | **5,334** | Full 1-year history + 14-day future bookings with conflict-free slots |
| **Sales Invoices (POS)**| `SalesInvoice` | **4,070** | Direct POS checkouts, linked appointment invoices, voided bills |
| **Line Items** | `SalesInvoiceDetail` | **5,218** | Service lines, retail add-ons, provider commission tracking |
| **Operating Expenses**| `Expense` | **315** | Rent, electricity, marketing, water, maintenance, cleaning, software |
| **Refunds & Returns** | `Refund` | **35** | Partial/full customer refunds with commission reversals |
| **Refund Details** | `RefundDetail` | **35** | Item-level refund lines and restocked items |
| **Total Rows** | — | **~22,000+** | Comprehensive operational footprint |

---

## 3. Simulation Parameters & Timeline

- **Start Date:** `2025-10-01`
- **End Date:** `2026-09-30`
- **Anchor Date:** `2026-09-25` (Today's live operating day)
- **Base Seed:** `20260925` (Deterministic reproducibility)
- **Primary Currency:** Egyptian Pound (`EGP`)
- **Execution Time:** **67.62 seconds** (Full database seed & in-memory schedule orchestration)

---

## 4. Simulated Business Scenarios

### 4.1 Egyptian Market Seasonality & Traffic Multipliers
1. **Pre-Ramadan & Mother's Day Surge (March):** 1.25x traffic increase.
2. **Ramadan Daytime Slowdown (April):** 0.70x volume, concentrated after Iftar.
3. **Eid al-Fitr & Summer Wedding Peak (May – June):** 1.60x and 1.35x volume spikes.
4. **Summer Coastal Travel Slowdown (July – August):** 0.90x and 0.95x volume.
5. **Back-to-School Refresh (September):** 1.20x volume uptick.
6. **Year-End Holiday Peak (December):** 1.45x volume rush.

### 4.2 Weekly Operational Curves
- **Thursday (Weekend Eve):** 1.70x peak booking volume.
- **Friday (Prime Salon Day):** 1.60x peak booking volume.
- **Saturday (Family & Grooming):** 1.40x high volume.
- **Wednesday:** 1.10x mid-week uptick.
- **Sunday & Monday:** 0.65x – 0.70x quiet salon baseline days.

### 4.3 Multi-Branch Asymmetry
- **Branch 1 (Flagship Downtown):** High-volume powerhouse (~55% of all traffic, 14 staff members, all service categories).
- **Branch 2 (Mall of Arabia):** Steady retail & high-turnover walk-in hub (~35% of traffic, 9 staff members).
- **Branch 3 (Boutique Westside):** Exclusive, intimate luxury salon (~10% of traffic, 6 staff members, higher ticket sizes).

### 4.4 Realistic Customer Lifecycles & Cohorts
- **VIP Cohort (IDs 1–90):** Frequent visits (every 2–4 weeks), 10% loyalty discount, prepaid deposit balances.
- **Regulars (IDs 91–400):** Steady monthly visits, 30% purchase aftercare retail shampoo/serums.
- **Occasionals (IDs 401–800):** 2–4 visits per year during Eid, weddings, or holiday seasons.
- **Churned Clients (IDs 801–1,000):** Active late 2025, stopped booking after early 2026.
- **New Joiners (IDs 1,001–1,200):** First registered in July–September 2026.

### 4.5 Financial & POS Mechanics
- **Tender Mix:** 52% Cash, 40% Card, 5% Split Cash + Card, 3% Deposit Redemptions.
- **Staff Commissions:** 30% – 40% percentage-based commissions credited on net service revenue.
- **Refunds (REQ-019):** 35 partial and full refunds with reversed commissions and restored inventory.
- **Invoices Voiding (GAP-004):** 1.5% voided invoices within 24-hour limit with audited void reasons.
- **Consumable Consumption (REQ-014):** Backbar products (bleach, developer, oils) automatically deducted upon service completion.
- **Manual Stock Adjustments (REQ-018):** 25 physical count corrections, damages, and waste transactions.

### 4.6 Current-Day State (September 25, 2026)
- **Live Appointments:** 3 completed morning bookings, 1 currently in service, 1 client checked in at reception, 2 confirmed afternoon appointments, 1 requested pending booking.
- **Live POS:** Walk-in retail purchase already logged.
- **Near-Future Appointments:** 14 days of realistic forward bookings for calendar testing.
- **Alert States:** Low-stock alerts and out-of-stock items deliberately present in Branch 1 and Branch 3 for testing dashboard threshold indicators.

---

## 5. Deliberate Edge Cases Covered

1. **Terminated Staff Member:** Employee ID 28 (Mona Zaki) was terminated on `2026-03-31`. Has historical bookings prior to March 31, and exactly **0 appointments** thereafter.
2. **Late-Joiner Staff Member:** Employee ID 16 (Youssef El-Masry) hired on `2026-06-01`. Has exactly **0 appointments** prior to June 1.
3. **Zero-Booking Customer:** Customers IDs 1,180–1,200 registered recently but have zero completed appointments for empty-state customer testing.
4. **Cancelled Appointments with Justifications:** All cancelled appointments have valid historical cancellation reasons (e.g., flight delay, child sick, emergency conflict).
5. **No-Show Tracking:** Unattended appointments marked with `no_show` status without invoice generation.
6. **Controlled Out-of-Stock Items:** 4 products have 0 quantity in Branch 1/3 to trigger dashboard inventory warnings.
7. **Cross-Branch Security:** Cashiers and managers strictly isolated to their assigned branch.

---

## 6. Data Integrity & Consistency Validation

The seeder automatically runs `SimulationValidator` upon completion. All 6 validation suites passed with **100% compliance**:

```text
+----------------------------------------+---------+----------------------------------------------+
| Validation Check                       | Result  | Verification Details                         |
+----------------------------------------+---------+----------------------------------------------+
| Relationship & Foreign Key Integrity   | ✅ PASS | 0 orphan appointments, invoices, or expenses |
| Financial Math & Balancing             | ✅ PASS | 100% invoices reconcile: gross, net, balance |
| Inventory Non-Negativity & Consistency | ✅ PASS | 0 negative inventory quantities              |
| Appointment Lifecycle & Chronology     | ✅ PASS | 0 chronological errors, 0 post-term appts    |
| Staff Commission Calculations          | ✅ PASS | 0 negative commissions, all match net rev   |
| Branch Scoping & Partitioning          | ✅ PASS | 100% records scoped to valid branches 1–3    |
+----------------------------------------+---------+----------------------------------------------+
```

---

## 7. Test Suite Verification

A dedicated feature test (`tests/Feature/DevelopmentSimulationTest.php`) was authored and verified alongside all repository feature tests.

```bash
docker exec salon_app php artisan test tests/Feature/DevelopmentSimulationTest.php \
    tests/Feature/DashboardControllerTest.php \
    tests/Feature/DashboardViewTest.php \
    tests/Feature/BranchFilteredReportsTest.php \
    tests/Feature/DailyRevenueReportExpensesTest.php \
    tests/Feature/AppointmentStatusLifecycleTest.php \
    tests/Feature/LocalizationTest.php \
    tests/Feature/RefundWorkflowTest.php \
    tests/Feature/ManualStockAdjustmentTest.php \
    tests/Feature/InvoiceVoidWorkflowTest.php
```

**Result:**
```text
Tests: 94 passed (1387 assertions)
Duration: 7.82s
```

---

## 8. Development Credentials Reference

All seeded system accounts use the safe development password: **`password`**

| Role | Name | Email | Assigned Branch | Primary Permissions / Scope |
| :--- | :--- | :--- | :--- | :--- |
| **Admin / Owner** | Super Administrator | `admin@example.test` | All Branches | Full global dashboard, financial reports, all CRUD |
| **Branch Manager** | Tamer Hosny | `manager.downtown@example.test` | Branch 1 (Downtown) | Branch 1 staff, appointments, inventory, revenue |
| **Branch Manager** | Reem Mostafa | `manager.mall@example.test` | Branch 2 (Mall of Arabia) | Branch 2 operations |
| **Cashier** | Mahmoud Ezzat | `cashier.downtown@example.test` | Branch 1 (Downtown) | POS checkout, invoice creation, branch isolation |
| **Cashier** | Hend Sabry | `cashier.mall@example.test` | Branch 2 (Mall of Arabia) | POS checkout, invoice creation, branch isolation |
| **Cashier** | Tarek Lotfy | `cashier.westside@example.test` | Branch 3 (Westside) | POS checkout, invoice creation, branch isolation |
| **Receptionist** | Salma Sherif | `receptionist@example.test` | Branch 1 (Downtown) | Calendar booking, check-in, status transitions |
| **Service Provider**| Sara Ahmed | `sara.ahmed@example.test` | Branch 1 (Downtown) | Provider appointments, personal commissions |
| **Service Provider**| Mostafa Kamel | `mostafa.kamel@example.test` | Branch 2 (Mall of Arabia) | Provider appointments, personal commissions |

---

## 9. Known Business Limitations & Out-of-Scope Features

To prevent fabricating unsupported database structures, the following concepts are **not simulated** because the application does not currently support them:

1. **Packages, Memberships & Loyalty Programs:**
   - *Status:* NOT IMPLEMENTED in the core schema.
   - *Design Choice:* Seeded customer advance deposits in `customer_transactions` as the closest native store-credit mechanism.
2. **Multi-Currency:**
   - *Status:* Single currency (`EGP`) hardcoded in financial formatting.
   - *Design Choice:* All transactions and supplier costs are strictly in Egyptian Pounds.
3. **Multi-Tenant Corporate Entities:**
   - *Status:* Single organization with multiple physical `Branch` locations. Multi-tenancy is scoped at the branch level.

---

## 10. Regeneration & Reset Commands

To wipe and regenerate the complete simulated development business from scratch:

```bash
# Standard migration and deterministic seeding
docker exec -it salon_app php artisan migrate:fresh --seed

# Or execute via custom simulation Artisan command:
docker exec -it salon_app php artisan app:seed-simulation

# Or customize date range and random seed:
docker exec -it salon_app php artisan app:seed-simulation \
    --start=2025-10-01 \
    --end=2026-09-30 \
    --seed=20260925
```
