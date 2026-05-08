<!-- LEAVE GENRAL SETTING START -->
<div class="col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-4">

    <div class="form-group">

        <div class="d-block d-lg-flex d-md-flex">
            <x-forms.radio fieldId="login-yes" :fieldLabel="__('modules.leaves.countLeavesFromDateOfJoining')"
                           fieldName="leaves_start_from" fieldValue="joining_date"
                           :checked="company()->leaves_start_from == 'joining_date'">
            </x-forms.radio>
            <x-forms.radio fieldId="login-no" :fieldLabel="__('modules.leaves.countLeavesFromStartOfYear')"
                           fieldValue="year_start" fieldName="leaves_start_from"
                           :checked="company()->leaves_start_from == 'year_start'">
            </x-forms.radio>
        </div>
        <div class="d-block d-lg-flex d-md-flex">
            <div class="col-lg-3" id="year_starts"
                 @if (company()->leaves_start_from == 'joining_date') style='display:none' @endif>
                <x-forms.select fieldId="year_starts_from" :fieldLabel="__('modules.accountSettings.yearStartsFrom')"
                                fieldName="year_starts_from" search="true">
                    @foreach(\App\Models\GlobalSetting::getMonthsOfYear() as $month)
                        <option value="{{ $loop->iteration }}"
                                @if (company()->year_starts_from == $loop->iteration) selected @endif>{{ $month }}</option>
                    @endforeach
                </x-forms.select>
            </div>
        </div>
    </div>

    <div class="d-block d-lg-flex d-md-flex">
        <x-alert type="info">@lang('modules.leaves.leaveSettingNote')</x-alert>
    </div>

    <div class="d-block d-lg-flex d-md-flex">
        <p> @lang('modules.leaves.reportingManager') </p>
        <div class="col-lg-4">

            <select name="permission" class="form-control select-picker manager-permission"
                    onchange="changeStatus(this.value)">
                <option
                    @if ($leavePermission->manager_permission == 'pre-approve') @endif value="pre-approve">@lang('modules.leaves.preApprove')</option>
                <option @if ($leavePermission->manager_permission == 'approved') selected
                        @endif value="approved">@lang('modules.leaves.approve')</option>
                <option @if ($leavePermission->manager_permission == 'cannot-approve') selected
                        @endif value="cannot-approve">@lang('modules.leaves.canNotApprove')</option>
            </select>
        </div>
        <p> @lang('modules.leaves.theLeave') </p>
    </div>

    <hr class="my-4">
    <h5 class="mb-3">@lang('modules.leaves.lwpConfiguration')</h5>
    <x-alert type="info">@lang('messages.leave.lwpConfigurationNote')</x-alert>
    <div class="row mt-3">
        <div class="col-lg-6 mb-3">
            <div class="card border">
                <div class="card-body">
                    <h6 class="mb-2">@lang('modules.leaves.overQuotaAsLwp')</h6>
                    <p class="f-13 text-dark-grey mb-3">@lang('messages.leave.overQuotaAsLwpNote')</p>
                    <button type="button" class="btn btn-primary btn-sm" id="apply-over-quota-lwp">
                        @lang('modules.leaves.applyOverQuotaAsLwp')
                    </button>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-3">
            <div class="card border">
                <div class="card-body">
                    <h6 class="mb-2">@lang('modules.leaves.lwpLeaveType')</h6>
                    @if ($lwpLeaveTypeExists)
                        <p class="f-13 text-success mb-2"><i class="fa fa-check-circle"></i> @lang('messages.leave.lwpTypeConfigured')</p>
                    @else
                        <p class="f-13 text-dark-grey mb-3">@lang('messages.leave.lwpTypeCreateNote')</p>
                        <button type="button" class="btn btn-primary btn-sm" id="create-lwp-type">
                            @lang('modules.leaves.createLwpLeaveType')
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

</div>
<!-- LEAVE GENRAL SETTING ENDS -->

<script>

    $('input[name=leaves_start_from], #year_starts_from').on("click change", function () {
        var leaveCountFrom = $('input[name=leaves_start_from]:checked').val();
        var yearStartFrom = $('#year_starts_from').val();
        $.easyAjax({
            url: "{{ route('leaves-settings.store') }}",
            type: "POST",
            data: {
                '_token': '{{ csrf_token() }}',
                'leaveCountFrom': leaveCountFrom,
                'yearStartFrom': yearStartFrom
            }
        })
    });

    $(function () {
        $('input[name=leaves_start_from]').change(function () {
            ($(this).val() == 'year_start') ? $('#year_starts').show() : $('#year_starts').hide();
        });
    });

    function changeStatus(value) {

        var url = "{{ route('leaves-settings.changePermission') }}";
        var token = "{{ csrf_token() }}";
        var id = {{$leavePermission->id}};

        $.easyAjax({
            type: 'POST',
            url: url,
            data: {
                '_token': token,
                'value': value,
                'id': id,
            },
        });
    }

    $('#apply-over-quota-lwp').on('click', function() {
        $.easyAjax({
            type: 'POST',
            url: "{{ route('leaves-settings.apply-over-quota-lwp') }}",
            data: { '_token': '{{ csrf_token() }}' },
            success: function(response) {
                if (response.status == 'success') {
                    window.location.reload();
                }
            }
        });
    });

    $('#create-lwp-type').on('click', function() {
        $.easyAjax({
            type: 'POST',
            url: "{{ route('leaves-settings.create-lwp-type') }}",
            data: { '_token': '{{ csrf_token() }}' },
            success: function(response) {
                if (response.status == 'success') {
                    window.location.reload();
                }
            }
        });
    });
</script>
