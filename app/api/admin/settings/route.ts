import { NextRequest, NextResponse } from 'next/server'
import { requireSuperAdmin } from '@/lib/auth/session'
import { prisma } from '@/lib/db/prisma'

// GET platform settings
export async function GET() {
  try {
    console.log('Settings GET: Starting authentication check...')
    const session = await requireSuperAdmin()
    console.log('Settings GET: Authentication successful', session.user.email)


    let settings = await prisma.platformSettings.findFirst()

    // Create default settings if not exists
    if (!settings) {
      settings = await prisma.platformSettings.create({
        data: {
          openaiModel: 'gpt-4o'
        }
      })
    }

    // Return settings (mask sensitive fields for display but keep them editable)
    return NextResponse.json({
      openaiApiKey: settings.openaiApiKey || '',
      openaiModel: settings.openaiModel || 'gpt-4o',
      stripeSecretKey: settings.stripeSecretKey || '',
      stripeWebhook: settings.stripeWebhook || '',
      smtpHost: settings.smtpHost || '',
      smtpPort: settings.smtpPort || 587,
      smtpUser: settings.smtpUser || '',
      smtpPass: settings.smtpPass || '',
      googleClientId: settings.googleClientId || '',
      googleClientSecret: settings.googleClientSecret || ''
    })
  } catch (error) {
    console.error('Settings GET error:', error)
    if (error.message === 'Not authenticated') {
      return NextResponse.json(
        { error: 'Not authorized' },
        { status: 401 }
      )
    }
    if (error.message === 'Super admin access required') {
      return NextResponse.json(
        { error: 'Super admin access required' },
        { status: 403 }
      )
    }
    return NextResponse.json(
      { error: 'Errore nel recupero delle impostazioni' },
      { status: 500 }
    )
  }
}

// UPDATE platform settings
export async function PUT(request: NextRequest) {
  try {
    console.log('Settings PUT: Starting authentication check...')
    const session = await requireSuperAdmin()
    console.log('Settings PUT: Authentication successful', session.user.email)

    const data = await request.json()

    // Find existing settings
    let settings = await prisma.platformSettings.findFirst()

    // Update provided fields
    const updateData: any = {}

    if (data.openaiApiKey !== undefined) {
      updateData.openaiApiKey = data.openaiApiKey
    }
    if (data.openaiModel !== undefined) {
      updateData.openaiModel = data.openaiModel
    }
    if (data.stripeSecretKey !== undefined) {
      updateData.stripeSecretKey = data.stripeSecretKey
    }
    if (data.stripeWebhook !== undefined) {
      updateData.stripeWebhook = data.stripeWebhook
    }
    if (data.smtpHost !== undefined) {
      updateData.smtpHost = data.smtpHost
    }
    if (data.smtpPort !== undefined) {
      updateData.smtpPort = parseInt(data.smtpPort) || 587
    }
    if (data.smtpUser !== undefined) {
      updateData.smtpUser = data.smtpUser
    }
    if (data.smtpPass !== undefined) {
      updateData.smtpPass = data.smtpPass
    }
    if (data.googleClientId !== undefined) {
      updateData.googleClientId = data.googleClientId
    }
    if (data.googleClientSecret !== undefined) {
      updateData.googleClientSecret = data.googleClientSecret
    }

    if (settings) {
      settings = await prisma.platformSettings.update({
        where: { id: settings.id },
        data: updateData
      })
    } else {
      settings = await prisma.platformSettings.create({
        data: {
          openaiModel: 'gpt-4o',
          ...updateData
        }
      })
    }

    return NextResponse.json({ success: true })
  } catch (error) {
    console.error('Settings PUT error:', error)
    if (error.message === 'Not authenticated') {
      return NextResponse.json(
        { error: 'Not authorized' },
        { status: 401 }
      )
    }
    if (error.message === 'Super admin access required') {
      return NextResponse.json(
        { error: 'Super admin access required' },
        { status: 403 }
      )
    }
    return NextResponse.json(
      { error: 'Errore nell\'aggiornamento delle impostazioni' },
      { status: 500 }
    )
  }
}