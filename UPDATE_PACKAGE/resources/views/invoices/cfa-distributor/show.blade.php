@extends('layouts.app')

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        @include('invoices.cfa-distributor.ajax.show')
    </div>
    <!-- CONTENT WRAPPER END -->
@endsection

