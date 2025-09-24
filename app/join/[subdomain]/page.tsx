'use client'

import { useState, useEffect } from 'react'
import { useRouter, useParams } from 'next/navigation'
import Link from 'next/link'
import { Eye, EyeOff, Loader2, CheckCircle } from 'lucide-react'

export default function JoinTenantPage() {
  const router = useRouter()
  const params = useParams()
  const subdomain = params.subdomain as string

  const [isLoading, setIsLoading] = useState(false)
  const [showPassword, setShowPassword] = useState(false)
  const [error, setError] = useState('')
  const [tenantInfo, setTenantInfo] = useState<any>(null)
  const [tenantLoading, setTenantLoading] = useState(true)

  const [formData, setFormData] = useState({
    name: '',
    email: '',
    password: '',
    confirmPassword: ''
  })

  useEffect(() => {
    loadTenantInfo()
  }, [subdomain])

  const loadTenantInfo = async () => {
    try {
      const response = await fetch(`/api/tenant/info/${subdomain}`)
      if (response.ok) {
        const data = await response.json()
        setTenantInfo(data)
      } else {
        setError('Organizzazione non trovata')
      }
    } catch (error) {
      setError('Errore nel caricamento delle informazioni')
    } finally {
      setTenantLoading(false)
    }
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError('')
    setIsLoading(true)

    // Validate form
    if (formData.password !== formData.confirmPassword) {
      setError('Le password non coincidono')
      setIsLoading(false)
      return
    }

    if (formData.password.length < 8) {
      setError('La password deve essere di almeno 8 caratteri')
      setIsLoading(false)
      return
    }

    try {
      const response = await fetch('/api/tenant/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          ...formData,
          tenantSubdomain: subdomain
        })
      })

      if (!response.ok) {
        const errorData = await response.json()
        setError(errorData.error || 'Errore durante la registrazione')
        return
      }

      const data = await response.json()
      console.log('Registration successful:', data)

      // Redirect to tenant dashboard
      setTimeout(() => {
        window.location.replace('/dashboard')
      }, 500)

    } catch (error) {
      console.error('Registration error:', error)
      setError('Errore di connessione')
    } finally {
      setIsLoading(false)
    }
  }

  if (tenantLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
        <div className="text-center">
          <Loader2 className="animate-spin mx-auto mb-4" size={40} />
          <p className="text-gray-600 dark:text-gray-400">Caricamento...</p>
        </div>
      </div>
    )
  }

  if (!tenantInfo) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800">
        <div className="w-full max-w-md">
          <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8 text-center">
            <div className="text-red-500 mb-4">
              <svg className="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.232 15.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
            </div>
            <h1 className="text-xl font-bold text-gray-900 dark:text-white mb-2">
              Organizzazione non trovata
            </h1>
            <p className="text-gray-600 dark:text-gray-400 mb-6">
              {error || 'L\'organizzazione che stai cercando non esiste o non è più attiva.'}
            </p>
            <Link
              href="/auth/login"
              className="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
            >
              Torna al Login
            </Link>
          </div>
        </div>
      </div>
    )
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800 p-4">
      <div className="w-full max-w-md">
        <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl p-8">
          {/* Tenant Info */}
          <div className="text-center mb-8">
            <div className="flex justify-center mb-4">
              {tenantInfo.logo ? (
                <img src={tenantInfo.logo} alt={tenantInfo.name} className="h-12 w-auto" />
              ) : (
                <div className="h-12 w-12 bg-blue-600 rounded-lg flex items-center justify-center">
                  <span className="text-white font-bold text-lg">
                    {tenantInfo.name?.charAt(0) || 'T'}
                  </span>
                </div>
              )}
            </div>
            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
              Unisciti a {tenantInfo.name}
            </h1>
            <p className="text-gray-600 dark:text-gray-400 mt-2">
              Crea il tuo account per accedere alla piattaforma
            </p>
          </div>

          {/* Error Message */}
          {error && (
            <div className="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-600 dark:text-red-400 text-sm">
              {error}
            </div>
          )}

          {/* Registration Form */}
          <form onSubmit={handleSubmit} className="space-y-5">
            <div>
              <label htmlFor="name" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Nome Completo
              </label>
              <input
                id="name"
                type="text"
                required
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent dark:bg-gray-700 dark:text-white transition-colors"
                placeholder="Mario Rossi"
              />
            </div>

            <div>
              <label htmlFor="email" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Email
              </label>
              <input
                id="email"
                type="email"
                required
                value={formData.email}
                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent dark:bg-gray-700 dark:text-white transition-colors"
                placeholder="mario@email.com"
              />
            </div>

            <div>
              <label htmlFor="password" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Password
              </label>
              <div className="relative">
                <input
                  id="password"
                  type={showPassword ? 'text' : 'password'}
                  required
                  value={formData.password}
                  onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                  className="w-full px-4 py-3 pr-12 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent dark:bg-gray-700 dark:text-white transition-colors"
                  placeholder="••••••••"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                  {showPassword ? <EyeOff size={20} /> : <Eye size={20} />}
                </button>
              </div>
            </div>

            <div>
              <label htmlFor="confirmPassword" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Conferma Password
              </label>
              <input
                id="confirmPassword"
                type="password"
                required
                value={formData.confirmPassword}
                onChange={(e) => setFormData({ ...formData, confirmPassword: e.target.value })}
                className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent dark:bg-gray-700 dark:text-white transition-colors"
                placeholder="••••••••"
              />
            </div>

            <button
              type="submit"
              disabled={isLoading}
              className="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
            >
              {isLoading ? (
                <>
                  <Loader2 className="animate-spin mr-2" size={20} />
                  Registrazione in corso...
                </>
              ) : (
                <>
                  <CheckCircle className="mr-2" size={20} />
                  Crea Account
                </>
              )}
            </button>
          </form>

          {/* Login Link */}
          <div className="mt-6 text-center">
            <p className="text-sm text-gray-600 dark:text-gray-400">
              Hai già un account?{' '}
              <Link href="/auth/login" className="text-blue-600 hover:text-blue-500 dark:text-blue-400 font-medium">
                Accedi
              </Link>
            </p>
          </div>

          {/* Tenant Info Footer */}
          <div className="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700 text-center">
            <p className="text-xs text-gray-500 dark:text-gray-400">
              Registrandoti accetti i termini di servizio di {tenantInfo.name}
            </p>
          </div>
        </div>
      </div>
    </div>
  )
}