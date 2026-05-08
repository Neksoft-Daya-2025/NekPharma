<?php $page = 'ui-lightbox'; ?>
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
                    <h2 class="mb-1">Lightbox</h2>
                    <nav>
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{url('index')}}"><i class="ti ti-smart-home"></i></a>
                            </li>
                            <li class="breadcrumb-item">
                                Base UI
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Lightbox</li>
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

                <!-- Lightbox -->
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Single Image Lightbox</h5>
                        </div>
                        <div class="card-body pb-1">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <a href="{{URL::asset('build/img/img-01.jpg')}}" class="image-popup">
                                        <img src="{{URL::asset('build/img/img-01.jpg')}}" class="img-fluid" alt="image">
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="{{URL::asset('build/img/img-02.jpg')}}" class="image-popup">
                                        <img src="{{URL::asset('build/img/img-02.jpg')}}" class="img-fluid" alt="image">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Lightbox -->

                <!-- Lightbox -->
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Image with Description</h5>
                        </div>
                        <div class="card-body pb-1">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <a href="{{URL::asset('build/img/img-03.jpg')}}" class="image-popup-desc" data-title="Title 01"
                                        data-description="Lorem ipsum dolor sit amet, consectetuer adipiscing elit">
                                        <img src="{{URL::asset('build/img/img-03.jpg')}}" class="img-fluid" alt="work-thumbnail">
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="{{URL::asset('build/img/img-04.jpg')}}" class="image-popup-desc" data-title="Title 02"
                                        data-description="Lorem ipsum dolor sit amet, consectetuer adipiscing elit">
                                        <img src="{{URL::asset('build/img/img-04.jpg')}}" class="img-fluid" alt="work-thumbnail">
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="{{URL::asset('build/img/img-05.jpg')}}" class="image-popup-desc" data-title="Title 03"
                                        data-description="Lorem ipsum dolor sit amet, consectetuer adipiscing elit">
                                        <img src="{{URL::asset('build/img/img-05.jpg')}}" class="img-fluid" alt="work-thumbnail">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Lightbox -->

            </div>
        </div>
        <!-- End Content -->

        @include('partials.footer')

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection

