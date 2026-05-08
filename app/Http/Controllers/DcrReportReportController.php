<?php

namespace App\Http\Controllers;

use App\DataTables\DcrReportReportDataTable;
use App\Exports\DcrReportExport;
use App\Helper\RoleHierarchy;
use App\Models\PharmaHeadquarter;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class DcrReportReportController extends AccountBaseController
{
    use \App\Traits\AccessibleHeadquarters;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'DCR Reports';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('dcr_reports', $this->user->modules));
            abort_403(user()->permission('view_dcr_report') != 'all');
            return $next($request);
        });
    }

    public function index(DcrReportReportDataTable $dataTable)
    {
        if (!request()->ajax()) {
            $this->fromDate = now($this->company->timezone)->startOfMonth();
            $this->toDate = now($this->company->timezone);

            $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
            $this->employees = User::with(['employeeDetail.designation', 'employeeDetail.headquarter'])
                ->whereHas('employeeDetail')
                ->where('company_id', company()->id)
                ->when(!empty($viewableIds), fn ($q) => $q->whereIn('id', $viewableIds))
                ->orderBy('name')
                ->get();

            $accessibleHqIds = $this->accessibleHeadquarterIds();
            $headquarterQuery = PharmaHeadquarter::with(['exstations', 'outstations', 'area'])
                ->where('company_id', company()->id);
            if ($accessibleHqIds !== null && !empty($accessibleHqIds)) {
                $headquarterQuery->whereIn('id', $accessibleHqIds);
            } elseif ($accessibleHqIds !== null && empty($accessibleHqIds)) {
                $headquarterQuery->where('id', 0);
            }
            $this->headquarters = $headquarterQuery->orderBy('name')->get();
        }

        return $dataTable->render('reports.dcr-report.index', $this->data);
    }

    public function exportExcel(Request $request)
    {
        abort_403(user()->permission('view_dcr_report') != 'all');

        $filters = $this->getExportFilters($request);
        $filename = 'DCR_Report_' . ($filters['startDate'] ?? '') . '_to_' . ($filters['endDate'] ?? '') . '.xlsx';

        return Excel::download(new DcrReportExport($filters), $filename);
    }

    public function exportPdf(Request $request)
    {
        abort_403(user()->permission('view_dcr_report') != 'all');

        $filters = $this->getExportFilters($request);
        $export = new DcrReportExport($filters);
        $rows = $export->getCollection();

        $pdf = app('dompdf.wrapper');

        $pdf->loadView('reports.dcr-report.pdf', [
            'rows' => $rows,
            'company' => company(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('DCR_Report_' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportCsv(Request $request)
    {
        abort_403(user()->permission('view_dcr_report') != 'all');

        $filters = $this->getExportFilters($request);
        $filename = 'DCR_Report_' . now()->format('Y-m-d') . '.csv';

        return Excel::download(
            new DcrReportExport($filters),
            $filename,
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    private function getExportFilters(Request $request): array
    {
        $company = company();
        $dateFormat = $company->date_format;

        $startDate = $request->input('startDate', now($company->timezone)->startOfMonth()->format($dateFormat));
        $endDate = $request->input('endDate', now($company->timezone)->format($dateFormat));

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'headquarter' => $request->input('headquarter'),
            'stationType' => $request->input('stationType'),
            'partyType' => $request->input('partyType'),
            'employee' => $request->input('employee', 'all'),
            'accessibleHqIds' => $this->accessibleHeadquarterIds(),
        ];
    }
}
