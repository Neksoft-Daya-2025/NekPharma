@extends('layouts.app')

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        @php
            $addPaymentPermission = user()->permission('add_payments');
            $deleteInvoicePermission = user()->permission('delete_invoices');
            $editInvoicePermission = user()->permission('edit_invoices');
        @endphp

        @if (!in_array('client', user_roles()))
            @if (!is_null($invoice->last_viewed))
                <x-alert type="info">
                    {{$invoice->client->name_salutation}} @lang('app.viewedOn') {{$invoice->last_viewed->timezone(isset($settings) && $settings ? $settings->timezone : company()->timezone)->translatedFormat(isset($settings) && $settings ? $settings->date_format : company()->date_format)}}
                    @lang('app.at') {{$invoice->last_viewed->timezone(isset($settings) && $settings ? $settings->timezone : company()->timezone)->translatedFormat(isset($settings) && $settings ? $settings->time_format : company()->time_format)}}
                    @lang('app.usingIpAddress'):{{$invoice->ip_address}}
                </x-alert>
            @endif
        @endif

        @if ($message = Session::get('success'))
            <div class="alert alert-success alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                <i class="fa fa-check"></i> {!! $message !!}
            </div>
            <?php Session::forget('success'); ?>
        @endif

        @if ($message = Session::get('error'))
            <div class="alert alert-danger alert-dismissable">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                <i class="fa fa-times"></i> {!! $message !!}
            </div>
            <?php Session::forget('error'); ?>
        @endif

        <!-- INVOICE CARD START -->
        <div class="card border-0 invoice">
            <div class="card-body">
                <div class="invoice-table-wrapper">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="invoice-table-header">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <h4 class="invoice-title">@lang('app.invoice')</h4>
                                    </div>
                                    <div class="col-sm-6 text-right">
                                        @if (!in_array('client', user_roles()))
                                            <div class="invoice-actions">
                                                @if ($editInvoicePermission == 'all' || ($editInvoicePermission == 'added' && $invoice->added_by == user()->id))
                                                    <a href="{{ route('cfa-distributor-invoices.edit', [$invoice->id]) }}?type=igst" class="btn btn-sm btn-secondary">
                                                        <i class="fa fa-edit"></i> @lang('app.edit')
                                                    </a>
                                                @endif
                                                <button onclick="printInvoice()" class="btn btn-sm btn-info">
                                                    <i class="fa fa-print"></i> @lang('app.print')
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- INVOICE CARD END -->

        <script>
            // Print function - inline for immediate availability
            function printInvoice() {
                try {
                    // Remove bg-additional-grey background color from section#fullscreen
                    var fullscreenSection = document.getElementById('fullscreen');
                    if (fullscreenSection) {
                        fullscreenSection.style.backgroundColor = 'white';
                        fullscreenSection.style.background = 'white';
                        // Remove the bg-additional-grey class temporarily
                        fullscreenSection.classList.remove('bg-additional-grey');
                    }
                    
                    // Also target by class
                    var bgGreyElements = document.querySelectorAll('.bg-additional-grey');
                    bgGreyElements.forEach(function(el) {
                        el.style.backgroundColor = 'white';
                        el.style.background = 'white';
                    });
                    
                    // Hide all buttons in invoice-actions
                    var invoiceActions = document.querySelector('.invoice-actions');
                    if (invoiceActions) {
                        invoiceActions.style.display = 'none';
                        invoiceActions.style.visibility = 'hidden';
                    }
                    
                    // Hide all UI elements before printing
                    var selectors = [
                        'header', 'nav', 'aside', '.sidebar', '.main-header', '.main-sidebar',
                        '.content-header', '.navbar', '.footer', '.breadcrumb', '.page-title',
                        '.page-heading', '.action-buttons', '.topbar', '.sidebar-menu',
                        '.alert', '.custom-alerts', '.preloader-container', '.spinner-border',
                        '.invoice-table-wrapper', '.invoice-table-header'
                    ];
                    
                    // Hide elements using querySelectorAll
                    selectors.forEach(function(selector) {
                        try {
                            var elements = document.querySelectorAll(selector);
                            elements.forEach(function(el) {
                                if (el && (!el.classList.contains('card') || !el.classList.contains('invoice'))) {
                                    el.style.display = 'none';
                                    el.style.visibility = 'hidden';
                                }
                            });
                        } catch(e) {
                            console.log('Error hiding selector:', selector, e);
                        }
                    });
                    
                    // Hide everything in content-wrapper except invoice card
                    var contentWrapper = document.querySelector('.content-wrapper');
                    if (contentWrapper) {
                        var children = contentWrapper.children;
                        for (var i = 0; i < children.length; i++) {
                            var child = children[i];
                            // Keep only the invoice card that contains the actual invoice content
                            if (!child.classList.contains('card') || !child.querySelector('.pharma-invoice-body, .invoice-container')) {
                                child.style.display = 'none';
                                child.style.visibility = 'hidden';
                            }
                        }
                    }
                    
                    // Small delay to ensure styles are applied, then print
                    setTimeout(function() {
                        window.print();
                    }, 100);
                } catch(error) {
                    console.error('Print error:', error);
                    // Fallback to simple print
                    window.print();
                }
            }
        </script>

        <!-- Invoice Content -->
        <div class="card border-0 invoice">
            <div class="card-body" style="display: flex; justify-content: center; align-items: flex-start; padding: 20px;">
                @include('invoices.cfa-distributor.igst-invoice')
            </div>
        </div>
    </div>
    <!-- CONTENT WRAPPER END -->
@endsection

@section('script')
    <style>
        /* Center align invoice content */
        .card.invoice .card-body {
            display: flex !important;
            justify-content: center !important;
            align-items: flex-start !important;
            padding: 20px !important;
        }
        
        .pharma-invoice-body {
            margin: 0 auto !important;
            width: 100% !important;
            max-width: 297mm !important;
        }
        
        @media print {
            /* Hide everything except invoice */
            body * {
                visibility: hidden;
            }
            
            .card.invoice,
            .card.invoice * {
                visibility: visible;
            }
            
            .card.invoice {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            
            /* Hide action buttons when printing */
            .invoice-actions,
            .invoice-actions * {
                display: none !important;
                visibility: hidden !important;
            }
            
            /* Ensure proper page setup */
            @page {
                margin: 0;
                size: A4 landscape;
            }
            
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                height: 100% !important;
                overflow: visible !important;
            }
            
            .content-wrapper {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                background: white !important;
                background-color: white !important;
            }
            
            html body .pharma-invoice-body {
                position: relative !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                margin: 0 !important;
                display: block !important;
                visibility: visible !important;
                background: white !important;
                background-color: white !important;
            }
            
            html body .card.invoice {
                position: relative !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                margin: 0 !important;
                display: block !important;
                visibility: visible !important;
                background: white !important;
                background-color: white !important;
            }
            
            html body .card.invoice .card-body {
                padding: 0 !important;
                display: block !important;
                margin: 0 !important;
                background: white !important;
                background-color: white !important;
            }
            
            /* Ensure invoice takes full page */
            html body .pharma-invoice-body {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                background: white !important;
                background-color: white !important;
            }
            
            /* Page settings */
            @page {
                margin: 0;
                size: A4 landscape;
            }
        }
    </style>
@endsection
