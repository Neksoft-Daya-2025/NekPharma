<?php

namespace App\Traits;

use App\Models\PharmaArea;
use App\Models\PharmaHeadquarter;

trait AccessibleHeadquarters
{
    private function safeDecode($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }

        return [];
    }

    private function accessibleHeadquarterIds(): ?array
    {
        $user = auth()->user();
        $emp  = $user->employeeDetail;

        if ($user->hasRole('admin')) {
            return null; // admin can see all HQs
        }

        if (!$emp) {
            return [];
        }

        $areaIds   = collect($this->safeDecode($emp->areas));
        $regionIds = collect($this->safeDecode($emp->regions));

        if ($regionIds->isNotEmpty()) {
            $regionAreaIds = PharmaArea::whereIn('region_id', $regionIds)->pluck('id');
            $areaIds = $areaIds->merge($regionAreaIds)->unique();
        }

        if ($areaIds->isEmpty()) {
            return [];
        }

        return PharmaHeadquarter::whereIn('area_id', $areaIds)->pluck('id')->toArray();
    }
}

