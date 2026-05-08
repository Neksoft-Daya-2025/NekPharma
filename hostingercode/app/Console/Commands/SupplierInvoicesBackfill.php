<?php

namespace App\Console\Commands;

use App\Models\ProductPurchaseDetail;
use App\Models\SupplierInvoice;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SupplierInvoicesBackfill extends Command
{
    protected $signature = 'supplier-invoices:backfill {--company-id= : Limit to a specific company ID}';

    protected $description = 'Create supplier_invoices from existing product_purchase_details grouped by vendor, invoice_number, invoice_date and link lines';

    public function handle(): int
    {
        $companyId = $this->option('company-id');

        $query = ProductPurchaseDetail::with('product')
            ->whereNull('supplier_invoice_id')
            ->whereNotNull('vendor_id')
            ->whereNotNull('invoice_number')
            ->whereNotNull('invoice_date');

        if ($companyId) {
            $query->whereHas('product', fn ($q) => $q->where('company_id', $companyId));
        }

        $lines = $query->orderBy('invoice_number')->orderBy('invoice_date')->get();
        if ($lines->isEmpty()) {
            $this->info('No unlinked purchase entry lines found.');
            return self::SUCCESS;
        }

        $groups = $lines->groupBy(function ($line) {
            $companyId = $line->product ? $line->product->company_id : null;
            return ($companyId ?? 'null') . '|' . ($line->vendor_id ?? '') . '|' . ($line->invoice_number ?? '') . '|' . ($line->invoice_date ? Carbon::parse($line->invoice_date)->format('Y-m-d') : '');
        });

        $created = 0;
        $linked = 0;

        foreach ($groups as $key => $group) {
            $first = $group->first();
            $companyId = $first->product ? $first->product->company_id : null;
            if (!$companyId) {
                $this->warn("Skipping group (no company_id from product): {$first->invoice_number}");
                continue;
            }

            $vendorId = $first->vendor_id;
            $invoiceNumber = $first->invoice_number;
            $invoiceDate = $first->invoice_date ? Carbon::parse($first->invoice_date) : null;
            if (!$invoiceDate) {
                $this->warn("Skipping group (no invoice_date): {$invoiceNumber}");
                continue;
            }

            $entryTotal = $group->sum('total');
            $lineIds = $group->pluck('id')->toArray();

            $supplierInvoice = SupplierInvoice::firstOrCreate(
                [
                    'company_id'     => $companyId,
                    'vendor_id'      => $vendorId,
                    'invoice_number' => $invoiceNumber,
                    'invoice_date'   => $invoiceDate,
                ],
                [
                    'supplier_invoice_total' => null,
                    'entry_total'           => $entryTotal,
                    'match_status'          => SupplierInvoice::MATCH_STATUS_DRAFT,
                    'payment_status'        => SupplierInvoice::PAYMENT_STATUS_PENDING,
                    'reference_number'      => $first->reference_number,
                    'reference_date'        => $first->reference_date ? Carbon::parse($first->reference_date) : null,
                    'notes'                 => null,
                    'created_by'            => $first->created_by,
                ]
            );

            if ($supplierInvoice->wasRecentlyCreated) {
                $created++;
            }

            $updated = ProductPurchaseDetail::whereIn('id', $lineIds)->update(['supplier_invoice_id' => $supplierInvoice->id]);
            $linked += $updated;

            $supplierInvoice->refreshTotalsAndMatchStatus();
        }

        $this->info("Backfill complete. Supplier invoices created/used: " . $groups->count() . ", lines linked: {$linked}.");
        return self::SUCCESS;
    }
}
