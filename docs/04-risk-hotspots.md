# Risk Hotspots and Audit Priorities

This document captures important technical risk areas to guide code review, QA, and hardening.

## 1) High-Risk Hotspot Areas

## A. Payroll (`Modules/Payroll`)

- Complex salary math, overtime, TDS, and status transitions
- High chance of silent business-impacting miscalculation
- Multiple report/export code paths can diverge from source-of-truth salary data

Audit first:

- status transition permissions
- overtime policy + pay code calculation paths
- report export accuracy (monthly/cumulative)
- null handling and runtime safety in controllers

## B. Invoicing + Observer Side Effects

- `app/Observers/InvoiceObserver.php` is large and likely central to multiple side effects
- Observer-heavy design can trigger unexpected updates/notifications/inventory behavior

Audit first:

- all write paths touching invoices
- observer-triggered changes in related models
- idempotency (avoid duplicate side effects)

## C. Pharma Domain Customizations

- Deeply integrated custom domain with many controllers/reports/imports
- Large surface: doctor/chemist/stockist/CFA/tours/DCR/sales/stock flows
- Data consistency risk between masters, transactions, and reports

Audit first:

- master-data merge/deduplicate services
- DCR/tour data integrity
- stock and invoice report correctness
- import validation and rollback behavior

## D. Scheduled Commands and Queue-Dependent Features

- Many critical features rely on scheduler/queue execution
- If cron/queue is down, behavior silently degrades

Audit first:

- scheduler registration vs business expectations
- queue driver setup in env
- retries/error alerting/logging for command failures

## E. Duplicate Trees (`hostingercode/`) and Drift Risk

- Presence of parallel deploy tree increases risk of editing wrong folder
- Can cause “works locally but not on server copy” drift

Audit first:

- source-of-truth policy for development
- sync procedure enforcement
- deployment checklist consistency

## 2) Security and Access Control Focus

- Validate permission checks for all state-changing endpoints
- Watch for state-changing GET routes
- Ensure request validation for bulk/import/update endpoints
- Review module middleware alignment with core auth model
- Verify sensitive logs and env handling

## 3) Data Integrity Focus

- Finance (invoice/payment/ledger)
- Payroll (salary, overtime, TDS)
- Pharma stocks and transactional reports
- Bulk imports and migration assumptions

Key checks:

- transactional boundaries (`DB::transaction` where needed)
- race conditions in status updates
- duplicate prevention and uniqueness constraints
- consistent date/time/company scoping

## 4) Operational Resilience Focus

- Confirm backup and restore process is tested
- Validate log monitoring and alerting (not just log files)
- Ensure production queue/scheduler supervision is configured
- Verify cache/config clear-and-rebuild deployment sequence

## 5) Recommended Review Sequence

1. Payroll critical paths
2. Invoice observer and payment flows
3. Pharma reporting and stock/invoice integrations
4. Scheduler/queue health and command reliability
5. Permission coverage and unsafe route patterns
6. Deployment/sync process hardening

## 6) Definition of “Healthy” for this codebase

A stable release should satisfy:

- no unauthorized status changes in payroll/finance flows
- report outputs match underlying source transactions
- scheduler and queue are running and monitored
- no drift between canonical source and deploy mirror
- major workflows pass smoke tests:
  - create/update invoice + payment
  - generate payroll + mark paid
  - pharma transaction entry + report consistency
