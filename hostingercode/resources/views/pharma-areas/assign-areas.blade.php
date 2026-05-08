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
    .area-badge {
        margin: 3px;
        font-size: 11px;
        padding: 4px 8px;
    }
    .region-section {
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
            <h5 class="mb-0"><i class="fa fa-plus-circle text-success"></i> Assign Areas to Region</h5>
        </div>
        <div class="card-body">
            <form id="assign-form">
                @csrf
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label><strong>1. Select Region</strong> <sup class="text-danger">*</sup></label>
                        <select class="form-control select-picker" name="region_id" id="region-select" data-live-search="true" required>
                            <option value="">-- Select Region --</option>
                            @foreach($regions as $region)
                                <option value="{{ $region->id }}">
                                    {{ $region->name }}
                                    @if($region->zone)
                                        (Zone: {{ $region->zone->name }})
                                    @endif
                                    - Areas: {{ $region->areas->count() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="region-section">
                            <label><strong>2. Select Areas</strong></label>
                            <small class="d-block text-muted mb-2">Choose one or more areas to assign to this region</small>
                            <select class="form-control select-picker" name="area_ids[]" id="area-select" multiple data-live-search="true" data-actions-box="true">
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}">
                                        {{ $area->name }}
                                        @if($area->region)
                                            (Currently in: {{ $area->region->name }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="w-100 border-top-grey d-flex justify-content-start px-4 py-3 mt-3">
                    {{-- Must be type="submit" so the form fires jQuery submit handler (button-primary component uses type="button") --}}
                    <button type="submit" id="save-assignments" class="btn btn-primary rounded f-14 p-2">
                        <i class="fa fa-save mr-1"></i> Save Assignments
                    </button>
                    <x-forms.button-secondary class="ml-3" onclick="window.location.reload()">
                        Clear Form
                    </x-forms.button-secondary>
                    @include('sections.password-autocomplete-hide')
                </div>
            </form>
        </div>
    </div>

    {{-- Current Assignments --}}
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa fa-list"></i> Current Region Assignments</h5>
            <span class="badge badge-secondary">{{ $regions->count() }} Regions</span>
        </div>
        <div class="card-body">
            @forelse($regions as $region)
                <div class="card assignment-card {{ $region->areas->count() == 0 ? 'unassigned' : '' }}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">
                                <i class="fa fa-map"></i> {{ $region->name }}
                                @if($region->zone)
                                    <span class="badge badge-info ml-2">Zone: {{ $region->zone->name }}</span>
                                @endif
                            </h6>
                        </div>
                        <div class="d-flex align-items-center">
                            @if($region->areas->count() > 0)
                                <span class="badge badge-success mr-2">{{ $region->areas->count() }} Areas</span>
                            @else
                                <span class="badge badge-danger mr-2">No Areas Assigned</span>
                            @endif
                            
                            <div class="dropdown">
                                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                    <i class="fa fa-ellipsis-v"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item edit-assignment" href="javascript:;" 
                                       data-region-id="{{ $region->id }}" 
                                       data-region-name="{{ $region->name }}"
                                       data-area-ids="{{ $region->areas->pluck('id')->implode(',') }}">
                                        <i class="fa fa-edit text-primary"></i> Edit Assignments
                                    </a>
                                    @if($region->areas->count() > 0)
                                    <a class="dropdown-item remove-all-assignments" href="javascript:;" 
                                       data-region-id="{{ $region->id }}"
                                       data-region-name="{{ $region->name }}">
                                        <i class="fa fa-times text-danger"></i> Remove All Areas
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($region->areas->count() > 0)
                            <strong>Assigned Areas:</strong><br>
                            @foreach($region->areas as $area)
                                <span class="badge badge-success area-badge">{{ $area->name }}</span>
                            @endforeach
                        @else
                            <p class="text-danger mb-0">
                                <i class="fa fa-exclamation-triangle"></i> No areas assigned to this region yet
                            </p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> No regions found. Please create regions first.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Edit Assignment
        $('.edit-assignment').click(function() {
            const regionId = $(this).data('region-id');
            const regionName = $(this).data('region-name');
            const areaIds = $(this).data('area-ids').toString().split(',').filter(Boolean);
            
            $('#region-select').val(regionId).trigger('change');
            $('#area-select').val(areaIds).trigger('change');
            
            $('html, body').animate({
                scrollTop: $('#assign-form').offset().top - 100
            }, 500);
            
            Swal.fire({
                icon: 'info',
                title: 'Edit Mode',
                text: 'Form loaded with current assignments for ' + regionName,
                timer: 2000
            });
        });

        // Remove All Assignments
        $('.remove-all-assignments').click(function() {
            const regionId = $(this).data('region-id');
            const regionName = $(this).data('region-name');
            
            Swal.fire({
                title: 'Remove All Areas?',
                html: `Remove all areas from "<strong>${regionName}</strong>"?<br><br><span class="text-danger">This will unassign all areas from this region!</span>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, remove all!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.easyAjax({
                        url: "{{ route('pharma-areas.assign-areas.store') }}",
                        type: "POST",
                        data: { 
                            _token: "{{ csrf_token() }}",
                            region_id: regionId,
                            area_ids: []
                        },
                        success: function() {
                            Swal.fire({
                                icon: 'success',
                                title: 'Removed!',
                                text: 'All areas removed from region',
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

            const regionId = $('#region-select').val();
            const areaIds = $('#area-select').val() || [];

            if (!regionId) {
                Swal.fire('Error', 'Please select a region', 'error');
                return;
            }

            if (!areaIds.length) {
                Swal.fire('Error', 'Please select at least one area', 'error');
                return;
            }

            $.easyAjax({
                url: "{{ route('pharma-areas.assign-areas.store') }}",
                type: 'POST',
                container: '#assign-form',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    region_id: regionId,
                    area_ids: areaIds
                },
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message || 'Areas assigned successfully',
                            timer: 1500
                        }).then(function () {
                            window.location.reload();
                        });
                    }
                }
            });
        });
    });
</script>
@endpush
