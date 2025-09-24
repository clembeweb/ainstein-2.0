'use client'

import { useState, useEffect } from 'react'
import {
  FileText,
  Sparkles,
  Clock,
  TrendingUp,
  BarChart3,
  Settings,
  LogOut,
  Menu,
  X,
  Plus,
  RefreshCw
} from 'lucide-react'
import Link from 'next/link'
import { useRouter } from 'next/navigation'

interface Stats {
  totalPages: number
  generationsToday: number
  tokensUsed: number
  tokensLimit: number
  successRate: number
}

export default function Dashboard() {
  const router = useRouter()
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const [stats, setStats] = useState<Stats>({
    totalPages: 0,
    generationsToday: 0,
    tokensUsed: 0,
    tokensLimit: 10000,
    successRate: 0
  })
  const [tenant, setTenant] = useState<any>(null)

  useEffect(() => {
    loadDashboardData()
  }, [])

  const loadDashboardData = async () => {
    try {
      // Load tenant info
      const tenantRes = await fetch('/api/tenant/current')
      if (tenantRes.ok) {
        const tenantData = await tenantRes.json()
        setTenant(tenantData)
        setStats(prev => ({
          ...prev,
          tokensUsed: tenantData.tokensUsedCurrent,
          tokensLimit: tenantData.tokensMonthlyLimit
        }))
      }
    } catch (error) {
      console.error('Error loading dashboard:', error)
    }
  }

  const handleLogout = async () => {
    await fetch('/api/auth/logout', { method: 'POST' })
    router.push('/auth/login')
  }

  const tokenUsagePercentage = Math.round((stats.tokensUsed / stats.tokensLimit) * 100)

  const menuItems = [
    { href: '/dashboard', icon: BarChart3, label: 'Dashboard' },
    { href: '/pages', icon: FileText, label: 'Pagine' },
    { href: '/generations', icon: Sparkles, label: 'Generazioni' },
    { href: '/prompts', icon: Clock, label: 'Prompt' },
    { href: '/settings', icon: Settings, label: 'Impostazioni' }
  ]

  return (
    <div className="flex h-screen bg-gray-50 dark:bg-gray-900">
      {/* Sidebar */}
      <div className={`fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 transform transition-transform lg:relative lg:translate-x-0 ${
        sidebarOpen ? 'translate-x-0' : '-translate-x-full'
      }`}>
        <div className="flex items-center justify-between h-16 px-6 border-b dark:border-gray-700">
          <h1 className="text-xl font-bold text-gray-900 dark:text-white">Ainstein</h1>
          <button
            onClick={() => setSidebarOpen(false)}
            className="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400"
          >
            <X size={24} />
          </button>
        </div>

        <nav className="p-4">
          {menuItems.map(item => (
            <Link
              key={item.href}
              href={item.href}
              className="flex items-center px-4 py-3 mb-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
              <item.icon size={20} className="mr-3" />
              {item.label}
            </Link>
          ))}
        </nav>

        {/* Token Usage Widget */}
        <div className="absolute bottom-0 left-0 right-0 p-4">
          <div className="bg-gray-100 dark:bg-gray-700 rounded-lg p-4">
            <div className="flex justify-between items-center mb-2">
              <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Token Usage</span>
              <span className="text-sm font-bold text-gray-900 dark:text-white">
                {tokenUsagePercentage}%
              </span>
            </div>
            <div className="w-full bg-gray-300 dark:bg-gray-600 rounded-full h-2">
              <div
                className={`h-2 rounded-full transition-all ${
                  tokenUsagePercentage >= 90 ? 'bg-red-500' :
                  tokenUsagePercentage >= 70 ? 'bg-yellow-500' :
                  'bg-green-500'
                }`}
                style={{ width: `${tokenUsagePercentage}%` }}
              />
            </div>
            <p className="text-xs text-gray-600 dark:text-gray-400 mt-2">
              {stats.tokensUsed.toLocaleString()} / {stats.tokensLimit.toLocaleString()} tokens
            </p>
          </div>
        </div>
      </div>

      {/* Overlay for mobile */}
      {sidebarOpen && (
        <div
          className="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      {/* Main Content */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Header */}
        <header className="bg-white dark:bg-gray-800 shadow-sm border-b dark:border-gray-700">
          <div className="flex items-center justify-between h-16 px-6">
            <button
              onClick={() => setSidebarOpen(true)}
              className="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400"
            >
              <Menu size={24} />
            </button>

            <h2 className="text-xl font-semibold text-gray-800 dark:text-white">
              Dashboard
            </h2>

            <div className="flex items-center space-x-4">
              <span className="text-sm text-gray-600 dark:text-gray-400">
                {tenant?.name}
              </span>
              <button
                onClick={handleLogout}
                className="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
              >
                <LogOut size={20} />
              </button>
            </div>
          </div>
        </header>

        {/* Dashboard Content */}
        <main className="flex-1 overflow-y-auto p-6">
          {/* Stats Cards */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div className="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Pagine Totali</p>
                  <p className="text-2xl font-bold text-gray-900 dark:text-white">
                    {stats.totalPages}
                  </p>
                </div>
                <FileText className="text-blue-500" size={32} />
              </div>
            </div>

            <div className="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Generazioni Oggi</p>
                  <p className="text-2xl font-bold text-gray-900 dark:text-white">
                    {stats.generationsToday}
                  </p>
                </div>
                <Sparkles className="text-purple-500" size={32} />
              </div>
            </div>

            <div className="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Token Utilizzati</p>
                  <p className="text-2xl font-bold text-gray-900 dark:text-white">
                    {stats.tokensUsed.toLocaleString()}
                  </p>
                </div>
                <Clock className="text-green-500" size={32} />
              </div>
            </div>

            <div className="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Success Rate</p>
                  <p className="text-2xl font-bold text-gray-900 dark:text-white">
                    {stats.successRate}%
                  </p>
                </div>
                <TrendingUp className="text-orange-500" size={32} />
              </div>
            </div>
          </div>

          {/* Quick Actions */}
          <div className="bg-white dark:bg-gray-800 rounded-lg p-6 shadow mb-8">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Azioni Rapide
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <Link
                href="/pages/new"
                className="flex items-center justify-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
              >
                <Plus size={20} className="mr-2" />
                Aggiungi Pagina
              </Link>
              <Link
                href="/generations/new"
                className="flex items-center justify-center px-4 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors"
              >
                <Sparkles size={20} className="mr-2" />
                Genera Contenuti
              </Link>
              <button
                onClick={loadDashboardData}
                className="flex items-center justify-center px-4 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors"
              >
                <RefreshCw size={20} className="mr-2" />
                Aggiorna Dati
              </button>
            </div>
          </div>

          {/* Recent Activity */}
          <div className="bg-white dark:bg-gray-800 rounded-lg p-6 shadow">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
              Attività Recente
            </h3>
            <div className="text-center py-12 text-gray-500 dark:text-gray-400">
              <Clock size={48} className="mx-auto mb-4 opacity-50" />
              <p>Nessuna attività recente</p>
              <p className="text-sm mt-2">Le tue generazioni appariranno qui</p>
            </div>
          </div>
        </main>
      </div>
    </div>
  )
}