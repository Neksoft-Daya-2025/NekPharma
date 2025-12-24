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
                    <a href="{{ route('cfa-distributor-invoices.edit', $invoice->id) }}" class="btn btn-secondary">
                        <i class="fa fa-edit"></i> @lang('app.edit')
                    </a>
                @endif
                <a href="{{ route('invoices.download', $invoice->id) }}" class="btn btn-primary" target="_blank">
                    <i class="fa fa-download"></i> @lang('app.download')
                </a>
                <button onclick="window.print()" class="btn btn-info">
                    <i class="fa fa-print"></i> @lang('app.print')
                </button>
            </div>
        </div>

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
            .no-print {
                display: none !important;
            }
            body {
                background: white;
            }
            .card {
                border: none;
                box-shadow: none;
            }
            .card.invoice .card-body {
                padding: 0 !important;
                display: block !important;
            }
        }
    </style>
@endsection

