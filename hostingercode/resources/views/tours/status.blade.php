@extends('layouts.app')

@push('styles')
<style>
    .status-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    .status-table thead {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .status-table thead th {
        color: white;
        font-weight: 600;
        border: none;
        padding: 15px 10px;
        font-size: 13px;
        text-align: left;
    }
    .status-table thead th.text-center {
        text-align: center;
    }
    .status-table tbody td {
        padding: 12px 10px;
        vertical-align: middle;
        font-size: 13px;
    }
    .status-approved {
        background-color: #d4edda !important;
    }
    .status-pending {
        background-color: #fff3cd !important;
    }
    .status-rejected {
        background-color: #f8d7da !important;
    }
</style>
@endpush

@section('content')
<div class="content-wrapper">
    <div class="d-block d-lg-flex d-md-flex justify-content-between action-bar">
        <div class="status-header w-100 text-center">
            <h3 class="mb-0">
                <i class="fa fa-list-alt"></i> Tour Plan Status
                @if(user()->hasRole('admin') && $selectedEmployeeId && $selectedEmployeeId != 'all')
                    @php
                        $selectedEmployee = $employees->firstWhere('id', $selectedEmployeeId);
                    @endphp
                    @if($selectedEmployee)
                        - {{ $selectedEmployee->name }}
                    @endif
                @endif
            </h3>
            <p class="mb-0 mt-2">
                @if(user()->hasRole('admin') || user()->permission('view_tours') == 'all')
                    View and track tour plan approval status for {{ \Carbon\Carbon::parse($selectedMonth)->format('F Y') }}
                @else
                    Check approval status of your submitted tour plans for {{ \Carbon\Carbon::parse($selectedMonth)->format('F Y') }}
                @endif
            </p>
        </div>
    </div>

    <div class="d-flex flex-column w-100 mt-4">
        <!-- Filter Section -->
        <form method="GET" action="{{ route('tours.status') }}" id="filter-form">
            <div class="row mb-3">
                <div class="col-md-{{ user()->hasRole('admin') || user()->permission('view_tours') == 'all' ? '3' : '4' }}">
                    <label for="month-filter" class="my-3 f-14 text-dark-grey mb-12 text-capitalize">
                        <i class="fa fa-calendar"></i> Select Month
                    </label>
                    <input type="month" class="form-control height-35 f-14" name="month" id="month-filter" value="{{ $selectedMonth }}" required>
                    <small class="form-text text-muted">View tours for selected month</small>
                </div>
                
                @if(user()->hasRole('admin') || user()->permission('view_tours') == 'all')
                    <div class="col-md-3">
                        <label for="employee-filter" class="my-3 f-14 text-dark-grey mb-12 text-capitalize">
                            <i class="fa fa-user"></i> Select Employee
                        </label>
                        <select class="form-control height-35 f-14 select-picker" name="employee_id" id="employee-filter" data-live-search="true" data-html="true">
                            <option value="all" {{ $selectedEmployeeId == 'all' ? 'selected' : '' }}>-- All Employees --</option>
                            @foreach($employees as $emp)
                                <x-user-option :user="$emp" :employeeSelect="true" :selected="$selectedEmployeeId == $emp->id" />
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Filter by specific employee</small>
                    </div>
                @endif
                
                <div class="col-md-{{ user()->hasRole('admin') || user()->permission('view_tours') == 'all' ? '3' : '4' }}">
                    <label for="status-filter" class="my-3 f-14 text-dark-grey mb-12 text-capitalize">
                        <i class="fa fa-info-circle"></i> Filter by Status
                    </label>
                    <select class="form-control height-35 f-14 select-picker" id="status-filter">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <small class="form-text text-muted">Client-side status filter</small>
                </div>
                
                <div class="col-md-3 d-flex align-items-end pb-3">
                    <button type="submit" class="btn btn-primary btn-sm mr-2">
                        <i class="fa fa-search"></i> Load Tours
                    </button>
                    <x-forms.link-primary :link="route('tours.create')" class="mr-2" icon="plus">
                        Create Tour
                    </x-forms.link-primary>
                </div>
            </div>
        </form>
        
        <!-- Summary Status Bar -->
        @if($totalTours > 0)
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div class="card-body py-3">
                        <div class="row text-center">
                            <div class="col-md-2">
                                <h4 class="mb-1"><i class="fa fa-calendar"></i> {{ $totalTours }}</h4>
                                <small>Total Tours</small>
                            </div>
                            <div class="col-md-2">
                                <h4 class="mb-1"><i class="fa fa-check-circle"></i> {{ $approvedTours }}</h4>
                                <small>Approved</small>
                            </div>
                            <div class="col-md-2">
                                <h4 class="mb-1"><i class="fa fa-clock"></i> {{ $pendingTours }}</h4>
                                <small>Pending</small>
                            </div>
                            <div class="col-md-2">
                                <h4 class="mb-1"><i class="fa fa-times-circle"></i> {{ $rejectedTours }}</h4>
                                <small>Rejected</small>
                            </div>
                            <div class="col-md-2">
                                <h4 class="mb-1"><i class="fa fa-percentage"></i> {{ $approvalRate }}%</h4>
                                <small>Approval Rate</small>
                            </div>
                            <div class="col-md-2">
                                <h4 class="mb-1"><i class="fa fa-calendar-alt"></i> {{ \Carbon\Carbon::parse($selectedMonth)->format('M Y') }}</h4>
                                <small>Selected Month</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> <strong>No tours found</strong> for {{ \Carbon\Carbon::parse($selectedMonth)->format('F Y') }}
            @if(user()->hasRole('admin') && $selectedEmployeeId != 'all')
                for the selected employee
            @endif
        </div>
        @endif

        <!-- Status Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover status-table mb-0">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Date</th>
                                <th style="width: 100px;">Day</th>
                                <th style="width: 200px;">Employee</th>
                                <th style="width: 150px;">Headquarter</th>
                                <th style="width: 130px;">Work Type</th>
                                <th style="width: 180px;">Station(s)</th>
                                <th style="width: 150px;">Work With</th>
                                <th style="width: 200px;">Remark</th>
                                <th style="width: 120px;" class="text-center">Status</th>
                                <th style="width: 130px;" class="text-center">Approved By</th>
                                <th style="width: 140px;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="status-body">
                            @forelse($tours as $tour)
                                @php
                                    $statusClass = '';
                                    if ($tour->status == 'approved') {
                                        $statusClass = 'status-approved';
                                    } elseif ($tour->status == 'pending' || !$tour->status) {
                                        $statusClass = 'status-pending';
                                    } elseif ($tour->status == 'rejected') {
                                        $statusClass = 'status-rejected';
                                    }
                                @endphp
                                <tr class="{{ $statusClass }}" data-status="{{ $tour->status ?? 'pending' }}">
                                    <td>{{ $tour->date->translatedFormat(companyOrGlobalSetting()->date_format) }}</td>
                                    <td><strong>{{ $tour->day }}</strong></td>
                                    <td>
                                        @if($tour->user)
                                            @php
                                                $empDetail = $tour->user->employeeDetail ?? $tour->user->employeeDetails;
                                            @endphp
                                            <div>
                                                <strong class="text-dark">{{ $tour->user->name }}</strong>
                                            </div>
                                            @if($empDetail && $empDetail->designation)
                                                <small class="text-muted d-block">{{ $empDetail->designation->name }}</small>
                                            @endif
                                            @if($empDetail && $empDetail->headquarter)
                                                <small class="text-muted d-block"><i class="fa fa-map-marker-alt"></i> {{ $empDetail->headquarter->name }}</small>
                                            @endif
                                        @elseif($tour->user_id)
                                            <span class="text-warning" title="{{ __('User record missing') }}">User #{{ $tour->user_id }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($tour->headquarter)->name ?? '-' }}</td>
                                    <td><span class="badge badge-info">{{ $tour->work_status ?: '-' }}</span></td>
                                    <td><small>{{ $tour->station ?: '-' }}</small></td>
                                    <td>
                                        @if($tour->work_with)
                                            @php
                                                // work_with is now stored as comma-separated designation names
                                                $workWithList = is_array($tour->work_with) 
                                                    ? $tour->work_with 
                                                    : explode(',', $tour->work_with);
                                                $workWithList = array_map('trim', $workWithList);
                                                $workWithList = array_filter($workWithList);
                                            @endphp
                                            @if(!empty($workWithList))
                                                @foreach($workWithList as $designation)
                                                    <span class="badge badge-secondary mr-1 mb-1">{{ trim($designation) }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td><small>{{ Str::limit($tour->remark, 40) ?: '-' }}</small></td>
                                    <td class="text-center">
                                        @if($tour->status == 'approved')
                                            <span class="badge badge-success">
                                                <i class="fa fa-check-circle"></i> Approved
                                            </span>
                                        @elseif($tour->status == 'rejected')
                                            <span class="badge badge-danger">
                                                <i class="fa fa-times-circle"></i> Rejected
                                            </span>
                                        @else
                                            <span class="badge badge-warning">
                                                <i class="fa fa-clock"></i> Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($tour->approvedBy)
                                            <div>
                                                <strong>{{ $tour->approvedBy->name }}</strong>
                                            </div>
                                            @if($tour->approved_at)
                                                <small class="text-muted d-block" style="font-size: 10px;">
                                                    <i class="fa fa-calendar-check"></i> {{ $tour->approved_at->translatedFormat(companyOrGlobalSetting()->date_format) }}
                                                </small>
                                                <small class="text-muted d-block" style="font-size: 10px;">
                                                    <i class="fa fa-clock"></i> {{ $tour->approved_at->translatedFormat(companyOrGlobalSetting()->time_format) }}
                                                </small>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $canApproveThis = user()->permission('approve_tours') == 'all' || $tour->submitted_to == user()->id;
                                        @endphp
                                        @if(($tour->status == 'pending' || !$tour->status) && $canApproveThis)
                                            <form action="{{ route('tours.approve', $tour->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success btn-xs" title="Approve">
                                                    <i class="fa fa-check"></i> Approve
                                                </button>
                                            </form>
                                            <form action="{{ route('tours.reject', $tour->id) }}" method="POST" class="d-inline ml-1">
                                                @csrf
                                                <button type="submit" class="btn btn-warning btn-xs" title="Reject" onclick="return confirm('Reject this tour?');">
                                                    <i class="fa fa-times"></i> Reject
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fa fa-calendar-times fa-3x mb-3 d-block"></i>
                                            <h5>No tour plans submitted yet</h5>
                                            <p>Go to <a href="{{ route('tours.create') }}">Create Tour Plan</a> to submit your tours</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.select-picker').selectpicker();
    
    // Client-side status filter (filters already loaded tours)
    $('#status-filter').on('change', function() {
        const selectedStatus = $(this).val();
        
        $('#status-body tr').each(function() {
            const rowStatus = $(this).data('status');
            
            if (!selectedStatus || rowStatus === selectedStatus) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
        
        // Update visible count
        updateVisibleCount();
    });
    
    // Update count of visible tours
    function updateVisibleCount() {
        const visibleRows = $('#status-body tr:visible').length;
        const totalRows = $('#status-body tr').length;
        
        if (visibleRows === 0 && totalRows > 0) {
            if ($('#no-results-row').length === 0) {
                $('#status-body').append(`
                    <tr id="no-results-row">
                        <td colspan="11" class="text-center py-3">
                            <i class="fa fa-filter"></i> No tours match the selected status filter
                        </td>
                    </tr>
                `);
            }
        } else {
            $('#no-results-row').remove();
        }
    }
    
    // Month change - submit form automatically
    $('#month-filter').on('change', function() {
        $('#filter-form').submit();
    });
    
    // Employee filter change - submit form automatically (Admin only)
    $('#employee-filter').on('change', function() {
        $('#filter-form').submit();
    });
    
    // Highlight current month
    const currentMonth = '{{ now()->format("Y-m") }}';
    const selectedMonth = '{{ $selectedMonth }}';
    
    if (currentMonth === selectedMonth) {
        $('#month-filter').addClass('border-primary');
    }
});
</script>
@endpush

