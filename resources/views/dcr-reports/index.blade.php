@extends('layouts.app')
<style>
 /* Force Bootstrap Select dropdown to open on the left */
.hq-wrapper .bootstrap-select .dropdown-menu {
    left: 0 !important;
    right: auto !important;
    transform: none !important;
}

.dcr-pagination {
    border-top: 1px solid #e8eef3;
    gap: 8px;
}

.dcr-pagination .btn {
    min-width: 34px;
}

.dcr-table-scroll-box {
    max-height: 420px;
    overflow: auto;
    border: 1px solid #e8eef3;
    border-radius: 6px;
}

.dcr-table-scroll-box table {
    margin-bottom: 0;
}

</style>
@section('content')
    <div class="content-wrapper">
        <div class="d-lg-flex d-md-flex d-block justify-content-between align-items-center action-bar flex-wrap">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                <form method="GET" action="{{ route('dcr-management.index') }}" id="filter-form" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                    @if(user()->permission('add_dcr_reports') == 'all' || user()->permission('add_dcr_reports') == 'added')
                        <div class="hq-wrapper">
                            <select id="hq-select"
                                    name="hq"
                                    class="select-picker"
                                    data-live-search="true"
                                    title="Select Headquarter"
                                    style="min-width: 200px;">
                            
                                <option value="">-- All Headquarters --</option>
                            
                                @foreach($headquarters as $hq)
                                    <option value="{{ $hq->id }}" {{ $selectedHQ == $hq->id ? 'selected' : '' }}>
                                        {{ $hq->name }}
                                    </option>
                                @endforeach
                            
                            </select>
                        </div>
                        @if(isset($areas) && $areas->isNotEmpty())
                        <div class="area-wrapper">
                            <select name="area" id="area-select" class="select-picker" data-live-search="true" title="Select Area" style="min-width: 160px;">
                                <option value="">-- All Areas --</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}" {{ (isset($selectedArea) && $selectedArea == $area->id) ? 'selected' : '' }}>{{ $area->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        @if(isset($regions) && $regions->isNotEmpty())
                        <div class="region-wrapper">
                            <select name="region" id="region-select" class="select-picker" data-live-search="true" title="Select Region" style="min-width: 160px;">
                                <option value="">-- All Regions --</option>
                                @foreach($regions as $region)
                                    <option value="{{ $region->id }}" {{ (isset($selectedRegion) && $selectedRegion == $region->id) ? 'selected' : '' }}>{{ $region->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                    @endif

                    @if(isset($employees) && count($employees) > 0)
                        <div class="employee-wrapper">
                            <select name="employee_id" id="employee-select" class="select-picker" data-live-search="true" title="Select Employee" style="min-width: 200px;">
                                <option value="">-- All Employees --</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee['id'] }}" {{ (isset($selectedEmployeeId) && $selectedEmployeeId == $employee['id']) ? 'selected' : '' }}>
                                        {{ $employee['name'] }}
                                        @if(!empty($employee['designation']))
                                            ({{ $employee['designation'] }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    
                    <div class="date-filter-wrapper" style="display: flex; gap: 10px; align-items: center;">
                        <label class="mb-0" style="font-weight: 500;">From Date:</label>
                        <input type="date" 
                               name="from_date" 
                               id="from_date" 
                               class="form-control" 
                               value="{{ $fromDate ?? '' }}"
                               style="width: 150px;">
                    </div>
                    
                    <div class="date-filter-wrapper" style="display: flex; gap: 10px; align-items: center;">
                        <label class="mb-0" style="font-weight: 500;">To Date:</label>
                        <input type="date" 
                               name="to_date" 
                               id="to_date" 
                               class="form-control" 
                               value="{{ $toDate ?? '' }}"
                               style="width: 150px;">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa fa-filter"></i> Apply Filter
                    </button>

                    <a href="{{ route('dcr-management.export', request()->query()) }}" class="btn btn-secondary btn-sm">
                        <i class="fa fa-file-export"></i> Export
                    </a>
                    
                    @if($selectedHQ || !empty($selectedArea) || !empty($selectedRegion) || (!empty($selectedEmployeeId) && $selectedEmployeeId != 'all') || $fromDate || $toDate)
                        <a href="{{ route('dcr-management.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa fa-times"></i> Clear
                        </a>
                    @endif
                </form>
            </div>
            @if(!empty($dcrDraftResumeInfo['has_draft']) && !empty($dcrDraftResumeInfo['complete']))
                <div class="d-flex align-items-center flex-wrap justify-content-end mt-2 mt-lg-0 pl-lg-3">
                    <a href="{{ route('dcr-management.create') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fa fa-edit"></i> Edit DCR draft
                    </a>
                    @if(!empty($dcrDraftResumeInfo['report_date']))
                        <span class="text-muted small ml-2">{{ \Carbon\Carbon::parse($dcrDraftResumeInfo['report_date'])->format('d M Y') }}</span>
                    @endif
                </div>
            @elseif(!empty($dcrDraftResumeInfo['has_draft']) && empty($dcrDraftResumeInfo['complete']))
                <div class="d-flex flex-wrap align-items-center justify-content-end mt-2 mt-lg-0 pl-lg-3">
                    <span class="badge badge-warning" title="Complete required fields, then Save DCR draft">Incomplete draft</span>
                    <a href="{{ route('dcr-management.create') }}" class="btn btn-outline-secondary btn-sm ml-2">
                        <i class="fa fa-folder-open"></i> Open draft
                    </a>
                </div>
            @endif
        </div>

        @php
            $otherWorkReports = $reports->filter(function ($report) {
                return ! $report->doctor_id
                    && ! $report->chemist_id
                    && ! $report->stockist_id
                    && $report->doctorVisits->count() === 0
                    && $report->chemistVisits->count() === 0
                    && $report->stockistVisits->count() === 0;
            });
        @endphp

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
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#other-work-tab">Other Work</a>
                </li>
            </ul>

            @php
                $formatVisitTime = function ($record) {
                    return $record && $record->created_at
                        ? $record->created_at->timezone(company()->timezone)->format('h:i A')
                        : '-';
                };

                $formatVisitLocation = function ($record) {
                    if (
                        ! $record
                        || $record->latitude === null
                        || $record->longitude === null
                        || $record->latitude === ''
                        || $record->longitude === ''
                    ) {
                        return null;
                    }

                    $label = $record->latitude . ', ' . $record->longitude;

                    return [
                        'label' => $label,
                        'url' => 'https://www.google.com/maps/search/?api=1&query=' . urlencode($label),
                    ];
                };
            @endphp

            <div class="tab-content p-3">
                <!-- Doctors Tab -->
                <div id="doctors-tab" class="tab-pane active">
                    <x-table class="table-hover border-0" headType="thead-light">
                        <x-slot name="thead">
                            <th>@lang('app.date')</th>
                            @if($showVisitEmployeeColumn ?? false)
                                <th>Submitted By</th>
                            @endif
                            <th>Doctor</th>
                            <th>Speciality</th>
                            <th>HQ</th>
                            <th>Station</th>
                            <th>Time</th>
                            <th>Location</th>
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
                                        @if($showVisitEmployeeColumn ?? false)
                                            <td>{{ $report->employee_name_snapshot ?? optional($report->user)->name ?? ('Employee #' . $report->user_id) }}</td>
                                        @endif
                                        <td>{{ $visit->doctor->fullname ?? $visit->doctor_name ?? '-' }}</td>
                                        <td>{{ $visit->speciality ?? '-' }}</td>
                                        <td>{{ $report->headquarter }}</td>
                                        <td>{{ $report->station }}</td>
                                        <td>{{ $formatVisitTime($visit) }}</td>
                                        @php($visitLocation = $formatVisitLocation($visit))
                                        <td>
                                            @if($visitLocation)
                                                <a href="{{ $visitLocation['url'] }}" target="_blank" rel="noopener">View map</a>
                                                <div><small class="text-muted">{{ $visitLocation['label'] }}</small></div>
                                            @else
                                                -
                                            @endif
                                        </td>
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
                                    @if($showVisitEmployeeColumn ?? false)
                                        <td>{{ $report->employee_name_snapshot ?? optional($report->user)->name ?? ('Employee #' . $report->user_id) }}</td>
                                    @endif
                                    <td>{{ $report->doctor->fullname ?? '-' }}</td>
                                    <td>{{ $report->speciality }}</td>
                                    <td>{{ $report->headquarter }}</td>
                                    <td>{{ $report->station }}</td>
                                    <td>{{ $formatVisitTime($report) }}</td>
                                    <td>-</td>
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
                            @if($showVisitEmployeeColumn ?? false)
                                <th>Submitted By</th>
                            @endif
                            <th>Chemist</th>
                            <th>Station</th>
                            <th>Time</th>
                            <th>Location</th>
                            <th>RCPA Products</th>
                            <th>@lang('app.action')</th>
                        </x-slot>

                        @foreach($reports->where(function($r) { return $r->chemist_id != null || $r->chemistVisits->count() > 0; }) as $report)
                            @if($report->chemistVisits->count() > 0)
                                {{-- Show new multiple visits format --}}
                                @foreach($report->chemistVisits as $visit)
                                    <tr>
                                        <td>{{ $report->report_date->format(company()->date_format) }}</td>
                                        @if($showVisitEmployeeColumn ?? false)
                                            <td>{{ $report->employee_name_snapshot ?? optional($report->user)->name ?? ('Employee #' . $report->user_id) }}</td>
                                        @endif
                                        <td>{{ $visit->chemist->shopname ?? $visit->chemist_name ?? '-' }}</td>
                                        <td>{{ $visit->station ?? '-' }}</td>
                                        <td>{{ $formatVisitTime($visit) }}</td>
                                        @php($visitLocation = $formatVisitLocation($visit))
                                        <td>
                                            @if($visitLocation)
                                                <a href="{{ $visitLocation['url'] }}" target="_blank" rel="noopener">View map</a>
                                                <div><small class="text-muted">{{ $visitLocation['label'] }}</small></div>
                                            @else
                                                -
                                            @endif
                                        </td>
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
                                    @if($showVisitEmployeeColumn ?? false)
                                        <td>{{ $report->employee_name_snapshot ?? optional($report->user)->name ?? ('Employee #' . $report->user_id) }}</td>
                                    @endif
                                    <td>{{ $report->chemist->shopname ?? '-' }}</td>
                                    <td>{{ $report->chemist_station }}</td>
                                    <td>{{ $formatVisitTime($report) }}</td>
                                    <td>-</td>
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
                            @if($showVisitEmployeeColumn ?? false)
                                <th>Submitted By</th>
                            @endif
                            <th>Stockist</th>
                            <th>Station</th>
                            <th>Time</th>
                            <th>Location</th>
                            <th>@lang('app.action')</th>
                        </x-slot>

                        @foreach($reports->where(function($r) { return $r->stockist_id != null || $r->stockistVisits->count() > 0; }) as $report)
                            @if($report->stockistVisits->count() > 0)
                                {{-- Show new multiple visits format --}}
                                @foreach($report->stockistVisits as $visit)
                                    <tr>
                                        <td>{{ $report->report_date->format(company()->date_format) }}</td>
                                        @if($showVisitEmployeeColumn ?? false)
                                            <td>{{ $report->employee_name_snapshot ?? optional($report->user)->name ?? ('Employee #' . $report->user_id) }}</td>
                                        @endif
                                        <td>{{ $visit->stockist->shopname ?? $visit->stockist_name ?? '-' }}</td>
                                        <td>{{ $visit->station ?? '-' }}</td>
                                        <td>{{ $formatVisitTime($visit) }}</td>
                                        @php($visitLocation = $formatVisitLocation($visit))
                                        <td>
                                            @if($visitLocation)
                                                <a href="{{ $visitLocation['url'] }}" target="_blank" rel="noopener">View map</a>
                                                <div><small class="text-muted">{{ $visitLocation['label'] }}</small></div>
                                            @else
                                                -
                                            @endif
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
                                    @if($showVisitEmployeeColumn ?? false)
                                        <td>{{ $report->employee_name_snapshot ?? optional($report->user)->name ?? ('Employee #' . $report->user_id) }}</td>
                                    @endif
                                    <td>{{ $report->stockist->shopname ?? '-' }}</td>
                                    <td>{{ $report->stockist_station }}</td>
                                    <td>{{ $formatVisitTime($report) }}</td>
                                    <td>-</td>
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

                <!-- Other Work Tab -->
                <div id="other-work-tab" class="tab-pane fade">
                    <x-table class="table-hover border-0" headType="thead-light">
                        <x-slot name="thead">
                            <th>@lang('app.date')</th>
                            @if($showVisitEmployeeColumn ?? false)
                                <th>Submitted By</th>
                            @endif
                            <th>Work Type</th>
                            <th>HQ</th>
                            <th>Station</th>
                            <th>Work With</th>
                            <th>Submit To</th>
                            <th>Time</th>
                            <th>Remark</th>
                            <th>Status</th>
                            <th>@lang('app.action')</th>
                        </x-slot>

                        @forelse($otherWorkReports as $report)
                            <tr id="row-{{ $report->id }}">
                                <td>{{ $report->report_date->format(company()->date_format) }}</td>
                                @if($showVisitEmployeeColumn ?? false)
                                    <td>{{ $report->employee_name_snapshot ?? optional($report->user)->name ?? ('Employee #' . $report->user_id) }}</td>
                                @endif
                                <td>{{ $report->work_status ?: '-' }}</td>
                                <td>{{ $report->headquarter ?: '-' }}</td>
                                <td>{{ $report->station ?: '-' }}</td>
                                <td>{{ $report->work_with ?: '-' }}</td>
                                <td>{{ optional($report->submittedTo)->name ?: '-' }}</td>
                                <td>{{ $formatVisitTime($report) }}</td>
                                <td>{{ $report->remark ?: '-' }}</td>
                                <td>
                                    @if($report->status === 'approved')
                                        <span class="badge badge-success">Approved</span>
                                    @elseif($report->status === 'rejected')
                                        <span class="badge badge-danger">Rejected</span>
                                    @elseif($report->status === 'draft')
                                        <span class="badge badge-secondary">Draft</span>
                                    @else
                                        <span class="badge badge-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-danger delete-report" data-id="{{ $report->id }}">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ ($showVisitEmployeeColumn ?? false) ? 11 : 10 }}" class="text-center text-muted">
                                    No other work DCR reports found
                                </td>
                            </tr>
                        @endforelse
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
                    url: "{{ route('dcr-management.destroy', ':id') }}".replace(':id', id),
                    type: 'POST',
                    data: {'_token': '{{ csrf_token() }}', '_method': 'DELETE'},
                    success: function() {
                        window.location.reload();
                    }
                });
            }
        });
    });

    function setupDcrTablePagination() {
        var rowsPerPage = 10;
        var scrollBuffer = 120;

        $('.tab-pane table').each(function(index) {
            var table = $(this);
            var scrollBox = table.closest('.dcr-table-scroll-box');
            var bodyRows = table.find('tbody tr');
            var dataRows = bodyRows.filter(function() {
                return !$(this).find('td[colspan]').length;
            });

            if (!scrollBox.length) {
                table.wrap('<div class="dcr-table-scroll-box"></div>');
                scrollBox = table.closest('.dcr-table-scroll-box');
            }

            scrollBox.next('.dcr-pagination').remove();

            if (dataRows.length <= rowsPerPage) {
                bodyRows.show();
                scrollBox.css('max-height', 'none');
                return;
            }

            scrollBox.css('max-height', '');

            var visibleRows = rowsPerPage;
            var pager = $('<div class="dcr-pagination d-flex align-items-center justify-content-end flex-wrap px-3 py-2"></div>');
            var summary = $('<small class="text-muted"></small>');

            pager.append(summary);
            scrollBox.after(pager);

            function renderRows() {
                var end = Math.min(visibleRows, dataRows.length);

                dataRows.hide().slice(0, end).show();
                summary.text('Showing 1-' + end + ' of ' + dataRows.length);
            }

            function revealMore() {
                if (visibleRows >= dataRows.length) {
                    return;
                }

                visibleRows = Math.min(visibleRows + rowsPerPage, dataRows.length);
                renderRows();
            }

            function ensureScrollableRows() {
                while (
                    visibleRows < dataRows.length
                    && scrollBox[0].scrollHeight <= scrollBox.outerHeight()
                ) {
                    visibleRows = Math.min(visibleRows + rowsPerPage, dataRows.length);
                    renderRows();
                }
            }

            scrollBox.off('scroll.dcrPagination').on('scroll.dcrPagination', function() {
                if (visibleRows >= dataRows.length) {
                    return;
                }

                var triggerPoint = scrollBox[0].scrollHeight - scrollBox.outerHeight() - scrollBuffer;

                if (scrollBox.scrollTop() >= triggerPoint) {
                    revealMore();
                }
            });

            renderRows();
            ensureScrollableRows();
        });
    }

    setupDcrTablePagination();
    
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
