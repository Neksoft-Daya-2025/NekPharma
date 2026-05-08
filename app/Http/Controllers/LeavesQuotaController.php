<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Exports\LeaveQuotaReportExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\User;
use App\Helper\Reply;
use App\Models\LeaveType;
use App\Scopes\ActiveScope;
use Illuminate\Http\Request;
use App\Models\EmployeeLeaveQuota;
use Illuminate\Support\Facades\Artisan;

class LeavesQuotaController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.leaves';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('leaves', $this->user->modules));
            return $next($request);
        });
    }

    public function update(Request $request, $id)
    {
        $type = EmployeeLeaveQuota::findOrFail($id);

        if ($request->leaves < 0 || $request->leaves < $type->leaves_used) {
            return Reply::error('messages.employeeLeaveQuota');
        }

        $remainingLeaves = ($request->leaves - $type->leaves_used - $type->unused_leaves);
        $overutilisedLeaves = ($type->overutilised_leaves - $request->leaves);
        $unusedLeaves = ($type->unused_leaves - $request->leaves);

        $type->no_of_leaves = $request->leaves;
        $type->leave_type_impact = $request->leaveimpact;
        $type->leaves_remaining = ($remainingLeaves > 0) ? $remainingLeaves : 0;
        $type->overutilised_leaves = ($overutilisedLeaves > 0) ? $overutilisedLeaves : 0;
        $type->unused_leaves = ($unusedLeaves > 0) ? $unusedLeaves : 0;
        $type->save();

        session()->forget('user');

        return Reply::success(__('messages.leaveTypeAdded'));
    }

    public function employeeLeaveTypes($userId)
    {
        $options = '';

        if ($userId != 0) {
            $employee = User::withoutGlobalScope(ActiveScope::class)->with(['roles', 'leaveTypes', 'employeeDetail'])->findOrFail($userId);

            foreach ($employee->leaveTypes as $leavesQuota) {
                $hasLeave = ($leavesQuota->leaveType && $leavesQuota->leaveType->deleted_at == null)
                    ? $leavesQuota->leaveType->leaveTypeCondition($leavesQuota->leaveType, $employee)
                    : false;

                if ($hasLeave) {
                    $displayRemaining = (int) round((float) $leavesQuota->leaves_remaining);
                    $options .= '<option value="' . $leavesQuota->leave_type_id . '"> ' . $leavesQuota->leaveType->type_name . ' (' . $displayRemaining . ') </option>'; /** @phpstan-ignore-line */
                }
            }
        } else {
            // userId=0 is an admin-only generic list (no employee context).
            // Restrict this to admin/HR roles only so employees cannot abuse it to bypass probation checks.
            abort_403(!($this->user->hasRole('admin') || $this->user->hasRole('employee') === false));

            $leaveQuotas = LeaveType::all();

            foreach ($leaveQuotas as $leaveQuota) {
                $displayLeaves = (int) round((float) $leaveQuota->no_of_leaves);
                $options .= '<option value="' . $leaveQuota->id . '"> ' . $leaveQuota->type_name . ' (' . $displayLeaves . ') </option>'; /** @phpstan-ignore-line */
            }
        }

        return Reply::dataOnly(['status' => 'success', 'data' => $options]);
    }

    public function exportAllLeaveQuota($id, $year, $month)
    {
        abort_403(!canDataTableExport());
        $name = __('app.leaveQuotaReport') . '-' . Carbon::createFromDate($year, $month, 1)->startOfDay()->translatedFormat('F-Y');
        return Excel::download(new LeaveQuotaReportExport($id, $year, $month), $name . '.xlsx');
    }

}
