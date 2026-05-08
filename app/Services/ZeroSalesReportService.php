<?php

namespace App\Services;

use App\Helper\AccessibleHeadquartersHelper;
use App\Models\CFAStockist;
use App\Models\Invoice;
use App\Models\PharmaArea;
use App\Models\PharmaHeadquarter;
use App\Models\PharmaRegion;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ZeroSalesReportService
{
    /**
     * Get entities (HQ, Area, Region, Stockist) with zero sales in the period.
     *
     * @param array $filters startDate, endDate, reportBy (headquarters|areas|regions|stockists), headquarter, area, region, stockist, accessibleHqIds
     * @return Collection of stdClass rows with entity_type, entity_name, parent info
     */
    public static function getReportRows(array $filters): Collection
    {
        $company = company();
        $dateFormat = $company->date_format;
        $timezone = $company->timezone;

        $startDate = isset($filters['startDate']) ? Carbon::createFromFormat($dateFormat, $filters['startDate'])->startOfDay() : now($timezone)->startOfMonth();
        $endDate = isset($filters['endDate']) ? Carbon::createFromFormat($dateFormat, $filters['endDate'])->endOfDay() : now($timezone);

        $accessibleHqIds = $filters['accessibleHqIds'] ?? AccessibleHeadquartersHelper::getAccessibleHeadquarterIds();
        if ($accessibleHqIds !== null && empty($accessibleHqIds)) {
            return collect();
        }

        $reportBy = $filters['reportBy'] ?? 'headquarters';
        $headquarterFilter = $filters['headquarter'] ?? null;
        $areaFilter = $filters['area'] ?? null;
        $regionFilter = $filters['region'] ?? null;
        $stockistFilter = $filters['stockist'] ?? null;

        $validInvoiceIds = Invoice::where('company_id', $company->id)
            ->where('credit_note', 0)
            ->whereNotIn('status', ['canceled', 'draft'])
            ->whereDate('issue_date', '>=', $startDate->toDateString())
            ->whereDate('issue_date', '<=', $endDate->toDateString())
            ->pluck('id')
            ->toArray();

        $rows = collect();

        if ($reportBy === 'headquarters') {
            $rows = self::getZeroSalesHeadquarters($company->id, $validInvoiceIds, $accessibleHqIds, $headquarterFilter, $areaFilter, $regionFilter);
        } elseif ($reportBy === 'areas') {
            $rows = self::getZeroSalesAreas($company->id, $validInvoiceIds, $accessibleHqIds, $headquarterFilter, $areaFilter, $regionFilter);
        } elseif ($reportBy === 'regions') {
            $rows = self::getZeroSalesRegions($company->id, $validInvoiceIds, $accessibleHqIds, $regionFilter);
        } elseif ($reportBy === 'stockists') {
            $rows = self::getZeroSalesStockists($company->id, $validInvoiceIds, $accessibleHqIds, $headquarterFilter, $areaFilter, $regionFilter, $stockistFilter);
        }

        return $rows;
    }

    private static function getZeroSalesHeadquarters(int $companyId, array $validInvoiceIds, ?array $accessibleHqIds, ?int $hqFilter, ?int $areaFilter, ?int $regionFilter): Collection
    {
        $hqQuery = PharmaHeadquarter::with(['area.region'])
            ->where('company_id', $companyId);

        if ($accessibleHqIds !== null) {
            $hqQuery->whereIn('id', $accessibleHqIds);
        }
        if ($hqFilter) {
            $hqQuery->where('id', $hqFilter);
        }
        if ($areaFilter) {
            $hqQuery->where('area_id', $areaFilter);
        }
        if ($regionFilter) {
            $hqQuery->whereHas('area', fn ($q) => $q->where('region_id', $regionFilter));
        }

        $allHqIds = $hqQuery->pluck('id')->toArray();
        if (empty($allHqIds)) {
            return collect();
        }

        $hqIdsWithSales = [];
        if (!empty($validInvoiceIds)) {
            $clientIdsWithInvoices = Invoice::whereIn('id', $validInvoiceIds)->pluck('client_id')->unique()->toArray();
            if (!empty($clientIdsWithInvoices)) {
                $clientDetailIds = DB::table('client_details')->whereIn('user_id', $clientIdsWithInvoices)->pluck('id')->toArray();
                if (!empty($clientDetailIds)) {
                    $hqIdsWithSales = DB::table('client_headquarters')
                        ->whereIn('client_detail_id', $clientDetailIds)
                        ->whereIn('headquarter_id', $allHqIds)
                        ->distinct()
                        ->pluck('headquarter_id')
                        ->toArray();
                }
            }
        }

        $zeroHqIds = array_diff($allHqIds, $hqIdsWithSales);
        if (empty($zeroHqIds)) {
            return collect();
        }

        $headquarters = PharmaHeadquarter::with(['area.region'])
            ->whereIn('id', $zeroHqIds)
            ->orderBy('name')
            ->get();

        return $headquarters->map(fn ($hq) => (object) [
            'entity_type' => 'Headquarter',
            'entity_name' => $hq->name,
            'hq_name' => $hq->name,
            'area_name' => $hq->area ? $hq->area->name : '--',
            'region_name' => $hq->area && $hq->area->region ? $hq->area->region->name : '--',
        ]);
    }

    private static function getZeroSalesAreas(int $companyId, array $validInvoiceIds, ?array $accessibleHqIds, ?int $hqFilter, ?int $areaFilter, ?int $regionFilter): Collection
    {
        $areaQuery = PharmaArea::with('region')
            ->where('company_id', $companyId);

        if ($regionFilter) {
            $areaQuery->where('region_id', $regionFilter);
        }
        if ($areaFilter) {
            $areaQuery->where('id', $areaFilter);
        }
        if ($hqFilter) {
            $areaQuery->whereHas('headquarters', fn ($q) => $q->where('id', $hqFilter));
        }
        if ($accessibleHqIds !== null) {
            $areaIdsFromHq = PharmaHeadquarter::whereIn('id', $accessibleHqIds)->pluck('area_id')->unique()->filter()->toArray();
            if (!empty($areaIdsFromHq)) {
                $areaQuery->whereIn('id', $areaIdsFromHq);
            } else {
                return collect();
            }
        }

        $allAreaIds = $areaQuery->pluck('id')->toArray();
        if (empty($allAreaIds)) {
            return collect();
        }

        $areaIdsWithSales = [];
        if (!empty($validInvoiceIds)) {
            $clientIdsWithInvoices = Invoice::whereIn('id', $validInvoiceIds)->pluck('client_id')->unique()->toArray();
            if (!empty($clientIdsWithInvoices)) {
                $clientDetailIds = DB::table('client_details')->whereIn('user_id', $clientIdsWithInvoices)->pluck('id')->toArray();
                if (!empty($clientDetailIds)) {
                    $areaIdsWithSales = DB::table('client_areas')
                        ->whereIn('client_detail_id', $clientDetailIds)
                        ->whereIn('area_id', $allAreaIds)
                        ->distinct()
                        ->pluck('area_id')
                        ->toArray();
                }
            }
        }

        $zeroAreaIds = array_diff($allAreaIds, $areaIdsWithSales);
        if (empty($zeroAreaIds)) {
            return collect();
        }

        $areas = PharmaArea::with('region')
            ->whereIn('id', $zeroAreaIds)
            ->orderBy('name')
            ->get();

        return $areas->map(fn ($area) => (object) [
            'entity_type' => 'Area',
            'entity_name' => $area->name,
            'hq_name' => '--',
            'area_name' => $area->name,
            'region_name' => $area->region ? $area->region->name : '--',
        ]);
    }

    private static function getZeroSalesRegions(int $companyId, array $validInvoiceIds, ?array $accessibleHqIds, ?int $regionFilter): Collection
    {
        $regionQuery = PharmaRegion::where('company_id', $companyId);

        if ($regionFilter) {
            $regionQuery->where('id', $regionFilter);
        }
        if ($accessibleHqIds !== null) {
            $areaIdsFromHq = PharmaHeadquarter::whereIn('id', $accessibleHqIds)->pluck('area_id')->unique()->filter()->toArray();
            if (!empty($areaIdsFromHq)) {
                $regionIdsFromAreas = PharmaArea::whereIn('id', $areaIdsFromHq)->pluck('region_id')->unique()->filter()->toArray();
                if (!empty($regionIdsFromAreas)) {
                    $regionQuery->whereIn('id', $regionIdsFromAreas);
                } else {
                    return collect();
                }
            } else {
                return collect();
            }
        }

        $allRegionIds = $regionQuery->pluck('id')->toArray();
        if (empty($allRegionIds)) {
            return collect();
        }

        $regionIdsWithSales = [];
        if (!empty($validInvoiceIds)) {
            $clientIdsWithInvoices = Invoice::whereIn('id', $validInvoiceIds)->pluck('client_id')->unique()->toArray();
            if (!empty($clientIdsWithInvoices)) {
                $regionIdsWithSales = DB::table('client_details')
                    ->whereIn('user_id', $clientIdsWithInvoices)
                    ->whereIn('region_id', $allRegionIds)
                    ->whereNotNull('region_id')
                    ->distinct()
                    ->pluck('region_id')
                    ->toArray();
            }
        }

        $zeroRegionIds = array_diff($allRegionIds, $regionIdsWithSales);
        if (empty($zeroRegionIds)) {
            return collect();
        }

        $regions = PharmaRegion::whereIn('id', $zeroRegionIds)->orderBy('name')->get();

        return $regions->map(fn ($region) => (object) [
            'entity_type' => 'Region',
            'entity_name' => $region->name,
            'hq_name' => '--',
            'area_name' => '--',
            'region_name' => $region->name,
        ]);
    }

    private static function getZeroSalesStockists(
        int $companyId,
        array $validInvoiceIds,
        ?array $accessibleHqIds,
        ?int $hqFilter,
        ?int $areaFilter,
        ?int $regionFilter,
        ?int $stockistFilter
    ): Collection {
        $stockistQuery = CFAStockist::with(['headquarter', 'area'])
            ->where('company_id', $companyId);

        if ($stockistFilter) {
            $stockistQuery->where('id', $stockistFilter);
        }
        if ($hqFilter) {
            $stockistQuery->where('headquarter_id', $hqFilter);
        }
        if ($areaFilter) {
            $stockistQuery->where('area_id', $areaFilter);
        }
        if ($regionFilter) {
            $stockistQuery->whereHas('area', fn ($q) => $q->where('region_id', $regionFilter));
        }
        if ($accessibleHqIds !== null) {
            $stockistQuery->whereIn('headquarter_id', $accessibleHqIds);
        }

        $allStockistIds = $stockistQuery->pluck('id')->toArray();
        if (empty($allStockistIds)) {
            return collect();
        }

        $stockistIdsWithSales = [];
        if (!empty($validInvoiceIds)) {
            $stockistIdsWithSales = DB::table('cfa_stockist_stocks')
                ->whereIn('invoice_id', $validInvoiceIds)
                ->whereIn('cfa_stockist_id', $allStockistIds)
                ->distinct()
                ->pluck('cfa_stockist_id')
                ->toArray();
        }

        $zeroStockistIds = array_diff($allStockistIds, $stockistIdsWithSales);
        if (empty($zeroStockistIds)) {
            return collect();
        }

        $stockists = CFAStockist::with(['headquarter', 'area'])
            ->whereIn('id', $zeroStockistIds)
            ->orderBy('shopname')
            ->get();

        return $stockists->map(fn ($s) => (object) [
            'entity_type' => 'Stockist',
            'entity_name' => $s->shopname ?? $s->fullname ?? '--',
            'hq_name' => $s->headquarter ? $s->headquarter->name : '--',
            'area_name' => $s->area ? $s->area->name : '--',
            'region_name' => '--',
        ]);
    }
}
