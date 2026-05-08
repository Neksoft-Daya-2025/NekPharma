<?php

namespace App\DataTables;

use App\Services\ZeroSalesReportService;

class ZeroSalesReportDataTable extends BaseDataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->collection($query)
            ->addIndexColumn()
            ->addColumn('entity_type', fn ($row) => $row->entity_type ?? '-')
            ->addColumn('entity_name', fn ($row) => $row->entity_name ?? '-')
            ->addColumn('hq_name', fn ($row) => $row->hq_name ?? '-')
            ->addColumn('area_name', fn ($row) => $row->area_name ?? '-')
            ->addColumn('region_name', fn ($row) => $row->region_name ?? '-')
            ->rawColumns(['entity_type', 'entity_name', 'hq_name', 'area_name', 'region_name']);
    }

    public function query()
    {
        $request = $this->request();
        $filters = [
            'startDate' => $request->startDate ?? null,
            'endDate' => $request->endDate ?? null,
            'reportBy' => $request->reportBy ?? 'headquarters',
            'headquarter' => $request->headquarter ? (int) $request->headquarter : null,
            'area' => $request->area ? (int) $request->area : null,
            'region' => $request->region ? (int) $request->region : null,
            'stockist' => $request->stockist ? (int) $request->stockist : null,
        ];

        return ZeroSalesReportService::getReportRows($filters);
    }

    public function html()
    {
        return $this->setBuilder('zero-sales-report-table');
    }

    protected function getColumns()
    {
        return [
            '#' => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'title' => '#'],
            'Entity Type' => ['data' => 'entity_type', 'name' => 'entity_type', 'title' => 'Entity Type'],
            'Entity Name' => ['data' => 'entity_name', 'name' => 'entity_name', 'title' => 'Entity Name'],
            __('app.hq') => ['data' => 'hq_name', 'name' => 'hq_name', 'title' => __('app.hq')],
            'Area' => ['data' => 'area_name', 'name' => 'area_name', 'title' => 'Area'],
            'Region' => ['data' => 'region_name', 'name' => 'region_name', 'title' => 'Region'],
        ];
    }
}
