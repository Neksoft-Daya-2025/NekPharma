<?php

namespace App\Services;

use App\Models\Stockist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockistDuplicateMergeService
{
    /** @var list<string> */
    protected array $mergeableAttributes = [
        'shopname', 'fullname', 'email', 'mobile', 'dob', 'dom', 'area', 'area_id',
        'headquarter_id', 'exstation_id', 'outstation_id', 'gender', 'address',
        'owner_name', 'owner_mobile', 'employee_name', 'employee_mobile', 'stockist_pic',
        'dl_number', 'gst_number', 'msl_number', 'latitude', 'longitude',
    ];

    /**
     * @param  Builder|Collection<int, Stockist>  $stockists
     * @return Collection<int, Collection<int, Stockist>>
     */
    public function findDuplicateGroups($stockists): Collection
    {
        $list = $stockists instanceof Builder ? $stockists->get() : $stockists;

        $buckets = [];
        foreach ($list as $stockist) {
            $key = $this->duplicateKey($stockist);
            if (! isset($buckets[$key])) {
                $buckets[$key] = [];
            }
            $buckets[$key][] = $stockist;
        }

        return collect($buckets)
            ->filter(fn ($group) => count($group) > 1)
            ->values()
            ->map(fn ($group) => collect($group));
    }

    public function duplicateKey(Stockist $s): string
    {
        $digits = preg_replace('/\D/', '', (string) $s->mobile);
        if (strlen($digits) >= 10) {
            return 'm:'.substr($digits, -10);
        }

        $shop = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $s->shopname)));
        $hq = (string) ($s->headquarter_id ?? '0');

        return 's:'.$shop.'|hq:'.$hq;
    }

    public function completenessScore(Stockist $s): int
    {
        $score = 0;
        foreach ($this->mergeableAttributes as $attr) {
            $v = $s->getAttribute($attr);
            if ($v !== null && $v !== '') {
                $score++;
            }
        }

        return $score;
    }

    /**
     * @param  Collection<int, Stockist>  $group
     */
    public function mergeGroup(Collection $group): array
    {
        if ($group->count() < 2) {
            return ['merged' => false, 'message' => 'Group has fewer than 2 stockists'];
        }

        $sorted = $group->sortBy(function (Stockist $s) {
            return [-$this->completenessScore($s), $s->id];
        })->values();

        /** @var Stockist $winner */
        $winner = $sorted->first();
        $losers = $sorted->slice(1);

        return DB::transaction(function () use ($winner, $losers) {
            $mergedIds = [];

            foreach ($losers as $loser) {
                $this->mergeScalarFields($winner, $loser);
            }
            $winner->save();

            foreach ($losers as $loser) {
                $this->reassignForeignKeys($winner->id, $loser->id);
                $loser->delete();
                $mergedIds[] = $loser->id;
            }

            return [
                'merged' => true,
                'kept_stockist_id' => $winner->id,
                'removed_stockist_ids' => $mergedIds,
            ];
        });
    }

    /**
     * @param  Collection<int, Collection<int, Stockist>>  $groups
     */
    public function mergeAllGroups(Collection $groups): array
    {
        $totalRemoved = 0;
        $groupsProcessed = 0;

        foreach ($groups as $group) {
            $result = $this->mergeGroup($group);
            if (! empty($result['merged'])) {
                $groupsProcessed++;
                $totalRemoved += count($result['removed_stockist_ids'] ?? []);
            }
        }

        return [
            'groups_merged' => $groupsProcessed,
            'stockists_removed' => $totalRemoved,
        ];
    }

    protected function mergeScalarFields(Stockist $winner, Stockist $loser): void
    {
        foreach ($this->mergeableAttributes as $attr) {
            $w = $winner->getAttribute($attr);
            if ($w !== null && $w !== '') {
                continue;
            }
            $v = $loser->getAttribute($attr);
            if ($v !== null && $v !== '') {
                $winner->setAttribute($attr, $v);
            }
        }
    }

    protected function reassignForeignKeys(int $winnerId, int $loserId): void
    {
        if (Schema::hasTable('dcr_reports')) {
            DB::table('dcr_reports')->where('stockist_id', $loserId)->update(['stockist_id' => $winnerId]);
        }
        if (Schema::hasTable('dcr_stockist_visits')) {
            DB::table('dcr_stockist_visits')->where('stockist_id', $loserId)->update(['stockist_id' => $winnerId]);
        }
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'stockist_id')) {
            DB::table('invoices')->where('stockist_id', $loserId)->update(['stockist_id' => $winnerId]);
        }
    }
}
