@extends('layouts.app')

@push('styles')
<style>
    .overview-card {
        border-left: 4px solid;
        margin-bottom: 1rem;
        transition: all 0.3s;
    }
    .overview-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }
    .zone-card { border-left-color: #6f42c1; }
    .region-card { border-left-color: #007bff; }
    .area-card { border-left-color: #28a745; }
    .hq-card { border-left-color: #8bab4c; }
    .station-badge {
        margin: 2px;
        font-size: 11px;
        padding: 3px 8px;
    }
    .stat-card {
        border-radius: 8px;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.3s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }
    .stat-card.zone { background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%); color: white; }
    .stat-card.region { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; }
    .stat-card.area { background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); color: white; }
    .stat-card.hq { background: linear-gradient(135deg, #8bab4c 0%, #6d8a3c 100%); color: white; }
    .stat-card.exstation { background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%); color: white; }
    .stat-card.outstation { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: #333; }
    .hierarchy-level {
        margin-left: 20px;
        border-left: 2px dashed #dee2e6;
        padding-left: 15px;
        margin-top: 10px;
    }
    .collapse-toggle {
        cursor: pointer;
        user-select: none;
    }
    .collapse-toggle:hover {
        background-color: #f8f9fa;
    }
    .unassigned-badge {
        background-color: #dc3545;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
    }
    .search-filter {
        margin-bottom: 1.5rem;
    }
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6c757d;
    }
</style>
@endpush

@section('content')
<div class="content-wrapper">
    {{-- Statistics Dashboard --}}
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="stat-card zone">
                <h3 class="mb-1">{{ $stats['zones'] }}</h3>
                <p class="mb-0"><i class="fa fa-globe"></i> Zones</p>
                @if($stats['unassigned_regions'] > 0)
                    <small class="badge badge-light mt-1">{{ $stats['unassigned_regions'] }} Unassigned</small>
                @endif
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card region">
                <h3 class="mb-1">{{ $stats['regions'] }}</h3>
                <p class="mb-0"><i class="fa fa-map"></i> Regions</p>
                @if($stats['unassigned_regions'] > 0)
                    <small class="badge badge-light mt-1">{{ $stats['unassigned_regions'] }} Unassigned</small>
                @endif
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card area">
                <h3 class="mb-1">{{ $stats['areas'] }}</h3>
                <p class="mb-0"><i class="fa fa-map-marker-alt"></i> Areas</p>
                @if($stats['unassigned_areas'] > 0)
                    <small class="badge badge-light mt-1">{{ $stats['unassigned_areas'] }} Unassigned</small>
                @endif
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card hq">
                <h3 class="mb-1">{{ $stats['headquarters'] }}</h3>
                <p class="mb-0"><i class="fa fa-building"></i> Headquarters</p>
                @if($stats['unassigned_headquarters'] > 0)
                    <small class="badge badge-light mt-1">{{ $stats['unassigned_headquarters'] }} Unassigned</small>
                @endif
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card exstation">
                <h3 class="mb-1">{{ $stats['exstations'] }}</h3>
                <p class="mb-0"><i class="fa fa-map-pin"></i> Ex-Stations</p>
                @if($stats['unassigned_exstations'] > 0)
                    <small class="badge badge-light mt-1">{{ $stats['unassigned_exstations'] }} Unassigned</small>
                @endif
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card outstation">
                <h3 class="mb-1">{{ $stats['outstations'] }}</h3>
                <p class="mb-0"><i class="fa fa-location-arrow"></i> Out-Stations</p>
                @if($stats['unassigned_outstations'] > 0)
                    <small class="badge badge-light mt-1">{{ $stats['unassigned_outstations'] }} Unassigned</small>
                @endif
            </div>
        </div>
    </div>

    {{-- Search and Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <input type="text" id="search-overview" class="form-control" placeholder="Search zones, regions, areas, headquarters, stations...">
                </div>
                <div class="col-md-6">
                    <select id="filter-type" class="form-control">
                        <option value="">All Types</option>
                        <option value="zone">Zones</option>
                        <option value="region">Regions</option>
                        <option value="area">Areas</option>
                        <option value="headquarter">Headquarters</option>
                        <option value="exstation">Ex-Stations</option>
                        <option value="outstation">Out-Stations</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Hierarchical Mapping View --}}
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa fa-sitemap"></i> Complete Mapping Overview</h5>
            <div>
                <button class="btn btn-sm btn-secondary" onclick="expandAll()">
                    <i class="fa fa-expand"></i> Expand All
                </button>
                <button class="btn btn-sm btn-secondary ml-2" onclick="collapseAll()">
                    <i class="fa fa-compress"></i> Collapse All
                </button>
            </div>
        </div>
        <div class="card-body" id="mapping-container">
            @forelse($zones as $zone)
                <div class="card overview-card zone-card mb-3" data-type="zone" data-name="{{ strtolower($zone->name) }}">
                    <div class="card-header bg-light collapse-toggle" data-toggle="collapse" data-target="#zone-{{ $zone->id }}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fa fa-globe text-purple"></i>
                                <strong>{{ $zone->name }}</strong>
                                <span class="badge badge-secondary ml-2">{{ $zone->regions->count() }} Regions</span>
                            </div>
                            <i class="fa fa-chevron-down"></i>
                        </div>
                    </div>
                    <div id="zone-{{ $zone->id }}" class="collapse show">
                        <div class="card-body">
                            @forelse($zone->regions as $region)
                                <div class="hierarchy-level">
                                    <div class="card overview-card region-card mb-2" data-type="region" data-name="{{ strtolower($region->name) }}">
                                        <div class="card-header bg-light collapse-toggle" data-toggle="collapse" data-target="#region-{{ $region->id }}">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <i class="fa fa-map text-primary"></i>
                                                    <strong>{{ $region->name }}</strong>
                                                    <span class="badge badge-primary ml-2">{{ $region->areas->count() }} Areas</span>
                                                </div>
                                                <i class="fa fa-chevron-down"></i>
                                            </div>
                                        </div>
                                        <div id="region-{{ $region->id }}" class="collapse show">
                                            <div class="card-body">
                                                @forelse($region->areas as $area)
                                                    <div class="hierarchy-level">
                                                        <div class="card overview-card area-card mb-2" data-type="area" data-name="{{ strtolower($area->name) }}">
                                                            <div class="card-header bg-light collapse-toggle" data-toggle="collapse" data-target="#area-{{ $area->id }}">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <div>
                                                                        <i class="fa fa-map-marker-alt text-success"></i>
                                                                        <strong>{{ $area->name }}</strong>
                                                                        <span class="badge badge-success ml-2">{{ $area->headquarters->count() }} Headquarters</span>
                                                                    </div>
                                                                    <i class="fa fa-chevron-down"></i>
                                                                </div>
                                                            </div>
                                                            <div id="area-{{ $area->id }}" class="collapse show">
                                                                <div class="card-body">
                                                                    @forelse($area->headquarters as $hq)
                                                                        <div class="hierarchy-level">
                                                                            <div class="card overview-card hq-card mb-2" data-type="headquarter" data-name="{{ strtolower($hq->name) }}">
                                                                                <div class="card-header bg-light">
                                                                                    <div class="d-flex justify-content-between align-items-center">
                                                                                        <div>
                                                                                            <i class="fa fa-building text-success"></i>
                                                                                            <strong>{{ $hq->name }}</strong>
                                                                                            @php
                                                                                                $exCount = $hq->exstations->count();
                                                                                                $outCount = $hq->outstations->count();
                                                                                                $totalStations = $exCount + $outCount;
                                                                                            @endphp
                                                                                            <span class="badge badge-info ml-2">{{ $totalStations }} Stations</span>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="card-body">
                                                                                    @if($exCount > 0)
                                                                                        <div class="mb-2">
                                                                                            <strong class="text-primary"><i class="fa fa-map-pin"></i> Ex-Stations:</strong>
                                                                                            <div class="d-flex flex-wrap mt-1">
                                                                                                @foreach($hq->exstations as $ex)
                                                                                                    <span class="badge badge-primary station-badge" data-type="exstation" data-name="{{ strtolower($ex->name) }}">{{ $ex->name }}</span>
                                                                                                @endforeach
                                                                                            </div>
                                                                                        </div>
                                                                                    @endif
                                                                                    @if($outCount > 0)
                                                                                        <div>
                                                                                            <strong class="text-warning"><i class="fa fa-location-arrow"></i> Out-Stations:</strong>
                                                                                            <div class="d-flex flex-wrap mt-1">
                                                                                                @foreach($hq->outstations as $out)
                                                                                                    <span class="badge badge-warning station-badge" data-type="outstation" data-name="{{ strtolower($out->name) }}">{{ $out->name }}</span>
                                                                                                @endforeach
                                                                                            </div>
                                                                                        </div>
                                                                                    @endif
                                                                                    @if($totalStations == 0)
                                                                                        <p class="text-muted mb-0"><i class="fa fa-info-circle"></i> No stations assigned</p>
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @empty
                                                                        <p class="text-muted"><i class="fa fa-info-circle"></i> No headquarters in this area</p>
                                                                    @endforelse
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p class="text-muted"><i class="fa fa-info-circle"></i> No areas in this region</p>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-muted"><i class="fa fa-info-circle"></i> No regions in this zone</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="fa fa-sitemap fa-4x mb-3"></i>
                    <p>No zones found. Start by creating zones, regions, and areas.</p>
                </div>
            @endforelse

            {{-- Unassigned Items Section --}}
            @if($stats['unassigned_regions'] > 0 || $stats['unassigned_areas'] > 0 || $stats['unassigned_headquarters'] > 0 || $stats['unassigned_exstations'] > 0 || $stats['unassigned_outstations'] > 0)
                <div class="card border-danger mt-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fa fa-exclamation-triangle"></i> Unassigned Items</h5>
                    </div>
                    <div class="card-body">
                        @if($stats['unassigned_regions'] > 0)
                            <div class="mb-3">
                                <h6><i class="fa fa-map text-primary"></i> Unassigned Regions ({{ $stats['unassigned_regions'] }})</h6>
                                @foreach($regions->whereNull('zone_id') as $region)
                                    <span class="badge badge-primary mr-2 mb-2">{{ $region->name }}</span>
                                @endforeach
                            </div>
                        @endif

                        @if($stats['unassigned_areas'] > 0)
                            <div class="mb-3">
                                <h6><i class="fa fa-map-marker-alt text-success"></i> Unassigned Areas ({{ $stats['unassigned_areas'] }})</h6>
                                @foreach($areas->whereNull('region_id') as $area)
                                    <span class="badge badge-success mr-2 mb-2">{{ $area->name }}</span>
                                @endforeach
                            </div>
                        @endif

                        @if($stats['unassigned_headquarters'] > 0)
                            <div class="mb-3">
                                <h6><i class="fa fa-building text-success"></i> Unassigned Headquarters ({{ $stats['unassigned_headquarters'] }})</h6>
                                @foreach($headquarters->whereNull('area_id') as $hq)
                                    <span class="badge badge-info mr-2 mb-2">{{ $hq->name }}</span>
                                @endforeach
                            </div>
                        @endif

                        @if($stats['unassigned_exstations'] > 0)
                            <div class="mb-3">
                                <h6><i class="fa fa-map-pin text-info"></i> Unassigned Ex-Stations ({{ $stats['unassigned_exstations'] }})</h6>
                                @foreach($exstations->filter(function($ex) { return $ex->headquarters->isEmpty(); }) as $ex)
                                    <span class="badge badge-primary mr-2 mb-2">{{ $ex->name }}</span>
                                @endforeach
                            </div>
                        @endif

                        @if($stats['unassigned_outstations'] > 0)
                            <div>
                                <h6><i class="fa fa-location-arrow text-warning"></i> Unassigned Out-Stations ({{ $stats['unassigned_outstations'] }})</h6>
                                @foreach($outstations->filter(function($out) { return $out->headquarters->isEmpty(); }) as $out)
                                    <span class="badge badge-warning mr-2 mb-2">{{ $out->name }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Search functionality
    $('#search-overview').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterItems(searchTerm, $('#filter-type').val());
    });

    // Filter by type
    $('#filter-type').on('change', function() {
        const filterType = $(this).val();
        const searchTerm = $('#search-overview').val().toLowerCase();
        filterItems(searchTerm, filterType);
    });

    function filterItems(searchTerm, filterType) {
        $('.overview-card, .station-badge').each(function() {
            const $item = $(this);
            const itemType = $item.data('type');
            const itemName = $item.data('name') || '';
            
            const matchesSearch = !searchTerm || itemName.includes(searchTerm);
            const matchesFilter = !filterType || itemType === filterType;
            
            if (matchesSearch && matchesFilter) {
                $item.show();
                // Show parent containers
                $item.parents('.overview-card').show();
                $item.parents('.collapse').addClass('show');
            } else {
                if ($item.hasClass('overview-card')) {
                    // Hide entire card if it doesn't match
                    $item.hide();
                } else {
                    // Hide badge if it doesn't match
                    $item.hide();
                }
            }
        });
    }

    // Expand/Collapse functionality
    $('.collapse-toggle').on('click', function() {
        const icon = $(this).find('.fa-chevron-down, .fa-chevron-up');
        icon.toggleClass('fa-chevron-down fa-chevron-up');
    });

    function expandAll() {
        $('.collapse').addClass('show');
        $('.fa-chevron-up').removeClass('fa-chevron-up').addClass('fa-chevron-down');
    }

    function collapseAll() {
        $('.collapse').removeClass('show');
        $('.fa-chevron-down').removeClass('fa-chevron-down').addClass('fa-chevron-up');
    }

    // Initialize - show all by default
    $(document).ready(function() {
        $('.collapse').addClass('show');
    });
</script>
@endpush

