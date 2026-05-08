<?php

namespace App\Http\Controllers;

use App\DataTables\TpDeviationReportDataTable;
use App\Models\PharmaHeadquarter;
use App\Models\User;
use App\Services\TpDeviationReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TpDeviationReportController extends AccountBaseController
{
    use \App\Traits\AccessibleHeadquarters;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.tpDeviationReport';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array(user()->permission('view_tp_deviation_report'), ['all', 'added', 'owned', 'both'], true));

            return $next($request);
        });
    }

    public function index(TpDeviationReportDataTable $dataTable)
    {
        if (!request()->ajax()) {
            $this->fromDate = now($this->company->timezone)->startOfMonth();
            $this->toDate = now($this->company->timezone);

            $accessibleHqIds = $this->accessibleHeadquarterIds();
            $hqQuery = PharmaHeadquarter::where('company_id', company()->id)->orderBy('name');
            if ($accessibleHqIds !== null && !empty($accessibleHqIds)) {
                $hqQuery->whereIn('id', $accessibleHqIds);
            } elseif ($accessibleHqIds !== null && empty($accessibleHqIds)) {
                $hqQuery->where('id', 0);
            }
            $this->headquarters = $hqQuery->get();

            $scopedIds = TpDeviationReportService::scopedUserIdsForCurrentUser();
            $this->employeesForFilter = collect();
            if (!empty($scopedIds)) {
                $this->employeesForFilter = User::with(['employeeDetail', 'employeeDetails'])
                    ->where(function ($q) {
                        $q->whereHas('employeeDetail')->orWhereHas('employeeDetails');
                    })
                    ->where('company_id', company()->id)
                    ->whereIn('id', $scopedIds)
                    ->orderBy('name')
                    ->get()
                    ->map(function ($u) {
                        $emp = $u->employeeDetail ?? $u->employeeDetails;

                        return [
                            'id' => $u->id,
                            'name' => $u->name,
                            'employee_id' => optional($emp)->employee_id,
                        ];
                    });
            }
        }

        return $dataTable->render('reports.tp-deviation.index', $this->data);
    }

    public function exportExcel(Request $request)
    {
        abort_403(!in_array(user()->permission('view_tp_deviation_report'), ['all', 'added', 'owned', 'both'], true));

        $filters = TpDeviationReportService::filtersFromRequest($request);
        $filename = 'TP_Deviation_Report_' . ($filters['startDate'] ?? '') . '_to_' . ($filters['endDate'] ?? '') . '.xlsx';

        return Excel::download(new \App\Exports\TpDeviationReportExport($filters), $filename);
    }
}
