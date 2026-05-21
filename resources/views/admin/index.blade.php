@extends('layouts.master')

@section('title', 'Admin Dashboard')

@section('content')
<div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl p-6 text-white mb-6">
    <h1 class="text-2xl font-bold">Admin Dashboard 👑</h1>
    <p class="opacity-90 mt-1">Welcome to the admin control panel. You have full control over the system.</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Users</p>
                <p class="text-2xl font-bold">{{ $totalUsers }}</p>
            </div>
            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-indigo-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-2 text-xs text-gray-400 flex gap-3">
            <span>Sellers: {{ $totalSellers }}</span>
            <span>Customers: {{ $totalCustomers }}</span>
        </div>
    </div>
    
    <div class="bg-white rounded-xl p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Products</p>
                <p class="text-2xl font-bold">{{ $totalProducts }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-box text-green-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-2 text-xs text-gray-400">
            <span>Out of Stock: {{ $outOfStock }}</span>
        </div>
    </div>
    
    <div class="bg-white rounded-xl p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Orders</p>
                <p class="text-2xl font-bold">{{ $totalOrders }}</p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-shopping-cart text-blue-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-2 text-xs text-gray-400">
            <span>Pending: {{ $pendingOrders }}</span> | <span>Delivered: {{ $deliveredOrders }}</span>
        </div>
    </div>
    
    <div class="bg-white rounded-xl p-5 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Revenue</p>
                <p class="text-2xl font-bold text-green-600">₹{{ number_format($totalRevenue, 2) }}</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fas fa-rupee-sign text-yellow-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="flex flex-wrap gap-3 mb-6">
    <a href="{{ route('admin.users') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">📋 Manage Users</a>
    <a href="{{ route('admin.sellers') }}" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">🏪 Manage Sellers</a>
    <a href="{{ route('admin.products') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">📦 Manage Products</a>
    <a href="{{ route('admin.orders') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">📋 All Orders</a>
    <a href="{{ route('admin.payouts') }}" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700">💰 Seller Payouts</a>
</div>

<!-- Recent Orders & Users -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm">
        <div class="p-4 border-b font-bold">🕐 Recent Orders</div>
        <div class="divide-y">
            @forelse($recentOrders as $order)
            <div class="p-4 flex justify-between">
                <div>
                    <p class="font-medium">#{{ $order->id }} - {{ $order->user->name }}</p>
                    <p class="text-xs text-gray-500">{{ $order->created_at->diffForHumans() }}</p>
                </div>
                <div class="text-right">
                    <p class="font-bold">₹{{ number_format($order->total_amount, 2) }}</p>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800">{{ $order->order_status }}</span>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-gray-500">No orders yet</div>
            @endforelse
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm">
        <div class="p-4 border-b font-bold">👤 Recent Users</div>
        <div class="divide-y">
            @forelse($recentUsers as $user)
            <div class="p-4 flex justify-between">
                <div>
                    <p class="font-medium">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500">{{ $user->email }}</p>
                </div>
                <div class="text-right">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $user->role == 'seller' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                        {{ ucfirst($user->role) }}
                    </span>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-gray-500">No users yet</div>
            @endforelse
        </div>
    </div>
</div>
@endsection