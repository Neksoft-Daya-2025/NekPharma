<div class="bg-white rounded b-shadow-4 p-4">
    <h4 class="mb-3">@lang('app.salesStockStatement')</h4>
    <div class="row mb-3">
        <div class="col-md-3"><strong>Period:</strong> {{ \Carbon\Carbon::create()->month($statement->period_month)->format('F') }} {{ $statement->period_year }}</div>
        <div class="col-md-4"><strong>Stockist:</strong> {{ $statement->cfaStockist->shopname ?? '-' }} ({{ $statement->cfaStockist->cfa_stockist_id ?? '-' }})</div>
        <div class="col-md-3"><strong>MR:</strong> {{ $statement->user->name ?? '-' }}</div>
        <div class="col-md-2"><strong>Status:</strong> <span class="badge badge-{{ $statement->status === 'submitted' ? 'success' : 'secondary' }}">{{ ucfirst($statement->status) }}</span></div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>@lang('app.name')</th>
                    <th class="text-right">Opening</th>
                    <th class="text-right">Primary</th>
                    <th class="text-right">Secondary</th>
                    <th class="text-right">Closing</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statement->lines as $line)
                    <tr>
                        <td>{{ $line->product->name ?? '-' }}</td>
                        <td class="text-right">{{ number_format($line->opening_qty, 2) }}</td>
                        <td class="text-right">{{ number_format($line->primary_qty, 2) }}</td>
                        <td class="text-right">{{ number_format($line->secondary_qty, 2) }}</td>
                        <td class="text-right">{{ number_format($line->closing_qty, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
