<?php

namespace App\Console\Commands;

use App\Models\Stockist;
use Illuminate\Console\Command;

class UpdateStockistFields extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stockists:update-fields {--company-id= : Company ID to update stockists for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing stockists to populate fullname and mobile from owner_name and owner_mobile if empty';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyId = $this->option('company-id');
        
        $query = Stockist::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        $stockists = $query->get();
        $updated = 0;
        
        foreach ($stockists as $stockist) {
            $updatedFields = false;
            
            // If fullname is empty but owner_name exists, copy it
            if (empty($stockist->fullname) && !empty($stockist->owner_name)) {
                $stockist->fullname = $stockist->owner_name;
                $updatedFields = true;
            }
            
            // If mobile is empty but owner_mobile exists, copy it
            if (empty($stockist->mobile) && !empty($stockist->owner_mobile)) {
                $stockist->mobile = $stockist->owner_mobile;
                $updatedFields = true;
            }
            
            if ($updatedFields) {
                $stockist->save();
                $updated++;
                $this->info("Updated stockist ID {$stockist->id}: {$stockist->shopname}");
            }
        }
        
        $this->info("Total stockists updated: {$updated} out of {$stockists->count()}");
        
        return Command::SUCCESS;
    }
}






