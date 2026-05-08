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

        <!-- REPORT BY START -->
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">Report By</p>
            <div class="select-status">
                <select class="form-control select-picker" name="reportBy" id="report_by_id" data-size="8">
                    <option value="headquarters">Headquarters</option>
                    <option value="areas">Areas</option>
                    <option value="regions">Regions</option>
                    <option value="stockists">Stockists</option>
                </select>
            </div>
        </div>
        <!-- REPORT BY END -->

        <!-- HEADQUARTER START -->
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.hq')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="headquarter" id="headquarter_id" data-live-search="true" data-size="8">
                    <option value="">@lang('app.all')</option>
                    @foreach ($headquarters ?? [] as $hq)
                        <option value="{{ $hq->id }}">{{ $hq->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <!-- HEADQUARTER END -->

        <!-- REGION START -->
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">Region</p>
            <div class="select-status">
                <select class="form-control select-picker" name="region" id="region_id" data-live-search="true" data-size="8">
                    <option value="">@lang('app.all')</option>
                    @foreach ($regions ?? [] as $region)
                        <option value="{{ $region->id }}">{{ $region->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <!-- REGION END -->

        <!-- AREA START -->
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">Area</p>
            <div class="select-status">
                <select class="form-control select-picker" name="area" id="area_id" data-live-search="true" data-size="8">
                    <option value="">@lang('app.all')</option>
                    @foreach ($areas ?? [] as $area)
                        <option value="{{ $area->id }}">{{ $area->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <!-- AREA END -->

        <!-- STOCKIST START -->
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">Stockist</p>
            <div class="select-status">
                <select class="form-control select-picker" name="stockist" id="stockist_id" data-live-search="true" data-size="8">
                    <option value="">@lang('app.all')</option>
                    @foreach ($stockists ?? [] as $stockist)
                        <option value="{{ $stockist->id }}">{{ $stockist->shopname ?? $stockist->fullname ?? 'N/A' }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <!-- STOCKIST END -->

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

        <!-- Summary Card -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card border-0 rounded">
                    <div class="card-body">
                        <h6 class="text-muted">Zero Sales Entities</h6>
                        <h4 id="summary-total">0</h4>
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
                reportBy: $('#report_by_id').val() || 'headquarters',
                headquarter: $('#headquarter_id').val() || '',
                region: $('#region_id').val() || '',
                area: $('#area_id').val() || '',
                stockist: $('#stockist_id').val() || ''
            };
        }

        $(function() {
            setDate();
            $('#datatableRange2').on('apply.daterangepicker', function(ev, picker) {
                showTable();
            });
        });

        $('#zero-sales-report-table').on('preXhr.dt', function(e, settings, data) {
            var params = getUrlParams();
            data['startDate'] = params.startDate;
            data['endDate'] = params.endDate;
            data['reportBy'] = params.reportBy;
            data['headquarter'] = params.headquarter;
            data['region'] = params.region;
            data['area'] = params.area;
            data['stockist'] = params.stockist;
        });

        $('#zero-sales-report-table').on('draw.dt', function() {
            var api = window.LaravelDataTables["zero-sales-report-table"];
            var data = api.ajax.json();
            if (data) {
                $('#summary-total').text(data.recordsFiltered ?? data.recordsTotal ?? 0);
            }
        });

        const showTable = () => {
            window.LaravelDataTables["zero-sales-report-table"].draw(true);
        };

        $('.filter-box select').on('change', function() {
            $('#reset-filters').removeClass('d-none');
            showTable();
        });

        $('#reset-filters').click(function() {
            setDate();
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        $('#export-excel-btn').on('click', function(e) {
            e.preventDefault();
            var url = "{{ route('zero-sales-report.export-excel') }}?" + $.param(getUrlParams());
            window.location.href = url;
        });

        $('#export-pdf-btn').on('click', function(e) {
            e.preventDefault();
            var url = "{{ route('zero-sales-report.export-pdf') }}?" + $.param(getUrlParams());
            window.location.href = url;
        });

        $('#export-csv-btn').on('click', function(e) {
            e.preventDefault();
            var url = "{{ route('zero-sales-report.export-csv') }}?" + $.param(getUrlParams());
            window.location.href = url;
        });
    </script>
@endpush
