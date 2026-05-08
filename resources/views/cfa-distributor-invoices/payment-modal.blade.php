@php
    $paymentRoutePrefix = $paymentRoutePrefix ?? 'cfa-distributor-invoices';
@endphp

<div class="modal-header">
    <h5 class="modal-title" id="modelHeading">
        {{ __('Invoice Payment Management') }}
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
                                <p class="mb-0 font-weight-bold">{{ $invoice->invoice_number }}</p>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Total Amount</small>
                                <p class="mb-0 font-weight-bold">{{ $invoice->currency->currency_symbol ?? '' }} {{ number_format($invoice->total, 2) }}</p>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Paid Amount</small>
                                <p class="mb-0 font-weight-bold text-success">{{ $invoice->currency->currency_symbol ?? '' }} {{ number_format($invoice->getPaidAmount(), 2) }}</p>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">Due Amount</small>
                                <p class="mb-0 font-weight-bold text-danger">{{ $invoice->currency->currency_symbol ?? '' }} {{ number_format($invoice->amountDue(), 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        @if($invoice->payment && $invoice->payment->count() > 0)
        <div class="row mb-4">
            <div class="col-md-12">
                <h6 class="mb-3">Payment History</h6>
                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                    <table class="table table-bordered table-sm">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Mode</th>
                                <th>Amount</th>
                                <th>Reference</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th width="80">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->payment as $index => $payment)
                            <tr id="payment-row-{{ $payment->id }}">
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $payment->paid_on ? $payment->paid_on->format(company()->date_format) : '-' }}</td>
                                <td><span class="badge badge-info">{{ ucfirst($payment->gateway ?? '-') }}</span></td>
                                <td class="font-weight-bold">{{ $invoice->currency->currency_symbol ?? '' }} {{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->transaction_id ?? '-' }}</td>
                                <td>
                                    @if($payment->status == 'complete')
                                        <span class="badge badge-success">Complete</span>
                                    @elseif($payment->status == 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @else
                                        <span class="badge badge-secondary">{{ ucfirst($payment->status) }}</span>
                                    @endif
                                </td>
                                <td><small>{{ $payment->remarks ?? '-' }}</small></td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary btn-sm edit-payment" 
                                                data-payment-id="{{ $payment->id }}"
                                                data-payment-date="{{ $payment->paid_on ? $payment->paid_on->format('Y-m-d') : '' }}"
                                                data-payment-mode="{{ $payment->gateway }}"
                                                data-payment-amount="{{ $payment->amount }}"
                                                data-payment-reference="{{ $payment->transaction_id }}"
                                                data-payment-notes="{{ $payment->remarks }}"
                                                title="Edit Payment">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm delete-payment" 
                                                data-payment-id="{{ $payment->id }}"
                                                data-payment-amount="{{ $payment->amount }}"
                                                title="Delete Payment">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Add New Payment (only if not fully paid) -->
        @if($invoice->status != 'paid' && $invoice->amountDue() > 0)
        <div class="row mb-3">
            <div class="col-md-12">
                <h6 class="mb-3">
                    @if($invoice->payment && $invoice->payment->count() > 0)
                        Add New Payment
                    @else
                        Record Payment
                    @endif
                </h6>
            </div>
        </div>

        <x-form id="payment-status-form" method="POST" class="ajax-form">
            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
            <input type="hidden" name="current_status" value="{{ $invoice->status }}">
            
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

                    <!-- Payment Amount (optional - for partial payments) -->
                    <div class="col-lg-12 col-md-12 mb-3">
                        <x-forms.number 
                            fieldId="payment_amount" 
                            :fieldLabel="__('Payment Amount')" 
                            fieldName="payment_amount"
                            :fieldValue="$invoice->amountDue()"
                            :fieldPlaceholder="__('Enter payment amount')"
                            fieldRequired="true"
                            fieldHelp="Remaining due: {{ $invoice->currency->currency_symbol ?? '' }}{{ number_format($invoice->amountDue(), 2) }}"
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
            <strong>Invoice Fully Paid!</strong> This invoice has been paid in full. No additional payments can be added.
        </div>
        @endif
    </div>
</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0">@lang('app.close')</x-forms.button-cancel>
    @if($invoice->status != 'paid' && $invoice->amountDue() > 0)
    <x-forms.button-primary id="save-payment-status" icon="check">@lang('Add Payment')</x-forms.button-primary>
    @endif
</div>

<script>
    // Initialize select picker
    $(".select-picker").selectpicker();

    // Note: Datepicker is automatically initialized by x-forms.datepicker component
    // No manual initialization needed

    // Handle Edit Payment
    $('.edit-payment').on('click', function() {
        var paymentId = $(this).data('payment-id');
        var paymentDate = $(this).data('payment-date');
        var paymentMode = $(this).data('payment-mode');
        var paymentAmount = $(this).data('payment-amount');
        var paymentReference = $(this).data('payment-reference');
        var paymentNotes = $(this).data('payment-notes');

        console.log('Edit payment clicked:', {
            id: paymentId,
            date: paymentDate,
            mode: paymentMode,
            amount: paymentAmount
        });

        // Populate form with existing values
        $('#payment_mode').val(paymentMode).selectpicker('refresh');
        $('#payment_date').val(paymentDate);
        $('#payment_amount').val(paymentAmount);
        $('#payment_reference').val(paymentReference || '');
        $('#payment_notes').val(paymentNotes || '');

        // Add hidden field for payment ID (to identify edit vs create)
        if ($('#edit_payment_id').length === 0) {
            $('#payment-status-form').prepend('<input type="hidden" id="edit_payment_id" name="edit_payment_id" value="">');
        }
        $('#edit_payment_id').val(paymentId);

        // Change button text
        $('#save-payment-status').html('<i class="fa fa-check"></i> Update Payment');

        // Scroll to form
        $('html, body').animate({
            scrollTop: $('#payment-status-form').offset().top - 100
        }, 500);
    });

    // Handle Delete Payment
    $('.delete-payment').on('click', function() {
        var paymentId = $(this).data('payment-id');
        var paymentAmount = $(this).data('payment-amount');

        console.log('Delete payment clicked:', {
            id: paymentId,
            amount: paymentAmount
        });

        Swal.fire({
            title: 'Delete Payment?',
            html: 'Are you sure you want to delete this payment?<br>' +
                  '<strong>Amount: {{ $invoice->currency->currency_symbol ?? '' }}' + paymentAmount + '</strong><br><br>' +
                  '<span class="text-danger">This will update the invoice status and due amount accordingly.</span>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-danger mr-2',
                cancelButton: 'btn btn-secondary'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                $.easyAjax({
                    url: "{{ route($paymentRoutePrefix . '.delete-payment') }}",
                    type: "POST",
                    data: {
                        payment_id: paymentId,
                        invoice_id: {{ $invoice->id }},
                        _token: "{{ csrf_token() }}"
                    },
                    blockUI: true,
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Payment Deleted',
                                text: response.message || 'Payment deleted successfully',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // Reload modal with updated data
                                var invoiceId = {{ $invoice->id }};
                                var url = "{{ route($paymentRoutePrefix . '.payment-modal') }}";
                                $(MODAL_LG + ' ' + MODAL_HEADING).html('Invoice Payment Management');
                                $.ajaxModal(MODAL_LG, url + '?invoice_id=' + invoiceId);
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Failed to delete payment'
                        });
                    }
                });
            }
        });
    });

    // Function to submit payment (defined at top level for proper scope)
    function submitPayment() {
        console.log('Submitting payment...');
        
        $.easyAjax({
            url: "{{ route($paymentRoutePrefix . '.update-payment-status') }}",
            container: '#payment-status-form',
            type: "POST",
            disableButton: true,
            blockUI: true,
            buttonSelector: '#save-payment-status',
            data: $('#payment-status-form').serialize(),
            success: function(response) {
                console.log('Payment response:', response);
                console.log('Response data:', response.data);
                console.log('Response type:', typeof response);
                
                if (response.status === 'success') {
                    // Handle both possible response structures
                    var paymentData = response.data || response;
                    
                    console.log('Payment data extracted:', paymentData);
                    
                    var isFullyPaid = (paymentData.new_status == 'paid' || paymentData.is_fully_paid === true);
                    var paymentAmount = paymentData.payment_amount || '0.00';
                    var newStatus = paymentData.new_status || 'unknown';
                    var dueAmount = parseFloat(paymentData.due_amount) || 0;
                    
                    console.log('Processed values:', {
                        isFullyPaid: isFullyPaid,
                        paymentAmount: paymentAmount,
                        newStatus: newStatus,
                        dueAmount: dueAmount
                    });
                    
                    // Show success message
                    var swalOptions = {
                        icon: 'success',
                        title: 'Payment Added Successfully!',
                        html: '<div class="text-left">' +
                              '<p><strong>Payment Amount:</strong> {{ $invoice->currency->currency_symbol ?? '' }}' + paymentAmount + '</p>' +
                              '<p><strong>Invoice Status:</strong> <span class="badge badge-' + (isFullyPaid ? 'success' : 'warning') + '">' + newStatus.toUpperCase() + '</span></p>' +
                              '<p><strong>Remaining Due:</strong> {{ $invoice->currency->currency_symbol ?? '' }}' + dueAmount.toFixed(2) + '</p>' +
                              '</div>',
                        showCancelButton: !isFullyPaid,
                        confirmButtonText: isFullyPaid ? 'OK' : 'Add Another Payment',
                        cancelButtonText: 'Close',
                        allowOutsideClick: false,
                        customClass: {
                            confirmButton: 'btn btn-primary mr-2',
                            cancelButton: 'btn btn-secondary'
                        },
                        buttonsStyling: false
                    };
                    
                    // Only add timer if fully paid
                    if (isFullyPaid) {
                        swalOptions.timer = 2500;
                        swalOptions.timerProgressBar = true;
                    }
                    
                    Swal.fire(swalOptions).then((result) => {
                        console.log('Swal result:', result);
                        
                        if (isFullyPaid || result.dismiss === Swal.DismissReason.cancel || result.dismiss === Swal.DismissReason.timer) {
                            // Close modal and reload table
                            console.log('Closing modal and reloading table');
                            $(MODAL_LG).modal('hide');
                            
                            // Reload DataTable
                            if (window.LaravelDataTables && window.LaravelDataTables["cfa-distributor-invoices-table"]) {
                                window.LaravelDataTables["cfa-distributor-invoices-table"].draw(false);
                            } else {
                                setTimeout(function() {
                                    window.location.reload();
                                }, 300);
                            }
                        } else if (result.isConfirmed) {
                            // User wants to add another payment
                            console.log('Reloading modal for another payment');
                            var invoiceId = {{ $invoice->id }};
                            var url = "{{ route($paymentRoutePrefix . '.payment-modal') }}";
                            $(MODAL_LG + ' ' + MODAL_HEADING).html('Invoice Payment Management');
                            $.ajaxModal(MODAL_LG, url + '?invoice_id=' + invoiceId);
                        }
                    });
                }
            },
            error: function(response) {
                console.error('Payment update error:', response);
            }
        });
    }

    // Handle form submission - attach to button click (only if button exists)
    $('#save-payment-status').off('click').on('click', function(e) {
        console.log('Button exists, attaching handler');
    });
    
    $('#save-payment-status').on('click', function(e) {
        e.preventDefault();
        console.log('Add Payment button clicked');
        
        var paymentMode = $('#payment_mode').val();
        var paymentDate = $('#payment_date').val();
        var paymentAmount = parseFloat($('#payment_amount').val());
        var dueAmount = {{ $invoice->amountDue() }};

        console.log('Form values:', {
            paymentMode: paymentMode,
            paymentDate: paymentDate,
            paymentAmount: paymentAmount,
            dueAmount: dueAmount
        });

        if (!paymentMode || !paymentDate || !paymentAmount) {
            Swal.fire({
                icon: 'error',
                title: 'Required Fields',
                text: 'Please fill in all required fields'
            });
            return;
        }

        if (paymentAmount <= 0 || isNaN(paymentAmount)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Amount',
                text: 'Payment amount must be greater than 0'
            });
            return;
        }

        if (paymentAmount > dueAmount) {
            Swal.fire({
                icon: 'warning',
                title: 'Amount Exceeds Due',
                text: 'Payment amount ({{ $invoice->currency->currency_symbol ?? '' }}' + paymentAmount.toFixed(2) + ') is greater than due amount ({{ $invoice->currency->currency_symbol ?? '' }}' + dueAmount.toFixed(2) + '). Continue?',
                showCancelButton: true,
                confirmButtonText: 'Yes, Continue',
                cancelButtonText: 'No, Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    submitPayment();
                }
            });
        } else {
            submitPayment();
        }
    });
</script>
