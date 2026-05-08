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
        margin-top: 30px !important;
    }

    .purchase-info {
        margin-top: 13px !important;
    }
</style>

<div class="row">
    <div class="col-sm-12">
        <x-form id="save-product-data-form" method="PUT">
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-bottom-grey">
                    @lang('app.menu.editProducts') </h4>
                <div class="row p-20">
                    <div class="col-lg-12">
                        <!-- Hidden fields -->
                        <input type="hidden" id="hiddenProductId" value="{{ $product->id }}">
                        <input type="hidden" name="type" value="goods" id="product_type_hidden">

                        <div class="row">
                            <div class="col-md-4">
                                <x-forms.text fieldId="name" :fieldLabel="__('app.name')" fieldName="name"
                                    fieldRequired="true" fieldPlaceholder="product name"
                                    :fieldValue="$product->name">
                                </x-forms.text>
                            </div>

                            <div class="col-md-4" id="sku_id">
                                <x-forms.text fieldId="sku" fieldLabel="HSN" fieldName="sku"
                                            :fieldPlaceholder="__('placeholders.hsnSac')" :fieldValue="$product->hsn_sac_code ?? $product->sku ?? ''">
                                </x-forms.text>
                            </div>

                            <div class="col-lg-4 col-md-6">
                                <x-forms.label class="my-3" fieldId="" :fieldLabel="__('modules.unitType.unitType')">
                                </x-forms.label>
                                <x-forms.input-group>
                                    <select class="form-control select-picker" name="unit_type" id="unit_type_id"
                                            data-live-search="true">
                                        @foreach ($unit_types as $unit_type)
                                            <option value="{{ $unit_type->id }}" @if ($unit_type->id == $product->unit_id) selected @endif>{{ $unit_type->unit_type }}
                                            </option>
                                        @endforeach
                                    </select>
                                </x-forms.input-group>
                            </div>

                            {{-- Product Category - Hidden --}}
                            <div class="col-md-4" style="display: none;">
                                <x-forms.label class="my-3" fieldId="category_id"
                                    :fieldLabel="__('modules.productCategory.productCategory')">
                                </x-forms.label>
                                <x-forms.input-group>
                                    <select class="form-control select-picker height-35" name="category_id"
                                        id="product_category_id" data-live-search="true">
                                        <option value="">--</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}" @if ($category->id == $product->category_id) selected @endif>{{ mb_ucwords($category->category_name) }}</option>
                                        @endforeach
                                    </select>

                                    @if ($addProductCategoryPermission == 'all' || $addProductCategoryPermission == 'added')
                                        <x-slot name="append">
                                            <button id="add-category" type="button"
                                                class="btn btn-outline-secondary border-grey height-35">@lang('app.add')</button>
                                        </x-slot>
                                    @endif
                                </x-forms.input-group>
                            </div>

                            {{-- Product Sub Category - Hidden --}}
                            <div class="col-md-4" style="display: none;">
                                <x-forms.label class="my-3" fieldId=""
                                    :fieldLabel="__('modules.productCategory.productSubCategory')">
                                </x-forms.label>
                                <x-forms.input-group>
                                    <select class="form-control select-picker" name="sub_category_id"
                                        id="sub_category_id" data-live-search="true">
                                        <option value="">@lang('messages.noProductSubCategoryAdded')</option>
                                        @if ($product->category_id)
                                            @foreach ($product->category->subCategories as $category)
                                                <option value="{{ $category->id }}" @if ($category->id == $product->sub_category_id) selected @endif>{{ mb_ucwords($category->category_name) }}</option>
                                            @endforeach
                                        @endif
                                    </select>

                                    @if ($addProductSubCategoryPermission == 'all' || $addProductSubCategoryPermission == 'added')
                                        <x-slot name="append">
                                            <button id="add-sub-category" type="button"
                                                class="btn btn-outline-secondary border-grey"
                                                data-toggle="tooltip" data-original-title="{{ __('app.add').' '.__('modules.productCategory.productSubCategory') }}">@lang('app.add')</button>
                                        </x-slot>
                                    @endif
                                </x-forms.input-group>
                            </div>

                            {{-- Vendor - Hidden --}}
                            <div class="col-md-4" style="display: none;">
                                <x-forms.label class="my-3" fieldId="vendor_id"
                                               :fieldLabel="__('Vendor')">
                                </x-forms.label>
                                <x-forms.input-group>
                                    <select class="form-control select-picker" name="vendor_id" id="vendor_id" data-live-search="true">
                                        <option value="">--</option>
                                        @if(isset($vendors))
                                            @foreach ($vendors as $vendor)
                                                <option value="{{ $vendor->id }}" @if ($product->vendor_id == $vendor->id) selected @endif>
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

                            <div class="col-md-4">
                                <x-forms.text fieldId="packing" :fieldLabel="__('Packing')" fieldName="packing"
                                              :fieldValue="$product->packing ?? ''"
                                              :fieldPlaceholder="__('e.g. 10x10 Tablets, 1x10 Strips, 500ml Bottle')">
                                </x-forms.text>
                            </div>

                            <!-- Hidden fields for PTR and PTS -->
                            <input type="hidden" name="ptr" id="ptr" value="{{ $product->ptr ?? '' }}">
                            <input type="hidden" name="pts" id="pts" value="{{ $product->pts ?? '' }}">

                            <div class="col-lg-4 col-md-6 mt-3" style="display: none;">
                                <div class="form-group">
                                    <label class="f-14 text-dark-grey mb-12 text-capitalize"
                                        for="mrp">@lang('MRP (Maximum Retail Price)')</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend height-35">
                                            <span class="input-group-text border-grey f-15 bg-additional-grey px-3 text-dark"
                                                id="basic-addon-mrp">{{ company()->currency->currency_code }}</span>
                                        </div>
                                        <input type="number" name="purchase_price" id="mrp"
                                            class="form-control height-35 f-15 readonly-background" 
                                            value="{{ $product->purchase_price ? $product->purchase_price : 0 }}" 
                                            placeholder="e.g. 100.00" aria-label="mrp" aria-describedby="basic-addon-mrp" min="0" step="0.01">
                                    </div>
                                </div>
                            </div>

                            {{-- Discount Type - Hidden --}}
                            <div class="col-lg-4 col-md-6" style="display: none;">
                                <div class="form-group">
                                    <x-forms.label fieldId="discount_type"
                                                   :fieldLabel="__('Discount Type')">
                                    </x-forms.label>
                                    <x-forms.input-group>
                                        <select class="form-control select-picker" name="discount_type" id="discount_type" data-live-search="true">
                                            <option value="flat" @if ($product->discount_type == 'flat' || !$product->discount_type) selected @endif>@lang('Flat Amount')</option>
                                            <option value="percentage" @if ($product->discount_type == 'percentage') selected @endif>@lang('Percentage (%)')</option>
                                        </select>
                                    </x-forms.input-group>
                                </div>
                            </div>

                            {{-- Discount - Hidden --}}
                            <div class="col-lg-4 col-md-6" style="display: none;">
                                <div class="form-group">
                                    <label class="f-14 text-dark-grey mb-12 text-capitalize"
                                        for="discount">@lang('Discount')<span id="discount_label_suffix"></span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend height-35" id="discount_prepend">
                                            <span class="input-group-text border-grey f-15 bg-additional-grey px-3 text-dark"
                                                id="discount-addon">{{ company()->currency->currency_code }}</span>
                                        </div>
                                        <input type="number" name="discount" id="discount"
                                            class="form-control height-35 f-15 readonly-background" 
                                            value="{{ $product->discount ?? '' }}"
                                            placeholder="e.g. 10 or 5%" aria-label="discount" aria-describedby="discount-addon" 
                                            min="0" step="0.01">
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4 col-md-6" style="display: none;">
                                <div class="form-group">
                                    <label class="f-14 text-dark-grey mb-12 text-capitalize"
                                        for="total">@lang('Total')</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend height-35">
                                            <span class="input-group-text border-grey f-15 bg-additional-grey px-3 text-dark"
                                                id="basic-addon-total">{{ company()->currency->currency_code }}</span>
                                        </div>
                                        <input type="number" name="total" id="total"
                                            class="form-control height-35 f-15 readonly-background" 
                                            value="{{ $product->total ?? 0 }}" 
                                            placeholder="Auto-calculated" aria-label="total" aria-describedby="basic-addon-total" 
                                            min="0" step="0.01" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            {{-- Tax - Hidden --}}
                            <div class="col-md-4 my-3" style="display: none;">
                                <x-forms.label fieldId="multiselect" :fieldLabel="__('modules.invoices.tax')">
                                </x-forms.label>
                                <x-forms.input-group>
                                    <select class="form-control select-picker" name="tax[]" id="multiselect"
                                        data-live-search="true" multiple="true">
                                        @foreach ($taxes as $tax)
                                            <option value="{{ $tax->id }}" @if (isset($product->taxes) && array_search($tax->id, json_decode($product->taxes)) !== false) selected @endif>
                                                {{ strtoupper($tax->tax_name) }}: {{ $tax->rate_percent }}%
                                            </option>
                                        @endforeach
                                    </select>

                                    @if (user()->permission('manage_tax') == 'all')
                                        <x-slot name="append">
                                            <button id="add-tax" type="button"
                                                class="btn btn-outline-secondary border-grey"
                                                data-toggle="tooltip"
                                            data-original-title="{{ __('app.add').' '.__('modules.invoices.tax') }}">@lang('app.add')</button>
                                        </x-slot>
                                    @endif
                                </x-forms.input-group>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12 track_inventory_div" style="display: none;">
                                <input type="hidden" name="track_inventory" value="1" id="track_inventory_hidden">
                                <label class="f-14 text-dark-grey mb-12 font-weight-600">@lang('purchase::app.trackInventory')</label>
                            </div>

                            <div class="col-md-12 mt-3 track_inventory">
                                <div class="row">
                                    <div class="col-md-4" style="display: none;">
                                        <x-forms.number fieldId="opening_stock" :fieldLabel="__('purchase::app.openingStock')" fieldName="opening_stock" :fieldPlaceholder="__('purchase::placeholders.openingStock')"
                                         :popover="__('purchase::app.availableStock')" :fieldValue="$product->opening_stock ?? 0">
                                        </x-forms.number>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="f-14 text-dark-grey mb-12 text-capitalize font-weight-600" for="current_stock_display">
                                                <i class="fa fa-cubes mr-1"></i> Current Stock (Auto-calculated)
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend height-35">
                                                    <span class="input-group-text border-grey f-15 px-3 text-dark" style="background-color: #e8f5e9; border-color: #4caf50;">
                                                        <i class="fa fa-cubes text-success"></i>
                                                    </span>
                                                </div>
                                                <input type="text" id="current_stock_display" 
                                                    class="form-control height-35 f-15" 
                                                    value="{{ isset($currentStock) ? number_format($currentStock, 0) : '0' }}" 
                                                    placeholder="0" 
                                                    readonly
                                                    style="background-color: #e8f5e9; border-color: #4caf50; font-weight: 600; color: #2e7d32;">
                                                <div class="input-group-append height-35">
                                                    <span class="input-group-text border-grey f-13" style="background-color: #e8f5e9; border-color: #4caf50; color: #2e7d32; font-weight: 500;">
                                                        {{ $product->unit ? $product->unit->unit_type : '' }}
                                                    </span>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted"><i class="fa fa-info-circle"></i> Calculated from Purchase Entries only</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if(isset($batchStocks) && $batchStocks->count() > 0)
                            <div class="col-md-12 mt-3">
                                <div class="form-group">
                                    <label class="f-14 text-dark-grey mb-12 text-capitalize font-weight-600">Batch-wise stock</label>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Batch</th>
                                                    <th>Expiry</th>
                                                    <th class="text-right">Quantity</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($batchStocks as $bs)
                                                <tr>
                                                    <td>{{ $bs->batch ?? '–' }}</td>
                                                    <td>{{ $bs->expiry ? $bs->expiry->format('M Y') : '–' }}</td>
                                                    <td class="text-right">{{ number_format($bs->quantity, 2) }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <div class="col-md-12 mt-3" style="display: none;">
                                <div class="form-group">
                                    <x-forms.label class="my-3" fieldId="description-text"
                                        :fieldLabel="__('app.description')">
                                    </x-forms.label>
                                    <textarea name="description" id="description-text" rows="4" class="form-control f-14 w-100">{{ $product->description }}</textarea>
                                </div>
                            </div>

                            <div class="col-lg-12" style="display: none;">
                                <x-forms.file-multiple class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('purchase::modules.product.addImages')"
                                    fieldName="file" fieldId="file-upload-dropzone" />
                            </div>

                        </div>
                    </div>

                </div>

                <x-forms.custom-field :fields="$fields" :model="$productData"></x-forms.custom-field>

                <x-form-actions>
                    <x-forms.button-primary id="save-product-form" class="mr-3" icon="check">@lang('app.save')
                    </x-forms.button-primary>
                    <x-forms.button-cancel :link="route('purchase-products.index')" class="border-0">@lang('app.cancel')
                    </x-forms.button-cancel>
                </x-form-actions>
            </div>
        </x-form>

    </div>
</div>

<script>
    $(document).ready(function() {

        let lastIndex = 0;
        let defaultImage = '';
        var mockFile = {!! $images !!};

        Dropzone.autoDiscover = false;

        // Dropzone class
        productDropzone = new Dropzone("div#file-upload-dropzone", {
            dictDefaultMessage: "{{ __('app.dragDrop') }}",
            url: "{{ route('product-files.update_images') }}",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            paramName: "file",
            maxFilesize: DROPZONE_MAX_FILESIZE,
            maxFiles: DROPZONE_MAX_FILES,
            autoProcessQueue: false,
            uploadMultiple: true,
            addRemoveLinks: true,
            parallelUploads: DROPZONE_MAX_FILES,
            acceptedFiles: 'image/*',
            init: function() {
                productDropzone = this;
            },
            removedfile: function (file) {
                var index = mockFile.findIndex(x => x.name == file.name);
                mockFile.splice(index, 1);

                if(typeof(file.id) != 'undefined') {
                    Swal.fire({
                        title: "@lang('messages.sweetAlertTitle')",
                        text: "@lang('messages.recoverRecord')",
                        icon: 'warning',
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonText: "@lang('messages.confirmDelete')",
                        cancelButtonText: "@lang('app.cancel')",
                        customClass: {
                            confirmButton: 'btn btn-primary mr-3',
                            cancelButton: 'btn btn-secondary'
                        },
                        showClass: {
                            popup: 'swal2-noanimation',
                            backdrop: 'swal2-noanimation'
                        },
                        buttonsStyling: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            var token = "{{ csrf_token() }}";

                            var url = "{{ route('product-files.destroy', ':id') }}";
                            url = url.replace(':id', file.id);

                            $.easyAjax({
                                type: 'POST',
                                url: url,
                                data: {
                                    '_token': token,
                                    '_method': 'DELETE'
                                },
                                success: function(response) {
                                    //This will manually removed the file
                                    file.previewElement.remove();

                                    if ('{{ $product->default_image }}' == file.hashname) {
                                        let $radio = $('.custom-control-input');
                                        $radio[1].checked = true;
                                    }
                                }
                            });
                        }
                    });

                    return false;
                }

                //This will manually removed the file
                file.previewElement.remove();
            }
        });


        productDropzone.on('sending', function (file, xhr, formData) {
            // const productID = $('#hiddenProductId').val();
            var productID = '{{ $product->id }}';
            formData.append('product_id', productID);
            formData.append('default_image', defaultImage);

            if (mockFile.length > 0) {
                formData.append('uploaded_files', JSON.stringify(mockFile));
            }


            $.easyBlockUI();
        });
        productDropzone.on('uploadprogress', function () {
            $.easyBlockUI();
        });
        productDropzone.on('completemultiple', function () {
            window.location.href = '{{ route("purchase-products.index") }}';
        });

        productDropzone.on('removedfile', function () {
            var grp = $('div#file-upload-dropzone').closest(".form-group");
            var label = $('div#file-upload-box').siblings("label");
            $(grp).removeClass("has-error");
            $(label).removeClass("is-invalid");
        });

        productDropzone.on('error', function (file, message) {
            productDropzone.removeFile(file);
            var grp = $('div#file-upload-dropzone').closest(".form-group");
            var label = $('div#file-upload-box').siblings("label");
            $(grp).find(".help-block").remove();
            var helpBlockContainer = $(grp);

            if (helpBlockContainer.length == 0) {
                helpBlockContainer = $(grp);
            }

            helpBlockContainer.append('<div class="help-block invalid-feedback">' + message + '</div>');
            $(grp).addClass("has-error");
            $(label).addClass("is-invalid");

        });


        productDropzone.on('addedfile', function(file) {
            lastIndex++;

            var div = document.createElement('div');
            div.className = 'form-check-inline custom-control custom-radio mt-2';

            var input = document.createElement('input');
            input.className = 'custom-control-input';
            input.type = 'radio';
            input.name = 'default_image';
            input.id = 'default-image-'+lastIndex;
            input.value = file.hashname != undefined ? file.hashname : file.name;
            if (lastIndex == 1) {
                input.checked = true;
            }
            if ('{{ $product->default_image }}' == file.hashname) {
                input.checked = true;
            }
            div.appendChild(input);

            var label = document.createElement('label');
            label.className = 'custom-control-label pt-1 cursor-pointer';
            label.innerHTML = "@lang('modules.makeDefaultImage')";
            label.htmlFor = 'default-image-'+lastIndex;
            div.appendChild(label);

            file.previewTemplate.appendChild(div);
        });

            mockFile.forEach(file => {
            productDropzone.emit('addedfile', file);
            productDropzone.emit('thumbnail', file, file.file_url);
            productDropzone.files.push(file);
            productDropzone.emit("complete", file);
        });

        productDropzone.options.maxFiles = productDropzone.options.maxFiles - mockFile.length;

        // Product type is always 'goods', removed service type logic

        $('#save-product-form').click(function() {
            const url = "{{ route('purchase-products.update', [$product->id]) }}";
            
            // Track inventory is always enabled, no toggle needed
            
            let formData = $('#save-product-data-form').serialize();
            // Add _method=PUT for Laravel method spoofing
            formData += '&_method=PUT';

            $.easyAjax({
                url: url,
                container: '#save-product-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-product-form",
                file: true,
                data: formData,
                errorPosition: 'field',
                errorCallback: function(response) {
                    // Show comprehensive error toast
                    if (response.errors && Object.keys(response.errors).length > 0) {
                        let errorMessages = [];
                        let fieldLabels = {
                            'name': 'Product Name',
                            'purchase_price': 'MRP (Maximum Retail Price)',
                            'opening_stock': 'Opening Stock',
                            'expiry_date': 'Expiry Date',
                            'vendor_id': 'Vendor',
                            'discount': 'Discount',
                            'discount_type': 'Discount Type',
                            'total': 'Total'
                        };
                        
                        $.each(response.errors, function(field, messages) {
                            let fieldLabel = fieldLabels[field] || field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
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
                    } else if (response.message) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message
                        });
                    }
                },
                success: function(response) {
                    if (productDropzone.getQueuedFiles().length > 0) {
                        productID = response.productID
                        defaultImage = response.defaultImage;
                        $('#hiddenProductId').val(productID);
                        productDropzone.processQueue();
                    }
                    else{
                        if ($(MODAL_XL).hasClass('show')) {
                            $(MODAL_XL).modal('hide');
                            window.location.reload();
                        } else {
                            window.location.href = response.redirectUrl;
                        }
                    }
                }
            });
        });

        $('#product_category_id').change(function(e) {
            let categoryId = $(this).val();

            var url = "{{ route('get_product_sub_categories', ':id') }}";
            url = url.replace(':id', categoryId);

            $.easyAjax({
                url: url,
                type: "GET",
                success: function(response) {
                    if (response.status == 'success') {
                        var options = [];
                        var rData = [];
                        rData = response.data;
                        $.each(rData, function(index, value) {
                            var selectData = '';
                            selectData = '<option value="' + value.id + '">' + value
                                .category_name + '</option>';
                            options.push(selectData);
                        });

                        $('#sub_category_id').html('<option value="">--</option>' + options);
                        $('#sub_category_id').selectpicker('refresh');
                    }
                }
            })
        });

        $('#add-category').click(function() {
            const url = "{{ route('productCategory.create') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        })

        $('#add-sub-category').click(function() {
            const url = "{{ route('productSubCategory.create') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        $('#add-tax').click(function() {
            const url = "{{ route('taxes.create') }}";
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        // Vendor add button is handled via openRightModal in the view
            $(MODAL_LG + ' ' + MODAL_HEADING).html('...');
            $.ajaxModal(MODAL_LG, url);
        });

        // Update discount field based on discount type
        function updateDiscountField() {
            let discountType = $('#discount_type').val();
            let discountAddon = $('#discount-addon');
            
            if (discountType === 'percentage') {
                discountAddon.text('%');
                $('#discount_label_suffix').html('');
                $('#discount').attr('max', '100').attr('step', '0.01');
            } else {
                discountAddon.text('{{ company()->currency->currency_code }}');
                $('#discount_label_suffix').html('');
                $('#discount').removeAttr('max').attr('step', '0.01');
            }
        }

        // Calculate total dynamically: MRP - Discount (flat or percentage) = Total
        function calculateTotal() {
            let mrp = parseFloat($('#mrp').val()) || 0;
            let discount = parseFloat($('#discount').val()) || 0;
            let discountType = $('#discount_type').val();
            
            let discountAmount = 0;
            
            if (discountType === 'percentage') {
                // Calculate discount as percentage of MRP
                discountAmount = (mrp * discount) / 100;
            } else {
                // Flat discount amount
                discountAmount = discount;
            }
            
            let total = mrp - discountAmount;
            
            // Ensure total is not negative
            if (total < 0) {
                total = 0;
            }
            
            $('#total').val(total.toFixed(2));
        }

        // Bind calculation to input changes
        $('#mrp, #discount, #discount_type').on('input change', function() {
            updateDiscountField();
            calculateTotal();
        });

        // Initialize discount field on page load
        updateDiscountField();

        // Initialize selectpickers
        $('.select-picker, #discount_type, #vendor_id').selectpicker();

        // Initialize calculations on page load
        calculateTotal();

        // Scheme fields removed - no longer needed
        // Track inventory is always enabled, no toggle needed

        // Initialize right modal if init function is available
        // Use setTimeout to ensure main.js is loaded
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
        }, 100);
    });

</script>

<x-forms.custom-field-filejs/>
