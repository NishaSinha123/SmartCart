<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SmartCart') }} - @yield('title', 'Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f3f4f6; }
        
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            padding: 2rem 1.5rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 1000;
        }
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }
        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 2rem;
            display: block;
            text-decoration: none;
        }
        .sidebar-nav { display: flex; flex-direction: column; gap: 0.5rem; }
        .sidebar-nav a {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            color: #4b5563;
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar-nav a:hover, .sidebar-nav a.active {
            background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
            color: #667eea;
        }
        .sidebar-nav a i { width: 20px; }
        .user-info-sidebar {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        .budget-sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 1rem;
            margin-top: 1rem;
            color: white;
        }
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: white;
            padding: 0.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            cursor: pointer;
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding-top: 4rem; }
            .mobile-menu-btn { display: block; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="mobile-menu-btn" onclick="document.querySelector('.sidebar').classList.toggle('open')">
        <i class="fas fa-bars text-xl"></i>
    </div>
    
    <div class="dashboard-container">
        <aside class="sidebar">
            <a href="{{ Auth::user()->isAdmin() ? route('admin.dashboard') : (Auth::user()->isSeller() ? route('seller.dashboard') : route('dashboard')) }}" class="sidebar-logo">
                🛒 SmartCart
            </a>
            
            <nav class="sidebar-nav">
                {{-- ADMIN MENU --}}
                @if(Auth::user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                    <a href="{{ route('admin.sellers') }}" class="{{ request()->routeIs('admin.sellers*') ? 'active' : '' }}">
                        <i class="fas fa-store"></i> Manage Sellers
                    </a>
                    <a href="{{ route('admin.products') }}" class="{{ request()->routeIs('admin.products*') ? 'active' : '' }}">
                        <i class="fas fa-box"></i> All Products
                    </a>
                    <a href="{{ route('admin.orders') }}" class="{{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
                        <i class="fas fa-shopping-cart"></i> All Orders
                    </a>
                    <a href="{{ route('admin.payouts') }}" class="{{ request()->routeIs('admin.payouts*') ? 'active' : '' }}">
                        <i class="fas fa-rupee-sign"></i> Seller Payouts
                    </a>
                
                {{-- SELLER MENU --}}
                @elseif(Auth::user()->isSeller())
                    <a href="{{ route('seller.dashboard') }}" class="{{ request()->routeIs('seller.dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="{{ route('seller.orders') }}" class="{{ request()->routeIs('seller.orders*') ? 'active' : '' }}">
                        <i class="fas fa-box"></i> Orders
                        @php 
                            $sellerOrderCount = \App\Models\Order::whereHas('items', function($q) {
                                $q->whereHas('product', function($subQ) {
                                    $subQ->where('seller_id', Auth::id())->withTrashed();
                                });
                            })->count();
                        @endphp
                        @if($sellerOrderCount > 0)
                            <span class="bg-red-500 text-white text-xs rounded-full px-2 py-0.5 ml-auto">{{ $sellerOrderCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.index') ? 'active' : '' }}">
                        <i class="fas fa-boxes"></i> Products
                    </a>
                    <a href="{{ route('products.create') }}">
                        <i class="fas fa-plus-circle"></i> Add Product
                    </a>
                
                {{-- NORMAL USER MENU --}}
                @else
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') && !request()->routeIs('products.create') && !request()->routeIs('products.edit') ? 'active' : '' }}">
                        <i class="fas fa-store"></i> Browse Products
                    </a>
                    <a href="{{ route('cart.index') }}" class="{{ request()->routeIs('cart.*') ? 'active' : '' }}">
                        <i class="fas fa-shopping-cart"></i> My Cart
                        @php $cartCount = Auth::user()->cart?->items->sum('quantity') ?? 0; @endphp
                        @if($cartCount > 0)
                            <span class="bg-red-500 text-white text-xs rounded-full px-2 py-0.5 ml-auto">{{ $cartCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('budget.index') }}" class="{{ request()->routeIs('budget.*') ? 'active' : '' }}">
                        <i class="fas fa-wallet"></i> Budget Tracker
                    </a>
                    <a href="{{ route('orders.index') }}" class="{{ request()->routeIs('orders.index') ? 'active' : '' }}">
                        <i class="fas fa-box"></i> My Orders
                    </a>
                    <a href="{{ route('addresses.index') }}" class="{{ request()->routeIs('addresses.*') ? 'active' : '' }}">
                        <i class="fas fa-map-marker-alt"></i> Addresses
                    </a>
                @endif
                
                {{-- Profile Link (Common for all) --}}
                <a href="{{ route('profile.edit') }}">
                    <i class="fas fa-user"></i> Profile
                </a>
            </nav>
            
            {{-- BUDGET SIDEBAR - Only for Normal Users (NOT for Admin or Seller) --}}
            @if(!Auth::user()->isSeller() && !Auth::user()->isAdmin())
                @php 
                    $budget = Auth::user()->budget;
                    $monthlySpent = Auth::user()->orders()
                        ->where('order_status', 'delivered')
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->sum('total_amount');
                    $cartTotal = Auth::user()->cart?->items->sum(fn($i) => $i->product->price * $i->quantity) ?? 0;
                    $totalCommitted = $monthlySpent + $cartTotal;
                    $remainingBudget = $budget ? max(0, $budget->amount - $totalCommitted) : 0;
                    $percentageUsed = $budget && $budget->amount > 0 ? min(100, round(($totalCommitted / $budget->amount) * 100)) : 0;
                @endphp
                @if($budget)
                <div class="budget-sidebar">
                    <div style="font-size: 0.7rem; opacity: 0.8;">Monthly Budget</div>
                    <div style="font-size: 1.2rem; font-weight: bold;">₹{{ number_format($budget->amount) }}</div>
                    <div style="font-size: 0.6rem; margin-top: 3px;">
                        <span>Spent: ₹{{ number_format($monthlySpent) }}</span>
                        @if($cartTotal > 0)
                            <span class="ml-2">Cart: ₹{{ number_format($cartTotal) }}</span>
                        @endif
                    </div>
                    <div style="font-size: 0.7rem; margin-top: 3px;">
                        Remaining: <strong>₹{{ number_format($remainingBudget) }}</strong>
                    </div>
                    <div style="height: 4px; background: rgba(255,255,255,0.3); border-radius: 2px; margin-top: 8px;">
                        <div style="width: {{ $percentageUsed }}%; height: 4px; background: {{ $percentageUsed >= 100 ? '#ef4444' : ($percentageUsed >= 70 ? '#f59e0b' : '#10b981') }}; border-radius: 2px;"></div>
                    </div>
                </div>
                @endif
            @endif
            
            {{-- USER INFO SIDEBAR - Common for all --}}
            <div class="user-info-sidebar">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1rem;">
                    <div style="width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, #667eea, #764ba2); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.2rem;">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                    <div>
                        <div style="font-weight: 600;">{{ Auth::user()->name }}</div>
                        <div style="font-size: 0.7rem; color: #667eea;">{{ ucfirst(Auth::user()->role) }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" style="width: 100%; padding: 0.6rem; background: #fee2e2; color: #dc2626; border: none; border-radius: 10px; cursor: pointer;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </form>
            </div>
        </aside>
        
        <main class="main-content">
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-4">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-4">
                    <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
                </div>
            @endif
            @yield('content')
        </main>
    </div>
    
    <script>
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
                if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    </script>
    @stack('scripts')
</body>
</html>