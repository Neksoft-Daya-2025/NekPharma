<x-form id="save-sfc-document-form">
    <div class="row">
        <div class="col-lg-12">
            <h4 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-bottom-grey">
                @lang('sfc::app.menu.addSfcChart')
            </h4>
        </div>
    </div>

    <div class="row p-20">
        <!-- Top Section: NAME, HQ, Area, and Region -->
        <div class="col-md-3">
            <x-forms.text :fieldLabel="__('sfc::app.name')" fieldName="name" fieldId="name" :fieldValue="$employeeName ?? user()->name" fieldPlaceholder="Employee Name"/>
        </div>
        <div class="col-md-3">
            <x-forms.text :fieldLabel="__('sfc::app.headquarter')" fieldName="headquarter" fieldId="headquarter" :fieldValue="$employeeHeadquarter ?? ''" fieldPlaceholder="Headquarter"/>
        </div>
        <div class="col-md-3">
            <x-forms.text :fieldLabel="__('app.area')" fieldName="area" fieldId="area" :fieldValue="$employeeArea ?? ''" fieldPlaceholder="Area"/>
        </div>
        <div class="col-md-3">
            <x-forms.text :fieldLabel="__('app.region')" fieldName="region" fieldId="region" :fieldValue="$employeeRegion ?? ''" fieldPlaceholder="Region"/>
        </div>

        <!-- Doctor Visit Statistics Table -->
        <div class="col-md-12 mt-3">
            <h5 class="mb-3">@lang('sfc::app.provideSfcAsPerDrsListArea')</h5>
            <div class="table-responsive">
                <table class="table table-bordered" style="background-color: #fffacd;">
                    <thead>
                        <tr>
                            <th></th>
                            <th class="text-center">@lang('sfc::app.vip')</th>
                            <th class="text-center">@lang('sfc::app.core')</th>
                            <th class="text-center">@lang('sfc::app.total')</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>@lang('sfc::app.typeOfDrs')</strong></td>
                            <td class="text-center">VIP</td>
                            <td class="text-center">CORE</td>
                            <td class="text-center">TOTAL</td>
                        </tr>
                        <tr>
                            <td><strong>@lang('sfc::app.noOfDrs')</strong></td>
                            <td class="text-center">
                                <input type="number" name="vip_dr_count" id="vip_dr_count" class="form-control text-center" value="52" placeholder="52" required min="0"/>
                            </td>
                            <td class="text-center">
                                <input type="number" name="core_dr_count" id="core_dr_count" class="form-control text-center" value="48" placeholder="48" required min="0"/>
                            </td>
                            <td class="text-center">
                                <input type="number" name="total_dr_count" id="total_dr_count_summary" class="form-control text-center" value="100"/>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>@lang('sfc::app.noOfVisitInMonthEachDr')</strong></td>
                            <td class="text-center">
                                <input type="number" name="vip_visits_per_month" id="vip_visits_per_month" class="form-control text-center" value="2" placeholder="2" required min="0"/>
                            </td>
                            <td class="text-center">
                                <input type="number" name="core_visits_per_month" id="core_visits_per_month" class="form-control text-center" value="4" placeholder="4" required min="0"/>
                            </td>
                            <td class="text-center">
                                <input type="number" name="total_visits_per_month" id="total_visits_per_month_summary" class="form-control text-center" value="6"/>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>@lang('sfc::app.totalVisitInMonth')</strong></td>
                            <td class="text-center">
                                <input type="number" name="total_vip_visits_monthly" id="total_vip_visits_monthly" class="form-control text-center" value="104"/>
                            </td>
                            <td class="text-center">
                                <input type="number" name="total_core_visits_monthly" id="total_core_visits_monthly" class="form-control text-center" value="192"/>
                            </td>
                            <td class="text-center">
                                <input type="number" name="total_visits_monthly" id="total_visits_monthly_summary" class="form-control text-center" value="296"/>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="alert alert-info mt-2">
                <small>
                    <strong>@lang('sfc::app.instructions.stockistName')</strong><br>
                    @lang('sfc::app.instructions.againstTownName')
                </small>
            </div>
        </div>

        <!-- Main Table: ACTUAL KM -->
        <div class="col-md-12 mt-4">
            <h5 class="mb-3">ACTUAL KM</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-sm" id="sfc-items-table" style="background-color: #e6ffe6;">
                    <thead>
                        <tr>
                            <th style="width: 40px;">@lang('sfc::app.serialNumber')</th>
                            <th>@lang('sfc::app.coveredFrom')</th>
                            <th>@lang('sfc::app.townName')</th>
                            <th style="min-width: 200px;">@lang('sfc::app.oneWayKmActual')</th>
                            <th>@lang('sfc::app.grace')</th>
                            <th>@lang('sfc::app.totalKm')</th>
                            <th>@lang('sfc::app.twoWayFare')</th>
                            <th>@lang('sfc::app.oneWayFare')</th>
                            <th>@lang('sfc::app.exHqOs')</th>
                            <th>
                                @lang('sfc::app.modeOfTravel')
                                <button type="button" id="add-mode-travel" class="btn btn-xs btn-info ml-1" title="Add New Mode of Travel" style="padding: 2px 6px;">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </th>
                            <th>@lang('sfc::app.timeInHours')</th>
                            <th>@lang('sfc::app.noOfDaysMonthly')</th>
                            <th>@lang('sfc::app.vipDrCount')</th>
                            <th>@lang('sfc::app.coreDrCount')</th>
                            <th>@lang('sfc::app.totalDrCount')</th>
                            <th>@lang('sfc::app.stockistName')</th>
                            <th>@lang('sfc::app.currentBusiness')</th>
                            <th>@lang('sfc::app.approxBusinessExpected')</th>
                            <th>@lang('sfc::app.remarks')</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sfc-items-tbody">
                        <!-- Rows will be added dynamically -->
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6" class="text-right"><strong>TOTAL</strong></td>
                            <td><input type="text" id="total_two_way_fare" class="form-control text-right" readonly value="0"/></td>
                            <td><input type="text" id="total_one_way_fare" class="form-control text-right" readonly value="0"/></td>
                            <td><input type="text" id="total_ex_hq_os" class="form-control text-center" readonly value="0"/></td>
                            <td colspan="3"></td>
                            <td><input type="text" id="total_vip_dr" class="form-control text-center" readonly value="0"/></td>
                            <td><input type="text" id="total_core_dr" class="form-control text-center" readonly value="0"/></td>
                            <td><input type="text" id="total_dr" class="form-control text-center" readonly value="0"/></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <button type="button" class="btn btn-sm btn-secondary mt-2" id="add-sfc-row">
                <i class="fa fa-plus"></i> Add Row
            </button>
        </div>

        <!-- Bottom Section: Instructions and Approvals -->
        <div class="col-md-12 mt-4">
            <div class="alert alert-warning">
                <strong>@lang('sfc::app.toBeFilledInCapitalLetters')</strong>
            </div>
        </div>

        <div class="col-md-6 mt-3">
            <x-forms.text :fieldLabel="__('sfc::app.filledByName')" fieldName="filled_by_name" fieldId="filled_by_name" fieldPlaceholder="Enter NAME"/>
        </div>

        <div class="col-md-6 mt-3">
            <x-forms.text :fieldLabel="__('sfc::app.abmApproval')" fieldName="abm_approval" fieldId="abm_approval" fieldPlaceholder="ABM Approval" :fieldReadOnly="true"/>
        </div>

        <div class="col-md-6 mt-3">
            <x-forms.text :fieldLabel="__('sfc::app.rbmApproval')" fieldName="rbm_approval" fieldId="rbm_approval" fieldPlaceholder="RBM Approval" :fieldReadOnly="true"/>
        </div>
    </div>

    <x-form-actions>
        <x-forms.button-primary id="save-sfc-document-btn" class="mr-3" icon="check">@lang('app.save')</x-forms.button-primary>
        <x-forms.button-cancel :link="route('sfc-charts.index')" class="border-0">@lang('app.cancel')</x-forms.button-cancel>
    </x-form-actions>
</x-form>

<script>
$(document).ready(function() {
    let rowCount = 0;

    // Add initial 1 row only
    addSfcRow();
    
    // Initialize timepickers for existing rows
    $('.time-picker-input').timepicker({
        showMeridian: true,
        defaultTime: false,
        modalBackdrop: true,
        showInputs: false
    });

    // Function to add a new row
    function addSfcRow() {
        rowCount++;
        const row = `
            <tr data-row-index="${rowCount}">
                <td>${rowCount}</td>
                <td><input type="text" name="items[${rowCount}][covered_from]" class="form-control form-control-sm"/></td>
                <td><input type="text" name="items[${rowCount}][town_name]" class="form-control form-control-sm"/></td>
                <td><input type="number" step="0.01" name="items[${rowCount}][one_way_km_actual]" class="form-control form-control-sm" placeholder="KM"/></td>
                <td><input type="number" step="0.01" name="items[${rowCount}][grace]" class="form-control form-control-sm"/></td>
                <td><input type="number" step="0.01" name="items[${rowCount}][total_km]" class="form-control form-control-sm"/></td>
                <td><input type="number" step="0.01" name="items[${rowCount}][two_way_fare]" class="form-control form-control-sm"/></td>
                <td><input type="number" step="0.01" name="items[${rowCount}][one_way_fare]" class="form-control form-control-sm"/></td>
                <td>
                    <select name="items[${rowCount}][ex_hq_os]" class="form-control form-control-sm">
                        <option value="">--</option>
                        <option value="HQ">HQ</option>
                        <option value="EX">EX</option>
                        <option value="OS">OS</option>
                    </select>
                </td>
                <td>
                    <select name="items[${rowCount}][mode_of_travel]" class="form-control form-control-sm mode-of-travel-select">
                        <option value="">--</option>
                        <option value="Bus">@lang('sfc::app.bus')</option>
                        <option value="Train">@lang('sfc::app.train')</option>
                        <option value="Car">@lang('sfc::app.car')</option>
                        <option value="Bike">Bike</option>
                        <option value="Flight">@lang('sfc::app.flight')</option>
                        <option value="Other">@lang('sfc::app.other')</option>
                    </select>
                </td>
                <td>
                    <div class="bootstrap-timepicker">
                        <input type="text" name="items[${rowCount}][time_in_hours]" class="form-control form-control-sm time-picker-input" placeholder="HH:MM AM/PM"/>
                    </div>
                </td>
                <td><input type="number" name="items[${rowCount}][no_of_days_monthly]" class="form-control form-control-sm"/></td>
                <td><input type="number" name="items[${rowCount}][vip_dr_count]" class="form-control form-control-sm" value="0"/></td>
                <td><input type="number" name="items[${rowCount}][core_dr_count]" class="form-control form-control-sm" value="0"/></td>
                <td><input type="text" name="items[${rowCount}][total_dr_count]" class="form-control form-control-sm" value="0"/></td>
                <td>
                    <select name="items[${rowCount}][stockist_name]" class="form-control form-control-sm stockist-select">
                        <option value="">-- Select Stockist --</option>
                        @foreach($stockists ?? [] as $stockist)
                            <option value="{{ $stockist->shopname }}">{{ $stockist->shopname }}{{ $stockist->fullname ? ' (' . $stockist->fullname . ')' : '' }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="number" step="0.01" name="items[${rowCount}][current_business]" class="form-control form-control-sm"/></td>
                <td><input type="number" step="0.01" name="items[${rowCount}][approx_business_expected]" class="form-control form-control-sm"/></td>
                <td><input type="text" name="items[${rowCount}][remarks]" class="form-control form-control-sm"/></td>
                <td>
                    <button type="button" class="btn btn-sm btn-info duplicate-row" title="Duplicate Row" style="margin-right: 2px;">
                        <i class="fa fa-copy"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger remove-row" title="Remove Row">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#sfc-items-tbody').append(row);
        updateRowNumbers();
        // Initialize timepicker for the new row
        $('#sfc-items-tbody tr:last .time-picker-input').timepicker({
            showMeridian: true,
            defaultTime: false,
            modalBackdrop: true,
            showInputs: false
        });
        // Calculate totals after adding row
        calculateTotals();
    }

    // Duplicate/replicate entire row
    $(document).on('click', '.duplicate-row', function() {
        const sourceRow = $(this).closest('tr');
        const sourceRowIndex = sourceRow.data('row-index');
        
        // Get all values from source row
        const rowData = {
            covered_from: sourceRow.find(`input[name="items[${sourceRowIndex}][covered_from]"]`).val() || '',
            town_name: sourceRow.find(`select[name="items[${sourceRowIndex}][town_name]"]`).val() || '',
            one_way_km_actual: sourceRow.find(`input[name="items[${sourceRowIndex}][one_way_km_actual]"]`).val() || '',
            grace: sourceRow.find(`input[name="items[${sourceRowIndex}][grace]"]`).val() || '',
            total_km: sourceRow.find(`input[name="items[${sourceRowIndex}][total_km]"]`).val() || '',
            two_way_fare: sourceRow.find(`input[name="items[${sourceRowIndex}][two_way_fare]"]`).val() || '',
            one_way_fare: sourceRow.find(`input[name="items[${sourceRowIndex}][one_way_fare]"]`).val() || '',
            ex_hq_os: sourceRow.find(`select[name="items[${sourceRowIndex}][ex_hq_os]"]`).val() || '',
            mode_of_travel: sourceRow.find(`select[name="items[${sourceRowIndex}][mode_of_travel]"]`).val() || '',
            time_in_hours: sourceRow.find(`input[name="items[${sourceRowIndex}][time_in_hours]"]`).val() || '',
            no_of_days_monthly: sourceRow.find(`input[name="items[${sourceRowIndex}][no_of_days_monthly]"]`).val() || '',
            vip_dr_count: sourceRow.find(`input[name="items[${sourceRowIndex}][vip_dr_count]"]`).val() || '0',
            core_dr_count: sourceRow.find(`input[name="items[${sourceRowIndex}][core_dr_count]"]`).val() || '0',
            total_dr_count: sourceRow.find(`input[name="items[${sourceRowIndex}][total_dr_count]"]`).val() || '0',
            stockist_name: sourceRow.find(`select[name="items[${sourceRowIndex}][stockist_name]"]`).val() || '',
            current_business: sourceRow.find(`input[name="items[${sourceRowIndex}][current_business]"]`).val() || '',
            approx_business_expected: sourceRow.find(`input[name="items[${sourceRowIndex}][approx_business_expected]"]`).val() || '',
            remarks: sourceRow.find(`input[name="items[${sourceRowIndex}][remarks]"]`).val() || ''
        };
        
        // Add new row
        rowCount++;
        const newRow = `
            <tr data-row-index="${rowCount}">
                <td>${rowCount}</td>
                <td><input type="text" name="items[${rowCount}][covered_from]" class="form-control form-control-sm" value="${rowData.covered_from}"/></td>
                <td>
                    <select name="items[${rowCount}][town_name]" class="form-control form-control-sm town-select">
                        <option value="">-- Select Town --</option>
                        @foreach($towns ?? [] as $town)
                            <option value="{{ $town['name'] }}" ${rowData.town_name === '{{ $town['name'] }}' ? 'selected' : ''}>{{ $town['name'] }} ({{ $town['type'] }})</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="number" step="0.01" name="items[${rowCount}][one_way_km_actual]" class="form-control form-control-sm" value="${rowData.one_way_km_actual}"/></td>
                <td><input type="number" step="0.01" name="items[${rowCount}][grace]" class="form-control form-control-sm" value="${rowData.grace}"/></td>
                <td><input type="number" step="0.01" name="items[${rowCount}][total_km]" class="form-control form-control-sm" value="${rowData.total_km}"/></td>
                <td><input type="number" step="0.01" name="items[${rowCount}][two_way_fare]" class="form-control form-control-sm" value="${rowData.two_way_fare}"/></td>
                <td><input type="number" step="0.01" name="items[${rowCount}][one_way_fare]" class="form-control form-control-sm" value="${rowData.one_way_fare}"/></td>
                <td>
                    <select name="items[${rowCount}][ex_hq_os]" class="form-control form-control-sm">
                        <option value="">--</option>
                        <option value="HQ" ${rowData.ex_hq_os === 'HQ' ? 'selected' : ''}>HQ</option>
                        <option value="EX" ${rowData.ex_hq_os === 'EX' ? 'selected' : ''}>EX</option>
                        <option value="OS" ${rowData.ex_hq_os === 'OS' ? 'selected' : ''}>OS</option>
                    </select>
                </td>
                <td>
                    <select name="items[${rowCount}][mode_of_travel]" class="form-control form-control-sm mode-of-travel-select">
                        <option value="">--</option>
                        <option value="Bus" ${rowData.mode_of_travel === 'Bus' ? 'selected' : ''}>@lang('sfc::app.bus')</option>
                        <option value="Train" ${rowData.mode_of_travel === 'Train' ? 'selected' : ''}>@lang('sfc::app.train')</option>
                        <option value="Car" ${rowData.mode_of_travel === 'Car' ? 'selected' : ''}>@lang('sfc::app.car')</option>
                        <option value="Bike" ${rowData.mode_of_travel === 'Bike' ? 'selected' : ''}>Bike</option>
                        <option value="Flight" ${rowData.mode_of_travel === 'Flight' ? 'selected' : ''}>@lang('sfc::app.flight')</option>
                        <option value="Other" ${rowData.mode_of_travel === 'Other' ? 'selected' : ''}>@lang('sfc::app.other')</option>
                    </select>
                </td>
                <td>
                    <div class="bootstrap-timepicker">
                        <input type="text" name="items[${rowCount}][time_in_hours]" class="form-control form-control-sm time-picker-input" placeholder="HH:MM AM/PM" value="${rowData.time_in_hours}"/>
                    </div>
                </td>
                <td><input type="number" name="items[${rowCount}][no_of_days_monthly]" class="form-control form-control-sm" value="${rowData.no_of_days_monthly}"/></td>
                <td><input type="number" name="items[${rowCount}][vip_dr_count]" class="form-control form-control-sm" value="${rowData.vip_dr_count}"/></td>
                <td><input type="number" name="items[${rowCount}][core_dr_count]" class="form-control form-control-sm" value="${rowData.core_dr_count}"/></td>
                <td><input type="text" name="items[${rowCount}][total_dr_count]" class="form-control form-control-sm" value="${rowData.total_dr_count}"/></td>
                <td>
                    <select name="items[${rowCount}][stockist_name]" class="form-control form-control-sm stockist-select">
                        <option value="">-- Select Stockist --</option>
                        @foreach($stockists ?? [] as $stockist)
                            <option value="{{ $stockist->shopname }}" ${rowData.stockist_name === '{{ $stockist->shopname }}' ? 'selected' : ''}>{{ $stockist->shopname }}{{ $stockist->fullname ? ' (' . $stockist->fullname . ')' : '' }}</option>
                        @endforeach
                    </select>
                </td>
                <td><input type="number" step="0.01" name="items[${rowCount}][current_business]" class="form-control form-control-sm" value="${rowData.current_business}"/></td>
                <td><input type="number" step="0.01" name="items[${rowCount}][approx_business_expected]" class="form-control form-control-sm" value="${rowData.approx_business_expected}"/></td>
                <td><input type="text" name="items[${rowCount}][remarks]" class="form-control form-control-sm" value="${rowData.remarks}"/></td>
                <td>
                    <button type="button" class="btn btn-sm btn-info duplicate-row" title="Duplicate Row" style="margin-right: 2px;">
                        <i class="fa fa-copy"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger remove-row" title="Remove Row">
                        <i class="fa fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        // Insert new row after source row
        sourceRow.after(newRow);
        updateRowNumbers();
        // Initialize timepicker for the new row
        sourceRow.next().find('.time-picker-input').timepicker({
            showMeridian: true,
            defaultTime: false,
            modalBackdrop: true,
            showInputs: false
        });
        // Calculate totals after duplicating row
        calculateTotals();
    });

    // Update row numbers
    function updateRowNumbers() {
        $('#sfc-items-tbody tr').each(function(index) {
            $(this).find('td:first').text(index + 1);
            $(this).find('input, select').each(function() {
                const name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(/items\[\d+\]/, `items[${index + 1}]`));
                }
            });
        });
    }


    // Event handlers
    $('#add-sfc-row').click(function() {
        addSfcRow();
    });

    $(document).on('click', '.remove-row', function() {
        // Allow removing rows, but ensure at least one row exists
        if ($('#sfc-items-tbody tr').length > 1) {
            $(this).closest('tr').remove();
            updateRowNumbers();
            // Recalculate totals after removing row
            calculateTotals();
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Warning',
                text: 'At least one row is required',
                confirmButtonText: 'OK'
            });
        }
    });

    // Add Mode of Travel functionality
    $('#add-mode-travel').click(function() {
        Swal.fire({
            title: 'Add New Mode of Travel',
            input: 'text',
            inputLabel: 'Mode of Travel Name',
            inputPlaceholder: 'Enter mode of travel (e.g., Auto, Taxi)',
            inputValidator: (value) => {
                if (!value) {
                    return 'You need to enter a mode of travel!';
                }
                if (value.length > 50) {
                    return 'Mode of travel must be 50 characters or less!';
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
                const newMode = result.value.trim();
                // Check if mode already exists
                const $firstSelect = $('.mode-of-travel-select').first();
                let exists = false;
                $firstSelect.find('option').each(function() {
                    if ($(this).val().toUpperCase() === newMode.toUpperCase()) {
                        exists = true;
                        return false;
                    }
                });
                
                if (exists) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Mode Already Exists',
                        text: 'This mode of travel already exists in the list.',
                        customClass: {
                            confirmButton: 'btn btn-primary',
                        },
                        buttonsStyling: false
                    });
                } else {
                    // Add new option to all mode of travel selects
                    $('.mode-of-travel-select').each(function() {
                        $(this).append(`<option value="${newMode}">${newMode}</option>`);
                    });
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Added!',
                        text: 'Mode of travel "' + newMode + '" has been added.',
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

    // Calculate row totals (VIP DR + CORE DR = Total DR in each row)
    function calculateRowTotal(row) {
        const vipDr = parseInt(row.find('input[name*="[vip_dr_count]"]').val()) || 0;
        const coreDr = parseInt(row.find('input[name*="[core_dr_count]"]').val()) || 0;
        const totalDr = vipDr + coreDr;
        row.find('input[name*="[total_dr_count]"]').val(totalDr);
    }

    // Calculate footer totals
    function calculateTotals() {
        let totalTwoWay = 0;
        let totalOneWay = 0;
        let totalVipDr = 0;
        let totalCoreDr = 0;
        
        $('#sfc-items-tbody tr').each(function() {
            const twoWay = parseFloat($(this).find('input[name*="[two_way_fare]"]').val()) || 0;
            const oneWay = parseFloat($(this).find('input[name*="[one_way_fare]"]').val()) || 0;
            const vipDr = parseInt($(this).find('input[name*="[vip_dr_count]"]').val()) || 0;
            const coreDr = parseInt($(this).find('input[name*="[core_dr_count]"]').val()) || 0;
            
            totalTwoWay += twoWay;
            totalOneWay += oneWay;
            totalVipDr += vipDr;
            totalCoreDr += coreDr;
        });
        
        $('#total_two_way_fare').val(totalTwoWay.toFixed(2));
        $('#total_one_way_fare').val(totalOneWay.toFixed(2));
        $('#total_vip_dr').val(totalVipDr);
        $('#total_core_dr').val(totalCoreDr);
        $('#total_dr').val(totalVipDr + totalCoreDr);
    }

    // Dynamic calculation on input change
    $(document).on('input', 'input[name*="[two_way_fare]"], input[name*="[one_way_fare]"], input[name*="[vip_dr_count]"], input[name*="[core_dr_count]"]', function() {
        const row = $(this).closest('tr');
        
        // Calculate row total for DR count
        if ($(this).attr('name').includes('vip_dr_count') || $(this).attr('name').includes('core_dr_count')) {
            calculateRowTotal(row);
        }
        
        // Calculate footer totals
        calculateTotals();
    });

    // Form submission
    $('#save-sfc-document-btn').click(function(e) {
        e.preventDefault();
        // Calculate totals before submission
        calculateTotals();
        
        const url = "{{ route('sfc-charts.store') }}";
        $.easyAjax({
            url: url,
            container: '#save-sfc-document-form',
            type: "POST",
            disableButton: true,
            blockUI: true,
            buttonSelector: "#save-sfc-document-btn",
            data: $('#save-sfc-document-form').serialize(),
            success: function(response) {
                if (response.status == 'success') {
                    if ($(MODAL_XL).hasClass('show')) {
                        $(MODAL_XL).modal('hide');
                        window.LaravelDataTables["sfc-documents-table"].draw();
                    } else {
                        window.location.href = response.redirectUrl || "{{ route('sfc-charts.index') }}";
                    }
                }
            }
        });
    });
});
</script>

