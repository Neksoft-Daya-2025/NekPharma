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
        'product_id', 'vendor_id', 'quantity', 'unit_id', 'batch', 'expiry',
        'pts', 'ptr', 'dis', 'mrp', 'discount', 'discount_type',
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
}

