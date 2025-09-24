import jwt from 'jsonwebtoken'
import { User } from '@prisma/client'

const JWT_SECRET = process.env.JWT_SECRET || 'your-jwt-secret-change-this-in-production'
const JWT_EXPIRES_IN = process.env.JWT_EXPIRES_IN || '7d'

export interface JWTPayload {
  userId: string
  email: string
  tenantId?: string
  role: string
  isSuperAdmin: boolean
}

export function generateToken(user: User): string {
  const payload: JWTPayload = {
    userId: user.id,
    email: user.email,
    tenantId: user.tenantId || undefined,
    role: user.role,
    isSuperAdmin: user.isSuperAdmin
  }

  return jwt.sign(payload, JWT_SECRET, { expiresIn: JWT_EXPIRES_IN })
}

export function verifyToken(token: string): JWTPayload | null {
  try {
    return jwt.verify(token, JWT_SECRET) as JWTPayload
  } catch (error) {
    return null
  }
}