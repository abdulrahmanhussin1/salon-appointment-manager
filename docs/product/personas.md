# User Personas — Salon Appointment Manager

> Each persona describes: role definition, primary responsibilities, system touchpoints, daily workflows, current pain points, and what they need that the system doesn't yet deliver.

---

## P-01: Business Owner (Multi-Branch)

**Role:** Owner of one or more salon/spa branches. Non-technical. Focused on financials, staff performance, and growth.

**System Access:** Admin role. Access to all branches.

**Primary Responsibilities:**
- Review revenue performance across branches
- Monitor staff productivity and commissions
- Approve operational decisions (expenses, pricing changes)
- Assess whether each branch is profitable

**Daily Workflows:**
1. Check today's revenue vs target
2. Review which employees generated the most revenue
3. Monitor expenses vs income

**Current Pain Points:**
- Reports are not branch-filterable — cannot see individual branch performance
- Commission reports show revenue, not actual commissions — cannot make payroll decisions from the system
- No gross margin visibility (no COGS tracking)
- No KPIs: cancellation rate, average ticket, repeat customer rate

**Unmet Needs:**
- Branch-level P&L snapshot
- Staff commission report with actual calculated amounts
- Customer retention metrics
- Revenue vs. target comparison

---

## P-02: Branch Manager

**Role:** Manages day-to-day operations of a single branch. Reports to business owner.

**System Access:** Manager role. Should be restricted to own branch.

**Primary Responsibilities:**
- Oversee staff schedules and appointments
- Approve/manage expenses for their branch
- Monitor daily sales and inventory
- Handle customer escalations

**Daily Workflows:**
1. Review today's appointment schedule
2. Check stock levels for consumables
3. Reconcile daily cash with POS
4. Review staff service counts

**Current Pain Points:**
- No appointment status — cannot see which appointments are confirmed, running late, or no-shows
- Reports show all branches — no clean branch-filtered view
- Inventory consumable depletion not visible — must physically check stock
- No cash reconciliation tool
- Staff schedule/availability not managed in system

**Unmet Needs:**
- Branch-filtered dashboard
- Appointment status board (today's appointments, status per slot)
- Low-stock alerts
- Cash drawer reconciliation

---

## P-03: Receptionist

**Role:** Front desk. Books appointments, greets customers, manages schedule.

**System Access:** Limited — appointments, customer lookup, calendar.

**Primary Responsibilities:**
- Book and manage appointments
- Manage customer information
- Communicate schedule to service providers
- Handle reschedules and cancellations

**Daily Workflows:**
1. Open calendar view for the day
2. Book new appointments (customer selects service, provider, time)
3. Handle reschedule requests
4. Note no-shows and cancellations

**Current Pain Points:**
- Appointment has no status — cannot mark as confirmed, arrived, cancelled
- No overlap prevention — can accidentally double-book a provider
- Service duration is not auto-computed — must mentally calculate end time
- No customer notification (SMS/email) sent on booking
- No reminder system — customers forget appointments
- Cannot see customer visit history or balance from appointment screen

**Unmet Needs:**
- Appointment status lifecycle (requested → confirmed → arrived → in-service → completed)
- Double-booking prevention with clear error message
- Auto-compute end time from service duration
- Automated SMS/email reminder to customer
- Customer history visible on booking screen

---

## P-04: Cashier (POS Operator)

**Role:** Processes payments at the end of a customer's visit. Creates sales invoices.

**System Access:** Cashier role. Restricted to own branch's sales.

**Primary Responsibilities:**
- Create and submit sales invoices
- Accept payment (cash, card, deposit)
- Print or share receipts
- Handle invoice corrections

**Daily Workflows:**
1. Customer arrives at checkout
2. Select customer, add services performed + products sold
3. Apply any discounts or deposit
4. Accept payment
5. Print receipt

**Current Pain Points:**
- Cannot edit an invoice after submission — any error requires creating a new one
- Cannot cancel/void an invoice — inactive status is available but no workflow
- If service was at a custom price, `price_can_change` flag is ignored — system always uses base price
- Split payment across 3+ methods not supported (only cash + one non-cash)
- Draft invoices supported in schema but workflow is unclear
- No receipt send to customer (email/WhatsApp)

**Unmet Needs:**
- Invoice editing within a time window
- Invoice void/cancel with reason
- Respect `price_can_change` at POS
- Multi-method payment split
- Email/digital receipt delivery

---

## P-05: Service Provider / Stylist / Therapist

**Role:** Delivers services to customers. May also sell products.

**System Access:** Minimal — may only view their own schedule.

**Primary Responsibilities:**
- Deliver services as booked
- Track their own performance
- Prepare materials for service

**Daily Workflows:**
1. Check their day's appointment schedule
2. Prepare for each service
3. Notify when service is complete

**Current Pain Points:**
- No personal dashboard showing their own appointments for the day
- No way to see if appointment is confirmed or just tentative
- No commission visibility — cannot verify own earnings
- Service consumables not tracked — they pull products manually with no system record

**Unmet Needs:**
- Personal schedule view (today's appointments, status)
- Commission earnings visibility per period
- Service completion action (marks appointment done, triggers commission)

---

## P-06: Doctor (Clinic Mode — Anticipated)

**Role:** Medical professional delivering consultations or treatments in a clinic context.

**System Access:** Equivalent to Service Provider, possibly with additional patient data access.

**Current System Support:** Minimal — `Dr` salutation on customers, `outside_price` on services. No consultation, patient record, or clinical workflow.

**Unmet Needs (for clinic viability):**
- Patient medical notes per visit
- Prescription tracking
- Consultation vs. treatment distinction
- Clinical appointment types (requires prior visit, referral)

**Assessment:** Clinic mode is anticipated but not implemented. Should be treated as a future phase product decision.

---

## P-07: Inventory Employee / Storekeeper

**Role:** Manages product stock — receives deliveries, transfers stock between branches, monitors levels.

**System Access:** Inventory and purchase invoice access.

**Primary Responsibilities:**
- Receive supplier deliveries (purchase invoices)
- Transfer stock between branches
- Monitor stock levels
- Report damaged or missing stock

**Daily Workflows:**
1. Check current stock levels by product
2. Create purchase invoice on delivery
3. Transfer stock to another branch if needed
4. Flag low-stock products

**Current Pain Points:**
- No manual stock adjustment — cannot correct errors without faking a purchase or transfer
- No damaged/waste recording
- Service consumables never deducted — stock counts overstate real availability
- Inventory transfer silently fails if product doesn't exist in destination
- No low-stock alert or reorder point

**Unmet Needs:**
- Manual stock adjustment with reason and audit trail
- Damage / waste recording
- Automatic service consumable deduction
- Low-stock threshold alerts
- Purchase order / supplier request workflow

---

## P-08: Accountant / Finance User

**Role:** Reviews and reconciles all financial data. Responsible for reporting accuracy.

**System Access:** Read-only or report-level access to all financial data.

**Primary Responsibilities:**
- Verify daily cash and revenue figures
- Review expenses by category and branch
- Calculate staff commissions
- Prepare reports for owner/management

**Daily Workflows:**
1. Pull daily revenue report
2. Match cash on hand to system records
3. Review expenses submitted
4. Calculate pending commissions (currently done in Excel)

**Current Pain Points:**
- Commission report shows revenue, not commission — must manually calculate
- Expenses only partially visible in revenue report (cash-only filter)
- No COGS data — cannot calculate gross margin
- No branch-filtered reports — all branches mixed
- No invoice edit/void audit trail — no way to reconcile corrections
- Deposits are both "used in invoice" and shown separately — risk of double-counting

**Unmet Needs:**
- Commission report with calculated amounts per employee
- Expense report by category, branch, and payment method
- COGS against service/product revenue
- Branch-filtered P&L summary
- Receivables aging (outstanding balances from customers)
- Supplier payment tracking

---

## P-09: Customer

**Role:** Person receiving services or purchasing products. External to the system.

**System Interaction:** Indirect — a receptionist creates their profile. Customer has no login or self-service portal.

**Touchpoints:**
- Appointment booking (via receptionist)
- Service delivery
- Payment and receipt

**Current Experience Gaps:**
- No appointment confirmation sent
- No reminder before visit
- No digital receipt sent after payment
- No loyalty program or rewards
- No self-service booking
- No visibility into deposit balance
- No communication history

**Unmet Needs:**
- Appointment confirmation (SMS/WhatsApp/email)
- Pre-visit reminder (24h before)
- Digital receipt
- Online self-booking portal (future)
- Loyalty/points visibility

---

## Persona Summary Matrix

| Persona | Primary Module | Critical Missing Feature |
|---|---|---|
| Business Owner | Reports | Branch P&L, real commission report |
| Branch Manager | Dashboard / Calendar | Branch-filtered views, appointment status |
| Receptionist | Calendar / Appointments | Status lifecycle, conflict prevention, notifications |
| Cashier | POS | Invoice edit/void, price_can_change enforcement |
| Service Provider | Schedule | Personal view, commission visibility |
| Doctor (future) | Clinical records | Not implemented |
| Inventory Employee | Inventory | Manual adjustment, consumable deduction |
| Accountant | Reports | Commission calc, COGS, branch P&L |
| Customer | (External) | Notifications, self-booking |
