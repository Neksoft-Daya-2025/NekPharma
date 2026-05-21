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
                'name' => __('Email'),
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

}

