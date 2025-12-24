<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CFADistributorStock extends BaseModel
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'company_id',
        'cfa_distributor_id',
        'product_id',
        'purchase_entry_id',
        'invoice_id',
        'batch',
        'expiry',
        'quantity',
        'available_quantity',
        'pts',
        'ptr',
        'mrp',
        'dis',
    ];

    protected $casts = [
        'expiry' => 'date',
        'quantity' => 'decimal:2',
        'available_quantity' => 'decimal:2',
        'pts' => 'decimal:2',
        'ptr' => 'decimal:2',
        'mrp' => 'decimal:2',
        'dis' => 'decimal:2',
    ];

    /**
     * Get the CFA/Distributor (User)
     */
    public function cfaDistributor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfa_distributor_id');
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the purchase entry
     */
    public function purchaseEntry(): BelongsTo
    {
        return $this->belongsTo(ProductPurchaseDetail::class, 'purchase_entry_id');
    }

    /**
     * Get the invoice that created this stock
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
