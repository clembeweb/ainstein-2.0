<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use App\Models\Page;
use App\Models\Prompt;
use App\Models\ContentGeneration;
use App\Models\UsageHistory;
use App\Models\PlatformSetting;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        echo "🌱 Starting seed...\n";

        // Create Super Admin User (if not exists)
        $existingSuperAdmin = User::where('is_super_admin', true)->first();

        if (!$existingSuperAdmin) {
            $superAdmin = User::create([
                'id' => Str::ulid(),
                'email' => 'admin@ainstein.com',
                'name' => 'Super Admin',
                'password_hash' => Hash::make('Admin123!'),
                'role' => 'admin',
                'is_super_admin' => true,
                'is_active' => true,
                'email_verified' => true
            ]);
            echo "✅ Super Admin created: {$superAdmin->email}\n";
        } else {
            echo "✅ Super Admin already exists\n";
        }

        // Create Demo Tenant
        $demoTenant = Tenant::where('subdomain', 'demo')->first();

        if (!$demoTenant) {
            $demoTenant = Tenant::create([
                'id' => Str::ulid(),
                'name' => 'Demo Company',
                'subdomain' => 'demo',
                'domain' => 'demo.example.com',
                'plan_type' => 'professional',
                'tokens_monthly_limit' => 50000,
                'tokens_used_current' => 1500,
                'status' => 'active',
                'theme_config' => [
                    'primaryColor' => '#3B82F6',
                    'secondaryColor' => '#6366F1',
                    'brandName' => 'Demo Company'
                ],
                'features' => 'ai-generation,cms-integration,analytics'
            ]);
            echo "✅ Demo tenant created: {$demoTenant->name}\n";

            // Create tenant admin user
            $tenantAdmin = User::create([
                'id' => Str::ulid(),
                'email' => 'admin@demo.com',
                'name' => 'Demo Admin',
                'password_hash' => Hash::make('demo123'),
                'role' => 'admin',
                'is_active' => true,
                'email_verified' => true,
                'tenant_id' => $demoTenant->id
            ]);
            echo "✅ Demo tenant admin created: {$tenantAdmin->email}\n";

            // Create tenant member user
            $member = User::create([
                'id' => Str::ulid(),
                'email' => 'member@demo.com',
                'name' => 'Demo Member',
                'password_hash' => Hash::make('member123'),
                'role' => 'member',
                'is_active' => true,
                'email_verified' => true,
                'tenant_id' => $demoTenant->id
            ]);
            echo "✅ Demo member created: {$member->email}\n";

            // Create default prompts for demo tenant
            $prompts = [
                [
                    'id' => Str::ulid(),
                    'tenant_id' => $demoTenant->id,
                    'name' => 'Articolo Blog SEO',
                    'alias' => 'blog-article',
                    'description' => 'Template per generare articoli blog ottimizzati SEO',
                    'template' => 'Scrivi un articolo blog di circa 800 parole su: {{keyword}}. Include: titolo accattivante, introduzione, 3-4 sezioni principali con sottotitoli H2, conclusione con call-to-action. Ottimizza per SEO con keyword density naturale.',
                    'variables' => ['keyword'],
                    'category' => 'blog',
                    'is_active' => true,
                    'is_system' => true
                ],
                [
                    'id' => Str::ulid(),
                    'tenant_id' => $demoTenant->id,
                    'name' => 'Meta Description',
                    'alias' => 'meta-description',
                    'description' => 'Template per generare meta description ottimizzate',
                    'template' => 'Scrivi una meta description di massimo 155 caratteri per una pagina su: {{keyword}}. Deve essere accattivante, includere la keyword principale e una call-to-action che inviti al clic.',
                    'variables' => ['keyword'],
                    'category' => 'seo',
                    'is_active' => true,
                    'is_system' => true
                ],
                [
                    'id' => Str::ulid(),
                    'tenant_id' => $demoTenant->id,
                    'name' => 'Titolo H1 Ottimizzato',
                    'alias' => 'h1-title',
                    'description' => 'Template per generare titoli H1 ottimizzati SEO',
                    'template' => 'Crea 5 opzioni di titolo H1 accattivanti per una pagina su: {{keyword}}. Ogni titolo deve essere:\n- Massimo 60 caratteri\n- Includere la keyword principale\n- Essere coinvolgente e chiaro\n- Ottimizzato per SEO',
                    'variables' => ['keyword'],
                    'category' => 'seo',
                    'is_active' => true,
                    'is_system' => true
                ],
                [
                    'id' => Str::ulid(),
                    'tenant_id' => $demoTenant->id,
                    'name' => 'Descrizione Prodotto E-commerce',
                    'alias' => 'product-description',
                    'description' => 'Template per descrizioni prodotti e-commerce',
                    'template' => 'Scrivi una descrizione prodotto accattivante per: {{product_name}}. Include:\n- Introduzione coinvolgente\n- Caratteristiche principali ({{features}})\n- Benefici per il cliente\n- Call-to-action per l\'acquisto\n- Ottimizzazione SEO per {{target_keyword}}',
                    'variables' => ['product_name', 'features', 'target_keyword'],
                    'category' => 'ecommerce',
                    'is_active' => true,
                    'is_system' => true
                ]
            ];

            foreach ($prompts as $promptData) {
                Prompt::create($promptData);
            }
            echo "✅ Default prompts created for demo tenant\n";

            // Create sample pages
            $pages = [
                [
                    'id' => Str::ulid(),
                    'tenant_id' => $demoTenant->id,
                    'url_path' => '/blog/guida-seo-2024',
                    'keyword' => 'guida SEO 2024',
                    'category' => 'blog',
                    'language' => 'it',
                    'status' => 'active',
                    'priority' => 1,
                    'metadata' => [
                        'title' => 'Guida SEO 2024: Strategie Vincenti',
                        'description' => 'Scopri le migliori strategie SEO per il 2024',
                        'author' => 'Demo Author'
                    ]
                ],
                [
                    'id' => Str::ulid(),
                    'tenant_id' => $demoTenant->id,
                    'url_path' => '/servizi/consulenza-marketing',
                    'keyword' => 'consulenza marketing digitale',
                    'category' => 'servizi',
                    'language' => 'it',
                    'status' => 'active',
                    'priority' => 2,
                    'metadata' => [
                        'title' => 'Consulenza Marketing Digitale',
                        'description' => 'Servizi di consulenza marketing per la tua azienda',
                        'author' => 'Demo Author'
                    ]
                ],
                [
                    'id' => Str::ulid(),
                    'tenant_id' => $demoTenant->id,
                    'url_path' => '/prodotti/corso-online-seo',
                    'keyword' => 'corso SEO online',
                    'category' => 'prodotti',
                    'language' => 'it',
                    'status' => 'draft',
                    'priority' => 1,
                    'metadata' => [
                        'title' => 'Corso SEO Online Completo',
                        'description' => 'Impara la SEO con il nostro corso online',
                        'author' => 'Demo Author'
                    ]
                ]
            ];

            foreach ($pages as $pageData) {
                Page::create($pageData);
            }
            echo "✅ Sample pages created for demo tenant\n";

            // Create sample content generation
            $firstPage = Page::where('tenant_id', $demoTenant->id)->first();
            $firstPrompt = Prompt::where('tenant_id', $demoTenant->id)->first();
            if ($firstPage && $firstPrompt) {
                ContentGeneration::create([
                    'id' => Str::ulid(),
                    'tenant_id' => $demoTenant->id,
                    'page_id' => $firstPage->id,
                    'prompt_id' => $firstPrompt->id,
                    'prompt_type' => 'content',
                    'prompt_template' => $firstPrompt->template,
                    'variables' => ['keyword' => 'SEO', 'word_count' => '800'],
                    'additional_instructions' => 'Focus on practical tips',
                    'generated_content' => 'Nell\'era digitale di oggi, la SEO (Search Engine Optimization) rappresenta uno degli strumenti più potenti per aumentare la visibilità online del tuo business...',
                    'meta_title' => 'Guida SEO 2024: Strategie Vincenti per il Successo Online',
                    'meta_description' => 'Scopri le migliori strategie SEO per il 2024. Guida completa con tecniche avanzate, consigli pratici e strumenti essenziali.',
                    'tokens_used' => 450,
                    'ai_model' => 'gpt-4o',
                    'status' => 'completed',
                    'published_at' => now(),
                    'completed_at' => now(),
                    'created_by' => $demoMember->id
                ]);
                echo "✅ Sample content generation created\n";
            }

            // Create usage history for current month
            $currentMonth = now()->format('Y-m');
            UsageHistory::create([
                'id' => Str::ulid(),
                'tenant_id' => $demoTenant->id,
                'month' => $currentMonth,
                'tokens_used' => 1500,
                'pages_generated' => 3,
                'api_calls' => 8,
                'created_at' => now()
            ]);
            echo "✅ Usage history created\n";

        } else {
            echo "✅ Demo tenant already exists\n";
        }

        // Create Platform Settings (if not exists)
        $existingSettings = PlatformSetting::first();
        if (!$existingSettings) {
            PlatformSetting::create([
                'id' => Str::ulid(),
                'openai_model' => 'gpt-4o',
                'smtp_port' => 587
            ]);
            echo "✅ Platform settings created\n";
        } else {
            echo "✅ Platform settings already exist\n";
        }

        // Create default plans (if not exists)
        if (Plan::count() === 0) {
            $plans = [
                [
                    'id' => Str::ulid(),
                    'name' => 'Starter',
                    'slug' => 'starter',
                    'description' => 'Perfect for small businesses getting started with AI content',
                    'price_monthly' => 29.00,
                    'price_yearly' => 290.00,
                    'tokens_monthly_limit' => 10000,
                    'features' => ['ai-generation', 'basic-prompts', 'api-access'],
                    'max_users' => 3,
                    'max_api_keys' => 2,
                    'is_active' => true,
                    'sort_order' => 1
                ],
                [
                    'id' => Str::ulid(),
                    'name' => 'Professional',
                    'slug' => 'professional',
                    'description' => 'Advanced features for growing businesses',
                    'price_monthly' => 79.00,
                    'price_yearly' => 790.00,
                    'tokens_monthly_limit' => 50000,
                    'features' => ['ai-generation', 'custom-prompts', 'api-access', 'analytics', 'cms-integration'],
                    'max_users' => 10,
                    'max_api_keys' => 5,
                    'is_active' => true,
                    'sort_order' => 2
                ],
                [
                    'id' => Str::ulid(),
                    'name' => 'Enterprise',
                    'slug' => 'enterprise',
                    'description' => 'Full-featured solution for large organizations',
                    'price_monthly' => 199.00,
                    'price_yearly' => 1990.00,
                    'tokens_monthly_limit' => 200000,
                    'features' => ['ai-generation', 'custom-prompts', 'api-access', 'analytics', 'cms-integration', 'priority-support', 'white-label', 'webhook-support'],
                    'max_users' => 50,
                    'max_api_keys' => 20,
                    'is_active' => true,
                    'sort_order' => 3
                ],
                [
                    'id' => Str::ulid(),
                    'name' => 'Free Trial',
                    'slug' => 'free',
                    'description' => '7-day free trial to explore our platform',
                    'price_monthly' => 0.00,
                    'price_yearly' => 0.00,
                    'tokens_monthly_limit' => 1000,
                    'features' => ['ai-generation', 'basic-prompts'],
                    'max_users' => 1,
                    'max_api_keys' => 1,
                    'is_active' => true,
                    'sort_order' => 0
                ]
            ];

            foreach ($plans as $planData) {
                Plan::create($planData);
            }
            echo "✅ Default plans created\n";
        } else {
            echo "✅ Plans already exist\n";
        }

        echo "🏁 Seed completed!\n";
    }
}
