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

// GET /api/prompts - List prompts for current tenant
export async function GET(request: NextRequest) {
  try {
    const { session, error } = await validateTenantAccess(request)
    if (error) return error

    const { searchParams } = new URL(request.url)

    // Query parameters
    const page = parseInt(searchParams.get('page') || '1')
    const limit = parseInt(searchParams.get('limit') || '50')
    const search = searchParams.get('search') || ''
    const category = searchParams.get('category') || ''
    const includeGlobal = searchParams.get('includeGlobal') === 'true'
    const sortBy = searchParams.get('sortBy') || 'name'
    const sortOrder = searchParams.get('sortOrder') || 'asc'

    const skip = (page - 1) * limit

    // Build where clause
    const where: any = {}

    // For non-super admins, show only their tenant's prompts + global prompts
    if (!session.user.isSuperAdmin) {
      where.OR = [
        { tenantId: session.user.tenantId },
        ...(includeGlobal ? [{ tenantId: null }] : [])
      ]
    } else if (!includeGlobal && searchParams.get('tenantId')) {
      // Super admin can filter by specific tenant
      where.tenantId = searchParams.get('tenantId')
    }

    if (search) {
      where.OR = [
        ...(where.OR || []),
        { name: { contains: search, mode: 'insensitive' } },
        { alias: { contains: search, mode: 'insensitive' } },
        { description: { contains: search, mode: 'insensitive' } }
      ]
    }

    if (category) {
      where.category = category
    }

    // Get prompts with count
    const [prompts, totalCount] = await Promise.all([
      prisma.prompt.findMany({
        where,
        include: {
          tenant: {
            select: {
              id: true,
              name: true,
              subdomain: true
            }
          }
        },
        orderBy: {
          [sortBy]: sortOrder as 'asc' | 'desc'
        },
        skip,
        take: limit
      }),
      prisma.prompt.count({ where })
    ])

    // Calculate pagination info
    const totalPages = Math.ceil(totalCount / limit)
    const hasNext = page < totalPages
    const hasPrev = page > 1

    return NextResponse.json({
      prompts: prompts.map(prompt => ({
        ...prompt,
        isGlobal: !prompt.tenantId,
        usageCount: 0 // Placeholder for now, can be calculated if needed
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
        includeGlobal,
        sortBy,
        sortOrder
      }
    })

  } catch (error) {
    console.error('Prompts GET error:', error)
    return NextResponse.json(
      { error: 'Error fetching prompts' },
      { status: 500 }
    )
  }
}

// POST /api/prompts - Create new prompt
export async function POST(request: NextRequest) {
  try {
    const { session, error } = await validateTenantAccess(request)
    if (error) return error

    const body = await request.json()
    const {
      name,
      alias,
      description,
      template,
      category = 'custom',
      variables = [],
      isActive = true
    } = body

    // Validate required fields
    if (!name || !alias || !template) {
      return NextResponse.json(
        { error: 'Name, alias, and template are required' },
        { status: 400 }
      )
    }

    // Validate alias format (alphanumeric and underscores only)
    if (!/^[a-zA-Z0-9_]+$/.test(alias)) {
      return NextResponse.json(
        { error: 'Alias can only contain letters, numbers, and underscores' },
        { status: 400 }
      )
    }

    // Check if alias already exists for this tenant
    const tenantId = session.user.isSuperAdmin && body.tenantId ? body.tenantId : session.user.tenantId

    const existingPrompt = await prisma.prompt.findFirst({
      where: {
        alias,
        OR: [
          { tenantId },
          { tenantId: null } // Global prompts
        ]
      }
    })

    if (existingPrompt) {
      return NextResponse.json(
        { error: 'A prompt with this alias already exists' },
        { status: 409 }
      )
    }

    // Create prompt
    const prompt = await prisma.prompt.create({
      data: {
        tenantId,
        name: name.trim(),
        alias: alias.trim(),
        description: description?.trim() || null,
        template: template.trim(),
        category: category.trim(),
        variables: JSON.stringify(variables),
        isActive
      },
      include: {
        tenant: {
          select: {
            id: true,
            name: true,
            subdomain: true
          }
        }
      }
    })

    return NextResponse.json({
      success: true,
      prompt: {
        ...prompt,
        isGlobal: !prompt.tenantId,
        variables: JSON.parse(prompt.variables || '[]')
      }
    })

  } catch (error) {
    console.error('Prompt creation error:', error)
    return NextResponse.json(
      { error: 'Error creating prompt' },
      { status: 500 }
    )
  }
}

// GET /api/prompts/categories - Get available categories
export async function getCategoriesHandler(request: NextRequest) {
  try {
    const { session, error } = await validateTenantAccess(request)
    if (error) return error

    const whereClause: any = {}

    // For non-super admins, show only their tenant's categories + global
    if (!session.user.isSuperAdmin) {
      whereClause.OR = [
        { tenantId: session.user.tenantId },
        { tenantId: null }
      ]
    }

    const categories = await prisma.prompt.findMany({
      where: whereClause,
      select: {
        category: true
      },
      distinct: ['category']
    })

    const uniqueCategories = categories
      .map(p => p.category)
      .filter(Boolean)
      .sort()

    return NextResponse.json({
      categories: uniqueCategories
    })

  } catch (error) {
    console.error('Categories GET error:', error)
    return NextResponse.json(
      { error: 'Error fetching categories' },
      { status: 500 }
    )
  }
}