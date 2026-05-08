<?php

namespace App\Services;

use App\Models\Expense;

class PharmaExpenseStatementImporter
{
    /**
     * Month is locked when any pharma statement row for that user/month is pending or approved.
     */
    public static function isMonthLocked(int $companyId, int $userId, string $expenseMonthYm): bool
    {
        return Expense::where('company_id', $companyId)
            ->where('expense_type', 'pharma_statement')
            ->where('user_id', $userId)
            ->where('expense_month', $expenseMonthYm)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();
    }

    /**
     * Block import for a calendar day if a non-rejected row already exists for that user/month/date.
     */
    public static function hasDuplicateDay(int $companyId, int $userId, string $expenseMonthYm, string $purchaseDateYmd): bool
    {
        return Expense::where('company_id', $companyId)
            ->where('expense_type', 'pharma_statement')
            ->where('user_id', $userId)
            ->where('expense_month', $expenseMonthYm)
            ->whereDate('purchase_date', $purchaseDateYmd)
            ->whereNotIn('status', ['rejected'])
            ->exists();
    }

    /**
     * Same emptiness check as ExpenseController::storePharma (skip rows with no meaningful data).
     *
     * @param  array<string, mixed>  $expenseData
     */
    public static function rowHasExpenseData(array $expenseData): bool
    {
        return !empty($expenseData['town_worked'])
            || !empty($expenseData['worked_with'])
            || (!empty($expenseData['no_of_doctors_met']) && (float) $expenseData['no_of_doctors_met'] > 0)
            || (!empty($expenseData['no_of_retailers_met']) && (float) $expenseData['no_of_retailers_met'] > 0)
            || !empty($expenseData['headquarter_from'])
            || !empty($expenseData['headquarter_to'])
            || !empty($expenseData['mode_of_transport'])
            || (!empty($expenseData['km']) && (float) $expenseData['km'] > 0)
            || (!empty($expenseData['fare_rs']) && (float) $expenseData['fare_rs'] > 0)
            || (!empty($expenseData['daily_allowance_hq_rs']) && (float) $expenseData['daily_allowance_hq_rs'] > 0)
            || (!empty($expenseData['daily_allowance_ex_rs']) && (float) $expenseData['daily_allowance_ex_rs'] > 0)
            || (!empty($expenseData['daily_allowance_os_rs']) && (float) $expenseData['daily_allowance_os_rs'] > 0)
            || (!empty($expenseData['fixed_expenses']) && (float) $expenseData['fixed_expenses'] > 0)
            || (!empty($expenseData['other_expenses']) && (float) $expenseData['other_expenses'] > 0);
    }

    public static function encodeWorkedWith(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $parts = array_filter(array_map('trim', explode(',', $raw)));
        if ($parts === []) {
            return null;
        }

        return json_encode(array_values($parts));
    }
}
