<?php

namespace App\Traits;

use App\Helper\Files;
use Illuminate\Support\Facades\Bus;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\HeadingRowImport;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use ReflectionClass;

trait ImportExcel
{

    public function importFileProcess($request, $importClass)
    {
        // get class name from $importClass
        $this->importClassName = (new ReflectionClass($importClass))->getShortName();

        $this->file = Files::upload($request->import_file, Files::IMPORT_FOLDER);

        $importInstance = new $importClass;
        Excel::import($importInstance, public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $this->file));
        $excelData = $importInstance->getProcessedData();
        if ($request->has('heading')) {
            array_shift($excelData);
        }

        $isDataNull = true;

        foreach ($excelData as $rowitem) {
            if (array_filter($rowitem)) {
                $isDataNull = false;
                break;
            }
        }

        if ($isDataNull) {
            return 'abort';
        }

        $this->hasHeading = $request->has('heading');
        $this->heading = array();
        $this->fileHeading = array();

        $this->columns = $importClass::fields();
        $this->importMatchedColumns = array();
        $this->matchedColumns = array();

        if ($this->hasHeading) {
            $this->heading = (new HeadingRowImport)->toArray(public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $this->file))[0][0];

            // Excel Format None for get Heading Row Without Format and after change back to config
            HeadingRowFormatter::default('none');
            $this->fileHeading = (new HeadingRowImport)->toArray(public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $this->file))[0][0];
            HeadingRowFormatter::default(config('excel.imports.heading_row.formatter'));

            array_shift($excelData);

            // Normalize headings for matching (lowercase, remove spaces/special chars)
            $normalize = function ($s) {
                return strtolower(trim(preg_replace('/[^a-z0-9]/', '', (string) $s)));
            };
            $normalizedHeadings = array_map($normalize, $this->heading);

            // Create mapping array: column index => field id
            $columnMapping = array();
            foreach ($this->columns as $column) {
                $columnId = $normalize($column['id']);
                $columnName = $normalize($column['name']);
                $matchStrings = array_unique(array_filter([$columnId, $columnName]));
                if (!empty($column['aliases']) && is_array($column['aliases'])) {
                    foreach ($column['aliases'] as $alias) {
                        $matchStrings[] = $normalize($alias);
                    }
                    $matchStrings = array_unique(array_filter($matchStrings));
                }

                foreach ($normalizedHeadings as $index => $normalizedHeading) {
                    $matched = false;
                    foreach ($matchStrings as $matchStr) {
                        if (
                            $normalizedHeading === $matchStr ||
                            strpos($normalizedHeading, $matchStr) !== false ||
                            strpos($matchStr, $normalizedHeading) !== false
                        ) {
                            $matched = true;
                            break;
                        }
                    }
                    if ($matched && !isset($columnMapping[$index])) {
                        $columnMapping[$index] = $column['id'];
                        break;
                    }
                }
            }

            // Build matched columns array
            $this->matchedColumns = array_values($columnMapping);
            $importMatchedColumns = array();

            foreach ($columnMapping as $index => $fieldId) {
                $importMatchedColumns[$fieldId] = $index;
            }

            // Also create index-based mapping for backward compatibility
            foreach ($this->heading as $index => $headingValue) {
                if (isset($columnMapping[$index])) {
                    $importMatchedColumns[$index] = $columnMapping[$index];
                }
            }

            $this->importMatchedColumns = $importMatchedColumns;
        }

        $this->importSample = array_slice($excelData, 0, 5);
    }

    public function importJobProcess($request, $importClass, $importJobClass)
    {
        // get class name from $importClass
        $importClassName = (new ReflectionClass($importClass))->getShortName();

        // clear previous import
        Artisan::call('queue:clear database --queue=' . $importClassName);
        Artisan::call('queue:flush');

        // Get column mapping - use provided columns or auto-map from headers
        $columns = array();

        if (!empty($request->columns)) {
            // Manual mapping provided
            $columns = array_filter($request->columns, function ($value) {
                return $value !== null;
            });
        } else {
            // Auto-map based on headers if no manual mapping provided
            if ($request->has_heading) {
                $heading = (new HeadingRowImport)->toArray(public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $request->file))[0][0];
                $importColumns = $importClass::fields();

                // Normalize headings for matching
                $normalizedHeadings = array_map(function ($h) {
                    return strtolower(trim(preg_replace('/[^a-z0-9]/', '', $h)));
                }, $heading);

                // Create auto-mapping
                foreach ($heading as $index => $headingValue) {
                    $normalizedHeading = $normalizedHeadings[$index];

                    foreach ($importColumns as $column) {
                        $columnId = strtolower(trim(preg_replace('/[^a-z0-9]/', '', $column['id'])));
                        $columnName = strtolower(trim(preg_replace('/[^a-z0-9]/', '', $column['name'])));

                        if (
                            $normalizedHeading === $columnId ||
                            $normalizedHeading === $columnName ||
                            strpos($normalizedHeading, $columnId) !== false ||
                            strpos($normalizedHeading, $columnName) !== false ||
                            strpos($columnId, $normalizedHeading) !== false ||
                            strpos($columnName, $normalizedHeading) !== false
                        ) {
                            $columns[$index] = $column['id'];
                            break;
                        }
                    }
                }
            }
        }

        $importInstance = new $importClass;
        Excel::import($importInstance, public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $request->file));
        $excelData = $importInstance->getProcessedData();

        if ($request->has_heading) {
            array_shift($excelData);
        }

        $jobs = [];

        Session::put('leads_count', count($excelData));

        foreach ($excelData as $row) {
            $jobs[] = (new $importJobClass($row, $columns, company()));
        }

        $batch = Bus::batch($jobs)->onConnection('database')->onQueue($importClassName)->name($importClassName)->dispatch();

        // Files::deleteFile($request->file, Files::IMPORT_FOLDER);

        // Move file to history folder
        $historyFolder = 'import_history/' . strtolower($importClassName);
        if (!\Storage::disk('local')->exists($historyFolder)) {
            \Storage::disk('local')->makeDirectory($historyFolder);
        }

        $file = $request->file;
        $sourcePath = Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $file;
        $destinationPath = $historyFolder . '/' . $file;

        try {
            $sourceFullPath = public_path($sourcePath);
            $destinationFullPath = storage_path('app/public/' . $destinationPath);

            // Ensure directory exists
            if (!file_exists(dirname($destinationFullPath))) {
                mkdir(dirname($destinationFullPath), 0775, true);
            }

            copy($sourceFullPath, $destinationFullPath);

            $displayFilename = request()->hasFile('import_file')
                ? request()->file('import_file')->getClientOriginalName()
                : $file;
            $recordsCount = count($excelData);

            // Create history record
            \App\Models\ImportHistory::create([
                'company_id' => company()->id,
                'user_id' => user()->id,
                'module' => $importClassName,
                'filename' => $displayFilename,
                'filepath' => $destinationPath,
                'status' => 'processing', // Starts as processing for queued jobs
                'records_count' => $recordsCount,
            ]);

            // Now delete from temp import folder
            Files::deleteFile($file, Files::IMPORT_FOLDER);

        } catch (\Exception $e) {
            \Log::error('Failed to create import history: ' . $e->getMessage());
            Files::deleteFile($file, Files::IMPORT_FOLDER);
        }

        return $batch;
    }

    public function importJobProcessDirect($excelData, $columns, $file, $importClass, $importJobClass, $allowedHeadquarterIds = null)
    {
        $importClassName = (new ReflectionClass($importClass))->getShortName();

        // clear previous import
        Artisan::call('queue:clear database --queue=' . $importClassName);
        Artisan::call('queue:flush');

        $jobs = [];
        $totalRows = count($excelData);

        // One job per file for moderate sizes avoids partial imports (e.g. only first 50 of 100 rows)
        // when sync batch / timeout / queue edge cases occur. Chunk only for very large files.
        $chunkSize = $totalRows <= 1000 ? max(1, $totalRows) : 100;

        Session::put('leads_count', $totalRows);

        // Chunk data into groups for faster processing
        $chunks = array_chunk($excelData, $chunkSize);

        foreach ($chunks as $chunk) {
            $jobs[] = (new $importJobClass($chunk, $columns, company(), $allowedHeadquarterIds));
        }

        // Use 'sync' connection to process jobs immediately without queue worker
        $batch = Bus::batch($jobs)->onConnection('sync')->onQueue($importClassName)->name($importClassName)->dispatch();

        // Files::deleteFile($file, Files::IMPORT_FOLDER);

        // Move file to history folder
        $historyFolder = 'import_history/' . strtolower($importClassName);
        if (!\Storage::disk('local')->exists($historyFolder)) {
            \Storage::disk('local')->makeDirectory($historyFolder);
        }

        $sourcePath = Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $file;
        $destinationPath = $historyFolder . '/' . $file;

        // Copy to storage/app/public/... (or wherever Files helper uses)
        // Since Files::upload puts it in public_path(Files::UPLOAD_FOLDER...), we need to move it to storage
        // Assuming we want to keep it in storage/app/public for easy download via Storage facade

        try {
            $sourceFullPath = public_path($sourcePath);
            $destinationFullPath = storage_path('app/public/' . $destinationPath);

            // Ensure directory exists
            if (!file_exists(dirname($destinationFullPath))) {
                mkdir(dirname($destinationFullPath), 0775, true);
            }

            copy($sourceFullPath, $destinationFullPath);

            $displayFilename = request()->hasFile('import_file')
                ? request()->file('import_file')->getClientOriginalName()
                : $file;

            // Create history record
            \App\Models\ImportHistory::create([
                'company_id' => company()->id,
                'user_id' => user()->id,
                'module' => $importClassName,
                'filename' => $displayFilename,
                'filepath' => $destinationPath,
                'status' => 'completed',
                'records_count' => $totalRows,
            ]);

            // Now delete from temp import folder
            Files::deleteFile($file, Files::IMPORT_FOLDER);

        } catch (\Exception $e) {
            \Log::error('Failed to create import history: ' . $e->getMessage());
            // Fallback: delete file to clean up if history creation fails
            Files::deleteFile($file, Files::IMPORT_FOLDER);
        }

        return $batch;
    }

    /**
     * Read all sheet rows with fixed column indices (empty cells stay in place).
     * Maatwebsite ToArray collapses sparse rows and misaligns headers when middle columns are blank.
     */
    protected function readExcelPreserveColumnIndices(string $filePath): array
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = (int) $worksheet->getHighestRow();

        if ($highestRow < 1) {
            return [];
        }

        $highestColumn = $worksheet->getHighestColumn();
        $range = 'A1:' . $highestColumn . $highestRow;
        $raw = $worksheet->rangeToArray($range, null, true, true, false);

        return array_map(function (array $row) {
            return array_map(function ($cell) {
                if ($cell === null || $cell === '') {
                    return '';
                }

                return is_scalar($cell) ? trim((string) $cell) : trim((string) $cell);
            }, $row);
        }, $raw);
    }

}
