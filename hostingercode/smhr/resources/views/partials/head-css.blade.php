	<!-- Favicon -->
	<link rel="shortcut icon" type="image/x-icon" href="{{URL::asset('build/img/favicon.png')}}">

	<!-- Apple Touch Icon -->
	<link rel="apple-touch-icon" sizes="180x180" href="{{URL::asset('build/img/apple-touch-icon.png')}}">

@if (!Route::is(['login', 'layout-horizontal', 'layout-detached', 'layout-modern', 'layout-horizontal-overlay', 'layout-two-column', 'layout-hovered', 'layout-box', 'layout-horizontal-single', 'layout-horizontal-box', 'layout-horizontal-fullwidth', 'layout-stacked', 'layout-horizontal-sidemenu', 'layout-vertical-transparent', 'layout-without-header', 'login-2', 'login-3', 'register', 'register-2', 'register-3', 'forgot-password', 'forgot-password-2', 'forgot-password-3', 'reset-password', 'reset-password-2', 'reset-password-3', 'email-verification', 'email-verification-2', 'email-verification-3', 'lock-screen', 'error-404', 'error-500', 'coming-soon', 'under-maintenance', 'under-construction', 'success', 'success-2', 'success-3', 'two-step-verification', 'two-step-verification-2', 'two-step-verification-3', 'layout-rtl', 'layout-dark', 'job-grid-2', 'job-list-2', 'job-details']))
    <!-- Theme Script js -->
    <script src="{{URL::asset('build/js/theme-script.js')}}"></script>
@endif

@if (!Route::is(['layout-rtl']))
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/css/bootstrap.min.css')}}">
@endif

@if (Route::is(['layout-rtl']))
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/css/bootstrap.rtl.min.css')}}">
@endif

@if (Route::is(['email-reply', 'project-details', 'gallery', 'plugins', 'search-result', 'social-feed']))
    <!-- Fancybox CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/fancybox/jquery.fancybox.min.css')}}">
@endif

    <!-- Feather CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/icons/feather/feather.css')}}">

@if (Route::is(['form-vertical', 'tables-basic', 'ui-alerts', 'ui-nav-tabs']))
    <!-- FeatherIcon CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/css/feather.css')}}">
@endif

@if (Route::is(['icon-bootstrap']))
    <!-- Bootstrap Icon CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/icons/bootstrap/bootstrap-icons.min.css')}}">
@endif

    <!-- Tabler Icon CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/tabler-icons/tabler-icons.css')}}">

@if (Route::is(['icon-remix']))
    <!-- Remix Icon CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/remix/fonts/remixicon.css')}}">
@endif

@if (!Route::is(['under-maintenance', 'under-construction', 'coming-soon', 'error-404', 'error-500', 'two-step-verification-3', 'two-step-verification-2', 'two-step-verification', 'email-verification-3', 'email-verification-2', 'email-verification', 'reset-password-3', 'reset-password-2', 'reset-password', 'forgot-password-3', 'forgot-password-2', 'forgot-password', 'register-3', 'register-2', 'register', 'login-3', 'login-2', 'login', 'success', 'success-2', 'success-3', 'lock-screen']))
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/select2/css/select2.min.css')}}">
@endif

@if (Route::is(['maps-leaflet']))
    <!-- Leaflet Maps CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/leaflet/leaflet.css')}}">
@endif

@if (Route::is(['maps-vector']))
    <!-- Jsvector Maps -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/jsvectormap/css/jsvectormap.min.css')}}">
@endif

@if (Route::is(['ui-scrollbar']))
    <!-- Main CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/scrollbar/scroll.min.css')}}">
@endif

@if (Route::is(['ui-stickynote']))
    <!-- Sticky CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/stickynote/sticky.css')}}">
@endif

    <!-- Fontawesome CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/fontawesome/css/fontawesome.min.css')}}">
    <link rel="stylesheet" href="{{URL::asset('build/plugins/fontawesome/css/all.min.css')}}">

@if (!Route::is(['under-maintenance', 'under-construction', 'coming-soon', 'error-404', 'error-500', 'two-step-verification-3', 'two-step-verification-2', 'two-step-verification', 'email-verification-3', 'email-verification-2', 'email-verification', 'reset-password-3', 'reset-password-2', 'reset-password', 'forgot-password-3', 'forgot-password-2', 'forgot-password', 'register-3', 'register-2', 'register', 'login-3', 'login-2', 'login', 'success', 'success-2', 'success-3', 'lock-screen']))
    <!-- Color Picker Css -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/flatpickr/flatpickr.min.css')}}">
    <link rel="stylesheet" href="{{URL::asset('build/plugins/@simonwep/pickr/themes/nano.min.css')}}">

    <!-- Daterangepikcer CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/daterangepicker/daterangepicker.css')}}">
@endif

@if (Route::is(['email-reply', 'email', 'notes', 'plugin', 'project-details', 'social-feed', 'task-details']))
    <!-- Owl carousel CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/css/owl.carousel.min.css')}}">
@endif

@if (!Route::is(['under-maintenance', 'under-construction', 'coming-soon', 'error-404', 'error-500', 'two-step-verification-3', 'two-step-verification-2', 'two-step-verification', 'email-verification-3', 'email-verification-2', 'email-verification', 'reset-password-3', 'reset-password-2', 'reset-password', 'forgot-password-3', 'forgot-password-2', 'forgot-password', 'register-3', 'register-2', 'register', 'login-3', 'login-2', 'login', 'success', 'success-2', 'success-3', 'lock-screen']))
    <!-- Datatable CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/css/dataTables.bootstrap5.min.css')}}">
@endif

@if (Route::is(['add-invoices', 'edit-invoices', 'file-manager', 'group-video-call', 'invoice-details', 'invoices', 'manage-jobs.php'  || $page === 'maps-vector', 'payslip', 'promotion', 'resignation', 'termination']))
    <!-- Player CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/css/plyr.css')}}">
@endif

@if (Route::is(['add-invoices', 'edit-invoices', 'file-manager', 'group-video-call', 'invoice-details', 'invoices', 'manage-jobs.php'  || $page === 'maps-vector', 'payslip', 'promotion', 'resignation', 'termination']))
    <!-- Owl Carousel -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/owlcarousel/owl.carousel.min.css')}}">
@endif


@if (!Route::is(['under-maintenance', 'under-construction', 'coming-soon', 'error-404', 'error-500', 'two-step-verification-3', 'two-step-verification-2', 'two-step-verification', 'email-verification-3', 'email-verification-2', 'email-verification', 'reset-password-3', 'reset-password-2', 'reset-password', 'forgot-password-3', 'forgot-password-2', 'forgot-password', 'register-3', 'register-2', 'register', 'login-3', 'login-2', 'login', 'success', 'success-2', 'success-3', 'lock-screen']))
    <!-- Summernote CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/summernote/summernote-lite.min.css')}}">

    <!-- Datetimepicker CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/css/bootstrap-datetimepicker.min.css')}}">
@endif

@if (Route::is(['ui-drag-drop', 'form-pickers', 'icon-bootstrap', 'icon-remix', 'icon-tabler', 'maps-leaflet', 'maps-vector', 'plugin', 'ui-clipboard', 'ui-sortable', 'ui-swiperjs']))
    <!-- Dragula CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/dragula/css/dragula.min.css')}}">
@endif

@if (Route::is(['ui-swiperjs', 'maps-google', 'ui-sortable']))
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/swiper/swiper-bundle.min.css')}}">
@endif

@if (Route::is(['chart-c3', 'plugin']))
    <!-- ChartC3 CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/c3-chart/c3.min.css')}}">
@endif

@if (!Route::is(['under-maintenance', 'under-construction', 'coming-soon', 'error-404', 'error-500', 'two-step-verification-3', 'two-step-verification-2', 'two-step-verification', 'email-verification-3', 'email-verification-2', 'email-verification', 'reset-password-3', 'reset-password-2', 'reset-password', 'forgot-password-3', 'forgot-password-2', 'forgot-password', 'register-3', 'register-2', 'register', 'login-3', 'login-2', 'login', 'success', 'success-2', 'success-3', 'lock-screen']))
    <!-- Bootstrap Tagsinput CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/bootstrap-tagsinput/bootstrap-tagsinput.css')}}">
@endif

@if (Route::is(['job-grid-2', 'job-list-2', 'plugin', 'ui-rangeslider', 'ui-rating']))
    <!-- Rangeslider CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/ion-rangeslider/css/ion.rangeSlider.min.css')}}">
@endif

@if (Route::is(['ui-lightbox']))
    <!-- Lightbox CSS -->
	<link rel="stylesheet" href="{{URL::asset('build/plugins/lightbox/glightbox.min.css')}}">
@endif

@if (Route::is(['icon-ionic', 'plugin']))
    <!-- Ionic CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/icons/ionic/ionicons.css')}}">
@endif

@if (Route::is(['chart-morris']))
    <!-- Morris CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/morris/morris.css')}}">
@endif

@if (Route::is(['icon-material']))
    <!-- Material CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/material/materialdesignicons.css')}}">
@endif

@if (Route::is(['icon-pe7']))
    <!-- Pe7 CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/icons/pe7/pe-icon-7.css')}}">
@endif

@if (Route::is(['icon-simpleline', 'plugin']))
    <!-- Material CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/simpleline/simple-line-icons.css')}}">
@endif

@if (Route::is(['icon-themify']))
    <!-- Pe7 CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/icons/themify/themify.css')}}">
@endif

@if (Route::is(['icon-typicon']))
    <!-- Typicons CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/icons/typicons/typicons.css')}}">
@endif

@if (Route::is(['icon-flag']))
    <!-- Flags CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/icons/flags/flags.css')}}">
@endif

@if (Route::is(['icon-weather']))
    <!-- Weather CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/plugins/icons/weather/weathericons.css')}}">
@endif

@if (Route::is(['form-pickers']))
    <link rel="stylesheet" href="{{URL::asset('build/plugins/flatpickr/flatpickr.css')}}">
    <link rel="stylesheet" href="{{URL::asset('build/plugins/bootstrap-datepicker/bootstrap-datepicker.css')}}">
    <link rel="stylesheet" href="{{URL::asset('build/plugins/jquery-timepicker/jquery-timepicker.css')}}">
    <link rel="stylesheet" href="{{URL::asset('build/plugins/pickr/pickr-themes.css')}}">
@endif

    <!-- Main CSS -->
    <link rel="stylesheet" href="{{URL::asset('build/css/style.css')}}">


