@php
    $hasApproveTourPermission = in_array(user()->permission('approve_tours'), ['all', 'added', 'owned']);
    $hasApproveDcrPermission = in_array(user()->permission('approve_dcr_reports'), ['all', 'added', 'owned', 'both']);
    $hasApproveExpensePermission = in_array(user()->permission('approve_expenses'), ['all', 'added', 'owned']);
    $canViewLedger = in_array('accountant', user_roles()) || in_array('admin', user_roles()) || in_array('fsa-executive', user_roles()) || (in_array('invoices', user_modules()) && in_array(user()->permission('view_invoices'), ['all', 'added', 'owned', 'both']));
    $hasCFAStockistAccess = in_array(user()->permission('view_stockists'), ['all', 'added', 'owned', 'both']);
    $hasSubmitActions = (in_array('dcr_reports', user_modules()) && user()->permission('add_dcr_reports') != 'none')
        || (in_array('tours', user_modules()) && user()->permission('add_tours') != 'none')
        || (in_array('expenses', user_modules()) && user()->permission('add_expenses') != 'none');
    $hasApproveActions = $hasApproveTourPermission || $hasApproveDcrPermission || $hasApproveExpensePermission;
    $hasLedgerActions = $canViewLedger;
    $hasAnyQuickLink = $hasSubmitActions || $hasApproveActions || $hasLedgerActions;
@endphp
@if ($hasAnyQuickLink)
<div class="row mb-3">
    <div class="col-12">
        <div class="card bg-white border-0 b-shadow-4 rounded">
            <div class="card-header bg-white border-0 border-bottom py-3 px-4" data-toggle="collapse" data-target="#quickLinksCollapse" role="button" aria-expanded="true" aria-controls="quickLinksCollapse">
                <h5 class="mb-0 f-15 f-w-600 text-darkest-grey">
                    <i class="fa fa-bolt mr-2 text-primary"></i>{{ __('modules.dashboard.quickActions') }}
                </h5>
                <i class="fa fa-chevron-down text-muted float-right" aria-hidden="true"></i>
            </div>
            <div id="quickLinksCollapse" class="collapse show">
                <div class="card-body p-4">
                    <div class="row">
                        @if ($hasSubmitActions)
                            <div class="col-12 col-md-4 mb-3 mb-md-0">
                                <div class="quick-action-group">
                                    <span class="d-block f-12 text-muted text-uppercase mb-2 font-weight-bold">{{ __('app.submit') }}</span>
                                    <div class="d-flex flex-wrap">
                                        @if (in_array('dcr_reports', user_modules()) && user()->permission('add_dcr_reports') != 'none')
                                            <a href="{{ route('dcr-management.create') }}" class="btn btn-sm btn-outline-primary mb-2 mr-2">
                                                <i class="fa fa-clipboard-list mr-1"></i>{{ __('modules.dashboard.submitDcr') }}
                                            </a>
                                        @endif
                                        @if (in_array('tours', user_modules()) && user()->permission('add_tours') != 'none')
                                            <a href="{{ route('tours.create') }}" class="btn btn-sm btn-outline-primary mb-2 mr-2">
                                                <i class="fa fa-route mr-1"></i>{{ __('modules.dashboard.submitTourPlan') }}
                                            </a>
                                        @endif
                                        @if (in_array('expenses', user_modules()) && user()->permission('add_expenses') != 'none')
                                            <a href="{{ route('expenses.create') }}" class="btn btn-sm btn-outline-primary mb-2 mr-2">
                                                <i class="fa fa-money-bill mr-1"></i>{{ __('modules.dashboard.submitExpense') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if ($hasApproveActions)
                            <div class="col-12 col-md-4 mb-3 mb-md-0">
                                <div class="quick-action-group">
                                    <span class="d-block f-12 text-muted text-uppercase mb-2 font-weight-bold">{{ __('app.approve') }}</span>
                                    <div class="d-flex flex-wrap">
                                        @if ($hasApproveTourPermission)
                                            <a href="{{ route('tours.index', ['mode' => 'approve']) }}" class="btn btn-sm btn-outline-success mb-2 mr-2">
                                                <i class="fa fa-check-circle mr-1"></i>{{ __('modules.dashboard.approveTourPlans') }}
                                            </a>
                                        @endif
                                        @if ($hasApproveDcrPermission)
                                            <a href="{{ route('dcr-management.index', ['mode' => 'approve']) }}" class="btn btn-sm btn-outline-success mb-2 mr-2">
                                                <i class="fa fa-clipboard-check mr-1"></i>{{ __('modules.dashboard.approveDcr') }}
                                            </a>
                                        @endif
                                        @if ($hasApproveExpensePermission)
                                            <a href="{{ route('expenses.status') }}" class="btn btn-sm btn-outline-success mb-2 mr-2">
                                                <i class="fa fa-check-double mr-1"></i>{{ __('modules.dashboard.approveExpenses') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                        @if ($hasLedgerActions)
                            <div class="col-12 col-md-4">
                                <div class="quick-action-group">
                                    <span class="d-block f-12 text-muted text-uppercase mb-2 font-weight-bold">{{ __('app.menu.ledger') }}</span>
                                    <div class="d-flex flex-wrap">
                                        @if ($canViewLedger)
                                            <a href="{{ route('cfa-ledger.index') }}" class="btn btn-sm btn-outline-secondary mb-2 mr-2">
                                                <i class="fa fa-book mr-1"></i>{{ __('modules.dashboard.companyCfaLedger') }}
                                            </a>
                                            @if ($hasCFAStockistAccess || $canViewLedger)
                                                <a href="{{ route('cfa-stockist-ledger.index') }}" class="btn btn-sm btn-outline-secondary mb-2 mr-2">
                                                    <i class="fa fa-store mr-1"></i>{{ __('modules.dashboard.cfaStockistLedger') }}
                                                </a>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
