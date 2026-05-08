# SFC Module - Standard Fare Chart for Territories

## Overview
The SFC (Standard Fare Chart) module manages fare charts for territories, tracking travel expenses, doctor counts, business metrics, and related information for pharmaceutical territory management.

## Features
- Complete CRUD operations for SFC Charts
- Territory-based fare management
- Doctor count tracking (VIP and CORE)
- Business metrics tracking
- Stockist information management
- Travel mode and fare calculations

## Database Structure

### Tables
1. **sfc_settings** - Module settings and configuration
2. **sfc_charts** - Main fare chart data with all territory information

### Key Fields in sfc_charts
- Territory information (name, headquarter, covered from, town name)
- Distance metrics (one way KM, grace, total KM)
- Fare information (two way fare, one way fare)
- Travel details (mode of travel, time in hours, days monthly)
- Doctor counts (VIP, CORE, Total)
- Business metrics (current business, expected business)
- Stockist information
- Remarks

## Usage

### Activate Module
```bash
php artisan sfc:activate
```

### Access Routes
- List: `/account/sfc-charts`
- Create: `/account/sfc-charts/create`
- View: `/account/sfc-charts/{id}`
- Edit: `/account/sfc-charts/{id}/edit`

## Permissions
- `view_sfc_chart` - View SFC charts
- `add_sfc_chart` - Add new SFC charts
- `edit_sfc_chart` - Edit SFC charts
- `delete_sfc_chart` - Delete SFC charts

## Module Structure
```
Modules/SFC/
├── Config/
├── Console/
│   └── ActivateModuleCommand.php
├── Database/
│   └── Migrations/
├── DataTables/
│   └── SFCChartDataTable.php
├── Entities/
│   ├── SFCChart.php
│   └── SFCSetting.php
├── Http/
│   └── Controllers/
│       └── SFCController.php
├── Listeners/
│   └── CompanyCreatedListener.php
├── Providers/
│   ├── EventServiceProvider.php
│   ├── RouteServiceProvider.php
│   └── SFCServiceProvider.php
└── Resources/
    ├── lang/
    └── views/
```

## Based on Excel Format
This module is designed based on the SFC.xlsx template with the following columns:
1. SL (Serial Number)
2. COVERED FROM
3. NAME OF THE TOWN TO BE COVERED
4. ONE WAY KM (ACTUAL)
5. GRACE
6. TOTAL KM
7. TWO WAY FARE
8. ONE WAY FARE
9. EX-HQ / OS
10. MODE OF TRAVEL
11. TIME IN HOURS
12. NO OF DAYS (MONTHLY)
13. VIP DR (52)
14. CORE DR (48)
15. TOTAL
16. STOCKIST NAME
17. CURRENT BUSINESS
18. APPROX BUSINESS EXPECTED
19. REMARKS

## Version
1.0.0

