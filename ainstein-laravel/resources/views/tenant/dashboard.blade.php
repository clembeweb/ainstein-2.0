@extends('layouts.app')

@section('title', 'Dashboard')

@push('scripts')
<script>
    console.log('🔍 Dashboard scripts loaded');
    console.log('🔍 Checking onboarding status...');
    @if(!Auth::user()->onboarding_completed)
        console.log('✅ User needs onboarding');
        console.log('🔍 Checking functions availability...');
        console.log('typeof autoStartOnboarding:', typeof autoStartOnboarding);
        console.log('typeof window.autoStartOnboarding:', typeof window.autoStartOnboarding);
        console.log('typeof Shepherd:', typeof Shepherd);

        // Wait for DOM and scripts to be fully loaded
        setTimeout(() => {
            console.log('⏰ Delayed check after 1 second...');
            console.log('typeof autoStartOnboarding:', typeof autoStartOnboarding);
            console.log('typeof window.autoStartOnboarding:', typeof window.autoStartOnboarding);

            if (typeof autoStartOnboarding !== 'undefined') {
                console.log('✅ Starting tour with autoStartOnboarding()...');
                autoStartOnboarding(true);
            } else if (typeof window.autoStartOnboarding !== 'undefined') {
                console.log('✅ Starting tour with window.autoStartOnboarding()...');
                window.autoStartOnboarding(true);
            } else {
                console.error('❌ autoStartOnboarding function not found!');
                console.log('Available window properties:', Object.keys(window).filter(k => k.toLowerCase().includes('onboarding') || k.includes('Shepherd')));
            }
        }, 1000);
    @else
        console.log('ℹ️ User already completed onboarding');
    @endif
</script>
@endpush

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Welcome Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Welcome back, {{ Auth::user()->name }}!</h1>
                        <p class="text-gray-600">{{ $tenant->name }} • {{ ucfirst($planInfo['name']) }} Plan</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <div class="text-sm text-gray-500">Token Usage</div>
                            <div class="text-lg font-semibold">{{ number_format($tokenStats['usage_percent'] ?? 0, 1) }}%</div>
                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $tokenStats['usage_percent'] ?? 0 }}%"></div>
                            </div>
                        </div>
                        <a href="{{ route('tenant.pages.create') }}" class="btn-primary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            New Page
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Total Pages -->
            <div class="stat-card">
                <div class="stat-card-content">
                    <div class="flex items-center">
                        <div class="stat-card-icon bg-blue-500 text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Pages</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $stats['total_pages'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Generations -->
            <div class="stat-card">
                <div class="stat-card-content">
                    <div class="flex items-center">
                        <div class="stat-card-icon bg-green-500 text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Generations</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $stats['total_generations'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Prompts -->
            <div class="stat-card">
                <div class="stat-card-content">
                    <div class="flex items-center">
                        <div class="stat-card-icon bg-purple-500 text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Prompts</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $stats['active_prompts'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- API Keys -->
            <div class="stat-card">
                <div class="stat-card-content">
                    <div class="flex items-center">
                        <div class="stat-card-icon bg-yellow-500 text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1721 9z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">API Keys</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $apiKeyStats['active_api_keys'] ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Recent Activity -->
            <div class="lg:col-span-2">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="card-header">
                        <h3 class="text-lg font-medium text-gray-900">Recent Pages</h3>
                    </div>
                    <div class="card-body">
                        @if($recentPages && $recentPages->count() > 0)
                            <div class="space-y-4">
                                @foreach($recentPages as $page)
                                    <div class="flex items-center justify-between p-4 border rounded-lg">
                                        <div class="flex-1">
                                            <div class="flex items-center">
                                                <h4 class="text-sm font-medium text-gray-900">{{ $page->url_path }}</h4>
                                                <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                    {{ $page->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                    {{ ucfirst($page->status) }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-500 mt-1">{{ $page->keyword }}</p>
                                            <p class="text-xs text-gray-400">{{ $page->generations_count ?? 0 }} generations</p>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('tenant.pages.show', $page) }}" class="text-blue-600 hover:text-blue-900">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </a>
                                            <a href="{{ route('tenant.pages.edit', $page) }}" class="text-gray-600 hover:text-gray-900">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-6">
                                <a href="{{ route('tenant.pages.index') }}" class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                    View all pages →
                                </a>
                            </div>
                        @else
                            <div class="text-center py-6">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No pages yet</h3>
                                <p class="mt-1 text-sm text-gray-500">Get started by creating your first page.</p>
                                <div class="mt-6">
                                    <a href="{{ route('tenant.pages.create') }}" class="btn-primary">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        New Page
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Recent Content Generations -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="card-header flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Recent Content Generations</h3>
                        <a href="{{ route('tenant.content.create') }}" class="btn-primary text-sm">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            GENERATE CONTENT
                        </a>
                    </div>
                    <div class="card-body">
                        @if($recentGenerations && $recentGenerations->count() > 0)
                            <div class="space-y-3">
                                @foreach($recentGenerations as $generation)
                                    <div class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                    @if($generation->status === 'completed') bg-green-100 text-green-800
                                                    @elseif($generation->status === 'processing') bg-yellow-100 text-yellow-800
                                                    @elseif($generation->status === 'failed') bg-red-100 text-red-800
                                                    @else bg-gray-100 text-gray-800 @endif">
                                                    {{ ucfirst($generation->status) }}
                                                </span>
                                                <span class="text-sm text-gray-600">{{ $generation->created_at->format('M j, Y g:i A') }}</span>
                                                @if($generation->status === 'completed' && $generation->tokens_used)
                                                    <span class="text-xs text-gray-500">{{ $generation->tokens_used }} tokens</span>
                                                @endif
                                            </div>
                                            @if($generation->status === 'completed' && $generation->generated_content)
                                                <p class="text-sm text-gray-700 mt-2 line-clamp-2">
                                                    {{ Str::limit($generation->generated_content, 150) }}
                                                </p>
                                            @elseif($generation->status === 'failed' && $generation->error_message)
                                                <p class="text-sm text-red-600 mt-2">{{ Str::limit($generation->error_message, 100) }}</p>
                                            @endif
                                        </div>
                                        <a href="{{ route('tenant.content.show', $generation) }}" class="text-blue-600 hover:text-blue-900 text-sm font-medium ml-4">
                                            View
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('tenant.content.index') }}" class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                    View all generations →
                                </a>
                            </div>
                        @else
                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No content generations yet</h3>
                                <p class="mt-1 text-sm text-gray-500">Start generating AI-powered content for your pages.</p>
                                <div class="mt-6">
                                    <a href="{{ route('tenant.content.create') }}" class="btn-primary">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Generate Content
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Plan Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="card-header">
                        <h3 class="text-lg font-medium text-gray-900">Plan Usage</h3>
                    </div>
                    <div class="card-body">
                        <div class="space-y-4">
                            <!-- Tokens -->
                            <div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Tokens</span>
                                    <span class="font-medium">{{ number_format($tokenStats['tokens_used'] ?? 0) }}/{{ number_format($planInfo['tokens_limit'] ?? 0) }}</span>
                                </div>
                                <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $tokenStats['usage_percent'] ?? 0 }}%"></div>
                                </div>
                            </div>

                            <!-- Pages -->
                            <div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Pages</span>
                                    <span class="font-medium">{{ $stats['total_pages'] ?? 0 }}/{{ $planInfo['pages_limit'] ?? 0 }}</span>
                                </div>
                                <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ $planInfo['pages_limit'] > 0 ? (($stats['total_pages'] ?? 0) / $planInfo['pages_limit']) * 100 : 0 }}%"></div>
                                </div>
                            </div>

                            <!-- API Keys -->
                            <div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">API Keys</span>
                                    <span class="font-medium">{{ $apiKeyStats['active_api_keys'] ?? 0 }}/{{ $planInfo['api_keys_limit'] ?? 0 }}</span>
                                </div>
                                <div class="mt-1 w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-yellow-600 h-2 rounded-full" style="width: {{ $planInfo['api_keys_limit'] > 0 ? (($apiKeyStats['active_api_keys'] ?? 0) / $planInfo['api_keys_limit']) * 100 : 0 }}%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <a href="{{ route('tenant.settings') }}" class="btn-secondary w-full text-center">
                                Upgrade Plan
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="card-header">
                        <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="space-y-3">
                            <a href="{{ route('tenant.pages.create') }}" class="flex items-center p-3 border rounded-lg hover:bg-gray-50">
                                <svg class="w-5 h-5 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                <span class="text-sm font-medium">Create New Page</span>
                            </a>

                            <a href="{{ route('tenant.generations') }}" class="flex items-center p-3 border rounded-lg hover:bg-gray-50">
                                <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span class="text-sm font-medium">Generate Content</span>
                            </a>

                            <a href="{{ route('tenant.api-keys.index') }}" class="flex items-center p-3 border rounded-lg hover:bg-gray-50">
                                <svg class="w-5 h-5 text-yellow-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1721 9z"></path>
                                </svg>
                                <span class="text-sm font-medium">Manage API Keys</span>
                            </a>

                            <a href="{{ route('tenant.prompts.index') }}" class="flex items-center p-3 border rounded-lg hover:bg-gray-50">
                                <svg class="w-5 h-5 text-purple-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                </svg>
                                <span class="text-sm font-medium">Browse Prompts</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection