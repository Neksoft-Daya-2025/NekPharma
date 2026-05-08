<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
/**
 * Excel layout aligned with Leave Formatt.xlsx:
 * Row 1: Employee ID, Name, Designation, Department, DOJ | Total Leaves Year (CL,SL,EL) | Leaves per month | [each month MMM-yy × 6 cols]
 * Row 2: CL, SL, EL under year totals | (I–K empty) | per month: Leaves (3 merged) + Availed (3 merged)
 * Row 3: empty A–E | F–H note | I–K empty | per month: CL, SL, EL under Leaves + CL, SL, EL under Availed
 * Row 4+: one row per employee.
 */
class EmployeeLeaveReportExport implements FromArray, WithEvents
{
    /** @var array<int, array<int, mixed>> */
    private array $rows;

    /** @var array<int, string> merge ranges */
    private array $mergeRanges = [];

    private int $dataColCount;

    private int $headerRows = 3;

    public function __construct(array $payload)
    {
        $this->buildSheet($payload);
    }

    /**
     * @param  array{
     *   leave_from: Carbon,
     *   months: array<int, array{label: string, start: Carbon, end: Carbon}>,
     *   slots: array<int, array{id: int|null, label: string}>,
     *   employees: array<int, array{
     *     employee_code: string,
     *     name: string,
     *     designation: string,
     *     department: string,
     *     doj: string,
     *     annual: array<int, float|int|string>,
     *     per_month_row: array<int, float|int|string>,
     *     monthly: array<int, array{array{float|int}, array{float|int}}>
     *   }>
     * }  $payload
     */
    private function buildSheet(array $payload): void
    {
        $months = $payload['months'];
        $slots = $payload['slots'];
        $slotCount = 3;
        $monthCount = count($months);

        // A–E + F–H + I–K + monthCount * 6
        $this->dataColCount = 5 + $slotCount + $slotCount + ($monthCount * 6);

        $empty = array_fill(0, $this->dataColCount, '');

        // Row 1
        $r1 = $empty;
        $r1[0] = 'Employee ID';
        $r1[1] = 'Employee Name';
        $r1[2] = 'Designation';
        $r1[3] = 'Department';
        $r1[4] = 'DOJ';
        $r1[5] = 'Total Leaves this Year';
        $r1[8] = 'Leaves per month';

        $col = 11; // 0-based index 11 = column L
        foreach ($months as $m) {
            $r1[$col] = $m['label'];
            $col += 6;
        }

        // Row 2 (CL / SL / EL labels match reference template)
        $r2 = $empty;
        $r2[5] = 'CL';
        $r2[6] = 'SL';
        $r2[7] = 'EL';
        $col = 11;
        foreach ($months as $_) {
            $r2[$col] = 'Leaves';
            $r2[$col + 3] = 'Availed';
            $col += 6;
        }

        // Row 3
        $r3 = $empty;
        $r3[5] = 'Calculated according to the DOJ';
        $col = 11;
        foreach ($months as $_) {
            $r3[$col] = 'CL';
            $r3[$col + 1] = 'SL';
            $r3[$col + 2] = 'EL';
            $r3[$col + 3] = 'CL';
            $r3[$col + 4] = 'SL';
            $r3[$col + 5] = 'EL';
            $col += 6;
        }

        $this->rows = [$r1, $r2, $r3];

        foreach ($payload['employees'] as $emp) {
            $row = $empty;
            $row[0] = $emp['employee_code'];
            $row[1] = $emp['name'];
            $row[2] = $emp['designation'];
            $row[3] = $emp['department'];
            $row[4] = $emp['doj'];
            $row[5] = $emp['annual'][0];
            $row[6] = $emp['annual'][1];
            $row[7] = $emp['annual'][2];
            $row[8] = $emp['per_month_row'][0];
            $row[9] = $emp['per_month_row'][1];
            $row[10] = $emp['per_month_row'][2];

            $col = 11;
            foreach ($emp['monthly'] as $block) {
                $leaves = $block[0];
                $availed = $block[1];
                $row[$col] = $leaves[0];
                $row[$col + 1] = $leaves[1];
                $row[$col + 2] = $leaves[2];
                $row[$col + 3] = $availed[0];
                $row[$col + 4] = $availed[1];
                $row[$col + 5] = $availed[2];
                $col += 6;
            }

            $this->rows[] = $row;
        }

        $this->buildMergeRanges($monthCount);
    }

    private function buildMergeRanges(int $monthCount): void
    {
        // Row 1: F1:H1, I1:K1, then each month 6 cols
        $this->mergeRanges[] = 'F1:H1';
        $this->mergeRanges[] = 'I1:K1';

        $colIdx = 12; // L = 12
        for ($m = 0; $m < $monthCount; $m++) {
            $c1 = Coordinate::stringFromColumnIndex($colIdx);
            $c2 = Coordinate::stringFromColumnIndex($colIdx + 5);
            $this->mergeRanges[] = "{$c1}1:{$c2}1";
            $colIdx += 6;
        }

        // Row 2: per month Leaves (3 cols) + Availed (3 cols)
        $colIdx = 12;
        for ($m = 0; $m < $monthCount; $m++) {
            $c1 = Coordinate::stringFromColumnIndex($colIdx);
            $c2 = Coordinate::stringFromColumnIndex($colIdx + 2);
            $this->mergeRanges[] = "{$c1}2:{$c2}2";
            $c3 = Coordinate::stringFromColumnIndex($colIdx + 3);
            $c4 = Coordinate::stringFromColumnIndex($colIdx + 5);
            $this->mergeRanges[] = "{$c3}2:{$c4}2";
            $colIdx += 6;
        }

        // Row 3: F3:H3 note
        $this->mergeRanges[] = 'F3:H3';
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach ($this->mergeRanges as $range) {
                    $sheet->mergeCells($range);
                }

                $lastColLetter = Coordinate::stringFromColumnIndex($this->dataColCount);
                $lastRow = count($this->rows);

                // Column widths (approximate template)
                $sheet->getColumnDimension('A')->setWidth(14);
                $sheet->getColumnDimension('B')->setWidth(22);
                $sheet->getColumnDimension('C')->setWidth(16);
                $sheet->getColumnDimension('D')->setWidth(18);
                $sheet->getColumnDimension('E')->setWidth(12);
                for ($c = 6; $c <= $this->dataColCount; $c++) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setWidth(10);
                }

                // Row 1 header style
                $sheet->getStyle("A1:{$lastColLetter}1")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'D9D9D9'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Row 2–3
                $sheet->getStyle("A2:{$lastColLetter}3")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F2F2F2'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Data rows
                if ($lastRow > $this->headerRows) {
                    $sheet->getStyle("A4:{$lastColLetter}{$lastRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '000000'],
                            ],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                    $sheet->getStyle("A4:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }

                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->freezePane('A4');
            },
        ];
    }
}
