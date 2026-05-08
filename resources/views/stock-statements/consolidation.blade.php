@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-lg-flex d-md-flex d-block justify-content-between action-bar mb-3">
            <h4 class="mb-0">@lang('app.salesStockStatement') – Consolidation</h4>
            <a href="{{ route('stock-statements.index') }}" class="btn btn-secondary btn-sm">@lang('app.back')</a>
        </div>

        <form method="GET" action="{{ route('stock-statements.consolidation') }}" id="consolidation-filter-form" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
            <div class="consolidation-filter-item">
                <select name="period_month" class="form-control form-control-sm" style="width: 130px; min-height: 31px;">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ (isset($periodMonth) && $periodMonth == $m) ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                    @endfor
                </select>
            </div>
            <div class="consolidation-filter-item">
                <select name="period_year" class="form-control form-control-sm" style="width: 95px; min-height: 31px;">
                    @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                        <option value="{{ $y }}" {{ (isset($periodYear) && $periodYear == $y) ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            @if(isset($zones) && $zones->isNotEmpty())
            <div class="consolidation-filter-item">
                <select name="zone_id" class="form-control form-control-sm select-picker" data-live-search="true" title="All Zones" style="min-width: 160px;">
                    <option value="">All Zones</option>
                    @foreach($zones as $z)
                        <option value="{{ $z->id }}" {{ (isset($filterZoneId) && $filterZoneId == $z->id) ? 'selected' : '' }}>{{ $z->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            @if(isset($regions) && $regions->isNotEmpty())
            <div class="consolidation-filter-item">
                <select name="region_id" class="form-control form-control-sm select-picker" data-live-search="true" title="All Regions" style="min-width: 160px;">
                    <option value="">All Regions</option>
                    @foreach($regions as $r)
                        <option value="{{ $r->id }}" {{ (isset($filterRegionId) && $filterRegionId == $r->id) ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            @if(isset($areas) && $areas->isNotEmpty())
            <div class="consolidation-filter-item">
                <select name="area_id" class="form-control form-control-sm select-picker" data-live-search="true" title="All Areas" style="min-width: 160px;">
                    <option value="">All Areas</option>
                    @foreach($areas as $a)
                        <option value="{{ $a->id }}" {{ (isset($filterAreaId) && $filterAreaId == $a->id) ? 'selected' : '' }}>{{ $a->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            @if(isset($headquarters) && $headquarters->isNotEmpty())
            <div class="consolidation-filter-item">
                <select name="headquarter_id" class="form-control form-control-sm select-picker" data-live-search="true" title="All HQ" style="min-width: 160px;">
                    <option value="">All HQ</option>
                    @foreach($headquarters as $h)
                        <option value="{{ $h->id }}" {{ (isset($filterHeadquarterId) && $filterHeadquarterId == $h->id) ? 'selected' : '' }}>{{ $h->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="consolidation-filter-item">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> @lang('app.apply')</button>
            </div>
        </form>

        <div class="d-flex flex-column w-tables rounded bg-white">
            <div class="table-responsive">
                <table class="table table-bordered table-hover w-100">
                    <thead>
                        <tr>
                            <th>@lang('app.name') (Product)</th>
                            <th class="text-right">Total Opening</th>
                            <th class="text-right">Total Primary</th>
                            <th class="text-right">Total Secondary</th>
                            <th class="text-right">Total Closing</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($consolidationLines ?? [] as $row)
                            <tr>
                                <td>{{ $row->product_name ?? '-' }}</td>
                                <td class="text-right">{{ number_format($row->total_opening ?? 0, 2) }}</td>
                                <td class="text-right">{{ number_format($row->total_primary ?? 0, 2) }}</td>
                                <td class="text-right">{{ number_format($row->total_secondary ?? 0, 2) }}</td>
                                <td class="text-right">{{ number_format($row->total_closing ?? 0, 2) }}</td>
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

@push('scripts')
<script>
$(function() {
    $('.select-picker').selectpicker();
});
</script>
@endpush
