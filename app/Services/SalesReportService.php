<?php

namespace App\Services;

use App\Helper\AccessibleHeadquartersHelper;
use App\Helpers\PharmaDesignationHelper;
use App\Models\Invoice;
use Illuminate\Support\Collection;

class SalesReportService
{
    public static function getReportRows(array $filters): Collection
    {
        $company = company();
        $dateFormat = $company->date_format;

        $startDate = isset($filters['startDate']) ? \Carbon\Carbon::createFromFormat($dateFormat, $filters['startDate'])->startOfDay() : now($company->timezone)->startOfMonth();
        $endDate = isset($filters['endDate']) ? \Carbon\Carbon::createFromFormat($dateFormat, $filters['endDate'])->endOfDay() : now($company->timezone);

        $query = Invoice::with(['items', 'client.clientDetails.headquarters', 'client.clientDetails.areas', 'cfaDistributorStocks', 'cfaStockistStocks.cfaStockist', 'payment.bankAccount'])
            ->where('invoices.company_id', $company->id)
            ->where('invoices.credit_note', 0)
            ->whereNotIn('invoices.status', ['canceled', 'draft'])
            ->whereDate('invoices.issue_date', '>=', $startDate->toDateString())
            ->whereDate('invoices.issue_date', '<=', $endDate->toDateString());

        $accessibleHqIds = $filters['accessibleHqIds'] ?? AccessibleHeadquartersHelper::getAccessibleHeadquarterIds();
        if ($accessibleHqIds !== null && empty($accessibleHqIds)) {
            return collect();
        }

        $invoiceType = $filters['invoiceType'] ?? '';
        if ($invoiceType === 'company_cfa') {
            $query->whereHas('cfaDistributorStocks');
        } elseif ($invoiceType === 'cfa_stockist') {
            $query->whereHas('cfaStockistStocks');
        } else {
            $query->where(function ($q) {
                $q->whereHas('cfaDistributorStocks')->orWhereHas('cfaStockistStocks');
            });
        }

        if (!empty($filters['clientID']) && $filters['clientID'] !== 'all') {
            $query->where('invoices.client_id', $filters['clientID']);
        }

        if ($accessibleHqIds !== null && !empty($accessibleHqIds)) {
            $query->whereHas('client.clientDetails.headquarters', function ($q) use ($accessibleHqIds) {
                $q->whereIn('pharma_headquarters.id', $accessibleHqIds);
            });
        }

        if (!empty($filters['headquarter'])) {
            $query->whereHas('client.clientDetails.headquarters', function ($q) use ($filters) {
                $q->where('pharma_headquarters.id', $filters['headquarter']);
            });
        }

        if (!empty($filters['area'])) {
            $query->whereHas('client.clientDetails.areas', function ($q) use ($filters) {
                $q->where('pharma_areas.id', $filters['area']);
            });
        }

        if (!empty($filters['region'])) {
            $query->whereHas('client.clientDetails', function ($q) use ($filters) {
                $q->where('region_id', $filters['region']);
            });
        }

        if (!empty($filters['stockist'])) {
            $query->whereHas('cfaStockistStocks', function ($q) use ($filters) {
                $q->where('cfa_stockist_id', $filters['stockist']);
            });
        }

        if (!empty($filters['product'])) {
            $query->whereHas('items', function ($q) use ($filters) {
                $q->where('product_id', $filters['product']);
            });
        }

        if (in_array('client', user_roles())) {
            $query->where('invoices.client_id', user()->id);
        } elseif (!PharmaDesignationHelper::hasFullCFAAccess()) {
            $viewCfaDist = user()->permission('view_cfa_distributor_invoices');
            $viewCfaStock = user()->permission('view_cfa_stockist_invoices');
            if ($viewCfaDist === 'owned' || $viewCfaStock === 'owned') {
                $query->where('invoices.client_id', user()->id);
            } elseif ($viewCfaDist === 'added' || $viewCfaStock === 'added') {
                $query->where('invoices.added_by', user()->id);
            }
        }

        return $query->orderBy('invoices.issue_date', 'desc')->get();
    }
}
