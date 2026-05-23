@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <a href="{{ route('stock-statements.index') }}" class="btn btn-secondary btn-sm">@lang('app.back')</a>
            </div>
            @if($canEditStatement ?? false)
                <div>
                    <a href="{{ route('stock-statements.edit', $statement->id) }}" class="btn btn-primary btn-sm">@lang('app.edit')</a>
                </div>
            @endif
        </div>
        <div class="bg-white rounded b-shadow-4 p-4">
            <h4 class="mb-3">@lang('app.salesStockStatement')</h4>
            <div class="row mb-3">
                <div class="col-md-3"><strong>Period:</strong> {{ \Carbon\Carbon::create()->month($statement->period_month)->format('F') }} {{ $statement->period_year }}</div>
                <div class="col-md-4"><strong>Stockist:</strong> {{ $statement->cfaStockist->shopname ?? '-' }} ({{ $statement->cfaStockist->cfa_stockist_id ?? '-' }})</div>
                <div class="col-md-3"><strong>MR:</strong> {{ $statement->user->name ?? '-' }}</div>
                <div class="col-md-2"><strong>Status:</strong> <span class="badge badge-{{ $statement->status === 'submitted' ? 'success' : 'secondary' }}">{{ ucfirst($statement->status) }}</span></div>
            </div>
            @if($statement->submitted_at)
                <div class="mb-3"><strong>Submitted at:</strong> {{ $statement->submitted_at->format('d M Y H:i') }}</div>
            @endif
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
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
    </div>
@endsection
