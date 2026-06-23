<div class="bg-white rounded b-shadow-4 create-inv">
    <div class="px-lg-4 px-md-4 px-3 py-3">
        <h4 class="mb-0 f-21 font-weight-normal">@lang('app.edit') Target Plan</h4>
    </div>
    <hr class="m-0 border-top-grey">

    <x-form class="c-inv-form" id="updateSalesPlanForm" method="PUT" action="{{ route('target-plan.update', $target->id) }}">
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-md-3">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Period Month <span class="text-danger">*</span></label>
                    <select name="period_month" id="period_month" class="form-control" required>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $target->period_month == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Period Year <span class="text-danger">*</span></label>
                    <select name="period_year" id="period_year" class="form-control" required>
                        @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                            <option value="{{ $y }}" {{ $target->period_year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <input type="hidden" name="plan_level" value="headquarter">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Headquarter <span class="text-danger">*</span></label>
                    <select name="headquarter_id" id="headquarter_id" class="form-control select-picker" data-live-search="true" required>
                        <option value="">Select HQ</option>
                        @foreach($headquarters as $h)
                            <option value="{{ $h->id }}" {{ $target->headquarter_id == $h->id ? 'selected' : '' }}>{{ $h->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Product <span class="text-danger">*</span></label>
                    <select name="product_id" id="product_id" class="form-control select-picker" data-live-search="true" required>
                        <option value="">Select Product</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ $target->product_id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Target Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="target_amount" id="target_amount" class="form-control" required value="{{ $target->target_amount }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Target Qty <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="target_qty" id="target_qty" class="form-control" required value="{{ $target->target_qty ?? 0 }}">
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Notes</label>
                    <textarea name="notes" id="notes" class="form-control" rows="2">{{ $target->notes }}</textarea>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end px-lg-4 px-md-4 px-3 py-3 border-top-grey">
            <x-forms.button-cancel :link="route('target-plan.index')" class="mr-3">@lang('app.cancel')</x-forms.button-cancel>
            <x-forms.button-primary id="update-sales-plan-form" icon="check">@lang('app.update')</x-forms.button-primary>
        </div>
    </x-form>
</div>

@push('scripts')
<script>
$(function() {
    $('#update-sales-plan-form').on('click', function() {
        var $form = $('#updateSalesPlanForm');
        if ($form[0].checkValidity && !$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }
        $.easyAjax({
            url: $form.attr('action'),
            container: '#updateSalesPlanForm',
            type: 'POST',
            disableButton: true,
            buttonSelector: '#update-sales-plan-form',
            data: $form.serialize(),
            success: function(response) {
                if (response.status === 'success' && response.action === 'redirect' && response.url) {
                    window.location.href = response.url;
                }
            }
        });
    });
});
</script>
@endpush
