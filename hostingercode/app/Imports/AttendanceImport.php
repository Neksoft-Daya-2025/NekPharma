<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class AttendanceImport implements ToArray
{
    private $processedData = [];

    public static function fields(): array
    {
        $fields = [
            ['id' => 'employee_id', 'name' => __('app.employeeId'), 'required' => 'Yes'],
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

    public static function columnIdForHeading($heading): ?string
    {
        $heading = trim((string) $heading);
        $normalized = preg_replace('/[^a-z0-9]/', '', strtolower($heading));

        if ($normalized === 'employeeid') {
            return 'employee_id';
        }

        if ($normalized === 'month') {
            return 'month';
        }

        if (preg_match('/^\d{4}-\d{2}-(\d{2})$/', $heading, $matches)) {
            $day = (int) $matches[1];

            return $day >= 1 && $day <= 31 ? 'day_' . $day : null;
        }

        if (preg_match('/^(?:day)?0?([1-9]|[12][0-9]|3[01])$/', $normalized, $matches)) {
            return 'day_' . (int) $matches[1];
        }

        return null;
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
