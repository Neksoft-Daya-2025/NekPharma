<?php

namespace App\Jobs;

use App\Models\Currency;
use App\Models\Expense;
use App\Services\PharmaExpenseStatementImporter;
use App\Traits\ExcelImportable;
use App\Traits\UniversalSearchTrait;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportPharmaExpenseJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UniversalSearchTrait;
    use ExcelImportable;

    private $row;

    /** @var array<int|string, string> column index => field id */
    private $columns;

    private $company;

    /** @var array<string, mixed> */
    private array $pharmaContext;

    public function __construct($row, $columns, $company = null, array $pharmaContext = [])
    {
        $this->row = $row;
        $this->columns = $columns;
        $this->company = $company;
        $this->pharmaContext = $pharmaContext;
    }

    public function handle(): void
    {
        $expenseData = $this->buildExpenseDataFromRow();

        if (!PharmaExpenseStatementImporter::rowHasExpenseData($expenseData)) {
            return;
        }

        if (!$this->isColumnExists('date')) {
            $this->failJob(__('messages.invalidData'));

            return;
        }

        $dateRaw = $this->getColumnValue('date');
        $purchaseCarbon = $this->parsePurchaseDate($dateRaw);
        if ($purchaseCarbon === null) {
            $this->failJob(__('messages.invalidDate'));

            return;
        }

        $purchaseYmd = $purchaseCarbon->format('Y-m-d');
        $expectedMonth = $this->pharmaContext['expense_month'];
        $expected = Carbon::createFromFormat('Y-m', $expectedMonth);
        if ($purchaseCarbon->year !== (int) $expected->year || $purchaseCarbon->month !== (int) $expected->month) {
            $this->failJob(__('messages.pharmaExpenseImportDateNotInSelectedMonth'));

            return;
        }

        $companyId = (int) $this->company->id;
        $userId = (int) $this->pharmaContext['pharma_user_id'];

        if (PharmaExpenseStatementImporter::hasDuplicateDay($companyId, $userId, $expectedMonth, $purchaseYmd)) {
            $this->failJob(__('messages.pharmaExpenseImportDuplicateDay'));

            return;
        }

        $currencySetting = Currency::findOrFail($this->company->currency_id);

        DB::beginTransaction();
        try {
            $expense = new Expense();
            $expense->company_id = $companyId;
            $expense->expense_type = 'pharma_statement';
            $expense->user_id = $userId;
            $expense->headquarter_id = (int) $this->pharmaContext['pharma_headquarter_id'];
            $expense->expense_month = $expectedMonth;
            $expense->posted_on = $this->pharmaContext['posted_on'];
            $expense->no_of_vouchers = (int) $this->pharmaContext['no_of_vouchers'];

            $expense->purchase_date = $purchaseYmd;
            $expense->day = $purchaseCarbon->format('l');

            $expense->town_worked = $expenseData['town_worked'] ?? null;
            $expense->worked_with = $expenseData['worked_with'] ?? null;
            $expense->no_of_doctors_met = (int) ($expenseData['no_of_doctors_met'] ?? 0);
            $expense->no_of_retailers_met = (int) ($expenseData['no_of_retailers_met'] ?? 0);
            $expense->headquarter_from = $expenseData['headquarter_from'] ?? null;
            $expense->headquarter_to = $expenseData['headquarter_to'] ?? null;
            $expense->mode_of_transport = $expenseData['mode_of_transport'] ?? null;
            $expense->km = (float) ($expenseData['km'] ?? 0);
            $expense->fare_rs = (float) ($expenseData['fare_rs'] ?? 0);
            $expense->daily_allowance_hq_rs = (float) ($expenseData['daily_allowance_hq_rs'] ?? 0);
            $expense->daily_allowance_ex_rs = (float) ($expenseData['daily_allowance_ex_rs'] ?? 0);
            $expense->daily_allowance_os_rs = (float) ($expenseData['daily_allowance_os_rs'] ?? 0);
            $expense->fixed_expenses = (float) ($expenseData['fixed_expenses'] ?? 0);
            $expense->other_expenses = (float) ($expenseData['other_expenses'] ?? 0);
            $expense->remarks = $expenseData['remarks'] ?? null;

            $totalPrice = ($expense->fare_rs ?? 0)
                + ($expense->daily_allowance_hq_rs ?? 0)
                + ($expense->daily_allowance_ex_rs ?? 0)
                + ($expense->daily_allowance_os_rs ?? 0)
                + ($expense->fixed_expenses ?? 0)
                + ($expense->other_expenses ?? 0);

            $expense->item_name = 'Pharma Expense - ' . $expense->purchase_date;
            $expense->price = round($totalPrice, $currencySetting->no_of_decimal);
            $expense->currency_id = $currencySetting->id;
            $expense->default_currency_id = $currencySetting->id;
            $expense->exchange_rate = $currencySetting->exchange_rate ?? 1;

            if (!empty($this->pharmaContext['is_admin'])) {
                $expense->status = 'approved';
                $expense->approver_id = (int) $this->pharmaContext['added_by_user_id'];
                $expense->approved_at = now();
                $expense->submitted_to = null;
            } else {
                $expense->status = 'pending';
                $expense->submitted_to = (int) $this->pharmaContext['submitted_to'];
                $expense->approver_id = null;
                $expense->approved_at = null;
            }

            $expense->added_by = (int) $this->pharmaContext['added_by_user_id'];

            $expense->save();

            DB::commit();
        } catch (InvalidFormatException $e) {
            DB::rollBack();
            $this->failJob(__('messages.invalidDate'));
        } catch (Exception $e) {
            DB::rollBack();
            $this->failJobWithMessage($e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildExpenseDataFromRow(): array
    {
        $workedRaw = $this->isColumnExists('worked_with') ? $this->getColumnValue('worked_with') : null;
        $workedJson = PharmaExpenseStatementImporter::encodeWorkedWith(is_string($workedRaw) ? $workedRaw : (string) $workedRaw);

        return [
            'town_worked' => $this->optionalString('town_worked'),
            'worked_with' => $workedJson,
            'no_of_doctors_met' => $this->optionalFloat('no_of_doctors_met'),
            'no_of_retailers_met' => $this->optionalFloat('no_of_retailers_met'),
            'headquarter_from' => $this->optionalStationId('headquarter_from'),
            'headquarter_to' => $this->optionalStationId('headquarter_to'),
            'mode_of_transport' => $this->optionalString('mode_of_transport'),
            'km' => $this->optionalFloat('km'),
            'fare_rs' => $this->optionalFloat('fare_rs'),
            'daily_allowance_hq_rs' => $this->optionalFloat('daily_allowance_hq_rs'),
            'daily_allowance_ex_rs' => $this->optionalFloat('daily_allowance_ex_rs'),
            'daily_allowance_os_rs' => $this->optionalFloat('daily_allowance_os_rs'),
            'fixed_expenses' => $this->optionalFloat('fixed_expenses'),
            'other_expenses' => $this->optionalFloat('other_expenses'),
            'remarks' => $this->optionalString('remarks'),
        ];
    }

    private function optionalString(string $field): ?string
    {
        if (!$this->isColumnExists($field)) {
            return null;
        }
        $v = $this->getColumnValue($field);
        if ($v === null || $v === '') {
            return null;
        }

        return is_string($v) ? trim($v) : (string) $v;
    }

    private function optionalStationId(string $field): ?string
    {
        $s = $this->optionalString($field);
        if ($s === null) {
            return null;
        }

        return $s;
    }

    private function optionalFloat(string $field): float
    {
        if (!$this->isColumnExists($field)) {
            return 0;
        }
        $v = $this->getColumnValue($field);
        if ($v === null || $v === '') {
            return 0;
        }
        if (is_numeric($v)) {
            return (float) $v;
        }

        return (float) str_replace(',', '', (string) $v);
    }

    private function parsePurchaseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $n = (float) $value;
            if ($n > 200 && $n < 60000) {
                try {
                    return Carbon::instance(ExcelDate::excelToDateTimeObject($n));
                } catch (\Throwable $e) {
                    // fall through
                }
            }
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance(\DateTimeImmutable::createFromInterface($value));
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
