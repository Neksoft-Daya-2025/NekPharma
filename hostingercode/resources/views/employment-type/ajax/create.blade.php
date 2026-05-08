<div class="row">
    <div class="col-sm-12">
        <x-form id="save-employment-type-data-form">
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal border-bottom-grey">
                    Add Employment Type</h4>
                <div class="row p-20">
                    <div class="col-md-6">
                        <x-forms.text 
                            fieldId="name" 
                            :fieldLabel="__('app.name')" 
                            fieldName="name"
                            fieldRequired="true" 
                            :fieldPlaceholder="'e.g., Full Time, Consultant, Freelancer'">
                        </x-forms.text>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group my-3">
                            <x-forms.checkbox 
                                fieldId="requires_end_date" 
                                :fieldLabel="'Requires Contract/Internship End Date'" 
                                fieldName="requires_end_date"
                                :popover="'Check this if employees with this type need to specify an end date (like Contract or Internship)'">
                            </x-forms.checkbox>
                        </div>
                    </div>
                </div>

                <x-form-actions>
                    <x-forms.button-primary id="save-employment-type-form" class="mr-3" icon="check">@lang('app.save')
                    </x-forms.button-primary>
                    <x-forms.button-cancel :link="route('employment-types.index')" class="border-0">@lang('app.cancel')
                    </x-forms.button-cancel>
                </x-form-actions>

            </div>
        </x-form>

    </div>
</div>

<script>
    $(document).ready(function () {

        $('#save-employment-type-form').click(function () {

            const url = "{{ route('employment-types.store') }}";

            $.easyAjax({
                url: url,
                container: '#save-employment-type-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-employment-type-form",
                data: $('#save-employment-type-data-form').serialize(),
                success: function (response) {
                    if (response.status === 'success') {
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

        init(RIGHT_MODAL);
    });
</script>






