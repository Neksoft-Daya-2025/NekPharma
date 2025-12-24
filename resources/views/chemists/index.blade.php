@extends('layouts.app')

@section('filter-section')
    @php
        $headquarterOptions = $headquarters ?? collect();
        $headquarterStationsMap = $headquarterStations ?? [];
        $defaultHeadquarterId = $defaultHeadquarterId ?? null;
        $selectedHeadquarter = request('headquarter_id', $defaultHeadquarterId ?? 'all');
        $selectedStation = request('station', 'all');
    @endphp

    <x-filters.filter-box>
        <!-- SEARCH START -->
        <div class="task-search d-flex py-1 px-lg-3 px-0 border-right-grey align-items-center">
            <form class="w-100 mr-1 mr-lg-0 mr-md-1 ml-md-1 ml-0 ml-lg-0">
                <div class="input-group bg-grey rounded">
                    <div class="input-group-prepend">
                        <span class="input-group-text border-0 bg-additional-grey">
                            <i class="fa fa-search f-13 text-dark-grey"></i>
                        </span>
                    </div>
                    <input type="text" class="form-control f-14 p-1 border-additional-grey" id="search-text-field"
                           placeholder="@lang('app.startTyping')">
                </div>
            </form>
        </div>
        <!-- SEARCH END -->

        <!-- HEADQUARTER FILTER START -->
        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0 border-right-grey">
            <div class="select-status mr-3 pl-3">
                <select class="form-control select-picker" name="headquarter_id" id="headquarter_filter" data-live-search="true">
                    <option value="all" @selected($selectedHeadquarter === 'all' || $selectedHeadquarter === null)>All Headquarters</option>
                    @foreach($headquarterOptions as $hq)
                        <option value="{{ $hq->id }}" @selected((string) $selectedHeadquarter === (string) $hq->id)>
                            {{ $hq->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <!-- HEADQUARTER FILTER END -->

        <!-- STATION FILTER START (Shows after HQ selected) -->
        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0 border-right-grey" id="station_filter_box" style="display: none;">
            <div class="select-status mr-3 pl-3">
                <select class="form-control select-picker" name="station" id="station_filter" data-live-search="true">
                    <option value="all">All Stations</option>
                    <!-- Options will be loaded dynamically -->
                </select>
            </div>
        </div>
        <!-- STATION FILTER END -->

        <!-- RESET START -->
        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0">
            <x-forms.button-secondary class="btn-xs" id="reset-filters" icon="times-circle">
                @lang('app.clearFilters')
            </x-forms.button-secondary>
        </div>
        <!-- RESET END -->
    </x-filters.filter-box>
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="d-lg-flex d-md-flex d-block justify-content-between action-bar">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                @if(user()->permission('add_chemists') == 'all' || user()->permission('add_chemists') == 'added')
                    <x-forms.link-primary :link="route('chemists.create')" class="mr-3 openRightModal" icon="plus">
                        @lang('app.add') Chemist
                    </x-forms.link-primary>
                    <x-forms.link-primary :link="route('chemists.import')" class="mr-3 openRightModal" icon="upload">
                        @lang('app.importExcel')
                    </x-forms.link-primary>
                @endif
            </div>
        </div>

        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            <x-table class="table-hover border-0 w-100" headType="thead-light">
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
                    <tr id="row-{{ $chemist->id }}">
                        <td>{{ $key + 1 }}</td>
                        <td><strong>{{ $chemist->shopname }}</strong></td>
                        <td>{{ $chemist->fullname }}</td>
                        <td>{{ $chemist->email }}</td>
                        <td>{{ $chemist->mobile }}</td>
                        <td>{{ $chemist->area }}</td>
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
    </div>
@endsection

@push('scripts')
    <script>
        const headquarterStations = @json($headquarterStationsMap ?? []);
        const selectedHeadquarter = @json($selectedHeadquarter);
        const selectedStation = @json($selectedStation);

        const getHeadquarterData = (hqId) => {
            if (!hqId || !headquarterStations) {
                return { exstations: [], outstations: [] };
            }

            return headquarterStations[hqId] || headquarterStations[String(hqId)] || { exstations: [], outstations: [] };
        };

        const populateStationFilter = (hqId, stationValue = 'all') => {
            const $stationFilter = $('#station_filter');
            const data = getHeadquarterData(hqId);

            $stationFilter.empty();
            $stationFilter.append('<option value="all">All Stations</option>');

            if (hqId && hqId !== 'all') {
                $stationFilter.append('<option value="hq">Headquarter</option>');

                if (data.exstations && data.exstations.length) {
                    data.exstations.forEach(station => {
                        $stationFilter.append(`<option value="ex-${station.id}">${station.name} (Ex-Station)</option>`);
                    });
                }

                if (data.outstations && data.outstations.length) {
                    data.outstations.forEach(station => {
                        $stationFilter.append(`<option value="out-${station.id}">${station.name} (Out-Station)</option>`);
                    });
                }
            }

            $stationFilter.val(stationValue);
            $stationFilter.selectpicker('refresh');
        };

        const updateStationFilterVisibility = (hqId, stationValue = 'all') => {
            if (hqId && hqId !== 'all') {
                $('#station_filter_box').show();
                populateStationFilter(hqId, stationValue);
            } else {
                $('#station_filter_box').hide();
                $('#station_filter').empty().selectpicker('refresh');
            }
        };

        const applyFilters = () => {
            const headquarterId = $('#headquarter_filter').val();
            const station = $('#station_filter').val();
            let url = "{{ route('chemists.index') }}";
            const params = [];

            if (headquarterId && headquarterId !== 'all') {
                params.push('headquarter_id=' + headquarterId);

                if (station && station !== 'all') {
                    params.push('station=' + station);
                }
            }

            if (params.length > 0) {
                url += '?' + params.join('&');
            }

            window.location.href = url;
        };

        $('#headquarter_filter').on('change', function() {
            const headquarterId = $(this).val();

            if (headquarterId && headquarterId !== 'all') {
                updateStationFilterVisibility(headquarterId);
                applyFilters();
            } else {
                window.location.href = "{{ route('chemists.index') }}";
            }
        });

        $('#station_filter').on('change', function() {
            applyFilters();
        });

        $('#reset-filters').on('click', function() {
            window.location.href = "{{ route('chemists.index') }}";
        });

        if (selectedHeadquarter && selectedHeadquarter !== 'all') {
            $('#headquarter_filter').val(selectedHeadquarter).selectpicker('refresh');
            updateStationFilterVisibility(selectedHeadquarter, selectedStation);
        } else {
            $('#station_filter_box').hide();
        }

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

        $('#search-text-field').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    </script>
@endpush

