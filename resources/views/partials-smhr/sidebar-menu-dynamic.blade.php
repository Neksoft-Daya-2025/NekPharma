@php
    $sidebarUserPermissions = $sidebarUserPermissions ?? (function_exists('sidebar_user_perms') ? sidebar_user_perms() : []);
    if (!is_array($sidebarUserPermissions)) {
        $sidebarUserPermissions = [];
    }
    $currentRoute = request()->route() ? request()->route()->getName() : '';
@endphp
<ul>
    <li class="menu-title"><span>{{ strtoupper(__('app.menu.main')) }}</span></li>
    <li>
        <ul>
            {{-- Dashboard --}}
            <li class="{{ $currentRoute === 'dashboard' ? 'active' : '' }}">
                <a href="{{ route('dashboard') }}">
                    <i class="ti ti-smart-home"></i><span>{{ __('app.menu.dashboard') }}</span>
                </a>
            </li>
            @if (Route::has('smhr.demo'))
            <li class="{{ $currentRoute === 'smhr.demo' ? 'active' : '' }}">
                <a href="{{ route('smhr.demo') }}">
                    <i class="ti ti-layout-dashboard"></i><span>SMHR Demo</span>
                </a>
            </li>
            @endif

            @if (!in_array('client', user_roles()))
                @if (in_array('employees', user_modules()) && isset($sidebarUserPermissions['view_employees']) && $sidebarUserPermissions['view_employees'] != 5 && $sidebarUserPermissions['view_employees'] != 'none')
                <li class="submenu">
                    <a href="javascript:void(0);" class="{{ str_starts_with($currentRoute ?? '', 'employees') ? 'active subdrop' : '' }}">
                        <i class="ti ti-users"></i><span>{{ __('app.menu.employees') }}</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul>
                        <li><a href="{{ route('employees.index') }}" class="{{ ($currentRoute ?? '') === 'employees.index' ? 'active' : '' }}">{{ __('app.employee') }}</a></li>
                    </ul>
                </li>
                @endif

                @if (in_array('tours', user_modules()))
                <li class="{{ str_starts_with($currentRoute ?? '', 'tours') ? 'active' : '' }}">
                    <a href="{{ route('tours.index') }}">
                        <i class="ti ti-calendar-event"></i><span>{{ __('app.menu.tourPlan') ?? 'Tour Plan' }}</span>
                    </a>
                </li>
                @endif

                @if (in_array('expenses', user_modules()) && isset($sidebarUserPermissions['view_expenses']) && $sidebarUserPermissions['view_expenses'] != 5 && $sidebarUserPermissions['view_expenses'] != 'none')
                <li class="{{ str_starts_with($currentRoute ?? '', 'expenses') ? 'active' : '' }}">
                    <a href="{{ route('expenses.index') }}">
                        <i class="ti ti-cash"></i><span>{{ __('app.menu.expenses') }}</span>
                    </a>
                </li>
                @endif

                @if (in_array('clients', user_modules()) && isset($sidebarUserPermissions['view_clients']) && $sidebarUserPermissions['view_clients'] != 5 && $sidebarUserPermissions['view_clients'] != 'none')
                <li class="{{ str_starts_with($currentRoute ?? '', 'clients') ? 'active' : '' }}">
                    <a href="{{ route('clients.index') }}">
                        <i class="ti ti-building"></i><span>{{ __('app.menu.clients') }}</span>
                    </a>
                </li>
                @endif

                @if (in_array('attendance', user_modules()) && isset($sidebarUserPermissions['view_attendance']) && $sidebarUserPermissions['view_attendance'] != 5 && $sidebarUserPermissions['view_attendance'] != 'none')
                <li class="{{ str_starts_with($currentRoute ?? '', 'attendances') ? 'active' : '' }}">
                    <a href="{{ route('attendances.index') }}">
                        <i class="ti ti-clock"></i><span>{{ __('app.menu.attendance') }}</span>
                    </a>
                </li>
                @endif

                @if (in_array('leaves', user_modules()) && isset($sidebarUserPermissions['view_leave']) && $sidebarUserPermissions['view_leave'] != 5 && $sidebarUserPermissions['view_leave'] != 'none')
                <li class="{{ str_starts_with($currentRoute ?? '', 'leaves') ? 'active' : '' }}">
                    <a href="{{ route('leaves.index') }}">
                        <i class="ti ti-calendar-off"></i><span>{{ __('app.menu.leave') }}</span>
                    </a>
                </li>
                @endif

                @if (in_array('payroll', user_modules()))
                <li class="{{ str_starts_with($currentRoute ?? '', 'payroll') ? 'active' : '' }}">
                    <a href="{{ route('payroll.index') }}">
                        <i class="ti ti-wallet"></i><span>{{ __('app.menu.payroll') ?? 'Payroll' }}</span>
                    </a>
                </li>
                @endif

                @if (in_array('invoices', user_modules()) && (in_array('admin', user_roles()) || (isset($sidebarUserPermissions['view_invoices']) && $sidebarUserPermissions['view_invoices'] != 5 && $sidebarUserPermissions['view_invoices'] != 'none')))
                <li class="submenu">
                    <a href="javascript:void(0);" class="{{ str_starts_with($currentRoute ?? '', 'cfa-') ? 'active subdrop' : '' }}">
                        <i class="ti ti-file-invoice"></i><span>{{ __('app.menu.invoices') ?? 'Invoices' }}</span>
                        <span class="menu-arrow"></span>
                    </a>
                    <ul>
                        @if (Route::has('cfa-distributor-invoices.index'))
                        <li><a href="{{ route('cfa-distributor-invoices.index') }}" class="{{ ($currentRoute ?? '') === 'cfa-distributor-invoices.index' ? 'active' : '' }}">{{ __('app.menu.cfaDistributorInvoice') ?? 'CFA Distributor' }}</a></li>
                        @endif
                        @if (Route::has('cfa-stockist-invoices.index'))
                        <li><a href="{{ route('cfa-stockist-invoices.index') }}" class="{{ ($currentRoute ?? '') === 'cfa-stockist-invoices.index' ? 'active' : '' }}">{{ __('app.menu.cfaStockistInvoice') ?? 'CFA Stockist' }}</a></li>
                        @endif
                    </ul>
                </li>
                @endif

                @if (in_array('admin', user_roles()))
                <li class="menu-title"><span>{{ strtoupper(__('app.settings')) }}</span></li>
                <li class="{{ str_starts_with($currentRoute ?? '', 'settings') ? 'active' : '' }}">
                    <a href="{{ route('settings.index') }}">
                        <i class="ti ti-settings"></i><span>{{ __('app.menu.settings') ?? 'Settings' }}</span>
                    </a>
                </li>
                @endif
            @endif
        </ul>
    </li>
</ul>
