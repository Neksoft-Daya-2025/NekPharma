<?php

namespace Modules\SFC\Http\Controllers;

use App\Helper\Reply;
use App\Http\Controllers\AccountBaseController;
use Illuminate\Http\Request;
use Illuminate\Contracts\Support\Renderable;
use Modules\SFC\DataTables\SFCDocumentDataTable;
use Modules\SFC\Entities\SFCDocument;
use Modules\SFC\Entities\SFCChartItem;
use Modules\SFC\Entities\SFCSetting;

class SFCController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = __('sfc::app.menu.sfcChart');
        $this->middleware(function ($request, $next) {
            abort_403(!in_array(SFCSetting::MODULE_NAME, $this->user->modules));
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(SFCDocumentDataTable $dataTable)
    {
        $this->viewSfcChartPermission = user()->permission('view_sfc_chart');
        abort_403($this->viewSfcChartPermission == 'none');

        return $dataTable->render('sfc::sfc-documents.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->addPermission = user()->permission('add_sfc_chart');
        abort_403($this->addPermission !== 'all');

        // Get employee details for auto-population
        $employee = \App\Models\EmployeeDetails::where('user_id', user()->id)->with(['headquarter.area.region', 'headquarter.exstations', 'headquarter.outstations'])->first();
        
        // Auto-populate employee data
        $this->data['employeeName'] = user()->name;
        $this->data['employeeHeadquarter'] = $employee && $employee->headquarter ? $employee->headquarter->name : null;
        $this->data['employeeHeadquarterId'] = $employee && $employee->headquarter ? $employee->headquarter->id : null;
        $this->data['employeeArea'] = $employee && $employee->headquarter && $employee->headquarter->area ? $employee->headquarter->area->name : null;
        $this->data['employeeRegion'] = $employee && $employee->headquarter && $employee->headquarter->area && $employee->headquarter->area->region ? $employee->headquarter->area->region->name : null;
        
        // Load towns from headquarter, ex-stations, and out-stations
        $towns = collect();
        if ($employee && $employee->headquarter) {
            $hq = $employee->headquarter;
            // Add headquarter name
            $towns->push(['name' => $hq->name, 'type' => 'Headquarter']);
            
            // Add ex-station names
            foreach ($hq->exstations as $exstation) {
                $towns->push(['name' => $exstation->name, 'type' => 'Ex-Station']);
            }
            
            // Add out-station names
            foreach ($hq->outstations as $outstation) {
                $towns->push(['name' => $outstation->name, 'type' => 'Out-Station']);
            }
        }
        $this->data['towns'] = $towns;
        
        // Load stockists based on employee's headquarter
        $stockists = collect();
        if ($employee && $employee->headquarter) {
            $hqId = $employee->headquarter->id;
            $exstationIds = $employee->headquarter->exstations->pluck('id')->toArray();
            $outstationIds = $employee->headquarter->outstations->pluck('id')->toArray();
            
            $stockists = \App\Models\Stockist::where(function($q) use ($hqId, $exstationIds, $outstationIds) {
                $q->where('headquarter_id', $hqId);
                if (!empty($exstationIds)) {
                    $q->orWhereIn('exstation_id', $exstationIds);
                }
                if (!empty($outstationIds)) {
                    $q->orWhereIn('outstation_id', $outstationIds);
                }
            })->orderBy('shopname')->get();
        }
        $this->data['stockists'] = $stockists;

        $this->pageTitle = __('sfc::app.menu.addSfcChart');
        $this->view = 'sfc::sfc-documents.ajax.create';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('sfc::sfc-documents.create', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->addPermission = user()->permission('add_sfc_chart');
        abort_403($this->addPermission !== 'all');

        $request->validate([
            'name' => 'nullable|string|max:255',
            'headquarter' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'vip_dr_count' => 'nullable|integer|min:0',
            'core_dr_count' => 'nullable|integer|min:0',
            'vip_visits_per_month' => 'nullable|integer|min:0',
            'core_visits_per_month' => 'nullable|integer|min:0',
            'filled_by_name' => 'nullable|string|max:255',
            'items' => 'nullable|array',
            'items.*.town_name' => 'nullable|string|max:255',
        ]);

        // Validate that at least one row has data before saving (no auto-save)
        $hasValidData = false;
        if (!empty($request->items)) {
            foreach ($request->items as $item) {
                if (!empty($item['town_name']) || 
                    !empty($item['covered_from']) || 
                    !empty($item['one_way_km_actual']) || 
                    !empty($item['two_way_fare']) || 
                    !empty($item['one_way_fare']) ||
                    !empty($item['stockist_name'])) {
                    $hasValidData = true;
                    break;
                }
            }
        }

        if (!$hasValidData) {
            return Reply::error(__('Please fill at least one row with data before saving.'));
        }

        \DB::beginTransaction();
        try {
            $document = new SFCDocument();
            $document->company_id = company()->id;
            $document->name = $request->name ?? user()->name;
            $document->headquarter = $request->headquarter;
            $document->area = $request->area;
            $document->region = $request->region;
            $document->vip_dr_count = $request->vip_dr_count ?? 52;
            $document->core_dr_count = $request->core_dr_count ?? 48;
            $document->vip_visits_per_month = $request->vip_visits_per_month ?? 2;
            $document->core_visits_per_month = $request->core_visits_per_month ?? 4;
            $document->filled_by_name = $request->filled_by_name;
            $document->added_by = user()->id;
            $document->calculateStatistics();
            $document->save();

            // Save chart items - only save rows that have at least some data
            foreach ($request->items ?? [] as $index => $item) {
                // Check if row has any meaningful data before saving (follow system pattern)
                $hasData = !empty($item['town_name']) || 
                          !empty($item['covered_from']) || 
                          !empty($item['one_way_km_actual']) || 
                          !empty($item['two_way_fare']) || 
                          !empty($item['one_way_fare']) ||
                          !empty($item['stockist_name']);
                
                if ($hasData) {
                    $chartItem = new SFCChartItem();
                    $chartItem->sfc_document_id = $document->id;
                    $chartItem->serial_number = $index + 1;
                    $chartItem->covered_from = $item['covered_from'] ?? null;
                    $chartItem->town_name = $item['town_name'];
                    // Store actual KM as single value (convert from array if needed for backward compatibility)
                    $actualKm = $item['one_way_km_actual'] ?? null;
                    if (is_array($actualKm)) {
                        // If it's an array (from old data), sum it up
                        $actualKm = !empty($actualKm) ? array_sum(array_filter(array_map('floatval', $actualKm))) : null;
                    } else {
                        $actualKm = $actualKm ? (float)$actualKm : null;
                    }
                    $chartItem->one_way_km_actual = $actualKm;
                    $chartItem->grace = $item['grace'] ?? null;
                    $chartItem->total_km = $item['total_km'] ?? null;
                    $chartItem->two_way_fare = $item['two_way_fare'] ?? null;
                    $chartItem->one_way_fare = $item['one_way_fare'] ?? null;
                    $chartItem->ex_hq_os = $item['ex_hq_os'] ?? null;
                    $chartItem->mode_of_travel = $item['mode_of_travel'] ?? null;
                    $chartItem->time_in_hours = $item['time_in_hours'] ?? null;
                    $chartItem->no_of_days_monthly = $item['no_of_days_monthly'] ?? null;
                    $chartItem->vip_dr_count = $item['vip_dr_count'] ?? 0;
                    $chartItem->core_dr_count = $item['core_dr_count'] ?? 0;
                    $chartItem->stockist_name = $item['stockist_name'] ?? null;
                    $chartItem->current_business = $item['current_business'] ?? null;
                    $chartItem->approx_business_expected = $item['approx_business_expected'] ?? null;
                    $chartItem->remarks = $item['remarks'] ?? null;
                    $chartItem->calculateTotalDrCount();
                    $chartItem->save();
                }
            }

            \DB::commit();
            return Reply::successWithData(__('messages.recordSaved'), ['id' => $document->id]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return Reply::error($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $this->viewSfcChartPermission = user()->permission('view_sfc_chart');
        abort_403($this->viewSfcChartPermission == 'none');

        $this->document = SFCDocument::with('chartItems')->findOrFail($id);
        $this->pageTitle = __('sfc::app.menu.viewSfcChart') . ' - ' . ($this->document->name ?? 'Document #' . $this->document->id);

        return view('sfc::sfc-documents.show', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $this->editPermission = user()->permission('edit_sfc_chart');
        abort_403($this->editPermission !== 'all');

        $this->document = SFCDocument::with('chartItems')->findOrFail($id);
        
        // Get employee details for auto-population (if not already set)
        $headquarterId = null;
        if (!$this->document->name || !$this->document->headquarter) {
            $employee = \App\Models\EmployeeDetails::where('user_id', user()->id)->with(['headquarter.area.region', 'headquarter.exstations', 'headquarter.outstations'])->first();
            
            $this->data['employeeName'] = $this->document->name ?: user()->name;
            $this->data['employeeHeadquarter'] = $this->document->headquarter ?: ($employee && $employee->headquarter ? $employee->headquarter->name : null);
            $headquarterId = $employee && $employee->headquarter ? $employee->headquarter->id : null;
            $this->data['employeeArea'] = $this->document->area ?: ($employee && $employee->headquarter && $employee->headquarter->area ? $employee->headquarter->area->name : null);
            $this->data['employeeRegion'] = $this->document->region ?: ($employee && $employee->headquarter && $employee->headquarter->area && $employee->headquarter->area->region ? $employee->headquarter->area->region->name : null);
        } else {
            $this->data['employeeName'] = $this->document->name;
            $this->data['employeeHeadquarter'] = $this->document->headquarter;
            // Try to find headquarter by name
            $hq = \App\Models\PharmaHeadquarter::where('name', $this->document->headquarter)->where('company_id', company()->id)->first();
            $headquarterId = $hq ? $hq->id : null;
            $this->data['employeeArea'] = $this->document->area;
            $this->data['employeeRegion'] = $this->document->region;
        }
        $this->data['employeeHeadquarterId'] = $headquarterId;
        
        // Load towns from headquarter, ex-stations, and out-stations
        $towns = collect();
        if ($headquarterId) {
            $hq = \App\Models\PharmaHeadquarter::with(['exstations', 'outstations'])->find($headquarterId);
            if ($hq) {
                // Add headquarter name
                $towns->push(['name' => $hq->name, 'type' => 'Headquarter']);
                
                // Add ex-station names
                foreach ($hq->exstations as $exstation) {
                    $towns->push(['name' => $exstation->name, 'type' => 'Ex-Station']);
                }
                
                // Add out-station names
                foreach ($hq->outstations as $outstation) {
                    $towns->push(['name' => $outstation->name, 'type' => 'Out-Station']);
                }
            }
        }
        $this->data['towns'] = $towns;
        
        // Load stockists based on headquarter
        $stockists = collect();
        if ($headquarterId) {
            $hq = \App\Models\PharmaHeadquarter::with(['exstations', 'outstations'])->find($headquarterId);
            if ($hq) {
                $exstationIds = $hq->exstations->pluck('id')->toArray();
                $outstationIds = $hq->outstations->pluck('id')->toArray();
                
                $stockists = \App\Models\Stockist::where(function($q) use ($headquarterId, $exstationIds, $outstationIds) {
                    $q->where('headquarter_id', $headquarterId);
                    if (!empty($exstationIds)) {
                        $q->orWhereIn('exstation_id', $exstationIds);
                    }
                    if (!empty($outstationIds)) {
                        $q->orWhereIn('outstation_id', $outstationIds);
                    }
                })->orderBy('shopname')->get();
            }
        }
        $this->data['stockists'] = $stockists;
        
        $this->pageTitle = __('sfc::app.menu.editSfcChart');
        $this->view = 'sfc::sfc-documents.ajax.edit';

        if (request()->ajax()) {
            $html = view($this->view, $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('sfc::sfc-documents.create', $this->data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $this->editPermission = user()->permission('edit_sfc_chart');
        abort_403($this->editPermission !== 'all');

        $request->validate([
            'name' => 'nullable|string|max:255',
            'headquarter' => 'nullable|string|max:255',
            'area' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'vip_dr_count' => 'nullable|integer|min:0',
            'core_dr_count' => 'nullable|integer|min:0',
            'vip_visits_per_month' => 'nullable|integer|min:0',
            'core_visits_per_month' => 'nullable|integer|min:0',
            'filled_by_name' => 'nullable|string|max:255',
            'items' => 'nullable|array',
            'items.*.town_name' => 'nullable|string|max:255',
        ]);

        // Validate that at least one row has data before saving (no auto-save)
        $hasValidData = false;
        if (!empty($request->items)) {
            foreach ($request->items as $item) {
                if (!empty($item['town_name']) || 
                    !empty($item['covered_from']) || 
                    !empty($item['one_way_km_actual']) || 
                    !empty($item['two_way_fare']) || 
                    !empty($item['one_way_fare']) ||
                    !empty($item['stockist_name'])) {
                    $hasValidData = true;
                    break;
                }
            }
        }

        if (!$hasValidData) {
            return Reply::error(__('Please fill at least one row with data before saving.'));
        }

        \DB::beginTransaction();
        try {
            $document = SFCDocument::findOrFail($id);
            $document->name = $request->name ?? user()->name;
            $document->headquarter = $request->headquarter;
            $document->area = $request->area;
            $document->region = $request->region;
            $document->vip_dr_count = $request->vip_dr_count ?? 52;
            $document->core_dr_count = $request->core_dr_count ?? 48;
            $document->vip_visits_per_month = $request->vip_visits_per_month ?? 2;
            $document->core_visits_per_month = $request->core_visits_per_month ?? 4;
            $document->filled_by_name = $request->filled_by_name;
            $document->calculateStatistics();
            $document->save();

            // Delete existing items
            $document->chartItems()->delete();

            // Save new chart items - only save rows that have at least some data (follow system pattern)
            foreach ($request->items ?? [] as $index => $item) {
                // Check if row has any meaningful data before saving
                $hasData = !empty($item['town_name']) || 
                          !empty($item['covered_from']) || 
                          !empty($item['one_way_km_actual']) || 
                          !empty($item['two_way_fare']) || 
                          !empty($item['one_way_fare']) ||
                          !empty($item['stockist_name']);
                
                if ($hasData) {
                    $chartItem = new SFCChartItem();
                    $chartItem->sfc_document_id = $document->id;
                    $chartItem->serial_number = $index + 1;
                    $chartItem->covered_from = $item['covered_from'] ?? null;
                    $chartItem->town_name = $item['town_name'];
                    // Store actual KM as single value (convert from array if needed for backward compatibility)
                    $actualKm = $item['one_way_km_actual'] ?? null;
                    if (is_array($actualKm)) {
                        // If it's an array (from old data), sum it up
                        $actualKm = !empty($actualKm) ? array_sum(array_filter(array_map('floatval', $actualKm))) : null;
                    } else {
                        $actualKm = $actualKm ? (float)$actualKm : null;
                    }
                    $chartItem->one_way_km_actual = $actualKm;
                    $chartItem->grace = $item['grace'] ?? null;
                    $chartItem->total_km = $item['total_km'] ?? null;
                    $chartItem->two_way_fare = $item['two_way_fare'] ?? null;
                    $chartItem->one_way_fare = $item['one_way_fare'] ?? null;
                    $chartItem->ex_hq_os = $item['ex_hq_os'] ?? null;
                    $chartItem->mode_of_travel = $item['mode_of_travel'] ?? null;
                    $chartItem->time_in_hours = $item['time_in_hours'] ?? null;
                    $chartItem->no_of_days_monthly = $item['no_of_days_monthly'] ?? null;
                    $chartItem->vip_dr_count = $item['vip_dr_count'] ?? 0;
                    $chartItem->core_dr_count = $item['core_dr_count'] ?? 0;
                    $chartItem->stockist_name = $item['stockist_name'] ?? null;
                    $chartItem->current_business = $item['current_business'] ?? null;
                    $chartItem->approx_business_expected = $item['approx_business_expected'] ?? null;
                    $chartItem->remarks = $item['remarks'] ?? null;
                    $chartItem->calculateTotalDrCount();
                    $chartItem->save();
                }
            }

            \DB::commit();
            return Reply::successWithData(__('messages.updateSuccess'), ['id' => $document->id]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return Reply::error($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $deletePermission = user()->permission('delete_sfc_chart');
        abort_403($deletePermission !== 'all');

        SFCDocument::destroy($id);
        return Reply::success(__('messages.deleteSuccess'));
    }

    /**
     * Approve document (ABM or RBM)
     */
    public function approve(Request $request, $id)
    {
        $document = SFCDocument::findOrFail($id);
        $type = $request->type; // 'abm' or 'rbm'

        if ($type == 'abm') {
            $document->abm_approval = $request->approval_text;
            $document->abm_approved_by = user()->id;
            $document->abm_approved_at = now();
        } elseif ($type == 'rbm') {
            $document->rbm_approval = $request->approval_text;
            $document->rbm_approved_by = user()->id;
            $document->rbm_approved_at = now();
        }

        $document->save();
        return Reply::success(__('messages.updateSuccess'));
    }

    /**
     * Get stockists by headquarter ID
     */
    public function getStockistsByHeadquarter($headquarterId)
    {
        try {
            $hq = \App\Models\PharmaHeadquarter::with(['exstations', 'outstations'])->find($headquarterId);
            
            if (!$hq) {
                return Reply::dataOnly(['status' => 'error', 'message' => 'Headquarter not found']);
            }
            
            $exstationIds = $hq->exstations->pluck('id')->toArray();
            $outstationIds = $hq->outstations->pluck('id')->toArray();
            
            $stockists = \App\Models\Stockist::where(function($q) use ($headquarterId, $exstationIds, $outstationIds) {
                $q->where('headquarter_id', $headquarterId);
                if (!empty($exstationIds)) {
                    $q->orWhereIn('exstation_id', $exstationIds);
                }
                if (!empty($outstationIds)) {
                    $q->orWhereIn('outstation_id', $outstationIds);
                }
            })->orderBy('shopname')->get(['id', 'shopname', 'fullname']);
            
            return Reply::dataOnly([
                'status' => 'success',
                'stockists' => $stockists->map(function($stockist) {
                    return [
                        'id' => $stockist->id,
                        'name' => $stockist->shopname . ($stockist->fullname ? ' (' . $stockist->fullname . ')' : '')
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return Reply::error($e->getMessage());
        }
    }
}
