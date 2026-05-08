@extends('layouts.app')

@push('styles')
    <style>
        .attendance-total {
            width: 10%;
        }

        .table .thead-light th,
        .table tr td,
        .table h5 {
            font-size: 12px;
        }
        .mw-250{
            min-width: 125px;
        }
    </style>
@endpush

@section('filter-section')

    <x-filters.filter-box>
        <div class="select-box d-flex py-2 pr-2 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.employee')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="user_id" id="user_id" data-live-search="true"
                        data-size="8" data-html="true">
                    @if ($employees->count() > 1 || in_array('admin', user_roles()))
                        <option value="all">@lang('app.all')</option>
                    @endif
                    @forelse ($employees as $item)
                        <x-user-option :user="$item" :employeeSelect="true" :selected="request('employee_id') == $item->id"></x-user-option>
                    @empty
                        <x-user-option :user="user()" :employeeSelect="true" />
                    @endforelse
                </select>
            </div>
        </div>

        @if ($viewAttendancePermission == 'owned')
            <input type="hidden" name="department" id="department" value="all">
            <input type="hidden" name="designation" id="designation" value="all">
        @else
            <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
                <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.department')</p>
                <div class="select-status">
                    <select class="form-control select-picker" name="department" id="department" data-live-search="true"
                            data-size="8" data-html="true">
                        <option value="all">@lang('app.all')</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->team_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
                <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.designation')</p>
                <div class="select-status">
                    <select class="form-control select-picker" name="designation" id="designation" data-live-search="true"
                            data-size="8" data-html="true">
                        <option value="all">@lang('app.all')</option>
                        @foreach ($designations as $designation)
                            <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif

        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.month')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="month" id="month" data-live-search="true"
                        data-size="8" data-html="true">
                    <x-forms.months :selectedMonth="$month" fieldRequired="true"/>
                </select>
            </div>
        </div>

        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.year')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="year" id="year" data-live-search="true" data-size="8" data-html="true">
                    @for ($i = $year; $i >= $year - 4; $i--)
                        <option @if ($i == $year) selected @endif value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <!-- RESET START -->
        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0">
            <x-forms.button-secondary class="btn-xs d-none" id="reset-filters" icon="times-circle">
                @lang('app.clearFilters')
            </x-forms.button-secondary>
        </div>
        <!-- RESET END -->

    </x-filters.filter-box>

@endsection

@php
    $addAttendancePermission = user()->permission('add_attendance');
    $viewAttendancePermission = $viewAttendancePermission ?? user()->permission('view_attendance');
@endphp

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper px-4">

        {{-- Attendance Management flow (SRS 3.1.3) --}}
        <div class="alert alert-info border-0 mb-3 py-3 px-4 d-flex align-items-center flex-wrap" style="background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%); border-left: 4px solid #e65100;">
            <div class="flex-grow-1 mr-4 mb-2 mb-md-0" style="min-width: 0;">
                <strong class="d-block f-14 mb-1"><i class="fa fa-calendar-check-o mr-1"></i> @lang('modules.attendance.attendanceManagementFlow')</strong>
                <span class="f-13 text-dark-grey">@lang('modules.attendance.attendanceManagementDescription')</span>
            </div>
            <div class="d-flex flex-wrap align-items-center mt-2 mt-md-0" style="gap: 1rem;">
                @if ($addAttendancePermission == 'all' || $addAttendancePermission == 'added')
                    <a href="{{ route('attendances.create') }}" class="btn btn-primary openRightModal">
                        <i class="fa fa-plus mr-1"></i> @lang('modules.attendance.markAttendance')
                    </a>
                @endif
                @if (isset($viewAttendancePermission) && $viewAttendancePermission == 'all')
                    <button type="button" class="btn btn-outline-secondary" id="finalise-attendance-btn" data-toggle="tooltip" title="@lang('modules.attendance.finaliseAttendance')">
                        <i class="fa fa-lock mr-1"></i> @lang('modules.attendance.finaliseAttendance')
                    </button>
                    @if (!empty($attendanceFinalisedForCurrentMonth))
                        <span class="badge badge-success align-middle">@lang('modules.attendance.attendanceFinalised')</span>
                    @endif
                @endif
            </div>
        </div>

        <div class="d-grid d-lg-flex d-md-flex action-bar">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                @if (canDataTableExport())
                    <x-forms.button-secondary id="export-all" class="mr-3 mb-2 mb-lg-0" icon="file-export">
                        @lang('app.exportExcel')
                    </x-forms.button-secondary>
                @endif

                @if ($addAttendancePermission == 'all' || $addAttendancePermission == 'added')
                    <x-forms.link-secondary :link="route('attendances.import')" class="mr-3 openRightModal float-left d-none d-lg-block"
                                            icon="file-upload">
                        @lang('app.importExcel')
                    </x-forms.link-secondary>
                @endif
            </div>

            <div class="btn-group mt-2 mt-lg-0 mt-md-0 ml-0 ml-lg-3 ml-md-3" role="group">
                <a href="{{ route('attendances.index') }}" class="btn btn-secondary f-14 btn-active"
                   data-toggle="tooltip"
                   data-original-title="@lang('app.summary')"><i class="side-icon bi bi-list-ul"></i></a>

                <a href="{{ route('attendances.by_member') }}" class="btn btn-secondary f-14" data-toggle="tooltip"
                   data-original-title="@lang('modules.attendance.attendanceByMember')"><i
                        class="side-icon bi bi-person"></i></a>

                <a href="{{ route('attendances.by_hour') }}" class="btn btn-secondary f-14" data-toggle="tooltip"
                   data-original-title="@lang('modules.attendance.attendanceByHour')"><i class="fa fa-clock"></i></a>

                @if (attendance_setting()->save_current_location)
                    <a href="{{ route('attendances.by_map_location') }}" class="btn btn-secondary f-14"
                       data-toggle="tooltip" data-original-title="@lang('modules.attendance.attendanceByLocation')"><i
                            class="fa fa-map-marked-alt"></i></a>
                @endif

            </div>
        </div>

        <!-- Task Box Start -->
        <x-cards.data class="mt-3">
            <div class="row">
                <div class="col-md-12">
                    <span class="f-w-500 mr-1">@lang('app.note'):</span> <i class="fa fa-star text-warning"></i> <i
                        class="fa fa-arrow-right text-lightest f-11 mx-1"></i> @lang('app.menu.holiday') &nbsp;|&nbsp;<i
                        class="fa fa-calendar-week text-red"></i> <i class="fa fa-arrow-right text-lightest f-11 mx-1"></i>
                    @lang('modules.attendance.dayOff') &nbsp;|&nbsp;
                    <i class="fa fa-check text-success"></i> <i class="fa fa-arrow-right text-lightest f-11 mx-1"></i>
                    @lang('modules.attendance.present') &nbsp;|&nbsp; <i class="fa fa-star-half-alt text-red"></i> <i
                        class="fa fa-arrow-right text-lightest f-11 mx-1"></i>
                    @lang('modules.attendance.halfDay') &nbsp;|&nbsp; <i class="fa fa-exclamation-circle text-warning"></i> <i
                        class="fa fa-arrow-right text-lightest f-11 mx-1"></i>
                    @lang('modules.attendance.late') &nbsp;|&nbsp; <i class="fa fa-times text-lightest"></i> <i
                        class="fa fa-arrow-right text-lightest f-11 mx-1"></i>
                    @lang('modules.attendance.absent') &nbsp;|&nbsp; <i class="fa fa-plane-departure text-danger"></i> <i
                        class="fa fa-arrow-right text-lightest f-11 mx-1"></i>
                    @lang('modules.attendance.leave')

                </div>
            </div>

            <div class="row">
                <div class="col-md-12" id="attendance-data"></div>
            </div>
        </x-cards.data>
        <!-- Task Box End -->
    </div>
    <!-- CONTENT WRAPPER END -->

@endsection

@push('scripts')

    <script>

        $('#user_id, #department, #designation, #month, #year').on('change', function () {
            if ($('#user_id').val() != "all") {
                $('#reset-filters').removeClass('d-none');
                showTable();
            } else if ($('#department').val() != "all") {
                $('#reset-filters').removeClass('d-none');
                showTable();
            } else if ($('#designation').val() != "all") {
                $('#reset-filters').removeClass('d-none');
                showTable();
            } else if ($('#month').val() != "all") {
                $('#reset-filters').removeClass('d-none');
                showTable();
            } else if ($('#year').val() != "all") {
                $('#reset-filters').removeClass('d-none');
                showTable();
            } else {
                $('#reset-filters').addClass('d-none');
                showTable();
            }
        });

        $('#reset-filters').click(function () {
            $('#filter-form')[0].reset();
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        function showTable(loading = true) {

            var year = $('#year').val();
            var month = $('#month').val();

            var userId = $('#user_id').val();
            var department = $('#department').val();
            var designation = $('#designation').val();

            //refresh counts
            var url = "{{ route('attendances.index') }}";

            var token = "{{ csrf_token() }}";

            $.easyAjax({
                data: {
                    '_token': token,
                    year: year,
                    month: month,
                    department: department,
                    designation: designation,
                    userId: userId
                },
                url: url,
                blockUI: loading,
                container: '.content-wrapper',
                success: function (response) {
                    $('#attendance-data').html(response.data);
                }
            });

        }


        $('#attendance-data').on('click', '.view-attendance', function () {
            var attendanceID = $(this).data('attendance-id');
            var url = "{{ route('attendances.show', ':attendanceID') }}";
            url = url.replace(':attendanceID', attendanceID);

            $(MODAL_XL + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_XL, url);
        });

        $('#attendance-data').on('click', '.edit-attendance', function (event) {
            var attendanceDate = $(this).data('attendance-date');
            var userData = $(this).closest('tr').children('td:first');
            var userID = $(this).data('user-id');
            var year = $('#year').val();
            var month = $('#month').val();

            var url = "{{ route('attendances.mark', [':userid', ':day', ':month', ':year']) }}";
            url = url.replace(':userid', userID);
            url = url.replace(':day', attendanceDate);
            url = url.replace(':month', month);
            url = url.replace(':year', year);

            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_XL, url);
        });

        function editAttendance(id) {
            var url = "{{ route('attendances.edit', [':id']) }}";
            url = url.replace(':id', id);

            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        }

        function addAttendance(userID) {
            var date = $('#date').val();
            const attendanceDate = date.split("-");
            let dayTime = attendanceDate[2];
            dayTime = dayTime.split(' ');
            let day = dayTime[0];
            let month = attendanceDate[1];
            let year = attendanceDate[0];

            var url = "{{ route('attendances.add-user-attendance', [':userid', ':day', ':month', ':year']) }}";
            url = url.replace(':userid', userID);
            url = url.replace(':day', day);
            url = url.replace(':month', month);
            url = url.replace(':year', year);

            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        }

        @if (isset($viewAttendancePermission) && $viewAttendancePermission == 'all')
            $('#finalise-attendance-btn').on('click', function () {
                var year = $('#year').val();
                var month = $('#month').val();
                if (!year || !month) {
                    Swal.fire({ icon: 'warning', text: 'Please select month and year from the filters.' });
                    return;
                }
                $.easyAjax({
                    type: 'POST',
                    url: "{{ route('attendances.finalise_month') }}",
                    data: { _token: "{{ csrf_token() }}", year: year, month: month },
                    success: function (response) {
                        if (response.status === 'success') {
                            Swal.fire({ icon: 'success', text: response.message });
                            if (!$('.badge-success.align-middle').length) {
                                $('#finalise-attendance-btn').after(' <span class="badge badge-success align-middle mr-2">@lang("modules.attendance.attendanceFinalised")</span>');
                            }
                        }
                    }
                });
            });
        @endif

        showTable(false);
        @if (canDataTableExport())
            $('#export-all').click(function () {
                var year = $('#year').val();
                var month = $('#month').val();
                var department = $('#department').val();
                var designation = $('#designation').val();
                var userId = $('#user_id').val();

                var url =
                    "{{ route('attendances.export_all_attendance', [':year', ':month', ':userId', ':department', ':designation']) }}";
                url = url.replace(':year', year).replace(':month', month).replace(':userId', userId).replace(':department', department).replace(':designation', designation);
                window.location.href = url;

            });
        @endif
    </script>

@endpush
