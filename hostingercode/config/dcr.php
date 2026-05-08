<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DCR GPS / 100m Rule (SRS 3.2.5)
    |--------------------------------------------------------------------------
    | enforce_gps_100m: when true, DCR visit is rejected if employee is
    |   more than max_distance_meters from doctor/chemist/stockist (when
    |   both have lat/long). When false, only warn but allow save.
    | max_distance_meters: allowed distance in meters (default 100).
    */
    'enforce_gps_100m' => env('DCR_ENFORCE_GPS_100M', true),
    'max_distance_meters' => (int) env('DCR_MAX_DISTANCE_METERS', 100),

    /*
    |--------------------------------------------------------------------------
    | DCR Work types that enable Doctor/Chemist/Stockist calls (SRS 3.2.5)
    |--------------------------------------------------------------------------
    | When Work Type equals any of these, Doctor/Chemist/Stockist sections
    | are shown and visits are processed. Other work types: only Remarks.
    */
    'field_work_types' => ['Field Work', 'Working Day', 'Working Days'],
];
