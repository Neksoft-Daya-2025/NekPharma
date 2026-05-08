<?php

namespace App\Models;

use App\Scopes\ActiveScope;
use App\Traits\HasCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\LeaveType
 *
 * @property int $id
 * @property string $type_name
 * @property string $color
 * @property int $no_of_leaves
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $paid
 * @property-read mixed $icon
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Leave[] $leaves
 * @property-read int|null $leaves_count
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType query()
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType whereNoOfLeaves($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType wherePaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType whereTypeName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType whereUpdatedAt($value)
 * @property int|null $company_id
 * @property int $monthly_limit
 * @property-read \App\Models\Company|null $company
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Leave[] $leavesCount
 * @property-read int|null $leaves_count_count
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType whereMonthlyLimit($value)
 * @property int|null $effective_after
 * @property string|null $effective_type
 * @property string|null $unused_leave
 * @property int $encashed
 * @property int $allowed_probation
 * @property int $allowed_notice
 * @property string|null $gender
 * @property string|null $marital_status
 * @property string|null $department
 * @property string|null $designation
 * @property string|null $role
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType whereAllowedNotice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType whereAllowedProbation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType whereDepartment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType whereDesignation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType whereEffectiveAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType whereEffectiveType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType whereEncashed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType whereMaritalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LeaveType whereUnusedLeave($value)
 * @mixin \Eloquent
 */
class LeaveType extends BaseModel
{

    use HasCompany,SoftDeletes;

    protected $casts = [
        'monthly_limit' => 'float',
    ];

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class, 'leave_type_id');
    }

    public function leavesCount(): HasOne
    {
        return $this->hasOne(Leave::class, 'leave_type_id')
            ->selectRaw('leave_type_id, count(*) as count, SUM(if(duration="half day", 1, 0)) AS halfday')
            ->groupBy('leave_type_id');
    }

    public static function byUser($user, $leaveTypeId = null, $status = array('approved'), $leaveDate = null)
    {
        if (!is_null($leaveDate)) {
            $leaveDate = Carbon::createFromFormat(company()->date_format, $leaveDate);

        }
        else {
            $leaveDate = Carbon::createFromFormat('d-m-Y', '01-'.company()->year_starts_from.'-'.now(company()->timezone)->year)->startOfMonth();
        }

        if (!$user instanceof User) {
            $user = User::withoutGlobalScope(ActiveScope::class)->withOut('clientDetails', 'role')->findOrFail($user);
        }

        $setting = company();

        if (isset($user->employee[0])) {
            if ($setting->leaves_start_from == 'joining_date') {
                $currentYearJoiningDate = Carbon::parse($user->employee[0]->joining_date->format((now(company()->timezone)->year) . '-m-d'));

                if ($currentYearJoiningDate->isFuture()) {
                    $currentYearJoiningDate->subYear();
                }

                $leaveTypes = LeaveType::with(['leavesCount' => function ($q) use ($user, $currentYearJoiningDate, $status) {
                    $q->where('leaves.user_id', $user->id);
                    $q->whereBetween('leaves.leave_date', [$currentYearJoiningDate->copy()->toDateString(), $currentYearJoiningDate->copy()->addYear()->toDateString()]);
                    $q->whereIn('leaves.status', $status);
                }])->select('leave_types.*', 'employee_details.notice_period_start_date', 'employee_details.probation_end_date',
                'employee_details.department_id as employee_department', 'employee_details.designation_id as employee_designation',
                'employee_details.marital_status as maritalStatus', 'users.gender as usergender', 'employee_details.joining_date')
                ->join('employee_leave_quotas', 'employee_leave_quotas.leave_type_id', 'leave_types.id')
                ->join('users', 'users.id', 'employee_leave_quotas.user_id')
                ->join('employee_details', 'employee_details.user_id', 'users.id')->where('users.id', $user->id);

                if (!is_null($leaveTypeId)) {
                    $leaveTypes = $leaveTypes->where('leave_types.id', $leaveTypeId);
                }

                return $leaveTypes = $leaveTypes->get();

            }
            else {
                $leaveTypes = LeaveType::with(['leavesCount' => function ($q) use ($user, $status, $leaveDate) {
                    $q->where('leaves.user_id', $user->id);
                    $q->whereBetween('leaves.leave_date', [$leaveDate->copy()->toDateString(), $leaveDate->copy()->addYear()->toDateString()]);
                    $q->whereIn('leaves.status', $status);
                }])->select('leave_types.*', 'employee_details.notice_period_start_date', 'employee_details.probation_end_date',
                'employee_details.department_id as employee_department', 'employee_details.designation_id as employee_designation',
                'employee_details.marital_status as maritalStatus', 'users.gender as usergender', 'employee_details.joining_date')
                ->join('employee_leave_quotas', 'employee_leave_quotas.leave_type_id', 'leave_types.id')
                ->join('users', 'users.id', 'employee_leave_quotas.user_id')
                ->join('employee_details', 'employee_details.user_id', 'users.id')->where('users.id', $user->id);
            }

            if (!is_null($leaveTypeId)) {
                $leaveTypes = $leaveTypes->where('leave_types.id', $leaveTypeId);
            }

            return $leaveTypes->get();
        }

        return collect();

    }

    public function leaveTypeCondition(LeaveType $leave, User $user)
    {
        if (!$user->employee) {
            return false;
        }

        $detail = $user->employeeDetail ?? $user->employeeDetails;

        if (!$detail) {
            return false;
        }

        $tz = company()->timezone;
        $currentDate = now($tz);

        $probationEndDate = $detail->probation_end_date
            ? Carbon::parse($detail->probation_end_date)->timezone($tz)->startOfDay()
            : null;

        // If employment_status is explicitly 'Probation' but no end date is set,
        // treat them as still on probation by using a far-future sentinel date.
        // This prevents employees with status=Probation+null end date from bypassing leave restrictions.
        if (is_null($probationEndDate) && ($detail->employment_status ?? '') === 'Probation') {
            $probationEndDate = Carbon::now($tz)->addYears(10)->startOfDay();
        }

        $userRole = $user->roles->pluck('id')->toArray();

        $leaveRole = $leave->role;

        $effectiveDate = null;

        if (!is_null($leave->effective_type) && !is_null($leave->effective_after) && $detail->joining_date) {
            $joining = $detail->joining_date instanceof Carbon
                ? $detail->joining_date->copy()
                : Carbon::parse($detail->joining_date)->timezone($tz);
            $effectiveDate = $leave->effective_type == 'days'
                ? $joining->copy()->addDays($leave->effective_after)
                : $joining->copy()->addMonths($leave->effective_after);
        }

        $noticeStart = $detail->notice_period_start_date
            ? Carbon::parse($detail->notice_period_start_date)->timezone($tz)->startOfDay()
            : null;

        if (!is_null($leave->effective_after) && is_null($effectiveDate)) {
            return false;
        }

        $probationOk = (
            is_null($probationEndDate)
            || ($leave->allowed_probation == 0 && $probationEndDate->lt($currentDate))
            || $leave->allowed_probation == 1
        );

        $noticeOk = (
            is_null($noticeStart)
            || ($leave->allowed_notice == 0 && $noticeStart->gt($currentDate))
            || $leave->allowed_notice == 1
        );

        if (!$probationOk || !$noticeOk) {
            return false;
        }

        // If applicability fields are empty, treat as "no restriction" (legacy rows often have nulls here).
        $genderList = self::decodedJsonList($leave->gender);
        if (count($genderList) > 0 && !in_array($user->gender, $genderList, true)) {
            return false;
        }

        $maritalList = self::decodedJsonList($leave->marital_status);
        if (count($maritalList) > 0 && !in_array($detail->marital_status?->value, $maritalList, true)) {
            return false;
        }

        $departmentList = self::decodedJsonList($leave->department);
        if (count($departmentList) > 0 && !in_array($detail->department?->id, $departmentList)) {
            return false;
        }

        $designationList = self::decodedJsonList($leave->designation);
        if (count($designationList) > 0 && !in_array($detail->designation?->id, $designationList)) {
            return false;
        }

        $roleList = self::decodedJsonList($leaveRole);
        if (count($roleList) > 0 && empty(array_intersect($userRole, array_map('intval', $roleList)))) {
            return false;
        }

        if (
            !is_null($leave->effective_after)
            && !is_null($effectiveDate)
            && !$currentDate->gt($effectiveDate)
        ) {
            return false;
        }

        return true;
    }

    /**
     * @return array<int|string, mixed>
     */
    private static function decodedJsonList(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

}
