<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class UserOption extends Component
{

    public $user;
    public $selected;
    public $pill;
    public $agent;
    public $userID;
    public $additionalText;

    /** When true, label is Employee ID – Name (Designation) (see EmployeeSelectLabel). */
    public $employeeSelect;

    /** Optional data-headquarter-id on option (e.g. tours filter). */
    public $dataHeadquarterId;

    /** Optional extra data-* attributes on the option element (e.g. FNF: last_date, salary). */
    public $optionExtraAttrs;

    /**
     * Create a new component instance.
     *
     * @param  array<string, scalar|null>  $optionExtraAttrs
     * @return void
     */
    public function __construct($user, $selected = false, $pill = false, $agent = false, $userID = null, $additionalText = null, $employeeSelect = false, $dataHeadquarterId = null, $optionExtraAttrs = null)
    {
        $this->user = $user;
        $this->selected = $selected;
        $this->pill = $pill;
        $this->agent = $agent;
        $this->userID = $userID;
        $this->additionalText = $additionalText;
        $this->employeeSelect = $employeeSelect;
        $this->dataHeadquarterId = $dataHeadquarterId;
        $this->optionExtraAttrs = is_array($optionExtraAttrs) ? $optionExtraAttrs : [];
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render()
    {
        return view('components.user-option');
    }

}
