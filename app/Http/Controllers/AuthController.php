<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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

        if (Auth::attempt([
            $field => $login,
            'password' => $validated['password'],
        ], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->route('project-selection');
        }

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

    public function updateOwnPassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'different:current_password', 'confirmed'],
        ], [
            'current_password.required' => 'Password saat ini wajib diisi.',
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.different' => 'Password baru harus berbeda dari password saat ini.',
            'password.confirmed' => 'Konfirmasi password baru tidak sesuai.',
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
            'remember_token' => null,
        ])->save();

        $request->session()->regenerate();

        return redirect()->route('profile.show')->with('password_success', 'Password berhasil diperbarui.');
    }

    public function permissions()
    {
        $users = User::orderBy('name')->get();
        $permissionMatrix = RolePermission::getMatrix();
        $roles = ['admin', 'admin_control_project', 'admin_costing', 'coordinator_costing', 'document_control', 'engineering', 'marketing', 'editor', 'viewer'];
        $modules = [
            'dashboard' => 'Dashboard',
            'project' => 'Project',
            'inbox_breakdown' => 'Inbox Breakdown',
            'inbox_costing' => 'Inbox Costing',
            'inbox_new_part_request' => 'Inbox New Part Request',
            'inbox_marketing' => 'Inbox Marketing',
            'input_data' => 'Input Data',
            'database' => 'Database',
            'laporan' => 'Laporan',
            'user_management' => 'User Management',
            'document_control' => 'Document Control',
            'control_project' => 'Control Project',
        ];

        return view('auth.permissions', compact('users', 'permissionMatrix', 'roles', 'modules'));
    }

    public function updatePermission(Request $request)
    {
        if ($request->has('permissions')) {
            $validated = $request->validate([
                'permissions' => ['required', 'array'],
                'permissions.*' => ['required', 'array'],
                'permissions.*.*' => ['required', 'in:full,view,none'],
            ]);

            $allowedRoles = ['admin_control_project', 'admin_costing', 'coordinator_costing', 'document_control', 'engineering', 'marketing', 'editor', 'viewer'];
            $allowedModules = ['dashboard', 'project', 'inbox_breakdown', 'inbox_costing', 'inbox_new_part_request', 'inbox_marketing', 'input_data', 'database', 'laporan', 'document_control', 'control_project'];
            $updates = [];

            foreach ($validated['permissions'] as $role => $modules) {
                abort_unless(in_array($role, $allowedRoles, true), 422, 'Role permission tidak valid.');
                foreach ($modules as $module => $access) {
                    abort_unless(in_array($module, $allowedModules, true), 422, 'Modul permission tidak valid.');
                    $updates[] = compact('role', 'module', 'access');
                }
            }

            DB::transaction(function () use ($updates) {
                foreach ($updates as $permission) {
                    RolePermission::updateOrCreate(
                        ['role' => $permission['role'], 'module' => $permission['module']],
                        ['access' => $permission['access']]
                    );
                }
            });

            return redirect()->route('permissions')->with('success', 'Semua perubahan hak akses berhasil disimpan.');
        }

        $validated = $request->validate([
            'role' => ['required', 'in:admin,admin_control_project,admin_costing,coordinator_costing,document_control,engineering,marketing,editor,viewer'],
            'module' => ['required', 'in:dashboard,project,inbox_breakdown,inbox_costing,inbox_new_part_request,inbox_marketing,input_data,database,laporan,user_management,document_control,control_project'],
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
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:admin,admin_control_project,admin_costing,coordinator_costing,document_control,engineering,marketing,editor,viewer'],
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
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role' => ['required', 'in:admin,admin_control_project,admin_costing,coordinator_costing,document_control,engineering,marketing,editor,viewer'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];

        if (!empty($validated['password'])) {
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
