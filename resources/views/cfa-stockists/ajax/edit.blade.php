<div class="bg-white rounded b-shadow-4 create-inv">
    <div class="px-lg-4 px-md-4 px-3 py-3">
        <h4 class="mb-0 f-21 font-weight-normal">@lang('app.editCFAStockist')</h4>
    </div>
    <hr class="m-0 border-top-grey">
    
    <x-form class="c-inv-form" id="updateCFAStockistForm" method="PUT" action="{{ route('cfa-stockists.update', $cfaStockist->id) }}">
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">@lang('app.cfaStockistId')</label>
                    <p class="f-14 text-dark"><span class="badge badge-info">{{ $cfaStockist->cfa_stockist_id ?? '-' }}</span></p>
                    <small class="form-text text-muted">This ID is auto-generated and cannot be changed.</small>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12" for="shopname">@lang('app.shopname') <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="shopname" id="shopname" value="{{ $cfaStockist->shopname }}" required>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12" for="email">@lang('app.email')</label>
                    <input type="email" class="form-control" name="email" id="email" value="{{ $cfaStockist->email }}">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12" for="owner_name">@lang('app.ownerName')</label>
                    <input type="text" class="form-control" name="owner_name" id="owner_name" value="{{ $cfaStockist->owner_name }}">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12" for="owner_mobile">@lang('app.ownerMobile')</label>
                    <input type="text" class="form-control" name="owner_mobile" id="owner_mobile" value="{{ $cfaStockist->owner_mobile }}">
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12" for="address">@lang('app.address')</label>
                    <textarea class="form-control" name="address" id="address" rows="3">{{ $cfaStockist->address }}</textarea>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12" for="gst_number">@lang('app.gstNumber')</label>
                    <input type="text" class="form-control" name="gst_number" id="gst_number" value="{{ $cfaStockist->gst_number }}">
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12" for="dl_number">@lang('app.dlNumber')</label>
                    <input type="text" class="form-control" name="dl_number" id="dl_number" value="{{ $cfaStockist->dl_number }}">
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12" for="cfa_distributor_ids">@lang('app.mapToCFADistributors')</label>
                    <select class="form-control select-picker" name="cfa_distributor_ids[]" id="cfa_distributor_ids" 
                            data-live-search="true" data-size="8" multiple>
                        @foreach($cfaDistributors as $cfaDistributor)
                            <option value="{{ $cfaDistributor->id }}" 
                                {{ $cfaStockist->cfaDistributors->contains($cfaDistributor->id) ? 'selected' : '' }}>
                                {{ $cfaDistributor->company_name ?? $cfaDistributor->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">Select one or more CFA/Distributors to map this stockist to.</small>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end px-lg-4 px-md-4 px-3 py-3 border-top-grey">
            <x-forms.button-cancel :link="route('cfa-stockists.index')" class="mr-3">@lang('app.cancel')</x-forms.button-cancel>
            <x-forms.button-primary id="update-form" icon="check">@lang('app.update')</x-forms.button-primary>
        </div>
    </x-form>
</div>

<script>
    $(document).ready(function() {
        $('.select-picker').selectpicker();
    });
</script>

