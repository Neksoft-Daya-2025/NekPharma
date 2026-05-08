@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            @include('sfc::sfc-charts.ajax.create')
        </div>
    </div>
@endsection

