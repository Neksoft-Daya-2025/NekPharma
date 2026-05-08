<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">Edit FNF Settlement</h5>
    <button type="button"  class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
</div>
<div class="modal-body">
    <x-form id="fnf-edit-form" method="PUT" class="ajax-form">
        <div class="row">
            
            <div class="col-md-12">
                <h6 class="heading-h6 text-dark mb-3">Financial Adjustments</h6>
            </div>

            <!-- Earned Salary -->
            <div class="col-md-6">
                <x-forms.number fieldId="earned_salary" :fieldLabel="'Earned Salary'" 
                                fieldName="earned_salary" :fieldValue="$fnfSettlement->earned_salary" minValue="0"></x-forms.number>
            </div>

            <!-- EL Encashment -->
            <div class="col-md-6">
                <x-forms.number fieldId="leave_encashment_amount" :fieldLabel="'EL Encashment Amount'" 
                                fieldName="leave_encashment_amount" :fieldValue="$fnfSettlement->leave_encashment_amount" minValue="0"></x-forms.number>
            </div>

            <!-- Pending Bonus -->
            <div class="col-md-6">
                <x-forms.number fieldId="pending_bonus" :fieldLabel="'Pending Bonus'" 
                                fieldName="pending_bonus" :fieldValue="$fnfSettlement->pending_bonus" minValue="0"></x-forms.number>
            </div>

            <!-- Pending Incentives -->
            <div class="col-md-6">
                <x-forms.number fieldId="pending_incentives" :fieldLabel="'Pending Incentives'" 
                                fieldName="pending_incentives" :fieldValue="$fnfSettlement->pending_incentives" minValue="0"></x-forms.number>
            </div>

            <div class="col-md-12">
                <h6 class="heading-h6 text-dark mb-3 mt-3">Deductions</h6>
            </div>

            <!-- Loan Outstanding -->
            <div class="col-md-6">
                <x-forms.number fieldId="loan_outstanding" :fieldLabel="'Loan Outstanding'" 
                                fieldName="loan_outstanding" :fieldValue="$fnfSettlement->loan_outstanding" minValue="0"></x-forms.number>
            </div>

            <!-- Advance Outstanding -->
            <div class="col-md-6">
                <x-forms.number fieldId="advance_outstanding" :fieldLabel="'Advance Outstanding'" 
                                fieldName="advance_outstanding" :fieldValue="$fnfSettlement->advance_outstanding" minValue="0"></x-forms.number>
            </div>

            <!-- Notice Period Recovery -->
            <div class="col-md-6">
                <x-forms.number fieldId="notice_period_recovery" :fieldLabel="'Notice Period Recovery'" 
                                fieldName="notice_period_recovery" :fieldValue="$fnfSettlement->notice_period_recovery" minValue="0"></x-forms.number>
            </div>

            <!-- Other Deductions -->
            <div class="col-md-6">
                <x-forms.number fieldId="other_deductions" :fieldLabel="'Other Deductions'" 
                                fieldName="other_deductions" :fieldValue="$fnfSettlement->other_deductions" minValue="0"></x-forms.number>
            </div>

            <!-- Deduction Remarks -->
            <div class="col-md-12">
                <x-forms.textarea :fieldLabel="'Deduction Remarks'" fieldName="deduction_remarks" 
                                  fieldId="deduction_remarks" :fieldValue="$fnfSettlement->deduction_remarks">
                </x-forms.textarea>
            </div>

            <!-- HR Notes -->
            <div class="col-md-12">
                <x-forms.textarea :fieldLabel="'HR Notes'" fieldName="hr_notes" 
                                  fieldId="hr_notes" :fieldValue="$fnfSettlement->hr_notes">
                </x-forms.textarea>
            </div>

            <!-- Remarks -->
            <div class="col-md-12">
                <x-forms.textarea :fieldLabel="'Remarks'" fieldName="remarks" 
                                  fieldId="remarks" :fieldValue="$fnfSettlement->remarks">
                </x-forms.textarea>
            </div>

        </div>
    </x-form>
</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
    <x-forms.button-primary id="save-fnf-edit" icon="check">@lang('app.save')</x-forms.button-primary>
</div>

<script>
    $('#save-fnf-edit').click(function() {
        const url = "{{ route('fnf-settlements.update', $fnfSettlement->id) }}";

        $.easyAjax({
            url: url,
            container: '#fnf-edit-form',
            type: "PUT",
            disableButton: true,
            blockUI: true,
            buttonSelector: "#save-fnf-edit",
            data: $('#fnf-edit-form').serialize(),
            success: function(response) {
                if (response.status === 'success') {
                    $(MODAL_LG).modal('hide');
                    window.location.reload();
                }
            }
        });
    });
</script>

