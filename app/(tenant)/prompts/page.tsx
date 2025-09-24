'use client'

import { useState, useEffect } from 'react'
import { Plus, Edit3, Copy, Trash2, Eye, Globe, User, Search, Filter, X } from 'lucide-react'

interface Prompt {
  id: string
  name: string
  alias: string
  description: string | null
  template: string
  category: string
  variables: string[]
  isActive: boolean
  isGlobal: boolean
  usageCount: number
  tenant?: {
    id: string
    name: string
    subdomain: string
  }
  createdAt: string
  updatedAt: string
}

export default function PromptsPage() {
  const [prompts, setPrompts] = useState<Prompt[]>([])
  const [categories, setCategories] = useState<string[]>([])
  const [loading, setLoading] = useState(true)
  const [showModal, setShowModal] = useState(false)
  const [editingPrompt, setEditingPrompt] = useState<Prompt | null>(null)
  const [viewingPrompt, setViewingPrompt] = useState<Prompt | null>(null)

  // Filters
  const [search, setSearch] = useState('')
  const [categoryFilter, setCategoryFilter] = useState('')
  const [showGlobal, setShowGlobal] = useState(true)

  // Form state
  const [formData, setFormData] = useState({
    name: '',
    alias: '',
    description: '',
    template: '',
    category: 'content',
    variables: [] as string[],
    isActive: true
  })
  const [newVariable, setNewVariable] = useState('')

  useEffect(() => {
    loadPrompts()
    loadCategories()
  }, [search, categoryFilter, showGlobal])

  const loadPrompts = async () => {
    setLoading(true)
    try {
      const params = new URLSearchParams({
        limit: '100',
        includeGlobal: showGlobal.toString()
      })

      if (search) params.append('search', search)
      if (categoryFilter) params.append('category', categoryFilter)

      const response = await fetch(`/api/prompts?${params}`)
      if (response.ok) {
        const data = await response.json()
        setPrompts(data.prompts || [])
      }
    } catch (error) {
      console.error('Error loading prompts:', error)
    } finally {
      setLoading(false)
    }
  }

  const loadCategories = async () => {
    try {
      const response = await fetch('/api/prompts/categories')
      if (response.ok) {
        const data = await response.json()
        setCategories(data.categories || [])
      }
    } catch (error) {
      console.error('Error loading categories:', error)
    }
  }

  const handleSave = async () => {
    try {
      const url = editingPrompt ? `/api/prompts/${editingPrompt.id}` : '/api/prompts'
      const method = editingPrompt ? 'PUT' : 'POST'

      const response = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
      })

      if (response.ok) {
        alert(editingPrompt ? 'Prompt aggiornato' : 'Prompt creato')
        setShowModal(false)
        resetForm()
        loadPrompts()
      } else {
        const error = await response.json()
        alert(`Errore: ${error.error}`)
      }
    } catch (error) {
      console.error('Error saving prompt:', error)
      alert('Errore durante il salvataggio')
    }
  }

  const handleDelete = async (promptId: string) => {
    if (!confirm('Sei sicuro di voler eliminare questo prompt?')) return

    try {
      const response = await fetch(`/api/prompts/${promptId}`, {
        method: 'DELETE'
      })

      if (response.ok) {
        alert('Prompt eliminato')
        loadPrompts()
      } else {
        const error = await response.json()
        alert(`Errore: ${error.error}`)
      }
    } catch (error) {
      console.error('Error deleting prompt:', error)
      alert('Errore durante l\'eliminazione')
    }
  }

  const handleDuplicate = async (promptId: string, name: string, alias: string) => {
    try {
      const response = await fetch(`/api/prompts/${promptId}/duplicate`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, alias })
      })

      if (response.ok) {
        alert('Prompt duplicato')
        loadPrompts()
      } else {
        const error = await response.json()
        alert(`Errore: ${error.error}`)
      }
    } catch (error) {
      console.error('Error duplicating prompt:', error)
      alert('Errore durante la duplicazione')
    }
  }

  const openEditModal = (prompt: Prompt) => {
    setEditingPrompt(prompt)
    setFormData({
      name: prompt.name,
      alias: prompt.alias,
      description: prompt.description || '',
      template: prompt.template,
      category: prompt.category,
      variables: prompt.variables,
      isActive: prompt.isActive
    })
    setShowModal(true)
  }

  const openCreateModal = () => {
    setEditingPrompt(null)
    resetForm()
    setShowModal(true)
  }

  const resetForm = () => {
    setFormData({
      name: '',
      alias: '',
      description: '',
      template: '',
      category: 'content',
      variables: [],
      isActive: true
    })
    setNewVariable('')
  }

  const addVariable = () => {
    if (newVariable.trim() && !formData.variables.includes(newVariable.trim())) {
      setFormData({
        ...formData,
        variables: [...formData.variables, newVariable.trim()]
      })
      setNewVariable('')
    }
  }

  const removeVariable = (variable: string) => {
    setFormData({
      ...formData,
      variables: formData.variables.filter(v => v !== variable)
    })
  }

  const filteredPrompts = prompts.filter(prompt => {
    if (search && !prompt.name.toLowerCase().includes(search.toLowerCase()) &&
        !prompt.alias.toLowerCase().includes(search.toLowerCase())) {
      return false
    }
    if (categoryFilter && prompt.category !== categoryFilter) {
      return false
    }
    return true
  })

  return (
    <div className="p-6">
      {/* Header */}
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Gestione Prompt</h1>
          <p className="text-gray-600">Crea e gestisci i template per la generazione di contenuti AI</p>
        </div>

        <button
          onClick={openCreateModal}
          className="flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
        >
          <Plus className="w-4 h-4 mr-2" />
          Nuovo Prompt
        </button>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg border border-gray-200 p-4 mb-6">
        <div className="flex flex-wrap items-center gap-4">
          <div className="flex-1 min-w-64">
            <div className="relative">
              <Search className="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <input
                type="text"
                placeholder="Cerca prompt..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>

          <div>
            <select
              value={categoryFilter}
              onChange={(e) => setCategoryFilter(e.target.value)}
              className="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Tutte le categorie</option>
              {categories.map(category => (
                <option key={category} value={category}>{category}</option>
              ))}
            </select>
          </div>

          <div className="flex items-center">
            <input
              type="checkbox"
              id="showGlobal"
              checked={showGlobal}
              onChange={(e) => setShowGlobal(e.target.checked)}
              className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            />
            <label htmlFor="showGlobal" className="ml-2 text-sm text-gray-700">
              Includi prompt globali
            </label>
          </div>
        </div>
      </div>

      {/* Prompts Grid */}
      {loading ? (
        <div className="text-center py-8">
          <p className="text-gray-500">Caricamento prompt...</p>
        </div>
      ) : filteredPrompts.length === 0 ? (
        <div className="text-center py-8">
          <p className="text-gray-500">Nessun prompt trovato</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {filteredPrompts.map((prompt) => (
            <div key={prompt.id} className="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-lg transition-shadow">
              <div className="flex items-start justify-between mb-4">
                <div className="flex-1">
                  <div className="flex items-center space-x-2">
                    <h3 className="text-lg font-medium text-gray-900 truncate">
                      {prompt.name}
                    </h3>
                    {prompt.isGlobal && (
                      <Globe className="w-4 h-4 text-blue-500" title="Prompt globale" />
                    )}
                    {!prompt.isGlobal && (
                      <User className="w-4 h-4 text-green-500" title="Prompt personalizzato" />
                    )}
                  </div>
                  <p className="text-sm text-gray-500 font-mono">{prompt.alias}</p>
                </div>

                <div className={`px-2 py-1 rounded-full text-xs font-medium ${
                  prompt.isActive
                    ? 'bg-green-100 text-green-800'
                    : 'bg-gray-100 text-gray-800'
                }`}>
                  {prompt.isActive ? 'Attivo' : 'Inattivo'}
                </div>
              </div>

              {prompt.description && (
                <p className="text-sm text-gray-600 mb-3 line-clamp-2">
                  {prompt.description}
                </p>
              )}

              <div className="mb-4">
                <div className="flex items-center justify-between text-sm text-gray-500 mb-2">
                  <span className="bg-gray-100 px-2 py-1 rounded capitalize">{prompt.category}</span>
                  <span>{prompt.usageCount} utilizzi</span>
                </div>

                {prompt.variables.length > 0 && (
                  <div className="flex flex-wrap gap-1 mb-2">
                    {prompt.variables.map(variable => (
                      <span key={variable} className="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                        {variable}
                      </span>
                    ))}
                  </div>
                )}

                <p className="text-xs text-gray-400 line-clamp-2">
                  {prompt.template.substring(0, 100)}...
                </p>
              </div>

              <div className="flex items-center justify-between border-t border-gray-100 pt-4">
                <div className="flex space-x-2">
                  <button
                    onClick={() => setViewingPrompt(prompt)}
                    className="p-2 text-gray-600 hover:bg-gray-100 rounded-md"
                    title="Visualizza"
                  >
                    <Eye className="w-4 h-4" />
                  </button>

                  <button
                    onClick={() => {
                      const name = prompt(`${prompt.name} (Copy)`)
                      const alias = prompt(`${prompt.alias}_copy`)
                      if (name && alias) {
                        handleDuplicate(prompt.id, name, alias)
                      }
                    }}
                    className="p-2 text-blue-600 hover:bg-blue-100 rounded-md"
                    title="Duplica"
                  >
                    <Copy className="w-4 h-4" />
                  </button>

                  {!prompt.isGlobal && (
                    <>
                      <button
                        onClick={() => openEditModal(prompt)}
                        className="p-2 text-green-600 hover:bg-green-100 rounded-md"
                        title="Modifica"
                      >
                        <Edit3 className="w-4 h-4" />
                      </button>

                      <button
                        onClick={() => handleDelete(prompt.id)}
                        className="p-2 text-red-600 hover:bg-red-100 rounded-md"
                        title="Elimina"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    </>
                  )}
                </div>

                <div className="text-xs text-gray-400">
                  {new Date(prompt.updatedAt).toLocaleDateString('it-IT')}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Create/Edit Modal */}
      {showModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6">
              <div className="flex justify-between items-center mb-6">
                <h2 className="text-xl font-bold text-gray-900">
                  {editingPrompt ? 'Modifica Prompt' : 'Nuovo Prompt'}
                </h2>
                <button
                  onClick={() => setShowModal(false)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <X className="w-6 h-6" />
                </button>
              </div>

              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Left Column */}
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Nome *
                    </label>
                    <input
                      type="text"
                      value={formData.name}
                      onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Nome del prompt"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Alias *
                    </label>
                    <input
                      type="text"
                      value={formData.alias}
                      onChange={(e) => setFormData({ ...formData, alias: e.target.value.toLowerCase().replace(/[^a-z0-9_]/g, '_') })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                      placeholder="alias_prompt"
                    />
                    <p className="text-xs text-gray-500 mt-1">Solo lettere, numeri e underscore</p>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Descrizione
                    </label>
                    <textarea
                      rows={3}
                      value={formData.description}
                      onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Descrizione del prompt"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Categoria
                    </label>
                    <select
                      value={formData.category}
                      onChange={(e) => setFormData({ ...formData, category: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    >
                      <option value="content">Contenuto</option>
                      <option value="seo">SEO</option>
                      <option value="meta">Meta Tag</option>
                      <option value="social">Social Media</option>
                      <option value="custom">Personalizzato</option>
                    </select>
                  </div>

                  <div>
                    <div className="flex items-center">
                      <input
                        type="checkbox"
                        id="isActive"
                        checked={formData.isActive}
                        onChange={(e) => setFormData({ ...formData, isActive: e.target.checked })}
                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                      />
                      <label htmlFor="isActive" className="ml-2 text-sm text-gray-700">
                        Prompt attivo
                      </label>
                    </div>
                  </div>

                  {/* Variables */}
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Variabili
                    </label>
                    <div className="space-y-2">
                      <div className="flex space-x-2">
                        <input
                          type="text"
                          value={newVariable}
                          onChange={(e) => setNewVariable(e.target.value)}
                          onKeyPress={(e) => e.key === 'Enter' && addVariable()}
                          className="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Nome variabile"
                        />
                        <button
                          onClick={addVariable}
                          className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                        >
                          Aggiungi
                        </button>
                      </div>

                      {formData.variables.length > 0 && (
                        <div className="flex flex-wrap gap-2">
                          {formData.variables.map(variable => (
                            <span
                              key={variable}
                              className="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm"
                            >
                              {variable}
                              <button
                                onClick={() => removeVariable(variable)}
                                className="ml-2 text-blue-600 hover:text-blue-800"
                              >
                                <X className="w-3 h-3" />
                              </button>
                            </span>
                          ))}
                        </div>
                      )}
                    </div>
                    <p className="text-xs text-gray-500 mt-1">
                      Le variabili possono essere usate nel template con la sintassi {`{{nome_variabile}}`}
                    </p>
                  </div>
                </div>

                {/* Right Column - Template */}
                <div className="space-y-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                      Template *
                    </label>
                    <textarea
                      rows={15}
                      value={formData.template}
                      onChange={(e) => setFormData({ ...formData, template: e.target.value })}
                      className="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                      placeholder="Scrivi qui il tuo prompt template..."
                    />
                    <p className="text-xs text-gray-500 mt-1">
                      Usa {`{{url_path}}, {{keyword}}, {{category}}, {{language}}`} per variabili automatiche
                    </p>
                  </div>

                  <div className="text-sm text-gray-600">
                    <p className="font-medium mb-2">Variabili disponibili automaticamente:</p>
                    <ul className="space-y-1 text-xs">
                      <li><code className="bg-gray-100 px-1 rounded">{'{{url_path}}'}</code> - Percorso URL della pagina</li>
                      <li><code className="bg-gray-100 px-1 rounded">{'{{keyword}}'}</code> - Keyword target della pagina</li>
                      <li><code className="bg-gray-100 px-1 rounded">{'{{category}}'}</code> - Categoria della pagina</li>
                      <li><code className="bg-gray-100 px-1 rounded">{'{{language}}'}</code> - Lingua della pagina</li>
                    </ul>
                  </div>
                </div>
              </div>

              <div className="flex justify-end space-x-4 mt-6 pt-6 border-t border-gray-200">
                <button
                  onClick={() => setShowModal(false)}
                  className="px-4 py-2 text-gray-600 hover:text-gray-800"
                >
                  Annulla
                </button>
                <button
                  onClick={handleSave}
                  disabled={!formData.name || !formData.alias || !formData.template}
                  className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
                >
                  {editingPrompt ? 'Aggiorna' : 'Crea'} Prompt
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* View Modal */}
      {viewingPrompt && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div className="p-6">
              <div className="flex justify-between items-center mb-6">
                <div>
                  <h2 className="text-xl font-bold text-gray-900">{viewingPrompt.name}</h2>
                  <p className="text-gray-500 font-mono text-sm">{viewingPrompt.alias}</p>
                </div>
                <button
                  onClick={() => setViewingPrompt(null)}
                  className="text-gray-400 hover:text-gray-600"
                >
                  <X className="w-6 h-6" />
                </button>
              </div>

              <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div className="lg:col-span-2">
                  <h3 className="font-medium text-gray-900 mb-2">Template</h3>
                  <div className="bg-gray-50 p-4 rounded-md">
                    <pre className="text-sm text-gray-700 whitespace-pre-wrap">
                      {viewingPrompt.template}
                    </pre>
                  </div>
                </div>

                <div>
                  <div className="space-y-4">
                    <div>
                      <h3 className="font-medium text-gray-900 mb-2">Informazioni</h3>
                      <dl className="space-y-2 text-sm">
                        <div>
                          <dt className="text-gray-500">Categoria:</dt>
                          <dd className="font-medium capitalize">{viewingPrompt.category}</dd>
                        </div>
                        <div>
                          <dt className="text-gray-500">Stato:</dt>
                          <dd className={`font-medium ${viewingPrompt.isActive ? 'text-green-600' : 'text-gray-600'}`}>
                            {viewingPrompt.isActive ? 'Attivo' : 'Inattivo'}
                          </dd>
                        </div>
                        <div>
                          <dt className="text-gray-500">Utilizzi:</dt>
                          <dd className="font-medium">{viewingPrompt.usageCount}</dd>
                        </div>
                        <div>
                          <dt className="text-gray-500">Tipo:</dt>
                          <dd className="font-medium">
                            {viewingPrompt.isGlobal ? 'Globale' : 'Personalizzato'}
                          </dd>
                        </div>
                      </dl>
                    </div>

                    {viewingPrompt.description && (
                      <div>
                        <h3 className="font-medium text-gray-900 mb-2">Descrizione</h3>
                        <p className="text-sm text-gray-700">{viewingPrompt.description}</p>
                      </div>
                    )}

                    {viewingPrompt.variables.length > 0 && (
                      <div>
                        <h3 className="font-medium text-gray-900 mb-2">Variabili</h3>
                        <div className="space-y-1">
                          {viewingPrompt.variables.map(variable => (
                            <span key={variable} className="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm mr-2 mb-1">
                              {variable}
                            </span>
                          ))}
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}