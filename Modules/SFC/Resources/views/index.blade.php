@extends('sfc::layouts.master')

@section('content')
    <h1>Hello World</h1>

    <p>Module: {!! config('sfc.name') !!}</p>
@endsection
