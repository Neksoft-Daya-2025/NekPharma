<?php

namespace App\Jobs;

use App\Traits\ExcelImportable;
use App\Traits\UniversalSearchTrait;
use App\Models\Doctor;
use App\Models\Chemist;
use App\Models\Stockist;
use App\Models\Product;
use App\Models\PharmaHeadquarter;
use App\Models\PharmaExstation;
use App\Models\PharmaOutstation;
use App\Models\PharmaHeadquarterAssign;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ImportDoctorJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UniversalSearchTrait;
    use ExcelImportable;

    private $rows; // Changed to handle multiple rows for chunk processing
    private $columns;
    private $company;
    private $row; // Current row being processed
    /** @var array|null null = admin (all HQs allowed), array = allowed HQ ids for non-admin */
    private $allowedHeadquarterIds;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($rows, $columns, $company = null, $allowedHeadquarterIds = null)
    {
        // Accept single row or array of rows for chunk processing
        $this->rows = is_array($rows) && isset($rows[0]) && is_array($rows[0]) ? $rows : [$rows];
        $this->columns = $columns;
        $this->company = $company;
        $this->allowedHeadquarterIds = $allowedHeadquarterIds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $companyId = $this->company?->id ?? company()->id;
        
        // Pre-load all related data once for faster lookups (major performance boost)
        // Load headquarters with their exstations and outstations relationships
        $headquarters = PharmaHeadquarter::with(['exstations', 'outstations'])
            ->where('company_id', $companyId)
            ->get()
            ->keyBy(fn ($headquarter) => self::normalizeGeoName($headquarter->name));
        
        \Log::info('Doctor import: Loaded ' . $headquarters->count() . ' headquarters: ' . $headquarters->keys()->implode(', '));
        
        // Build exstations map: headquarter_id => [stations keyed by name]
        $exstations = collect();
        foreach ($headquarters as $hq) {
            $exstations[$hq->id] = $hq->exstations->keyBy(fn ($station) => self::normalizeGeoName($station->name));
        }
        
        // Build outstations map: headquarter_id => [stations keyed by name]
        $outstations = collect();
        foreach ($headquarters as $hq) {
            $outstations[$hq->id] = $hq->outstations->keyBy(fn ($station) => self::normalizeGeoName($station->name));
        }
        
        $allProducts = Product::where('company_id', $companyId)
            ->get()
            ->keyBy('name');
        
        // Pre-load existing doctors for faster matching (no DB queries per row)
        $existingDoctorsByEmail = Doctor::where('company_id', $companyId)
            ->whereNotNull('email')
            ->get()
            ->mapWithKeys(function ($doctor) {
                $key = self::normalizeEmailForDuplicate($doctor->email);

                return $key ? [$key => $doctor] : [];
            });
        
        $existingDoctorsByMobile = Doctor::where('company_id', $companyId)
            ->whereNotNull('mobile')
            ->get()
            ->mapWithKeys(function ($doctor) {
                $key = self::normalizeMobileForDuplicate($doctor->mobile);

                return $key ? [$key => $doctor] : [];
            });
        
        $existingDoctorsByNameHq = Doctor::where('company_id', $companyId)
            ->get()
            ->groupBy(function($doctor) {
                return strtolower(preg_replace('/\s+/', ' ', trim($doctor->fullname))) . '|' . $doctor->headquarter_id;
            });
        
        // Process all rows in this chunk within a single transaction
        $newCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $duplicateNames = []; // legacy summary; duplicates are now skipped (see skipped_details)
        $skippedDetails = [];
        $errorDetails = [];

        DB::beginTransaction();
        try {
            foreach ($this->rows as $rowIndex => $rowData) {
                $this->row = $rowData;
                $rowNum = $rowIndex + 1;

                \Log::info('Processing row ' . $rowNum . ' of ' . count($this->rows));

                if (!$this->rowHasData($rowData)) {
                    continue;
                }

                // Only Dr. Name and HQ are mandatory; rest have defaults for client format (Dr. Name, HQ, Station Name, Dr. Type)
                // Collapse internal whitespace so "MUKESH  SRIVASTAVA" and "MUKESH SRIVASTAVA" are treated the same.
                $fullname = $this->isColumnExists('fullname') ? preg_replace('/\s+/', ' ', trim((string) $this->getColumnValue('fullname'))) : '';
                $headquarterName = $this->isColumnExists('headquarter') ? trim((string) $this->getColumnValue('headquarter')) : '';

                if (empty($fullname) || empty($headquarterName)) {
                    $reason = empty($fullname) && empty($headquarterName)
                        ? 'Dr. Name and HQ are required (both empty)'
                        : (empty($fullname) ? 'Dr. Name is required' : 'HQ is required');
                    $skippedDetails[] = ['row' => $rowNum, 'name' => $fullname ?: '(empty)', 'hq' => $headquarterName ?: '(empty)', 'reason' => $reason];
                    $skippedCount++;
                    continue;
                }

                $qualification = $this->isColumnExists('qualification') ? trim((string) $this->getColumnValue('qualification')) : '';
                $stationTypeRaw = $this->isColumnExists('station_type') ? trim((string) $this->getColumnValue('station_type')) : '';
                $stationType = $stationTypeRaw !== '' ? $stationTypeRaw : 'headquarter';
                $stationName = $this->isColumnExists('station') ? trim((string) $this->getColumnValue('station')) : '';
                $address = $this->isColumnExists('address') ? trim((string) $this->getColumnValue('address')) : '';
                $speciality = $this->isColumnExists('speciality') ? trim((string) $this->getColumnValue('speciality')) : '';

                // Station name required only when station type is exstation or outstation
                $stationTypeLower = strtolower($stationType);
                $stationNameRequired = !in_array($stationTypeLower, ['headquarter', 'hq']);
                if ($stationNameRequired && empty($stationName)) {
                    $skippedDetails[] = ['row' => $rowNum, 'name' => $fullname, 'hq' => $headquarterName, 'reason' => 'Station name is required for station type "' . $stationType . '"'];
                    $skippedCount++;
                    continue;
                }

                try {
                    $result = $this->processDoctorRow(
                        $fullname,
                        $qualification,
                        $headquarterName,
                        $stationType,
                        $stationName,
                        $address,
                        $speciality,
                        $companyId,
                        $headquarters,
                        $exstations,
                        $outstations,
                        $allProducts,
                        $existingDoctorsByEmail,
                        $existingDoctorsByMobile,
                        $existingDoctorsByNameHq
                    );
                    if ($result === 'skip_duplicate') {
                        $skippedCount++;
                        $skippedDetails[] = [
                            'row' => $rowNum,
                            'name' => $fullname,
                            'hq' => $headquarterName,
                            'reason' => 'Duplicate (same email, mobile, or name+HQ as existing doctor) — skipped',
                        ];
                        \Log::info('Doctor import row ' . $rowNum . ': Skipped duplicate – ' . $fullname);
                    } elseif ($result === true) {
                        $newCount++;
                        \Log::info('Doctor import row ' . $rowNum . ': New doctor added – ' . $fullname);
                    }
                } catch (\Exception $rowError) {
                    $errorCount++;
                    $errorDetails[] = ['row' => $rowNum, 'name' => $fullname, 'hq' => $headquarterName, 'reason' => $rowError->getMessage()];
                    \Log::error('Doctor import row ' . $rowNum . ' error for ' . $fullname . ': ' . $rowError->getMessage());
                    \Log::error('Stack trace: ' . $rowError->getTraceAsString());
                    continue;
                }
            }

            DB::commit();

            // Aggregate this chunk's result into cache for the UI (merge across all chunk jobs)
            $batch = $this->batch();
            $batchId = $batch ? $batch->id : null;
            if ($batchId) {
                $cacheKey = 'doctor_import_result_' . $batchId;
                $existing = Cache::get($cacheKey, [
                    'new' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0,
                    'duplicate_names' => [], 'skipped_details' => [], 'error_details' => []
                ]);
                $existing['new'] += $newCount;
                $existing['updated'] += $updatedCount;
                $existing['skipped'] += $skippedCount;
                $existing['errors'] += $errorCount;
                $existing['duplicate_names'] = array_merge($existing['duplicate_names'], $duplicateNames);
                $existing['skipped_details'] = array_merge($existing['skipped_details'], $skippedDetails);
                $existing['error_details'] = array_merge($existing['error_details'], $errorDetails);
                Cache::put($cacheKey, $existing, now()->addMinutes(10));
            }

            \Log::info('Doctor import chunk completed. New: ' . $newCount . ', Updated (already existed): ' . $updatedCount . ', Skipped: ' . $skippedCount . ', Errors: ' . $errorCount);
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('Doctor import chunk error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Process a single doctor row.
     *
     * @return bool|string true if new doctor was created, 'skip_duplicate' if already exists (not updated)
     */
    private function processDoctorRow($fullname, $qualification, $headquarterName, $stationType, $stationName, $address, $speciality, $companyId, $headquarters, $exstations, $outstations, $allProducts, $existingDoctorsByEmail, $existingDoctorsByMobile, $existingDoctorsByNameHq): bool|string
    {
        // Get headquarter from cache (no DB query)
        // Try exact match first
        $headquarter = $headquarters->get(self::normalizeGeoName($headquarterName));

        // If not found, try case-insensitive/fuzzy match
        if (!$headquarter) {
            $headquarter = $this->findGeoByName($headquarters, $headquarterName);
        }

        // If still not found, try partial match (handles truncated Excel column names)
        if (!$headquarter) {
            $trimmedName = trim($headquarterName);
            foreach ($headquarters as $hq) {
                $hqName = trim($hq->name);
                // Check if headquarter name starts with the provided name or vice versa
                if (stripos($hqName, $trimmedName) === 0 || stripos($trimmedName, $hqName) === 0) {
                    $headquarter = $hq;
                    \Log::info('Doctor import: Matched headquarter "' . $headquarterName . '" to "' . $hqName . '"');
                    break;
                }
            }
        }

        if (!$headquarter) {
            $availableHQs = $headquarters->pluck('name')->implode(', ');
            $errorMsg = 'Doctor import failed for "' . $fullname . '": Headquarter "' . $headquarterName . '" not found. Available headquarters: ' . $availableHQs;
            \Log::warning($errorMsg);
            throw new \Exception($errorMsg);
        }

        // Restrict to assigned areas: non-admin can only import into their accessible HQs
        if ($this->allowedHeadquarterIds !== null && !in_array($headquarter->id, $this->allowedHeadquarterIds, true)) {
            $msg = 'Doctor import skipped for "' . $fullname . '": Headquarter "' . $headquarterName . '" is not in your assigned areas.';
            \Log::warning($msg);
            throw new \Exception($msg);
        }

        // Get email and mobile for matching
        $email = $this->isColumnExists('email') ? trim($this->getColumnValue('email')) : null;
        $emailKey = self::normalizeEmailForDuplicate($email);
        $mobileRaw = $this->isColumnExists('mobile') ? trim($this->getColumnValue('mobile')) : null;

        $mobile = self::normalizeMobileForStorage($mobileRaw);
        $mobileKey = self::normalizeMobileForDuplicate($mobile);
        
        // Find existing doctor from cache (no DB query)
        $doctor = null;
        if (!empty($emailKey)) {
            $doctor = $existingDoctorsByEmail->get($emailKey);
        }
        
        if (!$doctor && !empty($mobileKey)) {
            $doctor = $existingDoctorsByMobile->get($mobileKey);
        }
        
        if (!$doctor) {
            $key = strtolower(preg_replace('/\s+/', ' ', trim($fullname))) . '|' . $headquarter->id;
            $doctors = $existingDoctorsByNameHq->get($key);
            $doctor = $doctors ? $doctors->first() : null;
        }

        // Skip duplicates — do not update existing doctors from file (avoids re-import overwriting data)
        if ($doctor) {
            return 'skip_duplicate';
        }

        $doctor = new Doctor();
        $doctor->company_id = $companyId;
        $doctor->fullname = $fullname;
        $doctor->headquarter_id = $headquarter->id;
        $doctor->area_id = $headquarter->area_id;

        // Update/Create fields
        $doctor->email = $email;
        $doctor->mobile = $mobile;
        // Mandatory fields - use passed parameters
        $doctor->qualification = $qualification;
        $doctor->speciality = $speciality;
        $doctor->address = $address;
        
        // Optional fields
        if ($this->isColumnExists('gender')) {
            $doctor->gender = trim($this->getColumnValue('gender')) ?: null;
        }
        if ($this->isColumnExists('doctor_type')) {
            $doctor->doctor_type = trim($this->getColumnValue('doctor_type')) ?: null;
        }
        
        // MSL Number
        if ($this->isColumnExists('msl_number')) {
            $mslNumber = trim($this->getColumnValue('msl_number'));
            if (!empty($mslNumber)) {
                // Check if MSL number already exists in doctors, chemists, or stockists (excluding current doctor)
                $mslExists = $this->mslNumberExists($mslNumber, $companyId, $doctor->id ?? null);
                if ($mslExists) {
                    \Log::warning('Doctor import: MSL number "' . $mslNumber . '" already exists for doctor "' . $fullname . '"');
                    // Don't throw exception, just skip MSL number assignment
                } else {
                    $doctor->msl_number = $mslNumber;
                }
            }
        }

        // Date fields
        if ($this->isColumnExists('dob')) {
            $dob = $this->getColumnValue('dob');
            if ($dob) {
                try {
                    $doctor->dob = \Carbon\Carbon::parse($dob)->format('Y-m-d');
                } catch (\Exception $e) {
                    $doctor->dob = null;
                }
            }
        }

        if ($this->isColumnExists('dom')) {
            $dom = $this->getColumnValue('dom');
            if ($dom) {
                try {
                    $doctor->dom = \Carbon\Carbon::parse($dom)->format('Y-m-d');
                } catch (\Exception $e) {
                    $doctor->dom = null;
                }
            }
        }

        // Station handling - use mandatory fields passed as parameters
        $stationTypeLower = self::normalizeStationType($stationType);

        if (self::normalizeGeoName($stationName) !== '' && self::normalizeGeoName($stationName) === self::normalizeGeoName($headquarter->name)) {
            $stationTypeLower = 'headquarter';
        }
        
        if ($stationTypeLower === 'exstation') {
            $station = $this->resolveStation(
                $exstations,
                $headquarter,
                $stationName,
                $companyId,
                PharmaExstation::class,
                'exstation'
            );
            $doctor->exstation_id = $station->id;
            $doctor->outstation_id = null;
        } elseif ($stationTypeLower === 'outstation') {
            $station = $this->resolveStation(
                $outstations,
                $headquarter,
                $stationName,
                $companyId,
                PharmaOutstation::class,
                'outstation'
            );
            $doctor->outstation_id = $station->id;
            $doctor->exstation_id = null;
        } elseif ($stationTypeLower === 'headquarter') {
            // Doctor is at headquarter, no station
            $doctor->exstation_id = null;
            $doctor->outstation_id = null;
        } else {
            $errorMsg = 'Doctor import: Invalid station type "' . $stationType . '" for doctor "' . $fullname . '". Expected: headquarter, exstation, or outstation';
            \Log::warning($errorMsg);
            // Throw exception to be caught by outer handler
            throw new \Exception($errorMsg);
        }

        // Note: doctors table doesn't have added_by column, so we don't set it
        
        try {
            $doctor->save();
            $savedEmailKey = self::normalizeEmailForDuplicate($doctor->email);
            if (!empty($savedEmailKey)) {
                $existingDoctorsByEmail->put($savedEmailKey, $doctor);
            }
            $savedMobileKey = self::normalizeMobileForDuplicate($doctor->mobile);
            if (!empty($savedMobileKey)) {
                $existingDoctorsByMobile->put($savedMobileKey, $doctor);
            }
            $nameHqKey = strtolower(preg_replace('/\s+/', ' ', trim($fullname))) . '|' . $headquarter->id;
            if (!$existingDoctorsByNameHq->has($nameHqKey)) {
                $existingDoctorsByNameHq->put($nameHqKey, collect());
            }
            $existingDoctorsByNameHq->get($nameHqKey)->push($doctor);

            \Log::info('Doctor imported: ' . $doctor->fullname . ' (ID: ' . $doctor->id . ', HQ: ' . $headquarter->name . ')');
        } catch (\Exception $saveError) {
            \Log::error('Doctor save error for ' . $fullname . ': ' . $saveError->getMessage());
            \Log::error('Doctor data: ' . json_encode($doctor->getAttributes()));
            throw $saveError; // Re-throw to be caught by outer handler
        }

        // Handle products - support single column (comma-separated) or Barnd1/Barnd2/Barnd3 columns
        $productNames = [];
        if ($this->isColumnExists('products')) {
            $productsString = trim((string) $this->getColumnValue('products'));
            if ($productsString !== '') {
                $productNames = array_merge($productNames, array_map('trim', explode(',', $productsString)));
            }
        }
        if ($this->isColumnExists('products_2')) {
            $v = trim((string) $this->getColumnValue('products_2'));
            if ($v !== '') {
                $productNames = array_merge($productNames, array_map('trim', explode(',', $v)));
            }
        }
        if ($this->isColumnExists('products_3')) {
            $v = trim((string) $this->getColumnValue('products_3'));
            if ($v !== '') {
                $productNames = array_merge($productNames, array_map('trim', explode(',', $v)));
            }
        }
        $productNames = array_unique(array_filter($productNames));
        if (!empty($productNames)) {
            $productIds = [];
            foreach ($productNames as $productName) {
                $product = $allProducts->get($productName);
                if ($product) {
                    $productIds[] = $product->id;
                }
            }
            if (!empty($productIds)) {
                $doctor->products()->sync($productIds);
            }
        }

        return true;
    }

    public static function normalizeEmailForDuplicate($email): ?string
    {
        $email = strtolower(trim((string) $email));

        return $email !== '' ? $email : null;
    }

    public static function normalizeMobileForStorage($mobile): ?string
    {
        $mobile = trim((string) $mobile);

        if ($mobile === '') {
            return null;
        }

        if (preg_match('/^[\d.]+E\+?\d+$/i', $mobile)) {
            $mobile = number_format((float) $mobile, 0, '', '');
        }

        $mobile = preg_replace('/\D+/', '', $mobile);

        return $mobile !== '' ? $mobile : null;
    }

    public static function normalizeMobileForDuplicate($mobile): ?string
    {
        $mobile = self::normalizeMobileForStorage($mobile);

        if ($mobile === null) {
            return null;
        }

        return strlen($mobile) > 10 ? substr($mobile, -10) : $mobile;
    }

    public static function normalizeGeoName($name): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $name)));
    }

    public static function normalizeStationType($stationType): string
    {
        $stationType = self::normalizeGeoName($stationType);

        return match ($stationType) {
            'hq', 'headquarter', 'headquarters' => 'headquarter',
            'ex', 'exstation', 'exstations' => 'exstation',
            'os', 'outstation', 'outstations' => 'outstation',
            default => $stationType,
        };
    }

    private function rowHasData(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function findGeoByName($items, string $name)
    {
        $normalizedName = self::normalizeGeoName($name);

        if ($normalizedName === '') {
            return null;
        }

        $direct = $items->get($normalizedName);
        if ($direct) {
            return $direct;
        }

        $bestMatch = null;
        $bestScore = 0;

        foreach ($items as $item) {
            $candidate = self::normalizeGeoName($item->name ?? '');

            if ($candidate === '') {
                continue;
            }

            if ($candidate === $normalizedName || str_starts_with($candidate, $normalizedName) || str_starts_with($normalizedName, $candidate)) {
                return $item;
            }

            similar_text($normalizedName, $candidate, $score);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $item;
            }
        }

        return $bestScore >= 85 ? $bestMatch : null;
    }

    private function resolveStation(&$stationMaps, $headquarter, string $stationName, int $companyId, string $stationModel, string $stationType)
    {
        $stationName = trim($stationName);
        $normalizedName = self::normalizeGeoName($stationName);

        if ($normalizedName === '') {
            throw new \Exception('Doctor import: Station name is required for station type "' . $stationType . '"');
        }

        if (!$stationMaps->has($headquarter->id)) {
            $stationMaps->put($headquarter->id, collect());
        }

        $stations = $stationMaps->get($headquarter->id);
        $station = $this->findGeoByName($stations, $stationName);

        if ($station) {
            return $station;
        }

        $station = $stationModel::firstOrCreate(
            [
                'company_id' => $companyId,
                'name' => $stationName,
            ],
            [
                'company_id' => $companyId,
                'name' => $stationName,
            ]
        );

        PharmaHeadquarterAssign::firstOrCreate([
            'company_id' => $companyId,
            'headquarter_id' => $headquarter->id,
            'station' => $stationType,
            'station_id' => $station->id,
        ]);

        $stations->put(self::normalizeGeoName($station->name), $station);
        $stationMaps->put($headquarter->id, $stations);

        \Log::info('Doctor import: Auto-created ' . $stationType . ' "' . $station->name . '" for headquarter "' . $headquarter->name . '"');

        return $station;
    }

    /**
     * Check if MSL number exists in doctors, chemists, or stockists tables
     */
    private function mslNumberExists(string $mslNumber, int $companyId, ?int $excludeDoctorId = null): bool
    {
        // Check in doctors table
        $doctorQuery = Doctor::where('msl_number', $mslNumber)
            ->where('company_id', $companyId);
        if ($excludeDoctorId) {
            $doctorQuery->where('id', '!=', $excludeDoctorId);
        }
        if ($doctorQuery->exists()) {
            return true;
        }
        
        // Check in chemists table
        if (Chemist::where('msl_number', $mslNumber)
            ->where('company_id', $companyId)
            ->exists()) {
            return true;
        }
        
        // Check in stockists table
        if (Stockist::where('msl_number', $mslNumber)
            ->where('company_id', $companyId)
            ->exists()) {
            return true;
        }
        
        return false;
    }

}
