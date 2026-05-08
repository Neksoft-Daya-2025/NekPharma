<?php

namespace Modules\Recruit\Services;

use Carbon\Carbon;
use Modules\Recruit\Entities\RecruitJobOfferLetter;

class OfferLetterVariableService
{
    /**
     * Replace offer letter HTML placeholders in saved Recruit settings content.
     * Supports both @{{ $var }} (preferred) and {{ $var }} (legacy).
     */
    public static function replace(
        string $content,
        RecruitJobOfferLetter $jobOffer,
        $company,
        ?int $currencyId,
        bool $currencyShowSymbol = true
    ): string {
        if ($content === '') {
            return $content;
        }

        $jobOffer->loadMissing([
            'job.team',
            'job.location',
            'job.recruiter.employeeDetail.designation',
            'job.jobType',
            'jobApplication',
        ]);

        $job = $jobOffer->job;
        $app = $jobOffer->jobApplication;
        if ($currencyId === null && function_exists('company')) {
            $c = company();
            $currencyId = ($c && $c->currency) ? $c->currency->id : 1;
        } elseif ($currencyId === null) {
            $currencyId = 1;
        }

        $compAmount = currency_format($jobOffer->comp_amount, $currencyId, $currencyShowSymbol);

        $expectedJoin = $jobOffer->expected_joining_date
            ? Carbon::parse($jobOffer->expected_joining_date)->format('d-M-Y')
            : '';
        $jobExpire = $jobOffer->job_expire
            ? Carbon::parse($jobOffer->job_expire)->format('d-M-Y')
            : '';

        $issueDate = $jobOffer->created_at
            ? Carbon::parse($jobOffer->created_at)->format('d-M-Y')
            : '';
        $issueDateSlash = $jobOffer->created_at
            ? Carbon::parse($jobOffer->created_at)->format('d/m/Y')
            : '';
        $validDays = '';
        if ($jobOffer->created_at && $jobOffer->job_expire) {
            $start = Carbon::parse($jobOffer->created_at)->startOfDay();
            $end = Carbon::parse($jobOffer->job_expire)->startOfDay();
            $validDays = (string) max(1, $start->diffInDays($end));
        }

        $payAccording = $job?->pay_according ?? '';
        $jobTypeEnum = $job ? ucwords((string) $job->job_type) : '';
        $jobTypeLabel = $job?->jobType?->job_type
            ? ucwords($job->jobType->job_type)
            : $jobTypeEnum;

        $recruiterName = $job?->recruiter?->name ?? 'Area Business Manager';
        $recruiterDesignation = $job?->recruiter?->employeeDetail?->designation?->name ?? '';
        $recruiterEmail = $job?->recruiter?->email ?? '';

        $companyName = $company?->company_name ?? '';

        $dateFormat = (function_exists('company') && company()) ? company()->date_format : 'd-M-Y';
        $currentDate = Carbon::now()->format($dateFormat);
        $currentDateSlash = Carbon::now()->format('d/m/Y');

        $pairs = [
            '@{{ $jobOffer->jobApplication->full_name }}' => $app->full_name ?? '',
            '@{{ $jobOffer->jobApplication->email }}' => $app->email ?? '',
            '@{{ $jobOffer->jobApplication->phone }}' => $app->phone ?? '',
            '@{{ $jobOffer->job->title }}' => $job ? ucwords($job->title) : '',
            '@{{ $jobOffer->job->team->team_name }}' => $job?->team?->team_name ?? 'Sales Department',
            '@{{ $jobOffer->job->location->location }}' => $job?->location?->location ?? 'Headquarter',
            '@{{ $jobOffer->job->recruiter->name }}' => $recruiterName,
            '@{{ $jobOffer->job->recruiter->designation }}' => $recruiterDesignation,
            '@{{ $jobOffer->job->recruiter->email }}' => $recruiterEmail,
            '@{{ $jobOffer->job->job_type }}' => $jobTypeEnum,
            '@{{ $jobOffer->job->job_type_label }}' => $jobTypeLabel,
            '@{{ $jobOffer->job->pay_according }}' => $payAccording,
            '@{{ $jobOffer->comp_amount }}' => $compAmount,
            '@{{ $jobOffer->expected_joining_date }}' => $expectedJoin,
            '@{{ $jobOffer->job_expire }}' => $jobExpire,
            '@{{ $jobOffer->offer_issue_date }}' => $issueDate,
            '@{{ $jobOffer->offer_issue_date_slash }}' => $issueDateSlash,
            '@{{ $jobOffer->offer_valid_days }}' => $validDays,
            '@{{ $company->company_name }}' => $companyName,
            '@{{ $current_date }}' => $currentDate,
            '@{{ $today }}' => $currentDate,
            '@{{ $current_date_slash }}' => $currentDateSlash,
        ];

        foreach ($pairs as $token => $value) {
            $content = str_replace($token, (string) $value, $content);
            if (str_starts_with($token, '@')) {
                $content = str_replace(substr($token, 1), (string) $value, $content);
            }
        }

        // Legacy Blade-style date in stored HTML (not evaluated when saved)
        $content = str_replace('{{ date("d-M-Y") }}', $currentDate, $content);
        $content = str_replace("{{ date('d-M-Y') }}", $currentDate, $content);

        return $content;
    }
}
