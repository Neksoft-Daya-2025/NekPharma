@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-lg-flex d-md-flex d-block justify-content-between action-bar">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                <form method="GET" action="{{ route('sales-plan.index') }}" id="sales-plan-filter-form" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <div class="sales-plan-filter-item">
                        <select name="period_month" class="form-control form-control-sm" style="width: 130px; min-height: 31px;">
                            <option value="">All Months</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ (isset($filterMonth) && $filterMonth == $m) ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="sales-plan-filter-item">
                        <select name="period_year" class="form-control form-control-sm" style="width: 95px; min-height: 31px;">
                            <option value="">Year</option>
                            @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                <option value="{{ $y }}" {{ (isset($filterYear) && $filterYear == $y) ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="sales-plan-filter-item">
                        <select name="plan_level" class="form-control form-control-sm" style="width: 110px; min-height: 31px;">
                            <option value="">All Levels</option>
                            <option value="headquarter" {{ (isset($filterPlanLevel) && $filterPlanLevel === 'headquarter') ? 'selected' : '' }}>HQ</option>
                            <option value="area" {{ (isset($filterPlanLevel) && $filterPlanLevel === 'area') ? 'selected' : '' }}>Area</option>
                            <option value="region" {{ (isset($filterPlanLevel) && $filterPlanLevel === 'region') ? 'selected' : '' }}>Region</option>
                        </select>
                    </div>
                    <div class="sales-plan-filter-item">
                        <select name="headquarter_id" class="form-control form-control-sm select-picker" data-live-search="true" title="All HQ" style="min-width: 160px;">
                            <option value="">All HQ</option>
                            @foreach($headquarters as $h)
                                <option value="{{ $h->id }}" {{ (isset($filterHeadquarterId) && $filterHeadquarterId == $h->id) ? 'selected' : '' }}>{{ $h->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sales-plan-filter-item">
                        <select name="area_id" class="form-control form-control-sm select-picker" data-live-search="true" title="All Area" style="min-width: 160px;">
                            <option value="">All Area</option>
                            @foreach($areas as $a)
                                <option value="{{ $a->id }}" {{ (isset($filterAreaId) && $filterAreaId == $a->id) ? 'selected' : '' }}>{{ $a->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sales-plan-filter-item">
                        <select name="region_id" class="form-control form-control-sm select-picker" data-live-search="true" title="All Region" style="min-width: 160px;">
                            <option value="">All Region</option>
                            @foreach($regions as $r)
                                <option value="{{ $r->id }}" {{ (isset($filterRegionId) && $filterRegionId == $r->id) ? 'selected' : '' }}>{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sales-plan-filter-item">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> @lang('app.apply')</button>
                        <a href="{{ route('sales-plan.index') }}" class="btn btn-secondary btn-sm">@lang('app.clearFilters')</a>
                    </div>
                </form>
            </div>
            <div class="mb-2 mb-lg-0 mb-md-0">
                <x-forms.link-primary :link="route('sales-plan.create')" icon="plus">@lang('app.add') @lang('app.salesPlan')</x-forms.link-primary>
            </div>
        </div>

        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            <div class="table-responsive">
                <table class="table table-hover border-0 w-100">
                    <thead>
                        <tr class="border-0">
                            <th>Period</th>
                            <th>Level</th>
                            <th>Scope (HQ / Area / Region)</th>
                            <th>Target Amount</th>
                            <th>Product</th>
                            <th class="text-right">@lang('app.action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($targets as $t)
                            <tr>
                                <td>{{ \Carbon\Carbon::create()->month($t->period_month)->format('F') }} {{ $t->period_year }}</td>
                                <td>{{ ucfirst($t->plan_level) }}</td>
                                <td>{{ $t->scope_name }}</td>
                                <td>{{ number_format($t->target_amount, 2) }}</td>
                                <td>{{ $t->product->name ?? '-' }}</td>
                                <td class="text-right">
                                    <a href="{{ route('sales-plan.edit', $t->id) }}" class="btn btn-sm btn-primary">@lang('app.edit')</a>
                                    <button type="button" class="btn btn-sm btn-danger delete-target" data-id="{{ $t->id }}">@lang('app.delete')</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center">@lang('messages.noRecordFound')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($targets->hasPages())
                <div class="p-3">{{ $targets->links() }}</div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function() {
    $('.select-picker').selectpicker();
    $('body').on('click', '.delete-target', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: "@lang('messages.sweetAlertTitle')",
            text: "@lang('messages.recoverRecord')",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: "@lang('messages.confirmDelete')",
            cancelButtonText: "@lang('app.cancel')"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('sales-plan.destroy', ':id') }}".replace(':id', id),
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function() {
                        window.location.reload();
                    },
                    error: function(xhr) {
                        Swal.fire('@lang('messages.error')', xhr.responseJSON?.message || '@lang('messages.error')', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush
