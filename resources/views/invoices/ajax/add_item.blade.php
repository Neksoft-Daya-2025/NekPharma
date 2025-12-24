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
                    <td width="8%" class="border-0" align="center">Exp</td>
                    <td width="8%" class="border-0" align="right">MRP</td>
                    <td width="8%" class="border-0" align="right">PTS</td>
                    <td width="8%" class="border-0" align="right">PTR</td>
                    <td width="8%" class="border-0" align="right">DIS</td>
                    @if ($invoiceSetting->hsn_sac_code_show)
                        <td width="5%" class="border-0" align="right">@lang('app.hsnSac')</td>
                    @endif
                    <td width="7%" class="border-0" align="right">@lang('modules.invoices.unitPrice')</td>
                    <td width="4%" class="border-0" align="center">SGST</td>
                    <td width="4%" class="border-0" align="center">CGST</td>
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
                        @php
                            $schemeValue = '';
                            if (isset($purchaseEntry)) {
                                // Get scheme from purchase entry - check multiple fields
                                if ($purchaseEntry->scheme_enabled && $purchaseEntry->total_quantity && $purchaseEntry->free_quantity) {
                                    $schemeValue = $purchaseEntry->total_quantity . '+' . $purchaseEntry->free_quantity;
                                } elseif (isset($purchaseEntry->scheme) && $purchaseEntry->scheme) {
                                    $schemeValue = $purchaseEntry->scheme;
                                } elseif ($items->scheme) {
                                    $schemeValue = $items->scheme;
                                }
                            } elseif ($items->scheme) {
                                $schemeValue = $items->scheme;
                            }
                        @endphp
                        <input type="text" class="form-control f-12 border-0 w-100 text-center" name="scheme[]"
                            value="{{ $schemeValue }}" placeholder="Scheme" readonly>
                    </td>
                    <td class="border-bottom-0">
                        @php
                            // Pack comes from product->packing (purchase entry doesn't have pack field)
                            // But if purchase entry exists, use product from purchase entry
                            $packValue = '';
                            if (isset($purchaseEntry) && $purchaseEntry->product) {
                                $packValue = $purchaseEntry->product->packing ?? '';
                            } elseif (isset($items->packing)) {
                                $packValue = $items->packing;
                            }
                        @endphp
                        <input type="text" class="form-control f-12 border-0 w-100 text-center" name="pack[]"
                            value="{{ $packValue }}" placeholder="Pack" readonly>
                    </td>
                    <td class="border-bottom-0">
                        @php
                            // Get SKU from product - only SKU, not hsn_sac_code
                            $skuValue = '';
                            if (isset($items) && !empty($items->sku)) {
                                $skuValue = $items->sku;
                            } elseif (isset($purchaseEntry) && isset($purchaseEntry->product) && !empty($purchaseEntry->product->sku)) {
                                $skuValue = $purchaseEntry->product->sku;
                            }
                        @endphp
                        <input type="text" class="form-control f-12 border-0 w-100 text-center" name="sku[]"
                            value="{{ $skuValue }}" placeholder="HSN" readonly>
                    </td>
                    <td class="border-bottom-0">
                        <input type="text" class="form-control f-12 border-0 w-100 text-center" name="mfr[]"
                            value="{{ $items->vendor_name ?? '' }}" placeholder="MFR" readonly>
                    </td>
                    <td class="border-bottom-0">
                        @php
                            $batchValue = '';
                            if (isset($purchaseEntry) && $purchaseEntry->batch) {
                                $batchValue = $purchaseEntry->batch;
                            }
                        @endphp
                        <input type="text" class="form-control f-12 border-0 w-100 text-center" name="batch[]"
                            value="{{ $batchValue }}" placeholder="Batch" readonly>
                        <input type="hidden" name="purchase_entry_id[]" class="purchase-entry-id-input" value="{{ isset($purchaseEntry) ? (is_object($purchaseEntry) ? $purchaseEntry->id : '') : '' }}">
                    </td>
                    <td class="border-bottom-0">
                        @php
                            $expiryValue = '';
                            if (isset($purchaseEntry) && $purchaseEntry->expiry) {
                                if (is_string($purchaseEntry->expiry)) {
                                    $expiryValue = $purchaseEntry->expiry;
                                } elseif (is_object($purchaseEntry->expiry) && method_exists($purchaseEntry->expiry, 'format')) {
                                    $expiryValue = $purchaseEntry->expiry->format('Y-m-d');
                                } elseif ($purchaseEntry->expiry instanceof \Carbon\Carbon) {
                                    $expiryValue = $purchaseEntry->expiry->format('Y-m-d');
                                }
                            }
                        @endphp
                        <input type="date" class="form-control f-12 border-0 w-100 text-center batch-exp-input" name="exp[]"
                            value="{{ $expiryValue }}" placeholder="Exp" readonly>
                    </td>
                    <td class="border-bottom-0">
                        <input type="number" class="form-control f-12 border-0 w-100 text-right batch-mrp-input" name="mrp[]"
                            value="{{ isset($purchaseEntry) ? ($purchaseEntry->mrp ?? '') : '' }}" placeholder="MRP" step="0.01" readonly>
                    </td>
                    <td class="border-bottom-0">
                        <input type="number" class="form-control f-12 border-0 w-100 text-right batch-pts-input" name="pts[]"
                            value="{{ isset($purchaseEntry) ? ($purchaseEntry->pts ?? '') : '' }}" placeholder="PTS" step="0.01" readonly>
                    </td>
                    <td class="border-bottom-0">
                        <input type="number" class="form-control f-12 border-0 w-100 text-right batch-ptr-input" name="ptr[]"
                            value="{{ isset($purchaseEntry) ? ($purchaseEntry->ptr ?? '') : '' }}" placeholder="PTR" step="0.01" readonly>
                    </td>
                    <td class="border-bottom-0">
                        @php
                            $disValue = '';
                            if (isset($purchaseEntry)) {
                                // Priority: discount field (actively used) > dis field (legacy)
                                $disValue = $purchaseEntry->discount ?? $purchaseEntry->dis ?? '';
                                // Format as percentage if value exists
                                if ($disValue !== null && $disValue !== '') {
                                    $disValue = number_format((float)$disValue, 2);
                                }
                            }
                        @endphp
                        <input type="text" class="form-control f-12 border-0 w-100 text-right batch-dis-input" name="dis[]"
                            value="{{ $disValue }}" placeholder="DIS %" readonly>
                    </td>
                    @if (isset($invoiceSetting) && $invoiceSetting->hsn_sac_code_show)
                        <td class="border-bottom-0">
                            @php
                                // Get HSN/SKU code - same logic as purchase entry form: hsn_sac_code ?? sku
                                // This should match what's displayed in the purchase entry form
                                $hsnValue = '';
                                
                                // Priority 1: Invoice item HSN (for edit mode - already saved value)
                                if (isset($invoiceItem) && !empty($invoiceItem->hsn_sac_code)) {
                                    $hsnValue = $invoiceItem->hsn_sac_code;
                                }
                                // Priority 2: From items object (product) - same as purchase entry form logic
                                elseif (isset($items)) {
                                    // Match purchase entry form: hsn_sac_code ?? sku
                                    // Check both fields explicitly
                                    if (!empty($items->hsn_sac_code)) {
                                        $hsnValue = $items->hsn_sac_code;
                                    } elseif (!empty($items->sku)) {
                                        $hsnValue = $items->sku;
                                    }
                                }
                                // Priority 3: From purchase entry product - same as purchase entry form logic
                                elseif (isset($purchaseEntry) && isset($purchaseEntry->product)) {
                                    // Match purchase entry form exactly: hsn_sac_code ?? sku
                                    if (!empty($purchaseEntry->product->hsn_sac_code)) {
                                        $hsnValue = $purchaseEntry->product->hsn_sac_code;
                                    } elseif (!empty($purchaseEntry->product->sku)) {
                                        $hsnValue = $purchaseEntry->product->sku;
                                    }
                                }
                                
                                // Debug: Log what we're getting
                                \Log::info('HSN Value in Invoice Item Row', [
                                    'has_invoiceItem' => isset($invoiceItem),
                                    'invoiceItem_hsn' => isset($invoiceItem) ? ($invoiceItem->hsn_sac_code ?? 'NULL') : 'N/A',
                                    'has_items' => isset($items),
                                    'items_id' => isset($items) ? ($items->id ?? 'NULL') : 'N/A',
                                    'items_hsn' => isset($items) ? ($items->hsn_sac_code ?? 'NULL') : 'N/A',
                                    'items_sku' => isset($items) ? ($items->sku ?? 'NULL') : 'N/A',
                                    'has_purchaseEntry' => isset($purchaseEntry),
                                    'purchaseEntry_id' => isset($purchaseEntry) ? ($purchaseEntry->id ?? 'NULL') : 'N/A',
                                    'has_product' => isset($purchaseEntry) && isset($purchaseEntry->product),
                                    'product_id' => (isset($purchaseEntry) && isset($purchaseEntry->product)) ? ($purchaseEntry->product->id ?? 'NULL') : 'N/A',
                                    'product_hsn' => (isset($purchaseEntry) && isset($purchaseEntry->product)) ? ($purchaseEntry->product->hsn_sac_code ?? 'NULL') : 'N/A',
                                    'product_sku' => (isset($purchaseEntry) && isset($purchaseEntry->product)) ? ($purchaseEntry->product->sku ?? 'NULL') : 'N/A',
                                    'final_value' => $hsnValue
                                ]);
                            @endphp
                            <input type="text" min="1"
                                class="form-control f-14 border-0 w-100 text-right hsn_sac_code"
                                data-item-id="{{ isset($items) ? $items->id : '' }}" value="{{ $hsnValue }}"
                                name="hsn_sac_code[]" placeholder="HSN Code">
                        </td>
                    @endif
                    <td class="border-bottom-0">
                        <input type="number" min="0" step="0.01"
                            class="f-14 border-0 w-100 text-right cost_per_item form-control item-price-input"
                            data-item-id="{{ $items->id }}" 
                            placeholder="{{ $items->price }}"
                            value="{{ $items->price }}" 
                            name="cost_per_item[]"
                            readonly>
                    </td>
                    @php
                        // Get taxes from invoice item (highest priority), then purchase entry, then product
                        $selectedTaxIds = [];
                        $sgstTax = null;
                        $cgstTax = null;
                        
                        // First check invoice item taxes (for edit mode)
                        if (isset($invoiceItem) && $invoiceItem->taxes) {
                            if (is_array($invoiceItem->taxes)) {
                                $selectedTaxIds = $invoiceItem->taxes;
                            } elseif (is_string($invoiceItem->taxes)) {
                                $decoded = json_decode($invoiceItem->taxes, true);
                                $selectedTaxIds = is_array($decoded) ? $decoded : [];
                            }
                        }
                        // Then check purchase entry taxes
                        elseif (isset($purchaseEntry) && $purchaseEntry->tax) {
                            if (is_array($purchaseEntry->tax)) {
                                $selectedTaxIds = $purchaseEntry->tax;
                            } elseif (is_string($purchaseEntry->tax)) {
                                $decoded = json_decode($purchaseEntry->tax, true);
                                $selectedTaxIds = is_array($decoded) ? $decoded : [];
                            }
                        }
                        // Finally check product taxes
                        elseif (isset($items->taxes)) {
                            $decoded = json_decode($items->taxes, true);
                            $selectedTaxIds = is_array($decoded) ? $decoded : [];
                        }
                        // Remove duplicates
                        $selectedTaxIds = array_unique($selectedTaxIds);
                        
                        // Get SGST and CGST separately
                        foreach ($taxes as $tax) {
                            if (in_array($tax->id, $selectedTaxIds)) {
                                $taxNameUpper = strtoupper($tax->tax_name);
                                if (strpos($taxNameUpper, 'SGST') !== false) {
                                    $sgstTax = $tax;
                                } elseif (strpos($taxNameUpper, 'CGST') !== false) {
                                    $cgstTax = $tax;
                                }
                            }
                        }
                    @endphp
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
                            @foreach ($taxes as $tax)
                                <option data-rate="{{ $tax->rate_percent }}" data-tax-text="{{ $tax->tax_name .':'. $tax->rate_percent }}%" data-tax-name="{{ strtoupper($tax->tax_name) }}"
                                    @if (in_array($tax->id, $selectedTaxIds)) selected @endif value="{{ $tax->id }}">
                                    {{ $tax->tax_name }}: {{ $tax->rate_percent }}%</option>
                            @endforeach
                        </select>
                    </td>
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
                            data-purchase-entry-id="{{ isset($purchaseEntry) ? $purchaseEntry->id : '' }}"
                            value="{{ isset($invoiceItem) && $invoiceItem->quantity ? $invoiceItem->quantity : (isset($items->quantity) ? $items->quantity : 1) }}" 
                            name="quantity[]"
                            max="{{ $items->available_stock ?? 0 }}"
                            required>
                        <span class="text-dark-grey float-right border-0 f-12">{{ $items->unit->unit_type ?? '' }}</span>
                        <div class="scheme-breakdown-display mt-2" style="display: none; padding: 4px 8px; background: #f8f9fa; border-radius: 4px; border-left: 3px solid #28a745;">
                            <span class="scheme-paid-text" style="font-size: 11px; font-weight: 500;"></span>
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
                            <span class="badge {{ $stockBadgeClass }} stock-badge" style="font-size: 11px; padding: 5px 10px; font-weight: 600; cursor: help;" title="Stock from this purchase entry">
                                <i class="fa fa-box"></i> <span class="stock-value">{{ number_format($availableStock, 0) }}</span>
                            </span>
                            <small class="text-muted stock-label" style="font-size: 10px; display: block;">Entry Stock</small>
                        </div>
                        <input type="hidden" class="available-stock-input" value="{{ $availableStock }}" data-purchase-entry-stock="{{ $availableStock }}">
                    </td>
                </tr>
                {{-- Description and Image fields removed - invoice only uses purchase entry fields --}}
            </tbody>
        </table>

        <a href="javascript:;" class="d-flex align-items-center justify-content-center ml-3 remove-item"><i
                class="fa fa-times-circle f-20 text-lightest"></i></a>
    </div>

    <script>
        $(function() {

            $(document).find('.dropify').dropify({
                messages: dropifyMessages
            });

            // Function to calculate item amount based on PTS
            // All calculations use PTS as base, taxes from purchase entry (SGST/CGST)
            function calculateItemAmount($row) {
                // Find quantity input - try multiple selectors
                var $quantityInput = $row.find('.item-quantity-input');
                if (!$quantityInput.length) {
                    $quantityInput = $row.find('input[name="quantity[]"]');
                }
                var quantity = parseFloat($quantityInput.val()) || 0;
                
                var pts = parseFloat($row.find('input[name="pts[]"]').val()) || 0;
                var exchangeRate = parseFloat($('#exchange_rate').val()) || 1;
                
                // ALWAYS use PTS as base price - convert to invoice currency if needed
                // If PTS is 0 or not available, use 0 (don't fallback to cost_per_item)
                var basePrice = pts > 0 ? (pts / exchangeRate) : 0;
                
                var disPercent = parseFloat($row.find('input[name="dis[]"]').val()) || 0;
                
                // Calculate discount amount (based on PTS)
                var discountAmount = 0;
                if (disPercent > 0 && basePrice > 0 && quantity > 0) {
                    discountAmount = (basePrice * quantity * disPercent) / 100;
                }
                
                // Calculate subtotal: (quantity × PTS) - discount
                var subtotal = (quantity * basePrice) - discountAmount;
                
                // Calculate tax based on selected taxes (SGST/CGST from purchase entry)
                var totalTaxPercent = 0;
                var $taxSelect = $row.find('.tax-select-hidden, select[name="taxes[0][]"], select[name*="taxes"]');
                if ($taxSelect.length) {
                    $taxSelect.find('option:selected').each(function() {
                        var taxRate = parseFloat($(this).data('rate')) || 0;
                        totalTaxPercent += taxRate;
                    });
                }
                
                // Calculate tax amount (based on subtotal after discount)
                var taxAmount = 0;
                if (totalTaxPercent > 0 && subtotal > 0) {
                    taxAmount = (subtotal * totalTaxPercent) / 100;
                }
                
                // Final amount: subtotal + tax
                var amount = subtotal + taxAmount;
                
                // Update amount display
                $row.find('.amount').val(Math.max(0, amount).toFixed(2));
                $row.find('.amount-html').html(Math.max(0, amount).toFixed(2));
                
                return amount;
            }
            
            // Function to validate stock - per batch validation (each batch has its own stock)
            function validateStock($row) {
                // Find quantity input - try multiple selectors
                var $quantityInput = $row.find('.item-quantity-input');
                if (!$quantityInput.length) {
                    $quantityInput = $row.find('input[name="quantity[]"]');
                }
                var paidQty = parseFloat($quantityInput.val()) || 0;
                
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
                
                var totalQty = paidQty + freeQty; // Total quantity for stock (paid + free)
                
                // Get the stock for THIS specific batch (purchase entry)
                var availableStock = parseFloat($row.find('.available-stock-input').val()) || 0;
                var $errorMsg = $row.find('.stock-error-message');
                var $stockBadge = $row.find('.stock-badge');
                var $stockValue = $row.find('.stock-value');
                
                // Get purchase entry ID for this batch
                var purchaseEntryId = $row.find('.purchase-entry-id-input, input[name="purchase_entry_id[]"]').val();
                
                // Calculate total quantity used for THIS SPECIFIC BATCH across OTHER rows (excluding current row)
                var totalUsedQuantity = 0;
                $('.item-row').each(function() {
                    var $otherRow = $(this);
                    // Skip the current row - we'll check its quantity separately
                    if ($otherRow[0] === $row[0]) {
                        return; // Skip current row
                    }
                    var otherPurchaseEntryId = $otherRow.find('.purchase-entry-id-input, input[name="purchase_entry_id[]"]').val();
                    
                    // Only count quantities for the SAME purchase entry (same batch)
                    if (purchaseEntryId && otherPurchaseEntryId == purchaseEntryId) {
                        var otherPaidQty = parseFloat($otherRow.find('.item-quantity-input, input[name="quantity[]"]').val()) || 0;
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
                        // Use TOTAL quantity (paid + free) for stock calculation
                        totalUsedQuantity += (otherPaidQty + otherFreeQty);
                    }
                });
                
                // Add current row's total quantity (paid + free) for stock calculation
                totalUsedQuantity += totalQty;
                
                // Calculate remaining stock (total quantity used includes paid + free)
                var remainingStock = Math.max(0, availableStock - totalUsedQuantity);
                
                // Update stock badge to show remaining stock for this batch
                if ($stockValue.length) {
                    $stockValue.text(remainingStock.toLocaleString());
                }
                
                // Update badge color based on remaining stock
                $stockBadge.removeClass('badge-info badge-warning badge-danger low-stock out-of-stock');
                if (remainingStock <= 0) {
                    $stockBadge.addClass('badge-danger out-of-stock');
                } else if (remainingStock < 10) {
                    $stockBadge.addClass('badge-warning low-stock');
                } else {
                    $stockBadge.addClass('badge-info');
                }
                
                // Validate current row quantity - completely hide error message
                // Error message is completely removed as per user request
                if ($errorMsg.length) {
                    $errorMsg.hide().html(''); // Clear and hide error message
                }
                $quantityInput.removeClass('border-danger');
                
                // Set max attribute to prevent exceeding stock (validation works but no error message shown)
                if (quantity > 0) {
                    if (quantity > availableStock) {
                        // Quantity is MORE than stock - set max but don't show error
                        $quantityInput.prop('max', availableStock);
                        return false;
                    } else if (quantity > remainingStock) {
                        // Quantity is MORE than remaining stock - set max but don't show error
                        $quantityInput.prop('max', remainingStock);
                        return false;
                    } else {
                        // Quantity is valid (less than or equal to stock) - no error
                        $quantityInput.prop('max', remainingStock);
                        return true;
                    }
                } else {
                    // Quantity is 0 or empty - no error
                    $quantityInput.prop('max', remainingStock);
                    return true;
                }
            }
            
            // Make functions globally accessible
            window.calculateItemAmount = calculateItemAmount;
            window.validateStock = validateStock;

            // Calculate amounts for all items on load
            $('.item-row').each(function() {
                var $row = $(this).closest('tr').closest('.item-row');
                calculateItemAmount($row);
                // Don't validate stock on load - only validate when user enters quantity
                // Hide error message by default
                var $errorMsg = $row.find('.stock-error-message');
                if ($errorMsg.length) {
                    $errorMsg.hide();
                }
            });

            // Real-time calculation on quantity change - listen for both selectors
            $(document).on('input change keyup', '.item-quantity-input, input[name="quantity[]"]', function() {
                var $row = $(this).closest('.item-row');
                if (!$row.length) {
                    $row = $(this).closest('tr').closest('.item-row');
                }
                
                var quantity = parseFloat($(this).val()) || 0;
                var $errorMsg = $row.find('.stock-error-message');
                
                // Get purchase entry ID for this batch
                var purchaseEntryId = $row.find('.purchase-entry-id-input, input[name="purchase_entry_id[]"]').val();
                
                // Always validate stock - the validateStock function will show/hide error message appropriately
                validateStock($row);
                
                // Also update other rows with the same purchase entry ID (same batch)
                if (purchaseEntryId) {
                    $('.item-row').each(function() {
                        var $otherRow = $(this);
                        var otherPurchaseEntryId = $otherRow.find('.purchase-entry-id-input, input[name="purchase_entry_id[]"]').val();
                        if (otherPurchaseEntryId == purchaseEntryId && typeof validateStock === 'function') {
                            validateStock($otherRow);
                        }
                    });
                }
                
                // Calculate amount for current row
                calculateItemAmount($row);
                
                // Trigger total calculation
                if (typeof calculateTotal === 'function') {
                    calculateTotal();
                }
            });
            
            // Real-time calculation on PTS change (if editable)
            $(document).on('input change', 'input[name="pts[]"]', function() {
                var $row = $(this).closest('tr').closest('.item-row');
                calculateItemAmount($row);
                if (typeof calculateTotal === 'function') {
                    calculateTotal();
                }
            });
            
            // Real-time calculation on DIS change
            $(document).on('input change', 'input[name="dis[]"]', function() {
                var $row = $(this).closest('tr').closest('.item-row');
                calculateItemAmount($row);
                if (typeof calculateTotal === 'function') {
                    calculateTotal();
                }
            });
            
            // Real-time calculation on tax change (taxes are in hidden select)
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
            
            // Real-time calculation on exchange rate change
            $(document).on('input change', '#exchange_rate', function() {
                $('.item-row').each(function() {
                    var $row = $(this);
                    calculateItemAmount($row);
                });
                if (typeof calculateTotal === 'function') {
                    calculateTotal();
                }
            });

            // Trigger total calculation if function exists
            if (typeof calculateTotal === 'function') {
                calculateTotal();
            }
            
            // Recalculate after a delay to ensure all fields are populated (especially PTS)
            setTimeout(function() {
                $('.item-row').each(function() {
                    var $row = $(this);
                    // Ensure PTS is available before calculating
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
            
            // Initialize selectpickers for batch dropdown only
            $('.purchase-batch-select').selectpicker();
            
            // Function to update tax display when taxes change - separate columns for SGST and CGST
            function updateTaxDisplay($row, taxIds) {
                var $sgstDisplay = $row.find('.sgst-display-wrapper');
                var $cgstDisplay = $row.find('.cgst-display-wrapper');
                var $hiddenSelect = $row.find('.tax-select-hidden');
                
                var sgstRate = null;
                var cgstRate = null;
                
                if ($hiddenSelect.length && taxIds && taxIds.length > 0) {
                    // Get SGST and CGST separately from hidden select options
                    $hiddenSelect.find('option').each(function() {
                        if (taxIds.indexOf($(this).val()) !== -1) {
                            var taxName = $(this).data('tax-name') || $(this).text().toUpperCase();
                            var taxRate = parseFloat($(this).data('rate')) || 0;
                            
                            if (taxName.indexOf('SGST') !== -1) {
                                sgstRate = taxRate;
                            } else if (taxName.indexOf('CGST') !== -1) {
                                cgstRate = taxRate;
                            }
                        }
                    });
                    
                    // Update hidden select
                    $hiddenSelect.val(taxIds);
                }
                
                // Update SGST display
                if (sgstRate !== null) {
                    $sgstDisplay.html('<div class="f-12 text-dark-grey text-center">' + sgstRate + '%</div>');
                } else {
                    $sgstDisplay.html('<div class="f-12 text-lightest text-center">--</div>');
                }
                
                // Update CGST display
                if (cgstRate !== null) {
                    $cgstDisplay.html('<div class="f-12 text-dark-grey text-center">' + cgstRate + '%</div>');
                } else {
                    $cgstDisplay.html('<div class="f-12 text-lightest text-center">--</div>');
                }
            }
            
            // Handle batch selection change (for both regular change and selectpicker change)
            $(document).on('change', '.purchase-batch-select', function() {
                loadBatchDetails($(this));
            });
            
            $(document).on('changed.bs.select', '.purchase-batch-select', function() {
                loadBatchDetails($(this));
            });
            
            function loadBatchDetails($select) {
                var $row = $select.closest('tr');
                var selectedOption = $select.find('option:selected');
                
                if (selectedOption.val()) {
                    // Update purchase_entry_id hidden input
                    var purchaseEntryId = selectedOption.data('purchase-entry-id');
                    $row.find('.purchase-entry-id-input').val(purchaseEntryId);
                    
                    // Update expiry, MRP, PTS, PTR, DIS fields
                    var expiry = selectedOption.data('expiry') || '';
                    var mrp = selectedOption.data('mrp') || '';
                    var pts = selectedOption.data('pts') || '';
                    var ptr = selectedOption.data('ptr') || '';
                    var dis = selectedOption.data('dis') || '';
                    
                    // Find the corresponding input fields in the same row
                    var $expInput = $row.find('.batch-exp-input, input[name="exp[]"]');
                    var $mrpInput = $row.find('.batch-mrp-input, input[name="mrp[]"]');
                    var $ptsInput = $row.find('.batch-pts-input, input[name="pts[]"]');
                    var $ptrInput = $row.find('.batch-ptr-input, input[name="ptr[]"]');
                    var $disInput = $row.find('.batch-dis-input, input[name="dis[]"]');
                    
                    if ($expInput.length && expiry) $expInput.val(expiry);
                    if ($mrpInput.length && mrp) $mrpInput.val(parseFloat(mrp).toFixed(2));
                    if ($ptsInput.length && pts) {
                        $ptsInput.val(parseFloat(pts).toFixed(2));
                        $ptsInput.trigger('change'); // Trigger change event to recalculate
                    }
                    if ($ptrInput.length && ptr) $ptrInput.val(parseFloat(ptr).toFixed(2));
                    // Format DIS as percentage with 2 decimal places
                    if ($disInput.length && dis) {
                        var disFormatted = parseFloat(dis).toFixed(2);
                        $disInput.val(disFormatted);
                        $disInput.trigger('change'); // Trigger change event to recalculate
                    }
                    
                    // Get item row for calculation
                    var $itemRow = $row.closest('.item-row');
                    
                    // Load taxes from purchase entry for this batch
                    if (purchaseEntryId) {
                        $.easyAjax({
                            url: "{{ route('invoices.get_batch_details') }}",
                            type: "GET",
                            data: { purchase_entry_id: purchaseEntryId },
                            success: function(response) {
                                if (response.status == 'success' && response.data) {
                                    // Update tax display with taxes from purchase entry
                                    var $hiddenSelect = $row.find('.tax-select-hidden');
                                    if ($hiddenSelect.length && response.data.tax && Array.isArray(response.data.tax)) {
                                        // Update hidden select
                                        $hiddenSelect.val(response.data.tax);
                                        // Update tax display
                                        if (typeof updateTaxDisplay === 'function') {
                                            updateTaxDisplay($row, response.data.tax);
                                        }
                                    }
                                }
                                
                                // Recalculate amount based on PTS and taxes (after fields are updated)
                                setTimeout(function() {
                                    if (typeof calculateItemAmount === 'function') {
                                        calculateItemAmount($itemRow);
                                    }
                                    if (typeof calculateTotal === 'function') {
                                        calculateTotal();
                                    }
                                }, 100);
                            }
                        });
                    }
                    
                    // Always recalculate immediately after updating PTS/DIS fields (even if no purchaseEntryId)
                    // This ensures amount is calculated as soon as batch is selected
                    setTimeout(function() {
                        if (typeof calculateItemAmount === 'function') {
                            calculateItemAmount($itemRow);
                        }
                        if (typeof calculateTotal === 'function') {
                            calculateTotal();
                        }
                    }, 50);
                    
                    // Update tax display based on selected taxes in hidden select
                    var $hiddenSelect = $row.find('.tax-select-hidden');
                    if ($hiddenSelect.length) {
                        var selectedTaxIds = $hiddenSelect.val() || [];
                        if (typeof updateTaxDisplay === 'function') {
                            updateTaxDisplay($row, selectedTaxIds);
                        }
                    }
                }
            });
        });
    </script>

</div>
<!-- DESKTOP DESCRIPTION TABLE END -->
