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
        <div id="progressError" style="display:none"></div>
        <div id="progress">
            <p>@lang('app.importInProgress') <strong id="progressAmount">@lang('app.pleaseWait')</strong></p>
            <div class="progress">
                <div id="processingBarStatus" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
            </div>
        </div>
    </div>
    <div class="w-100 border-top-grey justify-content-start px-4 py-3 bg-white rounded d-none" id="afterProcessing">
        <x-forms.link-primary :link="route('stockists.index')">@lang('app.back') @lang('Stockists')</x-forms.link-primary>
    </div>
</div>
<div id="exceptionTable" class="col-sm-12 mt-2" style="display:none"></div>

<script src="https://cdn.jsdelivr.net/npm/lodash@4.17.21/lodash.min.js"></script>
<script type="text/javascript">
    let progress = 0;
    let batchId = @json($batchId ?? (isset($batch) && is_object($batch) ? ($batch->id ?? null) : null));

    function getProgress(batchId) {
        $('#afterSubmitting').show();
        var url = "{{ route('import.process.progress', ['StockistImport', ':batchId']) }}";
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
                    }else{
                        $('#importSuccess').html(`@lang("app.importCompleted")`);
                        $('#process-warning').hide();
                        $('#importSuccess').show();
                        $('#progress').hide();
                        $('#afterProcessing').removeClass('d-none');
                        $('#afterProcessing').addClass('d-lg-flex d-md-flex d-block');
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

    function getQueueException() {
        var url = "{{ route('import.process.exception', 'StockistImport') }}";

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

