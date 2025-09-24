import { redirect } from 'next/navigation'
import { requireSuperAdmin } from '@/lib/auth/session'
import AdminDashboardClient from './AdminDashboardClient'

export default async function AdminDashboard() {
  try {
    const session = await requireSuperAdmin()
    return <AdminDashboardClient />
  } catch (error) {
    redirect('/auth/login')
  }
}