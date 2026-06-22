<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactInquiryController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CouponController;
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
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\WebsiteContentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MemberRegisterController;
use App\Http\Controllers\CustomerLoginController;
use App\Http\Controllers\CustomerPasswordResetController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\ShopCategoryController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

// Frontend routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about-us', [HomeController::class, 'about'])->name('about');
Route::get('/contact-us', [HomeController::class, 'contact'])->name('contact');
Route::post('/contact-us', [ContactInquiryController::class, 'store'])->name('contact.submit');
Route::get('/terms-conditions', [HomeController::class, 'terms'])->name('terms');
Route::get('/privacy-policy', [HomeController::class, 'privacy'])->name('privacy');
Route::get('/delivery-returns', [HomeController::class, 'deliveryReturns'])->name('delivery-returns');
Route::get('/refund-cancellation', [HomeController::class, 'refundCancellation'])->name('refund-cancellation');
Route::get('/login', [HomeController::class, 'login'])->name('login');
Route::post('/login', [CustomerLoginController::class, 'login'])->name('login.store');
Route::post('/logout', [CustomerLoginController::class, 'logout'])->name('customer.logout');
Route::get('/forgot-password', [HomeController::class, 'forgotPassword'])->name('forgot-password');
Route::post('/forgot-password', [CustomerPasswordResetController::class, 'sendOtp'])->name('forgot-password.send-otp');
Route::get('/otp-verification', [HomeController::class, 'otpVerification'])->name('otp-verification');
Route::post('/otp-verification', [CustomerPasswordResetController::class, 'verifyOtp'])->name('otp-verification.verify');
Route::post('/otp-verification/resend', [CustomerPasswordResetController::class, 'resendOtp'])->name('otp-verification.resend');
Route::get('/reset-password', [CustomerPasswordResetController::class, 'showResetForm'])->name('customer.reset-password');
Route::post('/reset-password', [CustomerPasswordResetController::class, 'resetPassword'])->name('customer.reset-password.update');
Route::get('/register', [HomeController::class, 'register'])->name('register');
Route::post('/register', [MemberRegisterController::class, 'store'])->name('register.store');

// Customer protected routes
Route::middleware('auth:customer')->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout/address/save', [CheckoutController::class, 'saveAddress'])->name('checkout.address.save');
    Route::post('/checkout/address/set-default', [CheckoutController::class, 'setDefaultAddress'])->name('checkout.address.set-default');
    Route::delete('/checkout/address/delete', [CheckoutController::class, 'deleteAddress'])->name('checkout.address.delete');
});

Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::get('/cart/count', [CartController::class, 'count'])->name('cart.count');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');

Route::get('/shop/{slug?}', [ShopCategoryController::class, 'index'])->name('shop-by-category');
Route::post('/shop/filter', [ShopCategoryController::class, 'filter'])->name('shop.filter');
Route::get('/product/{slug}', [HomeController::class, 'detail'])->name('product.detail');

Route::get('robots.txt', function () {
    $host = request()->getHost();
    if (str_contains($host, 'royalgujarati')) {
        $content = "User-agent: *\nDisallow: /";
    } else {
        $content = "User-agent: *\nDisallow:";
    }
    return response($content, 200)->header('Content-Type', 'text/plain');
});

// Required by Laravel's password broker to generate reset URL in email
Route::get('/admin/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset')->middleware('guest:web');

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });

    // Guest routes
    Route::middleware('guest:web')->group(function () {
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login']);

        Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.request');
        Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');

        Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
        Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword'])->name('password.update');
    });

    // Authenticated routes
    Route::middleware('auth:web')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Contact Inquiries
        Route::get('contact-inquiries/data', [ContactInquiryController::class, 'data'])->name('contact-inquiries.data');
        Route::get('contact-inquiries', [ContactInquiryController::class, 'index'])->name('contact-inquiries.index');
        Route::get('contact-inquiries/{contactInquiry}', [ContactInquiryController::class, 'show'])->name('contact-inquiries.show');
        Route::delete('contact-inquiries/{contactInquiry}', [ContactInquiryController::class, 'destroy'])->name('contact-inquiries.destroy');

        // Products
        Route::get('products/data', [ProductController::class, 'data'])->name('products.data');
        Route::get('products/sub-categories', [ProductController::class, 'getSubCategories'])->name('products.sub-categories');
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
        Route::patch('purchases/{purchase}/payment-status', [PurchaseInvoiceController::class, 'updatePaymentStatus'])->name('purchases.update-payment-status');

        // Suppliers
        Route::get('suppliers/data', [SupplierController::class, 'data'])->name('suppliers.data');
        Route::resource('suppliers', SupplierController::class)->except('show');
        Route::patch('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');

        // Customers
        Route::get('customers/data', [CustomerController::class, 'data'])->name('customers.data');
        Route::resource('customers', CustomerController::class)->except('show');
        Route::patch('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');

        // Coupons
        Route::get('coupons/data', [CouponController::class, 'data'])->name('coupons.data');
        Route::resource('coupons', CouponController::class)->except('show');
        Route::patch('coupons/{coupon}/toggle-status', [CouponController::class, 'toggleStatus'])->name('coupons.toggle-status');

        // Inventory Stock API (for Sales)
        Route::get('inventory/stock', [InventoryController::class, 'stock'])->name('inventory.stock');

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('products', [ReportController::class, 'products'])->name('products');
            Route::get('products/export', [ReportController::class, 'exportProducts'])->name('products.export');
            Route::get('stock-inventory', [ReportController::class, 'stockInventory'])->name('stock-inventory');
            Route::get('stock-inventory/export', [ReportController::class, 'exportStockInventory'])->name('stock-inventory.export');
            Route::get('purchases', [ReportController::class, 'purchases'])->name('purchases');
            Route::get('purchases/export', [ReportController::class, 'exportPurchases'])->name('purchases.export');
            Route::get('sales', [ReportController::class, 'sales'])->name('sales');
            Route::get('sales/export', [ReportController::class, 'exportSales'])->name('sales.export');
            Route::get('profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
            Route::get('profit-loss/export', [ReportController::class, 'exportProfitLoss'])->name('profit-loss.export');
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
        Route::patch('categories/{category}/toggle-featured', [CategoryController::class, 'toggleFeatured'])->name('categories.toggle-featured');

        // Sub Categories
        Route::get('sub-categories/data', [SubCategoryController::class, 'data'])->name('sub-categories.data');
        Route::resource('sub-categories', SubCategoryController::class)->except('show');
        Route::patch('sub-categories/{sub_category}/toggle-status', [SubCategoryController::class, 'toggleStatus'])->name('sub-categories.toggle-status');

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

        // Modules
        Route::get('modules', [ModuleController::class, 'index'])->name('modules.index');
        Route::post('modules/reorder', [ModuleController::class, 'reorder'])->name('modules.reorder');

        // Users
        Route::get('users/data', [UserController::class, 'data'])->name('users.data');
        Route::get('users/{user}/change-password', [UserController::class, 'showChangePasswordForm'])->name('users.change-password');
        Route::post('users/{user}/change-password', [UserController::class, 'changePassword'])->name('users.update-password');
        Route::resource('users', UserController::class)->except('show');
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

        // Attributes
        Route::get('attributes/data', [AttributeController::class, 'data'])->name('attributes.data');
        Route::get('attributes/values-list', [AttributeController::class, 'getAttributesWithValues'])->name('attributes.values-list');
        Route::post('attributes/values', [AttributeController::class, 'storeValue'])->name('attributes.values.store');
        Route::post('attributes/quick-store', [AttributeController::class, 'quickStore'])->name('attributes.quick-store');
        Route::resource('attributes', AttributeController::class)->except('show');
        Route::patch('attributes/{attribute}/toggle-status', [AttributeController::class, 'toggleStatus'])->name('attributes.toggle-status');

        // Profile
        Route::get('profile/change-password', [ProfileController::class, 'showChangePasswordForm'])->name('profile.change-password');
        Route::post('profile/change-password', [ProfileController::class, 'changePassword'])->name('profile.update-password');

        // Website Content
        Route::get('website-content', [WebsiteContentController::class, 'index'])->name('website-content.index');
        Route::post('website-content', [WebsiteContentController::class, 'update'])->name('website-content.update');
    });

});
