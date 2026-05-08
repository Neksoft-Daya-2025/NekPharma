<div class="bg-white rounded b-shadow-4 create-inv">
    <div class="px-lg-4 px-md-4 px-3 py-3">
        <h4 class="mb-0 f-21 font-weight-normal">@lang('app.add') @lang('app.salesPlan')</h4>
    </div>
    <hr class="m-0 border-top-grey">

    <x-form class="c-inv-form" id="saveSalesPlanForm" method="POST" action="{{ route('sales-plan.store') }}">
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-md-3">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Period Month <span class="text-danger">*</span></label>
                    <select name="period_month" id="period_month" class="form-control" required>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Period Year <span class="text-danger">*</span></label>
                    <select name="period_year" id="period_year" class="form-control" required>
                        @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                            <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Plan Level <span class="text-danger">*</span></label>
                    <select name="plan_level" id="plan_level" class="form-control" required>
                        <option value="headquarter">HQ-wise</option>
                        <option value="area">Area-wise</option>
                        <option value="region">Region-wise</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6 plan-scope-headquarter">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Headquarter <span class="text-danger">*</span></label>
                    <select name="headquarter_id" id="headquarter_id" class="form-control select-picker" data-live-search="true">
                        <option value="">Select HQ</option>
                        @foreach($headquarters as $h)
                            <option value="{{ $h->id }}">{{ $h->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6 plan-scope-area d-none">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Area <span class="text-danger">*</span></label>
                    <select name="area_id" id="area_id" class="form-control select-picker" data-live-search="true">
                        <option value="">Select Area</option>
                        @foreach($areas as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6 plan-scope-region d-none">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Region <span class="text-danger">*</span></label>
                    <select name="region_id" id="region_id" class="form-control select-picker" data-live-search="true">
                        <option value="">Select Region</option>
                        @foreach($regions as $r)
                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Target Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="target_amount" id="target_amount" class="form-control" required value="0">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Product (optional)</label>
                    <select name="product_id" id="product_id" class="form-control select-picker" data-live-search="true">
                        <option value="">All Products</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Notes</label>
                    <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end px-lg-4 px-md-4 px-3 py-3 border-top-grey">
            <x-forms.button-cancel :link="route('sales-plan.index')" class="mr-3">@lang('app.cancel')</x-forms.button-cancel>
            <x-forms.button-primary id="save-sales-plan-form" icon="check">@lang('app.save')</x-forms.button-primary>
        </div>
    </x-form>
</div>

@push('scripts')
<script>
$(function() {
    function togglePlanScope() {
        var level = $('#plan_level').val();
        $('.plan-scope-headquarter, .plan-scope-area, .plan-scope-region').addClass('d-none');
        $('#headquarter_id, #area_id, #region_id').removeAttr('required');
        if (level === 'headquarter') {
            $('.plan-scope-headquarter').removeClass('d-none');
            $('#headquarter_id').attr('required', 'required');
        } else if (level === 'area') {
            $('.plan-scope-area').removeClass('d-none');
            $('#area_id').attr('required', 'required');
        } else if (level === 'region') {
            $('.plan-scope-region').removeClass('d-none');
            $('#region_id').attr('required', 'required');
        }
    }
    $('#plan_level').on('change', togglePlanScope);
    togglePlanScope();

    $('#save-sales-plan-form').on('click', function() {
        var $form = $('#saveSalesPlanForm');
        if ($form[0].checkValidity && !$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }
        $.easyAjax({
            url: $form.attr('action'),
            container: '#saveSalesPlanForm',
            type: 'POST',
            disableButton: true,
            buttonSelector: '#save-sales-plan-form',
            data: $form.serialize(),
            success: function(response) {
                if (response.status === 'success' && (response.action === 'redirect' && response.url)) {
                    window.location.href = response.url;
                } else if (response.status === 'success') {
                    window.location.href = "{{ route('sales-plan.index') }}";
                }
            }
        });
    });
});
</script>
@endpush
