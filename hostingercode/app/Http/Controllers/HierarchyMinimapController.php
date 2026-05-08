<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PharmaZone;
use App\Models\PharmaRegion;
use App\Models\PharmaArea;
use App\Models\PharmaHeadquarter;
use App\Models\EmployeeDetails;
use Illuminate\Http\Request;

class HierarchyMinimapController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->middleware(function ($request, $next) {
            abort_403(!user()->hasRole('admin'));
            return $next($request);
        });
    }

    /**
     * Hierarchy minimap: reporting, area association, org association (admin only).
     */
    public function index()
    {
        $this->pageTitle = __('app.hierarchyMinimap');

        $companyId = company()->id;

        // Load employees with hierarchy relations
        $employees = User::with([
            'employeeDetail.reportingTo:id,name',
            'employeeDetail.designation:id,name',
            'employeeDetail.department:id,team_name',
            'employeeDetail.headquarter.area.region.zone',
        ])
            ->whereHas('employeeDetail', fn($q) => $q->where('company_id', $companyId))
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // Build reporting tree: roots = no reporting_to or reporting_to not in company
        $reportingToIds = EmployeeDetails::where('company_id', $companyId)->whereNotNull('reporting_to')->pluck('reporting_to')->unique();
        $roots = $employees->filter(function ($u) use ($reportingToIds) {
            $detail = $u->employeeDetail;
            if (!$detail) {
                return true;
            }
            $reportingTo = $detail->reporting_to ?? null;
            return $reportingTo === null || !$reportingToIds->contains($reportingTo);
        });

        $this->reportingRoots = $roots->values();
        $this->allEmployees = $employees;
        $this->employeesByReportingTo = $employees->groupBy(function ($u) {
            $detail = $u->employeeDetail;
            return $detail ? ($detail->reporting_to ?? 'none') : 'none';
        });

        // Geography: Zone > Region > Area > Headquarter (with employees per HQ and per area)
        $zones = PharmaZone::with([
            'regions.areas.headquarters',
            'regions.areas' => fn($q) => $q->withCount('headquarters'),
        ])
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $this->zones = $zones;

        // Employees by headquarter_id (single HQ assignment)
        $this->employeesByHeadquarter = $employees->groupBy(function ($u) {
            $d = $u->employeeDetail;
            return $d ? ($d->headquarter_id ?? 'none') : 'none';
        });

        // Employees by area (from areas array or from HQ's area)
        $employeesByArea = [];
        foreach ($employees as $emp) {
            $detail = $emp->employeeDetail;
            if (!$detail) {
                continue;
            }
            $areaIds = $detail->areas ?? [];
            if (is_string($areaIds)) {
                $areaIds = json_decode($areaIds, true) ?: [];
            }
            if (empty($areaIds) && $detail->headquarter && $detail->headquarter->area_id) {
                $areaIds = [$detail->headquarter->area_id];
            }
            foreach ((array) $areaIds as $aid) {
                if ($aid !== null && $aid !== '') {
                    $employeesByArea[$aid] = ($employeesByArea[$aid] ?? collect())->push($emp);
                }
            }
        }
        $this->employeesByArea = $employeesByArea;

        // Org: by designation and department (for summary)
        $this->employeesByDesignation = $employees->groupBy(function ($u) {
            $d = $u->employeeDetail;
            return $d ? ($d->designation_id ?? 'none') : 'none';
        });
        $this->employeesByDepartment = $employees->groupBy(function ($u) {
            $d = $u->employeeDetail;
            return $d ? ($d->department_id ?? 'none') : 'none';
        });

        return view('hierarchy-minimap.index', $this->data);
    }
}
