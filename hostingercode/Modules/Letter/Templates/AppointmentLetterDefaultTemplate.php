<?php

namespace Modules\Letter\Templates;

/**
 * Default Appointment Letter HTML (aligned with NEW APL.docx; ##PLACEHOLDER## tokens).
 */
final class AppointmentLetterDefaultTemplate
{
    public static function html(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; font-size: 12pt; line-height: 1.45; color: #000;">
<p><strong>##EMPLOYEE_NAME_WITH_SALUTATION##</strong></p>
<p><strong>S/O</strong> ______________________________________</p>
<p>##EMPLOYEE_ADDRESS##</p>
<p>+91-##EMPLOYEE_MOBILE##</p>
<p>&nbsp;</p>
<p style="text-align: center;"><strong><span style="text-decoration: underline;">Appointment Letter</span></strong></p>
<p>&nbsp;</p>
<p>Dear ##EMPLOYEE_NAME_WITH_SALUTATION##,</p>
<p>&nbsp;</p>
<p>We take pleasure to inform you that you have been appointed for the position of <strong>##EMPLOYEE_DESIGNATION##</strong> in <strong>##EMPLOYEE_DEPARTMENT##</strong> for our company <strong>##COMPANY_NAME##</strong>. Your employment with us will be governed by the following terms and conditions as amended from time to time:</p>
<p>&nbsp;</p>
<p><strong>1. Commencement of employment:</strong> Your employment will be effective from <strong>##EMPLOYEE_JOINING_DATE_MDY##</strong> and you will be posted at our <strong>##EMPLOYEE_HQ_POSTING_LABEL##</strong>. Your reporting will be under company&rsquo;s <strong>##EMPLOYEE_REPORTING_TO##</strong> and who will define your job responsibilities.</p>
<p>&nbsp;</p>
<p><strong>2. Salary and Compensation:</strong> Your Gross Salary would be <strong>INR Rs. ##APPOINTMENT_GROSS_SALARY##/- per month</strong>, the break-up of the CTC is provided in Annexure &quot;A&quot; and shall remain same as provided in the intent letter of Appointment. Your future increments for promotion or any other salary increment shall be based on the merit considering your periodic and consistent overall performance, business conditions and other parameters fixed from time to time at the discretion of the management and shall not be considered merely as matter of right.</p>
<p>&nbsp;</p>
<p><strong>3. Probation Period:</strong> You will be on a probation period of six (06) months commencing from your date of joining. During this period, your performance, conduct, and suitability for the role will be evaluated.<br>
The company reserves the right to extend the probation period if deemed necessary. Confirmation of your employment will be communicated in writing upon successful completion of the probation period.<br>
In the event you wish to resign from your position during or after the probation period, you will be required to serve a notice period of one (01) month or salary in lieu of the notice period, subject to management approval.</p>
<p>&nbsp;</p>
<p style="text-align: right; margin-top: 100px;">(Signature of Employee)</p>
<p>&nbsp;</p>
<p style="text-align: right;">(Signature of Employee)</p>
<p>&nbsp;</p>
<p><strong>4. Posting / Transfer:</strong> You will be posted at <strong>##EMPLOYEE_POSTING_LOCATION_FULL##</strong>. You may however be required to work at any place of business which the management will decide from time to time. During the course of your employment, company reserves the right to transfer your service to any other location in India.</p>
<p>&nbsp;</p>
<p><strong>5. Training:</strong> As part of your role, you may be required to undergo training programs as determined by the Company. These training sessions are designed to enhance your skills and align your performance with organizational goals.<br>
Participation in such training is mandatory and forms an integral part of your employment. The Company reserves the right to assign, reschedule, or modify training requirements at its discretion. Any training costs borne by the Company may be subject to recovery if the employee resigns within a specified period, as per Company policy.</p>
<p>&nbsp;</p>
<p><strong>6. Moral duty against Company&rsquo;s infrastructure:</strong></p>
<ul>
<li>Please note that the accessories like pen drives, laptops / tablet and any other gadgets given to you to complete official task is one of your responsibilities. If you lose any of it, you will be the whole and sole responsible person for the loss &amp; payment in lieu thereof.</li>
<li>You are required to deal with the Company&rsquo;s money, materials and documents with utmost honesty. If at any time you are found of moral immorality or any dishonesty in dealing with the Company&rsquo;s money, materials and documents, you shall render yourself liable for termination without any notice or payment in lieu thereof.</li>
<li>If you are doing or planning for any kind of educational/training/development program, you must declare the same during your interview. Post joining if you plan for such program, then you must take written permission from Management. Management reserves the right to allow or not allow you for such program as per the interest of Company</li>
<li>You shall not refuse to take up any assignment that may be offered to you by the Company.</li>
<li>You shall be expected to abide by the rules and regulations of the Company, be courteous, honest and professional within the Company or with its clients/customers, and maintain &amp; represent the Company&rsquo;s high standards of professional services at all times, whether in the Company or at its client&rsquo;s site(s).</li>
</ul>
<p>&nbsp;</p>
<p><strong>7.</strong> You shall not be entitled to collect any cash or stocks either from the dealers or from the stockiest without written permission. In case, of any such happening, your services are liable to terminate with immediate effects.</p>
<p>&nbsp;</p>
<p><strong>8.</strong> You shall not enter into services or be engaged or be interested in any other concern directly, indirectly or in<br>
advisory capacity or engage yourself in other professional, vocational or occupational activities. You shall be responsible for the charge and care of the company&rsquo;s money, goods and other property that may be entrusted to you or that may at any time come to your hands or under your charge on account of the company and you shall truly and faithfully account for pay over or deliver the same to the concerned person.</p>
<p>&nbsp;</p>
<p style="text-align: right; margin-top: 100px;">(Signature of Employee)</p>
<p>&nbsp;</p>
<p><strong>9.</strong> You shall not take out patents for any invention made by you except with the prior written permission of the company. You shall not divulge or make known any manufacturing process, formulas, specifications, inventions or any secrets, accounts dealing of or relating to the company to any person other than the management of the company.</p>
<p>&nbsp;</p>
<p><strong>10.</strong> You shall be governed under service rules and regulations adopted by the company amended from time to time and shall obey all orders and directives that you may receive from your superiors. You shall scrupulously follow the instructions as mentioned in the various circulars issued by us or from your superiors.</p>
<p>&nbsp;</p>
<p><strong>11.</strong> You shall not be eligible for travelling allowances for joining duty or on termination of your services.</p>
<p>&nbsp;</p>
<p><strong>12.</strong> Your services shall be liable to be terminated under any of the following circumstances:</p>
<p>a) Absence for a continuous period of three days (including absence when leave though applied for but not granted) and when overstayed for period of eight consecutive days would make you to lose your lien on the service and the same shall automatically come to an end without any notice or even intimation. You will be liable to pay one month&rsquo;s salary in lieu of notice, which shall be deducted, from your salary or other dues.</p>
<p>b) If the company is satisfied on Medical Evidence that you are unfit and likely for considerable time to continue to be unfit by reasons of ill-health of Physical/Mental disability for the proper discharge of your duties. Provided always that the decision of the Company that you are likely to continue to be unfit shall be conclusively binding on you.</p>
<p>c) If you are found guilty of insubordination, intemperance, corrupt practices, any mis-conduct, breach of trust, noncompliance with the administrative orders of provisions of service rules, regulations and conditions.</p>
<p>d) If it is found that the declaration(s) or information furnished by you including that given for seeking employment are false and/or misleading and/or it is found that you have willingly suppressed any information to the company.<br>
If you are declared insolvent or convicted of any offence involving any moral turpitude or found guilty of fraud or misappropriation.</p>
<p>&nbsp;</p>
<p><strong>13.</strong> Your retirement age will be 58 years.</p>
<p>&nbsp;</p>
<p><strong>14. Holidays:</strong> You will be entitled to holidays as mentioned in the Organizational policy. <strong>##COMPANY_NAME##</strong> shall notify a list of declared holidays in the beginning of each year.</p>
<p>&nbsp;</p>
<p><strong>15. Leave Entitlement: -</strong></p>
<p><strong>A- Casual leave (CL):</strong> The company recognizes that there may be unforeseen and pressing exigencies (related to family or other personal reasons that an employee may need leave for, and for that, employees are eligible for CL with full pay for 10days in a calendar year. CL are credited into your leave balance periodically from your date of joining.<br>
Applicable to confirmed employees only. Casual Leave can be taken on monthly basis for a maximum of 2 consecutive days at a time.</p>
<p><strong>B- Earned leave (EL):</strong> You are entitled to get Earned leave which is 15 days leave in a calendar year. Grant of leave will depend on the exigencies of work and shall be at the discretion of the management. Before proceeding on leave, you will have to apply for leave at least before a week in the prescribed form to the appropriate authority and seek the prior sanction of leave. Similarly, for extension of leave an application you will have to inform in writing, well in advance, so as to reach positively before the expiry of leave originally granted. You will have to write your address during the period. Mere submission of an application will not mean that the leave has been sanctioned. Applicable to confirmed employees only.</p>
<p>&nbsp;</p>
<p style="text-align: right; margin-top: 100px;">(Signature of Employee)</p>
<p>&nbsp;</p>
<p><strong>C- Sick leave (SL):</strong> You are entitled to get Sick leaves for 12 days in a calendar year and can be availed from DOJ.<br>
Leaves can be grant according to the employee health exigencies. If leaves are required to be taken for more than 2 days (continuing the leaves) in a month then before proceeding on leave, you will have to apply through official mail communication, the submission of proper doctor prescription after resuming the services is must.</p>
<p>&nbsp;</p>
<p><strong>D- Maternity Leave (ML):</strong> Subject to the provisions of the Maternity Benefit Act 1961, female employees who have been in the continuous employment with the company for not less than 90 days prior to the date of commencement of Maternity Leave, will been entitled to Maternity Leave with full pay up to a maximum of 26 weeks in which not more than 8 weeks shall precede the date of the delivery, provided they do not undertake any gainful employment during the period of leave.</p>
<p>&nbsp;</p>
<p><strong>E- Leave without Pay (LWP):</strong> If there is no such kind of leave to credit, an employee can apply for LWP, in case of exigencies. All LWP requests need to be approved by the first line Manager. Leave shall be availed of with prior approval of the reporting head. However, in case of an emergency the employee must inform the concerned superior over telephone or by any other means. Holidays / Weekly offs falling in between LWP will be counted as an LWP. Leave without pay may be approved by the sanctioning authority, in the event of you having zero leave balance, depending upon the exigencies of work. For any kind of leave you supposed to get preauthorization one week prior to the stating of their leaves.</p>
<p>&nbsp;</p>
<p><strong>16. Report:</strong> You are required to send your daily reports through company application and allowances/ expenses shall be submitted by you at end of the month positively. You are responsible for business as desired by the company management and you should perform to achieve the given target with your team. You are required to send the performance report of each of your teammates weekly and also at the end of the month to your senior.</p>
<p>&nbsp;</p>
<p><strong>17. Termination:</strong><br>
In case the Company is not satisfied with your performance, conduct, or overall suitability for the role, the Company reserves the right to terminate your employment at any time during the probation period without prior notice or compensation in lieu of notice.<br>
After Probation, Either the Company or the Employee may terminate this employment by providing one (1) month&rsquo;s prior written notice or payment in lieu of such notice. The Company reserves the right to waive the notice period and relieve the Employee from duties immediately by paying the equivalent of one month&rsquo;s basic salary in lieu of notice. Similarly, the Employee may choose to pay an amount equivalent to one month&rsquo;s basic salary in lieu of serving the notice period.</p>
<p>&nbsp;</p>
<p><strong>18. Settlement:-</strong> In case of you leaving the job or termination of your services, your final accounts including terminal on retiral benefits will be settled only after you hand over the charge to us or to the person nominated by the company and deliver all documents, correspondence, information, notice, goods stores property, money and any other material supplied to you by <strong>##COMPANY_NAME##</strong> in a proper way suggested by us. Your full &amp; final dues will be settled within forty-five days from your last working day in the Company. Proper account of samples and other promotional materials including instructions for propaganda issued from time to time that may be in your custody or under your control during your employment in <strong>##COMPANY_NAME_SHORT##</strong> shall be handed over too. Failing this, the company shall have the right to withhold the payment of your final dues.</p>
<ul>
<li>In the case of employees working in sales functions, such as Medical Representatives or Area Business Managers (ABMs), it is mandatory to obtain a No Objection Certificate (NOC) from the designated stockist(s) confirming that there are no outstanding dues or unresolved issues. This NOC must be submitted along with other exit documentation for the settlement process to proceed.</li>
</ul>
<p>&nbsp;</p>
<p style="text-align: right; margin-top: 100px;">(Signature of Employee)</p>
<p>&nbsp;</p>
<p><strong>19. Code of conduct:</strong> This policy is in regards of your behavior in the Organization with your supervisor, managers and colleagues. Your employment may be governed by rules and regulations and code of ethics lay down by the Company.<br>
The Company may make such rules / lay out that be deemed necessary for the implementation/ administration of the terms and conditions of your employment as stated in this letter with due intimation to you and the same may be binding on you.</p>
<ul>
<li>In case of breach of this policy or any misbehavior with your superiors or colleagues then you may be asked to submit apology letter in written and even then, if you are not rectifying your behavior then we may discharge or terminate you from your services at any time without giving any prior notice to you.</li>
<li>You are a representative of the Company holding the values &amp; ethics of the Organization. You are expected to be extremely fair and impartial in all transactions and avoid the situation, which decreases the reputation of the Company.</li>
<li>Under this, you should wear formals and we will expect that you look presentable all the time and you must carry your ID card/Visiting card during your working hours.</li>
<li>In office hours, you will not speak harsh words or be abusing to anyone. If you have any problem with the behavior of your colleague or your supervisor then in this situation you are allowed to talk to your HR department regarding the same.</li>
<li>If you are found eating or drinking something like chewing gum, tobacco, alcohol etc. during hours working then we have a right to take strict action against you. It is your moral duty that if you find anyone doing such type of activities then in that case you should inform your seniors.</li>
<li>You should not discuss anything personal inside the Company. Passing any personal comments or religious or communal comment or any which is specified by Indian Laws on anyone or disrespecting your colleagues, supervisor and manager will not be tolerated. In such case if you will be found guilty with proof then you may be penalized.</li>
<li>We do not promote any kind of promotion or any such act which is purely or partially based on Religion or Community during office hours.</li>
<li>You must be accessible over mail or other source of communication even if you are on leave. In case if you are busy or unavailable on phone then you have to give genuine reason for this, which should be acceptable by the Company. In any circumstance if you are not able to answer the call, you are supposed to call back at your earliest available time within twenty-four hours.</li>
</ul>
<p>&nbsp;</p>
<p><strong>20. Governing Law/Jurisdiction:</strong> Your employment with the Company is subject to Indian laws. All disputes shall be subject to the jurisdiction of Lucknow, Uttar Pradesh only.</p>
<p>&nbsp;</p>
<p>In case the terms and conditions are acceptable to you, please sign the duplicate copy of this letter in token of your having understood and accepted.</p>
<p>&nbsp;</p>
<p>Thanking You,<br>
For <strong>##COMPANY_NAME##</strong></p>
<p>&nbsp;</p>
<table width="100%" style="margin-top: 48px; border-collapse: collapse;">
<tbody>
<tr>
<td style="width: 50%; vertical-align: top;"><strong>Authorized Signatory</strong></td>
<td style="width: 50%; text-align: right; vertical-align: top;">(Signature of Employee)</td>
</tr>
<tr>
<td style="padding-top: 40px; vertical-align: top;"><strong>HR Manager</strong></td>
<td style="padding-top: 40px; text-align: right; vertical-align: top;">&nbsp;</td>
</tr>
</tbody>
</table>
<p>&nbsp;</p>
<p><strong>Acceptance</strong></p>
<p>I, ##EMPLOYEE_NAME_WITH_SALUTATION## S/O ______________________________________ confirm that I have read the all terms of employment set out in this appointment letter and attached salary Annexure and accept the employment. I confirm that by signing this Appointment letter, I agree to be bound by the terms of the same. I declare that I shall be abiding by all terms and conditions, code of conduct and rules framed time to time.</p>
<p>&nbsp;</p>
<p>##EMPLOYEE_HEADQUARTER##<br>
Date:- ##CURRENT_DATE##</p>
<p style="text-align: right; margin-top: 50px;">(Signature of Employee)</p>
<p>&nbsp;</p>
<p><strong>Annexure</strong></p>
<p><strong>Salary Structure</strong></p>
<table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 100%; max-width: 640px;">
<tbody>
<tr>
<th style="text-align: left;">Particular</th>
<th style="text-align: right;">Monthly</th>
<th style="text-align: right;">Annually</th>
</tr>
<tr>
<td>Basic</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>
<tr>
<td>HRA</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>
<tr>
<td>Special Allowance</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>
<tr>
<td>Gross Salary</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>
<tr>
<td>Deduction</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>
<tr>
<td>Net Salary</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>
</tbody>
</table>
</div>
HTML;
    }
}
