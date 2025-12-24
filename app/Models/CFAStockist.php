<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CFAStockist extends BaseModel
{
    use HasFactory, HasCompany, SoftDeletes;

    const CUSTOM_FIELD_MODEL = 'App\Models\CFAStockist';

    protected $table = 'cfa_stockists';

    protected $fillable = [
        'company_id',
        'cfa_stockist_id',
        'shopname',
        'fullname',
        'email',
        'mobile',
        'owner_name',
        'owner_mobile',
        'address',
        'gst_number',
        'dl_number',
        'msl_number',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cfaStockist) {
            if (empty($cfaStockist->cfa_stockist_id)) {
                $cfaStockist->cfa_stockist_id = self::generateCFAStockistId($cfaStockist->company_id);
            }
        });
    }

    /**
     * Generate a unique CFA Stockist ID
     *
     * @param int|null $companyId
     * @return string
     */
    public static function generateCFAStockistId($companyId = null): string
    {
        $companyId = $companyId ?? company()->id;
        
        // Get the prefix (can be configured later)
        $prefix = 'CFA-STK';
        
        // Get all existing CFA Stockist IDs for this company
        $existingIds = self::where('company_id', $companyId)
            ->whereNotNull('cfa_stockist_id')
            ->pluck('cfa_stockist_id')
            ->toArray();
        
        $maxNumber = 0;
        
        // Extract numbers from existing IDs and find the maximum
        foreach ($existingIds as $id) {
            if (preg_match('/-(\d+)$/', $id, $matches)) {
                $number = (int)$matches[1];
                if ($number > $maxNumber) {
                    $maxNumber = $number;
                }
            }
        }
        
        // Generate next number
        $nextNumber = $maxNumber + 1;
        
        // Format with leading zeros (3 digits: 001, 002, etc.)
        $formattedNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        
        $newId = $prefix . '-' . $formattedNumber;
        
        // Double-check uniqueness (in case of race conditions)
        $attempts = 0;
        while (self::where('company_id', $companyId)->where('cfa_stockist_id', $newId)->exists() && $attempts < 10) {
            $nextNumber++;
            $formattedNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $newId = $prefix . '-' . $formattedNumber;
            $attempts++;
        }
        
        return $newId;
    }

    // Relationship with CFA/Distributors
    public function cfaDistributors()
    {
        return $this->belongsToMany(User::class, 'cfa_distributor_stockist', 'cfa_stockist_id', 'cfa_distributor_id')
            ->withPivot('company_id')
            ->withTimestamps();
    }
}

