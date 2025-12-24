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

            <!-- Top Section: NAME, HQ, Area, and Region -->
            <div class="row p-20">
                <div class="col-md-3">
                    <x-cards.data :title="__('sfc::app.name')" :value="$document->name ?? '-'"/>
                </div>
                <div class="col-md-3">
                    <x-cards.data :title="__('sfc::app.headquarter')" :value="$document->headquarter ?? '-'"/>
                </div>
                <div class="col-md-3">
                    <x-cards.data :title="__('app.area')" :value="$document->area ?? '-'"/>
                </div>
                <div class="col-md-3">
                    <x-cards.data :title="__('app.region')" :value="$document->region ?? '-'"/>
                </div>
            </div>

            <!-- Doctor Visit Statistics Table -->
            <div class="row p-20">
                <div class="col-md-12">
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
                                    <td class="text-center">{{ $document->vip_dr_count }}</td>
                                    <td class="text-center">{{ $document->core_dr_count }}</td>
                                    <td class="text-center">{{ $document->total_dr_count }}</td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('sfc::app.noOfVisitInMonthEachDr')</strong></td>
                                    <td class="text-center">{{ $document->vip_visits_per_month }}</td>
                                    <td class="text-center">{{ $document->core_visits_per_month }}</td>
                                    <td class="text-center">{{ $document->vip_visits_per_month + $document->core_visits_per_month }}</td>
                                </tr>
                                <tr>
                                    <td><strong>@lang('sfc::app.totalVisitInMonth')</strong></td>
                                    <td class="text-center">{{ $document->total_vip_visits_monthly }}</td>
                                    <td class="text-center">{{ $document->total_core_visits_monthly }}</td>
                                    <td class="text-center">{{ $document->total_visits_monthly }}</td>
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
            </div>

            <!-- Main Table: ACTUAL KM -->
            <div class="row p-20">
                <div class="col-md-12">
                    <h5 class="mb-3">ACTUAL KM</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" style="background-color: #e6ffe6;">
                            <thead>
                                <tr>
                                    <th>@lang('sfc::app.serialNumber')</th>
                                    <th>@lang('sfc::app.coveredFrom')</th>
                                    <th>@lang('sfc::app.townName')</th>
                                    <th>@lang('sfc::app.oneWayKmActual')</th>
                                    <th>@lang('sfc::app.grace')</th>
                                    <th>@lang('sfc::app.totalKm')</th>
                                    <th>@lang('sfc::app.twoWayFare')</th>
                                    <th>@lang('sfc::app.oneWayFare')</th>
                                    <th>@lang('sfc::app.exHqOs')</th>
                                    <th>@lang('sfc::app.modeOfTravel')</th>
                                    <th>@lang('sfc::app.timeInHours')</th>
                                    <th>@lang('sfc::app.noOfDaysMonthly')</th>
                                    <th>@lang('sfc::app.vipDrCount')</th>
                                    <th>@lang('sfc::app.coreDrCount')</th>
                                    <th>@lang('sfc::app.totalDrCount')</th>
                                    <th>@lang('sfc::app.stockistName')</th>
                                    <th>@lang('sfc::app.currentBusiness')</th>
                                    <th>@lang('sfc::app.approxBusinessExpected')</th>
                                    <th>@lang('sfc::app.remarks')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totalTwoWay = 0;
                                    $totalOneWay = 0;
                                    $totalVipDr = 0;
                                    $totalCoreDr = 0;
                                @endphp
                                @foreach($document->chartItems as $item)
                                    @php
                                        $totalTwoWay += $item->two_way_fare ?? 0;
                                        $totalOneWay += $item->one_way_fare ?? 0;
                                        $totalVipDr += $item->vip_dr_count ?? 0;
                                        $totalCoreDr += $item->core_dr_count ?? 0;
                                    @endphp
                                    <tr>
                                        <td>{{ $item->serial_number }}</td>
                                        <td>{{ $item->covered_from ?? '-' }}</td>
                                        <td>{{ $item->town_name }}</td>
                                        <td>
                                            @php
                                                $actualKm = is_array($item->one_way_km_actual) ? (count($item->one_way_km_actual) > 0 ? array_sum($item->one_way_km_actual) : null) : $item->one_way_km_actual;
                                            @endphp
                                            {{ $actualKm ? number_format($actualKm, 2) : '-' }}
                                        </td>
                                        <td>{{ $item->grace ? number_format($item->grace, 2) : '-' }}</td>
                                        <td>{{ $item->total_km ? number_format($item->total_km, 2) : '-' }}</td>
                                        <td>{{ $item->two_way_fare ? currency_format($item->two_way_fare) : '-' }}</td>
                                        <td>{{ $item->one_way_fare ? currency_format($item->one_way_fare) : '-' }}</td>
                                        <td>{{ $item->ex_hq_os ?? '-' }}</td>
                                        <td>{{ $item->mode_of_travel ?? '-' }}</td>
                                        <td>{{ $item->time_in_hours ? number_format($item->time_in_hours, 2) : '-' }}</td>
                                        <td>{{ $item->no_of_days_monthly ?? '-' }}</td>
                                        <td>{{ $item->vip_dr_count ?? 0 }}</td>
                                        <td>{{ $item->core_dr_count ?? 0 }}</td>
                                        <td>{{ $item->total_dr_count ?? 0 }}</td>
                                        <td>{{ $item->stockist_name ?? '-' }}</td>
                                        <td>{{ $item->current_business ? currency_format($item->current_business) : '-' }}</td>
                                        <td>{{ $item->approx_business_expected ? currency_format($item->approx_business_expected) : '-' }}</td>
                                        <td>{{ $item->remarks ?? '-' }}</td>
                                    </tr>
                                @endforeach
                                @if($document->chartItems->count() == 0)
                                    <tr>
                                        <td colspan="19" class="text-center">No items added yet</td>
                                    </tr>
                                @endif
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-right"><strong>TOTAL</strong></td>
                                    <td><strong>{{ currency_format($totalTwoWay) }}</strong></td>
                                    <td><strong>{{ currency_format($totalOneWay) }}</strong></td>
                                    <td><strong>0</strong></td>
                                    <td colspan="3"></td>
                                    <td><strong>{{ $totalVipDr }}</strong></td>
                                    <td><strong>{{ $totalCoreDr }}</strong></td>
                                    <td><strong>{{ $totalVipDr + $totalCoreDr }}</strong></td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Bottom Section: Instructions and Approvals -->
            <div class="row p-20">
                <div class="col-md-12">
                    <div class="alert alert-warning">
                        <strong>@lang('sfc::app.toBeFilledInCapitalLetters')</strong>
                    </div>
                </div>
                <div class="col-md-6">
                    <x-cards.data :title="__('sfc::app.filledByName')" :value="$document->filled_by_name ?? '-'"/>
                </div>
                <div class="col-md-6">
                    <x-cards.data :title="__('sfc::app.abmApproval')" :value="$document->abm_approval ?? '-'"/>
                </div>
                <div class="col-md-6">
                    <x-cards.data :title="__('sfc::app.rbmApproval')" :value="$document->rbm_approval ?? '-'"/>
                </div>
            </div>

            <div class="row p-20 border-top-grey">
                <div class="col-md-12">
                    @if(user()->permission('edit_sfc_chart') == 'all')
                        <x-forms.link-primary :link="route('sfc-charts.edit', $document->id)" class="mr-3" icon="edit">
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

