<?php

namespace App\Observers;

use App\Models\EmployeeFnfSettlement;

class EmployeeFnfSettlementObserver
{
    public function creating(EmployeeFnfSettlement $fnf)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $fnf->added_by = user()->id;
        }
    }

    public function saving(EmployeeFnfSettlement $fnf)
    {
        if (!isRunningInConsoleOrSeeding()) {
            $fnf->last_updated_by = user()->id;
        }
    }
}

