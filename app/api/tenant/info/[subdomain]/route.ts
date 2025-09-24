import { NextRequest, NextResponse } from 'next/server'
import { prisma } from '@/lib/db/prisma'

// GET /api/tenant/info/[subdomain] - Get public tenant info for registration page
export async function GET(
  request: NextRequest,
  { params }: { params: { subdomain: string } }
) {
  try {
    const subdomain = params.subdomain.toLowerCase()

    const tenant = await prisma.tenant.findUnique({
      where: {
        subdomain,
        status: 'active'
      },
      select: {
        id: true,
        name: true,
        subdomain: true,
        domain: true,
        planType: true,
        themeConfig: true,
        brandConfig: true,
        createdAt: true
      }
    })

    if (!tenant) {
      return NextResponse.json(
        { error: 'Tenant not found or inactive' },
        { status: 404 }
      )
    }

    // Parse theme config
    let themeConfig = {}
    let brandConfig = {}

    try {
      themeConfig = JSON.parse(tenant.themeConfig || '{}')
      brandConfig = JSON.parse(tenant.brandConfig || '{}')
    } catch (error) {
      console.log('Error parsing tenant configs:', error)
    }

    const publicInfo = {
      id: tenant.id,
      name: tenant.name,
      subdomain: tenant.subdomain,
      domain: tenant.domain,
      planType: tenant.planType,
      logo: brandConfig.logoUrl || null,
      primaryColor: themeConfig.primaryColor || '#3B82F6',
      brandName: themeConfig.brandName || tenant.name,
      allowRegistrations: true, // Could be a tenant setting
      createdAt: tenant.createdAt
    }

    return NextResponse.json(publicInfo)

  } catch (error) {
    console.error('Tenant info error:', error)
    return NextResponse.json(
      { error: 'Error fetching tenant information' },
      { status: 500 }
    )
  }
}