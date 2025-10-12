<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Welcome Header -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Welcome back, <?php echo e(Auth::user()->name); ?>!</h1>
                        <p class="text-gray-600"><?php echo e($tenant->name); ?> • <?php echo e(ucfirst($planInfo['name'])); ?> Plan</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <div class="text-sm text-gray-500">Token Usage</div>
                            <div class="text-lg font-semibold"><?php echo e(number_format($tokenStats['usage_percent'] ?? 0, 1)); ?>%</div>
                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo e($tokenStats['usage_percent'] ?? 0); ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tools Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Content Generator Tool -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">Content Generator</h3>
                                <p class="text-sm text-gray-600">AI-powered content generation</p>
                            </div>
                        </div>
                        <a href="<?php echo e(route('tenant.content.index')); ?>" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                            View All →
                        </a>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <div class="text-2xl font-bold text-gray-900"><?php echo e($stats['total_pages'] ?? 0); ?></div>
                            <div class="text-sm text-gray-600">Pages</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900"><?php echo e($stats['total_generations'] ?? 0); ?></div>
                            <div class="text-sm text-gray-600">Generations</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900"><?php echo e($stats['active_prompts'] ?? 0); ?></div>
                            <div class="text-sm text-gray-600">Prompts</div>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Success Rate</span>
                            <span class="font-semibold text-green-600"><?php echo e($stats['success_rate'] ?? 0); ?>%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campaign Generator Tool -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">Campaign Generator</h3>
                                <p class="text-sm text-gray-600">Google Ads campaigns (RSA & PMAX)</p>
                            </div>
                        </div>
                        <a href="<?php echo e(route('tenant.campaigns.index')); ?>" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                            View All →
                        </a>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <div class="text-2xl font-bold text-gray-900"><?php echo e($campaignStats['total_campaigns'] ?? 0); ?></div>
                            <div class="text-sm text-gray-600">Campaigns</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900"><?php echo e($campaignStats['total_assets'] ?? 0); ?></div>
                            <div class="text-sm text-gray-600">Assets</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-900"><?php echo e(number_format($campaignStats['campaign_tokens'] ?? 0)); ?></div>
                            <div class="text-sm text-gray-600">Tokens</div>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">RSA / PMAX</span>
                            <span class="font-semibold text-gray-900"><?php echo e($campaignStats['rsa_campaigns'] ?? 0); ?> / <?php echo e($campaignStats['pmax_campaigns'] ?? 0); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEO Tools (Coming Soon) -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-gray-900">SEO Tools</h3>
                            <p class="text-sm text-gray-600">Meta tags, FAQ, Internal links & more</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        Coming Soon
                    </span>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <div class="text-sm font-medium text-gray-900">Meta Tags Generator</div>
                        <div class="text-xs text-gray-500 mt-1">SEO optimization</div>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <div class="text-sm font-medium text-gray-900">FAQ Generator</div>
                        <div class="text-xs text-gray-500 mt-1">Schema markup</div>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <div class="text-sm font-medium text-gray-900">Internal Links</div>
                        <div class="text-xs text-gray-500 mt-1">Site structure</div>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-lg">
                        <div class="text-sm font-medium text-gray-900">Content Analyzer</div>
                        <div class="text-xs text-gray-500 mt-1">SEO scoring</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Token Usage Overview -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Token Usage Overview</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Monthly Usage</span>
                            <span class="text-sm text-gray-600">
                                <?php echo e(number_format($tokenStats['total_tokens_used'])); ?> / <?php echo e(number_format($tokenStats['monthly_limit'])); ?>

                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-blue-600 h-3 rounded-full" style="width: <?php echo e($tokenStats['usage_percent'] ?? 0); ?>%"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4 pt-4 border-t">
                        <div>
                            <div class="text-sm text-gray-600">Used</div>
                            <div class="text-lg font-semibold text-gray-900"><?php echo e(number_format($tokenStats['total_tokens_used'])); ?></div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">Remaining</div>
                            <div class="text-lg font-semibold text-green-600"><?php echo e(number_format($tokenStats['remaining_tokens'])); ?></div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600">Limit</div>
                            <div class="text-lg font-semibold text-gray-900"><?php echo e(number_format($tokenStats['monthly_limit'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\ainstein-3\resources\views/tenant/dashboard.blade.php ENDPATH**/ ?>