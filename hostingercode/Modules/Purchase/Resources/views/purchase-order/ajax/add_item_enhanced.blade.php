<!-- ENHANCED PRODUCT ENTRY ROW - UltimatePOS Style -->
@php
    $rowCount = $rowCount ?? 0;
    $rowCount++;
    $currencyPrecision = 2;
    $currencyDetails = (object)[
        'decimal_separator' => '.',
        'thousand_separator' => ','
    ];
    
    // Initialize values
    $ppWithoutDiscount = $items->purchase_price ?? $items->price ?? 0;
    $discountPercent = 0;
    $purchasePrice = $ppWithoutDiscount;
    $purchasePriceIncTax = $purchasePrice;
    $lotNumber = '';
    $mfgDate = '';
    $expiryDate = '';
    
    // Get tax ID for this product
    $lineTaxId = null;
    if (isset($items->taxes) && !empty($items->taxes)) {
        $productTaxes = json_decode($items->taxes);
        if (!empty($productTaxes) && isset($productTaxes[0])) {
            $lineTaxId = $productTaxes[0];
        }
    }
    
    // Get last purchase price if available
    $lastPurchasePrice = null;
    $lastDiscountPercent = null;
    if (isset($lastPurchaseItem)) {
        $lastPurchasePrice = $lastPurchaseItem->unit_price ?? null;
        $lastDiscountPercent = $lastPurchaseItem->discount_percent ?? null;
    }
@endphp

<tr class="purchase-entry-row" data-row-index="{{ $rowCount }}" id="row-{{ $rowCount }}">
    <!-- Serial Number -->
    <td>
        <span class="sr-number">{{ $rowCount }}</span>
    </td>
    
    <!-- Product Name -->
    <td>
        {{ $items->name }}
        @if(isset($items->sku) && $items->sku)
            <br><small class="text-muted">SKU: {{ $items->sku }}</small>
        @endif
        @if($items->track_inventory == 1)
            @php
                $currentStock = \Modules\Purchase\Entities\PurchaseStockAdjustment::where('product_id', $items->id)->sum('net_quantity') ?? 0;
            @endphp
            <br><small class="text-muted">Stock: {{ number_format($currentStock, 2) }} {{ $items->unit->unit_type }}</small>
        @endif
        <input type="hidden" name="product_id[]" value="{{ $items->id }}">
        <input type="hidden" name="item_name[]" value="{{ $items->name }}">
        <input type="hidden" name="purchases[{{ $rowCount }}][product_id]" value="{{ $items->id }}">
    </td>
    
    @if ($invoiceSetting->hsn_sac_code_show)
    <!-- HSN/SAC Code -->
    <td>
        <input type="text" 
            class="form-control input-sm hsn_sac_code"
            name="hsn_sac_code[]"
            value="{{ $items->hsn_sac_code ?? '' }}" 
            placeholder="@lang('app.hsnSac')" 
            data-row="{{ $rowCount }}">
        <input type="hidden" name="purchases[{{ $rowCount }}][hsn_sac_code]" value="{{ $items->hsn_sac_code ?? '' }}">
    </td>
    @endif
    
    <!-- Quantity -->
    <td>
        <input type="text" 
            name="quantity[]" 
            value="1"
            class="form-control input-sm purchase_quantity input_number"
            data-row="{{ $rowCount }}"
            required>
        <small class="text-muted">{{ $items->unit->unit_type }}</small>
        <input type="hidden" name="unit_id[]" value="{{ $items->unit_id }}">
        <input type="hidden" name="purchases[{{ $rowCount }}][product_id]" value="{{ $items->id }}">
        <input type="hidden" name="purchases[{{ $rowCount }}][quantity]" value="1">
        <input type="hidden" name="purchases[{{ $rowCount }}][product_unit_id]" value="{{ $items->unit_id }}">
    </td>
    
    <!-- Price Without Discount -->
    <td>
        <input type="text" 
            name="pp_without_discount[]"
            value="{{ number_format($ppWithoutDiscount, 2, '.', '') }}"
            class="form-control input-sm purchase_unit_cost_without_discount input_number"
            data-row="{{ $rowCount }}"
            required>
        <input type="hidden" name="purchases[{{ $rowCount }}][pp_without_discount]" value="{{ number_format($ppWithoutDiscount, 2, '.', '') }}">
        @if($lastPurchasePrice)
            <small class="text-muted">Prev: {{ number_format($lastPurchasePrice, 2) }}</small>
        @endif
    </td>
    
    <!-- Discount Percent -->
    <td>
        <input type="text" 
            name="discount_percent[]"
            value="{{ number_format($discountPercent, 2, '.', '') }}"
            class="form-control input-sm inline_discounts input_number"
            data-row="{{ $rowCount }}"
            required>
        <input type="hidden" name="purchases[{{ $rowCount }}][discount_percent]" value="{{ number_format($discountPercent, 2, '.', '') }}">
        @if($lastDiscountPercent)
            <small class="text-muted">Prev: {{ number_format($lastDiscountPercent, 2) }}%</small>
        @endif
    </td>
    
    <!-- Purchase Price (After Discount) -->
    <td>
        <input type="text" 
            name="purchase_price[]"
            value="{{ number_format($purchasePrice, 2, '.', '') }}"
            class="form-control input-sm purchase_unit_cost input_number"
            data-row="{{ $rowCount }}"
            readonly>
        <input type="hidden" name="cost_per_item[]" value="{{ number_format($purchasePrice, 2, '.', '') }}">
        <input type="hidden" name="purchases[{{ $rowCount }}][purchase_price]" value="{{ number_format($purchasePrice, 2, '.', '') }}">
    </td>
    
    <!-- Subtotal Before Tax -->
    <td>
        <span class="row_subtotal_before_tax display_currency" data-row="{{ $rowCount }}">0.00</span>
        <input type="hidden" class="row_subtotal_before_tax_hidden" name="subtotal_before_tax[]" value="0" data-row="{{ $rowCount }}">
    </td>
    
    <!-- Tax Selection (Multi-Select) -->
    <td>
        <div class="input-group">
            <select name="purchase_line_tax_id[{{ $rowCount }}][]" 
                multiple="multiple"
                class="form-control select-picker purchase_line_tax_id" 
                data-row="{{ $rowCount }}" 
                data-live-search="true"
                data-size="5">
                @foreach ($taxes as $tax)
                    <option value="{{ $tax->id }}" 
                        data-rate="{{ $tax->rate_percent }}"
                        {{ $lineTaxId == $tax->id ? 'selected' : '' }}>
                        {{ $tax->tax_name }}: {{ $tax->rate_percent }}%
                    </option>
                @endforeach
            </select>
            <input type="hidden" class="purchase_product_unit_tax" name="item_tax_amount[]" value="0" data-row="{{ $rowCount }}">
        </div>
        <!-- Purchase Price Including Tax (Hidden) -->
        <input type="hidden" 
            name="purchase_price_inc_tax[]"
            value="{{ number_format($purchasePriceIncTax, 2, '.', '') }}"
            class="purchase_unit_cost_after_tax"
            data-row="{{ $rowCount }}">
        <input type="hidden" name="purchases[{{ $rowCount }}][purchase_price_inc_tax]" value="{{ number_format($purchasePriceIncTax, 2, '.', '') }}">
    </td>
    
    <!-- Total After Tax (Hidden - used for calculations) -->
    <input type="hidden" class="row_subtotal_after_tax_hidden amount" name="amount[]" value="0" data-row="{{ $rowCount }}">
    <input type="hidden" class="row_subtotal_after_tax" data-row="{{ $rowCount }}" value="0">
    <input type="hidden" class="amount-html" data-item-id="{{ $items->id }}" data-row="{{ $rowCount }}" value="0">
    
    <!-- Lot Number -->
    <td>
        <input type="text" 
            class="form-control input-sm lot_number"
            name="lot_number[]"
            value="{{ $lotNumber }}" 
            placeholder="Lot/Batch" 
            data-row="{{ $rowCount }}">
        <input type="hidden" name="purchases[{{ $rowCount }}][lot_number]" value="{{ $lotNumber }}">
    </td>
    
    <!-- Expiry Month and Year -->
    <td>
        <div class="row no-gutters">
            <div class="col-6 pr-1">
                <select name="expiry_month[]" 
                    class="form-control input-sm expiry_month" 
                    data-row="{{ $rowCount }}">
                    <option value="">Month</option>
                    @for($i = 1; $i <= 12; $i++)
                        <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}" 
                            {{ $expiryDate && date('m', strtotime($expiryDate)) == str_pad($i, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                            {{ date('M', mktime(0, 0, 0, $i, 1)) }}
                        </option>
                    @endfor
                </select>
            </div>
            <div class="col-6 pl-1">
                <select name="expiry_year[]" 
                    class="form-control input-sm expiry_year" 
                    data-row="{{ $rowCount }}">
                    <option value="">Year</option>
                    @for($i = date('Y'); $i <= date('Y') + 10; $i++)
                        <option value="{{ $i }}" 
                            {{ $expiryDate && date('Y', strtotime($expiryDate)) == $i ? 'selected' : '' }}>
                            {{ $i }}
                        </option>
                    @endfor
                </select>
            </div>
        </div>
        <input type="hidden" class="mfg_date" name="mfg_date[]" value="{{ $mfgDate }}" data-row="{{ $rowCount }}">
        <input type="hidden" class="expiry_date_hidden" name="purchases[{{ $rowCount }}][exp_date]" value="{{ $expiryDate }}" data-row="{{ $rowCount }}">
        <input type="hidden" name="purchases[{{ $rowCount }}][mfg_date]" value="{{ $mfgDate }}">
    </td>
    
    <!-- Remove Button -->
    <td>
        <i class="fa fa-times remove_purchase_entry_row text-danger" title="Remove" style="cursor:pointer;" data-row="{{ $rowCount }}"></i>
    </td>
</tr>

