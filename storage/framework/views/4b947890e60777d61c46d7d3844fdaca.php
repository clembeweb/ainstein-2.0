<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('content'); ?>
<div class="px-4 py-6">
    <h1 class="text-3xl font-bold mb-6">Dashboard</h1>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-500 text-sm">Total Tenants</h3>
            <p class="text-3xl font-bold"><?php echo e($stats['total_tenants']); ?></p>
            <p class="text-sm text-green-600"><?php echo e($stats['active_tenants']); ?> active</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-500 text-sm">Total Users</h3>
            <p class="text-3xl font-bold"><?php echo e($stats['total_users']); ?></p>
            <p class="text-sm text-green-600"><?php echo e($stats['active_users']); ?> active</p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-500 text-sm">Token Usage</h3>
            <p class="text-3xl font-bold"><?php echo e(number_format($stats['total_tokens_used'])); ?></p>
            <p class="text-sm text-gray-600">/ <?php echo e(number_format($stats['total_tokens_limit'])); ?></p>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-gray-500 text-sm">Generations</h3>
            <p class="text-3xl font-bold"><?php echo e($stats['total_generations']); ?></p>
            <p class="text-sm text-blue-600"><?php echo e($stats['today_generations']); ?> today</p>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-bold mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="<?php echo e(route('admin.users')); ?>" class="block p-4 border rounded hover:bg-gray-50">
                <h3 class="font-bold">👥 Manage Users</h3>
                <p class="text-sm text-gray-600">View and edit users</p>
            </a>
            <a href="<?php echo e(route('admin.tenants')); ?>" class="block p-4 border rounded hover:bg-gray-50">
                <h3 class="font-bold">🏢 Manage Tenants</h3>
                <p class="text-sm text-gray-600">View tenants and token usage</p>
            </a>
            <a href="<?php echo e(route('admin.settings.index')); ?>" class="block p-4 border rounded hover:bg-gray-50">
                <h3 class="font-bold">⚙️ Settings</h3>
                <p class="text-sm text-gray-600">Configure OpenAI API</p>
            </a>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\ainstein-3\resources\views/admin/dashboard.blade.php ENDPATH**/ ?>