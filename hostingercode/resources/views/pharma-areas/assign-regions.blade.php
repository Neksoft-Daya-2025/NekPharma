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
    .region-badge {
        margin: 3px;
        font-size: 11px;
        padding: 4px 8px;
    }
    .zone-section {
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
            <h5 class="mb-0"><i class="fa fa-plus-circle text-success"></i> Assign Regions to Zone</h5>
        </div>
        <div class="card-body">
            <form id="assign-form">
                @csrf
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label><strong>1. Select Zone</strong> <sup class="text-danger">*</sup></label>
                        <select class="form-control select-picker" name="zone_id" id="zone-select" data-live-search="true" required>
                            <option value="">-- Select Zone --</option>
                            @foreach($zones as $zone)
                                <option value="{{ $zone->id }}">
                                    {{ $zone->name }}
                                    - Regions: {{ $zone->regions->count() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="zone-section">
                            <label><strong>2. Select Regions</strong></label>
                            <small class="d-block text-muted mb-2">Choose one or more regions to assign to this zone</small>
                            <select class="form-control select-picker" name="region_ids[]" id="region-select" multiple data-live-search="true" data-actions-box="true">
                                @foreach($regions as $region)
                                    <option value="{{ $region->id }}">
                                        {{ $region->name }}
                                        @if($region->zone)
                                            (Currently in: {{ $region->zone->name }})
                                        @endif
                                        - Areas: {{ $region->areas->count() }}
                                    </option>
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
            <h5 class="mb-0"><i class="fa fa-list"></i> Current Zone Assignments</h5>
            <span class="badge badge-secondary">{{ $zones->count() }} Zones</span>
        </div>
        <div class="card-body">
            @forelse($zones as $zone)
                <div class="card assignment-card {{ $zone->regions->count() == 0 ? 'unassigned' : '' }}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">
                                <i class="fa fa-globe"></i> {{ $zone->name }}
                            </h6>
                        </div>
                        <div class="d-flex align-items-center">
                            @if($zone->regions->count() > 0)
                                <span class="badge badge-success mr-2">{{ $zone->regions->count() }} Regions</span>
                            @else
                                <span class="badge badge-danger mr-2">No Regions Assigned</span>
                            @endif
                            
                            <div class="dropdown">
                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item edit-assignment" href="javascript:;" 
                                       data-zone-id="{{ $zone->id }}" 
                                       data-zone-name="{{ $zone->name }}"
                                       data-region-ids="{{ $zone->regions->pluck('id')->implode(',') }}">
                                        <i class="fa fa-edit text-primary"></i> Edit Assignments
                                    </a>
                                    @if($zone->regions->count() > 0)
                                    <a class="dropdown-item remove-all-assignments" href="javascript:;" 
                                       data-zone-id="{{ $zone->id }}"
                                       data-zone-name="{{ $zone->name }}">
                                        <i class="fa fa-times text-danger"></i> Remove All Regions
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($zone->regions->count() > 0)
                            <strong>Assigned Regions:</strong><br>
                            @foreach($zone->regions as $region)
                                <span class="badge badge-info region-badge">
                                    {{ $region->name }} ({{ $region->areas->count() }} areas)
                                </span>
                            @endforeach
                        @else
                            <p class="text-danger mb-0">
                                <i class="fa fa-exclamation-triangle"></i> No regions assigned to this zone yet
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> No zones found. Please create zones first.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Edit Assignment
    $('.edit-assignment').click(function() {
        const zoneId = $(this).data('zone-id');
        const zoneName = $(this).data('zone-name');
        const regionIds = $(this).data('region-ids').toString().split(',').filter(Boolean);
        
        $('#zone-select').val(zoneId).trigger('change');
        $('#region-select').val(regionIds).trigger('change');
        
        $('html, body').animate({
            scrollTop: $('#assign-form').offset().top - 100
        }, 500);
        
        Swal.fire({
            icon: 'info',
            title: 'Edit Mode',
            text: 'Form loaded with current assignments for ' + zoneName,
            timer: 2000
        });
    });

    // Remove All Assignments
    $('.remove-all-assignments').click(function() {
        const zoneId = $(this).data('zone-id');
        const zoneName = $(this).data('zone-name');
        
        Swal.fire({
            title: 'Remove All Regions?',
            html: `Remove all regions from "<strong>${zoneName}</strong>"?<br><br><span class="text-danger">This will unassign all regions from this zone!</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, remove all!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.easyAjax({
                    url: "{{ route('pharma-areas.assign-regions.store') }}",
                    type: "POST",
                    data: { 
                        _token: "{{ csrf_token() }}",
                        zone_id: zoneId,
                        region_ids: []
                    },
                    success: function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Removed!',
                            text: 'All regions removed from zone',
                            timer: 1500
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                });
            }
        });
    });

    $('#assign-form').submit(function(e) {
        e.preventDefault();
        
        const zoneId = $('#zone-select').val();
        const regionIds = $('#region-select').val();
        
        if (!zoneId) {
            Swal.fire('Error', 'Please select a zone', 'error');
            return;
        }
        
        if (!regionIds || regionIds.length === 0) {
            Swal.fire('Error', 'Please select at least one region', 'error');
            return;
        }
        
        $.easyAjax({
            url: "{{ route('pharma-areas.assign-regions.store') }}",
            type: "POST",
            container: '#assign-form',
            data: $(this).serialize(),
            success: function(response) {
                if(response.status == 'success'){
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Regions assigned successfully',
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                }
            }
        });
    });
</script>
@endpush
