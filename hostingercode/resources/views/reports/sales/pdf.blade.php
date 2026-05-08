<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@lang('app.menu.salesReport')</title>
    <style>
        body { margin: 0; font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 11px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #e7e9eb; padding: 4px 6px; text-align: left; }
        th { background-color: #f2f4f7; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9fafb; }
        .header { margin-bottom: 15px; }
        .header img { height: 35px; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ invoice_setting()->logo_url }}" alt="Logo" />
        <h3>@lang('app.menu.salesReport')</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>@lang('app.date')</th>
                <th>@lang('app.invoiceNumber')</th>
                <th>Invoice Type</th>
                <th>@lang('app.clientName')</th>
                <th>Stockist</th>
                <th>@lang('modules.invoices.invoiceValue')</th>
                <th>@lang('modules.invoices.amountPaid')</th>
                <th>@lang('modules.invoices.taxableValue')</th>
                <th>@lang('modules.invoices.discount')</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
            @php
                $invoiceType = '--';
                if ($row->cfaDistributorStocks && $row->cfaDistributorStocks->isNotEmpty()) {
                    $invoiceType = 'Company→CFA';
                } elseif ($row->cfaStockistStocks && $row->cfaStockistStocks->isNotEmpty()) {
                    $invoiceType = 'CFA→Stockist';
                }
                $stock = $row->cfaStockistStocks->first();
                $stockistName = ($stock && $stock->cfaStockist) ? ($stock->cfaStockist->shopname ?? $stock->cfaStockist->fullname ?? '--') : '--';
            @endphp
            <tr>
                <td>{{ $row->issue_date ? $row->issue_date->format($company->date_format) : '--' }}</td>
                <td>{{ $row->custom_invoice_number ?? '--' }}</td>
                <td>{{ $invoiceType }}</td>
                <td>{{ $row->client ? $row->client->name : '--' }}</td>
                <td>{{ $stockistName }}</td>
                <td>{{ $row->total ? currency_format($row->total, $row->currency_id) : '--' }}</td>
                <td>{{ currency_format($row->amountPaid(), $row->currency_id) }}</td>
                <td>{{ currency_format($row->sub_total, $row->currency_id) }}</td>
                <td>{{ $row->discount > 0 ? currency_format($row->discount_type == 'percent' ? (($row->discount / 100) * $row->sub_total) : $row->discount, $row->currency_id) : 0 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
