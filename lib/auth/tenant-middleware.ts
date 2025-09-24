import { NextRequest, NextResponse } from 'next/server'
import { getSession } from './session'
import { getTenantFromSubdomain } from '@/lib/utils/tenant'

export interface TenantContext {
  tenant: any
  user: any
  canAccess: boolean
  role: string
}

/**
 * Middleware per verificare l'accesso al tenant e isolare i dati
 */
export async function withTenantAuth(request: NextRequest): Promise<{
  response?: NextResponse
  context?: TenantContext
}> {
  try {
    // Ottieni la sessione utente
    const session = await getSession()

    if (!session) {
      return {
        response: NextResponse.json(
          { error: 'Authentication required' },
          { status: 401 }
        )
      }
    }

    // Determina il tenant dalla request
    let tenant = null

    // 1. Prima prova dal subdomain nell'URL
    const host = request.headers.get('host')
    if (host) {
      const subdomain = host.split('.')[0]
      if (subdomain && subdomain !== 'www' && subdomain !== 'localhost:3000') {
        tenant = await getTenantFromSubdomain(subdomain)
      }
    }

    // 2. Se non trovato, usa il tenant dell'utente
    if (!tenant && session.user.tenantId) {
      const { prisma } = await import('@/lib/db/prisma')
      tenant = await prisma.tenant.findUnique({
        where: { id: session.user.tenantId }
      })
    }

    // 3. Verifica che il tenant esista e sia attivo
    if (!tenant || tenant.status !== 'active') {
      return {
        response: NextResponse.json(
          { error: 'Tenant not found or inactive' },
          { status: 404 }
        )
      }
    }

    // 4. Verifica che l'utente appartenga al tenant
    if (session.user.tenantId !== tenant.id && !session.user.isSuperAdmin) {
      return {
        response: NextResponse.json(
          { error: 'Access denied to this tenant' },
          { status: 403 }
        )
      }
    }

    // Crea il context per la request
    const context: TenantContext = {
      tenant,
      user: session.user,
      canAccess: true,
      role: session.user.role
    }

    return { context }

  } catch (error) {
    console.error('Tenant middleware error:', error)
    return {
      response: NextResponse.json(
        { error: 'Internal server error' },
        { status: 500 }
      )
    }
  }
}

/**
 * Wrapper per API routes che richiedono accesso al tenant
 */
export function requireTenantAccess(
  handler: (request: NextRequest, context: TenantContext) => Promise<NextResponse>
) {
  return async function(request: NextRequest) {
    const { response, context } = await withTenantAuth(request)

    if (response) {
      return response
    }

    if (!context) {
      return NextResponse.json(
        { error: 'Failed to establish tenant context' },
        { status: 500 }
      )
    }

    return handler(request, context)
  }
}

/**
 * Wrapper per API routes che richiedono ruolo admin del tenant
 */
export function requireTenantAdmin(
  handler: (request: NextRequest, context: TenantContext) => Promise<NextResponse>
) {
  return async function(request: NextRequest) {
    const { response, context } = await withTenantAuth(request)

    if (response) {
      return response
    }

    if (!context) {
      return NextResponse.json(
        { error: 'Failed to establish tenant context' },
        { status: 500 }
      )
    }

    // Verifica che l'utente sia admin del tenant o super admin
    if (context.role !== 'admin' && !context.user.isSuperAdmin) {
      return NextResponse.json(
        { error: 'Tenant admin access required' },
        { status: 403 }
      )
    }

    return handler(request, context)
  }
}

/**
 * Ottiene il tenant context dalla request (da usare nelle API routes)
 */
export async function getTenantContext(request: NextRequest): Promise<TenantContext | null> {
  const { context } = await withTenantAuth(request)
  return context || null
}

/**
 * Aggiunge headers per il tenant context alle response
 */
export function addTenantHeaders(response: NextResponse, context: TenantContext): NextResponse {
  response.headers.set('X-Tenant-Id', context.tenant.id)
  response.headers.set('X-Tenant-Name', context.tenant.name)
  response.headers.set('X-User-Role', context.role)

  return response
}

/**
 * Filtra i dati per il tenant corrente (utility per query Prisma)
 */
export function createTenantFilter(tenantId: string) {
  return {
    tenantId
  }
}

/**
 * Verifica i permessi per operazioni specifiche
 */
export function hasPermission(context: TenantContext, operation: string, resource: string): boolean {
  // Super admin può fare tutto
  if (context.user.isSuperAdmin) {
    return true
  }

  const { role } = context

  // Logica dei permessi basata sui ruoli
  switch (role) {
    case 'admin':
      return true // Admin tenant può fare tutto sul proprio tenant

    case 'member':
      // Membri possono leggere e creare, ma non eliminare/modificare impostazioni
      if (operation === 'read' || operation === 'create') {
        return true
      }
      if (operation === 'update') {
        return !['settings', 'users', 'prompts'].includes(resource)
      }
      return false

    case 'viewer':
      // Viewer può solo leggere
      return operation === 'read'

    default:
      return false
  }
}

/**
 * Middleware per logging delle azioni del tenant
 */
export async function logTenantActivity(
  context: TenantContext,
  action: string,
  entity: string,
  entityId?: string,
  metadata?: any,
  ipAddress?: string,
  userAgent?: string
) {
  try {
    const { prisma } = await import('@/lib/db/prisma')

    await prisma.activityLog.create({
      data: {
        userId: context.user.id,
        action,
        entity,
        entityId,
        metadata: JSON.stringify(metadata || {}),
        ipAddress,
        userAgent
      }
    })
  } catch (error) {
    console.error('Error logging tenant activity:', error)
    // Non propagare l'errore per non interrompere l'operazione principale
  }
}