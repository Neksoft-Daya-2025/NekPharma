<div class="bg-white rounded b-shadow-4 create-inv">
    <div class="px-lg-4 px-md-4 px-3 py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0 f-21 font-weight-normal">@lang('app.cfaStockistDetails')</h4>
            <div>
                @if(user()->permission('edit_stockists') == 'all' || user()->permission('edit_stockists') == 'added')
                    <a href="{{ route('cfa-stockists.edit', $cfaStockist->id) }}" class="btn btn-sm btn-primary">
                        <i class="fa fa-edit"></i> @lang('app.edit')
                    </a>
                @endif
            </div>
        </div>
    </div>
    <hr class="m-0 border-top-grey">
    
    <div class="row px-lg-4 px-md-4 px-3 py-3">
        <div class="col-md-6">
            <div class="form-group">
                <label class="f-14 text-dark-grey mb-12">@lang('app.cfaStockistId')</label>
                <p class="f-14 text-dark"><span class="badge badge-info">{{ $cfaStockist->cfa_stockist_id ?? '-' }}</span></p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label class="f-14 text-dark-grey mb-12">@lang('app.shopname')</label>
                <p class="f-14 text-dark">{{ $cfaStockist->shopname }}</p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label class="f-14 text-dark-grey mb-12">@lang('app.email')</label>
                <p class="f-14 text-dark">{{ $cfaStockist->email ?? '-' }}</p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label class="f-14 text-dark-grey mb-12">@lang('app.ownerName')</label>
                <p class="f-14 text-dark">{{ $cfaStockist->owner_name ?? '-' }}</p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label class="f-14 text-dark-grey mb-12">@lang('app.ownerMobile')</label>
                <p class="f-14 text-dark">{{ $cfaStockist->owner_mobile ?? '-' }}</p>
            </div>
        </div>

        @if($cfaStockist->address)
        <div class="col-md-12">
            <div class="form-group">
                <label class="f-14 text-dark-grey mb-12">@lang('app.address')</label>
                <p class="f-14 text-dark">{{ $cfaStockist->address }}</p>
            </div>
        </div>
        @endif

        @if($cfaStockist->gst_number || $cfaStockist->dl_number)
        <div class="col-md-6">
            <div class="form-group">
                <label class="f-14 text-dark-grey mb-12">@lang('app.gstNumber')</label>
                <p class="f-14 text-dark">{{ $cfaStockist->gst_number ?? '-' }}</p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                <label class="f-14 text-dark-grey mb-12">@lang('app.dlNumber')</label>
                <p class="f-14 text-dark">{{ $cfaStockist->dl_number ?? '-' }}</p>
            </div>
        </div>
        @endif

        <div class="col-md-12">
            <hr class="my-4">
            <h5 class="mb-3">@lang('app.mappedCFADistributors')</h5>
            @if($cfaStockist->cfaDistributors->isEmpty())
                <p class="text-muted">@lang('app.noCFADistributorsMapped')</p>
            @else
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>@lang('app.name')</th>
                                <th>@lang('app.email')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cfaStockist->cfaDistributors as $cfaDistributor)
                                <tr>
                                    <td>{{ $cfaDistributor->clientDetails->company_name ?? $cfaDistributor->name }}</td>
                                    <td>{{ $cfaDistributor->email ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="d-flex justify-content-end px-lg-4 px-md-4 px-3 py-3 border-top-grey">
        <x-forms.button-cancel :link="route('cfa-stockists.index')" class="mr-3">@lang('app.back')</x-forms.button-cancel>
    </div>
</div>

