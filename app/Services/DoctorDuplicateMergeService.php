<?php

namespace App\Services;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DoctorDuplicateMergeService
{
    /** @var list<string> */
    protected array $mergeableAttributes = [
        'fullname', 'email', 'qualification', 'speciality', 'mobile',
        'dob', 'dom', 'area', 'area_id', 'headquarter_id', 'exstation_id', 'outstation_id',
        'gender', 'doctor_type', 'address', 'doctor_pic', 'msl_number', 'latitude', 'longitude',
    ];

    /**
     * Build duplicate groups: same normalized mobile (10+ digits), else same name + headquarter.
     *
     * @param  Builder|Collection<int, Doctor>  $doctors
     * @return Collection<int, Collection<int, Doctor>>
     */
    public function findDuplicateGroups($doctors): Collection
    {
        $list = $doctors instanceof Builder ? $doctors->get() : $doctors;

        $buckets = [];
        foreach ($list as $doctor) {
            $key = $this->duplicateKey($doctor);
            if (! isset($buckets[$key])) {
                $buckets[$key] = [];
            }
            $buckets[$key][] = $doctor;
        }

        return collect($buckets)
            ->filter(fn ($group) => count($group) > 1)
            ->values()
            ->map(fn ($group) => collect($group));
    }

    public function duplicateKey(Doctor $d): string
    {
        $digits = preg_replace('/\D/', '', (string) $d->mobile);
        if (strlen($digits) >= 10) {
            return 'm:'.substr($digits, -10);
        }

        $name = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $d->fullname)));
        $hq = (string) ($d->headquarter_id ?? '0');

        return 'n:'.$name.'|hq:'.$hq;
    }

    public function completenessScore(Doctor $d): int
    {
        $score = 0;
        foreach ($this->mergeableAttributes as $attr) {
            $v = $d->getAttribute($attr);
            if ($v !== null && $v !== '') {
                $score++;
            }
        }

        return $score;
    }

    /**
     * Merge one group into a single doctor (best-filled wins; ties: lowest id).
     *
     * @param  Collection<int, Doctor>  $group
     */
    public function mergeGroup(Collection $group): array
    {
        if ($group->count() < 2) {
            return ['merged' => false, 'message' => 'Group has fewer than 2 doctors'];
        }

        $sorted = $group->sortBy(function (Doctor $d) {
            return [-$this->completenessScore($d), $d->id];
        })->values();

        /** @var Doctor $winner */
        $winner = $sorted->first();
        $losers = $sorted->slice(1);

        return DB::transaction(function () use ($winner, $losers) {
            $mergedIds = [];

            foreach ($losers as $loser) {
                $this->mergeScalarFields($winner, $loser);
            }
            $winner->save();

            foreach ($losers as $loser) {
                $this->mergeDoctorProducts($winner, $loser);
                $this->mergeSfcChartLinks($winner, $loser);
                $this->reassignForeignKeys($winner->id, $loser->id);
                $loser->delete();
                $mergedIds[] = $loser->id;
            }

            return [
                'merged' => true,
                'kept_doctor_id' => $winner->id,
                'removed_doctor_ids' => $mergedIds,
            ];
        });
    }

    /**
     * @param  Collection<int, Collection<int, Doctor>>  $groups
     */
    public function mergeAllGroups(Collection $groups): array
    {
        $totalRemoved = 0;
        $groupsProcessed = 0;

        foreach ($groups as $group) {
            $result = $this->mergeGroup($group);
            if (! empty($result['merged'])) {
                $groupsProcessed++;
                $totalRemoved += count($result['removed_doctor_ids'] ?? []);
            }
        }

        return [
            'groups_merged' => $groupsProcessed,
            'doctors_removed' => $totalRemoved,
        ];
    }

    protected function mergeScalarFields(Doctor $winner, Doctor $loser): void
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

    protected function mergeDoctorProducts(Doctor $winner, Doctor $loser): void
    {
        if (! Schema::hasTable('doctor_products')) {
            return;
        }

        $productIds = DB::table('doctor_products')
            ->where('doctor_id', $loser->id)
            ->pluck('product_id');

        foreach ($productIds as $pid) {
            $exists = DB::table('doctor_products')
                ->where('doctor_id', $winner->id)
                ->where('product_id', $pid)
                ->exists();
            if (! $exists) {
                DB::table('doctor_products')->insert([
                    'doctor_id' => $winner->id,
                    'product_id' => $pid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('doctor_products')->where('doctor_id', $loser->id)->delete();
    }

    protected function mergeSfcChartLinks(Doctor $winner, Doctor $loser): void
    {
        if (! Schema::hasTable('sfc_chart_item_doctors')) {
            return;
        }

        $rows = DB::table('sfc_chart_item_doctors')->where('doctor_id', $loser->id)->get();

        foreach ($rows as $row) {
            $exists = DB::table('sfc_chart_item_doctors')
                ->where('sfc_chart_item_id', $row->sfc_chart_item_id)
                ->where('doctor_id', $winner->id)
                ->exists();
            if (! $exists) {
                DB::table('sfc_chart_item_doctors')->insert([
                    'sfc_chart_item_id' => $row->sfc_chart_item_id,
                    'doctor_id' => $winner->id,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('sfc_chart_item_doctors')->where('doctor_id', $loser->id)->delete();
    }

    protected function reassignForeignKeys(int $winnerId, int $loserId): void
    {
        if (Schema::hasTable('dcr_reports')) {
            DB::table('dcr_reports')->where('doctor_id', $loserId)->update(['doctor_id' => $winnerId]);
        }
        if (Schema::hasTable('dcr_doctor_visits')) {
            DB::table('dcr_doctor_visits')->where('doctor_id', $loserId)->update(['doctor_id' => $winnerId]);
        }
    }
}
