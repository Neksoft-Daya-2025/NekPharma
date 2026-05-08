<?php

namespace App\DataTables;

use App\Helper\AccessibleHeadquartersHelper;
use App\Helpers\PharmaDesignationHelper;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tax;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesReportDataTable extends BaseDataTable
{
    public function dataTable($query) // phpcs:ignore
    {
        $taxes = Tax::all();

        $datatable = datatables()
            ->eloquent($query);

        $datatable->addColumn('issue_date', function ($row) {
            return $row->issue_date ? Carbon::parse($row->issue_date)->format($this->company->date_format) : '--';
        });
        $datatable->addColumn('invoice_number', function ($row) {
            return $row->custom_invoice_number ?: '--';
        });
        $datatable->addColumn('invoice_type', function ($row) {
            if ($row->cfaDistributorStocks && $row->cfaDistributorStocks->isNotEmpty()) {
                return 'Company→CFA';
            }
            if ($row->cfaStockistStocks && $row->cfaStockistStocks->isNotEmpty()) {
                return 'CFA→Stockist';
            }
            return '--';
        });
        $datatable->addColumn('client_name', function ($row) {
            return $row->client ? $row->client->name : '--';
        });
        $datatable->addColumn('stockist_name', function ($row) {
            $stock = $row->cfaStockistStocks->first();
            return $stock && $stock->cfaStockist ? ($stock->cfaStockist->shopname ?? $stock->cfaStockist->fullname ?? '--') : '--';
        });
        $datatable->addColumn('invoice_value', function ($row) {
            return $row->total ? currency_format($row->total, $row->currency_id) : '--';
        });
        $datatable->addColumn('amount_paid', function ($row) {
            return currency_format($row->amountPaid(), $row->currency_id);
        });
        $datatable->addColumn('taxable_value', function ($row) {
            if ($row->calculate_tax == 'after_discount') {
                if ($row->discount_type == 'percent') {
                    $discountAmount = (($row->sub_total / 100) * $row->discount);
                    $discountedAmount = ($row->sub_total - $discountAmount);
                } else {
                    $discountedAmount = ($row->sub_total - $row->discount);
                }
                return currency_format($discountedAmount, $row->currency_id);
            }
            return currency_format($row->sub_total, $row->currency_id);
        });
        $datatable->addColumn('discount', function ($row) {
            if ($row->discount > 0) {
                if ($row->discount_type == 'percent') {
                    $discountAmount = (($row->sub_total / 100) * $row->discount);
                } else {
                    $discountAmount = $row->discount;
                }
                return currency_format($discountAmount, $row->currency_id);
            }
            return 0;
        });

        foreach ($taxes as $taxName) {
            $datatable->addColumn($taxName['tax_name'], function ($row) use ($taxName, $taxes) {
                $taxList = [];
                $discount = 0;
                if ($row->discount > 0) {
                    $discount = $row->discount_type == 'percent' ? (($row->discount / 100) * $row->sub_total) : $row->discount;
                }
                foreach ($row->items as $item) {
                    if (!is_null($item->taxes)) {
                        foreach (json_decode($item->taxes) as $taxId) {
                            $taxValue = $taxes->filter(fn($v) => $v->id == $taxId)->first();
                            if ($taxValue && $taxName['tax_name'] == $taxValue->tax_name) {
                                $key = $taxValue->tax_name . ': ' . $taxValue->rate_percent . '%';
                                $amount = $row->calculate_tax == 'after_discount' && $discount > 0
                                    ? ($item->amount - ($item->amount / $row->sub_total) * $discount) * (floatval($taxValue->rate_percent) / 100)
                                    : $item->amount * (floatval($taxValue->rate_percent) / 100);
                                $taxList[$key] = ($taxList[$key] ?? 0) + $amount;
                            }
                        }
                    }
                }
                return !empty($taxList) ? currency_format(array_sum($taxList), $row->currency_id) : 0;
            });
        }

        $datatable->addColumn('bank_account', function ($row) {
            $payment = $row->payment()->first();
            return $payment && $payment->bankAccount ? $payment->bankAccount->bank_name : '--';
        });

        $datatable->addIndexColumn()
            ->setRowId(fn($row) => 'row-' . $row->id);
        $rawColumns = array_merge($taxes->pluck('tax_name')->toArray(), ['client_name', 'issue_date', 'invoice_type', 'stockist_name']);
        $datatable->orderColumn('client_name', 'client_id $1');
        $datatable->orderColumn('issue_date', 'issue_date $1');
        $datatable->orderColumn('invoice_value', 'total $1');
        $datatable->orderColumn('amount_paid', 'total $1');
        $datatable->orderColumn('taxable_value', 'sub_total $1');
        $datatable->orderColumn('discount', 'discount $1');
        $datatable->orderColumn('invoice_number', 'custom_invoice_number $1');
        $datatable->rawColumns($rawColumns);

        $baseQuery = clone $query;
        $totalValue = (clone $baseQuery)->sum('invoices.total');
        $invoiceIds = (clone $baseQuery)->pluck('invoices.id');
        $totalPaid = Payment::whereIn('invoice_id', $invoiceIds)->where('status', 'complete')->sum('amount');
        $currencyId = $this->company->currency_id ?? null;
        $datatable->with('summary', [
            'total_count' => (clone $baseQuery)->count(),
            'total_value' => currency_format($totalValue, $currencyId),
            'total_paid' => currency_format($totalPaid, $currencyId),
        ]);

        return $datatable;
    }

    public function query(Invoice $model)
    {
        $request = $this->request();

        $model = $model->with(['items', 'client.clientDetails.headquarters', 'client.clientDetails.areas', 'cfaDistributorStocks', 'cfaStockistStocks.cfaStockist', 'payment.bankAccount'])
            ->where('invoices.company_id', company()->id)
            ->where('invoices.credit_note', 0)
            ->whereNotIn('invoices.status', ['canceled', 'draft']);

        $accessibleHqIds = AccessibleHeadquartersHelper::getAccessibleHeadquarterIds();
        if ($accessibleHqIds !== null && empty($accessibleHqIds)) {
            return $model->where('invoices.id', 0);
        }

        $invoiceType = $request->invoiceType ?? '';
        if ($invoiceType === 'company_cfa') {
            $model->whereHas('cfaDistributorStocks');
        } elseif ($invoiceType === 'cfa_stockist') {
            $model->whereHas('cfaStockistStocks');
        } else {
            $model->where(function ($q) {
                $q->whereHas('cfaDistributorStocks')->orWhereHas('cfaStockistStocks');
            });
        }

        $startDate = $request->startDate ?? null;
        $endDate = $request->endDate ?? null;
        if ($startDate && $startDate != 'null' && $startDate != '') {
            $start = companyToDateString($startDate);
            if ($start) {
                $model->whereDate('invoices.issue_date', '>=', $start);
            }
        }
        if ($endDate && $endDate != 'null' && $endDate != '') {
            $end = companyToDateString($endDate);
            if ($end) {
                $model->whereDate('invoices.issue_date', '<=', $end);
            }
        }

        if ($request->clientID != 'all' && !empty($request->clientID)) {
            $model->where('invoices.client_id', $request->clientID);
        }

        if ($accessibleHqIds !== null && !empty($accessibleHqIds)) {
            $model->whereHas('client.clientDetails.headquarters', function ($q) use ($accessibleHqIds) {
                $q->whereIn('pharma_headquarters.id', $accessibleHqIds);
            });
        }

        if (!empty($request->headquarter)) {
            $model->whereHas('client.clientDetails.headquarters', function ($q) use ($request) {
                $q->where('pharma_headquarters.id', $request->headquarter);
            });
        }

        if (!empty($request->area)) {
            $model->whereHas('client.clientDetails.areas', function ($q) use ($request) {
                $q->where('pharma_areas.id', $request->area);
            });
        }

        if (!empty($request->region)) {
            $model->whereHas('client.clientDetails', function ($q) use ($request) {
                $q->where('region_id', $request->region);
            });
        }

        if (!empty($request->stockist)) {
            $model->whereHas('cfaStockistStocks', function ($q) use ($request) {
                $q->where('cfa_stockist_id', $request->stockist);
            });
        }

        if (!empty($request->product)) {
            $model->whereHas('items', function ($q) use ($request) {
                $q->where('product_id', $request->product);
            });
        }

        if (in_array('client', user_roles())) {
            $model->where('invoices.client_id', user()->id);
        } elseif (!PharmaDesignationHelper::hasFullCFAAccess()) {
            $viewCfaDist = user()->permission('view_cfa_distributor_invoices');
            $viewCfaStock = user()->permission('view_cfa_stockist_invoices');
            if ($viewCfaDist === 'owned' || $viewCfaStock === 'owned') {
                $model->where('invoices.client_id', user()->id);
            } elseif ($viewCfaDist === 'added' || $viewCfaStock === 'added') {
                $model->where('invoices.added_by', user()->id);
            }
        }

        return $model->select('invoices.*');
    }

    public function html()
    {
        return $this->setBuilder('sales-report-table', 2)
            ->parameters([
                'fnDrawCallback' => 'function( oSettings ) {
                    $(".select-picker").selectpicker();
                }',
            ]);
    }

    protected function getColumns()
    {
        $columns = Tax::all();
        $newColumns = [];

        $newColumns['#'] = ['data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'visible' => false, 'title' => '#'];
        $newColumns[__('app.id')] = ['data' => 'id', 'name' => 'id', 'visible' => false, 'exportable' => false, 'title' => __('app.id')];
        $newColumns[__('app.date')] = ['data' => 'issue_date', 'name' => 'issue_date', 'title' => __('app.date')];
        $newColumns[__('app.invoiceNumber')] = ['data' => 'invoice_number', 'name' => 'invoice_number', 'title' => __('app.invoiceNumber')];
        $newColumns['Invoice Type'] = ['data' => 'invoice_type', 'name' => 'invoice_type', 'orderable' => false, 'title' => 'Invoice Type'];
        $newColumns[__('app.clientName')] = ['data' => 'client_name', 'name' => 'client_name', 'title' => __('app.clientName')];
        $newColumns['Stockist'] = ['data' => 'stockist_name', 'name' => 'stockist_name', 'orderable' => false, 'title' => 'Stockist'];
        $newColumns[__('modules.invoices.invoiceValue')] = ['data' => 'invoice_value', 'name' => 'invoice_value', 'title' => __('modules.invoices.invoiceValue')];
        $newColumns[__('modules.invoices.amountPaid')] = ['data' => 'amount_paid', 'name' => 'amount_paid', 'title' => __('modules.invoices.amountPaid')];
        $newColumns[__('modules.invoices.taxableValue')] = ['data' => 'taxable_value', 'name' => 'taxable_value', 'title' => __('modules.invoices.taxableValue')];
        $newColumns[__('modules.invoices.discount')] = ['data' => 'discount', 'name' => 'discount', 'title' => __('modules.invoices.discount')];

        foreach ($columns as $column) {
            $newColumns[$column->tax_name] = ['data' => $column->tax_name, 'name' => $column->tax_name, 'orderable' => false, 'searchable' => false, 'visible' => true];
        }

        $newColumns[__('app.bankaccount')] = ['data' => 'bank_account', 'name' => 'bank_account', 'orderable' => false, 'title' => __('app.bankaccount')];

        return $newColumns;
    }
}
