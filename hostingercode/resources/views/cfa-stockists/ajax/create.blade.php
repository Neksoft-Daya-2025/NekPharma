<div class="bg-white rounded b-shadow-4 create-inv">
    <div class="px-lg-4 px-md-4 px-3 py-3">
        <h4 class="mb-0 f-21 font-weight-normal">@lang('app.addCFAStockist')</h4>
    </div>
    <hr class="m-0 border-top-grey">
    
    <x-form class="c-inv-form" id="saveCFAStockistForm" method="POST" action="{{ route('cfa-stockists.store') }}">
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12" for="shopname">@lang('app.shopname') <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="shopname" id="shopname" required>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12" for="email">@lang('app.email')</label>
                    <input type="email" class="form-control" name="email" id="email">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12" for="owner_name">@lang('app.ownerName')</label>
                    <input type="text" class="form-control" name="owner_name" id="owner_name">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12" for="owner_mobile">@lang('app.ownerMobile')</label>
                    <input type="text" class="form-control" name="owner_mobile" id="owner_mobile">
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12" for="address">@lang('app.address')</label>
                    <textarea class="form-control" name="address" id="address" rows="3"></textarea>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12" for="gst_number">@lang('app.gstNumber')</label>
                    <input type="text" class="form-control" name="gst_number" id="gst_number">
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12" for="dl_number">@lang('app.dlNumber')</label>
                    <input type="text" class="form-control" name="dl_number" id="dl_number">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12" for="area_id">@lang('app.area')</label>
                    <select class="form-control select-picker" name="area_id" id="area_id" data-live-search="true">
                        <option value="">-- @lang('app.select') @lang('app.area') --</option>
                        @if(isset($areas) && $areas->count() > 0)
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12" for="headquarter_id">@lang('app.headquarter')</label>
                    <select class="form-control select-picker" name="headquarter_id" id="headquarter_id" data-live-search="true">
                        <option value="">-- @lang('app.select') @lang('app.headquarter') --</option>
                        @if(isset($headquarters) && $headquarters->count() > 0)
                            @foreach($headquarters as $hq)
                                <option value="{{ $hq->id }}">{{ $hq->name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12" for="cfa_distributor_ids">@lang('app.mapToCFADistributors')</label>
                    <select class="form-control select-picker" name="cfa_distributor_ids[]" id="cfa_distributor_ids" 
                            data-live-search="true" data-size="8" multiple>
                        @if(isset($cfaDistributors) && $cfaDistributors->count() > 0)
                            @foreach($cfaDistributors as $cfaDistributor)
                                <option value="{{ $cfaDistributor->id }}">
                                    {{ $cfaDistributor->company_name ?? $cfaDistributor->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                    <small class="form-text text-muted">Select one or more CFA/Distributors to map this stockist to.</small>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end px-lg-4 px-md-4 px-3 py-3 border-top-grey">
            <x-forms.button-cancel :link="route('cfa-stockists.index')" class="mr-3">@lang('app.cancel')</x-forms.button-cancel>
            <x-forms.button-primary id="save-form" icon="check">@lang('app.save')</x-forms.button-primary>
        </div>
    </x-form>
</div>

<script>
    $(document).ready(function() {
        $('.select-picker').selectpicker();
        
        // Handle form submission
        $('#save-form').click(function() {
            const url = "{{ route('cfa-stockists.store') }}";
            
            $.easyAjax({
                url: url,
                container: '#saveCFAStockistForm',
                type: "POST",
                disableButton: true,
                buttonSelector: "#save-form",
                data: $('#saveCFAStockistForm').serialize(),
                success: function(response) {
                    if (response.status == 'success') {
                        if (response.redirectUrl) {
                            window.location.href = response.redirectUrl;
                        } else {
                            window.location.reload();
                        }
                    }
                }
            });
        });
    });
</script>

