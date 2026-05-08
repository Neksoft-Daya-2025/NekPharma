<?php

namespace App\Console\Commands;

use App\Services\PharmaAreaMappingSyncService;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SyncPharmaAreaMappingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pharma:sync-area-mapping
                            {file : Absolute or relative path to the .xlsx file}
                            {--company-id= : Company ID (required)}
                            {--sheet=0 : Sheet index (0-based) or sheet name}
                            {--header-row=1 : 1-based row number containing column headers}
                            {--zone-col= : Header text for Zone column (optional)}
                            {--region-col= : Header text for Region column}
                            {--area-col= : Header text for Area / HQ column}
                            {--default-zone= : When the sheet has no Zone column, assign all regions to this zone name}
                            {--forward-fill : Forward-fill blank Region/Zone from the last non-empty value (default: true)}
                            {--no-forward-fill : Do not forward-fill}
                            {--ci : Case-insensitive name matching (stored as normalized)}
                            {--dry-run : Run and roll back (no DB changes)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Upsert pharma zone / region / area hierarchy from an Excel mapping file (HQ column maps to Area).';

    /**
     * Execute the console command.
     */
    public function handle(PharmaAreaMappingSyncService $sync): int
    {
        $companyId = $this->option('company-id');
        if ($companyId === null || $companyId === '') {
            $this->error('Option --company-id is required.');

            return self::FAILURE;
        }

        $path = $this->argument('file');
        if (! is_string($path) || $path === '') {
            $this->error('File path is required.');

            return self::FAILURE;
        }

        $absolute = $this->resolvePath($path);
        if (! is_readable($absolute)) {
            $this->error('File is not readable: '.$absolute);

            return self::FAILURE;
        }

        $sheetOpt = $this->option('sheet') ?? '0';
        $headerRowOneBased = max(1, (int) ($this->option('header-row') ?? 1));

        $rows = $this->loadSheetRows($absolute, $sheetOpt, $headerRowOneBased);
        if ($rows === null) {
            return self::FAILURE;
        }

        $forwardFill = $this->option('no-forward-fill') ? false : true;

        $options = [
            'zone_col' => $this->option('zone-col') ?: null,
            'region_col' => $this->option('region-col') ?: null,
            'area_col' => $this->option('area-col') ?: null,
            'default_zone' => $this->option('default-zone') ?: null,
            'forward_fill' => $forwardFill,
            'case_insensitive' => (bool) $this->option('ci'),
            'dry_run' => (bool) $this->option('dry-run'),
        ];

        $stats = $sync->syncFromSheetData($rows, (int) $companyId, $options);

        $fatal = array_values(array_filter($stats['errors'], fn (string $e) => ! str_contains($e, 'skipped')));
        foreach ($fatal as $msg) {
            $this->error($msg);
        }
        foreach (array_diff($stats['errors'], $fatal) as $msg) {
            $this->warn($msg);
        }

        $this->info('Zones created: '.$stats['zones_created']);
        $this->info('Regions created: '.$stats['regions_created']);
        $this->info('Areas created: '.$stats['areas_created']);
        $this->info('Rows skipped: '.$stats['rows_skipped']);
        $this->info('Records restored (from soft-delete): '.$stats['restored']);

        if ($options['dry_run']) {
            $this->comment('Dry run: database changes were rolled back.');
        }

        return $fatal !== [] ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<list<mixed>>|null
     */
    private function loadSheetRows(string $absolute, string $sheetOpt, int $headerRowOneBased): ?array
    {
        try {
            $reader = IOFactory::createReaderForFile($absolute);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($absolute);
        } catch (\Throwable $e) {
            $this->error('Could not read spreadsheet: '.$e->getMessage());

            return null;
        }

        if (is_numeric($sheetOpt)) {
            $index = (int) $sheetOpt;
            if ($index < 0 || $index >= $spreadsheet->getSheetCount()) {
                $this->error('Sheet index '.$index.' is out of range.');

                return null;
            }
            $sheet = $spreadsheet->getSheet($index);
        } else {
            $sheet = $spreadsheet->getSheetByName((string) $sheetOpt);
            if ($sheet === null) {
                $this->error('Sheet not found: '.$sheetOpt);

                return null;
            }
        }

        $data = $sheet->toArray(null, true, true, false);
        $startIdx = $headerRowOneBased - 1;
        if ($startIdx < 0 || $startIdx >= count($data)) {
            $this->error('Header row '.$headerRowOneBased.' is outside the sheet.');

            return null;
        }

        return array_slice($data, $startIdx);
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:\\\\/', $path)) {
            return $path;
        }

        return base_path($path);
    }
}
