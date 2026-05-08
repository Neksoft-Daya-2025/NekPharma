<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Models\ProductPurchaseDetail;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoicePayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Purchase\Entities\PurchaseSetting;
use Modules\Purchase\Entities\PurchaseVendor;
use App\DataTables\SupplierInvoicesDataTable;
use App\Models\User;

class SupplierInvoiceController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.supplierInvoices');
        $this->middleware(function ($request, $next) {
            abort_403(!in_array(PurchaseSetting::MODULE_NAME, $this->user->modules));
            return $next($request);
        });
    }

    /**
     * Display a listing of supplier invoices.
     */
    public function index(SupplierInvoicesDataTable $dataTable)
    {
        $viewPermission = user()->permission('view_product');
        abort_403(!in_array($viewPermission, ['all', 'added']));

        $this->vendors = PurchaseVendor::where('company_id', company()->id)->orderBy('primary_name')->get(['id', 'primary_name', 'company_name']);
        $this->pageTitle = __('app.supplierInvoices');

        return $dataTable->render('supplier-invoices.index', $this->data);
    }

    /**
     * Show the form for creating a new supplier invoice (header only; lines added via purchase entry or link).
     */
    public function create()
    {
        $addPermission = user()->permission('add_product');
        abort_403(!in_array($addPermission, ['all', 'added']));

        $this->vendors = PurchaseVendor::where('company_id', company()->id)->orderBy('primary_name')->get();
        $this->pageTitle = __('app.add') . ' ' . __('app.supplierInvoice');

        if (request()->ajax() || request()->wantsJson()) {
            return $this->returnAjax('supplier-invoices.create');
        }

        return view('supplier-invoices.create', $this->data);
    }

    /**
     * Store a newly created supplier invoice (header). Optionally redirect to add purchase entry lines.
     */
    public function store(Request $request)
    {
        $addPermission = user()->permission('add_product');
        abort_403(!in_array($addPermission, ['all', 'added']));

        $request->validate([
            'invoice_number' => 'required|string|max:100',
            'invoice_date'   => 'required|date',
            'vendor_id'      => 'required|exists:purchase_vendors,id',
            'supplier_invoice_total' => 'nullable|numeric|min:0',
            'reference_number' => 'nullable|string|max:100',
            'reference_date'  => 'nullable|date',
            'notes'           => 'nullable|string|max:2000',
        ]);

        $supplierInvoice = SupplierInvoice::create([
            'company_id'             => company()->id,
            'vendor_id'              => $request->vendor_id,
            'invoice_number'         => $request->invoice_number,
            'invoice_date'            => Carbon::parse($request->invoice_date),
            'supplier_invoice_total'  => $request->supplier_invoice_total ?: null,
            'entry_total'             => null,
            'match_status'            => SupplierInvoice::MATCH_STATUS_DRAFT,
            'reference_number'        => $request->reference_number,
            'reference_date'          => $request->reference_date ? Carbon::parse($request->reference_date) : null,
            'payment_status'          => SupplierInvoice::PAYMENT_STATUS_PENDING,
            'notes'                   => $request->notes,
            'created_by'              => user()->id,
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return Reply::redirect(route('supplier-invoices.show', $supplierInvoice->id), __('messages.recordSaved'));
        }
        return redirect()->route('supplier-invoices.show', $supplierInvoice->id)->with('message', __('messages.recordSaved'));
    }

    /**
     * Display the specified supplier invoice with lines and match status.
     */
    public function show($id)
    {
        $viewPermission = user()->permission('view_product');
        abort_403(!in_array($viewPermission, ['all', 'added']));

        $this->supplierInvoice = SupplierInvoice::with(['vendor', 'lines.product.unit', 'payments'])
            ->where('company_id', company()->id)
            ->findOrFail($id);
        $this->pageTitle = __('app.supplierInvoice') . ' #' . $this->supplierInvoice->invoice_number;

        return view('supplier-invoices.show', $this->data);
    }

    /**
     * Show the form for editing the supplier invoice header.
     */
    public function edit($id)
    {
        $editPermission = user()->permission('edit_product');
        abort_403(!in_array($editPermission, ['all', 'added']));

        $this->supplierInvoice = SupplierInvoice::with(['vendor', 'lines'])->where('company_id', company()->id)->findOrFail($id);
        $this->vendors = PurchaseVendor::where('company_id', company()->id)->orderBy('primary_name')->get();
        $this->pageTitle = __('app.edit') . ' ' . __('app.supplierInvoice');

        return view('supplier-invoices.edit', $this->data);
    }

    /**
     * Update the specified supplier invoice header. Recompute match_status.
     */
    public function update(Request $request, $id)
    {
        $editPermission = user()->permission('edit_product');
        abort_403(!in_array($editPermission, ['all', 'added']));

        $supplierInvoice = SupplierInvoice::where('company_id', company()->id)->findOrFail($id);

        $request->validate([
            'invoice_number' => 'required|string|max:100',
            'invoice_date'   => 'required|date',
            'vendor_id'      => 'required|exists:purchase_vendors,id',
            'supplier_invoice_total' => 'nullable|numeric|min:0',
            'reference_number' => 'nullable|string|max:100',
            'reference_date'  => 'nullable|date',
            'notes'           => 'nullable|string|max:2000',
        ]);

        $supplierInvoice->update([
            'vendor_id'              => $request->vendor_id,
            'invoice_number'         => $request->invoice_number,
            'invoice_date'            => Carbon::parse($request->invoice_date),
            'supplier_invoice_total'  => $request->supplier_invoice_total ?: null,
            'reference_number'        => $request->reference_number,
            'reference_date'          => $request->reference_date ? Carbon::parse($request->reference_date) : null,
            'notes'                   => $request->notes,
        ]);

        $supplierInvoice->refreshTotalsAndMatchStatus();

        if ($request->ajax() || $request->wantsJson()) {
            return Reply::redirect(route('supplier-invoices.show', $supplierInvoice->id), __('messages.updateSuccess'));
        }
        return redirect()->route('supplier-invoices.show', $supplierInvoice->id)->with('message', __('messages.updateSuccess'));
    }

    /**
     * Remove the specified supplier invoice. Unlink lines (set supplier_invoice_id to null).
     */
    public function destroy($id)
    {
        $deletePermission = user()->permission('delete_product');
        abort_403(!in_array($deletePermission, ['all', 'added']));

        $supplierInvoice = SupplierInvoice::where('company_id', company()->id)->findOrFail($id);
        ProductPurchaseDetail::where('supplier_invoice_id', $supplierInvoice->id)->update(['supplier_invoice_id' => null]);
        $supplierInvoice->payments()->delete();
        $supplierInvoice->delete();

        if (request()->ajax()) {
            return Reply::success(__('messages.deleteSuccess'));
        }
        return redirect()->route('supplier-invoices.index')->with('message', __('messages.deleteSuccess'));
    }

    /**
     * Store a payment for a supplier invoice (payment-to-vendor).
     */
    public function storePayment(Request $request, $supplierInvoiceId)
    {
        $addPermission = user()->permission('add_product');
        abort_403(!in_array($addPermission, ['all', 'added']));

        $supplierInvoice = SupplierInvoice::where('company_id', company()->id)->findOrFail($supplierInvoiceId);
        $request->validate([
            'amount'   => 'required|numeric|min:0.01',
            'paid_on'  => 'required|date',
            'reference' => 'nullable|string|max:100',
            'remarks'   => 'nullable|string|max:500',
        ]);

        $payment = SupplierInvoicePayment::create([
            'supplier_invoice_id' => $supplierInvoice->id,
            'amount'              => $request->amount,
            'paid_on'             => Carbon::parse($request->paid_on),
            'reference'           => $request->reference,
            'remarks'             => $request->remarks,
            'created_by'          => user()->id,
        ]);
        $supplierInvoice->refreshPaymentStatus();

        if ($request->ajax() || $request->wantsJson()) {
            return Reply::successWithData(__('messages.recordSaved'), [
                'payment_id' => $payment->id,
                'payment_status' => $supplierInvoice->fresh()->payment_status,
            ]);
        }
        return redirect()->route('supplier-invoices.show', $supplierInvoice->id)->with('message', __('messages.recordSaved'));
    }

    /**
     * Update a supplier invoice payment.
     */
    public function updatePayment(Request $request, $supplierInvoiceId, $paymentId)
    {
        $editPermission = user()->permission('edit_product');
        abort_403(!in_array($editPermission, ['all', 'added']));

        $supplierInvoice = SupplierInvoice::where('company_id', company()->id)->findOrFail($supplierInvoiceId);
        $payment = SupplierInvoicePayment::where('supplier_invoice_id', $supplierInvoice->id)->findOrFail($paymentId);
        $request->validate([
            'amount'   => 'required|numeric|min:0.01',
            'paid_on'  => 'required|date',
            'reference' => 'nullable|string|max:100',
            'remarks'   => 'nullable|string|max:500',
        ]);

        $payment->update([
            'amount'    => $request->amount,
            'paid_on'   => Carbon::parse($request->paid_on),
            'reference' => $request->reference,
            'remarks'   => $request->remarks,
        ]);
        $supplierInvoice->refreshPaymentStatus();

        if ($request->ajax() || $request->wantsJson()) {
            return Reply::successWithData(__('messages.updateSuccess'), [
                'payment_status' => $supplierInvoice->fresh()->payment_status,
            ]);
        }
        return redirect()->route('supplier-invoices.show', $supplierInvoice->id)->with('message', __('messages.updateSuccess'));
    }

    /**
     * Delete a supplier invoice payment.
     */
    public function destroyPayment($supplierInvoiceId, $paymentId)
    {
        $deletePermission = user()->permission('delete_product');
        abort_403(!in_array($deletePermission, ['all', 'added']));

        $supplierInvoice = SupplierInvoice::where('company_id', company()->id)->findOrFail($supplierInvoiceId);
        $payment = SupplierInvoicePayment::where('supplier_invoice_id', $supplierInvoice->id)->findOrFail($paymentId);
        $payment->delete();
        $supplierInvoice->refreshPaymentStatus();

        if (request()->ajax()) {
            return Reply::success(__('messages.deleteSuccess'));
        }
        return redirect()->route('supplier-invoices.show', $supplierInvoice->id)->with('message', __('messages.deleteSuccess'));
    }
}
