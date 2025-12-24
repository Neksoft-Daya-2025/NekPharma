<?php

namespace Modules\Purchase\DataTables;

use App\Models\CustomField;
use App\Models\CustomFieldGroup;
use App\DataTables\BaseDataTable;
use App\Models\Product;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
// use Modules\Purchase\Entities\PurchaseProduct;
use App\Models\ProductPurchaseDetail;
use Modules\Purchase\Entities\PurchaseStockAdjustment;


class PurchaseProductsDetailDataTable extends BaseDataTable
{

    private $deleteProductPermission;
    private $editProductPermission;
    private $addProductPermission;

    public function __construct()
    {
        parent::__construct();
        $this->addProductPermission = user()->permission('add_product');
        $this->editProductPermission = user()->permission('edit_product');
        $this->deleteProductPermission = user()->permission('delete_product');
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

    $datatables->addColumn('product', function ($row) {
        return optional($row->product)->name ?? '--';
    });

    $datatables->editColumn('quantity', fn($row) => $row->quantity ?? 0);
    $datatables->editColumn('batch', fn($row) => $row->batch ?? '--');
    $datatables->editColumn('expiry', fn($row) => $row->expiry ? $row->expiry->format('d-m-Y') : '--');
    $datatables->editColumn('pts', fn($row) => $row->pts ? number_format($row->pts, 2) : '--');
    $datatables->editColumn('ptr', fn($row) => $row->ptr ? number_format($row->ptr, 2) : '--');
    $datatables->editColumn('dis', function($row) {
        // DIS column should display discount percentage
        // Priority: discount field (actively used) > dis field (legacy)
        $disValue = null;
        
        // First check discount field (this is what's actually used in the form)
        if (isset($row->discount) && $row->discount !== null && $row->discount !== '') {
            $disValue = $row->discount;
        }
        // Fallback to dis field if discount is not available
        elseif (isset($row->dis) && $row->dis !== null && $row->dis !== '') {
            $disValue = $row->dis;
        }
        
        if ($disValue !== null && $disValue !== '') {
            // Format as percentage with 2 decimal places
            return number_format((float)$disValue, 2) . '%';
        }
        
        return '--';
    });
    $datatables->editColumn('mrp', fn($row) => currency_format($row->mrp));
    $datatables->editColumn('total', fn($row) => currency_format($row->total));

    // Add Total Stock column
    $datatables->addColumn('total_stock', function ($row) {
        if (!$row->product_id) {
            return '--';
        }
        
        // Get current stock from PurchaseStockAdjustment (most accurate)
        $stockAdjustment = PurchaseStockAdjustment::where('product_id', $row->product_id)
            ->where('company_id', company()->id)
            ->first();
        
        if ($stockAdjustment && $stockAdjustment->net_quantity > 0) {
            return number_format($stockAdjustment->net_quantity, 0);
        }
        
        // Fallback: Calculate from opening_stock + purchase entries
        $product = $row->product;
        if (!$product) {
            return '--';
        }
        
        $openingStock = $product->opening_stock ?? 0;
        $totalPurchaseEntries = ProductPurchaseDetail::where('product_id', $row->product_id)
            ->sum('quantity');
        $currentStock = $openingStock + $totalPurchaseEntries;
        
        return number_format($currentStock, 0);
    });

    $datatables->addColumn('action', function ($row) {

    $action = '<div class="d-flex align-items-center">';

    /* Duplicate button (optional) */
    if (user()->permission('add_product') == 'all') {
        $action .= '<a class="btn btn-secondary btn-sm rounded mr-1 openRightModal"
                                href="' . route('purchase-entries.create') . '?duplicate_purchase=' . $row->id . '"
                        data-toggle="tooltip"
                        data-original-title="' . __('app.duplicate') . '">
                        <i class="fa fa-clone"></i>
                    </a>';
    }

    /* Dropdown */
    $action .= '<div class="task_view">
        <div class="dropdown">
            <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle"
                id="dropdownMenuLink-' . $row->id . '"
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false">
                <i class="icon-options-vertical icons"></i>
            </a>

            <div class="dropdown-menu dropdown-menu-right"
                aria-labelledby="dropdownMenuLink-' . $row->id . '">';

    /* View */
    $action .= '<a href="' . route('purchase-entries.show', $row->id) . '"
                    class="dropdown-item">
                    <i class="fa fa-eye mr-2"></i>' . __('app.view') . '
                </a>';

    /* Edit */
    if (user()->permission('edit_product') == 'all') {
            $action .= '<a href="' . route('purchase-entries.edit', $row->id) . '"
                        class="dropdown-item openRightModal">
                        <i class="fa fa-edit mr-2"></i>' . __('app.edit') . '
                    </a>';
    }

    /* Delete */
    if (user()->permission('delete_product') == 'all') {
        $action .= '<a href="javascript:;"
                        class="dropdown-item delete-table-row"
                        data-id="' . $row->id . '"
                        data-product-id="' . $row->id . '"
                                data-url="' . route('purchase-entries.destroy', $row->id) . '">
                        <i class="fa fa-trash mr-2"></i>' . __('app.delete') . '
                    </a>';
    }

    $action .= '</div></div></div></div>';

    return $action;
});


    $datatables->rawColumns(['action']);

    return $datatables;
}


    /**
     * @param ProductPurchaseDetail $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
     
    public function query(ProductPurchaseDetail $model)
{
    $request = $this->request();

    $query = $model->newQuery()
        ->with([
            'product',
            'product.category',
            'product.subCategory',
            'product.unit',
        ])
        ->select('product_purchase_details.*');

    /* ================= FILTERS ================= */
    
    // CRITICAL: Filter by company_id to ensure data isolation - always applied
    $companyId = company()->id;
    
    // Base filter: Only show purchase entries for products belonging to current company
    if ($companyId) {
        $query->whereHas('product', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        });
    }

    // Category filter
    if (!is_null($request->category_id) && $request->category_id != 'all') {
        $query->whereHas('product', function ($q) use ($request, $companyId) {
            $q->where('category_id', $request->category_id);
            if ($companyId) {
                $q->where('company_id', $companyId);
            }
        });
    }

    // Sub-category filter
    if (!is_null($request->sub_category_id) && $request->sub_category_id != 'all') {
        $query->whereHas('product', function ($q) use ($request, $companyId) {
            $q->where('sub_category_id', $request->sub_category_id);
            if ($companyId) {
                $q->where('company_id', $companyId);
            }
        });
    }

    // Unit type filter
    if (!is_null($request->unit_type_id) && $request->unit_type_id != 'all') {
        $query->whereHas('product', function ($q) use ($request, $companyId) {
            $q->where('unit_id', $request->unit_type_id);
            if ($companyId) {
                $q->where('company_id', $companyId);
            }
        });
    }

    // Search filter
    if ($request->searchText != '') {
        $query->whereHas('product', function ($q) use ($companyId) {
            $q->where(function($query) {
                $query->where('name', 'like', '%' . request('searchText') . '%')
                      ->orWhere('packing', 'like', '%' . request('searchText') . '%');
            });
            if ($companyId) {
                $q->where('company_id', $companyId);
            }
        });
    }

    if (user()->permission('view_product') == 'added') {
        $query->where('created_by', user()->id);
    }

    return $query; // 🔥 MUST return Builder
}


    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    public function html()
    {
        $dataTable = $this->setBuilder('products-table', 2)
            ->parameters([
                'initComplete' => 'function () {
                   window.LaravelDataTables["products-table"].buttons().container()
                    .appendTo( "#table-actions")
                }',
                'fnDrawCallback' => 'function( oSettings ) {
                    $("body").tooltip({
                        selector: \'[data-toggle="tooltip"]\'
                    });
                    $(".change-product-status").selectpicker();
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
    return [
        __('Product') => [
            'data' => 'product',
            'name' => 'product'
        ],
        __('Qty') => [
            'data' => 'quantity',
            'name' => 'quantity'
        ],
        __('Batch') => [
            'data' => 'batch',
            'name' => 'batch'
        ],
        __('Expiry') => [
            'data' => 'expiry',
            'name' => 'expiry'
        ],
        __('PTS') => [
            'data' => 'pts',
            'name' => 'pts'
        ],
        __('PTR') => [
            'data' => 'ptr',
            'name' => 'ptr'
        ],
        __('DIS %') => [
            'data' => 'dis',
            'name' => 'dis'
        ],
        __('MRP') => [
            'data' => 'mrp',
            'name' => 'mrp'
        ],
        __('Total') => [
            'data' => 'total',
            'name' => 'total'
        ],
        __('Total Stock') => [
            'data' => 'total_stock',
            'name' => 'total_stock',
            'orderable' => false,
            'searchable' => false
        ],
        \Yajra\DataTables\Html\Column::computed('action')
            ->exportable(false)
            ->printable(false)
    ];
}




}

