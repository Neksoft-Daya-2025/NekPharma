@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        {{-- Create Zone Form --}}
        <div class="card mb-4">
            <div class="card-header" style="background: linear-gradient(135deg, #8bab4c 0%, #6d8a3c 100%); color: white;">
                <h5 class="mb-0"><i class="fa fa-plus-circle"></i> Create New Zone</h5>
            </div>
            <div class="card-body">
                <form id="create-zone-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-8">
                            <label><strong>Zone Name</strong> <sup class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="name" placeholder="e.g., Zone 2" required>
                        </div>
                        <div class="col-md-4">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fa fa-plus"></i> Create Zone
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Zones List --}}
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fa fa-globe"></i> Zones List</h5>
                <span class="badge badge-secondary">{{ $zones->count() }} Total</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th width="5%">#</th>
                                <th>Zone Name</th>
                                <th width="15%">Created</th>
                                <th width="15%" class="text-right">@lang('app.action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($zones as $key => $zone)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td><strong>{{ $zone->name }}</strong></td>
                                    <td>{{ $zone->created_at->format(company()->date_format) }}</td>
                                    <td class="text-right">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                                <i class="fa fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item edit-zone" href="javascript:;" data-id="{{ $zone->id }}" data-name="{{ $zone->name }}">
                                                    <i class="fa fa-edit text-primary"></i> Edit
                                                </a>
                                                <a class="dropdown-item delete-zone" href="javascript:;" data-id="{{ $zone->id }}" data-name="{{ $zone->name }}">
                                                    <i class="fa fa-trash text-danger"></i> Delete
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">
                                        <i class="fa fa-globe fa-3x text-lightest"></i>
                                        <p class="mt-2">- No zones found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Setup CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Create Zone with proper form submission
    $('#create-zone-form').submit(function(e) {
        e.preventDefault();
        
        $.easyAjax({
            url: "{{ route('pharma-areas.zones.store') }}",
            container: '#create-zone-form',
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if(response.status == 'success'){
                    Swal.fire({
                        icon: 'success',
                        title: 'Created!',
                        text: 'Zone created successfully',
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                }
            }
        });
    });

    // Edit Zone
    $('.edit-zone').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        Swal.fire({
            title: 'Edit Zone',
            html: '<input id="edit-name" class="swal2-input" value="' + name + '" placeholder="Zone Name">',
            showCancelButton: true,
            confirmButtonText: 'Update',
            confirmButtonColor: '#8bab4c',
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
                    url: "{{ url('account/pharma-areas/zones') }}/" + id,
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

    // Delete Zone
    $('.delete-zone').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        Swal.fire({
            title: 'Delete Zone?',
            html: `Delete "<strong>${name}</strong>"?<br><br><span class="text-danger">This cannot be undone!</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.easyAjax({
                    url: "{{ url('account/pharma-areas/zones') }}/" + id,
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
