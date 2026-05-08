<?php

use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

class HeaderReader implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // We only need headers, which are processed by WithHeadingRow
    }
}

try {
    $data = Excel::toArray(new HeaderReader, 'Dr list ryva.xls');
    if (!empty($data) && !empty($data[0])) {
        $firstRow = $data[0][0]; // First sheet, first row of data (which has keys from headers)
        echo "Headers: " . implode(", ", array_keys($firstRow)) . "\n";
        echo "First Row Data: " . json_encode($firstRow) . "\n";
    } else {
        echo "No data found in Excel file.\n";
    }
} catch (Exception $e) {
    echo "Error reading file: " . $e->getMessage() . "\n";
}
