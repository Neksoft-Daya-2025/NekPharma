<div class="row" id="import_table">
    <div class="col-sm-12">
        <x-form id="import-doctor-data-form">
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal  border-bottom-grey">
                    @lang('app.importExcel') Doctors</h4>
                <div class="col-sm-12 pt-2">
                    <div class="alert alert-warning" role="alert">
                        @lang('app.importProjectExcelInfo')
                    </div>
                    <div class="alert alert-info" role="alert">
                        <strong>@lang('app.tip'):</strong> @lang('Download sample file to see the correct format')
                        <a href="{{ route('doctors.import.sample') }}" class="btn btn-sm btn-primary ml-2" id="download-sample-file" target="_blank">
                            <i class="fa fa-download"></i> @lang('Download Sample File')
                        </a>
                        <p class="mb-0 mt-2 small text-muted">Keep &quot;Contains headings&quot; enabled if your file has a header row. Headers like <strong>Speciality</strong> or <strong>Speciality (Head)</strong> map automatically. The doctor list shows 50 per page—use pagination to see all records.</p>
                    </div>
                </div>
                <div class="row py-20">
                    <div class="col-md-12">
                        <x-forms.file :fieldLabel="__('modules.import.file')" fieldName="import_file" fieldId="doctor_import" />
                    </div>
                    <div class="col-md-12">
                        <x-forms.toggle-switch class="mr-0 mr-lg-12"
                            :fieldLabel="__('modules.import.containsHeadings')"
                            fieldName="heading"
                            fieldId="heading"
                            :checked="true"/>
                    </div>
                </div>
                <x-form-actions>
                    <x-forms.button-primary id="import-doctor-form" class="mr-3" icon="arrow-right">@lang('app.uploadNext')
                    </x-forms.button-primary>
                    <x-forms.button-cancel :link="route('doctors.index')" class="border-0">@lang('app.back')
                    </x-forms.button-cancel>

                </x-form-actions>
            </div>
        </x-form>

    </div>
</div>

<script>

    $(document).ready(function() {

        $("#doctor_import").dropify({
            messages: dropifyMessages
        });

        // Handle sample file download
        $('#download-sample-file').on('click', function(e) {
            e.preventDefault();
            window.location.href = $(this).attr('href');
        });

        $('body').on('click', '#import-doctor-form', function() {
            const url = "{{ route('doctors.import.store') }}";

            $.easyAjax({
                url: url,
                container: '#import-doctor-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#import-doctor-form",
                file: true,
                data: $('#import-doctor-data-form').serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        if (response.view) {
                            $('#import_table').html(response.view);
                            // If batchId is provided, start progress tracking
                            if (response.batchId && typeof getProgress === 'function') {
                                setTimeout(function() {
                                    getProgress(response.batchId);
                                }, 500);
                            }
                        }
                    }
                },
                error: function(response) {
                    console.error('Import error:', response);
                }
            });
        });
    });
</script>

