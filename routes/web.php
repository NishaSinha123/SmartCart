<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\GoogleLoginController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Budget;
use App\Models\Order;

// Public Routes
Route::get('/', function () {
    return view('welcome');
});

// Dashboard Route (Dynamic based on role)
Route::get('/dashboard', function () {
    $user = auth()->user();
    
    if (!$user) {
        return redirect()->route('login');
    }
    
    if ($user->isAdmin()) {
        return view('admin.dashboard');
    }
    
    if ($user->isSeller()) {
        return view('seller.dashboard');
    }
    
    // For normal users, check email verification
    if (!$user->hasVerifiedEmail()) {
        return redirect()->route('verification.notice');
    }
    
    // User Dashboard Data
    $cart = Cart::where('user_id', $user->id)->first();
    $cartItems = [];
    $cartTotal = 0;
    $cartItemCount = 0;
    
    if ($cart) {
        $cartItems = CartItem::where('cart_id', $cart->id)->with('product')->get();
        foreach ($cartItems as $item) {
            $cartTotal += $item->product->price * $item->quantity;
            $cartItemCount += $item->quantity;
        }
    }
    
    $budget = Budget::where('user_id', $user->id)->first();
    $budgetAmount = $budget ? $budget->amount : 0;
    $budgetRemaining = max(0, $budgetAmount - $cartTotal);
    $budgetPercentage = $budgetAmount > 0 ? round(($cartTotal / $budgetAmount) * 100) : 0;
    $ordersCount = Order::where('user_id', $user->id)->count();
    $recentOrders = Order::where('user_id', $user->id)->orderBy('created_at', 'desc')->limit(5)->get();
    
    return view('dashboard', compact('cartItems', 'cartTotal', 'cartItemCount', 'budget', 'budgetAmount', 'budgetRemaining', 'budgetPercentage', 'ordersCount', 'recentOrders'));
})->middleware(['auth'])->name('dashboard');

// Google Login Routes
Route::get('login/google', [GoogleLoginController::class, 'redirectToGoogle'])->name('login.google');
Route::get('auth/google/callback', [GoogleLoginController::class, 'handleGoogleCallback'])->name('login.google.callback');

// Profile Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Product Routes
Route::resource('products', ProductController::class)->middleware(['auth']);

// Cart Routes
Route::middleware(['auth'])->prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('cart.index');
    Route::post('/add', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/update/{id}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
    Route::delete('/clear', [CartController::class, 'clear'])->name('cart.clear');
});

// Budget Routes
Route::middleware(['auth'])->prefix('budget')->group(function () {
    Route::get('/', [BudgetController::class, 'index'])->name('budget.index');
    Route::post('/', [BudgetController::class, 'store'])->name('budget.store');
    Route::get('/status', [BudgetController::class, 'getStatus'])->name('budget.status');
    Route::get('/insights', [BudgetController::class, 'insights'])->name('budget.insights');
});

// Address Routes
Route::resource('addresses', AddressController::class)->middleware(['auth']);
Route::post('/addresses/{address}/set-default', [AddressController::class, 'setDefault'])->name('addresses.set-default')->middleware(['auth']);

// Order Routes
Route::middleware(['auth'])->prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/checkout', [OrderController::class, 'checkout'])->name('orders.checkout');
    Route::post('/place', [OrderController::class, 'placeOrder'])->name('orders.place');
    Route::get('/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::get('/{order}/track', [OrderController::class, 'track'])->name('orders.track');
    Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/{order}/return', [OrderController::class, 'requestReturn'])->name('orders.return');
    Route::get('/{order}/payment', [OrderController::class, 'payment'])->name('orders.payment');
    Route::post('/{order}/payment-proof', [OrderController::class, 'uploadPaymentProof'])->name('orders.payment-proof');
    
    // Razorpay payment verification
    Route::post('/payment/verify', [OrderController::class, 'verifyPayment'])->name('orders.payment.verify');
});

// Seller Routes
Route::middleware(['auth', 'seller'])->prefix('seller')->group(function () {
    Route::get('/dashboard', function () { return view('seller.dashboard'); })->name('seller.dashboard');
    Route::get('/orders', [OrderController::class, 'sellerOrders'])->name('seller.orders');
    Route::get('/orders/{order}', [OrderController::class, 'sellerOrderShow'])->name('seller.orders.show');
    Route::post('/orders/{order}/update-status', [OrderController::class, 'updateOrderStatus'])->name('seller.orders.update-status');
    Route::post('/orders/{order}/approve-return', [OrderController::class, 'approveReturn'])->name('seller.orders.approve-return');
    Route::post('/orders/{order}/reject-return', [OrderController::class, 'rejectReturn'])->name('seller.orders.reject-return');
    Route::post('/orders/{order}/process-refund', [OrderController::class, 'processRefund'])->name('seller.orders.process-refund');
});

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/users/{user}', [AdminController::class, 'userShow'])->name('users.show');
    Route::post('/users/{user}/block', [AdminController::class, 'userBlock'])->name('users.block');
    Route::post('/users/{user}/unblock', [AdminController::class, 'userUnblock'])->name('users.unblock');
    Route::delete('/users/{user}', [AdminController::class, 'userDelete'])->name('users.delete');
    Route::post('/users/create', [AdminController::class, 'userCreate'])->name('users.create');
    Route::get('/sellers', [AdminController::class, 'sellers'])->name('sellers');
    Route::get('/payouts', [AdminController::class, 'sellerPayouts'])->name('payouts');
    Route::get('/products', [AdminController::class, 'products'])->name('products');
    Route::delete('/products/{id}/force-delete', [AdminController::class, 'productForceDelete'])->name('products.force-delete');
    Route::post('/products/{id}/restore', [AdminController::class, 'productRestore'])->name('products.restore');
    Route::get('/orders', [AdminController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [AdminController::class, 'orderShow'])->name('orders.show');
    Route::post('/orders/{order}/update-status', [AdminController::class, 'orderUpdateStatus'])->name('orders.update-status');
});

// terms and privacy policy routes
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');

// Auth routes (Breeze default - includes email verification routes)
require __DIR__ . '/auth.php';