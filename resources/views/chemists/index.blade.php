@extends('layouts.app')

@section('filter-section')
@endsection

@php
$addChemistPermission = user()->permission('add_chemists');
$editChemistPermission = user()->permission('edit_chemists');
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
                @if ($addChemistPermission == 'all' || $addChemistPermission == 'added')
                    <x-forms.link-primary :link="route('chemists.create')" class="mr-3 openRightModal" icon="plus">
                        @lang('app.add') Chemist
                    </x-forms.link-primary>
                    <x-forms.link-secondary :link="route('chemists.import')" class="mr-3 openRightModal" icon="upload">
                        @lang('app.importExcel')
                    </x-forms.link-secondary>
                @endif
                @if($editChemistPermission == 'all' || $editChemistPermission == 'added')
                    <x-forms.link-primary :link="route('chemists.merge-duplicates')" class="mr-3" icon="compress">
                        Merge duplicates
                    </x-forms.link-primary>
                @endif
            </div>
        </div>

        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            <div class="chemists-inline-filters border-bottom px-3 py-3 bg-additional-grey">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                        <label for="chemists-inline-search" class="f-12 text-dark-grey mb-1 d-block text-capitalize">@lang('app.search')</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text border bg-white"><i class="fa fa-search f-12 text-muted"></i></span>
                            </div>
                            <input type="text" class="form-control height-35 f-14" id="chemists-inline-search"
                                   placeholder="@lang('app.name'), shop, email, mobile…" autocomplete="off" aria-label="@lang('app.search')">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-2 mb-lg-0">
                        <label for="chemists-filter-headquarter" class="f-12 text-dark-grey mb-1 d-block">Headquarter</label>
                        <select class="form-control select-picker height-35 f-14" id="chemists-filter-headquarter" data-live-search="true" title="All" @if(!$canSeeAllHqOption) disabled @endif>
                            @if($canSeeAllHqOption)
                                <option value="all" @selected((string)($selectedHeadquarterInline ?? 'all') === 'all' || ($selectedHeadquarterInline ?? null) === null)>@lang('app.all') Headquarters</option>
                            @endif
                            @foreach(($headquarters ?? collect()) as $hq)
                                <option value="{{ $hq->id }}" @selected((string)($selectedHeadquarterInline ?? '') === (string) $hq->id)>{{ $hq->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-2 mb-lg-0">
                        <label for="chemists-filter-area" class="f-12 text-dark-grey mb-1 d-block">Area</label>
                        <select class="form-control select-picker height-35 f-14" id="chemists-filter-area" data-live-search="true" title="All">
                            <option value="">@lang('app.all')</option>
                            @foreach(($areaOptions ?? collect()) as $a)
                                <option value="{{ $a }}">{{ $a }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-2 mb-lg-0">
                        <label for="chemists-filter-gender" class="f-12 text-dark-grey mb-1 d-block">@lang('app.gender')</label>
                        <select class="form-control select-picker height-35 f-14" id="chemists-filter-gender" data-live-search="true" title="All">
                            <option value="">@lang('app.all')</option>
                            @foreach(($genderOptions ?? collect()) as $g)
                                <option value="{{ $g }}">{{ ucfirst($g) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <x-forms.button-secondary class="btn-sm height-35" id="chemists-inline-filters-clear" type="button" icon="times-circle">
                            @lang('app.clearFilters')
                        </x-forms.button-secondary>
                    </div>
                </div>
                <small class="text-muted d-block mt-2 mb-0">Filters apply to the chemists listed below.</small>
            </div>
            <div class="chemists-table-scroll">
            <x-table class="table-hover border-0 w-100" headType="thead-light" id="chemists-table">
                <x-slot name="thead">
                    <th>#</th>
                    <th>Shop Name</th>
                    <th>@lang('app.name')</th>
                    <th>@lang('app.email')</th>
                    <th>@lang('app.mobile')</th>
                    <th>Area</th>
                    <th>@lang('app.gender')</th>
                    <th class="text-right pr-20">@lang('app.action')</th>
                </x-slot>

                @forelse($chemists as $key => $chemist)
                    @php
                        $areaDisplay = optional($chemist->area)->name ?? trim((string) ($chemist->getRawOriginal('area') ?? ''));
                    @endphp
                    <tr id="row-{{ $chemist->id }}"
                        data-headquarter-id="{{ $chemist->headquarter_id ?? '' }}"
                        data-area="{{ \Illuminate\Support\Str::lower($areaDisplay) }}"
                        data-gender="{{ \Illuminate\Support\Str::lower(trim($chemist->gender ?? '')) }}">
                        <td>{{ $key + 1 }}</td>
                        <td><strong>{{ $chemist->shopname }}</strong></td>
                        <td>{{ $chemist->fullname }}</td>
                        <td>{{ $chemist->email }}</td>
                        <td>{{ $chemist->mobile }}</td>
                        <td>{{ $areaDisplay !== '' ? $areaDisplay : '—' }}</td>
                        <td>{{ ucfirst($chemist->gender) }}</td>
                        <td class="text-right pr-20">
                            <div class="dropdown">
                                <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link" data-toggle="dropdown">
                                    <i class="icon-options-vertical icons"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item openRightModal" href="{{ route('chemists.edit', $chemist->id) }}">
                                        <i class="fa fa-edit mr-2"></i>@lang('app.edit')
                                    </a>
                                    <a class="dropdown-item delete-table-row" href="javascript:;" data-id="{{ $chemist->id }}">
                                        <i class="fa fa-trash mr-2"></i>@lang('app.delete')
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">
                            <x-cards.no-record icon="users" message="No chemists found" />
                        </td>
                    </tr>
                @endforelse
            </x-table>
            </div>
            @if($chemists->isNotEmpty())
                <div class="d-flex justify-content-between align-items-center mt-3 px-3 pb-3">
                    <div class="text-muted">
                        {{ $chemists->count() }} chemist(s) total
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function applyChemistsTableFilters() {
            const search = ($('#chemists-inline-search').val() || '').toLowerCase().trim();
            const hq = $('#chemists-filter-headquarter').val() || 'all';
            const area = ($('#chemists-filter-area').val() || '').toLowerCase().trim();
            const gender = ($('#chemists-filter-gender').val() || '').toLowerCase().trim();

            $('#chemists-table tbody tr').each(function() {
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

        $('#chemists-inline-search').on('keyup input', function() {
            applyChemistsTableFilters();
        });
        $('#chemists-filter-headquarter, #chemists-filter-area, #chemists-filter-gender').on('changed.bs.select', function() {
            applyChemistsTableFilters();
        });
        $('#chemists-inline-filters-clear').on('click', function() {
            $('#chemists-inline-search').val('');
            $('#chemists-filter-headquarter').val(@json($canSeeAllHqOption ? 'all' : (string) ($selectedHeadquarterInline ?? '')));
            $('#chemists-filter-area, #chemists-filter-gender').val('');
            if (typeof $.fn.selectpicker === 'function') {
                $('.chemists-inline-filters .select-picker').each(function() {
                    var $el = $(this);
                    if ($el.data('selectpicker')) {
                        $el.selectpicker('refresh');
                    }
                });
            }
            applyChemistsTableFilters();
        });

        $('body').on('click', '.delete-table-row', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: "@lang('messages.sweetAlertTitle')",
                text: "@lang('messages.recoverRecord')",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "@lang('messages.confirmDelete')",
                cancelButtonText: "@lang('app.cancel')",
                customClass: {
                    confirmButton: 'btn btn-primary mr-3',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = "{{ route('chemists.destroy', ':id') }}";
                    url = url.replace(':id', id);
                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        data: {'_token': '{{ csrf_token() }}', '_method': 'DELETE'},
                        success: function(response) {
                            if (response.status == "success") {
                                $('#row-' + id).fadeOut();
                            }
                        }
                    });
                }
            });
        });

        if (typeof $.fn.selectpicker === 'function') {
            $('.chemists-inline-filters .select-picker').each(function() {
                var $el = $(this);
                if (!$el.data('selectpicker')) {
                    $el.selectpicker();
                } else {
                    $el.selectpicker('refresh');
                }
            });
        }
        applyChemistsTableFilters();
    </script>
@endpush

