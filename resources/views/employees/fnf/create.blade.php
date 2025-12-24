<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">Initiate Full & Final Settlement</h5>
    <button type="button"  class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
</div>
<div class="modal-body">
    <x-form id="fnf-create-form" method="POST" class="ajax-form">
        <div class="row">
                    
                    <!-- Employee Selection -->
                    <div class="col-md-6">
                        <x-forms.select fieldId="user_id" :fieldLabel="'Select Employee'" fieldName="user_id" 
                                        search="true" fieldRequired="true">
                            <option value="">-- Select Employee --</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" 
                                        data-last-date="{{ $employee->employeeDetail->last_date ?? '' }}"
                                        data-salary="{{ $employee->employeeDetail->salary ?? 0 }}">
                                    {{ $employee->name }} - {{ $employee->email }}
                                    @if($employee->employeeDetail && $employee->employeeDetail->last_date)
                                        (Exit: {{ $employee->employeeDetail->last_date->format(company()->date_format) }})
                                    @endif
                                </option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    <!-- Resignation Type -->
                    <div class="col-md-6">
                        <x-forms.select fieldId="resignation_type" :fieldLabel="'Resignation Type'" 
                                        fieldName="resignation_type" fieldRequired="true">
                            <option value="">-- Select Type --</option>
                            @foreach($resignationTypes as $type)
                                <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    <!-- Resignation Date -->
                    <div class="col-md-6">
                        <x-forms.text fieldId="resignation_date" :fieldLabel="'Resignation Date'" 
                                      fieldName="resignation_date" fieldPlaceholder="Select date"
                                      :fieldValue="now()->format(company()->date_format)"></x-forms.text>
                    </div>

                    <!-- Last Working Day -->
                    <div class="col-md-6">
                        <x-forms.text fieldId="last_working_day" :fieldLabel="'Last Working Day'" 
                                      fieldName="last_working_day" fieldPlaceholder="Select date"
                                      fieldRequired="true"></x-forms.text>
                    </div>

                    <!-- Resignation Reason -->
                    <div class="col-md-12">
                        <x-forms.textarea :fieldLabel="'Resignation Reason'" fieldName="resignation_reason" 
                                          fieldId="resignation_reason" :fieldPlaceholder="'Enter reason for resignation'"
                                          fieldRequired="true">
                        </x-forms.textarea>
                    </div>

                    <div class="col-md-12">
                        <h5 class="heading-h5 text-dark mb-3 mt-3">Financial Details</h5>
                    </div>

                    <!-- Pending Bonus -->
                    <div class="col-md-6">
                        <x-forms.number fieldId="pending_bonus" :fieldLabel="'Pending Bonus'" 
                                        fieldName="pending_bonus" :fieldValue="0" minValue="0"></x-forms.number>
                    </div>

                    <!-- Pending Incentives -->
                    <div class="col-md-6">
                        <x-forms.number fieldId="pending_incentives" :fieldLabel="'Pending Incentives'" 
                                        fieldName="pending_incentives" :fieldValue="0" minValue="0"></x-forms.number>
                    </div>

                    <div class="col-md-12">
                        <h5 class="heading-h5 text-dark mb-3 mt-3">Deductions</h5>
                    </div>

                    <!-- Loan Outstanding -->
                    <div class="col-md-6">
                        <x-forms.number fieldId="loan_outstanding" :fieldLabel="'Loan Outstanding'" 
                                        fieldName="loan_outstanding" :fieldValue="0" minValue="0"></x-forms.number>
                    </div>

                    <!-- Advance Outstanding -->
                    <div class="col-md-6">
                        <x-forms.number fieldId="advance_outstanding" :fieldLabel="'Advance Outstanding'" 
                                        fieldName="advance_outstanding" :fieldValue="0" minValue="0"></x-forms.number>
                    </div>

                    <!-- Notice Period Recovery -->
                    <div class="col-md-6">
                        <x-forms.number fieldId="notice_period_recovery" :fieldLabel="'Notice Period Recovery'" 
                                        fieldName="notice_period_recovery" :fieldValue="0" minValue="0"></x-forms.number>
                    </div>

                    <!-- Other Deductions -->
                    <div class="col-md-6">
                        <x-forms.number fieldId="other_deductions" :fieldLabel="'Other Deductions'" 
                                        fieldName="other_deductions" :fieldValue="0" minValue="0"></x-forms.number>
                    </div>

                    <!-- Deduction Remarks -->
                    <div class="col-md-12">
                        <x-forms.textarea :fieldLabel="'Deduction Remarks'" fieldName="deduction_remarks" 
                                          fieldId="deduction_remarks" :fieldPlaceholder="'Enter deduction details'">
                        </x-forms.textarea>
                    </div>

                    <!-- Remarks -->
                    <div class="col-md-12">
                        <x-forms.textarea :fieldLabel="'Remarks'" fieldName="remarks" 
                                          fieldId="remarks" :fieldPlaceholder="'Additional remarks'">
                        </x-forms.textarea>
                    </div>

        </div>
    </x-form>
</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
    <x-forms.button-primary id="save-fnf-form" icon="check">Initiate FNF</x-forms.button-primary>
</div>

<script>
        $(function() {
            datepicker('#resignation_date', {
                position: 'bl',
                ...datepickerConfig
            });

            datepicker('#last_working_day', {
                position: 'bl',
                ...datepickerConfig
            });

            // Auto-fill last working day when employee is selected
            $('#user_id').on('change', function() {
                var lastDate = $(this).find(':selected').data('last-date');
                if (lastDate) {
                    $('#last_working_day').val(lastDate);
                }
            });

            // Submit form
            $('#save-fnf-form').click(function() {
                const url = "{{ route('fnf-settlements.store') }}";

                $.easyAjax({
                    url: url,
                    container: '#fnf-create-form',
                    type: "POST",
                    disableButton: true,
                    blockUI: true,
                    buttonSelector: "#save-fnf-form",
                    data: $('#fnf-create-form').serialize(),
                    success: function(response) {
                        if (response.status === 'success') {
                            $(MODAL_XL).modal('hide');
                            if (typeof showTable !== 'undefined') {
                                showTable();
                            } else {
                                window.location.reload();
                            }
                        }
                    }
                });
            });
        });
    </script>

