<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EmployeeDetails;
use App\Models\PharmaArea;
use App\Models\PharmaHeadquarter;
use App\Models\PharmaAssignHeadquarter;
use Illuminate\Http\Request;

class DebugStationsController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Debug Stations';
        
        // Allow all authenticated users for debugging purposes
        // No permission check - accessible to anyone logged in
    }

    public function index(Request $request)
    {
        $userName = $request->get('user', 'Vicky');
        
        // Find user
        $user = User::where('name', 'LIKE', "%{$userName}%")->first();

        if (!$user) {
            return response("❌ ERROR: User '{$userName}' not found!", 404);
        }

        $output = [];
        $output[] = "========================================";
        $output[] = "STATION DROPDOWN DEBUG";
        $output[] = "========================================";
        $output[] = "";
        $output[] = "✅ Found User: {$user->name} (ID: {$user->id})";
        $output[] = "";

        $emp = $user->employeeDetails ?? $user->employeeDetail;

        if (!$emp) {
            return response("❌ ERROR: No employee details found!", 404);
        }

        $output[] = "=== STEP 1: Check Employee Details ===";
        $output[] = "Headquarter ID: " . ($emp->headquarter_id ?? 'NULL');
        $output[] = "Areas (raw from DB): " . var_export($emp->getRawOriginal('areas'), true);
        $output[] = "Areas (after casting): " . var_export($emp->areas, true);
        $output[] = "Areas type: " . gettype($emp->areas);
        $output[] = "Regions (raw from DB): " . var_export($emp->getRawOriginal('regions'), true);
        $output[] = "Regions (after casting): " . var_export($emp->regions, true);
        $output[] = "";

        // Test safeDecode function
        function safeDecode($value) {
            if (is_array($value)) {
                return $value;
            }
            if (is_string($value)) {
                return json_decode($value, true) ?: [];
            }
            return [];
        }

        $output[] = "=== STEP 2: Test Decoding ===";
        $rawAreas = $emp->getRawOriginal('areas');
        $decodedAreas = safeDecode($rawAreas);
        $output[] = "Decoded areas: " . json_encode($decodedAreas);
        $output[] = "Decoded areas type: " . gettype($decodedAreas);

        // Convert to integers
        $areaIds = collect($decodedAreas)->map(function($id) {
            return is_numeric($id) ? (int)$id : $id;
        })->filter()->values();

        $output[] = "Area IDs (as integers): " . json_encode($areaIds->toArray());
        $output[] = "Area IDs count: " . $areaIds->count();
        $output[] = "";

        if ($areaIds->isEmpty()) {
            $output[] = "❌ ERROR: No area IDs found! This is the problem.";
            $output[] = "The system will fall back to direct headquarter assignment.";
            $output[] = "";
            
            if ($emp->headquarter_id) {
                $hq = PharmaHeadquarter::find($emp->headquarter_id);
                $output[] = "Fallback HQ: " . ($hq->name ?? 'NOT FOUND') . " (ID: {$emp->headquarter_id})";
                $output[] = "This explains why only Bareilly's stations are shown.";
            }
            
            return response('<pre>' . implode("\n", $output) . '</pre>', 200)
                ->header('Content-Type', 'text/html; charset=utf-8');
        }

        $output[] = "=== STEP 3: Check Area 11 Headquarters ===";
        foreach ($areaIds as $areaId) {
            $area = PharmaArea::find($areaId);
            $output[] = "";
            $output[] = "Area ID {$areaId}: " . ($area->name ?? 'NOT FOUND');
            
            // Check pharma_assign_headquarters table
            $assignments = PharmaAssignHeadquarter::where('company_id', company()->id)
                ->where('area_id', $areaId)
                ->get();
            
            $output[] = "  Assignments in pharma_assign_headquarters: " . $assignments->count();
            
            if ($assignments->isNotEmpty()) {
                $allHqIds = collect();
                foreach ($assignments as $assignment) {
                    $hqIds = is_array($assignment->headquarter_ids) ? $assignment->headquarter_ids : json_decode($assignment->headquarter_ids, true);
                    if (is_array($hqIds)) {
                        $allHqIds = $allHqIds->merge($hqIds);
                    }
                }
                $allHqIds = $allHqIds->unique()->values();
                $output[] = "  HQ IDs from assignments: " . json_encode($allHqIds->toArray());
                
                if ($allHqIds->isNotEmpty()) {
                    $output[] = "  Headquarters from assignments:";
                    foreach ($allHqIds as $hqId) {
                        $hq = PharmaHeadquarter::with(['exstations', 'outstations'])->find($hqId);
                        if ($hq) {
                            $stationCount = $hq->exstations->count() + $hq->outstations->count() + 1;
                            $output[] = "    - {$hq->name} (ID: {$hqId}) - {$stationCount} stations";
                        }
                    }
                }
            } else {
                $output[] = "  No assignments found - using fallback (all HQs with area_id = {$areaId})";
                
                $hqs = PharmaHeadquarter::where('area_id', $areaId)
                    ->where('company_id', company()->id)
                    ->get();
                
                if ($hqs->isNotEmpty()) {
                    $output[] = "  Found " . $hqs->count() . " headquarters:";
                    $totalStations = 0;
                    foreach ($hqs as $hq) {
                        $hq->load(['exstations', 'outstations']);
                        $stationCount = $hq->exstations->count() + $hq->outstations->count() + 1;
                        $totalStations += $stationCount;
                        $output[] = "    - {$hq->name} (ID: {$hq->id}) - {$stationCount} stations";
                    }
                    $output[] = "  TOTAL STATIONS: {$totalStations}";
                } else {
                    $output[] = "  ❌ NO HEADQUARTERS FOUND for area_id = {$areaId}!";
                    $output[] = "  This is why only the fallback HQ (Bareilly) is shown.";
                }
            }
        }

        $output[] = "";
        $output[] = "=== STEP 4: Simulate AccessibleHeadquarters Trait ===";

        // Simulate the exact logic from AccessibleHeadquarters trait
        $emp = $user->employeeDetails ?? $user->employeeDetail;

        if ($user->hasRole('admin')) {
            $output[] = "User is admin - should see all HQs";
            $accessibleHqIds = null;
        } else {
            $decodedAreas = safeDecode($emp->areas);
            $areaIds = collect($decodedAreas)->map(function($id) {
                return is_numeric($id) ? (int)$id : $id;
            })->filter()->values();
            
            $output[] = "Area IDs after processing: " . json_encode($areaIds->toArray());
            
            if ($areaIds->isNotEmpty()) {
                $assignedHeadquarters = PharmaAssignHeadquarter::where('company_id', company()->id)
                    ->whereIn('area_id', $areaIds->toArray())
                    ->get();
                
                $output[] = "Assignments found: " . $assignedHeadquarters->count();
                
                if ($assignedHeadquarters->isNotEmpty()) {
                    $headquarterIds = collect();
                    foreach ($assignedHeadquarters as $assignment) {
                        $hqIds = is_array($assignment->headquarter_ids) 
                            ? $assignment->headquarter_ids 
                            : json_decode($assignment->headquarter_ids, true);
                        if (is_array($hqIds)) {
                            $headquarterIds = $headquarterIds->merge($hqIds);
                        }
                    }
                    $accessibleHqIds = $headquarterIds->unique()->values()->toArray();
                    $output[] = "✅ Accessible HQ IDs from assignments: " . json_encode($accessibleHqIds);
                } else {
                    $accessibleHqIds = PharmaHeadquarter::whereIn('area_id', $areaIds->toArray())
                        ->where('company_id', company()->id)
                        ->pluck('id')
                        ->toArray();
                    $output[] = "✅ Accessible HQ IDs (fallback): " . json_encode($accessibleHqIds);
                }
            } else {
                $directHqId = $emp->headquarter_id ?? $emp->pharma_headquarter_id ?? null;
                if ($directHqId) {
                    $accessibleHqIds = [$directHqId];
                    $output[] = "⚠️  No areas - using direct HQ: " . json_encode($accessibleHqIds);
                } else {
                    $accessibleHqIds = [];
                    $output[] = "❌ No areas and no direct HQ - empty array";
                }
            }
        }

        $output[] = "";
        $output[] = "=== STEP 5: Expected Stations ===";
        if ($accessibleHqIds && !empty($accessibleHqIds)) {
            $headquarters = PharmaHeadquarter::with(['exstations', 'outstations'])
                ->whereIn('id', $accessibleHqIds)
                ->where('company_id', company()->id)
                ->get();
            
            $output[] = "Headquarters that should be accessible: " . $headquarters->count();
            $output[] = "";
            
            $allStations = [];
            foreach ($headquarters as $hq) {
                $output[] = "HQ: {$hq->name} (ID: {$hq->id})";
                $output[] = "  Stations:";
                $output[] = "    - {$hq->name} (Headquarter)";
                $allStations[] = "{$hq->name} (Headquarter)";
                
                foreach ($hq->exstations as $ex) {
                    $output[] = "    - {$ex->name} (Ex-Station)";
                    $allStations[] = "{$ex->name} (Ex-Station)";
                }
                
                foreach ($hq->outstations as $out) {
                    $output[] = "    - {$out->name} (Out-Station)";
                    $allStations[] = "{$out->name} (Out-Station)";
                }
                $output[] = "";
            }
            
            $output[] = "=== SUMMARY ===";
            $output[] = "Total stations that SHOULD be in dropdown: " . count($allStations);
            $output[] = "Current production shows: 10 stations (Bareilly only)";
            $output[] = "Expected: " . count($allStations) . " stations from " . $headquarters->count() . " headquarters";
            
            if (count($allStations) > 10) {
                $output[] = "";
                $output[] = "✅ DIAGNOSIS: The code should work. If production still shows only 10:";
                $output[] = "   1. Files 3 and 4 may not be uploaded correctly";
                $output[] = "   2. Caches may not be cleared";
                $output[] = "   3. Browser cache may need clearing";
                $output[] = "   4. Check if production has different EmployeeDetails model";
            }
        } else {
            $output[] = "❌ PROBLEM: No accessible headquarters found!";
            $output[] = "This means the system is falling back to direct headquarter assignment.";
        }

        $output[] = "";
        $output[] = "=== STEP 6: Check File Updates ===";
        $output[] = "Checking if EmployeeDetails model has JSON casting...";

        $reflection = new \ReflectionClass(EmployeeDetails::class);
        $castsProperty = $reflection->getProperty('casts');
        $castsProperty->setAccessible(true);
        $casts = $castsProperty->getValue(new EmployeeDetails());

        if (isset($casts['areas']) && $casts['areas'] === 'array') {
            $output[] = "✅ EmployeeDetails model HAS 'areas' => 'array' casting";
        } else {
            $output[] = "❌ EmployeeDetails model MISSING 'areas' => 'array' casting!";
            $output[] = "   This is the problem! File 3 needs to be uploaded.";
        }

        if (isset($casts['regions']) && $casts['regions'] === 'array') {
            $output[] = "✅ EmployeeDetails model HAS 'regions' => 'array' casting";
        } else {
            $output[] = "❌ EmployeeDetails model MISSING 'regions' => 'array' casting!";
            $output[] = "   This is the problem! File 3 needs to be uploaded.";
        }

        $output[] = "";
        $output[] = "=== STEP 7: Check AccessibleHeadquarters Trait ===";
        $traitFile = app_path('Traits/AccessibleHeadquarters.php');
        if (file_exists($traitFile)) {
            $traitContent = file_get_contents($traitFile);
            if (strpos($traitContent, 'is_numeric($id) ? (int)$id') !== false) {
                $output[] = "✅ AccessibleHeadquarters trait HAS integer conversion code";
            } else {
                $output[] = "❌ AccessibleHeadquarters trait MISSING integer conversion code!";
                $output[] = "   This is the problem! File 4 needs to be uploaded.";
            }
        } else {
            $output[] = "❌ AccessibleHeadquarters.php file not found!";
        }

        $output[] = "";
        $output[] = "========================================";
        $output[] = "DEBUG COMPLETE";
        $output[] = "========================================";

        return response('<pre style="background: #1e1e1e; color: #d4d4d4; padding: 20px; font-family: monospace; font-size: 12px; line-height: 1.5;">' . htmlspecialchars(implode("\n", $output)) . '</pre>', 200)
            ->header('Content-Type', 'text/html; charset=utf-8');
    }
}

