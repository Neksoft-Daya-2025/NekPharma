<div class="row" id="import_table">
    <div class="col-sm-12">
        <x-form id="import-attendance-data-form">
            <div class="add-attendance bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal  border-bottom-grey">
                    @lang('app.importAttendance')</h4>
                <div class="col-sm-12 pt-2">
                    <div class="alert alert-warning" role="alert">
                        @lang('app.importAttendanceExcelInfo')
                    </div>
                </div>
                <div class="row py-20">
                    <div class="col-md-12">
                        {{-- Dynamic template: always includes all current employees --}}
                        <a href="{{ route('attendances.import.template') }}" class="btn btn-secondary rounded f-14 p-2 mb-3">
                            <i class="fa fa-download mr-1"></i> @lang('app.downloadSampleImport')
                            <span class="badge badge-light ml-1 f-11">Live — all employees</span>
                        </a>

                        <div class="alert alert-info f-13 py-2 px-3 mb-3" role="alert">
                            <strong>How to fill the template:</strong><br>
                            The first 3 columns (<em>name, designation, department</em>) are for reference only — <strong>do not change them</strong>.<br>
                            Fill the <strong>date</strong> column (format: <code>YYYY-MM-DD</code>) and the <strong>status</strong> column:<br>
                            <code>present</code> &nbsp;|&nbsp; <code>absent</code> &nbsp;|&nbsp; <code>half_day</code> &nbsp;|&nbsp; <code>late</code>
                        </div>

                        <x-forms.file :fieldLabel="__('modules.import.file')" fieldName="import_file"
                                      fieldId="attendance_import"/>
                    </div>
                    <div class="col-md-12">
                        <x-forms.toggle-switch class="mr-0 mr-lg-12"
                                               :fieldLabel="__('modules.import.containsHeadings')"
                                               fieldName="heading"
                                               fieldId="heading"/>
                    </div>
                </div>
                <x-form-actions>
                    <x-forms.button-primary id="import-attendance-form" class="mr-3"
                                            icon="arrow-right">@lang('app.uploadNext')
                    </x-forms.button-primary>
                    <x-forms.button-cancel :link="route('attendances.index')" class="border-0">@lang('app.back')
                    </x-forms.button-cancel>

                </x-form-actions>
            </div>
        </x-form>

    </div>
</div>

<script>

    $(document).ready(function () {

        $("#attendance_import").dropify({
            messages: dropifyMessages
        });

        $('body').on('click', '#import-attendance-form', function () {
            const url = "{{ route('attendances.import.store') }}";

            $.easyAjax({
                url: url,
                container: '#import-attendance-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#import-attendance-form",
                file: true,
                data: $('#import-attendance-data-form').serialize(),
                success: function (response) {
                    if (response.status === 'success') {
                        $('#import_table').html(response.view);
                    }
                }
            });
        });
    });
</script>
