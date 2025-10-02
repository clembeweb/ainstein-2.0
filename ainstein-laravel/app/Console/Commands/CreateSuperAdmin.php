<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-super-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a super admin user for testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenant = Tenant::create([
            'name' => 'Super Admin Tenant',
            'subdomain' => 'super-admin',
            'status' => 'active',
            'plan_type' => 'enterprise',
        ]);

        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@ainstein.com',
            'password_hash' => Hash::make('password123'),
            'email_verified_at' => now(),
            'role' => 'super_admin',
            'is_active' => true,
            'tenant_id' => $tenant->id,
        ]);

        $this->info('Super admin user created successfully!');
        $this->info('Email: admin@ainstein.com');
        $this->info('Password: password123');
        $this->info('Tenant: ' . $tenant->name);

        return 0;
    }
}
