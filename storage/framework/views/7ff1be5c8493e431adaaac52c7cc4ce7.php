

<?php $__env->startSection('title', 'OAuth Settings - Social Login'); ?>

<?php $__env->startSection('content'); ?>
<div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">OAuth Settings</h1>
        <p class="mt-2 text-gray-600">Configura il login social per il tuo workspace</p>
    </div>

    <?php if(session('success')): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
            <p class="font-medium">Successo!</p>
            <p><?php echo e(session('success')); ?></p>
        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
            <p class="font-medium">Errore</p>
            <p><?php echo e(session('error')); ?></p>
        </div>
    <?php endif; ?>

    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Google OAuth Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-red-500 to-red-600 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <i class="fab fa-google text-white text-2xl"></i>
                        <h2 class="text-xl font-semibold text-white">Google OAuth</h2>
                    </div>
                    <?php if($googleProvider->isAvailable()): ?>
                        <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                            <i class="fas fa-check-circle mr-1"></i> Attivo
                        </span>
                    <?php elseif($googleProvider->isConfigured()): ?>
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">
                            <i class="fas fa-pause-circle mr-1"></i> Configurato
                        </span>
                    <?php else: ?>
                        <span class="px-3 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">
                            <i class="fas fa-times-circle mr-1"></i> Non configurato
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <form action="<?php echo e(route('tenant.settings.oauth.google.update')); ?>" method="POST" class="p-6">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Client ID
                            <span class="text-gray-400 text-xs ml-2">(dalla Google Cloud Console)</span>
                        </label>
                        <input type="text" name="client_id"
                               value="<?php echo e($googleProvider->client_id); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                               placeholder="123456789-xxxxx.apps.googleusercontent.com">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Client Secret
                            <span class="text-gray-400 text-xs ml-2">(mantenuto criptato)</span>
                        </label>
                        <input type="password" name="client_secret"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                               placeholder="<?php echo e($googleProvider->client_secret ? '••••••••••••••••' : 'GOCSPX-xxxxxxxxxxxxx'); ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Callback URL
                            <span class="text-gray-400 text-xs ml-2">(da configurare in Google)</span>
                        </label>
                        <div class="flex items-center space-x-2">
                            <input type="text" readonly
                                   value="<?php echo e($googleProvider->getCallbackUrl()); ?>"
                                   class="flex-1 px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-600"
                                   id="google-callback-url">
                            <button type="button" onclick="copyToClipboard('google-callback-url')"
                                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="google_active" value="1"
                               <?php echo e($googleProvider->is_active ? 'checked' : ''); ?>

                               <?php echo e(!$googleProvider->isConfigured() ? 'disabled' : ''); ?>

                               class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                        <label for="google_active" class="ml-2 block text-sm text-gray-900">
                            Abilita login con Google
                        </label>
                    </div>

                    <?php if($googleProvider->test_status): ?>
                        <div class="p-3 rounded-lg <?php echo e($googleProvider->test_status == 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'); ?>">
                            <p class="text-sm font-medium">
                                Test Status: <?php echo e(ucfirst($googleProvider->test_status)); ?>

                            </p>
                            <?php if($googleProvider->test_message): ?>
                                <p class="text-xs mt-1"><?php echo e($googleProvider->test_message); ?></p>
                            <?php endif; ?>
                            <?php if($googleProvider->last_tested_at): ?>
                                <p class="text-xs mt-1 text-gray-500">
                                    Testato: <?php echo e($googleProvider->last_tested_at->diffForHumans()); ?>

                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="flex items-center space-x-3">
                        <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition">
                            <i class="fas fa-save mr-2"></i> Salva Configurazione
                        </button>

                        <?php if($googleProvider->isConfigured()): ?>
                            <button type="button" onclick="testOAuth('google')"
                                    class="px-4 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 font-medium rounded-lg transition">
                                <i class="fas fa-vial mr-2"></i> Test
                            </button>

                            <a href="<?php echo e(route('tenant.settings.oauth.clear', 'google')); ?>"
                               onclick="return confirm('Sei sicuro di voler cancellare la configurazione Google?')"
                               class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition">
                                <i class="fas fa-trash mr-2"></i> Cancella
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <div class="bg-gray-50 px-6 py-4 border-t">
                <p class="text-xs text-gray-600">
                    <i class="fas fa-info-circle mr-1"></i>
                    Configura OAuth 2.0 dalla
                    <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-blue-600 hover:underline">
                        Google Cloud Console <i class="fas fa-external-link-alt text-xs"></i>
                    </a>
                </p>
            </div>
        </div>

        <!-- Facebook OAuth Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <i class="fab fa-facebook text-white text-2xl"></i>
                        <h2 class="text-xl font-semibold text-white">Facebook OAuth</h2>
                    </div>
                    <?php if($facebookProvider->isAvailable()): ?>
                        <span class="px-3 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">
                            <i class="fas fa-check-circle mr-1"></i> Attivo
                        </span>
                    <?php elseif($facebookProvider->isConfigured()): ?>
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">
                            <i class="fas fa-pause-circle mr-1"></i> Configurato
                        </span>
                    <?php else: ?>
                        <span class="px-3 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full">
                            <i class="fas fa-times-circle mr-1"></i> Non configurato
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <form action="<?php echo e(route('tenant.settings.oauth.facebook.update')); ?>" method="POST" class="p-6">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            App ID
                            <span class="text-gray-400 text-xs ml-2">(dalla Facebook App Dashboard)</span>
                        </label>
                        <input type="text" name="client_id"
                               value="<?php echo e($facebookProvider->client_id); ?>"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                               placeholder="1234567890123456">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            App Secret
                            <span class="text-gray-400 text-xs ml-2">(mantenuto criptato)</span>
                        </label>
                        <input type="password" name="client_secret"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent"
                               placeholder="<?php echo e($facebookProvider->client_secret ? '••••••••••••••••' : 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'); ?>">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Callback URL
                            <span class="text-gray-400 text-xs ml-2">(da configurare in Facebook)</span>
                        </label>
                        <div class="flex items-center space-x-2">
                            <input type="text" readonly
                                   value="<?php echo e($facebookProvider->getCallbackUrl()); ?>"
                                   class="flex-1 px-4 py-2 bg-gray-50 border border-gray-300 rounded-lg text-gray-600"
                                   id="facebook-callback-url">
                            <button type="button" onclick="copyToClipboard('facebook-callback-url')"
                                    class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="facebook_active" value="1"
                               <?php echo e($facebookProvider->is_active ? 'checked' : ''); ?>

                               <?php echo e(!$facebookProvider->isConfigured() ? 'disabled' : ''); ?>

                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="facebook_active" class="ml-2 block text-sm text-gray-900">
                            Abilita login con Facebook
                        </label>
                    </div>

                    <?php if($facebookProvider->test_status): ?>
                        <div class="p-3 rounded-lg <?php echo e($facebookProvider->test_status == 'success' ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'); ?>">
                            <p class="text-sm font-medium">
                                Test Status: <?php echo e(ucfirst($facebookProvider->test_status)); ?>

                            </p>
                            <?php if($facebookProvider->test_message): ?>
                                <p class="text-xs mt-1"><?php echo e($facebookProvider->test_message); ?></p>
                            <?php endif; ?>
                            <?php if($facebookProvider->last_tested_at): ?>
                                <p class="text-xs mt-1 text-gray-500">
                                    Testato: <?php echo e($facebookProvider->last_tested_at->diffForHumans()); ?>

                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="flex items-center space-x-3">
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                            <i class="fas fa-save mr-2"></i> Salva Configurazione
                        </button>

                        <?php if($facebookProvider->isConfigured()): ?>
                            <button type="button" onclick="testOAuth('facebook')"
                                    class="px-4 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 font-medium rounded-lg transition">
                                <i class="fas fa-vial mr-2"></i> Test
                            </button>

                            <a href="<?php echo e(route('tenant.settings.oauth.clear', 'facebook')); ?>"
                               onclick="return confirm('Sei sicuro di voler cancellare la configurazione Facebook?')"
                               class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition">
                                <i class="fas fa-trash mr-2"></i> Cancella
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <div class="bg-gray-50 px-6 py-4 border-t">
                <p class="text-xs text-gray-600">
                    <i class="fas fa-info-circle mr-1"></i>
                    Configura l'app OAuth dalla
                    <a href="https://developers.facebook.com/apps" target="_blank" class="text-blue-600 hover:underline">
                        Facebook Developers Dashboard <i class="fas fa-external-link-alt text-xs"></i>
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- Instructions Section -->
    <div class="mt-8 bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-4">
            <h2 class="text-xl font-semibold text-white">
                <i class="fas fa-book mr-2"></i> Guida alla Configurazione
            </h2>
        </div>

        <div class="p-6 space-y-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-3">
                    <i class="fab fa-google text-red-500 mr-2"></i> Configurazione Google OAuth
                </h3>
                <ol class="list-decimal list-inside space-y-2 text-gray-700">
                    <li>Vai alla <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-blue-600 hover:underline">Google Cloud Console</a></li>
                    <li>Crea un nuovo progetto o seleziona uno esistente</li>
                    <li>Clicca su "Create Credentials" → "OAuth client ID"</li>
                    <li>Scegli "Web application" come tipo di applicazione</li>
                    <li>Aggiungi il Callback URL copiato sopra in "Authorized redirect URIs"</li>
                    <li>Copia Client ID e Client Secret e incollali nei campi sopra</li>
                    <li>Abilita Google+ API nel tuo progetto Google Cloud</li>
                </ol>
            </div>

            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-3">
                    <i class="fab fa-facebook text-blue-600 mr-2"></i> Configurazione Facebook OAuth
                </h3>
                <ol class="list-decimal list-inside space-y-2 text-gray-700">
                    <li>Vai alla <a href="https://developers.facebook.com/apps" target="_blank" class="text-blue-600 hover:underline">Facebook Developers</a></li>
                    <li>Clicca su "Create App" e scegli "Consumer"</li>
                    <li>Compila i dettagli dell'app</li>
                    <li>Nella dashboard dell'app, vai su "Facebook Login" → "Settings"</li>
                    <li>Aggiungi il Callback URL copiato sopra in "Valid OAuth Redirect URIs"</li>
                    <li>Copia App ID e App Secret dalla dashboard e incollali nei campi sopra</li>
                    <li>Assicurati che l'app sia in modalità "Live" per il login pubblico</li>
                </ol>
            </div>

            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Importante:</strong> Dopo aver configurato OAuth, assicurati di testare la configurazione
                            usando il pulsante "Test" prima di abilitare il login social per gli utenti.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    element.select();
    element.setSelectionRange(0, 99999);
    document.execCommand('copy');

    // Show feedback
    const button = event.target.closest('button');
    const originalHTML = button.innerHTML;
    button.innerHTML = '<i class="fas fa-check text-green-600"></i>';
    setTimeout(() => {
        button.innerHTML = originalHTML;
    }, 2000);
}

function testOAuth(provider) {
    fetch(`<?php echo e(url('/dashboard/settings/oauth/test')); ?>/${provider}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ Errore durante il test: ' + error.message);
    });
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\ainstein-3\resources\views/tenant/settings/oauth.blade.php ENDPATH**/ ?>