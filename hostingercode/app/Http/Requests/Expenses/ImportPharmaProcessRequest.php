<?php

namespace App\Http\Requests\Expenses;

use Illuminate\Foundation\Http\FormRequest;

class ImportPharmaProcessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required',
            'has_heading' => 'nullable',
            'columns' => ['required', 'array', 'min:1'],
            'pharma_user_id' => 'required|exists:users,id',
            'pharma_headquarter_id' => 'required|exists:pharma_headquarters,id',
            'expense_month' => 'required|integer|between:1,12',
            'expense_year' => 'required|integer|min:2020|max:2100',
            'posted_on' => 'required|date',
            'no_of_vouchers' => 'required|integer|min:0',
            'submitted_to' => 'required|exists:users,id',
        ];
    }

    public function attributes(): array
    {
        return [
            'columns.*' => 'column',
        ];
    }
}
