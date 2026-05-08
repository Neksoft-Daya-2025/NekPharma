@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-lg-flex d-md-flex d-block justify-content-between action-bar mb-3">
            <h4 class="mb-0">Purchase & Sales Report</h4>
            <a href="{{ route('cfa-ledger.index') }}" class="btn btn-secondary btn-sm">@lang('app.back') to Ledger</a>
        </div>

        <form method="GET" action="{{ route('reports.invoices.purchase-and-sales') }}" class="bg-white rounded b-shadow-4 p-3 mb-3">
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
                    <button type="submit" class="btn btn-primary btn-sm">@lang('app.apply')</button>
                </div>
            </div>
        </form>

        <div class="d-flex flex-column w-tables rounded bg-white p-3">
            <h5 class="mb-3">Summary for period: {{ $fromDate ?? '' }} to {{ $toDate ?? '' }}</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card border">
                        <div class="card-header bg-light"><strong>Purchase</strong> (Purchase Orders)</div>
                        <div class="card-body">
                            <p class="mb-1">Count: <strong>{{ $purchaseCount ?? 0 }}</strong></p>
                            <p class="mb-0">Total: <strong>{{ number_format($purchaseTotal ?? 0, 2) }}</strong></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card border">
                        <div class="card-header bg-light"><strong>Sales</strong></div>
                        <div class="card-body">
                            <p class="mb-1">Company → CFA: <strong>{{ $cfaSalesCount ?? 0 }}</strong> invoices, {{ number_format($cfaSalesTotal ?? 0, 2) }}</p>
                            <p class="mb-1">CFA → Stockist: <strong>{{ $stockistSalesCount ?? 0 }}</strong> invoices, {{ number_format($stockistSalesTotal ?? 0, 2) }}</p>
                            <p class="mb-0 pt-2 border-top"><strong>Total Sales:</strong> {{ $salesCount ?? 0 }} invoices, {{ number_format($salesTotal ?? 0, 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
