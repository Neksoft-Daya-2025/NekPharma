@extends('layouts.app')

@section('filter-section')
@endsection

@php
$addStockistPermission = user()->permission('add_stockists');
$editStockistPermission = user()->permission('edit_stockists');
$accessibleHqCount = ($headquarters ?? collect())->count();
// Non-admin users with exactly one accessible HQ shouldn't see an "All Headquarters" option —
// it is misleading and implies access they don't have.
$canSeeAllHqOption = user()->hasAdminLikeAccess() || $accessibleHqCount > 1;
$fallbackHqSelection = $canSeeAllHqOption ? 'all' : (string) optional(($headquarters ?? collect())->first())->id;
$selectedHeadquarterInline = request('headquarter_id', $defaultHeadquarterId ?? $fallbackHqSelection);
@endphp

@section('content')
    <div class="content-wrapper">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        @endif
        <div class="d-lg-flex d-md-flex d-block justify-content-between action-bar">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                @if ($addStockistPermission == 'all' || $addStockistPermission == 'added')
                    <x-forms.link-primary :link="route('stockists.create')" class="mr-3 openRightModal" icon="plus">
                        @lang('app.add') Stockist
                    </x-forms.link-primary>
                    <x-forms.link-secondary :link="route('stockists.import')" class="mr-3 openRightModal" icon="upload">
                        @lang('app.importExcel')
                    </x-forms.link-secondary>
                @endif
                @if($editStockistPermission == 'all' || $editStockistPermission == 'added')
                    <x-forms.link-primary :link="route('stockists.merge-duplicates')" class="mr-3" icon="compress">
                        Merge duplicates
                    </x-forms.link-primary>
                @endif
            </div>
        </div>

        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            <div class="stockists-inline-filters border-bottom px-3 py-3 bg-additional-grey">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                        <label for="stockists-inline-search" class="f-12 text-dark-grey mb-1 d-block text-capitalize">@lang('app.search')</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text border bg-white"><i class="fa fa-search f-12 text-muted"></i></span>
                            </div>
                            <input type="text" class="form-control height-35 f-14" id="stockists-inline-search"
                                   placeholder="@lang('app.name'), shop, mobile…" autocomplete="off" aria-label="@lang('app.search')">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-2 mb-lg-0">
                        <label for="stockists-filter-headquarter" class="f-12 text-dark-grey mb-1 d-block">Headquarter</label>
                        <select class="form-control select-picker height-35 f-14" id="stockists-filter-headquarter" data-live-search="true" title="All" @if(!$canSeeAllHqOption) disabled @endif>
                            @if($canSeeAllHqOption)
                                <option value="all" @selected((string)($selectedHeadquarterInline ?? 'all') === 'all' || ($selectedHeadquarterInline ?? null) === null)>@lang('app.all') Headquarters</option>
                            @endif
                            @foreach(($headquarters ?? collect()) as $hq)
                                <option value="{{ $hq->id }}" @selected((string)($selectedHeadquarterInline ?? '') === (string) $hq->id)>{{ $hq->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-2 mb-lg-0">
                        <label for="stockists-filter-area" class="f-12 text-dark-grey mb-1 d-block">Area</label>
                        <select class="form-control select-picker height-35 f-14" id="stockists-filter-area" data-live-search="true" title="All">
                            <option value="">@lang('app.all')</option>
                            @foreach(($areaOptions ?? collect()) as $a)
                                <option value="{{ $a }}">{{ $a }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-2 mb-lg-0">
                        <label for="stockists-filter-gender" class="f-12 text-dark-grey mb-1 d-block">@lang('app.gender')</label>
                        <select class="form-control select-picker height-35 f-14" id="stockists-filter-gender" data-live-search="true" title="All">
                            <option value="">@lang('app.all')</option>
                            @foreach(($genderOptions ?? collect()) as $g)
                                <option value="{{ $g }}">{{ ucfirst($g) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <x-forms.button-secondary class="btn-sm height-35" id="stockists-inline-filters-clear" type="button" icon="times-circle">
                            @lang('app.clearFilters')
                        </x-forms.button-secondary>
                    </div>
                </div>
                <small class="text-muted d-block mt-2 mb-0">Filters apply to the stockists listed below.</small>
            </div>
            <div class="stockists-table-scroll">
            <x-table class="table-hover border-0 w-100" headType="thead-light" id="stockists-table">
                <x-slot name="thead">
                    <th>#</th>
                    <th>Shop Name</th>
                    <th>@lang('app.name')</th>
                    <th>@lang('app.mobile')</th>
                    <th>Area</th>
                    <th>Headquarter</th>
                    <th class="text-right pr-20">@lang('app.action')</th>
                </x-slot>

                @forelse($stockists as $key => $stockist)
                    @php
                        $areaDisplay = optional($stockist->area)->name ?? trim((string) ($stockist->getRawOriginal('area') ?? ''));
                        $headquarterName = null;
                        if ($stockist->relationLoaded('headquarter') && $stockist->headquarter) {
                            $headquarterName = $stockist->headquarter->name ?? null;
                        }
                        if (!$headquarterName) {
                            $headquarterName = $stockist->getRawOriginal('headquarter') ?? null;
                        }
                        if (!$headquarterName && $stockist->getAttribute('headquarter') && !is_object($stockist->getAttribute('headquarter'))) {
                            $headquarterName = $stockist->getAttribute('headquarter');
                        }
                    @endphp
                    <tr id="row-{{ $stockist->id }}"
                        data-headquarter-id="{{ $stockist->headquarter_id ?? '' }}"
                        data-area="{{ \Illuminate\Support\Str::lower($areaDisplay) }}"
                        data-gender="{{ \Illuminate\Support\Str::lower(trim($stockist->gender ?? '')) }}">
                        <td>{{ $key + 1 }}</td>
                        <td><strong>{{ $stockist->shopname ?? '--' }}</strong></td>
                        <td>{{ $stockist->fullname ?? ($stockist->getAttribute('fullname') ?? '--') }}</td>
                        <td>{{ $stockist->mobile ?? ($stockist->getAttribute('mobile') ?? '--') }}</td>
                        <td>{{ $areaDisplay !== '' ? $areaDisplay : '—' }}</td>
                        <td>
                            {{ $headquarterName ?? '--' }}
                        </td>
                        <td class="text-right pr-20">
                            <div class="dropdown">
                                <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" 
                                   type="link" data-toggle="dropdown">
                                    <i class="icon-options-vertical icons"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item openRightModal" href="{{ route('stockists.edit', $stockist->id) }}">
                                        <i class="fa fa-edit mr-2"></i>@lang('app.edit')
                                    </a>
                                    <a class="dropdown-item delete-row" href="javascript:;" data-id="{{ $stockist->id }}">
                                        <i class="fa fa-trash mr-2"></i>@lang('app.delete')
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">
                            <x-cards.no-record icon="building" message="No stockists found" />
                        </td>
                    </tr>
                @endforelse
            </x-table>
            </div>
            @if($stockists->isNotEmpty())
                <div class="d-flex justify-content-between align-items-center mt-3 px-3 pb-3">
                    <div class="text-muted">
                        {{ $stockists->count() }} stockist(s) total
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function applyStockistsTableFilters() {
        const search = ($('#stockists-inline-search').val() || '').toLowerCase().trim();
        const hq = $('#stockists-filter-headquarter').val() || 'all';
        const area = ($('#stockists-filter-area').val() || '').toLowerCase().trim();
        const gender = ($('#stockists-filter-gender').val() || '').toLowerCase().trim();

        $('#stockists-table tbody tr').each(function() {
            const $row = $(this);
            if ($row.find('td[colspan]').length) {
                return;
            }
            const text = $row.text().toLowerCase();
            const matchSearch = !search || text.indexOf(search) > -1;
            const rowHq = String($row.attr('data-headquarter-id') || '');
            const matchHq = hq === 'all' || rowHq === String(hq);
            const rowArea = String($row.attr('data-area') || '');
            const matchArea = !area || rowArea === area;
            const rowGender = String($row.attr('data-gender') || '');
            const matchGender = !gender || rowGender === gender;
            $row.toggle(matchSearch && matchHq && matchArea && matchGender);
        });
    }

    $('#stockists-inline-search').on('keyup input', function() {
        applyStockistsTableFilters();
    });
    $('#stockists-filter-headquarter, #stockists-filter-area, #stockists-filter-gender').on('changed.bs.select', function() {
        applyStockistsTableFilters();
    });
    $('#stockists-inline-filters-clear').on('click', function() {
        $('#stockists-inline-search').val('');
        $('#stockists-filter-headquarter').val(@json($canSeeAllHqOption ? 'all' : (string) ($selectedHeadquarterInline ?? '')));
        $('#stockists-filter-area, #stockists-filter-gender').val('');
        if (typeof $.fn.selectpicker === 'function') {
            $('.stockists-inline-filters .select-picker').each(function() {
                var $el = $(this);
                if ($el.data('selectpicker')) {
                    $el.selectpicker('refresh');
                }
            });
        }
        applyStockistsTableFilters();
    });

    $('body').on('click', '.delete-row', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: "@lang('messages.sweetAlertTitle')",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: "@lang('messages.confirmDelete')",
        }).then((result) => {
            if (result.isConfirmed) {
                $.easyAjax({
                    type: 'POST',
                    url: "{{ route('stockists.destroy', ':id') }}".replace(':id', id),
                    data: {'_token': '{{ csrf_token() }}', '_method': 'DELETE'},
                    success: function(response) {
                        $('#row-' + id).fadeOut();
                    }
                });
            }
        });
    });

    if (typeof $.fn.selectpicker === 'function') {
        $('.stockists-inline-filters .select-picker').each(function() {
            var $el = $(this);
            if (!$el.data('selectpicker')) {
                $el.selectpicker();
            } else {
                $el.selectpicker('refresh');
            }
        });
    }
    applyStockistsTableFilters();
</script>
@endpush

