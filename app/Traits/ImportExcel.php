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
            $normalizedHeadings = array_map(function($heading) {
                return strtolower(trim(preg_replace('/[^a-z0-9]/', '', $heading)));
            }, $this->heading);
            
            // Create mapping array: column index => field id
            $columnMapping = array();
            foreach ($this->columns as $column) {
                $columnId = strtolower(trim(preg_replace('/[^a-z0-9]/', '', $column['id'])));
                $columnName = strtolower(trim(preg_replace('/[^a-z0-9]/', '', $column['name'])));
                
                // Try to match by ID first, then by name
                foreach ($normalizedHeadings as $index => $normalizedHeading) {
                    if ($normalizedHeading === $columnId || 
                        $normalizedHeading === $columnName ||
                        strpos($normalizedHeading, $columnId) !== false ||
                        strpos($normalizedHeading, $columnName) !== false ||
                        strpos($columnId, $normalizedHeading) !== false ||
                        strpos($columnName, $normalizedHeading) !== false) {
                        if (!isset($columnMapping[$index])) {
                            $columnMapping[$index] = $column['id'];
                            break;
                        }
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
                $normalizedHeadings = array_map(function($h) {
                    return strtolower(trim(preg_replace('/[^a-z0-9]/', '', $h)));
                }, $heading);
                
                // Create auto-mapping
                foreach ($heading as $index => $headingValue) {
                    $normalizedHeading = $normalizedHeadings[$index];
                    
                    foreach ($importColumns as $column) {
                        $columnId = strtolower(trim(preg_replace('/[^a-z0-9]/', '', $column['id'])));
                        $columnName = strtolower(trim(preg_replace('/[^a-z0-9]/', '', $column['name'])));
                        
                        if ($normalizedHeading === $columnId || 
                            $normalizedHeading === $columnName ||
                            strpos($normalizedHeading, $columnId) !== false ||
                            strpos($normalizedHeading, $columnName) !== false ||
                            strpos($columnId, $normalizedHeading) !== false ||
                            strpos($columnName, $normalizedHeading) !== false) {
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

        Files::deleteFile($request->file, Files::IMPORT_FOLDER);

        return $batch;
    }

    public function importJobProcessDirect($excelData, $columns, $file, $importClass, $importJobClass)
    {
        $importClassName = (new ReflectionClass($importClass))->getShortName();

        // clear previous import
        Artisan::call('queue:clear database --queue=' . $importClassName);
        Artisan::call('queue:flush');

        $jobs = [];
        $totalRows = count($excelData);
        
        // For small imports (< 50 rows), process one row per job for accurate progress tracking
        // For larger imports, chunk for better performance
        $chunkSize = $totalRows <= 50 ? 1 : 50;

        Session::put('leads_count', $totalRows);

        // Chunk data into groups for faster processing
        $chunks = array_chunk($excelData, $chunkSize);
        
        foreach ($chunks as $chunk) {
            $jobs[] = (new $importJobClass($chunk, $columns, company()));
        }

        // Use 'sync' connection to process jobs immediately without queue worker
        $batch = Bus::batch($jobs)->onConnection('sync')->onQueue($importClassName)->name($importClassName)->dispatch();

        Files::deleteFile($file, Files::IMPORT_FOLDER);

        return $batch;
    }

}
