# Pharma CRM (RYVA) - Software Analysis Index

This documentation set provides a practical "full-system understanding" of the repository.

## Read Order

1. `docs/01-architecture-overview.md`  
   High-level system structure, framework, app layers, and where code lives.

2. `docs/02-domain-map.md`  
   Business-domain breakdown (CRM, pharma field-force, payroll, recruit, purchase, etc.).

3. `docs/03-operations-runbook.md`  
   Local setup, build/run commands, deployment, scheduler/queue, and operational caveats.

4. `docs/04-risk-hotspots.md`  
   Important technical risks, bug-prone areas, and recommended audit priorities.

## Scope and Intent

- This is an engineering understanding document, not end-user training material.
- It is written to help:
  - new developers onboard fast,
  - leads understand architecture boundaries,
  - reviewers find high-risk code quickly,
  - operators run the app safely.

## Important Context

- Main application root: `Pharma Crm RYVA/`
- Deployment mirror: `Pharma Crm RYVA/hostingercode/` (sync target for hosting)
- Additional nested project: `Pharma Crm RYVA/smhr/` (separate Laravel app)

Treat the root app (`Pharma Crm RYVA/`) as the canonical source for core development unless your team process says otherwise.
