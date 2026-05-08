@extends('layouts.app')

@section('content')

@php
$firstProduct = $products->first();
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

<!-- EDIT INVOICE START -->
<div class="content-wrapper">
    <div class="bg-white rounded b-shadow-4 create-inv">
        <div class="px-lg-4 px-md-4 px-3 py-3">
            <h4 class="mb-0 f-21 font-weight-normal text-capitalize">
                Edit Invoice: {{ $invoiceNumber }}
            </h4>
        </div>
        <hr class="m-0 border-top-grey">
        
        <x-form class="c-inv-form" id="editInvoiceForm">
            
            <!-- INVOICE DETAILS -->
            <div class="row px-lg-4 px-md-4 px-3 py-3">
                <div class="col-lg-3 col-md-6">
                    <div class="form-group my-3">
                        <label class="f-14 text-dark-grey mb-12" data-label="true">
                            {{ __('Invoice Number') }}
                        </label>
                        <input type="text" class="form-control height-35 f-14" value="{{ $invoiceNumber }}" readonly>
                        <input type="hidden" name="invoice_number" value="{{ $invoiceNumber }}">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="form-group my-3">
                        <label class="f-14 text-dark-grey mb-12" data-label="true">
                            {{ __('Invoice Date') }} <sup class="text-danger">*</sup>
                        </label>
                        <input type="date" name="invoice_date" id="invoice_date" class="form-control height-35 f-14" value="{{ $firstProduct->invoice_date ? \Carbon\Carbon::parse($firstProduct->invoice_date)->format('Y-m-d') : date('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="form-group my-3">
                        <label class="f-14 text-dark-grey mb-12" data-label="true">
                            {{ __('Reference Number') }}
                        </label>
                        <input type="text" name="reference_number" id="reference_number" class="form-control height-35 f-14" value="{{ $firstProduct->reference_number ?? '' }}" placeholder="e.g. PO-2024-001">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="form-group my-3">
                        <label class="f-14 text-dark-grey mb-12" data-label="true">
                            {{ __('Reference Date') }}
                        </label>
                        <input type="date" name="reference_date" id="reference_date" class="form-control height-35 f-14" value="{{ $firstProduct->reference_date ? \Carbon\Carbon::parse($firstProduct->reference_date)->format('Y-m-d') : '' }}">
                    </div>
                </div>
            </div>
            
            <div class="row px-lg-4 px-md-4 px-3 py-3">
                <div class="col-lg-3 col-md-6">
                    <div class="form-group my-3">
                        <label class="f-14 text-dark-grey mb-12" data-label="true">
                            {{ __('Mode of Payment') }}
                        </label>
                        <select name="mode_of_payment" id="mode_of_payment" class="form-control height-35 f-14 select-picker">
                            <option value="">Select Mode</option>
                            <option value="cash" {{ ($firstProduct->mode_of_payment ?? '') == 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="cheque" {{ ($firstProduct->mode_of_payment ?? '') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                            <option value="online" {{ ($firstProduct->mode_of_payment ?? '') == 'online' ? 'selected' : '' }}>Online Transfer</option>
                            <option value="credit" {{ ($firstProduct->mode_of_payment ?? '') == 'credit' ? 'selected' : '' }}>Credit</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="form-group my-3">
                        <label class="f-14 text-dark-grey mb-12" data-label="true">
                            {{ __('Dispatch Through') }}
                        </label>
                        <input type="text" name="dispatch_through" id="dispatch_through" class="form-control height-35 f-14" value="{{ $firstProduct->dispatch_through ?? '' }}" placeholder="e.g. Blue Dart">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="form-group my-3">
                        <label class="f-14 text-dark-grey mb-12" data-label="true">
                            {{ __('Destination') }}
                        </label>
                        <input type="text" name="destination" id="destination" class="form-control height-35 f-14" value="{{ $firstProduct->destination ?? '' }}" placeholder="City/Location">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="form-group my-3">
                        <label class="f-14 text-dark-grey mb-12" data-label="true">
                            {{ __('Terms of Delivery') }}
                        </label>
                        <input type="text" name="terms_of_delivery" id="terms_of_delivery" class="form-control height-35 f-14" value="{{ $firstProduct->terms_of_delivery ?? '' }}" placeholder="e.g. FOB, CIF">
                    </div>
                </div>
            </div>
            
            <div class="row px-lg-4 px-md-4 px-3 py-3">
                <div class="col-lg-4 col-md-6">
                    <div class="form-group my-3">
                        <label class="f-14 text-dark-grey mb-12" data-label="true">
                            {{ __('purchase::app.vendor') }} <sup class="text-danger">*</sup>
                        </label>
                        <select name="vendor_id" id="vendor_id" class="form-control height-35 f-14 select-picker" data-live-search="true" required>
                            <option value="">{{ __('purchase::app.selectVendor') }}</option>
                            @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ $firstProduct->vendor_id == $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->primary_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="form-group my-3">
                        <label class="f-14 text-dark-grey mb-12" data-label="true">
                            {{ __('Payment Status') }} <sup class="text-danger">*</sup>
                        </label>
                        <select name="payment_status" id="payment_status" class="form-control height-35 f-14 select-picker" required>
                            <option value="pending" {{ ($firstProduct->payment_status ?? 'pending') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="partial" {{ ($firstProduct->payment_status ?? '') == 'partial' ? 'selected' : '' }}>Partial</option>
                            <option value="paid" {{ ($firstProduct->payment_status ?? '') == 'paid' ? 'selected' : '' }}>Paid</option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="form-group my-3">
                        <label class="f-14 text-dark-grey mb-12" data-label="true">
                            {{ __('app.supplierInvoiceTotal') }}
                        </label>
                        <input type="number" name="supplier_invoice_total" id="supplier_invoice_total" class="form-control height-35 f-14" value="{{ isset($supplierInvoice) && $supplierInvoice ? ($supplierInvoice->supplier_invoice_total ?? '') : '' }}" placeholder="Amount on supplier invoice (optional)" step="0.01" min="0">
                        <small class="form-text text-muted">{{ __('app.supplierInvoiceTotalHelp') }}</small>
                    </div>
                </div>
            </div>
            
            <hr class="m-0 border-top-grey">
            
            <!-- PRODUCTS TABLE -->
            <div class="row px-lg-4 px-md-4 px-3 py-3">
                <div class="col-sm-12">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="purchase_entry_table">
                            <thead>
                                <tr>
                                    <th width="20%">Product <span class="text-danger">*</span></th>
                                    <th width="8%">Billed Qty <span class="text-danger">*</span></th>
                                    <th width="8%">Total Qty <span class="text-danger">*</span></th>
                                    <th width="10%">Batch Number <span class="text-danger">*</span></th>
                                    <th width="8%">Expiry <span class="text-danger">*</span></th>
                                    <th width="8%">Purchase Price <span class="text-danger">*</span></th>
                                    <th width="8%">MRP <span class="text-danger">*</span></th>
                                    <th width="8%">PTS <span class="text-danger">*</span></th>
                                    <th width="8%">PTR <span class="text-danger">*</span></th>
                                    <th width="6%">Disc %</th>
                                    <th width="12%">Tax</th>
                                    <th width="10%">Total</th>
                                    <th width="5%">
                                        <button type="button" class="btn btn-sm btn-secondary" id="apply-tax-to-all" title="Apply first row tax to all">
                                            <i class="fa fa-copy"></i>
                                        </button>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="sortable">
                                @foreach($products as $index => $product)
                                <tr id="row-{{ $index + 1 }}">
                                    <td>
                                        <select name="product_id[]" class="form-control select-picker product-select" data-live-search="true" data-size="8" required>
                                            <option value="">Select Product</option>
                                            @foreach($allProducts as $p)
                                                <option value="{{ $p->id }}" 
                                                    data-mrp="{{ $p->price }}" 
                                                    data-pts="{{ $p->pts ?? 0 }}" 
                                                    data-ptr="{{ $p->ptr ?? 0 }}"
                                                    data-hsn="{{ $p->hsn_sac_code ?? '' }}"
                                                    {{ $product->product_id == $p->id ? 'selected' : '' }}>
                                                    {{ $p->name }} @if($p->hsn_sac_code) - HSN: {{ $p->hsn_sac_code }} @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" name="quantity[]" class="form-control quantity-input" value="{{ $product->quantity }}" min="1" required></td>
                                    <td><input type="number" name="total_quantity[]" class="form-control total-quantity-input" value="{{ $product->total_quantity ?? $product->quantity }}" min="1" required></td>
                                    <td><input type="text" name="batch[]" class="form-control batch-input" value="{{ $product->batch ?? '' }}" required></td>
                                    <td><input type="month" name="expiry[]" class="form-control expiry-input" value="{{ $product->expiry ? $product->expiry->format('Y-m') : '' }}" required></td>
                                    <td><input type="number" step="0.01" name="purchase_price[]" class="form-control purchase-price-input" value="{{ $product->purchase_price }}" required></td>
                                    <td><input type="number" step="0.01" name="mrp[]" class="form-control mrp-input" value="{{ $product->mrp }}" required></td>
                                    <td><input type="number" step="0.01" name="pts[]" class="form-control pts-input" value="{{ $product->pts }}" required></td>
                                    <td><input type="number" step="0.01" name="ptr[]" class="form-control ptr-input" value="{{ $product->ptr }}" required></td>
                                    <td><input type="number" step="0.01" name="discount[]" class="form-control discount-input" value="{{ $product->discount ?? 0 }}"></td>
                                    <td>
                                        @php
                                            // Ensure tax is an array and convert IDs to integers for comparison
                                            $productTaxes = [];
                                            if ($product->tax) {
                                                if (is_array($product->tax)) {
                                                    $productTaxes = array_map('intval', $product->tax);
                                                } elseif (is_string($product->tax)) {
                                                    $decoded = json_decode($product->tax, true);
                                                    $productTaxes = is_array($decoded) ? array_map('intval', $decoded) : [];
                                                }
                                            }
                                        @endphp
                                        <select name="purchase_line_tax_id[{{ $index }}][]" class="form-control select-picker purchase_line_tax_id" multiple data-actions-box="true" data-live-search="true" data-size="8" data-selected-taxes="{{ json_encode($productTaxes) }}">
                                            @foreach($taxes as $tax)
                                                @php
                                                    $isSelected = in_array((int)$tax->id, $productTaxes, true);
                                                @endphp
                                                <option value="{{ $tax->id }}" {{ $isSelected ? 'selected' : '' }}>
                                                    {{ $tax->tax_name }}: {{ $tax->rate_percent }}%
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="total[]" class="form-control total-input" value="{{ number_format($product->total, 2) }}" readonly></td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-primary mt-2" id="add-product-row">
                        <i class="fa fa-plus"></i> Add Product
                    </button>
                </div>
            </div>
            
            <!-- TOTALS -->
            <div class="row px-lg-4 px-md-4 px-3 py-3 bg-light">
                <div class="col-md-6 offset-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th>Subtotal:</th>
                            <td class="text-right"><strong id="subtotal-display">0.00</strong></td>
                        </tr>
                        <tr>
                            <th>Tax:</th>
                            <td class="text-right"><strong id="tax-display">0.00</strong></td>
                        </tr>
                        <tr class="border-top">
                            <th>Grand Total:</th>
                            <td class="text-right"><h4 id="grand-total-display">0.00</h4></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- BUTTONS -->
            <x-form-actions class="c-inv-btns d-block d-lg-flex d-md-flex px-lg-4 px-md-4 px-3 py-3">
                <div class="d-flex mb-3 mb-lg-0 mb-md-0">
                    <x-forms.button-primary id="save-invoice" icon="check" class="btn-lg">@lang('app.update')</x-forms.button-primary>
                </div>
                @if(isset($supplierInvoice) && $supplierInvoice && $supplierInvoice->id)
                    <a href="{{ route('supplier-invoices.show', $supplierInvoice->id) }}" class="btn btn-secondary btn-lg border-0 mr-2">@lang('app.supplierInvoice')</a>
                @endif
                <x-forms.link-secondary :link="route('purchase-entries.index')" class="border-0">@lang('app.cancel')</x-forms.link-secondary>
            </x-form-actions>
            
        </x-form>
    </div>
</div>

<script src="{{ asset('vendor/jquery/jquery-ui.min.js') }}"></script>
<script>
$(document).ready(function() {
    $('.select-picker').selectpicker();

    // Initialize tax dropdowns with selected values from server
    $('.purchase_line_tax_id').each(function(index) {
        const $select = $(this);
        let selectedTaxes = $select.data('selected-taxes');

        if (typeof selectedTaxes === 'string') {
            try {
                selectedTaxes = JSON.parse(selectedTaxes);
            } catch(e) {
                selectedTaxes = [];
            }
        }
        if (!Array.isArray(selectedTaxes)) {
            selectedTaxes = [];
        }
        selectedTaxes = selectedTaxes.map(id => String(id));

        if (selectedTaxes && selectedTaxes.length > 0) {
            $select.find('option').each(function() {
                const optionValue = String($(this).val());
                $(this).prop('selected', selectedTaxes.includes(optionValue));
            });
        }

        $select.selectpicker({
            size: 8,
            liveSearch: true,
            actionsBox: true
        });
        if (selectedTaxes && selectedTaxes.length > 0) {
            $select.selectpicker('val', selectedTaxes);
        }
        $select.selectpicker('refresh');
    });

    let rowIndex = {{ $products->count() }};

    // Calculate totals on load
    calculateGrandTotal();
    
    // Add product row
    $('#add-product-row').click(function() {
        rowIndex++;
        const newRow = `
            <tr id="row-${rowIndex}">
                <td>
                    <select name="product_id[]" class="form-control select-picker product-select" data-live-search="true" data-size="8" required>
                        <option value="">Select Product</option>
                        @foreach($allProducts as $p)
                            <option value="{{ $p->id }}" 
                                data-mrp="{{ $p->price }}" 
                                data-pts="{{ $p->pts ?? 0 }}" 
                                data-ptr="{{ $p->ptr ?? 0 }}"
                                data-hsn="{{ $p->hsn_sac_code ?? '' }}">
                                {{ $p->name }} @if($p->hsn_sac_code) - HSN: {{ $p->hsn_sac_code }} @endif
                            </option>
                        @endforeach
                    </select>
                </td>
                <td><input type="number" name="quantity[]" class="form-control quantity-input" value="1" min="1" required></td>
                <td><input type="number" name="total_quantity[]" class="form-control total-quantity-input" value="1" min="1" required></td>
                <td><input type="text" name="batch[]" class="form-control batch-input" required></td>
                <td><input type="month" name="expiry[]" class="form-control expiry-input" required></td>
                <td><input type="number" step="0.01" name="purchase_price[]" class="form-control purchase-price-input" value="0" required></td>
                <td><input type="number" step="0.01" name="mrp[]" class="form-control mrp-input" value="0" required></td>
                <td><input type="number" step="0.01" name="pts[]" class="form-control pts-input" value="0" required></td>
                <td><input type="number" step="0.01" name="ptr[]" class="form-control ptr-input" value="0" required></td>
                <td><input type="number" step="0.01" name="discount[]" class="form-control discount-input" value="0"></td>
                <td>
                    <select name="purchase_line_tax_id[${rowIndex}][]" class="form-control select-picker purchase_line_tax_id" multiple data-actions-box="true" data-live-search="true" data-size="8">
                        @foreach($taxes as $tax)
                            <option value="{{ $tax->id }}">{{ $tax->tax_name }}: {{ $tax->rate_percent }}%</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="text" name="total[]" class="form-control total-input" value="0.00" readonly></td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-trash"></i></button>
                </td>
            </tr>
        `;
        
        $('#sortable').append(newRow);
        $('.select-picker').selectpicker('refresh');
        $('.purchase_line_tax_id').last().selectpicker({
            size: 8,
            liveSearch: true,
            actionsBox: true
        });
    });
    
    // Remove row
    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        calculateGrandTotal();
    });
    
    // Product selection - autofill prices
    $(document).on('change', '.product-select', function() {
        const row = $(this).closest('tr');
        const selected = $(this).find(':selected');
        
        row.find('.mrp-input').val(selected.data('mrp') || 0);
        row.find('.pts-input').val(selected.data('pts') || 0);
        row.find('.ptr-input').val(selected.data('ptr') || 0);
        
        calculateRowTotal(row);
    });
    
    // Calculate on input change
    $(document).on('input', '.quantity-input, .purchase-price-input, .discount-input', function() {
        calculateRowTotal($(this).closest('tr'));
    });
    
    // Calculate on tax change
    $(document).on('changed.bs.select', '.purchase_line_tax_id', function() {
        calculateRowTotal($(this).closest('tr'));
    });
    
    // Apply tax to all
    $('#apply-tax-to-all').click(function() {
        const firstRowTax = $('#sortable tr:first').find('.purchase_line_tax_id');
        const selectedTaxes = firstRowTax.selectpicker('val');
        
        if (!selectedTaxes || selectedTaxes.length === 0) {
            alert('Please select at least one tax in the first row.');
            return;
        }
        
        $('.purchase_line_tax_id').each(function() {
            $(this).selectpicker('val', selectedTaxes);
            calculateRowTotal($(this).closest('tr'));
        });
    });
    
    function calculateRowTotal(row) {
        const qty = parseFloat(row.find('.quantity-input').val()) || 0;
        const purchasePrice = parseFloat(row.find('.purchase-price-input').val()) || 0;
        const discount = parseFloat(row.find('.discount-input').val()) || 0;
        
        let subtotal = purchasePrice * qty;
        let discountAmount = (subtotal * discount) / 100;
        let afterDiscount = subtotal - discountAmount;
        
        // Tax calculation
        let taxAmount = 0;
        const selectedTaxes = row.find('.purchase_line_tax_id').selectpicker('val') || [];
        
        selectedTaxes.forEach(function(taxId) {
            const taxOption = row.find('.purchase_line_tax_id option[value="' + taxId + '"]');
            const taxText = taxOption.text();
            const taxRate = parseFloat(taxText.split(':')[1]) || 0;
            taxAmount += (afterDiscount * taxRate) / 100;
        });
        
        const total = afterDiscount + taxAmount;
        row.find('.total-input').val(total.toFixed(2));
        
        calculateGrandTotal();
    }
    
    function calculateGrandTotal() {
        let subtotal = 0;
        let totalTax = 0;
        
        $('#sortable tr').each(function() {
            const qty = parseFloat($(this).find('.quantity-input').val()) || 0;
            const purchasePrice = parseFloat($(this).find('.purchase-price-input').val()) || 0;
            const discount = parseFloat($(this).find('.discount-input').val()) || 0;
            
            let rowSubtotal = purchasePrice * qty;
            let discountAmount = (rowSubtotal * discount) / 100;
            let afterDiscount = rowSubtotal - discountAmount;
            
            subtotal += afterDiscount;
            
            const selectedTaxes = $(this).find('.purchase_line_tax_id').selectpicker('val') || [];
            selectedTaxes.forEach(function(taxId) {
                const taxOption = $(this).find('.purchase_line_tax_id option[value="' + taxId + '"]');
                const taxText = taxOption.text();
                const taxRate = parseFloat(taxText.split(':')[1]) || 0;
                totalTax += (afterDiscount * taxRate) / 100;
            }.bind(this));
        });
        
        const grandTotal = subtotal + totalTax;
        
        $('#subtotal-display').text(subtotal.toFixed(2));
        $('#tax-display').text(totalTax.toFixed(2));
        $('#grand-total-display').text(grandTotal.toFixed(2));
    }
    
    // Save form
    $('#save-invoice').click(function(e) {
        e.preventDefault();
        
        const form = $('#editInvoiceForm');
        
        if (form[0].checkValidity() === false) {
            form[0].reportValidity();
            return;
        }
        
        // Debug: Log batch and tax values before submit
        console.log('=== Values Before Submit ===');
        
        // CRITICAL: Ensure selectpicker values are synced to underlying select elements before serialization
        $('#sortable tr').each(function(index) {
            const taxSelect = $(this).find('.purchase_line_tax_id');
            const taxValues = taxSelect.selectpicker('val') || [];
            
            // Explicitly set the values on the underlying select element
            taxSelect.val(taxValues);
            
            const batchValue = $(this).find('.batch-input').val();
            console.log('Row ' + (index + 1) + ':', {
                'batch': batchValue,
                'taxes': taxValues,
                'tax-select-name': taxSelect.attr('name'),
                'underlying-select-value': taxSelect.val()
            });
        });
        
        // Refresh selectpickers to ensure sync
        $('.purchase_line_tax_id').selectpicker('refresh');
        
        // Now serialize the form
        const formData = form.serialize();
        console.log('Form Data:', formData);
        console.log('==================================');
        
        $.easyAjax({
            url: "{{ route('purchase-entries.update-invoice', $invoiceNumber) }}",
            container: '#editInvoiceForm',
            type: "POST",
            disableButton: true,
            blockUI: true,
            buttonSelector: "#save-invoice",
            data: formData,
            success: function(response) {
                console.log('Update response:', response);
                if (response.status === 'success') {
                    var redirectUrl = response.redirectUrl || "{{ route('purchase-entries.index') }}";
                    if (response.matchMessage && (response.matchStatus === 'matched' || response.matchStatus === 'unmatched')) {
                        if (typeof $.showToastr !== 'undefined') {
                            $.showToastr(response.matchMessage, response.matchStatus === 'matched' ? 'success' : 'warning');
                        }
                        window.location.href = redirectUrl;
                    } else if (redirectUrl) {
                        window.location.href = redirectUrl;
                    } else {
                        window.location.href = "{{ route('purchase-entries.index') }}";
                    }
                }
            },
            error: function(response) {
                console.error('Update error:', response);
                // Error will be shown by easyAjax automatically
            }
        });
    });
});
</script>

@endsection
