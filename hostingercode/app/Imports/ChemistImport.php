<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class ChemistImport implements ToArray
{
    private $processedData = [];

    public static function fields(): array
    {
        return array(
            array('id' => 'shopname', 'name' => __('Shop Name'), 'required' => 'Yes'),
            array('id' => 'headquarter', 'name' => __('Headquarter'), 'required' => 'Yes'),
            array('id' => 'station_type', 'name' => __('Station Type (headquarter/exstation/outstation)'), 'required' => 'Yes'),
            array('id' => 'station', 'name' => __('Station Name (Ex-Station or Out-Station)'), 'required' => 'Yes'),
            array('id' => 'address', 'name' => __('Address'), 'required' => 'Yes'),
            array('id' => 'fullname', 'name' => __('Chemist Name'), 'required' => 'No'),
            array('id' => 'mobile', 'name' => __('Mobile'), 'required' => 'No'),
            array('id' => 'email', 'name' => __('Email'), 'required' => 'No'),
            array('id' => 'gender', 'name' => __('Gender'), 'required' => 'No'),
            array('id' => 'dob', 'name' => __('Date of Birth'), 'required' => 'No'),
            array('id' => 'dom', 'name' => __('Date of Marriage'), 'required' => 'No'),
            array('id' => 'msl_number', 'name' => __('MSL Number'), 'required' => 'No'),
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

