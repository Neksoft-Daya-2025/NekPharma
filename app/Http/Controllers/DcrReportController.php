<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Models\DcrReport;
use App\Models\Doctor;
use App\Models\Chemist;
use App\Models\Stockist;
use App\Models\Product;
use App\Models\PharmaHeadquarter;
use Illuminate\Http\Request;

class DcrReportController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'DCR Reports';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('dcr_reports', $this->user->modules));
            return $next($request);
        });
    }

    public function index()
    {
        $this->viewPermission = user()->permission('view_dcr_reports');
        abort_403(!in_array($this->viewPermission, ['all', 'added', 'owned', 'both']));

        $this->reports = DcrReport::with(['user', 'doctor', 'chemist', 'stockist', 'doctorVisits.doctor', 'chemistVisits.chemist', 'stockistVisits.stockist'])
            ->orderBy('report_date', 'desc')
            ->get();

        return view('dcr-reports.index', $this->data);
    }

    public function create()
    {
        $this->addPermission = user()->permission('add_dcr_reports');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        // Load with area, headquarter, and station relationships
        $this->doctors = Doctor::with(['area', 'headquarter', 'exstation', 'outstation'])->get();
        $this->chemists = Chemist::with(['area', 'headquarter', 'exstation', 'outstation'])->get();
        $this->stockists = Stockist::with(['area', 'headquarter', 'exstation', 'outstation'])->get();
        // Load products from Worksuite purchase-products (active products only)
        $this->products = Product::where('company_id', company()->id)
            ->orderBy('name', 'asc')
            ->get();
        $this->headquarters = PharmaHeadquarter::with(['exstations', 'outstations'])->get();
        
        // Find the last pending date (approved tour without DCR submission)
        $lastPendingDate = $this->findLastPendingDate();
        $this->reportDate = $lastPendingDate;
        
        // Get user's headquarters - check both possible field names
        $this->userHeadquarter = user()->employeeDetails->headquarter_id 
            ?? user()->employeeDetails->pharma_headquarter_id 
            ?? null;

        if (request()->ajax()) {
            $html = view('dcr-reports.ajax.create', $this->data)->render();
            return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle]);
        }

        return view('dcr-reports.create', $this->data);
    }
    
    private function findLastPendingDate()
    {
        $userId = user()->id;
        
        // Get the start of current month
        $startOfMonth = now()->startOfMonth()->format('Y-m-d');
        $today = now()->format('Y-m-d');
        
        // Get all approved tour dates for the user (from start of month to today)
        $approvedTours = \App\Models\Tour::where('user_id', $userId)
            ->where(function($query) {
                $query->where('status', 'approved')
                      ->orWhere('approved', 1);
            })
            ->where('date', '>=', $startOfMonth)
            ->where('date', '<=', $today)
            ->orderBy('date', 'asc')
            ->pluck('date')
            ->map(function($date) {
                return \Carbon\Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();
        
        // Get dates that already have DCR reports
        $submittedDates = DcrReport::where('user_id', $userId)
            ->where('report_date', '>=', $startOfMonth)
            ->where('report_date', '<=', $today)
            ->pluck('report_date')
            ->map(function($date) {
                return \Carbon\Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();
        
        // Find pending dates (approved tours without DCR)
        $pendingDates = array_diff($approvedTours, $submittedDates);
        
        // Return the FIRST (oldest) pending date chronologically, or today's date if none found
        if (!empty($pendingDates)) {
            // Sort ascending to get the oldest/first pending date
            sort($pendingDates);
            return $pendingDates[0];
        }
        
        // If no pending dates, return today's date
        return $today;
    }
    
    public function getTourByDate(Request $request)
    {
        $date = $request->date;
        $userId = $request->user_id ?? user()->id;
        
        // Log for debugging
        \Log::info('getTourByDate called', [
            'date' => $date,
            'userId' => $userId
        ]);
        
        // Try to find tour with status OR approved field
        $tour = \App\Models\Tour::with(['headquarter', 'submittedTo', 'approvedBy'])
            ->where('user_id', $userId)
            ->where('date', $date)
            ->where(function($query) {
                $query->where('status', 'approved')
                      ->orWhere('approved', 1);
            })
            ->first();
        
        // Log what we found
        \Log::info('Tour search result', [
            'found' => $tour ? true : false,
            'tour_id' => $tour ? $tour->id : null,
            'tour_status' => $tour ? $tour->status : null,
            'tour_approved' => $tour ? $tour->approved : null
        ]);
        
        if ($tour) {
            $dateFormat = companyOrGlobalSetting()->date_format;
            $timeFormat = companyOrGlobalSetting()->time_format;
            
            return Reply::dataOnly([
                'status' => 'success',
                'tour' => [
                    'id' => $tour->id,
                    'date' => $tour->date->translatedFormat($dateFormat),
                    'day' => $tour->day,
                    'headquarter' => $tour->headquarter->name ?? '-',
                    'station' => $tour->station,
                    'work_status' => $tour->work_status,
                    'work_with' => $tour->work_with,
                    'remark' => $tour->remark,
                    'approved_by' => $tour->approvedBy->name ?? '-',
                    'approved_at' => $tour->approved_at ? $tour->approved_at->translatedFormat($dateFormat . ' ' . $timeFormat) : '-',
                ]
            ]);
        }
        
        return Reply::dataOnly([
            'status' => 'error',
            'message' => 'No approved tour found for this date'
        ]);
    }

    public function store(Request $request)
    {
        $this->addPermission = user()->permission('add_dcr_reports');
        abort_403(!in_array($this->addPermission, ['all', 'added']));

        $request->validate([
            'report_date' => 'required|date',
            'work_status' => 'required|string',
            'headquarter_id' => 'required|exists:pharma_headquarters,id',
        ]);
        
        // If not Field Work, validate that remark is provided
        if ($request->work_status !== 'Field Work') {
            $request->validate([
                'remark' => 'required|string',
            ]);
        }
        
        // Get headquarter name
        $headquarter = PharmaHeadquarter::find($request->headquarter_id);
        $headquarterName = $headquarter ? $headquarter->name : null;
        
        // Determine headquarter from station if station is a HQ name
        if ($request->station) {
            $hqByName = PharmaHeadquarter::where('name', $request->station)->first();
            if ($hqByName) {
                $headquarterName = $hqByName->name;
            }
        }
        
        // HRM Single-Row Structure - One DCR record per date
        // Station is now single select
        $stationString = $request->station; // Single value now
        $workStatusString = $request->work_status; // Single select value
        $workWithString = is_array($request->work_with) ? implode(',', $request->work_with) : $request->work_with;
        
        // Check if Field Work is selected (shows doctor/chemist/stockist sections)
        $isFieldWork = $workStatusString === 'Field Work';
        
        // Base data for all DCR types
        $dcrData = [
            'company_id' => company()->id,
            'user_id' => user()->id,
            'report_date' => $request->report_date,
            'headquarter' => $headquarterName,
            'station' => $stationString,
            'work_status' => $workStatusString,
            'work_with' => $workWithString,
            'remark' => $request->remark, // For non-field work
        ];
        
        // Create the main DCR report
        $dcr = DcrReport::create($dcrData);
        
        // If Field Work or Working Days, process multiple doctor/chemist/stockist visits
        if ($isFieldWork) {
            // Process Doctor Visits
            if ($request->has('doctors') && is_array($request->doctors)) {
                foreach ($request->doctors as $doctorData) {
                    if (!empty($doctorData['doctor_id']) || !empty($doctorData['speciality'])) {
                        \App\Models\DcrDoctorVisit::create([
                            'dcr_report_id' => $dcr->id,
                            'doctor_id' => $doctorData['doctor_id'] ?? null,
                            'speciality' => $doctorData['speciality'] ?? null,
                            'area' => $doctorData['area'] ?? null,
                            'msl' => $doctorData['msl'] ?? 0,
                            'product1' => $doctorData['product1'] ?? null,
                            'samples_unit1' => $doctorData['samples_unit1'] ?? 0,
                            'pob1' => $doctorData['pob1'] ?? 0,
                            'remark1' => $doctorData['remark1'] ?? null,
                            'product2' => $doctorData['product2'] ?? null,
                            'samples_unit2' => $doctorData['samples_unit2'] ?? 0,
                            'pob2' => $doctorData['pob2'] ?? 0,
                            'remark2' => $doctorData['remark2'] ?? null,
                            'product3' => $doctorData['product3'] ?? null,
                            'samples_unit3' => $doctorData['samples_unit3'] ?? 0,
                            'pob3' => $doctorData['pob3'] ?? 0,
                            'remark3' => $doctorData['remark3'] ?? null,
                            'general_remark' => $doctorData['general_remark'] ?? null,
                        ]);
                    }
                }
            }
            
            // Process Chemist Visits
            if ($request->has('chemists') && is_array($request->chemists)) {
                foreach ($request->chemists as $chemistData) {
                    if (!empty($chemistData['chemist_id']) || !empty($chemistData['area'])) {
                        \App\Models\DcrChemistVisit::create([
                            'dcr_report_id' => $dcr->id,
                            'chemist_id' => $chemistData['chemist_id'] ?? null,
                            'area' => $chemistData['area'] ?? null,
                            'station' => $chemistData['station'] ?? null,
                            'msl' => $chemistData['msl'] ?? 0,
                            'rcpa1' => $chemistData['rcpa1'] ?? null,
                            'pob_amount1' => $chemistData['pob_amount1'] ?? 0,
                            'remark1' => $chemistData['remark1'] ?? null,
                            'rcpa2' => $chemistData['rcpa2'] ?? null,
                            'pob_amount2' => $chemistData['pob_amount2'] ?? 0,
                            'remark2' => $chemistData['remark2'] ?? null,
                            'rcpa3' => $chemistData['rcpa3'] ?? null,
                            'pob_amount3' => $chemistData['pob_amount3'] ?? 0,
                            'remark3' => $chemistData['remark3'] ?? null,
                            'rcpa4' => $chemistData['rcpa4'] ?? null,
                            'pob_amount4' => $chemistData['pob_amount4'] ?? 0,
                            'remark4' => $chemistData['remark4'] ?? null,
                            'general_remark' => $chemistData['general_remark'] ?? null,
                        ]);
                    }
                }
            }
            
            // Process Stockist Visits
            if ($request->has('stockists') && is_array($request->stockists)) {
                foreach ($request->stockists as $stockistData) {
                    if (!empty($stockistData['stockist_id']) || !empty($stockistData['area'])) {
                        \App\Models\DcrStockistVisit::create([
                            'dcr_report_id' => $dcr->id,
                            'stockist_id' => $stockistData['stockist_id'] ?? null,
                            'area' => $stockistData['area'] ?? null,
                            'station' => $stockistData['station'] ?? null,
                            'msl' => $stockistData['msl'] ?? 0,
                            'pob' => $stockistData['pob'] ?? null,
                            'pob_amount' => $stockistData['pob_amount'] ?? 0,
                            'proprietor' => $stockistData['proprietor'] ?? null,
                            'proprietor_mobile' => $stockistData['proprietor_mobile'] ?? null,
                            'general_remark' => $stockistData['general_remark'] ?? null,
                        ]);
                    }
                }
            }
        }

        return Reply::successWithData(__('DCR Report saved successfully'), ['redirectUrl' => route('dcr-reports.index')]);
    }

    public function destroy($id)
    {
        $report = DcrReport::findOrFail($id);
        $this->deletePermission = user()->permission('delete_dcr_reports');
        abort_403(!in_array($this->deletePermission, ['all', 'added', 'owned', 'both']));

        $report->delete();
        return Reply::success(__('messages.deleteSuccess'));
    }
    
    /**
     * AJAX: Create new doctor from DCR form and return the created doctor
     */
    public function createDoctorInline(Request $request)
    {
        $request->validate([
            'fullname' => 'required|string|max:255',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'speciality' => 'nullable|string|max:255',
            'qualification' => 'nullable|string|max:255',
            'headquarter_id' => 'required|exists:pharma_headquarters,id',
        ]);

        $headquarter = PharmaHeadquarter::find($request->headquarter_id);
        
        $doctor = new Doctor();
        $doctor->company_id = company()->id;
        $doctor->fullname = $request->fullname;
        $doctor->email = $request->email;
        $doctor->mobile = $request->mobile;
        $doctor->qualification = $request->qualification;
        $doctor->speciality = $request->speciality;
        $doctor->headquarter_id = $request->headquarter_id;
        $doctor->area_id = $headquarter->area_id;
        $doctor->address = $request->address;
        
        // Handle station type if provided
        if ($request->station_type === 'exstation' && $request->exstation_id) {
            $doctor->exstation_id = $request->exstation_id;
        } elseif ($request->station_type === 'outstation' && $request->outstation_id) {
            $doctor->outstation_id = $request->outstation_id;
        }
        
        $doctor->save();

        return Reply::dataOnly([
            'status' => 'success',
            'message' => 'Doctor created successfully',
            'doctor' => [
                'id' => $doctor->id,
                'fullname' => $doctor->fullname,
                'speciality' => $doctor->speciality,
                'area' => $doctor->area,
                'mobile' => $doctor->mobile,
            ]
        ]);
    }
    
    /**
     * AJAX: Create new chemist from DCR form and return the created chemist
     */
    public function createChemistInline(Request $request)
    {
        $request->validate([
            'shopname' => 'required|string|max:255',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'headquarter_id' => 'required|exists:pharma_headquarters,id',
        ]);

        $headquarter = PharmaHeadquarter::find($request->headquarter_id);
        
        $chemist = new Chemist();
        $chemist->company_id = company()->id;
        $chemist->shopname = $request->shopname;
        $chemist->email = $request->email;
        $chemist->mobile = $request->mobile;
        $chemist->headquarter_id = $request->headquarter_id;
        $chemist->area_id = $headquarter->area_id;
        $chemist->address = $request->address;
        
        // Handle station type if provided
        if ($request->station_type === 'exstation' && $request->exstation_id) {
            $chemist->exstation_id = $request->exstation_id;
        } elseif ($request->station_type === 'outstation' && $request->outstation_id) {
            $chemist->outstation_id = $request->outstation_id;
        }
        
        $chemist->save();

        return Reply::dataOnly([
            'status' => 'success',
            'message' => 'Chemist created successfully',
            'chemist' => [
                'id' => $chemist->id,
                'shopname' => $chemist->shopname,
                'area' => $chemist->area,
                'mobile' => $chemist->mobile,
            ]
        ]);
    }
    
    /**
     * AJAX: Create new stockist from DCR form and return the created stockist
     */
    public function createStockistInline(Request $request)
    {
        $request->validate([
            'shopname' => 'required|string|max:255',
            'owner_mobile' => 'required|string|max:20',
            'headquarter_id' => 'required|exists:pharma_headquarters,id',
        ]);

        $headquarter = PharmaHeadquarter::find($request->headquarter_id);
        
        $stockist = new Stockist();
        $stockist->company_id = company()->id;
        $stockist->shopname = $request->shopname;
        $stockist->owner_name = $request->owner_name;
        $stockist->owner_mobile = $request->owner_mobile;
        $stockist->headquarter_id = $request->headquarter_id;
        $stockist->area_id = $headquarter->area_id;
        $stockist->address = $request->address;
        
        // Handle station type if provided
        if ($request->station_type === 'exstation' && $request->exstation_id) {
            $stockist->exstation_id = $request->exstation_id;
        } elseif ($request->station_type === 'outstation' && $request->outstation_id) {
            $stockist->outstation_id = $request->outstation_id;
        }
        
        $stockist->save();

        return Reply::dataOnly([
            'status' => 'success',
            'message' => 'Stockist created successfully',
            'stockist' => [
                'id' => $stockist->id,
                'shopname' => $stockist->shopname,
                'area' => $stockist->area,
                'owner_mobile' => $stockist->owner_mobile,
            ]
        ]);
    }
}
