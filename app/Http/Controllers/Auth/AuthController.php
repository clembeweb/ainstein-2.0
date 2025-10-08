<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Display the login view.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        \Log::info('🔐 LOGIN ATTEMPT START', [
            'email' => $request->email,
            'has_password' => !empty($request->password),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        \Log::info('✅ Validation passed');

        // Find user by email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            \Log::warning('❌ User not found', ['email' => $request->email]);
            return back()->withErrors([
                'email' => 'Le credenziali fornite non corrispondono ai nostri record.',
            ])->onlyInput('email');
        }

        \Log::info('✅ User found', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'is_super_admin' => $user->is_super_admin,
            'tenant_id' => $user->tenant_id
        ]);

        // Check if password_hash field exists
        if (!$user->password_hash) {
            \Log::error('❌ User has no password_hash field!', ['user_id' => $user->id]);
            return back()->withErrors([
                'email' => 'Errore di configurazione utente. Contatta l\'amministratore.',
            ])->onlyInput('email');
        }

        // Verify password
        $passwordCheck = Hash::check($request->password, $user->password_hash);
        \Log::info('🔑 Password check result', ['valid' => $passwordCheck]);

        if ($passwordCheck) {
            \Log::info('✅ Password verified, attempting login...');

            // Login the user
            Auth::login($user, $request->boolean('remember'));
            \Log::info('✅ Auth::login() completed', ['auth_check' => Auth::check()]);

            $request->session()->regenerate();
            \Log::info('✅ Session regenerated');

            // Redirect based on user role
            if ($user->is_super_admin) {
                \Log::info('🔀 Redirecting to /admin (super admin)');
                return redirect('/admin');
            } else {
                \Log::info('🔀 Redirecting to /dashboard (tenant user)');
                return redirect('/dashboard');
            }
        }

        \Log::warning('❌ Password verification failed');
        return back()->withErrors([
            'email' => 'Le credenziali fornite non corrispondono ai nostri record.',
        ])->onlyInput('email');
    }

    /**
     * Display the registration view.
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle registration request.
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company_name' => ['required', 'string', 'max:255'],
        ]);

        // Create tenant for the user
        $tenant = Tenant::create([
            'name' => $request->company_name,
            'domain' => Str::slug($request->company_name) . '.ainstein.local',
            'subdomain' => Str::slug($request->company_name),
            'status' => 'active',
            'plan_type' => 'starter',
            'tokens_monthly_limit' => 10000,
            'tokens_used_current' => 0,
        ]);

        // Create user and assign to tenant
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password_hash' => Hash::make($request->password),
            'tenant_id' => $tenant->id,
            'role' => 'tenant_admin',
            'email_verified_at' => now(), // Auto-verify for demo
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect('/dashboard')->with('success', 'Registrazione completata! Benvenuto su Ainstein.');
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Show admin login form
     */
    public function showAdminLogin()
    {
        return view('admin.login');
    }

    /**
     * Handle admin login
     */
    public function adminLogin(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->where('is_super_admin', true)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'Super admin not found']);
        }

        if (!Hash::check($request->password, $user->password_hash)) {
            return back()->withErrors(['password' => 'Invalid password']);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    /**
     * Handle admin logout
     */
    public function adminLogout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}