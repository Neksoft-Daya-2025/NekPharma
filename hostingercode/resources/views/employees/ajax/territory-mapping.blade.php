@php
    $m = $territoryMapping ?? [];
    $chain = $m['reportingChainTopDown'] ?? [];
@endphp

<style>
    .tm-flow { max-width: 720px; margin: 0 auto; }
    .tm-node {
        background: #f8f9fb;
        border: 1px solid #e7e9ec;
        border-radius: 8px;
        padding: 12px 16px;
        text-align: center;
        color: #28313c;
    }
    .tm-node.emp { background: #e8f4fd; border-color: #b8daff; }
    .tm-node.rm { background: #fff; }
    .tm-arrow { text-align: center; font-size: 20px; color: #99a5b5; padding: 4px 0; line-height: 1.2; }
    .tm-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #99a5b5;
        margin-bottom: 8px;
        font-weight: 600;
    }
    .tm-chip-row { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; }
    .tm-chip {
        display: inline-block;
        padding: 6px 12px;
        background: #fff;
        border: 1px solid #e7e9ec;
        border-radius: 20px;
        font-size: 13px;
        color: #28313c;
    }
    .tm-muted { color: #99a5b5; font-size: 13px; }
    .tm-sub { font-size: 12px; color: #99a5b5; margin-top: 4px; }
</style>

<div class="row">
    <div class="col-12">
        <x-cards.data :title="__('modules.employees.territoryMappingTitle')" class="mb-4">
            <p class="f-13 text-lightest mb-4">{{ __('modules.employees.territoryMappingHelp') }}</p>

            <div class="tm-flow">
                <div class="tm-label">{{ __('modules.employees.territoryMappingReportingChain') }}</div>

                @forelse ($chain as $rm)
                    <div class="tm-node rm">
                        <div class="f-14 f-w-500">{{ $rm['name'] }}</div>
                        @if (!empty($rm['designation']))
                            <div class="tm-sub">{{ $rm['designation'] }}</div>
                        @endif
                    </div>
                    <div class="tm-arrow">↓</div>
                @empty
                @endforelse

                <div class="tm-node emp">
                    <div class="f-14 f-w-500">{{ $m['employeeName'] ?? '—' }}</div>
                    @if (!empty($m['designation']))
                        <div class="tm-sub">{{ $m['designation'] }}</div>
                    @endif
                </div>

                <div class="mt-5"></div>

                <div class="tm-label">{{ __('modules.employees.territoryMappingAssignedGeography') }}</div>

                <div class="mt-2 mb-3">
                    <span class="text-lightest f-12">{{ __('modules.employees.territoryFlowZones') }}:</span>
                    @if (!empty($m['zones']))
                        <div class="tm-chip-row mt-2">
                            @foreach ($m['zones'] as $z)
                                <span class="tm-chip">{{ $z }}</span>
                            @endforeach
                        </div>
                    @else
                        <p class="tm-muted mb-0 mt-1">{{ __('modules.employees.territoryMappingNone') }}</p>
                    @endif
                </div>

                <div class="mb-3">
                    <span class="text-lightest f-12">{{ __('modules.employees.territoryFlowRegions') }}:</span>
                    @if (!empty($m['regions']))
                        <div class="tm-chip-row mt-2">
                            @foreach ($m['regions'] as $r)
                                <span class="tm-chip">{{ $r }}</span>
                            @endforeach
                        </div>
                    @else
                        <p class="tm-muted mb-0 mt-1">{{ __('modules.employees.territoryMappingNone') }}</p>
                    @endif
                </div>

                <div class="mb-3">
                    <span class="text-lightest f-12">{{ __('modules.employees.territoryFlowAreas') }}:</span>
                    @if (!empty($m['areas']))
                        <div class="tm-chip-row mt-2">
                            @foreach ($m['areas'] as $a)
                                <span class="tm-chip" title="{{ $a['region'] }} / {{ $a['zone'] }}">{{ $a['name'] }}</span>
                            @endforeach
                        </div>
                    @else
                        <p class="tm-muted mb-0 mt-1">{{ __('modules.employees.territoryMappingNone') }}</p>
                    @endif
                </div>

                <div class="mb-0">
                    <span class="text-lightest f-12">{{ __('modules.employees.territoryMappingHeadquarter') }}:</span>
                    @if (!empty($m['hqHierarchy']))
                        <div class="mt-3">
                            <div class="tm-node">{{ $m['hqHierarchy']['zone'] }}</div>
                            <div class="tm-arrow">↓</div>
                            <div class="tm-node">{{ $m['hqHierarchy']['region'] }}</div>
                            <div class="tm-arrow">↓</div>
                            <div class="tm-node">{{ $m['hqHierarchy']['area'] }}</div>
                            <div class="tm-arrow">↓</div>
                            <div class="tm-node emp">{{ $m['hqHierarchy']['hq'] }}</div>
                        </div>
                    @elseif (!empty($m['headquarterLabel']))
                        <p class="f-14 mb-0 mt-2">{{ $m['headquarterLabel'] }}</p>
                    @else
                        <p class="tm-muted mb-0 mt-1">{{ __('modules.employees.territoryMappingNone') }}</p>
                    @endif
                </div>
            </div>
        </x-cards.data>
    </div>
</div>
