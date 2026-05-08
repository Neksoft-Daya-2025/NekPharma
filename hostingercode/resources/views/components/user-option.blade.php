@php
    use App\Helper\EmployeeSelectLabel;

    if (!empty($employeeSelect)) {
        // Plain <option> text only — no data-content / data-html (bootstrap-select + theme CSS often hides HTML labels).
        $optionPlain = EmployeeSelectLabel::plain($user);
        if (user() && user()->id == $user->id) {
            $optionPlain .= ' (' . __('app.itsYou') . ')';
        }
        if (($user->status ?? '') == 'deactive') {
            $optionPlain .= ' [Inactive]';
        }
        if (isset($additionalText) && !is_null($additionalText) && $additionalText !== '') {
            $optionPlain .= ' — ' . strip_tags((string) $additionalText);
        }
        if ($agent) {
            $optionPlain .= ' [' . $user->email . ']';
        }
    } else {
        $content = "<span class='d-flex align-items-center text-left'>
    <span class='taskEmployeeImg border-0 d-inline-block mr-1 flex-shrink-0'>
        <img class='rounded-circle' alt='' src='" . e($user->image_url) . "'>
    </span>
    <span class='flex-grow-1' style='min-width:0'>" . htmlentities($user->userBadge());

        if (isset($additionalText) && !is_null($additionalText)) {
            $content .= "<span class='d-block f-10 font-weight-light my-1'>" . $additionalText . '</span>';
        }

        $content .= '</span></span>';

        if ($agent) {
            $content .= ' [' . $user->email . '] ';
        }

        if ($pill) {
            $content = "<span class='badge badge-pill badge-light border abc'>" . $content . '</span>';
        }

        if ($user->status == 'deactive') {
            $content .= "<span class='badge badge-pill badge-danger border align-center ml-2 px-2'>Inactive</span>";
        }

        $optionPlain = $user->name_salutation;
    }
@endphp

    <option @selected($selected) @if (empty($employeeSelect)) data-content="{!! $content !!}" @endif value="{{ $userID ?? $user->id }}"
        @if(isset($dataHeadquarterId) && $dataHeadquarterId !== null && $dataHeadquarterId !== '') data-headquarter-id="{{ $dataHeadquarterId }}" @endif
        @foreach($optionExtraAttrs ?? [] as $attrName => $attrValue)
            @if($attrValue !== null)
                {{ $attrName }}="{{ $attrValue }}"
            @endif
        @endforeach
    >
        {{ $optionPlain }}
    </option>
