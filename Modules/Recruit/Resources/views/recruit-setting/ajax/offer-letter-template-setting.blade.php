<div class="row border-top-grey bg-white p-20">
    <div class="col-lg-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">@lang('recruit::app.menu.offerLetterTemplate')</h4>
            <button type="button" class="btn btn-secondary btn-sm" data-toggle="modal" data-target="#offer-letter-help-modal">
                <i class="fa fa-question-circle"></i> @lang('app.help')
            </button>
        </div>
        <p class="text-muted">@lang('recruit::modules.setting.offerLetterContentDescription')</p>
        
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> 
            @lang('recruit::modules.setting.offerLetterContentInfo')
        </div>

        <!-- Rich Text Content Editor -->
        <form id="save-offer-letter-content-form" method="POST" autocomplete="off" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="redirect_url_offer_letter" name="redirect_url" value="">
            
            <div class="row">
                <div class="col-md-12">
                    <x-forms.label class="my-3" fieldId="offer_letter_content" :fieldLabel="__('recruit::modules.setting.offerLetterBodyContent')" fieldRequired="true" />
                    <div id="offer-letter-content-editor" style="height: 400px; min-height: 400px;">
                        {!! ($mail && $mail->offer_letter_content) ? $mail->offer_letter_content : '' !!}
                    </div>
                    <textarea name="offer_letter_content" id="offer_letter_content" style="display: none;"></textarea>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <x-forms.label class="my-3" fieldId="offer_letter_background_image" :fieldLabel="__('recruit::modules.setting.offerLetterBackgroundImage')" />
                    <x-forms.file allowedFileExtensions="png jpg jpeg svg" class="mr-0 mr-lg-2 mr-md-2"
                        :fieldLabel="__('recruit::modules.setting.offerLetterBackgroundImage')"
                        :fieldValue="($mail && $mail->offer_letter_background_image) ? asset_url_local_s3('offer-letter-background/' . $mail->offer_letter_background_image) : ''"
                        fieldName="offer_letter_background_image"
                        fieldId="offer_letter_background_image"
                        :popover="__('recruit::modules.setting.offerLetterBackgroundImageInfo')">
                    </x-forms.file>
                    @if($mail && $mail->offer_letter_background_image)
                        <input type="hidden" name="offer_letter_background_image_delete" id="offer_letter_background_image_delete" value="no">
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteOfferLetterBackgroundImage()">
                                <i class="fa fa-trash"></i> @lang('app.remove') @lang('recruit::modules.setting.offerLetterBackgroundImage')
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <x-forms.button-primary id="save-offer-letter-content-btn" icon="check">
                        @lang('app.save')
                    </x-forms.button-primary>
                </div>
            </div>
        </form>

        <div class="mt-4">
            <h5>@lang('recruit::modules.setting.availableVariables')</h5>
            <div class="alert alert-light">
                <p class="mb-2"><strong>@lang('recruit::modules.setting.variablesInfo')</strong></p>
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-success">✓ Available from System (Dynamic):</h6>
                        <ul class="mb-3 small">
                            <li><code>@{{ $jobOffer->jobApplication->full_name }}</code> — Candidate name</li>
                            <li><code>@{{ $jobOffer->jobApplication->email }}</code> — Candidate email</li>
                            <li><code>@{{ $jobOffer->jobApplication->phone }}</code> — Candidate mobile</li>
                            <li><code>@{{ $jobOffer->job->title }}</code> — Job title (e.g. Medical Representative)</li>
                            <li><code>@{{ $jobOffer->job->team->team_name }}</code> — Department / team</li>
                            <li><code>@{{ $jobOffer->job->location->location }}</code> — Location / HQ</li>
                            <li><code>@{{ $jobOffer->job->recruiter->name }}</code> — Reporting manager / recruiter name</li>
                            <li><code>@{{ $jobOffer->job->recruiter->designation }}</code> — Reporting manager designation</li>
                            <li><code>@{{ $jobOffer->job->recruiter->email }}</code> — Reporting manager email</li>
                            <li><code>@{{ $jobOffer->job->job_type }}</code> — Job type from job record (e.g. Full Time)</li>
                            <li><code>@{{ $jobOffer->job->job_type_label }}</code> — Job type from <em>Recruit job types</em> table (if set)</li>
                            <li><code>@{{ $jobOffer->job->pay_according }}</code> — Pay period key (month/year/hour…)</li>
                            <li><code>@{{ $jobOffer->comp_amount }}</code> — Compensation amount (formatted with currency)</li>
                            <li><code>@{{ $jobOffer->expected_joining_date }}</code> — Joining date (d-M-Y)</li>
                            <li><code>@{{ $jobOffer->job_expire }}</code> — Offer acceptance / expiry date (d-M-Y)</li>
                            <li><code>@{{ $jobOffer->offer_issue_date }}</code> — Offer letter issue date (from offer record, d-M-Y)</li>
                            <li><code>@{{ $jobOffer->offer_issue_date_slash }}</code> — Same issue date as <code>dd/mm/yyyy</code> (letter header)</li>
                            <li><code>@{{ $jobOffer->offer_valid_days }}</code> — Days between issue date and offer expiry</li>
                            <li><code>@{{ $company->company_name }}</code> — Legal company name</li>
                            <li><code>@{{ $current_date }}</code> or <code>@{{ $today }}</code> — Today&rsquo;s date (company date format)</li>
                            <li><code>@{{ $current_date_slash }}</code> — Today as <code>dd/mm/yyyy</code> (e.g. offer letter header)</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-warning">⚠ Usually fixed in template text</h6>
                        <ul class="mb-3 small">
                            <li><strong>Probation / notice wording</strong> — Edit the paragraph in this editor (not stored per job unless you add custom fields later).</li>
                            <li><strong>Working hours</strong> — Type your standard timings in the HTML (e.g. 10:00 AM–18:30 PM).</li>
                            <li><strong>Sign-off</strong> — Use recruiter variables above for name, designation, and email instead of hardcoding.</li>
                        </ul>
                        <p class="text-muted small">Use the exact tokens including <code>@{{</code> and <code>}}</code>. Legacy <code>{{ $var }}</code> (without <code>@</code>) is still replaced for older templates.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Define modal constants if not already defined (for AJAX-loaded views)
    if (typeof MODAL_LG === 'undefined') {
        window.MODAL_LG = '#myModal';
    }
    if (typeof MODAL_HEADING === 'undefined') {
        window.MODAL_HEADING = '#modelHeading';
    }
    if (typeof MODAL_DEFAULT === 'undefined') {
        window.MODAL_DEFAULT = '#myModalDefault';
    }
    if (typeof MODAL_XL === 'undefined') {
        window.MODAL_XL = '#myModalXl';
    }
    
    $(document).ready(function() {
        // Use the existing quillImageLoad function if available, otherwise initialize manually
        function initQuillEditor() {
            if ($('#offer-letter-content-editor').length === 0) {
                return;
            }
            
            // Check if Quill is already initialized
            if (typeof quillArray !== 'undefined' && quillArray['#offer-letter-content-editor']) {
                return;
            }
            
            // Wait for Quill to be available
            if (typeof Quill === 'undefined') {
                setTimeout(initQuillEditor, 200);
                return;
            }
            
            try {
                // Get initial content before initializing
                const initialContent = $('#offer-letter-content-editor').html().trim();
                
                // Use quillImageLoad if available, otherwise initialize manually
                if (typeof quillImageLoad !== 'undefined') {
                    quillImageLoad('#offer-letter-content-editor');
                    
                    // Set initial content after initialization
                    setTimeout(function() {
                        if (typeof quillArray !== 'undefined' && quillArray['#offer-letter-content-editor']) {
                            if (initialContent && initialContent !== '<p><br></p>' && initialContent !== '') {
                                quillArray['#offer-letter-content-editor'].root.innerHTML = initialContent;
                            }
                        }
                    }, 100);
                } else {
                    // Fallback: manual initialization
                    if (typeof quillArray === 'undefined') {
                        window.quillArray = {};
                    }
                    
                    quillArray['#offer-letter-content-editor'] = new Quill('#offer-letter-content-editor', {
                        theme: 'snow',
                        modules: {
                            toolbar: [
                                [{ header: [1, 2, 3, 4, 5, false] }],
                                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                                [{ align: '' }, { align: 'center' }, { align: 'right' }, { align: 'justify' }],
                                ['bold', 'italic', 'underline', 'strike'],
                                ['link'],
                                [{ color: [] }, { background: [] }],
                                ['clean']
                            ]
                        }
                    });
                    
                    // Set initial content
                    if (initialContent && initialContent !== '<p><br></p>' && initialContent !== '') {
                        quillArray['#offer-letter-content-editor'].root.innerHTML = initialContent;
                    }
                }
            } catch (e) {
                console.error('Error initializing Quill editor:', e);
            }
        }
        
        // Initialize editor after page is fully loaded
        setTimeout(function() {
            initQuillEditor();
        }, 500);

        // Save offer letter content
        $(document).on('click', '#save-offer-letter-content-btn', function(e) {
            e.preventDefault();
            
            let content = '';
            
            // Get content from Quill editor - try multiple methods
            try {
                if (typeof quillArray !== 'undefined' && quillArray['#offer-letter-content-editor']) {
                    content = quillArray['#offer-letter-content-editor'].root.innerHTML;
                } else if (typeof offerLetterQuill !== 'undefined' && offerLetterQuill) {
                    content = offerLetterQuill.root.innerHTML;
                } else {
                    // Fallback: get content from the div
                    content = $('#offer-letter-content-editor').html();
                }
            } catch (e) {
                console.error('Error getting Quill content:', e);
                content = $('#offer-letter-content-editor').html();
            }
            
            // Validate content
            if (!content || content.trim() === '' || content === '<p><br></p>') {
                Swal.fire({
                    icon: 'warning',
                    title: '@lang("app.warning")',
                    text: '@lang("recruit::modules.setting.contentRequired")',
                    confirmButtonText: '@lang("app.ok")'
                });
                return false;
            }
            
            // Update hidden textarea
            $('#offer_letter_content').val(content);
            
            // Create FormData for file upload
            var formData = new FormData($('#save-offer-letter-content-form')[0]);
            formData.set('offer_letter_content', content);
            
            // Submit form
            $.easyAjax({
                url: "{{ route('recruit-settings.save-offer-letter-content') }}",
                container: '#save-offer-letter-content-form',
                type: "POST",
                blockUI: true,
                disableButton: true,
                buttonSelector: "#save-offer-letter-content-btn",
                data: formData,
                processData: false,
                contentType: false,
                file: true,
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '@lang("app.success")',
                            text: response.message || '@lang("messages.updateSuccess")',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        // Reload page to show updated background image
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: '@lang("app.error")',
                            text: response.message || '@lang("messages.somethingWentWrong")',
                            confirmButtonText: '@lang("app.ok")'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Save error:', error, xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: '@lang("app.error")',
                        text: '@lang("messages.somethingWentWrong")' + ': ' + (xhr.responseJSON?.message || error),
                        confirmButtonText: '@lang("app.ok")'
                    });
                }
            });
            
            return false;
        });
        
        function deleteOfferLetterBackgroundImage() {
            Swal.fire({
                title: '@lang("app.areYouSure")',
                text: '@lang("recruit::modules.setting.confirmDeleteBackgroundImage")',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '@lang("app.yes")',
                cancelButtonText: '@lang("app.cancel")',
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#offer_letter_background_image_delete').val('yes');
                    // Get content from Quill editor
                    let content = '';
                    if (typeof quillArray !== 'undefined' && quillArray['#offer-letter-content-editor']) {
                        content = quillArray['#offer-letter-content-editor'].root.innerHTML;
                    } else {
                        content = $('#offer-letter-content-editor').html();
                    }
                    $('#offer_letter_content').val(content);
                    
                    // Create FormData
                    var formData = new FormData($('#save-offer-letter-content-form')[0]);
                    formData.set('offer_letter_content', content);
                    
                    $.easyAjax({
                        url: "{{ route('recruit-settings.save-offer-letter-content') }}",
                        container: '#save-offer-letter-content-form',
                        type: "POST",
                        blockUI: true,
                        data: formData,
                        processData: false,
                        contentType: false,
                        file: true,
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: '@lang("app.success")',
                                    text: response.message || '@lang("messages.updateSuccess")',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                setTimeout(function() {
                                    window.location.reload();
                                }, 2000);
                            }
                        }
                    });
                }
            });
        }
    });
</script>

<!-- Help Modal -->
<div class="modal fade" id="offer-letter-help-modal" tabindex="-1" role="dialog" aria-labelledby="offer-letter-help-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="offer-letter-help-modal-label">
                    <i class="fa fa-info-circle text-primary"></i> @lang('recruit::modules.setting.offerLetterTemplateHelp')
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <h6 class="text-primary mb-3"><i class="fa fa-edit"></i> @lang('recruit::modules.setting.howToUseEditor')</h6>
                        <ul class="mb-4">
                            <li>@lang('recruit::modules.setting.editorHelp1')</li>
                            <li>@lang('recruit::modules.setting.editorHelp2')</li>
                            <li>@lang('recruit::modules.setting.editorHelp3')</li>
                            <li>@lang('recruit::modules.setting.editorHelp4')</li>
                        </ul>

                        <h6 class="text-primary mb-3"><i class="fa fa-code"></i> @lang('recruit::modules.setting.usingVariables')</h6>
                        <div class="alert alert-info mb-4">
                            <p class="mb-2">@lang('recruit::modules.setting.variablesHelp1')</p>
                            <p class="mb-0">@lang('recruit::modules.setting.variablesHelp2')</p>
                        </div>

                        <h6 class="text-primary mb-3"><i class="fa fa-image"></i> @lang('recruit::modules.setting.backgroundImage')</h6>
                        <ul class="mb-4">
                            <li>@lang('recruit::modules.setting.bgImageHelp1')</li>
                            <li>@lang('recruit::modules.setting.bgImageHelp2')</li>
                            <li>@lang('recruit::modules.setting.bgImageHelp3')</li>
                        </ul>

                        <h6 class="text-primary mb-3"><i class="fa fa-sitemap"></i> @lang('recruit::modules.setting.completeFlow')</h6>
                        <div class="flow-diagram mb-4">
                            <div class="step-box mb-2 p-2 bg-light border-left-primary">
                                <strong>1. @lang('recruit::modules.setting.step1')</strong><br>
                                <small class="text-muted">@lang('recruit::modules.setting.step1Desc')</small>
                            </div>
                            <div class="text-center mb-2"><i class="fa fa-arrow-down text-primary"></i></div>
                            <div class="step-box mb-2 p-2 bg-light border-left-success">
                                <strong>2. @lang('recruit::modules.setting.step2')</strong><br>
                                <small class="text-muted">@lang('recruit::modules.setting.step2Desc')</small>
                            </div>
                            <div class="text-center mb-2"><i class="fa fa-arrow-down text-primary"></i></div>
                            <div class="step-box mb-2 p-2 bg-light border-left-info">
                                <strong>3. @lang('recruit::modules.setting.step3')</strong><br>
                                <small class="text-muted">@lang('recruit::modules.setting.step3Desc')</small>
                            </div>
                            <div class="text-center mb-2"><i class="fa fa-arrow-down text-primary"></i></div>
                            <div class="step-box mb-2 p-2 bg-light border-left-warning">
                                <strong>4. @lang('recruit::modules.setting.step4')</strong><br>
                                <small class="text-muted">@lang('recruit::modules.setting.step4Desc')</small>
                            </div>
                            <div class="text-center mb-2"><i class="fa fa-arrow-down text-primary"></i></div>
                            <div class="step-box mb-2 p-2 bg-light border-left-danger">
                                <strong>5. @lang('recruit::modules.setting.step5')</strong><br>
                                <small class="text-muted">@lang('recruit::modules.setting.step5Desc')</small>
                            </div>
                        </div>

                        <h6 class="text-primary mb-3"><i class="fa fa-lightbulb"></i> @lang('recruit::modules.setting.tips')</h6>
                        <div class="alert alert-warning mb-0">
                            <ul class="mb-0 pl-3">
                                <li>@lang('recruit::modules.setting.tip1')</li>
                                <li>@lang('recruit::modules.setting.tip2')</li>
                                <li>@lang('recruit::modules.setting.tip3')</li>
                                <li>@lang('recruit::modules.setting.tip4')</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('app.close')</button>
            </div>
        </div>
    </div>
</div>

<style>
    .border-left-primary {
        border-left: 4px solid #007bff !important;
    }
    .border-left-success {
        border-left: 4px solid #28a745 !important;
    }
    .border-left-info {
        border-left: 4px solid #17a2b8 !important;
    }
    .border-left-warning {
        border-left: 4px solid #ffc107 !important;
    }
    .border-left-danger {
        border-left: 4px solid #dc3545 !important;
    }
    .step-box {
        border-radius: 4px;
    }
</style>

