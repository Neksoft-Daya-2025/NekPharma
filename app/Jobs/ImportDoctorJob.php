<?php

namespace App\Jobs;

use App\Traits\ExcelImportable;
use App\Traits\UniversalSearchTrait;
use App\Models\Doctor;
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
use Illuminate\Support\Facades\DB;

class ImportDoctorJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UniversalSearchTrait;
    use ExcelImportable;

    private $rows; // Changed to handle multiple rows for chunk processing
    private $columns;
    private $company;
    private $row; // Current row being processed

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($rows, $columns, $company = null)
    {
        // Accept single row or array of rows for chunk processing
        $this->rows = is_array($rows) && isset($rows[0]) && is_array($rows[0]) ? $rows : [$rows];
        $this->columns = $columns;
        $this->company = $company;
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
        $savedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        
        DB::beginTransaction();
        try {
            foreach ($this->rows as $rowIndex => $rowData) {
                $this->row = $rowData;
                
                \Log::info('Processing row ' . ($rowIndex + 1) . ' of ' . count($this->rows));
                
                // Check for mandatory fields
                $mandatoryFields = ['fullname', 'qualification', 'headquarter', 'station_type', 'station', 'address', 'speciality'];
                $missingFields = [];
                foreach ($mandatoryFields as $field) {
                    if (!$this->isColumnExists($field)) {
                        $missingFields[] = $field;
                    }
                }
                
                if (!empty($missingFields)) {
                    \Log::warning('Doctor import row ' . ($rowIndex + 1) . ': Missing mandatory fields - ' . implode(', ', $missingFields));
                    $skippedCount++;
                    continue; // Skip invalid rows
                }

                $fullname = trim($this->getColumnValue('fullname'));
                $qualification = trim($this->getColumnValue('qualification'));
                $headquarterName = trim($this->getColumnValue('headquarter'));
                $stationType = trim($this->getColumnValue('station_type'));
                $stationName = trim($this->getColumnValue('station'));
                $address = trim($this->getColumnValue('address'));
                $speciality = trim($this->getColumnValue('speciality'));

                // Validate mandatory fields are not empty
                // Note: station_name can be empty if station_type is "headquarter"
                $stationTypeLower = strtolower($stationType);
                $stationNameRequired = !in_array($stationTypeLower, ['headquarter', 'hq']);
                
                if (empty($fullname) || empty($qualification) || empty($headquarterName) || empty($stationType) || empty($address) || empty($speciality)) {
                    \Log::warning('Doctor import row ' . ($rowIndex + 1) . ': Empty mandatory fields for ' . $fullname);
                    $skippedCount++;
                    continue; // Skip invalid rows
                }
                
                // Validate station_name is required for exstation/outstation
                if ($stationNameRequired && empty($stationName)) {
                    \Log::warning('Doctor import row ' . ($rowIndex + 1) . ': Station name is required for station type "' . $stationType . '" for doctor ' . $fullname);
                    $skippedCount++;
                    continue; // Skip invalid rows
                }

                try {
                    $this->processDoctorRow($fullname, $qualification, $headquarterName, $stationType, $stationName, $address, $speciality, $companyId, $headquarters, $exstations, $outstations, $allProducts, $existingDoctorsByEmail, $existingDoctorsByMobile, $existingDoctorsByNameHq);
                    $savedCount++;
                    \Log::info('Doctor import row ' . ($rowIndex + 1) . ': Successfully saved ' . $fullname);
                } catch (\Exception $rowError) {
                    $errorCount++;
                    \Log::error('Doctor import row ' . ($rowIndex + 1) . ' error for ' . $fullname . ': ' . $rowError->getMessage());
                    \Log::error('Stack trace: ' . $rowError->getTraceAsString());
                    // Continue with next row instead of failing entire chunk
                    continue;
                }
            }
            
            DB::commit();
            \Log::info('Doctor import chunk completed. Total rows: ' . count($this->rows) . ', Saved: ' . $savedCount . ', Skipped: ' . $skippedCount . ', Errors: ' . $errorCount);
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('Doctor import chunk error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * Process a single doctor row
     */
    private function processDoctorRow($fullname, $qualification, $headquarterName, $stationType, $stationName, $address, $speciality, $companyId, $headquarters, $exstations, $outstations, $allProducts, $existingDoctorsByEmail, $existingDoctorsByMobile, $existingDoctorsByNameHq)
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
            // Throw exception to be caught by outer handler
            throw new \Exception($errorMsg);
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
        
        // If doctor exists, update it; otherwise create new
        if ($doctor) {
            // Update existing doctor
            $doctor->fullname = $fullname;
            $doctor->headquarter_id = $headquarter->id;
            $doctor->area_id = $headquarter->area_id;
        } else {
            // Create new doctor
            $doctor = new Doctor();
            $doctor->company_id = $companyId;
            $doctor->fullname = $fullname;
            $doctor->headquarter_id = $headquarter->id;
            $doctor->area_id = $headquarter->area_id;
        }

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
            \Log::info('Doctor imported/updated: ' . $doctor->fullname . ' (ID: ' . $doctor->id . ', HQ: ' . $headquarter->name . ')');
        } catch (\Exception $saveError) {
            \Log::error('Doctor save error for ' . $fullname . ': ' . $saveError->getMessage());
            \Log::error('Doctor data: ' . json_encode($doctor->getAttributes()));
            throw $saveError; // Re-throw to be caught by outer handler
        }

        // Handle products - use cached products
        if ($this->isColumnExists('products')) {
            $productsString = trim($this->getColumnValue('products'));
            if (!empty($productsString)) {
                $productNames = array_map('trim', explode(',', $productsString));
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
        }
    }

}
