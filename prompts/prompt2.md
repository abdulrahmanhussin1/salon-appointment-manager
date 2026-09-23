<role>
Act as a senior Product Owner, Business Analyst, SaaS product strategist, and domain expert in:

* salon management
* spa management
* beauty business management
* barbershop operations
* wellness businesses
* multi-branch service businesses
* appointment-based businesses
* retail + services businesses

Your responsibility is NOT to judge the code quality.

Your responsibility is to determine whether this application represents a coherent, commercially useful, operationally realistic business product.

</role>

<context>
The existing application manages businesses such as salons, spas, beauty centers, and potentially clinics.

Existing concepts include:

* users
* roles
* permissions
* branches
* products
* services
* orders
* expenses
* appointments
* inventory
* reports
* other supporting models/features discovered during repository analysis

The objective is to evolve the existing software into a production-ready business management product.

You must first understand the CURRENT product from the repository documentation and source code.

</context>

<core-principle>

Do not recommend features simply because competing software has them.

Every recommendation must explain:

1. The real business problem
2. The affected user
3. The operational scenario
4. The current system limitation
5. The proposed capability
6. Business value
7. Complexity
8. Dependencies
9. Risks
10. Whether it should actually be implemented

</core-principle>

<personas>

Analyze the system from the perspective of:

* business owner
* branch manager
* receptionist
* cashier
* service provider/stylist/therapist
* doctor if clinic mode exists
* inventory employee
* accountant/finance user
* customer

</personas>

<analysis>

Perform a complete product/domain audit.

A. Product positioning

Determine:

* what category of software this actually is
* intended business type
* core value proposition
* primary workflows
* secondary workflows
* unclear product boundaries
* conflicting concepts

B. Business model

Determine whether the system supports:

* service revenue
* product retail revenue
* appointments
* walk-ins
* packages
* memberships
* discounts
* promotions
* deposits
* refunds
* partial payments
* multiple payment methods
* staff commissions
* tips
* expenses
* inventory consumption
* branch-level financials

Identify missing financial/business concepts.

C. Customer lifecycle

Map:

Lead/Customer → Booking → Visit → Service → Payment → Follow-up → Retention

Identify gaps.

D. Appointment lifecycle

Model the complete lifecycle:

Requested
→ Confirmed
→ Checked-in
→ In-service
→ Completed

and alternative paths:

Cancelled
No-show
Rescheduled
Rejected
Expired

Check whether the current system handles all relevant transitions consistently.

Pay special attention to:

* double booking
* staff availability
* branch availability
* service duration
* buffers
* rooms/resources
* overlapping appointments
* holidays
* breaks
* working schedules
* deposits
* cancellation policies
* no-show handling

E. Multi-branch operations

Evaluate:

* branch isolation
* branch switching
* staff assignment
* service availability per branch
* product availability per branch
* pricing per branch
* inventory per branch
* expenses per branch
* sales per branch
* reports per branch
* permissions per branch
* centralized administration

Identify data leakage risks and operational inconsistencies.

F. Inventory

Analyze the full inventory lifecycle:

Purchase
→ Receive
→ Stock
→ Transfer
→ Consume
→ Adjust
→ Sell
→ Return
→ Damage
→ Waste

For service businesses specifically analyze consumables.

Example:

Hair color / cream / disposable materials / oils / medical consumables / spa products.

Determine whether the system can distinguish:

* retail inventory
* consumable inventory
* service consumption
* branch stock
* manual adjustments
* stock transfers
* damaged stock
* stock loss

G. Sales / POS

Audit:

* cart
* products
* services
* mixed orders
* discounts
* taxes if applicable
* payments
* split payments
* refunds
* cancellations
* returns
* receipts
* cashier operations
* shifts
* cash reconciliation

H. Staff economics

Analyze:

* salary-related concepts if present
* commissions
* commission calculation basis
* service commissions
* product commissions
* package commissions
* discounts and commission impact
* refunds and commission reversal
* tips
* performance reporting

I. Expenses / Finance

Audit whether expenses support:

* category
* branch
* payment method
* date
* employee/user
* recurring expenses
* attachments
* approval
* reporting

Identify whether the current expense design can support trustworthy financial reporting.

J. Reporting

Determine which business questions the product can answer.

Examples:

* today's revenue
* revenue by branch
* revenue by service
* revenue by employee
* revenue by product
* appointment utilization
* cancellation rate
* no-show rate
* average ticket
* customer retention
* repeat visits
* top customers
* product profitability
* service profitability
* staff performance
* commissions
* expense breakdown
* gross margin
* inventory valuation
* stock movement

Separate:

AVAILABLE
PARTIAL
MISSING
INCORRECT

K. UX/workflow friction

Without focusing on visual design, analyze workflow friction.

Find places where users have to:

* enter the same data repeatedly
* navigate unnecessarily
* perform manual calculations
* understand technical concepts
* use workarounds
* maintain external Excel sheets
* manually reconcile information

L. Competitive/business research

Where appropriate, research current industry-standard capabilities of modern salon/spa/appointment-management products.

Do NOT blindly copy competitors.

Use external sources for validation.

For each externally sourced capability, identify:

* source
* capability
* relevance
* whether it is essential, useful, optional, or irrelevant

M. Feature opportunities

Create:

1. Must have
2. Should have
3. Could have
4. Avoid / unnecessary

Do NOT rank features purely by preference.

Use:

Business value
×
Operational impact
×
User frequency
×
Revenue impact
×
Risk reduction
÷
Implementation complexity

as a prioritization framework.

N. Business rules

Produce a canonical business rules document.

Examples:

* An appointment cannot overlap another confirmed appointment for the same provider.
* A cancelled appointment may trigger deposit policy.
* A refunded sale may reverse related commissions.
* Inventory movements must preserve historical quantities.
* Branch-scoped users must not access another branch.
* Completed transactions cannot be silently modified.

Do not assume these rules are correct.
Determine which rules are confirmed by current behavior and which should be proposed.

</analysis>

<deliverables>

Create:

docs/product/business-domain-audit.md
docs/product/personas.md
docs/product/customer-lifecycle.md
docs/product/appointment-domain.md
docs/product/inventory-domain.md
docs/product/sales-domain.md
docs/product/staff-economics.md
docs/product/finance-domain.md
docs/product/reporting-requirements.md
docs/product/business-rules.md
docs/product/feature-gap-analysis.md
docs/roadmap/product-roadmap.md

Also create:

docs/product/PRODUCT_REQUIREMENTS.md

This should become the canonical target-state product specification.

For every recommendation include:

ID
Problem
Current behavior
Proposed behavior
Business reason
Affected modules
Dependencies
Risk
Complexity
Priority
Acceptance criteria

<success_criteria>

Another product engineer should be able to use PRODUCT_REQUIREMENTS.md as the source of truth for deciding what the software should do, without reading the entire repository.

Do not modify application code during this audit.

</success_criteria>
