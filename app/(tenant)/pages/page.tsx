'use client'

import { useState, useEffect } from 'react'
import { useRouter } from 'next/navigation'
import {
  Plus,
  Search,
  Filter,
  Edit,
  Trash2,
  Sparkles,
  ExternalLink,
  RefreshCw,
  Download,
  Upload
} from 'lucide-react'

interface Page {
  id: string
  urlPath: string
  keyword: string | null
  category: string | null
  status: string
  priority: number
  generationsCount: number
  lastGeneration: any
  createdAt: string
  updatedAt: string
}

export default function PagesManagement() {
  const router = useRouter()
  const [pages, setPages] = useState<Page[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  // Filters
  const [search, setSearch] = useState('')
  const [categoryFilter, setCategoryFilter] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [showFilters, setShowFilters] = useState(false)

  // Pagination
  const [currentPage, setCurrentPage] = useState(1)
  const [totalPages, setTotalPages] = useState(1)
  const [totalCount, setTotalCount] = useState(0)

  // Selection
  const [selectedPages, setSelectedPages] = useState<string[]>([])

  useEffect(() => {
    loadPages()
  }, [currentPage, search, categoryFilter, statusFilter])

  const loadPages = async () => {
    setLoading(true)
    setError('')

    try {
      const params = new URLSearchParams({
        page: currentPage.toString(),
        limit: '10',
        ...(search && { search }),
        ...(categoryFilter && { category: categoryFilter }),
        ...(statusFilter && { status: statusFilter })
      })

      const response = await fetch(`/api/pages?${params}`)

      if (!response.ok) {
        const errorData = await response.json()
        setError(errorData.error || 'Errore nel caricamento delle pagine')
        return
      }

      const data = await response.json()
      setPages(data.pages)
      setTotalPages(data.pagination.totalPages)
      setTotalCount(data.pagination.totalCount)

    } catch (error) {
      console.error('Error loading pages:', error)
      setError('Errore di connessione')
    } finally {
      setLoading(false)
    }
  }

  const handleDeletePage = async (pageId: string) => {
    if (!confirm('Sei sicuro di voler eliminare questa pagina?')) return

    try {
      const response = await fetch(`/api/pages/${pageId}`, {
        method: 'DELETE'
      })

      if (response.ok) {
        loadPages()
      } else {
        const errorData = await response.json()
        alert(errorData.error || 'Errore durante l\'eliminazione')
      }
    } catch (error) {
      alert('Errore di connessione')
    }
  }

  const handleBulkDelete = async () => {
    if (selectedPages.length === 0) return
    if (!confirm(`Sei sicuro di voler eliminare ${selectedPages.length} pagine?`)) return

    try {
      await Promise.all(
        selectedPages.map(pageId =>
          fetch(`/api/pages/${pageId}`, { method: 'DELETE' })
        )
      )
      setSelectedPages([])
      loadPages()
    } catch (error) {
      alert('Errore durante l\'eliminazione')
    }
  }

  const togglePageSelection = (pageId: string) => {
    setSelectedPages(prev =>
      prev.includes(pageId)
        ? prev.filter(id => id !== pageId)
        : [...prev, pageId]
    )
  }

  const toggleAllSelection = () => {
    setSelectedPages(prev =>
      prev.length === pages.length ? [] : pages.map(page => page.id)
    )
  }

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'active': return 'bg-green-100 text-green-800'
      case 'draft': return 'bg-gray-100 text-gray-800'
      case 'archived': return 'bg-red-100 text-red-800'
      default: return 'bg-gray-100 text-gray-800'
    }
  }

  const getStatusText = (status: string) => {
    switch (status) {
      case 'active': return 'Attiva'
      case 'draft': return 'Bozza'
      case 'archived': return 'Archiviata'
      default: return status
    }
  }

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-8">
        <div className="flex items-center justify-between mb-4">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
              Gestione Pagine
            </h1>
            <p className="text-gray-600 dark:text-gray-400 mt-2">
              Gestisci le pagine per la generazione di contenuti AI
            </p>
          </div>

          <div className="flex gap-3">
            <button
              onClick={() => setShowFilters(!showFilters)}
              className="flex items-center px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700 dark:text-white transition-colors"
            >
              <Filter size={18} className="mr-2" />
              Filtri
            </button>
            <button
              onClick={() => router.push('/pages/new')}
              className="flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
            >
              <Plus size={18} className="mr-2" />
              Aggiungi Pagina
            </button>
          </div>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div className="text-sm font-medium text-gray-600 dark:text-gray-400">Totale Pagine</div>
            <div className="text-2xl font-bold text-gray-900 dark:text-white">{totalCount}</div>
          </div>
          <div className="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div className="text-sm font-medium text-gray-600 dark:text-gray-400">Attive</div>
            <div className="text-2xl font-bold text-green-600">{pages.filter(p => p.status === 'active').length}</div>
          </div>
          <div className="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div className="text-sm font-medium text-gray-600 dark:text-gray-400">Bozze</div>
            <div className="text-2xl font-bold text-gray-600">{pages.filter(p => p.status === 'draft').length}</div>
          </div>
          <div className="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
            <div className="text-sm font-medium text-gray-600 dark:text-gray-400">Con Generazioni</div>
            <div className="text-2xl font-bold text-purple-600">{pages.filter(p => p.generationsCount > 0).length}</div>
          </div>
        </div>
      </div>

      {/* Filters */}
      {showFilters && (
        <div className="bg-white dark:bg-gray-800 rounded-lg p-6 mb-6 border border-gray-200 dark:border-gray-700">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Ricerca
              </label>
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" size={18} />
                <input
                  type="text"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  placeholder="URL, keyword o categoria..."
                  className="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Categoria
              </label>
              <select
                value={categoryFilter}
                onChange={(e) => setCategoryFilter(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
              >
                <option value="">Tutte le categorie</option>
                <option value="blog">Blog</option>
                <option value="servizi">Servizi</option>
                <option value="prodotti">Prodotti</option>
                <option value="landing">Landing Page</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Stato
              </label>
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
              >
                <option value="">Tutti gli stati</option>
                <option value="active">Attive</option>
                <option value="draft">Bozze</option>
                <option value="archived">Archiviate</option>
              </select>
            </div>
          </div>

          <div className="flex justify-end mt-4">
            <button
              onClick={() => {
                setSearch('')
                setCategoryFilter('')
                setStatusFilter('')
                setCurrentPage(1)
              }}
              className="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200"
            >
              Pulisci Filtri
            </button>
          </div>
        </div>
      )}

      {/* Bulk Actions */}
      {selectedPages.length > 0 && (
        <div className="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-6 flex items-center justify-between">
          <span className="text-blue-900 dark:text-blue-100 font-medium">
            {selectedPages.length} pagine selezionate
          </span>
          <div className="flex gap-2">
            <button
              onClick={handleBulkDelete}
              className="flex items-center px-3 py-1.5 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/20 rounded-lg transition-colors"
            >
              <Trash2 size={16} className="mr-1" />
              Elimina
            </button>
          </div>
        </div>
      )}

      {/* Error */}
      {error && (
        <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
          <p className="text-red-600 dark:text-red-400">{error}</p>
        </div>
      )}

      {/* Pages Table */}
      <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead className="bg-gray-50 dark:bg-gray-800">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  <input
                    type="checkbox"
                    checked={selectedPages.length === pages.length && pages.length > 0}
                    onChange={toggleAllSelection}
                    className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  />
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Pagina
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Categoria
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Stato
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Generazioni
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Aggiornata
                </th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                  Azioni
                </th>
              </tr>
            </thead>
            <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              {loading ? (
                <tr>
                  <td colSpan={7} className="px-6 py-8 text-center">
                    <RefreshCw className="animate-spin mx-auto mb-2" size={24} />
                    <p className="text-gray-500 dark:text-gray-400">Caricamento...</p>
                  </td>
                </tr>
              ) : pages.length === 0 ? (
                <tr>
                  <td colSpan={7} className="px-6 py-8 text-center">
                    <p className="text-gray-500 dark:text-gray-400">Nessuna pagina trovata</p>
                  </td>
                </tr>
              ) : (
                pages.map((page) => (
                  <tr key={page.id} className="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <input
                        type="checkbox"
                        checked={selectedPages.includes(page.id)}
                        onChange={() => togglePageSelection(page.id)}
                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                      />
                    </td>
                    <td className="px-6 py-4">
                      <div>
                        <div className="text-sm font-medium text-gray-900 dark:text-white">
                          {page.urlPath}
                        </div>
                        {page.keyword && (
                          <div className="text-sm text-gray-500 dark:text-gray-400">
                            🎯 {page.keyword}
                          </div>
                        )}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {page.category && (
                        <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                          {page.category}
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(page.status)}`}>
                        {getStatusText(page.status)}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                      {page.generationsCount > 0 ? (
                        <div className="flex items-center">
                          <Sparkles className="text-purple-500 mr-1" size={16} />
                          {page.generationsCount}
                        </div>
                      ) : (
                        <span className="text-gray-400">-</span>
                      )}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      {new Date(page.updatedAt).toLocaleDateString('it-IT')}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div className="flex items-center justify-end gap-2">
                        <button
                          onClick={() => router.push(`/pages/${page.id}`)}
                          className="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                        >
                          <Edit size={16} />
                        </button>
                        <button
                          onClick={() => router.push(`/generations/new?pageId=${page.id}`)}
                          className="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-300"
                        >
                          <Sparkles size={16} />
                        </button>
                        <button
                          onClick={() => handleDeletePage(page.id)}
                          className="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                        >
                          <Trash2 size={16} />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="px-6 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div className="text-sm text-gray-700 dark:text-gray-300">
              Pagina {currentPage} di {totalPages} ({totalCount} pagine totali)
            </div>
            <div className="flex gap-2">
              <button
                onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
                disabled={currentPage === 1}
                className="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed dark:text-white"
              >
                Precedente
              </button>
              <button
                onClick={() => setCurrentPage(Math.min(totalPages, currentPage + 1))}
                disabled={currentPage === totalPages}
                className="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed dark:text-white"
              >
                Successiva
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  )
}