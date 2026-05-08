@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <a href="{{ route('supplier-invoices.show', $supplierInvoice->id) }}" class="btn btn-secondary btn-sm">@lang('app.back')</a>
            </div>
        </div>
        <div class="bg-white rounded b-shadow-4 p-4">
            <h4 class="mb-3">@lang('app.edit') @lang('app.supplierInvoice') #{{ $supplierInvoice->invoice_number }}</h4>
            <form action="{{ route('supplier-invoices.update', $supplierInvoice->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>@lang('app.invoiceNumber') <span class="text-danger">*</span></label>
                            <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number', $supplierInvoice->invoice_number) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>@lang('app.date') <span class="text-danger">*</span></label>
                            <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', $supplierInvoice->invoice_date ? $supplierInvoice->invoice_date->format('Y-m-d') : '') }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>@lang('modules.invoices.vendor') <span class="text-danger">*</span></label>
                            <select name="vendor_id" class="form-control select-picker" data-live-search="true" required>
                                @foreach($vendors as $v)
                                    <option value="{{ $v->id }}" {{ (old('vendor_id', $supplierInvoice->vendor_id) == $v->id) ? 'selected' : '' }}>{{ $v->primary_name ?? $v->company_name ?? $v->id }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>@lang('app.supplierTotal') (from vendor document)</label>
                            <input type="number" step="0.01" name="supplier_invoice_total" class="form-control" value="{{ old('supplier_invoice_total', $supplierInvoice->supplier_invoice_total) }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>@lang('app.referenceNumber')</label>
                            <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number', $supplierInvoice->reference_number) }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>@lang('app.referenceDate')</label>
                            <input type="date" name="reference_date" class="form-control" value="{{ old('reference_date', $supplierInvoice->reference_date ? $supplierInvoice->reference_date->format('Y-m-d') : '') }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>@lang('app.note')</label>
                            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $supplierInvoice->notes) }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">@lang('app.update')</button>
                    <a href="{{ route('supplier-invoices.show', $supplierInvoice->id) }}" class="btn btn-secondary">@lang('app.cancel')</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('.select-picker').selectpicker();
</script>
@endpush
