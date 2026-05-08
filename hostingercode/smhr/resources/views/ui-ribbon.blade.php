<?php $page = 'ui-ribbon'; ?>
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
                    <h2 class="mb-1">Ribbons</h2>
                    <nav>
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{url('index')}}"><i class="ti ti-smart-home"></i></a>
                            </li>
                            <li class="breadcrumb-item">
                                Advanced UI
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Ribbons</li>
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
                <h5 class="fw-semibold mb-4">Ribbon Style 1</h5>
                <div class="col-xl-3 col-md-6">
                    <div class="card ribbone-card">
                        <div class="power-ribbone power-ribbone-top-left text-warning"><span class="bg-warning"><i
                                    data-feather="zap" class="feather-zap"></i></span></div>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ribbone-card">
                        <div class="power-ribbone power-ribbone-bottom-left text-primary"><span
                               class="bg-primary"><i data-feather="zap" class="feather-zap"></i></span></div>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ribbone-card">
                        <div class="power-ribbone power-ribbone-top-right text-danger"><span class="bg-danger"><i
                                    data-feather="zap" class="feather-zap"></i></span></div>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ribbone-card">
                        <div class="power-ribbone power-ribbone-bottom-right text-success"><span
                               class="bg-success"><i data-feather="zap" class="feather-zap"></i></span></div>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <h5 class="fw-semibold mb-4">Ribbon Style 2</h5>
                <div class="col-xl-3 col-md-6">
                    <div class="card ribbone-card">
                        <div class="ribbone ribbone-top-left text-primary"><span class="bg-primary">sold out</span>
                        </div>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold text-end">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ribbone-card  sold-out">
                        <div class="ribbone ribbone-top-right text-danger"><span class="bg-danger">Offer</span>
                        </div>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ribbone-card">
                        <div class="ribbone ribbone-top-left text-success"><span class="bg-success">Update</span>
                        </div>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold text-end">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ribbone-card sold-out">
                        <div class="ribbone ribbone-top-right text-warning"><span class="bg-warning">Open</span>
                        </div>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <h5 class="fw-semibold mb-4">Ribbon Style 3</h5>
                <div class="col-xl-3 col-md-6">
                    <div class="card ribbone-card">
                        <div class="arrow-ribbone-left bg-secondary">Sale</div>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold text-end">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ribbone-card">
                        <div class="arrow-ribbone-right bg-info">Price</div>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ribbone-card">
                        <div class="arrow-ribbone-left bg-warning">Service</div>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold text-end">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card ribbone-card">
                        <div class="arrow-ribbone-right bg-teal">Offer</div>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row ribbone-row">
                <h5 class="fw-semibold mb-4">Ribbon Style 4</h5>
                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <span class="ribbone-success-left">
                            <span><i data-feather="zap" class="feather-zap"></i></span>
                        </span>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold text-end">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <span class="ribbone-warning-right">
                            <span><i data-feather="zap" class="feather-zap"></i></span>
                        </span>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <span class="ribbone-info-left">
                            <span><i data-feather="zap" class="feather-zap"></i></span>
                        </span>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold text-end">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <span class="ribbone-danger-right">
                            <span><i data-feather="zap" class="feather-zap"></i></span>
                        </span>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <h5 class="fw-semibold mb-4">Ribbon Style 5</h5>
                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <span class="bookmark-ribbone-danger-left">
                            <span>Jul</span>
                        </span>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold text-end">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <span class="bookmark-ribbone-secondary-right">
                            <span>Aug</span>
                        </span>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <span class="bookmark-sideleft-ribbone-success-left">
                            <span>Sept</span>
                        </span>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold text-end">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card">
                        <span class="bookmark-sideright-ribbone-primary-right">
                            <span>Oct</span>
                        </span>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <h5 class="fw-semibold mb-4">Ribbon Style 6</h5>
                <div class="col-xxl-2 col-lg-4 col-md-4 col-sm-10 mx-0 mx-sm-7">
                    <div class="card br-0">
                        <div class="fullwidth-secondary-ribbons">

                            <div class="bar">
                                CSS Ribbon
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-2 col-lg-4 col-md-4 col-sm-10 mx-0 mx-sm-7">
                    <div class="card br-0">
                        <div class="fullwidth-primary-ribbons">

                            <div class="bar">
                                CSS Ribbon
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-2 col-lg-4 col-md-4 col-sm-10 mx-0 mx-sm-7">
                    <div class="card br-0">
                        <div class="fullwidth-arrow-warning-ribbons">

                            <div class="bar">
                                CSS Ribbon
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-2 col-lg-4 col-md-4 col-sm-10 mx-0 mx-sm-7">
                    <div class="card br-0">
                        <div class="fullwidth-arrow-danger-ribbons-right">

                            <div class="bar">
                                CSS Ribbon
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>
                        <div class="card-body  p-6">
                            <h6 class="card-subtitle mb-2 text-dark fw-bold">Card subtitle</h6>
                            <p class="card-text">Some quick example text to build on the card title. Some quick
                                example text to build on the card title</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- End Content -->

        @include('partials.footer')

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection

