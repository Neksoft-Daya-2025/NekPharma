<?php

namespace Modules\Purchase\Http\Controllers;

use Carbon\Carbon;
use App\Models\Tax;
use App\Helper\Files;
use App\Helper\Reply;
use App\Models\Product;
use App\Models\Currency;
use App\Models\UnitType;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use App\Models\CompanyAddress;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Modules\Purchase\Entities\PurchaseItem;
use Illuminate\Contracts\Support\Renderable;
use Modules\Purchase\Entities\PurchaseOrder;
use Modules\Purchase\Entities\PurchaseVendor;
use Modules\Purchase\Entities\PurchaseItemTax;
use Modules\Purchase\Entities\PurchaseSetting;
use App\Http\Controllers\AccountBaseController;
use Modules\Purchase\Entities\PurchaseItemImage;
use Modules\Purchase\Events\NewPurchaseOrderEvent;
use Modules\Purchase\Entities\PurchaseOrderHistory;
use Modules\Purchase\Entities\PurchasePaymentHistory;
use Modules\Purchase\DataTables\PurchaseOrderDataTable;
use Modules\Purchase\Http\Requests\PurchaseOrder\StoreRequest;

class PurchaseOrderController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('Bulk Purchase');
        $this->purchaseSetting = PurchaseSetting::first();
        $this->middleware(function ($request, $next) {
            abort_403(!in_array(PurchaseSetting::MODULE_NAME, $this->user->modules));

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */

    public function index(PurchaseOrderDataTable $dataTable)
    {
        $viewPermission = user()->permission('view_purchase_order');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
        $this->pageTitle = __('Bulk Purchase');

        return $dataTable->render('purchase::purchase-order.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $this->pageTitle = __('Bulk Purchase');
        $this->lastOrder = PurchaseOrder::lastOrderNumber() + 1;
        $this->products = Product::where('status', 'active')->get();
        $this->categories = ProductCategory::all();
        $this->taxes = Tax::all();
        $this->units = UnitType::all();
        $this->vendors = PurchaseVendor::all();
        $this->addresses = CompanyAddress::all();
        $this->currencies = Currency::all();
        $this->companyCurrency = Currency::where('id', company()->currency_id)->first();
        $this->vendorID = request()->purchase_order_vendor_id;
        $this->purchaseVendorID = PurchaseVendor::where('id', $this->vendorID)->first();
        
        // Get vendor's address if vendor is selected
        $this->vendorAddress = null;
        if ($this->purchaseVendorID && $this->purchaseVendorID->address_id) {
            $this->vendorAddress = CompanyAddress::find($this->purchaseVendorID->address_id);
        }

        $this->zero = '';

        if (strlen($this->lastOrder) < $this->purchaseSetting->purchase_order_number_digit) {
            $condition = $this->purchaseSetting->purchase_order_number_digit - strlen($this->lastOrder);

            for ($i = 0; $i < $condition; $i++) {
                $this->zero = '0' . $this->zero;
            }
        }

        $this->linkOrderPermission = user()->permission('link_order_bank_account');
        $this->viewBankAccountPermission = user()->permission('view_bankaccount');

        $bankAccounts = BankAccount::where('status', 1)->where('currency_id', company()->currency_id);

        if ($this->viewBankAccountPermission == 'added') {
            $bankAccounts = $bankAccounts->where('added_by', user()->id);
        }

        $bankAccounts = $bankAccounts->get();
        $this->bankDetails = $bankAccounts;
        
        // Always return full page view, not modal
        $this->view = 'purchase::purchase-order.ajax.create';
        $this->invoiceSetting = invoice_setting();

        return view('purchase::purchase-order.create', $this->data);

    }

    /**
     * Store a newly created resource in storage.
     * @param StoreRequest $request
     * @return Renderable
     */
    public function store(StoreRequest $request)
    {
        $redirectUrl = urldecode($request->redirect_url ?? '');

        if ($redirectUrl == '') {
            $redirectUrl = route('purchase-order.index');
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

        $order = new PurchaseOrder();
        $order->purchase_order_number = $request->purchase_order_number;
        $order->currency_id = $request->currency_id ?? null;
        $order->default_currency_id = company()->currency_id;
        $order->exchange_rate = $request->exchange_rate;
        $order->vendor_id = $request->vendor_id ?? null;
        $order->purchase_date = Carbon::createFromFormat($this->company->date_format, $request->purchase_date)->format('Y-m-d');
        $order->expected_delivery_date = Carbon::createFromFormat($this->company->date_format, $request->expected_date)->format('Y-m-d');
        $order->sub_total = round($request->sub_total, 2);
        $order->discount = round($request->discount_value, 2);
        $order->discount_type = $request->discount_type ?? 'percent';
        $order->delivery_status = $request->delivery_status;
        $order->total = round($request->total, 2);
        $order->address_id = $request->address_id;
        $order->note = trim_editor($request->note);
        $order->save();

        // Save purchase items - Support both old format and new enhanced format
        $purchases = $request->purchases ?? [];
        $productIds = $request->product_id ?? [];
        $itemNames = $request->item_name ?? [];
        $quantities = $request->quantity ?? [];
        $costPerItems = $request->cost_per_item ?? [];
        $amounts = $request->amount ?? [];
        $unitIds = $request->unit_id ?? [];
        $hsnSacCodes = $request->hsn_sac_code ?? [];
        $itemSummaries = $request->item_summary ?? [];
        $ppWithoutDiscounts = $request->pp_without_discount ?? [];
        $discountPercents = $request->discount_percent ?? [];
        $purchasePrices = $request->purchase_price ?? [];
        $purchasePriceIncTaxes = $request->purchase_price_inc_tax ?? [];
        $purchaseLineTaxIds = $request->purchase_line_tax_id ?? [];
        $lotNumbers = $request->lot_number ?? [];
        $expiryDates = $request->expiry_date ?? [];
        $mfgDates = $request->mfg_date ?? [];
        $taxes = $request->taxes ?? [];

        // Use enhanced format if available, otherwise fall back to old format
        if (!empty($purchases)) {
            // Enhanced format: purchases[][product_id], purchases[][quantity], etc.
            foreach ($purchases as $index => $purchase) {
                $productId = $purchase['product_id'] ?? ($productIds[$index] ?? null);
                if (!$productId) {
                    continue;
                }

                $product = Product::find($productId);
                if (!$product) {
                    continue;
                }

                $purchaseItem = new PurchaseItem();
                $purchaseItem->purchase_order_id = $order->id;
                $purchaseItem->product_id = $productId;
                $purchaseItem->item_name = $product->name;
                $purchaseItem->item_summary = $purchase['item_summary'] ?? ($itemSummaries[$index] ?? '');
                $purchaseItem->quantity = $purchase['quantity'] ?? ($quantities[$index] ?? 1);
                $purchaseItem->unit_id = $purchase['product_unit_id'] ?? ($unitIds[$index] ?? $product->unit_id);
                $purchaseItem->unit_price = $purchase['purchase_price'] ?? ($purchasePrices[$index] ?? ($costPerItems[$index] ?? 0));
                $purchaseItem->amount = $amounts[$index] ?? ($purchaseItem->quantity * $purchaseItem->unit_price);
                $purchaseItem->hsn_sac_code = $purchase['hsn_sac_code'] ?? ($hsnSacCodes[$index] ?? $product->hsn_sac_code ?? '');
                
                // Enhanced fields
                $purchaseItem->pp_without_discount = $purchase['pp_without_discount'] ?? ($ppWithoutDiscounts[$index] ?? $purchaseItem->unit_price);
                $purchaseItem->discount_percent = $purchase['discount_percent'] ?? ($discountPercents[$index] ?? 0);
                $purchaseItem->purchase_price = $purchase['purchase_price'] ?? ($purchasePrices[$index] ?? $purchaseItem->unit_price);
                $purchaseItem->purchase_price_inc_tax = $purchase['purchase_price_inc_tax'] ?? ($purchasePriceIncTaxes[$index] ?? $purchaseItem->unit_price);
                $purchaseItem->lot_number = $purchase['lot_number'] ?? ($lotNumbers[$index] ?? null);
                
                // Handle dates - expiry date and mfg date
                if (isset($purchase['exp_date']) && !empty($purchase['exp_date'])) {
                    try {
                        $purchaseItem->expiry_date = Carbon::createFromFormat($this->company->date_format, $purchase['exp_date'])->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Try default format if custom format fails
                        $purchaseItem->expiry_date = Carbon::parse($purchase['exp_date'])->format('Y-m-d');
                    }
                } elseif (isset($expiryDates[$index]) && !empty($expiryDates[$index])) {
                    try {
                        $purchaseItem->expiry_date = Carbon::createFromFormat($this->company->date_format, $expiryDates[$index])->format('Y-m-d');
                    } catch (\Exception $e) {
                        $purchaseItem->expiry_date = Carbon::parse($expiryDates[$index])->format('Y-m-d');
                    }
                }
                
                if (isset($purchase['mfg_date']) && !empty($purchase['mfg_date'])) {
                    try {
                        $purchaseItem->mfg_date = Carbon::createFromFormat($this->company->date_format, $purchase['mfg_date'])->format('Y-m-d');
                    } catch (\Exception $e) {
                        $purchaseItem->mfg_date = Carbon::parse($purchase['mfg_date'])->format('Y-m-d');
                    }
                } elseif (isset($mfgDates[$index]) && !empty($mfgDates[$index])) {
                    try {
                        $purchaseItem->mfg_date = Carbon::createFromFormat($this->company->date_format, $mfgDates[$index])->format('Y-m-d');
                    } catch (\Exception $e) {
                        $purchaseItem->mfg_date = Carbon::parse($mfgDates[$index])->format('Y-m-d');
                    }
                }
                
                // Handle purchase_line_tax_id - for multi-select, store first tax or null
                $taxForColumn = null;
                $itemTaxIds = [];
                
                if (isset($purchase['purchase_line_tax_id']) && !empty($purchase['purchase_line_tax_id'])) {
                    // Multi-select: purchase_line_tax_id is already an array
                    if (is_array($purchase['purchase_line_tax_id'])) {
                        $itemTaxIds = $purchase['purchase_line_tax_id'];
                        $taxForColumn = $itemTaxIds[0] ?? null; // Store first tax in column
                    } else {
                        $itemTaxIds[] = $purchase['purchase_line_tax_id'];
                        $taxForColumn = $purchase['purchase_line_tax_id'];
                    }
                } elseif (isset($purchaseLineTaxIds[$index]) && !empty($purchaseLineTaxIds[$index])) {
                    // Check if it's an array (multi-select) or single value
                    if (is_array($purchaseLineTaxIds[$index])) {
                        $itemTaxIds = $purchaseLineTaxIds[$index];
                        $taxForColumn = $itemTaxIds[0] ?? null; // Store first tax in column
                    } else {
                        $itemTaxIds[] = $purchaseLineTaxIds[$index];
                        $taxForColumn = $purchaseLineTaxIds[$index];
                    }
                } elseif (isset($taxes[$index]) && is_array($taxes[$index])) {
                    $itemTaxIds = $taxes[$index];
                    $taxForColumn = $itemTaxIds[0] ?? null; // Store first tax in column
                }
                
                $purchaseItem->purchase_line_tax_id = $taxForColumn;
                $purchaseItem->save();
                
                if (!empty($itemTaxIds)) {
                    $purchaseItem->taxes()->sync($itemTaxIds);
                }
            }
        } else {
            // Old format: item_name[], quantity[], cost_per_item[], etc.
            foreach ($itemNames as $index => $itemName) {
                if (empty($itemName)) {
                    continue;
                }

                $productId = $productIds[$index] ?? null;
                $product = $productId ? Product::find($productId) : null;

                $purchaseItem = new PurchaseItem();
                $purchaseItem->purchase_order_id = $order->id;
                $purchaseItem->product_id = $productId;
                $purchaseItem->item_name = $itemName;
                $purchaseItem->item_summary = $itemSummaries[$index] ?? '';
                $purchaseItem->quantity = $quantities[$index] ?? 1;
                $purchaseItem->unit_id = $unitIds[$index] ?? ($product->unit_id ?? null);
                $purchaseItem->unit_price = $costPerItems[$index] ?? 0;
                $purchaseItem->amount = $amounts[$index] ?? ($purchaseItem->quantity * $purchaseItem->unit_price);
                $purchaseItem->hsn_sac_code = $hsnSacCodes[$index] ?? ($product->hsn_sac_code ?? '');
                
                // Enhanced fields with defaults
                $purchaseItem->pp_without_discount = $ppWithoutDiscounts[$index] ?? $purchaseItem->unit_price;
                $purchaseItem->discount_percent = $discountPercents[$index] ?? 0;
                $purchaseItem->purchase_price = $purchasePrices[$index] ?? $purchaseItem->unit_price;
                $purchaseItem->purchase_price_inc_tax = $purchasePriceIncTaxes[$index] ?? $purchaseItem->unit_price;
                $purchaseItem->lot_number = $lotNumbers[$index] ?? null;
                
                if (isset($expiryDates[$index]) && !empty($expiryDates[$index])) {
                    try {
                        $purchaseItem->expiry_date = Carbon::createFromFormat($this->company->date_format, $expiryDates[$index])->format('Y-m-d');
                    } catch (\Exception $e) {
                        $purchaseItem->expiry_date = Carbon::parse($expiryDates[$index])->format('Y-m-d');
                    }
                }
                
                if (isset($mfgDates[$index]) && !empty($mfgDates[$index])) {
                    try {
                        $purchaseItem->mfg_date = Carbon::createFromFormat($this->company->date_format, $mfgDates[$index])->format('Y-m-d');
                    } catch (\Exception $e) {
                        $purchaseItem->mfg_date = Carbon::parse($mfgDates[$index])->format('Y-m-d');
                    }
                }
                
                // Handle purchase_line_tax_id - for multi-select, store first tax or null
                $taxForColumn = null;
                $itemTaxIds = [];
                
                if (isset($taxes[$index]) && is_array($taxes[$index]) && !empty($taxes[$index])) {
                    $itemTaxIds = $taxes[$index];
                    $taxForColumn = $itemTaxIds[0] ?? null; // Store first tax in column
                } elseif (isset($purchaseLineTaxIds[$index]) && !empty($purchaseLineTaxIds[$index])) {
                    // Check if it's an array (multi-select) or single value
                    if (is_array($purchaseLineTaxIds[$index])) {
                        $itemTaxIds = $purchaseLineTaxIds[$index];
                        $taxForColumn = $itemTaxIds[0] ?? null; // Store first tax in column
                    } else {
                        $itemTaxIds[] = $purchaseLineTaxIds[$index];
                        $taxForColumn = $purchaseLineTaxIds[$index];
                    }
                }
                
                $purchaseItem->purchase_line_tax_id = $taxForColumn;
                $purchaseItem->save();
                
                if (!empty($itemTaxIds)) {
                    $purchaseItem->taxes()->sync($itemTaxIds);
                }
            }
        }

        if ($order->send_status == 1) {
            return Reply::successWithData(__('purchase::messages.orderSentSuccessfully'), ['redirectUrl' => $redirectUrl, 'orderID' => $order->id]);
        }

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => $redirectUrl, 'orderID' => $order->id]);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        $this->order = PurchaseOrder::with('vendor', 'files', 'currency', 'items', 'items.unit', 'items.purchaseItemImage')->findOrFail($id);

        $this->viewPermission = user()->permission('view_purchase_order');
        $this->deletePermission = user()->permission('delete_purchase_order');
        $this->addInvoicesPermission = user()->permission('add_purchase_order');

        abort_403(!(
            $this->viewPermission == 'all'
            || ($this->viewPermission == 'added' && $this->order->added_by == user()->id)
            || ($this->viewPermission == 'owned' && $this->order->send_status)
            || ($this->viewPermission == 'both' && ($this->order->added_by == user()->id))
        ));

        $this->pageTitle = $this->order->purchase_order_number;

        $this->discount = 0;

        if ($this->order->discount > 0) {
            if ($this->order->discount_type == 'percent') {
                $this->discount = (($this->order->discount / 100) * $this->order->sub_total);
            }
            else {
                $this->discount = $this->order->discount;
            }
        }

        $taxList = array();

        $items = PurchaseItem::with('itemTaxes')
            ->where('purchase_order_id', $this->order->id)
            ->get();

        foreach ($items as $item) {
            if (!is_null($item->itemTaxes)) {
                foreach ($item->itemTaxes as $tax) {
                    $this->tax = PurchaseItemTax::taxbyid($tax->tax_id)->first();

                    if (!isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {

                        if ($this->order->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->order->sub_total) * $this->discount) * ($this->tax->rate_percent / 100);

                        }
                        else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $item->amount * ($this->tax->rate_percent / 100);
                        }

                    }
                    else {
                        if ($this->order->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->order->sub_total) * $this->discount) * ($this->tax->rate_percent / 100));

                        }
                        else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + ($item->amount * ($this->tax->rate_percent / 100));
                        }
                    }
                }
            }

        }

        $this->taxes = $taxList;

        $tab = request('tab');

        switch ($tab) {
        case 'files':
            $this->view = 'purchase::purchase-order.ajax.files';
            break;
        case 'history':
            return $this->history($id);
        default:
            $this->view = 'purchase::purchase-order.ajax.overview';
            break;
        }

        $this->settings = company();

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->activeTab = $tab ?: 'overview';

        return view('purchase::purchase-order.show', $this->data);

    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $this->order = PurchaseOrder::with(['items', 'vendor', 'items.itemTaxes'
        ])->findOrFail($id);

        $this->editPermission = user()->permission('edit_purchase_order');

        abort_403(!(
            $this->editPermission == 'all'
            || ($this->editPermission == 'added' && $this->order->added_by == user()->id)
        ));

        $this->pageTitle = $this->order->purchase_order_number;

        $this->currencies = Currency::all();
        $this->units = UnitType::all();
        $this->addresses = CompanyAddress::all();

        $this->taxes = Tax::all();
        $this->products = Product::where('status', 'active')->get();
        $this->categories = ProductCategory::all();
        $this->linkOrderPermission = user()->permission('link_order_bank_account');
        $this->viewBankAccountPermission = user()->permission('view_bankaccount');

        $bankAccounts = BankAccount::where('status', 1)->where('currency_id', $this->order->currency_id);

        if ($this->viewBankAccountPermission == 'added') {
            $bankAccounts = $bankAccounts->where('added_by', user()->id);
        }

        $bankAccounts = $bankAccounts->get();
        $this->bankDetails = $bankAccounts;
        $this->companyCurrency = Currency::where('id', company()->currency_id)->first();
        $this->purchaseSetting = PurchaseSetting::first();
        $this->vendors = PurchaseVendor::all();

        $this->companyAddresses = CompanyAddress::all();

        if (request()->ajax()) {
            $html = view('purchase::purchase-order.ajax.edit', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'purchase::purchase-order.ajax.edit';

        return view('purchase::purchase-order.create', $this->data);

    }

    /**
     * Update the specified resource in storage.
     * @param StoreRequest $request
     * @param int $id
     * @return Renderable
     */
    public function update(StoreRequest $request, $id)
    {
        $items = $request->item_name;
        $cost_per_item = $request->cost_per_item;
        $quantity = $request->quantity;
        $amount = $request->amount;

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

        $order = PurchaseOrder::findOrFail($id);
        $order->purchase_order_number = $request->purchase_order_number;
        $order->currency_id = $request->currency_id ?? null;
        $order->default_currency_id = company()->currency_id;
        $order->exchange_rate = $request->exchange_rate;
        $order->vendor_id = $request->vendor_id ?? null;
        $order->purchase_date = Carbon::createFromFormat($this->company->date_format, $request->purchase_date)->format('Y-m-d');
        $order->expected_delivery_date = Carbon::createFromFormat($this->company->date_format, $request->expected_date)->format('Y-m-d');
        $order->sub_total = round($request->sub_total, 2);
        $order->discount = round($request->discount_value, 2);
        $order->discount_type = $request->discount_type;
        $order->delivery_status = $request->delivery_status;
        $order->total = round($request->total, 2);
        $order->address_id = $request->address_id;
        $order->note = trim_editor($request->note);
        $order->updated_at = now();

        $order->save();

        $redirectUrl = route('purchase-order.index');

        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => $redirectUrl, 'orderID' => $order->id]);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $order = PurchaseOrder::findOrFail($id);
        $this->deletePermission = user()->permission('delete_invoices');
        abort_403(!(
            $this->deletePermission == 'all'
            || ($this->deletePermission == 'added' && $order->added_by == user()->id)
            || ($this->deletePermission == 'both' || $order->added_by == user()->id)
        ));

        PurchaseOrder::destroy($id);

        return Reply::success(__('messages.deleteSuccess'));

    }

    public function getProducts(Request $request)
    {
        $term = $request->get('term', '');
        $products = Product::where('status', 'active')
            ->where(function($query) use ($term) {
                $query->where('name', 'like', '%' . $term . '%')
                    ->orWhere('description', 'like', '%' . $term . '%');
            })
            ->limit(20)
            ->get();

        $results = [];
        foreach ($products as $product) {
            $results[] = [
                'id' => $product->id,
                'value' => $product->id,
                'label' => $product->name . ($product->description ? ' - ' . substr(strip_tags($product->description), 0, 50) : ''),
                'product_id' => $product->id,
                'name' => $product->name,
            ];
        }

        return response()->json($results);
    }

    public function addItem(Request $request)
    {
        $this->items = Product::findOrFail($request->id);
        $this->rowCount = $request->row_count ?? 0;
        $this->currencyId = $request->currencyId ?? company()->currency_id;
        $this->invoiceSetting = invoice_setting();

        $exchangeRate = Currency::findOrFail($this->currencyId);

        // Calculate purchase price with exchange rate
        $purchasePrice = $this->items->purchase_price ?? $this->items->price ?? 0;
        
        if (!is_null($exchangeRate) && !is_null($exchangeRate->exchange_rate)) {
            if ($this->items->total_amount != '') {
                $purchasePrice = floor($this->items->total_amount * $exchangeRate->exchange_rate);
            } else {
                $purchasePrice = floatval($purchasePrice) * floatval($exchangeRate->exchange_rate);
            }
        }

        $this->items->purchase_price = number_format((float)$purchasePrice, 2, '.', '');
        $this->items->price = number_format((float)$purchasePrice, 2, '.', '');
        $this->taxes = Tax::all();
        $this->units = UnitType::all();
        
        // Get last purchase price for this product (if available)
        $lastPurchaseItem = PurchaseItem::where('product_id', $this->items->id)
            ->orderBy('id', 'desc')
            ->first();
        $this->lastPurchaseItem = $lastPurchaseItem;
        
        $view = view('purchase::purchase-order.ajax.add_item_enhanced', $this->data)->render();

        return Reply::dataOnly(['status' => 'success', 'view' => $view]);
    }

    public function deletePurchaseItemImage(Request $request)
    {
        $item = PurchaseItemImage::where('purchase_item_id', $request->purchase_item_id)->first();

        if ($item) {
            Files::deleteFile($item->hashname, PurchaseItemImage::FILE_PATH . '/' . $item->id . '/');
            $item->delete();
        }

        return Reply::success(__('messages.deleteSuccess'));
    }

    public function sendOrder($orderID)
    {
        $order = PurchaseOrder::with('vendor')->findOrFail($orderID);
        $notifyUser = $order->vendor;

        if (isset($notifyUser) && !is_null($notifyUser) && request()->data_type != 'mark_as_send') {
            event(new NewPurchaseOrderEvent($order, $notifyUser));
        }

        $order->send_status = 1;
        $order->save();

        if (request()->data_type == 'mark_as_send') {
            return Reply::success(__('purchase::messages.orderMarkAsSent'));
        }
        else {
            return Reply::success(__('purchase::messages.orderSentSuccessfully'));
        }
    }

    public function download($id)
    {
        $this->invoiceSetting = invoice_setting();
        $this->purchaseSetting = purchase_setting();
        $this->orderSetting = PurchaseSetting::first();

        $this->order = PurchaseOrder::with('vendor', 'items', 'items.unit')->findOrFail($id);

        $this->viewPermission = user()->permission('view_purchase_order');
        $this->company = $this->order->company;

        abort_403(!(
            $this->viewPermission == 'all'
            || ($this->viewPermission == 'added' && $this->order->added_by == user()->id)
        ));

        App::setLocale($this->invoiceSetting->locale ?? 'en');
        Carbon::setLocale($this->invoiceSetting->locale ?? 'en');

        // Download file uploaded
        if ($this->order->file != null) {
            return response()->download(storage_path('app/public/purchase-order-files') . '/' . $this->order->file);
        }

        $pdfOption = $this->domPdfObjectForDownload($id);
        $pdf = $pdfOption['pdf'];
        $filename = $pdfOption['fileName'];

        return request()->view ? $pdf->stream($filename . '.pdf') : $pdf->download($filename . '.pdf');
    }

    public function domPdfObjectForConsoleDownload($id)
    {
        $this->order = PurchaseOrder::with('items', 'items.unit')->findOrFail($id);

        if ($this->order->discount > 0) {
            if ($this->order->discount_type == 'percent') {
                $this->discount = (($this->order->discount / 100) * $this->order->sub_total);

            }
            else {
                $this->discount = $this->order->discount;
            }
        }
        else {
            $this->discount = 0;
        }

        $taxList = array();

        $items = PurchaseItem::with('itemTaxes')
            ->where('purchase_order_id', $this->order->id)
            ->get();

        foreach ($items as $item) {
            if (!is_null($item->itemTaxes)) {
                foreach ($item->itemTaxes as $tax) {
                    $this->tax = PurchaseItemTax::taxbyid($tax->tax_id)->first();

                    if (!isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {

                        if ($this->order->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->order->sub_total) * $this->discount) * ($this->tax->rate_percent / 100);

                        }
                        else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $item->amount * ($this->tax->rate_percent / 100);
                        }

                    }
                    else {
                        if ($this->order->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->order->sub_total) * $this->discount) * ($this->tax->rate_percent / 100));

                        }
                        else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + ($item->amount * ($this->tax->rate_percent / 100));
                        }
                    }
                }
            }

        }

        $this->taxes = $taxList;

        $this->company = $this->order->company;

        $this->invoiceSetting = $this->company->invoiceSetting;
        $this->defaultAddress = CompanyAddress::where('is_default', 1)->where('company_id', $this->order->company_id)->first();

        $pdf = app('dompdf.wrapper');
        $pdf->setOption('enable_php', true);
        $pdf->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        App::setLocale($this->invoiceSetting->locale ?? 'en');
        Carbon::setLocale($this->invoiceSetting->locale ?? 'en');
        $pdf->loadView('purchase::purchase-order.pdf.order-1', $this->data);

        $dom_pdf = $pdf->getDomPDF();
        $canvas = $dom_pdf->getCanvas();
        $canvas->page_text(530, 820, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 10);

        $filename = $this->order->purchase_order_number;

        return [
            'pdf' => $pdf,
            'fileName' => $filename
        ];
    }

    public function domPdfObjectForDownload($id)
    {
        $this->invoiceSetting = invoice_setting();
        $this->orderSetting = PurchaseSetting::first();

        $this->order = PurchaseOrder::with('items', 'items.unit')->findOrFail($id);

        App::setLocale($this->invoiceSetting->locale ?? 'en');
        Carbon::setLocale($this->invoiceSetting->locale ?? 'en');

        if ($this->order->discount > 0) {
            if ($this->order->discount_type == 'percent') {
                $this->discount = (($this->order->discount / 100) * $this->order->sub_total);

            }
            else {
                $this->discount = $this->order->discount;
            }
        }
        else {
            $this->discount = 0;
        }

        $taxList = array();

        $items = PurchaseItem::with('itemTaxes')
            ->where('purchase_order_id', $this->order->id)
            ->get();

        foreach ($items as $item) {
            if (!is_null($item->itemTaxes)) {
                foreach ($item->itemTaxes as $tax) {
                    $this->tax = PurchaseItemTax::taxbyid($tax->tax_id)->first();

                    if (!isset($taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'])) {

                        if ($this->order->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = ($item->amount - ($item->amount / $this->order->sub_total) * $this->discount) * ($this->tax->rate_percent / 100);

                        }
                        else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $item->amount * ($this->tax->rate_percent / 100);
                        }

                    }
                    else {
                        if ($this->order->calculate_tax == 'after_discount' && $this->discount > 0) {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + (($item->amount - ($item->amount / $this->order->sub_total) * $this->discount) * ($this->tax->rate_percent / 100));

                        }
                        else {
                            $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] = $taxList[$this->tax->tax_name . ': ' . $this->tax->rate_percent . '%'] + ($item->amount * ($this->tax->rate_percent / 100));
                        }
                    }
                }
            }

        }

        $this->taxes = $taxList;

        $this->company = $this->order->company;

        $pdf = app('dompdf.wrapper');
        $pdf->setOption('enable_php', true);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        $pdf->loadView('purchase::purchase-order.pdf.' . $this->invoiceSetting->template, $this->data);
        $filename = $this->order->purchase_order_number;

        return [
            'pdf' => $pdf,
            'fileName' => $filename
        ];
    }

    public function getExchangeRate($id)
    {
        $exchangeRate = Currency::where('id', $id)->pluck('exchange_rate')->toArray();

        return Reply::dataOnly(['status' => 'success', 'data' => $exchangeRate]);
    }

    public function history($id)
    {
        $tab = request('tab');

        $this->activeTab = $tab ?: 'history';

        $orderVariable = PurchaseOrderHistory::where('purchase_order_id', $id)
            ->selectRaw('purchase_vendor_id, purchase_order_id, "null" as purchase_bill_id, "null" as purchase_payment_id, "null" as purchase_credit_id, "null" as purchase_order, "null" as purchase_vendor_notes_id, "null" as bill_date, user_id, "null" as amount, label, details, purchase_order_histories.created_at, "Order" as type');
        $billVariable = DB::table('purchase_bill_histories')
            ->where('purchase_orders.id', $id)
            ->join('purchase_orders', 'purchase_orders.id', 'purchase_bill_histories.purchase_order_id')
            ->selectRaw('purchase_vendor_id, "null" as purchase_order_id, purchase_bill_id, "null" as purchase_payment_id, "null" as purchase_credit_id, purchase_order, "null" as purchase_vendor_notes_id, bill_date, user_id, amount, label, details, purchase_bill_histories.created_at, "bill" as type')
            ->where('purchase_bill_histories.company_id', company()->id);


        $paymentVariable = PurchasePaymentHistory::join('purchase_orders', 'purchase_orders.id', 'purchase_payment_histories.purchase_order_id')
            ->where('purchase_orders.id', $id)
            ->with('user')
            ->selectRaw('purchase_vendor_id, "null" as purchase_order_id, "null" as purchase_bill_id, purchase_payment_id, "null" as purchase_credit_id, purchase_order, "null" as purchase_vendor_notes_id, "null" as bill_date, user_id, amount, label, details, purchase_payment_histories.created_at, "Payment" as type')
            ->where('purchase_payment_histories.company_id', company()->id)
            ->union($orderVariable)
            ->union($billVariable)
            ->orderByDesc('created_at')->get();

        $this->history = $paymentVariable;
        $this->view = 'purchase::purchase-order.ajax.history';

        return view('purchase::purchase-order.show', $this->data);

    }

    public function vendorCurrency(Request $request)
    {
        if (!is_null($request->id)) {
            $PurchaseVendor = PurchaseVendor::with('currency')->findOrFail($request->id);
            
            $vendorAddressId = $PurchaseVendor->address_id ?? null;
            $vendorAddressLocation = null;
            
            if ($vendorAddressId) {
                $address = \App\Models\CompanyAddress::find($vendorAddressId);
                $vendorAddressLocation = $address ? $address->location : null;
            }

            return Reply::dataOnly([
                'data' => $PurchaseVendor->currency,
                'vendor_address_id' => $vendorAddressId,
                'vendor_address_location' => $vendorAddressLocation
            ]);
        }
        else {
            return Reply::dataOnly([
                'data' => null, 
                'vendor_address_id' => null,
                'vendor_address_location' => null
            ]);
        }
    }

    public function changeStatus($id, Request $request)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);
        $purchaseOrder->delivery_status = $request->delivery_status;
        $purchaseOrder->save();

        return Reply::success(__('messages.updateSuccess'));

    }

}
