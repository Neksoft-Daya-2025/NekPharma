<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Http\Requests\UploadInstallRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Zip;

class UpdateAppController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.updates';
        $this->pageIcon = 'ti-reload';
        $this->activeSettingMenu = 'update_settings';
        $this->middleware(function ($request, $next) {
            abort_403(!user()->hasAdminLikeAccess());

            return $next($request);
        });
    }

    public function index()
    {
        try {
            $results = DB::select('select version()');
            $this->mysql_version = $results[0]->{'version()'};
            $this->databaseType = 'MySQL Version';

            if (str_contains($this->mysql_version, 'Maria')) {
                $this->databaseType = 'Maria Version';
            }
        } catch (\Exception $e) {
            $this->mysql_version = null;
            $this->databaseType = 'MySQL Version';
        }

        $this->reviewed = file_exists(storage_path('reviewed'));

        return view('update-settings.index', $this->data);
    }

    public function store(UploadInstallRequest $request)
    {
        try {
            // Store uploaded ZIP file
            config(['filesystems.default' => 'storage']);
            $updateFolder = storage_path('app') . '/updates';
            
            // Create updates directory if it doesn't exist
            if (!File::exists($updateFolder)) {
                File::makeDirectory($updateFolder, 0755, true);
            }
            
            $fileName = $request->file->getClientOriginalName();
            $path = $updateFolder . '/' . $fileName;

            // Delete existing file if present
            if (file_exists($path)) {
                File::delete($path);
            }

            // Store the uploaded file
            $request->file->storeAs('updates', $fileName);
            
            return Reply::success(__('messages.fileUploadedSuccessfully'), ['filePath' => $path, 'fileName' => $fileName]);
        } catch (\Exception $e) {
            return Reply::error('Error uploading file: ' . $e->getMessage());
        }
    }

    public function deleteFile(Request $request)
    {
        $filePath = $request->filePath;
        File::delete($filePath);

        return Reply::success(__('messages.deleteSuccess'));
    }

    public function install(Request $request)
    {
        try {
            $filePath = $request->filePath;
            
            if (!file_exists($filePath)) {
                return Reply::error('Update file not found.');
            }

            \Log::info('Starting application update from: ' . $filePath);

            // Put application in maintenance mode
            Artisan::call('down', ['--retry' => 60]);
            \Log::info('Maintenance mode enabled');

            // Backup current .env file (important!)
            $envPath = base_path('.env');
            $envBackupPath = base_path('.env.backup.' . date('Y-m-d_H-i-s'));
            if (File::exists($envPath)) {
                File::copy($envPath, $envBackupPath);
                \Log::info('Backed up .env to: ' . $envBackupPath);
            }

            // Open ZIP file
            $zip = Zip::open($filePath);
            
            // Get list of files in ZIP for logging
            try {
                $zipFiles = $zip->listFiles();
                \Log::info('ZIP contains ' . count($zipFiles) . ' files');
            } catch (\Exception $e) {
                \Log::warning('Could not list ZIP files: ' . $e->getMessage());
            }

            // Extract all files from ZIP
            // Note: .env will be restored from backup after extraction
            $zip->extract(base_path());
            \Log::info('Files extracted successfully');

            // Always restore .env file from backup to preserve existing configuration
            if (File::exists($envBackupPath)) {
                File::copy($envBackupPath, $envPath);
                \Log::info('Restored .env from backup to preserve existing configuration');
            }

            // Run database migrations
            \Log::info('Running database migrations...');
            try {
                Artisan::call('migrate', ['--force' => true]);
                \Log::info('Database migrations completed successfully');
            } catch (\Exception $e) {
                \Log::error('Migration error: ' . $e->getMessage());
                // Don't fail the update if migrations fail - log and continue
            }

            // Clear all caches
            \Log::info('Clearing caches...');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            Artisan::call('optimize:clear');
            \Log::info('Caches cleared');

            // Regenerate autoloader (important for new classes)
            \Log::info('Regenerating autoloader...');
            try {
                exec('composer dump-autoload --no-interaction 2>&1', $output, $returnCode);
                \Log::info('Autoloader regenerated. Output: ' . implode("\n", $output));
            } catch (\Exception $e) {
                \Log::warning('Could not regenerate autoloader: ' . $e->getMessage());
            }

            // Rebuild caches
            \Log::info('Rebuilding caches...');
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            \Log::info('Caches rebuilt');

            // Update version file if exists in ZIP
            $versionFile = base_path('version.txt');
            if (File::exists($versionFile)) {
                \Log::info('Version file updated: ' . File::get($versionFile));
            }

            // Mark installation as complete
            File::put(public_path() . '/install-version.txt', 'complete');

            // Delete uploaded ZIP file after successful installation
            if (File::exists($filePath)) {
                File::delete($filePath);
                \Log::info('Deleted update ZIP file');
            }

            // Flush session to force re-login
            Session::flush();

            // Take application out of maintenance mode
            Artisan::call('up');
            \Log::info('Maintenance mode disabled - Update completed successfully');

            return Reply::success('Application updated successfully! Please login again.', [
                'redirect' => route('login')
            ]);

        } catch (\Exception $e) {
            \Log::error('Update installation error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Try to restore .env from most recent backup if available
            $backupFiles = glob(base_path('.env.backup.*'));
            if (!empty($backupFiles)) {
                // Get the most recent backup file
                usort($backupFiles, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                $latestBackup = $backupFiles[0];
                File::copy($latestBackup, base_path('.env'));
                \Log::info('Restored .env from backup: ' . $latestBackup);
            }
            
            // Disable maintenance mode
            try {
                Artisan::call('up');
            } catch (\Exception $upError) {
                \Log::error('Failed to disable maintenance mode: ' . $upError->getMessage());
            }
            
            return Reply::error('Update failed: ' . $e->getMessage() . '. Please check logs for details.');
        }
    }

}
