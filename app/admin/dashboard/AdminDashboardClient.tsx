'use client'

import { useState, useEffect } from 'react'
import { useRouter } from 'next/navigation'
import { Settings, Key, Database, CreditCard, Mail, Cloud, Save, Loader2, LogOut } from 'lucide-react'

export default function AdminDashboardClient() {
  const router = useRouter()
  const [activeTab, setActiveTab] = useState('overview')
  const [isLoading, setIsLoading] = useState(true)
  const [isSaving, setIsSaving] = useState(false)
  const [message, setMessage] = useState('')
  const [mounted, setMounted] = useState(false)

  // Platform stats
  const [platformStats, setPlatformStats] = useState({
    totalTenants: 0,
    activeTenants: 0,
    totalUsers: 0,
    totalPages: 0,
    totalGenerations: 0,
    totalTokensUsed: 0
  })

  // Tenants data
  const [tenants, setTenants] = useState([])
  const [tenantsLoading, setTenantsLoading] = useState(false)
  const [showCreateTenant, setShowCreateTenant] = useState(false)

  const [settings, setSettings] = useState({
    openaiApiKey: '',
    openaiModel: 'gpt-4o',
    stripeSecretKey: '',
    stripeWebhook: '',
    smtpHost: '',
    smtpPort: 587,
    smtpUser: '',
    smtpPass: '',
    googleClientId: '',
    googleClientSecret: ''
  })

  useEffect(() => {
    setMounted(true)
    loadSettings().catch(console.error)
    loadPlatformStats().catch(console.error)
  }, [])

  const loadPlatformStats = async () => {
    try {
      const response = await fetch('/api/admin/stats')
      if (response.ok) {
        const data = await response.json()
        setPlatformStats(data.totalStats)
        setTenants(data.tenants)
      }
    } catch (error) {
      console.error('Error loading platform stats:', error)
    }
  }

  const loadTenants = async () => {
    setTenantsLoading(true)
    try {
      const response = await fetch('/api/tenants')
      if (response.ok) {
        const data = await response.json()
        setTenants(data)
      }
    } catch (error) {
      console.error('Error loading tenants:', error)
    } finally {
      setTenantsLoading(false)
    }
  }

  const loadSettings = async () => {
    setIsLoading(true)
    try {
      const response = await fetch('/api/admin/settings')
      if (response.ok) {
        const data = await response.json()
        setSettings(prev => ({
          ...prev,
          ...data,
          openaiApiKey: data.openaiApiKey || '',
          stripeSecretKey: data.stripeSecretKey || '',
          smtpPort: data.smtpPort || 587
        }))
      }
    } catch (error) {
      console.error('Error loading settings:', error)
    } finally {
      setIsLoading(false)
    }
  }

  const handleSave = async () => {
    setIsSaving(true)
    setMessage('')

    try {
      const response = await fetch('/api/admin/settings', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(settings)
      })

      if (response.ok) {
        setMessage('Impostazioni salvate con successo!')
        setTimeout(() => setMessage(''), 3000)
      } else {
        setMessage('Errore nel salvataggio delle impostazioni')
      }
    } catch (error) {
      setMessage('Errore di connessione')
    } finally {
      setIsSaving(false)
    }
  }

  const handleLogout = async () => {
    await fetch('/api/auth/logout', { method: 'POST' })
    router.push('/auth/login')
  }

  const tabs = [
    { id: 'overview', label: 'Overview', icon: Settings },
    { id: 'tenants', label: 'Tenants', icon: Database },
    { id: 'openai', label: 'OpenAI', icon: Key },
    { id: 'stripe', label: 'Stripe', icon: CreditCard },
    { id: 'email', label: 'Email', icon: Mail },
    { id: 'google', label: 'Google', icon: Cloud }
  ]

  if (!mounted || isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
        <div className="text-center">
          <Loader2 className="animate-spin mx-auto mb-4" size={40} />
          <p className="text-gray-600 dark:text-gray-400">Caricamento dashboard admin...</p>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      {/* Header */}
      <div className="bg-white dark:bg-gray-800 shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center py-6">
            <div className="flex items-center">
              <Settings className="mr-3" size={28} />
              <div>
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                  Pannello Super Admin
                </h1>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  Gestione impostazioni piattaforma Ainstein
                </p>
              </div>
            </div>
            <button
              onClick={handleLogout}
              className="flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors"
            >
              <LogOut size={18} className="mr-2" />
              Logout
            </button>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Success/Error Message */}
        {message && (
          <div className={`mb-6 p-4 rounded-lg ${
            message.includes('successo')
              ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800'
              : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800'
          }`}>
            {message}
          </div>
        )}

        <div className="bg-white dark:bg-gray-800 rounded-lg shadow">
          {/* Tabs */}
          <div className="border-b border-gray-200 dark:border-gray-700">
            <nav className="flex space-x-8 px-6" aria-label="Tabs">
              {tabs.map(tab => (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id)}
                  className={`
                    py-4 px-1 border-b-2 font-medium text-sm flex items-center
                    ${activeTab === tab.id
                      ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                    }
                  `}
                >
                  <tab.icon size={18} className="mr-2" />
                  {tab.label}
                </button>
              ))}
            </nav>
          </div>

          {/* Tab Content */}
          <div className="p-6">
            {activeTab === 'overview' && (
              <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                  Panoramica Piattaforma
                </h3>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  <div className="bg-blue-50 dark:bg-blue-900/20 p-6 rounded-lg">
                    <div className="flex items-center">
                      <div className="flex-shrink-0">
                        <Database className="h-8 w-8 text-blue-600" />
                      </div>
                      <div className="ml-4">
                        <p className="text-sm font-medium text-blue-600">Tenant Totali</p>
                        <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                          {platformStats.totalTenants}
                        </p>
                        <p className="text-xs text-gray-500">
                          {platformStats.activeTenants} attivi
                        </p>
                      </div>
                    </div>
                  </div>

                  <div className="bg-green-50 dark:bg-green-900/20 p-6 rounded-lg">
                    <div className="flex items-center">
                      <div className="flex-shrink-0">
                        <Settings className="h-8 w-8 text-green-600" />
                      </div>
                      <div className="ml-4">
                        <p className="text-sm font-medium text-green-600">Utenti Totali</p>
                        <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                          {platformStats.totalUsers}
                        </p>
                      </div>
                    </div>
                  </div>

                  <div className="bg-purple-50 dark:bg-purple-900/20 p-6 rounded-lg">
                    <div className="flex items-center">
                      <div className="flex-shrink-0">
                        <Key className="h-8 w-8 text-purple-600" />
                      </div>
                      <div className="ml-4">
                        <p className="text-sm font-medium text-purple-600">Generazioni</p>
                        <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                          {platformStats.totalGenerations.toLocaleString()}
                        </p>
                      </div>
                    </div>
                  </div>

                  <div className="bg-yellow-50 dark:bg-yellow-900/20 p-6 rounded-lg">
                    <div className="flex items-center">
                      <div className="flex-shrink-0">
                        <Mail className="h-8 w-8 text-yellow-600" />
                      </div>
                      <div className="ml-4">
                        <p className="text-sm font-medium text-yellow-600">Pagine Totali</p>
                        <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                          {platformStats.totalPages.toLocaleString()}
                        </p>
                      </div>
                    </div>
                  </div>

                  <div className="bg-red-50 dark:bg-red-900/20 p-6 rounded-lg">
                    <div className="flex items-center">
                      <div className="flex-shrink-0">
                        <CreditCard className="h-8 w-8 text-red-600" />
                      </div>
                      <div className="ml-4">
                        <p className="text-sm font-medium text-red-600">Token Utilizzati</p>
                        <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                          {platformStats.totalTokensUsed.toLocaleString()}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'tenants' && (
              <div className="space-y-6">
                <div className="flex justify-between items-center">
                  <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                    Gestione Tenant
                  </h3>
                  <button
                    onClick={() => setShowCreateTenant(true)}
                    className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium"
                  >
                    Nuovo Tenant
                  </button>
                </div>

                {/* Tenants Table */}
                <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                  <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h4 className="text-sm font-medium text-gray-900 dark:text-white">
                      Tenant Attivi ({tenants.length})
                    </h4>
                  </div>

                  <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                      <thead className="bg-gray-50 dark:bg-gray-800">
                        <tr>
                          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Nome
                          </th>
                          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Subdomain
                          </th>
                          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Piano
                          </th>
                          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Token Usage
                          </th>
                          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Utenti
                          </th>
                          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status
                          </th>
                          <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Azioni
                          </th>
                        </tr>
                      </thead>
                      <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        {tenants.map((tenant) => (
                          <tr key={tenant.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td className="px-6 py-4 whitespace-nowrap">
                              <div>
                                <div className="text-sm font-medium text-gray-900 dark:text-white">
                                  {tenant.name}
                                </div>
                                <div className="text-sm text-gray-500">
                                  {tenant.domain || `${tenant.subdomain}.ainstein.app`}
                                </div>
                              </div>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                              <span className="text-sm text-gray-900 dark:text-white">
                                {tenant.subdomain}
                              </span>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                              <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                {tenant.planType}
                              </span>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                              {tenant.tokensUsedCurrent?.toLocaleString()} / {tenant.tokensMonthlyLimit?.toLocaleString()}
                              <div className="w-full bg-gray-200 rounded-full h-1 mt-1">
                                <div
                                  className="bg-blue-600 h-1 rounded-full"
                                  style={{ width: `${Math.min((tenant.tokensUsedCurrent / tenant.tokensMonthlyLimit) * 100, 100)}%` }}
                                ></div>
                              </div>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                              {tenant._count?.users || 0}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                              <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                                tenant.status === 'active'
                                  ? 'bg-green-100 text-green-800'
                                  : 'bg-red-100 text-red-800'
                              }`}>
                                {tenant.status}
                              </span>
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                              <button className="text-blue-600 hover:text-blue-900 mr-3">
                                Modifica
                              </button>
                              <button className="text-red-600 hover:text-red-900">
                                Disattiva
                              </button>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            )}

            {activeTab === 'openai' && (
              <div className="space-y-6">
                <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                  Configurazione OpenAI
                </h3>

                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    API Key
                  </label>
                  <input
                    type="password"
                    value={settings.openaiApiKey}
                    onChange={(e) => setSettings({ ...settings, openaiApiKey: e.target.value })}
                    placeholder="sk-..."
                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  />
                  <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    La chiave API di OpenAI per generare contenuti
                  </p>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Modello Default
                  </label>
                  <select
                    value={settings.openaiModel}
                    onChange={(e) => setSettings({ ...settings, openaiModel: e.target.value })}
                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                  >
                    <option value="gpt-4o">GPT-4o (Consigliato)</option>
                    <option value="gpt-4">GPT-4</option>
                    <option value="gpt-4-turbo">GPT-4 Turbo</option>
                    <option value="gpt-4o-mini">GPT-4o Mini</option>
                    <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                  </select>
                </div>
              </div>
            )}
          </div>

          {/* Save Button */}
          <div className="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 rounded-b-lg">
            <button
              onClick={handleSave}
              disabled={isSaving}
              className="flex items-center px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isSaving ? (
                <>
                  <Loader2 className="animate-spin mr-2" size={18} />
                  Salvataggio...
                </>
              ) : (
                <>
                  <Save size={18} className="mr-2" />
                  Salva Impostazioni
                </>
              )}
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}