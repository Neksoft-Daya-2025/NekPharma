@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-lg-flex d-md-flex d-block justify-content-between action-bar mb-3">
            <h4 class="mb-0">Target vs Achievement</h4>
            <a href="{{ route('stock-statements.index') }}" class="btn btn-secondary btn-sm">@lang('app.back')</a>
        </div>

        <form method="GET" action="{{ route('stock-statements.target-vs-achievement') }}" class="bg-white rounded b-shadow-4 p-3 mb-3">
            <div class="row align-items-end">
                <div class="col-md-2">
                    <label>Period Month</label>
                    <select name="period_month" class="form-control form-control-sm">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ (isset($periodMonth) && $periodMonth == $m) ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Period Year</label>
                    <select name="period_year" class="form-control form-control-sm">
                        @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                            <option value="{{ $y }}" {{ (isset($periodYear) && $periodYear == $y) ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                @if(isset($headquarters) && $headquarters->isNotEmpty())
                <div class="col-md-3">
                    <label>HQ</label>
                    <select name="headquarter_id" class="form-control form-control-sm">
                        <option value="">All</option>
                        @foreach($headquarters as $h)
                            <option value="{{ $h->id }}" {{ (isset($filterHeadquarterId) && $filterHeadquarterId == $h->id) ? 'selected' : '' }}>{{ $h->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if(isset($products) && $products->isNotEmpty())
                <div class="col-md-3">
                    <label>Product</label>
                    <select name="product_id" class="form-control form-control-sm">
                        <option value="">All</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ (isset($filterProductId) && $filterProductId == $p->id) ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm">@lang('app.apply')</button>
                </div>
            </div>
        </form>

        @php
            $reportTotals = collect($reportRows ?? []);
        @endphp
        <div class="d-flex flex-column w-tables rounded bg-white">
            <div class="table-responsive">
                <table class="table table-bordered table-hover w-100">
                    <thead>
                        <tr>
                            <th>HQ</th>
                            <th>Product</th>
                            <th class="text-right">Target Qty</th>
                            <th class="text-right">Target Amount</th>
                            <th class="text-right">Primary Qty</th>
                            <th class="text-right">Primary Amount</th>
                            <th class="text-right">Primary %</th>
                            <th class="text-right">Secondary Qty</th>
                            <th class="text-right">Secondary Amount</th>
                            <th class="text-right">Secondary %</th>
                            <th class="text-right">Balance Qty</th>
                            <th class="text-right">Balance Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportRows ?? [] as $row)
                            <tr>
                                <td>{{ $row['headquarter_name'] }}</td>
                                <td>{{ $row['product_name'] }}</td>
                                <td class="text-right">{{ number_format($row['target_qty'], 2) }}</td>
                                <td class="text-right">{{ number_format($row['target_amount'], 2) }}</td>
                                <td class="text-right">{{ number_format($row['primary_qty'], 2) }}</td>
                                <td class="text-right">{{ number_format($row['primary_amount'], 2) }}</td>
                                <td class="text-right">{{ $row['primary_qty_pct'] }}% / {{ $row['primary_amount_pct'] }}%</td>
                                <td class="text-right">{{ number_format($row['secondary_qty'], 2) }}</td>
                                <td class="text-right">{{ number_format($row['secondary_amount'], 2) }}</td>
                                <td class="text-right">{{ $row['secondary_qty_pct'] }}% / {{ $row['secondary_amount_pct'] }}%</td>
                                <td class="text-right">{{ number_format($row['balance_qty'], 2) }}</td>
                                <td class="text-right">{{ number_format($row['balance_amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="text-center">@lang('messages.noRecordFound')</td></tr>
                        @endforelse
                    </tbody>
                    @if($reportTotals->isNotEmpty())
                        <tfoot>
                            <tr class="font-weight-bold bg-light">
                                <td colspan="2" class="text-right">Total</td>
                                <td class="text-right">{{ number_format($reportTotals->sum('target_qty'), 2) }}</td>
                                <td class="text-right">{{ number_format($reportTotals->sum('target_amount'), 2) }}</td>
                                <td class="text-right">{{ number_format($reportTotals->sum('primary_qty'), 2) }}</td>
                                <td class="text-right">{{ number_format($reportTotals->sum('primary_amount'), 2) }}</td>
                                <td></td>
                                <td class="text-right">{{ number_format($reportTotals->sum('secondary_qty'), 2) }}</td>
                                <td class="text-right">{{ number_format($reportTotals->sum('secondary_amount'), 2) }}</td>
                                <td></td>
                                <td class="text-right">{{ number_format($reportTotals->sum('balance_qty'), 2) }}</td>
                                <td class="text-right">{{ number_format($reportTotals->sum('balance_amount'), 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
