<?php

namespace Modules\Purchase\Entities;

use App\Models\BaseModel;
use App\Models\Product;
use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseBatchStock extends BaseModel
{
    use HasCompany;

    protected $table = 'purchase_batch_stock';

    protected $fillable = [
        'company_id',
        'product_id',
        'batch',
        'expiry',
        'quantity',
    ];

    protected $casts = [
        'expiry' => 'date',
        'quantity' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Scope: FEFO (First Expiry First Out) - order by expiry ascending so soonest expiry first.
     */
    public function scopeFefo($query)
    {
        return $query->orderByRaw('expiry IS NULL, expiry ASC');
    }

    /**
     * Scope: for a product with available quantity.
     */
    public function scopeForProduct($query, $productId, $companyId = null)
    {
        $q = $query->where('product_id', $productId)->where('quantity', '>', 0);
        if ($companyId !== null) {
            $q->where('company_id', $companyId);
        }
        return $q;
    }

    /**
     * Deduct quantity from batch stock using FEFO (First Expiry First Out).
     * Returns true if full quantity was deducted, false otherwise.
     */
    public static function deductFefo(int $companyId, int $productId, float $quantity): bool
    {
        if ($quantity <= 0) {
            return true;
        }
        $remaining = $quantity;
        $rows = self::where('company_id', $companyId)
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->orderByRaw('expiry IS NULL, expiry ASC')
            ->get();
        foreach ($rows as $row) {
            if ($remaining <= 0) {
                break;
            }
            $deduct = min((float) $row->quantity, $remaining);
            $row->quantity = (float) $row->quantity - $deduct;
            $row->save();
            $remaining -= $deduct;
        }
        return $remaining <= 0;
    }
}
