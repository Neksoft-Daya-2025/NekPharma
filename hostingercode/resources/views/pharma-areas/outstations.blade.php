@extends('layouts.app')

@push('styles')
<style>
    .table td .btn {
        margin-left: 3px;
    }
</style>
@endpush

@section('content')
    <div class="content-wrapper">
        {{-- Create Out-Station Form --}}
        <div class="card mb-4">
            <div class="card-header" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white;">
                <h5 class="mb-0"><i class="fa fa-plus-circle"></i> Create New Out-Station</h5>
            </div>
            <div class="card-body">
                <form id="create-outstation-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-8">
                            <label><strong>Out-Station Name</strong> <sup class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="name" placeholder="e.g., MUM Out 1" required>
                        </div>
                        <div class="col-md-4">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-info btn-block">
                                <i class="fa fa-plus"></i> Create Out-Station
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Out-Stations List --}}
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa fa-map-marker"></i> Out-Stations List</h5>
                <span class="badge badge-secondary">{{ $outstations->count() }} Total</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th width="5%">#</th>
                                <th>Out-Station Name</th>
                                <th width="15%">Created</th>
                                <th width="10%" class="text-right">@lang('app.action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($outstations as $key => $station)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>{{ $station->name }}</td>
                                    <td>{{ $station->created_at->format(company()->date_format) }}</td>
                                    <td class="text-right">
                                        <a class="btn btn-sm btn-primary edit-station" href="javascript:;" data-id="{{ $station->id }}" data-name="{{ $station->name }}" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <a class="btn btn-sm btn-danger delete-station" href="javascript:;" data-id="{{ $station->id }}" data-name="{{ $station->name }}" title="Delete">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">
                                        <i class="fa fa-map-marker fa-3x text-lightest"></i>
                                        <p class="mt-2">- No out-stations found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
    // Setup CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Create Out-Station
    $('#create-outstation-form').submit(function(e) {
        e.preventDefault();
        
        $.easyAjax({
            url: "{{ route('pharma-areas.outstations.store') }}",
            container: '#create-outstation-form',
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if(response.status == 'success'){
                    Swal.fire({
                        icon: 'success',
                        title: 'Created!',
                        text: 'Out-Station created successfully',
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                }
            }
        });
    });

    // Edit Out-Station
    $('.edit-station').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        Swal.fire({
            title: 'Edit Out-Station',
            html: `
                <input id="edit-name" class="swal2-input" value="${name}" placeholder="Out-Station Name">
            `,
            showCancelButton: true,
            confirmButtonText: 'Update',
            confirmButtonColor: '#17a2b8',
            preConfirm: () => {
                const newName = document.getElementById('edit-name').value;
                if (!newName) {
                    Swal.showValidationMessage('Please enter a name');
                    return false;
                }
                return { name: newName };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.easyAjax({
                    url: "{{ url('account/pharma-areas/outstations') }}/" + id,
                    type: "PUT",
                    data: { 
                        _token: "{{ csrf_token() }}",
                        name: result.value.name 
                    },
                    success: function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            timer: 1500
                        }).then(() => {
                            window.location.reload();
                        });
                    }
                });
            }
        });
    });

    // Delete Out-Station
    $('.delete-station').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        Swal.fire({
            title: 'Delete Out-Station?',
            html: `Delete "<strong>${name}</strong>"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.easyAjax({
                    url: "{{ url('account/pharma-areas/outstations') }}/" + id,
                    type: "DELETE",
                    success: function() {
                        window.location.reload();
                    }
                });
            }
        });
    });
</script>
@endpush
@endsection
