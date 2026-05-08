@extends('layouts.app')

@section('filter-section')
    <x-filters.filter-box>
        <div class="select-box d-flex pr-2 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.duration')</p>
            <div class="select-status d-flex">
                <input type="text" class="position-relative text-dark form-control border-0 p-2 text-left f-14 f-w-500 border-additional-grey"
                    id="ledgerDateRange" placeholder="@lang('placeholders.dateRange')" value="{{ $startDate }} - {{ $endDate }}">
            </div>
        </div>
        <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
            <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.client')</p>
            <div class="select-status">
                <select class="form-control select-picker" id="partyID" data-live-search="true" data-size="8">
                    <option value="all">@lang('app.all')</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" {{ $partyId == $client->id ? 'selected' : '' }}>{{ $client->company_name ?? $client->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0">
            <x-forms.button-primary id="apply-ledger-filters" icon="check">@lang('app.apply')</x-forms.button-primary>
        </div>
    </x-filters.filter-box>
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">{{ $pageTitle }}</h4>
        </div>
        <div id="ledger-message" class="alert alert-info d-none mb-3"></div>
        <div id="ledger-table-wrap" class="d-flex flex-column w-tables rounded mt-3 bg-white table-responsive">
            <table class="table table-hover border-0 w-100" id="ledger-table">
                <thead>
                    <tr>
                        <th>@lang('app.date')</th>
                        <th>@lang('app.particular')</th>
                        <th class="text-right">@lang('app.debit')</th>
                        <th class="text-right">@lang('app.credit')</th>
                        <th class="text-right">@lang('app.balance')</th>
                    </tr>
                </thead>
                <tbody id="ledger-tbody">
                    <tr><td colspan="5" class="text-center text-muted">Select a CFA and click Apply</td></tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(function () {
        var dateRange = $('#ledgerDateRange').val().split(' - ');
        var startDate = dateRange[0] ? dateRange[0].trim() : '';
        var endDate = dateRange[1] ? dateRange[1].trim() : '';

        $('#ledgerDateRange').daterangepicker({
            locale: { format: '{{ company()->moment_date_format }}' },
            startDate: startDate || moment().startOf('month'),
            endDate: endDate || moment(),
            ranges: {
                '@lang("app.today")': [moment(), moment()],
                '@lang("app.yesterday")': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                '@lang("app.last7Days")': [moment().subtract(6, 'days'), moment()],
                '@lang("app.last30Days")': [moment().subtract(29, 'days'), moment()],
                '@lang("app.thisMonth")': [moment().startOf('month'), moment().endOf('month')],
                '@lang("app.lastMonth")': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
            }
        });

        $('#apply-ledger-filters').on('click', function () {
            var range = $('#ledgerDateRange').data('daterangepicker');
            var start = range.startDate.format('{{ company()->moment_date_format }}');
            var end = range.endDate.format('{{ company()->moment_date_format }}');
            var partyId = $('#partyID').val();
            if (partyId === 'all' || !partyId) {
                $('#ledger-message').removeClass('d-none').text('@lang("app.selectClientAndApply")');
                $('#ledger-tbody').html('<tr><td colspan="5" class="text-center text-muted">@lang("app.selectClientAndApply")</td></tr>');
                return;
            }
            $('#ledger-message').addClass('d-none');
            $('#ledger-tbody').html('<tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i> @lang("app.loading")</td></tr>');
            $.ajax({
                url: '{{ route("cfa-ledger.data") }}',
                type: 'GET',
                data: { party_id: partyId, start_date: start, end_date: end },
                success: function (res) {
                    if (res && res.rows) {
                        var rows = res.rows;
                        var partyName = res.party_name || '';
                        var opening = parseFloat(res.opening_balance) || 0;
                        var html = '';
                        if (partyName) {
                            html += '<tr class="table-light"><td colspan="4" class="font-weight-bold">' + partyName + '</td><td class="text-right"></td></tr>';
                        }
                        if (opening !== 0) {
                            html += '<tr><td>-</td><td>@lang("app.openingBalance")</td><td class="text-right">' + (opening > 0 ? opening.toFixed(2) : '') + '</td><td class="text-right">' + (opening < 0 ? (-opening).toFixed(2) : '') + '</td><td class="text-right">' + opening.toFixed(2) + '</td></tr>';
                        }
                        var runningBalance = opening;
                        for (var i = 0; i < rows.length; i++) {
                            var r = rows[i];
                            runningBalance = parseFloat(r.balance);
                            var partCell = r.link ? '<a href="' + r.link + '" class="text-dark">' + escapeHtml(r.particular) + '</a>' : escapeHtml(r.particular);
                            html += '<tr><td>' + r.date + '</td><td>' + partCell + '</td><td class="text-right">' + (r.debit ? r.debit.toFixed(2) : '') + '</td><td class="text-right">' + (r.credit ? r.credit.toFixed(2) : '') + '</td><td class="text-right">' + r.balance.toFixed(2) + '</td></tr>';
                        }
                        if (rows.length === 0 && opening === 0) {
                            html = '<tr><td colspan="5" class="text-center text-muted">@lang("messages.noData")</td></tr>';
                        }
                        $('#ledger-tbody').html(html);
                    } else {
                        $('#ledger-tbody').html('<tr><td colspan="5" class="text-center text-muted">@lang("messages.noData")</td></tr>');
                    }
                },
                error: function () {
                    $('#ledger-tbody').html('<tr><td colspan="5" class="text-center text-danger">@lang("messages.errorOccurred")</td></tr>');
                }
            });
        });

        function escapeHtml(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });
</script>
@endpush
