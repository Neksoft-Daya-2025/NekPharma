<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Helper\RoleHierarchy;
use App\Models\PharmaArea;
use App\Models\PharmaHeadquarter;
use App\Models\PharmaRegion;
use App\Models\Product;
use App\Models\SalesPlanTarget;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\AccessibleHeadquarters;
use App\Support\EnterpriseAudit;

class SalesPlanController extends AccountBaseController
{
    use AccessibleHeadquarters;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.salesPlan');
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('dcr_reports', $this->user->modules));
            // SRS 3.2.7: Sales Plan visible only to upper hierarchy, not to lower (e.g. not to MR)
            if (user()->hasAdminLikeAccess()) {
                return $next($request);
            }
            $level = RoleHierarchy::userHierarchyLevel(user());
            // Fallback when hierarchy_level is null: allow if user has an upper-hierarchy role by name
            $upperHierarchyRoleNames = ['area-business-manager', 'regional-manager', 'zonal-manager', 'sales-manager', 'pmt', 'hr'];
            user()->loadMissing('roles');
            $hasUpperRoleByName = user()->roles && user()->roles->contains(fn ($r) => in_array($r->name ?? '', $upperHierarchyRoleNames, true));
            if ($level === null && $hasUpperRoleByName) {
                return $next($request);
            }
            if ($level === null || $level < 2) {
                abort_403(true);
            }
            return $next($request);
        });
    }

    private function accessibleRegionIds(): ?array
    {
        $areaIds = $this->accessibleAreaIds();

        if ($areaIds === null) {
            return null;
        }

        if (empty($areaIds)) {
            return [];
        }

        return PharmaArea::where('company_id', company()->id)
            ->whereIn('id', $areaIds)
            ->whereNotNull('region_id')
            ->pluck('region_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();
    }

    private function applyAccessibleTargetScope($query): void
    {
        if (user()->hasAdminLikeAccess()) {
            return;
        }

        $hqIds = $this->accessibleHeadquarterIds();
        $areaIds = $this->accessibleAreaIds();
        $regionIds = $this->accessibleRegionIds();

        if ($hqIds === null || $areaIds === null || $regionIds === null) {
            return;
        }

        if (empty($hqIds) && empty($areaIds) && empty($regionIds)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->where(function ($q) use ($hqIds, $areaIds, $regionIds) {
            if (!empty($hqIds)) {
                $q->orWhere(function ($sub) use ($hqIds) {
                    $sub->where('plan_level', 'headquarter')->whereIn('headquarter_id', $hqIds);
                });
            }

            if (!empty($areaIds)) {
                $q->orWhere(function ($sub) use ($areaIds) {
                    $sub->where('plan_level', 'area')->whereIn('area_id', $areaIds);
                });
            }

            if (!empty($regionIds)) {
                $q->orWhere(function ($sub) use ($regionIds) {
                    $sub->where('plan_level', 'region')->whereIn('region_id', $regionIds);
                });
            }
        });
    }

    private function ensureSalesPlanScopeAccessible(string $planLevel, ?int $headquarterId, ?int $areaId, ?int $regionId): void
    {
        if (user()->hasAdminLikeAccess()) {
            return;
        }

        $hqIds = $this->accessibleHeadquarterIds();
        $areaIds = $this->accessibleAreaIds();
        $regionIds = $this->accessibleRegionIds();

        if ($hqIds === null || $areaIds === null || $regionIds === null) {
            return;
        }

        $allowed = match ($planLevel) {
            'headquarter' => $headquarterId !== null && in_array($headquarterId, array_map('intval', $hqIds), true),
            'area' => $areaId !== null && in_array($areaId, array_map('intval', $areaIds), true),
            'region' => $regionId !== null && in_array($regionId, array_map('intval', $regionIds), true),
            default => false,
        };

        abort_403(!$allowed);
    }

    private function scopedHeadquarters()
    {
        $query = PharmaHeadquarter::where('company_id', company()->id)->orderBy('name');
        $hqIds = $this->accessibleHeadquarterIds();

        if ($hqIds !== null) {
            $query->whereIn('id', $hqIds);
        }

        return $query->get(['id', 'name']);
    }

    private function abortIfNotAdmin(): void
    {
        abort_403(! user()->hasAdminLikeAccess());
    }

    private function duplicateTargetExists(
        int $periodMonth,
        int $periodYear,
        int $headquarterId,
        int $productId,
        ?int $ignoreId = null
    ): bool {
        return SalesPlanTarget::where('company_id', company()->id)
            ->where('period_month', $periodMonth)
            ->where('period_year', $periodYear)
            ->where('plan_level', 'headquarter')
            ->where('headquarter_id', $headquarterId)
            ->where('product_id', $productId)
            ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function scopedAreas()
    {
        $query = PharmaArea::where('company_id', company()->id)->orderBy('name');
        $areaIds = $this->accessibleAreaIds();

        if ($areaIds !== null) {
            $query->whereIn('id', $areaIds);
        }

        return $query->get(['id', 'name']);
    }

    private function scopedRegions()
    {
        $query = PharmaRegion::where('company_id', company()->id)->orderBy('name');
        $regionIds = $this->accessibleRegionIds();

        if ($regionIds !== null) {
            $query->whereIn('id', $regionIds);
        }

        return $query->get(['id', 'name']);
    }

    public function index(Request $request)
    {
        $query = SalesPlanTarget::with(['headquarter', 'area', 'region', 'product'])
            ->where('company_id', company()->id)
            ->where('plan_level', 'headquarter');

        $this->applyAccessibleTargetScope($query);

        if ($request->filled('period_month')) {
            $query->where('period_month', $request->period_month);
        }
        if ($request->filled('period_year')) {
            $query->where('period_year', $request->period_year);
        }
        if ($request->filled('headquarter_id')) {
            $query->where('headquarter_id', $request->headquarter_id);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $this->targets = $query->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->orderBy('headquarter_id')
            ->orderBy('product_id')
            ->orderBy('id')
            ->paginate(20);

        $this->headquarters = $this->scopedHeadquarters();
        $this->products = Product::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->filterMonth = $request->period_month;
        $this->filterYear = $request->period_year;
        $this->filterHeadquarterId = $request->headquarter_id;
        $this->filterProductId = $request->product_id;

        return view('sales-plan.index', $this->data);
    }

    public function create()
    {
        $this->abortIfNotAdmin();

        $this->headquarters = PharmaHeadquarter::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->products = Product::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);

        if (request()->ajax()) {
            $html = view('sales-plan.ajax.create', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }
        return view('sales-plan.create', $this->data);
    }

    public function store(Request $request)
    {
        $this->abortIfNotAdmin();

        $request->validate([
            'period_month' => 'required|integer|between:1,12',
            'period_year' => 'required|integer|min:2020|max:2100',
            'headquarter_id' => 'required|exists:pharma_headquarters,id',
            'targets' => 'required|array|min:1',
            'targets.*.product_id' => 'required|exists:products,id',
            'targets.*.target_amount' => 'required|numeric|min:0',
            'targets.*.target_qty' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $headquarterId = (int) $request->headquarter_id;
        $periodMonth = (int) $request->period_month;
        $periodYear = (int) $request->period_year;
        $productIds = [];

        foreach ($request->targets as $targetRow) {
            $productId = (int) $targetRow['product_id'];
            if (in_array($productId, $productIds, true)) {
                return Reply::error('Duplicate products are selected in the target list.');
            }
            if ($this->duplicateTargetExists($periodMonth, $periodYear, $headquarterId, $productId)) {
                return Reply::error('Target already exists for one or more selected products in this month and headquarter.');
            }
            $productIds[] = $productId;
        }

        DB::transaction(function () use ($request, $headquarterId, $periodMonth, $periodYear) {
            foreach ($request->targets as $targetRow) {
                $target = SalesPlanTarget::create([
                    'company_id' => company()->id,
                    'period_month' => $periodMonth,
                    'period_year' => $periodYear,
                    'plan_level' => 'headquarter',
                    'headquarter_id' => $headquarterId,
                    'area_id' => null,
                    'region_id' => null,
                    'target_amount' => $targetRow['target_amount'],
                    'target_qty' => $targetRow['target_qty'],
                    'product_id' => (int) $targetRow['product_id'],
                    'notes' => $request->notes,
                ]);

                EnterpriseAudit::record('sales_plan_target.created', $target, [], $target->only([
                    'period_month',
                    'period_year',
                    'plan_level',
                    'headquarter_id',
                    'area_id',
                    'region_id',
                    'target_amount',
                    'target_qty',
                    'product_id',
                ]));
            }
        });

        if (request()->ajax()) {
            return Reply::redirect(route('sales-plan.index'), __('messages.recordSaved'));
        }
        return redirect(route('sales-plan.index'))->with('message', __('messages.recordSaved'));
    }

    public function edit($id)
    {
        $this->abortIfNotAdmin();

        $this->target = SalesPlanTarget::where('company_id', company()->id)->findOrFail($id);
        $this->headquarters = PharmaHeadquarter::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->products = Product::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);

        if (request()->ajax()) {
            $html = view('sales-plan.ajax.edit', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }
        return view('sales-plan.edit', $this->data);
    }

    public function update(Request $request, $id)
    {
        $this->abortIfNotAdmin();

        $target = SalesPlanTarget::where('company_id', company()->id)->findOrFail($id);

        $request->validate([
            'period_month' => 'required|integer|between:1,12',
            'period_year' => 'required|integer|min:2020|max:2100',
            'headquarter_id' => 'required|exists:pharma_headquarters,id',
            'product_id' => 'required|exists:products,id',
            'target_amount' => 'required|numeric|min:0',
            'target_qty' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $before = $target->only([
            'period_month',
            'period_year',
            'plan_level',
            'headquarter_id',
            'area_id',
            'region_id',
            'target_amount',
            'target_qty',
            'product_id',
        ]);

        $newHeadquarterId = (int) $request->headquarter_id;
        $newProductId = (int) $request->product_id;
        if ($this->duplicateTargetExists((int) $request->period_month, (int) $request->period_year, $newHeadquarterId, $newProductId, (int) $target->id)) {
            return Reply::error('Target already exists for this month, headquarter, and product.');
        }

        $target->period_month = (int) $request->period_month;
        $target->period_year = (int) $request->period_year;
        $target->plan_level = 'headquarter';
        $target->headquarter_id = $newHeadquarterId;
        $target->area_id = null;
        $target->region_id = null;
        $target->target_amount = $request->target_amount;
        $target->target_qty = $request->target_qty;
        $target->product_id = $newProductId;
        $target->notes = $request->notes;
        $target->save();

        EnterpriseAudit::record('sales_plan_target.updated', $target, $before, $target->only([
            'period_month',
            'period_year',
            'plan_level',
            'headquarter_id',
            'area_id',
            'region_id',
            'target_amount',
            'target_qty',
            'product_id',
        ]));

        if (request()->ajax()) {
            return Reply::redirect(route('sales-plan.index'), __('messages.recordSaved'));
        }
        return redirect(route('sales-plan.index'))->with('message', __('messages.recordSaved'));
    }

    public function destroy($id)
    {
        $this->abortIfNotAdmin();

        $target = SalesPlanTarget::where('company_id', company()->id)->findOrFail($id);
        $before = $target->only([
            'period_month',
            'period_year',
            'plan_level',
            'headquarter_id',
            'area_id',
            'region_id',
            'target_amount',
            'target_qty',
            'product_id',
        ]);
        $target->delete();
        EnterpriseAudit::record('sales_plan_target.deleted', $target, $before, [], [], 'warning');

        if (request()->ajax()) {
            return Reply::success(__('messages.recordDeleted'));
        }
        return redirect(route('sales-plan.index'))->with('message', __('messages.recordDeleted'));
    }
}
