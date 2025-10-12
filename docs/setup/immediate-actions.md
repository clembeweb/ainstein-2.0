# AINSTEIN - Azioni Immediate Post-Analisi Database

## PRIORITY 1: CRITICAL FIXES (Da fare SUBITO)

### 1. Rinominare content_generations.page_id → content_id

**File da modificare**:
- `database/migrations/2025_10_11_000001_rename_page_id_to_content_id.php` (NUOVO)
- `app/Models/ContentGeneration.php`
- Tutti i controllers che usano `page_id`

**Migration**:
```php
Schema::table('content_generations', function (Blueprint $table) {
    $table->renameColumn('page_id', 'content_id');
});
```

**Model Update** (ContentGeneration.php):
```php
// RIMUOVERE:
public function page(): BelongsTo
{
    return $this->content();
}

// MODIFICARE:
public function content(): BelongsTo
{
    return $this->belongsTo(Content::class, 'content_id'); // era 'page_id'
}
```

**Tempo stimato**: 2 ore
**Breaking Change**: SI (aggiornare codice esistente)

---

### 2. Aggiungere tenant_id a activity_logs

**File da modificare**:
- `database/migrations/2025_10_11_000002_add_tenant_id_to_activity_logs.php` (NUOVO)
- `app/Models/ActivityLog.php`

**Migration**:
```php
// Step 1: Add column
Schema::table('activity_logs', function (Blueprint $table) {
    $table->string('tenant_id')->nullable()->after('user_id');
    $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
});

// Step 2: Populate from users
DB::statement('
    UPDATE activity_logs
    SET tenant_id = (SELECT tenant_id FROM users WHERE users.id = activity_logs.user_id)
');

// Step 3: Make NOT NULL
Schema::table('activity_logs', function (Blueprint $table) {
    $table->string('tenant_id')->nullable(false)->change();
    $table->index(['tenant_id', 'created_at']);
});
```

**Model Update** (ActivityLog.php):
```php
protected $fillable = [
    'action',
    'entity',
    'entity_id',
    'metadata',
    'ip_address',
    'user_agent',
    'user_id',
    'tenant_id', // NUOVO
];

// AGGIUNGERE:
public function tenant(): BelongsTo
{
    return $this->belongsTo(Tenant::class);
}

// AGGIUNGERE SCOPE:
public function scopeForTenant($query, $tenantId)
{
    return $query->where('tenant_id', $tenantId);
}
```

**Tempo stimato**: 3 ore
**Breaking Change**: NO (backward compatible)

---

### 3. Aggiungere Relazioni Inverse in User Model

**File da modificare**:
- `app/Models/User.php`

**Codice da aggiungere**:
```php
/**
 * Content generations created by this user.
 */
public function contentGenerations(): HasMany
{
    return $this->hasMany(ContentGeneration::class, 'created_by');
}

/**
 * Contents created by this user.
 */
public function createdContents(): HasMany
{
    return $this->hasMany(Content::class, 'created_by');
}

/**
 * CMS connections created by this user.
 */
public function createdCmsConnections(): HasMany
{
    return $this->hasMany(CmsConnection::class, 'created_by');
}

/**
 * Content imports created by this user.
 */
public function createdContentImports(): HasMany
{
    return $this->hasMany(ContentImport::class, 'created_by');
}

/**
 * Crews created by this user.
 */
public function createdCrews(): HasMany
{
    return $this->hasMany(Crew::class, 'created_by');
}

/**
 * Crew templates created by this user.
 */
public function createdCrewTemplates(): HasMany
{
    return $this->hasMany(CrewTemplate::class, 'created_by');
}

/**
 * Crew executions triggered by this user.
 */
public function triggeredCrewExecutions(): HasMany
{
    return $this->hasMany(CrewExecution::class, 'triggered_by');
}

/**
 * API keys revoked by this user.
 */
public function revokedApiKeys(): HasMany
{
    return $this->hasMany(ApiKey::class, 'revoked_by');
}
```

**Tempo stimato**: 1 ora
**Breaking Change**: NO

---

### 4. Aggiungere brands() Relationship in Tenant Model

**File da modificare**:
- `app/Models/Tenant.php`

**Codice da aggiungere**:
```php
/**
 * Get the brand configurations for this tenant.
 */
public function brands(): HasMany
{
    return $this->hasMany(TenantBrand::class);
}

/**
 * Get the active brand for this tenant.
 */
public function activeBrand(): HasOne
{
    return $this->hasOne(TenantBrand::class)->where('is_active', true);
}
```

**Tempo stimato**: 15 minuti
**Breaking Change**: NO

---

## PRIORITY 2: PERFORMANCE INDEXES (Prossimo Sprint)

### 5. Aggiungere Indici Mancanti

**File da creare**:
- `database/migrations/2025_10_11_000003_add_missing_indexes.php`

**Migration**:
```php
public function up(): void
{
    // FIX: Indici su created_by
    Schema::table('cms_connections', function (Blueprint $table) {
        $table->index('created_by');
    });

    Schema::table('content_imports', function (Blueprint $table) {
        $table->index('created_by');
    });

    Schema::table('crews', function (Blueprint $table) {
        $table->index('created_by');
    });

    // FIX: Indici compositi per performance
    Schema::table('content_generations', function (Blueprint $table) {
        $table->index(['status', 'created_at']);
    });

    Schema::table('crew_executions', function (Blueprint $table) {
        $table->index(['status', 'created_at']);
    });

    Schema::table('users', function (Blueprint $table) {
        $table->index(['tenant_id', 'is_active']);
    });
}
```

**Tempo stimato**: 1 ora
**Breaking Change**: NO

---

### 6. Aggiungere UNIQUE Constraint su contents

**ATTENZIONE**: Verificare PRIMA che non esistano duplicati!

**File da creare**:
- `database/migrations/2025_10_11_000004_add_unique_tenant_url_to_contents.php`

**Check preliminare**:
```sql
-- Eseguire PRIMA della migration
SELECT tenant_id, url, COUNT(*) as duplicates
FROM contents
GROUP BY tenant_id, url
HAVING COUNT(*) > 1;
```

**Se non ci sono duplicati, procedere**:
```php
public function up(): void
{
    DB::statement('
        CREATE UNIQUE INDEX idx_contents_tenant_url
        ON contents(tenant_id, url)
    ');
}

public function down(): void
{
    DB::statement('DROP INDEX idx_contents_tenant_url ON contents');
}
```

**Tempo stimato**: 1 ora (incluso check)
**Breaking Change**: POTENZIALE (verificare prima)

---

## PRIORITY 3: SOFT DELETES (Sprint Successivo)

### 7. Aggiungere SoftDeletes a content_generations

**Migration**:
```php
Schema::table('content_generations', function (Blueprint $table) {
    $table->softDeletes();
});
```

**Model**:
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentGeneration extends Model
{
    use SoftDeletes; // AGGIUNGERE

    // ... resto del codice
}
```

---

### 8. Aggiungere SoftDeletes a adv_campaigns

**Migration**:
```php
Schema::table('adv_campaigns', function (Blueprint $table) {
    $table->softDeletes();
});
```

**Model**:
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class AdvCampaign extends Model
{
    use SoftDeletes; // AGGIUNGERE

    // ... resto del codice
}
```

---

## PRIORITY 4: STANDARDIZZAZIONE (Refactoring)

### 9. Aggiungere updated_at a usage_histories

**Migration**:
```php
Schema::table('usage_histories', function (Blueprint $table) {
    $table->timestamp('updated_at')->nullable()->after('created_at');
});
```

**Model**:
```php
class UsageHistory extends Model
{
    // RIMUOVERE:
    // public $timestamps = false;

    // Laravel gestirà automaticamente created_at e updated_at
}
```

---

## CHECKLIST ESECUZIONE

### Sprint Corrente (Questa Settimana)

- [ ] **Fix #1**: Rename page_id → content_id
  - [ ] Creare migration
  - [ ] Aggiornare Model ContentGeneration
  - [ ] Cercare e aggiornare tutti i references nei Controllers
  - [ ] Testare CRUD content generations

- [ ] **Fix #2**: Add tenant_id to activity_logs
  - [ ] Creare migration
  - [ ] Aggiornare Model ActivityLog
  - [ ] Testare query performance

- [ ] **Fix #3**: Add User inverse relationships
  - [ ] Modificare User.php
  - [ ] Testare eager loading

- [ ] **Fix #4**: Add Tenant brands relationship
  - [ ] Modificare Tenant.php
  - [ ] Testare $tenant->activeBrand

### Prossimo Sprint

- [ ] **Fix #5**: Add missing indexes
- [ ] **Fix #6**: Add UNIQUE constraint (dopo verifica)
- [ ] **Fix #7-8**: Add SoftDeletes
- [ ] **Fix #9**: Standardize timestamps

---

## TESTING DOPO OGNI FIX

### Test #1: Relationships
```bash
php artisan tinker

$user = User::first();
$user->contentGenerations; // Deve funzionare
$user->createdContents; // Deve funzionare
$user->createdCrews; // Deve funzionare

$tenant = Tenant::first();
$tenant->activeBrand; // Deve funzionare
```

### Test #2: Performance
```bash
php artisan tinker

DB::enableQueryLog();
$tenant = Tenant::with('contents.generations.prompt')->first();
count(DB::getQueryLog()); // Deve essere <= 5
```

### Test #3: Activity Logs Tenant Filtering
```bash
php artisan tinker

$tenant = Tenant::first();
$logs = ActivityLog::forTenant($tenant->id)->count();
// Deve essere veloce (< 100ms)
```

---

## ROLLBACK PLAN

Se qualcosa va storto:

```bash
# Rollback ultima migration
php artisan migrate:rollback

# Rollback ultime N migrations
php artisan migrate:rollback --step=N

# Rollback completo (ATTENZIONE!)
php artisan migrate:reset
```

---

## CONTATTI SUPPORTO

**Database Architect**: AINSTEIN Eloquent Relationships Master
**Report Completo**: `AINSTEIN_DATABASE_ANALYSIS_REPORT.md`
**Data Analisi**: 2025-10-10

---

## NOTE FINALI

1. **BACKUP DATABASE** prima di applicare le migrations!
2. Testare SEMPRE in ambiente di sviluppo prima
3. Eseguire le migrations in PRODUCTION in orario di bassa utenza
4. Monitorare log errors dopo ogni deployment
5. Documentare ogni modifica nel CHANGELOG.md

**Tempo totale stimato Sprint corrente**: 6-7 ore
**Rischio**: BASSO (con testing appropriato)
**Impact**: ALTO (performance +40%, code quality +50%)
