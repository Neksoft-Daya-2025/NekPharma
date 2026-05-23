<div class="bg-white rounded b-shadow-4 create-inv">
    <div class="px-lg-4 px-md-4 px-3 py-3">
        <h4 class="mb-0 f-21 font-weight-normal">@lang('app.edit') @lang('app.salesStockStatement')</h4>
    </div>
    <hr class="m-0 border-top-grey">

    <x-form class="c-inv-form" id="updateStockStatementForm" method="PUT" action="{{ route('stock-statements.update', $statement->id) }}">
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-md-6">
                <p class="mb-0"><strong>Period:</strong> {{ \Carbon\Carbon::create()->month($statement->period_month)->format('F') }} {{ $statement->period_year }}</p>
                <p class="mb-0"><strong>Stockist:</strong> {{ $statement->cfaStockist->shopname ?? '-' }} ({{ $statement->cfaStockist->cfa_stockist_id ?? '-' }})</p>
            </div>
        </div>

        <div class="px-lg-4 px-md-4 px-3 py-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label class="f-14 text-dark-grey mb-0">Statement Lines (Opening / Primary / Secondary / Closing)</label>
                <button type="button" class="btn btn-sm btn-primary" id="add-statement-line-edit"><i class="fa fa-plus"></i> Add line</button>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered" id="statement-lines-edit-table">
                    <thead>
                        <tr>
                            <th>Product <span class="text-danger">*</span></th>
                            <th class="text-right" style="width: 100px;">Opening</th>
                            <th class="text-right" style="width: 100px;">Primary</th>
                            <th class="text-right" style="width: 120px;">Secondary</th>
                            <th class="text-right" style="width: 140px;">Closing</th>
                            <th style="width: 60px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($statement->lines as $idx => $line)
                            <tr class="statement-line-row">
                                <td>
                                    <input type="hidden" name="lines[{{ $idx }}][product_id]" value="{{ $line->product_id }}">
                                    <span class="product-name">{{ $line->product->name ?? '-' }}</span>
                                </td>
                                <td class="text-right">
                                    <input type="number" step="0.01" min="0" name="lines[{{ $idx }}][opening_qty]" class="form-control form-control-sm opening-qty-input text-right" value="{{ $line->opening_qty }}">
                                </td>
                                <td class="text-right">
                                    <input type="number" step="0.01" min="0" name="lines[{{ $idx }}][primary_qty]" class="form-control form-control-sm primary-qty-input text-right" value="{{ $line->primary_qty }}">
                                </td>
                                <td class="text-right">
                                    <input type="number" step="0.01" min="0" name="lines[{{ $idx }}][secondary_qty]" class="form-control form-control-sm secondary-qty-input text-right" value="{{ $line->secondary_qty }}">
                                </td>
                                <td class="text-right closing-cell">
                                    <span class="closing-qty-display">{{ number_format($line->closing_qty, 2) }}</span>
                                    <label class="mb-0 ml-1 small"><input type="checkbox" class="closing-override-checkbox"> Override</label>
                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm closing-qty-input text-right d-none" placeholder="0" style="max-width: 90px;">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-danger btn-remove-line" title="Remove line"><i class="fa fa-trash"></i></button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-muted small mb-0">Closing is computed as Opening + Primary - Secondary. Tick "Override" to enter Closing manually.</p>
        </div>

        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Save as</label>
                    <select name="status" id="status" class="form-control">
                        <option value="draft" {{ $statement->status === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="submitted" {{ $statement->status === 'submitted' ? 'selected' : '' }}>Submit</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end px-lg-4 px-md-4 px-3 py-3 border-top-grey">
            <x-forms.button-cancel :link="route('stock-statements.show', $statement->id)" class="mr-3">@lang('app.cancel')</x-forms.button-cancel>
            <x-forms.button-primary id="update-statement-form" icon="check">@lang('app.update')</x-forms.button-primary>
        </div>
    </x-form>
</div>

{{-- Template row for "Add line" (hidden) --}}
<template id="statement-line-row-template-edit">
    <tr class="statement-line-row new-line-row">
        <td>
            <select class="form-control form-control-sm product-select" required>
                <option value="">Select product</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
        </td>
        <td class="text-right">
            <input type="number" step="0.01" min="0" class="form-control form-control-sm opening-qty-input text-right" value="0" placeholder="0">
        </td>
        <td class="text-right">
            <input type="number" step="0.01" min="0" class="form-control form-control-sm primary-qty-input text-right" value="0" placeholder="0">
        </td>
        <td class="text-right">
            <input type="number" step="0.01" min="0" class="form-control form-control-sm secondary-qty-input text-right" value="0" placeholder="0">
        </td>
        <td class="text-right closing-cell">
            <span class="closing-qty-display">0.00</span>
            <label class="mb-0 ml-1 small"><input type="checkbox" class="closing-override-checkbox"> Override</label>
            <input type="number" step="0.01" min="0" class="form-control form-control-sm closing-qty-input text-right d-none" placeholder="0" style="max-width: 90px;">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-danger btn-remove-line" title="Remove line"><i class="fa fa-trash"></i></button>
        </td>
    </tr>
</template>

@push('scripts')
<script>
$(function() {
    var $tbody = $('#statement-lines-edit-table tbody');
    var $template = $('#statement-line-row-template-edit').html();

    function reindexRows() {
        $tbody.find('tr.statement-line-row').each(function(idx) {
            var $tr = $(this);
            if ($tr.hasClass('new-line-row')) {
                $tr.find('select.product-select').attr('name', 'lines[' + idx + '][product_id]');
            } else {
                $tr.find('input[type="hidden"]').attr('name', 'lines[' + idx + '][product_id]');
            }
            $tr.find('.opening-qty-input').attr('name', 'lines[' + idx + '][opening_qty]');
            $tr.find('.primary-qty-input').attr('name', 'lines[' + idx + '][primary_qty]');
            $tr.find('.secondary-qty-input').attr('name', 'lines[' + idx + '][secondary_qty]');
            var closingName = $tr.find('.closing-override-checkbox').is(':checked') ? ('lines[' + idx + '][closing_qty]') : '';
            $tr.find('.closing-qty-input').attr('name', closingName);
        });
    }

    function getNumericVal($el) {
        var v = $el.val();
        return v === '' ? 0 : parseFloat(v) || 0;
    }

    function updateClosingDisplay($tr) {
        if ($tr.find('.closing-override-checkbox').is(':checked')) return;
        var open = getNumericVal($tr.find('.opening-qty-input'));
        var primary = getNumericVal($tr.find('.primary-qty-input'));
        var secondary = getNumericVal($tr.find('.secondary-qty-input'));
        var closing = open + primary - secondary;
        $tr.find('.closing-qty-display').text(closing.toFixed(2));
    }

    function bindRowEvents($row) {
        $row.find('.btn-remove-line').off('click').on('click', function() {
            $(this).closest('tr').remove();
            reindexRows();
        });
        $row.find('.opening-qty-input, .primary-qty-input, .secondary-qty-input').off('input').on('input', function() {
            updateClosingDisplay($(this).closest('tr'));
        });
        $row.find('.closing-override-checkbox').off('change').on('change', function() {
            var $tr = $(this).closest('tr');
            var checked = $(this).is(':checked');
            $tr.find('.closing-qty-display').toggleClass('d-none', checked);
            $tr.find('.closing-qty-input').toggleClass('d-none', !checked);
            if (checked) {
                $tr.find('.closing-qty-input').val($tr.find('.closing-qty-display').text());
            }
        });
    }

    $('#add-statement-line-edit').on('click', function() {
        var $row = $($template);
        $tbody.append($row);
        reindexRows();
        bindRowEvents($row);
    });

    $(document).on('input', '#statement-lines-edit-table .opening-qty-input, #statement-lines-edit-table .primary-qty-input, #statement-lines-edit-table .secondary-qty-input', function() {
        updateClosingDisplay($(this).closest('tr'));
    });
    $(document).on('change', '#statement-lines-edit-table .closing-override-checkbox', function() {
        var $tr = $(this).closest('tr');
        var checked = $(this).is(':checked');
        $tr.find('.closing-qty-display').toggleClass('d-none', checked);
        $tr.find('.closing-qty-input').toggleClass('d-none', !checked);
        if (checked) {
            $tr.find('.closing-qty-input').val($tr.find('.closing-qty-display').text());
        }
        reindexRows();
    });

    $tbody.find('tr.statement-line-row').each(function() {
        bindRowEvents($(this));
    });

    function prepareStatementRows() {
        $tbody.find('tr.statement-line-row').each(function() {
            var $tr = $(this);
            var productId = $tr.find('input[type="hidden"]').val() || $tr.find('select.product-select').val();
            if (!productId) {
                $tr.remove();
            }
        });
        reindexRows();
        if ($tbody.find('tr.statement-line-row').length === 0) {
            alert('Please add at least one statement line with a product selected.');
            return false;
        }

        return true;
    }

    $('#update-statement-form').on('click', function() {
        var $form = $('#updateStockStatementForm');
        if (!prepareStatementRows()) {
            return;
        }
        if ($form[0].checkValidity && !$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }

        $.easyAjax({
            url: $form.attr('action'),
            container: '#updateStockStatementForm',
            type: 'POST',
            disableButton: true,
            buttonSelector: '#update-statement-form',
            data: $form.serialize(),
            success: function(response) {
                if (response.status === 'success' && response.action === 'redirect' && response.url) {
                    window.location.href = response.url;
                }
            }
        });
    });

    $('#updateStockStatementForm').on('submit', function(e) {
        if (!prepareStatementRows()) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
@endpush
