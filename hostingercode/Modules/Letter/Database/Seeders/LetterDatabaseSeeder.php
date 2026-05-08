<?php

namespace Modules\Letter\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Letter\Entities\Template;
use Modules\Letter\Templates\OfferLetterDefaultTemplate;
use App\Models\Company;

class LetterDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default offer letter template for all companies
        $companies = Company::all();
        
        foreach ($companies as $company) {
            // Check if template already exists
            $existingTemplate = Template::where('company_id', $company->id)
                ->where('title', 'Offer Letter')
                ->first();
            
            if (!$existingTemplate) {
                $defaultContent = OfferLetterDefaultTemplate::html();

                Template::create([
                    'company_id' => $company->id,
                    'title' => 'Offer Letter',
                    'description' => $defaultContent,
                ]);
            }
        }
    }
}
