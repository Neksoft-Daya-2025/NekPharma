<div class="row" id="import_pharma_table">
    <div class="col-sm-12">
        <x-form id="import-pharma-data-form">
            <div class="add-client bg-white rounded">
                <h4 class="mb-0 p-20 f-21 font-weight-normal border-bottom-grey">
                    @lang('app.importPharmaExpenseStatement')</h4>
                <div class="col-sm-12 pt-2">
                    <div class="alert alert-warning" role="alert">
                        @lang('app.importPharmaExpenseExcelInfo')
                    </div>
                </div>
                <div class="row py-20 px-3">
                    <div class="col-md-12 mb-3">
                        <x-forms.link-secondary :link="route('expenses.import.pharma.sample')" icon="download">
                            @lang('app.downloadPharmaExpenseSample')
                        </x-forms.link-secondary>
                    </div>

                    @if (user()->permission('add_expenses') == 'all')
                        <div class="col-md-3">
                            <x-forms.label class="mt-1" fieldId="pharma_user_id" :fieldLabel="__('Name of Employee')" />
                            <select class="form-control select-picker" name="pharma_user_id" id="pharma_user_id_import"
                                data-live-search="true" data-size="8" required>
                                <option value="">--</option>
                                @foreach ($employees as $item)
                                    <x-user-option :user="$item" :employeeSelect="true" :selected="user()->id == $item->id" />
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <x-forms.label class="mt-1" fieldId="pharma_headquarter_id" :fieldLabel="__('Head Quarter')" />
                            <select class="form-control select-picker" name="pharma_headquarter_id" id="pharma_headquarter_id_import"
                                data-live-search="true" data-size="8" required data-html="true">
                                <option value="">--</option>
                                @foreach ($headquarters as $hq)
                                    <option value="{{ $hq->id }}" @selected($hq->id == $currentUserHeadquarter)>
                                        {{ $hq->name }}
                                        @if($hq->area) ({{ $hq->area->name }}) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" name="pharma_user_id" value="{{ user()->id }}">
                        <div class="col-md-3">
                            <x-forms.label class="mt-1" fieldId="pharma_user_id_ro" :fieldLabel="__('Name of Employee')" />
                            <div class="form-control height-35 f-14 bg-light d-flex align-items-center">{{ user()->name }}</div>
                        </div>
                        <div class="col-md-3">
                            <x-forms.label class="mt-1" fieldId="pharma_headquarter_id" :fieldLabel="__('Head Quarter')" />
                            @if(isset($headquarters) && $headquarters->isNotEmpty() && ($headquarters->count() > 1 || ($showHqDropdownForPharmaRoles ?? false)))
                                <select class="form-control select-picker" name="pharma_headquarter_id" id="pharma_headquarter_id_import"
                                    data-live-search="true" data-size="8" required data-html="true">
                                    <option value="">--</option>
                                    @foreach ($headquarters as $hq)
                                        <option value="{{ $hq->id }}" @selected($hq->id == $currentUserHeadquarter)>
                                            {{ $hq->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @elseif($currentUserHeadquarter)
                                <input type="hidden" name="pharma_headquarter_id" value="{{ $currentUserHeadquarter }}">
                                <div class="form-control height-35 f-14 bg-light">{{ $currentUserHeadquarterName ?? '--' }}</div>
                            @else
                                <div class="form-control text-danger">Not assigned</div>
                            @endif
                        </div>
                    @endif

                    <div class="col-md-1">
                        <x-forms.label class="mt-1" fieldId="expense_month_import" :fieldLabel="__('Month')" />
                        <select class="form-control select-picker" name="expense_month" id="expense_month_import" required data-html="true">
                            <x-forms.months :selectedMonth="now()->month" fieldRequired="true"/>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <x-forms.label class="mt-1" fieldId="expense_year_import" :fieldLabel="__('Year')" />
                        <select class="form-control select-picker" name="expense_year" id="expense_year_import" required data-html="true">
                            @for ($i = 0; $i <= 2; $i++)
                                @php $year = now()->year - $i; @endphp
                                <option value="{{ $year }}" @selected($i === 0)>{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-2">
                        <x-forms.datepicker fieldId="posted_on_import" fieldRequired="true"
                            :fieldLabel="__('Posted on')" fieldName="posted_on"
                            :fieldPlaceholder="__('placeholders.date')"
                            :fieldValue="\Carbon\Carbon::today()->format(company()->date_format)" />
                    </div>
                    <div class="col-md-2">
                        <x-forms.number fieldId="no_of_vouchers_import" :fieldLabel="__('NO. OF VOUCHERS ATTACHED')"
                            fieldName="no_of_vouchers" fieldValue="0" fieldRequired="true" />
                    </div>
                    <div class="col-md-3">
                        <x-forms.label class="mt-1" fieldId="submitted_to_import" :fieldLabel="__('Submit To (Manager)')" />
                        <select class="form-control select-picker" name="submitted_to" id="submitted_to_import"
                            data-live-search="false" data-size="8" required data-html="true">
                            @if(isset($reportingManagerId) && $reportingManagerId)
                                @php $reportingManager = $managers->firstWhere('id', $reportingManagerId); @endphp
                                @if($reportingManager)
                                    <option value="{{ $reportingManager->id }}" selected>{{ $reportingManager->name }}</option>
                                @else
                                    <option value="">--</option>
                                    @foreach($managers as $manager)
                                        <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                                    @endforeach
                                @endif
                            @else
                                <option value="">--</option>
                                @foreach($managers as $manager)
                                    <option value="{{ $manager->id }}">{{ $manager->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-md-12 mt-3">
                        <x-forms.file :fieldLabel="__('modules.import.file')" fieldName="import_file"
                                      fieldId="pharma_expense_import"/>
                    </div>
                    <div class="col-md-12">
                        <x-forms.toggle-switch class="mr-0 mr-lg-12"
                                               :fieldLabel="__('modules.import.containsHeadings')"
                                               fieldName="heading"
                                               fieldId="heading_pharma"/>
                    </div>
                </div>
                <x-form-actions>
                    <x-forms.button-primary id="import-pharma-form" class="mr-3"
                                            icon="arrow-right">@lang('app.uploadNext')
                    </x-forms.button-primary>
                    <x-forms.button-cancel :link="route('expenses.index')" class="border-0">@lang('app.back')
                    </x-forms.button-cancel>
                </x-form-actions>
            </div>
        </x-form>
    </div>
</div>

<script>
    $(document).ready(function () {
        $("#pharma_expense_import").dropify({
            messages: dropifyMessages
        });

        $('body').on('click', '#import-pharma-form', function () {
            const url = "{{ route('expenses.import.pharma.store') }}";

            $.easyAjax({
                url: url,
                container: '#import-pharma-data-form',
                type: "POST",
                disableButton: true,
                blockUI: true,
                buttonSelector: "#import-pharma-form",
                file: true,
                data: $('#import-pharma-data-form').serialize(),
                success: function (response) {
                    if (response.status == 'success') {
                        $('#import_pharma_table').html(response.view);
                    }
                }
            });
        });
    });
</script>
