@php
$addProductPermission = user()->permission('add_product');
@endphp

<link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}">

<!-- CREATE INVOICE START -->
<div class="bg-white rounded b-shadow-4 create-inv">
    <!-- HEADING START -->
    <div class="px-lg-4 px-md-4 px-3 py-3">
        <h4 class="mb-0 f-21 font-weight-normal text-capitalize">@lang('purchase::app.menu.purchaseOrder')</h4>
    </div>
    <!-- HEADING END -->
    <hr class="m-0 border-top-grey">
    <!-- FORM START -->
    <x-form class="c-inv-form" id="saveOrderForm">
        <!-- INVOICE NUMBER, DATE, DUE DATE, FREQUENCY START -->
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <!-- ORDER NUMBER START -->
            <div class="col-md-6 col-lg-4">
                <div class="form-group mb-4">
                    <label class="f-14 text-dark-grey mb-12 text-capitalize" for="usr">@lang('app.orderNumber')</label>
                    <div class="input-group">
                        <div class="input-group-prepend  height-35 ">
                            <span class="input-group-text border-grey f-15 bg-additional-grey px-3 text-dark"
                                id="basic-addon1">{{ $purchaseSetting->purchase_order_prefix }}{{ $purchaseSetting->purchase_order_number_separator }}{{ $zero }}</span>
                        </div>
                        <input type="text" name="purchase_order_number" id="purchase_order_number"
                            class="form-control height-35 f-15"
                            value="@if (is_null($lastOrder)) 1 @else{{ $lastOrder }} @endif"
                            placeholder="0019" aria-label="0019" aria-describedby="basic-addon1">
                    </div>
                </div>
            </div>
            <!-- ORDER NUMBER END -->

            <div class="col-md-6 col-lg-4">
                <div class="form-group c-inv-select mb-4">
                    <x-forms.label fieldId="vendor_id" :fieldLabel="__('app.select').' '.__('purchase::app.menu.vendor')" fieldRequired="true">
                    </x-forms.label>

                    <div class="select-others height-35 rounded">
                        @if($vendorID) <input type="hidden" name="vendor_id" value="{{$vendorID}}"> @endif
                            <select @if($vendorID) disabled @endif class="form-control select-picker" name="vendor_id" id="vendor_id" data-live-search="true">
                                <option value="">--</option>
                                @foreach ($vendors as $vendor)
                                    <option  @if($vendorID == $vendor->id) selected @endif value="{{ $vendor->id }}">{{ $vendor->primary_name }}</option>
                                @endforeach
                            </select>
                    </div>
                </div>
            </div>

            <!-- CURRENCY START -->
            <div class="col-md-6 col-lg-4">
                <div class="form-group c-inv-select mb-lg-0 mb-4">
                    <x-forms.label fieldId="currency_id" :fieldLabel="__('modules.invoices.currency')">
                    </x-forms.label>

                    <div class="select-others height-35 rounded" id="select_currency_id">
                        <select class="form-control select-picker" name="currency_id" id="currency_id">
                            @if (!is_null($purchaseVendorID))
                            <option value="{{ $purchaseVendorID->currency->id }}">{{ $purchaseVendorID->currency->currency_code }} ({{ $purchaseVendorID->currency->currency_symbol }})</option>
                            @else
                            <option value="">--</option>
                            @endif
                        </select>
                    </div>
                </div>
            </div>
            <!-- CURRENCY END -->
            <!-- Exchange Rate - Hidden when currencies are the same -->
            <div class="col-md-6 col-lg-4 mt-3" id="exchange_rate_container" style="display: none;">
                <x-forms.label fieldId="exchange_rate" :fieldLabel="__('modules.currencySettings.exchangeRate')" fieldRequired="true">
                </x-forms.label>
                <input type="number" id="exchange_rate" name="exchange_rate"
                class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15" value="{{ $companyCurrency->exchange_rate}}" readonly>
                <small id="currency_exchange" class="form-text text-muted">( {{company()->currency->currency_code}} @lang('app.to') <span id="currency">{{ $companyCurrency->currency_code }}</span> )</small>
            </div>

            <div class="col-md-6 col-lg-4 mt-3">
                <div class="form-group mb-4">
                    <x-forms.label fieldId="purchase_date" :fieldLabel="__('purchase::modules.purchaseOrder.orderDate')" fieldRequired="true">
                    </x-forms.label>
                    <div class="input-group">
                        <input type="text" id="purchase_date" name="purchase_date"
                            class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15"
                            placeholder="@lang('placeholders.date')"
                            value="{{ now(company()->timezone)->translatedFormat(company()->date_format) }}">
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4 mt-3">
                <div class="form-group mb-4">
                    <x-forms.label fieldId="expected_date" :fieldLabel="__('purchase::modules.purchaseOrder.expectedDeliveryDate')" fieldRequired="true">
                    </x-forms.label>
                    <div class="input-group">
                        <input type="text" id="expected_date" name="expected_date"
                            class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15"
                            placeholder="@lang('placeholders.date')"
                            value="{{ now(company()->timezone)->translatedFormat(company()->date_format) }}">
                    </div>
                </div>
            </div>


            <div class="col-md-6 col-lg-4 mt-3">
                <div class="form-group mb-4">
                    <x-forms.label fieldId="vendor_address_display" :fieldLabel="__('purchase::app.deliveryAddresses')">
                    </x-forms.label>
                    
                    <!-- Display vendor address (read-only) -->
                    <div id="vendor_address_display" class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15" style="background-color: #f8f9fa; border: 1px solid #e9ecef; padding: 8px 12px !important; @if(!$purchaseVendorID || !$vendorAddress) display: none; @endif">
                        <span id="vendor_address_text">
                            @if($purchaseVendorID && $vendorAddress)
                                {{ $vendorAddress->location }}
                            @else
                                --
                            @endif
                        </span>
                    </div>
                    
                    <!-- Hidden field to store address_id -->
                    <input type="hidden" name="address_id" id="address_id" value="{{ $purchaseVendorID && $purchaseVendorID->address_id ? $purchaseVendorID->address_id : '' }}">
                </div>
            </div>

            <div class="col-md-6 col-lg-4 mt-3">
                <div class="form-group c-inv-select mb-4">
                    <x-forms.label fieldId="calculate_tax" :fieldLabel="__('modules.invoices.calculateTax')">
                    </x-forms.label>
                    <div class="select-others height-35 rounded">
                        <select class="form-control select-picker" data-live-search="true" data-size="8"
                            name="calculate_tax" id="calculate_tax">
                            <option value="after_discount">@lang('modules.invoices.afterDiscount')</option>
                            <option value="before_discount">
                                @lang('modules.invoices.beforeDiscount')</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-4 mt-3">
                <div class="form-group c-inv-select mb-4">
                    <x-forms.label fieldId="delivery_status" :fieldLabel="__('purchase::modules.purchaseOrder.deliveryStatus')">
                    </x-forms.label>
                    <div class="select-others height-35 rounded">
                        <select class="form-control select-picker" data-live-search="true" data-size="8"
                            name="delivery_status" id="delivery_status">
                            <option data-content="<i class='fa fa-circle mr-2 text-dark'></i> @lang('purchase::modules.purchaseOrder.notStarted')" value="not_started"></option>
                            <option data-content="<i class='fa fa-circle mr-2 text-yellow'></i> @lang('purchase::modules.purchaseOrder.inTransaction')" value="in_transaction"></option>
                            <option data-content="<i class='fa fa-circle mr-1 f-15 text-red'></i> @lang('purchase::modules.purchaseOrder.deliveryFailed')" value="delivery_failed"></option>
                            <option data-content="<i class='fa fa-circle mr-1 f-15 text-light-green'></i> @lang('purchase::modules.purchaseOrder.delivered')" value="delivered"></option>
                        </select>
                    </div>
                </div>
            </div>

        </div>
        <!-- INVOICE NUMBER, DATE, DUE DATE, FREQUENCY END -->

        <hr class="m-0 border-top-grey">
        <!-- PRODUCT SEARCH SECTION - UltimatePOS Style -->
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-2 text-center">
                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#import_purchase_products_modal" style="display: none;">
                            @lang('product.import_products')
                        </button>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-search"></i>
                                </span>
                                <input type="text" 
                                    class="form-control" 
                                    id="search_product" 
                                    placeholder="@lang('lang_v1.search_product_placeholder')"
                                    autocomplete="off">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            @if ($addProductPermission == 'all' || $addProductPermission == 'added')
                                <a href="{{ route('purchase-products.create') }}" 
                                    class="btn btn-link">
                                    <i class="fa fa-plus"></i> @lang('purchase::modules.product.add_new_product')
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRODUCT ENTRY TABLE - UltimatePOS Style -->
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-sm-12">
                <div class="table-responsive">
                    <table class="table table-condensed table-bordered table-th-green text-center table-striped" id="purchase_entry_table">
                        <thead>
                            <tr>
                                <th width="3%">#</th>
                                <th width="{{ $invoiceSetting->hsn_sac_code_show ? '20%' : '25%' }}">@lang('purchase::modules.product.product_name')</th>
                                @if ($invoiceSetting->hsn_sac_code_show)
                                    <th width="8%">@lang('app.hsnSac')</th>
                                @endif
                                <th width="6%">@lang('purchase::modules.purchaseOrder.purchase_quantity')</th>
                                <th width="8%">@lang('lang_v1.unit_cost_before_discount')</th>
                                <th width="6%">@lang('lang_v1.discount_percent')</th>
                                <th width="8%">@lang('purchase::modules.purchaseOrder.unit_cost_before_tax')</th>
                                <th width="8%">@lang('purchase::modules.purchaseOrder.subtotal_before_tax')</th>
                                <th width="10%">@lang('purchase::modules.purchaseOrder.product_tax')</th>
                                <th width="7%">@lang('lang_v1.lot_number')</th>
                                <th width="8%">@lang('purchase::modules.product.exp_date')</th>
                                <th width="2%"></th>
                            </tr>
                        </thead>
                        <tbody id="sortable">
                            <!-- Product rows will be added here -->
                        </tbody>
                    </table>
                </div>
                <input type="hidden" id="row_count" value="0">
            </div>
        </div>

        <hr class="m-0 border-top-grey">

        <!-- TOTAL, DISCOUNT START -->
        <div class="d-flex px-lg-4 px-md-4 px-3 pb-3 c-inv-total">
            <table width="100%" class="text-right f-14 text-capitalize d-none" id="total-table">
                <tbody>
                    <tr>
                        <td width="50%" class="border-0 d-lg-table d-md-table d-none"></td>
                        <td width="50%" class="p-0 border-0 c-inv-total-right">
                            <table width="100%">
                                <tbody>
                                    <tr>
                                        <td colspan="2" class="border-top-0 text-dark-grey">
                                            @lang('modules.invoices.subTotal')</td>
                                        <td width="30%" class="border-top-0 sub-total">0.00</td>
                                        <input type="hidden" class="sub-total-field" name="sub_total"
                                            value="0">
                                    </tr>
                                    <tr>
                                        <td width="20%" class="text-dark-grey">@lang('modules.invoices.discount')
                                        </td>
                                        <td width="40%" style="padding: 5px;">
                                            <table width="100%">
                                                <tbody>
                                                    <tr>
                                                        <td width="70%" class="c-inv-sub-padding">
                                                            <input type="number" min="0"
                                                                name="discount_value"
                                                                class="form-control f-14 border-0 w-100 text-right discount_value"
                                                                placeholder="0"
                                                                </td>
                                                        <td width="30%" align="left" class="c-inv-sub-padding">
                                                            <div
                                                                class="select-others select-tax height-35 rounded border-0">
                                                                <select class="form-control select-picker"
                                                                    id="discount_type" name="discount_type">
                                                                    <option
                                                                        value="percent">%
                                                                    </option>
                                                                    <option
                                                                        value="fixed">
                                                                        @lang('modules.invoices.amount')</option>
                                                                </select>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td><span
                                                id="discount_amount">0.00</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>@lang('modules.invoices.tax')</td>
                                        <td colspan="2" class="p-0 border-0">
                                            <table width="100%" id="invoice-taxes">
                                                <tr>
                                                    <td colspan="2"><span class="tax-percent">0.00</span></td>
                                                </tr>
                                            </table>
                                        </td>

                                    </tr>
                                    <tr class="bg-amt-grey f-16 f-w-500">
                                        <td colspan="2">@lang('modules.invoices.total')</td>
                                        <td><span class="total">0.00</span></td>
                                        <input type="hidden" class="total-field" name="total" value="0">
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- TOTAL, DISCOUNT END -->

          <!-- NOTE AND TERMS AND CONDITIONS START -->
          <div class="d-flex flex-wrap px-lg-4 px-md-4 px-3 py-3">
            <div class="col-md-6 col-sm-12 c-inv-note-terms p-0 mb-lg-0 mb-md-0 mb-3">
                <x-forms.label fieldId="" class="text-capitalize" :fieldLabel="__('modules.invoices.note')">
                </x-forms.label>
                <textarea class="form-control" name="note" id="note" rows="4"
                    placeholder="@lang('placeholders.invoices.note')"></textarea>
            </div>
            <div class="col-md-6 col-sm-12 p-0 c-inv-note-terms">
                <x-forms.label fieldId="" :fieldLabel="__('modules.invoiceSettings.invoiceTerms')">
                </x-forms.label>
                <p>
                    {!! nl2br($purchaseSetting->purchase_terms) !!}
                </p>
            </div>
        </div>
        <!-- NOTE AND TERMS AND CONDITIONS END -->

        <!-- UPLOAD MULTIPLE FILES START -->
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <!-- INVOICE NUMBER START -->
            <div class="col-md-12">
                <x-forms.file-multiple class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('app.menu.addFile')" fieldName="file" fieldId="file-upload-dropzone"/>
            </div>
            <input type="hidden" name="orderID" id="orderID">
        </div>
        <!-- UPLOAD MULTIPLE FILES END -->

         <!-- CANCEL SAVE SEND START -->
         <x-form-actions class="c-inv-btns d-block d-lg-flex d-md-flex">
            <div class="d-flex mb-3 mb-lg-0 mb-md-0">

                <div class="inv-action dropup mr-3">
                    <button class="btn-primary dropdown-toggle" type="button" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        @lang('app.save')
                        <span><i class="fa fa-chevron-up f-15 text-white"></i></span>
                    </button>
                    <!-- DROPDOWN - INFORMATION -->
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuBtn" tabindex="0">
                        <li>
                            <a class="dropdown-item f-14 text-dark save-form" href="javascript:;" data-type="save">
                                <i class="fa fa-save f-w-500 mr-2 f-11"></i> @lang('app.save')
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item f-14 text-dark save-form" href="javascript:void(0);"
                                data-type="send">
                                <i class="fa fa-paper-plane f-w-500  mr-2 f-12"></i> @lang('app.saveSend')
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item f-14 text-dark save-form" href="javascript:void(0);"
                                data-type="mark_as_send" data-toggle="tooltip" data-original-title="@lang('messages.markSentInfo')">
                                <i class="fa fa-check-double f-w-500  mr-2 f-12"></i> @lang('app.saveMark')
                            </a>
                        </li>
                    </ul>
                </div>

                <x-forms.button-secondary data-type="draft" class="save-form mr-3">@lang('app.saveDraft')
                </x-forms.button-secondary>

            </div>

            <x-forms.button-cancel :link="route('invoices.index')" class="border-0 ">@lang('app.cancel')
            </x-forms.button-cancel>

        </x-form-actions>
        <!-- CANCEL SAVE SEND END -->

    </x-form>
    <!-- FORM END -->
</div>
<!-- CREATE INVOICE END -->

<script src="{{ asset('vendor/jquery/dropzone.min.js') }}"></script>
    <style>
        /* Fix datepicker z-index to prevent overlapping */
        .qs-datepicker-container {
            z-index: 9999 !important;
        }
        .qs-overlay {
            z-index: 9998 !important;
        }
        /* Ensure datepicker appears above table */
        #purchase_entry_table {
            position: relative;
        }
    </style>
    
    <script>

    $(document).ready(function() {

        $('.toggle-product-category').click(function() {
            $('.product-category-filter').toggleClass('d-none');
        });

        $('#product_category_id').on('change', function(){
            var categoryId = $(this).val();
            var url = "{{route('invoices.product_category', ':id')}}",
            url = (categoryId) ? url.replace(':id', categoryId) : url.replace(':id', null);
            $.easyAjax({
                url : url,
                type : "GET",
                container: '#saveInvoiceForm',
                blockUI: true,
                success: function (response) {
                    if (response.status == 'success') {
                        var options = [];
                        var rData = [];
                        rData = response.data;
                        $.each(rData, function(index, value) {
                            var selectData = '';
                            selectData = '<option value="' + value.id + '">' + value.name +
                                '</option>';
                            options.push(selectData);
                        });
                        $('#add-products').html(
                            '<option value="" class="form-control" >{{ __('app.select') . ' ' . __('app.product') }}</option>' +
                            options);
                        $('#add-products').selectpicker('refresh');
                    }
                }
            });
        });

        let defaultImage = '';
        let lastIndex = 0;

        Dropzone.autoDiscover = false;
        //Dropzone class
        orderDropzone = new Dropzone("div#file-upload-dropzone", {
            dictDefaultMessage: "{{ __('app.dragDrop') }}",
            url: "{{ route('purchase-order-file.store') }}",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            paramName: "file",
            maxFilesize: DROPZONE_MAX_FILESIZE,
            maxFiles: 10,
            autoProcessQueue: false,
            uploadMultiple: true,
            addRemoveLinks: true,
            parallelUploads: 10,
            init: function () {
                orderDropzone = this;
            }
        });
        orderDropzone.on('sending', function (file, xhr, formData) {
            const orderID = $('#orderID').val();
            formData.append('order_id', orderID);
            $.easyBlockUI();
        });
        orderDropzone.on('uploadprogress', function () {
            $.easyBlockUI();
        });
        orderDropzone.on('completemultiple', function () {
            window.location.href = '{{ route("purchase-order.index") }}';
        });
        orderDropzone.on('addedfile', function (file) {
            lastIndex++;

            const div = document.createElement('div');
            div.className = 'form-check-inline custom-control custom-radio mt-2 mr-3';
            const input = document.createElement('input');
            input.className = 'custom-control-input';
            input.type = 'radio';
            input.name = 'default_image';
            input.id = 'default-image-' + lastIndex;
            input.value = file.name;
            if (lastIndex == 1) {
                input.checked = true;
            }
            div.appendChild(input);

            var label = document.createElement('label');
            label.className = 'custom-control-label pt-1 cursor-pointer';
            label.innerHTML = "@lang('modules.makeDefaultImage')";
            label.htmlFor = 'default-image-' + lastIndex;
            div.appendChild(label);

            file.previewTemplate.appendChild(div);
        });

        const hsn_status = "{{ $invoiceSetting->hsn_sac_code_show }}";

        const dp1 = datepicker('#purchase_date', {
            position: 'bl',
            ...datepickerConfig
        });

        const dp2 = datepicker('#expected_date', {
            position: 'bl',
            ...datepickerConfig
        });

        // Product Search Autocomplete - UltimatePOS Style
        // Escape HTML helper function
        function escapeHtml(text) {
            if (!text) return '';
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }
        
        if ($('#search_product').length > 0) {
            var searchTimeout;
            var searchResults = [];
            
            // Check if jQuery UI autocomplete is available
            if (typeof $.fn.autocomplete !== 'undefined' && $.ui && $.ui.autocomplete) {
                // Use jQuery UI autocomplete
                $('#search_product').autocomplete({
                    source: function(request, response) {
                        $.getJSON(
                            "{{ route('purchase_order.get_products') }}",
                            { term: request.term },
                            function(data) {
                                response(data);
                            }
                        ).fail(function() {
                            console.error('Failed to fetch products');
                            response([]);
                        });
                    },
                    minLength: 2,
                    select: function(event, ui) {
                        event.preventDefault();
                        $(this).val('');
                        if (ui.item && ui.item.product_id) {
                            getPurchaseEntryRow(ui.item.product_id);
                        }
                    },
                }).autocomplete('instance')._renderItem = function(ul, item) {
                    return $('<li>')
                        .append('<div>' + escapeHtml(item.label) + '</div>')
                        .appendTo(ul);
                };
            } else {
                // Fallback: Simple search with manual dropdown
                console.log('jQuery UI autocomplete not available, using fallback');
                $('#search_product').on('input', function() {
                    var term = $(this).val();
                    clearTimeout(searchTimeout);
                    
                    if (term.length < 2) {
                        $('#product_search_results').remove();
                        return;
                    }
                    
                    searchTimeout = setTimeout(function() {
                        $.getJSON(
                            "{{ route('purchase_order.get_products') }}",
                            { term: term },
                            function(data) {
                                searchResults = data;
                                showProductDropdown(data, term);
                            }
                        ).fail(function() {
                            console.error('Failed to fetch products');
                        });
                    }, 300);
                });
                
                // Handle Enter key
                $('#search_product').on('keydown', function(e) {
                    if (e.keyCode === 13) { // Enter
                        e.preventDefault();
                        if (searchResults.length > 0 && searchResults[0].product_id) {
                            $(this).val('');
                            getPurchaseEntryRow(searchResults[0].product_id);
                            $('#product_search_results').remove();
                        }
                    }
                });
                
                // Handle click outside to close dropdown
                $(document).on('click', function(e) {
                    if (!$(e.target).closest('#search_product, #product_search_results').length) {
                        $('#product_search_results').remove();
                    }
                });
            }
        }
        
        function showProductDropdown(results, term) {
            $('#product_search_results').remove();
            
            if (results.length === 0) {
                return;
            }
            
            var dropdown = $('<ul id="product_search_results" class="list-group" style="position: absolute; width: 100%; max-height: 300px; overflow-y: auto; z-index: 1000; background: white; border: 1px solid #ddd; border-radius: 4px; margin-top: 2px;"></ul>');
            var input = $('#search_product');
            var offset = input.offset();
            
            dropdown.css({
                top: (offset.top + input.outerHeight()) + 'px',
                left: offset.left + 'px',
                width: input.outerWidth() + 'px'
            });
            
            $.each(results, function(index, item) {
                var li = $('<li class="list-group-item" style="cursor: pointer;"><a href="javascript:;" data-product-id="' + item.product_id + '" style="text-decoration: none; color: inherit;">' + escapeHtml(item.label) + '</a></li>');
                li.on('click', function() {
                    $('#search_product').val('');
                    getPurchaseEntryRow($(this).find('a').data('product-id'));
                    dropdown.remove();
                });
                li.hover(function() {
                    $(this).css('background-color', '#f5f5f5');
                }, function() {
                    $(this).css('background-color', 'white');
                });
                dropdown.append(li);
            });
            
            $('body').append(dropdown);
        }

        function getPurchaseEntryRow(productId) {
            if (productId) {
                var rowCount = parseInt($('#row_count').val()) || 0;
                var currencyId = $('#currency_id').val();
                if (!currencyId) {
                    currencyId = {{ company()->currency_id }};
                }
                
                $.easyAjax({
                    url: "{{ route('purchase_order.add_item') }}",
                    type: "GET",
                    data: {
                        id: productId,
                        currencyId: currencyId,
                        row_count: rowCount
                    },
                    blockUI: true,
                    success: function(response) {
                        if (response.status == 'success' && response.view) {
                            $('#purchase_entry_table tbody').append(response.view);
                            $('#row_count').val(rowCount + 1);
                            $('#total-table').removeClass('d-none');
                            
                            // Initialize select picker for new row (including tax multi-select)
                            var newRow = $('#purchase_entry_table tbody tr:last');
                            newRow.find('.select-picker').selectpicker();
                            newRow.find('.purchase_line_tax_id').selectpicker();
                            newRow.find('.select-picker').selectpicker('refresh');
                            newRow.find('.purchase_line_tax_id').selectpicker('refresh');
                            
                            // Initialize expiry month/year dropdowns (no datepicker needed)
                            newRow.find('.expiry_month, .expiry_year').on('change', function() {
                                var row = $(this).data('row');
                                var month = $('.expiry_month[data-row="' + row + '"]').val();
                                var year = $('.expiry_year[data-row="' + row + '"]').val();
                                
                                if (month && year) {
                                    // Set expiry date as first day of selected month/year
                                    var expiryDate = year + '-' + month + '-01';
                                    $('.expiry_date_hidden[data-row="' + row + '"]').val(expiryDate);
                                } else {
                                    $('.expiry_date_hidden[data-row="' + row + '"]').val('');
                                }
                            });
                            
                            updateTableSrNumber();
                            
                            // Calculate totals for all rows after a short delay to ensure DOM is updated
                            setTimeout(function() {
                                // Calculate each row's total
                                $('#purchase_entry_table tbody tr').each(function() {
                                    var rowNum = $(this).find('.purchase_quantity').first().data('row');
                                    if (rowNum) {
                                        calculateRowTotal(rowNum);
                                    }
                                });
                                // Then calculate the enhanced total
                                calculateEnhancedTotal();
                            }, 200);
                        } else {
                            console.error('Failed to add product:', response);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error adding product:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to add product. Please try again.'
                        });
                    }
                });
            }
        }

        function updateTableSrNumber() {
            $('#purchase_entry_table tbody tr').each(function(index) {
                var rowNum = index + 1;
                $(this).find('.sr-number').text(rowNum);
                $(this).attr('data-row-index', rowNum);
                // Update data-row attributes for all inputs in this row
                $(this).find('[data-row]').attr('data-row', rowNum);
            });
        }

        // Remove purchase entry row
        $(document).on('click', '.remove_purchase_entry_row', function() {
            Swal.fire({
                title: "{{ __('messages.sure') }}",
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then((value) => {
                if (value) {
                    $(this).closest('tr').remove();
                    updateTableSrNumber();
                    calculateEnhancedTotal();
                }
            });
        });

        // Enhanced row calculations
        $(document).on('input', '.purchase_unit_cost_without_discount, .inline_discounts, .purchase_quantity, .purchase_unit_cost_after_tax', function() {
            var row = $(this).data('row');
            if (row) {
                // Calculate purchase price when discount changes
                if ($(this).hasClass('inline_discounts') || $(this).hasClass('purchase_unit_cost_without_discount')) {
                    var ppWithoutDiscount = parseFloat($('.purchase_unit_cost_without_discount[data-row="' + row + '"]').val()) || 0;
                    var discountPercent = parseFloat($('.inline_discounts[data-row="' + row + '"]').val()) || 0;
                    var purchasePrice = ppWithoutDiscount * (1 - (discountPercent / 100));
                    
                    // Update visible purchase price field
                    $('.purchase_unit_cost[data-row="' + row + '"]').val(purchasePrice.toFixed(2));
                    
                    // Update hidden fields
                    $('.purchase_unit_cost[data-row="' + row + '"]').closest('tr').find('input[name="cost_per_item[]"]').val(purchasePrice.toFixed(2));
                    $('.purchase_unit_cost[data-row="' + row + '"]').closest('tr').find('input[name*="[purchase_price]"]').val(purchasePrice.toFixed(2));
                    $('.purchase_unit_cost[data-row="' + row + '"]').closest('tr').find('input[name*="[pp_without_discount]"]').val(ppWithoutDiscount.toFixed(2));
                    $('.purchase_unit_cost[data-row="' + row + '"]').closest('tr').find('input[name*="[discount_percent]"]').val(discountPercent.toFixed(2));
                    
                    // Recalculate tax-inclusive price based on new purchase price
                    var taxId = $('.purchase_line_tax_id[data-row="' + row + '"]').val();
                    var taxRate = 0;
                    if (taxId) {
                        taxRate = parseFloat($('.purchase_line_tax_id[data-row="' + row + '"] option:selected').data('rate')) || 0;
                    }
                    var purchasePriceIncTax = purchasePrice * (1 + (taxRate / 100));
                    $('.purchase_unit_cost_after_tax[data-row="' + row + '"]').val(purchasePriceIncTax.toFixed(2));
                    $('.purchase_unit_cost_after_tax[data-row="' + row + '"]').closest('tr').find('input[name*="[purchase_price_inc_tax]"]').val(purchasePriceIncTax.toFixed(2));
                }
                calculateRowTotal(row);
                calculateEnhancedTotal();
            }
        });

        $(document).on('change changed.bs.select', '.purchase_line_tax_id', function() {
            var row = $(this).data('row');
            if (row) {
                // Recalculate purchase_price_inc_tax based on current purchase price and all selected taxes
                var purchasePrice = parseFloat($('.purchase_unit_cost[data-row="' + row + '"]').val()) || 0;
                var totalTaxRate = 0;
                
                // Sum all selected tax rates (multi-select support)
                $(this).find('option:selected').each(function() {
                    var taxRate = parseFloat($(this).data('rate')) || 0;
                    totalTaxRate += taxRate;
                });
                
                var purchasePriceIncTax = purchasePrice * (1 + (totalTaxRate / 100));
                $('.purchase_unit_cost_after_tax[data-row="' + row + '"]').val(purchasePriceIncTax.toFixed(2));
                $('.purchase_unit_cost_after_tax[data-row="' + row + '"]').closest('tr').find('input[name*="[purchase_price_inc_tax]"]').val(purchasePriceIncTax.toFixed(2));
                
                calculateRowTotal(row);
                calculateEnhancedTotal();
            }
        });

        // Handle expiry month/year changes
        $(document).on('change', '.expiry_month, .expiry_year', function() {
            var row = $(this).data('row');
            var month = $('.expiry_month[data-row="' + row + '"]').val();
            var year = $('.expiry_year[data-row="' + row + '"]').val();
            
            if (month && year) {
                // Set expiry date as first day of selected month/year
                var expiryDate = year + '-' + month + '-01';
                $('.expiry_date_hidden[data-row="' + row + '"]').val(expiryDate);
            } else {
                $('.expiry_date_hidden[data-row="' + row + '"]').val('');
            }
        });

        function calculateRowTotal(row) {
            var quantity = parseFloat($('.purchase_quantity[data-row="' + row + '"]').val()) || 0;
            var purchasePrice = parseFloat($('.purchase_unit_cost[data-row="' + row + '"]').val()) || 0;
            var purchasePriceIncTax = parseFloat($('.purchase_unit_cost_after_tax[data-row="' + row + '"]').val()) || purchasePrice;
            
            // Calculate total tax rate from all selected taxes (multi-select support)
            var totalTaxRate = 0;
            $('.purchase_line_tax_id[data-row="' + row + '"] option:selected').each(function() {
                var rate = parseFloat($(this).data('rate')) || 0;
                totalTaxRate += rate;
            });
            
            // Calculate subtotal before tax
            var subtotalBeforeTax = quantity * purchasePrice;
            $('.row_subtotal_before_tax[data-row="' + row + '"]').text(subtotalBeforeTax.toFixed(2));
            $('.row_subtotal_before_tax_hidden[data-row="' + row + '"]').val(subtotalBeforeTax);
            
            // Calculate tax amount using total of all selected taxes
            var taxAmount = 0;
            if (totalTaxRate > 0) {
                if (purchasePriceIncTax > purchasePrice) {
                    taxAmount = (purchasePriceIncTax - purchasePrice) * quantity;
                } else {
                    taxAmount = subtotalBeforeTax * (totalTaxRate / 100);
                }
            }
            
            $('.purchase_product_unit_tax[data-row="' + row + '"]').val(taxAmount);
            
            // Calculate total after tax
            var totalAfterTax = subtotalBeforeTax + taxAmount;
            $('.row_subtotal_after_tax[data-row="' + row + '"]').text(totalAfterTax.toFixed(2));
            $('.row_subtotal_after_tax_hidden[data-row="' + row + '"]').val(totalAfterTax);
            $('.amount-html[data-row="' + row + '"]').text(totalAfterTax.toFixed(2));
            $('.amount[data-row="' + row + '"]').val(totalAfterTax);
        }

        function calculateEnhancedTotal() {
            var subtotal = 0;
            var taxTotal = 0;
            var taxList = {};
            
            // Calculate subtotal from all rows
            $('.row_subtotal_before_tax_hidden').each(function() {
                subtotal += parseFloat($(this).val()) || 0;
            });
            
            // Calculate tax from all rows (supports multiple taxes per row)
            $('.purchase_product_unit_tax').each(function() {
                var taxAmount = parseFloat($(this).val()) || 0;
                var row = $(this).data('row');
                
                // Get all selected taxes for this row (multi-select support)
                $('.purchase_line_tax_id[data-row="' + row + '"] option:selected').each(function() {
                    var taxName = $(this).text();
                    var taxRate = parseFloat($(this).data('rate')) || 0;
                    
                    if (taxName && taxRate > 0 && taxAmount > 0) {
                        // Calculate individual tax contribution (proportional to rate)
                        var quantity = parseFloat($('.purchase_quantity[data-row="' + row + '"]').val()) || 0;
                        var purchasePrice = parseFloat($('.purchase_unit_cost[data-row="' + row + '"]').val()) || 0;
                        var subtotalBeforeTax = quantity * purchasePrice;
                        var individualTaxAmount = subtotalBeforeTax * (taxRate / 100);
                        
                        if (typeof taxList[taxName] === 'undefined') {
                            taxList[taxName] = 0;
                        }
                        taxList[taxName] += individualTaxAmount;
                    }
                });
                
                taxTotal += taxAmount;
            });
            
            // Apply bill-level discount
            var discountType = $('#discount_type').val();
            var discountValue = parseFloat($('.discount_value').val()) || 0;
            var discountAmount = 0;
            
            if (discountType == 'percent' && discountValue > 0) {
                discountAmount = (subtotal / 100) * discountValue;
            } else if (discountType == 'fixed') {
                discountAmount = discountValue;
            }
            
            $('#discount_amount').text(discountAmount.toFixed(2));
            
            // Update tax display
            var taxHtml = '';
            $.each(taxList, function(key, value) {
                taxHtml += '<tr><td class="text-dark-grey">' + key + '</td><td><span class="tax-percent">' + value.toFixed(2) + '</span></td></tr>';
            });
            
            if (taxHtml == '') {
                taxHtml = '<tr><td colspan="2"><span class="tax-percent">0.00</span></td></tr>';
            }
            
            $('#invoice-taxes').html(taxHtml);
            
            // Calculate final total
            var totalAfterDiscount = subtotal - discountAmount;
            totalAfterDiscount = totalAfterDiscount < 0 ? 0 : totalAfterDiscount;
            var total = totalAfterDiscount + taxTotal;
            
            $('.sub-total').text(subtotal.toFixed(2));
            $('.sub-total-field').val(subtotal);
            $('.total').text(total.toFixed(2));
            $('.total-field').val(total);
        }
        
        // Handle discount value and type changes
        $(document).on('input keyup change', '.discount_value', function() {
            calculateEnhancedTotal();
        });
        
        $(document).on('change', '#discount_type', function() {
            calculateEnhancedTotal();
        });

        $(document).on('click', '#add-item', function() {

            var i = $(document).find('.item_name').length;
            var item = ' <div class="d-flex px-4 py-3 c-inv-desc item-row">' +
                '<div class="c-inv-desc-table w-100 d-lg-flex d-md-flex d-block">' +
                '<table width="100%">' +
                '<tbody>' +
                '<tr class="text-dark-grey font-weight-bold f-14">' +
                '<td width="{{ $invoiceSetting->hsn_sac_code_show ? '40%' : '50%' }}" class="border-0 inv-desc-mbl btlr">@lang('app.description')</td>';

            if (hsn_status == 1) {
                item += '<td width="10%" class="border-0" align="right">@lang('app.hsnSac')</td>';
            }

            item +=
                `<td width="10%" class="border-0" align="right">@lang("modules.invoices.qty")</td>
                <td width="10%" class="border-0" align="right">@lang('modules.invoices.unitPrice')</td>
                <td width="13%" class="border-0" align="right">@lang('modules.invoices.tax')</td>
                <td width="17%" class="border-0 bblr-mbl" align="right">@lang('modules.invoices.amount')</td>
                </tr>` +
                '<tr>' +
                '<td class="border-bottom-0 btrr-mbl btlr">' +
                '<input type="text" class="f-14 border-0 w-100 item_name form-control" name="item_name[]" placeholder="@lang('modules.expenses.itemName')">' +
                '</td>' +
                '<td class="border-bottom-0 d-block d-lg-none d-md-none">' +
                '<textarea class="f-14 border-0 w-100 mobile-description form-control" name="item_summary[]" placeholder="@lang('placeholders.invoices.description')"></textarea>' +
                '</td>';

            if (hsn_status == 1) {
                item += '<td class="border-bottom-0">' +
                    '<input type="text" min="1" class="f-14 border-0 w-100 text-right hsn_sac_code form-control" value="" name="hsn_sac_code[]">' +
                    '</td>';
            }

            item += '<td class="border-bottom-0">' +
                '<input type="number" min="1" class="form-control f-14 border-0 w-100 text-right quantity mt-3" value="1" name="quantity[]">' +
                `<select class="text-dark-grey float-right border-0 f-12" name="unit_id[]">
                    @foreach ($units as $unit)
                        <option
                        @if ($unit->default == 1) selected @endif
                        value="{{ $unit->id }}">{{ $unit->unit_type }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="product_id[]" value="">`+
                '</td>' +
                '<td class="border-bottom-0">' +
                '<input type="number" min="1" class="f-14 border-0 w-100 text-right cost_per_item" placeholder="0.00" value="0" name="cost_per_item[]">' +
                '</td>' +
                '<td class="border-bottom-0">' +
                '<div class="select-others height-35 rounded border-0">' +
                '<select id="multiselect' + i + '" name="taxes[' + i +
                '][]" multiple="multiple" class="select-picker type customSequence" data-size="3">'
            @foreach ($taxes as $tax)
                +'<option data-rate="{{ $tax->rate_percent }}" data-tax-text="{{ strtoupper($tax->tax_name) .':'. $tax->rate_percent }}%" value="{{ $tax->id }}">' +
                '{{ strtoupper($tax->tax_name) }}:{{ $tax->rate_percent }}%</option>'
            @endforeach +
            '</select>' +
            '</div>' +
            '</td>' +
            '<td rowspan="2" align="right" valign="top" class="bg-amt-grey btrr-bbrr">' +
            '<span class="amount-html">0.00</span>' +
            '<input type="hidden" class="amount" name="amount[]" value="0">' +
            '</td>' +
            '</tr>' +
            '<tr class="d-none d-md-table-row d-lg-table-row">' +
            '<td colspan="{{ $invoiceSetting->hsn_sac_code_show ? 4 : 3 }}" class="dash-border-top bblr">' +
            '<textarea class="f-14 border-0 w-100 desktop-description form-control" name="item_summary[]" placeholder="@lang('placeholders.invoices.description')"></textarea>' +
            '</td>' +
            '<td class="border-left-0">' +
            '<input type="file" class="dropify" id="dropify' + i +
                '" name="order_item_image[]" data-allowed-file-extensions="png jpg jpeg" data-messages-default="test" data-height="70" /><input type="hidden" name="order_item_image_url[]">' +
                '</td>' +
                '</tr>' +
                '</tbody>' +
                '</table>' +
                '</div>' +
                '<a href="javascript:;" class="d-flex align-items-center justify-content-center ml-3 remove-item"><i class="fa fa-times-circle f-20 text-lightest"></i></a>' +
                '</div>';
            $(item).hide().appendTo("#sortable").fadeIn(500);
            $('#multiselect' + i).selectpicker();

            $('#dropify' + i).dropify({
                messages: dropifyMessages
            });
        });

        $('#saveOrderForm').on('click', '.remove-item', function() {
            $(this).closest('.item-row').fadeOut(300, function() {
                $(this).remove();
                $('select.customSequence').each(function(index) {
                    $(this).attr('name', 'taxes[' + index + '][]');
                    $(this).attr('id', 'multiselect' + index + '');
                });
                calculateTotal();
            });
        });

        $('.save-form').click(function() {
            var type = $(this).data('type');

            if (KTUtil.isMobileDevice()) {
                $('.desktop-description').remove();
            } else {
                $('.mobile-description').remove();
            }

            calculateTotal();

            var discount = $('#discount_amount').html();
            var total = $('.sub-total-field').val();

            if (parseFloat(discount) > parseFloat(total)) {
                Swal.fire({
                    icon: 'error',
                    text: "{{ __('messages.discountExceed') }}",

                    customClass: {
                        confirmButton: 'btn btn-primary',
                    },
                    showClass: {
                        popup: 'swal2-noanimation',
                        backdrop: 'swal2-noanimation'
                    },
                    buttonsStyling: false
                });
                return false;
            }

            $.easyAjax({
                url: "{{ route('purchase-order.store') }}" + "?type=" + type,
                container: '#saveOrderForm',
                type: "POST",
                blockUI: true,
                redirect: true,
                file: true,  // Commented so that we dot get error of Input variables exceeded 1000
                data: $('#saveOrderForm').serialize(),
                success: function(response) {

                    if (response.status === 'success') {
                        if (typeof orderDropzone !== 'undefined' && orderDropzone.getQueuedFiles().length > 0) {
                            orderID = response.orderID;
                            $('#orderID').val(response.orderID);
                            (response.add_more == true) ? localStorage.setItem("redirect_order", window.location.href) : localStorage.setItem("redirect_order", response.redirectUrl);
                            orderDropzone.processQueue();
                        }
                        else {
                            window.location.href = response.redirectUrl;
                        }
                    }
                }
            })
        });

        $('#saveOrderForm').on('keyup', '.quantity,.cost_per_item,.item_name, .discount_value', function() {
            var quantity = $(this).closest('.item-row').find('.quantity').val();
            var perItemCost = $(this).closest('.item-row').find('.cost_per_item').val();
            var amount = (quantity * perItemCost);

            $(this).closest('.item-row').find('.amount').val(decimalupto2(amount));
            $(this).closest('.item-row').find('.amount-html').html(decimalupto2(amount));

            calculateTotal();
        });

        $('#saveOrderForm').on('change', '.type, #discount_type, #calculate_tax', function() {
            var quantity = $(this).closest('.item-row').find('.quantity').val();
            var perItemCost = $(this).closest('.item-row').find('.cost_per_item').val();
            var amount = (quantity * perItemCost);

            $(this).closest('.item-row').find('.amount').val(decimalupto2(amount));
            $(this).closest('.item-row').find('.amount-html').html(decimalupto2(amount));

            calculateTotal();
        });

        $('#saveOrderForm').on('input', '.quantity', function() {
            var quantity = $(this).closest('.item-row').find('.quantity').val();
            var perItemCost = $(this).closest('.item-row').find('.cost_per_item').val();
            var amount = (quantity * perItemCost);

            $(this).closest('.item-row').find('.amount').val(decimalupto2(amount));
            $(this).closest('.item-row').find('.amount-html').html(decimalupto2(amount));

            calculateTotal();
        });

        calculateTotal();

        init(RIGHT_MODAL);
    });

    function ucWord(str) {
        str = str.toLowerCase().replace(/\b[a-z]/g, function(letter) {
            return letter.toUpperCase();
        });
        return str;
    }

    function checkboxChange(parentClass, id) {
        var checkedData = '';
        $('.' + parentClass).find("input[type= 'checkbox']:checked").each(function() {
            checkedData = (checkedData !== '') ? checkedData + ', ' + $(this).val() : $(this).val();
        });
        $('#' + id).val(checkedData);
    }

    $('#currency_id').change(function() {
        var curId = $(this).val();
        var companyCurrencyName = "{{$companyCurrency->currency_code}}";
        var currentCurrencyName = $('#currency_id option:selected').attr('data-currency-code');
        var companyCurrency = '{{ $companyCurrency->id }}';

        // Hide exchange rate if currencies are the same
        if(curId == companyCurrency){
            $('#exchange_rate_container').hide();
            $('#exchange_rate').prop('readonly', true);
        } else{
            $('#exchange_rate_container').show();
            $('#exchange_rate').prop('readonly', false);
        }
        var token = "{{ csrf_token() }}";

        $.easyAjax({
            url: "{{ route('payments.account_list') }}",
            container: '#saveOrderForm',
            type: "GET",
            blockUI: true,
            data: { 'curId' : curId , _token: token},
            success: function(response) {
                if (response.status == 'success') {
                    $('#bank_account_id').html(response.data);
                    $('#bank_account_id').selectpicker('refresh');
                    $('#exchange_rate').val(response.exchangeRate);
                    $('#currency_exchange').html('( '+companyCurrencyName+' @lang('app.to') '+currentCurrencyName+' )');
                }
            }
        });
    });

    $('#vendor_id').change(function(){
        var vendorId = $(this).val();
        var companyCurrency = "{{ $companyCurrency->exchange_rate }}"
        var companyCurrencyCode = "{{ $companyCurrency->currency_code }}"
        var companyCurrencyId = "{{ $companyCurrency->id }}"
        if(vendorId)
        {
            $('#products').removeClass('d-none');
        }
        else
        {
            $('#products').addClass('d-none');
        }
        var url = "{{route('purchase_order.vendor_currency')}}" + "?id=" + vendorId;
        $.easyAjax({
            url: url,
            container: '#saveOrderForm',
            type: "GET",
            blockUI: true,
            success: function(response) {
                if(response.data == null){
                    $('#currency_id').html('<option>'+' -- ' +'</option>');
                    $('#currency_id').selectpicker('refresh');
                    $('#exchange_rate').val(companyCurrency);
                    $('#exchange_rate').selectpicker('refresh');
                    $('#currency').html('<span>' + companyCurrencyCode + '</span>');
                    $('#currency').selectpicker('refresh');
                    // Hide exchange rate if no currency selected
                    $('#exchange_rate_container').hide();
                }
                else
                {
                    $('#currency_id').html('<option value="'+response.data.id+'">'+response.data.currency_code+ ' ('+response.data.currency_symbol+')'+'</option>');
                    $('#currency_id').selectpicker('refresh');
                    $('#currency').html('<span>' + response.data.currency_code+ '</span>')
                    $('#exchange_rate').val(response.data.exchange_rate);
                    $('#exchange_rate').selectpicker('refresh');
                    
                    // Show/hide exchange rate based on currency match
                    if(response.data.id == companyCurrencyId){
                        $('#exchange_rate_container').hide();
                    } else {
                        $('#exchange_rate_container').show();
                    }
                    
                    // Set vendor address if available
                    if(response.vendor_address_id && response.vendor_address_location){
                        $('#address_id').val(response.vendor_address_id);
                        $('#vendor_address_text').text(response.vendor_address_location);
                        $('#vendor_address_display').show();
                    } else {
                        $('#vendor_address_display').hide();
                        $('#address_id').val('');
                    }
                }
            }
        });
    });
    
    // Check initial state on page load and set vendor address
    $(document).ready(function(){
        var selectedCurrency = $('#currency_id').val();
        var companyCurrencyId = "{{ $companyCurrency->id }}";
        if(selectedCurrency == companyCurrencyId || !selectedCurrency){
            $('#exchange_rate_container').hide();
        } else {
            $('#exchange_rate_container').show();
        }
        
        // Set vendor address on initial load if vendor is pre-selected
        @if($purchaseVendorID && $vendorAddress)
            // Address is already set in the display, just ensure hidden field is set
            $('#address_id').val({{ $purchaseVendorID->address_id }});
            $('#vendor_address_display').show();
        @else
            // If vendor is selected but address not set in backend, fetch it via AJAX
            var vendorId = $('#vendor_id').val();
            if(vendorId) {
                var url = "{{route('purchase_order.vendor_currency')}}" + "?id=" + vendorId;
                $.easyAjax({
                    url: url,
                    container: '#saveOrderForm',
                    type: "GET",
                    blockUI: false,
                    success: function(response) {
                        if(response.vendor_address_id && response.vendor_address_location){
                            $('#address_id').val(response.vendor_address_id);
                            $('#vendor_address_text').text(response.vendor_address_location);
                            $('#vendor_address_display').show();
                        } else {
                            $('#vendor_address_display').hide();
                            $('#address_id').val('');
                        }
                    }
                });
            } else {
                // No vendor selected, hide address display
                $('#vendor_address_display').hide();
            }
        @endif
    });

</script>

<!-- Quick Add Product Modal -->
<div class="modal fade quick_add_product_modal" id="quick_add_product_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('purchase::modules.product.add_new_product')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Content will be loaded here via AJAX -->
            </div>
        </div>
    </div>
</div>
