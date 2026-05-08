@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        @include('stock-statements.ajax.create')
    </div>
@endsection
