@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@section('filter-section')
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

        <!-- STATUS FILTER START -->
        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0 border-right-grey">
            <div class="select-status mr-3 pl-3">
                <select class="form-control select-picker" name="status" id="status_filter" data-live-search="true">
                    <option value="all">@lang('app.all') @lang('app.status')</option>
                    <option value="initiated">Initiated</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>
        <!-- STATUS FILTER END -->

        <!-- PAYMENT STATUS FILTER START -->
        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0 border-right-grey">
            <div class="select-status mr-3 pl-3">
                <select class="form-control select-picker" name="payment_status" id="payment_status_filter">
                    <option value="all">@lang('app.all') Payment Status</option>
                    <option value="pending">Pending</option>
                    <option value="processed">Processed</option>
                    <option value="paid">Paid</option>
                </select>
            </div>
        </div>
        <!-- PAYMENT STATUS FILTER END -->

        <!-- RESET START -->
        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0">
            <x-forms.button-secondary class="btn-xs" id="reset-filters" icon="times-circle">
                @lang('app.clearFilters')
            </x-forms.button-secondary>
        </div>
        <!-- RESET END -->
    </x-filters.filter-box>
@endsection

@php
$addFnfPermission = user()->permission('add_employees');
@endphp

@section('content')
    <div class="content-wrapper">
        <div class="d-lg-flex d-md-flex d-block justify-content-between action-bar">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                @if ($addFnfPermission == 'all')
                    <x-forms.link-primary :link="route('fnf-settlements.create')" class="mr-3 openRightModal" icon="plus">
                        @lang('app.add') FNF Settlement
                    </x-forms.link-primary>
                @endif
            </div>

            <x-datatable.actions>
                <div class="select-status mr-3 pl-3">
                    <select name="action_type" class="form-control select-picker" id="quick-action-type" disabled>
                        <option value="">@lang('app.selectAction')</option>
                        <option value="delete">@lang('app.delete')</option>
                    </select>
                </div>
                <div class="select-status mr-3 d-none quick-action-field" id="quick-action-apply">
                    <button type="button" id="apply-quick-action" class="btn-secondary rounded f-14 p-2 mr-3 apply-quick-action">
                        <i class="fa fa-check"></i> @lang('app.apply')
                    </button>
                </div>
            </x-datatable.actions>

        </div>

        <div class="d-flex flex-column w-tables rounded bg-white mt-3">
            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}
        </div>
    </div>
@endsection

@push('scripts')
    @include('sections.datatable_js')

    <script>
        $('#fnf-settlements-table').on('preXhr.dt', function(e, settings, data) {
            data['status'] = $('#status_filter').val();
            data['payment_status'] = $('#payment_status_filter').val();
            data['searchText'] = $('#search-text-field').val();
        });

        const showTable = () => {
            window.LaravelDataTables["fnf-settlements-table"].draw(false);
        }

        $('#status_filter, #payment_status_filter').on('change', function() {
            showTable();
        });

        $('#search-text-field').on('keyup', function() {
            if ($('#search-text-field').val() != "") {
                $('#reset-filters').removeClass('d-none');
                showTable();
            }
        });

        $('#reset-filters').click(function() {
            $('#search-text-field').val('');
            $('#status_filter').val('all').selectpicker('refresh');
            $('#payment_status_filter').val('all').selectpicker('refresh');
            showTable();
        });

        $('body').on('click', '.delete-table-row', function() {
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
                    var url = "{{ route('fnf-settlements.destroy',':id') }}";
                    url = url.replace(':id', id);

                    var token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        data: {'_token': token, '_method': 'DELETE'},
                        success: function (response) {
                            if (response.status == "success") {
                                showTable();
                            }
                        }
                    });
                }
            });
        });

        // Quick action
        $('#quick-action-type').change(function () {
            const actionValue = $(this).val();
            if (actionValue != '') {
                $('#quick-action-apply').removeClass('d-none');
            } else {
                $('#quick-action-apply').addClass('d-none');
            }
        });

        $('#apply-quick-action').click(function() {
            const actionValue = $('#quick-action-type').val();
            if (actionValue == '') {
                return;
            }

            var rowdIds = $("#fnf-settlements-table input:checkbox:checked").map(function(){
                return $(this).val();
            }).get();

            if (rowdIds.length === 0) {
                Swal.fire({
                    icon: 'error',
                    text: "@lang('messages.selectAtLeastOneRecord')",
                });
                return;
            }

            Swal.fire({
                title: "@lang('messages.areYouSure')",
                text: "@lang('messages.youWontRecover')",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: "@lang('messages.confirmDelete')",
                cancelButtonText: "@lang('app.cancel')"
            }).then((result) => {
                if (result.isConfirmed) {
                    applyQuickAction(rowdIds, actionValue);
                }
            });
        });

        function applyQuickAction(rowdIds, actionValue) {
            $.easyAjax({
                url: "{{ route('fnf-settlements.apply_quick_action') }}",
                container: '#quick-action-form',
                type: "POST",
                data: {
                    action_type: actionValue,
                    _token: '{{ csrf_token() }}',
                    row_ids: rowdIds
                },
                success: function (response) {
                    if (response.status == 'success') {
                        showTable();
                        fnfResetQuickActions();
                        fnfDeselectAllTableRows();
                    }
                }
            });
        }

        /** Local reset — avoid global `resetActionButtons` from custom.js (const, same scope). */
        function fnfResetQuickActions() {
            $('#quick-action-type').val('').selectpicker('refresh');
            $('#quick-action-apply').addClass('d-none');
        }

        function fnfDeselectAllTableRows() {
            $('#select-all-table').prop('checked', false);
            $('.table-row-select').prop('checked', false);
        }
    </script>
@endpush

