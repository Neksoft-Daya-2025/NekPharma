<?php
$path = 'c:/Users/ASUS/Desktop/stock-statement-lines-sample.csv';
$handle = fopen($path, 'rb');
$line = 0;
while (($data = fgetcsv($handle)) !== false) {
    $line++;
    echo "Row $line: " . json_encode($data) . PHP_EOL;
}
fclose($handle);
