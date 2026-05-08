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
use Illuminate\Http\Request;
use App\Traits\AccessibleHeadquarters;

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

    public function index(Request $request)
    {
        $query = SalesPlanTarget::with(['headquarter', 'area', 'region', 'product'])
            ->where('company_id', company()->id);

        if ($request->filled('period_month')) {
            $query->where('period_month', $request->period_month);
        }
        if ($request->filled('period_year')) {
            $query->where('period_year', $request->period_year);
        }
        if ($request->filled('plan_level')) {
            $query->where('plan_level', $request->plan_level);
        }
        if ($request->filled('headquarter_id')) {
            $query->where('headquarter_id', $request->headquarter_id);
        }
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }
        if ($request->filled('region_id')) {
            $query->where('region_id', $request->region_id);
        }

        $this->targets = $query->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->orderBy('plan_level')
            ->orderBy('id')
            ->paginate(20);

        $this->headquarters = PharmaHeadquarter::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->areas = PharmaArea::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->regions = PharmaRegion::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->filterMonth = $request->period_month;
        $this->filterYear = $request->period_year;
        $this->filterPlanLevel = $request->plan_level;
        $this->filterHeadquarterId = $request->headquarter_id;
        $this->filterAreaId = $request->area_id;
        $this->filterRegionId = $request->region_id;

        return view('sales-plan.index', $this->data);
    }

    public function create()
    {
        $this->headquarters = PharmaHeadquarter::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->areas = PharmaArea::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->regions = PharmaRegion::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->products = Product::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);

        if (request()->ajax()) {
            $html = view('sales-plan.ajax.create', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }
        return view('sales-plan.create', $this->data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'period_month' => 'required|integer|between:1,12',
            'period_year' => 'required|integer|min:2020|max:2100',
            'plan_level' => 'required|in:headquarter,area,region',
            'headquarter_id' => 'nullable|required_if:plan_level,headquarter|exists:pharma_headquarters,id',
            'area_id' => 'nullable|required_if:plan_level,area|exists:pharma_areas,id',
            'region_id' => 'nullable|required_if:plan_level,region|exists:pharma_regions,id',
            'target_amount' => 'required|numeric|min:0',
            'product_id' => 'nullable|exists:products,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $headquarterId = $request->plan_level === 'headquarter' ? $request->headquarter_id : null;
        $areaId = $request->plan_level === 'area' ? $request->area_id : null;
        $regionId = $request->plan_level === 'region' ? $request->region_id : null;

        SalesPlanTarget::create([
            'company_id' => company()->id,
            'period_month' => (int) $request->period_month,
            'period_year' => (int) $request->period_year,
            'plan_level' => $request->plan_level,
            'headquarter_id' => $headquarterId,
            'area_id' => $areaId,
            'region_id' => $regionId,
            'target_amount' => $request->target_amount,
            'product_id' => $request->product_id ?: null,
            'notes' => $request->notes,
        ]);

        if (request()->ajax()) {
            return Reply::redirect(route('sales-plan.index'), __('messages.recordSaved'));
        }
        return redirect(route('sales-plan.index'))->with('message', __('messages.recordSaved'));
    }

    public function edit($id)
    {
        $this->target = SalesPlanTarget::where('company_id', company()->id)->findOrFail($id);
        $this->headquarters = PharmaHeadquarter::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->areas = PharmaArea::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->regions = PharmaRegion::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->products = Product::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);

        if (request()->ajax()) {
            $html = view('sales-plan.ajax.edit', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }
        return view('sales-plan.edit', $this->data);
    }

    public function update(Request $request, $id)
    {
        $target = SalesPlanTarget::where('company_id', company()->id)->findOrFail($id);

        $request->validate([
            'period_month' => 'required|integer|between:1,12',
            'period_year' => 'required|integer|min:2020|max:2100',
            'plan_level' => 'required|in:headquarter,area,region',
            'headquarter_id' => 'nullable|required_if:plan_level,headquarter|exists:pharma_headquarters,id',
            'area_id' => 'nullable|required_if:plan_level,area|exists:pharma_areas,id',
            'region_id' => 'nullable|required_if:plan_level,region|exists:pharma_regions,id',
            'target_amount' => 'required|numeric|min:0',
            'product_id' => 'nullable|exists:products,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $target->period_month = (int) $request->period_month;
        $target->period_year = (int) $request->period_year;
        $target->plan_level = $request->plan_level;
        $target->headquarter_id = $request->plan_level === 'headquarter' ? $request->headquarter_id : null;
        $target->area_id = $request->plan_level === 'area' ? $request->area_id : null;
        $target->region_id = $request->plan_level === 'region' ? $request->region_id : null;
        $target->target_amount = $request->target_amount;
        $target->product_id = $request->product_id ?: null;
        $target->notes = $request->notes;
        $target->save();

        if (request()->ajax()) {
            return Reply::redirect(route('sales-plan.index'), __('messages.recordSaved'));
        }
        return redirect(route('sales-plan.index'))->with('message', __('messages.recordSaved'));
    }

    public function destroy($id)
    {
        $target = SalesPlanTarget::where('company_id', company()->id)->findOrFail($id);
        $target->delete();

        if (request()->ajax()) {
            return Reply::success(__('messages.recordDeleted'));
        }
        return redirect(route('sales-plan.index'))->with('message', __('messages.recordDeleted'));
    }
}
