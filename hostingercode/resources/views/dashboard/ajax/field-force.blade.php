<div class="row">
    {{-- DCRs Submitted Today --}}
    <div class="col-xl-3 col-lg-6 col-md-6 mb-3">
        <div class="bg-white p-20 rounded b-shadow-4 d-flex justify-content-between align-items-center">
            <div class="d-block">
                <h5 class="f-15 f-w-500 mb-20 text-darkest-grey">DCRs Submitted Today</h5>
                <p class="mb-0 f-15 font-weight-bold text-blue text-primary">{{ $dcrsSubmittedToday ?? 0 }}</p>
            </div>
            <div class="d-block">
                <i class="fa fa-clipboard-list text-lightest f-18"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Pending DCR Approvals --}}
    @if (in_array('dcr_reports', user_modules()))
        <div class="col-lg-6 col-md-12 mb-3">
            <x-cards.data :title="__('modules.dashboard.pendingDcrApprovals')" padding="false" otherClasses="h-200">
                <x-table>
                    @forelse ($pendingDcrReports ?? [] as $report)
                        <tr>
                            <td class="pl-20">
                                <x-employee :user="$report->user"/>
                            </td>
                            <td class="text-darkest-grey">{{ $report->report_date ? \Carbon\Carbon::parse($report->report_date)->translatedFormat(company()->date_format) : '-' }}</td>
                            <td class="f-14">{{ $report->work_status ?? '-' }}</td>
                            <td align="right" class="pr-20">
                                <a href="{{ route('dcr-management.index', ['mode' => 'approve']) }}" class="btn btn-sm btn-primary">@lang('app.view')</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="shadow-none">
                                <x-cards.no-record icon="clipboard-list" :message="__('messages.noRecordFound')"/>
                            </td>
                        </tr>
                    @endforelse
                </x-table>
                @if (($pendingDcrReports ?? collect())->isNotEmpty())
                    <div class="p-3 border-top">
                        <a href="{{ route('dcr-management.index', ['mode' => 'approve']) }}" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                @endif
            </x-cards.data>
        </div>
    @endif

    {{-- Pending Tour Approvals --}}
    @if (in_array('tours', user_modules()))
        <div class="col-lg-6 col-md-12 mb-3">
            <x-cards.data :title="__('modules.dashboard.pendingTourApprovals')" padding="false" otherClasses="h-200">
                <x-table>
                    @forelse ($pendingTours ?? [] as $tour)
                        <tr>
                            <td class="pl-20">
                                <x-employee :user="$tour->user"/>
                            </td>
                            <td class="text-darkest-grey">{{ $tour->date ? $tour->date->translatedFormat(company()->date_format) : '-' }}</td>
                            <td class="f-14">{{ optional($tour->headquarter)->name ?? '-' }}</td>
                            <td align="right" class="pr-20">
                                <a href="{{ route('tours.index', ['mode' => 'approve']) }}" class="btn btn-sm btn-primary">@lang('app.view')</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="shadow-none">
                                <x-cards.no-record icon="route" :message="__('messages.noRecordFound')"/>
                            </td>
                        </tr>
                    @endforelse
                </x-table>
                @if (($pendingTours ?? collect())->isNotEmpty())
                    <div class="p-3 border-top">
                        <a href="{{ route('tours.index', ['mode' => 'approve']) }}" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                @endif
            </x-cards.data>
        </div>
    @endif

    {{-- Pending Expense Approvals --}}
    @if (in_array('expenses', user_modules()))
        <div class="col-lg-6 col-md-12 mb-3">
            <x-cards.data :title="__('modules.dashboard.pendingExpenseApprovals')" padding="false" otherClasses="h-200">
                <x-table>
                    @forelse ($pendingExpenses ?? [] as $expense)
                        <tr>
                            <td class="pl-20">
                                <x-employee :user="$expense->user"/>
                            </td>
                            <td class="text-darkest-grey">{{ $expense->item_name ?? '-' }}</td>
                            <td class="f-14">{{ currency_format($expense->total ?? 0, $expense->currency_id ?? company()->currency_id) }}</td>
                            <td align="right" class="pr-20">
                                <a href="{{ route('expenses.status') }}" class="btn btn-sm btn-primary">@lang('app.view')</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="shadow-none">
                                <x-cards.no-record icon="money-bill" :message="__('messages.noRecordFound')"/>
                            </td>
                        </tr>
                    @endforelse
                </x-table>
                @if (($pendingExpenses ?? collect())->isNotEmpty())
                    <div class="p-3 border-top">
                        <a href="{{ route('expenses.status') }}" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                @endif
            </x-cards.data>
        </div>
    @endif
</div>
