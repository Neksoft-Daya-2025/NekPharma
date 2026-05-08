@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-0 f-21 font-weight-normal text-capitalize">{{ is_array(__($pageTitle)) ? $pageTitle : __($pageTitle) }}</h4>
            </div>
        </div>

        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#reporting" role="tab">Reporting Hierarchy</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#area" role="tab">Area / Geography Association</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#org" role="tab">Org Association (Designation & Department)</a>
            </li>
        </ul>

        <div class="tab-content">
            {{-- Reporting hierarchy --}}
            <div class="tab-pane fade show active" id="reporting" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Reporting Association</h5>
                        <p class="text-muted small">Who reports to whom (from HR Reporting Manager).</p>
                        <div class="border rounded p-3 bg-light">
                            @forelse($reportingRoots ?? [] as $root)
                                @include('hierarchy-minimap.partials.reporting-tree', ['user' => $root, 'employeesByReportingTo' => $employeesByReportingTo ?? collect(), 'level' => 0])
                            @empty
                                <p class="mb-0 text-muted">No employees with reporting structure found.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Area / Geography --}}
            <div class="tab-pane fade" id="area" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Area Association</h5>
                        <p class="text-muted small">Zone → Region → Area → Headquarter and assigned employees.</p>
                        <div class="border rounded p-3 bg-light">
                            @forelse($zones ?? [] as $zone)
                                <div class="mb-3">
                                    <strong class="text-primary">Zone: {{ $zone->name }}</strong>
                                    @foreach($zone->regions ?? [] as $region)
                                        <div class="ml-3 mt-2">
                                            <strong class="text-secondary">Region: {{ $region->name }}</strong>
                                            @foreach($region->areas ?? [] as $area)
                                                <div class="ml-3 mt-1">
                                                    <strong>Area: {{ $area->name }}</strong>
                                                    @foreach($area->headquarters ?? [] as $hq)
                                                        @php
                                                            $hqEmployees = $employeesByHeadquarter[$hq->id] ?? collect();
                                                            $areaEmployees = $employeesByArea[$area->id] ?? collect();
                                                        @endphp
                                                        <div class="ml-3 mb-2">
                                                            <span class="badge badge-info">HQ: {{ $hq->name }}</span>
                                                            @if($hqEmployees->isNotEmpty())
                                                                <span class="small text-muted">({{ $hqEmployees->count() }} assigned)</span>
                                                                <ul class="small mb-0 pl-3">
                                                                    @foreach($hqEmployees->take(10) as $emp)
                                                                        <li>{{ $emp->name }} @if($emp->employeeDetail && $emp->employeeDetail->designation)({{ $emp->employeeDetail->designation->name }})@endif</li>
                                                                    @endforeach
                                                                    @if($hqEmployees->count() > 10)
                                                                        <li class="text-muted">... and {{ $hqEmployees->count() - 10 }} more</li>
                                                                    @endif
                                                                </ul>
                                                            @else
                                                                <span class="small text-muted">(no employees)</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                    @if(isset($employeesByArea[$area->id]) && $employeesByArea[$area->id]->isNotEmpty())
                                                        <div class="ml-3 small text-muted">Area-mapped (ABM): {{ $employeesByArea[$area->id]->pluck('name')->join(', ') }}</div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            @empty
                                <p class="mb-0 text-muted">No zones/regions/areas configured.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Org: Designation & Department --}}
            <div class="tab-pane fade" id="org" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Designation Association</h5>
                                <p class="text-muted small">Employees by designation.</p>
                                <ul class="list-group list-group-flush">
                                    @php
                                        $designations = \App\Models\Designation::where('company_id', company()->id)->orderBy('name')->get()->keyBy('id');
                                        if (!isset($employeesByDesignation)) { $employeesByDesignation = []; }
                                    @endphp
                                    @foreach($employeesByDesignation ?? [] as $desId => $emps)
                                        @if($desId !== 'none')
                                            @php $des = $designations->get($desId); @endphp
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                {{ $des ? $des->name : 'ID '.$desId }}
                                                <span class="badge badge-primary">{{ $emps->count() }}</span>
                                            </li>
                                        @else
                                            <li class="list-group-item d-flex justify-content-between align-items-center text-muted">
                                                No designation
                                                <span class="badge badge-secondary">{{ $emps->count() }}</span>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Department Association</h5>
                                <p class="text-muted small">Employees by department.</p>
                                <ul class="list-group list-group-flush">
                                    @php
                                        $departments = \App\Models\Team::where('company_id', company()->id)->orderBy('team_name')->get()->keyBy('id');
                                        if (!isset($employeesByDepartment)) { $employeesByDepartment = []; }
                                    @endphp
                                    @foreach($employeesByDepartment ?? [] as $deptId => $emps)
                                        @if($deptId !== 'none')
                                            @php $dept = $departments->get($deptId); @endphp
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                {{ $dept ? $dept->team_name : 'ID '.$deptId }}
                                                <span class="badge badge-primary">{{ $emps->count() }}</span>
                                            </li>
                                        @else
                                            <li class="list-group-item d-flex justify-content-between align-items-center text-muted">
                                                No department
                                                <span class="badge badge-secondary">{{ $emps->count() }}</span>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
