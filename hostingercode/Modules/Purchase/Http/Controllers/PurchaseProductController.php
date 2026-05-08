<?php

namespace Modules\Purchase\Http\Controllers;

use Carbon\Carbon;
use App\Models\Tax;
use App\Models\Task;
use App\Helper\Files;
use App\Helper\Reply;
use App\Models\UnitType;
use App\Models\OrderCart;
use App\Scopes\ActiveScope;
use App\Models\InvoiceItems;
use App\Models\ProductFiles;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Models\ProductSubCategory;
use Modules\Purchase\Entities\PurchaseVendor;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Response;
use Modules\Purchase\Entities\PurchaseProduct;
use App\Http\Controllers\AccountBaseController;
use App\Models\Product;
use App\Models\User;
use Modules\Purchase\Entities\PurchaseInventory;
use Modules\Purchase\Entities\PurchaseSetting;
use Modules\Purchase\Events\PurchaseInventoryEvent;
use Modules\Purchase\Entities\PurchaseProductHistory;
use Modules\Purchase\Entities\PurchaseStockAdjustment;
use Modules\Purchase\Entities\PurchaseBatchStock;
use Modules\Purchase\DataTables\PurchaseProductsDataTable;
use Modules\Purchase\DataTables\PurchaseProductsDetailDataTable;
use Modules\Purchase\DataTables\PurchaseProductTransaction;
use Modules\Purchase\Entities\PurchaseStockAdjustmentReason;
use Modules\Purchase\Http\Requests\Product\StorePurchaseProductRequest;
use Modules\Purchase\Http\Requests\Product\UpdatePurchaseProductRequest;
use Modules\Purchase\Imports\PurchaseProductImport;
use Modules\Purchase\Jobs\ImportPurchaseProductJob;
use Modules\Purchase\Exports\PurchaseProductSampleExport;
use App\Traits\ImportExcel;
use App\Http\Requests\Admin\Employee\ImportRequest;
use App\Http\Requests\Admin\Employee\ImportProcessRequest;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use App\Models\ProductPurchaseDetail;
use App\Models\SupplierInvoice;

class PurchaseProductController extends AccountBaseController
{
    use ImportExcel;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.products';
        $this->middleware(function ($request, $next) {
            // Allow clients (CFA/Distributor) to access purchase-entries.index for invoice creation
            $isClient = User::isClient(user()->id);
            $isPurchaseEntriesIndex = $request->routeIs('purchase-entries.index');
            
            if ($isClient && $isPurchaseEntriesIndex) {
                // Allow clients to view purchase entries
                return $next($request);
            }
            
            // For all other routes, require purchase module
            abort_403(!in_array(PurchaseSetting::MODULE_NAME, $this->user->modules));

            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(PurchaseProductsDataTable $dataTable)
    {
        $viewPermission = user()->permission('view_product');
        abort_403(!in_array($viewPermission, ['all', 'added']));

        $productDetails = [];
        $productDetails = OrderCart::all();
        $this->productDetails = $productDetails;

        $this->totalProducts = PurchaseProduct::count();
        $this->cartProductCount = OrderCart::where('client_id', user()->id)->count();

        $this->categories = ProductCategory::all();
        $this->subCategories = ProductSubCategory::all();
        $this->unitTypes = UnitType::all();

        return $dataTable->render('purchase::purchase-products.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        $this->pageTitle = __('app.menu.addProducts');
        $this->addPermission = user()->permission('add_product');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $this->taxes = Tax::all();
        $this->categories = ProductCategory::all();
        $this->subCategories = ProductSubCategory::all();
        $productId = request('duplicate_product');

        $this->product = $productId ? PurchaseProduct::findOrFail($productId)->withCustomFields() : null;
        $this->subCategories = ($this->product && !is_null($this->product->sub_category_id)) ? ProductSubCategory::where('category_id', $this->product->category_id)->get() : [];

        $product = new Product();

        if ($product->getCustomFieldGroupsWithFields()) {
            $this->fields = $product->getCustomFieldGroupsWithFields()->fields;
        }

        $this->unit_types = UnitType::all();

        if (request()->ajax()) {
            $html = view('purchase::purchase-products.ajax.create', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'purchase::purchase-products.ajax.create';

        return view('purchase::purchase-products.create', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(StorePurchaseProductRequest $request)
    {
        $this->addPermission = user()->permission('add_product');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $product = new PurchaseProduct();
        $product->name = $request->name;
        $product->taxes = $request->tax ? json_encode($request->tax) : null;
        $product->hsn_sac_code = $request->hsn_sac_code;
        $product->unit_id = $request->unit_type ?: null;
        $product->description = trim_editor($request->description);
        $product->allow_purchase = false; // Removed from form, default to false
        $product->downloadable = false; // Removed from form, default to false
        $product->category_id = ($request->category_id) ?: null;
        $product->sub_category_id = ($request->sub_category_id) ?: null;
        $product->sku = $request->sku;
        $product->type = $request->type;
        $product->price = '0'; // Selling price removed from form, set default to 0
        $product->vendor_id = $request->vendor_id ?: null;
        $product->packing = $request->packing ?: null;
        $product->ptr = $request->ptr ?: null;
        $product->pts = $request->pts ?: null;
        
        // Combine scheme_quantity and scheme_free into scheme format (e.g., "10+1")
        $scheme = null;
        if ($request->scheme_quantity || $request->scheme_free) {
            $schemeQuantity = $request->scheme_quantity ?? 0;
            $schemeFree = $request->scheme_free ?? 0;
            if ($schemeQuantity > 0 || $schemeFree > 0) {
                $scheme = $schemeQuantity . '+' . $schemeFree;
            }
        } elseif ($request->scheme) {
            // Fallback to direct scheme field if provided
            $scheme = $request->scheme;
        }
        $product->scheme = $scheme;
        
        $product->discount = $request->discount ?: null;
        $product->discount_type = $request->discount_type ?? 'flat';
        
        // Calculate total: MRP - Discount (flat or percentage)
        $mrp = $request->purchase_price ?? 0;
        $discount = $request->discount ?? 0;
        $discountType = $request->discount_type ?? 'flat';
        
        $discountAmount = 0;
        if ($discountType === 'percentage') {
            $discountAmount = ($mrp * $discount) / 100;
        } else {
            $discountAmount = $discount;
        }
        
        $product->total = max(0, $mrp - $discountAmount);
        
        // MRP (purchase_price) is always required now
        $product->purchase_information = '1';
        $product->purchase_price = $request->purchase_price ?: '0';

        if (!is_null($request->track_inventory)) {
            $product->track_inventory = $request->track_inventory;
            $product->opening_stock = $request->opening_stock ?: null;
        }
        else {
            $product->track_inventory = '0';
            $product->opening_stock = null;
        }

        if (request()->hasFile('downloadable_file') && request()->downloadable == 'true') {
            Files::deleteFile($product->downloadable_file, ProductFiles::FILE_PATH);
            $product->downloadable_file = Files::uploadLocalOrS3(request()->downloadable_file, ProductFiles::FILE_PATH);
        }

        $product->save();

        if (!is_null($request->track_inventory)) {
            $addStock = PurchaseStockAdjustment::where('product_id', $product->id)->first();

            if (!$addStock) {
                $inventory = new PurchaseInventory();

                $addStock = new PurchaseStockAdjustment();
                $addStock->product_id = $product->id;
            }
            else {
                $inventory = PurchaseInventory::where('id', $addStock->inventory_id)->first();
            }

            $inventory->date = Carbon::today()->format('Y-m-d');
            $inventory->type = (!is_null($request->opening_stock)) ? 'quantity' : 'value';
            $inventory->reason_id = null;
            $inventory->save();

            $addStock->inventory_id = $inventory->id;
            $addStock->reason_id = null;
            $addStock->date = Carbon::today()->format('Y-m-d');
            $addStock->type = (!is_null($request->opening_stock)) ? 'quantity' : 'value';
            $addStock->net_quantity = $request->opening_stock ?: null;
            $addStock->changed_value = $request->rate_per_unit ?: null;
            $addStock->status = 'converted';
            $addStock->save();
        }

        // To add custom fields data
        if ($request->custom_fields_data) {
            $productData = Product::find($product->id);
            $productData->updateCustomFieldData($request->custom_fields_data);
        }

        $redirectUrl = urldecode($request->redirect_url ?? '');

        if ($redirectUrl == '') {
            $redirectUrl = route('purchase-products.index');
        }

        if ($request->add_more == 'true') {
            $html = $this->create();

            return Reply::successWithData(__('messages.recordSaved'), ['html' => $html, 'add_more' => true, 'productID' => $product->id, 'defaultImage' => $request->default_image ?? 0]);
        }

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => $redirectUrl, 'productID' => $product->id, 'defaultImage' => $request->default_image ?? 0]);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        $this->addPermission = user()->permission('add_product');
        $this->viewPermission = user()->permission('view_product');
        $this->deletePermission = user()->permission('delete_product');
        $this->editInventoryPermission = user()->permission('edit_product');
        abort_403(!($this->viewPermission == 'all' || ($this->viewPermission == 'added' && $this->product->added_by == user()->id)));

        $this->product = PurchaseProduct::with(['category', 'subCategory'])->findOrFail($id);
        $this->inventory = PurchaseStockAdjustment::where('product_id', $id)->first();
        $this->taxes = Tax::withTrashed()->get();
        $this->pageTitle = $this->product->name;

        $this->taxValue = '';
        $taxes = [];

        foreach ($this->taxes as $tax) {
            if ($this->product && isset($this->product->taxes) && array_search($tax->id, json_decode($this->product->taxes)) !== false) {
                $taxes[] = $tax->tax_name . ' : ' . $tax->rate_percent . '%';
            }
        }

        $this->taxValue = implode(', ', $taxes);

        $this->task = Task::first();

        $this->productData = Product::find($id)->withCustomFields();

        $getCustomFieldGroupsWithFields = $this->productData->getCustomFieldGroupsWithFields();

        if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }


        $this->view = 'purchase::purchase-products.ajax.overview';

        $tab = request('tab');

        switch ($tab) {

        case 'files':
            $this->view = 'purchase::purchase-products.ajax.files';
            break;
        case 'history':
            $this->history = PurchaseProductHistory::where('purchase_product_id', $id)->orderByDesc('id')->get();
            $this->view = 'purchase::purchase-products.ajax.history';
            break;
        case 'transactions':
            return $this->transactions();
        default:
            $this->view = 'purchase::purchase-products.ajax.overview';
            break;
        }

        $this->commitedStock = InvoiceItems::whereHas('invoice', function ($invoiceQuery) {
            $invoiceQuery->where('status', 'unpaid');
        })->where('product_id', $id)->sum('quantity');

        $this->batchStocks = PurchaseBatchStock::where('product_id', $id)
            ->where('company_id', company()->id)
            ->where('quantity', '>', 0)
            ->orderByRaw('expiry IS NULL, expiry ASC')
            ->get();

        $this->activeTab = $tab ?: 'overview';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('purchase::purchase-products.show', $this->data);
    }

    public function transactions()
    {
        $this->viewPermission = user()->permission('view_product');
        abort_403(!($this->viewPermission == 'all' || ($this->viewPermission == 'added' && $this->product->added_by == user()->id)));

        $dataTable = new PurchaseProductTransaction();

        $tab = request('tab');
        $this->activeTab = $tab ?: 'transactions';
        $this->view = 'purchase::purchase-products.ajax.transactions';

        return $dataTable->render('purchase::purchase-products.show', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $this->product = PurchaseProduct::with('orderItem.purchaseOrder.purchaseBill')->findOrFail($id);

        foreach ($this->product->orderItem as $orderItem) {
            if ($orderItem->purchaseOrder->purchaseBill) {
                $this->trackInventory = 'disable';
            }
        }

        $invoiceItems = InvoiceItems::where('product_id', $this->product->id)->get();

        if ($invoiceItems->isNotEmpty()) {
            $this->trackInventory = 'disable';
        }

        $this->editPermission = user()->permission('edit_product');
        abort_403(!($this->editPermission == 'all' || ($this->editPermission == 'added' && $this->product->added_by == user()->id)));

        $this->taxes = Tax::all();
        $this->categories = ProductCategory::all();
        $this->unit_types = UnitType::all();
        $this->vendors = PurchaseVendor::where('company_id', company()->id)->get();
        $this->subCategories = !is_null($this->product->sub_category_id) ? ProductSubCategory::where('category_id', $this->product->category_id)->get() : [];
        $this->pageTitle = __('app.update') . ' ' . __('app.menu.products');

        $images = [];

        if (isset($this->product) && isset($this->product->files)) {
            foreach ($this->product->files as $file) {
                $image['id'] = $file->id;
                $image['name'] = $file->filename;
                $image['hashname'] = $file->hashname;
                $image['size'] = $file->size;
                $image['file_url'] = $file->file_url;
                $images[] = $image;
            }
        }

        $this->images = json_encode($images);

        $this->productData = Product::find($id)->withCustomFields();

        $getCustomFieldGroupsWithFields = $this->productData->getCustomFieldGroupsWithFields();

        if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }

        // Calculate current stock from PurchaseStockAdjustment.net_quantity
        // This reflects: Purchase Entries (increase) - CFA Distributor Invoices (decrease)
        $stockAdjustment = PurchaseStockAdjustment::where('product_id', $id)
            ->where('company_id', company()->id)
            ->first();
        
        if ($stockAdjustment && $stockAdjustment->net_quantity !== null) {
            // Use net_quantity from PurchaseStockAdjustment (already accounts for purchases and CFA invoices)
            $this->currentStock = (float)$stockAdjustment->net_quantity;
        } else {
            // Fallback: Calculate from purchase entries if PurchaseStockAdjustment doesn't exist
            // This should only happen for products that haven't had any stock adjustments yet
            $entries = ProductPurchaseDetail::where('product_id', $id)
                ->whereHas('product', function($q) {
                    $q->where('company_id', company()->id);
                })
                ->get();
            
            $totalPurchaseEntries = $entries->sum(function($entry) {
                return $entry->total_quantity ?? $entry->quantity ?? 0;
            });
            
            $this->currentStock = (float)($totalPurchaseEntries ?? 0);
        }
        
        // Log for debugging
        \Log::info('Current Stock Calculation', [
            'product_id' => $id,
            'current_stock' => $this->currentStock,
            'from_purchase_stock_adjustment' => !empty($stockAdjustment),
            'company_id' => company()->id
        ]);

        // Batch-wise stock for display
        $this->batchStocks = PurchaseBatchStock::where('product_id', $id)
            ->where('company_id', company()->id)
            ->where('quantity', '>', 0)
            ->orderByRaw('expiry IS NULL, expiry ASC')
            ->get();

        if (request()->ajax()) {
            $html = view('purchase::purchase-products.ajax.edit', $this->data)->render();

            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        $this->view = 'purchase::purchase-products.ajax.edit';

        return view('purchase::purchase-products.create', $this->data);
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(UpdatePurchaseProductRequest $request, $id)
    {
        $product = PurchaseProduct::findOrFail($id);
        $this->editPermission = user()->permission('edit_product');
        abort_403(!($this->editPermission == 'all' || ($this->editPermission == 'added' && $product->added_by == user()->id)));

        $product->name = $request->name;
        $product->taxes = $request->tax ? json_encode($request->tax) : null;
        $product->hsn_sac_code = $request->hsn_sac_code;
        $product->unit_id = $request->unit_type ?: null;
        $product->description = trim_editor($request->description);
        $product->allow_purchase = false; // Removed from form, default to false
        $product->downloadable = false; // Removed from form, default to false
        $product->category_id = ($request->category_id) ? $request->category_id : null;
        $product->sub_category_id = ($request->sub_category_id) ? $request->sub_category_id : null;
        $product->sku = $request->sku;
        $product->type = $request->type;
        $product->price = '0'; // Selling price removed from form, set default to 0
        $product->vendor_id = $request->vendor_id ?: null;
        $product->packing = $request->packing ?: null;
        $product->ptr = $request->ptr ?: null;
        $product->pts = $request->pts ?: null;
        // Combine scheme_quantity and scheme_free into scheme format (e.g., "10+1")
        $scheme = null;
        if ($request->scheme_quantity || $request->scheme_free) {
            $schemeQuantity = $request->scheme_quantity ?? 0;
            $schemeFree = $request->scheme_free ?? 0;
            if ($schemeQuantity > 0 || $schemeFree > 0) {
                $scheme = $schemeQuantity . '+' . $schemeFree;
            }
        } elseif ($request->scheme) {
            // Fallback to direct scheme field if provided
            $scheme = $request->scheme;
        }
        $product->scheme = $scheme;
        $product->discount = $request->discount ?: null;
        $product->discount_type = $request->discount_type ?? 'flat';
        
        // Calculate total: MRP - Discount (flat or percentage)
        $mrp = $request->purchase_price ?? 0;
        $discount = $request->discount ?? 0;
        $discountType = $request->discount_type ?? 'flat';
        
        $discountAmount = 0;
        if ($discountType === 'percentage') {
            $discountAmount = ($mrp * $discount) / 100;
        } else {
            $discountAmount = $discount;
        }
        
        $product->total = max(0, $mrp - $discountAmount);

        // MRP (purchase_price) is always required now
        $product->purchase_information = '1';
        $product->purchase_price = $request->purchase_price ?: '0';

        if (!is_null($request->track_inventory)) {
            $product->track_inventory = $request->track_inventory;
            $product->opening_stock = $request->opening_stock ?: null;
        } else {
            $product->track_inventory = '0';
            $product->opening_stock = null;
        }

        if (request()->hasFile('downloadable_file') && request()->downloadable == 'true') {
            Files::deleteFile($product->downloadable_file, ProductFiles::FILE_PATH);
            $product->downloadable_file = Files::uploadLocalOrS3(request()->downloadable_file, ProductFiles::FILE_PATH);
        }
        elseif (request()->downloadable == 'true' && $product->downloadable_file == null) {
            $product->downloadable = false;
        }

        if (!request()->hasFile('file')) {
            $product->default_image = request()->default_image;
        }

        $product->save();

        if (!is_null($request->track_inventory)) {
            $addStock = PurchaseStockAdjustment::where('product_id', $product->id)->first();

            if (!$addStock) {
                $inventory = new PurchaseInventory();

                $addStock = new PurchaseStockAdjustment();
                $addStock->product_id = $product->id;
            }
            else {
                $inventory = PurchaseInventory::where('id', $addStock->inventory_id)->first();
            }

            $inventory->date = Carbon::today()->format('Y-m-d');
            $inventory->type = (!is_null($request->opening_stock)) ? 'quantity' : 'value';
            $inventory->reason_id = null;
            $inventory->save();

            $addStock->inventory_id = $inventory->id;
            $addStock->reason_id = null;
            $addStock->date = Carbon::today()->format('Y-m-d');
            $addStock->type = (!is_null($request->opening_stock)) ? 'quantity' : 'value';
            $addStock->net_quantity = $request->opening_stock ?: null;
            $addStock->changed_value = $request->rate_per_unit ?: null;
            $addStock->status = 'converted';
            $addStock->save();
        }

        // To add custom fields data
        if ($request->custom_fields_data) {
            $productData = Product::find($product->id);
            $productData->updateCustomFieldData($request->custom_fields_data);
        }

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('purchase-products.index'), 'productID' => $product->id, 'defaultImage' => $request->default_image ?? 0]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $product = PurchaseProduct::findOrFail($id);
        $this->deletePermission = user()->permission('delete_product');
        abort_403(!($this->deletePermission == 'all' || ($this->deletePermission == 'added' && $product->added_by == user()->id)));

        try {
            // Delete stock adjustments and related inventory
            $stocks = PurchaseStockAdjustment::where('product_id', $product->id)->get();

            foreach ($stocks as $item) {
                if ($item->inventory_id) {
                    $inventory = PurchaseInventory::where('id', $item->inventory_id)->first();
                    if ($inventory) {
                        $inventory->delete();
                    }
                }
                $item->delete();
            }

            // Delete product files
            $product->files()->each(function ($file) {
                $file->delete();
            });

            // Delete the product
            $product->delete();

            return Reply::successWithData(__('messages.deleteSuccess'), ['redirectUrl' => route('purchase-products.index')]);
            
        } catch (\Exception $e) {
            \Log::error('Product deletion error: ' . $e->getMessage());
            return Reply::error('Failed to delete product: ' . $e->getMessage());
        }
    }

    public function storeImages(Request $request)
    {
        if ($request->hasFile('file')) {

            $defaultImage = null;

            foreach ($request->file as $fileData) {
                $file = new ProductFiles();
                $file->product_id = $request->product_id;

                $filename = Files::uploadLocalOrS3($fileData, ProductFiles::FILE_PATH);

                $file->filename = $fileData->getClientOriginalName();
                $file->hashname = $filename;
                $file->size = $fileData->getSize();
                $file->created_at = now();
                $file->save();

                if ($fileData->getClientOriginalName() == $request->default_image) {
                    $defaultImage = $filename;
                }
            }

            if ($request->default_image != 0) {
                $product = PurchaseProduct::findOrFail($request->product_id);
                $product->default_image = $defaultImage;
                $product->save();
            }
        }

        return Reply::success(__('messages.fileUploaded'));
    }

    public function changeStatus(Request $request)
    {
        $this->editPermission = user()->permission('edit_product');
        abort_403(!($this->editPermission == 'all' || ($this->editPermission == 'added' && $this->product->added_by == user()->id)));

        $expense = PurchaseProduct::findOrFail($request->productId);
        $expense->status = $request->status;
        $expense->save();

        return Reply::success(__('messages.updateSuccess'));
    }

    public function applyQuickAction(Request $request)
    {
        // Sanitize row_ids: exclude "on" (select-all checkbox value) and non-numeric/zero values
        $rowIds = array_values(array_filter(array_map('intval', explode(',', $request->row_ids ?? '')), function ($id) {
            return $id > 0;
        }));
        if (empty($rowIds)) {
            return Reply::error(__('validation.selectAtLeastOne'));
        }
        $request->merge(['row_ids' => implode(',', $rowIds)]);

        switch ($request->action_type) {
        case 'delete':
            $this->deleteRecords($request);

            return Reply::success(__('messages.deleteSuccess'));
        case 'change-status':
            abort_403(user()->permission('edit_product') != 'all');

            PurchaseProduct::withoutGlobalScope(ActiveScope::class)->whereIn('id', $rowIds)->update(['status' => $request->product_status]);

            return Reply::success(__('messages.updateSuccess'));
        case 'change-purchase':
            abort_403(user()->permission('edit_product') != 'all');

            PurchaseProduct::whereIn('id', $rowIds)->update(['allow_purchase' => $request->status]);

            return Reply::success(__('messages.updateSuccess'));
        default:
            return Reply::error(__('messages.selectAction'));
        }
    }

    protected function deleteRecords($request)
    {
        abort_403(user()->permission('delete_product') != 'all');

        $rowIds = array_filter(array_map('intval', explode(',', $request->row_ids ?? '')));
        if (empty($rowIds)) {
            return;
        }

        $products = PurchaseProduct::whereIn('id', $rowIds)->get();

        foreach ($products as $product) {
            $product->files()->each(function ($file) {
                $file->delete();
            });

            $stocks = PurchaseStockAdjustment::where('product_id', $product->id)->get();

            foreach ($stocks as $item) {
                if (!empty($item->inventory_id)) {
                    $inventory = PurchaseInventory::where('id', $item->inventory_id)->first();
                    if ($inventory) {
                        $inventory->delete();
                    }
                }
                $item->delete();
            }

            $product->delete();
        }
    }

    public function layout(Request $request)
    {
        $this->viewPermission = user()->permission('view_product');
        $this->deletePermission = user()->permission('delete_product');
        abort_403(!in_array($this->viewPermission, ['all', 'added']));

        $this->product = PurchaseProduct::with('files')->findOrFail($request->id);

        $layout = $request->layout == 'listview' ? 'purchase::purchase-products.product-files.ajax-list' : 'purchase::purchase-products.product-files.thumbnail-list';

        $view = view($layout, $this->data)->render();

        return Reply::dataOnly(['status' => 'success', 'html' => $view]);
    }

    public function addImages()
    {
        $addPermission = user()->permission('add_product');
        abort_403(!in_array($addPermission, ['all', 'added']));

        $this->productId = request()->id;

        return view('purchase::purchase-products.product-files.create', $this->data);
    }

    public function adjustInventory()
    {
        // Use edit_product permission (same as what we check in the DataTable)
        $editPermission = user()->permission('edit_product');
        abort_403(!in_array($editPermission, ['all', 'added']));

        $productId = request()->id;
        $product = PurchaseProduct::with('unit')->where('id', request()->id)->first();

        // Check if product exists
        if (!$product) {
            abort(404, 'Product not found');
        }

        $adjustment = PurchaseStockAdjustment::where('product_id', request()->id)->first();
        $reasons = PurchaseStockAdjustmentReason::all();

        return view('purchase::purchase-products.ajax.update_inventory', compact('productId', 'product', 'adjustment', 'reasons'));
    }

    public function updateInventory(Request $request)
    {
        $addPermission = user()->permission('edit_inventory');
        abort_403(!in_array($addPermission, ['all', 'added']));

        $updateStock = PurchaseStockAdjustment::where('product_id', $request->product_id)->first();

        if (!$updateStock) {
            $inventory = new PurchaseInventory();

            $updateStock = new PurchaseStockAdjustment();
            $updateStock->product_id = $request->product_id;
        }
        else {
            $inventory = PurchaseInventory::where('id', $updateStock->inventory_id)->first();
        }

        $inventory->date = Carbon::parse($request->date)->format('Y-m-d');
        $inventory->type = $request->type;
        $inventory->reason_id = null;
        $inventory->save();

        $updateStock->inventory_id = $inventory->id;
        $updateStock->reason_id = $request->reason_id;
        $updateStock->date = Carbon::parse($request->date)->format('Y-m-d');
        $updateStock->reference_number = $request->reference_number;
        $updateStock->type = $request->type;
        $updateStock->description = $request->description;

        if ($request->type == 'quantity') {
            $updateStock->net_quantity = $request->quantity_on_hand;
            $updateStock->quantity_adjustment = $request->quantity_adjusted;
            $updateStock->changed_value = $request->cost_price;
        }
        else {
            $updateStock->changed_value = $request->changed_value;
            $updateStock->adjusted_value = $request->adjusted_value;
        }

        $updateStock->status = 'converted';
        $updateStock->save();

        $product = PurchaseProduct::findOrFail($request->product_id);
        $product->purchase_price = $updateStock->changed_value;
        $product->save();

        $productID = ($request->product_id);

        $products = explode(',', $productID);

        $company = company();

        event(new PurchaseInventoryEvent($inventory, $products, $company));

        return Reply::success(__('messages.recordSaved'));
    }

    public function allPurchaseProductOption()
    {
        $products = PurchaseProduct::whereNotNull('opening_stock')->get();

        $option = '';

        foreach ($products as $item) {
            $option .= '<option data-content="' . $item->name . '" value="' . $item->id . '"> ' . $item->name . '</option>';
        }

        return Reply::dataOnly(['products' => $option]);
    }

    /**
     * Show the import form
     *
     * @return \Illuminate\Http\Response
     */
    public function importProduct()
    {
        $this->pageTitle = __('app.importExcel') . ' ' . __('app.menu.products');

        $this->addPermission = user()->permission('add_product');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $this->view = 'purchase::purchase-products.ajax.import';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('purchase::purchase-products.import', $this->data);
    }

    /**
     * Process the import file upload
     *
     * @param ImportRequest $request
     * @return \Illuminate\Http\Response
     */
    public function importStore(ImportRequest $request)
    {
        try {
            // Direct import without mapping step
            $this->importClassName = 'PurchaseProductImport';
            $uploadedFile = Files::upload($request->import_file, Files::IMPORT_FOLDER);
            $filePath = public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $uploadedFile);

            if (!file_exists($filePath)) {
                return Reply::error('File not found after upload');
            }

            $importInstance = new PurchaseProductImport;
            Excel::import($importInstance, $filePath);
            $excelData = $importInstance->getProcessedData();
            
            // Ensure we have data - if empty, try reading directly
            if (empty($excelData) || !is_array($excelData)) {
                // Re-read the file using the import instance
                $importInstance2 = new PurchaseProductImport;
                Excel::import($importInstance2, $filePath);
                $excelData = $importInstance2->getProcessedData();
            }
            
            if (!is_array($excelData) || empty($excelData)) {
                Files::deleteFile($uploadedFile, Files::IMPORT_FOLDER);
                return Reply::error('No data found in the file');
            }
            
            if ($request->has('heading')) {
                array_shift($excelData);
            }

            // Check if data is empty after removing header
            $isDataNull = true;
            foreach ($excelData as $rowitem) {
                if (is_array($rowitem) && array_filter($rowitem)) {
                    $isDataNull = false;
                    break;
                }
            }

            if ($isDataNull || empty($excelData)) {
                Files::deleteFile($uploadedFile, Files::IMPORT_FOLDER);
                return Reply::error(__('messages.abortAction'));
            }

            // Auto-map columns based on headers
            $columns = array();
            $hasHeading = $request->has('heading');
            
            if ($hasHeading) {
                try {
                    $headingData = (new HeadingRowImport)->toArray($filePath);
                    if (isset($headingData[0][0]) && is_array($headingData[0][0])) {
                        $heading = $headingData[0][0];
                    } else {
                        $heading = [];
                    }
                } catch (\Exception $e) {
                    $heading = [];
                }
                
                if (!empty($heading)) {
                    $importColumns = PurchaseProductImport::fields();
                    
                    // Normalize headings for matching
                    $normalizedHeadings = array_map(function($h) {
                        return strtolower(trim(preg_replace('/[^a-z0-9]/', '', (string)$h)));
                    }, $heading);
                    
                    // Create auto-mapping
                    foreach ($heading as $index => $headingValue) {
                        if (!isset($normalizedHeadings[$index])) continue;
                        
                        $normalizedHeading = $normalizedHeadings[$index];
                        
                        foreach ($importColumns as $column) {
                            $columnId = strtolower(trim(preg_replace('/[^a-z0-9]/', '', $column['id'])));
                            $columnName = strtolower(trim(preg_replace('/[^a-z0-9]/', '', $column['name'])));
                            
                            if ($normalizedHeading === $columnId || 
                                $normalizedHeading === $columnName ||
                                strpos($normalizedHeading, $columnId) !== false ||
                                strpos($normalizedHeading, $columnName) !== false ||
                                strpos($columnId, $normalizedHeading) !== false ||
                                strpos($columnName, $normalizedHeading) !== false) {
                                $columns[$index] = $column['id'];
                                break;
                            }
                        }
                    }
                }
            }
            
            // If no columns mapped, use positional mapping (first 3 columns)
            if (empty($columns)) {
                $importColumns = PurchaseProductImport::fields();
                foreach ($importColumns as $index => $column) {
                    if ($index < 3) { // Only map first 3 mandatory columns
                        $columns[$index] = $column['id'];
                    }
                }
            }

            // Process import directly
            $batch = $this->importJobProcessDirect($excelData, $columns, $uploadedFile, PurchaseProductImport::class, ImportPurchaseProductJob::class);

            if (!$batch) {
                Files::deleteFile($uploadedFile, Files::IMPORT_FOLDER);
                return Reply::error('Failed to create import batch');
            }

            // Prepare data for view
            $this->data['batch'] = $batch;
            $this->data['batchId'] = is_object($batch) && isset($batch->id) ? $batch->id : null;
            
            try {
                $view = view('purchase::purchase-products.ajax.import_progress', $this->data)->render();
            } catch (\Exception $viewError) {
                \Log::error('View render error: ' . $viewError->getMessage());
                Files::deleteFile($uploadedFile, Files::IMPORT_FOLDER);
                return Reply::error('Failed to render progress view: ' . $viewError->getMessage());
            }

            $batchId = is_object($batch) && isset($batch->id) ? $batch->id : null;
            return Reply::successWithData(__('messages.importProcessStart'), [
                'view' => $view, 
                'batchId' => $batchId
            ]);
        } catch (\Exception $e) {
            \Log::error('Import error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            if (isset($uploadedFile)) {
                try {
                    Files::deleteFile($uploadedFile, Files::IMPORT_FOLDER);
                } catch (\Exception $deleteError) {
                    // Ignore delete errors
                }
            }
            $errorMessage = config('app.debug') ? $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() : 'Import failed. Please check the file format and try again.';
            return Reply::error($errorMessage);
        } catch (\Throwable $e) {
            \Log::error('Import fatal error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            if (isset($uploadedFile)) {
                try {
                    Files::deleteFile($uploadedFile, Files::IMPORT_FOLDER);
                } catch (\Exception $deleteError) {
                    // Ignore delete errors
                }
            }
            $errorMessage = config('app.debug') ? $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() : 'Import failed. Please check the file format and try again.';
            return Reply::error($errorMessage);
        }
    }

    /**
     * Process import job directly without mapping step
     */
    private function importJobProcessDirect($excelData, $columns, $file, $importClass, $importJobClass)
    {
        $importClassName = (new ReflectionClass($importClass))->getShortName();

        // clear previous import
        Artisan::call('queue:clear database --queue=' . $importClassName);
        Artisan::call('queue:flush');

        $jobs = [];

        Session::put('leads_count', count($excelData));

        foreach ($excelData as $row) {
            $jobs[] = (new $importJobClass($row, $columns, company()));
        }

        $batch = Bus::batch($jobs)->onConnection('database')->onQueue($importClassName)->name($importClassName)->dispatch();

        Files::deleteFile($file, Files::IMPORT_FOLDER);

        return $batch;
    }

    /**
     * Process the import job
     *
     * @param ImportProcessRequest $request
     * @return \Illuminate\Http\Response
     */
    public function importProcess(ImportProcessRequest $request)
    {
        $batch = $this->importJobProcess($request, PurchaseProductImport::class, ImportPurchaseProductJob::class);

        return Reply::successWithData(__('messages.importProcessStart'), ['batch' => $batch]);
    }

    /**
     * Download sample import file
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadSample()
    {
        try {
            return Excel::download(new PurchaseProductSampleExport, 'sample-purchase-products-import.xlsx');
        } catch (\Exception $e) {
            \Log::error('Error downloading sample file: ' . $e->getMessage());
            return Reply::error('Error generating sample file: ' . $e->getMessage());
        }
    }

    // =================================================== Purchase Create Methods =======================================================
    public function purchase_entries_index(PurchaseProductsDetailDataTable $dataTable)
    {
        // Allow clients (CFA/Distributor) to view purchase entries for invoice creation
        // but they need view_product permission or be a client
        $isClient = \App\Models\User::isClient(user()->id);
        $viewPermission = user()->permission('view_product');
        
        // Allow access if user has view_product permission OR is a client (CFA/Distributor)
        if (!$isClient && !in_array($viewPermission, ['all', 'added'])) {
            abort_403(true);
        }

        $this->pageTitle = __('Purchase Entries');
        
        $productDetails = [];
        $productDetails = OrderCart::all();
        $this->productDetails = $productDetails;
        $this->purchases = ProductPurchaseDetail::with('product')->get(); 
        $this->totalProducts = PurchaseProduct::count();
        $this->cartProductCount = OrderCart::where('client_id', user()->id)->count();

        $this->categories = ProductCategory::all();
        $this->subCategories = ProductSubCategory::all();
        $this->unitTypes = UnitType::all();
        
        // For clients, mark as read-only (they can view but not edit/create)
        $this->isClient = $isClient;

        return $dataTable->render('purchase::purchase-products.purchase_create', $this->data);
    }
    
    public function purchase_entry_edit($id)
    {
        // Edit is handled by purchase_entry_create with ID
        return $this->purchase_entry_create($id);
    }
    
    public function purchase_entry_create($id = null)
    {
        // Prevent clients from creating/editing purchase entries
        $isClient = User::isClient(user()->id);
        abort_403($isClient);
        
        // Check if this is an edit request (ID provided in route)
        $isEdit = $id !== null;
        
        if ($isEdit) {
            $this->pageTitle = __('Edit Entry');
            $this->editPermission = user()->permission('edit_product');
            abort_403(!in_array($this->editPermission, ['all', 'added']));
            
            $purchaseDetail = ProductPurchaseDetail::with(['product.unit', 'product.vendor', 'vendor'])->findOrFail($id);
            
            // Get current stock for the product: sum of all stock adjustments
            $totalStock = PurchaseStockAdjustment::where('product_id', $purchaseDetail->product_id)
                ->where('company_id', company()->id)
                ->sum('net_quantity');
            $purchaseDetail->product->current_stock = $totalStock > 0 ? $totalStock : ($purchaseDetail->product->opening_stock ?? 0);
            abort_403(!($this->editPermission == 'all' || ($this->editPermission == 'added' && $purchaseDetail->created_by == user()->id)));
            
            $this->purchaseDetail = $purchaseDetail;
            $this->product = $purchaseDetail->product;
        } else {
            $this->pageTitle = __('Add Entry');
            $this->addPermission = user()->permission('add_product');
            abort_403(!in_array($this->addPermission, ['all', 'added']));
            
            // FOR DUPLICATE
            $productId = request('duplicate_product');
            $this->product = $productId
                ? PurchaseProduct::findOrFail($productId)->withCustomFields()
                : null;
        }

        $this->taxes = Tax::all();
        $this->categories = ProductCategory::all();
        $this->subCategories = ProductSubCategory::all();
        $this->vendors = PurchaseVendor::where('company_id', company()->id)->get();
        $this->unit_types = UnitType::all();
        $this->invoiceSetting = invoice_setting();
        
        // Generate invoice number for display
        $this->autoInvoiceNumber = 'PE-' . date('Ymd') . '-' . str_pad(ProductPurchaseDetail::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);

        // FOR DROPDOWN - Include stock information and price fields (aligned with edit_invoice product shape)
        $this->products = Product::with('unit', 'vendor')
            ->select('id', 'name', 'sku', 'hsn_sac_code', 'opening_stock', 'unit_id', 'packing', 'vendor_id', 'price', 'mrp', 'pts', 'ptr')
            ->where('company_id', company()->id)
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                // Get current stock: sum of all stock adjustments for this product
                // If no stock adjustments exist, use opening_stock
                $totalStock = PurchaseStockAdjustment::where('product_id', $product->id)
                    ->where('company_id', company()->id)
                    ->sum('net_quantity');
                
                if ($totalStock > 0) {
                    $product->current_stock = $totalStock;
                } else {
                    // No stock adjustments yet, use opening_stock
                    $product->current_stock = $product->opening_stock ?? 0;
                }
                $product->unit_type = $product->unit ? $product->unit->unit_type : '';
                return $product;
            });

        if ($this->product && $this->product->sub_category_id) {
            $this->subCategories = ProductSubCategory::where(
                'category_id',
                $this->product->category_id
            )->get();
        }

        $product = new Product();
        if ($product->getCustomFieldGroupsWithFields()) {
            $this->fields = $product->getCustomFieldGroupsWithFields()->fields;
        }

        if (request()->ajax()) {
            $html = view('purchase::purchase-products.ajax.purchase_create', $this->data)->render();
            return Reply::dataOnly([
                'status' => 'success',
                'html' => $html,
                'title' => $this->pageTitle
            ]);
        }

        $this->view = 'purchase::purchase-products.ajax.purchase_create';
        return view('purchase::purchase-products.create', $this->data);
    }

    public function purchase_entry_store(Request $request)
    {
        // Prevent clients from creating purchase entries
        $isClient = User::isClient(user()->id);
        abort_403($isClient);
        
        // Invoice-level fields
        $invoiceDate = $request->invoice_date;
        $modeOfPayment = $request->mode_of_payment;
        $paymentStatus = $request->payment_status ?? 'pending';
        $referenceNumber = $request->reference_number;
        $referenceDate = $request->reference_date;
        $dispatchThrough = $request->dispatch_through;
        $destination = $request->destination;
        $termsOfDelivery = $request->terms_of_delivery;
        
        $vendorId = $request->vendor_id;
        
        // Product-level fields (arrays)
        $productIds = $request->product_id ?? [];
        $quantities = $request->quantity ?? []; // Billed Quantity
        $totalQuantities = $request->total_quantity ?? []; // Total Quantity (for stock)
        $batches = $request->batch ?? [];
        $expiries = $request->expiry ?? [];
        $purchasePrices = $request->purchase_price ?? [];
        $mrps = $request->mrp ?? [];
        $ptses = $request->pts ?? [];
        $ptrs = $request->ptr ?? [];
        $discounts = $request->discount ?? [];
        $taxes = $request->purchase_line_tax_id ?? [];
        
        if (empty($productIds)) {
            return Reply::error('Please add at least one product.');
        }
        
        // Use invoice number from form (pre-generated) or generate if not provided
        $invoiceNumber = $request->invoice_number;
        if (empty($invoiceNumber)) {
            $invoiceNumber = 'PE-' . date('Ymd') . '-' . str_pad(ProductPurchaseDetail::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT);
        }
        
        $savedCount = 0;
        $stockUpdated = []; // For debugging: product_id => net_quantity after update

        \Log::info('Create Invoice - All tax data:', [
            'invoice' => $invoiceNumber,
            'all_taxes_raw' => $taxes,
            'product_count' => count($productIds)
        ]);

        try {
            \DB::beginTransaction();

        foreach ($productIds as $index => $productId) {
            if (empty($productId)) continue;
            
            $product = Product::find($productId);
            if (!$product) continue;
            
            $billedQty = $quantities[$index] ?? 1; // Billed Quantity (for billing calculation)
            $totalQty = $totalQuantities[$index] ?? $billedQty; // Total Quantity (for stock)
            $purchasePrice = $purchasePrices[$index] ?? 0;
            $pts = $ptses[$index] ?? 0;
            $discount = $discounts[$index] ?? 0;
            
            // Calculate total based on billed quantity and purchase_price
            $base = $billedQty * $purchasePrice;
            $discAmt = $base * ($discount / 100);
            $afterDisc = $base - $discAmt;
            
            // Get taxes for this row
            $itemTaxes = isset($taxes[$index]) && is_array($taxes[$index]) ? $taxes[$index] : [];
            
            \Log::info("Create - Processing product $index:", [
                'product_id' => $productId,
                'index' => $index,
                'taxes_raw' => $taxes[$index] ?? 'not found',
                'item_taxes' => $itemTaxes,
                'tax_type' => gettype($itemTaxes)
            ]);
            
            $taxAmt = 0;
            if (!empty($itemTaxes)) {
                $taxSum = Tax::whereIn('id', $itemTaxes)->sum('rate_percent');
                $taxAmt = $afterDisc * ($taxSum / 100);
                \Log::info("Create - Tax calculation for product $index:", [
                    'tax_ids' => $itemTaxes,
                    'tax_sum' => $taxSum,
                    'tax_amount' => $taxAmt
                ]);
            }
            
            $total = $afterDisc + $taxAmt;
            
            $purchaseEntry = new ProductPurchaseDetail();
            // Invoice details (same for all products in this bulk entry)
            $purchaseEntry->invoice_number = $invoiceNumber;
            $purchaseEntry->invoice_date = $invoiceDate;
            $purchaseEntry->mode_of_payment = $modeOfPayment;
            $purchaseEntry->payment_status = $paymentStatus;
            $purchaseEntry->reference_number = $referenceNumber;
            $purchaseEntry->reference_date = $referenceDate;
            $purchaseEntry->dispatch_through = $dispatchThrough;
            $purchaseEntry->destination = $destination;
            $purchaseEntry->terms_of_delivery = $termsOfDelivery;
            
            // Vendor
            $purchaseEntry->vendor_id = $vendorId;
            
            // Product details
            $purchaseEntry->product_id = $productId;
            $purchaseEntry->quantity = $billedQty; // Billed Quantity
            $purchaseEntry->total_quantity = $totalQty; // Total Quantity (received)
            $purchaseEntry->unit_id = $product->unit_id;
            $purchaseEntry->batch = $batches[$index] ?? null;
            
            if (!empty($expiries[$index])) {
                try {
                    $purchaseEntry->expiry = Carbon::parse($expiries[$index] . '-01');
                } catch (\Exception $e) {
                    $purchaseEntry->expiry = null;
                }
            }
            
            $purchaseEntry->purchase_price = $purchasePrices[$index] ?? 0;
            $purchaseEntry->mrp = $mrps[$index] ?? 0;
            $purchaseEntry->pts = $pts;
            $purchaseEntry->ptr = $ptrs[$index] ?? 0;
            $purchaseEntry->discount = $discount;
            $purchaseEntry->discount_type = 'percent';
            $purchaseEntry->tax = !empty($itemTaxes) ? $itemTaxes : null;
            $purchaseEntry->total = $total;
            $purchaseEntry->created_by = user()->id;
            
            \Log::info("Create - Before save product $index:", [
                'product_id' => $productId,
                'batch' => $purchaseEntry->batch,
                'tax_before_save' => $purchaseEntry->tax,
                'tax_type' => gettype($purchaseEntry->tax),
                'total' => $purchaseEntry->total
            ]);
            
            $purchaseEntry->save();
            
            \Log::info("Create - After save product $index:", [
                'id' => $purchaseEntry->id,
                'tax_after_save' => $purchaseEntry->tax,
                'tax_from_db' => $purchaseEntry->fresh()->tax
            ]);
            
            // Update stock using Total Quantity (firstOrCreate aligns with updateInvoice, avoids duplicate rows)
            $stockAdj = PurchaseStockAdjustment::firstOrCreate(
                ['product_id' => $productId, 'company_id' => company()->id],
                ['net_quantity' => 0]
            );
            $stockAdj->net_quantity += $totalQty;
            $stockAdj->save();
            $stockUpdated[$productId] = $stockAdj->net_quantity;

            // Batch-wise inventory: add to purchase_batch_stock
            $batchKey = $purchaseEntry->batch ?? '';
            $expiryDate = $purchaseEntry->expiry;
            $batchStock = PurchaseBatchStock::firstOrCreate(
                [
                    'company_id' => company()->id,
                    'product_id' => $productId,
                    'batch'      => $batchKey ?: null,
                    'expiry'     => $expiryDate,
                ],
                ['quantity' => 0]
            );
            $batchStock->quantity = (float) $batchStock->quantity + $totalQty;
            $batchStock->save();
            
            \Log::info('Increased product stock from purchase entry', [
                'product_id' => $productId,
                'quantity_added' => $totalQty,
                'stock_before' => $stockAdj->net_quantity - $totalQty,
                'stock_after' => $stockAdj->net_quantity
            ]);
            
            $savedCount++;
        }

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Purchase entry store failed', [
                'invoice' => $invoiceNumber ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Reply::error('Failed to save purchase entry: ' . $e->getMessage());
        }

        // Create or find Supplier Invoice and link purchase entry lines; set entry_total and match_status
        $invoiceDateParsed = Carbon::parse($invoiceDate);
        $supplierInvoice = SupplierInvoice::firstOrCreate(
            [
                'company_id'     => company()->id,
                'vendor_id'     => $vendorId,
                'invoice_number' => $invoiceNumber,
                'invoice_date'  => $invoiceDateParsed,
            ],
            [
                'supplier_invoice_total' => $request->supplier_invoice_total ? (float) $request->supplier_invoice_total : null,
                'entry_total'           => null,
                'match_status'          => SupplierInvoice::MATCH_STATUS_DRAFT,
                'payment_status'        => SupplierInvoice::PAYMENT_STATUS_PENDING,
                'reference_number'     => $referenceNumber,
                'reference_date'        => $referenceDate ? Carbon::parse($referenceDate) : null,
                'notes'                 => null,
                'created_by'            => user()->id,
            ]
        );
        ProductPurchaseDetail::where('invoice_number', $invoiceNumber)
            ->where('vendor_id', $vendorId)
            ->whereDate('invoice_date', $invoiceDateParsed)
            ->update(['supplier_invoice_id' => $supplierInvoice->id]);
        $supplierInvoice->refreshTotalsAndMatchStatus();

        // When user did not enter a supplier total, auto-set it from calculated entry total so it stays matched (no popup)
        $userEnteredSupplierTotal = $request->supplier_invoice_total !== null && $request->supplier_invoice_total !== '';
        if (!$userEnteredSupplierTotal && $supplierInvoice->entry_total !== null) {
            $supplierInvoice->supplier_invoice_total = (float) $supplierInvoice->entry_total;
            $supplierInvoice->saveQuietly();
            $supplierInvoice->refreshTotalsAndMatchStatus();
        }

        $matchStatus = $supplierInvoice->fresh()->match_status;
        $matchMessage = null;
        // Only show match popup when user explicitly entered a supplier total (reconciliation case)
        if ($userEnteredSupplierTotal && $supplierInvoice->supplier_invoice_total !== null && $supplierInvoice->supplier_invoice_total !== '') {
            $matchMessage = $matchStatus === SupplierInvoice::MATCH_STATUS_MATCHED
                ? __('app.entryMatchesSupplierInvoice')
                : __('app.entryDoesNotMatchSupplierInvoice');
        }

        return Reply::successWithData(__('messages.recordSaved') . " ($savedCount entries)", [
            'redirectUrl' => route('purchase-entries.index'),
            'matchStatus' => $matchStatus,
            'matchMessage' => $matchMessage,
            'stockUpdated' => $stockUpdated, // Debug: product_id => net_quantity after update
        ]);
    }

    // =================================================== Purchase Product Details Methods =======================================================
    public function purchase_entry_show($id)
    {
        // Load purchase entry with all necessary relationships
        $purchaseDetail = ProductPurchaseDetail::with([
            'product.unit',
            'product.vendor',
            'product.category',
            'product.subCategory',
            'vendor'
        ])->findOrFail($id);
        
        // Ensure company_id matches current company
        if ($purchaseDetail->product && $purchaseDetail->product->company_id != company()->id) {
            abort_403(true);
        }
        
        $this->purchaseDetail = $purchaseDetail;
        $this->pageTitle = __('Purchase Entry Details');
        
        return view('purchase::purchase-products.show_detail', $this->data);
    }


    public function purchase_entry_update(Request $request, $id)
    {
        // Prevent clients from updating purchase entries
        $isClient = User::isClient(user()->id);
        abort_403($isClient);
        
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
            'purchase_price' => 'required|numeric',
            'total'      => 'required|numeric',
        ]);
        
        $purchaseDetail = ProductPurchaseDetail::findOrFail($id);
        $this->editPermission = user()->permission('edit_product');
        abort_403(!($this->editPermission == 'all' || ($this->editPermission == 'added' && $purchaseDetail->created_by == user()->id)));
        
        // Store old quantity for stock adjustment
        $oldQuantity = $purchaseDetail->quantity;
        $oldProductId = $purchaseDetail->product_id;
        
        $purchaseDetail->update([
            'product_id'    => $request->product_id,
            'vendor_id'     => $request->vendor_id ?? null,
            'quantity'      => $request->quantity,
            'unit_id'      => $request->unit_type ?? 1,
            'batch'         => $request->batch,
            'expiry'        => $request->expiry,
            'pts'           => $request->pts,
            'ptr'           => $request->ptr,
            'dis'           => $request->dis,
            'mrp'           => $request->purchase_price,
            'discount'      => $request->discount,
            'discount_type' => 'percentage', // Always percentage
            'total'         => $request->total,
            'tax'           => $request->tax ? json_encode($request->tax) : null,
            'description'   => $request->description,
            'scheme_enabled' => $request->has('scheme_enabled') ? 1 : 0,
            'total_quantity' => $request->total_quantity ?? null,
            'free_quantity'  => $request->free_quantity ?? null,
        ]);

        // Update stock: Recalculate total stock for both old and new products
        // Old product (if changed)
        if ($oldProductId != $request->product_id) {
            $oldProduct = Product::find($oldProductId);
            $oldOpeningStock = $oldProduct->opening_stock ?? 0;
            $oldTotalPurchaseEntries = \App\Models\ProductPurchaseDetail::where('product_id', $oldProductId)
                ->sum('quantity');
            $oldTotalStock = $oldOpeningStock + $oldTotalPurchaseEntries;
            
            $oldStockAdjustment = PurchaseStockAdjustment::firstOrCreate(
                ['product_id' => $oldProductId, 'company_id' => company()->id],
                ['date' => now()->format('Y-m-d'), 'type' => 'quantity', 'status' => 'converted', 'net_quantity' => 0]
            );
            $oldStockAdjustment->net_quantity = $oldTotalStock;
            $oldStockAdjustment->save();
        }
        
        // New/Updated product
        $currentProduct = Product::find($request->product_id);
        
        // Update product's HSN/SAC code if provided (from 'sku' field in form)
        if ($request->has('sku') && $request->sku) {
            $currentProduct->hsn_sac_code = $request->sku;
            $currentProduct->save();
        }
        
        // Update purchase entry vendor_id if provided
        if ($request->has('vendor_id')) {
            $purchaseDetail->vendor_id = $request->vendor_id ?: null;
            $purchaseDetail->save();
        }
        
        $openingStock = $currentProduct->opening_stock ?? 0;
        $totalPurchaseEntries = \App\Models\ProductPurchaseDetail::where('product_id', $request->product_id)
            ->sum('quantity');
        $totalStock = $openingStock + $totalPurchaseEntries;
        
        $stockAdjustment = PurchaseStockAdjustment::firstOrCreate(
            ['product_id' => $request->product_id, 'company_id' => company()->id],
            ['date' => now()->format('Y-m-d'), 'type' => 'quantity', 'status' => 'converted', 'net_quantity' => 0]
        );
        $stockAdjustment->net_quantity = $totalStock;
        $stockAdjustment->save();

        $redirectUrl = urldecode($request->redirect_url ?? '');

        // CRITICAL: Prevent redirecting to PUT-only routes (e.g., /purchase-entries/12)
        // These routes only accept PUT/DELETE, not GET
        if ($redirectUrl && preg_match('/\/purchase-entries\/\d+$/', $redirectUrl)) {
            // Redirect to edit page instead of PUT-only route
            $redirectUrl = route('purchase-entries.edit', $id);
        }

        if ($redirectUrl == '') {
            $redirectUrl = route('purchase-entries.index');
        }
        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => $redirectUrl]);
    }

    public function purchase_entry_destroy($id)
    {
        // Prevent clients from deleting purchase entries
        $isClient = User::isClient(user()->id);
        abort_403($isClient);
        
        $purchaseDetail = ProductPurchaseDetail::findOrFail($id);
        $this->deletePermission = user()->permission('delete_product');
        abort_403(!($this->deletePermission == 'all' || ($this->deletePermission == 'added' && $purchaseDetail->created_by == user()->id)));

        // Reduce stock from PurchaseStockAdjustment when purchase entry is deleted
        $productId = $purchaseDetail->product_id;
        $quantityToRestore = $purchaseDetail->total_quantity ?? $purchaseDetail->quantity ?? 0;
        
        if ($productId && $quantityToRestore > 0) {
            $stockAdjustment = PurchaseStockAdjustment::where('product_id', $productId)
                ->where('company_id', company()->id)
                ->first();
            
            if ($stockAdjustment) {
                // Reduce stock (subtract the quantity that was added when this entry was created)
                $stockBefore = $stockAdjustment->net_quantity;
                $stockAdjustment->net_quantity = max(0, $stockAdjustment->net_quantity - $quantityToRestore);
                $stockAdjustment->save();
                
                \Log::info('Reduced product stock on purchase entry deletion', [
                    'purchase_entry_id' => $id,
                    'product_id' => $productId,
                    'quantity_restored' => $quantityToRestore,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAdjustment->net_quantity
                ]);
            } else {
                \Log::warning('PurchaseStockAdjustment not found when deleting purchase entry', [
                    'purchase_entry_id' => $id,
                    'product_id' => $productId
                ]);
            }

            // Batch-wise: subtract from purchase_batch_stock
            $batchStock = PurchaseBatchStock::where('company_id', company()->id)
                ->where('product_id', $productId)
                ->where(function ($q) use ($purchaseDetail) {
                    if ($purchaseDetail->batch !== null && $purchaseDetail->batch !== '') {
                        $q->where('batch', $purchaseDetail->batch);
                    } else {
                        $q->whereNull('batch');
                    }
                    if ($purchaseDetail->expiry) {
                        $q->where('expiry', $purchaseDetail->expiry);
                    } else {
                        $q->whereNull('expiry');
                    }
                })->first();
            if ($batchStock && $batchStock->quantity > 0) {
                $batchStock->quantity = max(0, (float) $batchStock->quantity - $quantityToRestore);
                $batchStock->save();
            }
        }

        $purchaseDetail->delete();

        return Reply::successWithData(__('messages.deleteSuccess'), ['redirectUrl' => route('purchase-entries.index')]);
    }

    /**
     * Get consolidated products with batch numbers for dropdown
     * Returns products grouped by name with all available batches
     */
    public function getConsolidatedProducts()
    {
        $products = ProductPurchaseDetail::with('product')
            ->select('product_id')
            ->groupBy('product_id')
            ->get()
            ->map(function($entry) {
                $batches = ProductPurchaseDetail::where('product_id', $entry->product_id)
                    ->select('batch', 'id as purchase_entry_id')
                    ->whereNotNull('batch')
                    ->where('batch', '!=', '')
                    ->distinct()
                    ->get();
                
                if ($batches->isEmpty()) {
                    return null;
                }
                
                $batchText = $batches->map(function($b) {
                    return "Batch: " . ($b->batch ?? 'N/A');
                })->join(' ');
                
                $displayName = ($entry->product->name ?? 'Unknown') . " - " . $batchText;
                
                return [
                    'product_id' => $entry->product_id,
                    'product_name' => $entry->product->name ?? 'Unknown',
                    'batches' => $batches->map(function($b) {
                        return [
                            'batch' => $b->batch,
                            'purchase_entry_id' => $b->purchase_entry_id
                        ];
                    }),
                    'display_name' => $displayName
                ];
            })
            ->filter(); // Remove null entries
        
        return Reply::dataOnly(['data' => $products->values()]);
    }

    /**
     * Get purchase entry data by product ID and batch number
     */
    public function getPurchaseEntryByBatch($productId, $batch)
    {
        $purchaseEntry = ProductPurchaseDetail::with(['product.unit', 'product.category', 'product.subCategory', 'vendor'])
            ->where('product_id', $productId)
            ->where('batch', $batch)
            ->first();
        
        if (!$purchaseEntry) {
            return Reply::error('Purchase entry not found for this product and batch combination.');
        }
        
        // Format the response
        $data = [
            'id' => $purchaseEntry->id,
            'product_id' => $purchaseEntry->product_id,
            'batch' => $purchaseEntry->batch,
            'expiry' => $purchaseEntry->expiry ? $purchaseEntry->expiry->format('Y-m-d') : null,
            'quantity' => $purchaseEntry->quantity,
            'mrp' => $purchaseEntry->mrp,
            'pts' => $purchaseEntry->pts,
            'ptr' => $purchaseEntry->ptr,
            'dis' => $purchaseEntry->dis,
            'discount' => $purchaseEntry->discount,
            'total' => $purchaseEntry->total,
            'tax' => $purchaseEntry->tax ? json_decode($purchaseEntry->tax, true) : [],
            'vendor_id' => $purchaseEntry->vendor_id,
            'unit_id' => $purchaseEntry->unit_id,
            'packing' => $purchaseEntry->product->packing ?? null,
            'hsn_code' => $purchaseEntry->product->hsn_code ?? null,
            'scheme_enabled' => $purchaseEntry->scheme_enabled ?? 0,
            'total_quantity' => $purchaseEntry->total_quantity,
            'free_quantity' => $purchaseEntry->free_quantity,
            'description' => $purchaseEntry->description,
            'current_stock' => $purchaseEntry->product->current_stock ?? 0,
        ];
        
        return Reply::dataOnly(['data' => $data]);
    }
    
    /**
     * View all products in a specific invoice
     */
    public function viewInvoiceProducts($invoiceNumber)
    {
        $products = ProductPurchaseDetail::with(['product', 'vendor'])
            ->where('invoice_number', $invoiceNumber)
            ->get();
        
        $this->invoiceNumber = $invoiceNumber;
        $this->products = $products;
        $this->pageTitle = 'Invoice: ' . $invoiceNumber;
        
        return view('purchase::purchase-products.invoice_products', $this->data);
    }
    
    /**
     * Edit entire invoice
     */
    public function editInvoice($invoiceNumber)
    {
        $products = ProductPurchaseDetail::with(['product', 'vendor'])
            ->where('invoice_number', $invoiceNumber)
            ->get();
        
        if ($products->isEmpty()) {
            abort(404, 'Invoice not found');
        }
        
        // Debug: Log batch and tax values from database
        \Log::info('Edit Invoice - Data from DB:', [
            'invoice' => $invoiceNumber,
            'batches' => $products->pluck('batch', 'id')->toArray(),
            'taxes' => $products->map(function($p) {
                return [
                    'id' => $p->id,
                    'tax' => $p->tax,
                    'tax_type' => gettype($p->tax),
                    'tax_json' => json_encode($p->tax)
                ];
            })->toArray()
        ]);
        
        // Get all products for the dropdown - same query/shape as purchase_entry_create for consistent list and order
        $allProducts = Product::with('unit', 'vendor')
            ->select('id', 'name', 'sku', 'hsn_sac_code', 'price', 'mrp', 'pts', 'ptr')
            ->where('company_id', company()->id)
            ->orderBy('name')
            ->get();
        
        $first = $products->first();
        $supplierInvoice = $first && $first->supplier_invoice_id
            ? SupplierInvoice::where('company_id', company()->id)->find($first->supplier_invoice_id)
            : SupplierInvoice::where('company_id', company()->id)
                ->where('invoice_number', $invoiceNumber)
                ->where('vendor_id', $first->vendor_id ?? 0)
                ->first();

        $this->invoiceNumber = $invoiceNumber;
        $this->products = $products;
        $this->allProducts = $allProducts;
        $this->vendors = PurchaseVendor::where('company_id', company()->id)->get();
        $this->taxes = Tax::all();
        $this->pageTitle = 'Edit Invoice: ' . $invoiceNumber;
        $this->invoiceSetting = invoice_setting();
        $this->supplierInvoice = $supplierInvoice;
        
        return view('purchase::purchase-products.edit_invoice', $this->data);
    }
    
    /**
     * Update entire invoice
     */
    public function updateInvoice(Request $request, $invoiceNumber)
    {
        // Debug: Log incoming data
        \Log::info('Update Invoice - Request data:', [
            'invoice' => $invoiceNumber,
            'product_ids' => $request->product_id ?? [],
            'batches' => $request->batch ?? [],
            'quantities' => $request->quantity ?? [],
            'vendor_id' => $request->vendor_id,
            'invoice_date' => $request->invoice_date
        ]);
        
        // Validate required fields BEFORE deleting anything
        $productIds = $request->product_id ?? [];
        if (empty($productIds)) {
            \Log::error('Update Invoice - No products provided');
            return Reply::error('At least one product is required');
        }
        
        if (!$request->vendor_id) {
            \Log::error('Update Invoice - No vendor provided');
            return Reply::error('Vendor is required');
        }
        
        if (!$request->invoice_date) {
            \Log::error('Update Invoice - No invoice date provided');
            return Reply::error('Invoice date is required');
        }
        
        // Get existing products to track stock changes
        $existingProducts = ProductPurchaseDetail::where('invoice_number', $invoiceNumber)->get();
        
        if ($existingProducts->isEmpty()) {
            \Log::warning('Update Invoice - Invoice not found: ' . $invoiceNumber);
            return Reply::error('Invoice not found');
        }
        
        // Store old quantities and batch info for stock adjustment (product-level and batch-level)
        $oldQuantities = [];
        foreach ($existingProducts as $product) {
            $oldQuantities[$product->id] = [
                'product_id' => $product->product_id,
                'total_quantity' => $product->total_quantity ?? $product->quantity,
                'batch' => $product->batch,
                'expiry' => $product->expiry,
            ];
        }
        
        try {
            // Use database transaction
            \DB::beginTransaction();
            
            // Delete existing products
            ProductPurchaseDetail::where('invoice_number', $invoiceNumber)->delete();
            
            // Restore old stock (product-level and batch-level)
            foreach ($oldQuantities as $oldData) {
                $stockAdjustment = PurchaseStockAdjustment::where('product_id', $oldData['product_id'])
                    ->where('company_id', company()->id)
                    ->first();
                
                if ($stockAdjustment) {
                    $stockBefore = $stockAdjustment->net_quantity;
                    $stockAdjustment->net_quantity = max(0, $stockAdjustment->net_quantity - $oldData['total_quantity']);
                    $stockAdjustment->save();
                    
                    \Log::info('Restored stock on purchase entry update (old entry deleted)', [
                        'product_id' => $oldData['product_id'],
                        'quantity_restored' => $oldData['total_quantity'],
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAdjustment->net_quantity
                    ]);
                }

                // Batch-wise: subtract from purchase_batch_stock
                $batchStock = PurchaseBatchStock::where('company_id', company()->id)
                    ->where('product_id', $oldData['product_id'])
                    ->where(function ($q) use ($oldData) {
                        if (isset($oldData['batch']) && $oldData['batch'] !== '' && $oldData['batch'] !== null) {
                            $q->where('batch', $oldData['batch']);
                        } else {
                            $q->whereNull('batch');
                        }
                        if (isset($oldData['expiry']) && $oldData['expiry']) {
                            $q->where('expiry', $oldData['expiry']);
                        } else {
                            $q->whereNull('expiry');
                        }
                    })->first();
                if ($batchStock && $batchStock->quantity > 0) {
                    $batchStock->quantity = max(0, (float) $batchStock->quantity - $oldData['total_quantity']);
                    $batchStock->save();
                }
            }
            
            // Now add new products (reuse the same logic as store)
            $savedCount = 0;
        
        foreach ($productIds as $index => $productId) {
            if (empty($productId)) continue;
            
            $billedQty = $request->quantity[$index] ?? 0;
            $totalQty = $request->total_quantity[$index] ?? $billedQty;
            $purchasePrice = $request->purchase_price[$index] ?? 0;
            $discount = $request->discount[$index] ?? 0;
            
            // Calculate total
            $subtotal = $purchasePrice * $billedQty;
            $discountAmount = ($subtotal * $discount) / 100;
            $afterDiscount = $subtotal - $discountAmount;
            
            // Tax calculation with detailed logging
            $taxAmount = 0;
            $selectedTaxes = $request->purchase_line_tax_id[$index] ?? [];
            
            \Log::info("Processing product $index:", [
                'product_id' => $productId,
                'index' => $index,
                'raw_tax_from_request' => $request->purchase_line_tax_id[$index] ?? 'not found',
                'selected_taxes' => $selectedTaxes,
                'tax_type' => gettype($selectedTaxes),
                'all_tax_data' => $request->purchase_line_tax_id ?? []
            ]);
            
            if (!empty($selectedTaxes) && is_array($selectedTaxes)) {
                foreach ($selectedTaxes as $taxId) {
                    $tax = Tax::find($taxId);
                    if ($tax) {
                        $taxAmount += ($afterDiscount * $tax->rate_percent) / 100;
                        \Log::info("Applied tax $taxId:", [
                            'tax_name' => $tax->tax_name,
                            'rate' => $tax->rate_percent,
                            'amount' => ($afterDiscount * $tax->rate_percent) / 100
                        ]);
                    }
                }
            }
            
            $total = $afterDiscount + $taxAmount;
            
            \Log::info("Product $index totals:", [
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'after_discount' => $afterDiscount,
                'tax_amount' => $taxAmount,
                'total' => $total
            ]);
            
            // Save product entry
            $entry = new ProductPurchaseDetail();
            $entry->invoice_number = $invoiceNumber;
            $entry->invoice_date = $request->invoice_date;
            $entry->vendor_id = $request->vendor_id;
            $entry->product_id = $productId;
            $entry->quantity = $billedQty;
            $entry->total_quantity = $totalQty;
            $entry->batch = $request->batch[$index] ?? null;
            
            // Handle expiry date - convert from YYYY-MM to full date
            if (!empty($request->expiry[$index])) {
                try {
                    $entry->expiry = \Carbon\Carbon::parse($request->expiry[$index] . '-01');
                } catch (\Exception $e) {
                    \Log::warning("Invalid expiry date for product $index: " . $request->expiry[$index]);
                    $entry->expiry = null;
                }
            } else {
                $entry->expiry = null;
            }
            $entry->purchase_price = $purchasePrice;
            $entry->mrp = $request->mrp[$index] ?? 0;
            $entry->pts = $request->pts[$index] ?? 0;
            $entry->ptr = $request->ptr[$index] ?? 0;
            $entry->discount = $discount;
            $entry->tax = !empty($selectedTaxes) ? $selectedTaxes : null;
            $entry->total = $total;
            $entry->mode_of_payment = $request->mode_of_payment;
            $entry->payment_status = $request->payment_status;
            $entry->reference_number = $request->reference_number;
            $entry->reference_date = $request->reference_date;
            $entry->dispatch_through = $request->dispatch_through;
            $entry->destination = $request->destination;
            $entry->terms_of_delivery = $request->terms_of_delivery;
            $entry->created_by = user()->id;
            
            \Log::info("Saving product entry $index:", [
                'product_id' => $productId,
                'batch' => $entry->batch,
                'tax_before_save' => $entry->tax,
                'tax_type' => gettype($entry->tax),
                'total' => $entry->total
            ]);
            
            $entry->save();
            
            \Log::info("After save - product entry $index:", [
                'id' => $entry->id,
                'tax_after_save' => $entry->tax,
                'tax_from_db' => $entry->fresh()->tax
            ]);
            
            // Update stock (product-level)
            $stockAdjustment = PurchaseStockAdjustment::firstOrCreate(
                ['product_id' => $productId, 'company_id' => company()->id],
                ['net_quantity' => 0]
            );
            $stockAdjustment->net_quantity += $totalQty;
            $stockAdjustment->save();

            // Batch-wise inventory: add to purchase_batch_stock
            $batchKey = $entry->batch ?? null;
            $expiryDate = $entry->expiry;
            $batchStock = PurchaseBatchStock::firstOrCreate(
                [
                    'company_id' => company()->id,
                    'product_id' => $productId,
                    'batch'      => $batchKey,
                    'expiry'     => $expiryDate,
                ],
                ['quantity' => 0]
            );
            $batchStock->quantity = (float) $batchStock->quantity + $totalQty;
            $batchStock->save();
            
            $savedCount++;
        }

            // Create or find Supplier Invoice and link lines; set entry_total and match_status
            $invoiceDateParsed = Carbon::parse($request->invoice_date);
            $supplierInvoice = SupplierInvoice::firstOrCreate(
                [
                    'company_id'      => company()->id,
                    'vendor_id'      => $request->vendor_id,
                    'invoice_number' => $invoiceNumber,
                    'invoice_date'   => $invoiceDateParsed,
                ],
                [
                    'supplier_invoice_total' => $request->supplier_invoice_total ? (float) $request->supplier_invoice_total : null,
                    'entry_total'           => null,
                    'match_status'          => SupplierInvoice::MATCH_STATUS_DRAFT,
                    'payment_status'        => SupplierInvoice::PAYMENT_STATUS_PENDING,
                    'reference_number'     => $request->reference_number,
                    'reference_date'        => $request->reference_date ? Carbon::parse($request->reference_date) : null,
                    'notes'                 => null,
                    'created_by'            => user()->id,
                ]
            );
            $supplierInvoice->supplier_invoice_total = $request->supplier_invoice_total !== null && $request->supplier_invoice_total !== '' ? (float) $request->supplier_invoice_total : null;
            $supplierInvoice->saveQuietly();
            ProductPurchaseDetail::where('invoice_number', $invoiceNumber)
                ->where('vendor_id', $request->vendor_id)
                ->whereDate('invoice_date', $invoiceDateParsed)
                ->update(['supplier_invoice_id' => $supplierInvoice->id]);
            $supplierInvoice->refreshTotalsAndMatchStatus();

            // When user did not enter a supplier total, auto-set from calculated entry total so it stays matched (no popup)
            $userEnteredSupplierTotal = $request->supplier_invoice_total !== null && $request->supplier_invoice_total !== '';
            if (!$userEnteredSupplierTotal && $supplierInvoice->entry_total !== null) {
                $supplierInvoice->supplier_invoice_total = (float) $supplierInvoice->entry_total;
                $supplierInvoice->saveQuietly();
                $supplierInvoice->refreshTotalsAndMatchStatus();
            }

            $matchStatus = $supplierInvoice->fresh()->match_status;
            $matchMessage = null;
            if ($userEnteredSupplierTotal && $supplierInvoice->supplier_invoice_total !== null && $supplierInvoice->supplier_invoice_total !== '') {
                $matchMessage = $matchStatus === SupplierInvoice::MATCH_STATUS_MATCHED
                    ? __('app.entryMatchesSupplierInvoice')
                    : __('app.entryDoesNotMatchSupplierInvoice');
            }
        
            \DB::commit();
            
            \Log::info('Update Invoice Success:', [
                'invoice' => $invoiceNumber,
                'saved_count' => $savedCount
            ]);
            
            return Reply::successWithData(__('messages.updateSuccess') . " ($savedCount products)", [
                'redirectUrl' => route('purchase-entries.index'),
                'matchStatus' => $matchStatus,
                'matchMessage' => $matchMessage,
            ]);
            
        } catch (\Exception $e) {
            \DB::rollBack();
            
            \Log::error('Update Invoice Error:', [
                'invoice' => $invoiceNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Reply::error('Failed to update invoice: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete entire invoice (all products)
     */
    public function deleteInvoice(Request $request)
    {
        // Check permission
        abort_403(user()->permission('delete_product') != 'all');
        
        $invoiceNumber = $request->input('invoice_number');
        
        \Log::info('Delete Invoice Request:', [
            'invoice_number' => $invoiceNumber,
            'all_data' => $request->all(),
            'user_id' => user()->id
        ]);
        
        if (!$invoiceNumber) {
            \Log::error('Delete Invoice - No invoice number provided');
            return Reply::error('Invoice number required');
        }
        
        // Get all products in this invoice
        $products = ProductPurchaseDetail::where('invoice_number', $invoiceNumber)->get();
        
        if ($products->isEmpty()) {
            \Log::warning('Delete Invoice - No products found for: ' . $invoiceNumber);
            return Reply::error('Invoice not found or already deleted');
        }
        
        try {
            // Reduce stock for each product before deletion (restore stock that was added when entry was created)
            foreach ($products as $product) {
                $productId = $product->product_id;
                $quantityToRestore = $product->total_quantity ?? $product->quantity ?? 0;
                
                if ($productId && $quantityToRestore > 0) {
                    $stockAdjustment = PurchaseStockAdjustment::where('product_id', $productId)
                        ->where('company_id', company()->id)
                        ->first();
                    
                    if ($stockAdjustment) {
                        // Reduce stock (subtract the quantity that was added when this entry was created)
                        $stockBefore = $stockAdjustment->net_quantity;
                        $stockAdjustment->net_quantity = max(0, $stockAdjustment->net_quantity - $quantityToRestore);
                        $stockAdjustment->save();
                        
                        \Log::info('Reduced product stock on purchase invoice deletion', [
                            'invoice_number' => $invoiceNumber,
                            'product_id' => $productId,
                            'quantity_restored' => $quantityToRestore,
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAdjustment->net_quantity
                        ]);
                    }

                    // Batch-wise: subtract from purchase_batch_stock
                    $batchStock = PurchaseBatchStock::where('company_id', company()->id)
                        ->where('product_id', $productId)
                        ->where(function ($q) use ($product) {
                            if ($product->batch !== null && $product->batch !== '') {
                                $q->where('batch', $product->batch);
                            } else {
                                $q->whereNull('batch');
                            }
                            if ($product->expiry) {
                                $q->where('expiry', $product->expiry);
                            } else {
                                $q->whereNull('expiry');
                            }
                        })->first();
                    if ($batchStock && $batchStock->quantity > 0) {
                        $batchStock->quantity = max(0, (float) $batchStock->quantity - $quantityToRestore);
                        $batchStock->save();
                    }
                }
            }
            
            // Now delete all products with this invoice number
            foreach ($products as $product) {
                $totalQty = $product->total_quantity ?? $product->quantity;
                
                if ($product->product_id && $totalQty > 0) {
                    // Reduce stock by subtracting the quantity that was added
                    $stockAdjustment = PurchaseStockAdjustment::where('product_id', $product->product_id)
                        ->where('company_id', company()->id)
                        ->first();
                    
                    if ($stockAdjustment) {
                        $stockAdjustment->net_quantity = max(0, $stockAdjustment->net_quantity - $totalQty);
                        $stockAdjustment->save();
                    }
                }
            }
            
            // Delete all products with this invoice number (force delete to ensure removal)
            $deleted = $products->count();
            
            // Use force delete to ensure records are actually removed
            foreach ($products as $product) {
                $product->forceDelete(); // Force delete to bypass any soft delete
            }
            
            // Also try direct database delete as backup
            \DB::table('product_purchase_details')
                ->where('invoice_number', $invoiceNumber)
                ->delete();

            // Refresh Supplier Invoices that had these lines (entry_total will become 0)
            $supplierInvoiceIds = $products->pluck('supplier_invoice_id')->unique()->filter();
            foreach ($supplierInvoiceIds as $sid) {
                SupplierInvoice::find($sid)?->refreshTotalsAndMatchStatus();
            }
            
            \Log::info('Delete Invoice Success:', [
                'invoice' => $invoiceNumber,
                'deleted_count' => $deleted,
                'method' => 'force_delete'
            ]);
            
            return Reply::success(__('messages.deleteSuccess') . " ($deleted products deleted)");
        } catch (\Exception $e) {
            \Log::error('Delete Invoice Error:', [
                'invoice' => $invoiceNumber,
                'error' => $e->getMessage()
            ]);
            return Reply::error('Failed to delete invoice: ' . $e->getMessage());
        }
    }

    /**
     * Bulk recalculate product stock from purchase entries and clean orphaned entries
     */
    public function recalculateStock(Request $request)
    {
        // Check permission
        abort_403(user()->permission('edit_product') != 'all');
        
        try {
            \DB::beginTransaction();
            
            $companyId = company()->id;
            $stats = [
                'products_updated' => 0,
                'orphaned_entries_deleted' => 0,
                'stock_adjustments_updated' => 0
            ];
            
            // Step 1: Clean up ALL orphaned purchase entries
            // Get all valid product IDs for current company
            $validProductIds = PurchaseProduct::where('company_id', $companyId)->pluck('id')->toArray();
            
            if (empty($validProductIds)) {
                // If no valid products, delete ALL purchase entries
                $orphanedEntries = ProductPurchaseDetail::all();
            } else {
                // Find ALL orphaned entries using efficient queries:
                // 1. Entries where product_id is null
                $nullProductEntries = ProductPurchaseDetail::whereNull('product_id')->get();
                
                // 2. Entries where product_id doesn't exist in valid products
                $invalidProductEntries = ProductPurchaseDetail::whereNotNull('product_id')
                    ->whereNotIn('product_id', $validProductIds)
                    ->get();
                
                // 3. Entries where product exists but belongs to different company
                $wrongCompanyEntries = ProductPurchaseDetail::whereIn('product_id', $validProductIds)
                    ->whereHas('product', function($q) use ($companyId) {
                        $q->where('company_id', '!=', $companyId);
                    })
                    ->get();
                
                // Combine all orphaned entries
                $orphanedEntries = $nullProductEntries
                    ->merge($invalidProductEntries)
                    ->merge($wrongCompanyEntries)
                    ->unique('id');
            }
            
            $stats['orphaned_entries_deleted'] = $orphanedEntries->count();
            
            // Delete all orphaned entries (force delete to ensure removal)
            foreach ($orphanedEntries as $entry) {
                $reason = 'unknown';
                if (is_null($entry->product_id)) {
                    $reason = 'null_product_id';
                } elseif (!in_array($entry->product_id, $validProductIds ?? [])) {
                    $reason = 'invalid_product_id';
                } elseif ($entry->product && $entry->product->company_id != $companyId) {
                    $reason = 'wrong_company';
                }
                
                \Log::info('Deleting orphaned purchase entry', [
                    'entry_id' => $entry->id,
                    'product_id' => $entry->product_id,
                    'invoice_number' => $entry->invoice_number,
                    'quantity' => $entry->total_quantity ?? $entry->quantity ?? 0,
                    'reason' => $reason
                ]);
                
                // Force delete to ensure it's actually removed
                try {
                    $entry->forceDelete();
                } catch (\Exception $e) {
                    // If forceDelete doesn't work, use regular delete
                    $entry->delete();
                }
            }
            
            // Also use direct DB delete as final cleanup for any remaining orphaned entries
            if (!empty($validProductIds)) {
                $directDeleted = \DB::table('product_purchase_details')
                    ->whereNotIn('product_id', $validProductIds)
                    ->orWhereNull('product_id')
                    ->delete();
                
                if ($directDeleted > 0) {
                    \Log::info('Direct DB cleanup deleted entries', ['count' => $directDeleted]);
                    $stats['orphaned_entries_deleted'] += $directDeleted;
                }
            }
            
            // Step 2: Get all products for current company
            $products = PurchaseProduct::where('company_id', $companyId)->get();
            
            foreach ($products as $product) {
                // Calculate stock from purchase entries
                $entries = ProductPurchaseDetail::where('product_id', $product->id)
                    ->whereHas('product', function($q) use ($companyId) {
                        $q->where('company_id', $companyId);
                    })
                    ->get();
                
                // Sum quantities (use total_quantity if available, otherwise quantity)
                $totalStock = $entries->sum(function($entry) {
                    return $entry->total_quantity ?? $entry->quantity ?? 0;
                });
                
                // Update or create PurchaseStockAdjustment
                $stockAdjustment = PurchaseStockAdjustment::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'company_id' => $companyId
                    ],
                    [
                        'date' => now()->format('Y-m-d'),
                        'type' => 'quantity',
                        'status' => 'converted',
                        'net_quantity' => 0
                    ]
                );
                
                // Update net_quantity (only from purchase entries, excluding opening stock)
                $stockAdjustment->net_quantity = $totalStock;
                $stockAdjustment->save();
                
                $stats['products_updated']++;
                $stats['stock_adjustments_updated']++;
                
                \Log::info('Recalculated stock for product', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'total_stock' => $totalStock,
                    'entries_count' => $entries->count()
                ]);
            }
            
            \DB::commit();
            
            $message = sprintf(
                'Stock recalculation completed! Products updated: %d, Orphaned entries deleted: %d, Stock adjustments updated: %d',
                $stats['products_updated'],
                $stats['orphaned_entries_deleted'],
                $stats['stock_adjustments_updated']
            );
            
            return Reply::success($message);
            
        } catch (\Exception $e) {
            \DB::rollBack();
            
            \Log::error('Recalculate Stock Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Reply::error('Failed to recalculate stock: ' . $e->getMessage());
        }
    }

    /**
     * Bulk delete purchase entries
     */
    public function bulkDeletePurchaseEntries(Request $request)
    {
        // Check permission
        abort_403(user()->permission('delete_product') != 'all');
        
        try {
            $invoiceNumbers = $request->input('invoice_numbers', []);
            
            if (empty($invoiceNumbers) || !is_array($invoiceNumbers)) {
                return Reply::error('No invoices selected for deletion');
            }
            
            \DB::beginTransaction();
            
            $totalDeleted = 0;
            $deletedInvoices = [];
            
            foreach ($invoiceNumbers as $invoiceNumber) {
                // Get all products in this invoice
                $products = ProductPurchaseDetail::where('invoice_number', $invoiceNumber)->get();
                
                if ($products->isEmpty()) {
                    continue;
                }
                
                // Adjust stock for each product before deletion
                foreach ($products as $product) {
                    $totalQty = $product->total_quantity ?? $product->quantity;
                    
                    if ($product->product_id && $totalQty > 0) {
                        // Reduce stock by subtracting the quantity that was added
                        $stockAdjustment = PurchaseStockAdjustment::where('product_id', $product->product_id)
                            ->where('company_id', company()->id)
                            ->first();
                        
                        if ($stockAdjustment) {
                            $stockAdjustment->net_quantity = max(0, $stockAdjustment->net_quantity - $totalQty);
                            $stockAdjustment->save();
                        }
                    }
                }
                
                // Delete all products with this invoice number (force delete)
                foreach ($products as $product) {
                    $product->forceDelete(); // Force delete to bypass any soft delete
                }
                
                // Also try direct database delete as backup
                $deleted = \DB::table('product_purchase_details')
                    ->where('invoice_number', $invoiceNumber)
                    ->delete();
                
                $totalDeleted += $deleted;
                $deletedInvoices[] = $invoiceNumber;
                
                \Log::info('Bulk Delete Invoice:', [
                    'invoice' => $invoiceNumber,
                    'deleted_count' => $deleted
                ]);
            }
            
            \DB::commit();
            
            $message = sprintf(
                'Successfully deleted %d purchase entries from %d invoice(s)',
                $totalDeleted,
                count($deletedInvoices)
            );
            
            return Reply::success($message);
            
        } catch (\Exception $e) {
            \DB::rollBack();
            
            \Log::error('Bulk Delete Purchase Entries Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Reply::error('Failed to delete purchase entries: ' . $e->getMessage());
        }
    }

    /**
     * Show payment modal for purchase entry invoice
     */
    public function showPaymentModal(Request $request)
    {
        try {
            \Log::info('showPaymentModal called', ['invoice_number' => $request->invoice_number]);
            
            $invoiceNumber = $request->invoice_number;
            
            if (empty($invoiceNumber)) {
                \Log::error('Invoice number is empty');
                return response('<div class="alert alert-danger">Invoice number is required</div>', 400);
            }
            
            // Get all purchase entries for this invoice number with vendor relationship
            $companyId = company()->id;
            \Log::info('Company ID', ['company_id' => $companyId]);
            
            $purchaseEntries = ProductPurchaseDetail::where('invoice_number', $invoiceNumber)
                ->where('company_id', $companyId)
                ->with('vendor')
                ->get();
            
            \Log::info('Purchase entries found', ['count' => $purchaseEntries->count()]);
            
            if ($purchaseEntries->isEmpty()) {
                \Log::warning('No purchase entries found', ['invoice_number' => $invoiceNumber]);
                return response('<div class="alert alert-warning">Purchase entry invoice not found</div>', 404);
            }
            
            // Check permissions
            $addPaymentPermission = user()->permission('add_payments');
            \Log::info('Payment permission', ['permission' => $addPaymentPermission]);
            
            abort_403(!(
                $addPaymentPermission == 'all'
                || ($addPaymentPermission == 'added')
            ));
            
            // Calculate totals
            $totalAmount = $purchaseEntries->sum('total');
            
            // For purchase entries, payment_status is stored on each entry
            // Calculate paid amount based on payment_status
            // Note: This is a simplified approach - for multiple payments, we'd need a payments table
            $paymentStatus = $purchaseEntries->first()->payment_status ?? 'pending';
            $paidAmount = 0;
            
            // If status is 'paid', consider full amount paid
            // If status is 'partial', we'd need to track partial amounts (simplified for now)
            if ($paymentStatus == 'paid') {
                $paidAmount = $totalAmount;
            } elseif ($paymentStatus == 'partial') {
                // For partial payments, we'd ideally track amounts in a payments table
                // For now, we'll show the total as due
                $paidAmount = 0; // Will be updated when payment is recorded
            }
            
            $dueAmount = $totalAmount - $paidAmount;
            
            // Get vendor info - load relationship safely
            $firstEntry = $purchaseEntries->first();
            $vendor = null;
            if ($firstEntry && $firstEntry->vendor_id) {
                try {
                    $vendor = $firstEntry->vendor;
                } catch (\Exception $e) {
                    \Log::warning('Could not load vendor for purchase entry', [
                        'entry_id' => $firstEntry->id,
                        'vendor_id' => $firstEntry->vendor_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            $this->data['invoiceNumber'] = $invoiceNumber;
            $this->data['purchaseEntries'] = $purchaseEntries;
            $this->data['totalAmount'] = $totalAmount;
            $this->data['paidAmount'] = $paidAmount;
            $this->data['dueAmount'] = $dueAmount;
            $this->data['paymentStatus'] = $paymentStatus;
            $this->data['vendor'] = $vendor;
            
            \Log::info('Rendering payment modal view');
            return view('purchase::purchase-products.payment-modal', $this->data);
            
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            \Log::error('Authorization error in payment modal', [
                'error' => $e->getMessage()
            ]);
            return response('<div class="alert alert-danger">You do not have permission to access this page</div>', 403);
        } catch (\Exception $e) {
            \Log::error('Error in showPaymentModal', [
                'invoice_number' => $request->invoice_number ?? 'unknown',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response('<div class="alert alert-danger">Error loading payment modal: ' . htmlspecialchars($e->getMessage()) . '</div>', 500);
        }
    }

    /**
     * Update payment status for purchase entry invoice
     */
    public function updatePaymentStatus(Request $request)
    {
        try {
            \Log::info('Purchase entry payment status update request', $request->all());
            
            $request->validate([
                'invoice_number' => 'required|string',
                'payment_mode' => 'required|string',
                'payment_date' => 'required|date',
                'payment_amount' => 'required|numeric|min:0.01',
            ]);

            $invoiceNumber = $request->invoice_number;
            
            // Get all purchase entries for this invoice number
            $purchaseEntries = ProductPurchaseDetail::where('invoice_number', $invoiceNumber)
                ->where('company_id', company()->id)
                ->get();
            
            if ($purchaseEntries->isEmpty()) {
                return Reply::error('Purchase entry invoice not found');
            }
            
            // Check permissions
            $addPaymentPermission = user()->permission('add_payments');
            abort_403(!(
                $addPaymentPermission == 'all'
                || ($addPaymentPermission == 'added')
            ));

            $paymentAmount = floatval($request->payment_amount);
            $totalAmount = $purchaseEntries->sum('total');
            
            // Calculate new paid amount (simple approach - store in payment_status field)
            // For multiple payments, we'd need a separate payments table
            // For now, update payment_status based on payment amount
            $newPaymentStatus = 'pending';
            if ($paymentAmount >= $totalAmount) {
                $newPaymentStatus = 'paid';
            } elseif ($paymentAmount > 0) {
                $newPaymentStatus = 'partial';
            }
            
            // Update all purchase entries with same invoice_number
            ProductPurchaseDetail::where('invoice_number', $invoiceNumber)
                ->where('company_id', company()->id)
                ->update([
                    'payment_status' => $newPaymentStatus,
                    'mode_of_payment' => $request->payment_mode,
                ]);
            
            \Log::info('Purchase entry payment status updated', [
                'invoice_number' => $invoiceNumber,
                'payment_amount' => $paymentAmount,
                'total_amount' => $totalAmount,
                'new_status' => $newPaymentStatus
            ]);

            return Reply::successWithData(__('Payment recorded successfully'), [
                'invoice_number' => $invoiceNumber,
                'payment_amount' => number_format($paymentAmount, 2),
                'new_status' => $newPaymentStatus,
                'total_amount' => $totalAmount,
                'due_amount' => max(0, $totalAmount - $paymentAmount),
                'is_fully_paid' => $newPaymentStatus == 'paid'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error in purchase entry payment update', [
                'errors' => $e->errors()
            ]);
            return Reply::error($e->validator->errors()->first());
        } catch (\Exception $e) {
            \Log::error('Error updating purchase entry payment status', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Reply::error('Error updating payment status: ' . $e->getMessage());
        }
    }

}
