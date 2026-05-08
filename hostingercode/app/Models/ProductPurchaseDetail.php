<?php

namespace App\Models;

// use App\Traits\HasCompany;
// use App\Traits\CustomFieldsTrait;
// use Illuminate\Database\Eloquent\Relations\HasMany;
// use Illuminate\Database\Eloquent\Relations\BelongsTo;
// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Purchase\Entities\PurchaseStockAdjustment;
// use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class ProductPurchaseDetail extends BaseModel
{
    protected $fillable = [
        'supplier_invoice_id',
        'invoice_number', 'invoice_date', 'mode_of_payment', 'payment_status', 'reference_number', 'reference_date',
        'dispatch_through', 'destination', 'terms_of_delivery',
        'product_id', 'vendor_id', 'quantity', 'unit_id', 'batch', 'expiry',
        'purchase_price', 'pts', 'ptr', 'dis', 'mrp', 'discount', 'discount_type',
        'total', 'tax', 'description', 'created_by',
        'scheme_enabled', 'total_quantity', 'free_quantity'
    ];

    protected $casts = [
        'expiry' => 'date',
        'tax' => 'array'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor()
    {
        return $this->belongsTo(\Modules\Purchase\Entities\PurchaseVendor::class, 'vendor_id');
    }

    public function supplierInvoice()
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }
}

