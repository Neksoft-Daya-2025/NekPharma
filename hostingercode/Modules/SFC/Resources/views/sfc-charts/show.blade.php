@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            <div class="row p-20">
                <div class="col-lg-12">
                    <h4 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-bottom-grey">
                        @lang('sfc::app.menu.viewSfcChart')
                    </h4>
                </div>
            </div>

            <div class="row p-20">
                <div class="col-md-6">
                    <x-cards.data :title="__('sfc::app.territoryName')" :value="$sfcChart->territory_name ?? '-'"/>
                </div>
                <div class="col-md-6">
                    <x-cards.data :title="__('sfc::app.headquarter')" :value="$sfcChart->headquarter ?? '-'"/>
                </div>
                <div class="col-md-6">
                    <x-cards.data :title="__('sfc::app.townName')" :value="$sfcChart->town_name"/>
                </div>
                <div class="col-md-6">
                    <x-cards.data :title="__('sfc::app.coveredFrom')" :value="$sfcChart->covered_from ?? '-'"/>
                </div>
                <div class="col-md-4">
                    <x-cards.data :title="__('sfc::app.oneWayKmActual')" :value="$sfcChart->one_way_km_actual ? number_format($sfcChart->one_way_km_actual, 2) : '-'"/>
                </div>
                <div class="col-md-4">
                    <x-cards.data :title="__('sfc::app.grace')" :value="$sfcChart->grace ? number_format($sfcChart->grace, 2) : '-'"/>
                </div>
                <div class="col-md-4">
                    <x-cards.data :title="__('sfc::app.totalKm')" :value="$sfcChart->total_km ? number_format($sfcChart->total_km, 2) : '-'"/>
                </div>
                <div class="col-md-6">
                    <x-cards.data :title="__('sfc::app.twoWayFare')" :value="$sfcChart->two_way_fare ? currency_format($sfcChart->two_way_fare) : '-'"/>
                </div>
                <div class="col-md-6">
                    <x-cards.data :title="__('sfc::app.oneWayFare')" :value="$sfcChart->one_way_fare ? currency_format($sfcChart->one_way_fare) : '-'"/>
                </div>
                <div class="col-md-6">
                    <x-cards.data :title="__('sfc::app.exHqOs')" :value="$sfcChart->ex_hq_os ?? '-'"/>
                </div>
                <div class="col-md-6">
                    <x-cards.data :title="__('sfc::app.modeOfTravel')" :value="$sfcChart->mode_of_travel ?? '-'"/>
                </div>
                <div class="col-md-6">
                    <x-cards.data :title="__('sfc::app.timeInHours')" :value="$sfcChart->time_in_hours ? number_format($sfcChart->time_in_hours, 2) : '-'"/>
                </div>
                <div class="col-md-6">
                    <x-cards.data :title="__('sfc::app.noOfDaysMonthly')" :value="$sfcChart->no_of_days_monthly ?? '-'"/>
                </div>
                <div class="col-md-4">
                    <x-cards.data :title="__('sfc::app.vipDrCount')" :value="$sfcChart->vip_dr_count ?? 0"/>
                </div>
                <div class="col-md-4">
                    <x-cards.data :title="__('sfc::app.coreDrCount')" :value="$sfcChart->core_dr_count ?? 0"/>
                </div>
                <div class="col-md-4">
                    <x-cards.data :title="__('sfc::app.totalDrCount')" :value="$sfcChart->total_dr_count ?? 0"/>
                </div>
                <div class="col-md-6">
                    <x-cards.data :title="__('sfc::app.stockistName')" :value="$sfcChart->stockist_name ?? '-'"/>
                </div>
                <div class="col-md-6">
                    <x-cards.data :title="__('sfc::app.currentBusiness')" :value="$sfcChart->current_business ? currency_format($sfcChart->current_business) : '-'"/>
                </div>
                <div class="col-md-6">
                    <x-cards.data :title="__('sfc::app.approxBusinessExpected')" :value="$sfcChart->approx_business_expected ? currency_format($sfcChart->approx_business_expected) : '-'"/>
                </div>
                <div class="col-md-12">
                    <x-cards.data :title="__('sfc::app.remarks')" :value="$sfcChart->remarks ?? '-'"/>
                </div>
            </div>

            <div class="row p-20 border-top-grey">
                <div class="col-md-12">
                    @if(user()->permission('edit_sfc_chart') == 'all')
                        <x-forms.link-primary :link="route('sfc-charts.edit', $sfcChart->id)" class="mr-3" icon="edit">
                            @lang('app.edit')
                        </x-forms.link-primary>
                    @endif
                    <x-forms.button-secondary :link="route('sfc-charts.index')" icon="arrow-left">
                        @lang('app.back')
                    </x-forms.button-secondary>
                </div>
            </div>
        </div>
    </div>
@endsection

