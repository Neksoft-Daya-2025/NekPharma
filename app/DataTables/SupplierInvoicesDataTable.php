<?php

namespace App\DataTables;

use App\Models\SupplierInvoice;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SupplierInvoicesDataTable extends BaseDataTable
{
    private $viewProductPermission;
    private $editProductPermission;
    private $deleteProductPermission;
    private $addProductPermission;

    public function __construct()
    {
        parent::__construct();
        $this->viewProductPermission = user()->permission('view_product');
        $this->editProductPermission = user()->permission('edit_product');
        $this->deleteProductPermission = user()->permission('delete_product');
        $this->addProductPermission = user()->permission('add_product');
    }

    public function dataTable($query)
    {
        $datatables = datatables()->eloquent($query);
        $datatables->addIndexColumn();
        $datatables->editColumn('invoice_number', function ($row) {
            return '<a href="' . route('supplier-invoices.show', $row->id) . '" class="text-dark">' . ($row->invoice_number ?? '--') . '</a>';
        });
        $datatables->addColumn('vendor_name', function ($row) {
            return $row->vendor ? ($row->vendor->primary_name ?? $row->vendor->company_name ?? '--') : '--';
        });
        $datatables->editColumn('invoice_date', function ($row) {
            return $row->invoice_date ? $row->invoice_date->format(company()->date_format) : '--';
        });
        $datatables->editColumn('supplier_invoice_total', function ($row) {
            return currency_format($row->supplier_invoice_total ?? 0);
        });
        $datatables->editColumn('entry_total', function ($row) {
            return currency_format($row->entry_total ?? 0);
        });
        $datatables->addColumn('match_status', function ($row) {
            $badge = [
                'draft' => 'secondary',
                'matched' => 'success',
                'unmatched' => 'danger',
            ];
            $color = $badge[$row->match_status] ?? 'secondary';
            return '<span class="badge badge-' . $color . '">' . ucfirst($row->match_status) . '</span>';
        });
        $datatables->addColumn('payment_status', function ($row) {
            $badge = [
                'pending' => 'secondary',
                'partial' => 'warning',
                'paid' => 'success',
            ];
            $color = $badge[$row->payment_status] ?? 'secondary';
            return '<span class="badge badge-' . $color . '">' . ucfirst($row->payment_status) . '</span>';
        });
        $datatables->addColumn('action', function ($row) {
            $action = '<div class="task_view"><div class="dropdown dropup">';
            $action .= '<a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link" id="dropdownMenuLink-' . $row->id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-options-vertical icons"></i></a>';
            $action .= '<div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink-' . $row->id . '">';
            $action .= '<a href="' . route('supplier-invoices.show', $row->id) . '" class="dropdown-item"><i class="fa fa-eye mr-2"></i>' . __('app.view') . '</a>';
            if ($this->addProductPermission == 'all' || $this->addProductPermission == 'added') {
                $addEntriesUrl = route('purchase-entries.create', [
                    'invoice_number' => $row->invoice_number,
                    'vendor_id' => $row->vendor_id,
                    'invoice_date' => $row->invoice_date ? $row->invoice_date->format('Y-m-d') : '',
                    'supplier_invoice_total' => $row->supplier_invoice_total ?? '',
                ]);
                $action .= '<a href="' . $addEntriesUrl . '" class="dropdown-item"><i class="fa fa-plus mr-2"></i>' . __('app.addPurchaseEntriesForInvoice') . '</a>';
            }
            if ($this->editProductPermission == 'all' || $this->editProductPermission == 'added') {
                $action .= '<a href="' . route('supplier-invoices.edit', $row->id) . '" class="dropdown-item"><i class="fa fa-edit mr-2"></i>' . __('app.edit') . '</a>';
            }
            if ($this->deleteProductPermission == 'all' || $this->deleteProductPermission == 'added') {
                $action .= '<a href="javascript:;" class="dropdown-item delete-table-row" data-supplier-invoice-id="' . $row->id . '"><i class="fa fa-trash mr-2"></i>' . __('app.delete') . '</a>';
            }
            $action .= '</div></div></div>';
            return $action;
        });
        $datatables->rawColumns(['invoice_number', 'match_status', 'payment_status', 'action']);
        return $datatables;
    }

    public function query(SupplierInvoice $model)
    {
        $request = $this->request();
        $query = $model->newQuery()
            ->with(['vendor'])
            ->select('supplier_invoices.*');

        if (company()->id) {
            $query->where('supplier_invoices.company_id', company()->id);
        }

        if ($request->vendor_id && $request->vendor_id !== 'all') {
            $query->where('supplier_invoices.vendor_id', $request->vendor_id);
        }
        if ($request->match_status && $request->match_status !== 'all') {
            $query->where('supplier_invoices.match_status', $request->match_status);
        }
        if ($request->payment_status && $request->payment_status !== 'all') {
            $query->where('supplier_invoices.payment_status', $request->payment_status);
        }
        if ($request->startDate && $request->startDate != '') {
            $query->whereDate('supplier_invoices.invoice_date', '>=', $request->startDate);
        }
        if ($request->endDate && $request->endDate != '') {
            $query->whereDate('supplier_invoices.invoice_date', '<=', $request->endDate);
        }

        return $query->orderBy('supplier_invoices.invoice_date', 'desc')->orderBy('supplier_invoices.id', 'desc');
    }

    public function html()
    {
        $filterScript = 'if($("#vendor_id").length) data.vendor_id = $("#vendor_id").val(); if($("#match_status").length) data.match_status = $("#match_status").val(); if($("#payment_status").length) data.payment_status = $("#payment_status").val(); var dr = $("#datatableRange").data("daterangepicker"); if(dr && dr.startDate) { data.startDate = dr.startDate.format("YYYY-MM-DD"); data.endDate = dr.endDate.format("YYYY-MM-DD"); }';
        $locale = user()->locale ?? 'en';
        $localPath = public_path('i18n/' . $locale . '.json');
        $fallbackPath = public_path('i18n/en.json');
        $languageUrl = File::exists($localPath) ? asset('i18n/' . $locale . '.json') : (File::exists($fallbackPath) ? asset('i18n/en.json') : null);
        return $this->builder()
            ->setTableId('supplier-invoices-table')
            ->columns($this->getColumns())
            ->minifiedAjax('', $filterScript)
            ->orderBy(1)
            ->destroy(true)
            ->responsive()
            ->serverSide()
            ->stateSave(true)
            ->processing()
            ->dom($this->domHtml)
            ->language($languageUrl ? ['url' => $languageUrl] : [])
            ->parameters([
                'initComplete' => 'function () {
                    window.LaravelDataTables["supplier-invoices-table"].buttons().container().appendTo("#table-actions");
                }',
            ]);
    }

    protected function getColumns(): array
    {
        return [
            '#' => ['data' => 'DT_RowIndex', 'name' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'title' => '#'],
            __('app.invoiceNumber') => ['data' => 'invoice_number', 'name' => 'invoice_number', 'title' => __('app.invoiceNumber')],
            __('modules.invoices.vendor') => ['data' => 'vendor_name', 'name' => 'vendor_id', 'title' => __('modules.invoices.vendor'), 'orderable' => false],
            __('app.date') => ['data' => 'invoice_date', 'name' => 'invoice_date', 'title' => __('app.date')],
            __('app.supplierTotal') => ['data' => 'supplier_invoice_total', 'name' => 'supplier_invoice_total', 'title' => __('app.supplierTotal'), 'orderable' => false],
            __('app.entryTotal') => ['data' => 'entry_total', 'name' => 'entry_total', 'title' => __('app.entryTotal'), 'orderable' => false],
            __('app.matchStatus') => ['data' => 'match_status', 'name' => 'match_status', 'title' => __('app.matchStatus'), 'orderable' => false],
            __('app.paymentStatus') => ['data' => 'payment_status', 'name' => 'payment_status', 'title' => __('app.paymentStatus')],
            __('app.action') => ['data' => 'action', 'name' => 'action', 'title' => __('app.action'), 'orderable' => false, 'searchable' => false, 'exportable' => false, 'printable' => false],
        ];
    }
}
