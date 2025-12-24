<div class="modal-header">
    <h5 class="modal-title" id="modelHeading"><i class="fa fa-edit"></i> @lang('app.edit') Tour Plan</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
            aria-hidden="true">×</span></button>
</div>
<x-form id="edit-tour-form" method="PUT">
    <div class="modal-body">
        <div class="portlet-body">
            <div class="row">
                <div class="col-md-12">
                    <x-forms.datepicker fieldId="date" fieldName="date" 
                        :fieldLabel="__('app.date')" 
                        :fieldValue="$tour->date->format('Y-m-d')" 
                        fieldRequired="true" />
                </div>

                <div class="col-md-12">
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

                <div class="col-md-12">
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

                <div class="col-md-12">
                    <x-forms.label class="my-3" fieldId="station" 
                        :fieldLabel="__('Station')" fieldRequired="false">
                    </x-forms.label>
                    <select class="form-control select-picker" name="station" 
                        id="station" data-live-search="true">
                        <option value="">-- Select Station --</option>
                        @php
                            $selectedStation = $tour->station;
                            $tourHQ = $tour->headquarter;
                        @endphp
                        @if($tourHQ)
                            {{-- Add Headquarters as first option --}}
                            <option value="{{ $tourHQ->name }}" 
                                {{ $selectedStation == $tourHQ->name ? 'selected' : '' }}>
                                {{ $tourHQ->name }} (Headquarter)
                            </option>
                            {{-- Add Ex-Stations --}}
                            @foreach($tourHQ->exstations as $station)
                                <option value="{{ $station->name }}" 
                                    {{ $selectedStation == $station->name ? 'selected' : '' }}>
                                    {{ $station->name }} (Ex-Station)
                                </option>
                            @endforeach
                            {{-- Add Out-Stations --}}
                            @foreach($tourHQ->outstations as $station)
                                <option value="{{ $station->name }}" 
                                    {{ $selectedStation == $station->name ? 'selected' : '' }}>
                                    {{ $station->name }} (Out-Station)
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <div class="col-md-12">
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

                <div class="col-md-12">
                    <x-forms.textarea class="mr-0" 
                        :fieldLabel="__('app.remark')" 
                        fieldName="remark" 
                        fieldId="remark" 
                        :fieldPlaceholder="__('placeholders.remark')" 
                        :fieldValue="$tour->remark">
                    </x-forms.textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
        <x-forms.button-primary id="save-tour-edit" icon="check">@lang('app.save')</x-forms.button-primary>
    </div>
</x-form>

<script>
    $(document).ready(function() {
        $('.select-picker').selectpicker();

        // Populate stations when HQ changes
        $('#headquarter_id').on('changed.bs.select', function() {
            const hqId = $(this).val();
            const $stationSelect = $('#station');
            
            // Clear existing options except the first one
            $stationSelect.find('option:not(:first)').remove();
            
            if (hqId) {
                // Fetch stations for this HQ via AJAX or use existing data
                const headquarters = @json($headquarters);
                const hq = headquarters.find(h => h.id == hqId);
                
                if (hq) {
                    // Add Headquarters as first option
                    $stationSelect.append(`<option value="${hq.name}">${hq.name} (Headquarter)</option>`);
                    
                    // Add exstations
                    if (hq.exstations && hq.exstations.length > 0) {
                        hq.exstations.forEach(station => {
                            $stationSelect.append(`<option value="${station.name}">${station.name} (Ex-Station)</option>`);
                        });
                    }
                    
                    // Add outstations
                    if (hq.outstations && hq.outstations.length > 0) {
                        hq.outstations.forEach(station => {
                            $stationSelect.append(`<option value="${station.name}">${station.name} (Out-Station)</option>`);
                        });
                    }
                    
                    $stationSelect.selectpicker('refresh');
                }
            } else {
                $stationSelect.selectpicker('refresh');
            }
        });

        $('#save-tour-edit').click(function(e) {
            e.preventDefault();
            $.easyAjax({
                url: "{{ route('tours.update', $tour->id) }}",
                container: '#edit-tour-form',
                type: "POST",
                data: $('#edit-tour-form').serialize(),
                success: function(response) {
                    if (response.status == "success") {
                        window.location.reload();
                    }
                }
            })
        });
    });
</script>

