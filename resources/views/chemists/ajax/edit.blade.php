<div class="modal-header">
    <h5 class="modal-title">@lang('app.edit') Chemist</h5>
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
</div>

<div class="modal-body">
    <x-form id="update-chemist-form" method="PUT" class="ajax-form">
        <div class="row">
            <div class="col-md-6">
                <x-forms.text fieldId="shopname" fieldLabel="Shop Name" fieldName="shopname"
                    fieldRequired="true" fieldPlaceholder="Enter shop name" :fieldValue="$chemist->shopname">
                </x-forms.text>
            </div>

            <div class="col-md-6">
                <x-forms.text fieldId="fullname" :fieldLabel="__('app.name')" fieldName="fullname"
                    :fieldPlaceholder="__('placeholders.name')" :fieldValue="$chemist->fullname">
                </x-forms.text>
            </div>

            <div class="col-md-6">
                <x-forms.email fieldId="email" :fieldLabel="__('app.email')" fieldName="email"
                    :fieldPlaceholder="__('placeholders.email')" :fieldValue="$chemist->email">
                </x-forms.email>
            </div>

            <div class="col-md-6">
                <x-forms.tel fieldId="mobile" :fieldLabel="__('app.mobile')" fieldName="mobile"
                    :fieldPlaceholder="__('placeholders.mobile')" :fieldValue="$chemist->mobile">
                </x-forms.tel>
            </div>

            <div class="col-md-6">
                <x-forms.label class="my-3" fieldId="headquarter_id" :fieldLabel="'HeadQuarter'" fieldRequired="true">
                </x-forms.label>
                <select class="form-control select-picker" name="headquarter_id" id="headquarter_id" data-live-search="true" required>
                    <option value="">-- Select HeadQuarter --</option>
                    @foreach(\App\Models\PharmaHeadquarter::orderBy('name')->get() as $hq)
                        <option value="{{ $hq->id }}" @if($chemist->headquarter_id == $hq->id) selected @endif>
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
                        <input type="radio" class="custom-control-input" name="station_type" id="type_headquarter" value="headquarter"
                            @if(!$chemist->exstation_id && !$chemist->outstation_id) checked @endif>
                        <label class="custom-control-label" for="type_headquarter">Headquarter</label>
                    </div>
                    <div class="custom-control custom-radio mr-4">
                        <input type="radio" class="custom-control-input" name="station_type" id="type_exstation" value="exstation"
                            @if($chemist->exstation_id) checked @endif>
                        <label class="custom-control-label" for="type_exstation">Ex-Station</label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" class="custom-control-input" name="station_type" id="type_outstation" value="outstation"
                            @if($chemist->outstation_id) checked @endif>
                        <label class="custom-control-label" for="type_outstation">Out-Station</label>
                    </div>
                </div>
                <small class="form-text text-muted">Select station type for this chemist</small>
            </div>

            <div class="col-md-6" id="exstation_field" style="display: {{ $chemist->exstation_id ? 'block' : 'none' }};">
                <x-forms.label class="my-3" fieldId="exstation_id" :fieldLabel="'Select Ex-Station'">
                </x-forms.label>
                <select class="form-control select-picker" name="exstation_id" id="exstation_id" data-live-search="true">
                    <option value="">-- Select Ex-Station --</option>
                    @if($chemist->exstation_id)
                        <option value="{{ $chemist->exstation_id }}" selected>{{ $chemist->exstation->name ?? '' }}</option>
                    @endif
                </select>
                <small class="form-text text-muted">Only mapped ex-stations shown</small>
            </div>

            <div class="col-md-6" id="outstation_field" style="display: {{ $chemist->outstation_id ? 'block' : 'none' }};">
                <x-forms.label class="my-3" fieldId="outstation_id" :fieldLabel="'Select Out-Station'">
                </x-forms.label>
                <select class="form-control select-picker" name="outstation_id" id="outstation_id" data-live-search="true">
                    <option value="">-- Select Out-Station --</option>
                    @if($chemist->outstation_id)
                        <option value="{{ $chemist->outstation_id }}" selected>{{ $chemist->outstation->name ?? '' }}</option>
                    @endif
                </select>
                <small class="form-text text-muted">Only mapped out-stations shown</small>
            </div>

            <div class="col-md-6">
                <x-forms.datepicker fieldId="dob" fieldLabel="Date of Birth" fieldName="dob"
                    :fieldPlaceholder="__('placeholders.date')" :fieldValue="$chemist->dob ? $chemist->dob->format(company()->date_format) : ''" custom="true">
                </x-forms.datepicker>
            </div>

            <div class="col-md-6">
                <x-forms.datepicker fieldId="dom" fieldLabel="Date of Marriage" fieldName="dom"
                    :fieldPlaceholder="__('placeholders.date')" :fieldValue="$chemist->dom ? $chemist->dom->format(company()->date_format) : ''" custom="true">
                </x-forms.datepicker>
            </div>

            <div class="col-md-6">
                <x-forms.select fieldId="gender" :fieldLabel="__('modules.employees.gender')" fieldName="gender">
                    <option value="">--</option>
                    <option value="male" @if($chemist->gender == 'male') selected @endif>@lang('app.male')</option>
                    <option value="female" @if($chemist->gender == 'female') selected @endif>@lang('app.female')</option>
                    <option value="other" @if($chemist->gender == 'other') selected @endif>@lang('app.others')</option>
                </x-forms.select>
            </div>

            <div class="col-md-12">
                <x-forms.file allowedFileExtensions="png jpg jpeg" :fieldLabel="__('Chemist Photo')" 
                    fieldName="chemist_pic" fieldId="chemist_pic" fieldHeight="200"
                    :fieldValue="$chemist->chemist_pic ? asset_url('chemist/'.$chemist->chemist_pic) : ''">
                </x-forms.file>
            </div>

            <div class="col-md-12">
                <x-forms.textarea :fieldLabel="__('app.address')" fieldName="address" fieldId="address"
                    :fieldPlaceholder="__('placeholders.address')" :fieldValue="$chemist->address" fieldRequired="true">
                </x-forms.textarea>
            </div>
        </div>
    </x-form>
</div>

<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
    <x-forms.button-primary id="update-chemist" icon="check">@lang('app.update')</x-forms.button-primary>
</div>

<script>
    $(document).ready(function() {
        const headquarterStations = @json($headquarterStations ?? []);
        const existingHeadquarterId = @json($chemist->headquarter_id);
        const existingStationType = @json($chemist->exstation_id ? 'exstation' : ($chemist->outstation_id ? 'outstation' : 'headquarter'));
        const existingExstationId = @json($chemist->exstation_id);
        const existingOutstationId = @json($chemist->outstation_id);

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

        if (existingHeadquarterId) {
            $('#headquarter_id').val(existingHeadquarterId).selectpicker('refresh');
        }

        updateStationOptionsAvailability(existingHeadquarterId);
        toggleStationFields(existingStationType);

        if (existingHeadquarterId) {
            if (existingStationType === 'exstation') {
                loadStationsForHQ(existingHeadquarterId, 'exstation', existingExstationId);
            } else if (existingStationType === 'outstation') {
                loadStationsForHQ(existingHeadquarterId, 'outstation', existingOutstationId);
            }
        }

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
                const preselectedId = stationType === 'exstation' ? existingExstationId : existingOutstationId;
                loadStationsForHQ(hqId, stationType, preselectedId);
            }
        });
        
        $('#update-chemist').click(function() {
            $.easyAjax({
                url: "{{ route('chemists.update', $chemist->id) }}",
                container: '#update-chemist-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#update-chemist",
                file: true,
                data: $('#update-chemist-form').serialize(),
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

