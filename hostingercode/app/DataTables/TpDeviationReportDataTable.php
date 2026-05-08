<?php

namespace App\DataTables;

use App\Services\TpDeviationReportService;

class TpDeviationReportDataTable extends BaseDataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->collection($query)
            ->addIndexColumn()
            ->addColumn('deviation_type_label', function ($row) {
                $key = 'app.tpDeviationTypes.' . ($row->deviation_type ?? '');

                return __($key);
            })
            ->rawColumns(['deviation_type_label']);
    }

    public function query()
    {
        // Use Laravel's request(), not $this->request(): DataTables wraps Yajra\DataTables\Utilities\Request,
        // which breaks Illuminate\Http\Request type-hint in filtersFromRequest().
        $filters = TpDeviationReportService::filtersFromRequest(request());

        return TpDeviationReportService::buildRows($filters);
    }

    public function html()
    {
        return $this->setBuilder('tp-deviation-report-table');
    }

    protected function getColumns()
    {
        return [
            '#' => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'title' => '#'],
            __('app.date') => ['data' => 'report_date', 'name' => 'report_date', 'title' => __('app.date')],
            __('app.name') => ['data' => 'employee_name', 'name' => 'employee_name', 'title' => __('app.name')],
            __('app.employeeId') => ['data' => 'employee_code', 'name' => 'employee_code', 'title' => __('app.employeeId')],
            __('app.type') => ['data' => 'deviation_type_label', 'name' => 'deviation_type_label', 'title' => __('app.type')],
            'Tour work status' => ['data' => 'tour_work_status', 'name' => 'tour_work_status', 'title' => 'Tour work status'],
            'DCR work status' => ['data' => 'dcr_work_status', 'name' => 'dcr_work_status', 'title' => 'DCR work status'],
            'Tour station' => ['data' => 'tour_station', 'name' => 'tour_station', 'title' => 'Tour station'],
            'DCR station' => ['data' => 'dcr_station', 'name' => 'dcr_station', 'title' => 'DCR station'],
            'Tour ' . __('app.hq') => ['data' => 'tour_headquarter', 'name' => 'tour_headquarter', 'title' => 'Tour ' . __('app.hq')],
            'DCR ' . __('app.hq') => ['data' => 'dcr_headquarter', 'name' => 'dcr_headquarter', 'title' => 'DCR ' . __('app.hq')],
        ];
    }
}
