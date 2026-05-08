<div class="modal-header">
    <h5 class="modal-title" id="supplierPaymentModalTitle">Add Payment</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<form id="supplier-invoice-payment-form">
    @csrf
    <input type="hidden" id="payment_edit_id" name="payment_edit_id" value="">
    <div class="modal-body">
        <div class="form-group">
            <label>Amount <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="amount" id="payment_amount" class="form-control" required min="0.01">
        </div>
        <div class="form-group">
            <label>Date <span class="text-danger">*</span></label>
            <input type="date" name="paid_on" id="payment_paid_on" class="form-control" required value="{{ date('Y-m-d') }}">
        </div>
        <div class="form-group">
            <label>Reference (cheque / reference no)</label>
            <input type="text" name="reference" id="payment_reference" class="form-control" maxlength="100">
        </div>
        <div class="form-group">
            <label>Remarks</label>
            <textarea name="remarks" id="payment_remarks" class="form-control" rows="2" maxlength="500"></textarea>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary" id="save-supplier-payment-btn">Save Payment</button>
    </div>
</form>
