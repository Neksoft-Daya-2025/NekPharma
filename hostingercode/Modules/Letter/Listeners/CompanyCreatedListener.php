<?php

namespace Modules\Letter\Listeners;

use Modules\Letter\Entities\LetterSetting;
use Modules\Letter\Entities\Template;
use Modules\Letter\Templates\OfferLetterDefaultTemplate;
use Modules\Letter\Templates\AppointmentLetterDefaultTemplate;

class CompanyCreatedListener
{

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $company = $event->company;
        LetterSetting::addModuleSetting($company);
        
        // Ensure LetterSetting exists for the company
        LetterSetting::firstOrCreate(['company_id' => $company->id]);

        $this->addLetters($company);
    }

    public function addLetters($company): void
    {

        $incrementLetter = <<<HOD
<span style="font-size: 12px;"><b>##EMPLOYEE_NAME##</b></span><br>
Employee ID:&nbsp;<span style="font-size: 12px;"><b>##EMPLOYEE_ID##</b></span><br>
Designation:&nbsp;<span style="font-size: 12px;"><b>##EMPLOYEE_DESIGNATION##</b></span><br>
Date: <b><u>XXXX</u></b><br>
<br>
Subject: Salary increment <br>
<br>
Dear&nbsp;<span style="font-size: 12px;">&nbsp;##EMPLOYEE_NAME##</span>,<br>
<br>
Congratulations!<br>
We would like to gladly inform you that your salary will be increased by&nbsp;<b><u>XXXX</u></b> starting <b><u>XXXX</u></b> and your new salary will be <b><u>XXXX</u></b> This increase is the result your continuous contribution to the success of this company. We recognize your efforts and would like to reward you for that. <br>
<br>
We also hope this will encourage you to perform even better. There is always room for improvement. <br>
Keep up the good work. <br>
<br>
<br>
__________________________<br><span style="font-size: 12px;">##SIGNATORY##,<br></span><span style="font-size: 12px;">##SIGNATORY_DESIGNATION##</span>&nbsp;–&nbsp;<span style="font-size: 12px;">##SIGNATORY_DEPARTMENT##</span><br><span style="font-size: 12px;">##COMPANY_NAME##</span><br>


HOD;

        $offerLetter = OfferLetterDefaultTemplate::html();


        $joiningLetter = <<<HOD

                                       <p>                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br><div style="text-align: center;">&nbsp;<b>Joining Letter</b></div>
<br>
<br><span style="font-size: 12px;"><b>##EMPLOYEE_NAME##</b></span><br><span style="font-size: 12px;"><b>##EMPLOYEE_ADDRESS##</b></span><br>
<br>
Date:&nbsp;<span style="font-size: 12px;"><b>##EMPLOYEE_JOINING_DATE##</b></span><br>
<br>
Dear&nbsp;<b style="font-size: 12px;">##EMPLOYEE_NAME##</b>,<br>
<br>
Thank you for joining ABC Pvt. Ltd. on<b>&nbsp;<span style="font-size: 12px;">##EMPLOYEE_JOINING_DATE##</span>&nbsp;</b>and accepting the position of<b>&nbsp;<span style="font-size: 12px;">##EMPLOYEE_DESIGNATION##</span></b>. We are pleased to have you on our team. This letter acknowledges that you have completed all the formalities for joining ##COMPANY_NAME## and have accepted the terms of job as described below:<br>
<br>
Monthly Compensation: XXXX INR<br><br>
You are expected to abide by the Company’s policies, ethics and principles during your employment. Looking forward to a healthy and productive employment relationship with you.<br>
<br>
<br>
<br>
_____________________________<br><span style="font-size: 12px;">##SIGNATORY##</span><br><span style="font-size: 12px;">##SIGNATORY_DESIGNATION##</span>&nbsp;–&nbsp;<span style="font-size: 12px;">##SIGNATORY_DEPARTMENT##</span><br><span style="font-size: 12px;">##COMPANY_NAME##</span><br>
</p>

HOD;

        $acceptanceLetter = <<<HOD
<p><br></p><p><div style="text-align: center;"><b>Acceptance Letter</b></div>
<br>
<br><span style="font-size: 12px;">##EMPLOYEE_JOINING_DATE##</span><br><span style="font-size: 12px;">##SIGNATORY_DESIGNATION##</span>,<br><span style="font-size: 12px;">##SIGNATORY_DESIGNATION## , ##SIGNATORY_DEPARTMENT##</span>,<br><br><span style="font-size: 12px;">##COMPANY_NAME##,</span><br><span style="font-size: 12px;">##CONTACT_ADDRESS##</span><br>
<br>
Subject: Acceptance Letter<br>
<br>
Dear&nbsp;<span style="font-size: 12px;">##SIGNATORY## </span>,<br>
<br>
I am pleased to accept your offer and I would like to inform you that I am joining the company from&nbsp;<span style="font-size: 12px;">##EMPLOYEE_JOINING_DATE##</span>&nbsp;as a<span style="font-size: 12px;">##EMPLOYEE_DESIGNATION## i</span>n respect to your appointment letter dated XXOFFERLETTERDATEXXX As we discussed, my starting salary will be 9,000 per month. I understand and accept the conditions of employment that you explained in your appointment letter.<br>
<br>
The position is ideally suited to my educational background and interests. I confidently feel that I can make a significant contribution to your company, and I am grateful for the opportunity you have given me. I humbly request you to accept my acceptance letter.<br>
<br>
Regards,<br>
<br>
<br><span style="font-size: 12px;">##EMPLOYEE_NAME##&nbsp;</span><br>
<br>
</p>
HOD;

        $welcomeLetter = <<<HOD
<p><br></p><p><div style="text-align: center;"><b>Welcome Letter</b></div>
<br>
<br><span style="font-size: 12px;">##EMPLOYEE_NAME##</span><br><span style="font-size: 12px;">##EMPLOYEE_ADDRESS##</span><br>
<br>
<br>
Date:&nbsp;<span style="font-size: 12px;"><b>##EMPLOYEE_JOINING_DATE##</b></span><br>
<br>
Dear&nbsp;<span style="font-size: 12px;">##EMPLOYEE_NAME##</span>, <br>
<br>
We are extremely glad and happy as a corporation to welcome you to our company&nbsp;<span style="font-size: 12px;">&nbsp;<b>##COMPANY_NAME##</b></span>&nbsp;from the&nbsp;<span style="font-size: 12px;"><b>##EMPLOYEE_JOINING_DATE##</b>&nbsp;</span>onwards.<br>
You shall be filling the position with Software Developer in the technical department, and we wish to inform you that this is one of the most crucial job positions in our company. We are looking forward to see you prove us right in the decision to hire you over the other great applicants as far as qualifications and experience is concerned.<br>
<br>
Our company shall welcome you and I am sure you would be able to fit in comfortably. We hope you realize your duties and help us grow further as far as the success and development is concerned.<br>
Welcome once again!<br>
<br>
<br>
_____________________________<br><span style="font-size: 12px;">##SIGNATORY##</span><br><span style="font-size: 12px;">##SIGNATORY_DESIGNATION## - ##SIGNATORY_DEPARTMENT##&nbsp;</span><br><span style="font-size: 12px;">&nbsp;##COMPANY_NAME##</span><br>
<br>
</p>

HOD;

        $relievingLetter = <<<HOD

<br>
<br>
<br>
<br>
Date:&nbsp;<span style="font-size: 12px;">##EMPLOYEE_EXIT_DATE##</span><br><div style="text-align: center;"><b>Relieving Letter &amp; Experience Certificate&nbsp;</b></div><div style="text-align: center;"><b><br></b></div>
Employee Name:&nbsp;<span style="font-size: 12px;">##EMPLOYEE_NAME##</span><br>
Employee Id:&nbsp;&nbsp;<span style="font-size: 12px;">##EMPLOYEE_ID##</span><br>
Designation:&nbsp;<span style="font-size: 12px;">##EMPLOYEE_DESIGNATION##</span>&nbsp;<br>
<br>
<br>
Dear&nbsp;<span style="font-size: 12px;">##EMPLOYEE_NAME##</span><br>
<br>
This is in reference to our discussion on <b><u>XXXTERMINATIONLETTERDATEXXX</u></b><br>
You will be relived from your duties at the end of our official working hours on&nbsp;<span style="font-size: 12px;"><b>##EMPLOYEE_EXIT_DATE##.</b></span><br>
We wish to express our sincere appreciation for your dedication &amp; hard work for the company during the period of your association from 15-Jan-2018 to 30-June-2018 with your last designation as Software Developer. <br>
We wish you good luck for all your future endeavours.<br>
<br>
Regards,<br><br><span style="font-size: 12px;">##SIGNATORY##</span><br><span style="font-size: 12px;">##SIGNATORY_DESIGNATION##&nbsp; - ##SIGNATORY_DEPARTMENT##</span><br>
<span style="font-size: 12px;">##COMPANY_NAME##</span><br>


HOD;

        $attendanceLetter = <<<HOD

<br><div style="text-align: center;"><b>Excellent Attendance Letter</b></div>
<br>
<br>
<br><span style="font-size: 12px;"><b>##EMPLOYEE_NAME##</b></span><br>
Employee ID:&nbsp;<span style="font-size: 12px;"><b>##EMPLOYEE_ID##</b></span><br>
Position:&nbsp;<span style="font-size: 12px;"><b>##EMPLOYEE_DESIGNATION##</b></span><br>
<br>
Date: XXXXCURRENT_DATEXXX<br>
<br>
<br>
Dear&nbsp;<span style="font-size: 12px;">##EMPLOYEE_NAME##</span>,	<br>
<br>
Thank you for your excellent attendance at work during the last 3 months. It is an honor and privilege to work with such a committed employee. The effort you put into your job is more than what is required and ##COMPANY_NAME## team is going to recognize you. You will receive a bonus as recognition for a well job done.  <br>
Thanks again for all that you do for our firm and keep up the great work.<br>
<br>
<br>
 <br>
<br>
_____________________________<br><span style="font-size: 12px;">##SIGNATORY##</span><br><span style="font-size: 12px;">##SIGNATORY_DESIGNATION##</span><br><span style="font-size: 12px;">##COMPANY_NAME##</span><br>
<br>



HOD;

        $appointmentLetter = AppointmentLetterDefaultTemplate::html();


        $letters = [
            [
                'title' => 'Joining Letter',
                'template' => $joiningLetter
            ],
            [
                'title' => 'Offer Letter',
                'template' => $offerLetter
            ],
            [
                'title' => 'Appointment Letter',
                'template' => $appointmentLetter
            ],
            [
                'title' => 'Increment Letter',
                'template' => $incrementLetter
            ],
            [
                'title' => 'Acceptance Letter',
                'template' => $acceptanceLetter
            ],
            [
                'title' => 'Welcome Letter',
                'template' => $welcomeLetter
            ],
            [
                'title' => 'Relieving and Experience Letter',
                'template' => $relievingLetter
            ],
            [
                'title' => 'Excellent Attendance Letter',
                'template' => $attendanceLetter
            ]

        ];

        foreach ($letters as $letter) {

            $let = Template::where('company_id', $company->id)
                ->where('title', $letter['title'])
                ->first();

            if ($let) {
                $let->company_id = $company->id;
                $let->title = $letter['title'];

                $let->description = $letter['template'];
                $let->save();
                continue;
            }

            $let = new Template();
            $let->company_id = $company->id;
            $let->title = $letter['title'];

            $let->description = $letter['template'];
            $let->save();
        }

    }

}
