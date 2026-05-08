@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-lg-flex d-md-flex d-block justify-content-between action-bar">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                <form method="GET" action="{{ route('stock-statements.index') }}" id="stock-statements-filter-form" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    <div class="stock-statement-filter-item">
                        <select name="period_month" class="form-control form-control-sm" style="width: 120px; min-height: 31px;">
                            <option value="">All Months</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ (isset($filterMonth) && $filterMonth == $m) ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="stock-statement-filter-item">
                        <select name="period_year" class="form-control form-control-sm" style="width: 100px; min-height: 31px;">
                            <option value="">Year</option>
                            @for($y = date('Y'); $y >= date('Y') - 5; $y--)
                                <option value="{{ $y }}" {{ (isset($filterYear) && $filterYear == $y) ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="stock-statement-filter-item">
                        <select name="cfa_stockist_id" class="form-control form-control-sm select-picker" data-live-search="true" title="All Stockists" style="min-width: 180px;">
                            <option value="">All Stockists</option>
                            @foreach($cfaStockists as $s)
                                <option value="{{ $s->id }}" {{ (isset($filterStockistId) && $filterStockistId == $s->id) ? 'selected' : '' }}>{{ $s->shopname }} ({{ $s->cfa_stockist_id }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="stock-statement-filter-item">
                        <select name="status" class="form-control form-control-sm" style="width: 120px; min-height: 31px;">
                            <option value="">All Status</option>
                            <option value="draft" {{ (isset($filterStatus) && $filterStatus === 'draft') ? 'selected' : '' }}>Draft</option>
                            <option value="submitted" {{ (isset($filterStatus) && $filterStatus === 'submitted') ? 'selected' : '' }}>Submitted</option>
                        </select>
                    </div>
                    <div class="stock-statement-filter-item">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> @lang('app.apply')</button>
                        <a href="{{ route('stock-statements.index') }}" class="btn btn-secondary btn-sm">@lang('app.clearFilters')</a>
                    </div>
                </form>
            </div>
            <div class="mb-2 mb-lg-0 mb-md-0">
                <x-forms.link-primary :link="route('stock-statements.create')" icon="plus">@lang('app.add') @lang('app.salesStockStatement')</x-forms.link-primary>
            </div>
        </div>

        @if(isset($missingStockistsForPeriod) && $missingStockistsForPeriod->isNotEmpty() && isset($mandatoryPeriodMonth) && isset($mandatoryPeriodYear))
            <div class="alert alert-warning mt-3 mb-0" role="alert">
                <strong><i class="fa fa-exclamation-triangle"></i> @lang('app.mandatoryStockStatementTitle', ['period' => \Carbon\Carbon::create()->month($mandatoryPeriodMonth)->format('F') . ' ' . $mandatoryPeriodYear])</strong>
                <p class="mb-2 mt-1">@lang('app.mandatoryStockStatementText')</p>
                <ul class="mb-0 pl-3">
                    @foreach($missingStockistsForPeriod as $ms)
                        <li>
                            <a href="{{ route('stock-statements.create', ['period_month' => $mandatoryPeriodMonth, 'period_year' => $mandatoryPeriodYear, 'cfa_stockist_id' => $ms->id]) }}">{{ $ms->shopname }} ({{ $ms->cfa_stockist_id ?? '-' }})</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            <div class="table-responsive">
                <table class="table table-hover border-0 w-100">
                    <thead>
                        <tr class="border-0">
                            <th>Period</th>
                            <th>Stockist</th>
                            <th>MR</th>
                            <th>Status</th>
                            <th>Submitted At</th>
                            <th class="text-right">@lang('app.action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($statements as $st)
                            <tr>
                                <td>{{ \Carbon\Carbon::create()->month($st->period_month)->format('F') }} {{ $st->period_year }}</td>
                                <td>{{ $st->cfaStockist->shopname ?? '-' }} ({{ $st->cfaStockist->cfa_stockist_id ?? '-' }})</td>
                                <td>{{ $st->user->name ?? '-' }}</td>
                                <td><span class="badge badge-{{ $st->status === 'submitted' ? 'success' : 'secondary' }}">{{ ucfirst($st->status) }}</span></td>
                                <td>{{ $st->submitted_at ? $st->submitted_at->format('d M Y H:i') : '-' }}</td>
                                <td class="text-right">
                                    <a href="{{ route('stock-statements.show', $st->id) }}" class="btn btn-sm btn-secondary">@lang('app.view')</a>
                                    @if($st->status === 'draft' && $st->user_id == user()->id)
                                        <a href="{{ route('stock-statements.edit', $st->id) }}" class="btn btn-sm btn-primary">@lang('app.edit')</a>
                                        <button type="button" class="btn btn-sm btn-danger delete-statement" data-id="{{ $st->id }}">@lang('app.delete')</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center">@lang('messages.noRecordFound')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($statements->hasPages())
                <div class="p-3">{{ $statements->links() }}</div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function() {
    $('.select-picker').selectpicker();
    $('body').on('click', '.delete-statement', function() {
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
                    url: "{{ route('stock-statements.destroy', ':id') }}".replace(':id', id),
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
