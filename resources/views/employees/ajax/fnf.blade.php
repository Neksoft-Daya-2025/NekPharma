@php
$fnfSettlement = $employee->fnfSettlement;
@endphp

<div class="row mt-4">
    @if($fnfSettlement)
        <!-- FNF Exists - Show Details -->
        <div class="col-md-12">
            <div class="card border-0 b-shadow-4">
                <div class="card-header bg-white border-bottom-grey d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Full & Final Settlement Status</h5>
                    <span class="badge badge-{{ $fnfSettlement->status_color }} f-14 p-2">
                        {{ ucfirst(str_replace('_', ' ', $fnfSettlement->status)) }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-light border-0">
                                <div class="card-body text-center">
                                    <h4 class="text-success mb-0">{{ currency_format($fnfSettlement->gross_amount, company()->currency_id) }}</h4>
                                    <p class="f-12 text-muted mb-0">Gross Amount</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light border-0">
                                <div class="card-body text-center">
                                    <h4 class="text-danger mb-0">{{ currency_format($fnfSettlement->total_deductions, company()->currency_id) }}</h4>
                                    <p class="f-12 text-muted mb-0">Deductions</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success border-0">
                                <div class="card-body text-center">
                                    <h4 class="text-white mb-0">{{ currency_format($fnfSettlement->net_payable, company()->currency_id) }}</h4>
                                    <p class="f-12 text-white mb-0">Net Payable</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light border-0">
                                <div class="card-body text-center">
                                    <div class="progress mb-2" style="height: 25px;">
                                        <div class="progress-bar bg-success" style="width: {{ $fnfSettlement->clearance_progress }}%;">
                                            {{ $fnfSettlement->clearance_progress }}%
                                        </div>
                                    </div>
                                    <p class="f-12 text-muted mb-0">Clearance Progress</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-dark mb-3">Resignation Details</h6>
                            <table class="table table-borderless f-13">
                                <tr>
                                    <td class="w-50">Resignation Type:</td>
                                    <td><strong>{{ ucfirst(str_replace('_', ' ', $fnfSettlement->resignation_type)) }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Resignation Date:</td>
                                    <td><strong>{{ $fnfSettlement->resignation_date ? $fnfSettlement->resignation_date->format(company()->date_format) : '-' }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Last Working Day:</td>
                                    <td><strong>{{ $fnfSettlement->last_working_day->format(company()->date_format) }}</strong></td>
                                </tr>
                                <tr>
                                    <td>Payment Status:</td>
                                    <td>
                                        @if($fnfSettlement->payment_status == 'paid')
                                            <span class="badge badge-success"><i class="fa fa-check"></i> Paid</span>
                                        @elseif($fnfSettlement->payment_status == 'processed')
                                            <span class="badge badge-info">Processed</span>
                                        @else
                                            <span class="badge badge-warning">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6 text-right">
                            <a href="{{ route('fnf-settlements.show', $fnfSettlement->id) }}" class="btn btn-primary mb-2">
                                <i class="fa fa-eye"></i> View Full Details
                            </a>
                            <br>
                            <a href="{{ route('fnf-settlements.download-statement', $fnfSettlement->id) }}" class="btn btn-secondary">
                                <i class="fa fa-download"></i> Download Statement
                            </a>
                        </div>
                    </div>

                    @if($fnfSettlement->resignation_reason)
                    <div class="alert alert-info mt-3">
                        <strong>Resignation Reason:</strong> {{ $fnfSettlement->resignation_reason }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        <!-- FNF Doesn't Exist - Show Initiate Button -->
        <div class="col-md-12">
            <div class="card border-0 b-shadow-4">
                <div class="card-body text-center py-5">
                    <i class="fa fa-file-invoice-dollar fa-3x text-lightest mb-3"></i>
                    <h5 class="text-dark">No Full & Final Settlement Initiated</h5>
                    <p class="text-muted">
                        This employee has an exit date set ({{ $employee->employeeDetail->last_date->format(company()->date_format) }}).
                        <br>Initiate the Full & Final Settlement process to calculate final dues and manage clearances.
                    </p>
                    @if(user()->permission('add_employees') == 'all')
                    <a href="{{ route('fnf-settlements.create') }}?user_id={{ $employee->id }}" 
                       class="btn btn-primary openRightModal mt-3">
                        <i class="fa fa-plus"></i> Initiate FNF Settlement
                    </a>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

