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
                                        <div class="invoice-actions">
                                            @if (!in_array('client', user_roles()))
                                                <a href="{{ route('invoices.download', [$invoice->id]) }}?type=igst" class="btn btn-sm btn-primary">
                                                    <i class="fa fa-download"></i> @lang('app.download')
                                                </a>
                                            @endif
                                            @if (!empty($canEditCfaStockistInvoice) || $editInvoicePermission == 'all' || ($editInvoicePermission == 'added' && $invoice->added_by == user()->id))
                                                <a href="{{ route('cfa-stockist-invoices.edit', [$invoice->id]) }}?type=igst" class="btn btn-sm btn-secondary">
                                                    <i class="fa fa-edit"></i> @lang('app.edit')
                                                </a>
                                            @endif
                                            @if (!empty($canDeleteCfaStockistInvoice) || $deleteInvoicePermission == 'all' || ($deleteInvoicePermission == 'added' && $invoice->added_by == user()->id))
                                                <a href="javascript:;" class="btn btn-sm btn-danger delete-invoice" data-invoice-id="{{ $invoice->id }}">
                                                    <i class="fa fa-trash"></i> @lang('app.delete')
                                                </a>
                                            @endif
                                        </div>
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
            $(document).ready(function() {
                // Handle delete invoice
                $('.delete-invoice').on('click', function() {
                    var invoiceId = $(this).data('invoice-id');
                    if (confirm('Are you sure you want to delete this invoice?')) {
                        $.easyAjax({
                            url: "{{ route('invoices.destroy', [$invoice->id]) }}",
                            type: "POST",
                            data: { '_method': 'DELETE', '_token': '{{ csrf_token() }}' },
                            success: function(response) {
                                if (response.status == 'success') {
                                    window.location.href = "{{ route('cfa-stockist-invoices.index') }}";
                                }
                            }
                        });
                    }
                });
            });
        </script>

        <!-- Invoice Content -->
        <div class="card border-0 invoice">
            <div class="card-body" style="display: flex; justify-content: center; align-items: flex-start; padding: 20px;">
                @include('invoices.cfa-stockist.igst-invoice')
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
