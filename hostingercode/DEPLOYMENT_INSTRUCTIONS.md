# Deployment Instructions - Pharma CRM Updates

This document describes how to deploy the latest changes from the main application to your production/hosted environment (e.g., Hostinger).

## Summary of Changes

### 1. Duplicate Export Button Fixes
- **Attendance Report** (`/account/attendance-report`): Removed duplicate DataTable Excel button. Only "Export Excel (Present/Absent Summary)" remains.
- **Sales Report** (`/account/sales-report`): Removed duplicate DataTable Excel button. Custom Export Excel, PDF, CSV buttons remain.
- **DCR Report** (`/account/dcr-report`): Removed duplicate DataTable Excel button. Custom Export Excel, PDF, CSV buttons remain.
- **Zero Sales Report** (`/account/zero-sales-report`): New report; no duplicate (uses custom export buttons only).

### 2. New Zero Sales Report
- New report at `/account/zero-sales-report` showing HQ, Areas, Regions, or Stockists with zero sales in the selected period.
- Requires `view_zero_sales_report` permission (migration adds it for admin).

---

## Option A: Deploy from Main App (Recommended)

If your production app is a copy of the main project (not hostingercode):

### Step 1: Backup
```bash
# Backup your database
php artisan backup:run   # if you have backup package
# Or manually export your database

# Backup production files
cp -r /path/to/production/app /path/to/backup/app
```

### Step 2: Copy Updated Files
Copy these files from the main project to production:

**DataTables (duplicate export fixes):**
- `app/DataTables/AttendanceReportDataTable.php`
- `app/DataTables/SalesReportDataTable.php`
- `app/DataTables/DcrReportReportDataTable.php`
- `app/DataTables/ZeroSalesReportDataTable.php` (new)

**Controllers:**
- `app/Http/Controllers/ZeroSalesReportController.php` (new)
- `app/Http/Controllers/SalesReportController.php` (if using pharma-enhanced version)

**Services:**
- `app/Services/ZeroSalesReportService.php` (new)

**Exports:**
- `app/Exports/ZeroSalesReportExport.php` (new)
- `app/Exports/SalesReportExport.php` (if using pharma version)

**Views:**
- `resources/views/reports/zero-sales/index.blade.php` (new)
- `resources/views/reports/zero-sales/pdf.blade.php` (new)
- `resources/views/reports/sales/index.blade.php` (if using pharma version)

**Routes:** Update `routes/web.php` - add Zero Sales Report routes and Sales Report export routes (see main app).

**Menu:** Update `resources/views/sections/menu.blade.php` - add Zero Sales Report menu item and permission checks.

**Permissions:** Update `app/Models/Module.php`, `app/Helper/start.php`, `resources/lang/en/app.php`, `resources/lang/en/permissions.php`.

**Migration:**
- `database/migrations/2026_03_13_100000_add_view_zero_sales_report_permission.php`

### Step 3: Run Migration
```bash
cd /path/to/production
php artisan migrate --force
```

### Step 4: Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Step 5: Assign Permission
- Log in as admin.
- Go to **Settings > Roles & Permissions**.
- Ensure the **View Zero Sales Report** permission is assigned to roles that should access it (admin has it by default after migration).

---

## Option B: Deploy from Hostingercode Folder (Hostinger)

**For Hostinger deployment:** See [hostingercode/HOSTINGER_DEPLOYMENT.md](hostingercode/HOSTINGER_DEPLOYMENT.md) for step-by-step instructions when you're already running on Hostinger.

The `hostingercode` folder has been updated with:
- **Attendance Report**: Duplicate export button removed.
- **Zero Sales Report**: New report added (full feature parity with main app).

The hostingercode Sales Report uses a simpler payment-based structure and has no custom Export PDF/CSV buttons, so the DataTable Excel button was kept as the only export.

### Files Updated/Added in Hostingercode
- `app/DataTables/AttendanceReportDataTable.php` (duplicate export fix)
- **Leave Decimal Display Fix:**
  - `app/Http/Controllers/LeavesQuotaController.php` (employeeLeaveTypes formats leaves as whole numbers)
  - `resources/views/leaves/ajax/create.blade.php`
  - `resources/views/leaves/ajax/edit.blade.php`
  - `resources/views/employees/leaves_quota.blade.php`
- `app/DataTables/ZeroSalesReportDataTable.php` (new)
- `app/Http/Controllers/ZeroSalesReportController.php` (new)
- `app/Services/ZeroSalesReportService.php` (new)
- `app/Exports/ZeroSalesReportExport.php` (new)
- `app/Helper/AccessibleHeadquartersHelper.php` (new)
- `app/Traits/AccessibleHeadquarters.php` (modified: `accessibleHeadquarterIds` visibility)
- `resources/views/reports/zero-sales/index.blade.php` (new)
- `resources/views/reports/zero-sales/pdf.blade.php` (new)
- `routes/web.php` (Zero Sales Report routes)
- `resources/views/sections/menu.blade.php` (Zero Sales Report menu item)
- `app/Models/Module.php` (view_zero_sales_report permission)
- `app/Helper/start.php` (view_zero_sales_report in sidebar)
- `resources/lang/eng/app.php` (zeroSalesReport translation)
- `resources/lang/eng/permissions.php` (view_zero_sales_report translation)
- `database/migrations/2026_03_13_100000_add_view_zero_sales_report_permission.php` (new)

### To Deploy Hostingercode to Production

1. **Backup** your database and production files before deploying.

2. **Upload** the entire `hostingercode` folder contents to your server, overwriting existing files.
   - Or use FTP/SFTP: upload the changed files listed above.

3. **Run migration** (required for Zero Sales Report permission):
   ```bash
   cd /path/to/your/production
   php artisan migrate --force
   ```

4. **Clear cache**:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

5. **Verify**:
   - Visit `/account/attendance-report` - only one "Export Excel" button.
   - Visit `/account/zero-sales-report` - report loads with filters and Export Excel/PDF/CSV buttons (admin only by default).

---

## Option C: Full Sync (Main App → Hostingercode)

To make hostingercode match the main app (including Zero Sales Report and pharma Sales Report):

1. Copy all files listed in Option A from the main project into the hostingercode folder.
2. Ensure hostingercode has the pharma models and traits (PharmaHeadquarter, PharmaArea, PharmaRegion, CFAStockist, AccessibleHeadquarters, etc.).
3. Run migrations in the hostingercode database.
4. Deploy hostingercode to production.

---

## Post-Deployment Verification

1. **Attendance Report**: Visit `/account/attendance-report` - only one "Export Excel (Present/Absent Summary)" button.
2. **Sales Report**: Visit `/account/sales-report` - Export Excel, PDF, CSV buttons, no duplicate.
3. **DCR Report**: Visit `/account/dcr-report` - Export Excel, PDF, CSV buttons, no duplicate.
4. **Zero Sales Report**: Visit `/account/zero-sales-report` - report loads with filters and export buttons (admin only by default).

---

## Rollback

If issues occur:
1. Restore the backed-up files.
2. Run `php artisan migrate:rollback` if you need to undo the Zero Sales Report permission migration.
3. Clear cache: `php artisan cache:clear`
