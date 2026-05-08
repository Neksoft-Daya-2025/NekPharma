<?php

namespace App\DataTables;

use App\Services\DcrReportReportService;
use Carbon\Carbon;

class DcrReportReportDataTable extends BaseDataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->collection($query)
            ->addIndexColumn()
            ->addColumn('date', function ($row) {
                $d = $row->date;
                return $d instanceof Carbon ? $d->format($this->company->date_format) : Carbon::parse($d)->format($this->company->date_format);
            })
            ->addColumn('employee_name', fn ($row) => $row->employee_name ?? '-')
            ->addColumn('role', fn ($row) => $row->role ?? '-')
            ->addColumn('headquarter', fn ($row) => $row->headquarter ?? '-')
            ->addColumn('station_type', fn ($row) => $row->station_type ?? '-')
            ->addColumn('party_name', fn ($row) => $row->party_name ?? '-')
            ->addColumn('party_type', fn ($row) => $row->party_type ?? '-')
            ->addColumn('product', fn ($row) => $row->product ?? '-')
            ->addColumn('visit_time', fn ($row) => $row->visit_time ?? '-')
            ->addColumn('remarks', fn ($row) => $row->remarks ?? '-')
            ->rawColumns(['date', 'employee_name', 'role', 'headquarter', 'station_type', 'party_name', 'party_type', 'product', 'visit_time', 'remarks']);
    }

    public function query()
    {
        $request = $this->request();
        $filters = [
            'startDate' => $request->startDate ?? null,
            'endDate' => $request->endDate ?? null,
            'headquarter' => $request->headquarter ?? null,
            'stationType' => $request->stationType ?? null,
            'partyType' => $request->partyType ?? null,
            'employee' => $request->employee ?? 'all',
        ];

        return DcrReportReportService::getVisitRows($filters);
    }

    public function html()
    {
        return $this->setBuilder('dcr-report-table');
    }

    protected function getColumns()
    {
        return [
            '#' => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'title' => '#'],
            __('app.date') => ['data' => 'date', 'name' => 'date', 'title' => __('app.date')],
            __('app.employee') => ['data' => 'employee_name', 'name' => 'employee_name', 'title' => __('app.employee')],
            __('app.role') => ['data' => 'role', 'name' => 'role', 'title' => __('app.role')],
            __('app.hq') => ['data' => 'headquarter', 'name' => 'headquarter', 'title' => __('app.hq')],
            __('app.stationType') => ['data' => 'station_type', 'name' => 'station_type', 'title' => __('app.stationType')],
            __('app.partyName') => ['data' => 'party_name', 'name' => 'party_name', 'title' => __('app.partyName')],
            __('app.partyType') => ['data' => 'party_type', 'name' => 'party_type', 'title' => __('app.partyType')],
            __('app.product') => ['data' => 'product', 'name' => 'product', 'title' => __('app.product')],
            __('app.visitTime') => ['data' => 'visit_time', 'name' => 'visit_time', 'title' => __('app.visitTime')],
            __('app.remarks') => ['data' => 'remarks', 'name' => 'remarks', 'title' => __('app.remarks')],
        ];
    }
}
