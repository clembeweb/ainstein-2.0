'use client'

import Link from 'next/link'
import { usePathname, useRouter } from 'next/navigation'
import { LayoutDashboard, FileText, Sparkles, MessageSquare, Settings, Users, Database, LogOut } from 'lucide-react'

const navigationItems = [
  {
    name: 'Dashboard',
    href: '/dashboard',
    icon: LayoutDashboard
  },
  {
    name: 'Pagine',
    href: '/pages',
    icon: FileText
  },
  {
    name: 'Generazioni AI',
    href: '/generations',
    icon: Sparkles
  },
  {
    name: 'Prompt',
    href: '/prompts',
    icon: MessageSquare
  },
  {
    name: 'Impostazioni',
    href: '/settings',
    icon: Settings
  }
]

const adminItems = [
  {
    name: 'Admin Dashboard',
    href: '/admin/dashboard',
    icon: Database
  },
  {
    name: 'Gestione Tenant',
    href: '/admin/tenants',
    icon: Users
  }
]

export default function Navigation() {
  const pathname = usePathname()
  const router = useRouter()

  // Check if user is on admin routes
  const isAdminRoute = pathname?.startsWith('/admin/')

  const handleLogout = async () => {
    try {
      const response = await fetch('/api/auth/logout', { method: 'POST' })
      if (response.ok) {
        router.push('/auth/login')
      }
    } catch (error) {
      console.error('Logout error:', error)
    }
  }

  return (
    <nav className="bg-white border-r border-gray-200 w-64 min-h-screen p-4 relative">
      <div className="mb-8">
        <Link href="/" className="flex items-center space-x-2">
          <Sparkles className="w-8 h-8 text-blue-600" />
          <span className="text-xl font-bold text-gray-900">Ainstein</span>
        </Link>
      </div>

      <div className="space-y-1">
        {navigationItems.map((item) => {
          const isActive = pathname === item.href
          return (
            <Link
              key={item.href}
              href={item.href}
              className={`flex items-center space-x-2 px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                isActive
                  ? 'bg-blue-100 text-blue-700'
                  : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
              }`}
            >
              <item.icon className="w-4 h-4" />
              <span>{item.name}</span>
            </Link>
          )
        })}
      </div>

      {/* Admin Section */}
      <div className="mt-8 pt-8 border-t border-gray-200">
        <h3 className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
          Amministrazione
        </h3>
        <div className="space-y-1">
          {adminItems.map((item) => {
            const isActive = pathname === item.href
            return (
              <Link
                key={item.href}
                href={item.href}
                className={`flex items-center space-x-2 px-3 py-2 rounded-md text-sm font-medium transition-colors ${
                  isActive
                    ? 'bg-red-100 text-red-700'
                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                }`}
              >
                <item.icon className="w-4 h-4" />
                <span>{item.name}</span>
              </Link>
            )
          })}
        </div>
      </div>

      {/* Logout Button */}
      <div className="absolute bottom-4 left-4 right-4">
        <button
          onClick={handleLogout}
          className="flex items-center space-x-2 px-3 py-2 w-full rounded-md text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-colors"
        >
          <LogOut className="w-4 h-4" />
          <span>Esci</span>
        </button>
      </div>
    </nav>
  )
}