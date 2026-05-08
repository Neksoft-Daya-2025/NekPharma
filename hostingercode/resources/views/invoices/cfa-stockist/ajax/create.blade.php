@php
$addProductPermission = user()->permission('add_product');
@endphp

<link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/css/jquery-ui.css') }}">

<style>
    .invoice-wrapper {
        background: #ffffff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    
    .invoice-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px 40px;
        border-bottom: 4px solid #5a67d8;
    }
    
    .invoice-header h2 {
        margin: 0;
        font-size: 28px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    .invoice-header .subtitle {
        margin-top: 5px;
        font-size: 14px;
        opacity: 0.9;
    }
    
    .invoice-meta-section {
        background: #f8f9fa;
        padding: 25px 40px;
        border-bottom: 1px solid #e9ecef;
    }
    
    .meta-label {
        font-size: 12px;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }
    
    .client-section {
        padding: 25px 40px;
        background: #ffffff;
        border-bottom: 2px solid #e9ecef;
    }
    
    .section-title {
        font-size: 16px;
        font-weight: 600;
        color: #495057;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e9ecef;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .product-selection-section {
        padding: 25px 40px;
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
    }
    
    /* Batch selection UI removed - products are added directly */
    
    .items-section {
        padding: 30px 40px;
        background: #ffffff;
    }
    
    .items-table-wrapper {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .amount-html {
        font-weight: 600;
        font-size: 16px;
        color: #28a745;
    }
    
    .summary-section {
        padding: 30px 40px;
        background: #f8f9fa;
        border-top: 2px solid #dee2e6;
    }
    
    .summary-table {
        width: 100%;
        max-width: 400px;
        margin-left: auto;
    }
    
    .summary-table td {
        padding: 12px 15px;
        font-size: 14px;
    }
    
    .summary-table .summary-label {
        font-weight: 500;
        color: #6c757d;
        text-align: right;
    }
    
    .summary-table .summary-value {
        font-weight: 600;
        color: #212529;
        text-align: right;
        font-size: 15px;
    }
    
    .summary-table .summary-total {
        background: #667eea;
        color: white;
        font-size: 18px;
        font-weight: 700;
        border-radius: 6px;
    }
    
    .summary-table .summary-total .summary-label,
    .summary-table .summary-total .summary-value {
        color: white;
    }
    
    .action-section {
        padding: 25px 40px;
        background: #ffffff;
        border-top: 1px solid #e9ecef;
    }
</style>

<div class="invoice-wrapper">
    <div class="invoice-header">
        <h2>CFA/Stockist Invoice</h2>
        <div class="subtitle">Create a new invoice for CFA Stockists</div>
    </div>
    
    <x-form class="c-inv-form" id="saveInvoiceForm">
        <input type="hidden" name="do_it_later" id="doItLater" value="direct">
        <input type="hidden" name="invoice_type" value="cfa_stockist">
        @php
            // Check if this is an IGST invoice
            $isIGST = (isset($invoiceType) && $invoiceType == 'igst') || request('type') == 'igst';
        @endphp
        @if($isIGST)
            <input type="hidden" name="type" id="invoice_type_hidden" value="igst">
        @endif
        <input type="hidden" name="discount_type" value="percent">
        <input type="hidden" name="calculate_tax" value="after_discount">
        <input type="hidden" name="status" value="unpaid">

        <div class="invoice-meta-section">
            <div class="row">
                <div class="col-md-3">
                    <div class="meta-label">@lang('modules.invoices.invoiceNumber')</div>
                    <x-forms.input-group>
                        <x-slot name="prepend">
                            <span class="input-group-text bg-white">{{ invoice_setting()->invoice_prefix }}{{ invoice_setting()->invoice_number_separator }}{{ $zero }}</span>
                        </x-slot>
                        <input type="number" name="invoice_number" id="invoice_number" class="form-control height-35 f-15"
                            value="{{ is_null($lastInvoice) ? 1 : $lastInvoice }}">
                    </x-forms.input-group>
                </div>
                
                <div class="col-md-2">
                    <div class="meta-label">@lang('modules.invoices.invoiceDate')</div>
                    <input type="text" id="invoice_date" name="issue_date"
                        class="form-control height-35 f-15"
                        placeholder="@lang('placeholders.date')"
                        value="{{ now(company()->timezone)->format(company()->date_format) }}">
                </div>
                
                <div class="col-md-2">
                    <div class="meta-label">@lang('app.dueDate')</div>
                    <input type="text" id="due_date" name="due_date"
                        class="form-control height-35 f-15"
                        placeholder="@lang('placeholders.date')"
                        value="{{ now(company()->timezone)->addDays($invoiceSetting->due_after)->format(company()->date_format) }}">
                </div>
                
                <div class="col-md-2">
                    <div class="meta-label">@lang('modules.invoices.currency')</div>
                    <div class="select-others height-35 rounded">
                        <select class="form-control select-picker" name="currency_id" id="currency_id">
                            @foreach ($currencies as $currency)
                                <option @selected($currency->id == company()->currency_id)
                                    value="{{ $currency->id }}" 
                                    data-exchange-rate="{{$currency->exchange_rate ?? 1}}">
                                    {{ $currency->currency_code . ' (' . $currency->currency_symbol . ')' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="meta-label">@lang('modules.currencySettings.exchangeRate')</div>
                    <input type="number" id="exchange_rate" name="exchange_rate"
                        class="form-control height-35 f-15" 
                        value="{{ $companyCurrency->exchange_rate ?? 1 }}" readonly>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-3">
                    <div class="meta-label">L.R. No.</div>
                    <input type="text" name="lr_number" id="lr_number"
                        class="form-control height-35 f-15"
                        placeholder="Enter L.R. Number">
                </div>
                
                <div class="col-md-3">
                    <div class="meta-label">L.R. Date</div>
                    <input type="text" id="lr_date" name="lr_date"
                        class="form-control height-35 f-15"
                        placeholder="@lang('placeholders.date')"
                        value="{{ now(company()->timezone)->format(company()->date_format) }}">
                </div>
            </div>
        </div>

        <div class="client-section">
            <div class="row">
                <div class="col-md-6" style="border-right: 1px solid #e9ecef; padding-right: 30px;">
                    <div class="section-title">Billed By (CFA/Distributor)</div>
                    @if(in_array('admin', user_roles()))
                    <div>
                        <x-forms.label fieldId="cfa_distributor_id" :fieldLabel="__('CFA/Distributor')" fieldRequired="true">
                        </x-forms.label>
                        <div class="select-others height-35 rounded">
                            <select class="form-control select-picker" name="cfa_distributor_id" id="cfa_distributor_id" data-live-search="true">
                                <option value="">Select CFA/Distributor</option>
                                @foreach ($cfaDistributors as $distributor)
                                    <option value="{{ $distributor->id }}">
                                        {{ $distributor->company_name ?? $distributor->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <small class="form-text text-muted">Select which CFA/Distributor is billing this invoice</small>
                    </div>
                    @else
                    <div>
                        <x-forms.label fieldId="cfa_distributor_display" :fieldLabel="__('CFA/Distributor')">
                        </x-forms.label>
                        <div class="form-control height-35 f-15 bg-light" style="border: 1px solid #dee2e6; border-radius: 4px; padding: 6px 12px;">
                            {{ user()->clientDetails->company_name ?? user()->name }}
                        </div>
                        <input type="hidden" name="cfa_distributor_id" id="cfa_distributor_id" value="{{ user()->id }}">
                        <small class="form-text text-muted">You are billing this invoice</small>
                    </div>
                    @endif
                </div>
                
                <div class="col-md-6" style="padding-left: 30px;">
                    <div class="section-title">Bill To</div>
                    <div>
                        <x-forms.label fieldId="cfa_stockist_id" :fieldLabel="__('CFA Stockist')" fieldRequired="true">
                        </x-forms.label>
                        <div class="select-others height-35 rounded">
                            <select class="form-control select-picker" name="cfa_stockist_id" id="cfa_stockist_id" data-live-search="true">
                                <option value="">Select CFA Stockist</option>
                                @foreach ($cfaStockists as $stockist)
                                    <option value="{{ $stockist->id }}">
                                        {{ ($stockist->cfa_stockist_id ? $stockist->cfa_stockist_id . ' - ' : '') . $stockist->shopname }}
                                        @if($stockist->fullname) - {{ $stockist->fullname }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <small class="form-text text-muted">Select the CFA Stockist who will receive this invoice</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="items-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="section-title mb-0" style="border-bottom: none;">Invoice Items</div>
            </div>
            <div class="items-table-wrapper">
                <div id="sortable"></div>
            </div>
            
            <!-- Add Product Section - Simplified and Straightforward -->
            <div class="dynamic-add-product-section mt-4 p-3" style="background: #f8f9fa; border-radius: 6px; border: 1px solid #dee2e6;">
                <div class="section-title mb-3" style="font-size: 14px; border-bottom: 1px solid #dee2e6; padding-bottom: 10px;">Add Products from Distributor Inventory</div>
                <div class="row align-items-end">
                    <div class="col-md-5">
                        <x-forms.label fieldId="dynamic-add-products" fieldLabel="Select Product">
                        </x-forms.label>
                        <div class="select-others height-35 rounded">
                            <select class="form-control select-picker" id="dynamic-add-products" data-live-search="true" data-size="8">
                                <option value="">-- Select Product --</option>
                            </select>
                        </div>
                        <small class="form-text text-muted mt-1">
                            <i class="fa fa-info-circle"></i> Products from CFA/Distributor Inventory
                        </small>
                    </div>
                    <div class="col-md-3">
                        <x-forms.label fieldId="dynamic-product-quantity" fieldLabel="Quantity">
                        </x-forms.label>
                        <input type="number" class="form-control" id="dynamic-product-quantity" 
                               value="1" min="1" placeholder="Qty" style="height: 35px;">
                    </div>
                    <div class="col-md-4">
                        <x-forms.label fieldId="add-product-btn" fieldLabel="&nbsp;">
                        </x-forms.label>
                        <button type="button" class="btn btn-primary btn-block" id="add-product-btn" style="height: 35px;">
                            <i class="fa fa-plus"></i> Add Product
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="summary-section">
            <table class="summary-table">
                <tbody>
                    <tr>
                        <td class="summary-label">Sub Total</td>
                        <td class="summary-value">
                            <span id="sub_total">0.00</span>
                            <input type="hidden" name="sub_total" id="sub_total_input" value="0.00">
                        </td>
                    </tr>
                    <tr>
                        <td class="summary-label">Discount</td>
                        <td class="summary-value">
                            <span id="discount_amount">0.00</span>
                            <input type="hidden" name="discount" id="discount_input" value="0.00">
                        </td>
                    </tr>
                    <tr>
                        <td class="summary-label">Tax</td>
                        <td class="summary-value">
                            <span id="tax_amount">0.00</span>
                            <input type="hidden" name="tax_amount" id="tax_amount_input" value="0.00">
                        </td>
                    </tr>
                    <tr class="summary-total">
                        <td class="summary-label">Total</td>
                        <td class="summary-value">
                            <span id="total">0.00</span>
                            <input type="hidden" name="total" id="total_input" value="0.00">
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="action-section">
            <div class="d-flex justify-content-end">
                <x-forms.button-cancel :link="route('invoices.index')" class="mr-3">@lang('app.cancel')</x-forms.button-cancel>
                <x-forms.button-primary id="save-form" icon="check" class="btn-lg">@lang('app.save')</x-forms.button-primary>
            </div>
        </div>
    </x-form>
</div>

<script>
var addedBatches = {};

// Initialize addedBatches with existing items on page load (for edit mode)
$(document).ready(function() {
    $('.item-row').each(function() {
        var cfaDistributorStockId = $(this).find('input[name="cfa_distributor_stock_id[]"]').val();
        if (cfaDistributorStockId) {
            addedBatches[cfaDistributorStockId] = true;
        }
    });
    // Initialize addedBatches to prevent duplicates
});
var allBatches = [];
var currentProductId = null;

$(document).ready(function() {
    if (typeof datepicker !== 'undefined' && typeof datepickerConfig !== 'undefined') {
        datepicker('#invoice_date', { position: 'bl', ...datepickerConfig });
        datepicker('#due_date', { position: 'bl', ...datepickerConfig });
        datepicker('#lr_date', { position: 'bl', ...datepickerConfig });
    }
    
    $('.select-picker').selectpicker();
    
    // Load stockists and products when CFA/Distributor is selected (for admin)
    @if(in_array('admin', user_roles()))
    $('#cfa_distributor_id').on('changed.bs.select', function() {
        var cfaDistributorId = $(this).val();
        if (cfaDistributorId) {
            loadStockistsForCFA(cfaDistributorId);
            loadProductsFromStock(cfaDistributorId);
            $('#cfa_stockist_id').prop('disabled', false);
        } else {
            refreshCfaStockistSelect('<option value="">Select CFA Stockist</option>', true);
            $('#dynamic-add-products').empty().append('<option value="">-- Select CFA/Distributor first --</option>').selectpicker('refresh');
            $('#product-selection-section').hide();
        }
        loadConsolidatedProducts();
    });
    @else
    // For CFA/Distributor users, load stockists immediately; products load via setTimeout(loadConsolidatedProducts)
    var cfaDistributorId = {{ user()->id }};
    loadStockistsForCFA(cfaDistributorId);
    @endif
    
    setTimeout(loadConsolidatedProducts, 100);
    
    // Initialize add product selectpicker
    $('#dynamic-add-products').selectpicker();
    
    // Add Product Button Click Handler - No batch selection needed
    $('#add-product-btn').on('click', function() {
        var productId = $('#dynamic-add-products').val();
        var quantity = parseFloat($('#dynamic-product-quantity').val()) || 1;
        
        if (!productId) {
            alert('Please select a product first.');
            return;
        }
        
        if (quantity < 1) {
            alert('Please enter a valid quantity.');
            return;
        }
        
        // Get CFA Distributor ID
        var cfaDistributorId = $('#cfa_distributor_id').val() || {{ in_array('admin', user_roles()) ? 'null' : user()->id }};
        if (!cfaDistributorId) {
            alert('Please select CFA/Distributor first.');
            return;
        }
        
        // Load batches and automatically select the first available batch (FIFO)
        $.easyAjax({
            url: "{{ route('cfa-stockist-invoices.get-stock-batches') }}",
            type: "GET",
            data: { 
                product_id: productId,
                cfa_distributor_id: cfaDistributorId
            },
            success: function(response) {
                if (response.status == 'success' && response.data && response.data.length > 0) {
                    // Use the first available batch (FIFO - First In First Out)
                    var firstBatch = response.data[0];
                    addProductToInvoice(productId, firstBatch.cfa_distributor_stock_id, quantity);
                    
                    // Reset form
                    $('#dynamic-add-products').val('').selectpicker('refresh');
                    $('#dynamic-product-quantity').val('1');
                } else {
                    alert('No available stock found for this product.');
                }
            },
            error: function() {
                alert('Error loading product stock. Please try again.');
            }
        });
    });

    $('#save-form').click(function() {
        calculateCFAInvoiceTotal();
        var url = "{{ route('cfa-stockist-invoices.store') }}";
        var formDataArray = $('#saveInvoiceForm').serializeArray();
        var mappedData = {};
        
        // Process form data - handle array fields properly
        formDataArray.forEach(item => {
            var fieldName = item.name;
            
            // Map cfa_distributor_id to client_id for validation (StoreInvoice requires client_id)
            // Controller will use cfa_distributor_id for invoice.client_id
            if (item.name === 'cfa_distributor_id') {
                mappedData['client_id'] = item.value;
                mappedData['cfa_distributor_id'] = item.value; // Keep original for controller use
            }
            
            // Handle array fields (item_name[], quantity[], etc.)
            // serializeArray() returns array fields with [] in the name
            if (fieldName.endsWith('[]')) {
                var baseName = fieldName.slice(0, -2);
                if (!mappedData[baseName]) {
                    mappedData[baseName] = [];
                }
                mappedData[baseName].push(item.value);
            } else {
                // For non-array fields, use the last value if duplicate names exist
                // Skip cfa_distributor_id here since we already handled it above
                if (item.name !== 'cfa_distributor_id') {
                    mappedData[fieldName] = item.value;
                }
            }
        });
        
        // Ensure required totals are set
        if (!mappedData.sub_total || mappedData.sub_total === '') {
            mappedData.sub_total = $('#sub_total_input').val() || '0.00';
        }
        if (!mappedData.total || mappedData.total === '') {
            mappedData.total = $('#total_input').val() || '0.00';
        }
        if (!mappedData.discount || mappedData.discount === '') {
            mappedData.discount = $('#discount_input').val() || '0.00';
        }
        
        // Validate that we have items before submitting
        if (!mappedData.item_name || mappedData.item_name.length === 0) {
            alert('Please add at least one item to the invoice.');
            return false;
        }
        
        // Ensure invoice_type is set for validation
        if (!mappedData.invoice_type) {
            mappedData.invoice_type = 'cfa_stockist';
        }
        
        // Debug: Log form data to console
        console.log('Submitting form data:', mappedData);
        console.log('Item names count:', mappedData.item_name ? mappedData.item_name.length : 0);
        console.log('Quantities:', mappedData.quantity);
        console.log('CFA Distributor Stock IDs:', mappedData.cfa_distributor_stock_id);
        console.log('CFA Stockist ID:', mappedData.cfa_stockist_id);
        console.log('CFA Distributor ID:', mappedData.cfa_distributor_id);
        console.log('Client ID (mapped from CFA Distributor):', mappedData.client_id);
        console.log('Invoice Type:', mappedData.invoice_type);
        
        // Validate CFA Stockist is selected
        if (!mappedData.cfa_stockist_id || mappedData.cfa_stockist_id === '') {
            alert('Please select a CFA Stockist.');
            return false;
        }
        
        // Validate CFA Distributor is selected (if admin)
        if (!mappedData.cfa_distributor_id || mappedData.cfa_distributor_id === '') {
            alert('Please select a CFA/Distributor.');
            return false;
        }
        
        $.easyAjax({
            url: url,
            container: '#saveInvoiceForm',
            type: "POST",
            disableButton: true,
            blockUI: true,
            buttonSelector: "#save-form",
            data: mappedData,
            success: function(response) {
                if (response.status == 'success') {
                    window.location.href = response.redirectUrl;
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON?.errors || {};
                    var errorMessages = [];
                    for (var field in errors) {
                        if (errors.hasOwnProperty(field)) {
                            errorMessages.push(field + ': ' + errors[field].join(', '));
                        }
                    }
                    alert('Validation errors:\n' + errorMessages.join('\n'));
                } else {
                    var errorMessage = 'Error saving invoice.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        errorMessage = 'Error: ' + xhr.responseText.substring(0, 200);
                    }
                    console.error('Invoice save error:', xhr);
                    alert(errorMessage);
                }
            }
        });
    });
});

// CALCULATION FUNCTION - Separate function for CFA invoices to avoid conflicts
// This is independent of the global calculateTotal() from custom.js
function calculateCFAInvoiceTotal() {
    var totalSubTotal = 0;
    var totalDiscount = 0;
    var totalTax = 0;
    var exchangeRate = parseFloat($('#exchange_rate').val()) || 1;
    
    $('.item-row').each(function(index) {
        var $row = $(this);
        
        // Find inputs - try multiple ways to ensure we get them
        var $qtyInput = $row.find('input[name="quantity[]"]');
        var $ptsInput = $row.find('input[name="pts[]"]');
        var $disInput = $row.find('input[name="dis[]"]');
        var $schemeInput = $row.find('input[name="scheme[]"]');
        
        var qty = parseFloat($qtyInput.val()) || 0;
        var pts = parseFloat($ptsInput.val()) || 0;
        var dis = parseFloat($disInput.val()) || 0;
        var scheme = ($schemeInput.val() || '').trim();
        
        // Sync hidden cost_per_item from PTS for backend unit_price (Unit price column hidden for CFA)
        $row.find('input.cost_per_item, input[name="cost_per_item[]"]').val(pts > 0 ? pts.toFixed(2) : '0');
        
        // SCHEME CALCULATOR: User enters PAID quantity, system calculates FREE quantity
        // Quantity field = PAID quantity (what user enters)
        // Scheme is percentage-based: "20+2" means 2 free = 10% of 20 paid
        var paidQty = qty; // User enters paid quantity
        var freeQty = 0;
        var totalQty = qty; // Total quantity for stock (paid + free)
        
        if (scheme && scheme.indexOf('+') !== -1) {
            // Scheme format: "20+2" means 20 paid + 2 free (2 is 10% of 20)
            var schemeParts = scheme.split('+');
            var schemePaid = parseFloat(schemeParts[0]) || 0;
            var schemeFree = parseFloat(schemeParts[1]) || 0;
            
            if (schemePaid > 0 && qty > 0) {
                // Calculate free percentage: (free products / paid products) × 100
                var freePercentage = (schemeFree / schemePaid) * 100;
                
                // Free quantity = paid quantity × free percentage
                // Example: If scheme is "20+2" (10% free) and user enters 10 paid:
                // Free = 10 × 10% = 1 free product
                freeQty = Math.floor((paidQty * freePercentage) / 100);
                
                // Total quantity = paid (entered) + free (calculated as percentage)
                totalQty = paidQty + freeQty;
            }
        } else if (scheme) {
            // Scheme format: "20" means 20 per scheme (no free)
            // If no "+" in scheme, treat as no free products
            freeQty = 0;
            totalQty = paidQty;
        }
        
        // Warn if PTS is missing
        if (pts === 0 && qty > 0) {
            console.warn('⚠️ PTS is zero or empty for item - Amount will be 0.00');
        }
        
        // Default quantity to 1 if not set
        if (paidQty === 0 && qty > 0) paidQty = qty;
        if (paidQty === 0) paidQty = 1;
        
        if (pts > 0 && paidQty > 0) {
            var basePrice = pts / exchangeRate;
            // Use paidQty for cost calculation (not total qty)
            var discountAmount = (dis > 0) ? (basePrice * paidQty * dis) / 100 : 0;
            var taxableValue = (paidQty * basePrice) - discountAmount;
            
            var taxAmount = 0;
            var $taxSelect = $row.find('.tax-select-hidden, select[name*="taxes"]');
            if ($taxSelect.length && taxableValue > 0) {
                $taxSelect.find('option:selected').each(function() {
                    var rate = parseFloat($(this).data('rate')) || 0;
                    if (rate > 0) {
                        taxAmount += (taxableValue * rate) / 100;
                    }
                });
            }
            
            var rowTotal = taxableValue + taxAmount;
            
            // Update amount display
            var $amountHtml = $row.find('.amount-html');
            var $amountInput = $row.find('input[name="amount[]"]');
            
            if ($amountHtml.length) {
                $amountHtml.text(rowTotal.toFixed(2));
            } else {
                console.warn('⚠️ Amount display element not found in row');
            }
            
            if ($amountInput.length) {
                $amountInput.val(rowTotal.toFixed(2));
            } else {
                console.warn('⚠️ Amount input element not found in row');
            }
            
            // Update scheme breakdown display
            var $schemeDisplay = $row.find('.scheme-breakdown-display');
            var $schemePaidText = $row.find('.scheme-paid-text');
            if ($schemeDisplay.length && $schemePaidText.length && scheme && scheme.indexOf('+') !== -1 && freeQty > 0) {
                // Show breakdown: numbers only without "paid" and "free" labels
                $schemePaidText.html(
                    '<span class="text-success font-weight-bold">' + paidQty + '</span> + ' +
                    '<span class="text-info font-weight-bold">' + freeQty + '</span> ' +
                    '<span class="text-dark" style="font-weight: 600;">(Total: ' + totalQty + ')</span>'
                );
                $schemeDisplay.show();
            } else if ($schemeDisplay.length) {
                $schemeDisplay.hide();
            }
            
            // Update stock display - use total quantity (paid + free) for stock calculation
            // Stock should decrease by total quantity, not just paid quantity
            var $stockValue = $row.find('.stock-value');
            var $stockBadge = $row.find('.stock-badge');
            if ($stockValue.length) {
                var cfaDistributorStockId = $row.find('input[name="cfa_distributor_stock_id[]"]').val();
                var availableStock = parseFloat($row.find('.available-stock-input').val()) || 0;
                
                // Calculate total quantity used for THIS SPECIFIC BATCH across ALL rows (including current row)
                var totalUsedQuantity = 0;
                $('.item-row').each(function() {
                    var $otherRow = $(this);
                    var otherPurchaseEntryId = $otherRow.find('input[name="cfa_distributor_stock_id[]"]').val();
                    
                    // Only count quantities for the SAME purchase entry (same batch)
                    if (cfaDistributorStockId && otherPurchaseEntryId == cfaDistributorStockId) {
                        // Get paid quantity from input
                        var otherPaidQty = parseFloat($otherRow.find('input[name="quantity[]"]').val()) || 0;
                        // Calculate free quantity for this row
                        var otherScheme = ($otherRow.find('input[name="scheme[]"]').val() || '').trim();
                        var otherFreeQty = 0;
                        
                        if (otherScheme && otherScheme.indexOf('+') !== -1) {
                            var otherSchemeParts = otherScheme.split('+');
                            var otherSchemePaid = parseFloat(otherSchemeParts[0]) || 0;
                            var otherSchemeFree = parseFloat(otherSchemeParts[1]) || 0;
                            if (otherSchemePaid > 0 && otherPaidQty > 0) {
                                // Calculate free percentage: (free products / paid products) × 100
                                var otherFreePercentage = (otherSchemeFree / otherSchemePaid) * 100;
                                // Free quantity = paid quantity × free percentage
                                otherFreeQty = Math.floor((otherPaidQty * otherFreePercentage) / 100);
                            }
                        }
                        
                        // Use TOTAL quantity (paid + free) for stock calculation
                        var otherTotalQty = otherPaidQty + otherFreeQty;
                        totalUsedQuantity += otherTotalQty;
                    }
                });
                
                // Calculate remaining stock (total quantity used includes paid + free)
                var remainingStock = Math.max(0, availableStock - totalUsedQuantity);
                
                // Update stock display
                $stockValue.text(remainingStock.toLocaleString());
                
                // Update badge color based on remaining stock
                $stockBadge.removeClass('badge-info badge-warning badge-danger low-stock out-of-stock');
                if (remainingStock <= 0) {
                    $stockBadge.addClass('badge-danger out-of-stock');
                } else if (remainingStock < 10) {
                    $stockBadge.addClass('badge-warning low-stock');
                } else {
                    $stockBadge.addClass('badge-info');
                }
            }
            
            totalSubTotal += taxableValue;
            totalDiscount += discountAmount;
            totalTax += taxAmount;
        } else {
            // Set to 0.00 if PTS is missing or 0
            $row.find('.amount-html').text('0.00');
            $row.find('input[name="amount[]"]').val('0.00');
        }
    });
    
    var grandTotal = totalSubTotal + totalTax;
    $('#sub_total').text(totalSubTotal.toFixed(2));
    $('#discount_amount').text(totalDiscount.toFixed(2));
    $('#tax_amount').text(totalTax.toFixed(2));
    $('#total').text(grandTotal.toFixed(2));
    $('#sub_total_input').val(totalSubTotal.toFixed(2));
    $('#discount_input').val(totalDiscount.toFixed(2));
    $('#tax_amount_input').val(totalTax.toFixed(2));
    $('#total_input').val(grandTotal.toFixed(2));
};

// Alias for easier calling - use calculateCFAInvoiceTotal() directly
// This avoids conflicts with global calculateTotal() from custom.js

// STOCK VALIDATION - Prevent quantity exceeding available stock
// Note: quantity input = PAID quantity, but stock validation uses TOTAL quantity (paid + free)
function validateStockQuantity($input) {
    var $row = $input.closest('.item-row');
    var paidQty = parseFloat($input.val()) || 0;
    var availableStock = parseFloat($input.data('available-stock')) || parseFloat($input.attr('max')) || 0;
    var cfaDistributorStockId = $input.data('cfa-distributor-stock-id') || $row.find('input[name="cfa_distributor_stock_id[]"]').val();
    
    // Get scheme to calculate free quantity (percentage-based)
    var scheme = ($row.find('input[name="scheme[]"]').val() || '').trim();
    var freeQty = 0;
    
    if (scheme && scheme.indexOf('+') !== -1) {
        var schemeParts = scheme.split('+');
        var schemePaid = parseFloat(schemeParts[0]) || 0;
        var schemeFree = parseFloat(schemeParts[1]) || 0;
        if (schemePaid > 0 && paidQty > 0) {
            // Calculate free percentage: (free products / paid products) × 100
            var freePercentage = (schemeFree / schemePaid) * 100;
            // Free quantity = paid quantity × free percentage
            freeQty = Math.floor((paidQty * freePercentage) / 100);
        }
    }
    
    var totalQty = paidQty + freeQty; // Total quantity for stock validation
    
    // Calculate total quantity used for this purchase entry across all rows (excluding current row)
    var totalUsedQuantity = 0;
    $('.item-row').each(function() {
        var $otherRow = $(this);
        var otherPurchaseEntryId = $otherRow.find('input[name="cfa_distributor_stock_id[]"]').val();
        if (cfaDistributorStockId && otherPurchaseEntryId == cfaDistributorStockId) {
            var $otherQtyInput = $otherRow.find('input[name="quantity[]"]');
            if ($otherQtyInput[0] !== $input[0]) { // Exclude current input
                var otherPaidQty = parseFloat($otherQtyInput.val()) || 0;
                // Calculate free quantity for other row
                var otherScheme = ($otherRow.find('input[name="scheme[]"]').val() || '').trim();
                var otherFreeQty = 0;
                if (otherScheme && otherScheme.indexOf('+') !== -1) {
                    var otherSchemeParts = otherScheme.split('+');
                    var otherSchemePaid = parseFloat(otherSchemeParts[0]) || 0;
                    var otherSchemeFree = parseFloat(otherSchemeParts[1]) || 0;
                    if (otherSchemePaid > 0 && otherPaidQty > 0) {
                        // Calculate free percentage: (free products / paid products) × 100
                        var otherFreePercentage = (otherSchemeFree / otherSchemePaid) * 100;
                        // Free quantity = paid quantity × free percentage
                        otherFreeQty = Math.floor((otherPaidQty * otherFreePercentage) / 100);
                    }
                }
                totalUsedQuantity += (otherPaidQty + otherFreeQty);
            }
        }
    });
    
    // Available stock = total stock - quantity used in other rows
    var remainingStock = availableStock - totalUsedQuantity;
    
    // If TOTAL quantity (paid + free) exceeds remaining stock, adjust paid quantity
    if (totalQty > remainingStock) {
        // Calculate max paid quantity that can fit in remaining stock
        var maxPaidQty = 0;
        if (scheme && scheme.indexOf('+') !== -1) {
            var schemeParts = scheme.split('+');
            var schemePaid = parseFloat(schemeParts[0]) || 0;
            var schemeFree = parseFloat(schemeParts[1]) || 0;
            var schemeTotal = schemePaid + schemeFree;
            if (schemeTotal > 0) {
                var maxSchemes = Math.floor(remainingStock / schemeTotal);
                maxPaidQty = maxSchemes * schemePaid;
            }
        } else {
            maxPaidQty = remainingStock;
        }
        
        $input.val(maxPaidQty > 0 ? maxPaidQty : 0);
        alert('Total quantity (paid + free) cannot exceed available stock. Available stock: ' + remainingStock);
        return false;
    }
    
    // Update max attribute dynamically (based on paid quantity that fits in remaining stock)
    var maxPaidQty = 0;
    if (scheme && scheme.indexOf('+') !== -1) {
        var schemeParts = scheme.split('+');
        var schemePaid = parseFloat(schemeParts[0]) || 0;
        var schemeFree = parseFloat(schemeParts[1]) || 0;
        var schemeTotal = schemePaid + schemeFree;
        if (schemeTotal > 0) {
            var maxSchemes = Math.floor(remainingStock / schemeTotal);
            maxPaidQty = maxSchemes * schemePaid;
        }
    } else {
        maxPaidQty = remainingStock;
    }
    $input.attr('max', maxPaidQty);
    
    return true;
}

// LISTENERS - Trigger calculation and stock validation on any change
$(document).on('input change', 'input[name="quantity[]"]', function() {
    validateStockQuantity($(this));
    calculateCFAInvoiceTotal();
});

$(document).on('input change', 'input[name="pts[]"], input[name="dis[]"], select[name*="taxes"], #exchange_rate', function() {
    calculateCFAInvoiceTotal();
});

// Listen for scheme changes to recalculate free quantity and update display
$(document).on('input change blur', 'input[name="scheme[]"]', function() {
    var $row = $(this).closest('.item-row');
    if ($row.length) {
        // Trigger calculation to update free quantity and scheme breakdown display
        calculateCFAInvoiceTotal();
        // Also validate stock since total quantity (paid + free) may have changed
        var $qtyInput = $row.find('input[name="quantity[]"]');
        if ($qtyInput.length) {
            validateStockQuantity($qtyInput);
        }
    }
});

// Initialize calculation on page load
$(document).ready(function() {
    calculateCFAInvoiceTotal();
});

// BATCH DROPDOWN CHANGE - Update PTS and calculate
$(document).on('change changed.bs.select', '.stock-batch-select, .purchase-batch-select', function() {
    var $select = $(this);
    var $itemRow = $select.closest('.item-row');
    var selectedOption = $select.find('option:selected');
    
    if (selectedOption.val()) {
        var pts = parseFloat(selectedOption.data('pts')) || 0;
        var dis = parseFloat(selectedOption.data('dis')) || 0;
        
        // Update PTS and DIS fields
        var $ptsInput = $itemRow.find('input[name="pts[]"]');
        var $disInput = $itemRow.find('input[name="dis[]"]');
        
        if ($ptsInput.length) {
            $ptsInput.val(pts > 0 ? pts.toFixed(2) : '');
        }
        if ($disInput.length) {
            $disInput.val(dis > 0 ? dis.toFixed(2) : '');
        }
        
        // Update other fields
        $itemRow.find('input[name="cfa_distributor_stock_id[]"]').val(selectedOption.data('cfa-distributor-stock-id') || selectedOption.data('purchase-entry-id'));
        $itemRow.find('input[name="exp[]"]').val(selectedOption.data('expiry') || '');
        $itemRow.find('input[name="mrp[]"]').val(selectedOption.data('mrp') ? parseFloat(selectedOption.data('mrp')).toFixed(2) : '');
        $itemRow.find('input[name="ptr[]"]').val(selectedOption.data('ptr') ? parseFloat(selectedOption.data('ptr')).toFixed(2) : '');
        
        // Calculate immediately after updating values
        calculateCFAInvoiceTotal();
    }
});

// ADD PRODUCT TO INVOICE
function addProductToInvoice(productId, cfaDistributorStockId, quantity) {
    // Prevent duplicate additions
    if (addedBatches[cfaDistributorStockId]) {
        console.warn('⚠️ This batch is already added. CFA Distributor Stock ID:', cfaDistributorStockId);
        alert('This batch is already added to the invoice.');
        return;
    }
    
    var currencyId = $('#currency_id').val();
    if (!currencyId) {
        alert('Please select a currency before adding items.');
        return;
    }
    
    // Get quantity from parameter or input field
    var qty = quantity || parseFloat($('#dynamic-product-quantity').val()) || 1;
    if (qty < 1) qty = 1;

    // Get invoice type (IGST or GST) from hidden input or URL parameter
    var invoiceType = $('#invoice_type_hidden').val() || '';
    if (!invoiceType) {
        // Check URL parameter
        var urlParams = new URLSearchParams(window.location.search);
        invoiceType = urlParams.get('type') || '';
    }
    
    $.easyAjax({
        url: "{{ route('invoices.add_item') }}",
        type: "GET",
        data: { 
            id: productId,
            cfa_distributor_stock_id: cfaDistributorStockId, 
            currencyId: currencyId,
            exchangeRate: $('#exchange_rate').val() || 1,
            type: invoiceType
        },
        success: function(response) {
            if (response.status == 'success') {
                var $tempDiv = $('<div>').html(response.view);
                $tempDiv.find('script').remove();
                
                var $item = $tempDiv.hide();
                $('#sortable').append($item);
                $item.fadeIn();
                
                addedBatches[cfaDistributorStockId] = true;

                // Initialize selectpicker (needs small delay for DOM)
                setTimeout(function() {
                    $item.find('.select-picker').selectpicker();
                    $item.find('.select-picker').selectpicker('refresh');
                }, 100);
                
                // DEBUG: Log PTS input value from DOM
                var $ptsInput = $item.find('input[name="pts[]"]');
                var $qtyInput = $item.find('input[name="quantity[]"]');
                
                // Set quantity from parameter or ensure it's valid
                if ($qtyInput.length) {
                    var quantityToSet = qty || 1;
                    var availableStock = parseFloat($qtyInput.data('available-stock')) || parseFloat($qtyInput.attr('max')) || 0;
                    
                    // Don't exceed available stock
                    if (quantityToSet > availableStock && availableStock > 0) {
                        quantityToSet = availableStock;
                        alert('Quantity cannot exceed available stock. Set to: ' + availableStock);
                    }
                    
                    $qtyInput.val(quantityToSet);
                    // Validate stock after setting quantity
                    validateStockQuantity($qtyInput);
                }
                
                // Calculate immediately
                calculateCFAInvoiceTotal();
                
                // Also calculate after a small delay to ensure DOM is ready
                setTimeout(function() {
                    calculateCFAInvoiceTotal();
                }, 100);
                
                // Trigger change events to ensure listeners fire
                if ($ptsInput.length) {
                    $ptsInput.trigger('change');
                }
                if ($qtyInput.length) {
                    $qtyInput.trigger('change');
                }
            }
        },
        error: function(xhr) {
            alert('Error adding item.');
        }
    });
}

function loadConsolidatedProducts() {
    var cfaDistributorId = $('#cfa_distributor_id').val() || {{ in_array('admin', user_roles()) ? 'null' : user()->id }};
    if (!cfaDistributorId) {
        $('#dynamic-add-products').empty().append('<option value="">-- Select CFA/Distributor first --</option>').selectpicker('refresh');
        return;
    }
    
    // Load products from CFA/Distributor's available stock
    loadProductsFromStock(cfaDistributorId);
}

function refreshCfaStockistSelect(html, disabled) {
    var $sel = $('#cfa_stockist_id');
    if ($sel.data('selectpicker')) {
        $sel.selectpicker('destroy');
    }
    $sel.html(html).prop('disabled', !!disabled);
    $sel.selectpicker({ liveSearch: true, size: 8 });
}

function loadStockistsForCFA(cfaDistributorId) {
    if (!cfaDistributorId) {
        refreshCfaStockistSelect('<option value="">Select CFA/Distributor first</option>', true);
        return;
    }

    $.easyAjax({
        url: "{{ route('cfa-stockists.get-stockists-for-cfa') }}",
        type: "GET",
        data: { cfa_distributor_id: cfaDistributorId },
        success: function(response) {
            if (response.status == 'success' && typeof response.data !== 'undefined') {
                refreshCfaStockistSelect(response.data, false);
            }
        },
        error: function(xhr) {
            refreshCfaStockistSelect('<option value="">Could not load stockists</option>', true);
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', text: xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Could not load CFA stockists for this distributor.' });
            }
        }
    });
}

function loadProductsFromStock(cfaDistributorId) {
    var $sel = $('#dynamic-add-products');
    if (!cfaDistributorId) {
        if ($sel.data('selectpicker')) {
            $sel.selectpicker('destroy');
        }
        $sel.empty().append('<option value="">-- Select CFA/Distributor first --</option>');
        $sel.selectpicker();
        return;
    }

    $.easyAjax({
        url: "{{ route('cfa-stockist-invoices.get-products-from-stock') }}",
        type: "GET",
        data: { cfa_distributor_id: cfaDistributorId },
        success: function(response) {
            if (response.status === 'success') {
                var rows = Array.isArray(response.data) ? response.data : [];
                if ($sel.data('selectpicker')) {
                    $sel.selectpicker('destroy');
                }
                $sel.empty().append('<option value="">-- Select Product --</option>');
                rows.forEach(function (p) {
                    if (p && p.product_id) {
                        $sel.append('<option value="' + p.product_id + '">' + (p.product_name || 'Product') + '</option>');
                    }
                });
                $sel.selectpicker({ liveSearch: true, size: 8 });
            }
        },
        error: function () {
            if ($sel.data('selectpicker')) {
                $sel.selectpicker('destroy');
            }
            $sel.empty().append('<option value="">-- Could not load products --</option>');
            $sel.selectpicker();
        }
    });
}

// Removed loadDynamicProducts and loadDynamicProductBatches - products are loaded directly from CFA/Distributor Inventory


$(document).on('click', '.remove-item', function() {
    var stockId = $(this).closest('.item-row').find('input[name="cfa_distributor_stock_id[]"]').val();
    delete addedBatches[stockId];
    $(this).closest('.item-row').remove();
    calculateCFAInvoiceTotal();
    // Batch rendering removed - no longer needed
});

window.calculateItemAmount = function($row) {
    calculateCFAInvoiceTotal();
};

window.validateStock = function($row) { 
    return true; 
};
</script>
