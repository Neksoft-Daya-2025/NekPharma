<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class DoctorImport implements ToArray
{
    private $processedData = [];

    public static function fields(): array
    {
        return array(
            array(
                'id' => 'fullname',
                'name' => __('Dr. Name'),
                'required' => 'Yes',
                'aliases' => ['Doctor Name', 'Dr Name', 'Dr. Name'],
            ),
            array(
                'id' => 'headquarter',
                'name' => __('HQ'),
                'required' => 'Yes',
                'aliases' => ['HQ', 'Headquarter', 'Headquarters'],
            ),
            array(
                'id' => 'station',
                'name' => __('Station Name'),
                'required' => 'No',
                'aliases' => ['Station Name', 'Station', 'Territory/Cluster', 'Territory', 'Cluster'],
            ),
            array(
                'id' => 'doctor_type',
                'name' => __('Dr. Type'),
                'required' => 'No',
                'aliases' => ['Dr. Type', 'Dr Type', 'Dr. Type (SFC)', 'Dr Type (SFC)', 'Dr Category', 'Doctor Type', 'Doctor Category'],
            ),
            array(
                'id' => 'qualification',
                'name' => __('Qualification'),
                'required' => 'No',
                'aliases' => ['Qualification', 'Dr Qual.', 'Dr Qual', 'Qual'],
            ),
            array(
                'id' => 'station_type',
                'name' => __('Station Type'),
                'required' => 'No',
                'aliases' => ['Station Type', 'HQ/EX/OS', 'HQ/EX/OS ', 'Headquarter/Ex/OS'],
            ),
            array(
                'id' => 'address',
                'name' => __('Address'),
                'required' => 'No',
                'aliases' => ['Address', 'Dr Address'],
            ),
            array(
                'id' => 'speciality',
                'name' => __('Speciality'),
                'required' => 'No',
                'aliases' => [
                    'Speciality',
                    'Dr Speciality',
                    'Specialty',
                    'Speciality (Head)',
                    'Speciality Head',
                    'Specialty (Head)',
                    'Head Speciality',
                    'Spl',
                    'Dr Spl',
                ],
            ),
            array(
                'id' => 'mobile',
                'name' => __('Mobile'),
                'required' => 'No',
                'aliases' => ['Mobile', 'Dr Mobile'],
            ),
            array(
                'id' => 'email',
                'name' => 'Email',
                'required' => 'No',
                'aliases' => ['Email', 'Dr EMail', 'Dr Email', 'EMail'],
            ),
            array('id' => 'gender', 'name' => __('Gender'), 'required' => 'No', 'aliases' => ['Gender']),
            array(
                'id' => 'dob',
                'name' => __('DOB'),
                'required' => 'No',
                'aliases' => ['DOB', 'Dr DOB', 'Date of Birth'],
            ),
            array(
                'id' => 'dom',
                'name' => __('DOM'),
                'required' => 'No',
                'aliases' => ['DOM', 'Dr DOM', 'Date of Marriage'],
            ),
            array(
                'id' => 'products',
                'name' => __('Products (comma-separated)'),
                'required' => 'No',
                'aliases' => ['Products (comma-separated)', 'Products', 'Barnd1', 'Brand1'],
            ),
            array('id' => 'products_2', 'name' => 'Brand 2', 'required' => 'No', 'aliases' => ['Barnd2', 'Brand2']),
            array('id' => 'products_3', 'name' => 'Brand 3', 'required' => 'No', 'aliases' => ['Barnd3', 'Brand3']),
            array('id' => 'msl_number', 'name' => __('MSL Number'), 'required' => 'No', 'aliases' => ['MSL Number', 'MSL']),
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

    public static function rowHasData(array $row): bool
    {
        return collect($row)->contains(function ($value) {
            return $value !== null && trim((string) $value) !== '';
        });
    }

    public static function filterBlankRows(array $rows): array
    {
        return array_values(array_filter($rows, [self::class, 'rowHasData']));
    }

    /**
     * Map Excel column index => field id using header text matching.
     *
     * Header matching is always preferred because the sample file contains an
     * empty column B (index 2) that makes positional mapping unreliable — every
     * field after HQ would be off by one.  A positional fallback is only used
     * when no headers are present at all.
     */
    public static function buildColumnIndexMap(?array $headingRow): array
    {
        $fields = self::fields();

        $normalize = static function ($value): string {
            return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $value)));
        };

        // Build positional fallback (field array order → column index)
        $positionalMap = [];
        foreach ($fields as $index => $field) {
            $positionalMap[$index] = $field['id'];
        }

        if (empty($headingRow)) {
            return $positionalMap;
        }

        // Always attempt header-based matching so that files with extra/empty
        // columns (like the sample file's blank column 2) are handled correctly.
        $headerMap    = [];
        $assignedFields = [];

        foreach ($headingRow as $colIndex => $headingValue) {
            $normalizedHeading = $normalize($headingValue);

            if ($normalizedHeading === '') {
                continue;
            }

            foreach ($fields as $field) {
                $fieldId = $field['id'];

                if (in_array($fieldId, $assignedFields, true)) {
                    continue;
                }

                $matchKeys = array_merge([$fieldId], $field['aliases'] ?? []);

                if (!empty($field['name']) && is_string($field['name'])) {
                    $matchKeys[] = $field['name'];
                }

                $matched = false;
                foreach ($matchKeys as $key) {
                    if ($normalize($key) === $normalizedHeading) {
                        $matched = true;
                        break;
                    }
                }

                if ($matched) {
                    $headerMap[$colIndex] = $fieldId;
                    $assignedFields[]     = $fieldId;
                    break;
                }
            }
        }

        // Require at least the two mandatory fields (fullname + headquarter)
        // before trusting the header-based map.
        $hasName = in_array('fullname',    $assignedFields, true);
        $hasHq   = in_array('headquarter', $assignedFields, true);

        return ($hasName && $hasHq) ? $headerMap : $positionalMap;
    }

    /**
     * @deprecated No longer used — buildColumnIndexMap always tries header matching.
     */
    public static function matchesSampleFileHeaders(array $headingRow): bool
    {
        $normalize = static function ($value): string {
            return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $value)));
        };

        $first  = $normalize($headingRow[0] ?? '');
        $second = $normalize($headingRow[1] ?? '');

        return in_array($first,  ['drname', 'doctorname', 'fullname'],         true)
            && in_array($second, ['hq', 'headquarter', 'headquarters'], true);
    }

}
