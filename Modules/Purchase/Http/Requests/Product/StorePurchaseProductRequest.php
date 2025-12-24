<?php

namespace Modules\Purchase\Http\Requests\Product;

use App\Http\Requests\CoreRequest;
use App\Traits\CustomFieldsRequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseProductRequest extends CoreRequest
{
    use CustomFieldsRequestTrait;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'track_inventory' => 'sometimes|in:0,1',
            'type' => 'required|in:goods',
            'opening_stock' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'unit_type' => 'nullable|exists:unit_types,id',
            'vendor_id' => 'nullable|exists:purchase_vendors,id',
            'packing' => 'nullable|string|max:255',
            'ptr' => 'nullable|numeric|min:0',
            'pts' => 'nullable|numeric|min:0',
            'scheme' => 'nullable|string|max:50',
            'scheme_quantity' => 'nullable|numeric|min:0',
            'scheme_free' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:flat,percentage',
            'total' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|max:255',
            'hsn_sac_code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:product_category,id',
            'sub_category_id' => 'nullable|exists:product_sub_category,id',
            'tax' => 'nullable|array',
            'tax.*' => 'nullable|exists:taxes,id',
        ];

        $rules = $this->customFieldRules($rules);

        return $rules;
    }

    public function messages()
    {
        return [
            'name.required' => __('The product name field is required.'),
            'name.unique' => __('The product name has already been taken.'),
            'purchase_price.required' => __('The MRP (Maximum Retail Price) field is required.'),
            'purchase_price.numeric' => __('The MRP must be a valid number.'),
            'purchase_price.min' => __('The MRP must be at least 0.'),
            'opening_stock.required_if' => __('The opening stock field is required when track inventory is enabled.'),
            'opening_stock.numeric' => __('The opening stock must be a valid number.'),
            'opening_stock.min' => __('The opening stock must be at least 0.'),
            'vendor_id.exists' => __('The selected vendor is invalid.'),
            'discount.numeric' => __('The discount must be a valid number.'),
            'discount.min' => __('The discount must be at least 0.'),
            'discount_type.in' => __('The discount type must be either flat or percentage.'),
            'scheme_quantity.numeric' => __('The scheme quantity must be a valid number.'),
            'scheme_free.numeric' => __('The free quantity must be a valid number.'),
        ];
    }

    public function attributes()
    {
        $attributes = [];

        $attributes = $this->customFieldsAttributes($attributes);

        return $attributes;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

}
