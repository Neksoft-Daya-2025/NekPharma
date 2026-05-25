<div class="col-lg-12 col-md-12 ntfcn-tab-content-left w-100 p-4">
    @php
        $setupItems = [
            [
                'title' => 'Payroll Currency',
                'text' => 'Salary currency for payroll.',
                'tab' => 'payroll-currency-setting',
                'icon' => 'fa-money-bill',
            ],
            [
                'title' => 'Payslip Fields',
                'text' => 'Fields shown in salary slips.',
                'tab' => 'salary-setting',
                'icon' => 'fa-file-invoice',
            ],
            [
                'title' => 'Salary Components',
                'text' => 'Earnings and deductions.',
                'tab' => 'salary-components',
                'icon' => 'fa-list-ul',
            ],
            [
                'title' => 'Salary Groups',
                'text' => 'Reusable salary structures.',
                'tab' => 'salary-groups',
                'icon' => 'fa-layer-group',
            ],
            [
                'title' => 'Tax / TDS',
                'text' => 'Tax slabs and status.',
                'tab' => 'salary-tds',
                'icon' => 'fa-percent',
            ],
            [
                'title' => 'Payment Methods',
                'text' => 'Ways salaries are paid.',
                'tab' => 'payment-methods',
                'icon' => 'fa-credit-card',
            ],
        ];
    @endphp

    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3">
        <div>
            <h4 class="f-18 f-w-500 mb-1">Quick Payroll Setup</h4>
            <p class="f-13 text-lightest mb-0">Open the setting you need and finish payroll setup faster.</p>
        </div>
    </div>

    <div class="row">
        @foreach ($setupItems as $item)
            <div class="col-xl-4 col-md-6 mb-3">
                <div class="border rounded bg-white h-100 p-3">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="d-flex align-items-start">
                            <span class="btn-secondary rounded d-inline-flex align-items-center justify-content-center mr-3"
                                  style="width: 36px; height: 36px;">
                                <i class="fa {{ $item['icon'] }} f-14"></i>
                            </span>
                            <div>
                                <h5 class="f-15 f-w-500 mb-1">{{ $item['title'] }}</h5>
                                <p class="f-12 text-lightest mb-0">{{ $item['text'] }}</p>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('payroll.payroll_settings') }}?tab={{ $item['tab'] }}"
                       class="btn btn-sm btn-primary f-12 mt-3">
                        Open
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
