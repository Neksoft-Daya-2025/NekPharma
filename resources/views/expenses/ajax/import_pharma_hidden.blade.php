@if(!empty($pharmaImportContext))
    <input type="hidden" name="pharma_user_id" value="{{ $pharmaImportContext['pharma_user_id'] }}">
    <input type="hidden" name="pharma_headquarter_id" value="{{ $pharmaImportContext['pharma_headquarter_id'] }}">
    <input type="hidden" name="expense_month" value="{{ $pharmaImportContext['expense_month'] }}">
    <input type="hidden" name="expense_year" value="{{ $pharmaImportContext['expense_year'] }}">
    <input type="hidden" name="posted_on" value="{{ $pharmaImportContext['posted_on'] }}">
    <input type="hidden" name="no_of_vouchers" value="{{ $pharmaImportContext['no_of_vouchers'] }}">
    <input type="hidden" name="submitted_to" value="{{ $pharmaImportContext['submitted_to'] }}">
@endif
