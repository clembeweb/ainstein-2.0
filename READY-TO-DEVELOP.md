# ✅ SYSTEM READY - Next Development Task

**Last Updated**: 6 Ottobre 2025
**Status**: 📋 Layer 2.1 Complete (OpenAI Service ✅) - Ready for Layer 3.1

---

## 🎯 NEXT TASK: Campaign Generator - Database & Models

**Priority**: P1 HIGH
**Estimated Time**: 1 giorno (8h)
**Spec Document**: [`docs/01-project-overview/DEVELOPMENT-ROADMAP.md` - Lines 228-256](docs/01-project-overview/DEVELOPMENT-ROADMAP.md#L228-L256)

---

## ✅ COMPLETATO: OpenAI Service Base (Layer 2.1)

**Commits**: `9b56547` + `4ec6ef4`
**Status**: ✅ Complete + Browser Tested
**Features**:
- ✅ OpenAIService con chat, completion, JSON parsing, embeddings
- ✅ Retry logic con exponential backoff (3 tentativi)
- ✅ Token tracking per billing
- ✅ Use case configuration (campaigns, articles, SEO)
- ✅ Mock service per testing
- ✅ config/ai.php centralizzato
- ✅ Browser testing interface (6/7 test passed)
- ✅ TestOpenAIController + UI completa
- ✅ OPENAI-SERVICE-TEST-REPORT.md documentazione

**Test Results**:
- ✅ Chat completion: 27 tokens, gpt-4o-mini
- ✅ Simple completion: 53 tokens
- ✅ JSON parsing: Valid JSON structure
- ✅ Embeddings: 1536 dimensions, 13 tokens
- ✅ Use case configuration tested (campaigns, articles, SEO)

---

## 🚀 QUICK START - Campaign Generator

Quando digiti **"proseguiamo"** in una nuova chat, l'AI eseguirà automaticamente:

1. ✅ Legge `START-HERE.md` per context
2. ✅ Legge `.project-status` per task corrente
3. ✅ Identifica: **Layer 3.1 - Campaign Generator DB & Models (P1 HIGH)**
4. ✅ Apre spec: `DEVELOPMENT-ROADMAP.md` (lines 228-256)
5. ✅ Inizia implementazione

---

## 📋 IMPLEMENTATION CHECKLIST

### Layer 3.1: Database & Models (1 giorno - 8h)

#### 1. Migrations (2h) - P1
```bash
cd ainstein-laravel
php artisan make:migration create_adv_campaigns_table
php artisan make:migration create_adv_generated_assets_table
```

**Tables to Create**:

**adv_campaigns**:
- `id` (ULID primary key)
- `tenant_id` (ULID, foreign key to tenants)
- `name` (string, campaign name)
- `info` (text, briefing/description)
- `keywords` (text, comma-separated)
- `type` (enum: 'rsa', 'pmax')
- `language` (string, default 'it')
- `url` (string, destination URL)
- `tokens_used` (integer, default 0)
- `model_used` (string, nullable)
- `created_at`, `updated_at`
- **Index**: `tenant_id`, `type`, `created_at`

**adv_generated_assets**:
- `id` (ULID primary key)
- `campaign_id` (ULID, foreign key to adv_campaigns)
- `type` (enum: 'rsa', 'pmax')
- `titles` (JSON, array di titoli brevi)
- `long_titles` (JSON, array di titoli lunghi - solo PMAX)
- `descriptions` (JSON, array di descrizioni)
- `ai_quality_score` (decimal 3,2, nullable)
- `created_at`, `updated_at`
- **Index**: `campaign_id`, `type`

#### 2. Models (3h) - P1

**AdvCampaign Model**:
```php
class AdvCampaign extends Model
{
    use HasUlids;

    protected $fillable = [
        'tenant_id', 'name', 'info', 'keywords',
        'type', 'language', 'url', 'tokens_used', 'model_used'
    ];

    // Relationships
    public function tenant() // belongsTo(Tenant)
    public function assets() // hasMany(AdvGeneratedAsset)

    // Scopes
    public function scopeForTenant($query, $tenantId)

    // Accessors
    public function getKeywordsArrayAttribute() // Split keywords
}
```

**AdvGeneratedAsset Model**:
```php
class AdvGeneratedAsset extends Model
{
    use HasUlids;

    protected $fillable = [
        'campaign_id', 'type', 'titles', 'long_titles',
        'descriptions', 'ai_quality_score'
    ];

    protected $casts = [
        'titles' => 'array',
        'long_titles' => 'array',
        'descriptions' => 'array',
        'ai_quality_score' => 'decimal:2',
    ];

    // Relationships
    public function campaign() // belongsTo(AdvCampaign)

    // Accessors
    public function getTitlesCountAttribute()
    public function getDescriptionsCountAttribute()
}
```

#### 3. Factory & Seeders (2h) - P2
```bash
php artisan make:factory AdvCampaignFactory
php artisan make:factory AdvGeneratedAssetFactory
```

**Tasks**:
- [ ] Create factories con dati realistici
- [ ] Seeder per demo data (opzionale)
- [ ] Test data generation

#### 4. Testing (1h) - P1
```bash
php artisan make:test Models/AdvCampaignTest --unit
php artisan make:test Models/AdvGeneratedAssetTest --unit
```

**Test Cases**:
- [ ] Relationships work correctly
- [ ] Tenant scope filters correctly
- [ ] JSON casts work
- [ ] Keywords array accessor works
- [ ] Migrations up/down work

---

## 🎯 SUCCESS CRITERIA

✅ **Migrations** create tables correctly
✅ **Foreign keys** work (tenant_id, campaign_id)
✅ **Relationships** tested (tenant → campaigns → assets)
✅ **Scopes** filter by tenant correctly
✅ **JSON casts** for titles/descriptions work
✅ **Accessors** return correct data types
✅ **Tests** pass (migration + relationships)

---

## 📊 TECHNICAL SPECIFICATIONS

### Database Schema

```sql
-- adv_campaigns table
CREATE TABLE adv_campaigns (
    id CHAR(26) PRIMARY KEY,  -- ULID
    tenant_id CHAR(26) NOT NULL,
    name VARCHAR(255) NOT NULL,
    info TEXT,
    keywords TEXT,
    type ENUM('rsa', 'pmax') NOT NULL,
    language VARCHAR(10) DEFAULT 'it',
    url VARCHAR(500),
    tokens_used INTEGER DEFAULT 0,
    model_used VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id),
    INDEX idx_type (type),
    INDEX idx_created (created_at)
);

-- adv_generated_assets table
CREATE TABLE adv_generated_assets (
    id CHAR(26) PRIMARY KEY,  -- ULID
    campaign_id CHAR(26) NOT NULL,
    type ENUM('rsa', 'pmax') NOT NULL,
    titles JSON NOT NULL,
    long_titles JSON,
    descriptions JSON NOT NULL,
    ai_quality_score DECIMAL(3,2),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES adv_campaigns(id) ON DELETE CASCADE,
    INDEX idx_campaign (campaign_id),
    INDEX idx_type (type)
);
```

### Asset Limits (Google Ads)

**RSA (Responsive Search Ads)**:
- Titles: 3-15 (max 30 chars each)
- Descriptions: 2-4 (max 90 chars each)

**PMAX (Performance Max)**:
- Short Titles: 3-5 (max 30 chars)
- Long Titles: 1-5 (max 90 chars)
- Descriptions: 1-5 (max 90 chars)

---

## 📁 FILES TO CREATE

### New Files
1. `database/migrations/XXXX_create_adv_campaigns_table.php`
2. `database/migrations/XXXX_create_adv_generated_assets_table.php`
3. `app/Models/AdvCampaign.php`
4. `app/Models/AdvGeneratedAsset.php`
5. `database/factories/AdvCampaignFactory.php`
6. `database/factories/AdvGeneratedAssetFactory.php`
7. `tests/Unit/Models/AdvCampaignTest.php`
8. `tests/Unit/Models/AdvGeneratedAssetTest.php`

---

## 🔧 TESTING WORKFLOW

```bash
# 1. Create migrations
php artisan make:migration create_adv_campaigns_table
php artisan make:migration create_adv_generated_assets_table

# 2. Create models
php artisan make:model AdvCampaign
php artisan make:model AdvGeneratedAsset

# 3. Run migrations
php artisan migrate

# 4. Test in Tinker
php artisan tinker
>>> $tenant = Tenant::first();
>>> $campaign = AdvCampaign::create([
    'tenant_id' => $tenant->id,
    'name' => 'Test Campaign',
    'info' => 'Test info',
    'keywords' => 'keyword1, keyword2',
    'type' => 'rsa',
    'url' => 'https://example.com'
]);
>>> $campaign->assets()->create([
    'type' => 'rsa',
    'titles' => ['Title 1', 'Title 2', 'Title 3'],
    'descriptions' => ['Desc 1', 'Desc 2']
]);
>>> $campaign->assets()->get();

# 5. Run tests
php artisan test --filter=AdvCampaign
```

---

## 🚨 CRITICAL PATH

This task is **P1 HIGH** because:

1. **Foundation for Campaign Tool**: Primo tool da implementare
2. **Database Schema**: Necessario prima del service layer
3. **Multi-tenancy**: Deve rispettare isolamento tenant
4. **Asset Limits**: Validation critica per Google Ads
5. **Token Tracking**: Preparazione per billing

**Do NOT proceed to Service Layer (3.2)** until database & models are tested!

---

## 📚 REFERENCE DOCUMENTS

1. **Roadmap**: [`docs/01-project-overview/DEVELOPMENT-ROADMAP.md`](docs/01-project-overview/DEVELOPMENT-ROADMAP.md) (Lines 228-256)
2. **Google Ads RSA Specs**: [https://support.google.com/google-ads/answer/7684791](https://support.google.com/google-ads/answer/7684791)
3. **Google Ads PMAX Specs**: [https://support.google.com/google-ads/answer/10724817](https://support.google.com/google-ads/answer/10724817)

---

## 🎓 AFTER THIS TASK

Once Database & Models are complete:

**Next Task**: Layer 3.2 - Campaign Assets Generator Service
**Estimated Time**: 1 giorno (8h)
**Reference**: `DEVELOPMENT-ROADMAP.md` Lines 258-292

---

## ✅ COMPLETION VERIFICATION

Before marking this task as complete, verify:

- [ ] Migration `create_adv_campaigns_table` OK
- [ ] Migration `create_adv_generated_assets_table` OK
- [ ] AdvCampaign model created with relationships
- [ ] AdvGeneratedAsset model created with casts
- [ ] Foreign keys work (tenant_id, campaign_id)
- [ ] `tenant()` relationship works
- [ ] `assets()` relationship works
- [ ] `forTenant()` scope filters correctly
- [ ] JSON casts work for titles/descriptions
- [ ] Unit tests pass
- [ ] Can create campaign + assets in Tinker
- [ ] No breaking changes to existing tables

---

**🚀 Ready to start - Digita "proseguiamo" per iniziare Layer 3.1!**

---

_Created: 6 Ottobre 2025_
_Task Priority: P1 HIGH_
_Estimated Time: 1 giorno (8h)_
_Depends On: OpenAI Service Base (✅ Complete)_
_Blocks: Campaign Assets Generator Service (Layer 3.2)_
