@extends('layouts.app')

@section('content')

    <!-- SETTINGS START -->
    <div class="w-100 d-flex ">

        <x-setting-sidebar :activeMenu="$activeSettingMenu"/>

        <x-setting-card>
            <x-slot name="header">
                <div class="s-b-n-header" id="tabs">
                    <h2 class="mb-0 p-20 f-21 font-weight-normal text-capitalize border-bottom-grey">
                        @lang($pageTitle)
                    </h2>
                </div>
            </x-slot>

            <x-slot name="buttons">
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <x-forms.button-secondary class="mr-3" id="refresh-health-check" icon="sync-alt">
                            @lang('app.refresh')
                        </x-forms.button-secondary>
                    </div>
                </div>
            </x-slot>

            <!-- HEALTH CHECK RESULTS -->
            <div class="col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-4">

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-0 b-shadow-4">
                            <div class="card-body text-center">
                                <h1 class="f-30 f-w-500 text-success">{{ $passed }}</h1>
                                <p class="f-13 text-dark-grey mb-0">@lang('app.passed')</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 b-shadow-4">
                            <div class="card-body text-center">
                                <h1 class="f-30 f-w-500 text-warning">{{ $warnings }}</h1>
                                <p class="f-13 text-dark-grey mb-0">@lang('app.warning')</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 b-shadow-4">
                            <div class="card-body text-center">
                                <h1 class="f-30 f-w-500 text-danger">{{ $failed }}</h1>
                                <p class="f-13 text-dark-grey mb-0">@lang('app.failed')</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 b-shadow-4">
                            <div class="card-body text-center">
                                <h1 class="f-30 f-w-500 text-dark">{{ $total }}</h1>
                                <p class="f-13 text-dark-grey mb-0">@lang('app.total')</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overall Status Badge -->
                <div class="row mb-4">
                    <div class="col-md-12 text-center">
                        @if($failed == 0)
                            <div class="alert alert-success" role="alert">
                                <i class="fa fa-check-circle f-20"></i>
                                <strong class="ml-2">@lang('app.allChecksPassed')</strong> - System is healthy and functioning properly.
                            </div>
                        @elseif($passed >= 7)
                            <div class="alert alert-warning" role="alert">
                                <i class="fa fa-exclamation-triangle f-20"></i>
                                <strong class="ml-2">@lang('app.minorIssues')</strong> - System is mostly healthy but has some minor issues.
                            </div>
                        @else
                            <div class="alert alert-danger" role="alert">
                                <i class="fa fa-times-circle f-20"></i>
                                <strong class="ml-2">@lang('app.criticalIssues')</strong> - System has critical issues that need attention.
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Health Check Details -->
                <div class="d-flex flex-column w-tables rounded mt-3 bg-white">

                    <x-table class="table-hover border-0 w-100" headType="thead-light">
                        <x-slot name="thead">
                            <th class="w-5">#</th>
                            <th class="w-20">@lang('app.check')</th>
                            <th class="w-30">@lang('app.description')</th>
                            <th class="w-10">@lang('app.status')</th>
                            <th class="w-35">@lang('app.message')</th>
                        </x-slot>

                        @forelse($checks as $index => $check)
                            <tr>
                                <td class="f-14 text-dark">{{ $index + 1 }}</td>
                                <td class="f-14 text-dark">
                                    <strong>{{ $check['name'] }}</strong>
                                </td>
                                <td class="f-13 text-dark-grey">{{ $check['description'] }}</td>
                                <td>
                                    @if($check['status'] == 'pass')
                                        <span class="badge badge-success"><i class="fa fa-check"></i> PASS</span>
                                    @elseif($check['status'] == 'warning')
                                        <span class="badge badge-warning"><i class="fa fa-exclamation-triangle"></i> WARN</span>
                                    @else
                                        <span class="badge badge-danger"><i class="fa fa-times"></i> FAIL</span>
                                    @endif
                                </td>
                                <td class="f-13">
                                    <span class="@if($check['status'] == 'pass') text-success @elseif($check['status'] == 'warning') text-warning @else text-danger @endif">
                                        {{ $check['message'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">
                                    <x-cards.no-record icon="check-circle" :message="__('messages.noRecordFound')" />
                                </td>
                            </tr>
                        @endforelse
                    </x-table>
                </div>

                <!-- Info Box -->
                <div class="alert alert-info mt-4" role="alert">
                    <i class="fa fa-info-circle"></i>
                    <strong>@lang('app.note'):</strong> This health check verifies critical system components including database connectivity, essential tables, storage permissions, and recently deployed features (Doctor HQ/Station filters and Payroll CSV upload).
                </div>

                <!-- Last Check Time -->
                <div class="text-right mt-3">
                    <small class="text-muted">
                        <i class="fa fa-clock"></i> Last checked: {{ now()->format('F d, Y - h:i A') }}
                    </small>
                </div>

            </div>

        </x-setting-card>

    </div>
    <!-- SETTINGS END -->
@endsection

@push('scripts')
    <script>
        // Refresh health check
        $('#refresh-health-check').click(function() {
            window.location.reload();
        });
    </script>
@endpush

