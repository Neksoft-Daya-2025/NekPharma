<?php

namespace Modules\Purchase\Http\Controllers;

use App\Http\Controllers\AccountBaseController;
use Modules\Purchase\DataTables\PurchaseOrderReportDataTable;
use Modules\Purchase\Entities\PurchaseSetting;
use Modules\Purchase\Entities\PurchaseVendor;

class PurchaseOrderReportController extends AccountBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'Purchase Order Report';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array(PurchaseSetting::MODULE_NAME, $this->user->modules));
            return $next($request);
        });
    }

    public function index()
    {
        $viewPermission = user()->permission('view_order_report');
        abort_403(!in_array($viewPermission, ['all']));

        $this->vendors = PurchaseVendor::all();
        $dataTable = new PurchaseOrderReportDataTable();

        return $dataTable->render('purchase::reports.index', $this->data);
    }

    public function create()
    {
        abort_404();
    }

    public function store()
    {
        abort_404();
    }

    public function show($id)
    {
        abort_404();
    }

    public function edit($id)
    {
        abort_404();
    }

    public function update($id)
    {
        abort_404();
    }

    public function destroy($id)
    {
        abort_404();
    }
}

