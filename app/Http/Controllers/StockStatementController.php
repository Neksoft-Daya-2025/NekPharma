<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Helper\RoleHierarchy;
use App\Models\CFAStockist;
use App\Models\CFAStockistStock;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\SalesPlanTarget;
use App\Models\StockStatement;
use App\Models\StockStatementLine;
use App\Models\User;
use App\Exports\StockStatementSampleExport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\AccessibleHeadquarters;

class StockStatementController extends AccountBaseController
{
    use AccessibleHeadquarters;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('app.salesStockStatement');
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('stock_statements', $this->user->modules) && !in_array('dcr_reports', $this->user->modules));
            return $next($request);
        });
    }

    private function stockStatementPermission(string $action): string|bool
    {
        $permission = user()->permission($action . '_stock_statements');

        return $permission ?: user()->permission($action . '_dcr_reports');
    }

    private function canAccessStatement(StockStatement $statement, string $action): bool
    {
        if (user()->hasAdminLikeAccess()) {
            return true;
        }

        $permission = $this->stockStatementPermission($action);
        if (! in_array($permission, ['all', 'added', 'owned', 'both'], true)) {
            return false;
        }

        if ($permission !== 'all' && (int) $statement->user_id !== (int) user()->id) {
            return false;
        }

        $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
        if (! in_array((int) $statement->user_id, array_map('intval', $viewableIds), true)) {
            return false;
        }

        $stockistIds = $this->assignedCfaStockistsQuery()->pluck('id')->map(fn ($id) => (int) $id)->toArray();

        return in_array((int) $statement->cfa_stockist_id, $stockistIds, true)
            || (int) $statement->user_id === (int) user()->id;
    }

    private function canModifyStatement(StockStatement $statement, string $action): bool
    {
        if ($statement->status !== 'draft' && ! user()->hasAdminLikeAccess()) {
            return false;
        }

        return $this->canAccessStatement($statement, $action);
    }

    /**
     * Get CFA Stockists assignable to current user (by HQ/area).
     */
    protected function assignedCfaStockistsQuery()
    {
        $q = CFAStockist::where('company_id', company()->id);
        $hqIds = $this->accessibleHeadquarterIds();
        $areaIds = $this->accessibleAreaIds();
        if ($hqIds === null) {
            return $q->orderBy('shopname');
        }
        if ((is_array($hqIds) && count($hqIds) === 0) && (is_array($areaIds) && count($areaIds) === 0)) {
            return $q->whereRaw('1 = 0')->orderBy('shopname');
        }
        if ((is_array($hqIds) && count($hqIds) > 0) || (is_array($areaIds) && count($areaIds) > 0)) {
            $q->where(function ($query) use ($hqIds, $areaIds) {
                if (is_array($hqIds) && count($hqIds) > 0) {
                    $query->whereIn('headquarter_id', $hqIds);
                }
                if (is_array($areaIds) && count($areaIds) > 0) {
                    $query->orWhereIn('area_id', $areaIds);
                }
            });
        }
        return $q->orderBy('shopname');
    }

    /**
     * List statements for current user (MR: own; manager/admin: by hierarchy + HQ/area).
     */
    public function index(Request $request)
    {
        $this->viewPermission = $this->stockStatementPermission('view');
        abort_403(!in_array($this->viewPermission, ['all', 'added', 'owned', 'both'], true));

        $query = StockStatement::with(['user', 'cfaStockist'])
            ->where('company_id', company()->id);

        if (!user()->hasAdminLikeAccess()) {
            if ($this->viewPermission !== 'all') {
                $query->where('user_id', user()->id);
            } else {
                $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
                $query->whereIn('user_id', $viewableIds);
                $hqIds = $this->accessibleHeadquarterIds();
                $areaIds = $this->accessibleAreaIds();
                if (is_array($hqIds) && count($hqIds) === 0 && is_array($areaIds) && count($areaIds) === 0) {
                    $query->where('user_id', user()->id);
                } else {
                    $stockistIds = $this->assignedCfaStockistsQuery()->pluck('id');
                    $query->where(function ($q) use ($stockistIds) {
                        $q->where('user_id', user()->id)
                            ->orWhereIn('cfa_stockist_id', $stockistIds);
                    });
                }
            }
        }

        if ($request->filled('period_month')) {
            $query->where('period_month', $request->period_month);
        }
        if ($request->filled('period_year')) {
            $query->where('period_year', $request->period_year);
        }
        if ($request->filled('cfa_stockist_id')) {
            $query->where('cfa_stockist_id', $request->cfa_stockist_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $this->statements = $query->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        $this->cfaStockists = $this->assignedCfaStockistsQuery()->get(['id', 'shopname', 'cfa_stockist_id']);
        $this->filterMonth = $request->period_month;
        $this->filterYear = $request->period_year;
        $this->filterStatus = $request->status;
        $this->filterStockistId = $request->cfa_stockist_id;
        $this->addPermission = $this->stockStatementPermission('add');
        $this->editPermission = $this->stockStatementPermission('edit');
        $this->deletePermission = $this->stockStatementPermission('delete');

        // SRS 3.2.8: Mandatory for each assigned stockist – show pending (no submitted statement) for period
        $this->missingStockistsForPeriod = collect();
        $this->mandatoryPeriodMonth = null;
        $this->mandatoryPeriodYear = null;
        if (!user()->hasAdminLikeAccess()) {
            $periodMonth = $request->filled('period_month') ? (int) $request->period_month : Carbon::now()->month;
            $periodYear = $request->filled('period_year') ? (int) $request->period_year : Carbon::now()->year;
            $assignedIds = $this->assignedCfaStockistsQuery()->pluck('id');
            if ($assignedIds->isNotEmpty()) {
                $submittedStockistIds = StockStatement::where('company_id', company()->id)
                    ->where('user_id', user()->id)
                    ->where('period_month', $periodMonth)
                    ->where('period_year', $periodYear)
                    ->where('status', 'submitted')
                    ->pluck('cfa_stockist_id');
                $missingIds = $assignedIds->diff($submittedStockistIds)->values();
                if ($missingIds->isNotEmpty()) {
                    $this->missingStockistsForPeriod = CFAStockist::whereIn('id', $missingIds)->get(['id', 'shopname', 'cfa_stockist_id']);
                    $this->mandatoryPeriodMonth = $periodMonth;
                    $this->mandatoryPeriodYear = $periodYear;
                }
            }
        }

        return view('stock-statements.index', $this->data);
    }

    /**
     * Show create form: month, year, stockist; products with opening/primary/secondary/closing.
     */
    public function create(Request $request)
    {
        $this->addPermission = $this->stockStatementPermission('add');
        abort_403(!in_array($this->addPermission, ['all', 'added'], true));

        $this->cfaStockists = $this->assignedCfaStockistsQuery()->get();
        $this->products = Product::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $periodMonth = (int) ($request->period_month ?? Carbon::now()->month);
        $periodYear = (int) ($request->period_year ?? Carbon::now()->year);
        $cfaStockistId = $request->cfa_stockist_id ? (int) $request->cfa_stockist_id : null;
        $this->periodMonth = $periodMonth;
        $this->periodYear = $periodYear;
        $this->cfaStockistId = $cfaStockistId;

        if (request()->ajax()) {
            $html = view('stock-statements.ajax.create', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }
        return view('stock-statements.create', $this->data);
    }

    /**
     * Sample CSV for statement line import on the create form.
     */
    public function downloadSample()
    {
        return Excel::download(
            new StockStatementSampleExport(),
            'stock-statement-lines-sample.csv',
            \Maatwebsite\Excel\Excel::CSV,
            ['Content-Type' => 'text/csv']
        );
    }

    /**
     * Parse uploaded CSV and return statement lines for the create form.
     */
    public function importLines(Request $request)
    {
        $this->addPermission = $this->stockStatementPermission('add');
        abort_403(!in_array($this->addPermission, ['all', 'added'], true));

        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt,xls,xlsx|max:5120',
            'cfa_stockist_id' => 'required|exists:cfa_stockists,id',
            'period_month' => 'required|integer|between:1,12',
            'period_year' => 'required|integer|min:2020|max:2100',
        ]);

        $cfaStockistId = (int) $request->cfa_stockist_id;
        $periodMonth = (int) $request->period_month;
        $periodYear = (int) $request->period_year;

        $stockistIds = $this->assignedCfaStockistsQuery()->pluck('id')->toArray();
        if (! in_array($cfaStockistId, $stockistIds, true)) {
            return Reply::error(__('messages.unauthorizedAccess'));
        }

        /** @var UploadedFile $file */
        $file = $request->file('import_file');
        $parsed = $this->parseStockStatementFile($file);

        if ($parsed['errors']) {
            return Reply::error(implode(' ', $parsed['errors']));
        }

        if (empty($parsed['rows'])) {
            return Reply::error(__('messages.noRecordFound'));
        }

        $productsByName = Product::where('company_id', company()->id)
            ->get(['id', 'name'])
            ->keyBy(fn ($product) => $this->normalizeProductKey($product->name));

        $lines = [];
        $skipped = [];
        $seenProductIds = [];

        foreach ($parsed['rows'] as $rowNum => $row) {
            $productKey = $this->normalizeProductKey($row['product']);
            $product = $productsByName->get($productKey);

            if (! $product) {
                $skipped[] = ['row' => $rowNum, 'product' => $row['product'], 'reason' => 'Product not found'];
                continue;
            }

            if (in_array($product->id, $seenProductIds, true)) {
                $skipped[] = ['row' => $rowNum, 'product' => $row['product'], 'reason' => 'Duplicate product in file'];
                continue;
            }

            $seenProductIds[] = $product->id;

            $opening = $row['opening_qty'] !== null
                ? $row['opening_qty']
                : $this->getOpeningQty($cfaStockistId, $product->id, $periodMonth, $periodYear);
            $primary = $row['primary_qty'] !== null
                ? $row['primary_qty']
                : $this->getPrimaryQty($cfaStockistId, $product->id, $periodMonth, $periodYear);
            $secondary = $row['secondary_qty'] ?? 0;
            $closing = $row['closing_qty'] !== null
                ? $row['closing_qty']
                : ($opening + $primary - $secondary);

            $lines[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'opening_qty' => round($opening, 2),
                'primary_qty' => round($primary, 2),
                'secondary_qty' => round($secondary, 2),
                'closing_qty' => round($closing, 2),
            ];
        }

        if (empty($lines)) {
            return Reply::error(__('messages.noRecordFound') . ' — no valid product rows matched.');
        }

        return Reply::dataOnly([
            'status' => 'success',
            'lines' => $lines,
            'skipped' => $skipped,
            'imported' => count($lines),
        ]);
    }

    /**
     * @return array{rows: array<int, array{product: string, opening_qty: ?float, primary_qty: ?float, secondary_qty: float, closing_qty: ?float}>, errors: array<int, string>}
     */
    private function parseStockStatementCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return ['rows' => [], 'errors' => ['Could not read CSV file.']];
        }

        $csvRows = [];

        while (($data = fgetcsv($handle)) !== false) {
            $csvRows[] = $data;
        }

        fclose($handle);

        return $this->parseStockStatementRows($csvRows);
    }

    private function parseStockStatementFile(UploadedFile $file): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        if (in_array($extension, ['xls', 'xlsx'], true)) {
            try {
                $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath())->getActiveSheet();
            } catch (\Throwable $e) {
                return ['rows' => [], 'errors' => ['Could not read Excel file. Please download the sample and try again.']];
            }

            return $this->parseStockStatementRows($sheet->toArray());
        }

        return $this->parseStockStatementCsv($file->getRealPath());
    }

    private function parseStockStatementRows(array $fileRows): array
    {
        $headerMap = null;
        $rows = [];
        $errors = [];
        $lineNumber = 0;

        foreach ($fileRows as $data) {
            $lineNumber++;
            $data = is_array($data) ? array_values($data) : [];

            if ($this->csvRowIsEmpty($data)) {
                continue;
            }

            if ($headerMap === null) {
                $headerMap = $this->mapStockStatementCsvHeaders($data);
                if ($headerMap === null) {
                    return ['rows' => [], 'errors' => ['Invalid file header. Expected columns: Product, Opening Qty, Primary Qty, Secondary Qty, Closing Qty.']];
                }
                continue;
            }

            $product = trim((string) ($data[$headerMap['product']] ?? ''));
            if ($product === '') {
                continue;
            }

            $opening = $this->parseCsvQty($data[$headerMap['opening_qty']] ?? null);
            $primary = $this->parseCsvQty($data[$headerMap['primary_qty']] ?? null);
            $secondary = $this->parseCsvQty($data[$headerMap['secondary_qty']] ?? null);
            $closing = $this->parseCsvQty($data[$headerMap['closing_qty']] ?? null);

            if ($opening !== null && $opening < 0) {
                $errors[] = "Row {$lineNumber}: Opening Qty cannot be negative.";
            }
            if ($primary !== null && $primary < 0) {
                $errors[] = "Row {$lineNumber}: Primary Qty cannot be negative.";
            }
            if ($secondary !== null && $secondary < 0) {
                $errors[] = "Row {$lineNumber}: Secondary Qty cannot be negative.";
            }
            if ($closing !== null && $closing < 0) {
                $errors[] = "Row {$lineNumber}: Closing Qty cannot be negative.";
            }

            $rows[$lineNumber] = [
                'product' => $product,
                'opening_qty' => $opening,
                'primary_qty' => $primary,
                'secondary_qty' => $secondary ?? 0.0,
                'closing_qty' => $closing,
            ];
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    private function mapStockStatementCsvHeaders(array $headerRow): ?array
    {
        $normalize = static fn ($value) => strtolower(trim(preg_replace('/[^a-z0-9]/', '', (string) $value)));

        $aliases = [
            'product' => ['product', 'productname', 'productname', 'item', 'itemname', 'medicine'],
            'opening_qty' => ['openingqty', 'opening', 'openingstock', 'openqty'],
            'primary_qty' => ['primaryqty', 'primary', 'primarysales', 'purchaseqty'],
            'secondary_qty' => ['secondaryqty', 'secondary', 'secondarysales', 'salesqty'],
            'closing_qty' => ['closingqty', 'closing', 'closingstock', 'closeqty'],
        ];

        $map = [];
        foreach ($headerRow as $index => $heading) {
            $key = $normalize($heading);
            if ($key === '') {
                continue;
            }
            foreach ($aliases as $field => $options) {
                if (in_array($key, $options, true) && ! isset($map[$field])) {
                    $map[$field] = $index;
                    break;
                }
            }
        }

        return isset($map['product']) ? $map : null;
    }

    private function parseCsvQty($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return null;
        }

        if (! is_numeric($trimmed)) {
            return null;
        }

        return (float) $trimmed;
    }

    private function csvRowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeProductKey(?string $name): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', (string) $name)));
    }

    /**
     * Get opening qty for product + cfa_stockist: closing of previous month's statement line.
     */
    protected function getOpeningQty(int $cfaStockistId, int $productId, int $periodMonth, int $periodYear): float
    {
        $prev = Carbon::createFromDate($periodYear, $periodMonth, 1)->subMonth();
        $line = StockStatementLine::whereHas('stockStatement', function ($q) use ($cfaStockistId, $prev) {
            $q->where('company_id', company()->id)
                ->where('cfa_stockist_id', $cfaStockistId)
                ->where('period_month', $prev->month)
                ->where('period_year', $prev->year);
        })->where('product_id', $productId)->first();
        return $line ? (float) $line->closing_qty : 0;
    }

    /**
     * Get primary qty: sum of CFAStockistStock for product + cfa_stockist + invoices in statement month.
     */
    protected function getPrimaryQty(int $cfaStockistId, int $productId, int $periodMonth, int $periodYear): float
    {
        $start = Carbon::createFromDate($periodYear, $periodMonth, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        $sum = CFAStockistStock::where('company_id', company()->id)
            ->where('cfa_stockist_id', $cfaStockistId)
            ->where('product_id', $productId)
            ->whereHas('invoice', function ($q) use ($start, $end) {
                $q->whereBetween('issue_date', [$start, $end]);
            })
            ->sum('quantity');
        return (float) $sum;
    }

    /**
     * API for create form: return opening/primary for each product for given stockist + period.
     */
    public function getOpeningPrimary(Request $request)
    {
        $cfaStockistId = (int) $request->cfa_stockist_id;
        $periodMonth = (int) $request->period_month;
        $periodYear = (int) $request->period_year;
        $productIds = $request->product_ids ? (array) $request->product_ids : [];
        $out = [];
        foreach ($productIds as $pid) {
            $pid = (int) $pid;
            $out[$pid] = [
                'opening_qty' => $this->getOpeningQty($cfaStockistId, $pid, $periodMonth, $periodYear),
                'primary_qty' => $this->getPrimaryQty($cfaStockistId, $pid, $periodMonth, $periodYear),
            ];
        }
        return Reply::dataOnly(['status' => 'success', 'data' => $out]);
    }

    public function store(Request $request)
    {
        $this->addPermission = $this->stockStatementPermission('add');
        abort_403(!in_array($this->addPermission, ['all', 'added'], true));

        $request->validate([
            'period_month' => 'required|integer|between:1,12',
            'period_year' => 'required|integer|min:2020|max:2100',
            'cfa_stockist_id' => 'required|exists:cfa_stockists,id',
            'status' => 'nullable|in:draft,submitted',
            'lines' => 'required|array',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.secondary_qty' => 'nullable|numeric|min:0',
            'lines.*.opening_qty' => 'nullable|numeric|min:0',
            'lines.*.primary_qty' => 'nullable|numeric|min:0',
            'lines.*.closing_qty' => 'nullable|numeric|min:0',
        ]);

        $cfaStockistId = (int) $request->cfa_stockist_id;
        $stockistIds = $this->assignedCfaStockistsQuery()->pluck('id')->toArray();
        if (!in_array($cfaStockistId, $stockistIds)) {
            return Reply::error(__('messages.unauthorizedAccess'));
        }

        $existing = StockStatement::where('company_id', company()->id)
            ->where('user_id', user()->id)
            ->where('cfa_stockist_id', $cfaStockistId)
            ->where('period_month', $request->period_month)
            ->where('period_year', $request->period_year)
            ->first();
        if ($existing) {
            return Reply::error(__('app.stockStatementAlreadyExists'));
        }

        $productIdsSeen = [];
        foreach ($request->lines as $row) {
            $pid = (int) ($row['product_id'] ?? 0);
            if (in_array($pid, $productIdsSeen)) {
                return Reply::error(__('app.stockStatementDuplicateProduct'));
            }
            $productIdsSeen[] = $pid;
        }

        $status = $request->status === 'submitted' ? 'submitted' : 'draft';
        $statement = new StockStatement();
        $statement->company_id = company()->id;
        $statement->user_id = user()->id;
        $statement->cfa_stockist_id = $cfaStockistId;
        $statement->period_month = (int) $request->period_month;
        $statement->period_year = (int) $request->period_year;
        $statement->status = $status;
        if ($status === 'submitted') {
            $statement->submitted_at = now();
        }
        $statement->save();

        foreach ($request->lines as $row) {
            $productId = (int) $row['product_id'];
            $secondary = (float) ($row['secondary_qty'] ?? 0);
            $openingInput = isset($row['opening_qty']) && $row['opening_qty'] !== '' ? (float) $row['opening_qty'] : null;
            $primaryInput = isset($row['primary_qty']) && $row['primary_qty'] !== '' ? (float) $row['primary_qty'] : null;
            $closingInput = isset($row['closing_qty']) && $row['closing_qty'] !== '' ? (float) $row['closing_qty'] : null;
            $opening = $openingInput !== null ? $openingInput : $this->getOpeningQty($cfaStockistId, $productId, $statement->period_month, $statement->period_year);
            $primary = $primaryInput !== null ? $primaryInput : $this->getPrimaryQty($cfaStockistId, $productId, $statement->period_month, $statement->period_year);
            $closing = $closingInput !== null ? $closingInput : ($opening + $primary - $secondary);
            StockStatementLine::create([
                'stock_statement_id' => $statement->id,
                'product_id' => $productId,
                'opening_qty' => $opening,
                'primary_qty' => $primary,
                'secondary_qty' => $secondary,
                'closing_qty' => $closing,
            ]);
        }

        $redirect = route('stock-statements.show', $statement->id);
        if (request()->ajax()) {
            return Reply::redirect($redirect, __('messages.recordSaved'));
        }
        return redirect($redirect)->with('message', __('messages.recordSaved'));
    }

    public function show($id)
    {
        $statement = StockStatement::with(['user', 'cfaStockist', 'lines.product'])
            ->where('company_id', company()->id)->findOrFail($id);

        abort_403(! $this->canAccessStatement($statement, 'view'));

        $this->statement = $statement;
        $this->canEditStatement = $this->canModifyStatement($statement, 'edit');
        if (request()->ajax()) {
            return view('stock-statements.ajax.show', $this->data);
        }
        return view('stock-statements.show', $this->data);
    }

    public function edit($id)
    {
        $statement = StockStatement::with(['lines.product'])
            ->where('company_id', company()->id)->findOrFail($id);
        if ($statement->status !== 'draft' && ! user()->hasAdminLikeAccess()) {
            return Reply::error(__('app.onlyDraftEditable'));
        }
        abort_403(! $this->canModifyStatement($statement, 'edit'));

        $this->statement = $statement;
        $this->cfaStockists = $this->assignedCfaStockistsQuery()->get();
        $this->products = Product::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        if (request()->ajax()) {
            $html = view('stock-statements.ajax.edit', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }
        return view('stock-statements.edit', $this->data);
    }

    public function update(Request $request, $id)
    {
        $statement = StockStatement::where('company_id', company()->id)->findOrFail($id);
        if ($statement->status !== 'draft' && ! user()->hasAdminLikeAccess()) {
            return Reply::error(__('app.onlyDraftEditable'));
        }
        abort_403(! $this->canModifyStatement($statement, 'edit'));

        $request->validate([
            'status' => 'nullable|in:draft,submitted',
            'lines' => 'required|array',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.secondary_qty' => 'nullable|numeric|min:0',
            'lines.*.opening_qty' => 'nullable|numeric|min:0',
            'lines.*.primary_qty' => 'nullable|numeric|min:0',
            'lines.*.closing_qty' => 'nullable|numeric|min:0',
        ]);

        $productIdsSeen = [];
        foreach ($request->lines as $row) {
            $pid = (int) ($row['product_id'] ?? 0);
            if (in_array($pid, $productIdsSeen)) {
                return Reply::error(__('app.stockStatementDuplicateProduct'));
            }
            $productIdsSeen[] = $pid;
        }

        $status = $request->status === 'submitted' ? 'submitted' : 'draft';
        $statement->status = $status;
        if ($status === 'submitted') {
            $statement->submitted_at = now();
        }
        $statement->save();

        $statement->lines()->delete();
        $cfaStockistId = $statement->cfa_stockist_id;
        foreach ($request->lines as $row) {
            $productId = (int) $row['product_id'];
            $secondary = (float) ($row['secondary_qty'] ?? 0);
            $openingInput = isset($row['opening_qty']) && $row['opening_qty'] !== '' ? (float) $row['opening_qty'] : null;
            $primaryInput = isset($row['primary_qty']) && $row['primary_qty'] !== '' ? (float) $row['primary_qty'] : null;
            $closingInput = isset($row['closing_qty']) && $row['closing_qty'] !== '' ? (float) $row['closing_qty'] : null;
            $opening = $openingInput !== null ? $openingInput : $this->getOpeningQty($cfaStockistId, $productId, $statement->period_month, $statement->period_year);
            $primary = $primaryInput !== null ? $primaryInput : $this->getPrimaryQty($cfaStockistId, $productId, $statement->period_month, $statement->period_year);
            $closing = $closingInput !== null ? $closingInput : ($opening + $primary - $secondary);
            StockStatementLine::create([
                'stock_statement_id' => $statement->id,
                'product_id' => $productId,
                'opening_qty' => $opening,
                'primary_qty' => $primary,
                'secondary_qty' => $secondary,
                'closing_qty' => $closing,
            ]);
        }

        $redirect = route('stock-statements.show', $statement->id);
        if (request()->ajax()) {
            return Reply::redirect($redirect, __('messages.recordSaved'));
        }
        return redirect($redirect)->with('message', __('messages.recordSaved'));
    }

    public function destroy($id)
    {
        $statement = StockStatement::where('company_id', company()->id)->findOrFail($id);
        if ($statement->status !== 'draft' && ! user()->hasAdminLikeAccess()) {
            return Reply::error(__('app.onlyDraftDeletable'));
        }
        abort_403(! $this->canModifyStatement($statement, 'delete'));
        $statement->lines()->delete();
        $statement->delete();
        if (request()->ajax()) {
            return Reply::success(__('messages.recordDeleted'));
        }
        return redirect(route('stock-statements.index'))->with('message', __('messages.recordDeleted'));
    }

    /**
     * Consolidation report: roll up statement lines by HQ → Area → Region → Zone.
     * Visible only to upper hierarchy (admin or users with accessible HQs/areas).
     */
    public function consolidation(Request $request)
    {
        if (user()->hasAdminLikeAccess()) {
            // admin: allow
        } else {
            $hqIds = $this->accessibleHeadquarterIds();
            $areaIds = $this->accessibleAreaIds();
            if ($hqIds !== null && $areaIds !== null && count($hqIds) === 0 && count($areaIds) === 0) {
                abort_403(true);
            }
        }

        $periodMonth = $request->filled('period_month') ? (int) $request->period_month : Carbon::now()->month;
        $periodYear = $request->filled('period_year') ? (int) $request->period_year : Carbon::now()->year;

        $query = StockStatementLine::query()
            ->select([
                'stock_statement_lines.product_id',
                'products.name as product_name',
                DB::raw('SUM(stock_statement_lines.opening_qty) as total_opening'),
                DB::raw('SUM(stock_statement_lines.primary_qty) as total_primary'),
                DB::raw('SUM(stock_statement_lines.secondary_qty) as total_secondary'),
                DB::raw('SUM(stock_statement_lines.closing_qty) as total_closing'),
            ])
            ->join('stock_statements', 'stock_statements.id', '=', 'stock_statement_lines.stock_statement_id')
            ->join('cfa_stockists', 'cfa_stockists.id', '=', 'stock_statements.cfa_stockist_id')
            ->whereNull('cfa_stockists.deleted_at')
            ->leftJoin('pharma_headquarters', 'pharma_headquarters.id', '=', 'cfa_stockists.headquarter_id')
            ->leftJoin('pharma_areas', 'pharma_areas.id', '=', 'cfa_stockists.area_id')
            ->leftJoin('pharma_regions', 'pharma_regions.id', '=', 'pharma_areas.region_id')
            ->leftJoin('pharma_zones', 'pharma_zones.id', '=', 'pharma_regions.zone_id')
            ->join('products', 'products.id', '=', 'stock_statement_lines.product_id')
            ->where('stock_statements.company_id', company()->id)
            ->where('stock_statements.period_month', $periodMonth)
            ->where('stock_statements.period_year', $periodYear)
            ->where('stock_statements.status', 'submitted')
            ->groupBy('stock_statement_lines.product_id', 'products.name');

        if (!user()->hasAdminLikeAccess()) {
            $hqIds = $this->accessibleHeadquarterIds();
            $areaIds = $this->accessibleAreaIds();
            $query->where(function ($q) use ($hqIds, $areaIds) {
                if (is_array($hqIds) && count($hqIds) > 0) {
                    $q->whereIn('cfa_stockists.headquarter_id', $hqIds);
                }
                if (is_array($areaIds) && count($areaIds) > 0) {
                    $q->orWhereIn('cfa_stockists.area_id', $areaIds);
                }
            });
        }
        if ($request->filled('headquarter_id')) {
            $query->where('cfa_stockists.headquarter_id', $request->headquarter_id);
        }
        if ($request->filled('area_id')) {
            $query->where('cfa_stockists.area_id', $request->area_id);
        }
        if ($request->filled('region_id')) {
            $query->where('pharma_areas.region_id', $request->region_id);
        }
        if ($request->filled('zone_id')) {
            $query->where('pharma_regions.zone_id', $request->zone_id);
        }

        $this->consolidationLines = $query->orderBy('products.name')->get();
        $this->periodMonth = $periodMonth;
        $this->periodYear = $periodYear;
        $this->headquarters = \App\Models\PharmaHeadquarter::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->areas = \App\Models\PharmaArea::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->regions = \App\Models\PharmaRegion::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->zones = \App\Models\PharmaZone::where('company_id', company()->id)->orderBy('name')->get(['id', 'name']);
        $this->filterHeadquarterId = $request->headquarter_id;
        $this->filterAreaId = $request->area_id;
        $this->filterRegionId = $request->region_id;
        $this->filterZoneId = $request->zone_id;

        return view('stock-statements.consolidation', $this->data);
    }

    public function targetVsAchievement(Request $request)
    {
        $hqIds = null;
        if (user()->hasAdminLikeAccess()) {
            // allow
        } else {
            $hqIds = $this->accessibleHeadquarterIds();
            if (!is_array($hqIds) || count($hqIds) === 0) {
                abort_403(true);
            }
        }

        $periodMonth = $request->filled('period_month') ? (int) $request->period_month : Carbon::now()->month;
        $periodYear = $request->filled('period_year') ? (int) $request->period_year : Carbon::now()->year;
        $start = Carbon::createFromDate($periodYear, $periodMonth, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $targetsQuery = SalesPlanTarget::with(['headquarter', 'product'])
            ->where('company_id', company()->id)
            ->where('period_month', $periodMonth)
            ->where('period_year', $periodYear)
            ->where('plan_level', 'headquarter')
            ->where('plan_type', 'target_plan')
            ->whereNotNull('headquarter_id')
            ->whereNotNull('product_id');

        if (!user()->hasAdminLikeAccess()) {
            $targetsQuery->whereIn('headquarter_id', $hqIds);
        }

        if ($request->filled('headquarter_id')) {
            $targetsQuery->where('headquarter_id', $request->headquarter_id);
        }
        if ($request->filled('product_id')) {
            $targetsQuery->where('product_id', $request->product_id);
        }

        $targets = $targetsQuery->orderBy('headquarter_id')->orderBy('product_id')->get();

        $rows = [];
        foreach ($targets as $target) {
            $targetHeadquarterId = (int) $target->headquarter_id;
            $targetProductId = (int) $target->product_id;

            $primaryQuery = CFAStockistStock::query()
                ->join('invoices', 'invoices.id', '=', 'cfa_stockist_stocks.invoice_id')
                ->join('cfa_stockists', 'cfa_stockists.id', '=', 'cfa_stockist_stocks.cfa_stockist_id')
                ->whereNull('cfa_stockists.deleted_at')
                ->where('cfa_stockist_stocks.company_id', company()->id)
                ->where('cfa_stockist_stocks.product_id', $targetProductId)
                ->where('cfa_stockists.headquarter_id', $targetHeadquarterId)
                ->whereBetween('invoices.issue_date', [$start, $end]);

            $primaryQty = (float) (clone $primaryQuery)->sum('cfa_stockist_stocks.quantity');
            $primaryAmount = (float) (clone $primaryQuery)->selectRaw('SUM(cfa_stockist_stocks.quantity * cfa_stockist_stocks.ptr) as total_amount')->value('total_amount');

            $secondaryQuery = StockStatementLine::query()
                ->join('stock_statements', 'stock_statements.id', '=', 'stock_statement_lines.stock_statement_id')
                ->join('cfa_stockists', 'cfa_stockists.id', '=', 'stock_statements.cfa_stockist_id')
                ->whereNull('cfa_stockists.deleted_at')
                ->where('stock_statements.company_id', company()->id)
                ->where('stock_statements.period_month', $periodMonth)
                ->where('stock_statements.period_year', $periodYear)
                ->where('stock_statements.status', 'submitted')
                ->where('stock_statement_lines.product_id', $targetProductId)
                ->where('cfa_stockists.headquarter_id', $targetHeadquarterId);

            $secondaryQty = (float) (clone $secondaryQuery)->sum('stock_statement_lines.secondary_qty');
            $secondaryAmount = (float) (clone $secondaryQuery)
                ->join('products', 'products.id', '=', 'stock_statement_lines.product_id')
                ->selectRaw('SUM(stock_statement_lines.secondary_qty * COALESCE(products.price, products.mrp, 0)) as total_amount')
                ->value('total_amount');

            $targetQty = (float) ($target->target_qty ?? 0);
            $targetAmount = (float) $target->target_amount;

            $rows[] = [
                'headquarter_name' => $target->headquarter->name ?? '-',
                'product_name' => $target->product->name ?? '-',
                'target_qty' => $targetQty,
                'target_amount' => $targetAmount,
                'primary_qty' => $primaryQty,
                'primary_amount' => $primaryAmount,
                'primary_qty_pct' => $targetQty > 0 ? round(($primaryQty / $targetQty) * 100, 1) : 0,
                'primary_amount_pct' => $targetAmount > 0 ? round(($primaryAmount / $targetAmount) * 100, 1) : 0,
                'secondary_qty' => $secondaryQty,
                'secondary_amount' => $secondaryAmount,
                'secondary_qty_pct' => $targetQty > 0 ? round(($secondaryQty / $targetQty) * 100, 1) : 0,
                'secondary_amount_pct' => $targetAmount > 0 ? round(($secondaryAmount / $targetAmount) * 100, 1) : 0,
                'balance_qty' => max($targetQty - $secondaryQty, 0),
                'balance_amount' => max($targetAmount - $secondaryAmount, 0),
            ];
        }

        $headquartersQuery = \App\Models\PharmaHeadquarter::where('company_id', company()->id)->orderBy('name');
        if (!user()->hasAdminLikeAccess() && is_array($hqIds)) {
            $headquartersQuery->whereIn('id', $hqIds);
        }

        $productsQuery = Product::where('company_id', company()->id)->orderBy('name');
        if (!user()->hasAdminLikeAccess() && is_array($hqIds)) {
            $productsQuery->whereIn('id', function ($query) use ($hqIds, $periodMonth, $periodYear) {
                $query->select('product_id')
                    ->from('sales_plan_targets')
                    ->where('company_id', company()->id)
                    ->where('period_month', $periodMonth)
                    ->where('period_year', $periodYear)
                    ->where('plan_level', 'headquarter')
                    ->where('plan_type', 'target_plan')
                    ->whereIn('headquarter_id', $hqIds);
                });
        }

        $this->reportRows = $rows;
        $this->periodMonth = $periodMonth;
        $this->periodYear = $periodYear;
        $this->headquarters = $headquartersQuery->get(['id', 'name']);
        $this->products = $productsQuery->get(['id', 'name']);
        $this->filterHeadquarterId = $request->headquarter_id;
        $this->filterProductId = $request->product_id;

        return view('stock-statements.target-vs-achievement', $this->data);
    }
}
