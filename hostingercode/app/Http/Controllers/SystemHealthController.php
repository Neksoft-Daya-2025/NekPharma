<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Exception;

class SystemHealthController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Software Health';
        $this->activeSettingMenu = 'software_health';
        
        $this->middleware(function ($request, $next) {
            // Only admin can access
            abort_403(!in_array('admin', user_roles()));
            return $next($request);
        });
    }

    public function index()
    {
        $this->checks = $this->runHealthChecks();
        $this->passed = collect($this->checks)->where('status', 'pass')->count();
        $this->failed = collect($this->checks)->where('status', 'fail')->count();
        $this->warnings = collect($this->checks)->where('status', 'warning')->count();
        $this->total = count($this->checks);
        
        return view('settings.software-health.index', $this->data);
    }

    private function runHealthChecks()
    {
        $checks = [];

        // 1. Database Connection
        $checks[] = [
            'name' => 'Database Connection',
            'description' => 'Check if database is accessible',
            'status' => $this->checkDatabase(),
            'message' => $this->checkDatabase() == 'pass' ? 'Connected successfully' : 'Cannot connect to database'
        ];

        // 2. Core Tables
        $checks[] = [
            'name' => 'Core Tables',
            'description' => 'Verify essential tables exist',
            'status' => $this->checkCoreTables(),
            'message' => $this->checkCoreTables() == 'pass' ? 'All core tables present' : 'Some core tables missing'
        ];

        // 3. Pharma Tables
        $checks[] = [
            'name' => 'Pharma CRM Tables',
            'description' => 'Check Doctors, Chemists, Stockists tables',
            'status' => $this->checkPharmaTables(),
            'message' => $this->checkPharmaTables() == 'pass' ? 'All pharma tables present' : 'Some pharma tables missing'
        ];

        // 4. Headquarter Data
        $hqCheck = $this->checkHeadquarters();
        $checks[] = [
            'name' => 'Headquarter Data',
            'description' => 'Doctor filter headquarters',
            'status' => $hqCheck['status'],
            'message' => $hqCheck['message']
        ];

        // 5. Storage Permissions
        $checks[] = [
            'name' => 'Storage Permissions',
            'description' => 'Check if storage folders are writable',
            'status' => $this->checkStoragePermissions(),
            'message' => $this->checkStoragePermissions() == 'pass' ? 'Storage is writable' : 'Storage permission issues'
        ];

        // 6. Environment File
        $checks[] = [
            'name' => 'Environment Configuration',
            'description' => 'Verify .env file exists',
            'status' => $this->checkEnvFile(),
            'message' => $this->checkEnvFile() == 'pass' ? '.env file configured' : '.env file missing'
        ];

        // 7. Application Key
        $checks[] = [
            'name' => 'Application Key',
            'description' => 'APP_KEY security',
            'status' => $this->checkAppKey(),
            'message' => $this->checkAppKey() == 'pass' ? 'APP_KEY is set' : 'APP_KEY not configured'
        ];

        // 8. Modules
        $checks[] = [
            'name' => 'Pharma Modules',
            'description' => 'Check Payroll and other modules',
            'status' => $this->checkModules(),
            'message' => $this->checkModules() == 'pass' ? 'All modules present' : 'Some modules missing'
        ];

        // 9. Payroll CSV Upload Feature
        $checks[] = [
            'name' => 'Payroll CSV Upload',
            'description' => 'Deductions upload feature',
            'status' => $this->checkPayrollCSVFeature(),
            'message' => $this->checkPayrollCSVFeature() == 'pass' ? 'Feature installed' : 'Feature not found'
        ];

        // 10. Doctor Filters
        $checks[] = [
            'name' => 'Doctor HQ/Station Filters',
            'description' => 'Headquarter and Station filters',
            'status' => $this->checkDoctorFilters(),
            'message' => $this->checkDoctorFilters() == 'pass' ? 'Filters installed' : 'Filters not found'
        ];

        return $checks;
    }

    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            return 'pass';
        } catch (Exception $e) {
            return 'fail';
        }
    }

    private function checkCoreTables()
    {
        try {
            $tables = ['users', 'companies', 'employee_details'];
            foreach ($tables as $table) {
                if (!Schema::hasTable($table)) {
                    return 'fail';
                }
            }
            return 'pass';
        } catch (Exception $e) {
            return 'fail';
        }
    }

    private function checkPharmaTables()
    {
        try {
            $tables = ['doctors', 'chemists', 'stockists', 'pharma_headquarters'];
            foreach ($tables as $table) {
                if (!Schema::hasTable($table)) {
                    return 'fail';
                }
            }
            return 'pass';
        } catch (Exception $e) {
            return 'fail';
        }
    }

    private function checkHeadquarters()
    {
        try {
            $count = DB::table('pharma_headquarters')->count();
            if ($count > 0) {
                return [
                    'status' => 'pass',
                    'message' => "{$count} headquarters configured"
                ];
            }
            return [
                'status' => 'warning',
                'message' => 'No headquarters found'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'fail',
                'message' => 'Cannot check headquarters'
            ];
        }
    }

    private function checkStoragePermissions()
    {
        $writable = is_writable(storage_path('logs')) && 
                   is_writable(storage_path('framework/cache'));
        return $writable ? 'pass' : 'fail';
    }

    private function checkEnvFile()
    {
        return file_exists(base_path('.env')) ? 'pass' : 'fail';
    }

    private function checkAppKey()
    {
        return !empty(env('APP_KEY')) ? 'pass' : 'fail';
    }

    private function checkModules()
    {
        $exists = is_dir(base_path('Modules/Payroll')) && 
                 is_dir(base_path('Modules/UniversalBundle'));
        return $exists ? 'pass' : 'fail';
    }

    private function checkPayrollCSVFeature()
    {
        try {
            $file = base_path('Modules/Payroll/Http/Controllers/PayrollController.php');
            if (file_exists($file)) {
                $content = file_get_contents($file);
                return strpos($content, 'downloadDeductionSample') !== false ? 'pass' : 'fail';
            }
            return 'fail';
        } catch (Exception $e) {
            return 'fail';
        }
    }

    private function checkDoctorFilters()
    {
        try {
            $file = resource_path('views/doctors/index.blade.php');
            if (file_exists($file)) {
                $content = file_get_contents($file);
                $hasHQ = strpos($content, 'headquarter_filter') !== false;
                $hasStation = strpos($content, 'station_filter') !== false;
                return ($hasHQ && $hasStation) ? 'pass' : 'fail';
            }
            return 'fail';
        } catch (Exception $e) {
            return 'fail';
        }
    }
}

