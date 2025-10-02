<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tenant;
use App\Models\ContentGeneration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_tokens_used' => Tenant::sum('tokens_used_current'),
            'total_tokens_limit' => Tenant::sum('tokens_monthly_limit'),
            'total_generations' => ContentGeneration::count(),
            'today_generations' => ContentGeneration::whereDate('created_at', today())->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }

    // Users Management
    public function users()
    {
        $users = User::with('tenant')->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function createUser()
    {
        $tenants = Tenant::all();
        return view('admin.users.create', compact('tenants'));
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'tenant_id' => 'nullable|exists:tenants,id',
            'role' => 'required|in:super_admin,tenant_admin,tenant_user',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['password_hash'] = Hash::make($validated['password']);
        unset($validated['password']);
        $validated['is_super_admin'] = $request->has('is_super_admin');
        $validated['is_active'] = $request->has('is_active') ? true : false;

        User::create($validated);

        return redirect()->route('admin.users')->with('success', 'User created successfully');
    }

    public function editUser(User $user)
    {
        $tenants = Tenant::all();
        return view('admin.users.edit', compact('user', 'tenants'));
    }

    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'tenant_id' => 'nullable|exists:tenants,id',
            'role' => 'required|in:super_admin,tenant_admin,tenant_user',
            'is_super_admin' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if (!empty($validated['password'])) {
            $validated['password_hash'] = Hash::make($validated['password']);
        }
        unset($validated['password']);

        $validated['is_super_admin'] = $request->has('is_super_admin');
        $validated['is_active'] = $request->has('is_active') ? true : false;

        $user->update($validated);

        return redirect()->route('admin.users')->with('success', 'User updated successfully');
    }

    public function deleteUser(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users')->with('success', 'User deleted successfully');
    }

    // Tenants Management
    public function tenants()
    {
        $tenants = Tenant::withCount('users')->paginate(20);
        return view('admin.tenants.index', compact('tenants'));
    }

    public function createTenant()
    {
        return view('admin.tenants.create');
    }

    public function storeTenant(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'subdomain' => 'nullable|string|max:255',
            'plan_type' => 'required|in:starter,professional,enterprise',
            'status' => 'required|in:active,trial,suspended,cancelled',
            'tokens_monthly_limit' => 'required|integer|min:0',
        ]);

        Tenant::create($validated);

        return redirect()->route('admin.tenants')->with('success', 'Tenant created successfully');
    }

    public function editTenant(Tenant $tenant)
    {
        return view('admin.tenants.edit', compact('tenant'));
    }

    public function updateTenant(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'subdomain' => 'nullable|string|max:255',
            'plan_type' => 'required|in:starter,professional,enterprise',
            'status' => 'required|in:active,trial,suspended,cancelled',
            'tokens_monthly_limit' => 'required|integer|min:0',
        ]);

        $tenant->update($validated);

        return redirect()->route('admin.tenants')->with('success', 'Tenant updated successfully');
    }

    public function resetTokens(Tenant $tenant)
    {
        $tenant->update(['tokens_used_current' => 0]);
        return redirect()->route('admin.tenants')->with('success', 'Tokens reset successfully');
    }

    // Settings
    public function settings()
    {
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.model');

        $settings = [
            'openai_api_key' => is_callable($apiKey) ? $apiKey() : ($apiKey ?? ''),
            'openai_model' => is_callable($model) ? $model() : ($model ?? 'gpt-4'),
        ];

        return view('admin.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'openai_api_key' => 'required|string',
            'openai_model' => 'required|string',
        ]);

        // Update .env
        $this->updateEnvFile('OPENAI_API_KEY', $validated['openai_api_key']);
        $this->updateEnvFile('OPENAI_MODEL', $validated['openai_model']);

        return redirect()->route('admin.settings')->with('success', 'Settings updated successfully');
    }

    protected function updateEnvFile($key, $value)
    {
        $path = base_path('.env');
        $content = file_get_contents($path);

        if (preg_match("/^{$key}=/m", $content)) {
            $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
        } else {
            $content .= "\n{$key}={$value}";
        }

        file_put_contents($path, $content);
    }
}
