# Data Seeding Domain Map — Salon Appointment Manager

This document maps all database models, schema attributes, foreign keys, relationships, domain constraints, and dependency graphs for the 1-Year Realistic Business Simulation Engine.

---

## 1. Domain Entities & Schema Map

### 1.1 Foundation & Administration

#### AdminPanelSetting
- **Table:** `admin_panel_settings`
- **Purpose:** System-wide configuration (brand name, contact, logo, void time window, inventory consumable enforcement).
- **Key Fields:** `system_name`, `system_phone`, `system_address`, `system_logo`, `void_time_window_hours` (default: 24), `block_insufficient_consumables` (default: false), `created_by`, `updated_by`.
- **Relationships:** None (singleton row).
- **Seeded:** Yes (1 record).
- **Dependencies:** `users` (for `created_by`).

#### Branch
- **Table:** `branches`
- **Purpose:** Physical business locations. Root scope for inventory, employees, expenses, and invoices.
- **Key Fields:** `name`, `address`, `phone` (unique), `email` (unique), `status` (`active | inactive`), `manager_id` (nullable FK -> `employees`), `created_by`, `updated_by`.
- **Relationships:**
  - `hasMany(Employee)`
  - `hasOne(Inventory)`
  - `hasMany(SalesInvoice)`
  - `hasMany(PurchaseInvoice)`
  - `hasMany(Expense)`
  - `hasMany(Refund)`
- **Seeded:** Yes (3 branches: Flagship Downtown, Mall Branch, Boutique Westside).
- **Dependencies:** `users` (for `created_by`). Note: `manager_id` is updated after employees are seeded to avoid cyclic FK deadlock.

#### User
- **Table:** `users`
- **Purpose:** Authentication and RBAC accounts for system operators.
- **Key Fields:** `name`, `email` (unique), `password`, `status` (`active | inactive`), `employee_id` (nullable FK -> `employees`), `created_by`.
- **Relationships:**
  - `belongsTo(Employee)`
  - `roles` / `permissions` via Spatie trait `HasRoles`.
- **Seeded:** Yes (deterministic role accounts: Owner/Admin, Branch Managers, Cashiers, Receptionists, Staff).
- **Dependencies:** None for root admin (`created_by = 1` self-reference), then subsequent users depend on admin.

#### Role & Permission (Spatie)
- **Tables:** `roles`, `permissions`, `model_has_roles`, `role_has_permissions`.
- **Purpose:** Role-Based Access Control and fine-grained capabilities.
- **Key Roles:** `admin`, `cashier`, `manager`, `receptionist`, `provider`.
- **Seeded:** Yes (core system permissions and role assignments).
- **Dependencies:** Spatie permission migrations, `users`.

---

### 1.2 Catalog & Master Data

#### Unit
- **Table:** `units`
- **Purpose:** Measurement units for inventory products (e.g., Piece, Bottle, Box, Milliliter, Gram).
- **Key Fields:** `name`, `status` (`active | inactive`), `created_by`.
- **Seeded:** Yes.
- **Dependencies:** `users`.

#### Tool
- **Table:** `tools`
- **Purpose:** Equipment required for delivering services (e.g., Hairdryer, Scissors, Laser Wand).
- **Key Fields:** `name`, `status` (`active | inactive`), `created_by`.
- **Seeded:** Yes.
- **Dependencies:** `users`.

#### Supplier
- **Table:** `suppliers`
- **Purpose:** Vendors supplying retail and operational products.
- **Key Fields:** `name`, `email` (nullable), `phone` (nullable), `address` (nullable), `status` (`active | inactive`), `created_by`.
- **Relationships:** `hasMany(Product)`, `hasMany(PurchaseInvoice)`, `hasMany(SupplierTransaction)`.
- **Seeded:** Yes (5-8 professional beauty & wellness suppliers).
- **Dependencies:** `users`.

#### ProductCategory
- **Table:** `product_categories`
- **Purpose:** Hierarchical grouping of products.
- **Key Fields:** `name`, `status` (`active | inactive`), `created_by`.
- **Relationships:** `hasMany(Product)`.
- **Seeded:** Yes (Hair Care, Skin Care, Styling & Finishing, Nail Care, Consumables & Salon Supplies).
- **Dependencies:** `users`.

#### ServiceCategory
- **Table:** `service_categories`
- **Purpose:** Grouping of salon & spa treatments.
- **Key Fields:** `name`, `status` (`active | inactive`), `created_by`.
- **Relationships:** `hasMany(Service)`.
- **Seeded:** Yes (Haircut & Styling, Hair Color & Treatment, Nails & Manicure, Facial & Skincare, Massage & Spa, Grooming & Shaving).
- **Dependencies:** `users`.

#### EmployeeLevel
- **Table:** `employee_levels`
- **Purpose:** Seniority and tier classification for staff (e.g., Junior, Senior, Master Stylist, Specialist).
- **Key Fields:** `name`, `status` (`active | inactive`), `created_by`.
- **Relationships:** `hasMany(Employee)`.
- **Seeded:** Yes.
- **Dependencies:** `users`.

---

### 1.3 People (Staff & Customers)

#### Employee
- **Table:** `employees`
- **Purpose:** Stylists, technicians, therapists, cashiers, and receptionists.
- **Key Fields:** `name`, `email` (unique, nullable), `phone` (unique, nullable), `national_id` (unique, nullable), `hiring_date`, `termination_date` (nullable), `job_title`, `gender` (`male | female`), `status` (`active | inactive`), `inactive_reason` (nullable), `employee_level_id` (FK), `branch_id` (FK), `created_by`.
- **Relationships:**
  - `belongsTo(Branch)`
  - `belongsTo(EmployeeLevel)`
  - `hasOne(EmployeeWage)`
  - `hasOne(User)`
  - `belongsToMany(Service)` via `service_employees` (with commission configuration)
  - `hasMany(SalesInvoiceDetail, 'provider_id')`
  - `hasMany(Appointment, 'provider_id')`
- **Seeded:** Yes (20-30 employees across 3 branches with varied hiring dates and performance tiers).
- **Dependencies:** `branches`, `employee_levels`, `users`.

#### EmployeeWage
- **Table:** `employee_wages`
- **Purpose:** Compensation and working schedule rules for employees.
- **Key Fields:** `employee_id` (unique FK), `salary_type` (`daily | weekly | monthly | commission`), `basic_salary`, `bonus_salary`, `total_salary`, `working_hours` (default: 8), `start_working_time` (e.g. 09:00:00), `overtime_rate`, `break_duration_minutes`.
- **Relationships:** `belongsTo(Employee)`.
- **Seeded:** Yes (1 per employee).
- **Dependencies:** `employees`.

#### Customer
- **Table:** `customers`
- **Purpose:** Clients receiving services and buying products. Global across branches.
- **Key Fields:** `name`, `email` (unique, nullable), `phone` (unique, nullable), `address`, `salutation` (`Mr | Mrs | Ms | Dr | Eng`), `status` (`active | inactive`), `dob`, `gender` (`male | female`), `is_vip` (boolean), `last_service` (date, nullable), `added_from` (`online | referral | walk_in | advertisement | direct`), `created_by`, `updated_by`.
- **Relationships:**
  - `hasMany(CustomerTransaction)`
  - `hasMany(SalesInvoice)`
  - `hasMany(Appointment)`
  - `hasMany(Refund)`
- **Seeded:** Yes (1,200+ customers segmented into VIP, Loyal, Regular, One-Time, Churned, and New).
- **Dependencies:** `users`.

#### CustomerTransaction
- **Table:** `customer_transactions`
- **Purpose:** Customer deposit ledger (prepayments, deposit redemption in invoices, deposit refunds).
- **Key Fields:** `customer_id` (FK), `reference_type`, `reference_id` (`morphs`), `amount` (decimal: positive = deposit added, negative = deposit consumed), `notes`, `status` (`available | used`), `used_in_transaction_id` (nullable self-reference), `created_by`.
- **Relationships:** `belongsTo(Customer)`.
- **Seeded:** Yes (initial deposits, invoice redemptions, deposit refunds).
- **Dependencies:** `customers`, `users`.

---

### 1.4 Services & Products Catalog

#### Service
- **Table:** `services`
- **Purpose:** Salon and spa treatments offered to customers.
- **Key Fields:** `name` (unique), `notes`, `duration` (minutes, unsigned tinyint), `price` (decimal 10,2), `outside_price`, `is_target` (boolean), `price_can_change` (boolean), `status` (`active | inactive`), `branch_id` (FK), `service_category_id` (FK), `created_by`.
- **Relationships:**
  - `belongsTo(ServiceCategory)`
  - `belongsTo(Branch)`
  - `belongsToMany(Employee)` via `service_employees` (pivot: `commission_type`, `commission_value`, `is_immediate_commission`)
  - `belongsToMany(Product)` via `service_products` (pivot: `product_quantity`)
  - `belongsToMany(Tool)` via `service_tools` (pivot: `tool_quantity`)
- **Seeded:** Yes (35-50 distinct services across categories).
- **Dependencies:** `branches`, `service_categories`, `users`.

#### Product
- **Table:** `products`
- **Purpose:** Physical items (retail sales or backbar operation consumables).
- **Key Fields:** `name`, `code` (unique, auto 100001+), `description`, `category_id` (FK -> `product_categories`), `supplier_id` (FK -> `suppliers`), `unit_id` (FK -> `units`), `initial_quantity`, `is_target`, `price_can_change`, `type` (`operation | sales`), `status` (`active | inactive`), `branch_id` (FK), `created_by`.
- **Relationships:**
  - `belongsTo(ProductCategory)`
  - `belongsTo(Supplier)`
  - `belongsTo(Unit)`
  - `hasMany(SupplierPrice)` (pricing history & inventory lot costs)
  - `hasMany(InventoryProduct)` (quantities by inventory warehouse)
- **Seeded:** Yes (80-120 products, split between retail sales and operation consumables).
- **Dependencies:** `product_categories`, `suppliers`, `units`, `branches`, `users`.

---

### 1.5 Inventory & Purchasing

#### Inventory
- **Table:** `inventories`
- **Purpose:** Stock storage location (warehouse/storeroom), 1 per branch.
- **Key Fields:** `name`, `status` (`active | inactive`), `branch_id` (FK), `created_by`.
- **Relationships:** `belongsTo(Branch)`, `hasMany(InventoryProduct)`.
- **Seeded:** Yes (1 per branch: 3 total).
- **Dependencies:** `branches`, `users`.

#### InventoryProduct
- **Table:** `inventory_products`
- **Purpose:** Current physical stock balance of a product in an inventory.
- **Key Fields:** `inventory_id` (FK), `product_id` (FK), `quantity` (unsigned big integer).
- **Seeded:** Yes (dynamically maintained and reconciled with purchases, sales, adjustments, returns).
- **Dependencies:** `inventories`, `products`.

#### PurchaseInvoice & PurchaseInvoiceDetail
- **Tables:** `purchase_invoices`, `purchase_invoice_details`
- **Purpose:** Restock shipments received from suppliers. Creates supplier prices and increments inventory products.
- **Key Fields (Invoice):** `invoice_number` (unique), `invoice_date`, `total_amount`, `status` (`active`), `invoice_discount`, `supplier_id` (FK), `branch_id` (FK), `created_by`.
- **Key Fields (Detail):** `purchase_invoice_id` (FK), `product_id` (FK), `supplier_price` (cost), `quantity`, `subtotal`, `discount`.
- **Seeded:** Yes (historical batches across 12 months simulating initial restock and monthly replenishment).
- **Dependencies:** `suppliers`, `branches`, `products`, `inventories`, `users`.

#### SupplierPrice
- **Table:** `supplier_prices`
- **Purpose:** FIFO lot ledger recording purchase cost and retail customer price. Decremented when sold in POS.
- **Key Fields:** `supplier_id` (FK), `product_id` (FK), `supplier_price`, `customer_price`, `discount`, `quantity` (remaining quantity in this cost lot).
- **Seeded:** Yes (created alongside purchase invoices).
- **Dependencies:** `suppliers`, `products`.

#### SupplierTransaction
- **Table:** `supplier_transactions`
- **Purpose:** Accounts payable ledger with suppliers.
- **Key Fields:** `supplier_id` (FK), `reference_id`, `reference_type` (`purchase`), `amount`, `notes`.
- **Seeded:** Yes (1 per purchase invoice).
- **Dependencies:** `suppliers`, `purchase_invoices`.

#### InventoryTransaction & InventoryTransactionDetail
- **Tables:** `inventory_transactions`, `inventory_transaction_details`
- **Purpose:** Audit log of stock movements (purchases, sales deductions, inter-branch transfers, service consumption, manual adjustments, sales returns).
- **Key Fields:** `transaction_type` (`purchase | sales | transfer | service_consumption | adjustment | sales_return`), `adjustment_type` (`increase | decrease`), `adjustment_reason` (`count_correction | damage | waste | theft | other`), `source_inventory_id`, `destination_inventory_id`, `net_total`, `notes`, `created_by`, `created_at`.
- **Seeded:** Yes (generated for every inventory event).
- **Dependencies:** `inventories`, `products`, `users`.

---

### 1.6 Appointments & Operational Scheduling

#### Appointment
- **Table:** `appointments`
- **Purpose:** Client bookings linked to customer, provider (employee), and service over a scheduled time slot.
- **Key Fields:** `customer_id` (FK), `provider_id` (FK -> `employees`), `service_id` (FK -> `services`), `status` (`requested | confirmed | rejected | cancelled | rescheduled | checked_in | in_service | completed | no_show | expired`), `start_date` (DATETIME), `end_date` (DATETIME), `cancelled_at`, `cancellation_reason`, `created_by`, `updated_by`.
- **Relationships:**
  - `belongsTo(Customer)`
  - `belongsTo(Employee, 'provider_id')`
  - `belongsTo(Service)`
  - `hasOne(SalesInvoice, 'appointment_id')`
- **Seeded:** Yes (4,000+ appointments over 12 months + today + 14 days future window, respecting provider working hours and non-conflicting schedules).
- **Dependencies:** `customers`, `employees`, `services`, `users`.

---

### 1.7 Sales, Invoicing & Financial Operations

#### PaymentMethod
- **Table:** `payment_methods`
- **Purpose:** Payment tenders accepted at checkout.
- **Key Fields:** `name` (`cash`, `credit card`, `bank transfer`), `status` (`active | inactive`), `created_by`.
- **Seeded:** Yes (`cash`, `credit card`, `bank transfer`).
- **Dependencies:** `users`.

#### SalesInvoice & SalesInvoiceDetail
- **Tables:** `sales_invoices`, `sales_invoice_details`
- **Purpose:** Primary point-of-sale customer checkout document.
- **Key Fields (Invoice):** `invoice_date`, `status` (`active | voided | draft`), `refund_status` (`none | partial | full`), `total_amount` (gross), `invoice_discount`, `invoice_tax`, `net_total`, `invoice_deposit`, `paid_amount_cash`, `payment_method_id` (FK), `payment_method_value`, `balance_due`, `total_refunded`, `customer_id` (FK), `appointment_id` (nullable FK), `branch_id` (FK), `voided_by`, `voided_at`, `void_reason`, `created_by`.
- **Key Fields (Detail):** `sales_invoice_id` (FK), `service_id` (nullable FK), `product_id` (nullable FK), `provider_id` (FK -> `employees`), `customer_price`, `quantity`, `discount` (percentage), `tax` (percentage), `subtotal`, `commission_type` (`percentage | value`), `commission_rate`, `commission_amount`, `is_immediate_commission`, `refunded_quantity`, `refunded_amount`.
- **Financial Invariant:**
  - `subtotal = (customer_price * quantity) * (1 - discount/100) * (1 + tax/100)`
  - `total_amount = sum(details.subtotal)`
  - `net_total = total_amount - invoice_discount + invoice_tax`
  - `balance_due = net_total - paid_amount_cash - payment_method_value - invoice_deposit`
- **Seeded:** Yes (3,000+ sales invoices across 12 months with realistic tenders, conversion from appointments, retail sales, split tenders, deposits, and voided orders).
- **Dependencies:** `customers`, `employees`, `services`, `products`, `payment_methods`, `branches`, `appointments`, `users`.

#### Refund & RefundDetail
- **Tables:** `refunds`, `refund_details`
- **Purpose:** Official return/refund records for previously paid invoices.
- **Key Fields (Refund):** `refund_number` (e.g. `REF-20260515-0001`), `sales_invoice_id` (FK), `customer_id` (FK), `branch_id` (FK), `refund_date`, `refund_method` (`cash | deposit | card | bank_transfer`), `total_refund_amount`, `tax_refund_amount`, `commission_reversed_amount`, `reason`, `notes`, `created_by`.
- **Key Fields (Detail):** `refund_id` (FK), `sales_invoice_detail_id` (FK), `service_id` (nullable), `product_id` (nullable), `provider_id` (nullable), `quantity`, `unit_price`, `discount`, `tax`, `subtotal`, `commission_reversed`, `inventory_restored` (boolean), `inventory_id` (nullable).
- **Seeded:** Yes (40-60 historical refunds: retail product returns with stock restoral, partial service refunds, full order cancellations).
- **Dependencies:** `sales_invoices`, `sales_invoice_details`, `customers`, `branches`, `inventories`, `users`.

#### ExpenseType & Expense
- **Tables:** `expense_types`, `expenses`
- **Purpose:** Operational overhead and operating expenditures.
- **Key Fields (Type):** `name` (e.g. Rent, Utilities, Salon Supplies, Maintenance, Marketing, Salaries/Wages, Software/Licensing, Cleaning/Sanitation), `status` (`active`).
- **Key Fields (Expense):** `expense_type_id` (FK), `description`, `amount`, `paid_amount`, `balance`, `paid_at` (DATETIME), `invoice_number`, `payment_method_id` (FK), `status` (`active | inactive`), `branch_id` (FK), `created_by`.
- **Seeded:** Yes (monthly fixed recurring expenses + variable weekly operational expenses across 12 months per branch).
- **Dependencies:** `expense_types`, `payment_methods`, `branches`, `users`.

---

## 2. Feature Support Classification

| Feature Area | Support Status | Seeding Treatment |
|---|---|---|
| **Multi-Branch Operations** | **IMPLEMENTED** | Seed 3 distinct operational branches with different volume profiles. |
| **RBAC / User Roles** | **IMPLEMENTED** | Seed Admin, Branch Managers, Cashiers, Receptionists, Providers. |
| **Appointment Lifecycle Machine** | **IMPLEMENTED** | Full status coverage (`requested`, `confirmed`, `checked_in`, `in_service`, `completed`, `cancelled`, `no_show`). |
| **Appointment-to-Invoice Linkage** | **IMPLEMENTED** | Completed appointments linked to `sales_invoices.appointment_id`. |
| **Employee Commission Calculation** | **IMPLEMENTED** | Service line items compute `commission_amount` from `service_employees`. |
| **Retail & Consumable Inventory** | **IMPLEMENTED** | Purchases, FIFO supplier prices, sales deduction, service consumption. |
| **Manual Stock Adjustments (REQ-018)** | **IMPLEMENTED** | Seed stock corrections, damages, waste, theft via `inventory_transactions`. |
| **Refunds & Product Returns (REQ-019)** | **IMPLEMENTED** | Seed `refunds`, `refund_details`, update `total_refunded`, restore stock. |
| **Invoice Voiding Workflow (GAP-004)**| **IMPLEMENTED** | Seed voided invoices within allowable window with audit reasons. |
| **Customer Deposit Balances** | **IMPLEMENTED** | Seed deposits in `customer_transactions`, deposit redemption in invoices. |
| **Operational Expenses & Categories** | **IMPLEMENTED** | Seed recurring overhead (rent, utilities) and variable operating costs. |
| **Audit Activity Timeline** | **IMPLEMENTED** | Unified activity feed across invoices, voids, appointments, refunds, stock. |
| **Packages / Bundles** | **NOT SUPPORTED** | **Do NOT seed**. Model does not exist in schema. |
| **Memberships / Subscriptions** | **NOT SUPPORTED** | **Do NOT seed**. Model does not exist in schema. |
| **Loyalty Points System** | **NOT SUPPORTED** | **Do NOT seed**. Model does not exist in schema. |
| **Multi-Currency Conversion** | **NOT SUPPORTED** | Single currency (EGP/Base) used across monetary columns. |

---

## 3. Deterministic Dependency Graph

To prevent foreign key violations, orphaned records, and deadlocks, the simulation must execute strictly in this topological sequence:

```text
PHASE 1: Foundation
  1. AdminPanelSetting
  2. Roles & Permissions (Spatie)
  3. Master Users (Admin / Owner)
  4. Branches (Flagship, Mall, Boutique)
  5. PaymentMethods (Cash, Credit Card, Bank Transfer)
  6. ExpenseTypes (Rent, Utilities, Maintenance, Supplies, etc.)
  7. Units (Piece, Bottle, Box, ml, g)
  8. Tools (Dryers, Scissors, Irons, Sterilizers)

PHASE 2: Catalog Master Data
  9. ServiceCategories (Hair, Nails, Skin, Massage, Grooming)
  10. ProductCategoryCategories (Hair Care, Skin Care, Tools, Consumables)
  11. Suppliers (L'Oréal Pro, Schwarzkopf, Kerastase, Salon Essentials)
  12. EmployeeLevels (Junior, Senior, Master, Specialist)

PHASE 3: People & Locations
  13. Employees (Staff across 3 branches)
  14. EmployeeWages (Schedules, basic salary, hours)
  15. User Accounts for Employees (Managers, Cashiers, Receptionists, Providers)
  16. Link Branch Managers (Update branches.manager_id)
  17. Inventories (1 warehouse per branch)
  18. Customers (1,200+ segmented clients)

PHASE 4: Services & Products Mapping
  19. Services (Prices, duration, branch association)
  20. ServiceEmployees Pivot (Commission percentage / value per provider)
  21. ServiceProducts Pivot (Consumables needed per service)
  22. Products (Retail items and backbar consumables)

PHASE 5: Inventory Initialization
  23. Initial PurchaseInvoices & Details (Historical restock from suppliers)
  24. SupplierPrices (FIFO cost lots)
  25. InventoryProducts (Warehouse stock balances)
  26. SupplierTransactions (Purchasing ledger)
  27. Initial Stock InventoryTransactions

PHASE 6: Customer Financial Initialization
  28. Initial Customer Deposits (CustomerTransactions with available balances)

PHASE 7: Historical 12-Month Timeline Simulation (Looping Monthly/Weekly/Daily)
  29. Appointments (Scheduled times, realistic provider allocations, status progressions)
  30. SalesInvoices & Details (Appointment conversions, retail sales, split tenders, deposits)
  31. Stock Deductions (Retail FIFO allocation & backbar service consumption)
  32. Voided Invoices (Controlled cancellation within 24h window)
  33. Refunds & Details (Product returns, cash/deposit refunds, stock restoral)
  34. Stock Adjustments (Periodic inventory counts, damage, waste write-offs)
  35. Operating Expenses (Monthly rent, utilities, weekly supplies, maintenance)

PHASE 8: Current Operating State (Today & Near-Future)
  36. Today's Appointments (Requested, Confirmed, Checked-in, In-Service, Completed)
  37. Today's POS Invoices & Cashier Transactions
  38. Upcoming 14-Day Confirmed Bookings
  39. Low-Stock & Out-of-Stock Products (Triggering dashboard inventory alerts)

PHASE 9: Data Integrity & Consistency Validation
  40. Execute SimulationValidator (Asserting zero orphan rows, balanced ledgers, non-conflicting schedules)
```
