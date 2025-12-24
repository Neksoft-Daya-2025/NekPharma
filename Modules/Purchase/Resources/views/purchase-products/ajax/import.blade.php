<div class="row" id="import_table">
    <div class="col-sm-12">
        <x-form id="import-purchase-product-data-form">
            <div class="add-product bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal  border-bottom-grey">
                    @lang('app.importExcel') @lang('app.menu.products')</h4>
                <div class="col-sm-12 pt-2">
                    <div class="alert alert-warning" role="alert">
                        @lang('app.importProjectExcelInfo')
                    </div>
                    <div class="alert alert-info" role="alert">
                        <strong>@lang('app.tip'):</strong> @lang('Download sample file to see the correct format')
                        <a href="{{ route('purchase-products.import.sample') }}" class="btn btn-sm btn-primary ml-2" download>
                            <i class="fa fa-download"></i> @lang('Download Sample File')
                        </a>
                    </div>
                </div>
                <div class="row py-20">
                    <div class="col-md-12">
                        <x-forms.file :fieldLabel="__('modules.import.file')" fieldName="import_file" fieldId="purchase_product_import" />
                    </div>
                    <div class="col-md-12">
                        <x-forms.toggle-switch class="mr-0 mr-lg-12"
                            :fieldLabel="__('modules.import.containsHeadings')"
                            fieldName="heading"
                            fieldId="heading"
                            :checked="true"/>
                    </div>
                </div>
                <x-form-actions>
                    <x-forms.button-primary id="import-purchase-product-form" class="mr-3" icon="arrow-right">@lang('app.uploadNext')
                    </x-forms.button-primary>
                    <x-forms.button-cancel :link="route('purchase-products.index')" class="border-0">@lang('app.back')
                    </x-forms.button-cancel>

                </x-form-actions>
            </div>
        </x-form>

    </div>
</div>

<script>

    $(document).ready(function() {

        $("#purchase_product_import").dropify({
            messages: dropifyMessages
        });

        $('body').on('click', '#import-purchase-product-form', function() {
            const url = "{{ route('purchase-products.import.store') }}";

            $.easyAjax({
                url: url,
                container: '#import-purchase-product-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#import-purchase-product-form",
                file: true,
                data: $('#import-purchase-product-data-form').serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        if (response.view) {
                            $('#import_table').html(response.view);
                            // If batchId is provided, start progress tracking
                            if (response.batchId && typeof getProgress === 'function') {
                                setTimeout(function() {
                                    getProgress(response.batchId);
                                }, 500);
                            }
                        }
                    }
                },
                error: function(response) {
                    console.error('Import error:', response);
                }
            });
        });
    });
</script>

