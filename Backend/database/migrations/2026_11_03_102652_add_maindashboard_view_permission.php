
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create dashboard permission if it does not exist
        DB::table('permissions')->insertOrIgnore([
            'name' => 'maindashboard.view',
        ]);

        // Get dashboard permission ID
        $permissionId = DB::table('permissions')
            ->where('name', 'maindashboard.view')
            ->value('id');

        // Grant permission to admin role (ID 3)
        DB::table('role_permissions')->insertOrIgnore([
            'role_id' => 3,
            'permission_id' => $permissionId,
        ]);
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('name', 'maindashboard.view')
            ->value('id');

        if ($permissionId) {
            DB::table('role_permissions')
                ->where('role_id', 3)
                ->where('permission_id', $permissionId)
                ->delete();

            DB::table('permissions')
                ->where('id', $permissionId)
                ->delete();
        }
    }
};
