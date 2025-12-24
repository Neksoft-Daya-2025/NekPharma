# Live Server Fixes Needed

## Issue Found
The live server code in `Hostingercode/` folder is **missing critical fixes** that prevent duplicate invoice items for CFA/Distributor invoices.

## Problems Identified

### 1. InvoiceObserver.php - Missing CFA/Distributor Check
**Location:** `Hostingercode/app/Observers/InvoiceObserver.php`

**Problem:** The `created()` and `updated()` methods are creating invoice items for ALL invoices, including CFA/Distributor invoices. This causes duplicates because:
- The Observer creates items without `purchase_entry_id`, `batch`, or `scheme`
- The Controller creates items WITH `purchase_entry_id`, `batch`, and `scheme`
- Result: Each product appears twice in the invoice

**Fix Required:** Add checks to skip CFA/Distributor invoices in both methods.

### 2. InvoiceController.php - May Be Missing CFA/Distributor Methods
**Location:** `Hostingercode/app/Http/Controllers/InvoiceController.php`

**Status:** Need to verify if CFA/Distributor invoice methods exist and have all the fixes.

## Files That Need to Be Updated

### File 1: `app/Observers/InvoiceObserver.php`

#### In `created()` method (around line 95):
Add this check at the beginning:

```php
public function created(Invoice $invoice)
{
    if (!isRunningInConsoleOrSeeding()) {
        // Skip item creation for CFA/Distributor invoices - they are handled by the controller
        // Check if this is a CFA/Distributor invoice by checking the route or request
        $isCFADistributorInvoice = request()->routeIs('cfa-distributor-invoices.store') 
            || request()->routeIs('cfa-distributor-invoices.update')
            || request()->has('invoice_type') && request()->invoice_type === 'cfa_distributor'
            || str_contains(request()->url(), 'cfa-distributor-invoices');
        
        if ($isCFADistributorInvoice) {
            // Skip observer processing for CFA/Distributor invoices
            // Items are created directly by InvoiceController
            return;
        }

        if (!empty(request()->item_name) && is_array(request()->item_name)) {
            // ... rest of existing code ...
```

#### In `updated()` method (around line 258):
Add this check at the beginning:

```php
public function updated(Invoice $invoice)
{
    if (!isRunningInConsoleOrSeeding()) {
        // Skip item updates for CFA/Distributor invoices - they are handled by the controller
        $isCFADistributorInvoice = request()->routeIs('cfa-distributor-invoices.store') 
            || request()->routeIs('cfa-distributor-invoices.update')
            || request()->has('invoice_type') && request()->invoice_type === 'cfa_distributor'
            || str_contains(request()->url(), 'cfa-distributor-invoices');
        
        if ($isCFADistributorInvoice) {
            // Skip observer processing for CFA/Distributor invoices
            return;
        }

        // ... rest of existing code ...
```

### File 2: `resources/views/invoices/cfa-distributor/pharma-invoice.blade.php`

**Fix Required:** Add safety check to filter out items without `purchase_entry_id` if items with `purchase_entry_id` exist.

In the `@php` block before the `@foreach` loop, add:

```php
@php
    // Display exactly what's in the database - items should already be filtered and sorted by controller
    // Just ensure we only show 'item' type and maintain the order from controller
    $displayItems = $invoice->items->where('type', 'item');
    
    // Safety check: Filter out items without purchase_entry_id for CFA/Distributor invoices
    // These are likely duplicates created by the observer before the fix
    // Only filter if there are items WITH purchase_entry_id (to avoid removing legitimate items)
    $itemsWithPurchaseEntry = $displayItems->filter(function($item) {
        return !empty($item->purchase_entry_id);
    });
    
    if ($itemsWithPurchaseEntry->count() > 0) {
        // If we have items with purchase_entry_id, only show those (filter out observer duplicates)
        $displayItems = $itemsWithPurchaseEntry;
    }
    
    // Ensure unique by ID (shouldn't be needed, but safety check)
    $displayItems = $displayItems->unique('id');
    
    // Sort by field_order then id (items should already be sorted, but ensure consistency)
    $displayItems = $displayItems->sortBy(function($item) {
        return $item->field_order ?? ($item->id ?? 999999);
    })->values();
@endphp
```

## Database Cleanup Script

After deploying the fixes, run this script to clean up existing duplicate items:

```php
<?php
// fix_all_invoice_duplicates.php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Invoice;
use App\Models\InvoiceItems;

$invoices = Invoice::orderBy('id', 'asc')->get();
$totalFixed = 0;
$totalDeleted = 0;

foreach ($invoices as $invoice) {
    $items = InvoiceItems::where('invoice_id', $invoice->id)
        ->where('type', 'item')
        ->orderBy('field_order', 'asc')
        ->orderBy('id', 'asc')
        ->get();
    
    if ($items->count() <= 1) continue;
    
    $grouped = [];
    foreach ($items as $item) {
        $key = ($item->product_id ?? 'null') . '_' . ($item->field_order ?? 'null');
        if (!isset($grouped[$key])) {
            $grouped[$key] = [];
        }
        $grouped[$key][] = $item;
    }
    
    foreach ($grouped as $key => $groupItems) {
        if (count($groupItems) > 1) {
            $correctItem = null;
            $duplicateItems = [];
            
            foreach ($groupItems as $item) {
                if (!empty($item->purchase_entry_id)) {
                    $correctItem = $item;
                } else {
                    $duplicateItems[] = $item;
                }
            }
            
            if ($correctItem && !empty($duplicateItems)) {
                foreach ($duplicateItems as $dupItem) {
                    $dupItem->delete();
                    $totalDeleted++;
                    $totalFixed++;
                }
            }
        }
    }
}

echo "Invoices fixed: {$totalFixed}\n";
echo "Duplicate items deleted: {$totalDeleted}\n";
```

## Deployment Steps

1. **Update InvoiceObserver.php** with the fixes above
2. **Update pharma-invoice.blade.php** with the safety check
3. **Upload to server** following the instructions in `UPLOAD_TO_HOSTINGER.txt`
4. **Run the cleanup script** to fix existing duplicate items
5. **Test** by creating a new CFA/Distributor invoice

## Verification

After deployment, verify:
- ✅ New invoices don't have duplicates
- ✅ Existing invoices display correctly (after cleanup script)
- ✅ Invoice items have `purchase_entry_id`, `batch`, and `scheme` populated
- ✅ No items without `purchase_entry_id` exist for CFA/Distributor invoices

