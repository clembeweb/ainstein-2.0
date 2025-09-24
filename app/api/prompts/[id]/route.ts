import { NextRequest, NextResponse } from 'next/server'
import { getSession } from '@/lib/auth/session'
import { prisma } from '@/lib/db/prisma'

async function validateTenantAccess(request: NextRequest, promptId: string) {
  const session = await getSession()

  if (!session) {
    return { error: NextResponse.json({ error: 'Authentication required' }, { status: 401 }) }
  }

  if (!session.user.tenantId && !session.user.isSuperAdmin) {
    return { error: NextResponse.json({ error: 'Tenant access required' }, { status: 403 }) }
  }

  // For non-super admins, verify the prompt belongs to their tenant or is global
  if (!session.user.isSuperAdmin) {
    const prompt = await prisma.prompt.findFirst({
      where: {
        id: promptId,
        OR: [
          { tenantId: session.user.tenantId },
          { tenantId: null } // Global prompts are readable by all
        ]
      }
    })

    if (!prompt) {
      return { error: NextResponse.json({ error: 'Prompt not found' }, { status: 404 }) }
    }

    // But they can only edit their own tenant's prompts
    if (request.method !== 'GET' && prompt.tenantId !== session.user.tenantId) {
      return { error: NextResponse.json({ error: 'Cannot modify global prompts' }, { status: 403 }) }
    }
  }

  return { session }
}

// GET /api/prompts/[id] - Get single prompt
export async function GET(
  request: NextRequest,
  { params }: { params: { id: string } }
) {
  try {
    const { session, error } = await validateTenantAccess(request, params.id)
    if (error) return error

    const prompt = await prisma.prompt.findFirst({
      where: {
        id: params.id,
        ...(session.user.isSuperAdmin ? {} : {
          OR: [
            { tenantId: session.user.tenantId },
            { tenantId: null }
          ]
        })
      },
      include: {
        tenant: {
          select: {
            id: true,
            name: true,
            subdomain: true
          }
        },
        _count: {
          select: {
            contentGenerations: true
          }
        }
      }
    })

    if (!prompt) {
      return NextResponse.json({ error: 'Prompt not found' }, { status: 404 })
    }

    // Parse variables
    let variables = []
    try {
      variables = JSON.parse(prompt.variables || '[]')
    } catch (e) {
      // Keep empty array if parsing fails
    }

    return NextResponse.json({
      ...prompt,
      isGlobal: !prompt.tenantId,
      variables,
      usageCount: prompt._count.contentGenerations
    })

  } catch (error) {
    console.error('Prompt GET error:', error)
    return NextResponse.json({ error: 'Error fetching prompt' }, { status: 500 })
  }
}

// PUT /api/prompts/[id] - Update prompt
export async function PUT(
  request: NextRequest,
  { params }: { params: { id: string } }
) {
  try {
    const { session, error } = await validateTenantAccess(request, params.id)
    if (error) return error

    const body = await request.json()
    const {
      name,
      alias,
      description,
      template,
      category,
      variables,
      isActive
    } = body

    // Verify prompt exists and user can edit it
    const existingPrompt = await prisma.prompt.findFirst({
      where: {
        id: params.id,
        ...(session.user.isSuperAdmin ? {} : { tenantId: session.user.tenantId })
      }
    })

    if (!existingPrompt) {
      return NextResponse.json({ error: 'Prompt not found' }, { status: 404 })
    }

    // Check alias uniqueness if changed
    if (alias && alias !== existingPrompt.alias) {
      if (!/^[a-zA-Z0-9_]+$/.test(alias)) {
        return NextResponse.json(
          { error: 'Alias can only contain letters, numbers, and underscores' },
          { status: 400 }
        )
      }

      const duplicatePrompt = await prisma.prompt.findFirst({
        where: {
          alias,
          id: { not: params.id },
          OR: [
            { tenantId: existingPrompt.tenantId },
            { tenantId: null }
          ]
        }
      })

      if (duplicatePrompt) {
        return NextResponse.json(
          { error: 'A prompt with this alias already exists' },
          { status: 409 }
        )
      }
    }

    // Update prompt
    const updatedPrompt = await prisma.prompt.update({
      where: { id: params.id },
      data: {
        ...(name !== undefined && { name: name.trim() }),
        ...(alias !== undefined && { alias: alias.trim() }),
        ...(description !== undefined && { description: description?.trim() || null }),
        ...(template !== undefined && { template: template.trim() }),
        ...(category !== undefined && { category: category.trim() }),
        ...(variables !== undefined && { variables: JSON.stringify(variables) }),
        ...(isActive !== undefined && { isActive }),
        updatedAt: new Date()
      },
      include: {
        tenant: {
          select: {
            id: true,
            name: true,
            subdomain: true
          }
        },
        _count: {
          select: {
            contentGenerations: true
          }
        }
      }
    })

    return NextResponse.json({
      success: true,
      prompt: {
        ...updatedPrompt,
        isGlobal: !updatedPrompt.tenantId,
        variables: JSON.parse(updatedPrompt.variables || '[]'),
        usageCount: updatedPrompt._count.contentGenerations
      }
    })

  } catch (error) {
    console.error('Prompt update error:', error)
    return NextResponse.json({ error: 'Error updating prompt' }, { status: 500 })
  }
}

// DELETE /api/prompts/[id] - Delete prompt
export async function DELETE(
  request: NextRequest,
  { params }: { params: { id: string } }
) {
  try {
    const { session, error } = await validateTenantAccess(request, params.id)
    if (error) return error

    // Verify prompt exists and user can delete it
    const existingPrompt = await prisma.prompt.findFirst({
      where: {
        id: params.id,
        ...(session.user.isSuperAdmin ? {} : { tenantId: session.user.tenantId })
      },
      include: {
        _count: {
          select: {
            contentGenerations: true
          }
        }
      }
    })

    if (!existingPrompt) {
      return NextResponse.json({ error: 'Prompt not found' }, { status: 404 })
    }

    // Check if prompt is being used
    if (existingPrompt._count.contentGenerations > 0) {
      return NextResponse.json(
        { error: 'Cannot delete prompt that has been used in content generations' },
        { status: 409 }
      )
    }

    // Delete prompt
    await prisma.prompt.delete({
      where: { id: params.id }
    })

    return NextResponse.json({
      success: true,
      message: 'Prompt deleted successfully'
    })

  } catch (error) {
    console.error('Prompt deletion error:', error)
    return NextResponse.json({ error: 'Error deleting prompt' }, { status: 500 })
  }
}

// POST /api/prompts/[id]/duplicate - Duplicate prompt
export async function POST(
  request: NextRequest,
  { params }: { params: { id: string } }
) {
  try {
    const { session, error } = await validateTenantAccess(request, params.id)
    if (error) return error

    const body = await request.json()
    const { name: newName, alias: newAlias } = body

    // Get original prompt
    const originalPrompt = await prisma.prompt.findFirst({
      where: {
        id: params.id,
        ...(session.user.isSuperAdmin ? {} : {
          OR: [
            { tenantId: session.user.tenantId },
            { tenantId: null }
          ]
        })
      }
    })

    if (!originalPrompt) {
      return NextResponse.json({ error: 'Prompt not found' }, { status: 404 })
    }

    // Generate new name and alias if not provided
    const duplicateName = newName || `${originalPrompt.name} (Copy)`
    const duplicateAlias = newAlias || `${originalPrompt.alias}_copy`

    // Validate alias
    if (!/^[a-zA-Z0-9_]+$/.test(duplicateAlias)) {
      return NextResponse.json(
        { error: 'Alias can only contain letters, numbers, and underscores' },
        { status: 400 }
      )
    }

    // Check alias uniqueness
    const existingDuplicate = await prisma.prompt.findFirst({
      where: {
        alias: duplicateAlias,
        OR: [
          { tenantId: session.user.tenantId },
          { tenantId: null }
        ]
      }
    })

    if (existingDuplicate) {
      return NextResponse.json(
        { error: 'A prompt with this alias already exists' },
        { status: 409 }
      )
    }

    // Create duplicate (always assign to current user's tenant, never global)
    const duplicatePrompt = await prisma.prompt.create({
      data: {
        tenantId: session.user.tenantId,
        name: duplicateName,
        alias: duplicateAlias,
        description: originalPrompt.description,
        template: originalPrompt.template,
        category: originalPrompt.category,
        variables: originalPrompt.variables,
        isActive: originalPrompt.isActive
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
      message: 'Prompt duplicated successfully',
      prompt: {
        ...duplicatePrompt,
        isGlobal: false,
        variables: JSON.parse(duplicatePrompt.variables || '[]')
      }
    })

  } catch (error) {
    console.error('Prompt duplication error:', error)
    return NextResponse.json({ error: 'Error duplicating prompt' }, { status: 500 })
  }
}