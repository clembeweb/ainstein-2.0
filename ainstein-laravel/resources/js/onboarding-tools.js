/**
 * Ainstein Platform - Tool-specific Onboarding Tours
 * Individual guided tours for each tool/section
 */

/**
 * 1. PAGES MANAGEMENT ONBOARDING
 */
export function initPagesOnboardingTour() {
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

    // Step 1: Welcome to Pages
    tour.addStep({
        id: 'pages-welcome',
        title: '📄 Benvenuto in Pages',
        text: `
            <div class="onboarding-content">
                <p class="mb-4">Questa è la sezione dove gestisci tutte le pagine del tuo sito web.</p>
                <p class="mb-4">Imparerai a:</p>
                <ul class="list-disc pl-5 space-y-2 text-sm">
                    <li>Creare nuove pagine</li>
                    <li>Organizzare le pagine per categoria</li>
                    <li>Gestire le keyword SEO</li>
                    <li>Generare contenuti AI per ogni pagina</li>
                </ul>
            </div>
        `,
        buttons: [
            {
                text: 'Salta',
                classes: 'shepherd-button-secondary',
                action: tour.cancel
            },
            {
                text: 'Continua →',
                classes: 'shepherd-button-primary',
                action: tour.next
            }
        ]
    });

    // Step 2: Create Page Button
    tour.addStep({
        id: 'pages-create-button',
        title: '➕ Crea Nuova Pagina',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Clicca su questo pulsante per creare una nuova pagina.</p>
                <p class="text-sm mb-3">Ogni pagina rappresenta un URL del tuo sito web.</p>
                <p class="text-xs text-gray-500">💡 Tip: Inizia con le pagine principali del tuo sito</p>
            </div>
        `,
        attachTo: {
            element: 'a[href*="pages.create"], a[href*="pages/create"]',
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

    // Step 3: Pages Table
    if (document.querySelector('table')) {
        tour.addStep({
            id: 'pages-table',
            title: '📋 Elenco Pagine',
            text: `
                <div class="onboarding-content">
                    <p class="mb-3">Questa tabella mostra tutte le tue pagine con:</p>
                    <ul class="list-disc pl-5 space-y-1 text-sm">
                        <li><strong>URL Path:</strong> Percorso della pagina</li>
                        <li><strong>Keyword:</strong> Parola chiave SEO principale</li>
                        <li><strong>Category:</strong> Categoria di appartenenza</li>
                        <li><strong>Status:</strong> Stato della pagina</li>
                        <li><strong>Generations:</strong> Numero di contenuti generati</li>
                    </ul>
                </div>
            `,
            attachTo: {
                element: 'table',
                on: 'top'
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
    }

    // Step 4: Filters
    if (document.querySelector('input[type="search"], select[name="category"]')) {
        tour.addStep({
            id: 'pages-filters',
            title: '🔍 Filtra Pagine',
            text: `
                <div class="onboarding-content">
                    <p class="mb-3">Usa i filtri per trovare rapidamente le pagine:</p>
                    <ul class="list-disc pl-5 space-y-1 text-sm">
                        <li><strong>Ricerca:</strong> Cerca per URL o keyword</li>
                        <li><strong>Categoria:</strong> Filtra per categoria</li>
                        <li><strong>Status:</strong> Visualizza solo pagine attive/inattive</li>
                    </ul>
                </div>
            `,
            attachTo: {
                element: 'input[type="search"], select[name="category"]',
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
    }

    // Final Step
    tour.addStep({
        id: 'pages-complete',
        title: '✅ Hai capito tutto!',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Perfetto! Ora sai come gestire le pagine.</p>
                <p class="mb-3 text-sm">Prossimo passo: Crea la tua prima pagina e genera contenuto AI!</p>

                <div class="border-t pt-4 mt-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="dont-show-pages-again" class="rounded border-gray-300 text-blue-600 mr-2">
                        <span class="text-sm">Non mostrare più questo tour</span>
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
                action: function() {
                    const dontShow = document.getElementById('dont-show-pages-again').checked;
                    if (dontShow) {
                        completeToolOnboarding('pages');
                    }
                    tour.complete();
                }
            }
        ]
    });

    return tour;
}

/**
 * 2. CONTENT GENERATION ONBOARDING
 */
export function initContentGenerationOnboardingTour() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'shepherd-theme-custom',
            scrollTo: { behavior: 'smooth', block: 'center' },
            cancelIcon: { enabled: true }
        }
    });

    tour.addStep({
        id: 'gen-welcome',
        title: '🤖 Generazione Contenuti AI',
        text: `
            <div class="onboarding-content">
                <p class="mb-4">Qui puoi vedere e gestire tutti i contenuti generati dall'intelligenza artificiale.</p>
                <p class="mb-4">Potrai:</p>
                <ul class="list-disc pl-5 space-y-2 text-sm">
                    <li>Visualizzare tutti i contenuti generati</li>
                    <li>Filtrare per stato (completati, in corso, falliti)</li>
                    <li>Copiare e modificare i contenuti</li>
                    <li>Vedere l'utilizzo dei token</li>
                </ul>
            </div>
        `,
        buttons: [
            { text: 'Salta', classes: 'shepherd-button-secondary', action: tour.cancel },
            { text: 'Continua →', classes: 'shepherd-button-primary', action: tour.next }
        ]
    });

    // Content List
    if (document.querySelector('.generation-item, table')) {
        tour.addStep({
            id: 'gen-list',
            title: '📝 Lista Generazioni',
            text: `
                <div class="onboarding-content">
                    <p class="mb-3">Ogni riga mostra:</p>
                    <ul class="list-disc pl-5 space-y-1 text-sm">
                        <li><strong>Pagina:</strong> La pagina per cui è stato generato</li>
                        <li><strong>Prompt:</strong> Il template usato</li>
                        <li><strong>Status:</strong> Completato/In corso/Fallito</li>
                        <li><strong>Token:</strong> Token AI consumati</li>
                        <li><strong>Data:</strong> Quando è stato generato</li>
                    </ul>
                    <p class="text-xs text-gray-500 mt-3">💡 Clicca su una generazione per vedere il contenuto completo</p>
                </div>
            `,
            attachTo: {
                element: '.generation-item, table',
                on: 'top'
            },
            buttons: [
                { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Continua →', classes: 'shepherd-button-primary', action: tour.next }
            ]
        });
    }

    // Status Badges
    tour.addStep({
        id: 'gen-status',
        title: '🎯 Stati Generazione',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Gli stati possibili sono:</p>
                <ul class="list-disc pl-5 space-y-2 text-sm">
                    <li><span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Completed</span> - Generazione riuscita</li>
                    <li><span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Processing</span> - In elaborazione</li>
                    <li><span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">Pending</span> - In attesa</li>
                    <li><span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Failed</span> - Errore</li>
                </ul>
            </div>
        `,
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            { text: 'Continua →', classes: 'shepherd-button-primary', action: tour.next }
        ]
    });

    // Final
    tour.addStep({
        id: 'gen-complete',
        title: '✅ Tour Completato!',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Ottimo! Ora sai come gestire le generazioni AI.</p>
                <p class="mb-3 text-sm">Per generare nuovo contenuto, vai su una pagina e clicca su "Generate Content"</p>
                <div class="border-t pt-4 mt-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="dont-show-gen-again" class="rounded border-gray-300 text-blue-600 mr-2">
                        <span class="text-sm">Non mostrare più questo tour</span>
                    </label>
                </div>
            </div>
        `,
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            {
                text: '✓ Ho capito',
                classes: 'shepherd-button-primary',
                action: function() {
                    if (document.getElementById('dont-show-gen-again').checked) {
                        completeToolOnboarding('content-generation');
                    }
                    tour.complete();
                }
            }
        ]
    });

    return tour;
}

/**
 * 3. PROMPTS LIBRARY ONBOARDING
 */
export function initPromptsOnboardingTour() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'shepherd-theme-custom',
            scrollTo: { behavior: 'smooth', block: 'center' },
            cancelIcon: { enabled: true }
        }
    });

    tour.addStep({
        id: 'prompts-welcome',
        title: '💬 Library Prompts',
        text: `
            <div class="onboarding-content">
                <p class="mb-4">I Prompts sono template predefiniti per generare contenuti specifici.</p>
                <p class="mb-4">Cosa puoi fare:</p>
                <ul class="list-disc pl-5 space-y-2 text-sm">
                    <li>Usare prompts pronti per SEO, blog, social media</li>
                    <li>Creare prompts personalizzati</li>
                    <li>Modificare variabili e parametri</li>
                    <li>Organizzare per categoria</li>
                </ul>
            </div>
        `,
        buttons: [
            { text: 'Salta', classes: 'shepherd-button-secondary', action: tour.cancel },
            { text: 'Continua →', classes: 'shepherd-button-primary', action: tour.next }
        ]
    });

    // Prompt Cards
    if (document.querySelector('.prompt-card, .grid')) {
        tour.addStep({
            id: 'prompts-cards',
            title: '🎴 Card Prompts',
            text: `
                <div class="onboarding-content">
                    <p class="mb-3">Ogni card mostra:</p>
                    <ul class="list-disc pl-5 space-y-1 text-sm">
                        <li><strong>Nome:</strong> Identificativo del prompt</li>
                        <li><strong>Categoria:</strong> Blog, SEO, Social, etc.</li>
                        <li><strong>Variabili:</strong> Parametri personalizzabili</li>
                        <li><strong>Status:</strong> Attivo/Inattivo</li>
                    </ul>
                    <p class="text-xs text-gray-500 mt-3">💡 Clicca su "Use Prompt" per applicarlo ad una pagina</p>
                </div>
            `,
            attachTo: {
                element: '.prompt-card, .grid',
                on: 'top'
            },
            buttons: [
                { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Continua →', classes: 'shepherd-button-primary', action: tour.next }
            ]
        });
    }

    // Categories
    tour.addStep({
        id: 'prompts-categories',
        title: '📂 Categorie Prompts',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Le categorie principali sono:</p>
                <ul class="list-disc pl-5 space-y-1 text-sm">
                    <li><strong>SEO:</strong> Meta tag, title, descriptions</li>
                    <li><strong>Blog:</strong> Articoli, guide, tutorial</li>
                    <li><strong>Social:</strong> Post Instagram, LinkedIn, Twitter</li>
                    <li><strong>E-commerce:</strong> Descrizioni prodotti</li>
                    <li><strong>Marketing:</strong> Email, landing pages</li>
                </ul>
            </div>
        `,
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            { text: 'Continua →', classes: 'shepherd-button-primary', action: tour.next }
        ]
    });

    // Variables
    tour.addStep({
        id: 'prompts-variables',
        title: '⚙️ Variabili Dinamiche',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">I prompts usano variabili come:</p>
                <ul class="list-disc pl-5 space-y-1 text-sm">
                    <li><code>{{keyword}}</code> - Parola chiave target</li>
                    <li><code>{{word_count}}</code> - Lunghezza desiderata</li>
                    <li><code>{{tone}}</code> - Tono di voce</li>
                    <li><code>{{language}}</code> - Lingua output</li>
                </ul>
                <p class="text-xs text-gray-500 mt-3">💡 Compila le variabili quando usi il prompt</p>
            </div>
        `,
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            { text: 'Continua →', classes: 'shepherd-button-primary', action: tour.next }
        ]
    });

    // Final
    tour.addStep({
        id: 'prompts-complete',
        title: '✅ Perfetto!',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Ora sai come usare i prompts!</p>
                <p class="mb-3 text-sm">Prova ad applicare un prompt ad una delle tue pagine per generare contenuto</p>
                <div class="border-t pt-4 mt-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="dont-show-prompts-again" class="rounded border-gray-300 text-blue-600 mr-2">
                        <span class="text-sm">Non mostrare più questo tour</span>
                    </label>
                </div>
            </div>
        `,
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            {
                text: '✓ Ho capito',
                classes: 'shepherd-button-primary',
                action: function() {
                    if (document.getElementById('dont-show-prompts-again').checked) {
                        completeToolOnboarding('prompts');
                    }
                    tour.complete();
                }
            }
        ]
    });

    return tour;
}

/**
 * 4. API KEYS ONBOARDING
 */
export function initApiKeysOnboardingTour() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'shepherd-theme-custom',
            scrollTo: { behavior: 'smooth', block: 'center' },
            cancelIcon: { enabled: true }
        }
    });

    tour.addStep({
        id: 'api-welcome',
        title: '🔑 API Keys',
        text: `
            <div class="onboarding-content">
                <p class="mb-4">Le API Keys ti permettono di integrare Ainstein con i tuoi sistemi esterni.</p>
                <p class="mb-4">Potrai:</p>
                <ul class="list-disc pl-5 space-y-2 text-sm">
                    <li>Generare chiavi API sicure</li>
                    <li>Impostare date di scadenza</li>
                    <li>Revocare chiavi compromesse</li>
                    <li>Monitorare l'utilizzo</li>
                </ul>
            </div>
        `,
        buttons: [
            { text: 'Salta', classes: 'shepherd-button-secondary', action: tour.cancel },
            { text: 'Continua →', classes: 'shepherd-button-primary', action: tour.next }
        ]
    });

    // Create API Key Button
    if (document.querySelector('button[id*="create"], a[href*="create"]')) {
        tour.addStep({
            id: 'api-create',
            title: '➕ Genera Nuova API Key',
            text: `
                <div class="onboarding-content">
                    <p class="mb-3">Clicca qui per generare una nuova chiave API.</p>
                    <p class="text-sm mb-3"><strong>⚠️ Importante:</strong> La chiave completa sarà visibile solo una volta alla creazione!</p>
                    <p class="text-xs text-gray-500">💡 Copia e salva la chiave in un posto sicuro</p>
                </div>
            `,
            attachTo: {
                element: 'button[id*="create"], a[href*="create"]',
                on: 'bottom'
            },
            buttons: [
                { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Continua →', classes: 'shepherd-button-primary', action: tour.next }
            ]
        });
    }

    // API Keys Table
    if (document.querySelector('table, .api-key-item')) {
        tour.addStep({
            id: 'api-table',
            title: '📋 Gestione Chiavi',
            text: `
                <div class="onboarding-content">
                    <p class="mb-3">Per ogni chiave API vedi:</p>
                    <ul class="list-disc pl-5 space-y-1 text-sm">
                        <li><strong>Nome:</strong> Identificativo della chiave</li>
                        <li><strong>Key:</strong> Chiave parziale (per sicurezza)</li>
                        <li><strong>Status:</strong> Attiva/Scaduta/Revocata</li>
                        <li><strong>Scadenza:</strong> Data di scadenza (se impostata)</li>
                        <li><strong>Creata:</strong> Data di creazione</li>
                    </ul>
                </div>
            `,
            attachTo: {
                element: 'table, .api-key-item',
                on: 'top'
            },
            buttons: [
                { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
                { text: 'Continua →', classes: 'shepherd-button-primary', action: tour.next }
            ]
        });
    }

    // Security Best Practices
    tour.addStep({
        id: 'api-security',
        title: '🔒 Sicurezza',
        text: `
            <div class="onboarding-content">
                <p class="mb-3"><strong>Best Practices per le API Keys:</strong></p>
                <ul class="list-disc pl-5 space-y-2 text-sm">
                    <li>Non condividere mai le tue chiavi pubblicamente</li>
                    <li>Usa chiavi diverse per ambienti diversi (dev/prod)</li>
                    <li>Imposta date di scadenza ragionevoli</li>
                    <li>Revoca immediatamente chiavi compromesse</li>
                    <li>Monitora l'utilizzo regolarmente</li>
                </ul>
            </div>
        `,
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            { text: 'Continua →', classes: 'shepherd-button-primary', action: tour.next }
        ]
    });

    // Final
    tour.addStep({
        id: 'api-complete',
        title: '✅ Tutto Chiaro!',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Perfetto! Ora sai come gestire le API Keys.</p>
                <p class="mb-3 text-sm">Consulta la documentazione API per esempi di integrazione</p>
                <div class="border-t pt-4 mt-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="dont-show-api-again" class="rounded border-gray-300 text-blue-600 mr-2">
                        <span class="text-sm">Non mostrare più questo tour</span>
                    </label>
                </div>
            </div>
        `,
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            {
                text: '✓ Ho capito',
                classes: 'shepherd-button-primary',
                action: function() {
                    if (document.getElementById('dont-show-api-again').checked) {
                        completeToolOnboarding('api-keys');
                    }
                    tour.complete();
                }
            }
        ]
    });

    return tour;
}

/**
 * Helper function to complete tool onboarding
 */
function completeToolOnboarding(tool) {
    fetch(`/dashboard/onboarding/tool/${tool}/complete`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    }).then(response => {
        if (response.ok) {
            console.log(`✅ Tool onboarding '${tool}' marked as completed`);
        }
    }).catch(err => {
        console.error('Error completing tool onboarding:', err);
    });
}

/**
 * Auto-start tool tour based on current page (DISABLED - manual start only)
 */
export function autoStartToolOnboarding() {
    // Auto-start disabled - users will click "Inizia Tour" button
    console.log('Tool onboarding: Manual start mode enabled');
}

// Make functions globally available for manual triggers
window.startPagesOnboarding = () => initPagesOnboardingTour().start();
window.startContentGenerationOnboarding = () => initContentGenerationOnboardingTour().start();
window.startPromptsOnboarding = () => initPromptsOnboardingTour().start();
window.startApiKeysOnboarding = () => initApiKeysOnboardingTour().start();
