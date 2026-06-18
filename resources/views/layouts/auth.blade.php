<!doctype html>
<html class="no-js" lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'My-Task') }} - @yield('title', 'Authentication')</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    
    <!-- Project CSS file -->
    <link rel="stylesheet" href="{{ asset('assets/css/my-task.style.min.css') }}">
    
    @livewireStyles
</head>
<body data-mytask="theme-indigo">
    <div id="mytask-layout">
        <!-- main body area -->
        <div class="main p-2 py-3 p-xl-5">
            <!-- Body: Body -->
            <div class="body d-flex p-0 p-xl-5">
                <div class="container-xxl">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </div>

    <!-- Jquery Core Js -->
    <script src="{{ asset('assets/bundles/libscripts.bundle.js') }}"></script>
    
    @livewireScripts
    
    <!-- Toast notifications -->
    @if (session()->has('success'))
        <script>
            toastr.success('{{ session('success') }}');
        </script>
    @endif

    @if (session()->has('error'))
        <script>
            toastr.error('{{ session('error') }}');
        </script>
    @endif
</body>
</html>
