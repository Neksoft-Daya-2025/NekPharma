<?php

namespace Modules\Purchase\Jobs;

use App\Traits\ExcelImportable;
use App\Traits\UniversalSearchTrait;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Modules\Purchase\Entities\PurchaseProduct;
use Modules\Purchase\Entities\PurchaseInventory;
use Modules\Purchase\Entities\PurchaseStockAdjustment;

class ImportPurchaseProductJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UniversalSearchTrait;
    use ExcelImportable;

    private $row;
    private $columns;
    private $company;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($row, $columns, $company = null)
    {
        $this->row = $row;
        $this->columns = $columns;
        $this->company = $company;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Check for mandatory fields: product_name, packing, and mrp
        if (!$this->isColumnExists('product_name') || !$this->isColumnExists('packing') || !$this->isColumnExists('mrp')) {
            $this->failJob(__('messages.invalidData') . ' - Missing required fields: Product Name, Packing, or MRP');
            return;
        }

        $productName = trim($this->getColumnValue('product_name'));
        $packing = trim($this->getColumnValue('packing'));
        $mrp = $this->getColumnValue('mrp');

        // Validate mandatory fields are not empty
        if (empty($productName) || empty($packing) || empty($mrp)) {
            $this->failJob(__('messages.invalidData') . ' - Product Name, Packing, and MRP are required');
            return;
        }

        $cleanedMRP = preg_replace('/[^\d.]/', '', $mrp);

        if (!is_numeric($cleanedMRP) || floatval($cleanedMRP) <= 0) {
            $this->failJob(__('messages.invalidData') . ' - MRP must be a valid positive number');
            return;
        }

            DB::beginTransaction();
            try {
                $product = new PurchaseProduct();
                $product->company_id = $this->company?->id;
                $product->name = $productName;
                $product->packing = $packing; // Mandatory field
                $product->purchase_price = $cleanedMRP;
                $product->price = '0'; // Default selling price
                $product->type = 'goods';
                $product->allow_purchase = false;
                $product->downloadable = false;
                $product->purchase_information = '1';

                // Description
                $product->description = $this->isColumnExists('description') ? trim($this->getColumnValue('description')) : null;

                // SKU
                $product->sku = $this->isColumnExists('sku') ? trim($this->getColumnValue('sku')) : null;

                // HSN/SAC Code
                $product->hsn_sac_code = $this->isColumnExists('hsn_sac_code') ? $this->getColumnValue('hsn_sac_code') : null;

                // Unit Type
                if ($this->isColumnExists('unit_type')) {
                    $unitTypeName = $this->getColumnValue('unit_type');
                    $unitType = DB::table('unit_types')->where('unit_type', $unitTypeName)->first();

                    if ($unitType) {
                        $product->unit_id = $unitType->id;
                    } else {
                        $defaultUnitType = DB::table('unit_types')->where('default', true)->first();
                        $product->unit_id = $defaultUnitType ? $defaultUnitType->id : null;
                    }
                } else {
                    $defaultUnitType = DB::table('unit_types')->where('default', true)->first();
                    $product->unit_id = $defaultUnitType ? $defaultUnitType->id : null;
                }

                // Category
                if ($this->isColumnExists('product_category')) {
                    $categoryName = $this->getColumnValue('product_category');
                    $category = DB::table('product_category')->where('category_name', $categoryName)->first();
                    $product->category_id = $category ? $category->id : null;
                } else {
                    $product->category_id = null;
                }

                // Sub Category
                if ($this->isColumnExists('product_sub_category')) {
                    $subCategoryName = $this->getColumnValue('product_sub_category');
                    $subCategory = DB::table('product_sub_category')->where('category_name', $subCategoryName)->first();

                    if ($subCategory && $subCategory->category_id == $product->category_id) {
                        $product->sub_category_id = $subCategory->id;
                    } else {
                        $product->sub_category_id = null;
                    }
                } else {
                    $product->sub_category_id = null;
                }

                // Vendor
                if ($this->isColumnExists('vendor') || $this->isColumnExists('manufacturer')) {
                    $vendorName = $this->getColumnValue('vendor') ?? $this->getColumnValue('manufacturer');
                    $vendor = DB::table('purchase_vendors')
                        ->where('primary_name', $vendorName)
                        ->orWhere('company_name', $vendorName)
                        ->first();
                    $product->vendor_id = $vendor ? $vendor->id : null;
                } else {
                    $product->vendor_id = null;
                }

                // Discount
                $discount = $this->isColumnExists('discount') ? $this->getColumnValue('discount') : 0;
                $discountType = $this->isColumnExists('discount_type') ? strtolower($this->getColumnValue('discount_type')) : 'flat';
                
                if (!in_array($discountType, ['flat', 'percentage'])) {
                    $discountType = 'flat';
                }

                $product->discount = $discount ?: null;
                $product->discount_type = $discountType;

                // Calculate total
                $mrp = floatval($cleanedMRP);
                $discountAmount = 0;
                if ($discountType === 'percentage') {
                    $discountAmount = ($mrp * floatval($discount)) / 100;
                } else {
                    $discountAmount = floatval($discount);
                }
                $product->total = max(0, $mrp - $discountAmount);

                // Scheme
                $schemeQuantity = $this->isColumnExists('scheme_quantity') ? $this->getColumnValue('scheme_quantity') : null;
                $schemeFree = $this->isColumnExists('scheme_free') ? $this->getColumnValue('scheme_free') : null;
                
                if ($schemeQuantity && $schemeFree) {
                    $product->scheme = $schemeQuantity . '+' . $schemeFree;
                } else {
                    $product->scheme = null;
                }

                // Track Inventory and Opening Stock
                $openingStock = $this->isColumnExists('opening_stock') ? $this->getColumnValue('opening_stock') : null;
                
                if ($openingStock && is_numeric($openingStock) && $openingStock > 0) {
                    $product->track_inventory = '1';
                    $product->opening_stock = $openingStock;
                } else {
                    $product->track_inventory = '0';
                    $product->opening_stock = null;
                }

                $product->added_by = user() ? user()->id : null;
                $product->save();

                // Create inventory entry if opening stock is set
                if ($product->track_inventory == '1' && $product->opening_stock) {
                    $inventory = new PurchaseInventory();
                    $inventory->product_id = $product->id;
                    $inventory->company_id = $this->company?->id;
                    $inventory->save();

                    $stockAdjustment = new PurchaseStockAdjustment();
                    $stockAdjustment->product_id = $product->id;
                    $stockAdjustment->adjustment_type = 'opening_stock';
                    $stockAdjustment->quantity = $product->opening_stock;
                    $stockAdjustment->net_quantity = $product->opening_stock;
                    $stockAdjustment->company_id = $this->company?->id;
                    $stockAdjustment->added_by = user() ? user()->id : null;
                    $stockAdjustment->save();
                }

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                $this->failJobWithMessage($e->getMessage());
            }
    }

}

