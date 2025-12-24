@extends('layouts.app')
<style>
 /* Force Bootstrap Select dropdown to open on the left */
.hq-wrapper .bootstrap-select .dropdown-menu {
    left: 0 !important;
    right: auto !important;
    transform: none !important;
}

</style>
@section('content')
    <div class="content-wrapper">
        <div class="d-lg-flex d-md-flex d-block justify-content-between action-bar">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                @if(user()->permission('add_dcr_reports') == 'all' || user()->permission('add_dcr_reports') == 'added')
                    <div class="hq-wrapper">
                <form method="GET" action="{{ route('dcr-reports.index') }}" style="margin:0;">
                    <select id="hq-select"
                            name="hq"
                            class="select-picker"
                            data-live-search="true"
                            title="Select Headquarter"
                            onchange="this.form.submit()">
                
                        <option value="">-- All Headquarters --</option>
                
                        @foreach($headquarters as $hq)
                            <option value="{{ $hq->id }}" {{ $selectedHQ == $hq->id ? 'selected' : '' }}>
                                {{ $hq->name }}
                            </option>
                        @endforeach
                
                    </select>
                    </form>
                </div>
                @endif
            </div>
        </div>

        <div class="d-flex flex-column w-tables rounded mt-3 bg-white">
            <!-- Tabs -->
            <ul class="nav nav-tabs px-3 pt-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#doctors-tab">Doctor Visits</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#chemists-tab">Chemist Visits</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#stockists-tab">Stockist Visits</a>
                </li>
            </ul>

            <div class="tab-content p-3">
                <!-- Doctors Tab -->
                <div id="doctors-tab" class="tab-pane active">
                    <x-table class="table-hover border-0" headType="thead-light">
                        <x-slot name="thead">
                            <th>@lang('app.date')</th>
                            <th>Doctor</th>
                            <th>Speciality</th>
                            <th>HQ</th>
                            <th>Station</th>
                            <th>Products</th>
                            <th>Samples Unit</th>
                            <th>POB</th>
                            <th>@lang('app.action')</th>
                        </x-slot>

                        @foreach($reports->where(function($r) { return $r->doctor_id != null || $r->doctorVisits->count() > 0; }) as $report)
                            @if($report->doctorVisits->count() > 0)
                                {{-- Show new multiple visits format --}}
                                @foreach($report->doctorVisits as $visit)
                                    <tr id="row-{{ $report->id }}-visit-{{ $visit->id }}">
                                        <td>{{ $report->report_date->format(company()->date_format) }}</td>
                                        <td>{{ $visit->doctor->fullname ?? $visit->doctor_name ?? '-' }}</td>
                                        <td>{{ $visit->speciality ?? '-' }}</td>
                                        <td>{{ $report->headquarter }}</td>
                                        <td>{{ $report->station }}</td>
                                        <td>
                                            <small>
                                                @if($visit->product1) {{ $visit->product1 }}<br>@endif
                                                @if($visit->product2) {{ $visit->product2 }}<br>@endif
                                                @if($visit->product3) {{ $visit->product3 }}@endif
                                            </small>
                                        </td>
                                        <td>
                                            <small>
                                                @if($visit->product1) {{ $visit->samples_unit1 ?? 0 }}<br>@endif
                                                @if($visit->product2) {{ $visit->samples_unit2 ?? 0 }}<br>@endif
                                                @if($visit->product3) {{ $visit->samples_unit3 ?? 0 }}@endif
                                            </small>
                                        </td>
                                        <td>{{ $visit->pob }}</td>
                                        <td>
                                            <a class="btn btn-sm btn-danger delete-report" data-id="{{ $report->id }}">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                {{-- Show old single-entry format for backward compatibility --}}
                                <tr id="row-{{ $report->id }}">
                                    <td>{{ $report->report_date->format(company()->date_format) }}</td>
                                    <td>{{ $report->doctor->fullname ?? '-' }}</td>
                                    <td>{{ $report->speciality }}</td>
                                    <td>{{ $report->headquarter }}</td>
                                    <td>{{ $report->station }}</td>
                                    <td>
                                        <small>
                                            @if($report->product1) {{ $report->product1 }}<br>@endif
                                            @if($report->product2) {{ $report->product2 }}<br>@endif
                                            @if($report->product3) {{ $report->product3 }}@endif
                                        </small>
                                    </td>
                                    <td>
                                        <small>
                                            @if($report->product1) {{ $report->samples_unit1 ?? 0 }}<br>@endif
                                            @if($report->product2) {{ $report->samples_unit2 ?? 0 }}<br>@endif
                                            @if($report->product3) {{ $report->samples_unit3 ?? 0 }}@endif
                                        </small>
                                    </td>
                                    <td>{{ $report->pob }}</td>
                                    <td>
                                        <a class="btn btn-sm btn-danger delete-report" data-id="{{ $report->id }}">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </x-table>
                </div>

                <!-- Chemists Tab -->
                <div id="chemists-tab" class="tab-pane fade">
                    <x-table class="table-hover border-0" headType="thead-light">
                        <x-slot name="thead">
                            <th>@lang('app.date')</th>
                            <th>Chemist</th>
                            <th>Station</th>
                            <th>RCPA Products</th>
                            <th>@lang('app.action')</th>
                        </x-slot>

                        @foreach($reports->where(function($r) { return $r->chemist_id != null || $r->chemistVisits->count() > 0; }) as $report)
                            @if($report->chemistVisits->count() > 0)
                                {{-- Show new multiple visits format --}}
                                @foreach($report->chemistVisits as $visit)
                                    <tr>
                                        <td>{{ $report->report_date->format(company()->date_format) }}</td>
                                        <td>{{ $visit->chemist->shopname ?? $visit->chemist_name ?? '-' }}</td>
                                        <td>{{ $visit->station ?? '-' }}</td>
                                        <td>
                                            <small>
                                                {{ $visit->rcpa1 }}, {{ $visit->rcpa2 }}, {{ $visit->rcpa3 }}, {{ $visit->rcpa4 }}
                                            </small>
                                        </td>
                                        <td>
                                            <a class="btn btn-sm btn-danger delete-report" data-id="{{ $report->id }}">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                {{-- Show old single-entry format for backward compatibility --}}
                                <tr>
                                    <td>{{ $report->report_date->format(company()->date_format) }}</td>
                                    <td>{{ $report->chemist->shopname ?? '-' }}</td>
                                    <td>{{ $report->chemist_station }}</td>
                                    <td>
                                        <small>
                                            {{ $report->rcpa1 }}, {{ $report->rcpa2 }}, {{ $report->rcpa3 }}, {{ $report->rcpa4 }}
                                        </small>
                                    </td>
                                    <td>
                                        <a class="btn btn-sm btn-danger delete-report" data-id="{{ $report->id }}">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </x-table>
                </div>

                <!-- Stockists Tab -->
                <div id="stockists-tab" class="tab-pane fade">
                    <x-table class="table-hover border-0" headType="thead-light">
                        <x-slot name="thead">
                            <th>@lang('app.date')</th>
                            <th>Stockist</th>
                            <th>Station</th>
                            <th>@lang('app.action')</th>
                        </x-slot>

                        @foreach($reports->where(function($r) { return $r->stockist_id != null || $r->stockistVisits->count() > 0; }) as $report)
                            @if($report->stockistVisits->count() > 0)
                                {{-- Show new multiple visits format --}}
                                @foreach($report->stockistVisits as $visit)
                                    <tr>
                                        <td>{{ $report->report_date->format(company()->date_format) }}</td>
                                        <td>{{ $visit->stockist->shopname ?? $visit->stockist_name ?? '-' }}</td>
                                        <td>{{ $visit->station ?? '-' }}</td>
                                        <td>
                                            <a class="btn btn-sm btn-danger delete-report" data-id="{{ $report->id }}">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                {{-- Show old single-entry format for backward compatibility --}}
                                <tr>
                                    <td>{{ $report->report_date->format(company()->date_format) }}</td>
                                    <td>{{ $report->stockist->shopname ?? '-' }}</td>
                                    <td>{{ $report->stockist_station }}</td>
                                    <td>
                                        <a class="btn btn-sm btn-danger delete-report" data-id="{{ $report->id }}">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </x-table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $('body').on('click', '.delete-report', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: "@lang('messages.sweetAlertTitle')",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: "@lang('messages.confirmDelete')",
        }).then((result) => {
            if (result.isConfirmed) {
                $.easyAjax({
                    url: "{{ route('dcr-reports.destroy', ':id') }}".replace(':id', id),
                    type: 'POST',
                    data: {'_token': '{{ csrf_token() }}', '_method': 'DELETE'},
                    success: function() {
                        window.location.reload();
                    }
                });
            }
        });
    });
    
</script>
<script>
$('#filter_hq').on('change', function () {
    var hqId = $(this).val();

    window.location.href = '?hq=' + hqId;
});
</script>
<script>
    $('.select-picker').selectpicker(); // initialize

// Fix dropdown misalignment AFTER it opens
$('#hq-select').on('shown.bs.select', function () {
    let menu = $(this).closest('.bootstrap-select').find('.dropdown-menu');

    menu.css({
        left: 0,
        right: 'auto',
        transform: 'none'
    });
});


</script>

@endpush

