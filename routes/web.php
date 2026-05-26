<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Frontend routes (future)
Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

// Required by Laravel's password broker to generate reset URL in email
Route::get('/admin/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset')->middleware('guest');

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {

    // Guest routes
    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login']);

        Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.request');
        Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');

        Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
        Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword'])->name('password.update');
    });

    // Authenticated routes
    Route::middleware('auth')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Products
        Route::get('products/data', [ProductController::class, 'data'])->name('products.data');
        Route::resource('products', ProductController::class)->except('show');
        Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::patch('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
        Route::delete('products/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
        Route::patch('products/images/{image}/primary', [ProductController::class, 'setPrimaryImage'])->name('products.images.primary');

        // Purchases
        Route::get('purchases/data', [PurchaseInvoiceController::class, 'data'])->name('purchases.data');
        Route::get('products/{product}/price', [PurchaseInvoiceController::class, 'getProductPrice'])->name('products.price');
        Route::resource('purchases', PurchaseInvoiceController::class)->except('show');
        Route::get('purchases/{purchase}', [PurchaseInvoiceController::class, 'show'])->name('purchases.show');
        Route::get('purchases/{purchase}/pdf', [PurchaseInvoiceController::class, 'pdf'])->name('purchases.pdf');
        Route::patch('purchases/{purchase}/status', [PurchaseInvoiceController::class, 'updateStatus'])->name('purchases.status');

        // Suppliers
        Route::get('suppliers/data', [SupplierController::class, 'data'])->name('suppliers.data');
        Route::resource('suppliers', SupplierController::class)->except('show');
        Route::patch('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');

        // Customers
        Route::get('customers/data', [CustomerController::class, 'data'])->name('customers.data');
        Route::resource('customers', CustomerController::class)->except('show');
        Route::patch('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');

        // Inventory Report
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('inventory/stock', [InventoryController::class, 'stock'])->name('inventory.stock');

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('products', [ReportController::class, 'products'])->name('products');
            Route::get('stock-inventory', [ReportController::class, 'stockInventory'])->name('stock-inventory');
        });

        // Sales
        Route::get('sales/data', [SaleController::class, 'data'])->name('sales.data');
        Route::resource('sales', SaleController::class)->except('show');
        Route::get('sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::get('sales/{sale}/pdf', [SaleController::class, 'pdf'])->name('sales.pdf');
        Route::patch('sales/{sale}/status', [SaleController::class, 'updateStatus'])->name('sales.status');

        // Categories
        Route::get('categories/data', [CategoryController::class, 'data'])->name('categories.data');
        Route::resource('categories', CategoryController::class)->except('show');
        Route::patch('categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('categories.toggle-status');

        // Locations
        Route::get('locations/data', [LocationController::class, 'data'])->name('locations.data');
        Route::resource('locations', LocationController::class)->except('show');
        Route::patch('locations/{location}/toggle-status', [LocationController::class, 'toggleStatus'])->name('locations.toggle-status');

        // Permissions
        Route::get('permissions/data', [PermissionController::class, 'data'])->name('permissions.data');
        Route::resource('permissions', PermissionController::class)->except('show');

        // Roles
        Route::get('roles/data', [RoleController::class, 'data'])->name('roles.data');
        Route::resource('roles', RoleController::class)->except('show');

        // Users
        Route::get('users/data', [UserController::class, 'data'])->name('users.data');
        Route::resource('users', UserController::class)->except('show');
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    });

});
