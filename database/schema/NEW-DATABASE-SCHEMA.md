# New Unified Database Schema - Content Generation System

## Overview
This schema unifies the content management system with support for multiple import sources (manual, CSV, CMS connectors).

---

## New Tables

### 1. `contents` (replaces `pages`)
Unified content repository with support for multiple sources and types.

```sql
- id (ULID, primary key)
- tenant_id (FK to tenants)
- url (string, indexed) - Content URL
- content_type (enum) - article, product, service, landing_page, category
- source (enum) - manual, csv, wordpress, prestashop
- source_id (string, nullable) - Original ID from CMS
- title (string, nullable)
- keyword (string, nullable)
- language (string, default 'it')
- meta_data (json) - Additional metadata from CMS
- status (enum) - active, archived, deleted
- imported_at (timestamp, nullable)
- last_synced_at (timestamp, nullable)
- created_by (FK to users)
- created_at (timestamp)
- updated_at (timestamp)
- deleted_at (timestamp, nullable) - Soft delete

Indexes:
- tenant_id, status
- tenant_id, url
- tenant_id, source
- tenant_id, content_type
```

### 2. `cms_connections`
Stores CMS connection configurations for each tenant.

```sql
- id (ULID, primary key)
- tenant_id (FK to tenants)
- cms_type (enum) - wordpress, prestashop
- connection_name (string) - User-friendly name
- site_url (string) - CMS site URL
- api_key (string, encrypted) - API key for authentication
- api_secret (string, encrypted, nullable) - Additional secret if needed
- status (enum) - pending, active, disconnected, error
- last_sync_at (timestamp, nullable)
- last_error (text, nullable)
- sync_config (json) - Sync settings (auto-sync, frequency, etc)
- created_by (FK to users)
- created_at (timestamp)
- updated_at (timestamp)

Indexes:
- tenant_id, status
- tenant_id, cms_type
```

### 3. `content_imports`
Tracks import operations (CSV and CMS).

```sql
- id (ULID, primary key)
- tenant_id (FK to tenants)
- import_type (enum) - csv, cms_sync
- cms_connection_id (FK to cms_connections, nullable)
- file_path (string, nullable) - For CSV imports
- status (enum) - pending, processing, completed, failed
- total_rows (integer)
- processed_rows (integer)
- successful_rows (integer)
- failed_rows (integer)
- errors (json, nullable) - Error details
- started_at (timestamp, nullable)
- completed_at (timestamp, nullable)
- created_by (FK to users)
- created_at (timestamp)
- updated_at (timestamp)

Indexes:
- tenant_id, status
- tenant_id, import_type
```

### 4. `tools`
Master list of available tools in the platform.

```sql
- id (ULID, primary key)
- code (string, unique) - content-generation, future-seo-tool, etc
- name (string) - Display name
- category (enum) - copy, seo, adv
- description (text)
- icon (string) - Icon class
- is_active (boolean)
- settings_schema (json) - JSON schema for tool-specific settings
- created_at (timestamp)
- updated_at (timestamp)

Indexes:
- code (unique)
- category, is_active
```

### 5. `tool_settings`
Tool-specific settings per tenant (e.g., different API keys per tool).

```sql
- id (ULID, primary key)
- tenant_id (FK to tenants)
- tool_id (FK to tools)
- settings (json) - Tool-specific configuration
- is_active (boolean)
- created_at (timestamp)
- updated_at (timestamp)

Indexes:
- tenant_id, tool_id (unique together)
```

---

## Modified Tables

### `prompts` - Add tool association
```sql
ALTER TABLE prompts ADD:
- tool_id (FK to tools) - Which tool this prompt belongs to
- is_global (boolean, default false) - Available to all tools if true

Indexes:
- tool_id, is_active
- tenant_id, tool_id
```

### `content_generations` - Update references
```sql
ALTER TABLE content_generations:
- RENAME page_id TO content_id (FK to contents instead of pages)
- Keep all other fields

Indexes:
- Update page_id index to content_id
```

---

## Data Migration Strategy

### Phase 1: Create New Tables
1. Run migrations for: contents, cms_connections, content_imports, tools, tool_settings
2. Add columns to prompts

### Phase 2: Seed Tools
Insert base tools:
- content-generation (category: copy)
- [Future tools will be added here]

### Phase 3: Migrate Existing Data
```sql
-- Migrate pages to contents
INSERT INTO contents (
    id, tenant_id, url, content_type, source, title, keyword,
    language, status, created_by, created_at, updated_at
)
SELECT
    id, tenant_id, url_path, 'article', 'manual', NULL, keyword,
    language, 'active', created_by, created_at, updated_at
FROM pages;

-- Update content_generations references
-- (already using pages.id which matches contents.id)

-- Associate existing prompts with content-generation tool
UPDATE prompts
SET tool_id = (SELECT id FROM tools WHERE code = 'content-generation')
WHERE tool_id IS NULL;
```

### Phase 4: Cleanup (Optional - Keep for now)
- Keep `pages` table for rollback capability
- Can drop later after verification

---

## CSV Import Format

Standard CSV structure for content import:

```csv
url,content_type,title,keyword,language,meta_title,meta_description
/blog/article-1,article,"My Article","keyword",it,"Title","Description"
/products/product-1,product,"Product Name","product keyword",it,"",""
/services/service-1,service,"Service Name","",it,"","Custom meta"
```

Required columns:
- url (required)
- content_type (required, values: article|product|service|landing_page|category)

Optional columns:
- title
- keyword
- language (default: it)
- meta_title
- meta_description
- Any additional columns will be stored in meta_data JSON

---

## CMS API Authentication

### WordPress Plugin API
```
Endpoint: https://ainstein.it/api/wordpress/connect
Method: POST
Headers:
  - X-CMS-Type: wordpress
  - X-Site-URL: https://user-site.com
  - X-API-Key: generated-key-from-plugin

Response:
{
  "success": true,
  "connection_id": "ulid",
  "tenant_id": "ulid"
}
```

### PrestaShop Module API
```
Endpoint: https://ainstein.it/api/prestashop/connect
Method: POST
Headers:
  - X-CMS-Type: prestashop
  - X-Shop-URL: https://user-shop.com
  - X-API-Key: generated-key-from-module

Response:
{
  "success": true,
  "connection_id": "ulid",
  "tenant_id": "ulid"
}
```

---

## Rollback Instructions

To rollback to previous version:
```bash
cd ainstein-3/ainstein-laravel
git checkout v1.0-before-refactoring
php artisan migrate:rollback
composer install
npm install && npm run build
```

---

## Next Steps Implementation Order

1. ✅ Design schema (this document)
2. Create migration files
3. Create/update models
4. Migrate data
5. Update controllers
6. Update routes
7. Update views
8. Create CMS plugins
9. Test everything
