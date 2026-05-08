<?php

namespace App\Traits;

use App\Helpers\PharmaDesignationHelper;
use Illuminate\Database\Eloquent\Builder;
use App\Models\PharmaArea;
use App\Models\PharmaHeadquarter;
use App\Models\PharmaAssignHeadquarter;
use App\Models\PharmaHeadquarterAssign;
use App\Models\PharmaRegion;

trait AccessibleHeadquarters
{
    private function safeDecode($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }

        return [];
    }

    protected function accessibleHeadquarterIds($user = null): ?array
    {
        if ($user === null) {
            $user = auth()->user();
        }

        $emp = $user->employeeDetail ?? $user->employeeDetails;

        // Full access for admin, HR, PMT, Sales Manager (all HQs + Ex/Out-Stations)
        if ($user->hasRole('admin') || $user->hasRole('hr') || $user->hasRole('pmt') || $user->hasRole('sales-manager')) {
            return null; // null = all HQs
        }

        // MIS Executive: full access to Doctors, Chemists, Stockists, DCR, Tour Plan
        if ($emp && $emp->designation && PharmaDesignationHelper::isMISExecutive($emp->designation)) {
            return null; // null = all HQs
        }

        if (!$emp) {
            if (config('app.debug')) {
                \Log::info('AccessibleHeadquarters: No employee details found', ['user_id' => $user->id]);
            }
            return [];
        }

        // Medical Representative: single assigned headquarter only (no area-wide HQ expansion).
        // ABM/RBM/ZM keep geography allocation via usesGeographyAllocation / areas below.
        if ($emp->designation && PharmaDesignationHelper::isMedicalRepresentative($emp->designation)
            && !PharmaDesignationHelper::usesGeographyAllocation($emp->designation)) {
            $directHeadquarterId = $emp->headquarter_id ?? $emp->pharma_headquarter_id ?? null;
            if ($directHeadquarterId) {
                $hqId = (int) $directHeadquarterId;
                if (config('app.debug')) {
                    \Log::info('AccessibleHeadquarters: MR single headquarter', [
                        'user_id' => $user->id,
                        'headquarter_id' => $hqId,
                    ]);
                }

                return [$hqId];
            }
            // MR without headquarter_id: fall through to area/region logic
        }

        // Decode areas and ensure they're integers (handle both string and array formats)
        $decodedAreas = $this->safeDecode($emp->areas ?? null);
        $areaIds = collect($decodedAreas)->map(function($id) {
            return is_numeric($id) ? (int)$id : $id;
        })->filter()->values();
        
        // Decode regions and ensure they're integers
        $decodedRegions = $this->safeDecode($emp->regions ?? null);
        $regionIds = collect($decodedRegions)->map(function($id) {
            return is_numeric($id) ? (int)$id : $id;
        })->filter()->values();

        // SRS 3.2.6: If user has zones assigned, add regions from those zones
        $decodedZones = $this->safeDecode($emp->zones ?? null);
        $zoneIds = collect($decodedZones)->map(function($id) {
            return is_numeric($id) ? (int)$id : $id;
        })->filter()->values();
        if ($zoneIds->isNotEmpty()) {
            $zoneRegionIds = PharmaRegion::whereIn('zone_id', $zoneIds)->pluck('id');
            $regionIds = $regionIds->merge($zoneRegionIds)->unique()->values();
        }

        if ($regionIds->isNotEmpty()) {
            $regionAreaIds = PharmaArea::whereIn('region_id', $regionIds)->pluck('id');
            $areaIds = $areaIds->merge($regionAreaIds)->unique();
        }

        // If user has areas assigned, return ALL headquarters in those areas
        // Area managers should see all headquarters in their allotted areas
        if ($areaIds->isNotEmpty()) {
            // Get all headquarters in the assigned areas (base set)
            $headquarterIds = PharmaHeadquarter::whereIn('area_id', $areaIds)
                ->where('company_id', company()->id)
                ->pluck('id')
                ->toArray();
            
            // Also check if there are additional headquarters specifically assigned to these areas
            // via pharma_assign_headquarters table (for cross-area assignments or additional access)
            $assignedHeadquarters = PharmaAssignHeadquarter::where('company_id', company()->id)
                ->whereIn('area_id', $areaIds)
                ->get();

            if ($assignedHeadquarters->isNotEmpty()) {
                // Add any specifically assigned headquarters to the list
                foreach ($assignedHeadquarters as $assignment) {
                    if (is_array($assignment->headquarter_ids)) {
                        $headquarterIds = array_merge($headquarterIds, $assignment->headquarter_ids);
                    }
                }
            }
            
            // Return unique list of all headquarters (both in areas and specifically assigned)
            $uniqueHqIds = array_unique($headquarterIds);
            if (config('app.debug')) {
                \Log::info('AccessibleHeadquarters: Area-based access', [
                    'user_id' => $user->id,
                    'area_ids' => $areaIds->toArray(),
                    'headquarter_ids' => $uniqueHqIds,
                    'count' => count($uniqueHqIds),
                ]);
            }
            return array_values($uniqueHqIds);
        }

        // Area Sales Manager: base HQ only in profile (no areas JSON) — all HQs in that pharma area
        if ($emp->designation && PharmaDesignationHelper::isASM($emp->designation) && $areaIds->isEmpty()) {
            $baseHqId = $emp->headquarter_id ?? $emp->pharma_headquarter_id ?? null;
            if ($baseHqId) {
                $hq = PharmaHeadquarter::where('company_id', company()->id)->find($baseHqId);
                if ($hq && $hq->area_id) {
                    $asmHeadquarterIds = PharmaHeadquarter::where('area_id', $hq->area_id)
                        ->where('company_id', company()->id)
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->unique()
                        ->values()
                        ->toArray();
                    if (config('app.debug')) {
                        \Log::info('AccessibleHeadquarters: ASM area expansion from base HQ', [
                            'user_id' => $user->id,
                            'base_headquarter_id' => (int) $baseHqId,
                            'area_id' => $hq->area_id,
                            'headquarter_ids' => $asmHeadquarterIds,
                        ]);
                    }

                    return $asmHeadquarterIds;
                }
            }
        }

        // If no areas assigned, check for direct headquarter_id assignment
        $directHeadquarterId = $emp->headquarter_id ?? $emp->pharma_headquarter_id ?? null;
        if ($directHeadquarterId) {
            if (config('app.debug')) {
                \Log::info('AccessibleHeadquarters: Direct headquarter assignment', [
                    'user_id' => $user->id,
                    'headquarter_id' => $directHeadquarterId,
                ]);
            }
            return [$directHeadquarterId];
        }

        // No areas and no direct headquarter assignment
        if (config('app.debug')) {
            \Log::info('AccessibleHeadquarters: No areas or headquarter assignment', ['user_id' => $user->id]);
        }
        return [];
    }

    /**
     * Get accessible area IDs for the current user
     * Returns null for admin (all areas), array of area IDs for non-admin, or empty array if none
     *
     * Master data: for geography users, employee_details.areas / regions / zones must resolve to valid
     * pharma_areas ids; pharma_headquarters.area_id should be set for HQs in those territories or
     * accessibleHeadquarterIds() may be empty while doctors still have area_id set.
     */
    protected function accessibleAreaIds($user = null): ?array
    {
        if ($user === null) {
            $user = auth()->user();
        }

        $emp = $user->employeeDetail ?? $user->employeeDetails;

        // Full access for admin, HR, PMT, Sales Manager
        if ($user->hasRole('admin') || $user->hasRole('hr') || $user->hasRole('pmt') || $user->hasRole('sales-manager')) {
            return null; // null = all areas
        }

        // MIS Executive: full access to all areas (same as accessibleHeadquarterIds)
        if ($emp && $emp->designation && PharmaDesignationHelper::isMISExecutive($emp->designation)) {
            return null; // null = all areas
        }

        if (!$emp) {
            return [];
        }

        // MR: align area scope with single headquarter (for DCR/Tour etc. that filter by area)
        if ($emp->designation && PharmaDesignationHelper::isMedicalRepresentative($emp->designation)
            && !PharmaDesignationHelper::usesGeographyAllocation($emp->designation)) {
            $directHeadquarterId = $emp->headquarter_id ?? $emp->pharma_headquarter_id ?? null;
            if ($directHeadquarterId) {
                $headquarter = PharmaHeadquarter::find($directHeadquarterId);
                if ($headquarter && $headquarter->area_id) {
                    return [(int) $headquarter->area_id];
                }

                return [];
            }
        }

        $areaIds = collect($this->safeDecode($emp->areas ?? null));
        $regionIds = collect($this->safeDecode($emp->regions ?? null));
        $zoneIds = collect($this->safeDecode($emp->zones ?? null))->map(fn($id) => is_numeric($id) ? (int)$id : $id)->filter()->values();

        // If user has zones assigned, add regions from those zones
        if ($zoneIds->isNotEmpty()) {
            $zoneRegionIds = PharmaRegion::whereIn('zone_id', $zoneIds)->pluck('id');
            $regionIds = $regionIds->merge($zoneRegionIds)->unique()->values();
        }

        // If user has regions assigned, get areas from those regions
        if ($regionIds->isNotEmpty()) {
            $regionAreaIds = PharmaArea::whereIn('region_id', $regionIds)->pluck('id');
            $areaIds = $areaIds->merge($regionAreaIds)->unique();
        }

        // If user has direct headquarter assignment but no areas, get area from headquarter
        if ($areaIds->isEmpty()) {
            $directHeadquarterId = $emp->headquarter_id ?? $emp->pharma_headquarter_id ?? null;
            if ($directHeadquarterId) {
                $headquarter = PharmaHeadquarter::find($directHeadquarterId);
                if ($headquarter && $headquarter->area_id) {
                    return [$headquarter->area_id];
                }
            }
            return [];
        }

        return $areaIds->unique()->values()->toArray();
    }

    /**
     * Scope Doctor/Chemist/Stockist lists: headquarter OR ex/out stations OR pharma area.
     * Call only when accessibleHeadquarterIds() !== null (scoped non-admin). Full-access users skip this.
     *
     * @param  array|null  $hqIds  Resolved accessible headquarter IDs (may be empty)
     * @param  array|null  $areaIds  From accessibleAreaIds(); null treated as []
     * @param  array  $stationIds  [ 'exstation' => int[], 'outstation' => int[] ] from accessibleStations()
     */
    protected function applyCustomerGeoScope(Builder $query, ?array $hqIds, ?array $areaIds, array $stationIds): void
    {
        $ex = array_values(array_filter($stationIds['exstation'] ?? [], static fn ($id) => $id !== null && $id !== ''));
        $out = array_values(array_filter($stationIds['outstation'] ?? [], static fn ($id) => $id !== null && $id !== ''));
        $areaIds = array_values(array_filter($areaIds ?? [], static fn ($id) => $id !== null && $id !== ''));
        $hqIds = array_values(array_filter($hqIds ?? [], static fn ($id) => $id !== null && $id !== ''));

        if (! empty($hqIds)) {
            $query->where(function ($q) use ($hqIds, $areaIds, $ex, $out) {
                $q->whereIn('headquarter_id', $hqIds);
                if (! empty($ex)) {
                    $q->orWhereIn('exstation_id', $ex);
                }
                if (! empty($out)) {
                    $q->orWhereIn('outstation_id', $out);
                }
                if (! empty($areaIds)) {
                    $q->orWhereIn('area_id', $areaIds);
                }
            });

            return;
        }

        if (! empty($areaIds)) {
            $query->where(function ($q) use ($areaIds, $ex, $out) {
                $q->whereIn('area_id', $areaIds);
                if (! empty($ex)) {
                    $q->orWhereIn('exstation_id', $ex);
                }
                if (! empty($out)) {
                    $q->orWhereIn('outstation_id', $out);
                }
            });

            return;
        }

        if (! empty($ex) || ! empty($out)) {
            $query->where(function ($q) use ($ex, $out) {
                if (! empty($ex) && ! empty($out)) {
                    $q->whereIn('exstation_id', $ex)->orWhereIn('outstation_id', $out);
                } elseif (! empty($ex)) {
                    $q->whereIn('exstation_id', $ex);
                } else {
                    $q->whereIn('outstation_id', $out);
                }
            });

            return;
        }

        $query->whereRaw('1 = 0');
    }

    /**
     * AND this onto queries that already pin station (e.g. DCR AJAX). Do not add ex/out ORs.
     */
    protected function applyCustomerHeadquarterOrAreaScope(Builder $query, ?array $hqIds, ?array $areaIds): void
    {
        $areaIds = array_values(array_filter($areaIds ?? [], static fn ($id) => $id !== null && $id !== ''));
        $hqIds = array_values(array_filter($hqIds ?? [], static fn ($id) => $id !== null && $id !== ''));

        if (! empty($hqIds)) {
            $query->where(function ($q) use ($hqIds, $areaIds) {
                $q->whereIn('headquarter_id', $hqIds);
                if (! empty($areaIds)) {
                    $q->orWhereIn('area_id', $areaIds);
                }
            });

            return;
        }

        if (! empty($areaIds)) {
            $query->whereIn('area_id', $areaIds);

            return;
        }

        $query->whereRaw('1 = 0');
    }

    /**
     * Get accessible Ex-Station and Out-Station IDs for the current user.
     * Returns arrays keyed by 'exstation' and 'outstation'.
     * When accessibleHeadquarterIds() returns null (full access), returns all stations for the company.
     */
    protected function accessibleStations($user = null): array
    {
        $headquarterIds = $this->accessibleHeadquarterIds($user);

        if ($headquarterIds === null) {
            $baseQuery = PharmaHeadquarterAssign::query();
            if (function_exists('company') && company()) {
                $baseQuery->where('company_id', company()->id);
            }
            return [
                'exstation' => (clone $baseQuery)->where('station', 'exstation')->pluck('station_id')->map(fn ($id) => (int) $id)->unique()->values()->toArray(),
                'outstation' => (clone $baseQuery)->where('station', 'outstation')->pluck('station_id')->map(fn ($id) => (int) $id)->unique()->values()->toArray(),
            ];
        }

        if (empty($headquarterIds)) {
            return ['exstation' => [], 'outstation' => []];
        }

        $assignments = PharmaHeadquarterAssign::whereIn('headquarter_id', $headquarterIds)->get();

        return [
            'exstation' => $assignments->where('station', 'exstation')->pluck('station_id')->map(fn ($id) => (int) $id)->unique()->values()->toArray(),
            'outstation' => $assignments->where('station', 'outstation')->pluck('station_id')->map(fn ($id) => (int) $id)->unique()->values()->toArray(),
        ];
    }
}

