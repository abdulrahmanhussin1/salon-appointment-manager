# PROGRESS — Salon Appointment Manager

> Tracks implementation status of all requirements from `docs/product/PRODUCT_REQUIREMENTS.md`.
> Update this file at the end of every implementation session (Phase 6 of prompt3.md).

---

## Status Legend

| Symbol | Meaning |
|---|---|
| ⬜ | Not started |
| 🔄 | In progress |
| ✅ | Complete — merged, tested |
| ❌ | Blocked — see note |
| ⏭️ | Skipped / deferred — see note |

---

## Phase 0 — Stabilization (Bug Fixes & Security)

| ID | Requirement | Status | Notes |
|---|---|---|---|
| REQ-001 | Secure appointment routes (auth middleware) | ⬜ | |
| REQ-002 | Fix AppointmentController using `$request->id` instead of route param | ⬜ | |
| REQ-003 | Remove `dd()` from all catch blocks | ⬜ | Affects: EmployeeController, ServiceController, PurchaseInvoiceController |
| REQ-004 | Fix EmployeeWage duplicate creation (BUG-002) | ⬜ | |
| REQ-005 | Fix inventory transfer silent failure (BUG-004) | ⬜ | |
| REQ-006 | Fix `price_can_change` ignored in `processService()` (BUG-011) | ⬜ | |
| REQ-007 | Update `Customer.last_service` on invoice creation | ⬜ | |
| REQ-008 | Fix draft invoice incorrectly deducting inventory | ⬜ | Depends on draft → active transition flow |

---

## Phase 1 — Core Operations

| ID | Requirement | Status | Notes |
|---|---|---|---|
| REQ-009 | Appointment status lifecycle | ⬜ | Migration + status machine + UI |
| REQ-010 | Appointment double-booking prevention | ⬜ | Depends on REQ-009 |
| REQ-011 | Auto-compute appointment end time from service duration | ⬜ | Depends on REQ-009 |
| REQ-012 | Commission calculation at invoice creation | ⬜ | Migration + processService() + reports |

---

## Phase 2 — Financial Integrity

| ID | Requirement | Status | Notes |
|---|---|---|---|
| REQ-013 | Branch-filtered reports | ⬜ | All report controllers + views |
| REQ-014 | Service consumable inventory deduction | ⬜ | Depends on REQ-005 |
| REQ-015 | Invoice void / cancel workflow | ⬜ | New action + audit columns |
| REQ-016 | Fix daily revenue report expense calculation (cash-only filter) | ⬜ | One-line fix in ReportController |
| REQ-020 | Outstanding customer deposit report | ⬜ | New report view + query |

---

## Phase 3 — Service Completeness

| ID | Requirement | Status | Notes |
|---|---|---|---|
| REQ-017 | Appointment → Invoice linkage | ⬜ | Depends on REQ-009 |
| REQ-018 | Manual stock adjustment workflow | ⬜ | New UI + InventoryTransaction type |
| REQ-019 | Refund / return workflow | ⬜ | Depends on REQ-015, REQ-012 |

---

## Non-Functional Requirements

| ID | Requirement | Status | Notes |
|---|---|---|---|
| NFR-001 | Security — all routes auth-guarded, no dd(), debug=false | ⬜ | Covered by REQ-001–003 + deployment guidance |
| NFR-002 | Data integrity — transactions, no hard-delete on financial records | ⬜ | Ongoing; enforce per feature |
| NFR-003 | Branch isolation — server-side enforcement | ⬜ | Partially covered by REQ-013 |
| NFR-004 | Auditability — created_by/updated_by + void/refund audit | ⬜ | Covered per feature |
| NFR-005 | Reporting accuracy — correct commissions + expenses + branch filter | ⬜ | Covered by REQ-012, REQ-013, REQ-016 |

---

## Implementation Log

> Most recent first.

### 2026-09-24 — Product & Domain Audit Complete

- Completed full repository analysis (migrations, models, controllers, routes, existing docs)
- Created all 13 product documentation files under `docs/product/` and `docs/roadmap/`
- Created `docs/ai/PROGRESS.md` (this file) and `docs/ai/DECISIONS.md`
- No application code was modified
- All 20 requirements defined in `docs/product/PRODUCT_REQUIREMENTS.md`
- Roadmap defined in `docs/roadmap/product-roadmap.md`

**Ready to begin implementation with prompt3.md starting from Phase 0.**

---

## Dependency Graph

```
REQ-001 (auth routes)
  └→ REQ-002 (route param fix — needs auth first)
  └→ REQ-009 (appointment lifecycle — needs auth)

REQ-005 (transfer fix)
  └→ REQ-014 (consumable deduction — inventory must be safe first)

REQ-009 (appointment status)
  └→ REQ-010 (double-booking prevention)
  └→ REQ-011 (end time auto-compute)
  └→ REQ-017 (appointment → invoice link)

REQ-012 (commission calculation)
  └→ REQ-019 (refund reverses commissions)

REQ-015 (invoice void)
  └→ REQ-019 (refund builds on void concepts)
```

---

## Quick Stats

| Phase | Total | Done | In Progress | Blocked |
|---|---|---|---|---|
| Phase 0 | 8 | 0 | 0 | 0 |
| Phase 1 | 4 | 0 | 0 | 0 |
| Phase 2 | 5 | 0 | 0 | 0 |
| Phase 3 | 3 | 0 | 0 | 0 |
| **Total** | **20** | **0** | **0** | **0** |
