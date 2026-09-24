# Inventory Domain — Salon Appointment Manager

> Full inventory lifecycle analysis: what is implemented, what is broken, what is missing, and how retail vs. consumable inventory should be managed.

---

## Inventory Architecture Overview

The system uses a **named inventory location** model:
- Each **Branch** has one **Inventory** (manually created — not auto-created with branch)
- An **Inventory** contains many **InventoryProduct** records (product × quantity pairs)
- Stock moves are tracked via **InventoryTransaction** + **InventoryTransactionDetail**
- Purchase pricing is tracked via **SupplierPrice** (also serves as FIFO price ledger for sales)

```
Supplier → PurchaseInvoice → PurchaseInvoiceDetail
                                    │
                              saveDetails()
                             /            \
                   InventoryProduct    SupplierPrice
                   (qty increment)     (FIFO price record)
                         │
               InventoryTransaction (type=purchase)
               InventoryTransactionDetail
```

---

## Current Lifecycle Implementation

### Purchase / Receive

**Trigger:** `POST /admin/purchase_invoices`

**Flow in `PurchaseInvoice::saveDetails()`:**
1. For each product in the purchase:
   - `UPSERT` into `inventory_products` — increment if exists, insert if not
   - Insert `PurchaseInvoiceDetail` record
   - Insert `InventoryTransactionDetail`
   - Insert `SupplierPrice` (cost price + customer_price at time of purchase)
2. Create single `InventoryTransaction` (type=`purchase`)
3. Create `SupplierTransaction` record

**Assessment:** ✅ Works correctly for inbound stock.

---

### Stock (Current Balance)

Tracked in `inventory_products.quantity` — one row per (product × inventory) pair.

**Retrieval:** `InventoryProduct::where('product_id', $id)->sum('quantity')` — aggregates across all inventories.

**Assessment:** ✅ Current balance visible. Per-inventory and cross-inventory totals work.

---

### Transfer (Branch-to-Branch)

**Trigger:** `POST /admin/inventory_transactions/transfer`

**Flow (`InventoryTransactionController`):**
1. Validate source has sufficient stock
2. `inventory_products.quantity -= amount` at source
3. `inventory_products.quantity += amount` at destination (via `increment()`)
4. Create `InventoryTransaction` (type=`transfer`) with source/destination

**Critical Bug (BUG-004):** `DB::table('inventory_products')->where(...)->increment('quantity', $qty)` — if the product row **doesn't exist** in the destination inventory, `increment()` does nothing. The stock silently disappears from source without appearing in destination.

**Assessment:** ⚠️ Works only if product already exists in destination. Silent failure otherwise.

---

### Sell / Deduct (Retail Sale)

**Trigger:** Invoice line item with `product_id` in `SalesInvoiceController::store()`

**Flow (`deductFromInventory()`):**
1. Fetch all `InventoryProduct` rows for this product with `quantity > 0`, ordered by `created_at ASC` (FIFO)
2. Decrement oldest batches first until quantity satisfied
3. Create `InventoryTransaction` (but with `total_before_discount=0`, `net_total=0`)
4. Create `InventoryTransactionDetail`

**Issues:**
1. `InventoryTransactionDetail` created with quantity but no cost linkage — COGS cannot be computed from transaction history
2. `InventoryTransaction.source_inventory_id` set only from first depleted inventory — multi-inventory deductions not tracked in header
3. Transaction amounts are always 0 — transaction log is incomplete

**Assessment:** ⚠️ Inventory deduction works. Historical cost tracking does not.

---

### Service Consumable Deduction (MISSING)

**Schema support:** `service_products` table — links a service to products it consumes with quantity per service.

**Current runtime behavior:** When a service line item is created in a sales invoice, `processService()` in `SalesInvoiceController` **ignores** `service_products` entirely. No consumable deduction occurs.

**Business impact:**
- Hair color, treatment creams, spa oils, medical consumables are never deducted
- Inventory stock for consumables overstates real availability
- Service cost of goods is zero in all reports
- Service profitability cannot be calculated

**Assessment:** ❌ Critical gap. Schema is ready, logic is missing.

---

### Manual Stock Adjustment (MISSING)

There is no UI or workflow for manually adjusting stock quantities.

**Workarounds used by staff:**
- Create a fake purchase invoice to add missing stock
- Create a fake transfer to move stock manually
- Neither creates an accurate audit trail

**Assessment:** ❌ Missing. Required for operational accuracy.

---

### Damaged / Waste Stock (MISSING)

No model or workflow for recording damaged or wasted products.

**Assessment:** ❌ Missing. Required for accurate stock and CoGS.

---

### Return to Supplier (MISSING)

No return workflow — once stock is received, it cannot be returned to supplier through the system.

**Assessment:** ❌ Missing.

---

## Retail vs. Consumable Distinction

The schema has `products.type = enum('operation', 'sales')`.

| Product Type | Meaning | Sell at POS | Auto-Deduct on Service | Current Status |
|---|---|---|---|---|
| `sales` | Retail product sold to customer | ✅ | N/A | ✅ Works |
| `operation` | Consumable used during service delivery | ❌ | ❌ Not implemented | ❌ Gap |

**The `type` field is stored but never used as a filter in the POS or inventory deduction logic.**

An `operation` type product can currently be sold at the POS (no filter prevents it) and is never deducted when a service uses it.

---

## Inventory Valuation

### Stock Report (`StockReportController`)
- Shows current quantity per product
- Cost = **latest** `supplier_price` — not FIFO cost
- This may misrepresent inventory value if purchase prices have changed

### Store Balance Report (`StoreBalanceReportController`)
- Shows beginning qty/value, in qty/value (purchases), out qty/value (sales), on-hand qty/value
- Only covers **current month** (hardcoded `startOfMonth()` / `endOfMonth()`)
- Cost for all calculations = latest supplier price — inconsistent with FIFO sales deduction
- Filter by `inventory_id` uses `branch_id` — column mismatch bug

---

## Inventory Domain Gap Summary

| Gap | Impact | Priority |
|---|---|---|
| Service consumable not deducted | Service CoGS invisible; stock inaccurate | **Must Have** |
| Manual stock adjustment | Staff workarounds corrupt data | **Must Have** |
| Transfer fails if product missing in destination | Silent stock loss between branches | **Must Have** |
| `operation` product type not enforced | Consumables sold at POS incorrectly | **Should Have** |
| Damaged / waste recording | Stock inaccurate; losses untracked | **Should Have** |
| Inventory valuation uses latest price not FIFO | Report values inconsistent with sales deduction | **Should Have** |
| Store balance report limited to current month | Cannot view historical balance | **Should Have** |
| Low-stock threshold / reorder alert | Manual monitoring required | **Could Have** |
| Supplier return workflow | Missing operational workflow | **Could Have** |
| Historical COGS per sale | Profitability analysis blocked | **Should Have** |

---

## Proposed Additions to Inventory Model

### 1. Service Consumable Deduction (in SalesInvoiceController)

When processing a service line item:
```
service → service_products → [product, quantity_per_service]
  → for each consumable: deductFromInventory(product_id, quantity * service_quantity)
```

This requires the same FIFO deduction logic already used for retail products.

### 2. Stock Adjustment Transaction Type

Add `adjustment` and `damage` to `inventory_transactions.transaction_type` enum:
```sql
transaction_type ENUM('purchase','sales','transfer','adjustment','damage','waste','return')
```

Create a dedicated adjustment UI: select inventory, product, quantity (+/-), reason, date.

### 3. Transfer Fix

Before `increment()`, check if row exists:
```php
InventoryProduct::firstOrCreate(
    ['inventory_id' => $destinationId, 'product_id' => $productId],
    ['quantity' => 0]
)->increment('quantity', $qty);
```

### 4. Low-Stock Threshold

Add to `products`:
```sql
reorder_point DECIMAL(10,2) DEFAULT 0
reorder_quantity DECIMAL(10,2) DEFAULT 0
```

Dashboard alert when `inventory_products.quantity <= products.reorder_point`.
