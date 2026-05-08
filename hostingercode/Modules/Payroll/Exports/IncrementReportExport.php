<?php

namespace Modules\Payroll\Exports;

use App\Helper\ReportCommonFields;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Payroll\Entities\EmployeeMonthlySalary;

class IncrementReportExport implements FromCollection, WithHeadings, WithMapping
{
    protected $startDate;

    protected $endDate;

    protected $departmentId;

    protected $designationId;

    public function __construct($startDate = null, $endDate = null, $departmentId = 'all', $designationId = 'all')
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->departmentId = $departmentId;
        $this->designationId = $designationId;
    }

    public function headings(): array
    {
        return array_merge(
            ReportCommonFields::headings(),
            [
                __('payroll::modules.payroll.incrementDate'),
                __('payroll::modules.payroll.amount'),
            ]
        );
    }

    public function collection()
    {
        $query = EmployeeMonthlySalary::with([
            'user',
            'user.employeeDetail',
            'user.employeeDetail.designation',
            'user.employeeDetail.department',
            'user.employeeDetail.headquarter',
        ])
            ->where('type', 'increment')
            ->orderBy('date', 'desc')
            ->orderBy('user_id');

        if ($this->startDate) {
            $query->where('date', '>=', Carbon::parse($this->startDate)->startOfDay());
        }
        if ($this->endDate) {
            $query->where('date', '<=', Carbon::parse($this->endDate)->endOfDay());
        }
        if ($this->departmentId && $this->departmentId !== 'all') {
            $query->whereHas('user.employeeDetail', function ($q) {
                $q->where('department_id', $this->departmentId);
            });
        }
        if ($this->designationId && $this->designationId !== 'all') {
            $query->whereHas('user.employeeDetail', function ($q) {
                $q->where('designation_id', $this->designationId);
            });
        }

        return $query->get();
    }

    public function map($row): array
    {
        $user = $row->user;
        $employeeDetail = $user ? $user->employeeDetail : null;

        $common = $employeeDetail
            ? ReportCommonFields::mapEmployeeRow($employeeDetail, $user)
            : [$user ? $user->name : '-', '-', '-', '-', '-', '-'];

        $incrementDate = $row->date
            ? Carbon::parse($row->date)->format(company()->date_format)
            : '-';

        return array_merge($common, [
            $incrementDate,
            $row->amount ?? '0',
        ]);
    }
}
