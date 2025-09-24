import { cookies } from 'next/headers'
import { prisma } from '@/lib/db/prisma'
import { verifyToken } from './jwt'

export async function getSession() {
  try {
    const cookieStore = await cookies()
    const token = cookieStore.get('auth-token')?.value

    if (!token) {
      console.log('getSession: No auth token found')
      return null
    }

    const payload = verifyToken(token)
    if (!payload) {
      return null
    }

    // Get user from database
    const user = await prisma.user.findUnique({
      where: { id: payload.userId },
      include: { tenant: true }
    })

    if (!user || !user.isActive) {
      return null
    }

    return {
      user: {
        id: user.id,
        email: user.email,
        name: user.name,
        role: user.role,
        isSuperAdmin: user.isSuperAdmin,
        tenantId: user.tenantId,
        tenant: user.tenant
      }
    }
  } catch (error) {
    console.error('Session error:', error)
    return null
  }
}

export async function requireAuth() {
  const session = await getSession()
  if (!session) {
    throw new Error('Not authenticated')
  }
  return session
}

export async function requireSuperAdmin() {
  const session = await requireAuth()
  if (!session.user.isSuperAdmin) {
    throw new Error('Super admin access required')
  }
  return session
}