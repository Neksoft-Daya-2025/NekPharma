<?php

namespace Tests\Unit;

use App\Exports\ChemistExport;
use App\Exports\StockistExport;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class CustomerExportTest extends TestCase
{
    public function test_chemist_export_maps_rows_like_the_list_export(): void
    {
        $chemist = (object) [
            'shopname' => 'Care Chemist',
            'fullname' => 'Ravi Shah',
            'headquarter' => (object) ['name' => 'Mumbai HQ'],
            'area' => (object) ['name' => 'West'],
            'exstation' => null,
            'outstation' => (object) ['name' => 'Thane'],
            'exstation_id' => null,
            'outstation_id' => 9,
            'mobile' => '9999999999',
            'email' => 'care@example.test',
            'gender' => 'male',
            'dob' => '1990-01-02',
            'dom' => '2015-03-04',
            'address' => 'Main Road',
            'msl_number' => 'MSL-C-1',
        ];

        $export = new ChemistExport(new Collection([$chemist]));

        $this->assertSame([
            'Shop Name',
            'Name',
            'HQ',
            'Area',
            'Ex-Station',
            'Outstation',
            'Station Type',
            'Mobile',
            'Email',
            'Gender',
            'DOB',
            'DOM',
            'Address',
            'MSL Number',
        ], $export->headings());
        $this->assertSame([
            'Care Chemist',
            'Ravi Shah',
            'Mumbai HQ',
            'West',
            null,
            'Thane',
            'Outstation',
            '9999999999',
            'care@example.test',
            'male',
            '1990-01-02',
            '2015-03-04',
            'Main Road',
            'MSL-C-1',
        ], $export->map($chemist));
    }

    public function test_stockist_export_maps_rows_like_the_list_export(): void
    {
        $stockist = (object) [
            'shopname' => 'Prime Stockist',
            'owner_name' => 'Neha Patel',
            'owner_mobile' => '8888888888',
            'fullname' => 'Accounts Desk',
            'headquarter' => (object) ['name' => 'Delhi HQ'],
            'area' => (object) ['name' => 'North'],
            'exstation' => (object) ['name' => 'Noida'],
            'outstation' => null,
            'exstation_id' => 5,
            'outstation_id' => null,
            'mobile' => '7777777777',
            'email' => 'prime@example.test',
            'gender' => 'female',
            'dob' => null,
            'dom' => '2018-08-09',
            'address' => 'Market Lane',
            'dl_number' => 'DL-123',
            'gst_number' => 'GST-123',
            'msl_number' => 'MSL-S-1',
        ];

        $export = new StockistExport(new Collection([$stockist]));

        $this->assertSame([
            'Shop Name',
            'Owner Name',
            'Owner Mobile',
            'Name',
            'HQ',
            'Area',
            'Ex-Station',
            'Outstation',
            'Station Type',
            'Mobile',
            'Email',
            'Gender',
            'DOB',
            'DOM',
            'Address',
            'DL Number',
            'GST Number',
            'MSL Number',
        ], $export->headings());
        $this->assertSame([
            'Prime Stockist',
            'Neha Patel',
            '8888888888',
            'Accounts Desk',
            'Delhi HQ',
            'North',
            'Noida',
            null,
            'Ex-Station',
            '7777777777',
            'prime@example.test',
            'female',
            null,
            '2018-08-09',
            'Market Lane',
            'DL-123',
            'GST-123',
            'MSL-S-1',
        ], $export->map($stockist));
    }
}
