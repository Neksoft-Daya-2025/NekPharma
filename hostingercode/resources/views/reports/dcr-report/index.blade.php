@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@section('filter-section')
    <x-filters.filter-box>
        <!-- DATE START -->
        <div class="select-box d-flex pr-2 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.duration')</p>
            <div class="select-status d-flex">
                <input type="text" class="position-relative text-dark form-control border-0 p-2 text-left f-14 f-w-500 border-additional-grey"
                    id="datatableRange2" placeholder="@lang('placeholders.dateRange')">
            </div>
        </div>
        <!-- DATE END -->

        <!-- HEADQUARTER START -->
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.hq')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="headquarter" id="headquarter_id" data-live-search="true" data-size="8" data-html="true">
                    <option value="">@lang('app.all')</option>
                    @foreach ($headquarters as $hq)
                        <option value="{{ $hq->id }}">{{ $hq->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <!-- HEADQUARTER END -->

        <!-- STATION TYPE START -->
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.stationType')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="stationType" id="station_type_id" data-size="8" data-html="true">
                    <option value="">@lang('app.all')</option>
                    <option value="HQ">HQ</option>
                    <option value="EX Station">EX Station</option>
                    <option value="Out Station">Out Station</option>
                </select>
            </div>
        </div>
        <!-- STATION TYPE END -->

        <!-- PARTY TYPE START -->
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.partyType')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="partyType" id="party_type_id" data-size="8" data-html="true">
                    <option value="">@lang('app.all')</option>
                    <option value="doctor">Doctor</option>
                    <option value="chemist">Chemist</option>
                    <option value="stockist">Stockist</option>
                </select>
            </div>
        </div>
        <!-- PARTY TYPE END -->

        <!-- REPRESENTATIVE START -->
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.employee')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="employee" id="employee_id" data-live-search="true" data-size="8" data-html="true">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($employees as $employee)
                        <x-user-option :user="$employee" :employeeSelect="true" />
                    @endforeach
                </select>
            </div>
        </div>
        <!-- REPRESENTATIVE END -->

        <!-- RESET START -->
        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0">
            <x-forms.button-secondary class="btn-xs d-none" id="reset-filters" icon="times-circle">
                @lang('app.clearFilters')
            </x-forms.button-secondary>
        </div>
        <!-- RESET END -->
    </x-filters.filter-box>
@endsection

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        <div class="d-flex flex-column">
            <div id="table-actions" class="flex-grow-1 align-items-center mt-4">
                <a href="#" id="export-excel-btn" class="btn btn-light btn-sm mb-2 mb-lg-0 mb-md-0">
                    <i class="fa fa-file-excel"></i> @lang('app.exportExcel')
                </a>
                <a href="#" id="export-pdf-btn" class="btn btn-light btn-sm mb-2 mb-lg-0 mb-md-0">
                    <i class="fa fa-file-pdf"></i> Export PDF
                </a>
                <a href="#" id="export-csv-btn" class="btn btn-light btn-sm mb-2 mb-lg-0 mb-md-0">
                    <i class="fa fa-file-csv"></i> Export CSV
                </a>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card border-0 rounded">
                    <div class="card-body">
                        <h6 class="text-muted">@lang('app.total') Visits</h6>
                        <h4 id="summary-total">0</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 rounded">
                    <div class="card-body">
                        <h6 class="text-muted">Doctor Visits</h6>
                        <h4 id="summary-doctor">0</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 rounded">
                    <div class="card-body">
                        <h6 class="text-muted">Chemist Visits</h6>
                        <h4 id="summary-chemist">0</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 rounded">
                    <div class="card-body">
                        <h6 class="text-muted">Stockist Visits</h6>
                        <h4 id="summary-stockist">0</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-column w-tables rounded mt-4 bg-white">
            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}
        </div>
    </div>
    <!-- CONTENT WRAPPER END -->
@endsection

@push('scripts')
    @include('sections.datatable_js')

    <script type="text/javascript">
        function setDate() {
            var start = moment().clone().startOf('month');
            var end = moment();
            $('#datatableRange2').daterangepicker({
                locale: daterangeLocale,
                linkedCalendars: false,
                startDate: start,
                endDate: end,
                ranges: daterangeConfig
            }, typeof cb === 'function' ? cb : function() {});
        }

        function getUrlParams() {
            var dateRangePicker = $('#datatableRange2').data('daterangepicker');
            var startDate = moment().clone().startOf('month').format('{{ company()->moment_date_format }}');
            var endDate = moment().format('{{ company()->moment_date_format }}');
            if (dateRangePicker && $('#datatableRange2').val()) {
                startDate = dateRangePicker.startDate.format('{{ company()->moment_date_format }}');
                endDate = dateRangePicker.endDate.format('{{ company()->moment_date_format }}');
            }
            return {
                startDate: startDate,
                endDate: endDate,
                headquarter: $('#headquarter_id').val() || '',
                stationType: $('#station_type_id').val() || '',
                partyType: $('#party_type_id').val() || '',
                employee: $('#employee_id').val() || 'all'
            };
        }

        $(function() {
            setDate();
            $('#datatableRange2').on('apply.daterangepicker', function(ev, picker) {
                showTable();
            });
        });

        $('#dcr-report-table').on('preXhr.dt', function(e, settings, data) {
            var params = getUrlParams();
            data['startDate'] = params.startDate;
            data['endDate'] = params.endDate;
            data['headquarter'] = params.headquarter;
            data['stationType'] = params.stationType;
            data['partyType'] = params.partyType;
            data['employee'] = params.employee;
            data['_token'] = '{{ csrf_token() }}';
        });

        $('#dcr-report-table').on('draw.dt', function() {
            var api = window.LaravelDataTables["dcr-report-table"];
            var data = api.ajax.json();
            if (data && data.data) {
                var rows = data.data;
                var total = rows.length;
                var doctor = rows.filter(function(r) { return r.party_type === 'Doctor'; }).length;
                var chemist = rows.filter(function(r) { return r.party_type === 'Chemist'; }).length;
                var stockist = rows.filter(function(r) { return r.party_type === 'Stockist'; }).length;
                $('#summary-total').text(total);
                $('#summary-doctor').text(doctor);
                $('#summary-chemist').text(chemist);
                $('#summary-stockist').text(stockist);
            }
        });

        const showTable = () => {
            window.LaravelDataTables["dcr-report-table"].draw(true);
        };

        $('.filter-box select').on('change', function() {
            $('#reset-filters').removeClass('d-none');
            showTable();
        });

        $('#reset-filters').click(function() {
            $('#filter-form')[0].reset();
            setDate();
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        $('#export-excel-btn').on('click', function(e) {
            e.preventDefault();
            var params = getUrlParams();
            var url = "{{ route('dcr-report.export-excel') }}?" + $.param(params);
            window.location.href = url;
        });

        $('#export-pdf-btn').on('click', function(e) {
            e.preventDefault();
            var params = getUrlParams();
            var url = "{{ route('dcr-report.export-pdf') }}?" + $.param(params);
            window.location.href = url;
        });

        $('#export-csv-btn').on('click', function(e) {
            e.preventDefault();
            var params = getUrlParams();
            var url = "{{ route('dcr-report.export-csv') }}?" + $.param(params);
            window.location.href = url;
        });
    </script>
@endpush
