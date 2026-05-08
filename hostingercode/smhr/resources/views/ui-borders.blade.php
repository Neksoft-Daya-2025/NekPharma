<?php $page = 'ui-borders'; ?>
@extends('layout.mainlayout')
@section('content')

    <!-- ========================
        Start Page Content
    ========================= -->

    <div class="page-wrapper">

        <!-- Start Content -->
        <div class="content">
            <div class="d-md-flex d-block align-items-center justify-content-between page-breadcrumb mb-3">
                <div class="my-auto mb-2">
                    <h2 class="mb-1">Borders</h2>
                    <nav>
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{url('index')}}"><i class="ti ti-smart-home"></i></a>
                            </li>
                            <li class="breadcrumb-item">
                                Base UI
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Borders</li>
                        </ol>
                    </nav>
                </div>
                <div class="head-icons ms-2">
                    <a href="javascript:void(0);" class="" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="Collapse" id="collapse-header">
                        <i class="ti ti-chevrons-up"></i>
                    </a>
                </div>
            </div>
            <div class="row">

                <!-- Borders -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Borders</h5>
                        </div>
                        <div class="card-body p-3">
                            <span class="border border-container"></span>
                            <span class="border-top border-container"></span>
                            <span class="border-end border-container"></span>
                            <span class="border-bottom border-container"></span>
                            <span class="border-start border-container"></span>
                        </div>
                    </div>
                </div>
                <!-- /Borders -->

                <!-- Remove Borders -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Remove Borders</h5>
                        </div>
                        <div class="card-body p-3">
                            <span class="border-0 border-container"></span>
                            <span class="border border-top-0 border-container"></span>
                            <span class="border border-end-0 border-container"></span>
                            <span class="border border-bottom-0 border-container"></span>
                            <span class="border border-start-0 border-container"></span>
                        </div>
                    </div>
                </div>
                <!-- /Remove Borders -->

                <!-- Borders Widths -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title"> Border Widths </h5>
                        </div>
                        <div class="card-body p-3">
                            <span class="border border-container border-1"></span>
                            <span class="border border-container border-2"></span>
                            <span class="border border-container border-3"></span>
                            <span class="border border-container border-4"></span>
                            <span class="border border-container border-5"></span>
                        </div>
                    </div>
                </div>
                <!-- /Borders Widths -->

                <!-- Borders Colors -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Border Colors</h5>
                        </div>
                        <div class="card-body p-3">
                            <span class="border border-container border-primary"></span>
                            <span class="border border-container border-secondary"></span>
                            <span class="border border-container border-success"></span>
                            <span class="border border-container border-danger"></span>
                            <span class="border border-container border-warning"></span>
                            <span class="border border-container border-info"></span>
                            <span class="border border-container border-light"></span>
                            <span class="border border-container border-dark"></span>
                            <span class="border border-container border-white"></span>
                        </div>
                    </div>
                </div>
                <!-- /Borders Colors -->

                <!-- Borders -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header justify-content-between">
                            <div class="card-title">
                                Border color Styling
                            </div>
                        </div>
                        <div class="card-body pb-3">
                            <div class="mb-4">
                                <label for="exampleFormControlInput1" class="form-label">Email
                                    address</label>
                                <input type="email" class="form-control border-success"
                                    id="exampleFormControlInput1" placeholder="name@example.com">
                            </div>
                            <div class="h4 pb-3 mb-4 text-danger border-bottom border-danger">
                                Below Shows Danger Border
                            </div>
                            <div
                               class="p-3 bg-info bg-opacity-10 border border-info border-start-0 rounded-end mb-1 text-muted">
                                Customizing borders with backgrounud colors
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Borders -->

                <!-- Borders with opacity -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header justify-content-between">
                            <div class="card-title">
                                Border with opacity
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="border border-success p-2 mb-2">This is default success border</div>
                            <div class="border border-success p-2 mb-2 border-opacity-75">This is 75%
                                opacity
                                success border
                            </div>
                            <div class="border border-success p-2 mb-2 border-opacity-50">This is 50%
                                opacity
                                success border
                            </div>
                            <div class="border border-success p-2 mb-2 border-opacity-25">This is 25%
                                opacity
                                success border
                            </div>
                            <div class="border border-success p-2 border-opacity-10">This is 10% opacity
                                success
                                border
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Borders with opacity -->

                <!-- Borders Radius -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Border Radius</h5>
                        </div>
                        <div class="card-body pb-1">
                            <img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}"
                               class="avatar-xxl avatar bd-placeholder-img rounded mb-3" alt="img">
                            <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}"
                               class="avatar-xxl avatar  d-placeholder-img rounded-top mb-3" alt="img">
                            <img src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}"
                               class="avatar-xxl avatar bd-placeholder-img rounded-end mb-3" alt="img">
                            <img src="{{URL::asset('build/img/profiles/avatar-07.jpg')}}"
                               class="avatar-xxl avatar bd-placeholder-img rounded-bottom mb-3" alt="img">
                            <img src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}"
                               class="avatar-xxl avatar bd-placeholder-img rounded-start mb-3" alt="img">
                            <img src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}"
                               class="avatar-xxl avatar bd-placeholder-img rounded-circle mb-3" alt="img">
                            <img src="{{URL::asset('build/img/profiles/avatar-06.jpg')}}"
                               class="avatar-xxl avatar bd-placeholder-img  rounded-pill mb-3" alt="img">
                        </div>
                    </div>
                </div>
                <!-- /Borders Radius -->

                <!-- Sizes -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Sizes</h5>
                        </div>
                        <div class="card-body pb-1">
                            <img src="{{URL::asset('build/img/profiles/avatar-01.jpg')}}"
                               class="avatar-xxl avatar bd-placeholder-img rounded mb-3" alt="img">
                            <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}"
                               class="avatar-xxl avatar  d-placeholder-img rounded-top mb-3" alt="img">
                            <img src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}"
                               class="avatar-xxl avatar bd-placeholder-img rounded-end mb-3" alt="img">
                            <img src="{{URL::asset('build/img/profiles/avatar-04.jpg')}}"
                               class="avatar-xxl avatar bd-placeholder-img rounded-start mb-3" alt="img">
                            <img src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}"
                               class="avatar-xxl avatar bd-placeholder-img rounded-circle mb-3" alt="img">
                            <img src="{{URL::asset('build/img/profiles/avatar-06.jpg')}}"
                               class="avatar-xxl avatar bd-placeholder-img  rounded-pill mb-3" alt="img">
                        </div>
                    </div>
                </div>
                <!-- /Sizes -->

            </div>
        </div>
        <!-- End Content -->

        @include('partials.footer')

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection

