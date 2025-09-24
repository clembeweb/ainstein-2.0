import { PrismaClient } from '@prisma/client'
import bcrypt from 'bcryptjs'

const prisma = new PrismaClient()

async function main() {
  console.log('🌱 Starting seed...')

  // Create Super Admin User (if not exists)
  const existingSuperAdmin = await prisma.user.findFirst({
    where: { isSuperAdmin: true }
  })

  if (!existingSuperAdmin) {
    const hashedPassword = await bcrypt.hash('Admin123!', 12)

    const superAdmin = await prisma.user.create({
      data: {
        email: 'admin@ainstein.com',
        name: 'Super Admin',
        passwordHash: hashedPassword,
        role: 'admin',
        isSuperAdmin: true,
        isActive: true,
        emailVerified: true
      }
    })
    console.log('✅ Super Admin created:', superAdmin.email)
  } else {
    console.log('✅ Super Admin already exists')
  }

  // Create Demo Tenant
  let demoTenant = await prisma.tenant.findFirst({
    where: { subdomain: 'demo' }
  })

  if (!demoTenant) {
    demoTenant = await prisma.tenant.create({
      data: {
        name: 'Demo Company',
        subdomain: 'demo',
        domain: 'demo.example.com',
        planType: 'professional',
        tokensMonthlyLimit: 50000,
        tokensUsedCurrent: 1500,
        status: 'active',
        themeConfig: JSON.stringify({
          primaryColor: '#3B82F6',
          secondaryColor: '#6366F1',
          brandName: 'Demo Company'
        }),
        features: 'ai-generation,cms-integration,analytics'
      }
    })
    console.log('✅ Demo tenant created:', demoTenant.name)

    // Create tenant admin user
    const tenantAdminPassword = await bcrypt.hash('demo123', 12)
    const tenantAdmin = await prisma.user.create({
      data: {
        email: 'admin@demo.com',
        name: 'Demo Admin',
        passwordHash: tenantAdminPassword,
        role: 'admin',
        isActive: true,
        emailVerified: true,
        tenantId: demoTenant.id
      }
    })
    console.log('✅ Demo tenant admin created:', tenantAdmin.email)

    // Create tenant member user
    const memberPassword = await bcrypt.hash('member123', 12)
    const member = await prisma.user.create({
      data: {
        email: 'member@demo.com',
        name: 'Demo Member',
        passwordHash: memberPassword,
        role: 'member',
        isActive: true,
        emailVerified: true,
        tenantId: demoTenant.id
      }
    })
    console.log('✅ Demo member created:', member.email)

    // Create default prompts for demo tenant
    await prisma.prompt.createMany({
      data: [
        {
          tenantId: demoTenant.id,
          name: 'Articolo Blog SEO',
          alias: 'blog-article',
          description: 'Template per generare articoli blog ottimizzati SEO',
          template: 'Scrivi un articolo blog di circa 800 parole su: {{keyword}}. Include: titolo accattivante, introduzione, 3-4 sezioni principali con sottotitoli H2, conclusione con call-to-action. Ottimizza per SEO con keyword density naturale.',
          variables: JSON.stringify(['keyword']),
          category: 'blog',
          isActive: true,
          isSystem: true
        },
        {
          tenantId: demoTenant.id,
          name: 'Meta Description',
          alias: 'meta-description',
          description: 'Template per generare meta description ottimizzate',
          template: 'Scrivi una meta description di massimo 155 caratteri per una pagina su: {{keyword}}. Deve essere accattivante, includere la keyword principale e una call-to-action che inviti al clic.',
          variables: JSON.stringify(['keyword']),
          category: 'seo',
          isActive: true,
          isSystem: true
        },
        {
          tenantId: demoTenant.id,
          name: 'Titolo H1 Ottimizzato',
          alias: 'h1-title',
          description: 'Template per generare titoli H1 ottimizzati SEO',
          template: 'Crea 5 opzioni di titolo H1 accattivanti per una pagina su: {{keyword}}. Ogni titolo deve essere:\n- Massimo 60 caratteri\n- Includere la keyword principale\n- Essere coinvolgente e chiaro\n- Ottimizzato per SEO',
          variables: JSON.stringify(['keyword']),
          category: 'seo',
          isActive: true,
          isSystem: true
        },
        {
          tenantId: demoTenant.id,
          name: 'Descrizione Prodotto E-commerce',
          alias: 'product-description',
          description: 'Template per descrizioni prodotti e-commerce',
          template: 'Scrivi una descrizione prodotto accattivante per: {{product_name}}. Include:\n- Introduzione coinvolgente\n- Caratteristiche principali ({{features}})\n- Benefici per il cliente\n- Call-to-action per l\'acquisto\n- Ottimizzazione SEO per {{target_keyword}}',
          variables: JSON.stringify(['product_name', 'features', 'target_keyword']),
          category: 'ecommerce',
          isActive: true,
          isSystem: true
        }
      ]
    })
    console.log('✅ Default prompts created for demo tenant')

    // Create sample pages
    await prisma.page.createMany({
      data: [
        {
          tenantId: demoTenant.id,
          urlPath: '/blog/guida-seo-2024',
          keyword: 'guida SEO 2024',
          category: 'blog',
          language: 'it',
          status: 'active',
          priority: 1,
          metadata: JSON.stringify({
            title: 'Guida SEO 2024: Strategie Vincenti',
            description: 'Scopri le migliori strategie SEO per il 2024',
            author: 'Demo Author'
          })
        },
        {
          tenantId: demoTenant.id,
          urlPath: '/servizi/consulenza-marketing',
          keyword: 'consulenza marketing digitale',
          category: 'servizi',
          language: 'it',
          status: 'active',
          priority: 2,
          metadata: JSON.stringify({
            title: 'Consulenza Marketing Digitale',
            description: 'Servizi di consulenza marketing per la tua azienda',
            author: 'Demo Author'
          })
        },
        {
          tenantId: demoTenant.id,
          urlPath: '/prodotti/corso-online-seo',
          keyword: 'corso SEO online',
          category: 'prodotti',
          language: 'it',
          status: 'draft',
          priority: 1,
          metadata: JSON.stringify({
            title: 'Corso SEO Online Completo',
            description: 'Impara la SEO con il nostro corso online',
            author: 'Demo Author'
          })
        }
      ]
    })
    console.log('✅ Sample pages created for demo tenant')

    // Create sample content generations
    const pages = await prisma.page.findMany({
      where: { tenantId: demoTenant.id }
    })

    if (pages.length > 0) {
      await prisma.contentGeneration.create({
        data: {
          tenantId: demoTenant.id,
          pageId: pages[0].id,
          promptType: 'blog-article',
          generatedContent: 'Nell\'era digitale di oggi, la SEO (Search Engine Optimization) rappresenta uno degli strumenti più potenti per aumentare la visibilità online del tuo business...',
          metaTitle: 'Guida SEO 2024: Strategie Vincenti per il Successo Online',
          metaDescription: 'Scopri le migliori strategie SEO per il 2024. Guida completa con tecniche avanzate, consigli pratici e strumenti essenziali.',
          tokensUsed: 450,
          aiModel: 'gpt-4o',
          status: 'completed',
          publishedAt: new Date()
        }
      })
      console.log('✅ Sample content generation created')
    }

    // Create usage history for current month
    const currentMonth = new Date().toISOString().substring(0, 7)
    await prisma.usageHistory.create({
      data: {
        tenantId: demoTenant.id,
        month: currentMonth,
        tokensUsed: 1500,
        pagesGenerated: 3,
        apiCalls: 8
      }
    })
    console.log('✅ Usage history created')

  } else {
    console.log('✅ Demo tenant already exists')
  }

  // Create Platform Settings (if not exists)
  const existingSettings = await prisma.platformSettings.findFirst()
  if (!existingSettings) {
    await prisma.platformSettings.create({
      data: {
        openaiModel: 'gpt-4o',
        smtpPort: 587
      }
    })
    console.log('✅ Platform settings created')
  } else {
    console.log('✅ Platform settings already exist')
  }
}

main()
  .catch((e) => {
    console.error('❌ Seed failed:', e)
    process.exit(1)
  })
  .finally(async () => {
    await prisma.$disconnect()
    console.log('🏁 Seed completed!')
  })