<?php $page = 'ui-rangeslider'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content ">

            <!-- Page Header -->
            <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Range Slider</h2>
                    <nav>
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{url('index')}}"><i class="ti ti-smart-home"></i></a>
                            </li>
                            <li class="breadcrumb-item">
                                Advanced UI
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Range Slider</li>
                        </ol>
                    </nav>
                </div>
                <div class="head-icons ms-2">
                    <a href="javascript:void(0);" class="" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Collapse" id="collapse-header">
                        <i class="ti ti-chevrons-up"></i>
                    </a>
                </div>
            </div>
            <!-- /Page Header -->

            <div class="row">

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Default</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_01">
                        </div>
                    </div>
                </div>
                <!-- /Rangeslider -->

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Min-Max</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_02">
                        </div>
                    </div>
                </div>
                <!-- /Rangeslider -->

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Prefix</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_03">
                        </div>
                    </div>
                </div>
                <!-- /Rangeslider -->

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Range</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_04">
                        </div>
                    </div>
                </div>
                <!-- /Rangeslider -->

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Step</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_05">
                        </div>
                    </div>
                </div>
                <!-- /Rangeslider -->

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Custom Values</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_06">
                        </div>
                    </div>
                </div>
                <!-- /Rangeslider -->

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Modern skin</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_13">
                        </div>
                    </div>
                </div>
                <!-- /Rangeslider -->

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Sharp Skin</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_14">
                        </div>
                    </div>
                </div>
                <!-- /Rangeslider -->

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Round skin</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_15">
                        </div>
                    </div>
                </div>
                <!-- /Rangeslider -->

                <!-- Rangeslider -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Square Skin</h5>
                        </div>
                        <div class="card-body">
                            <input type="text" id="range_16">
                        </div>
                    </div>
                </div>
                <!-- /Rangeslider -->

            </div>
        </div>
        <!-- End Content -->

        @include('partials.footer')

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection

