<?php

namespace App\DataTables;

use App\Helpers\PharmaDesignationHelper;
use App\Models\CFAStockistStock;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;

class CFAStockistInventoryDataTable extends BaseDataTable
{
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
            $name = $row->product_name ?? ($row->product->name ?? \App\Models\Product::find($row->product_id)->name ?? '-');
            return '<a href="' . route('products.show', [$row->product_id]) . '" class="text-dark">' . e($name) . '</a>';
        });

        $datatables->editColumn('batch', function ($row) {
            if ($row->batch) {
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
            if (!$row->expiry) {
                return '<span class="text-muted">-</span>';
            }
            $expiries = is_string($row->expiry) ? explode(', ', $row->expiry) : [$row->expiry];
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

        $datatables->editColumn('pts', function ($row) {
            return $row->pts ? number_format($row->pts, 2) : '<span class="text-muted">-</span>';
        });

        $datatables->editColumn('ptr', function ($row) {
            return $row->ptr ? number_format($row->ptr, 2) : '<span class="text-muted">-</span>';
        });

        $datatables->editColumn('mrp', function ($row) {
            return $row->mrp ? number_format($row->mrp, 2) : '<span class="text-muted">-</span>';
        });

        $datatables->editColumn('cfa_stockist.name', function ($row) {
            $name = $row->stockist_name ?? null;
            if ($name === null) {
                $stockist = $row->cfaStockist ?? \App\Models\CFAStockist::find($row->cfa_stockist_id);
                $name = $stockist ? ($stockist->shopname ?? $stockist->fullname ?? '-') : '-';
            }
            return '<a href="' . route('cfa-stockists.show', [$row->cfa_stockist_id]) . '" class="text-dark">' . e($name) . '</a>';
        });

        $datatables->editColumn('cfa_distributor.name', function ($row) {
            $name = $row->cfa_distributor_name ?? null;
            if ($name === null) {
                $distributor = $row->cfaDistributor ?? \App\Models\User::with('clientDetails')->find($row->cfa_distributor_id);
                $name = $distributor ? ($distributor->clientDetails->company_name ?? $distributor->name ?? '-') : '-';
            }
            return '<a href="' . route('clients.show', [$row->cfa_distributor_id]) . '" class="text-dark">' . e($name) . '</a>';
        });

        $datatables->editColumn('invoice.invoice_number', function ($row) {
            if ($row->invoice_numbers) {
                $invoiceNumbers = explode(', ', $row->invoice_numbers);
                $invoiceHtml = '';
                foreach ($invoiceNumbers as $invoiceNumber) {
                    $invoice = \App\Models\Invoice::where('invoice_number', trim($invoiceNumber))
                        ->where('company_id', company()->id)
                        ->first();
                    if ($invoice) {
                        $invoiceHtml .= '<a href="' . route('cfa-stockist-invoices.show', [$invoice->id]) . '" class="badge badge-primary mr-1">' . trim($invoiceNumber) . '</a>';
                    } else {
                        $invoiceHtml .= '<span class="badge badge-secondary mr-1">' . trim($invoiceNumber) . '</span>';
                    }
                }
                return $invoiceHtml;
            }
            return '<span class="text-muted">-</span>';
        });

        $datatables->editColumn('created_at', function ($row) {
            if (!$row->created_at) {
                return '-';
            }
            $createdAt = is_string($row->created_at) ? \Carbon\Carbon::parse($row->created_at) : $row->created_at;
            return $createdAt->format(company()->date_format . ' ' . company()->time_format);
        });

        $datatables->addColumn('action', function ($row) {
            $action = '<div class="task_view"><div class="dropdown dropup">';
            $action .= '<a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link" id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-options-vertical icons"></i></a>';
            $action .= '<div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '">';
            $action .= '<a href="' . route('cfa-stockist-inventory.batches', ['product_id' => $row->product_id, 'cfa_stockist_id' => $row->cfa_stockist_id]) . '" class="dropdown-item"><i class="fa fa-list mr-2"></i>' . __('app.viewBatches') . '</a>';
            $action .= '</div></div></div>';
            return $action;
        });

        $rawColumns = ['product.name', 'batch', 'expiry', 'quantity', 'pts', 'ptr', 'mrp', 'invoice.invoice_number', 'created_at', 'action'];
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            $rawColumns[] = 'cfa_distributor.name';
        }
        $rawColumns[] = 'cfa_stockist.name';
        $datatables->rawColumns($rawColumns);

        return $datatables;
    }

    /**
     * Get query source of dataTable.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(CFAStockistStock $model)
    {
        $query = $model->newQuery()
            ->select(
                'cfa_stockist_stocks.product_id',
                'cfa_stockist_stocks.cfa_stockist_id',
                'cfa_stockist_stocks.cfa_distributor_id',
                'products.name as product_name',
                'cfa_stockists.shopname as stockist_name',
                DB::raw('SUM(cfa_stockist_stocks.quantity) as quantity'),
                DB::raw('MAX(cfa_stockist_stocks.pts) as pts'),
                DB::raw('MAX(cfa_stockist_stocks.ptr) as ptr'),
                DB::raw('MAX(cfa_stockist_stocks.mrp) as mrp'),
                DB::raw('MAX(cfa_stockist_stocks.dis) as dis'),
                DB::raw('MAX(cfa_stockist_stocks.id) as id'),
                DB::raw('MAX(cfa_stockist_stocks.created_at) as created_at'),
                DB::raw('GROUP_CONCAT(DISTINCT cfa_stockist_stocks.batch ORDER BY cfa_stockist_stocks.batch SEPARATOR ", ") as batch'),
                DB::raw('GROUP_CONCAT(DISTINCT DATE_FORMAT(cfa_stockist_stocks.expiry, "%Y-%m-%d") ORDER BY cfa_stockist_stocks.expiry SEPARATOR ", ") as expiry'),
                DB::raw('GROUP_CONCAT(DISTINCT invoices.invoice_number ORDER BY invoices.invoice_number SEPARATOR ", ") as invoice_numbers')
            )
            ->leftJoin('invoices', 'invoices.id', '=', 'cfa_stockist_stocks.invoice_id')
            ->leftJoin('products', 'products.id', '=', 'cfa_stockist_stocks.product_id')
            ->leftJoin('cfa_stockists', 'cfa_stockists.id', '=', 'cfa_stockist_stocks.cfa_stockist_id')
            ->where('cfa_stockist_stocks.company_id', company()->id)
            ->groupBy(
                'cfa_stockist_stocks.product_id',
                'cfa_stockist_stocks.cfa_stockist_id',
                'cfa_stockist_stocks.cfa_distributor_id',
                'products.name',
                'cfa_stockists.shopname'
            );

        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            $query->addSelect(DB::raw('MAX(COALESCE(client_details.company_name, users.name)) as cfa_distributor_name'))
                ->leftJoin('users', 'users.id', '=', 'cfa_stockist_stocks.cfa_distributor_id')
                ->leftJoin('client_details', 'client_details.user_id', '=', 'users.id');
        }

        $cfaStockistId = request('cfaStockistID');
        if ($cfaStockistId && $cfaStockistId != 'all') {
            $query->where('cfa_stockist_stocks.cfa_stockist_id', $cfaStockistId);
        } elseif (!PharmaDesignationHelper::hasFullCFAAccess()) {
            $query->whereIn('cfa_stockist_stocks.cfa_stockist_id', function ($q) {
                $q->select('cfa_stockist_id')->from('cfa_distributor_stockist')
                    ->where('cfa_distributor_id', user()->id);
            });
        }

        $cfaDistributorId = request('cfaDistributorID');
        if ($cfaDistributorId && $cfaDistributorId != 'all') {
            $query->where('cfa_stockist_stocks.cfa_distributor_id', $cfaDistributorId);
        }

        $stockFilter = request('stockFilter');
        if ($stockFilter && $stockFilter != 'all') {
            switch ($stockFilter) {
                case 'available':
                    $query->havingRaw('SUM(cfa_stockist_stocks.quantity) > 0');
                    break;
                case 'expired':
                    $query->whereNotNull('cfa_stockist_stocks.expiry')
                        ->whereDate('cfa_stockist_stocks.expiry', '<', now());
                    break;
                case 'expiring_soon':
                    $query->whereNotNull('cfa_stockist_stocks.expiry')
                        ->whereDate('cfa_stockist_stocks.expiry', '>=', now())
                        ->whereDate('cfa_stockist_stocks.expiry', '<=', now()->addDays(30));
                    break;
            }
        }

        $searchText = request('searchText');
        if ($searchText) {
            $query->where(function ($q) use ($searchText) {
                $q->where('products.name', 'like', '%' . $searchText . '%')
                    ->orWhere('cfa_stockist_stocks.batch', 'like', '%' . $searchText . '%')
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
        return $this->setBuilder('cfa-stockist-inventory-table', 0)
            ->buttons([
                Button::make('export'),
                Button::make('print'),
                Button::make('reset'),
                Button::make('reload')
            ])
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["cfa-stockist-inventory-table"].buttons().container()
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
            __('app.id') => ['data' => 'id', 'name' => 'cfa_stockist_stocks.id', 'visible' => false, 'title' => __('app.id')],
            __('app.product') => ['data' => 'product.name', 'name' => 'products.name', 'title' => __('app.product')],
            __('app.batch') => ['data' => 'batch', 'name' => 'cfa_stockist_stocks.batch', 'title' => __('app.batch')],
            __('app.expiry') => ['data' => 'expiry', 'name' => 'cfa_stockist_stocks.expiry', 'title' => __('app.expiry')],
            __('app.totalQuantity') => ['data' => 'quantity', 'name' => 'cfa_stockist_stocks.quantity', 'title' => __('app.totalQuantity')],
            __('app.pts') => ['data' => 'pts', 'name' => 'cfa_stockist_stocks.pts', 'title' => __('app.pts')],
            __('app.ptr') => ['data' => 'ptr', 'name' => 'cfa_stockist_stocks.ptr', 'title' => __('app.ptr')],
            __('app.mrp') => ['data' => 'mrp', 'name' => 'cfa_stockist_stocks.mrp', 'title' => __('app.mrp')],
        ];

        $columns[__('app.cfaStockist')] = ['data' => 'cfa_stockist.name', 'name' => 'stockist_name', 'title' => __('app.cfaStockist')];

        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            $columns[__('app.client')] = ['data' => 'cfa_distributor.name', 'name' => 'cfa_distributor_name', 'title' => __('app.client')];
        }

        $columns[__('app.invoice')] = ['data' => 'invoice.invoice_number', 'name' => 'invoice.invoice_number', 'title' => __('app.invoice')];
        $columns[__('app.createdAt')] = ['data' => 'created_at', 'name' => 'cfa_stockist_stocks.created_at', 'title' => __('app.createdAt')];
        $columns[__('app.action')] = ['data' => 'action', 'name' => 'action', 'title' => __('app.action'), 'orderable' => false, 'searchable' => false, 'exportable' => false, 'printable' => false];

        return $columns;
    }
}
