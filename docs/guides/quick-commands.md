# AINSTEIN - Quick Commands Reference

Quick reference per sviluppatori per operazioni comuni sul database.

---

## DATABASE INSPECTION

### Verifica Migrations
```bash
# Vedi stato tutte migrations
php artisan migrate:status

# Conta migrations eseguite
php artisan migrate:status | grep "Ran" | wc -l

# Vedi batch corrente
php artisan migrate:status | tail -10
```

### Verifica Tabelle
```bash
# SQLite - Lista tutte le tabelle
php artisan tinker --execute="DB::select('SELECT name FROM sqlite_master WHERE type=\"table\"')"

# Conta tabelle
php artisan tinker --execute="count(DB::select('SELECT name FROM sqlite_master WHERE type=\"table\"'))"

# Schema di una tabella specifica
php artisan tinker --execute="Schema::getColumnListing('contents')"
```

---

## MODEL INSPECTION

### Verifica Relationships
```bash
# Tenant relationships
php artisan tinker --execute="
\$tenant = App\Models\Tenant::first();
echo 'Users: ' . \$tenant->users()->count() . PHP_EOL;
echo 'Contents: ' . \$tenant->contents()->count() . PHP_EOL;
echo 'Crews: ' . \$tenant->crews()->count() . PHP_EOL;
"

# User relationships (verificare fix)
php artisan tinker --execute="
\$user = App\Models\User::first();
echo 'Has tenant: ' . (\$user->tenant ? 'YES' : 'NO') . PHP_EOL;
echo 'Activities: ' . \$user->activities()->count() . PHP_EOL;
"

# Content with generations
php artisan tinker --execute="
\$content = App\Models\Content::with('generations')->first();
echo 'Generations: ' . \$content->generations->count() . PHP_EOL;
"
```

### Test N+1 Query Problems
```bash
# BEFORE optimization
php artisan tinker --execute="
DB::enableQueryLog();
\$tenant = App\Models\Tenant::find('01JC37CZZQV5YCXMXPXP0YDQHZ'); // Use real ID
\$tenant->contents->each(function(\$c) {
    echo \$c->creator->name . PHP_EOL;
});
echo 'Queries: ' . count(DB::getQueryLog()) . PHP_EOL;
"

# AFTER optimization (con eager loading)
php artisan tinker --execute="
DB::enableQueryLog();
\$tenant = App\Models\Tenant::with('contents.creator')->find('01JC37CZZQV5YCXMXPXP0YDQHZ');
\$tenant->contents->each(function(\$c) {
    echo \$c->creator->name . PHP_EOL;
});
echo 'Queries: ' . count(DB::getQueryLog()) . PHP_EOL;
"
```

---

## DATA INSPECTION

### Tenants
```bash
# Lista tutti i tenant
php artisan tinker --execute="
App\Models\Tenant::all(['id', 'name', 'subdomain', 'plan_type'])->toArray()
"

# Tenant con statistiche
php artisan tinker --execute="
\$t = App\Models\Tenant::first();
echo 'Tenant: ' . \$t->name . PHP_EOL;
echo 'Users: ' . \$t->users()->count() . PHP_EOL;
echo 'Contents: ' . \$t->contents()->count() . PHP_EOL;
echo 'Generations: ' . \$t->contentGenerations()->count() . PHP_EOL;
echo 'Crews: ' . \$t->crews()->count() . PHP_EOL;
echo 'Tokens used: ' . \$t->tokens_used_current . '/' . \$t->tokens_monthly_limit . PHP_EOL;
"
```

### Contents
```bash
# Contents per tenant
php artisan tinker --execute="
App\Models\Content::where('tenant_id', '01JC37CZZQV5YCXMXPXP0YDQHZ')
    ->get(['id', 'title', 'url', 'status'])
    ->toArray()
"

# Contents by source
php artisan tinker --execute="
App\Models\Content::select('source', DB::raw('count(*) as total'))
    ->groupBy('source')
    ->get()
    ->toArray()
"

# Contents by type
php artisan tinker --execute="
App\Models\Content::select('content_type', DB::raw('count(*) as total'))
    ->groupBy('content_type')
    ->get()
    ->toArray()
"
```

### Content Generations
```bash
# Generazioni per status
php artisan tinker --execute="
App\Models\ContentGeneration::select('status', DB::raw('count(*) as total'))
    ->groupBy('status')
    ->get()
    ->toArray()
"

# Ultime generazioni
php artisan tinker --execute="
App\Models\ContentGeneration::with('content:id,title', 'prompt:id,name')
    ->latest()
    ->limit(5)
    ->get(['id', 'page_id', 'prompt_id', 'status', 'tokens_used', 'created_at'])
    ->toArray()
"

# Token usage summary
php artisan tinker --execute="
echo 'Total tokens: ' . App\Models\ContentGeneration::sum('tokens_used') . PHP_EOL;
echo 'Avg tokens: ' . round(App\Models\ContentGeneration::avg('tokens_used')) . PHP_EOL;
echo 'Max tokens: ' . App\Models\ContentGeneration::max('tokens_used') . PHP_EOL;
"
```

### Crews
```bash
# Crews attivi
php artisan tinker --execute="
App\Models\Crew::active()
    ->with('tenant:id,name')
    ->get(['id', 'tenant_id', 'name', 'status', 'process_type'])
    ->toArray()
"

# Crew executions per status
php artisan tinker --execute="
App\Models\CrewExecution::select('status', DB::raw('count(*) as total'))
    ->groupBy('status')
    ->get()
    ->toArray()
"

# Crew execution details
php artisan tinker --execute="
\$exec = App\Models\CrewExecution::with(['crew:id,name', 'triggeredBy:id,name'])
    ->latest()
    ->first();
if (\$exec) {
    echo 'Crew: ' . \$exec->crew->name . PHP_EOL;
    echo 'Triggered by: ' . \$exec->triggeredBy->name . PHP_EOL;
    echo 'Status: ' . \$exec->status . PHP_EOL;
    echo 'Tokens: ' . \$exec->total_tokens_used . PHP_EOL;
    echo 'Duration: ' . \$exec->duration . 's' . PHP_EOL;
}
"
```

---

## PERFORMANCE TESTING

### Query Count Benchmarks
```bash
# Dashboard query count
php artisan tinker --execute="
DB::enableQueryLog();
\$tenant = App\Models\Tenant::with([
    'contents' => fn(\$q) => \$q->latest()->limit(10),
    'contents.creator:id,name',
    'contents.generations' => fn(\$q) => \$q->latest()->limit(3),
    'contents.generations.prompt:id,name'
])->first();
echo 'Queries executed: ' . count(DB::getQueryLog()) . PHP_EOL;
echo 'Expected: <= 5 queries' . PHP_EOL;
"

# Activity logs performance (BEFORE fix)
php artisan tinker --execute="
\$start = microtime(true);
\$logs = DB::table('activity_logs')
    ->join('users', 'activity_logs.user_id', '=', 'users.id')
    ->where('users.tenant_id', '01JC37CZZQV5YCXMXPXP0YDQHZ')
    ->count();
\$time = (microtime(true) - \$start) * 1000;
echo 'Time: ' . round(\$time, 2) . 'ms (with JOIN)' . PHP_EOL;
"

# Activity logs performance (AFTER fix - if tenant_id exists)
php artisan tinker --execute="
if (Schema::hasColumn('activity_logs', 'tenant_id')) {
    \$start = microtime(true);
    \$logs = DB::table('activity_logs')
        ->where('tenant_id', '01JC37CZZQV5YCXMXPXP0YDQHZ')
        ->count();
    \$time = (microtime(true) - \$start) * 1000;
    echo 'Time: ' . round(\$time, 2) . 'ms (direct index)' . PHP_EOL;
} else {
    echo 'tenant_id column not yet added to activity_logs' . PHP_EOL;
}
"
```

### Memory Usage
```bash
# Load heavy relationships
php artisan tinker --execute="
\$memory_start = memory_get_usage(true);
\$tenant = App\Models\Tenant::with([
    'users',
    'contents.generations',
    'crews.agents',
    'crews.tasks'
])->first();
\$memory_end = memory_get_usage(true);
echo 'Memory used: ' . round((\$memory_end - \$memory_start) / 1024 / 1024, 2) . ' MB' . PHP_EOL;
"
```

---

## MIGRATIONS

### Esegui Migrations
```bash
# Esegui migrations pendenti
php artisan migrate

# Esegui con output dettagliato
php artisan migrate --verbose

# Dry run (simula senza eseguire)
php artisan migrate --pretend

# Force in production (ATTENZIONE!)
php artisan migrate --force
```

### Rollback
```bash
# Rollback ultimo batch
php artisan migrate:rollback

# Rollback ultimi N batch
php artisan migrate:rollback --step=3

# Rollback specifico batch
php artisan migrate:rollback --batch=5

# Rollback con pretend
php artisan migrate:rollback --pretend
```

### Refresh (ATTENZIONE: Cancella tutti i dati!)
```bash
# Reset tutte le migrations
php artisan migrate:reset

# Refresh (reset + migrate)
php artisan migrate:refresh

# Fresh (drop all tables + migrate)
php artisan migrate:fresh

# Fresh with seeders
php artisan migrate:fresh --seed
```

---

## SEEDING

### Esegui Seeders
```bash
# Tutti i seeders
php artisan db:seed

# Seeder specifico
php artisan db:seed --class=TenantSeeder

# Seed con migration fresh
php artisan migrate:fresh --seed
```

---

## DIAGNOSTICS

### Verifica Anomalie Identificate

#### ANOMALY #1: page_id naming
```bash
# Verifica se page_id esiste ancora
php artisan tinker --execute="
if (Schema::hasColumn('content_generations', 'page_id')) {
    echo '⚠️  page_id still exists (should be content_id)' . PHP_EOL;
} else {
    echo '✓ page_id renamed to content_id' . PHP_EOL;
}
"
```

#### ANOMALY #2: activity_logs tenant_id
```bash
# Verifica se tenant_id è stato aggiunto
php artisan tinker --execute="
if (Schema::hasColumn('activity_logs', 'tenant_id')) {
    echo '✓ tenant_id column exists in activity_logs' . PHP_EOL;
    // Verifica indice
    \$indexes = DB::select(\"PRAGMA index_list('activity_logs')\");
    \$hasTenantIndex = false;
    foreach (\$indexes as \$index) {
        \$cols = DB::select(\"PRAGMA index_info('{$index->name}')\");
        foreach (\$cols as \$col) {
            if (\$col->name === 'tenant_id') {
                \$hasTenantIndex = true;
                break 2;
            }
        }
    }
    echo \$hasTenantIndex ? '✓ Index on tenant_id exists' . PHP_EOL : '⚠️  Index on tenant_id missing' . PHP_EOL;
} else {
    echo '⚠️  tenant_id column missing in activity_logs' . PHP_EOL;
}
"
```

#### ANOMALY #3: User relationships
```bash
# Verifica relazioni User
php artisan tinker --execute="
\$user = new App\Models\User;
\$methods = [
    'contentGenerations',
    'createdContents',
    'createdCmsConnections',
    'createdContentImports',
    'createdCrews',
    'triggeredCrewExecutions'
];
foreach (\$methods as \$method) {
    \$exists = method_exists(\$user, \$method);
    echo (\$exists ? '✓' : '⚠️ ') . ' ' . \$method . '()' . PHP_EOL;
}
"
```

#### ANOMALY #4: Tenant brands relationship
```bash
# Verifica relazioni Tenant
php artisan tinker --execute="
\$tenant = new App\Models\Tenant;
\$methods = ['brands', 'activeBrand'];
foreach (\$methods as \$method) {
    \$exists = method_exists(\$tenant, \$method);
    echo (\$exists ? '✓' : '⚠️ ') . ' ' . \$method . '()' . PHP_EOL;
}
"
```

#### ANOMALY #5: UNIQUE constraint on contents
```bash
# Verifica constraint UNIQUE su contents(tenant_id, url)
php artisan tinker --execute="
\$indexes = DB::select(\"PRAGMA index_list('contents')\");
\$hasUniqueConstraint = false;
foreach (\$indexes as \$index) {
    if (\$index->unique) {
        \$cols = DB::select(\"PRAGMA index_info('{$index->name}')\");
        \$colNames = array_column(\$cols, 'name');
        if (in_array('tenant_id', \$colNames) && in_array('url', \$colNames)) {
            \$hasUniqueConstraint = true;
            break;
        }
    }
}
echo \$hasUniqueConstraint ? '✓ UNIQUE constraint exists on (tenant_id, url)' . PHP_EOL : '⚠️  UNIQUE constraint missing on (tenant_id, url)' . PHP_EOL;
"
```

---

## INDEX VERIFICATION

### Verifica Indici Esistenti
```bash
# Lista indici per una tabella
php artisan tinker --execute="
\$table = 'contents';
\$indexes = DB::select(\"PRAGMA index_list('\$table')\");
echo 'Indexes on ' . \$table . ':' . PHP_EOL;
foreach (\$indexes as \$index) {
    echo '- ' . \$index->name . ' (unique: ' . (\$index->unique ? 'YES' : 'NO') . ')' . PHP_EOL;
    \$cols = DB::select(\"PRAGMA index_info('{$index->name}')\");
    foreach (\$cols as \$col) {
        echo '  - ' . \$col->name . PHP_EOL;
    }
}
"
```

### Verifica Foreign Keys
```bash
# Lista foreign keys per una tabella
php artisan tinker --execute="
\$table = 'content_generations';
\$fks = DB::select(\"PRAGMA foreign_key_list('\$table')\");
echo 'Foreign Keys on ' . \$table . ':' . PHP_EOL;
foreach (\$fks as \$fk) {
    echo '- ' . \$fk->from . ' → ' . \$fk->table . '.' . \$fk->to . PHP_EOL;
}
"
```

---

## BACKUP & RESTORE

### Backup Database (SQLite)
```bash
# Simple copy
cp database/database.sqlite database/backups/database_$(date +%Y%m%d_%H%M%S).sqlite

# Con verifica
php artisan tinker --execute="
\$source = 'database/database.sqlite';
\$backup = 'database/backups/database_' . date('Ymd_His') . '.sqlite';
if (copy(\$source, \$backup)) {
    echo '✓ Backup created: ' . \$backup . PHP_EOL;
    echo 'Size: ' . round(filesize(\$backup) / 1024 / 1024, 2) . ' MB' . PHP_EOL;
} else {
    echo '⚠️  Backup failed' . PHP_EOL;
}
"
```

### Restore Database
```bash
# Restore da backup
cp database/backups/database_20251010_120000.sqlite database/database.sqlite

# Con verifica
php artisan migrate:status
```

---

## CACHE MANAGEMENT

### Clear All Caches
```bash
# Clear application cache
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Clear view cache
php artisan view:clear

# Clear all
php artisan optimize:clear
```

### Rebuild Caches
```bash
# Cache config
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize (all caches)
php artisan optimize
```

---

## USEFUL QUERIES

### Find Duplicate URLs per Tenant
```bash
php artisan tinker --execute="
DB::table('contents')
    ->select('tenant_id', 'url', DB::raw('count(*) as duplicates'))
    ->groupBy('tenant_id', 'url')
    ->having('duplicates', '>', 1)
    ->get()
    ->toArray()
"
```

### Token Usage by Tenant
```bash
php artisan tinker --execute="
DB::table('content_generations')
    ->select('tenant_id', DB::raw('SUM(tokens_used) as total_tokens'))
    ->groupBy('tenant_id')
    ->orderByDesc('total_tokens')
    ->get()
    ->toArray()
"
```

### Most Active Users
```bash
php artisan tinker --execute="
DB::table('activity_logs')
    ->select('user_id', DB::raw('count(*) as actions'))
    ->groupBy('user_id')
    ->orderByDesc('actions')
    ->limit(10)
    ->get()
    ->toArray()
"
```

### Crew Success Rate
```bash
php artisan tinker --execute="
\$total = App\Models\CrewExecution::count();
\$completed = App\Models\CrewExecution::where('status', 'completed')->count();
\$failed = App\Models\CrewExecution::where('status', 'failed')->count();
echo 'Total executions: ' . \$total . PHP_EOL;
echo 'Completed: ' . \$completed . ' (' . round(\$completed/\$total*100) . '%)' . PHP_EOL;
echo 'Failed: ' . \$failed . ' (' . round(\$failed/\$total*100) . '%)' . PHP_EOL;
"
```

---

## TESTING COMMANDS

### Run Tests
```bash
# All tests
php artisan test

# Specific test
php artisan test tests/Unit/Models/UserTest.php

# With coverage
php artisan test --coverage

# Parallel testing
php artisan test --parallel
```

### Database Testing
```bash
# Fresh database per test
php artisan test --env=testing

# Keep database after test
php artisan test --without-dropping
```

---

## FILAMENT ADMIN

### Create Admin User
```bash
php artisan tinker --execute="
\$user = App\Models\User::create([
    'email' => 'admin@ainstein.local',
    'password_hash' => bcrypt('password'),
    'name' => 'Admin',
    'is_super_admin' => true,
    'is_active' => true,
    'email_verified' => true,
    'role' => 'admin'
]);
echo 'Admin user created: ' . \$user->email . PHP_EOL;
"
```

### Clear Filament Cache
```bash
php artisan filament:clear-cache
```

---

## LOGS

### View Logs
```bash
# Laravel log
tail -f storage/logs/laravel.log

# Last 50 lines
tail -50 storage/logs/laravel.log

# Filter errors
grep "ERROR" storage/logs/laravel.log
```

### Clear Logs
```bash
# Clear all logs
truncate -s 0 storage/logs/laravel.log

# Or delete
rm storage/logs/*.log
```

---

## USEFUL ARTISAN COMMANDS

```bash
# List all commands
php artisan list

# Help for specific command
php artisan help migrate

# Check Laravel version
php artisan --version

# Environment info
php artisan about

# List routes
php artisan route:list

# List routes for specific controller
php artisan route:list --path=tenant

# Create new model with migration
php artisan make:model MyModel -m

# Create controller
php artisan make:controller MyController

# Create migration
php artisan make:migration create_my_table

# Generate IDE helper files
php artisan ide-helper:generate
php artisan ide-helper:models
```

---

## EMERGENCY COMMANDS

### Database Locked
```bash
# Kill all connections
php artisan tinker --execute="DB::disconnect()"

# Restart queue workers
php artisan queue:restart
```

### Permission Issues
```bash
# Fix storage permissions (Linux/Mac)
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Windows - run as admin
icacls storage /grant Users:F /t
```

### Reset Everything (DANGER!)
```bash
# Clear all caches and rebuild
php artisan optimize:clear
php artisan optimize

# Reset database and reseed
php artisan migrate:fresh --seed

# Regenerate app key
php artisan key:generate
```

---

## MONITORING

### Queue Workers
```bash
# Monitor queue
php artisan queue:listen --verbose

# Process queue once
php artisan queue:work --once

# Restart all workers
php artisan queue:restart
```

### Schedule
```bash
# List scheduled tasks
php artisan schedule:list

# Run schedule manually
php artisan schedule:run
```

---

**Last Updated**: 2025-10-10
**For detailed analysis**: See `AINSTEIN_DATABASE_ANALYSIS_REPORT.md`
**For action plan**: See `IMMEDIATE_ACTIONS.md`
