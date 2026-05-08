<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Stripe\Stripe;
use App\Models\Tax;
use App\Models\User;
use App\Helper\Files;
use App\Helper\Reply;
use App\Helpers\PharmaDesignationHelper;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Project;
use App\Models\Currency;
use App\Models\Estimate;
use App\Models\Proposal;
use App\Models\UnitType;
use App\Models\BankAccount;
use App\Models\CreditNotes;
use App\Scopes\ActiveScope;
use App\Models\InvoiceItems;
use Illuminate\Http\Request;
use App\Models\ClientDetails;
use App\Models\CompanyAddress;
use App\Models\InvoiceSetting;
use App\Models\ProjectTimeLog;
use App\Events\NewInvoiceEvent;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\InvoiceItemImage;
use App\Models\ProjectMilestone;
use Illuminate\Support\Facades\App;
use App\Events\PaymentReminderEvent;
use App\Events\NewPaymentEvent;
use App\Models\OfflinePaymentMethod;
use App\DataTables\InvoicesDataTable;
use App\DataTables\CFADistributorInvoicesDataTable;
use App\DataTables\CFAStockistInvoicesDataTable;
use App\DataTables\CFADistributorInventoryDataTable;
use App\DataTables\CFAStockistInventoryDataTable;
use App\Traits\EmployeeActivityTrait;
use App\Http\Requests\InvoiceFileStore;
use App\Models\PaymentGatewayCredentials;
use App\Http\Requests\Invoices\StoreInvoice;
use App\Http\Requests\Invoices\UpdateInvoice;
use App\Http\Requests\Payments\InvoicePayment;
use Modules\Purchase\Entities\PurchaseProduct;
use App\Http\Requests\Stripe\StoreStripeDetail;
use Modules\Purchase\Entities\PurchaseStockAdjustment;
use Modules\Purchase\Entities\PurchaseBatchStock;
use App\Models\ProductPurchaseDetail;
use App\Models\CFADistributorStock;
use App\Models\CFAStockist;
use App\Models\CFAStockistStock;
use App\Http\Requests\Admin\Client\StoreShippingAddressRequest;
use App\Models\InvoicePaymentDetail;
use App\Helper\UserService;
use App\Models\Stockist;
use App\Models\ClientCategory;

class InvoiceController extends AccountBaseController
{
    use EmployeeActivityTrait;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.invoices';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('invoices', $this->user->modules));

            return $next($request);
        });
    }

    public function index(InvoicesDataTable $dataTable)
    {
        $viewPermission = user()->permission('view_invoices');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        if (!request()->ajax()) {
            $this->projects = Project::allProjects();

            if (in_array('client', user_roles())) {
                $this->clients = User::client();
            } else {
                $this->clients = User::allClients();
            }
        }

        return $dataTable->render('invoices.index', $this->data);
    }

    public function create()
    {
        $this->addPermission = user()->permission('add_invoices');

        $this->pageTitle = __('modules.invoices.addInvoice');

        abort_403(!in_array($this->addPermission, ['all', 'added']));

        if (request('invoice') != '') {
            $this->invoiceId = request('invoice');
            $this->type = 'invoice';
            $this->invoice = Invoice::with('items', 'client', 'client.projects', 'invoicePaymentDetail')->findOrFail($this->invoiceId);
        }

        $this->userId = UserService::getUserId();
        $this->isClient = User::isClient($this->userId);

        // Store the logged-in client separately to check for CFA/Distributor
        $loggedInClient = null;
        if ($this->isClient) {
            $loggedInClient = User::with(['projects', 'clientDetails.areas'])->withoutGlobalScope(ActiveScope::class)->findOrFail($this->userId);
            $this->client = $loggedInClient;
        }

        // this data is sent from project and client invoices
        $this->project = request('project_id') ? Project::findOrFail(request('project_id')) : null;

        if (request('client_id')) {
            $this->client = User::withoutGlobalScope(ActiveScope::class)->with('clientDetails.areas')->findOrFail(request('client_id'));
        }

        if (request('estimate') != '') {
            $this->estimateId = request('estimate');
            $this->type = 'estimate';
            $this->estimate = Estimate::with('items', 'client', 'client.clientDetails', 'client.projects')->findOrFail($this->estimateId);
            $this->estimateCurrency = Currency::where('id', $this->estimate->currency_id)->first();
        }

        if (request('proposal') != '') {
            $this->proposalId = request('proposal');
            $this->type = 'proposal';
            $this->estimate = Proposal::with('items', 'lead', 'lead.contact')->findOrFail($this->proposalId);
            $this->client = $this->estimate->lead->contact->client;
            $this->proposalCurrency = Currency::where('id', $this->estimate->currency_id)->first();
        }
        
        // For CFA/Distributor check, always use the logged-in client, not the request client
        $clientToCheck = $loggedInClient ?? (isset($this->client) ? $this->client : null);

        $this->currencies = Currency::all();
        $this->categories = ProductCategory::all();
        $this->lastInvoice = Invoice::lastInvoiceNumber() + 1;
        $this->invoiceSetting = invoice_setting();
        $this->zero = '';

        if (strlen($this->lastInvoice) < $this->invoiceSetting->invoice_digit) {
            $condition = $this->invoiceSetting->invoice_digit - strlen($this->lastInvoice);

            for ($i = 0; $i < $condition; $i++) {
                $this->zero = '0' . $this->zero;
            }
        }

        $this->units = UnitType::all();
        $this->taxes = Tax::all();

        // Check if logged-in user is a CFA/Distributor
        // Always check the logged-in client, not the request client
        $this->isCFADistributor = false;
        $this->stockists = collect();
        
        if ($this->isClient && $clientToCheck && $clientToCheck->clientDetails) {
            // Ensure areas relationship is loaded
            if (!$clientToCheck->clientDetails->relationLoaded('areas')) {
                $clientToCheck->clientDetails->load('areas');
            }
            
            $hasAreas = $clientToCheck->clientDetails->areas->count() > 0;
            
            // Check if client category is CFA or Distributor (case-insensitive)
            $clientCategory = $clientToCheck->clientDetails->category_id 
                ? ClientCategory::where('id', $clientToCheck->clientDetails->category_id)
                    ->where('company_id', company()->id)
                    ->first()
                : null;
            
            $isCategoryCFA = false;
            if ($clientCategory) {
                $categoryName = strtolower(trim($clientCategory->category_name));
                $isCategoryCFA = (stripos($categoryName, 'cfa') !== false || stripos($categoryName, 'distributor') !== false);
            }
            
            // If category is CFA/Distributor OR if client has areas assigned (fallback for clients without category)
            if ($isCategoryCFA || $hasAreas) {
                $this->isCFADistributor = true;
                
                // Get areas allotted to this CFA/Distributor
                $allottedAreaIds = $clientToCheck->clientDetails->areas->pluck('id')->toArray();
                
                if (!empty($allottedAreaIds)) {
                    // Get stockists in these areas
                    $this->stockists = Stockist::whereIn('area_id', $allottedAreaIds)
                        ->where('company_id', company()->id)
                        ->get();
                } else {
                    // If no areas allotted, show empty collection
                    $this->stockists = collect();
                }
            }
        }
        
        // INVOICE FORM IS ONLY ASSOCIATED WITH PURCHASE ENTRIES
        // Load products ONLY from purchase entries (ProductPurchaseDetail)
        // This is a separate entity that does NOT use Product model directly
        if (!module_enabled('Purchase')) {
            // If Purchase module is not enabled, invoice form cannot work
            $this->products = collect([]);
            $this->productsFlat = collect([]);
        } else {
            // Load ONLY from purchase entries - group by product with batches
            $purchaseEntries = ProductPurchaseDetail::with(['product.vendor', 'product.unit'])
                ->whereHas('product', function($query) {
                    $query->where('company_id', company()->id);
                })
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Group by product_id - but remember: invoice works with purchase entries, not products
            $productsGrouped = [];
            foreach ($purchaseEntries as $entry) {
                $productId = $entry->product_id;
                if (!isset($productsGrouped[$productId])) {
                    $productsGrouped[$productId] = [
                        'product' => $entry->product, // Only for display/reference
                        'entries' => []
                    ];
                }
                $productsGrouped[$productId]['entries'][] = $entry;
            }
            $this->products = collect($productsGrouped);
            // For backward compatibility: also provide flat list (but still from purchase entries)
            $this->productsFlat = collect($productsGrouped)->map(function($item) {
                return $item['product'];
            })->unique('id');
        }
        
        // Filter clients by region if logged-in user is a CFA/Distributor (client) - old logic
        if ($this->isClient && $clientToCheck && $clientToCheck->clientDetails && isset($clientToCheck->clientDetails->region_id) && $clientToCheck->clientDetails->region_id && !$this->isCFADistributor) {
            // Get all clients from the same region
            $regionId = $clientToCheck->clientDetails->region_id;
            $allClients = User::allClients();
            // Load clientDetails with region_id for filtering
            $allClients->load('clientDetails');
            $this->clients = $allClients->filter(function($client) use ($regionId) {
                return $client->clientDetails && 
                       isset($client->clientDetails->region_id) &&
                       $client->clientDetails->region_id == $regionId;
            });
        } else {
            $this->clients = User::allClients();
        }
        $this->companyAddresses = CompanyAddress::all();
        $this->projects = Project::allProjectsHavingClient();
        $this->linkInvoicePermission = user()->permission('link_invoice_bank_account');
        $this->viewBankAccountPermission = user()->permission('view_bankaccount');
        $this->paymentGateway = PaymentGatewayCredentials::first();
        $this->invoicePayments = InvoicePaymentDetail::all();


        $bankAccounts = BankAccount::where('status', 1)->where('currency_id', company()->currency_id);

        if ($this->viewBankAccountPermission == 'added') {
            $bankAccounts = $bankAccounts->where('added_by', $this->userId);
        }

        $bankAccounts = $bankAccounts->get();
        $this->bankDetails = $bankAccounts;

        $this->companyCurrency = Currency::where('id', company()->currency_id)->first();

        if (request('type') == 'timelog' && in_array('projects', user_modules())) {

            $this->startDate = now($this->company->timezone)->subDays(7);
            $this->endDate = now($this->company->timezone);

            $this->view = 'invoices.ajax.create-timelog-invoice';

            if (request()->ajax()) {
                return $this->returnAjax($this->view);
            }

            return view('invoices.create', $this->data);
        }

        $invoice = new Invoice();

        $getCustomFieldGroupsWithFields = $invoice->getCustomFieldGroupsWithFields();

        if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }

        $this->view = 'invoices.ajax.create';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('invoices.create', $this->data);
    }

    public function store(StoreInvoice $request)
    {
        $quantity = $request->quantity;
        $product = $request->product_id;
        $stockAdjustment = [];
        $userId = UserService::getUserId();

        if ((module_enabled('Purchase') && in_array('purchase', user_modules()) && $request->do_it_later == 'direct')) {
            if (is_array($product)) {

                $serviceProductIds = Product::whereIn('id', $product)->where('type', 'service')->pluck('id')->toArray();
                $nonServiceProductIds = array_diff($product, $serviceProductIds);

                foreach ($nonServiceProductIds as $key => $productId) {
                    if (!is_null($productId)) {
                        if (!isset($stockAdjustment[$productId])) {
                            $stockAdjustment[$productId] = 0;
                        }


                        $stockAdjustment[$productId] += $quantity[$key];
                    }
                }
            }

            $check = [];
            $invoiceItems = InvoiceItems::whereHas('invoice', function ($invoiceQuery) {
                $invoiceQuery->where('status', 'unpaid');
            })->get();

            foreach ($stockAdjustment as $index => $quantityCount) {
                $commitedStock = $invoiceItems->filter(function ($value, $key) use ($index) {
                    return $value->product_id == $index;
                })->sum('quantity');

                $quantity = PurchaseStockAdjustment::where('product_id', $index)->sum('net_quantity');

                if (($quantity - $commitedStock) < $quantityCount) {
                    $check[] = $index;
                }
            }

            if (!empty($check)) {
                return Reply::dataOnly(['status' => 'error', 'data' => $check, 'showValue' => true, 'title' => $this->pageTitle]);
            }
        }

        $redirectUrl = urldecode($request->redirect_url ?? '');

        if ($redirectUrl == '') {
            $redirectUrl = route('invoices.index');
        }

        $items = $request->item_name;
        $cost_per_item = $request->cost_per_item;
        $quantity = $request->quantity;
        $amount = $request->amount;

        if (empty($items)) {
            return Reply::error(__('messages.addItem'));
        }

        foreach ($items as $itm) {
            if (is_null($itm)) {
                return Reply::error(__('messages.itemBlank'));
            }
        }

        foreach ($quantity as $qty) {
            if (!is_numeric($qty) && (intval($qty) < 1)) {
                return Reply::error(__('messages.quantityNumber'));
            }
        }

        foreach ($cost_per_item as $rate) {
            if (!is_numeric($rate)) {
                return Reply::error(__('messages.unitPriceNumber'));
            }
        }

        foreach ($amount as $amt) {
            if (!is_numeric($amt)) {
                return Reply::error(__('messages.amountNumber'));
            }
        }

        $invoice = new Invoice();
        $invoice->project_id = $request->project_id ?? null;
        $invoice->client_id = ($request->client_id) ?: null;
        $invoice->stockist_id = ($request->stockist_id) ?: null;
        $invoice->issue_date = companyToYmd($request->issue_date);
        $invoice->due_date = companyToYmd($request->due_date);
        $invoice->sub_total = round($request->sub_total, 2);
        $invoice->discount = round($request->discount_value, 2);
        $invoice->discount_type = $request->discount_type;
        $invoice->total = round($request->total, 2);
        $invoice->due_amount = round($request->total, 2);
        $invoice->currency_id = $request->currency_id;
        $invoice->default_currency_id = company()->currency_id;
        $invoice->exchange_rate = $request->exchange_rate;
        $invoice->recurring = 'no';
        $invoice->is_timelog_invoice = $request->invoice_type ? '1' : '0';
        $invoice->billing_frequency = $request->recurring_payment == 'yes' ? $request->billing_frequency : null;
        $invoice->billing_interval = $request->recurring_payment == 'yes' ? $request->billing_interval : null;
        $invoice->billing_cycle = $request->recurring_payment == 'yes' ? $request->billing_cycle : null;
        $invoice->note = trim_editor($request->note);
        $invoice->show_shipping_address = $request->show_shipping_address;
        $invoice->invoice_number = $request->invoice_number;
        $invoice->company_address_id = $request->company_address_id;
        $invoice->estimate_id = $request->estimate_id ? $request->estimate_id : null;
        $invoice->bank_account_id = $request->bank_account_id;
        $invoice->payment_status = $request->payment_status == null ? '0' : $request->payment_status;
        $invoice->invoice_payment_id = $request->invoice_payment_id;
        $invoice->save();

        // To add custom fields data

        if ($request->custom_fields_data) {
            $invoice->updateCustomFieldData($request->custom_fields_data);
        }

        if ($request->estimate_id) {
            $estimate = Estimate::findOrFail($request->estimate_id);
            $estimate->status = 'accepted';
            $estimate->save();
        }

        if ($request->proposal_id) {
            $proposal = Proposal::findOrFail($request->proposal_id);
            $proposalData = [
                'invoice_convert' => 1,
            ];

            if ($proposal->signature) {
                $proposalData['status'] = 'accepted';
            }

            Proposal::where('id', $request->proposal_id)->update($proposalData);
        }

        if ($request->has('shipping_address') || $request->has('billing_address')) {
            if ($invoice->project_id != null && $invoice->project_id != '') {
                $client = $invoice->project->clientdetails;
            } elseif ($invoice->client_id != null && $invoice->client_id != '') {
                $client = $invoice->clientdetails;
            }

            if (isset($client)) {
                if (isset($request->shipping_address)) {
                    $client->shipping_address = $request->shipping_address;
                }
                if (isset($request->billing_address)) {
                    $client->address = $request->billing_address;
                }
                $client->save();
            }
        }

        // Set milestone paid if converted milestone to invoice
        if ($request->milestone_id != '') {
            $milestone = ProjectMilestone::findOrFail($request->milestone_id);
            $milestone->invoice_created = 1;
            $milestone->invoice_id = $invoice->id;
            $milestone->save();
        }

        // Set invoice id in timelog
        if ($request->has('timelog_from') && $request->timelog_from != '' && $request->has('timelog_to') && $request->timelog_to != '') {
            $timelogFrom = companyToYmd($request->timelog_from);
            $timelogTo = companyToYmd($request->timelog_to);
            $this->timelogs = ProjectTimeLog::where('project_time_logs.project_id', $request->project_id)
                ->leftJoin('tasks', 'tasks.id', '=', 'project_time_logs.task_id')
                ->where('project_time_logs.earnings', '>', 0)
                ->where('project_time_logs.approved', 1)
                ->where(
                    function ($query) {
                        $query->where('tasks.billable', 1)
                            ->orWhereNull('tasks.billable');
                    }
                )
                ->whereDate('project_time_logs.start_time', '>=', $timelogFrom)
                ->whereDate('project_time_logs.end_time', '<=', $timelogTo)
                ->update(['invoice_id' => $invoice->id]);
        }

        // Log search
        $this->logSearchEntry($invoice->id, $invoice->invoice_number, 'invoices.show', 'invoice');

        if (user()) {
            self::createEmployeeActivity($userId, 'invoice-created', $invoice->id, 'invoice');
        }

        if ($invoice->send_status == 1) {
            return Reply::successWithData(__('messages.invoiceSentSuccessfully'), ['redirectUrl' => $redirectUrl, 'invoiceID' => $invoice->id]);
        }


        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => $redirectUrl, 'invoiceID' => $invoice->id]);
    }

    public function committedModal(Request $request)
    {
        $productIds = $request->products;
        $productIDsArray = explode(',', $productIds);
        $this->products = PurchaseProduct::whereIn('id', $productIDsArray)->get();

        return view('invoices.ajax.comitted_model', $this->data);
    }

    public function applyQuickAction(Request $request)
    {
        switch ($request->action_type) {
            case 'delete':
                $this->deleteRecords($request);

                return Reply::success(__('messages.deleteSuccess'));
            default:
                return Reply::error(__('messages.selectAction'));
        }
    }

    protected function deleteRecords($request)
    {
        abort_403(user()->permission('delete_invoices') != 'all');

        $items = explode(',', $request->row_ids);

        foreach ($items as $id) {
            $firstInvoice = Invoice::orderBy('id', 'desc')->first();

            if ($firstInvoice->id == $id) {
                if (CreditNotes::where('invoice_id', $id)->exists()) {
                    CreditNotes::where('invoice_id', $id)->update(['invoice_id' => null]);
                }

                Invoice::destroy($id);

                return Reply::success(__('messages.deleteSuccess'));
            } else {
                return Reply::error(__('messages.invoiceCanNotDeleted'));
            }
        }
    }

    public function destroy($id)
    {
        $firstInvoice = Invoice::orderBy('id', 'desc')->first();
        $invoice = Invoice::findOrFail($id);
        if (CFAStockistStock::where('invoice_id', $invoice->id)->exists()) {
            abort_403(!$this->userCanDeleteCfaStockistInvoice($invoice));
        } else {
            $this->deletePermission = user()->permission('delete_invoices');
            $userId = UserService::getUserId();
            abort_403(!(
                $this->deletePermission == 'all'
                || ($this->deletePermission == 'added' && $invoice->added_by == $userId || $invoice->added_by == user()->id)
                || ($this->deletePermission == 'owned' && $invoice->client_id == $userId)
                || ($this->deletePermission == 'both' && ($invoice->client_id == $userId) || ($invoice->added_by == $userId || $invoice->added_by == user()->id))
            ));
        }

        // if ($firstInvoice->id == $id) {
            if (CreditNotes::where('invoice_id', $id)->exists()) {
                CreditNotes::where('invoice_id', $id)->update(['invoice_id' => null]);
            }

            Invoice::destroy($id);

            return Reply::success(__('messages.deleteSuccess'));
        // } else {
        //     return Reply::error(__('messages.invoiceCanNotDeleted'));
        // }
    }

    public function download($id)
    {
        $this->invoiceSetting = invoice_setting();
        $this->invoice = Invoice::with('project', 'items', 'items.unit')->findOrFail($id)->withCustomFields();
        $userId = UserService::getUserId();

        $getCustomFieldGroupsWithFields = $this->invoice->getCustomFieldGroupsWithFields();

        if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }

        $this->viewPermission = user()->permission('view_invoices');
        $this->company = $this->invoice->company;

        $viewProjectInvoicePermission = user()->permission('view_project_invoices');
        abort_403(!(
            $this->viewPermission == 'all'
            || ($this->viewPermission == 'added' && ($this->invoice->added_by == $userId || $this->invoice->added_by == user()->id))
            || ($this->viewPermission == 'owned' && $this->invoice->client_id == $userId)
            || ($viewProjectInvoicePermission == 'owned' && $this->invoice->project_id && $this->invoice->project->client_id == $userId)
        ));

        App::setLocale($this->invoiceSetting->locale ?? 'en');
        Carbon::setLocale($this->invoiceSetting->locale ?? 'en');

        // Download file uploaded
        if ($this->invoice->file != null && request()->has('download-uploaded')) {
            return response()->download(storage_path('app/public/invoice-files') . '/' . $this->invoice->file);
        }

        $pdfOption = $this->domPdfObjectForDownload($id);
        $pdf = $pdfOption['pdf'];
        $filename = $pdfOption['fileName'];

        return request()->view ? $pdf->stream($filename . '.pdf') : $pdf->download($filename . '.pdf');
    }

    public function domPdfObjectForDownload($id)
    {
        $this->invoice = Invoice::with([
            'items' => function($query) {
                $query->where('type', 'item')->orderBy('field_order', 'asc')->orderBy('id', 'asc');
            }, 
            'items.unit', 
            'items.product' => function($query) {
                $query->select('id', 'name', 'packing', 'sku', 'hsn_sac_code', 'vendor_id');
            }, 
            'items.purchaseEntry', 
            'items.purchaseEntry.product' => function($query) {
                $query->select('id', 'name', 'packing', 'sku', 'hsn_sac_code', 'vendor_id');
            }, 
            'cfaDistributorStocks', 
            'cfaDistributorStocks.product' => function($query) {
                $query->select('id', 'name', 'packing', 'sku', 'hsn_sac_code', 'vendor_id');
            }, 
            'client.clientDetails', 
            'address'
        ])->findOrFail($id)->withCustomFields();
        
        // Ensure items are properly ordered - display exactly what's in the database
        // Items should already be unique by ID (primary key) from the database
        if ($this->invoice->items) {
            // Just ensure unique by ID in case of any query issues, then sort by field_order
            $this->invoice->items = $this->invoice->items->unique('id')->sortBy(function($item) {
                return $item->field_order ?? ($item->id ?? 999999);
            })->values();
            
            // Log if we detect any duplicate IDs (which shouldn't happen)
            $itemIds = $this->invoice->items->pluck('id')->toArray();
            $duplicateIds = array_diff_assoc($itemIds, array_unique($itemIds));
            if (!empty($duplicateIds)) {
                \Log::warning('Duplicate invoice item IDs detected in database (PDF)', [
                    'invoice_id' => $this->invoice->id,
                    'duplicate_ids' => array_unique($duplicateIds),
                    'total_items' => $this->invoice->items->count()
                ]);
            }
        }
        $this->invoiceSetting = InvoiceSetting::withoutGlobalScopes()->where('company_id', $this->invoice->company_id)->first();
        App::setLocale($this->invoiceSetting->locale ?? 'en');
        Carbon::setLocale($this->invoiceSetting->locale ?? 'en');
        $this->paidAmount = $this->invoice->getPaidAmount();
        $this->creditNote = 0;

        $getCustomFieldGroupsWithFields = $this->invoice->getCustomFieldGroupsWithFields();

        if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }

        if ($this->invoice->credit_note) {
            $this->creditNote = CreditNotes::where('invoice_id', $id)
                ->select('cn_number')
                ->first();
        }

        $this->discount = 0;

        if ($this->invoice->discount > 0) {
            if ($this->invoice->discount_type == 'percent') {
                $this->discount = (($this->invoice->discount / 100) * $this->invoice->sub_total);
            } else {
                $this->discount = $this->invoice->discount;
            }
        }

        $taxList = array();

        $items = InvoiceItems::whereNotNull('taxes')->where('invoice_id', $this->invoice->id)->get();

        foreach ($items as $item) {
            $taxes = json_decode($item->taxes, true);
            if (!is_array($taxes)) {
                continue;
            }
            
            foreach ($taxes as $tax) {
                $this->tax = InvoiceItems::taxbyid($tax)->first();

                if ($this->tax && !isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {

                    if ($this->invoice->calculate_tax == 'after_discount' && $this->discount > 0) {
                        $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->invoice->sub_total) * $this->discount) * ($this->tax->rate_percent / 100);
                    } else {
                        $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $item->amount * ($this->tax->rate_percent / 100);
                    }
                } elseif ($this->tax) {
                    if ($this->invoice->calculate_tax == 'after_discount' && $this->discount > 0) {
                        $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->invoice->sub_total) * $this->discount) * ($this->tax->rate_percent / 100));
                    } else {
                        $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + ($item->amount * ($this->tax->rate_percent / 100));
                    }
                }
            }
        }

        $this->taxes = $taxList;

        $this->company = $this->invoice->company;
        $this->settings = company();
        $this->invoiceSetting = $this->company->invoiceSetting;

        $this->payments = Payment::with(['offlineMethod'])->where('invoice_id', $this->invoice->id)->where('status', 'complete')->orderByDesc('paid_on')->get();

        // Check if this is a CFA/Distributor invoice or CFA/Stockist invoice
        $isCFAInvoice = false;
        $isCFAStockistInvoice = false;
        $isIGSTInvoice = request('type') == 'igst' || request()->has('igst');
        // Also check stored invoice type
        if (!$isIGSTInvoice && $this->invoice->note && strpos($this->invoice->note, '<!--IGST_INVOICE-->') !== false) {
            $isIGSTInvoice = true;
        }
        
        if ($this->invoice->cfaStockistStocks && $this->invoice->cfaStockistStocks->count() > 0) {
            $isCFAStockistInvoice = true;
            $isCFAInvoice = true;
        } elseif ($this->invoice->cfaDistributorStocks && $this->invoice->cfaDistributorStocks->count() > 0) {
            $isCFAInvoice = true;
        } elseif ($this->invoice->items) {
            // Check if any item has purchase_entry_id
            foreach ($this->invoice->items as $item) {
                if ($item->purchase_entry_id) {
                    $isCFAInvoice = true;
                    break;
                }
            }
        }

        $pdf = app('dompdf.wrapper');
        $pdf->setOption('enable_php', true);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('dpi', 150);
        $pdf->setPaper('a4', 'landscape'); // Set landscape orientation for CFA invoices

        $customCss = '<style>
                * { text-transform: none !important; }
            </style>';

        // Use pharmaceutical template for CFA/Distributor or CFA/Stockist invoices
        if ($isCFAInvoice) {
            // Load complete HTML document for PDF
            $template = 'invoices.cfa-distributor.pharma-invoice';
            
            // Determine which template to use
            if ($isIGSTInvoice) {
                if ($isCFAStockistInvoice) {
                    $template = 'invoices.cfa-stockist.igst-invoice';
                } else {
                    $template = 'invoices.cfa-distributor.igst-invoice';
                }
            } elseif ($isCFAStockistInvoice) {
                $template = 'invoices.cfa-stockist.pharma-invoice';
            }
            
            $htmlContent = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . ($isIGSTInvoice ? 'IGST' : 'GST') . ' Invoice - ' . $this->invoice->invoice_number . '</title><meta name="viewport" content="width=device-width, initial-scale=1.0">' . $customCss . '</head><body style="margin:0;padding:0;">';
            $htmlContent .= view($template, $this->data)->render();
            $htmlContent .= '</body></html>';
            $pdf->loadHTML($htmlContent, 'UTF-8');
            $pdf->setPaper('a4', 'landscape'); // Ensure landscape for PDF
        } else {
            $pdf->loadHTML($customCss . view('invoices.pdf.' . $this->invoiceSetting->template, $this->data)->render());
        }

        $filename = $this->invoice->invoice_number;

        return [
            'pdf' => $pdf,
            'fileName' => $filename
        ];
    }

    public function domPdfObjectForConsoleDownload($id)
    {
        $this->invoice = Invoice::with('items')->findOrFail($id);
        $this->paidAmount = $this->invoice->getPaidAmount();
        $this->creditNote = 0;

        if ($this->invoice->credit_note) {
            $this->creditNote = CreditNotes::where('invoice_id', $id)
                ->select('cn_number')
                ->first();
        }

        if ($this->invoice->discount > 0) {
            if ($this->invoice->discount_type == 'percent') {
                $this->discount = (($this->invoice->discount / 100) * $this->invoice->sub_total);
            } else {
                $this->discount = $this->invoice->discount;
            }
        } else {
            $this->discount = 0;
        }

        $taxList = array();

        $items = InvoiceItems::whereNotNull('taxes')
            ->where('invoice_id', $this->invoice->id)
            ->get();

        foreach ($items as $item) {
            $taxes = json_decode($item->taxes, true);
            if (!is_array($taxes)) {
                continue;
            }
            
            foreach ($taxes as $tax) {
                $this->tax = InvoiceItems::taxbyid($tax)->first();

                if ($this->tax) {
                    if (!isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {

                        if ($this->invoice->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->invoice->sub_total) * $this->discount) * ($this->tax->rate_percent / 100);
                        } else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $item->amount * ($this->tax->rate_percent / 100);
                        }
                    } else {
                        if ($this->invoice->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->invoice->sub_total) * $this->discount) * ($this->tax->rate_percent / 100));
                        } else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + ($item->amount * ($this->tax->rate_percent / 100));
                        }
                    }
                }
            }
        }

        $this->taxes = $taxList;

        $this->company = $this->invoice->company;

        $this->invoiceSetting = $this->company->invoiceSetting;
        $this->payments = Payment::with(['offlineMethod'])->where('invoice_id', $this->invoice->id)->where('status', 'complete')->orderByDesc('paid_on')->get();
        $this->defaultAddress = CompanyAddress::where('is_default', 1)->where('company_id', $this->invoice->company_id)->first();

        $pdf = app('dompdf.wrapper');
        $pdf->setOption('enable_php', true);
        $pdf->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        App::setLocale($this->invoiceSetting->locale ?? 'en');
        Carbon::setLocale($this->invoiceSetting->locale ?? 'en');
        // Hide  $pdf->loadView('invoices.pdf.invoice-recurring', $this->data);
        $pdf->loadView('invoices.pdf.' . $this->invoiceSetting->template, $this->data);

        $filename = $this->invoice->invoice_number;

        return [
            'pdf' => $pdf,
            'fileName' => $filename
        ];
    }

    public function edit($id)
    {
        $this->invoice = Invoice::with('client', 'client.projects', 'items', 'items.invoiceItemImage')->findOrFail($id)->withCustomFields();
        $this->editPermission = user()->permission('edit_invoices');
        $this->invoiceSetting = invoice_setting();
        $this->userId = UserService::getUserId();

        abort_403(!(
            $this->editPermission == 'all'
            || ($this->editPermission == 'added' && ($this->invoice->added_by == $this->userId || $this->invoice->added_by == user()->id))
            || ($this->editPermission == 'owned' && $this->invoice->client_id == $this->userId)
            || ($this->editPermission == 'both' && ($this->invoice->client_id == $this->userId || $this->invoice->added_by == $this->userId || $this->invoice->added_by == user()->id))
        ));

        $this->pageTitle = $this->invoice->invoice_number;

        $this->isClient = User::isClient($this->userId);

        if ($this->isClient) {
            $this->client = User::with('clientDetails.areas')->withoutGlobalScope(ActiveScope::class)->findOrFail($this->userId);
        }

        $getCustomFieldGroupsWithFields = $this->invoice->getCustomFieldGroupsWithFields();

        if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }

        $this->projects = Project::whereNotNull('client_id')->get();
        $this->currencies = Currency::all();
        $this->categories = ProductCategory::all();
        $this->units = UnitType::all();

        $this->taxes = Tax::all();
        
        // Load purchase entries if Purchase module is enabled (for invoice editing)
        // This allows clients (CFA/Distributor) to see purchase entries even if 'purchase' is not in their user_modules()
        if (module_enabled('Purchase')) {
            // Load purchase entries instead of products for invoice editing
            $this->products = ProductPurchaseDetail::with(['product.vendor', 'product.unit'])
                ->whereHas('product') // Only entries with valid products
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $this->products = Product::all();
        }
        
        // Check if logged-in user is a CFA/Distributor
        $this->isCFADistributor = false;
        $this->stockists = collect();
        
        if ($this->isClient && $this->client && $this->client->clientDetails) {
            // Ensure areas relationship is loaded
            if (!$this->client->clientDetails->relationLoaded('areas')) {
                $this->client->clientDetails->load('areas');
            }
            
            $hasAreas = $this->client->clientDetails->areas->count() > 0;
            
            // Check if client category is CFA or Distributor (case-insensitive)
            $clientCategory = $this->client->clientDetails->category_id 
                ? ClientCategory::where('id', $this->client->clientDetails->category_id)
                    ->where('company_id', company()->id)
                    ->first()
                : null;
            
            $isCategoryCFA = false;
            if ($clientCategory) {
                $categoryName = strtolower(trim($clientCategory->category_name));
                $isCategoryCFA = (stripos($categoryName, 'cfa') !== false || stripos($categoryName, 'distributor') !== false);
            }
            
            // If category is CFA/Distributor OR if client has areas assigned (fallback for clients without category)
            if ($isCategoryCFA || $hasAreas) {
                $this->isCFADistributor = true;
                
                // Get areas allotted to this CFA/Distributor
                $allottedAreaIds = $this->client->clientDetails->areas->pluck('id')->toArray();
                
                if (!empty($allottedAreaIds)) {
                    // Get stockists in these areas
                    $this->stockists = Stockist::whereIn('area_id', $allottedAreaIds)
                        ->where('company_id', company()->id)
                        ->get();
                } else {
                    // If no areas allotted, show empty collection
                    $this->stockists = collect();
                }
            }
        }
        
        // Filter clients by region if logged-in user is a CFA/Distributor (client) - old logic
        if ($this->isClient && $this->client && $this->client->clientDetails && isset($this->client->clientDetails->region_id) && $this->client->clientDetails->region_id && !$this->isCFADistributor) {
            // Get all clients from the same region
            $regionId = $this->client->clientDetails->region_id;
            $allClients = User::allClients();
            // Load clientDetails with region_id for filtering
            $allClients->load('clientDetails');
            $this->clients = $allClients->filter(function($client) use ($regionId) {
                return $client->clientDetails && 
                       isset($client->clientDetails->region_id) &&
                       $client->clientDetails->region_id == $regionId;
            });
        } else {
            $this->clients = User::allClients();
        }
        $this->linkInvoicePermission = user()->permission('link_invoice_bank_account');
        $this->viewBankAccountPermission = user()->permission('view_bankaccount');
        $this->paymentGateway = PaymentGatewayCredentials::first();
        $this->methods = OfflinePaymentMethod::all();
        $this->invoicePayments = InvoicePaymentDetail::all();

        $bankAccounts = BankAccount::where('status', 1)->where('currency_id', $this->invoice->currency_id);

        if ($this->viewBankAccountPermission == 'added') {
            $bankAccounts = $bankAccounts->where('added_by', $this->userId);
        }

        $bankAccounts = $bankAccounts->get();
        $this->bankDetails = $bankAccounts;
        $this->companyCurrency = Currency::where('id', company()->currency_id)->first();

        if ($this->invoice->project_id != '') {
            $companyName = Project::where('id', $this->invoice->project_id)->with('clientdetails')->first();
            $this->companyName = isset($companyName) ? ($companyName->clientdetails ? $companyName->clientdetails->company_name : '') : '';
        }

        $this->companyAddresses = CompanyAddress::all();

        if (request()->ajax()) {
            $html = view('invoices.ajax.edit', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'invoices.ajax.edit';

        return view('invoices.create', $this->data);
    }

    public function update(UpdateInvoice $request, $id)
    {
        $items = $request->item_name;
        $cost_per_item = $request->cost_per_item;
        $quantity = $request->quantity;
        $product = $request->product_id;
        $amount = $request->amount;
        $userId = UserService::getUserId();

        if (module_enabled('Purchase') && in_array('purchase', user_modules()) && $request->do_it_later == 'direct') {
            $stockAdjustment = [];

            if (is_array($product)) {

                $serviceProductIds = Product::whereIn('id', $product)->where('type', 'service')->pluck('id')->toArray();
                $nonServiceProductIds = array_diff($product, $serviceProductIds);

                foreach ($nonServiceProductIds as $key => $productId) {
                    if (!is_null($productId)) {
                        if (!isset($stockAdjustment[$productId])) {
                            $stockAdjustment[$productId] = 0;
                        }

                        $stockAdjustment[$productId] += $quantity[$key];
                    }
                }
            }

            $check = [];
            $invoiceItems = InvoiceItems::whereHas('invoice', function ($invoiceQuery) {
                $invoiceQuery->where('status', 'unpaid');
            })->get();

            foreach ($stockAdjustment as $index => $quantityCount) {
                $commitedStock = $invoiceItems->filter(function ($value, $key) use ($index) {
                    return $value->product_id == $index;
                })->sum('quantity');

                $qty = PurchaseStockAdjustment::where('product_id', $index)->sum('net_quantity');
                $productQuantity = InvoiceItems::select('quantity')->where('invoice_id', $id)->first();
                $productQty = $productQuantity->quantity;
                $remainingStock = $commitedStock - $productQty;

                if (($remainingStock + $quantityCount) > $qty) {
                    $check[] = $index;
                }
            }

            if (!empty($check && $productId)) {
                return Reply::dataOnly(['status' => 'error', 'data' => $check, 'showValue' => true, 'title' => $this->pageTitle]);
            }
        }

        foreach ($quantity as $qty) {
            if (!is_numeric($qty) && $qty < 1) {
                return Reply::error(__('messages.quantityNumber'));
            }
        }

        foreach ($cost_per_item as $rate) {
            if (!is_numeric($rate)) {
                return Reply::error(__('messages.unitPriceNumber'));
            }
        }

        foreach ($amount as $amt) {
            if (!is_numeric($amt)) {
                return Reply::error(__('messages.amountNumber'));
            }
        }

        foreach ($items as $itm) {
            if (is_null($itm)) {
                return Reply::error(__('messages.itemBlank'));
            }
        }

        $invoice = Invoice::findOrFail($id);

        $invoice->project_id = $request->project_id ?? null;
        $invoice->client_id = ($request->client_id) ? $request->client_id : null;
        $invoice->stockist_id = ($request->stockist_id) ?: null;
        $invoice->issue_date = companyToYmd($request->issue_date);
        $invoice->due_date = companyToYmd($request->due_date);
        $invoice->sub_total = round($request->sub_total, 2);
        $invoice->discount = round($request->discount_value, 2);
        $invoice->discount_type = $request->discount_type;
        $invoice->total = round($request->total, 2);
        $invoice->due_amount = round($request->total, 2);
        $invoice->currency_id = $request->currency_id;
        $invoice->default_currency_id = company()->currency_id;
        $invoice->exchange_rate = $request->exchange_rate;

        if ($request->has('status')) {
            $invoice->status = $request->status;
        }

        $invoice->recurring = $request->recurring_payment;
        $invoice->billing_frequency = $request->recurring_payment == 'yes' ? $request->billing_frequency : null;
        $invoice->billing_interval = $request->recurring_payment == 'yes' ? $request->billing_interval : null;
        $invoice->billing_cycle = $request->recurring_payment == 'yes' ? $request->billing_cycle : null;
        $invoice->note = trim_editor($request->note);
        $invoice->show_shipping_address = $request->show_shipping_address;
        $invoice->invoice_number = $request->invoice_number;
        $invoice->company_address_id = $request->company_address_id;
        $invoice->bank_account_id = $request->bank_account_id;
        $invoice->payment_status = $request->payment_status == null ? '0' : $request->payment_status;
        $invoice->invoice_payment_id = $request->invoice_payment_id;
        $invoice->save();

        // To add custom fields data
        if ($request->custom_fields_data) {
            $invoice->updateCustomFieldData($request->custom_fields_data);
        }

        if ($request->has('shipping_address') || $request->has('billing_address')) {
            if ($invoice->project_id != null && $invoice->project_id != '') {
                $client = $invoice->project->clientdetails;
            } elseif ($invoice->client_id != null && $invoice->client_id != '') {
                $client = $invoice->clientdetails;
            }

            if (isset($client)) {
                
                if ($request->shipping_address != null) {
                    $client->shipping_address = $request->shipping_address;
                }
                if ($request->billing_address != null) {
                    $client->address = $request->billing_address;
                }
                $client->save();
                
            }
        }

        if (user()) {
            self::createEmployeeActivity($userId, 'invoice-updated', $invoice->id, 'invoice');
        }

        $redirectUrl = route('invoices.index');

        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => $redirectUrl, 'invoiceID' => $invoice->id]);
    }

    public function show($id)
    {
        $this->invoice = Invoice::with('project', 'items', 'items.unit', 'items.invoiceItemImage', 'invoicePaymentDetail')->findOrFail($id)->withCustomFields();
        /* Used for cancel invoice condition */
        $this->firstInvoice = Invoice::orderBy('id', 'desc')->first();
        $this->userId = UserService::getUserId();

        $this->viewPermission = user()->permission('view_invoices');
        $this->deletePermission = user()->permission('delete_invoices');
        $viewProjectInvoicePermission = user()->permission('view_project_invoices');
        $this->addInvoicesPermission = user()->permission('add_invoices');

        abort_403(!(
            $this->viewPermission == 'all'
            || ($this->viewPermission == 'added' && ($this->invoice->added_by == $this->userId || $this->invoice->added_by == user()->id))
            || ($this->viewPermission == 'owned' && $this->invoice->client_id == $this->userId && $this->invoice->send_status)
            || ($this->viewPermission == 'both' && ($this->invoice->added_by == $this->userId || $this->invoice->added_by == user()->id || $this->invoice->client_id == $this->userId))
            || ($viewProjectInvoicePermission == 'owned' && $this->invoice->client_id == $this->userId && $this->invoice->send_status)
        ));

        $getCustomFieldGroupsWithFields = $this->invoice->getCustomFieldGroupsWithFields();

        if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }

        $this->paidAmount = $this->invoice->getPaidAmount();
        $this->pageTitle = $this->invoice->invoice_number;

        $this->firstInvoice = Invoice::orderBy('id', 'desc')->first();

        $this->discount = 0;

        if ($this->invoice->discount > 0) {
            if ($this->invoice->discount_type == 'percent') {
                $this->discount = (($this->invoice->discount / 100) * $this->invoice->sub_total);
            } else {
                $this->discount = $this->invoice->discount;
            }
        }

        if ($this->invoice->discount_type == 'percent') {
            $discountAmount = $this->invoice->discount;
            $this->discountType = $discountAmount . '%';
        } else {
            $discountAmount = $this->invoice->discount;
            $this->discountType = currency_format($discountAmount, $this->invoice->currency_id);
        }

        $taxList = array();

        $items = InvoiceItems::whereNotNull('taxes')
            ->where('invoice_id', $this->invoice->id)
            ->get();

        foreach ($items as $item) {
            $taxes = json_decode($item->taxes, true);
            if (!is_array($taxes)) {
                continue;
            }
            
            foreach ($taxes as $tax) {
                $this->tax = InvoiceItems::taxbyid($tax)->first();

                if ($this->tax && !isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {

                    if ($this->invoice->calculate_tax == 'after_discount' && $this->discount > 0) {
                        $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->invoice->sub_total) * $this->discount) * ($this->tax->rate_percent / 100);
                    } else {
                        $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $item->amount * ($this->tax->rate_percent / 100);
                    }
                } elseif ($this->tax) {
                    if ($this->invoice->calculate_tax == 'after_discount' && $this->discount > 0) {
                        $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->invoice->sub_total) * $this->discount) * ($this->tax->rate_percent / 100));
                    } else {
                        $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + ($item->amount * ($this->tax->rate_percent / 100));
                    }
                }
            }
        }

        $this->taxes = $taxList;
        $this->payments = Payment::with(['offlineMethod'])->where('invoice_id', $this->invoice->id)->where('status', 'complete')->orderByDesc('paid_on')->get();

        $this->settings = company();
        $this->invoiceSetting = invoice_setting();
        $this->creditNote = 0;

        $this->credentials = PaymentGatewayCredentials::first();
        $this->methods = OfflinePaymentMethod::activeMethod();

        if (in_array('client', user_roles())) {
            $lastViewed = now();
            $ipAddress = request()->ip();
            $this->invoice->last_viewed = $lastViewed;
            $this->invoice->ip_address = $ipAddress;
            $this->invoice->save();
        }

        return view('invoices.show', $this->data);
    }

    public function approveOfflineInvoice($invoiceID)
    {
        $invoice = Invoice::with(['project', 'project.client', 'payment'])->findOrFail($invoiceID);

        if ($invoice) {

            $payment = Payment::findOrFail($invoice->payment[0]->id);

            if ($invoice->status == 'pending-confirmation') {

                $invoiceAmt = (float)($invoice->total);
                $paymentAmt = (float)($payment->amount);

                if ($invoiceAmt > $paymentAmt) {
                    $invoice->status = 'partial';
                }
                $invoice->status = 'paid';
            }

            $invoice->save();

            $payment->bank_account_id = $invoice->bank_account_id;
            $payment->status = 'complete';
            $payment->save();

            if ($invoice->project_id != null && $invoice->project_id != '') {
                $notifyUser = $invoice->project->client;
            } elseif ($invoice->client_id != null && $invoice->client_id != '') {
                $notifyUser = $invoice->client;
            }
            if (isset($notifyUser) && !is_null($notifyUser)) {
                event(new NewPaymentEvent($payment, $notifyUser));
            }

            return Reply::success(__('messages.offlineInvoiceApproved'));
        }
    }

    public function sendInvoice($invoiceID)
    {
        $invoice = Invoice::with(['project', 'project.client'])->findOrFail($invoiceID);

        if ($invoice->project_id != null && $invoice->project_id != '') {
            $notifyUser = $invoice->project->client;
        } elseif ($invoice->client_id != null && $invoice->client_id != '') {
            $notifyUser = $invoice->client;
        }
        if (isset($notifyUser) && !is_null($notifyUser) && request()->data_type != 'mark_as_send') {
            event(new NewInvoiceEvent($invoice, $notifyUser));
        }

        $invoice->send_status = 1;

        if ($invoice->status == 'draft') {
            $invoice->status = 'unpaid';
        }

        $invoice->save();

        if (request()->data_type == 'mark_as_send') {
            return Reply::success(__('messages.invoiceMarkAsSent'));
        }


        return Reply::success(__('messages.invoiceSentSuccessfully'));
    }

    public function remindForPayment($id)
    {
        $invoice = Invoice::with(['project', 'project.client'])->findOrFail($id);

        if ($invoice->project_id != null && $invoice->project_id != '') {
            $notifyUser = $invoice->project->client;
        } elseif ($invoice->client_id != null && $invoice->client_id != '') {
            $notifyUser = $invoice->client;
        }
        if (isset($notifyUser) && !is_null($notifyUser)) {
            event(new PaymentReminderEvent($invoice, $notifyUser));
        }

        return Reply::success('messages.reminderMailSuccess');
    }

    public function addItem(Request $request)
    {
        // Load invoice settings for HSN code display
        $this->invoiceSetting = invoice_setting();
        
        // Check if this is for CFA Stockist invoice (uses CFA Distributor Stock)
        if ($request->has('cfa_distributor_stock_id') && $request->cfa_distributor_stock_id) {
            $cfaDistributorStock = CFADistributorStock::with([
                'product' => function($query) {
                    $query->select('id', 'name', 'hsn_sac_code', 'sku', 'packing', 'vendor_id', 'unit_id', 'taxes', 'company_id');
                },
                'product.vendor:id,primary_name,company_name',
                'product.unit:id,unit_type',
                'invoice',
                'purchaseEntry' => function($query) {
                    $query->select('id', 'product_id', 'vendor_id', 'scheme_enabled', 'total_quantity', 'free_quantity', 'batch', 'expiry', 'mrp', 'pts', 'ptr', 'dis', 'tax');
                },
                'purchaseEntry.vendor:id,primary_name,company_name'
            ])
            ->where('id', $request->cfa_distributor_stock_id)
            ->where('company_id', company()->id)
            ->first();
            
            if (!$cfaDistributorStock || !$cfaDistributorStock->product) {
                return Reply::error('CFA Distributor Stock not found.');
            }
            
            // Set items to the product from CFA Distributor Stock
            $this->items = $cfaDistributorStock->product;
            
            // Ensure product has required properties
            if (!$this->items) {
                return Reply::error('Product not found for this CFA Distributor Stock entry.');
            }
            
            // Calculate total available stock for this product from all CFA Distributor Stock entries
            // This shows the total stock available across all batches for the product
            $productId = $cfaDistributorStock->product_id;
            $cfaDistributorId = $cfaDistributorStock->cfa_distributor_id;
            
            try {
                $totalAvailableStock = CFADistributorStock::where('product_id', $productId)
                    ->where('cfa_distributor_id', $cfaDistributorId)
                    ->where('available_quantity', '>', 0)
                    ->where('company_id', company()->id)
                    ->whereHas('invoice', function($query) {
                        $query->where('delivery_status', 'received');
                    })
                    ->sum('available_quantity');
            } catch (\Exception $e) {
                // Fallback: if delivery_status column doesn't exist or relationship fails, get all available stocks
                \Log::warning('Error loading total available stock with delivery_status filter', ['error' => $e->getMessage()]);
                $totalAvailableStock = CFADistributorStock::where('product_id', $productId)
                    ->where('cfa_distributor_id', $cfaDistributorId)
                    ->where('available_quantity', '>', 0)
                    ->where('company_id', company()->id)
                    ->sum('available_quantity');
            }
            
            // Add stock information - use total available stock from all batches
            $this->items->available_stock = max(0, $totalAvailableStock ?? 0);
            $this->items->cfa_distributor_stock_id = $cfaDistributorStock->id;
            $this->items->batch = $cfaDistributorStock->batch ?? '';
            // Format expiry date as month-year only (YYYY-MM format for month input)
            if ($cfaDistributorStock->expiry) {
                if ($cfaDistributorStock->expiry instanceof \Carbon\Carbon) {
                    $this->items->expiry = $cfaDistributorStock->expiry->format('Y-m');
                } elseif (is_string($cfaDistributorStock->expiry)) {
                    // Extract year-month from date string
                    $this->items->expiry = substr($cfaDistributorStock->expiry, 0, 7);
                } else {
                    $this->items->expiry = $cfaDistributorStock->expiry;
                }
            } else {
                $this->items->expiry = null;
            }
            $this->items->pts = $cfaDistributorStock->pts ?? 0;
            $this->items->ptr = $cfaDistributorStock->ptr ?? 0;
            $this->items->mrp = $cfaDistributorStock->mrp ?? 0;
            $this->items->dis = $cfaDistributorStock->dis ?? 0;
            
            // Set HSN/SKU code - same priority as CFA Distributor invoice: hsn_sac_code ?? sku
            // Ensure both fields are explicitly set on items object for the view
            $productSku = $cfaDistributorStock->product->sku ?? null;
            $productHsnSac = $cfaDistributorStock->product->hsn_sac_code ?? null;
            
            // Get HSN/SKU code - priority: hsn_sac_code ?? sku
            $hsnCode = $productHsnSac ?? $productSku ?? null;
            
            if ($hsnCode) {
                // Set hsn_sac_code (primary field)
                $this->items->hsn_sac_code = $hsnCode;
                // Also set sku if product has it, otherwise use hsn_sac_code as fallback
                $this->items->sku = $productSku ?? $hsnCode;
            } else {
                // If still empty, try to get it directly from database
                $productFromDb = \App\Models\Product::select('id', 'hsn_sac_code', 'sku')
                    ->where('id', $cfaDistributorStock->product->id)
                    ->first();
                if ($productFromDb) {
                    $hsnCode = $productFromDb->hsn_sac_code ?? $productFromDb->sku ?? null;
                    if ($hsnCode) {
                        $this->items->hsn_sac_code = $hsnCode;
                        $this->items->sku = $productFromDb->sku ?? $hsnCode;
                    }
                }
            }
            
            // Ensure packing is set (from product)
            if (!isset($this->items->packing)) {
                $this->items->packing = $cfaDistributorStock->product->packing ?? '';
            }
            
            // Add vendor information (MFR) - prioritize purchase entry vendor over product vendor
            // Prioritize company_name over primary_name for MFR display
            if ($cfaDistributorStock->purchaseEntry && $cfaDistributorStock->purchaseEntry->vendor) {
                $this->items->vendor_name = $cfaDistributorStock->purchaseEntry->vendor->company_name ?? ($cfaDistributorStock->purchaseEntry->vendor->primary_name ?? '');
            } elseif ($cfaDistributorStock->product->vendor) {
                $this->items->vendor_name = $cfaDistributorStock->product->vendor->company_name ?? ($cfaDistributorStock->product->vendor->primary_name ?? '');
            } else {
                $this->items->vendor_name = '';
            }
            
            // Load scheme from purchase entry if available
            // Check if purchase entry exists and has scheme enabled
            if ($cfaDistributorStock->purchaseEntry) {
                $purchaseEntry = $cfaDistributorStock->purchaseEntry;
                // Check scheme_enabled (can be boolean true, integer 1, or string '1')
                $schemeEnabled = $purchaseEntry->scheme_enabled ?? false;
                if ($schemeEnabled == true || $schemeEnabled == 1 || $schemeEnabled === '1') {
                    $schemeInfo = '';
                    $totalQty = $purchaseEntry->total_quantity ?? null;
                    $freeQty = $purchaseEntry->free_quantity ?? null;
                    
                    if ($totalQty && $freeQty) {
                        $schemeInfo = $totalQty . '+' . $freeQty;
                    } elseif ($totalQty) {
                        $schemeInfo = (string)$totalQty;
                    }
                    $this->items->scheme = $schemeInfo;
                } else {
                    $this->items->scheme = '';
                }
            } else {
                $this->items->scheme = '';
            }
            
            // Load batches from CFA Distributor Stock for this product
            $productId = $cfaDistributorStock->product->id;
            $cfaDistributorId = $cfaDistributorStock->cfa_distributor_id;
            
            // Load batches - only include stocks from invoices with delivery_status = 'received'
            try {
                $batchesQuery = CFADistributorStock::where('product_id', $productId)
                    ->where('cfa_distributor_id', $cfaDistributorId)
                    ->where('available_quantity', '>', 0)
                    ->where('company_id', company()->id)
                    ->whereHas('invoice', function($query) {
                        $query->where('delivery_status', 'received');
                    })
                    ->select('id', 'batch', 'expiry', 'mrp', 'pts', 'ptr', 'dis', 'available_quantity', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->get();
            } catch (\Exception $e) {
                // Fallback: if delivery_status column doesn't exist or relationship fails, get all available stocks
                \Log::warning('Error loading batches with delivery_status filter', ['error' => $e->getMessage()]);
                $batchesQuery = CFADistributorStock::where('product_id', $productId)
                    ->where('cfa_distributor_id', $cfaDistributorId)
                    ->where('available_quantity', '>', 0)
                    ->where('company_id', company()->id)
                    ->select('id', 'batch', 'expiry', 'mrp', 'pts', 'ptr', 'dis', 'available_quantity', 'created_at')
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
            
            // Process batches
            $this->batches = $batchesQuery->map(function($stock) {
                return (object)[
                    'id' => $stock->id,
                    'cfa_distributor_stock_id' => $stock->id,
                    'batch' => $stock->batch ?? 'No Batch',
                    'expiry' => $stock->expiry,
                    'mrp' => $stock->mrp ?? 0,
                    'pts' => $stock->pts ?? 0,
                    'ptr' => $stock->ptr ?? 0,
                    'dis' => $stock->dis ?? 0,
                    'display_dis' => $stock->dis ?? 0,
                    'available_quantity' => $stock->available_quantity ?? 0,
                    'created_at' => $stock->created_at
                ];
            });
            
            // Set purchaseEntry for CFA Stockist invoices (from CFA Distributor Stock)
            $this->purchaseEntry = $cfaDistributorStock->purchaseEntry ?? null;
            
            // Check if we're loading an existing invoice item (for edit mode)
            $invoiceItem = null;
            if ($request->has('invoice_item_id') && $request->invoice_item_id) {
                $invoiceItem = InvoiceItems::find($request->invoice_item_id);
            }
            
            try {
                $exchangeRate = Currency::findOrFail($request->currencyId);
                
                if ($exchangeRate->exchange_rate == $request->exchangeRate) {
                    $exRate = $exchangeRate->exchange_rate;
                } else {
                    $exRate = floatval($request->exchangeRate ?: 1);
                }
            } catch (\Exception $e) {
                // Fallback to default exchange rate if currency not found
                $exRate = floatval($request->exchangeRate ?: 1);
            }
            
            // If loading existing invoice item, use saved values; otherwise use CFA Distributor Stock defaults
            if ($invoiceItem) {
                $this->items->price = $invoiceItem->unit_price ?? 0;
                $this->items->taxes = $invoiceItem->taxes;
                // Use saved scheme, MFR from invoice item if available
                if (isset($invoiceItem->scheme)) {
                    $this->items->scheme = $invoiceItem->scheme;
                }
                if (isset($invoiceItem->mfr)) {
                    $this->items->vendor_name = $invoiceItem->mfr;
                }
            } else {
                // Use PTS as default price for CFA Distributor Stock
                // Convert to invoice currency if exchange rate is not 1
                $basePrice = $cfaDistributorStock->pts ?? 0;
                if ($exRate > 0 && $exRate != 1) {
                    $this->items->price = floatval($basePrice) / floatval($exRate);
                } else {
                    $this->items->price = floatval($basePrice);
                }
                $this->items->price = number_format((float)$this->items->price, 2, '.', '');
                
                // Load taxes - prioritize purchase entry taxes over product taxes
                // Purchase entry taxes are more specific and reflect the actual taxes used in the CFA Distributor invoice
                $purchaseEntryTaxes = null;
                if ($cfaDistributorStock->purchaseEntry && $cfaDistributorStock->purchaseEntry->tax) {
                    $purchaseEntryTaxes = $cfaDistributorStock->purchaseEntry->tax;
                    // Purchase entry tax is cast as array in the model
                    if (is_array($purchaseEntryTaxes) && !empty($purchaseEntryTaxes)) {
                        $this->items->taxes = json_encode($purchaseEntryTaxes);
                    } elseif (is_string($purchaseEntryTaxes)) {
                        $decoded = json_decode($purchaseEntryTaxes, true);
                        $this->items->taxes = is_array($decoded) && !empty($decoded) ? json_encode($decoded) : $purchaseEntryTaxes;
                    } else {
                        $this->items->taxes = null;
                    }
                }
                
                // Fallback to product taxes if purchase entry doesn't have taxes
                if (empty($this->items->taxes) || $this->items->taxes === null) {
                    $productTaxes = $cfaDistributorStock->product->taxes ?? null;
                    if ($productTaxes) {
                        // If taxes is a JSON string, ensure it's valid JSON
                        if (is_string($productTaxes)) {
                            $decodedTaxes = json_decode($productTaxes, true);
                            // If decoding succeeded and it's an array, use it; otherwise keep original
                            if (is_array($decodedTaxes) && !empty($decodedTaxes)) {
                                $this->items->taxes = json_encode($decodedTaxes);
                            } else {
                                // Try to parse as comma-separated values or single value
                                $this->items->taxes = $productTaxes;
                            }
                        } elseif (is_array($productTaxes) && !empty($productTaxes)) {
                            // If it's already an array, encode it
                            $this->items->taxes = json_encode($productTaxes);
                        } else {
                            $this->items->taxes = $productTaxes;
                        }
                    } else {
                        $this->items->taxes = null;
                    }
                }
            }
            
            $this->exchangeRate = $exRate;
            $this->invoiceItem = $invoiceItem;
            
            $this->taxes = Tax::all();
            $this->units = UnitType::all();
            
            // Pass type parameter to view for IGST invoice handling
            $this->invoiceType = $request->get('type', '');
            $this->data['invoiceType'] = $this->invoiceType;
            $this->data['isIGSTInvoice'] = ($this->invoiceType == 'igst');
            
            // Ensure batches is set (even if empty)
            if (!isset($this->batches)) {
                $this->batches = collect([]);
            }
            
            try {
                $view = view('invoices.cfa-stockist.ajax.add-item', $this->data)->render();
                return Reply::dataOnly(['status' => 'success', 'view' => $view]);
            } catch (\Exception $e) {
                \Log::error('Error rendering CFA Stockist add-item view', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return Reply::error('Error loading product: ' . $e->getMessage());
            }
        }
        
        // Load purchase entry directly (invoice is ONLY associated with purchase entries)
        $purchaseEntry = null;
        
        // Load by purchase_entry_id if provided
        if ($request->has('purchase_entry_id')) {
            $purchaseEntry = ProductPurchaseDetail::with([
                'product' => function($query) {
                    // Select all necessary fields including hsn_sac_code and sku (HSN can be in either field)
                    // IMPORTANT: Must include 'name' field for product name to display correctly
                    $query->select('id', 'name', 'hsn_sac_code', 'sku', 'packing', 'vendor_id', 'unit_id', 'taxes', 'company_id');
                }, 
                'product.vendor:id,primary_name,company_name', 
                'product.unit:id,unit_type', 
                'vendor:id,primary_name,company_name'
            ])
                ->find($request->purchase_entry_id);
        }
        // Otherwise try by id
        elseif ($request->has('id')) {
            $purchaseEntry = ProductPurchaseDetail::with([
                'product' => function($query) {
                    // Select all necessary fields including hsn_sac_code and sku (HSN can be in either field)
                    // IMPORTANT: Must include 'name' field for product name to display correctly
                    $query->select('id', 'name', 'hsn_sac_code', 'sku', 'packing', 'vendor_id', 'unit_id', 'taxes', 'company_id');
                }, 
                'product.vendor:id,primary_name,company_name', 
                'product.unit:id,unit_type', 
                'vendor:id,primary_name,company_name'
            ])
                ->find($request->id);
        }
        
        if (!$purchaseEntry || !$purchaseEntry->product) {
            return Reply::error('Purchase entry not found.');
        }
        
        // Set items to the product from purchase entry
        $this->items = $purchaseEntry->product;
        
        // Ensure HSN/SKU code is available from product - match purchase entry form logic exactly
        // Purchase entry form shows: product->hsn_sac_code ?? product->sku
        // Since $this->items = $purchaseEntry->product, they're the same object
        // But we need to ensure both hsn_sac_code and sku are explicitly available
        if ($this->items && $purchaseEntry->product) {
            // Get HSN/SKU code - same priority as purchase entry form: hsn_sac_code ?? sku
            $hsnCode = $purchaseEntry->product->hsn_sac_code ?? $purchaseEntry->product->sku ?? null;
            
            // Ensure SKU is explicitly set on items object for the view
            // Only set SKU, not hsn_sac_code (as requested)
            if (!empty($purchaseEntry->product->sku)) {
                $this->items->sku = $purchaseEntry->product->sku;
            }
            
            // If still empty, try to get it directly from database
            if (empty($hsnCode)) {
                $productFromDb = \App\Models\Product::select('id', 'hsn_sac_code', 'sku')
                    ->where('id', $purchaseEntry->product->id)
                    ->first();
                if ($productFromDb) {
                    // Same logic as purchase entry form: hsn_sac_code ?? sku
                    $hsnCode = $productFromDb->hsn_sac_code ?? $productFromDb->sku ?? null;
                    if (!empty($hsnCode)) {
                        // Set on items object so it's available in view (priority 2)
                        $this->items->hsn_sac_code = $hsnCode;
                        // Also set sku explicitly
                        if (!empty($productFromDb->sku)) {
                            $this->items->sku = $productFromDb->sku;
                        }
                        // Update purchase entry product object too (for priority 3)
                        $purchaseEntry->product->hsn_sac_code = $hsnCode;
                        if (!empty($productFromDb->sku)) {
                            $purchaseEntry->product->sku = $productFromDb->sku;
                        }
                    }
                }
            } else {
                // If HSN/SKU code exists, ensure it's explicitly set on items object
                // This is critical for priority 2 to work in the view
                $this->items->hsn_sac_code = $hsnCode;
                // Also ensure SKU is explicitly available on items if product has it
                if (!empty($purchaseEntry->product->sku)) {
                    $this->items->sku = $purchaseEntry->product->sku;
                }
                // If we got hsnCode from sku field, also set it on sku property
                if (empty($purchaseEntry->product->hsn_sac_code) && !empty($purchaseEntry->product->sku)) {
                    $this->items->sku = $purchaseEntry->product->sku;
                }
            }
            
            // Debug: Log what we're setting
            \Log::info('HSN/SKU set in addItem controller', [
                'product_id' => $purchaseEntry->product->id,
                'product_name' => $purchaseEntry->product->name,
                'product_hsn_sac_code' => $purchaseEntry->product->hsn_sac_code ?? 'NULL',
                'product_sku' => $purchaseEntry->product->sku ?? 'NULL',
                'items_hsn_sac_code' => $this->items->hsn_sac_code ?? 'NULL',
                'items_sku' => $this->items->sku ?? 'NULL',
                'final_hsnCode' => $hsnCode ?? 'NULL'
            ]);
        }
        
        // Add vendor information from purchase entry (not product vendor)
        // Prioritize company_name over primary_name for MFR display
        if ($purchaseEntry->vendor) {
            $this->items->purchase_entry_vendor = $purchaseEntry->vendor;
            $this->items->vendor_name = $purchaseEntry->vendor->company_name ?? ($purchaseEntry->vendor->primary_name ?? '');
        } else {
            // Fallback to product vendor if purchase entry vendor is not set
            $this->items->purchase_entry_vendor = $purchaseEntry->product->vendor ?? null;
            if ($purchaseEntry->product->vendor) {
                $this->items->vendor_name = $purchaseEntry->product->vendor->company_name ?? ($purchaseEntry->product->vendor->primary_name ?? '');
            } else {
                $this->items->vendor_name = '';
            }
        }
        
        // Add scheme information to items if purchase entry has scheme
        if ($purchaseEntry->scheme_enabled) {
            $schemeInfo = '';
            if ($purchaseEntry->total_quantity && $purchaseEntry->free_quantity) {
                $schemeInfo = $purchaseEntry->total_quantity . '+' . $purchaseEntry->free_quantity;
            } elseif ($purchaseEntry->total_quantity) {
                $schemeInfo = $purchaseEntry->total_quantity;
            }
            $this->items->scheme = $schemeInfo;
        } else {
            $this->items->scheme = '';
        }
        
        // Load all batches for this product from purchase entries (with company_id filtering)
        $productId = $purchaseEntry->product->id;
        $companyId = company()->id;
        
        $batchesQuery = ProductPurchaseDetail::where('product_id', $productId)
            ->whereHas('product', function($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->whereNotNull('batch')
            ->where('batch', '!=', '')
            ->select('id', 'batch', 'expiry', 'mrp', 'pts', 'ptr', 'dis', 'discount', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Remove duplicate batches - group by batch value and keep the most recent one
        $uniqueBatches = [];
        foreach ($batchesQuery as $batch) {
            $batchValue = trim($batch->batch);
            if (!isset($uniqueBatches[$batchValue])) {
                $uniqueBatches[$batchValue] = $batch;
            } else {
                // Keep the most recent one (already sorted by created_at desc)
                if ($batch->created_at > $uniqueBatches[$batchValue]->created_at) {
                    $uniqueBatches[$batchValue] = $batch;
                }
            }
        }
        
        // Process batches to prioritize discount over dis
        $this->batches = collect($uniqueBatches)->map(function($batch) {
            // Add a computed field for display that prioritizes discount
            $batch->display_dis = $batch->discount ?? $batch->dis ?? '';
            return $batch;
        })->values();
        
        // Use purchase entry data directly
        $this->purchaseEntry = $purchaseEntry;
        
        // Check if we're loading an existing invoice item (for edit mode)
        $invoiceItem = null;
        if ($request->has('invoice_item_id') && $request->invoice_item_id) {
            $invoiceItem = InvoiceItems::find($request->invoice_item_id);
        }
        
        // Get TOTAL product stock from PurchaseStockAdjustment (net_quantity)
        // This is the actual available stock for the product across all purchase entries
        $productId = $purchaseEntry->product_id;
        $stockAdjustment = \Modules\Purchase\Entities\PurchaseStockAdjustment::where('product_id', $productId)
            ->where('company_id', company()->id)
            ->first();
        
        $totalProductStock = $stockAdjustment ? $stockAdjustment->net_quantity : 0;
        
        // Add stock information to items
        // available_stock = total product stock (what's actually available to sell)
        $this->items->available_stock = max(0, $totalProductStock);
        
        $this->invoiceSetting = invoice_setting();

        $exchangeRate = Currency::findOrFail($request->currencyId);

        if ($exchangeRate->exchange_rate == $request->exchangeRate) {
            $exRate = $exchangeRate->exchange_rate;
        } else {
            $exRate = floatval($request->exchangeRate ?: 1);
        }

        // If loading existing invoice item, use saved values; otherwise use purchase entry defaults
        if ($invoiceItem) {
            // Use saved values from invoice item for editable fields
            $this->items->price = $invoiceItem->unit_price ?? 0;
            $this->items->taxes = $invoiceItem->taxes;
            $this->items->quantity = $invoiceItem->quantity ?? 1;
            $this->items->amount = $invoiceItem->amount ?? 0;
            // Set pharma-specific fields from invoice item
            $this->items->scheme = $invoiceItem->scheme;
            $this->items->pack = $invoiceItem->pack;
            $this->items->mfr = $invoiceItem->mfr;
            $this->items->batch = $invoiceItem->batch;
            $this->items->exp = $invoiceItem->exp;
            
            // IMPORTANT: PTS, PTR, MRP, DIS should come from purchase entry (source of truth)
            // Only use invoice item values if purchase entry doesn't have them
            $this->items->mrp = $purchaseEntry->mrp ?? $invoiceItem->mrp ?? null;
            $this->items->pts = $purchaseEntry->pts ?? $invoiceItem->pts ?? null;
            $this->items->ptr = $purchaseEntry->ptr ?? $invoiceItem->ptr ?? null;
            $this->items->dis = $purchaseEntry->dis ?? $purchaseEntry->discount ?? $invoiceItem->dis ?? null;
            
            // For display in view, keep purchase entry values as-is (don't overwrite with invoice item)
            // Purchase entry is the source of truth for PTS, PTR, MRP, DIS
            // Only update batch/expiry if they differ (these can be edited per invoice)
            if ($invoiceItem->batch && $invoiceItem->batch != $purchaseEntry->batch) {
                $purchaseEntry->batch = $invoiceItem->batch;
            }
            if ($invoiceItem->exp && $invoiceItem->exp != $purchaseEntry->expiry) {
                $purchaseEntry->expiry = $invoiceItem->exp;
            }
        } else {
            // For CFA/Distributor invoices, use PTS (Price to Stockist) as the base price
            // Priority: PTS > PTR > MRP
            $basePrice = $purchaseEntry->pts ?? $purchaseEntry->ptr ?? $purchaseEntry->mrp ?? 0;
            
            // Convert to invoice currency if exchange rate is not 1
            if (!is_null($exchangeRate) && !is_null($exchangeRate->exchange_rate) && $exchangeRate->exchange_rate > 0) {
                $this->items->price = floatval($basePrice) / floatval($exRate);
            } else {
                $this->items->price = floatval($basePrice);
            }

            $this->items->price = number_format((float)$this->items->price, 2, '.', '');
            
            // Load taxes from purchase entry (prioritize purchase entry taxes over product taxes)
            $purchaseEntryTaxes = null;
            if ($purchaseEntry->tax && is_array($purchaseEntry->tax)) {
                $purchaseEntryTaxes = $purchaseEntry->tax;
            } elseif ($purchaseEntry->tax && is_string($purchaseEntry->tax)) {
                // Try to decode if it's JSON string
                $decoded = json_decode($purchaseEntry->tax, true);
                $purchaseEntryTaxes = is_array($decoded) ? $decoded : null;
            }
            
            // Set taxes on items - use purchase entry taxes if available, otherwise use product taxes
            if ($purchaseEntryTaxes && !empty($purchaseEntryTaxes)) {
                $this->items->taxes = json_encode($purchaseEntryTaxes);
            } else {
                // Fallback to product taxes if purchase entry doesn't have taxes
                $this->items->taxes = $purchaseEntry->product->taxes ?? null;
            }
        }
        
        // Pass invoiceItem to view for proper value display
        $this->invoiceItem = $invoiceItem;
        
        // Ensure purchaseEntry is passed to view for HSN code access
        $this->purchaseEntry = $purchaseEntry;
        
        $this->taxes = Tax::all();
        $this->units = UnitType::all();
        
        // Pass type parameter to view for IGST invoice handling
        $this->invoiceType = $request->get('type', '');
        
        $view = view('invoices.ajax.add_item', $this->data)->render();

        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }

    /**
     * Get batches for a specific product from purchase entries only
     */
    public function getProductBatches(Request $request)
    {
        try {
            $productId = $request->product_id;
            
            if (!$productId) {
                return Reply::error('Product ID is required.');
            }
            
            if (!module_enabled('Purchase')) {
                return Reply::dataOnly(['status' => 'success', 'data' => []]);
            }
            
            // Ensure company_id is always set and matches
            $companyId = company()->id;
            if (!$companyId) {
                return Reply::error('Company ID is not set.');
            }
            
            // Get batches ONLY from purchase entries - ensure company_id matches
            $purchaseEntries = ProductPurchaseDetail::where('product_id', $productId)
                ->join('products', 'product_purchase_details.product_id', '=', 'products.id')
                ->where('products.company_id', $companyId)
                ->whereNotNull('product_purchase_details.batch')
                ->where('product_purchase_details.batch', '!=', '')
                ->select('product_purchase_details.*')
                ->orderBy('product_purchase_details.created_at', 'desc')
                ->get();
            
            $batches = [];
            $seenBatches = [];
            
            foreach ($purchaseEntries as $entry) {
                $batchValue = trim($entry->batch);
                if (!empty($batchValue) && !in_array($batchValue, $seenBatches)) {
                    $seenBatches[] = $batchValue;
                    
                    // Format created_at as "Month Year" (e.g., "December 2025")
                    $createdAt = $entry->created_at;
                    $createdMonth = null;
                    if ($createdAt) {
                        if (is_string($createdAt)) {
                            $createdAt = \Carbon\Carbon::parse($createdAt);
                        }
                        $createdMonth = $createdAt->format('F Y'); // e.g., "December 2025"
                    }
                    
                    // DIS field: prioritize discount (actively used) over dis (legacy)
                    $disValue = null;
                    if (isset($entry->discount) && $entry->discount !== null && $entry->discount !== '') {
                        $disValue = $entry->discount;
                    } elseif (isset($entry->dis) && $entry->dis !== null && $entry->dis !== '') {
                        $disValue = $entry->dis;
                    }
                    
                    $batches[] = [
                        'batch' => $batchValue,
                        'purchase_entry_id' => $entry->id,
                        'expiry' => $entry->expiry ? (is_string($entry->expiry) ? $entry->expiry : $entry->expiry->format('Y-m-d')) : null,
                        'mrp' => $entry->mrp ?? '',
                        'pts' => $entry->pts ?? '',
                        'ptr' => $entry->ptr ?? '',
                        'dis' => $disValue !== null ? (string)$disValue : '',
                        'created_at' => $createdAt ? (is_string($createdAt) ? $createdAt : $createdAt->format('Y-m-d H:i:s')) : null,
                        'created_month' => $createdMonth,
                    ];
                }
            }
            
            return Reply::dataOnly(['status' => 'success', 'data' => $batches]);
        } catch (\Exception $e) {
            \Log::error('Error in getProductBatches: ' . $e->getMessage());
            return Reply::error('Error loading batches: ' . $e->getMessage());
        }
    }

    /**
     * Get products from purchase entries only (not purchase products)
     * Returns products grouped by name with all available batches
     */
    public function getConsolidatedProducts()
    {
        try {
            if (!module_enabled('Purchase')) {
                \Log::warning('Purchase module not enabled');
                return Reply::dataOnly(['status' => 'success', 'data' => []]);
            }
            
            // Load ONLY from purchase entries (ProductPurchaseDetail)
            // Get company ID - ensure it's always set
            $companyId = company()->id;
            
            if (!$companyId) {
                \Log::error('Company ID is null or empty!');
                return Reply::error('Company ID is not set. Please ensure you are logged in.');
            }
            
            \Log::info('Loading purchase entries for company: ' . $companyId);
            
            // Use join to ensure company_id matching is explicit and always enforced
            $purchaseEntries = ProductPurchaseDetail::with(['product.unit', 'product.vendor'])
                ->join('products', 'product_purchase_details.product_id', '=', 'products.id')
                ->where('products.company_id', $companyId)
                ->select('product_purchase_details.*')
                ->get();
            
            \Log::info('Purchase entries loaded: ' . $purchaseEntries->count());
            
            // Debug: Check if products exist for this company
            if ($purchaseEntries->isEmpty()) {
                \Log::warning('No purchase entries found for company: ' . $companyId);
                
                // Debug queries
                $totalProducts = \App\Models\Product::where('company_id', $companyId)->count();
                $totalEntries = ProductPurchaseDetail::count();
                $entriesWithProducts = ProductPurchaseDetail::whereHas('product')->count();
                
                \Log::info('Debug info - Total products for company: ' . $totalProducts);
                \Log::info('Debug info - Total purchase entries: ' . $totalEntries);
                \Log::info('Debug info - Entries with products: ' . $entriesWithProducts);
                
                return Reply::dataOnly([
                    'status' => 'success', 
                    'data' => [],
                    'debug' => [
                        'company_id' => $companyId,
                        'total_products' => $totalProducts,
                        'total_entries' => $totalEntries
                    ]
                ]);
            }
            
            // Group by product_id
            $groupedEntries = $purchaseEntries->groupBy('product_id');
            \Log::info('Grouped by product_id: ' . $groupedEntries->count() . ' products');
            
            $products = [];
            
            foreach ($groupedEntries as $productId => $entries) {
                // Reload product to ensure it's fresh and has company_id matching
                $product = \App\Models\Product::where('id', $productId)
                    ->where('company_id', $companyId)
                    ->first();
                
                if (!$product) {
                    \Log::warning('Product not found or company mismatch - product_id: ' . $productId . ', company_id: ' . $companyId);
                    continue;
                }
                
                // Double-check company_id matches
                if ($product->company_id != $companyId) {
                    \Log::warning('Product company_id mismatch - Product: ' . $product->company_id . ', Expected: ' . $companyId);
                    continue;
                }
                
                \Log::info('Processing product: ' . $product->name . ' (ID: ' . $productId . ', Company: ' . $product->company_id . ') with ' . $entries->count() . ' entries');
                
                // Debug: Log all batches for this product
                foreach ($entries as $entry) {
                    \Log::info('  Entry ID: ' . $entry->id . ', Batch: "' . ($entry->batch ?? 'NULL') . '"');
                }
                
                // Get unique batches for this product - include ALL entries with batch numbers
                $batches = $entries->filter(function($entry) {
                    // Include entries that have a batch number (not null, not empty string)
                    $hasBatch = !is_null($entry->batch) && trim($entry->batch) !== '';
                    if (!$hasBatch) {
                        \Log::info('  Skipping entry ID ' . $entry->id . ' - no batch number');
                    }
                    return $hasBatch;
                })
                ->map(function($entry) {
                    return [
                        'purchase_entry_id' => $entry->id,
                        'batch' => trim($entry->batch),
                        'expiry' => $entry->expiry ? (is_string($entry->expiry) ? $entry->expiry : $entry->expiry->format('Y-m-d')) : null,
                    ];
                })
                ->unique('batch')
                ->values();
                
                \Log::info('Product ' . $product->name . ' has ' . $batches->count() . ' unique batches after filtering');
                
                // Always include the product - if no batches, use first entry
                if ($batches->isEmpty()) {
                    $firstEntry = $entries->first();
                    $batches = collect([[
                        'purchase_entry_id' => $firstEntry->id,
                        'batch' => $firstEntry->batch ?? 'N/A',
                        'expiry' => $firstEntry->expiry ? (is_string($firstEntry->expiry) ? $firstEntry->expiry : $firstEntry->expiry->format('Y-m-d')) : null,
                    ]]);
                    \Log::info('Product ' . $product->name . ' has no valid batches, using first entry');
                }
                
                $products[] = [
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'display_name' => $product->name,
                    'batches' => $batches->toArray(),
                ];
            }
            
            \Log::info('Final products count: ' . count($products));
            
            // Sort by product name
            usort($products, function($a, $b) {
                return strcmp($a['product_name'], $b['product_name']);
            });
            
            return Reply::dataOnly(['status' => 'success', 'data' => $products]);
        } catch (\Exception $e) {
            \Log::error('Error in getConsolidatedProducts: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return Reply::error('Error loading products: ' . $e->getMessage());
        }
    }

    public function getBatchDetails(Request $request)
    {
        $purchaseEntryId = $request->purchase_entry_id;
        
        if (!$purchaseEntryId) {
            return Reply::error('Purchase entry ID is required.');
        }
        
        $purchaseEntry = ProductPurchaseDetail::find($purchaseEntryId);
        
        if (!$purchaseEntry) {
            return Reply::error('Purchase entry not found.');
        }
        
        // Get taxes from purchase entry
        $taxIds = null;
        if ($purchaseEntry->tax && is_array($purchaseEntry->tax)) {
            $taxIds = $purchaseEntry->tax;
        } elseif ($purchaseEntry->tax && is_string($purchaseEntry->tax)) {
            $decoded = json_decode($purchaseEntry->tax, true);
            $taxIds = is_array($decoded) ? $decoded : null;
        }
        
        return Reply::dataOnly([
            'status' => 'success',
            'data' => [
                'batch' => $purchaseEntry->batch ?? '',
                'expiry' => $purchaseEntry->expiry ? $purchaseEntry->expiry->format('Y-m-d') : '',
                'mrp' => $purchaseEntry->mrp ?? '',
                'pts' => $purchaseEntry->pts ?? '',
                'ptr' => $purchaseEntry->ptr ?? '',
                'dis' => $purchaseEntry->dis ?? $purchaseEntry->discount ?? '',
                'tax' => $taxIds,
            ]
        ]);
    }

    public function appliedCredits(Request $request, $id)
    {
        $this->invoice = Invoice::with('payment', 'payment.creditNote')->findOrFail($id);
        $this->pageTitle = __('app.menu.payments');

        $this->payments = $this->invoice->payment->filter(function ($payment) {
            return $payment->status === 'complete';
        });

        if (request()->ajax()) {
            $html = view('invoices.ajax.applied_credits', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'invoices.ajax.applied_credits';

        return view('invoices.create', $this->data);
    }

    public function deleteAppliedCredit(Request $request, $id)
    {

        $this->invoice = Invoice::with('payment', 'payment.creditNote')->findOrFail($request->invoice_id);

        $payment = Payment::with('creditNote', 'invoice')->findOrFail($id);
        $payment->delete();

        $creditNote = CreditNotes::find($payment->credit_notes_id);

        // Change credit note status
        if (isset($creditNote) && $creditNote->status == 'closed') {
            $creditNote->status = 'open';
            $creditNote->save();
        }


        $this->payments = $this->invoice->payment;

        if (request()->ajax()) {
            $view = view('invoices.ajax.applied_credits', $this->data)->render();

            return Reply::successWithData(__('messages.deleteSuccess'), ['view' => $view, 'remainingAmount' => number_format((float)$this->invoice->amountDue(), 2, '.', '')]);
        }

        return Reply::redirect(route('invoices.show', [$this->invoice->id]), __('messages.deleteSuccess'));
    }

    public function paymentDetail($invoiceID)
    {
        $this->invoice = Invoice::findOrFail($invoiceID);
        $this->pageTitle = __('app.menu.payments');

        if (request()->ajax()) {
            $html = view('invoices.ajax.payment-details', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'invoices.ajax.payment-details';

        return view('invoices.create', $this->data);
    }

    public function fileUpload()
    {
        $this->invoiceId = request('invoice_id');

        return view('invoices.file_upload', $this->data);
    }

    public function storeFile(InvoiceFileStore $request)
    {
        $invoiceId = $request->invoice_id;
        $file = $request->file('file');

        $newName = $file->hashName(); // Setting hashName name
        // Getting invoice data
        $invoice = Invoice::findOrFail($invoiceId);

        if ($invoice != null) {

            if ($invoice->file != null) {
                unlink(storage_path('app/public/invoice-files') . '/' . $invoice->file);
            }

            $file->move(storage_path('app/public/invoice-files'), $newName);

            $invoice->file = $newName;
            $invoice->file_original_name = $file->getClientOriginalName(); // Getting uploading file name;

            $invoice->save();

            return Reply::success('messages.fileUploadedSuccessfully');
        }

        return Reply::error(__('messages.fileUploadIssue'));
    }

    public function stripeModal(Request $request)
    {
        $this->invoiceID = $request->invoice_id;
        $this->countries = countries();

        return view('invoices.stripe.index', $this->data);
    }

    public function saveStripeDetail(StoreStripeDetail $request)
    {
        $id = $request->invoice_id;
        $this->invoice = Invoice::with(['client', 'project', 'project.client'])->findOrFail($id);
        $this->settings = $this->company;
        $this->credentials = PaymentGatewayCredentials::first();

        $client = null;

        if (!is_null($this->invoice->client_id)) {
            $client = $this->invoice->client;
        } else if (!is_null($this->invoice->project_id) && !is_null($this->invoice->project->client_id)) {
            $client = $this->invoice->project->client;
        }

        if (($this->credentials->test_stripe_secret || $this->credentials->live_stripe_secret) && !is_null($client)) {
            Stripe::setApiKey($this->credentials->stripe_mode == 'test' ? $this->credentials->test_stripe_secret : $this->credentials->live_stripe_secret);

            $totalAmount = $this->invoice->amountDue();

            $customer = \Stripe\Customer::create([
                'email' => $client->email,
                'name' => $request->clientName,
                'address' => [
                    'line1' => $request->clientName,
                    'city' => $request->city,
                    'state' => $request->state,
                    'country' => $request->country,
                ],
            ]);

            $intent = \Stripe\PaymentIntent::create([
                'amount' => $totalAmount * 100,
                'currency' => $this->invoice->currency->currency_code,
                'customer' => $customer->id,
                'setup_future_usage' => 'off_session',
                'payment_method_types' => ['card'],
                'description' => $this->invoice->invoice_number . ' Payment',
                'metadata' => ['integration_check' => 'accept_a_payment', 'invoice_id' => $id]
            ]);

            $this->intent = $intent;
        }

        $customerDetail = [
            'email' => $client->email,
            'name' => $request->clientName,
            'line1' => $request->clientName,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
        ];

        $this->customerDetail = $customerDetail;

        $view = view('invoices.stripe.stripe-payment', $this->data)->render();

        return Reply::dataOnly(['view' => $view, 'intent' => $this->intent]);
    }

    public function offlinePaymentModal(Request $request)
    {
        $this->invoiceID = $request->invoice_id;
        $this->methods = OfflinePaymentMethod::activeMethod();
        $this->invoice = Invoice::findOrFail($this->invoiceID);

        return view('invoices.offline.index', $this->data);
    }

    public function storeOfflinePayment(InvoicePayment $request)
    {
        $returnUrl = '';

        if (isset($request->invoiceID)) {
            $invoiceId = $request->invoiceID;
            $invoice = Invoice::findOrFail($request->invoiceID);
            $returnUrl = route('invoices.show', $invoiceId);
        }

        if (isset($request->orderID)) {
            $order = Order::findOrFail($request->orderID);
            $returnUrl = route('orders.show', $request->orderID);
        }

        $clientPayment = new Payment();
        $clientPayment->currency_id = isset($invoice) ? $invoice->currency_id : $order->currency_id;
        $clientPayment->invoice_id = isset($invoice) ? $invoice->id : null;
        $clientPayment->project_id = isset($invoice) ? $invoice->project_id : null;
        $clientPayment->order_id = $request->orderID;
        $clientPayment->amount = isset($invoice) ? $invoice->total : $order->total;
        $clientPayment->offline_method_id = ($request->offlineMethod != 'all') ? $request->offlineMethod : null;
        $clientPayment->gateway = 'Offline';
        $clientPayment->status = 'pending';
        $clientPayment->paid_on = now();

        if ($request->hasFile('bill')) {
            $clientPayment->bill = $request->bill->hashName();
            $request->bill->store(Payment::FILE_PATH);
        }

        $clientPayment->save();

        if (isset($invoice)) {
            $invoice->status = 'pending-confirmation';
            $invoice->save();
        }

        return Reply::successWithData(__('messages.requestSent'), ['redirectUrl' => $returnUrl]);
    }

    public function makeInvoice($orderId)
    {
        /* Step1 -  Set order status paid */
        $order = Order::findOrFail($orderId);

        /* Step2 - Make an invoice related to recently paid order_id */
        $invoice = new Invoice();
        $invoice->order_id = $orderId;
        $invoice->client_id = $order->client_id;
        $invoice->sub_total = $order->sub_total;
        $invoice->total = $order->total;
        $invoice->currency_id = $order->currency_id;
        $invoice->status = 'paid';
        $invoice->note = trim_editor($order->note);
        $invoice->issue_date = now();
        $invoice->send_status = 1;
        $invoice->invoice_number = Invoice::lastInvoiceNumber() + 1;
        $invoice->due_amount = 0;
        $invoice->save();

        /* Step3 - Make invoice item & image entry */
        if (isset($order->items)) {
            foreach ($order->items as $item) /* @phpstan-ignore-line */ {
                // Save invoice item
                $invoiceItem = new InvoiceItems();
                $invoiceItem->invoice_id = $invoice->id;
                $invoiceItem->item_name = $item->item_name;
                $invoiceItem->item_summary = $item->item_summary;
                $invoiceItem->type = $item->type;
                $invoiceItem->quantity = $item->quantity;
                $invoiceItem->unit_price = $item->unit_price;
                $invoiceItem->amount = $item->amount;
                $invoiceItem->hsn_sac_code = $item->hsn_sac_code;
                $invoiceItem->taxes = $item->taxes;
                $invoiceItem->save();

                // Save invoice item image
                if ($item->orderItemImage) {
                    $invoiceItemImage = new InvoiceItemImage();
                    $invoiceItemImage->invoice_item_id = $invoiceItem->id;
                    $invoiceItemImage->external_link = $item->orderItemImage->external_link;
                    $invoiceItemImage->save();
                }
            }
        }

        return $invoice;
    }

    public function cancelStatus(Request $request)
    {
        $invoice = Invoice::findOrFail($request->invoiceID);
        $invoice->status = 'canceled'; // update status as canceled
        $invoice->save();

        if (quickbooks_setting()->status && quickbooks_setting()->access_token != '') {
            $quickBooks = new QuickbookController();
            $quickBooks->voidInvoice($invoice);
        }

        optional($invoice->payment->first())->delete();
        return Reply::success(__('messages.updateSuccess'));
    }

    public function getClientOrCompanyName($projectID = '')
    {
        $this->projectID = $projectID;
        $this->currencies = Currency::all();

        if ($projectID == '') {
            // Filter clients by region if logged-in user is a CFA/Distributor (client)
            $currentUser = user();
            $isClient = in_array('client', user_roles());
            if ($isClient && $currentUser && $currentUser->clientDetails && isset($currentUser->clientDetails->region_id) && $currentUser->clientDetails->region_id) {
                // Get all clients from the same region
                $regionId = $currentUser->clientDetails->region_id;
                $allClients = User::allClients();
                // Load clientDetails with region_id for filtering
                $allClients->load('clientDetails');
                $this->clients = $allClients->filter(function($client) use ($regionId) {
                    return $client->clientDetails && 
                           isset($client->clientDetails->region_id) &&
                           $client->clientDetails->region_id == $regionId;
                });
            } else {
                $this->clients = User::allClients();
            }
            $exchangeRate = company()->currency->exchange_rate;
            $currencyName = company()->currency->currency_code;
        } else {
            $this->client = Project::with('currency')->where('id', $projectID)->with('client')->first();
            $this->companyName = '';
            $this->clientId = '';

            if ($this->client) {
                $this->companyName = $this->client->client->name;
                $this->clientId = $this->client->client->id;
            }

            $exchangeRate = Currency::where('id', $this->client->currency_id)->pluck('exchange_rate')->toArray();
            $currencyName = $this->client->currency->currency_code;
        }

        $currency = view('invoices.currency_list', $this->data)->render();
        $list = view('invoices.client_or_company_name', $this->data)->render();

        return Reply::dataOnly(['html' => $list, 'currency' => $currency, 'exchangeRate' => $exchangeRate, 'currencyName' => $currencyName]);
    }

    public function fetchTimelogs(Request $request)
    {
        $this->taxes = Tax::all();
        $this->invoiceSetting = invoice_setting();
        $projectId = $request->projectId;
        $this->qtyVal = $request->qtyValue;
        $this->timelogs = [];
        $this->units = UnitType::all();

        if (!is_null($request->timelogFrom) && $request->timelogFrom != '') {
            $timelogFrom = companyToYmd($request->timelogFrom);
            $timelogTo = companyToYmd($request->timelogTo);
            $this->timelogs = ProjectTimeLog::with('task')
                ->leftJoin('tasks', 'tasks.id', '=', 'project_time_logs.task_id')
                ->groupBy('project_time_logs.task_id')
                ->where('project_time_logs.project_id', $projectId)
                ->where('project_time_logs.earnings', '>', 0)
                ->where('project_time_logs.approved', 1)
                ->where(
                    function ($query) {
                        $query->where('tasks.billable', 1)
                            ->orWhereNull('tasks.billable');
                    }
                )
                ->whereDate('project_time_logs.start_time', '>=', $timelogFrom)
                ->whereDate('project_time_logs.end_time', '<=', $timelogTo)
                ->selectRaw('project_time_logs.id, project_time_logs.task_id, sum(project_time_logs.earnings) as sum')
                ->get();
        }

        $html = view('invoices.timelog-item', $this->data)->render();

        return Reply::dataOnly(['html' => $html]);
    }

    public function checkShippingAddress()
    {
        if (request()->has('clientId')) {
            $user = User::findOrFail(request()->clientId);

            if (request()->showShipping == 'yes' && (is_null($user->clientDetails->shipping_address) || $user->clientDetails->shipping_address === '')) {
                $view = view('invoices.show_shipping_address_input')->render();

                return Reply::dataOnly(['view' => $view]);
            } else {
                return Reply::dataOnly(['show' => 'false']);
            }
        } else {
            return Reply::dataOnly(['switch' => 'off']);
        }
    }

    public function toggleShippingAddress(Invoice $invoice)
    {
        $invoice->show_shipping_address = ($invoice->show_shipping_address === 'yes') ? 'no' : 'yes';
        $invoice->save();

        return Reply::success(__('messages.updateSuccess'));
    }

    public function shippingAddressModal(Invoice $invoice)
    {
        $clientId = $invoice->clientdetails ? $invoice->clientdetails->user_id : $invoice->project->clientdetails->user_id;

        return view('invoices.add_shipping_address', ['clientId' => $clientId]);
    }

    public function addShippingAddress(StoreShippingAddressRequest $request, $clientId)
    {
        $clientDetail = ClientDetails::where('user_id', $clientId)->first();
        $clientDetail->shipping_address = $request->shipping_address;
        $clientDetail->save();

        return Reply::success(__('messages.recordSaved'));
    }

    public function deleteInvoiceItemImage(Request $request)
    {
        $item = InvoiceItemImage::where('invoice_item_id', $request->invoice_item_id)->first();

        if ($item) {
            Files::deleteFile($item->hashname, InvoiceItemImage::FILE_PATH . '/' . $item->id . '/');
            $item->delete();
        }

        return Reply::success(__('messages.deleteSuccess'));
    }

    public function getExchangeRate($id)
    {
        $exchangeRate = Currency::where('id', $id)->pluck('exchange_rate')->toArray();
        return Reply::dataOnly(['status' => 'success', 'data' => $exchangeRate]);
    }

    public function getclients($id)
    {
        $unitId = UnitType::where('id', $id)->first();
        return Reply::dataOnly(['status' => 'success', 'type' => $unitId]);
    }

    public function productCategory(Request $request, $id = null)
    {
        // If purchase module is enabled, return purchase entries instead of products
        if (module_enabled('Purchase') && in_array('purchase', user_modules())) {
            $purchaseEntries = ProductPurchaseDetail::with(['product.category', 'product.vendor', 'product.unit'])
                ->whereHas('product'); // Only entries with valid products

            if (!is_null($request->id) && $request->id != 'null' && $request->id != '') {
                $purchaseEntries = $purchaseEntries->whereHas('product', function($query) use ($request) {
                    $query->where('category_id', $request->id);
                });
            }

            $purchaseEntries = $purchaseEntries->orderBy('created_at', 'desc')->get();
            
            // Format data for dropdown (matching product structure)
            $formattedData = $purchaseEntries->filter(function($entry) {
                return $entry->product !== null; // Filter out any entries without products
            })->map(function($entry) {
                return [
                    'id' => $entry->id,
                    'name' => $entry->product->name . ($entry->batch ? ' - Batch: ' . $entry->batch : '')
                ];
            });

            return Reply::dataOnly(['status' => 'success', 'data' => $formattedData]);
        } else {
            $categorisedProduct = Product::with('category');

            if (!is_null($request->id) && $request->id != 'null' && $request->id != '') {
                $categorisedProduct = $categorisedProduct->where('category_id', $request->id);
            }

            $categorisedProduct = $categorisedProduct->get();

            return Reply::dataOnly(['status' => 'success', 'data' => $categorisedProduct]);
        }
    }

    public function offlineDescription(Request $request)
    {
        $id = $request->id;

        $offlineMethod = $id ? OfflinePaymentMethod::findOrFail($id) : '';
        $description = $offlineMethod ? '<span class="float-left">' . $offlineMethod->description . '</span>' : '';

        if ($offlineMethod && $offlineMethod->image) {
            $description .= '<span class="float-right"><img src="' . $offlineMethod->image_url . '" width="100px" height="100px"/></span>';
        }
        return Reply::dataOnly(['status' => 'success', 'description' => $description]);
    }

    /**
     * Show the form for creating a new CFA/Distributor invoice
     */
    public function indexCFADistributorInvoices(CFADistributorInvoicesDataTable $dataTable)
    {
        // Admin, accountant, and FSA Executive users have full access
        // Also allow clients (CFA Distributors) to view their own invoices
        if (!PharmaDesignationHelper::hasFullCFAAccess() && !in_array('client', user_roles())) {
            $viewPermission = user()->permission('view_cfa_distributor_invoices');
            abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
        }

        if (!request()->ajax()) {
            // Load CFA/Distributor clients for filter
            $cfaDistributorQuery = User::without('session')
                ->join('role_user', 'role_user.user_id', '=', 'users.id')
                ->join('roles', 'roles.id', '=', 'role_user.role_id')
                ->join('client_details', 'users.id', '=', 'client_details.user_id')
                ->leftJoin('client_categories', 'client_details.category_id', '=', 'client_categories.id')
                ->leftJoin('client_areas', 'client_details.id', '=', 'client_areas.client_detail_id')
                ->select('users.id', 'users.name', 'users.email', 'users.created_at', 'client_details.company_name', 'users.image', 'users.email_notifications', 'users.mobile', 'users.country_id', 'users.salutation', 'users.status', 'users.is_client_contact')
                ->whereNull('users.is_client_contact')
                ->where('roles.name', 'client')
                ->where('users.status', 'active')
                ->where('users.company_id', company()->id)
                ->where(function($query) {
                    $query->where(function($q) {
                        $q->whereRaw('LOWER(client_categories.category_name) LIKE ?', ['%cfa%'])
                          ->orWhereRaw('LOWER(client_categories.category_name) LIKE ?', ['%distributor%']);
                    })
                    ->orWhereNotNull('client_areas.area_id');
                })
                ->groupBy('users.id', 'users.name', 'users.email', 'users.created_at', 'client_details.company_name', 'users.image', 'users.email_notifications', 'users.mobile', 'users.country_id', 'users.salutation', 'users.status', 'users.is_client_contact')
                ->orderBy('users.name', 'asc');
            
            $this->clients = $cfaDistributorQuery->get();
        }

        return $dataTable->render('invoices.cfa-distributor.index', $this->data);
    }

    public function createCFADistributorInvoice()
    {
        // Admin, accountant, and FSA Executive users have full access
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            $this->addPermission = 'all';
        } else {
            $this->addPermission = user()->permission('add_cfa_distributor_invoices');
            abort_403(!in_array($this->addPermission, ['all', 'added']));
        }

        $this->pageTitle = __('CFA/Distributor Invoice');

        // Load all active clients for CFA/Distributor invoice creation
        // Show all clients so user can select any distributor (not just those with specific category/area)
        $this->cfaDistributors = User::without('session')
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->join('client_details', 'users.id', '=', 'client_details.user_id')
            ->whereNull('users.is_client_contact')
            ->where('roles.name', 'client')
            ->where('users.status', 'active')
            ->where('users.company_id', company()->id)
            ->select('users.id', 'users.name', 'users.email', 'users.created_at', 'client_details.company_name', 'users.image', 'users.email_notifications', 'users.mobile', 'users.country_id', 'users.salutation', 'users.status', 'users.is_client_contact')
            ->distinct()
            ->get()
            ->sortBy(function($user) {
                return $user->company_name ?? $user->name;
            })
            ->values();

        // Load products from purchase entries only
        if (module_enabled('Purchase')) {
            $purchaseEntries = ProductPurchaseDetail::with(['product.vendor', 'product.unit'])
                ->whereHas('product', function($query) {
                    $query->where('company_id', company()->id);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            // Group by product_id
            $productsGrouped = [];
            foreach ($purchaseEntries as $entry) {
                $productId = $entry->product_id;
                if (!isset($productsGrouped[$productId])) {
                    $productsGrouped[$productId] = [
                        'product' => $entry->product,
                        'entries' => []
                    ];
                }
                $productsGrouped[$productId]['entries'][] = $entry;
            }
            $this->products = collect($productsGrouped);
        } else {
            $this->products = collect([]);
        }

        $this->currencies = Currency::all();
        $this->taxes = Tax::all();
        $this->invoiceSetting = invoice_setting();
        $this->projects = Project::allProjects();
        $this->units = UnitType::all();
        $this->bankAccounts = BankAccount::all();
        
        // Add missing variables for the view
        $this->lastInvoice = Invoice::lastInvoiceNumber() + 1;
        $this->companyCurrency = Currency::where('id', company()->currency_id)->first();
        
        // Calculate zero padding for invoice number
        $this->zero = '';
        if (strlen($this->lastInvoice) < $this->invoiceSetting->invoice_digit) {
            $condition = $this->invoiceSetting->invoice_digit - strlen($this->lastInvoice);
            for ($i = 0; $i < $condition; $i++) {
                $this->zero = '0' . $this->zero;
            }
        }
        
        // Check if this is an IGST invoice creation
        $isIGSTInvoice = request('type') == 'igst' || request()->has('igst');
        $this->invoiceType = $isIGSTInvoice ? 'igst' : '';
        $this->data['invoiceType'] = $this->invoiceType;
        $this->data['isIGSTInvoice'] = $isIGSTInvoice;

        return view('invoices.cfa-distributor.create', $this->data);
    }

    /**
     * Get CFA/Distributor clients via AJAX
     */
    public function getCFADistributors(Request $request)
    {
        // Get all active clients (same as createCFADistributorInvoice)
        $cfaDistributors = User::without('session')
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->join('client_details', 'users.id', '=', 'client_details.user_id')
            ->whereNull('users.is_client_contact')
            ->where('roles.name', 'client')
            ->where('users.status', 'active')
            ->where('users.company_id', company()->id)
            ->select('users.id', 'users.name', 'users.email', 'client_details.company_name')
            ->distinct()
            ->get()
            ->sortBy(function($user) {
                return $user->company_name ?? $user->name;
            })
            ->values();

        $options = '<option value="">Select CFA/Distributor</option>';
        foreach ($cfaDistributors as $distributor) {
            $displayName = $distributor->company_name ?? $distributor->name;
            $options .= '<option value="' . $distributor->id . '">' . htmlspecialchars($displayName) . '</option>';
        }

        return Reply::dataOnly(['status' => 'success', 'data' => $options]);
    }

    /**
     * Store a newly created CFA/Distributor invoice
     */
    public function storeCFADistributorInvoice(StoreInvoice $request)
    {
        try {
            $userId = UserService::getUserId();
            
            // Debug: Log request data for stock reduction debugging
            \Log::info('CFA Distributor Invoice Creation - Request Data:', [
                'product_ids' => $request->product_id ?? [],
                'purchase_entry_ids' => $request->purchase_entry_id ?? [],
                'quantities' => $request->quantity ?? [],
                'item_names' => $request->item_name ?? [],
                'client_id' => $request->client_id
            ]);
            
            // Validate items
            $items = $request->item_name;
            if (empty($items)) {
                return Reply::error(__('messages.addItem'));
            }

            foreach ($items as $itm) {
                if (is_null($itm)) {
                    return Reply::error(__('messages.itemBlank'));
                }
            }

            foreach ($request->quantity as $qty) {
                if (!is_numeric($qty) && (intval($qty) < 1)) {
                    return Reply::error(__('messages.quantityNumber'));
                }
            }
            
            // Validate cost_per_item and amount arrays
            if (empty($request->cost_per_item) || empty($request->amount)) {
                return Reply::error(__('messages.addItem'));
            }
            
            foreach ($request->cost_per_item as $rate) {
                if (!is_numeric($rate)) {
                    return Reply::error(__('messages.unitPriceNumber'));
                }
            }
            
            foreach ($request->amount as $amt) {
                if (!is_numeric($amt)) {
                    return Reply::error(__('messages.amountNumber'));
                }
            }

            $invoice = new Invoice();
            $invoice->company_id = company()->id;
            $invoice->added_by = $userId;
            $invoice->client_id = $request->client_id;
            $invoice->issue_date = companyToYmd($request->issue_date);
            $invoice->due_date = companyToYmd($request->due_date);
            $invoice->sub_total = round($request->sub_total, 2);
            $invoice->discount = round($request->discount, 2);
            $invoice->discount_type = $request->discount_type;
            $invoice->total = round($request->total, 2);
            $invoice->due_amount = round($request->total, 2);
            $invoice->currency_id = $request->currency_id;
            $invoice->default_currency_id = company()->currency_id;
            $invoice->exchange_rate = $request->exchange_rate;
            $invoice->status = $request->status;
            $invoice->note = trim_editor($request->note);
            $invoice->invoice_number = $request->invoice_number;
            $invoice->company_address_id = $request->company_address_id;
            $invoice->bank_account_id = $request->bank_account_id;
            $invoice->payment_status = $request->payment_status == null ? '0' : $request->payment_status;
            $invoice->invoice_payment_id = $request->invoice_payment_id;
            $invoice->calculate_tax = $request->calculate_tax;
            $invoice->lr_number = $request->lr_number ?? null;
            $invoice->lr_date = $request->lr_date ? companyToYmd($request->lr_date) : null;
            $invoice->delivery_status = $request->delivery_status ?? 'in_transit';
            $invoice->company_address_id = $request->filled('company_address_id')
                ? $request->company_address_id
                : $this->defaultCompanyAddressId();
            $this->applyCfaPharmaTaxInvoiceMeta($invoice, $request);
            $invoice->recurring = 'no';
            // Store IGST invoice type if applicable
            // Check both request parameter and hidden form field
            $isIGSTInvoice = ($request->has('type') && $request->type == 'igst') || 
                             ($request->has('invoice_type') && $request->invoice_type == 'igst');
            if ($isIGSTInvoice) {
                $invoice->invoice_type = 'igst';
            } else {
                $invoice->invoice_type = 'sgst_cgst'; // Default to SGST/CGST
            }
            $invoice->save();

            // Add custom fields data
            if ($request->custom_fields_data) {
                $invoice->updateCustomFieldData($request->custom_fields_data);
            }

            // Store invoice items
            $savedItemsCount = 0;
            foreach ($request->item_name as $key => $item) {
                // Skip if item name is null or empty
                if (is_null($item) || trim($item) === '') {
                    \Log::warning('Skipping empty item at index ' . $key . ' for invoice ' . $invoice->id);
                    continue;
                }
                
                // Get SKU value (from sku[] field) and use it for hsn_sac_code if hsn_sac_code is empty
                $skuValue = $request->sku[$key] ?? null;
                $hsnSacCode = $request->hsn_sac_code[$key] ?? $skuValue ?? null;
                
                $invoiceItem = InvoiceItems::create([
                    'invoice_id' => $invoice->id,
                    'item_name' => $item,
                    'item_summary' => $request->item_summary[$key] ?? null,
                    'type' => 'item',
                    'quantity' => $request->quantity[$key] ?? 0,
                    'unit_price' => round($request->cost_per_item[$key] ?? 0, 2),
                    'amount' => round($request->amount[$key] ?? 0, 2),
                    'taxes' => isset($request->taxes[$key]) && !empty($request->taxes[$key]) ? json_encode($request->taxes[$key]) : null,
                    'hsn_sac_code' => $hsnSacCode,
                    'product_id' => $request->product_id[$key] ?? null,
                    'unit_id' => $request->unit_id[$key] ?? null,
                    'purchase_entry_id' => $request->purchase_entry_id[$key] ?? null,
                    'scheme' => $request->scheme[$key] ?? null,
                    'pack' => $request->pack[$key] ?? null,
                    'mfr' => $request->mfr[$key] ?? null,
                    'batch' => $request->batch[$key] ?? null,
                    'exp' => $request->exp[$key] ? date('Y-m-d', strtotime($request->exp[$key])) : null,
                    'mrp' => isset($request->mrp[$key]) ? round($request->mrp[$key] ?? 0, 2) : null,
                    'pts' => isset($request->pts[$key]) ? round($request->pts[$key] ?? 0, 2) : null,
                    'ptr' => isset($request->ptr[$key]) ? round($request->ptr[$key] ?? 0, 2) : null,
                    'dis' => isset($request->dis[$key]) ? round($request->dis[$key] ?? 0, 2) : null,
                    'field_order' => $savedItemsCount + 1,
                ]);
                
                $savedItemsCount++;
                \Log::info('Saved invoice item #' . $savedItemsCount . ' for invoice ' . $invoice->id, [
                    'item_id' => $invoiceItem->id,
                    'item_name' => $item,
                    'product_id' => $request->product_id[$key] ?? null,
                    'purchase_entry_id' => $request->purchase_entry_id[$key] ?? null,
                    'field_order' => $savedItemsCount
                ]);

                // Create CFA/Distributor stock entry and reduce main product stock
                $invoiceQuantity = $request->quantity[$key] ?? 0;
                $productId = $request->product_id[$key] ?? null;
                $purchaseEntryId = $request->purchase_entry_id[$key] ?? null;
                
                // Get product_id - prefer from purchase_entry, fallback to direct product_id
                $finalProductId = null;
                $purchaseEntry = null;
                
                if (!empty($purchaseEntryId)) {
                    $purchaseEntry = ProductPurchaseDetail::find($purchaseEntryId);
                    if ($purchaseEntry) {
                        $finalProductId = $purchaseEntry->product_id;
                    }
                }
                
                // Fallback: use product_id directly if purchase_entry_id is not available
                if (empty($finalProductId) && !empty($productId)) {
                    $finalProductId = $productId;
                }
                
                // Only proceed if we have a valid product_id and quantity
                if (!empty($finalProductId) && $invoiceQuantity > 0) {
                    // Create CFA Distributor Stock entry if purchase_entry exists
                    if ($purchaseEntry) {
                        CFADistributorStock::create([
                            'company_id' => company()->id,
                            'cfa_distributor_id' => $request->client_id,
                            'product_id' => $finalProductId,
                            'purchase_entry_id' => $purchaseEntry->id,
                            'invoice_id' => $invoice->id,
                            'batch' => $request->batch[$key] ?? $purchaseEntry->batch,
                            'expiry' => $request->exp[$key] ?? $purchaseEntry->expiry,
                            'quantity' => $invoiceQuantity,
                            'available_quantity' => $invoiceQuantity, // Initially all quantity is available
                            'pts' => $request->pts[$key] ?? $purchaseEntry->pts ?? 0,
                            'ptr' => $request->ptr[$key] ?? $purchaseEntry->ptr ?? 0,
                            'mrp' => $request->mrp[$key] ?? $purchaseEntry->mrp ?? 0,
                            'dis' => $request->dis[$key] ?? $purchaseEntry->dis ?? 0,
                        ]);
                    } else {
                        // Create CFA Distributor Stock entry without purchase_entry_id
                        CFADistributorStock::create([
                            'company_id' => company()->id,
                            'cfa_distributor_id' => $request->client_id,
                            'product_id' => $finalProductId,
                            'purchase_entry_id' => null,
                            'invoice_id' => $invoice->id,
                            'batch' => $request->batch[$key] ?? null,
                            'expiry' => $request->exp[$key] ? date('Y-m-d', strtotime($request->exp[$key])) : null,
                            'quantity' => $invoiceQuantity,
                            'available_quantity' => $invoiceQuantity,
                            'pts' => $request->pts[$key] ?? 0,
                            'ptr' => $request->ptr[$key] ?? 0,
                            'mrp' => $request->mrp[$key] ?? 0,
                            'dis' => $request->dis[$key] ?? 0,
                        ]);
                    }
                    
                    // Reduce stock from PurchaseStockAdjustment when invoice is created/billed
                    // Stock should reduce immediately on billing, not on delivery toggle
                    $stockAdjustment = PurchaseStockAdjustment::firstOrCreate(
                        ['product_id' => $finalProductId, 'company_id' => company()->id],
                        ['net_quantity' => 0]
                    );
                    
                    // Get current stock before reduction
                    $stockBefore = $stockAdjustment->net_quantity;
                    
                    // Reduce stock (subtract from net_quantity)
                    $stockAdjustment->net_quantity = max(0, $stockAdjustment->net_quantity - $invoiceQuantity);
                    $stockAdjustment->save();

                    // Batch-wise: deduct from purchase_batch_stock (FEFO)
                    PurchaseBatchStock::deductFefo((int) company()->id, (int) $finalProductId, (float) $invoiceQuantity);
                    
                    \Log::info('Reduced product stock for CFA Distributor invoice', [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'product_id' => $finalProductId,
                        'product_name' => $item,
                        'quantity_reduced' => $invoiceQuantity,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAdjustment->net_quantity,
                        'invoice_type' => $invoice->invoice_type ?? 'sgst_cgst',
                        'purchase_entry_id' => $purchaseEntryId,
                        'has_purchase_entry' => !empty($purchaseEntry)
                    ]);
                } else {
                    \Log::warning('Skipping stock reduction - missing product_id or quantity', [
                        'invoice_id' => $invoice->id,
                        'item_name' => $item,
                        'product_id' => $productId,
                        'purchase_entry_id' => $purchaseEntryId,
                        'quantity' => $invoiceQuantity
                    ]);
                }
            }

            // Log activity
            if (user()) {
                self::createEmployeeActivity($userId, 'cfa-distributor-invoice-created', $invoice->id, 'invoice');
            }
            
            // Log final count for debugging
            \Log::info('Invoice #' . $invoice->id . ' saved with ' . $savedItemsCount . ' items', [
                'invoice_id' => $invoice->id,
                'total_items_saved' => $savedItemsCount,
                'total_items_in_request' => count($request->item_name ?? [])
            ]);

            return Reply::successWithData(__('messages.recordSaved'), [
                'redirectUrl' => route('cfa-distributor-invoices.show', $invoice->id),
                'invoiceID' => $invoice->id
            ]);
        } catch (\Exception $e) {
            \Log::error('Error storing CFA/Distributor invoice: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return Reply::error('Error saving invoice: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing a CFA/Distributor invoice
     */
    public function editCFADistributorInvoice($id)
    {
        // Admin, accountant, and FSA Executive users have full access
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            $this->editPermission = 'all';
        } else {
            $this->editPermission = user()->permission('edit_cfa_distributor_invoices');
            abort_403(!in_array($this->editPermission, ['all', 'added', 'owned', 'both']));
        }
        
        $this->invoice = Invoice::with([
            'client', 
            'client.clientDetails.areas', 
            'items', 
            'items.purchaseEntry' => function($query) {
                // Load all purchase entry fields including PTS, PTR, MRP, DIS, tax
                $query->select('id', 'product_id', 'vendor_id', 'batch', 'expiry', 'mrp', 'pts', 'ptr', 'dis', 'discount', 'tax', 'scheme_enabled', 'total_quantity', 'free_quantity');
            },
            'items.purchaseEntry.product' => function($query) {
                $query->select('id', 'name', 'packing', 'sku', 'hsn_sac_code', 'vendor_id', 'unit_id', 'taxes');
            },
            'items.purchaseEntry.product.unit', 
            'items.purchaseEntry.product.vendor', 
            'items.purchaseEntry.vendor', 
            'items.unit', 
            'cfaDistributorStocks'
        ])->findOrFail($id)->withCustomFields();
        
        // If items don't have purchase_entry_id, try to get it from CFADistributorStock
        foreach ($this->invoice->items as $item) {
            if (!$item->purchase_entry_id && $item->product_id) {
                $stockEntry = $this->invoice->cfaDistributorStocks->where('product_id', $item->product_id)->first();
                if ($stockEntry && $stockEntry->purchase_entry_id) {
                    $item->purchase_entry_id = $stockEntry->purchase_entry_id;
                }
            }
        }
        $this->editPermission = user()->permission('edit_invoices');
        $this->invoiceSetting = invoice_setting();
        $this->userId = UserService::getUserId();

        abort_403(!(
            $this->editPermission == 'all'
            || ($this->editPermission == 'added' && ($this->invoice->added_by == $this->userId || $this->invoice->added_by == user()->id))
            || ($this->editPermission == 'owned' && $this->invoice->client_id == $this->userId)
            || ($this->editPermission == 'both' && ($this->invoice->client_id == $this->userId || $this->invoice->added_by == $this->userId || $this->invoice->added_by == user()->id))
        ));

        $this->pageTitle = __('Edit CFA/Distributor Invoice') . ' - ' . $this->invoice->invoice_number;

        // Load CFA/Distributor clients
        $cfaDistributorQuery = User::without('session')
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->join('client_details', 'users.id', '=', 'client_details.user_id')
            ->leftJoin('client_categories', 'client_details.category_id', '=', 'client_categories.id')
            ->leftJoin('client_areas', 'client_details.id', '=', 'client_areas.client_detail_id')
            ->select('users.id', 'users.name', 'users.email', 'users.created_at', 'client_details.company_name', 'users.image', 'users.email_notifications', 'users.mobile', 'users.country_id', 'users.salutation', 'users.status', 'users.is_client_contact')
            ->whereNull('users.is_client_contact')
            ->where('roles.name', 'client')
            ->where('users.status', 'active')
            ->where('users.company_id', company()->id)
            ->where(function($query) {
                $query->where(function($q) {
                    $q->whereRaw('LOWER(client_categories.category_name) LIKE ?', ['%cfa%'])
                      ->orWhereRaw('LOWER(client_categories.category_name) LIKE ?', ['%distributor%']);
                })
                ->orWhereNotNull('client_areas.area_id');
            })
            ->groupBy('users.id', 'users.name', 'users.email', 'users.created_at', 'client_details.company_name', 'users.image', 'users.email_notifications', 'users.mobile', 'users.country_id', 'users.salutation', 'users.status', 'users.is_client_contact')
            ->orderBy('client_details.company_name', 'asc')
            ->orderBy('users.name', 'asc');
        
        $this->cfaDistributors = $cfaDistributorQuery->get();

        // Load products from purchase entries
        if (module_enabled('Purchase')) {
            $purchaseEntries = ProductPurchaseDetail::with(['product.vendor', 'product.unit'])
                ->whereHas('product', function($query) {
                    $query->where('company_id', company()->id);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $productsGrouped = [];
            foreach ($purchaseEntries as $entry) {
                $productId = $entry->product_id;
                if (!isset($productsGrouped[$productId])) {
                    $productsGrouped[$productId] = [
                        'product' => $entry->product,
                        'entries' => []
                    ];
                }
                $productsGrouped[$productId]['entries'][] = $entry;
            }
            $this->products = collect($productsGrouped);
        } else {
            $this->products = collect([]);
        }

        $this->currencies = Currency::all();
        $this->taxes = Tax::all();
        $this->units = UnitType::all();
        $this->bankAccounts = BankAccount::all();
        $this->companyCurrency = Currency::where('id', company()->currency_id)->first();
        $this->companyAddresses = CompanyAddress::all();
        
        // Extract numeric part from invoice number for display
        $this->zero = '';
        $invoiceNumber = $this->invoice->invoice_number;
        // Remove prefix and separator to get numeric part
        $prefix = $this->invoiceSetting->invoice_prefix . $this->invoiceSetting->invoice_number_separator;
        $numericPart = str_replace($prefix, '', $invoiceNumber);
        // Calculate zero padding if needed (though we're displaying full formatted number)
        if (is_numeric($numericPart) && strlen($numericPart) < $this->invoiceSetting->invoice_digit) {
            $condition = $this->invoiceSetting->invoice_digit - strlen($numericPart);
            for ($i = 0; $i < $condition; $i++) {
                $this->zero = '0' . $this->zero;
            }
        }

        $getCustomFieldGroupsWithFields = $this->invoice->getCustomFieldGroupsWithFields();
        if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }

        // Check if this is an IGST invoice
        // Priority 1: Check database field (most reliable)
        $isIGSTInvoice = false;
        if (isset($this->invoice->invoice_type)) {
            $isIGSTInvoice = $this->invoice->invoice_type === 'igst';
        }
        
        // Priority 2: Check request parameter
        if (!$isIGSTInvoice && (request('type') == 'igst' || request()->has('igst'))) {
            $isIGSTInvoice = true;
        }
        
        // Priority 3: Check stored invoice type marker in note field (for backward compatibility)
        if (!$isIGSTInvoice && $this->invoice->note && strpos($this->invoice->note, '<!--IGST_INVOICE-->') !== false) {
            $isIGSTInvoice = true;
            // Update database field
            $this->invoice->invoice_type = 'igst';
            $this->invoice->save();
        }
        
        $this->invoiceType = $isIGSTInvoice ? 'igst' : '';
        $this->data['invoiceType'] = $this->invoiceType;
        $this->data['isIGSTInvoice'] = $isIGSTInvoice;

        return view('invoices.cfa-distributor.edit', $this->data);
    }

    /**
     * Helper function to safely get array value
     */
    private function safeArrayGet($array, $key, $default = null)
    {
        if (!isset($array) || !is_array($array) || !isset($array[$key])) {
            return $default;
        }
        return $array[$key];
    }

    /**
     * Update a CFA/Distributor invoice
     */
    public function updateCFADistributorInvoice(UpdateInvoice $request, $id)
    {
        // Admin, accountant, and FSA Executive users have full access
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            $this->editPermission = 'all';
        } else {
            $this->editPermission = user()->permission('edit_cfa_distributor_invoices');
            abort_403(!in_array($this->editPermission, ['all', 'added', 'owned', 'both']));
        }
        
        try {
            $userId = UserService::getUserId();
            $invoice = Invoice::findOrFail($id);
            
            // Validate items - safely check if arrays exist and are arrays
            $items = (isset($request->item_name) && is_array($request->item_name)) ? $request->item_name : [];
            $cost_per_item = (isset($request->cost_per_item) && is_array($request->cost_per_item)) ? $request->cost_per_item : [];
            $quantity = (isset($request->quantity) && is_array($request->quantity)) ? $request->quantity : [];
            $amount = (isset($request->amount) && is_array($request->amount)) ? $request->amount : [];
            
            // Get all request arrays safely
            $requestArrays = [
                'item_summary' => (isset($request->item_summary) && is_array($request->item_summary)) ? $request->item_summary : [],
                'taxes' => (isset($request->taxes) && is_array($request->taxes)) ? $request->taxes : [],
                'hsn_sac_code' => (isset($request->hsn_sac_code) && is_array($request->hsn_sac_code)) ? $request->hsn_sac_code : [],
                'sku' => (isset($request->sku) && is_array($request->sku)) ? $request->sku : [],
                'product_id' => (isset($request->product_id) && is_array($request->product_id)) ? $request->product_id : [],
                'unit_id' => (isset($request->unit_id) && is_array($request->unit_id)) ? $request->unit_id : [],
                'purchase_entry_id' => (isset($request->purchase_entry_id) && is_array($request->purchase_entry_id)) ? $request->purchase_entry_id : [],
                'scheme' => (isset($request->scheme) && is_array($request->scheme)) ? $request->scheme : [],
                'pack' => (isset($request->pack) && is_array($request->pack)) ? $request->pack : [],
                'mfr' => (isset($request->mfr) && is_array($request->mfr)) ? $request->mfr : [],
                'batch' => (isset($request->batch) && is_array($request->batch)) ? $request->batch : [],
                'exp' => (isset($request->exp) && is_array($request->exp)) ? $request->exp : [],
                'mrp' => (isset($request->mrp) && is_array($request->mrp)) ? $request->mrp : [],
                'pts' => (isset($request->pts) && is_array($request->pts)) ? $request->pts : [],
                'ptr' => (isset($request->ptr) && is_array($request->ptr)) ? $request->ptr : [],
                'dis' => (isset($request->dis) && is_array($request->dis)) ? $request->dis : [],
            ];
            
            if (empty($items)) {
                return Reply::error(__('messages.addItem'));
            }
            
            foreach ($items as $itm) {
                if (is_null($itm)) {
                    return Reply::error(__('messages.itemBlank'));
                }
            }
            
            // Only validate if arrays are not empty
            if (!empty($quantity)) {
                foreach ($quantity as $qty) {
                    if (!is_numeric($qty) && (intval($qty) < 1)) {
                        return Reply::error(__('messages.quantityNumber'));
                    }
                }
            }
            
            if (!empty($cost_per_item)) {
                foreach ($cost_per_item as $rate) {
                    if (!is_numeric($rate)) {
                        return Reply::error(__('messages.unitPriceNumber'));
                    }
                }
            }
            
            if (!empty($amount)) {
                foreach ($amount as $amt) {
                    if (!is_numeric($amt)) {
                        return Reply::error(__('messages.amountNumber'));
                    }
                }
            }

            $invoice->client_id = $request->client_id;
            $invoice->issue_date = companyToYmd($request->issue_date);
            $invoice->due_date = companyToYmd($request->due_date);
            $invoice->sub_total = round($request->sub_total, 2);
            $invoice->total = round($request->total, 2);
            $invoice->currency_id = $request->currency_id;
            $invoice->exchange_rate = $request->exchange_rate ?? 1;
            $invoice->status = $request->status;
            // Update invoice type based on request
            $note = trim_editor($request->note);
            $hasIGSTMarker = $invoice->note && strpos($invoice->note, '<!--IGST_INVOICE-->') !== false;
            // Check both request parameter and hidden form field, or existing database field, or marker
            $isIGSTInvoice = ($request->has('type') && $request->type == 'igst') || 
                             ($request->has('invoice_type') && $request->invoice_type == 'igst') ||
                             (isset($invoice->invoice_type) && $invoice->invoice_type === 'igst') ||
                             $hasIGSTMarker;
            if ($isIGSTInvoice) {
                $invoice->invoice_type = 'igst';
            } else {
                $invoice->invoice_type = 'sgst_cgst'; // Default to SGST/CGST
            }
            $invoice->note = $note;
            $invoice->invoice_number = $request->invoice_number;
            $invoice->company_address_id = $request->filled('company_address_id')
                ? $request->company_address_id
                : ($invoice->company_address_id ?? $this->defaultCompanyAddressId());
            $invoice->bank_account_id = $request->bank_account_id;
            $invoice->payment_status = $request->payment_status == null ? '0' : $request->payment_status;
            $invoice->invoice_payment_id = $request->invoice_payment_id;
            $invoice->calculate_tax = $request->calculate_tax;
            $invoice->discount = round($request->discount ?? 0, 2);
            $invoice->discount_type = $request->discount_type ?? 'percent';
            $invoice->due_amount = round($request->total, 2);
            $invoice->lr_number = $request->lr_number ?? null;
            $invoice->lr_date = $request->lr_date ? companyToYmd($request->lr_date) : null;
            $invoice->delivery_status = $request->delivery_status ?? 'in_transit';
            $this->applyCfaPharmaTaxInvoiceMeta($invoice, $request);
            $invoice->recurring = 'no';
            $invoice->save();

            // Update custom fields data
            if ($request->custom_fields_data) {
                $invoice->updateCustomFieldData($request->custom_fields_data);
            }

            // Get existing CFA Distributor Stock entries to restore main product stock
            $existingStocks = CFADistributorStock::where('invoice_id', $invoice->id)->get();
            
            // Restore stock that was previously reduced when invoice was created (product-level and batch-level)
            foreach ($existingStocks as $existingStock) {
                if ($existingStock->product_id && $existingStock->quantity > 0) {
                    $stockAdjustment = PurchaseStockAdjustment::where('product_id', $existingStock->product_id)
                        ->where('company_id', company()->id)
                        ->first();
                    
                    if ($stockAdjustment) {
                        // Restore the stock (add back the quantity)
                        $stockAdjustment->net_quantity += $existingStock->quantity;
                        $stockAdjustment->save();
                        
                        \Log::info('Restored product stock for CFA Distributor invoice update', [
                            'invoice_id' => $invoice->id,
                            'product_id' => $existingStock->product_id,
                            'quantity_restored' => $existingStock->quantity,
                            'new_stock' => $stockAdjustment->net_quantity
                        ]);
                    }

                    // Batch-wise: restore to purchase_batch_stock (same batch/expiry as existing stock)
                    $batchStock = PurchaseBatchStock::firstOrCreate(
                        [
                            'company_id' => company()->id,
                            'product_id' => $existingStock->product_id,
                            'batch'      => $existingStock->batch ?? null,
                            'expiry'     => $existingStock->expiry ?? null,
                        ],
                        ['quantity' => 0]
                    );
                    $batchStock->quantity = (float) $batchStock->quantity + (float) $existingStock->quantity;
                    $batchStock->save();
                }
            }
            
            // Delete existing items and stocks
            InvoiceItems::where('invoice_id', $invoice->id)->delete();
            CFADistributorStock::where('invoice_id', $invoice->id)->delete();

            // Store invoice items - ensure item_name is an array
            $itemNames = (isset($request->item_name) && is_array($request->item_name)) ? $request->item_name : [];
            
            foreach ($itemNames as $key => $item) {
                if (!is_null($item)) {
                    // Safely get array values using helper function
                    $itemSummary = $this->safeArrayGet($requestArrays['item_summary'], $key, null);
                    $itemQuantity = $this->safeArrayGet($quantity, $key, 0);
                    $costPerItem = $this->safeArrayGet($cost_per_item, $key, 0);
                    $itemAmount = $this->safeArrayGet($amount, $key, 0);
                    
                    // Handle taxes array safely
                    $taxes = null;
                    $taxValue = $this->safeArrayGet($requestArrays['taxes'], $key, null);
                    if (!empty($taxValue)) {
                        $taxes = json_encode($taxValue);
                    }
                    
                    // Handle other optional fields safely
                    // Get SKU value and use it for hsn_sac_code if hsn_sac_code is empty
                    $skuValue = $this->safeArrayGet($requestArrays['sku'], $key, null);
                    $hsnSacCodeValue = $this->safeArrayGet($requestArrays['hsn_sac_code'], $key, null);
                    $hsnSacCode = !empty($hsnSacCodeValue) ? $hsnSacCodeValue : $skuValue;
                    
                    $productId = $this->safeArrayGet($requestArrays['product_id'], $key, null);
                    $unitId = $this->safeArrayGet($requestArrays['unit_id'], $key, null);
                    $purchaseEntryId = $this->safeArrayGet($requestArrays['purchase_entry_id'], $key, null);
                    $scheme = $this->safeArrayGet($requestArrays['scheme'], $key, null);
                    $pack = $this->safeArrayGet($requestArrays['pack'], $key, null);
                    $mfr = $this->safeArrayGet($requestArrays['mfr'], $key, null);
                    $batch = $this->safeArrayGet($requestArrays['batch'], $key, null);
                    
                    // Handle expiry date safely
                    $exp = null;
                    $expValue = $this->safeArrayGet($requestArrays['exp'], $key, null);
                    if (!empty($expValue)) {
                        try {
                            $exp = date('Y-m-d', strtotime($expValue));
                        } catch (\Exception $e) {
                            $exp = null;
                        }
                    }
                    
                    // Handle MRP, PTS, PTR, DIS safely
                    $mrpValue = $this->safeArrayGet($requestArrays['mrp'], $key, null);
                    $mrp = ($mrpValue !== null && $mrpValue !== '') ? round($mrpValue, 2) : null;
                    
                    $ptsValue = $this->safeArrayGet($requestArrays['pts'], $key, null);
                    $pts = ($ptsValue !== null && $ptsValue !== '') ? round($ptsValue, 2) : null;
                    
                    $ptrValue = $this->safeArrayGet($requestArrays['ptr'], $key, null);
                    $ptr = ($ptrValue !== null && $ptrValue !== '') ? round($ptrValue, 2) : null;
                    
                    $disValue = $this->safeArrayGet($requestArrays['dis'], $key, null);
                    $dis = ($disValue !== null && $disValue !== '') ? round($disValue, 2) : null;
                    
                    $invoiceItem = InvoiceItems::create([
                        'invoice_id' => $invoice->id,
                        'item_name' => $item,
                        'item_summary' => $itemSummary,
                        'type' => 'item',
                        'quantity' => $itemQuantity,
                        'unit_price' => round($costPerItem, 2),
                        'amount' => round($itemAmount, 2),
                        'taxes' => $taxes,
                        'hsn_sac_code' => $hsnSacCode,
                        'product_id' => $productId,
                        'unit_id' => $unitId,
                        'purchase_entry_id' => $purchaseEntryId,
                        'scheme' => $scheme,
                        'pack' => $pack,
                        'mfr' => $mfr,
                        'batch' => $batch,
                        'exp' => $exp,
                        'mrp' => $mrp,
                        'pts' => $pts,
                        'ptr' => $ptr,
                        'dis' => $dis,
                        'field_order' => $key + 1,
                    ]);

                    // Create CFA/Distributor stock entry and reduce main product stock
                    if (!empty($purchaseEntryId)) {
                        $purchaseEntry = ProductPurchaseDetail::find($purchaseEntryId);
                        if ($purchaseEntry) {
                            // Safely get stock entry values
                            $stockBatch = $batch ?? $purchaseEntry->batch;
                            $stockExpiry = $exp ?? $purchaseEntry->expiry;
                            $stockQuantity = $itemQuantity ?? 0;
                            $stockPts = $pts ?? $purchaseEntry->pts ?? null;
                            $stockPtr = $ptr ?? $purchaseEntry->ptr ?? null;
                            $stockMrp = $mrp ?? $purchaseEntry->mrp ?? null;
                            $stockDis = $dis ?? $purchaseEntry->dis ?? null;
                            
                            // Create CFA Distributor Stock entry
                            CFADistributorStock::create([
                                'company_id' => company()->id,
                                'cfa_distributor_id' => $request->client_id,
                                'product_id' => $purchaseEntry->product_id,
                                'purchase_entry_id' => $purchaseEntry->id,
                                'invoice_id' => $invoice->id,
                                'batch' => $stockBatch,
                                'expiry' => $stockExpiry,
                                'quantity' => $stockQuantity,
                                'available_quantity' => $stockQuantity,
                                'pts' => $stockPts,
                                'ptr' => $stockPtr,
                                'mrp' => $stockMrp,
                                'dis' => $stockDis,
                            ]);
                            
                            // Reduce stock from PurchaseStockAdjustment when invoice is updated/billed
                            // Stock should reduce immediately on billing, not on delivery toggle
                            if ($stockQuantity > 0) {
                                $stockAdjustment = PurchaseStockAdjustment::firstOrCreate(
                                    ['product_id' => $purchaseEntry->product_id, 'company_id' => company()->id],
                                    ['net_quantity' => 0]
                                );
                                
                                // Reduce stock (subtract from net_quantity)
                                $stockAdjustment->net_quantity = max(0, $stockAdjustment->net_quantity - $stockQuantity);
                                $stockAdjustment->save();

                                // Batch-wise: deduct from purchase_batch_stock (FEFO)
                                PurchaseBatchStock::deductFefo((int) company()->id, (int) $purchaseEntry->product_id, (float) $stockQuantity);
                                
                                \Log::info('Reduced product stock for CFA Distributor invoice update', [
                                    'invoice_id' => $invoice->id,
                                    'product_id' => $purchaseEntry->product_id,
                                    'quantity_reduced' => $stockQuantity,
                                    'remaining_stock' => $stockAdjustment->net_quantity,
                                    'invoice_type' => $invoice->invoice_type ?? 'sgst_cgst'
                                ]);
                            }
                        }
                    }
                }
            }

            // Log activity
            if (user()) {
                self::createEmployeeActivity($userId, 'cfa-distributor-invoice-updated', $invoice->id, 'invoice');
            }

            return Reply::successWithData(__('messages.updateSuccess'), [
                'redirectUrl' => route('cfa-distributor-invoices.show', $invoice->id),
                'invoiceID' => $invoice->id
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating CFA/Distributor invoice: ' . $e->getMessage());
            \Log::error('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Safely log request data
            try {
                $requestData = $request->all();
                // Remove sensitive data if any
                unset($requestData['password'], $requestData['password_confirmation']);
                \Log::error('Request data: ' . json_encode($requestData));
            } catch (\Exception $logError) {
                \Log::error('Could not log request data: ' . $logError->getMessage());
            }
            
            return Reply::error('Error updating invoice: ' . $e->getMessage() . ' (Check logs for details)');
        }
    }

    /**
     * Update delivery status for CFA/Distributor invoice
     */
    public function updateDeliveryStatus(Request $request, $id)
    {
        try {
            $invoice = Invoice::findOrFail($id);

            if ((int) $invoice->company_id !== (int) company()->id) {
                abort_403();
            }
            $isCfaDistributorFlow = $invoice->cfaDistributorStocks()->exists();
            if (!$isCfaDistributorFlow) {
                $isOwnClient = in_array('client', user_roles(), true)
                    && (int) $invoice->client_id === (int) (user()->id ?? 0);
                if (!$isOwnClient) {
                    abort_403();
                }
            }
            if (!$this->userCanEditCfaDistributorInvoice($invoice)) {
                abort_403();
            }

            $deliveryStatus = $request->delivery_status;
            
            if (!in_array($deliveryStatus, ['in_transit', 'received'])) {
                return Reply::error('Invalid delivery status');
            }
            
            $invoice->delivery_status = $deliveryStatus;
            $invoice->save();
            
            return Reply::successWithData(__('messages.updateSuccess'), [
                'delivery_status' => $deliveryStatus,
                'status_text' => $deliveryStatus == 'received' ? 'Received' : 'In Transit',
                'status_class' => $deliveryStatus == 'received' ? 'success' : 'warning'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating delivery status: ' . $e->getMessage());
            return Reply::error('Error updating delivery status: ' . $e->getMessage());
        }
    }

    /**
     * Show payment modal for CFA Distributor invoice
     */
    public function showPaymentModal(Request $request)
    {
        $invoiceId = $request->invoice_id;
        $this->invoice = Invoice::findOrFail($invoiceId);
        
        // Check permissions
        $addPaymentPermission = user()->permission('add_payments');
        abort_403(!(
            $addPaymentPermission == 'all'
            || ($addPaymentPermission == 'added' && $this->invoice->added_by == user()->id)
        ));

        // Detect which route was called to determine route prefix
        $routeName = $request->route()->getName();
        if (strpos($routeName, 'cfa-stockist-invoices') !== false) {
            $routePrefix = 'cfa-stockist-invoices';
        } else {
            $routePrefix = 'cfa-distributor-invoices';
        }

        $this->data['paymentRoutePrefix'] = $routePrefix;

        return view('cfa-distributor-invoices.payment-modal', $this->data);
    }

    /**
     * Update payment status for CFA Distributor invoice - supports multiple partial payments
     */
    public function updatePaymentStatus(Request $request)
    {
        try {
            \Log::info('Payment status update request', $request->all());
            
            $request->validate([
                'invoice_id' => 'required|exists:invoices,id',
                'payment_mode' => 'required|string',
                'payment_date' => 'required|date',
                'payment_amount' => 'required|numeric|min:0.01',
                'edit_payment_id' => 'nullable|exists:payments,id'
            ]);

            $invoice = Invoice::findOrFail($request->invoice_id);
            
            // Check permissions
            $addPaymentPermission = user()->permission('add_payments');
            abort_403(!(
                $addPaymentPermission == 'all'
                || ($addPaymentPermission == 'added' && $invoice->added_by == user()->id)
            ));

            $paymentAmount = floatval($request->payment_amount);
            
            // Check if this is an edit or new payment
            if ($request->edit_payment_id) {
                // Edit existing payment
                $payment = Payment::findOrFail($request->edit_payment_id);
                
                // Store old amount for recalculation
                $oldAmount = $payment->amount;
                
                $payment->amount = $paymentAmount;
                $payment->gateway = $request->payment_mode;
                $payment->transaction_id = $request->payment_reference ?? null;
                $payment->paid_on = \Carbon\Carbon::parse($request->payment_date);
                $payment->remarks = $request->payment_notes ?? null;
                $payment->save();
                
                \Log::info('Payment updated', [
                    'payment_id' => $payment->id,
                    'old_amount' => $oldAmount,
                    'new_amount' => $paymentAmount
                ]);
            } else {
                // Create new payment record
                $payment = new Payment();
                $payment->company_id = company()->id;
                $payment->project_id = $invoice->project_id;
                $payment->invoice_id = $invoice->id;
                $payment->currency_id = $invoice->currency_id;
                $payment->amount = $paymentAmount;
                $payment->gateway = $request->payment_mode;
                $payment->transaction_id = $request->payment_reference ?? null;
                $payment->paid_on = \Carbon\Carbon::parse($request->payment_date);
                $payment->status = 'complete';
                $payment->remarks = $request->payment_notes ?? null;
                $payment->save();
                
                \Log::info('New payment created', [
                    'payment_id' => $payment->id,
                    'amount' => $paymentAmount
                ]);
            }

            // Recalculate invoice status
            $totalPaid = $invoice->getPaidAmount();
            $newDueAmount = $invoice->total - $totalPaid;

            // Update invoice status based on total paid amount
            if ($newDueAmount <= 0.01) {
                // Fully paid (with small tolerance for rounding)
                $invoice->status = 'paid';
                $invoice->due_amount = 0;
            } else if ($totalPaid > 0) {
                // Partially paid
                $invoice->status = 'partial';
                $invoice->due_amount = $newDueAmount;
            } else {
                // No payment yet
                $invoice->status = 'unpaid';
                $invoice->due_amount = $invoice->total;
            }
            
            $invoice->save();

            \Log::info('Invoice status updated', [
                'invoice_id' => $invoice->id,
                'total_paid' => $totalPaid,
                'new_due_amount' => $invoice->due_amount,
                'new_status' => $invoice->status
            ]);

            return Reply::successWithData(__('Payment recorded successfully'), [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'payment_amount' => number_format($paymentAmount, 2),
                'new_status' => $invoice->status,
                'total_paid' => $totalPaid,
                'due_amount' => max(0, $invoice->due_amount),
                'is_fully_paid' => $invoice->status == 'paid'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error in payment update', [
                'errors' => $e->errors()
            ]);
            return Reply::error($e->validator->errors()->first());
        } catch (\Exception $e) {
            \Log::error('Error updating payment status', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Reply::error('Error updating payment status: ' . $e->getMessage());
        }
    }

    /**
     * Delete a payment record and update invoice status
     */
    public function deletePayment(Request $request)
    {
        try {
            \Log::info('Delete payment request', $request->all());
            
            $request->validate([
                'payment_id' => 'required|exists:payments,id',
                'invoice_id' => 'required|exists:invoices,id'
            ]);

            $payment = Payment::findOrFail($request->payment_id);
            $invoice = Invoice::findOrFail($request->invoice_id);
            
            // Check permissions
            $deletePaymentPermission = user()->permission('delete_payments');
            if (!$deletePaymentPermission || $deletePaymentPermission == 'none') {
                $deletePaymentPermission = user()->permission('add_payments'); // Fallback to add_payments permission
            }
            
            abort_403(!(
                $deletePaymentPermission == 'all'
                || ($deletePaymentPermission == 'added' && $invoice->added_by == user()->id)
            ));

            // Verify payment belongs to invoice
            if ($payment->invoice_id != $invoice->id) {
                return Reply::error('Payment does not belong to this invoice');
            }

            $deletedAmount = $payment->amount;
            
            // Delete the payment
            $payment->delete();
            
            \Log::info('Payment deleted', [
                'payment_id' => $request->payment_id,
                'amount' => $deletedAmount,
                'invoice_id' => $invoice->id
            ]);

            // Recalculate invoice status
            $totalPaid = $invoice->getPaidAmount();
            $newDueAmount = $invoice->total - $totalPaid;

            // Update invoice status
            if ($newDueAmount <= 0.01 && $totalPaid > 0) {
                $invoice->status = 'paid';
                $invoice->due_amount = 0;
            } else if ($totalPaid > 0) {
                $invoice->status = 'partial';
                $invoice->due_amount = $newDueAmount;
            } else {
                $invoice->status = 'unpaid';
                $invoice->due_amount = $invoice->total;
            }
            
            $invoice->save();

            \Log::info('Invoice status updated after payment deletion', [
                'invoice_id' => $invoice->id,
                'new_status' => $invoice->status,
                'new_due_amount' => $invoice->due_amount,
                'total_paid' => $totalPaid
            ]);

            return Reply::successWithData(__('Payment deleted successfully'), [
                'invoice_id' => $invoice->id,
                'deleted_amount' => number_format($deletedAmount, 2),
                'new_status' => $invoice->status,
                'total_paid' => $totalPaid,
                'due_amount' => $invoice->due_amount
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error in payment delete', [
                'errors' => $e->errors()
            ]);
            return Reply::error($e->validator->errors()->first());
        } catch (\Exception $e) {
            \Log::error('Error deleting payment', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Reply::error('Error deleting payment: ' . $e->getMessage());
        }
    }

    /**
     * Display a CFA/Distributor invoice
     */
    public function showCFADistributorInvoice($id)
    {
        // Admin, accountant, and FSA Executive users have full access
        // Also allow clients (CFA Distributors) to view their own invoices
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            $this->viewPermission = 'all';
        } else {
            // Check if user is a client and this is their invoice
            $invoice = Invoice::findOrFail($id);
            if (in_array('client', user_roles()) && $invoice->client_id == user()->id) {
                $this->viewPermission = 'owned';
            } else {
                $this->viewPermission = user()->permission('view_cfa_distributor_invoices');
                abort_403(!in_array($this->viewPermission, ['all', 'added', 'owned', 'both']));
            }
        }
        
        $this->invoice = Invoice::with([
            'order',
            'client', 
            'client.clientDetails', 
            'items' => function($query) {
                $query->where('type', 'item')->orderBy('field_order', 'asc')->orderBy('id', 'asc');
            }, 
            'items.unit', 
            'items.product' => function($query) {
                $query->select('id', 'name', 'packing', 'sku', 'hsn_sac_code', 'vendor_id', 'taxes');
            }, 
            'items.purchaseEntry',
            'items.purchaseEntry.product' => function($query) {
                $query->select('id', 'name', 'packing', 'sku', 'hsn_sac_code', 'vendor_id', 'taxes');
            },
            'items.purchaseEntry.product.vendor', 
            'items.purchaseEntry.product.unit', 
            'cfaDistributorStocks', 
            'cfaDistributorStocks.product' => function($query) {
                $query->select('id', 'name', 'packing', 'sku', 'hsn_sac_code', 'vendor_id', 'taxes');
            },
            'address'
        ])->findOrFail($id)->withCustomFields();
        
        // Ensure items are properly ordered - display exactly what's in the database
        // Items should already be unique by ID (primary key) from the database
        if ($this->invoice->items) {
            // Just ensure unique by ID in case of any query issues, then sort by field_order
            $this->invoice->items = $this->invoice->items->unique('id')->sortBy(function($item) {
                return $item->field_order ?? ($item->id ?? 999999);
            })->values();
            
            // Log if we detect any duplicate IDs (which shouldn't happen)
            $itemIds = $this->invoice->items->pluck('id')->toArray();
            $duplicateIds = array_diff_assoc($itemIds, array_unique($itemIds));
            if (!empty($duplicateIds)) {
                \Log::warning('Duplicate invoice item IDs detected in database', [
                    'invoice_id' => $this->invoice->id,
                    'duplicate_ids' => array_unique($duplicateIds),
                    'total_items' => $this->invoice->items->count()
                ]);
            }
        }
        
        $this->userId = UserService::getUserId();

        // Do not re-check view_invoices: access is already set above (full CFA, own client, or
        // view_cfa_distributor_invoices). A second check against view_invoices + send_status
        // would wrongly block CFA distributors and staff with distributor-invoice–only view rights.

        $getCustomFieldGroupsWithFields = $this->invoice->getCustomFieldGroupsWithFields();
        if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }

        $this->paidAmount = $this->invoice->getPaidAmount();
        $this->pageTitle = $this->invoice->invoice_number;

        $this->discount = 0;
        if ($this->invoice->discount > 0) {
            if ($this->invoice->discount_type == 'percent') {
                $this->discount = (($this->invoice->discount / 100) * $this->invoice->sub_total);
            } else {
                $this->discount = $this->invoice->discount;
            }
        }

        if ($this->invoice->discount_type == 'percent') {
            $discountAmount = $this->invoice->discount;
            $this->discountType = $discountAmount . '%';
        } else {
            $discountAmount = $this->invoice->discount;
            $this->discountType = currency_format($discountAmount, $this->invoice->currency_id);
        }

        $taxList = array();
        $items = InvoiceItems::whereNotNull('taxes')
            ->where('invoice_id', $this->invoice->id)
            ->get();

        foreach ($items as $item) {
            $taxes = json_decode($item->taxes, true);
            if (!is_array($taxes)) {
                continue;
            }
            
            foreach ($taxes as $tax) {
                $this->tax = InvoiceItems::taxbyid($tax)->first();
                if ($this->tax) {
                    if (!isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {
                        if ($this->invoice->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->invoice->sub_total) * $this->discount) * ($this->tax->rate_percent / 100);
                        } else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $item->amount * ($this->tax->rate_percent / 100);
                        }
                    } else {
                        if ($this->invoice->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->invoice->sub_total) * $this->discount) * ($this->tax->rate_percent / 100));
                        } else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + ($item->amount * ($this->tax->rate_percent / 100));
                        }
                    }
                }
            }
        }

        $this->taxes = $taxList;
        $this->company = $this->invoice->company;
        $this->settings = company();
        $this->invoiceSetting = $this->company->invoiceSetting;
        $this->creditNote = 0;
        $this->firstInvoice = Invoice::where('company_id', company()->id)->orderBy('id', 'desc')->first();
        $this->credentials = PaymentGatewayCredentials::first();
        $this->methods = OfflinePaymentMethod::activeMethod();
        $this->user = $this->invoice->client;
        $this->payments = Payment::with(['offlineMethod'])->where('invoice_id', $this->invoice->id)->where('status', 'complete')->orderByDesc('paid_on')->get();
        $this->deletePermission = user()->permission('delete_invoices');
        $this->addInvoicesPermission = user()->permission('add_invoices');
        $this->editInvoicesPermission = user()->permission('edit_invoices');

        // Check if this is an IGST invoice
        // Priority 1: Check database field (most reliable)
        $isIGSTInvoice = false;
        if (isset($this->invoice->invoice_type)) {
            $isIGSTInvoice = $this->invoice->invoice_type === 'igst';
        }
        
        // Priority 2: Check request parameter (for backward compatibility)
        if (!$isIGSTInvoice && (request('type') == 'igst' || request()->has('igst'))) {
            $isIGSTInvoice = true;
            // Update database field if missing
            if (!isset($this->invoice->invoice_type) || $this->invoice->invoice_type !== 'igst') {
                $this->invoice->invoice_type = 'igst';
                $this->invoice->save();
            }
        }
        
        // Priority 3: Check stored invoice type marker in note field (for backward compatibility)
        if (!$isIGSTInvoice && $this->invoice->note && strpos($this->invoice->note, '<!--IGST_INVOICE-->') !== false) {
            $isIGSTInvoice = true;
            // Update database field
            $this->invoice->invoice_type = 'igst';
            $this->invoice->save();
        }
        
        // Priority 4: Check invoice items for IGST tax (if tax name contains IGST)
        if (!$isIGSTInvoice && $this->invoice->items) {
            foreach ($this->invoice->items as $item) {
                if ($item->taxes) {
                    $taxes = [];
                    if (is_string($item->taxes)) {
                        $decoded = json_decode($item->taxes, true);
                        if (is_array($decoded)) {
                            $taxes = $decoded;
                        } elseif (is_numeric($item->taxes)) {
                            $taxes = [(int)$item->taxes];
                        }
                    } elseif (is_array($item->taxes)) {
                        $taxes = $item->taxes;
                    }
                    
                    foreach ($taxes as $taxId) {
                        if (empty($taxId)) continue;
                        $tax = \App\Models\Tax::find($taxId);
                        if ($tax && stripos($tax->tax_name ?? '', 'IGST') !== false) {
                            $isIGSTInvoice = true;
                            // Update database field
                            $this->invoice->invoice_type = 'igst';
                            $this->invoice->save();
                            break 2; // Break out of both loops
                        }
                    }
                }
            }
        }
        
        // Store invoice type in data for view
        $this->invoiceType = $isIGSTInvoice ? 'igst' : '';
        $this->data['invoiceType'] = $this->invoiceType;
        $this->data['isIGSTInvoice'] = $isIGSTInvoice;
        
        // Use pharmaceutical-specific template for CFA/Distributor invoices
        if ($isIGSTInvoice) {
            return view('invoices.cfa-distributor.igst-show', $this->data);
        }
        return view('invoices.cfa-distributor.pharma-show', $this->data);
    }

    /**
     * CFA/Stockist Invoice Methods
     * TODO: Implement full functionality
     */
    public function indexCFAStockistInvoices(CFAStockistInvoicesDataTable $dataTable)
    {
        // Admin, accountant, and FSA Executive users have full access
        // Also allow clients (CFA Distributors/Stockists) to view their invoices
        if (!PharmaDesignationHelper::hasFullCFAAccess() && !in_array('client', user_roles())) {
            $viewPermission = user()->permission('view_cfa_stockist_invoices');
            abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
        }

        if (!request()->ajax()) {
            // Load CFA Stockists for filter
            // If admin, show all stockists; if CFA/Distributor, show only their stockists
            if (PharmaDesignationHelper::hasFullCFAAccess()) {
                $this->cfaStockists = \App\Models\CFAStockist::all();
            } else {
                $cfaDistributorId = user()->id; // Current user (CFA/Distributor)
                $this->cfaStockists = \App\Models\CFAStockist::whereHas('cfaDistributors', function($query) use ($cfaDistributorId) {
                    $query->where('cfa_distributor_id', $cfaDistributorId);
                })->get();
            }
        }

        return $dataTable->render('invoices.cfa-stockist.index', $this->data);
    }

    public function createCFAStockistInvoice()
    {
        abort_403(!$this->userCanAddCfaStockistInvoice());

        $this->pageTitle = __('CFA/Stockist Invoice');
        $this->setupCfaStockistInvoiceForm(null);

        return view('invoices.cfa-stockist.create', $this->data);
    }

    /**
     * @param  \App\Models\Invoice|null  $editingInvoice
     */
    private function setupCfaStockistInvoiceForm($editingInvoice = null)
    {
        if ($editingInvoice) {
            $this->editingInvoice = $editingInvoice;
            $this->pageTitle = __('Edit CFA/Stockist Invoice') . ' - ' . $editingInvoice->invoice_number;
            $this->editingLineItems = $this->buildCfaStockistInvoiceEditLineItems($editingInvoice);
            $this->editingCfaStockistId = CFAStockistStock::where('invoice_id', $editingInvoice->id)->value('cfa_stockist_id');
            $tz = company()->timezone;
            $this->cfaInvoiceIssueDate = $editingInvoice->issue_date
                ? $editingInvoice->issue_date->timezone($tz)->format(company()->date_format) : null;
            $this->cfaInvoiceDueDate = $editingInvoice->due_date
                ? $editingInvoice->due_date->timezone($tz)->format(company()->date_format) : null;
        } else {
            $this->editingInvoice = null;
            $this->editingLineItems = [];
            $this->editingCfaStockistId = null;
            $this->cfaInvoiceIssueDate = null;
            $this->cfaInvoiceDueDate = null;
        }

        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            $this->cfaDistributors = User::without('session')
                ->join('role_user', 'role_user.user_id', '=', 'users.id')
                ->join('roles', 'roles.id', '=', 'role_user.role_id')
                ->join('client_details', 'users.id', '=', 'client_details.user_id')
                ->select('users.id', 'users.name', 'users.email', 'client_details.company_name')
                ->whereNull('users.is_client_contact')
                ->where('roles.name', 'client')
                ->where('users.status', 'active')
                ->where('users.company_id', company()->id)
                ->distinct()
                ->get()
                ->sortBy(function ($user) {
                    return $user->company_name ?? $user->name;
                })
                ->values();

            $cfaDistributorId = $editingInvoice ? (int) $editingInvoice->client_id : null;
        } else {
            $cfaDistributorId = user()->id;
            $this->cfaDistributors = collect([user()]);
        }

        if ($cfaDistributorId) {
            $query = CFAStockist::whereHas('cfaDistributors', function ($q) use ($cfaDistributorId) {
                $q->where('cfa_distributor_id', $cfaDistributorId);
            });
            $cfaUser = User::with('clientDetails.areas', 'clientDetails.headquarters')->find($cfaDistributorId);
            if ($cfaUser && $cfaUser->clientDetails) {
                $areaIds = $cfaUser->clientDetails->areas->pluck('id')->filter()->toArray();
                $hqIds = $cfaUser->clientDetails->headquarters->pluck('id')->filter()->toArray();
                if (!empty($areaIds) || !empty($hqIds)) {
                    $query->where(function ($q) use ($areaIds, $hqIds) {
                        if (!empty($areaIds) && !empty($hqIds)) {
                            $q->whereIn('area_id', $areaIds)->orWhereIn('headquarter_id', $hqIds);
                        } elseif (!empty($areaIds)) {
                            $q->whereIn('area_id', $areaIds);
                        } else {
                            $q->whereIn('headquarter_id', $hqIds);
                        }
                    });
                }
            }
            $this->cfaStockists = $query->get();
        } else {
            $this->cfaStockists = CFAStockist::all();
        }

        if ($cfaDistributorId) {
            $availableStock = CFADistributorStock::with(['product.unit', 'product.vendor', 'cfaDistributor'])
                ->where('cfa_distributor_id', $cfaDistributorId)
                ->where('available_quantity', '>', 0)
                ->where('company_id', company()->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $productsGrouped = [];
            foreach ($availableStock as $stock) {
                $key = $stock->product_id . '_' . ($stock->batch ?? 'no_batch');
                if (!isset($productsGrouped[$key])) {
                    $productsGrouped[$key] = [
                        'product' => $stock->product,
                        'stocks' => []
                    ];
                }
                $productsGrouped[$key]['stocks'][] = $stock;
            }
            $this->products = collect($productsGrouped);
        } else {
            $this->products = collect([]);
        }

        $this->currencies = Currency::all();
        $this->taxes = Tax::all();
        $this->invoiceSetting = invoice_setting();
        $this->projects = Project::allProjects();
        $this->units = UnitType::all();
        $this->bankAccounts = BankAccount::all();

        if ($editingInvoice) {
            $this->lastInvoice = $editingInvoice->custom_invoice_number ?: preg_replace(
                '/^' . preg_quote($this->invoiceSetting->invoice_prefix . $this->invoiceSetting->invoice_number_separator, '/') . '/',
                '',
                (string) $editingInvoice->invoice_number
            );
            if ($this->lastInvoice === '' || $this->lastInvoice === null) {
                $this->lastInvoice = $editingInvoice->invoice_number;
            }
        } else {
            $this->lastInvoice = Invoice::lastInvoiceNumber() + 1;
        }
        $this->companyCurrency = Currency::where('id', company()->currency_id)->first();

        $this->zero = '';
        $len = is_string($this->lastInvoice) ? strlen($this->lastInvoice) : strlen((string) $this->lastInvoice);
        if ($len < $this->invoiceSetting->invoice_digit) {
            $condition = $this->invoiceSetting->invoice_digit - $len;
            for ($i = 0; $i < $condition; $i++) {
                $this->zero = '0' . $this->zero;
            }
        }

        if ($editingInvoice) {
            $isIGSTInvoice = ($editingInvoice->invoice_type ?? '') === 'igst' || request('type') == 'igst' || request()->has('igst');
        } else {
            $isIGSTInvoice = request('type') == 'igst' || request()->has('igst');
        }
        $this->invoiceType = $isIGSTInvoice ? 'igst' : '';
        $this->data['invoiceType'] = $this->invoiceType;
        $this->data['isIGSTInvoice'] = $isIGSTInvoice;
    }

    private function buildCfaStockistInvoiceEditLineItems(Invoice $editingInvoice): array
    {
        $editingInvoice->load(['items' => function ($q) {
            $q->where('type', 'item')->orderBy('id');
        }, 'cfaStockistStocks' => function ($q) {
            $q->orderBy('id');
        }]);
        $items = $editingInvoice->items;
        $stocks = $editingInvoice->cfaStockistStocks;
        $lines = [];
        for ($i = 0; $i < $items->count(); $i++) {
            $item = $items->get($i);
            $st = $stocks->get($i);
            if (! $item || ! $st || ! $item->product_id) {
                continue;
            }
            $lines[] = [
                'product_id' => (int) $item->product_id,
                'cfa_distributor_stock_id' => (int) $st->cfa_distributor_stock_id,
                'quantity' => (float) $item->quantity,
            ];
        }

        return $lines;
    }

    /**
     * Create invoice line items and CFA stockist stock rows (shared by store and update).
     *
     * @return string|null  Error message, or null on success
     */
    private function persistCfaStockistInvoiceLineItems(Invoice $invoice, StoreInvoice $request): ?string
    {
        $savedItemsCount = 0;
        foreach ($request->item_name as $key => $item) {
            if (is_null($item) || trim($item) === '') {
                \Log::warning('Skipping empty item at index ' . $key . ' for invoice ' . $invoice->id);
                continue;
            }

            $skuValue = $request->sku[$key] ?? null;
            $hsnSacCode = $request->hsn_sac_code[$key] ?? $skuValue ?? null;

            $paidQty = floatval($request->quantity[$key] ?? 0);
            $scheme = ($request->scheme[$key] ?? '') ? trim($request->scheme[$key]) : '';
            $totalQty = $paidQty;
            $freeQty = 0;

            if ($scheme && strpos($scheme, '+') !== false) {
                $schemeParts = explode('+', $scheme);
                $schemePaid = floatval($schemeParts[0] ?? 0);
                $schemeFree = floatval($schemeParts[1] ?? 0);

                if ($schemePaid > 0 && $paidQty > 0) {
                    $freePercentage = ($schemeFree / $schemePaid) * 100;
                    $freeQty = floor(($paidQty * $freePercentage) / 100);
                    $totalQty = $paidQty + $freeQty;
                }
            }

            $invoiceItem = InvoiceItems::create([
                'invoice_id' => $invoice->id,
                'item_name' => $item,
                'item_summary' => $request->item_summary[$key] ?? null,
                'type' => 'item',
                'quantity' => $paidQty,
                'unit_price' => round($request->cost_per_item[$key] ?? 0, 2),
                'amount' => round($request->amount[$key] ?? 0, 2),
                'taxes' => isset($request->taxes[$key]) && !empty($request->taxes[$key]) ? json_encode($request->taxes[$key]) : null,
                'hsn_sac_code' => $hsnSacCode,
                'product_id' => $request->product_id[$key] ?? null,
                'unit_id' => $request->unit_id[$key] ?? null,
                'purchase_entry_id' => null,
                'scheme' => $scheme,
                'pack' => $request->pack[$key] ?? null,
                'mfr' => $request->mfr[$key] ?? null,
                'batch' => $request->batch[$key] ?? null,
                'exp' => $request->exp[$key] ? date('Y-m-d', strtotime($request->exp[$key])) : null,
                'mrp' => isset($request->mrp[$key]) ? round($request->mrp[$key] ?? 0, 2) : null,
                'pts' => isset($request->pts[$key]) ? round($request->pts[$key] ?? 0, 2) : null,
                'ptr' => isset($request->ptr[$key]) ? round($request->ptr[$key] ?? 0, 2) : null,
                'dis' => isset($request->dis[$key]) ? round($request->dis[$key] ?? 0, 2) : null,
                'field_order' => $savedItemsCount + 1,
            ]);

            $savedItemsCount++;

            if (!empty($request->cfa_distributor_stock_id[$key])) {
                $distributorStock = CFADistributorStock::find($request->cfa_distributor_stock_id[$key]);

                if ($distributorStock) {
                    if ($distributorStock->available_quantity < $totalQty) {
                        \Log::error('Insufficient stock for invoice item', [
                            'invoice_id' => $invoice->id,
                            'item_index' => $key,
                            'distributor_stock_id' => $distributorStock->id,
                            'required_qty' => $totalQty,
                            'available_qty' => $distributorStock->available_quantity
                        ]);
                        return 'Insufficient stock available. Required: ' . $totalQty . ', Available: ' . $distributorStock->available_quantity;
                    }

                    $distributorStock->available_quantity -= $totalQty;
                    $distributorStock->save();

                    CFAStockistStock::create([
                        'company_id' => company()->id,
                        'cfa_distributor_id' => $request->cfa_distributor_id,
                        'cfa_stockist_id' => $request->cfa_stockist_id,
                        'product_id' => $distributorStock->product_id,
                        'cfa_distributor_stock_id' => $distributorStock->id,
                        'invoice_id' => $invoice->id,
                        'batch' => $request->batch[$key] ?? $distributorStock->batch,
                        'expiry' => $request->exp[$key] ? date('Y-m-d', strtotime($request->exp[$key])) : ($distributorStock->expiry ? $distributorStock->expiry->format('Y-m-d') : null),
                        'quantity' => $totalQty,
                        'pts' => isset($request->pts[$key]) ? round($request->pts[$key] ?? 0, 2) : ($distributorStock->pts ?? 0),
                        'ptr' => isset($request->ptr[$key]) ? round($request->ptr[$key] ?? 0, 2) : ($distributorStock->ptr ?? 0),
                        'mrp' => isset($request->mrp[$key]) ? round($request->mrp[$key] ?? 0, 2) : ($distributorStock->mrp ?? 0),
                        'dis' => isset($request->dis[$key]) ? round($request->dis[$key] ?? 0, 2) : ($distributorStock->dis ?? 0),
                    ]);
                } else {
                    \Log::error('CFA Distributor Stock not found', [
                        'cfa_distributor_stock_id' => $request->cfa_distributor_stock_id[$key]
                    ]);
                    return 'Stock entry not found for item: ' . $item;
                }
            }
        }

        return null;
    }

    public function storeCFAStockistInvoice(StoreInvoice $request)
    {
        try {
            abort_403(!$this->userCanAddCfaStockistInvoice());

            // CFA distributors (client role) always bill as themselves; ignore tampered distributor id
            if (in_array('client', user_roles(), true)) {
                $request->merge(['cfa_distributor_id' => user()->id]);
            }

            $userId = UserService::getUserId();
            
            // Validate required fields
            if (empty($request->cfa_distributor_id)) {
                return Reply::error('CFA/Distributor is required.');
            }
            
            if (empty($request->cfa_stockist_id)) {
                return Reply::error('CFA Stockist is required.');
            }
            
            // Validate items
            $items = $request->item_name;
            if (empty($items)) {
                return Reply::error(__('messages.addItem'));
            }

            foreach ($items as $itm) {
                if (is_null($itm)) {
                    return Reply::error(__('messages.itemBlank'));
                }
            }

            foreach ($request->quantity as $qty) {
                if (!is_numeric($qty) && (intval($qty) < 1)) {
                    return Reply::error(__('messages.quantityNumber'));
                }
            }
            
            // Validate cost_per_item and amount arrays
            if (empty($request->cost_per_item) || empty($request->amount)) {
                return Reply::error(__('messages.addItem'));
            }
            
            foreach ($request->cost_per_item as $rate) {
                if (!is_numeric($rate)) {
                    return Reply::error(__('messages.unitPriceNumber'));
                }
            }
            
            foreach ($request->amount as $amt) {
                if (!is_numeric($amt)) {
                    return Reply::error(__('messages.amountNumber'));
                }
            }

            // Create invoice
            $invoice = new Invoice();
            $invoice->company_id = company()->id;
            $invoice->added_by = $userId;
            // Set client_id to CFA/Distributor (the one billing)
            $invoice->client_id = $request->cfa_distributor_id;
            $invoice->issue_date = companyToYmd($request->issue_date);
            $invoice->due_date = companyToYmd($request->due_date);
            $invoice->sub_total = round($request->sub_total, 2);
            $invoice->discount = round($request->discount, 2);
            $invoice->discount_type = $request->discount_type;
            $invoice->total = round($request->total, 2);
            $invoice->due_amount = round($request->total, 2);
            $invoice->currency_id = $request->currency_id;
            $invoice->default_currency_id = company()->currency_id;
            $invoice->exchange_rate = $request->exchange_rate;
            $invoice->status = $request->status;
            $invoice->note = trim_editor($request->note);
            $invoice->invoice_number = $request->invoice_number;
            $invoice->company_address_id = $request->company_address_id;
            $invoice->bank_account_id = $request->bank_account_id;
            $invoice->payment_status = $request->payment_status == null ? '0' : $request->payment_status;
            $invoice->invoice_payment_id = $request->invoice_payment_id;
            $invoice->calculate_tax = $request->calculate_tax;
            $invoice->lr_number = $request->lr_number ?? null;
            $invoice->lr_date = $request->lr_date ? companyToYmd($request->lr_date) : null;
            $invoice->recurring = 'no';
            // Store IGST invoice type if applicable
            // Check both request parameter and hidden form field
            $isIGSTInvoice = ($request->has('type') && $request->type == 'igst') || 
                             ($request->has('invoice_type') && $request->invoice_type == 'igst');
            if ($isIGSTInvoice) {
                $invoice->invoice_type = 'igst';
            } else {
                $invoice->invoice_type = 'sgst_cgst'; // Default to SGST/CGST
            }
            $invoice->save();

            // Add custom fields data
            if ($request->custom_fields_data) {
                $invoice->updateCustomFieldData($request->custom_fields_data);
            }

            $lineErr = $this->persistCfaStockistInvoiceLineItems($invoice, $request);
            if ($lineErr !== null) {
                return Reply::error($lineErr);
            }

            // Log activity
            if (user()) {
                self::createEmployeeActivity($userId, 'cfa-stockist-invoice-created', $invoice->id, 'invoice');
            }
            
            \Log::info('CFA/Stockist Invoice created successfully', [
                'invoice_id' => $invoice->id,
                'cfa_distributor_id' => $request->cfa_distributor_id,
                'cfa_stockist_id' => $request->cfa_stockist_id
            ]);

            return Reply::successWithData(__('messages.recordSaved'), [
                'redirectUrl' => route('cfa-stockist-invoices.show', $invoice->id),
                'invoiceID' => $invoice->id
            ]);
        } catch (\Exception $e) {
            \Log::error('Error storing CFA/Stockist invoice: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return Reply::error('Error creating invoice: ' . $e->getMessage());
        }
    }

    public function editCFAStockistInvoice($id)
    {
        $invoice = Invoice::where('company_id', company()->id)->findOrFail($id);
        abort_403(!$this->userCanEditCfaStockistInvoice($invoice));
        abort_unless(CFAStockistStock::where('invoice_id', $invoice->id)->exists(), 404);

        $this->setupCfaStockistInvoiceForm($invoice);

        return view('invoices.cfa-stockist.create', $this->data);
    }

    public function updateCFAStockistInvoice(StoreInvoice $request, $id)
    {
        try {
            $invoice = Invoice::where('company_id', company()->id)->findOrFail($id);
            abort_403(!$this->userCanEditCfaStockistInvoice($invoice));
            abort_unless(CFAStockistStock::where('invoice_id', $invoice->id)->exists(), 404);

            if (in_array('client', user_roles(), true)) {
                $request->merge(['cfa_distributor_id' => user()->id]);
            }

            $userId = UserService::getUserId();

            if (empty($request->cfa_distributor_id)) {
                return Reply::error('CFA/Distributor is required.');
            }
            if (empty($request->cfa_stockist_id)) {
                return Reply::error('CFA Stockist is required.');
            }
            $items = $request->item_name;
            if (empty($items)) {
                return Reply::error(__('messages.addItem'));
            }
            foreach ($items as $itm) {
                if (is_null($itm)) {
                    return Reply::error(__('messages.itemBlank'));
                }
            }
            foreach ($request->quantity as $qty) {
                if (!is_numeric($qty) && (intval($qty) < 1)) {
                    return Reply::error(__('messages.quantityNumber'));
                }
            }
            if (empty($request->cost_per_item) || empty($request->amount)) {
                return Reply::error(__('messages.addItem'));
            }
            foreach ($request->cost_per_item as $rate) {
                if (!is_numeric($rate)) {
                    return Reply::error(__('messages.unitPriceNumber'));
                }
            }
            foreach ($request->amount as $amt) {
                if (!is_numeric($amt)) {
                    return Reply::error(__('messages.amountNumber'));
                }
            }

            DB::beginTransaction();

            foreach (CFAStockistStock::where('invoice_id', $invoice->id)->get() as $st) {
                $ds = CFADistributorStock::find($st->cfa_distributor_stock_id);
                if ($ds) {
                    $ds->available_quantity += $st->quantity;
                    $ds->save();
                }
            }
            CFAStockistStock::where('invoice_id', $invoice->id)->delete();
            InvoiceItems::where('invoice_id', $invoice->id)->delete();

            $invoice->client_id = $request->cfa_distributor_id;
            $invoice->issue_date = companyToYmd($request->issue_date);
            $invoice->due_date = companyToYmd($request->due_date);
            $invoice->sub_total = round($request->sub_total, 2);
            $invoice->discount = round($request->discount, 2);
            $invoice->discount_type = $request->discount_type;
            $invoice->total = round($request->total, 2);
            $invoice->due_amount = round($request->total, 2);
            $invoice->currency_id = $request->currency_id;
            $invoice->default_currency_id = company()->currency_id;
            $invoice->exchange_rate = $request->exchange_rate;
            $invoice->status = $request->status;
            $invoice->note = trim_editor($request->note);
            $invoice->invoice_number = $request->invoice_number;
            $invoice->company_address_id = $request->company_address_id;
            $invoice->bank_account_id = $request->bank_account_id;
            $invoice->payment_status = $request->payment_status == null ? '0' : $request->payment_status;
            $invoice->invoice_payment_id = $request->invoice_payment_id;
            $invoice->calculate_tax = $request->calculate_tax;
            $invoice->lr_number = $request->lr_number ?? null;
            $invoice->lr_date = $request->lr_date ? companyToYmd($request->lr_date) : null;
            $isIGSTInvoice = ($request->has('type') && $request->type == 'igst')
                || ($request->has('invoice_type') && $request->invoice_type == 'igst');
            if ($isIGSTInvoice) {
                $invoice->invoice_type = 'igst';
            } else {
                $invoice->invoice_type = 'sgst_cgst';
            }
            $invoice->save();

            if ($request->custom_fields_data) {
                $invoice->updateCustomFieldData($request->custom_fields_data);
            }

            $lineErr = $this->persistCfaStockistInvoiceLineItems($invoice, $request);
            if ($lineErr !== null) {
                DB::rollBack();
                return Reply::error($lineErr);
            }

            DB::commit();

            if (user()) {
                self::createEmployeeActivity($userId, 'cfa-stockist-invoice-updated', $invoice->id, 'invoice');
            }

            return Reply::successWithData(__('messages.updateSuccess'), [
                'redirectUrl' => route('cfa-stockist-invoices.show', $invoice->id),
                'invoiceID' => $invoice->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating CFA/Stockist invoice: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return Reply::error('Error updating invoice: ' . $e->getMessage());
        }
    }

    public function showCFAStockistInvoice($id)
    {
        // Admin, accountant, and FSA Executive users have full access
        // Also allow clients (CFA Distributors) to view their stockist invoices
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            $this->viewPermission = 'all';
        } else {
            // Check if user is a client and this invoice belongs to them
            $invoice = Invoice::findOrFail($id);
            if (in_array('client', user_roles()) && $invoice->client_id == user()->id) {
                $this->viewPermission = 'owned';
            } else {
                $this->viewPermission = user()->permission('view_cfa_stockist_invoices');
                abort_403(!in_array($this->viewPermission, ['all', 'added', 'owned', 'both']));
            }
        }
        
        $this->invoice = Invoice::with([
            'order',
            'client', 
            'client.clientDetails', 
            'items' => function($query) {
                $query->where('type', 'item')->orderBy('field_order', 'asc')->orderBy('id', 'asc');
            }, 
            'items.unit', 
            'items.product' => function($query) {
                $query->select('id', 'name', 'packing', 'sku', 'hsn_sac_code', 'vendor_id');
            }, 
            'cfaStockistStocks', 
            'cfaStockistStocks.product' => function($query) {
                $query->select('id', 'name', 'packing', 'sku', 'hsn_sac_code', 'vendor_id');
            },
            'cfaStockistStocks.cfaDistributorStock',
            'cfaStockistStocks.cfaDistributorStock.purchaseEntry' => function($query) {
                $query->select('id', 'product_id', 'vendor_id', 'batch', 'expiry', 'mrp', 'pts', 'ptr', 'dis', 'discount', 'tax', 'scheme_enabled', 'total_quantity', 'free_quantity');
            },
            'cfaStockistStocks.cfaDistributorStock.purchaseEntry.vendor' => function($query) {
                $query->select('id', 'primary_name', 'company_name');
            },
            'cfaStockistStocks.cfaDistributorStock.purchaseEntry.product' => function($query) {
                $query->select('id', 'name', 'packing', 'sku', 'hsn_sac_code', 'vendor_id');
            },
            'cfaStockistStocks.cfaDistributorStock',
            'cfaStockistStocks.cfaStockist.area',
            'cfaStockistStocks.cfaStockist',
            'cfaStockistStocks.cfaDistributor',
            'address'
        ])->findOrFail($id)->withCustomFields();
        
        // Ensure items are properly ordered
        if ($this->invoice->items) {
            $this->invoice->items = $this->invoice->items->unique('id')->sortBy(function($item) {
                return $item->field_order ?? ($item->id ?? 999999);
            })->values();
        }
        
        $this->userId = UserService::getUserId();

        // Do not re-check view_invoices: access is already set above (full CFA, own client, or
        // view_cfa_stockist_invoices). A second check against view_invoices + send_status
        // would wrongly block CFA distributors and staff with stockist-invoice–only view rights.

        $getCustomFieldGroupsWithFields = $this->invoice->getCustomFieldGroupsWithFields();
        if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }

        $this->paidAmount = $this->invoice->getPaidAmount();
        $this->pageTitle = $this->invoice->invoice_number;

        $this->discount = 0;
        if ($this->invoice->discount > 0) {
            if ($this->invoice->discount_type == 'percent') {
                $this->discount = (($this->invoice->discount / 100) * $this->invoice->sub_total);
            } else {
                $this->discount = $this->invoice->discount;
            }
        }

        if ($this->invoice->discount_type == 'percent') {
            $discountAmount = $this->invoice->discount;
            $this->discountType = $discountAmount . '%';
        } else {
            $discountAmount = $this->invoice->discount;
            $this->discountType = currency_format($discountAmount, $this->invoice->currency_id);
        }

        $taxList = array();
        $items = InvoiceItems::whereNotNull('taxes')
            ->where('invoice_id', $this->invoice->id)
            ->get();

        foreach ($items as $item) {
            $taxes = json_decode($item->taxes, true);
            if (!is_array($taxes)) {
                continue;
            }
            
            foreach ($taxes as $tax) {
                $this->tax = InvoiceItems::taxbyid($tax)->first();
                if ($this->tax) {
                    if (!isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {
                        if ($this->invoice->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->invoice->sub_total) * $this->discount) * ($this->tax->rate_percent / 100);
                        } else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $item->amount * ($this->tax->rate_percent / 100);
                        }
                    } else {
                        if ($this->invoice->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->invoice->sub_total) * $this->discount) * ($this->tax->rate_percent / 100));
                        } else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + ($item->amount * ($this->tax->rate_percent / 100));
                        }
                    }
                }
            }
        }

        $this->taxes = $taxList;
        $this->company = $this->invoice->company;
        $this->settings = company();
        $this->invoiceSetting = $this->company->invoiceSetting;
        $this->creditNote = 0;
        $this->firstInvoice = Invoice::where('company_id', company()->id)->orderBy('id', 'desc')->first();
        $this->credentials = PaymentGatewayCredentials::first();
        $this->methods = OfflinePaymentMethod::activeMethod();
        $this->user = $this->invoice->client; // CFA/Distributor who is billing
        $this->payments = Payment::with(['offlineMethod'])->where('invoice_id', $this->invoice->id)->where('status', 'complete')->orderByDesc('paid_on')->get();
        $this->deletePermission = user()->permission('delete_invoices');
        $this->addInvoicesPermission = user()->permission('add_invoices');
        $this->editInvoicesPermission = user()->permission('edit_invoices');
        $this->canEditCfaStockistInvoice = $this->userCanEditCfaStockistInvoice($this->invoice);
        $this->canDeleteCfaStockistInvoice = $this->userCanDeleteCfaStockistInvoice($this->invoice);

        // Get CFA Stockist from stock entries
        $firstStock = $this->invoice->cfaStockistStocks->first();
        $this->cfaStockist = $firstStock ? $firstStock->cfaStockist : null;

        // Check if this is an IGST invoice - same logic as CFA Distributor invoices
        $isIGSTInvoice = false;
        
        // Priority 1: Check invoice_type field
        if (isset($this->invoice->invoice_type) && $this->invoice->invoice_type === 'igst') {
            $isIGSTInvoice = true;
        }
        
        // Priority 2: Check request parameter
        if (!$isIGSTInvoice && (request('type') == 'igst' || request()->has('igst'))) {
            $isIGSTInvoice = true;
            // Update database field if not already set
            if (!isset($this->invoice->invoice_type) || $this->invoice->invoice_type !== 'igst') {
                $this->invoice->invoice_type = 'igst';
                $this->invoice->save();
            }
        }
        
        // Priority 3: Check invoice note for IGST marker
        if (!$isIGSTInvoice && $this->invoice->note && strpos($this->invoice->note, '<!--IGST_INVOICE-->') !== false) {
            $isIGSTInvoice = true;
            // Update database field if not already set
            if (!isset($this->invoice->invoice_type) || $this->invoice->invoice_type !== 'igst') {
                $this->invoice->invoice_type = 'igst';
                $this->invoice->save();
            }
        }
        
        // Priority 4: Check invoice items for IGST tax (if tax name contains IGST)
        if (!$isIGSTInvoice && $this->invoice->items) {
            foreach ($this->invoice->items as $item) {
                if ($item->taxes) {
                    $taxes = [];
                    if (is_string($item->taxes)) {
                        $decoded = json_decode($item->taxes, true);
                        if (is_array($decoded)) {
                            $taxes = $decoded;
                        } elseif (is_numeric($item->taxes)) {
                            $taxes = [(int)$item->taxes];
                        }
                    } elseif (is_array($item->taxes)) {
                        $taxes = $item->taxes;
                    }
                    
                    foreach ($taxes as $taxId) {
                        if (empty($taxId)) continue;
                        $tax = \App\Models\Tax::find($taxId);
                        if ($tax && stripos($tax->tax_name ?? '', 'IGST') !== false) {
                            $isIGSTInvoice = true;
                            // Update database field
                            $this->invoice->invoice_type = 'igst';
                            $this->invoice->save();
                            break 2; // Break out of both loops
                        }
                    }
                }
            }
        }
        
        // Store invoice type in data for view
        $this->invoiceType = $isIGSTInvoice ? 'igst' : '';
        $this->data['invoiceType'] = $this->invoiceType;
        $this->data['isIGSTInvoice'] = $isIGSTInvoice;
        
        // Use pharmaceutical-specific template for CFA/Stockist invoices
        if ($isIGSTInvoice) {
            return view('invoices.cfa-stockist.igst-show', $this->data);
        }
        return view('invoices.cfa-stockist.pharma-show', $this->data);
    }

    public function getCFAStockists(Request $request)
    {
        $cfaDistributorId = $request->cfa_distributor_id;
        
        if (!$cfaDistributorId) {
            return Reply::dataOnly(['status' => 'success', 'data' => '<option value="">-- Select Stockist --</option>']);
        }

        $cfaDistributor = User::where('company_id', company()->id)->findOrFail($cfaDistributorId);
        $cfaStockists = $cfaDistributor->cfaStockists()
            ->where('cfa_stockists.company_id', company()->id)
            ->get();

        $options = '<option value="">-- Select CFA Stockist --</option>';
        foreach ($cfaStockists as $stockist) {
            $displayText = ($stockist->cfa_stockist_id ? $stockist->cfa_stockist_id . ' - ' : '') . $stockist->shopname;
            if ($stockist->fullname) {
                $displayText .= ' - ' . $stockist->fullname;
            }
            $options .= '<option value="' . $stockist->id . '">' . $displayText . '</option>';
        }

        return Reply::dataOnly(['status' => 'success', 'data' => $options]);
    }

    /**
     * Get products from CFA/Distributor's available stock
     */
    public function getProductsFromStock(Request $request)
    {
        $cfaDistributorId = $request->cfa_distributor_id;
        
        if (!$cfaDistributorId) {
            return Reply::dataOnly(['status' => 'success', 'data' => []]);
        }

        $availableStock = $this->cfaDistributorStockForStockistInvoiceProducts((int) $cfaDistributorId);

        // Group by product_id
        $productsGrouped = [];
        foreach ($availableStock as $stock) {
            $productId = $stock->product_id;
            if (!isset($productsGrouped[$productId])) {
                $productsGrouped[$productId] = [
                    'product_id' => $productId,
                    'product_name' => $stock->product->name ?? 'Unknown Product'
                ];
            }
        }

        return Reply::dataOnly(['status' => 'success', 'data' => array_values($productsGrouped)]);
    }

    /**
     * Prefer stock tied to invoices marked delivered; if that yields nothing, use any available rows
     * (strict filter alone often emptied the product dropdown when delivery_status was never set).
     */
    private function cfaDistributorStockForStockistInvoiceProducts(int $cfaDistributorId)
    {
        $loose = function () use ($cfaDistributorId) {
            return CFADistributorStock::with(['product.unit', 'product.vendor'])
                ->where('cfa_distributor_id', $cfaDistributorId)
                ->where('available_quantity', '>', 0)
                ->where('company_id', company()->id);
        };

        try {
            $strict = $loose()
                ->whereHas('invoice', function ($query) {
                    $query->where('delivery_status', 'received');
                })
                ->get();
            if ($strict->isNotEmpty()) {
                return $strict;
            }
        } catch (\Exception $e) {
            \Log::warning('CFA stockist invoice products: strict delivery filter failed', ['error' => $e->getMessage()]);
        }

        return $loose()->get();
    }

    /**
     * Get batches for a product from CFA/Distributor's available stock
     */
    public function getStockBatches(Request $request)
    {
        $productId = $request->product_id;
        $cfaDistributorId = $request->cfa_distributor_id;
        
        if (!$productId || !$cfaDistributorId) {
            return Reply::dataOnly(['status' => 'success', 'data' => []]);
        }

        $stockTable = (new CFADistributorStock)->getTable();

        try {
            $availableStock = CFADistributorStock::with(['product.unit', 'product.vendor'])
                ->join('invoices', 'invoices.id', '=', $stockTable . '.invoice_id')
                ->where($stockTable . '.cfa_distributor_id', $cfaDistributorId)
                ->where($stockTable . '.product_id', $productId)
                ->where($stockTable . '.available_quantity', '>', 0)
                ->where($stockTable . '.company_id', company()->id)
                ->where('invoices.delivery_status', 'received')
                ->select($stockTable . '.*')
                ->orderBy($stockTable . '.created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            \Log::warning('CFA stockist getStockBatches: strict query failed', ['error' => $e->getMessage()]);
            $availableStock = collect();
        }

        if ($availableStock->isEmpty()) {
            $availableStock = CFADistributorStock::with(['product.unit', 'product.vendor'])
                ->where('cfa_distributor_id', $cfaDistributorId)
                ->where('product_id', $productId)
                ->where('available_quantity', '>', 0)
                ->where('company_id', company()->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        $batches = [];
        foreach ($availableStock as $stock) {
            $batch = $stock->batch ?? 'No Batch';
            $batches[] = [
                'cfa_distributor_stock_id' => $stock->id,
                'batch' => $batch,
                'expiry' => $stock->expiry ? $stock->expiry->format('Y-m-d') : null,
                'available_quantity' => $stock->available_quantity,
                'pts' => $stock->pts ?? 0,
                'ptr' => $stock->ptr ?? 0,
                'mrp' => $stock->mrp ?? 0,
                'dis' => $stock->dis ?? 0,
                'created_month' => $stock->created_at ? $stock->created_at->format('F Y') : 'Unknown'
            ];
        }

        return Reply::dataOnly(['status' => 'success', 'data' => $batches]);
    }

    /**
     * Display CFA/Distributor Inventory
     */
    public function indexCFADistributorInventory(CFADistributorInventoryDataTable $dataTable)
    {
        // Admin, accountant, and FSA Executive users have full access
        // Also allow clients (CFA Distributors) to view their own inventory
        if (!PharmaDesignationHelper::hasFullCFAAccess() && !in_array('client', user_roles())) {
            $viewPermission = user()->permission('view_invoices');
            abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
        }

        if (!request()->ajax()) {
            // Load CFA/Distributors for filter (admin only)
            if (PharmaDesignationHelper::hasFullCFAAccess()) {
                $this->cfaDistributors = User::without('session')
                    ->join('role_user', 'role_user.user_id', '=', 'users.id')
                    ->join('roles', 'roles.id', '=', 'role_user.role_id')
                    ->join('client_details', 'users.id', '=', 'client_details.user_id')
                    ->leftJoin('client_categories', 'client_details.category_id', '=', 'client_categories.id')
                    ->leftJoin('client_areas', 'client_details.id', '=', 'client_areas.client_detail_id')
                    ->select('users.id', 'users.name', 'users.email', 'client_details.company_name')
                    ->whereNull('users.is_client_contact')
                    ->where('roles.name', 'client')
                    ->where('users.status', 'active')
                    ->where('users.company_id', company()->id)
                    ->where(function($query) {
                        $query->where(function($q) {
                            $q->whereRaw('LOWER(client_categories.category_name) LIKE ?', ['%cfa%'])
                              ->orWhereRaw('LOWER(client_categories.category_name) LIKE ?', ['%distributor%']);
                        })
                        ->orWhereNotNull('client_areas.area_id');
                    })
                    ->groupBy('users.id', 'users.name', 'users.email', 'client_details.company_name')
                    ->orderBy('client_details.company_name', 'asc')
                    ->get();
            } else {
                $this->cfaDistributors = collect([]);
            }
        }

        $this->pageTitle = __('CFA/Distributor Inventory');
        return $dataTable->render('cfa-distributor-inventory.index', $this->data);
    }

    /**
     * Check if current user can access CFA distributor stock (same logic as index).
     */
    private function canAccessCFADistributorStock(?int $cfaDistributorId): bool
    {
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            return true;
        }
        if (in_array('client', user_roles())) {
            return $cfaDistributorId !== null && (int) $cfaDistributorId === (int) user()->id;
        }
        $viewPermission = user()->permission('view_invoices');
        return in_array($viewPermission, ['all', 'added', 'owned', 'both']);
    }

    /**
     * List batches for a product+distributor (CFA Distributor Inventory detail).
     */
    public function batchesCFADistributorInventory()
    {
        $productId = request('product_id');
        $cfaDistributorId = request('cfa_distributor_id');
        if (!$productId || !$cfaDistributorId) {
            abort(404);
        }
        if (!$this->canAccessCFADistributorStock((int) $cfaDistributorId)) {
            abort_403(__('messages.permissionDenied'));
        }

        $batches = CFADistributorStock::with(['product', 'invoice', 'cfaDistributor.clientDetails'])
            ->where('company_id', company()->id)
            ->where('product_id', $productId)
            ->where('cfa_distributor_id', $cfaDistributorId)
            ->orderBy('batch')
            ->orderBy('expiry')
            ->get();

        $product = $batches->first()->product ?? Product::find($productId);
        $distributor = $batches->first()->cfaDistributor ?? User::with('clientDetails')->find($cfaDistributorId);
        $distributorName = $distributor ? ($distributor->clientDetails->company_name ?? $distributor->name ?? '-') : '-';

        $this->pageTitle = __('app.batches') . ' – ' . ($product->name ?? '-') . ' / ' . $distributorName;
        return view('cfa-distributor-inventory.batches', [
            'batches' => $batches,
            'product' => $product,
            'distributorName' => $distributorName,
            'productId' => $productId,
            'cfaDistributorId' => $cfaDistributorId,
        ] + $this->data);
    }

    /**
     * Edit form for one CFA distributor stock batch (modal content).
     */
    public function editBatchCFADistributorInventory($id)
    {
        $stock = CFADistributorStock::where('company_id', company()->id)->findOrFail($id);
        if (!$this->canAccessCFADistributorStock((int) $stock->cfa_distributor_id)) {
            abort_403(__('messages.permissionDenied'));
        }
        $data = ['batch' => $stock] + $this->data;
        if (request()->ajax() || request()->wantsJson()) {
            $html = view('cfa-distributor-inventory.ajax.edit_batch', $data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => __('app.editBatch')]);
        }
        return view('cfa-distributor-inventory.ajax.edit_batch', $data);
    }

    /**
     * Update one CFA distributor stock batch.
     */
    public function updateBatchCFADistributorInventory(\Illuminate\Http\Request $request, $id)
    {
        $stock = CFADistributorStock::where('company_id', company()->id)->findOrFail($id);
        if (!$this->canAccessCFADistributorStock((int) $stock->cfa_distributor_id)) {
            return Reply::error(__('messages.permissionDenied'));
        }

        $request->validate([
            'batch' => 'nullable|string|max:255',
            'expiry' => 'nullable|date',
            'quantity' => 'required|numeric|min:0',
            'available_quantity' => 'required|numeric|min:0',
            'pts' => 'nullable|numeric|min:0',
            'ptr' => 'nullable|numeric|min:0',
            'mrp' => 'nullable|numeric|min:0',
            'dis' => 'nullable|numeric|min:0',
        ]);

        $available = (float) $request->available_quantity;
        $quantity = (float) $request->quantity;
        if ($available > $quantity) {
            return Reply::error(__('app.availableQuantityCannotExceedTotal'));
        }

        $stock->fill([
            'batch' => $request->batch,
            'expiry' => $request->expiry ?: null,
            'quantity' => $quantity,
            'available_quantity' => $available,
            'pts' => $request->pts ?: null,
            'ptr' => $request->ptr ?: null,
            'mrp' => $request->mrp ?: null,
            'dis' => $request->dis ?: null,
        ]);
        $stock->save();

        if ($request->ajax() || $request->wantsJson()) {
            return Reply::successWithData(__('messages.updateSuccess'), [
                'redirectUrl' => route('cfa-distributor-inventory.batches', [
                    'product_id' => $stock->product_id,
                    'cfa_distributor_id' => $stock->cfa_distributor_id,
                ]),
            ]);
        }
        return redirect()->route('cfa-distributor-inventory.batches', [
            'product_id' => $stock->product_id,
            'cfa_distributor_id' => $stock->cfa_distributor_id,
        ])->with('success', __('messages.updateSuccess'));
    }

    /**
     * Delete one CFA distributor stock batch.
     */
    public function destroyBatchCFADistributorInventory($id)
    {
        $stock = CFADistributorStock::where('company_id', company()->id)->findOrFail($id);
        if (!$this->canAccessCFADistributorStock((int) $stock->cfa_distributor_id)) {
            return Reply::error(__('messages.permissionDenied'));
        }

        $usedByStockist = CFAStockistStock::where('cfa_distributor_stock_id', $id)->exists();
        if ($usedByStockist) {
            return Reply::error(__('app.thisBatchUsedByStockistDeleteWillCascade'));
        }

        $productId = $stock->product_id;
        $cfaDistributorId = $stock->cfa_distributor_id;
        $stock->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return Reply::successWithData(__('messages.deleteSuccess'), [
                'redirectUrl' => route('cfa-distributor-inventory.batches', [
                    'product_id' => $productId,
                    'cfa_distributor_id' => $cfaDistributorId,
                ]),
            ]);
        }
        return redirect()->route('cfa-distributor-inventory.batches', [
            'product_id' => $productId,
            'cfa_distributor_id' => $cfaDistributorId,
        ])->with('success', __('messages.deleteSuccess'));
    }

    /**
     * Check if current user can access CFA stockist stock (same logic as stockist invoices).
     */
    private function canAccessCFAStockistStock(?int $cfaStockistId): bool
    {
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            return true;
        }
        if (in_array('client', user_roles())) {
            return $cfaStockistId !== null && CFAStockist::where('id', $cfaStockistId)
                ->whereHas('cfaDistributors', fn($q) => $q->where('cfa_distributor_id', user()->id))
                ->exists();
        }
        $viewPermission = user()->permission('view_cfa_stockist_invoices');
        return in_array($viewPermission, ['all', 'added', 'owned', 'both']);
    }

    /**
     * Display CFA Stockist Inventory.
     */
    public function indexCFAStockistInventory(CFAStockistInventoryDataTable $dataTable)
    {
        if (!PharmaDesignationHelper::hasFullCFAAccess() && !in_array('client', user_roles())) {
            $viewPermission = user()->permission('view_cfa_stockist_invoices');
            abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
        }

        if (!request()->ajax()) {
            if (PharmaDesignationHelper::hasFullCFAAccess()) {
                $this->cfaStockists = CFAStockist::where('company_id', company()->id)->orderBy('shopname')->get();
                $this->cfaDistributors = User::without('session')
                    ->join('role_user', 'role_user.user_id', '=', 'users.id')
                    ->join('roles', 'roles.id', '=', 'role_user.role_id')
                    ->join('client_details', 'users.id', '=', 'client_details.user_id')
                    ->select('users.id', 'users.name', 'client_details.company_name')
                    ->where('roles.name', 'client')
                    ->where('users.company_id', company()->id)
                    ->groupBy('users.id', 'users.name', 'client_details.company_name')
                    ->orderBy('client_details.company_name')
                    ->get();
            } else {
                $this->cfaStockists = CFAStockist::where('company_id', company()->id)
                    ->whereHas('cfaDistributors', fn($q) => $q->where('cfa_distributor_id', user()->id))
                    ->orderBy('shopname')
                    ->get();
                $this->cfaDistributors = collect([]);
            }
        }

        $this->pageTitle = __('app.cfaStockistInventory');
        return $dataTable->render('cfa-stockist-inventory.index', $this->data);
    }

    /**
     * List batches for a product+stockist (CFA Stockist Inventory detail).
     */
    public function batchesCFAStockistInventory()
    {
        $productId = request('product_id');
        $cfaStockistId = request('cfa_stockist_id');
        if (!$productId || !$cfaStockistId) {
            abort(404);
        }
        if (!$this->canAccessCFAStockistStock((int) $cfaStockistId)) {
            abort_403(__('messages.permissionDenied'));
        }

        $batches = CFAStockistStock::with(['product', 'invoice', 'cfaStockist', 'cfaDistributor.clientDetails'])
            ->where('company_id', company()->id)
            ->where('product_id', $productId)
            ->where('cfa_stockist_id', $cfaStockistId)
            ->orderBy('batch')
            ->orderBy('expiry')
            ->get();

        $product = $batches->first()->product ?? Product::find($productId);
        $stockist = $batches->first()->cfaStockist ?? CFAStockist::find($cfaStockistId);
        $stockistName = $stockist ? ($stockist->shopname ?? $stockist->fullname ?? '-') : '-';

        $this->pageTitle = __('app.batches') . ' – ' . ($product->name ?? '-') . ' / ' . $stockistName;
        return view('cfa-stockist-inventory.batches', [
            'batches' => $batches,
            'product' => $product,
            'stockistName' => $stockistName,
            'productId' => $productId,
            'cfaStockistId' => $cfaStockistId,
        ] + $this->data);
    }

    /**
     * Edit form for one CFA stockist stock batch (modal content).
     */
    public function editBatchCFAStockistInventory($id)
    {
        $stock = CFAStockistStock::where('company_id', company()->id)->findOrFail($id);
        if (!$this->canAccessCFAStockistStock((int) $stock->cfa_stockist_id)) {
            abort_403(__('messages.permissionDenied'));
        }
        $data = ['batch' => $stock] + $this->data;
        if (request()->ajax() || request()->wantsJson()) {
            $html = view('cfa-stockist-inventory.ajax.edit_batch', $data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => __('app.editBatch')]);
        }
        return view('cfa-stockist-inventory.ajax.edit_batch', $data);
    }

    /**
     * Update one CFA stockist stock batch.
     */
    public function updateBatchCFAStockistInventory(\Illuminate\Http\Request $request, $id)
    {
        $stock = CFAStockistStock::where('company_id', company()->id)->findOrFail($id);
        if (!$this->canAccessCFAStockistStock((int) $stock->cfa_stockist_id)) {
            return Reply::error(__('messages.permissionDenied'));
        }

        $request->validate([
            'batch' => 'nullable|string|max:255',
            'expiry' => 'nullable|date',
            'quantity' => 'required|numeric|min:0',
            'pts' => 'nullable|numeric|min:0',
            'ptr' => 'nullable|numeric|min:0',
            'mrp' => 'nullable|numeric|min:0',
            'dis' => 'nullable|numeric|min:0',
        ]);

        $stock->fill([
            'batch' => $request->batch,
            'expiry' => $request->expiry ?: null,
            'quantity' => (float) $request->quantity,
            'pts' => $request->pts ?: null,
            'ptr' => $request->ptr ?: null,
            'mrp' => $request->mrp ?: null,
            'dis' => $request->dis ?: null,
        ]);
        $stock->save();

        if ($request->ajax() || $request->wantsJson()) {
            return Reply::successWithData(__('messages.updateSuccess'), [
                'redirectUrl' => route('cfa-stockist-inventory.batches', [
                    'product_id' => $stock->product_id,
                    'cfa_stockist_id' => $stock->cfa_stockist_id,
                ]),
            ]);
        }
        return redirect()->route('cfa-stockist-inventory.batches', [
            'product_id' => $stock->product_id,
            'cfa_stockist_id' => $stock->cfa_stockist_id,
        ])->with('success', __('messages.updateSuccess'));
    }

    /**
     * Delete one CFA stockist stock batch.
     */
    public function destroyBatchCFAStockistInventory($id)
    {
        $stock = CFAStockistStock::where('company_id', company()->id)->findOrFail($id);
        if (!$this->canAccessCFAStockistStock((int) $stock->cfa_stockist_id)) {
            return Reply::error(__('messages.permissionDenied'));
        }

        $productId = $stock->product_id;
        $cfaStockistId = $stock->cfa_stockist_id;
        $stock->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return Reply::successWithData(__('messages.deleteSuccess'), [
                'redirectUrl' => route('cfa-stockist-inventory.batches', [
                    'product_id' => $productId,
                    'cfa_stockist_id' => $cfaStockistId,
                ]),
            ]);
        }
        return redirect()->route('cfa-stockist-inventory.batches', [
            'product_id' => $productId,
            'cfa_stockist_id' => $cfaStockistId,
        ])->with('success', __('messages.deleteSuccess'));
    }

    /**
     * CFA distributors (client role) may create CFA stockist invoices without add_cfa_stockist_invoices.
     */
    private function userCanAddCfaStockistInvoice(): bool
    {
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            return true;
        }
        $perm = user()->permission('add_cfa_stockist_invoices');
        if (in_array($perm, ['all', 'added'], true)) {
            return true;
        }

        return in_array('client', user_roles(), true);
    }

    /**
     * CFA distributor (client) may edit their own distributor invoices without matrix permission.
     * Staff need edit_cfa_distributor_invoices (scoped by type when invoice is set).
     */
    private function userCanEditCfaDistributorInvoice(?Invoice $invoice = null): bool
    {
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            return true;
        }
        $userId = (int) UserService::getUserId();
        $uid = (int) (user()->id ?? 0);
        $perm = user()->permission('edit_cfa_distributor_invoices');
        if (in_array($perm, ['all', 'added', 'owned', 'both'], true)) {
            if ($invoice === null) {
                return true;
            }
            if ($perm === 'all') {
                return true;
            }
            if ($perm === 'added' && ((int) $invoice->added_by === $userId || (int) $invoice->added_by === $uid)) {
                return true;
            }
            if ($perm === 'owned' && (int) $invoice->client_id === $userId) {
                return true;
            }
            if ($perm === 'both' && ((int) $invoice->client_id === $userId || (int) $invoice->added_by === $userId || (int) $invoice->added_by === $uid)) {
                return true;
            }
        }

        return in_array('client', user_roles(), true)
            && $invoice !== null
            && (int) $invoice->client_id === $uid;
    }

    /**
     * CFA distributor (client) may edit their own stockist invoices without matrix permission.
     * Staff need edit_cfa_stockist_invoices (scoped by type when invoice is set).
     */
    private function userCanEditCfaStockistInvoice(?Invoice $invoice = null): bool
    {
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            return true;
        }
        $userId = (int) UserService::getUserId();
        $uid = (int) (user()->id ?? 0);
        $perm = user()->permission('edit_cfa_stockist_invoices');
        if (in_array($perm, ['all', 'added', 'owned', 'both'], true)) {
            if ($invoice === null) {
                return true;
            }
            if ($perm === 'all') {
                return true;
            }
            if ($perm === 'added' && ((int) $invoice->added_by === $userId || (int) $invoice->added_by === $uid)) {
                return true;
            }
            if ($perm === 'owned' && (int) $invoice->client_id === $userId) {
                return true;
            }
            if ($perm === 'both' && ((int) $invoice->client_id === $userId || (int) $invoice->added_by === $userId || (int) $invoice->added_by === $uid)) {
                return true;
            }
        }

        return in_array('client', user_roles(), true)
            && $invoice !== null
            && (int) $invoice->client_id === $uid;
    }

    /**
     * Delete CFA stockist invoice: own invoice for client CFAs, or delete_cfa_stockist_invoices for staff.
     */
    private function userCanDeleteCfaStockistInvoice(Invoice $invoice): bool
    {
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            return true;
        }
        $userId = (int) UserService::getUserId();
        $uid = (int) (user()->id ?? 0);
        $perm = user()->permission('delete_cfa_stockist_invoices');
        if (in_array($perm, ['all', 'added', 'owned', 'both'], true)) {
            if ($perm === 'all') {
                return true;
            }
            if ($perm === 'added' && ((int) $invoice->added_by === $userId || (int) $invoice->added_by === $uid)) {
                return true;
            }
            if ($perm === 'owned' && (int) $invoice->client_id === $userId) {
                return true;
            }
            if ($perm === 'both' && ((int) $invoice->client_id === $userId || (int) $invoice->added_by === $userId || (int) $invoice->added_by === $uid)) {
                return true;
            }
        }

        return in_array('client', user_roles(), true) && (int) $invoice->client_id === $uid;
    }

    /**
     * Copy CFA/pharma tax-invoice meta fields (IRN, e-way bill, dispatch,
     * customer order ref, classification and address overrides) from the
     * request onto the invoice. Only columns that exist on the table are
     * touched, so this is safe on installs where the related migration
     * has not been applied yet.
     */
    private function applyCfaPharmaTaxInvoiceMeta($invoice, $request): void
    {
        $fields = [
            'irn_number',
            'eway_bill_number',
            'eway_bill_date',
            'dispatch_through',
            'lr_cases',
            'customer_order_reference',
            'tax_invoice_classification',
            'place_of_supply_override',
            'ship_to_address_override',
        ];

        foreach ($fields as $field) {
            if (!Schema::hasColumn('invoices', $field)) {
                continue;
            }

            if (!$request->has($field)) {
                continue;
            }

            $value = $request->input($field);

            if ($field === 'eway_bill_date') {
                $value = $value ? companyToYmd($value) : null;
            } elseif (is_string($value)) {
                $trimmed = trim($value);
                $value = $trimmed === '' ? null : $trimmed;
            }

            $invoice->{$field} = $value;
        }
    }

    /**
     * Resolve the id of the current company's default address, with a
     * fallback to any existing address for the company so invoice save
     * does not fail when `is_default` has not been set yet.
     */
    private function defaultCompanyAddressId()
    {
        $companyId = company() ? company()->id : (user() ? user()->company_id : null);

        if (!$companyId) {
            return null;
        }

        $defaultId = CompanyAddress::where('company_id', $companyId)
            ->where('is_default', 1)
            ->value('id');

        if ($defaultId) {
            return $defaultId;
        }

        return CompanyAddress::where('company_id', $companyId)->value('id');
    }
}
