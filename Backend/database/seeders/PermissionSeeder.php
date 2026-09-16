<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'brands.view',
            'brands.create',
            'brands.update',
            'brands.delete',
            'category.view',
            'category.create',
            'category.update',
            'category.delete',
            'product.view',
            'product.create',
            'product.update',
            'product.delete',
            'suppliers.view',
            'suppliers.create',
            'suppliers.update',
            'suppliers.delete',
            'purchase.view',
            'purchase.create',
            'purchase.update',
            'purchase.delete',
            'purchaserecive',
            'inventory.view',
            'inventoryhistory.view',
            'store.view',
            'store.create',
            'store.update',
            'store.delete',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',

            'permissions.view',
            'permissions.create',
            'permissions.update',
            'permissions.delete',

            'pos.view',
            'category.view',
            'pos.cart.view',
            'pos.cart.add',

            'pos.cart.remove',
            'pos.cart.incres',
            'pos.cart.decres',

            'checkout.create',
            'checkout.confirm',

            'order.view',
            'orders.update',

            'payment_method.view',
            'payment_method.create',
            'payment_method.update',
            'payment_method.delete',

            'payment.view',
            'payment.update',
            'payment.delete',
            'pos.payment',

            'return.view',
            'return.create',

            'sales_return.view'
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission
            ]);
        }
    }
}
