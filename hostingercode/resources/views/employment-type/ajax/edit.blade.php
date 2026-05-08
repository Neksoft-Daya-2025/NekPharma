<div class="row">
    <div class="col-sm-12">
        <x-form id="update-employment-type-data-form" method="PUT">
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal border-bottom-grey">
                    Edit Employment Type</h4>
                <div class="row p-20">
                    <div class="col-md-6">
                        <x-forms.text 
                            fieldId="name" 
                            :fieldLabel="__('app.name')" 
                            fieldName="name"
                            :fieldValue="$employmentType->name"
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
                                :checked="$employmentType->requires_end_date"
                                :popover="'Check this if employees with this type need to specify an end date'">
                            </x-forms.checkbox>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group my-3">
                            <x-forms.checkbox 
                                fieldId="is_active" 
                                :fieldLabel="__('app.status') . ' (Active)'" 
                                fieldName="is_active"
                                :checked="$employmentType->is_active">
                            </x-forms.checkbox>
                        </div>
                    </div>
                </div>

                <x-form-actions>
                    <x-forms.button-primary id="update-employment-type-form" class="mr-3" icon="check">@lang('app.update')
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

        $('#update-employment-type-form').click(function () {

            const url = "{{ route('employment-types.update', $employmentType->id) }}";

            $.easyAjax({
                url: url,
                container: '#update-employment-type-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#update-employment-type-form",
                data: $('#update-employment-type-data-form').serialize(),
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






