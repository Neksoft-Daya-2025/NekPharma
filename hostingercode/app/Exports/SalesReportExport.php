<?php

namespace App\Exports;

use App\Services\SalesReportService;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesReportExport implements FromCollection, WithHeadings, WithMapping
{
    use Exportable;

    protected array $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        return SalesReportService::getReportRows($this->filters);
    }

    public function getCollection()
    {
        return $this->collection();
    }

    public function headings(): array
    {
        return [
            __('app.date'),
            __('app.invoiceNumber'),
            'Invoice Type',
            __('app.clientName'),
            'Stockist',
            __('modules.invoices.invoiceValue'),
            __('modules.invoices.amountPaid'),
            __('modules.invoices.taxableValue'),
            __('modules.invoices.discount'),
        ];
    }

    public function map($row): array
    {
        $company = company();
        $issueDate = $row->issue_date instanceof Carbon ? $row->issue_date->format($company->date_format) : Carbon::parse($row->issue_date)->format($company->date_format);

        $invoiceType = '--';
        if ($row->cfaDistributorStocks && $row->cfaDistributorStocks->isNotEmpty()) {
            $invoiceType = 'Company→CFA';
        } elseif ($row->cfaStockistStocks && $row->cfaStockistStocks->isNotEmpty()) {
            $invoiceType = 'CFA→Stockist';
        }

        $stockistName = '--';
        $stock = $row->cfaStockistStocks->first();
        if ($stock && $stock->cfaStockist) {
            $stockistName = $stock->cfaStockist->shopname ?? $stock->cfaStockist->fullname ?? '--';
        }

        return [
            $issueDate,
            $row->custom_invoice_number ?? '--',
            $invoiceType,
            $row->client ? $row->client->name : '--',
            $stockistName,
            $row->total ? currency_format($row->total, $row->currency_id) : '--',
            currency_format($row->amountPaid(), $row->currency_id),
            currency_format($row->sub_total, $row->currency_id),
            $row->discount > 0 ? currency_format($row->discount_type == 'percent' ? (($row->discount / 100) * $row->sub_total) : $row->discount, $row->currency_id) : 0,
        ];
    }
}
