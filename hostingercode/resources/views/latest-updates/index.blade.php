@extends('layouts.app')

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-4">
                                <h4 class="mb-3">
                                    <i class="fa fa-bell text-primary mr-2"></i>
                                    Latest Updates & Release Notes
                                </h4>
                                <p class="text-muted">Stay informed about the latest features, improvements, and bug fixes.</p>
                            </div>

                            @foreach($updates as $update)
                                <div class="update-card mb-4 p-4 border rounded-lg {{ $update['type'] === 'feature' ? 'border-primary' : ($update['type'] === 'bugfix' ? 'border-danger' : 'border-warning') }}">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="mb-1">
                                                @if($update['type'] === 'feature')
                                                    <span class="badge badge-primary mr-2">New Feature</span>
                                                @elseif($update['type'] === 'bugfix')
                                                    <span class="badge badge-danger mr-2">Bug Fix</span>
                                                @else
                                                    <span class="badge badge-warning mr-2">Improvement</span>
                                                @endif
                                                {{ $update['title'] }}
                                            </h5>
                                            <p class="text-muted mb-0">
                                                <i class="fa fa-calendar mr-1"></i> {{ $update['date'] }}
                                                <span class="ml-3">
                                                    <i class="fa fa-tag mr-1"></i> Version {{ $update['version'] }}
                                                </span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <h6 class="mb-2"><strong>What's New:</strong></h6>
                                        <ul class="list-unstyled ml-3">
                                            @foreach($update['items'] as $item)
                                                <li class="mb-2">
                                                    <i class="fa fa-check-circle text-success mr-2"></i>
                                                    {{ $item }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>

                                    @if(!empty($update['instructions']))
                                        <div class="bg-light p-3 rounded">
                                            <h6 class="mb-2"><strong><i class="fa fa-info-circle text-info mr-1"></i> Instructions:</strong></h6>
                                            <ol class="mb-0">
                                                @foreach($update['instructions'] as $instruction)
                                                    <li class="mb-1">{{ $instruction }}</li>
                                                @endforeach
                                            </ol>
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            <div class="mt-4 p-3 bg-primary-light rounded">
                                <h6 class="mb-2"><strong><i class="fa fa-question-circle mr-1"></i> Need Help?</strong></h6>
                                <p class="mb-0">If you encounter any issues or have questions about these updates, please contact your system administrator or refer to the documentation.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- CONTENT WRAPPER END -->
    </div>
@endsection

@push('styles')
<style>
    .update-card {
        transition: all 0.3s ease;
    }
    .update-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    .bg-primary-light {
        background-color: #e3f2fd;
    }
</style>
@endpush

