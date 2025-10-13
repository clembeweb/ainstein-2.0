<?php $__env->startSection('title', 'Nuova Campaign'); ?>

<?php $__env->startSection('content'); ?>
<div x-data="{
    isGenerating: false,
    campaignType: '<?php echo e(old('campaign_type', '')); ?>',
    keywords: '<?php echo e(old('target_keywords', '')); ?>',
    get keywordArray() {
        return this.keywords.split(',').map(k => k.trim()).filter(k => k.length > 0);
    }
}">
    <div class="max-w-4xl">
        <!-- Header Card -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg bg-amber-100">
                        <i class="fas fa-bullhorn text-xl text-amber-600"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">Crea Campaign Google Ads</h3>
                    <p class="text-sm text-gray-600 mt-1">L'AI genererà asset pubblicitari ottimizzati per RSA o Performance Max</p>
                </div>
            </div>
        </div>

        <!-- Main Form -->
        <form method="POST" action="<?php echo e(route('tenant.campaigns.store')); ?>" @submit="isGenerating = true" class="bg-white rounded-lg shadow-sm p-6">
            <?php echo csrf_field(); ?>

            <!-- Campaign Name -->
            <div class="mb-6">
                <label for="campaign_name" class="block text-sm font-medium text-gray-700 mb-2">
                    Nome Campaign <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="campaign_name"
                    name="campaign_name"
                    value="<?php echo e(old('campaign_name')); ?>"
                    class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                    placeholder="es. Saldi Estivi 2025 - Orologi"
                    required
                    :disabled="isGenerating"
                >
                <?php $__errorArgs = ['campaign_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>

            <!-- Campaign Type -->
            <div class="mb-6">
                <label for="campaign_type" class="block text-sm font-medium text-gray-700 mb-2">
                    Tipo Campaign <span class="text-red-500">*</span>
                </label>
                <select
                    id="campaign_type"
                    name="campaign_type"
                    x-model="campaignType"
                    class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                    required
                    :disabled="isGenerating"
                >
                    <option value="">Seleziona tipo campaign...</option>
                    <option value="RSA">RSA (Responsive Search Ads)</option>
                    <option value="PMAX">PMAX (Performance Max)</option>
                </select>
                <?php $__errorArgs = ['campaign_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                <!-- Dynamic Type Info -->
                <div x-show="campaignType === 'RSA'" x-transition class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-500 mt-0.5 mr-2"></i>
                        <p class="text-xs text-blue-700">
                            <strong>RSA:</strong> Genera fino a 15 titoli (30 caratteri) e 4 descrizioni (90 caratteri) per annunci di ricerca dinamici.
                        </p>
                    </div>
                </div>
                <div x-show="campaignType === 'PMAX'" x-transition class="mt-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-green-500 mt-0.5 mr-2"></i>
                        <p class="text-xs text-green-700">
                            <strong>PMAX:</strong> Genera titoli brevi (30 caratteri), titoli lunghi (90 caratteri) e descrizioni per campagne Performance Max multi-canale.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Business Description -->
            <div class="mb-6">
                <label for="business_description" class="block text-sm font-medium text-gray-700 mb-2">
                    Descrizione Business <span class="text-red-500">*</span>
                </label>
                <textarea
                    id="business_description"
                    name="business_description"
                    rows="5"
                    class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                    placeholder="Descrivi il tuo business, prodotti o servizi. Sii specifico su cosa ti rende unico."
                    required
                    :disabled="isGenerating"
                ><?php echo e(old('business_description')); ?></textarea>
                <?php $__errorArgs = ['business_description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <p class="mt-1 text-xs text-gray-500">
                    <i class="fas fa-lightbulb mr-1"></i>Esempio: "Orologi svizzeri di lusso realizzati a mano da maestri artigiani. Segnatempo di qualità premium con garanzia a vita."
                </p>
            </div>

            <!-- Target Keywords -->
            <div class="mb-6">
                <label for="target_keywords" class="block text-sm font-medium text-gray-700 mb-2">
                    Parole Chiave Target <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="target_keywords"
                    name="target_keywords"
                    x-model="keywords"
                    class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                    placeholder="es. orologi svizzeri, segnatempo lusso, orologi artigianali"
                    required
                    :disabled="isGenerating"
                >
                <?php $__errorArgs = ['target_keywords'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <p class="mt-1 text-xs text-gray-500">
                    Separa le parole chiave con virgole. Saranno usate per ottimizzare i contenuti della campaign.
                </p>

                <!-- Keywords Preview -->
                <div x-show="keywordArray.length > 0" x-transition class="mt-3 flex flex-wrap gap-2">
                    <template x-for="keyword in keywordArray" :key="keyword">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                            <i class="fas fa-tag mr-1 text-amber-500"></i>
                            <span x-text="keyword"></span>
                        </span>
                    </template>
                </div>
            </div>

            <!-- URL (Optional) -->
            <div class="mb-6">
                <label for="url" class="block text-sm font-medium text-gray-700 mb-2">
                    URL Finale
                </label>
                <input
                    type="url"
                    id="url"
                    name="url"
                    value="<?php echo e(old('url', 'https://')); ?>"
                    class="w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500"
                    placeholder="https://www.esempio.com"
                    :disabled="isGenerating"
                >
                <?php $__errorArgs = ['url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <p class="mt-1 text-xs text-gray-500">
                    URL di destinazione per la campaign (opzionale)
                </p>
            </div>

            <!-- Language (Hidden, default IT) -->
            <input type="hidden" name="language" value="it">

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-6 border-t">
                <a href="<?php echo e(route('tenant.campaigns.index')); ?>"
                   class="text-gray-600 hover:text-gray-900 font-medium"
                   :class="{ 'pointer-events-none opacity-50': isGenerating }">
                    <i class="fas fa-arrow-left mr-2"></i>Torna alle campaigns
                </a>

                <button type="submit"
                        class="bg-amber-500 hover:bg-amber-600 text-white px-6 py-2.5 rounded-lg font-medium inline-flex items-center shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="isGenerating">
                    <span x-show="!isGenerating" class="inline-flex items-center">
                        <i class="fas fa-magic mr-2"></i>Genera Campaign
                    </span>
                    <span x-show="isGenerating" class="inline-flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Generazione in corso...
                    </span>
                </button>
            </div>
        </form>

        <!-- Info Box -->
        <div class="mt-6 bg-amber-50 border border-amber-200 rounded-lg p-5">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-lightbulb text-2xl text-amber-500"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-sm font-semibold text-amber-900 mb-2">Come Funziona</h3>
                    <ul class="text-sm text-amber-800 space-y-1.5">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-amber-600 mr-2 mt-0.5"></i>
                            <span>L'AI genera asset ottimizzati basandosi sui tuoi input</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-amber-600 mr-2 mt-0.5"></i>
                            <span>RSA crea titoli e descrizioni per annunci di ricerca</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-amber-600 mr-2 mt-0.5"></i>
                            <span>PMAX crea asset completi per campagne Performance Max</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-amber-600 mr-2 mt-0.5"></i>
                            <span>Tutti i contenuti rispettano le best practice di Google Ads</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\ainstein-3\resources\views/tenant/campaigns/create.blade.php ENDPATH**/ ?>