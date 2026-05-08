<?php

namespace App\Http\Controllers;

use App\DataTables\ExpensesDataTable;
use App\Helper\Files;
use App\Helper\Reply;
use App\Helper\RoleHierarchy;
use App\Http\Requests\Admin\Employee\ImportProcessRequest;
use App\Http\Requests\Admin\Employee\ImportRequest;
use App\Http\Requests\Expenses\ImportPharmaProcessRequest;
use App\Http\Requests\Expenses\ImportPharmaRequest;
use App\Http\Requests\Expenses\StoreExpense;
use App\Imports\ExpenseImport;
use App\Imports\PharmaExpenseStatementImport;
use App\Exports\ApprovedPharmaExpenseStatementExport;
use App\Exports\PharmaExpenseStatementSampleExport;
use App\Jobs\ImportExpenseJob;
use App\Jobs\ImportPharmaExpenseJob;
use App\Services\PharmaExpenseStatementImporter;
use App\Models\BankAccount;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\ExpensesCategory;
use App\Models\ExpensesCategoryRole;
use App\Models\PharmaHeadquarter;
use App\Models\PharmaExstation;
use App\Models\PharmaOutstation;
use App\Models\PharmaHeadquarterAssign;
use App\Models\PharmaArea;
use App\Models\Project;
use App\Traits\AccessibleHeadquarters;
use App\Models\User;
use App\Scopes\ActiveScope;
use App\Traits\ImportExcel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Imports\HeadingRowImport;
use ReflectionClass;

class ExpenseController extends AccountBaseController
{
    use ImportExcel, AccessibleHeadquarters;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.expenses';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('expenses', $this->user->modules));
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $viewPermission = user()->permission('view_expenses');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        // APPROVAL PAGE: Show expenses submitted TO the current user for approval (like Tour Plan)
        // This page is for managers/admins to approve expenses submitted to them
        
        // For admin: employee filter
        $selectedEmployeeId = $request->get('employee_id');
        $this->selectedEmployeeId = $selectedEmployeeId;
        
        // Get accessible headquarters for filtering
        $accessibleHqIds = $this->accessibleHeadquarterIds();
        
        // Load headquarters with all their stations (like Tour Plan)
        // Filter by accessible headquarters for ABM profiles
        $headquartersQuery = PharmaHeadquarter::with(['exstations', 'outstations', 'area'])
            ->where('company_id', company()->id);
        
        if ($accessibleHqIds !== null && !user()->hasAdminLikeAccess()) {
            if (empty($accessibleHqIds)) {
                $this->headquarters = collect();
            } else {
                $headquartersQuery->whereIn('id', $accessibleHqIds);
                $this->headquarters = $headquartersQuery->get();
            }
        } else {
            // Admin: show all headquarters
            $this->headquarters = $headquartersQuery->get();
        }
        
        // Get current month for filter
        $this->currentMonth = $request->get('month', now()->format('Y-m'));
        
        // First, get all employees who have pending expenses (before month filter)
        // This ensures the dropdown shows all relevant employees
        $allPendingExpensesQuery = Expense::where('expense_type', 'pharma_statement')
            ->where('company_id', company()->id)
            ->where('status', 'pending');
        
        // Get accessible headquarters for filtering expenses
        $accessibleHqIdsForFilter = $this->accessibleHeadquarterIds();
        
        if ($viewPermission == 'all') {
            // Non-admin with 'all': restrict by hierarchy and HQ (Requirement 3.1.1)
            if (!user()->hasAdminLikeAccess()) {
                $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
                if (!empty($viewableIds)) {
                    $allPendingExpensesQuery->whereIn('user_id', $viewableIds);
                }
                if ($accessibleHqIdsForFilter !== null) {
                    if (!empty($accessibleHqIdsForFilter)) {
                        $allPendingExpensesQuery->whereHas('user.employeeDetail.headquarter', function ($hqQuery) use ($accessibleHqIdsForFilter) {
                            $hqQuery->whereIn('id', $accessibleHqIdsForFilter);
                        });
                    } else {
                        $allPendingExpensesQuery->whereRaw('1 = 0');
                    }
                }
            }
        } else {
            // Non-admin (RM/Manager): Show expenses submitted TO this user OR from reporting employees
            $reportingEmployeeIds = \App\Models\EmployeeDetails::where('reporting_to', user()->id)
                ->where('company_id', company()->id)
                ->pluck('user_id')
                ->toArray();
            $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
            $reportingEmployeeIds = array_values(array_intersect($reportingEmployeeIds, $viewableIds));
            
            $allPendingExpensesQuery->where(function($q) use ($reportingEmployeeIds) {
                // Expenses submitted directly to current user
                $q->where('submitted_to', user()->id);
                
                // OR expenses from employees who report to current user (hierarchy-based)
                if (!empty($reportingEmployeeIds)) {
                    $q->orWhereIn('user_id', $reportingEmployeeIds);
                }
            });
            
            // Filter by accessible headquarters for ABM profiles
            if ($accessibleHqIdsForFilter !== null && !empty($accessibleHqIdsForFilter)) {
                $allPendingExpensesQuery->whereHas('user.employeeDetail.headquarter', function($hqQuery) use ($accessibleHqIdsForFilter) {
                    $hqQuery->whereIn('id', $accessibleHqIdsForFilter);
                });
            } elseif ($accessibleHqIdsForFilter !== null && empty($accessibleHqIdsForFilter)) {
                // No accessible headquarters - return empty
                $allPendingExpensesQuery->whereRaw('1 = 0');
            }
        }
        
        // Get unique employee IDs who have pending expenses
        $employeeIds = $allPendingExpensesQuery->distinct()->pluck('user_id')->unique()->toArray();
        
        // Load only employees who have pending expenses submitted for approval
        if (!empty($employeeIds)) {
            $this->employees = User::whereIn('id', $employeeIds)
                ->where('company_id', company()->id)
                ->with(['employeeDetail.designation'])
                ->orderBy('name', 'asc')
                ->get();
        } else {
            $this->employees = collect([]);
        }
        
        // Now load expenses with filters applied (month filter, employee filter)
        $expensesQuery = Expense::with(['user.employeeDetail', 'user.employeeDetails', 'submittedTo', 'approver', 'headquarter'])
            ->where('expense_type', 'pharma_statement')
            ->where('company_id', company()->id)
            ->where('status', 'pending');
        
        // Get accessible headquarters for filtering expenses
        $accessibleHqIdsForFilter = $this->accessibleHeadquarterIds();
        
        if ($viewPermission == 'all') {
            // Non-admin with 'all': restrict by hierarchy and HQ (Requirement 3.1.1)
            if (!user()->hasAdminLikeAccess()) {
                $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
                if (!empty($viewableIds)) {
                    $expensesQuery = $expensesQuery->whereIn('user_id', $viewableIds);
                }
                if ($accessibleHqIdsForFilter !== null) {
                    if (!empty($accessibleHqIdsForFilter)) {
                        $expensesQuery->whereHas('user.employeeDetail.headquarter', function ($hqQuery) use ($accessibleHqIdsForFilter) {
                            $hqQuery->whereIn('id', $accessibleHqIdsForFilter);
                        });
                    } else {
                        $expensesQuery->whereRaw('1 = 0');
                    }
                }
            }
            if ($selectedEmployeeId && $selectedEmployeeId != 'all') {
                $expensesQuery = $expensesQuery->where('user_id', $selectedEmployeeId);
            }
        } else {
            // Non-admin (RM/Manager): Show expenses submitted TO this user OR from reporting employees
            $reportingEmployeeIds = \App\Models\EmployeeDetails::where('reporting_to', user()->id)
                ->where('company_id', company()->id)
                ->pluck('user_id')
                ->toArray();
            $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
            $reportingEmployeeIds = array_values(array_intersect($reportingEmployeeIds, $viewableIds));
            
            $expensesQuery->where(function($q) use ($reportingEmployeeIds) {
                // Expenses submitted directly to current user
                $q->where('submitted_to', user()->id);
                
                // OR expenses from employees who report to current user (hierarchy-based)
                if (!empty($reportingEmployeeIds)) {
                    $q->orWhereIn('user_id', $reportingEmployeeIds);
                }
            });
            
            // Filter by accessible headquarters for ABM profiles
            if ($accessibleHqIdsForFilter !== null && !empty($accessibleHqIdsForFilter)) {
                $expensesQuery->whereHas('user.employeeDetail.headquarter', function($hqQuery) use ($accessibleHqIdsForFilter) {
                    $hqQuery->whereIn('id', $accessibleHqIdsForFilter);
                });
            } elseif ($accessibleHqIdsForFilter !== null && empty($accessibleHqIdsForFilter)) {
                // No accessible headquarters - return empty
                $expensesQuery->whereRaw('1 = 0');
            }
        }
        
        // Filter by month if provided
        if ($this->currentMonth) {
            $monthStart = \Carbon\Carbon::parse($this->currentMonth . '-01')->startOfMonth();
            $monthEnd = \Carbon\Carbon::parse($this->currentMonth . '-01')->endOfMonth();
            $expensesQuery = $expensesQuery->whereBetween('purchase_date', [$monthStart, $monthEnd]);
        }
        
        $this->expenses = $expensesQuery->orderBy('purchase_date', 'asc')->get();
        
        // Group expenses by employee and month for display
        $this->groupedExpenses = $this->expenses->groupBy(function($expense) {
            return $expense->user_id . '_' . $expense->expense_month;
        });
        
        // Load designations for "Worked With" dropdown
        $this->workedWithDesignations = [
            'Independent',
            'Medical Representative',
            'ABM',
            'RBM',
            'Sales Manager',
            'Zonal Manager',
            'PMT',
            'HO'
        ];
        
        // Check if user has any expenses submitted to them (to show/hide menu item)
        // Also check if user has reporting employees (for proactive visibility)
        $hasExpensesSubmittedToMe = Expense::where('submitted_to', user()->id)
            ->where('company_id', company()->id)
            ->where('status', 'pending')
            ->where('expense_type', 'pharma_statement')
            ->exists();
        
        $hasReportingEmployees = \App\Models\EmployeeDetails::where('reporting_to', user()->id)
            ->where('company_id', company()->id)
            ->exists();
        
        $this->hasExpensesToApprove = $hasExpensesSubmittedToMe || $hasReportingEmployees;

        return view('expenses.approve', $this->data);
    }

    public function changeStatus(Request $request)
    {
        abort_403(user()->permission('approve_expenses') != 'all');

        $expenseId = $request->expenseId;
        $status = $request->status;
        $expense = Expense::findOrFail($expenseId);
        $expense->status = $status;
        $expense->save();
        return Reply::success(__('messages.updateSuccess'));
    }

    public function show($id)
    {
        $this->expense = Expense::with([
            'user.employeeDetail',
            'user.employeeDetails',
            'project',
            'category',
            'transactions' => function ($q) {
                $q->orderByDesc('id')->limit(1);
            },
            'transactions.bankAccount',
        ])->findOrFail($id)->withCustomFields();

        $this->viewPermission = user()->permission('view_expenses');
        $viewProjectPermission = user()->permission('view_project_expenses');
        $this->editExpensePermission = user()->permission('edit_expenses');
        $this->deleteExpensePermission = user()->permission('delete_expenses');

        abort_403(!($this->viewPermission == 'all'
        || ($this->viewPermission == 'added' && $this->expense->added_by == user()->id)
        || ($viewProjectPermission == 'owned' || $this->expense->user_id == user()->id)));

        $getCustomFieldGroupsWithFields = $this->expense->getCustomFieldGroupsWithFields();

        if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }

        $this->pageTitle = $this->expense->item_name;
        $this->view = 'expenses.ajax.show';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('expenses.show', $this->data);

    }

    public function create()
    {
        // Check permission like Tour Plan
        $this->addPermission = user()->permission('add_expenses');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $this->currencies = Currency::all();
        $this->categories = ExpenseCategoryController::getCategoryByCurrentRole();
        $this->linkExpensePermission = user()->permission('link_expense_bank_account');
        $this->viewBankAccountPermission = user()->permission('view_bankaccount');

        $bankAccounts = BankAccount::where('status', 1)->where('currency_id', company()->currency_id);

        if($this->viewBankAccountPermission == 'added'){
            $bankAccounts = $bankAccounts->where('added_by', user()->id);
        }

        $bankAccounts = $bankAccounts->get();
        $this->bankDetails = $bankAccounts;
        $this->companyCurrency = Currency::where('id', company()->currency_id)->first();

        // Get only current login employee projects
        if ($this->addPermission == 'added') {
            $this->projects = Project::where('added_by', user()->id)->orWhereHas('projectMembers', function ($query) {
                $query->where('user_id', user()->id);
            })->get();

        } else {
            $this->projects = Project::all();
        }

        $this->pageTitle = __('modules.expenses.addExpense');
        $this->projectId = request('project_id') ? request('project_id') : null;

        $this->resolveEmployeesForPharmaCreate($this->projectId);
        $this->loadPharmaHeadquarterAndManagers();

        $expense = new Expense();

        $getCustomFieldGroupsWithFields = $expense->getCustomFieldGroupsWithFields();

        if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }

        // Use pharma expense form by default for pharma CRM
        $this->view = 'expenses.create-pharma';
        
        // Check for existing expenses for current month/year (for employees)
        // Allow checking for specific month/year via query params (for when user changes month/year)
        $requestedMonth = request('month');
        $requestedYear = request('year');
        
        if ($requestedMonth && $requestedYear) {
            $currentMonth = sprintf('%04d-%02d', $requestedYear, $requestedMonth);
        } else {
            $currentMonth = now()->format('Y-m');
        }
        
        $existingExpenses = collect([]);
        $isLocked = false;
        $lockStatus = null;
        
        if ($this->addPermission == 'added') {
            // For employees: Check if they already have expenses for selected month
            $existingExpenses = Expense::where('expense_type', 'pharma_statement')
                ->where('company_id', company()->id)
                ->where('user_id', user()->id)
                ->where('expense_month', $currentMonth)
                ->orderBy('purchase_date', 'asc')
                ->get();
            
            // If ANY expense is submitted (pending) or approved, lock the entire month
            if ($existingExpenses->isNotEmpty()) {
                $hasPending = $existingExpenses->where('status', 'pending')->isNotEmpty();
                $hasApproved = $existingExpenses->where('status', 'approved')->isNotEmpty();
                
                if ($hasPending || $hasApproved) {
                    $isLocked = true;
                    $lockStatus = $hasApproved ? 'approved' : 'pending';
                }
            }
        }
        
        // Ensure current user headquarter data is available in view (like Tour Plan)
        $this->data['currentUserHeadquarter'] = $this->currentUserHeadquarter;
        $this->data['currentUserHeadquarterName'] = $this->currentUserHeadquarterName;
        $this->data['userHeadquarter'] = $this->userHeadquarter;
        $this->data['showHqDropdownForPharmaRoles'] = $this->showHqDropdownForPharmaRoles ?? false;
        $this->data['workedWithDesignations'] = $this->workedWithDesignations;
        $this->data['managers'] = $this->managers;
        $this->data['reportingManagerId'] = $this->reportingManagerId;
        $this->data['existingExpenses'] = $existingExpenses;
        $this->data['currentMonth'] = $currentMonth;
        $this->data['isLocked'] = $isLocked;
        $this->data['lockStatus'] = $lockStatus;
        $hasRejected = $existingExpenses->where('status', 'rejected')->isNotEmpty();
        $this->data['hasRejected'] = $hasRejected;
        $this->data['rejectedReasons'] = $hasRejected
            ? $existingExpenses->where('status', 'rejected')->pluck('description')->filter()->unique()->values()->all()
            : [];
        
        // Pre-process existing expenses data for JavaScript (to avoid Blade parsing issues)
        // Include all expenses for display, but lock the entire form if any are pending/approved
        $existingExpensesData = [];
        if ($existingExpenses->isNotEmpty()) {
            foreach ($existingExpenses as $exp) {
                $day = \Carbon\Carbon::parse($exp->purchase_date)->day;
                $existingExpensesData[$day] = [
                    'id' => $exp->id,
                    'town_worked' => $exp->town_worked ?? '',
                    'worked_with' => $exp->worked_with ? json_decode($exp->worked_with, true) : [],
                    'headquarter_from' => $exp->headquarter_from ?? '',
                    'headquarter_to' => $exp->headquarter_to ?? '',
                    'mode_of_transport' => $exp->mode_of_transport ?? '',
                    'no_of_doctors_met' => $exp->no_of_doctors_met ?? 0,
                    'no_of_retailers_met' => $exp->no_of_retailers_met ?? 0,
                    'km' => $exp->km ?? 0,
                    'fare_rs' => $exp->fare_rs ?? 0,
                    'daily_allowance_hq_rs' => $exp->daily_allowance_hq_rs ?? 0,
                    'daily_allowance_ex_rs' => $exp->daily_allowance_ex_rs ?? 0,
                    'daily_allowance_os_rs' => $exp->daily_allowance_os_rs ?? 0,
                    'fixed_expenses' => $exp->fixed_expenses ?? 0,
                    'other_expenses' => $exp->other_expenses ?? 0,
                    'remarks' => $exp->remarks ?? '',
                    'status' => $exp->status ?? 'pending'
                ];
            }
        }
        $this->data['existingExpensesData'] = $existingExpensesData;

        // Always return dedicated page view (no AJAX modal)
        return view('expenses.create-pharma', $this->data);

    }

    public function store(StoreExpense $request)
    {
        $currencySetting = Currency::findOrFail($request->currency_id);

        $userRole = session('user_roles');
        $expense = new Expense();
        $expense->item_name = $request->item_name;
        $expense->purchase_date = companyToYmd($request->purchase_date);
        $expense->purchase_from = $request->purchase_from;
        $expense->price = round($request->price, $currencySetting->no_of_decimal);
        $expense->currency_id = $request->currency_id;
        $expense->category_id = $request->category_id;
        $expense->user_id = $request->user_id;
        $expense->default_currency_id = company()->currency_id;
        $expense->exchange_rate = $request->exchange_rate;
        $expense->description = trim_editor($request->description);

        if ($userRole[0] == 'admin') {
            $expense->status = 'approved';
            $expense->approver_id = user()->id;
        }

        if ($request->has('status')) {
            $expense->status = $request->status;
            $expense->approver_id = user()->id;
        }

        if ($request->has('project_id') && $request->project_id != '0') {
            $expense->project_id = $request->project_id;
        }

        if ($request->hasFile('bill')) {
            $filename = Files::uploadLocalOrS3($request->bill, Expense::FILE_PATH);
            $expense->bill = $filename;
        }

        $expense->bank_account_id = $request->bank_account_id;

        $expense->save();

        // To add custom fields data
        if ($request->custom_fields_data) {
            $expense->updateCustomFieldData($request->custom_fields_data);
        }

        $redirectUrl = urldecode($request->redirect_url ?? '');

        if ($redirectUrl == '') {
            $redirectUrl = route('expenses.index');
        }

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => $redirectUrl]);
    }

    public function storePharma(Request $request)
    {
        $this->addPermission = user()->permission('add_expenses');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $request->validate([
            'pharma_user_id' => 'required|exists:users,id',
            'pharma_headquarter_id' => 'required|exists:pharma_headquarters,id',
            'expense_month' => 'required|integer|between:1,12',
            'expense_year' => 'required|integer|min:2020|max:2100',
            'posted_on' => 'required|date',
            'no_of_vouchers' => 'required|integer|min:0',
            'submitted_to' => 'required|exists:users,id',
            'expenses' => 'required|array|min:1',
            'expenses.*.date' => 'required|date',
            'vouchers.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max per file
        ]);

        // Validate headquarter is in expense owner's accessible list
        $expenseOwner = User::find($request->pharma_user_id);
        $employeeToValidate = $expenseOwner ?: user();
        $allowedHqIds = $this->accessibleHeadquarterIds($employeeToValidate);
        if ($allowedHqIds !== null && !in_array((int) $request->pharma_headquarter_id, $allowedHqIds)) {
            return Reply::error(__('You can only create expenses for allocated headquarter(s).'));
        }
        
        // Combine month and year into Y-m format
        $expenseMonth = sprintf('%04d-%02d', $request->expense_year, $request->expense_month);

        $userRole = session('user_roles');
        $currencyId = company()->currency_id;
        $currencySetting = Currency::findOrFail($currencyId);
        
        // Handle voucher uploads
        $voucherFiles = [];
        if ($request->hasFile('vouchers')) {
            foreach ($request->file('vouchers') as $voucher) {
                $filename = Files::uploadLocalOrS3($voucher, Expense::FILE_PATH);
                $voucherFiles[] = $filename;
            }
        }

        // Process each expense row (only save rows with data)
        $firstExpenseRecord = true;
        foreach ($request->expenses as $expenseData) {
            // Skip empty rows
            $hasData = !empty($expenseData['town_worked']) ||
                      !empty($expenseData['worked_with']) ||
                      (!empty($expenseData['no_of_doctors_met']) && $expenseData['no_of_doctors_met'] > 0) ||
                      (!empty($expenseData['no_of_retailers_met']) && $expenseData['no_of_retailers_met'] > 0) ||
                      !empty($expenseData['headquarter_from']) ||
                      !empty($expenseData['headquarter_to']) ||
                      !empty($expenseData['mode_of_transport']) ||
                      (!empty($expenseData['km']) && $expenseData['km'] > 0) ||
                      (!empty($expenseData['fare_rs']) && $expenseData['fare_rs'] > 0) ||
                      (!empty($expenseData['daily_allowance_hq_rs']) && $expenseData['daily_allowance_hq_rs'] > 0) ||
                      (!empty($expenseData['daily_allowance_ex_rs']) && $expenseData['daily_allowance_ex_rs'] > 0) ||
                      (!empty($expenseData['daily_allowance_os_rs']) && $expenseData['daily_allowance_os_rs'] > 0) ||
                      (!empty($expenseData['fixed_expenses']) && $expenseData['fixed_expenses'] > 0) ||
                      (!empty($expenseData['other_expenses']) && $expenseData['other_expenses'] > 0);
            
            if (!$hasData) {
                continue; // Skip empty rows
            }

            // Resubmit: update existing rejected expense if expense_id provided and expense is rejected and owned by user
            $expenseId = isset($expenseData['expense_id']) ? (int) $expenseData['expense_id'] : 0;
            $expense = null;
            if ($expenseId > 0) {
                $existing = Expense::where('id', $expenseId)
                    ->where('expense_type', 'pharma_statement')
                    ->where('company_id', company()->id)
                    ->where('user_id', $request->pharma_user_id)
                    ->where('status', 'rejected')
                    ->first();
                if ($existing) {
                    $expense = $existing;
                }
            }

            if (!$expense) {
                $expense = new Expense();
                $expense->expense_type = 'pharma_statement';
                $expense->user_id = $request->pharma_user_id;
                $expense->headquarter_id = $request->pharma_headquarter_id;
                $expense->expense_month = $expenseMonth;
                $expense->posted_on = companyToYmd($request->posted_on);
                $expense->no_of_vouchers = $request->no_of_vouchers;
            } else {
                // Resubmitting rejected expense: clear approval and set back to pending
                $expense->approver_id = null;
                $expense->approved_at = null;
                $expense->posted_on = companyToYmd($request->posted_on);
                $expense->no_of_vouchers = $request->no_of_vouchers;
            }
            
            // Date and day
            $expense->purchase_date = companyToYmd($expenseData['date']);
            $expense->day = $expenseData['day'] ?? date('l', strtotime($expense->purchase_date));
            
            // Work details
            $expense->town_worked = $expenseData['town_worked'] ?? null;
            $expense->worked_with = $expenseData['worked_with'] ?? null;
            $expense->no_of_doctors_met = $expenseData['no_of_doctors_met'] ?? 0;
            $expense->no_of_retailers_met = $expenseData['no_of_retailers_met'] ?? 0;
            $expense->headquarter_from = $expenseData['headquarter_from'] ?? null;
            $expense->headquarter_to = $expenseData['headquarter_to'] ?? null;
            
            // Transport details
            $expense->mode_of_transport = $expenseData['mode_of_transport'] ?? null;
            $expense->km = $expenseData['km'] ?? 0;
            $expense->fare_rs = $expenseData['fare_rs'] ?? 0;
            
            // Allowances
            $expense->daily_allowance_hq_rs = $expenseData['daily_allowance_hq_rs'] ?? 0;
            $expense->daily_allowance_ex_rs = $expenseData['daily_allowance_ex_rs'] ?? 0;
            $expense->daily_allowance_os_rs = $expenseData['daily_allowance_os_rs'] ?? 0;
            
            // Other expenses
            $expense->fixed_expenses = $expenseData['fixed_expenses'] ?? 0;
            $expense->other_expenses = $expenseData['other_expenses'] ?? 0;
            
            // Remarks
            $expense->remarks = $expenseData['remarks'] ?? null;
            
            // Calculate total price (include fixed expenses)
            $totalPrice = ($expense->fare_rs ?? 0) + 
                         ($expense->daily_allowance_hq_rs ?? 0) + 
                         ($expense->daily_allowance_ex_rs ?? 0) + 
                         ($expense->daily_allowance_os_rs ?? 0) + 
                         ($expense->fixed_expenses ?? 0) + 
                         ($expense->other_expenses ?? 0);
            
            $expense->item_name = 'Pharma Expense - ' . $expense->purchase_date;
            $expense->price = round($totalPrice, $currencySetting->no_of_decimal);
            $expense->currency_id = $currencyId;
            $expense->default_currency_id = $currencyId;
            $expense->exchange_rate = $currencySetting->exchange_rate ?? 1;
            
            // Submission and approval workflow (like Tour Plan)
            $isAdmin = ($userRole[0] == 'admin');
            if ($isAdmin) {
                // Admin can auto-approve
                $expense->status = 'approved';
                $expense->approver_id = user()->id;
                $expense->approved_at = now();
                $expense->submitted_to = null;
            } else {
                // Employee submits to manager
                $expense->status = 'pending';
                $expense->submitted_to = $request->submitted_to;
                $expense->approver_id = null;
                $expense->approved_at = null;
            }
            
            $expense->added_by = user()->id;
            
            // Attach vouchers to the first expense record
            if ($firstExpenseRecord && !empty($voucherFiles)) {
                $expense->bill = $voucherFiles[0]; // First voucher as bill
                if (count($voucherFiles) > 1) {
                    $expense->description = json_encode(['additional_vouchers' => array_slice($voucherFiles, 1)]);
                }
                $firstExpenseRecord = false;
            }
            
            $expense->save();
        }

        return Reply::successWithData(__('messages.expenseSuccess'), [
            'redirectUrl' => route('expenses.status')
        ]);
    }
    
    public function approve($id)
    {
        $expense = Expense::findOrFail($id);
        
        // Check if user can approve: either admin, the person expense is submitted to, or user is reporting manager
        $isAdmin = user()->permission('approve_expenses') == 'all';
        $isSubmittedToMe = $expense->submitted_to == user()->id;
        
        // Check if expense creator reports to current user (hierarchy-based approval)
        $isReportingEmployee = false;
        if (!$isAdmin && !$isSubmittedToMe) {
            $expenseCreator = $expense->user;
            if ($expenseCreator && $expenseCreator->employeeDetails) {
                $isReportingEmployee = $expenseCreator->employeeDetails->reporting_to == user()->id;
            }
        }
        
        $canApprove = $isAdmin || $isSubmittedToMe || $isReportingEmployee;
        abort_403(!$canApprove);
        
        // Only approve pending expenses
        if ($expense->status != 'pending') {
            return Reply::error(__('Expense is not pending approval'));
        }
        
        $expense->status = 'approved';
        $expense->approver_id = user()->id;
        $expense->approved_at = now();
        $expense->save();

        return Reply::success(__('Expense approved successfully'));
    }
    
    public function approveAll(Request $request)
    {
        $this->approvePermission = user()->permission('approve_expenses');
        
        // Check if user has permission to approve expenses
        // Admin can approve all expenses, managers can only approve expenses submitted to them
        $isAdmin = $this->approvePermission == 'all';
        
        if (!$isAdmin) {
            // For non-admin: Check if expenses are submitted to this user OR from reporting employees
            $expenseIds = json_decode($request->expense_ids ?? '[]', true);
            if (!empty($expenseIds)) {
                // Get reporting employee IDs
                $reportingEmployeeIds = \App\Models\EmployeeDetails::where('reporting_to', user()->id)
                    ->where('company_id', company()->id)
                    ->pluck('user_id')
                    ->toArray();
                $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
                $reportingEmployeeIds = array_values(array_intersect($reportingEmployeeIds, $viewableIds));
                
                $hasPermission = Expense::whereIn('id', $expenseIds)
                    ->where(function($q) use ($reportingEmployeeIds) {
                        // Expenses submitted directly to current user
                        $q->where('submitted_to', user()->id);
                        
                        // OR expenses from employees who report to current user
                        if (!empty($reportingEmployeeIds)) {
                            $q->orWhereIn('user_id', $reportingEmployeeIds);
                        }
                    })
                    ->exists();
                if (!$hasPermission) {
                    abort_403();
                }
            } else {
                abort_403();
            }
        }
        
        $request->validate([
            'expense_ids' => 'required',
            'expenses' => 'required'
        ]);
        
        $expenseIds = json_decode($request->expense_ids, true);
        if (!is_array($expenseIds)) {
            $expenseIds = [$expenseIds];
        }
        
        $expensesData = json_decode($request->expenses, true);
        if (!is_array($expensesData)) {
            $expensesData = [];
        }
        
        // Update each expense with form data and approve
        $approvedCount = 0;
        foreach ($expenseIds as $expenseId) {
            $expense = Expense::findOrFail($expenseId);
            
            // Check permission
            // Admin can approve all expenses, managers can only approve expenses submitted to them
            // Check if expense creator reports to current user (hierarchy-based approval)
            $isSubmittedToMe = $expense->submitted_to == user()->id;
            $isReportingEmployee = false;
            if (!$isAdmin && !$isSubmittedToMe) {
                $expenseCreator = $expense->user;
                if ($expenseCreator && $expenseCreator->employeeDetails) {
                    $isReportingEmployee = $expenseCreator->employeeDetails->reporting_to == user()->id;
                }
            }
            $canApprove = $isAdmin || $isSubmittedToMe || $isReportingEmployee;
            if (!$canApprove) {
                continue;
            }
            
            // Only approve pending expenses
            if ($expense->status != 'pending') {
                continue;
            }
            
            // Update expense data from form
            if (isset($expensesData[$expenseId])) {
                $expenseData = $expensesData[$expenseId];
                
                $expense->town_worked = $expenseData['town_worked'] ?? $expense->town_worked;
                $expense->worked_with = isset($expenseData['worked_with']) ? json_encode($expenseData['worked_with']) : $expense->worked_with;
                $expense->no_of_doctors_met = $expenseData['no_of_doctors_met'] ?? $expense->no_of_doctors_met;
                $expense->no_of_retailers_met = $expenseData['no_of_retailers_met'] ?? $expense->no_of_retailers_met;
                $expense->headquarter_from = $expenseData['headquarter_from'] ?? $expense->headquarter_from;
                $expense->headquarter_to = $expenseData['headquarter_to'] ?? $expense->headquarter_to;
                $expense->mode_of_transport = $expenseData['mode_of_transport'] ?? $expense->mode_of_transport;
                $expense->km = $expenseData['km'] ?? $expense->km;
                $expense->fare_rs = $expenseData['fare_rs'] ?? $expense->fare_rs;
                $expense->daily_allowance_hq_rs = $expenseData['daily_allowance_hq_rs'] ?? $expense->daily_allowance_hq_rs;
                $expense->daily_allowance_ex_rs = $expenseData['daily_allowance_ex_rs'] ?? $expense->daily_allowance_ex_rs;
                $expense->daily_allowance_os_rs = $expenseData['daily_allowance_os_rs'] ?? $expense->daily_allowance_os_rs;
                $expense->fixed_expenses = $expenseData['fixed_expenses'] ?? $expense->fixed_expenses;
                $expense->other_expenses = $expenseData['other_expenses'] ?? $expense->other_expenses;
                $expense->remarks = $expenseData['remarks'] ?? $expense->remarks;
                
                // Recalculate total price (include fixed expenses)
                $totalPrice = ($expense->fare_rs ?? 0) + 
                             ($expense->daily_allowance_hq_rs ?? 0) + 
                             ($expense->daily_allowance_ex_rs ?? 0) + 
                             ($expense->daily_allowance_os_rs ?? 0) + 
                             ($expense->fixed_expenses ?? 0) + 
                             ($expense->other_expenses ?? 0);
                
                $currencySetting = Currency::findOrFail($expense->currency_id);
                $expense->price = round($totalPrice, $currencySetting->no_of_decimal);
            }
            
            // Set approval fields and persist all expense data (form updates + approval)
            $expense->status = 'approved';
            $expense->approver_id = user()->id;
            $expense->approved_at = now();
            $expense->save();
            
            // Log for debugging
            \Log::info('Expense approved', [
                'expense_id' => $expense->id,
                'user_id' => $expense->user_id,
                'status' => $expense->status,
                'approver_id' => $expense->approver_id,
                'approved_at' => $expense->approved_at,
                'submitted_to' => $expense->submitted_to,
                'approved_by' => user()->id,
                'is_admin' => $isAdmin
            ]);
            
            $approvedCount++;
        }
        
        return Reply::success(__('All expenses approved successfully') . " ({$approvedCount} expense(s))");
    }

    /**
     * Reject a single pharma expense (reporting manager).
     */
    public function reject(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        $isAdmin = user()->permission('approve_expenses') == 'all';
        $isSubmittedToMe = $expense->submitted_to == user()->id;
        $isReportingEmployee = false;
        if (!$isAdmin && !$isSubmittedToMe && $expense->user && $expense->user->employeeDetails) {
            $isReportingEmployee = $expense->user->employeeDetails->reporting_to == user()->id;
        }
        $canReject = $isAdmin || $isSubmittedToMe || $isReportingEmployee;
        abort_403(!$canReject);

        if ($expense->status != 'pending') {
            return Reply::error(__('app.expenseNotPending'));
        }

        $reason = $request->input('reject_reason', '');
        $expense->status = 'rejected';
        $expense->approver_id = user()->id;
        $expense->approved_at = null;
        if ($reason !== '') {
            $prefix = trim($expense->description ?? '') ? $expense->description . "\n\n" : '';
            $expense->description = $prefix . __('app.rejectedReason') . ': ' . $reason;
        }
        $expense->save();

        return Reply::success(__('app.expenseRejected'));
    }

    /**
     * Reject multiple pharma expenses (same permission as approveAll).
     */
    public function rejectAll(Request $request)
    {
        $this->approvePermission = user()->permission('approve_expenses');
        $isAdmin = $this->approvePermission == 'all';

        if (!$isAdmin) {
            $expenseIds = json_decode($request->expense_ids ?? '[]', true);
            if (!empty($expenseIds)) {
                $reportingEmployeeIds = \App\Models\EmployeeDetails::where('reporting_to', user()->id)
                    ->where('company_id', company()->id)
                    ->pluck('user_id')
                    ->toArray();
                $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
                $reportingEmployeeIds = array_values(array_intersect($reportingEmployeeIds, $viewableIds));

                $hasPermission = Expense::whereIn('id', $expenseIds)
                    ->where(function ($q) use ($reportingEmployeeIds) {
                        $q->where('submitted_to', user()->id);
                        if (!empty($reportingEmployeeIds)) {
                            $q->orWhereIn('user_id', $reportingEmployeeIds);
                        }
                    })
                    ->exists();
                if (!$hasPermission) {
                    abort_403();
                }
            } else {
                abort_403();
            }
        }

        $request->validate(['expense_ids' => 'required']);

        $expenseIds = json_decode($request->expense_ids, true);
        if (!is_array($expenseIds)) {
            $expenseIds = [$expenseIds];
        }

        $reason = $request->input('reject_reason', '');
        $rejectedCount = 0;

        foreach ($expenseIds as $expenseId) {
            $expense = Expense::find($expenseId);
            if (!$expense) {
                continue;
            }
            $isSubmittedToMe = $expense->submitted_to == user()->id;
            $isReportingEmployee = false;
            if (!$isAdmin && !$isSubmittedToMe && $expense->user && $expense->user->employeeDetails) {
                $isReportingEmployee = $expense->user->employeeDetails->reporting_to == user()->id;
            }
            if (!$isAdmin && !$isSubmittedToMe && !$isReportingEmployee) {
                continue;
            }
            if ($expense->status != 'pending') {
                continue;
            }

            $expense->status = 'rejected';
            $expense->approver_id = user()->id;
            $expense->approved_at = null;
            if ($reason !== '') {
                $prefix = trim($expense->description ?? '') ? $expense->description . "\n\n" : '';
                $expense->description = $prefix . __('app.rejectedReason') . ': ' . $reason;
            }
            $expense->save();
            $rejectedCount++;
        }

        return Reply::success(__('app.expensesRejected') . " ({$rejectedCount})");
    }

    /**
     * Same visibility rules as the Expense Statement Status page; returns query before ordering/executing.
     */
    protected function buildPharmaStatementStatusQuery(Request $request)
    {
        $this->viewPermission = user()->permission('view_expenses');
        abort_403(!in_array($this->viewPermission, ['all', 'added', 'owned', 'both']));

        $selectedMonth = $request->get('month', now()->format('Y-m'));
        $this->selectedMonth = $selectedMonth;
        $monthStart = \Carbon\Carbon::parse($selectedMonth . '-01')->startOfMonth();
        $monthEnd = \Carbon\Carbon::parse($selectedMonth . '-01')->endOfMonth();

        $selectedEmployeeId = $request->get('employee_id', 'all');
        $this->selectedEmployeeId = $selectedEmployeeId;

        if ($this->viewPermission == 'all') {
            $this->employees = User::with(['employeeDetail.designation'])
                ->whereHas('employeeDetail')
                ->where('company_id', company()->id)
                ->orderBy('name')
                ->get();
        } else {
            $this->employees = collect();
        }

        $expensesQuery = Expense::with([
            'user.employeeDetail.designation',
            'submittedTo.employeeDetail.designation',
            'approver',
        ])
            ->where('expense_type', 'pharma_statement')
            ->where('company_id', company()->id)
            ->whereBetween('purchase_date', [$monthStart, $monthEnd]);

        $accessibleHqIdsForFilter = $this->accessibleHeadquarterIds();

        if ($this->viewPermission == 'all') {
            if (!user()->hasAdminLikeAccess()) {
                $viewableIds = RoleHierarchy::userIdsViewableBy(user(), company()->id);
                if (!empty($viewableIds)) {
                    $expensesQuery = $expensesQuery->whereIn('user_id', $viewableIds);
                }
                if ($accessibleHqIdsForFilter !== null) {
                    if (!empty($accessibleHqIdsForFilter)) {
                        $expensesQuery->whereHas('user.employeeDetail.headquarter', function ($hqQuery) use ($accessibleHqIdsForFilter) {
                            $hqQuery->whereIn('id', $accessibleHqIdsForFilter);
                        });
                    } else {
                        $expensesQuery->whereRaw('1 = 0');
                    }
                }
            }
            if ($selectedEmployeeId && $selectedEmployeeId != 'all') {
                $expensesQuery = $expensesQuery->where('user_id', $selectedEmployeeId);
            }
        } else {
            $expensesQuery = $expensesQuery->where('user_id', user()->id);
            if ($accessibleHqIdsForFilter !== null && !empty($accessibleHqIdsForFilter)) {
                $expensesQuery->whereHas('user.employeeDetail.headquarter', function ($hqQuery) use ($accessibleHqIdsForFilter) {
                    $hqQuery->whereIn('id', $accessibleHqIdsForFilter);
                });
            } elseif ($accessibleHqIdsForFilter !== null && empty($accessibleHqIdsForFilter)) {
                $expensesQuery->whereRaw('1 = 0');
            }
        }

        return $expensesQuery;
    }

    public function status(Request $request)
    {
        $expensesQuery = $this->buildPharmaStatementStatusQuery($request);

        $this->expenses = $expensesQuery->orderBy('purchase_date', 'asc')->get();
        
        // Group expenses by month and user for display
        $this->groupedExpenses = $this->expenses->groupBy(function($expense) {
            return $expense->user_id . '_' . $expense->expense_month;
        });

        return view('expenses.status', $this->data);
    }

    public function exportApprovedExpenseStatement(Request $request)
    {
        $expenses = $this->buildPharmaStatementStatusQuery($request)
            ->where('status', 'approved')
            ->orderBy('purchase_date', 'asc')
            ->get();

        $month = $request->get('month', now()->format('Y-m'));
        $filename = 'approved-expense-statement-' . $month . '.xlsx';

        return Excel::download(
            new ApprovedPharmaExpenseStatementExport($expenses),
            $filename
        );
    }

    public function getHeadquarterLocations($headquarterId)
    {
        $locations = [];
        
        if ($headquarterId) {
            // Check if user has access to this headquarter
            $accessibleHeadquarterIds = $this->accessibleHeadquarterIds();
            
            // If employee and headquarter is not in accessible list, return empty
            if ($accessibleHeadquarterIds !== null && !in_array($headquarterId, $accessibleHeadquarterIds)) {
                return Reply::dataOnly(['locations' => []]);
            }
            
            // Follow Tour Plan mapping: Load only the selected headquarter's stations
            $headquarter = PharmaHeadquarter::with(['exstations', 'outstations'])->find($headquarterId);
            
            if ($headquarter) {
                // Add headquarter itself (store name, like Tour Plan does)
                $locations[] = [
                    'value' => $headquarter->name,
                    'name' => $headquarter->name . ' (Headquarter)',
                    'type' => 'headquarter'
                ];
                
                // Add exstations assigned to this headquarter
                foreach ($headquarter->exstations as $exstation) {
                    $locations[] = [
                        'value' => $exstation->name,
                        'name' => $exstation->name . ' (Ex Station)',
                        'type' => 'exstation'
                    ];
                }
                
                // Add outstations assigned to this headquarter
                foreach ($headquarter->outstations as $outstation) {
                    $locations[] = [
                        'value' => $outstation->name,
                        'name' => $outstation->name . ' (Out Station)',
                        'type' => 'outstation'
                    ];
                }
            }
        }
        
        return Reply::dataOnly(['locations' => $locations]);
    }

    public function edit($id)
    {
        $this->expense = Expense::findOrFail($id)->withCustomFields();
        $this->editPermission = user()->permission('edit_expenses');

        abort_403(!($this->editPermission == 'all' || ($this->editPermission == 'added' && $this->expense->added_by == user()->id)));
        
        // Lock pharma expense statements: prevent editing if submitted or approved
        if ($this->expense->expense_type == 'pharma_statement') {
            $isOwner = $this->expense->user_id == user()->id;
            $isAdmin = $this->editPermission == 'all';
            
            // Employee cannot edit submitted/approved expenses
            if ($isOwner && ($this->expense->status == 'pending' || $this->expense->status == 'approved')) {
                abort_403(true, __('messages.expenseLockedForEmployee'));
            }
            
            // Admin cannot edit approved expenses (but can edit pending ones during approval)
            if ($isAdmin && $this->expense->status == 'approved') {
                abort_403(true, __('messages.expenseLockedAfterApproval'));
            }
        }

        $this->currencies = Currency::all();
        $this->categories = ExpenseCategoryController::getCategoryByCurrentRole();
        $this->employees = User::allEmployees(null, false);
        $this->pageTitle = __('modules.expenses.updateExpense');
        $this->linkExpensePermission = user()->permission('link_expense_bank_account');
        $this->viewBankAccountPermission = user()->permission('view_bankaccount');

        $bankAccounts = BankAccount::where('status', 1)->where('currency_id', $this->expense->currency_id);

        if($this->viewBankAccountPermission == 'added'){
            $bankAccounts = $bankAccounts->where('added_by', user()->id);
        }

        $bankAccounts = $bankAccounts->get();
        $this->bankDetails = $bankAccounts;


        $userId = $this->expense->user_id;

        if (!is_null($userId)) {
            $this->projects = Project::with('members')->whereHas('members', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })->get();
        }
        else {
            $this->projects = Project::get();
        }

        $this->companyCurrency = Currency::where('id', company()->currency_id)->first();

        $expense = new Expense();

        $getCustomFieldGroupsWithFields = $expense->getCustomFieldGroupsWithFields();

        if ($getCustomFieldGroupsWithFields) {
            $this->fields = $getCustomFieldGroupsWithFields->fields;
        }

        $this->view = 'expenses.ajax.edit';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('expenses.show', $this->data);

    }

    public function update(StoreExpense $request, $id)
    {
        $currencySetting = Currency::findOrFail($request->currency_id);

        $expense = Expense::findOrFail($id);
        
        // Lock pharma expense statements: prevent updating if submitted or approved
        if ($expense->expense_type == 'pharma_statement') {
            $isOwner = $expense->user_id == user()->id;
            $isAdmin = user()->permission('edit_expenses') == 'all';
            
            // Employee cannot update submitted/approved expenses
            if ($isOwner && ($expense->status == 'pending' || $expense->status == 'approved')) {
                return Reply::error(__('messages.expenseLockedForEmployee'));
            }
            
            // Admin cannot update approved expenses (but can update pending ones during approval)
            if ($isAdmin && $expense->status == 'approved') {
                return Reply::error(__('messages.expenseLockedAfterApproval'));
            }
        }
        $expense->item_name = $request->item_name;
        $expense->purchase_date = companyToYmd($request->purchase_date);
        $expense->purchase_from = $request->purchase_from;
        $expense->price = round($request->price, $currencySetting->no_of_decimal);
        $expense->currency_id = $request->currency_id;
        $expense->user_id = $request->user_id;
        $expense->category_id = $request->category_id;
        $expense->default_currency_id = company()->currency_id;
        $expense->exchange_rate = $request->exchange_rate;
        $expense->description = trim_editor($request->description);

        $expense->project_id = ($request->project_id > 0) ? $request->project_id : null;


        if ($request->bill_delete == 'yes') {
            Files::deleteFile($expense->bill, Expense::FILE_PATH);
            $expense->bill = null;
        }

        if ($request->hasFile('bill')) {
            Files::deleteFile($expense->bill, Expense::FILE_PATH);

            $filename = Files::uploadLocalOrS3($request->bill, Expense::FILE_PATH);
            $expense->bill = $filename;
        }

        if ($request->has('status')) {
            $expense->status = $request->status;
        }

        $expense->bank_account_id = $request->bank_account_id;
        $expense->save();

        // To add custom fields data
        if ($request->custom_fields_data) {
            $expense->updateCustomFieldData($request->custom_fields_data);
        }

        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('expenses.index')]);

    }

    public function destroy($id)
    {
        try {
            $this->expense = Expense::findOrFail($id);
            $this->deletePermission = user()->permission('delete_expenses');
            $isAdmin = $this->deletePermission == 'all';
            
            // Check if expense is in pending or approved state
            $isPendingOrApproved = in_array($this->expense->status, ['pending', 'approved']);
            
            // If expense is pending or approved, only admin can delete
            if ($isPendingOrApproved && !$isAdmin) {
                abort_403(true);
            }
            
            // For other expenses, check normal permissions
            if (!$isPendingOrApproved) {
                abort_403(!($this->deletePermission == 'all' || ($this->deletePermission == 'added' && $this->expense->added_by == user()->id)));
            }

            // Store expense info before deletion for logging
            $expenseId = $this->expense->id;
            $expenseStatus = $this->expense->status;
            
            // Delete the expense using DB facade to ensure it's deleted
            DB::table('expenses')->where('id', $expenseId)->delete();
            
            // Verify deletion
            $stillExists = Expense::find($expenseId);
            if ($stillExists) {
                \Log::warning('Expense still exists after delete attempt', [
                    'expense_id' => $expenseId,
                    'user_id' => user()->id
                ]);
                return Reply::error('Expense could not be deleted. It may be protected or in use.');
            }
            
            \Log::info('Expense deleted successfully', [
                'expense_id' => $expenseId,
                'user_id' => user()->id,
                'status' => $expenseStatus
            ]);
            
            return Reply::success(__('messages.deleteSuccess'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return Reply::error('Expense not found.');
        } catch (\Exception $e) {
            \Log::error('Error deleting expense', [
                'expense_id' => $id,
                'user_id' => user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Reply::error('Failed to delete expense: ' . $e->getMessage());
        }
    }

    /**
     * Apply quick action (e.g. approve, reject) to selected expenses.
     *
     * @return \Illuminate\Http\Response
     */
    public function applyQuickAction(Request $request)
    {
        switch ($request->action_type) {
        case 'delete':
            $this->deleteRecords($request);
                return Reply::success(__('messages.deleteSuccess'));
        case 'change-status':
            $this->changeBulkStatus($request);
                return Reply::success(__('messages.updateSuccess'));
        default:
                return Reply::error(__('messages.selectAction'));
        }
    }

    protected function deleteRecords($request)
    {
        $deletePermission = user()->permission('delete_expenses');
        abort_403($deletePermission != 'all');

        // Did this to call observer
        foreach (Expense::withoutGlobalScope(ActiveScope::class)->whereIn('id', explode(',', $request->row_ids))->get() as $delete) {
            // Only allow deletion of pending/approved expenses if user is admin
            $isPendingOrApproved = in_array($delete->status, ['pending', 'approved']);
            if ($isPendingOrApproved && $deletePermission != 'all') {
                continue; // Skip this expense
            }
            $delete->delete();
        }
    }

    protected function changeBulkStatus($request)
    {
        abort_403(user()->permission('edit_employees') != 'all');

        $expenses = Expense::withoutGlobalScope(ActiveScope::class)->whereIn('id', explode(',', $request->row_ids))->get();

        $expenses->each(function ($expense) use ($request) {
            $expense->status = $request->status;
            $expense->save();
        });
    }

    protected function getEmployeeProjects(Request $request)
    {
        // Get employee category
        if (!is_null($request->userId)) {
            $categories = ExpensesCategory::with('roles')->whereHas('roles', function($q) use ($request) {
                $user = User::withoutGlobalScope(ActiveScope::class)->findOrFail($request->userId);

                $roleId = (count($user->role) > 1) ? $user->role[1]->role_id : $user->role[0]->role_id;
                $q->where('role_id', $roleId);
            })->get();

        }
        else {
            $categories = ExpensesCategory::get();
        }

        if($categories) {
            foreach ($categories as $category) {
                $selected = $category->id == $request->categoryId ? 'selected' : '';
                $categories .= '<option value="' . $category->id . '"'.$selected.'>' . $category->category_name . '</option>';
            }
        }

        // Get employee project
        if (!is_null($request->userId)) {
            $projects = Project::with('members')->whereHas('members', function ($q) use ($request) {
                $q->where('user_id', $request->userId);
            })->get();
        }
        else if(user()->permission('add_expenses') == 'all' && is_null($request->userId))
        {
            $projects = [];
        }
        else {
            $projects = Project::get();
        }

        $data = null;

        if ($projects) {
            foreach ($projects as $project) {
                $data .= '<option data-currency-id="'. $project->currency_id .'" value="' . $project->id . '">' . $project->project_name . '</option>';
            }
        }


        return Reply::dataOnly(['status' => 'success', 'data' => $data, 'category' => $categories]);
    }

    protected function getCategoryEmployee(Request $request)
    {
        $expenseCategory = ExpensesCategoryRole::where('expenses_category_id', $request->categoryId)->get();
        $roleId = [];
        $managers = [];
        $employees = [];

        foreach($expenseCategory as $category) {
            array_push($roleId, $category->role_id);
        }

        if (count($roleId ) == 1 && $roleId != null) {
            $users = User::whereHas(
                'role', function($q)  use ($roleId) {
                    $q->whereIn('role_id', $roleId);
                }
            )->get();

            foreach ($users as $user) {
                ($user->hasRole('Manager')) ? array_push($managers, $user) : array_push($employees, $user);
            }
        }
        else {
            $employees = User::allEmployees(null, false);
        }

        $data = null;

        if ($employees) {
            foreach ($employees as $employee) {
                if($employee->status == 'active' || $employee->id == $request->userId){
                    $data .= '<option ';

                    $content = ($employee->status == 'deactive') ? "<span class='badge badge-pill badge-danger border align-center ml-2 px-2'>Inactive</span>" : '';
                    $selected = $employee->id == $request->userId ? 'selected' : '';
                    $itsYou = $employee->id == user()->id ? "<span class='ml-2 badge badge-secondary pr-1'>". __('app.itsYou') .'</span>' : '';

                    $data .= 'data-content="<div class=\'d-inline-block mr-1\'><img class=\'taskEmployeeImg rounded-circle\' src=\'' . $employee->image_url . '\' ></div> '.$employee->name.$itsYou.$content.'"
                    value="' . $employee->id . '"'.$selected.'>'.$employee->name.'</option>';
                }
            }
        }
        else {
            foreach ($managers as $manager) {
                $data .= '<option ';

                $selected = $manager->id == $request->userId ? 'selected' : '';
                $itsYou = $manager->id == user()->id ? "<span class='ml-2 badge badge-secondary pr-1'>" . __('app.itsYou') . '</span>' : '';
                $data .= 'data-content="<div class=\'d-inline-block mr-1\'><img class=\'taskEmployeeImg rounded-circle\' src=\'' . $manager->image_url . '\' ></div> '.$manager->name.'"
                value="' . $manager->id . '"'.$selected.'>'.$manager->name.$itsYou.'</option>';
            }
        }

        return Reply::dataOnly(['status' => 'success', 'employees' => $data]);
    }

    public function import()
    {
        $this->pageTitle = __('app.importExcel') . ' ' . __('app.menu.expenses');

        $addPermission = user()->permission('add_expenses');
        abort_403(!in_array($addPermission, ['all', 'added']));

        $this->view = 'expenses.ajax.import';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('expenses.show', $this->data);
    }

    public function importStore(ImportRequest $request)
    {
        $rvalue = $this->importFileProcess($request, ExpenseImport::class);

        if($rvalue == 'abort'){
            return Reply::error(__('messages.abortAction'));
        }

        $view = view('expenses.ajax.import_progress', $this->data)->render();

        return Reply::successWithData(__('messages.importUploadSuccess'), ['view' => $view]);
    }

    public function importProcess(ImportProcessRequest $request)
    {
        $batch = $this->importJobProcess($request, ExpenseImport::class, ImportExpenseJob::class);

        return Reply::successWithData(__('messages.importProcessStart'), ['batch' => $batch]);
    }

    public function importPharma()
    {
        $this->addPermission = user()->permission('add_expenses');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $this->resolveEmployeesForPharmaCreate(null);
        $this->loadPharmaHeadquarterAndManagers();

        $this->pageTitle = __('app.importPharmaExpenseStatement');
        $this->view = 'expenses.ajax.import_pharma';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('expenses.show', $this->data);
    }

    public function importPharmaStore(ImportPharmaRequest $request)
    {
        $this->addPermission = user()->permission('add_expenses');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        if ($this->addPermission === 'added' && (int) $request->pharma_user_id !== (int) user()->id) {
            return Reply::error(__('messages.pharmaExpenseImportSelfOnly'));
        }

        $expenseMonthYm = sprintf('%04d-%02d', $request->expense_year, $request->expense_month);

        $expenseOwner = User::findOrFail($request->pharma_user_id);
        $allowedHqIds = $this->accessibleHeadquarterIds($expenseOwner);
        if ($allowedHqIds !== null && !in_array((int) $request->pharma_headquarter_id, $allowedHqIds, true)) {
            return Reply::error(__('You can only create expenses for allocated headquarter(s).'));
        }

        if (PharmaExpenseStatementImporter::isMonthLocked((int) company()->id, (int) $request->pharma_user_id, $expenseMonthYm)) {
            return Reply::error(__('messages.pharmaExpenseImportMonthLocked'));
        }

        $rvalue = $this->importFileProcess($request, PharmaExpenseStatementImport::class);

        if ($rvalue === 'abort') {
            return Reply::error(__('messages.abortAction'));
        }

        $this->pharmaImportContext = [
            'pharma_user_id' => (int) $request->pharma_user_id,
            'pharma_headquarter_id' => (int) $request->pharma_headquarter_id,
            'expense_month' => (int) $request->expense_month,
            'expense_year' => (int) $request->expense_year,
            'posted_on' => $request->posted_on,
            'no_of_vouchers' => (int) $request->no_of_vouchers,
            'submitted_to' => (int) $request->submitted_to,
        ];

        $view = view('expenses.ajax.import_pharma_progress', $this->data)->render();

        return Reply::successWithData(__('messages.importUploadSuccess'), ['view' => $view]);
    }

    public function importPharmaProcess(ImportPharmaProcessRequest $request)
    {
        $this->addPermission = user()->permission('add_expenses');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        if ($this->addPermission === 'added' && (int) $request->pharma_user_id !== (int) user()->id) {
            return Reply::error(__('messages.pharmaExpenseImportSelfOnly'));
        }

        $expenseOwner = User::findOrFail($request->pharma_user_id);
        $allowedHqIds = $this->accessibleHeadquarterIds($expenseOwner);
        if ($allowedHqIds !== null && !in_array((int) $request->pharma_headquarter_id, $allowedHqIds, true)) {
            return Reply::error(__('You can only create expenses for allocated headquarter(s).'));
        }

        $expenseMonthYm = sprintf('%04d-%02d', $request->expense_year, $request->expense_month);
        if (PharmaExpenseStatementImporter::isMonthLocked((int) company()->id, (int) $request->pharma_user_id, $expenseMonthYm)) {
            return Reply::error(__('messages.pharmaExpenseImportMonthLocked'));
        }

        $batch = $this->importPharmaJobProcess($request);

        return Reply::successWithData(__('messages.importProcessStart'), ['batch' => $batch]);
    }

    public function downloadPharmaSample()
    {
        $addPermission = user()->permission('add_expenses');
        abort_403(!in_array($addPermission, ['all', 'added']));

        return Excel::download(new PharmaExpenseStatementSampleExport(), 'pharma-expense-statement-sample.xlsx');
    }

    /**
     * @return mixed
     */
    private function importPharmaJobProcess(ImportPharmaProcessRequest $request)
    {
        $importClass = PharmaExpenseStatementImport::class;
        $importJobClass = ImportPharmaExpenseJob::class;
        $importClassName = (new ReflectionClass($importClass))->getShortName();

        Artisan::call('queue:clear database --queue=' . $importClassName);
        Artisan::call('queue:flush');

        $columns = [];

        if (!empty($request->columns)) {
            $columns = array_filter($request->columns, function ($value) {
                return $value !== null && $value !== '';
            });
        } elseif ($request->has_heading) {
            $heading = (new HeadingRowImport)->toArray(public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $request->file))[0][0];
            $importColumns = $importClass::fields();

            $normalizedHeadings = array_map(function ($h) {
                return strtolower(trim(preg_replace('/[^a-z0-9]/', '', (string) $h)));
            }, $heading);

            foreach ($heading as $index => $headingValue) {
                $normalizedHeading = $normalizedHeadings[$index];

                foreach ($importColumns as $column) {
                    $columnId = strtolower(trim(preg_replace('/[^a-z0-9]/', '', $column['id'])));
                    $columnName = strtolower(trim(preg_replace('/[^a-z0-9]/', '', $column['name'])));

                    if (
                        $normalizedHeading === $columnId ||
                        $normalizedHeading === $columnName ||
                        strpos($normalizedHeading, $columnId) !== false ||
                        strpos($normalizedHeading, $columnName) !== false ||
                        strpos($columnId, $normalizedHeading) !== false ||
                        strpos($columnName, $normalizedHeading) !== false
                    ) {
                        $columns[$index] = $column['id'];
                        break;
                    }
                }
            }
        }

        $importInstance = new $importClass;
        Excel::import($importInstance, public_path(Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $request->file));
        $excelData = $importInstance->getProcessedData();

        if ($request->has_heading) {
            array_shift($excelData);
        }

        $userRole = session('user_roles');
        $isAdmin = is_array($userRole) && (($userRole[0] ?? '') === 'admin');

        $pharmaContext = [
            'pharma_user_id' => (int) $request->pharma_user_id,
            'pharma_headquarter_id' => (int) $request->pharma_headquarter_id,
            'expense_month' => sprintf('%04d-%02d', $request->expense_year, $request->expense_month),
            'posted_on' => companyToYmd($request->posted_on),
            'no_of_vouchers' => (int) $request->no_of_vouchers,
            'submitted_to' => (int) $request->submitted_to,
            'is_admin' => $isAdmin,
            'added_by_user_id' => (int) user()->id,
        ];

        $jobs = [];

        Session::put('leads_count', count($excelData));

        foreach ($excelData as $row) {
            $jobs[] = (new $importJobClass($row, $columns, company(), $pharmaContext));
        }

        $batch = Bus::batch($jobs)->onConnection('database')->onQueue($importClassName)->name($importClassName)->dispatch();

        $historyFolder = 'import_history/' . strtolower($importClassName);
        if (!\Storage::disk('local')->exists($historyFolder)) {
            \Storage::disk('local')->makeDirectory($historyFolder);
        }

        $file = $request->file;
        $sourcePath = Files::UPLOAD_FOLDER . '/' . Files::IMPORT_FOLDER . '/' . $file;
        $destinationPath = $historyFolder . '/' . $file;

        try {
            $destinationFullPath = storage_path('app/public/' . $destinationPath);

            if (!file_exists(dirname($destinationFullPath))) {
                mkdir(dirname($destinationFullPath), 0775, true);
            }

            copy(public_path($sourcePath), $destinationFullPath);

            $displayFilename = request()->hasFile('import_file')
                ? request()->file('import_file')->getClientOriginalName()
                : $file;
            $recordsCount = count($excelData);

            \App\Models\ImportHistory::create([
                'company_id' => company()->id,
                'user_id' => user()->id,
                'module' => $importClassName,
                'filename' => $displayFilename,
                'filepath' => $destinationPath,
                'status' => 'processing',
                'records_count' => $recordsCount,
            ]);

            Files::deleteFile($file, Files::IMPORT_FOLDER);

        } catch (\Exception $e) {
            \Log::error('Failed to create import history: ' . $e->getMessage());
            Files::deleteFile($file, Files::IMPORT_FOLDER);
        }

        return $batch;
    }

    private function resolveEmployeesForPharmaCreate($projectId): void
    {
        if ($projectId !== null) {
            $this->project = Project::with('projectMembers')->where('id', $projectId)->first();
            if ($this->project) {
                $this->projectName = $this->project->project_name;
                $this->employees = $this->project->projectMembers;
            } else {
                $this->employees = collect();
            }
        } else {
            $this->employees = User::with(['employeeDetail.designation', 'employeeDetail.headquarter'])
                ->whereHas('employeeDetail')
                ->where('company_id', company()->id)
                ->orderBy('name')
                ->get();
        }
    }

    private function loadPharmaHeadquarterAndManagers(): void
    {
        $accessibleHqIds = $this->accessibleHeadquarterIds();

        $headquartersQuery = PharmaHeadquarter::with(['exstations', 'outstations', 'area'])
            ->where('company_id', company()->id)
            ->orderBy('name');

        if ($accessibleHqIds !== null && !user()->hasAdminLikeAccess()) {
            if (empty($accessibleHqIds)) {
                $this->headquarters = collect();
            } else {
                $headquartersQuery->whereIn('id', $accessibleHqIds);
                $this->headquarters = $headquartersQuery->get();
            }
        } else {
            $this->headquarters = $headquartersQuery->get();
        }

        $this->workedWithDesignations = [
            'Independent',
            'Medical Representative',
            'ABM',
            'RBM',
            'Sales Manager',
            'Zonal Manager',
            'PMT',
            'HO',
        ];

        $emp = user()->employeeDetails ?? user()->employeeDetail;
        $this->userHeadquarter = $emp->headquarter_id ?? $emp->pharma_headquarter_id ?? null;

        if ($this->userHeadquarter === null && $this->headquarters->isNotEmpty()) {
            $this->userHeadquarter = $this->headquarters->first()->id;
        }
        $this->showHqDropdownForPharmaRoles = $this->headquarters->count() > 1;

        $this->currentUserHeadquarter = $this->userHeadquarter;
        $this->currentUserHeadquarterName = null;

        if ($this->userHeadquarter) {
            $hq = $this->headquarters->firstWhere('id', $this->userHeadquarter);
            if ($hq) {
                $this->currentUserHeadquarterName = $hq->name;
            }
        }

        $this->reportingManagerId = optional(user()->employeeDetails)->reporting_to;

        $this->managers = User::with(['employeeDetail.designation'])
            ->whereHas('employeeDetail')
            ->where('id', '!=', user()->id)
            ->where('company_id', company()->id)
            ->orderBy('name')
            ->get();
    }

    public function checkExisting(Request $request)
    {
        $month = $request->get('month');
        $year = $request->get('year');
        $expenseMonth = $request->get('expense_month');
        
        if (!$month || !$year || !$expenseMonth) {
            return response()->json([
                'has_expenses' => false,
                'is_locked' => false
            ]);
        }
        
        // Only check for employees (not admins)
        if (user()->permission('add_expenses') == 'added') {
            $existingExpenses = Expense::where('expense_type', 'pharma_statement')
                ->where('company_id', company()->id)
                ->where('user_id', user()->id)
                ->where('expense_month', $expenseMonth)
                ->orderBy('purchase_date', 'asc')
                ->get();
            
            if ($existingExpenses->isNotEmpty()) {
                // Check if ANY expense is pending or approved - if so, lock the ENTIRE month
                $hasPending = $existingExpenses->where('status', 'pending')->isNotEmpty();
                $hasApproved = $existingExpenses->where('status', 'approved')->isNotEmpty();
                $isLocked = $hasPending || $hasApproved;
                $lockStatus = $hasApproved ? 'approved' : ($hasPending ? 'pending' : null);
                
                // Map expenses by day (include all expenses)
                $expensesByDay = [];
                foreach ($existingExpenses as $exp) {
                    $day = \Carbon\Carbon::parse($exp->purchase_date)->day;
                    $expensesByDay[$day] = [
                        'id' => $exp->id,
                        'town_worked' => $exp->town_worked ?? '',
                        'worked_with' => $exp->worked_with ? json_decode($exp->worked_with, true) : [],
                        'headquarter_from' => $exp->headquarter_from ?? '',
                        'headquarter_to' => $exp->headquarter_to ?? '',
                        'mode_of_transport' => $exp->mode_of_transport ?? '',
                        'no_of_doctors_met' => $exp->no_of_doctors_met ?? 0,
                        'no_of_retailers_met' => $exp->no_of_retailers_met ?? 0,
                        'km' => $exp->km ?? 0,
                        'fare_rs' => $exp->fare_rs ?? 0,
                        'daily_allowance_hq_rs' => $exp->daily_allowance_hq_rs ?? 0,
                        'daily_allowance_ex_rs' => $exp->daily_allowance_ex_rs ?? 0,
                        'daily_allowance_os_rs' => $exp->daily_allowance_os_rs ?? 0,
                        'fixed_expenses' => $exp->fixed_expenses ?? 0,
                        'other_expenses' => $exp->other_expenses ?? 0,
                        'remarks' => $exp->remarks ?? '',
                        'status' => $exp->status ?? 'pending'
                    ];
                }
                
                return response()->json([
                    'has_expenses' => true,
                    'is_locked' => $isLocked,
                    'lock_status' => $lockStatus,
                    'existing_expenses' => $expensesByDay
                ]);
            }
        }
        
        return response()->json([
            'has_expenses' => false,
            'is_locked' => false
        ]);
    }

}
