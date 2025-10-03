# ⚙️ BILLING ADMIN SETTINGS - CONFIGURAZIONE COMPLETA

**Data**: 2025-10-03
**Configurazioni Confermate dall'Utente**

---

## 📋 CONFIGURAZIONI DEFINITE

Basato sulle tue risposte:

1. ✅ **Payment Provider**: STRIPE
2. ✅ **Pricing**: Definito da admin
3. ✅ **Currency**: Solo EUR
4. ✅ **Trial**: SÌ - Trial period configurabile
5. ✅ **Fatturazione Italia**: SÌ - SDI, P.IVA, CF
6. ✅ **Usage-Based Billing**: SÌ - Utente sceglie (piano fisso vs pay-as-you-go)
7. ✅ **Coupon/Promo**: SÌ - Previsto sistema coupon

**REQUISITO PRINCIPALE**: Tutte queste configurazioni devono essere gestibili da **Admin Panel → Billing Settings**

---

## 🗄️ DATABASE SCHEMA PER SETTINGS

### Nuova Tabella: `billing_settings`

```php
Schema::create('billing_settings', function (Blueprint $table) {
    $table->id();

    // Stripe Configuration
    $table->string('stripe_public_key')->nullable();
    $table->text('stripe_secret_key')->nullable(); // Encrypted
    $table->text('stripe_webhook_secret')->nullable(); // Encrypted
    $table->boolean('stripe_test_mode')->default(true);

    // Currency & Localization
    $table->string('default_currency', 3)->default('eur');
    $table->string('currency_locale')->default('it_IT');
    $table->json('supported_currencies')->nullable(); // ['eur', 'usd', 'gbp']

    // Trial Configuration
    $table->boolean('trial_enabled')->default(true);
    $table->integer('trial_days')->default(14);
    $table->boolean('trial_requires_card')->default(false);

    // Billing Model
    $table->enum('billing_model', ['fixed', 'usage_based', 'hybrid'])->default('fixed');
    $table->decimal('usage_price_per_1k_tokens', 8, 4)->default(0.1000); // €0.10 per 1k tokens

    // Italian E-Invoicing
    $table->boolean('einvoicing_enabled')->default(false);
    $table->string('einvoicing_provider')->nullable(); // 'fattureincloud', 'aruba', etc.
    $table->text('einvoicing_api_key')->nullable(); // Encrypted
    $table->string('company_vat')->nullable();
    $table->string('company_fiscal_code')->nullable();
    $table->string('company_sdi_code')->nullable();
    $table->string('company_pec')->nullable();

    // Coupon/Promo System
    $table->boolean('coupons_enabled')->default(true);
    $table->integer('max_discount_percentage')->default(100);

    // Payment Retry Policy
    $table->integer('payment_retry_attempts')->default(4);
    $table->json('payment_retry_days')->default(json_encode([3, 5, 7, 10]));

    // Grace Period & Downgrade
    $table->boolean('downgrade_grace_period')->default(true);
    $table->integer('grace_period_days')->default(3);

    // Notifications
    $table->boolean('notify_payment_failed')->default(true);
    $table->boolean('notify_trial_ending')->default(true);
    $table->integer('trial_ending_notice_days')->default(3);
    $table->boolean('notify_subscription_renewed')->default(true);

    // Refund Policy
    $table->enum('refund_policy', ['manual', 'auto_partial', 'auto_full'])->default('manual');
    $table->integer('refund_window_days')->default(30);

    $table->timestamps();
});
```

### Nuova Tabella: `coupons`

```php
Schema::create('coupons', function (Blueprint $table) {
    $table->ulid('id')->primary();

    // Basic Info
    $table->string('code')->unique(); // SUMMER2025, WELCOME50
    $table->string('name');
    $table->text('description')->nullable();

    // Discount Type
    $table->enum('type', ['percentage', 'fixed_amount']); // 20% or €10
    $table->decimal('value', 10, 2); // 20.00 or 10.00
    $table->string('currency', 3)->default('eur'); // Solo per fixed_amount

    // Duration
    $table->enum('duration', ['once', 'repeating', 'forever']);
    $table->integer('duration_months')->nullable(); // Se repeating

    // Restrictions
    $table->json('applicable_plans')->nullable(); // [plan_id1, plan_id2] or null = all
    $table->integer('max_redemptions')->nullable(); // Null = unlimited
    $table->integer('times_redeemed')->default(0);

    // Validity
    $table->timestamp('valid_from')->nullable();
    $table->timestamp('valid_until')->nullable();

    // Status
    $table->boolean('is_active')->default(true);

    // Stripe
    $table->string('stripe_coupon_id')->nullable();

    $table->timestamps();

    $table->index(['code', 'is_active']);
    $table->index(['valid_from', 'valid_until']);
});
```

### Nuova Tabella: `coupon_redemptions`

```php
Schema::create('coupon_redemptions', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->foreignUlid('coupon_id')->constrained()->onDelete('cascade');
    $table->foreignUlid('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignUlid('subscription_id')->nullable()->constrained();

    $table->timestamp('redeemed_at');

    $table->timestamps();

    $table->unique(['coupon_id', 'tenant_id']); // Un tenant può usare un coupon solo una volta
});
```

### Aggiungere a `tenants` table:

```php
Schema::table('tenants', function (Blueprint $table) {
    // Billing Model Choice
    $table->enum('billing_preference', ['fixed_plan', 'usage_based'])->default('fixed_plan');

    // Italian E-Invoicing
    $table->string('vat_number')->nullable();
    $table->string('fiscal_code')->nullable();
    $table->string('sdi_code')->nullable();
    $table->string('pec_email')->nullable();

    // Trial Tracking
    $table->timestamp('trial_starts_at')->nullable();
    $table->timestamp('trial_ends_at')->nullable();
    $table->boolean('trial_used')->default(false);
});
```

---

## 🎛️ ADMIN SETTINGS PAGE - UI COMPLETA

### Route

```php
// routes/admin.php
Route::prefix('billing')->name('admin.billing.')->group(function () {
    Route::get('/', [AdminBillingController::class, 'dashboard'])->name('dashboard');
    Route::get('/subscriptions', [AdminBillingController::class, 'subscriptions'])->name('subscriptions');
    Route::get('/invoices', [AdminBillingController::class, 'invoices'])->name('invoices');

    // Settings
    Route::get('/settings', [AdminBillingController::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminBillingController::class, 'updateSettings'])->name('settings.update');

    // Coupons Management
    Route::get('/coupons', [AdminBillingController::class, 'coupons'])->name('coupons');
    Route::get('/coupons/create', [AdminBillingController::class, 'createCoupon'])->name('coupons.create');
    Route::post('/coupons', [AdminBillingController::class, 'storeCoupon'])->name('coupons.store');
    Route::get('/coupons/{coupon}/edit', [AdminBillingController::class, 'editCoupon'])->name('coupons.edit');
    Route::put('/coupons/{coupon}', [AdminBillingController::class, 'updateCoupon'])->name('coupons.update');
    Route::delete('/coupons/{coupon}', [AdminBillingController::class, 'deleteCoupon'])->name('coupons.delete');
});
```

### Controller

```php
// app/Http/Controllers/AdminBillingController.php

public function settings()
{
    $settings = BillingSetting::firstOrCreate([]);

    return view('admin.billing.settings', compact('settings'));
}

public function updateSettings(Request $request)
{
    $validated = $request->validate([
        // Stripe
        'stripe_public_key' => 'nullable|string',
        'stripe_secret_key' => 'nullable|string',
        'stripe_webhook_secret' => 'nullable|string',
        'stripe_test_mode' => 'boolean',

        // Currency
        'default_currency' => 'required|in:eur,usd,gbp',
        'currency_locale' => 'required|string',

        // Trial
        'trial_enabled' => 'boolean',
        'trial_days' => 'required|integer|min:0|max:90',
        'trial_requires_card' => 'boolean',

        // Billing Model
        'billing_model' => 'required|in:fixed,usage_based,hybrid',
        'usage_price_per_1k_tokens' => 'required|numeric|min:0',

        // E-Invoicing
        'einvoicing_enabled' => 'boolean',
        'einvoicing_provider' => 'nullable|string',
        'einvoicing_api_key' => 'nullable|string',
        'company_vat' => 'nullable|string',
        'company_fiscal_code' => 'nullable|string',
        'company_sdi_code' => 'nullable|string',
        'company_pec' => 'nullable|email',

        // Coupons
        'coupons_enabled' => 'boolean',
        'max_discount_percentage' => 'required|integer|min:1|max:100',

        // Payment Retry
        'payment_retry_attempts' => 'required|integer|min:1|max:10',

        // Notifications
        'notify_payment_failed' => 'boolean',
        'notify_trial_ending' => 'boolean',
        'trial_ending_notice_days' => 'required|integer|min:1|max:14',
    ]);

    $settings = BillingSetting::firstOrCreate([]);

    // Encrypt sensitive data
    if ($request->filled('stripe_secret_key')) {
        $validated['stripe_secret_key'] = encrypt($request->stripe_secret_key);
    }

    if ($request->filled('stripe_webhook_secret')) {
        $validated['stripe_webhook_secret'] = encrypt($request->stripe_webhook_secret);
    }

    if ($request->filled('einvoicing_api_key')) {
        $validated['einvoicing_api_key'] = encrypt($request->einvoicing_api_key);
    }

    $settings->update($validated);

    return back()->with('success', 'Billing settings updated successfully');
}

// Coupons CRUD

public function coupons()
{
    $coupons = Coupon::withCount('redemptions')->latest()->paginate(20);

    return view('admin.billing.coupons.index', compact('coupons'));
}

public function createCoupon()
{
    $plans = Plan::where('is_active', true)->get();

    return view('admin.billing.coupons.create', compact('plans'));
}

public function storeCoupon(Request $request)
{
    $validated = $request->validate([
        'code' => 'required|string|unique:coupons,code|max:50',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'type' => 'required|in:percentage,fixed_amount',
        'value' => 'required|numeric|min:0.01',
        'duration' => 'required|in:once,repeating,forever',
        'duration_months' => 'required_if:duration,repeating|nullable|integer|min:1',
        'applicable_plans' => 'nullable|array',
        'max_redemptions' => 'nullable|integer|min:1',
        'valid_from' => 'nullable|date',
        'valid_until' => 'nullable|date|after:valid_from',
        'is_active' => 'boolean',
    ]);

    // Create on Stripe
    $stripeCoupon = \Stripe\Coupon::create([
        'id' => strtoupper($validated['code']),
        'name' => $validated['name'],
        $validated['type'] === 'percentage' ? 'percent_off' : 'amount_off' => $validated['value'],
        'currency' => 'eur',
        'duration' => $validated['duration'],
        'duration_in_months' => $validated['duration_months'] ?? null,
    ]);

    $validated['stripe_coupon_id'] = $stripeCoupon->id;
    $validated['code'] = strtoupper($validated['code']);

    Coupon::create($validated);

    return redirect()->route('admin.billing.coupons')
        ->with('success', 'Coupon created successfully');
}
```

### View: Billing Settings Page

```blade
{{-- resources/views/admin/billing/settings.blade.php --}}
@extends('admin.layout')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <h1 class="text-3xl font-bold mb-6">⚙️ Billing Settings</h1>

    <form method="POST" action="{{ route('admin.billing.settings.update') }}">
        @csrf

        <!-- Stripe Configuration -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-semibold">Stripe Configuration</h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Test Mode</label>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="stripe_test_mode" value="1"
                               {{ $settings->stripe_test_mode ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600">
                        <span class="ml-2 text-sm text-gray-600">Enable test mode (use test API keys)</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Publishable Key</label>
                    <input type="text" name="stripe_public_key"
                           value="{{ old('stripe_public_key', $settings->stripe_public_key) }}"
                           placeholder="pk_test_... or pk_live_..."
                           class="w-full px-4 py-2 border rounded-lg">
                    <p class="mt-1 text-xs text-gray-500">Get from Stripe Dashboard → Developers → API Keys</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Secret Key</label>
                    <input type="password" name="stripe_secret_key"
                           placeholder="sk_test_... or sk_live_..."
                           class="w-full px-4 py-2 border rounded-lg">
                    <p class="mt-1 text-xs text-gray-500">⚠️ Will be encrypted in database</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Webhook Secret</label>
                    <input type="password" name="stripe_webhook_secret"
                           placeholder="whsec_..."
                           class="w-full px-4 py-2 border rounded-lg">
                    <p class="mt-1 text-xs text-gray-500">Create webhook endpoint: {{ url('/api/stripe/webhook') }}</p>
                </div>
            </div>
        </div>

        <!-- Trial Configuration -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-semibold">Trial Period</h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="trial_enabled" value="1"
                               {{ $settings->trial_enabled ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600">
                        <span class="ml-2 text-sm font-medium">Enable free trial for new subscriptions</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Trial Duration (days)</label>
                    <input type="number" name="trial_days"
                           value="{{ old('trial_days', $settings->trial_days) }}"
                           min="0" max="90"
                           class="w-full px-4 py-2 border rounded-lg">
                </div>

                <div>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="trial_requires_card" value="1"
                               {{ $settings->trial_requires_card ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600">
                        <span class="ml-2 text-sm text-gray-600">Require credit card for trial</span>
                    </label>
                </div>

                <div>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="notify_trial_ending" value="1"
                               {{ $settings->notify_trial_ending ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600">
                        <span class="ml-2 text-sm text-gray-600">Notify users before trial ends</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Trial Ending Notice (days before)</label>
                    <input type="number" name="trial_ending_notice_days"
                           value="{{ old('trial_ending_notice_days', $settings->trial_ending_notice_days) }}"
                           min="1" max="14"
                           class="w-full px-4 py-2 border rounded-lg">
                </div>
            </div>
        </div>

        <!-- Billing Model -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-semibold">Billing Model</h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Default Model</label>
                    <select name="billing_model" class="w-full px-4 py-2 border rounded-lg">
                        <option value="fixed" {{ $settings->billing_model === 'fixed' ? 'selected' : '' }}>
                            Fixed Plans (monthly subscription)
                        </option>
                        <option value="usage_based" {{ $settings->billing_model === 'usage_based' ? 'selected' : '' }}>
                            Usage-Based (pay per token consumed)
                        </option>
                        <option value="hybrid" {{ $settings->billing_model === 'hybrid' ? 'selected' : '' }}>
                            Hybrid (plan + overage charges)
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">⚠️ Users can choose their preference if set to Hybrid</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Usage Price (per 1,000 tokens)</label>
                    <div class="flex items-center">
                        <span class="mr-2">€</span>
                        <input type="number" name="usage_price_per_1k_tokens"
                               value="{{ old('usage_price_per_1k_tokens', $settings->usage_price_per_1k_tokens) }}"
                               step="0.0001" min="0"
                               class="flex-1 px-4 py-2 border rounded-lg">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Used for usage-based and overage billing</p>
                </div>
            </div>
        </div>

        <!-- Italian E-Invoicing -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-semibold">🇮🇹 Italian E-Invoicing (Fatturazione Elettronica)</h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="einvoicing_enabled" value="1"
                               {{ $settings->einvoicing_enabled ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600"
                               onchange="document.getElementById('einvoicing_fields').style.display = this.checked ? 'block' : 'none'">
                        <span class="ml-2 text-sm font-medium">Enable Italian E-Invoicing (SDI)</span>
                    </label>
                </div>

                <div id="einvoicing_fields" style="display: {{ $settings->einvoicing_enabled ? 'block' : 'none' }};" class="space-y-4 pl-6 border-l-2 border-blue-200">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Provider</label>
                        <select name="einvoicing_provider" class="w-full px-4 py-2 border rounded-lg">
                            <option value="">Select provider...</option>
                            <option value="fattureincloud" {{ $settings->einvoicing_provider === 'fattureincloud' ? 'selected' : '' }}>
                                Fatture in Cloud
                            </option>
                            <option value="aruba" {{ $settings->einvoicing_provider === 'aruba' ? 'selected' : '' }}>
                                Aruba Fatturazione Elettronica
                            </option>
                            <option value="teamSystem" {{ $settings->einvoicing_provider === 'teamSystem' ? 'selected' : '' }}>
                                TeamSystem
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">API Key</label>
                        <input type="password" name="einvoicing_api_key"
                               placeholder="Provider API key"
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Company VAT Number</label>
                            <input type="text" name="company_vat"
                                   value="{{ old('company_vat', $settings->company_vat) }}"
                                   placeholder="IT12345678901"
                                   class="w-full px-4 py-2 border rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fiscal Code</label>
                            <input type="text" name="company_fiscal_code"
                                   value="{{ old('company_fiscal_code', $settings->company_fiscal_code) }}"
                                   placeholder="Codice Fiscale"
                                   class="w-full px-4 py-2 border rounded-lg">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">SDI Code</label>
                            <input type="text" name="company_sdi_code"
                                   value="{{ old('company_sdi_code', $settings->company_sdi_code) }}"
                                   placeholder="0000000"
                                   maxlength="7"
                                   class="w-full px-4 py-2 border rounded-lg">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">PEC Email</label>
                            <input type="email" name="company_pec"
                                   value="{{ old('company_pec', $settings->company_pec) }}"
                                   placeholder="company@pec.it"
                                   class="w-full px-4 py-2 border rounded-lg">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Coupons & Discounts -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-semibold">🎟️ Coupons & Discounts</h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="coupons_enabled" value="1"
                               {{ $settings->coupons_enabled ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600">
                        <span class="ml-2 text-sm font-medium">Enable coupon system</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Maximum Discount Percentage</label>
                    <input type="number" name="max_discount_percentage"
                           value="{{ old('max_discount_percentage', $settings->max_discount_percentage) }}"
                           min="1" max="100"
                           class="w-full px-4 py-2 border rounded-lg">
                    <p class="mt-1 text-xs text-gray-500">Prevent creating coupons with discount > this value</p>
                </div>

                <div>
                    <a href="{{ route('admin.billing.coupons') }}" class="text-blue-600 hover:text-blue-800">
                        → Manage Coupons
                    </a>
                </div>
            </div>
        </div>

        <!-- Payment Retry & Notifications -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-semibold">🔔 Notifications & Retry Policy</h2>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Retry Attempts</label>
                    <input type="number" name="payment_retry_attempts"
                           value="{{ old('payment_retry_attempts', $settings->payment_retry_attempts) }}"
                           min="1" max="10"
                           class="w-full px-4 py-2 border rounded-lg">
                    <p class="mt-1 text-xs text-gray-500">Stripe will retry failed payments (default: 4 attempts)</p>
                </div>

                <div>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="notify_payment_failed" value="1"
                               {{ $settings->notify_payment_failed ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600">
                        <span class="ml-2 text-sm text-gray-600">Email on failed payment</span>
                    </label>
                </div>

                <div>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="notify_subscription_renewed" value="1"
                               {{ $settings->notify_subscription_renewed ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600">
                        <span class="ml-2 text-sm text-gray-600">Email on successful renewal</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                💾 Save Billing Settings
            </button>
        </div>
    </form>
</div>
@endsection
```

---

## 🎟️ COUPON MANAGEMENT VIEWS

### Coupons List Page

```blade
{{-- resources/views/admin/billing/coupons/index.blade.php --}}
@extends('admin.layout')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">🎟️ Coupons</h1>
        <a href="{{ route('admin.billing.coupons.create') }}"
           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            + Create Coupon
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Redeemed</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valid Until</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($coupons as $coupon)
                <tr>
                    <td class="px-6 py-4">
                        <div class="font-mono font-bold">{{ $coupon->code }}</div>
                        <div class="text-sm text-gray-500">{{ $coupon->name }}</div>
                    </td>
                    <td class="px-6 py-4">
                        @if($coupon->type === 'percentage')
                            <span class="text-green-600 font-semibold">{{ $coupon->value }}% off</span>
                        @else
                            <span class="text-green-600 font-semibold">€{{ number_format($coupon->value, 2) }} off</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm">
                            @if($coupon->duration === 'once')
                                Once
                            @elseif($coupon->duration === 'forever')
                                Forever
                            @else
                                {{ $coupon->duration_months }} months
                            @endif
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm">
                            {{ $coupon->times_redeemed }}
                            @if($coupon->max_redemptions)
                                / {{ $coupon->max_redemptions }}
                            @endif
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        @if($coupon->valid_until)
                            <span class="text-sm">{{ $coupon->valid_until->format('M d, Y') }}</span>
                        @else
                            <span class="text-sm text-gray-400">No expiry</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($coupon->is_active)
                            <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">Active</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-800">Inactive</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <a href="{{ route('admin.billing.coupons.edit', $coupon) }}"
                           class="text-blue-600 hover:text-blue-800 mr-3">Edit</a>
                        <form method="POST" action="{{ route('admin.billing.coupons.delete', $coupon) }}"
                              class="inline"
                              onsubmit="return confirm('Delete this coupon?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No coupons created yet</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $coupons->links() }}
    </div>
</div>
@endsection
```

---

## 🔄 TENANT BILLING PREFERENCE

### Aggiungere a Tenant Settings

```blade
{{-- In resources/views/tenant/settings.blade.php --}}

<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-6 py-4 border-b">
        <h2 class="text-xl font-semibold">💳 Billing Preference</h2>
    </div>
    <div class="p-6">
        <form method="POST" action="{{ route('settings.billing-preference') }}">
            @csrf

            <label class="block text-sm font-medium text-gray-700 mb-3">How would you like to be billed?</label>

            <div class="space-y-3">
                <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" name="billing_preference" value="fixed_plan"
                           {{ $tenant->billing_preference === 'fixed_plan' ? 'checked' : '' }}
                           class="text-blue-600">
                    <div class="ml-3">
                        <div class="font-medium">Fixed Monthly Plan</div>
                        <div class="text-sm text-gray-500">Predictable monthly cost with token limits</div>
                    </div>
                </label>

                <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="radio" name="billing_preference" value="usage_based"
                           {{ $tenant->billing_preference === 'usage_based' ? 'checked' : '' }}
                           class="text-blue-600">
                    <div class="ml-3">
                        <div class="font-medium">Pay As You Go</div>
                        <div class="text-sm text-gray-500">Only pay for tokens you actually use (€{{ $billingSettings->usage_price_per_1k_tokens }} per 1k tokens)</div>
                    </div>
                </label>
            </div>

            <button type="submit" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Update Preference
            </button>
        </form>
    </div>
</div>
```

---

## 📝 SUMMARY

Con questa implementazione, l'admin può configurare **tutto da pannello**:

### ✅ Configurazioni Admin Settings

1. **Stripe**: API keys, test mode, webhook secret
2. **Trial**: Durata, richiede carta, notifiche
3. **Billing Model**: Fisso, usage-based, hybrid
4. **E-Invoicing Italia**: Provider, SDI, P.IVA, CF, PEC
5. **Coupons**: Abilitazione, limiti sconto
6. **Notifiche**: Email automatiche
7. **Retry Policy**: Tentativi pagamento

### ✅ Gestione Coupons

- CRUD completo da `/admin/billing/coupons`
- Percentuale o importo fisso
- Durata: once, repeating, forever
- Limiti redemption
- Validità temporale
- Applicabili a specifici piani

### ✅ Tenant Choice

- Tenant sceglie billing preference da settings
- Fixed plan vs Pay-as-you-go
- Fatturazione italiana opzionale (P.IVA, SDI)

---

Vuoi che proceda con l'implementazione completa? 🚀
