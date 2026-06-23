<div class="bg-white rounded b-shadow-4 create-inv">
    <div class="px-lg-4 px-md-4 px-3 py-3">
        <h4 class="mb-0 f-21 font-weight-normal">@lang('app.add') Target Plan</h4>
    </div>
    <hr class="m-0 border-top-grey">

    <x-form class="c-inv-form" id="saveSalesPlanForm" method="POST" action="{{ route('target-plan.store') }}">
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
            <input type="hidden" name="plan_level" value="headquarter">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Headquarter <span class="text-danger">*</span></label>
                    <select name="headquarter_id" id="headquarter_id" class="form-control select-picker" data-live-search="true" required>
                        <option value="">Select HQ</option>
                        @foreach($headquarters as $h)
                            <option value="{{ $h->id }}">{{ $h->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Products <span class="text-danger">*</span></label>
                    <select id="product_ids" class="form-control select-picker" data-live-search="true" multiple
                            data-actions-box="true" data-selected-text-format="count > 2" title="Select Products">
                        @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Select one or more products to enter product-wise monthly targets.</small>
                </div>
            </div>
            <div class="col-md-12">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                    <label class="f-14 text-dark-grey mb-0">Product Targets <span class="text-danger">*</span></label>
                    <div>
                        <a href="{{ route('target-plan.import.sample') }}" class="btn btn-sm btn-secondary mr-2 mb-2 mb-md-0">
                            <i class="fa fa-download"></i> Sample CSV
                        </a>
                        <label class="btn btn-sm btn-secondary mb-2 mb-md-0 mb-0" for="sales-plan-import-file">
                            <i class="fa fa-file-upload"></i> Import CSV
                        </label>
                        <input type="file" id="sales-plan-import-file" accept=".csv,text/csv" class="d-none">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered mb-2" id="product-targets-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-right" style="width: 180px;">Target Qty</th>
                                <th class="text-right" style="width: 180px;">Target Amount</th>
                                <th style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="empty-product-target-row">
                                <td colspan="4" class="text-center text-muted">Select products to generate target rows.</td>
                            </tr>
                        </tbody>
                    </table>
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
            <x-forms.button-cancel :link="route('target-plan.index')" class="mr-3">@lang('app.cancel')</x-forms.button-cancel>
            <x-forms.button-primary id="save-sales-plan-form" icon="check">@lang('app.save')</x-forms.button-primary>
        </div>
    </x-form>
</div>

@push('scripts')
<script>
$(function() {
    var $productSelect = $('#product_ids');
    var $targetTableBody = $('#product-targets-table tbody');
    var importTargetsUrl = "{{ route('target-plan.import.targets') }}";
    var importCsrf = "{{ csrf_token() }}";
    var importedTargetValues = {};

    function productName(productId) {
        return $productSelect.find('option[value="' + productId + '"]').text();
    }

    function syncProductTargetRows() {
        var selectedProductIds = $productSelect.val() || [];
        var existingValues = {};

        $targetTableBody.find('tr.product-target-row').each(function() {
            var productId = String($(this).data('product-id'));
            existingValues[productId] = {
                qty: $(this).find('.target-qty-input').val(),
                amount: $(this).find('.target-amount-input').val()
            };
        });

        $targetTableBody.empty();

        if (selectedProductIds.length === 0) {
            $targetTableBody.append('<tr class="empty-product-target-row"><td colspan="4" class="text-center text-muted">Select products to generate target rows.</td></tr>');
            return;
        }

        selectedProductIds.forEach(function(productId, index) {
            var values = existingValues[productId] || importedTargetValues[productId] || { qty: '0', amount: '0' };
            var row = [
                '<tr class="product-target-row" data-product-id="' + productId + '">',
                '<td>',
                $('<div>').text(productName(productId)).html(),
                '<input type="hidden" name="targets[' + index + '][product_id]" value="' + productId + '">',
                '</td>',
                '<td><input type="number" step="0.01" min="0" name="targets[' + index + '][target_qty]" class="form-control form-control-sm text-right target-qty-input" required value="' + values.qty + '"></td>',
                '<td><input type="number" step="0.01" min="0" name="targets[' + index + '][target_amount]" class="form-control form-control-sm text-right target-amount-input" required value="' + values.amount + '"></td>',
                '<td><button type="button" class="btn btn-sm btn-danger remove-product-target" data-product-id="' + productId + '"><i class="fa fa-trash"></i></button></td>',
                '</tr>'
            ].join('');
            $targetTableBody.append(row);
        });
    }

    $productSelect.on('changed.bs.select change', syncProductTargetRows);

    $targetTableBody.on('click', '.remove-product-target', function() {
        var productId = String($(this).data('product-id'));
        var selectedProductIds = ($productSelect.val() || []).filter(function(id) {
            return String(id) !== productId;
        });
        $productSelect.selectpicker('val', selectedProductIds);
        syncProductTargetRows();
    });

    $('#sales-plan-import-file').on('change', function() {
        var fileInput = this;
        var file = fileInput.files && fileInput.files[0];
        if (!file) {
            return;
        }

        var periodMonth = $('#period_month').val();
        var periodYear = $('#period_year').val();
        var headquarterId = $('#headquarter_id').val();

        if (!periodMonth || !periodYear || !headquarterId) {
            alert('Please select Period Month, Period Year, and Headquarter before importing CSV.');
            fileInput.value = '';
            return;
        }

        var formData = new FormData();
        formData.append('_token', importCsrf);
        formData.append('import_file', file);
        formData.append('period_month', periodMonth);
        formData.append('period_year', periodYear);
        formData.append('headquarter_id', headquarterId);

        $.ajax({
            url: importTargetsUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.status !== 'success' || !res.lines || !res.lines.length) {
                    alert(res.message || 'No valid rows were imported.');
                    return;
                }

                var productIds = [];
                importedTargetValues = {};
                res.lines.forEach(function(line) {
                    var productId = String(line.product_id);
                    productIds.push(productId);
                    importedTargetValues[productId] = {
                        qty: parseFloat(line.target_qty || 0),
                        amount: parseFloat(line.target_amount || 0)
                    };
                });

                $productSelect.selectpicker('val', productIds);
                syncProductTargetRows();

                var skippedCount = (res.skipped || []).length;
                var message = res.imported + ' target row(s) imported from CSV.';
                if (skippedCount > 0) {
                    message += ' ' + skippedCount + ' row(s) skipped.';
                }
                alert(message);
            },
            error: function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'CSV import failed.';
                alert(message);
            },
            complete: function() {
                fileInput.value = '';
            }
        });
    });

    $('#save-sales-plan-form').on('click', function() {
        var $form = $('#saveSalesPlanForm');
        syncProductTargetRows();

        if ($targetTableBody.find('tr.product-target-row').length === 0) {
            alert('Please select at least one product and enter target values.');
            return;
        }

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
                    window.location.href = "{{ route('target-plan.index') }}";
                }
            }
        });
    });
});
</script>
@endpush
