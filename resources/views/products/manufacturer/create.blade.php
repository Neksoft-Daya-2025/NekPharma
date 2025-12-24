@php
    $deleteManufacturerPermission = user()->permission('manage_product_category');
@endphp

<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">@lang('Manufacturer')</h5>
    <button type="button"  class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>
</div>
<div class="modal-body">

    <x-table class="table-bordered" headType="thead-light">
        <x-slot name="thead">
            <th>#</th>
            <th>@lang('Name')</th>
            <th class="text-right">@lang('app.action')</th>
        </x-slot>

        @forelse($manufacturers as $key=>$manufacturer)
            <tr id="man-{{ $manufacturer->id }}">
                <td>{{ $key + 1 }}</td>
                <td data-row-id="{{ $manufacturer->id }}" contenteditable="true">{{ $manufacturer->name }}
                </td>
                <td class="text-right">
                    @if ($deleteManufacturerPermission == 'all' || ($deleteManufacturerPermission == 'added' && $manufacturer->added_by == user()->id))
                        <x-forms.button-secondary data-man-id="{{ $manufacturer->id }}" icon="trash" class="delete-manufacturer">
                            @lang('app.delete')
                        </x-forms.button-secondary>
                    @endif
            </tr>
        @empty
            <x-cards.no-record-found-list colspan="3" />
        @endforelse
    </x-table>

    <x-form id="createManufacturer">
        <div class="row border-top-grey ">
            <div class="col-sm-12">
                <x-forms.text fieldId="name" :fieldLabel="__('Name')"
                    fieldName="name" fieldRequired="true" :fieldPlaceholder="__('Enter manufacturer name')">
                </x-forms.text>
            </div>
        </div>
    </x-form>
</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
    <x-forms.button-primary id="save-manufacturer" icon="check">@lang('app.save')</x-forms.button-primary>
</div>

<script>
    $('.delete-manufacturer').click(function() {

        const id = $(this).data('man-id');
        let url = "{{ route('manufacturer.destroy', ':id') }}";
        url = url.replace(':id', id);

        const token = "{{ csrf_token() }}";

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
                $.easyAjax({
                    type: 'POST',
                    url: url,
                    data: {
                        '_token': token,
                        '_method': 'DELETE'
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#man-' + id).fadeOut();
                            $('#manufacturer_id').html(response.data);
                            $('#manufacturer_id').selectpicker('refresh');
                        }
                    }
                });
            }
        });

    });

    $('#save-manufacturer').click(function() {
        const url = "{{ route('manufacturer.store') }}";
        $.easyAjax({
            url: url,
            container: '#createManufacturer',
            type: "POST",
            data: $('#createManufacturer').serialize(),
            success: function(response) {
                if (response.status === 'success') {
                    $('#manufacturer_id').html(response.data);
                    $('#manufacturer_id').selectpicker('refresh');
                    $(MODAL_LG).modal('hide');
                }
            }
        })
    });

    $('[contenteditable=true]').focus(function() {
        $(this).data("initialText", $(this).html());
    }).blur(function() {
        if ($(this).data("initialText") !== $(this).html()) {
            let id = $(this).data('row-id');
            let value = $(this).html();

            let url = "{{ route('manufacturer.update', ':id') }}";
            url = url.replace(':id', id);

            const token = "{{ csrf_token() }}";

            $.easyAjax({
                url: url,
                container: '#row-' + id,
                type: "POST",
                data: {
                    'name': value,
                    '_token': token,
                    '_method': 'PUT'
                },
                blockUI: true,
                success: function(response) {
                    if (response.status == 'success') {
                        $('#manufacturer_id').html(response.data);
                        $('#manufacturer_id').selectpicker('refresh');
                    }
                }
            })
        }
    });

</script>

