<?php $page = 'ui-counter'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">

            <!-- Page Header -->
            <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Counter</h2>
                    <nav>
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{url('index')}}"><i class="ti ti-smart-home"></i></a>
                            </li>
                            <li class="breadcrumb-item">
                                Advanced UI
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Counter</li>
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

                <!-- Counter -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5>Clients</h5>
                            <h6 class="counter">3,000</h6>
                        </div>
                    </div>
                </div>
                <!-- /Counter -->

                <!-- Counter -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5>Total Sales</h5>
                            <h6 class="counter">10,000</h6>
                        </div>
                    </div>
                </div>
                <!-- /Counter -->

                <!-- Counter -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5>Total Projects</h5>
                            <h6 class="counter">15,000</h6>
                        </div>
                    </div>
                </div>
                <!-- /Counter -->

                <!-- Counter -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Count Down</h5>
                        </div>
                        <div class="card-body">
                            <h6>Time Count from 3</h6>
                            <span id="timer-countdown"></span>
                        </div>
                    </div>
                </div>
                <!-- /Counter -->

                <!-- Counter -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Count Up</h5>
                        </div>
                        <div class="card-body">
                            <h6>Time Counting From 0</h6>
                            <span id="timer-countup"></span>
                        </div>
                    </div>
                </div>
                <!-- /Counter -->

                <!-- Counter -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Count Inbetween</h5>
                        </div>
                        <div class="card-body">
                            <h6>Time counting from 30 to 20</h6>
                            <span id="timer-countinbetween"></span>
                        </div>
                    </div>
                </div>
                <!-- /Counter -->

                <!-- Counter -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Count Callback</h5>
                        </div>
                        <div class="card-body">
                            <h6>Count from 10 to 0 and calls timer end callback</h6>
                            <span id="timer-countercallback"></span>
                        </div>
                    </div>
                </div>
                <!-- /Counter -->

                <!-- Counter -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Custom Output</h5>
                        </div>
                        <div class="card-body">
                            <h6>Changed output pattern</h6>
                            <span id="timer-outputpattern"></span>
                        </div>
                    </div>
                </div>
                <!-- /Counter -->

            </div>

        </div>
        <!-- End Content -->

        @include('partials.footer')

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection

