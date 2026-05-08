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
    private $addPaymentPermission;

    public function __construct()
    {
        parent::__construct();
        $this->addProductPermission = user()->permission('add_product');
        $this->editProductPermission = user()->permission('edit_product');
        $this->deleteProductPermission = user()->permission('delete_product');
        $this->addPaymentPermission = user()->permission('add_payments') ?? 'none';
    }

    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    
    public function dataTable($query)
{
    $datatables = datatables()->collection($query);

    $datatables->addColumn('check', function ($row) {
        return '<input type="checkbox" class="select-table-row" id="datatable-row-' . ($row->invoice_number ?? '') . '" name="datatable_ids[]" value="' . ($row->invoice_number ?? '') . '" onclick="dataTableRowCheck(\'' . ($row->invoice_number ?? '') . '\')">';
    });

    $datatables->addColumn('invoice_number', function ($row) {
        return '<strong>' . ($row->invoice_number ?? '--') . '</strong>';
    });
    
    $datatables->addColumn('invoice_date', function ($row) {
        return $row->invoice_date ? \Carbon\Carbon::parse($row->invoice_date)->format('d-m-Y') : '--';
    });
    
    $datatables->addColumn('vendor', function ($row) {
        $vendor = \Modules\Purchase\Entities\PurchaseVendor::find($row->vendor_id);
        return $vendor ? $vendor->primary_name : '--';
    });
    
    $datatables->addColumn('products_count', function ($row) {
        return '<span class="badge badge-primary">' . $row->products_count . ' Products</span>';
    });
    
    $datatables->addColumn('total_amount', function ($row) {
        return '<strong>' . currency_format($row->total_amount) . '</strong>';
    });
    
    $datatables->addColumn('payment_status', function ($row) {
        $status = $row->payment_status ?? 'pending';
        $canUpdatePayment = ($this->addPaymentPermission == 'all' || ($this->addPaymentPermission == 'added'));
        
        $badge = [
            'paid' => 'success',
            'partial' => 'warning',
            'pending' => 'secondary'
        ];
        $color = $badge[$status] ?? 'secondary';
        
        // Escape invoice_number to prevent XSS
        $invoiceNumber = htmlspecialchars($row->invoice_number ?? '', ENT_QUOTES, 'UTF-8');
        $statusEscaped = htmlspecialchars($status, ENT_QUOTES, 'UTF-8');
        
        if ($canUpdatePayment) {
            return '<span class="badge badge-' . $color . ' payment-status-badge" style="cursor: pointer;" 
                        data-invoice-number="' . $invoiceNumber . '" 
                        data-current-status="' . $statusEscaped . '" 
                        title="Click to manage payment">' . ucfirst($status) . '</span>';
        } else {
            return '<span class="badge badge-' . $color . '">' . ucfirst($status) . '</span>';
        }
    });

    $datatables->addColumn('action', function ($row) {
        $action = '<div class="task_view">
            <div class="dropdown">
                <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" 
                   type="link" 
                   id="dropdownMenuLink-' . $row->invoice_number . '" 
                   data-toggle="dropdown" 
                   aria-haspopup="true" 
                   aria-expanded="false">
                    <i class="icon-options-vertical icons"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->invoice_number . '">';

        /* View Invoice Details */
        $action .= '<a class="dropdown-item" 
                      href="javascript:;" 
                      onclick="viewInvoiceProducts(\'' . $row->invoice_number . '\')">
                      <i class="fa fa-eye mr-2"></i>' . __('app.view') . '
                   </a>';

        /* Edit Invoice */
        if (user()->permission('edit_product') == 'all') {
            $action .= '<a class="dropdown-item" 
                          href="' . route('purchase-entries.edit-invoice', $row->invoice_number) . '">
                          <i class="fa fa-edit mr-2"></i>' . __('app.edit') . '
                       </a>';
        }

        /* View Supplier Invoice (linked when purchase entry is saved) */
        if (!empty($row->supplier_invoice_id)) {
            $action .= '<a class="dropdown-item" href="' . route('supplier-invoices.show', $row->supplier_invoice_id) . '">
                          <i class="fa fa-file-invoice mr-2"></i>' . __('app.supplierInvoice') . '
                       </a>';
        }

        /* Delete Invoice */
        if (user()->permission('delete_product') == 'all') {
            $action .= '<a href="javascript:;"
                          class="dropdown-item delete-invoice"
                          data-invoice="' . $row->invoice_number . '">
                          <i class="fa fa-trash mr-2"></i>' . __('app.delete') . '
                       </a>';
        }

        $action .= '</div></div></div>';
        return $action;
    });

    $datatables->rawColumns(['check', 'invoice_number', 'products_count', 'total_amount', 'payment_status', 'action']);

    return $datatables;
}


    /**
     * @param ProductPurchaseDetail $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
     
    public function query(ProductPurchaseDetail $model)
{
    $request = $this->request();
    $companyId = company()->id;

    // First, get product IDs that belong to this company
    $companyProductIds = [];
    if ($companyId) {
        $companyProductIds = Product::where('company_id', $companyId)->pluck('id')->toArray();
    }

    // Group by invoice_number to show invoice-wise view
    $invoices = $model->newQuery()
        ->whereNotNull('invoice_number');
    
    // Filter by company products
    if (!empty($companyProductIds)) {
        $invoices->whereIn('product_id', $companyProductIds);
    }
    
    $invoices->select([
        'invoice_number',
        'invoice_date',
        'vendor_id',
        'payment_status',
        'mode_of_payment',
        \DB::raw('COUNT(*) as products_count'),
        \DB::raw('SUM(total) as total_amount'),
        \DB::raw('MAX(created_at) as created_at'),
        \DB::raw('MAX(id) as latest_id'),
        \DB::raw('MAX(supplier_invoice_id) as supplier_invoice_id')
    ])
    ->groupBy('invoice_number', 'invoice_date', 'vendor_id', 'payment_status', 'mode_of_payment');

    // Search by invoice number
    if ($request->searchText != '') {
        $search = $request->searchText;
        $invoices->where(function($q) use ($search) {
            $q->where('invoice_number', 'like', '%' . $search . '%')
              ->orWhere('reference_number', 'like', '%' . $search . '%');
        });
    }

    if (user()->permission('view_product') == 'added') {
        $invoices->where('created_by', user()->id);
    }

    // Order by invoice date and number (latest first)
    $invoices->orderByDesc('invoice_number');

    return collect($invoices->get());
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
        '<input type="checkbox" name="select_all_table" id="select-all-table" onclick="selectAllPurchaseEntries(this)">' => [
            'data' => 'check',
            'name' => 'check',
            'orderable' => false,
            'searchable' => false,
            'width' => '20px'
        ],
        __('Invoice #') => [
            'data' => 'invoice_number',
            'name' => 'invoice_number'
        ],
        __('Invoice Date') => [
            'data' => 'invoice_date',
            'name' => 'invoice_date'
        ],
        __('Vendor') => [
            'data' => 'vendor',
            'name' => 'vendor',
            'orderable' => false
        ],
        __('Products') => [
            'data' => 'products_count',
            'name' => 'products_count',
            'orderable' => false
        ],
        __('Total Amount') => [
            'data' => 'total_amount',
            'name' => 'total_amount'
        ],
        __('Payment Status') => [
            'data' => 'payment_status',
            'name' => 'payment_status'
        ],
        \Yajra\DataTables\Html\Column::computed('action')
            ->exportable(false)
            ->printable(false)
            ->width('15%')
    ];
}




}

