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
    $discount = $invoice->discount ?? 0; // Initialize discount variable
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
        
        // Get tax rate from item taxes - identify SGST and CGST by tax name
        if ($item->taxes) {
            // Handle both JSON array and string formats
            $taxes = [];
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
            // Remove duplicates and empty values
            $taxes = array_filter(array_unique($taxes));
            
            if (!empty($taxes)) {
                foreach ($taxes as $taxId) {
                    if (empty($taxId)) {
                        continue;
                    }
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
                        
                        // Identify SGST and CGST by tax name
                        $taxNameUpper = strtoupper($tax->tax_name ?? '');
                        if (strpos($taxNameUpper, 'SGST') !== false) {
                            $gstBreakdown[$rate]['sgst'] += $taxAmount;
                        } elseif (strpos($taxNameUpper, 'CGST') !== false) {
                            $gstBreakdown[$rate]['cgst'] += $taxAmount;
                        } else {
                            // If tax name doesn't contain SGST or CGST, split equally (fallback)
                            $gstBreakdown[$rate]['sgst'] += $taxAmount / 2;
                            $gstBreakdown[$rate]['cgst'] += $taxAmount / 2;
                        }
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
    
    // Get CFA/Distributor details (client - who is billing)
    $cfaDistributor = $invoice->client;
    $cfaDistributorDetails = $invoice->client->clientDetails ?? null;
    $client = $invoice->client ?? ($user ?? null);
    $clientDetails = $client ? ($client->clientDetails ?? null) : null;
    
    // Get CFA Stockist details (who is being billed)
    $cfaStockist = $cfaStockist ?? null;
    $cfaStockistDetails = null;
@endphp

<style>
    @page {
        size: landscape;
        margin: 10mm;
    }
    .pharma-invoice-body {
        font-family: Arial, sans-serif;
        font-size: 12px;
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
        /** Ryva Vitabiotics — seller block; party = CFA stockist */
        $stk = $cfaStockist;
        $phiSellerGst = '09AAOCR8265M1ZD';
        $phiIssueTime = $invoice->created_at
            ? $invoice->created_at->timezone(company()->timezone ?? config('app.timezone'))->format('H:i:s')
            : '';
        $phiCustGst = $stk && $stk->gst_number ? trim((string) $stk->gst_number) : '';
        $phiCustState = strlen((string) $phiCustGst) >= 2 ? substr((string) $phiCustGst, 0, 2) : '';
        $phiBillName = $stk
            ? (trim((string) ($stk->shopname ?? '')) !== '' ? trim((string) $stk->shopname) : ($stk->fullname ?? '—'))
            : '—';
        $phiBillAddr = $stk && $stk->address ? trim((string) $stk->address) : '';
        $phiShipAddr = $phiBillAddr;
        $phiPlaceSupply = optional($stk?->area)->name ?? '';
        $phiQrPayload = trim(($invoice->invoice_number ?? '') . '|' . $phiSellerGst . '|' . $invoice->issue_date->format('d-m-Y'));
        $phiQrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=' . rawurlencode($phiQrPayload);
        $phiOrderRef = ($invoice->relationLoaded('order') && $invoice->order) ? ($invoice->order->order_number ?? $invoice->order->id) : '';
        $phiBillEmail = $stk ? trim((string) ($stk->email ?? '')) : '';
        $phiBillPhone = $stk && !empty($stk->mobile) ? trim((string) $stk->mobile) : '';
        $phiStkCode = $stk && !empty($stk->cfa_stockist_id) ? $stk->cfa_stockist_id : '—';
    @endphp

    <div class="pharma-tax-invoice-header">
        <table class="phi-title-row" style="margin-bottom:0;border-bottom:0;">
            <tr>
                <td colspan="4" style="text-align:center;border-bottom:1px solid #000;">
                    <div class="phi-title">TAX INVOICE</div>
                    <div class="phi-sub-legal">Issued under sec. 31 of CGST act, 2017 read with rule 48 of CGST Rule, 2017</div>
                </td>
                <td style="width:11%;text-align:right;vertical-align:top;font-size:8px;border-bottom:1px solid #000;">PAGE 1 OF 1</td>
            </tr>
        </table>

        <table>
            <tr>
                <td style="width:22%;vertical-align:top;">
                    @if (company()->light_logo_url)
                        <div class="phi-col-logo">
                            <img src="{{ company()->light_logo_url }}" alt="Ryva Vitabiotics Pvt. Ltd." />
                        </div>
                    @endif
                    <div class="phi-label">RYVA VITABIOTICS PVT. LTD.</div>
                    <div class="phi-kv"><span class="phi-label">H.O.:</span> 4, Pal Complex, Near BCC Green, Deva Road, Lucknow 226028</div>
                    <div class="phi-kv"><span class="phi-label">C.O. / BRANCH:</span> H365+5P5, Manish Global Mall, Sector-22, Dwarka, New Delhi - 110077</div>
                    <div class="phi-kv">E-MAIL: info@ryvavitabiotics.com</div>
                    <div class="phi-kv">PHONE: +919308465860</div>
                    <div class="phi-kv"><span class="phi-label">GSTIN:</span> 09AAOCR8265M1ZD</div>
                    <div class="phi-kv">DL NUM- WLF20B2025UP009468</div>
                    <div class="phi-kv">DL NUM-WLF21B2025UP009439</div>
                </td>
                <td style="width:20%;vertical-align:top;">
                    <div class="phi-label" style="margin-bottom:4px;">INVOICED AT:</div>
                    <div class="phi-kv">H365+5P5, Manish Global Mall, Sector-22, Dwarka, New Delhi - 110077</div>
                    <div class="phi-kv"><span class="phi-label">GSTIN:</span> 09AAOCR8265M1ZD</div>
                    <div class="phi-kv"><span class="phi-label">STATE CODE:</span> 09</div>
                </td>
                <td style="width:25%;vertical-align:top;">
                    <div class="phi-kv"><span class="phi-label">STK CODE:</span> {{ $phiStkCode }}</div>
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
                    @if ($stk && $stk->dl_number)
                        <div class="phi-kv"><span class="phi-label">CUST D.L.NO.:</span> {{ $stk->dl_number }}</div>
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
                <th width="18%">Product Name</th>
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
                    
                    // Get batch and expiry from item, CFAStockistStock, or CFA Distributor Stock
                    $cfaStockistStock = $invoice->cfaStockistStocks->where('product_id', $item->product_id)->first();
                    $cfaDistributorStock = $cfaStockistStock ? $cfaStockistStock->cfaDistributorStock : null;
                    
                    // Get purchase entry from CFA Distributor Stock (if available)
                    $purchaseEntry = null;
                    if ($cfaDistributorStock && isset($cfaDistributorStock->purchaseEntry) && $cfaDistributorStock->purchaseEntry) {
                        $purchaseEntry = $cfaDistributorStock->purchaseEntry;
                    } elseif (isset($item->purchaseEntry) && $item->purchaseEntry) {
                        // Fallback to item's purchase entry if available
                        $purchaseEntry = $item->purchaseEntry;
                    }
                    
                    // Try to get batch from multiple sources
                    $batch = '';
                    if (!empty($item->batch)) {
                        $batch = $item->batch;
                    } elseif ($cfaStockistStock && !empty($cfaStockistStock->batch)) {
                        $batch = $cfaStockistStock->batch;
                    } elseif ($cfaDistributorStock && !empty($cfaDistributorStock->batch)) {
                        $batch = $cfaDistributorStock->batch;
                    }
                    
                    // Try to get expiry from multiple sources
                    $exp = '';
                    if ($item->exp) {
                        try {
                            $exp = \Carbon\Carbon::parse($item->exp)->format('m/y');
                        } catch (\Exception $e) {
                            $exp = '';
                        }
                    } elseif ($cfaStockistStock && $cfaStockistStock->expiry) {
                            try {
                            $exp = \Carbon\Carbon::parse($cfaStockistStock->expiry)->format('m/y');
                            } catch (\Exception $e) {
                                $exp = '';
                            }
                    } elseif ($cfaDistributorStock && $cfaDistributorStock->expiry) {
                        try {
                            $exp = \Carbon\Carbon::parse($cfaDistributorStock->expiry)->format('m/y');
                        } catch (\Exception $e) {
                            $exp = '';
                        }
                    }
                    
                    // Get pharma fields - try multiple sources
                    $pack = '';
                    if (!empty($item->pack)) {
                        $pack = $item->pack;
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
                               $purchaseEntry->vendor->primary_name ?? '';
                    } elseif ($purchaseEntry && $purchaseEntry->product && $purchaseEntry->product->vendor) {
                        // Fallback to product vendor's company_name for MFR display
                        $mfr = $purchaseEntry->product->vendor->company_name ?? 
                               $purchaseEntry->product->vendor->primary_name ?? '';
                    } elseif ($item->product && $item->product->vendor) {
                        // Last fallback to item product vendor's company_name for MFR display
                        $mfr = $item->product->vendor->company_name ?? 
                               $item->product->vendor->primary_name ?? '';
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
                        if ($vendor && $vendor->primary_name == $savedMfr) {
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
                    
                    // Get HSN code (SKU) - priority: invoice item > product SKU > product hsn_sac_code > CFAStockistStock product > CFADistributorStock product > empty
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
                    // Then check CFAStockistStock product relationship (prioritize SKU)
                    elseif ($cfaStockistStock && $cfaStockistStock->product) {
                        if (!empty($cfaStockistStock->product->sku)) {
                            $hsn = $cfaStockistStock->product->sku;
                        } elseif (!empty($cfaStockistStock->product->hsn_sac_code)) {
                            $hsn = $cfaStockistStock->product->hsn_sac_code;
                        }
                    }
                    // Also check CFADistributorStock product relationship (prioritize SKU)
                    elseif ($cfaDistributorStock && $cfaDistributorStock->product) {
                        if (!empty($cfaDistributorStock->product->sku)) {
                            $hsn = $cfaDistributorStock->product->sku;
                        } elseif (!empty($cfaDistributorStock->product->hsn_sac_code)) {
                            $hsn = $cfaDistributorStock->product->hsn_sac_code;
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
                    
                    // Handle calculate_tax == 'after_discount' if applicable
                    $taxBaseAmount = $itemAmount;
                    if ($invoice->calculate_tax == 'after_discount' && $discount > 0 && $invoice->sub_total > 0) {
                        // Adjust tax base by subtracting proportional discount
                        $taxBaseAmount = $itemAmount - (($itemAmount / $invoice->sub_total) * $discount);
                    }
                    
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
                <strong>FOR {{ company()->company_name }}</strong><br><br><br>
                <strong>Authorised Signatory</strong>
            </td>
        </tr>
    </table>
</div>
