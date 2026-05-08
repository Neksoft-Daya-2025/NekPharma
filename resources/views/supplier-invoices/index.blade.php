@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@section('filter-section')
    <x-filters.filter-box>
        <div class="select-box d-flex py-2 pr-2 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('modules.invoices.vendor')</p>
            <div class="select-status d-flex">
                <select class="form-control select-picker" name="vendor_id" id="vendor_id" data-live-search="true">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($vendors as $v)
                        <option value="{{ $v->id }}">{{ $v->primary_name ?? $v->company_name ?? $v->id }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.matchStatus')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="match_status" id="match_status">
                    <option value="all">@lang('app.all')</option>
                    <option value="draft">Draft</option>
                    <option value="matched">Matched</option>
                    <option value="unmatched">Unmatched</option>
                </select>
            </div>
        </div>
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.paymentStatus')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="payment_status" id="payment_status">
                    <option value="all">@lang('app.all')</option>
                    <option value="pending">Pending</option>
                    <option value="partial">Partial</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
        </div>
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.duration')</p>
            <div class="select-status d-flex">
                <input type="text" class="position-relative text-dark form-control border-0 p-2 text-left f-14 f-w-500 border-additional-grey" id="datatableRange" placeholder="@lang('placeholders.dateRange')">
            </div>
        </div>
        <div class="task-search d-flex py-1 px-lg-3 px-0 border-right-grey align-items-center">
            <form class="w-100 mr-1 mr-lg-0 mr-md-1 ml-md-1 ml-0 ml-lg-0">
                <div class="input-group bg-grey rounded">
                    <div class="input-group-prepend">
                        <span class="input-group-text border-0 bg-additional-grey"><i class="fa fa-search f-13 text-dark-grey"></i></span>
                    </div>
                    <input type="text" class="form-control f-14 p-1 border-additional-grey" id="search-text-field" placeholder="@lang('app.startTyping')">
                </div>
            </form>
        </div>
        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0">
            <x-forms.button-secondary class="btn-xs d-none" id="reset-filters" icon="times-circle">@lang('app.clearFilters')</x-forms.button-secondary>
        </div>
    </x-filters.filter-box>
@endsection

@php
$addProductPermission = user()->permission('add_product');
@endphp

@section('content')
    <div class="content-wrapper">
        <div class="d-block d-lg-flex d-md-flex justify-content-between">
            <div id="table-actions" class="flex-grow-1 align-items-center mb-2 mb-lg-0 mb-md-0">
                @if ($addProductPermission == 'all' || $addProductPermission == 'added')
                    <x-forms.link-primary :link="route('supplier-invoices.create')" class="mr-3" icon="plus">
                        @lang('app.add') @lang('app.supplierInvoice')
                    </x-forms.link-primary>
                @endif
                <x-forms.link-secondary :link="route('purchase-entries.index')" class="mr-3" icon="list">
                    Purchase Entries
                </x-forms.link-secondary>
            </div>
        </div>
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white w-100 table-responsive">
            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}
        </div>
    </div>
@endsection

@push('scripts')
    @include('sections.datatable_js')
    <script>
        $(function() {
            var dTable = $('#supplier-invoices-table').dataTable();
            $('#vendor_id, #match_status, #payment_status').on('change', function() {
                dTable.fnDraw();
            });
            $('#datatableRange').daterangepicker({
                locale: { format: '{{ company()->moment_date_format }}' },
                linkedCalendars: false,
                opens: 'left',
                autoUpdateInput: false
            }, function(start, end) {
                $('#datatableRange').val(start.format('{{ company()->moment_date_format }}') + ' - ' + end.format('{{ company()->moment_date_format }}'));
                dTable.fnDraw();
            });
            $('#search-text-field').on('keyup', function() {
                dTable.fnFilter(this.value);
            });
            $('body').on('click', '.delete-table-row', function() {
                var id = $(this).data('supplier-invoice-id');
                Swal.fire({
                    title: "@lang('messages.sweetAlertTitle')",
                    text: "@lang('messages.recoverRecord')",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: "@lang('messages.confirmDelete')",
                    cancelButtonText: "@lang('app.cancel')"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: "{{ url('account/supplier-invoices') }}/" + id,
                            type: 'DELETE',
                            data: { _token: '{{ csrf_token() }}' },
                            success: function() {
                                window.LaravelDataTables["supplier-invoices-table"].draw();
                            },
                            error: function(xhr) {
                                Swal.fire('@lang('messages.error')', xhr.responseJSON?.message || '@lang('messages.error')', 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
@endpush
