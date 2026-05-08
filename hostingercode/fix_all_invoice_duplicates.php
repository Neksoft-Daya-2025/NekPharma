<?php

/**
 * Script to fix duplicate invoice items in existing invoices
 * 
 * This script identifies and removes duplicate items created by the InvoiceObserver
 * for CFA/Distributor invoices. Items without purchase_entry_id are considered duplicates
 * and will be removed, keeping only items with purchase_entry_id (created by controller).
 * 
 * Run: php fix_all_invoice_duplicates.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Invoice;
use App\Models\InvoiceItems;

echo "=== Fixing All Invoice Duplicates ===\n\n";

// Get all invoices
$invoices = Invoice::orderBy('id', 'asc')->get();

$totalFixed = 0;
$totalDeleted = 0;
$invoicesProcessed = 0;

foreach ($invoices as $invoice) {
    $invoicesProcessed++;
    
    // Get all items for this invoice
    $items = InvoiceItems::where('invoice_id', $invoice->id)
        ->where('type', 'item')
        ->orderBy('field_order', 'asc')
        ->orderBy('id', 'asc')
        ->get();
    
    if ($items->count() <= 1) {
        continue; // Skip invoices with 0 or 1 item
    }
    
    // Group items by product_id and field_order to find duplicates
    $grouped = [];
    foreach ($items as $item) {
        $key = ($item->product_id ?? 'null') . '_' . ($item->field_order ?? 'null');
        if (!isset($grouped[$key])) {
            $grouped[$key] = [];
        }
        $grouped[$key][] = $item;
    }
    
    $invoiceFixed = false;
    $invoiceDeleted = 0;
    
    foreach ($grouped as $key => $groupItems) {
        if (count($groupItems) > 1) {
            // Found duplicates for this product_id + field_order combination
            // Find the item with purchase_entry_id (created by controller) - this is the correct one
            $correctItem = null;
            $duplicateItems = [];
            
            foreach ($groupItems as $item) {
                if (!empty($item->purchase_entry_id)) {
                    // This is the correct item (created by controller)
                    $correctItem = $item;
                } else {
                    // This is likely a duplicate (created by observer)
                    $duplicateItems[] = $item;
                }
            }
            
            if ($correctItem && !empty($duplicateItems)) {
                // Delete the duplicate items (without purchase_entry_id)
                foreach ($duplicateItems as $dupItem) {
                    $dupItem->delete();
                    $invoiceDeleted++;
                    $totalDeleted++;
                    $invoiceFixed = true;
                }
            } elseif (empty($correctItem) && count($groupItems) > 1) {
                // All items are missing purchase_entry_id, keep the first one
                for ($i = 1; $i < count($groupItems); $i++) {
                    $groupItems[$i]->delete();
                    $invoiceDeleted++;
                    $totalDeleted++;
                    $invoiceFixed = true;
                }
            }
        }
    }
    
    if ($invoiceFixed) {
        $totalFixed++;
        echo "Invoice #{$invoice->id} ({$invoice->invoice_number}): Deleted {$invoiceDeleted} duplicate item(s)\n";
    }
}

echo "\n=== Summary ===\n";
echo "Total invoices processed: {$invoicesProcessed}\n";
echo "Invoices fixed: {$totalFixed}\n";
echo "Duplicate items deleted: {$totalDeleted}\n";
echo "=== Fix Complete ===\n";


