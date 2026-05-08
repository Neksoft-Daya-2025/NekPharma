<?php

namespace App\Http\Requests\Admin\Employee;

use App\Http\Requests\CoreRequest;
use App\Models\Designation;
use App\Helpers\PharmaDesignationHelper;
use App\Traits\CustomFieldsRequestTrait;

class StoreRequest extends CoreRequest
{
    use CustomFieldsRequestTrait;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $setting = company();
        $rules = [
            'employee_id' => 'required|unique:employee_details,employee_id,null,id,company_id,' . company()->id.'|max:100',
            'name' => 'required|max:50',
            'email' => 'required|email:rfc,strict|unique:users,email,null,id,company_id,' . company()->id.'|max:100',
            'password' => 'required|min:8|max:50',
            'slack_username' => 'nullable|unique:employee_details,slack_username,null,id,company_id,' . company()->id.'|max:30',
            'hourly_rate' => 'nullable|numeric',
            'joining_date' => 'required',
            'last_date' => 'nullable|date_format:"' . $setting->date_format . '"|after_or_equal:joining_date',
            'date_of_birth' => 'nullable|date_format:"' . $setting->date_format . '"|before_or_equal:'.now($setting->timezone)->toDateString(),
            'department' => 'required',
            'designation' => 'required',
            'company_address' => 'required',
            'probation_end_date' => 'nullable|date_format:"' . $setting->date_format . '"|after_or_equal:joining_date',
            'notice_period_start_date' => 'nullable|required_with:notice_period_end_date|date_format:"' . $setting->date_format . '"',
            'notice_period_end_date' => 'nullable|required_with:notice_period_start_date|date_format:"' . $setting->date_format . '"|after_or_equal:notice_period_start_date',
            'internship_end_date' => 'nullable|date_format:"' . $setting->date_format . '"|after_or_equal:joining_date',
            'contract_end_date' => 'nullable|date_format:"' . $setting->date_format . '"|after_or_equal:joining_date',
        ];

        if (request()->telegram_user_id) {
            $rules['telegram_user_id'] = 'nullable|unique:users,telegram_user_id,null,id,company_id,' . company()->id;
        }

        // Pharma HeadQuarter validation - required if pharma_areas module is active, except for ABM/RBM/ZM/MIS Executive (they get access from areas/regions/zones or full access)
        if (in_array('pharma_areas', user_modules())) {
            $designation = $this->input('designation') ? Designation::find($this->input('designation')) : null;
            $skipsHeadquarter = $designation && (
                PharmaDesignationHelper::isABM($designation)
                || PharmaDesignationHelper::isRBM($designation)
                || PharmaDesignationHelper::isZM($designation)
                || PharmaDesignationHelper::isMISExecutive($designation)
            );
            $rules['headquarter_id'] = $skipsHeadquarter ? 'nullable|exists:pharma_headquarters,id' : 'required|exists:pharma_headquarters,id';
        }

        $rules = $this->customFieldRules($rules);

        return $rules;
    }

    public function attributes()
    {
        $attributes = [];

        $attributes = $this->customFieldsAttributes($attributes);

        return $attributes;
    }

}
