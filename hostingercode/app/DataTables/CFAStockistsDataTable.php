<?php

namespace App\DataTables;

use App\Models\CFAStockist;
use App\Models\CustomFieldGroup;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use App\Helper\UserService;
use App\Helper\Common;

class CFAStockistsDataTable extends BaseDataTable
{
    private $viewStockistPermission;
    private $deleteStockistPermission;
    private $editStockistPermission;

    public function __construct()
    {
        parent::__construct();
        $this->viewStockistPermission = user()->permission('view_stockists');
        $this->deleteStockistPermission = user()->permission('delete_stockists');
        $this->editStockistPermission = user()->permission('edit_stockists');
    }

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $userId = UserService::getUserId();
        $datatables = datatables()->eloquent($query);
        $datatables->addIndexColumn();
        
        $datatables->addColumn('action', function ($row) use ($userId) {
            $action = '<div class="task_view">

                <div class="dropdown dropup">
                    <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                        id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="icon-options-vertical icons"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';

            $action .= '<a href="' . route('cfa-stockists.show', [$row->id]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';

            if ($this->editStockistPermission == 'all' || ($this->editStockistPermission == 'added')) {
                $action .= '<a href="' . route('cfa-stockists.edit', [$row->id]) . '" class="dropdown-item"><i class="fa fa-edit mr-2"></i>' . __('app.edit') . '</a>';
            }

            if ($this->deleteStockistPermission == 'all' || ($this->deleteStockistPermission == 'added')) {
                $action .= '<a class="dropdown-item delete-table-row" href="javascript:;" data-cfa-stockist-id="' . $row->id . '">
                                <i class="fa fa-trash mr-2"></i>
                                ' . trans('app.delete') . '
                            </a>';
            }

            $action .= '</div>
                    </div>
                </div>';

            return $action;
        });

        $datatables->editColumn('cfa_stockist_id', function ($row) {
            return '<span class="badge badge-info">' . ($row->cfa_stockist_id ?? '-') . '</span>';
        });

        $datatables->editColumn('shopname', function ($row) {
            return '<a href="' . route('cfa-stockists.show', [$row->id]) . '" class="text-dark">' . $row->shopname . '</a>';
        });

        $datatables->addColumn('cfa_distributors', function ($row) {
            $cfaDistributors = $row->cfaDistributors;
            if ($cfaDistributors->isEmpty()) {
                return '<span class="text-muted">-</span>';
            }
            // Load clientDetails relationship if not already loaded
            $cfaDistributors->load('clientDetails');
            return $cfaDistributors->map(function ($distributor) {
                return $distributor->clientDetails->company_name ?? $distributor->name;
            })->join(', ');
        });

        $datatables->rawColumns(['cfa_stockist_id', 'shopname', 'cfa_distributors', 'action']);

        return $datatables;
    }

    /**
     * Get query source of dataTable.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        $request = $this->request();
        $userId = UserService::getUserId();

        $model = CFAStockist::with('cfaDistributors.clientDetails')
            ->where('company_id', company()->id);

        $safeTerm = Common::safeString(request('searchText'));
        if ($request->searchText != '') {
            $model->where(function ($query) use ($safeTerm) {
                $query->where('shopname', 'like', '%' . $safeTerm . '%')
                    ->orWhere('email', 'like', '%' . $safeTerm . '%')
                    ->orWhere('owner_name', 'like', '%' . $safeTerm . '%')
                    ->orWhere('owner_mobile', 'like', '%' . $safeTerm . '%');
            });
        }

        return $model;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        $dataTable = $this->setBuilder('cfa-stockists-table', 2)
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["cfa-stockists-table"].buttons().container()
                    .appendTo( "#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $("body").tooltip({
                        selector: \'[data-toggle="tooltip"]\'
                    })
                }',
            ]);

        if (canDataTableExport()) {
            $dataTable->buttons(Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')]));
        }

        return $dataTable;
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        $data = [
            __('app.id') => ['data' => 'id', 'name' => 'cfa_stockists.id', 'visible' => false, 'title' => __('app.id')],
            __('app.cfaStockistId') => ['data' => 'cfa_stockist_id', 'name' => 'cfa_stockists.cfa_stockist_id', 'title' => __('app.cfaStockistId')],
            __('app.shopname') => ['data' => 'shopname', 'name' => 'cfa_stockists.shopname', 'title' => __('app.shopname')],
            __('app.email') => ['data' => 'email', 'name' => 'cfa_stockists.email', 'title' => __('app.email')],
            __('app.ownerName') => ['data' => 'owner_name', 'name' => 'cfa_stockists.owner_name', 'title' => __('app.ownerName')],
            __('app.ownerMobile') => ['data' => 'owner_mobile', 'name' => 'cfa_stockists.owner_mobile', 'title' => __('app.ownerMobile')],
            __('CFA/Distributors') => ['data' => 'cfa_distributors', 'name' => 'cfa_distributors', 'title' => __('CFA/Distributors'), 'orderable' => false, 'searchable' => false],
        ];

        $action = [
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-right pr-20')
        ];

        return array_merge($data, CustomFieldGroup::customFieldsDataMerge(new CFAStockist()), $action);
    }
}

