@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="row">
            <!-- Zones -->
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header bg-white text-dark font-weight-bold">
                        <i class="fa fa-globe"></i> Zones
                    </div>
                    <div class="card-body">
                        <form id="add-zone-form" class="mb-3">
                            @csrf
                            <div class="input-group">
                                <input type="text" class="form-control" name="name" placeholder="Zone Name" required>
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit"><i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                        </form>
                        <ul class="list-group">
                            @forelse($zones as $zone)
                                <li class="list-group-item d-flex justify-content-between">
                                    {{ $zone->name }}
                                    <a href="javascript:void(0)" class="text-danger delete-zone" data-id="{{ $zone->id }}">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">No zones added</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Regions -->
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header bg-white text-dark font-weight-bold">
                        <i class="fa fa-map"></i> Regions
                    </div>
                    <div class="card-body">
                        <form id="add-region-form" class="mb-3">
                            @csrf
                            <div class="input-group">
                                <input type="text" class="form-control" name="name" placeholder="Region Name" required>
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit"><i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                        </form>
                        <ul class="list-group">
                            @forelse($regions as $region)
                                <li class="list-group-item">{{ $region->name }}</li>
                            @empty
                                <li class="list-group-item text-muted">No regions added</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Areas -->
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header bg-white text-dark font-weight-bold">
                        <i class="fa fa-map-marker"></i> Areas
                    </div>
                    <div class="card-body">
                        <form id="add-area-form" class="mb-3">
                            @csrf
                            <div class="input-group">
                                <input type="text" class="form-control" name="name" placeholder="Area Name" required>
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit"><i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                        </form>
                        <ul class="list-group">
                            @forelse($areas as $area)
                                <li class="list-group-item">{{ $area->name }}</li>
                            @empty
                                <li class="list-group-item text-muted">No areas added</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Headquarters -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white text-dark font-weight-bold">
                        <i class="fa fa-building"></i> Headquarters
                    </div>
                    <div class="card-body">
                        <form id="add-hq-form" class="mb-3">
                            @csrf
                            <div class="input-group">
                                <input type="text" class="form-control" name="name" placeholder="Headquarter Name" required>
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit"><i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                        </form>
                        <ul class="list-group">
                            @forelse($headquarters as $hq)
                                <li class="list-group-item">{{ $hq->name }}</li>
                            @empty
                                <li class="list-group-item text-muted">No headquarters added</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Stations -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-white text-dark font-weight-bold">
                        <i class="fa fa-location-arrow"></i> Stations
                    </div>
                    <div class="card-body">
                        <h6>Ex-Stations</h6>
                        <form id="add-exstation-form" class="mb-3">
                            @csrf
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" name="name" placeholder="Ex-station Name" required>
                                <div class="input-group-append">
                                    <button class="btn btn-sm btn-primary" type="submit"><i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                        </form>

                        <h6 class="mt-3">Out-Stations</h6>
                        <form id="add-outstation-form" class="mb-3">
                            @csrf
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" name="name" placeholder="Out-station Name" required>
                                <div class="input-group-append">
                                    <button class="btn btn-sm btn-primary" type="submit"><i class="fa fa-plus"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hierarchical Assignments -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fa fa-link"></i> Hierarchical Assignments</h5>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-tabs" id="assignmentTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="assign-region-tab" data-toggle="tab" href="#assign-region" role="tab">
                                    <i class="fa fa-arrow-right"></i> Assign Region to Zone
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="assign-area-tab" data-toggle="tab" href="#assign-area" role="tab">
                                    <i class="fa fa-arrow-right"></i> Assign Area to Region
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="assign-hq-tab" data-toggle="tab" href="#assign-hq" role="tab">
                                    <i class="fa fa-arrow-right"></i> Assign Headquarter to Area
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content mt-3" id="assignmentTabContent">
                            <!-- Assign Region to Zone -->
                            <div class="tab-pane fade show active" id="assign-region" role="tabpanel">
                                <form id="assign-region-form">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label>Select Zone <sup class="text-danger">*</sup></label>
                                            <select class="form-control" name="zone_id" required>
                                                <option value="">-- Select Zone --</option>
                                                @foreach($zones as $zone)
                                                    <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Select Regions <sup class="text-danger">*</sup></label>
                                            <select class="form-control select-picker" name="region_ids[]" multiple data-live-search="true" required>
                                                @foreach($regions as $region)
                                                    <option value="{{ $region->id }}">
                                                        {{ $region->name }}
                                                        @if($region->zone)
                                                            (Currently in: {{ $region->zone->name }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label>&nbsp;</label>
                                            <button type="submit" class="btn btn-success btn-block">
                                                <i class="fa fa-check"></i> Assign
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <h6 class="mt-4">Current Assignments:</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Zone</th>
                                                <th>Regions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($zones as $zone)
                                                <tr>
                                                    <td><strong>{{ $zone->name }}</strong></td>
                                                    <td>
                                                        @if($zone->regions->count() > 0)
                                                            @foreach($zone->regions as $region)
                                                                <span class="badge badge-info">{{ $region->name }}</span>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">No regions assigned</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="2" class="text-center text-muted">No zones created yet</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Assign Area to Region -->
                            <div class="tab-pane fade" id="assign-area" role="tabpanel">
                                <form id="assign-area-form">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label>Select Region <sup class="text-danger">*</sup></label>
                                            <select class="form-control" name="region_id" required>
                                                <option value="">-- Select Region --</option>
                                                @foreach($regions as $region)
                                                    <option value="{{ $region->id }}">
                                                        {{ $region->name }}
                                                        @if($region->zone)
                                                            ({{ $region->zone->name }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Select Areas <sup class="text-danger">*</sup></label>
                                            <select class="form-control select-picker" name="area_ids[]" multiple data-live-search="true" required>
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
                                        <div class="col-md-2">
                                            <label>&nbsp;</label>
                                            <button type="submit" class="btn btn-success btn-block">
                                                <i class="fa fa-check"></i> Assign
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <h6 class="mt-4">Current Assignments:</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Region</th>
                                                <th>Areas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($regions as $region)
                                                <tr>
                                                    <td><strong>{{ $region->name }}</strong></td>
                                                    <td>
                                                        @if($region->areas->count() > 0)
                                                            @foreach($region->areas as $area)
                                                                <span class="badge badge-success">{{ $area->name }}</span>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">No areas assigned</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="2" class="text-center text-muted">No regions created yet</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Assign Headquarter to Area -->
                            <div class="tab-pane fade" id="assign-hq" role="tabpanel">
                                <form id="assign-hq-to-area-form">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label>Select Area <sup class="text-danger">*</sup></label>
                                            <select class="form-control" name="area_id" required>
                                                <option value="">-- Select Area --</option>
                                                @foreach($areas as $area)
                                                    <option value="{{ $area->id }}">
                                                        {{ $area->name }}
                                                        @if($area->region)
                                                            ({{ $area->region->name }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Select Headquarters <sup class="text-danger">*</sup></label>
                                            <select class="form-control select-picker" name="headquarter_ids[]" multiple data-live-search="true" required>
                                                @foreach($headquarters as $hq)
                                                    <option value="{{ $hq->id }}">
                                                        {{ $hq->name }}
                                                        @if($hq->area)
                                                            (Currently in: {{ $hq->area->name }})
                                                        @endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label>&nbsp;</label>
                                            <button type="submit" class="btn btn-success btn-block">
                                                <i class="fa fa-check"></i> Assign
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <h6 class="mt-4">Current Assignments:</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>Area</th>
                                                <th>Headquarters</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($areas as $area)
                                                <tr>
                                                    <td><strong>{{ $area->name }}</strong></td>
                                                    <td>
                                                        @if($area->headquarters->count() > 0)
                                                            @foreach($area->headquarters as $hq)
                                                                <span class="badge badge-primary">{{ $hq->name }}</span>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">No headquarters assigned</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="2" class="text-center text-muted">No areas created yet</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('#add-zone-form').submit(function(e) {
        e.preventDefault();
        $.easyAjax({
            url: "{{ route('pharma-areas.zones.store') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function() { window.location.reload(); }
        });
    });

    $('#add-region-form').submit(function(e) {
        e.preventDefault();
        $.easyAjax({
            url: "{{ route('pharma-areas.regions.store') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function() { window.location.reload(); }
        });
    });

    $('#add-area-form').submit(function(e) {
        e.preventDefault();
        $.easyAjax({
            url: "{{ route('pharma-areas.areas.store') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function() { window.location.reload(); }
        });
    });

    $('#add-hq-form').submit(function(e) {
        e.preventDefault();
        $.easyAjax({
            url: "{{ route('pharma-areas.headquarters.store') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function() { window.location.reload(); }
        });
    });

    $('#add-exstation-form').submit(function(e) {
        e.preventDefault();
        $.easyAjax({
            url: "{{ route('pharma-areas.exstations.store') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function() { window.location.reload(); }
        });
    });

    $('#add-outstation-form').submit(function(e) {
        e.preventDefault();
        $.easyAjax({
            url: "{{ route('pharma-areas.outstations.store') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function() { window.location.reload(); }
        });
    });

    // Assignment forms
    $('#assign-region-form').submit(function(e) {
        e.preventDefault();
        $.easyAjax({
            url: "{{ route('pharma-areas.assign-region-to-zone') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function() { window.location.reload(); }
        });
    });

    $('#assign-area-form').submit(function(e) {
        e.preventDefault();
        $.easyAjax({
            url: "{{ route('pharma-areas.assign-area-to-region') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function() { window.location.reload(); }
        });
    });

    $('#assign-hq-to-area-form').submit(function(e) {
        e.preventDefault();
        $.easyAjax({
            url: "{{ route('pharma-areas.assign-headquarter-to-area') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function() { window.location.reload(); }
        });
    });

    // Delete zone
    $('.delete-zone').click(function() {
        const zoneId = $(this).data('id');
        if (confirm('Are you sure you want to delete this zone?')) {
            $.easyAjax({
                url: "{{ url('pharma-areas/zones') }}/" + zoneId,
                type: "DELETE",
                success: function() { window.location.reload(); }
            });
        }
    });
</script>
@endpush

