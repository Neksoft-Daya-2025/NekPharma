<?php

namespace App\Services;

use App\Helper\AccessibleHeadquartersHelper;
use App\Helper\RoleHierarchy;
use App\Models\DcrReport;
use App\Models\EmployeeDetails;
use App\Models\PharmaHeadquarter;
use App\Models\Tour;
use Carbon\Carbon;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Collection;

class TpDeviationReportService
{
    /**
     * Filters for buildRows from HTTP request (DataTable + export).
     *
     * @return array<string, mixed>
     */
    public static function filtersFromRequest(HttpRequest $request): array
    {
        $company = company();
        $dateFormat = $company->date_format;
        $startDate = $request->input('startDate', now($company->timezone)->startOfMonth()->format($dateFormat));
        $endDate = $request->input('endDate', now($company->timezone)->format($dateFormat));

        $accessibleHqIds = AccessibleHeadquartersHelper::getAccessibleHeadquarterIds();

        return [
            'companyId' => $company->id,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'scopedUserIds' => self::scopedUserIdsForCurrentUser(),
            'employeeId' => $request->input('employee_id') && $request->input('employee_id') !== 'all'
                ? (int) $request->input('employee_id')
                : null,
            'headquarter' => $request->input('headquarter') && $request->input('headquarter') !== ''
                ? (int) $request->input('headquarter')
                : null,
            'accessibleHqIds' => $accessibleHqIds,
            'type_missing' => $request->has('type_missing') ? $request->boolean('type_missing') : true,
            'type_mismatch' => $request->has('type_mismatch') ? $request->boolean('type_mismatch') : true,
            'type_unplanned' => $request->has('type_unplanned') ? $request->boolean('type_unplanned') : true,
            'show_unplanned' => $request->boolean('show_unplanned'),
        ];
    }

    /**
     * @return int[]
     */
    public static function scopedUserIdsForCurrentUser(): array
    {
        $viewPermission = user()->permission('view_tp_deviation_report');

        if ($viewPermission === 'all') {
            $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);

            return !empty($viewableIds) ? $viewableIds : [];
        }

        $reportingEmployeeIds = EmployeeDetails::where('reporting_to', user()->id)
            ->where('company_id', company()->id)
            ->pluck('user_id')
            ->toArray();
        $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
        $reportingEmployeeIds = array_values(array_intersect($reportingEmployeeIds, $viewableIds));

        $scopedIds = [user()->id];
        if (!empty($reportingEmployeeIds)) {
            $scopedIds = array_merge($scopedIds, $reportingEmployeeIds);
        }

        return array_values(array_unique($scopedIds));
    }

    /**
     * Build deviation rows: missing DCR, field mismatch, optional unplanned DCR.
     *
     * @param array $filters companyId, startDate, endDate (strings in company format), scopedUserIds, employeeId,
     *                       headquarter (int|null), accessibleHqIds (null|array), type_missing, type_mismatch,
     *                       type_unplanned, show_unplanned (bool)
     */
    public static function buildRows(array $filters): Collection
    {
        $company = company();
        $companyId = (int) ($filters['companyId'] ?? $company->id);
        $dateFormat = $company->date_format;
        $timezone = $company->timezone;

        $startDate = isset($filters['startDate'])
            ? Carbon::createFromFormat($dateFormat, $filters['startDate'])->startOfDay()
            : now($timezone)->startOfMonth();
        $endDate = isset($filters['endDate'])
            ? Carbon::createFromFormat($dateFormat, $filters['endDate'])->endOfDay()
            : now($timezone)->endOfDay();

        $scopedUserIds = $filters['scopedUserIds'] ?? [];
        if (!is_array($scopedUserIds) || empty($scopedUserIds)) {
            return collect();
        }

        $accessibleHqIds = array_key_exists('accessibleHqIds', $filters)
            ? $filters['accessibleHqIds']
            : AccessibleHeadquartersHelper::getAccessibleHeadquarterIds();
        if ($accessibleHqIds !== null && empty($accessibleHqIds)) {
            return collect();
        }

        $employeeId = isset($filters['employeeId']) && $filters['employeeId'] !== '' && $filters['employeeId'] !== 'all'
            ? (int) $filters['employeeId']
            : null;
        if ($employeeId) {
            if (!in_array($employeeId, $scopedUserIds, true)) {
                return collect();
            }
            $scopedUserIds = [$employeeId];
        }

        $hqFilter = isset($filters['headquarter']) && $filters['headquarter'] !== '' && $filters['headquarter'] !== 'all'
            ? (int) $filters['headquarter']
            : null;

        $effectiveHqIds = self::resolveEffectiveHeadquarterIds($accessibleHqIds, $hqFilter);

        if ($effectiveHqIds !== null && empty($effectiveHqIds)) {
            return collect();
        }

        $typeMissing = filter_var($filters['type_missing'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $typeMismatch = filter_var($filters['type_mismatch'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $typeUnplanned = filter_var($filters['type_unplanned'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $showUnplanned = filter_var($filters['show_unplanned'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $hqNameMap = PharmaHeadquarter::where('company_id', $companyId)
            ->pluck('name', 'id')
            ->toArray();

        $toursQuery = Tour::query()
            ->where('company_id', $companyId)
            ->where('approved', true)
            ->where('status', 'approved')
            ->whereDate('date', '>=', $startDate->toDateString())
            ->whereDate('date', '<=', $endDate->toDateString())
            ->whereIn('user_id', $scopedUserIds);

        if ($effectiveHqIds !== null) {
            $toursQuery->whereIn('headquarter_id', $effectiveHqIds);
        }

        $tours = $toursQuery->with(['user.employeeDetails'])->orderBy('date')->get();

        $dcrQuery = DcrReport::query()
            ->where('company_id', $companyId)
            ->where('status', '!=', 'draft')
            ->whereDate('report_date', '>=', $startDate->toDateString())
            ->whereDate('report_date', '<=', $endDate->toDateString())
            ->whereIn('user_id', $scopedUserIds);

        if ($effectiveHqIds !== null) {
            $dcrQuery->whereHas('user.employeeDetail', function ($q) use ($effectiveHqIds) {
                $q->whereIn('headquarter_id', $effectiveHqIds);
            });
        }

        $dcrs = $dcrQuery->with(['user.employeeDetails'])->orderByDesc('id')->get();

        $dcrByUserDate = [];
        foreach ($dcrs as $dcr) {
            $key = self::userDateKey($dcr->user_id, $dcr->report_date);
            if (!isset($dcrByUserDate[$key]) || $dcr->id > $dcrByUserDate[$key]->id) {
                $dcrByUserDate[$key] = $dcr;
            }
        }

        $approvedTourKeys = [];
        foreach ($tours as $tour) {
            $approvedTourKeys[self::userDateKey($tour->user_id, $tour->date)] = true;
        }

        $rows = collect();

        foreach ($tours as $tour) {
            $key = self::userDateKey($tour->user_id, $tour->date);
            $dcr = $dcrByUserDate[$key] ?? null;

            $emp = $tour->user->employeeDetails ?? null;
            $employeeCode = $emp->employee_id ?? '-';

            $tourHqName = $hqNameMap[$tour->headquarter_id] ?? '';

            if (!$dcr) {
                if ($typeMissing) {
                    $rows->push(self::row(
                        $tour->date,
                        $tour->user_id,
                        $tour->user->name ?? '-',
                        $employeeCode,
                        'missing_dcr',
                        $tour->work_status,
                        null,
                        $tour->station,
                        null,
                        $tourHqName,
                        null,
                        $tour->id,
                        null
                    ));
                }

                continue;
            }

            $dcrHq = (string) ($dcr->headquarter ?? '');
            $mismatch = !self::fieldMatchWorkStatus($tour->work_status, $dcr->work_status)
                || !self::fieldMatchStation($tour->station, $dcr->station)
                || !self::fieldMatchHeadquarter($tourHqName, $dcrHq);

            if ($mismatch && $typeMismatch) {
                $rows->push(self::row(
                    $tour->date,
                    $tour->user_id,
                    $tour->user->name ?? '-',
                    $employeeCode,
                    'field_mismatch',
                    $tour->work_status,
                    $dcr->work_status,
                    $tour->station,
                    $dcr->station,
                    $tourHqName,
                    $dcrHq,
                    $tour->id,
                    $dcr->id
                ));
            }
        }

        if ($showUnplanned && $typeUnplanned) {
            foreach ($dcrByUserDate as $key => $dcr) {
                if (isset($approvedTourKeys[$key])) {
                    continue;
                }

                $user = $dcr->user;
                $emp = $user->employeeDetails ?? null;
                $employeeCode = $emp->employee_id ?? '-';

                $rows->push(self::row(
                    $dcr->report_date,
                    $dcr->user_id,
                    $user->name ?? '-',
                    $employeeCode,
                    'unplanned_dcr',
                    null,
                    $dcr->work_status,
                    null,
                    $dcr->station,
                    '-',
                    (string) ($dcr->headquarter ?? ''),
                    null,
                    $dcr->id
                ));
            }
        }

        return $rows->sortBy(function ($r) {
            return ($r->report_date_raw ?? '') . '|' . ($r->employee_name ?? '') . '|' . ($r->deviation_type ?? '');
        })->values();
    }

    /**
     * @param int[]|null $accessibleHqIds null = no restriction
     * @param int|null $hqFilter selected HQ from UI
     * @return int[]|null null = all HQs (no filter)
     */
    private static function resolveEffectiveHeadquarterIds(?array $accessibleHqIds, ?int $hqFilter): ?array
    {
        if ($hqFilter) {
            if ($accessibleHqIds !== null && !in_array($hqFilter, $accessibleHqIds, true)) {
                return [];
            }

            return [$hqFilter];
        }

        return $accessibleHqIds;
    }

    private static function userDateKey(int $userId, $date): string
    {
        if ($date instanceof Carbon) {
            $d = $date->format('Y-m-d');
        } else {
            $d = Carbon::parse($date)->format('Y-m-d');
        }

        return $userId . '|' . $d;
    }

    private static function row(
        $date,
        int $userId,
        string $employeeName,
        string $employeeCode,
        string $deviationType,
        $tourWs,
        $dcrWs,
        $tourStation,
        $dcrStation,
        $tourHq,
        $dcrHq,
        ?int $tourId,
        ?int $dcrId
    ): \stdClass {
        $r = new \stdClass();
        $r->report_date = $date instanceof Carbon ? $date->format('d-m-Y') : Carbon::parse($date)->format('d-m-Y');
        $r->report_date_raw = $date instanceof Carbon ? $date->format('Y-m-d') : Carbon::parse($date)->format('Y-m-d');
        $r->user_id = $userId;
        $r->employee_name = $employeeName;
        $r->employee_code = $employeeCode;
        $r->deviation_type = $deviationType;
        $r->tour_work_status = $tourWs ?? '-';
        $r->dcr_work_status = $dcrWs ?? '-';
        $r->tour_station = self::displayStation($tourStation);
        $r->dcr_station = self::displayStation($dcrStation);
        $r->tour_headquarter = $tourHq !== '' ? $tourHq : '-';
        $r->dcr_headquarter = $dcrHq !== '' && $dcrHq !== null ? $dcrHq : '-';
        $r->tour_id = $tourId;
        $r->dcr_id = $dcrId;

        return $r;
    }

    private static function displayStation($value): string
    {
        if ($value === null) {
            return '-';
        }
        $s = trim((string) $value);

        return $s === '' ? '-' : $s;
    }

    private static function normLine(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\r\n|\r|\n/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    private static function fieldMatchWorkStatus($a, $b): bool
    {
        return strcasecmp(trim((string) $a), trim((string) $b)) === 0;
    }

    private static function fieldMatchStation($a, $b): bool
    {
        return strcasecmp(self::normLine((string) $a), self::normLine((string) $b)) === 0;
    }

    private static function fieldMatchHeadquarter(string $tourHqName, string $dcrHq): bool
    {
        return strcasecmp(trim($tourHqName), trim($dcrHq)) === 0;
    }
}
