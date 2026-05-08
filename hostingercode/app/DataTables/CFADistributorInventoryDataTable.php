<?php

namespace App\DataTables;

use App\Helpers\PharmaDesignationHelper;
use App\Models\CFADistributorStock;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use App\Helper\UserService;
use Illuminate\Support\Facades\DB;

class CFADistributorInventoryDataTable extends BaseDataTable
{
    private $cfaDistributorId;

    public function __construct($cfaDistributorId = null)
    {
        parent::__construct();
        $this->cfaDistributorId = $cfaDistributorId;
    }

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        $datatables = datatables()->eloquent($query);
        $datatables->addIndexColumn();

        $datatables->editColumn('product.name', function ($row) {
            $name = $row->product_name
                ?? $row->product?->name
                ?? optional(\App\Models\Product::find($row->product_id))->name
                ?? '-';
            if ($row->product_id) {
                return '<a href="' . route('products.show', [$row->product_id]) . '" class="text-dark">' . e($name) . '</a>';
            }
            return e($name);
        });

        $datatables->editColumn('batch', function ($row) {
            if ($row->batch) {
                // Handle comma-separated batches
                $batches = is_string($row->batch) ? explode(', ', $row->batch) : [$row->batch];
                $batchHtml = '';
                foreach ($batches as $batch) {
                    $batchHtml .= '<span class="badge badge-info mr-1">' . trim($batch) . '</span>';
                }
                return $batchHtml;
            }
            return '<span class="text-muted">-</span>';
        });

        $datatables->editColumn('expiry', function ($row) {
            $expiryRaw = $row->expiry_agg ?? null;
            if (!$expiryRaw) {
                return '<span class="text-muted">-</span>';
            }
            // Handle comma-separated expiry dates (aggregated column expiry_agg, not model cast expiry)
            $expiries = is_string($expiryRaw) ? explode(', ', $expiryRaw) : [$expiryRaw];
            $expiryHtml = '';
            foreach ($expiries as $expiryStr) {
                try {
                    $expiryDate = \Carbon\Carbon::parse(trim($expiryStr));
                    $expiryFormatted = $expiryDate->format(company()->date_format);
                    $isExpired = $expiryDate->isPast();
                    $isNearExpiry = $expiryDate->isFuture() && $expiryDate->diffInDays(now()) <= 30;
                    
                    $badgeClass = 'badge-success';
                    if ($isExpired) {
                        $badgeClass = 'badge-danger';
                    } elseif ($isNearExpiry) {
                        $badgeClass = 'badge-warning';
                    }
                    
                    $expiryHtml .= '<span class="badge ' . $badgeClass . ' mr-1">' . $expiryFormatted . '</span>';
                } catch (\Exception $e) {
                    $expiryHtml .= '<span class="badge badge-secondary mr-1">' . trim($expiryStr) . '</span>';
                }
            }
            return $expiryHtml ?: '<span class="text-muted">-</span>';
        });

        $datatables->editColumn('quantity', function ($row) {
            return '<span class="font-weight-bold">' . number_format($row->quantity, 2) . '</span>';
        });

        $datatables->editColumn('available_quantity', function ($row) {
            $percentage = $row->quantity > 0 ? ($row->available_quantity / $row->quantity) * 100 : 0;
            $badgeClass = 'badge-success';
            if ($percentage < 25) {
                $badgeClass = 'badge-danger';
            } elseif ($percentage < 50) {
                $badgeClass = 'badge-warning';
            }
            return '<span class="badge ' . $badgeClass . '">' . number_format($row->available_quantity, 2) . '</span>';
        });

        $datatables->editColumn('pts', function ($row) {
            return $row->pts ? number_format($row->pts, 2) : '<span class="text-muted">-</span>';
        });

        $datatables->editColumn('ptr', function ($row) {
            return $row->ptr ? number_format($row->ptr, 2) : '<span class="text-muted">-</span>';
        });

        $datatables->editColumn('mrp', function ($row) {
            return $row->mrp ? number_format($row->mrp, 2) : '<span class="text-muted">-</span>';
        });

        $datatables->editColumn('cfa_distributor.name', function ($row) {
            $name = $row->cfa_distributor_name ?? null;
            if ($name === null) {
                $distributor = $row->cfaDistributor ?? \App\Models\User::with('clientDetails')->find($row->cfa_distributor_id);
                $name = $distributor
                    ? ($distributor->clientDetails?->company_name ?? $distributor->name ?? '-')
                    : '-';
            }
            if ($row->cfa_distributor_id) {
                return '<a href="' . route('clients.show', [$row->cfa_distributor_id]) . '" class="text-dark">' . e($name) . '</a>';
            }
            return e($name);
        });

        $datatables->editColumn('invoice.invoice_number', function ($row) {
            // Show comma-separated invoice numbers
            if ($row->invoice_numbers) {
                $invoiceNumbers = explode(', ', $row->invoice_numbers);
                $invoiceHtml = '';
                foreach ($invoiceNumbers as $invoiceNumber) {
                    // Try to find invoice ID from invoice number
                    $invoice = \App\Models\Invoice::where('invoice_number', trim($invoiceNumber))
                        ->where('company_id', company()->id)
                        ->first();
                    if ($invoice) {
                        $invoiceHtml .= '<a href="' . route('cfa-distributor-invoices.show', [$invoice->id]) . '" class="badge badge-primary mr-1">' . trim($invoiceNumber) . '</a>';
                    } else {
                        $invoiceHtml .= '<span class="badge badge-secondary mr-1">' . trim($invoiceNumber) . '</span>';
                    }
                }
                return $invoiceHtml;
            }
            return '<span class="text-muted">-</span>';
        });

        $datatables->editColumn('invoice.issue_date', function ($row) {
            $raw = $row->invoice_dates ?? null;
            if (!$raw) {
                return '<span class="text-muted">-</span>';
            }
            $dates = is_string($raw) ? explode(', ', $raw) : [$raw];
            $html = '';
            foreach ($dates as $date) {
                $date = trim($date);
                if ($date === '') {
                    continue;
                }
                try {
                    $formatted = \Carbon\Carbon::parse($date)->format(company()->date_format);
                    $html .= '<span class="badge badge-light mr-1">' . $formatted . '</span>';
                } catch (\Exception $e) {
                    $html .= '<span class="badge badge-secondary mr-1">' . e($date) . '</span>';
                }
            }
            return $html ?: '<span class="text-muted">-</span>';
        });

        $datatables->editColumn('created_at', function ($row) {
            if (!$row->created_at) {
                return '-';
            }
            // Handle both Carbon instance and string date
            $createdAt = is_string($row->created_at) ? \Carbon\Carbon::parse($row->created_at) : $row->created_at;
            return $createdAt->format(company()->date_format . ' ' . company()->time_format);
        });

        $datatables->addColumn('action', function ($row) {
            $action = '<div class="task_view"><div class="dropdown dropup">';
            $action .= '<a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link" id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-options-vertical icons"></i></a>';
            $action .= '<div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '">';
            $action .= '<a href="' . route('cfa-distributor-inventory.batches', ['product_id' => $row->product_id, 'cfa_distributor_id' => $row->cfa_distributor_id]) . '" class="dropdown-item"><i class="fa fa-list mr-2"></i>' . __('app.viewBatches') . '</a>';
            $action .= '</div></div></div>';
            return $action;
        });

        $datatables->rawColumns(['product.name', 'batch', 'expiry', 'quantity', 'available_quantity', 'pts', 'ptr', 'mrp', 'cfa_distributor.name', 'invoice.invoice_number', 'invoice.issue_date', 'created_at', 'action']);

        // Grouped query: ORDER BY raw column names breaks MySQL ONLY_FULL_GROUP_BY (500 on Ajax).
        $datatables->orderColumn('c_f_a_distributor_stocks.id', 'MAX(c_f_a_distributor_stocks.id) $1');
        $datatables->orderColumn('products.name', 'products.name $1');
        $datatables->orderColumn('c_f_a_distributor_stocks.batch', 'MAX(c_f_a_distributor_stocks.batch) $1');
        $datatables->orderColumn('c_f_a_distributor_stocks.expiry', 'MAX(c_f_a_distributor_stocks.expiry) $1');
        $datatables->orderColumn('c_f_a_distributor_stocks.quantity', 'SUM(c_f_a_distributor_stocks.quantity) $1');
        $datatables->orderColumn('c_f_a_distributor_stocks.available_quantity', 'SUM(c_f_a_distributor_stocks.available_quantity) $1');
        $datatables->orderColumn('c_f_a_distributor_stocks.pts', 'MAX(c_f_a_distributor_stocks.pts) $1');
        $datatables->orderColumn('c_f_a_distributor_stocks.ptr', 'MAX(c_f_a_distributor_stocks.ptr) $1');
        $datatables->orderColumn('c_f_a_distributor_stocks.mrp', 'MAX(c_f_a_distributor_stocks.mrp) $1');
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            $datatables->orderColumn('cfa_distributor_name', 'cfa_distributor_name $1');
        }
        $datatables->orderColumn('invoice.invoice_number', 'MAX(invoices.invoice_number) $1');
        $datatables->orderColumn('invoice.issue_date', 'MAX(invoices.issue_date) $1');
        $datatables->orderColumn('c_f_a_distributor_stocks.created_at', 'MAX(c_f_a_distributor_stocks.created_at) $1');

        return $datatables;
    }

    /**
     * Get query source of dataTable.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(CFADistributorStock $model)
    {
        // Build base query with consolidation by product; join products (and distributor for admin) for sort/export and to avoid N+1
        $query = $model->newQuery()
            ->select(
                'c_f_a_distributor_stocks.product_id',
                'c_f_a_distributor_stocks.cfa_distributor_id',
                'products.name as product_name',
                DB::raw('SUM(c_f_a_distributor_stocks.quantity) as quantity'),
                DB::raw('SUM(c_f_a_distributor_stocks.available_quantity) as available_quantity'),
                DB::raw('MAX(c_f_a_distributor_stocks.pts) as pts'),
                DB::raw('MAX(c_f_a_distributor_stocks.ptr) as ptr'),
                DB::raw('MAX(c_f_a_distributor_stocks.mrp) as mrp'),
                DB::raw('MAX(c_f_a_distributor_stocks.dis) as dis'),
                DB::raw('MAX(c_f_a_distributor_stocks.id) as id'),
                DB::raw('MAX(c_f_a_distributor_stocks.created_at) as created_at'),
                DB::raw('GROUP_CONCAT(DISTINCT c_f_a_distributor_stocks.batch ORDER BY c_f_a_distributor_stocks.batch SEPARATOR ", ") as batch'),
                // Alias must NOT be "expiry": model casts expiry to date; GROUP_CONCAT returns a string and breaks hydration.
                DB::raw('GROUP_CONCAT(DISTINCT DATE_FORMAT(c_f_a_distributor_stocks.expiry, "%Y-%m-%d") ORDER BY c_f_a_distributor_stocks.expiry SEPARATOR ", ") as expiry_agg'),
                DB::raw('GROUP_CONCAT(DISTINCT invoices.invoice_number ORDER BY invoices.invoice_number SEPARATOR ", ") as invoice_numbers'),
                DB::raw('GROUP_CONCAT(DISTINCT DATE_FORMAT(invoices.issue_date, "%Y-%m-%d") ORDER BY invoices.issue_date SEPARATOR ", ") as invoice_dates')
            )
            ->leftJoin('invoices', 'invoices.id', '=', 'c_f_a_distributor_stocks.invoice_id')
            ->leftJoin('products', 'products.id', '=', 'c_f_a_distributor_stocks.product_id')
            ->where('c_f_a_distributor_stocks.company_id', company()->id)
            ->groupBy(
                'c_f_a_distributor_stocks.product_id',
                'c_f_a_distributor_stocks.cfa_distributor_id',
                'products.name'
            );

        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            $query->addSelect(DB::raw('MAX(COALESCE(client_details.company_name, users.name)) as cfa_distributor_name'))
                ->leftJoin('users', 'users.id', '=', 'c_f_a_distributor_stocks.cfa_distributor_id')
                ->leftJoin('client_details', 'client_details.user_id', '=', 'users.id');
        }

        // Filter by CFA/Distributor if specified
        $cfaDistributorId = request('cfaDistributorID');
        if ($cfaDistributorId && $cfaDistributorId != 'all') {
            $query->where('c_f_a_distributor_stocks.cfa_distributor_id', $cfaDistributorId);
        } elseif (!PharmaDesignationHelper::hasFullCFAAccess()) {
            // Non-admin/accountant/FSA Executive/MIS Executive users (including clients) see only their own stock
            $query->where('c_f_a_distributor_stocks.cfa_distributor_id', user()->id);
        }

        // Optional: filter by invoice delivery (default = all; previously hard-coded to received only, which hid new stock)
        $deliveryFilter = request('deliveryFilter');
        if ($deliveryFilter === 'received') {
            $query->where(function ($q) {
                $q->where('invoices.delivery_status', 'received');
            });
        } elseif ($deliveryFilter === 'in_transit') {
            $query->where(function ($q) {
                $q->where('invoices.delivery_status', 'in_transit');
            });
        }

        // Filter by stock status (apply after grouping)
        $stockFilter = request('stockFilter');
        if ($stockFilter && $stockFilter != 'all') {
            switch ($stockFilter) {
                case 'available':
                    $query->havingRaw('SUM(c_f_a_distributor_stocks.available_quantity) > 0');
                    break;
                case 'low':
                    $query->havingRaw('(SUM(c_f_a_distributor_stocks.available_quantity) / NULLIF(SUM(c_f_a_distributor_stocks.quantity), 0)) < 0.25');
                    break;
                case 'expired':
                    $query->whereNotNull('c_f_a_distributor_stocks.expiry')
                          ->whereDate('c_f_a_distributor_stocks.expiry', '<', now());
                    break;
                case 'expiring_soon':
                    $query->whereNotNull('c_f_a_distributor_stocks.expiry')
                          ->whereDate('c_f_a_distributor_stocks.expiry', '>=', now())
                          ->whereDate('c_f_a_distributor_stocks.expiry', '<=', now()->addDays(30));
                    break;
            }
        }

        // Search filter (products already joined)
        $searchText = request('searchText');
        if ($searchText) {
            $query->where(function($q) use ($searchText) {
                $q->where('products.name', 'like', '%' . $searchText . '%')
                  ->orWhere('c_f_a_distributor_stocks.batch', 'like', '%' . $searchText . '%')
                  ->orWhere('invoices.invoice_number', 'like', '%' . $searchText . '%');
            });
        }

        return $query;
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        return $this->setBuilder('cfa-distributor-inventory-table', 0)
            ->buttons([
                Button::make('export'),
                Button::make('print'),
                Button::make('reset'),
                Button::make('reload')
            ])
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["cfa-distributor-inventory-table"].buttons().container()
                    .appendTo( "#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $("body").tooltip({
                        selector: \'[data-toggle="tooltip"]\'
                    })
                }',
            ]);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        $columns = [
            __('app.id') => ['data' => 'id', 'name' => 'c_f_a_distributor_stocks.id', 'visible' => false, 'title' => __('app.id')],
            __('app.product') => ['data' => 'product.name', 'name' => 'products.name', 'title' => __('app.product')],
            __('app.batch') => ['data' => 'batch', 'name' => 'c_f_a_distributor_stocks.batch', 'title' => __('app.batch')],
            __('app.expiry') => ['data' => 'expiry', 'name' => 'c_f_a_distributor_stocks.expiry', 'title' => __('app.expiry')],
            __('app.totalQuantity') => ['data' => 'quantity', 'name' => 'c_f_a_distributor_stocks.quantity', 'title' => __('app.totalQuantity')],
            __('app.availableQuantity') => ['data' => 'available_quantity', 'name' => 'c_f_a_distributor_stocks.available_quantity', 'title' => __('app.availableQuantity')],
            __('app.pts') => ['data' => 'pts', 'name' => 'c_f_a_distributor_stocks.pts', 'title' => __('app.pts')],
            __('app.ptr') => ['data' => 'ptr', 'name' => 'c_f_a_distributor_stocks.ptr', 'title' => __('app.ptr')],
            __('app.mrp') => ['data' => 'mrp', 'name' => 'c_f_a_distributor_stocks.mrp', 'title' => __('app.mrp')],
        ];

        // Add CFA/Distributor column only for admin (orderable by alias cfa_distributor_name from query)
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            $columns[__('app.client')] = ['data' => 'cfa_distributor.name', 'name' => 'cfa_distributor_name', 'title' => __('app.client')];
        }

        $columns[__('app.invoice')] = ['data' => 'invoice.invoice_number', 'name' => 'invoice.invoice_number', 'title' => __('app.invoice')];
        $columns[__('app.invoiceDate')] = ['data' => 'invoice.issue_date', 'name' => 'invoice.issue_date', 'title' => __('app.invoiceDate')];
        $columns[__('app.createdAt')] = ['data' => 'created_at', 'name' => 'c_f_a_distributor_stocks.created_at', 'title' => __('app.createdAt')];
        $columns[__('app.action')] = ['data' => 'action', 'name' => 'action', 'title' => __('app.action'), 'orderable' => false, 'searchable' => false, 'exportable' => false, 'printable' => false];

        return $columns;
    }
}

