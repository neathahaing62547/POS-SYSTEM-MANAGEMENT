<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ProductionSetupSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $admin = Role::firstOrCreate(
                ['name' => 'admin'],
                ['description' => 'System administrator']
            );

            Role::firstOrCreate(
                ['name' => 'staff'],
                ['description' => 'POS staff']
            );

            $this->call(PermissionSeeder::class);
            $this->call(RolePermissionSeeder::class);

            $email = 'neathahaing@gmail.com';

            $username = $this->command->ask('Admin username', 'admin');
            $fullName = $this->command->ask('Admin full name');

            $password = $this->command->secret('Enter a new admin password');

            if (strlen($password) < 12) {
                throw new \RuntimeException(
                    'Password must contain at least 12 characters.'
                );
            }

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'username' => $username,
                    'full_name' => $fullName,
                    'password_hash' => Hash::make($password),
                    'status' => 'active',
                ]
            );

            $user->roles()->syncWithoutDetaching([$admin->id]);
        });

        $this->command->info('Production admin setup completed.');
    }
}
