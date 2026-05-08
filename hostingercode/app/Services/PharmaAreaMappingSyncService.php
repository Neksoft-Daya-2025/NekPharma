<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PharmaArea;
use App\Models\PharmaRegion;
use App\Models\PharmaZone;
use Illuminate\Support\Facades\DB;

class PharmaAreaMappingSyncService
{
    /** @var array<string, true> */
    private const ZONE_ALIASES = ['zone' => true, 'zonal' => true, 'zone name' => true];

    /** @var array<string, true> */
    private const REGION_ALIASES = ['region' => true, 'rbm region' => true, 'state' => true];

    /** @var array<string, true> */
    private const AREA_ALIASES = ['area' => true, 'hq' => true, 'headquarter' => true, 'head quarter' => true, 'headquarters' => true, 'abm area' => true, 'territory' => true];

    /**
     * @param  array<int, array<int, mixed>>  $rawRows  First row = headers; following rows = data (0-based).
     * @param  array{
     *   zone_col?: string|null,
     *   region_col?: string|null,
     *   area_col?: string|null,
     *   default_zone?: string|null,
     *   forward_fill?: bool,
     *   case_insensitive?: bool,
     *   dry_run?: bool
     * }  $options
     * @return array{
     *   zones_created: int,
     *   regions_created: int,
     *   areas_created: int,
     *   rows_skipped: int,
     *   restored: int,
     *   errors: list<string>
     * }
     */
    public function syncFromSheetData(array $rawRows, int $companyId, array $options = []): array
    {
        Company::query()->findOrFail($companyId);

        if ($rawRows === []) {
            return $this->emptyStats(['No rows in sheet.']);
        }

        $headerRow = array_shift($rawRows);
        $headers = $this->normalizeHeaderRow($headerRow);

        $forwardFill = $options['forward_fill'] ?? true;
        $ci = $options['case_insensitive'] ?? false;
        $dryRun = $options['dry_run'] ?? false;

        $zoneIdx = $this->resolveColumnIndex($headers, $options['zone_col'] ?? null, self::ZONE_ALIASES);
        $regionIdx = $this->resolveColumnIndex($headers, $options['region_col'] ?? null, self::REGION_ALIASES);
        $areaIdx = $this->resolveColumnIndex($headers, $options['area_col'] ?? null, self::AREA_ALIASES);

        $errors = [];
        if ($regionIdx === null) {
            $errors[] = 'Could not resolve Region column. Set --region-col= to match your header row.';
        }
        if ($areaIdx === null) {
            $errors[] = 'Could not resolve Area column (try --area-col=HQ or Area).';
        }
        if ($errors !== []) {
            return $this->emptyStats($errors);
        }

        $defaultZoneRaw = isset($options['default_zone']) ? trim((string) $options['default_zone']) : null;

        $stats = [
            'zones_created' => 0,
            'regions_created' => 0,
            'areas_created' => 0,
            'rows_skipped' => 0,
            'restored' => 0,
            'errors' => [],
        ];

        $run = function () use ($rawRows, $companyId, $zoneIdx, $regionIdx, $areaIdx, $forwardFill, $ci, $defaultZoneRaw, &$stats) {
            $lastZone = '';
            $lastRegion = '';

            $idxForPad = array_filter([$zoneIdx, $regionIdx, $areaIdx], fn ($x) => $x !== null);
            $minCells = ($idxForPad !== [] ? max($idxForPad) : 0) + 1;

            foreach ($rawRows as $rowIndex => $row) {
                $row = $this->padRow($row, $minCells);

                $zoneName = $zoneIdx !== null ? $this->cellString($row[$zoneIdx] ?? null) : '';
                $regionName = $regionIdx !== null ? $this->cellString($row[$regionIdx] ?? null) : '';
                $areaName = $this->cellString($row[$areaIdx] ?? null);

                if ($zoneIdx !== null && $zoneName !== '') {
                    $lastZone = $zoneName;
                } elseif ($zoneIdx !== null && $forwardFill && $lastZone !== '') {
                    $zoneName = $lastZone;
                }

                if ($defaultZoneRaw !== null && $zoneIdx === null) {
                    $zoneName = $defaultZoneRaw;
                }

                if ($regionName !== '') {
                    $lastRegion = $regionName;
                } elseif ($forwardFill && $lastRegion !== '') {
                    $regionName = $lastRegion;
                }

                if ($areaName === '') {
                    $stats['rows_skipped']++;

                    continue;
                }

                if ($regionName === '') {
                    $stats['rows_skipped']++;
                    $stats['errors'][] = 'Row '.($rowIndex + 2).': Area "'.$areaName.'" skipped (no Region; add an RBM row with Region or disable --no-forward-fill).';

                    continue;
                }

                $zoneNameNorm = $this->normalizeName($zoneName, $ci);
                $regionNameNorm = $this->normalizeName($regionName, $ci);
                $areaNameNorm = $this->normalizeName($areaName, $ci);

                if ($zoneIdx !== null && $zoneNameNorm === '') {
                    $stats['rows_skipped']++;
                    $stats['errors'][] = 'Row '.($rowIndex + 2).': missing Zone for area "'.$areaNameNorm.'".';

                    continue;
                }

                $zoneId = null;
                if ($zoneNameNorm !== '') {
                    $z = $this->upsertZone($companyId, $zoneNameNorm);
                    $stats['restored'] += $z['restored'] ? 1 : 0;
                    $stats['zones_created'] += $z['created'] ? 1 : 0;
                    $zoneId = $z['id'];
                }

                $r = $this->upsertRegion($companyId, $zoneId, $regionNameNorm);
                $stats['restored'] += $r['restored'] ? 1 : 0;
                $stats['regions_created'] += $r['created'] ? 1 : 0;

                $a = $this->upsertArea($companyId, $r['id'], $areaNameNorm);
                $stats['restored'] += $a['restored'] ? 1 : 0;
                $stats['areas_created'] += $a['created'] ? 1 : 0;
            }
        };

        if ($dryRun) {
            DB::beginTransaction();
            try {
                $run();
                DB::rollBack();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        } else {
            DB::transaction($run);
        }

        return $stats;
    }

    /**
     * @param  array<int, mixed>  $row
     * @return array<int, mixed>
     */
    private function padRow(array $row, int $minLength): array
    {
        $c = count($row);
        if ($c >= $minLength) {
            return $row;
        }

        return array_pad($row, $minLength, null);
    }

    /**
     * @param  array<int, string>  $headerRow
     * @return array<int, string>
     */
    private function normalizeHeaderRow(array $headerRow): array
    {
        $out = [];
        foreach ($headerRow as $i => $h) {
            $out[$i] = $this->normalizeHeader($h);
        }

        return $out;
    }

    private function normalizeHeader(mixed $h): string
    {
        if ($h === null) {
            return '';
        }

        return trim((string) $h);
    }

    /**
     * @param  array<int, string>  $headers
     */
    private function resolveColumnIndex(array $headers, ?string $explicit, array $aliases): ?int
    {
        if ($explicit !== null && $explicit !== '') {
            $want = strtolower(trim($explicit));
            foreach ($headers as $i => $label) {
                if (strtolower($label) === $want) {
                    return $i;
                }
            }
            foreach ($headers as $i => $label) {
                if ($label !== '' && str_contains(strtolower($label), $want)) {
                    return $i;
                }
            }

            return null;
        }

        foreach ($headers as $i => $label) {
            $l = strtolower($label);
            foreach (array_keys($aliases) as $alias) {
                if ($l === $alias || str_contains($l, $alias)) {
                    return $i;
                }
            }
        }

        return null;
    }

    private function cellString(mixed $v): string
    {
        if ($v === null) {
            return '';
        }
        if (is_numeric($v)) {
            return trim((string) $v);
        }

        return trim((string) $v);
    }

    private function normalizeName(string $name, bool $ci): string
    {
        $n = preg_replace('/\s+/u', ' ', trim($name)) ?? '';

        return $ci ? mb_strtolower($n) : $n;
    }

    /**
     * @return array{id: int, created: bool, restored: bool}
     */
    private function upsertZone(int $companyId, string $name): array
    {
        $model = PharmaZone::withTrashed()->where('company_id', $companyId)->where('name', $name)->first();
        if ($model) {
            $wasTrashed = $model->trashed();
            if ($wasTrashed) {
                $model->restore();
            }

            return ['id' => $model->id, 'created' => false, 'restored' => $wasTrashed];
        }

        $created = PharmaZone::create(['company_id' => $companyId, 'name' => $name]);

        return ['id' => $created->id, 'created' => true, 'restored' => false];
    }

    /**
     * @return array{id: int, created: bool, restored: bool}
     */
    private function upsertRegion(int $companyId, ?int $zoneId, string $name): array
    {
        $model = PharmaRegion::withTrashed()
            ->where('company_id', $companyId)
            ->where('name', $name)
            ->where('zone_id', $zoneId)
            ->first();

        if ($model) {
            $wasTrashed = $model->trashed();
            if ($wasTrashed) {
                $model->restore();
            }

            return ['id' => $model->id, 'created' => false, 'restored' => $wasTrashed];
        }

        $created = PharmaRegion::create([
            'company_id' => $companyId,
            'zone_id' => $zoneId,
            'name' => $name,
        ]);

        return ['id' => $created->id, 'created' => true, 'restored' => false];
    }

    /**
     * @return array{id: int, created: bool, restored: bool}
     */
    private function upsertArea(int $companyId, int $regionId, string $name): array
    {
        $model = PharmaArea::withTrashed()
            ->where('company_id', $companyId)
            ->where('region_id', $regionId)
            ->where('name', $name)
            ->first();

        if ($model) {
            $wasTrashed = $model->trashed();
            if ($wasTrashed) {
                $model->restore();
            }

            return ['id' => $model->id, 'created' => false, 'restored' => $wasTrashed];
        }

        $created = PharmaArea::create([
            'company_id' => $companyId,
            'region_id' => $regionId,
            'name' => $name,
        ]);

        return ['id' => $created->id, 'created' => true, 'restored' => false];
    }

    /**
     * @param  list<string>  $errors
     * @return array{zones_created: int, regions_created: int, areas_created: int, rows_skipped: int, restored: int, errors: list<string>}
     */
    private function emptyStats(array $errors): array
    {
        return [
            'zones_created' => 0,
            'regions_created' => 0,
            'areas_created' => 0,
            'rows_skipped' => 0,
            'restored' => 0,
            'errors' => $errors,
        ];
    }
}
