@php
    $userId = $user->id;
    $children = $employeesByReportingTo[$userId] ?? collect();
    $detail = $user->employeeDetail;
    $designation = $detail && $detail->designation ? $detail->designation->name : '';
    $reportingToName = $detail && $detail->reportingTo ? $detail->reportingTo->name : '';
@endphp
<div class="mb-2" style="margin-left: {{ $level * 20 }}px;">
    <span class="font-weight-bold">{{ $user->name }}</span>
    @if($designation)
        <span class="text-muted small">({{ $designation }})</span>
    @endif
    @if($reportingToName)
        <span class="small text-info">→ reports to {{ $reportingToName }}</span>
    @endif
</div>
@if($children->isNotEmpty())
    <div class="pl-2 border-left">
        @foreach($children as $child)
            @include('hierarchy-minimap.partials.reporting-tree', ['user' => $child, 'employeesByReportingTo' => $employeesByReportingTo, 'level' => $level + 1])
        @endforeach
    </div>
@endif
