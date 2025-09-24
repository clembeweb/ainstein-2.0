import { NextRequest, NextResponse } from 'next/server'
import { getSession } from '@/lib/auth/session'
import { prisma } from '@/lib/db/prisma'

async function validateTenantAccess(request: NextRequest) {
  const session = await getSession()

  if (!session) {
    return { error: NextResponse.json({ error: 'Authentication required' }, { status: 401 }) }
  }

  if (!session.user.tenantId && !session.user.isSuperAdmin) {
    return { error: NextResponse.json({ error: 'Tenant access required' }, { status: 403 }) }
  }

  return { session }
}

// GET /api/pages - List pages for current tenant
export async function GET(request: NextRequest) {
  try {
    const { session, error } = await validateTenantAccess(request)
    if (error) return error

    const { searchParams } = new URL(request.url)

    // Query parameters
    const page = parseInt(searchParams.get('page') || '1')
    const limit = parseInt(searchParams.get('limit') || '20')
    const search = searchParams.get('search') || ''
    const category = searchParams.get('category') || ''
    const status = searchParams.get('status') || ''
    const sortBy = searchParams.get('sortBy') || 'createdAt'
    const sortOrder = searchParams.get('sortOrder') || 'desc'

    const skip = (page - 1) * limit

    // Build where clause
    const where: any = {}

    // Filter by tenant for non-super admins
    if (!session.user.isSuperAdmin) {
      where.tenantId = session.user.tenantId
    }

    if (search) {
      where.OR = [
        { urlPath: { contains: search } },
        { keyword: { contains: search } },
        { metadata: { contains: search } }
      ]
    }

    if (category) {
      where.category = category
    }

    if (status) {
      where.status = status
    }

    // Get pages with count
    const [pages, totalCount] = await Promise.all([
      prisma.page.findMany({
        where,
        include: {
          _count: {
            select: {
              generations: true
            }
          },
          generations: {
            select: {
              id: true,
              status: true,
              createdAt: true
            },
            orderBy: {
              createdAt: 'desc'
            },
            take: 1
          }
        },
        orderBy: {
          [sortBy]: sortOrder as 'asc' | 'desc'
        },
        skip,
        take: limit
      }),
      prisma.page.count({ where })
    ])

    // Calculate pagination info
    const totalPages = Math.ceil(totalCount / limit)
    const hasNext = page < totalPages
    const hasPrev = page > 1

    const response = {
      pages: pages.map(page => ({
        ...page,
        metadata: JSON.parse(page.metadata || '{}'),
        lastGeneration: page.generations[0] || null,
        generationsCount: page._count.generations
      })),
      pagination: {
        page,
        limit,
        totalCount,
        totalPages,
        hasNext,
        hasPrev
      },
      filters: {
        search,
        category,
        status,
        sortBy,
        sortOrder
      }
    }

    return NextResponse.json(response)

  } catch (error) {
    console.error('Pages GET error:', error)
    return NextResponse.json(
      { error: 'Error fetching pages' },
      { status: 500 }
    )
  }
}

// POST /api/pages - Create new page
export async function POST(request: NextRequest) {
  try {
    const { session, error } = await validateTenantAccess(request)
    if (error) return error

    const body = await request.json()
    const {
      urlPath,
      keyword,
      category,
      language = 'it',
      cmsType,
      cmsPageId,
      priority = 0,
      metadata = {}
    } = body

    // Validate required fields
    if (!urlPath) {
      return NextResponse.json(
        { error: 'URL path is required' },
        { status: 400 }
      )
    }

    // Validate tenant ID
    if (!session.user.tenantId && !session.user.isSuperAdmin) {
      return NextResponse.json(
        { error: 'Tenant ID is required' },
        { status: 400 }
      )
    }

    const tenantId = session.user.tenantId || body.tenantId // Super admin can specify tenant

    // Check if URL path already exists for this tenant
    const existingPage = await prisma.page.findFirst({
      where: {
        tenantId,
        urlPath: urlPath.trim()
      }
    })

    if (existingPage) {
      return NextResponse.json(
        { error: 'A page with this URL path already exists' },
        { status: 409 }
      )
    }

    // Create page
    const page = await prisma.page.create({
      data: {
        tenantId,
        urlPath: urlPath.trim(),
        keyword: keyword?.trim() || null,
        category: category?.trim() || null,
        language,
        cmsType: cmsType?.trim() || null,
        cmsPageId: cmsPageId?.trim() || null,
        status: 'draft',
        priority: Math.max(0, Math.min(10, priority || 0)),
        metadata: JSON.stringify(metadata)
      },
      include: {
        _count: {
          select: {
            generations: true
          }
        }
      }
    })

    return NextResponse.json({
      success: true,
      page: {
        ...page,
        metadata: JSON.parse(page.metadata || '{}'),
        generationsCount: page._count.generations
      }
    })

  } catch (error) {
    console.error('Page creation error:', error)
    return NextResponse.json(
      { error: 'Error creating page' },
      { status: 500 }
    )
  }
}