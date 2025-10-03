# 🎓 Ainstein Onboarding System

## 📋 Panoramica

Il sistema di onboarding Ainstein è basato su **Shepherd.js** e fornisce tour guidati interattivi per:
- Dashboard principale (prima accesso)
- Ogni tool/sezione specifica
- Feature nuove (quando rilasciate)

---

## 🏗️ Architettura Onboarding

### Tipi di Tour

1. **Main Onboarding** - Dashboard al primo accesso
2. **Tool-Specific Tours** - Uno per ogni tool/sezione
3. **Feature Tours** - Per nuove funzionalità rilasciate
4. **Contextual Help** - Mini-tour su richiesta

---

## 🗄️ Database Schema

### Migration: `add_onboarding_tools_to_users_table`

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Main onboarding completion
            $table->boolean('onboarding_completed')->default(false);

            // Tool-specific onboarding completions (JSON)
            $table->json('onboarding_tools_completed')->nullable();
            // Esempio: ["pages", "content-generation", "prompts", "api-keys"]

            // Timestamps per tracking
            $table->timestamp('onboarding_completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'onboarding_completed',
                'onboarding_tools_completed',
                'onboarding_completed_at',
            ]);
        });
    }
};
```

### Model Methods

```php
// app/Models/User.php

protected $casts = [
    'onboarding_completed' => 'boolean',
    'onboarding_tools_completed' => 'array',
    'onboarding_completed_at' => 'datetime',
];

/**
 * Check if main onboarding is completed
 */
public function hasCompletedOnboarding(): bool
{
    return $this->onboarding_completed;
}

/**
 * Check if specific tool onboarding is completed
 */
public function hasCompletedToolOnboarding(string $tool): bool
{
    $completed = $this->onboarding_tools_completed ?? [];
    return in_array($tool, $completed);
}

/**
 * Mark main onboarding as completed
 */
public function completeOnboarding(): void
{
    $this->update([
        'onboarding_completed' => true,
        'onboarding_completed_at' => now(),
    ]);
}

/**
 * Mark specific tool onboarding as completed
 */
public function markToolOnboardingComplete(string $tool): void
{
    $completed = $this->onboarding_tools_completed ?? [];

    if (!in_array($tool, $completed)) {
        $completed[] = $tool;
        $this->update(['onboarding_tools_completed' => $completed]);
    }
}

/**
 * Reset tool onboarding (for testing or user request)
 */
public function resetToolOnboarding(?string $tool = null): void
{
    if ($tool) {
        $completed = $this->onboarding_tools_completed ?? [];
        $completed = array_values(array_diff($completed, [$tool]));
        $this->update(['onboarding_tools_completed' => $completed]);
    } else {
        $this->update(['onboarding_tools_completed' => []]);
    }
}
```

---

## 🎯 Controller Methods

### `app/Http/Controllers/OnboardingController.php`

```php
<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OnboardingController extends Controller
{
    /**
     * Mark main onboarding as completed
     */
    public function complete(Request $request)
    {
        $user = Auth::user();
        $user->completeOnboarding();

        return response()->json([
            'success' => true,
            'message' => 'Onboarding completed successfully'
        ]);
    }

    /**
     * Reset main onboarding (for testing)
     */
    public function reset(Request $request)
    {
        $user = Auth::user();

        $user->update([
            'onboarding_completed' => false,
            'onboarding_completed_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Onboarding reset successfully'
        ]);
    }

    /**
     * Mark tool onboarding as completed
     */
    public function completeToolOnboarding(Request $request, string $tool)
    {
        $user = Auth::user();

        // Validate tool name
        $validTools = [
            'pages',
            'content-generation',
            'prompts',
            'api-keys',
            // Tool nuovi (aggiungi qui)
            'campaign-generator',
            'negative-keywords',
            'article-generator',
            'internal-links',
            'gsc-tracker',
            'keyword-research',
        ];

        if (!in_array($tool, $validTools)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid tool name'
            ], 400);
        }

        $user->markToolOnboardingComplete($tool);

        return response()->json([
            'success' => true,
            'message' => "Tool onboarding '{$tool}' marked as completed"
        ]);
    }

    /**
     * Reset specific tool onboarding
     */
    public function resetToolOnboarding(Request $request, string $tool = null)
    {
        $user = Auth::user();
        $user->resetToolOnboarding($tool);

        return response()->json([
            'success' => true,
            'message' => $tool
                ? "Tool onboarding '{$tool}' reset successfully"
                : 'All tool onboardings reset successfully'
        ]);
    }

    /**
     * Get onboarding status
     */
    public function status(Request $request)
    {
        $user = Auth::user();

        return response()->json([
            'main_completed' => $user->hasCompletedOnboarding(),
            'tools_completed' => $user->onboarding_tools_completed ?? [],
            'completed_at' => $user->onboarding_completed_at?->toISOString(),
        ]);
    }
}
```

---

## 🛣️ Routes

### `routes/web.php`

```php
// Onboarding Routes
Route::middleware(['auth'])->prefix('dashboard/onboarding')->name('onboarding.')->group(function () {

    // Main onboarding
    Route::post('/complete', [OnboardingController::class, 'complete'])->name('complete');
    Route::post('/reset', [OnboardingController::class, 'reset'])->name('reset');

    // Tool-specific onboarding
    Route::post('/tool/{tool}/complete', [OnboardingController::class, 'completeToolOnboarding'])->name('tool.complete');
    Route::post('/tool/{tool}/reset', [OnboardingController::class, 'resetToolOnboarding'])->name('tool.reset');

    // Status check
    Route::get('/status', [OnboardingController::class, 'status'])->name('status');
});
```

---

## 📜 JavaScript Implementation

### Setup Base - `resources/js/onboarding-setup.js`

```javascript
import Shepherd from 'shepherd.js';
import 'shepherd.js/dist/css/shepherd.css';

/**
 * Global Shepherd.js configuration
 */
export const shepherdConfig = {
    useModalOverlay: true,
    defaultStepOptions: {
        classes: 'ainstein-tour-step',
        scrollTo: {
            behavior: 'smooth',
            block: 'center'
        },
        cancelIcon: {
            enabled: true
        },
        modalOverlayOpeningPadding: 4,
        modalOverlayOpeningRadius: 8,
    }
};

/**
 * Custom Shepherd Theme CSS (already in app.css)
 */
// .ainstein-tour-step styles...

/**
 * Helper: Create standard buttons
 */
export function tourButtons(tour, isFirst = false, isLast = false) {
    const buttons = [];

    if (!isFirst) {
        buttons.push({
            text: '← Indietro',
            classes: 'shepherd-button-secondary',
            action: tour.back
        });
    }

    if (isLast) {
        buttons.push({
            text: '✓ Ho capito',
            classes: 'shepherd-button-primary',
            action: () => {
                const dontShowCheckbox = document.getElementById('dont-show-again');
                if (dontShowCheckbox?.checked) {
                    markToolAsCompleted(tour.tourName);
                }
                tour.complete();
            }
        });
    } else {
        buttons.push({
            text: 'Continua →',
            classes: 'shepherd-button-primary',
            action: tour.next
        });
    }

    return buttons;
}

/**
 * Helper: Mark tool onboarding as completed
 */
async function markToolAsCompleted(toolName) {
    try {
        const response = await fetch(`/dashboard/onboarding/tool/${toolName}/complete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        if (response.ok) {
            console.log(`✅ Tool onboarding '${toolName}' completed`);
        }
    } catch (error) {
        console.error('Error completing tool onboarding:', error);
    }
}

/**
 * Helper: Check if should show tour
 */
export async function shouldShowTour(toolName) {
    try {
        const response = await fetch('/dashboard/onboarding/status');
        const data = await response.json();

        return !data.tools_completed.includes(toolName);
    } catch (error) {
        console.error('Error checking onboarding status:', error);
        return false; // Don't show on error
    }
}
```

---

## 🎓 Template Tour per Nuovi Tool

### File: `resources/js/tours/tool-name-tour.js`

```javascript
import Shepherd from 'shepherd.js';
import { shepherdConfig, tourButtons } from '../onboarding-setup.js';

/**
 * Onboarding Tour: Tool Name
 */
export function initToolNameTour() {
    const tour = new Shepherd.Tour(shepherdConfig);
    tour.tourName = 'tool-name'; // IMPORTANTE: identificatore tool

    // Step 1: Welcome
    tour.addStep({
        id: 'welcome',
        title: '🎯 Benvenuto in [Tool Name]',
        text: `
            <div class="onboarding-content">
                <p class="mb-4">[Breve descrizione del tool e cosa fa]</p>
                <p class="mb-4">In questo tour imparerai a:</p>
                <ul class="list-disc pl-5 space-y-2 text-sm">
                    <li>[Funzionalità 1]</li>
                    <li>[Funzionalità 2]</li>
                    <li>[Funzionalità 3]</li>
                </ul>
            </div>
        `,
        buttons: [
            {
                text: 'Salta tour',
                classes: 'shepherd-button-secondary',
                action: tour.cancel
            },
            {
                text: 'Iniziamo →',
                classes: 'shepherd-button-primary',
                action: tour.next
            }
        ]
    });

    // Step 2: Main Feature
    tour.addStep({
        id: 'main-feature',
        title: '⚡ [Feature Title]',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">[Descrizione feature]</p>
                <ul class="list-disc pl-5 space-y-1 text-sm">
                    <li>[Dettaglio 1]</li>
                    <li>[Dettaglio 2]</li>
                </ul>
                <p class="text-xs text-gray-500 mt-3">💡 Tip: [Suggerimento utile]</p>
            </div>
        `,
        attachTo: {
            element: '.main-feature-selector',
            on: 'bottom' // top, bottom, left, right
        },
        buttons: tourButtons(tour, false, false)
    });

    // Step 3: Stats/Dashboard
    tour.addStep({
        id: 'stats',
        title: '📊 Statistiche',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Monitora le metriche chiave:</p>
                <ul class="list-disc pl-5 space-y-1 text-sm">
                    <li><strong>Metrica 1:</strong> Descrizione</li>
                    <li><strong>Metrica 2:</strong> Descrizione</li>
                </ul>
            </div>
        `,
        attachTo: {
            element: '.stats-row, .stat-card:first-child',
            on: 'bottom'
        },
        buttons: tourButtons(tour, false, false)
    });

    // Step 4: AI Feature (se presente)
    if (document.querySelector('.ai-feature')) {
        tour.addStep({
            id: 'ai-feature',
            title: '🤖 Intelligenza Artificiale',
            text: `
                <div class="onboarding-content">
                    <p class="mb-3">Questa funzione usa l'AI per [cosa fa].</p>
                    <p class="text-sm mb-3"><strong>Token consumati:</strong> circa [X] per operazione</p>
                    <p class="text-xs text-gray-500">💡 Tip: [Best practice AI]</p>
                </div>
            `,
            attachTo: {
                element: '.ai-feature',
                on: 'left'
            },
            buttons: tourButtons(tour, false, false)
        });
    }

    // Step FINAL: Completion
    tour.addStep({
        id: 'complete',
        title: '✅ Tour Completato!',
        text: `
            <div class="onboarding-content">
                <p class="mb-4">Ottimo lavoro! Ora sai come usare [Tool Name].</p>
                <p class="mb-4 text-sm">Prossimi passi suggeriti:</p>
                <ul class="list-disc pl-5 space-y-1 text-sm mb-4">
                    <li>[Azione suggerita 1]</li>
                    <li>[Azione suggerita 2]</li>
                </ul>

                <div class="border-t pt-4 mt-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox"
                               id="dont-show-again"
                               class="rounded border-gray-300 text-indigo-600 mr-2">
                        <span class="text-sm text-gray-700">
                            Non mostrare più questo tour
                        </span>
                    </label>
                </div>
            </div>
        `,
        buttons: [
            {
                text: '← Indietro',
                classes: 'shepherd-button-secondary',
                action: tour.back
            },
            {
                text: '✓ Ho capito',
                classes: 'shepherd-button-primary',
                action: async function() {
                    const dontShow = document.getElementById('dont-show-again')?.checked;

                    if (dontShow) {
                        // Mark as completed
                        await fetch(`/dashboard/onboarding/tool/${tour.tourName}/complete`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });
                    }

                    tour.complete();
                }
            }
        ]
    });

    // Event Handlers
    tour.on('cancel', () => {
        if (confirm('Vuoi davvero saltare il tour? Potrai riavviarlo dal menu aiuto.')) {
            tour.cancel();
        }
    });

    return tour;
}

// Export per uso globale
export function startToolNameTour() {
    const tour = initToolNameTour();
    tour.start();
}

// Auto-start (condizionale)
export async function autoStartToolNameTour() {
    const shouldShow = await shouldShowTour('tool-name');

    if (shouldShow) {
        window.addEventListener('load', () => {
            setTimeout(() => {
                const tour = initToolNameTour();
                tour.start();
            }, 1500); // Delay per smooth UX
        });
    }
}

// Rendi disponibile globalmente
if (typeof window !== 'undefined') {
    window.startToolNameTour = startToolNameTour;
}
```

---

## 🎨 UI Components per Tour

### Bottone "Tour Guidato" (da aggiungere in ogni tool)

```blade
<!-- In tool-header actions -->
<button @click="startOnboarding()"
        class="btn-secondary btn-sm inline-flex items-center gap-2">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    Tour Guidato
</button>

@push('scripts')
<script>
function startOnboarding() {
    if (typeof window.startToolNameTour === 'function') {
        window.startToolNameTour();
    }
}
</script>
@endpush
```

### Custom Shepherd CSS Theme

```css
/* resources/css/shepherd-custom.css */

.ainstein-tour-step {
    @apply rounded-xl shadow-2xl border border-gray-200;
    max-width: 400px;
}

.ainstein-tour-step .shepherd-header {
    @apply bg-gradient-to-r from-indigo-600 to-purple-600;
    @apply text-white px-6 py-4 rounded-t-xl;
}

.ainstein-tour-step .shepherd-title {
    @apply text-lg font-semibold;
}

.ainstein-tour-step .shepherd-cancel-icon {
    @apply text-white hover:text-gray-200;
}

.ainstein-tour-step .shepherd-text {
    @apply px-6 py-4 text-gray-700;
}

.ainstein-tour-step .onboarding-content {
    @apply text-sm;
}

.ainstein-tour-step .onboarding-content ul {
    @apply text-sm;
}

.ainstein-tour-step .shepherd-footer {
    @apply px-6 py-4 bg-gray-50 rounded-b-xl;
    @apply flex justify-end gap-3;
}

.ainstein-tour-step .shepherd-button-primary {
    @apply bg-indigo-600 text-white px-4 py-2 rounded-lg;
    @apply hover:bg-indigo-700 transition-colors;
    @apply font-medium text-sm;
}

.ainstein-tour-step .shepherd-button-secondary {
    @apply bg-white text-gray-700 px-4 py-2 rounded-lg;
    @apply border border-gray-300 hover:bg-gray-50;
    @apply font-medium text-sm;
}

/* Modal Overlay */
.shepherd-modal-overlay-container {
    @apply bg-black bg-opacity-50;
}

/* Highlighted element */
.shepherd-enabled.shepherd-element {
    @apply relative z-50;
}

/* Arrow */
.shepherd-arrow {
    @apply border-indigo-600;
}
```

---

## 🚀 Implementazione Nuovi Tour

### Step-by-Step per Aggiungere Tour a Nuovo Tool

1. **Crea file tour**: `resources/js/tours/nome-tool-tour.js`

2. **Copia template** da sezione "Template Tour" sopra

3. **Personalizza step**:
   - Modifica `tour.tourName = 'nome-tool'`
   - Adatta testi e descrizioni
   - Aggiorna selettori CSS (`attachTo.element`)

4. **Import in `app.js`**:
```javascript
// resources/js/app.js
import { autoStartToolNameTour } from './tours/nome-tool-tour.js';

// Auto-start condizionale
if (window.location.pathname.includes('/tools/nome-tool')) {
    autoStartToolNameTour();
}
```

5. **Aggiungi bottone "Tour Guidato"** nella view tool

6. **Aggiungi tool a lista validi** in `OnboardingController.php`

7. **Test**:
   - Reset onboarding: `POST /dashboard/onboarding/tool/nome-tool/reset`
   - Verifica auto-start
   - Verifica checkbox "non mostrare più"

---

## 📊 Admin Panel - Onboarding Analytics

### View Onboarding Completion Rates

```php
// app/Http/Controllers/Admin/AnalyticsController.php

public function onboardingStats()
{
    $stats = [
        'main_completion_rate' => User::where('onboarding_completed', true)->count() / User::count() * 100,
        'tools_completion' => [],
    ];

    $tools = ['pages', 'content-generation', 'prompts', 'api-keys', /* ... */];

    foreach ($tools as $tool) {
        $completed = User::whereJsonContains('onboarding_tools_completed', $tool)->count();
        $stats['tools_completion'][$tool] = [
            'count' => $completed,
            'rate' => $completed / User::count() * 100,
        ];
    }

    return view('admin.analytics.onboarding', compact('stats'));
}
```

---

## ✅ Checklist per Ogni Nuovo Tool

- [ ] Creato file tour in `resources/js/tours/`
- [ ] Tour esporta funzioni `initTour()`, `startTour()`, `autoStartTour()`
- [ ] Aggiunto `tour.tourName` identificativo
- [ ] Step welcome + feature highlights + completion
- [ ] Checkbox "non mostrare più" nello step finale
- [ ] Bottone "Tour Guidato" nella UI del tool
- [ ] Tool aggiunto a `$validTools` in controller
- [ ] Import in `app.js` con auto-start condizionale
- [ ] Selettori CSS testati su elementi reali
- [ ] Testi tradotti in italiano
- [ ] Test reset/completion funzionante

---

## 🎯 Best Practices

### DO ✅
- Mantieni tour brevi (max 6-7 step)
- Usa emoji per rendere friendly
- Fornisci tips pratici in ogni step
- Testa con utenti reali
- Permetti sempre skip
- Offri "non mostrare più"

### DON'T ❌
- Non sovraccaricare di informazioni
- Non usare selettori CSS fragili
- Non forzare tour a utenti esperti
- Non bloccare l'UI completamente
- Non dimenticare mobile responsiveness
- Non ripetere tour già visti

---

## 🔄 Feature Tours (Nuove Release)

Per comunicare nuove features:

```javascript
// Feature Tour Example
export function initFeatureTour_v2_5() {
    const tour = new Shepherd.Tour(shepherdConfig);
    tour.tourName = 'feature-copilot-v2.5';

    tour.addStep({
        id: 'new-feature',
        title: '🎉 Novità: Ainstein Copilot!',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Abbiamo rilasciato una nuova funzionalità!</p>
                <p class="mb-3"><strong>Ainstein Copilot</strong> è il tuo assistente AI personale.</p>
                <p class="text-sm">Clicca sull'icona 🤖 per iniziare a chattare.</p>
            </div>
        `,
        attachTo: {
            element: '.copilot-trigger',
            on: 'left'
        },
        buttons: [
            {
                text: '✓ Ho capito',
                classes: 'shepherd-button-primary',
                action: tour.complete
            }
        ]
    });

    return tour;
}
```

---

**Next Step**: Implementa tour per tutti i tool esistenti seguendo questo sistema! 🚀
