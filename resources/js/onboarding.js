/**
 * Ainstein Platform - Onboarding Tour
 * Interactive guided tour for new users
 */

export function initOnboardingTour() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'shepherd-theme-custom',
            scrollTo: { behavior: 'smooth', block: 'center' },
            cancelIcon: {
                enabled: true
            }
        }
    });

    // Step 1: Welcome
    tour.addStep({
        id: 'welcome',
        title: '🎉 Benvenuto su Ainstein!',
        text: `
            <div class="onboarding-content">
                <p class="mb-4">Ciao! Sono il tuo assistente virtuale e ti guiderò alla scoperta della piattaforma.</p>
                <p class="mb-4">In pochi minuti imparerai a:</p>
                <ul class="list-disc pl-5 space-y-2 text-sm">
                    <li>Creare e gestire le tue pagine</li>
                    <li>Generare contenuti con AI</li>
                    <li>Monitorare l'utilizzo dei token</li>
                    <li>Usare le API</li>
                </ul>
            </div>
        `,
        buttons: [
            {
                text: 'Salta il tour',
                classes: 'shepherd-button-secondary',
                action: tour.cancel
            },
            {
                text: 'Iniziamo! →',
                classes: 'shepherd-button-primary',
                action: tour.next
            }
        ]
    });

    // Step 2: Statistics Cards
    tour.addStep({
        id: 'stats',
        title: '📊 Le tue statistiche',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Qui trovi un riepilogo rapido della tua attività:</p>
                <ul class="list-disc pl-5 space-y-2 text-sm">
                    <li><strong>Total Pages:</strong> Numero di pagine create</li>
                    <li><strong>Generations:</strong> Contenuti generati dall'AI</li>
                    <li><strong>Active Prompts:</strong> Template pronti all'uso</li>
                    <li><strong>API Keys:</strong> Chiavi per l'integrazione</li>
                </ul>
            </div>
        `,
        attachTo: {
            element: '.stat-card:first-child',
            on: 'bottom'
        },
        buttons: [
            {
                text: '← Indietro',
                classes: 'shepherd-button-secondary',
                action: tour.back
            },
            {
                text: 'Continua →',
                classes: 'shepherd-button-primary',
                action: tour.next
            }
        ]
    });

    // Step 3: Token Usage
    tour.addStep({
        id: 'tokens',
        title: '🎯 Utilizzo Token',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Monitora il consumo dei tuoi token AI in tempo reale.</p>
                <p class="text-sm mb-3">Ogni piano include un limite mensile di token:</p>
                <ul class="list-disc pl-5 space-y-1 text-sm">
                    <li><strong>Starter:</strong> 10.000 token/mese</li>
                    <li><strong>Professional:</strong> 100.000 token/mese</li>
                    <li><strong>Enterprise:</strong> Illimitati</li>
                </ul>
                <p class="text-xs text-gray-500 mt-3">💡 Un contenuto medio usa circa 500-1000 token</p>
            </div>
        `,
        attachTo: {
            element: '[class*="token"]',
            on: 'left'
        },
        buttons: [
            {
                text: '← Indietro',
                classes: 'shepherd-button-secondary',
                action: tour.back
            },
            {
                text: 'Continua →',
                classes: 'shepherd-button-primary',
                action: tour.next
            }
        ]
    });

    // Step 4: Quick Actions
    tour.addStep({
        id: 'quick-actions',
        title: '⚡ Azioni Rapide',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Accedi velocemente alle funzioni principali:</p>
                <ul class="list-disc pl-5 space-y-2 text-sm">
                    <li><strong>Create New Page:</strong> Aggiungi una nuova pagina</li>
                    <li><strong>Generate Content:</strong> Genera contenuto AI</li>
                    <li><strong>Manage API Keys:</strong> Gestisci le chiavi API</li>
                    <li><strong>Browse Prompts:</strong> Esplora i template</li>
                </ul>
            </div>
        `,
        attachTo: {
            element: '.space-y-3',
            on: 'left'
        },
        buttons: [
            {
                text: '← Indietro',
                classes: 'shepherd-button-secondary',
                action: tour.back
            },
            {
                text: 'Continua →',
                classes: 'shepherd-button-primary',
                action: tour.next
            }
        ]
    });

    // Step 5: Navigation
    tour.addStep({
        id: 'navigation',
        title: '🧭 Menu di Navigazione',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Usa il menu principale per navigare:</p>
                <ul class="list-disc pl-5 space-y-2 text-sm">
                    <li><strong>Dashboard:</strong> Panoramica generale</li>
                    <li><strong>Pages:</strong> Gestisci le pagine del sito</li>
                    <li><strong>Prompts:</strong> Template per i contenuti</li>
                    <li><strong>Content Generation:</strong> Genera e visualizza contenuti</li>
                    <li><strong>API Keys:</strong> Chiavi per integrazioni</li>
                </ul>
            </div>
        `,
        attachTo: {
            element: 'nav',
            on: 'bottom'
        },
        buttons: [
            {
                text: '← Indietro',
                classes: 'shepherd-button-secondary',
                action: tour.back
            },
            {
                text: 'Continua →',
                classes: 'shepherd-button-primary',
                action: tour.next
            }
        ]
    });

    // Step 6: Create First Page
    tour.addStep({
        id: 'create-page',
        title: '📄 Crea la tua prima pagina',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Pronto per iniziare? Clicca su "New Page" per creare la tua prima pagina!</p>
                <p class="text-sm mb-3">Dovrai inserire:</p>
                <ul class="list-disc pl-5 space-y-1 text-sm">
                    <li>URL della pagina (es. /prodotti/scarpe)</li>
                    <li>Keyword principale</li>
                    <li>Categoria e lingua</li>
                </ul>
                <p class="text-xs text-gray-500 mt-3">💡 Tip: Scegli keyword specifiche per risultati migliori</p>
            </div>
        `,
        attachTo: {
            element: 'a[href*="pages.create"]',
            on: 'bottom'
        },
        buttons: [
            {
                text: '← Indietro',
                classes: 'shepherd-button-secondary',
                action: tour.back
            },
            {
                text: 'Continua →',
                classes: 'shepherd-button-primary',
                action: tour.next
            }
        ]
    });

    // Step 7: Final - Don't show again
    tour.addStep({
        id: 'complete',
        title: '🎓 Tour completato!',
        text: `
            <div class="onboarding-content">
                <p class="mb-4">Ottimo lavoro! Ora sai come muoverti sulla piattaforma.</p>
                <p class="mb-4 text-sm">Se hai bisogno di aiuto, consulta:</p>
                <ul class="list-disc pl-5 space-y-1 text-sm mb-4">
                    <li>La documentazione completa</li>
                    <li>I video tutorial</li>
                    <li>Il supporto clienti</li>
                </ul>

                <div class="border-t pt-4 mt-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="dont-show-again" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 mr-2">
                        <span class="text-sm text-gray-700">Non mostrare più questo tour all'avvio</span>
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
                text: '✓ Inizia ad usare Ainstein',
                classes: 'shepherd-button-primary',
                action: function() {
                    const dontShowAgain = document.getElementById('dont-show-again').checked;

                    if (dontShowAgain) {
                        // Save preference via AJAX
                        fetch('/dashboard/onboarding/complete', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ completed: true })
                        });
                    }

                    tour.complete();
                }
            }
        ]
    });

    // Event handlers
    tour.on('cancel', () => {
        if (confirm('Sei sicuro di voler saltare il tour? Potrai riavviarlo in qualsiasi momento dalle impostazioni.')) {
            tour.cancel();
        }
    });

    return tour;
}

// Auto-start tour if user hasn't completed it
export function autoStartOnboarding(shouldShow) {
    if (shouldShow) {
        // Wait for page to be fully loaded
        window.addEventListener('load', () => {
            setTimeout(() => {
                const tour = initOnboardingTour();
                tour.start();
            }, 1000); // 1 second delay for smooth experience
        });
    }
}

// Manual tour trigger (for settings/help menu)
export function startManualTour() {
    const tour = initOnboardingTour();
    tour.start();
}

// Make it globally available
window.startOnboardingTour = startManualTour;
