<?php

namespace App\DataTables;

use App\Models\EmployeeFnfSettlement;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class EmployeeFnfSettlementDataTable extends BaseDataTable
{
    private $viewPermission;
    private $editPermission;
    private $deletePermission;

    public function __construct()
    {
        parent::__construct();
        $this->viewPermission = user()->permission('view_employees');
        $this->editPermission = user()->permission('edit_employees');
        $this->deletePermission = user()->permission('delete_employees');
    }

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('check', fn($row) => $this->checkBox($row))
            ->addColumn('employee', function ($row) {
                return '<div class="d-flex align-items-center">
                            <img src="' . $row->employee->image_url . '" class="mr-2 taskEmployeeImg rounded-circle" style="width: 35px; height: 35px;">
                            <div>
                                <h6 class="mb-0 f-13 text-darkest-grey">' . $row->employee->name . '</h6>
                                <span class="f-11 text-lightest">' . $row->employee->email . '</span>
                            </div>
                        </div>';
            })
            ->addColumn('last_working_day', fn($row) => $row->last_working_day->translatedFormat(company()->date_format))
            ->addColumn('status', function ($row) {
                $color = $row->status_color;
                $status = ucfirst(str_replace('_', ' ', $row->status));
                return '<span class="badge badge-' . $color . '">' . $status . '</span>';
            })
            ->addColumn('clearance_progress', function ($row) {
                $progress = $row->clearance_progress;
                return '<div class="progress" style="height: 20px;">
                            <div class="progress-bar progress-bar-striped bg-success" 
                                 style="width: ' . $progress . '%">' . $progress . '%</div>
                        </div>';
            })
            ->addColumn('net_payable', fn($row) => '<strong class="text-success">' . currency_format($row->net_payable, company()->currency_id) . '</strong>')
            ->addColumn('payment_status', function ($row) {
                if ($row->payment_status == 'paid') {
                    return '<span class="badge badge-success"><i class="fa fa-check"></i> Paid</span>';
                } elseif ($row->payment_status == 'processed') {
                    return '<span class="badge badge-info">Processed</span>';
                }
                return '<span class="badge badge-warning">Pending</span>';
            })
            ->addColumn('action', function ($row) {
                $action = '<div class="task_view">';

                $action .= '<a href="' . route('fnf-settlements.show', [$row->id]) . '" class="btn btn-sm btn-primary">
                                <i class="fa fa-eye"></i> ' . __('app.view') . '
                            </a>';

                if ($this->editPermission == 'all') {
                    $action .= ' <a href="' . route('fnf-settlements.edit', [$row->id]) . '" class="btn btn-sm btn-secondary ml-1 openRightModal">
                                    <i class="fa fa-edit"></i> ' . __('app.edit') . '
                                </a>';
                }

                $action .= '</div>';
                return $action;
            })
            ->addIndexColumn()
            ->rawColumns(['employee', 'status', 'clearance_progress', 'net_payable', 'payment_status', 'action', 'check']);
    }

    public function query(EmployeeFnfSettlement $model)
    {
        $request = $this->request();

        $model = $model->with(['employee', 'approvedBy'])
            ->select('employee_fnf_settlements.*');

        if ($request->status != 'all' && $request->status != null) {
            $model = $model->where('employee_fnf_settlements.status', $request->status);
        }

        if ($request->searchText != '') {
            $model = $model->whereHas('employee', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->searchText . '%')
                  ->orWhere('email', 'like', '%' . $request->searchText . '%');
            });
        }

        if ($request->payment_status != 'all' && $request->payment_status != null) {
            $model = $model->where('employee_fnf_settlements.payment_status', $request->payment_status);
        }

        return $model->orderBy('employee_fnf_settlements.created_at', 'desc');
    }

    public function html()
    {
        $dataTable = $this->setBuilder('fnf-settlements-table')
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["fnf-settlements-table"].buttons().container()
                     .appendTo(".bg-additional-grey:eq(0)")
                }',
                'fnDrawCallback' => 'function( settings ) {
                    $("body").tooltip({
                        selector: \'[data-toggle="tooltip"]\'
                    });
                }',
            ]);

        if (canDataTableExport()) {
            $dataTable->buttons(Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')]));
        }

        return $dataTable;
    }

    protected function getColumns()
    {
        return [
            'check' => Column::make('check')
                ->title('<input type="checkbox" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">')
                ->exportable(false)
                ->orderable(false)
                ->searchable(false)
                ->width(20),
            '#' => Column::computed('DT_RowIndex')
                ->title('#')
                ->width(30),
            __('app.employee') => Column::computed('employee')
                ->name('employee.name')
                ->exportable(true)
                ->title(__('app.employee'))
                ->width(200),
            __('Last Working Day') => Column::computed('last_working_day')
                ->title(__('Last Working Day'))
                ->width(120),
            __('app.status') => Column::computed('status')
                ->title(__('app.status'))
                ->width(100),
            __('Clearance Progress') => Column::computed('clearance_progress')
                ->title(__('Clearance Progress'))
                ->orderable(false)
                ->width(150),
            __('Net Payable') => Column::computed('net_payable')
                ->title(__('Net Payable'))
                ->width(120),
            __('Payment Status') => Column::computed('payment_status')
                ->title(__('Payment Status'))
                ->width(120),
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->width(150)
                ->addClass('text-right')
        ];
    }
}

