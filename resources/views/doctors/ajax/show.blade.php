@php
$editPermission = user()->permission('edit_doctors');
$deletePermission = user()->permission('delete_doctors');
@endphp

<div id="doctor-detail-section">
    <div class="row">
        <div class="col-sm-12">
            <div class="card bg-white border-0 b-shadow-4">
                <div class="card-header bg-white border-bottom-grey justify-content-between p-20">
                    <div class="row">
                        <div class="col-lg-10 col-10">
                            <h3 class="heading-h1 mb-3">Doctor Details</h3>
                        </div>
                        <div class="col-lg-2 col-2 text-right">
                            @if (
                                ($editPermission == 'all' || ($editPermission == 'added' && $doctor->added_by == user()->id))
                                || ($deletePermission == 'all' || ($deletePermission == 'added' && $doctor->added_by == user()->id))
                                )
                                <div class="dropdown">
                                    <button
                                        class="btn btn-lg f-14 px-2 py-1 text-dark-grey rounded dropdown-toggle"
                                        type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fa fa-ellipsis-h"></i>
                                    </button>

                                    <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0"
                                        aria-labelledby="dropdownMenuLink" tabindex="0">
                                        @if ($editPermission == 'all' || ($editPermission == 'added' && $doctor->added_by == user()->id))
                                            <a class="dropdown-item openRightModal"
                                                href="{{ route('doctors.edit', $doctor->id) }}">@lang('app.edit')
                                            </a>
                                        @endif

                                        @if ($deletePermission == 'all' || ($deletePermission == 'added' && $doctor->added_by == user()->id))
                                            <a class="dropdown-item delete-doctor"
                                                data-doctor-id="{{ $doctor->id }}">@lang('app.delete')</a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            @if($doctor->doctor_pic)
                                <div class="mb-4 text-center">
                                    <img src="{{ asset_url_local_s3('doctors/'.$doctor->doctor_pic) }}" 
                                         class="rounded-circle" 
                                         alt="{{ $doctor->fullname }}"
                                         style="width: 150px; height: 150px; object-fit: cover;">
                                </div>
                            @endif

                            <x-cards.data-row :label="__('app.name')" :value="$doctor->fullname ?? '--'" />
                            <x-cards.data-row :label="__('app.email')" :value="$doctor->email ?? '--'" />
                            <x-cards.data-row :label="__('app.mobile')" :value="$doctor->mobile ?? '--'" />
                            <x-cards.data-row :label="__('Qualification')" :value="$doctor->qualification ?? '--'" />
                            <x-cards.data-row :label="__('Speciality')" :value="$doctor->speciality ?? '--'" />
                            <x-cards.data-row :label="__('Gender')" :value="$doctor->gender ?? '--'" />
                            <x-cards.data-row :label="__('Date of Birth')" :value="$doctor->dob ? \Carbon\Carbon::parse($doctor->dob)->format('d M Y') : '--'" />
                            <x-cards.data-row :label="__('Date of Marriage')" :value="$doctor->dom ? \Carbon\Carbon::parse($doctor->dom)->format('d M Y') : '--'" />
                            <x-cards.data-row :label="__('Doctor Type (SFC)')" 
                                :value="$doctor->doctor_type ? '<span class=\'badge badge-info\'>'.$doctor->doctor_type.'</span>' : '--'" />
                            <x-cards.data-row :label="__('Headquarter')" 
                                :value="$doctor->headquarter ? '<span class=\'badge badge-info\'>'.$doctor->headquarter->name.'</span>' : '--'" />
                            
                            <x-cards.data-row :label="__('Station Type')" 
                                :value="($doctor->exstation_id ? '<span class=\'badge badge-success\'>Ex-Station</span>' : ($doctor->outstation_id ? '<span class=\'badge badge-warning\'>Out-Station</span>' : '<span class=\'badge badge-primary\'>Headquarter</span>'))" />
                            
                            <x-cards.data-row :label="__('Station')" 
                                :value="($doctor->exstation ? $doctor->exstation->name : ($doctor->outstation ? $doctor->outstation->name : '--'))" />
                            
                            <x-cards.data-row :label="__('Address')" :value="$doctor->address ?? '--'" />
                            
                            @if($doctor->products && $doctor->products->count() > 0)
                                <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                                    <p class="mb-0 text-lightest f-14 w-30">@lang('Products')</p>
                                    <p class="mb-0 text-dark-grey f-14 w-70 text-wrap">
                                        @foreach($doctor->products as $product)
                                            <span class="badge badge-secondary mr-1 mb-1">{{ $product->name }}</span>
                                        @endforeach
                                    </p>
                                </div>
                            @else
                                <x-cards.data-row :label="__('Products')" :value="'--'" />
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $('body').on('click', '.delete-doctor', function() {
        var id = $(this).data('doctor-id');
        Swal.fire({
            title: "@lang('messages.sweetAlertTitle')",
            text: "@lang('messages.recoverRecord')",
            icon: 'warning',
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonText: "@lang('messages.confirmDelete')",
            cancelButtonText: "@lang('app.cancel')",
            customClass: {
                confirmButton: 'btn btn-primary mr-3',
                cancelButton: 'btn btn-secondary'
            },
            showClass: {
                popup: 'swal2-noanimation',
                backdrop: 'swal2-noanimation'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                let url = "{{ route('doctors.destroy', ':id') }}";
                url = url.replace(':id', id);

                const token = "{{ csrf_token() }}";

                $.easyAjax({
                    type: 'POST',
                    url: url,
                    data: {
                        '_token': token,
                        '_method': 'DELETE'
                    },
                    success: function(response) {
                        if (response.status === "success") {
                            window.location.href = response.redirectUrl || "{{ route('doctors.index') }}";
                        }
                    }
                });
            }
        });
    });
</script>

