@php
    $addProductCategoryPermission = user()->permission('manage_product_category');
    $addProductSubCategoryPermission = user()->permission('manage_product_sub_category');
@endphp

<link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}">
<style>
    .product_type{
        margin-top: 0px !important;
    }
    .track_inventory_label {
        margin-left: 30px !important;
    }

    #purchase_price_div {
        margin-top: 46px !important;
    }

    #salePrice {
        margin-top: 38px !important;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
    
    .select-picker {
        width: 100% !important;
    }
    
    .height-35 {
        height: 35px;
    }
    
    .add-client {
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border-radius: 8px;
    }
    
    .row.mt-3 {
        margin-top: 1.5rem !important;
    }
    
    .row.mt-4 {
        margin-top: 2rem !important;
    }
    
    .section-divider {
        border-top: 1px solid #e0e0e0;
        margin: 2rem 0;
        padding-top: 1.5rem;
    }
    
    .form-section-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #f0f0f0;
    }
    
    .input-group-text {
        background-color: #f8f9fa;
        border-color: #dee2e6;
    }
    
    .form-control {
        border-color: #dee2e6;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }
    
    .form-control:focus {
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }
    
    /* Stock field styling */
    #current_stock {
        background-color: #e8f5e9 !important;
        border-color: #4caf50 !important;
        font-weight: 600;
        color: #2e7d32 !important;
        font-size: 15px;
    }
    
    #current_stock:focus {
        border-color: #4caf50 !important;
        box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, 0.25) !important;
    }
    
    #stock_unit {
        background-color: #e8f5e9 !important;
        border-color: #4caf50 !important;
        color: #2e7d32 !important;
        font-weight: 500;
    }
    
    .stock-info-box {
        background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
        border: 2px solid #4caf50;
        border-radius: 6px;
        padding: 12px;
        margin-bottom: 1rem;
    }
    
    .col-lg-2, .col-lg-4, .col-md-4, .col-md-6 {
        padding-left: 15px;
        padding-right: 15px;
    }
    
    /* Scheme quantity readonly styling */
    #quantity[readonly] {
        background-color: #e9ecef !important;
        cursor: not-allowed;
    }
    
    @media (max-width: 768px) {
        .form-group {
            margin-bottom: 1rem;
        }
        
        .row.mt-3 {
            margin-top: 1rem !important;
        }
    }

</style>

<div class="row">
    <div class="col-sm-12">
        <x-form id="save-product-form">
            @include('sections.password-autocomplete-hide')

            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-bottom-grey">
                    {{ isset($purchaseDetail) ? __('Edit Entry') : __('Add Entry') }}</h4>
                <div class="row p-20">
                    <div class="col-lg-12">
                        <!-- Hidden fields -->
                        <input type="hidden" id="hiddenProductId">
                        @if(isset($purchaseDetail))
                            <input type="hidden" name="purchase_detail_id" value="{{ $purchaseDetail->id ?? '' }}">
                        @endif
                        <input type="hidden" value="{{ isset($purchaseDetail) ? ($purchaseDetail->vendor_id ?? '') : '' }}" name="purchase_vendor_id">
                        <input type="hidden" name="type" value="goods" id="product_type_hidden">

                        <div class="row">
                            <div class="col-lg-4 col-md-6" hidden>
                                <x-forms.text fieldId="name" :fieldLabel="__('app.name')" fieldName="name" :fieldValue="($product->name ?? '')"
                                              fieldRequired="true" :fieldPlaceholder="__('placeholders.productName')">
                                </x-forms.text>
                            </div>
                            
                            <div class="col-lg-4 col-md-6">
                                <div class="form-group">
                                    <label class="f-14 text-dark-grey mb-12" for="product_id">
                                        {{ __('app.name') }} <sup class="text-danger">*</sup>
                                    </label>
                                    <select name="product_id"
                                            id="product_id"
                                            class="form-control select-picker"
                                            data-live-search="true"
                                            required>
                                        <option value="">-- Select Product --</option>
                                        @foreach ($products as $p)
                                            <option value="{{ $p->id }}"
                                                data-hsn-sac="{{ $p->hsn_sac_code ?? $p->sku ?? '' }}"
                                                data-stock="{{ $p->current_stock ?? 0 }}"
                                                data-unit="{{ $p->unit_type ?? '' }}"
                                                data-packing="{{ $p->packing ?? '' }}"
                                                data-vendor-id="{{ $p->vendor_id ?? '' }}"
                                                {{ (isset($purchaseDetail) && ($purchaseDetail->product_id ?? null) == $p->id) || (isset($product) && $product && ($product->id ?? null) == $p->id) ? 'selected' : '' }}>
                                                {{ $p->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Batch Selection Dropdown --}}
                            <div class="col-lg-4 col-md-6" id="batch_selection_container" style="display: none;">
                                <div class="form-group">
                                    <label class="f-14 text-dark-grey mb-12" for="batch_select">
                                        {{ __('Batch Number') }} <sup class="text-danger">*</sup>
                                    </label>
                                    <select name="batch_select" id="batch_select" class="form-control select-picker" data-live-search="true">
                                        <option value="">-- Select Batch --</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        <i class="fa fa-info-circle"></i> Select a batch to load purchase entry data
                                    </small>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6" id="sku_id">
                                <x-forms.text fieldId="sku" fieldLabel="HSN" fieldName="sku"
                                :fieldValue="($product->hsn_sac_code ?? $product->sku ?? '') ?: ((isset($purchaseDetail) && isset($purchaseDetail->product)) ? ($purchaseDetail->product->hsn_sac_code ?? $purchaseDetail->product->sku ?? '') : '')" :fieldPlaceholder="__('placeholders.hsnSac')">
                                </x-forms.text>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <div class="form-group">
                                    <label class="f-14 text-dark-grey mb-12 text-capitalize font-weight-600" for="current_stock">
                                        <i class="fa fa-cubes mr-1"></i> Current Stock
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend height-35">
                                            <span class="input-group-text border-grey f-15 px-3 text-dark" style="background-color: #e8f5e9; border-color: #4caf50;">
                                                <i class="fa fa-cubes text-success"></i>
                                            </span>
                                        </div>
                                        <input type="text" id="current_stock" 
                                            class="form-control height-35 f-15" 
                                            value="{{ isset($purchaseDetail) && isset($purchaseDetail->product) ? number_format($purchaseDetail->product->current_stock ?? 0, 0) : (isset($product) ? number_format($product->current_stock ?? 0, 0) : '0') }}" 
                                            placeholder="0" 
                                            readonly>
                                        <div class="input-group-append height-35">
                                            <span class="input-group-text border-grey f-13" id="stock_unit" style="background-color: #e8f5e9; border-color: #4caf50; color: #2e7d32; font-weight: 500;">
                                                {{ isset($purchaseDetail) && isset($purchaseDetail->product) && isset($purchaseDetail->product->unit) ? ($purchaseDetail->product->unit->unit_type ?? '') : (isset($product) && isset($product->unit) ? ($product->unit->unit_type ?? '') : '') }}
                                            </span>
                                        </div>
                                    </div>
                                    <small class="form-text text-muted"><i class="fa fa-info-circle"></i> Stock from product inventory</small>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <x-forms.label class="my-3" fieldId="" :fieldLabel="__('modules.unitType.unitType')">
                                </x-forms.label>
                                <x-forms.input-group>
                                    <select class="form-control unit_type select-picker" name="unit_type" id="unit_type_id"
                                            data-live-search="true">
                                        @foreach ($unit_types as $unit_type)
                                            <option @if ((isset($purchaseDetail) && ($purchaseDetail->unit_id ?? null) == $unit_type->id) || (isset($product) && ($unit_type->id ?? null) == ($product->unit_id ?? null))) selected @elseif (!isset($purchaseDetail) && !isset($product) && ($unit_type->default ?? 0) == 1) selected @endif value="{{ $unit_type->id }}">{{ ucwords($unit_type->unit_type) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </x-forms.input-group>
                            </div>

                            <div class="col-lg-4 col-md-6" style="display: none;">
                                <x-forms.label class="my-3" fieldId=""
                                               :fieldLabel="__('modules.productCategory.productCategory')">
                                </x-forms.label>
                                <x-forms.input-group>
                                    <select class="form-control select-picker" name="category_id"
                                            id="product_category_id" data-live-search="true">
                                        <option value="">--</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" @if (isset($product) && ($category->id ?? null) == ($product->category_id ?? null)) selected @endif>
                                                {{ mb_ucwords($category->category_name) }}</option>
                                        @endforeach
                                    </select>

                                    @if ($addProductCategoryPermission == 'all' || $addProductCategoryPermission == 'added')
                                        <x-slot name="append">
                                            <button id="add-category" type="button"
                                                    data-toggle="tooltip"
                                                    data-original-title="{{ __('app.add').' '.__('modules.productCategory.productCategory') }}"
                                                    class="btn btn-outline-secondary border-grey">@lang('app.add')</button>
                                        </x-slot>
                                    @endif
                                </x-forms.input-group>
                            </div>

                            <div class="col-lg-4 col-md-6" style="display: none;">
                                <x-forms.label class="my-3" fieldId=""
                                               :fieldLabel="__('modules.productCategory.productSubCategory')">
                                </x-forms.label>
                                <x-forms.input-group>
                                    <select class="form-control select-picker" name="sub_category_id" id="sub_category_id" data-live-search="true">
                                        <option value="">@lang('messages.noProductSubCategoryAdded')</option>
                                        @foreach ($subCategories as $subCategory)
                                            <option value="{{ $subCategory->id }}" @if (isset($product) && ($category->id ?? null) == ($product->sub_category_id ?? null)) selected @endif>
                                                {{ mb_ucwords($subCategory->category_name) }}</option>
                                        @endforeach
                                    </select>

                                    @if ($addProductSubCategoryPermission == 'all' || $addProductSubCategoryPermission == 'added')
                                        <x-slot name="append">
                                            <button id="add-sub-category" type="button" data-toggle="tooltip" data-original-title="{{ __('app.add').' '.__('modules.productCategory.productSubCategory') }}" class="btn btn-outline-secondary border-grey">@lang('app.add')</button>
                                        </x-slot>
                                    @endif
                                </x-forms.input-group>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <x-forms.label class="my-3" fieldId="vendor_id"
                                               :fieldLabel="__('Vendor')">
                                </x-forms.label>
                                <x-forms.input-group>
                                    <select class="form-control select-picker" name="vendor_id" id="vendor_id" data-live-search="true">
                                        <option value="">--</option>
                                        @if(isset($vendors))
                                            @foreach ($vendors as $vendor)
                                                <option value="{{ $vendor->id }}" @if (isset($purchaseDetail) && ($purchaseDetail->vendor_id ?? null) == $vendor->id) selected @endif>
                                                    {{ mb_ucwords($vendor->primary_name . ($vendor->company_name ? ' - ' . $vendor->company_name : '')) }}</option>
                                        @endforeach
                                        @endif
                                    </select>
                                    <x-slot name="append">
                                        <a href="{{ route('vendors.create') }}" data-redirect-url="no"
                                            class="btn btn-outline-secondary border-grey openRightModal"
                                                data-toggle="tooltip"
                                            data-original-title="{{ __('app.add').' '.__('Vendor') }}">@lang('app.add')</a>
                                    </x-slot>
                                </x-forms.input-group>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-lg-4 col-md-6">
                                <x-forms.text fieldId="packing" :fieldLabel="__('Packing')" fieldName="packing"
                                              :fieldValue="(isset($purchaseDetail) && isset($purchaseDetail->product)) ? ($purchaseDetail->product->packing ?? '') : (isset($product) ? ($product->packing ?? '') : '')"
                                              :fieldPlaceholder="__('e.g. 10x10 Tablets, 1x10 Strips, 500ml Bottle')">
                                </x-forms.text>
                            </div>
                        </div>
                        
                        <!-- Entry Details Section -->
                        <div class="section-divider">
                            <div class="form-section-title">Entry Details</div>
                        </div>
                        
                        <div class="row">
                        {{-- Scheme Toggle --}}
                        <div class="col-lg-12 mb-3">
                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="scheme_enabled" id="scheme_enabled" value="1" {{ (isset($purchaseDetail) && !($purchaseDetail->scheme_enabled ?? 0)) || old('scheme_enabled') === '0' ? '' : 'checked' }}>
                                    <label class="form-check-label f-14 text-dark-grey font-weight-600" for="scheme_enabled">
                                        <i class="fa fa-gift mr-1"></i> Enable Scheme (Total Quantity + Free Quantity)
                                    </label>
                                </div>
                                <small class="form-text text-muted">When enabled, you can enter Total Quantity and Free Quantity separately. The final quantity will be calculated automatically.</small>
                            </div>
                        </div>
                        
                        {{-- Scheme Fields (Total Quantity and Free Quantity) - Visible by default --}}
                        <div class="col-lg-3 col-md-4" id="scheme_fields">
                            <x-forms.number
                                fieldId="total_quantity"
                                fieldLabel="Total Quantity"
                                fieldName="total_quantity"
                                fieldPlaceholder="e.g. 100"
                                :fieldValue="isset($purchaseDetail) ? ($purchaseDetail->total_quantity ?? '') : (old('total_quantity') ?? '')"
                            />
                        </div>
                        
                        <div class="col-lg-3 col-md-4" id="free_quantity_field">
                            <x-forms.number
                                fieldId="free_quantity"
                                fieldLabel="Free Quantity"
                                fieldName="free_quantity"
                                fieldPlaceholder="e.g. 10"
                                :fieldValue="isset($purchaseDetail) ? ($purchaseDetail->free_quantity ?? '') : (old('free_quantity') ?? '')"
                            />
                        </div>
                        
                        {{-- Quantity --}}
                        <div class="col-lg-3 col-md-4" id="quantity_field">
                            <x-forms.number
                                fieldId="quantity"
                                fieldLabel="Final Quantity <small class='text-muted'>(Auto-calculated)</small>"
                                fieldName="quantity"
                                fieldPlaceholder="e.g. 100"
                                fieldRequired="true"
                                :fieldValue="isset($purchaseDetail) ? ($purchaseDetail->quantity ?? '') : (old('quantity') ?? '')"
                            />
                        </div>
                        {{-- Batch --}}
                        <div class="col-lg-3 col-md-4">
                            <x-forms.text
                                fieldId="batch"
                                fieldLabel="Batch Number"
                                fieldName="batch"
                                fieldPlaceholder="Batch No"
                                :fieldValue="isset($purchaseDetail) ? ($purchaseDetail->batch ?? '') : (old('batch') ?? '')"
                            />
                        </div>
                        {{-- Expiry --}}
                        <div class="col-lg-3 col-md-4">
                            <div class="form-group">
                                <label class="f-14 text-dark-grey mb-12" for="expiry">
                                    {{ __('Expiry Date') }}
                                </label>
                                <input
                                    type="date"
                                    name="expiry"
                                    id="expiry"
                                    class="form-control height-35 f-14"
                                    value="{{ old('expiry', isset($purchaseDetail) && isset($purchaseDetail->expiry) ? \Carbon\Carbon::parse($purchaseDetail->expiry)->format('Y-m-d') : (isset($product) && isset($product->expiry) ? \Carbon\Carbon::parse($product->expiry)->format('Y-m-d') : '')) }}"
                                >
                            </div>
                        </div>

                        {{-- PTS --}}
                        <div class="col-lg-3 col-md-4">
                            <x-forms.number
                                fieldId="pts"
                                fieldLabel="PTS (Price to Stockist)"
                                fieldName="pts"
                                fieldPlaceholder="e.g. 80"
                                step="0.01"
                                :fieldValue="isset($purchaseDetail) ? ($purchaseDetail->pts ?? '') : (old('pts') ?? '')"
                            />
                        </div>
                        </div>
                        
                        <div class="row mt-3">
                        {{-- PTR --}}
                        <div class="col-lg-3 col-md-4">
                            <x-forms.number
                                fieldId="ptr"
                                fieldLabel="PTR (Price to Retailer)"
                                fieldName="ptr"
                                fieldPlaceholder="e.g. 90"
                                step="0.01"
                                :fieldValue="isset($purchaseDetail) ? ($purchaseDetail->ptr ?? '') : (old('ptr') ?? '')"
                            />
                        </div>
                        {{-- DIS --}}
                        <div class="col-lg-3 col-md-4" style="display: none;">
                            <x-forms.number
                                fieldId="dis"
                                fieldLabel="DIS %"
                                fieldName="dis"
                                fieldPlaceholder="e.g. 5"
                                step="0.01"
                            />
                        </div>
                        </div>
                        <!-- Pricing Section -->
                        <div class="section-divider">
                            <div class="form-section-title">Pricing Information</div>
                            <small class="text-muted"><i class="fa fa-info-circle"></i> All amount calculations (discount, total, tax) are based on PTS (Price to Stockist)</small>
                        </div>

                        <div class="row mt-3">
                            <div class="col-lg-4 col-md-6">
                                <div class="form-group">
                                    <label class="f-14 text-dark-grey mb-12 text-capitalize"
                                        for="mrp">@lang('MRP (Maximum Retail Price)')<sup class="text-red f-14 mr-1">*</sup></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend height-35">
                                            <span class="input-group-text border-grey f-15 bg-additional-grey px-3 text-dark"
                                                id="basic-addon-mrp">{{ company()->currency->currency_code }}</span>
                                        </div>
                                        <input type="number" name="purchase_price" id="mrp"
                                            class="form-control height-35 f-15 readonly-background" 
                                            value="{{ isset($purchaseDetail) ? ($purchaseDetail->mrp ?? '') : ((isset($product) && isset($product->purchase_price)) ? $product->purchase_price : (old('purchase_price') ?? '')) }}" 
                                            placeholder="e.g. 100.00" aria-label="mrp" aria-describedby="basic-addon-mrp" min="0" step="0.01">
                                    </div>
                                </div>
                            </div>

                            {{-- Discount Type - Hidden, always percentage --}}
                            <div class="col-lg-4 col-md-6" style="display: none;">
                                <div class="form-group">
                                    <x-forms.label fieldId="discount_type"
                                                   :fieldLabel="__('Discount Type')">
                                    </x-forms.label>
                                    <x-forms.input-group>
                                        <select class="form-control select-picker" name="discount_type" id="discount_type" data-live-search="true">
                                            <option value="percentage" selected>@lang('Percentage (%)')</option>
                                        </select>
                                    </x-forms.input-group>
                                </div>
                            </div>
                            <input type="hidden" name="discount_type" value="percentage">

                            <div class="col-lg-4 col-md-6">
                                <div class="form-group">
                                    <label class="f-14 text-dark-grey mb-12 text-capitalize"
                                        for="discount">@lang('Discount (%)') <small class="text-muted">(on PTS)</small></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend height-35" id="discount_prepend">
                                            <span class="input-group-text border-grey f-15 bg-additional-grey px-3 text-dark"
                                                id="discount-addon">%</span>
                                        </div>
                                        <input type="number" name="discount" id="discount"
                                            class="form-control height-35 f-15 readonly-background" 
                                            value="{{ isset($purchaseDetail) ? ($purchaseDetail->discount ?? '') : (isset($product) ? ($product->discount ?? '') : (old('discount') ?? '')) }}"
                                            placeholder="e.g. 5" aria-label="discount" aria-describedby="discount-addon" 
                                            min="0" max="100" step="0.01">
                                    </div>
                                    <small class="form-text text-muted"><i class="fa fa-info-circle"></i> Discount calculated from PTS</small>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <div class="form-group">
                                    <label class="f-14 text-dark-grey mb-12 text-capitalize"
                                        for="total">@lang('Total (After Discount)') <small class="text-muted">(PTS - Discount)</small><sup class="text-red f-14 mr-1">*</sup></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend height-35">
                                            <span class="input-group-text border-grey f-15 bg-additional-grey px-3 text-dark"
                                                id="basic-addon-total">{{ company()->currency->currency_code }}</span>
                                        </div>
                                        <input type="number" name="total" id="total"
                                            class="form-control height-35 f-15 readonly-background" 
                                            value="{{ isset($purchaseDetail) ? ($purchaseDetail->total ?? 0) : ((isset($product) && isset($product->total)) ? $product->total : (old('total') ?? 0)) }}" 
                                            placeholder="Auto-calculated" aria-label="total" aria-describedby="basic-addon-total" 
                                            min="0" step="0.01" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tax & Additional Information Section -->
                        <div class="section-divider">
                            <div class="form-section-title">Tax & Additional Information</div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-4">
                                <x-forms.label fieldId="" :fieldLabel="__('modules.invoices.tax')">
                                </x-forms.label>
                                <x-forms.input-group>
                                    <select class="form-control tax select-picker" name="tax[]" id="tax_id"
                                            data-live-search="true" multiple="true">
                                        @foreach ($taxes as $tax)
                                            <option value="{{ $tax->id }}" @if ((isset($purchaseDetail) && isset($purchaseDetail->tax) && array_search($tax->id, json_decode($purchaseDetail->tax)) !== false) || (isset($product) && isset($product->taxes) && array_search($tax->id, json_decode($product->taxes)) !== false)) selected @endif>{{ strtoupper($tax->tax_name) }}:
                                                {{ $tax->rate_percent }}%
                                            </option>
                                        @endforeach
                                    </select>

                                    @if (user()->permission('manage_tax') == 'all')
                                        <x-slot name="append">
                                            <button id="add-tax" type="button"
                                            data-toggle="tooltip"
                                            data-original-title="{{ __('app.add').' '.__('modules.invoices.tax') }}"
                                            class="btn btn-outline-secondary border-grey">@lang('app.add')</button>
                                        </x-slot>
                                    @endif
                                </x-forms.input-group>
                            </div>
                        </div>

                        <!-- Tax Calculation Summary Section -->
                        <div class="section-divider">
                            <div class="form-section-title">Tax Calculation Summary</div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="card border-grey" style="background-color: #f8f9fa;">
                                    <div class="card-body">
                                        <table class="table table-borderless mb-0">
                                            <tbody>
                                                <tr>
                                                    <td class="text-dark-grey f-14 font-weight-600" style="width: 60%;">Subtotal (PTS - Discount):</td>
                                                    <td class="text-right">
                                                        <span class="f-15 font-weight-600 text-dark" id="summary_subtotal">{{ company()->currency->currency_symbol }}0.00</span>
                                                    </td>
                                                </tr>
                                                <tr id="tax_breakdown_row" style="display: none;">
                                                    <td class="text-dark-grey f-14 font-weight-600">Tax:</td>
                                                    <td class="text-right">
                                                        <div id="tax_breakdown" class="text-right"></div>
                                                        <span class="f-15 font-weight-600 text-dark" id="summary_tax_total">{{ company()->currency->currency_symbol }}0.00</span>
                                                    </td>
                                                </tr>
                                                <tr style="border-top: 2px solid #dee2e6; padding-top: 10px;">
                                                    <td class="text-dark-grey f-16 font-weight-bold">Final Amount (After Tax):</td>
                                                    <td class="text-right">
                                                        <span class="f-18 font-weight-bold text-primary" id="summary_final_amount">{{ company()->currency->currency_symbol }}0.00</span>
                                                        <input type="hidden" name="final_amount" id="final_amount_hidden" value="0">
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Details Section - Hidden -->
                        <div class="section-divider" style="display: none;">
                            <div class="form-section-title">Additional Details</div>
                        </div>

                           <div class="col-md-12 mt-3" style="display: none;">
                                <div class="form-group">
                                    <x-forms.label class="my-3" fieldId="description-text"
                                                   :fieldLabel="__('app.description')">
                                    </x-forms.label>
                                    <textarea name="description" id="description-text" rows="4"
                                              class="form-control" placeholder="{{ __('Enter description (optional)') }}">{{ isset($purchaseDetail) ? ($purchaseDetail->description ?? '') : (isset($product) ? ($product->description ?? '') : (old('description') ?? '')) }}</textarea>
                                </div>
                            </div>

                            <div class="col-lg-12 mt-3" style="display: none;">
                                <x-forms.file-multiple class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('purchase::modules.product.addImages')" fieldName="file" fieldId="file-upload-dropzones"/>
                            </div>
                            <input type ="hidden" name="add_more" value="false" id="add_more" />
                        </div>
                    </div>

                </div>

                <x-forms.custom-field :fields="$fields"></x-forms.custom-field>

                <div class="section-divider"></div>
                
                <x-form-actions>
                    <x-forms.button-primary id="save-product" class="mr-3" icon="check">@lang('app.save')
                    </x-forms.button-primary>
                    <x-forms.button-secondary class="mr-3" id="save-more-product" icon="check-double">@lang('app.saveAddMore')
                    </x-forms.button-secondary>
                    <x-forms.button-cancel :link="route('purchase-entries.index')" class="border-0">@lang('app.cancel')
                    </x-forms.button-cancel>
                </x-form-actions>
            </div>
        </x-form>

    </div>
</div>

<script>
    $(document).ready(function () {
        // Declare all variables with proper scope
        let defaultImage = '';
        let lastIndex = 0;
        let productID = null;
        let productDropzone;

        $('.unit_type, .tax, #discount_type, #product_id, #vendor_id, #batch_select').selectpicker();
        
        // ==================== BATCH SELECTION FUNCTIONALITY ====================
        
        // Load consolidated products on page load (for create mode)
        @if(!isset($purchaseDetail))
        loadConsolidatedProducts();
        @endif
        
        // Function to load consolidated products
        function loadConsolidatedProducts() {
            $.easyAjax({
                url: "{{ route('purchase-entries.products-consolidated') }}",
                type: "GET",
                blockUI: false,
                success: function(response) {
                    if (response.status == 'success' && response.data && response.data.length > 0) {
                        var options = '<option value="">-- Select Product --</option>';
                        response.data.forEach(function(product) {
                            var batchesJson = JSON.stringify(product.batches);
                            options += '<option value="' + product.product_id + '" ' +
                                'data-batches=\'' + batchesJson + '\'>' +
                                product.display_name +
                                '</option>';
                        });
                        $('#product_id').html(options).selectpicker('refresh');
                    }
                },
                error: function(response) {
                    console.log('Error loading consolidated products:', response);
                }
            });
        }
        
        // Function to populate batch dropdown
        function populateBatchDropdown(batches) {
            if (!batches || batches.length === 0) {
                $('#batch_selection_container').hide();
                return;
            }
            
            var options = '<option value="">-- Select Batch --</option>';
            batches.forEach(function(batch) {
                var batchValue = batch.batch || 'N/A';
                options += '<option value="' + batchValue + '" ' +
                    'data-purchase-entry-id="' + (batch.purchase_entry_id || '') + '">' +
                    batchValue +
                    '</option>';
            });
            
            $('#batch_select').html(options).selectpicker('refresh');
            $('#batch_selection_container').show();
        }
        
        // Handle product selection - show batch dropdown
        $('#product_id').on('changed.bs.select', function (e, clickedIndex, isSelected, previousValue) {
            var productId = $(this).val();
            var selectedOption = $(this).find('option:selected');
            var batches = selectedOption.data('batches');
            
            // Reset batch selection
            $('#batch_select').val('').selectpicker('refresh');
            $('#batch_selection_container').hide();
            
            // If batches exist, show batch dropdown
            if (batches && batches.length > 0) {
                populateBatchDropdown(batches);
            } else {
                // If no batches, just update product details normally
                updateProductDetails();
            }
        });
        
        // Handle batch selection - load purchase entry data
        $('#batch_select').on('changed.bs.select', function() {
            var productId = $('#product_id').val();
            var batch = $(this).val();
            
            if (productId && batch) {
                loadPurchaseEntryData(productId, batch);
            }
        });
        
        // Function to load purchase entry data by batch
        function loadPurchaseEntryData(productId, batch) {
            var url = "{{ route('purchase-entries.by-batch', [':productId', ':batch']) }}"
                .replace(':productId', productId)
                .replace(':batch', encodeURIComponent(batch));
            
            $.easyAjax({
                url: url,
                type: "GET",
                blockUI: true,
                success: function(response) {
                    if (response.status == 'success' && response.data) {
                        var data = response.data;
                        
                        // Populate form fields with purchase entry data
                        if (data.batch) $('#batch').val(data.batch);
                        if (data.expiry) $('#expiry').val(data.expiry);
                        if (data.quantity) $('#quantity').val(data.quantity);
                        if (data.mrp) $('#mrp').val(data.mrp);
                        if (data.pts) $('#pts').val(data.pts);
                        if (data.ptr) $('#ptr').val(data.ptr);
                        if (data.dis) $('#dis').val(data.dis);
                        if (data.discount) $('#discount').val(data.discount);
                        if (data.total) $('#total').val(data.total);
                        if (data.vendor_id) {
                            $('#vendor_id').val(data.vendor_id).selectpicker('refresh');
                        }
                        if (data.unit_id) {
                            $('#unit_type_id').val(data.unit_id).selectpicker('refresh');
                        }
                        if (data.packing) {
                            $('#packing').val(data.packing);
                        }
                        if (data.hsn_code) {
                            $('#sku').val(data.hsn_code);
                        }
                        if (data.description) {
                            $('#description').val(data.description);
                        }
                        
                        // Handle scheme fields
                        if (data.scheme_enabled == 1) {
                            $('#scheme_enabled').prop('checked', true);
                            if (data.total_quantity) $('#total_quantity').val(data.total_quantity);
                            if (data.free_quantity) $('#free_quantity').val(data.free_quantity);
                        } else {
                            $('#scheme_enabled').prop('checked', false);
                        }
                        toggleSchemeFields();
                        
                        // Handle tax selection
                        if (data.tax && Array.isArray(data.tax) && data.tax.length > 0) {
                            var taxIds = data.tax.map(function(t) {
                                return typeof t === 'object' ? t.id : t;
                            });
                            $('#tax_id').val(taxIds).selectpicker('refresh');
                        }
                        
                        // Update current stock
                        if (data.current_stock !== undefined) {
                            $('#current_stock').val(number_format(data.current_stock, 0));
                        }
                        
                        // Trigger calculations
                        calculateDIS();
                        calculateTotal(false);
                        calculateTaxAndSummary();
                        
                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            text: 'Purchase entry data loaded successfully',
                            toast: true,
                            position: 'top-end',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                error: function(response) {
                    Swal.fire({
                        icon: 'error',
                        text: response.responseJSON?.message || 'Error loading purchase entry data',
                        toast: true,
                        position: 'top-end',
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            });
        }

        Dropzone.autoDiscover = false;
        //Dropzone class
        productDropzone = new Dropzone("div#file-upload-dropzones", {
            dictDefaultMessage: "{{ __('app.dragDrop') }}",
            url: "{{ route('product-files.store') }}",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            paramName: "file",
            maxFilesize: DROPZONE_MAX_FILESIZE,
            maxFiles: 10,
            autoProcessQueue: false,
            uploadMultiple: true,
            addRemoveLinks: true,
            parallelUploads: 10,
            acceptedFiles: 'image/*',
            init: function () {
                productDropzone = this;
            }
        });
        productDropzone.on('sending', function (file, xhr, formData) {
            const currentProductID = $('#hiddenProductId').val();
            formData.append('product_id', currentProductID);
            formData.append('default_image', defaultImage);
            $.easyBlockUI();
        });
        productDropzone.on('uploadprogress', function () {
            $.easyBlockUI();
        });
        productDropzone.on('completemultiple', function () {
            window.location.href = '{{ route("purchase-entries.index") }}';
        });
        productDropzone.on('addedfile', function (file) {
            lastIndex++;

            const div = document.createElement('div');
            div.className = 'form-check-inline custom-control custom-radio mt-2 mr-3';
            const input = document.createElement('input');
            input.className = 'custom-control-input';
            input.type = 'radio';
            input.name = 'default_image';
            input.id = 'default-image-' + lastIndex;
            input.value = file.name;
            if (lastIndex == 1) {
                input.checked = true;
            }
            div.appendChild(input);

            const label = document.createElement('label');
            label.className = 'custom-control-label pt-1 cursor-pointer';
            label.innerHTML = "@lang('modules.makeDefaultImage')";
            label.htmlFor = 'default-image-' + lastIndex;
            div.appendChild(label);

            file.previewTemplate.appendChild(div);
        });

        // Function to update product details when product is selected
        function updateProductDetails() {
            const selectedValue = $('#product_id').val();
            if (selectedValue) {
                // Get the selected option's data attributes
                // For Bootstrap Selectpicker, we need to get the option from the original select element
                const $select = $('#product_id');
                const selectedOption = $select.find('option[value="' + selectedValue + '"]');
                
                // Get data attributes - try multiple methods for compatibility
                let hsnSacCode = '';
                if (selectedOption.length) {
                    // Try .attr() first for Bootstrap Selectpicker compatibility
                    hsnSacCode = selectedOption.attr('data-hsn-sac') || '';
                    // Fallback to .data() if .attr() doesn't work
                    if (!hsnSacCode) {
                        hsnSacCode = selectedOption.data('hsn-sac') || '';
                    }
                }
                
                const currentStock = selectedOption.data('stock') || selectedOption.attr('data-stock') || 0;
                const unitType = selectedOption.data('unit') || selectedOption.attr('data-unit') || '';
                const packing = selectedOption.data('packing') || selectedOption.attr('data-packing') || '';
                const vendorId = selectedOption.data('vendor-id') || selectedOption.attr('data-vendor-id') || '';
                
                // Update HSN - ensure it's set even if empty
                $('#sku').val(hsnSacCode || '');
                
                // Update Stock - format with commas
                const formattedStock = parseFloat(currentStock).toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });
                $('#current_stock').val(formattedStock);
                
                // Update stock unit
                $('#stock_unit').text(unitType);
                
                // Update Packing
                $('#packing').val(packing);
                
                // Update Vendor
                if (vendorId) {
                    $('#vendor_id').val(vendorId);
                    $('#vendor_id').selectpicker('refresh');
                }
            } else {
                $('#sku').val('');
                $('#current_stock').val('0');
                $('#stock_unit').text('');
                $('#packing').val('');
            }
        }
        
        // Note: Product selection is handled in batch selection section above (line 691)
        // The handler there checks for batches and either shows batch dropdown or calls updateProductDetails()
        
        // Initialize vendor dropdown on page load if editing
        @if(isset($purchaseDetail) && isset($purchaseDetail->vendor_id))
            setTimeout(function() {
                $('#vendor_id').val({{ $purchaseDetail->vendor_id }});
                $('#vendor_id').selectpicker('refresh');
            }, 100);
        @endif
        
        // Initialize product details on page load if product is already selected
        if ($('#product_id').val()) {
            updateProductDetails();
        }

        $('#product_category_id').on('change', function (e) {
            const categoryId = $(this).val();
            let url = "{{ route('get_product_sub_categories', ':id') }}";

            url = (categoryId) ? url.replace(':id', categoryId) : url.replace(':id', null);

            $.easyAjax({
                url: url,
                type: "GET",
                success: function (response) {
                    if (response.status == 'success') {
                        const options = [];
                        const rData = response.data;
                        $.each(rData, function (index, value) {
                            const selectData = '<option value="' + value.id + '">' + value.category_name + '</option>';
                            options.push(selectData);
                        });

                        $('#sub_category_id').html('<option value="">--</option>' + options);
                        $('#sub_category_id').selectpicker('refresh');
                    }
                }
            })
        });

        // ==================== PRICING CALCULATIONS ====================
        
        // Calculate DIS % = ((MRP - PTR) / MRP) * 100
        function calculateDIS() {
            const mrp = parseFloat($('#mrp').val()) || 0;
            const ptr = parseFloat($('#ptr').val()) || 0;
        
            if (mrp > 0 && ptr > 0) {
                const dis = ((mrp - ptr) / mrp) * 100;
                $('#dis').val(dis.toFixed(2));
            }
        }
        
        // Calculate Total = PTS - (PTS * Discount / 100)
        function calculateTotal(preserveExisting = false) {
            const pts = parseFloat($('#pts').val()) || 0;
            const discount = parseFloat($('#discount').val()) || 0;
            const existingTotal = parseFloat($('#total').val()) || 0;
            
            // If preserveExisting is true and we have an existing total, don't overwrite it
            // This is important for edit mode where we want to preserve database values
            if (preserveExisting && existingTotal > 0) {
                // Only update tax summary, don't recalculate total
                calculateTaxAndSummary();
                return;
            }
            
            // Only calculate if PTS has a value
            if (pts <= 0) {
                // If PTS is empty and we're preserving, keep existing total
                if (preserveExisting && existingTotal > 0) {
                    calculateTaxAndSummary();
                    return;
                }
                // In create mode, if PTS is empty, leave total empty (don't force 0)
                // User must enter PTS to calculate total
                calculateTaxAndSummary();
                return;
            }
            
            // Calculate discount as percentage of PTS
            const discountAmount = (pts * discount) / 100;
            
            // Total = PTS - Discount Amount
            let total = pts - discountAmount;
            
            // Ensure total is not negative
            if (total < 0) {
                total = 0;
            }
            
            $('#total').val(total.toFixed(2));
            
            // Update tax calculations after total is calculated
            calculateTaxAndSummary();
        }

        // Calculate tax and update summary section
        // Tax is calculated on the Total
        // Final Amount = Total + Tax Amount
        function calculateTaxAndSummary() {
            const subtotal = parseFloat($('#total').val()) || 0;
            const selectedTaxes = $('#tax_id').val() || [];
            const taxBreakdown = [];
            let totalTax = 0;
            const currencySymbol = '{{ company()->currency->currency_symbol }}';
            
            // Calculate tax for each selected tax (based on Total)
            if (selectedTaxes.length > 0 && subtotal > 0) {
                selectedTaxes.forEach(function(taxId) {
                    const taxOption = $('#tax_id option[value="' + taxId + '"]');
                    const taxText = taxOption.text();
                    
                    // Extract tax rate from text (format: "TAX_NAME: XX%")
                    const rateMatch = taxText.match(/(\d+\.?\d*)%/);
                    if (rateMatch) {
                        const taxRate = parseFloat(rateMatch[1]);
                        // Tax is calculated on Total
                        const taxAmount = (subtotal * taxRate) / 100;
                        totalTax += taxAmount;
                        
                        taxBreakdown.push({
                            name: taxText.split(':')[0].trim(),
                            rate: taxRate,
                            amount: taxAmount
                        });
                    }
                });
            }
            
            // Update summary display
            $('#summary_subtotal').text(currencySymbol + subtotal.toFixed(2));
            
            if (taxBreakdown.length > 0) {
                let breakdownHtml = '';
                taxBreakdown.forEach(function(tax) {
                    breakdownHtml += '<div class="f-13 text-dark-grey mb-1">' + tax.name + ' (' + tax.rate + '%): ' + currencySymbol + tax.amount.toFixed(2) + '</div>';
                });
                $('#tax_breakdown').html(breakdownHtml);
                $('#summary_tax_total').text(currencySymbol + totalTax.toFixed(2));
                $('#tax_breakdown_row').show();
            } else {
                $('#tax_breakdown').html('');
                $('#summary_tax_total').text(currencySymbol + '0.00');
                $('#tax_breakdown_row').hide();
            }
            
            // Final Amount = Total + Tax Amount
            const finalAmount = subtotal + totalTax;
            $('#summary_final_amount').text(currencySymbol + finalAmount.toFixed(2));
            $('#final_amount_hidden').val(finalAmount.toFixed(2));
        }

        // Reactive pricing calculations - trigger on ANY field change that affects calculations
        // This ensures calculations always run when user changes any relevant field
        
        // Fields that affect Total calculation (PTS, Discount)
        $('#pts, #discount').on('input change', function() {
            // Mark field as changed for edit mode detection
            $(this).data('changed', true);
            calculateTotal(false); // Always recalculate when user changes values
        });

        // Fields that affect DIS calculation (MRP, PTR)
        $('#mrp, #ptr').on('input change', function () {
            $(this).data('changed', true);
            calculateDIS();
        });

        // Tax selection affects tax summary
        $('#tax_id').on('changed.bs.select', function() {
            $(this).data('changed', true);
            calculateTaxAndSummary();
        });
        
        // Product selection affects stock, HSN, packing, vendor
        $('#product_id').on('changed.bs.select change', function() {
            $(this).data('changed', true);
            updateProductDetails();
            // Recalculate if PTS exists
            const pts = parseFloat($('#pts').val()) || 0;
            if (pts > 0) {
                calculateTotal(false);
                calculateTaxAndSummary();
            }
        });
        
        // Unit type change might affect calculations
        $('#unit_type_id').on('changed.bs.select change', function() {
            $(this).data('changed', true);
        });
        
        // Quantity changes affect scheme calculations
        $('#quantity, #total_quantity, #free_quantity').on('input change', function() {
            $(this).data('changed', true);
        });
        
        // General form change listener - ensures calculations run when ANY field changes
        // This is a safety net to catch any field changes we might have missed
        // It ensures that if user changes ANY field, calculations will run before submission
        $('#save-product-form').on('input change', 'input, select, textarea', function() {
            const fieldId = $(this).attr('id');
            const fieldName = $(this).attr('name');
            
            // Skip readonly fields and hidden fields from triggering immediate recalculation
            if ($(this).prop('readonly') || $(this).prop('type') === 'hidden') {
                return;
            }
            
            // Mark that form has been modified (used to ensure recalculation before submission)
            $(this).closest('form').data('modified', true);
            $(this).data('changed', true);
            
            // If it's a pricing-related field, trigger immediate recalculation
            // These are already handled above with specific listeners, but this ensures coverage
            if (['pts', 'discount', 'mrp', 'ptr', 'tax_id', 'purchase_price', 'total'].includes(fieldId) ||
                ['pts', 'discount', 'mrp', 'ptr', 'tax', 'purchase_price', 'total'].includes(fieldName)) {
                // Trigger recalculation for pricing fields
                const pts = parseFloat($('#pts').val()) || 0;
                const mrp = parseFloat($('#mrp').val()) || 0;
                
                if (pts > 0) {
                    calculateTotal(false);
                    calculateTaxAndSummary();
                } else if (mrp > 0) {
                    calculateTaxAndSummary();
                }
                
                if (mrp > 0 && parseFloat($('#ptr').val()) > 0) {
                    calculateDIS();
                }
            }
        });

        // ==================== SCHEME LOGIC ====================
        
        // Calculate Final Quantity = total_quantity + free_quantity
        function calculateSchemeQuantity() {
            if ($('#scheme_enabled').is(':checked')) {
                const totalQty = parseFloat($('#total_quantity').val()) || 0;
                const freeQty = parseFloat($('#free_quantity').val()) || 0;
                const finalQty = totalQty + freeQty;
                
                // Always update quantity when scheme is enabled
                $('#quantity').val(finalQty > 0 ? finalQty : 0);
            }
        }
        
        function toggleSchemeFields() {
            const schemeEnabled = $('#scheme_enabled').is(':checked');
            
            if (schemeEnabled) {
                // Show scheme fields
                $('#scheme_fields').show();
                $('#free_quantity_field').show();
                
                // Make quantity field readonly with grey background
                $('#quantity').prop('readonly', true);
                $('#quantity').css('background-color', '#e9ecef');
                $('#quantity_field').find('label').html('Final Quantity <small class="text-muted">(Auto-calculated)</small>');
                
                // Calculate quantity from total and free
                calculateSchemeQuantity();
            } else {
                // Hide scheme fields
                $('#scheme_fields').hide();
                $('#free_quantity_field').hide();
                
                // Make quantity field editable
                $('#quantity').prop('readonly', false);
                $('#quantity').css('background-color', '');
                $('#quantity_field').find('label').html('Quantity');
                
                // Clear scheme fields only if not editing
                @if(!isset($purchaseDetail))
                $('#total_quantity').val('');
                $('#free_quantity').val('');
                @endif
            }
        }
        
        // Initialize quantity field as readonly on page load if scheme is enabled
        function initializeSchemeFields() {
            if ($('#scheme_enabled').is(':checked')) {
                $('#quantity').prop('readonly', true);
                $('#quantity').css('background-color', '#e9ecef');
            }
        }
        
        // Handle scheme checkbox change
        $('#scheme_enabled').on('change', function() {
            toggleSchemeFields();
        });
        
        // Handle total quantity and free quantity changes - reactive
        $('#total_quantity, #free_quantity').on('input change', function() {
            calculateSchemeQuantity();
        });
        
        // Initialize scheme fields on page load
        if ($('#scheme_enabled').is(':checked')) {
            $('#quantity').prop('readonly', true);
            $('#quantity').css('background-color', '#e9ecef');
        }
        
        setTimeout(function() {
            toggleSchemeFields();
            initializeSchemeFields();
        }, 100);

        // ==================== SAVE FUNCTION WITH FORMDATA ====================
        
        function saveProduct(url, buttonSelector, method = 'POST') {
            // CRITICAL: ALWAYS recalculate totals before submission to ensure readonly fields have correct values
            // This ensures calculations are accurate regardless of which field the user changed
            // This is especially important in edit mode when user changes ANY field
            
            // Get current values
            const pts = parseFloat($('#pts').val()) || 0;
            const discount = parseFloat($('#discount').val()) || 0;
            const mrp = parseFloat($('#mrp').val()) || 0;
            const ptr = parseFloat($('#ptr').val()) || 0;
            
            // ALWAYS recalculate if we have pricing data (PTS or MRP)
            // This ensures calculations run even if user only changed other fields
            if (pts > 0 || mrp > 0) {
                // Recalculate DIS if MRP and PTR exist
                if (mrp > 0 && ptr > 0) {
                    calculateDIS();
                }
                
                // Recalculate Total and Tax Summary if PTS exists
                if (pts > 0) {
                    calculateTotal(false); // Force recalculation (don't preserve existing)
                    calculateTaxAndSummary(); // Update tax summary
                } else if (mrp > 0) {
                    // Even if PTS is empty, update tax summary if MRP exists
                    calculateTaxAndSummary();
                }
            } else {
                // If no pricing data, still update tax summary (might have tax selected)
                calculateTaxAndSummary();
            }
            
            // Use FormData for file uploads - reads all form fields including readonly
            const formData = new FormData($('#save-product-form')[0]);
            
            // CRITICAL: Ensure readonly fields are included (FormData includes readonly fields, but verify)
            // Always ensure these critical fields are present
            const mrpValue = $('#mrp').val();
            const totalValue = $('#total').val();
            const finalAmountValue = $('#final_amount_hidden').val();
            
            // Remove and re-add to ensure they're included
            if (formData.has('purchase_price')) {
                formData.delete('purchase_price');
            }
            if (mrpValue) {
                formData.append('purchase_price', mrpValue);
            }
            
            if (formData.has('total')) {
                formData.delete('total');
            }
            if (totalValue) {
                formData.append('total', totalValue);
            }
            
            if (formData.has('final_amount')) {
                formData.delete('final_amount');
            }
            if (finalAmountValue) {
                formData.append('final_amount', finalAmountValue);
            }
            
            // CRITICAL: Route now accepts both PUT and POST, so we can use POST directly
            // POST is more reliable with FormData and file uploads
            // For DELETE, still use method spoofing
            if (method === 'DELETE') {
                formData.append('_method', 'DELETE');
                method = 'POST'; // Change to POST for actual request
            } else if (method === 'PUT') {
                // Route accepts POST, so use POST directly (no spoofing needed)
                method = 'POST';
            }
            
            // CRITICAL: Ensure CSRF token is included in FormData
            const csrfToken = $('input[name="_token"]').val() || $('meta[name="csrf-token"]').attr('content');
            if (csrfToken && !formData.has('_token')) {
                formData.append('_token', csrfToken);
            }
            
            // Use POST directly (route accepts both PUT and POST, POST is more reliable with FormData)
            $.easyAjax({
                url: url,
                container: '#save-product-form',
                type: method, // POST for update (route accepts POST), POST for create, POST with _method for DELETE
                disableButton: true,
                blockUI: true,
                buttonSelector: buttonSelector,
                file: true,
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function(xhr) {
                    // CRITICAL: Set headers to ensure Laravel treats this as AJAX
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.setRequestHeader('Accept', 'application/json');
                    // Also set CSRF token in header as backup
                    if (csrfToken) {
                        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                    }
                },
                errorPosition: 'field',
                errorCallback: function(response) {
                    // Handle 422 validation errors and other errors
                    console.log('Error Response:', response); // Debug log
                    
                    // Check if response has errors object (Laravel validation errors)
                    let errors = null;
                    if (response.responseJSON && response.responseJSON.errors) {
                        errors = response.responseJSON.errors;
                    } else if (response.errors) {
                        errors = response.errors;
                    } else if (response.responseJSON) {
                        errors = response.responseJSON;
                    }
                    
                    // Show comprehensive error toast
                    if (errors && Object.keys(errors).length > 0) {
                        const errorMessages = [];
                        const fieldLabels = {
                            'product_id': 'Product',
                            'name': 'Product Name',
                            'purchase_price': 'MRP (Maximum Retail Price)',
                            'quantity': 'Quantity',
                            'pts': 'PTS (Price to Stockist)',
                            'ptr': 'PTR (Price to Retailer)',
                            'opening_stock': 'Opening Stock',
                            'vendor_id': 'Vendor',
                            'discount': 'Discount',
                            'discount_type': 'Discount Type',
                            'total': 'Total',
                            'batch': 'Batch Number',
                            'expiry': 'Expiry Date'
                        };
                        
                        $.each(errors, function(field, messages) {
                            const fieldLabel = fieldLabels[field] || field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                            if (Array.isArray(messages)) {
                                messages.forEach(function(msg) {
                                    errorMessages.push('<strong>' + fieldLabel + ':</strong> ' + msg);
                                });
                            } else {
                                errorMessages.push('<strong>' + fieldLabel + ':</strong> ' + messages);
                            }
                        });
                        
                        if (errorMessages.length > 0) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Validation Error',
                                html: '<div style="text-align: left; max-height: 400px; overflow-y: auto;">' +
                                      '<p>The following fields have errors:</p>' +
                                      '<ul style="margin-top: 10px;">' +
                                      errorMessages.map(function(msg) { return '<li>' + msg + '</li>'; }).join('') +
                                      '</ul></div>',
                                confirmButtonText: 'OK',
                                width: '600px'
                            });
                        }
                    } else {
                        // Generic error message
                        const errorMessage = (response.responseJSON && response.responseJSON.message) || 
                                           response.message || 
                                           'An error occurred while saving. Please check the console for details.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage
                        });
                    }
                },
                success: function(response) {
                    if (productDropzone.getQueuedFiles().length > 0) {
                        productID = response.productID;
                        defaultImage = response.defaultImage;
                        $('#hiddenProductId').val(productID);
                        productDropzone.processQueue();
                    }
                    else if(response.add_more == true) {
                        const right_modal_content = $.trim($(RIGHT_MODAL_CONTENT).html());

                        if(right_modal_content.length) {
                            $(RIGHT_MODAL_CONTENT).html(response.html.html);
                            $('#add_more').val(false);
                        }
                        else {
                            $('.content-wrapper').html(response.html.html);
                            init('.content-wrapper');
                            $('#add_more').val(false);
                        }
                    }
                    else{
                        if (response.redirectUrl == 'no') {
                            getProductOptions();
                            if (typeof closeTaskDetail === 'function') {
                                closeTaskDetail();
                            }
                        } else if ($(MODAL_XL).hasClass('show')) {
                            $(MODAL_XL).modal('hide');
                            window.location.reload();
                        } else {
                            // Check if we're in a right modal
                            const isRightModal = $(RIGHT_MODAL).hasClass('show') || $(RIGHT_MODAL_CONTENT).length > 0;
                            let redirectUrl = response.redirectUrl || '{{ route("purchase-entries.index") }}';
                            
                            // CRITICAL: Prevent redirecting to PUT-only routes (e.g., /purchase-entries/12)
                            // These routes only accept PUT/DELETE, not GET
                            const updateRoutePattern = /\/purchase-entries\/\d+$/;
                            if (updateRoutePattern.test(redirectUrl)) {
                                // If updating, redirect to edit page instead
                                @if(isset($purchaseDetail))
                                    redirectUrl = '{{ route("purchase-entries.edit", $purchaseDetail->id) }}';
                                @else
                                    redirectUrl = '{{ route("purchase-entries.index") }}';
                                @endif
                            }
                            
                            if (isRightModal) {
                                // If opened in right modal, close it and refresh the table
                                if (typeof closeTaskDetail === 'function') {
                                    closeTaskDetail();
                                } else if (typeof $(RIGHT_MODAL).modal === 'function') {
                                    $(RIGHT_MODAL).modal('hide');
                                }
                                
                                // Refresh the table if it exists, otherwise redirect
                                if (typeof showTable !== 'undefined' && typeof showTable === 'function') {
                                    setTimeout(function() {
                                        showTable();
                                    }, 300);
                                } else {
                                    window.location.href = redirectUrl;
                                }
                            } else {
                                window.location.href = redirectUrl;
                            }
                        }
                    }

                    if (typeof showTable !== 'undefined' && typeof showTable === 'function') {
                        showTable();
                    }
                }
            });
        }

        $('#save-more-product').on('click', function () {
            $('#add_more').val(true);
            const url = "{{ route('purchase-products.store') }}";
            saveProduct(url, "#save-more-product", 'POST');
        });

        $('#save-product').on('click', function() {
            @if(isset($purchaseDetail))
                const url = "{{ route('purchase-entries.update', $purchaseDetail->id) }}";
                // Use POST directly - route accepts both PUT and POST, POST is more reliable with FormData
                saveProduct(url, "#save-product", 'POST');
            @else
                const url = "{{ route('purchase-entries.store') }}";
                saveProduct(url, "#save-product", 'POST');
            @endif
        });

        function getProductOptions() {
            $.easyAjax({
                url: "{{ route('purchase_products.options') }}",
                type: "GET",
                success: function (response) {
                    $('#add-products').html(response.products);
                    $('#add-products').val('');
                    $('#add-products').selectpicker('refresh');
                }
            })
        }

        $('#add-category').on('click', function () {
            const url = "{{ route('productCategory.create') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        })

        $('#add-sub-category').on('click', function () {
            const url = "{{ route('productSubCategory.create') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('#add-tax').on('click', function () {
            const url = "{{ route('taxes.create') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        // Initialize calculations on page load
        // CRITICAL: In edit mode, NEVER overwrite database values
        // Only update tax summary and DIS calculation, never recalculate totals
        setTimeout(function() {
            @if(isset($purchaseDetail))
                // Edit mode: Only update display calculations, never overwrite DB values
                // Tax summary needs to be calculated for display
                calculateTaxAndSummary();
                // DIS calculation for display only
                calculateDIS();
            @else
                // Create mode: Only calculate if user has entered values
                // Don't force calculations on empty form
                const pts = parseFloat($('#pts').val()) || 0;
                if (pts > 0) {
                    calculateTotal();
                    calculateTaxAndSummary();
                }
                calculateDIS();
            @endif
        }, 300);

        // Initialize right modal if init function is available (single call)
        setTimeout(function() {
            try {
                if (typeof init === 'function') {
                    init(RIGHT_MODAL);
                } else if (typeof window.init === 'function') {
                    window.init(RIGHT_MODAL);
                }
            } catch (e) {
                // Silently fail if init is not available
                console.log('init function not available');
            }
        }, 200);
    });
</script>
