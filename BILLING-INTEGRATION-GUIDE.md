# 💳 GUIDA INTEGRAZIONE BILLING - AINSTEIN PLATFORM

**Data Creazione**: 2025-10-03
**Versione**: 1.0
**Status**: 📋 PRONTO PER IMPLEMENTAZIONE

---

## 📊 INDICE

1. [Panoramica Sistema](#panoramica-sistema)
2. [Architettura Proposta](#architettura-proposta)
3. [Schema Database](#schema-database)
4. [Flusso Utente](#flusso-utente)
5. [Implementazione Step-by-Step](#implementazione-step-by-step)
6. [Interfacce Utente](#interfacce-utente)
7. [Testing](#testing)
8. [Sicurezza e Compliance](#sicurezza-e-compliance)
9. [Configurazione Prezzi](#configurazione-prezzi)
10. [FAQ e Decisioni](#faq-e-decisioni)

---

## 🎯 PANORAMICA SISTEMA

### Stato Attuale

**Già presente nel progetto:**
- ✅ Tabella `plans` (con pricing mensile/annuale)
- ✅ Tenant model con campi `stripe_customer_id`, `stripe_subscription_id`
- ✅ Sistema piani: free, basic, pro, enterprise
- ✅ Token tracking e limiti per tenant
- ✅ Sistema multi-tenant funzionante

**Cosa manca:**
- ❌ Integrazione payment gateway (Stripe)
- ❌ Tabelle subscriptions, invoices, payment_methods
- ❌ Webhook handlers per eventi pagamento
- ❌ UI per gestione billing (admin + tenant)
- ❌ Notifiche pagamenti falliti
- ❌ Reports finanziari

---

## 🏗️ ARCHITETTURA PROPOSTA

### Payment Provider: STRIPE (Consigliato)

**Motivi della scelta:**
- ✅ Più popolare e documentato (99% marketplace)
- ✅ SDK PHP robusto (`stripe/stripe-php`)
- ✅ Webhook affidabili con retry automatico
- ✅ Subscription management completo
- ✅ Supporto SCA (Strong Customer Authentication) EU
- ✅ Dashboard completa per monitoraggio
- ✅ Test mode semplice con webhook CLI
- ✅ Supporto multi-currency (EUR, USD, GBP)

**Alternative considerate:**
- Paddle: Più semplice ma meno flessibile, merchant of record
- Lemon Squeezy: Ottimo per prodotti digitali, fees più alte
- PayPal: Meno affidabile per subscriptions

### Stack Tecnologico

```
Laravel 12.31.1
├── Stripe PHP SDK (stripe/stripe-php)
├── Laravel Cashier (opzionale, helper per Stripe)
├── Queue System (per webhook processing)
└── Email Notifications (pagamenti)
```

---

## 📊 SCHEMA DATABASE

### Tabelle da Creare

#### 1. `subscriptions`
Gestisce gli abbonamenti attivi dei tenant.

```php
Schema::create('subscriptions', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->foreignUlid('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignUlid('plan_id')->constrained();

    // Stripe data
    $table->string('stripe_subscription_id')->unique();
    $table->string('stripe_status'); // active, canceled, incomplete, past_due, trialing

    // Billing cycle
    $table->timestamp('current_period_start');
    $table->timestamp('current_period_end');
    $table->timestamp('trial_ends_at')->nullable();

    // Cancellation
    $table->timestamp('canceled_at')->nullable();
    $table->timestamp('ends_at')->nullable(); // When access ends

    $table->timestamps();

    $table->index(['tenant_id', 'stripe_status']);
    $table->index('current_period_end');
});
```

#### 2. `invoices`
Storico fatture generate per ogni tenant.

```php
Schema::create('invoices', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->foreignUlid('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignUlid('subscription_id')->nullable()->constrained();

    // Stripe data
    $table->string('stripe_invoice_id')->unique();
    $table->string('stripe_payment_intent_id')->nullable();

    // Invoice details
    $table->decimal('amount', 10, 2);
    $table->string('currency', 3)->default('eur');
    $table->string('status'); // draft, open, paid, void, uncollectible
    $table->string('billing_reason')->nullable(); // subscription_create, subscription_cycle, etc.

    // URLs
    $table->text('invoice_pdf')->nullable();
    $table->text('hosted_invoice_url')->nullable();

    // Payment date
    $table->timestamp('paid_at')->nullable();

    $table->timestamps();

    $table->index(['tenant_id', 'status']);
    $table->index('paid_at');
});
```

#### 3. `payment_methods`
Metodi di pagamento salvati (carte, SEPA, etc.).

```php
Schema::create('payment_methods', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->foreignUlid('tenant_id')->constrained()->onDelete('cascade');

    // Stripe data
    $table->string('stripe_payment_method_id')->unique();
    $table->string('type'); // card, sepa_debit, paypal

    // Card details (se type = card)
    $table->string('card_brand')->nullable(); // visa, mastercard, amex
    $table->string('card_last_four')->nullable();
    $table->date('card_expires_at')->nullable();

    // Status
    $table->boolean('is_default')->default(false);

    $table->timestamps();

    $table->index(['tenant_id', 'is_default']);
});
```

#### 4. `billing_events`
Log di tutti gli eventi webhook Stripe (per debugging e audit).

```php
Schema::create('billing_events', function (Blueprint $table) {
    $table->ulid('id')->primary();
    $table->foreignUlid('tenant_id')->nullable()->constrained()->onDelete('cascade');

    // Event data
    $table->string('type'); // invoice.paid, subscription.created, etc.
    $table->string('stripe_event_id')->unique();
    $table->json('payload');

    // Processing
    $table->timestamp('processed_at')->nullable();
    $table->text('error')->nullable();

    $table->timestamps();

    $table->index(['type', 'created_at']);
    $table->index('processed_at');
});
```

#### 5. Modifica `tenants` table (già presenti campi Stripe)

```php
// GIÀ PRESENTI in migrazione esistente:
$table->string('stripe_customer_id')->nullable();
$table->string('stripe_subscription_id')->nullable();

// DA AGGIUNGERE (opzionale):
$table->decimal('balance', 10, 2)->default(0); // Account balance/credits
$table->string('billing_email')->nullable(); // Email fatturazione separata
$table->json('tax_ids')->nullable(); // VAT numbers, etc.
```

### Relazioni Eloquent

```php
// Tenant.php
public function subscription() {
    return $this->hasOne(Subscription::class)->latest();
}

public function subscriptions() {
    return $this->hasMany(Subscription::class);
}

public function invoices() {
    return $this->hasMany(Invoice::class);
}

public function paymentMethods() {
    return $this->hasMany(PaymentMethod::class);
}

public function defaultPaymentMethod() {
    return $this->hasOne(PaymentMethod::class)->where('is_default', true);
}

// Subscription.php
public function tenant() {
    return $this->belongsTo(Tenant::class);
}

public function plan() {
    return $this->belongsTo(Plan::class);
}

public function invoices() {
    return $this->hasMany(Invoice::class);
}

// Invoice.php
public function tenant() {
    return $this->belongsTo(Tenant::class);
}

public function subscription() {
    return $this->belongsTo(Subscription::class);
}
```

---

## 🔄 FLUSSO UTENTE COMPLETO

### Scenario 1: Registrazione e Trial

```
1. Nuovo utente si registra
   ↓
2. Sistema crea Tenant con plan_type='free'
   ↓
3. Tenant usa piattaforma (10,000 tokens free)
   ↓
4. Decide di fare upgrade
```

### Scenario 2: Upgrade a Piano Pagato

```
1. Tenant va su /dashboard/billing
   ↓
2. Click "Upgrade to Pro" (€99/mese)
   ↓
3. Redirect a Stripe Checkout Session
   ↓
4. Inserisce carta di credito
   ↓
5. Stripe processa pagamento
   ↓
6. Webhook 'checkout.session.completed' ricevuto
   ↓
7. Sistema crea:
   - Stripe Customer (se non esiste)
   - Stripe Subscription
   - Record in 'subscriptions' table
   - Record in 'invoices' table (prima fattura)
   ↓
8. Aggiorna Tenant:
   - plan_type = 'pro'
   - tokens_monthly_limit = 200,000
   - stripe_customer_id
   - stripe_subscription_id
   ↓
9. Redirect a /dashboard/billing con messaggio successo
   ↓
10. Email conferma inviata
```

### Scenario 3: Pagamento Ricorrente Mensile

```
1. Stripe addebita automaticamente dopo 30 giorni
   ↓
2. Webhook 'invoice.payment_succeeded' ricevuto
   ↓
3. Sistema:
   - Crea nuovo record Invoice con status='paid'
   - Aggiorna subscription.current_period_end
   - Reset tokens_used_current = 0
   ↓
4. Email con fattura PDF inviata a tenant
```

### Scenario 4: Pagamento Fallito

```
1. Stripe tenta addebito → FAIL
   ↓
2. Webhook 'invoice.payment_failed' ricevuto
   ↓
3. Sistema:
   - Crea Invoice con status='open'
   - Marca subscription.stripe_status = 'past_due'
   ↓
4. Email urgente inviata: "Update payment method"
   ↓
5. Stripe riprova automaticamente dopo 3, 5, 7 giorni
   ↓
6. Se fallisce dopo 4 tentativi:
   - Webhook 'customer.subscription.deleted'
   - Subscription cancellata
   - Tenant downgrade a 'free' plan
```

### Scenario 5: Cancellazione Abbonamento

```
1. Tenant va su /dashboard/billing
   ↓
2. Click "Cancel Subscription"
   ↓
3. Modale conferma: "Access until [end_date]"
   ↓
4. Conferma cancellazione
   ↓
5. API call a Stripe: cancelSubscription(at_period_end=true)
   ↓
6. Webhook 'customer.subscription.updated' (cancel_at_period_end=true)
   ↓
7. Sistema:
   - Aggiorna subscription.canceled_at = now()
   - Aggiorna subscription.ends_at = current_period_end
   ↓
8. Tenant continua ad usare fino a ends_at
   ↓
9. Quando arriva ends_at:
   - Webhook 'customer.subscription.deleted'
   - Downgrade automatico a 'free'
   - tokens_monthly_limit = 10,000
```

### Scenario 6: Upgrade/Downgrade Piano

```
UPGRADE (Basic → Pro):
1. Tenant click "Upgrade to Pro"
   ↓
2. API Stripe: updateSubscription(new_plan_id)
   ↓
3. Stripe calcola prorata (credito periodo rimasto)
   ↓
4. Addebita differenza immediatamente
   ↓
5. Webhook 'customer.subscription.updated'
   ↓
6. Sistema aggiorna plan_type e limiti
   ↓
7. Tenant ha subito 200,000 tokens

DOWNGRADE (Pro → Basic):
1. Tenant click "Downgrade to Basic"
   ↓
2. API Stripe: updateSubscription(new_plan_id, proration_behavior='none')
   ↓
3. Downgrade effettivo a fine periodo corrente
   ↓
4. Sistema mostra: "Downgrade scheduled for [date]"
   ↓
5. A fine periodo: webhook aggiorna piano
```

---

## 🛠️ IMPLEMENTAZIONE STEP-BY-STEP

### FASE 1: Setup Base (2-3 ore)

#### Step 1.1: Installazione Package

```bash
cd ainstein-laravel

# Stripe SDK
composer require stripe/stripe-php

# Laravel Cashier (opzionale, semplifica integrazione)
composer require laravel/cashier

# Pubblica config Cashier
php artisan vendor:publish --tag="cashier-config"
php artisan vendor:publish --tag="cashier-migrations"
```

#### Step 1.2: Configurazione .env

```env
# .env
STRIPE_KEY=pk_test_xxxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxx

# Cashier
CASHIER_CURRENCY=eur
CASHIER_CURRENCY_LOCALE=it_IT
CASHIER_LOGGER=daily
```

#### Step 1.3: Creare Migrations

```bash
# Crea migrations custom
php artisan make:migration create_subscriptions_table
php artisan make:migration create_invoices_table
php artisan make:migration create_payment_methods_table
php artisan make:migration create_billing_events_table
php artisan make:migration add_billing_fields_to_tenants_table

# Implementa schema descritto sopra in ogni migration

# Esegui migrations
php artisan migrate
```

#### Step 1.4: Creare Models

```bash
php artisan make:model Subscription
php artisan make:model Invoice
php artisan make:model PaymentMethod
php artisan make:model BillingEvent

# Implementa fillable, casts, relationships
```

---

### FASE 2: Service Layer (3-4 ore)

#### Step 2.1: Creare StripeService

```bash
php artisan make:class Services/StripeService
```

```php
// app/Services/StripeService.php
<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Subscription;
use Stripe\StripeClient;
use Stripe\Checkout\Session;

class StripeService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Crea o recupera Stripe Customer per tenant
     */
    public function createOrGetCustomer(Tenant $tenant): string
    {
        if ($tenant->stripe_customer_id) {
            return $tenant->stripe_customer_id;
        }

        $customer = $this->stripe->customers->create([
            'email' => $tenant->owner->email ?? $tenant->billing_email,
            'name' => $tenant->name,
            'metadata' => [
                'tenant_id' => $tenant->id,
            ],
        ]);

        $tenant->update(['stripe_customer_id' => $customer->id]);

        return $customer->id;
    }

    /**
     * Crea Checkout Session per subscription
     */
    public function createCheckoutSession(Tenant $tenant, Plan $plan): Session
    {
        $customerId = $this->createOrGetCustomer($tenant);

        return $this->stripe->checkout->sessions->create([
            'customer' => $customerId,
            'mode' => 'subscription',
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $plan->name,
                            'description' => $plan->description,
                        ],
                        'unit_amount' => $plan->price_monthly * 100, // In cents
                        'recurring' => ['interval' => 'month'],
                    ],
                    'quantity' => 1,
                ],
            ],
            'success_url' => route('billing.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('billing.cancel'),
            'metadata' => [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
            ],
        ]);
    }

    /**
     * Cancella subscription (a fine periodo)
     */
    public function cancelSubscription(Subscription $subscription): void
    {
        $this->stripe->subscriptions->update(
            $subscription->stripe_subscription_id,
            ['cancel_at_period_end' => true]
        );

        $subscription->update(['canceled_at' => now()]);
    }

    /**
     * Riattiva subscription cancellata
     */
    public function resumeSubscription(Subscription $subscription): void
    {
        $this->stripe->subscriptions->update(
            $subscription->stripe_subscription_id,
            ['cancel_at_period_end' => false]
        );

        $subscription->update(['canceled_at' => null]);
    }

    /**
     * Aggiorna subscription a nuovo piano
     */
    public function updateSubscriptionPlan(Subscription $subscription, Plan $newPlan): void
    {
        $stripeSubscription = $this->stripe->subscriptions->retrieve(
            $subscription->stripe_subscription_id
        );

        $this->stripe->subscriptions->update(
            $subscription->stripe_subscription_id,
            [
                'items' => [
                    [
                        'id' => $stripeSubscription->items->data[0]->id,
                        'price_data' => [
                            'currency' => 'eur',
                            'product_data' => ['name' => $newPlan->name],
                            'unit_amount' => $newPlan->price_monthly * 100,
                            'recurring' => ['interval' => 'month'],
                        ],
                    ],
                ],
                'proration_behavior' => 'create_prorations',
            ]
        );

        $subscription->update(['plan_id' => $newPlan->id]);
    }

    /**
     * Recupera fattura PDF
     */
    public function getInvoicePdf(string $invoiceId): string
    {
        $invoice = $this->stripe->invoices->retrieve($invoiceId);
        return $invoice->invoice_pdf;
    }
}
```

---

### FASE 3: Webhook Handler (2-3 ore)

#### Step 3.1: Creare Controller

```bash
php artisan make:controller StripeWebhookController
```

```php
// app/Http/Controllers/StripeWebhookController.php
<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\BillingEvent;
use Illuminate\Http\Request;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Log evento
        BillingEvent::create([
            'type' => $event->type,
            'stripe_event_id' => $event->id,
            'payload' => $event->toArray(),
        ]);

        // Gestisci evento
        return match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event),
            'invoice.payment_succeeded' => $this->handlePaymentSucceeded($event),
            'invoice.payment_failed' => $this->handlePaymentFailed($event),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
            default => response()->json(['status' => 'ignored']),
        };
    }

    protected function handleCheckoutCompleted($event)
    {
        $session = $event->data->object;
        $tenantId = $session->metadata->tenant_id;
        $planId = $session->metadata->plan_id;

        $tenant = Tenant::findOrFail($tenantId);
        $plan = Plan::findOrFail($planId);

        // Crea subscription record
        Subscription::create([
            'tenant_id' => $tenantId,
            'plan_id' => $planId,
            'stripe_subscription_id' => $session->subscription,
            'stripe_status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        // Aggiorna tenant
        $tenant->update([
            'plan_type' => $plan->slug,
            'tokens_monthly_limit' => $plan->tokens_monthly_limit,
            'stripe_subscription_id' => $session->subscription,
        ]);

        return response()->json(['status' => 'success']);
    }

    protected function handlePaymentSucceeded($event)
    {
        $invoice = $event->data->object;
        $customerId = $invoice->customer;

        $tenant = Tenant::where('stripe_customer_id', $customerId)->first();

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        // Crea invoice record
        Invoice::create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $tenant->subscription?->id,
            'stripe_invoice_id' => $invoice->id,
            'stripe_payment_intent_id' => $invoice->payment_intent,
            'amount' => $invoice->amount_paid / 100,
            'currency' => $invoice->currency,
            'status' => 'paid',
            'billing_reason' => $invoice->billing_reason,
            'invoice_pdf' => $invoice->invoice_pdf,
            'hosted_invoice_url' => $invoice->hosted_invoice_url,
            'paid_at' => now(),
        ]);

        // Reset token usage se è nuovo ciclo
        if ($invoice->billing_reason === 'subscription_cycle') {
            $tenant->update(['tokens_used_current' => 0]);
        }

        // TODO: Invia email con fattura

        return response()->json(['status' => 'success']);
    }

    protected function handlePaymentFailed($event)
    {
        $invoice = $event->data->object;
        $customerId = $invoice->customer;

        $tenant = Tenant::where('stripe_customer_id', $customerId)->first();

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        // Marca subscription come past_due
        $tenant->subscription?->update(['stripe_status' => 'past_due']);

        // TODO: Invia email urgente "Payment failed"

        return response()->json(['status' => 'success']);
    }

    protected function handleSubscriptionUpdated($event)
    {
        $stripeSubscription = $event->data->object;

        $subscription = Subscription::where(
            'stripe_subscription_id',
            $stripeSubscription->id
        )->first();

        if (!$subscription) {
            return response()->json(['error' => 'Subscription not found'], 404);
        }

        $subscription->update([
            'stripe_status' => $stripeSubscription->status,
            'current_period_start' => now()->createFromTimestamp($stripeSubscription->current_period_start),
            'current_period_end' => now()->createFromTimestamp($stripeSubscription->current_period_end),
            'canceled_at' => $stripeSubscription->cancel_at_period_end ? now() : null,
        ]);

        return response()->json(['status' => 'success']);
    }

    protected function handleSubscriptionDeleted($event)
    {
        $stripeSubscription = $event->data->object;

        $subscription = Subscription::where(
            'stripe_subscription_id',
            $stripeSubscription->id
        )->first();

        if (!$subscription) {
            return response()->json(['error' => 'Subscription not found'], 404);
        }

        $tenant = $subscription->tenant;

        // Downgrade a free plan
        $freePlan = Plan::where('slug', 'free')->first();

        $tenant->update([
            'plan_type' => 'free',
            'tokens_monthly_limit' => $freePlan->tokens_monthly_limit ?? 10000,
        ]);

        $subscription->update([
            'stripe_status' => 'canceled',
            'ends_at' => now(),
        ]);

        // TODO: Invia email "Subscription ended"

        return response()->json(['status' => 'success']);
    }
}
```

#### Step 3.2: Aggiungi Route Webhook

```php
// routes/api.php
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook');
```

#### Step 3.3: Escludi Webhook da CSRF

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'api/stripe/webhook',
    ]);
})
```

---

### FASE 4: Controllers Billing (4-5 ore)

#### Step 4.1: Admin Billing Controller

```bash
php artisan make:controller AdminBillingController
```

```php
// app/Http/Controllers/AdminBillingController.php
<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\Invoice;
use Illuminate\Http\Request;

class AdminBillingController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'mrr' => Subscription::where('stripe_status', 'active')
                ->with('plan')
                ->get()
                ->sum(fn($sub) => $sub->plan->price_monthly),

            'active_subscriptions' => Subscription::where('stripe_status', 'active')->count(),
            'trial_subscriptions' => Subscription::where('stripe_status', 'trialing')->count(),
            'past_due_subscriptions' => Subscription::where('stripe_status', 'past_due')->count(),

            'total_revenue_month' => Invoice::where('status', 'paid')
                ->whereMonth('paid_at', now()->month)
                ->sum('amount'),

            'failed_payments' => Invoice::where('status', 'open')
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
        ];

        $recentInvoices = Invoice::with('tenant')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.billing.dashboard', compact('stats', 'recentInvoices'));
    }

    public function subscriptions()
    {
        $subscriptions = Subscription::with(['tenant', 'plan'])
            ->latest()
            ->paginate(20);

        return view('admin.billing.subscriptions', compact('subscriptions'));
    }

    public function invoices()
    {
        $invoices = Invoice::with('tenant')
            ->latest()
            ->paginate(50);

        return view('admin.billing.invoices', compact('invoices'));
    }
}
```

#### Step 4.2: Tenant Billing Controller

```bash
php artisan make:controller TenantBillingController
```

```php
// app/Http/Controllers/TenantBillingController.php
<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\StripeService;
use Illuminate\Http\Request;

class TenantBillingController extends Controller
{
    public function __construct(
        protected StripeService $stripeService
    ) {}

    public function index()
    {
        $tenant = auth()->user()->tenant;
        $subscription = $tenant->subscription;
        $invoices = $tenant->invoices()->latest()->paginate(10);
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return view('tenant.billing.index', compact('tenant', 'subscription', 'invoices', 'plans'));
    }

    public function subscribe(Request $request, Plan $plan)
    {
        $tenant = auth()->user()->tenant;

        if ($tenant->subscription?->isActive()) {
            return back()->with('error', 'You already have an active subscription');
        }

        $session = $this->stripeService->createCheckoutSession($tenant, $plan);

        return redirect($session->url);
    }

    public function success(Request $request)
    {
        // Stripe redirect dopo pagamento
        return redirect()->route('billing.index')
            ->with('success', 'Subscription activated successfully!');
    }

    public function cancel()
    {
        // Stripe redirect se cancella checkout
        return redirect()->route('billing.index')
            ->with('info', 'Checkout canceled');
    }

    public function cancelSubscription()
    {
        $tenant = auth()->user()->tenant;
        $subscription = $tenant->subscription;

        if (!$subscription || !$subscription->isActive()) {
            return back()->with('error', 'No active subscription found');
        }

        $this->stripeService->cancelSubscription($subscription);

        return back()->with('success', 'Subscription will be canceled at period end');
    }

    public function resumeSubscription()
    {
        $tenant = auth()->user()->tenant;
        $subscription = $tenant->subscription;

        if (!$subscription || !$subscription->canceled_at) {
            return back()->with('error', 'No canceled subscription found');
        }

        $this->stripeService->resumeSubscription($subscription);

        return back()->with('success', 'Subscription resumed successfully');
    }

    public function downloadInvoice($invoiceId)
    {
        $invoice = auth()->user()->tenant->invoices()->findOrFail($invoiceId);

        return redirect($invoice->invoice_pdf);
    }
}
```

---

### FASE 5: Routes (30 min)

```php
// routes/admin.php
Route::prefix('billing')->name('admin.billing.')->group(function () {
    Route::get('/', [AdminBillingController::class, 'dashboard'])->name('dashboard');
    Route::get('/subscriptions', [AdminBillingController::class, 'subscriptions'])->name('subscriptions');
    Route::get('/invoices', [AdminBillingController::class, 'invoices'])->name('invoices');
});

// routes/web.php (tenant)
Route::middleware('auth')->prefix('dashboard/billing')->name('billing.')->group(function () {
    Route::get('/', [TenantBillingController::class, 'index'])->name('index');
    Route::post('/subscribe/{plan}', [TenantBillingController::class, 'subscribe'])->name('subscribe');
    Route::get('/success', [TenantBillingController::class, 'success'])->name('success');
    Route::get('/cancel', [TenantBillingController::class, 'cancel'])->name('cancel');
    Route::post('/cancel-subscription', [TenantBillingController::class, 'cancelSubscription'])->name('cancel-subscription');
    Route::post('/resume-subscription', [TenantBillingController::class, 'resumeSubscription'])->name('resume-subscription');
    Route::get('/invoice/{invoice}/download', [TenantBillingController::class, 'downloadInvoice'])->name('invoice.download');
});
```

---

### FASE 6: Views (5-6 ore)

#### Admin Billing Dashboard

```blade
{{-- resources/views/admin/billing/dashboard.blade.php --}}
@extends('admin.layout')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <h1 class="text-3xl font-bold mb-6">💰 Billing Dashboard</h1>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-500">Monthly Recurring Revenue</div>
            <div class="text-3xl font-bold text-green-600">€{{ number_format($stats['mrr'], 2) }}</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-500">Active Subscriptions</div>
            <div class="text-3xl font-bold text-blue-600">{{ $stats['active_subscriptions'] }}</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-500">Revenue This Month</div>
            <div class="text-3xl font-bold text-purple-600">€{{ number_format($stats['total_revenue_month'], 2) }}</div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-sm text-gray-500">Failed Payments</div>
            <div class="text-3xl font-bold text-red-600">{{ $stats['failed_payments'] }}</div>
        </div>
    </div>

    <!-- Recent Invoices -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-xl font-semibold">Recent Invoices</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tenant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($recentInvoices as $invoice)
                    <tr>
                        <td class="px-6 py-4">{{ $invoice->tenant->name }}</td>
                        <td class="px-6 py-4">€{{ number_format($invoice->amount, 2) }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded
                                {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $invoice->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4">{{ $invoice->created_at->format('d M Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
```

#### Tenant Billing Page

```blade
{{-- resources/views/tenant/billing/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <h1 class="text-3xl font-bold mb-6">💳 Billing & Subscription</h1>

    <!-- Current Subscription -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Current Plan</h2>

        @if($subscription && $subscription->isActive())
            <div class="flex justify-between items-start">
                <div>
                    <div class="text-2xl font-bold">{{ $subscription->plan->name }}</div>
                    <div class="text-gray-600">€{{ number_format($subscription->plan->price_monthly, 2) }}/month</div>
                    <div class="text-sm text-gray-500 mt-2">
                        Renews on {{ $subscription->current_period_end->format('F j, Y') }}
                    </div>

                    @if($subscription->canceled_at)
                        <div class="mt-2 text-orange-600">
                            ⚠️ Subscription will end on {{ $subscription->current_period_end->format('F j, Y') }}
                        </div>
                    @endif
                </div>

                <div class="text-right">
                    @if($subscription->canceled_at)
                        <form method="POST" action="{{ route('billing.resume-subscription') }}">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                Resume Subscription
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('billing.cancel-subscription') }}"
                              onsubmit="return confirm('Are you sure you want to cancel?')">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                                Cancel Subscription
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @else
            <p class="text-gray-600">You are currently on the Free plan</p>
        @endif
    </div>

    <!-- Available Plans -->
    <div class="mb-8">
        <h2 class="text-xl font-semibold mb-4">Available Plans</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($plans as $plan)
            <div class="bg-white rounded-lg shadow p-6 {{ $subscription?->plan_id === $plan->id ? 'ring-2 ring-blue-500' : '' }}">
                <h3 class="text-xl font-bold mb-2">{{ $plan->name }}</h3>
                <div class="text-3xl font-bold mb-4">€{{ number_format($plan->price_monthly, 0) }}<span class="text-sm text-gray-500">/mo</span></div>

                <ul class="space-y-2 mb-6">
                    <li class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        {{ number_format($plan->tokens_monthly_limit) }} tokens/month
                    </li>
                    <li class="flex items-center text-sm">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Up to {{ $plan->max_users }} users
                    </li>
                </ul>

                @if($subscription?->plan_id === $plan->id)
                    <button disabled class="w-full px-4 py-2 bg-gray-300 text-gray-600 rounded cursor-not-allowed">
                        Current Plan
                    </button>
                @else
                    <form method="POST" action="{{ route('billing.subscribe', $plan) }}">
                        @csrf
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            {{ $subscription ? 'Switch to ' . $plan->name : 'Subscribe' }}
                        </button>
                    </form>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <!-- Invoices -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-xl font-semibold">Invoices</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($invoices as $invoice)
                    <tr>
                        <td class="px-6 py-4">{{ $invoice->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4">€{{ number_format($invoice->amount, 2) }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded
                                {{ $invoice->status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if($invoice->invoice_pdf)
                                <a href="{{ route('billing.invoice.download', $invoice) }}"
                                   class="text-blue-600 hover:text-blue-800">
                                    Download PDF
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-4 text-center text-gray-500">No invoices yet</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
```

---

### FASE 7: Testing (3-4 ore)

#### Step 7.1: Setup Stripe Test Mode

```bash
# Installa Stripe CLI
# Windows: scoop install stripe
# Mac: brew install stripe/stripe-cli/stripe
# Linux: https://stripe.com/docs/stripe-cli

# Login
stripe login

# Forward webhook a local
stripe listen --forward-to http://127.0.0.1:8080/api/stripe/webhook

# Copia webhook secret da output e aggiorna .env
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxx
```

#### Step 7.2: Test Cards Stripe

```
SUCCESS: 4242 4242 4242 4242
DECLINE: 4000 0000 0000 0002
INSUFFICIENT FUNDS: 4000 0000 0000 9995
SCA REQUIRED: 4000 0025 0000 3155
```

#### Step 7.3: Test Scenarios

```bash
# 1. Crea subscription
# - Vai su /dashboard/billing
# - Scegli piano Pro
# - Inserisci carta 4242 4242 4242 4242
# - Completa checkout
# - Verifica webhook ricevuto
# - Verifica record subscription creato
# - Verifica tenant aggiornato

# 2. Simula pagamento fallito
stripe trigger payment_intent.payment_failed

# 3. Simula cancellazione subscription
# - Clicca "Cancel Subscription"
# - Verifica webhook
# - Verifica subscription.canceled_at impostato

# 4. Download fattura
# - Clicca "Download PDF" su invoice
# - Verifica PDF si apre
```

---

## 🎨 INTERFACCE UTENTE

### Admin Panel

**Menu Sidebar:**
```
Admin
├── Dashboard
├── Users
├── Tenants
├── 💰 Billing (NEW)
│   ├── Dashboard
│   ├── Subscriptions
│   └── Invoices
└── Settings
```

**Billing Dashboard:**
- 4 widget stats (MRR, Active Subs, Revenue Month, Failed Payments)
- Revenue chart (ultimi 12 mesi)
- Recent invoices table (ultimi 10)

**Subscriptions Page:**
- Table: Tenant | Plan | Status | MRR | Next Billing | Actions
- Filters: Status, Plan Type
- Actions: View Details, Cancel, Change Plan

**Invoices Page:**
- Table: Tenant | Amount | Status | Date | PDF
- Filters: Status, Date Range
- Export CSV button

### Tenant Panel

**Menu Sidebar:**
```
Dashboard
├── Overview
├── Pages
├── Prompts
├── Content
├── API Keys
├── 💳 Billing (NEW)
└── Settings
```

**Billing Page:**
- Current subscription card (plan, price, renewal date)
- Available plans grid (3 cards side-by-side)
- Invoices table (downloadable PDFs)
- Payment methods section (optional)

---

## 🔐 SICUREZZA E COMPLIANCE

### Checklist Sicurezza

- ✅ Webhook signature verification (Stripe)
- ✅ Idempotency keys su API calls critiche
- ✅ PCI compliance (gestito da Stripe, non tocchiamo carte)
- ✅ Encrypt sensitive billing data in DB (se necessario)
- ✅ HTTPS obbligatorio su produzione
- ✅ Rate limiting su webhook endpoint
- ✅ Log tutti gli eventi billing (billing_events table)

### GDPR Compliance

- ✅ Consent tracking per processing pagamenti
- ✅ Export billing data su richiesta utente
- ✅ Delete billing data (con retention policy)
- ✅ Privacy policy con sezione billing
- ✅ Cookie consent per Stripe.js

### Fatturazione Italia (Opzionale)

Se servono fatture elettroniche italiane:

- Integrare con SDK fatturazione elettronica (es. Fatture in Cloud API)
- Aggiungere campi: P.IVA, Codice Fiscale, SDI, PEC
- Generare XML fattura e invio SdI
- Storico fatture XML in storage

---

## 💰 CONFIGURAZIONE PREZZI

### Piani Suggeriti

```php
// database/seeders/PlanSeeder.php

Plan::create([
    'name' => 'Free',
    'slug' => 'free',
    'description' => 'Perfect for testing and small projects',
    'price_monthly' => 0,
    'price_yearly' => 0,
    'tokens_monthly_limit' => 10000,
    'features' => ['basic_generation'],
    'max_users' => 1,
    'max_api_keys' => 2,
    'is_active' => true,
    'sort_order' => 0,
]);

Plan::create([
    'name' => 'Basic',
    'slug' => 'basic',
    'description' => 'Great for freelancers and small teams',
    'price_monthly' => 29,
    'price_yearly' => 290, // 2 mesi gratis
    'tokens_monthly_limit' => 50000,
    'features' => ['basic_generation', 'advanced_prompts'],
    'max_users' => 3,
    'max_api_keys' => 5,
    'is_active' => true,
    'sort_order' => 1,
]);

Plan::create([
    'name' => 'Pro',
    'slug' => 'pro',
    'description' => 'For growing businesses',
    'price_monthly' => 99,
    'price_yearly' => 990,
    'tokens_monthly_limit' => 200000,
    'features' => ['basic_generation', 'advanced_prompts', 'custom_templates', 'analytics'],
    'max_users' => 10,
    'max_api_keys' => 10,
    'is_active' => true,
    'sort_order' => 2,
]);

Plan::create([
    'name' => 'Enterprise',
    'slug' => 'enterprise',
    'description' => 'For large organizations',
    'price_monthly' => 299,
    'price_yearly' => 2990,
    'tokens_monthly_limit' => 1000000,
    'features' => ['basic_generation', 'advanced_prompts', 'custom_templates', 'analytics', 'priority_support', 'custom_integrations'],
    'max_users' => 50,
    'max_api_keys' => 25,
    'is_active' => true,
    'sort_order' => 3,
]);
```

### Pricing Strategy

**Monthly vs Yearly:**
- Yearly = 10 mesi di prezzo (sconto ~17%)
- Incentiva commitment lungo termine
- Migliora cash flow

**Add-ons Possibili:**
- Extra tokens: €10 per 50,000 tokens
- Extra users: €5/user/month
- Priority support: €50/month

---

## ❓ FAQ E DECISIONI

### 1. Payment Provider

**DECISIONE: STRIPE**

✅ Pro:
- Industry standard (Shopify, AWS, Slack usano Stripe)
- SDK completo in tutte le lingue
- Webhook robusti con retry automatico
- Dashboard eccellente
- SCA compliance automatico
- Multi-currency facile

❌ Contro:
- Fees 1.4% + €0.25 (EU cards)
- Setup iniziale più complesso di Paddle

**Alternative:**
- Paddle: Merchant of record, fatture automatiche, fees più alte (5% + €0.50)
- Lemon Squeezy: Simile a Paddle, ottimo per prodotti digitali

### 2. Currency

**DECISIONE: EUR primario, USD/GBP opzionali**

- Piattaforma italiana → EUR default
- Stripe supporta multi-currency senza sforzo
- Prezzi mostrati in base a IP utente (Stripe.js auto-detect)

### 3. Trial Period

**DECISIONE: Free plan permanente, NO trial su piani pagati**

- Free plan = "eternal trial" con limiti bassi
- Più semplice da gestire
- Meno friction per utenti
- Possibilità aggiungere trial 14gg in futuro se serve

### 4. Billing Cycle

**DECISIONE: Monthly default, Yearly opzionale**

- Monthly più accessibile per PMI
- Yearly con sconto 17% per incentivare
- Stripe gestisce automaticamente rinnovi

### 5. Fatture Elettroniche Italia

**DECISIONE: Fase 2 (non ora)**

- Per MVP basta fattura PDF Stripe
- Se richiesto da clienti enterprise, integrare FattureInCloud API
- Aggiungere campi P.IVA, CF, SDI a tenant model

### 6. Usage-Based Billing

**DECISIONE: Fase 2 (non ora)**

Attualmente: Piani fissi con limiti token

Futuro possibile:
- Pay-as-you-go: €0.10 per 1,000 tokens
- Hybrid: Piano base + metered billing oltre limiti
- Stripe Metered Billing supporta questo

### 7. Coupon/Promo Codes

**DECISIONE: Fase 2**

Stripe supporta:
- Coupon percentuali (20% off)
- Coupon fissi (€10 off)
- Durata: forever, once, repeating(3 months)

Implementare quando serve per:
- Referral program
- Seasonal promotions
- Partnership deals

### 8. Refunds

**DECISIONE: Manual via Admin Panel**

- Admin può emettere refund da Stripe dashboard
- Webhook `charge.refunded` aggiorna Invoice status
- No self-service refund per utenti (policy da definire)

### 9. Downgrade Protection

**DECISIONE: Immediate downgrade con grace period**

Scenario: Pro (200k tokens) → Basic (50k tokens) a metà mese, già usati 100k tokens

Opzioni:
1. **Immediate block**: Blocca generazioni fino a prossimo ciclo ❌
2. **Grace period**: Permetti uso fino a fine periodo ✅ (SCELTA)
3. **Prorata refund**: Rimborsa differenza ❌ (complesso)

Implementazione: Downgrade schedule, effective at period end

### 10. Failed Payment Retry

**DECISIONE: Stripe Smart Retries (default)**

Stripe riprova automaticamente:
- 1st retry: 3 giorni dopo
- 2nd retry: 5 giorni dopo
- 3rd retry: 7 giorni dopo
- 4th retry: Subscription canceled

Azioni nostre:
- Email reminder dopo 1st fail
- Email urgente dopo 2nd fail
- Email finale + downgrade dopo 4th fail

---

## 🚀 TIMELINE IMPLEMENTAZIONE

### Settimana 1: Core Billing (16-20 ore)

**Giorno 1-2 (6-8 ore):**
- Setup Stripe account + test keys
- Install packages (stripe-php, cashier)
- Migrations (subscriptions, invoices, payment_methods, billing_events)
- Models + relationships

**Giorno 3-4 (6-8 ore):**
- StripeService implementazione completa
- Webhook controller + handlers
- Test webhook con Stripe CLI

**Giorno 5 (4-5 ore):**
- Billing controllers (Admin + Tenant)
- Routes setup
- Basic views (copy-paste da sopra)

### Settimana 2: UI & Testing (12-16 ore)

**Giorno 1-2 (6-8 ore):**
- Admin billing dashboard completo
- Admin subscriptions/invoices pages
- Tenant billing page UI polish

**Giorno 3 (4-5 ore):**
- Email notifications (payment_succeeded, payment_failed, subscription_canceled)
- Queue jobs per async processing

**Giorno 4-5 (4-5 ore):**
- End-to-end testing tutti scenari
- Bug fixing
- Documentation update

### Settimana 3: Polish & Deploy (8-10 ore)

**Giorno 1-2 (4-6 ore):**
- Analytics dashboard admin (revenue charts)
- Export CSV invoices
- Filters advanced

**Giorno 3 (2-3 ore):**
- Production Stripe account setup
- Webhook production URL
- ENV variables production

**Giorno 4 (2-3 ore):**
- Deploy a staging
- Test completo con carte reali (small amounts)
- Go live 🚀

---

## 📝 CHECKLIST PRE-DEPLOY

### Stripe Setup

- [ ] Stripe account business completato
- [ ] Business info verificata (nome, indirizzo, tax ID)
- [ ] Bank account collegato per payouts
- [ ] Production API keys generate
- [ ] Webhook endpoint configurato in Stripe dashboard
- [ ] Webhook signing secret salvato in .env
- [ ] Test subscription creata e cancellata con successo

### Database

- [ ] Migrations eseguite su production
- [ ] Plans seeded con prezzi corretti
- [ ] Backup database schedulato

### Security

- [ ] HTTPS enforced su domain
- [ ] CSRF protection attiva
- [ ] Webhook signature verification implementata
- [ ] Rate limiting su webhook endpoint
- [ ] Sensitive data encrypted (se applicabile)

### Email

- [ ] SMTP configurato per production
- [ ] Email templates testate
- [ ] Sender email verified
- [ ] Unsubscribe link implementato (GDPR)

### Legal

- [ ] Terms of Service aggiornati con billing info
- [ ] Privacy Policy include processing pagamenti
- [ ] Refund policy definita
- [ ] Cookie consent per Stripe.js

### Monitoring

- [ ] Logging billing events abilitato
- [ ] Error tracking (Sentry/Bugsnag) configurato
- [ ] Alert su failed payments (>5/day)
- [ ] Dashboard MRR monitorato

---

## 📞 SUPPORT & RESOURCES

### Documentazione Utile

- **Stripe Docs**: https://stripe.com/docs
- **Stripe PHP SDK**: https://github.com/stripe/stripe-php
- **Laravel Cashier**: https://laravel.com/docs/billing
- **Stripe Webhook Events**: https://stripe.com/docs/api/events/types
- **Stripe Testing**: https://stripe.com/docs/testing

### Stripe Dashboard

- **Test Mode**: https://dashboard.stripe.com/test
- **Production**: https://dashboard.stripe.com/
- **Webhook Logs**: Dashboard → Developers → Webhooks
- **Events Log**: Dashboard → Developers → Events

### Comandi Utili

```bash
# Stripe CLI
stripe login
stripe listen --forward-to localhost:8080/api/stripe/webhook
stripe trigger payment_intent.succeeded
stripe trigger invoice.payment_failed

# Laravel
php artisan migrate --path=database/migrations/billing
php artisan queue:work --queue=billing
php artisan tinker
>>> Subscription::with('tenant','plan')->get()
```

---

## 🎉 CONCLUSIONI

Questa guida fornisce tutto il necessario per implementare un sistema billing completo e production-ready su Ainstein Platform.

**Tempo stimato totale:** 35-45 ore sviluppo (1-2 settimane full-time)

**Risultato finale:**
- ✅ Subscription management completo
- ✅ Pagamenti ricorrenti automatici
- ✅ Fatture PDF scaricabili
- ✅ Admin dashboard con analytics
- ✅ Tenant self-service billing
- ✅ Webhook handlers robusti
- ✅ Email notifications
- ✅ Production-ready

**Prossimi step:**
1. Confermare decisioni in FAQ section
2. Setup Stripe test account
3. Iniziare implementazione Fase 1 (Core Billing)
4. Iterare basandosi su feedback utenti

---

**Good luck! 🚀**

**Per domande o supporto durante implementazione, consultare:**
- Questa guida
- Stripe documentation
- Laravel Cashier docs
- PROJECT-STATUS.md per contesto piattaforma
