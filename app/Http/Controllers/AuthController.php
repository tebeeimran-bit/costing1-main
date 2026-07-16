<?php

namespace App\Http\Controllers;

use App\Models\LoginActivity;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('project-selection');
        }

        return view('auth.login');
    }

    public function projectSelection()
    {
        return view('auth.project-selection');
    }

    public function productPerformance()
    {
        return view('auth.costing-product-performance');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $login = trim((string) $validated['login']);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
        $throttleKey = 'login|'.Str::lower($login).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()->withErrors(['login' => 'Terlalu banyak percobaan login. Coba kembali dalam '.RateLimiter::availableIn($throttleKey).' detik.'])->onlyInput('login');
        }

        if (Auth::attempt([
            $field => $login,
            'password' => $validated['password'],
        ], $request->boolean('remember'))) {
            $request->session()->regenerate();
            RateLimiter::clear($throttleKey);
            LoginActivity::create(['user_id' => Auth::id(), 'identifier' => $login, 'ip_address' => $request->ip(), 'user_agent' => Str::limit((string) $request->userAgent(), 1000), 'successful' => true, 'occurred_at' => now()]);

            return redirect()->route('project-selection');
        }

        RateLimiter::hit($throttleKey, 60);
        LoginActivity::create(['identifier' => $login, 'ip_address' => $request->ip(), 'user_agent' => Str::limit((string) $request->userAgent(), 1000), 'successful' => false, 'occurred_at' => now()]);

        return back()->withErrors([
            'login' => 'Nama/email atau password salah.',
        ])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function permissions()
    {
        $users = User::orderBy('name')->get();
        $permissionMatrix = RolePermission::getMatrix();
        $roles = ['admin', 'admin_costing', 'coordinator_costing', 'marketing', 'editor', 'viewer'];
        $modules = [
            'dashboard' => 'Dashboard',
            'input_data' => 'Input Data',
            'database' => 'Database',
            'laporan' => 'Laporan',
            'user_management' => 'User Management',
        ];

        return view('auth.permissions', compact('users', 'permissionMatrix', 'roles', 'modules'));
    }

    public function updatePermission(Request $request)
    {
        $validated = $request->validate([
            'role' => ['required', 'in:admin,admin_costing,coordinator_costing,marketing,editor,viewer'],
            'module' => ['required', 'in:dashboard,input_data,database,laporan,user_management'],
            'access' => ['required', 'in:full,view,none'],
        ]);

        // Admin selalu full – tidak bisa diubah
        if ($validated['role'] === 'admin') {
            return redirect()->route('permissions')->with('error', 'Permission role Admin tidak dapat diubah.');
        }

        // Modul user_management hanya boleh diakses admin – tidak bisa diberi akses ke role lain
        if ($validated['module'] === 'user_management') {
            return redirect()->route('permissions')->with('error', 'Modul User Management hanya dapat diakses oleh Admin.');
        }

        RolePermission::updateOrCreate(
            ['role' => $validated['role'], 'module' => $validated['module']],
            ['access' => $validated['access']]
        );

        return redirect()->route('permissions')->with('success', 'Permission berhasil diperbarui.');
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:10'],
            'role' => ['required', 'in:admin,admin_costing,coordinator_costing,marketing,editor,viewer'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('permissions')->with('success', 'User berhasil ditambahkan.');
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'role' => ['required', 'in:admin,admin_costing,coordinator_costing,marketing,editor,viewer'],
            'password' => ['nullable', 'string', 'min:10'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('permissions')->with('success', 'User berhasil diperbarui.');
    }

    public function destroyUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return redirect()->route('permissions')->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('permissions')->with('success', 'User berhasil dihapus.');
    }
}
