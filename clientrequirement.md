SOFTWARE REQUIREMENT SPECIFICATION
1. Introduction
1.1 Purpose
The purpose of this document is to define the functional and non-functional requirements of an integrated enterprise system covering HR Management, Daily Call Reporting (DCR), Invoicing, and Reporting for a pharmaceutical sales organization.
1.2 System Overview
The system will:
•	Manage employee lifecycle and payroll
•	Track field force activity through GPS-based DCR
•	Handle multi-level invoicing and inventory
•	Provide role-based, hierarchy-driven reports
The solution should be accessible via Web and Mobile (Android & IOS).
2. User Roles & Hierarchy
2.1 Role Hierarchy (Bottom → Top)
1.	Medical Representative
2.	Area Business Manager (ABM)
3.	Regional Manager (RM)
4.	Zonal Manager (ZM)
5.	Sales Manager
6.	PMT
7.	HR
8.	Admin
2.2 Access Control
•	Role-based access (RBAC)
•	Higher hierarchy can view lower hierarchy data
•	Lower hierarchy cannot view upper hierarchy data
•	Admin has full control
3. Module-Wise Functional Requirements
3.1 HR MODULE
3.1.1 Employee Onboarding
•	System shall generate unique Employee ID with prefix “RVB”
•	Capture employee master data:
o	Name
o	Designation
o	Department
o	Assigned HQ
o	Aadhaar Number
o	PAN Number
o	UAN Number
o	Date of Birth
o	Present Address
o	Permanent Address
o	Date of Joining
o	Employment Status (Probation / Confirmed / Resigned)
o	Other details also which are required for onboarding
o	Bank Name. Bank Account Number, IFSC Code, Branch Name
•	Assigned HQ will control:
o	DCR visibility
o	Area access
o	Reporting scope
3.1.2 Leave Management
Leave Types
1.	Casual Leave (CL)
o	Visible to all employees, count is visible but cannot be take until he is confirmed
o	Accrual: 0.8 CL per month
o	Calculated on pro-rata basis from joining date
2.	Earned Leave (EL)
o	Visible only to all employees, count is visible but cannot be take until he is confirmed
o	Accrual 1.5 EL per month
o	Calculated on pro-rata basis from joining date
o	Included in Full & Final Settlement
3.	Sick Leave (SL)
o	Everyone can take this leave 
o	Accrual 01 SL per month
o	Calculated on pro-rata basis from joining date


Leave Rules
•	Leave balance auto-calculated
•	Leave application approval flow:
o	Employee → Reporting Manager → HR
•	Leave Report:
o	As per given format
o	Export to Excel
o	Print option
3.1.3 Attendance Management
•	Office Employees
o	Manual / biometric / system clock-in clock-out
•	Field Employees
o	Attendance auto-marked only after DCR Close Day
o	Attendance date must match DCR date
o	No manual attendance allowed
o	Option to finalise monthly attendance than able to generate payroll for the particular month.
3.1.4 Payroll & Payslip
•	Payroll generation as per provided format
•	Also Give option to manually fill the heads while adding employee’s salary.
•	Payslip:
o	Monthly
o	Downloadable (PDF)
o	Printable
•	Payroll should consider:
o	Attendance
o	Leave
o	Deductions
o	Allowances
3.1.5 Full & Final Settlement
•	Applicable for resigned / terminated employees
•	Should calculate:
o	Salary payable
o	EL balance
o	Deductions
•	Generate Full & Final Statement in given format
3.2 DCR (DAILY CALL REPORTING) MODULE
3.2.1 Area Mapping
Area Structure:
Zone → Region → Area → HQ → Ex-Station / Out-Station
•	ABM & above:
o	Can add / edit / delete Ex-Station & Out-Station
•	HQ, Area, Region, Zone are admin-controlled masters
[Implementation note 3.2.1] Implemented: Full hierarchy (Zone → Region → Area → HQ → Ex/Out-Station) with PharmaZone, PharmaRegion, PharmaArea, PharmaHeadquarter, PharmaExstation, PharmaOutstation. Zone/Region/Area/HQ add-edit-delete require manage_area_assignments or add/edit/delete_* permissions (admin-controlled). Ex-Station and Out-Station add/edit/delete: allowed for users with designation Area Business Manager, Regional Business Manager, Zonal Manager, or Sales Manager (ABM & above), or when permission add_exstations/add_outstations (edit_*/delete_*) is granted. See PharmaAreaController::isABMOrAbove().
3.2.2 Area Visibility
•	Employees can view only assigned HQ, Ex-Stations, Out-Stations
•	Assignment is done during onboarding
[Implementation note 3.2.2] Implemented: AccessibleHeadquarters trait derives visible HQ/Ex/Out from employee_details (headquarter_id, areas, regions, zones). DCR and Doctor/Chemist/Stockist indexes restrict to this scope. Onboarding: Employee create/edit include Assigned HQ, Area(s) for ABM, Region(s) for RBM, and Zone(s) for Zonal Manager; all persisted to employee_details and used for visibility.
3.2.3 Doctor / Chemist / Stockist Master (DCR Purpose)
•	Medical Representative & above can:
o	Add Doctor, Chemist, Stockist in assigned areas
•	Area Manager & above:
o	Can edit / delete
•	Features:
o	HQ / Ex-Station / Out-Station wise filters
o	Bulk import (Excel)
•	Visibility:
o	Field staff see only assigned area data
[Implementation note 3.2.3] Implemented: Add Doctor/Chemist/Stockist restricted to assigned scope (add_doctors/add_chemists/add_stockists with all/added); edit/delete use edit_*/delete_* with scope check (assertHeadquarterAccessible). HQ / Ex-Station / Out-Station filters on index; Excel import (DoctorImport, ChemistImport, StockistImport) scoped to allowed HQs. Field staff see only records in accessible HQ/Ex/Out. Align roles so MR & above have add permission and Area Manager & above have edit/delete.
3.2.4 Tour Plan
•	Monthly tour plan submission by field employees
•	Workflow:
o	Submit → Auto-send to Reporting Manager → Approval
•	Reporting Manager:
o	Can fully edit before approval
•	After approval:
o	Tour plan becomes locked
o	Shown on employee dashboard as Approved
•	Rules:
o	Auto-lock on 25th of every month for next month
o	Admin can unlock any time
[Implementation note 3.2.4] Implemented: Monthly tour plan submission by field employees (TourController store, Tour model). Submit is only to Reporting Manager (employee_details.reporting_to); TourPlanSubmitted notification sent on first tour created. Reporting Manager (or admin) can edit before approval (edit/update blocked only when tour is approved). Approval via approve/approveAll sets approved, status = approved; TourPlanApproved notification. After approval tour is locked (only admin can edit). Tour index and create view show status as Approved (status-indicator, tour-status-approved, approved count). Auto-lock: tour:lock-next-month command runs daily at 00:15; on 25th inserts next month into tour_month_locks; create/edit block non-admin when month locked. Admin can unlock via unlockMonth (removes tour_month_locks row).
3.2.5 Daily Call Reporting (DCR)
DCR Entry Rules
•	Reporting date defaults to last unreported date
•	Assigned HQ auto-filled
•	Fields:
o	Work Type (master-controlled)
o	Worked Station (HQ / Ex / Out Station)
o	Worked With (hierarchy-based selectable)
Call Reporting
•	Multiple Doctor / Chemist / Stockist calls per day
•	Mandatory GPS geo-tagging
•	Call allowed only if employee is within 100 meters
•	Workflow:
o	Day Start
o	Auto-save each call with geo-tag
o	Close Day → submits DCR & marks attendance
Work Type Logic
•	If Work Type = Working Day
o	Doctor / Chemist / Stockist options enabled
•	Other work types:
o	Only Remarks required

[Implementation note 3.2.5] Met: Reporting date defaults to last unreported date (first pending approved-tour date); Assigned HQ auto-filled; Work Type from master (TourWorkStatus); Worked Station (HQ/Ex/Out); Worked With hierarchy; multiple Doctor/Chemist/Stockist calls; mandatory GPS; 100 m rule (config: dcr.enforce_gps_100m, dcr.max_distance_meters); Close Day submits DCR and marks attendance. Day Start = opening DCR create (default date + HQ). Work types that enable Doctor/Chemist/Stockist: Field Work, Working Day, Working Days (config: dcr.field_work_types). Auto-save: calls are saved with geo on Close Day submit (no per-call auto-save before submit).

3.2.6 DCR Visibility Rules
•	Representative can see:
o	Own DCR
•	Upper hierarchy can see lower hierarchy DCR
•	Visibility based on:
o	HQ
o	Area
o	Region
o	Zone

[Implementation note 3.2.6] Met: Representative sees own DCR (index includes user_id = current user). Upper hierarchy sees lower (RoleHierarchy::userIdsViewableBy + reporting employees). Visibility by HQ/Area/Region/Zone: AccessibleHeadquarters trait (areas, regions, zones on employee_details) drives accessible HQ IDs; DCR list filtered by creator’s headquarter in that set. Zone: zones column on employee_details; trait resolves zones to regions to areas to HQs. Direct-ID actions (destroy, approve, reject) enforce canViewDcr() so only in-scope DCRs can be deleted or approved/rejected.

3.2.7 Sales Plan
•	Entry level:
o	HQ-wise
o	Area-wise
o	Region-wise
•	Visible only to upper hierarchy
•	Not visible to lower hierarchy

[Implementation note 3.2.7] Met: Entry level HQ/Area/Region via plan_level and headquarter_id, area_id, region_id (SalesPlanTarget). Visible only to upper hierarchy: SalesPlanController and menu restrict to hierarchy level >= 2 or admin (MR = 1 cannot access). Not visible to lower hierarchy: same check (level < 2 aborts and menu hidden).

3.2.8 Sales & Stock Statement
•	Submitted by Medical Representatives
•	Mandatory for each assigned Stockist
•	Logic:
o	Opening Qty → Last month closing
o	Primary Qty → Auto from CFA to Stockiest invoice
o	Secondary Qty → Entered by MR
o	Closing Qty → Auto calculated
•	Auto consolidation:
o	HQ → Area → Region → Zone
•	Visible to all upper hierarchy

[Implementation note 3.2.8] Met: Submitted by MR (user_id on StockStatement; MR and others with dcr_reports module can submit). Mandatory per stockist: warning on index shows missing stockists for selected period with links to create (no block). Logic: Opening = last month closing (getOpeningQty from previous month closing_qty). Primary = auto from CFA/stockist invoice (CFAStockistStock linked to invoices in period). Secondary = MR entered (lines.secondary_qty). Closing = opening + primary + secondary. Auto consolidation: HQ → Area → Region → Zone filters and roll-up on consolidation page (StockStatementController::consolidation). Visible to upper hierarchy (index/consolidation scoped by RoleHierarchy + accessible HQ/area). Draft vs submitted: statements can be saved as draft or submitted; only draft is editable/deletable; consolidation and Target vs Achievement use only submitted statements. Target vs Achievement: report under Stock Statements (Sales Plan vs primary/secondary achievement), visible to upper hierarchy. UI: Index and consolidation filter bars aligned with Sales Plan/DCR (content-wrapper, action-bar, flex form, select-picker for stockist/HQ/Area/Region/Zone); selectpicker init; delete uses route so no 404. Gap: No hard enforcement that MR must submit for every assigned stockist per period (no mandatory block before month close).

3.2.9 Expense Management
•	Field employees submit expenses in given format
•	Expense workflow:
o	Employee → Reporting Manager → Approval
•	After approval:
o	Status shown as Approved
o	Linked with payroll (recommended)

[Implementation note 3.2.9] Met: Pharma format (day-wise rows, HQ, transport, allowances, vouchers); submit to manager (submitted_to); approve path (Approve Expenses page, approve/all); status shown as Approved (Expense Status page); linked with payroll (Include Expense Claims sums approved, claimable expenses). Gap addressed: Reject action added on Approve Expenses screen so reporting manager can reject with optional reason.

3.3 INVOICING MODULE
3.3.1 Product Master
•	Product Name
•	MRP
•	PTS
•	PTR
•	Tax
•	Discount
•	Status (Active / Inactive)

[Implementation note 3.3.1] Met: Product Name, MRP (column + form), PTS, PTR, Tax, Discount (and Discount Type flat/percentage), Status (Active/Inactive). All fields are in Product Master create/edit forms and persisted via ProductController; migration added mrp and status to products table; existing pharma columns ptr, pts, discount, discount_type are now fillable and saved.

3.3.2 Purchase Entry
•	Capture:
o	Batch No
o	Expiry Month & Year
o	MRP, PTS, PTR
o	Tax, Discount
•	Purchase entry must match supplier invoice
•	Stock update:
o	Batch-wise inventory

[Implementation note 3.3.2] Capture (Batch No, Expiry Month & Year, MRP, PTS, PTR, Tax, Discount) and batch-wise inventory were already in place. Implemented: (1) Optional "Supplier invoice total" on Purchase Entry create and edit-invoice forms; store passes/updates it on SupplierInvoice and match_status (matched/unmatched) is computed; after save, a short message is shown when a total was entered ("Entry total matches supplier invoice" / "Entry total does not match – please verify"). (2) Supplier Invoices: CTA "Add purchase entries for this invoice" on list (per-row dropdown) and on detail page, linking to Purchase Entry create with prefill (invoice number, vendor, date, supplier invoice total).

3.3.3 CFA / Distributor Management
•	Create CFA / Distributor with:
o	User ID & Password
o	Assigned HQ / Area
•	CFA can invoice only assigned HQ stockists

[Implementation note 3.3.3] Met: CFA/Distributor is created as a Client (User with role client) with email (login) and password; optional Area Allotment and Assigned HQ (client_areas and client_headquarters). Client create/edit forms include Area Allotment and Assigned HQ multi-select; ClientController syncs both. CFA Stockist master has Headquarter and Area dropdowns in create/edit forms; headquarter_id and area_id are saved. Enforcement: (1) When linking a stockist to CFAs (CFA Stockist store/update), server-side validation ensures each selected CFA has that stockist’s area in client_areas and headquarter in client_headquarters; otherwise an error is returned. (2) When creating a CFA→Stockist invoice, stockists are filtered by pivot and by CFA’s assigned HQ/Area (only stockists whose area_id or headquarter_id is in the CFA’s assigned set are shown). getStockistsForCFA AJAX applies the same HQ/Area filter so the dropdown only lists stockists within the CFA’s assignment.

3.3.4 Invoicing
Invoice Types
1.	IGST (Inter-State)
2.	CGST + SGST (Intra-State)
Features
•	Company → CFA/Distributor invoice
•	CFA/Distributor → Stockist invoice
•	Inventory auto-update
•	Multiple payment entries:
o	Amount
o	Date
o	Cheque / Reference No
o	Remarks
•	Credit note adjustment during invoicing
•	Discount and Scheme should dynamic in all invoice

[Implementation note 3.3.4] Met: Invoice types: IGST (Inter-State) and CGST+SGST (Intra-State) implemented via invoices.invoice_type and pharma/igst templates for both CFA distributor and CFA stockist. Company→CFA/Distributor invoice: cfa-distributor-invoices (create, store, edit, update, show); client = CFA/Distributor. CFA/Distributor→Stockist invoice: cfa-stockist-invoices; products from CFA distributor stock; stockist filtered by CFA assigned HQ/Area. Inventory auto-update: CFADistributorStock created on Company→CFA store/update; CFAStockistStock created on CFA→Stockist from selected distributor stock. Multiple payment entries: Payment model (amount, paid_on, gateway, transaction_id, remarks); payment modal for both invoice types with add/edit/delete; Amount, Date, Mode (e.g. Cheque/Bank), Reference, Remarks. Credit note adjustment: credit notes can be created from invoice (Add Credit Note on show); apply credit to invoices from Credit Notes module (applyToInvoice, applyInvoiceCredit); invoice show has Applied credits link. If “during invoicing” means from invoice create/edit screen, that is not implemented. Discount and Scheme dynamic: line-level scheme[] and discount on both CFA distributor and CFA stockist invoice create/edit; scheme format e.g. 20+2; JS recalculates totals and free qty.

3.3.5 Stockist (Invoice Purpose Only)
•	Separate from DCR stockist
•	Mapped to:
o	HQ
o	Area
o	Region
o	Zone

[Implementation note 3.3.5] Met: Invoice-purpose stockists are CFA Stockists (cfa_stockists, CFAStockist model), used only for CFA→Stockist invoicing. They are separate from DCR: DCR reports refer to Doctor/Chemist/Stockist (visit/coverage data), not the same entity. Mapped to HQ and Area directly (headquarter_id, area_id). Region and Zone are derived via hierarchy: Area→PharmaRegion (region_id on pharma_areas), Region→PharmaZone (zone_id on pharma_regions). So stockists can be filtered/reported by HQ, Area, Region, and Zone (Region/Zone via area->region->zone). No direct region_id/zone_id on cfa_stockists; add if reports need direct columns.

3.3.6 Credit Notes
•	Applicable at both levels:
o	Company → CFA
o	CFA → Stockist
•	Types:
1.	Saleable
2.	Non-Saleable
•	Unique credit note number like invoice

[Implementation note 3.3.6] Met: Credit notes apply at both levels: same Invoice model and CreditNotes model (credit_notes.invoice_id); Company→CFA and CFA→Stockist invoices are both in invoices table, so creating a credit note from an invoice (or from Credit Notes module with any invoice) works for both. Types: Saleable and Non-Saleable implemented (credit_note_type: saleable, non_saleable; migration, edit/convert forms, saleableVsNonSaleableReport). Unique credit note number: cn_number stored; display uses invoice setting credit_note_prefix and credit_note_digit (same pattern as invoice) via NumberFormat::creditNote(). Minor UX: “Add Credit Note” link exists on CFA Distributor invoice show (ajax/show); CFA Stockist invoice show (pharma-show) does not show this link—users can still create credit notes from Credit Notes module and select the stockist invoice.

3.3.7 Ledger
•	Company → CFA ledger
•	CFA → Stockist ledger
•	Date-wise and party-wise filters

[Implementation note 3.3.7] Met: Company→CFA Ledger and CFA→Stockist Ledger implemented. LedgerController: indexCFALedger / dataCFALedger (party = client_id, invoices via cfaDistributorStocks), indexCFAStockistLedger / dataCFAStockistLedger (party = cfa_stockist_id, invoices via cfa_stockist_stocks). Each ledger shows date-ordered rows: invoices (debit), payments (credit), credit notes (credit), with running balance; opening balance for date range. Filters: date range (daterangepicker) and party dropdown (CFA clients for Company→CFA, CFA Stockists for CFA→Stockist). Routes: cfa-ledger.index, cfa-ledger.data, cfa-stockist-ledger.index, cfa-stockist-ledger.data. Menu: Company → CFA Ledger and CFA → Stockist Ledger under CFA section; permissions reuse view_cfa_distributor_invoices and view_cfa_stockist_invoices.

3.4 REPORTING MODULE
Common Report Fields
All reports must display:
•	Employee Name
•	Employee ID
•	Designation
•	HQ
•	Date of Joining

[Implementation note 3.4] The standard set (Employee Name, Employee ID, Designation, HQ, Date of Joining, Department) is implemented for employee-based reports: on-screen columns added to Leave Report, Attendance Report, and Leave Quota Report DataTables; exports (Leave, Attendance, Leave Quota, Shift Schedule, Project Time Logs, Projectwise Time Log, Attendance by Member, Employee Leave Report) aligned to include these fields with shared lang keys; optional helper `App\Helper\ReportCommonFields` (headings() and mapEmployeeRow()) available for new exports.

HR Reports
•	Leave report 
•	Attendance report with Clock in and Clock out
•	Attendance Report with Present/Absent and also heads with total Working Days,SL,CL,EL, Holiday, Paid Days
•	Payroll & Payslip report
•	Full & Final report
(As per provided formats)
•	Increment Reports/ Appraisal Reports
Note:- All reports should have Emp code, Name, DOJ, Department, Designation and HQ

[Implementation note 3.4 HR Reports] Leave and Attendance (clock in/out) reports already have common fields (3.4). Implemented: (1) Attendance Summary export: "Export Excel (Present/Absent Summary)" on Attendance Report page; columns Employee Name, Employee ID, Designation, Department, DOJ, HQ, Working Days, SL, CL, EL, Holiday, Paid Days; Working Days = present + half-days; SL/CL/EL from approved leaves by type name; Paid Days = Working Days + approved paid leave days. (2) Payroll & Payslip: Salary Monthly and Salary Cumulative exports now include DOJ and HQ (pharma_headquarters), with standard column order and app lang keys. (3) Full & Final: statement PDF already shows Name, Employee ID, Designation, Department, HQ, DOJ. (4) Increment report: Payroll Reports → Salary Report tab has "Increment Report" download; export lists all increments with common fields plus Increment Date and Amount; filters by date range (month-year), department, designation. Provided formats: Layout/format for Payroll, F&F, and Increment reports will follow client-provided templates when supplied; until then, existing layouts are used with the standard common fields applied.
DCR Reports
•	Doctor / Chemist / Stockist:
o	Date-wise
o	HQ-wise
o	Area-wise
o	Region-wise
•	Call average analysis
•	Area performance reports
•	Target vs Achievement:
o	Based on invoicing (Primary)
o	Based on stock statement (Secondary)

[Implementation note 3.4 DCR Reports] Doctor/Chemist/Stockist listing with tabs (dcr-reports.index). Date-wise, HQ-wise, Area-wise and Region-wise filters implemented. Call average analysis (dcr-reports.call-average) and Area performance (dcr-reports.area-performance) implemented as new report pages. Target vs Achievement implemented (stock-statements.target-vs-achievement; Primary = invoicing, Secondary = stock statement).

Invoice Reports
•	Ledgers:
o	Company → CFA
o	CFA → Stockist
•	Reports:
o	CFA-wise
o	Stockist-wise
o	HQ / Area / Region-wise
o	Product-wise
o	Purchase & Sales (custom date)
•	Saleable vs Non-Saleable product report

[Implementation note 3.4 Invoice Reports] Ledgers: Company→CFA (cfa-ledger.index) and CFA→Stockist (cfa-stockist-ledger.index) implemented (see 3.3.7). Saleable vs Non-Saleable product report implemented (creditnotes.saleable-vs-non-saleable-report). Invoice report pages implemented: CFA-wise (reports.invoices.cfa-wise), Stockist-wise (reports.invoices.stockist-wise), HQ/Area/Region-wise (reports.invoices.hq-area-region-wise), Product-wise (reports.invoices.product-wise), and Purchase & Sales (reports.invoices.purchase-and-sales); all under Ledger menu and InvoiceReportController.

4. Additional (Recommended but Important)
•	Offline DCR with auto-sync
•	GPS enforcement
4.2 Notifications
•	Tour plan approval
•	DCR pending alerts
•	Expense approval
•	Payment due alerts
4.3 Security & Audit
•	Password policy
•	Login logs
•	Data edit audit trail
•	Role-based data restriction
4.4 Data Management
•	Excel / PDF export
•	Daily backup
•	Restore option
4.  Letter
•	Appointment Letter generation
•	Offer Letter generation
6. Increment Module
•	Option should be there to insert a increment amount with effect date.
•	Salary will be generated according to the amount as per the date of increment






