<?php

namespace App\Services;

use App\Models\Chemist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ChemistDuplicateMergeService
{
    /** @var list<string> */
    protected array $mergeableAttributes = [
        'shopname', 'fullname', 'email', 'mobile', 'dob', 'dom', 'area', 'area_id',
        'headquarter_id', 'exstation_id', 'outstation_id', 'gender', 'address',
        'chemist_pic', 'msl_number', 'latitude', 'longitude',
    ];

    /**
     * @param  Builder|Collection<int, Chemist>  $chemists
     * @return Collection<int, Collection<int, Chemist>>
     */
    public function findDuplicateGroups($chemists): Collection
    {
        $list = $chemists instanceof Builder ? $chemists->get() : $chemists;

        $buckets = [];
        foreach ($list as $chemist) {
            $key = $this->duplicateKey($chemist);
            if (! isset($buckets[$key])) {
                $buckets[$key] = [];
            }
            $buckets[$key][] = $chemist;
        }

        return collect($buckets)
            ->filter(fn ($group) => count($group) > 1)
            ->values()
            ->map(fn ($group) => collect($group));
    }

    public function duplicateKey(Chemist $c): string
    {
        $digits = preg_replace('/\D/', '', (string) $c->mobile);
        if (strlen($digits) >= 10) {
            return 'm:'.substr($digits, -10);
        }

        $shop = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $c->shopname)));
        $hq = (string) ($c->headquarter_id ?? '0');

        return 's:'.$shop.'|hq:'.$hq;
    }

    public function completenessScore(Chemist $c): int
    {
        $score = 0;
        foreach ($this->mergeableAttributes as $attr) {
            $v = $c->getAttribute($attr);
            if ($v !== null && $v !== '') {
                $score++;
            }
        }

        return $score;
    }

    /**
     * @param  Collection<int, Chemist>  $group
     */
    public function mergeGroup(Collection $group): array
    {
        if ($group->count() < 2) {
            return ['merged' => false, 'message' => 'Group has fewer than 2 chemists'];
        }

        $sorted = $group->sortBy(function (Chemist $c) {
            return [-$this->completenessScore($c), $c->id];
        })->values();

        /** @var Chemist $winner */
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
                'kept_chemist_id' => $winner->id,
                'removed_chemist_ids' => $mergedIds,
            ];
        });
    }

    /**
     * @param  Collection<int, Collection<int, Chemist>>  $groups
     */
    public function mergeAllGroups(Collection $groups): array
    {
        $totalRemoved = 0;
        $groupsProcessed = 0;

        foreach ($groups as $group) {
            $result = $this->mergeGroup($group);
            if (! empty($result['merged'])) {
                $groupsProcessed++;
                $totalRemoved += count($result['removed_chemist_ids'] ?? []);
            }
        }

        return [
            'groups_merged' => $groupsProcessed,
            'chemists_removed' => $totalRemoved,
        ];
    }

    protected function mergeScalarFields(Chemist $winner, Chemist $loser): void
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
            DB::table('dcr_reports')->where('chemist_id', $loserId)->update(['chemist_id' => $winnerId]);
        }
        if (Schema::hasTable('dcr_chemist_visits')) {
            DB::table('dcr_chemist_visits')->where('chemist_id', $loserId)->update(['chemist_id' => $winnerId]);
        }
    }
}
