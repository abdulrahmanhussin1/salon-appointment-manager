# Domain Model — Salon Appointment Manager

This document describes every major entity in the system: its purpose, fields, relationships, lifecycle, ownership, branch scope, status, invariants, soft deletion behavior, and auditability.

---

## 1. User

**Purpose:** System login account. Every person who logs in to the admin panel is a User.

**Important Fields:**
- `name`, `email` (unique), `password`
- `photo` — stored path (nullable)
- `status` — `active | inactive`
- `employee_id` — optional link to an Employee record
- `created_by`, `updated_by`

**Relationships:**
- `belongsTo(Employee)` via `employee_id` — a user may be linked to an employee
- Spatie role/permission tables via `HasRoles` trait

**Lifecycle:** Created by an admin. Registration is disabled for self-sign-up.

**Ownership:** System-level, not branch-scoped directly. Branch scope is derived through linked Employee.

**Status:** `active | inactive`

**Soft Deletion:** Not implemented. `User::destroy()` hard-deletes + revokes roles/permissions.

**Auditability:** `created_by`, `updated_by` tracked via `HasUserActions` trait.

**Invariants:**
- Email must be unique
- `created_by` is NOT NULL and references `users.id` — first user must seed themselves
- A user can have zero or one linked employee

---

## 2. Employee

**Purpose:** A staff member who provides services, is assigned to a branch, and earns commissions.

**Important Fields:**
- `name`, `email` (unique, nullable), `phone` (unique, nullable), `national_id` (unique, nullable)
- `photo`, `id_card` — file paths
- `hiring_date`, `dob`, `termination_date`
- `finger_print_code` (unique, nullable)
- `job_title`, `gender`, `status` (`active | inactive`)
- `inactive_reason`
- `employee_level_id`, `branch_id`
- `created_by`, `updated_by`

**Relationships:**
- `belongsTo(EmployeeLevel)`
- `belongsTo(Branch)`
- `hasOne(User, 'employee_id')` — reverse link to login account
- `belongsToMany(Service)` via `service_employees` with pivot: `commission_type`, `commission_value`, `is_immediate_commission`
- `hasMany(SalesInvoiceDetail, 'provider_id')` — services/products rendered

**Lifecycle:**
- Created by admin via `EmployeeController::store()`
- On `Employee::created` event (boot method): automatically creates an `EmployeeWage` record
- On update: wage record updated separately
- On destroy: files deleted, `EmployeeWage` and `ServiceEmployee` rows hard-deleted, then employee hard-deleted

**Ownership:** Branch-scoped via `branch_id`.

**Status:** `active | inactive`

**Soft Deletion:** NOT implemented. Hard delete.

**Auditability:** `created_by`, `updated_by` tracked.

**Invariants:**
- Destroying an employee also destroys their wage and service associations
- Employee boot creates EmployeeWage automatically — duplicates possible if `EmployeeController::store()` also explicitly creates one (confirmed duplication risk: both happen in the store() method)

---

## 3. EmployeeWage

**Purpose:** Stores salary and work schedule configuration for an Employee. One-to-one with Employee.

**Important Fields:**
- `employee_id`
- `salary_type` — `daily | weekly | monthly | commission`
- `basic_salary`, `bonus_salary`, `allowance1/2/3`, `total_salary`
- `working_hours`, `start_working_time`, `overtime_rate`
- `penalty_late_hour`, `penalty_absence_day`
- `sales_target_settings` — `no | total_sales | employee_daily_service`
- `break_time`, `break_duration_minutes`

**Relationships:** `belongsTo(Employee)`

**Lifecycle:** Auto-created on Employee creation (via model boot). Updated alongside employee.

**Invariants:** `cascadeOnDelete` — deleted when Employee is deleted.

---

## 4. EmployeeLevel

**Purpose:** A classification level for employees (e.g., junior, senior).

**Fields:** `name`, `status` (`active | inactive`)

---

## 5. Customer

**Purpose:** A person who receives services or buys products. May have a deposit balance.

**Important Fields:**
- `name`, `email` (unique, nullable), `phone` (unique, nullable)
- `salutation` — `Mr | Mrs | Ms | Dr | Eng`
- `gender` — `male | female`
- `status` — `active | inactive`
- `dob`, `address`, `notes`
- `is_vip` — boolean
- `last_service` — date (nullable; purpose: UNKNOWN — not updated programmatically in code reviewed)
- `added_from` — `online | referral | walk_in | advertisement | direct`
- `created_by`, `updated_by`

**Note:** `deposit` column was commented out in migration. Deposits are tracked in `customer_transactions`.

**Relationships:**
- `hasMany(CustomerTransaction)`
- `morphMany(InventoryTransaction, 'reference')` — unclear if actively used
- Method `getAvailableDepositAmount()` sums available `CustomerTransaction` amounts

**Lifecycle:** Created via `CustomerController::store()` (supports both AJAX and form submit). Also created inline during sales invoice creation (AJAX modal in POS).

**Branch Scope:** NOT branch-scoped at model level. Customers are global.

**Status:** `active | inactive`

**Soft Deletion:** NOT implemented.

**Auditability:** `created_by`, `updated_by` tracked.

---

## 6. CustomerTransaction

**Purpose:** Tracks customer deposit credits and their usage. Acts as a ledger for prepaid balances.

**Important Fields:**
- `customer_id`
- `reference_type`, `reference_id` — polymorphic; known values: `deposit`, `invoice`
- `amount` — positive = credit deposited, negative = deposit used in invoice
- `notes`
- `status` — `available | used`
- `used_in_transaction_id` — self-referencing FK pointing to the deposit record being used
- `created_by`, `updated_by`

**Relationships:**
- `belongsTo(Customer)`
- Self-referential via `used_in_transaction_id`

**Lifecycle:**
- Deposit created: when customer is created with initial deposit (AJAX), or presumably via `CustomerTransactionController`
- Deposit consumed: during `SalesInvoice::store()` when `deposit > 0` is submitted

**Invariants:**
- The `reference_type`/`reference_id` columns use `morphs()` but the morph relationship is not defined on the model — morphing is done manually via string values
- `reference_id = 0` is used as a placeholder for initial deposits (not a valid FK)

---

## 7. Branch

**Purpose:** A physical business location. Inventory, sales, and expenses are scoped to a branch.

**Important Fields:**
- `name`, `address`, `phone` (unique, nullable), `email` (unique, nullable)
- `status` — `active | inactive`
- `manager_id` — FK to `employees.id` (nullable, added via separate migration)
- `created_by`, `updated_by`

**Relationships:**
- `hasMany(Employee)`
- `hasOne(Inventory)` (inferred from `PurchaseInvoice::saveDetails()` which calls `$this->branch->inventory()->first()`)
- `hasMany(PurchaseInvoice)`

**Lifecycle:** Created by admin.

**Branch Scope:** Self. Branches are root-level entities.

**Invariants:**
- Branch ID 1 is used as a magic "all branches" sentinel in `HomePageController`
- Branch is required for services, products, employees, sales invoices, purchase invoices, expenses

---

## 8. Service

**Purpose:** A treatment or procedure offered to customers (haircut, massage, facial, etc.).

**Important Fields:**
- `name` (unique), `notes`
- `price` — standard price (decimal 10,2)
- `outside_price` — price for outside/home visit
- `duration` — unsigned tiny integer (minutes)
- `image` (nullable)
- `is_target` — boolean
- `price_can_change` — boolean flag allowing override at time of sale
- `status` — `active | inactive`
- `branch_id`, `service_category_id`
- `created_by`, `updated_by`

**Relationships:**
- `belongsTo(ServiceCategory)`
- `belongsTo(Branch)`
- `belongsToMany(Employee)` via `service_employees` — employees certified to deliver this service
- `belongsToMany(Tool)` via `service_tools` — tools required
- `belongsToMany(Product)` via `service_products` — products consumed

**Lifecycle:** Created/updated by admin. On update: all pivot associations deleted and recreated (sync pattern implemented manually).

**Branch Scope:** `branch_id` present but filtering by branch is not enforced in sales invoice create (all active services are listed globally).

**Status:** `active | inactive`

**Soft Deletion:** NOT implemented.

**Auditability:** `created_by`, `updated_by` tracked.

**Bug Found:** `Service` model uses `use HasUserActions;` twice:
```php
class Service extends Model
{    use HasFactory,HasUserActions;
    use HasUserActions;  // ← duplicate
```

---

## 9. ServiceCategory

**Purpose:** Categorizes services.

**Fields:** `name`, `status`

---

## 10. Tool

**Purpose:** Equipment required for service delivery (not tracked in inventory).

**Fields:** `name`, `status`, `created_by`

---

## 11. Product

**Purpose:** A physical item that can be sold to customers or used internally in service delivery.

**Important Fields:**
- `name`, `code` (unique, auto-incremented from 100001)
- `description`, `image`
- `category_id` (ProductCategory), `supplier_id`, `unit_id`
- `initial_quantity`
- `is_target` — boolean
- `price_can_change` — boolean
- `type` — `operation | sales`
- `status` — `active | inactive`
- `branch_id`
- `created_by`, `updated_by`

**Note:** Price columns (`supplier_price`, `customer_price`, `outside_price`) were **commented out** from the migration. Prices live in `supplier_prices` table.

**Relationships:**
- `belongsTo(ProductCategory, 'category_id')`
- `belongsTo(Unit)`
- `belongsTo(Supplier)`
- `hasMany(SupplierPrice, 'product_id')` — price history per purchase
- `hasMany(InventoryProduct)`

**Lifecycle:**
- Auto-generates `code` on `creating` event (boot): `lastProduct->code + 1` starting at 100001
- **Race condition risk** in code generation (not atomic)

**Inventory:** Tracked separately in `inventory_products`. The `initial_quantity` field in products is not actively used in inventory calculations.

**Soft Deletion:** NOT implemented.

---

## 12. SupplierPrice

**Purpose:** Records the price at which a product was purchased from a supplier in a specific purchase invoice. Also stores the customer_price set at time of purchase. Acts as FIFO price ledger.

**Important Fields:**
- `product_id`, `supplier_id`, `purchase_invoice_id`
- `supplier_price` — cost price
- `customer_price` — selling price at time of purchase
- `discount`
- `quantity` — remaining quantity at this price point

**Lifecycle:** Created when purchase invoice is saved. Quantity decremented when products are sold (via `allocateProductPrices` in SalesInvoiceController — FIFO by `created_at`).

---

## 13. Inventory

**Purpose:** A named stock location (warehouse/storeroom), typically one per branch.

**Important Fields:**
- `name`, `status`
- `branch_id`

**Relationships:**
- `belongsTo(Branch)`
- `hasMany(InventoryProduct)` — products and quantities in this inventory
- `morphMany(InventoryTransaction, 'reference')` — transactions involving this inventory

---

## 14. InventoryProduct

**Purpose:** Pivot table tracking quantity of each product in each inventory.

**Fields:** `inventory_id`, `product_id`, `quantity`

---

## 15. InventoryTransaction

**Purpose:** Records movement of products (purchase in, sales out, transfer between inventories).

**Important Fields:**
- `transaction_type` — `purchase | sales | transfer`
- `source_inventory_id` (nullable), `destination_inventory_id` (nullable)
- `total_before_discount`, `discount`, `net_total`
- `delivery_expense`, `other_expenses`, `added_value_tax`, `commercial_tax`

---

## 16. SalesInvoice

**Purpose:** The primary sales/POS transaction. Records what a customer purchased (services and/or products) in a single visit.

**Important Fields:**
- `invoice_date`, `status` (`active | inactive | draft`)
- `total_amount` (gross), `invoice_discount`, `invoice_tax`, `net_total`
- `invoice_deposit` — deposit amount used
- `paid_amount_cash`, `payment_method_id`, `payment_method_value`
- `balance_due` (signed decimal — can be negative if overpaid)
- `invoice_notes`
- `customer_id`, `branch_id`, `payment_method_id`
- `created_by`, `updated_by`

**Relationships:**
- `belongsTo(Customer)`
- `belongsTo(Branch)`
- `hasMany(SalesInvoiceDetail)`

**Lifecycle:**
1. Store: validate → lock customer → process items (FIFO inventory deduction for products) → create invoice + details → process deposit deduction if applicable
2. Update, Delete: **not implemented** (abort(404))
3. Receipt view available at `/admin/sales_invoices/invoice/{id}`

**Status:** `active | inactive | draft`

**Soft Deletion:** NOT implemented.

**Auditability:** `created_by`, `updated_by`.

**Invariants:**
- Customer must be active
- All items must have an employee as `provider_id`
- Inventory must have sufficient quantity for products
- `balance_due` can be negative (overpayment allowed)

---

## 17. SalesInvoiceDetail

**Purpose:** Line item of a SalesInvoice. Either a product or a service (mutually exclusive — one of `product_id` or `service_id` is null).

**Important Fields:**
- `sales_invoice_id`
- `product_id` (nullable) OR `service_id` (nullable)
- `provider_id` — FK to `employees.id`
- `quantity`, `customer_price`
- `discount` (percentage), `tax` (percentage)
- `subtotal`

---

## 18. PurchaseInvoice

**Purpose:** Records a purchase from a supplier. Updates inventory and supplier price history.

**Important Fields:**
- `supplier_id`, `branch_id`
- `invoice_number` (auto-incremented)
- `invoice_date`, `status` (`active | inactive`)
- `total_amount`, `invoice_discount`, `invoice_notes`
- `created_by`, `updated_by`

**Lifecycle:**
1. Created with details
2. `saveDetails()` method on model: creates `InventoryTransaction`, updates/inserts `InventoryProduct`, creates `InventoryTransactionDetail`, creates `SupplierPrice` per product
3. Creates `SupplierTransaction` record

**Soft Deletion:** NOT implemented. Destroy returns 404.

---

## 19. Supplier

**Purpose:** A vendor that supplies products.

**Fields:** `name`, `email`, `phone`, `address`, `status`, `created_by`, `updated_by`

---

## 20. SupplierTransaction

**Purpose:** Tracks financial transactions with a supplier (purchases, payments).

**Fields:** `supplier_id`, `reference_id`, `reference_type` (`purchase`), `amount`, `notes`

---

## 21. Expense

**Purpose:** Records an operational expense.

**Important Fields:**
- `expense_type_id`, `payment_method_id`, `branch_id`
- `description`, `amount`, `paid_amount`, `balance`
- `paid_at` (timestamp), `invoice_number`, `status` (`active | inactive`)
- `created_by`, `updated_by`

**Note:** `amount` vs `paid_amount` vs `balance` suggests partial payment support, but the implementation of partial payment flow is UNKNOWN.

---

## 22. Appointment

**Purpose:** A booking linking a customer, an employee (provider), and a service, with a time window.

**Important Fields:**
- `customer_id`, `provider_id` (Employee), `service_id`
- `start_date`, `end_date` — stored as **string** (not datetime), parsed by Carbon on write
- `status` — NOT present in the schema (no status column in appointments table)
- `created_by`, `updated_by`

**Lifecycle:** Created/updated via `AppointmentController`. No status transitions defined (no completed, cancelled, no-show states).

**Branch Scope:** NOT present.

**Authorization:** Appointment routes (`Route::resource('appointments', ...)`) are placed **outside** the `auth` + `checkRole` middleware group — accessible without authentication.

**Soft Deletion:** NOT implemented.

**Invariants:**
- No validation in store/update (raw `Request` used, no Form Request)
- No appointment status column — cannot track completion, cancellation, no-show

---

## 23. AdminPanelSetting

**Purpose:** System-wide configuration (app name, logo, etc.) shared to all Blade views via `AppServiceProvider`.

**Lifecycle:** Single row, updated via settings page.

---

## 24. PaymentMethod

**Purpose:** Lookup table for payment types (cash, credit card, etc.).

**Fields:** `name`, `status`

**Special:** Cash is filtered out from payment method dropdowns in sales invoice creation (non-cash methods listed); cash is handled via separate `paid_amount_cash` field.

---

## 25. Role / Permission

**Purpose:** Spatie roles and permissions for RBAC.

**Custom Fields Added to Spatie Tables:**
- `permissions.group` — permission grouping
- `permissions.status` — `active | inactive | draft`
- `roles.status` — `active | inactive | draft`
- `roles.description`
- `roles.created_by`, `roles.updated_by`

---

## Entity Relationship Summary

```
Branch (1) ─── (N) Employee
Branch (1) ─── (1) Inventory
Branch (1) ─── (N) Service
Branch (1) ─── (N) Product
Branch (1) ─── (N) SalesInvoice
Branch (1) ─── (N) PurchaseInvoice
Branch (1) ─── (N) Expense
Employee (1) ─── (1) EmployeeWage
Employee (N) ─── (N) Service [service_employees, with commission]
Employee (N) ─── (N) Service [service_tools]
Service (N) ─── (N) Product [service_products]
Service (N) ─── (N) Tool [service_tools]
Customer (1) ─── (N) CustomerTransaction
Customer (1) ─── (N) SalesInvoice
SalesInvoice (1) ─── (N) SalesInvoiceDetail
SalesInvoiceDetail → Employee (provider_id)
SalesInvoiceDetail → Product OR Service
PurchaseInvoice (1) ─── (N) PurchaseInvoiceDetail
PurchaseInvoice (1) ─── (N) SupplierPrice (via saveDetails)
SupplierPrice → Product, Supplier, PurchaseInvoice
Inventory (1) ─── (N) InventoryProduct
InventoryProduct → Product
InventoryTransaction (N) ─── (N) InventoryProduct [via transfer/purchase logic]
Appointment → Customer, Employee (provider), Service
User → Employee (optional)
User ─── Role/Permission (spatie)
```
