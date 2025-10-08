@extends('layouts.app')

@section('title', 'Create Campaign')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="max-w-3xl">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Create New Campaign</h3>
            <p class="text-gray-600">Generate AI-powered Google Ads campaigns (RSA & PMAX)</p>
        </div>

        <form method="POST" action="{{ route('tenant.campaigns.store') }}">
            @csrf

            <!-- Campaign Name -->
            <div class="mb-6">
                <label for="campaign_name" class="block text-sm font-medium text-gray-700 mb-2">
                    Campaign Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="campaign_name"
                    name="campaign_name"
                    value="{{ old('campaign_name') }}"
                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    placeholder="e.g., Summer Sale 2025 - Watches"
                    required
                >
                @error('campaign_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Campaign Type -->
            <div class="mb-6">
                <label for="campaign_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Campaign Type <span class="text-red-500">*</span>
                </label>
                <select
                    id="campaign_type"
                    name="campaign_type"
                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    required
                >
                    <option value="">Select campaign type...</option>
                    <option value="RSA" {{ old('campaign_type') === 'RSA' ? 'selected' : '' }}>RSA (Responsive Search Ads)</option>
                    <option value="PMAX" {{ old('campaign_type') === 'PMAX' ? 'selected' : '' }}>PMAX (Performance Max)</option>
                </select>
                @error('campaign_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">
                    RSA generates headlines and descriptions. PMAX generates comprehensive campaign assets.
                </p>
            </div>

            <!-- Business Description -->
            <div class="mb-6">
                <label for="business_description" class="block text-sm font-medium text-gray-700 mb-2">
                    Business Description <span class="text-red-500">*</span>
                </label>
                <textarea
                    id="business_description"
                    name="business_description"
                    rows="4"
                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Describe your business, products, or services. Be specific about what makes you unique."
                    required
                >{{ old('business_description') }}</textarea>
                @error('business_description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">
                    Example: "Luxury Swiss watches handmade by master craftsmen. Premium quality timepieces with lifetime warranty."
                </p>
            </div>

            <!-- Target Keywords -->
            <div class="mb-6">
                <label for="target_keywords" class="block text-sm font-medium text-gray-700 mb-2">
                    Target Keywords <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="target_keywords"
                    name="target_keywords"
                    value="{{ old('target_keywords') }}"
                    class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    placeholder="e.g., swiss watches, luxury timepieces, handmade watches"
                    required
                >
                @error('target_keywords')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">
                    Separate multiple keywords with commas. These will be used to optimize the campaign content.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-4 border-t">
                <a href="{{ route('tenant.campaigns.index') }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Campaigns
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                    <i class="fas fa-magic mr-2"></i>Generate Campaign
                </button>
            </div>
        </form>
    </div>

    <!-- Info Box -->
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-500"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">How it works</h3>
                <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc list-inside space-y-1">
                        <li>AI generates optimized campaign assets based on your input</li>
                        <li>RSA creates headlines and descriptions for search ads</li>
                        <li>PMAX creates comprehensive assets for Performance Max campaigns</li>
                        <li>All generated content is optimized for Google Ads best practices</li>
                    </ul>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>
@endsection
