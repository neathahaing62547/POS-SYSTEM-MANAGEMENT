<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\PosCartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\SalesReturnController;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/verify-otp', [AuthController::class, 'verifyLoginOtp']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    Route::get('/brands', [BrandController::class, 'index'])
        ->middleware('permission:brands.view');

    Route::post('/brands', [BrandController::class, 'store'])
        ->middleware('permission:brands.create');

    Route::get('/brands/{brand}', [BrandController::class, 'show'])
        ->middleware('permission:brands.view');

    Route::put('/brands/{brand}', [BrandController::class, 'update'])
        ->middleware('permission:brands.update');

    Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])
        ->middleware('permission:brands.delete');


    Route::get('/categories', [CategoryController::class, 'index'])
        ->middleware('permission:category.view');

    Route::post('/categories', [CategoryController::class, 'store'])
        ->middleware('permission:category.create');

    Route::get('/categories/{category}', [CategoryController::class, 'show'])
        ->middleware('permission:category.view');

    Route::put('/categories/{category}', [CategoryController::class, 'update'])
        ->middleware('permission:category.update');

    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
        ->middleware('permission:category.delete');


    Route::get('/products', [ProductController::class, 'index'])
        ->middleware('permission:product.view');

    Route::post('/products', [ProductController::class, 'store'])
        ->middleware('permission:product.create');

    Route::get('/products/{product}', [ProductController::class, 'show'])
        ->middleware('permission:product.view');

    Route::put('/products/{product}', [ProductController::class, 'update'])
        ->middleware('permission:product.update');

    Route::delete('/products/{product}', [ProductController::class, 'destroy'])
        ->middleware('permission:product.delete');



    Route::get('/suppliers', [SupplierController::class, 'index'])
        ->middleware('permission:suppliers.view');

    Route::post('/suppliers', [SupplierController::class, 'store'])
        ->middleware('permission:suppliers.create');

    Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])
        ->middleware('permission:suppliers.view');

    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])
        ->middleware('permission:suppliers.update');

    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])
        ->middleware('permission:suppliers.delete');


    Route::get('/purchases', [PurchaseController::class, 'index'])
        ->middleware('permission:purchase.view');

    Route::post('/purchases', [PurchaseController::class, 'store'])
        ->middleware('permission:purchase.create');

    Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])
        ->middleware('permission:purchase.view');

    Route::put('/purchases/{purchase}', [PurchaseController::class, 'update'])
        ->middleware('permission:purchase.update');

    Route::delete('/purchases/{purchase}', [PurchaseController::class, 'destroy'])
        ->middleware('permission:purchase.delete');

    Route::post('/purchases/{purchase}/receive', [PurchaseController::class, 'receive'])
        ->middleware('permission:purchaserecive');


    Route::get('/inventorys', [InventoryController::class, 'index'])
        ->middleware('permission:inventory.view');

    Route::get('/inventorys/{inventoryStock}', [InventoryController::class, 'show'])
        ->middleware('permission:inventory.view');

    Route::get('/stockhistory', [InventoryController::class, 'history'])
        ->middleware('permission:inventoryhistory.view');


    Route::get('/stores', [StoreController::class, 'index'])
        ->middleware('permission:store.view');

    Route::post('/stores', [StoreController::class, 'store'])
        ->middleware('permission:store.create');

    Route::get('/stores/{store}', [StoreController::class, 'show'])
        ->middleware('permission:store.view');

    Route::put('/stores/{store}', [StoreController::class, 'update'])
        ->middleware('permission:store.update');

    Route::patch('/stores/{store}/status', [StoreController::class, 'updateStatus'])
        ->middleware('permission:store.update');

    Route::delete('/stores/{store}', [StoreController::class, 'destroy'])
        ->middleware('permission:store.delete');

    Route::get('/pos/products', [PosController::class, 'index'])
        ->middleware('permission:pos.view');

    Route::get('/pos/categorys/{category}', [PosController::class, 'filltercategory'])
        ->middleware('permission:category.view');

    Route::get('/pos/products/{product}', [PosController::class, 'show'])
        ->middleware('permission:pos.view');

    Route::get('/pos/cart', [PosCartController::class, 'index'])
        ->middleware('permission:pos.cart.view');

    Route::post('/pos/cart/items', [PosCartController::class, 'Addtocarts'])
        ->middleware('permission:pos.cart.add');

    Route::delete('/pos/cart/items/{remove}', [PosCartController::class, 'removeitem'])
        ->middleware('permission:pos.cart.remove');

    Route::patch('/pos/cart/items/{cartItem}/increase', [PosCartController::class, 'increase'])
        ->middleware('permission:pos.cart.incres');

    Route::patch('/pos/cart/items/{cartItem}/decrease', [PosCartController::class, 'decrease'])
        ->middleware('permission:pos.cart.decres');

    Route::get('/pos/checkout', [CheckoutController::class, 'checkout'])
        ->middleware('permission:checkout.create');

    Route::post('pos/checkout/confirm', [CheckoutController::class, 'confirm'])
        ->middleware('permission:checkout.confirm');

    Route::post('pos/payment-success/{paymentId}', [CheckoutController::class, 'paymentSuccess'])
        ->middleware('permission:pos.payment');

    Route::get('/pos/orders', [OrderController::class, 'index'])
        ->middleware('permission:order.view');

    Route::get('pos/orders/{id}', [OrderController::class, 'show'])
        ->middleware('permission:order.view');

    Route::patch('pos/orders/{id}/status', [OrderController::class, 'updateStatus'])
        ->middleware('permission:orders.update');

    Route::get('/pos/payment-methods', [PaymentMethodController::class, 'active'])
        ->middleware('permission:pos.view');

    Route::get('/orders/{order}/return', [SalesReturnController::class, 'showorder'])
        ->middleware('permission:return.view');

    Route::post('/orders/{order}/return', [SalesReturnController::class, 'store'])
        ->middleware('permission:return.create');

    Route::get('/sales-returns', [SalesReturnController::class, 'index'])
        ->middleware('permission:sales_return.view');
        
    Route::get('/sales-returns/{salesReturn}', [SalesReturnController::class, 'show'])
        ->middleware('permission:sales_return.view');


    Route::middleware('role:admin')->group(function () {

        Route::get('/users', [UserController::class, 'index'])
            ->middleware('permission:users.view');

        Route::post('/users', [UserController::class, 'store'])
            ->middleware('permission:users.create');

        Route::get('/users/{user}', [UserController::class, 'show'])
            ->middleware('permission:users.view');

        Route::put('/users/{user}', [UserController::class, 'update'])
            ->middleware('permission:users.update');

        Route::delete('/users/{user}', [UserController::class, 'destroy'])
            ->middleware('permission:users.delete');


        Route::get('/roles', [RoleController::class, 'index'])
            ->middleware('permission:roles.view');

        Route::post('/roles', [RoleController::class, 'store'])
            ->middleware('permission:roles.create');

        Route::get('/roles/{role}', [RoleController::class, 'show'])
            ->middleware('permission:roles.view');

        Route::put('/roles/{role}', [RoleController::class, 'update'])
            ->middleware('permission:roles.update');

        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
            ->middleware('permission:roles.delete');


        Route::get('/permissions', [PermissionController::class, 'index'])
            ->middleware('permission:permissions.view');

        Route::post('/permissions', [PermissionController::class, 'store'])
            ->middleware('permission:permissions.create');

        Route::get('/permissions/{permission}', [PermissionController::class, 'show'])
            ->middleware('permission:permissions.view');

        Route::put('/permissions/{permission}', [PermissionController::class, 'update'])
            ->middleware('permission:permissions.update');

        Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])
            ->middleware('permission:permissions.delete');

        Route::get('/payment-methods', [PaymentMethodController::class, 'index'])
            ->middleware('permission:payment_method.view');

        Route::post('/payment-methods', [PaymentMethodController::class, 'store'])
            ->middleware('permission:payment_method.create');

        Route::get('/payment-methods/{id}', [PaymentMethodController::class, 'show'])
            ->middleware('permission:payment_method.view');

        Route::put('/payment-methods/{id}', [PaymentMethodController::class, 'update'])
            ->middleware('permission:payment_method.update');

        Route::delete('/payment-methods/{id}', [PaymentMethodController::class, 'destroy'])
            ->middleware('permission:payment_method.delete');

        Route::get('/payments', [PaymentController::class, 'index'])
            ->middleware('permission:payment.view');

        Route::get('/payments/{patments}', [PaymentController::class, 'show'])
            ->middleware('permission:payment.view');

        Route::patch('/payment/{payment}', [PaymentController::class, 'update'])
            ->middleware('permission:payment.update');

        Route::delete('/payment/{payments}', [PaymentController::class, 'destroy'])
            ->middleware('permission:payment.delete');
    });
});
