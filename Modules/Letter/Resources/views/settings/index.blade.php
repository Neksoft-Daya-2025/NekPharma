@extends('layouts.app')

@section('content')
    <!-- CONTENT WRAPPER START -->
    <div class="content-wrapper">
        <div class="row">
            <div class="col-lg-12">
                <div class="card bg-white rounded">
                    <h4 class="mb-0 p-20 f-21 font-weight-normal border-bottom-grey">
                        @lang('letter::app.letterSettings')
                    </h4>

                    <div class="card-body">
                        <form id="letter-settings-form" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="background_image_delete" id="background_image_delete" value="no">

                            <div class="row p-20">
                                <div class="col-md-12">
                                    <x-forms.label class="my-3" fieldId="background_image" :fieldLabel="__('letter::app.backgroundImage')" />
                                    <x-forms.file allowedFileExtensions="png jpg jpeg svg webp gif bmp" class="mr-0 mr-lg-2 mr-md-2"
                                        :fieldLabel="__('letter::app.backgroundImage')"
                                        :fieldValue="($letterSetting && $letterSetting->background_image) ? asset_url_local_s3('letter-background/' . $letterSetting->background_image) : ''"
                                        fieldName="background_image"
                                        fieldId="background_image"
                                        :popover="__('letter::app.backgroundImageInfo')">
                                    </x-forms.file>
                                    @if($letterSetting && $letterSetting->background_image)
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteBackgroundImage()">
                                                <i class="fa fa-trash"></i> @lang('app.remove') @lang('letter::app.backgroundImage')
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="row p-20">
                                <div class="col-md-12">
                                    <x-forms.button-primary id="save-letter-settings-btn" icon="check">
                                        @lang('app.save')
                                    </x-forms.button-primary>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- CONTENT WRAPPER END -->
@endsection

@push('scripts')
<script>
    function deleteBackgroundImage() {
            Swal.fire({
            title: "@lang('messages.sweetAlertTitle')",
            text: "@lang('messages.deleteConfirm')",
            icon: 'warning',
            showCancelButton: true,
            focusConfirm: false,
            confirmButtonText: "@lang('app.delete')",
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
                $('#background_image_delete').val('yes');
                $.easyAjax({
                    url: "{{ route('letter.settings.update') }}",
                    container: '#letter-settings-form',
                    type: "POST",
                    blockUI: true,
                    file: true,
                    success: function(response) {
                        if (response.status == 'success') {
                            window.location.reload();
                        }
                    }
                });
            }
        });
    }

    $('#background_image').on('change', function () {
        $('#background_image_delete').val('no');
    });

    $('#save-letter-settings-btn').click(function (e) {
        e.preventDefault();
        $.easyAjax({
            url: "{{ route('letter.settings.update') }}",
            container: '#letter-settings-form',
            type: "POST",
            blockUI: true,
            buttonSelector: "#save-letter-settings-btn",
            disableButton: true,
            file: true,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status == 'success') {
                    window.location.reload();
                }
            }
        });
    });
</script>
@endpush

