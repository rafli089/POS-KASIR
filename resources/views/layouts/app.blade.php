<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'POS') }} @isset($title) | {{ $title }} @endisset</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-[#F5F5F3] font-sans text-[#1F1F1F] antialiased">
    @if(request()->routeIs('login'))
        @yield('content')
    @else
        @include('partials.nav')
        <main class="container mx-auto px-4 py-6">
            @if(session('success'))
                <div class="bg-[#5F7D68] text-white rounded-lg p-4 mb-4 text-sm" x-data="{ show: true }" x-show="show" x-transition>
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-[#A65D5D] text-white rounded-lg p-4 mb-4 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            @yield('content')
        </main>

        @yield('scripts')
    @endif
</body>
</html>