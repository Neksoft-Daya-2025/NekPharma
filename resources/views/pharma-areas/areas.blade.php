@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        {{-- Create Area Form --}}
        <div class="card mb-4">
            <div class="card-header" style="background: linear-gradient(135deg, #8bab4c 0%, #6d8a3c 100%); color: white;">
                <h5 class="mb-0"><i class="fa fa-plus-circle"></i> Create New Area</h5>
            </div>
            <div class="card-body">
                <form id="create-area-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-8">
                            <label><strong>Area Name</strong> <sup class="text-danger">*</sup></label>
                            <input type="text" class="form-control" name="name" placeholder="e.g., Mumbai" required>
                        </div>
                        <div class="col-md-4">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fa fa-plus"></i> Create Area
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Areas List --}}
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fa fa-map-marker-alt"></i> Areas List</h5>
        <span class="badge badge-secondary">{{ $areas->count() }} Total</span>
    </div>

    <div class="card-body">

            {{-- ======================== AREA TABLE ======================== --}}
            <div class="table-responsive mb-4">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%">#</th>
                            <th>Area Name</th>
                            <th width="15%">Created</th>
                            <th width="15%" class="text-right">@lang('app.action')</th>
                        </tr>
                    </thead>
    
                    <tbody>
                    @forelse($areas as $key => $area)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td><strong>{{ $area->name }}</strong></td>
                            <td>{{ $area->created_at->format(company()->date_format) }}</td>
    
                            <td class="text-right">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                        <i class="fa fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item edit-area" href="javascript:;" 
                                           data-id="{{ $area->id }}" data-name="{{ $area->name }}">
                                            <i class="fa fa-edit text-primary"></i> Edit
                                        </a>
                                        <a class="dropdown-item delete-area" href="javascript:;" 
                                           data-id="{{ $area->id }}" data-name="{{ $area->name }}">
                                            <i class="fa fa-trash text-danger"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
    
                        {{-- ⭐ ADDED — HEADQUARTER DISPLAY LIKE HEADQUARTERS PAGE --}}
                        @if($area->headquarters->count() > 0)
                        <tr>
                            <td></td>
                            <td colspan="3">
    
                                @foreach($area->headquarters as $hq)
                                <div class="border rounded p-3 mb-3" style="border-left: 4px solid #88a84f;">
                                    
                                    <h6 class="mb-2">
                                        <i class="fa fa-building text-success"></i> 
                                        <strong>{{ $hq->name }}</strong>
    
                                        <span class="badge badge-dark ml-2">
                                            Total: {{ 
                                                $hq->exstations->count() + 
                                                $hq->outstations->count() 
                                            }}
                                        </span>
                                    </h6>
    
                                    {{-- EX STATIONS --}}
                                    <div class="mb-2">
                                        <i class="fa fa-map-marker text-purple"></i>
                                        <strong>Ex-Stations</strong>
                                        <span class="badge badge-info">{{ $hq->exstations->count() }}</span>
    
                                        <div class="mt-2">
                                            @foreach($hq->exstations as $ex)
                                                <span class="badge badge-primary p-2 m-1">{{ $ex->name }}</span>
                                            @endforeach
                                        </div>
                                    </div>
    
                                    {{-- OUT STATIONS --}}
                                    <div class="mb-2">
                                        <i class="fa fa-map-marker text-teal"></i>
                                        <strong>Out-Stations</strong>
                                        <span class="badge badge-info">{{ $hq->outstations->count() }}</span>
    
                                        <div class="mt-2">
                                            @foreach($hq->outstations as $out)
                                                <span class="badge badge-info p-2 m-1">{{ $out->name }}</span>
                                            @endforeach
                                        </div>
                                    </div>
    
                                </div>
                                @endforeach
    
                            </td>
                        </tr>
                        @endif
                        {{-- ⭐ END ADDED BLOCK --}}
    
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">
                                <i class="fa fa-map-marker-alt fa-3x text-lightest"></i>
                                <p class="mt-2">- No areas found</p>
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

    // Create Area with proper form submission
    $('#create-area-form').submit(function(e) {
        e.preventDefault();
        
        $.easyAjax({
            url: "{{ route('pharma-areas.areas.store') }}",
            container: '#create-area-form',
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                if(response.status == 'success'){
                    Swal.fire({
                        icon: 'success',
                        title: 'Created!',
                        text: 'Area created successfully',
                        timer: 1500
                    }).then(() => {
                        window.location.reload();
                    });
                }
            }
        });
    });

    // Edit Area
    $('.edit-area').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        Swal.fire({
            title: 'Edit Area',
            html: '<input id="edit-name" class="swal2-input" value="' + name + '" placeholder="Area Name">',
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
                    url: "{{ url('account/pharma-areas/areas') }}/" + id,
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

    // Delete Area
    $('.delete-area').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        Swal.fire({
            title: 'Delete Area?',
            html: `Delete "<strong>${name}</strong>"?<br><br><span class="text-danger">This cannot be undone!</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.easyAjax({
                    url: "{{ url('account/pharma-areas/areas') }}/" + id,
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
