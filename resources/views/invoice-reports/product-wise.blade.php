@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-lg-flex d-md-flex d-block justify-content-between action-bar mb-3">
            <h4 class="mb-0">Product-wise Invoice Report</h4>
            <a href="{{ route('cfa-ledger.index') }}" class="btn btn-secondary btn-sm">@lang('app.back') to Ledger</a>
        </div>

        <form method="GET" action="{{ route('reports.invoices.product-wise') }}" class="bg-white rounded b-shadow-4 p-3 mb-3">
            <div class="row align-items-end">
                <div class="col-md-2">
                    <label>From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label>To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label>Invoice Type</label>
                    <select name="invoice_type" class="form-control form-control-sm">
                        <option value="company_cfa" {{ ($invoiceType ?? 'company_cfa') == 'company_cfa' ? 'selected' : '' }}>Company → CFA</option>
                        <option value="cfa_stockist" {{ ($invoiceType ?? '') == 'cfa_stockist' ? 'selected' : '' }}>CFA → Stockist</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm">@lang('app.apply')</button>
                </div>
            </div>
        </form>

        <div class="d-flex flex-column w-tables rounded bg-white">
            <div class="table-responsive">
                <table class="table table-bordered table-hover w-100">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-right">Total Quantity</th>
                            <th class="text-right">Total Value</th>
                            <th class="text-center">Invoice Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows ?? [] as $row)
                            <tr>
                                <td>{{ $row['product_name'] }}</td>
                                <td class="text-right">{{ number_format($row['total_quantity'], 2) }}</td>
                                <td class="text-right">{{ number_format($row['total_value'], 2) }}</td>
                                <td class="text-center">{{ $row['invoice_count'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center">@lang('messages.noRecordFound')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
