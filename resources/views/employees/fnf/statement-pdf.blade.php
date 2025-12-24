<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Full & Final Settlement Statement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #8bab4c;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #8bab4c;
            margin-bottom: 5px;
        }
        .document-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            background-color: #8bab4c;
            color: white;
            padding: 8px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
        }
        table td:first-child {
            width: 60%;
        }
        table td:last-child {
            width: 40%;
            text-align: right;
            font-weight: bold;
        }
        .total-row {
            background-color: #f5f5f5;
            font-weight: bold;
            border-top: 2px solid #333;
        }
        .net-payable {
            background-color: #8bab4c;
            color: white;
            font-size: 16px;
            padding: 10px;
            text-align: center;
        }
        .signature-section {
            margin-top: 50px;
        }
        .signature-box {
            display: inline-block;
            width: 45%;
            text-align: center;
            border-top: 1px solid #333;
            padding-top: 5px;
            margin-top: 60px;
        }
        .clearance-item {
            padding: 5px;
            margin-bottom: 5px;
            background-color: #f9f9f9;
        }
        .cleared {
            color: green;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">{{ company()->company_name }}</div>
        <div>{{ company()->address }}</div>
        <div class="document-title">FULL & FINAL SETTLEMENT STATEMENT</div>
    </div>

    <!-- Employee Details -->
    <div class="section">
        <div class="section-title">EMPLOYEE DETAILS</div>
        <table>
            <tr>
                <td>Employee Name:</td>
                <td>{{ $fnf->employee->name }}</td>
            </tr>
            <tr>
                <td>Employee ID:</td>
                <td>{{ $fnf->employee->employeeDetail->employee_id ?? '-' }}</td>
            </tr>
            <tr>
                <td>Department:</td>
                <td>{{ $fnf->employee->employeeDetail->designation->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Resignation Type:</td>
                <td>{{ ucfirst(str_replace('_', ' ', $fnf->resignation_type)) }}</td>
            </tr>
            <tr>
                <td>Resignation Date:</td>
                <td>{{ $fnf->resignation_date ? $fnf->resignation_date->format(company()->date_format) : '-' }}</td>
            </tr>
            <tr>
                <td>Last Working Day:</td>
                <td>{{ $fnf->last_working_day->format(company()->date_format) }}</td>
            </tr>
        </table>
    </div>

    <!-- Financial Breakdown - Earnings -->
    <div class="section">
        <div class="section-title">EARNINGS</div>
        <table>
            <tr>
                <td>Basic Salary ({{ $fnf->payable_days }} days of {{ $fnf->working_days }} working days)</td>
                <td>{{ currency_format($fnf->earned_salary, company()->currency_id) }}</td>
            </tr>
            <tr>
                <td>Leave Encashment ({{ $fnf->leave_balance_days }} days)</td>
                <td>{{ currency_format($fnf->leave_encashment_amount, company()->currency_id) }}</td>
            </tr>
            <tr>
                <td>Pending Bonus</td>
                <td>{{ currency_format($fnf->pending_bonus, company()->currency_id) }}</td>
            </tr>
            <tr>
                <td>Pending Incentives</td>
                <td>{{ currency_format($fnf->pending_incentives, company()->currency_id) }}</td>
            </tr>
            <tr class="total-row">
                <td>TOTAL EARNINGS:</td>
                <td style="color: green;">{{ currency_format($fnf->gross_amount, company()->currency_id) }}</td>
            </tr>
        </table>
    </div>

    <!-- Financial Breakdown - Deductions -->
    <div class="section">
        <div class="section-title">DEDUCTIONS</div>
        <table>
            <tr>
                <td>Loan Outstanding</td>
                <td>{{ currency_format($fnf->loan_outstanding, company()->currency_id) }}</td>
            </tr>
            <tr>
                <td>Advance Outstanding</td>
                <td>{{ currency_format($fnf->advance_outstanding, company()->currency_id) }}</td>
            </tr>
            <tr>
                <td>Notice Period Recovery</td>
                <td>{{ currency_format($fnf->notice_period_recovery, company()->currency_id) }}</td>
            </tr>
            <tr>
                <td>Other Deductions</td>
                <td>{{ currency_format($fnf->other_deductions, company()->currency_id) }}</td>
            </tr>
            <tr class="total-row">
                <td>TOTAL DEDUCTIONS:</td>
                <td style="color: red;">{{ currency_format($fnf->total_deductions, company()->currency_id) }}</td>
            </tr>
        </table>

        @if($fnf->deduction_remarks)
        <div style="background-color: #f9f9f9; padding: 10px; margin-top: 10px;">
            <strong>Deduction Remarks:</strong> {{ $fnf->deduction_remarks }}
        </div>
        @endif
    </div>

    <!-- Net Payable -->
    <div class="net-payable">
        <div>NET AMOUNT PAYABLE</div>
        <div style="font-size: 24px; font-weight: bold;">{{ currency_format($fnf->net_payable, company()->currency_id) }}</div>
    </div>

    <!-- Payment Details -->
    @if($fnf->payment_status == 'paid')
    <div class="section">
        <div class="section-title">PAYMENT DETAILS</div>
        <table>
            <tr>
                <td>Payment Date:</td>
                <td>{{ $fnf->payment_date->format(company()->date_format) }}</td>
            </tr>
            <tr>
                <td>Payment Mode:</td>
                <td>{{ $fnf->payment_mode }}</td>
            </tr>
            <tr>
                <td>Payment Reference:</td>
                <td>{{ $fnf->payment_reference ?? '-' }}</td>
            </tr>
        </table>
    </div>
    @endif

    <!-- Clearance Status -->
    <div class="section">
        <div class="section-title">CLEARANCE STATUS</div>
        @foreach($fnf->clearance_checklist as $department)
        <div class="clearance-item">
            <strong>
                @if($department['cleared'])
                    <span class="cleared">✓</span>
                @else
                    <span>✗</span>
                @endif
                {{ $department['department'] }}
            </strong>
            @if($department['cleared'])
                <div style="font-size: 10px; color: #666; margin-top: 3px;">
                    Cleared on {{ $department['cleared_date'] }}
                </div>
            @endif
        </div>
        @endforeach
    </div>

    <!-- Remarks -->
    @if($fnf->remarks)
    <div class="section">
        <div class="section-title">REMARKS</div>
        <div style="padding: 10px;">{{ $fnf->remarks }}</div>
    </div>
    @endif

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box" style="float: left;">
            Employee Signature
            <div style="margin-top: 5px;">{{ $fnf->employee->name }}</div>
        </div>
        <div class="signature-box" style="float: right;">
            Authorized Signatory
            <div style="margin-top: 5px;">HR Department</div>
        </div>
        <div style="clear: both;"></div>
    </div>

    <!-- Footer -->
    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
        <div>This is a computer-generated document and does not require a signature.</div>
        <div>Generated on: {{ now()->format('F d, Y h:i A') }}</div>
        <div>Document ID: FNF-{{ str_pad($fnf->id, 6, '0', STR_PAD_LEFT) }}</div>
    </div>
</body>
</html>

