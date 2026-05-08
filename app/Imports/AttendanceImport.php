<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class AttendanceImport implements ToArray
{
    private $processedData = [];

    public static function fields(): array
    {
        return array(
            array('id' => 'email',  'name' => __('app.email'), 'required' => 'Yes'),
            array('id' => 'date',   'name' => __('app.date') . ' (YYYY-MM-DD)', 'required' => 'Yes'),
            array('id' => 'status', 'name' => 'Status (present / absent / half_day / late)', 'required' => 'Yes'),
        );
    }

    public function array(array $array): array
    {
        $this->processedData = $array;

        return $array;
    }

    public function getProcessedData()
    {
        return $this->processedData;
    }

}

