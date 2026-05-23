# Domain Map and Feature Inventory

This file maps major business domains to their implementation areas.

## 1) CRM and Sales Core

### Scope

- Leads, deals, pipelines, follow-ups
- Clients and contacts
- Product/order flows
- Proposal and estimate workflows

### Primary code areas

- `app/Http/Controllers/*Lead*`, `*Deal*`, `*Client*`, `*Order*`, `*Product*`
- `app/Models/Lead*`, `Deal*`, `Client*`, `Order*`, `Product*`
- `resources/views/leads/`, `clients/`, `orders/`, `products/`
- `app/DataTables/*Lead*`, `*Deal*`, `*Client*`, `*Order*`

## 2) Finance and Billing

### Scope

- Invoices, recurring invoices, credit notes
- Payments and payment details
- Ledger/bank transaction tracking
- Supplier invoice flows

### Primary code areas

- Controllers: `InvoiceController`, `InvoiceReportController`, `PaymentController`, `CreditNoteController`, `LedgerController`, `SupplierInvoiceController`
- Models: `Invoice*`, `Payment*`, `CreditNotes`, `Bank*`, `SupplierInvoice*`
- Views: `resources/views/invoices/`, `payments/`, `credit-notes/`, `ledger/`
- Observers: `app/Observers/InvoiceObserver.php` (high side-effect surface)

## 3) Pharma Field Force Domain (Major Customization Area)

### Scope

- Doctor/chemist/stockist masters
- CFA distributor/stockist stocks and invoice workflows
- Pharma areas and territory mappings
- DCR, tours, sales plans, stock statements
- TP deviation and zero-sales analytics

### Primary code areas

- Controllers: `DoctorController`, `ChemistController`, `StockistController`, `CFAStockistController`, `PharmaAreaController`, `TourController`, `DcrReportController`, `SalesPlanController`, `StockStatementController`, `TpDeviationReportController`, `ZeroSalesReportController`
- Services: duplicate merges, area mapping sync, pharma reporting services
- Imports/exports/jobs: doctor/chemist/stockist/pharma expense pipelines
- Views: `resources/views/doctors/`, `chemists/`, `stockists/`, `cfa-*`, `pharma-areas/`, `dcr-reports/`, `tours/`, `reports/`

## 4) HR and Workforce

### Scope

- Employees, departments, designations
- Attendance and shift scheduling
- Leave lifecycle and quotas
- Employee lifecycle documents/details

### Primary code areas

- Controllers: `EmployeeController`, `AttendanceController`, `AttendanceReportController`, `LeaveController`, `LeaveReportController`, `EmployeeShift*`
- Models: employee, attendance, leave, shift entities
- DataTables/Exports: attendance and leave reporting classes

## 5) Payroll (Module)

### Scope

- Salary groups/components
- Employee salary and salary slips
- Payroll generation/status updates
- TDS settings and payroll reports
- Overtime policies and requests

### Primary code areas

- `Modules/Payroll/Http/Controllers/`
- `Modules/Payroll/Entities/`
- `Modules/Payroll/DataTables/`
- `Modules/Payroll/Exports/`
- `Modules/Payroll/Resources/views/`
- `Modules/Payroll/Routes/web.php`

## 6) Recruitment (Module)

### Scope

- Job postings and applications
- Interview scheduling
- Offer letter flows and recruit settings

### Primary code areas

- `Modules/Recruit/*`

## 7) Purchase and Vendor Management (Module)

### Scope

- Purchase orders and bills
- Vendor management/payments/credits
- Purchase inventory tracking

### Primary code areas

- `Modules/Purchase/*`

## 8) Letters and Templates (Module)

### Scope

- Company/recruit/HR letter templates
- Issued letters and previews/PDF output

### Primary code areas

- `Modules/Letter/*`

## 9) Additional Modules (Cross-cutting / optional)

- `Modules/Asset/` - asset tracking
- `Modules/Zoom/` - meetings/integration
- `Modules/EInvoice/` - e-invoice support
- `Modules/QRCode/` - QR generation
- `Modules/CyberSecurity/` - security controls/middleware
- `Modules/SFC/` - route/chart-oriented pharma analytics
- `Modules/UniversalBundle/` - bundle submodules (RestAPI/Webhooks/Sms/etc.)

## 10) Reporting Surface

The system has extensive report coverage using controllers + DataTables + exports:

- Financial reports
- Attendance and leave reports
- Deal/lead/project/time reports
- Payroll monthly/cumulative/increment reports
- Pharma operational reports (DCR, TP deviation, zero sales, stock)

## 11) Integration Hotspots

Important cross-cutting implementation patterns:

- Notifications (`app/Notifications` + module notifications)
- Observer-driven side effects (`app/Observers` and module observers)
- Excel import/export (bulk data workflows)
- Scheduled command automation (cron-dependent)

## 12) Domain Priority for Maintenance

If prioritizing review and stability, start in this order:

1. Finance + Payroll + Invoice observer side effects
2. Pharma field-force flows (high customization density)
3. HR attendance/leave dependencies
4. Purchase and Recruit module integrations
5. Secondary modules (QR, asset, e-invoice, etc.)
