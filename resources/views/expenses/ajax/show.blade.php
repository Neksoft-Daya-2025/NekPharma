<div class="row">
    <div class="col-sm-12">
        <x-cards.data :title="__('app.menu.expenses') . ' ' . __('app.details')" class=" mt-4">
            @if (is_null($expense->expenses_recurring_id))
                <x-slot name="action">
                    <div class="dropdown">
                        <button class="btn f-14 px-0 py-0 text-dark-grey dropdown-toggle" type="button"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fa fa-ellipsis-h"></i>
                        </button>

                        <div class="dropdown-menu dropdown-menu-right border-grey rounded b-shadow-4 p-0"
                            aria-labelledby="dropdownMenuLink" tabindex="0">
                            @php
                                $trashBtn = (!is_null($expense->project) && is_null($expense->project->deleted_at)) ? true : (is_null($expense->project) ? true : false) ;
                            @endphp

                            @php
                                // Check if expense is locked (pharma statement that is submitted or approved)
                                $isLocked = false;
                                if ($expense->expense_type == 'pharma_statement') {
                                    $isOwner = $expense->user_id == user()->id;
                                    $isAdmin = $editExpensePermission == 'all';
                                    
                                    // Employee cannot edit submitted/approved expenses
                                    if ($isOwner && ($expense->status == 'pending' || $expense->status == 'approved')) {
                                        $isLocked = true;
                                    }
                                    
                                    // Admin cannot edit approved expenses
                                    if ($isAdmin && $expense->status == 'approved') {
                                        $isLocked = true;
                                    }
                                }
                                
                                // Check if expense can be deleted
                                $isPendingOrApproved = in_array($expense->status, ['pending', 'approved']);
                                $canDelete = false;
                                if ($isPendingOrApproved) {
                                    // Only admin can delete pending/approved expenses
                                    $canDelete = $deleteExpensePermission == 'all';
                                } else {
                                    // Normal delete permission check for other expenses
                                    $canDelete = $deleteExpensePermission == 'all' || ($deleteExpensePermission == 'added' && user()->id == $expense->added_by);
                                }
                            @endphp
                            @if (!$isLocked && $trashBtn && ($editExpensePermission == 'all' || ($editExpensePermission == 'added' && user()->id == $expense->added_by)))
                                <a class="dropdown-item openRightModal" href="{{ route('expenses.edit', [$expense->id]) }}">@lang('app.edit')
                                        </a>
                            @elseif($isLocked)
                                <a class="dropdown-item disabled" href="javascript:;" title="@lang('messages.expenseLockedForEmployee')">
                                    <i class="fa fa-lock"></i> @lang('app.edit') (Locked)
                                </a>
                            @endif
                                @if ($canDelete)
                                    <a class="dropdown-item delete-table-row" href="javascript:;" data-expense-id="{{ $expense->id }}">@lang('app.delete')
                                    </a>
                                @endif
                        </div>
                    </div>
                </x-slot>
            @endif
            <x-cards.data-row :label="__('modules.expenses.itemName')" :value="$expense->item_name" />

            <x-cards.data-row :label="__('app.category')" :value="$expense->category->category_name ?? '--'" />

            <x-cards.data-row :label="__('app.price')" :value="$expense->total_amount" />

            <x-cards.data-row :label="__('modules.expenses.purchaseDate')"
                :value="(!is_null($expense->purchase_date) ? $expense->purchase_date->translatedFormat(company()->date_format) : '--')" />

            <x-cards.data-row :label="__('modules.expenses.purchaseFrom')" :value="$expense->purchase_from ?? '--'" />

            <x-cards.data-row :label="__('app.project')"
                :value="(!is_null($expense->project) && !is_null($expense->project->withTrashed()) ? $expense->project->project_name : '--')" />

            @php
                $bankName = !is_null($expense->bankAccount) ? ($expense->bankAccount->bank_name . ' | ' . $expense->bankAccount->account_name ?? '') : '--';
            @endphp
            <x-cards.data-row :label="__('app.menu.bankaccount')" :value="$bankName !== '' ? $bankName : '--'" />

            <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                <p class="mb-0 text-lightest f-14 w-30 ">
                    @lang('app.bill')</p>
                <p class="mb-0 text-dark-grey f-14">
                    @if (!is_null($expense->bill))
                        <a target="_blank" href="{{ $expense->bill_url }}" class="text-darkest-grey">@lang('app.view')
                            @lang('app.bill') <i class="fa fa-link"></i></a>&nbsp
                            <a href="{{ $expense->bill_url }}" class="text-darkest-grey" download>@lang('app.download')
                            <i class="fa fa-download f-w-500 mr-1 f-11"></i></a>
                    @else
                        --
                    @endif
                </p>
            </div>

            <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                <p class="mb-0 text-lightest f-14 w-30 ">
                    @lang('app.employee')</p>
                <p class="mb-0 text-dark-grey f-14">
                    <x-employee :user="$expense->user" />
                </p>
            </div>
            @php
                $u = $expense->user;
                $showEmpId = $u ? (optional($u->employeeDetail)->employee_id ?? optional($u->employeeDetails)->employee_id) : null;
            @endphp
            @if ($showEmpId)
                <x-cards.data-row :label="__('modules.employees.employeeId')" :value="$showEmpId" />
            @endif

            @if ($expense->expense_type === 'pharma_statement')
                @php
                    $wwShow = $expense->worked_with ? json_decode($expense->worked_with, true) : null;
                    $wwShowText = is_array($wwShow) && count($wwShow)
                        ? implode(', ', $wwShow)
                        : (!empty($expense->worked_with) && !is_array($expense->worked_with) ? (string) $expense->worked_with : '—');
                @endphp
                <x-cards.data-row label="Town Worked" :value="$expense->town_worked ?? '—'" />
                <x-cards.data-row label="Worked With" :value="$wwShowText" />
                <x-cards.data-row label="No. of Doctors Met" :value="(string) (int) ($expense->no_of_doctors_met ?? 0)" />
                <x-cards.data-row label="No. of Retailers Met" :value="(string) (int) ($expense->no_of_retailers_met ?? 0)" />
                <x-cards.data-row label="Head Quarter From" :value="$expense->headquarter_from ?? '—'" />
                <x-cards.data-row label="Head Quarter To" :value="$expense->headquarter_to ?? '—'" />
                <x-cards.data-row label="Mode of Transport" :value="$expense->mode_of_transport ?? '—'" />
                <x-cards.data-row label="Km" :value="number_format($expense->km ?? 0, 2)" />
                <x-cards.data-row label="Fare (Rs)" :value="number_format($expense->fare_rs ?? 0, 2)" />
                <x-cards.data-row
                    label="Allowances (HQ / Ex / O/S)"
                    :value="'HQ: ' . number_format($expense->daily_allowance_hq_rs ?? 0, 2) . ' | Ex: ' . number_format($expense->daily_allowance_ex_rs ?? 0, 2) . ' | O/S: ' . number_format($expense->daily_allowance_os_rs ?? 0, 2)" />
                <x-cards.data-row label="Fixed Expenses" :value="number_format($expense->fixed_expenses ?? 0, 2)" />
                <x-cards.data-row label="Other Expenses" :value="number_format($expense->other_expenses ?? 0, 2)" />
                <x-cards.data-row label="Remarks" :value="!empty($expense->remarks) ? $expense->remarks : '—'" />
            @endif

            <x-cards.data-row :label="__('app.description')"
            :value="!empty($expense->description) ? $expense->description : '--'"
            html="true"/>

            <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                <p class="mb-0 text-lightest f-14 w-30 ">
                    @lang('app.status')</p>
                <p class="mb-0 text-dark-grey f-14">
                    @if ($expense->status == 'pending')
                        <x-status :value="__('app.'.$expense->status)" color="yellow" />
                    @elseif ($expense->status == 'approved')
                        <x-status :value="__('app.'.$expense->status)" color="dark-green" />
                    @else
                        <x-status :value="__('app.'.$expense->status)" color="red" />
                    @endif
                </p>
            </div>

            @if ($expense->status == 'approved')
                <div class="col-12 px-0 pb-3 d-lg-flex d-md-flex d-block">
                    <p class="mb-0 text-lightest f-14 w-30 ">
                        @lang('modules.expenses.approvedBy')</p>
                    <p class="mb-0 text-dark-grey f-14">
                        <x-employee :user="$expense->approver" />
                    </p>
                </div>
            @endif


            <x-forms.custom-field-show :fields="$fields" :model="$expense"></x-forms.custom-field-show>

        </x-cards.data>
    </div>
</div>
<script>
    $('body').on('click', '.delete-table-row', function() {
            var id = $(this).data('expense-id');
            Swal.fire({
                title: "@lang('messages.sweetAlertTitle')",
                text: "@lang('messages.recoverRecord')",
                icon: 'warning',
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: "@lang('messages.confirmDelete')",
                cancelButtonText: "@lang('app.cancel')",
                customClass: {
                    confirmButton: 'btn btn-primary mr-3',
                    cancelButton: 'btn btn-secondary'
                },
                showClass: {
                    popup: 'swal2-noanimation',
                    backdrop: 'swal2-noanimation'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = "{{ route('expenses.destroy', ':id') }}";
                    url = url.replace(':id', id);

                    var token = "{{ csrf_token() }}";

                    $.easyAjax({
                        type: 'POST',
                        url: url,
                        data: {
                            '_token': token,
                            '_method': 'DELETE'
                        },
                        success: function(response) {
                            if (response.status == "success") {
                                window.location.href = "{{ route('expenses.index')}}";
                            }
                        }
                    });
                }
            });
        });
</script>
