@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-flex justify-content-between action-bar mb-2 mb-lg-0 mb-md-0">
            <div class="d-flex align-items-center">
                <x-forms.link-secondary :link="route('cfa-distributor-inventory.index')" class="mr-3" icon="arrow-left">
                    @lang('app.back')
                </x-forms.link-secondary>
                <h4 class="mb-0 f-21 font-weight-normal text-capitalize">{{ $pageTitle }}</h4>
            </div>
        </div>

        <div class="d-flex flex-column w-tables rounded mt-3 bg-white w-100 table-responsive">
            @if($batches->isEmpty())
                <div class="p-4 text-muted">@lang('messages.noRecordFound')</div>
            @else
                <table class="table table-hover border-0 w-100">
                    <thead>
                        <tr>
                            <th>@lang('app.batch')</th>
                            <th>@lang('app.expiry')</th>
                            <th>@lang('app.totalQuantity')</th>
                            <th>@lang('app.availableQuantity')</th>
                            <th>@lang('app.pts')</th>
                            <th>@lang('app.ptr')</th>
                            <th>@lang('app.mrp')</th>
                            <th>@lang('app.invoice')</th>
                            <th>@lang('app.action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($batches as $batch)
                            <tr>
                                <td>{{ $batch->batch ?? '-' }}</td>
                                <td>{{ $batch->expiry ? $batch->expiry->format(company()->date_format) : '-' }}</td>
                                <td>{{ number_format($batch->quantity, 2) }}</td>
                                <td>{{ number_format($batch->available_quantity, 2) }}</td>
                                <td>{{ $batch->pts ? number_format($batch->pts, 2) : '-' }}</td>
                                <td>{{ $batch->ptr ? number_format($batch->ptr, 2) : '-' }}</td>
                                <td>{{ $batch->mrp ? number_format($batch->mrp, 2) : '-' }}</td>
                                <td>
                                    @if($batch->invoice)
                                        <a href="{{ route('cfa-distributor-invoices.show', $batch->invoice_id) }}" class="text-dark">{{ $batch->invoice->invoice_number }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <div class="dropdown dropup">
                                        <a class="task_view_more d-flex align-items-center justify-content-center dropdown-toggle" type="link" id="batchMenu-{{ $batch->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon-options-vertical icons"></i></a>
                                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="batchMenu-{{ $batch->id }}">
                                            <a href="{{ route('cfa-distributor-inventory.batches.edit', $batch->id) }}" class="dropdown-item openRightModal">
                                                <i class="fa fa-edit mr-2"></i>@lang('app.editBatch')
                                            </a>
                                            <a href="javascript:;" class="dropdown-item delete-batch-btn" data-batch-id="{{ $batch->id }}">
                                                <i class="fa fa-trash mr-2"></i>@lang('app.deleteBatch')
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(function() {
        $('body').on('click', '.delete-batch-btn', function() {
            var id = $(this).data('batch-id');
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
                    var url = "{{ route('cfa-distributor-inventory.batches.destroy', ':id') }}".replace(':id', id);
                    var token = "{{ csrf_token() }}";
                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        blockUI: true,
                        data: { '_token': token, '_method': 'DELETE' },
                        success: function(response) {
                            if (response.status == 'success') {
                                if (response.redirectUrl) {
                                    window.location.href = response.redirectUrl;
                                } else {
                                    window.location.reload();
                                }
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    text: response.message || "@lang('messages.somethingWentWrong')"
                                });
                            }
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
