@extends('layouts.app')

@section('content')
<div class="content-wrapper">

    {{-- Create Region --}}
    <div class="card mb-4">
        <div class="card-header" style="background: linear-gradient(135deg, #8bab4c 0%, #6d8a3c 100%); color: white;">
            <h5 class="mb-0"><i class="fa fa-plus-circle"></i> Create New Region</h5>
        </div>
        <div class="card-body">
            <form id="create-region-form">
                @csrf
                <div class="row">
                    <div class="col-md-8">
                        <label><strong>Region Name</strong> <sup class="text-danger">*</sup></label>
                        <input type="text" class="form-control" name="name" placeholder="e.g., UP Region" required>
                    </div>
                    <div class="col-md-4">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fa fa-plus"></i> Create Region
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Region List --}}
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fa fa-globe"></i> Regions List</h5>
            <span class="badge badge-secondary">{{ $regions->count() }} Total</span>
        </div>

        <div class="card-body">

            {{-- Regions Table --}}
            <div class="table-responsive mb-4">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%">#</th>
                            <th>Region Name</th>
                            <th width="15%">Created</th>
                            <th width="15%" class="text-right">@lang('app.action')</th>
                        </tr>
                    </thead>

                    <tbody>
                    @forelse($regions as $key => $region)
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td><strong>{{ $region->name }}</strong></td>
                            <td>{{ $region->created_at->format(company()->date_format) }}</td>

                            <td class="text-right">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" data-toggle="dropdown">
                                        <i class="fa fa-ellipsis-v"></i>
                                    </button>

                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item edit-region" href="javascript:;" 
                                           data-id="{{ $region->id }}" data-name="{{ $region->name }}">
                                            <i class="fa fa-edit text-primary"></i> Edit
                                        </a>

                                        <a class="dropdown-item delete-region" href="javascript:;" 
                                           data-id="{{ $region->id }}" data-name="{{ $region->name }}">
                                            <i class="fa fa-trash text-danger"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        {{-- ⭐ SHOW AREAS + HEADQUARTERS EXACT LIKE AREA PAGE --}}
                        <tr>
                            <td></td>
                            <td colspan="3">

                                @foreach($region->areas as $area)
                                    <div class="border rounded p-3 mb-3" style="border-left: 4px solid #5a8bd8;">
                                        <h5 class="mb-2">
                                            <i class="fa fa-map-marker text-primary"></i>
                                            <strong>{{ $area->name }}</strong>
                                        </h5>

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

                                                {{-- EX-STATIONS --}}
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

                                                {{-- OUT-STATIONS --}}
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
                                    </div>
                                @endforeach

                            </td>
                        </tr>
                        {{-- ⭐ END REGION BLOCK --}}

                    @empty
                        <tr>
                            <td colspan="4" class="text-center">
                                <i class="fa fa-globe fa-3x text-lightest"></i>
                                <p>No regions found</p>
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
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $('#create-region-form').on('submit', function (e) {
        e.preventDefault();

        $.easyAjax({
            url: "{{ route('pharma-areas.regions.store') }}",
            container: '#create-region-form',
            type: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Created!',
                        text: 'Region created successfully',
                        timer: 1500
                    }).then(function () {
                        window.location.reload();
                    });
                }
            }
        });
    });

    $(document).on('click', '.edit-region', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
            title: 'Edit Region',
            html: '<input id="edit-region-name" class="swal2-input" placeholder="Region Name">',
            showCancelButton: true,
            confirmButtonText: 'Update',
            confirmButtonColor: '#8bab4c',
            didOpen: function () {
                document.getElementById('edit-region-name').value = name;
            },
            preConfirm: function () {
                const newName = document.getElementById('edit-region-name').value;
                if (!newName) {
                    Swal.showValidationMessage('Please enter a name');
                    return false;
                }
                return { name: newName };
            }
        }).then(function (result) {
            if (result.isConfirmed) {
                $.easyAjax({
                    url: "{{ url('account/pharma-areas/regions') }}/" + id,
                    type: 'PUT',
                    data: {
                        _token: "{{ csrf_token() }}",
                        name: result.value.name
                    },
                    success: function () {
                        Swal.fire({
                            icon: 'success',
                            title: 'Updated!',
                            timer: 1500
                        }).then(function () {
                            window.location.reload();
                        });
                    }
                });
            }
        });
    });

    $(document).on('click', '.delete-region', function () {
        const id = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
            title: 'Delete Region?',
            html: 'Delete "<strong>' + $('<div/>').text(name).html() + '</strong>"?<br><br><span class="text-danger">This cannot be undone!</span>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then(function (result) {
            if (result.isConfirmed) {
                $.easyAjax({
                    url: "{{ url('account/pharma-areas/regions') }}/" + id,
                    type: 'DELETE',
                    success: function () {
                        window.location.reload();
                    }
                });
            }
        });
    });
</script>
@endpush
