@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="row action-bar mb-2 align-items-center">
            <div class="col-12 col-md mb-2 mb-md-0">
                <h4 class="mb-0">Employee leave report</h4>
            </div>
            <div class="col-12 col-md-auto text-md-right">
                <a href="{{ route('leave-report.employee-leave-report.export') }}" class="btn btn-primary">
                    <i class="fa fa-file-excel"></i> Export to Excel
                </a>
            </div>
        </div>

        <x-cards.data>
            <div class="row mb-4">
                <div class="col-12 text-dark-grey f-14">
                    <p class="mb-2">This page shows each person’s <strong>leave balances by type</strong> (for example Casual, Sick, or Earned). The figures are the same ones used everywhere else in the app—on the employee’s own leave screen, when someone applies for leave, and in the background process that updates balances daily.</p>
                    <p class="mb-3">You can scroll through the list, or use <strong>Export to Excel</strong> to get a file you can sort, filter, or keep for your records. The download includes the same balances plus extra technical columns: how the “per month” rate is set in your rules, how many months have been counted in the current leave year, and any monthly cap your company has configured.</p>
                    <p class="mb-2 font-weight-bold text-dark f-14">What the main table shows</p>
                    <ul class="mb-0" style="padding-left: 1.35rem;">
                        <li class="mb-1"><strong>Allotted</strong> — how much that leave type is worth for this person in the current period (after rules like joining date and pro-rata).</li>
                        <li class="mb-1"><strong>Used</strong> — leave days already taken (including requests still waiting for approval, if your rules count those).</li>
                        <li class="mb-1"><strong>Remaining</strong> — what is still free to use under the current rules.</li>
                        <li class="mb-1"><strong>Over</strong> — if someone went beyond the normal limit (where your policy allows that).</li>
                        <li class="mb-0"><strong>Unused</strong> — any unused part your rules track (for example under monthly leave types).</li>
                    </ul>
                </div>
            </div>

            @if(isset($leavesStartFrom))
                <div class="border rounded bg-additional-grey p-3 mb-4 f-14 w-100">
                    <div class="font-weight-bold text-dark mb-2">What your company means by a “leave year”</div>
                    @if($leavesStartFrom === 'joining_date')
                        <p class="mb-2">Your organisation is set so that <strong>each employee’s own date of joining</strong> is the anchor for their leave. Think of it as a personal “leave birthday”: each year, their leave cycle lines up with the <strong>month and day they started</strong> (or the same idea in the way your system was configured), not with a single January or April date for the whole company.</p>
                        <p class="mb-2">That means one person’s “year 2” of leave may not start on the same calendar day as a colleague’s. <strong>Balances are built a little at a time from that starting point</strong>—so someone who joined in June does not get the same timing as someone who joined in March. The numbers in this report follow that same logic, so you can trust them to match time-off decisions for each individual.</p>
                        <p class="mb-0">If you need the exact <strong>rate per month</strong> and <strong>how many months are in the count so far</strong> for a given leave type, open the <strong>extra detail</strong> section under each person’s table, or use the Excel file.</p>
                    @else
                        <p class="mb-2">Your organisation uses <strong>one shared leave year for everyone</strong>, starting in <strong>{{ $fiscalMonthName ?? '—' }}</strong> each time the cycle restarts. All employees’ balances line up to that same calendar, which is easier when HR works to a financial year or a fixed policy year.</p>
                        <p class="mb-2">When someone joins <strong>in the middle of that year</strong>, they are usually not given a full year’s leave on day one. The system can start them with a <strong>smaller, fair amount</strong> for the months left in the year, and then the next year they follow the same cycle as everyone else. What you see on this report is consistent with that setup.</p>
                        <p class="mb-0">For a breakdown of <strong>per month rates, months counted, and any monthly cap</strong>, use the <strong>extra detail</strong> block under a person’s name or download <strong>Export to Excel</strong>.</p>
                    @endif
                </div>
            @endif

            @php
                $groupedData = [];
                foreach ($reportData as $data) {
                    $employeeId = $data['employee_id'];
                    if (!isset($groupedData[$employeeId])) {
                        $groupedData[$employeeId] = [
                            'employee_name' => $data['employee_name'],
                            'leave_types' => [],
                        ];
                    }
                    $groupedData[$employeeId]['leave_types'][] = $data;
                }
            @endphp

            @forelse($groupedData as $employeeId => $employeeData)
                @php
                    $firstLeave = $employeeData['leave_types'][0] ?? null;
                    $totalNoOfLeaves = 0;
                    $totalLeavesTaken = 0;
                    $totalRemainingLeaves = 0;
                    $totalOver = 0;
                    $totalUnused = 0;
                    foreach ($employeeData['leave_types'] as $leaveData) {
                        $totalNoOfLeaves += $leaveData['no_of_leaves'];
                        $totalLeavesTaken += $leaveData['leaves_taken'];
                        $totalRemainingLeaves += $leaveData['remaining_leaves'];
                        $totalOver += $leaveData['over_utilized'];
                        $totalUnused += $leaveData['unused_leaves'];
                    }
                @endphp

                <div class="card border rounded mb-4 overflow-hidden w-100">
                    <div class="card-header bg-white border-bottom-0 pb-0 pt-3 px-3">
                        <h5 class="mb-1 font-weight-bold">
                            @if($firstLeave)
                                <span class="text-uppercase text-dark-grey f-16">{{ $firstLeave['employee_code'] }}</span>
                                <span class="text-dark-grey f-16 mx-1">—</span>
                            @endif
                            {{ $employeeData['employee_name'] }}
                        </h5>
                        @if($firstLeave)
                            <div class="f-13 text-dark-grey row">
                                <div class="col-lg-6 mb-2 mb-lg-0">
                                    <span class="d-block d-md-inline"><span class="text-lightest">Joined</span> {{ $firstLeave['joining_date'] ?? '—' }}</span>
                                </div>
                                <div class="col-lg-6">
                                    <span class="d-block d-md-inline"><span class="text-lightest">Role</span> {{ $firstLeave['designation'] }}</span>
                                    <span class="d-none d-md-inline mx-2">·</span>
                                    <span class="d-block d-md-inline"><span class="text-lightest">Dept</span> {{ $firstLeave['department'] }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="card-body pt-2 px-3">
                        <div class="table-responsive w-100">
                            <table class="table table-hover table-bordered mb-0 f-13 w-100 text-left">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="align-middle">Leave</th>
                                        <th class="text-right align-middle">Allotted</th>
                                        <th class="text-right align-middle">Used</th>
                                        <th class="text-right align-middle">Remaining</th>
                                        <th class="text-right align-middle">Over</th>
                                        <th class="text-right align-middle">Unused</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($employeeData['leave_types'] as $leaveData)
                                        <tr>
                                            <td class="align-middle">
                                                <span class="badge badge-pill px-2 py-1" style="background-color: {{ $leaveData['leave_type_color'] }}; color: #fff;">
                                                    {{ $leaveData['leave_type'] }}
                                                </span>
                                            </td>
                                            <td class="text-right align-middle font-weight-bold">{{ $leaveData['no_of_leaves'] }}</td>
                                            <td class="text-right align-middle">{{ $leaveData['leaves_taken'] }}</td>
                                            <td class="text-right align-middle font-weight-bold text-success">{{ $leaveData['remaining_leaves'] }}</td>
                                            <td class="text-right align-middle">
                                                @if($leaveData['over_utilized'] > 0)
                                                    <span class="text-danger">{{ $leaveData['over_utilized'] }}</span>
                                                @else
                                                    <span class="text-lightest">0</span>
                                                @endif
                                            </td>
                                            <td class="text-right align-middle">{{ $leaveData['unused_leaves'] }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="bg-additional-grey font-weight-bold">
                                        <td class="align-middle">Total</td>
                                        <td class="text-right align-middle">{{ $totalNoOfLeaves }}</td>
                                        <td class="text-right align-middle">{{ $totalLeavesTaken }}</td>
                                        <td class="text-right align-middle">{{ $totalRemainingLeaves }}</td>
                                        <td class="text-right align-middle">{{ $totalOver }}</td>
                                        <td class="text-right align-middle">{{ $totalUnused }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <details class="mt-3 border-top pt-3 w-100 text-left">
                            <summary class="f-13 text-primary cursor-pointer user-select-none d-block w-100">Show extra detail (per month, months counted, cap)</summary>
                            <div class="table-responsive mt-3 w-100">
                                <table class="table table-sm table-bordered mb-0 f-12 text-dark-grey w-100">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="align-middle">Leave</th>
                                            <th class="text-right align-middle" title="How much the rules give per month">Per month (rules)</th>
                                            <th class="text-right align-middle" title="How many months count so far in this year">Months counted so far</th>
                                            <th class="text-right align-middle" title="Optional max leaves per month">Cap per month (if any)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($employeeData['leave_types'] as $leaveData)
                                            <tr>
                                                <td>{{ $leaveData['leave_type'] }}</td>
                                                <td class="text-right">
                                                    @if(!empty($leaveData['quota_manual']))
                                                        <span class="text-muted">Manual quota</span>
                                                    @else
                                                        {{ isset($leaveData['per_month_pro_rata']) ? rtrim(rtrim(number_format((float) $leaveData['per_month_pro_rata'], 2, '.', ''), '0'), '.') : '—' }}
                                                    @endif
                                                </td>
                                                <td class="text-right">
                                                    @if(!empty($leaveData['quota_manual']))
                                                        —
                                                    @elseif(isset($leaveData['months_in_cycle']))
                                                        {{ $leaveData['months_in_cycle'] }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td class="text-right">{{ $leaveData['monthly_limit'] > 0 ? $leaveData['monthly_limit'] : '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="f-12 text-dark-grey mt-3 text-left w-100">
                                <p class="mb-2"><strong>Per month (rules)</strong> is the notional amount your leave policy assigns each month (for example, a fraction of an annual pot, or a fixed amount per month for a “monthly” leave type). It is taken from the same settings HR uses for leave types.</p>
                                <p class="mb-2"><strong>Months counted so far</strong> is how many months in the current leave cycle the system is using to build the balance. It is the same value the overnight calculation uses, so the main table and this detail always stay in step.</p>
                                <p class="mb-2"><strong>Cap per month (if any)</strong> is only when your company has set a <strong>maximum</strong> number of days that can be taken in a single calendar month for that type. If you see a dash, no extra monthly cap is applied beyond the other rules.</p>
                                <p class="mb-0 text-lightest f-11">If a line says <strong>Manual quota</strong>, that balance was entered or fixed by HR instead of using the automatic calculation, so the per-month and month-count cells may not apply in the same way.</p>
                            </div>
                        </details>
                    </div>
                </div>
            @empty
                <x-cards.no-record icon="redo" :message="__('messages.noRecordFound')" />
            @endforelse
        </x-cards.data>
    </div>
@endsection
