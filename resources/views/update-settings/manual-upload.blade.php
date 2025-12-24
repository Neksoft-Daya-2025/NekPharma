<div class="row mt-4">
    <div class="col-sm-12">
        <div class="white-box">
            <h4 class="f-21 font-weight-normal mb-3">
                <i class="fa fa-upload"></i> @lang('Manual Update via ZIP Upload')
            </h4>
            
            <x-alert type="warning">
                <ol class="mb-0">
                    <li>@lang('messages.updateAlert')</li>
                    <li>@lang('messages.updateBackupNotice')</li>
                    <li><strong>Important:</strong> Your existing data will be preserved. The .env file will NOT be overwritten.</li>
                    <li>Database migrations will run automatically after file extraction.</li>
                </ol>
            </x-alert>

            <div class="row mt-3">
                <div class="col-md-12">
                    <h6 class="mb-2">Upload Update ZIP File</h6>
                    <p class="text-muted">Upload a ZIP file containing the updated application files. The system will automatically extract files, run migrations, and clear caches.</p>
                    
                    <div id="manual-update-dropzone" class="dropzone">
                        <div class="dz-message">
                            <i class="fa fa-cloud-upload fa-3x text-muted"></i>
                            <p class="text-muted mt-2">@lang('app.dropFileToUpload')</p>
                            <p class="text-muted small">Accepted: ZIP files only</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4" id="uploaded-files-section" style="display: none;">
                <div class="col-md-12">
                    <h6 class="mb-3">Uploaded Update Files</h6>
                    <div id="uploaded-files-list"></div>
                </div>
            </div>

            <div id="update-progress" class="mt-3" style="display: none;">
                <div class="alert alert-info">
                    <i class="fa fa-spinner fa-spin"></i> <span id="update-status">Preparing update...</span>
                </div>
                <div class="progress" style="height: 25px;">
                    <div id="update-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" style="width: 0%">0%</div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/css/dropzone.min.css') }}">
<style>
    .dropzone {
        border: 2px dashed #ccc;
        border-radius: 5px;
        padding: 20px;
        text-align: center;
        min-height: 150px;
        background: #f9f9f9;
    }
    .dropzone.dz-started {
        min-height: auto;
    }
    .file-item {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-bottom: 10px;
        background: #fff;
    }
</style>
@endpush

@push('scripts')
<script src="{{ asset('vendor/js/dropzone.min.js') }}"></script>
<script>
    Dropzone.autoDiscover = false;
    
    $(document).ready(function() {
        const uploadUrl = "{{ route('update-settings.store') }}";
        const installUrl = "{{ route('update-settings.install') }}";
        
        let updateDropzone = new Dropzone("#manual-update-dropzone", {
            url: uploadUrl,
            acceptedFiles: 'application/zip, application/x-zip-compressed, application/x-compressed, multipart/x-zip',
            maxFilesize: 500, // 500 MB
            addRemoveLinks: true,
            dictDefaultMessage: "@lang('app.dropFileToUpload')",
            dictRemoveFile: "@lang('app.remove')",
            dictFileTooBig: "File is too big. Maximum file size is 500MB.",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            init: function() {
                this.on("success", function(file, response) {
                    if (response.status === 'success') {
                        showUploadedFile(response.data.fileName, response.data.filePath);
                        this.removeAllFiles();
                    }
                });
                
                this.on("error", function(file, errorMessage) {
                    $.showToastr(errorMessage, 'error');
                });
            }
        });
        
        // Load existing uploaded files
        loadUploadedFiles();
    });
    
    function loadUploadedFiles() {
        const updatesPath = "{{ storage_path('app/updates') }}";
        // This would require a separate endpoint to list files
        // For now, we'll show files after upload
    }
    
    function showUploadedFile(fileName, filePath) {
        const filesList = $('#uploaded-files-list');
        const fileHtml = `
            <div class="file-item">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <i class="fa fa-file-archive text-primary"></i> <strong>${fileName}</strong>
                    </div>
                    <div class="col-md-4 text-muted small">
                        <i class="fa fa-clock"></i> ${new Date().toLocaleString()}
                    </div>
                    <div class="col-md-2 text-right">
                        <button type="button" class="btn btn-primary btn-sm install-update" 
                                data-file-path="${filePath}" data-file-name="${fileName}">
                            <i class="fa fa-download"></i> Install Update
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        filesList.html(fileHtml);
        $('#uploaded-files-section').show();
        
        // Bind install button
        $('.install-update').on('click', function() {
            installUpdate($(this).data('file-path'), $(this).data('file-name'));
        });
    }
    
    function installUpdate(filePath, fileName) {
        if (!confirm('Are you sure you want to install this update? The application will be put in maintenance mode during the update. You will be logged out after the update completes.')) {
            return;
        }
        
        $('#update-progress').show();
        $('#update-status').text('Installing update...');
        $('#update-progress-bar').css('width', '10%').text('10%');
        
        $.easyAjax({
            type: 'POST',
            url: installUrl,
            data: {
                _token: '{{ csrf_token() }}',
                filePath: filePath
            },
            blockUI: true,
            success: function(response) {
                $('#update-progress-bar').css('width', '100%').text('100%');
                $('#update-status').text('Update completed successfully!');
                
                setTimeout(function() {
                    if (response.data && response.data.redirect) {
                        window.location.href = response.data.redirect;
                    } else {
                        window.location.reload();
                    }
                }, 2000);
            },
            error: function(response) {
                $('#update-progress-bar').css('width', '0%').text('0%');
                $('#update-status').text('Update failed. Please check logs.');
                $.showToastr(response.responseJSON.message || 'Update failed', 'error');
            }
        });
    }
</script>
@endpush

