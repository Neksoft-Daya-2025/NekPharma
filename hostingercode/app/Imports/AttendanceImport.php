<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class AttendanceImport implements ToArray
{
    private $processedData = [];

    public static function fields(): array
    {
        $fields = [
            ['id' => 'email', 'name' => __('app.email'), 'required' => 'Yes'],
            ['id' => 'month', 'name' => 'Month (YYYY-MM)', 'required' => 'Yes'],
        ];

        for ($day = 1; $day <= 31; $day++) {
            $fields[] = [
                'id' => 'day_' . $day,
                'name' => 'Day ' . $day,
                'required' => 'No',
                'aliases' => [
                    (string) $day,
                    str_pad((string) $day, 2, '0', STR_PAD_LEFT),
                ],
            ];
        }

        return $fields;
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
