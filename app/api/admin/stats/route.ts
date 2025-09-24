import { NextRequest, NextResponse } from 'next/server'
import { requireSuperAdmin } from '@/lib/auth/session'
import { getAllTenantsStats } from '@/lib/utils/tenant'

// GET /api/admin/stats - Get platform statistics
export async function GET() {
  try {
    await requireSuperAdmin()

    const stats = await getAllTenantsStats()

    return NextResponse.json(stats)
  } catch (error) {
    console.error('Platform stats error:', error)
    if (error.message === 'Not authenticated') {
      return NextResponse.json({ error: 'Not authorized' }, { status: 401 })
    }
    if (error.message === 'Super admin access required') {
      return NextResponse.json({ error: 'Super admin access required' }, { status: 403 })
    }
    return NextResponse.json({ error: 'Error fetching platform stats' }, { status: 500 })
  }
}