@include('import.process-form', [
    'headingTitle' => __('app.importPharmaExpenseStatement'),
    'processRoute' => route('expenses.import.pharma.process'),
    'backRoute' => route('expenses.index'),
    'backButtonText' => __('app.backToExpense'),
    'importExtraHidden' => view('expenses.ajax.import_pharma_hidden', ['pharmaImportContext' => $pharmaImportContext ?? []])->render(),
])
