@extends('layouts.app')

@section('content')
    <div class="content-wrapper">
        <div class="d-flex justify-content-between action-bar mb-3">
            <div class="d-flex align-items-center">
                <h4 class="mb-0">@lang('modules.expenses.addExpense')</h4>
            </div>
            <div class="d-flex align-items-center">
                @if (user()->permission('add_expenses') == 'all' || user()->permission('add_expenses') == 'added')
                    <a href="{{ route('expenses.import.pharma') }}" class="btn btn-outline-primary openRightModal mr-2">
                        <i class="fa fa-file-excel"></i> @lang('app.importPharmaExpenseStatement')
                    </a>
                @endif
                <a href="{{ route('expenses.index') }}" class="btn btn-secondary">
                    <i class="fa fa-arrow-left"></i> @lang('app.back')
                </a>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                @include('expenses.ajax.create-pharma')
            </div>
        </div>
    </div>
@endsection

