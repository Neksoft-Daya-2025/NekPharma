<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">
        {{ __('Purchase Entry Payment Management') }}
    </h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">×</span>
    </button>
</div>
<div class="modal-body">
    <div class="portlet-body">
        <!-- Invoice Summary -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card bg-light">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-md-3">
                                <small class="text-muted">Invoice Number</small>
                                <p class="mb-0 font-weight-bold">{{ $invoiceNumber }}</p>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Total Amount</small>
                                <p class="mb-0 font-weight-bold">{{ currency_format($totalAmount, company()->currency_id) }}</p>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Paid Amount</small>
                                <p class="mb-0 font-weight-bold text-success">{{ currency_format($paidAmount, company()->currency_id) }}</p>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Due Amount</small>
                                <p class="mb-0 font-weight-bold text-danger">{{ currency_format($dueAmount, company()->currency_id) }}</p>
                            </div>
                        </div>
                        @if(isset($vendor) && $vendor)
                        <div class="row mt-2">
                            <div class="col-md-12">
                                <small class="text-muted">{{ __('purchase::app.vendor') }}</small>
                                <p class="mb-0">{{ $vendor->primary_name ?? $vendor->company_name ?? '--' }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Update Payment Form -->
        @if($paymentStatus != 'paid' && $dueAmount > 0)
        <div class="row mb-3">
            <div class="col-md-12">
                <h6 class="mb-3">Record Payment</h6>
            </div>
        </div>

        <x-form id="payment-status-form" method="POST" class="ajax-form">
            <input type="hidden" name="invoice_number" value="{{ $invoiceNumber }}">
            <input type="hidden" name="current_status" value="{{ $paymentStatus }}">
            
            <div class="form-body">
                <div class="row">
                    <!-- Payment Mode -->
                    <div class="col-lg-12 col-md-12 mb-3">
                        <x-forms.select 
                            class="select-picker" 
                            fieldId="payment_mode" 
                            :fieldLabel="__('Mode of Payment')"
                            fieldName="payment_mode" 
                            search="true"
                            fieldRequired="true">
                            <option value="">-- Select Payment Mode --</option>
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="online">Online Payment</option>
                            <option value="upi">UPI</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="debit_card">Debit Card</option>
                            <option value="other">Other</option>
                        </x-forms.select>
                    </div>

                    <!-- Payment Date -->
                    <div class="col-lg-12 col-md-12 mb-3">
                        <x-forms.datepicker 
                            fieldId="payment_date" 
                            :fieldLabel="__('Payment Date')" 
                            fieldName="payment_date"
                            :fieldValue="now()->format('Y-m-d')"
                            fieldRequired="true"
                            :fieldPlaceholder="__('Select payment date')" 
                        />
                    </div>

                    <!-- Payment Amount -->
                    <div class="col-lg-12 col-md-12 mb-3">
                        <x-forms.number 
                            fieldId="payment_amount" 
                            :fieldLabel="__('Payment Amount')" 
                            fieldName="payment_amount"
                            :fieldValue="$dueAmount"
                            :fieldPlaceholder="__('Enter payment amount')"
                            fieldRequired="true"
                            fieldHelp="Remaining due: {{ currency_format($dueAmount, company()->currency_id) }}"
                        />
                    </div>

                    <!-- Payment Reference/Transaction ID (optional) -->
                    <div class="col-lg-12 col-md-12 mb-3">
                        <x-forms.text 
                            fieldId="payment_reference" 
                            :fieldLabel="__('Payment Reference / Transaction ID')" 
                            fieldName="payment_reference"
                            :fieldPlaceholder="__('Enter reference number (optional)')"
                        />
                    </div>

                    <!-- Payment Notes (optional) -->
                    <div class="col-lg-12 col-md-12 mb-3">
                        <x-forms.textarea 
                            fieldId="payment_notes" 
                            :fieldLabel="__('Payment Notes')" 
                            fieldName="payment_notes"
                            :fieldPlaceholder="__('Any additional notes (optional)')"
                            fieldRows="3"
                        />
                    </div>
                </div>
            </div>
        </x-form>
        @else
        <div class="alert alert-success mt-3">
            <i class="fa fa-check-circle"></i> 
            This purchase entry invoice is fully paid.
        </div>
        @endif
    </div>
</div>

<div class="modal-footer">
    @if($paymentStatus != 'paid' && $dueAmount > 0)
    <x-forms.button-cancel data-dismiss="modal" class="border-0">@lang('app.cancel')</x-forms.button-cancel>
    <x-forms.button-primary id="save-payment-btn" icon="check">@lang('app.save')</x-forms.button-primary>
    @else
    <x-forms.button-cancel data-dismiss="modal" class="border-0">@lang('app.close')</x-forms.button-cancel>
    @endif
</div>

<script>
    $(document).ready(function() {
        // Initialize select picker
        $('.select-picker').selectpicker();
        
        // Initialize datepicker
        $('#payment_date').datepicker({
            format: '{{ company()->date_picker_format }}',
            autoclose: true
        });
        
        // Handle form submission
        $('#save-payment-btn').click(function() {
            var url = "{{ route('purchase-entries.update-payment-status') }}";
            var formData = $('#payment-status-form').serialize();
            
            $.easyAjax({
                url: url,
                type: "POST",
                data: formData,
                container: '#payment-status-form',
                blockUI: true,
                messagePosition: "inline",
                success: function(response) {
                    if (response.status == "success") {
                        var modalLg = typeof MODAL_LG !== 'undefined' ? MODAL_LG : (window.MODAL_LG || '#myModal');
                        $(modalLg).modal('hide');
                        
                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: response.message || 'Payment recorded successfully',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        // Reload the DataTable
                        if (typeof window.LaravelDataTables !== 'undefined' && window.LaravelDataTables['products-table']) {
                            window.LaravelDataTables['products-table'].draw();
                        } else {
                            // Fallback: reload page
                            setTimeout(function() {
                                window.location.reload();
                            }, 2000);
                        }
                    }
                },
                error: function(response) {
                    // Handle validation errors
                    if (response.status == 422) {
                        var errors = response.responseJSON.errors;
                        var errorMessages = [];
                        $.each(errors, function(key, value) {
                            errorMessages.push(value[0]);
                        });
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            html: errorMessages.join('<br>'),
                            showConfirmButton: true
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.responseJSON.message || 'An error occurred while recording payment',
                            showConfirmButton: true
                        });
                    }
                }
            });
        });
    });
</script>
