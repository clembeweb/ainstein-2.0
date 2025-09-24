'use client'

import { useState, useEffect } from 'react'
import { User, Building, CreditCard, Key, Save } from 'lucide-react'

export default function SettingsPage() {
  const [loading, setLoading] = useState(false)
  const [message, setMessage] = useState('')

  const handleSave = async (section: string) => {
    setLoading(true)
    setMessage('')

    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1000))
      setMessage(`${section} salvate con successo!`)
    } catch (error) {
      setMessage('Errore durante il salvataggio')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-900 mb-2">Impostazioni</h1>
        <p className="text-gray-600">Gestisci le impostazioni del tuo account e della tua organizzazione</p>
      </div>

      {/* Message */}
      {message && (
        <div className={`mb-6 p-4 rounded-md ${
          message.includes('Errore')
            ? 'bg-red-50 text-red-700 border border-red-200'
            : 'bg-green-50 text-green-700 border border-green-200'
        }`}>
          {message}
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Profile Settings */}
        <div className="bg-white p-6 rounded-lg border border-gray-200">
          <div className="flex items-center space-x-2 mb-4">
            <User className="w-5 h-5 text-blue-600" />
            <h2 className="text-lg font-medium text-gray-900">Profilo Utente</h2>
          </div>

          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Nome
              </label>
              <input
                type="text"
                defaultValue="Mario Rossi"
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Email
              </label>
              <input
                type="email"
                defaultValue="mario@example.com"
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Ruolo
              </label>
              <select
                disabled
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-500"
              >
                <option>Membro</option>
              </select>
            </div>

            <button
              onClick={() => handleSave('Impostazioni profilo')}
              disabled={loading}
              className="flex items-center justify-center w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
            >
              <Save className="w-4 h-4 mr-2" />
              {loading ? 'Salvando...' : 'Salva Profilo'}
            </button>
          </div>
        </div>

        {/* Organization Settings */}
        <div className="bg-white p-6 rounded-lg border border-gray-200">
          <div className="flex items-center space-x-2 mb-4">
            <Building className="w-5 h-5 text-green-600" />
            <h2 className="text-lg font-medium text-gray-900">Organizzazione</h2>
          </div>

          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Nome Organizzazione
              </label>
              <input
                type="text"
                defaultValue="La mia Azienda"
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Sottominio
              </label>
              <div className="flex">
                <input
                  type="text"
                  defaultValue="mia-azienda"
                  className="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:ring-blue-500 focus:border-blue-500"
                />
                <span className="px-3 py-2 bg-gray-50 border border-l-0 border-gray-300 rounded-r-md text-gray-500">
                  .ainstein.com
                </span>
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Piano
              </label>
              <select
                disabled
                className="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-500"
              >
                <option>Starter</option>
              </select>
            </div>

            <button
              onClick={() => handleSave('Impostazioni organizzazione')}
              disabled={loading}
              className="flex items-center justify-center w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50"
            >
              <Save className="w-4 h-4 mr-2" />
              {loading ? 'Salvando...' : 'Salva Organizzazione'}
            </button>
          </div>
        </div>

        {/* API Keys */}
        <div className="bg-white p-6 rounded-lg border border-gray-200">
          <div className="flex items-center space-x-2 mb-4">
            <Key className="w-5 h-5 text-purple-600" />
            <h2 className="text-lg font-medium text-gray-900">Chiavi API</h2>
          </div>

          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                OpenAI API Key
              </label>
              <input
                type="password"
                placeholder="sk-..."
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                Google Search Console
              </label>
              <div className="flex space-x-2">
                <button className="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                  Configura GSC
                </button>
                <span className="px-3 py-2 text-sm text-gray-500">Non configurato</span>
              </div>
            </div>

            <button
              onClick={() => handleSave('Chiavi API')}
              disabled={loading}
              className="flex items-center justify-center w-full px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 disabled:opacity-50"
            >
              <Save className="w-4 h-4 mr-2" />
              {loading ? 'Salvando...' : 'Salva Chiavi'}
            </button>
          </div>
        </div>

        {/* Billing */}
        <div className="bg-white p-6 rounded-lg border border-gray-200">
          <div className="flex items-center space-x-2 mb-4">
            <CreditCard className="w-5 h-5 text-yellow-600" />
            <h2 className="text-lg font-medium text-gray-900">Fatturazione</h2>
          </div>

          <div className="space-y-4">
            <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-yellow-800">Piano Starter</p>
                  <p className="text-xs text-yellow-600">10.000 token/mese inclusi</p>
                </div>
                <span className="text-lg font-bold text-yellow-800">€0/mese</span>
              </div>
            </div>

            <div className="space-y-2">
              <div className="flex justify-between text-sm">
                <span className="text-gray-600">Token utilizzati questo mese:</span>
                <span className="font-medium">2.450 / 10.000</span>
              </div>
              <div className="w-full bg-gray-200 rounded-full h-2">
                <div className="bg-yellow-500 h-2 rounded-full" style={{ width: '24.5%' }}></div>
              </div>
            </div>

            <button className="w-full px-4 py-2 border border-yellow-600 text-yellow-700 rounded-md hover:bg-yellow-50">
              Aggiorna Piano
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}