import { NextRequest, NextResponse } from 'next/server'
import { prisma } from '@/lib/db/prisma'
import { hashPassword } from '@/lib/auth/password'
import { generateToken } from '@/lib/auth/jwt'

// POST /api/tenant/register - Register user to existing tenant
export async function POST(request: NextRequest) {
  try {
    const body = await request.json()
    const { email, password, name, tenantSubdomain, inviteCode } = body

    // Validate required fields
    if (!email || !password || !name || !tenantSubdomain) {
      return NextResponse.json(
        { error: 'All fields are required' },
        { status: 400 }
      )
    }

    // Find tenant by subdomain
    const tenant = await prisma.tenant.findUnique({
      where: {
        subdomain: tenantSubdomain.toLowerCase(),
        status: 'active'
      }
    })

    if (!tenant) {
      return NextResponse.json(
        { error: 'Tenant not found or inactive' },
        { status: 404 }
      )
    }

    // Check if email already exists
    const existingUser = await prisma.user.findUnique({
      where: { email: email.toLowerCase() }
    })

    if (existingUser) {
      return NextResponse.json(
        { error: 'Email already registered' },
        { status: 409 }
      )
    }

    // Hash password
    const hashedPassword = await hashPassword(password)

    // Create user
    const user = await prisma.user.create({
      data: {
        email: email.toLowerCase(),
        name,
        passwordHash: hashedPassword,
        role: 'member', // Default role for new registrations
        isActive: true,
        tenantId: tenant.id
      },
      select: {
        id: true,
        email: true,
        name: true,
        role: true,
        isActive: true,
        tenantId: true,
        tenant: {
          select: {
            id: true,
            name: true,
            subdomain: true
          }
        }
      }
    })

    // Generate JWT token
    const token = generateToken({
      userId: user.id,
      email: user.email,
      role: user.role,
      tenantId: user.tenantId,
      isSuperAdmin: false
    })

    // Create response and set cookie
    const response = NextResponse.json({
      success: true,
      message: 'Registration successful',
      user
    })

    response.cookies.set('auth-token', token, {
      httpOnly: true,
      secure: false, // Set to true in production
      sameSite: 'lax',
      path: '/',
      maxAge: 7 * 24 * 60 * 60 // 7 days
    })

    return response

  } catch (error) {
    console.error('Tenant registration error:', error)
    return NextResponse.json(
      { error: 'Registration failed' },
      { status: 500 }
    )
  }
}