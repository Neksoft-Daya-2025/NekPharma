<div class="modal-header">
    <h5 class="modal-title">@lang('app.add') Stockist</h5>
    <button type="button" class="close" data-dismiss="modal">×</button>
</div>

@php
    $headquarterOptions = $headquarters ?? collect();
    $headquarterStations = $headquarterStations ?? [];
    $defaultHeadquarterId = $defaultHeadquarterId ?? null;
@endphp

<div class="modal-body">
    <x-form id="save-stockist-form" method="POST">
        <div class="row">
            <div class="col-md-12">
                <x-forms.text fieldId="shopname" fieldLabel="Shop Name" fieldName="shopname"
                    fieldRequired="true" fieldPlaceholder="Enter shop/firm name">
                </x-forms.text>
            </div>

            <div class="col-md-6">
                <x-forms.text fieldId="fullname" fieldLabel="Stockist Name" fieldName="fullname"
                    fieldPlaceholder="Enter stockist name">
                </x-forms.text>
            </div>

            <div class="col-md-6">
                <x-forms.tel fieldId="mobile" fieldLabel="Stockist Mobile" fieldName="mobile"
                    fieldPlaceholder="Enter stockist mobile number">
                </x-forms.tel>
            </div>

            <div class="col-md-6">
                <x-forms.text fieldId="owner_name" fieldLabel="Owner Name" fieldName="owner_name"
                    fieldRequired="true" fieldPlaceholder="Enter owner name">
                </x-forms.text>
            </div>

            <div class="col-md-6">
                <x-forms.tel fieldId="owner_mobile" fieldLabel="Owner Mobile" fieldName="owner_mobile"
                    fieldRequired="true" fieldPlaceholder="Enter owner mobile number">
                </x-forms.tel>
            </div>

            <div class="col-md-6">
                <x-forms.label class="my-3" fieldId="headquarter_id" :fieldLabel="'HeadQuarter'" fieldRequired="true">
                </x-forms.label>
                <select class="form-control select-picker" name="headquarter_id" id="headquarter_id" data-live-search="true" required>
                    <option value="">-- Select HeadQuarter --</option>
                    @foreach($headquarterOptions as $hq)
                        <option value="{{ $hq->id }}" @selected($defaultHeadquarterId === $hq->id)>
                            {{ $hq->name }}
                            @if($hq->area) ({{ $hq->area->name }}) @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <x-forms.label class="my-3" fieldId="station_type" :fieldLabel="'Station Type'" fieldRequired="true">
                </x-forms.label>
                <div class="d-flex align-items-center flex-wrap">
                    <div class="custom-control custom-radio mr-4">
                        <input type="radio" class="custom-control-input" name="station_type" id="type_headquarter" value="headquarter" checked>
                        <label class="custom-control-label" for="type_headquarter">Headquarter</label>
                    </div>
                    <div class="custom-control custom-radio mr-4">
                        <input type="radio" class="custom-control-input" name="station_type" id="type_exstation" value="exstation">
                        <label class="custom-control-label" for="type_exstation">Ex-Station</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" class="custom-control-input" name="station_type" id="type_outstation" value="outstation">
                        <label class="custom-control-label" for="type_outstation">Out-Station</label>
                    </div>
                </div>
                <small class="form-text text-muted">Select station type for this stockist</small>
            </div>

            <div class="col-md-6" id="exstation_field" style="display: none;">
                <x-forms.label class="my-3" fieldId="exstation_id" :fieldLabel="'Select Ex-Station'">
                </x-forms.label>
                <select class="form-control select-picker" name="exstation_id" id="exstation_id" data-live-search="true">
                    <option value="">-- Select Ex-Station --</option>
                </select>
                <small class="form-text text-muted">Only mapped ex-stations shown</small>
            </div>

            <div class="col-md-6" id="outstation_field" style="display: none;">
                <x-forms.label class="my-3" fieldId="outstation_id" :fieldLabel="'Select Out-Station'">
                </x-forms.label>
                <select class="form-control select-picker" name="outstation_id" id="outstation_id" data-live-search="true">
                    <option value="">-- Select Out-Station --</option>
                </select>
                <small class="form-text text-muted">Only mapped out-stations shown</small>
            </div>

            <div class="col-md-6">
                <x-forms.datepicker fieldId="dob" fieldLabel="Date of Birth" fieldName="dob"
                    :fieldPlaceholder="__('placeholders.date')" custom="true">
                </x-forms.datepicker>
            </div>

            <div class="col-md-6">
                <x-forms.datepicker fieldId="dom" fieldLabel="Date of Marriage" fieldName="dom"
                    :fieldPlaceholder="__('placeholders.date')" custom="true">
                </x-forms.datepicker>
            </div>

            <div class="col-md-6">
                <x-forms.select fieldId="gender" :fieldLabel="__('modules.employees.gender')" fieldName="gender">
                    <option value="">--</option>
                    <option value="male">@lang('app.male')</option>
                    <option value="female">@lang('app.female')</option>
                    <option value="other">@lang('app.others')</option>
                </x-forms.select>
            </div>

            <div class="col-md-12">
                <x-forms.file allowedFileExtensions="png jpg jpeg" :fieldLabel="__('Stockist Photo')" 
                    fieldName="stockist_pic" fieldId="stockist_pic" fieldHeight="200">
                </x-forms.file>
            </div>

            <div class="col-md-12">
                <x-forms.textarea :fieldLabel="__('app.address')" fieldName="address" fieldId="address"
                    :fieldPlaceholder="__('placeholders.address')" fieldRequired="true">
                </x-forms.textarea>
            </div>

            <div class="col-md-4">
                <x-forms.text fieldId="dl_number" fieldLabel="DL Number" fieldName="dl_number"
                    fieldPlaceholder="Enter DL Number">
                </x-forms.text>
            </div>

            <div class="col-md-4">
                <x-forms.text fieldId="gst_number" fieldLabel="GST Number" fieldName="gst_number"
                    fieldPlaceholder="Enter GST Number">
                </x-forms.text>
            </div>

            <div class="col-md-4">
                <x-forms.text fieldId="msl_number" fieldLabel="MSL Number" fieldName="msl_number"
                    fieldPlaceholder="Enter MSL Number">
                </x-forms.text>
            </div>
        </div>
    </x-form>
</div>

<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
    <x-forms.button-primary id="save-stockist" icon="check">@lang('app.save')</x-forms.button-primary>
</div>

<script>
    $(document).ready(function() {
        const headquarterStations = @json($headquarterStations ?? []);
        const defaultHeadquarterId = @json($defaultHeadquarterId);

        const getHeadquarterData = (hqId) => {
            if (!hqId) {
                return { exstations: [], outstations: [] };
            }

            return headquarterStations[hqId] || headquarterStations[String(hqId)] || { exstations: [], outstations: [] };
        };

        const populateStationSelect = (selector, options, selectedId = null, placeholder = '-- Select --') => {
            const $select = $(selector);
            $select.empty().append(`<option value="">${placeholder}</option>`);

            options.forEach(option => {
                $select.append(`<option value="${option.id}">${option.name}</option>`);
            });

            if (selectedId) {
                $select.val(selectedId);
            }

            $select.selectpicker('refresh');
        };

        const toggleStationOption = (selector, enabled) => {
            const $input = $(selector);
            const $label = $(`label[for="${$input.attr('id')}"]`);

            $input.prop('disabled', !enabled);
            $label.toggleClass('text-muted', !enabled);

            if (!enabled && $input.is(':checked')) {
                $('#type_headquarter').prop('checked', true).trigger('change');
            }
        };

        const updateStationOptionsAvailability = (hqId) => {
            const data = getHeadquarterData(hqId);

            toggleStationOption('#type_exstation', data.exstations.length > 0);
            toggleStationOption('#type_outstation', data.outstations.length > 0);
        };

        const toggleStationFields = (stationType) => {
            if (stationType === 'exstation') {
                $('#exstation_field').show();
                $('#outstation_field').hide();
                $('#outstation_id').val('').selectpicker('refresh');
            } else if (stationType === 'outstation') {
                $('#outstation_field').show();
                $('#exstation_field').hide();
                $('#exstation_id').val('').selectpicker('refresh');
            } else {
                $('#exstation_field').hide();
                $('#outstation_field').hide();
                $('#exstation_id, #outstation_id').val('').selectpicker('refresh');
            }
        };

        const loadStationsForHQ = (hqId, stationType, selectedId = null) => {
            const data = getHeadquarterData(hqId);

            if (stationType === 'exstation') {
                populateStationSelect('#exstation_id', data.exstations, selectedId, '-- Select Ex-Station --');
            } else if (stationType === 'outstation') {
                populateStationSelect('#outstation_id', data.outstations, selectedId, '-- Select Out-Station --');
            }
        };

        $('.select-picker').selectpicker('refresh');

        if (defaultHeadquarterId) {
            $('#headquarter_id').val(defaultHeadquarterId).selectpicker('refresh');
        }

        updateStationOptionsAvailability($('#headquarter_id').val());
        toggleStationFields($('input[name="station_type"]:checked').val());

        $('#headquarter_id').on('change', function() {
            const hqId = $(this).val();
            updateStationOptionsAvailability(hqId);

            const stationType = $('input[name="station_type"]:checked').val();

            if (hqId && stationType !== 'headquarter') {
                loadStationsForHQ(hqId, stationType);
            }
        });

        $('input[name="station_type"]').on('change', function() {
            const stationType = $(this).val();
            toggleStationFields(stationType);

            const hqId = $('#headquarter_id').val();

            if (hqId && stationType !== 'headquarter') {
                loadStationsForHQ(hqId, stationType);
            }
        });
        
        $('#save-stockist').click(function() {
            $.easyAjax({
                url: "{{ route('stockists.store') }}",
                container: '#save-stockist-form',
                type: "POST",
                disableButton: true,
                buttonSelector: "#save-stockist",
                file: true,
                data: $('#save-stockist-form').serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        window.location.href = response.redirectUrl;
                    }
                }
            });
        });

        $('.custom-date-picker').each(function(ind, el) {
            datepicker(el, {
                position: 'bl',
                ...datepickerConfig
            });
        });
    });
</script>

