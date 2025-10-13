# AINSTEIN - Directory Structure Decision

**Data**: 2025-10-13
**Decisore**: Project Owner + Dev Team
**Stato**: ⚠ PENDING DECISION

---

## PROBLEMA

Due branch hanno strutture directory diverse:

### Opzione A: Laravel in Root (sviluppo-tool)
```
ainstein-3/
├── app/
├── config/
├── database/
├── public/
├── routes/
├── artisan
├── composer.json
└── ...
```

### Opzione B: Laravel in Subdirectory (master)
```
ainstein-3/
├── ainstein-laravel/
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── public/
│   ├── routes/
│   ├── artisan
│   └── composer.json
└── [spazio per frontend/docs]
```

---

## CONFRONTO DETTAGLIATO

| Aspetto | Opzione A (Root) | Opzione B (Subdirectory) |
|---------|------------------|--------------------------|
| **Standard Laravel** | ✅ Sì | ❌ No |
| **Deployment** | ✅ Semplice | ⚠ Richiede config |
| **CI/CD** | ✅ Nativo | ⚠ Path custom |
| **Multi-app** | ❌ No | ✅ Possibile frontend separato |
| **Documentation Root** | ❌ Mescola con Laravel | ✅ Separato |
| **Refactoring Effort** | 🔨 Alto (master) | 🔨 Alto (sviluppo-tool) |

---

## ANALISI PRO/CONTRO

### Opzione A: Laravel in Root

#### ✅ VANTAGGI
1. **Standard Laravel**: Segue la convenzione ufficiale
2. **Tool Integration**: PHPStorm, VS Code, Composer tool funzionano nativamente
3. **Deployment Semplice**:
   - `cd /var/www/ainstein && php artisan serve`
   - DocumentRoot = `/public`
4. **CI/CD Ready**: GitHub Actions, GitLab CI nativi
5. **Community Support**: Tutta la documentazione assume questa struttura
6. **Zero Config**: `composer install`, `npm install` funzionano subito

#### ❌ SVANTAGGI
1. **Root Clutter**: File Laravel misti con docs/scripts
2. **No Frontend Separato**: Difficile avere React/Vue app separata
3. **Migration Required**: Master e production vanno refactorati
4. **Risk**: Alto durante migrazione

---

### Opzione B: Laravel in Subdirectory

#### ✅ VANTAGGI
1. **Clean Root**: Directory root pulita per docs/scripts
2. **Multi-App Ready**: Possibilità frontend separato:
   ```
   ainstein-3/
   ├── ainstein-laravel/     # Backend API
   ├── ainstein-frontend/    # React/Vue SPA
   └── ainstein-docs/        # Documentation
   ```
3. **Organized**: Separazione logica componenti
4. **Current Master**: Nessuna modifica a master/production

#### ❌ SVANTAGGI
1. **Non-Standard**: Nessun progetto Laravel usa questa struttura
2. **Tool Configuration**: Ogni tool richiede configurazione custom
3. **Deployment Complicato**:
   ```nginx
   root /var/www/ainstein/ainstein-laravel/public;
   ```
4. **CI/CD Custom**: Workflow CI/CD richiedono path custom
5. **Documentation Confusa**: Tutorial/guide non applicabili direttamente
6. **Maintenance**: Più complesso per nuovi developer

---

## CASI D'USO

### Se pianificate frontend separato → Opzione B
```
ainstein-3/
├── backend-api/       # Laravel API pura
├── admin-spa/         # Vue.js Admin Dashboard
├── client-spa/        # React Client Frontend
└── docs/
```

### Se frontend è Blade/Livewire → Opzione A
```
ainstein-3/
├── app/
├── resources/views/   # Blade templates
├── public/
└── ... (standard Laravel)
```

---

## SITUAZIONE ATTUALE AINSTEIN

### Frontend Corrente
- ✅ Blade templates (Filament Admin)
- ✅ Livewire components
- ✅ Alpine.js per interattività
- ❌ NO React/Vue SPA separata

### Deployment Corrente
- Server Hostinger
- Laravel in subdirectory attuale
- DocumentRoot configurato custom

### Team Experience
- Team familiare con Laravel standard
- Nessuna necessità immediata di frontend separato

---

## RACCOMANDAZIONE

### 🎯 OPZIONE A: Laravel in Root

**Motivazioni**:
1. ✅ **Standard**: Segue best practices Laravel
2. ✅ **Sviluppo-tool Ready**: 41 commit già in questa struttura
3. ✅ **Team Productivity**: Meno configurazione = più sviluppo
4. ✅ **Future-Proof**: Facile migrare a Opzione B se serve
5. ✅ **CI/CD Native**: Zero configuration

**Piano Migrazione**:
1. Usare `sviluppo-tool` come base (già in root)
2. Migrare feature da master via cherry-pick
3. Aggiornare master gradualmente
4. Testare completamente
5. Deploy production

---

## DECISION CHECKLIST

Prima di decidere, rispondere a queste domande:

### 1. Frontend
- [ ] Avete piani per SPA React/Vue separata nei prossimi 6 mesi?
- [ ] Frontend resterà Blade/Livewire?

**Se Blade/Livewire**: ➡ **Opzione A**

### 2. Deployment
- [ ] Potete cambiare configurazione server facilmente?
- [ ] Avete accesso root server?

**Se Sì**: ➡ **Entrambe opzioni possibili**

### 3. Team
- [ ] Team ha esperienza con setup Laravel custom?
- [ ] Preferite standard vs custom?

**Se Standard**: ➡ **Opzione A**

### 4. Timeline
- [ ] Avete tempo per refactoring importante?
- [ ] Serve deploy urgente?

**Se Deploy urgente**: ➡ **Mantenere struttura corrente (Opzione B)**

### 5. Manutenzione
- [ ] Chi manterrà il progetto?
- [ ] Onboarding frequente di nuovi dev?

**Se Onboarding frequente**: ➡ **Opzione A (più facile)**

---

## IMPLEMENTAZIONE SCELTA

### Se scegliete Opzione A (Laravel in Root)

```bash
# 1. Usare sviluppo-tool come base
git checkout develop  # (già creato da sviluppo-tool)

# 2. Cherry-pick feature da master se necessario
git log master --oneline
git cherry-pick <commit-hash>

# 3. Test completo
php artisan test

# 4. Merge a master
git checkout master
git merge develop --no-ff -m "feat: Restructure - Laravel in root

BREAKING CHANGE: Project structure changed
- Moved Laravel to root directory
- Updated deployment configuration
- All paths now relative to root"

# 5. Tag major version
git tag -a v2.0.0 -m "Major restructure - Laravel in root"

# 6. Update production
git checkout production
git merge master
git push origin production --tags
```

### Se scegliete Opzione B (Mantenere Subdirectory)

```bash
# 1. Master rimane base
git checkout master

# 2. Cherry-pick feature da sviluppo-tool
git log sviluppo-tool --oneline --since="2025-10-06"

# Per ogni feature:
git checkout -b feature/crewai-integration
# Copiare file manualmente da sviluppo-tool
# Adattare path se necessario

# 3. Test e merge
git checkout master
git merge feature/crewai-integration

# 4. Update production
git checkout production
git merge master
```

---

## ROLLBACK PLAN

Se la scelta si rivela sbagliata:

```bash
# Tornare a snapshot
git checkout master
git reset --hard snapshot-master-2025-10-13

# Ricreare branch da backup
git checkout -b master-new snapshot-master-2025-10-13
git branch -D master
git branch -m master-new master

# Force push (SOLO se necessario)
git push origin master --force
```

---

## DOCUMENTAZIONE DA AGGIORNARE

Dopo la decisione, aggiornare:

1. ✅ README.md
2. ✅ DEPLOYMENT.md
3. ✅ .env.example
4. ✅ docker-compose.yml (se usato)
5. ✅ CI/CD workflows
6. ✅ Server configuration
7. ✅ Team onboarding docs

---

## DECISIONE FINALE

**Data Decisione**: _______________

**Opzione Scelta**: [ ] A - Root  [ ] B - Subdirectory

**Firmato da**: _______________

**Motivo Principale**:
_____________________________________________________
_____________________________________________________

**Timeline Implementazione**: _______________

**Responsabile**: _______________

---

## FOLLOW-UP

Dopo implementazione (1 settimana):

- [ ] Deployment funzionante
- [ ] CI/CD configurato
- [ ] Team onboarded
- [ ] Documentation aggiornata
- [ ] Performance check
- [ ] Zero breaking issues

Se problemi entro 1 settimana → Considerare rollback

---

**END OF DECISION DOCUMENT**

*Per supporto: Vedere GIT_ANALYSIS_REPORT.md*
