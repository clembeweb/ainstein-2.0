import { NextRequest, NextResponse } from 'next/server'
import { requireTenantAccess } from '@/lib/auth/tenant-middleware'
import { getTenantWithStats } from '@/lib/utils/tenant'

export const GET = requireTenantAccess(async (request: NextRequest, context) => {
  try {
    // Ottieni i dati completi del tenant con statistiche
    const tenantWithStats = await getTenantWithStats(context.tenant.id)

    if (!tenantWithStats) {
      return NextResponse.json(
        { error: 'Tenant non trovato' },
        { status: 404 }
      )
    }

    // Prepara la response con dati utili per la dashboard
    const response = {
      id: tenantWithStats.id,
      name: tenantWithStats.name,
      subdomain: tenantWithStats.subdomain,
      domain: tenantWithStats.domain,
      planType: tenantWithStats.planType,
      tokensMonthlyLimit: tenantWithStats.tokensMonthlyLimit,
      tokensUsedCurrent: tenantWithStats.tokensUsedCurrent,
      status: tenantWithStats.status,
      features: tenantWithStats.features,
      themeConfig: JSON.parse(tenantWithStats.themeConfig || '{}'),
      brandConfig: JSON.parse(tenantWithStats.brandConfig || '{}'),
      createdAt: tenantWithStats.createdAt,
      updatedAt: tenantWithStats.updatedAt,
      stats: {
        activeUsers: tenantWithStats._count.users,
        totalPages: tenantWithStats._count.pages,
        totalGenerations: tenantWithStats._count.contentGenerations,
        activePrompts: tenantWithStats._count.prompts
      },
      usage: {
        tokensUsed: tenantWithStats.tokensUsedCurrent,
        tokensLimit: tenantWithStats.tokensMonthlyLimit,
        tokensRemaining: tenantWithStats.tokensMonthlyLimit - tenantWithStats.tokensUsedCurrent,
        usagePercentage: Math.round((tenantWithStats.tokensUsedCurrent / tenantWithStats.tokensMonthlyLimit) * 100)
      },
      currentMonth: tenantWithStats.usageHistory[0] || null
    }

    return NextResponse.json(response)
  } catch (error) {
    console.error('Error fetching tenant:', error)
    return NextResponse.json(
      { error: 'Errore nel recupero del tenant' },
      { status: 500 }
    )
  }
})