# Payroll Settings Easy UI Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make Payroll Settings easier by adding a default Quick Setup landing page with clear cards and actions into existing settings sections.

**Architecture:** Keep payroll logic, routes, and existing detailed tabs unchanged. Add one new Blade partial for the Quick Setup dashboard, make it the default tab in `PayrollSettingController`, and update the tab navigation labels/order so non-technical admins can find setup areas quickly.

**Tech Stack:** Laravel 10, Blade views, existing Worksuite UI components, PHPUnit feature/view tests.

---

### Task 1: Add Quick Setup Tab

**Files:**
- Modify: `Modules/Payroll/Http/Controllers/PayrollSettingController.php`
- Modify: `Modules/Payroll/Resources/views/payroll-setting/index.blade.php`
- Create: `Modules/Payroll/Resources/views/payroll-setting/ajax/quick-setup.blade.php`
- Test: `tests/Feature/PayrollSettingsQuickSetupTest.php`

- [ ] Add a failing test that renders the Quick Setup partial and asserts it contains the setup cards.
- [ ] Run the focused test and verify it fails because the view does not exist.
- [ ] Add controller support for `tab=quick-setup` and make it the default active tab.
- [ ] Add the Quick Setup nav tab before advanced tabs.
- [ ] Create the Quick Setup partial with six action cards: Currency, Components, Groups, TDS, Payment Methods, Payslip Fields.
- [ ] Run the focused test and verify it passes.

### Task 2: Verify Existing Advanced Tabs Still Work

**Files:**
- Modify only if needed: `Modules/Payroll/Resources/views/payroll-setting/index.blade.php`

- [ ] Run syntax checks for edited PHP/Blade files.
- [ ] Run the new focused payroll UI test.
- [ ] Confirm no payroll calculation/controller logic changed.
