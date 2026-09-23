# Project AI Engineering Rules

## Mission

This repository contains an existing business management application for salons, spas, beauty businesses, and potentially clinics.

The objective is to improve reliability, business correctness, maintainability, security, and production readiness without destroying existing valid business behavior.

## Source of Truth

Product requirements:
`docs/product/PRODUCT_REQUIREMENTS.md`

Business rules:
`docs/product/business-rules.md`

System context:
`docs/ai/PROJECT_CONTEXT.md`

Architecture decisions:
`docs/ai/DECISIONS.md`

Progress:
`docs/ai/PROGRESS.md`

## Investigation Rule

Never make claims about code that has not been inspected.

Never assume behavior from:

* filenames
* class names
* variable names
* database column names
* comments
* documentation alone

Inspect the actual implementation.

## Change Rule

Before changing code:

1. Understand current behavior.
2. Understand intended behavior.
3. Identify the difference.
4. Identify affected dependencies.
5. Define tests.
6. Plan the change.

## Scope Rule

Do not perform unrelated refactors.

Do not introduce speculative abstractions.

Do not redesign architecture merely because another architecture appears cleaner.

Prefer the smallest change that correctly solves the real problem.

## Business Rule Safety

Business behavior is more important than code aesthetics.

Never silently change:

* prices
* payments
* refunds
* inventory quantities
* appointments
* commissions
* expenses
* branch access
* financial records

without explicit requirements.

## Data Integrity

Treat financial, inventory, appointment, and historical records as high-risk data.

Consider:

* database transactions
* concurrency
* race conditions
* idempotency
* foreign keys
* indexes
* auditability
* historical integrity

## Authorization

Authorization must be enforced server-side.

UI visibility is not authorization.

Branch filtering in the UI is not branch isolation.

## Testing

Never remove or weaken a test only to make the test suite pass.

Every meaningful behavior change should have appropriate automated tests.

Tests should validate business behavior, not implementation details.

## Database Changes

Every migration must consider:

* existing data
* rollback
* production safety
* indexes
* foreign keys
* nullable transitions
* deployment ordering

Never assume development data represents production data.

## Completion

A task is not complete until:

* implementation is finished
* tests are added/updated
* tests pass
* relevant static analysis passes
* important edge cases are verified
* the git diff is reviewed
* documentation is updated when necessary

## Uncertainty

When requirements are ambiguous:

Do not silently invent a rule.

Document the ambiguity and identify the decision required.

## Quality

Optimize for:

correctness
security
data integrity
maintainability
performance
simplicity

in that order unless explicit product requirements dictate otherwise.
