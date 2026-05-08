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
    .expense-group {
        margin-bottom: 2rem;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        overflow: hidden;
    }
    .expense-group-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem;
        font-weight: bold;
    }
</style>
@endpush

@section('content')
<div class="content-wrapper">
    <div class="d-block d-lg-flex d-md-flex justify-content-between action-bar">
        <div class="status-header w-100 text-center">
            <h3 class="mb-0">
                <i class="fa fa-file-invoice-dollar"></i> Expense Statement Status
                @if(user()->permission('view_expenses') == 'all' && $selectedEmployeeId && $selectedEmployeeId != 'all')
                    @php
                        $selectedEmployee = $employees->firstWhere('id', $selectedEmployeeId);
                    @endphp
                    @if($selectedEmployee)
                        - {{ $selectedEmployee->name }}
                    @endif
                @endif
            </h3>
            <p class="mb-0 mt-2">
                @if(user()->permission('view_expenses') == 'all')
                    View and track expense statement approval status for {{ \Carbon\Carbon::parse($selectedMonth)->format('F Y') }}
                @else
                    Check approval status of your submitted expense statements for {{ \Carbon\Carbon::parse($selectedMonth)->format('F Y') }}
                @endif
            </p>
        </div>
    </div>

    <div class="d-flex flex-column w-100 mt-4">
        <!-- Filter Section -->
        <form method="GET" action="{{ route('expenses.status') }}" id="filter-form">
            <div class="row mb-3">
                <div class="col-md-{{ user()->permission('view_expenses') == 'all' ? '3' : '4' }}">
                    <label for="month-filter" class="my-3 f-14 text-dark-grey mb-12 text-capitalize">
                        <i class="fa fa-calendar"></i> Select Month
                    </label>
                    <input type="month" class="form-control height-35 f-14" name="month" id="month-filter" value="{{ $selectedMonth }}" required>
                    <small class="form-text text-muted">View expenses for selected month</small>
                </div>
                
                @if(user()->permission('view_expenses') == 'all')
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
                
                <div class="col-md-{{ user()->permission('view_expenses') == 'all' ? '3' : '4' }}">
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
                        <i class="fa fa-search"></i> Load Expenses
                    </button>
                    <x-forms.link-primary :link="route('expenses.create')" class="mr-2" icon="plus">
                        Create Expense
                    </x-forms.link-primary>
                </div>
            </div>
        </form>
        
        <!-- Summary Status Bar -->
        @php
            $totalExpenses = $expenses->count();
            $approvedExpenses = $expenses->where('status', 'approved')->count();
            $pendingExpenses = $expenses->where('status', 'pending')->count();
            $approvalRate = $totalExpenses > 0 ? round(($approvedExpenses / $totalExpenses) * 100, 1) : 0;
            $totalAmount = $expenses->sum('price');
            $approvedAmount = $expenses->where('status', 'approved')->sum('price');
            $pendingAmount = $expenses->where('status', 'pending')->sum('price');
        @endphp
        
        @if($totalExpenses > 0)
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div class="card-body py-3">
                        <div class="row text-center">
                            <div class="col-md-2">
                                <h4 class="mb-1"><i class="fa fa-file-invoice"></i> {{ $totalExpenses }}</h4>
                                <small>Total Expenses</small>
                            </div>
                            <div class="col-md-2">
                                <h4 class="mb-1"><i class="fa fa-check-circle"></i> {{ $approvedExpenses }}</h4>
                                <small>Approved</small>
                            </div>
                            <div class="col-md-2">
                                <h4 class="mb-1"><i class="fa fa-clock"></i> {{ $pendingExpenses }}</h4>
                                <small>Pending</small>
                            </div>
                            <div class="col-md-2">
                                <h4 class="mb-1"><i class="fa fa-percentage"></i> {{ $approvalRate }}%</h4>
                                <small>Approval Rate</small>
                            </div>
                            <div class="col-md-2">
                                <h4 class="mb-1"><i class="fa fa-rupee-sign"></i> {{ number_format($totalAmount, 2) }}</h4>
                                <small>Total Amount</small>
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
            <i class="fa fa-info-circle"></i> <strong>No expenses found</strong> for {{ \Carbon\Carbon::parse($selectedMonth)->format('F Y') }}
            @if(user()->permission('view_expenses') == 'all' && $selectedEmployeeId != 'all')
                for the selected employee
            @endif
        </div>
        @endif

        <!-- Status Table - Grouped by Employee and Month -->
        @foreach($groupedExpenses as $groupKey => $groupExpenses)
            @php
                $firstExpense = $groupExpenses->first();
                $employee = $firstExpense->user;
                $month = $firstExpense->expense_month;
                $groupTotal = $groupExpenses->sum('price');
                $groupApproved = $groupExpenses->where('status', 'approved')->sum('price');
                $groupPending = $groupExpenses->where('status', 'pending')->sum('price');
            @endphp
            <div class="expense-group">
                <div class="expense-group-header">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0">
                                <i class="fa fa-user"></i> {{ $employee->name }}
                                @if($employee->employeeDetail && $employee->employeeDetail->designation)
                                    <small>({{ $employee->employeeDetail->designation->name }})</small>
                                @endif
                            </h5>
                        </div>
                        <div class="col-md-6 text-right">
                            <span class="badge badge-light mr-2">Month: {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</span>
                            <span class="badge badge-success mr-2">Total: ₹{{ number_format($groupTotal, 2) }}</span>
                            @if($groupExpenses->where('status', 'pending')->count() > 0 && (user()->permission('approve_expenses') == 'all' || $groupExpenses->first()->submitted_to == user()->id))
                                <button class="btn btn-sm btn-success approve-group-btn" data-expense-ids="{{ $groupExpenses->where('status', 'pending')->pluck('id')->toJson() }}">
                                    <i class="fa fa-check"></i> Approve All
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover status-table mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 100px;">Date</th>
                                    <th style="width: 100px;">Day</th>
                                    <th style="width: 120px;">Town Worked</th>
                                    <th style="width: 100px;">Km</th>
                                    <th style="width: 100px;">Fare</th>
                                    <th style="width: 120px;">Allowances</th>
                                    <th style="width: 100px;">Fixed</th>
                                    <th style="width: 100px;">Other</th>
                                    <th style="width: 100px;">Total</th>
                                    <th style="width: 150px;" class="text-center">Submitted To</th>
                                    <th style="width: 120px;" class="text-center">Status</th>
                                    <th style="width: 130px;" class="text-center">Approved By</th>
                                    <th style="width: 100px;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groupExpenses as $expense)
                                    @php
                                        $statusClass = '';
                                        if ($expense->status == 'approved') {
                                            $statusClass = 'status-approved';
                                        } elseif ($expense->status == 'pending') {
                                            $statusClass = 'status-pending';
                                        } elseif ($expense->status == 'rejected') {
                                            $statusClass = 'status-rejected';
                                        }
                                    @endphp
                                    <tr class="{{ $statusClass }}" data-status="{{ $expense->status ?? 'pending' }}" data-expense-id="{{ $expense->id }}">
                                        <td>{{ $expense->purchase_date->translatedFormat(companyOrGlobalSetting()->date_format) }}</td>
                                        <td><strong>{{ $expense->day }}</strong></td>
                                        <td><small>{{ $expense->town_worked ?: '-' }}</small></td>
                                        <td>{{ number_format($expense->km ?? 0, 2) }}</td>
                                        <td>₹{{ number_format($expense->fare_rs ?? 0, 2) }}</td>
                                        <td>
                                            <small>
                                                HQ: ₹{{ number_format($expense->daily_allowance_hq_rs ?? 0, 2) }}<br>
                                                Ex: ₹{{ number_format($expense->daily_allowance_ex_rs ?? 0, 2) }}<br>
                                                O/S: ₹{{ number_format($expense->daily_allowance_os_rs ?? 0, 2) }}
                                            </small>
                                        </td>
                                        <td>₹{{ number_format($expense->fixed_expenses ?? 0, 2) }}</td>
                                        <td>₹{{ number_format($expense->other_expenses ?? 0, 2) }}</td>
                                        <td><strong>₹{{ number_format($expense->price, 2) }}</strong></td>
                                        <td class="text-center">
                                            @if($expense->submittedTo)
                                                <div>
                                                    <strong class="text-primary">{{ $expense->submittedTo->name }}</strong>
                                                </div>
                                                @if($expense->submittedTo->employeeDetail && $expense->submittedTo->employeeDetail->designation)
                                                    <small class="text-muted d-block" style="font-size: 10px;">
                                                        {{ $expense->submittedTo->employeeDetail->designation->name }}
                                                    </small>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($expense->status == 'approved')
                                                <span class="badge badge-success">
                                                    <i class="fa fa-check-circle"></i> Approved
                                                </span>
                                            @elseif($expense->status == 'rejected')
                                                <span class="badge badge-danger">
                                                    <i class="fa fa-times-circle"></i> Rejected
                                                </span>
                                                @if($expense->description)
                                                    <div class="small text-left mt-1 text-danger" style="max-width: 200px;" title="{{ $expense->description }}">
                                                        <strong>Reason:</strong> {{ \Str::limit($expense->description, 60) }}
                                                    </div>
                                                @endif
                                            @else
                                                <span class="badge badge-warning">
                                                    <i class="fa fa-clock"></i> Pending
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($expense->approver)
                                                <div>
                                                    <strong>{{ $expense->approver->name }}</strong>
                                                </div>
                                                @if($expense->approved_at)
                                                    @php
                                                        $approvedAt = is_string($expense->approved_at) ? \Carbon\Carbon::parse($expense->approved_at) : $expense->approved_at;
                                                    @endphp
                                                    <small class="text-muted d-block" style="font-size: 10px;">
                                                        <i class="fa fa-calendar-check"></i> {{ $approvedAt->translatedFormat(companyOrGlobalSetting()->date_format) }}
                                                    </small>
                                                    <small class="text-muted d-block" style="font-size: 10px;">
                                                        <i class="fa fa-clock"></i> {{ $approvedAt->translatedFormat(companyOrGlobalSetting()->time_format) }}
                                                    </small>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                @if($expense->status == 'pending' && (user()->permission('approve_expenses') == 'all' || $expense->submitted_to == user()->id))
                                                    <button class="btn btn-sm btn-success approve-btn" data-expense-id="{{ $expense->id }}">
                                                        <i class="fa fa-check"></i> Approve
                                                    </button>
                                                @endif
                                                
                                                @if($expense->status == 'rejected' && $expense->user_id == user()->id)
                                                    <a href="{{ route('expenses.create') }}?month={{ \Carbon\Carbon::parse($expense->purchase_date)->format('n') }}&year={{ \Carbon\Carbon::parse($expense->purchase_date)->format('Y') }}" class="btn btn-sm btn-primary" title="Correct and resubmit">
                                                        <i class="fa fa-edit"></i> Correct &amp; Resubmit
                                                    </a>
                                                @endif
                                                
                                                @php
                                                    $deletePermission = user()->permission('delete_expenses');
                                                    $isPendingOrApproved = in_array($expense->status, ['pending', 'approved']);
                                                    $canDelete = false;
                                                    
                                                    if ($isPendingOrApproved) {
                                                        // Only admin can delete pending/approved expenses
                                                        $canDelete = $deletePermission == 'all';
                                                    } else {
                                                        // Normal delete permission check for other expenses
                                                        $canDelete = $deletePermission == 'all' || ($deletePermission == 'added' && user()->id == $expense->added_by);
                                                    }
                                                @endphp
                                                
                                                @if($canDelete)
                                                    <button class="btn btn-sm btn-danger delete-expense-btn" data-expense-id="{{ $expense->id }}" title="Delete Expense">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                @endif
                                                
                                                @if(!$canDelete && $expense->status != 'rejected' && ($expense->status != 'pending' || (user()->permission('approve_expenses') != 'all' && $expense->submitted_to != user()->id)))
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                <tr class="bg-light font-weight-bold">
                                    <td colspan="8" class="text-right"><strong>Group Total:</strong></td>
                                    <td><strong>₹{{ number_format($groupTotal, 2) }}</strong></td>
                                    <td colspan="4"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
        
        @if($groupedExpenses->isEmpty())
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="text-muted">
                        <i class="fa fa-file-invoice fa-3x mb-3 d-block"></i>
                        <h5>No expense statements submitted yet</h5>
                        <p>Go to <a href="{{ route('expenses.create') }}">Create Expense</a> to submit your expense statement</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('.select-picker').selectpicker();
    
    // Client-side status filter
    $('#status-filter').on('change', function() {
        const selectedStatus = $(this).val();
        
        $('.expense-group').each(function() {
            const group = $(this);
            let hasVisibleRows = false;
            
            group.find('tbody tr[data-status]').each(function() {
                const rowStatus = $(this).data('status');
                
                if (!selectedStatus || rowStatus === selectedStatus) {
                    $(this).show();
                    hasVisibleRows = true;
                } else {
                    $(this).hide();
                }
            });
            
            // Show/hide group based on visible rows
            if (hasVisibleRows) {
                group.show();
            } else {
                group.hide();
            }
        });
    });
    
    // Approve single expense
    $(document).on('click', '.approve-btn', function() {
        const expenseId = $(this).data('expense-id');
        const btn = $(this);
        
        Swal.fire({
            title: 'Approve Expense',
            text: 'Are you sure you want to approve this expense?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Yes, Approve',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.easyAjax({
                    url: "{{ route('expenses.approve', ['id' => ':id']) }}".replace(':id', expenseId),
                    type: "POST",
                    data: {
                        '_token': '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire('Approved!', 'Expense approved successfully', 'success');
                        window.location.reload();
                    },
                    error: function(response) {
                        Swal.fire('Error', response.responseJSON.message || 'Failed to approve expense', 'error');
                    }
                });
            }
        });
    });
    
    // Approve all expenses in group
    $(document).on('click', '.approve-group-btn', function() {
        const expenseIds = $(this).data('expense-ids');
        const btn = $(this);
        
        Swal.fire({
            title: 'Approve All Expenses',
            text: `Are you sure you want to approve all ${expenseIds.length} pending expense(s) in this group?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Yes, Approve All',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.easyAjax({
                    url: "{{ route('expenses.approve-all') }}",
                    type: "POST",
                    data: {
                        '_token': '{{ csrf_token() }}',
                        'expense_ids': expenseIds
                    },
                    success: function(response) {
                        Swal.fire('Approved!', `${expenseIds.length} expense(s) approved successfully`, 'success');
                        window.location.reload();
                    },
                    error: function(response) {
                        Swal.fire('Error', response.responseJSON.message || 'Failed to approve expenses', 'error');
                    }
                });
            }
        });
    });
    
    // Delete expense
    $(document).on('click', '.delete-expense-btn', function() {
        const expenseId = $(this).data('expense-id');
        const btn = $(this);
        
        Swal.fire({
            title: "@lang('messages.sweetAlertTitle')",
            text: "@lang('messages.recoverRecord')",
            icon: 'warning',
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonText: "@lang('messages.confirmDelete')",
            cancelButtonText: "@lang('app.cancel')",
            customClass: {
                confirmButton: 'btn btn-primary mr-3',
                cancelButton: 'btn btn-secondary'
            },
            showClass: {
                popup: 'swal2-noanimation',
                backdrop: 'swal2-noanimation'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                var url = "{{ route('expenses.destroy', ':id') }}";
                url = url.replace(':id', expenseId);

                var token = "{{ csrf_token() }}";

                $.easyAjax({
                    type: 'POST',
                    url: url,
                    data: {
                        '_token': token,
                        '_method': 'DELETE'
                    },
                    success: function(response) {
                        console.log('Delete success response:', response);
                        if (response.status == "success") {
                            $.showToastr(response.message || 'Expense deleted successfully', 'success');
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        } else {
                            $.showToastr(response.message || 'Failed to delete expense', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete error:', xhr, status, error);
                        console.error('Response:', xhr.responseJSON);
                        var errorMessage = 'Failed to delete expense';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseText) {
                            try {
                                var errorData = JSON.parse(xhr.responseText);
                                if (errorData.message) {
                                    errorMessage = errorData.message;
                                }
                            } catch (e) {
                                errorMessage = xhr.responseText.substring(0, 100);
                            }
                        }
                        $.showToastr(errorMessage, 'error');
                    }
                });
            }
        });
    });
});
</script>
@endpush

