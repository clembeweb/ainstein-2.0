import { NextRequest, NextResponse } from 'next/server'
import { getSession } from '@/lib/auth/session'
import { prisma } from '@/lib/db/prisma'
import OpenAI from 'openai'

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

async function getOpenAIClient() {
  const openaiSetting = await prisma.platformSetting.findUnique({
    where: { key: 'openai_api_key' }
  })

  if (!openaiSetting?.value) {
    throw new Error('OpenAI API key not configured')
  }

  return new OpenAI({
    apiKey: openaiSetting.value
  })
}

// GET /api/generations - List content generations for tenant
export async function GET(request: NextRequest) {
  try {
    const { session, error } = await validateTenantAccess(request)
    if (error) return error

    const { searchParams } = new URL(request.url)
    const page = parseInt(searchParams.get('page') || '1')
    const limit = parseInt(searchParams.get('limit') || '20')
    const status = searchParams.get('status') || ''
    const pageId = searchParams.get('pageId') || ''
    const sortBy = searchParams.get('sortBy') || 'createdAt'
    const sortOrder = searchParams.get('sortOrder') || 'desc'

    const skip = (page - 1) * limit

    // Build where clause
    const where: any = {}

    // Filter by tenant for non-super admins
    if (!session.user.isSuperAdmin) {
      where.page = {
        tenantId: session.user.tenantId
      }
    }

    if (status) {
      where.status = status
    }

    if (pageId) {
      where.pageId = pageId
    }

    // Get generations with count
    const [generations, totalCount] = await Promise.all([
      prisma.contentGeneration.findMany({
        where,
        include: {
          page: {
            select: {
              id: true,
              urlPath: true,
              keyword: true,
              category: true
            }
          },
          // Note: prompt relation doesn't exist, using promptType field instead
        },
        orderBy: {
          [sortBy]: sortOrder as 'asc' | 'desc'
        },
        skip,
        take: limit
      }),
      prisma.contentGeneration.count({ where })
    ])

    // Calculate pagination info
    const totalPages = Math.ceil(totalCount / limit)
    const hasNext = page < totalPages
    const hasPrev = page > 1

    return NextResponse.json({
      generations,
      pagination: {
        page,
        limit,
        totalCount,
        totalPages,
        hasNext,
        hasPrev
      },
      filters: {
        status,
        pageId,
        sortBy,
        sortOrder
      }
    })

  } catch (error) {
    console.error('Generations GET error:', error)
    return NextResponse.json(
      { error: 'Error fetching generations' },
      { status: 500 }
    )
  }
}

// POST /api/generations - Create new content generation
export async function POST(request: NextRequest) {
  try {
    const { session, error } = await validateTenantAccess(request)
    if (error) return error

    const body = await request.json()
    const {
      pageIds,
      promptAlias,
      aiModel = 'gpt-4o',
      variables = {},
      batchSize = 10,
      priority = 0
    } = body

    // Validate required fields
    if (!pageIds || !Array.isArray(pageIds) || pageIds.length === 0) {
      return NextResponse.json(
        { error: 'Page IDs are required' },
        { status: 400 }
      )
    }

    if (!promptAlias) {
      return NextResponse.json(
        { error: 'Prompt alias is required' },
        { status: 400 }
      )
    }

    // Get prompt
    const prompt = await prisma.prompt.findFirst({
      where: {
        alias: promptAlias,
        tenantId: session.user.tenantId
      }
    })

    if (!prompt) {
      return NextResponse.json(
        { error: 'Prompt not found' },
        { status: 404 }
      )
    }

    // Verify pages belong to tenant (for non-super admins)
    const whereClause: any = { id: { in: pageIds } }
    if (!session.user.isSuperAdmin) {
      whereClause.tenantId = session.user.tenantId
    }

    const pages = await prisma.page.findMany({
      where: whereClause
    })

    if (pages.length !== pageIds.length) {
      return NextResponse.json(
        { error: 'Some pages not found or not accessible' },
        { status: 404 }
      )
    }

    // Check tenant token limit
    if (!session.user.isSuperAdmin) {
      const tenant = await prisma.tenant.findUnique({
        where: { id: session.user.tenantId! }
      })

      if (tenant) {
        const estimatedTokens = pageIds.length * 1000 // Rough estimate
        if (tenant.tokensUsedCurrentMonth + estimatedTokens > tenant.tokensMonthlyLimit) {
          return NextResponse.json(
            { error: 'Monthly token limit would be exceeded' },
            { status: 429 }
          )
        }
      }
    }

    // Create generation records
    const generations = await Promise.all(
      pageIds.map(async (pageId) => {
        return prisma.contentGeneration.create({
          data: {
            pageId,
            promptType: promptAlias,
            aiModel,
            variables: JSON.stringify(variables),
            status: 'pending',
            priority: priority || 0,
            tenantId: session.user.tenantId || 'system'
          },
          include: {
            page: {
              select: {
                id: true,
                urlPath: true,
                keyword: true,
                category: true
              }
            }
          }
        })
      })
    )

    // Start processing asynchronously (in a real app, this would be queued)
    processGenerations(generations.map(g => g.id)).catch(console.error)

    return NextResponse.json({
      success: true,
      message: `${generations.length} content generations queued`,
      generations
    })

  } catch (error) {
    console.error('Generation creation error:', error)
    return NextResponse.json(
      { error: 'Error creating generation' },
      { status: 500 }
    )
  }
}

// Background processing function
async function processGenerations(generationIds: string[]) {
  const openai = await getOpenAIClient()

  for (const generationId of generationIds) {
    try {
      // Update status to processing
      await prisma.contentGeneration.update({
        where: { id: generationId },
        data: { status: 'processing', startedAt: new Date() }
      })

      // Get generation with related data
      const generation = await prisma.contentGeneration.findUnique({
        where: { id: generationId },
        include: {
          page: true,
          prompt: true
        }
      })

      if (!generation) continue

      // Build prompt with variables
      let finalPrompt = generation.prompt.template
      const variables = JSON.parse(generation.variables || '{}')

      // Replace variables in prompt
      Object.entries(variables).forEach(([key, value]) => {
        finalPrompt = finalPrompt.replace(new RegExp(`{{${key}}}`, 'g'), String(value))
      })

      // Replace page-specific variables
      finalPrompt = finalPrompt
        .replace(/{{url_path}}/g, generation.page.urlPath || '')
        .replace(/{{keyword}}/g, generation.page.keyword || '')
        .replace(/{{category}}/g, generation.page.category || '')
        .replace(/{{language}}/g, generation.page.language || 'it')

      // Call OpenAI
      const completion = await openai.chat.completions.create({
        model: generation.aiModel,
        messages: [
          {
            role: 'system',
            content: 'Sei un esperto copywriter SEO che genera contenuti ottimizzati per i motori di ricerca.'
          },
          {
            role: 'user',
            content: finalPrompt
          }
        ],
        temperature: 0.7,
        max_tokens: 2000
      })

      const generatedContent = completion.choices[0]?.message?.content
      const tokensUsed = completion.usage?.total_tokens || 0

      if (generatedContent) {
        // Parse generated content if it's JSON
        let parsedContent: any = generatedContent
        let metaTitle = ''
        let metaDescription = ''

        try {
          const parsed = JSON.parse(generatedContent)
          if (parsed.content) parsedContent = parsed.content
          if (parsed.meta_title) metaTitle = parsed.meta_title
          if (parsed.meta_description) metaDescription = parsed.meta_description
        } catch (e) {
          // Keep original content if not JSON
        }

        // Update generation with results
        await prisma.contentGeneration.update({
          where: { id: generationId },
          data: {
            generatedContent: typeof parsedContent === 'string' ? parsedContent : JSON.stringify(parsedContent),
            metaTitle,
            metaDescription,
            tokensUsed,
            status: 'completed',
            completedAt: new Date()
          }
        })

        // Update tenant token usage
        if (generation.page.tenantId) {
          await prisma.tenant.update({
            where: { id: generation.page.tenantId },
            data: {
              tokensUsedCurrentMonth: {
                increment: tokensUsed
              }
            }
          })
        }
      } else {
        throw new Error('No content generated')
      }

    } catch (error) {
      console.error(`Error processing generation ${generationId}:`, error)

      // Mark as failed
      await prisma.contentGeneration.update({
        where: { id: generationId },
        data: {
          status: 'failed',
          errorMessage: error instanceof Error ? error.message : 'Unknown error',
          completedAt: new Date()
        }
      }).catch(console.error)
    }
  }
}