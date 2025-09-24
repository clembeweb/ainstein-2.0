import { NextRequest, NextResponse } from 'next/server'
import { requireSuperAdmin } from '@/lib/auth/session'
import { prisma } from '@/lib/db/prisma'
import { generateTenantSubdomain } from '@/lib/utils/tenant'

// GET /api/tenants - List all tenants (Super Admin only)
export async function GET() {
  try {
    await requireSuperAdmin()

    const tenants = await prisma.tenant.findMany({
      include: {
        _count: {
          select: {
            users: true,
            pages: true,
            contentGenerations: true
          }
        }
      },
      orderBy: {
        createdAt: 'desc'
      }
    })

    return NextResponse.json(tenants)
  } catch (error) {
    console.error('Tenants GET error:', error)
    if (error.message === 'Not authenticated') {
      return NextResponse.json({ error: 'Not authorized' }, { status: 401 })
    }
    if (error.message === 'Super admin access required') {
      return NextResponse.json({ error: 'Super admin access required' }, { status: 403 })
    }
    return NextResponse.json({ error: 'Error fetching tenants' }, { status: 500 })
  }
}

// POST /api/tenants - Create new tenant
export async function POST(request: NextRequest) {
  try {
    await requireSuperAdmin()

    const body = await request.json()
    const { name, domain, planType, tokensMonthlyLimit, adminEmail, adminName } = body

    // Validate required fields
    if (!name || !adminEmail) {
      return NextResponse.json(
        { error: 'Name and admin email are required' },
        { status: 400 }
      )
    }

    // Generate unique subdomain
    const subdomain = await generateTenantSubdomain(name)

    // Create tenant with transaction
    const result = await prisma.$transaction(async (tx) => {
      // Create tenant
      const tenant = await tx.tenant.create({
        data: {
          name,
          domain,
          subdomain,
          planType: planType || 'starter',
          tokensMonthlyLimit: tokensMonthlyLimit || 10000,
          status: 'active'
        }
      })

      // Create admin user for tenant
      const { hashPassword } = await import('@/lib/auth/password')
      const tempPassword = Math.random().toString(36).slice(-8)
      const hashedPassword = await hashPassword(tempPassword)

      const adminUser = await tx.user.create({
        data: {
          email: adminEmail,
          name: adminName || 'Admin',
          passwordHash: hashedPassword,
          role: 'admin',
          isActive: true,
          tenantId: tenant.id
        }
      })

      // Create default prompt templates for tenant
      await tx.prompt.createMany({
        data: [
          {
            tenantId: tenant.id,
            name: 'Articolo Blog SEO',
            alias: 'blog-article',
            description: 'Template per generare articoli blog ottimizzati SEO',
            template: 'Scrivi un articolo blog di circa 800 parole su: {{keyword}}. Include: titolo accattivante, introduzione, 3-4 sezioni principali, conclusione. Ottimizza per SEO.',
            variables: JSON.stringify(['keyword']),
            category: 'blog',
            isSystem: true
          },
          {
            tenantId: tenant.id,
            name: 'Meta Description',
            alias: 'meta-description',
            description: 'Template per generare meta description ottimizzate',
            template: 'Scrivi una meta description di massimo 155 caratteri per una pagina su: {{keyword}}. Deve essere accattivante e includere call-to-action.',
            variables: JSON.stringify(['keyword']),
            category: 'seo',
            isSystem: true
          },
          {
            tenantId: tenant.id,
            name: 'Titolo H1',
            alias: 'h1-title',
            description: 'Template per generare titoli H1 ottimizzati',
            template: 'Crea 5 opzioni di titolo H1 accattivanti per una pagina su: {{keyword}}. Ogni titolo deve essere chiaro, coinvolgente e ottimizzato SEO.',
            variables: JSON.stringify(['keyword']),
            category: 'seo',
            isSystem: true
          }
        ]
      })

      return { tenant, adminUser, tempPassword }
    })

    // TODO: Send welcome email with login credentials

    return NextResponse.json({
      success: true,
      tenant: result.tenant,
      adminUser: {
        id: result.adminUser.id,
        email: result.adminUser.email,
        name: result.adminUser.name
      },
      tempPassword: result.tempPassword
    })

  } catch (error) {
    console.error('Tenant creation error:', error)

    if (error.message === 'Not authenticated') {
      return NextResponse.json({ error: 'Not authorized' }, { status: 401 })
    }
    if (error.message === 'Super admin access required') {
      return NextResponse.json({ error: 'Super admin access required' }, { status: 403 })
    }

    // Check for unique constraint violations
    if (error.code === 'P2002') {
      const field = error.meta?.target?.[0]
      return NextResponse.json(
        { error: `${field} already exists` },
        { status: 409 }
      )
    }

    return NextResponse.json({ error: 'Error creating tenant' }, { status: 500 })
  }
}