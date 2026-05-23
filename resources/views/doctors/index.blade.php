@extends('layouts.app')

{{-- Top #filter-form / client-list-filter strip hidden: filters live in content (inline bar + table) --}}
@section('filter-section')
@endsection

@php
$addDoctorPermission = user()->permission('add_doctors');
$editDoctorPermission = user()->permission('edit_doctors');
$accessibleHqCount = ($headquarters ?? collect())->count();
// If the user is non-admin and has exactly one accessible HQ, default-select that HQ
// instead of "all", so the dropdown reflects the user's real access scope.
$canSeeAllHqOption = user()->hasAdminLikeAccess() || $accessibleHqCount > 1;
$fallbackHqSelection = $canSeeAllHqOption ? 'all' : (string) optional(($headquarters ?? collect())->first())->id;
$selectedHeadquarterInline = request('headquarter_id', $defaultHeadquarterId ?? $fallbackHqSelection);
@endphp

@section('content')
    <div class="d-flex justify-content-end mb-3">
        <x-forms.link-secondary :link="route('doctors.export', request()->query())" class="mr-3" id="doctors-export-link" icon="file-export">
            @lang('app.exportExcel') Doctors
        </x-forms.link-secondary>
    </div>
    <div class="content-wrapper">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        @endif
        <div class="d-lg-flex d-md-flex d-block justify-content-between action-bar">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                @if ($addDoctorPermission == 'all' || $addDoctorPermission == 'added')
                    <x-forms.link-primary :link="route('doctors.create')" class="mr-3 openRightModal" icon="plus">
                        @lang('app.add') Doctor
                    </x-forms.link-primary>
                    <x-forms.link-secondary :link="route('doctors.import')" class="mr-3 openRightModal" icon="upload">
                        @lang('app.importExcel')
                    </x-forms.link-secondary>

                    <x-forms.link-secondary :link="route('import-history.index')" class="mr-3" icon="file-earmark-text">
                        Import History
                    </x-forms.link-secondary>
                @endif
                @if($editDoctorPermission == 'all' || $editDoctorPermission == 'added')
                    <x-forms.link-primary :link="route('doctors.merge-duplicates')" class="mr-3" icon="compress">
                        Merge duplicates
                    </x-forms.link-primary>
                @endif
            </div>
        </div>

        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            <div class="doctors-inline-filters border-bottom px-3 py-3 bg-additional-grey">
                <div class="row align-items-end">
                    <div class="col-lg-3 col-md-6 mb-2 mb-lg-0">
                        <label for="doctors-inline-search" class="f-12 text-dark-grey mb-1 d-block text-capitalize">@lang('app.search')</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text border bg-white"><i class="fa fa-search f-12 text-muted"></i></span>
                            </div>
                            <input type="text" class="form-control height-35 f-14" id="doctors-inline-search"
                                   placeholder="@lang('app.name'), email, mobile…" autocomplete="off" aria-label="@lang('app.search')">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-2 mb-lg-0">
                        <label for="doctors-filter-headquarter" class="f-12 text-dark-grey mb-1 d-block">Headquarter</label>
                        <select class="form-control select-picker height-35 f-14" id="doctors-filter-headquarter" data-live-search="true" title="All" @if(!$canSeeAllHqOption) disabled @endif>
                            @if($canSeeAllHqOption)
                                <option value="all" @selected((string)($selectedHeadquarterInline ?? 'all') === 'all' || ($selectedHeadquarterInline ?? null) === null)>@lang('app.all') Headquarters</option>
                            @endif
                            @foreach(($headquarters ?? collect()) as $hq)
                                <option value="{{ $hq->id }}" @selected((string)($selectedHeadquarterInline ?? '') === (string) $hq->id)>{{ $hq->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-2 mb-lg-0">
                        <label for="doctors-filter-qualification" class="f-12 text-dark-grey mb-1 d-block">Qualification</label>
                        <select class="form-control select-picker height-35 f-14" id="doctors-filter-qualification" data-live-search="true" title="All">
                            <option value="">@lang('app.all')</option>
                            @foreach(($qualificationOptions ?? collect()) as $q)
                                <option value="{{ $q }}">{{ $q }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-2 mb-lg-0">
                        <label for="doctors-filter-speciality" class="f-12 text-dark-grey mb-1 d-block">Speciality</label>
                        <select class="form-control select-picker height-35 f-14" id="doctors-filter-speciality" data-live-search="true" title="All">
                            <option value="">@lang('app.all')</option>
                            @foreach(($specialityOptions ?? collect()) as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <x-forms.button-secondary class="btn-sm height-35" id="doctors-inline-filters-clear" type="button" icon="times-circle">
                            @lang('app.clearFilters')
                        </x-forms.button-secondary>
                    </div>
                </div>
                <small class="text-muted d-block mt-2 mb-0">Filters apply to the doctors listed below.</small>
            </div>
            <div class="doctors-table-scroll">
            <x-table class="table-hover border-0 w-100" headType="thead-light" id="doctors-table">
                <x-slot name="thead">
                    <th>#</th>
                    <th>@lang('app.name')</th>
                    <th>@lang('app.email')</th>
                    <th>Qualification</th>
                    <th>Speciality</th>
                    <th>@lang('app.mobile')</th>
                    <th>Doctor Type (SFC)</th>
                    <th>Headquarter</th>
                    <th>Station Type</th>
                    <th>Station</th>
                    <th>Products</th>
                    <th class="text-right pr-20">@lang('app.action')</th>
                </x-slot>

                @forelse($doctors as $key => $doctor)
                    <tr id="row-{{ $doctor->id }}"
                        data-headquarter-id="{{ $doctor->headquarter_id ?? '' }}"
                        data-qualification="{{ \Illuminate\Support\Str::lower(trim($doctor->qualification ?? '')) }}"
                        data-speciality="{{ \Illuminate\Support\Str::lower(trim($doctor->speciality ?? '')) }}">
                        <td>{{ $key + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                @if($doctor->doctor_pic)
                                    <img src="{{ asset_url_local_s3('doctors/'.$doctor->doctor_pic) }}" class="mr-2 taskEmployeeImg rounded" alt="{{ $doctor->fullname }}">
                                @else
                                    <div class="mr-2 taskEmployeeImg rounded bg-primary text-white d-flex align-items-center justify-content-center">
                                        {{ mb_substr($doctor->fullname, 0, 1) }}
                                    </div>
                                @endif
                                <div class="d-flex flex-column">
                                    <span>{{ $doctor->fullname }}</span>
                                    <span class="badge badge-secondary align-self-start mt-1 f-11" title="Unique doctor record — use this to tell same-name doctors apart">ID {{ $doctor->id }}</span>
                                </div>
                            </div>
                        </td>
                        <td>{{ $doctor->email }}</td>
                        <td>{{ $doctor->qualification }}</td>
                        <td>{{ $doctor->speciality }}</td>
                        <td>{{ $doctor->mobile }}</td>
                        <td>
                            @if($doctor->doctor_type)
                                @php
                                    $badgeClass = match(strtoupper($doctor->doctor_type)) {
                                        'VIP' => 'success',
                                        'CORE' => 'warning',
                                        default => 'info'
                                    };
                                @endphp
                                <span class="badge badge-{{ $badgeClass }}">
                                    {{ $doctor->doctor_type }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-info">{{ optional($doctor->headquarter)->name ?? '-' }}</span>
                        </td>
                        <td>
                            @if($doctor->exstation_id)
                                <span class="badge badge-success">Ex-Station</span>
                            @elseif($doctor->outstation_id)
                                <span class="badge badge-warning">Out-Station</span>
                            @else
                                <span class="badge badge-primary">Headquarter</span>
                            @endif
                        </td>
                        <td>
                            @if($doctor->exstation_id)
                                {{ $doctor->exstation->name ?? '-' }}
                            @elseif($doctor->outstation_id)
                                {{ $doctor->outstation->name ?? '-' }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($doctor->products && $doctor->products->count() > 0)
                                <div class="d-flex flex-wrap">
                                    @foreach($doctor->products->take(3) as $product)
                                        <span class="badge badge-secondary mr-1 mb-1">{{ $product->name }}</span>
                                    @endforeach
                                    @if($doctor->products->count() > 3)
                                        <span class="badge badge-info">+{{ $doctor->products->count() - 3 }} more</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-right pr-20">
                            <div class="task_view">
                                <div class="dropdown">
                                    <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                                       id="dropdownMenuLink-{{ $doctor->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="icon-options-vertical icons"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-{{ $doctor->id }}">
                                        <a class="dropdown-item openRightModal" href="{{ route('doctors.show', $doctor->id) }}">
                                            <i class="fa fa-eye mr-2"></i>@lang('app.view')
                                        </a>
                                        <a class="dropdown-item openRightModal" href="{{ route('doctors.edit', $doctor->id) }}">
                                            <i class="fa fa-edit mr-2"></i>@lang('app.edit')
                                        </a>
                                        <a class="dropdown-item delete-table-row" href="javascript:;" data-doctor-id="{{ $doctor->id }}">
                                            <i class="fa fa-trash mr-2"></i>@lang('app.delete')
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center">
                            <x-cards.no-record icon="users" :message="__('No doctors found')" />
                        </td>
                    </tr>
                @endforelse
            </x-table>
            </div>
            
            <!-- Count reflects doctors currently visible after inline filters. -->
            @if($doctors->isNotEmpty())
                <div class="d-flex justify-content-between align-items-center mt-3 px-3 pb-3">
                    <div class="text-muted">
                        <span id="doctors-visible-count">{{ $doctors->count() }}</span> doctor(s) total
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $('body').on('click', '.delete-table-row', function() {
            const id = $(this).data('doctor-id');
            Swal.fire({
                title: "@lang('messages.sweetAlertTitle')",
                text: "@lang('messages.recoverRecord')",
                icon: 'warning',
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: "@lang('messages.confirmDelete')",
                cancelButtonText: "@lang('app.cancel')",
                customClass: {
                    confirmButton: 'btn btn-primary mr-3',
                    cancelButton: 'btn btn-secondary'
                },
                showClass: {
                    popup: 'swal2-noanimation',
                    backdrop: 'swal2-noanimation'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    let url = "{{ route('doctors.destroy', ':id') }}";
                    url = url.replace(':id', id);

                    const token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        blockUI: true,
                        data: {
                            '_token': token,
                            '_method': 'DELETE'
                        },
                        success: function(response) {
                            if (response.status === "success") {
                                $('#row-' + id).attr('data-deleted', '1').fadeOut(function() {
                                    applyDoctorsTableFilters();
                                });
                            }
                        }
                    });
                }
            });
        });

        function applyDoctorsTableFilters() {
            const search = ($('#doctors-inline-search').val() || '').toLowerCase().trim();

            const hq = $('#doctors-filter-headquarter').val() || 'all';
            const qual = ($('#doctors-filter-qualification').val() || '').toLowerCase().trim();
            const spec = ($('#doctors-filter-speciality').val() || '').toLowerCase().trim();

            let visibleCount = 0;

            $('#doctors-table tbody tr').each(function() {
                const $row = $(this);
                if ($row.find('td[colspan]').length || $row.attr('data-deleted') === '1') {
                    return;
                }
                const text = $row.text().toLowerCase();
                const matchSearch = !search || text.indexOf(search) > -1;
                const rowHq = String($row.attr('data-headquarter-id') || '');
                const matchHq = hq === 'all' || rowHq === String(hq);
                const rowQual = String($row.attr('data-qualification') || '');
                const matchQual = !qual || rowQual === qual;
                const rowSpec = String($row.attr('data-speciality') || '');
                const matchSpec = !spec || rowSpec === spec;
                const isVisible = matchSearch && matchHq && matchQual && matchSpec;
                $row.toggle(isVisible);

                if (isVisible) {
                    visibleCount++;
                }
            });

            $('#doctors-visible-count').text(visibleCount);
            updateDoctorsExportLink();
        }

        function updateDoctorsExportLink() {
            const $exportLink = $('#doctors-export-link');
            const hq = $('#doctors-filter-headquarter').val() || 'all';
            const qual = ($('#doctors-filter-qualification').val() || '').trim();
            const spec = ($('#doctors-filter-speciality').val() || '').trim();
            const search = ($('#doctors-inline-search').val() || '').trim();

            if (!$exportLink.length) {
                return;
            }

            const url = new URL(@json(route('doctors.export')), window.location.origin);
            url.searchParams.set('list_filter', '1');

            if (hq !== 'all') {
                url.searchParams.set('headquarter_id', hq);
            }

            if (qual) {
                url.searchParams.set('qualification', qual);
            }

            if (spec) {
                url.searchParams.set('speciality', spec);
            }

            if (search) {
                url.searchParams.set('search', search);
            }

            $exportLink.attr('href', url.toString());
        }

        $('#doctors-inline-search').on('keyup input', function() {
            applyDoctorsTableFilters();
        });
        $('#doctors-filter-headquarter, #doctors-filter-qualification, #doctors-filter-speciality').on('changed.bs.select', function() {
            applyDoctorsTableFilters();
        });
        $('#doctors-inline-filters-clear').on('click', function() {
            $('#doctors-inline-search').val('');
            $('#doctors-filter-headquarter').val(@json($canSeeAllHqOption ? 'all' : (string) ($selectedHeadquarterInline ?? '')));
            $('#doctors-filter-qualification').val('');
            $('#doctors-filter-speciality').val('');
            if (typeof $.fn.selectpicker === 'function') {
                $('.doctors-inline-filters .select-picker').each(function() {
                    var $el = $(this);
                    if ($el.data('selectpicker')) {
                        $el.selectpicker('refresh');
                    }
                });
            }
            applyDoctorsTableFilters();
        });

        if (typeof $.fn.selectpicker === 'function') {
            $('.doctors-inline-filters .select-picker').each(function() {
                var $el = $(this);
                if (!$el.data('selectpicker')) {
                    $el.selectpicker();
                } else {
                    $el.selectpicker('refresh');
                }
            });
        }
        applyDoctorsTableFilters();
    </script>
@endpush
