<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Helper\RoleHierarchy;
use App\Models\CFAStockist;
use App\Models\CFAStockistStock;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\SalesPlanTarget;
use App\Models\StockStatement;
use App\Models\StockStatementLine;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Traits\AccessibleHeadquarters;

class StockStatementController extends AccountBaseController
{
    use AccessibleHeadquarters;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.salesStockStatement');
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('dcr_reports', $this->user->modules));
            return $next($request);
        });
    }

    /**
     * Get CFA Stockists assignable to current user (by HQ/area).
     */
    protected function assignedCfaStockistsQuery()
    {
        $q = CFAStockist::where('company_id', company()->id);
        $hqIds = $this->accessibleHeadquarterIds();
        $areaIds = $this->accessibleAreaIds();
        if ($hqIds === null) {
            return $q->orderBy('shopname');
        }
        if ((is_array($hqIds) && count($hqIds) > 0) || (is_array($areaIds) && count($areaIds) > 0)) {
            $q->where(function ($query) use ($hqIds, $areaIds) {
                if (is_array($hqIds) && count($hqIds) > 0) {
                    $query->whereIn('headquarter_id', $hqIds);
                }
                if (is_array($areaIds) && count($areaIds) > 0) {
                    $query->orWhereIn('area_id', $areaIds);
                }
            });
        }
        return $q->orderBy('shopname');
    }

    /**
     * List statements for current user (MR: own; manager/admin: by hierarchy + HQ/area).
     */
    public function index(Request $request)
    {
        $query = StockStatement::with(['user', 'cfaStockist'])
            ->where('company_id', company()->id);

        if (!user()->hasRole('admin')) {
            $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
            $query->whereIn('user_id', $viewableIds);
            $hqIds = $this->accessibleHeadquarterIds();
            $areaIds = $this->accessibleAreaIds();
            if (is_array($hqIds) && count($hqIds) === 0 && is_array($areaIds) && count($areaIds) === 0) {
                $query->where('user_id', user()->id);
            } else {
                $stockistIds = $this->assignedCfaStockistsQuery()->pluck('id');
                $query->where(function ($q) use ($stockistIds) {
                    $q->where('user_id', user()->id)
                        ->orWhereIn('cfa_stockist_id', $stockistIds);
                });
            }
        }

        if ($request->filled('period_month')) {
            $query->where('period_month', $request->period_month);
        }
        if ($request->filled('period_year')) {
            $query->where('period_year', $request->period_year);
        }
        if ($request->filled('cfa_stockist_id')) {
            $query->where('cfa_stockist_id', $request->cfa_stockist_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $this->statements = $query->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        $this->cfaStockists = $this->assignedCfaStockistsQuery()->get(['id', 'shopname', 'cfa_stockist_id']);
        $this->filterMonth = $request->period_month;
        $this->filterYear = $request->period_year;
        $this->filterStatus = $request->status;
        $this->filterStockistId = $request->cfa_stockist_id;

        // SRS 3.2.8: Mandatory for each assigned stockist – show pending (no submitted statement) for period
        $this->missingStockistsForPeriod = collect();
        $this->mandatoryPeriodMonth = null;
        $this->mandatoryPeriodYear = null;
        if (!user()->hasRole('admin')) {
            $periodMonth = $request->filled('period_month') ? (int) $request->period_month : Carbon::now()->month;
            $periodYear = $request->filled('period_year') ? (int) $request->period_year : Carbon::now()->year;
            $assignedIds = $this->assignedCfaStockistsQuery()->pluck('id');
            if ($assignedIds->isNotEmpty()) {
                $submittedStockistIds = StockStatement::where('company_id', company()->id)
                    ->where('user_id', user()->id)
                    ->where('period_month', $periodMonth)
                    ->where('period_year', $periodYear)
                    ->where('status', 'submitted')
                    ->pluck('cfa_stockist_id');
                $missingIds = $assignedIds->diff($submittedStockistIds)->values();
                if ($missingIds->isNotEmpty()) {
                    $this->missingStockistsForPeriod = CFAStockist::whereIn('id', $missingIds)->get(['id', 'shopname', 'cfa_stockist_id']);
                    $this->mandatoryPeriodMonth = $periodMonth;
                    $this->mandatoryPeriodYear = $periodYear;
                }
            }
        }

        return view('stock-statements.index', $this->data);
    }

    /**
     * Show create form: month, year, stockist; products with opening/primary/secondary/closing.
     */
    public function create(Request $request)
    {
        $this->cfaStockists = $this->assignedCfaStockistsQuery()->get();
        $this->products = Product::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $periodMonth = (int) ($request->period_month ?? Carbon::now()->month);
        $periodYear = (int) ($request->period_year ?? Carbon::now()->year);
        $cfaStockistId = $request->cfa_stockist_id ? (int) $request->cfa_stockist_id : null;
        $this->periodMonth = $periodMonth;
        $this->periodYear = $periodYear;
        $this->cfaStockistId = $cfaStockistId;

        if (request()->ajax()) {
            $html = view('stock-statements.ajax.create', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }
        return view('stock-statements.create', $this->data);
    }

    /**
     * Get opening qty for product + cfa_stockist: closing of previous month's statement line.
     */
    protected function getOpeningQty(int $cfaStockistId, int $productId, int $periodMonth, int $periodYear): float
    {
        $prev = Carbon::createFromDate($periodYear, $periodMonth, 1)->subMonth();
        $line = StockStatementLine::whereHas('stockStatement', function ($q) use ($cfaStockistId, $prev) {
            $q->where('company_id', company()->id)
                ->where('cfa_stockist_id', $cfaStockistId)
                ->where('period_month', $prev->month)
                ->where('period_year', $prev->year);
        })->where('product_id', $productId)->first();
        return $line ? (float) $line->closing_qty : 0;
    }

    /**
     * Get primary qty: sum of CFAStockistStock for product + cfa_stockist + invoices in statement month.
     */
    protected function getPrimaryQty(int $cfaStockistId, int $productId, int $periodMonth, int $periodYear): float
    {
        $start = Carbon::createFromDate($periodYear, $periodMonth, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $sum = CFAStockistStock::where('company_id', company()->id)
            ->where('cfa_stockist_id', $cfaStockistId)
            ->where('product_id', $productId)
            ->whereHas('invoice', function ($q) use ($start, $end) {
                $q->whereBetween('issue_date', [$start, $end]);
            })
            ->sum('quantity');
        return (float) $sum;
    }

    /**
     * API for create form: return opening/primary for each product for given stockist + period.
     */
    public function getOpeningPrimary(Request $request)
    {
        $cfaStockistId = (int) $request->cfa_stockist_id;
        $periodMonth = (int) $request->period_month;
        $periodYear = (int) $request->period_year;
        $productIds = $request->product_ids ? (array) $request->product_ids : [];
        $out = [];
        foreach ($productIds as $pid) {
            $pid = (int) $pid;
            $out[$pid] = [
                'opening_qty' => $this->getOpeningQty($cfaStockistId, $pid, $periodMonth, $periodYear),
                'primary_qty' => $this->getPrimaryQty($cfaStockistId, $pid, $periodMonth, $periodYear),
            ];
        }
        return Reply::dataOnly(['status' => 'success', 'data' => $out]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'period_month' => 'required|integer|between:1,12',
            'period_year' => 'required|integer|min:2020|max:2100',
            'cfa_stockist_id' => 'required|exists:cfa_stockists,id',
            'status' => 'nullable|in:draft,submitted',
            'lines' => 'required|array',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.secondary_qty' => 'nullable|numeric|min:0',
            'lines.*.opening_qty' => 'nullable|numeric|min:0',
            'lines.*.primary_qty' => 'nullable|numeric|min:0',
            'lines.*.closing_qty' => 'nullable|numeric|min:0',
        ]);

        $cfaStockistId = (int) $request->cfa_stockist_id;
        $stockistIds = $this->assignedCfaStockistsQuery()->pluck('id')->toArray();
        if (!in_array($cfaStockistId, $stockistIds)) {
            return Reply::error(__('messages.unauthorizedAccess'));
        }

        $existing = StockStatement::where('company_id', company()->id)
            ->where('user_id', user()->id)
            ->where('cfa_stockist_id', $cfaStockistId)
            ->where('period_month', $request->period_month)
            ->where('period_year', $request->period_year)
            ->first();
        if ($existing) {
            return Reply::error(__('app.stockStatementAlreadyExists'));
        }

        $productIdsSeen = [];
        foreach ($request->lines as $row) {
            $pid = (int) ($row['product_id'] ?? 0);
            if (in_array($pid, $productIdsSeen)) {
                return Reply::error(__('app.stockStatementDuplicateProduct'));
            }
            $productIdsSeen[] = $pid;
        }

        $status = $request->status === 'submitted' ? 'submitted' : 'draft';
        $statement = new StockStatement();
        $statement->company_id = company()->id;
        $statement->user_id = user()->id;
        $statement->cfa_stockist_id = $cfaStockistId;
        $statement->period_month = (int) $request->period_month;
        $statement->period_year = (int) $request->period_year;
        $statement->status = $status;
        if ($status === 'submitted') {
            $statement->submitted_at = now();
        }
        $statement->save();

        foreach ($request->lines as $row) {
            $productId = (int) $row['product_id'];
            $secondary = (float) ($row['secondary_qty'] ?? 0);
            $openingInput = isset($row['opening_qty']) && $row['opening_qty'] !== '' ? (float) $row['opening_qty'] : null;
            $primaryInput = isset($row['primary_qty']) && $row['primary_qty'] !== '' ? (float) $row['primary_qty'] : null;
            $closingInput = isset($row['closing_qty']) && $row['closing_qty'] !== '' ? (float) $row['closing_qty'] : null;
            $opening = $openingInput !== null ? $openingInput : $this->getOpeningQty($cfaStockistId, $productId, $statement->period_month, $statement->period_year);
            $primary = $primaryInput !== null ? $primaryInput : $this->getPrimaryQty($cfaStockistId, $productId, $statement->period_month, $statement->period_year);
            $closing = $closingInput !== null ? $closingInput : ($opening + $primary + $secondary);
            StockStatementLine::create([
                'stock_statement_id' => $statement->id,
                'product_id' => $productId,
                'opening_qty' => $opening,
                'primary_qty' => $primary,
                'secondary_qty' => $secondary,
                'closing_qty' => $closing,
            ]);
        }

        $redirect = route('stock-statements.show', $statement->id);
        if (request()->ajax()) {
            return Reply::redirect($redirect, __('messages.recordSaved'));
        }
        return redirect($redirect)->with('message', __('messages.recordSaved'));
    }

    public function show($id)
    {
        $statement = StockStatement::with(['user', 'cfaStockist', 'lines.product'])
            ->where('company_id', company()->id)->findOrFail($id);

        if (!user()->hasRole('admin')) {
            $stockistIds = $this->assignedCfaStockistsQuery()->pluck('id')->toArray();
            if ($statement->user_id != user()->id && !in_array($statement->cfa_stockist_id, $stockistIds)) {
                abort_403(true);
            }
        }

        $this->statement = $statement;
        if (request()->ajax()) {
            return view('stock-statements.ajax.show', $this->data);
        }
        return view('stock-statements.show', $this->data);
    }

    public function edit($id)
    {
        $statement = StockStatement::with(['lines.product'])
            ->where('company_id', company()->id)->findOrFail($id);
        if ($statement->status !== 'draft') {
            return Reply::error(__('app.onlyDraftEditable'));
        }
        if ($statement->user_id != user()->id) {
            abort_403(true);
        }

        $this->statement = $statement;
        $this->cfaStockists = $this->assignedCfaStockistsQuery()->get();
        $this->products = Product::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        if (request()->ajax()) {
            $html = view('stock-statements.ajax.edit', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }
        return view('stock-statements.edit', $this->data);
    }

    public function update(Request $request, $id)
    {
        $statement = StockStatement::where('company_id', company()->id)->findOrFail($id);
        if ($statement->status !== 'draft' || $statement->user_id != user()->id) {
            return Reply::error(__('app.onlyDraftEditable'));
        }

        $request->validate([
            'status' => 'nullable|in:draft,submitted',
            'lines' => 'required|array',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.secondary_qty' => 'nullable|numeric|min:0',
            'lines.*.opening_qty' => 'nullable|numeric|min:0',
            'lines.*.primary_qty' => 'nullable|numeric|min:0',
            'lines.*.closing_qty' => 'nullable|numeric|min:0',
        ]);

        $productIdsSeen = [];
        foreach ($request->lines as $row) {
            $pid = (int) ($row['product_id'] ?? 0);
            if (in_array($pid, $productIdsSeen)) {
                return Reply::error(__('app.stockStatementDuplicateProduct'));
            }
            $productIdsSeen[] = $pid;
        }

        $status = $request->status === 'submitted' ? 'submitted' : 'draft';
        $statement->status = $status;
        if ($status === 'submitted') {
            $statement->submitted_at = now();
        }
        $statement->save();

        $statement->lines()->delete();
        $cfaStockistId = $statement->cfa_stockist_id;
        foreach ($request->lines as $row) {
            $productId = (int) $row['product_id'];
            $secondary = (float) ($row['secondary_qty'] ?? 0);
            $openingInput = isset($row['opening_qty']) && $row['opening_qty'] !== '' ? (float) $row['opening_qty'] : null;
            $primaryInput = isset($row['primary_qty']) && $row['primary_qty'] !== '' ? (float) $row['primary_qty'] : null;
            $closingInput = isset($row['closing_qty']) && $row['closing_qty'] !== '' ? (float) $row['closing_qty'] : null;
            $opening = $openingInput !== null ? $openingInput : $this->getOpeningQty($cfaStockistId, $productId, $statement->period_month, $statement->period_year);
            $primary = $primaryInput !== null ? $primaryInput : $this->getPrimaryQty($cfaStockistId, $productId, $statement->period_month, $statement->period_year);
            $closing = $closingInput !== null ? $closingInput : ($opening + $primary + $secondary);
            StockStatementLine::create([
                'stock_statement_id' => $statement->id,
                'product_id' => $productId,
                'opening_qty' => $opening,
                'primary_qty' => $primary,
                'secondary_qty' => $secondary,
                'closing_qty' => $closing,
            ]);
        }

        $redirect = route('stock-statements.show', $statement->id);
        if (request()->ajax()) {
            return Reply::redirect($redirect, __('messages.recordSaved'));
        }
        return redirect($redirect)->with('message', __('messages.recordSaved'));
    }

    public function destroy($id)
    {
        $statement = StockStatement::where('company_id', company()->id)->findOrFail($id);
        if ($statement->status !== 'draft') {
            return Reply::error(__('app.onlyDraftDeletable'));
        }
        if ($statement->user_id != user()->id) {
            abort_403(true);
        }
        $statement->lines()->delete();
        $statement->delete();
        if (request()->ajax()) {
            return Reply::success(__('messages.recordDeleted'));
        }
        return redirect(route('stock-statements.index'))->with('message', __('messages.recordDeleted'));
    }

    /**
     * Consolidation report: roll up statement lines by HQ → Area → Region → Zone.
     * Visible only to upper hierarchy (admin or users with accessible HQs/areas).
     */
    public function consolidation(Request $request)
    {
        if (user()->hasRole('admin')) {
            // admin: allow
        } else {
            $hqIds = $this->accessibleHeadquarterIds();
            $areaIds = $this->accessibleAreaIds();
            if ((!is_array($hqIds) || count($hqIds) === 0) && (!is_array($areaIds) || count($areaIds) === 0)) {
                abort_403(true);
            }
        }

        $periodMonth = $request->filled('period_month') ? (int) $request->period_month : Carbon::now()->month;
        $periodYear = $request->filled('period_year') ? (int) $request->period_year : Carbon::now()->year;

        $query = StockStatementLine::query()
            ->select([
                'stock_statement_lines.product_id',
                'products.name as product_name',
                DB::raw('SUM(stock_statement_lines.opening_qty) as total_opening'),
                DB::raw('SUM(stock_statement_lines.primary_qty) as total_primary'),
                DB::raw('SUM(stock_statement_lines.secondary_qty) as total_secondary'),
                DB::raw('SUM(stock_statement_lines.closing_qty) as total_closing'),
            ])
            ->join('stock_statements', 'stock_statements.id', '=', 'stock_statement_lines.stock_statement_id')
            ->join('cfa_stockists', 'cfa_stockists.id', '=', 'stock_statements.cfa_stockist_id')
            ->whereNull('cfa_stockists.deleted_at')
            ->leftJoin('pharma_headquarters', 'pharma_headquarters.id', '=', 'cfa_stockists.headquarter_id')
            ->leftJoin('pharma_areas', 'pharma_areas.id', '=', 'cfa_stockists.area_id')
            ->leftJoin('pharma_regions', 'pharma_regions.id', '=', 'pharma_areas.region_id')
            ->leftJoin('pharma_zones', 'pharma_zones.id', '=', 'pharma_regions.zone_id')
            ->join('products', 'products.id', '=', 'stock_statement_lines.product_id')
            ->where('stock_statements.company_id', company()->id)
            ->where('stock_statements.period_month', $periodMonth)
            ->where('stock_statements.period_year', $periodYear)
            ->where('stock_statements.status', 'submitted')
            ->groupBy('stock_statement_lines.product_id', 'products.name');

        if (!user()->hasRole('admin')) {
            $hqIds = $this->accessibleHeadquarterIds();
            $areaIds = $this->accessibleAreaIds();
            $query->where(function ($q) use ($hqIds, $areaIds) {
                if (is_array($hqIds) && count($hqIds) > 0) {
                    $q->whereIn('cfa_stockists.headquarter_id', $hqIds);
                }
                if (is_array($areaIds) && count($areaIds) > 0) {
                    $q->orWhereIn('cfa_stockists.area_id', $areaIds);
                }
            });
        }
        if ($request->filled('headquarter_id')) {
            $query->where('cfa_stockists.headquarter_id', $request->headquarter_id);
        }
        if ($request->filled('area_id')) {
            $query->where('cfa_stockists.area_id', $request->area_id);
        }
        if ($request->filled('region_id')) {
            $query->where('pharma_areas.region_id', $request->region_id);
        }
        if ($request->filled('zone_id')) {
            $query->where('pharma_regions.zone_id', $request->zone_id);
        }

        $this->consolidationLines = $query->orderBy('products.name')->get();
        $this->periodMonth = $periodMonth;
        $this->periodYear = $periodYear;
        $this->headquarters = \App\Models\PharmaHeadquarter::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->areas = \App\Models\PharmaArea::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->regions = \App\Models\PharmaRegion::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->zones = \App\Models\PharmaZone::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->filterHeadquarterId = $request->headquarter_id;
        $this->filterAreaId = $request->area_id;
        $this->filterRegionId = $request->region_id;
        $this->filterZoneId = $request->zone_id;

        return view('stock-statements.consolidation', $this->data);
    }

    /**
     * Target vs Achievement report: targets (Sales Plan) vs Primary (invoicing) and Secondary (stock statement).
     * Visible only to upper hierarchy.
     */
    public function targetVsAchievement(Request $request)
    {
        if (user()->hasRole('admin')) {
            // allow
        } else {
            $hqIds = $this->accessibleHeadquarterIds();
            $areaIds = $this->accessibleAreaIds();
            if ((!is_array($hqIds) || count($hqIds) === 0) && (!is_array($areaIds) || count($areaIds) === 0)) {
                abort_403(true);
            }
        }

        $periodMonth = $request->filled('period_month') ? (int) $request->period_month : Carbon::now()->month;
        $periodYear = $request->filled('period_year') ? (int) $request->period_year : Carbon::now()->year;
        $start = Carbon::createFromDate($periodYear, $periodMonth, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $targetsQuery = SalesPlanTarget::with(['headquarter', 'area', 'region'])
            ->where('company_id', company()->id)
            ->where('period_month', $periodMonth)
            ->where('period_year', $periodYear);

        if ($request->filled('plan_level')) {
            $targetsQuery->where('plan_level', $request->plan_level);
        }
        if ($request->filled('headquarter_id')) {
            $targetsQuery->where('headquarter_id', $request->headquarter_id);
        }
        if ($request->filled('area_id')) {
            $targetsQuery->where('area_id', $request->area_id);
        }
        if ($request->filled('region_id')) {
            $targetsQuery->where('region_id', $request->region_id);
        }

        $targets = $targetsQuery->orderBy('plan_level')->orderBy('id')->get();

        $rows = [];
        foreach ($targets as $target) {
            $scopeHqId = $target->plan_level === 'headquarter' ? $target->headquarter_id : null;
            $scopeAreaId = $target->plan_level === 'area' ? $target->area_id : null;
            $scopeRegionId = $target->plan_level === 'region' ? $target->region_id : null;

            $primaryQ = Invoice::query()
                ->where('company_id', company()->id)
                ->whereBetween('issue_date', [$start, $end])
                ->whereHas('cfaStockistStocks', function ($q) use ($scopeHqId, $scopeAreaId, $scopeRegionId) {
                    $q->whereHas('cfaStockist', function ($cq) use ($scopeHqId, $scopeAreaId, $scopeRegionId) {
                        if ($scopeHqId) {
                            $cq->where('headquarter_id', $scopeHqId);
                        }
                        if ($scopeAreaId) {
                            $cq->where('area_id', $scopeAreaId);
                        }
                        if ($scopeRegionId) {
                            $cq->whereHas('area', function ($aq) use ($scopeRegionId) {
                                $aq->where('region_id', $scopeRegionId);
                            });
                        }
                    });
                });
            $primaryAmount = (float) (clone $primaryQ)->sum('total');

            $secondaryQ = StockStatementLine::query()
                ->join('stock_statements', 'stock_statements.id', '=', 'stock_statement_lines.stock_statement_id')
                ->join('cfa_stockists', 'cfa_stockists.id', '=', 'stock_statements.cfa_stockist_id')
                ->whereNull('cfa_stockists.deleted_at')
                ->where('stock_statements.company_id', company()->id)
                ->where('stock_statements.period_month', $periodMonth)
                ->where('stock_statements.period_year', $periodYear)
                ->where('stock_statements.status', 'submitted');
            if ($scopeHqId) {
                $secondaryQ->where('cfa_stockists.headquarter_id', $scopeHqId);
            }
            if ($scopeAreaId) {
                $secondaryQ->where('cfa_stockists.area_id', $scopeAreaId);
            }
            if ($scopeRegionId) {
                $secondaryQ->join('pharma_areas', 'pharma_areas.id', '=', 'cfa_stockists.area_id')
                    ->where('pharma_areas.region_id', $scopeRegionId);
            }
            $secondaryTotal = (float) (clone $secondaryQ)->sum('stock_statement_lines.primary_qty');

            $targetVal = (float) $target->target_amount;
            $rows[] = [
                'scope_name' => $target->scope_name,
                'plan_level' => $target->plan_level,
                'target' => $targetVal,
                'primary_achievement' => $primaryAmount,
                'secondary_achievement' => $secondaryTotal,
                'primary_pct' => $targetVal > 0 ? round(($primaryAmount / $targetVal) * 100, 1) : 0,
            ];
        }

        $this->reportRows = $rows;
        $this->periodMonth = $periodMonth;
        $this->periodYear = $periodYear;
        $this->headquarters = \App\Models\PharmaHeadquarter::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->areas = \App\Models\PharmaArea::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->regions = \App\Models\PharmaRegion::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->filterPlanLevel = $request->plan_level;
        $this->filterHeadquarterId = $request->headquarter_id;
        $this->filterAreaId = $request->area_id;
        $this->filterRegionId = $request->region_id;

        return view('stock-statements.target-vs-achievement', $this->data);
    }
}
