<?php

namespace App\Http\Controllers;

use App\DataTables\AttendanceReportDataTable;
use App\Exports\AttendanceSheetFormatExport;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceReportController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.attendanceReport';
    }

    public function index(AttendanceReportDataTable $dataTable)
    {
        abort_403(user()->permission('view_attendance_report') != 'all');

        if (!request()->ajax()) {
            $this->fromDate = now($this->company->timezone)->startOfMonth();
            $this->toDate = now($this->company->timezone);
            $this->employees = User::allEmployees();
        }

        return $dataTable->render('reports.attendance.index', $this->data);
    }

    public function exportSummary(Request $request)
    {
        abort_403(user()->permission('view_attendance_report') != 'all');

        $startDate = $request->input('startDate', now($this->company->timezone)->startOfMonth()->format($this->company->date_format));
        $endDate = $request->input('endDate', now($this->company->timezone)->format($this->company->date_format));
        $employee = $request->input('employee', 'all');
        $format = $request->input('format', 'xlsx');

        $ext = $format === 'csv' ? 'csv' : 'xlsx';
        $filename = 'Attendance_Summary_' . $startDate . '_To_' . $endDate . '.' . $ext;

        $export = new AttendanceSheetFormatExport($startDate, $endDate, $employee);

        if ($format === 'csv') {
            return Excel::download($export, $filename, \Maatwebsite\Excel\Excel::CSV);
        }

        return Excel::download($export, $filename);
    }

}
