<?php $page = 'ui-images'; ?>
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
                    <h2 class="mb-1">Images</h2>
                    <nav>
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{url('index')}}"><i class="ti ti-smart-home"></i></a>
                            </li>
                            <li class="breadcrumb-item">
                                Base UI
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Images</li>
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

                <!-- Images Shapes -->
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header justify-content-between">
                            <div class="card-title">Images Shapes</div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-xl-12">
                                    <p>
                                        Add classes to an <code>&lt;img&gt;</code> element to easily style
                                        images in any project.
                                    </p>

                                    <div class="row">
                                        <div class="col-sm-3">
                                            <img src="{{URL::asset('build/img/img-4.jpg')}}" alt="image" class="img-fluid rounded"
                                                width="200">
                                            <p class="mb-0">
                                                <code>.rounded</code>
                                            </p>
                                        </div>

                                        <div class="col-sm-3">
                                            <img src="{{URL::asset('build/img/profiles/avatar-03.jpg')}}" alt="image"
                                               class="img-fluid rounded-circle" width="133">
                                            <p class="mb-0">
                                                <code>.rounded-circle</code>
                                            </p>
                                        </div>

                                        <div class="col-sm-3">
                                            <img src="{{URL::asset('build/img/img-1.jpg')}}" alt="image"
                                               class="img-fluid img-thumbnail" width="200">
                                            <p class="mb-0">
                                                <code>.img-thumbnail</code>
                                            </p>
                                        </div>

                                        <div class="col-sm-3">
                                            <img src="{{URL::asset('build/img/profiles/avatar-02.jpg')}}" alt="image"
                                               class="img-thumbnail rounded-pill" width="133">
                                            <p class="mb-0">
                                                <code>.rounded-pill</code>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Images Shapes -->

            </div>

            <div class="row">

                <!-- Image Left Align -->
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header justify-content-between">
                            <div class="card-title">Image Left Align</div>

                        </div>
                        <div class="card-body">
                            <img class="rounded float-start" src="{{URL::asset('build/img/img-1.jpg')}}" alt="..." width="200">
                        </div>

                    </div>
                </div>
                <!-- /Image Left Align -->

                <!-- Image Center Align -->
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header justify-content-between">
                            <div class="card-title">Image Center Align</div>
                        </div>
                        <div class="card-body">
                            <img class="rounded mx-auto d-block" src="{{URL::asset('build/img/img-1.jpg')}}" alt="..." width="200">
                        </div>
                    </div>
                </div>
                <!-- /Image Center Align -->

                <!-- Image Right Align -->
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header justify-content-between">
                            <div class="card-title">Image Right Align</div>
                        </div>
                        <div class="card-body">
                            <img class="rounded float-end" src="{{URL::asset('build/img/img-1.jpg')}}" alt="..." width="200">
                        </div>
                    </div>
                </div>
                <!-- /Image Right Align -->

                <!-- Figures -->
                <div class="col-xl-6">
                    <div class="card">
                        <div class="card-header justify-content-between">
                            <div class="card-title">
                                Figures
                            </div>
                        </div>
                        <div class="card-body d-flex justify-content-between gap-2 pb-1">
                            <figure class="figure">
                                <img class="bd-placeholder-img figure-img img-fluid rounded card-img"
                                    src="{{URL::asset('build/img/img-1.jpg')}}" alt="...">
                                <figcaption class="figure-caption">A caption for the above image.</figcaption>
                            </figure>
                            <figure class="figure float-end">
                                <img class="bd-placeholder-img figure-img img-fluid rounded card-img"
                                    src="{{URL::asset('build/img/img-1.jpg')}}" alt="...">
                                <figcaption class="figure-caption text-end">A caption for the above image.
                                </figcaption>
                            </figure>
                        </div>
                    </div>
                </div>
                <!-- /Figures -->

            </div>
        </div>
        <!-- End Content -->

        @include('partials.footer')

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection

