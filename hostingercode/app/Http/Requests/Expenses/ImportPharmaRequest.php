<?php

namespace App\Http\Requests\Expenses;

use Illuminate\Foundation\Http\FormRequest;

class ImportPharmaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'import_file' => 'required|file|mimes:xls,xlsx,csv,txt',
            'heading' => 'nullable',
            'pharma_user_id' => 'required|exists:users,id',
            'pharma_headquarter_id' => 'required|exists:pharma_headquarters,id',
            'expense_month' => 'required|integer|between:1,12',
            'expense_year' => 'required|integer|min:2020|max:2100',
            'posted_on' => 'required|date',
            'no_of_vouchers' => 'required|integer|min:0',
            'submitted_to' => 'required|exists:users,id',
        ];
    }
}
