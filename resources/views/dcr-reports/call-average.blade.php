@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-lg-flex d-md-flex d-block justify-content-between action-bar">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                <form method="GET" action="{{ route('dcr-management.call-average') }}" id="filter-form" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
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
                        @if(isset($areas) && $areas->isNotEmpty())
                        <div>
                            <select name="area" class="select-picker" data-live-search="true" title="Select Area" style="min-width: 160px;">
                                <option value="">-- All Areas --</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}" {{ (isset($selectedArea) && $selectedArea == $area->id) ? 'selected' : '' }}>{{ $area->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        @if(isset($regions) && $regions->isNotEmpty())
                        <div>
                            <select name="region" class="select-picker" data-live-search="true" title="Select Region" style="min-width: 160px;">
                                <option value="">-- All Regions --</option>
                                @foreach($regions as $region)
                                    <option value="{{ $region->id }}" {{ (isset($selectedRegion) && $selectedRegion == $region->id) ? 'selected' : '' }}>{{ $region->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        @if(isset($employees) && $employees->isNotEmpty())
                        <div>
                            <select name="employee_id" class="select-picker" data-live-search="true" data-html="true" title="Select Employee" style="min-width: 180px;">
                                <option value="all">-- All Employees --</option>
                                @foreach($employees as $emp)
                                    <x-user-option :user="$emp" :employeeSelect="true" :selected="isset($selectedEmployeeId) && $selectedEmployeeId == $emp->id" />
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
                    @if(($selectedHQ ?? '') !== '' || !empty($selectedArea) || !empty($selectedRegion) || (($selectedEmployeeId ?? '') !== '' && ($selectedEmployeeId ?? '') != 'all') || ($fromDate ?? '') !== '' || ($toDate ?? '') !== '')
                        <a href="{{ route('dcr-management.call-average') }}" class="btn btn-secondary btn-sm"><i class="fa fa-times"></i> Clear</a>
                    @endif
                </form>
            </div>
        </div>

        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            <div class="p-3">
                <h5 class="mb-3">Call Average Analysis</h5>
                <p class="text-muted small mb-3">Average doctor / chemist / stockist calls per working day per employee for the selected period.</p>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover w-100">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Employee ID</th>
                            <th>HQ</th>
                            <th>Area</th>
                            <th class="text-center">Working Days</th>
                            <th class="text-center">Doctor Calls</th>
                            <th class="text-center">Chemist Calls</th>
                            <th class="text-center">Stockist Calls</th>
                            <th class="text-center">Total Calls</th>
                            <th class="text-center">Call Average</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows ?? [] as $row)
                            <tr>
                                <td>{{ $row['employee_name'] }}</td>
                                <td>{{ $row['employee_id'] }}</td>
                                <td>{{ $row['hq'] }}</td>
                                <td>{{ $row['area'] }}</td>
                                <td class="text-center">{{ $row['working_days'] }}</td>
                                <td class="text-center">{{ $row['doctor_calls'] }}</td>
                                <td class="text-center">{{ $row['chemist_calls'] }}</td>
                                <td class="text-center">{{ $row['stockist_calls'] }}</td>
                                <td class="text-center">{{ $row['total_calls'] }}</td>
                                <td class="text-center">{{ $row['call_average'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center">@lang('messages.noRecordFound')</td>
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
