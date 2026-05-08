<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pharma Module Configurations
    |--------------------------------------------------------------------------
    |
    | These values help identify which designation represents Area Managers
    | and Regional Managers. This avoids hard-coding text names in your logic.
    |
    */

    'abm_designation_id' => env('ABM_DESIGNATION_ID', 0), // Area Business Manager
    'rbm_designation_id' => env('RBM_DESIGNATION_ID', 0), // Regional Business Manager
];

