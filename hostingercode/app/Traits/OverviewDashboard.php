<?php

namespace App\Traits;

use App\Models\DashboardWidget;
use App\Models\Deal;
use App\Models\Leave;
use App\Models\Payment;
use App\Models\ProjectActivity;
use App\Models\ProjectTimeLog;
use App\Models\Task;
use App\Models\TaskboardColumn;
use App\Models\Ticket;
use App\Models\UserActivity;
use App\Models\Currency;
use App\Models\Doctor;
use App\Models\Stockist;
use App\Models\Chemist;
use App\Models\Tour;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 *
 */
trait OverviewDashboard
{

    /**
     *
     * @return void
     */
    public function overviewDashboard()
    {
        // Permission check removed - allow all admin users
        // $this->viewOverviewDashboard = user()->permission('view_overview_dashboard');
        // abort_403($this->viewOverviewDashboard !== 'all');

        $this->startDate = (request('startDate') != '') ? Carbon::createFromFormat($this->company->date_format, request('startDate')) : now($this->company->timezone)->startOfMonth();
        $this->endDate = (request('endDate') != '') ? Carbon::createFromFormat($this->company->date_format, request('endDate')) : now($this->company->timezone);
        $startDate = $this->startDate->toDateString();
        $endDate = $this->endDate->toDateString();

        $completedTaskColumn = TaskboardColumn::completeColumn();

        $this->counts = DB::table('users')
            ->select($this->adminDashboardOverviewCountSelects($completedTaskColumn, company()->id))
            ->first();

        $minutes = (int) ($this->counts->totalHoursLogged ?? 0) - (int) ($this->counts->totalBreakMinutes ?? 0);
        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        $timeLog = $hours . ' ' . __('app.hrs');

        if ($remainingMinutes > 0) {
            $timeLog .= ' ' . $remainingMinutes . ' ' . __('app.mins');
        }

        $this->counts->totalHoursLogged = $timeLog;
        $this->widgets = DashboardWidget::where('dashboard_type', 'admin-dashboard')->get();

        $this->activeWidgets = $this->widgets->filter(function ($value, $key) {
            return $value->status == '1';
        })->pluck('widget_name')->toArray();

        $this->earningChartData = $this->earningChart($startDate, $endDate);
        $this->timlogChartData = $this->timelogChart($startDate, $endDate);

        $this->leaves = Leave::with('user', 'type')
            ->where('status', 'pending')
            ->whereBetween('leave_date', [$startDate, $endDate])
            ->get();

        $this->newTickets = Ticket::with('requester')->where('status', 'open')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->orderByDesc('updated_at')->get();

        $pendingTasksQuery = Task::with('project', 'users', 'boardColumn')
            ->where('tasks.is_private', 0)
            ->orderByDesc('due_date')
            ->whereBetween('due_date', [$startDate, $endDate])
            ->limit(15);

        if ($completedTaskColumn) {
            $pendingTasksQuery->where('tasks.board_column_id', '<>', $completedTaskColumn->id);
        }

        $this->pendingTasks = $pendingTasksQuery->get();


        $currentDate = now()->timezone($this->company->timezone)->toDateTimeString();

        $this->pendingLeadFollowUps = Deal::with('followup', 'leadAgent', 'leadAgent.user', 'leadAgent.user.employeeDetail', 'leadAgent.user.employeeDetail.designation')
            ->selectRaw('deals.id,leads.company_name, leads.client_name as client_name, deals.agent_id, ( select lead_follow_up.next_follow_up_date from lead_follow_up where lead_follow_up.deal_id = deals.id and DATE(lead_follow_up.next_follow_up_date) < "' . $currentDate . '" ORDER BY lead_follow_up.created_at DESC Limit 1) as follow_up_date_past,
            ( select lead_follow.next_follow_up_date from lead_follow_up as lead_follow where lead_follow.deal_id = deals.id and status = "incomplete" ORDER BY lead_follow.created_at DESC Limit 1) as follow_up_date_next'
            )
            ->leftJoin('leads', 'leads.id', 'deals.lead_id')
            ->where('deals.next_follow_up', 'yes')
            ->groupBy('deals.id')
            ->get();

        $this->pendingLeadFollowUps = $this->pendingLeadFollowUps->filter(function ($value, $key) {
            return $value->follow_up_date_past != null && $value->follow_up_date_next == null && $value->followup->status != 'completed';
        });

        $this->projectActivities = ProjectActivity::with('project')
            ->join('projects', 'projects.id', '=', 'project_activity.project_id')
            ->where('projects.company_id', company()->id)
            ->whereNull('projects.deleted_at')
            ->select('project_activity.*')
            ->limit(15)
            ->whereBetween('project_activity.created_at', [$startDate, $endDate])
            ->orderBy('project_activity.id', 'desc')
            ->groupBy('project_activity.id')
            ->get();

        $this->userActivities = UserActivity::with('user')->limit(15)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderByDesc('id')->get();

        // Document Expiries for current year - All employees
        $currentYear = now(company()->timezone)->year;
        $this->upcomingDocumentExpiries = \App\Models\EmployeeDocumentExpiry::with('user')
            ->where('alert_enabled', true)
            ->whereYear('expiry_date', $currentYear)
            ->orderBy('expiry_date', 'asc')
            ->get();

        $this->view = 'dashboard.ajax.overview';
    }

    /**
     * Get earning chart data for the given date range.
     *
     * @return \Illuminate\Http\Response
     */
    public function earningChart($startDate, $endDate)
    {
        $payments = Payment::join('currencies', 'currencies.id', '=', 'payments.currency_id')->where('status', 'complete');

        $payments = $payments->whereBetween('payments.paid_on', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]);

        $payments = $payments->orderBy('paid_on', 'ASC')
            ->get([
                DB::raw('DATE_FORMAT(paid_on,"%d-%M-%y") as date'),
                DB::raw('YEAR(paid_on) year, MONTH(paid_on) month'),
                DB::raw('amount as total'),
                'currencies.id as currency_id',
                'currencies.exchange_rate',
                'payments.exchange_rate',
                'payments.default_currency_id'
            ]);

        $incomes = [];

        foreach ($payments as $invoice) {

            if((is_null($invoice->default_currency_id) && is_null($invoice->exchange_rate)) ||
            (!is_null($invoice->default_currency_id) && Company()->currency_id != $invoice->default_currency_id))
            {
                $currency = Currency::findOrFail($invoice->currency_id);
                $exchangeRate = $currency->exchange_rate;
            }
            else {
                $exchangeRate = $invoice->exchange_rate;
            }

            if (!isset($incomes[$invoice->date])) {
                $incomes[$invoice->date] = 0;
            }

            if ($invoice->currency_id != $this->company->currency_id && $invoice->total > 0 && $exchangeRate > 0) {
                $incomes[$invoice->date] += floatval($invoice->total) * floatval($exchangeRate);
            }
            else {
                $incomes[$invoice->date] += round($invoice->total, 2);
            }

        }

        $dates = array_keys($incomes);
        $graphData = [];

        foreach ($dates as $date) {
            $graphData[] = [
                'date' => $date,
                'total' => isset($incomes[$date]) ? round($incomes[$date], 2) : 0,
            ];
        }

        usort($graphData, function ($a, $b) {
            $t1 = strtotime($a['date']);
            $t2 = strtotime($b['date']);

            return $t1 - $t2;
        });

        // return $graphData;
        $graphData = collect($graphData);

        $data['labels'] = $graphData->pluck('date');
        $data['values'] = $graphData->pluck('total')->toArray();
        $data['colors'] = [$this->appTheme->header_color];
        $data['name'] = __('app.earnings');

        return $data;
    }

    /**
     * Get timelog chart data for the given date range.
     *
     * @return \Illuminate\Http\Response
     */
    public function timelogChart($startDate, $endDate)
    {
        $timelogs = ProjectTimeLog::whereBetween('start_time', [$startDate, $endDate]);
        $timelogs = $timelogs->where('project_time_logs.approved', 1);
        $timelogs = $timelogs->groupBy('date')
            ->orderBy('start_time', 'ASC')
            ->get([
                DB::raw('DATE_FORMAT(start_time,\'%d-%M-%y\') as date'),
                DB::raw('FLOOR(sum(total_minutes/60)) as total_hours')
            ]);
        $data['labels'] = $timelogs->pluck('date');
        $data['values'] = $timelogs->pluck('total_hours')->toArray();
        $data['colors'] = [$this->appTheme->header_color];
        $data['name'] = __('modules.dashboard.totalHoursLogged');

        return $data;
    }

    /**
     * Build SELECT fragments for admin overview stats. Handles missing "completed" task column
     * and optional pharma tables when migrations were not all applied.
     *
     * @return array<int, \Illuminate\Database\Query\Expression>
     */
    protected function adminDashboardOverviewCountSelects(?TaskboardColumn $completedTaskColumn, int $companyId): array
    {
        $completedId = $completedTaskColumn?->id;

        $completedSql = $completedId !== null
            ? '(select count(tasks.id) from `tasks` where tasks.board_column_id=' . $completedId . ' and is_private = "0" AND tasks.company_id = ' . $companyId . ')'
            : '0';

        $pendingSql = $completedId !== null
            ? '(select count(tasks.id) from `tasks` where tasks.board_column_id != ' . $completedId . ' and is_private = "0" and tasks.deleted_at IS NULL AND tasks.company_id = ' . $companyId . ')'
            : '(select count(tasks.id) from `tasks` where is_private = "0" and tasks.deleted_at IS NULL AND tasks.company_id = ' . $companyId . ')';

        $selects = [
            DB::raw('(select count(users.id) from `users` inner join role_user on role_user.user_id=users.id inner join roles on roles.id=role_user.role_id WHERE roles.name = "client" AND users.company_id = ' . $companyId . ') as totalClients'),
            DB::raw('(select count(users.id) from `users` inner join role_user on role_user.user_id=users.id inner join roles on roles.id=role_user.role_id WHERE roles.name = "employee" and users.status = "active" AND users.company_id = ' . $companyId . ') as totalEmployees'),
            DB::raw('(select count(projects.id) from `projects` WHERE projects.company_id = ' . $companyId . ') as totalProjects'),
            DB::raw('(select count(invoices.id) from `invoices` where (status = "unpaid" or status = "partial") AND invoices.company_id = ' . $companyId . ') as totalUnpaidInvoices'),
            DB::raw('(select sum(project_time_logs.total_minutes) from `project_time_logs` where approved = "1" AND project_time_logs.company_id = ' . $companyId . ') as totalHoursLogged'),
            DB::raw('(
                    select sum(project_time_log_breaks.total_minutes)
                    from `project_time_log_breaks`
                    inner join project_time_logs on project_time_logs.id = project_time_log_breaks.project_time_log_id
                    where project_time_logs.approved = "1"
                        and project_time_log_breaks.company_id = ' . $companyId . '
                        and project_time_logs.company_id = ' . $companyId . '
                ) as totalBreakMinutes'),
            DB::raw($completedSql . ' as totalCompletedTasks'),
            DB::raw($pendingSql . ' as totalPendingTasks'),
            DB::raw('(select count(distinct(attendances.user_id)) from `attendances` inner join users as atd_user on atd_user.id=attendances.user_id inner join role_user on role_user.user_id=atd_user.id inner join roles on roles.id=role_user.role_id WHERE roles.name = "employee" and attendances.clock_in_time >= "' . today(company()->timezone)->setTimezone('UTC')->toDateTimeString() . '" and atd_user.status = "active" AND attendances.company_id = ' . $companyId . ') as totalTodayAttendance'),
            DB::raw('(select count(tickets.id) from `tickets` where (status="open") and deleted_at IS NULL AND tickets.company_id = ' . $companyId . ') as totalOpenTickets'),
            DB::raw('(select count(tickets.id) from `tickets` where (status="resolved" or status="closed") and deleted_at IS NULL AND tickets.company_id = ' . $companyId . ') as totalResolvedTickets'),
        ];

        $selects[] = Schema::hasTable('doctors')
            ? DB::raw('(select count(doctors.id) from `doctors` WHERE doctors.deleted_at IS NULL AND doctors.company_id = ' . $companyId . ') as totalDoctors')
            : DB::raw('0 as totalDoctors');
        $selects[] = Schema::hasTable('stockists')
            ? DB::raw('(select count(stockists.id) from `stockists` WHERE stockists.deleted_at IS NULL AND stockists.company_id = ' . $companyId . ') as totalStockists')
            : DB::raw('0 as totalStockists');
        $selects[] = Schema::hasTable('chemists')
            ? DB::raw('(select count(chemists.id) from `chemists` WHERE chemists.deleted_at IS NULL AND chemists.company_id = ' . $companyId . ') as totalChemists')
            : DB::raw('0 as totalChemists');

        $selects[] = DB::raw('(select count(users.id) from `users` inner join role_user on role_user.user_id=users.id inner join roles on roles.id=role_user.role_id WHERE roles.name = "employee" and users.status = "active" AND users.company_id = ' . $companyId . ') as totalMedicalRepresentatives');

        $selects[] = Schema::hasTable('tours')
            ? DB::raw('(select count(tours.id) from `tours` WHERE tours.deleted_at IS NULL AND tours.company_id = ' . $companyId . ') as totalTours')
            : DB::raw('0 as totalTours');
        $selects[] = Schema::hasTable('tours')
            ? DB::raw('(select count(tours.id) from `tours` WHERE tours.approved = 0 AND tours.deleted_at IS NULL AND tours.company_id = ' . $companyId . ') as pendingTourApprovals')
            : DB::raw('0 as pendingTourApprovals');
        $selects[] = Schema::hasTable('dcr_reports')
            ? DB::raw('(select count(dcr_reports.id) from `dcr_reports` WHERE dcr_reports.approved = 0 AND dcr_reports.deleted_at IS NULL AND dcr_reports.company_id = ' . $companyId . ') as pendingDcrApprovals')
            : DB::raw('0 as pendingDcrApprovals');

        $selects[] = DB::raw('(select count(expenses.id) from `expenses` WHERE expenses.status = "pending" AND expenses.company_id = ' . $companyId . ') as pendingExpenseApprovals');

        $selects[] = Schema::hasTable('cfa_stockists')
            ? DB::raw('(select count(cfa_stockists.id) from `cfa_stockists` WHERE cfa_stockists.deleted_at IS NULL AND cfa_stockists.company_id = ' . $companyId . ') as totalCfaStockists')
            : DB::raw('0 as totalCfaStockists');

        return $selects;
    }

}
