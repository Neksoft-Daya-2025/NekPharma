@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-flex justify-content-between action-bar mb-3">
            <div class="d-flex align-items-center">
                <h4 class="mb-0">Employee Leave Report</h4>
            </div>
            <div class="d-flex align-items-center">
                <a href="{{ route('leave-report.employee-leave-report.export') }}" class="btn btn-primary">
                    <i class="fa fa-file-excel"></i> Export to Excel
                </a>
            </div>
        </div>

        <x-cards.data>
            @php
                // Group report data by employee
                $groupedData = [];
                foreach ($reportData as $data) {
                    $employeeId = $data['employee_id'];
                    if (!isset($groupedData[$employeeId])) {
                        $groupedData[$employeeId] = [
                            'employee_name' => $data['employee_name'],
                            'leave_types' => []
                        ];
                    }
                    $groupedData[$employeeId]['leave_types'][] = $data;
                }
            @endphp
            
            @forelse($groupedData as $employeeId => $employeeData)
                @php
                    $firstLeave = $employeeData['leave_types'][0] ?? null;
                    $totalNoOfLeaves = 0;
                    $totalLeavesTaken = 0;
                    $totalRemainingLeaves = 0;
                    foreach ($employeeData['leave_types'] as $leaveData) {
                        $totalNoOfLeaves += $leaveData['no_of_leaves'];
                        $totalLeavesTaken += $leaveData['leaves_taken'];
                        $totalRemainingLeaves += $leaveData['remaining_leaves'];
                    }
                @endphp
                <div class="card w-100 rounded-0 border-0 mb-3">
                    <div class="card-horizontal">
                        <div class="card-body border-0 px-1 py-1">
                            <div class="d-flex flex-wrap">
                                <div class="col-md-12 mb-2">
                                    <h6 class="mb-0 font-weight-bold">{{ $employeeData['employee_name'] }}</h6>
                                    @if($firstLeave)
                                        <small class="text-muted">
                                            Employee Code: {{ $firstLeave['employee_code'] }} | 
                                            Designation: {{ $firstLeave['designation'] }} | 
                                            Department: {{ $firstLeave['department'] }}
                                        </small>
                                    @endif
                                </div>
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Leave Type</th>
                                                    <th>No of Leaves</th>
                                                    <th>Monthly Limit</th>
                                                    <th>Total Leaves Taken</th>
                                                    <th>Remaining Leaves</th>
                                                    <th>Over Utilized</th>
                                                    <th>Unused Leaves</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($employeeData['leave_types'] as $leaveData)
                                                    <tr>
                                                        <td>
                                                            <span class="badge badge-secondary" style="background-color: {{ $leaveData['leave_type_color'] }};">
                                                                {{ $leaveData['leave_type'] }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $leaveData['no_of_leaves'] }}</td>
                                                        <td>{{ $leaveData['monthly_limit'] > 0 ? $leaveData['monthly_limit'] : '--' }}</td>
                                                        <td>{{ $leaveData['leaves_taken'] }}</td>
                                                        <td>{{ $leaveData['remaining_leaves'] }}</td>
                                                        <td>
                                                            @if($leaveData['over_utilized'] > 0)
                                                                <span class="text-danger">{{ $leaveData['over_utilized'] }}</span>
                                                            @else
                                                                {{ $leaveData['over_utilized'] }}
                                                            @endif
                                                        </td>
                                                        <td>{{ $leaveData['unused_leaves'] }}</td>
                                                    </tr>
                                                @endforeach
                                                <tr class="font-weight-bold bg-light">
                                                    <td>TOTAL</td>
                                                    <td>{{ $totalNoOfLeaves }}</td>
                                                    <td>--</td>
                                                    <td>{{ $totalLeavesTaken }}</td>
                                                    <td>{{ $totalRemainingLeaves }}</td>
                                                    <td>--</td>
                                                    <td>--</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <x-cards.no-record icon="redo" :message="__('messages.noRecordFound')" />
            @endforelse
        </x-cards.data>
    </div>
@endsection


