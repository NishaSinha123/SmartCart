<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // DASHBOARD
    public function dashboard()
    {
        $totalUsers = User::count();
        $totalSellers = User::where('role', 'seller')->count();
        $totalCustomers = User::where('role', 'user')->count();
        $activeUsers = User::where('is_active', true)->count();
        $blockedUsers = User::where('is_active', false)->count();
        
        $totalProducts = Product::withTrashed()->count();
        $activeProducts = Product::where('quantity', '>', 0)->count();
        $outOfStock = Product::where('quantity', 0)->count();
        
        $totalOrders = Order::count();
        $pendingOrders = Order::where('order_status', 'pending')->count();
        $processingOrders = Order::where('order_status', 'processing')->count();
        $shippedOrders = Order::where('order_status', 'shipped')->count();
        $deliveredOrders = Order::where('order_status', 'delivered')->count();
        $cancelledOrders = Order::where('order_status', 'cancelled')->count();
        $returnRequested = Order::where('order_status', 'return_requested')->count();
        
        $totalRevenue = Order::where('order_status', 'delivered')->sum('total_amount');
        $platformCommission = $totalRevenue * 0.10;
        
        $recentOrders = Order::with('user')->latest()->limit(10)->get();
        $recentUsers = User::latest()->limit(10)->get();
        
        return view('admin.index', compact(
            'totalUsers', 'totalSellers', 'totalCustomers', 'activeUsers', 'blockedUsers',
            'totalProducts', 'activeProducts', 'outOfStock',
            'totalOrders', 'pendingOrders', 'processingOrders', 'shippedOrders', 
            'deliveredOrders', 'cancelledOrders', 'returnRequested',
            'totalRevenue', 'platformCommission', 'recentOrders', 'recentUsers'
        ));
    }
    
    // USERS MANAGEMENT
    public function users(Request $request)
    {
        $query = User::query();
        
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        if ($request->filled('status')) {
            $query->where('is_active', $request->status == 'active');
        }
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }
        
        $users = $query->latest()->paginate(20);
        
        $stats = [
            'total' => User::count(),
            'sellers' => User::where('role', 'seller')->count(),
            'customers' => User::where('role', 'user')->count(),
            'admins' => User::where('role', 'admin')->count(),
            'active' => User::where('is_active', true)->count(),
            'blocked' => User::where('is_active', false)->count(),
        ];
        
        return view('admin.users', compact('users', 'stats'));
    }
    
    public function userShow(User $user)
    {
        $orders = $user->orders()->with('items.product')->latest()->limit(20)->get();
        $totalSpent = $user->orders()->where('order_status', 'delivered')->sum('total_amount');
        
        if ($user->isSeller()) {
            $products = $user->products()->withTrashed()->get();
            $totalEarnings = $user->total_earnings;
        } else {
            $products = collect();
            $totalEarnings = 0;
        }
        
        return view('admin.user-show', compact('user', 'orders', 'totalSpent', 'products', 'totalEarnings'));
    }
    
    public function userBlock(User $user, Request $request)
    {
        $request->validate(['reason' => 'nullable|string']);
        $user->block($request->reason);
        return back()->with('success', "User {$user->name} has been blocked.");
    }
    
    public function userUnblock(User $user)
    {
        $user->unblock();
        return back()->with('success', "User {$user->name} has been unblocked.");
    }
    
    public function userDelete(User $user)
    {
        if ($user->role == 'admin') {
            return back()->with('error', 'Cannot delete admin user.');
        }
        $user->delete();
        return redirect()->route('admin.users')->with('success', 'User deleted successfully.');
    }
    
    public function userCreate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role' => 'required|in:user,seller',
        ]);
        
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        
        return redirect()->route('admin.users')->with('success', 'User created successfully.');
    }
    
    // SELLERS MANAGEMENT
    public function sellers()
    {
        $sellers = User::where('role', 'seller')
            ->withCount('products')
            ->latest()
            ->paginate(20);
        
        return view('admin.sellers', compact('sellers'));
    }
    
    public function sellerPayouts()
    {
        $sellers = User::where('role', 'seller')->get();
        
        $payouts = $sellers->map(function($seller) {
            $earnings = \App\Models\OrderItem::whereHas('product', function($q) use ($seller) {
                $q->where('seller_id', $seller->id);
            })->whereHas('order', function($q) {
                $q->where('order_status', 'delivered');
            })->sum(\DB::raw('price * quantity * 0.90'));
            
            $pending = \App\Models\OrderItem::whereHas('product', function($q) use ($seller) {
                $q->where('seller_id', $seller->id);
            })->whereHas('order', function($q) {
                $q->whereIn('order_status', ['pending', 'processing', 'shipped']);
            })->sum(\DB::raw('price * quantity * 0.90'));
            
            return (object)[
                'seller' => $seller,
                'earnings' => $earnings,
                'pending' => $pending,
            ];
        });
        
        return view('admin.payouts', compact('payouts'));
    }
    
    // PRODUCTS MANAGEMENT
    public function products(Request $request)
    {
        $query = Product::with('seller')->withTrashed();
        
        if ($request->filled('status')) {
            if ($request->status == 'deleted') {
                $query->onlyTrashed();
            } elseif ($request->status == 'out_of_stock') {
                $query->where('quantity', 0);
            } elseif ($request->status == 'low_stock') {
                $query->where('quantity', '>', 0)->where('quantity', '<=', 5);
            }
        }
        
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }
        
        $products = $query->latest()->paginate(20);
        
        $stats = [
            'total' => Product::withTrashed()->count(),
            'active' => Product::count(),
            'deleted' => Product::onlyTrashed()->count(),
            'out_of_stock' => Product::where('quantity', 0)->count(),
            'low_stock' => Product::where('quantity', '>', 0)->where('quantity', '<=', 5)->count(),
        ];
        
        return view('admin.products', compact('products', 'stats'));
    }
    
    public function productForceDelete($id)
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->forceDelete();
        return back()->with('success', 'Product permanently deleted.');
    }
    
    public function productRestore($id)
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->restore();
        return back()->with('success', 'Product restored successfully.');
    }
    
    // ORDERS MANAGEMENT
    public function orders(Request $request)
    {
        $query = Order::with('user');
        
        if ($request->filled('status')) {
            $query->where('order_status', $request->status);
        }
        
        if ($request->filled('search')) {
            $query->where('id', $request->search);
        }
        
        $orders = $query->latest()->paginate(20);
        
        $stats = [
            'total' => Order::count(),
            'pending' => Order::where('order_status', 'pending')->count(),
            'processing' => Order::where('order_status', 'processing')->count(),
            'shipped' => Order::where('order_status', 'shipped')->count(),
            'delivered' => Order::where('order_status', 'delivered')->count(),
            'cancelled' => Order::where('order_status', 'cancelled')->count(),
            'return_requested' => Order::where('order_status', 'return_requested')->count(),
        ];
        
        return view('admin.orders', compact('orders', 'stats'));
    }
    
    public function orderShow(Order $order)
    {
        $order->load('user', 'items.product', 'shippingAddress');
        return view('admin.order-show', compact('order'));
    }
    
    public function orderUpdateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,processing,shipped,out_for_delivery,delivered,cancelled'
        ]);
        
        $order->update(['order_status' => $request->status]);
        
        if ($request->status == 'delivered' && !$order->delivered_at) {
            $order->update(['delivered_at' => now()]);
        }
        
        return back()->with('success', 'Order status updated.');
    }
}