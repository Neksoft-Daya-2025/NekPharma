@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <a href="{{ route('supplier-invoices.index') }}" class="btn btn-secondary btn-sm">@lang('app.back')</a>
                @if(user()->permission('add_product') == 'all' || user()->permission('add_product') == 'added')
                    @php
                        $addEntriesUrl = route('purchase-entries.create', [
                            'invoice_number' => $supplierInvoice->invoice_number,
                            'vendor_id' => $supplierInvoice->vendor_id,
                            'invoice_date' => $supplierInvoice->invoice_date ? $supplierInvoice->invoice_date->format('Y-m-d') : '',
                            'supplier_invoice_total' => $supplierInvoice->supplier_invoice_total ?? '',
                        ]);
                    @endphp
                    <a href="{{ $addEntriesUrl }}" class="btn btn-success btn-sm ml-2">@lang('app.addPurchaseEntriesForInvoice')</a>
                @endif
            </div>
            @if(user()->permission('edit_product') == 'all' || user()->permission('edit_product') == 'added')
                <a href="{{ route('supplier-invoices.edit', $supplierInvoice->id) }}" class="btn btn-primary btn-sm">@lang('app.edit')</a>
            @endif
        </div>
        <div class="bg-white rounded b-shadow-4 p-4">
            <h4 class="mb-3">@lang('app.supplierInvoice') #{{ $supplierInvoice->invoice_number }}</h4>
            <div class="row mb-4">
                <div class="col-md-3"><strong>@lang('app.invoiceNumber'):</strong> {{ $supplierInvoice->invoice_number }}</div>
                <div class="col-md-3"><strong>@lang('app.date'):</strong> {{ $supplierInvoice->invoice_date ? $supplierInvoice->invoice_date->format(company()->date_format) : '--' }}</div>
                <div class="col-md-4"><strong>@lang('modules.invoices.vendor'):</strong> {{ $supplierInvoice->vendor ? ($supplierInvoice->vendor->primary_name ?? $supplierInvoice->vendor->company_name) : '--' }}</div>
                <div class="col-md-2"><strong>@lang('app.matchStatus'):</strong>
                    @if($supplierInvoice->match_status === 'matched')
                        <span class="badge badge-success">Matched</span>
                    @elseif($supplierInvoice->match_status === 'unmatched')
                        <span class="badge badge-danger">Unmatched</span>
                    @else
                        <span class="badge badge-secondary">Draft</span>
                    @endif
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-3"><strong>@lang('app.supplierTotal'):</strong> {{ currency_format($supplierInvoice->supplier_invoice_total ?? 0) }}</div>
                <div class="col-md-3"><strong>@lang('app.entryTotal'):</strong> {{ currency_format($supplierInvoice->entry_total ?? 0) }}</div>
                <div class="col-md-3"><strong>@lang('app.paymentStatus'):</strong>
                    <span class="badge badge-{{ $supplierInvoice->payment_status === 'paid' ? 'success' : ($supplierInvoice->payment_status === 'partial' ? 'warning' : 'secondary') }}">{{ ucfirst($supplierInvoice->payment_status) }}</span>
                </div>
                @if($supplierInvoice->reference_number)
                    <div class="col-md-3"><strong>@lang('app.referenceNumber'):</strong> {{ $supplierInvoice->reference_number }}</div>
                @endif
            </div>
            @if($supplierInvoice->notes)
                <div class="mb-3"><strong>@lang('app.note'):</strong> {{ $supplierInvoice->notes }}</div>
            @endif

            <h5 class="mb-2">Purchase entry lines</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>@lang('app.product')</th>
                            <th>Batch</th>
                            <th>Expiry</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">MRP</th>
                            <th class="text-right">PTS</th>
                            <th class="text-right">PTR</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($supplierInvoice->lines as $line)
                            <tr>
                                <td>{{ $line->product->name ?? '-' }}</td>
                                <td>{{ $line->batch ?? '-' }}</td>
                                <td>{{ $line->expiry ? $line->expiry->format('M Y') : '-' }}</td>
                                <td class="text-right">{{ $line->total_quantity ?? $line->quantity ?? '-' }}</td>
                                <td class="text-right">{{ number_format($line->mrp ?? 0, 2) }}</td>
                                <td class="text-right">{{ number_format($line->pts ?? 0, 2) }}</td>
                                <td class="text-right">{{ number_format($line->ptr ?? 0, 2) }}</td>
                                <td class="text-right">{{ currency_format($line->total ?? 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center">No purchase entry lines linked. Add lines via Purchase Entries and link to this invoice number.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <h5 class="mb-2 mt-4">Payment entries</h5>
            @if(user()->permission('add_product') == 'all' || user()->permission('add_product') == 'added')
                <button type="button" class="btn btn-primary btn-sm mb-2" id="add-payment-btn">Add Payment</button>
            @endif
            @if($supplierInvoice->payments->count() > 0)
                <table class="table table-bordered table-sm" id="supplier-payments-table">
                    <thead>
                        <tr>
                            <th>@lang('app.date')</th>
                            <th class="text-right">Amount</th>
                            <th>Reference</th>
                            <th>Remarks</th>
                            @if(user()->permission('edit_product') == 'all' || user()->permission('delete_product') == 'all')
                                <th width="100">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($supplierInvoice->payments as $pay)
                            <tr data-payment-id="{{ $pay->id }}">
                                <td>{{ $pay->paid_on ? $pay->paid_on->format(company()->date_format) : '-' }}</td>
                                <td class="text-right">{{ currency_format($pay->amount) }}</td>
                                <td>{{ $pay->reference ?? '-' }}</td>
                                <td>{{ $pay->remarks ?? '-' }}</td>
                                @if(user()->permission('edit_product') == 'all' || user()->permission('delete_product') == 'all')
                                    <td>
                                        @if(user()->permission('edit_product') == 'all' || user()->permission('edit_product') == 'added')
                                            <button type="button" class="btn btn-outline-primary btn-xs edit-payment-btn" data-id="{{ $pay->id }}" data-amount="{{ $pay->amount }}" data-paid-on="{{ $pay->paid_on ? $pay->paid_on->format('Y-m-d') : '' }}" data-reference="{{ $pay->reference ?? '' }}" data-remarks="{{ $pay->remarks ?? '' }}" title="Edit"><i class="fa fa-edit"></i></button>
                                        @endif
                                        @if(user()->permission('delete_product') == 'all' || user()->permission('delete_product') == 'added')
                                            <button type="button" class="btn btn-outline-danger btn-xs delete-payment-btn" data-id="{{ $pay->id }}" data-amount="{{ $pay->amount }}" title="Delete"><i class="fa fa-trash"></i></button>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted mb-0">No payment entries yet.</p>
            @endif
        </div>
    </div>

    @if(user()->permission('add_product') == 'all' || user()->permission('add_product') == 'added' || user()->permission('edit_product') == 'all' || user()->permission('edit_product') == 'added')
    <div class="modal fade" id="supplier-payment-modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                @include('supplier-invoices.payment-modal')
            </div>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
<script>
(function() {
    var supplierInvoiceId = {{ $supplierInvoice->id }};
    var $modal = $('#supplier-payment-modal');
    var $form = $('#supplier-invoice-payment-form');

    function openModal(editId, amount, paidOn, reference, remarks) {
        $('#payment_edit_id').val(editId || '');
        $('#payment_amount').val(amount ?? '');
        $('#payment_paid_on').val(paidOn || '{{ date("Y-m-d") }}');
        $('#payment_reference').val(reference || '');
        $('#payment_remarks').val(remarks || '');
        $('#supplierPaymentModalTitle').text(editId ? 'Edit Payment' : 'Add Payment');
        $('#save-supplier-payment-btn').text(editId ? 'Update' : 'Save Payment');
        $modal.modal('show');
    }

    $('#add-payment-btn').on('click', function() {
        openModal();
    });

    $(document).on('click', '.edit-payment-btn', function() {
        var id = $(this).data('id');
        var amount = $(this).data('amount');
        var paidOn = $(this).data('paid-on');
        var reference = $(this).data('reference');
        var remarks = $(this).data('remarks');
        openModal(id, amount, paidOn, reference, remarks);
    });

    $form.on('submit', function(e) {
        e.preventDefault();
        var editId = $('#payment_edit_id').val();
        var url = editId
            ? '{{ url("account/supplier-invoices") }}/' + supplierInvoiceId + '/payments/' + editId
            : '{{ url("account/supplier-invoices") }}/' + supplierInvoiceId + '/payments';
        var method = editId ? 'PUT' : 'POST';
        var data = $form.serialize();
        if (editId) data += '&_method=PUT';

        $.easyAjax({
            url: url,
            type: method,
            data: data,
            container: '#supplier-invoice-payment-form',
            blockUI: true,
            success: function() {
                $modal.modal('hide');
                window.location.reload();
            }
        });
    });

    $(document).on('click', '.delete-payment-btn', function() {
        var id = $(this).data('id');
        var amount = $(this).data('amount');
        Swal.fire({
            title: 'Delete Payment?',
            text: 'Amount: ' + (parseFloat(amount) || 0).toFixed(2),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.easyAjax({
                    url: '{{ url("account/supplier-invoices") }}/' + supplierInvoiceId + '/payments/' + id,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    blockUI: true,
                    success: function() {
                        window.location.reload();
                    }
                });
            }
        });
    });
})();
</script>
@endpush
