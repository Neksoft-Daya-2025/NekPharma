<?php

namespace App\Http\Controllers;

use App\DataTables\CFAStockistsDataTable;
use App\Helper\Reply;
use App\Helpers\PharmaDesignationHelper;
use App\Http\Requests\StoreCFAStockistRequest;
use App\Http\Requests\UpdateCFAStockistRequest;
use App\Models\CFAStockist;
use App\Models\User;
use App\Models\PharmaArea;
use App\Models\PharmaHeadquarter;
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
        // Admin, accountant, FSA Executive, and MIS Executive users have full access
        if (!PharmaDesignationHelper::hasFullCFAAccess()) {
            $viewPermission = user()->permission('view_cfa_stockists');
            abort_403(!in_array($viewPermission, ['all', 'added', 'owned', 'both']));
        }

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
        // Admin, accountant, FSA Executive, and MIS Executive users have full access
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            $this->addPermission = 'all';
        } else {
            $this->addPermission = user()->permission('add_cfa_stockists');
            abort_403(!in_array($this->addPermission, ['all', 'added']));
        }

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

        $this->areas = PharmaArea::all();
        $this->headquarters = PharmaHeadquarter::all();

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
        // Admin, accountant, FSA Executive, and MIS Executive users have full access
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            $this->addPermission = 'all';
        } else {
            $this->addPermission = user()->permission('add_cfa_stockists');
            abort_403(!in_array($this->addPermission, ['all', 'added']));
        }

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
        $cfaStockist->headquarter_id = $request->headquarter_id ?: null;
        $cfaStockist->area_id = $request->area_id ?: null;

        if ($request->has('cfa_distributor_ids') && !empty($request->cfa_distributor_ids) && !PharmaDesignationHelper::hasFullCFAAccess()) {
            $validation = $this->validateCFADistributorAssignment(
                $request->cfa_distributor_ids,
                $cfaStockist->area_id,
                $cfaStockist->headquarter_id
            );
            if ($validation !== true) {
                return Reply::error($validation);
            }
        }

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
        // Admin, accountant, FSA Executive, and MIS Executive users have full access
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            $this->viewPermission = 'all';
        } else {
            $this->viewPermission = user()->permission('view_cfa_stockists');
            abort_403(!in_array($this->viewPermission, ['all', 'added', 'owned', 'both']));
        }

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
        // Admin, accountant, FSA Executive, and MIS Executive users have full access
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            $this->editPermission = 'all';
        } else {
            $this->editPermission = user()->permission('edit_cfa_stockists');
            abort_403(!in_array($this->editPermission, ['all', 'added']));
        }

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

        // Form-side filter: only show CFAs that have this stockist's HQ/Area in their assignment
        if (($this->cfaStockist->area_id || $this->cfaStockist->headquarter_id) && $this->cfaDistributors->isNotEmpty()) {
            $ids = $this->cfaDistributors->pluck('id')->toArray();
            $withDetails = User::with('clientDetails.areas', 'clientDetails.headquarters')
                ->whereIn('id', $ids)
                ->get();
            $this->cfaDistributors = $withDetails->filter(function ($user) {
                $d = $user->clientDetails;
                if (!$d) {
                    return false;
                }
                if ($this->cfaStockist->area_id && !$d->areas->contains('id', $this->cfaStockist->area_id)) {
                    return false;
                }
                if ($this->cfaStockist->headquarter_id && !$d->headquarters->contains('id', $this->cfaStockist->headquarter_id)) {
                    return false;
                }
                return true;
            })->values();
        }

        $this->areas = PharmaArea::all();
        $this->headquarters = PharmaHeadquarter::all();

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
        // Admin, accountant, FSA Executive, and MIS Executive users have full access
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            $this->editPermission = 'all';
        } else {
            $this->editPermission = user()->permission('edit_cfa_stockists');
            abort_403(!in_array($this->editPermission, ['all', 'added']));
        }

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
        $cfaStockist->headquarter_id = $request->headquarter_id ?: null;
        $cfaStockist->area_id = $request->area_id ?: null;

        if ($request->has('cfa_distributor_ids') && !empty($request->cfa_distributor_ids) && !PharmaDesignationHelper::hasFullCFAAccess()) {
            $validation = $this->validateCFADistributorAssignment(
                $request->cfa_distributor_ids,
                $cfaStockist->area_id,
                $cfaStockist->headquarter_id
            );
            if ($validation !== true) {
                return Reply::error($validation);
            }
        }

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
        // Admin, accountant, FSA Executive, and MIS Executive users have full access
        if (PharmaDesignationHelper::hasFullCFAAccess()) {
            $this->deletePermission = 'all';
        } else {
            $this->deletePermission = user()->permission('delete_cfa_stockists');
            abort_403(!in_array($this->deletePermission, ['all', 'added']));
        }

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

        $cfaDistributor = User::where('company_id', company()->id)->find($cfaDistributorId);
        if (!$cfaDistributor) {
            return Reply::dataOnly(['status' => 'success', 'data' => '<option value="">-- Select Stockist --</option>']);
        }

        // Pivot-linked stockists only (same as InvoiceController::getCFAStockists).
        // HQ/Area filtering here hid valid mappings when stockist HQ/Area did not match client assignments.
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

    /**
     * Validate that each selected CFA/Distributor has this stockist's HQ/Area in their assignment.
     * If both area and HQ are set on the stockist, either may match the client's assignment (OR).
     * If only one is set, that one must match. IDs are compared as integers to avoid type mismatches.
     * Returns true if valid, or an error message string.
     */
    private function validateCFADistributorAssignment(array $cfaDistributorIds, $stockistAreaId, $stockistHeadquarterId): bool|string
    {
        if (!$stockistAreaId && !$stockistHeadquarterId) {
            return true;
        }

        $users = User::with('clientDetails.areas', 'clientDetails.headquarters')
            ->whereIn('id', $cfaDistributorIds)
            ->where('company_id', company()->id)
            ->get();

        foreach ($users as $user) {
            $details = $user->clientDetails;
            if (!$details) {
                return __('A selected CFA/Distributor has no client details. Please assign Area or HQ to the client first.');
            }

            $name = $details->company_name ?? $user->name;
            $areaMatch = $this->cfaClientMatchesArea($details, $stockistAreaId);
            $hqMatch = $this->cfaClientMatchesHeadquarter($details, $stockistHeadquarterId);

            if ($stockistAreaId && $stockistHeadquarterId) {
                if (! $areaMatch && ! $hqMatch) {
                    return __('The stockist does not match :name\'s assigned areas or headquarters. Add this stockist\'s area or headquarter on the client (CFA), or choose a CFA that is assigned to this location.', ['name' => $name]);
                }

                continue;
            }

            if ($stockistAreaId && ! $areaMatch) {
                return __('The stockist\'s Area is not in :name\'s assigned areas. CFA can only invoice stockists from their assigned HQ/Area.', ['name' => $name]);
            }

            if ($stockistHeadquarterId && ! $hqMatch) {
                return __('The stockist\'s Headquarter is not in :name\'s assigned headquarters. CFA can only invoice stockists from their assigned HQ/Area.', ['name' => $name]);
            }
        }

        return true;
    }

    private function cfaClientMatchesArea($clientDetails, $areaId): bool
    {
        if (! $areaId) {
            return true;
        }

        $id = (int) $areaId;

        return $clientDetails->areas->contains(static function ($a) use ($id) {
            return (int) $a->id === $id;
        });
    }

    private function cfaClientMatchesHeadquarter($clientDetails, $headquarterId): bool
    {
        if (! $headquarterId) {
            return true;
        }

        $id = (int) $headquarterId;

        return $clientDetails->headquarters->contains(static function ($h) use ($id) {
            return (int) $h->id === $id;
        });
    }
}
