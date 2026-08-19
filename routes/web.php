<?php

use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\UtilityReportController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactInquiryController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerLoginController;
use App\Http\Controllers\CustomerPasswordResetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\LocationBalanceController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MemberRegisterController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductBulkImageUploadController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\PurchaseImportController;
use App\Http\Controllers\PurchaseBillController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ShopCategoryController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\SubCategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Website\ProductReviewController as WebsiteProductReviewController;
use App\Http\Controllers\Website\ProfileController as WebsiteProfileController;
use App\Http\Controllers\WebsiteContentController;
use App\Http\Controllers\WishlistController;
use App\Models\Setting;
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
    Route::get('/my-account', [WebsiteProfileController::class, 'index'])->name('customer.profile');
    Route::get('/my-account/order/{id}', [WebsiteProfileController::class, 'viewOrder'])->name('customer.profile.view-order');
    Route::post('/my-account/order/{id}/cancel', [WebsiteProfileController::class, 'cancelOrder'])->name('customer.profile.cancel-order');
    Route::get('/my-account/order/{id}/invoice', [WebsiteProfileController::class, 'downloadInvoice'])->name('customer.profile.order-invoice');
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
    Route::post('/checkout/address/save', [CheckoutController::class, 'saveAddress'])->name('checkout.address.save');
    Route::patch('/checkout/address/update', [CheckoutController::class, 'updateAddress'])->name('checkout.address.update');
    Route::post('/checkout/address/set-default', [CheckoutController::class, 'setDefaultAddress'])->name('checkout.address.set-default');
    Route::delete('/checkout/address/delete', [CheckoutController::class, 'deleteAddress'])->name('checkout.address.delete');
    Route::post('/customer/profile/update', [WebsiteProfileController::class, 'updateProfile'])->name('customer.profile.update');
    Route::post('/customer/profile/change-password', [WebsiteProfileController::class, 'updateCustomerPassword'])->name('customer.profile.update-password');
    Route::post('/customer/profile/avatar', [WebsiteProfileController::class, 'updateAvatar'])->name('customer.profile.avatar');
    Route::post('/customer/reviews', [WebsiteProductReviewController::class, 'store'])->name('customer.reviews.store');
    Route::post('/checkout/payment/initialize', [CheckoutController::class, 'initializePayment'])->name('checkout.payment.initialize');
    Route::post('/checkout/payment/verify', [CheckoutController::class, 'verifyPayment'])->name('checkout.payment.verify');
    Route::post('/checkout/payment/failed', [CheckoutController::class, 'failedPayment'])->name('checkout.payment.failed');
    Route::post('/checkout/coupon/apply', [CheckoutController::class, 'applyCoupon'])->name('checkout.coupon.apply');
    Route::post('/checkout/coupon/remove', [CheckoutController::class, 'removeCoupon'])->name('checkout.coupon.remove');
    Route::post('/checkout/shipping/recalculate', [CheckoutController::class, 'recalculateShipping'])->name('checkout.shipping.recalculate');
    Route::post('/checkout/payment/cod', [CheckoutController::class, 'placeCodOrder'])->name('checkout.payment.cod');
    Route::post('/buy-now/payment/initialize', [CheckoutController::class, 'buyNowInitialize'])->name('buynow.payment.initialize');
    Route::post('/buy-now/coupon/validate', [CheckoutController::class, 'validateBuyNowCoupon'])->name('buynow.coupon.validate');
});

Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::get('/cart/count', [CartController::class, 'count'])->name('cart.count');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');

Route::get('/shop/{slug?}', [ShopCategoryController::class, 'index'])->name('shop-by-category');
Route::post('/shop/filter', [ShopCategoryController::class, 'filter'])->name('shop.filter');
Route::get('/product/{slug}', [HomeController::class, 'detail'])->name('product.detail');

// Required by Laravel's password broker to generate reset URL in email
Route::get('/admin/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset')->middleware('admin.guest:web');

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });

    // Guest routes
    Route::middleware('admin.guest:web')->group(function () {
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login']);

        Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.request');
        Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');

        Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
        Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword'])->name('password.update');
    });

    // Authenticated routes
    Route::middleware(['auth:web', 'active.user'])->group(function () {
        Route::get('/ping', fn () => response()->json(['status' => 'pong', 'time' => time()]))->name('ping');
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Contact Inquiries
        Route::get('contact-inquiries/data', [ContactInquiryController::class, 'data'])->name('contact-inquiries.data');
        Route::get('contact-inquiries', [ContactInquiryController::class, 'index'])->name('contact-inquiries.index');
        Route::get('contact-inquiries/{contactInquiry}', [ContactInquiryController::class, 'show'])->name('contact-inquiries.show');
        Route::delete('contact-inquiries/{contactInquiry}', [ContactInquiryController::class, 'destroy'])->name('contact-inquiries.destroy');

        // Products
        Route::get('products/import/sample', [ProductImportController::class, 'sample'])->name('products.import.sample');
        Route::post('products/import', [ProductImportController::class, 'store'])->name('products.import');
        Route::get('products/bulk-images/sample', [ProductBulkImageUploadController::class, 'sample'])->name('products.bulk-images.sample');
        Route::get('products/bulk-images', [ProductBulkImageUploadController::class, 'form'])->name('products.bulk-images.form');
        Route::post('products/bulk-images', [ProductBulkImageUploadController::class, 'store'])->name('products.bulk-images.store');
        Route::get('products/data', [ProductController::class, 'data'])->name('products.data');
        Route::get('products/sub-categories', [ProductController::class, 'getSubCategories'])->name('products.sub-categories');
        Route::get('products/search', [ProductController::class, 'search'])->name('products.search');
        Route::get('products/print-barcodes', [ProductController::class, 'printBarcodes'])->name('products.print-barcodes');
        Route::post('products/print-barcodes/prepare', [ProductController::class, 'prepareBarcodePrint'])->name('products.print-barcodes.prepare');
        Route::post('products/bulk-toggle-website', [ProductController::class, 'bulkToggleWebsite'])->name('products.bulk-toggle-website');
        Route::resource('products', ProductController::class)->except('show');
        Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::get('products/{product}/purchase-history', [ProductController::class, 'purchaseHistoryData'])->name('products.purchase-history');
        Route::get('products/{product}/transfer-history', [ProductController::class, 'transferHistoryData'])->name('products.transfer-history');
        Route::get('products/{product}/sale-history', [ProductController::class, 'saleHistoryData'])->name('products.sale-history');
        Route::get('products/{product}/barcode', [ProductController::class, 'generateBarcodeImage'])->name('products.barcode');
        Route::patch('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
        Route::delete('products/images/{image}', [ProductController::class, 'destroyImage'])->name('products.images.destroy');
        Route::patch('products/images/{image}/primary', [ProductController::class, 'setPrimaryImage'])->name('products.images.primary');

        // Purchases
        Route::get('purchases/products-json', [PurchaseController::class, 'getMappedProductsJson'])->name('purchases.products-json');
        Route::get('purchases/import/sample', [PurchaseImportController::class, 'sample'])->name('purchases.import.sample');
        Route::post('purchases/import/preview', [PurchaseImportController::class, 'preview'])->name('purchases.import.preview');
        Route::post('purchases/import/confirm', [PurchaseImportController::class, 'confirm'])->name('purchases.import.confirm');
        Route::delete('purchases/import/cancel', [PurchaseImportController::class, 'cancel'])->name('purchases.import.cancel');
        Route::get('purchases/data', [PurchaseController::class, 'data'])->name('purchases.data');
        Route::get('products/{product}/price', [PurchaseController::class, 'getProductPrice'])->name('products.price');
        Route::resource('purchases', PurchaseController::class)->except('show');
        Route::get('purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
        Route::get('purchases/{purchase}/pdf', [PurchaseController::class, 'pdf'])->name('purchases.pdf');
        Route::patch('purchases/{purchase}/status', [PurchaseController::class, 'updateStatus'])->name('purchases.status');
        Route::patch('purchases/{purchase}/payment-status', [PurchaseController::class, 'updatePaymentStatus'])->name('purchases.update-payment-status');
        Route::get('purchases/{purchase}/payment-history', [PurchaseController::class, 'paymentHistory'])->name('purchases.payment-history');
        Route::get('purchases/{purchase}/barcode-items', [PurchaseController::class, 'barcodeItems'])->name('purchases.barcode-items');

        // Purchase Bills
        Route::get('purchase-bills/products-json', [PurchaseBillController::class, 'getMappedProductsJson'])->name('purchase-bills.products-json');
        Route::get('purchase-bills/data', [PurchaseBillController::class, 'data'])->name('purchase-bills.data');
        Route::get('purchase-bills/export', [PurchaseBillController::class, 'export'])->name('purchase-bills.export');
        Route::get('purchase-bills/pending-count', [PurchaseBillController::class, 'pendingCount'])->name('purchase-bills.pending-count');
        Route::patch('purchase-bills/{purchaseBill}/accept', [PurchaseBillController::class, 'accept'])->name('purchase-bills.accept');
        Route::patch('purchase-bills/{purchaseBill}/reject', [PurchaseBillController::class, 'reject'])->name('purchase-bills.reject');
        Route::patch('purchase-bills/{purchaseBill}/payment-status', [PurchaseBillController::class, 'updatePaymentStatus'])->name('purchase-bills.update-payment-status');
        Route::get('purchase-bills/{purchaseBill}/payment-history', [PurchaseBillController::class, 'paymentHistory'])->name('purchase-bills.payment-history');
        Route::resource('purchase-bills', PurchaseBillController::class)
            ->parameters(['purchase-bills' => 'purchaseBill'])
            ->only(['index', 'create', 'store', 'show', 'edit', 'update']);

        // Suppliers
        Route::get('suppliers/data', [SupplierController::class, 'data'])->name('suppliers.data');
        Route::resource('suppliers', SupplierController::class)->except('show');
        Route::patch('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');

        // Expenses
        Route::get('expenses/data', [ExpenseController::class, 'data'])->name('expenses.data');
        Route::resource('expenses', ExpenseController::class)->except('show');

        // Customers
        Route::get('customers/data', [CustomerController::class, 'data'])->name('customers.data');
        Route::resource('customers', CustomerController::class)->except('show');
        Route::patch('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');
        Route::patch('customers/{customer}/toggle-credit-customer', [CustomerController::class, 'toggleCreditCustomer'])->name('customers.toggle-credit-customer');

        // Product Reviews
        Route::get('product-reviews/data', [ProductReviewController::class, 'data'])->name('product-reviews.data');
        Route::get('product-reviews', [ProductReviewController::class, 'index'])->name('product-reviews.index');
        Route::delete('product-reviews/{productReview}', [ProductReviewController::class, 'destroy'])->name('product-reviews.destroy');

        // Coupons
        Route::get('coupons/data', [CouponController::class, 'data'])->name('coupons.data');
        Route::resource('coupons', CouponController::class)->except('show');
        Route::patch('coupons/{coupon}/toggle-status', [CouponController::class, 'toggleStatus'])->name('coupons.toggle-status');

        // Inventory Stock API (for Sales)
        Route::get('inventory/stock', [InventoryController::class, 'stock'])->name('inventory.stock');

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('products', [ReportController::class, 'products'])->name('products');
            Route::get('products/data', [ReportController::class, 'productsData'])->name('products.data');
            Route::get('products/export', [ReportController::class, 'exportProducts'])->name('products.export');
            Route::get('stock-inventory', [ReportController::class, 'stockInventory'])->name('stock-inventory');
            Route::get('stock-inventory/data', [ReportController::class, 'stockInventoryData'])->name('stock-inventory.data');
            Route::get('stock-inventory/totals', [ReportController::class, 'stockInventoryTotals'])->name('stock-inventory.totals');
            Route::get('stock-inventory/export', [ReportController::class, 'exportStockInventory'])->name('stock-inventory.export');
            Route::get('purchases', [ReportController::class, 'purchases'])->name('purchases');
            Route::get('purchases/data', [ReportController::class, 'purchasesData'])->name('purchases.data');
            Route::get('purchases/products-data', [ReportController::class, 'purchasesProductsData'])->name('purchases.products-data');
            Route::get('purchases/export', [ReportController::class, 'exportPurchases'])->name('purchases.export');
            Route::get('sales', [ReportController::class, 'sales'])->name('sales');
            Route::get('sales/data', [ReportController::class, 'salesData'])->name('sales.data');
            Route::get('sales/products-data', [ReportController::class, 'salesProductsData'])->name('sales.products-data');
            Route::get('sales/export', [ReportController::class, 'exportSales'])->name('sales.export');
            Route::get('profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
            Route::get('profit-loss/data', [ReportController::class, 'profitLossData'])->name('profit-loss.data');
            Route::get('profit-loss/export', [ReportController::class, 'exportProfitLoss'])->name('profit-loss.export');
            Route::get('payments', [ReportController::class, 'payments'])->name('payments');
            Route::get('payments/export', [ReportController::class, 'exportPayments'])->name('payments.export');
            Route::get('daily-report', [ReportController::class, 'dailyReport'])->name('daily-report');
            Route::get('daily-report/data', [ReportController::class, 'dailyReportData'])->name('daily-report.data');
            Route::get('daily-report/export', [ReportController::class, 'exportDailyReport'])->name('daily-report.export');
            Route::get('customer-report', [ReportController::class, 'customerReport'])->name('customer-report');
            Route::get('customer-report/export', [ReportController::class, 'exportCustomerReport'])->name('customer-report.export');
            Route::get('customer-report/detail', [ReportController::class, 'customerReportDetail'])->name('customer-report.detail');
            Route::get('customer-report/sale-products', [ReportController::class, 'customerReportSaleProducts'])->name('customer-report.sale-products');

            // Utility Report
            Route::get('utility/data', [UtilityReportController::class, 'data'])->name('utility.data');
            Route::get('utility/export', [UtilityReportController::class, 'export'])->name('utility.export');
            Route::get('utility/{utilityReport}', [UtilityReportController::class, 'show'])->name('utility.show');
            Route::get('utility', [UtilityReportController::class, 'index'])->name('utility');
        });

        // Sales
        Route::get('sales/products-json', [SaleController::class, 'getAllMappedProductsJson'])->name('sales.products-json');
        Route::get('sales/data', [SaleController::class, 'data'])->name('sales.data');
        Route::resource('sales', SaleController::class)->except(['show', 'destroy']);
        Route::get('sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::delete('sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');
        Route::get('sales/{sale}/pdf', [SaleController::class, 'pdf'])->name('sales.pdf');
        Route::get('sales/{sale}/tax-invoice', [SaleController::class, 'taxInvoice'])->name('sales.tax-invoice');
        Route::get('sales/{sale}/thermal', [SaleController::class, 'thermal'])->name('sales.thermal');
        Route::get('sales/{sale}/label', [SaleController::class, 'label'])->name('sales.label');
        Route::patch('sales/{sale}/status', [SaleController::class, 'updateStatus'])->name('sales.status');
        Route::get('sales/{sale}/payment-history', [SaleController::class, 'paymentHistory'])->name('sales.payment-history');
        Route::post('sales/{sale}/cancellation/approve', [SaleController::class, 'approveCancellation'])->name('sales.cancellation.approve');
        Route::post('sales/{sale}/cancellation/reject', [SaleController::class, 'rejectCancellation'])->name('sales.cancellation.reject');

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

        // Location Balances
        Route::get('locations/{location}/balance', [LocationBalanceController::class, 'show'])->name('locations.balance.show');
        Route::get('locations/{location}/balance/create', [LocationBalanceController::class, 'create'])->name('locations.balance.create');
        Route::get('locations/{location}/balance/data', [LocationBalanceController::class, 'data'])->name('locations.balance.data');
        Route::post('locations/{location}/balance', [LocationBalanceController::class, 'store'])->name('locations.balance.store');

        // Ledgers
        Route::get('ledgers/supplier', [LedgerController::class, 'supplierLedger'])->name('ledgers.supplier');
        Route::get('ledgers/supplier/data', [LedgerController::class, 'supplierLedgerData'])->name('ledgers.supplier.data');
        Route::get('ledgers/supplier/detail', [LedgerController::class, 'supplierLedgerDetail'])->name('ledgers.supplier.detail');
        Route::get('ledgers/supplier/export', [LedgerController::class, 'exportSupplierLedger'])->name('ledgers.supplier.export');
        Route::get('ledgers/customer', [LedgerController::class, 'customerLedger'])->name('ledgers.customer');
        Route::get('ledgers/customer/data', [LedgerController::class, 'customerLedgerData'])->name('ledgers.customer.data');
        Route::get('ledgers/customer/detail', [LedgerController::class, 'customerLedgerDetail'])->name('ledgers.customer.detail');
        Route::get('ledgers/customer/export', [LedgerController::class, 'exportCustomerLedger'])->name('ledgers.customer.export');
        Route::get('ledgers/cash', [LedgerController::class, 'cashLedger'])->name('ledgers.cash');
        Route::get('ledgers/cash/data', [LedgerController::class, 'cashLedgerData'])->name('ledgers.cash.data');
        Route::get('ledgers/cash/detail', [LedgerController::class, 'cashLedgerDetail'])->name('ledgers.cash.detail');
        Route::get('ledgers/cash/export', [LedgerController::class, 'exportCashLedger'])->name('ledgers.cash.export');
        Route::get('ledgers/bank', [LedgerController::class, 'bankLedger'])->name('ledgers.bank');
        Route::get('ledgers/bank/data', [LedgerController::class, 'bankLedgerData'])->name('ledgers.bank.data');
        Route::get('ledgers/bank/detail', [LedgerController::class, 'bankLedgerDetail'])->name('ledgers.bank.detail');
        Route::get('ledgers/bank/export', [LedgerController::class, 'exportBankLedger'])->name('ledgers.bank.export');
        Route::get('ledgers/branch', [LedgerController::class, 'branchLedger'])->name('ledgers.branch');
        Route::get('ledgers/branch/data', [LedgerController::class, 'branchLedgerData'])->name('ledgers.branch.data');
        Route::get('ledgers/branch/detail', [LedgerController::class, 'branchLedgerDetail'])->name('ledgers.branch.detail');
        Route::get('ledgers/branch/dues-bills', [LedgerController::class, 'branchDuesBills'])->name('ledgers.branch.dues-bills');
        Route::get('ledgers/branch/export', [LedgerController::class, 'exportBranchLedger'])->name('ledgers.branch.export');
        Route::get('ledgers/branch/pending-count', [LedgerController::class, 'branchLedgerPendingCount'])->name('ledgers.branch.pending-count');

        // Accounting
        Route::get('accounting/cashbook', [AccountingController::class, 'cashBook'])->name('accounting.cashbook');
        Route::get('accounting/cashbook/data', [AccountingController::class, 'cashBookData'])->name('accounting.cashbook.data');
        Route::get('accounting/bankbook', [AccountingController::class, 'bankBook'])->name('accounting.bankbook');
        Route::get('accounting/bankbook/data', [AccountingController::class, 'bankBookData'])->name('accounting.bankbook.data');
        Route::get('accounting/general-ledger', [AccountingController::class, 'generalLedger'])->name('accounting.general-ledger');
        Route::get('accounting/general-ledger/data', [AccountingController::class, 'generalLedgerData'])->name('accounting.general-ledger.data');
        Route::get('accounting/general-ledger/export', [AccountingController::class, 'exportGeneralLedger'])->name('accounting.general-ledger.export');
        Route::get('accounting/outstanding-payables', [AccountingController::class, 'outstandingPayables'])->name('accounting.outstanding-payables');
        Route::get('accounting/outstanding-payables/data', [AccountingController::class, 'outstandingPayablesData'])->name('accounting.outstanding-payables.data');
        Route::get('accounting/outstanding-payables/detail', [AccountingController::class, 'outstandingPayablesDetail'])->name('accounting.outstanding-payables.detail');
        Route::post('accounting/outstanding-payables/bulk-pay', [AccountingController::class, 'bulkPaySupplier'])->name('accounting.outstanding-payables.bulk-pay');
        Route::get('accounting/outstanding-payables/payment-history', [AccountingController::class, 'payablePaymentHistory'])->name('accounting.outstanding-payables.payment-history');
        Route::get('accounting/outstanding-payables/payment-history/data', [AccountingController::class, 'payablePaymentHistoryData'])->name('accounting.outstanding-payables.payment-history.data');
        Route::put('accounting/outstanding-payables/payment-history/{payment}', [AccountingController::class, 'payablePaymentUpdate'])->name('accounting.outstanding-payables.payment-history.update');
        Route::delete('accounting/outstanding-payables/payment-history/{payment}', [AccountingController::class, 'payablePaymentDestroy'])->name('accounting.outstanding-payables.payment-history.destroy');
        Route::get('accounting/opening-balances', [AccountingController::class, 'branchBalances'])->name('accounting.opening-balances');
        Route::get('accounting/opening-balances/data', [AccountingController::class, 'branchBalancesData'])->name('accounting.opening-balances.data');
        Route::get('accounting/opening-balances/create', [AccountingController::class, 'branchBalancesCreate'])->name('accounting.opening-balances.create');
        Route::post('accounting/opening-balances/store', [AccountingController::class, 'branchBalancesStore'])->name('accounting.opening-balances.store');
        Route::get('accounting/opening-balances/transfer', [AccountingController::class, 'branchBalancesTransferCreate'])->name('accounting.opening-balances.transfer');
        Route::post('accounting/opening-balances/transfer-store', [AccountingController::class, 'branchBalancesTransferStore'])->name('accounting.opening-balances.transfer-store');
        Route::post('accounting/opening-balances/transfer/{transfer}/accept', [AccountingController::class, 'branchBalancesTransferAccept'])->name('accounting.opening-balances.transfer-accept');
        Route::post('accounting/opening-balances/transfer/{transfer}/reject', [AccountingController::class, 'branchBalancesTransferReject'])->name('accounting.opening-balances.transfer-reject');
        Route::get('accounting/opening-balances/transfer/{transfer}/edit', [AccountingController::class, 'branchBalancesTransferEdit'])->name('accounting.opening-balances.transfer-edit');
        Route::put('accounting/opening-balances/transfer/{transfer}', [AccountingController::class, 'branchBalancesTransferUpdate'])->name('accounting.opening-balances.transfer-update');
        Route::delete('accounting/opening-balances/transfer/{transfer}', [AccountingController::class, 'branchBalancesTransferDestroy'])->name('accounting.opening-balances.transfer-destroy');
        Route::get('accounting/customer-balance/create', [AccountingController::class, 'customerBalanceCreate'])->name('accounting.customer-balance.create');
        Route::post('accounting/customer-balance/store', [AccountingController::class, 'customerBalanceStore'])->name('accounting.customer-balance.store');
        Route::get('accounting/customer-balance/{transaction}/edit', [AccountingController::class, 'customerBalanceEdit'])->name('accounting.customer-balance.edit');
        Route::put('accounting/customer-balance/{transaction}', [AccountingController::class, 'customerBalanceUpdate'])->name('accounting.customer-balance.update');
        Route::delete('accounting/customer-balance/{transaction}', [AccountingController::class, 'customerBalanceDestroy'])->name('accounting.customer-balance.destroy');
        Route::get('accounting/customer-balance/{transaction}/thermal', [AccountingController::class, 'customerBalanceThermal'])->name('accounting.customer-balance.thermal');

        // States
        Route::get('states/data', [StateController::class, 'data'])->name('states.data');
        Route::resource('states', StateController::class)->except('show');
        Route::patch('states/{state}/toggle-status', [StateController::class, 'toggleStatus'])->name('states.toggle-status');

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
        Route::get('profile/change-password', [AdminProfileController::class, 'showChangePasswordForm'])->name('profile.change-password');
        Route::post('profile/change-password', [AdminProfileController::class, 'changePassword'])->name('profile.update-password');

        // Website Content
        Route::get('website-content', [WebsiteContentController::class, 'index'])->name('website-content.index');
        Route::post('website-content', [WebsiteContentController::class, 'update'])->name('website-content.update');

        // Banners
        Route::get('banners/data', [BannerController::class, 'data'])->name('banners.data');
        Route::resource('banners', BannerController::class)->except('show');
        Route::patch('banners/{banner}/toggle-status', [BannerController::class, 'toggleStatus'])->name('banners.toggle-status');

        // Settings
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
        
        // Google Drive OAuth
        Route::get('settings/google-drive/connect', [SettingController::class, 'googleDriveConnect'])->name('settings.google-drive.connect');
        Route::get('settings/google-drive/callback', [SettingController::class, 'googleDriveCallback'])->name('settings.google-drive.callback');
        Route::post('settings/google-drive/disconnect', [SettingController::class, 'googleDriveDisconnect'])->name('settings.google-drive.disconnect');

        // AJAX Backup
        Route::post('settings/backup/run', [SettingController::class, 'runBackup'])->name('settings.backup.run');
    });
});

Route::get('/set-debug-true', function () {
    Setting::setValue('app_debug', 'true');
    return response()->json(['message' => 'Debug mode enabled. Laravel error trace will be shown.']);
});

Route::get('/set-debug-false', function () {
    Setting::setValue('app_debug', 'false');
    return response()->json(['message' => 'Debug mode disabled. Custom error pages (404/500) will be shown.']);
});
