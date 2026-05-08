<?php

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

class RawReader implements ToArray
{
    public function array(array $array): array
    {
        return $array;
    }
}

try {
    $data = Excel::toArray(new RawReader, 'doc.xls');
    if (!empty($data) && !empty($data[0])) {
        echo "Raw Data Dump (First 10 rows):\n";
        $rows = array_slice($data[0], 0, 10);
        foreach ($rows as $index => $row) {
            echo "Row $index: " . json_encode($row) . "\n";
        }
    } else {
        echo "No data found in Excel file.\n";
    }
} catch (Exception $e) {
    echo "Error reading file: " . $e->getMessage() . "\n";
}
