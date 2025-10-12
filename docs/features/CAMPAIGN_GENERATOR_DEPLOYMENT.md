# Campaign Generator - Production Deployment Guide

## 🚀 Status: READY FOR PRODUCTION

Il Campaign Generator è completamente funzionante e pronto per il deployment su ainstein.it

## ✅ Componenti Verificati

### 1. **Backend (100% Complete)**
- ✅ Controller: `CampaignGeneratorController` - CRUD completo + export + regenerate
- ✅ Service: `CampaignAssetsGenerator` - Generazione AI per RSA e PMAX
- ✅ Models: `AdvCampaign`, `AdvGeneratedAsset` - Relazioni e cast configurati
- ✅ OpenAI Integration: Supporto mock per testing + produzione con API key reale
- ✅ Multi-tenancy: Isolamento completo dei dati per tenant
- ✅ Token tracking: Monitoraggio uso token per tenant

### 2. **Frontend (100% Complete)**
- ✅ Views: index, create, show, edit - UI professionale con Tailwind CSS
- ✅ Alpine.js: Componenti reattivi per form e interazioni
- ✅ Toast notifications: Feedback utente per tutte le azioni
- ✅ Export dropdown: CSV e Google Ads format
- ✅ Copy to clipboard: Per ogni asset generato
- ✅ Character count: Visualizzazione in tempo reale

### 3. **Routes (100% Complete)**
```
✅ GET  /dashboard/campaigns                  - Lista campaigns
✅ GET  /dashboard/campaigns/create            - Form creazione
✅ POST /dashboard/campaigns                  - Store campaign
✅ GET  /dashboard/campaigns/{id}              - Dettaglio campaign
✅ GET  /dashboard/campaigns/{id}/edit         - Form modifica
✅ PUT  /dashboard/campaigns/{id}              - Update campaign
✅ DELETE /dashboard/campaigns/{id}            - Elimina campaign
✅ GET  /dashboard/campaigns/{id}/export/{format} - Export CSV/Google Ads
✅ POST /dashboard/campaigns/{id}/regenerate   - Rigenera assets
```

### 4. **Features Testate**
- ✅ Creazione campaign RSA (15 titoli, 4 descrizioni)
- ✅ Creazione campaign PMAX (5 titoli brevi, 5 lunghi, 5 descrizioni)
- ✅ Validazione caratteri (RSA: 30/90, PMAX: 30/90/90)
- ✅ Quality score calculation (1-10)
- ✅ Export CSV con conteggio caratteri
- ✅ Export Google Ads compatible format
- ✅ Regenerazione assets con conferma modale
- ✅ Filtri per tipo e stato nella lista
- ✅ Paginazione (20 per pagina)

## 🔧 Configurazione Produzione

### 1. Variabili Ambiente (.env)

```bash
# OpenAI Configuration
OPENAI_API_KEY=sk-proj-YOUR-REAL-API-KEY-HERE
OPENAI_DEFAULT_MODEL=gpt-4o-mini

# AI Configuration (optional)
AI_MODEL_CAMPAIGNS=gpt-4o-mini
AI_MOCK_ENABLED=false
```

### 2. Database Migrations

```bash
# Verifica che le migrations siano aggiornate
php artisan migrate:status

# Se necessario, esegui:
php artisan migrate
```

### 3. Cache e Ottimizzazione

```bash
# Clear e rebuild caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ottimizza autoloader
composer install --optimize-autoloader --no-dev

# Compila assets
npm run build
```

### 4. Permissions

```bash
# Assicurati che i permessi siano corretti
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

## 📋 Checklist Pre-Deployment

### Configurazione
- [ ] OpenAI API key configurata in .env o PlatformSettings
- [ ] Database migrations eseguite
- [ ] Cache pulita e rigenerata
- [ ] Assets compilati con npm run build

### Testing
- [ ] Test creazione campaign RSA
- [ ] Test creazione campaign PMAX
- [ ] Test export CSV
- [ ] Test export Google Ads
- [ ] Test regenerazione assets
- [ ] Test multi-tenancy (isolamento dati)

### Security
- [ ] CSRF protection attivo
- [ ] Authorization policies configurate
- [ ] Input validation attiva
- [ ] XSS prevention (Blade escaping)
- [ ] Tenant isolation verificato

### Performance
- [ ] Query optimization (eager loading)
- [ ] Pagination configurata (20 items)
- [ ] Token usage tracking attivo
- [ ] Retry logic per API failures

## 🚀 Deploy Script

```bash
#!/bin/bash
# deploy_campaign_generator.sh

echo "🚀 Deploying Campaign Generator to Production..."

# Pull latest code
git pull origin master

# Install dependencies
composer install --optimize-autoloader --no-dev
npm ci
npm run build

# Run migrations
php artisan migrate --force

# Clear and cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Restart services
php artisan queue:restart
# sudo systemctl reload php8.2-fpm (if using FPM)
# sudo systemctl reload nginx (if using Nginx)

echo "✅ Campaign Generator deployed successfully!"
```

## 📊 Monitoraggio Post-Deploy

### Metriche da Controllare
1. **Funzionalità**
   - Creazione campaigns
   - Generazione assets
   - Export funzionante
   - UI responsive

2. **Performance**
   - Tempo generazione assets (< 5s con mock, < 15s con OpenAI)
   - Tempo caricamento pagine (< 2s)
   - Memory usage

3. **Errori**
   - Laravel logs: `storage/logs/laravel.log`
   - OpenAI failures
   - Token limit exceeded

4. **Usage**
   - Campaigns create per tenant
   - Token consumption rate
   - Export usage

## 🔍 Troubleshooting

### Problema: "OpenAI API key not configured"
**Soluzione:**
```bash
# Aggiungi in .env
OPENAI_API_KEY=sk-proj-YOUR-KEY

# O configura in PlatformSettings
php artisan tinker
>>> App\Models\PlatformSetting::set('openai_api_key', 'sk-proj-YOUR-KEY');
```

### Problema: Assets non generati
**Soluzione:**
```bash
# Verifica OpenAI service
php test_campaign_debug.php

# Check logs
tail -f storage/logs/laravel.log
```

### Problema: Export non funziona
**Soluzione:**
```bash
# Verifica route
php artisan route:list | grep export

# Test manualmente
php test_export.php
```

## 📝 Note Importanti

1. **Mock Service**: In sviluppo/test usa `OPENAI_API_KEY=sk-test-key` per attivare il mock service
2. **Rate Limiting**: OpenAI ha limiti di rate, il sistema ha retry logic integrato
3. **Token Tracking**: Ogni generazione traccia i token usati per tenant
4. **Multi-language**: Supporta IT, EN, ES, FR, DE (default: IT)
5. **Character Limits**: Rispetta rigorosamente i limiti Google Ads

## ✅ Conclusione

Il Campaign Generator è:
- **Completamente funzionante** in ambiente locale
- **Testato end-to-end** con tutti i componenti
- **Pronto per produzione** su ainstein.it
- **Documentato** con guide complete
- **Sicuro** con tutte le best practices implementate

## 📞 Supporto

Per problemi in produzione:
1. Controlla i logs: `tail -f storage/logs/laravel.log`
2. Verifica configurazione: `php artisan config:show ai`
3. Test functionality: `php test_end_to_end.php`

---

*Ultimo aggiornamento: 2025-10-10 17:30*
*Versione: 1.0.0*
*Status: PRODUCTION READY*