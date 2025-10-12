# 🎓 Content Generator - Onboarding Guidato

**Data**: 2025-10-06
**Tool**: Content Generator (Unified View)
**Tipo**: Tour interattivo step-by-step verso prima generazione

---

## OBIETTIVO

Guidare l'utente attraverso il processo completo di creazione della **prima generazione di contenuto AI**, spiegando ogni passaggio del flusso:

1. Creare una pagina
2. Scegliere un prompt template
3. Generare il contenuto
4. Monitorare la generazione
5. Usare il contenuto generato

---

## STRUTTURA DEL TOUR

### 📋 Panoramica

**13 step totali**:
- 2 step introduttivi (welcome + overview)
- 10 step operativi (procedura guidata)
- 1 step finale (riepilogo + completamento)

**Tempo stimato**: 3-5 minuti

**Trigger**: Bottone "Tour Guidato" in alto a destra nel Content Generator

---

## DETTAGLIO STEP

### Step 1: Welcome
**ID**: `cg-welcome`
**Titolo**: 🎉 Benvenuto nel Content Generator!
**Contenuto**:
- Intro al tour guidato
- Spiegazione del flusso in 4 passi:
  1. Crea una pagina
  2. Scegli un prompt
  3. Genera il contenuto
  4. Modifica e usa
- Tempo stimato: 3-5 minuti

**Azioni**:
- Salta (cancel tour)
- Iniziamo! (next)

---

### Step 2: Overview delle 3 Sezioni
**ID**: `cg-overview`
**Titolo**: 📋 Le 3 Sezioni del Content Generator
**Contenuto**:
- Spiegazione delle 3 tab:
  - 📄 **Pages**: Le pagine del sito
  - 🤖 **Generations**: I contenuti AI generati
  - 💬 **Prompts**: Template per generare

**Azioni**:
- ← Indietro
- Continua →

---

### Step 3: PASSO 1 - Crea una Pagina
**ID**: `cg-step1-create-page`
**Titolo**: 📄 PASSO 1: Crea una Pagina
**Contenuto**:
- Cosa è una pagina (URL del sito)
- Esempi di URL:
  - `/prodotti/scarpe-running`
  - `/blog/guida-seo`
  - `/servizi/consulenza`
- Call to action: **Clicca su "Create Page"**

**Attach to**: Bottone "Create Page" (highlight)

**Azioni**:
- ← Indietro
- Aspetta, prima spiegami i campi →

---

### Step 4: Campi del Form Pagina
**ID**: `cg-step1-form-fields`
**Titolo**: 📝 Campi del Form Pagina
**Contenuto**:
Spiegazione dettagliata dei campi:
- 🔗 **URL Path** (obbligatorio): `/prodotti/scarpe`
- 🎯 **Keyword** (obbligatorio): "scarpe da running Nike"
- 📂 **Category** (opzionale): E-commerce, Blog, Landing Page
- 🌍 **Language** (default IT): Lingua del contenuto

**Focus**: La keyword è fondamentale per SEO

**Azioni**:
- ← Indietro
- Ok, ora clicco "Create Page" →

---

### Step 5: PASSO 2 - Scegli un Prompt
**ID**: `cg-step2-prompts-intro`
**Titolo**: 💬 PASSO 2: Scegli un Prompt Template
**Contenuto**:
- Congratulazioni: hai creato la pagina 🎉
- Cosa sono i prompt (istruzioni per l'AI)
- Esempi di prompt:
  - **SEO Article**: Articolo ottimizzato
  - **Product Description**: Descrizione coinvolgente
  - **Landing Page**: Testo persuasivo
  - **Blog Post**: Articolo informativo

**Call to action**: Clicca sul tab "Prompts"

**Azioni**:
- ← Indietro
- Continua →

---

### Step 6: Come Scegliere il Prompt Giusto
**ID**: `cg-step2-prompts-choose`
**Titolo**: 🎴 Come Scegliere il Prompt Giusto
**Contenuto**:
Ogni prompt card mostra:
- **Nome**: Tipo di contenuto
- **Categoria**: Blog, SEO, E-commerce, etc.
- **Variabili**: {{keyword}}, {{word_count}}
- **Badge System/Custom**: Predefiniti o personalizzati
- **Status Active/Inactive**: Solo attivi utilizzabili

**💡 Tip**: Per la prima prova, usa un prompt "System" nella categoria "SEO" o "Blog"

**Azioni**:
- ← Indietro
- Ho capito, torno su Pages →

---

### Step 7: PASSO 3 - Genera il Contenuto
**ID**: `cg-step3-generate`
**Titolo**: 🪄 PASSO 3: Genera il Contenuto AI
**Contenuto**:
Perfetto! Ora hai:
- ✅ Una pagina creata
- ✅ Un'idea di quale prompt usare

Torna sul tab "Pages" e sulla riga della tua pagina troverai 3 icone:
- 🖊️ Edit - Modifica la pagina
- 🪄 **Generate** - <span style="bg-yellow">Clicca questa!</span>
- 🗑️ Delete - Elimina la pagina

**Call to action**: Clicca sull'icona viola "Generate Content" (bacchetta magica)

**Azioni**:
- ← Indietro
- Cosa succede dopo? →

---

### Step 8: Form di Generazione
**ID**: `cg-step3-generation-form`
**Titolo**: ⚙️ Form di Generazione
**Contenuto**:
Nel form dovrai:
1. **Selezionare un Prompt** dal dropdown
2. **Compilare le variabili** (word_count, tone, target_audience)
3. **Verificare la keyword** (precompilata)
4. **Cliccare "Generate"** (l'AI inizia!)

**💡 Tempo**: ~10-30 secondi a seconda della lunghezza

**Azioni**:
- ← Indietro
- Continua →

---

### Step 9: PASSO 4 - Monitora la Generazione
**ID**: `cg-step4-monitor`
**Titolo**: 📊 PASSO 4: Monitora la Generazione
**Contenuto**:
Dopo aver cliccato "Generate", sarai reindirizzato al tab **"Generations"**.

Stati possibili:
- ⏳ **Pending**: In attesa di elaborazione
- 🔄 **Processing**: L'AI sta generando
- ✅ **Completed**: Pronto da usare!
- ❌ **Failed**: Errore (riprova)

**💡 Tip**: Puoi ricaricare la pagina per vedere lo stato aggiornato

**Azioni**:
- ← Indietro
- Continua →

---

### Step 10: PASSO 5 - Usa il Contenuto
**ID**: `cg-step5-actions`
**Titolo**: ✨ PASSO 5: Usa il Contenuto Generato
**Contenuto**:
Quando la generation è **Completed**, puoi:

- 👁️ **View**: Visualizza il contenuto completo
- ✏️ **Edit**: Modifica e personalizza il testo
- 📋 **Copy**: Copia negli appunti (per usarlo subito)
- 🗑️ **Delete**: Elimina se non serve più

**💡 Tip**: Usa "Edit" per perfezionare il contenuto AI prima di pubblicarlo

**Azioni**:
- ← Indietro
- Continua →

---

### Step 11: Token Usage & Costi
**ID**: `cg-tokens`
**Titolo**: 💰 Token Usage & Costi
**Contenuto**:
Ogni generazione consuma **token AI**.

Nella tabella Generations vedrai:
- **Tokens Used**: Numero di token consumati
- **AI Model**: Modello usato (gpt-4, gpt-4o-mini, etc.)

**Esempi**:
- 📊 Articolo ~800 parole = **~1.000 token**
- 📊 Descrizione prodotto = **~200-300 token**
- 📊 Post social = **~100-150 token**

**💡 Tip**: Monitora l'utilizzo mensile dalla Dashboard

**Azioni**:
- ← Indietro
- Continua →

---

### Step 12: Tips & Best Practices
**ID**: `cg-tips`
**Titolo**: 💡 Tips & Best Practices
**Contenuto**:
Per ottenere i migliori risultati:

1. 🎯 **Keyword specifiche**: "scarpe running Nike Air Max" invece di "scarpe"
2. 📝 **Prompt dettagliati**: Specifica tone, target, lunghezza desiderata
3. ✏️ **Modifica sempre**: L'AI è brava ma non perfetta: rivedi e personalizza
4. 🔄 **Prova prompt diversi**: Stessa pagina + prompt diversi = risultati diversi

**Azioni**:
- ← Indietro
- Continua →

---

### Step 13: Riepilogo del Flusso
**ID**: `cg-recap`
**Titolo**: 📖 Riepilogo del Flusso
**Contenuto**:
Ecco i 5 passi che hai imparato:

1. **Tab Pages** → Clicca "Create Page"
   Compila URL, keyword, categoria

2. **Tab Prompts** → Esplora i template
   Scegli il prompt giusto per il tuo contenuto

3. **Tab Pages** → Clicca icona viola "Generate"
   Seleziona prompt e compila variabili

4. **Tab Generations** → Monitora lo stato
   Attendi che diventi "Completed"

5. **Tab Generations** → View/Edit/Copy
   Usa il contenuto generato

**💡 Tip**: Puoi ripetere questo flusso infinite volte!

**Azioni**:
- ← Indietro
- Ho capito! →

---

### Step 14: Final - Sei Pronto!
**ID**: `cg-complete`
**Titolo**: 🎉 Sei Pronto!
**Contenuto**:
**Congratulazioni!** Ora sai esattamente come usare il Content Generator.

**🚀 Inizia ora**:
1. Clicca "Create Page" nel tab Pages
2. Crea la tua prima pagina
3. Genera il primo contenuto AI

Se hai dubbi, puoi riavviare questo tour cliccando su "Tour Guidato" in alto.

**Checkbox**: ☐ Non mostrare più questo tour automaticamente

**Azioni**:
- ← Indietro
- ✓ Inizia a generare contenuti! (complete tour)

**Post-completion action**: Auto-scroll + highlight del bottone "Create Page" per 2 secondi

---

## IMPLEMENTAZIONE TECNICA

### File Modificati

1. **`resources/js/onboarding-tools.js`**
   - Funzione: `initContentGeneratorOnboardingTour()`
   - Linee: 617-1106 (~490 linee)
   - 13 step totali con Shepherd.js

2. **`resources/views/tenant/content-generator/index.blade.php`**
   - Linea 16-21: Bottone "Tour Guidato"
   - Design: Gradient purple-to-blue con lightning icon
   - Onclick: `startContentGeneratorOnboarding()`

### JavaScript Global Function

```javascript
window.startContentGeneratorOnboarding = () => initContentGeneratorOnboardingTour().start();
```

**Chiamata**: `onclick="startContentGeneratorOnboarding()"`

---

## CARATTERISTICHE SPECIALI

### 1. Auto-highlight al completamento
Al termine del tour, il bottone "Create Page" viene:
- Scrollato in vista
- Evidenziato con ring-4 blue per 2 secondi
- Poi il ring scompare

```javascript
createButton.scrollIntoView({ behavior: 'smooth', block: 'center' });
createButton.classList.add('ring-4', 'ring-blue-400', 'ring-opacity-50');
setTimeout(() => {
    createButton.classList.remove('ring-4', 'ring-blue-400', 'ring-opacity-50');
}, 2000);
```

### 2. Persistenza preferenze
Checkbox "Non mostrare più" salva tramite AJAX:

```javascript
completeToolOnboarding('content-generator');
```

**Endpoint**: `POST /dashboard/onboarding/tool/content-generator/complete`

### 3. Responsive & Accessibility
- Overlay modale con Shepherd.js
- Scroll automatico agli elementi
- Cancel icon enabled
- Keyboard navigation support

---

## DESIGN BOTTONE "TOUR GUIDATO"

**Location**: Header Content Generator (top-right)

**Stile**:
```html
<button onclick="startContentGeneratorOnboarding()"
        class="inline-flex items-center px-4 py-2
               bg-gradient-to-r from-purple-600 to-blue-600
               hover:from-purple-700 hover:to-blue-700
               text-white rounded-lg font-medium
               shadow-md transition-all duration-200 hover:shadow-lg text-sm">
    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M13 10V3L4 14h7v7l9-11h-7z"/>
    </svg>
    Tour Guidato
</button>
```

**Icona**: Lightning bolt (fulmine) per indicare velocità/azione

---

## FLUSSO UTENTE

### Scenario Tipico

1. **Utente arriva su Content Generator** per la prima volta
2. Vede il bottone **"Tour Guidato"** in alto a destra (gradient purple-blue, eye-catching)
3. Clicca il bottone
4. **Step 1-2**: Intro + overview delle 3 tab
5. **Step 3-4**: Guida alla creazione della prima pagina
6. **Step 5-6**: Spiegazione dei prompt templates
7. **Step 7-8**: Guida alla generazione del contenuto
8. **Step 9-10**: Monitoraggio e uso del contenuto generato
9. **Step 11-12**: Token usage + best practices
10. **Step 13**: Riepilogo completo
11. **Step 14**: Completamento + auto-highlight "Create Page"
12. Utente clicca "Create Page" evidenziato e inizia!

---

## VANTAGGI

### ✅ Onboarding Completo
- Copre tutto il flusso dalla A alla Z
- Spiega ogni singolo passo
- Nessun dubbio rimasto

### ✅ Orientato all'Azione
- Non solo spiegazioni teoriche
- Guida l'utente verso la prima generazione reale
- Auto-highlight del prossimo passo

### ✅ Educativo
- Spiega il "perché" non solo il "come"
- Token usage & best practices inclusi
- Tips per ottimizzare i risultati

### ✅ User-Friendly
- Può essere skippato in qualsiasi momento
- Navigation avanti/indietro
- Checkbox per non mostrare più

---

## METRICHE DI SUCCESSO

### KPI da Monitorare

1. **Tour Completion Rate**: % utenti che completano il tour
2. **First Generation Rate**: % utenti che generano contenuto dopo il tour
3. **Time to First Generation**: Tempo medio dalla registrazione alla prima generation
4. **Tour Skip Rate**: % utenti che skippano il tour

### Target Attesi

- Tour Completion: **>70%**
- First Generation Rate: **>80%** (dopo tour completato)
- Time to First Generation: **<10 minuti**
- Tour Skip Rate: **<30%**

---

## TESTING CHECKLIST

### Pre-Deploy
- [x] Tour compila senza errori
- [x] Bottone "Tour Guidato" visibile e funzionante
- [x] Tutti i 13 step caricano correttamente
- [x] Navigation avanti/indietro funziona
- [x] Checkbox "Non mostrare più" salva preferenze
- [x] Auto-highlight finale funziona
- [ ] Test su browser diversi (Chrome, Firefox, Safari, Edge)
- [ ] Test su mobile/tablet (responsive)
- [ ] Test con utente reale (usability testing)

### Post-Deploy
- [ ] Monitorare completion rate primi 7 giorni
- [ ] Raccogliere feedback utenti
- [ ] A/B test: tour vs no tour (conversion rate)
- [ ] Analytics: step di maggior drop-off

---

## NEXT STEPS (Opzionali)

### Short-term (1-2 settimane)
1. Aggiungere analytics tracking per ogni step
2. Implementare "Jump to step" per utenti avanzati
3. Aggiungere video tutorial inline

### Mid-term (1 mese)
1. Creare tour separati per utenti avanzati (features avanzate)
2. Tour contestuali per altre sezioni (Campaign Generator, etc.)
3. Gamification: badge "First Generation Completed"

### Long-term (3 mesi)
1. AI-powered onboarding personalizzato (basato su use case)
2. Interactive demo con dati fake (sandbox mode)
3. Onboarding progressivo (mostra features man mano che l'utente avanza)

---

## CONCLUSIONI

✅ **Onboarding completo implementato**
- 13 step guidati
- Copertura del flusso completo
- Design accattivante con gradient button
- Auto-highlight post-completion

✅ **Pronto per testing**
- Compilazione JS ok
- Bottone visibile
- Tour funzionale

✅ **Obiettivo raggiunto**
- Utente guidato verso prima generazione
- Zero frizione
- Esperienza educativa e actionable

**Status**: 🟢 **READY FOR USER TESTING**
