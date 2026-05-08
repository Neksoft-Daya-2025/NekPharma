<?php $page = 'ui-media'; ?>
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
                    <h2 class="mb-1">Media</h2>
                    <nav>
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{url('index')}}"><i class="ti ti-smart-home"></i></a>
                            </li>
                            <li class="breadcrumb-item">
                                Base UI
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Media</li>
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

                <!-- Media -->
                <div class="col-xxl-6">
                    <div class="card">
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">Examples</h4>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-4">Use few flex utilities that allow even more flexibility and
                                customization than before.</p>
                            <div class="d-flex align-items-start text-muted mb-4">
                                <div class="flex-shrink-0 me-3">
                                    <img src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" class="avatar avatar-xl rounded"
                                        alt="...">
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fs-14">Media Heading</h5>
                                    This is some content from a media component. You can replace this with any
                                    content and adjust it as needed.
                                </div>
                            </div>
                            <div class="d-flex align-items-start text-muted mb-4">
                                <div class="flex-grow-1">
                                    <h5 class="fs-14">Media Heading</h5>
                                    This is some content from a media component. You can replace this with any
                                    content and adjust it as needed.
                                </div>
                                <div class="flex-shrink-0 ms-3">
                                    <img src="{{URL::asset('build/img/profiles/avatar-06.jpg')}}" class="avatar avatar-xl rounded"
                                        alt="...">
                                </div>
                            </div>
                            <div class="d-flex align-items-start text-muted">
                                <div class="flex-shrink-0 me-3">
                                    <img src="{{URL::asset('build/img/profiles/avatar-07.jpg')}}" class="avatar avatar-xl rounded"
                                        alt="...">
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fs-14">Media Heading</h5>
                                    This is some content from a media component. You can replace this with any
                                    content and adjust it as needed.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Media -->

                <!-- Nesting Example -->
                <div class="col-xxl-6">
                    <div class="card">
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">Nesting Example</h4>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-4">Media objects can be infinitely nested, though we suggest you
                                stop at some point. Place nested <code>d-flex align-items-start</code> within the
                                <code>flex-grow-1</code> of a parent media object.
                            </p>
                            <div class="d-flex align-items-start text-muted mb-4">
                                <div class="flex-shrink-0 me-3">
                                    <img src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" class="avatar avatar-xl rounded"
                                        alt="...">
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fs-14">Media Heading</h5>
                                    This is some content from a media component. You can replace this with any
                                    content and adjust it as needed.
                                    <div class="d-flex align-items-start text-muted mt-3">
                                        <div class="flex-shrink-0 me-3">
                                            <img src="{{URL::asset('build/img/profiles/avatar-06.jpg')}}"
                                               class="avatar avatar-xl rounded" alt="...">
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="fs-14">Media Heading</h5>
                                            This is some content from a media component. You can replace this with
                                            any content and adjust it as needed.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-start text-muted">
                                <div class="flex-shrink-0 me-3">
                                    <img src="{{URL::asset('build/img/profiles/avatar-07.jpg')}}" class="avatar avatar-xl rounded"
                                        alt="...">
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fs-14">Media Heading</h5>
                                    This is some content from a media component. You can replace this with any
                                    content and adjust it as needed.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Nesting Example -->

            </div>

            <div class="row">

                <!-- Media Alignment -->
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header align-items-center d-flex">
                            <h4 class="card-title mb-0 flex-grow-1">Media Alignment</h4>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-4">The images or other media can be aligned top, middle, or
                                bottom. The default is top aligned.
                            </p>
                            <div class="d-flex align-items-start text-muted mb-4">
                                <div class="flex-shrink-0 me-3">
                                    <img src="{{URL::asset('build/img/profiles/avatar-05.jpg')}}" class="avatar avatar-xxxl rounded"
                                        alt="...">
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fs-14">Top Aligned media</h5>
                                    <p class="mb-1">Cras sit amet nibh libero, in gravida nulla. Nulla vel metus
                                        scelerisque ante sollicitudin. Cras purus odio, vestibulum in vulputate at,
                                        tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla.
                                        Donec lacinia congue felis in faucibus.</p>
                                    <p class="mb-0">Donec sed odio dui. Nullam quis risus eget urna mollis ornare
                                        vel eu leo. Cum sociis natoque penatibus et magnis dis parturient montes,
                                        nascetur ridiculus mus.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-center text-muted mb-4">
                                <div class="flex-shrink-0 me-3">
                                    <img src="{{URL::asset('build/img/profiles/avatar-06.jpg')}}" class="avatar avatar-xxxl rounded"
                                        alt="...">
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fs-14">Center Aligned media</h5>
                                    <p class="mb-1">Cras sit amet nibh libero, in gravida nulla. Nulla vel metus
                                        scelerisque ante sollicitudin. Cras purus odio, vestibulum in vulputate at,
                                        tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla.
                                        Donec lacinia congue felis in faucibus.</p>
                                    <p class="mb-0">Donec sed odio dui. Nullam quis risus eget urna mollis ornare
                                        vel eu leo. Cum sociis natoque penatibus et magnis dis parturient montes,
                                        nascetur ridiculus mus.</p>
                                </div>
                            </div>
                            <div class="d-flex align-items-end text-muted">
                                <div class="flex-shrink-0 me-3">
                                    <img src="{{URL::asset('build/img/profiles/avatar-07.jpg')}}" class="avatar avatar-xxxl rounded"
                                        alt="...">
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fs-14">Bottom Aligned media</h5>
                                    <p class="mb-1">Cras sit amet nibh libero, in gravida nulla. Nulla vel metus
                                        scelerisque ante sollicitudin.Cras purus odio, vestibulum in vulputate at,
                                        tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla.
                                        Donec lacinia congue felis in faucibus.</p>
                                    <p class="mb-0">Donec sed odio dui. Nullam quis risus eget urna mollis ornare
                                        vel eu leo. Cum sociis natoque penatibus et magnis dis parturient montes,
                                        nascetur ridiculus mus.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /Media Alignment -->

            </div>
        </div>
        <!-- End Content -->

        @include('partials.footer')

    </div>

    <!-- ========================
        End Page Content
    ========================= -->

@endsection

