<div class="row">
    <div class="col-sm-12">
        <x-form id="save-batch-form" method="PUT">
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal border-bottom-grey">
                    @lang('app.editBatch')
                </h4>
                <div class="row p-20">
                    <div class="col-md-6 col-lg-4">
                        <x-forms.text fieldId="batch" :fieldLabel="__('app.batch')" fieldName="batch"
                            :fieldPlaceholder="__('app.batch')" :fieldValue="$batch->batch" />
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <x-forms.datepicker fieldId="expiry" :fieldLabel="__('app.expiry')" fieldName="expiry"
                            :fieldPlaceholder="__('placeholders.date')"
                            :fieldValue="$batch->expiry ? $batch->expiry->format(company()->date_format) : ''" custom="true" />
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <x-forms.number fieldId="quantity" :fieldLabel="__('app.totalQuantity')" fieldName="quantity"
                            fieldRequired="true" :fieldValue="$batch->quantity" />
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <x-forms.number fieldId="pts" :fieldLabel="__('app.pts')" fieldName="pts"
                            :fieldValue="$batch->pts" />
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <x-forms.number fieldId="ptr" :fieldLabel="__('app.ptr')" fieldName="ptr"
                            :fieldValue="$batch->ptr" />
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <x-forms.number fieldId="mrp" :fieldLabel="__('app.mrp')" fieldName="mrp"
                            :fieldValue="$batch->mrp" />
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <x-forms.number fieldId="dis" :fieldLabel="__('app.discount')" fieldName="dis"
                            :fieldValue="$batch->dis" />
                    </div>
                </div>
                <x-form-actions>
                    <x-forms.button-primary id="save-batch-btn" class="mr-3" icon="check">@lang('app.save')</x-forms.button-primary>
                    <x-forms.button-cancel :link="'javascript:;'" class="border-0 btn-cancel">@lang('app.cancel')</x-forms.button-cancel>
                </x-form-actions>
            </div>
        </x-form>
    </div>
</div>

<script>
    $(function() {
        $('.custom-date-picker').each(function(ind, el) {
            if (typeof datepicker !== 'undefined') {
                datepicker(el, { position: 'bl', ...(typeof datepickerConfig !== 'undefined' ? datepickerConfig : {}) });
            }
        });

        $('#save-batch-btn').click(function() {
            var url = "{{ route('cfa-stockist-inventory.batches.update', $batch->id) }}";
            var data = $('#save-batch-form').serialize() + '&_method=PUT';

            $.easyAjax({
                url: url,
                container: '#save-batch-form',
                type: 'POST',
                disableButton: true,
                blockUI: true,
                buttonSelector: '#save-batch-btn',
                data: data,
                success: function(response) {
                    if (response.status == 'success') {
                        if (response.redirectUrl) {
                            window.location.href = response.redirectUrl;
                        } else {
                            $('.close-modal').trigger('click');
                            window.location.reload();
                        }
                    }
                }
            });
        });
    });
</script>
