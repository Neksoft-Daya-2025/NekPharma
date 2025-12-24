<?php

namespace App\Http\Requests\FnfSettlement;

use App\Http\Requests\CoreRequest;

class StoreRequest extends CoreRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'resignation_type' => 'required|in:resignation,termination,retirement,end_of_contract',
            'last_working_day' => 'required|date',
            'resignation_date' => 'nullable|date',
            'resignation_reason' => 'required|string',
            'pending_bonus' => 'nullable|numeric|min:0',
            'pending_incentives' => 'nullable|numeric|min:0',
            'loan_outstanding' => 'nullable|numeric|min:0',
            'advance_outstanding' => 'nullable|numeric|min:0',
            'notice_period_recovery' => 'nullable|numeric|min:0',
            'other_deductions' => 'nullable|numeric|min:0',
        ];
    }

    public function attributes()
    {
        return [
            'user_id' => __('Employee'),
            'resignation_type' => __('Resignation Type'),
            'last_working_day' => __('Last Working Day'),
            'resignation_reason' => __('Resignation Reason'),
        ];
    }
}

