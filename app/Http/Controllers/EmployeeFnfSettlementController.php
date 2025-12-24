<?php

namespace App\Http\Controllers;

use App\DataTables\EmployeeFnfSettlementDataTable;
use App\Models\EmployeeFnfSettlement;
use App\Models\User;
use App\Models\Leave;
use App\Helper\Reply;
use App\Http\Requests\FnfSettlement\StoreRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmployeeFnfSettlementController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Full & Final Settlement';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('employees', user_modules()));
            return $next($request);
        });
    }

    public function index(EmployeeFnfSettlementDataTable $dataTable)
    {
        $this->viewPermission = user()->permission('view_employees');
        abort_403($this->viewPermission == 'none');

        return $dataTable->render('employees.fnf.index', $this->data);
    }

    public function create()
    {
        $this->addPermission = user()->permission('add_employees');
        abort_403($this->addPermission != 'all');

        // Get all employees - can initiate FNF for any employee
        $this->employees = User::with('employeeDetail')
            ->whereHas('employeeDetail')
            ->whereDoesntHave('fnfSettlement')
            ->where(function($query) {
                $query->where('status', 'active')
                      ->orWhere('status', 'deactive');
            })
            ->get();
        
        // Prioritize employees with last_date set (exiting employees)
        $this->employees = $this->employees->sortByDesc(function($employee) {
            return $employee->employeeDetail && $employee->employeeDetail->last_date ? 1 : 0;
        });

        $this->resignationTypes = ['resignation', 'termination', 'retirement', 'end_of_contract'];

        $this->view = 'employees.fnf.create';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('employees.fnf.create', $this->data);
    }

    public function store(StoreRequest $request)
    {
        $this->addPermission = user()->permission('add_employees');
        abort_403($this->addPermission != 'all');

        $employee = User::with('employeeDetail')->findOrFail($request->user_id);

        $fnf = new EmployeeFnfSettlement();
        $fnf->company_id = company()->id;
        $fnf->user_id = $request->user_id;
        $fnf->resignation_date = $request->resignation_date ? Carbon::createFromFormat(company()->date_format, $request->resignation_date) : null;
        $fnf->last_working_day = Carbon::createFromFormat(company()->date_format, $request->last_working_day);
        $fnf->fnf_initiated_date = now();
        $fnf->resignation_type = $request->resignation_type;
        $fnf->resignation_reason = $request->resignation_reason;
        $fnf->status = 'initiated';

        // Initialize clearance checklist
        $fnf->clearance_checklist = EmployeeFnfSettlement::getDefaultClearanceChecklist();

        // Initialize documents
        $fnf->documents_to_collect = EmployeeFnfSettlement::getDefaultDocuments();

        // Get employee's basic salary from payroll
        $fnf->basic_salary = $employee->employeeDetail->salary ?? 0;

        // Calculate working days and earned salary
        $this->calculateEarnedSalary($fnf, $request->last_working_day);

        // Calculate leave encashment
        $this->calculateLeaveEncashment($fnf, $employee);

        // Add any pending bonuses/incentives from request
        $fnf->pending_bonus = $request->pending_bonus ?? 0;
        $fnf->pending_incentives = $request->pending_incentives ?? 0;

        // Add deductions
        $fnf->loan_outstanding = $request->loan_outstanding ?? 0;
        $fnf->advance_outstanding = $request->advance_outstanding ?? 0;
        $fnf->notice_period_recovery = $request->notice_period_recovery ?? 0;
        $fnf->other_deductions = $request->other_deductions ?? 0;
        $fnf->deduction_remarks = $request->deduction_remarks;

        // Calculate final settlement
        $fnf->calculateSettlement();

        $fnf->remarks = $request->remarks;
        $fnf->added_by = user()->id;
        $fnf->save();

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('fnf-settlements.show', $fnf->id)]);
    }

    public function show($id)
    {
        $this->fnfSettlement = EmployeeFnfSettlement::with(['employee.employeeDetail', 'approvedBy', 'addedBy'])
            ->findOrFail($id);

        $this->viewPermission = user()->permission('view_employees');
        abort_403($this->viewPermission == 'none');

        $this->pageTitle = 'FNF - ' . $this->fnfSettlement->employee->name;

        return view('employees.fnf.show', $this->data);
    }

    public function edit($id)
    {
        $this->fnfSettlement = EmployeeFnfSettlement::findOrFail($id);

        $this->editPermission = user()->permission('edit_employees');
        abort_403($this->editPermission != 'all');

        $this->resignationTypes = ['resignation', 'termination', 'retirement', 'end_of_contract'];

        $this->view = 'employees.fnf.edit';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('employees.fnf.edit', $this->data);
    }

    public function update(Request $request, $id)
    {
        $this->editPermission = user()->permission('edit_employees');
        abort_403($this->editPermission != 'all');

        $fnf = EmployeeFnfSettlement::findOrFail($id);

        if ($request->has('clearance_checklist')) {
            $fnf->clearance_checklist = json_decode($request->clearance_checklist, true);
        }

        if ($request->has('assets_returned')) {
            $fnf->assets_returned = $request->assets_returned == 'true';
            $fnf->assets_return_date = $request->assets_returned == 'true' ? now() : null;
        }

        if ($request->has('documents_to_collect')) {
            $fnf->documents_to_collect = json_decode($request->documents_to_collect, true);
        }

        // Update financial details
        if ($request->has('earned_salary')) {
            $fnf->earned_salary = $request->earned_salary;
        }

        if ($request->has('leave_encashment_amount')) {
            $fnf->leave_encashment_amount = $request->leave_encashment_amount;
        }

        if ($request->has('pending_bonus')) {
            $fnf->pending_bonus = $request->pending_bonus;
        }

        if ($request->has('pending_incentives')) {
            $fnf->pending_incentives = $request->pending_incentives;
        }

        // Update deductions
        if ($request->has('loan_outstanding')) {
            $fnf->loan_outstanding = $request->loan_outstanding;
        }

        if ($request->has('advance_outstanding')) {
            $fnf->advance_outstanding = $request->advance_outstanding;
        }

        if ($request->has('notice_period_recovery')) {
            $fnf->notice_period_recovery = $request->notice_period_recovery;
        }

        if ($request->has('other_deductions')) {
            $fnf->other_deductions = $request->other_deductions;
        }

        $fnf->deduction_remarks = $request->deduction_remarks;

        // Recalculate settlement
        $fnf->calculateSettlement();

        // Update status
        if ($request->has('status')) {
            $fnf->status = $request->status;
        }

        $fnf->remarks = $request->remarks;
        $fnf->hr_notes = $request->hr_notes;
        $fnf->last_updated_by = user()->id;

        // Check if all clearances are done
        if ($fnf->isClearanceComplete() && $fnf->assets_returned) {
            $fnf->status = 'completed';
            $fnf->fnf_completion_date = now();
        }

        $fnf->save();

        return Reply::success(__('messages.updateSuccess'));
    }

    public function updateClearance(Request $request, $id)
    {
        $fnf = EmployeeFnfSettlement::findOrFail($id);

        $checklist = $fnf->clearance_checklist;
        $deptIndex = $request->department_index;

        $checklist[$deptIndex]['cleared'] = $request->cleared == 'true';
        $checklist[$deptIndex]['cleared_by'] = $request->cleared == 'true' ? user()->id : null;
        $checklist[$deptIndex]['cleared_date'] = $request->cleared == 'true' ? now()->toDateString() : null;
        $checklist[$deptIndex]['remarks'] = $request->remarks;

        $fnf->clearance_checklist = $checklist;
        $fnf->last_updated_by = user()->id;

        // Update status to in_progress if it was initiated
        if ($fnf->status == 'initiated') {
            $fnf->status = 'in_progress';
        }

        $fnf->save();

        return Reply::success(__('messages.updateSuccess'));
    }

    public function markPaymentComplete(Request $request, $id)
    {
        $fnf = EmployeeFnfSettlement::findOrFail($id);

        $fnf->payment_status = 'paid';
        $fnf->payment_date = Carbon::createFromFormat(company()->date_format, $request->payment_date);
        $fnf->payment_mode = $request->payment_mode;
        $fnf->payment_reference = $request->payment_reference;
        $fnf->last_updated_by = user()->id;
        $fnf->save();

        return Reply::success(__('messages.updateSuccess'));
    }

    public function approve($id)
    {
        $fnf = EmployeeFnfSettlement::findOrFail($id);

        $fnf->approved_by = user()->id;
        $fnf->approved_date = now();
        $fnf->status = 'completed';
        $fnf->fnf_completion_date = now();
        $fnf->save();

        return Reply::success('FNF Settlement approved successfully');
    }

    private function calculateEarnedSalary($fnf, $lastWorkingDay)
    {
        $lastDay = Carbon::createFromFormat(company()->date_format, $lastWorkingDay);
        $currentMonth = $lastDay->month;
        $currentYear = $lastDay->year;

        // Get total days in the month
        $totalDaysInMonth = $lastDay->daysInMonth;

        // Get working day in the month up to last working day
        $workingDays = $lastDay->day;

        $fnf->working_days = $workingDays;
        $fnf->payable_days = $workingDays;

        // Calculate earned salary (pro-rata)
        $fnf->earned_salary = ($fnf->basic_salary / $totalDaysInMonth) * $workingDays;
    }

    private function calculateLeaveEncashment($fnf, $employee)
    {
        // Get leave balance
        $leaveBalance = 0;

        foreach ($employee->leaveTypes as $leaveQuota) {
            if ($leaveQuota->leaves_remaining > 0) {
                $leaveBalance += $leaveQuota->leaves_remaining;
            }
        }

        $fnf->leave_balance_days = $leaveBalance;

        // Calculate encashment (daily salary * leave days)
        if ($fnf->basic_salary > 0) {
            $dailyRate = $fnf->basic_salary / 30; // Assuming 30-day month
            $fnf->leave_encashment_amount = $dailyRate * $leaveBalance;
        }
    }

    public function downloadStatement($id)
    {
        $fnf = EmployeeFnfSettlement::with(['employee.employeeDetail'])->findOrFail($id);

        // Generate PDF statement
        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadView('employees.fnf.statement-pdf', ['fnf' => $fnf]);

        return $pdf->download('FNF_Statement_' . $fnf->employee->name . '_' . now()->format('Y-m-d') . '.pdf');
    }

    public function destroy($id)
    {
        $this->deletePermission = user()->permission('delete_employees');
        abort_403($this->deletePermission != 'all');

        EmployeeFnfSettlement::destroy($id);

        return Reply::success(__('messages.deleteSuccess'));
    }

    public function applyQuickAction(Request $request)
    {
        switch ($request->action_type) {
            case 'delete':
                $this->deletePermission = user()->permission('delete_employees');
                abort_403($this->deletePermission != 'all');

                EmployeeFnfSettlement::whereIn('id', $request->row_ids)->delete();

                return Reply::success(__('messages.deleteSuccess'));
            default:
                return Reply::error(__('messages.selectAction'));
        }
    }
}

