@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@section('filter-section')
    <x-filters.filter-box>
        <div class="select-box d-flex pr-2 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.duration')</p>
            <div class="select-status d-flex">
                <input type="text" class="position-relative text-dark form-control border-0 p-2 text-left f-14 f-w-500 border-additional-grey"
                    id="datatableRange2" placeholder="@lang('placeholders.dateRange')">
            </div>
        </div>

        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.employee')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="employee_id" id="employee_id" data-live-search="true" data-html="true" data-size="8">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($employeesForFilter ?? [] as $emp)
                        @php
                            $code = $emp['employee_id'] ?? '';
                            $name = $emp['name'] ?? '';
                            $des = $emp['designation'] ?? $emp['designation_name'] ?? '-';
                            $plain = ($code !== '' && $code !== null) ? ($code . ' - ' . $name . ' (' . $des . ')') : ($name . ' (' . $des . ')');
                            $html = ($code !== '' && $code !== null)
                                ? '<span class="font-weight-bold">' . e($code) . '</span> - ' . e($name) . ' (' . e($des) . ')'
                                : e($name) . ' (' . e($des) . ')';
                        @endphp
                        <option value="{{ $emp['id'] }}" data-content="{!! $html !!}">{{ $plain }}</option>
                    @endforeach
                </select>
            </div>
        </div>

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

        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0 align-items-center">
            <p class="mb-0 pr-2 f-14 text-dark-grey">@lang('app.deviationTypes')</p>
            <div class="d-flex flex-wrap align-items-center">
                <label class="mb-0 mr-3 f-12">
                    <input type="checkbox" id="type_missing" name="type_missing" value="1" checked class="mr-1">
                    {{ __('app.tpDeviationTypes.missing_dcr') }}
                </label>
                <label class="mb-0 mr-3 f-12">
                    <input type="checkbox" id="type_mismatch" name="type_mismatch" value="1" checked class="mr-1">
                    {{ __('app.tpDeviationTypes.field_mismatch') }}
                </label>
                <label class="mb-0 mr-3 f-12">
                    <input type="checkbox" id="type_unplanned" name="type_unplanned" value="1" checked class="mr-1">
                    {{ __('app.tpDeviationTypes.unplanned_dcr') }}
                </label>
            </div>
        </div>

        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0 align-items-center">
            <label class="mb-0 f-14 text-dark-grey d-flex align-items-center">
                <input type="checkbox" id="show_unplanned" name="show_unplanned" value="1" class="mr-2">
                @lang('app.showUnplannedDcrs')
            </label>
        </div>

        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0">
            <x-forms.button-secondary class="btn-xs d-none" id="reset-filters" icon="times-circle">
                @lang('app.clearFilters')
            </x-forms.button-secondary>
        </div>
    </x-filters.filter-box>
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="d-flex flex-column">
            <div id="table-actions" class="flex-grow-1 align-items-center mt-4">
                <a href="#" id="export-excel-btn" class="btn btn-light btn-sm mb-2 mb-lg-0 mb-md-0">
                    <i class="fa fa-file-excel"></i> @lang('app.exportExcel')
                </a>
            </div>
        </div>

        <div class="d-flex flex-column w-tables rounded mt-4 bg-white">
            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}
        </div>
    </div>
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
                employee_id: $('#employee_id').val() || 'all',
                headquarter: $('#headquarter_id').val() || '',
                type_missing: $('#type_missing').is(':checked') ? 1 : 0,
                type_mismatch: $('#type_mismatch').is(':checked') ? 1 : 0,
                type_unplanned: $('#type_unplanned').is(':checked') ? 1 : 0,
                show_unplanned: $('#show_unplanned').is(':checked') ? 1 : 0,
            };
        }

        $(function() {
            setDate();
            $('#datatableRange2').on('apply.daterangepicker', function(ev, picker) {
                showTable();
            });
        });

        $('#tp-deviation-report-table').on('preXhr.dt', function(e, settings, data) {
            var params = getUrlParams();
            data['startDate'] = params.startDate;
            data['endDate'] = params.endDate;
            data['employee_id'] = params.employee_id;
            data['headquarter'] = params.headquarter;
            data['type_missing'] = params.type_missing;
            data['type_mismatch'] = params.type_mismatch;
            data['type_unplanned'] = params.type_unplanned;
            data['show_unplanned'] = params.show_unplanned;
        });

        const showTable = () => {
            window.LaravelDataTables["tp-deviation-report-table"].draw(true);
        };

        $('.filter-box select').on('change', function() {
            $('#reset-filters').removeClass('d-none');
            showTable();
        });

        $('#type_missing, #type_mismatch, #type_unplanned, #show_unplanned').on('change', function() {
            $('#reset-filters').removeClass('d-none');
            showTable();
        });

        $('#reset-filters').click(function() {
            setDate();
            $('#employee_id').val('all');
            $('#headquarter_id').val('');
            $('#type_missing, #type_mismatch, #type_unplanned').prop('checked', true);
            $('#show_unplanned').prop('checked', false);
            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        $('#export-excel-btn').on('click', function(e) {
            e.preventDefault();
            var url = "{{ route('tp-deviation-report.export-excel') }}?" + $.param(getUrlParams());
            window.location.href = url;
        });
    </script>
@endpush
