<?php

namespace App\Jobs;

use App\Traits\ExcelImportable;
use App\Traits\UniversalSearchTrait;
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

class ImportStockistJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UniversalSearchTrait;
    use ExcelImportable;

    private $rows;
    private $columns;
    private $company;
    private $row;

    public function __construct($rows, $columns, $company = null)
    {
        $this->rows = is_array($rows) && isset($rows[0]) && is_array($rows[0]) ? $rows : [$rows];
        $this->columns = $columns;
        $this->company = $company;
    }

    public function handle()
    {
        $companyId = $this->company?->id ?? company()->id;
        
        // Pre-load all related data once for faster lookups
        $headquarters = PharmaHeadquarter::with(['exstations', 'outstations'])
            ->where('company_id', $companyId)
            ->get()
            ->keyBy('name');
        
        \Log::info('Stockist import: Loaded ' . $headquarters->count() . ' headquarters');
        
        // Build exstations and outstations maps
        $exstations = collect();
        foreach ($headquarters as $hq) {
            $exstations[$hq->id] = $hq->exstations->keyBy('name');
        }
        
        $outstations = collect();
        foreach ($headquarters as $hq) {
            $outstations[$hq->id] = $hq->outstations->keyBy('name');
        }
        
        // Pre-load existing stockists for faster matching
        $existingStockistsByEmail = Stockist::where('company_id', $companyId)
            ->whereNotNull('email')
            ->get()
            ->keyBy('email');
        
        $existingStockistsByMobile = Stockist::where('company_id', $companyId)
            ->whereNotNull('mobile')
            ->get()
            ->keyBy('mobile');
        
        $existingStockistsByShopnameHq = Stockist::where('company_id', $companyId)
            ->get()
            ->groupBy(function($stockist) {
                return $stockist->shopname . '|' . $stockist->headquarter_id;
            });
        
        $savedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        
        DB::beginTransaction();
        try {
            foreach ($this->rows as $rowIndex => $rowData) {
                $this->row = $rowData;
                
                \Log::info('Processing stockist row ' . ($rowIndex + 1) . ' of ' . count($this->rows));
                
                // Check for mandatory fields
                $mandatoryFields = ['shopname', 'owner_name', 'owner_mobile', 'headquarter', 'station_type', 'station', 'address'];
                $missingFields = [];
                foreach ($mandatoryFields as $field) {
                    if (!$this->isColumnExists($field)) {
                        $missingFields[] = $field;
                    }
                }
                
                if (!empty($missingFields)) {
                    \Log::warning('Stockist import row ' . ($rowIndex + 1) . ': Missing mandatory fields - ' . implode(', ', $missingFields));
                    $skippedCount++;
                    continue;
                }

                $shopname = trim($this->getColumnValue('shopname'));
                $ownerName = trim($this->getColumnValue('owner_name'));
                $ownerMobileRaw = trim($this->getColumnValue('owner_mobile'));
                $headquarterName = trim($this->getColumnValue('headquarter'));
                $stationType = trim($this->getColumnValue('station_type'));
                $stationName = trim($this->getColumnValue('station'));
                $address = trim($this->getColumnValue('address'));

                // Validate mandatory fields are not empty
                $stationTypeLower = strtolower($stationType);
                $stationNameRequired = !in_array($stationTypeLower, ['headquarter', 'hq']);
                
                if (empty($shopname) || empty($ownerName) || empty($ownerMobileRaw) || empty($headquarterName) || empty($stationType) || empty($address)) {
                    \Log::warning('Stockist import row ' . ($rowIndex + 1) . ': Empty mandatory fields for ' . $shopname);
                    $skippedCount++;
                    continue;
                }
                
                if ($stationNameRequired && empty($stationName)) {
                    \Log::warning('Stockist import row ' . ($rowIndex + 1) . ': Station name is required for station type "' . $stationType . '" for stockist ' . $shopname);
                    $skippedCount++;
                    continue;
                }

                try {
                    $this->processStockistRow($shopname, $ownerName, $ownerMobileRaw, $headquarterName, $stationType, $stationName, $address, $companyId, $headquarters, $exstations, $outstations, $existingStockistsByEmail, $existingStockistsByMobile, $existingStockistsByShopnameHq);
                    $savedCount++;
                    \Log::info('Stockist import row ' . ($rowIndex + 1) . ': Successfully saved ' . $shopname);
                } catch (\Exception $rowError) {
                    $errorCount++;
                    \Log::error('Stockist import row ' . ($rowIndex + 1) . ' error for ' . $shopname . ': ' . $rowError->getMessage());
                    \Log::error('Stack trace: ' . $rowError->getTraceAsString());
                    continue;
                }
            }
            
            DB::commit();
            \Log::info('Stockist import chunk completed. Total rows: ' . count($this->rows) . ', Saved: ' . $savedCount . ', Skipped: ' . $skippedCount . ', Errors: ' . $errorCount);
        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('Stockist import chunk error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
    
    private function processStockistRow($shopname, $ownerName, $ownerMobileRaw, $headquarterName, $stationType, $stationName, $address, $companyId, $headquarters, $exstations, $outstations, $existingStockistsByEmail, $existingStockistsByMobile, $existingStockistsByShopnameHq)
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
            throw new \Exception('Stockist import failed for "' . $shopname . '": Headquarter "' . $headquarterName . '" not found. Available headquarters: ' . $availableHQs);
        }
        
        // Process owner mobile (handle scientific notation)
        $ownerMobile = null;
        if (!empty($ownerMobileRaw)) {
            if (preg_match('/^[\d.]+E\+?\d+$/i', $ownerMobileRaw)) {
                $ownerMobile = (string)(int)(float)$ownerMobileRaw;
            } else {
                $ownerMobile = preg_replace('/[^\d+]/', '', $ownerMobileRaw);
                $ownerMobile = ltrim($ownerMobile, '+');
            }
            $ownerMobile = !empty($ownerMobile) ? $ownerMobile : null;
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
        
        // Find existing stockist
        $stockist = null;
        if (!empty($email)) {
            $stockist = $existingStockistsByEmail->get($email);
        }
        
        if (!$stockist && !empty($mobile)) {
            $stockist = $existingStockistsByMobile->get($mobile);
        }
        
        if (!$stockist) {
            $key = $shopname . '|' . $headquarter->id;
            $stockists = $existingStockistsByShopnameHq->get($key);
            $stockist = $stockists ? $stockists->first() : null;
        }
        
        // Create or update stockist
        if ($stockist) {
            $stockist->shopname = $shopname;
            $stockist->headquarter_id = $headquarter->id;
            $stockist->area_id = $headquarter->area_id;
        } else {
            $stockist = new Stockist();
            $stockist->company_id = $companyId;
            $stockist->shopname = $shopname;
            $stockist->headquarter_id = $headquarter->id;
            $stockist->area_id = $headquarter->area_id;
        }
        
        // Set mandatory fields
        $stockist->owner_name = $ownerName;
        $stockist->owner_mobile = $ownerMobile;
        $stockist->address = $address;
        
        // Set optional fields
        $stockist->email = $email;
        $stockist->mobile = $mobile;
        if ($this->isColumnExists('fullname')) {
            $stockist->fullname = trim($this->getColumnValue('fullname')) ?: null;
        }
        if ($this->isColumnExists('gender')) {
            $stockist->gender = trim($this->getColumnValue('gender')) ?: null;
        }
        if ($this->isColumnExists('employee_name')) {
            $stockist->employee_name = trim($this->getColumnValue('employee_name')) ?: null;
        }
        if ($this->isColumnExists('employee_mobile')) {
            $employeeMobileRaw = trim($this->getColumnValue('employee_mobile'));
            if (!empty($employeeMobileRaw)) {
                if (preg_match('/^[\d.]+E\+?\d+$/i', $employeeMobileRaw)) {
                    $stockist->employee_mobile = (string)(int)(float)$employeeMobileRaw;
                } else {
                    $stockist->employee_mobile = preg_replace('/[^\d+]/', '', $employeeMobileRaw);
                    $stockist->employee_mobile = ltrim($stockist->employee_mobile, '+');
                }
            } else {
                $stockist->employee_mobile = null;
            }
        }
        
        // Date fields
        if ($this->isColumnExists('dob')) {
            $dob = $this->getColumnValue('dob');
            if ($dob) {
                try {
                    $stockist->dob = \Carbon\Carbon::parse($dob)->format('Y-m-d');
                } catch (\Exception $e) {
                    $stockist->dob = null;
                }
            }
        }
        
        if ($this->isColumnExists('dom')) {
            $dom = $this->getColumnValue('dom');
            if ($dom) {
                try {
                    $stockist->dom = \Carbon\Carbon::parse($dom)->format('Y-m-d');
                } catch (\Exception $e) {
                    $stockist->dom = null;
                }
            }
        }
        
        // Station handling
        $stationTypeLower = strtolower(trim($stationType));
        
        if ($stationTypeLower === 'exstation' || $stationTypeLower === 'ex-station') {
            $station = $exstations->get($headquarter->id)?->get($stationName);
            if ($station) {
                $stockist->exstation_id = $station->id;
                $stockist->outstation_id = null;
            } else {
                throw new \Exception('Ex-Station "' . $stationName . '" not found for headquarter "' . $headquarter->name . '"');
            }
        } elseif ($stationTypeLower === 'outstation' || $stationTypeLower === 'out-station') {
            $station = $outstations->get($headquarter->id)?->get($stationName);
            if ($station) {
                $stockist->outstation_id = $station->id;
                $stockist->exstation_id = null;
            } else {
                throw new \Exception('Out-Station "' . $stationName . '" not found for headquarter "' . $headquarter->name . '"');
            }
        } elseif ($stationTypeLower === 'headquarter' || $stationTypeLower === 'hq') {
            $stockist->exstation_id = null;
            $stockist->outstation_id = null;
        } else {
            throw new \Exception('Invalid station type "' . $stationType . '". Expected: headquarter, exstation, or outstation');
        }
        
        try {
            $stockist->save();
            \Log::info('Stockist imported/updated: ' . $stockist->shopname . ' (ID: ' . $stockist->id . ', HQ: ' . $headquarter->name . ')');
        } catch (\Exception $saveError) {
            \Log::error('Stockist save error for ' . $shopname . ': ' . $saveError->getMessage());
            \Log::error('Stockist data: ' . json_encode($stockist->getAttributes()));
            throw $saveError;
        }
    }
}

