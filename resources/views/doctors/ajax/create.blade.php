<div class="modal-header">
    <h5 class="modal-title">@lang('app.add') Doctor</h5>
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
</div>

<div class="modal-body">
    <x-form id="save-doctor-form" method="POST" class="ajax-form">
        <div class="row">
            <div class="col-md-6">
                <x-forms.text fieldId="fullname" :fieldLabel="__('app.name')" fieldName="fullname"
                    fieldRequired="true" :fieldPlaceholder="__('placeholders.name')">
                </x-forms.text>
            </div>

            <div class="col-md-6">
                <x-forms.email fieldId="email" :fieldLabel="__('app.email')" fieldName="email"
                    :fieldPlaceholder="__('placeholders.email')">
                </x-forms.email>
            </div>

@php
    $headquarterOptions = $headquarters ?? collect();
    $defaultHeadquarterId = $defaultHeadquarterId ?? null;
@endphp

            <div class="col-md-6">
                <x-forms.text fieldId="qualification" fieldLabel="Qualification" fieldName="qualification"
                    fieldPlaceholder="e.g., MBBS, MD, MS">
                </x-forms.text>
            </div>

            <div class="col-md-6">
                <x-forms.text fieldId="speciality" fieldLabel="Speciality" fieldName="speciality"
                    fieldPlaceholder="e.g., Cardiologist, Pediatrician">
                </x-forms.text>
            </div>

            <div class="col-md-6">
                <x-forms.tel fieldId="mobile" :fieldLabel="__('app.mobile')" fieldName="mobile"
                    :fieldPlaceholder="__('placeholders.mobile')">
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
                <small class="form-text text-muted">Select station type for this doctor</small>
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

            <div class="col-md-6">
                <x-forms.label class="my-3" fieldId="doctor_type" fieldLabel="Doctor Type (SFC)">
                </x-forms.label>
                <x-forms.input-group>
                    <select class="form-control select-picker" name="doctor_type" id="doctor_type" data-live-search="true">
                        <option value="">-- Select Type --</option>
                        @foreach($doctorTypes ?? ['VIP', 'CORE'] as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                    <x-slot name="append">
                        <button id="doctor-type-add" type="button" 
                                class="btn btn-outline-secondary border-grey" 
                                data-toggle="tooltip" 
                                title="Add New Doctor Type">
                            <i class="fa fa-plus"></i> @lang('app.add')
                        </button>
                    </x-slot>
                </x-forms.input-group>
                <small class="form-text text-muted">Select doctor type for SFC (Standard Fare Chart) association. Click Add to create a new type.</small>
            </div>

            <div class="col-md-12">
                <x-forms.file allowedFileExtensions="png jpg jpeg" class="mr-0 mr-lg-2 mr-md-2"
                    :fieldLabel="__('Doctor Photo')" fieldName="doctor_pic" fieldId="doctor_pic" fieldHeight="200">
                </x-forms.file>
            </div>

            <div class="col-md-12">
                <x-forms.textarea class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('app.address')"
                    fieldName="address" fieldId="address" :fieldPlaceholder="__('placeholders.address')" fieldRequired="true">
                </x-forms.textarea>
            </div>

            <div class="col-md-12">
                <x-forms.label class="my-3" fieldId="products" fieldLabel="Products">
                </x-forms.label>
                <select class="form-control select-picker" name="products[]" id="products" multiple data-live-search="true" data-actions-box="true">
                    @foreach($products ?? [] as $product)
                        <option value="{{ $product->id }}" data-product-name="{{ $product->name }}">{{ $product->name }}</option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Select products associated with this doctor</small>
                
                <!-- Selected Products Display -->
                <div id="selected-products-list" class="mt-3" style="display: none;">
                    <label class="mb-2"><strong>Selected Products:</strong></label>
                    <div id="selected-products-container" class="d-flex flex-wrap">
                        <!-- Selected products will be displayed here -->
                    </div>
                </div>
            </div>
        </div>
    </x-form>
</div>

<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
    <x-forms.button-primary id="save-doctor" icon="check">@lang('app.save')</x-forms.button-primary>
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
            $('#headquarter_id').val(defaultHeadquarterId);
        }

        $('#headquarter_id').selectpicker('refresh');

        const currentHeadquarterId = $('#headquarter_id').val();
        updateStationOptionsAvailability(currentHeadquarterId);
        toggleStationFields($('input[name="station_type"]:checked').val());

        $('#headquarter_id').on('change', function() {
            const hqId = $(this).val();
            updateStationOptionsAvailability(hqId);

            const stationType = $('input[name="station_type"]:checked').val();

            if (stationType && stationType !== 'headquarter') {
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

        $('#save-doctor').click(function() {
            $.easyAjax({
                url: "{{ route('doctors.store') }}",
                container: '#save-doctor-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-doctor",
                file: true,
                data: $('#save-doctor-form').serialize(),
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

        // Products selection display
        const productsData = @json($products ?? []);
        const productMap = {};
        productsData.forEach(function(product) {
            productMap[product.id] = product.name;
        });

        function updateSelectedProductsDisplay() {
            const selectedValues = $('#products').val() || [];
            const container = $('#selected-products-container');
            const listDiv = $('#selected-products-list');

            container.empty();

            if (selectedValues.length === 0) {
                listDiv.hide();
                return;
            }

            listDiv.show();
            selectedValues.forEach(function(productId, index) {
                const productName = productMap[productId] || 'Product ' + (index + 1);
                const badge = $('<span>')
                    .addClass('badge badge-primary mr-2 mb-2 d-inline-flex align-items-center')
                    .html(productName + ' <i class="fa fa-times ml-2 cursor-pointer remove-product" data-product-id="' + productId + '" style="cursor: pointer;"></i>');
                container.append(badge);
            });

            // Add remove functionality
            $('.remove-product').on('click', function() {
                const productId = $(this).data('product-id');
                const currentValues = $('#products').val() || [];
                const newValues = currentValues.filter(function(id) {
                    return id != productId;
                });
                $('#products').val(newValues);
                $('#products').selectpicker('refresh');
                updateSelectedProductsDisplay();
            });
        }

        // Initialize display if there are pre-selected products
        updateSelectedProductsDisplay();

        // Update display when products selection changes
        $('#products').on('changed.bs.select', function() {
            updateSelectedProductsDisplay();
        });

        // Doctor Type Add functionality
        $('#doctor-type-add').click(function() {
            Swal.fire({
                title: 'Add New Doctor Type',
                input: 'text',
                inputLabel: 'Doctor Type Name',
                inputPlaceholder: 'Enter doctor type (e.g., PREMIUM, STANDARD)',
                inputValidator: (value) => {
                    if (!value) {
                        return 'You need to enter a doctor type!';
                    }
                    if (value.length > 50) {
                        return 'Doctor type must be 50 characters or less!';
                    }
                },
                showCancelButton: true,
                confirmButtonText: 'Add',
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-secondary'
                },
                showClass: {
                    popup: 'swal2-noanimation',
                    backdrop: 'swal2-noanimation'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const newType = result.value.trim();
                    // Check if type already exists
                    const $select = $('#doctor_type');
                    let exists = false;
                    $select.find('option').each(function() {
                        if ($(this).val().toUpperCase() === newType.toUpperCase()) {
                            exists = true;
                            return false;
                        }
                    });
                    
                    if (exists) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Type Already Exists',
                            text: 'This doctor type already exists in the list.',
                            customClass: {
                                confirmButton: 'btn btn-primary',
                            },
                            buttonsStyling: false
                        });
                    } else {
                        // Add new option to select
                        $select.append(`<option value="${newType}">${newType}</option>`);
                        $select.selectpicker('refresh');
                        $select.val(newType);
                        $select.selectpicker('refresh');
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Added!',
                            text: 'Doctor type "' + newType + '" has been added.',
                            timer: 1500,
                            showConfirmButton: false,
                            customClass: {
                                popup: 'swal2-noanimation',
                                backdrop: 'swal2-noanimation'
                            }
                        });
                    }
                }
            });
        });
    });
</script>

