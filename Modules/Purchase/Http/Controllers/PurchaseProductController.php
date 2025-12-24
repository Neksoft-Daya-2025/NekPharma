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
use ReflectionClass;
use App\Models\ProductPurchaseDetail;

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

        $redirectUrl = urldecode($request->redirect_url);

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

        // Calculate current stock: ONLY from purchase entries (not including opening stock)
        $totalPurchaseEntries = ProductPurchaseDetail::where('product_id', $id)
            ->whereHas('product', function($q) {
                $q->where('company_id', company()->id);
            })
            ->sum('quantity');
        $this->currentStock = $totalPurchaseEntries;
        
        // Also check PurchaseStockAdjustment if available, but exclude opening stock
        $stockAdjustment = PurchaseStockAdjustment::where('product_id', $id)
            ->where('company_id', company()->id)
            ->first();
        if ($stockAdjustment) {
            // If stock adjustment exists, use net_quantity but ensure it's only from purchase entries
            // net_quantity in PurchaseStockAdjustment should already exclude opening stock
            // But to be safe, calculate directly from purchase entries
            $this->currentStock = $totalPurchaseEntries;
        }

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

        $stocks = PurchaseStockAdjustment::where('product_id', $product->id)->get();

        foreach ($stocks as $item) {
            $inventory = PurchaseInventory::where('id', $item->inventory_id)->first();
            $inventory->delete();

            $item->delete();
        }

        $product->delete();

        return Reply::successWithData(__('messages.deleteSuccess'), ['redirectUrl' => route('purchase-products.index')]);
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
        switch ($request->action_type) {
        case 'delete':
            $this->deleteRecords($request);

            return Reply::success(__('messages.deleteSuccess'));
        case 'change-status':
            abort_403(user()->permission('edit_product') != 'all');

            PurchaseProduct::withoutGlobalScope(ActiveScope::class)->whereIn('id', explode(',', $request->row_ids))->update(['status' => $request->product_status]);

            return Reply::success(__('messages.updateSuccess'));
        case 'change-purchase':
            abort_403(user()->permission('edit_product') != 'all');

            PurchaseProduct::whereIn('id', explode(',', $request->row_ids))->update(['allow_purchase' => $request->status]);

            return Reply::success(__('messages.updateSuccess'));
        default:
            return Reply::error(__('messages.selectAction'));
        }
    }

    protected function deleteRecords($request)
    {
        abort_403(user()->permission('delete_product') != 'all');

        $products = PurchaseProduct::whereIn('id', explode(',', $request->row_ids))->get();

        foreach ($products as $product) {
            $product->files()->each(function ($file) {
                $file->delete();
            });

            $stocks = PurchaseStockAdjustment::where('product_id', $product->id)->get();

            foreach ($stocks as $item) {
                $inventory = PurchaseInventory::where('id', $item->inventory_id)->first();
                $inventory->delete();

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
        $addPermission = user()->permission('add_inventory');
        abort_403(!in_array($addPermission, ['all', 'added']));

        $this->productId = request()->id;
        $this->product = PurchaseProduct::with('unit')->where('id', request()->id)->first();
        $this->adjustment = PurchaseStockAdjustment::where('product_id', request()->id)->first();
        $this->reasons = PurchaseStockAdjustmentReason::all();

        return view('purchase::purchase-products.ajax.update_inventory', $this->data);
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

        // FOR DROPDOWN - Include stock information
        $this->products = Product::with('unit', 'vendor')
            ->select('id', 'name', 'sku', 'hsn_sac_code', 'opening_stock', 'unit_id', 'packing', 'vendor_id')
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
        
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
            'purchase_price'        => 'required|numeric',
            'total'      => 'required|numeric',
        ]);
        $purchase = ProductPurchaseDetail::create([
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
            'created_by'    => user()->id,
            'scheme_enabled' => $request->has('scheme_enabled') ? 1 : 0,
            'total_quantity' => $request->total_quantity ?? null,
            'free_quantity'  => $request->free_quantity ?? null,
        ]);

        // Update stock: Recalculate total stock from opening_stock + all purchase entries
        // This ensures accuracy even if there are multiple stock adjustment records
        $product = Product::find($request->product_id);
        
        $openingStock = $product->opening_stock ?? 0;
        
        // Calculate total from all purchase entries
        $totalPurchaseEntries = \App\Models\ProductPurchaseDetail::where('product_id', $request->product_id)
            ->sum('quantity');
        
        $totalStock = $openingStock + $totalPurchaseEntries;
        
        // Get or create a main stock adjustment record for this product
        $stockAdjustment = PurchaseStockAdjustment::firstOrCreate(
            [
                'product_id' => $request->product_id,
                'company_id' => company()->id,
            ],
            [
                'date' => now()->format('Y-m-d'),
                'type' => 'quantity',
                'status' => 'converted',
                'net_quantity' => 0,
            ]
        );
        
        // Update with the recalculated total
        $stockAdjustment->net_quantity = $totalStock;
        $stockAdjustment->date = now()->format('Y-m-d');
        $stockAdjustment->type = 'quantity';
        $stockAdjustment->status = 'converted';
        $stockAdjustment->save();

        $redirectUrl = urldecode($request->redirect_url ?? '');

        // CRITICAL: Prevent redirecting to PUT-only routes (e.g., /purchase-entries/12)
        // These routes only accept PUT/DELETE, not GET
        if ($redirectUrl && preg_match('/\/purchase-entries\/\d+$/', $redirectUrl)) {
            // Redirect to index instead of PUT-only route
            $redirectUrl = route('purchase-entries.index');
        }

        if ($redirectUrl == '') {
            $redirectUrl = route('purchase-entries.index');
        }
        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => $redirectUrl]);
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

        // Recalculate stock after deletion
        $product = Product::find($purchaseDetail->product_id);
        $openingStock = $product->opening_stock ?? 0;
        
        // Calculate total from remaining purchase entries (after this one is deleted)
        $totalPurchaseEntries = \App\Models\ProductPurchaseDetail::where('product_id', $purchaseDetail->product_id)
            ->where('id', '!=', $id) // Exclude the one being deleted
            ->sum('quantity');
        
        $totalStock = $openingStock + $totalPurchaseEntries;
        
        $stockAdjustment = PurchaseStockAdjustment::firstOrCreate(
            ['product_id' => $purchaseDetail->product_id, 'company_id' => company()->id],
            ['date' => now()->format('Y-m-d'), 'type' => 'quantity', 'status' => 'converted', 'net_quantity' => 0]
        );
        $stockAdjustment->net_quantity = $totalStock;
        $stockAdjustment->save();

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

}
