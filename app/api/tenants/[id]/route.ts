import { NextRequest, NextResponse } from 'next/server'
import { requireSuperAdmin } from '@/lib/auth/session'
import { prisma } from '@/lib/db/prisma'

// GET /api/tenants/[id] - Get single tenant
export async function GET(
  request: NextRequest,
  { params }: { params: { id: string } }
) {
  try {
    await requireSuperAdmin()

    const tenant = await prisma.tenant.findUnique({
      where: { id: params.id },
      include: {
        users: {
          select: {
            id: true,
            email: true,
            name: true,
            role: true,
            isActive: true,
            lastLogin: true,
            createdAt: true
          }
        },
        _count: {
          select: {
            pages: true,
            contentGenerations: true,
            prompts: true
          }
        }
      }
    })

    if (!tenant) {
      return NextResponse.json({ error: 'Tenant not found' }, { status: 404 })
    }

    return NextResponse.json(tenant)
  } catch (error) {
    console.error('Tenant GET error:', error)
    if (error.message === 'Not authenticated') {
      return NextResponse.json({ error: 'Not authorized' }, { status: 401 })
    }
    if (error.message === 'Super admin access required') {
      return NextResponse.json({ error: 'Super admin access required' }, { status: 403 })
    }
    return NextResponse.json({ error: 'Error fetching tenant' }, { status: 500 })
  }
}

// PUT /api/tenants/[id] - Update tenant
export async function PUT(
  request: NextRequest,
  { params }: { params: { id: string } }
) {
  try {
    await requireSuperAdmin()

    const body = await request.json()
    const { name, domain, subdomain, planType, tokensMonthlyLimit, status } = body

    // Verify tenant exists
    const existingTenant = await prisma.tenant.findUnique({
      where: { id: params.id }
    })

    if (!existingTenant) {
      return NextResponse.json({ error: 'Tenant not found' }, { status: 404 })
    }

    const updatedTenant = await prisma.tenant.update({
      where: { id: params.id },
      data: {
        ...(name && { name }),
        ...(domain !== undefined && { domain }),
        ...(subdomain && { subdomain }),
        ...(planType && { planType }),
        ...(tokensMonthlyLimit !== undefined && { tokensMonthlyLimit }),
        ...(status && { status }),
        updatedAt: new Date()
      }
    })

    return NextResponse.json(updatedTenant)
  } catch (error) {
    console.error('Tenant UPDATE error:', error)

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

    return NextResponse.json({ error: 'Error updating tenant' }, { status: 500 })
  }
}

// DELETE /api/tenants/[id] - Delete tenant
export async function DELETE(
  request: NextRequest,
  { params }: { params: { id: string } }
) {
  try {
    await requireSuperAdmin()

    // Verify tenant exists
    const existingTenant = await prisma.tenant.findUnique({
      where: { id: params.id },
      include: {
        _count: {
          select: {
            users: true,
            pages: true,
            contentGenerations: true
          }
        }
      }
    })

    if (!existingTenant) {
      return NextResponse.json({ error: 'Tenant not found' }, { status: 404 })
    }

    // Soft delete - set status to inactive instead of hard delete
    const deletedTenant = await prisma.tenant.update({
      where: { id: params.id },
      data: {
        status: 'inactive',
        updatedAt: new Date()
      }
    })

    // Also deactivate all users
    await prisma.user.updateMany({
      where: { tenantId: params.id },
      data: {
        isActive: false,
        updatedAt: new Date()
      }
    })

    return NextResponse.json({
      success: true,
      message: `Tenant ${existingTenant.name} has been deactivated`,
      tenant: deletedTenant
    })
  } catch (error) {
    console.error('Tenant DELETE error:', error)

    if (error.message === 'Not authenticated') {
      return NextResponse.json({ error: 'Not authorized' }, { status: 401 })
    }
    if (error.message === 'Super admin access required') {
      return NextResponse.json({ error: 'Super admin access required' }, { status: 403 })
    }

    return NextResponse.json({ error: 'Error deleting tenant' }, { status: 500 })
  }
}