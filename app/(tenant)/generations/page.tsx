'use client'

import { useState, useEffect } from 'react'
import { Plus, Sparkles, Clock, CheckCircle, XCircle, AlertCircle, RefreshCw, Play, Eye, Trash2, Filter } from 'lucide-react'

interface Page {
  id: string
  urlPath: string
  keyword: string | null
  category: string | null
  status: string
}

interface Prompt {
  id: string
  name: string
  alias: string
  description: string | null
  category: string
  variables: string[]
}

interface Generation {
  id: string
  promptType: string
  aiModel: string
  status: string
  tokensUsed: number
  generatedContent: string | null
  metaTitle: string | null
  metaDescription: string | null
  errorMessage: string | null
  createdAt: string
  completedAt: string | null
  page: {
    id: string
    urlPath: string
    keyword: string | null
    category: string | null
  }
  prompt: {
    id: string
    name: string
    alias: string
  }
}

export default function GenerationsPage() {
  const [activeTab, setActiveTab] = useState('generate')
  const [pages, setPages] = useState<Page[]>([])
  const [prompts, setPrompts] = useState<Prompt[]>([])
  const [generations, setGenerations] = useState<Generation[]>([])
  const [loading, setLoading] = useState(false)
  const [generationsLoading, setGenerationsLoading] = useState(false)

  // Generation form state
  const [selectedPages, setSelectedPages] = useState<string[]>([])
  const [selectedPrompt, setSelectedPrompt] = useState('')
  const [aiModel, setAiModel] = useState('gpt-4o')
  const [variables, setVariables] = useState<Record<string, string>>({})
  const [batchSize, setBatchSize] = useState(10)

  // Filters
  const [statusFilter, setStatusFilter] = useState('')
  const [pageFilter, setPageFilter] = useState('')

  useEffect(() => {
    loadPages()
    loadPrompts()
    loadGenerations()
  }, [])

  const loadPages = async () => {
    try {
      const response = await fetch('/api/pages?limit=1000&status=active')
      if (response.ok) {
        const data = await response.json()
        setPages(data.pages || [])
      }
    } catch (error) {
      console.error('Error loading pages:', error)
    }
  }

  const loadPrompts = async () => {
    try {
      const response = await fetch('/api/prompts?includeGlobal=true&limit=100')
      if (response.ok) {
        const data = await response.json()
        setPrompts(data.prompts || [])
      }
    } catch (error) {
      console.error('Error loading prompts:', error)
    }
  }

  const loadGenerations = async () => {
    setGenerationsLoading(true)
    try {
      const params = new URLSearchParams({
        limit: '50',
        sortBy: 'createdAt',
        sortOrder: 'desc'
      })

      if (statusFilter) params.append('status', statusFilter)
      if (pageFilter) params.append('pageId', pageFilter)

      const response = await fetch(`/api/generations?${params}`)
      if (response.ok) {
        const data = await response.json()
        setGenerations(data.generations || [])
      }
    } catch (error) {
      console.error('Error loading generations:', error)
    } finally {
      setGenerationsLoading(false)
    }
  }

  const handleGenerate = async () => {
    if (selectedPages.length === 0 || !selectedPrompt) {
      alert('Seleziona almeno una pagina e un prompt')
      return
    }

    setLoading(true)
    try {
      const response = await fetch('/api/generations', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          pageIds: selectedPages,
          promptAlias: selectedPrompt,
          aiModel,
          variables,
          batchSize
        })
      })

      if (response.ok) {
        const data = await response.json()
        alert(`${data.generations.length} generazioni avviate con successo!`)
        setActiveTab('history')
        loadGenerations()

        // Reset form
        setSelectedPages([])
        setSelectedPrompt('')
        setVariables({})
      } else {
        const error = await response.json()
        alert(`Errore: ${error.error}`)
      }
    } catch (error) {
      console.error('Error generating content:', error)
      alert('Errore durante la generazione')
    } finally {
      setLoading(false)
    }
  }

  const handleRetry = async (generationId: string) => {
    try {
      const response = await fetch(`/api/generations/${generationId}/retry`, {
        method: 'POST'
      })

      if (response.ok) {
        alert('Generazione riavviata')
        loadGenerations()
      } else {
        const error = await response.json()
        alert(`Errore: ${error.error}`)
      }
    } catch (error) {
      console.error('Error retrying generation:', error)
      alert('Errore durante il riavvio')
    }
  }

  const handleDelete = async (generationId: string) => {
    if (!confirm('Sei sicuro di voler eliminare questa generazione?')) return

    try {
      const response = await fetch(`/api/generations/${generationId}`, {
        method: 'DELETE'
      })

      if (response.ok) {
        alert('Generazione eliminata')
        loadGenerations()
      } else {
        const error = await response.json()
        alert(`Errore: ${error.error}`)
      }
    } catch (error) {
      console.error('Error deleting generation:', error)
      alert('Errore durante l\'eliminazione')
    }
  }

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'completed': return 'text-green-600 bg-green-100'
      case 'processing': return 'text-blue-600 bg-blue-100'
      case 'pending': return 'text-yellow-600 bg-yellow-100'
      case 'failed': return 'text-red-600 bg-red-100'
      case 'cancelled': return 'text-gray-600 bg-gray-100'
      default: return 'text-gray-600 bg-gray-100'
    }
  }

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'completed': return <CheckCircle className="w-4 h-4" />
      case 'processing': return <RefreshCw className="w-4 h-4 animate-spin" />
      case 'pending': return <Clock className="w-4 h-4" />
      case 'failed': return <XCircle className="w-4 h-4" />
      case 'cancelled': return <AlertCircle className="w-4 h-4" />
      default: return <Clock className="w-4 h-4" />
    }
  }

  const selectedPromptData = prompts.find(p => p.alias === selectedPrompt)
  const estimatedTokens = selectedPages.length * 800

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900 mb-2">Generazione Contenuti AI</h1>
        <p className="text-gray-600">Genera contenuti ottimizzati per SEO utilizzando l'intelligenza artificiale</p>
      </div>

      {/* Tabs */}
      <div className="mb-6 border-b border-gray-200">
        <nav className="flex space-x-8">
          <button
            onClick={() => setActiveTab('generate')}
            className={`flex items-center py-2 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'generate'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            <Sparkles className="w-4 h-4 mr-2" />
            Genera Contenuti
          </button>
          <button
            onClick={() => setActiveTab('history')}
            className={`flex items-center py-2 px-1 border-b-2 font-medium text-sm ${
              activeTab === 'history'
                ? 'border-blue-500 text-blue-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            }`}
          >
            <Clock className="w-4 h-4 mr-2" />
            Storico
          </button>
        </nav>
      </div>

      {/* Generate Tab */}
      {activeTab === 'generate' && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Generation Form */}
          <div className="lg:col-span-2 space-y-6">
            <div className="bg-white rounded-lg border border-gray-200 p-6">
              <h2 className="text-lg font-medium text-gray-900 mb-4">Configurazione Generazione</h2>

              {/* Page Selection */}
              <div className="mb-6">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Pagine da Processare *
                </label>
                <div className="space-y-2 max-h-40 overflow-y-auto border border-gray-300 rounded-md p-3">
                  {pages.map((page) => (
                    <div key={page.id} className="flex items-center">
                      <input
                        type="checkbox"
                        id={`page-${page.id}`}
                        checked={selectedPages.includes(page.id)}
                        onChange={(e) => {
                          if (e.target.checked) {
                            setSelectedPages([...selectedPages, page.id])
                          } else {
                            setSelectedPages(selectedPages.filter(id => id !== page.id))
                          }
                        }}
                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                      />
                      <label htmlFor={`page-${page.id}`} className="ml-3 text-sm text-gray-900 flex-1">
                        <span className="font-medium">{page.urlPath}</span>
                        {page.keyword && <span className="text-gray-500 ml-2">({page.keyword})</span>}
                      </label>
                    </div>
                  ))}
                </div>
                {selectedPages.length > 0 && (
                  <p className="text-sm text-gray-500 mt-2">
                    {selectedPages.length} pagine selezionate
                  </p>
                )}
              </div>

              {/* Prompt Selection */}
              <div className="mb-6">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Tipo di Prompt *
                </label>
                <select
                  value={selectedPrompt}
                  onChange={(e) => setSelectedPrompt(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="">Seleziona un prompt</option>
                  {prompts.map((prompt) => (
                    <option key={prompt.id} value={prompt.alias}>
                      {prompt.name} {!prompt.tenantId && '(Globale)'}
                    </option>
                  ))}
                </select>
                {selectedPromptData && (
                  <p className="text-sm text-gray-500 mt-1">
                    {selectedPromptData.description}
                  </p>
                )}
              </div>

              {/* AI Model */}
              <div className="mb-6">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Modello AI
                </label>
                <select
                  value={aiModel}
                  onChange={(e) => setAiModel(e.target.value)}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="gpt-4o">GPT-4o (Consigliato)</option>
                  <option value="gpt-4">GPT-4</option>
                  <option value="gpt-4-turbo">GPT-4 Turbo</option>
                  <option value="gpt-4o-mini">GPT-4o Mini (Economico)</option>
                </select>
              </div>

              {/* Variables */}
              {selectedPromptData && selectedPromptData.variables.length > 0 && (
                <div className="mb-6">
                  <label className="block text-sm font-medium text-gray-700 mb-2">
                    Variabili Personalizzate
                  </label>
                  <div className="space-y-3">
                    {selectedPromptData.variables.map((variable) => (
                      <div key={variable}>
                        <label className="block text-xs font-medium text-gray-600 mb-1">
                          {variable}
                        </label>
                        <input
                          type="text"
                          value={variables[variable] || ''}
                          onChange={(e) => setVariables({ ...variables, [variable]: e.target.value })}
                          placeholder={`Inserisci ${variable}`}
                          className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm"
                        />
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Batch Size */}
              <div className="mb-6">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                  Batch Size
                </label>
                <input
                  type="number"
                  min="1"
                  max="50"
                  value={batchSize}
                  onChange={(e) => setBatchSize(parseInt(e.target.value))}
                  className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                />
                <p className="text-xs text-gray-500 mt-1">
                  Numero di pagine da processare simultaneamente (max 50)
                </p>
              </div>

              {/* Submit Button */}
              <button
                onClick={handleGenerate}
                disabled={loading || selectedPages.length === 0 || !selectedPrompt}
                className="w-full flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {loading ? (
                  <>
                    <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                    Generazione in corso...
                  </>
                ) : (
                  <>
                    <Play className="w-4 h-4 mr-2" />
                    Avvia Generazione
                  </>
                )}
              </button>
            </div>
          </div>

          {/* Summary Panel */}
          <div className="space-y-6">
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <h3 className="text-lg font-medium text-blue-900 mb-3">Riepilogo</h3>
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-blue-700">Pagine selezionate:</span>
                  <span className="font-medium text-blue-900">{selectedPages.length}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-blue-700">Token stimati:</span>
                  <span className="font-medium text-blue-900">~{estimatedTokens.toLocaleString()}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-blue-700">Costo stimato:</span>
                  <span className="font-medium text-blue-900">~${((estimatedTokens * 0.002) / 1000).toFixed(3)}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* History Tab */}
      {activeTab === 'history' && (
        <div className="space-y-6">
          {/* Filters */}
          <div className="bg-white rounded-lg border border-gray-200 p-4">
            <div className="flex items-center space-x-4">
              <div className="flex-1">
                <select
                  value={statusFilter}
                  onChange={(e) => {
                    setStatusFilter(e.target.value)
                    setTimeout(() => loadGenerations(), 100)
                  }}
                  className="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="">Tutti gli stati</option>
                  <option value="pending">In attesa</option>
                  <option value="processing">In elaborazione</option>
                  <option value="completed">Completato</option>
                  <option value="failed">Fallito</option>
                  <option value="cancelled">Annullato</option>
                </select>
              </div>
              <div className="flex-1">
                <select
                  value={pageFilter}
                  onChange={(e) => {
                    setPageFilter(e.target.value)
                    setTimeout(() => loadGenerations(), 100)
                  }}
                  className="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="">Tutte le pagine</option>
                  {pages.map((page) => (
                    <option key={page.id} value={page.id}>
                      {page.urlPath}
                    </option>
                  ))}
                </select>
              </div>
              <button
                onClick={loadGenerations}
                className="flex items-center px-4 py-2 text-gray-600 hover:text-gray-800"
              >
                <RefreshCw className="w-4 h-4" />
              </button>
            </div>
          </div>

          {/* Generations List */}
          <div className="bg-white rounded-lg border border-gray-200">
            {generationsLoading ? (
              <div className="p-8 text-center">
                <RefreshCw className="w-8 h-8 animate-spin mx-auto mb-4 text-gray-400" />
                <p className="text-gray-500">Caricamento generazioni...</p>
              </div>
            ) : generations.length === 0 ? (
              <div className="p-8 text-center">
                <Sparkles className="w-12 h-12 mx-auto mb-4 text-gray-300" />
                <p className="text-gray-500">Nessuna generazione trovata</p>
              </div>
            ) : (
              <div className="divide-y divide-gray-200">
                {generations.map((generation) => (
                  <div key={generation.id} className="p-4 hover:bg-gray-50">
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <div className="flex items-center space-x-3">
                          <div className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(generation.status)}`}>
                            {getStatusIcon(generation.status)}
                            <span className="ml-1 capitalize">{generation.status}</span>
                          </div>
                          <span className="text-sm text-gray-500">{generation.prompt.name}</span>
                          <span className="text-sm text-gray-400">{generation.aiModel}</span>
                        </div>

                        <div className="mt-2">
                          <p className="text-sm font-medium text-gray-900">
                            {generation.page.urlPath}
                          </p>
                          {generation.page.keyword && (
                            <p className="text-sm text-gray-500">Keyword: {generation.page.keyword}</p>
                          )}
                        </div>

                        <div className="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                          <span>Creato: {new Date(generation.createdAt).toLocaleDateString('it-IT')}</span>
                          {generation.tokensUsed > 0 && <span>{generation.tokensUsed} token</span>}
                          {generation.errorMessage && (
                            <span className="text-red-500">{generation.errorMessage}</span>
                          )}
                        </div>

                        {generation.generatedContent && (
                          <div className="mt-3 p-3 bg-gray-50 rounded-md">
                            <p className="text-sm text-gray-700 line-clamp-3">
                              {generation.generatedContent.substring(0, 200)}...
                            </p>
                          </div>
                        )}
                      </div>

                      <div className="flex items-center space-x-2 ml-4">
                        {generation.status === 'failed' && (
                          <button
                            onClick={() => handleRetry(generation.id)}
                            className="p-2 text-blue-600 hover:bg-blue-100 rounded-md"
                            title="Riprova"
                          >
                            <RefreshCw className="w-4 h-4" />
                          </button>
                        )}

                        {generation.generatedContent && (
                          <button
                            onClick={() => {
                              // TODO: Implement view modal
                              alert('Funzionalità in sviluppo')
                            }}
                            className="p-2 text-gray-600 hover:bg-gray-100 rounded-md"
                            title="Visualizza"
                          >
                            <Eye className="w-4 h-4" />
                          </button>
                        )}

                        {['completed', 'failed', 'cancelled'].includes(generation.status) && (
                          <button
                            onClick={() => handleDelete(generation.id)}
                            className="p-2 text-red-600 hover:bg-red-100 rounded-md"
                            title="Elimina"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  )
}