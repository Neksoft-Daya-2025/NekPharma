<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Helpers\PharmaDesignationHelper;
use App\Models\PharmaZone;
use App\Models\PharmaRegion;
use App\Models\PharmaArea;
use App\Models\PharmaHeadquarter;
use App\Models\PharmaExstation;
use App\Models\PharmaOutstation;
use App\Models\PharmaHeadquarterAssign;
use App\Support\MasterAreaMapGraphBuilder;
use App\Traits\AccessibleHeadquarters;
use Illuminate\Http\Request;

class PharmaAreaController extends AccountBaseController
{
    use AccessibleHeadquarters;
    /**
     * Permission types that we treat as having at least some visibility scope.
     *
     * At the moment we don't have per-record ownership metadata on the area
     * entities, so "added/owned/both" behave the same as "all". We still keep
     * them in the allow-list so existing role configurations continue to work.
     */
    private const NON_NONE_PERMISSION_TYPES = ['all', 'added', 'owned', 'both'];

    private function permissionDeniedMessage(string $context): string
    {
        return __('messages.permissionDenied') . ' (' . $context . ')';
    }

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Area Management';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('pharma_areas', $this->user->modules));
            return $next($request);
        });
    }

    /**
     * Determine which headquarter IDs the current user can access.
     *
     * Admins may access every headquarter, so we return null to indicate no
     * scoping should be applied. If the user has no headquarter assignment we
     * return an empty array which results in no records being exposed.
     */
    private function safeDecode($value): array
    {
        // CASE: already array
        if (is_array($value)) {
            // CASE: ["[\"1\",\"2\"]"] → unwrap
            if (count($value) === 1 && is_string($value[0])) {
                $value = $value[0];
            } else {
                return array_map('intval', $value);
            }
        }

        // CASE: NULL / empty / "null"
        if (!$value || $value === "null") {
            return [];
        }

        // CASE: decode JSON string
        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_map('intval', $decoded);
        }

        // FAIL-SAFE
        return [];
    }

    // Removed duplicate accessibleHeadquarterIds() - now using AccessibleHeadquarters trait

    /**
     * Abort with 403 when the resolved permission does not grant any visibility.
     */
    private function authorizeNonNone(string $permission, ?string $message = null): string
    {
        if (user()->hasRole('admin')) {
            return 'all';
        }

        $permissionType = user()->permission($permission);

        abort_403(!in_array($permissionType, self::NON_NONE_PERMISSION_TYPES, true), $message);

        return $permissionType;
    }

    /**
     * Abort with 403 unless the resolved permission is "all".
     */
    private function authorizeAll(string $permission, ?string $message = null): string
    {
        if (user()->hasRole('admin')) {
            return 'all';
        }

        $permissionType = user()->permission($permission);

        abort_403($permissionType !== 'all', $message);

        return $permissionType;
    }

    /**
     * Whether the current user has designation ABM or above (requirement 3.2.1: ABM & above can add/edit/delete Ex/Out-Station).
     */
    private function isABMOrAbove(): bool
    {
        if (user()->hasRole('admin') || user()->hasRole('hr') || user()->hasRole('pmt') || user()->hasRole('sales-manager')) {
            return true;
        }
        $detail = user()->employeeDetail;
        if (!$detail) {
            return false;
        }
        $detail->loadMissing('designation');
        return $detail->designation && PharmaDesignationHelper::isABMOrAbove($detail->designation);
    }

    // HEADQUARTERS PAGE
    public function headquarters()
    {
        $this->authorizeNonNone('view_headquarters', $this->permissionDeniedMessage('View Headquarters'));

        $this->pageTitle = 'HeadQuarters';
        $headquarterScope = $this->accessibleHeadquarterIds();

        // Debug: Log the headquarter scope (remove in production)
        // \Log::info('Headquarter Scope:', ['scope' => $headquarterScope, 'user' => user()->id, 'is_admin' => user()->hasRole('admin')]);

        $headquarterQuery = PharmaHeadquarter::with(['area', 'exstations', 'outstations'])
            ->where('company_id', company()->id);

        if ($headquarterScope !== null) {
            // Non-admin user: filter by accessible headquarters
            if (empty($headquarterScope)) {
                // User has no accessible headquarters - show empty list
                $this->headquarters = collect();
                $this->exstations = collect();
                $this->outstations = collect();
                $this->areas = PharmaArea::orderBy('name')->get();

                return view('pharma-areas.headquarters', $this->data);
            }

            $headquarterQuery->whereIn('id', $headquarterScope);
        }
        // If $headquarterScope is null, user is admin - show all headquarters

        $areaFilter = request('area_id');
        if ($areaFilter === 'unassigned') {
            $headquarterQuery->whereNull('area_id');
        } elseif ($areaFilter !== null && $areaFilter !== '' && is_numeric($areaFilter)) {
            $headquarterQuery->where('area_id', (int) $areaFilter);
        }

        $this->headquarters = $headquarterQuery->orderBy('name')->get();
        $this->areas = PharmaArea::orderBy('name')->get();

        if ($headquarterScope === null) {
            // Admin: show all ex-stations and out-stations
            $this->exstations = PharmaExstation::where('company_id', company()->id)->get();
            $this->outstations = PharmaOutstation::where('company_id', company()->id)->get();
        } else {
            // Non-admin: show only stations linked to their accessible headquarters
            $this->exstations = $this->headquarters->flatMap->exstations->unique('id')->values();
            $this->outstations = $this->headquarters->flatMap->outstations->unique('id')->values();
        }

        return view('pharma-areas.headquarters', $this->data);
    }

    // EX-STATIONS PAGE
    public function exstations()
    {
        $this->authorizeNonNone('view_exstations', $this->permissionDeniedMessage('View Ex-Stations'));

        $this->pageTitle = 'Ex-Stations';
        $headquarterScope = $this->accessibleHeadquarterIds();

        if ($headquarterScope === null) {
            $this->exstations = PharmaExstation::all();
        } elseif (empty($headquarterScope)) {
            $this->exstations = collect();
        } else {
            $exstationIds = PharmaHeadquarterAssign::whereIn('headquarter_id', $headquarterScope)
                ->where('station', 'exstation')
                ->pluck('station_id');

            $this->exstations = $exstationIds->isEmpty()
                ? collect()
                : PharmaExstation::whereIn('id', $exstationIds)->get();
        }

        return view('pharma-areas.exstations', $this->data);
    }

    // OUT-STATIONS PAGE
    public function outstations()
    {
        $this->authorizeNonNone('view_outstations', $this->permissionDeniedMessage('View Out-Stations'));

        $this->pageTitle = 'Out-Stations';
        $headquarterScope = $this->accessibleHeadquarterIds();

        if ($headquarterScope === null) {
            $this->outstations = PharmaOutstation::all();
        } elseif (empty($headquarterScope)) {
            $this->outstations = collect();
        } else {
            $outstationIds = PharmaHeadquarterAssign::whereIn('headquarter_id', $headquarterScope)
                ->where('station', 'outstation')
                ->pluck('station_id');

            $this->outstations = $outstationIds->isEmpty()
                ? collect()
                : PharmaOutstation::whereIn('id', $outstationIds)->get();
        }

        return view('pharma-areas.outstations', $this->data);
    }

    // ASSIGN HEADQUARTERS PAGE
    public function assignHeadquarters()
    {
        $this->authorizeAll('manage_area_assignments', $this->permissionDeniedMessage('Manage Area Assignments'));

        $this->pageTitle = 'Assign HeadQuarters';
        $this->headquarters = PharmaHeadquarter::with(['exstations', 'outstations'])->get();
        $this->exstations = PharmaExstation::all();
        $this->outstations = PharmaOutstation::all();
        return view('pharma-areas.assign-headquarters', $this->data);
    }

    public function storeAssignHeadquarters(Request $request)
    {
        $this->authorizeAll('manage_area_assignments', $this->permissionDeniedMessage('Manage Area Assignments'));

        $request->validate([
            'headquarter_id' => 'required|exists:pharma_headquarters,id',
            'station_type' => 'required|in:exstation,outstation',
            'station_ids' => 'required|array',
        ]);

        foreach ($request->station_ids as $stationId) {
            // Check if already assigned
            $exists = PharmaHeadquarterAssign::where('company_id', company()->id)
                ->where('headquarter_id', $request->headquarter_id)
                ->where('station', $request->station_type)
                ->where('station_id', $stationId)
                ->exists();
            
            if (!$exists) {
                PharmaHeadquarterAssign::create([
                    'company_id' => company()->id,
                    'headquarter_id' => $request->headquarter_id,
                    'station' => $request->station_type,
                    'station_id' => $stationId,
                ]);
            }
        }

        return Reply::success(__('Stations assigned successfully'));
    }

    public function deleteAssignHeadquarters($id)
    {
        $this->authorizeAll('manage_area_assignments', $this->permissionDeniedMessage('Manage Area Assignments'));

        $assignment = PharmaHeadquarterAssign::findOrFail($id);
        $assignment->delete();
        return Reply::success(__('Assignment removed successfully'));
    }

    public function deleteAllHQAssignments(Request $request)
    {
        $this->authorizeAll('manage_area_assignments', $this->permissionDeniedMessage('Manage Area Assignments'));

        $request->validate(['headquarter_id' => 'required|exists:pharma_headquarters,id']);
        
        PharmaHeadquarterAssign::where('company_id', company()->id)
            ->where('headquarter_id', $request->headquarter_id)
            ->delete();
        
        return Reply::success(__('All assignments removed successfully'));
    }

    // AREAS PAGE
    public function areas()
    {
        $this->authorizeNonNone('view_areas', $this->permissionDeniedMessage('View Areas'));

        $this->pageTitle = 'Create Area';
        
        // Get accessible area IDs for current user
        $accessibleAreaIds = $this->accessibleAreaIds();
        
        if ($accessibleAreaIds === null) {
            // Admin: show all areas
            $this->areas = PharmaArea::where('company_id', company()->id)->get();
        } elseif (empty($accessibleAreaIds)) {
            // User has no accessible areas
            $this->areas = collect();
        } else {
            // Non-admin: show only assigned areas
            $this->areas = PharmaArea::where('company_id', company()->id)
                ->whereIn('id', $accessibleAreaIds)
                ->get();
        }
        
        return view('pharma-areas.areas', $this->data);
    }

    // ASSIGN AREAS PAGE
    public function assignAreas()
    {
        $this->authorizeAll('manage_area_assignments', $this->permissionDeniedMessage('Manage Area Assignments'));

        $this->pageTitle = 'Assign Area';
        $this->regions = PharmaRegion::with('areas')->get();
        $this->areas = PharmaArea::all();
        return view('pharma-areas.assign-areas', $this->data);
    }

    public function storeAssignAreas(Request $request)
    {
        $this->authorizeAll('manage_area_assignments', $this->permissionDeniedMessage('Manage Area Assignments'));

        $request->validate([
            'region_id' => 'required|exists:pharma_regions,id',
            'area_ids' => 'nullable|array',
            'area_ids.*' => 'exists:pharma_areas,id',
        ]);

        $regionId = (int) $request->region_id;
        $areaIds = $request->input('area_ids', []);

        if ($areaIds === [] || $areaIds === null) {
            PharmaArea::where('region_id', $regionId)->update(['region_id' => null]);

            return Reply::success(__('Areas assigned successfully'));
        }

        foreach ($areaIds as $areaId) {
            $area = PharmaArea::findOrFail($areaId);
            $area->update(['region_id' => $regionId]);
        }

        return Reply::success(__('Areas assigned successfully'));
    }

    // REGIONS PAGE
    public function regions()
    {
        $this->authorizeNonNone('view_regions', $this->permissionDeniedMessage('View Regions'));

        $this->pageTitle = 'Create Region';
        $this->regions = PharmaRegion::with([
            'areas.headquarters.exstations',
            'areas.headquarters.outstations',
        ])->orderBy('name')->get();

        return view('pharma-areas.regions', $this->data);
    }

    // ASSIGN REGIONS PAGE
    public function assignRegions()
    {
        $this->authorizeAll('manage_area_assignments', $this->permissionDeniedMessage('Manage Area Assignments'));

        $this->pageTitle = 'Assign Region';
        $this->zones = PharmaZone::with('regions')->get();
        $this->regions = PharmaRegion::all();
        return view('pharma-areas.assign-regions', $this->data);
    }

    public function storeAssignRegions(Request $request)
    {
        $this->authorizeAll('manage_area_assignments', $this->permissionDeniedMessage('Manage Area Assignments'));

        $request->validate([
            'zone_id' => 'required|exists:pharma_zones,id',
            'region_ids' => 'required|array',
            'region_ids.*' => 'exists:pharma_regions,id',
        ]);

        foreach ($request->region_ids as $regionId) {
            $region = PharmaRegion::findOrFail($regionId);
            $region->update(['zone_id' => $request->zone_id]);
        }

        return Reply::success(__('Regions assigned successfully'));
    }

    // ZONES PAGE
    public function zones()
    {
        $this->authorizeNonNone('view_zones', $this->permissionDeniedMessage('View Zones'));

        $this->pageTitle = 'Create Zone';
        $this->zones = PharmaZone::all();
        return view('pharma-areas.zones', $this->data);
    }

    // ASSIGN ZONES PAGE
    public function assignZones()
    {
        $this->authorizeAll('manage_area_assignments', $this->permissionDeniedMessage('Manage Area Assignments'));

        $this->pageTitle = 'Assign Zone';
        $this->zones = PharmaZone::with('regions')->get();
        $this->regions = PharmaRegion::all();
        return view('pharma-areas.assign-zones', $this->data);
    }

    public function storeAssignZones(Request $request)
    {
        $this->authorizeAll('manage_area_assignments', $this->permissionDeniedMessage('Manage Area Assignments'));

        $request->validate([
            'zone_id' => 'required|exists:pharma_zones,id',
            'region_ids' => 'required|array',
            'region_ids.*' => 'exists:pharma_regions,id',
        ]);

        foreach ($request->region_ids as $regionId) {
            $region = PharmaRegion::findOrFail($regionId);
            $region->update(['zone_id' => $request->zone_id]);
        }

        return Reply::success(__('Regions assigned to zone successfully'));
    }

    // INDEX PAGE - Shows overview of all pharma areas
    public function index()
    {
        return redirect()->route('pharma-areas.headquarters');
    }
    
    // OVERVIEW PAGE - Shows detailed overview with stats
    public function overview()
    {
        $this->authorizeNonNone('view_areas', $this->permissionDeniedMessage('View Areas'));
        
        $this->pageTitle = 'Pharma Areas Overview';
        $zones = PharmaZone::with('regions')->get();
        $regions = PharmaRegion::with('areas')->get();
        $areas = PharmaArea::with('headquarters')->get();
        $headquarters = PharmaHeadquarter::with(['area', 'exstations', 'outstations'])->get();
        
        // Calculate stats for overview
        $this->stats = [
            'zones' => $zones->count(),
            'regions' => $regions->count(),
            'areas' => $areas->count(),
            'headquarters' => $headquarters->count(),
            'exstations' => PharmaExstation::count(),
            'outstations' => PharmaOutstation::count(),
            'unassigned_regions' => $regions->whereNull('zone_id')->count(),
            'unassigned_areas' => $areas->whereNull('region_id')->count(),
            'unassigned_headquarters' => $headquarters->whereNull('area_id')->count(),
            'unassigned_exstations' => 0,
            'unassigned_outstations' => 0,
        ];
        
        $this->zones = $zones;
        $this->regions = $regions;
        $this->areas = $areas;
        $this->headquarters = $headquarters;
        
        return view('pharma-areas.overview', $this->data);
    }

    /**
     * Admin-only: interactive flowchart of Zone → Region → Area → HQ → Ex/Out stations.
     */
    public function masterAreaMap()
    {
        abort_403(! user()->hasRole('admin'), __('messages.permissionDenied'));

        $this->pageTitle = 'Master Area Map';
        $company = company();
        abort_if($company === null, 403, __('messages.permissionDenied'));

        $builder = new MasterAreaMapGraphBuilder(
            (int) $company->id,
            (string) ($company->company_name ?? 'Company')
        );
        $graph = $builder->build();
        $this->graphStats = $graph['stats'];

        $visNodes = array_map(static function (array $n) {
            return [
                'id' => $n['id'],
                'label' => $n['label'],
                'group' => $n['group'],
                'title' => $n['title'],
            ];
        }, $graph['nodes']);

        $this->masterAreaMapClient = [
            'visNodes' => $visNodes,
            'visEdges' => $graph['edges'],
            'nodesMeta' => $graph['nodesMeta'],
            'graphStats' => $graph['stats'],
            'manageLinks' => [
                'root' => ['label' => 'Pharma areas overview', 'url' => route('pharma-areas.overview')],
                'zone' => ['label' => 'Zones', 'url' => route('pharma-areas.zones')],
                'region' => ['label' => 'Regions', 'url' => route('pharma-areas.regions')],
                'area' => ['label' => 'Areas', 'url' => route('pharma-areas.areas')],
                'headquarter' => ['label' => 'Headquarters', 'url' => route('pharma-areas.headquarters')],
                'exstation' => ['label' => 'Ex-stations', 'url' => route('pharma-areas.exstations')],
                'outstation' => ['label' => 'Out-stations', 'url' => route('pharma-areas.outstations')],
                'bucket' => ['label' => 'Assign regions / areas / HQ', 'url' => route('pharma-areas.assign-headquarters')],
            ],
            'groups' => [
                'root' => [
                    'color' => [
                        'background' => '#5c4d7a',
                        'border' => '#3d3355',
                        'highlight' => ['background' => '#7a6a9a', 'border' => '#3d3355'],
                    ],
                    'font' => ['color' => '#fff', 'size' => 18, 'face' => 'system-ui, Segoe UI, sans-serif'],
                ],
                'zone' => ['color' => ['background' => '#6f42c1', 'border' => '#4a2d8a'], 'font' => ['color' => '#fff', 'size' => 16, 'face' => 'system-ui, Segoe UI, sans-serif']],
                'bucket' => ['color' => ['background' => '#e83e8c', 'border' => '#bd2130'], 'font' => ['color' => '#fff', 'size' => 15, 'face' => 'system-ui, Segoe UI, sans-serif']],
                'region' => ['color' => ['background' => '#0d6efd', 'border' => '#084298'], 'font' => ['color' => '#fff', 'size' => 15, 'face' => 'system-ui, Segoe UI, sans-serif']],
                'area' => ['color' => ['background' => '#198754', 'border' => '#146c43'], 'font' => ['color' => '#fff', 'size' => 15, 'face' => 'system-ui, Segoe UI, sans-serif']],
                'headquarter' => ['color' => ['background' => '#8bab4c', 'border' => '#6d8a3c'], 'font' => ['color' => '#fff', 'size' => 14, 'face' => 'system-ui, Segoe UI, sans-serif']],
                'exstation' => ['color' => ['background' => '#17a2b8', 'border' => '#117a8b'], 'font' => ['color' => '#fff', 'size' => 13, 'face' => 'system-ui, Segoe UI, sans-serif']],
                'outstation' => ['color' => ['background' => '#fd7e14', 'border' => '#e8590c'], 'font' => ['color' => '#fff', 'size' => 13, 'face' => 'system-ui, Segoe UI, sans-serif']],
            ],
        ];

        return view('pharma-areas.master-area-map', $this->data);
    }

    // STORE METHODS
    // Zones
    public function storeZone(Request $request)
    {
        $this->authorizeAll('add_zones', $this->permissionDeniedMessage('Add Zones'));

        $request->validate(['name' => 'required|string|max:255']);
        
        $zone = PharmaZone::create([
            'company_id' => company()->id,
            'name' => $request->name,
        ]);

        return Reply::successWithData(__('messages.recordSaved'), ['data' => $zone]);
    }

    public function updateZone(Request $request, $id)
    {
        $this->authorizeAll('edit_zones', $this->permissionDeniedMessage('Edit Zones'));

        $request->validate(['name' => 'required|string|max:255']);
        
        $zone = PharmaZone::findOrFail($id);
        $zone->update(['name' => $request->name]);
        
        return Reply::success(__('messages.updateSuccess'));
    }

    public function destroyZone($id)
    {
        $this->authorizeAll('delete_zones', $this->permissionDeniedMessage('Delete Zones'));

        PharmaZone::findOrFail($id)->delete();
        return Reply::success(__('messages.deleteSuccess'));
    }

    // Regions
    public function updateRegion(Request $request, $id)
    {
        $this->authorizeAll('edit_regions', $this->permissionDeniedMessage('Edit Regions'));

        $request->validate(['name' => 'required|string|max:255']);
        
        $region = PharmaRegion::findOrFail($id);
        $region->update(['name' => $request->name]);
        
        return Reply::success(__('messages.updateSuccess'));
    }

    public function destroyRegion($id)
    {
        $this->authorizeAll('delete_regions', $this->permissionDeniedMessage('Delete Regions'));

        PharmaRegion::findOrFail($id)->delete();
        return Reply::success(__('messages.deleteSuccess'));
    }
    public function storeRegion(Request $request)
    {
        $this->authorizeAll('add_regions', $this->permissionDeniedMessage('Add Regions'));

        $request->validate(['name' => 'required|string|max:255']);
        
        $region = PharmaRegion::create([
            'company_id' => company()->id,
            'name' => $request->name,
        ]);

        return Reply::successWithData(__('messages.recordSaved'), ['data' => $region]);
    }

    // Areas
    public function storeArea(Request $request)
    {
        $this->authorizeAll('add_areas', $this->permissionDeniedMessage('Add Areas'));

        $request->validate(['name' => 'required|string|max:255']);
        
        $area = PharmaArea::create([
            'company_id' => company()->id,
            'name' => $request->name,
        ]);

        return Reply::successWithData(__('messages.recordSaved'), ['data' => $area]);
    }

    public function updateArea(Request $request, $id)
    {
        $this->authorizeAll('edit_areas', $this->permissionDeniedMessage('Edit Areas'));

        $request->validate(['name' => 'required|string|max:255']);
        
        $area = PharmaArea::findOrFail($id);
        $area->update(['name' => $request->name]);
        
        return Reply::success(__('messages.updateSuccess'));
    }

    public function destroyArea($id)
    {
        $this->authorizeAll('delete_areas', $this->permissionDeniedMessage('Delete Areas'));

        PharmaArea::findOrFail($id)->delete();
        return Reply::success(__('messages.deleteSuccess'));
    }

    // Headquarters
    public function destroyHeadquarter($id)
    {
        $this->authorizeAll('delete_headquarters', $this->permissionDeniedMessage('Delete Headquarters'));

        PharmaHeadquarter::findOrFail($id)->delete();
        return Reply::success(__('HeadQuarter deleted successfully'));
    }

    public function updateHeadquarter(Request $request, $id)
    {
        $this->authorizeAll('edit_headquarters', $this->permissionDeniedMessage('Edit Headquarters'));

        $request->validate(['name' => 'required|string|max:255']);
        
        $hq = PharmaHeadquarter::findOrFail($id);
        $hq->update([
            'name' => $request->name,
            'area_id' => $request->area_id
        ]);
        
        return Reply::success(__('HeadQuarter updated successfully'));
    }

    public function storeHeadquarter(Request $request)
    {
        $this->authorizeAll('add_headquarters', $this->permissionDeniedMessage('Add Headquarters'));

        $request->validate(['name' => 'required|string|max:255']);
        
        $hq = PharmaHeadquarter::create([
            'company_id' => company()->id,
            'name' => $request->name,
            'area_id' => $request->area_id,
        ]);

        return Reply::successWithData(__('messages.recordSaved'), ['data' => $hq]);
    }

    // Ex-stations
    public function storeExstation(Request $request)
    {
        if (!$this->isABMOrAbove()) {
            $this->authorizeAll('add_exstations', $this->permissionDeniedMessage('Add Ex-Stations'));
        }

        $request->validate(['name' => 'required|string|max:255']);
        
        $exstation = PharmaExstation::create([
            'company_id' => company()->id,
            'name' => $request->name,
        ]);

        return Reply::successWithData(__('messages.recordSaved'), ['data' => $exstation]);
    }

    public function updateExstation(Request $request, $id)
    {
        if (!$this->isABMOrAbove()) {
            $this->authorizeNonNone('edit_exstations', $this->permissionDeniedMessage('Edit Ex-Stations'));
        }

        $request->validate(['name' => 'required|string|max:255']);
        
        $exstation = PharmaExstation::findOrFail($id);
        $exstation->update(['name' => $request->name]);
        
        return Reply::success(__('messages.updateSuccess'));
    }

    public function destroyExstation($id)
    {
        if (!$this->isABMOrAbove()) {
            $this->authorizeNonNone('delete_exstations', $this->permissionDeniedMessage('Delete Ex-Stations'));
        }

        PharmaExstation::findOrFail($id)->delete();
        return Reply::success(__('messages.deleteSuccess'));
    }

    // Out-stations
    public function storeOutstation(Request $request)
    {
        if (!$this->isABMOrAbove()) {
            $this->authorizeAll('add_outstations', $this->permissionDeniedMessage('Add Out-Stations'));
        }

        $request->validate(['name' => 'required|string|max:255']);
        
        $outstation = PharmaOutstation::create([
            'company_id' => company()->id,
            'name' => $request->name,
        ]);

        return Reply::successWithData(__('messages.recordSaved'), ['data' => $outstation]);
    }

    public function updateOutstation(Request $request, $id)
    {
        if (!$this->isABMOrAbove()) {
            $this->authorizeNonNone('edit_outstations', $this->permissionDeniedMessage('Edit Out-Stations'));
        }

        $request->validate(['name' => 'required|string|max:255']);
        
        $outstation = PharmaOutstation::findOrFail($id);
        $outstation->update(['name' => $request->name]);
        
        return Reply::success(__('messages.updateSuccess'));
    }

    public function destroyOutstation($id)
    {
        if (!$this->isABMOrAbove()) {
            $this->authorizeNonNone('delete_outstations', $this->permissionDeniedMessage('Delete Out-Stations'));
        }

        PharmaOutstation::findOrFail($id)->delete();
        return Reply::success(__('messages.deleteSuccess'));
    }

    // Get assigned stations for a headquarter
    public function getHeadquarterStations($headquarterId)
    {
        $this->authorizeAll('manage_area_assignments', $this->permissionDeniedMessage('Manage Area Assignments'));

        $hq = PharmaHeadquarter::with(['exstations', 'outstations'])->findOrFail($headquarterId);
        
        return response()->json([
            'status' => 'success',
            'exstations' => $hq->exstations->pluck('id')->toArray(),
            'outstations' => $hq->outstations->pluck('id')->toArray(),
            'exstations_names' => $hq->exstations->pluck('name', 'id')->toArray(),
            'outstations_names' => $hq->outstations->pluck('name', 'id')->toArray(),
        ]);
    }

}
