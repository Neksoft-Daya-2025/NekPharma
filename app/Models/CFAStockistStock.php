<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CFAStockistStock extends BaseModel
{
    use HasFactory, HasCompany;

    protected $table = 'cfa_stockist_stocks';

    protected $fillable = [
        'company_id',
        'cfa_distributor_id',
        'cfa_stockist_id',
        'product_id',
        'cfa_distributor_stock_id',
        'invoice_id',
        'batch',
        'expiry',
        'quantity',
        'pts',
        'ptr',
        'mrp',
        'dis',
    ];

    protected $casts = [
        'expiry' => 'date',
        'quantity' => 'decimal:2',
        'pts' => 'decimal:2',
        'ptr' => 'decimal:2',
        'mrp' => 'decimal:2',
        'dis' => 'decimal:2',
    ];

    /**
     * Get the CFA/Distributor (User) who is billing
     */
    public function cfaDistributor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cfa_distributor_id');
    }

    /**
     * Get the CFA Stockist who is being billed
     */
    public function cfaStockist(): BelongsTo
    {
        return $this->belongsTo(CFAStockist::class, 'cfa_stockist_id');
    }

    /**
     * Get the product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the CFA Distributor Stock entry (source stock)
     */
    public function cfaDistributorStock(): BelongsTo
    {
        return $this->belongsTo(CFADistributorStock::class, 'cfa_distributor_stock_id');
    }

    /**
     * Get the invoice that created this stock
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}

