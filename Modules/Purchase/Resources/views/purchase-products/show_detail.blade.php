@extends('layouts.app')

@section('content')
<div class="content-wrapper border-top-0">
    <div class="row">
        <div class="col-lg-12">
            <div class="card bg-white border-0 b-shadow-4">
                <div class="card-header bg-white border-0 text-capitalize d-flex justify-content-between p-20">
                    <h4 class="f-18 f-w-500 mb-0">{{ $pageTitle }}</h4>
                    <div>
                        <a href="{{ route('purchase-entries.edit', $purchaseDetail->id) }}" class="btn btn-secondary btn-sm mr-2">
                            <i class="fa fa-edit mr-1"></i> @lang('app.edit')
                        </a>
                        <a href="{{ route('purchase-entries.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa fa-arrow-left mr-1"></i> @lang('app.back')
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <x-cards.data :title="__('Product Information')">
                                <div class="table-responsive">
                                    <x-table class="table-bordered">
                                        <tbody>
                                            <tr>
                                                <x-cards.data-row :label="__('Product Name')" :value="$purchaseDetail->product->name ?? '--'" />
                                            </tr>
                                            <tr>
                                                <x-cards.data-row :label="__('HSN/SAC Code')" :value="$purchaseDetail->product->hsn_sac_code ?? $purchaseDetail->product->sku ?? '--'" />
                                            </tr>
                                            <tr>
                                                <x-cards.data-row :label="__('SKU')" :value="$purchaseDetail->product->sku ?? '--'" />
                                            </tr>
                                            <tr>
                                                <x-cards.data-row :label="__('Packing')" :value="$purchaseDetail->product->packing ?? '--'" />
                                            </tr>
                                            @if($purchaseDetail->product && $purchaseDetail->product->category)
                                            <tr>
                                                <x-cards.data-row :label="__('Category')" :value="$purchaseDetail->product->category->category_name ?? '--'" />
                                            </tr>
                                            @endif
                                            @if($purchaseDetail->product && $purchaseDetail->product->subCategory)
                                            <tr>
                                                <x-cards.data-row :label="__('Sub Category')" :value="$purchaseDetail->product->subCategory->category_name ?? '--'" />
                                            </tr>
                                            @endif
                                            @if($purchaseDetail->product && $purchaseDetail->product->unit)
                                            <tr>
                                                <x-cards.data-row :label="__('Unit')" :value="$purchaseDetail->product->unit->unit_type ?? '--'" />
                                            </tr>
                                            @endif
                                        </tbody>
                                    </x-table>
                                </div>
                            </x-cards.data>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <x-cards.data :title="__('Purchase Entry Details')">
                                <div class="table-responsive">
                                    <x-table class="table-bordered">
                                        <tbody>
                                            @if($purchaseDetail->vendor)
                                            <tr>
                                                <x-cards.data-row :label="__('Vendor')" :value="$purchaseDetail->vendor->primary_name . ($purchaseDetail->vendor->company_name ? ' - ' . $purchaseDetail->vendor->company_name : '')" />
                                            </tr>
                                            @endif
                                            @if($purchaseDetail->scheme_enabled)
                                            <tr>
                                                <x-cards.data-row :label="__('Scheme Enabled')" :value="__('app.yes')" />
                                            </tr>
                                            @endif
                                            @if($purchaseDetail->total_quantity || $purchaseDetail->free_quantity)
                                            <tr>
                                                <x-cards.data-row :label="__('Total Quantity')" :value="$purchaseDetail->total_quantity ?? '--'" />
                                            </tr>
                                            <tr>
                                                <x-cards.data-row :label="__('Free Quantity')" :value="$purchaseDetail->free_quantity ?? '--'" />
                                            </tr>
                                            @endif
                                            <tr>
                                                <x-cards.data-row :label="__('Final Quantity')" :value="$purchaseDetail->quantity ?? '--'" />
                                            </tr>
                                            @if($purchaseDetail->unit_id && $purchaseDetail->product && $purchaseDetail->product->unit)
                                            <tr>
                                                <x-cards.data-row :label="__('Unit')" :value="$purchaseDetail->product->unit->unit_type ?? '--'" />
                                            </tr>
                                            @endif
                                            <tr>
                                                <x-cards.data-row :label="__('Batch Number')" :value="$purchaseDetail->batch ?? '--'" />
                                            </tr>
                                            <tr>
                                                <x-cards.data-row :label="__('Expiry Date')" :value="$purchaseDetail->expiry ? \Carbon\Carbon::parse($purchaseDetail->expiry)->format(company()->date_format) : '--'" />
                                            </tr>
                                            <tr>
                                                <x-cards.data-row :label="__('PTS (Price to Stockist)')" :value="$purchaseDetail->pts ? currency_format($purchaseDetail->pts) : '--'" />
                                            </tr>
                                            <tr>
                                                <x-cards.data-row :label="__('PTR (Price to Retailer)')" :value="$purchaseDetail->ptr ? currency_format($purchaseDetail->ptr) : '--'" />
                                            </tr>
                                            <tr>
                                                <x-cards.data-row :label="__('DIS %')" :value="$purchaseDetail->dis ? number_format($purchaseDetail->dis, 2) . '%' : '--'" />
                                            </tr>
                                            <tr>
                                                <x-cards.data-row :label="__('MRP')" :value="$purchaseDetail->mrp ? currency_format($purchaseDetail->mrp) : '--'" />
                                            </tr>
                                            <tr>
                                                <x-cards.data-row :label="__('Discount')" :value="$purchaseDetail->discount ? number_format($purchaseDetail->discount, 2) . '%' : '--'" />
                                            </tr>
                                            @if($purchaseDetail->tax && is_array($purchaseDetail->tax))
                                            <tr>
                                                <td class="pl-20 py-3">
                                                    <strong>@lang('Tax')</strong>
                                                </td>
                                                <td class="px-3 py-3">
                                                    @php
                                                        $taxes = \App\Models\Tax::whereIn('id', $purchaseDetail->tax)->get();
                                                    @endphp
                                                    @if($taxes->count() > 0)
                                                        @foreach($taxes as $tax)
                                                            <span class="badge badge-primary">{{ $tax->tax_name }} ({{ $tax->rate_percent }}%)</span>
                                                        @endforeach
                                                    @else
                                                        --
                                                    @endif
                                                </td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <x-cards.data-row :label="__('Subtotal (After Discount)')" :value="$purchaseDetail->total ? currency_format($purchaseDetail->total) : '--'" />
                                            </tr>
                                            @php
                                                // Calculate final amount if tax exists
                                                $finalAmount = $purchaseDetail->total ?? 0;
                                                if ($purchaseDetail->tax && is_array($purchaseDetail->tax)) {
                                                    $taxes = \App\Models\Tax::whereIn('id', $purchaseDetail->tax)->get();
                                                    $taxAmount = 0;
                                                    foreach ($taxes as $tax) {
                                                        $taxAmount += ($purchaseDetail->total * $tax->rate_percent / 100);
                                                    }
                                                    $finalAmount = $purchaseDetail->total + $taxAmount;
                                                }
                                            @endphp
                                            @if($purchaseDetail->tax && is_array($purchaseDetail->tax) && count($purchaseDetail->tax) > 0)
                                            <tr>
                                                <td class="pl-20 py-3">
                                                    <strong>@lang('Final Amount (After Tax)')</strong>
                                                </td>
                                                <td class="px-3 py-3">
                                                    <strong class="text-primary">{{ currency_format($finalAmount) }}</strong>
                                                </td>
                                            </tr>
                                            @endif
                                            @if($purchaseDetail->description)
                                            <tr>
                                                <x-cards.data-row :label="__('Description')" :value="$purchaseDetail->description" />
                                            </tr>
                                            @endif
                                        </tbody>
                                    </x-table>
                                </div>
                            </x-cards.data>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <x-cards.data :title="__('Additional Information')">
                                <div class="table-responsive">
                                    <x-table class="table-bordered">
                                        <tbody>
                                            @if($purchaseDetail->created_by)
                                            <tr>
                                                @php
                                                    $createdBy = \App\Models\User::find($purchaseDetail->created_by);
                                                @endphp
                                                <x-cards.data-row :label="__('Created By')" :value="$createdBy ? $createdBy->name : '--'" />
                                            </tr>
                                            @endif
                                            <tr>
                                                <x-cards.data-row :label="__('Created At')" :value="$purchaseDetail->created_at ? $purchaseDetail->created_at->format(company()->date_format . ' ' . company()->time_format) : '--'" />
                                            </tr>
                                            <tr>
                                                <x-cards.data-row :label="__('Updated At')" :value="$purchaseDetail->updated_at ? $purchaseDetail->updated_at->format(company()->date_format . ' ' . company()->time_format) : '--'" />
                                            </tr>
                                        </tbody>
                                    </x-table>
                                </div>
                            </x-cards.data>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

