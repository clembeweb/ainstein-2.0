# 🔄 ISTRUZIONI SINCRONIZZAZIONE TRA CHAT

## 📍 SEI NELLA CHAT DI SVILUPPO

### Step 1: Leggi la Richiesta

Esegui questo comando per leggere cosa ti viene chiesto:

```bash
cat SYNC-REQUEST.md
```

oppure usa Read tool su: `C:\laragon\www\ainstein-3\SYNC-REQUEST.md`

---

### Step 2: Crea la Risposta

Dopo aver letto `SYNC-REQUEST.md`, crea il file **`SYNC-RESPONSE.md`** con TUTTE le informazioni richieste:

- ✅ Stato delle 3 fasi (Fase 1, 2, 3)
- ✅ File/codice implementato (migrations, models, controllers, services, views, routes)
- ✅ Task corrente in sviluppo
- ✅ API keys e configurazioni
- ✅ Database schema aggiornato
- ✅ Testing status
- ✅ Tool implementati
- ✅ Issues & blockers
- ✅ Next immediate actions
- ✅ Aggiornamento .project-status
- ✅ Metriche progetto

**Usa il formato esatto fornito in SYNC-REQUEST.md!**

---

### Step 3: Conferma Creazione

Quando hai creato `SYNC-RESPONSE.md`, avvisa l'utente:

```
✅ File SYNC-RESPONSE.md creato!
Ora vai nell'altra chat e leggi il file per sincronizzare la documentazione.
```

---

## 📝 Template Rapido SYNC-RESPONSE.md

Se vuoi velocizzare, ecco la struttura base:

```markdown
# 🔄 SYNC RESPONSE - Stato Progetto Ainstein

**Data**: [data oggi]
**Chat**: Sviluppo
**Status**: [X% completion]

---

## 📊 EXECUTIVE SUMMARY

[Scrivi paragrafo: dove siamo, cosa funziona, cosa manca]

---

## ✅ FASE 1: PIATTAFORMA BASE

### Completato
- [lista]

### In Corso
- [lista]

### Da Fare
- [lista]

---

## 🔨 FASE 2: TOOL AI

### Current Status
**Layer**: [numero]
**Task**: [nome]
**Completion**: [%]

### Codice Creato (dettagliato!)

#### Migrations
- `YYYY_MM_DD_HHMMSS_nome_migration.php`
  - Tabella: `nome_tabella`
  - Colonne: id, tenant_id, campo1, campo2, timestamps
  - Relationships: [lista]

[... continua con TUTTI i file creati]

---

## 🔑 API & CONFIGURAZIONI

| Servizio | Status | Note |
|----------|--------|------|
| OpenAI | [✅/🔨/⏸️] | [dettagli] |
| Google Ads | [✅/🔨/⏸️] | [dettagli] |
...

---

## 🎯 TASK CORRENTE

**Nome**: [task esatto]
**Layer**: [X.Y]
**Progress**: [%]
**Next Step**: [cosa fare dopo]

---

## 📋 NEXT ACTIONS

### 🔥 Oggi
**Command**: `[comando esatto]`
**Files**: [lista]

---

## 🔄 .project-status UPDATE

```
CURRENT_LAYER=[X]
CURRENT_TASK=[X.Y]
TASK_NAME=[nome]
LAST_UPDATED=[data]

LAYER_1_1_STATUS=[completed/in_progress/pending]
LAYER_1_2_STATUS=[completed/in_progress/pending]
...

NEXT_COMMAND=[comando esatto]
```

---

## 📈 METRICHE

- Fase 1: [X%]
- Fase 2: [X%]
- Fase 3: [X%]
- **Overall**: [X%]

---

_Fine response_
```

---

## ✅ CHECKLIST PRIMA DI INVIARE

- [ ] Tutte le sezioni compilate
- [ ] File creati listati con percorsi esatti
- [ ] Schema database completo
- [ ] Task corrente ben definito
- [ ] Next command specificato
- [ ] .project-status values forniti

---

**Salva come**: `C:\laragon\www\ainstein-3\SYNC-RESPONSE.md`
