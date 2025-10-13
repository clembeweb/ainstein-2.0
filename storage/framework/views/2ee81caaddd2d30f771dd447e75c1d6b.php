<div class="space-y-6">
    <!-- Header with Search and Filters -->
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Manage Pages</h3>
                <p class="text-gray-600">View and manage your content pages</p>
            </div>

            <div class="flex space-x-3">
                <a href="<?php echo e(route('tenant.pages.create')); ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium inline-flex items-center">
                    <i class="fas fa-plus mr-2"></i>Create Page
                </a>
            </div>
        </div>

        <!-- Search and Filters -->
        <form method="GET" action="<?php echo e(route('tenant.content.index')); ?>" class="mt-6">
            <input type="hidden" name="tab" value="pages">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <input type="text" name="search" value="<?php echo e(request('search')); ?>"
                           placeholder="Search pages, keywords, categories..."
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <select name="category" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($category); ?>" <?php echo e(request('category') === $category ? 'selected' : ''); ?>>
                                <?php echo e(ucfirst($category)); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="flex space-x-2">
                    <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                    <a href="<?php echo e(route('tenant.content.index', ['tab' => 'pages'])); ?>" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg">
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Pages List -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Page</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Language</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Generations</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php $__empty_1 = true; $__currentLoopData = $pages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="font-medium text-gray-900"><?php echo e($page->url_path); ?></div>
                                    <?php if($page->keyword): ?>
                                        <div class="text-sm text-gray-600"><?php echo e($page->keyword); ?></div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <?php if($page->category): ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <?php echo e(ucfirst($page->category)); ?>

                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <?php echo e(strtoupper($page->language ?? 'EN')); ?>

                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div class="flex items-center space-x-2">
                                    <span class="font-medium"><?php echo e($page->generations->count()); ?></span>
                                    <?php if($page->generations->where('status', 'completed')->count() > 0): ?>
                                        <span class="text-green-600 text-xs">
                                            (<?php echo e($page->generations->where('status', 'completed')->count()); ?> completed)
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <?php if($page->is_published): ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Published
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Draft
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php echo e($page->created_at->format('M j, Y')); ?>

                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="<?php echo e(route('tenant.pages.edit', $page->id)); ?>" class="text-blue-600 hover:text-blue-700" title="Edit Page">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?php echo e(route('tenant.content.create', ['page_id' => $page->id])); ?>" class="text-purple-600 hover:text-purple-700" title="Generate Content">
                                        <i class="fas fa-magic"></i>
                                    </a>
                                    <form method="POST" action="<?php echo e(route('tenant.pages.destroy', $page->id)); ?>" class="inline" onsubmit="return confirm('Are you sure you want to delete this page? This action cannot be undone.');">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="text-red-600 hover:text-red-700" title="Delete Page">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <i class="fas fa-file-alt text-4xl mb-4"></i>
                                    <p class="text-lg">No pages found</p>
                                    <p class="text-sm">Create your first page to get started with content generation</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if($pages->hasPages()): ?>
            <div class="px-6 py-4 border-t">
                <?php echo e($pages->appends(array_merge(request()->query(), ['tab' => 'pages']))->links()); ?>

            </div>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH C:\laragon\www\ainstein-3\resources\views/tenant/content-generator/tabs/pages.blade.php ENDPATH**/ ?>