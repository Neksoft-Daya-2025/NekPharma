<?php

namespace App\Traits;

use App\Models\DcrReport;
use App\Models\Expense;
use App\Models\Tour;
use Carbon\Carbon;

/**
 * Field Force Dashboard - DCR, Tour, Expense pending approvals and DCRs submitted today.
 */
trait FieldForceDashboard
{
    public function fieldForceDashboard()
    {
        $this->startDate = (request('startDate') != '') ? Carbon::createFromFormat($this->company->date_format, request('startDate')) : now($this->company->timezone)->startOfMonth();
        $this->endDate = (request('endDate') != '') ? Carbon::createFromFormat($this->company->date_format, request('endDate')) : now($this->company->timezone);
        $today = now($this->company->timezone)->toDateString();

        // Pending DCR approvals (company-wide for admin)
        $this->pendingDcrReports = DcrReport::with(['user.employeeDetail.designation', 'submittedTo'])
            ->where('company_id', company()->id)
            ->where('approved', 0)
            ->whereNull('deleted_at')
            ->orderByDesc('report_date')
            ->limit(10)
            ->get();

        // Pending Tour approvals
        $this->pendingTours = Tour::with(['user.employeeDetail.designation', 'headquarter', 'submittedTo'])
            ->where('company_id', company()->id)
            ->where('approved', 0)
            ->whereNull('deleted_at')
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        // Pending Expense approvals
        $this->pendingExpenses = Expense::with(['user.employeeDetail.designation', 'currency'])
            ->where('company_id', company()->id)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // DCRs submitted today (count - report_date = today)
        $this->dcrsSubmittedToday = DcrReport::where('company_id', company()->id)
            ->whereDate('report_date', $today)
            ->whereNull('deleted_at')
            ->count();

        $this->view = 'dashboard.ajax.field-force';
    }
}
