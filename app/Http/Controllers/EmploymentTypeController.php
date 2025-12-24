<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Models\EmploymentType;
use Illuminate\Http\Request;
use App\DataTables\EmploymentTypeDataTable;

class EmploymentTypeController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Employment Types';
        $this->activeSettingMenu = 'employment_types';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array('employees', $this->user->modules));
            return $next($request);
        });
    }

    public function index(EmploymentTypeDataTable $dataTable)
    {
        $viewPermission = user()->permission('view_designation'); // Using designation permission
        abort_403(!in_array($viewPermission, ['all']));

        return $dataTable->render('employment-type.index', $this->data);
    }

    public function create()
    {
        $this->view = 'employment-type.ajax.create';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('employment-type.create', $this->data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $slug = \Str::slug($request->name, '_');

        $employmentType = new EmploymentType();
        $employmentType->company_id = company()->id;
        $employmentType->name = $request->name;
        $employmentType->slug = $slug;
        $employmentType->requires_end_date = $request->has('requires_end_date') ? true : false;
        $employmentType->is_active = true;
        $employmentType->save();

        return Reply::successWithData(__('messages.recordSaved'), ['redirectUrl' => route('employment-types.index')]);
    }

    public function edit($id)
    {
        $this->employmentType = EmploymentType::findOrFail($id);
        $this->view = 'employment-type.ajax.edit';

        if (request()->ajax()) {
            return $this->returnAjax($this->view);
        }

        return view('employment-type.create', $this->data);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $employmentType = EmploymentType::findOrFail($id);
        $slug = \Str::slug($request->name, '_');

        $employmentType->name = $request->name;
        $employmentType->slug = $slug;
        $employmentType->requires_end_date = $request->has('requires_end_date') ? true : false;
        $employmentType->is_active = $request->has('is_active') ? true : false;
        $employmentType->save();

        return Reply::successWithData(__('messages.updateSuccess'), ['redirectUrl' => route('employment-types.index')]);
    }

    public function destroy($id)
    {
        $employmentType = EmploymentType::findOrFail($id);
        $employmentType->delete();

        return Reply::success(__('messages.deleteSuccess'));
    }
}
