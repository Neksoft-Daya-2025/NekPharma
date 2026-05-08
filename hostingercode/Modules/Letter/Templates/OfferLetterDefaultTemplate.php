<?php

namespace Modules\Letter\Templates;

final class OfferLetterDefaultTemplate
{
    /**
     * Default Offer Letter HTML (aligned with “New format of offer letter” Word layout; ##PLACEHOLDER## tokens).
     */
    public static function html(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; font-size: 12pt; line-height: 1.45; color: #000;">
<table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
<tbody>
<tr>
<td style="text-align: right;"><strong>Date:</strong> &ndash; ##CURRENT_DATE_SLASH##</td>
</tr>
</tbody>
</table>
<p style="margin: 0 0 8px 0;"><strong>##EMPLOYEE_NAME_WITH_SALUTATION##</strong></p>
<p style="margin: 0 0 8px 0;"><strong>S/O</strong> _________________________________</p>
<p>&nbsp;</p>
<p style="text-align: center; margin: 12px 0;"><strong><span style="text-decoration: underline;">OFFER LETTER</span></strong></p>
<p>&nbsp;</p>
<p style="margin: 0 0 8px 0;">Dear ##EMPLOYEE_NAME_WITH_SALUTATION##,</p>
<p>&nbsp;</p>
<p style="margin: 0 0 8px 0;">As per your application &amp; subsequent interview, we are pleased to offer you the position of <strong>##EMPLOYEE_DESIGNATION##</strong> at <strong>##EMPLOYEE_HEADQUARTER##</strong>, <strong>##EMPLOYEE_AREA##</strong>, effective <strong>##EMPLOYEE_JOINING_DATE_DMY##</strong>. You will report to <strong>##EMPLOYEE_REPORTING_TO##</strong> of the company.</p>
<p>&nbsp;</p>
<p style="margin: 0 0 8px 0;">Your annual package will be <strong>₹##OFFER_ANNUAL_PACKAGE##</strong>, subject to applicable taxes and deductions. The bifurcation of the same will be shown in appointment letter. During the probation period, your performance will be continuously reviewed. In case the Company is not satisfied with your performance, conduct, or overall suitability for the role, the Company reserves the right to terminate your employment at any time during the probation period without prior notice or compensation in lieu of notice.</p>
<p>&nbsp;</p>
<p style="margin: 0 0 8px 0;">In the event you wish to resign from your position during or after the probation period, you will be required to serve a notice period of one (01) month or salary in lieu of the notice period, subject to management approval.</p>
<p>&nbsp;</p>
<p style="margin: 0 0 8px 0;">This is a <strong>Full-Time</strong> role with regular working hours of <strong>Monday &ndash; Saturday, 10:00 AM&ndash;18:30 PM</strong>, with flexibility required depending on project demands.</p>
<p>&nbsp;</p>
<p style="margin: 0 0 8px 0;">Please note that this offer is subject to document verification, and we require all necessary documents to be submitted on or before your date of joining.</p>
<p>&nbsp;</p>
<p style="margin: 0 0 8px 0;">If you accept this offer, please confirm by signing and returning one copy of this letter to us. We look forward to having you join our team!</p>
<p>&nbsp;</p>
<p style="margin: 0 0 8px 0;"><strong>Note:-</strong> This letter is valid for <strong>03 days</strong> from the date of issue. After that this offer shall be considered as cancelled.</p>
<p>&nbsp;</p>
<p style="margin: 0 0 8px 0;">Thanking You,</p>
<p style="margin: 0 0 8px 0;">For <strong>##COMPANY_NAME##</strong></p>
<p>&nbsp;</p>
<table style="width: 100%; border-collapse: collapse; margin-top: 28px; font-size: 11pt;">
<tbody>
<tr>
<td style="width: 33%; text-align: center; vertical-align: top;">Full Signature &amp; Date</td>
<td style="width: 34%; text-align: center; vertical-align: top;"><strong>Authorized Signatory</strong><br>##SIGNATORY##<br>##SIGNATORY_DESIGNATION##<br><span style="font-size: 10pt;">##SIGNATORY_EMAIL##</span></td>
<td style="width: 33%; text-align: center; vertical-align: top;">(As a token of acceptance)</td>
</tr>
</tbody>
</table>
</div>
HTML;
    }
}
