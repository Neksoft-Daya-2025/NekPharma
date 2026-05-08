@php
$addProductPermission = user()->permission('add_product');
@endphp

<link rel="stylesheet" href="{{ asset('vendor/css/jquery-ui.css') }}">
<style>
    /* Fix tax dropdown scrolling - CRITICAL */
    .bootstrap-select.purchase_line_tax_id .dropdown-menu,
    .purchase_line_tax_id.bootstrap-select .dropdown-menu,
    div.bootstrap-select:has(.purchase_line_tax_id) .dropdown-menu {
        max-height: 350px !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
        height: auto !important;
    }
    
    /* Force inner menu to scroll */
    .bootstrap-select .dropdown-menu .inner {
        max-height: 250px !important;
        overflow-y: auto !important;
    }
    
    /* Ensure dropdown is not clipped */
    .table-responsive {
        overflow: visible !important;
    }
    
    #purchase_entry_table {
        overflow: visible !important;
    }
    
    /* Better action buttons */
    .bootstrap-select .bs-actionsbox {
        padding: 8px 12px;
        border-bottom: 1px solid #ddd;
        background: #f8f9fa;
    }
    
    .bootstrap-select .bs-actionsbox .btn-group button {
        font-size: 11px;
        padding: 4px 8px;
    }
</style>

<!-- BULK PURCHASE ENTRY START -->
<div class="bg-white rounded b-shadow-4 create-inv">
    <div class="px-lg-4 px-md-4 px-3 py-3">
        <h4 class="mb-0 f-21 font-weight-normal text-capitalize">
            {{ isset($purchaseDetail) ? 'Edit Purchase Entry' : 'Add Purchase Entries (Bulk)' }}
        </h4>
    </div>
    <hr class="m-0 border-top-grey">
    
    <x-form class="c-inv-form" id="savePurchaseEntryForm">
                        @if(isset($purchaseDetail))
            <input type="hidden" name="purchase_detail_id" value="{{ $purchaseDetail->id }}">
                        @endif
        
        <!-- INVOICE DETAILS -->
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-lg-3 col-md-6">
                <div class="form-group my-3">
                    <label class="f-14 text-dark-grey mb-12" data-label="true">
                        {{ __('Invoice Number') }} <sup class="text-danger">*</sup>
                                    </label>
                    <input type="text" name="invoice_number" id="invoice_number" class="form-control height-35 f-14" value="{{ request('invoice_number', $autoInvoiceNumber ?? '') }}" placeholder="Enter invoice number" required>
                                </div>
                            </div>
            <div class="col-lg-3 col-md-6">
                <div class="form-group my-3">
                    <label class="f-14 text-dark-grey mb-12" data-label="true">
                        {{ __('Invoice Date') }} <sup class="text-danger">*</sup>
                                    </label>
                    <input type="date" name="invoice_date" id="invoice_date" class="form-control height-35 f-14" value="{{ request('invoice_date', date('Y-m-d')) }}" required>
                                </div>
                            </div>
            <div class="col-lg-3 col-md-6">
                <div class="form-group my-3">
                    <label class="f-14 text-dark-grey mb-12" data-label="true">
                        {{ __('Reference Number') }}
                                    </label>
                    <input type="text" name="reference_number" id="reference_number" class="form-control height-35 f-14" placeholder="e.g. PO-2024-001">
                                        </div>
                                        </div>
            <div class="col-lg-3 col-md-6">
                <div class="form-group my-3">
                    <label class="f-14 text-dark-grey mb-12" data-label="true">
                        {{ __('Reference Date') }}
                    </label>
                    <input type="date" name="reference_date" id="reference_date" class="form-control height-35 f-14">
                                    </div>
                                </div>
                            </div>

        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-lg-3 col-md-6">
                <div class="form-group my-3">
                    <label class="f-14 text-dark-grey mb-12" data-label="true">
                        {{ __('Mode of Payment') }}
                    </label>
                    <input type="text" name="mode_of_payment" id="mode_of_payment" class="form-control height-35 f-14" placeholder="e.g. Cash, Cheque">
                            </div>
                            </div>
            <div class="col-lg-3 col-md-6">
                <div class="form-group my-3">
                    <label class="f-14 text-dark-grey mb-12" data-label="true">
                        {{ __('Dispatch Through') }}
                    </label>
                    <input type="text" name="dispatch_through" id="dispatch_through" class="form-control height-35 f-14" placeholder="e.g. FedEx, DHL">
                            </div>
                            </div>
            <div class="col-lg-3 col-md-6">
                <div class="form-group my-3">
                    <label class="f-14 text-dark-grey mb-12" data-label="true">
                        {{ __('Destination') }}
                                    </label>
                    <input type="text" name="destination" id="destination" class="form-control height-35 f-14" placeholder="e.g. Mumbai, Delhi">
                                </div>
                            </div>
            <div class="col-lg-3 col-md-6">
                <div class="form-group my-3">
                    <label class="f-14 text-dark-grey mb-12" data-label="true">
                        {{ __('Terms of Delivery') }}
                                </label>
                    <input type="text" name="terms_of_delivery" id="terms_of_delivery" class="form-control height-35 f-14" placeholder="Delivery terms">
                            </div>
                        </div>
                        </div>
                        
        <hr class="m-0 border-top-grey">
        
        <!-- VENDOR & PAYMENT STATUS -->
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-lg-6 col-md-6">
                <x-forms.label fieldId="vendor_id" :fieldLabel="__('purchase::app.vendor')" fieldRequired="true">
                </x-forms.label>
                <div class="select-others height-35 rounded">
                    <select class="form-control select-picker" name="vendor_id" id="vendor_id" data-live-search="true" required>
                        <option value="">-- {{ __('purchase::app.selectVendor') }} --</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" {{ (isset($purchaseDetail) && $purchaseDetail->vendor_id == $vendor->id) || request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                {{ $vendor->primary_name }}{{ $vendor->company_name ? ' - ' . $vendor->company_name : '' }}
                            </option>
                        @endforeach
                    </select>
                                        </div>
                                    </div>
            <div class="col-lg-6 col-md-6">
                <x-forms.label fieldId="payment_status" :fieldLabel="__('Payment Status')" fieldRequired="true">
                                    </x-forms.label>
                <div class="select-others height-35 rounded">
                    <select class="form-control select-picker" name="payment_status" id="payment_status" required>
                        <option value="">-- Select Status --</option>
                        <option value="pending" selected>Pending</option>
                        <option value="partial">Partial</option>
                        <option value="paid">Paid</option>
                                        </select>
                                </div>
                                </div>
                            </div>

        <!-- Supplier invoice total (optional – for matching) -->
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-lg-6 col-md-6">
                <x-forms.label fieldId="supplier_invoice_total" :fieldLabel="__('app.supplierInvoiceTotal')">
                </x-forms.label>
                <input type="number" name="supplier_invoice_total" id="supplier_invoice_total" class="form-control height-35 f-14" value="{{ request('supplier_invoice_total', '') }}" placeholder="Amount on vendor invoice (optional)" step="0.01" min="0">
                <small class="form-text text-muted">{{ __('app.supplierInvoiceTotalHelp') }}</small>
            </div>
        </div>

        <hr class="m-0 border-top-grey">
        
        <!-- PRODUCT SEARCH -->
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-md-5">
                <label class="f-14 text-dark-grey mb-2">Search Product</label>
                                    <div class="input-group">
                    <span class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                    </span>
                    <input type="text" class="form-control" id="search_product" 
                        placeholder="Type product name or SKU..." autocomplete="off">
                                        </div>
                                    </div>
            <div class="col-md-5">
                <label class="f-14 text-dark-grey mb-2">OR Select Product</label>
                <select class="form-control select-picker" id="product_dropdown" data-live-search="true">
                    <option value="">-- Select Product --</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}{{ $p->sku ? ' (SKU: ' . $p->sku . ')' : '' }}</option>
                    @endforeach
                </select>
                                </div>
            <div class="col-md-2">
                @if ($addProductPermission == 'all' || $addProductPermission == 'added')
                    <label class="f-14 text-dark-grey mb-2">&nbsp;</label>
                    <a href="{{ route('purchase-products.create') }}" class="btn btn-primary btn-block openRightModal">
                        <i class="fa fa-plus"></i> New
                    </a>
                @endif
                            </div>
                        </div>

        <!-- PRODUCT TABLE -->
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-sm-12">
                <div class="table-responsive">
                    <table class="table table-condensed table-bordered table-th-green text-center table-striped" id="purchase_entry_table">
                        <thead>
                            <tr>
                                <th width="3%">#</th>
                                <th width="13%">Product</th>
                                @if ($invoiceSetting->hsn_sac_code_show)
                                    <th width="5%">HSN</th>
                                    @endif
                                <th width="5%">Billed Qty <span class="text-danger">*</span></th>
                                <th width="5%">Total Qty <span class="text-danger">*</span></th>
                                <th width="6%">Batch Number <span class="text-danger">*</span></th>
                                <th width="6%">Expiry <span class="text-danger">*</span></th>
                                <th width="5%">Purchase Price <span class="text-danger">*</span></th>
                                <th width="5%">MRP <span class="text-danger">*</span></th>
                                <th width="5%">PTS <span class="text-danger">*</span></th>
                                <th width="5%">PTR <span class="text-danger">*</span></th>
                                <th width="4%">Disc %</th>
                                <th width="9%">Tax</th>
                                <th width="6%">Total</th>
                                <th width="2%"></th>
                            </tr>
                        </thead>
                        <tbody id="sortable">
                            @if(isset($purchaseDetail))
                                <!-- Edit mode: show existing entry -->
                            @endif
                        </tbody>
                    </table>
                            </div>
                <input type="hidden" id="row_count" value="0">
                        </div>
                        </div>

        <hr class="m-0 border-top-grey">
        
        <!-- TOTALS -->
        <div class="d-flex px-lg-4 px-md-4 px-3 pb-3 c-inv-total">
            <table width="100%" class="text-right f-14 text-capitalize" id="total-table" style="display: none;">
                                            <tbody>
                                                <tr>
                        <td width="50%" class="border-0"></td>
                        <td width="50%" class="p-0 border-0">
                            <table width="100%">
                                <tbody>
                                    <tr>
                                        <td colspan="2" class="border-top-0 text-dark-grey">Subtotal</td>
                                        <td width="30%" class="border-top-0 sub-total">0.00</td>
                                                </tr>
                                    <tr>
                                        <td>Tax</td>
                                        <td colspan="2" class="p-0 border-0">
                                            <table width="100%" id="invoice-taxes">
                                                <tr><td colspan="2"><span class="tax-percent">0.00</span></td></tr>
                                            </table>
                                                    </td>
                                                </tr>
                                    <tr class="bg-amt-grey f-16 f-w-500">
                                        <td colspan="2">Total</td>
                                        <td><span class="total">0.00</span></td>
                                    </tr>
                                </tbody>
                            </table>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                        </div>

        <!-- BUTTONS -->
                <x-form-actions>
            <x-forms.button-primary id="save-purchase-entry" icon="check">@lang('app.save')</x-forms.button-primary>
            <x-forms.button-cancel :link="route('purchase-products.index')" class="border-0">@lang('app.cancel')</x-forms.button-cancel>
                </x-form-actions>

    </x-form>
</div>

<script src="{{ asset('vendor/jquery/jquery-ui.min.js') }}"></script>
<script>
$(document).ready(function() {
    $('.select-picker').selectpicker();
    // Ensure prefilled values from "Add purchase entries for this invoice" display correctly
    $('#vendor_id, #product_dropdown').selectpicker('refresh');

    var productsData = @json($products ?? []);
    console.log('Products loaded:', productsData.length);
    
    // Product dropdown selection
    $('#product_dropdown').on('change', function() {
            var productId = $(this).val();
        if (productId) {
            addProductRow(parseInt(productId));
            $(this).val('').selectpicker('refresh');
        }
    });
    
    // Product search autocomplete
    if ($('#search_product').length > 0 && typeof $.fn.autocomplete !== 'undefined') {
        $('#search_product').autocomplete({
            source: function(request, response) {
                var term = request.term.toLowerCase();
                var results = productsData.filter(function(item) {
                    var name = (item.name || '').toLowerCase();
                    var sku = (item.sku || '').toLowerCase();
                    return name.indexOf(term) !== -1 || sku.indexOf(term) !== -1;
                }).map(function(item) {
                    return {
                        label: item.name + (item.sku ? ' (SKU: ' + item.sku + ')' : ''),
                        value: item.name,
                        product_id: item.id
                    };
                });
                response(results.slice(0, 20));
            },
            minLength: 2,
            select: function(event, ui) {
                event.preventDefault();
                $(this).val('');
                if (ui.item && ui.item.product_id) {
                    addProductRow(ui.item.product_id);
                }
            }
        });
                        } else {
        // Fallback if jQuery UI autocomplete not available
        $('#search_product').on('keyup', function(e) {
            if (e.key === 'Enter') {
                var term = $(this).val().toLowerCase();
                var product = productsData.find(function(p) {
                    return (p.name || '').toLowerCase() === term || (p.sku || '').toLowerCase() === term;
                });
                if (product) {
                    $(this).val('');
                    addProductRow(product.id);
                }
                }
            });
        }

    function addProductRow(productId) {
        var rowCount = parseInt($('#row_count').val()) || 0;
        var product = productsData.find(p => p.id == productId);
        
        if (!product) {
            console.error('Product not found:', productId);
            return;
        }
        
        rowCount++;
        var row = createProductRow(product, rowCount);
        $('#purchase_entry_table tbody').append(row);
        $('#row_count').val(rowCount);
        $('#total-table').show();
        
        // Initialize selectpicker - tax dropdown only gets special config
        var $newRow = $('#purchase_entry_table tbody tr:last');
        $newRow.find('.purchase_line_tax_id').selectpicker({
            size: 8,
            liveSearch: true,
            actionsBox: true
        });
        
        updateTableSrNumber();
        calculateGrandTotal();
    }
    
    function createProductRow(product, rowNum) {
        console.log('Creating row for product:', product.name, 'ID:', product.id);
        
        var hsnCol = {{ $invoiceSetting->hsn_sac_code_show ? 'true' : 'false' }};
        var taxOptions = '';
        @foreach ($taxes as $tax)
            taxOptions += '<option value="{{ $tax->id }}" data-rate="{{ $tax->rate_percent }}">{{ $tax->tax_name }}: {{ $tax->rate_percent }}%</option>';
        @endforeach
        
        var productName = product.name || 'Unknown Product';
        var productHsn = product.hsn_sac_code || product.sku || '';
        
        var row = `
            <tr class="purchase-entry-row" id="row-${rowNum}">
                <td><span class="sr-number">${rowNum}</span></td>
                <td class="text-left">
                    <strong>${productName}</strong>
                    ${productHsn ? '<br><small class="text-muted">HSN: ' + productHsn + '</small>' : ''}
                    <input type="hidden" name="product_id[]" value="${product.id}">
                </td>
                ${hsnCol ? '<td><input type="text" class="form-control input-sm" name="hsn_sac_code[]" value="' + productHsn + '" readonly></td>' : ''}
                <td><input type="number" name="quantity[]" value="1" class="form-control input-sm purchase_quantity" data-row="${rowNum}" min="1" required></td>
                <td><input type="number" name="total_quantity[]" value="1" class="form-control input-sm total_quantity_input" data-row="${rowNum}" min="1" required></td>
                <td><input type="text" class="form-control input-sm" name="batch[]" placeholder="Batch Number" data-row="${rowNum}" required></td>
                <td><input type="month" class="form-control input-sm" name="expiry[]" data-row="${rowNum}" required></td>
                <td><input type="number" name="purchase_price[]" value="0" class="form-control input-sm purchase_price_input" data-row="${rowNum}" step="0.01" min="0" required></td>
                <td><input type="number" name="mrp[]" value="0" class="form-control input-sm mrp_input" data-row="${rowNum}" step="0.01" min="0" required></td>
                <td><input type="number" name="pts[]" value="0" class="form-control input-sm pts_input" data-row="${rowNum}" step="0.01" min="0" required></td>
                <td><input type="number" name="ptr[]" value="0" class="form-control input-sm ptr_input" data-row="${rowNum}" step="0.01" min="0" required></td>
                <td><input type="number" name="discount[]" value="0" class="form-control input-sm discount_input" data-row="${rowNum}" step="0.01" min="0" max="100"></td>
                <td>
                    <select name="purchase_line_tax_id[${rowNum - 1}][]" multiple="multiple" class="form-control select-picker purchase_line_tax_id" data-row="${rowNum}" data-live-search="true" data-size="8" data-actions-box="true">
                        ${taxOptions}
                    </select>
                </td>
                <td><strong class="row_total">0.00</strong><input type="hidden" class="row_total_hidden" value="0" data-row="${rowNum}"></td>
                <td><button type="button" class="btn btn-sm btn-danger remove_row"><i class="fa fa-times"></i></button></td>
            </tr>
        `;
        
        console.log('Row created successfully');
        return row;
    }
    
    function updateTableSrNumber() {
        $('#purchase_entry_table tbody tr').each(function(index) {
            $(this).find('.sr-number').text(index + 1);
        });
    }
    
    // Remove row
    $(document).on('click', '.remove_row', function() {
        $(this).closest('tr').remove();
        updateTableSrNumber();
        calculateGrandTotal();
    });
    
    // Calculate on change
    $(document).on('input change changed.bs.select', '.purchase_quantity, .purchase_price_input, .discount_input, .purchase_line_tax_id', function() {
        var $row = $(this).closest('tr');
        var isTaxChange = $(this).hasClass('purchase_line_tax_id');
        
        if (isTaxChange) {
            var $taxSelect = $(this);
            var selectedTaxes = $taxSelect.selectpicker('val') || [];
            console.log('Tax changed:', {
                'selected_taxes': selectedTaxes,
                'row': $row.find('.sr-number').text()
            });
        }
        
        calculateRowTotal($row);
        calculateGrandTotal();
    });
    
    function calculateRowTotal($row) {
        var qty = parseFloat($row.find('.purchase_quantity').val()) || 0;
        var purchasePrice = parseFloat($row.find('.purchase_price_input').val()) || 0;
        var disc = parseFloat($row.find('.discount_input').val()) || 0;
        
        var base = qty * purchasePrice;
        var discAmt = base * (disc / 100);
        var afterDisc = base - discAmt;
        
        // Calculate tax using selectpicker's val() method to get selected tax IDs
        var totalTaxRate = 0;
        var $taxSelect = $row.find('.purchase_line_tax_id');
        var selectedTaxIds = $taxSelect.selectpicker('val') || [];
        
        // If selectpicker method doesn't work, fallback to regular val()
        if (!selectedTaxIds || selectedTaxIds.length === 0) {
            selectedTaxIds = $taxSelect.val() || [];
        }
        
        // Ensure it's an array
        if (!Array.isArray(selectedTaxIds)) {
            selectedTaxIds = [selectedTaxIds];
        }
        
        // Sum up tax rates from selected tax IDs
        selectedTaxIds.forEach(function(taxId) {
            var $taxOption = $taxSelect.find('option[value="' + taxId + '"]');
            if ($taxOption.length) {
                var taxRate = parseFloat($taxOption.data('rate')) || 0;
                totalTaxRate += taxRate;
            }
        });
        
        var taxAmt = afterDisc * (totalTaxRate / 100);
        var total = afterDisc + taxAmt;
        
        $row.find('.row_total').text(total.toFixed(2));
        $row.find('.row_total_hidden').val(total.toFixed(2));
        
        // Debug logging
        console.log('Row Total Calculated:', {
            'qty': qty,
            'price': purchasePrice,
            'base': base,
            'discount': disc,
            'after_discount': afterDisc,
            'selected_taxes': selectedTaxIds,
            'total_tax_rate': totalTaxRate,
            'tax_amount': taxAmt,
            'total': total
        });
    }
    
    function calculateGrandTotal() {
        var subtotal = 0;
        var taxList = {};
        
        $('#purchase_entry_table tbody tr').each(function() {
            var $row = $(this);
            var qty = parseFloat($row.find('.purchase_quantity').val()) || 0;
            var purchasePrice = parseFloat($row.find('.purchase_price_input').val()) || 0;
            var disc = parseFloat($row.find('.discount_input').val()) || 0;
            
            var base = qty * purchasePrice;
            var discAmt = base * (disc / 100);
            var afterDisc = base - discAmt;
            
            // Use selectpicker's val() method to get selected tax IDs
            var $taxSelect = $row.find('.purchase_line_tax_id');
            var selectedTaxIds = $taxSelect.selectpicker('val') || [];
            
            // If selectpicker method doesn't work, fallback to regular val()
            if (!selectedTaxIds || selectedTaxIds.length === 0) {
                selectedTaxIds = $taxSelect.val() || [];
            }
            
            // Ensure it's an array
            if (!Array.isArray(selectedTaxIds)) {
                selectedTaxIds = [selectedTaxIds];
            }
            
            // Calculate tax amounts for each selected tax
            selectedTaxIds.forEach(function(taxId) {
                var $taxOption = $taxSelect.find('option[value="' + taxId + '"]');
                if ($taxOption.length) {
                    var taxName = $taxOption.text();
                    var taxRate = parseFloat($taxOption.data('rate')) || 0;
                    var taxAmt = afterDisc * (taxRate / 100);
                    
                    if (taxName && taxAmt > 0) {
                        taxList[taxName] = (taxList[taxName] || 0) + taxAmt;
                    }
                }
            });
            
            subtotal += parseFloat($row.find('.row_total_hidden').val()) || 0;
        });
        
        var taxHtml = '';
        $.each(taxList, function(key, value) {
            taxHtml += '<tr><td class="text-dark-grey">' + key + '</td><td><span>' + value.toFixed(2) + '</span></td></tr>';
        });
        $('#invoice-taxes').html(taxHtml || '<tr><td colspan="2">0.00</td></tr>');
        
        $('.sub-total').text(subtotal.toFixed(2));
        $('.total').text(subtotal.toFixed(2));
    }
    
    // Save
    $('#save-purchase-entry').click(function() {
        if (!$('#vendor_id').val()) {
            alert('Please select a vendor.');
            return false;
        }
        
        if ($('#purchase_entry_table tbody tr').length === 0) {
            alert('Please add at least one product.');
            return false;
        }
        
        // CRITICAL: Ensure selectpicker values are synced to underlying select elements before serialization
        console.log('=== Syncing Tax Values Before Submit ===');
        $('#purchase_entry_table tbody tr').each(function(index) {
            const taxSelect = $(this).find('.purchase_line_tax_id');
            const taxValues = taxSelect.selectpicker('val') || [];
            
            // Explicitly set the values on the underlying select element
            taxSelect.val(taxValues);
            
            console.log('Row ' + (index + 1) + ':', {
                'taxes': taxValues,
                'tax-select-name': taxSelect.attr('name'),
                'underlying-select-value': taxSelect.val(),
                'row-total': $(this).find('.row_total').text()
            });
        });
        
        // Refresh selectpickers to ensure sync
        $('.purchase_line_tax_id').selectpicker('refresh');
        
        // Now serialize the form
        const formData = $('#savePurchaseEntryForm').serialize();
        console.log('Form Data:', formData);
        console.log('========================================');
        
            $.easyAjax({
            url: "{{ route('purchase-entries.store') }}",
            container: '#savePurchaseEntryForm',
            type: "POST",
                disableButton: true,
                blockUI: true,
            buttonSelector: "#save-purchase-entry",
            data: formData,
                success: function(response) {
                if (response.status == 'success') {
                    var redirectUrl = response.redirectUrl || "{{ route('purchase-entries.index') }}";
                    if (response.matchMessage && (response.matchStatus === 'matched' || response.matchStatus === 'unmatched')) {
                        if (typeof $.showToastr !== 'undefined') {
                            $.showToastr(response.matchMessage, response.matchStatus === 'matched' ? 'success' : 'warning');
                        }
                        window.location.href = redirectUrl;
                    } else if (redirectUrl) {
                        window.location.href = redirectUrl;
                    } else if (typeof RIGHT_MODAL !== 'undefined') {
                        $(RIGHT_MODAL_CONTENT).html('');
                        $(RIGHT_MODAL).modal('hide');
                        if (window.LaravelDataTables && window.LaravelDataTables["products-table"]) {
                            window.LaravelDataTables["products-table"].draw(false);
                        }
                    } else {
                        window.location.href = "{{ route('purchase-entries.index') }}";
                    }
                }
                }
            });
    });
    
                    init(RIGHT_MODAL);
    });
</script>
