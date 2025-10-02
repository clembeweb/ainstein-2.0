@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
<div class="px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">Dashboard</h1>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-500 text-sm">Total Tenants</h3>
            <p class="text-3xl font-bold">{{ $stats['total_tenants'] }}</p>
            <p class="text-sm text-green-600">{{ $stats['active_tenants'] }} active</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-500 text-sm">Total Users</h3>
            <p class="text-3xl font-bold">{{ $stats['total_users'] }}</p>
            <p class="text-sm text-green-600">{{ $stats['active_users'] }} active</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-500 text-sm">Token Usage</h3>
            <p class="text-3xl font-bold">{{ number_format($stats['total_tokens_used']) }}</p>
            <p class="text-sm text-gray-600">/ {{ number_format($stats['total_tokens_limit']) }}</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-500 text-sm">Generations</h3>
            <p class="text-3xl font-bold">{{ $stats['total_generations'] }}</p>
            <p class="text-sm text-blue-600">{{ $stats['today_generations'] }} today</p>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-bold mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ route('admin.users') }}" class="block p-4 border rounded hover:bg-gray-50">
                <h3 class="font-bold">👥 Manage Users</h3>
                <p class="text-sm text-gray-600">View and edit users</p>
            </a>
            <a href="{{ route('admin.tenants') }}" class="block p-4 border rounded hover:bg-gray-50">
                <h3 class="font-bold">🏢 Manage Tenants</h3>
                <p class="text-sm text-gray-600">View tenants and token usage</p>
            </a>
            <a href="{{ route('admin.settings') }}" class="block p-4 border rounded hover:bg-gray-50">
                <h3 class="font-bold">⚙️ Settings</h3>
                <p class="text-sm text-gray-600">Configure OpenAI API</p>
            </a>
        </div>
    </div>
</div>
@endsection
