<?php

namespace Modules\SFC\DataTables;

use App\DataTables\BaseDataTable;
use Modules\SFC\Entities\SFCChart;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Button;
use Illuminate\Support\Facades\File;

class SFCChartDataTable extends BaseDataTable
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
            ->editColumn('town_name', function ($row) {
                return '<a href="' . route('sfc-charts.show', [$row->id]) . '" style="color:black;">' . $row->town_name . '</a>';
            })
            ->editColumn('territory_name', function ($row) {
                return $row->territory_name ?? '-';
            })
            ->editColumn('headquarter', function ($row) {
                return $row->headquarter ?? '-';
            })
            ->editColumn('total_km', function ($row) {
                return $row->total_km ? number_format($row->total_km, 2) : '-';
            })
            ->editColumn('two_way_fare', function ($row) {
                return $row->two_way_fare ? currency_format($row->two_way_fare) : '-';
            })
            ->editColumn('one_way_fare', function ($row) {
                return $row->one_way_fare ? currency_format($row->one_way_fare) : '-';
            })
            ->editColumn('total_dr_count', function ($row) {
                return $row->total_dr_count ?? 0;
            })
            ->rawColumns(['action', 'check', 'town_name']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query()
    {
        $request = $this->request();

        $query = SFCChart::select('sfc_charts.*');

        if ($request->has('territory_name') && $request->territory_name != '') {
            $query->where('sfc_charts.territory_name', $request->territory_name);
        }

        if ($request->has('headquarter') && $request->headquarter != '') {
            $query->where('sfc_charts.headquarter', $request->headquarter);
        }

        return $query;
    }

    /**
     * Optional method if you want to use html builder.
     */
    public function html()
    {
        return $this->setBuilder('sfc-charts-table')
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["sfc-charts-table"].buttons().container()
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
            Column::make('town_name')->title(__('sfc::app.townName')),
            Column::make('territory_name')->title(__('sfc::app.territoryName')),
            Column::make('headquarter')->title(__('sfc::app.headquarter')),
            Column::make('covered_from')->title(__('sfc::app.coveredFrom')),
            Column::make('total_km')->title(__('sfc::app.totalKm')),
            Column::make('two_way_fare')->title(__('sfc::app.twoWayFare')),
            Column::make('one_way_fare')->title(__('sfc::app.oneWayFare')),
            Column::make('mode_of_travel')->title(__('sfc::app.modeOfTravel')),
            Column::make('total_dr_count')->title(__('sfc::app.totalDrCount')),
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

