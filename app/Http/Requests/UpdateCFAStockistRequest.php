<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCFAStockistRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'shopname' => 'required|string|max:255',
            'fullname' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile' => 'nullable|string|max:20',
            'owner_name' => 'nullable|string|max:255',
            'owner_mobile' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'gst_number' => 'nullable|string|max:255',
            'dl_number' => 'nullable|string|max:255',
            'msl_number' => 'nullable|string|max:255',
            'cfa_distributor_ids' => 'nullable|array',
            'cfa_distributor_ids.*' => 'exists:users,id',
        ];
    }
}
