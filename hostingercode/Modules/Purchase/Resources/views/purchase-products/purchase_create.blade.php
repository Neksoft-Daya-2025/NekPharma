@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@push('styles')
<style>
    .action-bar {
        min-height: 50px;
        padding: 10px 0;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    #table-actions {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    #table-actions .btn,
    #table-actions a {
        white-space: nowrap;
    }
    
    .action-bar > div {
        display: flex;
        align-items: center;
    }
    
    @media (max-width: 768px) {
        .action-bar {
            flex-direction: column;
            align-items: flex-start !important;
        }
        
        .action-bar > div:last-child {
            width: 100%;
            justify-content: flex-start;
            margin-top: 10px;
        }
    }
</style>
@endpush

@section('filter-section')

    <x-filters.filter-box>

        <!-- CATEGORY START -->
        <div class="select-box d-flex py-2 pr-2 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">
                @lang('modules.productCategory.productCategory')</p>
            <div class="select-status d-flex">
                <select class="form-control select-picker" name="category_id" id="category_id">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ mb_ucwords($category->category_name) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <!-- CATEGORY END -->

        <!-- SUBCATEGORY START -->
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">
                @lang('modules.productCategory.productSubCategory')</p>
            <div class="select-status">
                <select class="form-control select-picker" name="sub_category" id="sub_category">
                    <option selected value="all">@lang('app.all')</option>
                </select>
            </div>
        </div>
        <!-- SUBCATEGORY END -->

        <!-- UNITTYPE START-->

        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">
                @lang('modules.invoices.unitType')</p>
            <div class="select-status d-flex">
                <select class="form-control select-picker" name="unit_type_id" id="unit_type_id">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($unitTypes  as $unitType)
                        <option value="{{ $unitType->id }}">{{ $unitType->unit_type }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- UNITTYPE END-->

        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">
                @lang('purchase::modules.product.productType')</p>
            <div class="select-status d-flex">
                <select class="form-control select-picker" name="product_type" id="product_type">
                    <option value="all">@lang('app.all')</option>
                    <option value="goods">{{__('purchase::modules.product.goods')}}</option>
                    <option value="service">{{__('purchase::modules.product.service')}}</option>
                </select>
            </div>
        </div>

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
    </x-filters.filter-box>

@endsection

@php
$addProductPermission = user()->permission('add_product');
$addOrderPermission = user()->permission('add_order');
@endphp

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        <!-- Add Task Export Buttons Start -->
        <input type="hidden" name="user_id" class="user_id" value={{user()->id}}>
        <div class="d-flex justify-content-between align-items-center action-bar py-2">
            <div id="table-actions" class="d-flex align-items-center flex-wrap">
                @if ($addProductPermission == 'all' || $addProductPermission == 'added')
                    <x-forms.link-primary :link="route('purchase-entries.create')" class="mr-2 mb-2"
                        icon="plus">
                        @lang('Add Entry')
                    </x-forms.link-primary>
                    <x-forms.link-secondary :link="route('purchase-products.import')" class="mr-2 mb-2 openRightModal"
                        icon="upload">
                        @lang('app.importExcel')
                    </x-forms.link-secondary>
                @endif
            </div>
            
            <div class="d-flex align-items-center">
                @if (user()->permission('delete_product') == 'all')
                    <button type="button" class="btn btn-danger mr-2 mb-2" id="bulk-delete-entries-btn" data-toggle="tooltip" data-original-title="@lang('purchase::app.bulkDeleteEntriesTooltip')">
                        <i class="fa fa-trash mr-1"></i>
                        @lang('purchase::app.bulkDeleteEntries')
                    </button>
                @endif
                @if (!isset($isClient) || !$isClient)
                <div id="emptyCartBox" class="mr-3">
                    <a href="javascript:;" class="f-20 text-lightest d-flex align-items-center empty-cart fa fa-trash" data-user-id = {{ user()->id }} data-toggle="tooltip" data-original-title="@lang('app.emptyCart')" ><i></i></a>
                </div>
                @endif

                @if (in_array('client', user_roles()) && $addOrderPermission == 'all' && (!isset($isClient) || !$isClient))
                    <div class="btn-group mr-3" role="group">
                        <x-forms.link-primary :link="route('products.cart')" icon="shopping-bag">
                            @lang('app.cart') <span
                                class="badge badge-light ml-2 productCounter">{{ $cartProductCount }}</span>
                        </x-forms.link-primary>
                    </div>
                @endif

                @if (!in_array('client', user_roles()))
                    <x-datatable.actions>
                        <div class="select-status mr-2">
                            <select name="action_type" class="form-control select-picker" id="quick-action-type" disabled>
                                <option value="">@lang('app.selectAction')</option>
                                <option value="change-purchase">@lang('app.purchaseAllow')</option>
                                <option value="change-status">@lang('modules.tasks.changeStatus')</option>
                                <option value="delete">@lang('app.delete')</option>
                            </select>
                        </div>
                        <div class="select-status mr-2 d-none quick-action-field" id="change-status-action">
                            <select name="status" class="form-control select-picker">
                                <option value="1">@lang('app.allowed')</option>
                                <option value="0">@lang('app.notAllowed')</option>
                            </select>
                        </div>
                        <div class="select-status mr-2 d-none status-quick-action" id="change-product-status">
                            <select name="product_status" class="form-control select-picker">
                                <option value="active">@lang('app.active')</option>
                                <option value="inactive">@lang('app.inactive')</option>
                            </select>
                        </div>
                    </x-datatable.actions>
                @endif
            </div>
        </div>

        <!-- Add Task Export Buttons End -->
        <!-- Task Box Start -->
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white table-responsive">

            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}

        </div>
        <!-- Task Box End -->
    </div>
    <!-- CONTENT WRAPPER END -->

@endsection

@push('scripts')
    @include('sections.datatable_js')

    <script>
        // Select All functionality for Purchase Entries
        // Use window object to ensure it's globally accessible and avoid conflicts
        window.selectAllPurchaseEntries = function(checkbox) {
            if (checkbox.checked) {
                $('#products-table tbody input[type="checkbox"]').prop('checked', true);
            } else {
                $('#products-table tbody input[type="checkbox"]').prop('checked', false);
            }
        };

        // Override dataTableRowCheck for purchase entries to handle null select-all-table element
        // This overrides the global function from custom.js to handle our specific table
        window.dataTableRowCheck = function(invoiceNumber) {
                var selectAllCheckbox = document.getElementById("select-all-table");
                var checkedCount = $('.select-table-row:checked').length;
                var totalCount = $('.select-table-row').length;
                
                // Update select-all checkbox state if it exists
                if (selectAllCheckbox) {
                    if (checkedCount === 0) {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = false;
                    } else if (checkedCount === totalCount) {
                        selectAllCheckbox.checked = true;
                        selectAllCheckbox.indeterminate = false;
                    } else {
                        selectAllCheckbox.checked = false;
                        selectAllCheckbox.indeterminate = true;
                    }
                }
                
                // Update row active state
                var rowCheckbox = $('#datatable-row-' + invoiceNumber);
                if (rowCheckbox.length && rowCheckbox.is(':checked')) {
                    rowCheckbox.closest('tr').addClass('table-active');
                } else if (rowCheckbox.length) {
                    rowCheckbox.closest('tr').removeClass('table-active');
                }
            };
        
        $(window).on('load', function() {
            @if($cartProductCount == 0)
                $('#emptyCartBox').hide();
            @endif
        });

        var subCategories = @json($subCategories);

        $('#category_id').change(function(e) {
            // get projects of selected users
            var opts = '';

            var subCategory = subCategories.filter(function(item) {
                return item.category_id == e.target.value
            });

            subCategory.forEach(project => {
                opts += `<option value='${project.id}'>${project.category_name}</option>`
            })

            $('#sub_category').html('<option value="all">@lang("app.all")</option>' + opts)
            $("#sub_category").selectpicker("refresh");
        });

        $('#products-table').on('preXhr.dt', function(e, settings, data) {
            var categoryID = $('#category_id').val();
            var subCategoryID = $('#sub_category').val();
            var searchText = $('#search-text-field').val();
            var unitTypeID  = $('#unit_type_id').val();
            var productType = $('#product_type').val();

            data['category_id'] = categoryID;
            data['sub_category_id'] = subCategoryID;
            data['searchText'] = searchText;
            data['unit_type_id'] = unitTypeID;
            data['product_type'] = productType;
        });
        const showTable = () => {
            window.LaravelDataTables["products-table"].draw(false);
        }

        $('#category_id, #sub_category, #unit_type_id, #product_type').on('change keyup', function() {
            if ($('#category_id').val() != "") {
                $('#reset-filters').removeClass('d-none');
                showTable();
            } else if ($('#sub_category').val() != "") {
                $('#reset-filters').removeClass('d-none');
                showTable();
            } else if ($('#unit_type_id').val() != "") {
                $('#reset-filters').removeClass('d-none');
                showTable();
            }else if ($('#product_type').val() != "") {
                $('#reset-filters').removeClass('d-none');
                showTable();
            }else{
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

        $('#reset-filters').click(function() {
            $('#filter-form')[0].reset();

            $('#category_id').val('all');
            $('.select-picker').val('all');

            $('#sub_category').html('<option value="all">@lang("app.all")</option>');

            $('#unit_type_id').val('all');

            $('#product_type').val('all');

            $('.select-picker').selectpicker("refresh");

            $('#reset-filters').addClass('d-none');

            showTable();
        });

        $('#quick-action-type').change(function() {
            const actionValue = $(this).val();
            if (actionValue != '') {
                $('#quick-action-apply').removeAttr('disabled');

                if (actionValue == 'change-purchase') {
                    $('.quick-action-field').addClass('d-none');
                    $('#change-status-action').removeClass('d-none');
                } else {
                    $('.quick-action-field').addClass('d-none');
                }

                if (actionValue == 'change-status') {
                    $('.status-quick-action').addClass('d-none');
                    $('#change-product-status').removeClass('d-none');
                } else {
                    $('.status-quick-action').addClass('d-none');
                }

            } else {
                $('#quick-action-apply').attr('disabled', true);
                $('.quick-action-field').addClass('d-none');
                $('.status-quick-action').addClass('d-none');
            }
        });

        $('#quick-action-apply').click(function() {
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

            }
            else if (actionValue == 'change-status') {
                changeProductStatus();
            }
            else {
                applyQuickAction();
            }
        });

        const applyQuickAction = () => {
            var rowdIds = $("#products-table input:checkbox:checked").map(function() {
                return $(this).val();
            }).get();

            var url = "{{ route('purchase_products.apply_quick_action') }}?row_ids=" + rowdIds;

            $.easyAjax({
                url: url,
                container: '#quick-action-form',
                type: "POST",
                disableButton: true,
                buttonSelector: "#quick-action-apply",
                data: $('#quick-action-form').serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        showTable();
                        resetActionButtons();
                        deSelectAll();
                        $('#quick-action-form').hide();
                        $('.quick-action-field').addClass('d-none');
                    }
                }
            })
        };

        const changeProductStatus = () => {
            var rowdIds = $("#products-table input:checkbox:checked").map(function() {
                return $(this).val();
            }).get();

            var url = "{{ route('purchase_products.apply_quick_action') }}?row_ids=" + rowdIds;

            $.easyAjax({
                url: url,
                container: '#quick-action-form',
                type: "POST",
                disableButton: true,
                buttonSelector: "#quick-action-apply",
                data: $('#quick-action-form').serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        showTable();
                        resetActionButtons();
                        deSelectAll();
                        $('#quick-action-form').hide();
                        $('.status-quick-action').addClass('d-none');
                    }
                }
            })
        };

        $('body').on('click', '.productView', function() {
            let id = $(this).data('product-id');

            var url = "{{ route('purchase-products.show', ':id') }}";
            url = url.replace(':id', id);

            $(MODAL_DEFAULT + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_DEFAULT, url);
        });

        $('body').on('click', '.delete-table-row', function() {
            var id = $(this).data('id') || $(this).data('product-id');
            var url = $(this).data('url');
            
            if (!url) {
                url = "{{ route('purchase-entries.destroy', ':id') }}";
                url = url.replace(':id', id);
            }
            
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
                    var token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'POST',
                        url: url,
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

        $('body').on('change', '.change-product-status', function() {
            var id = $(this).data('product-id');
            var url = "{{ route('purchase_products.change_status') }}";

            var token = "{{ csrf_token() }}";
            var status = $(this).val();

            if (typeof id !== 'undefined') {
                $.easyAjax({
                    url: url,
                    type: "POST",
                    data: {
                        '_token': token,
                        productId: id,
                        status: status
                    },

                    success: function(response) {
                        if (response.status == "success") {
                            showTable();
                            resetActionButtons();
                            deSelectAll();
                        }
                    }
                });
            }
        });

        $('body').on('click', '.add-product', function() {
            let cartItems = [];
            var productId = $(this).data('product-id');
            let url = "{{ route('products.add_cart_item') }}";

            $.easyAjax({
                url: url,
                container: '.content-wrapper',
                type: "POST",
                data: {
                    'productID': productId,
                    '_token': "{{ csrf_token() }}"
                },
                success: function(response) {
                        $('#emptyCartBox').show();
                        cartItems = response.cartProduct;
                        $('.productCounter').html(cartItems);

                }
            })

        });

        $('body').on('click', '.empty-cart', function() {
            let id = $(this).data('user-id');

            var url = "{{ route('products.remove_cart_item', ':id') }}";
            url = url.replace(':id', id);
            $.easyAjax({
                url: url,
                container: '#saveInvoiceForm',
                type: "POST",
                blockUI: true,
                data: {
                    _token: "{{ csrf_token() }}",
                    type: "all_data",
                },
                success: function(response) {
                    cartItems = response.productItems;
                    $('.productCounter').html(cartItems);
                    $('#emptyCartBox').hide();

                }
            });
        });
        
        // View products in an invoice
        function viewInvoiceProducts(invoiceNumber) {
            var url = "{{ route('purchase-entries.invoice-products', ':invoice') }}";
            url = url.replace(':invoice', invoiceNumber);
            
            $(MODAL_LG + ' ' + MODAL_HEADING).html('Invoice: ' + invoiceNumber);
            $.ajaxModal(MODAL_LG, url);
        }
        
        // Delete entire invoice
        $('body').on('click', '.delete-invoice', function() {
            var invoiceNumber = $(this).data('invoice');
            
            Swal.fire({
                title: "Delete Invoice?",
                text: "This will delete all products in invoice " + invoiceNumber,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "Cancel",
                customClass: {
                    confirmButton: 'btn btn-danger mr-3',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.easyAjax({
                        url: "{{ route('purchase-entries.delete-invoice') }}",
                        type: "POST",
                        data: {
                            _token: "{{ csrf_token() }}",
                            invoice_number: invoiceNumber
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

        // Bulk Delete Purchase Entries
        $('#bulk-delete-entries-btn').click(function() {
            // Get all checked invoice checkboxes
            var checkedBoxes = $('#products-table tbody input[type="checkbox"]:checked');
            
            if (checkedBoxes.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: '@lang("app.warning")',
                    text: '@lang("purchase::app.selectInvoicesToDelete")',
                    showConfirmButton: true,
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                });
                return;
            }
            
            // Collect invoice numbers from checked checkboxes
            var invoiceNumbers = [];
            checkedBoxes.each(function() {
                var invoiceNumber = $(this).val();
                if (invoiceNumber) {
                    invoiceNumbers.push(invoiceNumber);
                }
            });
            
            if (invoiceNumbers.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: '@lang("app.warning")',
                    text: '@lang("purchase::app.noInvoicesSelected")',
                    showConfirmButton: true,
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                });
                return;
            }
            
            var invoiceCount = invoiceNumbers.length;
            Swal.fire({
                title: "@lang('messages.sweetAlertTitle')",
                text: "Are you sure you want to delete " + invoiceCount + " selected invoice(s)? This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: "@lang('messages.confirmDelete')",
                cancelButtonText: "@lang('app.cancel')",
                customClass: {
                    confirmButton: 'btn btn-danger mr-3',
                    cancelButton: 'btn btn-secondary'
                },
                showClass: {
                    popup: 'swal2-noanimation',
                    backdrop: 'swal2-noanimation'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = "{{ route('purchase-entries.bulk-delete') }}";
                    
                    $.easyAjax({
                        url: url,
                        type: "POST",
                        blockUI: true,
                        data: {
                            _token: "{{ csrf_token() }}",
                            invoice_numbers: invoiceNumbers
                        },
                        success: function(response) {
                            if (response.status == "success") {
                                Swal.fire({
                                    icon: 'success',
                                    title: '@lang("app.success")',
                                    text: response.message,
                                    showConfirmButton: true,
                                    customClass: {
                                        confirmButton: 'btn btn-primary'
                                    },
                                    buttonsStyling: false
                                }).then(() => {
                                    // Reload the table to show updated data
                                    showTable();
                                    // Uncheck all checkboxes
                                    $('#products-table input[type="checkbox"]').prop('checked', false);
                                });
                            }
                        },
                        error: function(response) {
                            Swal.fire({
                                icon: 'error',
                                title: '@lang("app.error")',
                                text: response.responseJSON?.message || '@lang("messages.somethingWentWrong")',
                                showConfirmButton: true,
                                customClass: {
                                    confirmButton: 'btn btn-primary'
                                },
                                buttonsStyling: false
                            });
                        }
                    });
                }
            });
        });

        // Handle payment status badge click - show modal
        // Use event delegation to handle dynamically added elements
        $(document).on('click', '.payment-status-badge', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            try {
                var invoiceNumber = $(this).data('invoice-number');
                var currentStatus = $(this).data('current-status');
                
                if (!invoiceNumber) {
                    console.error('Invoice number not found');
                    return;
                }
                
                console.log('Payment badge clicked:', {
                    invoiceNumber: invoiceNumber,
                    currentStatus: currentStatus
                });
                
                var url = "{{ route('purchase-entries.payment-modal') }}";
                
                // Use standard MODAL_LG for consistency with rest of application
                // Ensure MODAL_LG and MODAL_HEADING are available (fallback to window properties)
                var modalLg = typeof MODAL_LG !== 'undefined' ? MODAL_LG : (window.MODAL_LG || '#myModal');
                var modalHeading = typeof MODAL_HEADING !== 'undefined' ? MODAL_HEADING : (window.MODAL_HEADING || '#modelHeading');
                
                if (typeof $.ajaxModal === 'function') {
                    $(modalLg + ' ' + modalHeading).html('Purchase Entry Payment Management');
                    $.ajaxModal(modalLg, url + '?invoice_number=' + encodeURIComponent(invoiceNumber));
                } else {
                    console.error('ajaxModal function not available');
                }
            } catch (error) {
                console.error('Error opening payment modal:', error);
            }
        });

    </script>
@endpush

