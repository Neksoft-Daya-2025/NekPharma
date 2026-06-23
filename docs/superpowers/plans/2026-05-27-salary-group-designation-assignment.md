# Salary Group Designation Assignment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let payroll admins map salary groups to designations and assign matching existing employees from Payroll Settings.

**Architecture:** Add a small `salary_group_designations` mapping table and relationship on salary groups. Salary group create/edit modals manage designation mappings, while an explicit apply action assigns matching employees to the group without silently changing salaries.

**Tech Stack:** Laravel, Blade, Eloquent, PHPUnit source regression tests.

---

### Task 1: Add Designation Mapping Storage

**Files:**
- Create: `Modules/Payroll/Database/Migrations/2026_05_27_120000_create_salary_group_designations_table.php`
- Create: `Modules/Payroll/Entities/SalaryGroupDesignation.php`
- Modify: `Modules/Payroll/Entities/SalaryGroup.php`

- [ ] Add a migration for `salary_group_designations`.
- [ ] Add the Eloquent mapping model.
- [ ] Add salary group relationships for designation mappings and designations.

### Task 2: Manage Mappings In Salary Group Settings

**Files:**
- Modify: `Modules/Payroll/Http/Controllers/SalaryGroupController.php`
- Modify: `Modules/Payroll/Resources/views/payroll-setting/create-salary-group-modal.blade.php`
- Modify: `Modules/Payroll/Resources/views/payroll-setting/edit-salary-group-modal.blade.php`
- Modify: `Modules/Payroll/Resources/views/payroll-setting/ajax/salary-groups.blade.php`

- [ ] Load designations into create/edit modals.
- [ ] Persist selected designations when salary groups are stored or updated.
- [ ] Show mapped designations on the salary groups table.

### Task 3: Assign Matching Employees

**Files:**
- Modify: `Modules/Payroll/Http/Controllers/SalaryGroupController.php`
- Modify: `Modules/Payroll/Routes/web.php`
- Modify: `Modules/Payroll/Resources/views/payroll-setting/ajax/salary-groups.blade.php`
- Modify: `Modules/Payroll/Resources/views/payroll-setting/index.blade.php`

- [ ] Add a route and controller method to assign users whose designation matches the group mapping.
- [ ] Move matched users from any old salary group to this salary group.
- [ ] Add a dropdown action and JS handler to trigger assignment.

### Task 4: Regression Coverage

**Files:**
- Create: `tests/Unit/PayrollSalaryGroupDesignationAssignmentTest.php`

- [ ] Assert migration, controller, routes, and views expose the designation assignment workflow.
- [ ] Run the test before and after implementation.
