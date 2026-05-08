<?php

namespace App\Support;

use App\Models\PharmaArea;
use App\Models\PharmaHeadquarter;
use App\Models\PharmaRegion;
use App\Models\PharmaZone;

class MasterAreaMapGraphBuilder
{
    /** @var list<array{id:string,label:string,group:string,title:string,meta:array}> */
    protected array $nodes = [];

    /** @var list<array{from:string,to:string}> */
    protected array $edges = [];

    /** @var array<string, true> */
    protected array $nodeIdsAdded = [];

    protected bool $hasOrphanRegions = false;

    protected bool $hasOrphanAreas = false;

    protected bool $hasOrphanHeadquarters = false;

    public function __construct(
        protected int $companyId,
        protected string $companyName
    ) {}

    /**
     * @return array{nodes: array, edges: array, stats: array, nodesMeta: array<string, array>}
     */
    public function build(): array
    {
        $this->nodes = [];
        $this->edges = [];
        $this->nodeIdsAdded = [];

        $this->addNode('cr_root', $this->companyName, 'root', 'Company root', [
            'type' => 'root',
            'entityId' => null,
            'name' => $this->companyName,
        ]);

        $zones = PharmaZone::where('company_id', $this->companyId)
            ->with([
                'regions.areas.headquarters.exstations',
                'regions.areas.headquarters.outstations',
            ])
            ->orderBy('name')
            ->get();

        foreach ($zones as $zone) {
            $zId = 'z_'.$zone->id;
            $this->addNode($zId, $zone->name, 'zone', 'Zone #'.$zone->id, [
                'type' => 'zone',
                'entityId' => $zone->id,
                'name' => $zone->name,
            ]);
            $this->addEdge('cr_root', $zId);

            foreach ($zone->regions as $region) {
                $this->addRegionSubtree($region, $zId);
            }
        }

        $orphanRegions = PharmaRegion::where('company_id', $this->companyId)
            ->whereNull('zone_id')
            ->with(['areas.headquarters.exstations', 'areas.headquarters.outstations'])
            ->orderBy('name')
            ->get();

        if ($orphanRegions->isNotEmpty()) {
            $this->hasOrphanRegions = true;
            $bucket = 'b_orphan_regions';
            $this->addNode($bucket, 'Unassigned regions', 'bucket', 'Regions not linked to a zone', [
                'type' => 'bucket',
                'entityId' => null,
                'name' => 'Unassigned regions',
            ]);
            $this->addEdge('cr_root', $bucket);
            foreach ($orphanRegions as $region) {
                $this->addRegionSubtree($region, $bucket);
            }
        }

        $orphanAreas = PharmaArea::where('company_id', $this->companyId)
            ->whereNull('region_id')
            ->with(['headquarters.exstations', 'headquarters.outstations'])
            ->orderBy('name')
            ->get();

        if ($orphanAreas->isNotEmpty()) {
            $this->hasOrphanAreas = true;
            $bucket = 'b_orphan_areas';
            $this->addNode($bucket, 'Unassigned areas', 'bucket', 'Areas not linked to a region', [
                'type' => 'bucket',
                'entityId' => null,
                'name' => 'Unassigned areas',
            ]);
            $this->addEdge('cr_root', $bucket);
            foreach ($orphanAreas as $area) {
                $this->addAreaSubtree($area, $bucket);
            }
        }

        $orphanHqs = PharmaHeadquarter::where('company_id', $this->companyId)
            ->whereNull('area_id')
            ->with(['exstations', 'outstations'])
            ->orderBy('name')
            ->get();

        if ($orphanHqs->isNotEmpty()) {
            $this->hasOrphanHeadquarters = true;
            $bucket = 'b_orphan_hq';
            $this->addNode($bucket, 'Unassigned headquarters', 'bucket', 'Headquarters not linked to an area', [
                'type' => 'bucket',
                'entityId' => null,
                'name' => 'Unassigned headquarters',
            ]);
            $this->addEdge('cr_root', $bucket);
            foreach ($orphanHqs as $hq) {
                $this->addHeadquarterSubtree($hq, $bucket);
            }
        }

        $nodesMeta = [];
        foreach ($this->nodes as $n) {
            $nodesMeta[$n['id']] = $n['meta'];
        }

        $stats = [
            'zones' => $zones->count(),
            'regions' => PharmaRegion::where('company_id', $this->companyId)->count(),
            'areas' => PharmaArea::where('company_id', $this->companyId)->count(),
            'headquarters' => PharmaHeadquarter::where('company_id', $this->companyId)->count(),
            'exstations' => \App\Models\PharmaExstation::where('company_id', $this->companyId)->count(),
            'outstations' => \App\Models\PharmaOutstation::where('company_id', $this->companyId)->count(),
        ];

        return [
            'nodes' => $this->nodes,
            'edges' => $this->edges,
            'stats' => $stats,
            'nodesMeta' => $nodesMeta,
        ];
    }

    protected function addRegionSubtree($region, string $parentId): void
    {
        $rId = 'r_'.$region->id;
        $this->addNode($rId, $region->name, 'region', 'Region #'.$region->id, [
            'type' => 'region',
            'entityId' => $region->id,
            'name' => $region->name,
            'childCounts' => [
                'areas' => $region->areas->count(),
            ],
        ]);
        $this->addEdge($parentId, $rId);

        foreach ($region->areas as $area) {
            $this->addAreaSubtree($area, $rId);
        }
    }

    protected function addAreaSubtree($area, string $parentId): void
    {
        $aId = 'a_'.$area->id;
        $this->addNode($aId, $area->name, 'area', 'Area #'.$area->id, [
            'type' => 'area',
            'entityId' => $area->id,
            'name' => $area->name,
            'childCounts' => [
                'headquarters' => $area->headquarters->count(),
            ],
        ]);
        $this->addEdge($parentId, $aId);

        foreach ($area->headquarters as $hq) {
            $this->addHeadquarterSubtree($hq, $aId);
        }
    }

    protected function addHeadquarterSubtree($hq, string $parentId): void
    {
        $hId = 'h_'.$hq->id;
        $exCount = $hq->exstations->count();
        $outCount = $hq->outstations->count();
        $this->addNode($hId, $hq->name, 'headquarter', 'Headquarter #'.$hq->id, [
            'type' => 'headquarter',
            'entityId' => $hq->id,
            'name' => $hq->name,
            'childCounts' => [
                'exstations' => $exCount,
                'outstations' => $outCount,
            ],
        ]);
        $this->addEdge($parentId, $hId);

        foreach ($hq->exstations as $ex) {
            // One vis node per station record; edges can attach multiple HQs if pivot allows it.
            $xId = 'x_'.$ex->id;
            $this->addNode($xId, $ex->name, 'exstation', 'Ex-Station #'.$ex->id.' @ '.$hq->name, [
                'type' => 'exstation',
                'entityId' => $ex->id,
                'name' => $ex->name,
                'headquarterId' => $hq->id,
            ]);
            $this->addEdge($hId, $xId);
        }

        foreach ($hq->outstations as $out) {
            $oId = 'o_'.$out->id;
            $this->addNode($oId, $out->name, 'outstation', 'Out-Station #'.$out->id.' @ '.$hq->name, [
                'type' => 'outstation',
                'entityId' => $out->id,
                'name' => $out->name,
                'headquarterId' => $hq->id,
            ]);
            $this->addEdge($hId, $oId);
        }
    }

    protected function addNode(string $id, string $label, string $group, string $title, array $meta): void
    {
        if (isset($this->nodeIdsAdded[$id])) {
            return;
        }
        $this->nodeIdsAdded[$id] = true;
        $this->nodes[] = [
            'id' => $id,
            'label' => $this->truncateLabel($label),
            'group' => $group,
            'title' => $title,
            'meta' => $meta,
        ];
    }

    protected function truncateLabel(string $label, int $max = 52): string
    {
        $label = trim($label);

        return mb_strlen($label) > $max ? mb_substr($label, 0, $max - 1).'…' : $label;
    }

    protected function addEdge(string $from, string $to): void
    {
        $this->edges[] = ['from' => $from, 'to' => $to];
    }
}
