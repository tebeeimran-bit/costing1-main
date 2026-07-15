<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $fillable = ['role', 'module', 'access'];

    public static function getMatrix(): array
    {
        $permissions = self::all();
        $matrix = [];

        foreach ($permissions as $perm) {
            $matrix[$perm->role][$perm->module] = $perm->access;
        }

        return $matrix;
    }

    public static function hasAccess(string $role, string $module, string $minLevel = 'view'): bool
    {
        // Admin selalu punya akses penuh ke semua modul
        if ($role === 'admin') {
            return true;
        }

        $perm = self::where('role', $role)->where('module', $module)->first();

        // Jika tidak ada record, akses ditolak
        if (!$perm) {
            return false;
        }

        if ($minLevel === 'view') {
            return in_array($perm->access, ['view', 'full']);
        }

        return $perm->access === 'full';
    }
}
