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

                        <div class="alert alert-info f-13 py-3 px-3 mb-3" role="alert">
                            <strong>How to fill the attendance template</strong>
                            <div class="mt-2">
                                <strong>Allowed attendance values:</strong>
                                <code>Present</code> full day present,
                                <code>HF</code>, <code>WO</code> half day,
                                <code>Absent</code> absent,
                                <code>SL</code> sick leave,
                                <code>CL</code> casual leave,
                                <code>EL</code> earned leave,
                                <code>LWP</code> leave without pay.
                            </div>
                            <div class="mt-2">
                                Do not change the reference columns:
                                <em>name</em>, <em>designation</em>, and <em>department</em>.
                                Fill <strong>month</strong> as <code>YYYY-MM</code>, then enter one allowed value under each date column.
                            </div>
                            <div class="table-responsive mt-2">
                                <table class="table table-sm table-bordered mb-1 bg-white">
                                    <thead>
                                    <tr>
                                        <th>employee_id</th>
                                        <th>month</th>
                                        <th>2026-05-01</th>
                                        <th>2026-05-02</th>
                                        <th>2026-05-03</th>
                                        <th>2026-05-04</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td>RVB / 105</td>
                                        <td>2026-05</td>
                                        <td>Present</td>
                                        <td>HF</td>
                                        <td>Absent</td>
                                        <td>SL</td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                            <small><strong>Sample fill:</strong> Use <code>HF</code> when the employee worked Half Day.</small>
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
