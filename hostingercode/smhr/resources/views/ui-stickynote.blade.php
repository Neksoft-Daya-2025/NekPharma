<?php $page = 'ui-stickynote'; ?>
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
                    <h2 class="mb-1">Sticky Note</h2>
                    <nav>
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{url('index')}}"><i class="ti ti-smart-home"></i></a>
                            </li>
                            <li class="breadcrumb-item">
                                Advanced UI
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Sticky Note</li>
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

                <!-- Sticky -->
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title">Sticky Note </h5>
                            <a class="btn btn-primary float-sm-end m-l-10" id="add_new"
                                href="javascript:void(0);">Add New Note</a>
                        </div>
                        <div class="card-body pb-1">
                            <div class="sticky-note" id="board"></div>
                        </div>
                    </div>
                </div>
                <!-- /Sticky -->

            </div>

        </div>
        <!-- End Content -->

        @include('partials.footer')

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection

