<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>@lang('app.menu.dcrReport')</title>
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
        <h3>@lang('app.menu.dcrReport')</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>@lang('app.date')</th>
                <th>@lang('app.employee')</th>
                <th>@lang('app.role')</th>
                <th>@lang('app.hq')</th>
                <th>@lang('app.stationType')</th>
                <th>@lang('app.partyName')</th>
                <th>@lang('app.partyType')</th>
                <th>@lang('app.product')</th>
                <th>@lang('app.visitTime')</th>
                <th>@lang('app.remarks')</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
            <tr>
                <td>{{ $row->date instanceof \Carbon\Carbon ? $row->date->format($company->date_format) : \Carbon\Carbon::parse($row->date)->format($company->date_format) }}</td>
                <td>{{ $row->employee_name ?? '-' }}</td>
                <td>{{ $row->role ?? '-' }}</td>
                <td>{{ $row->headquarter ?? '-' }}</td>
                <td>{{ $row->station_type ?? '-' }}</td>
                <td>{{ $row->party_name ?? '-' }}</td>
                <td>{{ $row->party_type ?? '-' }}</td>
                <td>{{ $row->product ?? '-' }}</td>
                <td>{{ $row->visit_time ?? '-' }}</td>
                <td>{{ $row->remarks ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
