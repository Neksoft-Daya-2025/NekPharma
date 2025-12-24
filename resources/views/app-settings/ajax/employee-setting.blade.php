<div class="p-4 col-lg-12 col-md-12 ntfcn-tab-content-left w-100 ">
    <div class="row">
        <div class="col-lg-4">
            <x-forms.text 
                fieldId="employee_id_prefix" 
                :fieldLabel="'Employee ID Prefix'" 
                fieldName="employee_id_prefix"
                :fieldValue="companyOrGlobalSetting()->employee_id_prefix ?? 'RVB'"
                :fieldPlaceholder="'e.g., RVB, STAFF, PHR'"
                fieldRequired="true"
                :popover="'This prefix will be automatically added before the employee ID number (e.g., RVB / 101, STAFF / 102)'">
            </x-forms.text>
        </div>
        
        <div class="col-lg-12">
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> 
                <strong>Example:</strong> If you set prefix as "RVB", employee IDs will be generated as: <strong>RVB / 101</strong>, <strong>RVB / 102</strong>, <strong>RVB / 103</strong>, etc.
            </div>
        </div>
    </div>
</div>

<!-- SAVE SETTINGS FORM -->
<x-form id="editSettings" method="PUT" class="ajax-form">
    <input type="hidden" name="page" value="employee-setting">
    <x-form-actions>
        <x-forms.button-primary id="save-form" class="mr-3" icon="check">@lang('app.save')
        </x-forms.button-primary>
    </x-form-actions>
</x-form>

