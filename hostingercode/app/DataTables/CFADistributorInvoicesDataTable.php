<?php

namespace App\DataTables;

use App\Models\Invoice;
use App\Models\CustomField;
use App\Models\CustomFieldGroup;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;
use App\Helper\UserService;
use App\Helper\Common;
use Illuminate\Support\Facades\Schema;

class CFADistributorInvoicesDataTable extends BaseDataTable
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
        $this->viewInvoicePermission = user()->permission('view_invoices');
        $this->deleteInvoicePermission = user()->permission('delete_invoices');
        $this->editInvoicePermission = user()->permission('edit_invoices');
        $this->addPaymentPermission = user()->permission('add_payments');
        $this->addInvoicesPermission = user()->permission('add_invoices');
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

            $action .= '<a href="' . route('cfa-distributor-invoices.show', [$row->id]) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';

            if (
                $this->viewInvoicePermission == 'all'
                || ($this->viewInvoicePermission == 'added' && ($userId == $row->added_by || user()->id == $row->added_by))
                || ($this->viewInvoicePermission == 'owned' && $userId == $row->client_id)
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

            if (
                ($this->editInvoicePermission == 'all' || ($this->editInvoicePermission == 'added' && ($userId == $row->added_by || user()->id == $row->added_by)))
                && $row->status != 'paid'
            ) {
                $action .= '<a href="' . route('cfa-distributor-invoices.edit', [$row->id]) . '" class="dropdown-item"><i class="fa fa-edit mr-2"></i>' . __('app.edit') . '</a>';
            }

            if ($this->deleteInvoicePermission == 'all' || ($this->deleteInvoicePermission == 'added' && ($userId == $row->added_by || user()->id == $row->added_by))) {
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
            return '<a href="' . route('cfa-distributor-invoices.show', [$row->id]) . '" class="text-dark">' . $row->invoice_number . '</a>';
        });

        $datatables->editColumn('name', function ($row) {
            if ($row->client && $row->client->clientDetails) {
                return $row->client->clientDetails->company_name ?? $row->client->name;
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

        $datatables->editColumn('delivery_status', function ($row) {
            $isReceived = ($row->delivery_status ?? 'in_transit') == 'received';
            $statusText = $isReceived ? 'Received' : 'In Transit';
            $statusClass = $isReceived ? 'success' : 'warning';
            
            return '<div class="d-flex align-items-center">
                <label class="switch switch-sm mr-2">
                    <input type="checkbox" class="delivery-status-toggle" 
                           data-invoice-id="' . $row->id . '" 
                           ' . ($isReceived ? 'checked' : '') . '>
                    <span class="slider round"></span>
                </label>
                <span class="badge badge-' . $statusClass . '">' . $statusText . '</span>
            </div>';
        });

        $datatables->addColumn('invoice', function ($row) {
            return $row->invoice_number;
        });

        $datatables->addColumn('client_name', function ($row) {
            return $row->client ? $row->client->name : '--';
        });

        $datatables->addColumn('client_email', function ($row) {
            return $row->client ? $row->client->email : '--';
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

        $datatables->rawColumns(['invoice_number', 'status', 'action', 'delivery_status']);

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
        $hasPurchaseEntryColumn = Schema::hasColumn('invoice_items', 'purchase_entry_id');

        $model = Invoice::with(
            [
                'currency:id,currency_symbol,currency_code',
                'client',
                'client.clientDetails',
                'payment',
                'cfaDistributorStocks',
                'items' => function($query) {
                    $query->select('id', 'invoice_id', 'product_id', 'type')
                          ->where('type', 'item');
                },
                'items.product' => function($query) {
                    $query->select('id');
                },
            ]
        )
            ->join('users', 'users.id', '=', 'invoices.client_id')
            ->join('client_details', 'users.id', '=', 'client_details.user_id')
            ->leftJoin('c_f_a_distributor_stocks', 'invoices.id', '=', 'c_f_a_distributor_stocks.invoice_id')
            ->leftJoin('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->leftJoin('client_areas', 'client_details.id', '=', 'client_areas.client_detail_id')
            ->leftJoin('client_categories', 'client_details.category_id', '=', 'client_categories.id')
            ->where('invoices.company_id', company()->id)
            ->where(function($query) use ($hasPurchaseEntryColumn) {
                $query->whereNotNull('c_f_a_distributor_stocks.invoice_id')
                      ->orWhereNotNull('client_areas.area_id')
                      ->orWhere(function($q) {
                          $q->whereRaw('LOWER(client_categories.category_name) LIKE ?', ['%cfa%'])
                            ->orWhereRaw('LOWER(client_categories.category_name) LIKE ?', ['%distributor%']);
                      });
                      
                if ($hasPurchaseEntryColumn) {
                    $query->orWhereNotNull('invoice_items.purchase_entry_id');
                }
            })
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
                'invoices.delivery_status',
            ])
            ->addSelect('invoices.company_id')
            ->distinct();

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

        if ($request->clientID != 'all' && !is_null($request->clientID)) {
            $model = $model->where('invoices.client_id', '=', $request->clientID);
        }

        $safeTerm = Common::safeString(request('searchText'));
        if ($request->searchText != '') {
            $model->where(function ($query) use ($safeTerm) {
                $query->where('invoices.invoice_number', 'like', '%' . $safeTerm . '%')
                    ->orWhere('invoices.custom_invoice_number', 'like', '%' . $safeTerm . '%')
                    ->orWhere('invoices.id', 'like', '%' . $safeTerm . '%')
                    ->orWhere('invoices.total', 'like', '%' . $safeTerm . '%')
                    ->orWhere('client_details.company_name', 'like', '%' . $safeTerm . '%')
                    ->orWhereHas('client', function ($q) use ($safeTerm) {
                        $q->where('name', 'like', '%' . $safeTerm . '%');
                    })
                    ->orWhere(function ($query) use ($safeTerm) {
                        $query->where('invoices.status', 'like', '%' . $safeTerm . '%');
                    });
            });
        }

        if (in_array('client', user_roles())) {
            // CFA distributors (client role) should always see their own CFA invoices,
            // regardless of send_status — they are the consignee, not a regular customer.
            $model = $model->where('invoices.client_id', $userId);
        }

        if ($this->viewInvoicePermission == 'added') {
            $model = $model->where('invoices.added_by', $userId);
        }

        if ($this->viewInvoicePermission == 'owned') {
            $model = $model->where('invoices.client_id', $userId);
        }

        if ($this->viewInvoicePermission == 'both') {
            $model = $model->where(function($q) use ($userId) {
                $q->where('invoices.client_id', $userId)
                  ->orWhere('invoices.added_by', $userId);
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
        $dataTable = $this->setBuilder('cfa-distributor-invoices-table', 0)
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["cfa-distributor-invoices-table"].buttons().container()
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
            __('CFA / Distributor') => ['data' => 'name', 'name' => 'client.name', 'exportable' => false, 'title' => __('CFA / Distributor')],
            __('app.customers') => ['data' => 'client_name', 'name' => 'client.name', 'visible' => false, 'title' => __('app.customers')],
            __('app.email') => ['data' => 'client_email', 'name' => 'client.email', 'visible' => false, 'title' => __('app.email')],
            __('modules.invoices.invoiceDate') => ['data' => 'issue_date', 'name' => 'invoices.issue_date', 'title' => __('modules.invoices.invoiceDate')],
            __('app.dueDate') => ['data' => 'due_date', 'name' => 'invoices.due_date', 'title' => __('app.dueDate')],
            __('modules.invoices.total') => ['data' => 'total', 'name' => 'invoices.total', 'class' => 'text-right', 'exportable' => false, 'visible' => true, 'title' => __('modules.invoices.total')],
            __('modules.invoices.total') . ' ' . __('modules.invoices.amount') => ['data' => 'export_total', 'name' => 'export_total', 'visible' => false, 'exportable' => true, 'title' => __('modules.invoices.total') . ' ' . __('modules.invoices.amount')],
            __('modules.invoices.paid') => ['data' => 'export_paid', 'name' => 'paid', 'visible' => false, 'title' => __('modules.invoices.paid') . ' ' . __('modules.invoices.amount')],
            __('modules.invoices.unpaid') => ['data' => 'export_unpaid', 'name' => 'unpaid', 'visible' => false, 'title' => __('modules.invoices.unpaid') . ' ' . __('modules.invoices.amount')],
            __('app.status') => ['data' => 'status', 'name' => 'invoices.status', 'width' => '10%', 'title' => __('app.status')],
            __('Delivery Status') => ['data' => 'delivery_status', 'name' => 'invoices.delivery_status', 'width' => '12%', 'title' => __('Delivery Status'), 'orderable' => false, 'searchable' => false]
        ];

        $action = [
            Column::computed('action', __('app.action'))
                ->exportable(false)
                ->printable(false)
                ->orderable(false)
                ->searchable(false)
                ->addClass('text-right pr-20')
        ];

        // Get custom fields and hide Mfr column
        $customFields = CustomFieldGroup::customFieldsDataMerge(new Invoice());
        
        // Remove Mfr column completely - check all possible variations
        $fieldsToRemove = [];
        foreach ($customFields as $key => $field) {
            $fieldKey = strtolower($key);
            $fieldTitle = strtolower($field['title'] ?? '');
            
            // Mark for removal if key or title matches Mfr, MFR, Manufacturer, or any variation
            if ($fieldKey === 'mfr' || 
                $fieldTitle === 'mfr' || 
                $fieldTitle === 'manufacturer' ||
                strpos($fieldTitle, 'mfr') !== false ||
                strpos($fieldKey, 'mfr') !== false) {
                $fieldsToRemove[] = $key;
            }
        }
        
        // Remove the Mfr fields from the array
        foreach ($fieldsToRemove as $key) {
            unset($customFields[$key]);
        }

        return array_merge($data, $customFields, $action);
    }
}

