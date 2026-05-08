<?php

namespace Modules\SFC\DataTables;

use App\DataTables\BaseDataTable;
use Modules\SFC\Entities\SFCDocument;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Button;
use Illuminate\Support\Facades\File;

class SFCDocumentDataTable extends BaseDataTable
{
    private $editPermission;
    private $deletePermission;
    private $viewPermission;

    public function __construct()
    {
        parent::__construct();
        $this->editPermission = user()->permission('edit_sfc_chart');
        $this->deletePermission = user()->permission('delete_sfc_chart');
        $this->viewPermission = user()->permission('view_sfc_chart');
    }

    /**
     * Build DataTable class.
     */
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="select-table-row" id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" onclick="dataTableRowCheck(' . $row->id . ')">';
            })
            ->addColumn('action', function ($row) {
                $action = '<div class="task_view">
                    <div class="dropdown">
                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                            id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="icon-options-vertical icons"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';

                if ($this->viewPermission == 'all' ||
                    ($this->viewPermission == 'added' && $row->added_by == user()->id)) {
                    $action .= '<a class="dropdown-item" href="' . route('sfc-charts.show', [$row->id]) . '">
                                <i class="fa fa-eye mr-2"></i>
                                ' . trans('app.view') . '
                            </a>';
                }

                if ($this->editPermission == 'all' ||
                    ($this->editPermission == 'added' && $row->added_by == user()->id)) {
                    $action .= '<a class="dropdown-item openRightModal" href="' . route('sfc-charts.edit', [$row->id]) . '">
                                <i class="fa fa-edit mr-2"></i>
                                ' . trans('app.edit') . '
                            </a>';
                }

                if ($this->deletePermission == 'all' ||
                    ($this->deletePermission == 'added' && $row->added_by == user()->id)) {
                    $action .= '<a class="dropdown-item delete-table-row" href="javascript:;" data-sfc-chart-id="' . $row->id . '">
                                <i class="fa fa-trash mr-2"></i>
                                ' . trans('app.delete') . '
                            </a>';
                }

                $action .= '</div>
                    </div>
                </div>';

                return $action;
            })
            ->editColumn('name', function ($row) {
                return '<a href="' . route('sfc-charts.show', [$row->id]) . '" style="color:black;">' . ($row->name ?? 'Document #' . $row->id) . '</a>';
            })
            ->editColumn('headquarter', function ($row) {
                return $row->headquarter ?? '-';
            })
            ->editColumn('total_dr_count', function ($row) {
                return $row->total_dr_count ?? 0;
            })
            ->editColumn('total_visits_monthly', function ($row) {
                return $row->total_visits_monthly ?? 0;
            })
            ->editColumn('abm_approval', function ($row) {
                if ($row->abm_approval) {
                    return '<span class="badge badge-success">' . __('app.approved') . '</span>';
                }
                return '<span class="badge badge-warning">' . __('app.pending') . '</span>';
            })
            ->editColumn('rbm_approval', function ($row) {
                if ($row->rbm_approval) {
                    return '<span class="badge badge-success">' . __('app.approved') . '</span>';
                }
                return '<span class="badge badge-warning">' . __('app.pending') . '</span>';
            })
            ->rawColumns(['action', 'check', 'name', 'abm_approval', 'rbm_approval']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query()
    {
        $request = $this->request();

        $query = SFCDocument::select('sfc_documents.*');

        if ($request->has('name') && $request->name != '') {
            $query->where('sfc_documents.name', 'like', '%' . $request->name . '%');
        }

        if ($request->has('headquarter') && $request->headquarter != '') {
            $query->where('sfc_documents.headquarter', $request->headquarter);
        }

        return $query;
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html()
    {
        return $this->setBuilder('sfc-documents-table')
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["sfc-documents-table"].buttons().container()
                    .appendTo( "#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $("body").tooltip({
                        selector: \'[data-toggle="tooltip"]\'
                    })
                }',
            ])
            ->buttons(Button::make(['extend' => 'excel', 'text' => '<i class="fa fa-file-export"></i> ' . trans('app.exportExcel')]))
            ->minifiedAjax()
            ->orderBy(1)
            ->destroy(true)
            ->responsive()
            ->serverSide()
            ->stateSave(true)
            ->pageLength(companyOrGlobalSetting()->datatable_row_limit ?? 10)
            ->processing()
            ->dom($this->domHtml)
            ->language($this->getLanguage())
            ->columns($this->getColumns());
    }

    /**
     * Get columns.
     */
    protected function getColumns()
    {
        return [
            Column::computed('check', '<input type="checkbox" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">')
                ->exportable(false)
                ->orderable(false)
                ->searchable(false)
                ->width(20),
            Column::make('id')->visible(showId()),
            __('sfc::app.name') => ['data' => 'name', 'name' => 'name', 'title' => __('sfc::app.name')],
            __('sfc::app.headquarter') => ['data' => 'headquarter', 'name' => 'headquarter', 'title' => __('sfc::app.headquarter')],
            __('sfc::app.totalDrCount') => ['data' => 'total_dr_count', 'name' => 'total_dr_count', 'title' => __('sfc::app.totalDrCount')],
            __('sfc::app.totalVisitsMonthly') => ['data' => 'total_visits_monthly', 'name' => 'total_visits_monthly', 'title' => __('sfc::app.totalVisitsMonthly')],
            __('sfc::app.abmApproval') => ['data' => 'abm_approval', 'name' => 'abm_approval', 'title' => __('sfc::app.abmApproval')],
            __('sfc::app.rbmApproval') => ['data' => 'rbm_approval', 'name' => 'rbm_approval', 'title' => __('sfc::app.rbmApproval')],
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->orderable(false)
                ->searchable(false)
                ->width(150)
                ->addClass('text-right pr-20')
        ];
    }

    /**
     * Get language for DataTable
     */
    protected function getLanguage()
    {
        if (user() && File::exists(public_path('i18n/' . user()->locale . '.json'))) {
            return asset('i18n/' . user()->locale . '.json');
        }
        
        return __('app.datatable');
    }
}

