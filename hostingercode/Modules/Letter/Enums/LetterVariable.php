<?php

namespace Modules\Letter\Enums;

use App\Models\PharmaArea;
use App\Models\User;
use Illuminate\Support\Carbon;

enum LetterVariable: string
{

    case current_date = '##CURRENT_DATE##';
    case current_date_slash = '##CURRENT_DATE_SLASH##';
    case employee_id = '##EMPLOYEE_ID##';
    case employee_name = '##EMPLOYEE_NAME##';
    /** Salutation + full name (e.g. Mr. Amit Kumar) — matches offer letter address block */
    case employee_name_with_salutation = '##EMPLOYEE_NAME_WITH_SALUTATION##';
    case employee_salutation = '##EMPLOYEE_SALUTATION##';
    case employee_address = '##EMPLOYEE_ADDRESS##';
    case employee_joining_date = '##EMPLOYEE_JOINING_DATE##';
    /** Joining date as 12-Jan-2026 (matches standard offer letter Word format) */
    case employee_joining_date_dmy = '##EMPLOYEE_JOINING_DATE_DMY##';
    /** Joining date as Jan 19, 2026 (matches NEW APL.docx) */
    case employee_joining_date_mdy = '##EMPLOYEE_JOINING_DATE_MDY##';
    /** e.g. HQ Siwan — for “posted at our …” */
    case employee_hq_posting_label = '##EMPLOYEE_HQ_POSTING_LABEL##';
    /** e.g. Head quarter Siwan, Bihar, India — for Posting / Transfer clause */
    case employee_posting_location_full = '##EMPLOYEE_POSTING_LOCATION_FULL##';
    case employee_exit_date = '##EMPLOYEE_EXIT_DATE##';
    case employee_probation_end_date = '##EMPLOYEE_PROBATION_END_DATE##';
    case employee_notice_period_start_date = '##EMPLOYEE_NOTICE_PERIOD_START_DATE##';
    case employee_notice_period_end_date = '##EMPLOYEE_NOTICE_PERIOD_END_DATE##';
    case employee_dob = '##EMPLOYEE_DOB##';
    case employee_department = '##EMPLOYEE_DEPARTMENT##';
    case employee_designation = '##EMPLOYEE_DESIGNATION##';
    case employee_mobile = '##EMPLOYEE_MOBILE##';
    case employee_email = '##EMPLOYEE_EMAIL##';
    case employee_headquarter = '##EMPLOYEE_HEADQUARTER##';
    /** Assigned pharma areas (comma-separated), or ….. if none — matches “HQ, …..,” line */
    case employee_area = '##EMPLOYEE_AREA##';
    case employee_reporting_to = '##EMPLOYEE_REPORTING_TO##';
    case employee_reporting_to_designation = '##EMPLOYEE_REPORTING_TO_DESIGNATION##';
    case employee_reporting_to_email = '##EMPLOYEE_REPORTING_TO_EMAIL##';
    case signatory = '##SIGNATORY##';
    case signatory_designation = '##SIGNATORY_DESIGNATION##';
    case signatory_department = '##SIGNATORY_DEPARTMENT##';
    case signatory_email = '##SIGNATORY_EMAIL##';
    case company_name = '##COMPANY_NAME##';
    /** Placeholder dots for annual CTC (replace in editor or extend later); prints like ₹…. */
    case offer_annual_package = '##OFFER_ANNUAL_PACKAGE##';
    /** Gross monthly salary placeholder (e.g. digits); matches “INR Rs. XYZ/- per month” in APL */
    case appointment_gross_salary = '##APPOINTMENT_GROSS_SALARY##';
    /** Short company label (e.g. RYVA) for “employment in RYVA” line */
    case company_name_short = '##COMPANY_NAME_SHORT##';

    public function getValue(User $user)
    {
        $value = match ($this) {
            self::current_date => now()->format(company()->date_format),
            self::current_date_slash => now()->format('d/m/Y'),
            self::employee_id => $user->employeeDetail->employee_id,
            self::employee_name => $user->name,
            self::employee_name_with_salutation => $user->name_salutation,
            self::employee_salutation => $user->salutation ? $user->salutation->label() : '',
            self::employee_address => $user->employeeDetail->address,
            self::employee_joining_date => $user->employeeDetail->joining_date->format(company()->date_format),
            self::employee_joining_date_dmy => $user->employeeDetail?->joining_date
                ? Carbon::parse($user->employeeDetail->joining_date)->format('d-M-Y')
                : '',
            self::employee_joining_date_mdy => $user->employeeDetail?->joining_date
                ? Carbon::parse($user->employeeDetail->joining_date)->format('M j, Y')
                : '',
            self::employee_hq_posting_label => self::employeeHqPostingLabel($user),
            self::employee_posting_location_full => self::employeePostingLocationFull($user),
            self::employee_exit_date => $user->employeeDetail->last_date?->format(company()->date_format),
            self::employee_probation_end_date => $user->employeeDetail->probation_end_date ? Carbon::parse($user->employeeDetail->probation_end_date)->format(company()->date_format) : null,
            self::employee_notice_period_start_date => $user->employeeDetail->notice_period_start_date ? Carbon::parse($user->employeeDetail->notice_period_start_date)->format(company()->date_format) : null,
            self::employee_notice_period_end_date => $user->employeeDetail->notice_period_end_date ? Carbon::parse($user->employeeDetail->notice_period_end_date)->format(company()->date_format) : null,
            self::employee_dob => $user->employeeDetail->date_of_birth?->format(company()->date_format),
            self::employee_department => $user->employeeDetail->department?->team_name,
            self::employee_designation => $user->employeeDetail->designation?->name,
            self::employee_mobile => $user->mobile,
            self::employee_email => $user->email,
            self::employee_headquarter => $user->employeeDetail->headquarter?->name ?? 'Headquarter',
            self::employee_area => self::resolveEmployeeAreaLabel($user),
            self::employee_reporting_to => $user->employeeDetail->reportingTo?->name ?? 'Area Business Manager',
            self::employee_reporting_to_designation => $user->employeeDetail->reportingTo?->employeeDetail?->designation?->name ?? '',
            self::employee_reporting_to_email => $user->employeeDetail->reportingTo?->email ?? '',
            self::signatory => user()->name,
            self::signatory_designation => user()->employeeDetail->designation?->name,
            self::signatory_department => user()->employeeDetail->department?->team_name,
            self::signatory_email => user()->email ?? '',
            self::company_name => $user->company->company_name,
            self::company_name_short => self::resolveCompanyShortName($user),
            self::offer_annual_package => '....',
            self::appointment_gross_salary => 'XXXX',
        };

        return $value;
    }

    /**
     * Comma-separated area names from employee_details.areas, or ellipsis placeholder.
     */
    private static function resolveEmployeeAreaLabel(User $user): string
    {
        $ed = $user->employeeDetail;
        if (!$ed) {
            return '…..';
        }

        $raw = $ed->areas;
        $areaIds = collect(is_array($raw) ? $raw : (json_decode($raw ?? '[]', true) ?: []))
            ->map(fn ($a) => (int) $a)->filter()->unique()->values();

        if ($areaIds->isEmpty()) {
            return '…..';
        }

        $companyId = $user->company_id ?? (company() ? company()->id : null);
        if (!$companyId) {
            return '…..';
        }

        $names = PharmaArea::where('company_id', $companyId)
            ->whereIn('id', $areaIds)
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->values()
            ->all();

        return $names !== [] ? implode(', ', $names) : '…..';
    }

    private static function employeeHqPostingLabel(User $user): string
    {
        $ed = $user->employeeDetail;
        if (!$ed?->headquarter) {
            return 'Headquarter';
        }

        $n = trim($ed->headquarter->name ?? '');
        if ($n === '') {
            return 'Headquarter';
        }

        if (preg_match('/^hq\s+/i', $n)) {
            return $n;
        }

        return 'HQ '.$n;
    }

    private static function employeePostingLocationFull(User $user): string
    {
        $ed = $user->employeeDetail;
        if (!$ed?->headquarter) {
            return 'Head quarter ….., India';
        }

        $hq = $ed->headquarter;
        $hq->loadMissing('area.region');
        $hqName = trim($hq->name ?? '');
        if ($hqName === '') {
            return 'Head quarter ….., India';
        }

        $line = 'Head quarter '.$hqName;
        $regionName = $hq->area && $hq->area->region ? trim($hq->area->region->name) : '';

        return $regionName !== ''
            ? $line.', '.$regionName.', India'
            : $line.', India';
    }

    private static function resolveCompanyShortName(User $user): string
    {
        $cn = (string) ($user->company->company_name ?? '');
        if (preg_match('/\bryva\b/i', $cn)) {
            return 'RYVA';
        }

        $parts = preg_split('/\s+/', trim($cn));

        return $parts[0] !== '' ? strtoupper(mb_substr($parts[0], 0, 12)) : 'RYVA';
    }

    public function label(): string
    {
        $key = 'letter::app.variable_labels.'.$this->name;
        $trans = __($key);

        return $trans !== $key ? $trans : $this->name;
    }

    public static function getMappedValues(User $user)
    {
        $values = [];

        foreach (self::cases() as $case) {
            $values[$case->value] = $case->getValue($user);
        }

        return $values;

    }

}
