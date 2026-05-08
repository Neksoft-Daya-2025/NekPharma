<!-- DESKTOP DESCRIPTION TABLE START -->
<div class="d-flex px-4 py-3 c-inv-desc item-row">
    <div class="d-flex align-items-center">
        <span class="ui-icon ui-icon-arrowthick-2-n-s mr-2"></span>
        <input type="hidden" name="sort_order[]"
                value="{{ $items->id }}">
    </div>

    <div class="c-inv-desc-table w-100 d-lg-flex d-md-flex d-block ">
        <table width="100%">
            <tbody>
                <tr class="text-dark-grey font-weight-bold f-14">
                    <td width="{{ $invoiceSetting->hsn_sac_code_show ? '20%' : '25%' }}"
                        class="border-0 inv-desc-mbl btlr">@lang('app.description')</td>
                    <td width="8%" class="border-0" align="center">Scheme</td>
                    <td width="8%" class="border-0" align="center">Pack</td>
                    <td width="8%" class="border-0" align="center">HSN</td>
                    <td width="8%" class="border-0" align="center">MFR</td>
                    <td width="8%" class="border-0" align="center">Batch</td>
                    <td width="4%" class="border-0" align="center">Exp</td>
                    <td width="8%" class="border-0" align="right">MRP</td>
                    <td width="8%" class="border-0" align="right">PTS</td>
                    <td width="8%" class="border-0" align="right">PTR</td>
                    <td width="8%" class="border-0" align="right">DIS</td>
                    @if ($invoiceSetting->hsn_sac_code_show)
                        <td width="5%" class="border-0" align="right">@lang('app.hsnSac')</td>
                    @endif
                    <td class="d-none p-0 border-0" style="width:0;line-height:0;"></td>
                    @php
                        $isIGST = (isset($invoiceType) && $invoiceType == 'igst') || request('type') == 'igst';
                    @endphp
                    @if($isIGST)
                        <td width="8%" class="border-0" align="center">IGST</td>
                    @else
                        <td width="4%" class="border-0" align="center">SGST</td>
                        <td width="4%" class="border-0" align="center">CGST</td>
                    @endif
                    <td width="10%" class="border-0" align="right">@lang('modules.invoices.amount')</td>
                    <td width="7%" class="border-0" align="right">@lang('modules.invoices.qty')</td>
                    <td width="7%" class="border-0 bblr-mbl" align="center">Stock</td>
                </tr>
                <tr>
                    <td class="border-bottom-0 btrr-mbl btlr">
                        <input type="text" class="form-control f-14 border-0 w-100 item_name" name="item_name[]"
                            placeholder="@lang('modules.expenses.itemName')" value="{{ $items->name }}" readonly>
                    </td>
                    <td class="border-bottom-0">
                        <input type="text" class="form-control f-12 w-100 text-center" name="scheme[]"
                            value="{{ $items->scheme ?? '' }}" placeholder="Scheme"
                            style="border: 1px solid #ced4da; border-radius: 4px;">
                    </td>
                    <td class="border-bottom-0">
                        <input type="text" class="form-control f-12 border-0 w-100 text-center" name="pack[]"
                            value="{{ $items->packing ?? '' }}" placeholder="Pack" readonly>
                    </td>
                    <td class="border-bottom-0">
                        <input type="text" class="form-control f-12 border-0 w-100 text-center" name="sku[]"
                            value="{{ $items->sku ?? $items->hsn_sac_code ?? '' }}" placeholder="HSN" readonly>
                    </td>
                    <td class="border-bottom-0">
                        <input type="text" class="form-control f-12 border-0 w-100 text-center" name="mfr[]"
                            value="{{ $items->vendor_name ?? '' }}" placeholder="MFR" readonly>
                    </td>
                    <td class="border-bottom-0">
                        <input type="text" class="form-control f-12 border-0 w-100 text-center" name="batch[]"
                            value="{{ $items->batch ?? '' }}" placeholder="Batch" readonly>
                        <input type="hidden" name="cfa_distributor_stock_id[]" class="cfa-distributor-stock-id-input" value="{{ $items->cfa_distributor_stock_id ?? '' }}">
                    </td>
                    <td class="border-bottom-0" style="width: 4%; min-width: 60px;">
                        <input type="month" class="form-control f-10 border-0 w-100 text-center batch-exp-input" name="exp[]"
                            value="{{ $items->expiry ? ($items->expiry instanceof \Carbon\Carbon ? $items->expiry->format('Y-m') : (is_string($items->expiry) ? substr($items->expiry, 0, 7) : '')) : '' }}" placeholder="MM/YY" readonly style="padding: 2px 2px; font-size: 10px;">
                    </td>
                    <td class="border-bottom-0">
                        <input type="number" class="form-control f-12 w-100 text-right batch-mrp-input" name="mrp[]"
                            value="{{ $items->mrp ?? '' }}" placeholder="MRP" step="0.01"
                            style="border: 1px solid #ced4da; border-radius: 4px;">
                    </td>
                    <td class="border-bottom-0">
                        <input type="number" class="form-control f-12 w-100 text-right batch-pts-input" name="pts[]"
                            value="{{ $items->pts ?? '' }}" placeholder="PTS" step="0.01"
                            style="border: 1px solid #ced4da; border-radius: 4px;">
                    </td>
                    <td class="border-bottom-0">
                        <input type="number" class="form-control f-12 w-100 text-right batch-ptr-input" name="ptr[]"
                            value="{{ $items->ptr ?? '' }}" placeholder="PTR" step="0.01"
                            style="border: 1px solid #ced4da; border-radius: 4px;">
                    </td>
                    <td class="border-bottom-0">
                        <input type="number" step="0.01" min="0" max="100" class="form-control f-12 text-right batch-dis-input item-discount-input" name="dis[]"
                            value="{{ $items->dis ?? '' }}" placeholder="DIS %" data-item-id="{{ $items->id }}" 
                            style="border: 1px solid #ced4da; border-radius: 4px; padding: 4px 8px; background-color: #fff; box-shadow: inset 0 1px 2px rgba(0,0,0,0.075);">
                    </td>
                    @if (isset($invoiceSetting) && $invoiceSetting->hsn_sac_code_show)
                        <td class="border-bottom-0">
                            <input type="text" min="1"
                                class="form-control f-14 border-0 w-100 text-right hsn_sac_code"
                                data-item-id="{{ isset($items) ? $items->id : '' }}" value="{{ $items->hsn_sac_code ?? $items->sku ?? '' }}"
                                name="hsn_sac_code[]" placeholder="HSN Code">
                        </td>
                    @endif
                    <td class="d-none p-0 border-0" style="width:0;line-height:0;">
                        <input type="hidden" class="cost_per_item" name="cost_per_item[]" value="{{ $items->pts ?? $items->price }}">
                    </td>
                    @php
                        $selectedTaxIds = [];
                        $sgstTax = null;
                        $cgstTax = null;
                        
                        // Get all available taxes - check multiple sources
                        // In Blade views, controller properties are available directly
                        $availableTaxes = isset($taxes) && !empty($taxes) ? $taxes : \App\Models\Tax::all();
                        
                        // Ensure it's iterable
                        if (!is_iterable($availableTaxes)) {
                            $availableTaxes = [];
                        }
                        
                        // Extract tax IDs from various sources
                        if (isset($invoiceItem) && $invoiceItem->taxes) {
                            // Priority 1: Invoice item taxes (for edit mode)
                            if (is_array($invoiceItem->taxes)) {
                                $selectedTaxIds = $invoiceItem->taxes;
                            } elseif (is_string($invoiceItem->taxes)) {
                                $decoded = json_decode($invoiceItem->taxes, true);
                                $selectedTaxIds = is_array($decoded) ? $decoded : [];
                            }
                        } elseif (isset($purchaseEntry) && $purchaseEntry->tax) {
                            // Priority 2: Purchase entry taxes
                            if (is_array($purchaseEntry->tax)) {
                                $selectedTaxIds = $purchaseEntry->tax;
                            } elseif (is_string($purchaseEntry->tax)) {
                                $decoded = json_decode($purchaseEntry->tax, true);
                                $selectedTaxIds = is_array($decoded) ? $decoded : [];
                            }
                        } elseif (isset($items->taxes) && !empty($items->taxes)) {
                            // Priority 3: Product taxes
                            if (is_string($items->taxes)) {
                                $decoded = json_decode($items->taxes, true);
                                $selectedTaxIds = is_array($decoded) ? $decoded : [];
                            } elseif (is_array($items->taxes)) {
                                $selectedTaxIds = $items->taxes;
                            } else {
                                $selectedTaxIds = [];
                            }
                        } else {
                            $selectedTaxIds = [];
                        }
                        
                        // Ensure selectedTaxIds is an array
                        if (!is_array($selectedTaxIds)) {
                            $selectedTaxIds = [];
                        }
                        $selectedTaxIds = array_unique($selectedTaxIds);
                        
                        // Loop through available taxes to find SGST and CGST
                        foreach ($availableTaxes as $tax) {
                            if (in_array($tax->id, $selectedTaxIds)) {
                                $taxNameUpper = strtoupper($tax->tax_name ?? '');
                                if (strpos($taxNameUpper, 'SGST') !== false) {
                                    $sgstTax = $tax;
                                } elseif (strpos($taxNameUpper, 'CGST') !== false) {
                                    $cgstTax = $tax;
                                }
                            }
                        }
                    @endphp
                    @php
                        // Check type from multiple sources: controller variable, request parameter, or hidden input
                        $isIGST = false;
                        if (isset($invoiceType) && $invoiceType == 'igst') {
                            $isIGST = true;
                        } elseif (request('type') == 'igst') {
                            $isIGST = true;
                        } elseif (isset($_GET['type']) && $_GET['type'] == 'igst') {
                            $isIGST = true;
                        }
                    @endphp
                    @if($isIGST)
                        @php
                            // Calculate IGST = SGST + CGST
                            $igstRate = 0;
                            if ($sgstTax && $cgstTax) {
                                // If both SGST and CGST exist, IGST = SGST rate + CGST rate
                                $igstRate = ($sgstTax->rate_percent ?? 0) + ($cgstTax->rate_percent ?? 0);
                            } elseif ($sgstTax) {
                                // If only SGST exists, IGST = SGST rate * 2 (assuming equal split)
                                $igstRate = ($sgstTax->rate_percent ?? 0) * 2;
                            } elseif ($cgstTax) {
                                // If only CGST exists, IGST = CGST rate * 2 (assuming equal split)
                                $igstRate = ($cgstTax->rate_percent ?? 0) * 2;
                            }
                        @endphp
                        <td class="border-bottom-0">
                            <div class="igst-display-wrapper" style="min-height: 35px; display: flex; align-items: center; justify-content: center; padding: 5px 0;">
                                @if($igstRate > 0)
                                    <div class="f-12 text-dark-grey text-center">{{ number_format($igstRate, 2) }}%</div>
                                @else
                                    <div class="f-12 text-lightest text-center">--</div>
                                @endif
                            </div>
                            <select name="taxes[0][]" multiple="multiple" class="d-none tax-select-hidden">
                                @foreach ($availableTaxes as $tax)
                                    <option data-rate="{{ $tax->rate_percent }}" data-tax-text="{{ $tax->tax_name .':'. $tax->rate_percent }}%" data-tax-name="{{ strtoupper($tax->tax_name) }}"
                                        @if (in_array($tax->id, $selectedTaxIds)) selected @endif value="{{ $tax->id }}">
                                        {{ $tax->tax_name }}: {{ $tax->rate_percent }}%</option>
                                @endforeach
                            </select>
                        </td>
                    @else
                        <td class="border-bottom-0">
                            <div class="sgst-display-wrapper" style="min-height: 35px; display: flex; align-items: center; justify-content: center; padding: 5px 0;">
                                @if($sgstTax)
                                    <div class="f-12 text-dark-grey text-center">{{ $sgstTax->rate_percent }}%</div>
                                @else
                                    <div class="f-12 text-lightest text-center">--</div>
                                @endif
                            </div>
                        </td>
                        <td class="border-bottom-0">
                            <div class="cgst-display-wrapper" style="min-height: 35px; display: flex; align-items: center; justify-content: center; padding: 5px 0;">
                                @if($cgstTax)
                                    <div class="f-12 text-dark-grey text-center">{{ $cgstTax->rate_percent }}%</div>
                                @else
                                    <div class="f-12 text-lightest text-center">--</div>
                                @endif
                            </div>
                            <select name="taxes[0][]" multiple="multiple" class="d-none tax-select-hidden">
                                @foreach ($availableTaxes as $tax)
                                    <option data-rate="{{ $tax->rate_percent }}" data-tax-text="{{ $tax->tax_name .':'. $tax->rate_percent }}%" data-tax-name="{{ strtoupper($tax->tax_name) }}"
                                        @if (in_array($tax->id, $selectedTaxIds)) selected @endif value="{{ $tax->id }}">
                                        {{ $tax->tax_name }}: {{ $tax->rate_percent }}%</option>
                                @endforeach
                            </select>
                        </td>
                    @endif
                    <td rowspan="2" align="right" valign="top" class="bg-amt-grey">
                        <span class="amount-html" data-item-id="{{ $items->id }}">{{ isset($invoiceItem) && $invoiceItem->amount ? number_format($invoiceItem->amount, 2) : '0.00' }}</span>
                        <input type="hidden" class="amount" name="amount[]" data-item-id="{{ $items->id }}"
                            value="{{ isset($invoiceItem) && $invoiceItem->amount ? $invoiceItem->amount : 0 }}">
                    </td>
                    <td class="border-bottom-0">
                        <input type="number" min="1"
                            class="form-control f-14 border-0 w-100 text-right quantity mt-3 item-quantity-input"
                            data-item-id="{{ $items->id }}" 
                            data-available-stock="{{ $items->available_stock ?? 0 }}"
                            data-cfa-distributor-stock-id="{{ $items->cfa_distributor_stock_id ?? '' }}"
                            value="{{ isset($invoiceItem) && $invoiceItem->quantity ? $invoiceItem->quantity : (isset($items->quantity) ? $items->quantity : 1) }}" 
                            name="quantity[]"
                            max="{{ $items->available_stock ?? 0 }}"
                            required>
                        <span class="text-dark-grey float-right border-0 f-12">{{ $items->unit->unit_type ?? '' }}</span>
                        <!-- Scheme breakdown display -->
                        <div class="scheme-breakdown-display mt-1" style="display: none;">
                            <small class="scheme-paid-text text-muted"></small>
                        </div>
                        <input type="hidden" name="product_id[]" value="{{ $items->id }}">
                        <input type="hidden" name="unit_id[]" value="{{ $items->unit_id }}">
                        <small class="text-danger stock-error-message" style="display: none;"></small>
                    </td>
                    <td rowspan="2" align="center" valign="top" class="btrr-bbrr">
                        @php
                            $availableStock = $items->available_stock ?? 0;
                            $stockBadgeClass = 'badge-info';
                            if ($availableStock == 0) {
                                $stockBadgeClass = 'badge-danger out-of-stock';
                            } elseif ($availableStock < 10) {
                                $stockBadgeClass = 'badge-warning low-stock';
                            }
                        @endphp
                        <div class="stock-display-container" style="display: flex; flex-direction: column; align-items: center; gap: 4px; padding-top: 8px;">
                            <span class="badge {{ $stockBadgeClass }} stock-badge" style="font-size: 11px; padding: 5px 10px; font-weight: 600; cursor: help;" title="Available stock from CFA Distributor Inventory">
                                <i class="fa fa-box"></i> <span class="stock-value">{{ number_format($availableStock, 0) }}</span>
                            </span>
                            <small class="text-muted stock-label" style="font-size: 10px; display: block;">Available</small>
                        </div>
                        <input type="hidden" class="available-stock-input" value="{{ $availableStock }}" data-cfa-distributor-stock="{{ $availableStock }}">
                    </td>
                </tr>
            </tbody>
        </table>

        <a href="javascript:;" class="d-flex align-items-center justify-content-center ml-3 remove-item"><i
                class="fa fa-times-circle f-20 text-lightest"></i></a>
    </div>

    <script>
        $(function() {
            function calculateItemAmount($row) {
                var $quantityInput = $row.find('.item-quantity-input');
                if (!$quantityInput.length) {
                    $quantityInput = $row.find('input[name="quantity[]"]');
                }
                var quantity = parseFloat($quantityInput.val()) || 0;
                
                var pts = parseFloat($row.find('input[name="pts[]"]').val()) || 0;
                var exchangeRate = parseFloat($('#exchange_rate').val()) || 1;
                
                var basePrice = pts > 0 ? (pts / exchangeRate) : 0;
                
                var disPercent = parseFloat($row.find('input[name="dis[]"]').val()) || 0;
                
                var discountAmount = 0;
                if (disPercent > 0 && basePrice > 0 && quantity > 0) {
                    discountAmount = (basePrice * quantity * disPercent) / 100;
                }
                
                var subtotal = (quantity * basePrice) - discountAmount;
                
                var totalTaxPercent = 0;
                var $taxSelect = $row.find('.tax-select-hidden, select[name="taxes[0][]"], select[name*="taxes"]');
                if ($taxSelect.length) {
                    $taxSelect.find('option:selected').each(function() {
                        var taxRate = parseFloat($(this).data('rate')) || 0;
                        totalTaxPercent += taxRate;
                    });
                }
                
                var taxAmount = 0;
                if (totalTaxPercent > 0 && subtotal > 0) {
                    taxAmount = (subtotal * totalTaxPercent) / 100;
                }
                
                var amount = subtotal + taxAmount;
                
                $row.find('.amount').val(Math.max(0, amount).toFixed(2));
                $row.find('.amount-html').html(Math.max(0, amount).toFixed(2));
                
                return amount;
            }
            
            function validateStock($row) {
                var $quantityInput = $row.find('.item-quantity-input');
                if (!$quantityInput.length) {
                    $quantityInput = $row.find('input[name="quantity[]"]');
                }
                var quantity = parseFloat($quantityInput.val()) || 0;
                
                var availableStock = parseFloat($row.find('.available-stock-input').val()) || 0;
                var $errorMsg = $row.find('.stock-error-message');
                var $stockBadge = $row.find('.stock-badge');
                var $stockValue = $row.find('.stock-value');
                
                var cfaDistributorStockId = $row.find('.cfa-distributor-stock-id-input, input[name="cfa_distributor_stock_id[]"]').val();
                
                var totalUsedQuantity = 0;
                $('.item-row').each(function() {
                    var $otherRow = $(this);
                    if ($otherRow[0] === $row[0]) {
                        return;
                    }
                    var otherStockId = $otherRow.find('.cfa-distributor-stock-id-input, input[name="cfa_distributor_stock_id[]"]').val();
                    
                    if (cfaDistributorStockId && otherStockId == cfaDistributorStockId) {
                        var otherQty = parseFloat($otherRow.find('.item-quantity-input, input[name="quantity[]"]').val()) || 0;
                        totalUsedQuantity += otherQty;
                    }
                });
                
                totalUsedQuantity += quantity;
                
                var remainingStock = Math.max(0, availableStock - totalUsedQuantity);
                
                if ($stockValue.length) {
                    $stockValue.text(remainingStock.toLocaleString());
                }
                
                $stockBadge.removeClass('badge-info badge-warning badge-danger low-stock out-of-stock');
                if (remainingStock <= 0) {
                    $stockBadge.addClass('badge-danger out-of-stock');
                } else if (remainingStock < 10) {
                    $stockBadge.addClass('badge-warning low-stock');
                } else {
                    $stockBadge.addClass('badge-info');
                }
                
                if ($errorMsg.length) {
                    $errorMsg.hide().html('');
                }
                $quantityInput.removeClass('border-danger');
                
                if (quantity > 0) {
                    if (quantity > availableStock) {
                        $quantityInput.prop('max', availableStock);
                        return false;
                    } else if (quantity > remainingStock) {
                        $quantityInput.prop('max', remainingStock);
                        return false;
                    } else {
                        $quantityInput.prop('max', remainingStock);
                        return true;
                    }
                } else {
                    $quantityInput.prop('max', remainingStock);
                    return true;
                }
            }
            
            window.calculateItemAmount = calculateItemAmount;
            window.validateStock = validateStock;

            $('.item-row').each(function() {
                var $row = $(this).closest('tr').closest('.item-row');
                calculateItemAmount($row);
                var $errorMsg = $row.find('.stock-error-message');
                if ($errorMsg.length) {
                    $errorMsg.hide();
                }
            });

            $(document).on('input change keyup', '.item-quantity-input, input[name="quantity[]"]', function() {
                var $row = $(this).closest('.item-row');
                if (!$row.length) {
                    $row = $(this).closest('tr').closest('.item-row');
                }
                
                validateStock($row);
                
                var cfaDistributorStockId = $row.find('.cfa-distributor-stock-id-input, input[name="cfa_distributor_stock_id[]"]').val();
                
                if (cfaDistributorStockId) {
                    $('.item-row').each(function() {
                        var $otherRow = $(this);
                        var otherStockId = $otherRow.find('.cfa-distributor-stock-id-input, input[name="cfa_distributor_stock_id[]"]').val();
                        if (otherStockId == cfaDistributorStockId && typeof validateStock === 'function') {
                            validateStock($otherRow);
                        }
                    });
                }
                
                calculateItemAmount($row);
                
                if (typeof calculateTotal === 'function') {
                    calculateTotal();
                }
            });
            
            $(document).on('input change', 'input[name="pts[]"]', function() {
                var $row = $(this).closest('tr').closest('.item-row');
                calculateItemAmount($row);
                if (typeof calculateTotal === 'function') {
                    calculateTotal();
                }
            });
            
            $(document).on('input change', 'input[name="dis[]"]', function() {
                var $row = $(this).closest('tr').closest('.item-row');
                calculateItemAmount($row);
                if (typeof calculateTotal === 'function') {
                    calculateTotal();
                }
            });
            
            $(document).on('change', '.tax-select-hidden', function() {
                var $row = $(this).closest('tr').closest('.item-row');
                var selectedTaxIds = $(this).val() || [];
                if (typeof updateTaxDisplay === 'function') {
                    updateTaxDisplay($row, selectedTaxIds);
                }
                if (typeof calculateItemAmount === 'function') {
                    calculateItemAmount($row);
                }
                if (typeof calculateTotal === 'function') {
                    calculateTotal();
                }
            });
            
            $(document).on('input change', '#exchange_rate', function() {
                $('.item-row').each(function() {
                    var $row = $(this);
                    calculateItemAmount($row);
                });
                if (typeof calculateTotal === 'function') {
                    calculateTotal();
                }
            });

            if (typeof calculateTotal === 'function') {
                calculateTotal();
            }
            
            setTimeout(function() {
                $('.item-row').each(function() {
                    var $row = $(this);
                    var pts = parseFloat($row.find('input[name="pts[]"]').val()) || 0;
                    if (pts > 0) {
                        calculateItemAmount($row);
                    }
                    validateStock($row);
                });
                if (typeof calculateTotal === 'function') {
                    calculateTotal();
                }
            }, 300);
        });
    </script>

</div>
<!-- DESKTOP DESCRIPTION TABLE END -->

