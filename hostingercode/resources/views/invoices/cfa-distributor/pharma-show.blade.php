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
            <div class="custom-alerts alert alert-danger fade in">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true"></button>
                {!! $message !!}
            </div>
            <?php Session::forget('error'); ?>
        @endif

        <!-- Action buttons -->
        <div class="d-flex justify-content-end mb-3 no-print">
            <div class="btn-group">
                @if ($editInvoicePermission == 'all' || ($editInvoicePermission == 'added' && ($invoice->added_by == user()->id)))
                    @php
                        // Check if this is an IGST invoice
                        $isIGST = request('type') == 'igst' || request()->has('igst');
                        if (!$isIGST && $invoice->note && strpos($invoice->note, '<!--IGST_INVOICE-->') !== false) {
                            $isIGST = true;
                        }
                    @endphp
                    <a href="{{ route('cfa-distributor-invoices.edit', $invoice->id) }}{{ $isIGST ? '?type=igst' : '' }}" class="btn btn-secondary">
                        <i class="fa fa-edit"></i> @lang('app.edit')
                    </a>
                @endif
                {{-- Download button commented out --}}
                {{-- <a href="{{ route('invoices.download', $invoice->id) }}" class="btn btn-primary" target="_blank">
                    <i class="fa fa-download"></i> @lang('app.download')
                </a> --}}
                <button onclick="printInvoice()" class="btn btn-info">
                    <i class="fa fa-print"></i> @lang('app.print')
                </button>
            </div>
        </div>
        
        <!-- Print function - inline for immediate availability -->
        <script>
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
                    
                    // Explicitly hide the Download button and all buttons
                    var downloadButton = document.querySelector('a.btn-primary[href*="download"]');
                    if (downloadButton) {
                        downloadButton.style.display = 'none';
                        downloadButton.style.visibility = 'hidden';
                        downloadButton.style.position = 'absolute';
                        downloadButton.style.left = '-9999px';
                        downloadButton.style.opacity = '0';
                        downloadButton.style.width = '0';
                        downloadButton.style.height = '0';
                        downloadButton.style.overflow = 'hidden';
                    }
                    
                    // Hide all buttons in btn-group
                    var btnGroup = document.querySelector('.btn-group');
                    if (btnGroup) {
                        btnGroup.style.display = 'none';
                        btnGroup.style.visibility = 'hidden';
                        btnGroup.style.position = 'absolute';
                        btnGroup.style.left = '-9999px';
                        btnGroup.style.opacity = '0';
                        btnGroup.style.width = '0';
                        btnGroup.style.height = '0';
                        btnGroup.style.overflow = 'hidden';
                    }
                    
                    // Hide the no-print container
                    var noPrintContainer = document.querySelector('.no-print');
                    if (noPrintContainer) {
                        noPrintContainer.style.display = 'none';
                        noPrintContainer.style.visibility = 'hidden';
                        noPrintContainer.style.position = 'absolute';
                        noPrintContainer.style.left = '-9999px';
                        noPrintContainer.style.opacity = '0';
                        noPrintContainer.style.width = '0';
                        noPrintContainer.style.height = '0';
                        noPrintContainer.style.overflow = 'hidden';
                    }
                    
                    // Hide all buttons
                    var allButtons = document.querySelectorAll('.btn, a.btn, button.btn, .btn-primary, .btn-secondary, .btn-info');
                    allButtons.forEach(function(btn) {
                        if (btn && !btn.closest('.card.invoice')) {
                            btn.style.display = 'none';
                            btn.style.visibility = 'hidden';
                            btn.style.position = 'absolute';
                            btn.style.left = '-9999px';
                            btn.style.opacity = '0';
                            btn.style.width = '0';
                            btn.style.height = '0';
                            btn.style.overflow = 'hidden';
                        }
                    });
                    
                    // Hide all UI elements before printing
                    var selectors = [
                        'header', 'nav', 'aside', '.sidebar', '.main-header', '.main-sidebar',
                        '.content-header', '.navbar', '.footer', '.breadcrumb', '.page-title',
                        '.page-heading', '.action-buttons', '.topbar', '.sidebar-menu',
                        '.alert', '.custom-alerts', '.preloader-container', '.spinner-border'
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
                    
                    // Hide everything in content-wrapper except invoice
                    var contentWrapper = document.querySelector('.content-wrapper');
                    if (contentWrapper) {
                        var children = contentWrapper.children;
                        for (var i = 0; i < children.length; i++) {
                            var child = children[i];
                            if (!child.classList.contains('card') || !child.classList.contains('invoice')) {
                                child.style.display = 'none';
                                child.style.visibility = 'hidden';
                                child.style.position = 'absolute';
                                child.style.left = '-9999px';
                                child.style.opacity = '0';
                                child.style.width = '0';
                                child.style.height = '0';
                                child.style.overflow = 'hidden';
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
                @include('invoices.cfa-distributor.pharma-invoice')
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
            /* Force white background on everything */
            html,
            html *,
            body,
            body * {
                background-color: white !important;
                background: white !important;
            }
            
            /* Specifically target the fullscreen section and remove gray background */
            section#fullscreen,
            section#fullscreen.bg-additional-grey,
            section#fullscreen.main-container,
            section#fullscreen.main-container.bg-additional-grey,
            .bg-additional-grey,
            .main-container.bg-additional-grey,
            body section#fullscreen,
            body section#fullscreen.bg-additional-grey,
            body .bg-additional-grey,
            html body section#fullscreen,
            html body section#fullscreen.bg-additional-grey,
            html body .bg-additional-grey {
                background-color: white !important;
                background: white !important;
                background-image: none !important;
            }
            
            /* Hide EVERYTHING by default - most aggressive approach */
            html, body, body * {
                visibility: hidden !important;
            }
            
            /* Show only invoice content */
            html body .card.invoice,
            html body .card.invoice *,
            html body .pharma-invoice-body,
            html body .pharma-invoice-body *,
            html body .invoice-container,
            html body .invoice-container * {
                visibility: visible !important;
                display: block !important;
                background-color: white !important;
                background: white !important;
            }
            
            /* Hide ALL layout and UI elements */
            html body .body-wrapper,
            html body section#fullscreen,
            html body section#fullscreen.bg-additional-grey,
            html body .main-container,
            html body .main-container.bg-additional-grey,
            html body .preloader-container,
            html body .spinner-border,
            html body header,
            html body nav,
            html body aside,
            html body .sidebar,
            html body .main-header,
            html body .main-sidebar,
            html body .content-header,
            html body .navbar,
            html body .navbar-left,
            html body .navbar-right,
            html body .footer,
            html body .breadcrumb,
            html body .page-title,
            html body .page-heading,
            html body .action-buttons,
            html body .btn-group,
            html body .btn-group .btn,
            html body .btn-group .btn-primary,
            html body .btn-group .btn-secondary,
            html body .btn-group .btn-info,
            html body a.btn,
            html body a.btn-primary,
            html body a.btn-secondary,
            html body button.btn,
            html body button.btn-info,
            html body .topbar,
            html body .sidebar-menu,
            html body .alert,
            html body .custom-alerts,
            html body .no-print,
            html body [class*="topbar"],
            html body [class*="sidebar"],
            html body [id*="topbar"],
            html body [id*="sidebar"],
            html body [id*="header"],
            html body [id*="nav"],
            html body .content-wrapper > .alert,
            html body .content-wrapper > .d-flex,
            html body .content-wrapper > .d-flex.no-print,
            html body .content-wrapper > .mb-3,
            html body .content-wrapper > .mb-3.no-print,
            html body .content-wrapper > .btn-group,
            html body .content-wrapper > div:not(.card.invoice) {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                width: 0 !important;
                overflow: hidden !important;
                position: absolute !important;
                left: -9999px !important;
                opacity: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* Specifically hide the no-print container and all its children */
            html body .no-print,
            html body .no-print *,
            html body .d-flex.no-print,
            html body .d-flex.no-print *,
            html body .mb-3.no-print,
            html body .mb-3.no-print * {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                width: 0 !important;
                overflow: hidden !important;
                position: absolute !important;
                left: -9999px !important;
                opacity: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* Reset body and wrappers - ensure white background */
            html {
                background: white !important;
                background-color: white !important;
            }
            
            html body {
                background: white !important;
                background-color: white !important;
                padding: 0 !important;
                margin: 0 !important;
                visibility: visible !important;
            }
            
            html body .body-wrapper {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                background-color: white !important;
                border: none !important;
                display: block !important;
                visibility: visible !important;
                position: relative !important;
            }
            
            html body section#fullscreen,
            html body section#fullscreen.bg-additional-grey,
            html body .main-container,
            html body .main-container.bg-additional-grey {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                background-color: white !important;
                border: none !important;
                display: block !important;
                visibility: visible !important;
                position: relative !important;
            }
            
            html body .content-wrapper {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
                background-color: white !important;
                border: none !important;
                display: block !important;
                visibility: visible !important;
                position: relative !important;
            }
            
            /* Hide everything in content-wrapper except invoice card */
            html body .content-wrapper > *:not(.card.invoice) {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                width: 0 !important;
            }
            
            html body .card {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
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

