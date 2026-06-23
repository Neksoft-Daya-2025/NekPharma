<?php

namespace App\Http\Controllers;

use App\Exports\SalesPlanExport;
use App\Exports\SalesPlanSampleExport;
use App\Helper\Reply;
use App\Helper\RoleHierarchy;
use App\Models\PharmaArea;
use App\Models\PharmaHeadquarter;
use App\Models\PharmaRegion;
use App\Models\Product;
use App\Models\SalesPlanTarget;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
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

    private function salesPlanTargetsQuery(Request $request)
    {
        $query = SalesPlanTarget::with(['headquarter', 'area', 'region', 'product'])
            ->where('company_id', company()->id)
            ->where(function ($q) {
                $q->whereNull('plan_type')->orWhere('plan_type', 'sales_plan');
            });

        $this->applyAccessibleTargetScope($query);

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

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->salesPlanTargetsQuery($request);

        $this->targets = $query->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->orderBy('plan_level')
            ->orderBy('id')
            ->paginate(20);

        $this->headquarters = $this->scopedHeadquarters();
        $this->areas = $this->scopedAreas();
        $this->regions = $this->scopedRegions();
        $this->filterMonth = $request->period_month;
        $this->filterYear = $request->period_year;
        $this->filterPlanLevel = $request->plan_level;
        $this->filterHeadquarterId = $request->headquarter_id;
        $this->filterAreaId = $request->area_id;
        $this->filterRegionId = $request->region_id;

        return view('sales-plan.index', $this->data);
    }

    public function export(Request $request)
    {
        $targets = $this->salesPlanTargetsQuery($request)
            ->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->orderBy('plan_level')
            ->orderBy('id')
            ->get();

        return Excel::download(new SalesPlanExport($targets), 'sales-plan-' . now()->format('Y-m-d') . '.xlsx');
    }

    public function downloadSample()
    {
        return Excel::download(
            new SalesPlanSampleExport(),
            'sales-plan-sample.csv',
            \Maatwebsite\Excel\Excel::CSV,
            ['Content-Type' => 'text/csv']
        );
    }

    public function importTargets(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('import_file');
        $parsed = $this->parseSalesPlanCsv($file->getRealPath());

        if ($parsed['errors']) {
            return Reply::error(implode(' ', $parsed['errors']));
        }

        if (empty($parsed['rows'])) {
            return Reply::error(__('messages.noRecordFound'));
        }

        $headquarters = $this->scopedHeadquarters()->keyBy(fn ($item) => $this->normalizeCsvKey($item->name));
        $areas = $this->scopedAreas()->keyBy(fn ($item) => $this->normalizeCsvKey($item->name));
        $regions = $this->scopedRegions()->keyBy(fn ($item) => $this->normalizeCsvKey($item->name));
        $products = Product::where('company_id', company()->id)
            ->get(['id', 'name'])
            ->keyBy(fn ($item) => $this->normalizeCsvKey($item->name));

        $validRows = [];
        $skipped = [];

        foreach ($parsed['rows'] as $rowNum => $row) {
            $scope = trim((string) $row['scope']);
            $scopeKey = $this->normalizeCsvKey($scope);
            $headquarterId = null;
            $areaId = null;
            $regionId = null;

            if ($row['plan_level'] === 'headquarter') {
                $matched = $headquarters->get($scopeKey);
                $headquarterId = $matched?->id;
            } elseif ($row['plan_level'] === 'area') {
                $matched = $areas->get($scopeKey);
                $areaId = $matched?->id;
            } else {
                $matched = $regions->get($scopeKey);
                $regionId = $matched?->id;
            }

            if (empty($matched)) {
                $skipped[] = ['row' => $rowNum, 'reason' => 'Scope not found or not accessible'];
                continue;
            }

            $productId = null;
            $productName = trim((string) $row['product']);
            if ($productName !== '' && strtolower($productName) !== 'all products') {
                $product = $products->get($this->normalizeCsvKey($productName));
                if (! $product) {
                    $skipped[] = ['row' => $rowNum, 'reason' => 'Product not found'];
                    continue;
                }
                $productId = $product->id;
            }

            $this->ensureSalesPlanScopeAccessible($row['plan_level'], $headquarterId ? (int) $headquarterId : null, $areaId ? (int) $areaId : null, $regionId ? (int) $regionId : null);

            $validRows[] = [
                'period_month' => (int) $row['period_month'],
                'period_year' => (int) $row['period_year'],
                'plan_level' => $row['plan_level'],
                'headquarter_id' => $headquarterId,
                'area_id' => $areaId,
                'region_id' => $regionId,
                'target_amount' => $row['target_amount'],
                'product_id' => $productId,
                'notes' => $row['notes'],
            ];
        }

        if (empty($validRows)) {
            return Reply::error(__('messages.noRecordFound') . ' - no valid sales plan rows matched.');
        }

        DB::transaction(function () use ($validRows) {
            foreach ($validRows as $row) {
                $target = SalesPlanTarget::create(array_merge([
                    'company_id' => company()->id,
                    'plan_type' => 'sales_plan',
                ], $row));

                EnterpriseAudit::record('sales_plan_target.created', $target, [], $target->only([
                    'period_month',
                    'period_year',
                    'plan_level',
                    'headquarter_id',
                    'area_id',
                    'region_id',
                    'target_amount',
                    'product_id',
                ]));
            }
        });

        return Reply::dataOnly([
            'status' => 'success',
            'message' => count($validRows) . ' sales plan row(s) imported.' . (count($skipped) ? ' ' . count($skipped) . ' row(s) skipped.' : ''),
            'imported' => count($validRows),
            'skipped' => $skipped,
        ]);
    }

    private function parseSalesPlanCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return ['rows' => [], 'errors' => ['Could not read CSV file.']];
        }

        $headerMap = null;
        $rows = [];
        $errors = [];
        $lineNumber = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $lineNumber++;

            if ($this->csvRowIsEmpty($data)) {
                continue;
            }

            if ($headerMap === null) {
                $headerMap = $this->mapSalesPlanCsvHeaders($data);
                if ($headerMap === null) {
                    fclose($handle);

                    return ['rows' => [], 'errors' => ['Invalid CSV header. Expected columns: Period Month, Period Year, Plan Level, Scope, Target Amount.']];
                }
                continue;
            }

            $periodMonth = $this->parseCsvMonth($data[$headerMap['period_month']] ?? null);
            $periodYear = $this->parseCsvInteger($data[$headerMap['period_year']] ?? null);
            $planLevel = $this->normalizePlanLevel($data[$headerMap['plan_level']] ?? null);
            $scope = trim((string) ($data[$headerMap['scope']] ?? ''));
            $targetAmount = $this->parseCsvNumber($data[$headerMap['target_amount']] ?? null);

            if ($periodMonth === null) {
                $errors[] = "Row {$lineNumber}: Period Month must be 1-12 or a month name.";
            }
            if ($periodYear === null || $periodYear < 2020 || $periodYear > 2100) {
                $errors[] = "Row {$lineNumber}: Period Year must be between 2020 and 2100.";
            }
            if ($planLevel === null) {
                $errors[] = "Row {$lineNumber}: Plan Level must be headquarter, area, or region.";
            }
            if ($scope === '') {
                $errors[] = "Row {$lineNumber}: Scope is required.";
            }
            if ($targetAmount === null || $targetAmount < 0) {
                $errors[] = "Row {$lineNumber}: Target Amount must be a non-negative number.";
            }

            $rows[$lineNumber] = [
                'period_month' => $periodMonth ?? 0,
                'period_year' => $periodYear ?? 0,
                'plan_level' => $planLevel ?? '',
                'scope' => $scope,
                'product' => isset($headerMap['product']) ? trim((string) ($data[$headerMap['product']] ?? '')) : '',
                'target_amount' => $targetAmount ?? 0,
                'notes' => isset($headerMap['notes']) ? trim((string) ($data[$headerMap['notes']] ?? '')) : '',
            ];
        }

        fclose($handle);

        return ['rows' => $rows, 'errors' => $errors];
    }

    private function mapSalesPlanCsvHeaders(array $headerRow): ?array
    {
        $normalize = static fn ($value) => strtolower(trim(preg_replace('/[^a-z0-9]/', '', (string) $value)));
        $aliases = [
            'period_month' => ['periodmonth', 'month'],
            'period_year' => ['periodyear', 'year'],
            'plan_level' => ['planlevel', 'level'],
            'scope' => ['scope', 'hqarearegion', 'headquarterarearegion', 'name'],
            'product' => ['product', 'productname', 'item', 'itemname', 'medicine'],
            'target_amount' => ['targetamount', 'targetvalue', 'amount', 'value'],
            'notes' => ['notes', 'remark', 'remarks'],
        ];

        $map = [];
        foreach ($headerRow as $index => $heading) {
            $key = $normalize($heading);
            foreach ($aliases as $field => $options) {
                if (in_array($key, $options, true) && ! isset($map[$field])) {
                    $map[$field] = $index;
                    break;
                }
            }
        }

        return isset($map['period_month'], $map['period_year'], $map['plan_level'], $map['scope'], $map['target_amount']) ? $map : null;
    }

    private function parseCsvMonth($value): ?int
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return null;
        }

        if (ctype_digit($trimmed)) {
            $month = (int) $trimmed;
            return $month >= 1 && $month <= 12 ? $month : null;
        }

        try {
            return Carbon::parse('1 ' . $trimmed)->month;
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseCsvInteger($value): ?int
    {
        $trimmed = trim((string) $value);
        return $trimmed !== '' && ctype_digit($trimmed) ? (int) $trimmed : null;
    }

    private function parseCsvNumber($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '' || ! is_numeric($trimmed)) {
            return null;
        }

        return (float) $trimmed;
    }

    private function normalizePlanLevel($value): ?string
    {
        $key = $this->normalizeCsvKey($value);

        return match ($key) {
            'hq', 'headquarter', 'headquarters', 'hqwise' => 'headquarter',
            'area', 'areawise' => 'area',
            'region', 'regionwise' => 'region',
            default => null,
        };
    }

    private function normalizeCsvKey($value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));
    }

    private function csvRowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    public function create()
    {
        $this->headquarters = $this->scopedHeadquarters();
        $this->areas = $this->scopedAreas();
        $this->regions = $this->scopedRegions();
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

        $this->ensureSalesPlanScopeAccessible($request->plan_level, $headquarterId ? (int) $headquarterId : null, $areaId ? (int) $areaId : null, $regionId ? (int) $regionId : null);

        $target = SalesPlanTarget::create([
            'company_id' => company()->id,
            'plan_type' => 'sales_plan',
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

        EnterpriseAudit::record('sales_plan_target.created', $target, [], $target->only([
            'period_month',
            'period_year',
            'plan_level',
            'headquarter_id',
            'area_id',
            'region_id',
            'target_amount',
            'product_id',
        ]));

        if (request()->ajax()) {
            return Reply::redirect(route('sales-plan.index'), __('messages.recordSaved'));
        }
        return redirect(route('sales-plan.index'))->with('message', __('messages.recordSaved'));
    }

    public function edit($id)
    {
        $this->target = SalesPlanTarget::where('company_id', company()->id)->findOrFail($id);
        abort_403($this->target->plan_type === 'target_plan');
        $this->ensureSalesPlanScopeAccessible($this->target->plan_level, $this->target->headquarter_id ? (int) $this->target->headquarter_id : null, $this->target->area_id ? (int) $this->target->area_id : null, $this->target->region_id ? (int) $this->target->region_id : null);
        $this->headquarters = $this->scopedHeadquarters();
        $this->areas = $this->scopedAreas();
        $this->regions = $this->scopedRegions();
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
        abort_403($target->plan_type === 'target_plan');
        $this->ensureSalesPlanScopeAccessible($target->plan_level, $target->headquarter_id ? (int) $target->headquarter_id : null, $target->area_id ? (int) $target->area_id : null, $target->region_id ? (int) $target->region_id : null);

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

        $before = $target->only([
            'period_month',
            'period_year',
            'plan_level',
            'headquarter_id',
            'area_id',
            'region_id',
            'target_amount',
            'product_id',
        ]);

        $newHeadquarterId = $request->plan_level === 'headquarter' ? (int) $request->headquarter_id : null;
        $newAreaId = $request->plan_level === 'area' ? (int) $request->area_id : null;
        $newRegionId = $request->plan_level === 'region' ? (int) $request->region_id : null;
        $this->ensureSalesPlanScopeAccessible($request->plan_level, $newHeadquarterId, $newAreaId, $newRegionId);

        $target->period_month = (int) $request->period_month;
        $target->period_year = (int) $request->period_year;
        $target->plan_type = 'sales_plan';
        $target->plan_level = $request->plan_level;
        $target->headquarter_id = $newHeadquarterId;
        $target->area_id = $newAreaId;
        $target->region_id = $newRegionId;
        $target->target_amount = $request->target_amount;
        $target->product_id = $request->product_id ?: null;
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
            'product_id',
        ]));

        if (request()->ajax()) {
            return Reply::redirect(route('sales-plan.index'), __('messages.recordSaved'));
        }
        return redirect(route('sales-plan.index'))->with('message', __('messages.recordSaved'));
    }

    public function destroy($id)
    {
        $target = SalesPlanTarget::where('company_id', company()->id)->findOrFail($id);
        abort_403($target->plan_type === 'target_plan');
        $this->ensureSalesPlanScopeAccessible($target->plan_level, $target->headquarter_id ? (int) $target->headquarter_id : null, $target->area_id ? (int) $target->area_id : null, $target->region_id ? (int) $target->region_id : null);
        $before = $target->only([
            'period_month',
            'period_year',
            'plan_level',
            'headquarter_id',
            'area_id',
            'region_id',
            'target_amount',
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
