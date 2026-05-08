@php
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
                    
                    // For IGST invoices, use the tax rate directly (IGST is already the combined rate)
                    // For SGST/CGST in IGST invoices (shouldn't happen, but handle it), double the rate
                    if (strpos($taxNameUpper, 'SGST') !== false || strpos($taxNameUpper, 'CGST') !== false) {
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
                    
                    // Add item amount and discount only once per rate
                    if (!in_array($rate, $processedRates)) {
                        $gstBreakdown[$rate]['total'] += $itemAmount;
                        
                        // Use stored rounded discount (SINGLE SOURCE OF TRUTH) - DO NOT recompute
                        $itemId = $item->id;
                        $itemDiscountAmount = $itemDiscountsForTax[$itemId] ?? 0; // Use calculated amount, not percentage
                        $gstBreakdown[$rate]['discount'] += $itemDiscountAmount;
                        
                        $processedRates[] = $rate;
                    }
                    
                    // Calculate tax amount for this specific tax
                    $taxAmount = ($taxBaseAmount * $taxRate) / 100;
                    
                    // For IGST invoices, all tax should go to total_gst (IGST is a single tax, not split)
                    if (strpos($taxNameUpper, 'IGST') !== false) {
                        // IGST tax - add to total_gst only
                        $gstBreakdown[$rate]['total_gst'] += $taxAmount;
                    } elseif (strpos($taxNameUpper, 'SGST') !== false) {
                        // SGST tax (shouldn't happen in IGST invoices, but handle it)
                        $gstBreakdown[$rate]['sgst'] += $taxAmount;
                        $gstBreakdown[$rate]['total_gst'] += $taxAmount;
                    } elseif (strpos($taxNameUpper, 'CGST') !== false) {
                        // CGST tax (shouldn't happen in IGST invoices, but handle it)
                        $gstBreakdown[$rate]['cgst'] += $taxAmount;
                        $gstBreakdown[$rate]['total_gst'] += $taxAmount;
                    } else {
                        // If tax name doesn't contain IGST, SGST or CGST, assume it's IGST for IGST invoices
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
    
    
    /* Header Table Styles */
    .header-main-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 2px;
        border: 1px solid #000;
        page-break-inside: avoid;
    }
    .header-main-table td {
        border: 1px solid #000;
        padding: 8px;
        vertical-align: top;
        border-width: 1px;
    }
    
    /* Left Column - Company/CFA Info (Blue) */
    .header-left {
        width: 33%;
        padding: 8px;
        font-size: 11px;
        line-height: 1.4;
    }
    .header-left div {
        color: #0000FF;
        margin-bottom: 0px;
        padding: 0px 0;
    }
    .header-left .company-name {
        font-weight: bold;
        font-size: 16px;
        color: #0000FF;
        margin-bottom: 2px;
    }
    .header-left .cfa-name {
        font-weight: bold;
        font-size: 14px;
        color: #0000FF;
        margin-bottom: 2px;
    }
    
    .header-left .company-logo {
        margin-bottom: 4px;
        text-align: left;
    }
    
    .header-left .company-logo img {
        max-width: 150px;
        max-height: 60px;
        width: auto !important;
        height: auto !important;
        object-fit: contain;
        display: block;
        aspect-ratio: auto;
    }
    
    /* Prevent any external CSS from forcing square dimensions */
    .header-left .company-logo img[width],
    .header-left .company-logo img[height] {
        width: auto !important;
        height: auto !important;
    }
    
    /* Middle Column - IGST INVOICE Title */
    .header-middle {
        width: 34%;
        padding: 0;
        text-align: center;
        vertical-align: top;
        position: relative;
    }
    .header-middle > table {
        height: 100%;
        display: table;
    }
    .header-middle > table > tbody > tr:last-child {
        height: 100%;
    }
    .header-middle > table > tbody > tr:last-child > td {
        height: 100%;
        vertical-align: top;
        padding: 0;
    }
    .header-middle > table > tbody > tr:last-child > td > table {
        height: 100%;
        display: table;
        min-height: 100%;
    }
    .header-middle > table > tbody > tr:last-child > td > table > tbody > tr:last-child {
        height: 100%;
        display: table-row;
    }
    .header-middle > table > tbody > tr:last-child > td > table > tbody > tr:last-child > td {
        height: 100%;
        vertical-align: top;
        position: relative;
    }
    /* Ensure borders extend to bottom - use pseudo-element for full height borders */
    .header-middle > table > tbody > tr:last-child > td > table > tbody > tr:last-child > td[style*="border-right"] {
        border-right: 1px solid #000 !important;
        position: relative;
    }
    .header-middle > table > tbody > tr:last-child > td > table > tbody > tr:last-child > td[style*="border-right"]::after {
        content: '';
        position: absolute;
        right: 0;
        top: 0;
        bottom: -1000px;
        width: 1px;
        background: #000;
        z-index: 1;
    }
    .gst-invoice-title {
        color: #0000FF;
        font-size: 22px;
        font-weight: bold;
        margin-bottom: 1px;
        line-height: 1.0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .credit-subtitle {
        color: #0000FF;
        font-size: 14px;
        font-weight: bold;
        margin-bottom: 3px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .invoice-details-inner {
        width: 100%;
        border-collapse: collapse;
        margin-top: 5px;
    }
    .invoice-details-inner tr {
        height: 15px;
    }
    .invoice-details-inner td {
        border: none;
        padding: 4px 6px;
        font-size: 11px;
        text-align: left;
        vertical-align: middle;
    }
    .invoice-details-inner td:first-child {
        font-weight: bold;
        width: 48%;
        padding-right: 4px;
    }
    .invoice-details-inner td:last-child {
        width: 52%;
        padding-left: 2px;
    }
    
    /* Right Column - Party Details */
    .header-right {
        width: 33%;
        padding: 8px;
        font-size: 11px;
        line-height: 1.4;
    }
    .header-right div {
        margin-bottom: 0px;
        padding: 1px 0;
    }
    .header-right .party-name-label {
        font-weight: bold;
        margin-bottom: 1px;
    }
    
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
        .header-main-table td {
            padding: 2px !important;
            font-size: 7px !important;
        }
        .header-left {
            font-size: 7px !important;
            padding: 2px !important;
            line-height: 1.1 !important;
        }
        .header-left div {
            font-size: 7px !important;
        }
        .header-left .company-name {
            font-size: 10px !important;
        }
        .header-left .cfa-name {
            font-size: 9px !important;
        }
        .header-right {
            font-size: 7px !important;
            padding: 2px !important;
            line-height: 1.1 !important;
        }
        .header-right div {
            font-size: 7px !important;
        }
        .header-middle div {
            font-size: 16px !important;
        }
        .header-middle .credit-subtitle {
            font-size: 10px !important;
        }
        .invoice-details-inner td {
            padding: 2px 3px !important;
            font-size: 7px !important;
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
        .invoice-container .header-main-table td {
            font-size: 7px !important;
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
        .invoice-container .header-middle > table > tbody > tr:first-child > td > div:first-child {
            font-size: 16px !important;
        }
        .invoice-container .header-middle > table > tbody > tr:first-child > td > div:last-child {
            font-size: 10px !important;
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
        .invoice-container .header-middle [style*="font-size: 28px"],
        .invoice-container .header-middle [style*="font-size:28px"] {
            font-size: 16px !important;
        }
        .invoice-container .header-middle [style*="font-size: 18px"],
        .invoice-container .header-middle [style*="font-size:18px"] {
            font-size: 10px !important;
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
        .header-main-table,
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
    <!-- Header Section - Three Columns -->
    <table class="header-main-table">
        <tr>
            <!-- LEFT COLUMN: Company Details (Blue) -->
            <td class="header-left">
                @if(company()->light_logo_url)
                    <div class="company-logo">
                        <img src="{{ company()->light_logo_url }}" alt="{{ company()->company_name }} Logo" />
                    </div>
                @endif
                <div class="company-name">{{ company()->company_name }}</div>
                @php
                    $companyAddress = company()->defaultAddress;
                    $invoiceSetting = invoice_setting();
                @endphp
                @if ($companyAddress && $companyAddress->address)
                    <div>{{ $companyAddress->address }}</div>
                @elseif (company()->address)
                    <div>{{ company()->address }}</div>
                @endif
                @if (company()->company_email)
                    <div>E-Mail: {{ company()->company_email }}</div>
                @endif
                @if (company()->company_phone)
                    <div>Phone: {{ company()->company_phone }}</div>
                @endif
                <div>GSTIN: 09AAOCR8265M1ZD</div>
                <div>DL NUM- WLF20B2025UP009468</div>
                <div>DL NUM-WLF21B2025UP009439</div>
                @if ($companyAddress && $companyAddress->tax_number)
                    <div>D.L.No.: {{ $companyAddress->tax_number }}</div>
                @endif
                @if ($companyAddress && $companyAddress->tax_name && $companyAddress->tax_number)
                    <div>{{ $companyAddress->tax_name }}: {{ $companyAddress->tax_number }}</div>
                @elseif ($invoiceSetting && $invoiceSetting->tax_name && $invoiceSetting->gst_number)
                    <div>{{ $invoiceSetting->tax_name }}: {{ $invoiceSetting->gst_number }}</div>
                @elseif ($invoiceSetting && $invoiceSetting->gst_number)
                    <div>GSTIN: {{ $invoiceSetting->gst_number }}</div>
                @endif
            </td>
            
            <!-- MIDDLE COLUMN: IGST INVOICE Title & Details -->
            <td class="header-middle" style="text-align: center; vertical-align: top; padding: 0; border: 1px solid #000;">
                <!-- Nested table with two main rows -->
                <table style="width: 100%; border-collapse: collapse; height: 100%;">
                    <!-- ROW 1: IGST INVOICE and CREDIT (Top Section) -->
                    <tr>
                        <td style="text-align: center; vertical-align: middle; padding: 8px 6px; border-bottom: 1px solid #000;">
                            <div style="color: #0000FF; font-size: 28px; font-weight: bold; margin-bottom: 2px; line-height: 1.0; text-transform: uppercase; letter-spacing: 0.5px;">IGST INVOICE</div>
                            <div style="color: #0000FF; font-size: 18px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px;">CREDIT</div>
                        </td>
                    </tr>
                    <!-- ROW 2: Invoice Details Table (Bottom Section) -->
                    <tr style="height: 100%;">
                        <td style="padding: 0; border: none; vertical-align: top; height: 100%; position: relative;">
                            <div style="position: relative; height: 100%; width: 100%; display: flex; flex-direction: column;">
                                <div style="position: relative; flex: 0 0 auto;">
                                <table style="width: 100%; border-collapse: collapse; border-spacing: 0; table-layout: fixed;">
                                    <!-- FIRST ROW: 4 columns -->
                                    <tr>
                                        <!-- Column 1: Invoice No (heading) -->
                                        <td style="font-weight: bold; padding: 6px 8px; font-size: 12px; text-align: left; width: 20%; border-right: 1px solid #000; border-bottom: 1px solid #000;">Invoice No</td>
                                        <!-- Column 2: Invoice No (value) -->
                                        <td style="padding: 6px 8px; font-size: 12px; text-align: left; width: 25%; border-right: 1px solid #000; border-bottom: 1px solid #000;">{{ $invoice->invoice_number }}</td>
                                        <!-- Column 3: L.R. No. and L.R. Date (headings and values) -->
                                        <td style="padding: 6px 8px; font-size: 12px; text-align: left; width: 30%; border-right: 1px solid #000; border-bottom: 1px solid #000;">
                                            <div>
                                                <span style="font-weight: bold;">L.R. No.</span>
                                                <span style="margin-left: 5px;">{{ $invoice->lr_number ?? '' }}</span>
                                            </div>
                                            <div>
                                                <span style="font-weight: bold;">L.R. Date</span>
                                                <span style="margin-left: 5px;">
                                                    @if($invoice->lr_date)
                                                        {{ \Carbon\Carbon::parse($invoice->lr_date)->format('d/m/Y') }}
                                                    @endif
                                                </span>
                                            </div>
                                        </td>
                                        <!-- Column 4: Cases (label left, value right) -->
                                        <td style="padding: 6px 8px; font-size: 12px; text-align: left; width: 25%; border-bottom: 1px solid #000;">
                                            <table style="width: 100%; border-collapse: collapse;">
                                                <tr>
                                                    <td style="font-weight: bold; padding: 0; border: none; width: 50%;">Cases</td>
                                                    <td style="padding: 0; border: none; width: 50%;">0</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <!-- SECOND ROW: 3 columns -->
                                    <tr>
                                        <!-- Column 1: Invoice Date and Due Date (headings) -->
                                        <td style="padding: 6px 8px; font-size: 12px; text-align: left; width: 20%; border-right: 1px solid #000; border-bottom: 1px solid #000; vertical-align: top;">
                                            <div style="font-weight: bold;">Invoice Date</div>
                                            <div style="font-weight: bold;">Due Date</div>
                                        </td>
                                        <!-- Column 2: Invoice Date and Due Date (values) -->
                                        <td style="padding: 6px 8px; font-size: 12px; text-align: left; width: 25%; border-right: 1px solid #000; border-bottom: 1px solid #000; vertical-align: top;">
                                            <div>{{ $invoice->issue_date->format('d/m/Y') }}</div>
                                            <div>{{ $invoice->due_date->format('d/m/Y') }}</div>
                                        </td>
                                        <!-- Column 3: Transport (spans to end) -->
                                        <td colspan="2" style="padding: 6px 8px; font-size: 12px; text-align: left; border-bottom: 1px solid #000; vertical-align: top;">
                                            <div style="font-weight: bold;">Transport</div>
                                        </td>
                                    </tr>
                                </table>
                                <!-- Border extension lines for first two rows only - vertical divider lines -->
                                <!-- Line 2: Between first and second column (20%) - only for first two rows -->
                                <div style="position: absolute; left: calc(20% - 0.5px); top: 0; height: calc(100% - 0px); width: 1px; background: #000; pointer-events: none; z-index: 10;"></div>
                                <!-- Line 3: Between second and third column (45%) - only for first two rows -->
                                <div style="position: absolute; left: calc(45% - 0.5px); top: 0; height: calc(100% - 0px); width: 1px; background: #000; pointer-events: none; z-index: 10;"></div>
                                </div>
                                <!-- Status row - centered like IGST INVOICE -->
                                <div style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 6px 8px; text-align: center; font-size: 12px; font-weight: bold; position: relative; z-index: 20; background: #fff;">
                                    Invoice: {{ $invoice->status == 'paid' ? __('app.paid') : __('app.unpaid') }}
                                </div>
                                <!-- Full height borders for the entire box including status row -->
                                <div style="position: absolute; left: 0; top: 0; bottom: 0; width: 1px; background: #000; pointer-events: none; z-index: 5;"></div>
                                <div style="position: absolute; right: 0; top: 0; bottom: 0; width: 1px; background: #000; pointer-events: none; z-index: 5;"></div>
                                <!-- Bottom border line -->
                                <div style="position: absolute; left: 0; right: 0; bottom: 0; height: 1px; background: #000; pointer-events: none; z-index: 10;"></div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
            
            <!-- RIGHT COLUMN: Party/Recipient Details (CFA Distributor) -->
            <td class="header-right">
                @if ($cfaDistributor)
                    <div class="party-name-label">Party Name: {{ $cfaDistributorDetails->company_name ?? $cfaDistributor->name }}</div>
                    @if ($cfaDistributorDetails)
                        <div>{{ $cfaDistributorDetails->address }}</div>
                        <div>PIN NO: {{ $cfaDistributorDetails->pin_code ?? '' }}</div>
                        <div>PHONE.: {{ $cfaDistributor->mobile ?? $cfaDistributorDetails->phone ?? '' }}</div>
                        @if ($cfaDistributorDetails->dl_number)
                            <div>DL NO.: {{ $cfaDistributorDetails->dl_number }}</div>
                        @endif
                        @if ($cfaDistributorDetails->gst_number)
                            <div>GSTIN: {{ $cfaDistributorDetails->gst_number }}</div>
                        @endif
                        <div>M.R.NAME.: </div>
                    @endif
                @endif
            </td>
        </tr>
    </table>


    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th width="3%">S.</th>
                <th width="8%">QTY. SCH</th>
                <th width="6%">Pack</th>
                <th width="6%">HSN</th>
                <th width="20%">Product Name</th>
                <th width="7%">Batch</th>
                <th width="5%">Exp</th>
                <th width="6%">M.R.P</th>
                <th width="6%">PTS</th>
                <th width="6%">PTR</th>
                <th width="5%">DIS (%)</th>
                <th width="8%">IGST</th>
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
                    if ($dis === null && $purchaseEntry && isset($purchaseEntry->dis) && $purchaseEntry->dis !== null) {
                        $dis = $purchaseEntry->dis;
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
                    
                    // Calculate tax amounts - identify SGST and CGST by tax name
                    $sgstAmount = 0;
                    $cgstAmount = 0;
                    
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
                                // Calculate tax on tax base amount (adjusted for discount if applicable)
                                $taxAmount = ($taxBaseAmount * $tax->rate_percent) / 100;
                                $taxNameUpper = strtoupper($tax->tax_name ?? '');
                                
                                // Check if tax name contains SGST or CGST
                                if (strpos($taxNameUpper, 'SGST') !== false) {
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
                    
                    // Calculate IGST = SGST + CGST
                    $igstAmount = $sgstAmount + $cgstAmount;
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
                    <td class="text-center">{{ number_format($igstAmount, 2) }}</td>
                    <td class="text-center">{{ number_format($item->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Summary Section - Two Column Layout -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 5px; margin-bottom: 5px;">
        <tr>
            <!-- Left Column: IGST Breakdown Table -->
            <td width="65%" style="vertical-align: top; padding-right: 5px;">
                <table class="summary-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th width="20%">IGST</th>
                            <th width="20%">TOTAL</th>
                            <th width="15%">SCHEME</th>
                            <th width="15%">DISCOUNT</th>
                            <th width="30%">IGST</th>
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
                                // Calculate IGST = SGST + CGST
                                $igstAmount = $data['sgst'] + $data['cgst'];
                            @endphp
                            <tr>
                                <td style="text-align: left;">IGST {{ number_format($rate, 2) }}%</td>
                                <td class="text-right">{{ number_format($data['total'], 2) }}</td>
                                <td class="text-right">0.00</td>
                                <td class="text-right">{{ number_format($data['discount'] ?? 0, 2) }}</td>
                                <td class="text-right">{{ number_format($igstAmount, 2) }}</td>
                            </tr>
                        @endforeach
                        @php
                            // Calculate total IGST = total SGST + total CGST
                            $totalIGST = $totalSGST + $totalCGST;
                            
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
                            <td class="text-right">{{ number_format($totalIGST, 2) }}</td>
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
                        <!-- Right Column: DIS AMT., IGST PAYBLE, CR/DR NOTE -->
                        <td style="padding: 8px 10px; border: 1px solid #000; font-size: 12px; vertical-align: top; line-height: 1.5; width: 50%;">
                            DIS AMT.: {{ number_format($displayDiscount ?? $discount, 2) }}<br>
                            IGST PAYBLE: {{ number_format($totalIGST, 2) }}<br>
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
