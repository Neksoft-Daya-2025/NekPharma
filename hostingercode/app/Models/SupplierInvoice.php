<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierInvoice extends BaseModel
{
    use HasCompany;

    protected $table = 'supplier_invoices';

    const MATCH_STATUS_DRAFT = 'draft';
    const MATCH_STATUS_MATCHED = 'matched';
    const MATCH_STATUS_UNMATCHED = 'unmatched';

    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_PARTIAL = 'partial';
    const PAYMENT_STATUS_PAID = 'paid';

    protected $fillable = [
        'company_id',
        'vendor_id',
        'invoice_number',
        'invoice_date',
        'supplier_invoice_total',
        'entry_total',
        'match_status',
        'reference_number',
        'reference_date',
        'payment_status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'reference_date' => 'date',
        'supplier_invoice_total' => 'decimal:2',
        'entry_total' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(\Modules\Purchase\Entities\PurchaseVendor::class, 'vendor_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ProductPurchaseDetail::class, 'supplier_invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierInvoicePayment::class, 'supplier_invoice_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Recompute entry_total from lines and update match_status.
     */
    public function refreshTotalsAndMatchStatus(float $tolerance = 0.01): void
    {
        $this->entry_total = (float) $this->lines()->sum('total');
        if ($this->supplier_invoice_total !== null && $this->supplier_invoice_total != '') {
            $diff = abs((float) $this->entry_total - (float) $this->supplier_invoice_total);
            $this->match_status = $diff <= $tolerance ? self::MATCH_STATUS_MATCHED : self::MATCH_STATUS_UNMATCHED;
        } else {
            $this->match_status = self::MATCH_STATUS_DRAFT;
        }
        $this->saveQuietly();
    }

    /**
     * Update payment_status from sum of payments vs entry_total.
     */
    public function refreshPaymentStatus(): void
    {
        $paid = (float) $this->payments()->sum('amount');
        $total = (float) ($this->entry_total ?? $this->lines()->sum('total'));
        if ($total <= 0) {
            $this->payment_status = self::PAYMENT_STATUS_PENDING;
        } elseif ($paid >= $total) {
            $this->payment_status = self::PAYMENT_STATUS_PAID;
        } elseif ($paid > 0) {
            $this->payment_status = self::PAYMENT_STATUS_PARTIAL;
        } else {
            $this->payment_status = self::PAYMENT_STATUS_PENDING;
        }
        $this->saveQuietly();
    }
}
