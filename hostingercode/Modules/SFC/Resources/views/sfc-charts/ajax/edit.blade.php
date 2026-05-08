<x-form id="update-sfc-chart-form">
    <input type="hidden" name="id" value="{{ $sfcChart->id }}">
    
    <div class="row">
        <div class="col-lg-12">
            <h4 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-bottom-grey">
                @lang('sfc::app.menu.editSfcChart')
            </h4>
        </div>
    </div>

    <div class="row p-20">
        <!-- Territory Name -->
        <div class="col-md-6">
            <x-forms.text :fieldLabel="__('sfc::app.territoryName')" fieldName="territory_name" fieldId="territory_name" :fieldValue="$sfcChart->territory_name"/>
        </div>

        <!-- Headquarter -->
        <div class="col-md-6">
            <x-forms.text :fieldLabel="__('sfc::app.headquarter')" fieldName="headquarter" fieldId="headquarter" :fieldValue="$sfcChart->headquarter"/>
        </div>

        <!-- Town Name -->
        <div class="col-md-6">
            <x-forms.text :fieldLabel="__('sfc::app.townName')" fieldName="town_name" fieldId="town_name" :fieldValue="$sfcChart->town_name" :fieldRequired="true"/>
        </div>

        <!-- Covered From -->
        <div class="col-md-6">
            <x-forms.text :fieldLabel="__('sfc::app.coveredFrom')" fieldName="covered_from" fieldId="covered_from" :fieldValue="$sfcChart->covered_from"/>
        </div>

        <!-- One Way KM (Actual) -->
        <div class="col-md-4">
            <x-forms.number :fieldLabel="__('sfc::app.oneWayKmActual')" fieldName="one_way_km_actual" fieldId="one_way_km_actual" :fieldValue="$sfcChart->one_way_km_actual" fieldPlaceholder="0.00"/>
        </div>

        <!-- Grace -->
        <div class="col-md-4">
            <x-forms.number :fieldLabel="__('sfc::app.grace')" fieldName="grace" fieldId="grace" :fieldValue="$sfcChart->grace" fieldPlaceholder="0.00"/>
        </div>

        <!-- Total KM -->
        <div class="col-md-4">
            <x-forms.number :fieldLabel="__('sfc::app.totalKm')" fieldName="total_km" fieldId="total_km" :fieldValue="$sfcChart->total_km" fieldPlaceholder="0.00"/>
        </div>

        <!-- Two Way Fare -->
        <div class="col-md-6">
            <x-forms.number :fieldLabel="__('sfc::app.twoWayFare')" fieldName="two_way_fare" fieldId="two_way_fare" :fieldValue="$sfcChart->two_way_fare" fieldPlaceholder="0.00"/>
        </div>

        <!-- One Way Fare -->
        <div class="col-md-6">
            <x-forms.number :fieldLabel="__('sfc::app.oneWayFare')" fieldName="one_way_fare" fieldId="one_way_fare" :fieldValue="$sfcChart->one_way_fare" fieldPlaceholder="0.00"/>
        </div>

        <!-- EX-HQ / OS -->
        <div class="col-md-6">
            <x-forms.text :fieldLabel="__('sfc::app.exHqOs')" fieldName="ex_hq_os" fieldId="ex_hq_os" :fieldValue="$sfcChart->ex_hq_os"/>
        </div>

        <!-- Mode of Travel -->
        <div class="col-md-6">
            <x-forms.select :fieldLabel="__('sfc::app.modeOfTravel')" fieldName="mode_of_travel" fieldId="mode_of_travel" :fieldValue="$sfcChart->mode_of_travel">
                <option value="">--</option>
                <option value="Bus">@lang('sfc::app.bus')</option>
                <option value="Train">@lang('sfc::app.train')</option>
                <option value="Car">@lang('sfc::app.car')</option>
                <option value="Flight">@lang('sfc::app.flight')</option>
                <option value="Other">@lang('sfc::app.other')</option>
            </x-forms.select>
        </div>

        <!-- Time in Hours -->
        <div class="col-md-6">
            <x-forms.number :fieldLabel="__('sfc::app.timeInHours')" fieldName="time_in_hours" fieldId="time_in_hours" :fieldValue="$sfcChart->time_in_hours" fieldPlaceholder="0.00"/>
        </div>

        <!-- No of Days (Monthly) -->
        <div class="col-md-6">
            <x-forms.number :fieldLabel="__('sfc::app.noOfDaysMonthly')" fieldName="no_of_days_monthly" fieldId="no_of_days_monthly" :fieldValue="$sfcChart->no_of_days_monthly" fieldPlaceholder="0"/>
        </div>

        <!-- VIP DR Count -->
        <div class="col-md-4">
            <x-forms.number :fieldLabel="__('sfc::app.vipDrCount')" fieldName="vip_dr_count" fieldId="vip_dr_count" :fieldValue="$sfcChart->vip_dr_count" fieldPlaceholder="0"/>
        </div>

        <!-- CORE DR Count -->
        <div class="col-md-4">
            <x-forms.number :fieldLabel="__('sfc::app.coreDrCount')" fieldName="core_dr_count" fieldId="core_dr_count" :fieldValue="$sfcChart->core_dr_count" fieldPlaceholder="0"/>
        </div>

        <!-- Total DR Count (Auto-calculated) -->
        <div class="col-md-4">
            <x-forms.text :fieldLabel="__('sfc::app.totalDrCount')" fieldName="total_dr_count" fieldId="total_dr_count" :fieldValue="$sfcChart->total_dr_count" fieldPlaceholder="0" :fieldReadOnly="true"/>
        </div>

        <!-- Stockist Name -->
        <div class="col-md-6">
            <x-forms.text :fieldLabel="__('sfc::app.stockistName')" fieldName="stockist_name" fieldId="stockist_name" :fieldValue="$sfcChart->stockist_name"/>
        </div>

        <!-- Current Business -->
        <div class="col-md-6">
            <x-forms.number :fieldLabel="__('sfc::app.currentBusiness')" fieldName="current_business" fieldId="current_business" :fieldValue="$sfcChart->current_business" fieldPlaceholder="0.00"/>
        </div>

        <!-- Approx Business Expected -->
        <div class="col-md-6">
            <x-forms.number :fieldLabel="__('sfc::app.approxBusinessExpected')" fieldName="approx_business_expected" fieldId="approx_business_expected" :fieldValue="$sfcChart->approx_business_expected" fieldPlaceholder="0.00"/>
        </div>

        <!-- Remarks -->
        <div class="col-md-12">
            <x-forms.textarea :fieldLabel="__('sfc::app.remarks')" fieldName="remarks" fieldId="remarks" :fieldValue="$sfcChart->remarks" fieldPlaceholder="@lang('sfc::app.remarks')"/>
        </div>
    </div>

    <x-form-actions>
        <x-forms.button-primary id="update-sfc-chart-form" class="mr-3" icon="check">@lang('app.update')</x-forms.button-primary>
        <x-forms.button-cancel :link="route('sfc-charts.index')" class="border-0">@lang('app.cancel')</x-forms.button-cancel>
    </x-form-actions>
</x-form>

<script>
    $(document).ready(function() {
        // Auto-calculate total DR count
        $('#vip_dr_count, #core_dr_count').on('keyup change', function() {
            var vipCount = parseInt($('#vip_dr_count').val()) || 0;
            var coreCount = parseInt($('#core_dr_count').val()) || 0;
            $('#total_dr_count').val(vipCount + coreCount);
        });

        $('#update-sfc-chart-form').click(function() {
            var url = "{{ route('sfc-charts.update', $sfcChart->id) }}";
            $.easyAjax({
                url: url,
                container: '#update-sfc-chart-form',
                type: "PUT",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#update-sfc-chart-form",
                data: $('#update-sfc-chart-form').serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        if ($(MODAL_XL).hasClass('show')) {
                            $(MODAL_XL).modal('hide');
                            window.LaravelDataTables["sfc-charts-table"].draw();
                        } else {
                            window.location.href = response.redirectUrl;
                        }
                    }
                }
            });
        });
    });
</script>

