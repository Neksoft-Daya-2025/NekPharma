<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockStatement extends BaseModel
{
    use HasCompany, HasFactory;

    protected $table = 'stock_statements';

    protected $fillable = [
        'company_id',
        'user_id',
        'cfa_stockist_id',
        'period_month',
        'period_year',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cfaStockist(): BelongsTo
    {
        return $this->belongsTo(CFAStockist::class, 'cfa_stockist_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockStatementLine::class, 'stock_statement_id');
    }
}
