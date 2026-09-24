# Customer Lifecycle — Salon Appointment Manager

> Maps the full customer journey from acquisition through retention, identifying what the system currently supports vs. what is missing.

---

## Full Lifecycle Map

```
┌─────────────────────────────────────────────────────────────────┐
│  STAGE 1: ACQUISITION / LEAD                                    │
│                                                                 │
│  Customer is discovered or contacts the business                │
│                                                                 │
│  Sources tracked: online | referral | walk_in |                 │
│                   advertisement | direct                        │
│                                                                 │
│  ✅ `added_from` field on Customer model                        │
│  ❌ No lead pipeline / CRM funnel                               │
│  ❌ No online form or self-registration                         │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│  STAGE 2: CUSTOMER PROFILE CREATION                             │
│                                                                 │
│  Receptionist creates customer in system                        │
│                                                                 │
│  ✅ Name, phone, email, salutation, gender, DOB                 │
│  ✅ is_vip, added_from, notes, address                          │
│  ✅ Initial deposit at creation (via AJAX modal)                │
│  ❌ Photo / ID                                                  │
│  ❌ Preferences (preferred provider, service history notes)     │
│  ❌ Allergies / contraindications (clinic/spa critical)         │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│  STAGE 3: BOOKING                                               │
│                                                                 │
│  Receptionist books appointment via calendar                    │
│                                                                 │
│  ✅ Appointment created: customer + provider + service + time   │
│  ❌ No appointment status (no confirmation state)               │
│  ❌ No overlap/conflict check                                   │
│  ❌ No service duration auto-compute for end time               │
│  ❌ No confirmation sent to customer (SMS/email)                │
│  ❌ No deposit required at booking                              │
│  ❌ No cancellation policy enforced                             │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│  STAGE 4: PRE-VISIT                                             │
│                                                                 │
│  Customer prepares to arrive                                    │
│                                                                 │
│  ❌ No automated reminder (24h / 2h before)                     │
│  ❌ No two-way confirmation ("Reply YES to confirm")            │
│  ❌ No preparation instructions sent to customer                │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│  STAGE 5: CHECK-IN / ARRIVAL                                    │
│                                                                 │
│  Customer arrives at branch                                     │
│                                                                 │
│  ❌ No check-in action — appointment status doesn't exist       │
│  ❌ No arrival notification to provider                         │
│  ❌ No waiting list management                                  │
│  ❌ No estimated wait time                                      │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│  STAGE 6: SERVICE DELIVERY                                      │
│                                                                 │
│  Provider delivers the service                                  │
│                                                                 │
│  ❌ No "in-service" status on appointment                       │
│  ❌ Service consumables not deducted from inventory             │
│  ❌ No timer or service duration tracking                       │
│  ❌ No add-on services mid-visit workflow                       │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│  STAGE 7: PAYMENT / CHECKOUT                                    │
│                                                                 │
│  Cashier creates sales invoice at POS                           │
│                                                                 │
│  ✅ Multi-item invoice (services + products)                    │
│  ✅ Discount per item + per invoice                             │
│  ✅ Cash + non-cash split payment                               │
│  ✅ Customer deposit application (FIFO)                         │
│  ✅ Partial payment (balance_due)                               │
│  ✅ Receipt printable                                           │
│  ❌ No link from appointment to invoice (no appointment_id)     │
│  ❌ No refund / cancellation workflow                           │
│  ❌ No invoice editing after submission                         │
│  ❌ No receipt sent digitally to customer                       │
│  ❌ `price_can_change` flag ignored in service processing       │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│  STAGE 8: POST-VISIT FOLLOW-UP                                  │
│                                                                 │
│  Business communicates with customer after visit                │
│                                                                 │
│  ❌ No thank-you message                                        │
│  ❌ No review/feedback request                                  │
│  ❌ `Customer.last_service` field exists but is NEVER UPDATED   │
│  ❌ No next-appointment suggestion                              │
└──────────────────────────┬──────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│  STAGE 9: RETENTION                                             │
│                                                                 │
│  Business works to bring the customer back                      │
│                                                                 │
│  ❌ No loyalty points / rewards system                          │
│  ❌ No re-booking prompt                                        │
│  ❌ No birthday / anniversary automation                        │
│  ❌ Customer retention reports missing                          │
│  ❌ No segmentation (VIP, at-risk, lapsed)                      │
│  ✅ `is_vip` flag exists (manual tagging only)                  │
│  ✅ `dob` field exists (automation not wired)                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## Alternative Paths

### No-Show

```
Booked → [Customer does not arrive] → ???
```

**Current state:** Appointment stays in DB as-is. No status to mark no-show. No fee enforced. Provider's time is wasted with no record.

**Required:** Appointment status `no_show` + optional no-show fee applied to balance.

---

### Cancellation

```
Booked → [Customer cancels] → ???
```

**Current state:** Appointment is deleted (hard delete). No audit trail. No cancellation policy enforcement. No deposit returned or retained.

**Required:** Appointment status `cancelled` + cancellation reason + deposit policy check (retain deposit if within cancellation window).

---

### Walk-In (No Appointment)

```
Customer walks in → Cashier creates invoice directly
```

**Current state:** ✅ Fully supported — POS does not require an appointment.

---

### Deposit Refund

```
Customer paid deposit → Customer wants it back
```

**Current state:** ❌ No refund workflow. `CustomerTransaction` records cannot be reversed through any UI.

---

### Return / Product Return

```
Customer bought product → Returns it
```

**Current state:** ❌ No return workflow. Invoice cannot be cancelled or reversed.

---

## Lifecycle Gap Summary

| Gap | Business Impact | Priority |
|---|---|---|
| No appointment confirmation | Customer confusion, lost bookings | Must Have |
| No appointment reminders | High no-show rate | Must Have |
| Appointment → Invoice not linked | No visit analytics, no follow-up targeting | Must Have |
| `last_service` never updated | Cannot identify lapsed customers | Must Have |
| No cancellation workflow | Untracked lost revenue | Must Have |
| No no-show tracking | Cannot enforce no-show policies | Must Have |
| No post-visit communication | Missed retention opportunity | Should Have |
| No loyalty program | Lower retention vs. competitors | Could Have |
| No online self-booking | Higher receptionist load | Could Have |
| No deposit refund | Customer disputes | Should Have |
