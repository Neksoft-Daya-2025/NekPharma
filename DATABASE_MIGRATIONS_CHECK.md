# Database Migrations Check

## Critical Migrations Required

### 1. ✅ `field_order` Column Migration
**File:** `2024_09_13_092310_add_field_order_column_in_order_items_table.php`

**Status:**
- ✅ **Local:** Exists in `database/migrations/`
- ✅ **Hostingercode:** Exists in `Hostingercode/database/migrations/`

**What it does:**
- Adds `field_order` column to `invoice_items` table
- Required for maintaining item order in invoices

---

### 2. ⚠️ **MISSING** - Pharma Fields Migration
**File:** `2025_12_20_140010_add_pharma_fields_to_invoice_items_table.php`

**Status:**
- ✅ **Local:** Exists in `database/migrations/`
- ❌ **Hostingercode:** **MISSING** - Not found in `Hostingercode/database/migrations/`

**What it does:**
- Adds `scheme`, `pack`, `mfr`, `batch`, `exp`, `mrp`, `pts`, `ptr`, `dis` columns
- **CRITICAL:** Adds `purchase_entry_id` column (required for duplicate detection)
- Links to `product_purchase_details` table via foreign key

**Impact if Missing:**
- ❌ `purchase_entry_id` column won't exist in `invoice_items` table
- ❌ Duplicate detection logic will fail
- ❌ Cleanup script won't work properly
- ❌ Pharma-specific fields won't be available

---

## Required Actions

### Action 1: Copy Pharma Fields Migration to Hostingercode
**File to copy:**
- `database/migrations/2025_12_20_140010_add_pharma_fields_to_invoice_items_table.php`
- **Destination:** `Hostingercode/database/migrations/2025_12_20_140010_add_pharma_fields_to_invoice_items_table.php`

### Action 2: Verify Other 2025 Migrations
Check if other 2025 migrations exist in Hostingercode:
- `2025_12_21_080259_create_c_f_a_distributor_stocks_table.php` (if exists locally)
- `2025_12_22_190338_add_lr_fields_to_invoices_table.php` (if exists locally)
- Any other 2025 migrations

---

## Migration Files Comparison

### Local Migrations (2025):
1. ✅ `2025_12_20_140010_add_pharma_fields_to_invoice_items_table.php` - **CRITICAL**
2. Check for other 2025 migrations

### Hostingercode Migrations (2025):
1. ❌ **MISSING** - Pharma fields migration
2. Check for other 2025 migrations

---

## Deployment Checklist

Before deploying Hostingercode to server:

1. ✅ Copy `2025_12_20_140010_add_pharma_fields_to_invoice_items_table.php` to Hostingercode
2. ✅ Verify all 2025 migrations are included
3. ✅ After deployment, run migrations: `php artisan migrate`
4. ✅ Verify `purchase_entry_id` column exists: `php artisan tinker` → `Schema::hasColumn('invoice_items', 'purchase_entry_id')`
5. ✅ Run cleanup script: `php fix_all_invoice_duplicates.php`

---

## SQL Check (After Deployment)

Run this SQL to verify columns exist:

```sql
-- Check if purchase_entry_id exists
SHOW COLUMNS FROM invoice_items LIKE 'purchase_entry_id';

-- Check if field_order exists
SHOW COLUMNS FROM invoice_items LIKE 'field_order';

-- Check all pharma columns
SHOW COLUMNS FROM invoice_items WHERE Field IN ('scheme', 'pack', 'mfr', 'batch', 'exp', 'mrp', 'pts', 'ptr', 'dis', 'purchase_entry_id', 'field_order');
```

---

## Summary

**Critical Issue:** ⚠️ **Pharma fields migration is missing from Hostingercode**

**Required:** Copy the migration file before deployment to ensure database schema matches the code.

