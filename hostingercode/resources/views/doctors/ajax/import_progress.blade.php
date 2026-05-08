<div class="col-sm-12">
    <div class="bg-white rounded p-2" id="afterSubmitting">
        <div class="alert alert-warning" role="alert" id="process-warning">
            @lang("app.doNotCloseOrRefreshPage")
        </div>
        <div class="alert alert-success" role="alert" id="importSuccess" style="display:none">
        </div>
        <div class="alert alert-success" role="alert" id="progressSuccess" style="display:none">
        </div>
        <div class="alert alert-danger" role="alert" id="failedJobsCount" style="display:none">
        </div>
        <div class="alert alert-info" role="alert" id="importSummaryAlert" style="display:none">
        </div>
        <div id="progressError" style="display:none"></div>
        <div id="progress">
            <p>@lang('app.importInProgress') <strong id="progressAmount">@lang('app.pleaseWait')</strong></p>
            <div class="progress">
                <div id="processingBarStatus" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
        </div>
    </div>
    <div class="w-100 border-top-grey justify-content-start px-4 py-3 bg-white rounded d-none" id="afterProcessing">
        <x-forms.link-primary :link="route('doctors.index')">@lang('app.back') @lang('Doctors')</x-forms.link-primary>
    </div>
</div>
<div id="importDetailTables" class="col-sm-12 mt-3" style="display:none"></div>
<div id="exceptionTable" class="col-sm-12 mt-2" style="display:none"></div>

<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
<script type="text/javascript">
    let progress = 0;
    let batchId = @json($batchId ?? (isset($batch) && is_object($batch) ? ($batch->id ?? null) : null));

    function getProgress(batchId) {
        $('#afterSubmitting').show();
        var url = "{{ route('import.process.progress', ['DoctorImport', ':batchId']) }}";
        url = url.replace(':batchId', batchId);

        setTimeout(function () {
            $.easyAjax({
                type: 'GET',
                url: url,
                success: function (response) {
                    var failedJobs = response.failedJobs;
                    var pendingJobs = response.pendingJobs;
                    var processedJobs = response.processedJobs;
                    progress = response.progress;
                    var totalJobs = response.totalJobs;

                    $('#processingBarStatus').width(progress + '%');
                    $('#processingBarStatus').html(progress + '%');
                    $('#progressAmount').html(progress + '%');

                    if(failedJobs > 0){
                        var failedMsg = `@lang("app.importFailedJobs")`;
                        failedMsg = failedMsg.replace(':failedJobs', failedJobs).replace(':totalJobs', totalJobs);
                        $('#failedJobsCount').html(failedMsg);
                        $('#failedJobsCount').show();
                    }
                    if(processedJobs > 0){
                        var processedMsg = `@lang("app.importProcessedJobs")`;
                        processedMsg = processedMsg.replace(':processedJobs', processedJobs).replace(':totalJobs', totalJobs);
                        $('#progressSuccess').html(processedMsg);
                        $('#progressSuccess').show();
                    }

                    if (totalJobs != (failedJobs + processedJobs)) {
                        getProgress(batchId);
                    } else {
                        $('#importSuccess').html(`@lang("app.importCompleted")`);
                        $('#process-warning').hide();
                        $('#importSuccess').show();
                        $('#progress').hide();
                        $('#afterProcessing').removeClass('d-none');
                        $('#afterProcessing').addClass('d-lg-flex d-md-flex d-block');
                        if (response.importSummary) {
                            showImportSummary(response.importSummary);
                        }
                        getQueueException();
                    }
                },
                error: function (response) {
                    if (progress != 100) {
                        getProgress(batchId);
                    }
                }
            });
        }, 2000);
    }

    function showImportSummary(summary) {
        var parts = [];
        if (summary.new > 0) {
            parts.push(summary.new + ' new doctor(s) added');
        }
        if (summary.updated > 0) {
            parts.push(summary.updated + ' existing record(s) updated');
        }
        if (summary.skipped > 0) {
            parts.push(summary.skipped + ' row(s) skipped');
        }
        if (summary.errors > 0) {
            parts.push(summary.errors + ' error(s)');
        }
        if (parts.length === 0) {
            return;
        }
        var html = '<strong>Import summary:</strong> ' + parts.join('. ');
        if (summary.duplicate_names && summary.duplicate_names.length > 0) {
            html += '<br><strong>Duplicates / notes:</strong> ' + summary.duplicate_names.slice(0, 20).join(', ');
            if (summary.duplicate_names.length > 20) {
                html += ' <span class="text-muted">and ' + (summary.duplicate_names.length - 20) + ' more</span>';
            }
        }
        $('#importSummaryAlert').html(html).show();

        // Build detailed tables for skipped and errors
        var detailHtml = '';
        if (summary.skipped_details && summary.skipped_details.length > 0) {
            detailHtml += '<div class="card mb-3"><div class="card-header bg-warning text-dark"><strong>Skipped rows (' + summary.skipped_details.length + ')</strong> – each row and reason</div><div class="card-body p-0"><div class="table-responsive" style="max-height:320px;overflow-y:auto;"><table class="table table-sm table-striped table-bordered mb-0"><thead class="thead-light"><tr><th>Row #</th><th>Dr. Name</th><th>HQ</th><th>Reason</th></tr></thead><tbody>';
            summary.skipped_details.forEach(function(r) {
                detailHtml += '<tr><td>' + (r.row || '') + '</td><td>' + escapeHtml(r.name || '') + '</td><td>' + escapeHtml(r.hq || '') + '</td><td>' + escapeHtml(r.reason || '') + '</td></tr>';
            });
            detailHtml += '</tbody></table></div></div></div>';
        }
        if (summary.error_details && summary.error_details.length > 0) {
            detailHtml += '<div class="card mb-3"><div class="card-header bg-danger text-white"><strong>Errors (' + summary.error_details.length + ')</strong> – each row and error message</div><div class="card-body p-0"><div class="table-responsive" style="max-height:320px;overflow-y:auto;"><table class="table table-sm table-striped table-bordered mb-0"><thead class="thead-light"><tr><th>Row #</th><th>Dr. Name</th><th>HQ</th><th>Error</th></tr></thead><tbody>';
            summary.error_details.forEach(function(r) {
                detailHtml += '<tr><td>' + (r.row || '') + '</td><td>' + escapeHtml(r.name || '') + '</td><td>' + escapeHtml(r.hq || '') + '</td><td>' + escapeHtml(r.reason || '') + '</td></tr>';
            });
            detailHtml += '</tbody></table></div></div></div>';
        }
        if (summary.duplicate_names && summary.duplicate_names.length > 0) {
            detailHtml += '<div class="card mb-3"><div class="card-header bg-info text-white"><strong>Already existed – updated (' + summary.duplicate_names.length + ')</strong></div><div class="card-body"><div style="max-height:200px;overflow-y:auto;">' + summary.duplicate_names.map(function(n){ return escapeHtml(n); }).join(', ') + '</div></div></div>';
        }
        if (detailHtml) {
            $('#importDetailTables').html(detailHtml).show();
        }
    }

    function escapeHtml(text) {
        if (text == null) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function getQueueException() {
        var url = "{{ route('import.process.exception', 'DoctorImport') }}";

        $.easyAjax({
            type: 'GET',
            url: url,
            success: function(response){
                if (response.count) {
                    $('#exceptionTable').html(response.view);
                    $('#exceptionTable').show();
                }
            }
        });
    }

    $(document).ready(function() {
        if (batchId) {
            getProgress(batchId);
        }
    });
</script>

