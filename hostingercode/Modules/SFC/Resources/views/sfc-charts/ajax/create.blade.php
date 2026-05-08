<x-form id="save-sfc-chart-form">
    <div class="row">
        <div class="col-lg-12">
            <h4 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-bottom-grey">
                @lang('sfc::app.menu.addSfcChart')
            </h4>
        </div>
    </div>

    <div class="row p-20">
        <!-- Territory Name -->
        <div class="col-md-6">
            <x-forms.text :fieldLabel="__('sfc::app.territoryName')" fieldName="territory_name" fieldId="territory_name"/>
        </div>

        <!-- Headquarter -->
        <div class="col-md-6">
            <x-forms.text :fieldLabel="__('sfc::app.headquarter')" fieldName="headquarter" fieldId="headquarter"/>
        </div>

        <!-- Town Name -->
        <div class="col-md-6">
            <x-forms.text :fieldLabel="__('sfc::app.townName')" fieldName="town_name" fieldId="town_name" :fieldRequired="true"/>
        </div>

        <!-- Covered From -->
        <div class="col-md-6">
            <x-forms.text :fieldLabel="__('sfc::app.coveredFrom')" fieldName="covered_from" fieldId="covered_from"/>
        </div>

        <!-- One Way KM (Actual) -->
        <div class="col-md-4">
            <x-forms.number :fieldLabel="__('sfc::app.oneWayKmActual')" fieldName="one_way_km_actual" fieldId="one_way_km_actual" fieldPlaceholder="0.00"/>
        </div>

        <!-- Grace -->
        <div class="col-md-4">
            <x-forms.number :fieldLabel="__('sfc::app.grace')" fieldName="grace" fieldId="grace" fieldPlaceholder="0.00"/>
        </div>

        <!-- Total KM -->
        <div class="col-md-4">
            <x-forms.number :fieldLabel="__('sfc::app.totalKm')" fieldName="total_km" fieldId="total_km" fieldPlaceholder="0.00"/>
        </div>

        <!-- Two Way Fare -->
        <div class="col-md-6">
            <x-forms.number :fieldLabel="__('sfc::app.twoWayFare')" fieldName="two_way_fare" fieldId="two_way_fare" fieldPlaceholder="0.00"/>
        </div>

        <!-- One Way Fare -->
        <div class="col-md-6">
            <x-forms.number :fieldLabel="__('sfc::app.oneWayFare')" fieldName="one_way_fare" fieldId="one_way_fare" fieldPlaceholder="0.00"/>
        </div>

        <!-- EX-HQ / OS -->
        <div class="col-md-6">
            <x-forms.text :fieldLabel="__('sfc::app.exHqOs')" fieldName="ex_hq_os" fieldId="ex_hq_os"/>
        </div>

        <!-- Mode of Travel -->
        <div class="col-md-6">
            <x-forms.select :fieldLabel="__('sfc::app.modeOfTravel')" fieldName="mode_of_travel" fieldId="mode_of_travel">
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
            <x-forms.number :fieldLabel="__('sfc::app.timeInHours')" fieldName="time_in_hours" fieldId="time_in_hours" fieldPlaceholder="0.00"/>
        </div>

        <!-- No of Days (Monthly) -->
        <div class="col-md-6">
            <x-forms.number :fieldLabel="__('sfc::app.noOfDaysMonthly')" fieldName="no_of_days_monthly" fieldId="no_of_days_monthly" fieldPlaceholder="0"/>
        </div>

        <!-- VIP DR Count -->
        <div class="col-md-4">
            <x-forms.number :fieldLabel="__('sfc::app.vipDrCount')" fieldName="vip_dr_count" fieldId="vip_dr_count" fieldPlaceholder="0"/>
        </div>

        <!-- CORE DR Count -->
        <div class="col-md-4">
            <x-forms.number :fieldLabel="__('sfc::app.coreDrCount')" fieldName="core_dr_count" fieldId="core_dr_count" fieldPlaceholder="0"/>
        </div>

        <!-- Total DR Count (Auto-calculated) -->
        <div class="col-md-4">
            <x-forms.text :fieldLabel="__('sfc::app.totalDrCount')" fieldName="total_dr_count" fieldId="total_dr_count" fieldPlaceholder="0" :fieldReadOnly="true"/>
        </div>

        <!-- Stockist Name -->
        <div class="col-md-6">
            <x-forms.text :fieldLabel="__('sfc::app.stockistName')" fieldName="stockist_name" fieldId="stockist_name"/>
        </div>

        <!-- Current Business -->
        <div class="col-md-6">
            <x-forms.number :fieldLabel="__('sfc::app.currentBusiness')" fieldName="current_business" fieldId="current_business" fieldPlaceholder="0.00"/>
        </div>

        <!-- Approx Business Expected -->
        <div class="col-md-6">
            <x-forms.number :fieldLabel="__('sfc::app.approxBusinessExpected')" fieldName="approx_business_expected" fieldId="approx_business_expected" fieldPlaceholder="0.00"/>
        </div>

        <!-- Remarks -->
        <div class="col-md-12">
            <x-forms.textarea :fieldLabel="__('sfc::app.remarks')" fieldName="remarks" fieldId="remarks" fieldPlaceholder="@lang('sfc::app.remarks')"/>
        </div>
    </div>

    <x-form-actions>
        <x-forms.button-primary id="save-sfc-chart-form" class="mr-3" icon="check">@lang('app.save')</x-forms.button-primary>
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

        $('#save-sfc-chart-form').click(function() {
            var url = "{{ route('sfc-charts.store') }}";
            $.easyAjax({
                url: url,
                container: '#save-sfc-chart-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#save-sfc-chart-form",
                data: $('#save-sfc-chart-form').serialize(),
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

