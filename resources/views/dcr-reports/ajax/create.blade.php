<div class="modal-header">
    <h5 class="modal-title">@lang('app.add') DCR Report</h5>
    <button type="button" class="close" data-dismiss="modal">×</button>
</div>

<div class="modal-body">
    <x-form id="save-dcr-form" method="POST">
        <div class="row">
            <div class="col-md-12">
                <x-forms.datepicker fieldId="report_date" fieldLabel="Report Date" fieldName="report_date"
                    fieldRequired="true" :fieldValue="$reportDate ?? now()->format('Y-m-d')">
                </x-forms.datepicker>
            </div>

            <div class="col-md-12">
                <h6 class="mt-3 mb-2 text-dark-grey font-weight-bold">Doctor Visit</h6>
            </div>

            <div class="col-md-6">
                <x-forms.select fieldId="doctor_id" fieldLabel="Doctor" fieldName="doctors[]" search="true">
                    <option value="">--Select Doctor--</option>
                    @foreach($doctors as $doctor)
                        <option value="{{ $doctor->id }}">{{ $doctor->fullname }} ({{ $doctor->speciality }})</option>
                    @endforeach
                </x-forms.select>
            </div>

            <div class="col-md-6">
                <x-forms.text fieldId="speciality" fieldLabel="Speciality" fieldName="specialities[]">
                </x-forms.text>
            </div>

            <div class="col-md-4">
                <x-forms.text fieldId="product1" fieldLabel="Product 1" fieldName="product1[]">
                </x-forms.text>
            </div>

            <div class="col-md-4">
                <x-forms.text fieldId="product2" fieldLabel="Product 2" fieldName="product2[]">
                </x-forms.text>
            </div>

            <div class="col-md-4">
                <x-forms.text fieldId="product3" fieldLabel="Product 3" fieldName="product3[]">
                </x-forms.text>
            </div>

            <div class="col-md-6">
                <x-forms.text fieldId="pob" fieldLabel="POB (Point of Business)" fieldName="pob[]">
                </x-forms.text>
            </div>

            <div class="col-md-6">
                <x-forms.text fieldId="station" fieldLabel="Station" fieldName="doctor_stations[]">
                </x-forms.text>
            </div>

            <div class="col-md-12">
                <x-forms.textarea fieldLabel="Remark" fieldName="doctor_remarks[]" fieldId="doctor_remark">
                </x-forms.textarea>
            </div>
        </div>
    </x-form>
</div>

<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
    <x-forms.button-primary id="save-dcr" icon="check">@lang('app.save')</x-forms.button-primary>
</div>

<script>
    $('#save-dcr').click(function() {
        $.easyAjax({
            url: "{{ route('dcr-reports.store') }}",
            container: '#save-dcr-form',
            type: "POST",
            disableButton: true,
            buttonSelector: "#save-dcr",
            data: $('#save-dcr-form').serialize(),
            success: function(response) {
                if (response.status == 'success') {
                    window.location.href = response.redirectUrl;
                }
            }
        });
    });
</script>

