@php
$addProductPermission = user()->permission('add_product');
@endphp

<link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}">

<!-- for sortable content -->
<link rel="stylesheet" href="{{ asset('vendor/css/jquery-ui.css') }}">
<style>
    /* Batch buttons styling */
    .batch-btn {
        margin: 4px;
        padding: 8px 16px;
        border-radius: 6px;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .batch-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .batch-btn:active {
        transform: translateY(0);
    }
    
    #batch-buttons-container {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 6px;
        border: 1px solid #dee2e6;
    }
    
    .gap-2 {
        gap: 0.5rem;
    }
/* Invoice item table layout fix */
.c-inv-desc-table table {
    table-layout: fixed;
}

/* Header + cells */
.c-inv-desc-table th,
.c-inv-desc-table td {
    vertical-align: middle;
    padding: 6px 8px !important;
}

/* Description */
.inv-col-desc {
    width: 22%;
}

/* Compact text columns */
.inv-col-scheme,
.inv-col-pack,
.inv-col-mfr,
.inv-col-batch {
    width: 6%;
}

/* Date */
.inv-col-exp {
    width: 8%;
}

/* Price columns */
.inv-col-mrp,
.inv-col-pts,
.inv-col-ptr,
.inv-col-dis {
    width: 6%;
    text-align: right;
}

/* Quantity */
.inv-col-qty {
    width: 6%;
}

/* Unit price */
.inv-col-unit {
    width: 7%;
}

/* Tax */
.inv-col-tax {
    width: 12%;
}

/* Amount */
.inv-col-amount {
    width: 10%;
    background: #f5f6f7;
}

/* Inputs compact look */
.c-inv-desc-table input,
.c-inv-desc-table select {
    min-width: 100%;
    height: 32px;
    font-size: 13px;
}
</style>

</style>

@if ((!in_array('client', user_roles()) && !in_array('clients', user_modules())) || (in_array('client', user_roles()) && !in_array('invoices', user_modules())))
    <x-alert class="mb-3" type="danger" icon="exclamation-circle"><span>@lang('messages.enableClientModule')</span>
        <x-forms.link-secondary icon="arrow-left" :link="route('invoices.index')">@lang('app.back')</x-forms.link-secondary>
    </x-alert>
@else

<!-- CREATE INVOICE START -->
<div class="bg-white rounded b-shadow-4 create-inv">


    <!-- HEADING START -->
    <div class="px-lg-4 px-md-4 px-3 py-3">
        <h4 class="mb-0 f-21 font-weight-normal ">@lang('app.invoiceDetails')</h4>
    </div>
    <!-- HEADING END -->
    <hr class="m-0 border-top-grey">
    <!-- FORM START -->
    <x-form class="c-inv-form" id="saveInvoiceForm">
        @if (isset($type) && $type == 'proposal')
            <input type="hidden" name="proposal_id" value="{{ $proposalId }}">
        @endif
        @if (isset($type) && $type == 'estimate')
            <input type="hidden" name="estimate_id" value="{{ $estimateId }}">
        @endif

        <input type="hidden" name="do_it_later" id="doItLater" value="direct">

        <!-- INVOICE NUMBER, DATE, DUE DATE, FREQUENCY START -->
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <!-- INVOICE NUMBER START -->
            <div class="col-md-3">
                <div class="form-group mb-lg-0 mb-md-0 mb-4">
                    <x-forms.label class="mb-12" fieldId="invoice_number"
                        :fieldLabel="__('modules.invoices.invoiceNumber')" fieldRequired="true">
                    </x-forms.label>
                    <x-forms.input-group>
                        <x-slot name="prepend">
                            <span
                                class="input-group-text">{{ invoice_setting()->invoice_prefix }}{{ invoice_setting()->invoice_number_separator }}{{ $zero }}</span>
                        </x-slot>
                        <input type="number" name="invoice_number" id="invoice_number" class="form-control height-35 f-15"
                            value="{{ is_null($lastInvoice) ? 1 : $lastInvoice }}">
                    </x-forms.input-group>
                </div>
            </div>

            <!-- INVOICE NUMBER END -->
            <!-- INVOICE DATE START -->
            <div class="col-md-2">
                <div class="form-group mb-lg-0 mb-md-0 mb-4">
                    <x-forms.label fieldId="due_date" :fieldLabel="__('modules.invoices.invoiceDate')">
                    </x-forms.label>
                    <div class="input-group">
                        <input type="text" id="invoice_date" name="issue_date"
                            class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15"
                            placeholder="@lang('placeholders.date')"
                            value="{{ now(company()->timezone)->format(company()->date_format) }}">
                    </div>
                </div>
            </div>
            <!-- INVOICE DATE END -->
            <!-- DUE DATE START -->
            <div class="col-md-2">
                <div class="form-group mb-lg-0 mb-md-0 mb-4">
                    <x-forms.label fieldId="due_date" :fieldLabel="__('app.dueDate')"></x-forms.label>
                    <div class="input-group ">
                        <input type="text" id="due_date" name="due_date"
                            class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15"
                            placeholder="@lang('placeholders.date')"
                            value="{{ now(company()->timezone)->addDays($invoiceSetting->due_after)->format(company()->date_format) }}">
                    </div>
                </div>
            </div>
            <!-- DUE DATE END -->
            <!-- FREQUENCY START -->
            <div class="col-md-3">
                <div class="form-group c-inv-select mb-lg-0 mb-md-0 mb-4">
                    <x-forms.label fieldId="currency_id" :fieldLabel="__('modules.invoices.currency')">
                    </x-forms.label>
                    <div class="select-others height-35 rounded">
                        <select class="form-control select-picker" name="currency_id" id="currency_id">
                            @foreach ($currencies as $currency)
                                <option @if (isset($estimate))
                                    @selected($currency->id == $estimate->currency_id)
                                @elseif (isset($invoice))
                                    @selected($currency->id == $invoice->currency_id)
                                @else
                                    @selected($currency->id == company()->currency_id)
                                @endif
                            value="{{ $currency->id }}" data-currency-code="{{$currency->currency_code}}">
                            {{ $currency->currency_code . ' (' . $currency->currency_symbol . ')' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <!-- FREQUENCY END -->
            <div class="col-md-2">
                <x-forms.label fieldId="exchange_rate" :fieldLabel="__('modules.currencySettings.exchangeRate')" fieldRequired="true">
                </x-forms.label>
                <input type="number" id="exchange_rate" name="exchange_rate"
                class="px-6 position-relative text-dark font-weight-normal form-control height-35 rounded p-0 text-left f-15" value="{{ isset($estimateCurrency) ? $estimateCurrency?->exchange_rate : (isset($proposalCurrency) ? $proposalCurrency?->exchange_rate : $companyCurrency->exchange_rate) }}" readonly>
                <small id="currency_exchange" class="form-text text-muted"></small>
            </div>
        </div>
        <!-- INVOICE NUMBER, DATE, DUE DATE, FREQUENCY END -->

        <hr class="m-0 border-top-grey">

        <!-- CLIENT, PROJECT, GST, BILLING, SHIPPING ADDRESS START -->
        <div class="row px-lg-4 px-md-4 px-3 pt-3">

            <!-- CLIENT/STOCKIST START -->
            <div class="col-md-4 mb-4 ">
                @php
                    // Debug information - set to false to disable debug
                    $showDebug = false;
                @endphp
                @if($showDebug)
                    @php
                        $debugInfo = [
                            'isCFADistributor' => isset($isCFADistributor) ? ($isCFADistributor ? 'true' : 'false') : 'not set',
                            'isClient' => isset($isClient) ? ($isClient ? 'true' : 'false') : 'not set',
                            'hasClient' => isset($client) ? 'yes' : 'no',
                            'hasClientDetails' => (isset($client) && isset($client->clientDetails)) ? 'yes' : 'no',
                            'categoryId' => isset($client) && isset($client->clientDetails) ? ($client->clientDetails->category_id ?? 'null') : 'N/A',
                            'categoryName' => (isset($client) && isset($client->clientDetails) && $client->clientDetails->category_id) ? (\App\Models\ClientCategory::find($client->clientDetails->category_id)->category_name ?? 'N/A') : 'N/A',
                            'areasCount' => (isset($client) && isset($client->clientDetails)) ? ($client->clientDetails->areas->count() ?? 0) : 0,
                            'stockistsCount' => isset($stockists) ? $stockists->count() : 'not set',
                        ];
                    @endphp
                    <div class="alert alert-info mb-3">
                        <strong>Debug Info:</strong><br>
                        <pre>{{ json_encode($debugInfo, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @endif
                
                {{-- Single client_id hidden input --}}
                @if (isset($isCFADistributor) && $isCFADistributor == true)
                    <input type="hidden" name="client_id" id="client_id" value="{{ $userId }}">
                @elseif (isset($client) && !is_null($client))
                    <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
                @elseif(isset($isClient) && $isClient == true)
                    <input type="hidden" name="client_id" id="client_id" value="{{ $userId }}">
                @endif
                
                {{-- For CFA/Distributor, stockist selection is in Bill To section below --}}
                @if (isset($isCFADistributor) && $isCFADistributor == true)
                    <!-- CFA/Distributor - Stockist selection moved to Bill To section -->
                    <div class="form-group">
                        <x-forms.label fieldId="client_display" :fieldLabel="__('app.client')">
                        </x-forms.label>
                        <div class="input-group">
                            <input type="text" value="{{ $client->name_salutation ?? 'CFA/Distributor' }}"
                                class="form-control height-35 f-15 readonly-background" readonly>
                        </div>
                        <small class="form-text text-muted">Bill From details shown below</small>
                    </div>
                @elseif (isset($client) && !is_null($client))
                    <div class="form-group">
                        <x-forms.label fieldId="due_date" :fieldLabel="__('app.client')">
                        </x-forms.label>
                        <div class="input-group">
                            <input type="text" value="{{ $client->name_salutation }}"
                                class="form-control height-35 f-15 readonly-background" readonly>
                        </div>
                    </div>
                @elseif(isset($isClient) && $isClient == true)
                    <div class="form-group">
                        <x-forms.label fieldId="due_date" :fieldLabel="__('app.client')">
                        </x-forms.label>
                        <div class="input-group">
                            <input type="text" value="{{ $client->name_salutation }}"
                                class="form-control height-35 f-15 readonly-background" readonly>
                        </div>
                    </div>
                @else
                    <x-client-selection-dropdown :clients="$clients" :selected="(isset($invoice) ? $invoice->client_id : (request()->has('default_client') ? request()->has('default_client') : (isset($estimate) ? $estimate->client_id : null)))" />
                @endif
            </div>
            <!-- CLIENT/STOCKIST END -->

            @if(in_array('projects', user_modules()))
            <!-- PROJECT START -->
            <div class="col-md-4">
                @if (isset($project) && !is_null($project))
                    <div class="form-group mb-4">
                        <x-forms.label fieldId="due_date" :fieldLabel="__('app.project')">
                        </x-forms.label>
                        <div class="input-group">
                            <input type="hidden" name="project_id" id="project_id" value="{{ $project->id }}">
                            <input type="text" value="{{ $project->project_name }}"
                                class="form-control height-35 f-15 readonly-background" readonly>
                        </div>
                    </div>
                @else
                    <div class="form-group c-inv-select mb-4">
                        <x-forms.label fieldId="project_id" :fieldLabel="__('app.project')">
                        </x-forms.label>
                        <div class="select-others height-35 rounded">
                            <select class="form-control select-picker" data-live-search="true" data-size="8"
                                name="project_id" id="project_id">
                                <option value="">--</option>
                                @if (isset($invoice) && $invoice->client)
                                    @foreach ($invoice->client->projects as $item)
                                        <option @selected($invoice->project_id == $item->id)  value="{{ $item->id }}"
                                                data-content="{!! '<strong>'.$item->project_short_code."</strong> ".$item->project_name !!}"
                                        >
                                            {{ $item->project_name }}</option>
                                    @endforeach
                                @elseif (isset($estimate) && $estimate->client)
                                    @foreach ($estimate->client->projects as $item)
                                        <option @selected($estimate->project_id == $item->id) value="{{ $item->id }}"
                                                data-content="{!! '<strong>'.$item->project_short_code."</strong> ".$item->project_name !!}"
                                        >
                                            {{ $item->project_name }}</option>
                                    @endforeach
                                @elseif (isset($isClient) && $isClient == true)
                                    @foreach ($client->projects as $item)
                                        <option @selected($client->project_id == $item->id) value="{{ $item->id }}"
                                                data-content="{!! '<strong>'.$item->project_short_code."</strong> ".$item->project_name !!}"
                                        >
                                            {{ $item->project_name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                @endif
            </div>
            <!-- PROJECT END -->
            @endif

            <div class="col-md-4">
                <div class="form-group c-inv-select mb-4">
                    <x-forms.label fieldId="calculate_tax" :fieldLabel="__('modules.invoices.calculateTax')">
                    </x-forms.label>
                    <div class="select-others height-35 rounded">
                        <select class="form-control select-picker" data-live-search="true" data-size="8"
                            name="calculate_tax" id="calculate_tax">
                            <option value="after_discount" @if (isset($invoice) && $invoice->calculate_tax == 'after_discount') selected @elseif(isset($estimate) && $estimate->calculate_tax == 'after_discount') selected @endif>
                                @lang('modules.invoices.afterDiscount')</option>
                            <option value="before_discount" @if (isset($invoice) && $invoice->calculate_tax == 'before_discount') selected @elseif(isset($estimate) && $estimate->calculate_tax == 'before_discount') selected @endif>
                                @lang('modules.invoices.beforeDiscount')</option>
                        </select>
                    </div>
                </div>
            </div>

            @if($linkInvoicePermission == 'all')
                <div class="col-md-4">
                    <div class="form-group c-inv-select mb-4">
                        <x-forms.label fieldId="bank_account_id" :fieldLabel="__('app.bankaccount')">
                        </x-forms.label>
                        <div class="select-others height-35 rounded">
                            <select class="form-control select-picker" data-live-search="true" data-size="8"
                                    name="bank_account_id" id="bank_account_id">
                                <option value="">--</option>
                                @if($viewBankAccountPermission != 'none')
                                    @foreach ($bankDetails as $bankDetail)
                                        <option value="{{ $bankDetail->id }}">@if($bankDetail->type == 'bank')
                                            {{ $bankDetail->bank_name }} | @endif
                                            {{ $bankDetail->account_name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>
            @endif

            <div class="col-md-4">
                <div class="form-group c-inv-select mb-4">
                    <x-forms.label fieldId="invoice_payment_id" :fieldLabel="__('modules.invoices.paymentDetails')">
                    </x-forms.label>
                    <div class="select-others height-35 rounded">
                        <select class="form-control select-picker" data-live-search="true" data-size="8"
                                name="invoice_payment_id" id="invoice_payment_id">
                            <option value="">--</option>
                                @foreach ($invoicePayments as $invoicePayment)
                                    <option value="{{ $invoicePayment->id }}">
                                        {{ $invoicePayment->title }}
                                    </option>
                                @endforeach
                        </select>
                    </div>
                </div>
            </div>

        </div>

        @if (isset($isCFADistributor) && $isCFADistributor == true)
            <!-- BILL FROM / BILL TO SECTION FOR CFA/DISTRIBUTOR -->
            <div class="row px-lg-4 px-md-4 px-3 py-3">
                <!-- BILL FROM: CFA/Distributor Details (Left Side) -->
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fa fa-building mr-2"></i>Bill From</h5>
                        </div>
                        <div class="card-body">
                            @if(isset($client) && $client->clientDetails)
                                @if($client->clientDetails->company_name)
                                <div class="mb-3">
                                    <strong class="text-dark-grey f-16">Company Name:</strong>
                                    <p class="mb-0 f-15 font-weight-bold">{{ $client->clientDetails->company_name }}</p>
                                </div>
                                @endif
                                @if($client->email)
                                <div class="mb-2">
                                    <strong class="text-dark-grey">Email:</strong>
                                    <p class="mb-1">{{ $client->email }}</p>
                                </div>
                                @endif
                                @if($client->mobile)
                                <div class="mb-2">
                                    <strong class="text-dark-grey">Mobile:</strong>
                                    <p class="mb-1">{{ $client->mobile_with_phonecode }}</p>
                                </div>
                                @endif
                                @php
                                    $addressParts = [];
                                    if ($client->clientDetails->address) {
                                        $addressParts[] = $client->clientDetails->address;
                                    }
                                    if ($client->clientDetails->city) {
                                        $addressParts[] = $client->clientDetails->city;
                                    }
                                    if ($client->clientDetails->state) {
                                        $addressParts[] = $client->clientDetails->state;
                                    }
                                    if ($client->clientDetails->postal_code) {
                                        $addressParts[] = $client->clientDetails->postal_code;
                                    }
                                    $fullAddress = implode(', ', $addressParts);
                                @endphp
                                @if(!empty($fullAddress))
                                <div class="mb-2">
                                    <strong class="text-dark-grey">Address:</strong>
                                    <p class="mb-1">{{ $fullAddress }}</p>
                                </div>
                                @endif
                                @if($client->clientDetails->gst_number)
                                <div class="mb-2">
                                    <strong class="text-dark-grey">{{ $client->clientDetails->tax_name ?? 'GST' }}:</strong>
                                    <p class="mb-1">{{ $client->clientDetails->gst_number }}</p>
                                </div>
                                @endif
                                @if($client->clientDetails->dl_number)
                                <div class="mb-2">
                                    <strong class="text-dark-grey">DL Number:</strong>
                                    <p class="mb-1">{{ $client->clientDetails->dl_number }}</p>
                                </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- BILL TO: Stockist Details (Right Side) -->
                <div class="col-md-6">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fa fa-shopping-cart mr-2"></i>Bill To</h5>
                        </div>
                        <div class="card-body">
                            <!-- Stockist Selection -->
                            <div class="form-group mb-3">
                                <x-forms.label fieldId="stockist_id" :fieldLabel="__('Select Stockist')" fieldRequired="true">
                                </x-forms.label>
                                <div class="select-others height-35 rounded">
                                    <select class="form-control select-picker" data-live-search="true" data-size="8"
                                        name="stockist_id" id="stockist_id" required>
                                        <option value="">-- Select Stockist --</option>
                                        @if(isset($stockists) && $stockists->count() > 0)
                                            @foreach ($stockists as $stockist)
                                                <option value="{{ $stockist->id }}" 
                                                    @selected(isset($invoice) && $invoice->stockist_id == $stockist->id)>
                                                    {{ $stockist->shopname }}
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="" disabled>No stockists available in your allotted areas</option>
                                        @endif
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Stockist Details (will be populated when stockist is selected) -->
                            <div id="stockist_details_section" style="display: none;">
                                <hr class="my-3">
                                <div id="stockist_company_name" class="mb-3">
                                    <strong class="text-dark-grey f-16">Company Name:</strong>
                                    <p class="mb-0 f-15 font-weight-bold" id="stockist_company_name_value">-</p>
                                </div>
                                <div id="stockist_email" class="mb-2" style="display: none;">
                                    <strong class="text-dark-grey">Email:</strong>
                                    <p class="mb-1" id="stockist_email_value">-</p>
                                </div>
                                <div id="stockist_mobile" class="mb-2" style="display: none;">
                                    <strong class="text-dark-grey">Mobile:</strong>
                                    <p class="mb-1" id="stockist_mobile_value">-</p>
                                </div>
                                <div id="stockist_address" class="mb-2">
                                    <strong class="text-dark-grey">Address:</strong>
                                    <p class="mb-1" id="stockist_address_value">-</p>
                                </div>
                                <div id="stockist_gst" class="mb-2" style="display: none;">
                                    <strong class="text-dark-grey">GST:</strong>
                                    <p class="mb-1" id="stockist_gst_value">-</p>
                                </div>
                                <div id="stockist_dl" class="mb-2">
                                    <strong class="text-dark-grey">DL Number:</strong>
                                    <p class="mb-1" id="stockist_dl_value">-</p>
                                </div>
                            </div>
                            
                            <!-- Hidden fields for form submission -->
                            <textarea class="form-control d-none" id="client_billing_address_editable" name="billing_address" rows="3"></textarea>
                            <textarea class="form-control d-none" name="shipping_address" id="shipping_address" rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- Generated By field removed - using default company address --}}
            <input type="hidden" name="company_address_id" id="company_address_id" value="{{ $companyAddresses->where('is_default', true)->first()->id ?? ($companyAddresses->first()->id ?? '') }}">
        @else
            <!-- ORIGINAL BILLING/SHIPPING ADDRESS FOR NON-CFA/DISTRIBUTOR -->
            <div class="row px-lg-4 px-md-4 px-3 py-3">
                <!-- BILLING ADDRESS START -->
                <div class="col-md-4">
                    <div class="form-group c-inv-select mb-0">
                        <label class="f-14 text-dark-grey mb-12  w-100"
                            for="usr">@lang('modules.invoices.billingAddress')</label>
                        <p class="f-15" id="client_billing_address">
                            @if (isset($invoice) && $invoice->stockist)
                                {!! nl2br($invoice->stockist->address ?? '') !!}
                            @elseif (isset($invoice) && $invoice->client)
                                {!! nl2br($invoice->client->clientDetails->address) !!}
                            @elseif (isset($invoice) && isset($client))
                                {!! nl2br($client->clientDetails->address) !!}
                            @elseif (isset($estimate) && $estimate->client)
                                {!! nl2br($estimate->client->clientDetails->address) !!}
                            @else
                                <span class="text-lightest">@lang('messages.selectCustomerForBillingAddress')</span>
                            @endif
                        </p>
                        <textarea class="form-control d-none" id="client_billing_address_editable" name="billing_address" rows="3"></textarea>
                    </div>
                </div>
                <!-- BILLING ADDRESS END -->
                <!-- SHIPPING ADDRESS START -->
                <div class="col-md-4">
                    <div class="form-group c-inv-select mb-lg-0 mb-md-0 mb-4">
                        <label class="f-14 text-dark-grey mb-12  w-100"
                            for="usr">@lang('modules.invoices.shippingAddress') </label>
                        <p class="f-15" id="client_shipping_address">
                            @if (isset($invoice) && $invoice->stockist && $invoice->stockist->address)
                                {!! nl2br($invoice->stockist->address) !!}
                            @elseif (isset($invoice) && $invoice->client && $invoice->client->clientDetails->shipping_address)
                                {!! nl2br($invoice->client->clientDetails->shipping_address) !!}
                            @elseif(isset($client) && $client->clientDetails &&
                                $client->clientDetails->shipping_address)
                                {!! nl2br($client->clientDetails->shipping_address) !!}
                            @else
                                <a href="javascript:;" class="" id="show-shipping-field"><i
                                        class="f-12 mr-2 fa fa-plus"></i>@lang('app.addShippingAddress')</a>
                            @endif
                        </p>
                        <p class="d-none" id="add-shipping-field">
                            <textarea class="form-control f-14 pt-2" rows="3" placeholder="@lang('placeholders.address')"
                                name="shipping_address" id="shipping_address">@if (isset($invoice) && $invoice->client) {!! nl2br($invoice->client->clientDetails->shipping_address) !!} @endif</textarea>
                        </p>
                    </div>
                </div>
                <!-- SHIPPING ADDRESS END -->
                
                {{-- Generated By field removed - using default company address --}}
                <input type="hidden" name="company_address_id" id="company_address_id" value="{{ $companyAddresses->where('is_default', true)->first()->id ?? ($companyAddresses->first()->id ?? '') }}">
            </div>
        @endif
        <!-- CLIENT, PROJECT, GST, BILLING, SHIPPING ADDRESS END -->

         <x-forms.custom-field :fields="$fields"></x-forms.custom-field>

        <hr class="m-0 border-top-grey">

        <!-- NEW REDESIGNED PRODUCT SELECTION SECTION -->
            @if(in_array('products', user_modules()) || in_array('purchase', user_modules()))
        <div class="row px-lg-4 px-md-4 px-3 py-4 bg-light rounded mb-3">
            <div class="col-12">
                <h5 class="mb-3">
                    <i class="fa fa-shopping-cart mr-2"></i>Add Products to Invoice
                </h5>
            </div>
            
            <div class="col-md-5">
                <div class="form-group mb-3">
                    <label class="f-14 text-dark-grey mb-2 font-weight-bold" for="add-products">
                        <i class="fa fa-box mr-1"></i> Select Product <sup class="text-danger">*</sup>
                    </label>
                        <select class="form-control select-picker" data-live-search="true" data-size="8"
                            id="add-products" title="{{ __('app.menu.selectProduct') }}">
                        <option value="">{{ __('app.menu.selectProduct') }}</option>
                        </select>
                    <small class="form-text text-muted mt-1">
                        <i class="fa fa-info-circle"></i> Products are loaded from purchase entries only
                    </small>
                    </div>
                </div>
            
            <div class="col-md-7">
                <div class="form-group mb-3" id="batch-selection-wrapper" style="display: none;">
                    <label class="f-14 text-dark-grey mb-2 font-weight-bold">
                        <i class="fa fa-tags mr-1"></i> Select Batch <sup class="text-danger">*</sup>
                    </label>
                    <div id="batch-buttons-container" class="d-flex flex-wrap gap-2">
                        <!-- Batch buttons will be dynamically inserted here -->
                    </div>
                    <small class="form-text text-muted mt-2">
                        <i class="fa fa-info-circle"></i> Click on a batch number to add the product
                    </small>
                </div>
        </div>
            
            <div class="col-12">
                <div id="product-selection-info" class="alert alert-info mb-0" style="display: none;">
                    <i class="fa fa-info-circle"></i> <span id="product-selection-message"></span>
                </div>
            </div>
        </div>
        @endif

        <div id="sortable">
            @if (isset($invoice))
                @foreach ($invoice->items as $key => $item)
                    <!-- DESKTOP DESCRIPTION TABLE START -->
                    <div class="d-flex px-4 py-3 c-inv-desc item-row">
                        <div class="d-flex align-items-center">
                            <span class="ui-icon ui-icon-arrowthick-2-n-s mr-2"></span>
                            <input type="hidden" name="sort_order[]"
                                   value="{{ $item->id }}">
                        </div>

                        <div class="c-inv-desc-table w-100 d-lg-flex d-md-flex d-block">
                            <table width="100%">
                                <tbody>
                                    <tr class="text-dark-grey font-weight-bold f-14">
                                        <td width="{{ $invoiceSetting->hsn_sac_code_show ? '40%' : '50%' }}"
                                            class="border-0 inv-desc-mbl btlr">@lang('app.description')</td>
                                        @if ($invoiceSetting->hsn_sac_code_show)
                                            <td width="10%" class="border-0" align="right">@lang("app.hsnSac")
                                            </td>
                                        @endif
                                        <td width="10%" class="border-0" align="right">
                                            @lang("modules.invoices.qty")
                                        </td>
                                        <td width="10%" class="border-0" align="right">
                                            @lang("modules.invoices.unitPrice")</td>
                                        <td width="13%" class="border-0" align="right">
                                            @lang('modules.invoices.tax')
                                        </td>
                                        <td width="17%" class="border-0 bblr-mbl" align="right">
                                            @lang('modules.invoices.amount')</td>
                                    </tr>
                                    <tr>
                                        <td class="border-bottom-0 btrr-mbl btlr">
                                            <input type="text" class="form-control f-14 border-0 w-100 item_name"
                                                name="item_name[]" placeholder="@lang('modules.expenses.itemName')"
                                                value="{{ $item->item_name }}">
                                        </td>
                                        <td class="border-bottom-0 d-block d-lg-none d-md-none">
                                            <textarea class="f-14 border-0 w-100 mobile-description form-control"
                                                placeholder="@lang('placeholders.invoices.description')"
                                                name="item_summary[]">{{ $item->item_summary }}</textarea>
                                        </td>
                                        @if ($invoiceSetting->hsn_sac_code_show)
                                            <td class="border-bottom-0">
                                                <input type="text"
                                                    class="form-control f-14 border-0 w-100 text-right hsn_sac_code"
                                                    value="{{ $item->hsn_sac_code }}" name="hsn_sac_code[]">
                                            </td>
                                        @endif
                                        <td class="border-bottom-0">
                                            <input type="number" min="1"
                                                class="form-control f-14 border-0 w-100 text-right quantity mt-3"
                                                value="{{ $item->quantity }}" name="quantity[]">
                                                @if (!is_null($item->product_id) && $item->product_id != 0)
                                                    <span class="text-dark-grey float-right border-0 f-12">{{ $item->unit->unit_type }}</span>
                                                    <input type="hidden" name="product_id[]" value="{{ $item->product_id }}">
                                                    <input type="hidden" name="unit_id[]" value="{{ $item->unit_id }}">
                                                @else
                                                    <select class="text-dark-grey float-right border-0 f-12" name="unit_id[]">
                                                        @foreach ($units as $unit)
                                                            <option
                                                            @selected ($item->unit_id == $unit->id)
                                                            value="{{ $unit->id }}">{{ $unit->unit_type }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="hidden" name="product_id[]" value="">
                                                @endif
                                        </td>
                                        <td class="border-bottom-0">
                                            <input type="number" class="f-14 border-0 w-100 text-right cost_per_item form-control"
                                                placeholder="0.00" value="{{ $item->unit_price }}"
                                                name="cost_per_item[]" min="1">
                                        </td>
                                        <td class="border-bottom-0">
                                            <div class="select-others height-35 rounded border-0">
                                                <select id="multiselect" name="taxes[{{ $key }}][]"
                                                    multiple="multiple"
                                                    class="select-picker type customSequence border-0" data-size="3">
                                                    @foreach ($taxes as $tax)
                                                        <option data-rate="{{ $tax->rate_percent }}" data-tax-text="{{ $tax->tax_name .':'. $tax->rate_percent }}%"
                                                            @selected (isset($item->taxes) && array_search($tax->id, json_decode($item->taxes)) !== false) value="{{ $tax->id }}">
                                                            {{ $tax->tax_name }}:
                                                            {{ $tax->rate_percent }}%</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </td>
                                        <td rowspan="2" align="right" valign="top" class="bg-amt-grey btrr-bbrr">
                                            <span
                                                class="amount-html">{{ number_format((float) $item->amount, 2, '.', '') }}</span>
                                            <input type="hidden" class="amount" name="amount[]"
                                                value="{{ $item->amount }}">
                                        </td>
                                    </tr>
                                    <tr class="d-none d-md-block d-lg-table-row">
                                        <td colspan="{{ $invoiceSetting->hsn_sac_code_show ? '4' : '3' }}"
                                            class="dash-border-top bblr">
                                            <textarea class="f-14 border-0 w-100 desktop-description form-control"
                                                name="item_summary[]"
                                                placeholder="@lang('placeholders.invoices.description')">{{ $item->item_summary }}</textarea>
                                        </td>
                                        <td class="border-left-0">
                                            <input type="file" class="dropify itemImage"
                                                name="invoice_item_image[]" id="image{{ $item->id }}"
                                                data-index="{{ $loop->index }}"
                                                data-allowed-file-extensions="png jpg jpeg bmp"
                                                data-item-id="image{{ $item->id }}"
                                                data-default-file="{{ $item->invoiceItemImage ? $item->invoiceItemImage->file_url : '' }}"
                                                data-height="70" />
                                            <input type="hidden" name="invoice_item_image_url[]" value="{{ $item->invoiceItemImage ? $item->invoiceItemImage->file : '' }}">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <a href="javascript:;"
                                class="d-flex align-items-center justify-content-center ml-3 remove-item"><i
                                    class="fa fa-times-circle f-20 text-lightest"></i></a>
                        </div>
                    </div>
                    <!-- DESKTOP DESCRIPTION TABLE END -->
                @endforeach
                @elseif (isset($estimate))
                @foreach ($estimate->items as $key => $item)
                    <!-- DESKTOP DESCRIPTION TABLE START -->
                    <div class="d-flex px-4 py-3 c-inv-desc item-row">
                        <div class="d-flex align-items-center">
                            <span class="ui-icon ui-icon-arrowthick-2-n-s mr-2"></span>
                            <input type="hidden" name="sort_order[]"
                                   value="{{ $item->id }}">
                        </div>

                        <div class="c-inv-desc-table w-100 d-lg-flex d-md-flex d-block">
                            <table width="100%">
                                <tbody>
                                    <tr class="text-dark-grey font-weight-bold f-14">
                                        <td width="{{ $invoiceSetting->hsn_sac_code_show ? '40%' : '50%' }}"
                                            class="border-0 inv-desc-mbl btlr">@lang('app.description')</td>
                                        @if ($invoiceSetting->hsn_sac_code_show)
                                            <td width="10%" class="border-0" align="right">@lang("app.hsnSac")
                                            </td>
                                        @endif
                                        <td width="10%" class="border-0" align="right">
                                            @lang('modules.invoices.qty')
                                        </td>
                                        <td width="10%" class="border-0" align="right">
                                            @lang("modules.invoices.unitPrice")</td>
                                        <td width="13%" class="border-0" align="right">
                                            @lang('modules.invoices.tax')
                                        </td>
                                        <td width="17%" class="border-0 bblr-mbl" align="right">
                                            @lang('modules.invoices.amount')</td>
                                    </tr>
                                    <tr>
                                        <td class="border-bottom-0 btrr-mbl btlr">
                                            <input type="text" class="form-control f-14 border-0 w-100 item_name"
                                                name="item_name[]" placeholder="@lang('modules.expenses.itemName')"
                                                value="{{ $item->item_name }}">
                                        </td>
                                        <td class="border-bottom-0 d-block d-lg-none d-md-none">
                                            <textarea class="f-14 border-0 w-100 mobile-description form-control"
                                                placeholder="@lang('placeholders.invoices.description')"
                                                name="item_summary[]">{{ $item->item_summary }}</textarea>
                                        </td>
                                        @if ($invoiceSetting->hsn_sac_code_show)
                                            <td class="border-bottom-0">
                                                <input type="text"
                                                    class="form-control f-14 border-0 w-100 text-right hsn_sac_code"
                                                    value="{{ $item->hsn_sac_code }}" name="hsn_sac_code[]">
                                            </td>
                                        @endif
                                        <td class="border-bottom-0">
                                            <input type="number" min="1"
                                                class="form-control f-14 border-0 w-100 text-right quantity mt-3"
                                                value="{{ $item->quantity }}" name="quantity[]">
                                                @if (!is_null($item->product_id) && $item->product_id != 0)
                                                    <span class="text-dark-grey float-right border-0 f-12">{{ $item->unit->unit_type }}</span>
                                                    <input type="hidden" name="product_id[]" value="{{ $item->product_id }}">
                                                    <input type="hidden" name="unit_id[]" value="{{ $item->unit_id }}">
                                                @else
                                                    <select class="text-dark-grey float-right border-0 f-12" name="unit_id[]">
                                                        @foreach ($units as $unit)
                                                            <option
                                                            @selected($item->unit_id == $unit->id)
                                                            value="{{ $unit->id }}">{{ $unit->unit_type }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="hidden" name="product_id[]" value="">
                                                @endif
                                        </td>
                                        <td class="border-bottom-0">
                                            <input type="number" class="f-14 border-0 w-100 text-right cost_per_item form-control"
                                                placeholder="0.00" value="{{ $item->unit_price }}"
                                                name="cost_per_item[]" min="1">
                                        </td>
                                        <td class="border-bottom-0">
                                            <div class="select-others height-35 rounded border-0">
                                                <select id="multiselect" name="taxes[{{ $key }}][]"
                                                    multiple="multiple"
                                                    class="select-picker type customSequence border-0" data-size="3">
                                                    @foreach ($taxes as $tax)
                                                        <option data-rate="{{ $tax->rate_percent }}" data-tax-text="{{ $tax->tax_name .':'. $tax->rate_percent }}%"
                                                            @selected (isset($item->taxes) && array_search($tax->id, json_decode($item->taxes)) !== false) value="{{ $tax->id }}">
                                                            {{ $tax->tax_name }}:
                                                            {{ $tax->rate_percent }}%</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </td>
                                        <td rowspan="2" align="right" valign="top" class="bg-amt-grey btrr-bbrr">
                                            <span
                                                class="amount-html">{{ number_format((float) $item->amount, 2, '.', '') }}</span>
                                            <input type="hidden" class="amount" name="amount[]"
                                                value="{{ $item->amount }}">
                                        </td>
                                    </tr>
                                    <tr class="d-none d-md-block d-lg-table-row">
                                        <td colspan="{{ $invoiceSetting->hsn_sac_code_show ? '4' : '3' }}"
                                            class="dash-border-top bblr">
                                            <textarea class="f-14 border-0 w-100 desktop-description form-control"
                                                name="item_summary[]"
                                                placeholder="@lang('placeholders.invoices.description')">{{ $item->item_summary }}</textarea>
                                        </td>
                                        <td class="border-left-0">
                                            @if (isset($type) && $type == 'proposal')
                                                <input type="hidden" id="imageId_{{ $item->id }}"
                                                    class="itemOldImage" name="image_id[]"
                                                    value={{ isset($item->proposalItemImage->id) ? $item->proposalItemImage->id : '' }} />

                                                <input type="file" class="dropify itemImage"
                                                    name="invoice_item_image[]" id="image{{ $item->id }}"
                                                    data-index="{{ $loop->index }}"
                                                    data-allowed-file-extensions="png jpg jpeg bmp"
                                                    data-item-id="{{ $item->id }}"
                                                    data-default-file="{{ $item->proposalItemImage ? $item->proposalItemImage->file_url : '' }}"
                                                    data-height="70" multiple />
                                                <input type="hidden" name="invoice_item_image_url[]"  value="{{ $item->proposalItemImage ? $item->proposalItemImage->file : '' }}">
                                            @else
                                                <input type="hidden" id="imageId_{{ $item->id }}"
                                                    class="itemOldImage" name="image_id[]"
                                                    value={{ isset($item->estimateItemImage->id) ? $item->estimateItemImage->id : '' }} />

                                                <input type="file" class="dropify itemImage"
                                                    name="invoice_item_image[]" id="image{{ $item->id }}"
                                                    data-index="{{ $loop->index }}"
                                                    data-allowed-file-extensions="png jpg jpeg bmp"
                                                    data-item-id="{{ $item->id }}"
                                                    data-default-file="{{ $item->estimateItemImage ? $item->estimateItemImage->file_url : '' }}"
                                                    data-height="70" multiple />
                                                <input type="hidden" name="invoice_item_image_url[]"  value="{{ $item->estimateItemImage ? $item->estimateItemImage->file : '' }}">
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <a href="javascript:;"
                                class="d-flex align-items-center justify-content-center ml-3 remove-item"><i
                                    class="fa fa-times-circle f-20 text-lightest"></i></a>
                        </div>
                    </div>
                    <!-- DESKTOP DESCRIPTION TABLE END -->
                @endforeach
            @else
                <!-- DESKTOP DESCRIPTION TABLE START - Hidden by default, will be shown when product is selected -->
                <div class="d-flex px-4 py-3 c-inv-desc item-row" style="display: none;">
                    <div class="d-flex align-items-center">
                        <span class="ui-icon ui-icon-arrowthick-2-n-s mr-2"></span>
                        <input type="hidden" name="sort_order[]"
                                value="1">
                    </div>

                    <div class="c-inv-desc-table w-100 d-lg-flex d-md-flex d-block">
                        <table width="100%">
                            <tbody>
                                <tr class="text-dark-grey font-weight-bold f-14">
                                    <td width="{{ $invoiceSetting->hsn_sac_code_show ? '40%' : '50%' }}"
                                        class="border-0 inv-desc-mbl inv-col-desc btlr">@lang('app.description')</td>
                                        <td class="border-0 bblr-mbl" align="right">Scheme</td>
                                        <td align="right">Pack</td>
                                        <td align="right">MFR</td>
                                        <td align="right">Batch</td>
                                        <td align="right">Exp</td>
                                    @if ($invoiceSetting->hsn_sac_code_show)
                                        <td width="10%" class="border-0" align="right">@lang("app.hsnSac")</td>
                                    @endif
                                    <td align="right">MRP</td>
                                    <td align="right">PTS</td>
                                    <td align="right">PTR</td>
                                    <td align="right">DIS</td>
                                    <td width="10%" class="border-0" align="right">
                                        @lang("modules.invoices.qty")
                                    </td>
                                    <td width="10%" class="border-0" align="right">
                                        @lang("modules.invoices.unitPrice")
                                    </td>
                                    <td width="13%" class="border-0" align="right">@lang('modules.invoices.tax')
                                    </td>
                                    <td width="17%" class="border-0 bblr-mbl" align="right">
                                        @lang('modules.invoices.amount')</td>
                                </tr>
                                <tr>
                                    <td class="border-bottom-0 btrr-mbl btlr">
                                        {{-- Product name is auto-filled when added from main product selector --}}
                                        <input type="text" 
                                            class="form-control item_name border-0" 
                                            name="item_name[]"
                                            placeholder="@lang('app.description')"
                                            readonly
                                            style="background-color: #f5f6f7; cursor: not-allowed;"
                                        >
                                        <small class="text-muted d-block mt-1">
                                            <i class="fa fa-info-circle"></i> Select product from above to add
                                        </small>
                                    </td>
                                    <td class="border-bottom-0 d-block d-lg-none d-md-none">
                                        <textarea class="form-control f-14 border-0 w-100 mobile-description form-control"
                                            name="item_summary[]"
                                            placeholder="@lang('placeholders.invoices.description')"></textarea>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control border-0 scheme" name="scheme[]">
                                    </td>
                                    
                                    <td>
                                        <input type="text" class="form-control border-0 pack" name="pack[]">
                                    </td>
                                    
                                    <td>
                                        <input type="text" class="form-control border-0 mfr" name="mfr[]">
                                    </td>
                                    
                                    <td>
                                        <input type="text" class="form-control border-0" name="batch[]">
                                    </td>
                                    
                                    <td>
                                        <input type="date" class="form-control border-0" name="exp[]">
                                    </td>
                                    @if ($invoiceSetting->hsn_sac_code_show)
                                        <td class="border-bottom-0">
                                            <input type="text"
                                                class="form-control f-14 border-0 w-100 text-right hsn_sac_code"
                                                placeholder="" name="hsn_sac_code[]">
                                        </td>
                                    @endif
                                    <td>
                                        <input type="number" step="0.01" class="form-control border-0 text-right mrp" name="mrp[]">
                                    </td>
                                    
                                    <td>
                                        <input type="number" step="0.01" class="form-control border-0 text-right pts" name="pts[]">
                                    </td>
                                    
                                    <td>
                                        <input type="number" step="0.01" class="form-control border-0 text-right ptr" name="ptr[]">
                                    </td>
                                    
                                    <td>
                                        <input type="number" step="0.01" class="form-control border-0 text-right discount" name="dis[]">
                                    </td>

                                    <td class="border-bottom-0">
                                        <input type="number" min="1"
                                            class="form-control f-14 border-0 w-100 text-right quantity mt-3" value="1"
                                            name="quantity[]">
                                        <select class="text-dark-grey float-right border-0 f-12" name="unit_id[]">
                                            @foreach ($units as $unit)
                                                <option
                                                @selected($unit->default == 1)
                                                value="{{ $unit->id }}">{{ $unit->unit_type }}</option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="product_id[]" value="">
                                    </td>
                                    <td class="border-bottom-0">
                                        <input type="number" min="1"
                                            class="f-14 border-0 w-100 text-right cost_per_item form-control" placeholder="0.00"
                                            value="0" name="cost_per_item[]">
                                    </td>
                                    <td class="border-bottom-0">
                                        <div class="select-others height-35 rounded border-0">
                                            <select id="multiselect" name="taxes[0][]" multiple="multiple"
                                                class="select-picker type customSequence border-0" data-size="3">
                                                @foreach ($taxes as $tax)
                                                    <option data-rate="{{ $tax->rate_percent }}" data-tax-text="{{ $tax->tax_name .':'. $tax->rate_percent }}%"
                                                        value="{{ $tax->id }}">{{ $tax->tax_name }}:
                                                        {{ $tax->rate_percent }}%</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </td>
                                    <td rowspan="2" align="right" valign="top" class="bg-amt-grey btrr-bbrr">
                                        <span class="amount-html">0.00</span>
                                        <input type="hidden" class="amount" name="amount[]" value="0">
                                    </td>
                                </tr>
                                {{-- Description and Image fields removed - invoice only uses purchase entry fields --}}
                            </tbody>
                        </table>

                        <a href="javascript:;"
                            class="d-flex align-items-center justify-content-center ml-3 remove-item"><i
                                class="fa fa-times-circle f-20 text-lightest"></i></a>
                    </div>
                </div>
                <!-- DESKTOP DESCRIPTION TABLE END -->
            @endif

        </div>
        <!-- OLD ADD ITEM BUTTON REMOVED - Using new product selection flow above -->

        <hr class="m-0 border-top-grey">

        <!-- TOTAL, DISCOUNT START -->
        <div class="d-flex px-lg-4 px-md-4 px-3 pb-3 c-inv-total">
            <table width="100%" class="text-right f-14">
                <tbody>
                    <tr>
                        <td width="50%" class="border-0 d-lg-table d-md-table d-none"></td>
                        <td width="50%" class="p-0 border-0 c-inv-total-right">
                            <table width="100%">
                                <tbody>
                                    <tr>
                                        <td colspan="2" class="border-top-0 text-dark-grey">
                                            @lang('modules.invoices.subTotal')</td>
                                        <td width="30%" class="border-top-0 sub-total">0.00</td>
                                        <input type="hidden" class="sub-total-field" name="sub_total" value="0">
                                    </tr>
                                    <tr>
                                        <td width="20%" class="text-dark-grey">@lang('modules.invoices.discount')
                                        </td>
                                        <td width="40%" style="padding: 5px;">
                                            <table width="100%" class="mw-250">
                                                <tbody>
                                                    <tr>
                                                        <td width="70%" class="c-inv-sub-padding">
                                                            <input type="number" min="0" name="discount_value"
                                                                class="form-control f-14 border-0 w-100 text-right discount_value"
                                                                placeholder="0"
                                                                value= "{{ isset($estimate) ? $estimate->discount : (isset($invoice) ? $invoice->discount : '0') }}">
                                                        </td>
                                                        <td width="30%" align="left" class="c-inv-sub-padding">
                                                            <div
                                                                class="select-others select-tax height-35 rounded border-0">
                                                                <select class="form-control select-picker"
                                                                    id="discount_type" name="discount_type">
                                                                    <option  @selected(isset($invoice) && $invoice->discount_type == 'percent') value="percent">%
                                                                    </option>
                                                                    <option @selected(isset($invoice) && $invoice->discount_type == 'fixed') value="fixed">
                                                                        @lang('modules.invoices.amount')</option>
                                                                </select>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </td>
                                        <td><span
                                                id="discount_amount">{{ isset($invoice) ? number_format((float) $invoice->discount, 2, '.', '') : '0.00' }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>@lang('modules.invoices.tax')</td>
                                        <td colspan="2" class="p-0 border-0">
                                            <table width="100%" id="invoice-taxes">
                                                <tr>
                                                    <td colspan="2"><span class="tax-percent">0.00</span></td>
                                                </tr>
                                            </table>
                                        </td>

                                    </tr>
                                    <tr class="bg-amt-grey f-16 f-w-500">
                                        <td colspan="2">@lang('modules.invoices.total')</td>
                                        <td><span class="total">0.00</span></td>
                                        <input type="hidden" class="total-field" name="total" value="0">
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- TOTAL, DISCOUNT END -->

        <!-- NOTE AND TERMS AND CONDITIONS START -->
        <div class="d-flex flex-wrap px-lg-4 px-md-4 px-3 py-3">
            <div class="col-md-6 col-sm-12 c-inv-note-terms p-0 mb-lg-0 mb-md-0 mb-3">
                <x-forms.label fieldId="" class="" :fieldLabel="__('modules.invoices.note')">
                </x-forms.label>
                <textarea class="form-control" name="note" id="note" rows="4"
                    placeholder="@lang('placeholders.invoices.note')">{{ isset($estimate) ? $estimate->note : (isset($invoice) ? $invoice->note : '') }}</textarea>
            </div>
            <div class="col-md-6 col-sm-12 p-0 c-inv-note-terms">
                <x-forms.label fieldId="" :fieldLabel="__('modules.invoiceSettings.invoiceTerms')">
                </x-forms.label>
                <p>
                    {!! nl2br($invoiceSetting->invoice_terms) !!}
                </p>
            </div>
        </div>
        <!-- NOTE AND TERMS AND CONDITIONS END -->

        <!-- UPLOAD MULTIPLE FILES START -->
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <!-- INVOICE NUMBER START -->
            <div class="col-md-12">
                <x-forms.file-multiple class="mr-0 mr-lg-2 mr-md-2" :fieldLabel="__('app.menu.addFile')" fieldName="file" fieldId="file-upload-dropzone"/>
            </div>
            <input type="hidden" name="invoiceID" id="invoiceID">
        </div>
        <!-- UPLOAD MULTIPLE FILES END -->

        <div class="d-flex px-lg-4 px-md-4 px-3 py-2 bg-light-grey">
            <div class="col-md-3">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12 w-100" for="payment_status"></label>
                    <div class="d-flex">
                        <x-forms.checkbox fieldId="payment_status" :fieldLabel="__('modules.invoices.receivedPayment')" fieldValue="0" fieldName="payment_status"></x-forms.checkbox>
                    </div>
                </div>
            </div>

            <div class="col-md-3 payment-types d-none">
                <x-forms.select fieldId="payment_gateway_id" :fieldLabel="__('modules.payments.paymentGateway')" fieldName="gateway"
                search="true" fieldRequired="true">
                    <option value="">--</option>
                    <option value="Offline"  id="offline_method" >{{ __('modules.offlinePayment.offlinePayment') }}</option>
                    @if ($paymentGateway->paypal_status == 'active')
                        <option value="paypal">{{ __('app.paypal') }}</option>
                    @endif
                    @if ($paymentGateway->stripe_status == 'active')
                        <option value="stripe">{{ __('app.stripe') }}</option>
                    @endif
                    @if ($paymentGateway->razorpay_status == 'active')
                        <option value="razorpay">{{ __('app.razorpay') }}</option>
                    @endif
                    @if ($paymentGateway->paystack_status == 'active')
                        <option value="paystack">{{ __('app.paystack') }}</option>
                    @endif
                    @if ($paymentGateway->mollie_status == 'active')
                        <option value="mollie">{{ __('app.mollie') }}</option>
                    @endif
                    @if ($paymentGateway->payfast_status == 'active')
                        <option value="payfast">{{ __('app.payfast') }}</option>
                    @endif
                    @if ($paymentGateway->authorize_status == 'active')
                        <option value="authorize">{{ __('app.authorize') }}</option>
                    @endif
                    @if ($paymentGateway->square_status == 'active')
                        <option value="square">{{ __('app.square') }}</option>
                    @endif
                    @if ($paymentGateway->flutterwave_status == 'active')
                        <option value="flutterwave">{{ __('app.flutterwave') }}</option>
                    @endif
                </x-forms.select>
            </div>

            <div class="col-md-3 d-none" id="add_offline">
                <x-forms.select fieldId="add_offline_methods" :fieldLabel="__('modules.payments.offlinePaymentMethod')" fieldName="offline_methods"
                search="true" fieldRequired="true">
                </x-forms.select>
            </div>

            <div class="col-md-3 payment-types d-none">
                <x-forms.text fieldId="transaction_id" :fieldLabel="__('modules.payments.transactionId')"
                    fieldName="transaction_id" :fieldPlaceholder="__('placeholders.payments.transactionId')" />
            </div>
        </div>

        <!-- CANCEL SAVE SEND START -->
        <x-form-actions class="c-inv-btns d-block d-lg-flex d-md-flex">
            <div class="d-flex mb-3 mb-lg-0 mb-md-0">

                <div class="inv-action dropup mr-3">
                    <button class="btn-primary dropdown-toggle" type="button" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        @lang('app.save')
                        <span><i class="fa fa-chevron-up f-15 text-white"></i></span>
                    </button>
                    <!-- DROPDOWN - INFORMATION -->
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuBtn" tabindex="0">
                        <li>
                            <a class="dropdown-item f-14 text-dark save-form" href="javascript:;" data-type="save">
                                <i class="fa fa-save f-w-500 mr-2 f-11"></i> @lang('app.save')
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item f-14 text-dark save-form" href="javascript:void(0);"
                                data-type="send">
                                <i class="fa fa-paper-plane f-w-500  mr-2 f-12"></i> @lang('app.saveSend')
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item f-14 text-dark save-form" href="javascript:void(0);"
                                data-type="mark_as_send" data-toggle="tooltip" data-original-title="@lang('messages.markSentInfo')">
                                <i class="fa fa-check-double f-w-500  mr-2 f-12"></i> @lang('app.saveMark')
                            </a>
                        </li>
                    </ul>
                </div>

                <x-forms.button-secondary data-type="draft" class="save-form mr-3">@lang('app.saveDraft')
                </x-forms.button-secondary>

            </div>

            <x-forms.button-cancel :link="route('invoices.index')" class="border-0 ">@lang('app.cancel')
            </x-forms.button-cancel>

        </x-form-actions>
        <!-- CANCEL SAVE SEND END -->

    </x-form>
    <!-- FORM END -->
</div>
<!-- CREATE INVOICE END -->
<!-- for sortable content -->
<script src="{{ asset('vendor/jquery/jquery-ui.min.js') }}"></script>

<script>
    $(function () {
        $("#sortable").sortable();
    });

    $(document).ready(function() {

        let defaultImage = '';
        let lastIndex = 0;

        Dropzone.autoDiscover = false;
        //Dropzone class
        invoiceDropzone = new Dropzone("div#file-upload-dropzone", {
            dictDefaultMessage: {!! json_encode(__('app.dragDrop')) !!},
            url: {!! json_encode(route('invoice-files.store')) !!},
            headers: {
                'X-CSRF-TOKEN': {!! json_encode(csrf_token()) !!}
            },
            paramName: "file",
            maxFilesize: DROPZONE_MAX_FILESIZE,
            maxFiles: DROPZONE_MAX_FILES,
            autoProcessQueue: false,
            uploadMultiple: true,
            addRemoveLinks: true,
            parallelUploads: DROPZONE_MAX_FILES,
            acceptedFiles: DROPZONE_FILE_ALLOW,
            init: function () {
                invoiceDropzone = this;
            }
        });
        invoiceDropzone.on('sending', function (file, xhr, formData) {
            const invoiceID = $('#invoiceID').val();
            formData.append('invoice_id', invoiceID);
            formData.append('default_image', defaultImage);
            $.easyBlockUI();
        });
        invoiceDropzone.on('uploadprogress', function () {
            $.easyBlockUI();
        });
        invoiceDropzone.on('queuecomplete', function () {
            window.location.href = {!! json_encode(route('invoices.index')) !!};
        });
        invoiceDropzone.on('removedfile', function () {
            var grp = $('div#file-upload-dropzone').closest(".form-group");
            var label = $('div#file-upload-box').siblings("label");
            $(grp).removeClass("has-error");
            $(label).removeClass("is-invalid");
        });
        invoiceDropzone.on('error', function (file, message) {
            invoiceDropzone.removeFile(file);
            var grp = $('div#file-upload-dropzone').closest(".form-group");
            var label = $('div#file-upload-box').siblings("label");
            $(grp).find(".help-block").remove();
            var helpBlockContainer = $(grp);

            if (helpBlockContainer.length == 0) {
                helpBlockContainer = $(grp);
            }

            helpBlockContainer.append('<div class="help-block invalid-feedback">' + message + '</div>');
            $(grp).addClass("has-error");
            $(label).addClass("is-invalid");

        });
        invoiceDropzone.on('addedfile', function (file) {
            lastIndex++;

            const div = document.createElement('div');
            div.className = 'form-check-inline custom-control custom-radio mt-2 mr-3';
            const input = document.createElement('input');
            input.className = 'custom-control-input';
            input.type = 'radio';
            input.name = 'default_image';
            input.id = 'default-image-' + lastIndex;
            input.value = file.name;
            if (lastIndex == 1) {
                input.checked = true;
            }
            div.appendChild(input);

            var label = document.createElement('label');
            label.className = 'custom-control-label pt-1 cursor-pointer';
            label.innerHTML = "@lang('modules.makeDefaultImage')";
            label.htmlFor = 'default-image-' + lastIndex;
            div.appendChild(label);

            file.previewTemplate.appendChild(div);
        });

        $('.toggle-product-category').click(function() {
            $('.product-category-filter').toggleClass('d-none');
            @if (in_array('purchase', user_modules()))
                // For purchase entries, category filter is disabled
                return;
            @endif
            var url = {!! json_encode(route('invoices.product_category', ':id')) !!};
            url = url.replace(':id', null);
            changeProductCategory(url);
            $('#product_category_id').val('').trigger('change');
            $('#product_category_id').selectpicker('refresh');
        });

        $('#product_category_id').on('change', function(){
            @if (in_array('purchase', user_modules()))
                // For purchase entries, category filter is disabled
                return;
            @endif
            var categoryId = $(this).val();
            var url = {!! json_encode(route('invoices.product_category', ':id')) !!};
            url = (categoryId) ? url.replace(':id', categoryId) : url.replace(':id', null);
            changeProductCategory(url);
        });

        function changeProductCategory(url) {
            $.easyAjax({
                url : url,
                type : "GET",
                container: '#saveInvoiceForm',
                blockUI: true,
                success: function (response) {
                    if (response.status == 'success') {
                        var options = [];
                        var rData = [];
                        rData = response.data;
                        $.each(rData, function(index, value) {
                            var selectData = '';
                            // For purchase entries, value.id is the purchase entry ID
                            selectData = '<option value="' + value.id + '">' + value.name +
                                '</option>';
                            options.push(selectData);
                        });
                        $('#add-products').html(
                            '<option value="" class="form-control">' + translations.selectProduct + '</option>' +
                            options);
                        $('#add-products').selectpicker('refresh');
                    }
                }
            });
        }

        const hsn_status = {{ $invoiceSetting->hsn_sac_code_show }};
        const defaultClient = "{{ request('client_id') }}";

        const userRoles = @json($user->roles);
        const isClient = userRoles ? userRoles.some(function(role) { return role.name === "client"; }) : false;
        
        // Translation strings - declare once at top level to avoid issues
        const translations = {
            selectProduct: {!! json_encode(__('app.menu.selectProduct')) !!},
            addShippingAddress: {!! json_encode(__('app.addShippingAddress')) !!},
            selectCustomerForBilling: {!! json_encode(__('messages.selectCustomerForBillingAddress')) !!},
            to: {!! json_encode(__('app.to')) !!}
        };
        
        // Batch buttons are initialized dynamically - no need for selectpicker initialization

        $('.custom-date-picker').each(function(ind, el) {
            datepicker(el, {
                position: 'bl',
                ...datepickerConfig
            });
        });

        const dp1 = datepicker('#invoice_date', {
            position: 'bl',
            ...datepickerConfig
        });

        const dp2 = datepicker('#due_date', {
            position: 'bl',
            ...datepickerConfig
        });

        // ==================== NEW REDESIGNED PRODUCT SELECTION FLOW ====================
        
        var selectedProductId = null;
        var selectedBatchData = null;
        
        // Function to load consolidated products
        function loadConsolidatedProducts() {
            var clientId = $('#client_id').val() || $('#client_list_id').val() || '';
            var url = {!! json_encode(route('invoices.products-consolidated')) !!};
            if (clientId) {
                url += '?client_id=' + clientId;
            }
            
            console.log('Loading products from:', url);
            
            $.easyAjax({
                url: url,
                type: "GET",
                blockUI: false,
                success: function(response) {
                    console.log('Products API Response:', response);
                    if (response.status == 'success' && response.data && response.data.length > 0) {
                        var options = '<option value="">' + translations.selectProduct + '</option>';
                        response.data.forEach(function(product) {
                            options += '<option value="' + product.product_id + '" ' +
                                'data-product-name="' + (product.product_name || '') + '">' +
                                product.display_name +
                                '</option>';
                        });
                        $('#add-products').html(options).selectpicker('refresh');
                        $('#product-selection-info').hide();
                        console.log('Loaded ' + response.data.length + ' products');
                    } else {
                        console.log('No products found in response');
                        var purchaseEntriesUrl = "{{ route('purchase-entries.index') }}";
                        $('#product-selection-info').show().find('#product-selection-message').html('<i class="fa fa-exclamation-triangle text-warning"></i> No purchase entries found. Please add purchase entries first at <a href="' + purchaseEntriesUrl + '" class="text-primary">Purchase Entries</a>.');
                    }
                },
                error: function(response) {
                    console.error('Error loading consolidated products:', response);
                    var errorMsg = 'Error loading products. ';
                    if (response.responseJSON && response.responseJSON.message) {
                        errorMsg += response.responseJSON.message;
                    }
                    $('#product-selection-info').show().find('#product-selection-message').html('<i class="fa fa-exclamation-circle text-danger"></i> ' + errorMsg);
                }
            });
        }
        
        // Handle product selection - load batches
        $('#add-products').on('changed.bs.select', function() {
            selectedProductId = $(this).val();
            selectedBatchData = null;
            
            // Reset UI
            $('#batch-selection-wrapper').hide();
            $('#batch-buttons-container').html('');
            $('#product-selection-info').hide();
            
            if (!selectedProductId) {
                return;
            }
            
            // Load batches for selected product
            loadProductBatches(selectedProductId);
        });
        
        // Function to load batches for a product and show as buttons
        function loadProductBatches(productId) {
            var url = {!! json_encode(route('invoices.product-batches')) !!} + '?product_id=' + productId;
            
            $('#product-selection-info').show().find('#product-selection-message').html('<i class="fa fa-spinner fa-spin"></i> Loading batches...');
            $('#batch-buttons-container').html('');
            
            $.easyAjax({
                url: url,
                type: "GET",
                blockUI: false,
                success: function(response) {
                    $('#product-selection-info').hide();
                    
                    if (response.status == 'success' && response.data && response.data.length > 0) {
                        var buttonsHtml = '';
                        response.data.forEach(function(batch) {
                            var batchValue = batch.batch || 'N/A';
                            var batchDataStr = JSON.stringify(batch);
                            buttonsHtml += '<button type="button" class="btn btn-outline-primary batch-btn mb-2" ' +
                                'data-batch-data=\'' + batchDataStr + '\'>' +
                                '<i class="fa fa-tag mr-1"></i>' + batchValue +
                                '</button>';
                        });
                        
                        $('#batch-buttons-container').html(buttonsHtml);
                        $('#batch-selection-wrapper').slideDown(300);
                        $('#product-selection-info').show().find('#product-selection-message').html('<i class="fa fa-check-circle text-success"></i> ' + response.data.length + ' batch(es) found. Click a batch to add.');
                    } else {
                        $('#product-selection-info').show().find('#product-selection-message').html('<i class="fa fa-exclamation-triangle text-warning"></i> No batches found for this product.');
                        $('#batch-selection-wrapper').hide();
                    }
                },
                error: function(response) {
                    $('#product-selection-info').show().find('#product-selection-message').html('<i class="fa fa-exclamation-circle text-danger"></i> Error loading batches.');
                    console.log('Error loading batches:', response);
                }
            });
        }
        
        // Handle batch button click - add product directly (batch buttons are used instead of dropdown)
        $(document).on('click', '.batch-btn', function() {
            var batchDataStr = $(this).attr('data-batch-data');
            if (batchDataStr && selectedProductId) {
                try {
                    selectedBatchData = JSON.parse(batchDataStr);
                    addProductToInvoice();
                } catch(e) {
                    console.error('Error parsing batch data:', e);
                    Swal.fire({
                        icon: 'error',
                        text: 'Error loading batch data',
                        toast: true,
                        position: 'top-end',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            }
        });
        
        // Function to add product to invoice
        function addProductToInvoice() {
            if (!selectedProductId) {
                return;
            }
            
            var requestData = {
                purchase_entry_id: (selectedBatchData && selectedBatchData.purchase_entry_id) ? selectedBatchData.purchase_entry_id : selectedProductId,
                batch: (selectedBatchData && selectedBatchData.batch) ? selectedBatchData.batch : '',
                currencyId: $('#currency_id').val(),
                exchangeRate: $('#exchange_rate').val()
            };
            
            // Check if product with same batch already exists
            var existingRow = null;
            if (requestData.batch) {
                $("#sortable .item-row").each(function() {
                    var $row = $(this);
                    var batchInput = $row.find('input[name="batch[]"]').val();
                    var purchaseEntryIdInput = $row.find('input[name="purchase_entry_id[]"]').val();
                    if (batchInput === requestData.batch && purchaseEntryIdInput == requestData.purchase_entry_id) {
                        existingRow = $row;
                        return false; // break loop
                    }
                });
            }
            
            if (existingRow && existingRow.length && requestData.batch) {
                // Check if same batch exists
                var existingBatch = existingRow.find('input[name="batch[]"]').val();
                if (existingBatch === requestData.batch) {
                    // Increase quantity
                    let qtyInput = existingRow.find('input.quantity');
                    let currentQty = parseFloat(qtyInput.val()) || 1;
                    qtyInput.val(currentQty + 1).trigger('change');
                    
                    let cost = existingRow.find('input.cost_per_item');
                    let amountHtml = existingRow.find('span.amount-html');
                    let amount = existingRow.find('input.amount');
                    let newAmount = (qtyInput.val() * cost.val());
                    amountHtml.html(newAmount.toFixed(2));
                    amount.val(newAmount.toFixed(2));
                    
                    calculateTotal();
                    
                    // Reset selection
                    resetProductSelection();
                    
                    Swal.fire({
                        icon: 'success',
                        text: 'Product quantity increased',
                        toast: true,
                        position: 'top-end',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    return;
                }
            }
            
            $.easyAjax({
                url: "{{ route('invoices.add_item') }}",
                type: "GET",
                data: requestData,
                blockUI: true,
                success: function(response) {
                    // Remove any empty/default item rows
                    $("#sortable .item-row").each(function() {
                        var $row = $(this);
                        var hasProduct = $row.find('input[name="product_id[]"]').length && $row.find('input[name="product_id[]"]').val();
                        var $itemNameInput = $row.find('input.item_name');
                        var hasItemName = $itemNameInput.length && $itemNameInput.val() && $itemNameInput.val() !== '';
                        
                        if (!hasProduct && !hasItemName) {
                            $row.remove();
                        }
                    });
                    
                    // Extract and remove script tags before appending
                    var $tempDiv = $('<div>').html(response.view);
                    var scripts = $tempDiv.find('script');
                    var scriptContents = [];
                    scripts.each(function() {
                        scriptContents.push($(this).html());
                        $(this).remove();
                    });
                    
                    // Append HTML without scripts
                    $tempDiv.hide().appendTo("#sortable").fadeIn(500);
                    calculateTotal();
                    
                    // Execute scripts after appending using proper script element creation
                    scriptContents.forEach(function(scriptContent) {
                        if (scriptContent && scriptContent.trim()) {
                            try {
                                var scriptElement = document.createElement('script');
                                scriptElement.textContent = scriptContent;
                                document.body.appendChild(scriptElement);
                                document.body.removeChild(scriptElement);
                            } catch(e) {
                                console.error('Script execution error:', e);
                            }
                        }
                    });
                    
                    var noOfRows = $(document).find('#sortable .item-row').length;
                    var i = $(document).find('.item_name').length - 1;
                    
                    // Update tax select name if it exists (for hidden select)
                    var $taxSelect = $(document).find('#sortable .item-row:nth-child(' + noOfRows + ') .tax-select-hidden');
                    if ($taxSelect.length) {
                        $taxSelect.attr('name', 'taxes[' + i + '][]');
                    }
                    
                    // Initialize batch dropdown if exists
                    var $batchSelect = $(document).find('#sortable .item-row:nth-child(' + noOfRows + ') .purchase-batch-select');
                    if ($batchSelect.length) {
                        $batchSelect.selectpicker();
                    }
                    
                    // Set batch if provided
                    if (requestData.batch) {
                        var $batchInput = $(document).find('#sortable .item-row:nth-child(' + noOfRows + ') input[name="batch[]"]');
                        if ($batchInput.length) {
                            $batchInput.val(requestData.batch);
                        }
                        if ($batchSelect.length) {
                            $batchSelect.val(requestData.batch).selectpicker('refresh');
                            $batchSelect.trigger('change');
                        }
                    }
                    
                    // Initialize quantity change handler
                    var $newRow = $(document).find('#sortable .item-row:nth-child(' + noOfRows + ')');
                    $newRow.find('input.quantity').on('change', function() {
                        var quantity = parseFloat($(this).val()) || 1;
                        var cost = parseFloat($newRow.find('input.cost_per_item').val()) || 0;
                        var amount = quantity * cost;
                        $newRow.find('input.amount').val(amount.toFixed(2));
                        $newRow.find('span.amount-html').html(amount.toFixed(2));
                        calculateTotal();
                    });
                    
                    // Reset selection
                    resetProductSelection();
                    
                    Swal.fire({
                        icon: 'success',
                        text: 'Product added successfully',
                        toast: true,
                        position: 'top-end',
                        timer: 2000,
                        showConfirmButton: false
                    });
                },
                error: function(response) {
                    Swal.fire({
                        icon: 'error',
                        text: (response.responseJSON && response.responseJSON.message) ? response.responseJSON.message : 'Error adding product to invoice',
                        toast: true,
                        position: 'top-end',
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            });
        }
        
        // Function to reset product selection
        function resetProductSelection() {
            selectedProductId = null;
            selectedBatchData = null;
            $('#add-products').val('').selectpicker('refresh');
            $('#batch-buttons-container').html('');
            $('#batch-selection-wrapper').slideUp(300);
            $('#product-selection-info').hide();
        }

        // Handle client selection - update hidden field and reload products
        $('#client_list_id').on('changed.bs.select change', function() {
            var id = $(this).val();
            $('#client_id').val(id || ''); // Update hidden client_id field
            changeClient(id);
        });
        
        // Also handle if client_id is set directly
        if ($('#client_id').val()) {
            var clientId = $('#client_id').val();
            if ($('#client_list_id').length && $('#client_list_id').val() != clientId) {
                $('#client_list_id').val(clientId).selectpicker('refresh');
            }
        }

        // Handle stockist selection for CFA/Distributor
        // Use both change and changed.bs.select events for selectpicker compatibility
        $(document).on('change', '#stockist_id', function() {
            var id = $(this).val();
            changeStockist(id);
        });
        
        $(document).on('changed.bs.select', '#stockist_id', function() {
            var id = $(this).val();
            changeStockist(id);
        });

        function changeStockist(id) {
            if (id == '' || id == null) {
                // Hide stockist details section
                $('#stockist_details_section').hide();
                
                // Clear addresses if no stockist selected
                var emptyMsg = "<span class='text-lightest'>@lang('messages.selectCustomerForBillingAddress')</span>";
                $('#client_billing_address').html(emptyMsg).removeClass('d-none');
                $('#stockist_billing_address').html('<p class="f-15 mb-0">' + emptyMsg + '</p>');
                $('#client_billing_address_editable').addClass('d-none');
                $('#client_shipping_address').html(
                    '<a href="javascript:;" class="" id="show-shipping-field"><i class="f-12 mr-2 fa fa-plus"></i>' + translations.addShippingAddress + '</a>'
                );
                $('#stockist_shipping_address').html('<p class="f-15 mb-0">' + emptyMsg + '</p>');
                return;
            }

            var url = "{{ route('stockists.ajax_details', ':id') }}";
            url = url.replace(':id', id);
            var token = "{{ csrf_token() }}";

            $.easyAjax({
                url: url,
                container: '#saveInvoiceForm',
                type: "POST",
                blockUI: true,
                data: {
                    _token: token
                },
                success: function(response) {
                    if (response.status == 'success') {
                        if (response.data !== null && response.data) {
                            var stockist = response.data;
                            
                            // Show stockist details section
                            $('#stockist_details_section').show();
                            
                            // Update Company Name (using shopname)
                            if (stockist.shopname) {
                                $('#stockist_company_name_value').text(stockist.shopname);
                                $('#stockist_company_name').show();
                            } else {
                                $('#stockist_company_name').hide();
                            }
                            
                            // Update Email
                            if (stockist.email) {
                                $('#stockist_email_value').text(stockist.email);
                                $('#stockist_email').show();
                            } else {
                                $('#stockist_email').hide();
                            }
                            
                            // Update Mobile
                            if (stockist.mobile) {
                                $('#stockist_mobile_value').text(stockist.mobile);
                                $('#stockist_mobile').show();
                            } else {
                                $('#stockist_mobile').hide();
                            }
                            
                            // Update Address (always show in stockist details section)
                            if (stockist.address && stockist.address.trim() !== '') {
                                // Use plain text, just escape HTML and convert newlines to <br>
                                var addressText = $('<div>').text(stockist.address).html();
                                $('#stockist_address_value').html(addressText.replace(/\n/g, '<br>'));
                            } else {
                                $('#stockist_address_value').text('-');
                            }
                            
                            // Update GST (if available - stockists might not have this)
                            if (stockist.gst_number) {
                                $('#stockist_gst_value').text(stockist.gst_number);
                                $('#stockist_gst').show();
                            } else {
                                $('#stockist_gst').hide();
                            }
                            
                            // Update DL Number (always show, even if empty)
                            if (stockist.dl_number) {
                                $('#stockist_dl_value').text(stockist.dl_number);
                            } else {
                                $('#stockist_dl_value').text('-');
                            }
                            
                            // Update hidden form fields for billing and shipping addresses
                            if ($('#client_billing_address_editable').length) {
                                $('#client_billing_address_editable').val(stockist.address || '');
                            }
                            if ($('#shipping_address').length) {
                                $('#shipping_address').val(stockist.address || '');
                            }
                            if ($('textarea[name="billing_address"]').length) {
                                $('textarea[name="billing_address"]').val(stockist.address || '');
                            }
                            if ($('textarea[name="shipping_address"]').length) {
                                $('textarea[name="shipping_address"]').val(stockist.address || '');
                            }
                        } else {
                            // Hide stockist details section
                            $('#stockist_details_section').hide();
                            
                            // Clear address fields
                            $('#stockist_address_value').text('-');
                            if ($('#client_billing_address_editable').length) {
                                $('#client_billing_address_editable').val('');
                            }
                            if ($('#shipping_address').length) {
                                $('#shipping_address').val('');
                            }
                        }
                    }
                },
                error: function(xhr) {
                    console.error('Error fetching stockist details:', xhr);
                    $('#stockist_details_section').hide();
                    $('#stockist_address_value').text('-');
                }
            });
        }

        function changeClient(id) {
            // Update hidden client_id field
            $('#client_id').val(id || '');

            if (id == '') {
                id = 0;
            }

            var url = {!! json_encode(route('clients.project_list', ':id')) !!};
            url = url.replace(':id', id);
            var token = {!! json_encode(csrf_token()) !!};

            $.easyAjax({
                url: url,
                container: '#saveInvoiceForm',
                type: "POST",
                blockUI: true,
                data: {
                    _token: token
                },
                success: function(response) {
                    if (response.status == 'success') {
                        $('#project_id').html(response.data);
                        $('#project_id').selectpicker('refresh');
                    }
                }
            });

            var url = {!! json_encode(route('clients.ajax_details', ':id')) !!};
            url = url.replace(':id', id);

            $.easyAjax({
                url: url,
                container: '#saveInvoiceForm',
                type: "POST",
                blockUI: true,
                data: {
                    _token: token
                },
                success: function(response) {
                    if (response.status == 'success') {
                        if (response.data !== null) {
                            $('#client_billing_address').html(nl2br(response.data.client_details
                                .address));
                            $('#add-shipping-field').addClass('d-none');
                            $('#client_shipping_address').removeClass('d-none');

                            if (response.data.client_details.address) {
                                $('#client_billing_address').html(nl2br(response.data.client_details.address)).removeClass('d-none');
                                $('#client_billing_address_editable').addClass('d-none');
                            } else {
                                $('#client_billing_address').html(
                                    "<span class='text-lightest'>" + translations.selectCustomerForBilling + "</span>"
                                );
                                $('#client_billing_address_editable').addClass('d-none');
                            }

                            if (response.data.client_details.shipping_address === null) {
                                var addShippingLink =
                                    '<a href="javascript:;" class="" id="show-shipping-field"><i class="f-12 mr-2 fa fa-plus"></i>' + translations.addShippingAddress + '</a>';
                                $('#client_shipping_address').html(addShippingLink);
                            } else {
                                $('#client_shipping_address').html(nl2br(response.data
                                    .client_details
                                    .shipping_address));
                            }

                        } else {
                            $('#client_billing_address').html(
                                "<span class='text-lightest'>" + translations.selectCustomerForBilling + "</span>"
                            ).removeClass('d-none');
                            $('#client_billing_address_editable').addClass('d-none');
                        }
                    } else {
                        var addShippingLink =
                            '<a href="javascript:;" class="" id="show-shipping-field"><i class="f-12 mr-2 fa fa-plus"></i>' + translations.addShippingAddress + '</a>';
                        $('#client_shipping_address').html(addShippingLink);
                    }
                    
                    // Reload products when client changes (products may differ for different clients)
                    loadConsolidatedProducts();
                }
            });

        }

        $('body').on('click', '#show-shipping-field', function() {
            $('#add-shipping-field').removeClass('d-none');
            $('#client_shipping_address').addClass('d-none');
        });

        const resetAddProductButton = function() {
            $("#add-products").val('').selectpicker("refresh");
            // Batch buttons container is cleared in resetProductSelection()
        };

        // Note: Product selection is now handled by the unified system above (lines ~1500-1700)
        // This handler is kept for backward compatibility but the new system takes precedence
        // The new system shows batch dropdown first, then adds product when batch is selected

        $(".itemOldImage").next(".dropify-clear").trigger("click");

        var file = $('#sortable .dropify').dropify({
            messages: dropifyMessages
        });

        file.on("dropify.afterClear", function(event, element) {
            var elementID = element.element.id;
            var elementName = element.element.name;
            var elementIndex = element.element.dataset.index;
            if (elementName.indexOf("[]") > -1) {
                elementName = elementName.replace("[]", "");
            }
            if ($("#" + elementID + "_delete").length == 0) {
                $("#" + elementID).after(
                    '<input type="hidden" name="' +
                    elementName +
                    '_delete[' + elementIndex + ']" id="' +
                    elementID +
                    '_delete" value="yes">'
                );
            }
        });

        // Legacy addProduct function - kept for backward compatibility
        // New system uses addProductToInvoice() function above
        function addProduct(id) {
            // Check if this is using new consolidated system
            var $option = $('#add-products option[value="' + id + '"]');
            var productData = $option.data('product-data');
            
            if (productData) {
                // Use new system
                populateInvoiceBatchDropdown(productData);
                return;
            }
            
            // Legacy fallback for old product structure
            var invoiceItemId = $option.data('invoice-item-id');
            var purchaseEntryId = $option.data('purchase-entry-id');
            
            // Prepare the request data
            var requestData = {
                id: invoiceItemId || purchaseEntryId || id,
                currencyId: $('#currency_id').val(),
                exchangeRate: $('#exchange_rate').val()
            };
            
            // Add invoice_item_id if it's an invoice item
            if (invoiceItemId) {
                requestData.invoice_item_id = invoiceItemId;
            }

            var existingRow = $(`input[name="product_id[]"][value="${id}"]`).closest('.item-row');

            if (existingRow.length) {
                // Increase quantity
                let qtyInput = existingRow.find('input.quantity');
                let currentQty = parseFloat(qtyInput.val());
                qtyInput.val(currentQty + 1).trigger('change');

                let cost = existingRow.find('input.cost_per_item');
                let amountHtml = existingRow.find('span.amount-html');
                let amount = existingRow.find('input.amount');
                let newAmount = (qtyInput.val() * cost.val());
                amountHtml.html(newAmount).trigger('change');
                amount.val(newAmount).trigger('change');

                calculateTotal();
                return;
            }

            var currencyId = $('#currency_id').val();
            var exchangeRate = $('#exchange_rate').val();
            
            // Prepare the request data - use invoice_item_id if available, otherwise use purchase_entry_id or id
            var requestId = invoiceItemId || purchaseEntryId || id;

            $.easyAjax({
                url: "{{ route('invoices.add_item') }}",
                type: "GET",
                data: {
                    id: requestId,
                    invoice_item_id: invoiceItemId || null,
                    currencyId: currencyId,
                    exchangeRate: exchangeRate
                },
                blockUI: true,
                success: function(response) {
                    // Remove any empty/default item rows before adding new one
                    $("#sortable .item-row").each(function() {
                        var $row = $(this);
                        var hasProduct = $row.find('input[name="product_id[]"]').length && $row.find('input[name="product_id[]"]').val();
                        var $itemNameInput = $row.find('input.item_name');
                        var $itemNameSelect = $row.find('select.item_name');
                        var hasItemName = false;
                        
                        if ($itemNameInput.length) {
                            hasItemName = $itemNameInput.val() && $itemNameInput.val() !== '';
                        } else if ($itemNameSelect.length) {
                            hasItemName = $itemNameSelect.val() && $itemNameSelect.val() !== '';
                        }
                        
                        // If row has no product and no item name, it's an empty default row - remove it
                        if (!hasProduct && !hasItemName) {
                            $row.remove();
                        }
                    });
                    // Extract and remove script tags before appending
                    var $tempDiv = $('<div>').html(response.view);
                    var scripts = $tempDiv.find('script');
                    var scriptContents = [];
                    scripts.each(function() {
                        scriptContents.push($(this).html());
                        $(this).remove();
                    });
                    
                    // Append HTML without scripts
                    $tempDiv.hide().appendTo("#sortable").fadeIn(500);
                    calculateTotal();

                    // Execute scripts after appending using proper script element creation
                    scriptContents.forEach(function(scriptContent) {
                        if (scriptContent && scriptContent.trim()) {
                            try {
                                var scriptElement = document.createElement('script');
                                scriptElement.textContent = scriptContent;
                                document.body.appendChild(scriptElement);
                                document.body.removeChild(scriptElement);
                            } catch(e) {
                                console.error('Script execution error:', e);
                            }
                        }
                    });

                    var noOfRows = $(document).find('#sortable .item-row').length;
                    var i = $(document).find('.item_name').length - 1;
                    
                    // Update tax select name if it exists (for hidden select)
                    var $taxSelect = $(document).find('#sortable .item-row:nth-child(' + noOfRows + ') .tax-select-hidden');
                    if ($taxSelect.length) {
                        $taxSelect.attr('name', 'taxes[' + i + '][]');
                    }
                    
                    // Initialize batch dropdown selectpicker if it exists
                    var $batchSelect = $(document).find('#sortable .item-row:nth-child(' + noOfRows + ') .purchase-batch-select');
                    if ($batchSelect.length) {
                        $batchSelect.selectpicker();
                    }
                    
                    // Initialize quantity change handler for new row
                    var $newRow = $(document).find('#sortable .item-row:nth-child(' + noOfRows + ')');
                    $newRow.find('input.quantity').on('change', function() {
                        var quantity = parseFloat($(this).val()) || 1;
                        var cost = parseFloat($newRow.find('input.cost_per_item').val()) || 0;
                        var amount = quantity * cost;
                        $newRow.find('input.amount').val(amount.toFixed(2));
                        $newRow.find('span.amount-html').html(amount.toFixed(2));
                        calculateTotal();
                    });
                    
                    $(document).find('#dropify' + i).dropify({
                        messages: dropifyMessages
                    });
                }
            });
        }

        // OLD ADD ITEM FUNCTIONALITY DISABLED - Using new product selection flow
        // Products are now added via: Select Product → Select Batch → Click Add Button
        $(document).on('click', '#add-item', function(e) {
            e.preventDefault();
            e.stopPropagation();
            Swal.fire({
                icon: 'info',
                text: 'Please use the product selection section above to add products',
                toast: true,
                position: 'top-end',
                timer: 3000,
                showConfirmButton: false
            });
            return false;
            
            // OLD CODE BELOW - DISABLED
            if (false) {
            var i = $(document).find('.item_name').length;
            var item =
                ` <div class="d-flex px-4 py-3 c-inv-desc item-row">
                <div class="d-flex align-items-center">
                    <span class="ui-icon ui-icon-arrowthick-2-n-s mr-2"></span>
                    <input type="hidden" name="sort_order[]"
                            value="${i+1}">
                </div>
                <div class="c-inv-desc-table w-100 d-lg-flex d-md-flex d-block">
                <table width="100%">
                <tbody>
                <tr class="text-dark-grey font-weight-bold f-14">
                <td width="{{ $invoiceSetting->hsn_sac_code_show ? '40%' : '50%' }}" class="border-0 inv-desc-mbl btlr">@lang("app.description")</td>
                <td><input type="text" name="scheme[]" class="border-0 form-control"></td>
                <td><input type="text" name="pack[]" class="border-0 form-control"></td>
                <td><input type="text" name="mfr[]" class="border-0 form-control"></td>
                <td><input type="text" name="batch[]" class="border-0 form-control"></td>
                <td><input type="date" name="exp[]" class="border-0 form-control"></td>

                `;

            if (hsn_status == 1) {
                item += `<td width="10%" class="border-0" align="right">@lang("app.hsnSac")</td>`;
            }

            item += `
                    <td><input type="number" name="mrp[]" class="border-0 text-right form-control"></td>
                    <td><input type="number" name="pts[]" class="border-0 text-right form-control"></td>
                    <td><input type="number" name="ptr[]" class="border-0 text-right form-control"></td>
                    <td><input type="number" name="dis[]" class="border-0 text-right form-control"></td>
                    <td><input type="number" name="sgst[]" class="border-0 text-right form-control"></td>
                    <td><input type="number" name="cgst[]" class="border-0 text-right form-control"></td>
                    <td width="10%" class="border-0" align="right">@lang("modules.invoices.qty")</td>
                    <td width="10%" class="border-0" align="right">@lang("modules.invoices.unitPrice")</td>
                    <td width="13%" class="border-0" align="right">@lang("modules.invoices.tax")</td>
                    <td width="17%" class="border-0 bblr-mbl" align="right">@lang("modules.invoices.amount")</td>
                </tr>
                <tr>
                    <td class="border-bottom-0 btrr-mbl btlr">
                    <input type="text" class="form-control f-14 border-0 w-100 item_name" name="item_name[]" placeholder="@lang("modules.expenses.itemName")">
                    </td>
                    <td class="border-bottom-0 d-block d-lg-none d-md-none">
                    <textarea class="f-14 border-0 w-100 mobile-description form-control" name="item_summary[]" placeholder="@lang("placeholders.invoices.description")"></textarea>
                    </td>
                `;

            if (hsn_status == 1) {
                item += `<td class="border-bottom-0">
                    <input type="text" min="1" class="form-control f-14 border-0 w-100 text-right hsn_sac_code" name="hsn_sac_code[]" >
                    </td>`;
            }
            item += `<td class="border-bottom-0">
                <input type="number" min="1" class="form-control f-14 border-0 w-100 text-right quantity mt-3" value="1" name="quantity[]">
                <select class="text-dark-grey float-right border-0 f-12" name="unit_id[]">
                    @foreach ($units as $unit)
                        <option @selected ($unit->default == 1) value="{{ $unit->id }}">{{ $unit->unit_type }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="product_id[]" value="">
                </td>
                <td class="border-bottom-0">
                <input type="number" min="1" class="f-14 border-0 w-100 text-right cost_per_item" placeholder="0.00" value="0" name="cost_per_item[]">
                </td>
                <td class="border-bottom-0">
                <div class="select-others height-35 rounded border-0">
                <select id="multiselect${i}" name="taxes[${i}][]" multiple="multiple" class="select-picker type customSequence" data-size="3">
            @foreach ($taxes as $tax)
                <option data-rate="{{ $tax->rate_percent }}" data-tax-text="{{ $tax->tax_name .':'. $tax->rate_percent }}%" value="{{ $tax->id }}">
                    {{ $tax->tax_name }}:{{ $tax->rate_percent }}%</option>
            @endforeach

                </select>
                </div>
                </td>
                <td rowspan="2" align="right" valign="top" class="bg-amt-grey btrr-bbrr">
                <span class="amount-html">0.00</span>
                <input type="hidden" class="amount" name="amount[]" value="0">
                </td>
                </tr>
                <tr class="d-none d-md-table-row d-lg-table-row">
                    <td colspan="{{ $invoiceSetting->hsn_sac_code_show ? 4 : 3 }}" class="dash-border-top bblr">
                        <textarea class="f-14 border-0 w-100 desktop-description form-control" name="item_summary[]" placeholder="@lang("placeholders.invoices.description")"></textarea>
                    </td>
                    <td class="border-left-0">
                        <input type="file" class="dropify" id="dropify${i}" name="invoice_item_image[]" data-allowed-file-extensions="png jpg jpeg bmp" data-messages-default="test" data-height="70" />
                        <input type="hidden" name="invoice_item_image_url[]">
                    </td>
                </tr>
                </tbody>
                </table>
                </div>
                <a href="javascript:;" class="d-flex align-items-center justify-content-center ml-3 remove-item"><i class="fa fa-times-circle f-20 text-lightest"></i></a>
                </div>`;
            $(item).hide().appendTo("#sortable").fadeIn(500);
            $('#multiselect' + i).selectpicker();

            $('#dropify' + i).dropify({
                messages: dropifyMessages
            });

        });

        $('#saveInvoiceForm').on('click', '.remove-item', function() {
            $(this).closest('.item-row').fadeOut(300, function() {
                $(this).remove();
                $('select.customSequence').each(function(index) {
                    $(this).attr('name', 'taxes[' + index + '][]');
                    $(this).attr('id', 'multiselect' + index + '');
                });
                calculateTotal();
            });
        });

        $('.save-form').click(function() {
            let totalAmt = $('.total-field').val();
            var type = $(this).data('type');

            if((type == 'send' || type == 'mark_as_send') && totalAmt == 0) {
                Swal.fire({
                    title: "@lang('messages.sweetAlertTitle')",
                    text: "@lang('messages.markAsPaid')",
                    icon: 'warning',
                    showCancelButton: true,
                    focusConfirm: false,
                    confirmButtonText: "@lang('app.yes')",
                    cancelButtonText: "@lang('app.no')",
                    customClass: {
                        confirmButton: 'btn btn-primary mr-3',
                        cancelButton: 'btn btn-secondary'
                    },
                    showClass: {
                        popup: 'swal2-noanimation',
                        backdrop: 'swal2-noanimation'
                    },
                    buttonsStyling: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        saveForm(type, 'direct');
                    }
                });
            } else {
                saveForm(type, 'direct');
            }

        });

        $('#saveInvoiceForm').on('click', '.remove-item', function() {
            $(this).closest('.item-row').fadeOut(300, function() {
                $(this).remove();
                $('select.customSequence').each(function(index) {
                    $(this).attr('name', 'taxes[' + index + '][]');
                    $(this).attr('id', 'multiselect' + index + '');
                });
                calculateTotal();
            });
        });

        $('#saveInvoiceForm').on('keyup', '.quantity,.cost_per_item,.item_name, .discount_value', function() {
            var quantity = $(this).closest('.item-row').find('.quantity').val();
            var perItemCost = $(this).closest('.item-row').find('.cost_per_item').val();
            var amount = (quantity * perItemCost);

            $(this).closest('.item-row').find('.amount').val(decimalupto2(amount));
            $(this).closest('.item-row').find('.amount-html').html(decimalupto2(amount));

            calculateTotal();
        });

        $('#saveInvoiceForm').on('change', '.type, #discount_type, #calculate_tax', function() {
            var quantity = $(this).closest('.item-row').find('.quantity').val();
            var perItemCost = $(this).closest('.item-row').find('.cost_per_item').val();
            var amount = (quantity * perItemCost);

            $(this).closest('.item-row').find('.amount').val(decimalupto2(amount));
            $(this).closest('.item-row').find('.amount-html').html(decimalupto2(amount));

            calculateTotal();
        });

        $('#saveInvoiceForm').on('input', '.quantity', function() {
            var quantity = $(this).closest('.item-row').find('.quantity').val();
            var perItemCost = $(this).closest('.item-row').find('.cost_per_item').val();
            var amount = (quantity * perItemCost);

            $(this).closest('.item-row').find('.amount').val(decimalupto2(amount));
            $(this).closest('.item-row').find('.amount-html').html(decimalupto2(amount));

            calculateTotal();
        });

        calculateTotal();

        init(RIGHT_MODAL);

        if (defaultClient != "" && isClient == false) {
            changeClient(defaultClient);
        }
        
        // Load consolidated products after everything is initialized
        setTimeout(function() {
            if (typeof loadConsolidatedProducts === 'function') {
                loadConsolidatedProducts();
            }
        }, 1000);
    }); // End of document.ready


    function saveForm(type, exceed){
            $('#doItLater').val(exceed);
            if (KTUtil.isMobileDevice()) {
                $('.desktop-description').remove();
            } else {
                $('.mobile-description').remove();
            }

            calculateTotal();

            var discount = $('#discount_amount').html();
            var total = $('.sub-total-field').val();

            if (parseFloat(discount) > parseFloat(total)) {
                Swal.fire({
                    icon: 'error',
                    text: "{{ __('messages.discountExceed') }}",

                    customClass: {
                        confirmButton: 'btn btn-primary',
                    },
                    showClass: {
                        popup: 'swal2-noanimation',
                        backdrop: 'swal2-noanimation'
                    },
                    buttonsStyling: false
                });
                return false;
            }

            $.easyAjax({
                url: "{{ route('invoices.store') }}" + "?type=" + type,
                container: '#saveInvoiceForm',
                type: "POST",
                blockUI: true,
                redirect: true,
                file: true,  // Commented so that we dot get error of Input variables exceeded 1000
                data: $('#saveInvoiceForm').serialize(),
                success: function(response) {
                    $(MODAL_DEFAULT).modal('hide');
                    if (response.status == 'error' && response.showValue === true && exceed == 'direct') {
                        const productIDs = response.data;
                        $('#do_it_later').val('true');
                        const url = "{{ route('invoices.committed_modal') }}" + "?products=" + productIDs + "&type=" + type ;

                        $(MODAL_DEFAULT + ' ' + MODAL_HEADING).html('...');
                        $.ajaxModal(MODAL_DEFAULT, url);
                    }

                    if (response.status === 'success') {
                        if (typeof invoiceDropzone !== 'undefined' && invoiceDropzone.getQueuedFiles().length > 0) {
                            invoiceID = response.invoiceID;
                            $('#invoiceID').val(response.invoiceID);
                            (response.add_more == true) ? localStorage.setItem("redirect_invoice", window.location.href) : localStorage.setItem("redirect_invoice", response.redirectUrl);
                            invoiceDropzone.processQueue();
                        }
                        else {
                            window.location.href = response.redirectUrl;
                        }
                    }
                }
            })
        }


    function ucWord(str){
            str = str.toLowerCase().replace(/\b[a-z]/g, function(letter) {
                return letter.toUpperCase();
            });
            return str;
        }

    $('#currency_id').change(function() {
        var curId = $(this).val();
        var companyCurrencyName = {!! json_encode($companyCurrency->currency_code ?? '') !!};
        var currentCurrencyName = $('#currency_id option:selected').attr('data-currency-code');
        var companyCurrency = {!! json_encode($companyCurrency->id ?? '') !!};

        if(curId == companyCurrency){
            $('#exchange_rate').prop('readonly', true);
        } else{
            $('#exchange_rate').prop('readonly', false);
        }
        var token = "{{ csrf_token() }}";

        $.easyAjax({
            url: {!! json_encode(route('payments.account_list')) !!},
            container: '#saveInvoiceForm',
            type: "GET",
            blockUI: true,
            data: { 'curId' : curId , _token: token},
            success: function(response) {
                if (response.status == 'success') {
                    $('#bank_account_id').html(response.data);
                    $('#bank_account_id').selectpicker('refresh');
                    $('#exchange_rate').val(response.exchangeRate);
                    let currencyExchange = (companyCurrencyName != currentCurrencyName) ? '( '+currentCurrencyName+' ' + translations.to + ' '+companyCurrencyName+' )' : '';
                    $('#currency_exchange').html(currencyExchange);
                }
            }
        });
    });

    $('input[type=checkbox][name=payment_status]').change(function() {
        if ($(this).is(":checked")) {
            $(this).val(1);
            $('#add_offline').addClass('d-none');
            $('.payment-types').removeClass('d-none');
        } else {
            $(this).val(0);
            $('#transaction_id').val('');
            $('#add_offline').addClass('d-none');
            $('.payment-types').addClass('d-none');
            $('#payment_gateway_id').val('');
            $('#payment_gateway_id').selectpicker('refresh');
        }
    });

    $('#payment_gateway_id').on('change', function(){
        let val = $(this).val();

        if (val == 'Offline'){
            let url = "{{ route('offline.methods') }}";

            $.easyAjax({
                url : url,
                type : "GET",
                success: function (response) {
                    if (response.status == 'success') {
                        $('#add_offline').removeClass('d-none');
                        var options = [];
                        var rData = [];
                        rData = response.data;
                            $.each(rData, function (index, value) {
                            var selectData = '';
                            if(value.status=='yes'){
                            selectData = '<option value="' + value.id + '">' + value.name + '</option>';
                            }
                            options.push(selectData);
                        });
                        $('#add_offline_methods').html(
                            options);
                        $('#add_offline_methods').selectpicker('refresh');
                    }
                }
            });
        }
        else
        {
            $('#add_offline').addClass('d-none');
        }
    });

</script>
<!--=========================================== sonu ==============================================-->
<script>
    $('#saveInvoiceForm').on('changed.bs.select', '.item_name', function () {

    const selected = $(this).find(':selected');
    const row = $(this).closest('.item-row');

    // Basic product info
    row.find('.scheme').val(selected.data('scheme') || '');
    row.find('.pack').val(selected.data('pack') || '');
    row.find('.mfr').val(selected.data('mfr') || '');
    row.find('.hsn_sac_code').val(selected.data('hsn') || '');

    // Pricing
    row.find('.mrp').val(selected.data('mrp') || 0);
    row.find('.pts').val(selected.data('pts') || 0);
    row.find('.ptr').val(selected.data('ptr') || 0);
    row.find('.discount').val(selected.data('dis') || 0);

    // Auto-set unit price (PTR preferred)
    row.find('.cost_per_item')
        .val(selected.data('ptr') || selected.data('pts') || 0)
        .trigger('keyup'); // recalc amount

});

</script>
@endif
