<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')->insertOrIgnore([
            'name' => 'reports.view',
            'description' => 'View reports',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissionId = DB::table('permissions')
            ->where('name', 'reports.view')
            ->value('id');

        $adminRoleId = DB::table('roles')
            ->where('name', 'admin')
            ->value('id');

        if ($permissionId && $adminRoleId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id' => $adminRoleId,
                'permission_id' => $permissionId,
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('name', 'reports.view')
            ->value('id');

        $adminRoleId = DB::table('roles')
            ->where('name', 'admin')
            ->value('id');

        if ($permissionId && $adminRoleId) {
            DB::table('role_permissions')
                ->where('role_id', $adminRoleId)
                ->where('permission_id', $permissionId)
                ->delete();
        }
    }
};
