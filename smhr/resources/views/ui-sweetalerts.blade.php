<?php $page = 'ui-sweetalerts'; ?>
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
                    <h2 class="mb-1">Sweetalerts</h2>
                    <nav>
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{url('index')}}"><i class="ti ti-smart-home"></i></a>
                            </li>
                            <li class="breadcrumb-item">
                                Base UI
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Sweetalerts</li>
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
                <div class="col-md-12">

                    <!-- Basic Examples -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Basic Examples</h4>
                        </div>
                        <div class="card-body pb-3">
                            <p>SweetAlert automatically centers itself on the page and looks great no matter if
                                you're using a desktop
                                computer, mobile or tablet. It's even highly customizable, as you can see below!</p>
                            <button type="button" class="btn btn-outline-primary me-1 mb-1"
                                id="basic-alert">Basic</button>
                            <button type="button" class="btn btn-outline-primary me-1 mb-1" id="with-title">With
                                Title</button>
                            <button type="button" class="btn btn-outline-primary me-1 mb-1" id="footer-alert">With
                                Footer</button>
                        </div>
                    </div>
                    <!-- /Basic Examples -->

                    <!-- Position -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Position</h4>
                        </div>
                        <div class="card-body pb-3">
                            <p>You can specify position of your alert with
                                <code>position : { top-start | top-end | bottom-start | bottom-end }</code> in js.
                            </p>
                            <button class="btn btn-outline-success me-1 mb-1" id="position-top-start">Top
                                Start</button>
                            <button class="btn btn-outline-success me-1 mb-1" id="position-top-end">Top End</button>
                            <button class="btn btn-outline-success me-1 mb-1" id="position-bottom-start">Bottom
                                Starts</button>
                            <button class="btn btn-outline-success me-1 mb-1" id="position-bottom-end">Bottom
                                End</button>
                        </div>
                    </div>
                    <!-- \Position -->

                    <!-- Types -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Types</h4>
                        </div>
                        <div class="card-body pb-3">
                            <p>The type of the modal. SweetAlert comes with 4 built-in types which will show a
                                corresponding icon animation:
                                "warning", "error", "success" and "info". You can also set it as "input" to get a
                                prompt modal. It can either
                                be put in the object under the key "icon" or passed as the third parameter of the
                                function.</p>
                            <button type="button" class="btn btn-outline-success me-1 mb-1"
                                id="type-success">Success</button>
                            <button type="button" class="btn btn-outline-info me-1 mb-1"
                                id="type-info">Info</button>
                            <button type="button" class="btn btn-outline-warning me-1 mb-1"
                                id="type-warning">Warning</button>
                            <button type="button" class="btn btn-outline-danger me-1 mb-1"
                                id="type-error">Error</button>
                        </div>
                    </div>
                    <!-- \Types -->

                    <!-- Options -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Options</h4>
                        </div>
                        <div class="card-body pb-3">
                            <button type="button" class="btn btn-outline-primary me-1 mb-1" id="auto-close">Auto
                                Close</button>
                            <button type="button" class="btn btn-outline-primary me-1 mb-1" id="outside-click">Click
                                Outside</button>
                            <button type="button" class="btn btn-outline-primary me-1 mb-1"
                                id="prompt-function">Question</button>
                        </div>
                    </div>
                    <!-- \Options -->

                    <!-- Confirm Options -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Confirm Options</h4>
                        </div>
                        <div class="card-body">
                            <h5>Confirm Button Text</h5>
                            <p>Use <code>confirmButtonText: "Your text here!"</code> option to change the text of
                                the "Confirm" button.</p>
                            <button type="button" class="btn btn-outline-primary mb-3" id="confirm-text">Confirm
                                Text</button>
                            <h5>Confirm Button Color</h5>
                            <p>Use <code>confirmButtonClass: "btn btn-{colorName}"</code> option to change the color
                                of the "Confirm" button.</p>
                            <button type="button" class="btn btn-outline-primary" id="confirm-color">Confirm
                                Button Color</button>
                        </div>
                    </div>
                    <!-- \Confirm Options -->

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

