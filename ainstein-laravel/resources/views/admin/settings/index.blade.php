<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Platform Settings - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-gray-900">Platform Settings</h1>
                    <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-700">← Back to Dashboard</a>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
            <!-- Success Message -->
            @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
            @endif

            <!-- Validation Errors -->
            @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Tabs Navigation -->
            <div class="bg-white shadow rounded-lg" x-data="{ activeTab: 'oauth' }">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8 px-6">
                        <button @click="activeTab = 'oauth'"
                                :class="activeTab === 'oauth' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            OAuth Integrations
                        </button>
                        <button @click="activeTab = 'openai'"
                                :class="activeTab === 'openai' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            OpenAI Configuration
                        </button>
                        <button @click="activeTab = 'stripe'"
                                :class="activeTab === 'stripe' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Stripe Billing
                        </button>
                        <button @click="activeTab = 'email'"
                                :class="activeTab === 'email' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Email SMTP
                        </button>
                        <button @click="activeTab = 'branding'"
                                :class="activeTab === 'branding' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Logo & Branding
                        </button>
                        <button @click="activeTab = 'advanced'"
                                :class="activeTab === 'advanced' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Advanced
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- OAuth Integrations Tab -->
                    <div x-show="activeTab === 'oauth'">
                        <form action="{{ route('admin.settings.oauth.update') }}" method="POST" class="space-y-6">
                            @csrf

                            <!-- Google Ads -->
                            <div class="border border-gray-200 rounded-lg p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Google Ads OAuth</h3>
                                    @if($googleAdsConfigured)
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">✓ Configured</span>
                                    @else
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">Not configured</span>
                                    @endif
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Client ID</label>
                                        <input type="text" name="google_ads_client_id"
                                               value="{{ old('google_ads_client_id', $settings->google_ads_client_id) }}"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               placeholder="123456789-xxxxx.apps.googleusercontent.com">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Client Secret</label>
                                        <input type="password" name="google_ads_client_secret"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               placeholder="GOCSPX-xxxxxxxxxxxxx">
                                    </div>
                                </div>

                                <p class="text-xs text-gray-500 mt-3">
                                    Get credentials from <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-blue-600 underline">Google Cloud Console</a>
                                </p>
                            </div>

                            <!-- Facebook -->
                            <div class="border border-gray-200 rounded-lg p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Facebook OAuth</h3>
                                    @if($facebookConfigured)
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">✓ Configured</span>
                                    @else
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">Not configured</span>
                                    @endif
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">App ID</label>
                                        <input type="text" name="facebook_app_id"
                                               value="{{ old('facebook_app_id', $settings->facebook_app_id) }}"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               placeholder="1234567890123456">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">App Secret</label>
                                        <input type="password" name="facebook_app_secret"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                                    </div>
                                </div>

                                <p class="text-xs text-gray-500 mt-3">
                                    Get credentials from <a href="https://developers.facebook.com/apps" target="_blank" class="text-blue-600 underline">Facebook Developers</a>
                                </p>
                            </div>

                            <!-- Google Search Console -->
                            <div class="border border-gray-200 rounded-lg p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Google Search Console OAuth</h3>
                                    @if($googleConsoleConfigured)
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">✓ Configured</span>
                                    @else
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">Not configured</span>
                                    @endif
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Client ID</label>
                                        <input type="text" name="google_console_client_id"
                                               value="{{ old('google_console_client_id', $settings->google_console_client_id) }}"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               placeholder="123456789-xxxxx.apps.googleusercontent.com">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Client Secret</label>
                                        <input type="password" name="google_console_client_secret"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               placeholder="GOCSPX-xxxxxxxxxxxxx">
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    Save OAuth Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- OpenAI Configuration Tab -->
                    <div x-show="activeTab === 'openai'">
                        <form action="{{ route('admin.settings.openai.update') }}" method="POST" class="space-y-6">
                            @csrf

                            <div class="border border-gray-200 rounded-lg p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">OpenAI API Configuration</h3>
                                    <div class="flex items-center space-x-3">
                                        @if($openAiConfigured)
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">✓ Configured</span>
                                            <button type="button" onclick="testOpenAI()" class="px-4 py-2 text-sm bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200">
                                                Test Connection
                                            </button>
                                        @else
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">⚠ Required</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">API Key *</label>
                                        <input type="password" name="openai_api_key"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               placeholder="sk-proj-xxxxxxxxxxxxx" required>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Organization ID (Optional)</label>
                                            <input type="text" name="openai_organization_id"
                                                   value="{{ old('openai_organization_id', $settings->openai_organization_id) }}"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   placeholder="org-xxxxxxxxxxxxx">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Default Model *</label>
                                            <select name="openai_default_model" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                                <option value="gpt-4o" {{ ($settings->openai_default_model ?? 'gpt-4o-mini') == 'gpt-4o' ? 'selected' : '' }}>GPT-4o (Recommended)</option>
                                                <option value="gpt-4o-mini" {{ ($settings->openai_default_model ?? 'gpt-4o-mini') == 'gpt-4o-mini' ? 'selected' : '' }}>GPT-4o Mini (Fast)</option>
                                                <option value="gpt-4" {{ ($settings->openai_default_model ?? 'gpt-4o-mini') == 'gpt-4' ? 'selected' : '' }}>GPT-4</option>
                                                <option value="gpt-3.5-turbo" {{ ($settings->openai_default_model ?? 'gpt-4o-mini') == 'gpt-3.5-turbo' ? 'selected' : '' }}>GPT-3.5 Turbo</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Max Tokens *</label>
                                            <input type="number" name="openai_max_tokens"
                                                   value="{{ old('openai_max_tokens', $settings->openai_max_tokens ?? 2000) }}"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   min="100" max="4000" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Temperature *</label>
                                            <input type="number" name="openai_temperature"
                                                   value="{{ old('openai_temperature', $settings->openai_temperature ?? 0.7) }}"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   min="0" max="2" step="0.1" required>
                                            <p class="text-xs text-gray-500 mt-1">0 = Deterministic, 2 = Very Creative</p>
                                        </div>
                                    </div>
                                </div>

                                <p class="text-xs text-gray-500 mt-4">
                                    Get API key from <a href="https://platform.openai.com/api-keys" target="_blank" class="text-blue-600 underline">OpenAI Platform</a>
                                </p>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    Save OpenAI Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Stripe Billing Tab -->
                    <div x-show="activeTab === 'stripe'">
                        <form action="{{ route('admin.settings.stripe.update') }}" method="POST" class="space-y-6">
                            @csrf

                            <div class="border border-gray-200 rounded-lg p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Stripe API Configuration</h3>
                                    <div class="flex items-center space-x-3">
                                        @if($stripeConfigured)
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">✓ Configured</span>
                                            <button type="button" onclick="testStripe()" class="px-4 py-2 text-sm bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200">
                                                Test Connection
                                            </button>
                                        @else
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-600">Not configured</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Publishable Key *</label>
                                            <input type="text" name="stripe_public_key"
                                                   value="{{ old('stripe_public_key', $settings->stripe_public_key) }}"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   placeholder="pk_test_xxxxxxxxxxxxx" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Secret Key *</label>
                                            <input type="password" name="stripe_secret_key"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   placeholder="sk_test_xxxxxxxxxxxxx" required>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Webhook Secret (Optional)</label>
                                        <input type="password" name="stripe_webhook_secret"
                                               value="{{ old('stripe_webhook_secret', $settings->stripe_webhook_secret) }}"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               placeholder="whsec_xxxxxxxxxxxxx">
                                    </div>

                                    <div>
                                        <label class="flex items-center space-x-3">
                                            <input type="checkbox" name="stripe_test_mode" value="1"
                                                   {{ ($settings->stripe_test_mode ?? true) ? 'checked' : '' }}
                                                   class="h-5 w-5 text-blue-600 border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                            <span class="text-sm font-medium text-gray-700">Test Mode (Use test keys)</span>
                                        </label>
                                    </div>
                                </div>

                                <p class="text-xs text-gray-500 mt-4">
                                    Get API keys from <a href="https://dashboard.stripe.com/apikeys" target="_blank" class="text-blue-600 underline">Stripe Dashboard</a>
                                </p>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    Save Stripe Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Email SMTP Tab -->
                    <div x-show="activeTab === 'email'">
                        <form action="{{ route('admin.settings.email.update') }}" method="POST" class="space-y-6">
                            @csrf

                            <div class="border border-gray-200 rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">SMTP Configuration</h3>

                                <div class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Host *</label>
                                            <input type="text" name="smtp_host"
                                                   value="{{ old('smtp_host', $settings->smtp_host) }}"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   placeholder="smtp.gmail.com" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Port *</label>
                                            <input type="number" name="smtp_port"
                                                   value="{{ old('smtp_port', $settings->smtp_port ?? 587) }}"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   placeholder="587" min="1" max="65535" required>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Username *</label>
                                            <input type="text" name="smtp_username"
                                                   value="{{ old('smtp_username', $settings->smtp_username) }}"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   placeholder="user@example.com" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Password *</label>
                                            <input type="password" name="smtp_password"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   placeholder="••••••••" required>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Encryption *</label>
                                        <select name="smtp_encryption" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                            <option value="tls" {{ ($settings->smtp_encryption ?? 'tls') == 'tls' ? 'selected' : '' }}>TLS (Port 587)</option>
                                            <option value="ssl" {{ ($settings->smtp_encryption ?? 'tls') == 'ssl' ? 'selected' : '' }}>SSL (Port 465)</option>
                                        </select>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">From Email Address *</label>
                                            <input type="email" name="mail_from_address"
                                                   value="{{ old('mail_from_address', $settings->mail_from_address) }}"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   placeholder="noreply@example.com" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">From Name *</label>
                                            <input type="text" name="mail_from_name"
                                                   value="{{ old('mail_from_name', $settings->mail_from_name) }}"
                                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   placeholder="Ainstein Platform" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    Save Email Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Logo & Branding Tab -->
                    <div x-show="activeTab === 'branding'">
                        <div class="border border-gray-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Platform Logo</h3>

                            @if($settings->platform_logo_path)
                            <div class="mb-6">
                                <p class="text-sm text-gray-600 mb-3">Current Logo:</p>
                                <div class="flex items-center space-x-4">
                                    <img src="{{ Storage::url($settings->platform_logo_path) }}" alt="Platform Logo" class="h-20 w-auto border border-gray-200 rounded">
                                    <form action="{{ route('admin.settings.logo.delete') }}" method="POST" onsubmit="return confirm('Are you sure you want to delete the logo?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200">
                                            Delete Logo
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @endif

                            <form action="{{ route('admin.settings.logo.upload') }}" method="POST" enctype="multipart/form-data">
                                @csrf

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload New Logo</label>
                                    <input type="file" name="logo" accept="image/jpeg,image/png,image/svg+xml"
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                    <p class="text-xs text-gray-500 mt-2">Supported formats: JPEG, PNG, SVG (Max 2MB). Recommended size: 256x256px or larger.</p>
                                    <p class="text-xs text-gray-500">The system will automatically generate 3 sizes: Original, Small (64x64), and Favicon (32x32).</p>
                                </div>

                                <div class="mt-6">
                                    <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        Upload Logo
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Advanced Settings Tab -->
                    <div x-show="activeTab === 'advanced'">
                        <form action="{{ route('admin.settings.advanced.update') }}" method="POST" class="space-y-6">
                            @csrf

                            <!-- Cache Configuration -->
                            <div class="border border-gray-200 rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Cache Configuration</h3>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Cache Driver *</label>
                                        <select name="cache_driver" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                            <option value="redis" {{ ($settings->cache_driver ?? 'redis') == 'redis' ? 'selected' : '' }}>Redis (Recommended)</option>
                                            <option value="file" {{ ($settings->cache_driver ?? 'redis') == 'file' ? 'selected' : '' }}>File</option>
                                            <option value="database" {{ ($settings->cache_driver ?? 'redis') == 'database' ? 'selected' : '' }}>Database</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Default TTL (seconds) *</label>
                                        <input type="number" name="cache_default_ttl"
                                               value="{{ old('cache_default_ttl', $settings->cache_default_ttl ?? 3600) }}"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               min="60" required>
                                        <p class="text-xs text-gray-500 mt-1">Default: 3600 (1 hour)</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Queue Configuration -->
                            <div class="border border-gray-200 rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Queue Configuration</h3>

                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Queue Driver *</label>
                                        <select name="queue_driver" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                            <option value="redis" {{ ($settings->queue_driver ?? 'redis') == 'redis' ? 'selected' : '' }}>Redis (Recommended)</option>
                                            <option value="database" {{ ($settings->queue_driver ?? 'redis') == 'database' ? 'selected' : '' }}>Database</option>
                                            <option value="sync" {{ ($settings->queue_driver ?? 'redis') == 'sync' ? 'selected' : '' }}>Sync (No Queue)</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Retry After (seconds) *</label>
                                        <input type="number" name="queue_retry_after"
                                               value="{{ old('queue_retry_after', $settings->queue_retry_after ?? 90) }}"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               min="30" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Max Tries *</label>
                                        <input type="number" name="queue_max_tries"
                                               value="{{ old('queue_max_tries', $settings->queue_max_tries ?? 3) }}"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               min="1" max="10" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Rate Limiting -->
                            <div class="border border-gray-200 rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Rate Limiting</h3>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">API Requests per Minute *</label>
                                        <input type="number" name="rate_limit_per_minute"
                                               value="{{ old('rate_limit_per_minute', $settings->rate_limit_per_minute ?? 60) }}"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               min="10" max="1000" required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">AI Requests per Hour *</label>
                                        <input type="number" name="rate_limit_ai_per_hour"
                                               value="{{ old('rate_limit_ai_per_hour', $settings->rate_limit_ai_per_hour ?? 100) }}"
                                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                               min="10" max="10000" required>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    Save Advanced Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function testOpenAI() {
        fetch('{{ route("admin.settings.openai.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✓ ' + data.message);
            } else {
                alert('✗ ' + data.message);
            }
        })
        .catch(error => {
            alert('✗ Error testing OpenAI connection');
        });
    }

    function testStripe() {
        fetch('{{ route("admin.settings.stripe.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✓ ' + data.message);
            } else {
                alert('✗ ' + data.message);
            }
        })
        .catch(error => {
            alert('✗ Error testing Stripe connection');
        });
    }
    </script>
</body>
</html>
