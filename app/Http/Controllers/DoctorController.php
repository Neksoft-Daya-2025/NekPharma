<?php

namespace App\Http\Controllers;

use App\Helper\Files;
use App\Helper\Reply;
use App\Models\Doctor;
use App\Models\Product;
use App\Models\PharmaHeadquarter;
use App\Models\PharmaHeadquarterAssign;
use App\Imports\DoctorImport;
use App\Jobs\ImportDoctorJob;
use App\Exports\DoctorSampleExport;
use App\Traits\ImportExcel;
use App\Http\Requests\Admin\Employee\ImportRequest;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DoctorController extends AccountBaseController
{
    use ImportExcel;

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

        // Build query with relationships
        $query = Doctor::with(['headquarter', 'area', 'exstation', 'outstation', 'products'])
            ->where('company_id', company()->id);

        $accessibleHeadquarterIds = $this->accessibleHeadquarterIds();

        $accessibleStationIds = $this->accessibleStations();

        // Only apply headquarter filtering if user is NOT admin
        // Admins should see all doctors regardless of headquarter restrictions
        if (!user()->hasRole('admin') && $accessibleHeadquarterIds !== null) {
            if (empty($accessibleHeadquarterIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($q) use ($accessibleHeadquarterIds, $accessibleStationIds) {
                    $q->whereIn('headquarter_id', $accessibleHeadquarterIds);

                    if (!empty($accessibleStationIds['exstation'])) {
                        $q->orWhereIn('exstation_id', $accessibleStationIds['exstation']);
                    }

                    if (!empty($accessibleStationIds['outstation'])) {
                        $q->orWhereIn('outstation_id', $accessibleStationIds['outstation']);
                    }
                });
            }
        }

        // Filter by headquarter
        if ($request->has('headquarter_id') && $request->headquarter_id != 'all') {
            $hqId = $request->headquarter_id;
            
            // Get ex-stations and out-stations linked to this HQ
            $hq = \App\Models\PharmaHeadquarter::with(['exstations', 'outstations'])->find($hqId);
            $exstationIds = $hq ? $hq->exstations->pluck('id')->toArray() : [];
            $outstationIds = $hq ? $hq->outstations->pluck('id')->toArray() : [];
            
            // Second filter: Station (specific station or all)
            if ($request->has('station') && $request->station != 'all') {
                $station = $request->station;
                
                if ($station == 'hq') {
                    // Show only doctors at Headquarter
                    $query->where('headquarter_id', $hqId)
                          ->whereNull('exstation_id')
                          ->whereNull('outstation_id');
                          
                } elseif (strpos($station, 'ex-') === 0) {
                    // Show doctors at specific Ex-Station
                    $exstationId = str_replace('ex-', '', $station);
                    $query->where('exstation_id', $exstationId);
                    
                } elseif (strpos($station, 'out-') === 0) {
                    // Show doctors at specific Out-Station
                    $outstationId = str_replace('out-', '', $station);
                    $query->where('outstation_id', $outstationId);
                }
            } else {
                // No station filter - show ALL doctors linked to this HQ
                $query->where(function($q) use ($hqId, $exstationIds, $outstationIds) {
                    $q->where('headquarter_id', $hqId);
                    
                    if (!empty($exstationIds)) {
                        $q->orWhereIn('exstation_id', $exstationIds);
                    }
                    
                    if (!empty($outstationIds)) {
                        $q->orWhereIn('outstation_id', $outstationIds);
                    }
                });
            }
        }

        $headquarters = $this->accessibleHeadquartersCollection();

        // Add ordering and pagination for better performance
        $this->doctors = $query->orderBy('fullname')->paginate(50);
        $this->headquarters = $headquarters;
        $this->headquarterStations = $this->formatHeadquarterStations($headquarters);
        $this->defaultHeadquarterId = $this->determineDefaultHeadquarterId($headquarters, request()->get('headquarter_id'));
        
        return view('doctors.index', $this->data);
    }

    private function accessibleStations(): array
    {
        $headquarterIds = $this->accessibleHeadquarterIds();

        if ($headquarterIds === null) {
            return [
                'exstation' => PharmaHeadquarterAssign::where('station', 'exstation')->pluck('station_id')->map(fn($id) => (int) $id)->toArray(),
                'outstation' => PharmaHeadquarterAssign::where('station', 'outstation')->pluck('station_id')->map(fn($id) => (int) $id)->toArray(),
            ];
        }

        if (empty($headquarterIds)) {
            return ['exstation' => [], 'outstation' => []];
        }

        $assignments = PharmaHeadquarterAssign::whereIn('headquarter_id', $headquarterIds)->get();

        return [
            'exstation' => $assignments->where('station', 'exstation')->pluck('station_id')->map(fn($id) => (int) $id)->toArray(),
            'outstation' => $assignments->where('station', 'outstation')->pluck('station_id')->map(fn($id) => (int) $id)->toArray(),
        ];
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

        Files::deleteFile($doctor->doctor_pic, 'doctors');
        $doctor->delete();

        return Reply::success(__('messages.deleteSuccess'));
    }

    private function accessibleHeadquarterIds(): ?array
    {
        if (user()->hasRole('admin')) {
            return null;
        }

        $headquarterId = optional(user()->employeeDetail)->headquarter_id;

        return $headquarterId ? [(int) $headquarterId] : [];
    }

    private function assertHeadquarterAccessible(int $headquarterId): void
    {
        $accessibleIds = $this->accessibleHeadquarterIds();

        if ($accessibleIds === null) {
            return;
        }

        abort_403(empty($accessibleIds) || !in_array($headquarterId, $accessibleIds, true), __('messages.permissionDenied'));
    }

    private function prepareAreaData(?int $currentHeadquarterId = null): void
    {
        $headquarters = $this->accessibleHeadquartersCollection();

        if ($currentHeadquarterId) {
            $this->assertHeadquarterAccessible($currentHeadquarterId);
        }

        if ($headquarters->isEmpty()) {
            abort_403(!user()->hasRole('admin'), __('messages.permissionDenied'));
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

        if (empty($accessibleIds)) {
            return collect();
        }

        return $query->whereIn('id', $accessibleIds)->get();
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
        if (!$stationId || user()->hasRole('admin')) {
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
            // Direct import without mapping step
            $this->importClassName = 'DoctorImport';
            $uploadedFile = Files::upload($request->import_file, Files::IMPORT_FOLDER);
            $filePath = public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $uploadedFile);

            if (!file_exists($filePath)) {
                return Reply::error('File not found after upload');
            }

            $importInstance = new DoctorImport;
            Excel::import($importInstance, $filePath);
            $excelData = $importInstance->getProcessedData();
            
            // Ensure we have data - if empty, try reading directly
            if (empty($excelData) || !is_array($excelData)) {
                $importInstance2 = new DoctorImport;
                Excel::import($importInstance2, $filePath);
                $excelData = $importInstance2->getProcessedData();
            }
            
            if (!is_array($excelData) || empty($excelData)) {
                Files::deleteFile($uploadedFile, Files::IMPORT_FOLDER);
                return Reply::error('No data found in the file');
            }
            
            if ($request->has('heading')) {
                array_shift($excelData);
            }

            // Check if data is empty after removing header
            $isDataNull = true;
            foreach ($excelData as $rowitem) {
                if (is_array($rowitem) && array_filter($rowitem)) {
                    $isDataNull = false;
                    break;
                }
            }

            if ($isDataNull || empty($excelData)) {
                Files::deleteFile($uploadedFile, Files::IMPORT_FOLDER);
                return Reply::error(__('messages.abortAction'));
            }

            // Auto-map columns based on headers
            $columns = array();
            $hasHeading = $request->has('heading');
            
            if ($hasHeading) {
                try {
                    $headingData = (new HeadingRowImport)->toArray($filePath);
                    if (isset($headingData[0][0]) && is_array($headingData[0][0])) {
                        $heading = $headingData[0][0];
                    } else {
                        $heading = [];
                    }
                } catch (\Exception $e) {
                    $heading = [];
                }
                
                if (!empty($heading)) {
                    \Log::info('Excel headings found: ' . json_encode($heading));
                    $importColumns = DoctorImport::fields();
                    
                    // Normalize headings for matching
                    $normalizedHeadings = array_map(function($h) {
                        return strtolower(trim(preg_replace('/[^a-z0-9]/', '', (string)$h)));
                    }, $heading);
                    \Log::info('Normalized headings: ' . json_encode($normalizedHeadings));
                    
                    // Create auto-mapping with priority: exact matches first, then partial matches
                    foreach ($heading as $index => $headingValue) {
                        if (!isset($normalizedHeadings[$index])) continue;
                        
                        $normalizedHeading = $normalizedHeadings[$index];
                        $bestMatch = null;
                        $bestMatchScore = 0;
                        
                        foreach ($importColumns as $column) {
                            $columnId = strtolower(trim(preg_replace('/[^a-z0-9]/', '', (string)($column['id'] ?? ''))));
                            $columnNameValue = is_array($column['name'] ?? null) ? (string)(($column['name'][0] ?? '') ?: '') : (string)($column['name'] ?? '');
                            $columnName = strtolower(trim(preg_replace('/[^a-z0-9]/', '', $columnNameValue)));
                            
                            $columnIdWithoutUnderscore = str_replace('_', '', $columnId);
                            $columnIdParts = explode('_', $columnId);
                            
                            $score = 0;
                            
                            // Exact match gets highest priority
                            if ($normalizedHeading === $columnId || $normalizedHeading === $columnIdWithoutUnderscore) {
                                $score = 100;
                            }
                            // Column name exact match
                            elseif ($normalizedHeading === $columnName) {
                                $score = 90;
                            }
                            // Heading starts with column ID (e.g., "stationtype" starts "stationtypeheadquarter...")
                            elseif (strpos($normalizedHeading, $columnId) === 0 || strpos($normalizedHeading, $columnIdWithoutUnderscore) === 0) {
                                $score = 80;
                            }
                            // Column ID contains heading (exact substring match)
                            elseif (strpos($columnId, $normalizedHeading) === 0 || strpos($columnIdWithoutUnderscore, $normalizedHeading) === 0) {
                                $score = 70;
                            }
                            // All parts of column ID are in heading (for compound IDs like "station_type")
                            elseif (count($columnIdParts) > 1) {
                                $allPartsFound = true;
                                foreach ($columnIdParts as $part) {
                                    if (strpos($normalizedHeading, $part) === false) {
                                        $allPartsFound = false;
                                        break;
                                    }
                                }
                                if ($allPartsFound) {
                                    $score = 60;
                                }
                            }
                            // Partial match (heading contains column ID)
                            elseif (strpos($normalizedHeading, $columnId) !== false || strpos($normalizedHeading, $columnIdWithoutUnderscore) !== false) {
                                $score = 50;
                            }
                            // Column name partial match
                            elseif (strpos($normalizedHeading, $columnName) !== false || strpos($columnName, $normalizedHeading) !== false) {
                                $score = 40;
                            }
                            
                            // Keep track of best match
                            if ($score > $bestMatchScore) {
                                $bestMatchScore = $score;
                                $bestMatch = $column['id'];
                            }
                        }
                        
                        // Only map if we found a reasonable match (score >= 40)
                        if ($bestMatch && $bestMatchScore >= 40) {
                            $columns[$index] = $bestMatch;
                            \Log::info('Mapped column "' . $headingValue . '" (index ' . $index . ') to "' . $bestMatch . '" (score: ' . $bestMatchScore . ')');
                        }
                    }
                    
                    \Log::info('Column mapping result: ' . json_encode($columns));
                }
            }
            
            // If no columns mapped, use positional mapping (first 2 mandatory columns)
            if (empty($columns)) {
                $importColumns = DoctorImport::fields();
                foreach ($importColumns as $index => $column) {
                    if ($index < 2) { // Only map first 2 mandatory columns
                        $columns[$index] = $column['id'];
                    }
                }
            }

            // Process import directly
            $batch = $this->importJobProcessDirect($excelData, $columns, $uploadedFile, DoctorImport::class, ImportDoctorJob::class);

            if (!$batch) {
                Files::deleteFile($uploadedFile, Files::IMPORT_FOLDER);
                return Reply::error('Failed to create import batch');
            }

            // Prepare data for view
            $this->data['batch'] = $batch;
            $this->data['batchId'] = is_object($batch) && isset($batch->id) ? $batch->id : null;
            
            try {
                $view = view('doctors.ajax.import_progress', $this->data)->render();
            } catch (\Exception $viewError) {
                \Log::error('View render error: ' . $viewError->getMessage());
                Files::deleteFile($uploadedFile, Files::IMPORT_FOLDER);
                return Reply::error('Failed to render progress view: ' . $viewError->getMessage());
            }

            $batchId = is_object($batch) && isset($batch->id) ? $batch->id : null;
            return Reply::successWithData(__('messages.importProcessStart'), [
                'view' => $view, 
                'batchId' => $batchId
            ]);
        } catch (\Exception $e) {
            \Log::error('Import error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            \Log::error('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            if (isset($uploadedFile)) {
                try {
                    Files::deleteFile($uploadedFile, Files::IMPORT_FOLDER);
                } catch (\Exception $deleteError) {
                    // Ignore delete errors
                }
            }
            $errorMessage = config('app.debug') ? $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() : 'Import failed. Please check the file format and try again.';
            return Reply::error($errorMessage);
        } catch (\Throwable $e) {
            \Log::error('Import fatal error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            if (isset($uploadedFile)) {
                try {
                    Files::deleteFile($uploadedFile, Files::IMPORT_FOLDER);
                } catch (\Exception $deleteError) {
                    // Ignore delete errors
                }
            }
            $errorMessage = config('app.debug') ? $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() : 'Import failed. Please check the file format and try again.';
            return Reply::error($errorMessage);
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
}
