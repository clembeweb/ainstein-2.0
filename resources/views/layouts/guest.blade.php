<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Ainstein Platform') }} - @yield('title', 'Welcome')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Additional Styles -->
    @stack('styles')
</head>
<body class="h-full">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-gray-50">
        <!-- Logo Section (optional) -->
        @if (!isset($hideLogo) || !$hideLogo)
            <div class="sm:mx-auto sm:w-full sm:max-w-md">
                <div class="mx-auto h-16 w-16 bg-amber-500 rounded-full flex items-center justify-center">
                    <i class="fas fa-brain text-white text-2xl"></i>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    {{ config('app.name', 'Ainstein Platform') }}
                </h2>
            </div>
        @endif

        <!-- Flash Messages -->
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            @if (session('status'))
                <div class="mb-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <!-- Main Content -->
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                @yield('content')
            </div>
        </div>

        <!-- Footer Links (optional) -->
        @if (isset($showFooter) && $showFooter)
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    @yield('footer')
                </p>
            </div>
        @endif
    </div>

    <!-- Additional Scripts -->
    @stack('scripts')
</body>
</html>
