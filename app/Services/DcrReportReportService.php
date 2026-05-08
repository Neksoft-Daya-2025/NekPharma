<?php

namespace App\Services;

use App\Helper\AccessibleHeadquartersHelper;
use App\Helper\RoleHierarchy;
use App\Models\DcrChemistVisit;
use App\Models\DcrDoctorVisit;
use App\Models\DcrReport;
use App\Models\DcrStockistVisit;
use App\Models\PharmaExstation;
use App\Models\PharmaHeadquarter;
use App\Models\PharmaOutstation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DcrReportReportService
{
    /**
     * @param array $filters startDate, endDate, headquarter (id), stationType, partyType, employee, accessibleHqIds (optional)
     * @param array|null $accessibleHqIds null = all HQs, [] = none, [ids] = filter to those HQs. If not passed, uses filters['accessibleHqIds'] or AccessibleHeadquartersHelper.
     */
    public static function getVisitRows(array $filters, ?array $accessibleHqIds = null): Collection
    {
        $company = company();
        $dateFormat = $company->date_format;
        $timezone = $company->timezone;

        $startDate = isset($filters['startDate']) ? Carbon::createFromFormat($dateFormat, $filters['startDate'])->startOfDay() : now($timezone)->startOfMonth();
        $endDate = isset($filters['endDate']) ? Carbon::createFromFormat($dateFormat, $filters['endDate'])->endOfDay() : now($timezone);
        $headquarterId = $filters['headquarter'] ?? null;
        $stationType = $filters['stationType'] ?? null;
        $partyType = $filters['partyType'] ?? null;
        $employeeId = $filters['employee'] ?? 'all';

        if ($accessibleHqIds === null && array_key_exists('accessibleHqIds', $filters)) {
            $accessibleHqIds = $filters['accessibleHqIds'];
        }
        if ($accessibleHqIds === null) {
            $accessibleHqIds = AccessibleHeadquartersHelper::getAccessibleHeadquarterIds();
        }

        $viewableIds = RoleHierarchy::userIdsViewableBy(user(), $company->id);
        if ($accessibleHqIds !== null && empty($accessibleHqIds)) {
            return collect();
        }

        $hqNames = PharmaHeadquarter::where('company_id', $company->id)->pluck('name', 'id')->toArray();
        $exstationNames = PharmaExstation::where('company_id', $company->id)->pluck('name')->toArray();
        $outstationNames = PharmaOutstation::where('company_id', $company->id)->pluck('name')->toArray();

        $baseDcrQuery = DcrReport::with(['user.employeeDetail.designation', 'user.roles'])
            ->where('company_id', $company->id)
            ->whereNull('deleted_at')
            ->whereIn('user_id', $viewableIds)
            ->whereBetween('report_date', [$startDate->toDateString(), $endDate->toDateString()]);

        if ($employeeId !== 'all') {
            $baseDcrQuery->where('user_id', $employeeId);
        }

        if ($accessibleHqIds !== null) {
            $allowedHqNames = array_intersect_key($hqNames, array_flip($accessibleHqIds));
            if (!empty($allowedHqNames)) {
                $baseDcrQuery->whereIn('headquarter', array_values($allowedHqNames));
            } else {
                return collect();
            }
        }

        if ($headquarterId) {
            $hqName = $hqNames[$headquarterId] ?? null;
            if ($hqName) {
                $baseDcrQuery->where('headquarter', $hqName);
            }
        }

        $dcrIds = $baseDcrQuery->pluck('id')->toArray();
        if (empty($dcrIds)) {
            return collect();
        }

        $rows = collect();

        $getStationType = function ($station) use ($hqNames, $exstationNames, $outstationNames) {
            if (!$station) {
                return '-';
            }
            if (in_array($station, $hqNames)) {
                return 'HQ';
            }
            if (in_array($station, $exstationNames)) {
                return 'EX Station';
            }
            if (in_array($station, $outstationNames)) {
                return 'Out Station';
            }
            return $station;
        };

        if (!$partyType || $partyType === 'doctor') {
            $doctorVisits = DcrDoctorVisit::with(
                'doctor',
                'dcrReport.user.employeeDetail.designation',
                'dcrReport.user.roles'
            )
                ->whereIn('dcr_report_id', $dcrIds)
                ->get();

            foreach ($doctorVisits as $v) {
                $report = $v->dcrReport;
                if (!$report) {
                    continue;
                }
                if ($stationType && $getStationType($report->station) !== $stationType) {
                    continue;
                }
                $products = array_filter([$v->product1, $v->product2, $v->product3]);
                $partyName = optional($v->doctor)->fullname ?: ($v->doctor_name ?: '-');
                $rows->push((object)[
                    'date' => $report->report_date,
                    'employee_name' => $report->user->name ?? '-',
                    'role' => $report->user->roles->first()->name ?? '-',
                    'headquarter' => $report->headquarter ?? '-',
                    'station_type' => $getStationType($report->station),
                    'party_name' => $partyName,
                    'party_type' => 'Doctor',
                    'product' => implode(', ', $products) ?: '-',
                    'visit_time' => $v->created_at ? $v->created_at->timezone($timezone)->format($company->time_format) : '-',
                    'remarks' => $v->general_remark ?? '-',
                ]);
            }
        }

        if (!$partyType || $partyType === 'chemist') {
            $chemistVisits = DcrChemistVisit::with(
                'chemist',
                'dcrReport.user.employeeDetail.designation',
                'dcrReport.user.roles'
            )
                ->whereIn('dcr_report_id', $dcrIds)
                ->get();

            foreach ($chemistVisits as $v) {
                $report = $v->dcrReport;
                if (!$report) {
                    continue;
                }
                if ($stationType && $getStationType($v->station ?? $report->station) !== $stationType) {
                    continue;
                }
                $products = array_filter([$v->rcpa1, $v->rcpa2, $v->rcpa3, $v->rcpa4]);
                $partyName = $v->chemist?->shopname
                    ?? $v->chemist?->fullname
                    ?? ($v->chemist_name ?: '-');
                $rows->push((object)[
                    'date' => $report->report_date,
                    'employee_name' => $report->user->name ?? '-',
                    'role' => $report->user->roles->first()->name ?? '-',
                    'headquarter' => $report->headquarter ?? '-',
                    'station_type' => $getStationType($v->station ?? $report->station),
                    'party_name' => $partyName,
                    'party_type' => 'Chemist',
                    'product' => implode(', ', $products) ?: '-',
                    'visit_time' => $v->created_at ? $v->created_at->timezone($timezone)->format($company->time_format) : '-',
                    'remarks' => $v->general_remark ?? '-',
                ]);
            }
        }

        if (!$partyType || $partyType === 'stockist') {
            $stockistVisits = DcrStockistVisit::with(
                'stockist',
                'dcrReport.user.employeeDetail.designation',
                'dcrReport.user.roles'
            )
                ->whereIn('dcr_report_id', $dcrIds)
                ->get();

            foreach ($stockistVisits as $v) {
                $report = $v->dcrReport;
                if (!$report) {
                    continue;
                }
                if ($stationType && $getStationType($v->station ?? $report->station) !== $stationType) {
                    continue;
                }
                $partyName = $v->stockist?->shopname
                    ?? $v->stockist?->fullname
                    ?? ($v->stockist_name ?: '-');
                $rows->push((object)[
                    'date' => $report->report_date,
                    'employee_name' => $report->user->name ?? '-',
                    'role' => $report->user->roles->first()->name ?? '-',
                    'headquarter' => $report->headquarter ?? '-',
                    'station_type' => $getStationType($v->station ?? $report->station),
                    'party_name' => $partyName,
                    'party_type' => 'Stockist',
                    'product' => $v->pob ?? '-',
                    'visit_time' => $v->created_at ? $v->created_at->timezone($timezone)->format($company->time_format) : '-',
                    'remarks' => $v->general_remark ?? '-',
                ]);
            }
        }

        return $rows->sortByDesc('date')->values();
    }
}
