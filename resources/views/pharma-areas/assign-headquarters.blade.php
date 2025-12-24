@extends('layouts.app')

@push('styles')
<style>
    .assignment-card {
        border-left: 4px solid #8bab4c;
        margin-bottom: 1rem;
    }
    .assignment-card.unassigned {
        border-left: 4px solid #dc3545;
        background: #fff5f5;
    }
    .assignment-card.unassigned .card-header {
        background: #ffe5e5;
    }
    .station-badge {
        margin: 3px;
        font-size: 11px;
        padding: 4px 8px;
    }
    .hq-section {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="content-wrapper">
    {{-- Assignment Form --}}
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fa fa-plus-circle text-success"></i> Assign Stations to HeadQuarter</h5>
        </div>
        <div class="card-body">
                <form id="assign-form">
                @csrf
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label><strong>1. Select HeadQuarter</strong> <sup class="text-danger">*</sup></label>
                        <select class="form-control select-picker" name="headquarter_id" id="hq-select" data-live-search="true" required>
                            <option value="">-- Select HeadQuarter --</option>
                            @foreach($headquarters as $hq)
                                <option value="{{ $hq->id }}">
                                    {{ $hq->name }}
                                    @if($hq->area)
                                        ({{ $hq->area->name }})
                                    @endif
                                    - Ex: {{ $hq->exstations->count() }}, Out: {{ $hq->outstations->count() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="hq-section">
                            <label><strong>2. Select Ex-Stations</strong></label>
                            <small class="d-block text-muted mb-2">Choose one or more ex-stations</small>
                            <select class="form-control select-picker" name="exstation_ids[]" id="exstation-select" multiple data-live-search="true" data-actions-box="true">
                                @foreach($exstations as $station)
                                    <option value="{{ $station->id }}">{{ $station->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="hq-section">
                            <label><strong>3. Select Out-Stations</strong></label>
                            <small class="d-block text-muted mb-2">Choose one or more out-stations</small>
                            <select class="form-control select-picker" name="outstation_ids[]" id="outstation-select" multiple data-live-search="true" data-actions-box="true">
                                @foreach($outstations as $station)
                                    <option value="{{ $station->id }}">{{ $station->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="w-100 border-top-grey d-flex justify-content-start px-4 py-3 mt-3">
                    <x-forms.button-primary id="save-assignments" icon="save">
                        Save Assignments
                    </x-forms.button-primary>
                    <x-forms.button-secondary class="ml-3" onclick="window.location.reload()">
                        Clear Form
                    </x-forms.button-secondary>
                </div>
            </form>
        </div>
    </div>

    {{-- Current Assignments --}}
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa fa-list"></i> Current Headquarter Assignments</h5>
            <span class="badge badge-secondary">{{ $headquarters->count() }} Headquarters</span>
        </div>
        <div class="card-body">
            @foreach($headquarters as $hq)
                @php
                    $exCount = $hq->exstations->count();
                    $outCount = $hq->outstations->count();
                    $total = $exCount + $outCount;
                @endphp
                
                <div class="card assignment-card {{ $total == 0 ? 'unassigned' : '' }}">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <strong>
                            <i class="fa fa-building {{ $total == 0 ? 'text-danger' : 'text-primary' }}"></i> {{ $hq->name }}
                            @if($hq->area)
                                <span class="badge badge-info ml-2">{{ $hq->area->name }}</span>
                            @endif
                            @if($total == 0)
                                <span class="badge badge-danger ml-2"><i class="fa fa-exclamation-triangle"></i> Unassigned</span>
                            @endif
                        </strong>
                        <div>
                            <span class="badge {{ $total == 0 ? 'badge-danger' : 'badge-dark' }} mr-2">Total: {{ $total }}</span>
                            @if($total > 0)
                            <div class="dropdown d-inline-block">
                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item edit-hq-assignments" href="javascript:;" data-hq-id="{{ $hq->id }}" data-hq-name="{{ $hq->name }}">
                                        <i class="fa fa-edit text-primary"></i> Edit Assignments
                                    </a>
                                    <a class="dropdown-item delete-all-assignments" href="javascript:;" data-hq-id="{{ $hq->id }}" data-hq-name="{{ $hq->name }}">
                                        <i class="fa fa-trash text-danger"></i> Remove All Stations
                                    </a>
                                </div>
                            </div>
                            @endif
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
                                            <span class="badge badge-primary station-badge">
                                                {{ $station->name }}
                                            </span>
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
                                            <span class="badge badge-info station-badge">
                                                {{ $station->name }}
                                            </span>
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

    // Prepare HQ assignments data for edit functionality
    const hqAssignments = {
        @foreach($headquarters as $hq)
            {{ $hq->id }}: {
                exstations: [{{ $hq->exstations->pluck('id')->implode(',') }}],
                outstations: [{{ $hq->outstations->pluck('id')->implode(',') }}]
            },
        @endforeach
    };

    // Single Save Button Handler
    $('#save-assignments').click(function(e) {
        e.preventDefault();
        
        const hqId = $('#hq-select').val();
        const exStationIds = $('#exstation-select').val() || [];
        const outStationIds = $('#outstation-select').val() || [];
        
        // Validation
        if (!hqId) {
            Swal.fire('Error', 'Please select a headquarter first', 'error');
            return;
        }
        
        if (exStationIds.length === 0 && outStationIds.length === 0) {
            Swal.fire('Error', 'Please select at least one ex-station or out-station', 'error');
            return;
        }
        
        // Prepare promises for both types
        let promises = [];
        
        // Assign Ex-Stations
        if (exStationIds.length > 0) {
            promises.push(
                $.ajax({
                    url: "{{ route('pharma-areas.assign-headquarters.store') }}",
                    type: "POST",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        headquarter_id: hqId,
                        station_type: 'exstation',
                        station_ids: exStationIds
                    }
                })
            );
        }
        
        // Assign Out-Stations
        if (outStationIds.length > 0) {
            promises.push(
                $.ajax({
                    url: "{{ route('pharma-areas.assign-headquarters.store') }}",
                    type: "POST",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        headquarter_id: hqId,
                        station_type: 'outstation',
                        station_ids: outStationIds
                    }
                })
            );
        }
        
        // Execute all assignments
        Promise.all(promises)
            .then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: `Assigned ${exStationIds.length} ex-stations and ${outStationIds.length} out-stations successfully`,
                    timer: 2000
                }).then(() => {
                    window.location.reload();
                });
            })
            .catch((error) => {
                console.error(error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to save assignments. Please try again.'
                });
            });
    });

    // Edit Assignments - Pre-fill form with current stations
    $('.edit-hq-assignments').click(function() {
        const hqId = $(this).data('hq-id');
        const hqName = $(this).data('hq-name');
        
        // Set HQ
        $('#hq-select').val(hqId).selectpicker('refresh');
        
        // Pre-select current ex-stations
        if (hqAssignments[hqId] && hqAssignments[hqId].exstations.length > 0) {
            $('#exstation-select').selectpicker('val', hqAssignments[hqId].exstations);
        } else {
            $('#exstation-select').selectpicker('deselectAll');
        }
        
        // Pre-select current out-stations
        if (hqAssignments[hqId] && hqAssignments[hqId].outstations.length > 0) {
            $('#outstation-select').selectpicker('val', hqAssignments[hqId].outstations);
        } else {
            $('#outstation-select').selectpicker('deselectAll');
        }
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $('#assign-form').offset().top - 100
        }, 500);
        
        Swal.fire({
            icon: 'info',
            title: 'Edit Mode',
            html: `Now editing <strong>"${hqName}"</strong><br><br>Current stations are pre-selected.<br>Modify and click <strong>Save Assignments</strong>.`,
            timer: 3000,
            showConfirmButton: false
        });
    });

    // Delete All Assignments
    $('.delete-all-assignments').click(function() {
        const hqId = $(this).data('hq-id');
        const hqName = $(this).data('hq-name');
        
        Swal.fire({
            title: 'Remove All Stations?',
            html: `This will remove <strong>ALL</strong> ex-stations and out-stations from <strong>"${hqName}"</strong>.<br><br>This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove all!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Get all assignment IDs for this HQ
                $.easyAjax({
                    url: "{{ url('account/pharma-areas/delete-all-hq-assignments') }}",
                    type: "POST",
                    data: { headquarter_id: hqId },
                    success: function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Removed!',
                            text: 'All stations removed successfully',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                });
            }
        });
    });

    // Auto-load assigned stations when HQ changes
    $('#hq-select').change(function() {
        const hqId = $(this).val();
        
        if (!hqId) {
            // If no HQ selected, clear all selections
            $('#exstation-select').selectpicker('deselectAll');
            $('#outstation-select').selectpicker('deselectAll');
            return;
        }
        
        // Show loading indicator
        Swal.fire({
            title: 'Loading...',
            text: 'Fetching assigned stations',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Fetch assigned stations from server
        $.ajax({
            url: "{{ url('account/pharma-areas/headquarters') }}/" + hqId + "/stations",
            type: "GET",
            success: function(response) {
                Swal.close();
                
                if (response.status === 'success') {
                    // Check if there are any existing mappings
                    const hasExstations = response.exstations && response.exstations.length > 0;
                    const hasOutstations = response.outstations && response.outstations.length > 0;
                    
                    if (hasExstations || hasOutstations) {
                        // Auto-select ex-stations
                        if (hasExstations) {
                            $('#exstation-select').selectpicker('val', response.exstations);
                        } else {
                            $('#exstation-select').selectpicker('deselectAll');
                        }
                        
                        // Auto-select out-stations
                        if (hasOutstations) {
                            $('#outstation-select').selectpicker('val', response.outstations);
                        } else {
                            $('#outstation-select').selectpicker('deselectAll');
                        }
                        
                        // Show info message
                        Swal.fire({
                            icon: 'info',
                            title: 'Stations Loaded',
                            html: `Found existing mappings:<br>
                                   <strong>${response.exstations.length}</strong> Ex-Stations<br>
                                   <strong>${response.outstations.length}</strong> Out-Stations<br><br>
                                   <small>You can modify and save changes</small>`,
                            timer: 3000,
                            showConfirmButton: false
                        });
                    } else {
                        // No existing mappings
                        $('#exstation-select').selectpicker('deselectAll');
                        $('#outstation-select').selectpicker('deselectAll');
                        
                        Swal.fire({
                            icon: 'info',
                            title: 'No Existing Mappings',
                            text: 'This headquarter has no stations assigned yet. Please select stations below.',
                            timer: 2500,
                            showConfirmButton: false
                        });
                    }
                }
            },
            error: function(xhr) {
                Swal.close();
                console.error('Error fetching stations:', xhr);
                
                // Clear selections on error
                $('#exstation-select').selectpicker('deselectAll');
                $('#outstation-select').selectpicker('deselectAll');
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load assigned stations. Please try again.',
                    timer: 2000
                });
            }
        });
    });
</script>
@endpush
