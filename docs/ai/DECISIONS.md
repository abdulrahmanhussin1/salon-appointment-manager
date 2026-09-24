# DECISIONS — Salon Appointment Manager

> Records architectural and implementation decisions made during development.
> Every non-obvious decision must be recorded here so that future engineers and AI sessions understand WHY, not just WHAT.

**Format:**
```
## DEC-XXX: Short title
Date: YYYY-MM-DD
Requirement: REQ-XXX
Status: Accepted | Superseded | Deprecated
```

---

## How to Use This File

When implementing a requirement from `PRODUCT_REQUIREMENTS.md`:

1. If you make a decision that is **not obvious** from the requirements (e.g., choosing between two valid approaches, introducing a pattern, deferring something), record it here.
2. If a decision is later reversed or superseded, mark it as `Superseded` and link to the new decision.
3. Reference the decision ID in code comments where appropriate: `// See DEC-003`

---

## DEC-001: Appointment Status Added as Migration, Not Replaced

**Date:** _(to be filled when REQ-009 is implemented)_
**Requirement:** REQ-009
**Status:** Proposed

**Context:** The `appointments` table currently has no `status` column. Options:
1. Add a migration to add `status` to the existing table
2. Drop and recreate the table

**Decision:** Use `ALTER TABLE` via a new migration. Do not drop the table or recreate it. Existing appointment records will receive the default status `requested`.

**Reason:** The table may already contain data in any deployed environment. Dropping it would destroy booking history.

**Consequences:** Data migration needed to set `status = 'requested'` for all existing rows.

---

## DEC-002: Commission Stored at Invoice Line Level, Not Recalculated

**Date:** _(to be filled when REQ-012 is implemented)_
**Requirement:** REQ-012
**Status:** Proposed

**Context:** Commission could be:
1. Calculated live from `service_employees` at report time
2. Calculated and stored on `sales_invoice_details` at invoice creation time

**Decision:** Calculate and store `commission_amount` on `sales_invoice_details` at invoice creation time.

**Reason:**
- `service_employees` commission rates can change over time. If rates change, historical invoices should not be retroactively recalculated.
- Stored commission creates an immutable audit trail — the commission agreed to at time of service is preserved.
- Runtime recalculation would be slower and fragile.

**Consequences:** `commission_amount` on `sales_invoice_details` is a snapshot, not a live value.

---

## DEC-003: Invoice Void vs. Delete

**Date:** _(to be filled when REQ-015 is implemented)_
**Requirement:** REQ-015
**Status:** Proposed

**Context:** When an invoice needs to be cancelled, options:
1. Hard delete the record
2. Soft delete (deleted_at)
3. Set `status = 'voided'` with audit columns

**Decision:** Use `status = 'voided'` with `voided_by`, `voided_at`, `void_reason` columns. No soft delete, no hard delete.

**Reason:**
- Financial records must never be destroyed — immutable history requirement (NFR-002)
- Soft delete via `deleted_at` hides the record entirely; voided invoices should remain visible in the audit trail
- `status = 'voided'` is consistent with the existing status pattern on `sales_invoices`

**Consequences:** All report queries must explicitly exclude `status = 'voided'` (most already filter for `status = 'active'`).

---

## DEC-004: Branch Isolation Strategy

**Date:** _(to be filled when REQ-013 is implemented)_
**Requirement:** REQ-013, NFR-003
**Status:** Proposed

**Context:** Branch isolation can be enforced via:
1. Eloquent Global Scope on branch-scoped models
2. Middleware that injects `branch_id` into all queries
3. Manual per-controller filtering

**Decision:** Use manual per-controller filtering for Phase 2, with a clear pattern that can be promoted to Global Scope in a later phase.

**Reason:**
- Global Scope is powerful but invisible — it can cause subtle bugs (e.g., admin queries accidentally scoped)
- The codebase currently uses ad-hoc filtering; a consistent manual pattern is a smaller jump and easier to audit
- Can be promoted to Global Scope in Phase 4 once the pattern is proven

**Consequences:** Each report controller must be updated individually. Risk of missing one — must be caught in code review.

---

## DEC-005: Service Consumable Deduction — Non-Blocking on Insufficient Stock

**Date:** _(to be filled when REQ-014 is implemented)_
**Requirement:** REQ-014
**Status:** Proposed

**Context:** When a service is sold but there is insufficient consumable stock, options:
1. Block the sale entirely
2. Allow the sale but show a warning
3. Allow the sale silently

**Decision:** Allow the sale but show a warning. Do not block.

**Reason:**
- A salon cannot refuse to cut a customer's hair because the internal stock count is wrong
- Stock discrepancies are common in real operations (theft, waste, incorrect counts)
- The consumable deduction warning flags the issue for the inventory manager without harming the customer experience
- Behavior is configurable via `AdminPanelSetting` in a later phase

**Consequences:** Stock can go negative for consumables. Reports must handle negative stock gracefully.

---

## DEC-006: start_date / end_date Column Type Change

**Date:** _(to be filled when REQ-009 is implemented)_
**Requirement:** REQ-009, REQ-011
**Status:** Proposed

**Context:** `appointments.start_date` and `appointments.end_date` are currently `VARCHAR` columns, not `DATETIME`. Carbon parses them correctly in the controller, but:
1. Database cannot enforce ordering or comparison
2. Indexing for overlap checks is not possible on VARCHAR date strings

**Decision:** Rename to `start_datetime` and `end_datetime`, change type to `DATETIME`, via migration.

**Reason:**
- The overlap check required for double-booking prevention (REQ-010) uses `BETWEEN` / comparison — this is only reliable on proper DATETIME columns
- The current VARCHAR format ('Y-m-d H:i:s') is parseable but not semantically correct
- Migration can convert existing data: `STR_TO_DATE(start_date, '%Y-%m-%d %H:%i:%s')`

**Consequences:** Any code reading `$appointment->start_date` must be updated to `$appointment->start_datetime`.

---

## DEC-007: Appointment Authorization via Spatie Permissions and CheckRole Integration

**Date:** 2026-09-24
**Requirement:** REQ-001
**Status:** Accepted

**Context:** Appointment routes were previously completely outside the auth and checkRole middleware group. When moved inside `Route::prefix('admin')->middleware(['auth', 'verified', 'checkRole'])`:
1. The app uses `CheckRole` middleware which maps route names (`{resource}.{action}`) to Spatie permissions (`{resource}.create`, `{resource}.edit`, `{resource}.destroy`, `{resource}.index`).
2. Previously, no appointment permissions existed in the `permissions` table or `RolesAndPermissionsSeeder`.
3. If moved without seeding and migrating permissions, even administrators would receive 403 Forbidden.
4. The calendar view (`home.calender`) was also unauthenticated.

**Decision:**
1. Create a migration `2026_09_24_000001_add_appointment_permissions.php` to insert permissions: `appointments.index`, `appointments.show`, `appointments.create`, `appointments.edit`, `appointments.destroy`, and grant them to `admin` and `cashier` roles.
2. Update `RolesAndPermissionsSeeder` so fresh database seeds include them.
3. Move `appointments` resource and `calender` inside `Route::prefix('admin')->middleware(['auth', 'verified', 'checkRole'])`.
4. Add route matching redirect for legacy `/appointments` to redirect unauthenticated or external requests to `/admin/appointments` (which triggers auth redirect).
5. Update `CheckRole` middleware to authorize `home.calender` via `appointments.index`.

**Reason:**
- Preserves existing role-based access control paradigm consistently across all resources in the system.
- Avoids hardcoded role checks; uses permission granularity.
- Ensures zero downtime or 403 lockout for existing administrator and cashier accounts.

**Consequences:** Users must have the corresponding permission to manage appointments.

---
