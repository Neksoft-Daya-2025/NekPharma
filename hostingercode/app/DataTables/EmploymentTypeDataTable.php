<?php

namespace App\DataTables;

use App\DataTables\BaseDataTable;
use App\Models\EmploymentType;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;

class EmploymentTypeDataTable extends BaseDataTable
{
    private $editEmploymentTypePermission;
    private $deleteEmploymentTypePermission;

    public function __construct()
    {
        parent::__construct();
        $this->editEmploymentTypePermission = user()->permission('edit_designation');
        $this->deleteEmploymentTypePermission = user()->permission('delete_designation');
    }

    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addIndexColumn()
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="select-table-row" id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" onclick="dataTableRowCheck(' . $row->id . ')">';
            })
            ->addColumn('action', function ($row) {
                $action = '<div class="task_view">';

                $action .= '<div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                            id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';

                $action .= '<a href="' . route('employment-types.edit', [$row->id]) . '" class="dropdown-item openRightModal"><i class="fa fa-edit mr-2"></i>' . __('app.edit') . '</a>';

                if ($this->deleteEmploymentTypePermission == 'all') {
                    $action .= '<a class="dropdown-item delete-table-row" href="javascript:;" data-employment-type-id="' . $row->id . '">
                            <i class="fa fa-trash mr-2"></i>' . __('app.delete') . '
                        </a>';
                }

                $action .= '</div>
                    </div>
                </div>';

                return $action;
            })
            ->editColumn('name', function ($row) {
                return '<h5 class="mb-0 f-13 text-darkest-grey">' . $row->name . '</h5>';
            })
            ->editColumn('requires_end_date', function ($row) {
                if ($row->requires_end_date) {
                    return '<span class="badge badge-warning"><i class="fa fa-calendar"></i> Yes</span>';
                }
                return '<span class="badge badge-secondary">No</span>';
            })
            ->editColumn('is_active', function ($row) {
                if ($row->is_active) {
                    return '<span class="badge badge-success">Active</span>';
                }
                return '<span class="badge badge-danger">Inactive</span>';
            })
            ->rawColumns(['action', 'check', 'name', 'requires_end_date', 'is_active']);
    }

    public function query(EmploymentType $model)
    {
        return $model->where('employment_types.company_id', company()->id)
            ->select('employment_types.*');
    }

    public function html()
    {
        return $this->setBuilder('EmploymentType-table')
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["EmploymentType-table"].buttons().container()
                     .appendTo("#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $("body").tooltip({
                        selector: \'[data-toggle="tooltip"]\'
                    });
                }',
            ]);
    }

    protected function getColumns()
    {
        return [
            'check' => [
                'title' => '<input type="checkbox" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">',
                'exportable' => false,
                'orderable' => false,
                'searchable' => false,
                'visible' => !in_array('client', user_roles())
            ],
            '#' => ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'visible' => !in_array('client', user_roles()), 'title' => '#'],
            __('app.name') => ['data' => 'name', 'name' => 'name', 'exportable' => true, 'title' => __('app.name')],
            __('Slug') => ['data' => 'slug', 'name' => 'slug', 'exportable' => true, 'title' => 'Slug'],
            __('Requires End Date') => ['data' => 'requires_end_date', 'name' => 'requires_end_date', 'exportable' => true, 'title' => 'Requires End Date'],
            __('app.status') => ['data' => 'is_active', 'name' => 'is_active', 'exportable' => true, 'title' => __('app.status')],
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-right pr-20')
        ];
    }
}






