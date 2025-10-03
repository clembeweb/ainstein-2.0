# 🎨 Ainstein UI/UX Design System

## 📐 Design Principles

### Core Values
1. **🧠 AI-First**: L'AI non è una feature, è il cuore dell'interfaccia
2. **⚡ Speed**: Ogni azione deve essere immediata e fluida
3. **🎯 Clarity**: Zero ambiguità, massima chiarezza
4. **🔮 Predictive**: L'UI anticipa le necessità dell'utente
5. **♿ Accessible**: WCAG 2.1 AA compliance

---

## 🎨 Color Palette

### Primary Colors
```css
/* Indigo - Primary Brand */
--color-primary-50: #eef2ff;
--color-primary-100: #e0e7ff;
--color-primary-200: #c7d2fe;
--color-primary-300: #a5b4fc;
--color-primary-400: #818cf8;
--color-primary-500: #6366f1;  /* Main */
--color-primary-600: #4f46e5;
--color-primary-700: #4338ca;
--color-primary-800: #3730a3;
--color-primary-900: #312e81;

/* Success - Green */
--color-success: #10b981;
--color-success-light: #d1fae5;
--color-success-dark: #065f46;

/* Warning - Amber */
--color-warning: #f59e0b;
--color-warning-light: #fef3c7;
--color-warning-dark: #92400e;

/* Error - Red */
--color-error: #ef4444;
--color-error-light: #fee2e2;
--color-error-dark: #991b1b;

/* Info - Blue */
--color-info: #3b82f6;
--color-info-light: #dbeafe;
--color-info-dark: #1e40af;
```

### Neutral Scale
```css
--color-gray-50: #f9fafb;
--color-gray-100: #f3f4f6;
--color-gray-200: #e5e7eb;
--color-gray-300: #d1d5db;
--color-gray-400: #9ca3af;
--color-gray-500: #6b7280;
--color-gray-600: #4b5563;
--color-gray-700: #374151;
--color-gray-800: #1f2937;
--color-gray-900: #111827;
```

### AI/Special Colors
```css
/* AI Gradient */
--color-ai-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

/* Token Usage */
--color-token-low: #10b981;    /* < 30% usage */
--color-token-medium: #f59e0b;  /* 30-70% usage */
--color-token-high: #ef4444;    /* > 70% usage */
```

---

## 📏 Typography

### Font Stack
```css
/* Primary Font - Inter */
--font-primary: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;

/* Monospace - Code/API Keys */
--font-mono: 'JetBrains Mono', 'Fira Code', Consolas, Monaco, monospace;
```

### Type Scale
```css
/* Headings */
--text-xs: 0.75rem;    /* 12px */
--text-sm: 0.875rem;   /* 14px */
--text-base: 1rem;     /* 16px */
--text-lg: 1.125rem;   /* 18px */
--text-xl: 1.25rem;    /* 20px */
--text-2xl: 1.5rem;    /* 24px */
--text-3xl: 1.875rem;  /* 30px */
--text-4xl: 2.25rem;   /* 36px */
--text-5xl: 3rem;      /* 48px */

/* Line Heights */
--leading-tight: 1.25;
--leading-normal: 1.5;
--leading-relaxed: 1.75;

/* Font Weights */
--font-normal: 400;
--font-medium: 500;
--font-semibold: 600;
--font-bold: 700;
```

---

## 🧱 Layout Components

### 1. **Dashboard Layout Structure**

```blade
<!-- Layout Base: resources/views/layouts/tenant.blade.php -->
<div class="min-h-screen bg-gray-50">

    <!-- Sidebar Navigation -->
    <aside class="fixed inset-y-0 left-0 w-64 bg-white border-r border-gray-200 z-40">
        <!-- Logo -->
        <div class="flex items-center justify-center h-16 border-b border-gray-200">
            <img src="/logo.svg" alt="Ainstein" class="h-8">
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 px-4 py-6 space-y-1">
            <!-- Nav items -->
        </nav>

        <!-- User Profile -->
        <div class="border-t border-gray-200 p-4">
            <!-- User info + logout -->
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="ml-64">

        <!-- Top Bar -->
        <header class="sticky top-0 z-30 bg-white border-b border-gray-200">
            <div class="flex items-center justify-between h-16 px-6">

                <!-- Breadcrumbs -->
                <nav class="flex items-center space-x-2 text-sm">
                    <!-- Breadcrumb items -->
                </nav>

                <!-- Right Actions -->
                <div class="flex items-center space-x-4">
                    <!-- Token Counter Badge -->
                    <div class="token-badge">
                        <span class="token-count">1,250</span>
                        <span class="token-label">/ 10,000 token</span>
                    </div>

                    <!-- Notifications -->
                    <button class="notification-btn">🔔</button>

                    <!-- Ainstein Copilot -->
                    <button class="copilot-trigger">🤖 Ask Ainstein</button>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-6">
            @yield('content')
        </main>

    </div>

    <!-- Ainstein Copilot Modal (Fixed Bottom Right) -->
    <div x-data="{ open: false }" class="copilot-wrapper">
        <!-- Copilot UI -->
    </div>

</div>
```

### 2. **Navigation Menu Items**

```blade
<!-- Standard Nav Item -->
<a href="{{ route('tenant.dashboard') }}"
   class="nav-item {{ request()->routeIs('tenant.dashboard') ? 'active' : '' }}">
    <svg class="nav-icon"><!-- icon --></svg>
    <span class="nav-label">Dashboard</span>
</a>

<!-- Nav with Badge -->
<a href="{{ route('tenant.tools.index') }}" class="nav-item">
    <svg class="nav-icon"><!-- icon --></svg>
    <span class="nav-label">AI Tools</span>
    <span class="nav-badge">6</span>
</a>

<!-- Nav with Submenu -->
<div x-data="{ open: false }" class="nav-group">
    <button @click="open = !open" class="nav-item">
        <svg class="nav-icon"><!-- icon --></svg>
        <span class="nav-label">SEO Tools</span>
        <svg class="nav-chevron" :class="{ 'rotate-180': open }"><!-- chevron --></svg>
    </button>

    <div x-show="open" x-collapse class="nav-submenu">
        <a href="#" class="nav-subitem">Internal Links</a>
        <a href="#" class="nav-subitem">Keyword Research</a>
        <a href="#" class="nav-subitem">GSC Tracker</a>
    </div>
</div>
```

---

## 📊 Tool Page Layout (Standard)

Ogni tool deve seguire questa struttura consistente:

```blade
@extends('layouts.tenant')

@section('content')
<div class="tool-page" x-data="toolController()">

    <!-- 1. Header Section -->
    <div class="tool-header">
        <div class="flex items-center justify-between mb-6">
            <!-- Left: Title + Description -->
            <div class="flex-1">
                <h1 class="tool-title">
                    <span class="tool-icon">🔍</span>
                    Keyword Research
                </h1>
                <p class="tool-description">
                    Trova keyword vincenti con AI multi-source intelligence
                </p>
            </div>

            <!-- Right: Actions -->
            <div class="flex items-center gap-3">
                <!-- Help Button -->
                <button @click="startOnboarding()" class="btn-secondary btn-sm">
                    ❓ Tour Guidato
                </button>

                <!-- Primary CTA -->
                <button @click="openCreateModal()" class="btn-primary">
                    ➕ Nuova Ricerca
                </button>
            </div>
        </div>

        <!-- Stats Cards Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <p class="stat-label">Totale Ricerche</p>
                    <p class="stat-value">{{ $stats['total'] }}</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <p class="stat-label">Completate</p>
                    <p class="stat-value">{{ $stats['completed'] }}</p>
                </div>
            </div>

            <div class="stat-card ai-highlight">
                <div class="stat-icon">⚡</div>
                <div class="stat-content">
                    <p class="stat-label">Token Usati</p>
                    <p class="stat-value">{{ number_format($stats['tokens']) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Filters/Controls Bar -->
    <div class="tool-controls">
        <div class="flex items-center gap-4">
            <!-- Search -->
            <div class="search-box flex-1 max-w-md">
                <svg class="search-icon"><!-- magnifier --></svg>
                <input type="text"
                       x-model="search"
                       placeholder="Cerca..."
                       class="search-input">
            </div>

            <!-- Filters -->
            <select x-model="filterStatus" class="filter-select">
                <option value="">Tutti gli stati</option>
                <option value="completed">Completati</option>
                <option value="processing">In elaborazione</option>
            </select>

            <!-- Bulk Actions (when items selected) -->
            <div x-show="selectedItems.length > 0" class="bulk-actions">
                <span class="text-sm text-gray-600">
                    <span x-text="selectedItems.length"></span> selezionati
                </span>
                <button @click="bulkDelete()" class="btn-danger btn-sm">
                    🗑️ Elimina
                </button>
            </div>
        </div>
    </div>

    <!-- 3. Data Table / Cards -->
    <div class="tool-content">

        <!-- Table View -->
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="table-checkbox">
                            <input type="checkbox" @change="toggleAll($event)">
                        </th>
                        <th>Keyword</th>
                        <th>Volume</th>
                        <th>CPC</th>
                        <th>Difficoltà</th>
                        <th>Status</th>
                        <th class="table-actions">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr class="table-row">
                        <td class="table-checkbox">
                            <input type="checkbox"
                                   :value="{{ $item->id }}"
                                   x-model="selectedItems">
                        </td>
                        <td class="font-medium">{{ $item->keyword }}</td>
                        <td>{{ number_format($item->volume) }}</td>
                        <td>€{{ $item->cpc }}</td>
                        <td>
                            <div class="difficulty-badge difficulty-{{ $item->difficulty }}">
                                {{ ucfirst($item->difficulty) }}
                            </div>
                        </td>
                        <td>
                            <span class="status-badge status-{{ $item->status }}">
                                {{ ucfirst($item->status) }}
                            </span>
                        </td>
                        <td class="table-actions">
                            <button @click="viewItem({{ $item->id }})" class="action-btn">
                                👁️ Vedi
                            </button>
                            <button @click="deleteItem({{ $item->id }})" class="action-btn text-red-600">
                                🗑️
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="table-pagination">
                {{ $items->links() }}
            </div>
        </div>

    </div>

    <!-- 4. Modals (Create/Edit/View) -->
    <div x-show="showModal" class="modal-overlay" x-cloak>
        <div class="modal-container">
            <div class="modal-header">
                <h3 class="modal-title">Titolo Modal</h3>
                <button @click="showModal = false" class="modal-close">✕</button>
            </div>
            <div class="modal-body">
                <!-- Modal content -->
            </div>
            <div class="modal-footer">
                <button @click="showModal = false" class="btn-secondary">Annulla</button>
                <button @click="submit()" class="btn-primary">Conferma</button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function toolController() {
    return {
        search: '',
        filterStatus: '',
        selectedItems: [],
        showModal: false,

        toggleAll(event) {
            if (event.target.checked) {
                this.selectedItems = @json($items->pluck('id'));
            } else {
                this.selectedItems = [];
            }
        },

        startOnboarding() {
            if (typeof window.startToolOnboarding === 'function') {
                window.startToolOnboarding();
            }
        },

        // ... altri metodi
    };
}
</script>
@endpush
@endsection
```

---

## 🧩 UI Components Library

### 1. **Buttons**

```blade
<!-- Primary Button -->
<button class="btn btn-primary">
    Primary Action
</button>

<!-- Secondary Button -->
<button class="btn btn-secondary">
    Secondary Action
</button>

<!-- Danger Button -->
<button class="btn btn-danger">
    Delete
</button>

<!-- Icon Button -->
<button class="btn btn-icon">
    <svg><!-- icon --></svg>
</button>

<!-- Loading Button -->
<button class="btn btn-primary" :disabled="loading">
    <span x-show="!loading">Save</span>
    <span x-show="loading" class="flex items-center">
        <svg class="animate-spin h-4 w-4 mr-2"><!-- spinner --></svg>
        Saving...
    </span>
</button>

<!-- AI Button (special gradient) -->
<button class="btn btn-ai">
    🤖 Generate with AI
</button>
```

**CSS Classes**
```css
.btn {
    @apply px-4 py-2 rounded-lg font-medium transition-all duration-200;
    @apply focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.btn-primary {
    @apply bg-indigo-600 text-white hover:bg-indigo-700;
    @apply focus:ring-indigo-500;
}

.btn-secondary {
    @apply bg-white text-gray-700 border border-gray-300;
    @apply hover:bg-gray-50 focus:ring-gray-500;
}

.btn-danger {
    @apply bg-red-600 text-white hover:bg-red-700;
    @apply focus:ring-red-500;
}

.btn-ai {
    @apply bg-gradient-to-r from-indigo-600 to-purple-600;
    @apply text-white hover:from-indigo-700 hover:to-purple-700;
    @apply shadow-lg hover:shadow-xl;
}

.btn-sm {
    @apply px-3 py-1.5 text-sm;
}
```

### 2. **Status Badges**

```blade
<!-- Success -->
<span class="badge badge-success">Completed</span>

<!-- Warning -->
<span class="badge badge-warning">Processing</span>

<!-- Error -->
<span class="badge badge-error">Failed</span>

<!-- Info -->
<span class="badge badge-info">Pending</span>
```

**CSS**
```css
.badge {
    @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
}

.badge-success {
    @apply bg-green-100 text-green-800;
}

.badge-warning {
    @apply bg-yellow-100 text-yellow-800;
}

.badge-error {
    @apply bg-red-100 text-red-800;
}

.badge-info {
    @apply bg-blue-100 text-blue-800;
}
```

### 3. **Cards**

```blade
<!-- Standard Card -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Card Title</h3>
    </div>
    <div class="card-body">
        Content here
    </div>
    <div class="card-footer">
        <button class="btn btn-primary">Action</button>
    </div>
</div>

<!-- Stat Card (con AI highlight) -->
<div class="stat-card {{ $isAI ? 'ai-highlight' : '' }}">
    <div class="stat-icon">📊</div>
    <div class="stat-content">
        <p class="stat-label">Label</p>
        <p class="stat-value">{{ $value }}</p>
        @if($change)
        <p class="stat-change {{ $change > 0 ? 'positive' : 'negative' }}">
            {{ $change > 0 ? '↑' : '↓' }} {{ abs($change) }}%
        </p>
        @endif
    </div>
</div>
```

### 4. **Form Inputs**

```blade
<!-- Text Input -->
<div class="form-group">
    <label class="form-label">Label</label>
    <input type="text" class="form-input" placeholder="Placeholder">
    <p class="form-help">Help text</p>
</div>

<!-- Select -->
<div class="form-group">
    <label class="form-label">Select Option</label>
    <select class="form-select">
        <option>Option 1</option>
        <option>Option 2</option>
    </select>
</div>

<!-- Textarea -->
<div class="form-group">
    <label class="form-label">Description</label>
    <textarea class="form-textarea" rows="4"></textarea>
</div>

<!-- Toggle Switch -->
<label class="toggle-switch">
    <input type="checkbox" class="toggle-input">
    <span class="toggle-slider"></span>
    <span class="toggle-label">Enable AI Mode</span>
</label>
```

### 5. **Alerts & Notifications**

```blade
<!-- Success Alert -->
<div class="alert alert-success">
    <svg class="alert-icon"><!-- checkmark --></svg>
    <div class="alert-content">
        <h4 class="alert-title">Success!</h4>
        <p class="alert-message">Your action was completed successfully.</p>
    </div>
    <button class="alert-close">✕</button>
</div>

<!-- Toast Notification (Alpine.js) -->
<div x-data="{ show: true }"
     x-show="show"
     x-init="setTimeout(() => show = false, 3000)"
     class="toast toast-success">
    ✅ {{ session('success') }}
</div>
```

### 6. **Loading States**

```blade
<!-- Skeleton Loader -->
<div class="skeleton-card">
    <div class="skeleton-header"></div>
    <div class="skeleton-line"></div>
    <div class="skeleton-line short"></div>
</div>

<!-- Spinner -->
<div class="spinner-wrapper">
    <div class="spinner"></div>
    <p class="spinner-text">Loading...</p>
</div>

<!-- Progress Bar -->
<div class="progress-bar">
    <div class="progress-fill" style="width: 65%"></div>
    <span class="progress-label">65%</span>
</div>
```

---

## 🎭 Animation Guidelines

### Transitions
```css
/* Standard transitions */
--transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
--transition-base: 200ms cubic-bezier(0.4, 0, 0.2, 1);
--transition-slow: 300ms cubic-bezier(0.4, 0, 0.2, 1);

/* Use for: */
.hover-effect {
    transition: all var(--transition-base);
}

.modal-enter {
    animation: slideUp var(--transition-slow);
}
```

### Keyframes
```css
@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
```

---

## 📱 Responsive Breakpoints

```css
/* Mobile First Approach */
--breakpoint-sm: 640px;   /* Small devices */
--breakpoint-md: 768px;   /* Tablets */
--breakpoint-lg: 1024px;  /* Laptops */
--breakpoint-xl: 1280px;  /* Desktops */
--breakpoint-2xl: 1536px; /* Large screens */

/* Usage */
@media (min-width: 768px) {
    .tool-header {
        flex-direction: row;
    }
}
```

---

## ♿ Accessibility

### ARIA Labels
```blade
<!-- Always add aria-label for icon-only buttons -->
<button aria-label="Delete item" class="btn-icon">
    <svg><!-- trash icon --></svg>
</button>

<!-- Screen reader text -->
<span class="sr-only">Hidden text for screen readers</span>

<!-- Live regions for dynamic updates -->
<div aria-live="polite" aria-atomic="true">
    @if($message)
        {{ $message }}
    @endif
</div>
```

### Focus Management
```css
/* Custom focus ring */
*:focus-visible {
    outline: 2px solid var(--color-primary-500);
    outline-offset: 2px;
}

/* Skip to content link */
.skip-link {
    @apply sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4;
    @apply bg-primary-600 text-white px-4 py-2 rounded;
}
```

---

## 🌙 Dark Mode (Future)

```css
/* Prepara variabili per dark mode */
@media (prefers-color-scheme: dark) {
    :root {
        --bg-primary: #111827;
        --bg-secondary: #1f2937;
        --text-primary: #f9fafb;
        --text-secondary: #d1d5db;
    }
}
```

---

## ✅ Checklist UI/UX per Ogni Tool

- [ ] Segue struttura layout standard
- [ ] Header con titolo, descrizione, CTA
- [ ] Stats cards con icone consistenti
- [ ] Filtri/search bar sempre in alto
- [ ] Tabella responsive con checkbox selection
- [ ] Status badges colorati correttamente
- [ ] Azioni bulk quando items selezionati
- [ ] Modal per create/edit
- [ ] Loading states per operazioni async
- [ ] Toast notifications per feedback
- [ ] Bottone "Tour Guidato" sempre visibile
- [ ] Token usage badge se tool usa AI
- [ ] Copilot button accessibile
- [ ] Aria labels su icon buttons
- [ ] Focus states visibili
- [ ] Responsive mobile-first

---

## 🎨 Figma Design Tokens Export

```json
{
  "colors": {
    "primary": "#6366f1",
    "success": "#10b981",
    "warning": "#f59e0b",
    "error": "#ef4444"
  },
  "spacing": {
    "xs": "0.25rem",
    "sm": "0.5rem",
    "md": "1rem",
    "lg": "1.5rem",
    "xl": "2rem"
  },
  "borderRadius": {
    "sm": "0.375rem",
    "md": "0.5rem",
    "lg": "0.75rem",
    "full": "9999px"
  }
}
```

---

**Next**: Applica questo design system a tutti i tool esistenti e futuri!
