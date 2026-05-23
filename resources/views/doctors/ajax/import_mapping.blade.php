<div class="col-sm-12">
    <div class="alert alert-info mx-0 mt-3 mb-0">
        <strong>Step 2 — Map columns.</strong>
        Each box shows your <strong>Excel header</strong> and sample values. If required columns are matched, import starts automatically.
        Required: <strong>Dr. Name</strong> and <strong>HQ</strong>.
    </div>
</div>
@include('import.process-form', [
    'headingTitle' => __('app.importExcel') . ' ' . __('Doctors'),
    'processRoute' => route('doctors.import.process'),
    'backRoute' => route('doctors.index'),
    'backButtonText' => __('app.back') . ' ' . __('Doctors'),
    'autoSubmitImport' => true,
    'importProgressModule' => 'DoctorImport',
])
