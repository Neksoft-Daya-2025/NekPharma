<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class DoctorImport implements ToArray
{
    private $processedData = [];

    public static function fields(): array
    {
        return array(
            array('id' => 'fullname', 'name' => __('Full Name'), 'required' => 'Yes'),
            array('id' => 'qualification', 'name' => __('Qualification'), 'required' => 'Yes'),
            array('id' => 'headquarter', 'name' => __('Headquarter'), 'required' => 'Yes'),
            array('id' => 'station_type', 'name' => __('Station Type (headquarter/exstation/outstation)'), 'required' => 'Yes'),
            array('id' => 'station', 'name' => __('Station Name (Ex-Station or Out-Station)'), 'required' => 'Yes'),
            array('id' => 'address', 'name' => __('Address'), 'required' => 'Yes'),
            array('id' => 'speciality', 'name' => __('Speciality'), 'required' => 'Yes'),
            array('id' => 'mobile', 'name' => __('Mobile'), 'required' => 'No'),
            array('id' => 'email', 'name' => __('Email'), 'required' => 'No'),
            array('id' => 'gender', 'name' => __('Gender'), 'required' => 'No'),
            array('id' => 'dob', 'name' => __('Date of Birth'), 'required' => 'No'),
            array('id' => 'dom', 'name' => __('Date of Marriage'), 'required' => 'No'),
            array('id' => 'doctor_type', 'name' => __('Doctor Type (SFC)'), 'required' => 'No'),
            array('id' => 'products', 'name' => __('Products (comma-separated)'), 'required' => 'No'),
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

