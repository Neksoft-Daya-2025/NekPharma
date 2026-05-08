<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Backfill purchase_batch_stock from existing product_purchase_details (batch-wise inventory).
     */
    public function up(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('purchase_batch_stock')) {
            return;
        }

        $rows = DB::table('product_purchase_details')
            ->join('products', 'products.id', '=', 'product_purchase_details.product_id')
            ->whereNotNull('products.company_id')
            ->select(
                'products.company_id',
                'product_purchase_details.product_id',
                'product_purchase_details.batch',
                'product_purchase_details.expiry',
                DB::raw('SUM(COALESCE(product_purchase_details.total_quantity, product_purchase_details.quantity, 0)) as quantity')
            )
            ->groupBy('products.company_id', 'product_purchase_details.product_id', 'product_purchase_details.batch', 'product_purchase_details.expiry')
            ->get();

        foreach ($rows as $row) {
            $q = DB::table('purchase_batch_stock')
                ->where('company_id', $row->company_id)
                ->where('product_id', $row->product_id);
            if ($row->batch !== null && $row->batch !== '') {
                $q->where('batch', $row->batch);
            } else {
                $q->whereNull('batch');
            }
            if ($row->expiry !== null) {
                $q->where('expiry', $row->expiry);
            } else {
                $q->whereNull('expiry');
            }
            $existing = $q->first();

            $qty = (float) $row->quantity;
            if ($qty <= 0) {
                continue;
            }

            if ($existing) {
                DB::table('purchase_batch_stock')
                    ->where('id', $existing->id)
                    ->update(['quantity' => (float) $existing->quantity + $qty, 'updated_at' => now()]);
            } else {
                DB::table('purchase_batch_stock')->insert([
                    'company_id' => $row->company_id,
                    'product_id' => $row->product_id,
                    'batch' => $row->batch ?: null,
                    'expiry' => $row->expiry,
                    'quantity' => $qty,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Backfill is additive; down() could truncate purchase_batch_stock but would lose data. No-op.
    }
};
