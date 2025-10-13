<?php $__env->startSection('title', 'Content Generator'); ?>

<?php $__env->startSection('content'); ?>
<div class="py-12" x-data="{ activeTab: '<?php echo e($activeTab); ?>' }">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Content Generator</h1>
                        <p class="text-gray-600 mt-1">Manage your pages, content generations, and prompts</p>
                    </div>
                    <button onclick="startContentGeneratorOnboarding()" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-700 hover:to-blue-700 text-white rounded-lg font-medium shadow-md transition-all duration-200 hover:shadow-lg text-sm">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Tour Guidato
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                    <a href="?tab=pages"
                       @click.prevent="activeTab = 'pages'; window.history.pushState({}, '', '?tab=pages')"
                       :class="activeTab === 'pages' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                       class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm cursor-pointer">
                        <i class="fas fa-file-alt mr-2"></i>
                        Pages
                        <span class="ml-2 py-0.5 px-2 rounded-full text-xs font-medium bg-gray-100"><?php echo e($pages->total()); ?></span>
                    </a>
                    <a href="?tab=generations"
                       @click.prevent="activeTab = 'generations'; window.history.pushState({}, '', '?tab=generations')"
                       :class="activeTab === 'generations' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                       class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm cursor-pointer">
                        <i class="fas fa-robot mr-2"></i>
                        Generations
                        <span class="ml-2 py-0.5 px-2 rounded-full text-xs font-medium bg-gray-100"><?php echo e($generations->total()); ?></span>
                    </a>
                    <a href="?tab=prompts"
                       @click.prevent="activeTab = 'prompts'; window.history.pushState({}, '', '?tab=prompts')"
                       :class="activeTab === 'prompts' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                       class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm cursor-pointer">
                        <i class="fas fa-scroll mr-2"></i>
                        Prompts
                        <span class="ml-2 py-0.5 px-2 rounded-full text-xs font-medium bg-gray-100"><?php echo e($prompts->total()); ?></span>
                    </a>
                </nav>
            </div>
        </div>

        <!-- Pages Tab -->
        <div x-show="activeTab === 'pages'" x-cloak>
            <?php echo $__env->make('tenant.content-generator.tabs.pages', ['pages' => $pages, 'statuses' => $statuses, 'categories' => $categories], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>

        <!-- Generations Tab -->
        <div x-show="activeTab === 'generations'" x-cloak>
            <?php echo $__env->make('tenant.content-generator.tabs.generations', ['generations' => $generations, 'generationStatuses' => $generationStatuses], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>

        <!-- Prompts Tab -->
        <div x-show="activeTab === 'prompts'" x-cloak>
            <?php echo $__env->make('tenant.content-generator.tabs.prompts', ['prompts' => $prompts, 'promptCategories' => $promptCategories], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\ainstein-3\resources\views/tenant/content-generator/index.blade.php ENDPATH**/ ?>