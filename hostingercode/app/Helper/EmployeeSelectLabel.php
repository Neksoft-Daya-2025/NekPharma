<?php

namespace App\Helper;

use App\Models\User;

/**
 * Consistent employee dropdown labels: Employee ID – Name (Designation).
 * Matches DCR create format; used by user-option (employeeSelect) and manual loops.
 */
class EmployeeSelectLabel
{
    /**
     * Employee ID from details, join attribute, or null.
     */
    public static function employeeCode(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        if (isset($user->attributes['employee_id']) && $user->attributes['employee_id'] !== null && $user->attributes['employee_id'] !== '') {
            return (string) $user->attributes['employee_id'];
        }

        $detail = $user->employeeDetail ?? $user->employeeDetails;

        if ($detail && ! empty($detail->employee_id)) {
            return (string) $detail->employee_id;
        }

        return null;
    }

    /**
     * Designation display string.
     */
    public static function designation(?User $user): string
    {
        if ($user === null) {
            return '-';
        }

        $detail = $user->employeeDetail ?? $user->employeeDetails;
        if ($detail && $detail->relationLoaded('designation') && $detail->designation) {
            return (string) $detail->designation->name;
        }

        if (isset($user->attributes['designation_name']) && $user->attributes['designation_name'] !== null && $user->attributes['designation_name'] !== '') {
            return (string) $user->attributes['designation_name'];
        }

        if ($detail && $detail->designation) {
            return (string) $detail->designation->name;
        }

        return '-';
    }

    /**
     * Plain text for <option> body and search (no HTML).
     */
    public static function plain(?User $user): string
    {
        if ($user === null) {
            return '';
        }

        $name = $user->name_salutation;
        $designation = self::designation($user);
        $code = self::employeeCode($user);

        if ($code !== null && $code !== '') {
            return $code . ' - ' . $name . ' (' . $designation . ')';
        }

        return $name . ' (' . $designation . ')';
    }

    /**
     * HTML line for data-content: bold employee code when present, then name and designation.
     */
    public static function htmlInner(?User $user): string
    {
        if ($user === null) {
            return '';
        }

        $name = e($user->name_salutation);
        $designation = e(self::designation($user));
        $code = self::employeeCode($user);

        if ($code !== null && $code !== '') {
            return '<span class="font-weight-bold">' . e($code) . '</span> - ' . $name . ' (' . $designation . ')';
        }

        return $name . ' (' . $designation . ')';
    }

    /**
     * One bootstrap-select <option> — plain text only (no data-content; avoids bootstrap-select/theme contrast bugs).
     */
    public static function bootstrapSelectOptionHtml(User $user, $currentUserId = null, bool $selected = false): string
    {
        $currentUserId = $currentUserId ?? (user() ? user()->id : null);

        $plain = self::plain($user);
        if (user() && $currentUserId !== null && $currentUserId == $user->id) {
            $plain .= ' (' . __('app.itsYou') . ')';
        }
        if ($user->status == 'deactive') {
            $plain .= ' [Inactive]';
        }

        $sel = $selected ? ' selected' : '';

        return '<option' . $sel . ' value="' . (int) $user->id . '">' . htmlspecialchars($plain, ENT_QUOTES, 'UTF-8') . '</option>';
    }
}
