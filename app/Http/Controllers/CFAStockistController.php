<?php

namespace App\Http\Controllers;

use App\DataTables\CFAStockistsDataTable;
use App\Helper\Reply;
use App\Http\Requests\StoreCFAStockistRequest;
use App\Http\Requests\UpdateCFAStockistRequest;
use App\Models\CFAStockist;
use App\Models\User;
use Illuminate\Http\Request;

class CFAStockistController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('CFA Stockists');
    }

    /**
     * Display a listing of CFA Stockists.
     */
    public function index(CFAStockistsDataTable $dataTable)
    {
        $viewPermission = user()->permission('view_stockists');
        abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));

        if (!request()->ajax()) {
            // Load CFA/Distributor clients for mapping
            $this->cfaDistributors = User::without('session')
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
                ->where(function($query) {
                    $query->where(function($q) {
                        $q->whereRaw('LOWER(client_categories.category_name) LIKE ?', ['%cfa%'])
                          ->orWhereRaw('LOWER(client_categories.category_name) LIKE ?', ['%distributor%']);
                    })
                    ->orWhereNotNull('client_areas.area_id');
                })
                ->groupBy('users.id', 'users.name', 'users.email', 'client_details.company_name')
                ->orderBy('client_details.company_name', 'asc')
                ->orderBy('users.name', 'asc')
                ->get();
        }

        return $dataTable->render('cfa-stockists.index', $this->data);
    }

    /**
     * Show the form for creating a new CFA Stockist.
     */
    public function create()
    {
        $this->addPermission = user()->permission('add_stockists');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        // Load CFA/Distributor clients
        $this->cfaDistributors = User::without('session')
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
            ->where(function($query) {
                $query->where(function($q) {
                    $q->whereRaw('LOWER(client_categories.category_name) LIKE ?', ['%cfa%'])
                      ->orWhereRaw('LOWER(client_categories.category_name) LIKE ?', ['%distributor%']);
                })
                ->orWhereNotNull('client_areas.area_id');
            })
            ->groupBy('users.id', 'users.name', 'users.email', 'client_details.company_name')
            ->orderBy('client_details.company_name', 'asc')
            ->orderBy('users.name', 'asc')
            ->get();
        
        // Ensure cfaDistributors is always a collection
        if (!isset($this->cfaDistributors) || !$this->cfaDistributors) {
            $this->cfaDistributors = collect([]);
        }

        if (request()->ajax()) {
            $html = view('cfa-stockists.ajax.create', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('cfa-stockists.create', $this->data);
    }

    /**
     * Store a newly created CFA Stockist mapping.
     */
    public function store(StoreCFAStockistRequest $request)
    {
        $this->addPermission = user()->permission('add_stockists');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $cfaStockist = new CFAStockist();
        $cfaStockist->company_id = company()->id;
        $cfaStockist->shopname = $request->shopname;
        $cfaStockist->fullname = $request->fullname;
        $cfaStockist->email = $request->email;
        $cfaStockist->mobile = $request->mobile;
        $cfaStockist->owner_name = $request->owner_name;
        $cfaStockist->owner_mobile = $request->owner_mobile;
        $cfaStockist->address = $request->address;
        $cfaStockist->gst_number = $request->gst_number;
        $cfaStockist->dl_number = $request->dl_number;
        // msl_number removed from form, but keeping in model for backward compatibility
        $cfaStockist->msl_number = $request->msl_number ?? null;
        $cfaStockist->save();
        
        // Sync CFA/Distributors
        if ($request->has('cfa_distributor_ids')) {
            $cfaDistributorIds = $request->cfa_distributor_ids;
            $companyId = company()->id;
            
            // Format sync data with company_id for each distributor
            $syncData = [];
            foreach ($cfaDistributorIds as $distributorId) {
                $syncData[$distributorId] = ['company_id' => $companyId];
            }
            
            $cfaStockist->cfaDistributors()->sync($syncData);
        }

        return Reply::successWithData(__('messages.recordSaved'), [
            'redirectUrl' => route('cfa-stockists.index')
        ]);
    }

    /**
     * Display the specified CFA Stockist.
     */
    public function show($id)
    {
        $this->viewPermission = user()->permission('view_stockists');
        abort_403(!in_array($this->viewPermission, ['all', 'added', 'owned', 'both']));

        $this->cfaStockist = CFAStockist::with('cfaDistributors.clientDetails')
            ->where('company_id', company()->id)
            ->findOrFail($id);

        $this->pageTitle = __('CFA Stockist') . ' - ' . $this->cfaStockist->shopname;

        if (request()->ajax()) {
            $html = view('cfa-stockists.ajax.show', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('cfa-stockists.show', $this->data);
    }

    /**
     * Show the form for editing the specified CFA Stockist.
     */
    public function edit($id)
    {
        $this->editPermission = user()->permission('edit_stockists');
        abort_403(!in_array($this->editPermission, ['all', 'added']));

        $this->cfaStockist = CFAStockist::with('cfaDistributors')
            ->where('company_id', company()->id)
            ->findOrFail($id);

        $this->pageTitle = __('Edit CFA Stockist') . ' - ' . $this->cfaStockist->shopname;

        // Load CFA/Distributor clients
        $this->cfaDistributors = User::without('session')
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
            ->where(function($query) {
                $query->where(function($q) {
                    $q->whereRaw('LOWER(client_categories.category_name) LIKE ?', ['%cfa%'])
                      ->orWhereRaw('LOWER(client_categories.category_name) LIKE ?', ['%distributor%']);
                })
                ->orWhereNotNull('client_areas.area_id');
            })
            ->groupBy('users.id', 'users.name', 'users.email', 'client_details.company_name')
            ->orderBy('client_details.company_name', 'asc')
            ->orderBy('users.name', 'asc')
            ->get();

        if (request()->ajax()) {
            $html = view('cfa-stockists.ajax.edit', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('cfa-stockists.edit', $this->data);
    }

    /**
     * Update the specified CFA Stockist mapping.
     */
    public function update(UpdateCFAStockistRequest $request, $id)
    {
        $this->editPermission = user()->permission('edit_stockists');
        abort_403(!in_array($this->editPermission, ['all', 'added']));

        $cfaStockist = CFAStockist::where('company_id', company()->id)->findOrFail($id);
        
        $cfaStockist->shopname = $request->shopname;
        $cfaStockist->fullname = $request->fullname;
        $cfaStockist->email = $request->email;
        $cfaStockist->mobile = $request->mobile;
        $cfaStockist->owner_name = $request->owner_name;
        $cfaStockist->owner_mobile = $request->owner_mobile;
        $cfaStockist->address = $request->address;
        $cfaStockist->gst_number = $request->gst_number;
        $cfaStockist->dl_number = $request->dl_number;
        $cfaStockist->msl_number = $request->msl_number;
        $cfaStockist->save();
        
        // Sync CFA/Distributors
        if ($request->has('cfa_distributor_ids')) {
            $cfaDistributorIds = $request->cfa_distributor_ids;
            $companyId = company()->id;
            
            // Format sync data with company_id for each distributor
            $syncData = [];
            foreach ($cfaDistributorIds as $distributorId) {
                $syncData[$distributorId] = ['company_id' => $companyId];
            }
            
            $cfaStockist->cfaDistributors()->sync($syncData);
        } else {
            $cfaStockist->cfaDistributors()->detach();
        }

        return Reply::successWithData(__('messages.updateSuccess'), [
            'redirectUrl' => route('cfa-stockists.index')
        ]);
    }

    /**
     * Remove the specified CFA Stockist.
     */
    public function destroy($id)
    {
        $this->deletePermission = user()->permission('delete_stockists');
        abort_403(!in_array($this->deletePermission, ['all', 'added']));

        $cfaStockist = CFAStockist::where('company_id', company()->id)->findOrFail($id);
        $cfaStockist->delete();

        return Reply::success(__('messages.deleteSuccess'));
    }

    /**
     * Get CFA stockists for a specific CFA/Distributor (AJAX)
     */
    public function getStockistsForCFA(Request $request)
    {
        $cfaDistributorId = $request->cfa_distributor_id;
        
        if (!$cfaDistributorId) {
            return Reply::dataOnly(['status' => 'success', 'data' => '<option value="">-- Select Stockist --</option>']);
        }

        $cfaDistributor = User::where('company_id', company()->id)->findOrFail($cfaDistributorId);
        $cfaStockists = $cfaDistributor->cfaStockists()
            ->where('cfa_stockists.company_id', company()->id)
            ->get();

        $options = '<option value="">-- Select CFA Stockist --</option>';
        foreach ($cfaStockists as $stockist) {
            $displayText = ($stockist->cfa_stockist_id ? $stockist->cfa_stockist_id . ' - ' : '') . $stockist->shopname;
            if ($stockist->fullname) {
                $displayText .= ' - ' . $stockist->fullname;
            }
            $options .= '<option value="' . $stockist->id . '">' . $displayText . '</option>';
        }

        return Reply::dataOnly(['status' => 'success', 'data' => $options]);
    }
}
