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

/**
 * 5. CONTENT GENERATOR GUIDED ONBOARDING (Step-by-step to first generation)
 * Guida l'utente attraverso il processo completo di creazione della prima generazione
 */
export function initContentGeneratorOnboardingTour() {
    const tour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'shepherd-theme-custom',
            scrollTo: { behavior: 'smooth', block: 'center' },
            cancelIcon: { enabled: true }
        }
    });

    // Step 1: Welcome - Introduzione al flusso completo
    tour.addStep({
        id: 'cg-welcome',
        title: '🎉 Benvenuto nel Content Generator!',
        text: `
            <div class="onboarding-content">
                <p class="mb-4">Ti guiderò passo-passo per creare la tua <strong>prima generazione di contenuto AI</strong>.</p>
                <p class="mb-4">Il processo è semplice:</p>
                <ol class="list-decimal pl-5 space-y-2 text-sm">
                    <li><strong>Crea una pagina</strong> - Definisci l'URL e la keyword</li>
                    <li><strong>Scegli un prompt</strong> - Seleziona un template</li>
                    <li><strong>Genera il contenuto</strong> - Lascia che l'AI faccia il lavoro</li>
                    <li><strong>Modifica e usa</strong> - Personalizza il risultato</li>
                </ol>
                <p class="text-xs text-gray-500 mt-4">💡 Tempo stimato: 3-5 minuti</p>
            </div>
        `,
        buttons: [
            { text: 'Salta', classes: 'shepherd-button-secondary', action: tour.cancel },
            { text: 'Iniziamo! →', classes: 'shepherd-button-primary', action: tour.next }
        ]
    });

    // Step 2: Overview delle 3 sezioni
    tour.addStep({
        id: 'cg-overview',
        title: '📋 Le 3 Sezioni del Content Generator',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Il tool è diviso in 3 tab:</p>
                <div class="space-y-3 text-sm">
                    <div class="flex items-start">
                        <span class="text-2xl mr-3">📄</span>
                        <div>
                            <strong>Pages</strong><br>
                            <span class="text-gray-600">Le pagine del tuo sito web</span>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="text-2xl mr-3">🤖</span>
                        <div>
                            <strong>Generations</strong><br>
                            <span class="text-gray-600">I contenuti AI già generati</span>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="text-2xl mr-3">💬</span>
                        <div>
                            <strong>Prompts</strong><br>
                            <span class="text-gray-600">Template per generare contenuti</span>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-4">💡 Ora ti mostrerò come usarli per creare la tua prima generazione</p>
            </div>
        `,
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            { text: 'Continua →', classes: 'shepherd-button-primary', action: tour.next }
        ]
    });

    // Step 3: PASSO 1 - Crea la tua prima pagina
    tour.addStep({
        id: 'cg-step1-create-page',
        title: '📄 PASSO 1: Crea una Pagina',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Per generare contenuti AI, devi prima creare una <strong>pagina</strong>.</p>
                <p class="mb-3 text-sm">Una pagina rappresenta una URL del tuo sito, ad esempio:</p>
                <ul class="list-disc pl-5 space-y-1 text-sm text-gray-700">
                    <li><code>/prodotti/scarpe-running</code></li>
                    <li><code>/blog/guida-seo</code></li>
                    <li><code>/servizi/consulenza</code></li>
                </ul>
                <p class="mt-4 mb-2 text-sm"><strong>Clicca su "Create Page"</strong> per iniziare!</p>
                <p class="text-xs text-gray-500">💡 Non preoccuparti, ti guiderò nella compilazione</p>
            </div>
        `,
        attachTo: {
            element: 'a[href*="pages.create"], a[href*="pages/create"]',
            on: 'bottom'
        },
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            {
                text: 'Aspetta, prima spiegami i campi →',
                classes: 'shepherd-button-primary',
                action: tour.next
            }
        ]
    });

    // Step 4: Spiegazione campi form Create Page
    tour.addStep({
        id: 'cg-step1-form-fields',
        title: '📝 Campi del Form Pagina',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Quando crei una pagina, dovrai compilare:</p>
                <div class="space-y-3 text-sm">
                    <div>
                        <strong>🔗 URL Path</strong> (obbligatorio)<br>
                        <span class="text-gray-600">Es: <code>/prodotti/scarpe</code></span>
                    </div>
                    <div>
                        <strong>🎯 Keyword</strong> (obbligatorio)<br>
                        <span class="text-gray-600">La parola chiave SEO principale<br>Es: "scarpe da running Nike"</span>
                    </div>
                    <div>
                        <strong>📂 Category</strong> (opzionale)<br>
                        <span class="text-gray-600">Per organizzare le tue pagine<br>Es: "E-commerce", "Blog", "Landing Page"</span>
                    </div>
                    <div>
                        <strong>🌍 Language</strong> (default: IT)<br>
                        <span class="text-gray-600">Lingua del contenuto da generare</span>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-4">💡 La keyword è fondamentale: l'AI la userà per creare contenuto SEO-ottimizzato</p>
            </div>
        `,
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            { text: 'Ok, ora clicco "Create Page" →', classes: 'shepherd-button-primary', action: tour.next }
        ]
    });

    // Step 5: Dopo aver creato la pagina - vai su Prompts
    tour.addStep({
        id: 'cg-step2-prompts-intro',
        title: '💬 PASSO 2: Scegli un Prompt Template',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Ottimo! Hai creato la tua prima pagina 🎉</p>
                <p class="mb-3">Ora devi scegliere un <strong>Prompt Template</strong> per generare il contenuto.</p>
                <p class="mb-3 text-sm">I prompt sono istruzioni pre-configurate per l'AI, ad esempio:</p>
                <ul class="list-disc pl-5 space-y-1 text-sm text-gray-700">
                    <li><strong>SEO Article:</strong> Articolo ottimizzato per i motori di ricerca</li>
                    <li><strong>Product Description:</strong> Descrizione coinvolgente di un prodotto</li>
                    <li><strong>Landing Page:</strong> Testo persuasivo per conversioni</li>
                    <li><strong>Blog Post:</strong> Articolo informativo per blog</li>
                </ul>
                <p class="mt-4 text-sm"><strong>Clicca sul tab "Prompts"</strong> per vedere i template disponibili</p>
            </div>
        `,
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            { text: 'Continua →', classes: 'shepherd-button-primary', action: tour.next }
        ]
    });

    // Step 6: Nel tab Prompts - spiega come scegliere
    tour.addStep({
        id: 'cg-step2-prompts-choose',
        title: '🎴 Come Scegliere il Prompt Giusto',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Ogni card mostra:</p>
                <ul class="list-disc pl-5 space-y-2 text-sm">
                    <li><strong>Nome:</strong> Tipo di contenuto (es. "SEO Article")</li>
                    <li><strong>Categoria:</strong> Blog, SEO, E-commerce, etc.</li>
                    <li><strong>Variabili:</strong> Parametri personalizzabili come {{keyword}}, {{word_count}}</li>
                    <li><strong>Badge System/Custom:</strong> I prompt "System" sono predefiniti, i "Custom" sono creati da te</li>
                    <li><strong>Status Active/Inactive:</strong> Solo i prompt attivi sono utilizzabili</li>
                </ul>
                <p class="mt-4 text-sm"><strong>💡 Tip:</strong> Per la tua prima prova, usa un prompt "System" nella categoria "SEO" o "Blog"</p>
                <p class="text-xs text-gray-500 mt-2">Puoi duplicare e personalizzare qualsiasi prompt!</p>
            </div>
        `,
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            { text: 'Ho capito, torno su Pages →', classes: 'shepherd-button-primary', action: tour.next }
        ]
    });

    // Step 7: PASSO 3 - Torna su Pages e genera
    tour.addStep({
        id: 'cg-step3-generate',
        title: '🪄 PASSO 3: Genera il Contenuto AI',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Perfetto! Ora hai:</p>
                <ul class="list-disc pl-5 space-y-1 text-sm mb-4">
                    <li>✅ Una pagina creata</li>
                    <li>✅ Un'idea di quale prompt usare</li>
                </ul>
                <p class="mb-3 text-sm"><strong>Ora torniamo sul tab "Pages"</strong></p>
                <p class="mb-3">Sulla riga della tua pagina troverai 3 icone:</p>
                <div class="space-y-2 text-sm ml-4">
                    <div><i class="fas fa-edit text-blue-600"></i> <strong>Edit</strong> - Modifica la pagina</div>
                    <div><i class="fas fa-magic text-purple-600"></i> <strong>Generate</strong> - <span class="bg-yellow-100 px-2 py-1 rounded">Clicca questa!</span></div>
                    <div><i class="fas fa-trash text-red-600"></i> <strong>Delete</strong> - Elimina la pagina</div>
                </div>
                <p class="mt-4 text-sm"><strong>Clicca sull'icona viola "Generate Content" (bacchetta magica)</strong></p>
            </div>
        `,
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            { text: 'Cosa succede dopo? →', classes: 'shepherd-button-primary', action: tour.next }
        ]
    });

    // Step 8: Form di generazione
    tour.addStep({
        id: 'cg-step3-generation-form',
        title: '⚙️ Form di Generazione',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Nel form di generazione dovrai:</p>
                <ol class="list-decimal pl-5 space-y-2 text-sm">
                    <li><strong>Selezionare un Prompt</strong> dal menu dropdown<br>
                    <span class="text-xs text-gray-500">Qui trovi tutti i prompt disponibili</span></li>

                    <li><strong>Compilare le variabili</strong> richieste<br>
                    <span class="text-xs text-gray-500">Es: word_count, tone, target_audience</span></li>

                    <li><strong>Verificare la keyword</strong><br>
                    <span class="text-xs text-gray-500">Verrà precompilata con quella della pagina</span></li>

                    <li><strong>Cliccare "Generate"</strong><br>
                    <span class="text-xs text-gray-500">L'AI inizierà a lavorare!</span></li>
                </ol>
                <p class="mt-4 text-xs text-gray-500">💡 La generazione richiede ~10-30 secondi a seconda della lunghezza richiesta</p>
            </div>
        `,
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            { text: 'Continua →', classes: 'shepherd-button-primary', action: tour.next }
        ]
    });

    // Step 9: PASSO 4 - Monitora nel tab Generations
    tour.addStep({
        id: 'cg-step4-monitor',
        title: '📊 PASSO 4: Monitora la Generazione',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Dopo aver cliccato "Generate", sarai reindirizzato al <strong>tab "Generations"</strong>.</p>
                <p class="mb-3">Qui vedrai la tua generazione con uno di questi stati:</p>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800 mr-2">
                            <i class="fas fa-clock mr-1"></i>Pending
                        </span>
                        <span class="text-gray-600">In attesa di elaborazione</span>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800 mr-2">
                            <i class="fas fa-spinner fa-spin mr-1"></i>Processing
                        </span>
                        <span class="text-gray-600">L'AI sta generando il contenuto</span>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800 mr-2">
                            <i class="fas fa-check mr-1"></i>Completed
                        </span>
                        <span class="text-gray-600">Pronto da usare!</span>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-red-100 text-red-800 mr-2">
                            <i class="fas fa-times mr-1"></i>Failed
                        </span>
                        <span class="text-gray-600">Errore (riprova)</span>
                    </div>
                </div>
                <p class="mt-4 text-xs text-gray-500">💡 Puoi ricaricare la pagina per vedere lo stato aggiornato</p>
            </div>
        `,
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            { text: 'Continua →', classes: 'shepherd-button-primary', action: tour.next }
        ]
    });

    // Step 10: Azioni sulla generation completata
    tour.addStep({
        id: 'cg-step5-actions',
        title: '✨ PASSO 5: Usa il Contenuto Generato',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Quando la generation è <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Completed</span>, puoi:</p>
                <div class="space-y-3 text-sm">
                    <div class="flex items-start">
                        <i class="fas fa-eye text-blue-600 mt-1 mr-3 text-lg"></i>
                        <div>
                            <strong>View</strong><br>
                            <span class="text-gray-600">Visualizza il contenuto completo</span>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-edit text-purple-600 mt-1 mr-3 text-lg"></i>
                        <div>
                            <strong>Edit</strong><br>
                            <span class="text-gray-600">Modifica e personalizza il testo</span>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-copy text-green-600 mt-1 mr-3 text-lg"></i>
                        <div>
                            <strong>Copy</strong><br>
                            <span class="text-gray-600">Copia negli appunti (per usarlo subito)</span>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <i class="fas fa-trash text-red-600 mt-1 mr-3 text-lg"></i>
                        <div>
                            <strong>Delete</strong><br>
                            <span class="text-gray-600">Elimina se non ti serve più</span>
                        </div>
                    </div>
                </div>
                <p class="mt-4 text-xs text-gray-500">💡 Tip: Usa "Edit" per perfezionare il contenuto AI prima di pubblicarlo</p>
            </div>
        `,
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            { text: 'Continua →', classes: 'shepherd-button-primary', action: tour.next }
        ]
    });

    // Step 11: Token Usage
    tour.addStep({
        id: 'cg-tokens',
        title: '💰 Token Usage & Costi',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Ogni generazione consuma <strong>token AI</strong>.</p>
                <p class="mb-3 text-sm">Nella tabella Generations vedrai:</p>
                <ul class="list-disc pl-5 space-y-1 text-sm">
                    <li><strong>Tokens Used:</strong> Numero di token consumati</li>
                    <li><strong>AI Model:</strong> Modello usato (gpt-4, gpt-4o-mini, etc.)</li>
                </ul>
                <p class="mt-3 text-sm"><strong>Esempio:</strong></p>
                <div class="bg-gray-50 p-3 rounded text-xs">
                    <div>📊 Un articolo di ~800 parole = <strong>~1.000 token</strong></div>
                    <div>📊 Una descrizione prodotto = <strong>~200-300 token</strong></div>
                    <div>📊 Un post social = <strong>~100-150 token</strong></div>
                </div>
                <p class="mt-3 text-xs text-gray-500">💡 Monitora l'utilizzo mensile dalla Dashboard</p>
            </div>
        `,
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            { text: 'Continua →', classes: 'shepherd-button-primary', action: tour.next }
        ]
    });

    // Step 12: Tips & Best Practices
    tour.addStep({
        id: 'cg-tips',
        title: '💡 Tips & Best Practices',
        text: `
            <div class="onboarding-content">
                <p class="mb-3"><strong>Per ottenere i migliori risultati:</strong></p>
                <div class="space-y-3 text-sm">
                    <div class="flex items-start">
                        <span class="text-xl mr-2">🎯</span>
                        <div>
                            <strong>Keyword specifiche</strong><br>
                            <span class="text-gray-600">Es: "scarpe running Nike Air Max" invece di "scarpe"</span>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="text-xl mr-2">📝</span>
                        <div>
                            <strong>Prompt dettagliati</strong><br>
                            <span class="text-gray-600">Specifica tone, target, lunghezza desiderata</span>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="text-xl mr-2">✏️</span>
                        <div>
                            <strong>Modifica sempre</strong><br>
                            <span class="text-gray-600">L'AI è brava ma non perfetta: rivedi e personalizza</span>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="text-xl mr-2">🔄</span>
                        <div>
                            <strong>Prova prompt diversi</strong><br>
                            <span class="text-gray-600">Stessa pagina + prompt diversi = risultati diversi</span>
                        </div>
                    </div>
                </div>
            </div>
        `,
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            { text: 'Continua →', classes: 'shepherd-button-primary', action: tour.next }
        ]
    });

    // Step 13: Riepilogo Flusso
    tour.addStep({
        id: 'cg-recap',
        title: '📖 Riepilogo del Flusso',
        text: `
            <div class="onboarding-content">
                <p class="mb-3">Ecco i 5 passi che hai imparato:</p>
                <ol class="list-decimal pl-5 space-y-2 text-sm">
                    <li><strong>Tab Pages</strong> → Clicca "Create Page"<br>
                    <span class="text-xs text-gray-500">Compila URL, keyword, categoria</span></li>

                    <li><strong>Tab Prompts</strong> → Esplora i template<br>
                    <span class="text-xs text-gray-500">Scegli il prompt giusto per il tuo contenuto</span></li>

                    <li><strong>Tab Pages</strong> → Clicca icona viola "Generate"<br>
                    <span class="text-xs text-gray-500">Seleziona prompt e compila variabili</span></li>

                    <li><strong>Tab Generations</strong> → Monitora lo stato<br>
                    <span class="text-xs text-gray-500">Attendi che diventi "Completed"</span></li>

                    <li><strong>Tab Generations</strong> → View/Edit/Copy<br>
                    <span class="text-xs text-gray-500">Usa il contenuto generato</span></li>
                </ol>
                <p class="mt-4 text-xs text-gray-500">💡 Puoi ripetere questo flusso infinite volte!</p>
            </div>
        `,
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            { text: 'Ho capito! →', classes: 'shepherd-button-primary', action: tour.next }
        ]
    });

    // Final Step
    tour.addStep({
        id: 'cg-complete',
        title: '🎉 Sei Pronto!',
        text: `
            <div class="onboarding-content">
                <p class="mb-4"><strong>Congratulazioni!</strong> Ora sai esattamente come usare il Content Generator.</p>
                <div class="bg-blue-50 border border-blue-200 rounded p-4 mb-4">
                    <p class="text-sm mb-2"><strong>🚀 Inizia ora:</strong></p>
                    <ol class="list-decimal pl-5 space-y-1 text-sm">
                        <li>Clicca "Create Page" nel tab Pages</li>
                        <li>Crea la tua prima pagina</li>
                        <li>Genera il primo contenuto AI</li>
                    </ol>
                </div>
                <p class="text-xs text-gray-500 mb-4">Se hai dubbi, puoi riavviare questo tour cliccando su "Tour Guidato" in alto.</p>
                <div class="border-t pt-4 mt-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="dont-show-cg-again" class="rounded border-gray-300 text-blue-600 mr-2">
                        <span class="text-sm">Non mostrare più questo tour automaticamente</span>
                    </label>
                </div>
            </div>
        `,
        buttons: [
            { text: '← Indietro', classes: 'shepherd-button-secondary', action: tour.back },
            {
                text: '✓ Inizia a generare contenuti!',
                classes: 'shepherd-button-primary',
                action: function() {
                    if (document.getElementById('dont-show-cg-again').checked) {
                        completeToolOnboarding('content-generator');
                    }
                    tour.complete();

                    // Optional: Auto-scroll to Create Page button after tour
                    setTimeout(() => {
                        const createButton = document.querySelector('a[href*="pages.create"], a[href*="pages/create"]');
                        if (createButton) {
                            createButton.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            createButton.classList.add('ring-4', 'ring-blue-400', 'ring-opacity-50');
                            setTimeout(() => {
                                createButton.classList.remove('ring-4', 'ring-blue-400', 'ring-opacity-50');
                            }, 2000);
                        }
                    }, 500);
                }
            }
        ]
    });

    return tour;
}

// Make functions globally available for manual triggers
window.startPagesOnboarding = () => initPagesOnboardingTour().start();
window.startContentGenerationOnboarding = () => initContentGenerationOnboardingTour().start();
window.startPromptsOnboarding = () => initPromptsOnboardingTour().start();
window.startApiKeysOnboarding = () => initApiKeysOnboardingTour().start();
window.startContentGeneratorOnboarding = () => initContentGeneratorOnboardingTour().start();
