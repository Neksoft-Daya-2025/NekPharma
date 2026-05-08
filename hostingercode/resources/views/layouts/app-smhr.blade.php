<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials-smhr.title-meta')
    @include('partials-smhr.head-css')
</head>
<body>
    <div id="global-loader">
        <div class="page-loader"></div>
    </div>
    <div class="main-wrapper">
        @include('partials-smhr.topbar')
        @include('partials-smhr.sidebar')
        @include('partials-smhr.horizontal-sidebar')
        @include('partials-smhr.stocked-sidebar')
        @include('partials-smhr.twocolumn-sidebar')
        <div class="page-wrapper">
            <div class="content">
                @yield('content')
            </div>
        </div>
    </div>
    @include('partials-smhr.vendor-scripts')
</body>
</html>
