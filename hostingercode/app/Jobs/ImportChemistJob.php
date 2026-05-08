<?php

namespace App\Jobs;

use App\Traits\ExcelImportable;
use App\Traits\UniversalSearchTrait;
use App\Models\Chemist;
use App\Models\Doctor;
use App\Models\Stockist;
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

class ImportChemistJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UniversalSearchTrait;
    use ExcelImportable;

    private $rows;
    private $columns;
    private $company;
    private $row;
    /** @var array|null null = admin (all HQs allowed), array = allowed HQ ids for non-admin */
    private $allowedHeadquarterIds;

    public function __construct($rows, $columns, $company = null, $allowedHeadquarterIds = null)
    {
        $this->rows = is_array($rows) && isset($rows[0]) && is_array($rows[0]) ? $rows : [$rows];
        $this->columns = $columns;
        $this->company = $company;
        $this->allowedHeadquarterIds = $allowedHeadquarterIds;
    }

    public function handle()
    {
        $companyId = $this->company?->id ?? company()->id;
        
        // Pre-load all related data once for faster lookups
        $headquarters = PharmaHeadquarter::with(['exstations', 'outstations'])
            ->where('company_id', $companyId)
            ->get()
            ->keyBy('name');
        
        \Log::info('Chemist import: Loaded ' . $headquarters->count() . ' headquarters');
        
        // Build exstations and outstations maps
        $exstations = collect();
        foreach ($headquarters as $hq) {
            $exstations[$hq->id] = $hq->exstations->keyBy('name');
        }
        
        $outstations = collect();
        foreach ($headquarters as $hq) {
            $outstations[$hq->id] = $hq->outstations->keyBy('name');
        }
        
        // Pre-load existing chemists for faster matching
        $existingChemistsByEmail = Chemist::where('company_id', $companyId)
            ->whereNotNull('email')
            ->get()
            ->keyBy('email');
        
        $existingChemistsByMobile = Chemist::where('company_id', $companyId)
            ->whereNotNull('mobile')
            ->get()
            ->keyBy('mobile');
        
        $existingChemistsByShopnameHq = Chemist::where('company_id', $companyId)
            ->get()
            ->groupBy(function($chemist) {
                return $chemist->shopname . '|' . $chemist->headquarter_id;
            });
        
        $savedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        
        DB::beginTransaction();
        try {
            foreach ($this->rows as $rowIndex => $rowData) {
                $this->row = $rowData;
                
                \Log::info('Processing chemist row ' . ($rowIndex + 1) . ' of ' . count($this->rows));
                
                // Check for mandatory fields
                $mandatoryFields = ['shopname', 'headquarter', 'station_type', 'station', 'address'];
                $missingFields = [];
                foreach ($mandatoryFields as $field) {
                    if (!$this->isColumnExists($field)) {
                        $missingFields[] = $field;
                    }
                }
                
                if (!empty($missingFields)) {
                    \Log::warning('Chemist import row ' . ($rowIndex + 1) . ': Missing mandatory fields - ' . implode(', ', $missingFields));
                    $skippedCount++;
                    continue;
                }

                $shopname = trim($this->getColumnValue('shopname'));
                $headquarterName = trim($this->getColumnValue('headquarter'));
                $stationType = trim($this->getColumnValue('station_type'));
                $stationName = trim($this->getColumnValue('station'));
                $address = trim($this->getColumnValue('address'));

                // Validate mandatory fields are not empty
                $stationTypeLower = strtolower($stationType);
                $stationNameRequired = !in_array($stationTypeLower, ['headquarter', 'hq']);
                
                if (empty($shopname) || empty($headquarterName) || empty($stationType) || empty($address)) {
                    \Log::warning('Chemist import row ' . ($rowIndex + 1) . ': Empty mandatory fields for ' . $shopname);
                    $skippedCount++;
                    continue;
                }
                
                if ($stationNameRequired && empty($stationName)) {
                    \Log::warning('Chemist import row ' . ($rowIndex + 1) . ': Station name is required for station type "' . $stationType . '" for chemist ' . $shopname);
                    $skippedCount++;
                    continue;
                }

                try {
                    $this->processChemistRow($shopname, $headquarterName, $stationType, $stationName, $address, $companyId, $headquarters, $exstations, $outstations, $existingChemistsByEmail, $existingChemistsByMobile, $existingChemistsByShopnameHq);
                    $savedCount++;
                    \Log::info('Chemist import row ' . ($rowIndex + 1) . ': Successfully saved ' . $shopname);
                } catch (\Exception $rowError) {
                    $errorCount++;
                    \Log::error('Chemist import row ' . ($rowIndex + 1) . ' error for ' . $shopname . ': ' . $rowError->getMessage());
                    \Log::error('Stack trace: ' . $rowError->getTraceAsString());
                    continue;
                }
            }
            
            DB::commit();
            \Log::info('Chemist import chunk completed. Total rows: ' . count($this->rows) . ', Saved: ' . $savedCount . ', Skipped: ' . $skippedCount . ', Errors: ' . $errorCount);
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('Chemist import chunk error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
    
    private function processChemistRow($shopname, $headquarterName, $stationType, $stationName, $address, $companyId, $headquarters, $exstations, $outstations, $existingChemistsByEmail, $existingChemistsByMobile, $existingChemistsByShopnameHq)
    {
        // Get headquarter from cache
        $headquarter = $headquarters->get($headquarterName);
        
        if (!$headquarter) {
            foreach ($headquarters as $hq) {
                if (strcasecmp(trim($hq->name), trim($headquarterName)) === 0) {
                    $headquarter = $hq;
                    break;
                }
            }
        }
        
        if (!$headquarter) {
            $trimmedName = trim($headquarterName);
            foreach ($headquarters as $hq) {
                $hqName = trim($hq->name);
                if (stripos($hqName, $trimmedName) === 0 || stripos($trimmedName, $hqName) === 0) {
                    $headquarter = $hq;
                    break;
                }
            }
        }
        
        if (!$headquarter) {
            $availableHQs = $headquarters->keys()->implode(', ');
            throw new \Exception('Chemist import failed for "' . $shopname . '": Headquarter "' . $headquarterName . '" not found. Available headquarters: ' . $availableHQs);
        }

        // Restrict to assigned areas: non-admin can only import into their accessible HQs
        if ($this->allowedHeadquarterIds !== null && !in_array($headquarter->id, $this->allowedHeadquarterIds, true)) {
            $msg = 'Chemist import skipped for "' . $shopname . '": Headquarter "' . $headquarterName . '" is not in your assigned areas.';
            \Log::warning($msg);
            throw new \Exception($msg);
        }
        
        // Get optional fields
        $email = $this->isColumnExists('email') ? trim($this->getColumnValue('email')) : null;
        $mobileRaw = $this->isColumnExists('mobile') ? trim($this->getColumnValue('mobile')) : null;
        
        $mobile = null;
        if (!empty($mobileRaw)) {
            if (preg_match('/^[\d.]+E\+?\d+$/i', $mobileRaw)) {
                $mobile = (string)(int)(float)$mobileRaw;
            } else {
                $mobile = preg_replace('/[^\d+]/', '', $mobileRaw);
                $mobile = ltrim($mobile, '+');
            }
            $mobile = !empty($mobile) ? $mobile : null;
        }
        
        // Find existing chemist
        $chemist = null;
        if (!empty($email)) {
            $chemist = $existingChemistsByEmail->get($email);
        }
        
        if (!$chemist && !empty($mobile)) {
            $chemist = $existingChemistsByMobile->get($mobile);
        }
        
        if (!$chemist) {
            $key = $shopname . '|' . $headquarter->id;
            $chemists = $existingChemistsByShopnameHq->get($key);
            $chemist = $chemists ? $chemists->first() : null;
        }
        
        // Create or update chemist
        if ($chemist) {
            $chemist->shopname = $shopname;
            $chemist->headquarter_id = $headquarter->id;
            $chemist->area_id = $headquarter->area_id;
        } else {
            $chemist = new Chemist();
            $chemist->company_id = $companyId;
            $chemist->shopname = $shopname;
            $chemist->headquarter_id = $headquarter->id;
            $chemist->area_id = $headquarter->area_id;
        }
        
        // Set mandatory fields
        $chemist->address = $address;
        
        // Set optional fields
        $chemist->email = $email;
        $chemist->mobile = $mobile;
        if ($this->isColumnExists('fullname')) {
            $chemist->fullname = trim($this->getColumnValue('fullname')) ?: null;
        }
        if ($this->isColumnExists('gender')) {
            $chemist->gender = trim($this->getColumnValue('gender')) ?: null;
        }
        
        // MSL Number
        if ($this->isColumnExists('msl_number')) {
            $mslNumber = trim($this->getColumnValue('msl_number'));
            if (!empty($mslNumber)) {
                // Check if MSL number already exists in doctors, chemists, or stockists (excluding current chemist)
                $mslExists = $this->mslNumberExists($mslNumber, $companyId, $chemist->id ?? null);
                if ($mslExists) {
                    \Log::warning('Chemist import: MSL number "' . $mslNumber . '" already exists for chemist "' . $shopname . '"');
                    // Don't throw exception, just skip MSL number assignment
                } else {
                    $chemist->msl_number = $mslNumber;
                }
            }
        }
        
        // Date fields
        if ($this->isColumnExists('dob')) {
            $dob = $this->getColumnValue('dob');
            if ($dob) {
                try {
                    $chemist->dob = \Carbon\Carbon::parse($dob)->format('Y-m-d');
                } catch (\Exception $e) {
                    $chemist->dob = null;
                }
            }
        }
        
        if ($this->isColumnExists('dom')) {
            $dom = $this->getColumnValue('dom');
            if ($dom) {
                try {
                    $chemist->dom = \Carbon\Carbon::parse($dom)->format('Y-m-d');
                } catch (\Exception $e) {
                    $chemist->dom = null;
                }
            }
        }
        
        // Station handling
        $stationTypeLower = strtolower(trim($stationType));
        
        if ($stationTypeLower === 'exstation' || $stationTypeLower === 'ex-station') {
            $station = $exstations->get($headquarter->id)?->get($stationName);
            if ($station) {
                $chemist->exstation_id = $station->id;
                $chemist->outstation_id = null;
            } else {
                throw new \Exception('Ex-Station "' . $stationName . '" not found for headquarter "' . $headquarter->name . '"');
            }
        } elseif ($stationTypeLower === 'outstation' || $stationTypeLower === 'out-station') {
            $station = $outstations->get($headquarter->id)?->get($stationName);
            if ($station) {
                $chemist->outstation_id = $station->id;
                $chemist->exstation_id = null;
            } else {
                throw new \Exception('Out-Station "' . $stationName . '" not found for headquarter "' . $headquarter->name . '"');
            }
        } elseif ($stationTypeLower === 'headquarter' || $stationTypeLower === 'hq') {
            $chemist->exstation_id = null;
            $chemist->outstation_id = null;
        } else {
            throw new \Exception('Invalid station type "' . $stationType . '". Expected: headquarter, exstation, or outstation');
        }
        
        try {
            $chemist->save();
            \Log::info('Chemist imported/updated: ' . $chemist->shopname . ' (ID: ' . $chemist->id . ', HQ: ' . $headquarter->name . ')');
        } catch (\Exception $saveError) {
            \Log::error('Chemist save error for ' . $shopname . ': ' . $saveError->getMessage());
            \Log::error('Chemist data: ' . json_encode($chemist->getAttributes()));
            throw $saveError;
        }
    }
    
    /**
     * Check if MSL number exists in doctors, chemists, or stockists tables
     */
    private function mslNumberExists(string $mslNumber, int $companyId, ?int $excludeChemistId = null): bool
    {
        // Check in doctors table
        if (Doctor::where('msl_number', $mslNumber)
            ->where('company_id', $companyId)
            ->exists()) {
            return true;
        }
        
        // Check in chemists table
        $chemistQuery = Chemist::where('msl_number', $mslNumber)
            ->where('company_id', $companyId);
        if ($excludeChemistId) {
            $chemistQuery->where('id', '!=', $excludeChemistId);
        }
        if ($chemistQuery->exists()) {
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

