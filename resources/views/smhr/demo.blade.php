@extends('layouts.app-smhr')
@section('content')
    <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
        <div class="my-auto mb-2">
            <h2 class="mb-1">Smart HR Layout Demo</h2>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="ti ti-smart-home"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">SMHR Demo</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <p class="mb-0">This page uses the Smart HR template layout. If you see the sidebar, topbar, and this card styled correctly, Phase 1 is complete.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
