<?php $page = 'ui-clipboard'; ?>
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
                    <h2 class="mb-1">Clipboard</h2>
                    <nav>
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{url('index')}}"><i class="ti ti-smart-home"></i></a>
                            </li>
                            <li class="breadcrumb-item">
                                Advanced UI
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Clipboard</li>
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

                <!-- Drag Card -->
                <div class="col-md-12">

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Copy from input</h5>
                        </div>
                        <div class="card-body pb-3">
                            <div class="clipboard">
                                <form class="form-horizontal">
                                    <input type="text" class="form-control mb-4" id="input-copy"
                                        value="http://www.admin-dashboard.com">
                                    <a class="mb-1 btn clip-btn btn-primary" href="javascript:void(0);"
                                        data-clipboard-action="copy" data-clipboard-target="#input-copy"><i
                                           class="far fa-copy"></i> Copy from Input</a>
                                    <a class="mb-1 btn clip-btn btn-dark" href="javascript:void(0);"
                                        data-clipboard-action="cut" data-clipboard-target="#input-copy"><i
                                           class="fas fa-cut"></i> Cut from Input</a>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Copy from Text Area</h5>
                        </div>
                        <div class="card-body pb-3">
                            <div class="clipboard">
                                <form class="form-horizontal">
                                    <textarea class="form-control mb-4" rows="3"
                                        id="textarea-copy">Lorem ipsum dolor sit amet, consectetur adipiscing elit...</textarea>
                                    <a class="mb-1 btn clip-btn btn-primary" href="javascript:void(0);"
                                        data-clipboard-action="copy" data-clipboard-target="#textarea-copy"><i
                                           class="far fa-copy"></i> Copy from Input</a>
                                    <a class="mb-1 btn clip-btn btn-dark" href="javascript:void(0);"
                                        data-clipboard-action="cut" data-clipboard-target="#textarea-copy"><i
                                           class="fas fa-cut"></i> Cut from Input</a>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Copy Text from Paragraph</h5>
                        </div>
                        <div class="card-body pb-3">
                            <div class="clipboard copy-txt">
                                <p class="otp-pass">Here is your OTP <span id="paragraph-copy1">22991</span>.</p>
                                <p class="mb-4">Please do not share it to anyone</p>
                                <a class="mb-1 btn clip-btn btn-primary" href="javascript:void(0);"
                                    data-clipboard-action="copy" data-clipboard-target="#paragraph-copy1"><i
                                       class="far fa-copy"></i> Copy from Input</a>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Copy Hidden Text (Advanced)</h5>
                        </div>
                        <div class="card-body pb-3">
                            <div class="clipboard copy-txt">
                                <p class="mb-4">Link -&gt; <span
                                        id="advanced-paragraph">http://www.example.com/example</span></p>
                                <a class="mb-1 btn clip-btn btn-primary" href="javascript:void(0);"
                                    data-clipboard-action="copy" data-clipboard-target="#advanced-paragraph"><i
                                       class="far fa-copy"></i> Copy Link</a>
                                <a class="mb-1 btn clip-btn btn-warning" href="javascript:void(0);"
                                    data-clipboard-action="copy" data-clipboard-text="2291"><i
                                       class="far fa-copy"></i> Copy Hidden Code</a>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /Drag Card -->
            </div>

        </div>
        <!-- End Content -->

        @include('partials.footer')

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection

