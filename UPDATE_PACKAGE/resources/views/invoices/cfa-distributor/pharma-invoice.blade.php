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
    $totalQty = 0;
    $totalItems = 0;
    
    // Filter items and ensure uniqueness for totals calculation
    $itemsForTotals = $invoice->items->where('type', 'item')->unique('id');
    foreach ($itemsForTotals as $item) {
        $totalItems++;
        $paidQty = $item->quantity;
        $scheme = $item->scheme ?? '';
        $freeQty = calculateFreeQty($paidQty, $scheme);
        $totalQty += ($paidQty + $freeQty);
        
        $itemAmount = $item->amount;
        $totalSubTotal += $itemAmount;
        
        // Get tax rate from item taxes
        if ($item->taxes) {
            $taxes = json_decode($item->taxes, true);
            if (is_array($taxes)) {
                foreach ($taxes as $taxId) {
                    $tax = \App\Models\Tax::find($taxId);
                    if ($tax) {
                        $rate = $tax->rate_percent;
                        $taxAmount = ($itemAmount * $rate) / 100;
                        
                        if (!isset($gstBreakdown[$rate])) {
                            $gstBreakdown[$rate] = [
                                'rate' => $rate,
                                'total' => 0,
                                'sgst' => 0,
                                'cgst' => 0,
                                'total_gst' => 0
                            ];
                        }
                        $gstBreakdown[$rate]['total'] += $itemAmount;
                        // Split GST equally between SGST and CGST
                        $gstBreakdown[$rate]['sgst'] += $taxAmount / 2;
                        $gstBreakdown[$rate]['cgst'] += $taxAmount / 2;
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
        size: landscape;
        margin: 10mm;
    }
    .pharma-invoice-body {
        font-family: Arial, sans-serif;
        font-size: 10px;
        margin: 0 auto;
        padding: 0;
        color: #000;
        line-height: 1.3;
        width: 100%;
        max-width: 297mm;
        position: relative;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .invoice-container {
        width: 100%;
        max-width: 297mm;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #000;
        padding: 5px;
        position: relative;
        box-sizing: border-box;
        /* Center aligned container */
    }
    
    
    /* Header Table Styles */
    .header-main-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 3px;
        border: 1px solid #000;
    }
    .header-main-table td {
        border: 1px solid #000;
        padding: 4px;
        vertical-align: top;
        border-width: 1px;
    }
    
    /* Left Column - Company/CFA Info (Blue) */
    .header-left {
        width: 33%;
        padding: 4px;
        font-size: 10px;
        line-height: 1.3;
    }
    .header-left div {
        color: #0000FF;
        margin-bottom: 0px;
        padding: 1px 0;
    }
    .header-left .company-name {
        font-weight: bold;
        font-size: 15px;
        color: #0000FF;
        margin-bottom: 1px;
    }
    .header-left .cfa-name {
        font-weight: bold;
        font-size: 11px;
        color: #0000FF;
        margin-bottom: 1px;
    }
    
    .header-left .company-logo {
        margin-bottom: 8px;
        text-align: left;
    }
    
    .header-left .company-logo img {
        max-width: 200px;
        max-height: 80px;
        width: auto !important;
        height: auto !important;
        object-fit: contain;
        display: block;
        /* Maintain original aspect ratio - don't force square */
        /* Ensure natural aspect ratio is preserved */
        aspect-ratio: auto;
    }
    
    /* Prevent any external CSS from forcing square dimensions */
    .header-left .company-logo img[width],
    .header-left .company-logo img[height] {
        width: auto !important;
        height: auto !important;
    }
    
    /* Middle Column - GST INVOICE Title */
    .header-middle {
        width: 34%;
        padding: 0;
        text-align: center;
        vertical-align: top;
    }
    .gst-invoice-title {
        color: #0000FF;
        font-size: 26px;
        font-weight: bold;
        margin-bottom: 2px;
        line-height: 1.0;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .credit-subtitle {
        color: #0000FF;
        font-size: 17px;
        font-weight: bold;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
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
        padding: 2px 4px;
        font-size: 9px;
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
        padding: 4px;
        font-size: 10px;
        line-height: 1.3;
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
        padding: 4px;
        font-size: 10px;
        font-weight: bold;
    }
    
    /* Items Table */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 3px;
        border: 1px solid #000;
    }
    .items-table th {
        background-color: #f0f0f0;
        font-weight: bold;
        text-align: center;
        font-size: 9px;
        padding: 4px 2px;
        border: 1px solid #000;
        border-width: 1px;
    }
    .items-table td {
        text-align: center;
        font-size: 9px;
        padding: 2px 2px;
        border: 1px solid #000;
        border-width: 1px;
    }
    .items-table .text-left {
        text-align: left;
        padding-left: 3px;
    }
    .items-table .text-right {
        text-align: right;
        padding-right: 3px;
    }
    .items-table .text-center {
        text-align: center !important;
        padding: 2px;
    }
    
    /* Summary Section */
    .summary-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
        font-size: 9px;
        border: 1px solid #000;
    }
    .summary-table th {
        background-color: #f0f0f0;
        font-weight: bold;
        text-align: center;
        padding: 4px 2px;
        border: 1px solid #000;
        border-width: 1px;
        font-size: 9px;
    }
    .summary-table td {
        padding: 3px 2px;
        border: 1px solid #000;
        border-width: 1px;
        font-size: 9px;
        text-align: center;
    }
    .summary-table td.text-right {
        text-align: right;
        padding-right: 3px;
    }
    .summary-info-section {
        width: 100%;
        margin-top: 0;
        margin-bottom: 3px;
    }
    .summary-info-left {
        width: 50%;
        float: left;
        font-size: 9px;
        line-height: 1.3;
    }
    .summary-info-right {
        width: 50%;
        float: right;
        text-align: right;
        font-size: 11px;
        font-weight: bold;
        padding-top: 0;
    }
    
    .amount-in-words {
        font-weight: bold;
        font-size: 10px;
        margin-top: 3px;
        margin-bottom: 2px;
        line-height: 1.3;
    }
    .payment-message {
        font-size: 10px;
        margin-top: 2px;
        margin-bottom: 3px;
        line-height: 1.3;
    }
    
    /* Footer Section */
    .footer-section {
        margin-top: 5px;
        clear: both;
        font-size: 9px;
    }
    .footer-section table {
        border: 1px solid #000;
        width: 100%;
        border-collapse: collapse;
    }
    .footer-section td {
        border: 1px solid #000;
        padding: 4px;
        vertical-align: top;
    }
    
    .clearfix::after {
        content: "";
        display: table;
        clear: both;
    }
    
    @media print {
        @page {
            size: landscape;
            margin: 10mm;
        }
        body {
            padding: 0;
            margin: 0;
        }
        .no-print {
            display: none;
        }
        .pharma-invoice-body {
            padding: 0;
            margin: 0;
        }
        .invoice-container {
            max-width: 100%;
            margin: 0;
            padding: 5px;
        }
    }
</style>

<div class="invoice-container pharma-invoice-body">
    <!-- Header Section - Three Columns -->
    <table class="header-main-table">
        <tr>
            <!-- LEFT COLUMN: Company & CFA Details (Blue) -->
            <td class="header-left">
                @if(company()->light_logo_url)
                    <div class="company-logo">
                        <img src="{{ company()->light_logo_url }}" alt="{{ company()->company_name }} Logo" />
                    </div>
                @endif
                <div class="company-name">{{ company()->company_name }}</div>
                <div class="cfa-name">CFA {{ $cfaDistributorDetails->company_name ?? $cfaDistributor->name }}</div>
                @if ($cfaDistributorDetails)
                    <div>{{ $cfaDistributorDetails->address }}</div>
                @endif
                @if ($cfaDistributor && $cfaDistributor->email)
                    <div>E-Mail: {{ $cfaDistributor->email }}</div>
                @endif
                <div>Phone: {{ $cfaDistributorDetails->phone ?? ($cfaDistributor->mobile ?? company()->company_phone) }}</div>
                @if ($cfaDistributorDetails && $cfaDistributorDetails->dl_number)
                    <div>D.L.No.: {{ $cfaDistributorDetails->dl_number }}</div>
                @endif
                @if ($cfaDistributorDetails && $cfaDistributorDetails->gst_number)
                    <div>GSTIN: {{ $cfaDistributorDetails->gst_number }}</div>
                @endif
            </td>
            
            <!-- MIDDLE COLUMN: GST INVOICE Title & Details -->
            <td class="header-middle" style="text-align: center; vertical-align: top; padding: 0; border: 1px solid #000;">
                <!-- Nested table with two main rows -->
                <table style="width: 100%; border-collapse: collapse; height: 100%;">
                    <!-- ROW 1: GST INVOICE and CREDIT (Top Section) -->
                    <tr>
                        <td style="text-align: center; vertical-align: middle; padding: 8px 5px; border-bottom: 1px solid #000;">
                            <div style="color: #0000FF; font-size: 26px; font-weight: bold; margin-bottom: 3px; line-height: 1.0; text-transform: uppercase; letter-spacing: 1px;">GST INVOICE</div>
                            <div style="color: #0000FF; font-size: 17px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px;">CREDIT</div>
                        </td>
                    </tr>
                    <!-- ROW 2: Invoice Details Table (Bottom Section) -->
                    <tr>
                        <td style="padding: 0; border: none; vertical-align: top;">
                            <table style="width: 100%; border-collapse: collapse; border-spacing: 0;">
                                <!-- FIRST ROW: 4 columns -->
                                <tr>
                                    <!-- Column 1: Invoice No (heading) -->
                                    <td style="font-weight: bold; padding: 4px 6px; font-size: 9px; text-align: left; width: 20%; border-right: 1px solid #000; border-bottom: 1px solid #000;">Invoice No</td>
                                    <!-- Column 2: Invoice No (value) -->
                                    <td style="padding: 4px 6px; font-size: 9px; text-align: left; width: 25%; border-right: 1px solid #000; border-bottom: 1px solid #000;">{{ $invoice->invoice_number }}</td>
                                    <!-- Column 3: L.R. No. and L.R. Date (headings and values) -->
                                    <td style="padding: 4px 6px; font-size: 9px; text-align: left; width: 30%; border-right: 1px solid #000; border-bottom: 1px solid #000;">
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
                                    <td style="padding: 4px 6px; font-size: 9px; text-align: left; width: 25%; border-bottom: 1px solid #000;">
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
                                    <td style="padding: 4px 6px; font-size: 9px; text-align: left; width: 20%; border-right: 1px solid #000;">
                                        <div style="font-weight: bold;">Invoice Date</div>
                                        <div style="font-weight: bold;">Due Date</div>
                                    </td>
                                    <!-- Column 2: Invoice Date and Due Date (values) -->
                                    <td style="padding: 4px 6px; font-size: 9px; text-align: left; width: 25%; border-right: 1px solid #000;">
                                        <div>{{ $invoice->issue_date->format('d/m/Y') }}</div>
                                        <div>{{ $invoice->due_date->format('d/m/Y') }}</div>
                                    </td>
                                    <!-- Column 3: Transport (spans to end) -->
                                    <td colspan="2" style="padding: 4px 6px; font-size: 9px; text-align: left;">
                                        <div style="font-weight: bold;">Transport</div>
                                    </td>
                                </tr>
                            </table>
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
                <th width="5%">Mfr</th>
                <th width="6%">Pack</th>
                <th width="6%">HSN</th>
                <th width="15%">Product Name</th>
                <th width="7%">Batch</th>
                <th width="5%">Exp</th>
                <th width="6%">M.R.P</th>
                <th width="6%">PTS</th>
                <th width="6%">PTR</th>
                <th width="5%">DIS (%)</th>
                <th width="5%">SGST</th>
                <th width="5%">CGST</th>
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
                    // QTY. SCH column should display the scheme value directly from database
                    // e.g., "20+2", "10+1", etc.
                    $qtyDisplay = $scheme;
                    
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
                    if (!empty($item->mfr)) {
                        $mfr = $item->mfr;
                    } elseif ($purchaseEntry && $purchaseEntry->product && $purchaseEntry->product->vendor) {
                        // Try multiple vendor name fields
                        $mfr = $purchaseEntry->product->vendor->primary_name ?? 
                               $purchaseEntry->product->vendor->company_name ?? 
                               $purchaseEntry->product->vendor->name ?? '';
                    } elseif ($item->product && $item->product->vendor) {
                        // Try multiple vendor name fields
                        $mfr = $item->product->vendor->primary_name ?? 
                               $item->product->vendor->company_name ?? 
                               $item->product->vendor->name ?? '';
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
                    
                    // Calculate tax amounts
                    $sgstAmount = 0;
                    $cgstAmount = 0;
                    if ($item->taxes) {
                        $taxes = json_decode($item->taxes, true);
                        if (is_array($taxes)) {
                            foreach ($taxes as $taxId) {
                                $tax = \App\Models\Tax::find($taxId);
                                if ($tax) {
                                    $taxAmount = ($item->amount * $tax->rate_percent) / 100;
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
                    <td>{{ $mfr }}</td>
                    <td>{{ $pack }}</td>
                    <td>{{ $hsn }}</td>
                    <td class="text-left">{{ $productName }}</td>
                    <td>{{ $batch }}</td>
                    <td>{{ $exp }}</td>
                    <td class="text-center">{{ number_format($mrp, 2) }}</td>
                    <td class="text-center">{{ number_format($pts, 2) }}</td>
                    <td class="text-center">{{ number_format($ptr, 2) }}</td>
                    <td class="text-center">{{ number_format($dis, 2) }}</td>
                    <td class="text-center">{{ number_format($sgstAmount, 2) }}</td>
                    <td class="text-center">{{ number_format($cgstAmount, 2) }}</td>
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
                            <th width="12%">SGST</th>
                            <th width="12%">CGST</th>
                            <th width="17%">TOTAL GST</th>
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
                                    'sgst' => 0,
                                    'cgst' => 0,
                                    'total_gst' => 0
                                ];
                            @endphp
                            <tr>
                                <td style="text-align: left;">GST {{ number_format($rate, 2) }}%</td>
                                <td class="text-right">{{ number_format($data['total'], 2) }}</td>
                                <td class="text-right">0.00</td>
                                <td class="text-right">0.00</td>
                                <td class="text-right">{{ number_format($data['sgst'], 2) }}</td>
                                <td class="text-right">{{ number_format($data['cgst'], 2) }}</td>
                                <td class="text-right">{{ number_format($data['total_gst'], 2) }}</td>
                            </tr>
                        @endforeach
                        <tr style="font-weight: bold;">
                            <td style="text-align: left;">TOTAL</td>
                            <td class="text-right">{{ number_format($invoice->sub_total, 2) }}</td>
                            <td class="text-right">0.00</td>
                            <td class="text-right">{{ number_format($discount, 2) }}</td>
                            <td class="text-right">{{ number_format($totalSGST, 2) }}</td>
                            <td class="text-right">{{ number_format($totalCGST, 2) }}</td>
                            <td class="text-right">{{ number_format($totalGST, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
            
            <!-- Right Column: Summary Info -->
            <td width="35%" style="vertical-align: top; padding-left: 3px;">
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #000;">
                    <!-- TOTAL Row -->
                    <tr>
                        <td style="padding: 3px 4px; border: 1px solid #000; font-size: 11px; font-weight: bold; text-align: left;">
                            TOTAL
                        </td>
                        <td style="padding: 3px 4px; border: 1px solid #000; font-size: 11px; font-weight: bold; text-align: right;">
                            {{ number_format($invoice->sub_total, 2) }}
                        </td>
                    </tr>
                    <!-- Summary Details Row - Two Columns -->
                    <tr>
                        <!-- Left Column: Total Items, Total Qty, C/N NO -->
                        <td style="padding: 3px 4px; border: 1px solid #000; font-size: 9px; vertical-align: top; line-height: 1.3; width: 50%;">
                            Total Items: {{ $totalItems }}<br>
                            Total Qty: {{ $totalQty }}<br>
                            C/N NO: -- Add
                        </td>
                        <!-- Right Column: DIS AMT., SGST PAYBLE, CGST PAYBLE, CR/DR NOTE -->
                        <td style="padding: 3px 4px; border: 1px solid #000; font-size: 9px; vertical-align: top; line-height: 1.3; width: 50%;">
                            DIS AMT.: {{ number_format($discount, 2) }}<br>
                            SGST PAYBLE: {{ number_format($totalSGST, 2) }}<br>
                            CGST PAYBLE: {{ number_format($totalCGST, 2) }}<br>
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
                        <td style="padding: 3px 4px; border: 1px solid #000; font-size: 9px; line-height: 1.3;">
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
                            <div style="font-weight: bold; font-size: 10px; margin-bottom: 2px;">Rs. {{ $amountInWords }} only</div>
                            <div style="font-size: 10px;">MSG: PLEASE MAKE PAYMENT WITHIN 21 DAYS</div>
                        </td>
                    </tr>
                </table>
            </td>
            <!-- Right Column: Grand Total -->
            <td width="35%" style="vertical-align: top; padding-left: 3px;">
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #000;">
                    <tr>
                        <td style="padding: 8px 4px; border: 1px solid #000; text-align: center; vertical-align: middle; min-height: 40px;">
                            <strong style="font-size: 11px;">Grand Total: {{ number_format($grandTotal, 2) }}</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Footer Section - Three Columns -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 5px; border: 1px solid #000;">
        <tr>
            <td width="33%" style="padding: 4px; border: 1px solid #000; font-size: 9px; vertical-align: top; text-align: left; line-height: 1.3;">
                <strong>Terms & Conditions</strong><br>
                All disputes subject to Lucknow Jurisdiction only.<br>
                Bills not paid due date will attract 24% interest.<br>
                Goods once sold will not be taken back or exchanged.
            </td>
            <td width="34%" style="padding: 4px; border: 1px solid #000; font-size: 9px; vertical-align: top; text-align: center; line-height: 1.3;">
                <strong>Bank Details :-</strong>
                @if ($cfaDistributorDetails && ($cfaDistributorDetails->bank_account_name || $cfaDistributorDetails->bank_account_number || $cfaDistributorDetails->bank_ifsc_code))
                    @if ($cfaDistributorDetails->bank_account_name)
                        {{ $cfaDistributorDetails->bank_account_name }}<br>
                    @endif
                    @if ($cfaDistributorDetails->bank_account_number)
                        ACCOUNT NO. {{ $cfaDistributorDetails->bank_account_number }}<br>
                    @endif
                    @if ($cfaDistributorDetails->bank_ifsc_code)
                        IFSC CODE - {{ $cfaDistributorDetails->bank_ifsc_code }}
                    @endif
                @elseif ($invoice->bankAccount)
                    {{ $invoice->bankAccount->bank_name }}<br>
                    {{ $invoice->bankAccount->account_name }}<br>
                    ACCOUNT NO. {{ $invoice->bankAccount->account_number }}<br>
                    IFSC CODE - {{ $invoice->bankAccount->ifsc_code }}
                @else
                    <br><br><br>
                @endif
            </td>
            <td width="33%" style="padding: 4px; border: 1px solid #000; font-size: 9px; vertical-align: top; text-align: right; line-height: 1.3;">
                <strong>FOR {{ $cfaDistributorDetails->company_name ?? $cfaDistributor->name }}</strong><br><br><br>
                <strong>Authorised Signatory</strong>
            </td>
        </tr>
    </table>
</div>
