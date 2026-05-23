<?php

namespace App\Http\Controllers;

use App\Helper\Files;
use App\Helper\Reply;
use App\Models\Doctor;
use App\Models\Chemist;
use App\Models\Stockist;
use App\Models\Product;
use App\Models\PharmaHeadquarter;
use App\Models\PharmaHeadquarterAssign;
use App\Imports\DoctorImport;
use App\Jobs\ImportDoctorJob;
use App\Exports\DoctorSampleExport;
use App\Exports\DoctorExport;
use App\Traits\ImportExcel;
use App\Traits\AccessibleHeadquarters;
use App\Http\Requests\Admin\Employee\ImportRequest;
use App\Http\Requests\Admin\Employee\ImportProcessRequest;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use App\Services\DoctorDuplicateMergeService;

class DoctorController extends AccountBaseController
{
    use ImportExcel, AccessibleHeadquarters;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Doctors';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('doctors', $this->user->modules));
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $this->viewPermission = user()->permission('view_doctors');
        abort_403(!in_array($this->viewPermission, ['all', 'added', 'owned', 'both']));

        $query = $this->scopedDoctorsQuery($request, applyHeadquarterStationFilter: true);

        $headquarters = $this->accessibleHeadquartersCollection();

        // Load all matching doctors for the index (scrollable table handles long lists in the viewport)
        $this->doctors = $query->orderBy('fullname')->get();
        $this->headquarters = $headquarters;

        $companyId = company()->id;
        $this->qualificationOptions = Doctor::where('company_id', $companyId)
            ->whereNotNull('qualification')
            ->where('qualification', '!=', '')
            ->distinct()
            ->orderBy('qualification')
            ->pluck('qualification')
            ->values();
        $this->specialityOptions = Doctor::where('company_id', $companyId)
            ->whereNotNull('speciality')
            ->where('speciality', '!=', '')
            ->distinct()
            ->orderBy('speciality')
            ->pluck('speciality')
            ->values();
        $this->headquarterStations = $this->formatHeadquarterStations($headquarters);
        $this->defaultHeadquarterId = $this->determineDefaultHeadquarterId($headquarters, request()->get('headquarter_id'));

        return view('doctors.index', $this->data);
    }

    public function export(Request $request)
    {
        $this->viewPermission = user()->permission('view_doctors');
        abort_403(!in_array($this->viewPermission, ['all', 'added', 'owned', 'both']));

        // Match the on-screen list: same geography scope as index, then the same inline filters.
        $useInlineFilters = $request->boolean('list_filter');
        $query = $this->scopedDoctorsQuery($request, applyHeadquarterStationFilter: ! $useInlineFilters);

        if ($useInlineFilters) {
            $this->applyDoctorsInlineListFilters($query, $request);
        }

        $doctors = $query->orderBy('fullname')->get();

        return Excel::download(new DoctorExport($doctors), 'doctors-' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Base doctor query with designation-based geography access (MR, ABM, RBM, admin, etc.).
     * Index and export both use this so every role sees the same scoped dataset.
     */
    private function scopedDoctorsQuery(Request $request, bool $applyHeadquarterStationFilter = true): Builder
    {
        $query = Doctor::with(['headquarter', 'area', 'exstation', 'outstation', 'products'])
            ->where('company_id', company()->id);

        $accessibleHeadquarterIds = $this->accessibleHeadquarterIds();
        $accessibleAreaIds = $this->accessibleAreaIds();
        $accessibleStationIds = $this->accessibleStations();

        if (! user()->hasAdminLikeAccess() && $accessibleHeadquarterIds !== null) {
            $this->applyCustomerGeoScope(
                $query,
                $accessibleHeadquarterIds,
                $accessibleAreaIds ?? [],
                $accessibleStationIds
            );
        }

        if ($applyHeadquarterStationFilter) {
            $this->applyDoctorsHeadquarterStationFilter($query, $request, $accessibleHeadquarterIds);
        }

        return $query;
    }

    /**
     * Inline filters from the doctors index table (search, HQ, qualification, speciality).
     * Mirrors the client-side applyDoctorsTableFilters() logic.
     */
    private function applyDoctorsInlineListFilters(Builder $query, Request $request): void
    {
        $accessibleHeadquarterIds = $this->accessibleHeadquarterIds();

        $requestedHqId = $request->input('headquarter_id');
        if ($requestedHqId && $requestedHqId !== 'all') {
            $hqId = (int) $requestedHqId;

            if (! user()->hasAdminLikeAccess()
                && is_array($accessibleHeadquarterIds)
                && ! in_array($hqId, $accessibleHeadquarterIds, true)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('headquarter_id', $hqId);
            }
        }

        $qualification = trim((string) $request->input('qualification'));
        if ($qualification !== '') {
            $query->whereRaw('LOWER(TRIM(qualification)) = ?', [strtolower($qualification)]);
        }

        $speciality = trim((string) $request->input('speciality'));
        if ($speciality !== '') {
            $query->whereRaw('LOWER(TRIM(speciality)) = ?', [strtolower($speciality)]);
        }

        $search = trim((string) $request->input('search'));
        if ($search !== '') {
            $term = '%' . strtolower($search) . '%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(fullname) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(COALESCE(email, \'\')) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(COALESCE(mobile, \'\')) LIKE ?', [$term]);
            });
        }
    }

    /**
     * URL / server-side headquarter + station filter (includes ex/out stations linked to HQ).
     */
    private function applyDoctorsHeadquarterStationFilter(Builder $query, Request $request, ?array $accessibleHeadquarterIds): void
    {
        $requestedHqId = $request->input('headquarter_id');

        if ($requestedHqId && $requestedHqId != 'all'
            && ! user()->hasAdminLikeAccess()
            && is_array($accessibleHeadquarterIds)
            && ! in_array((int) $requestedHqId, $accessibleHeadquarterIds, true)) {
            $requestedHqId = null;
        }

        if (! $requestedHqId || $requestedHqId == 'all') {
            return;
        }

        $hqId = $requestedHqId;
        $hq = PharmaHeadquarter::with(['exstations', 'outstations'])->find($hqId);
        $exstationIds = $hq ? $hq->exstations->pluck('id')->toArray() : [];
        $outstationIds = $hq ? $hq->outstations->pluck('id')->toArray() : [];

        if ($request->has('station') && $request->station != 'all') {
            $station = $request->station;

            if ($station == 'hq') {
                $query->where('headquarter_id', $hqId)
                    ->whereNull('exstation_id')
                    ->whereNull('outstation_id');
            } elseif (strpos($station, 'ex-') === 0) {
                $query->where('exstation_id', str_replace('ex-', '', $station));
            } elseif (strpos($station, 'out-') === 0) {
                $query->where('outstation_id', str_replace('out-', '', $station));
            }
        } else {
            $query->where(function ($q) use ($hqId, $exstationIds, $outstationIds) {
                $q->where('headquarter_id', $hqId);

                if (! empty($exstationIds)) {
                    $q->orWhereIn('exstation_id', $exstationIds);
                }

                if (! empty($outstationIds)) {
                    $q->orWhereIn('outstation_id', $outstationIds);
                }
            });
        }
    }

    public function create()
    {
        $this->addPermission = user()->permission('add_doctors');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $this->prepareAreaData();
        
        // Get existing doctor types for suggestions
        $doctorTypes = [];
        try {
            $companyId = company() ? company()->id : null;
            if ($companyId) {
                $doctorTypes = Doctor::where('company_id', $companyId)
                    ->whereNotNull('doctor_type')
                    ->distinct()
                    ->pluck('doctor_type')
                    ->filter()
                    ->toArray();
            }
        } catch (\Exception $e) {
            $doctorTypes = [];
        }
        
        // Add default types if not present
        if (!in_array('VIP', $doctorTypes)) {
            $doctorTypes[] = 'VIP';
        }
        if (!in_array('CORE', $doctorTypes)) {
            $doctorTypes[] = 'CORE';
        }
        sort($doctorTypes);
        
        // Add to data array for view
        $this->data['doctorTypes'] = $doctorTypes;
        
        // Load products for selection
        $this->products = Product::where('company_id', company()->id)->orderBy('name')->get();
        $this->data['products'] = $this->products;

        if (request()->ajax()) {
            $html = view('doctors.ajax.create', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('doctors.create', $this->data);
    }

    public function store(Request $request)
    {
        $this->addPermission = user()->permission('add_doctors');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:20',
            'headquarter_id' => 'required|exists:pharma_headquarters,id',
            'doctor_type' => 'nullable|string|max:50',
            'station_type' => 'nullable|in:headquarter,exstation,outstation',
            'exstation_id' => 'nullable|required_if:station_type,exstation|exists:pharma_exstations,id',
            'outstation_id' => 'nullable|required_if:station_type,outstation|exists:pharma_outstations,id',
            'msl_number' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                if ($value && $this->mslNumberExists($value)) {
                    $fail('The MSL number already exists in the database.');
                }
            }],
        ]);

        $this->assertHeadquarterAccessible((int) $request->headquarter_id);

        // Auto-determine area_id from headquarter
        $headquarter = PharmaHeadquarter::find($request->headquarter_id);
        
        $doctor = new Doctor();
        $doctor->company_id = company()->id;
        $doctor->fullname = $request->fullname;
        $doctor->email = $request->email;
        $doctor->qualification = $request->qualification;
        $doctor->speciality = $request->speciality;
        $doctor->mobile = $request->mobile;
        $doctor->dob = $request->dob ? \Carbon\Carbon::parse($request->dob)->format('Y-m-d') : null;
        $doctor->dom = $request->dom ? \Carbon\Carbon::parse($request->dom)->format('Y-m-d') : null;
        $doctor->gender = $request->gender;
        $doctor->doctor_type = $request->doctor_type;
        $doctor->address = $request->address;
        $doctor->msl_number = $request->msl_number;
        $doctor->latitude = $request->filled('latitude') ? (float) $request->latitude : null;
        $doctor->longitude = $request->filled('longitude') ? (float) $request->longitude : null;

        // Auto-populate area_id from headquarter (backend mapping)
        $doctor->headquarter_id = $request->headquarter_id;
        $doctor->area_id = $headquarter ? $headquarter->area_id : null;
        
        // Handle station selection (EITHER ex-station OR out-station, not both)
        $stationType = $request->station_type ?? 'headquarter';

        if ($stationType === 'exstation') {
            $this->ensureStationAccessible((int) $request->headquarter_id, 'exstation', $request->exstation_id);
            $doctor->exstation_id = $request->exstation_id;
            $doctor->outstation_id = null;
        } elseif ($stationType === 'outstation') {
            $this->ensureStationAccessible((int) $request->headquarter_id, 'outstation', $request->outstation_id);
            $doctor->outstation_id = $request->outstation_id;
            $doctor->exstation_id = null;
        } else {
            $doctor->exstation_id = null;
            $doctor->outstation_id = null;
        }

        if ($request->hasFile('doctor_pic')) {
            $doctor->doctor_pic = Files::uploadLocalOrS3($request->doctor_pic, 'doctors');
        }

        $doctor->save();

        // Sync products
        if ($request->has('products')) {
            $doctor->products()->sync($request->products);
        } else {
            $doctor->products()->sync([]);
        }

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('doctors.index')]);
    }

    public function show($id)
    {
        $this->doctor = Doctor::with(['headquarter', 'area', 'exstation', 'outstation', 'products'])->findOrFail($id);
        $this->viewPermission = user()->permission('view_doctors');
        abort_403(!in_array($this->viewPermission, ['all', 'added', 'owned', 'both']));

        $this->pageTitle = $this->doctor->fullname;

        if (request()->ajax()) {
            $html = view('doctors.ajax.show', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('doctors.show', $this->data);
    }

    public function edit($id)
    {
        $this->doctor = Doctor::findOrFail($id);
        $this->editPermission = user()->permission('edit_doctors');
        abort_403(!in_array($this->editPermission, ['all', 'added', 'owned', 'both']));

        $this->assertHeadquarterAccessible((int) $this->doctor->headquarter_id);
        $this->prepareAreaData((int) $this->doctor->headquarter_id);
        
        // Get existing doctor types for suggestions
        $doctorTypes = [];
        try {
            $companyId = company() ? company()->id : null;
            if ($companyId) {
                $doctorTypes = Doctor::where('company_id', $companyId)
                    ->whereNotNull('doctor_type')
                    ->distinct()
                    ->pluck('doctor_type')
                    ->filter()
                    ->toArray();
            }
        } catch (\Exception $e) {
            $doctorTypes = [];
        }
        
        // Add default types if not present
        if (!in_array('VIP', $doctorTypes)) {
            $doctorTypes[] = 'VIP';
        }
        if (!in_array('CORE', $doctorTypes)) {
            $doctorTypes[] = 'CORE';
        }
        sort($doctorTypes);
        
        // Add to data array for view
        $this->data['doctorTypes'] = $doctorTypes;
        
        // Load products for selection
        $this->products = Product::where('company_id', company()->id)->orderBy('name')->get();
        $this->data['products'] = $this->products;
        
        // Load doctor's current products
        $this->doctor->load('products');
        $this->data['selectedProducts'] = $this->doctor->products->pluck('id')->toArray();

        if (request()->ajax()) {
            $html = view('doctors.ajax.edit', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('doctors.edit', $this->data);
    }

    public function update(Request $request, $id)
    {
        $doctor = Doctor::findOrFail($id);
        $this->editPermission = user()->permission('edit_doctors');
        abort_403(!in_array($this->editPermission, ['all', 'added', 'owned', 'both']));

        $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:20',
            'headquarter_id' => 'required|exists:pharma_headquarters,id',
            'doctor_type' => 'nullable|string|max:50',
            'station_type' => 'nullable|in:headquarter,exstation,outstation',
            'exstation_id' => 'nullable|required_if:station_type,exstation|exists:pharma_exstations,id',
            'outstation_id' => 'nullable|required_if:station_type,outstation|exists:pharma_outstations,id',
            'msl_number' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) use ($id) {
                if ($value && $this->mslNumberExists($value, $id, 'doctors')) {
                    $fail('The MSL number already exists in the database.');
                }
            }],
        ]);

        $this->assertHeadquarterAccessible((int) $request->headquarter_id);

        $doctor->fullname = $request->fullname;
        $doctor->email = $request->email;
        $doctor->qualification = $request->qualification;
        $doctor->speciality = $request->speciality;
        $doctor->mobile = $request->mobile;
        $doctor->dob = $request->dob ? \Carbon\Carbon::parse($request->dob)->format('Y-m-d') : null;
        $doctor->dom = $request->dom ? \Carbon\Carbon::parse($request->dom)->format('Y-m-d') : null;
        $doctor->area = $request->area;
        $doctor->gender = $request->gender;
        $doctor->doctor_type = $request->doctor_type;
        $doctor->address = $request->address;
        $doctor->msl_number = $request->msl_number;
        $doctor->latitude = $request->filled('latitude') ? (float) $request->latitude : null;
        $doctor->longitude = $request->filled('longitude') ? (float) $request->longitude : null;

        // Update headquarter and area mapping
        $headquarter = PharmaHeadquarter::find($request->headquarter_id);
        $doctor->headquarter_id = $request->headquarter_id;
        $doctor->area_id = $headquarter ? $headquarter->area_id : null;

        // Update station assignments
        $stationType = $request->station_type ?? 'headquarter';

        if ($stationType === 'exstation') {
            $this->ensureStationAccessible((int) $request->headquarter_id, 'exstation', $request->exstation_id);
            $doctor->exstation_id = $request->exstation_id;
            $doctor->outstation_id = null;
        } elseif ($stationType === 'outstation') {
            $this->ensureStationAccessible((int) $request->headquarter_id, 'outstation', $request->outstation_id);
            $doctor->outstation_id = $request->outstation_id;
            $doctor->exstation_id = null;
        } else {
            $doctor->exstation_id = null;
            $doctor->outstation_id = null;
        }

        if ($request->hasFile('doctor_pic')) {
            Files::deleteFile($doctor->doctor_pic, 'doctors');
            $doctor->doctor_pic = Files::uploadLocalOrS3($request->doctor_pic, 'doctors');
        }

        $doctor->save();

        // Sync products
        if ($request->has('products')) {
            $doctor->products()->sync($request->products);
        } else {
            $doctor->products()->sync([]);
        }

        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('doctors.index')]);
    }

    public function destroy($id)
    {
        $doctor = Doctor::findOrFail($id);
        $this->deletePermission = user()->permission('delete_doctors');
        abort_403(!in_array($this->deletePermission, ['all', 'added', 'owned', 'both']));

        $this->assertHeadquarterAccessible((int) $doctor->headquarter_id);
        Files::deleteFile($doctor->doctor_pic, 'doctors');
        $doctor->delete();

        return Reply::success(__('messages.deleteSuccess'));
    }

    private function assertHeadquarterAccessible(int $headquarterId): void
    {
        $accessibleIds = $this->accessibleHeadquarterIds();

        if ($accessibleIds === null) {
            if (user()->hasAdminLikeAccess() || ! $this->shouldRestrictNullHeadquarterScopeToDirectHq()) {
                return;
            }

            $employeeHeadquarterIds = $this->employeeAssignedHeadquarterIds(user());

            if (empty($employeeHeadquarterIds)) {
                return;
            }

            $accessibleIds = $employeeHeadquarterIds;
        }

        abort_403(empty($accessibleIds) || !in_array($headquarterId, $accessibleIds, true), __('messages.permissionDenied'));
    }

    private function employeeAssignedHeadquarterIds($user): array
    {
        $employee = $user->employeeDetail ?? $user->employeeDetails;

        if (! $employee) {
            return [];
        }

        return collect([
            $employee->headquarter_id ?? null,
            $employee->pharma_headquarter_id ?? null,
        ])->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();
    }

    private function stationIdsForHeadquarters(array $headquarterIds): array
    {
        if (empty($headquarterIds)) {
            return ['exstation' => [], 'outstation' => []];
        }

        $assignments = PharmaHeadquarterAssign::whereIn('headquarter_id', $headquarterIds)
            ->where('company_id', company()->id)
            ->get();

        return [
            'exstation' => $assignments->where('station', 'exstation')->pluck('station_id')->map(fn ($id) => (int) $id)->unique()->values()->toArray(),
            'outstation' => $assignments->where('station', 'outstation')->pluck('station_id')->map(fn ($id) => (int) $id)->unique()->values()->toArray(),
        ];
    }

    private function allowedHeadquarterIdsForImport(): ?array
    {
        $allowedHqIds = $this->accessibleHeadquarterIds();

        if (! user()->hasAdminLikeAccess() && $allowedHqIds === null && $this->shouldRestrictNullHeadquarterScopeToDirectHq()) {
            $employeeHeadquarterIds = $this->employeeAssignedHeadquarterIds(user());

            if (! empty($employeeHeadquarterIds)) {
                return $employeeHeadquarterIds;
            }
        }

        return $allowedHqIds;
    }

    private function shouldRestrictNullHeadquarterScopeToDirectHq(): bool
    {
        if (user()->hasRole('hr') || user()->hasRole('pmt') || user()->hasRole('sales-manager')) {
            return false;
        }

        $employee = user()->employeeDetail ?? user()->employeeDetails;

        if ($employee && $employee->designation && \App\Helpers\PharmaDesignationHelper::isMISExecutive($employee->designation)) {
            return false;
        }

        return ! empty($this->employeeAssignedHeadquarterIds(user()));
    }

    /**
     * Check if MSL number exists in doctors, chemists, or stockists tables
     * 
     * @param string $mslNumber
     * @param int|null $excludeId ID to exclude from check (for updates)
     * @param string|null $excludeTable Table to exclude from check (for updates)
     * @return bool
     */
    private function mslNumberExists(string $mslNumber, ?int $excludeId = null, ?string $excludeTable = null): bool
    {
        // Check doctors table
        if ($excludeTable !== 'doctors') {
            $doctorQuery = Doctor::where('msl_number', $mslNumber)->where('company_id', company()->id);
            if ($excludeId && $excludeTable === 'doctors') {
                $doctorQuery->where('id', '!=', $excludeId);
            }
            if ($doctorQuery->exists()) {
                return true;
            }
        }

        // Check chemists table
        if ($excludeTable !== 'chemists') {
            $chemistQuery = Chemist::where('msl_number', $mslNumber)->where('company_id', company()->id);
            if ($excludeId && $excludeTable === 'chemists') {
                $chemistQuery->where('id', '!=', $excludeId);
            }
            if ($chemistQuery->exists()) {
                return true;
            }
        }

        // Check stockists table
        if ($excludeTable !== 'stockists') {
            $stockistQuery = Stockist::where('msl_number', $mslNumber)->where('company_id', company()->id);
            if ($excludeId && $excludeTable === 'stockists') {
                $stockistQuery->where('id', '!=', $excludeId);
            }
            if ($stockistQuery->exists()) {
                return true;
            }
        }

        return false;
    }

    private function prepareAreaData(?int $currentHeadquarterId = null): void
    {
        $headquarters = $this->accessibleHeadquartersCollection();

        if ($currentHeadquarterId) {
            $this->assertHeadquarterAccessible($currentHeadquarterId);
        }

        if ($headquarters->isEmpty()) {
            abort_403(!user()->hasAdminLikeAccess(), __('messages.permissionDenied'));
        }

        $this->headquarters = $headquarters;
        $this->headquarterStations = $this->formatHeadquarterStations($headquarters);
        $this->defaultHeadquarterId = $this->determineDefaultHeadquarterId($headquarters, $currentHeadquarterId);
    }

    private function accessibleHeadquartersCollection(): Collection
    {
        $query = PharmaHeadquarter::with(['area', 'exstations', 'outstations'])->orderBy('name');
        $accessibleIds = $this->accessibleHeadquarterIds();

        if ($accessibleIds === null) {
            return $query->get();
        }

        if (! empty($accessibleIds)) {
            return $query->whereIn('id', $accessibleIds)->get();
        }

        $areaIds = $this->accessibleAreaIds();
        if ($areaIds !== null && ! empty($areaIds)) {
            return $query->where('company_id', company()->id)->whereIn('area_id', $areaIds)->get();
        }

        return collect();
    }

    private function formatHeadquarterStations(Collection $headquarters): array
    {
        return $headquarters->mapWithKeys(function ($headquarter) {
            return [
                $headquarter->id => [
                    'exstations' => $headquarter->exstations->map(fn($station) => [
                        'id' => $station->id,
                        'name' => $station->name,
                    ])->values(),
                    'outstations' => $headquarter->outstations->map(fn($station) => [
                        'id' => $station->id,
                        'name' => $station->name,
                    ])->values(),
                ],
            ];
        })->toArray();
    }

    private function determineDefaultHeadquarterId(Collection $headquarters, ?int $preferredId = null): ?int
    {
        if ($preferredId) {
            return $preferredId;
        }

        return $headquarters->count() === 1 ? optional($headquarters->first())->id : null;
    }

    private function ensureStationAccessible(int $headquarterId, string $stationType, $stationId): void
    {
        if (!$stationId || user()->hasAdminLikeAccess()) {
            return;
        }

        $stationId = (int) $stationId;

        $allowedStationIds = PharmaHeadquarterAssign::where('headquarter_id', $headquarterId)
            ->where('station', $stationType)
            ->pluck('station_id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        abort_403(!in_array($stationId, $allowedStationIds, true), __('messages.permissionDenied'));
    }

    /**
     * Show the import form
     *
     * @return \Illuminate\Http\Response
     */
    public function importDoctor()
    {
        $this->pageTitle = __('app.importExcel') . ' ' . __('Doctors');

        $this->addPermission = user()->permission('add_doctors');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $this->view = 'doctors.ajax.import';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('doctors.import', $this->data);
    }

    /**
     * Process the import file upload
     *
     * @param ImportRequest $request
     * @return \Illuminate\Http\Response
     */
    public function importStore(ImportRequest $request)
    {
        try {
            $rvalue = $this->importDoctorFileProcess($request, DoctorImport::class);

            if ($rvalue === 'abort') {
                if (!empty($this->file)) {
                    Files::deleteFile($this->file, Files::IMPORT_FOLDER);
                }

                return Reply::error(__('messages.abortAction'));
            }

            $view = view('doctors.ajax.import_mapping', $this->data)->render();

            return Reply::successWithData(__('messages.importUploadSuccess'), ['view' => $view]);
        } catch (\Exception $e) {
            \Log::error('Import error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            if (!empty($this->file)) {
                try {
                    Files::deleteFile($this->file, Files::IMPORT_FOLDER);
                } catch (\Exception $deleteError) {
                    // Ignore delete errors
                }
            }
            $errorMessage = config('app.debug') ? $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() : 'Import failed. Please check the file format and try again.';
            return Reply::error($errorMessage);
        } catch (\Throwable $e) {
            \Log::error('Import fatal error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            if (!empty($this->file)) {
                try {
                    Files::deleteFile($this->file, Files::IMPORT_FOLDER);
                } catch (\Exception $deleteError) {
                    // Ignore delete errors
                }
            }
            $errorMessage = config('app.debug') ? $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() : 'Import failed. Please check the file format and try again.';
            return Reply::error($errorMessage);
        }
    }

    /**
     * Run import after user confirms column mapping.
     */
    public function importProcess(ImportProcessRequest $request)
    {
        try {
            $this->importClassName = 'DoctorImport';
            $filePath = public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $request->file);

            if (!file_exists($filePath)) {
                return Reply::error('Import file not found. Please upload again.');
            }

            $excelData = $this->readExcelPreserveColumnIndices($filePath);

            if ($request->boolean('has_heading', true) && !empty($excelData)) {
                array_shift($excelData);
            }

            $excelData = DoctorImport::filterBlankRows($excelData);

            $columns = array_filter($request->columns ?? [], static function ($value) {
                return $value !== null && $value !== '';
            });

            if (empty($columns)) {
                return Reply::error('Please map at least Dr. Name and HQ columns.');
            }

            $newStationWarnings = $this->newStationWarnings($excelData, $columns);
            if (!empty($newStationWarnings) && !$request->boolean('confirm_new_stations')) {
                return Reply::dataOnly([
                    'status' => 'confirm_station_spellings',
                    'message' => 'These station names are not assigned yet and will be created automatically. Please check spelling before continuing.',
                    'stations' => $newStationWarnings,
                ]);
            }

            $allowedHqIds = $this->allowedHeadquarterIdsForImport();
            $batch = $this->importJobProcessDirect(
                $excelData,
                $columns,
                $request->file,
                DoctorImport::class,
                ImportDoctorJob::class,
                $allowedHqIds
            );

            if (!$batch) {
                return Reply::error('Failed to start import.');
            }

            return Reply::successWithData(__('messages.importProcessStart'), ['batch' => $batch]);
        } catch (\Exception $e) {
            \Log::error('Doctor import process error: ' . $e->getMessage());

            return Reply::error(config('app.debug') ? $e->getMessage() : 'Import failed. Please try again.');
        }
    }

    /**
     * Download sample import file
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadSample()
    {
        try {
            return Excel::download(new DoctorSampleExport, 'doctors-sample-import.xlsx');
        } catch (\Exception $e) {
            \Log::error('Doctor sample export error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return Reply::error('Error generating sample file: ' . $e->getMessage());
        }
    }

    private function newStationWarnings(array $excelData, array $columns): array
    {
        $companyId = company()->id;
        $headquarters = PharmaHeadquarter::with(['exstations', 'outstations'])
            ->where('company_id', $companyId)
            ->get()
            ->keyBy(fn ($headquarter) => ImportDoctorJob::normalizeGeoName($headquarter->name));

        $warnings = [];

        foreach ($excelData as $rowIndex => $row) {
            $headquarterName = $this->importColumnValue($row, $columns, 'headquarter');
            $stationName = $this->importColumnValue($row, $columns, 'station');
            $stationType = ImportDoctorJob::normalizeStationType($this->importColumnValue($row, $columns, 'station_type'));

            if (!in_array($stationType, ['exstation', 'outstation'], true) || $stationName === '') {
                continue;
            }

            $headquarter = $this->findImportGeoMatch($headquarters, $headquarterName);
            if (!$headquarter) {
                continue;
            }

            if (ImportDoctorJob::normalizeGeoName($stationName) === ImportDoctorJob::normalizeGeoName($headquarter->name)) {
                continue;
            }

            $stations = $stationType === 'exstation' ? $headquarter->exstations : $headquarter->outstations;
            if ($this->findImportGeoMatch($stations, $stationName)) {
                continue;
            }

            $key = $headquarter->id . '|' . $stationType . '|' . ImportDoctorJob::normalizeGeoName($stationName);
            $warnings[$key] = [
                'row' => $rowIndex + 2,
                'headquarter' => $headquarter->name,
                'station_type' => $stationType === 'exstation' ? 'Ex-Station' : 'Outstation',
                'station' => $stationName,
            ];
        }

        return array_values($warnings);
    }

    private function importColumnValue(array $row, array $columns, string $field): string
    {
        $indices = array_keys($columns, $field, true);
        if (empty($indices)) {
            return '';
        }

        $value = $row[(int) min($indices)] ?? '';

        return trim((string) $value);
    }

    private function findImportGeoMatch($items, string $name)
    {
        $normalizedName = ImportDoctorJob::normalizeGeoName($name);
        if ($normalizedName === '') {
            return null;
        }

        foreach ($items as $item) {
            $candidate = ImportDoctorJob::normalizeGeoName($item->name ?? '');
            if ($candidate === $normalizedName || str_starts_with($candidate, $normalizedName) || str_starts_with($normalizedName, $candidate)) {
                return $item;
            }

            similar_text($normalizedName, $candidate, $score);
            if ($score >= 85) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Preview duplicate doctor groups (same mobile last 10 digits, or same name + headquarter if mobile missing).
     */
    public function mergeDuplicates()
    {
        $this->editPermission = user()->permission('edit_doctors');
        abort_403(! in_array($this->editPermission, ['all', 'added']));

        $service = new DoctorDuplicateMergeService;
        $groups = $service->findDuplicateGroups($this->doctorsQueryForMerge());

        $this->data['duplicateGroups'] = $groups->map(function ($group) use ($service) {
            $sorted = $group->sortBy(function ($d) use ($service) {
                return [-$service->completenessScore($d), $d->id];
            })->values();
            $winner = $sorted->first();
            $scores = $sorted->mapWithKeys(fn ($d) => [$d->id => $service->completenessScore($d)]);

            return [
                'winner' => $winner,
                'winner_score' => $scores[$winner->id] ?? 0,
                'doctors' => $sorted,
                'scores' => $scores,
            ];
        });

        $this->pageTitle = 'Merge duplicate doctors';

        return view('doctors.merge-duplicates', $this->data);
    }

    /**
     * Merge all duplicate groups in scope: keep best-filled record, merge products/SFC/DCR links, soft-delete others.
     */
    public function mergeDuplicatesRun(Request $request)
    {
        $this->editPermission = user()->permission('edit_doctors');
        abort_403(! in_array($this->editPermission, ['all', 'added']));

        $request->validate([
            'confirm_merge' => 'required|accepted',
        ]);

        $service = new DoctorDuplicateMergeService;
        $groups = $service->findDuplicateGroups($this->doctorsQueryForMerge());

        if ($groups->isEmpty()) {
            return redirect()
                ->route('doctors.merge-duplicates')
                ->with('success', __('No duplicate groups found for your access scope.'));
        }

        $stats = $service->mergeAllGroups($groups);

        return redirect()
            ->route('doctors.index')
            ->with('success', __('Merged :groups duplicate group(s). Removed :removed duplicate doctor record(s).', [
                'groups' => $stats['groups_merged'],
                'removed' => $stats['doctors_removed'],
            ]));
    }

    /**
     * Doctors visible to current user (same geographic scope as list).
     */
    protected function doctorsQueryForMerge(): Builder
    {
        $query = Doctor::with(['headquarter', 'area', 'exstation', 'outstation'])
            ->where('company_id', company()->id);

        if (! user()->hasAdminLikeAccess()) {
            $accessibleHeadquarterIds = $this->accessibleHeadquarterIds();
            $accessibleAreaIds = $this->accessibleAreaIds();
            $accessibleStationIds = $this->accessibleStations();
            if ($accessibleHeadquarterIds !== null) {
                $this->applyCustomerGeoScope(
                    $query,
                    $accessibleHeadquarterIds,
                    $accessibleAreaIds ?? [],
                    $accessibleStationIds
                );
            }
        }

        return $query;
    }
}
