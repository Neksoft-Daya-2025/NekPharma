<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeFnfSettlement extends BaseModel
{
    use HasCompany;

    protected $table = 'employee_fnf_settlements';

    protected $fillable = [
        'company_id',
        'user_id',
        'resignation_date',
        'last_working_day',
        'fnf_initiated_date',
        'fnf_completion_date',
        'status',
        'resignation_type',
        'resignation_reason',
        'clearance_checklist',
        'assets_to_return',
        'assets_returned',
        'assets_return_date',
        'documents_to_collect',
        'documents_issued',
        'basic_salary',
        'earned_salary',
        'working_days',
        'payable_days',
        'leave_balance_days',
        'leave_encashment_amount',
        'pending_bonus',
        'pending_incentives',
        'loan_outstanding',
        'advance_outstanding',
        'notice_period_recovery',
        'other_deductions',
        'deduction_remarks',
        'gross_amount',
        'total_deductions',
        'net_payable',
        'payment_status',
        'payment_date',
        'payment_mode',
        'payment_reference',
        'fnf_statement_file',
        'approved_by',
        'approved_date',
        'remarks',
        'hr_notes',
        'added_by',
        'last_updated_by',
    ];

    protected $casts = [
        'resignation_date' => 'date',
        'last_working_day' => 'date',
        'fnf_initiated_date' => 'date',
        'fnf_completion_date' => 'date',
        'assets_return_date' => 'date',
        'payment_date' => 'date',
        'approved_date' => 'date',
        'clearance_checklist' => 'array',
        'assets_to_return' => 'array',
        'documents_to_collect' => 'array',
        'assets_returned' => 'boolean',
        'documents_issued' => 'boolean',
    ];

    protected $appends = ['clearance_progress', 'status_color'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withoutGlobalScopes();
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by')->withoutGlobalScopes();
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by')->withoutGlobalScopes();
    }

    // Calculate clearance progress percentage
    public function getClearanceProgressAttribute()
    {
        if (!$this->clearance_checklist) {
            return 0;
        }

        $total = count($this->clearance_checklist);
        $completed = collect($this->clearance_checklist)->where('cleared', true)->count();

        return $total > 0 ? round(($completed / $total) * 100) : 0;
    }

    // Get status color for badges
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'initiated' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }

    // Check if all clearances are complete
    public function isClearanceComplete(): bool
    {
        if (!$this->clearance_checklist) {
            return false;
        }

        return collect($this->clearance_checklist)->every(fn($item) => $item['cleared'] === true);
    }

    // Calculate final settlement automatically
    public function calculateSettlement()
    {
        // Gross Amount = Earned Salary + Leave Encashment + Bonus + Incentives
        $this->gross_amount = $this->earned_salary 
                            + $this->leave_encashment_amount 
                            + $this->pending_bonus 
                            + $this->pending_incentives;

        // Total Deductions
        $this->total_deductions = $this->loan_outstanding 
                                + $this->advance_outstanding 
                                + $this->notice_period_recovery 
                                + $this->other_deductions;

        // Net Payable
        $this->net_payable = $this->gross_amount - $this->total_deductions;

        return $this;
    }

    // Default clearance checklist
    public static function getDefaultClearanceChecklist(): array
    {
        return [
            [
                'department' => 'IT Department',
                'items' => [
                    'Laptop/Desktop returned',
                    'Mobile phone returned',
                    'Access cards returned',
                    'Email account deactivated',
                    'System access revoked',
                ],
                'cleared' => false,
                'cleared_by' => null,
                'cleared_date' => null,
                'remarks' => null,
            ],
            [
                'department' => 'Admin Department',
                'items' => [
                    'Office keys returned',
                    'ID card returned',
                    'Parking pass returned',
                    'Office supplies returned',
                ],
                'cleared' => false,
                'cleared_by' => null,
                'cleared_date' => null,
                'remarks' => null,
            ],
            [
                'department' => 'HR Department',
                'items' => [
                    'Exit interview completed',
                    'Employee handbook returned',
                    'Confidentiality agreement signed',
                    'Notice period completed/settled',
                ],
                'cleared' => false,
                'cleared_by' => null,
                'cleared_date' => null,
                'remarks' => null,
            ],
            [
                'department' => 'Finance Department',
                'items' => [
                    'Outstanding loans settled',
                    'Advance payments settled',
                    'Expense claims processed',
                    'Final salary calculated',
                ],
                'cleared' => false,
                'cleared_by' => null,
                'cleared_date' => null,
                'remarks' => null,
            ],
        ];
    }

    // Default documents to collect
    public static function getDefaultDocuments(): array
    {
        return [
            [
                'name' => 'Experience Certificate',
                'issued' => false,
                'issued_date' => null,
            ],
            [
                'name' => 'Relieving Letter',
                'issued' => false,
                'issued_date' => null,
            ],
            [
                'name' => 'No Dues Certificate',
                'issued' => false,
                'issued_date' => null,
            ],
            [
                'name' => 'FNF Statement',
                'issued' => false,
                'issued_date' => null,
            ],
        ];
    }
}

