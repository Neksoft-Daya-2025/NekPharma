@extends('layouts.app')

@push('styles')
<style>
    body { background-color: #f8f9fa; }
    .dcr-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    
    .status-pending {
        background-color: #fff3cd !important;
    }
    .status-approved {
        background-color: #d4edda !important;
    }
    .status-rejected {
        background-color: #f8d7da !important;
    }
</style>
@endpush

@section('content')
@php
    $reportingDescendantUserIds = $reportingDescendantUserIds ?? [];
@endphp
<div class="content-wrapper">
    <div class="d-block d-lg-flex d-md-flex justify-content-between action-bar">
        <div class="dcr-header w-100 text-center">
            <h3 class="mb-0"><i class="fa fa-check-circle"></i> Approve DCR Reports</h3>
            <p class="mb-0 mt-2">Review and approve submitted DCR reports</p>
        </div>
    </div>

    <div class="d-flex flex-column w-100 mt-4">
        <!-- Filters -->
        <div class="row mb-3">
            @if(user()->hasAdminLikeAccess() || ($employees->isNotEmpty() && !user()->hasAdminLikeAccess()))
                <div class="col-md-3">
                    <label>Select Employee</label>
                    <select class="form-control select-picker" id="employee-filter" data-live-search="true">
                        <option value="all" {{ $selectedEmployeeId == 'all' ? 'selected' : '' }}>-- All Employees @if(!user()->hasAdminLikeAccess())(Submitted to Me)@endif --</option>
                        @foreach($employees ?? [] as $employee)
                            <option value="{{ $employee['id'] }}" {{ $selectedEmployeeId == $employee['id'] ? 'selected' : '' }}>
                                {{ $employee['name'] }}
                                @if($employee['designation']) ({{ $employee['designation'] }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <!-- Approve All Button -->
        @php
            $canApproveAll = user()->permission('approve_dcr_reports') == 'all'
                || ($reports ?? collect())->contains(function ($r) use ($reportingDescendantUserIds) {
                    $pending = ($r->status == 'pending' || ! $r->status);
                    if (! $pending) {
                        return false;
                    }
                    if ($r->submitted_to == user()->id) {
                        return true;
                    }

                    return in_array((int) $r->user_id, array_map('intval', $reportingDescendantUserIds), true);
                });
        @endphp
        @if($canApproveAll)
            <div class="row mb-3">
                <div class="col-md-12 text-right">
                    <button type="button" class="btn btn-success" id="approve-all-btn">
                        <i class="fa fa-check-circle"></i> Approve All DCR Reports
                    </button>
                </div>
            </div>
        @endif

        <!-- DCR Reports with Visit Details -->
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            <!-- Tabs -->
            <ul class="nav nav-tabs px-3 pt-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#summary-tab">Summary</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#doctors-tab">Doctor Visits</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#chemists-tab">Chemist Visits</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#stockists-tab">Stockist Visits</a>
                </li>
            </ul>

            <div class="tab-content p-3">
                <!-- Summary Tab -->
                <div id="summary-tab" class="tab-pane active">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th style="width: 80px;">Date</th>
                                    <th style="width: 150px;">Submitted By</th>
                                    <th style="width: 120px;">Work Type</th>
                                    <th style="width: 150px;">Headquarter</th>
                                    <th style="width: 150px;">Station</th>
                                    <th style="width: 150px;">Work With</th>
                                    <th style="width: 120px;" class="text-center">Status</th>
                                    <th style="width: 200px;" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reports as $report)
                                    @php
                                        $statusClass = '';
                                        if ($report->status == 'approved') {
                                            $statusClass = 'status-approved';
                                        } elseif ($report->status == 'pending' || !$report->status) {
                                            $statusClass = 'status-pending';
                                        } elseif ($report->status == 'rejected') {
                                            $statusClass = 'status-rejected';
                                        }
                                    @endphp
                                    <tr class="{{ $statusClass }}" data-dcr-id="{{ $report->id }}">
                                        <td>{{ $report->report_date->translatedFormat(companyOrGlobalSetting()->date_format) }}</td>
                                        <td>
                                            @if($report->user)
                                                <strong class="text-primary">{{ $report->user->name }}</strong>
                                                @php
                                                    $empDetail = $report->user->employeeDetail ?? $report->user->employeeDetails;
                                                @endphp
                                                @if($empDetail && $empDetail->designation)
                                                    <br><small class="text-muted">{{ $empDetail->designation->name }}</small>
                                                @endif
                                                @if($empDetail && $empDetail->headquarter)
                                                    <br><small class="text-muted"><i class="fa fa-map-marker-alt"></i> {{ $empDetail->headquarter->name }}</small>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td><span class="badge badge-info">{{ $report->work_status ?: '-' }}</span></td>
                                        <td>{{ $report->headquarter ?: '-' }}</td>
                                        <td>{{ $report->station ?: '-' }}</td>
                                        <td>
                                            @if($report->work_with)
                                                @php
                                                    $workWithList = is_array($report->work_with) 
                                                        ? $report->work_with 
                                                        : explode(',', $report->work_with);
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
                                        <td class="text-center">
                                            @if($report->status == 'approved')
                                                <span class="badge badge-success">
                                                    <i class="fa fa-check-circle"></i> Approved
                                                </span>
                                            @elseif($report->status == 'rejected')
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
                                            @php
                                                $canApproveThis = user()->permission('approve_dcr_reports') === 'all' || $report->submitted_to == user()->id || in_array((int) $report->user_id, array_map('intval', $reportingDescendantUserIds), true);
                                            @endphp
                                            @if($canApproveThis && ($report->status == 'pending' || !$report->status))
                                                <button type="button" class="btn btn-sm btn-success approve-dcr-btn" data-id="{{ $report->id }}" title="Approve">
                                                    <i class="fa fa-check"></i> Approve
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger reject-dcr-btn" data-id="{{ $report->id }}" title="Reject">
                                                    <i class="fa fa-times"></i> Reject
                                                </button>
                                            @elseif($report->status == 'approved' || $report->status == 'rejected')
                                                @if($report->approvedBy)
                                                    <small class="text-muted">
                                                        By: {{ $report->approvedBy->name }}<br>
                                                        @if($report->approved_at)
                                                            {{ $report->approved_at->translatedFormat(companyOrGlobalSetting()->date_format . ' ' . companyOrGlobalSetting()->time_format) }}
                                                        @endif
                                                    </small>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fa fa-info-circle"></i> No DCR reports submitted for approval
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Doctors Tab -->
                <div id="doctors-tab" class="tab-pane fade">
                    <x-table class="table-hover border-0" headType="thead-light">
                        <x-slot name="thead">
                            <th>@lang('app.date')</th>
                            <th>Submitted By</th>
                            <th>Doctor</th>
                            <th>Speciality</th>
                            <th>HQ</th>
                            <th>Station</th>
                            <th>Products</th>
                            <th>Samples Unit</th>
                            <th>POB</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </x-slot>

                        @foreach($reports->where(function($r) { return $r->doctor_id != null || $r->doctorVisits->count() > 0; }) as $report)
                            @if($report->doctorVisits->count() > 0)
                                @foreach($report->doctorVisits as $visit)
                                    <tr>
                                        <td>{{ $report->report_date->format(company()->date_format) }}</td>
                                        <td>
                                            <strong>{{ $report->user->name ?? '-' }}</strong>
                                            @php
                                                $empDetail = $report->user->employeeDetail ?? $report->user->employeeDetails ?? null;
                                            @endphp
                                            @if($empDetail && $empDetail->designation)
                                                <br><small class="text-muted">{{ $empDetail->designation->name }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $visit->doctor->fullname ?? $visit->doctor_name ?? '-' }}</td>
                                        <td>{{ $visit->speciality ?? '-' }}</td>
                                        <td>{{ $report->headquarter }}</td>
                                        <td>{{ $report->station }}</td>
                                        <td>
                                            <small>
                                                @if($visit->product1) {{ $visit->product1 }}<br>@endif
                                                @if($visit->product2) {{ $visit->product2 }}<br>@endif
                                                @if($visit->product3) {{ $visit->product3 }}@endif
                                            </small>
                                        </td>
                                        <td>
                                            <small>
                                                @if($visit->product1) {{ $visit->samples_unit1 ?? 0 }}<br>@endif
                                                @if($visit->product2) {{ $visit->samples_unit2 ?? 0 }}<br>@endif
                                                @if($visit->product3) {{ $visit->samples_unit3 ?? 0 }}@endif
                                            </small>
                                        </td>
                                        <td>{{ $visit->pob ?? '-' }}</td>
                                        <td>
                                            @if($report->status == 'approved')
                                                <span class="badge badge-success">Approved</span>
                                            @elseif($report->status == 'rejected')
                                                <span class="badge badge-danger">Rejected</span>
                                            @else
                                                <span class="badge badge-warning">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $canApproveThis = user()->permission('approve_dcr_reports') === 'all' || $report->submitted_to == user()->id || in_array((int) $report->user_id, array_map('intval', $reportingDescendantUserIds), true);
                                            @endphp
                                            @if($canApproveThis && ($report->status == 'pending' || !$report->status))
                                                <button type="button" class="btn btn-sm btn-success approve-dcr-btn" data-id="{{ $report->id }}" title="Approve">
                                                    <i class="fa fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger reject-dcr-btn" data-id="{{ $report->id }}" title="Reject">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    </x-table>
                </div>

                <!-- Chemists Tab -->
                <div id="chemists-tab" class="tab-pane fade">
                    <x-table class="table-hover border-0" headType="thead-light">
                        <x-slot name="thead">
                            <th>@lang('app.date')</th>
                            <th>Submitted By</th>
                            <th>Chemist</th>
                            <th>Station</th>
                            <th>RCPA</th>
                            <th>POB Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </x-slot>

                        @foreach($reports->where(function($r) { return $r->chemist_id != null || $r->chemistVisits->count() > 0; }) as $report)
                            @if($report->chemistVisits->count() > 0)
                                @foreach($report->chemistVisits as $visit)
                                    <tr>
                                        <td>{{ $report->report_date->format(company()->date_format) }}</td>
                                        <td>
                                            <strong>{{ $report->user->name ?? '-' }}</strong>
                                            @php
                                                $empDetail = $report->user->employeeDetail ?? $report->user->employeeDetails ?? null;
                                            @endphp
                                            @if($empDetail && $empDetail->designation)
                                                <br><small class="text-muted">{{ $empDetail->designation->name }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $visit->chemist->shopname ?? $visit->chemist_name ?? '-' }}</td>
                                        <td>{{ $visit->station ?? '-' }}</td>
                                        <td>
                                            <small>
                                                @if($visit->rcpa1) {{ $visit->rcpa1 }}<br>@endif
                                                @if($visit->rcpa2) {{ $visit->rcpa2 }}<br>@endif
                                                @if($visit->rcpa3) {{ $visit->rcpa3 }}<br>@endif
                                                @if($visit->rcpa4) {{ $visit->rcpa4 }}@endif
                                            </small>
                                        </td>
                                        <td>
                                            @if($visit->pob_amount1) {{ $visit->pob_amount1 }}<br>@endif
                                            @if($visit->pob_amount2) {{ $visit->pob_amount2 }}<br>@endif
                                            @if($visit->pob_amount3) {{ $visit->pob_amount3 }}<br>@endif
                                            @if($visit->pob_amount4) {{ $visit->pob_amount4 }}@endif
                                        </td>
                                        <td>
                                            @if($report->status == 'approved')
                                                <span class="badge badge-success">Approved</span>
                                            @elseif($report->status == 'rejected')
                                                <span class="badge badge-danger">Rejected</span>
                                            @else
                                                <span class="badge badge-warning">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $canApproveThis = user()->permission('approve_dcr_reports') === 'all' || $report->submitted_to == user()->id || in_array((int) $report->user_id, array_map('intval', $reportingDescendantUserIds), true);
                                            @endphp
                                            @if($canApproveThis && ($report->status == 'pending' || !$report->status))
                                                <button type="button" class="btn btn-sm btn-success approve-dcr-btn" data-id="{{ $report->id }}" title="Approve">
                                                    <i class="fa fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger reject-dcr-btn" data-id="{{ $report->id }}" title="Reject">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    </x-table>
                </div>

                <!-- Stockists Tab -->
                <div id="stockists-tab" class="tab-pane fade">
                    <x-table class="table-hover border-0" headType="thead-light">
                        <x-slot name="thead">
                            <th>@lang('app.date')</th>
                            <th>Submitted By</th>
                            <th>Stockist</th>
                            <th>Station</th>
                            <th>POB Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </x-slot>

                        @foreach($reports->where(function($r) { return $r->stockist_id != null || $r->stockistVisits->count() > 0; }) as $report)
                            @if($report->stockistVisits->count() > 0)
                                @foreach($report->stockistVisits as $visit)
                                    <tr>
                                        <td>{{ $report->report_date->format(company()->date_format) }}</td>
                                        <td>
                                            <strong>{{ $report->user->name ?? '-' }}</strong>
                                            @php
                                                $empDetail = $report->user->employeeDetail ?? $report->user->employeeDetails ?? null;
                                            @endphp
                                            @if($empDetail && $empDetail->designation)
                                                <br><small class="text-muted">{{ $empDetail->designation->name }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $visit->stockist->shopname ?? $visit->stockist_name ?? '-' }}</td>
                                        <td>{{ $visit->station ?? '-' }}</td>
                                        <td>{{ $visit->pob_amount ?? '-' }}</td>
                                        <td>
                                            @if($report->status == 'approved')
                                                <span class="badge badge-success">Approved</span>
                                            @elseif($report->status == 'rejected')
                                                <span class="badge badge-danger">Rejected</span>
                                            @else
                                                <span class="badge badge-warning">Pending</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $canApproveThis = user()->permission('approve_dcr_reports') === 'all' || $report->submitted_to == user()->id || in_array((int) $report->user_id, array_map('intval', $reportingDescendantUserIds), true);
                                            @endphp
                                            @if($canApproveThis && ($report->status == 'pending' || !$report->status))
                                                <button type="button" class="btn btn-sm btn-success approve-dcr-btn" data-id="{{ $report->id }}" title="Approve">
                                                    <i class="fa fa-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger reject-dcr-btn" data-id="{{ $report->id }}" title="Reject">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    </x-table>
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
    
    // Employee filter change
    $('#employee-filter').on('changed.bs.select', function() {
        const employeeId = $(this).val();
        const url = new URL(window.location.href);
        if (employeeId && employeeId !== 'all') {
            url.searchParams.set('employee_id', employeeId);
        } else {
            url.searchParams.delete('employee_id');
        }
        url.searchParams.set('mode', 'approve');
        window.location.href = url.toString();
    });
    
    // Approve single DCR
    $(document).on('click', '.approve-dcr-btn', function() {
        const dcrId = $(this).data('id');
        const row = $(this).closest('tr');
        
        Swal.fire({
            title: 'Approve DCR Report?',
            text: 'Are you sure you want to approve this DCR report?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Approve',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.easyAjax({
                    url: "{{ route('dcr-management.approve', ':id') }}".replace(':id', dcrId),
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        $.showToastr(response.message, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    },
                    error: function(response) {
                        $.showToastr(response.responseJSON?.message || 'Error approving DCR report', 'error');
                    }
                });
            }
        });
    });
    
    // Reject single DCR
    $(document).on('click', '.reject-dcr-btn', function() {
        const dcrId = $(this).data('id');
        
        Swal.fire({
            title: 'Reject DCR Report?',
            text: 'Are you sure you want to reject this DCR report?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Reject',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.easyAjax({
                    url: "{{ route('dcr-management.reject', ':id') }}".replace(':id', dcrId),
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        $.showToastr(response.message, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    },
                    error: function(response) {
                        $.showToastr(response.responseJSON?.message || 'Error rejecting DCR report', 'error');
                    }
                });
            }
        });
    });
    
    // Approve All DCRs
    $('#approve-all-btn').on('click', function() {
        const pendingDcrIds = [];
        $('tr[data-dcr-id]').each(function() {
            const status = $(this).find('.badge').text().trim();
            if (status.includes('Pending')) {
                pendingDcrIds.push($(this).data('dcr-id'));
            }
        });
        
        if (pendingDcrIds.length === 0) {
            $.showToastr('No pending DCR reports to approve', 'info');
            return;
        }
        
        Swal.fire({
            title: 'Approve All DCR Reports?',
            text: `Are you sure you want to approve ${pendingDcrIds.length} DCR report(s)?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Approve All',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.easyAjax({
                    url: "{{ route('dcr-management.approve-all') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        dcr_ids: pendingDcrIds
                    },
                    success: function(response) {
                        $.showToastr(response.message, 'success');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    },
                    error: function(response) {
                        $.showToastr(response.responseJSON?.message || 'Error approving DCR reports', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush

