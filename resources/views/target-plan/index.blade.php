@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-lg-flex d-md-flex d-block justify-content-between action-bar">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                <form method="GET" action="{{ route('target-plan.index') }}" id="sales-plan-filter-form" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
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
                        <select name="headquarter_id" class="form-control form-control-sm select-picker" data-live-search="true" title="All HQ" style="min-width: 160px;">
                            <option value="">All HQ</option>
                            @foreach($headquarters as $h)
                                <option value="{{ $h->id }}" {{ (isset($filterHeadquarterId) && $filterHeadquarterId == $h->id) ? 'selected' : '' }}>{{ $h->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sales-plan-filter-item">
                        <select name="product_id" class="form-control form-control-sm select-picker" data-live-search="true" title="All Products" style="min-width: 180px;">
                            <option value="">All Products</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" {{ (isset($filterProductId) && $filterProductId == $p->id) ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sales-plan-filter-item">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> @lang('app.apply')</button>
                        <a href="{{ route('target-plan.index') }}" class="btn btn-secondary btn-sm">@lang('app.clearFilters')</a>
                    </div>
                </form>
            </div>
            <div class="mb-2 mb-lg-0 mb-md-0 d-flex flex-wrap">
                <a href="{{ route('target-plan.export', request()->query()) }}" class="btn btn-sm btn-secondary mr-2 mb-2 mb-md-0">
                    <i class="fa fa-download"></i> @lang('app.exportExcel')
                </a>
                @if(user()->hasAdminLikeAccess())
                    <x-forms.link-primary :link="route('target-plan.create')" icon="plus">@lang('app.add') Target Plan</x-forms.link-primary>
                @endif
            </div>
        </div>

        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            <div class="table-responsive">
                <table class="table table-hover border-0 w-100">
                    <thead>
                        <tr class="border-0">
                            <th>Period</th>
                            <th>Headquarter</th>
                            <th>Product</th>
                            <th class="text-right">Target Qty</th>
                            <th class="text-right">Target Amount</th>
                            <th class="text-right">@lang('app.action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($targets as $t)
                            <tr>
                                <td>{{ \Carbon\Carbon::create()->month($t->period_month)->format('F') }} {{ $t->period_year }}</td>
                                <td>{{ $t->scope_name }}</td>
                                <td>{{ $t->product->name ?? '-' }}</td>
                                <td class="text-right">{{ number_format($t->target_qty ?? 0, 2) }}</td>
                                <td class="text-right">{{ number_format($t->target_amount, 2) }}</td>
                                <td class="text-right">
                                    @if(user()->hasAdminLikeAccess())
                                        <a href="{{ route('target-plan.edit', $t->id) }}" class="btn btn-sm btn-primary">@lang('app.edit')</a>
                                        <button type="button" class="btn btn-sm btn-danger delete-target" data-id="{{ $t->id }}">@lang('app.delete')</button>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center">@lang('messages.noRecordFound')</td></tr>
                        @endforelse
                    </tbody>
                    @if(($targets ?? collect())->count() > 0)
                        <tfoot>
                            <tr class="font-weight-bold bg-light">
                                <td colspan="3" class="text-right">Total</td>
                                <td class="text-right">{{ number_format($totalTargetQty ?? 0, 2) }}</td>
                                <td class="text-right">{{ number_format($totalTargetAmount ?? 0, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
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
                    url: "{{ route('target-plan.destroy', ':id') }}".replace(':id', id),
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
