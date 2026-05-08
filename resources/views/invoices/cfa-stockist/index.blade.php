@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

<style>
    div.status-cell {
        width: 200px;
    }
</style>

@section('filter-section')

    <x-filters.filter-box>
        <!-- DATE START -->
        <div class="select-box d-flex pr-2 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.duration')</p>
            <div class="select-status d-flex">
                <input type="text" class="position-relative text-dark form-control border-0 p-2 text-left f-14 f-w-500 border-additional-grey"
                    id="datatableRange" placeholder="@lang('placeholders.dateRange')">
            </div>
        </div>
        <!-- DATE END -->

        @if (isset($cfaStockists) && $cfaStockists->count() > 0)
            <!-- STOCKIST START -->
            <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
                <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('CFA Stockist')</p>
                <div class="select-status">
                    <select class="form-control select-picker" id="stockistID" data-live-search="true" data-size="8">
                        <option value="all">@lang('app.all')</option>
                        @foreach ($cfaStockists as $stockist)
                            <option value="{{ $stockist->id }}">{{ ($stockist->cfa_stockist_id ? $stockist->cfa_stockist_id . ' - ' : '') . ($stockist->shopname ?? $stockist->fullname) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <!-- STOCKIST END -->
        @endif

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
                <label class="f-14 text-dark-grey mb-12 " for="usr">@lang('app.status')</label>
                <div class="select-filter mb-4">
                    <div class="select-others">
                        <select class="form-control select-picker" name="status" id="status" data-live-search="true"
                            data-container="body" data-size="8">
                            <option value="all">@lang('app.all')</option>
                            <option {{ request('status') == 'pending' ? 'selected' : '' }} value="pending">
                                @lang('app.pending')</option>
                            <option {{ request('status') == 'unpaid' ? 'selected' : '' }} value="unpaid">
                                @lang('app.unpaid')</option>
                            <option {{ request('status') == 'paid' ? 'selected' : '' }} value="paid">@lang('app.paid')
                            </option>
                            <option {{ request('status') == 'partial' ? 'selected' : '' }} value="partial">
                                @lang('app.partial')</option>
                            <option {{ request('status') == 'canceled' ? 'selected' : '' }} value="canceled">
                                @lang('app.canceled')</option>
                            <option {{ request('status') == 'pending-confirmation' ? 'selected' : '' }} value="pending-confirmation">
                                @lang('app.pendingConfirmation')</option>
                        </select>
                    </div>
                </div>
            </div>
        </x-filters.more-filter-box>
        <!-- MORE FILTERS END -->

    </x-filters.filter-box>

@endsection

@php
    $addCfaStockistPerm = user()->permission('add_cfa_stockist_invoices');
    $canAddCfaStockistInvoice = \App\Helpers\PharmaDesignationHelper::hasFullCFAAccess()
        || in_array('client', user_roles())
        || in_array($addCfaStockistPerm, ['all', 'added']);
@endphp

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        <!-- Add Task Export Buttons Start -->
        <div class="d-block d-lg-flex d-md-flex justify-content-between">
            <div id="table-actions" class="flex-grow-1 align-items-center mb-2 mb-lg-0 mb-md-0">
                @if ($canAddCfaStockistInvoice)
                    <x-forms.link-primary :link="route('cfa-stockist-invoices.create')" class="mr-3 float-left mb-2 mb-lg-0 mb-md-0"
                        icon="plus">
                        @lang('app.add') CFA/Stockist Invoice
                    </x-forms.link-primary>
                    <x-forms.link-secondary :link="route('cfa-stockist-invoices.create', ['type' => 'igst'])" class="mr-3 float-left mb-2 mb-lg-0 mb-md-0"
                        icon="plus">
                        Add IGST Invoice
                    </x-forms.link-secondary>
                @endif
            </div>

            <div class="btn-group mt-3 mt-lg-0 mt-md-0 ml-lg-3 d-none d-lg-block" role="group">
                <a href="javascript:;" class="img-lightbox btn btn-secondary f-14"
                data-image-url="{{ asset('img/invoice-lc.png') }}" data-toggle="tooltip"
                data-original-title="@lang('app.howItWorks')"><i class="side-icon bi bi-question-circle"></i></a>
            </div>
        </div>

        <!-- Add Task Export Buttons End -->
        <!-- Task Box Start -->
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white w-100 table-responsive">

            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}

        </div>
        <!-- Task Box End -->
    </div>
    <!-- CONTENT WRAPPER END -->

@endsection

@push('scripts')
    @include('sections.datatable_js')
    <script src="{{ asset('vendor/jquery/clipboard.min.js') }}"></script>
    <script>
        $(function() {
            var clipboard = new ClipboardJS('.btn-copy');

            clipboard.on('success', function(e) {
                Swal.fire({
                    icon: 'success',
                    text: '@lang("app.copied")',
                    toast: true,
                    position: 'top-end',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false,
                    customClass: {
                        confirmButton: 'btn btn-primary',
                    },
                    showClass: {
                        popup: 'swal2-noanimation',
                        backdrop: 'swal2-noanimation'
                    },
                })
            });
        });

        $('#cfa-stockist-invoices-table').on('preXhr.dt', function(e, settings, data) {

            var dateRangePicker = $('#datatableRange').data('daterangepicker');
            var startDate = $('#datatableRange').val();

            if (startDate == '') {
                startDate = null;
                endDate = null;
            } else {
                startDate = dateRangePicker.startDate.format('{{ company()->moment_date_format }}');
                endDate = dateRangePicker.endDate.format('{{ company()->moment_date_format }}');
            }

            var stockistID = $('#stockistID').val();
            var status = $('#status').val();

            var searchText = $('#search-text-field').val();

            data['stockistID'] = stockistID;
            data['status'] = status;
            data['startDate'] = startDate;
            data['endDate'] = endDate;
            data['searchText'] = searchText;
        });

        const showTable = () => {
            window.LaravelDataTables["cfa-stockist-invoices-table"].draw(true);
        }

        $('#stockistID, #status')
            .on('change keyup',
                function() {
                    if ($('#status').val() != "all") {
                        $('#reset-filters').removeClass('d-none');
                        showTable();
                    } else if ($('#stockistID').val() != "all") {
                        $('#reset-filters').removeClass('d-none');
                        showTable();
                    } else {
                        $('#reset-filters').addClass('d-none');
                        showTable();
                    }
                });

        $('#search-text-field').on('keyup', function() {
            if ($('#search-text-field').val() != "") {
                $('#reset-filters').removeClass('d-none');
                showTable();
            }
        });

        $('#reset-filters,#reset-filters-2').click(function () {
            $('#filter-form')[0].reset();

            $('.filter-box .select-picker').selectpicker("refresh");
            $('#reset-filters').addClass('d-none');
            showTable();
        });

        $('body').on('click', '.delete-table-row', function() {
            var id = $(this).data('invoice-id');
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
                    var url = "{{ route('invoices.destroy', ':id') }}";
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
                        success: function(response) {
                            if (response.status == "success") {
                                showTable();
                            }
                        }
                    });
                }
            });
        });

        $('body').on('click', '.sendButton', function() {
            var id = $(this).data('invoice-id');
            var dataType = $(this).data('type');
            var invoiceAmt = $(this).data('amt');
            var url = "{{ route('invoices.send_invoice', ':id') }}";
            url = url.replace(':id', id);

            var token = "{{ csrf_token() }}";

            if(invoiceAmt == 0 && invoiceAmt != null)
            {
                Swal.fire({
                            title: "@lang('messages.sweetAlertTitle')",
                            text: "@lang('messages.markAsPaid')",
                            icon: 'warning',
                            showCancelButton: true,
                            focusConfirm: false,
                            confirmButtonText: "@lang('app.yes')",
                            cancelButtonText: "@lang('app.no')",
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

                        $.easyAjax({
                            type: 'POST',
                            url: url,
                            container: '#cfa-stockist-invoices-table',
                            blockUI: true,
                            data: {
                                '_token': token,
                                'data_type' : dataType
                            },
                            success: function(response) {
                                if (response.status == "success") {
                                    showTable();
                                }
                            }
                        });
                    }
                });
            }
            else
            {
                $.easyAjax({
                    type: 'POST',
                    url: url,
                    container: '#cfa-stockist-invoices-table',
                    blockUI: true,
                    data: {
                        '_token': token,
                        'data_type' : dataType
                    },
                    success: function(response) {
                        if (response.status == "success") {
                            showTable();
                        }
                    }
                });
            }
        });

        $( document ).ready(function() {
            @if (!is_null(request('start')) && !is_null(request('end')))
            $('#datatableRange').val('{{ request('start') }}' +
            ' @lang("app.to") ' + '{{ request('end') }}');
            $('#datatableRange').data('daterangepicker').setStartDate("{{ request('start') }}");
            $('#datatableRange').data('daterangepicker').setEndDate("{{ request('end') }}");
                showTable();
            @endif
        });

        // Handle payment status badge click - show modal
        $(document).on('click', '.payment-status-badge', function(e) {
            e.preventDefault();
            
            var invoiceId = $(this).data('invoice-id');
            var currentStatus = $(this).data('current-status');
            
            console.log('Payment badge clicked:', {
                invoiceId: invoiceId,
                currentStatus: currentStatus
            });
            
            var url = "{{ route('cfa-stockist-invoices.payment-modal') }}";
            
            // Use standard MODAL_LG for consistency with rest of application
            $(MODAL_LG + ' ' + MODAL_HEADING).html('Mark Invoice as Paid');
            $.ajaxModal(MODAL_LG, url + '?invoice_id=' + invoiceId);
        });

    </script>
@endpush


