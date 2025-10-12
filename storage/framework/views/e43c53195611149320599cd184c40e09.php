<?php $__env->startSection('title', $campaign->name); ?>

<?php $__env->startSection('content'); ?>
<div x-data="{
    activeTab: 'assets',
    showRegenerateModal: false,
    showExportMenu: false,
    copiedId: null,
    copyToClipboard(text, id) {
        navigator.clipboard.writeText(text).then(() => {
            this.copiedId = id;
            setTimeout(() => this.copiedId = null, 2000);
        });
    }
}">
    <!-- Header Card -->
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex-1">
                <div class="flex items-center space-x-3 mb-2">
                    <h3 class="text-2xl font-bold text-gray-900"><?php echo e($campaign->name); ?></h3>
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold
                        <?php echo e(strtolower($campaign->type) === 'pmax' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'); ?>">
                        <?php echo e(strtoupper($campaign->type)); ?>

                    </span>
                </div>
                <p class="text-gray-600"><?php echo e($campaign->info); ?></p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-2 mt-6 pt-6 border-t">
            <a href="<?php echo e(route('tenant.campaigns.index')); ?>"
               class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Torna alle campaigns
            </a>

            <a href="<?php echo e(route('tenant.campaigns.edit', $campaign->id)); ?>"
               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                <i class="fas fa-edit mr-2"></i>Modifica
            </a>

            <?php if($campaign->assets->count() > 0): ?>
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors">
                        <i class="fas fa-download mr-2"></i>Esporta
                        <i class="fas fa-chevron-down ml-2 text-sm"></i>
                    </button>

                    <!-- Export Dropdown -->
                    <div x-show="open"
                         @click.away="open = false"
                         x-transition
                         class="absolute left-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10">
                        <a href="<?php echo e(route('tenant.campaigns.export', ['id' => $campaign->id, 'format' => 'csv'])); ?>"
                           class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-file-csv text-green-600 w-5 mr-3"></i>
                            <div>
                                <div class="font-medium">Esporta CSV</div>
                                <div class="text-xs text-gray-500">Formato tabellare standard</div>
                            </div>
                        </a>
                        <a href="<?php echo e(route('tenant.campaigns.export', ['id' => $campaign->id, 'format' => 'google-ads'])); ?>"
                           class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fab fa-google text-blue-600 w-5 mr-3"></i>
                            <div>
                                <div class="font-medium">Google Ads CSV</div>
                                <div class="text-xs text-gray-500">Pronto per l'upload</div>
                            </div>
                        </a>
                    </div>
                </div>

                <button @click="showRegenerateModal = true"
                        class="inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-medium transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i>Rigenera Asset
                </button>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('tenant.campaigns.destroy', $campaign->id)); ?>" class="inline ml-auto">
                <?php echo csrf_field(); ?>
                <?php echo method_field('DELETE'); ?>
                <button type="submit"
                        onclick="return confirm('Sei sicuro di voler eliminare questa campaign e tutti i suoi asset?');"
                        class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                    <i class="fas fa-trash mr-2"></i>Elimina
                </button>
            </form>
        </div>

        <!-- Campaign Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6 pt-6 border-t">
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-check-circle text-2xl text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-green-700">Asset Generati</p>
                        <p class="text-2xl font-bold text-green-900"><?php echo e($campaign->assets->count()); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-lg p-4 border border-amber-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-12 h-12 bg-amber-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-coins text-2xl text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-amber-700">Token Utilizzati</p>
                        <p class="text-2xl font-bold text-amber-900"><?php echo e(number_format($campaign->tokens_used ?? 0)); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0 w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-calendar text-2xl text-white"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-blue-700">Data Creazione</p>
                        <p class="text-lg font-bold text-blue-900"><?php echo e($campaign->created_at->format('d/m/Y')); ?></p>
                        <p class="text-xs text-blue-600"><?php echo e($campaign->created_at->format('H:i')); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Target Keywords -->
        <?php if($campaign->keywords): ?>
            <?php
                $keywordsArray = is_string($campaign->keywords) ? array_map('trim', explode(',', $campaign->keywords)) : $campaign->keywords;
            ?>
            <div class="mt-6 pt-6 border-t">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">
                    <i class="fas fa-tags mr-2 text-amber-500"></i>Parole Chiave Target
                </h4>
                <div class="flex flex-wrap gap-2">
                    <?php $__currentLoopData = $keywordsArray; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $keyword): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-50 text-blue-700 border border-blue-200">
                            <i class="fas fa-tag mr-2 text-xs text-blue-500"></i><?php echo e($keyword); ?>

                        </span>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tabs Navigation -->
    <div class="bg-white rounded-t-lg shadow-sm border-b">
        <div class="flex space-x-1 px-6">
            <button @click="activeTab = 'assets'"
                    :class="activeTab === 'assets' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-4 py-3 border-b-2 font-medium text-sm transition-colors">
                <i class="fas fa-layer-group mr-2"></i>Asset Generati
            </button>
            <button @click="activeTab = 'details'"
                    :class="activeTab === 'details' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-4 py-3 border-b-2 font-medium text-sm transition-colors">
                <i class="fas fa-info-circle mr-2"></i>Dettagli Campaign
            </button>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="bg-white rounded-b-lg shadow-sm p-6">
        <!-- Assets Tab -->
        <div x-show="activeTab === 'assets'" x-transition>
            <?php if($campaign->assets->count() > 0): ?>
                <?php
                    $asset = $campaign->assets->first();
                ?>

                <?php if(strtolower($campaign->type) === 'rsa'): ?>
                    <!-- RSA Assets -->
                    <div class="space-y-6">
                        <!-- Titles -->
                        <?php if(!empty($asset->titles)): ?>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-600 rounded-lg mr-3">
                                        <i class="fas fa-heading"></i>
                                    </span>
                                    Titoli (<?php echo e(count($asset->titles)); ?>)
                                </h4>
                                <div class="grid gap-3">
                                    <?php $__currentLoopData = $asset->titles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $title): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 hover:bg-blue-50 transition-all">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center mb-2">
                                                        <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-500 text-white text-xs font-bold rounded mr-3">
                                                            <?php echo e($index + 1); ?>

                                                        </span>
                                                        <span class="text-xs font-medium text-gray-500">
                                                            <?php echo e(mb_strlen($title)); ?>/30 caratteri
                                                        </span>
                                                    </div>
                                                    <p class="text-gray-900 font-medium ml-9"><?php echo e($title); ?></p>
                                                </div>
                                                <button @click="copyToClipboard('<?php echo e(addslashes($title)); ?>', 'title-<?php echo e($index); ?>')"
                                                        class="ml-4 px-3 py-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors">
                                                    <i class="fas" :class="copiedId === 'title-<?php echo e($index); ?>' ? 'fa-check' : 'fa-copy'"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Descriptions -->
                        <?php if(!empty($asset->descriptions)): ?>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 bg-green-100 text-green-600 rounded-lg mr-3">
                                        <i class="fas fa-align-left"></i>
                                    </span>
                                    Descrizioni (<?php echo e(count($asset->descriptions)); ?>)
                                </h4>
                                <div class="grid gap-3">
                                    <?php $__currentLoopData = $asset->descriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $description): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="border border-gray-200 rounded-lg p-4 hover:border-green-300 hover:bg-green-50 transition-all">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center mb-2">
                                                        <span class="inline-flex items-center justify-center w-6 h-6 bg-green-500 text-white text-xs font-bold rounded mr-3">
                                                            <?php echo e($index + 1); ?>

                                                        </span>
                                                        <span class="text-xs font-medium text-gray-500">
                                                            <?php echo e(mb_strlen($description)); ?>/90 caratteri
                                                        </span>
                                                    </div>
                                                    <p class="text-gray-900 ml-9"><?php echo e($description); ?></p>
                                                </div>
                                                <button @click="copyToClipboard('<?php echo e(addslashes($description)); ?>', 'desc-<?php echo e($index); ?>')"
                                                        class="ml-4 px-3 py-2 text-green-600 hover:bg-green-100 rounded-lg transition-colors">
                                                    <i class="fas" :class="copiedId === 'desc-<?php echo e($index); ?>' ? 'fa-check' : 'fa-copy'"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <!-- PMAX Assets -->
                    <div class="space-y-6">
                        <!-- Short Titles -->
                        <?php if(!empty($asset->titles)): ?>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 text-blue-600 rounded-lg mr-3">
                                        <i class="fas fa-heading"></i>
                                    </span>
                                    Titoli Brevi (<?php echo e(count($asset->titles)); ?>)
                                </h4>
                                <div class="grid gap-3">
                                    <?php $__currentLoopData = $asset->titles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $title): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 hover:bg-blue-50 transition-all">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center mb-2">
                                                        <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-500 text-white text-xs font-bold rounded mr-3">
                                                            <?php echo e($index + 1); ?>

                                                        </span>
                                                        <span class="text-xs font-medium text-gray-500">
                                                            <?php echo e(mb_strlen($title)); ?>/30 caratteri
                                                        </span>
                                                    </div>
                                                    <p class="text-gray-900 font-medium ml-9"><?php echo e($title); ?></p>
                                                </div>
                                                <button @click="copyToClipboard('<?php echo e(addslashes($title)); ?>', 'short-<?php echo e($index); ?>')"
                                                        class="ml-4 px-3 py-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors">
                                                    <i class="fas" :class="copiedId === 'short-<?php echo e($index); ?>' ? 'fa-check' : 'fa-copy'"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Long Titles -->
                        <?php if(!empty($asset->long_titles)): ?>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 bg-purple-100 text-purple-600 rounded-lg mr-3">
                                        <i class="fas fa-text-width"></i>
                                    </span>
                                    Titoli Lunghi (<?php echo e(count($asset->long_titles)); ?>)
                                </h4>
                                <div class="grid gap-3">
                                    <?php $__currentLoopData = $asset->long_titles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $longTitle): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="border border-gray-200 rounded-lg p-4 hover:border-purple-300 hover:bg-purple-50 transition-all">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center mb-2">
                                                        <span class="inline-flex items-center justify-center w-6 h-6 bg-purple-500 text-white text-xs font-bold rounded mr-3">
                                                            <?php echo e($index + 1); ?>

                                                        </span>
                                                        <span class="text-xs font-medium text-gray-500">
                                                            <?php echo e(mb_strlen($longTitle)); ?>/90 caratteri
                                                        </span>
                                                    </div>
                                                    <p class="text-gray-900 font-medium ml-9"><?php echo e($longTitle); ?></p>
                                                </div>
                                                <button @click="copyToClipboard('<?php echo e(addslashes($longTitle)); ?>', 'long-<?php echo e($index); ?>')"
                                                        class="ml-4 px-3 py-2 text-purple-600 hover:bg-purple-100 rounded-lg transition-colors">
                                                    <i class="fas" :class="copiedId === 'long-<?php echo e($index); ?>' ? 'fa-check' : 'fa-copy'"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Descriptions -->
                        <?php if(!empty($asset->descriptions)): ?>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 bg-green-100 text-green-600 rounded-lg mr-3">
                                        <i class="fas fa-align-left"></i>
                                    </span>
                                    Descrizioni (<?php echo e(count($asset->descriptions)); ?>)
                                </h4>
                                <div class="grid gap-3">
                                    <?php $__currentLoopData = $asset->descriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $description): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="border border-gray-200 rounded-lg p-4 hover:border-green-300 hover:bg-green-50 transition-all">
                                            <div class="flex items-start justify-between">
                                                <div class="flex-1">
                                                    <div class="flex items-center mb-2">
                                                        <span class="inline-flex items-center justify-center w-6 h-6 bg-green-500 text-white text-xs font-bold rounded mr-3">
                                                            <?php echo e($index + 1); ?>

                                                        </span>
                                                        <span class="text-xs font-medium text-gray-500">
                                                            <?php echo e(mb_strlen($description)); ?>/90 caratteri
                                                        </span>
                                                    </div>
                                                    <p class="text-gray-900 ml-9"><?php echo e($description); ?></p>
                                                </div>
                                                <button @click="copyToClipboard('<?php echo e(addslashes($description)); ?>', 'pmax-desc-<?php echo e($index); ?>')"
                                                        class="ml-4 px-3 py-2 text-green-600 hover:bg-green-100 rounded-lg transition-colors">
                                                    <i class="fas" :class="copiedId === 'pmax-desc-<?php echo e($index); ?>' ? 'fa-check' : 'fa-copy'"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Empty State -->
                <div class="text-center py-16">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-4">
                        <i class="fas fa-inbox text-4xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Nessun asset generato</h3>
                    <p class="text-gray-600">Gli asset verranno visualizzati qui una volta generati.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Details Tab -->
        <div x-show="activeTab === 'details'" x-transition>
            <div class="space-y-4">
                <div class="border-b pb-4">
                    <label class="text-sm font-semibold text-gray-700">Nome Campaign</label>
                    <p class="mt-1 text-gray-900"><?php echo e($campaign->name); ?></p>
                </div>

                <div class="border-b pb-4">
                    <label class="text-sm font-semibold text-gray-700">Tipo</label>
                    <p class="mt-1">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            <?php echo e(strtolower($campaign->type) === 'pmax' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'); ?>">
                            <?php echo e(strtoupper($campaign->type)); ?>

                        </span>
                    </p>
                </div>

                <div class="border-b pb-4">
                    <label class="text-sm font-semibold text-gray-700">Descrizione Business</label>
                    <p class="mt-1 text-gray-900"><?php echo e($campaign->info); ?></p>
                </div>

                <?php if($campaign->url): ?>
                    <div class="border-b pb-4">
                        <label class="text-sm font-semibold text-gray-700">URL Finale</label>
                        <p class="mt-1">
                            <a href="<?php echo e($campaign->url); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline">
                                <?php echo e($campaign->url); ?> <i class="fas fa-external-link-alt text-xs ml-1"></i>
                            </a>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="border-b pb-4">
                    <label class="text-sm font-semibold text-gray-700">Lingua</label>
                    <p class="mt-1 text-gray-900"><?php echo e(strtoupper($campaign->language ?? 'it')); ?></p>
                </div>

                <div class="border-b pb-4">
                    <label class="text-sm font-semibold text-gray-700">Data Creazione</label>
                    <p class="mt-1 text-gray-900"><?php echo e($campaign->created_at->format('d/m/Y H:i')); ?></p>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700">Ultimo Aggiornamento</label>
                    <p class="mt-1 text-gray-900"><?php echo e($campaign->updated_at->format('d/m/Y H:i')); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Regenerate Modal -->
    <div x-show="showRegenerateModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4"
         style="display: none;">
        <div @click.away="showRegenerateModal = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-4"
             class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-start mb-4">
                <div class="flex-shrink-0 w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-amber-600 text-xl"></i>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-lg font-semibold text-gray-900">Rigenera Asset</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Sei sicuro di voler rigenerare gli asset? Questa operazione:
                    </p>
                    <ul class="mt-2 text-sm text-gray-600 space-y-1">
                        <li class="flex items-start">
                            <i class="fas fa-times-circle text-red-500 mr-2 mt-0.5"></i>
                            <span>Eliminerà tutti gli asset attuali</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-coins text-amber-500 mr-2 mt-0.5"></i>
                            <span>Consumerà nuovi token AI</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-sync-alt text-blue-500 mr-2 mt-0.5"></i>
                            <span>Creerà nuovi asset basati sulle stesse informazioni</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <button @click="showRegenerateModal = false"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors">
                    Annulla
                </button>
                <form method="POST" action="<?php echo e(route('tenant.campaigns.regenerate', $campaign->id)); ?>" class="inline">
                    <?php echo csrf_field(); ?>
                    <button type="submit"
                            class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg font-medium transition-colors">
                        <i class="fas fa-sync-alt mr-2"></i>Rigenera
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Notification for Copy -->
    <div x-show="copiedId !== null"
         x-transition
         class="fixed bottom-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2 z-50">
        <i class="fas fa-check-circle"></i>
        <span>Copiato negli appunti!</span>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\ainstein-3\resources\views/tenant/campaigns/show.blade.php ENDPATH**/ ?>