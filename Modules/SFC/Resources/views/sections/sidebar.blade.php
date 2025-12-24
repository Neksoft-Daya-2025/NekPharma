@php
    $userModules = user_modules() ?? [];
    $userRoles = user_roles() ?? [];
    $sfcModuleName = \Modules\SFC\Entities\SFCSetting::MODULE_NAME;
@endphp
@if (user() && !in_array('client', $userRoles) && user()->permission('view_sfc_chart') != 'none' && in_array($sfcModuleName, $userModules))
    <x-menu-item icon="map" :text="__('sfc::app.menu.sfcChart')" :link="route('sfc-charts.index')" :addon="App::environment('demo')">
        <x-slot name="iconPath">
            <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
        </x-slot>
    </x-menu-item>
@endif

