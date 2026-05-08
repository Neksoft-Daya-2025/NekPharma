<?php

namespace Modules\Recruit\Http\Controllers;

use App\Helper\Files;
use App\Helper\Reply;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Recruit\Entities\RecruitSetting;
use App\Http\Controllers\AccountBaseController;
use App\Models\Company;
use App\Models\User;
use Modules\Recruit\Entities\ApplicationSource;
use Modules\Recruit\Entities\RecruitCustomQuestion;
use Modules\Recruit\Entities\RecruitEmailNotificationSetting;
use Modules\Recruit\Entities\Recruiter;
use Modules\Recruit\Entities\RecruitFooterLink;
use Modules\Recruit\Entities\RecruitJobCustomQuestion;
use Modules\Recruit\Entities\RecruitApplicationStatus;
use Modules\Recruit\Http\Requests\RecruitSetting\StoreSettingRequest;

class RecruitSettingController extends AccountBaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'recruit::app.menu.recruitSetting';
        $this->middleware(function ($request, $next) {
            abort_403(!in_array(RecruitSetting::MODULE_NAME, $this->user->modules));

            return $next($request);
        });

    }

    public function index()
    {
        $this->mail = RecruitSetting::where('company_id', '=', company()->id)->first();
        
        // Ensure mail setting exists for offer letter template
        if (!$this->mail && request('tab') === 'offer-letter-template-setting') {
            $this->mail = new RecruitSetting();
            $this->mail->company_id = company()->id;
        }
        
        // Set default offer letter content if not exists
        if ($this->mail && empty($this->mail->offer_letter_content) && request('tab') === 'offer-letter-template-setting') {
            $defaultContent = <<<'OFFER_HTML'
<table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
<tbody>
<tr>
<td style="text-align: right;"><strong>Date:</strong> &ndash; @{{ $jobOffer->offer_issue_date_slash }}</td>
</tr>
</tbody>
</table>
<p><strong>@{{ $jobOffer->jobApplication->full_name }}</strong></p>
<p>S/O _________________________________</p>
<p>&nbsp;</p>
<p style="text-align: center;"><strong>OFFER LETTER</strong></p>
<p>&nbsp;</p>
<p>Dear @{{ $jobOffer->jobApplication->full_name }},</p>
<p>&nbsp;</p>
<p>As per your application &amp; subsequent interview, we are pleased to offer you the position of <strong>@{{ $jobOffer->job->title }}</strong> at <strong>@{{ $jobOffer->job->location->location }}</strong>, effective <strong>@{{ $jobOffer->expected_joining_date }}</strong>. You will report to <strong>@{{ $jobOffer->job->recruiter->name }}</strong> of the company.</p>
<p>&nbsp;</p>
<p>Your annual package will be <strong>@{{ $jobOffer->comp_amount }}</strong>, subject to applicable taxes and deductions. The bifurcation of the same will be shown in appointment letter. During the probation period, your performance will be continuously reviewed. In case the Company is not satisfied with your performance, conduct, or overall suitability for the role, the Company reserves the right to terminate your employment at any time during the probation period without prior notice or compensation in lieu of notice.</p>
<p>&nbsp;</p>
<p>In the event you wish to resign from your position during or after the probation period, you will be required to serve a notice period of one (01) month or salary in lieu of the notice period, subject to management approval.</p>
<p>&nbsp;</p>
<p>This is a <strong>@{{ $jobOffer->job->job_type_label }}</strong> role with regular working hours of <strong>Monday &ndash; Saturday, 10:00 AM&ndash;18:30 PM</strong>, with flexibility required depending on project demands.</p>
<p>&nbsp;</p>
<p>Please note that this offer is subject to document verification, and we require all necessary documents to be submitted on or before your date of joining.</p>
<p>&nbsp;</p>
<p>If you accept this offer, please confirm by signing and returning one copy of this letter to us. We look forward to having you join our team!</p>
<p>&nbsp;</p>
<p><strong>Note:-</strong> This letter is valid for <strong>@{{ $jobOffer->offer_valid_days }}</strong> days from the date of issue. After that this offer shall be considered as cancelled.</p>
<p>&nbsp;</p>
<p>Thanking You,</p>
<p>For <strong>@{{ $company->company_name }}</strong></p>
<p>&nbsp;</p>
<table style="width: 100%; border-collapse: collapse; margin-top: 32px;">
<tbody>
<tr>
<td style="width: 33%; text-align: center; vertical-align: top; font-size: 12px;">Full Signature &amp; Date</td>
<td style="width: 34%; text-align: center; vertical-align: top; font-size: 12px;"><strong>Authorized Signatory</strong><br>@{{ $jobOffer->job->recruiter->name }}<br>@{{ $jobOffer->job->recruiter->designation }}<br><span style="font-size: 11px;">@{{ $jobOffer->job->recruiter->email }}</span></td>
<td style="width: 33%; text-align: center; vertical-align: top; font-size: 12px;">(As a token of acceptance)</td>
</tr>
</tbody>
</table>
OFFER_HTML;
            $this->mail->offer_letter_content = $defaultContent;
        }
        
        $this->recruiters = Recruiter::with('user')->get();
        $this->employees = User::allEmployees()->all();
        $this->selectedRecruiter = Recruiter::get()->pluck('user_id')->toArray();
        $this->activeSettingMenu = 'recruit_settings';
        $this->emailSettings = RecruitEmailNotificationSetting::all();
        $this->footerLinks = RecruitFooterLink::where('company_id', '=', company()->id)->get();
        $this->jobQuestions = RecruitCustomQuestion::where('company_id', '=', company()->id)->get();
        $this->statuses = RecruitApplicationStatus::with('category')->where('company_id', '=', company()->id)->get();
        $this->sources = ApplicationSource::all();


        $tab = request('tab');

        switch ($tab) {
        case 'recruit-setting':
            $this->view = 'recruit::recruit-setting.ajax.recruit-setting';
            break;
        case 'footer-settings':
            $this->view = 'recruit::recruit-setting.ajax.footer-settings';
            break;
        case 'recruit-email-notification-setting':
            $this->view = 'recruit::recruit-setting.ajax.recruit-email-notification-setting';
            break;
        case 'job-application-status-settings':
            $this->view = 'recruit::recruit-setting.ajax.job-application-status-settings';
            break;
        case 'recruit-custom-question-setting':
            $this->view = 'recruit::recruit-setting.ajax.custom-question-settings';
            break;
        case 'recruit-source-setting':
                $this->view = 'recruit::recruit-setting.ajax.source-setting';
                break;
        case 'offer-letter-template-setting':
            $this->view = 'recruit::recruit-setting.ajax.offer-letter-template-setting';
            break;
        default:
            $this->general = RecruitSetting::where('company_id', '=', company()->id)->select('about')->first();
            $this->view = 'recruit::recruit-setting.ajax.general-setting';
            break;
        }

        $this->activeTab = $tab ?: 'general-setting';

        if (request()->ajax()) {
            try {
                $html = view($this->view, $this->data)->render();
                return Reply::dataOnly(['status' => 'success', 'html' => $html, 'title' => $this->pageTitle, 'activeTab' => $this->activeTab]);
            } catch (\Exception $e) {
                Log::error('Error rendering recruit settings view', [
                    'view' => $this->view,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return Reply::error('Error loading view: ' . $e->getMessage());
            }
        }

        return view('recruit::recruit-setting.index', $this->data);
    }

    public function update(StoreSettingRequest $request)
    {
        $settings = RecruitSetting::where('company_id', company()->id)->first();

        $formSetting = [];
        $ar = $request->checkColumns;

        foreach ($settings->form_settings as $id => $from) {
            $from['status'] = false;

            if ($request->has('checkColumns') && in_array($id, $ar)) {
                $from['status'] = true;
            }

            $formSetting = Arr::add($formSetting, $id, $from);
        }

        // Background image

        if ($request->image_delete == 'yes') {
            Files::deleteFile($settings->background_image, 'background');
            $settings->background_image = null;
        }
        elseif ($request->type == 'bg-image') {
            $oldImage = $settings->background_image;

            if ($request->hasFile('image')) {
                $settings->background_image = Files::uploadLocalOrS3($request->image, 'background');

                $path = Files::UPLOAD_FOLDER . '/background' . '/' . $oldImage;

                if (\File::exists($path)) {
                    Files::deleteFile($oldImage, 'background');
                }
            }
        }
        elseif ($request->type == 'bg-color') {
            $settings->background_color = $request->logo_background_color;
        }

        // front page logo

        if ($request->logo_delete == 'yes') {
            Files::deleteFile($settings->logo, 'company-logo');
            $settings->logo = null;
        }

        if ($request->hasFile('logo')) {
            Files::deleteFile($settings->logo, 'company-logo');
            $settings->logo = Files::uploadLocalOrS3($request->logo, 'company-logo');
        }

        if ($request->favicon_delete == 'yes') {
            Files::deleteFile($settings->favicon, 'company-favicon');
            $settings->favicon = null;
        }

        if ($request->hasFile('favicon')) {
            $settings->favicon = Files::uploadLocalOrS3($request->favicon, 'company-favicon', null, null, false);
        }

        $settings->career_site = $request->career_site;
        $settings->job_alert_status = $request->job_alert_status ?? 'no';
        $settings->google_recaptcha_status = $request->google_recaptcha_status ?? 'deactive';
        session()->forget('messageforAdmin');
        $settings->company_name = $request->company_name;
        $settings->application_restriction = $request->application_restriction;
        $settings->offer_letter_reminder = $request->offer_letter_reminder;
        $settings->company_website = $request->company_website;
        $settings->about = $request->about;
        $settings->type = $request->type;
        $settings->form_settings = $formSetting;
        $settings->legal_term = ($request->description == '<p><br></p>') ? null : $request->description;
        $settings->save();

        return Reply::successWithData(__('recruit::messages.settingupdated'), ['redirectUrl' => route('recruit-settings.index')]);
    }

    public function getTemplateContent(Request $request)
    {
        try {
            $type = $request->get('type');
            
            if ($type === 'pdf') {
                $filePath = base_path('Modules/Recruit/Resources/views/jobs/offer-letter/offer-letter-pdf.blade.php');
            } elseif ($type === 'preview') {
                $filePath = base_path('Modules/Recruit/Resources/views/jobs/offer-letter-preview.blade.php');
            } else {
                return response()->json(Reply::error('Invalid template type'), 400);
            }

            if (!file_exists($filePath)) {
                Log::error('Template file not found', ['path' => $filePath, 'type' => $type]);
                return response()->json(Reply::error('Template file not found at: ' . $filePath), 404);
            }

            if (!is_readable($filePath)) {
                return response()->json(Reply::error('Template file is not readable. Please check file permissions.'), 403);
            }

            $content = file_get_contents($filePath);

            if ($content === false) {
                return response()->json(Reply::error('Failed to read template file'), 500);
            }

            return response()->json(Reply::dataOnly([
                'status' => 'success',
                'content' => $content,
                'filePath' => $filePath
            ]));
        } catch (\Exception $e) {
            Log::error('Error in getTemplateContent', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'type' => $request->get('type')
            ]);
            return response()->json(Reply::error('Error loading template: ' . $e->getMessage()), 500);
        }
    }

    public function saveTemplateContent(Request $request)
    {
        try {
            $type = $request->get('type');
            $content = $request->get('content');

            if ($type === 'pdf') {
                $filePath = base_path('Modules/Recruit/Resources/views/jobs/offer-letter/offer-letter-pdf.blade.php');
            } elseif ($type === 'preview') {
                $filePath = base_path('Modules/Recruit/Resources/views/jobs/offer-letter-preview.blade.php');
            } else {
                return response()->json(Reply::error('Invalid template type'), 400);
            }

            if (!file_exists($filePath)) {
                Log::error('Template file not found for save', ['path' => $filePath, 'type' => $type]);
                return response()->json(Reply::error('Template file not found'), 404);
            }

            if (!is_writable($filePath)) {
                return response()->json(Reply::error('Template file is not writable. Please check file permissions.'), 403);
            }

            // Backup the original file
            $backupPath = $filePath . '.backup.' . date('Y-m-d_H-i-s');
            if (!copy($filePath, $backupPath)) {
                Log::warning('Failed to create backup', ['original' => $filePath, 'backup' => $backupPath]);
            }

            // Write the new content
            if (file_put_contents($filePath, $content) === false) {
                return response()->json(Reply::error('Failed to save template file'), 500);
            }

            // Clear view cache
            try {
                \Artisan::call('view:clear');
            } catch (\Exception $e) {
                Log::warning('Failed to clear view cache', ['error' => $e->getMessage()]);
            }

            return response()->json(Reply::success(__('messages.updateSuccess')));
        } catch (\Exception $e) {
            Log::error('Error in saveTemplateContent', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(Reply::error('Error saving template: ' . $e->getMessage()), 500);
        }
    }

    public function saveOfferLetterContent(Request $request)
    {
        try {
            $content = $request->get('offer_letter_content');
            
            $setting = RecruitSetting::where('company_id', company()->id)->first();
            
            if (!$setting) {
                $setting = new RecruitSetting();
                $setting->company_id = company()->id;
            }
            
            $setting->offer_letter_content = $content;
            
            // Handle background image upload
            if ($request->offer_letter_background_image_delete == 'yes') {
                Files::deleteFile($setting->offer_letter_background_image, 'offer-letter-background');
                $setting->offer_letter_background_image = null;
            } elseif ($request->hasFile('offer_letter_background_image')) {
                $oldImage = $setting->offer_letter_background_image;
                $setting->offer_letter_background_image = Files::uploadLocalOrS3($request->offer_letter_background_image, 'offer-letter-background');
                
                // Delete old image if exists
                if ($oldImage) {
                    Files::deleteFile($oldImage, 'offer-letter-background');
                }
            }
            
            $setting->save();
            
            return Reply::success(__('messages.updateSuccess'));
        } catch (\Exception $e) {
            Log::error('Error saving offer letter content: ' . $e->getMessage(), ['exception' => $e]);
            return Reply::error('Error saving offer letter content: ' . $e->getMessage());
        }
    }

}
