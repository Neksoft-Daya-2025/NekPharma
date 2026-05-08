{{-- Smart HR core assets - load from smhr/build (path prefix smhr/build) --}}
<link rel="shortcut icon" type="image/x-icon" href="{{ asset('smhr/build/img/favicon.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('smhr/build/img/apple-touch-icon.png') }}">

<!-- Bootstrap CSS -->
<link rel="stylesheet" href="{{ asset('smhr/build/css/bootstrap.min.css') }}">
<!-- Feather CSS -->
<link rel="stylesheet" href="{{ asset('smhr/build/plugins/icons/feather/feather.css') }}">
<!-- Tabler Icon CSS -->
<link rel="stylesheet" href="{{ asset('smhr/build/plugins/tabler-icons/tabler-icons.css') }}">
<!-- Select2 CSS -->
<link rel="stylesheet" href="{{ asset('smhr/build/plugins/select2/css/select2.min.css') }}">
<!-- Fontawesome CSS -->
<link rel="stylesheet" href="{{ asset('smhr/build/plugins/fontawesome/css/fontawesome.min.css') }}">
<link rel="stylesheet" href="{{ asset('smhr/build/plugins/fontawesome/css/all.min.css') }}">
<!-- Flatpickr / Daterangepicker -->
<link rel="stylesheet" href="{{ asset('smhr/build/plugins/flatpickr/flatpickr.min.css') }}">
<link rel="stylesheet" href="{{ asset('smhr/build/plugins/daterangepicker/daterangepicker.css') }}">
<!-- Datatable CSS -->
<link rel="stylesheet" href="{{ asset('smhr/build/css/dataTables.bootstrap5.min.css') }}">
<!-- Summernote CSS -->
<link rel="stylesheet" href="{{ asset('smhr/build/plugins/summernote/summernote-lite.min.css') }}">
<link rel="stylesheet" href="{{ asset('smhr/build/css/bootstrap-datetimepicker.min.css') }}">
<!-- Bootstrap Tagsinput -->
<link rel="stylesheet" href="{{ asset('smhr/build/plugins/bootstrap-tagsinput/bootstrap-tagsinput.css') }}">
<!-- Main CSS -->
<link rel="stylesheet" href="{{ asset('smhr/build/css/style.css') }}">
@stack('styles')
