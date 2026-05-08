<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierInvoicePayment extends BaseModel
{
    protected $table = 'supplier_invoice_payments';

    protected $fillable = [
        'supplier_invoice_id',
        'amount',
        'paid_on',
        'reference',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'paid_on' => 'date',
        'amount' => 'decimal:2',
    ];

    public function supplierInvoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
