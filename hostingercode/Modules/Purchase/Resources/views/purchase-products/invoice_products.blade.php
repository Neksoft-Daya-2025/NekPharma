<div class="modal-header">
    <h5 class="modal-title">{{ $invoiceNumber }}</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>

<div class="modal-body">
    @if($products->count() > 0)
        @php
            $firstProduct = $products->first();
        @endphp
        
        <!-- Invoice Summary -->
        <div class="row mb-4">
            <div class="col-md-6">
                <table class="table table-borderless table-sm">
                    <tr>
                        <th width="40%">Invoice Date:</th>
                        <td>{{ $firstProduct->invoice_date ? \Carbon\Carbon::parse($firstProduct->invoice_date)->format('d-m-Y') : '--' }}</td>
                    </tr>
                    <tr>
                        <th>{{ __('purchase::app.vendor') }}:</th>
                        <td>{{ $firstProduct->vendor->primary_name ?? '--' }}</td>
                    </tr>
                    <tr>
                        <th>Payment Status:</th>
                        <td>
                            @php
                                $status = $firstProduct->payment_status ?? 'pending';
                                $badge = ['paid' => 'success', 'partial' => 'warning', 'pending' => 'secondary'];
                                $color = $badge[$status] ?? 'secondary';
                            @endphp
                            <span class="badge badge-{{ $color }}">{{ ucfirst($status) }}</span>
                        </td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-borderless table-sm">
                    @if($firstProduct->reference_number)
                    <tr>
                        <th width="40%">Reference:</th>
                        <td>{{ $firstProduct->reference_number }}</td>
                    </tr>
                    @endif
                    @if($firstProduct->mode_of_payment)
                    <tr>
                        <th>Payment Mode:</th>
                        <td>{{ $firstProduct->mode_of_payment }}</td>
                    </tr>
                    @endif
                    @if($firstProduct->destination)
                    <tr>
                        <th>Destination:</th>
                        <td>{{ $firstProduct->destination }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
        
        <hr>
        
        <!-- Products Table -->
        <h6 class="mb-3"><strong>Products ({{ $products->count() }})</strong></h6>
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="bg-light">
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th>Billed Qty</th>
                        <th>Total Qty</th>
                        <th>Batch</th>
                        <th>Expiry</th>
                        <th>Purchase Price</th>
                        <th>MRP</th>
                        <th>PTS</th>
                        <th>PTR</th>
                        <th>Disc %</th>
                        <th>Tax</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $item->product->name ?? '--' }}</strong>
                                @if($item->product && $item->product->hsn_sac_code)
                                    <br><small class="text-muted">HSN: {{ $item->product->hsn_sac_code }}</small>
                                @endif
                            </td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->total_quantity ?? $item->quantity }}</td>
                            <td>{{ $item->batch ?? '--' }}</td>
                            <td>{{ $item->expiry ? $item->expiry->format('M Y') : '--' }}</td>
                            <td>{{ number_format($item->purchase_price ?? 0, 2) }}</td>
                            <td>{{ number_format($item->mrp ?? 0, 2) }}</td>
                            <td>{{ number_format($item->pts ?? 0, 2) }}</td>
                            <td>{{ number_format($item->ptr ?? 0, 2) }}</td>
                            <td>{{ $item->discount ?? 0 }}%</td>
                            <td>
                                @if($item->tax && is_array($item->tax))
                                    @php
                                        $taxDetails = \App\Models\Tax::whereIn('id', $item->tax)->get();
                                    @endphp
                                    @foreach($taxDetails as $tax)
                                        <span class="badge badge-info">{{ $tax->tax_name }}: {{ $tax->rate_percent }}%</span>
                                    @endforeach
                                @else
                                    --
                                @endif
                            </td>
                            <td><strong>{{ currency_format($item->total) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-light">
                    <tr>
                        <th colspan="12" class="text-right">Grand Total:</th>
                        <th>{{ currency_format($products->sum('total')) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    @else
        <p class="text-center text-muted">No products found in this invoice.</p>
    @endif
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
</div>
