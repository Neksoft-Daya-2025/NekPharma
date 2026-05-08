<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockStatementLine extends Model
{
    protected $table = 'stock_statement_lines';

    protected $fillable = [
        'stock_statement_id',
        'product_id',
        'opening_qty',
        'primary_qty',
        'secondary_qty',
        'closing_qty',
    ];

    protected $casts = [
        'opening_qty' => 'decimal:2',
        'primary_qty' => 'decimal:2',
        'secondary_qty' => 'decimal:2',
        'closing_qty' => 'decimal:2',
    ];

    public function stockStatement(): BelongsTo
    {
        return $this->belongsTo(StockStatement::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
