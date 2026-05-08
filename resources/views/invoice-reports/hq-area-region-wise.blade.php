@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-lg-flex d-md-flex d-block justify-content-between action-bar mb-3">
            <h4 class="mb-0">HQ / Area / Region-wise Invoice Report</h4>
            <a href="{{ route('cfa-ledger.index') }}" class="btn btn-secondary btn-sm">@lang('app.back') to Ledger</a>
        </div>

        <form method="GET" action="{{ route('reports.invoices.hq-area-region-wise') }}" class="bg-white rounded b-shadow-4 p-3 mb-3">
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
                    <label>Group by</label>
                    <select name="segment" class="form-control form-control-sm">
                        <option value="hq" {{ ($segment ?? '') == 'hq' ? 'selected' : '' }}>HQ</option>
                        <option value="area" {{ ($segment ?? 'area') == 'area' ? 'selected' : '' }}>Area</option>
                        <option value="region" {{ ($segment ?? '') == 'region' ? 'selected' : '' }}>Region</option>
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
                            <th>{{ $segment == 'hq' ? 'HQ' : ($segment == 'area' ? 'Area' : 'Region') }}</th>
                            <th class="text-center">Invoice Count</th>
                            <th class="text-right">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows ?? [] as $row)
                            <tr>
                                <td>{{ $row['scope_name'] }}</td>
                                <td class="text-center">{{ $row['invoice_count'] }}</td>
                                <td class="text-right">{{ number_format($row['total_amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center">@lang('messages.noRecordFound')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
