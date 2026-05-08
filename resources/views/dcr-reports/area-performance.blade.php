@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-lg-flex d-md-flex d-block justify-content-between action-bar">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                <form method="GET" action="{{ route('dcr-management.area-performance') }}" id="filter-form" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    @if(user()->permission('view_dcr_reports') == 'all' || user()->permission('view_dcr_reports') == 'added')
                        @if(isset($headquarters) && $headquarters->isNotEmpty())
                        <div>
                            <select name="hq" class="select-picker" data-live-search="true" title="Select Headquarter" style="min-width: 200px;">
                                <option value="">-- All Headquarters --</option>
                                @foreach($headquarters as $hq)
                                    <option value="{{ $hq->id }}" {{ (isset($selectedHQ) && $selectedHQ == $hq->id) ? 'selected' : '' }}>{{ $hq->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                    @endif
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <label class="mb-0" style="font-weight: 500;">From Date:</label>
                        <input type="date" name="from_date" class="form-control" value="{{ $fromDate ?? '' }}" style="width: 150px;">
                    </div>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <label class="mb-0" style="font-weight: 500;">To Date:</label>
                        <input type="date" name="to_date" class="form-control" value="{{ $toDate ?? '' }}" style="width: 150px;">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> Apply Filter</button>
                    @if(($selectedHQ ?? '') !== '' || ($fromDate ?? '') !== '' || ($toDate ?? '') !== '')
                        <a href="{{ route('dcr-management.area-performance') }}" class="btn btn-secondary btn-sm"><i class="fa fa-times"></i> Clear</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            <div class="p-3">
                <h5 class="mb-3">Area Performance Report</h5>
                <p class="text-muted small mb-3">Performance metrics by area for the selected period: report count, visit counts, distinct doctors/chemists/stockists, and total POB.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover w-100">
                    <thead>
                        <tr>
                            <th>Area</th>
                            <th class="text-center">Reports</th>
                            <th class="text-center">Doctor Calls</th>
                            <th class="text-center">Chemist Calls</th>
                            <th class="text-center">Stockist Calls</th>
                            <th class="text-center">Distinct Doctors</th>
                            <th class="text-center">Distinct Chemists</th>
                            <th class="text-center">Distinct Stockists</th>
                            <th class="text-right">Total POB</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows ?? [] as $row)
                            <tr>
                                <td>{{ $row['area_name'] }}</td>
                                <td class="text-center">{{ $row['report_count'] }}</td>
                                <td class="text-center">{{ $row['doctor_calls'] }}</td>
                                <td class="text-center">{{ $row['chemist_calls'] }}</td>
                                <td class="text-center">{{ $row['stockist_calls'] }}</td>
                                <td class="text-center">{{ $row['distinct_doctors'] }}</td>
                                <td class="text-center">{{ $row['distinct_chemists'] }}</td>
                                <td class="text-center">{{ $row['distinct_stockists'] }}</td>
                                <td class="text-right">{{ number_format($row['total_pob'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">@lang('messages.noRecordFound')</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-3">
                <a href="{{ route('dcr-management.index') }}" class="btn btn-secondary btn-sm">@lang('app.back') to DCR Reporting</a>
            </div>
        </div>
    </div>
@endsection
