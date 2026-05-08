<?php

namespace App\Http\Controllers;

use App\Helpers\PharmaDesignationHelper;
use App\Models\Invoice;
use App\Models\InvoiceItems;
use App\Models\User;
use App\Models\CFAStockist;
use App\Models\CFAStockistStock;
use App\Models\ClientDetails;
use App\Models\PharmaArea;
use App\Models\PharmaRegion;
use App\Models\PharmaHeadquarter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceReportController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Check permission for CFA distributor invoice reports.
     */
    protected function checkCFADistributorPermission(): void
    {
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            return;
        }
        // CFA distributor (client): reports are scoped to own client_id below
        if (in_array('client', user_roles(), true)) {
            return;
        }
        $viewPermission = user()->permission('view_cfa_distributor_invoices');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
    }

    /**
     * Check permission for CFA stockist invoice reports.
     */
    protected function checkCFAStockistPermission(): void
    {
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            return;
        }
        if (in_array('client', user_roles(), true)) {
            return;
        }
        $viewPermission = user()->permission('view_cfa_stockist_invoices');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
    }

    protected function parseDate($value, $default): string
    {
        if (!$value) {
            return $default;
        }
        try {
            if ($this->company && $this->company->date_format) {
                $parsed = Carbon::createFromFormat($this->company->date_format, trim($value));
                return $parsed->toDateString();
            }
            return Carbon::parse($value)->toDateString();
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * CFA-wise report: Company→CFA invoices grouped by CFA (client_id).
     */
    public function cfaWise(Request $request)
    {
        $this->checkCFADistributorPermission();
        $this->pageTitle = 'Invoice Reports - CFA-wise';

        $startDate = $this->parseDate($request->get('from_date'), now()->startOfMonth()->toDateString());
        $endDate = $this->parseDate($request->get('to_date'), now()->toDateString());

        $query = Invoice::where('invoices.company_id', company()->id)
            ->where('invoices.credit_note', 0)
            ->whereNotIn('invoices.status', ['canceled', 'draft'])
            ->whereHas('cfaDistributorStocks')
            ->whereDate('invoices.issue_date', '>=', $startDate)
            ->whereDate('invoices.issue_date', '<=', $endDate);

        if (in_array('client', user_roles())) {
            $query->where('invoices.client_id', user()->id);
        } elseif (user()->permission('view_cfa_distributor_invoices') === 'owned') {
            $query->where('invoices.client_id', user()->id);
        } elseif (user()->permission('view_cfa_distributor_invoices') === 'added') {
            $query->where('invoices.added_by', user()->id);
        }

        $rows = $query->selectRaw('invoices.client_id, count(*) as invoice_count, sum(invoices.total) as total_amount')
            ->groupBy('invoices.client_id')
            ->get();

        $clientIds = $rows->pluck('client_id')->unique()->filter()->values();
        $clients = User::without('session')->with('clientDetails')->whereIn('id', $clientIds)->get()->keyBy('id');

        $reportRows = [];
        foreach ($rows as $r) {
            $client = $clients->get($r->client_id);
            $reportRows[] = [
                'cfa_id' => $r->client_id,
                'cfa_name' => $client ? ($client->clientDetails->company_name ?? $client->name) : 'N/A',
                'invoice_count' => $r->invoice_count,
                'total_amount' => round((float) $r->total_amount, 2),
            ];
        }
        usort($reportRows, fn ($a, $b) => strcmp($a['cfa_name'], $b['cfa_name']));

        $this->fromDate = $request->get('from_date') ?: $startDate;
        $this->toDate = $request->get('to_date') ?: $endDate;
        $this->rows = $reportRows;

        return view('invoice-reports.cfa-wise', $this->data);
    }

    /**
     * Stockist-wise report: CFA→Stockist invoices grouped by stockist.
     */
    public function stockistWise(Request $request)
    {
        $this->checkCFAStockistPermission();
        $this->pageTitle = 'Invoice Reports - Stockist-wise';

        $startDate = $this->parseDate($request->get('from_date'), now()->startOfMonth()->toDateString());
        $endDate = $this->parseDate($request->get('to_date'), now()->toDateString());

        $baseQuery = Invoice::where('invoices.company_id', company()->id)
            ->where('invoices.credit_note', 0)
            ->whereNotIn('invoices.status', ['canceled', 'draft'])
            ->join('cfa_stockist_stocks', 'cfa_stockist_stocks.invoice_id', '=', 'invoices.id')
            ->whereDate('invoices.issue_date', '>=', $startDate)
            ->whereDate('invoices.issue_date', '<=', $endDate);

        if (in_array('client', user_roles())) {
            $baseQuery->where('invoices.client_id', user()->id);
        } elseif (!PharmaDesignationHelper::hasFullCFAAccess()) {
            $baseQuery->where('cfa_stockist_stocks.cfa_distributor_id', user()->id);
        }

        $rows = (clone $baseQuery)
            ->selectRaw('cfa_stockist_stocks.cfa_stockist_id, count(distinct invoices.id) as invoice_count, sum(invoices.total) as total_amount')
            ->groupBy('cfa_stockist_stocks.cfa_stockist_id')
            ->get();

        $stockistIds = $rows->pluck('cfa_stockist_id')->unique()->filter()->values();
        $stockists = CFAStockist::whereIn('id', $stockistIds)->get()->keyBy('id');

        $reportRows = [];
        foreach ($rows as $r) {
            $st = $stockists->get($r->cfa_stockist_id);
            $reportRows[] = [
                'stockist_id' => $r->cfa_stockist_id,
                'stockist_name' => $st ? ($st->shopname ?? $st->fullname ?? 'N/A') : 'N/A',
                'invoice_count' => $r->invoice_count,
                'total_amount' => round((float) $r->total_amount, 2),
            ];
        }
        usort($reportRows, fn ($a, $b) => strcmp($a['stockist_name'], $b['stockist_name']));

        $this->fromDate = $request->get('from_date') ?: $startDate;
        $this->toDate = $request->get('to_date') ?: $endDate;
        $this->rows = $reportRows;

        return view('invoice-reports.stockist-wise', $this->data);
    }

    /**
     * HQ / Area / Region-wise report: aggregate by segment.
     */
    public function hqAreaRegionWise(Request $request)
    {
        $this->checkCFADistributorPermission();
        $this->pageTitle = 'Invoice Reports - HQ / Area / Region-wise';

        $startDate = $this->parseDate($request->get('from_date'), now()->startOfMonth()->toDateString());
        $endDate = $this->parseDate($request->get('to_date'), now()->toDateString());
        $segment = $request->get('segment', 'area'); // hq | area | region

        $invoices = Invoice::with(['client.clientDetails.region', 'client.clientDetails.areas', 'client.clientDetails.headquarters'])
            ->where('invoices.company_id', company()->id)
            ->where('invoices.credit_note', 0)
            ->whereNotIn('invoices.status', ['canceled', 'draft'])
            ->whereHas('cfaDistributorStocks')
            ->whereDate('invoices.issue_date', '>=', $startDate)
            ->whereDate('invoices.issue_date', '<=', $endDate)
            ->get(['invoices.id', 'invoices.client_id', 'invoices.total', 'invoices.added_by']);

        if (in_array('client', user_roles())) {
            $invoices = $invoices->where('client_id', user()->id);
        } elseif (user()->permission('view_cfa_distributor_invoices') === 'owned') {
            $invoices = $invoices->where('client_id', user()->id);
        } elseif (user()->permission('view_cfa_distributor_invoices') === 'added') {
            $invoices = $invoices->where('added_by', user()->id);
        }

        $grouped = [];
        foreach ($invoices as $inv) {
            $cd = $inv->client ? $inv->client->clientDetails : null;
            $scopeKey = 'unknown';
            $scopeName = 'Unknown';
            if ($segment === 'region' && $cd) {
                $rid = $cd->region_id;
                if ($rid) {
                    $scopeKey = 'region_' . $rid;
                    if (!isset($grouped[$scopeKey])) {
                        $region = PharmaRegion::find($rid);
                        $grouped[$scopeKey] = ['name' => $region ? $region->name : 'Region #' . $rid, 'count' => 0, 'total' => 0];
                    }
                    $grouped[$scopeKey]['count']++;
                    $grouped[$scopeKey]['total'] += (float) $inv->total;
                } else {
                    $scopeKey = 'region_0';
                    if (!isset($grouped[$scopeKey])) {
                        $grouped[$scopeKey] = ['name' => 'No region', 'count' => 0, 'total' => 0];
                    }
                    $grouped[$scopeKey]['count']++;
                    $grouped[$scopeKey]['total'] += (float) $inv->total;
                }
                continue;
            }
            if ($segment === 'area' && $cd && $cd->areas && $cd->areas->isNotEmpty()) {
                $area = $cd->areas->first();
                $scopeKey = 'area_' . $area->id;
                if (!isset($grouped[$scopeKey])) {
                    $grouped[$scopeKey] = ['name' => $area->name, 'count' => 0, 'total' => 0];
                }
                $grouped[$scopeKey]['count']++;
                $grouped[$scopeKey]['total'] += (float) $inv->total;
                continue;
            }
            if ($segment === 'area') {
                $scopeKey = 'area_0';
                if (!isset($grouped[$scopeKey])) {
                    $grouped[$scopeKey] = ['name' => 'No area', 'count' => 0, 'total' => 0];
                }
                $grouped[$scopeKey]['count']++;
                $grouped[$scopeKey]['total'] += (float) $inv->total;
                continue;
            }
            if ($segment === 'hq' && $cd && $cd->headquarters && $cd->headquarters->isNotEmpty()) {
                $hq = $cd->headquarters->first();
                $scopeKey = 'hq_' . $hq->id;
                if (!isset($grouped[$scopeKey])) {
                    $grouped[$scopeKey] = ['name' => $hq->name, 'count' => 0, 'total' => 0];
                }
                $grouped[$scopeKey]['count']++;
                $grouped[$scopeKey]['total'] += (float) $inv->total;
                continue;
            }
            if ($segment === 'hq') {
                $scopeKey = 'hq_0';
                if (!isset($grouped[$scopeKey])) {
                    $grouped[$scopeKey] = ['name' => 'No HQ', 'count' => 0, 'total' => 0];
                }
                $grouped[$scopeKey]['count']++;
                $grouped[$scopeKey]['total'] += (float) $inv->total;
            }
        }

        $reportRows = array_values(array_map(function ($v) {
            return [
                'scope_name' => $v['name'],
                'invoice_count' => $v['count'],
                'total_amount' => round($v['total'], 2),
            ];
        }, $grouped));
        usort($reportRows, fn ($a, $b) => strcmp($a['scope_name'], $b['scope_name']));

        $this->fromDate = $request->get('from_date') ?: $startDate;
        $this->toDate = $request->get('to_date') ?: $endDate;
        $this->segment = $segment;
        $this->rows = $reportRows;

        return view('invoice-reports.hq-area-region-wise', $this->data);
    }

    /**
     * Product-wise report: from invoice line items, group by product.
     */
    public function productWise(Request $request)
    {
        $this->pageTitle = 'Invoice Reports - Product-wise';
        $viewCfaDist = user()->permission('view_cfa_distributor_invoices');
        $viewCfaStock = user()->permission('view_cfa_stockist_invoices');
        if (!PharmaDesignationHelper::hasFullCFAAccess() && !in_array('client', user_roles(), true)) {
            abort_403(!in_array($viewCfaDist, ['all', 'added', 'owned', 'both']) && !in_array($viewCfaStock, ['all', 'added', 'owned', 'both']));
        }

        $startDate = $this->parseDate($request->get('from_date'), now()->startOfMonth()->toDateString());
        $endDate = $this->parseDate($request->get('to_date'), now()->toDateString());
        $invoiceType = $request->get('invoice_type', 'company_cfa'); // company_cfa | cfa_stockist

        $itemQuery = InvoiceItems::where('invoice_items.type', 'item')
            ->whereNotNull('invoice_items.product_id')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->where('invoices.company_id', company()->id)
            ->where('invoices.credit_note', 0)
            ->whereNotIn('invoices.status', ['canceled', 'draft'])
            ->whereDate('invoices.issue_date', '>=', $startDate)
            ->whereDate('invoices.issue_date', '<=', $endDate);

        if ($invoiceType === 'company_cfa') {
            $itemQuery->whereExists(function ($q) {
                $q->select(DB::raw(1))->from('c_f_a_distributor_stocks')->whereColumn('c_f_a_distributor_stocks.invoice_id', 'invoices.id');
            });
            if (in_array('client', user_roles())) {
                $itemQuery->where('invoices.client_id', user()->id);
            } elseif ($viewCfaDist === 'owned') {
                $itemQuery->where('invoices.client_id', user()->id);
            } elseif ($viewCfaDist === 'added') {
                $itemQuery->where('invoices.added_by', user()->id);
            }
        } else {
            $itemQuery->whereExists(function ($q) {
                $q->select(DB::raw(1))->from('cfa_stockist_stocks')->whereColumn('cfa_stockist_stocks.invoice_id', 'invoices.id');
            });
            if (in_array('client', user_roles())) {
                $itemQuery->where('invoices.client_id', user()->id);
            } elseif (!PharmaDesignationHelper::hasFullCFAAccess()) {
                $itemQuery->whereIn('invoices.id', function ($q) {
                    $q->select('invoice_id')->from('cfa_stockist_stocks')->where('cfa_distributor_id', user()->id);
                });
            }
        }

        $rows = $itemQuery
            ->selectRaw('invoice_items.product_id, sum(invoice_items.quantity) as total_quantity, sum(invoice_items.amount) as total_value, count(distinct invoices.id) as invoice_count')
            ->groupBy('invoice_items.product_id')
            ->get();

        $productIds = $rows->pluck('product_id')->unique()->filter()->values();
        $products = \App\Models\Product::whereIn('id', $productIds)->get()->keyBy('id');

        $reportRows = [];
        foreach ($rows as $r) {
            $product = $products->get($r->product_id);
            $reportRows[] = [
                'product_id' => $r->product_id,
                'product_name' => $product ? $product->name : 'N/A',
                'total_quantity' => round((float) $r->total_quantity, 2),
                'total_value' => round((float) $r->total_value, 2),
                'invoice_count' => $r->invoice_count,
            ];
        }
        usort($reportRows, fn ($a, $b) => strcmp($a['product_name'], $b['product_name']));

        $this->fromDate = $request->get('from_date') ?: $startDate;
        $this->toDate = $request->get('to_date') ?: $endDate;
        $this->invoiceType = $invoiceType;
        $this->rows = $reportRows;

        return view('invoice-reports.product-wise', $this->data);
    }

    /**
     * Purchase & Sales report: Purchase summary (orders) and Sales summary (CFA + Stockist invoices) for custom date range.
     */
    public function purchaseAndSales(Request $request)
    {
        $this->pageTitle = 'Invoice Reports - Purchase & Sales';
        $viewCfaDist = user()->permission('view_cfa_distributor_invoices');
        $viewCfaStock = user()->permission('view_cfa_stockist_invoices');
        if (!PharmaDesignationHelper::hasFullCFAAccess() && !in_array('client', user_roles(), true)) {
            abort_403(!in_array($viewCfaDist, ['all', 'added', 'owned', 'both']) && !in_array($viewCfaStock, ['all', 'added', 'owned', 'both']));
        }

        $startDate = $this->parseDate($request->get('from_date'), now()->startOfMonth()->toDateString());
        $endDate = $this->parseDate($request->get('to_date'), now()->toDateString());

        // Purchase summary (from Purchase module if available)
        $purchaseCount = 0;
        $purchaseTotal = 0;
        if (class_exists(\Modules\Purchase\Entities\PurchaseOrder::class)) {
            $poQuery = \Modules\Purchase\Entities\PurchaseOrder::where('company_id', company()->id)
                ->whereDate('purchase_date', '>=', $startDate)
                ->whereDate('purchase_date', '<=', $endDate);
            $purchaseCount = $poQuery->count();
            $purchaseTotal = (float) (clone $poQuery)->sum('total');
        }

        // Sales: Company→CFA
        $cfaSalesQuery = Invoice::where('invoices.company_id', company()->id)
            ->where('invoices.credit_note', 0)
            ->whereNotIn('invoices.status', ['canceled', 'draft'])
            ->whereHas('cfaDistributorStocks')
            ->whereDate('invoices.issue_date', '>=', $startDate)
            ->whereDate('invoices.issue_date', '<=', $endDate);
        if (in_array('client', user_roles())) {
            $cfaSalesQuery->where('invoices.client_id', user()->id);
        } elseif (user()->permission('view_cfa_distributor_invoices') === 'owned') {
            $cfaSalesQuery->where('invoices.client_id', user()->id);
        } elseif (user()->permission('view_cfa_distributor_invoices') === 'added') {
            $cfaSalesQuery->where('invoices.added_by', user()->id);
        }
        $cfaSalesCount = $cfaSalesQuery->count();
        $cfaSalesTotal = (float) (clone $cfaSalesQuery)->sum('total');

        // Sales: CFA→Stockist (distinct invoices)
        $stockistInvoiceIdsQuery = Invoice::where('invoices.company_id', company()->id)
            ->where('invoices.credit_note', 0)
            ->whereNotIn('invoices.status', ['canceled', 'draft'])
            ->join('cfa_stockist_stocks', 'cfa_stockist_stocks.invoice_id', '=', 'invoices.id')
            ->whereDate('invoices.issue_date', '>=', $startDate)
            ->whereDate('invoices.issue_date', '<=', $endDate)
            ->select('invoices.id');
        if (in_array('client', user_roles())) {
            $stockistInvoiceIdsQuery->where('invoices.client_id', user()->id);
        } elseif (!PharmaDesignationHelper::hasFullCFAAccess()) {
            $stockistInvoiceIdsQuery->where('cfa_stockist_stocks.cfa_distributor_id', user()->id);
        }
        $stockistInvoiceIds = (clone $stockistInvoiceIdsQuery)->distinct()->pluck('id');
        $stockistSalesCount = $stockistInvoiceIds->count();
        $stockistSalesTotal = $stockistInvoiceIds->isEmpty() ? 0 : (float) Invoice::whereIn('id', $stockistInvoiceIds)->sum('total');

        $this->fromDate = $request->get('from_date') ?: $startDate;
        $this->toDate = $request->get('to_date') ?: $endDate;
        $this->purchaseCount = $purchaseCount;
        $this->purchaseTotal = round($purchaseTotal, 2);
        $this->cfaSalesCount = $cfaSalesCount;
        $this->cfaSalesTotal = round($cfaSalesTotal, 2);
        $this->stockistSalesCount = $stockistSalesCount;
        $this->stockistSalesTotal = round($stockistSalesTotal, 2);
        $this->salesCount = $cfaSalesCount + $stockistSalesCount;
        $this->salesTotal = round($cfaSalesTotal + $stockistSalesTotal, 2);

        return view('invoice-reports.purchase-and-sales', $this->data);
    }
}
