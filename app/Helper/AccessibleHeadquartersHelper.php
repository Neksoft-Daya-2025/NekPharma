<?php

namespace App\Helper;

use App\Traits\AccessibleHeadquarters;

class AccessibleHeadquartersHelper
{
    use AccessibleHeadquarters;

    public static function getAccessibleHeadquarterIds($user = null): ?array
    {
        $instance = new self();
        return $instance->accessibleHeadquarterIds($user);
    }
}
