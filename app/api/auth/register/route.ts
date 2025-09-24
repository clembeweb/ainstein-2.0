import { NextRequest, NextResponse } from 'next/server'
import { prisma } from '@/lib/db/prisma'
import { hashPassword } from '@/lib/auth/password'
import { generateToken } from '@/lib/auth/jwt'

export async function POST(request: NextRequest) {
  try {
    const { email, password, name, tenantName } = await request.json()

    if (!email || !password || !tenantName) {
      return NextResponse.json(
        { error: 'Email, password e nome azienda sono richiesti' },
        { status: 400 }
      )
    }

    // Check if user exists
    const existingUser = await prisma.user.findUnique({
      where: { email }
    })

    if (existingUser) {
      return NextResponse.json(
        { error: 'Email già registrata' },
        { status: 409 }
      )
    }

    // Generate subdomain from tenant name
    const subdomain = tenantName
      .toLowerCase()
      .replace(/[^a-z0-9]/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '')

    // Check if subdomain is available
    const existingTenant = await prisma.tenant.findUnique({
      where: { subdomain }
    })

    if (existingTenant) {
      return NextResponse.json(
        { error: 'Nome azienda non disponibile' },
        { status: 409 }
      )
    }

    // Hash password
    const hashedPassword = await hashPassword(password)

    // Create tenant and user in transaction
    const result = await prisma.$transaction(async (tx) => {
      // Create tenant
      const tenant = await tx.tenant.create({
        data: {
          name: tenantName,
          subdomain,
          planType: 'starter',
          tokensMonthlyLimit: 10000
        }
      })

      // Create user as admin of the tenant
      const user = await tx.user.create({
        data: {
          email,
          passwordHash: hashedPassword,
          name,
          role: 'admin',
          tenantId: tenant.id,
          emailVerified: false
        }
      })

      // Create default prompts for tenant
      await tx.prompt.createMany({
        data: [
          {
            tenantId: tenant.id,
            name: 'Articolo SEO',
            alias: 'seo-article',
            template: 'Scrivi un articolo ottimizzato SEO su "{keyword}" in {language}. Il contenuto deve essere di almeno {minWords} parole.',
            variables: JSON.stringify(['keyword', 'language', 'minWords']),
            category: 'content',
            isSystem: true
          },
          {
            tenantId: tenant.id,
            name: 'Meta Description',
            alias: 'meta-description',
            template: 'Crea una meta description ottimizzata di massimo 160 caratteri per una pagina su "{keyword}".',
            variables: JSON.stringify(['keyword']),
            category: 'seo',
            isSystem: true
          }
        ]
      })

      return { tenant, user }
    })

    // Generate token
    const token = generateToken(result.user)

    // Create session
    await prisma.session.create({
      data: {
        sessionToken: token,
        userId: result.user.id,
        expires: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000)
      }
    })

    const response = NextResponse.json({
      user: {
        id: result.user.id,
        email: result.user.email,
        name: result.user.name,
        role: result.user.role,
        tenantId: result.tenant.id,
        tenantName: result.tenant.name
      },
      token
    })

    // Set cookie
    response.cookies.set('auth-token', token, {
      httpOnly: true,
      secure: process.env.NODE_ENV === 'production',
      sameSite: 'lax',
      maxAge: 7 * 24 * 60 * 60
    })

    return response
  } catch (error) {
    console.error('Registration error:', error)
    return NextResponse.json(
      { error: 'Errore durante la registrazione' },
      { status: 500 }
    )
  }
}