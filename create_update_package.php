<?php

/**
 * Script to create a comprehensive update package for Hostingercode
 * This will create a zip file with all necessary updates
 */

$rootDir = __DIR__;
$hostingercodeDir = $rootDir . '/Hostingercode';
$updateDir = $rootDir . '/UPDATE_PACKAGE';

// Create update directory
if (!is_dir($updateDir)) {
    mkdir($updateDir, 0755, true);
}

echo "=== Creating Update Package ===\n\n";

// Files to copy/update
$filesToUpdate = [
    // Fixed Observer
    'app/Observers/InvoiceObserver.php' => 'app/Observers/InvoiceObserver.php',
    
    // Missing Controller methods - we'll append these
    'app/Http/Controllers/InvoiceController.php' => 'app/Http/Controllers/InvoiceController_ADDITIONS.txt',
    
    // Missing Views
    'resources/views/invoices/cfa-distributor' => 'resources/views/invoices/cfa-distributor',
    
    // Missing DataTable
    'app/DataTables/CFADistributorInvoicesDataTable.php' => 'app/DataTables/CFADistributorInvoicesDataTable.php',
    
    // Missing Model
    'app/Models/CFADistributorStock.php' => 'app/Models/CFADistributorStock.php',
    
    // Database Migrations - All December 2025 migrations
    'database/migrations/2025_12_01_044403_create_doctor_products_table.php' => 'database/migrations/2025_12_01_044403_create_doctor_products_table.php',
    'database/migrations/2025_12_01_050000_add_packing_to_products_table.php' => 'database/migrations/2025_12_01_050000_add_packing_to_products_table.php',
    'database/migrations/2025_12_20_082001_add_scheme_fields_to_product_purchase_details_table.php' => 'database/migrations/2025_12_20_082001_add_scheme_fields_to_product_purchase_details_table.php',
    'database/migrations/2025_12_20_114343_create_client_areas_table.php' => 'database/migrations/2025_12_20_114343_create_client_areas_table.php',
    'database/migrations/2025_12_20_115711_add_stockist_id_to_invoices_table.php' => 'database/migrations/2025_12_20_115711_add_stockist_id_to_invoices_table.php',
    'database/migrations/2025_12_20_132239_add_dl_gst_msl_to_stockists_table.php' => 'database/migrations/2025_12_20_132239_add_dl_gst_msl_to_stockists_table.php',
    'database/migrations/2025_12_20_140010_add_pharma_fields_to_invoice_items_table.php' => 'database/migrations/2025_12_20_140010_add_pharma_fields_to_invoice_items_table.php',
    'database/migrations/2025_12_20_150000_replace_manufacturer_with_vendor_in_products.php' => 'database/migrations/2025_12_20_150000_replace_manufacturer_with_vendor_in_products.php',
    'database/migrations/2025_12_21_080259_create_c_f_a_distributor_stocks_table.php' => 'database/migrations/2025_12_21_080259_create_c_f_a_distributor_stocks_table.php',
    'database/migrations/2025_12_21_153000_create_cfa_stockists_table.php' => 'database/migrations/2025_12_21_153000_create_cfa_stockists_table.php',
    'database/migrations/2025_12_21_153100_create_cfa_distributor_stockist_table.php' => 'database/migrations/2025_12_21_153100_create_cfa_distributor_stockist_table.php',
    'database/migrations/2025_12_21_154000_add_cfa_stockist_id_to_cfa_stockists_table.php' => 'database/migrations/2025_12_21_154000_add_cfa_stockist_id_to_cfa_stockists_table.php',
    'database/migrations/2025_12_21_155000_fix_cfa_distributor_stockist_table.php' => 'database/migrations/2025_12_21_155000_fix_cfa_distributor_stockist_table.php',
    'database/migrations/2025_12_21_155100_ensure_cfa_stockist_id_column.php' => 'database/migrations/2025_12_21_155100_ensure_cfa_stockist_id_column.php',
    'database/migrations/2025_12_22_190338_add_lr_fields_to_invoices_table.php' => 'database/migrations/2025_12_22_190338_add_lr_fields_to_invoices_table.php',
    'database/migrations/2025_12_22_192154_add_bank_details_to_client_details_table.php' => 'database/migrations/2025_12_22_192154_add_bank_details_to_client_details_table.php',
];

// Copy files
foreach ($filesToUpdate as $source => $dest) {
    $sourcePath = $rootDir . '/' . $source;
    $destPath = $updateDir . '/' . $dest;
    
    // Create destination directory if needed
    $destDir = dirname($destPath);
    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }
    
    if (is_dir($sourcePath)) {
        // Copy directory recursively
        copyDirectory($sourcePath, $destPath);
        echo "✓ Copied directory: $source\n";
    } elseif (file_exists($sourcePath)) {
        // Copy file
        copy($sourcePath, $destPath);
        echo "✓ Copied file: $source\n";
    } else {
        echo "⚠ File not found: $source\n";
    }
}

// Extract CFA/Distributor methods from InvoiceController
echo "\n=== Extracting CFA/Distributor methods ===\n";
$controllerContent = file_get_contents($rootDir . '/app/Http/Controllers/InvoiceController.php');
$additionsFile = $updateDir . '/app/Http/Controllers/InvoiceController_ADDITIONS.txt';

// Extract lines 2299-3115 (CFA/Distributor methods, including docblock)
$lines = explode("\n", $controllerContent);
$additions = array_slice($lines, 2298, 817); // Lines 2299-3115 (includes opening /**)

$additionsContent = "// ADD THESE METHODS TO InvoiceController.php BEFORE THE CLOSING BRACE\n";
$additionsContent .= "// These are the CFA/Distributor invoice methods (lines 2299-3115)\n\n";
$additionsContent .= implode("\n", $additions);

file_put_contents($additionsFile, $additionsContent);
echo "✓ Created additions file: InvoiceController_ADDITIONS.txt\n";

// Create README with instructions
$readme = <<<'README'
# UPDATE PACKAGE FOR HOSTINGERCODE

## What's Included

1. **Fixed InvoiceObserver.php** - Prevents duplicate items for CFA/Distributor invoices
2. **Missing Database Migrations** - All pharma-related migrations
3. **CFA/Distributor Invoice Methods** - Complete controller methods
4. **View Files** - All CFA/Distributor invoice views
5. **DataTable Class** - CFADistributorInvoicesDataTable
6. **Model Class** - CFADistributorStock model

## Installation Instructions

### Step 1: Extract this package
Extract all files maintaining the directory structure.

### Step 2: Update InvoiceController.php
1. Open `Hostingercode/app/Http/Controllers/InvoiceController.php`
2. Add these imports at the top (around line 38):
   ```php
   use App\DataTables\CFADistributorInvoicesDataTable;
   use App\Models\ProductPurchaseDetail;
   use App\Models\CFADistributorStock;
   ```
3. Open `InvoiceController_ADDITIONS.txt` and copy ALL the methods
4. Paste them BEFORE the closing brace `}` at the end of InvoiceController.php

### Step 3: Copy Files
Copy all files from this package to Hostingercode maintaining directory structure:
- `app/Observers/InvoiceObserver.php` → Replace existing
- `app/DataTables/CFADistributorInvoicesDataTable.php` → Copy
- `app/Models/CFADistributorStock.php` → Copy
- `resources/views/invoices/cfa-distributor/` → Copy entire folder
- `database/migrations/*.php` → Copy all migration files

### Step 4: After Uploading to Server
1. Run migrations: `php artisan migrate`
2. Clear caches: `php artisan config:cache && php artisan route:cache && php artisan view:cache`
3. Run cleanup script: `php fix_all_invoice_duplicates.php`

## Verification

After deployment, verify:
- ✅ InvoiceObserver has CFA/Distributor checks
- ✅ InvoiceController has all CFA/Distributor methods
- ✅ Database has `purchase_entry_id` column in `invoice_items` table
- ✅ Routes work for CFA/Distributor invoices

README;

file_put_contents($updateDir . '/README.txt', $readme);
echo "✓ Created README.txt\n";

// Create zip file
echo "\n=== Creating ZIP file ===\n";
$zipFile = $rootDir . '/HOSTINGERCODE_UPDATE.zip';

if (class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($updateDir),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        $updateDirRealPath = realpath($updateDir);
        
        foreach ($iterator as $file) {
            $fileRealPath = $file->getRealPath();
            if (is_file($fileRealPath)) {
                // Calculate relative path from UPDATE_PACKAGE directory
                $relativePath = str_replace($updateDirRealPath . DIRECTORY_SEPARATOR, '', $fileRealPath);
                // Normalize path separators to forward slashes for ZIP
                $relativePath = str_replace('\\', '/', $relativePath);
                // Remove UPDATE_PACKAGE/ prefix if present
                $relativePath = preg_replace('#^UPDATE_PACKAGE/#', '', $relativePath);
                // Add file with clean relative path (extracts directly to root)
                $zip->addFile($fileRealPath, $relativePath);
            }
        }
        
        $zip->close();
        echo "✓ Created ZIP file: HOSTINGERCODE_UPDATE.zip\n";
        echo "   Size: " . number_format(filesize($zipFile) / 1024, 2) . " KB\n";
    } else {
        echo "✗ Failed to create ZIP file\n";
    }
} else {
    echo "⚠ ZipArchive class not available. Files are in: $updateDir\n";
}

echo "\n=== Update Package Created Successfully ===\n";
echo "Location: $updateDir\n";
if (file_exists($zipFile)) {
    echo "ZIP File: $zipFile\n";
}

function copyDirectory($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst, 0755, true);
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            if (is_dir($src . '/' . $file)) {
                copyDirectory($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

