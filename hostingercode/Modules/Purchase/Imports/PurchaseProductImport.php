<?php

namespace Modules\Purchase\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class PurchaseProductImport implements ToArray
{
    private $processedData = [];

    public static function fields(): array
    {
        return array(
            array('id' => 'product_name', 'name' => __('Product Name'), 'required' => 'Yes'),
            array('id' => 'packing', 'name' => __('Packing'), 'required' => 'Yes'),
            array('id' => 'mrp', 'name' => __('MRP (Maximum Retail Price)'), 'required' => 'Yes'),
            array('id' => 'unit_type', 'name' => __('Unit Type'), 'required' => 'No'),
            array('id' => 'product_category', 'name' => __('Product Category'), 'required' => 'No'),
            array('id' => 'product_sub_category', 'name' => __('Product Sub Category'), 'required' => 'No'),
            array('id' => 'sku', 'name' => __('SKU'), 'required' => 'No'),
            array('id' => 'vendor', 'name' => __('Vendor'), 'required' => 'No'),
            array('id' => 'discount', 'name' => __('Discount'), 'required' => 'No'),
            array('id' => 'discount_type', 'name' => __('Discount Type (flat/percentage)'), 'required' => 'No'),
            array('id' => 'description', 'name' => __('Description'), 'required' => 'No'),
            array('id' => 'hsn_sac_code', 'name' => __('HSN/SAC Code'), 'required' => 'No'),
            array('id' => 'opening_stock', 'name' => __('Opening Stock'), 'required' => 'No'),
            array('id' => 'scheme_quantity', 'name' => __('Scheme Quantity'), 'required' => 'No'),
            array('id' => 'scheme_free', 'name' => __('Free Quantity'), 'required' => 'No'),
        );
    }

    public function array(array $array): array
    {
        $this->processedData = $array;
        return $array;
    }

    public function getProcessedData()
    {
        return $this->processedData;
    }

}

