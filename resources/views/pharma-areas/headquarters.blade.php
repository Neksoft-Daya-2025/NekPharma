@extends('layouts.app')

@push('styles')
<style>
    .hq-card {
        border-left: 4px solid #8bab4c;
        margin-bottom: 1rem;
        transition: all 0.3s;
    }
    .hq-card.unassigned {
        border-left: 4px solid #dc3545;
        background: #fff5f5;
    }
    .hq-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .station-badge {
        margin: 3px;
        font-size: 11px;
        padding: 4px 8px;
    }
    .quick-assign-section {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 6px;
        margin-top: 1rem;
    }
</style>
@endpush

@section('content')
<div class="content-wrapper">
    {{-- Create New Headquarter Form --}}
    <div class="card mb-4">
        <div class="card-header" style="background: linear-gradient(135deg, #8bab4c 0%, #6d8a3c 100%); color: white;">
            <h5 class="mb-0"><i class="fa fa-plus-circle"></i> Create New HeadQuarter</h5>
        </div>
        <div class="card-body">
            <form id="create-hq-form">
                @csrf
                <div class="row">
                    <div class="col-md-5">
                        <label><strong>HeadQuarter Name</strong> <sup class="text-danger">*</sup></label>
                        <input type="text" class="form-control" name="name" id="hq-name" placeholder="e.g., Mumbai 1" required>
                    </div>
                    <div class="col-md-4">
                        <label><strong>Assign to Area</strong></label>
                        <div class="input-group">
                            <select class="form-control select-picker" name="area_id" data-live-search="true">
                                <option value="">-- Optional --</option>
                                @foreach(\App\Models\PharmaArea::all() as $area)
                                    <option value="{{ $area->id }}">{{ $area->name }}</option>
                                @endforeach
                            </select>
                            <div class="input-group-append">
                                <a href="{{ route('pharma-areas.areas') }}" class="btn btn-outline-secondary border-grey height-35" target="_blank" title="Add Area">
                                    <i class="fa fa-plus"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fa fa-plus"></i> Create HeadQuarter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Existing Headquarters List --}}
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa fa-building"></i> HeadQuarters List</h5>
            <span class="badge badge-secondary">{{ $headquarters->count() }} Total</span>
        </div>
        <div class="card-body">
            @foreach($headquarters as $hq)
                @php
                    $exCount = $hq->exstations->count();
                    $outCount = $hq->outstations->count();
                    $total = $exCount + $outCount;
                @endphp
                
                <div class="card hq-card {{ $total == 0 ? 'unassigned' : '' }}">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fa fa-building {{ $total == 0 ? 'text-danger' : 'text-success' }}"></i>
                            <strong>{{ $hq->name }}</strong>
                            @if($hq->area)
                                <span class="badge badge-info ml-2">{{ $hq->area->name }}</span>
                            @endif
                            @if($total == 0)
                                <span class="badge badge-danger ml-2"><i class="fa fa-exclamation-triangle"></i> No Stations</span>
                            @endif
                        </div>
                        <div>
                            <span class="badge {{ $total == 0 ? 'badge-danger' : 'badge-dark' }} mr-2">
                                Total: {{ $total }}
                            </span>
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item assign-stations" href="javascript:;" 
                                       data-hq-id="{{ $hq->id }}" 
                                       data-hq-name="{{ $hq->name }}">
                                        <i class="fa fa-link text-success"></i> Assign Stations
                                    </a>
                                    <a class="dropdown-item edit-hq" href="javascript:;" 
                                       data-hq-id="{{ $hq->id }}" 
                                       data-hq-name="{{ $hq->name }}"
                                       data-area-id="{{ $hq->area_id }}">
                                        <i class="fa fa-edit text-primary"></i> Edit HQ Details
                                    </a>
                                    @if($total > 0)
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item remove-all-stations" href="javascript:;" 
                                       data-hq-id="{{ $hq->id }}" 
                                       data-hq-name="{{ $hq->name }}">
                                        <i class="fa fa-unlink text-warning"></i> Remove All Stations
                                    </a>
                                    @endif
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item delete-hq" href="javascript:;" 
                                       data-hq-id="{{ $hq->id }}" 
                                       data-hq-name="{{ $hq->name }}">
                                        <i class="fa fa-trash text-danger"></i> Delete HeadQuarter
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            {{-- Ex-Stations --}}
                            <div class="col-md-6">
                                <h6 class="text-primary mb-2">
                                    <i class="fa fa-map-marker-alt"></i> Ex-Stations 
                                    <span class="badge badge-primary">{{ $exCount }}</span>
                                </h6>
                                @if($exCount > 0)
                                    <div class="d-flex flex-wrap">
                                        @foreach($hq->exstations as $station)
                                            <span class="badge badge-primary station-badge">{{ $station->name }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-danger mb-0"><i class="fa fa-exclamation-circle"></i> <em>No ex-stations assigned</em></p>
                                @endif
                            </div>

                            {{-- Out-Stations --}}
                            <div class="col-md-6">
                                <h6 class="text-info mb-2">
                                    <i class="fa fa-map-marker"></i> Out-Stations 
                                    <span class="badge badge-info">{{ $outCount }}</span>
                                </h6>
                                @if($outCount > 0)
                                    <div class="d-flex flex-wrap">
                                        @foreach($hq->outstations as $station)
                                            <span class="badge badge-info station-badge">{{ $station->name }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-danger mb-0"><i class="fa fa-exclamation-circle"></i> <em>No out-stations assigned</em></p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Setup CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // HQ assignments data
    const hqAssignments = {
        @foreach($headquarters as $hq)
            {{ $hq->id }}: {
                exstations: [{{ $hq->exstations->pluck('id')->implode(',') }}],
                outstations: [{{ $hq->outstations->pluck('id')->implode(',') }}]
            },
        @endforeach
    };

    // Create Headquarter
    $('#create-hq-form').submit(function(e) {
        e.preventDefault();
        
        $.easyAjax({
            url: "{{ route('pharma-areas.headquarters.store') }}",
            type: "POST",
            container: '#create-hq-form',
            data: $(this).serialize(),
            success: function(response) {
                if(response.status == 'success'){
                    Swal.fire({
                        icon: 'success',
                        title: 'Created!',
                        text: 'HeadQuarter created successfully',
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                }
            }
        });
    });

    // Assign Stations - Redirect to assign page
    $('.assign-stations').click(function() {
        window.location.href = "{{ route('pharma-areas.assign-headquarters') }}";
    });

    // Edit HQ
    $('.edit-hq').click(function() {
        const hqId = $(this).data('hq-id');
        const hqName = $(this).data('hq-name');
        const areaId = $(this).data('area-id');
        
        Swal.fire({
            title: 'Edit HeadQuarter',
            html: `
                <input type="text" id="swal-hq-name" class="swal2-input" value="${hqName}" placeholder="HeadQuarter Name" style="margin-bottom: 10px;">
                <select id="swal-area-id" class="swal2-select">
                    <option value="">-- No Area --</option>
                    @foreach(\App\Models\PharmaArea::all() as $area)
                        <option value="{{ $area->id }}" ${areaId == {{ $area->id }} ? 'selected' : ''}>{{ $area->name }}</option>
                    @endforeach
                </select>
            `,
            showCancelButton: true,
            confirmButtonText: 'Save Changes',
            preConfirm: () => {
                return {
                    name: document.getElementById('swal-hq-name').value,
                    area_id: document.getElementById('swal-area-id').value
                };
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                $.easyAjax({
                    url: "{{ url('account/pharma-areas/headquarters') }}/" + hqId,
                    type: "PUT",
                    data: result.value,
                    success: function() {
                        window.location.reload();
                    }
                });
            }
        });
    });

    // Remove All Stations
    $('.remove-all-stations').click(function() {
        const hqId = $(this).data('hq-id');
        const hqName = $(this).data('hq-name');
        
        Swal.fire({
            title: 'Remove All Stations?',
            html: `This will remove <strong>ALL</strong> stations from <strong>"${hqName}"</strong>.<br><br><span class="text-danger">This cannot be undone!</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, remove all!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.easyAjax({
                    url: "{{ url('account/pharma-areas/delete-all-hq-assignments') }}",
                    type: "POST",
                    data: { headquarter_id: hqId },
                    success: function() {
                        window.location.reload();
                    }
                });
            }
        });
    });

    // Delete HQ
    $('.delete-hq').click(function() {
        const hqId = $(this).data('hq-id');
        const hqName = $(this).data('hq-name');
        
        Swal.fire({
            title: 'Delete HeadQuarter?',
            html: `Delete <strong>"${hqName}"</strong> and all its assignments?<br><br><span class="text-danger">This cannot be undone!</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.easyAjax({
                    url: "{{ url('account/pharma-areas/headquarters') }}/" + hqId,
                    type: "DELETE",
                    success: function(response) {
                        if(response.status == 'success'){
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'HeadQuarter deleted successfully',
                                timer: 1500
                            }).then(() => {
                                window.location.reload();
                            });
                        }
                    }
                });
            }
        });
    });
</script>
@endpush
