<div class="bg-white rounded b-shadow-4 create-inv">
    <div class="px-lg-4 px-md-4 px-3 py-3">
        <h4 class="mb-0 f-21 font-weight-normal">@lang('app.add') @lang('app.salesStockStatement')</h4>
    </div>
    <hr class="m-0 border-top-grey">

    <x-form class="c-inv-form" id="saveStockStatementForm" method="POST" action="{{ route('stock-statements.store') }}">
        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-md-3">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Period Month <span class="text-danger">*</span></label>
                    <select name="period_month" id="period_month" class="form-control" required>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ ($periodMonth ?? 0) == $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Period Year <span class="text-danger">*</span></label>
                    <select name="period_year" id="period_year" class="form-control" required>
                        @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                            <option value="{{ $y }}" {{ ($periodYear ?? 0) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">CFA Stockist <span class="text-danger">*</span></label>
                    <select name="cfa_stockist_id" id="cfa_stockist_id" class="form-control select-picker" data-live-search="true" required>
                        <option value="">Select Stockist</option>
                        @foreach($cfaStockists as $s)
                            <option value="{{ $s->id }}" {{ (isset($cfaStockistId) && $cfaStockistId == $s->id) ? 'selected' : '' }}>{{ $s->shopname }} ({{ $s->cfa_stockist_id }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="px-lg-4 px-md-4 px-3 py-2">
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-2">
                <label class="f-14 text-dark-grey mb-0">Statement Lines (Opening / Primary / Secondary / Closing)</label>
                <div class="d-flex flex-wrap align-items-center">
                    <a href="{{ route('stock-statements.import.sample') }}" class="btn btn-sm btn-secondary mr-2 mb-2 mb-md-0">
                        <i class="fa fa-download"></i> Sample CSV
                    </a>
                    <label class="btn btn-sm btn-secondary mr-2 mb-2 mb-md-0 mb-0" for="stock-statement-import-file">
                        <i class="fa fa-file-upload"></i> Import CSV
                    </label>
                    <input type="file" id="stock-statement-import-file" accept=".csv,text/csv" class="d-none">
                    <button type="button" class="btn btn-sm btn-primary mb-2 mb-md-0" id="add-statement-line"><i class="fa fa-plus"></i> Add line</button>
                </div>
            </div>
            <p class="text-muted small mb-2">Select period and CFA stockist first, then import CSV or add lines manually. Closing = Opening + Primary − Secondary unless Closing Qty is provided in the file.</p>
            <div class="table-responsive">
                <table class="table table-bordered" id="statement-lines-table">
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
                        {{-- Rows added by JS --}}
                    </tbody>
                </table>
            </div>
            <p class="text-muted small mb-0">Closing is computed as Opening + Primary − Secondary. Tick "Override" to enter Closing manually.</p>
            @if($products->isEmpty())
                <p class="text-muted mt-2">@lang('messages.noRecordFound') Add products first.</p>
            @endif
        </div>

        <div class="row px-lg-4 px-md-4 px-3 py-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="f-14 text-dark-grey mb-12">Save as</label>
                    <select name="status" id="status" class="form-control">
                        <option value="draft">Draft</option>
                        <option value="submitted">Submit</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end px-lg-4 px-md-4 px-3 py-3 border-top-grey">
            <x-forms.button-cancel :link="route('stock-statements.index')" class="mr-3">@lang('app.cancel')</x-forms.button-cancel>
            <x-forms.button-primary id="save-statement-form" icon="check">@lang('app.save')</x-forms.button-primary>
        </div>
    </x-form>
</div>

{{-- Template row for "Add line" (hidden) --}}
<template id="statement-line-row-template">
    <tr class="statement-line-row">
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
    var getOpeningPrimaryUrl = "{{ route('stock-statements.get-opening-primary') }}";
    var importLinesUrl = "{{ route('stock-statements.import.lines') }}";
    var importCsrf = "{{ csrf_token() }}";
    var $tbody = $('#statement-lines-table tbody');
    var $template = $('#statement-line-row-template').html();
    var lineIndex = 0;

    function reindexRows() {
        $tbody.find('tr.statement-line-row').each(function(idx) {
            var $tr = $(this);
            $tr.find('select.product-select').attr('name', 'lines[' + idx + '][product_id]');
            $tr.find('.opening-qty-input').attr('name', 'lines[' + idx + '][opening_qty]');
            $tr.find('.primary-qty-input').attr('name', 'lines[' + idx + '][primary_qty]');
            $tr.find('.secondary-qty-input').attr('name', 'lines[' + idx + '][secondary_qty]');
            var closingName = $tr.find('.closing-override-checkbox').is(':checked') ? ('lines[' + idx + '][closing_qty]') : '';
            $tr.find('.closing-qty-input').attr('name', closingName);
            $tr.attr('data-row-index', idx);
        });
        lineIndex = $tbody.find('tr.statement-line-row').length;
    }

    function addRow() {
        var $row = $($template);
        $tbody.append($row);
        reindexRows();
        bindRowEvents($row);
        loadOpeningPrimaryForRow($row);
        return $row;
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
            $tr.find('.closing-qty-input').toggleClass('d-none', !checked).prop('required', false);
            if (checked) {
                $tr.find('.closing-qty-input').val($tr.find('.closing-qty-display').text());
            }
        });
        $row.find('.closing-qty-input').off('input').on('input', function() {
            var $tr = $(this).closest('tr');
            if ($tr.find('.closing-override-checkbox').is(':checked')) {
                // optional: sync display when overriding
            }
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

    function loadOpeningPrimary() {
        var cfaStockistId = $('#cfa_stockist_id').val();
        var periodMonth = $('#period_month').val();
        var periodYear = $('#period_year').val();
        if (!cfaStockistId || !periodMonth || !periodYear) return;
        var productIds = [];
        $tbody.find('select.product-select').each(function() {
            var v = $(this).val();
            if (v) productIds.push(v);
        });
        if (productIds.length === 0) return;
        $.ajax({
            url: getOpeningPrimaryUrl,
            type: 'GET',
            data: {
                cfa_stockist_id: cfaStockistId,
                period_month: periodMonth,
                period_year: periodYear,
                product_ids: productIds
            },
            success: function(res) {
                if (res.status === 'success' && res.data) {
                    $tbody.find('tr.statement-line-row').each(function() {
                        var pid = $(this).find('select.product-select').val();
                        if (pid && res.data[pid]) {
                            var row = res.data[pid];
                            $(this).find('.opening-qty-input').val(parseFloat(row.opening_qty || 0));
                            $(this).find('.primary-qty-input').val(parseFloat(row.primary_qty || 0));
                            updateClosingDisplay($(this));
                        }
                    });
                }
            }
        });
    }

    function loadOpeningPrimaryForRow($tr) {
        var pid = $tr.find('select.product-select').val();
        if (!pid) return;
        var cfaStockistId = $('#cfa_stockist_id').val();
        var periodMonth = $('#period_month').val();
        var periodYear = $('#period_year').val();
        if (!cfaStockistId || !periodMonth || !periodYear) return;
        $.ajax({
            url: getOpeningPrimaryUrl,
            type: 'GET',
            data: {
                cfa_stockist_id: cfaStockistId,
                period_month: periodMonth,
                period_year: periodYear,
                product_ids: [pid]
            },
            success: function(res) {
                if (res.status === 'success' && res.data && res.data[pid]) {
                    var row = res.data[pid];
                    $tr.find('.opening-qty-input').val(parseFloat(row.opening_qty || 0));
                    $tr.find('.primary-qty-input').val(parseFloat(row.primary_qty || 0));
                    updateClosingDisplay($tr);
                }
            }
        });
    }

    function addRowFromImport(line) {
        var $row = $($template);
        $tbody.append($row);
        $row.find('select.product-select').val(String(line.product_id));
        $row.find('.opening-qty-input').val(parseFloat(line.opening_qty || 0));
        $row.find('.primary-qty-input').val(parseFloat(line.primary_qty || 0));
        $row.find('.secondary-qty-input').val(parseFloat(line.secondary_qty || 0));
        $row.find('.closing-qty-display').text(parseFloat(line.closing_qty || 0).toFixed(2));
        reindexRows();
        bindRowEvents($row);
        return $row;
    }

    $('#stock-statement-import-file').on('change', function() {
        var fileInput = this;
        var file = fileInput.files && fileInput.files[0];
        if (!file) {
            return;
        }

        var cfaStockistId = $('#cfa_stockist_id').val();
        var periodMonth = $('#period_month').val();
        var periodYear = $('#period_year').val();

        if (!cfaStockistId || !periodMonth || !periodYear) {
            alert('Please select Period Month, Period Year, and CFA Stockist before importing CSV.');
            fileInput.value = '';
            return;
        }

        var formData = new FormData();
        formData.append('_token', importCsrf);
        formData.append('import_file', file);
        formData.append('cfa_stockist_id', cfaStockistId);
        formData.append('period_month', periodMonth);
        formData.append('period_year', periodYear);

        $.ajax({
            url: importLinesUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.status !== 'success' || !res.lines || !res.lines.length) {
                    alert(res.message || 'No valid rows were imported.');
                    return;
                }

                $tbody.empty();
                res.lines.forEach(function(line) {
                    addRowFromImport(line);
                });
                reindexRows();

                var skippedCount = (res.skipped || []).length;
                var message = res.imported + ' line(s) imported from CSV.';
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

    $('#add-statement-line').on('click', function() {
        addRow();
    });

    $('#cfa_stockist_id, #period_month, #period_year').on('change', loadOpeningPrimary);

    $(document).on('change', 'select.product-select', function() {
        var $tr = $(this).closest('tr');
        loadOpeningPrimaryForRow($tr);
    });

    function prepareStatementRows() {
        $tbody.find('tr.statement-line-row').each(function() {
            if (!$(this).find('select.product-select').val()) {
                $(this).remove();
            }
        });
        reindexRows();
        if ($tbody.find('tr.statement-line-row').length === 0) {
            alert('Please add at least one statement line with a product selected.');
            return false;
        }

        return true;
    }

    $('#save-statement-form').on('click', function() {
        var $form = $('#saveStockStatementForm');
        if (!prepareStatementRows()) {
            return;
        }
        if ($form[0].checkValidity && !$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }

        $.easyAjax({
            url: $form.attr('action'),
            container: '#saveStockStatementForm',
            type: 'POST',
            disableButton: true,
            buttonSelector: '#save-statement-form',
            data: $form.serialize(),
            success: function(response) {
                if (response.status === 'success' && response.action === 'redirect' && response.url) {
                    window.location.href = response.url;
                }
            }
        });
    });

    // Keep native submit behavior valid if the form is submitted by Enter key.
    $('#saveStockStatementForm').on('submit', function(e) {
        if (!prepareStatementRows()) {
            e.preventDefault();
            return false;
        }
    });

    // Start with one empty row
    if ($tbody.find('tr.statement-line-row').length === 0) {
        addRow();
    }
});
</script>
@endpush
