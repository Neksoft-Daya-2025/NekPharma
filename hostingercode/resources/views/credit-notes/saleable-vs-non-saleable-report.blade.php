@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-lg-flex d-md-flex d-block justify-content-between action-bar mb-3">
            <h4 class="mb-0">Saleable vs Non-Saleable Product Report</h4>
            <a href="{{ route('creditnotes.index') }}" class="btn btn-secondary btn-sm">@lang('app.back')</a>
        </div>

        <form method="GET" action="{{ route('creditnotes.saleable-vs-non-saleable-report') }}" class="bg-white rounded b-shadow-4 p-3 mb-3">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label>From Date</label>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $filterFromDate ?? $fromDate ?? '' }}">
                </div>
                <div class="col-md-3">
                    <label>To Date</label>
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $filterToDate ?? $toDate ?? '' }}">
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
                            <th>@lang('app.name') (Product)</th>
                            <th class="text-right">Saleable Amount</th>
                            <th class="text-right">Saleable Qty</th>
                            <th class="text-right">Non-Saleable Amount</th>
                            <th class="text-right">Non-Saleable Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportRows ?? [] as $row)
                            <tr>
                                <td>{{ $row['product_name'] }}</td>
                                <td class="text-right">{{ number_format($row['saleable_amount'], 2) }}</td>
                                <td class="text-right">{{ number_format($row['saleable_quantity'], 2) }}</td>
                                <td class="text-right">{{ number_format($row['non_saleable_amount'], 2) }}</td>
                                <td class="text-right">{{ number_format($row['non_saleable_quantity'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">@lang('messages.noRecordFound')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
