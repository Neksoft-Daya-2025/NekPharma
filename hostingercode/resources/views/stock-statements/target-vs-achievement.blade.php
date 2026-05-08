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
                <div class="col-md-2">
                    <label>Level</label>
                    <select name="plan_level" class="form-control form-control-sm">
                        <option value="">All</option>
                        <option value="headquarter" {{ (isset($filterPlanLevel) && $filterPlanLevel === 'headquarter') ? 'selected' : '' }}>HQ</option>
                        <option value="area" {{ (isset($filterPlanLevel) && $filterPlanLevel === 'area') ? 'selected' : '' }}>Area</option>
                        <option value="region" {{ (isset($filterPlanLevel) && $filterPlanLevel === 'region') ? 'selected' : '' }}>Region</option>
                    </select>
                </div>
                @if(isset($headquarters) && $headquarters->isNotEmpty())
                <div class="col-md-2">
                    <label>HQ</label>
                    <select name="headquarter_id" class="form-control form-control-sm">
                        <option value="">All</option>
                        @foreach($headquarters as $h)
                            <option value="{{ $h->id }}" {{ (isset($filterHeadquarterId) && $filterHeadquarterId == $h->id) ? 'selected' : '' }}>{{ $h->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if(isset($areas) && $areas->isNotEmpty())
                <div class="col-md-2">
                    <label>Area</label>
                    <select name="area_id" class="form-control form-control-sm">
                        <option value="">All</option>
                        @foreach($areas as $a)
                            <option value="{{ $a->id }}" {{ (isset($filterAreaId) && $filterAreaId == $a->id) ? 'selected' : '' }}>{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                @if(isset($regions) && $regions->isNotEmpty())
                <div class="col-md-2">
                    <label>Region</label>
                    <select name="region_id" class="form-control form-control-sm">
                        <option value="">All</option>
                        @foreach($regions as $r)
                            <option value="{{ $r->id }}" {{ (isset($filterRegionId) && $filterRegionId == $r->id) ? 'selected' : '' }}>{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
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
                            <th>Level</th>
                            <th>Scope</th>
                            <th class="text-right">Target (Amount)</th>
                            <th class="text-right">Primary Achievement (Invoicing)</th>
                            <th class="text-right">Primary %</th>
                            <th class="text-right">Secondary Achievement (Stock Statement Qty)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reportRows ?? [] as $row)
                            <tr>
                                <td>{{ ucfirst($row['plan_level']) }}</td>
                                <td>{{ $row['scope_name'] }}</td>
                                <td class="text-right">{{ number_format($row['target'], 2) }}</td>
                                <td class="text-right">{{ number_format($row['primary_achievement'], 2) }}</td>
                                <td class="text-right">{{ $row['primary_pct'] }}%</td>
                                <td class="text-right">{{ number_format($row['secondary_achievement'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center">@lang('messages.noRecordFound')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
