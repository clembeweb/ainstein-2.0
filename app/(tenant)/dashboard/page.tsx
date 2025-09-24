'use client'

import { useState, useEffect } from 'react'
import { FileText, Sparkles, CheckCircle, Clock, AlertTriangle, TrendingUp } from 'lucide-react'

interface Stats {
  totalPages: number
  totalGenerations: number
  completedGenerations: number
  pendingGenerations: number
  failedGenerations: number
  tokensUsed: number
  tokensLimit: number
}

export default function TenantDashboard() {
  const [stats, setStats] = useState<Stats | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    loadStats()
  }, [])

  const loadStats = async () => {
    try {
      // Load pages stats
      const pagesResponse = await fetch('/api/pages?limit=1')
      const pagesData = await pagesResponse.json()

      // Load generations stats
      const generationsResponse = await fetch('/api/generations?limit=1')
      const generationsData = await generationsResponse.json()

      // Calculate stats from actual data
      const totalPages = pagesData.pagination?.totalCount || 0
      const totalGenerations = generationsData.pagination?.totalCount || 0

      // Load detailed generations for status counts
      const allGenerationsResponse = await fetch('/api/generations?limit=1000')
      const allGenerationsData = await allGenerationsResponse.json()
      const generations = allGenerationsData.generations || []

      const completedGenerations = generations.filter((g: any) => g.status === 'completed').length
      const pendingGenerations = generations.filter((g: any) => g.status === 'pending' || g.status === 'processing').length
      const failedGenerations = generations.filter((g: any) => g.status === 'failed').length

      const tokensUsed = generations.reduce((total: number, g: any) => total + (g.tokensUsed || 0), 0)

      setStats({
        totalPages,
        totalGenerations,
        completedGenerations,
        pendingGenerations,
        failedGenerations,
        tokensUsed,
        tokensLimit: 10000 // Default limit
      })
    } catch (error) {
      console.error('Error loading stats:', error)
    } finally {
      setLoading(false)
    }
  }

  if (loading) {
    return (
      <div className="p-6">
        <div className="animate-pulse">
          <div className="h-8 bg-gray-200 rounded w-64 mb-4"></div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {Array.from({ length: 4 }).map((_, i) => (
              <div key={i} className="bg-white p-6 rounded-lg border border-gray-200">
                <div className="h-4 bg-gray-200 rounded w-24 mb-2"></div>
                <div className="h-8 bg-gray-200 rounded w-16"></div>
              </div>
            ))}
          </div>
        </div>
      </div>
    )
  }

  const tokenUsagePercentage = stats ? Math.round((stats.tokensUsed / stats.tokensLimit) * 100) : 0

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-900 mb-2">Dashboard</h1>
        <p className="text-gray-600">Panoramica delle tue attività di generazione contenuti</p>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div className="bg-white p-6 rounded-lg border border-gray-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600">Pagine Totali</p>
              <p className="text-2xl font-bold text-gray-900">{stats?.totalPages || 0}</p>
            </div>
            <FileText className="w-8 h-8 text-blue-500" />
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg border border-gray-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600">Generazioni Totali</p>
              <p className="text-2xl font-bold text-gray-900">{stats?.totalGenerations || 0}</p>
            </div>
            <Sparkles className="w-8 h-8 text-purple-500" />
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg border border-gray-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600">Completate</p>
              <p className="text-2xl font-bold text-green-600">{stats?.completedGenerations || 0}</p>
            </div>
            <CheckCircle className="w-8 h-8 text-green-500" />
          </div>
        </div>

        <div className="bg-white p-6 rounded-lg border border-gray-200">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm font-medium text-gray-600">In Attesa</p>
              <p className="text-2xl font-bold text-yellow-600">{stats?.pendingGenerations || 0}</p>
            </div>
            <Clock className="w-8 h-8 text-yellow-500" />
          </div>
        </div>
      </div>

      {/* Token Usage and Quick Actions */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Token Usage */}
        <div className="bg-white p-6 rounded-lg border border-gray-200">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Utilizzo Token</h3>

          <div className="flex items-center justify-between mb-2">
            <span className="text-sm text-gray-600">
              {stats?.tokensUsed.toLocaleString() || 0} / {stats?.tokensLimit.toLocaleString() || 0} token
            </span>
            <span className="text-sm font-medium text-gray-900">
              {tokenUsagePercentage}%
            </span>
          </div>

          <div className="w-full bg-gray-200 rounded-full h-2 mb-4">
            <div
              className={`h-2 rounded-full transition-all duration-300 ${
                tokenUsagePercentage >= 90
                  ? 'bg-red-500'
                  : tokenUsagePercentage >= 70
                  ? 'bg-yellow-500'
                  : 'bg-green-500'
              }`}
              style={{ width: `${Math.min(tokenUsagePercentage, 100)}%` }}
            ></div>
          </div>

          <div className="flex items-center space-x-2">
            {tokenUsagePercentage >= 90 && (
              <>
                <AlertTriangle className="w-4 h-4 text-red-500" />
                <span className="text-sm text-red-600">Limite quasi raggiunto</span>
              </>
            )}
            {tokenUsagePercentage >= 70 && tokenUsagePercentage < 90 && (
              <>
                <TrendingUp className="w-4 h-4 text-yellow-500" />
                <span className="text-sm text-yellow-600">Utilizzo elevato</span>
              </>
            )}
            {tokenUsagePercentage < 70 && (
              <>
                <CheckCircle className="w-4 h-4 text-green-500" />
                <span className="text-sm text-green-600">Utilizzo normale</span>
              </>
            )}
          </div>
        </div>

        {/* Quick Actions */}
        <div className="bg-white p-6 rounded-lg border border-gray-200">
          <h3 className="text-lg font-medium text-gray-900 mb-4">Azioni Rapide</h3>

          <div className="space-y-3">
            <a
              href="/pages"
              className="flex items-center justify-between p-3 bg-blue-50 hover:bg-blue-100 rounded-md transition-colors"
            >
              <div className="flex items-center space-x-3">
                <FileText className="w-5 h-5 text-blue-600" />
                <span className="text-sm font-medium text-blue-900">Gestisci Pagine</span>
              </div>
              <span className="text-blue-600">→</span>
            </a>

            <a
              href="/generations"
              className="flex items-center justify-between p-3 bg-purple-50 hover:bg-purple-100 rounded-md transition-colors"
            >
              <div className="flex items-center space-x-3">
                <Sparkles className="w-5 h-5 text-purple-600" />
                <span className="text-sm font-medium text-purple-900">Genera Contenuti</span>
              </div>
              <span className="text-purple-600">→</span>
            </a>

            <a
              href="/prompts"
              className="flex items-center justify-between p-3 bg-green-50 hover:bg-green-100 rounded-md transition-colors"
            >
              <div className="flex items-center space-x-3">
                <span className="w-5 h-5 text-green-600 font-bold">{'</>'}</span>
                <span className="text-sm font-medium text-green-900">Gestisci Prompt</span>
              </div>
              <span className="text-green-600">→</span>
            </a>
          </div>
        </div>
      </div>

      {/* Status Summary */}
      {stats && stats.failedGenerations > 0 && (
        <div className="mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
          <div className="flex items-center space-x-2">
            <AlertTriangle className="w-5 h-5 text-red-500" />
            <span className="text-sm font-medium text-red-800">
              Attenzione: {stats.failedGenerations} generazioni fallite
            </span>
            <a href="/generations" className="text-sm text-red-600 hover:text-red-800 underline">
              Visualizza dettagli
            </a>
          </div>
        </div>
      )}
    </div>
  )
}