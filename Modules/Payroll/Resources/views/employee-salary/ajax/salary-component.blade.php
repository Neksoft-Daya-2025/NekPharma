<div class="row">
    <div class="col-md-12">
        <h3 class="heading-h3 mb-0 py-4">@lang('payroll::modules.payroll.earning')</h3>
    </div>
    <div class="col-md-12">
        <div class="row mb-2">
            <div class="col-md-3">
                <x-forms.label fieldId="" :fieldLabel="__('payroll::modules.payroll.basicSalary')" />
            </div>
            <div class="col-md-3">
                <x-forms.input-group>
                    <input type="number" value="{{ $basicValue }}" onmouseout="changeClc()" name="basic_salary" id="basic_value" class="form-control height-35 f-15 tttt" style="width:30%">
                    <select name="basic_value" id="basic-type" onchange="selectType(this.value)" class="form-control select-picker" data-size="8">
                        <option @if($basicType == 'fixed') selected @endif value="fixed">@lang('payroll::modules.payroll.fixed')</option>
                        <option @if($basicType == 'ctc_percent') selected @endif value="ctc_percent">@lang('payroll::modules.payroll.percentOfCTC')</option>
                    </select>
                </x-forms.input-group>
            </div>
            <div class="col-md-3">
                <x-forms.input-group>
                    <x-slot name="prepend" id="currency"><span class="input-group-text f-14 bg-white-shade">{{ ($currency->currency ? $currency->currency->currency_symbol : company()->currency->currency_symbol ) }}</span></x-slot>
                    <input type="text" class="form-control height-35 f-14" name="basic_type" id="basic_type" value="{{ $payrollController->currencyFormatterCustom($basicSalary) }}" readonly>
                </x-forms.input-group>
            </div>
            <div class="col-md-3">
                <x-forms.input-group>
                    <x-slot name="prepend" id="currency"><span class="input-group-text f-14 bg-white-shade">{{ ($currency->currency ? $currency->currency->currency_symbol : company()->currency->currency_symbol ) }}</span></x-slot>
                    <input type="text" class="form-control height-35 f-14" value="{{ $payrollController->currencyFormatterCustom($basicSalary * 12) }}" readonly>
                </x-forms.input-group>
            </div>
        </div>
    </div>

    @foreach ($salaryComponents->where('component_type', 'earning') as $salaryComponent)
        @php
            $componentValue = isset($blankSalaryComponentValues) && $blankSalaryComponentValues ? '' : $payrollController->componentMonthlyValue($salaryComponent, $annualSalary, $basicSalary);
            $componentAnnualValue = $componentValue === '' ? '' : $payrollController->currencyFormatterCustom($componentValue * 12);
        @endphp
        <div class="col-md-12 mt-1">
            <div class="row">
                <div class="col-md-3"><x-forms.label fieldId="" :fieldLabel="$salaryComponent->component_name" /></div>
                <div class="col-md-3"><x-forms.label fieldId="" :fieldLabel="'Manual'" /></div>
                <div class="col-md-3">
                    <x-forms.input-group>
                        <x-slot name="prepend" id="currency"><span class="input-group-text f-14 bg-white-shade">{{ ($currency->currency ? $currency->currency->currency_symbol : company()->currency->currency_symbol ) }}</span></x-slot>
                        <input type="number" step="0.01" min="0" class="form-control height-35 f-14 manual-component earning-component" name="earning_variable[{{ $salaryComponent->id }}]" data-type="earning" data-type-id="{{ $salaryComponent->id }}" id="variable-{{ $salaryComponent->id }}" value="{{ $componentValue === '' ? '' : round($componentValue, 2) }}">
                    </x-forms.input-group>
                </div>
                <div class="col-md-3">
                    <x-forms.input-group>
                        <x-slot name="prepend" id="currency"><span class="input-group-text f-14 bg-white-shade">{{ ($currency->currency ? $currency->currency->currency_symbol : company()->currency->currency_symbol ) }}</span></x-slot>
                        <input type="text" class="form-control height-35 f-14 component-annual" id="variableAnually{{ $salaryComponent->id }}" value="{{ $componentAnnualValue }}" readonly>
                    </x-forms.input-group>
                </div>
            </div>
        </div>
    @endforeach

    <div class="col-md-12">
        <div class="row my-3">
            <div class="col-md-3">
                <x-forms.label fieldId="" :popover="__('payroll::messages.fixedAllowanceMessage')" :fieldLabel="__('payroll::modules.payroll.fixedAllowance')" />
                <p class="f-11 text-grey">@lang('payroll::modules.payroll.extraPay')</p>
            </div>
            <div class="col-md-3"><x-forms.label fieldId="" :fieldLabel="'Manual'" /></div>
            <div class="col-md-3">
                <x-forms.input-group>
                    <x-slot name="prepend" id="currency"><span class="input-group-text f-14 bg-white-shade">{{ ($currency->currency ? $currency->currency->currency_symbol : company()->currency->currency_symbol ) }}</span></x-slot>
                    <input type="number" min="0" step="0.01" class="form-control height-35 f-14 fixedAllowance monthlyFixedAllowance" name="fixedAllowance" id="fixed_allowance_input" value="{{ $fixedAllowance }}">
                </x-forms.input-group>
            </div>
            <div class="col-md-3">
                <x-forms.input-group>
                    <x-slot name="prepend" id="currency"><span class="input-group-text f-14 bg-white-shade">{{ ($currency->currency ? $currency->currency->currency_symbol : company()->currency->currency_symbol ) }}</span></x-slot>
                    <input type="text" class="form-control height-35 f-14 yearFixedAllowance" value="{{ $fixedAllowance === '' ? '' : $payrollController->currencyFormatterCustom($fixedAllowance * 12) }}" readonly>
                </x-forms.input-group>
            </div>
        </div>
    </div>

    <div class="col-md-12 salary-total mt-2 rounded bg-light">
        <div class="row">
            <div class="col-md-6"><h3 class="heading-h3 mb-0 py-4">@lang('payroll::modules.payroll.costToCompany')</h3></div>
            <div class="col-md-3"><h3 class="heading-h3 mb-0 py-4 monthly-ctc-total">{{ currency_format($annualSalary / 12, ($currency->currency ? $currency->currency->id : company()->currency->id )) }}</h3></div>
            <div class="col-md-3"><h3 class="heading-h3 mb-0 py-4 annual-ctc-total">{{ currency_format($annualSalary, ($currency->currency ? $currency->currency->id : company()->currency->id )) }}</h3></div>
        </div>
    </div>

    <div class="col-md-12 mt-2 rounded">
        @if ($salaryComponents->where('component_type', 'deduction')->count() > 0)
            <div class="col-md-12"><h3 class="heading-h3 mb-0">@lang('payroll::modules.payroll.deduction')</h3></div>
        @endif
        @foreach ($salaryComponents->where('component_type', 'deduction') as $salaryComponent)
            @php
                $componentValue = isset($blankSalaryComponentValues) && $blankSalaryComponentValues ? '' : $payrollController->componentMonthlyValue($salaryComponent, $annualSalary, $basicSalary);
                $componentAnnualValue = $componentValue === '' ? '' : $payrollController->currencyFormatterCustom($componentValue * 12);
            @endphp
            <div class="col-md-12 mt-1">
                <div class="row">
                    <div class="col-md-3"><x-forms.label fieldId="" :fieldLabel="$salaryComponent->component_name" /></div>
                    <div class="col-md-3"><x-forms.label fieldId="" :fieldLabel="'Manual'" /></div>
                    <div class="col-md-3">
                        <x-forms.input-group>
                            <x-slot name="prepend" id="currency"><span class="input-group-text f-14 bg-white-shade">{{ ($currency->currency ? $currency->currency->currency_symbol : company()->currency->currency_symbol ) }}</span></x-slot>
                            <input type="number" step="0.01" min="0" class="form-control height-35 f-14 manual-component deduction-component" data-type="deduction" data-type-id="{{ $salaryComponent->id }}" data-component-name="{{ strtolower($salaryComponent->component_name) }}" name="deduction_variable[{{ $salaryComponent->id }}]" id="deductionVariable{{ $salaryComponent->id }}" value="{{ $componentValue === '' ? '' : round($componentValue, 2) }}">
                        </x-forms.input-group>
                    </div>
                    <div class="col-md-3">
                        <x-forms.input-group>
                            <x-slot name="prepend" id="currency"><span class="input-group-text f-14 bg-white-shade">{{ ($currency->currency ? $currency->currency->currency_symbol : company()->currency->currency_symbol ) }}</span></x-slot>
                            <input type="text" class="form-control height-35 f-14 component-annual" id="variableAnuallyDeduction{{ $salaryComponent->id }}" value="{{ $componentAnnualValue }}" readonly>
                        </x-forms.input-group>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="col-md-12 mt-2 rounded bg-light">
        <div class="row">
            <div class="col-md-6"><h4 class="heading-h5 mb-0 py-4">@lang('app.total') @lang('payroll::modules.payroll.deduction')</h4></div>
            <div class="col-md-3"><h5 class="heading-h5 mb-0 py-4 expenses">{{ currency_format($expenses, ($currency->currency ? $currency->currency->id : company()->currency->id )) }}</h5></div>
            <div class="col-md-3"><h5 class="heading-h5 mb-0 py-4 expensesAnnual">{{ currency_format($expenses * 12, ($currency->currency ? $currency->currency->id : company()->currency->id )) }}</h5></div>
        </div>
    </div>

    <div class="col-md-12 mt-2 rounded bg-light">
        <div class="row">
            <div class="col-md-6"><h4 class="heading-h5 mb-0 py-4">Net Salary</h4></div>
            <div class="col-md-3"><h5 class="heading-h5 mb-0 py-4 net-salary-total">{{ currency_format($annualSalary / 12, ($currency->currency ? $currency->currency->id : company()->currency->id )) }}</h5></div>
            <div class="col-md-3"><h5 class="heading-h5 mb-0 py-4 net-salary-annual-total">{{ currency_format($annualSalary, ($currency->currency ? $currency->currency->id : company()->currency->id )) }}</h5></div>
        </div>
    </div>
</div>
<script>
    $('#annual_salary').val('{{ isset($blankSalaryComponentValues) && $blankSalaryComponentValues ? '' : $annualSalary }}').attr('data-component-calculated-ctc', 'true');

    function parseSalaryAmount(value) {
        return parseFloat((value || '0').toString().replace(/[^0-9.-]/g, '')) || 0;
    }

    function refreshManualSalaryTotals() {
        var basic = parseSalaryAmount($('#basic_type').val());
        var earnings = 0;
        var deductions = 0;
        $('.earning-component').each(function () { earnings += parseSalaryAmount($(this).val()); });
        $('.deduction-component').each(function () {
            var amount = parseSalaryAmount($(this).val());
            deductions += amount;
        });

        var specialAllowance = parseSalaryAmount($('.fixedAllowance').val());
        var monthlyCtc = basic + earnings + specialAllowance - deductions;
        var annualCtc = monthlyCtc * 12;

        $('.manual-component').each(function () {
            var id = $(this).data('type-id');
            var annual = parseSalaryAmount($(this).val()) * 12;
            if ($(this).data('type') == 'deduction') {
                $('#variableAnuallyDeduction' + id).val(number_format(annual));
            } else {
                $('#variableAnually' + id).val(number_format(annual));
            }
        });

        $('.yearFixedAllowance').val(number_format(specialAllowance * 12));
        $('#annual_salary').val(annualCtc.toFixed(2));
        $('.monthly-ctc-total').html(number_format(monthlyCtc));
        $('.annual-ctc-total').html(number_format(annualCtc));
        $('.expenses').html(number_format(deductions));
        $('.expensesAnnual').html(number_format(deductions * 12));
        $('.net-salary-total').html(number_format(monthlyCtc));
        $('.net-salary-annual-total').html(number_format(annualCtc));
    }

    $('.manual-component, .fixedAllowance').on('keyup change', refreshManualSalaryTotals);
    $('.select-picker').selectpicker();
    refreshManualSalaryTotals();

    $('body').tooltip({ selector: '[data-toggle="tooltip"]' });
</script>
