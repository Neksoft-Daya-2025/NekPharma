@extends('layouts.app')

@push('datatable-styles')
    @include('sections.datatable_css')
@endpush

@section('filter-section')
    <x-filters.filter-box>
        @if (in_array('admin', user_roles()) && isset($cfaStockists) && $cfaStockists->count() > 0)
            <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
                <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('CFA Stockist')</p>
                <div class="select-status">
                    <select class="form-control select-picker" id="cfaStockistID" data-live-search="true" data-size="8">
                        <option value="all">@lang('app.all')</option>
                        @foreach ($cfaStockists as $stockist)
                            <option value="{{ $stockist->id }}">{{ $stockist->shopname ?? $stockist->fullname ?? $stockist->id }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif
        @if (in_array('admin', user_roles()) && isset($cfaDistributors) && $cfaDistributors->count() > 0)
            <div class="select-box d-flex py-2 px-lg-2 px-md-2 px-0 border-right-grey border-right-grey-sm-0">
                <p class="mb-0 pr-2 f-14 text-dark-grey d-flex align-items-center">@lang('app.client')</p>
                <div class="select-status">
                    <select class="form-control select-picker" id="cfaDistributorID" data-live-search="true" data-size="8">
                        <option value="all">@lang('app.all')</option>
                        @foreach ($cfaDistributors as $distributor)
                            <option value="{{ $distributor->id }}">{{ $distributor->company_name ?? $distributor->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif

        <div class="task-search d-flex py-1 px-lg-3 px-0 border-right-grey align-items-center">
            <form class="w-100 mr-1 mr-lg-0 mr-md-1 ml-md-1 ml-0 ml-lg-0">
                <div class="input-group bg-grey rounded">
                    <div class="input-group-prepend">
                        <span class="input-group-text border-0 bg-additional-grey">
                            <i class="fa fa-search f-13 text-dark-grey"></i>
                        </span>
                    </div>
                    <input type="text" class="form-control f-14 p-1 border-additional-grey" id="search-text-field"
                        placeholder="@lang('app.startTyping')">
                </div>
            </form>
        </div>

        <div class="select-box d-flex py-1 px-lg-2 px-md-2 px-0">
            <x-forms.button-secondary class="btn-xs d-none" id="reset-filters" icon="times-circle">
                @lang('app.clearFilters')
            </x-forms.button-secondary>
        </div>

        <x-filters.more-filter-box>
            <div class="more-filter-items">
                <label class="f-14 text-dark-grey mb-12" for="usr">@lang('app.showOnly')</label>
                <div class="select-filter mb-4">
                    <div class="select-others">
                        <select class="form-control select-picker" name="stockFilter" id="stockFilter" data-live-search="true"
                            data-container="body" data-size="8">
                            <option value="all">@lang('app.all')</option>
                            <option value="available">@lang('app.availableOnly') (@lang('app.totalQuantity') > 0)</option>
                            <option value="expired">@lang('app.expired')</option>
                            <option value="expiring_soon">@lang('app.expiringSoon') (30 days)</option>
                        </select>
                    </div>
                </div>
            </div>
        </x-filters.more-filter-box>
    </x-filters.filter-box>
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="d-flex justify-content-between action-bar mb-2 mb-lg-0 mb-md-0">
            <div id="table-actions" class="flex-grow-1 align-items-center">
                <h4 class="mb-0 f-21 font-weight-normal text-capitalize">@lang('app.cfaStockistInventory')</h4>
            </div>
        </div>

        <div class="d-flex flex-column w-tables rounded mt-3 bg-white w-100 table-responsive">
            {!! $dataTable->table(['class' => 'table table-hover border-0 w-100']) !!}
        </div>
    </div>
@endsection

@push('scripts')
    @include('sections.datatable_js')
    <script>
        $(function() {
            $('#cfa-stockist-inventory-table').on('preXhr.dt', function(e, settings, data) {
                data['cfaStockistID'] = $('#cfaStockistID').val() || 'all';
                data['cfaDistributorID'] = $('#cfaDistributorID').length ? ($('#cfaDistributorID').val() || 'all') : 'all';
                data['stockFilter'] = $('#stockFilter').val() || 'all';
                data['searchText'] = $('#search-text-field').val() || '';
            });

            const showTable = () => {
                window.LaravelDataTables["cfa-stockist-inventory-table"].draw(true);
            };

            function updateResetVisibility() {
                var hasFilter = ($('#cfaStockistID').length && ($('#cfaStockistID').val() || 'all') != 'all') ||
                    ($('#cfaDistributorID').length && ($('#cfaDistributorID').val() || 'all') != 'all') ||
                    ($('#stockFilter').val() || 'all') != 'all' ||
                    ($('#search-text-field').val() || '').trim() !== '';
                $('#reset-filters').toggleClass('d-none', !hasFilter);
            }

            $('#cfaStockistID, #cfaDistributorID, #stockFilter').on('change', function() {
                updateResetVisibility();
                showTable();
            });

            var searchDebounce;
            $('#search-text-field').on('keyup', function() {
                clearTimeout(searchDebounce);
                searchDebounce = setTimeout(function() {
                    updateResetVisibility();
                    showTable();
                }, 400);
            });

            $('#reset-filters').click(function() {
                $('#cfaStockistID, #cfaDistributorID, #stockFilter').val('all').selectpicker('refresh');
                $('#search-text-field').val('');
                updateResetVisibility();
                showTable();
            });
        });
    </script>
@endpush
