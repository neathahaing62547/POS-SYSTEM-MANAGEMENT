<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::where('name', 'admin')->firstOrFail();
        $staff = Role::where('name', 'staff')->firstOrFail();
        $admin->permissions()->sync(
            Permission::pluck('id')
        );

        $staffPermissions = Permission::whereIn('name', [
            'brands.view',
            'category.view',
            'product.view',
            'product.create',
            'product.update',
            'suppliers.view',
            'purchase.view',
            'purchase.create',
            'purchaserecive',
            'inventory.view',
            'inventoryhistory.view',
            'store.view',
            'pos.view',
            'pos.cart.view',
            'pos.cart.add',
            'pos.cart.remove',
            'pos.cart.incres',
            'pos.cart.decres',
            'checkout.create',
            'checkout.confirm',
            'pos.payment',
            'order.view',
            'orders.update',
            'return.view',
            'return.create',
            'cash_register.view',
            'cash_register.open',
            'cash_register.close',
            'cash_register.cash_in',
            'cash_register.cash_out'


        ])->pluck('id');

        $staff->permissions()->sync($staffPermissions);
    }
}
