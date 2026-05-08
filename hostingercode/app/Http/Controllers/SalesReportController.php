<?php

namespace App\Http\Controllers;

use App\DataTables\SalesReportDataTable;
use App\Helpers\PharmaDesignationHelper;
use App\Models\CFAStockist;
use App\Models\PharmaArea;
use App\Models\PharmaHeadquarter;
use App\Models\PharmaRegion;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SalesReportController extends AccountBaseController
{
    use \App\Traits\AccessibleHeadquarters;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.salesReport';
        $this->middleware(function ($request, $next) {
            $this->checkSalesReportPermission();
            return $next($request);
        });
    }

    protected function checkSalesReportPermission(): void
    {
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            return;
        }
        if (user()->permission('view_sales_report') === 'all') {
            return;
        }
        $viewCfaDist = user()->permission('view_cfa_distributor_invoices');
        $viewCfaStock = user()->permission('view_cfa_stockist_invoices');
        abort_403(!in_array($viewCfaDist, ['all', 'added', 'owned', 'both']) && !in_array($viewCfaStock, ['all', 'added', 'owned', 'both']));
    }

    public function index(SalesReportDataTable $dataTable) /** @phpstan-ignore-line */
    {
        if (!request()->ajax()) {
            $this->fromDate = now($this->company->timezone)->startOfMonth();
            $this->toDate = now($this->company->timezone);

            $this->clients = User::allClients();

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
            $this->stockists = CFAStockist::where('company_id', company()->id)->orderBy('shopname')->get();
            $this->products = Product::where('company_id', company()->id)->orderBy('name')->get();
        }

        return $dataTable->render('reports.sales.index', $this->data); /** @phpstan-ignore-line */
    }

    public function exportExcel(Request $request)
    {
        $filters = $this->getExportFilters($request);
        $filename = 'Sales_Report_' . ($filters['startDate'] ?? '') . '_to_' . ($filters['endDate'] ?? '') . '.xlsx';
        return Excel::download(new \App\Exports\SalesReportExport($filters), $filename);
    }

    public function exportPdf(Request $request)
    {
        $filters = $this->getExportFilters($request);
        $export = new \App\Exports\SalesReportExport($filters);
        $rows = $export->getCollection();
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('reports.sales.pdf', [
            'rows' => $rows,
            'company' => company(),
        ])->setPaper('a4', 'landscape');
        return $pdf->download('Sales_Report_' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportCsv(Request $request)
    {
        $filters = $this->getExportFilters($request);
        $filename = 'Sales_Report_' . now()->format('Y-m-d') . '.csv';
        return Excel::download(
            new \App\Exports\SalesReportExport($filters),
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
            'invoiceType' => $request->input('invoiceType'),
            'clientID' => $request->input('clientID'),
            'headquarter' => $request->input('headquarter'),
            'area' => $request->input('area'),
            'region' => $request->input('region'),
            'stockist' => $request->input('stockist'),
            'product' => $request->input('product'),
            'accessibleHqIds' => $this->accessibleHeadquarterIds(),
        ];
    }
}
