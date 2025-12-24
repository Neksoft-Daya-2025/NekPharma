@extends('layouts.app')

@section('content')
<div class="content-wrapper">
    <div class="d-block d-lg-flex d-md-flex justify-content-between action-bar">
        <div>
            <h4 class="mb-0 f-21 text-capitalize">@lang('app.edit') Tour Plan</h4>
        </div>
        <div>
            <x-forms.button-cancel :link="route('tours.index')" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
        </div>
    </div>

    <x-form id="save-tour-form" method="PUT">
        <div class="bg-white rounded mt-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <x-forms.datepicker fieldId="date" fieldName="date" 
                            :fieldLabel="__('app.date')" 
                            :fieldValue="$tour->date->format('Y-m-d')" 
                            fieldRequired="true" />
                    </div>

                    <div class="col-md-6">
                        <x-forms.select fieldId="headquarter_id" :fieldLabel="__('modules.pharma.headquarter')" 
                            fieldName="headquarter_id" fieldRequired="true">
                            <option value="">--</option>
                            @foreach($headquarters as $hq)
                                <option value="{{ $hq->id }}" {{ $tour->headquarter_id == $hq->id ? 'selected' : '' }}>
                                    {{ $hq->name }}
                                </option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    <div class="col-md-6">
                        <x-forms.select fieldId="work_status" :fieldLabel="__('Work Status')" 
                            fieldName="work_status">
                            <option value="">--</option>
                            @foreach($workStatuses as $status)
                                <option value="{{ $status->name }}" {{ $tour->work_status == $status->name ? 'selected' : '' }}>
                                    {{ $status->name }}
                                </option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    <div class="col-md-6">
                        <x-forms.label class="my-3" fieldId="station" 
                            :fieldLabel="__('Station')" fieldRequired="false">
                        </x-forms.label>
                        <select class="form-control select-picker" name="station[]" 
                            id="station" data-live-search="true" multiple>
                            @php
                                $selectedStations = explode(',', $tour->station);
                            @endphp
                            @foreach($headquarters as $hq)
                                @foreach($hq->exstations as $station)
                                    <option value="{{ $station->name }}" 
                                        {{ in_array($station->name, $selectedStations) ? 'selected' : '' }}>
                                        {{ $station->name }} (Ex-Station)
                                    </option>
                                @endforeach
                                @foreach($hq->outstations as $station)
                                    <option value="{{ $station->name }}" 
                                        {{ in_array($station->name, $selectedStations) ? 'selected' : '' }}>
                                        {{ $station->name }} (Out-Station)
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <x-forms.label class="my-3" fieldId="work_with" 
                            :fieldLabel="__('Work With')" fieldRequired="false">
                        </x-forms.label>
                        <select class="form-control select-picker" name="work_with[]" 
                            id="work_with" data-live-search="true" multiple>
                            @php
                                $selectedEmployees = explode(',', $tour->work_with);
                            @endphp
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" 
                                    {{ in_array($emp->id, $selectedEmployees) ? 'selected' : '' }}>
                                    {{ $emp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <x-forms.textarea class="mr-0 mr-lg-2 mr-md-2" 
                            :fieldLabel="__('app.remark')" 
                            fieldName="remark" 
                            fieldId="remark" 
                            :fieldPlaceholder="__('placeholders.remark')" 
                            :fieldValue="$tour->remark">
                        </x-forms.textarea>
                    </div>
                </div>
            </div>

            <x-form-actions>
                <x-forms.button-primary id="save-tour" class="mr-3" icon="check">@lang('app.save')
                </x-forms.button-primary>
                <x-forms.button-cancel :link="route('tours.index')" class="border-0">@lang('app.cancel')
                </x-forms.button-cancel>
            </x-form-actions>
        </div>
    </x-form>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.select-picker').selectpicker();

        $('#save-tour-form').on('submit', function(e) {
            e.preventDefault();
            $.easyAjax({
                url: "{{ route('tours.update', $tour->id) }}",
                container: '#save-tour-form',
                type: "POST",
                data: $('#save-tour-form').serialize(),
                success: function(response) {
                    if (response.status == "success") {
                        window.location.href = response.redirectUrl;
                    }
                }
            })
        });
    });
</script>
@endpush

