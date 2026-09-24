# Appointment Domain — Salon Appointment Manager

> Complete appointment lifecycle analysis, status machine design, scheduling rules gap analysis, and proposed domain model.

---

## Current State Summary

The appointment system is **structurally incomplete**. It stores bookings but cannot manage their lifecycle. The core problem: there is no `status` column in the `appointments` table.

```sql
-- Current schema
appointments:
  id              BIGINT UNSIGNED
  start_date      VARCHAR  ← stored as string, not datetime
  end_date        VARCHAR  ← stored as string, not datetime
  customer_id     FK → customers
  provider_id     FK → employees
  service_id      FK → services
  created_by      FK → users (nullable)
  updated_by      FK → users (nullable)
  created_at      TIMESTAMP
  updated_at      TIMESTAMP
```

**Critical missing columns:**
- `status` — no lifecycle tracking possible
- `branch_id` — no branch scoping
- `appointment_date` / proper datetime type — `start_date` is VARCHAR
- `notes` — no booking notes
- `source` — how was booking made?
- `cancellation_reason`
- `no_show_fee`
- `deposit_amount`

---

## Current Implementation Audit

### AppointmentController

| Method | Behavior | Issues |
|---|---|---|
| `index()` | Returns JSON of all appointments | No auth, no branch filter, no pagination |
| `create()` | Returns product creation view | Wrong view — copy-paste error (renders products form) |
| `store()` | Inserts appointment | No validation, no auth, no conflict check |
| `update()` | Updates appointment | Uses `$request->id` instead of route `{id}` — BUG-003 |
| `destroy()` | Deletes appointment | Hard delete, uses `$request->id` instead of route param |

### Route Security

```php
// routes/web.php — OUTSIDE auth middleware group
Route::resource('appointments', AppointmentController::class);
```

Any unauthenticated user can create, update, or delete any appointment. This is **CRITICAL** (confirmed in security-audit.md as SEC-001, SEC-009).

---

## Proposed Appointment Status Machine

```
                        ┌─────────────────────────────────────┐
                        │         APPOINTMENT CREATED         │
                        │           status: requested         │
                        └──────────────────┬──────────────────┘
                                           │
                    ┌──────────────────────┼──────────────────────┐
                    │                      │                      │
                    ▼                      ▼                      ▼
              [confirmed]             [rejected]             [expired]
           status: confirmed        status: rejected        status: expired
                    │                  (terminal)         (if past date,
                    │                                       no response)
        ┌───────────┼──────────────┐
        │           │              │
        ▼           ▼              ▼
  [arrived/       [cancelled]   [rescheduled]
  checked_in]    status: cancelled  → new appointment
  status: checked_in  (terminal)    created; original
        │                           set to rescheduled
        ▼
  [in_service]
  status: in_service
        │
        ▼
  [completed]       ← triggers: last_service update,
  status: completed    commission calculation,
   (terminal)          consumable deduction

  Alternatively from checked_in:
        ▼
  [no_show]
  status: no_show
   (terminal)       ← may trigger: no-show fee
```

### Status Definitions

| Status | Meaning | Who Sets It | Triggers |
|---|---|---|---|
| `requested` | Booking created, awaiting confirmation | System (on create) | — |
| `confirmed` | Business has confirmed the booking | Receptionist | Send confirmation to customer |
| `rejected` | Business cannot accommodate | Receptionist | Notify customer |
| `cancelled` | Customer or business cancelled | Receptionist / future: customer | Check deposit policy |
| `rescheduled` | Moved to new time | Receptionist | Creates new appointment, links back |
| `checked_in` | Customer has arrived | Receptionist | Notify provider |
| `in_service` | Service is being delivered | Provider / Receptionist | Start timer (future) |
| `completed` | Service finished, ready for checkout | Provider / System | Update `last_service`, trigger commission |
| `no_show` | Customer did not arrive | Receptionist (after threshold) | Apply no-show fee if configured |
| `expired` | Past appointment with no action taken | System (cron) | Log, do not bill |

---

## Scheduling Rules — Required

### 1. Double-Booking Prevention

**Rule:** A provider cannot have two confirmed/checked_in/in_service appointments overlapping in time.

```
Overlap condition:
  new.start_date < existing.end_date
  AND
  new.end_date > existing.start_date
  AND
  new.provider_id = existing.provider_id
  AND
  existing.status NOT IN ('cancelled', 'rejected', 'no_show', 'expired')
```

**Current state:** ❌ No check implemented.

---

### 2. Service Duration Enforcement

**Rule:** `appointment.end_date = appointment.start_date + service.duration` (in minutes)

`service.duration` is an `unsigned tinyint` (minutes). Currently the system allows arbitrary `end_date` entry — the duration field has no effect on booking.

**Current state:** ❌ Not enforced.

---

### 3. Buffer Time

**Rule:** Optional configurable buffer between appointments per provider (e.g., 10 minutes for cleanup).

**Current state:** ❌ No buffer concept. Not in schema.

---

### 4. Staff Working Hours

**Rule:** Appointments must fall within the provider's working hours.

`employee_wages.start_working_time` and `working_hours` exist in the schema. These are never used in appointment validation.

**Current state:** ❌ Not enforced.

---

### 5. Break Time

**Rule:** Providers cannot be booked during their break.

`employee_wages.break_time` and `break_duration_minutes` exist.

**Current state:** ❌ Not enforced.

---

### 6. Branch Scoping

**Rule:** An appointment must be at a specific branch. Customer, provider, and service must all be available at that branch.

**Current state:** ❌ No `branch_id` on appointment. Provider and service branch filters not applied.

---

### 7. Room / Resource Booking

**Rule:** Some services require a specific room or equipment (treatment room, massage table). Two services requiring the same room cannot overlap.

**Current state:** ❌ No room/resource model in system.

---

### 8. Holiday and Day-Off

**Rule:** Appointments cannot be created on public holidays or employee days-off.

**Current state:** ❌ No holiday or schedule management.

---

## Appointment → Invoice Linkage (Missing)

A fundamental gap: there is **no `appointment_id` field on `sales_invoices`**.

This means:
- When a customer checks out, there's no way to close the appointment automatically
- No analytics on appointment conversion rate (booked vs. completed)
- No pre-population of invoice from appointment (must manually re-select customer, service, provider)

**Required:** `sales_invoices.appointment_id` (nullable FK) — nullable because walk-ins don't come from appointments.

---

## Deposit at Booking (Missing)

**Industry standard:** Many businesses require a deposit at booking time to reduce no-shows.

**Current state:** Deposits exist on `customer_transactions` and can be applied at invoice. But there is no "deposit on booking" field on the appointment, and no deposit-required enforcement per service.

**Required additions:**
- `appointments.deposit_amount` — deposit collected at booking
- `services.requires_deposit` — flag to enforce deposit
- `services.deposit_amount` or `services.deposit_percentage` — minimum deposit required

---

## Cancellation Policy (Missing)

**Industry standard:** Cancellations within N hours of appointment time result in full or partial deposit forfeiture.

**Required:**
- System-level or per-service cancellation policy
- Trigger deposit retention on late cancellation
- Track `cancelled_at` timestamp on appointment

---

## Proposed Schema (Target State)

```sql
appointments:
  id
  status              ENUM('requested','confirmed','rejected','cancelled',
                           'rescheduled','checked_in','in_service',
                           'completed','no_show','expired')
  start_datetime      DATETIME     ← rename from start_date, proper type
  end_datetime        DATETIME     ← rename from end_date, proper type
  customer_id         FK → customers
  provider_id         FK → employees
  service_id          FK → services
  branch_id           FK → branches
  notes               TEXT NULL
  source              ENUM('walk_in','phone','online','receptionist') DEFAULT 'receptionist'
  deposit_amount      DECIMAL(10,2) DEFAULT 0
  cancellation_reason TEXT NULL
  cancelled_at        DATETIME NULL
  no_show_fee         DECIMAL(10,2) DEFAULT 0
  rescheduled_from_id FK → appointments (self-referential, nullable)
  sales_invoice_id    FK → sales_invoices (nullable) ← links to checkout
  created_by          FK → users
  updated_by          FK → users
  timestamps
```

---

## Impact Assessment

| Implementing Appointment Status | Impact |
|---|---|
| Enables branch manager appointment board | High |
| Enables no-show tracking and policy | High |
| Enables appointment conversion analytics | High |
| Enables automatic `last_service` update on complete | High |
| Enables pre-fill invoice from appointment | Medium |
| Enables cancellation policy enforcement | Medium |
| Enables provider notification on check-in | Medium |
