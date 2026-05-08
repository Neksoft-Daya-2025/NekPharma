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
            ->keyBy('name');
        
        \Log::info('Doctor import: Loaded ' . $headquarters->count() . ' headquarters: ' . $headquarters->keys()->implode(', '));
        
        // Build exstations map: headquarter_id => [stations keyed by name]
        $exstations = collect();
        foreach ($headquarters as $hq) {
            $exstations[$hq->id] = $hq->exstations->keyBy('name');
        }
        
        // Build outstations map: headquarter_id => [stations keyed by name]
        $outstations = collect();
        foreach ($headquarters as $hq) {
            $outstations[$hq->id] = $hq->outstations->keyBy('name');
        }
        
        $allProducts = Product::where('company_id', $companyId)
            ->get()
            ->keyBy('name');
        
        // Pre-load existing doctors for faster matching (no DB queries per row)
        $existingDoctorsByEmail = Doctor::where('company_id', $companyId)
            ->whereNotNull('email')
            ->get()
            ->keyBy('email');
        
        $existingDoctorsByMobile = Doctor::where('company_id', $companyId)
            ->whereNotNull('mobile')
            ->get()
            ->keyBy('mobile');
        
        $existingDoctorsByNameHq = Doctor::where('company_id', $companyId)
            ->get()
            ->groupBy(function($doctor) {
                return $doctor->fullname . '|' . $doctor->headquarter_id;
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

                // Only Dr. Name and HQ are mandatory; rest have defaults for client format (Dr. Name, HQ, Station Name, Dr. Type)
                $fullname = $this->isColumnExists('fullname') ? trim((string) $this->getColumnValue('fullname')) : '';
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
        $headquarter = $headquarters->get($headquarterName);

        // If not found, try case-insensitive match
        if (!$headquarter) {
            foreach ($headquarters as $hq) {
                if (strcasecmp(trim($hq->name), trim($headquarterName)) === 0) {
                    $headquarter = $hq;
                    break;
                }
            }
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
            $availableHQs = $headquarters->keys()->implode(', ');
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
        $mobileRaw = $this->isColumnExists('mobile') ? trim($this->getColumnValue('mobile')) : null;

        // Convert scientific notation to regular number (Excel converts large numbers to scientific notation)
        $mobile = null;
        if (!empty($mobileRaw)) {
            // Check if it's in scientific notation (e.g., 9.88E+09)
            if (preg_match('/^[\d.]+E\+?\d+$/i', $mobileRaw)) {
                $mobile = (string)(int)(float)$mobileRaw; // Convert scientific notation to integer string
            } else {
                // Remove any non-numeric characters except + at the start
                $mobile = preg_replace('/[^\d+]/', '', $mobileRaw);
                // Remove leading + if present (we'll store without country code prefix)
                $mobile = ltrim($mobile, '+');
            }
            // Ensure it's not empty after processing
            $mobile = !empty($mobile) ? $mobile : null;
        }
        
        // Find existing doctor from cache (no DB query)
        $doctor = null;
        if (!empty($email)) {
            $doctor = $existingDoctorsByEmail->get($email);
        }
        
        if (!$doctor && !empty($mobile)) {
            $doctor = $existingDoctorsByMobile->get($mobile);
        }
        
        if (!$doctor) {
            $key = $fullname . '|' . $headquarter->id;
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
        $stationTypeLower = strtolower(trim($stationType));
        
        if ($stationTypeLower === 'exstation' || $stationTypeLower === 'ex-station') {
            $station = $exstations->get($headquarter->id)?->get($stationName);
            if ($station) {
                $doctor->exstation_id = $station->id;
                $doctor->outstation_id = null;
            } else {
                $errorMsg = 'Doctor import: Ex-Station "' . $stationName . '" not found for headquarter "' . $headquarter->name . '"';
                \Log::warning($errorMsg);
                // Throw exception to be caught by outer handler
                throw new \Exception($errorMsg);
            }
        } elseif ($stationTypeLower === 'outstation' || $stationTypeLower === 'out-station') {
            $station = $outstations->get($headquarter->id)?->get($stationName);
            if ($station) {
                $doctor->outstation_id = $station->id;
                $doctor->exstation_id = null;
            } else {
                $errorMsg = 'Doctor import: Out-Station "' . $stationName . '" not found for headquarter "' . $headquarter->name . '"';
                \Log::warning($errorMsg);
                // Throw exception to be caught by outer handler
                throw new \Exception($errorMsg);
            }
        } elseif ($stationTypeLower === 'headquarter' || $stationTypeLower === 'hq') {
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
            if (!empty($doctor->email)) {
                $existingDoctorsByEmail->put($doctor->email, $doctor);
            }
            if (!empty($doctor->mobile)) {
                $existingDoctorsByMobile->put($doctor->mobile, $doctor);
            }
            $nameHqKey = $fullname . '|' . $headquarter->id;
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
