import { NextRequest, NextResponse } from 'next/server'
import { getSession } from '@/lib/auth/session'
import { prisma } from '@/lib/db/prisma'

async function validateTenantAccess(request: NextRequest, generationId: string) {
  const session = await getSession()

  if (!session) {
    return { error: NextResponse.json({ error: 'Authentication required' }, { status: 401 }) }
  }

  if (!session.user.tenantId && !session.user.isSuperAdmin) {
    return { error: NextResponse.json({ error: 'Tenant access required' }, { status: 403 }) }
  }

  // For non-super admins, verify the generation belongs to their tenant
  if (!session.user.isSuperAdmin) {
    const generation = await prisma.contentGeneration.findFirst({
      where: {
        id: generationId,
        page: {
          tenantId: session.user.tenantId
        }
      }
    })

    if (!generation) {
      return { error: NextResponse.json({ error: 'Generation not found' }, { status: 404 }) }
    }
  }

  return { session }
}

// GET /api/generations/[id] - Get single generation
export async function GET(
  request: NextRequest,
  { params }: { params: { id: string } }
) {
  try {
    const { session, error } = await validateTenantAccess(request, params.id)
    if (error) return error

    const whereClause: any = { id: params.id }

    const generation = await prisma.contentGeneration.findFirst({
      where: whereClause,
      include: {
        page: {
          select: {
            id: true,
            urlPath: true,
            keyword: true,
            category: true,
            language: true,
            cmsType: true,
            cmsPageId: true
          }
        },
        prompt: {
          select: {
            id: true,
            name: true,
            alias: true,
            description: true
          }
        },
        user: {
          select: {
            id: true,
            name: true,
            email: true
          }
        }
      }
    })

    if (!generation) {
      return NextResponse.json({ error: 'Generation not found' }, { status: 404 })
    }

    // Parse variables if they exist
    let variables = {}
    try {
      variables = JSON.parse(generation.variables || '{}')
    } catch (e) {
      // Keep empty object if parsing fails
    }

    return NextResponse.json({
      ...generation,
      variables
    })

  } catch (error) {
    console.error('Generation GET error:', error)
    return NextResponse.json({ error: 'Error fetching generation' }, { status: 500 })
  }
}

// PUT /api/generations/[id] - Update generation (limited fields)
export async function PUT(
  request: NextRequest,
  { params }: { params: { id: string } }
) {
  try {
    const { session, error } = await validateTenantAccess(request, params.id)
    if (error) return error

    const body = await request.json()
    const { status, priority, notes } = body

    const whereClause: any = { id: params.id }

    // Verify generation exists and get current data
    const existingGeneration = await prisma.contentGeneration.findFirst({
      where: whereClause,
      include: {
        page: {
          select: {
            tenantId: true
          }
        }
      }
    })

    if (!existingGeneration) {
      return NextResponse.json({ error: 'Generation not found' }, { status: 404 })
    }

    // For non-super admins, verify it belongs to their tenant
    if (!session.user.isSuperAdmin && existingGeneration.page.tenantId !== session.user.tenantId) {
      return NextResponse.json({ error: 'Generation not found' }, { status: 404 })
    }

    // Only allow certain status changes
    const allowedStatuses = ['pending', 'cancelled']
    if (status && !allowedStatuses.includes(status)) {
      // Only allow cancelling pending/processing generations
      if (status === 'cancelled' && !['pending', 'processing'].includes(existingGeneration.status)) {
        return NextResponse.json(
          { error: 'Can only cancel pending or processing generations' },
          { status: 400 }
        )
      }
    }

    // Update generation
    const updatedGeneration = await prisma.contentGeneration.update({
      where: { id: params.id },
      data: {
        ...(status !== undefined && { status }),
        ...(priority !== undefined && { priority: Math.max(0, Math.min(10, priority)) }),
        ...(notes !== undefined && { notes }),
        updatedAt: new Date()
      },
      include: {
        page: {
          select: {
            id: true,
            urlPath: true,
            keyword: true,
            category: true
          }
        },
        prompt: {
          select: {
            id: true,
            name: true,
            alias: true
          }
        }
      }
    })

    return NextResponse.json({
      success: true,
      generation: updatedGeneration
    })

  } catch (error) {
    console.error('Generation update error:', error)
    return NextResponse.json({ error: 'Error updating generation' }, { status: 500 })
  }
}

// DELETE /api/generations/[id] - Delete generation
export async function DELETE(
  request: NextRequest,
  { params }: { params: { id: string } }
) {
  try {
    const { session, error } = await validateTenantAccess(request, params.id)
    if (error) return error

    const whereClause: any = { id: params.id }

    // Verify generation exists
    const existingGeneration = await prisma.contentGeneration.findFirst({
      where: whereClause,
      include: {
        page: {
          select: {
            tenantId: true
          }
        }
      }
    })

    if (!existingGeneration) {
      return NextResponse.json({ error: 'Generation not found' }, { status: 404 })
    }

    // For non-super admins, verify it belongs to their tenant
    if (!session.user.isSuperAdmin && existingGeneration.page.tenantId !== session.user.tenantId) {
      return NextResponse.json({ error: 'Generation not found' }, { status: 404 })
    }

    // Only allow deletion of completed, failed, or cancelled generations
    const deletableStatuses = ['completed', 'failed', 'cancelled']
    if (!deletableStatuses.includes(existingGeneration.status)) {
      return NextResponse.json(
        { error: 'Can only delete completed, failed, or cancelled generations' },
        { status: 400 }
      )
    }

    // Delete generation
    await prisma.contentGeneration.delete({
      where: { id: params.id }
    })

    return NextResponse.json({
      success: true,
      message: 'Generation deleted successfully'
    })

  } catch (error) {
    console.error('Generation deletion error:', error)
    return NextResponse.json({ error: 'Error deleting generation' }, { status: 500 })
  }
}

// POST /api/generations/[id]/retry - Retry failed generation
export async function POST(
  request: NextRequest,
  { params }: { params: { id: string } }
) {
  try {
    const { session, error } = await validateTenantAccess(request, params.id)
    if (error) return error

    const whereClause: any = { id: params.id }

    // Verify generation exists and is failed
    const existingGeneration = await prisma.contentGeneration.findFirst({
      where: whereClause,
      include: {
        page: {
          select: {
            tenantId: true
          }
        }
      }
    })

    if (!existingGeneration) {
      return NextResponse.json({ error: 'Generation not found' }, { status: 404 })
    }

    // For non-super admins, verify it belongs to their tenant
    if (!session.user.isSuperAdmin && existingGeneration.page.tenantId !== session.user.tenantId) {
      return NextResponse.json({ error: 'Generation not found' }, { status: 404 })
    }

    // Only allow retry of failed generations
    if (existingGeneration.status !== 'failed') {
      return NextResponse.json(
        { error: 'Can only retry failed generations' },
        { status: 400 }
      )
    }

    // Reset generation to pending
    const updatedGeneration = await prisma.contentGeneration.update({
      where: { id: params.id },
      data: {
        status: 'pending',
        errorMessage: null,
        generatedContent: null,
        metaTitle: null,
        metaDescription: null,
        tokensUsed: 0,
        startedAt: null,
        completedAt: null,
        updatedAt: new Date()
      },
      include: {
        page: {
          select: {
            id: true,
            urlPath: true,
            keyword: true,
            category: true
          }
        },
        prompt: {
          select: {
            id: true,
            name: true,
            alias: true
          }
        }
      }
    })

    return NextResponse.json({
      success: true,
      message: 'Generation queued for retry',
      generation: updatedGeneration
    })

  } catch (error) {
    console.error('Generation retry error:', error)
    return NextResponse.json({ error: 'Error retrying generation' }, { status: 500 })
  }
}