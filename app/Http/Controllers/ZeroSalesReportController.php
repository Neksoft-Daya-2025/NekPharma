<?php

namespace App\Http\Controllers;

use App\DataTables\ZeroSalesReportDataTable;
use App\Models\CFAStockist;
use App\Models\PharmaArea;
use App\Models\PharmaHeadquarter;
use App\Models\PharmaRegion;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ZeroSalesReportController extends AccountBaseController
{
    use \App\Traits\AccessibleHeadquarters;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.zeroSalesReport';
        $this->middleware(function ($request, $next) {
            abort_403(user()->permission('view_zero_sales_report') != 'all');
            return $next($request);
        });
    }

    public function index(ZeroSalesReportDataTable $dataTable) /** @phpstan-ignore-line */
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

            $areaQuery = PharmaArea::where('company_id', company()->id)->orderBy('name');
            if ($accessibleHqIds !== null && !empty($accessibleHqIds)) {
                $areaIds = PharmaHeadquarter::whereIn('id', $accessibleHqIds)->pluck('area_id')->unique()->filter();
                if ($areaIds->isNotEmpty()) {
                    $areaQuery->whereIn('id', $areaIds);
                }
            } elseif ($accessibleHqIds !== null && empty($accessibleHqIds)) {
                $areaQuery->where('id', 0);
            }
            $this->areas = $areaQuery->get();

            $this->regions = PharmaRegion::where('company_id', company()->id)->orderBy('name')->get();

            $stockistQuery = CFAStockist::where('company_id', company()->id)->orderBy('shopname');
            if ($accessibleHqIds !== null && !empty($accessibleHqIds)) {
                $stockistQuery->whereIn('headquarter_id', $accessibleHqIds);
            } elseif ($accessibleHqIds !== null && empty($accessibleHqIds)) {
                $stockistQuery->where('id', 0);
            }
            $this->stockists = $stockistQuery->get();
        }

        return $dataTable->render('reports.zero-sales.index', $this->data); /** @phpstan-ignore-line */
    }

    public function exportExcel(Request $request)
    {
        abort_403(user()->permission('view_zero_sales_report') != 'all');

        $filters = $this->getExportFilters($request);
        $filename = 'Zero_Sales_Report_' . ($filters['startDate'] ?? '') . '_to_' . ($filters['endDate'] ?? '') . '.xlsx';
        return Excel::download(new \App\Exports\ZeroSalesReportExport($filters), $filename);
    }

    public function exportPdf(Request $request)
    {
        abort_403(user()->permission('view_zero_sales_report') != 'all');

        $filters = $this->getExportFilters($request);
        $export = new \App\Exports\ZeroSalesReportExport($filters);
        $rows = $export->getCollection();
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('reports.zero-sales.pdf', [
            'rows' => $rows,
            'company' => company(),
            'reportBy' => $filters['reportBy'] ?? 'headquarters',
        ])->setPaper('a4', 'landscape');
        return $pdf->download('Zero_Sales_Report_' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportCsv(Request $request)
    {
        abort_403(user()->permission('view_zero_sales_report') != 'all');

        $filters = $this->getExportFilters($request);
        $filename = 'Zero_Sales_Report_' . now()->format('Y-m-d') . '.csv';
        return Excel::download(
            new \App\Exports\ZeroSalesReportExport($filters),
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
            'reportBy' => $request->input('reportBy', 'headquarters'),
            'headquarter' => $request->input('headquarter') ? (int) $request->input('headquarter') : null,
            'area' => $request->input('area') ? (int) $request->input('area') : null,
            'region' => $request->input('region') ? (int) $request->input('region') : null,
            'stockist' => $request->input('stockist') ? (int) $request->input('stockist') : null,
            'accessibleHqIds' => $this->accessibleHeadquarterIds(),
        ];
    }
}
