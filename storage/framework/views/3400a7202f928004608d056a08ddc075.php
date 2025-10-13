<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $__env->yieldContent('title', 'Tenant Dashboard'); ?> - <?php echo e(config('app.name')); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="bg-white shadow-lg w-64 fixed h-full">
            <div class="p-6 border-b">
                <h1 class="text-xl font-bold text-gray-800"><?php echo e(Auth::user()->tenant->name); ?></h1>
                <p class="text-sm text-gray-600"><?php echo e(Auth::user()->name); ?></p>
            </div>

            <nav class="mt-6">
                <a href="<?php echo e(route('tenant.dashboard')); ?>" class="flex items-center px-6 py-3 text-gray-700 hover:bg-amber-50 hover:text-amber-600 <?php echo e(request()->routeIs('tenant.dashboard') ? 'bg-amber-50 text-amber-600 border-r-2 border-amber-600' : ''); ?>">
                    <i class="fas fa-chart-bar mr-3"></i>
                    Dashboard
                </a>

                <a href="<?php echo e(route('tenant.campaigns.index')); ?>" class="flex items-center px-6 py-3 text-gray-700 hover:bg-amber-50 hover:text-amber-600 <?php echo e(request()->routeIs('tenant.campaigns.*') ? 'bg-amber-50 text-amber-600 border-r-2 border-amber-600' : ''); ?>">
                    <i class="fas fa-bullhorn mr-3"></i>
                    Campaigns
                </a>

                <a href="<?php echo e(route('tenant.content.index')); ?>" class="flex items-center px-6 py-3 text-gray-700 hover:bg-amber-50 hover:text-amber-600 <?php echo e(request()->routeIs('tenant.content.*') ? 'bg-amber-50 text-amber-600 border-r-2 border-amber-600' : ''); ?>">
                    <i class="fas fa-magic mr-3"></i>
                    Content Generation
                </a>

                <a href="<?php echo e(route('tenant.prompts.index')); ?>" class="flex items-center px-6 py-3 text-gray-700 hover:bg-amber-50 hover:text-amber-600 <?php echo e(request()->routeIs('tenant.prompts.*') ? 'bg-amber-50 text-amber-600 border-r-2 border-amber-600' : ''); ?>">
                    <i class="fas fa-edit mr-3"></i>
                    Prompts
                </a>

                <a href="<?php echo e(route('tenant.api-keys.index')); ?>" class="flex items-center px-6 py-3 text-gray-700 hover:bg-amber-50 hover:text-amber-600 <?php echo e(request()->routeIs('tenant.api-keys.*') ? 'bg-amber-50 text-amber-600 border-r-2 border-amber-600' : ''); ?>">
                    <i class="fas fa-key mr-3"></i>
                    API Keys
                </a>

                <a href="<?php echo e(route('tenant.settings.oauth.index')); ?>" class="flex items-center px-6 py-3 text-gray-700 hover:bg-amber-50 hover:text-amber-600 <?php echo e(request()->routeIs('tenant.settings.oauth.*') ? 'bg-amber-50 text-amber-600 border-r-2 border-amber-600' : ''); ?>">
                    <i class="fas fa-users-cog mr-3"></i>
                    OAuth Settings
                </a>

                <a href="<?php echo e(route('tenant.settings')); ?>" class="flex items-center px-6 py-3 text-gray-700 hover:bg-amber-50 hover:text-amber-600 <?php echo e(request()->routeIs('tenant.settings') ? 'bg-amber-50 text-amber-600 border-r-2 border-amber-600' : ''); ?>">
                    <i class="fas fa-cog mr-3"></i>
                    Settings
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="ml-64 flex-1">
            <!-- Top Navigation -->
            <header class="bg-white shadow-sm border-b px-6 py-4">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-semibold text-gray-800"><?php echo $__env->yieldContent('page-title', 'Dashboard'); ?></h2>

                    <div class="flex items-center space-x-4">
                        <!-- Token Usage -->
                        <div class="flex items-center space-x-2 bg-gray-100 rounded-lg px-3 py-1">
                            <i class="fas fa-coins text-amber-500"></i>
                            <span class="text-sm text-gray-600">
                                <?php echo e(number_format(Auth::user()->tenant->tokens_used_current)); ?>/<?php echo e(number_format(Auth::user()->tenant->tokens_monthly_limit)); ?> tokens
                            </span>
                        </div>

                        <!-- User Menu -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 text-gray-700 hover:text-gray-900">
                                <div class="w-8 h-8 bg-amber-500 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                    <?php echo e(substr(Auth::user()->name, 0, 1)); ?>

                                </div>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>

                            <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10">
                                <a href="<?php echo e(route('tenant.settings')); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                <form method="POST" action="<?php echo e(route('logout')); ?>">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-6">
                <?php if(session('success')): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                        <?php echo e(session('success')); ?>

                    </div>
                <?php endif; ?>

                <?php if(session('error')): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                        <?php echo e(session('error')); ?>

                    </div>
                <?php endif; ?>

                <?php echo $__env->yieldContent('content'); ?>
            </main>
        </div>
    </div>
</body>
</html><?php /**PATH C:\laragon\www\ainstein-3\resources\views/layouts/tenant.blade.php ENDPATH**/ ?>