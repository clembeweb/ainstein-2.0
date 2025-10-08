<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ auth()->user()->tenant->name ?? 'Ainstein' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 font-sans antialiased">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 flex items-center space-x-3">
                            @if(platform_logo_url())
                                <img src="{{ platform_logo_url() }}" alt="{{ platform_name() }}" class="h-8 w-auto">
                            @endif
                            <h1 class="text-xl font-semibold text-gray-900">
                                {{ platform_name() }}
                            </h1>
                        </div>
                        <nav class="hidden md:ml-6 md:flex md:space-x-8">
                            <a href="{{ route('tenant.dashboard') }}" class="px-3 py-2 text-sm font-medium {{ request()->routeIs('tenant.dashboard') ? 'text-gray-900 border-b-2 border-blue-600' : 'text-gray-700 hover:text-blue-600' }}">
                                Dashboard
                            </a>
                            <a href="{{ route('tenant.campaigns.index') }}" class="px-3 py-2 text-sm font-medium {{ request()->routeIs('tenant.campaigns.*') ? 'text-gray-900 border-b-2 border-blue-600' : 'text-gray-700 hover:text-blue-600' }}">
                                Campaigns
                            </a>
                            <a href="{{ route('tenant.pages.index') }}" class="px-3 py-2 text-sm font-medium {{ request()->routeIs('tenant.pages.*') ? 'text-gray-900 border-b-2 border-blue-600' : 'text-gray-700 hover:text-blue-600' }}">
                                Pages
                            </a>
                            <a href="{{ route('tenant.generations') }}" class="px-3 py-2 text-sm font-medium {{ request()->routeIs('tenant.generations') || request()->routeIs('tenant.generation.*') ? 'text-gray-900 border-b-2 border-blue-600' : 'text-gray-700 hover:text-blue-600' }}">
                                Content
                            </a>
                            <a href="{{ route('tenant.api-keys.index') }}" class="px-3 py-2 text-sm font-medium {{ request()->routeIs('tenant.api-keys.*') ? 'text-gray-900 border-b-2 border-blue-600' : 'text-gray-700 hover:text-blue-600' }}">
                                API Keys
                            </a>
                        </nav>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-sm text-gray-500">
                            Plan: {{ ucfirst(auth()->user()->tenant->plan_type) }}
                        </div>
                        <div class="text-sm text-gray-500">
                            Tokens: {{ number_format(auth()->user()->tenant->tokens_used_current) }}/{{ number_format(auth()->user()->tenant->tokens_monthly_limit) }}
                        </div>
                        <div class="relative">
                            <button class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <span class="ml-3 text-gray-700">{{ auth()->user()->name }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @if(session('success'))
                    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>