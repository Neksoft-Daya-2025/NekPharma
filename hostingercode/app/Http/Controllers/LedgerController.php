<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Helpers\PharmaDesignationHelper;
use App\Models\CFAStockist;
use App\Models\CreditNotes;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LedgerController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Company → CFA Ledger: list of CFAs (clients) with date/party filters.
     */
    public function indexCFALedger()
    {
        if (!PharmaDesignationHelper::hasFullCFAAccess() && !in_array('client', user_roles())) {
            $viewPermission = user()->permission('view_cfa_distributor_invoices');
            abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
        }

        $this->pageTitle = __('app.companyCfaLedger');

        $this->clients = $this->getCFAClientsForLedger();
        $this->startDate = request('start_date') ? request('start_date') : now($this->company->timezone)->startOfMonth()->format($this->company->date_format);
        $this->endDate = request('end_date') ? request('end_date') : now($this->company->timezone)->format($this->company->date_format);
        $this->partyId = request('party_id');

        return view('ledger.cfa-ledger', $this->data);
    }

    /**
     * Data for Company → CFA Ledger (AJAX).
     */
    public function dataCFALedger(Request $request)
    {
        if (!PharmaDesignationHelper::hasFullCFAAccess() && !in_array('client', user_roles())) {
            $viewPermission = user()->permission('view_cfa_distributor_invoices');
            if (!in_array($viewPermission, ['all', 'added', 'owned', 'both'])) {
                return Reply::error(__('messages.permissionDenied'));
            }
        }

        $partyId = $request->party_id;
        if (!$partyId || $partyId === 'all') {
            return Reply::dataOnly(['rows' => [], 'party_name' => '', 'opening_balance' => 0]);
        }

        $startDate = $this->parseDateParam($request->start_date, now()->startOfMonth()->toDateString());
        $endDate = $this->parseDateParam($request->end_date, now()->toDateString());

        $result = $this->buildCFALedgerData((int) $partyId, $startDate, $endDate);
        $partyName = User::without('session')->with('clientDetails')->find($partyId);
        $partyName = $partyName ? ($partyName->clientDetails->company_name ?? $partyName->name) : '';

        return Reply::dataOnly([
            'rows' => $result['rows'],
            'party_name' => $partyName,
            'opening_balance' => $result['opening_balance'],
        ]);
    }

    /**
     * CFA → Stockist Ledger: list of stockists with date/party filters.
     */
    public function indexCFAStockistLedger()
    {
        if (!PharmaDesignationHelper::hasFullCFAAccess() && !in_array('client', user_roles())) {
            $viewPermission = user()->permission('view_cfa_stockist_invoices');
            abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
        }

        $this->pageTitle = __('app.cfaStockistLedger');

        $this->cfaStockists = $this->getStockistsForLedger();
        $this->startDate = request('start_date') ? request('start_date') : now($this->company->timezone)->startOfMonth()->format($this->company->date_format);
        $this->endDate = request('end_date') ? request('end_date') : now($this->company->timezone)->format($this->company->date_format);
        $this->partyId = request('party_id');

        return view('ledger.cfa-stockist-ledger', $this->data);
    }

    /**
     * Data for CFA → Stockist Ledger (AJAX).
     */
    public function dataCFAStockistLedger(Request $request)
    {
        if (!PharmaDesignationHelper::hasFullCFAAccess() && !in_array('client', user_roles())) {
            $viewPermission = user()->permission('view_cfa_stockist_invoices');
            if (!in_array($viewPermission, ['all', 'added', 'owned', 'both'])) {
                return Reply::error(__('messages.permissionDenied'));
            }
        }

        $partyId = $request->party_id;
        if (!$partyId || $partyId === 'all') {
            return Reply::dataOnly(['rows' => [], 'party_name' => '', 'opening_balance' => 0]);
        }

        $startDate = $this->parseDateParam($request->start_date, now()->startOfMonth()->toDateString());
        $endDate = $this->parseDateParam($request->end_date, now()->toDateString());

        $result = $this->buildCFAStockistLedgerData((int) $partyId, $startDate, $endDate);
        $stockist = CFAStockist::find($partyId);
        $partyName = $stockist ? ($stockist->shopname ?? $stockist->fullname ?? $stockist->cfa_stockist_id) : '';

        return Reply::dataOnly([
            'rows' => $result['rows'],
            'party_name' => $partyName,
            'opening_balance' => $result['opening_balance'],
        ]);
    }

    /**
     * Parse date request param to Y-m-d string.
     */
    protected function parseDateParam($value, string $default): string
    {
        if (!$value) {
            return $default;
        }
        $value = trim($value);
        try {
            if (strlen($value) === 10 && $this->company && $this->company->date_format) {
                $parsed = Carbon::createFromFormat($this->company->date_format, $value);
                return $parsed->toDateString();
            }
            return Carbon::parse($value)->toDateString();
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Get CFA/Distributor clients for ledger dropdown (same logic as CFA distributor invoices).
     */
    protected function getCFAClientsForLedger()
    {
        $query = User::without('session')
            ->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->join('client_details', 'users.id', '=', 'client_details.user_id')
            ->leftJoin('client_categories', 'client_details.category_id', '=', 'client_categories.id')
            ->leftJoin('client_areas', 'client_details.id', '=', 'client_areas.client_detail_id')
            ->select('users.id', 'users.name', 'users.email', 'client_details.company_name')
            ->whereNull('users.is_client_contact')
            ->where('roles.name', 'client')
            ->where('users.status', 'active')
            ->where('users.company_id', company()->id)
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereRaw('LOWER(client_categories.category_name) LIKE ?', ['%cfa%'])
                        ->orWhereRaw('LOWER(client_categories.category_name) LIKE ?', ['%distributor%']);
                })
                    ->orWhereNotNull('client_areas.area_id');
            })
            ->groupBy('users.id', 'users.name', 'users.email', 'client_details.company_name')
            ->orderBy('client_details.company_name', 'asc');

        if (in_array('client', user_roles())) {
            $query->where('users.id', user()->id);
        }

        return $query->get();
    }

    /**
     * Get CFA Stockists for ledger dropdown.
     */
    protected function getStockistsForLedger()
    {
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            return CFAStockist::where('company_id', company()->id)->orderBy('shopname')->get();
        }
        return CFAStockist::where('company_id', company()->id)
            ->whereHas('cfaDistributors', fn ($q) => $q->where('cfa_distributor_id', user()->id))
            ->orderBy('shopname')
            ->get();
    }

    /**
     * Build ledger rows + opening balance for Company → CFA (party = client_id).
     */
    protected function buildCFALedgerData(int $clientId, string $startDate, string $endDate): array
    {
        $invoiceIds = Invoice::where('company_id', company()->id)
            ->where('client_id', $clientId)
            ->where('credit_note', 0)
            ->whereNotIn('status', ['canceled', 'draft'])
            ->whereHas('cfaDistributorStocks')
            ->pluck('id')
            ->toArray();

        if (empty($invoiceIds)) {
            return ['rows' => [], 'opening_balance' => 0];
        }

        return $this->buildLedgerRowsFromInvoiceIds($invoiceIds, $startDate, $endDate);
    }

    /**
     * Build ledger rows + opening balance for CFA → Stockist (party = cfa_stockist_id).
     */
    protected function buildCFAStockistLedgerData(int $cfaStockistId, string $startDate, string $endDate): array
    {
        $baseQuery = Invoice::where('invoices.company_id', company()->id)
            ->where('invoices.credit_note', 0)
            ->whereNotIn('invoices.status', ['canceled', 'draft'])
            ->join('cfa_stockist_stocks', 'cfa_stockist_stocks.invoice_id', '=', 'invoices.id')
            ->where('cfa_stockist_stocks.cfa_stockist_id', $cfaStockistId);

        if (!PharmaDesignationHelper::hasFullCFAAccess()) {
            if (in_array('client', user_roles())) {
                $baseQuery->where('invoices.client_id', user()->id);
            } else {
                $baseQuery->where('cfa_stockist_stocks.cfa_distributor_id', user()->id);
            }
        }

        $invoiceIds = $baseQuery->select('invoices.id')->distinct()->pluck('id')->toArray();

        if (empty($invoiceIds)) {
            return ['rows' => [], 'opening_balance' => 0];
        }

        return $this->buildLedgerRowsFromInvoiceIds($invoiceIds, $startDate, $endDate);
    }

    /**
     * Build unified ledger rows (date, particular, debit, credit, balance) and opening balance for given invoice IDs and date range.
     */
    protected function buildLedgerRowsFromInvoiceIds(array $invoiceIds, string $startDate, string $endDate): array
    {
        $rows = [];
        $openingDebit = Invoice::whereIn('id', $invoiceIds)
            ->where(DB::raw('DATE(issue_date)'), '<', $startDate)
            ->sum('total');
        $openingCreditPayments = Payment::whereIn('invoice_id', $invoiceIds)
            ->where('status', 'complete')
            ->where(DB::raw('DATE(paid_on)'), '<', $startDate)
            ->sum('amount');
        $openingCreditCN = CreditNotes::whereIn('invoice_id', $invoiceIds)
            ->where(DB::raw('DATE(issue_date)'), '<', $startDate)
            ->sum('total');
        $openingBalance = round($openingDebit - $openingCreditPayments - $openingCreditCN, 2);

        $entries = [];

        $stockistInvoiceIds = Invoice::whereIn('id', $invoiceIds)->whereHas('cfaStockistStocks')->pluck('id')->toArray();
        $invoices = Invoice::whereIn('id', $invoiceIds)
            ->where(DB::raw('DATE(issue_date)'), '>=', $startDate)
            ->where(DB::raw('DATE(issue_date)'), '<=', $endDate)
            ->orderBy('issue_date')->orderBy('id')
            ->get(['id', 'invoice_number', 'issue_date', 'total']);
        foreach ($invoices as $inv) {
            $link = in_array($inv->id, $stockistInvoiceIds)
                ? route('cfa-stockist-invoices.show', $inv->id)
                : route('cfa-distributor-invoices.show', $inv->id);
            $entries[] = [
                'date' => $inv->issue_date->format('Y-m-d'),
                'sort' => $inv->issue_date->format('Y-m-d H:i:s') . '.' . $inv->id,
                'type' => 'invoice',
                'reference' => $inv->invoice_number,
                'link' => $link,
                'debit' => round((float) $inv->total, 2),
                'credit' => 0,
            ];
        }

        $payments = Payment::whereIn('invoice_id', $invoiceIds)
            ->where('status', 'complete')
            ->where(DB::raw('DATE(paid_on)'), '>=', $startDate)
            ->where(DB::raw('DATE(paid_on)'), '<=', $endDate)
            ->orderBy('paid_on')->orderBy('id')
            ->get(['id', 'invoice_id', 'paid_on', 'amount', 'gateway', 'transaction_id', 'remarks']);
        foreach ($payments as $pmt) {
            $ref = $pmt->transaction_id ?: $pmt->gateway ?: __('app.payment');
            if ($pmt->remarks) {
                $ref .= ' - ' . $pmt->remarks;
            }
            $entries[] = [
                'date' => $pmt->paid_on->format('Y-m-d'),
                'sort' => $pmt->paid_on->format('Y-m-d H:i:s') . '.p' . $pmt->id,
                'type' => 'payment',
                'reference' => $ref,
                'link' => null,
                'debit' => 0,
                'credit' => round((float) $pmt->amount, 2),
            ];
        }

        $creditNotes = CreditNotes::whereIn('invoice_id', $invoiceIds)
            ->where(DB::raw('DATE(issue_date)'), '>=', $startDate)
            ->where(DB::raw('DATE(issue_date)'), '<=', $endDate)
            ->orderBy('issue_date')->orderBy('id')
            ->get(['id', 'invoice_id', 'cn_number', 'issue_date', 'total']);
        foreach ($creditNotes as $cn) {
            $entries[] = [
                'date' => $cn->issue_date->format('Y-m-d'),
                'sort' => $cn->issue_date->format('Y-m-d H:i:s') . '.c' . $cn->id,
                'type' => 'credit_note',
                'reference' => $cn->cn_number,
                'link' => route('creditnotes.show', $cn->id),
                'debit' => 0,
                'credit' => round((float) $cn->total, 2),
            ];
        }

        usort($entries, fn ($a, $b) => strcmp($a['sort'], $b['sort']));

        $balance = $openingBalance;
        foreach ($entries as $e) {
            $balance += $e['debit'] - $e['credit'];
            $rows[] = [
                'date' => $e['date'],
                'particular' => $e['reference'],
                'link' => $e['link'],
                'debit' => $e['debit'],
                'credit' => $e['credit'],
                'balance' => round($balance, 2),
            ];
        }

        return ['rows' => $rows, 'opening_balance' => $openingBalance];
    }
}
