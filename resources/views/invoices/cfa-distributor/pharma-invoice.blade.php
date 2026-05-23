@php
    $isIgstInvoiceFormat = $isIgstInvoiceFormat ?? (($invoice->invoice_type ?? '') === 'igst');

    // Helper function to format scheme display (e.g., "30 10+1" means 30 paid with 10+1 scheme)
    if (!function_exists('formatSchemeDisplay')) {
        function formatSchemeDisplay($quantity, $scheme) {
            // Ensure quantity is numeric and not empty
            $qty = $quantity ?? 0;
            if (is_numeric($qty)) {
                $qty = (float)$qty;
            } else {
                $qty = 0;
            }
            
            // Convert to string for display
            $qtyStr = $qty > 0 ? (string)$qty : '';
            
            // Clean scheme
            $schemeStr = '';
            if ($scheme !== null && $scheme !== '') {
                $schemeStr = trim((string)$scheme);
            }
            
            // If no scheme, just return quantity
            if (empty($schemeStr)) {
                return $qtyStr;
            }
            
            // If scheme exists, show as "QTY SCHEME" format (e.g., "30 10+1")
            if (!empty($qtyStr)) {
                return $qtyStr . ' ' . $schemeStr;
            } else {
                // If quantity is empty but scheme exists, show scheme only
                return $schemeStr;
            }
        }
    }
    
    // Calculate free quantity from scheme
    if (!function_exists('calculateFreeQty')) {
        function calculateFreeQty($paidQty, $scheme) {
            if (!$scheme || strpos($scheme, '+') === false) {
                return 0;
            }
            $parts = explode('+', $scheme);
            $schemePaid = floatval($parts[0] ?? 0);
            $schemeFree = floatval($parts[1] ?? 0);
            if ($schemePaid > 0) {
                $freePercentage = ($schemeFree / $schemePaid) * 100;
                return floor(($paidQty * $freePercentage) / 100);
            }
            return 0;
        }
    }
    
    // Group taxes by rate for GST breakdown
    $gstBreakdown = [];
    $totalSubTotal = 0;
    $totalDiscount = 0;
    
    // ============================================================================
    // SINGLE SOURCE OF TRUTH: Calculate item discounts ONCE and store rounded values
    // ============================================================================
    $itemDiscounts = []; // Store rounded discount per item ID (for display - uses DIS percentage value)
    $itemDiscountsForTax = []; // Store calculated discount per item ID (for tax calculation - calculated from item amount)
    $discountFromItems = 0;
    $displayItems = $invoice->items->where('type', 'item');
    $itemsWithPurchaseEntry = $displayItems->filter(function($item) {
        return !empty($item->purchase_entry_id);
    });
    if ($itemsWithPurchaseEntry->count() > 0) {
        $displayItems = $itemsWithPurchaseEntry;
    }
    $displayItems = $displayItems->unique('id');
    
    // Calculate and store rounded discount for each item ONCE
    foreach ($displayItems as $item) {
        $itemAmount = $item->amount ?? 0;
        $itemId = $item->id;
        
        // Get DIS percentage from item (priority: item > CFADistributorStock > purchase entry)
        $disPercent = null;
        if (isset($item->dis) && $item->dis !== null) {
            $disPercent = $item->dis;
        } elseif ($invoice->cfaDistributorStocks && $invoice->cfaDistributorStocks->count() > 0) {
            $stockEntry = $invoice->cfaDistributorStocks->where('product_id', $item->product_id)->first();
            if ($stockEntry && isset($stockEntry->dis) && $stockEntry->dis !== null) {
                $disPercent = $stockEntry->dis;
            }
        }
        if ($disPercent === null && $item->purchaseEntry) {
            // Check both 'dis' and 'discount' fields (discount is the active field, dis is legacy)
            if (isset($item->purchaseEntry->discount) && $item->purchaseEntry->discount !== null) {
                $disPercent = $item->purchaseEntry->discount;
            } elseif (isset($item->purchaseEntry->dis) && $item->purchaseEntry->dis !== null) {
                $disPercent = $item->purchaseEntry->dis;
            }
        }
        
        // For DISPLAY: Use DIS percentage value directly as discount amount (visual consistency)
        // This ensures: if DIS (%) = 1.50, discount amount displayed = 1.50 everywhere
        $itemDiscountForDisplay = 0;
        if ($disPercent !== null && $disPercent > 0) {
            $itemDiscountForDisplay = round($disPercent, 2);
        }
        
        // For TAX CALCULATION: Calculate discount from item amount (for accurate tax base)
        // This ensures correct SGST/CGST calculation
        $itemDiscountForTax = 0;
        if ($disPercent !== null && $disPercent > 0 && $itemAmount > 0) {
            $itemDiscountForTax = round(($itemAmount * $disPercent) / 100, 2);
        }
        
        // Store both values
        $itemDiscounts[$itemId] = $itemDiscountForDisplay; // For item row display (percentage)
        $itemDiscountsForTax[$itemId] = $itemDiscountForTax; // For tax base calculation and summary table (calculated amount)
        $discountFromItems += $itemDiscountForTax; // Sum of calculated discount amounts (not percentages)
    }
    
    // Final discount is sum of all rounded item discounts
    $discount = round($discountFromItems, 2);
    if ($discount == 0 && $invoice->discount > 0) {
        // Fallback to invoice-level discount if no item discounts
        if ($invoice->discount_type == 'percent') {
            $discount = (($invoice->discount / 100) * $invoice->sub_total);
        } else {
            $discount = $invoice->discount;
        }
        $discount = round($discount, 2);
    }
    
    $totalQty = 0;
    $totalItems = 0;
    
    // Use the same filtered items for totals calculation (same as discount calculation above)
    // This ensures consistency between discount calculation and summary table
    $itemsForTotals = $displayItems;
    foreach ($itemsForTotals as $item) {
        $totalItems++;
        $paidQty = $item->quantity;
        $scheme = $item->scheme ?? '';
        $freeQty = calculateFreeQty($paidQty, $scheme);
        $totalQty += ($paidQty + $freeQty);
        
        $itemAmount = $item->amount;
        $totalSubTotal += $itemAmount;
        
        // Get tax rate from item taxes - identify SGST and CGST by tax name
        // Check invoice item first, then fallback to purchase entry, then product
        $taxes = [];
        
        // First check invoice item taxes
        if ($item->taxes) {
            // Handle both JSON array and string formats
            if (is_string($item->taxes)) {
                $decoded = json_decode($item->taxes, true);
                if (is_array($decoded)) {
                    $taxes = $decoded;
                } elseif (is_numeric($item->taxes)) {
                    // Single tax ID as string
                    $taxes = [(int)$item->taxes];
                }
            } elseif (is_array($item->taxes)) {
                $taxes = $item->taxes;
            }
        }
        
        // Fallback: if no taxes in invoice item, try purchase entry
        if (empty($taxes) && $item->purchaseEntry && $item->purchaseEntry->tax) {
            if (is_array($item->purchaseEntry->tax)) {
                $taxes = $item->purchaseEntry->tax;
            } elseif (is_string($item->purchaseEntry->tax)) {
                $decoded = json_decode($item->purchaseEntry->tax, true);
                if (is_array($decoded)) {
                    $taxes = $decoded;
                }
            }
        }
        
        // Fallback: if still no taxes, try product
        if (empty($taxes) && $item->product && $item->product->taxes) {
            if (is_array($item->product->taxes)) {
                $taxes = $item->product->taxes;
            } elseif (is_string($item->product->taxes)) {
                $decoded = json_decode($item->product->taxes, true);
                if (is_array($decoded)) {
                    $taxes = $decoded;
                }
            }
        }
        
        // Remove duplicates and empty values (exactly like item row calculation)
        $taxes = array_filter(array_unique($taxes));
        
        if (!empty($taxes)) {
            // Track which rates we've already processed for this item to avoid double-counting item amount
            $processedRates = [];
            
            // Get stored calculated discount amount for tax calculation (not percentage)
            $itemId = $item->id;
            $itemDiscountAmount = $itemDiscountsForTax[$itemId] ?? 0; // Use calculated amount, not percentage
            
            // Handle calculate_tax == 'after_discount' - use calculated discount amount for tax calculation
            // Calculate tax on actual item amount minus calculated discount amount (for accurate tax amounts)
            $taxBaseAmount = $itemAmount;
            if ($invoice->calculate_tax == 'after_discount' && $itemDiscountAmount > 0) {
                // Subtract the calculated discount amount from item amount (for accurate tax calculation)
                $taxBaseAmount = max(0, $itemAmount - $itemDiscountAmount);
            }
            
            // Ensure tax base is never negative (safety check)
            $taxBaseAmount = max(0, $taxBaseAmount);
            
            foreach ($taxes as $taxId) {
                if (empty($taxId)) {
                    continue;
                }
                // Use exact same lookup method as item row display
                $tax = \App\Models\Tax::find($taxId);
                if ($tax) {
                    $taxRate = $tax->rate_percent;
                    $taxNameUpper = strtoupper($tax->tax_name ?? '');
                    
                    // Determine the combined GST rate for grouping
                    if (!$isIgstInvoiceFormat && (strpos($taxNameUpper, 'SGST') !== false || strpos($taxNameUpper, 'CGST') !== false)) {
                        $rate = $taxRate * 2; // Combined GST rate
                    } else {
                        $rate = $taxRate; // IGST or other taxes use as-is
                    }
                    
                    // Initialize rate breakdown if not exists
                    if (!isset($gstBreakdown[$rate])) {
                        $gstBreakdown[$rate] = [
                            'rate' => $rate,
                            'total' => 0,
                            'discount' => 0,
                            'sgst' => 0,
                            'cgst' => 0,
                            'total_gst' => 0
                        ];
                    }
                    
                    // Add item amount and discount only once per combined rate (even if item has both SGST and CGST)
                    if (!in_array($rate, $processedRates)) {
                        $gstBreakdown[$rate]['total'] += $itemAmount;
                        
                        // Use calculated discount amount (not percentage) for summary table
                        $itemId = $item->id;
                        $itemDiscountAmount = $itemDiscountsForTax[$itemId] ?? 0; // Use calculated amount, not percentage
                        $gstBreakdown[$rate]['discount'] += $itemDiscountAmount;
                        
                        $processedRates[] = $rate;
                    }
                    
                    // Calculate tax amount for this specific tax (matches item row calculation exactly)
                    $taxAmount = ($taxBaseAmount * $taxRate) / 100;
                    
                    // Identify tax bucket by invoice type and tax name.
                    if ($isIgstInvoiceFormat) {
                        $gstBreakdown[$rate]['total_gst'] += $taxAmount;
                    } elseif (strpos($taxNameUpper, 'SGST') !== false) {
                        $gstBreakdown[$rate]['sgst'] += $taxAmount;
                    } elseif (strpos($taxNameUpper, 'CGST') !== false) {
                        $gstBreakdown[$rate]['cgst'] += $taxAmount;
                    } else {
                        // If tax name doesn't contain SGST or CGST, split equally (fallback)
                        $gstBreakdown[$rate]['sgst'] += $taxAmount / 2;
                        $gstBreakdown[$rate]['cgst'] += $taxAmount / 2;
                    }
                    if (!$isIgstInvoiceFormat) {
                        $gstBreakdown[$rate]['total_gst'] += $taxAmount;
                    }
                }
            }
        }
    }
    
    // Sort GST breakdown by rate
    ksort($gstBreakdown);
    
    $totalGST = 0;
    $totalSGST = 0;
    $totalCGST = 0;
    foreach ($gstBreakdown as $rate => $data) {
        $totalGST += $data['total_gst'];
        $totalSGST += $data['sgst'];
        $totalCGST += $data['cgst'];
    }
    
    $grandTotal = $invoice->total;
    
    // Get CFA/Distributor details (client)
    $cfaDistributor = $invoice->client;
    $cfaDistributorDetails = $invoice->client->clientDetails ?? null;
    $client = $invoice->client ?? ($user ?? null);
    $clientDetails = $client ? ($client->clientDetails ?? null) : null;
@endphp

<style>
    @page {
        size: A4 landscape;
        margin: 3mm;
    }
    * {
        box-sizing: border-box;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    body {
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;
    }
    .pharma-invoice-body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        margin: 0;
        padding: 0;
        color: #000;
        line-height: 1.3;
        width: 100%;
        max-width: 100%;
        position: relative;
        box-sizing: border-box;
    }
    .invoice-container {
        width: 100%;
        max-width: 100%;
        margin: 0;
        background: #fff;
        border: 1px solid #000;
        padding: 8px;
        position: relative;
        box-sizing: border-box;
        page-break-inside: avoid;
    }

    /* Tax invoice header (same typography as body / line items) */
    .pharma-tax-invoice-header {
        font-family: Arial, sans-serif;
        font-size: 11px;
        line-height: 1.3;
        color: #000;
        margin-bottom: 4px;
    }
    .pharma-tax-invoice-header table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }
    .pharma-tax-invoice-header td,
    .pharma-tax-invoice-header th {
        border: 1px solid #000;
        padding: 3px 4px;
        vertical-align: top;
        word-wrap: break-word;
    }
    .phi-title {
        font-family: Arial, sans-serif;
        font-size: 16px;
        font-weight: 700;
        letter-spacing: 0.02em;
        margin: 0 0 2px;
        text-transform: uppercase;
    }
    .phi-sub-legal {
        font-family: Arial, sans-serif;
        font-size: 9px;
        font-weight: 400;
        text-transform: none;
        letter-spacing: 0;
        line-height: 1.3;
    }
    .phi-label { font-weight: 700; }
    .phi-col-logo img {
        max-height: 44px;
        max-width: 110px;
        object-fit: contain;
        display: block;
        margin-bottom: 3px;
    }
    /* Triplicate + ship column (Blue Cross–style block) */
    .phi-ship-copies-cell {
        vertical-align: top;
    }
    .phi-copies {
        font-family: Arial, sans-serif;
        font-size: 8px;
        line-height: 1.25;
        text-transform: uppercase;
        border-bottom: 1px solid #000;
        padding-bottom: 6px;
        margin-bottom: 8px;
    }
    .phi-ship-block {
        margin-top: 2px;
    }
    /* QR column — centered vertically in row */
    .pharma-tax-invoice-header td.phi-td-qr {
        vertical-align: middle;
        text-align: center;
    }
    .phi-qr {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100%;
        padding: 4px 2px;
    }
    .phi-qr img {
        width: 128px;
        height: 128px;
        display: block;
        margin: 0 auto;
        object-fit: contain;
    }
    .phi-kv { margin: 0 0 2px; }
    .phi-dispatch td { font-size: 10px; }

    /* Party Details Table */
    .party-details-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 5px;
        border: 1px solid #000;
    }
    .party-details-table td {
        border: 1px solid #000;
        padding: 8px;
        font-size: 13px;
        font-weight: bold;
    }
    
    /* Items Table */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 5px;
        border: 1px solid #000;
        page-break-inside: avoid;
        font-size: 10px;
    }
    .items-table th {
        background-color: #f0f0f0;
        font-weight: bold;
        text-align: center;
        font-size: 10px;
        padding: 6px 4px;
        border: 1px solid #000;
        border-width: 1px;
    }
    .items-table td {
        text-align: center;
        font-size: 10px;
        padding: 5px 4px;
        border: 1px solid #000;
        border-width: 1px;
    }
    .items-table .text-left {
        text-align: left;
        padding-left: 6px;
    }
    .items-table .text-right {
        text-align: right;
        padding-right: 6px;
    }
    .items-table .text-center {
        text-align: center !important;
        padding: 5px 4px;
    }
    
    /* Summary Section */
    .summary-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
        font-size: 10px;
        border: 1px solid #000;
        page-break-inside: avoid;
    }
    .summary-table th {
        background-color: #f0f0f0;
        font-weight: bold;
        text-align: center;
        padding: 6px 4px;
        border: 1px solid #000;
        border-width: 1px;
        font-size: 10px;
    }
    .summary-table td {
        padding: 5px 4px;
        border: 1px solid #000;
        border-width: 1px;
        font-size: 10px;
        text-align: center;
    }
    .summary-table td.text-right {
        text-align: right;
        padding-right: 6px;
    }
    .summary-info-section {
        width: 100%;
        margin-top: 0;
        margin-bottom: 3px;
    }
    .summary-info-left {
        width: 50%;
        float: left;
        font-size: 12px;
        line-height: 1.5;
    }
    .summary-info-right {
        width: 50%;
        float: right;
        text-align: right;
        font-size: 14px;
        font-weight: bold;
        padding-top: 0;
    }
    
    .amount-in-words {
        font-weight: bold;
        font-size: 13px;
        margin-top: 5px;
        margin-bottom: 3px;
        line-height: 1.5;
    }
    .payment-message {
        font-size: 12px;
        margin-top: 3px;
        margin-bottom: 5px;
        line-height: 1.5;
    }
    
    /* Footer Section */
    .footer-section {
        margin-top: 5px;
        clear: both;
        font-size: 11px;
    }
    .footer-section table {
        border: 1px solid #000;
        width: 100%;
        border-collapse: collapse;
        page-break-inside: avoid;
    }
    .footer-section td {
        border: 1px solid #000;
        padding: 8px;
        vertical-align: top;
    }
    
    .clearfix::after {
        content: "";
        display: table;
        clear: both;
    }
    
    @media print {
        @page {
            size: A4 landscape;
            margin: 3mm;
        }
        * {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        body {
            padding: 0;
            margin: 0;
            width: 100%;
        }
        .no-print {
            display: none !important;
        }
        .pharma-invoice-body {
            padding: 0;
            margin: 0;
            width: 100%;
            max-width: 100%;
            font-size: 7px !important;
            line-height: 1.1 !important;
        }
        .invoice-container {
            max-width: 100%;
            margin: 0;
            padding: 2px !important;
            width: 100%;
            page-break-inside: avoid;
        }
        .pharma-tax-invoice-header td,
        .pharma-tax-invoice-header th {
            padding: 2px !important;
            font-size: 6px !important;
        }
        .pharma-tax-invoice-header .phi-title {
            font-size: 10px !important;
        }
        .pharma-tax-invoice-header .phi-sub-legal {
            font-size: 5px !important;
        }
        .pharma-tax-invoice-header .phi-copies {
            font-size: 5px !important;
            padding-bottom: 3px !important;
            margin-bottom: 4px !important;
        }
        .pharma-tax-invoice-header td.phi-td-qr {
            vertical-align: middle !important;
            text-align: center !important;
        }
        .pharma-tax-invoice-header .phi-qr img {
            width: 96px !important;
            height: 96px !important;
            margin-left: auto !important;
            margin-right: auto !important;
        }
        .party-details-table td {
            padding: 2px !important;
            font-size: 7px !important;
        }
        .items-table {
            font-size: 6px !important;
        }
        .items-table th {
            font-size: 6px !important;
            padding: 1px 1px !important;
        }
        .items-table td {
            padding: 1px 2px !important;
            font-size: 6px !important;
        }
        .summary-table {
            font-size: 6px !important;
        }
        .summary-table th {
            font-size: 6px !important;
            padding: 1px 1px !important;
        }
        .summary-table td {
            padding: 1px 2px !important;
            font-size: 6px !important;
        }
        .summary-info-left {
            font-size: 8px !important;
        }
        .summary-info-right {
            font-size: 9px !important;
        }
        .amount-in-words {
            font-size: 8px !important;
        }
        .payment-message {
            font-size: 7px !important;
        }
        .footer-section {
            font-size: 8px !important;
        }
        .footer-section td {
            padding: 3px !important;
            font-size: 8px !important;
        }
        /* Override ALL inline font sizes in print - comprehensive approach */
        .invoice-container table td,
        .invoice-container table th {
            font-size: 6px !important;
        }
        .invoice-container .pharma-tax-invoice-header td {
            font-size: 6px !important;
        }
        .invoice-container .items-table td,
        .invoice-container .items-table th {
            font-size: 6px !important;
            padding: 1px 2px !important;
        }
        .invoice-container .summary-table td,
        .invoice-container .summary-table th {
            font-size: 6px !important;
            padding: 1px 2px !important;
        }
        .invoice-container div {
            font-size: 7px !important;
        }
        .invoice-container strong {
            font-size: 8px !important;
        }
        .invoice-container .summary-info-right strong {
            font-size: 9px !important;
        }
        /* Force override any inline style font-size */
        .invoice-container [style*="font-size"] {
            font-size: 7px !important;
        }
        .invoice-container table [style*="font-size"] {
            font-size: 6px !important;
        }
        .invoice-container [style*="font-size: 16px"],
        .invoice-container [style*="font-size:16px"] {
            font-size: 9px !important;
        }
        .invoice-container [style*="font-size: 14px"],
        .invoice-container [style*="font-size:14px"] {
            font-size: 8px !important;
        }
        .invoice-container [style*="font-size: 13px"],
        .invoice-container [style*="font-size:13px"] {
            font-size: 7px !important;
        }
        .invoice-container [style*="font-size: 12px"],
        .invoice-container [style*="font-size:12px"] {
            font-size: 6px !important;
        }
        .pharma-tax-invoice-header table,
        .items-table,
        .summary-table {
            page-break-inside: avoid;
        }
        table {
            page-break-inside: avoid;
        }
    }
</style>

<div class="invoice-container pharma-invoice-body">
    @php
        /** Ryva Vitabiotics — fixed seller header + QR GSTIN */
        $phiSellerGst = '09AAOCR8265M1ZD';
        $phiIssueTime = $invoice->created_at
            ? $invoice->created_at->timezone(company()->timezone ?? config('app.timezone'))->format('H:i:s')
            : '';
        $phiCustGst = optional($cfaDistributorDetails)->gst_number ?? '';
        $phiCustState = strlen((string) $phiCustGst) >= 2 ? substr((string) $phiCustGst, 0, 2) : '';
        $phiBillName = optional($cfaDistributorDetails)->company_name ?? (optional($cfaDistributor)->name ?? '');
        $phiBillAddr = optional($cfaDistributorDetails)->address ?? '';
        $phiShipAddr = !empty(optional($cfaDistributorDetails)->shipping_address) ? $cfaDistributorDetails->shipping_address : $phiBillAddr;
        $phiPlaceSupply = optional($cfaDistributorDetails)->state ?? '';
        $phiQrPayload = trim(($invoice->invoice_number ?? '') . '|' . $phiSellerGst . '|' . $invoice->issue_date->format('d-m-Y'));
        $phiQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=' . rawurlencode($phiQrPayload);
        $phiOrderRef = ($invoice->relationLoaded('order') && $invoice->order) ? ($invoice->order->order_number ?? $invoice->order->id) : '';
        $phiBillEmail = trim((string) (optional($cfaDistributor)->email ?? ''));
        $phiBillPhone = '';
        if ($cfaDistributor && !empty($cfaDistributor->mobile)) {
            $phiBillPhone = trim((string) ($cfaDistributor->mobile_with_phonecode ?? $cfaDistributor->mobile));
        }
    @endphp

    <div class="pharma-tax-invoice-header">
        <table class="phi-title-row" style="margin-bottom:0;border-bottom:0;">
            <tr>
                <td colspan="4" style="text-align:center;border-bottom:1px solid #000;">
                    <div class="phi-title">{{ $isIgstInvoiceFormat ? 'IGST INVOICE' : 'TAX INVOICE' }}</div>
                    <div class="phi-sub-legal">Issued under sec. 31 of CGST act, 2017 read with rule 48 of CGST Rule, 2017</div>
                </td>
                <td style="width:11%;text-align:right;vertical-align:top;font-size:8px;border-bottom:1px solid #000;">PAGE 1 OF 1</td>
            </tr>
        </table>

        {{-- CFA Distributor: same seller block in col1 + col2; logo only in td[0]. No INVOICED AT / branch in this row. --}}
        <table>
            <tr>
                <td style="width:22%;vertical-align:top;">
                    @if (company()->light_logo_url)
                        <div class="phi-col-logo">
                            <img src="{{ company()->light_logo_url }}" alt="Ryva Vitabiotics Pvt. Ltd." />
                        </div>
                    @endif
                    @include('invoices.cfa-distributor.partials.pharma-invoice-seller-block')
                </td>
                <td style="width:20%;vertical-align:top;">
                    <div class="phi-label" style="margin-bottom:4px;">Invoice by</div>
                    @include('invoices.cfa-distributor.partials.pharma-invoice-seller-block')
                </td>
                <td style="width:25%;vertical-align:top;">
                    <div class="phi-kv"><span class="phi-label">STK CODE:</span> —</div>
                    <div class="phi-kv"><span class="phi-label">IRN NO:</span> —</div>
                    <div class="phi-kv"><span class="phi-label">DOCUMENT TYPE:</span> TAX INVOICE</div>
                    <div class="phi-kv"><span class="phi-label">TAX INVOICE NO:</span> {{ $invoice->invoice_number }}</div>
                    <div class="phi-kv"><span class="phi-label">DATE:</span> {{ $invoice->issue_date->format('d/m/Y') }}
                        @if ($phiIssueTime !== '')
                            &nbsp;<span class="phi-label">TIME:</span> {{ $phiIssueTime }}
                        @endif
                    </div>
                    <div class="phi-kv"><span class="phi-label">INVOICE TYPE:</span> B2B, REGULAR</div>
                    <div class="phi-kv"><span class="phi-label">DUE DATE:</span> {{ $invoice->due_date->format('d/m/Y') }}</div>
                    <div class="phi-kv"><span class="phi-label">STATUS:</span> {{ strtoupper($invoice->status == 'paid' ? __('app.paid') : __('app.unpaid')) }}</div>
                </td>
                <td class="phi-ship-copies-cell" style="width:21%;vertical-align:top;">
                    <div class="phi-copies">
                        ORIGINAL FOR RECIPIENT<br>
                        DUPLICATE FOR TRANSPORTER<br>
                        TRIPLICATE FOR SUPPLIER
                    </div>
                    <div class="phi-ship-block">
                        <div class="phi-label">SHIP TO:</div>
                        <div class="phi-kv">{{ $phiBillName }}</div>
                        <div class="phi-kv">{{ $phiShipAddr }}</div>
                        @if ($cfaDistributorDetails && $cfaDistributorDetails->pin_code)
                            <div class="phi-kv">PIN: {{ $cfaDistributorDetails->pin_code }}</div>
                        @endif
                        @if ($phiCustState !== '')
                            <div class="phi-kv"><span class="phi-label">STATE CODE:</span> {{ $phiCustState }}</div>
                        @endif
                    </div>
                </td>
                <td class="phi-td-qr" style="width:12%;">
                    <div class="phi-qr">
                        <img src="{{ $phiQrUrl }}" width="128" height="128" alt="QR" />
                    </div>
                </td>
            </tr>
        </table>

        {{-- Secondary row: bill to | order / customer refs (reference layout) --}}
        <table>
            <tr>
                <td style="width:50%;vertical-align:top;">
                    <div class="phi-label">BILL TO:</div>
                    <div class="phi-kv">{{ $phiBillName }}</div>
                    <div class="phi-kv">{{ $phiBillAddr }}</div>
                    @if ($phiBillEmail !== '')
                        <div class="phi-kv">E-MAIL: {{ $phiBillEmail }}</div>
                    @endif
                    @if ($phiBillPhone !== '')
                        <div class="phi-kv">PHONE: {{ $phiBillPhone }}</div>
                    @endif
                    @if ($cfaDistributorDetails && $cfaDistributorDetails->pin_code)
                        <div class="phi-kv">PIN: {{ $cfaDistributorDetails->pin_code }}</div>
                    @endif
                    @if ($phiCustGst !== '')
                        <div class="phi-kv"><span class="phi-label">GSTIN:</span> {{ $phiCustGst }}</div>
                    @endif
                    @if ($cfaDistributorDetails && !empty($cfaDistributorDetails->dl_number))
                        <div class="phi-kv"><span class="phi-label">D.L. NO.:</span> {{ $cfaDistributorDetails->dl_number }}</div>
                    @endif
                    @if ($phiCustState !== '')
                        <div class="phi-kv"><span class="phi-label">STATE CODE:</span> {{ $phiCustState }}</div>
                    @endif
                    @if ($phiPlaceSupply !== '')
                        <div class="phi-kv"><span class="phi-label">PLACE OF SUPPLY:</span> {{ $phiPlaceSupply }}</div>
                    @endif
                </td>
                <td style="width:50%;vertical-align:top;">
                    <div class="phi-kv"><span class="phi-label">CUSTOMER ORD. NO.:</span> {{ $phiOrderRef ?: '—' }}</div>
                    <div class="phi-kv"><span class="phi-label">DATED:</span> {{ $invoice->issue_date->format('d/m/Y') }}</div>
                    @if ($cfaDistributorDetails && $cfaDistributorDetails->dl_number)
                        <div class="phi-kv"><span class="phi-label">CUST D.L.NO.:</span> {{ $cfaDistributorDetails->dl_number }}</div>
                    @endif
                    @if ($phiCustGst !== '')
                        <div class="phi-kv"><span class="phi-label">CUST GST NO.:</span> {{ $phiCustGst }}</div>
                    @endif
                </td>
            </tr>
        </table>

        <table class="phi-dispatch">
            <tr>
                <td style="width:28%;"><span class="phi-label">DESP. THRU:</span> —</td>
                <td style="width:22%;"><span class="phi-label">LOCATION:</span> {{ $phiPlaceSupply ?: '—' }}</td>
                <td style="width:22%;"><span class="phi-label">LR NO. / DATE / NO OF CASES:</span>
                    {{ $invoice->lr_number ?? '—' }}
                    @if($invoice->lr_date) / {{ \Carbon\Carbon::parse($invoice->lr_date)->format('d/m/Y') }} @endif / —
                </td>
                <td style="width:14%;"><span class="phi-label">E-WAY BILL NO.:</span> —</td>
                <td style="width:14%;"><span class="phi-label">DATE:</span> {{ $invoice->issue_date->format('d/m/Y') }}</td>
            </tr>
        </table>
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th width="3%">S.</th>
                <th width="8%">QTY. SCH</th>
                <th width="6%">Pack</th>
                <th width="6%">HSN</th>
                <th width="{{ $isIgstInvoiceFormat ? '20%' : '18%' }}">Product Name</th>
                <th width="7%">Batch</th>
                <th width="5%">Exp</th>
                <th width="6%">M.R.P</th>
                <th width="6%">PTS</th>
                <th width="6%">PTR</th>
                <th width="5%">DIS (%)</th>
                @if($isIgstInvoiceFormat)
                    <th width="10%">IGST</th>
                @else
                    <th width="5%">SGST</th>
                    <th width="5%">CGST</th>
                @endif
                <th width="10%">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php
                // Display exactly what's in the database - items should already be filtered and sorted by controller
                // Just ensure we only show 'item' type and maintain the order from controller
                $displayItems = $invoice->items->where('type', 'item');
                
                // Safety check: Filter out items without purchase_entry_id for CFA/Distributor invoices
                // These are likely duplicates created by the observer before the fix
                // Only filter if there are items WITH purchase_entry_id (to avoid removing legitimate items)
                $itemsWithPurchaseEntry = $displayItems->filter(function($item) {
                    return !empty($item->purchase_entry_id);
                });
                
                if ($itemsWithPurchaseEntry->count() > 0) {
                    // If we have items with purchase_entry_id, only show those (filter out observer duplicates)
                    $displayItems = $itemsWithPurchaseEntry;
                }
                
                // Ensure unique by ID (shouldn't be needed, but safety check)
                $displayItems = $displayItems->unique('id');
                
                // Sort by field_order then id (items should already be sorted, but ensure consistency)
                $displayItems = $displayItems->sortBy(function($item) {
                    return $item->field_order ?? ($item->id ?? 999999);
                })->values();
            @endphp
            @foreach ($displayItems as $index => $item)
                @php
                    $paidQty = $item->quantity ?? 0;
                    $scheme = $item->scheme ?? '';
                    // Ensure scheme is a string
                    if ($scheme !== null && $scheme !== '') {
                        $scheme = trim((string)$scheme);
                    } else {
                        $scheme = '';
                    }
                    $freeQty = calculateFreeQty($paidQty, $scheme);
                    // QTY. SCH column should display total quantity with breakdown in brackets
                    // e.g., "240 (219+21)" - total quantity (paid+free)
                    $totalQty = $paidQty + $freeQty;
                    if ($freeQty > 0 && !empty($scheme)) {
                        $qtyDisplay = $totalQty . ' (' . $paidQty . '+' . $freeQty . ')';
                    } else {
                        $qtyDisplay = $totalQty;
                    }
                    
                    // Get batch and expiry from item, CFADistributorStock, or purchase entry
                    $purchaseEntry = $item->purchaseEntry ?? null;
                    
                    // Try to get batch from multiple sources
                    $batch = '';
                    if (!empty($item->batch)) {
                        $batch = $item->batch;
                    } elseif ($invoice->cfaDistributorStocks && $invoice->cfaDistributorStocks->count() > 0) {
                        // Try to get batch from CFADistributorStock
                        $stockEntry = $invoice->cfaDistributorStocks->where('product_id', $item->product_id)->first();
                        if ($stockEntry && !empty($stockEntry->batch)) {
                            $batch = $stockEntry->batch;
                        } elseif ($purchaseEntry && !empty($purchaseEntry->batch)) {
                            $batch = $purchaseEntry->batch;
                        }
                    } elseif ($purchaseEntry && !empty($purchaseEntry->batch)) {
                        $batch = $purchaseEntry->batch;
                    }
                    
                    // Try to get expiry from multiple sources
                    $exp = '';
                    if ($item->exp) {
                        try {
                            $exp = \Carbon\Carbon::parse($item->exp)->format('m/y');
                        } catch (\Exception $e) {
                            $exp = '';
                        }
                    } elseif ($invoice->cfaDistributorStocks && $invoice->cfaDistributorStocks->count() > 0) {
                        // Try to get expiry from CFADistributorStock
                        $stockEntry = $invoice->cfaDistributorStocks->where('product_id', $item->product_id)->first();
                        if ($stockEntry && $stockEntry->expiry) {
                            try {
                                $exp = \Carbon\Carbon::parse($stockEntry->expiry)->format('m/y');
                            } catch (\Exception $e) {
                                $exp = '';
                            }
                        } elseif ($purchaseEntry && $purchaseEntry->expiry) {
                            try {
                                $exp = \Carbon\Carbon::parse($purchaseEntry->expiry)->format('m/y');
                            } catch (\Exception $e) {
                                $exp = '';
                            }
                        }
                    } elseif ($purchaseEntry && $purchaseEntry->expiry) {
                        try {
                            $exp = \Carbon\Carbon::parse($purchaseEntry->expiry)->format('m/y');
                        } catch (\Exception $e) {
                            $exp = '';
                        }
                    }
                    
                    // Get pharma fields - try multiple sources
                    $pack = '';
                    if (!empty($item->pack)) {
                        $pack = $item->pack;
                    } elseif ($purchaseEntry && !empty($purchaseEntry->pack)) {
                        $pack = $purchaseEntry->pack;
                    } elseif ($purchaseEntry && $purchaseEntry->product && !empty($purchaseEntry->product->packing)) {
                        // Get pack from product packing field
                        $pack = $purchaseEntry->product->packing;
                    } elseif ($item->product && !empty($item->product->packing)) {
                        // Get pack from item's product packing field
                        $pack = $item->product->packing;
                    }
                    
                    $mfr = '';
                    // Always prioritize company_name over primary_name for MFR display
                    // First, try to get company_name from vendor relationships
                    if ($purchaseEntry && $purchaseEntry->vendor) {
                        // Prioritize purchase entry vendor's company_name for MFR display
                        $mfr = $purchaseEntry->vendor->company_name ?? 
                               $purchaseEntry->vendor->primary_name ?? 
                               $purchaseEntry->vendor->name ?? '';
                    } elseif ($purchaseEntry && $purchaseEntry->product && $purchaseEntry->product->vendor) {
                        // Fallback to product vendor's company_name for MFR display
                        $mfr = $purchaseEntry->product->vendor->company_name ?? 
                               $purchaseEntry->product->vendor->primary_name ?? 
                               $purchaseEntry->product->vendor->name ?? '';
                    } elseif ($item->product && $item->product->vendor) {
                        // Last fallback to item product vendor's company_name for MFR display
                        $mfr = $item->product->vendor->company_name ?? 
                               $item->product->vendor->primary_name ?? 
                               $item->product->vendor->name ?? '';
                    } elseif (!empty($item->mfr)) {
                        // Only use saved mfr if no vendor relationship found
                        // But try to find vendor by matching the saved mfr with vendor names
                        $savedMfr = $item->mfr;
                        // Try to find vendor by matching saved mfr
                        $vendor = null;
                        if ($purchaseEntry && $purchaseEntry->vendor) {
                            $vendor = $purchaseEntry->vendor;
                        } elseif ($item->product && $item->product->vendor) {
                            $vendor = $item->product->vendor;
                        }
                        // If vendor found and saved mfr matches primary_name, use company_name instead
                        if ($vendor && ($vendor->primary_name == $savedMfr || $vendor->name == $savedMfr)) {
                            $mfr = $vendor->company_name ?? $savedMfr;
                        } else {
                            $mfr = $savedMfr;
                        }
                    }
                    
                    // Get MRP, PTS, PTR, DIS from multiple sources
                    // Check if value exists in item (including 0 as valid value)
                    $mrp = (isset($item->mrp) && $item->mrp !== null) ? $item->mrp : null;
                    $pts = (isset($item->pts) && $item->pts !== null) ? $item->pts : null;
                    $ptr = (isset($item->ptr) && $item->ptr !== null) ? $item->ptr : null;
                    $dis = (isset($item->dis) && $item->dis !== null) ? $item->dis : null;
                    
                    // If not in item, try CFADistributorStock
                    if ($mrp === null && $invoice->cfaDistributorStocks && $invoice->cfaDistributorStocks->count() > 0) {
                        $stockEntry = $invoice->cfaDistributorStocks->where('product_id', $item->product_id)->first();
                        if ($stockEntry && isset($stockEntry->mrp) && $stockEntry->mrp !== null) {
                            $mrp = $stockEntry->mrp;
                        }
                    }
                    // If still null, try purchase entry
                    if ($mrp === null && $purchaseEntry && isset($purchaseEntry->mrp) && $purchaseEntry->mrp !== null) {
                        $mrp = $purchaseEntry->mrp;
                    }
                    // Default to 0 if still null
                    $mrp = $mrp ?? 0;
                    
                    if ($pts === null && $invoice->cfaDistributorStocks && $invoice->cfaDistributorStocks->count() > 0) {
                        $stockEntry = $invoice->cfaDistributorStocks->where('product_id', $item->product_id)->first();
                        if ($stockEntry && isset($stockEntry->pts) && $stockEntry->pts !== null) {
                            $pts = $stockEntry->pts;
                        }
                    }
                    if ($pts === null && $purchaseEntry && isset($purchaseEntry->pts) && $purchaseEntry->pts !== null) {
                        $pts = $purchaseEntry->pts;
                    }
                    $pts = $pts ?? 0;
                    
                    if ($ptr === null && $invoice->cfaDistributorStocks && $invoice->cfaDistributorStocks->count() > 0) {
                        $stockEntry = $invoice->cfaDistributorStocks->where('product_id', $item->product_id)->first();
                        if ($stockEntry && isset($stockEntry->ptr) && $stockEntry->ptr !== null) {
                            $ptr = $stockEntry->ptr;
                        }
                    }
                    if ($ptr === null && $purchaseEntry && isset($purchaseEntry->ptr) && $purchaseEntry->ptr !== null) {
                        $ptr = $purchaseEntry->ptr;
                    }
                    $ptr = $ptr ?? 0;
                    
                    if ($dis === null && $invoice->cfaDistributorStocks && $invoice->cfaDistributorStocks->count() > 0) {
                        $stockEntry = $invoice->cfaDistributorStocks->where('product_id', $item->product_id)->first();
                        if ($stockEntry && isset($stockEntry->dis) && $stockEntry->dis !== null) {
                            $dis = $stockEntry->dis;
                        }
                    }
                    if ($dis === null && $purchaseEntry) {
                        // Check both 'dis' and 'discount' fields (discount is the active field, dis is legacy)
                        if (isset($purchaseEntry->discount) && $purchaseEntry->discount !== null) {
                            $dis = $purchaseEntry->discount;
                        } elseif (isset($purchaseEntry->dis) && $purchaseEntry->dis !== null) {
                            $dis = $purchaseEntry->dis;
                        }
                    }
                    $dis = $dis ?? 0;
                    
                    // Get HSN code (SKU) - priority: invoice item > product SKU > product hsn_sac_code > purchase entry product > CFADistributorStock product > empty
                    $hsn = '';
                    // First check if saved on invoice item
                    if (!empty($item->hsn_sac_code)) {
                        $hsn = $item->hsn_sac_code;
                    }
                    // Then check product SKU (prioritize SKU over hsn_sac_code)
                    elseif ($item->product && !empty($item->product->sku)) {
                        $hsn = $item->product->sku;
                    }
                    // Then check product hsn_sac_code
                    elseif ($item->product && !empty($item->product->hsn_sac_code)) {
                        $hsn = $item->product->hsn_sac_code;
                    }
                    // Then check product SKU from purchase entry (prioritize SKU)
                    elseif ($purchaseEntry && $purchaseEntry->product && !empty($purchaseEntry->product->sku)) {
                        $hsn = $purchaseEntry->product->sku;
                    }
                    // Then check product hsn_sac_code from purchase entry
                    elseif ($purchaseEntry && $purchaseEntry->product && !empty($purchaseEntry->product->hsn_sac_code)) {
                        $hsn = $purchaseEntry->product->hsn_sac_code;
                    }
                    // Also check CFADistributorStock product relationship (prioritize SKU)
                    elseif ($invoice->cfaDistributorStocks && $invoice->cfaDistributorStocks->count() > 0) {
                        $stockEntry = $invoice->cfaDistributorStocks->where('product_id', $item->product_id)->first();
                        if ($stockEntry && $stockEntry->product) {
                            if (!empty($stockEntry->product->sku)) {
                                $hsn = $stockEntry->product->sku;
                            } elseif (!empty($stockEntry->product->hsn_sac_code)) {
                                $hsn = $stockEntry->product->hsn_sac_code;
                            }
                        }
                    }
                    
                    // Calculate tax amounts by invoice type.
                    $sgstAmount = 0;
                    $cgstAmount = 0;
                    $igstAmount = 0;
                    
                    // Get taxes from invoice item, or fallback to purchase entry or product
                    $taxIds = [];
                    if ($item->taxes) {
                        // Handle both JSON array and string formats
                        if (is_string($item->taxes)) {
                            $decoded = json_decode($item->taxes, true);
                            if (is_array($decoded)) {
                                $taxIds = $decoded;
                            } elseif (is_numeric($item->taxes)) {
                                // Single tax ID as string
                                $taxIds = [(int)$item->taxes];
                            }
                        } elseif (is_array($item->taxes)) {
                            $taxIds = $item->taxes;
                        }
                        // Remove duplicates and empty values
                        $taxIds = array_filter(array_unique($taxIds));
                    }
                    
                    // Fallback: if no taxes in invoice item, try purchase entry
                    if (empty($taxIds) && $purchaseEntry && $purchaseEntry->tax) {
                        if (is_array($purchaseEntry->tax)) {
                            $taxIds = $purchaseEntry->tax;
                        } elseif (is_string($purchaseEntry->tax)) {
                            $decoded = json_decode($purchaseEntry->tax, true);
                            if (is_array($decoded)) {
                                $taxIds = $decoded;
                            }
                        }
                    }
                    
                    // Fallback: if still no taxes, try product
                    if (empty($taxIds) && $item->product && $item->product->taxes) {
                        if (is_array($item->product->taxes)) {
                            $taxIds = $item->product->taxes;
                        } elseif (is_string($item->product->taxes)) {
                            $decoded = json_decode($item->product->taxes, true);
                            if (is_array($decoded)) {
                                $taxIds = $decoded;
                            }
                        }
                    }
                    
                    // Calculate tax on item amount (matches controller logic)
                    // $item->amount is the subtotal (unit_price * quantity) for this line item
                    $itemAmount = $item->amount ?? 0;
                    
                    // Get stored calculated discount amount for tax calculation (not percentage)
                    $itemId = $item->id;
                    $itemDiscountAmount = $itemDiscountsForTax[$itemId] ?? 0; // Use calculated amount, not percentage
                    
                    // Handle calculate_tax == 'after_discount' - use calculated discount amount for tax calculation
                    // Calculate tax on actual item amount minus calculated discount amount (for accurate tax amounts)
                    $taxBaseAmount = $itemAmount;
                    if ($invoice->calculate_tax == 'after_discount' && $itemDiscountAmount > 0) {
                        // Subtract the calculated discount amount from item amount (for accurate tax calculation)
                        $taxBaseAmount = max(0, $itemAmount - $itemDiscountAmount);
                    }
                    
                    // Ensure tax base is never negative (safety check)
                    $taxBaseAmount = max(0, $taxBaseAmount);
                    
                    if (!empty($taxIds)) {
                        foreach ($taxIds as $taxId) {
                            if (empty($taxId)) {
                                continue;
                            }
                            $tax = \App\Models\Tax::find($taxId);
                            if ($tax) {
                                // Calculate tax on tax base amount (fixed at 100 when discount applied for visual consistency)
                                $taxAmount = ($taxBaseAmount * $tax->rate_percent) / 100;
                                $taxNameUpper = strtoupper($tax->tax_name ?? '');
                                
                                if ($isIgstInvoiceFormat) {
                                    $igstAmount += $taxAmount;
                                } elseif (strpos($taxNameUpper, 'SGST') !== false) {
                                    $sgstAmount += $taxAmount;
                                } elseif (strpos($taxNameUpper, 'CGST') !== false) {
                                    $cgstAmount += $taxAmount;
                                } else {
                                    // If tax name doesn't contain SGST or CGST, split equally (fallback)
                                    $sgstAmount += $taxAmount / 2;
                                    $cgstAmount += $taxAmount / 2;
                                }
                            }
                        }
                    }
                @endphp
                @php
                    // Get product name - fallback to product name if item_name is empty, null, or "CC"
                    $productName = $item->item_name;
                    // Check if item_name is empty, null, or equals "CC" (case-insensitive)
                    if (empty($productName) || trim($productName) === '' || strtolower(trim($productName)) === 'cc') {
                        // Try to get product name from product relationship (priority 1)
                        if ($item->product && !empty($item->product->name)) {
                            $productName = $item->product->name;
                        } 
                        // Try to get product name from purchase entry product (priority 2)
                        elseif ($purchaseEntry && $purchaseEntry->product && !empty($purchaseEntry->product->name)) {
                            $productName = $purchaseEntry->product->name;
                        }
                        // Try to get product name from CFADistributorStock product (priority 3)
                        elseif ($invoice->cfaDistributorStocks && $invoice->cfaDistributorStocks->count() > 0) {
                            $stockEntry = $invoice->cfaDistributorStocks->where('product_id', $item->product_id)->first();
                            if ($stockEntry && $stockEntry->product && !empty($stockEntry->product->name)) {
                                $productName = $stockEntry->product->name;
                            }
                        }
                        // If still empty, use a default value
                        if (empty($productName)) {
                            $productName = 'Product #' . ($item->product_id ?? $index + 1);
                        }
                    }
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $qtyDisplay }}</td>
                    <td>{{ $pack }}</td>
                    <td>{{ $hsn }}</td>
                    <td class="text-left">{{ $productName }}</td>
                    <td>{{ $batch }}</td>
                    <td>{{ $exp }}</td>
                    <td class="text-center">{{ number_format($mrp, 2) }}</td>
                    <td class="text-center">{{ number_format($pts, 2) }}</td>
                    <td class="text-center">{{ number_format($ptr, 2) }}</td>
                    <td class="text-center">{{ number_format($dis, 2) }}</td>
                    @if($isIgstInvoiceFormat)
                        <td class="text-center">{{ number_format($igstAmount, 2) }}</td>
                    @else
                        <td class="text-center">{{ number_format($sgstAmount, 2) }}</td>
                        <td class="text-center">{{ number_format($cgstAmount, 2) }}</td>
                    @endif
                    <td class="text-center">{{ number_format($item->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Summary Section - Two Column Layout -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 5px; margin-bottom: 5px;">
        <tr>
            <!-- Left Column: GST Breakdown Table -->
            <td width="65%" style="vertical-align: top; padding-right: 5px;">
                <table class="summary-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th width="20%">CLASS</th>
                            <th width="15%">TOTAL</th>
                            <th width="12%">SCHEME</th>
                            <th width="12%">DISCOUNT</th>
                            @if($isIgstInvoiceFormat)
                                <th width="41%">IGST</th>
                            @else
                                <th width="12%">SGST</th>
                                <th width="12%">CGST</th>
                                <th width="17%">TOTAL GST</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $gstRates = [5, 12, 18, 28];
                        @endphp
                        @foreach ($gstRates as $rate)
                            @php
                                $data = $gstBreakdown[$rate] ?? [
                                    'total' => 0,
                                    'discount' => 0,
                                    'sgst' => 0,
                                    'cgst' => 0,
                                    'total_gst' => 0
                                ];
                            @endphp
                            <tr>
                                <td style="text-align: left;">GST {{ number_format($rate, 2) }}%</td>
                                <td class="text-right">{{ number_format($data['total'], 2) }}</td>
                                <td class="text-right">0.00</td>
                                <td class="text-right">{{ number_format($data['discount'] ?? 0, 2) }}</td>
                                @if($isIgstInvoiceFormat)
                                    <td class="text-right">{{ number_format($data['total_gst'], 2) }}</td>
                                @else
                                    <td class="text-right">{{ number_format($data['sgst'], 2) }}</td>
                                    <td class="text-right">{{ number_format($data['cgst'], 2) }}</td>
                                    <td class="text-right">{{ number_format($data['total_gst'], 2) }}</td>
                                @endif
                            </tr>
                        @endforeach
                        @php
                            // Use the stored discount (sum of all rounded item discounts) - SINGLE SOURCE OF TRUTH
                            // $discountFromItems is already the sum of all rounded item discounts
                            $displayDiscount = round($discountFromItems, 2);
                            
                            // Safety check: Verify discount consistency (compare with breakdown sum)
                            $calculatedTotalDiscount = 0;
                            foreach ($gstBreakdown as $rate => $data) {
                                $calculatedTotalDiscount += $data['discount'] ?? 0;
                            }
                            if (abs($discountFromItems - $calculatedTotalDiscount) > 0.01) {
                                \Log::error('DISCOUNT MISMATCH DETECTED', [
                                    'discountFromItems' => $discountFromItems,
                                    'calculatedTotalDiscount' => $calculatedTotalDiscount,
                                    'displayDiscount' => $displayDiscount,
                                    'invoice_id' => $invoice->id
                                ]);
                            }
                            
                            // If discount is 0, fallback to invoice-level discount
                            if ($displayDiscount == 0) {
                                $displayDiscount = round($discount, 2);
                            }
                        @endphp
                        <tr style="font-weight: bold;">
                            <td style="text-align: left;">TOTAL</td>
                            <td class="text-right">{{ number_format($invoice->sub_total, 2) }}</td>
                            <td class="text-right">0.00</td>
                            <td class="text-right">{{ number_format($displayDiscount, 2) }}</td>
                            @if($isIgstInvoiceFormat)
                                <td class="text-right">{{ number_format($totalGST, 2) }}</td>
                            @else
                                <td class="text-right">{{ number_format($totalSGST, 2) }}</td>
                                <td class="text-right">{{ number_format($totalCGST, 2) }}</td>
                                <td class="text-right">{{ number_format($totalGST, 2) }}</td>
                            @endif
                        </tr>
                    </tbody>
                </table>
            </td>
            
            <!-- Right Column: Summary Info -->
            <td width="35%" style="vertical-align: top; padding-left: 3px;">
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #000;">
                    <!-- TOTAL Row -->
                    <tr>
                        <td style="padding: 8px 10px; border: 1px solid #000; font-size: 14px; font-weight: bold; text-align: left;">
                            TOTAL
                        </td>
                        <td style="padding: 8px 10px; border: 1px solid #000; font-size: 14px; font-weight: bold; text-align: right;">
                            {{ number_format($invoice->sub_total, 2) }}
                        </td>
                    </tr>
                    <!-- Summary Details Row - Two Columns -->
                    <tr>
                        <!-- Left Column: Total Items, Total Qty -->
                        <td style="padding: 8px 10px; border: 1px solid #000; font-size: 12px; vertical-align: top; line-height: 1.5; width: 50%;">
                            Total Items: {{ $totalItems }}<br>
                            Total Qty: {{ $totalQty }}
                        </td>
                        <!-- Right Column: DIS AMT., tax payable, CR/DR NOTE -->
                        <td style="padding: 8px 10px; border: 1px solid #000; font-size: 12px; vertical-align: top; line-height: 1.5; width: 50%;">
                            DIS AMT.: {{ number_format($displayDiscount ?? $discount, 2) }}<br>
                            @if($isIgstInvoiceFormat)
                                IGST PAYBLE: {{ number_format($totalGST, 2) }}<br>
                            @else
                                SGST PAYBLE: {{ number_format($totalSGST, 2) }}<br>
                                CGST PAYBLE: {{ number_format($totalCGST, 2) }}<br>
                            @endif
                            CR/DR NOTE: 0.00
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    
    <!-- Amount in Words, Payment Message, and Grand Total Section - Two Columns -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 3px; margin-bottom: 3px;">
        <tr>
            <!-- Left Column: Amount in Words and Payment Message -->
            <td width="65%" style="vertical-align: top; padding-right: 3px;">
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #000;">
                    <tr>
                        <td style="padding: 8px 10px; border: 1px solid #000; font-size: 13px; line-height: 1.5;">
                            @php
                                // Simple number to words conversion
                                if (!function_exists('numberToWords')) {
                                    function numberToWords($number) {
                                        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
                                        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
                                        
                                        if ($number == 0) return 'Zero';
                                        if ($number < 20) return $ones[$number];
                                        if ($number < 100) return $tens[intval($number/10)] . ($number%10 ? ' ' . $ones[$number%10] : '');
                                        if ($number < 1000) return $ones[intval($number/100)] . ' Hundred' . ($number%100 ? ' ' . numberToWords($number%100) : '');
                                        if ($number < 100000) return numberToWords(intval($number/1000)) . ' Thousand' . ($number%1000 ? ' ' . numberToWords($number%1000) : '');
                                        if ($number < 10000000) return numberToWords(intval($number/100000)) . ' Lakh' . ($number%100000 ? ' ' . numberToWords($number%100000) : '');
                                        return numberToWords(intval($number/10000000)) . ' Crore' . ($number%10000000 ? ' ' . numberToWords($number%10000000) : '');
                                    }
                                }
                                $amountInWords = numberToWords(intval($grandTotal));
                                $paise = round(($grandTotal - intval($grandTotal)) * 100);
                                if ($paise > 0) {
                                    $amountInWords .= ' and ' . numberToWords($paise) . ' Paise';
                                }
                            @endphp
                            <div style="font-weight: bold; font-size: 13px; margin-bottom: 3px;">Rs. {{ $amountInWords }} only</div>
                            <div style="font-size: 12px;">MSG: PLEASE MAKE PAYMENT WITHIN 21 DAYS</div>
                        </td>
                    </tr>
                </table>
            </td>
            <!-- Right Column: Grand Total -->
            <td width="35%" style="vertical-align: top; padding-left: 3px;">
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #000;">
                    <tr>
                        <td style="padding: 8px 4px; border: 1px solid #000; text-align: center; vertical-align: middle; min-height: 40px;">
                            <strong style="font-size: 16px;">Grand Total: {{ number_format($grandTotal, 2) }}</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Footer Section - Three Columns -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 5px; border: 1px solid #000;">
        <tr>
            <td width="33%" style="padding: 8px 10px; border: 1px solid #000; font-size: 12px; vertical-align: top; text-align: left; line-height: 1.5;">
                <strong>Terms & Conditions</strong><br>
                All disputes subject to Lucknow Jurisdiction only.<br>
                Bills not paid due date will attract 24% interest.<br>
                Goods once sold will not be taken back or exchanged.
            </td>
            <td width="34%" style="padding: 8px 10px; border: 1px solid #000; font-size: 12px; vertical-align: top; text-align: center; line-height: 1.5;">
                <strong>Bank Details :-</strong><br>
                RYVA VITABIOTICS PVT LTD<br>
                A/C NO- 740605000525<br>
                IFSC - ICIC0007406
            </td>
            <td width="33%" style="padding: 8px 10px; border: 1px solid #000; font-size: 12px; vertical-align: top; text-align: right; line-height: 1.5;">
                <strong>FOR {{ company()->company_name }}</strong><br><br><br>
                <strong>Authorised Signatory</strong>
            </td>
        </tr>
    </table>
</div>
