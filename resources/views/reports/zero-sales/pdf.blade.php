<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Zero Sales Report</title>
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
        <h3>Zero Sales Report</h3>
    </div>
    <table>
        <thead>
            <tr>
                <th>Entity Type</th>
                <th>Entity Name</th>
                <th>@lang('app.hq')</th>
                <th>Area</th>
                <th>Region</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
            <tr>
                <td>{{ $row->entity_type ?? '--' }}</td>
                <td>{{ $row->entity_name ?? '--' }}</td>
                <td>{{ $row->hq_name ?? '--' }}</td>
                <td>{{ $row->area_name ?? '--' }}</td>
                <td>{{ $row->region_name ?? '--' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
