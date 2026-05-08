<?php

namespace Modules\Recruit\Notifications;

use App\Notifications\BaseNotification;
use Modules\Recruit\Entities\RecruitJobOfferLetter;
use Modules\Recruit\Entities\RecruitSalaryStructure;
use Modules\Recruit\Entities\RecruitSelectedSalaryComponent;
use Modules\Recruit\Http\Controllers\JobOfferLetterController;
use App\Models\Currency;
use App\Models\Company;
use App\Models\GlobalSetting;

class SendOfferLetter extends BaseNotification
{
    private $offer;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(RecruitJobOfferLetter $offer)
    {
        $this->offer = $offer;
        $this->company = $this->offer->job->company;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $via = [];

        if ($notifiable->email) {
            array_push($via, 'mail');
        }

        return $via;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = route('front.jobOffer', $this->offer->hash, $this->company);
        $url = getDomainSpecificUrl($url, $this->company);

        $emailContent = parent::build()
            ->subject(__('recruit::modules.email.jobOffer'))
            ->greeting(__('email.hello').' '.$notifiable->full_name.'!')
            ->action(__('recruit::app.jobOffer.viewoffer'), $url);

        // Generate and attach PDF
        try {
            $pdfData = $this->generateOfferLetterPdf();
            if ($pdfData) {
                $filename = 'Offer-Letter-' . $this->offer->jobApplication->full_name . '.pdf';
                $emailContent->attachData($pdfData['pdf']->output(), $filename, [
                    'mime' => 'application/pdf',
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error generating PDF for offer letter email: ' . $e->getMessage());
            // Continue without PDF attachment if generation fails
        }

        return $emailContent->line(__('recruit::modules.email.thankyouNote'));
    }

    /**
     * Generate PDF for offer letter
     *
     * @return array|null
     */
    private function generateOfferLetterPdf()
    {
        try {
            // Load required data
            $jobOffer = RecruitJobOfferLetter::with(['files', 'job', 'jobApplication'])->findOrFail($this->offer->id);
            $currency = Currency::where('id', '=', $jobOffer->job->currency_id)->first();
            $company = $this->company;
            
            $salaryStructure = RecruitSalaryStructure::where('recruit_job_offer_letter_id', $jobOffer->id)->first() ?? null;
            $selectedEarningsComponent = null;
            $selectedDeductionsComponent = null;
            $fixedAllowance = null;
            $grossSalary = null;
            $totalDeduction = null;
            $netSalary = null;

            if (!is_null($salaryStructure)) {
                $selectedEarningsComponent = RecruitSelectedSalaryComponent::where('rss_id', $salaryStructure->id)->where('component_type', 'earning')->get();
                $selectedDeductionsComponent = RecruitSelectedSalaryComponent::where('rss_id', $salaryStructure->id)->where('component_type', 'deduction')->get();
                $earn = JobOfferLetterController::totalEarnings($salaryStructure);
                $deduction = JobOfferLetterController::totalDeductions($salaryStructure);
                $total = $salaryStructure->basic_salary + $earn;
                $fixedAllowance = $salaryStructure->amount - $total;
                $grossSalary = $fixedAllowance + $total;
                $totalDeduction = $deduction;
                $netSalary = $grossSalary - ($totalDeduction);
            }

            // Prepare data for PDF view
            $data = [
                'jobOffer' => $jobOffer,
                'currency' => $currency,
                'company' => $company,
                'settings' => global_setting(),
                'global' => global_setting(),
                'salaryStructure' => $salaryStructure,
                'selectedEarningsComponent' => $selectedEarningsComponent,
                'selectedDeductionsComponent' => $selectedDeductionsComponent,
                'fixedAllowance' => $fixedAllowance,
                'grossSalary' => $grossSalary,
                'totalDeduction' => $totalDeduction,
                'netSalary' => $netSalary,
            ];

            // Generate PDF
            $pdf = app('dompdf.wrapper');
            $pdf->setOption('enable_php', true);
            $pdf->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
            
            $pdf->loadView('recruit::jobs.offer-letter.offer-letter-pdf', $data);

            $dom_pdf = $pdf->getDomPDF();
            $canvas = $dom_pdf->get_canvas();
            $canvas->page_text(530, 820, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 10, array(0, 0, 0));

            return ['pdf' => $pdf];
        } catch (\Exception $e) {
            \Log::error('Error in generateOfferLetterPdf: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray()
    {
        return [
            'data' => $this->offer->toArray(),
        ];
    }
}
