import { prisma } from '@/lib/db/prisma'

/**
 * Genera un subdomain unico per un tenant basato sul nome
 */
export async function generateTenantSubdomain(name: string): Promise<string> {
  // Normalizza il nome per creare un subdomain valido
  let baseSubdomain = name
    .toLowerCase()
    .replace(/[^a-z0-9]/g, '-') // Sostituisce caratteri non alfanumerici con -
    .replace(/-+/g, '-') // Rimuove - consecutivi
    .replace(/^-|-$/g, '') // Rimuove - all'inizio e alla fine
    .substring(0, 20) // Limita a 20 caratteri

  // Se il subdomain è vuoto, usa un fallback
  if (!baseSubdomain) {
    baseSubdomain = 'tenant'
  }

  // Verifica che il subdomain sia unico
  let subdomain = baseSubdomain
  let counter = 1

  while (true) {
    const existing = await prisma.tenant.findUnique({
      where: { subdomain }
    })

    if (!existing) {
      return subdomain
    }

    // Se esiste, aggiungi un numero
    subdomain = `${baseSubdomain}-${counter}`
    counter++

    // Failsafe per evitare loop infiniti
    if (counter > 100) {
      subdomain = `${baseSubdomain}-${Date.now()}`
      break
    }
  }

  return subdomain
}

/**
 * Verifica se un subdomain è valido
 */
export function isValidSubdomain(subdomain: string): boolean {
  // Regole per subdomain validi:
  // - Solo lettere minuscole, numeri e trattini
  // - Non può iniziare o finire con un trattino
  // - Lunghezza tra 3 e 63 caratteri
  // - Non può essere una keyword riservata

  const reservedWords = [
    'www', 'api', 'admin', 'app', 'mail', 'email', 'ftp', 'cdn',
    'assets', 'static', 'blog', 'shop', 'store', 'dashboard',
    'panel', 'control', 'manage', 'system', 'root', 'test'
  ]

  if (reservedWords.includes(subdomain.toLowerCase())) {
    return false
  }

  const subdomainRegex = /^[a-z0-9]([a-z0-9-]{1,61}[a-z0-9])?$/

  return subdomainRegex.test(subdomain) && subdomain.length >= 3 && subdomain.length <= 63
}

/**
 * Ottiene il tenant dal subdomain nella request
 */
export async function getTenantFromSubdomain(subdomain: string) {
  if (!subdomain) {
    return null
  }

  return await prisma.tenant.findUnique({
    where: {
      subdomain: subdomain.toLowerCase(),
      status: 'active'
    },
    include: {
      users: {
        where: { isActive: true },
        select: {
          id: true,
          email: true,
          name: true,
          role: true
        }
      }
    }
  })
}

/**
 * Ottiene il tenant dall'ID con informazioni complete
 */
export async function getTenantWithStats(tenantId: string) {
  return await prisma.tenant.findUnique({
    where: { id: tenantId },
    include: {
      _count: {
        select: {
          users: { where: { isActive: true } },
          pages: true,
          contentGenerations: true,
          prompts: { where: { isActive: true } }
        }
      },
      usageHistory: {
        where: {
          month: new Date().toISOString().substring(0, 7) // YYYY-MM format
        },
        take: 1
      }
    }
  })
}

/**
 * Aggiorna l'usage di token per un tenant
 */
export async function updateTenantTokenUsage(tenantId: string, tokensUsed: number) {
  const currentMonth = new Date().toISOString().substring(0, 7)

  await prisma.$transaction(async (tx) => {
    // Aggiorna il contatore del tenant
    await tx.tenant.update({
      where: { id: tenantId },
      data: {
        tokensUsedCurrent: {
          increment: tokensUsed
        }
      }
    })

    // Aggiorna o crea il record di usage history
    await tx.usageHistory.upsert({
      where: {
        tenantId_month: {
          tenantId,
          month: currentMonth
        }
      },
      update: {
        tokensUsed: {
          increment: tokensUsed
        },
        apiCalls: {
          increment: 1
        }
      },
      create: {
        tenantId,
        month: currentMonth,
        tokensUsed,
        apiCalls: 1,
        pagesGenerated: 0
      }
    })
  })
}

/**
 * Verifica se un tenant ha ancora token disponibili
 */
export async function checkTenantTokenLimit(tenantId: string, requiredTokens: number = 0): Promise<{
  allowed: boolean
  tokensUsed: number
  tokensLimit: number
  tokensRemaining: number
}> {
  const tenant = await prisma.tenant.findUnique({
    where: { id: tenantId },
    select: {
      tokensUsedCurrent: true,
      tokensMonthlyLimit: true
    }
  })

  if (!tenant) {
    return {
      allowed: false,
      tokensUsed: 0,
      tokensLimit: 0,
      tokensRemaining: 0
    }
  }

  const tokensRemaining = tenant.tokensMonthlyLimit - tenant.tokensUsedCurrent
  const allowed = tokensRemaining >= requiredTokens

  return {
    allowed,
    tokensUsed: tenant.tokensUsedCurrent,
    tokensLimit: tenant.tokensMonthlyLimit,
    tokensRemaining
  }
}

/**
 * Reset mensile dei token utilizzati per tutti i tenant
 */
export async function resetMonthlyTokenUsage() {
  await prisma.tenant.updateMany({
    data: {
      tokensUsedCurrent: 0,
      updatedAt: new Date()
    }
  })
}

/**
 * Ottiene le statistiche di tutti i tenant per il super admin
 */
export async function getAllTenantsStats() {
  const tenants = await prisma.tenant.findMany({
    include: {
      _count: {
        select: {
          users: { where: { isActive: true } },
          pages: true,
          contentGenerations: true
        }
      }
    },
    orderBy: {
      createdAt: 'desc'
    }
  })

  const totalStats = {
    totalTenants: tenants.length,
    activeTenants: tenants.filter(t => t.status === 'active').length,
    totalUsers: tenants.reduce((sum, t) => sum + t._count.users, 0),
    totalPages: tenants.reduce((sum, t) => sum + t._count.pages, 0),
    totalGenerations: tenants.reduce((sum, t) => sum + t._count.contentGenerations, 0),
    totalTokensUsed: tenants.reduce((sum, t) => sum + t.tokensUsedCurrent, 0)
  }

  return {
    tenants,
    totalStats
  }
}