import { NextRequest, NextResponse } from 'next/server'
import { getSession } from '@/lib/auth/session'
import { prisma } from '@/lib/db/prisma'

async function validateTenantAccess(request: NextRequest, pageId: string) {
  const session = await getSession()

  if (!session) {
    return { error: NextResponse.json({ error: 'Authentication required' }, { status: 401 }) }
  }

  if (!session.user.tenantId && !session.user.isSuperAdmin) {
    return { error: NextResponse.json({ error: 'Tenant access required' }, { status: 403 }) }
  }

  // For non-super admins, verify the page belongs to their tenant
  if (!session.user.isSuperAdmin) {
    const page = await prisma.page.findFirst({
      where: {
        id: pageId,
        tenantId: session.user.tenantId
      }
    })

    if (!page) {
      return { error: NextResponse.json({ error: 'Page not found' }, { status: 404 }) }
    }
  }

  return { session }
}

// GET /api/pages/[id] - Get single page
export async function GET(
  request: NextRequest,
  { params }: { params: { id: string } }
) {
  try {
    const { session, error } = await validateTenantAccess(request, params.id)
    if (error) return error

    const whereClause: any = { id: params.id }
    if (!session.user.isSuperAdmin) {
      whereClause.tenantId = session.user.tenantId
    }

    const page = await prisma.page.findFirst({
      where: whereClause,
      include: {
        generations: {
          orderBy: {
            createdAt: 'desc'
          },
          take: 10
        },
        _count: {
          select: {
            generations: true
          }
        }
      }
    })

    if (!page) {
      return NextResponse.json({ error: 'Page not found' }, { status: 404 })
    }

    return NextResponse.json({
      ...page,
      metadata: JSON.parse(page.metadata || '{}'),
      generationsCount: page._count.generations
    })

  } catch (error) {
    console.error('Page GET error:', error)
    return NextResponse.json({ error: 'Error fetching page' }, { status: 500 })
  }
}

// PUT /api/pages/[id] - Update page
export async function PUT(
  request: NextRequest,
  { params }: { params: { id: string } }
) {
  try {
    const { session, error } = await validateTenantAccess(request, params.id)
    if (error) return error

    const body = await request.json()
    const {
      urlPath,
      keyword,
      category,
      language,
      cmsType,
      cmsPageId,
      status,
      priority,
      metadata = {}
    } = body

    const whereClause: any = { id: params.id }
    if (!session.user.isSuperAdmin) {
      whereClause.tenantId = session.user.tenantId
    }

    // Verify page exists
    const existingPage = await prisma.page.findFirst({
      where: whereClause
    })

    if (!existingPage) {
      return NextResponse.json({ error: 'Page not found' }, { status: 404 })
    }

    // Check URL path uniqueness if changed
    if (urlPath && urlPath !== existingPage.urlPath) {
      const duplicateWhereClause: any = {
        urlPath: urlPath.trim(),
        id: { not: params.id }
      }
      if (!session.user.isSuperAdmin) {
        duplicateWhereClause.tenantId = session.user.tenantId
      }

      const duplicatePage = await prisma.page.findFirst({
        where: duplicateWhereClause
      })

      if (duplicatePage) {
        return NextResponse.json(
          { error: 'A page with this URL path already exists' },
          { status: 409 }
        )
      }
    }

    // Update page
    const updatedPage = await prisma.page.update({
      where: { id: params.id },
      data: {
        ...(urlPath !== undefined && { urlPath: urlPath.trim() }),
        ...(keyword !== undefined && { keyword: keyword?.trim() || null }),
        ...(category !== undefined && { category: category?.trim() || null }),
        ...(language !== undefined && { language }),
        ...(cmsType !== undefined && { cmsType: cmsType?.trim() || null }),
        ...(cmsPageId !== undefined && { cmsPageId: cmsPageId?.trim() || null }),
        ...(status !== undefined && { status }),
        ...(priority !== undefined && { priority: Math.max(0, Math.min(10, priority || 0)) }),
        ...(metadata !== undefined && { metadata: JSON.stringify(metadata) }),
        updatedAt: new Date()
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
        ...updatedPage,
        metadata: JSON.parse(updatedPage.metadata || '{}'),
        generationsCount: updatedPage._count.generations
      }
    })

  } catch (error) {
    console.error('Page update error:', error)
    return NextResponse.json({ error: 'Error updating page' }, { status: 500 })
  }
}

// DELETE /api/pages/[id] - Delete page
export async function DELETE(
  request: NextRequest,
  { params }: { params: { id: string } }
) {
  try {
    const { session, error } = await validateTenantAccess(request, params.id)
    if (error) return error

    const whereClause: any = { id: params.id }
    if (!session.user.isSuperAdmin) {
      whereClause.tenantId = session.user.tenantId
    }

    // Verify page exists and get generation count
    const existingPage = await prisma.page.findFirst({
      where: whereClause,
      include: {
        _count: {
          select: {
            generations: true
          }
        }
      }
    })

    if (!existingPage) {
      return NextResponse.json({ error: 'Page not found' }, { status: 404 })
    }

    // Check permissions - only admins can delete pages with generations
    if (existingPage._count.generations > 0 && session.user.role !== 'admin' && !session.user.isSuperAdmin) {
      return NextResponse.json(
        { error: 'Only admins can delete pages with generations' },
        { status: 403 }
      )
    }

    // Delete page (cascades to generations due to schema)
    await prisma.page.delete({
      where: { id: params.id }
    })

    return NextResponse.json({
      success: true,
      message: 'Page deleted successfully'
    })

  } catch (error) {
    console.error('Page deletion error:', error)
    return NextResponse.json({ error: 'Error deleting page' }, { status: 500 })
  }
}