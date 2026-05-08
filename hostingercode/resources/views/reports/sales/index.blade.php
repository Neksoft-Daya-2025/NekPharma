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

        <!-- INVOICE TYPE START -->
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">Invoice Type</p>
            <div class="select-status">
                <select class="form-control select-picker" name="invoiceType" id="invoiceType" data-size="8">
                    <option value="">@lang('app.all')</option>
                    <option value="company_cfa">Company→CFA</option>
                    <option value="cfa_stockist">CFA→Stockist</option>
                </select>
            </div>
        </div>
        <!-- INVOICE TYPE END -->

        <!-- CLIENT START -->
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.client')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="clientID" id="clientID" data-live-search="true" data-size="8">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($clients as $client)
                        <x-user-option :user="$client" />
                    @endforeach
                </select>
            </div>
        </div>
        <!-- CLIENT END -->

        <!-- HQ START -->
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
        <!-- HQ END -->

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

        <!-- PRODUCT START -->
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.product')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="product" id="product_id" data-live-search="true" data-size="8">
                    <option value="">@lang('app.all')</option>
                    @foreach ($products ?? [] as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <!-- PRODUCT END -->

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
            <div class="col-md-4">
                <div class="card border-0 rounded">
                    <div class="card-body">
                        <h6 class="text-muted">@lang('app.total') Invoices</h6>
                        <h4 id="summary-total">0</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 rounded">
                    <div class="card-body">
                        <h6 class="text-muted">@lang('modules.invoices.invoiceValue')</h6>
                        <h4 id="summary-value">0</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 rounded">
                    <div class="card-body">
                        <h6 class="text-muted">@lang('modules.invoices.amountPaid')</h6>
                        <h4 id="summary-paid">0</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-column w-tables rounded mt-4 bg-white table-responsive">
            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}
        </div>
    </div>
    <!-- CONTENT WRAPPER END -->
@endsection

@push('scripts')
    @include('sections.datatable_js')

    <script type="text/javascript">
        function getDate() {
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
                invoiceType: $('#invoiceType').val() || '',
                clientID: $('#clientID').val() || 'all',
                headquarter: $('#headquarter_id').val() || '',
                area: $('#area_id').val() || '',
                region: $('#region_id').val() || '',
                stockist: $('#stockist_id').val() || '',
                product: $('#product_id').val() || ''
            };
        }

        $(function() {
            getDate();
            $('#datatableRange2').on('apply.daterangepicker', function(ev, picker) {
                showTable();
            });
        });

        $('#sales-report-table').on('preXhr.dt', function(e, settings, data) {
            var params = getUrlParams();
            data['startDate'] = params.startDate;
            data['endDate'] = params.endDate;
            data['invoiceType'] = params.invoiceType;
            data['clientID'] = params.clientID;
            data['headquarter'] = params.headquarter;
            data['area'] = params.area;
            data['region'] = params.region;
            data['stockist'] = params.stockist;
            data['product'] = params.product;
        });

        $('#sales-report-table').on('draw.dt', function() {
            var api = window.LaravelDataTables["sales-report-table"];
            var data = api.ajax.json();
            if (data) {
                var s = data.summary || {};
                $('#summary-total').text(s.total_count ?? data.recordsFiltered ?? 0);
                $('#summary-value').text(s.total_value ?? '-');
                $('#summary-paid').text(s.total_paid ?? '-');
            }
        });

        const showTable = () => {
            window.LaravelDataTables["sales-report-table"].draw(true);
        };

        $('.filter-box select').on('change', function() {
            $('#reset-filters').removeClass('d-none');
            showTable();
        });

        $('#reset-filters').click(function() {
            $('#filter-form')[0].reset();
            getDate();
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        $('#export-excel-btn').on('click', function(e) {
            e.preventDefault();
            var url = "{{ route('sales-report.export-excel') }}?" + $.param(getUrlParams());
            window.location.href = url;
        });

        $('#export-pdf-btn').on('click', function(e) {
            e.preventDefault();
            var url = "{{ route('sales-report.export-pdf') }}?" + $.param(getUrlParams());
            window.location.href = url;
        });

        $('#export-csv-btn').on('click', function(e) {
            e.preventDefault();
            var url = "{{ route('sales-report.export-csv') }}?" + $.param(getUrlParams());
            window.location.href = url;
        });
    </script>
@endpush
