<?php

namespace App\DataTables;

use App\Helpers\PharmaDesignationHelper;
use App\Models\Invoice;
use App\Models\CustomField;
use App\Models\CustomFieldGroup;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;
use App\Helper\UserService;
use App\Helper\Common;
use Illuminate\Support\Facades\Schema;

class CFAStockistInvoicesDataTable extends BaseDataTable
{
    protected $firstInvoice;
    private $viewInvoicePermission;
    private $deleteInvoicePermission;
    private $editInvoicePermission;
    private $addPaymentPermission;
    private $addInvoicesPermission;

    public function __construct()
    {
        parent::__construct();
        $this->viewInvoicePermission = user()->permission('view_cfa_stockist_invoices');
        $this->deleteInvoicePermission = user()->permission('delete_cfa_stockist_invoices');
        $this->editInvoicePermission = user()->permission('edit_cfa_stockist_invoices');
        $this->addPaymentPermission = user()->permission('add_payments');
        $this->addInvoicesPermission = user()->permission('add_cfa_stockist_invoices');
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
        $firstInvoice = $this->firstInvoice;
        $datatables = datatables()->eloquent($query);
        $datatables->addIndexColumn();
        $datatables->addColumn('action', function ($row) use ($firstInvoice, $userId) {
            $action = '<div class="task_view">

                <div class="dropdown dropup">
                    <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link"
                        id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="icon-options-vertical icons"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '" tabindex="0">';

            $action .= '<a href="' . route('cfa-stockist-invoices.show', [$row->id]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';

            if (
                $this->viewInvoicePermission == 'all'
                || ($this->viewInvoicePermission == 'added' && ($userId == $row->added_by || user()->id == $row->added_by))
                || ($this->viewInvoicePermission == 'owned' && $userId == $row->client_id)
                || ($this->viewInvoicePermission == 'both' && ($userId == $row->added_by || user()->id == $row->added_by || $userId == $row->client_id))
            ) {
                $action .= '<a class="dropdown-item" href="' . route('invoices.download', [$row->id]) . '">
                                <i class="fa fa-download mr-2"></i>
                                ' . trans('app.download') . '
                            </a>';
                $action .= '<a class="dropdown-item" target="_blank" href="' . route('invoices.download', [$row->id, 'view' => true]) . '">
                                <i class="fa fa-eye mr-2"></i>
                                ' . trans('app.viewPdf') . '
                            </a>';
            }

            if ($row->status != 'canceled' && !in_array('client', user_roles()) && $row->credit_note == 0) {
                $action .= '<a class="dropdown-item sendButton" href="javascript:;" data-toggle="tooltip"  data-invoice-id="' . $row->id . '" data-amt="' . (($row->total == 0 && $row->status != 'paid') ? 0 : 1) . '" >
                                <i class="fa fa-paper-plane mr-2"></i>
                                ' . trans('app.send') . '
                            </a>';
            }

            if (PharmaDesignationHelper::canEditCfaStockistInvoiceRow($row)) {
                $action .= '<a class="dropdown-item" href="' . route('cfa-stockist-invoices.edit', [$row->id]) . '"><i class="fa fa-edit mr-2"></i>' . trans('app.edit') . '</a>';
            }

            if (PharmaDesignationHelper::canDeleteCfaStockistInvoiceRow($row)) {
                $action .= '<a class="dropdown-item delete-table-row" href="javascript:;" data-invoice-id="' . $row->id . '">
                                <i class="fa fa-trash mr-2"></i>
                                ' . trans('app.delete') . '
                            </a>';
            }

            $action .= '</div>
                    </div>
                </div>';

            return $action;
        });

        $datatables->editColumn('invoice_number', function ($row) {
            return '<a href="' . route('cfa-stockist-invoices.show', [$row->id]) . '" class="text-dark">' . $row->invoice_number . '</a>';
        });

        $datatables->editColumn('name', function ($row) {
            $s = $row->cfaStockist;
            if ($s) {
                $label = $s->shopname ?? $s->fullname ?? '';
                if ($s->cfa_stockist_id) {
                    $label = trim($s->cfa_stockist_id . ($label !== '' ? ' - ' . $label : ''));
                }

                return $label !== '' ? $label : '--';
            }

            return '--';
        });

        $datatables->editColumn('status', function ($row) {
            $status = '';
            $canUpdatePayment = ($this->addPaymentPermission == 'all' || ($this->addPaymentPermission == 'added' && $row->added_by == user()->id));

            if ($row->status == 'paid') {
                // Paid invoices - clickable to view payment history (read-only)
                if ($canUpdatePayment) {
                    $status = '<span class="badge badge-success payment-status-badge" style="cursor: pointer;" 
                                    data-invoice-id="' . $row->id . '" 
                                    data-current-status="paid" 
                                    title="Click to view payment history">' . __('app.paid') . '</span>';
                } else {
                    $status = '<span class="badge badge-success">' . __('app.paid') . '</span>';
                }
            } elseif ($row->status == 'unpaid') {
                if ($canUpdatePayment) {
                    $status = '<span class="badge badge-danger payment-status-badge" style="cursor: pointer;" 
                                    data-invoice-id="' . $row->id . '" 
                                    data-current-status="unpaid" 
                                    title="Click to add payment">' . __('app.unpaid') . '</span>';
                } else {
                    $status = '<span class="badge badge-danger">' . __('app.unpaid') . '</span>';
                }
            } elseif ($row->status == 'partial') {
                if ($canUpdatePayment) {
                    $status = '<span class="badge badge-warning payment-status-badge" style="cursor: pointer;" 
                                    data-invoice-id="' . $row->id . '" 
                                    data-current-status="partial" 
                                    title="Click to add payment">' . __('app.partial') . '</span>';
                } else {
                    $status = '<span class="badge badge-warning">' . __('app.partial') . '</span>';
                }
            } elseif ($row->status == 'canceled') {
                $status = '<span class="badge badge-secondary">' . __('app.canceled') . '</span>';
            } else {
                $status = '<span class="badge badge-info">' . ucfirst($row->status) . '</span>';
            }

            return $status;
        });

        $datatables->editColumn('total', function ($row) {
            $currencySymbol = $row->currency ? $row->currency->currency_symbol : '';
            return $currencySymbol . number_format($row->total, 2);
        });

        $datatables->editColumn('issue_date', function ($row) {
            return $row->issue_date->format(company()->date_format);
        });

        $datatables->editColumn('due_date', function ($row) {
            return $row->due_date->format(company()->date_format);
        });

        $datatables->addColumn('invoice', function ($row) {
            return $row->invoice_number;
        });

        $datatables->addColumn('stockist_name', function ($row) {
            $s = $row->cfaStockist;
            if (! $s) {
                return '--';
            }
            $label = $s->shopname ?? $s->fullname ?? '--';

            return $label;
        });

        $datatables->addColumn('export_total', function ($row) {
            return $row->total;
        });

        $datatables->addColumn('export_paid', function ($row) {
            return $row->getPaidAmount();
        });

        $datatables->addColumn('export_unpaid', function ($row) {
            return $row->amountDue();
        });

        $datatables->rawColumns(['invoice_number', 'status', 'action']);

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
        $this->firstInvoice = Invoice::orderBy('id', 'desc')->first();
        $userId = UserService::getUserId();

        $model = Invoice::with(
            [
                'currency:id,currency_symbol,currency_code',
                'cfaStockistStocks',
                'cfaStockistStocks.cfaStockist',
                'items' => function($query) {
                    $query->select('id', 'invoice_id', 'product_id', 'type')
                          ->where('type', 'item');
                },
                'items.product' => function($query) {
                    $query->select('id');
                },
            ]
        )
            ->leftJoin('cfa_stockist_stocks', 'invoices.id', '=', 'cfa_stockist_stocks.invoice_id')
            ->where('invoices.company_id', company()->id)
            ->whereNotNull('cfa_stockist_stocks.invoice_id')
            ->select([
                'invoices.id',
                'invoices.due_amount',
                'invoices.client_id',
                'invoices.invoice_number',
                'invoices.currency_id',
                'invoices.total',
                'invoices.status',
                'invoices.issue_date',
                'invoices.due_date',
                'invoices.credit_note',
                'invoices.send_status',
                'invoices.added_by',
                'invoices.hash',
                'invoices.custom_invoice_number',
            ])
            ->addSelect('invoices.company_id')
            ->distinct();

        // Filter by CFA/Distributor if user is a CFA/Distributor (not admin, accountant, FSA Executive, or MIS Executive)
        // Also allow clients to see their own invoices
        if (!PharmaDesignationHelper::hasFullCFAAccess()) {
            if (in_array('client', user_roles())) {
                // Client can see their own invoices
                $model = $model->where('invoices.client_id', $userId);
            } else {
                // Check if user is a CFA/Distributor
                $model = $model->where('cfa_stockist_stocks.cfa_distributor_id', $userId);
            }
        }

        if ($request->startDate !== null && $request->startDate != 'null' && $request->startDate != '') {
            $startDate = companyToDateString($request->startDate);
            $model = $model->where(DB::raw('DATE(invoices.`issue_date`)'), '>=', $startDate);
        }

        if ($request->endDate !== null && $request->endDate != 'null' && $request->endDate != '') {
            $endDate = companyToDateString($request->endDate);
            $model = $model->where(DB::raw('DATE(invoices.`issue_date`)'), '<=', $endDate);
        }

        if ($request->status != 'all' && !is_null($request->status)) {
            if ($request->status == 'pending') {
                $model = $model->where(function ($q) {
                    $q->where('invoices.status', '=', 'unpaid')
                      ->orWhere('invoices.status', '=', 'partial');
                });
            } else {
                $model = $model->where('invoices.status', '=', $request->status);
            }
            $model = $model->where('invoices.credit_note', 0);
        }

        if ($request->stockistID != 'all' && !is_null($request->stockistID)) {
            $model = $model->where('cfa_stockist_stocks.cfa_stockist_id', '=', $request->stockistID);
        }

        $safeTerm = Common::safeString(request('searchText'));
        if ($request->searchText != '') {
            $model->where(function ($query) use ($safeTerm) {
                $query->where('invoices.invoice_number', 'like', '%' . $safeTerm . '%')
                    ->orWhere('invoices.custom_invoice_number', 'like', '%' . $safeTerm . '%')
                    ->orWhere('invoices.id', 'like', '%' . $safeTerm . '%')
                    ->orWhere('invoices.total', 'like', '%' . $safeTerm . '%')
                    ->orWhereHas('cfaStockistStocks.cfaStockist', function ($q) use ($safeTerm) {
                        $q->where('shopname', 'like', '%' . $safeTerm . '%')
                            ->orWhere('fullname', 'like', '%' . $safeTerm . '%')
                            ->orWhere('cfa_stockist_id', 'like', '%' . $safeTerm . '%');
                    })
                    ->orWhere(function ($query) use ($safeTerm) {
                        $query->where('invoices.status', 'like', '%' . $safeTerm . '%');
                    });
            });
        }

        if ($this->viewInvoicePermission == 'added') {
            $model = $model->where('invoices.added_by', $userId);
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
        $dataTable = $this->setBuilder('cfa-stockist-invoices-table', 0)
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["cfa-stockist-invoices-table"].buttons().container()
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
            __('app.id') => ['data' => 'id', 'name' => 'invoices.id', 'visible' => false, 'title' => __('app.id')],
            __('app.invoice') . '#' => ['data' => 'invoice_number', 'name' => 'invoices.invoice_number', 'exportable' => false, 'title' => __('app.invoice')],
            __('app.invoiceNumber') . '#' => ['data' => 'invoice', 'name' => 'invoices.invoice_number', 'visible' => false, 'title' => __('app.invoiceNumber')],
            // Virtual columns — use unique `name` + orderable false (no `invoices.name` / `stockist_name` SQL)
            __('CFA Stockist') => ['data' => 'name', 'name' => 'cfa_stockist_label', 'exportable' => false, 'orderable' => false, 'searchable' => false, 'title' => __('CFA Stockist')],
            __('app.customers') => ['data' => 'stockist_name', 'name' => 'cfa_stockist_export_name', 'visible' => false, 'orderable' => false, 'searchable' => false, 'title' => __('app.customers')],
            __('modules.invoices.invoiceDate') => ['data' => 'issue_date', 'name' => 'invoices.issue_date', 'title' => __('modules.invoices.invoiceDate')],
            __('app.dueDate') => ['data' => 'due_date', 'name' => 'invoices.due_date', 'title' => __('app.dueDate')],
            __('modules.invoices.total') => ['data' => 'total', 'name' => 'invoices.total', 'class' => 'text-right', 'exportable' => false, 'visible' => true, 'title' => __('modules.invoices.total')],
            __('modules.invoices.total') . ' ' . __('modules.invoices.amount') => ['data' => 'export_total', 'name' => 'invoices.total', 'visible' => false, 'exportable' => true, 'orderable' => false, 'title' => __('modules.invoices.total') . ' ' . __('modules.invoices.amount')],
            __('modules.invoices.paid') => ['data' => 'export_paid', 'name' => 'cfa_export_paid', 'visible' => false, 'orderable' => false, 'searchable' => false, 'title' => __('modules.invoices.paid') . ' ' . __('modules.invoices.amount')],
            __('modules.invoices.unpaid') => ['data' => 'export_unpaid', 'name' => 'cfa_export_unpaid', 'visible' => false, 'orderable' => false, 'searchable' => false, 'title' => __('modules.invoices.unpaid') . ' ' . __('modules.invoices.amount')],
            __('app.status') => ['data' => 'status', 'name' => 'invoices.status', 'width' => '10%', 'title' => __('app.status')],
        ];

        $action = [
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-right pr-20')
        ];

        return array_merge($data, CustomFieldGroup::customFieldsDataMerge(new Invoice()), $action);
    }
}

