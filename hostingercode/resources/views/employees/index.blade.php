@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@push('styles')
<style>
    /* Employee profile card view – pharma-centric */
    .employee-profile-card {
        background: linear-gradient(145deg, #fff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        border-left: 4px solid #1976d2;
        border-radius: 10px;
        padding: 1.25rem;
        height: 100%;
        transition: box-shadow 0.2s, border-color 0.2s;
    }
    .employee-profile-card:hover {
        box-shadow: 0 4px 12px rgba(25, 118, 210, 0.12);
        border-left-color: #0d47a1;
    }
    .employee-profile-card .emp-card-avatar-wrap {
        width: 64px;
        height: 64px;
        min-width: 64px;
        min-height: 64px;
        border-radius: 50%;
        overflow: hidden;
        border: 2px solid #e2e8f0;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .employee-profile-card .emp-card-avatar {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .employee-profile-card .emp-card-avatar-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
        color: #fff;
        font-size: 1.5rem;
    }
    .employee-profile-card .emp-card-avatar-placeholder i {
        font-size: 28px;
    }
    .employee-profile-card .emp-card-name {
        font-weight: 600;
        color: #1e293b;
        font-size: 1rem;
    }
    .employee-profile-card .emp-card-id {
        font-size: 0.8rem;
        color: #64748b;
    }
    .employee-profile-card .emp-card-meta {
        font-size: 0.85rem;
        color: #475569;
    }
    .employee-profile-card .emp-card-status {
        font-size: 0.75rem;
        padding: 2px 8px;
        border-radius: 4px;
    }
    .employee-profile-card .emp-card-status.active { background: #e8f5e9; color: #2e7d32; }
    .employee-profile-card .emp-card-status.inactive { background: #ffebee; color: #c62828; }
    #employee-view-toggle .btn.active,
    #employee-view-toggle .employee-view-btn.active { background: #1976d2; color: #fff; border-color: #1976d2; }
    .employees-view { min-height: 200px; }
    .employees-view.hidden-view { display: none !important; }
    #employees-card-view:not(.hidden-view) { display: block !important; background: #fff; border-radius: 8px; padding: 1rem 0; }
    #employees-views-container[data-current-view="card"] #employees-list-view { display: none !important; }
    #employees-views-container[data-current-view="card"] #employees-card-view { display: block !important; }
</style>
@endpush

@section('filter-section')

    <x-filters.filter-box>
        <!-- CLIENT START -->
        <div class="select-box py-2 d-flex pr-2 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.employee')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="employee" id="employee" data-live-search="true"
                        data-size="8" data-html="true">
                    @if ($employees->count() > 1 || in_array('admin', user_roles()))
                        <option value="all">@lang('app.all')</option>
                    @endif
                    @foreach ($employees as $employee)
                        <x-user-option :user="$employee" :employeeSelect="true"/>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- CLIENT END -->

        <!-- DESIGNATION START -->
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.designation')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="designation" id="designation" data-html="true">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($designations as $designation)
                        <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <!-- DESIGNATION END -->


        <!-- SEARCH BY TASK START -->
        <div class="task-search d-flex  py-1 px-lg-3 px-0 border-right-grey align-items-center">
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
        <!-- SEARCH BY TASK END -->

        <!-- RESET START -->
        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0">
            <x-forms.button-secondary class="btn-xs d-none" id="reset-filters" icon="times-circle">
                @lang('app.clearFilters')
            </x-forms.button-secondary>
        </div>
        <!-- RESET END -->

        <!-- MORE FILTERS START -->
        <x-filters.more-filter-box>
            <div class="more-filter-items">
                <label class="f-14 text-dark-grey mb-12 " for="usr">@lang('app.department')</label>
                <div class="select-filter mb-4">
                    <div class="select-others">
                        <select class="form-control select-picker" name="department" data-container="body"
                                id="department" data-html="true">
                            <option value="all">@lang('app.all')</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->team_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="more-filter-items">
                <label class="f-14 text-dark-grey mb-12 " for="usr">@lang('modules.employees.reportingTo')</label>
                <div class="select-filter mb-4">
                    <div class="select-others">
                        <select class="form-control select-picker" name="reporting_employee" id="reporting_employee" data-live-search="true"
                                data-size="8" data-html="true">
                            @if ($employees->count() > 1 || in_array('admin', user_roles()))
                                <option value="all">@lang('app.all')</option>
                            @endif
                            @foreach ($employees as $employee)
                                <x-user-option :user="$employee" :employeeSelect="true"/>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="more-filter-items">
                <label class="f-14 text-dark-grey mb-12 "
                       for="usr">@lang('modules.employees.role')</label>
                <div class="select-filter mb-4">
                    <div class="select-others">
                        <select class="form-control select-picker" name="role" id="role" data-container="body" data-html="true">
                            <option value="all">@lang('app.all')</option>
                            @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="more-filter-items">
                <label class="f-14 text-dark-grey mb-12 " for="usr">@lang('app.status')</label>
                <div class="select-filter mb-4">
                    <div class="select-others">
                        <select class="form-control select-picker" name="status" id="status" data-container="body" data-html="true">
                            <option value="all">@lang('app.all')</option>
                            <option selected value="active">@lang('app.active')</option>
                            <option value="deactive">@lang('app.inactive')</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="more-filter-items">
                <label class="f-14 text-dark-grey mb-12 " for="usr">@lang('modules.employees.gender')</label>
                <div class="select-filter mb-4">
                    <div class="select-others">
                        <select class="form-control select-picker" name="gender" id="gender" data-container="body" data-html="true">
                            <option value="all">@lang('app.all')</option>
                            <option value="male">@lang('app.male')</option>
                            <option value="female">@lang('app.female')</option>
                            <option value="others">@lang('app.others')</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="more-filter-items">
                <label class="f-14 text-dark-grey mb-12 " for="usr">@lang('modules.employees.employmentType')</label>
                <div class="select-filter mb-4">
                    <div class="select-others">
                        <select class="form-control select-picker" name="employmentType" id="employmentType" data-container="body" data-html="true">
                            <option value="all">@lang('app.all')</option>
                            <option value="probation">@lang('app.onProbation')</option>
                            <option value="internship">@lang('app.onInternship')</option>
                            <option value="notice_period">@lang('app.onNoticePeriod')</option>
                            <option value="new_hires">@lang('app.newHires')</option>
                            <option value="long_standing">@lang('app.longStanding')</option>

                        </select>
                    </div>
                </div>
            </div>

        </x-filters.more-filter-box>
        <!-- MORE FILTERS END -->
    </x-filters.filter-box>

@endsection

@php
    $addEmployeePermission = user()->permission('add_employees');
    $addDesignationPermission = user()->permission('add_designation');
    $viewDesignationPermission = user()->permission('view_designation');
@endphp

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        {{-- Employee Onboarding flow (SRS 3.1.1) --}}
        @if ($addEmployeePermission == 'all')
        <div class="alert alert-info border-0 mb-3 py-3 px-4 d-flex align-items-center flex-wrap" style="background: linear-gradient(135deg, #e3f2fd 0%, #e8f4fd 100%); border-left: 4px solid #1976d2;">
            <div class="flex-grow-1 mr-3">
                <strong class="d-block f-14 mb-1"><i class="fa fa-user-plus mr-1"></i> @lang('modules.employees.employeeOnboardingFlow')</strong>
                <span class="f-13 text-dark-grey">@lang('modules.employees.employeeOnboardingDescription')</span>
            </div>
            <a href="{{ route('employees.create') }}" class="btn btn-primary openRightModal mt-2 mt-md-0">
                <i class="fa fa-plus mr-1"></i> @lang('modules.employees.startOnboarding')
            </a>
        </div>
        @endif

        <!-- Add Task Export Buttons Start -->
        <div class="d-flex justify-content-between action-bar">

            <div id="table-actions" class="d-block d-lg-flex align-items-center">
                <div class="btn-group mr-3 mb-2 mb-lg-0" id="employee-view-toggle" role="group">
                    <button type="button" class="btn btn-outline-secondary employee-view-btn active" data-view="list" onclick="window.setEmployeeView && window.setEmployeeView('list'); return false;">
                        <i class="fa fa-list"></i> @lang('app.list')
                    </button>
                    <button type="button" class="btn btn-outline-secondary employee-view-btn" data-view="card" onclick="window.setEmployeeView && window.setEmployeeView('card'); return false;">
                        <i class="fa fa-id-card"></i> Card
                    </button>
                </div>
                @if ($addEmployeePermission == 'all')
                    <x-forms.button-secondary class="mr-3 invite-member mb-2 mb-lg-0" icon="plus">
                        @lang('app.inviteEmployee')
                    </x-forms.button-secondary>
                @endif

                @if ($addEmployeePermission == 'all')
                    <x-forms.link-secondary :link="route('employees.import')" class="mr-3 openRightModal mb-2 mb-lg-0 d-none d-lg-block"
                                            icon="file-upload">
                        @lang('app.importExcel')
                    </x-forms.link-secondary>
                @endif
            </div>

            <x-datatable.actions>
                <div class="select-status mr-3 pl-3">
                    <select name="action_type" class="form-control select-picker" id="quick-action-type" disabled data-html="true">
                        <option value="">@lang('app.selectAction')</option>
                        <option value="change-status">@lang('modules.tasks.changeStatus')</option>
                        <option value="delete">@lang('app.delete')</option>
                    </select>
                </div>
                <div class="select-status mr-3 d-none quick-action-field" id="change-status-action">
                    <select name="status" class="form-control select-picker" data-html="true">
                        <option value="deactive">@lang('app.inactive')</option>
                        <option value="active">@lang('app.active')</option>
                    </select>
                </div>
            </x-datatable.actions>

        </div>
        <!-- Add Task Export Buttons End -->
        <!-- Task Box Start: list and card views (only one visible at a time) -->
        <div id="employees-views-container" class="mt-3" data-current-view="list">
            <div id="employees-list-view" class="employees-view employees-view-list d-flex flex-column w-tables rounded bg-white table-responsive">
                {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}
            </div>
            <div id="employees-card-view" class="employees-view employees-view-cards hidden-view d-none">
                <div id="employees-card-grid" class="row"></div>
                <div id="employees-card-empty" class="text-center py-5 text-muted d-none">
                    <i class="fa fa-users fa-3x mb-3"></i>
                    <p class="mb-0">@lang('messages.noRecordFound')</p>
                </div>
            </div>
        </div>
        <!-- Task Box End -->
    </div>
    <!-- CONTENT WRAPPER END -->

@endsection

@push('scripts')
    @include('sections.datatable_js')

    <script>
        var employeeViewMode = localStorage.getItem('employeeViewMode') || 'list';
        var employeesShowUrl = "{{ url('account/employees') }}";

        function escapeCardText(s) {
            if (s == null || s === '') return '—';
            var div = document.createElement('div');
            div.textContent = s;
            return div.innerHTML;
        }
        function stripHtmlToText(s) {
            if (s == null || s === '') return '—';
            var div = document.createElement('div');
            div.innerHTML = s;
            return (div.textContent || div.innerText || '').trim() || '—';
        }
        function renderEmployeeCards() {
            var j = window.jQuery || window.$;
            if (!j || typeof window.LaravelDataTables === 'undefined' || !window.LaravelDataTables['employees-table']) return;
            var dt = window.LaravelDataTables['employees-table'];
            var rows = dt.rows({ page: 'current' }).data();
            var $grid = j('#employees-card-grid');
            var $empty = j('#employees-card-empty');
            if (!rows.length) {
                $grid.empty();
                $empty.removeClass('d-none');
                return;
            }
            $empty.addClass('d-none');
            var htmlParts = [];
            for (var i = 0; i < rows.length; i++) {
                var r = rows[i];
                var showUrl = employeesShowUrl + '/' + r.id;
                var statusClass = (r.status_plain === 'active') ? 'active' : 'inactive';
                var statusText = (r.status_plain === 'active') ? "{{ __('app.active') }}" : "{{ __('app.inactive') }}";
                var imgUrl = (r.image_url && String(r.image_url).trim() !== '') ? String(r.image_url).replace(/"/g, '&quot;') : '';
                var avatarHtml = '<a href="' + showUrl + '" class="emp-card-avatar-wrap mr-3 d-block">';
                if (imgUrl) {
                    avatarHtml += '<img src="' + imgUrl + '" class="emp-card-avatar" alt="">';
                } else {
                    avatarHtml += '<div class="emp-card-avatar-placeholder"><i class="fa fa-user" aria-hidden="true"></i></div>';
                }
                avatarHtml += '</a>';
                var card = '<div class="col-xl-3 col-lg-4 col-md-6 mb-4">' +
                    '<div class="employee-profile-card">' +
                    '<div class="d-flex align-items-start">' +
                    avatarHtml +
                    '<div class="flex-grow-1 min-width-0">' +
                    '<a href="' + showUrl + '" class="emp-card-name d-block text-truncate">' + escapeCardText(r.employee_name) + '</a>' +
                    '<div class="emp-card-id mt-1">' + escapeCardText(stripHtmlToText(r.employee_id)) + '</div>' +
                    '<div class="emp-card-meta mt-1">' + escapeCardText(r.designation_name) + '</div>' +
                    '<div class="emp-card-meta">' + escapeCardText(r.department_name) + '</div>' +
                    '<span class="emp-card-status ' + statusClass + ' mt-2 d-inline-block">' + statusText + '</span>' +
                    '</div></div>' +
                    '<div class="mt-3 pt-3 border-top-grey">' +
                    '<a href="' + showUrl + '" class="btn btn-sm btn-outline-primary">@lang("app.view")</a>' +
                    '</div></div></div>';
                htmlParts.push(card);
            }
            $grid.html(htmlParts.join(''));
        }

        function setEmployeeView(mode) {
            employeeViewMode = mode;
            localStorage.setItem('employeeViewMode', mode);
            var j = window.jQuery || window.$;
            if (j) {
                j('#employee-view-toggle .employee-view-btn').removeClass('active').filter('[data-view="' + mode + '"]').addClass('active');
                var $listView = j('#employees-list-view');
                var $cardView = j('#employees-card-view');
                if (mode === 'list') {
                    $listView.removeClass('hidden-view d-none').css('display', '');
                    $cardView.addClass('hidden-view d-none').css('display', 'none');
                    j('#employees-views-container').attr('data-current-view', 'list');
                } else {
                    $listView.addClass('hidden-view d-none').css('display', 'none');
                    $cardView.removeClass('hidden-view d-none').removeClass('d-none').css('display', 'block');
                    j('#employees-views-container').attr('data-current-view', 'card');
                    renderEmployeeCards();
                }
            }
        }
        window.setEmployeeView = setEmployeeView;

        var startDate = null;
        var endDate = null;
        var lastStartDate = null;
        var lastEndDate = null;

        @if(request('startDate') != '' && request('endDate') != '' )
            startDate = '{{ request("startDate") }}';
        endDate = '{{ request("endDate") }}';
        @endif

            @if(request('lastStartDate') !=='' && request('lastEndDate') !=='' )
            lastStartDate = '{{ request("lastStartDate") }}';
        lastEndDate = '{{ request("lastEndDate") }}';
        @endif

        $('#employees-table').on('preXhr.dt', function (e, settings, data) {
            const status = $('#status').val();
            const employee = $('#employee').val();
            const role = $('#role').val();
            const gender = $('#gender').val();
            const skill = $('#skill').val();
            const designation = $('#designation').val();
            const department = $('#department').val();
            const employmentType = $('#employmentType').val();
            const reporting_employee = $('#reporting_employee').val();
            const searchText = $('#search-text-field').val();
            data['status'] = status;
            data['employee'] = employee;
            data['role'] = role;
            data['gender'] = gender;
            data['skill'] = skill;
            data['designation'] = designation;
            data['department'] = department;
            data['employmentType'] = employmentType;
            data['reporting_employee'] = reporting_employee;
            data['searchText'] = searchText;

            /* If any of these following filters are applied, then dashboard conditions will not work  */
            if (status == "all" || employee == "all" || role == "all" || designation == "all" || searchText == "") {
                data['startDate'] = startDate;
                data['endDate'] = endDate;
                data['lastStartDate'] = lastStartDate;
                data['lastEndDate'] = lastEndDate;
            }

        });

        const showTable = () => {
            window.LaravelDataTables["employees-table"].draw(true);
        }

        (function($) {
            $(document).ready(function () {
                $(document).off('click.employeeView').on('click.employeeView', '#employee-view-toggle .employee-view-btn', function (e) {
                    e.preventDefault();
                    var view = $(this).data('view');
                    if (view) setEmployeeView(view);
                });

                var $table = $('#employees-table');
                if ($table.length) $table.on('draw.dt', function () {
                    if (employeeViewMode === 'card') renderEmployeeCards();
                });

                if (employeeViewMode === 'card') {
                    $('#employees-list-view').addClass('hidden-view d-none').css('display', 'none');
                    $('#employees-card-view').removeClass('hidden-view d-none').css('display', 'block');
                    $('#employees-views-container').attr('data-current-view', 'card');
                    $('#employee-view-toggle .employee-view-btn').removeClass('active').filter('[data-view="card"]').addClass('active');
                    if (window.LaravelDataTables && window.LaravelDataTables['employees-table']) {
                        setTimeout(function () { renderEmployeeCards(); }, 300);
                    }
                }
            });
        })(window.jQuery || window.$);

        $('#employee, #status, #role, #gender, #skill, #designation, #department, #employmentType, #reporting_employee').on('change keyup',
            function () {
                if ($('#status').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#employee').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#role').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#reporting_employee').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#gender').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#designation').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else if ($('#department').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                }else if ($('#employmentType').val() != "all") {
                    $('#reset-filters').removeClass('d-none');
                } else {
                    $('#reset-filters').addClass('d-none');
                }
                showTable();
            });

        $('#search-text-field').on('keyup', function () {
            if ($('#search-text-field').val() != "") {
                $('#reset-filters').removeClass('d-none');
                showTable();
            }
        });

        $('#reset-filters, #reset-filters-2').click(function () {
            $('#filter-form')[0].reset();
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });


        $('#quick-action-type').change(function () {
            const actionValue = $(this).val();
            if (actionValue != '') {
                $('#quick-action-apply').removeAttr('disabled');

                if (actionValue == 'change-status') {
                    $('.quick-action-field').addClass('d-none');
                    $('#change-status-action').removeClass('d-none');
                } else {
                    $('.quick-action-field').addClass('d-none');
                }
            } else {
                $('#quick-action-apply').attr('disabled', true);
                $('.quick-action-field').addClass('d-none');
            }
        });

        $('#quick-action-apply').click(function () {
            const actionValue = $('#quick-action-type').val();
            if (actionValue == 'delete') {
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
                        applyQuickAction();
                    }
                });

            } else {
                applyQuickAction();
            }
        });

        $('body').on('click', '.delete-table-row', function () {
            var id = $(this).data('user-id');
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
                    var url = "{{ route('employees.destroy', ':id') }}";
                    url = url.replace(':id', id);

                    var token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        blockUI: true,
                        data: {
                            '_token': token,
                            '_method': 'DELETE'
                        },
                        success: function (response) {
                            if (response.status == "success") {
                                showTable();
                            }
                        }
                    });
                }
            });
        });

        const applyQuickAction = () => {
            var rowdIds = $("#employees-table input:checkbox:checked").map(function () {
                return $(this).val();
            }).get();

            var url = "{{ route('employees.apply_quick_action') }}?row_ids=" + rowdIds;

            $.easyAjax({
                url: url,
                container: '#quick-action-form',
                type: "POST",
                disableButton: true,
                buttonSelector: "#quick-action-apply",
                data: $('#quick-action-form').serialize(),
                blockUI: true,
                success: function (response) {
                    if (response.status == 'success') {
                        showTable();
                        resetActionButtons();
                        deSelectAll();
                        $('#quick-action-form').hide();
                    }
                }
            })
        };


        $('body').on('change', '.assign_role', function () {
            var id = $(this).data('user-id');
            var role = $(this).val();
            var token = "{{ csrf_token() }}";

            if (typeof id !== 'undefined') {
                $.easyAjax({
                    url: "{{ route('employees.assign_role') }}",
                    type: "POST",
                    blockUI: true,
                    container: '#employees-table',
                    data: {
                        role: role,
                        userId: id,
                        _token: token
                    },
                    success: function (response) {
                        if (response.status == "success") {
                            window.LaravelDataTables["employees-table"].draw(true);
                        }
                    }
                })
            }

        });

        $('#designation-setting').click(function () {
            const url = "{{ route('designations.create') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        })

        $('.department-setting').click(function () {
            const url = "{{ route('departments.create') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });
    </script>
@endpush
