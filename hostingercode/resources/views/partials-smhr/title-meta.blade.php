@php
    $title = isset($pageTitle) ? (is_array(__($pageTitle)) ? $pageTitle : __($pageTitle)) : 'Dashboard';
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $title }} | {{ config('app.name') }}</title>
<meta name="description" content="{{ config('app.name') }} - HR & CRM">
<meta name="robots" content="index, follow">
