@extends('layouts.master')

@section('title', $user->name)

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.users') }}" class="text-indigo-600 hover:underline">← Back to Users</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-1">
        <div class="bg-white rounded-xl p-6 shadow-sm text-center">
            <div class="w-24 h-24 mx-auto bg-gradient-to-r from-indigo-600 to-purple-600 rounded-full flex items-center justify-center text-white text-3xl font-bold mb-4">
                {{ substr($user->name, 0, 1) }}
            </div>
            <h2 class="text-xl font-bold">{{ $user->name }}</h2>
            <p class="text-gray-500">{{ $user->email }}</p>
            <p class="mt-2"><span class="px-2 py-1 rounded-full text-xs {{ $user->role == 'admin' ? 'bg-red-100' : ($user->role == 'seller' ? 'bg-purple-100' : 'bg-blue-100') }}">{{ ucfirst($user->role) }}</span></p>
            <p class="mt-2">Status: {{ $user->is_active ? '✅ Active' : '❌ Blocked' }}</p>
            <p class="text-sm text-gray-500">Joined: {{ $user->created_at->format('d M Y') }}</p>
            @if(!$user->is_active && $user->admin_notes)
                <p class="mt-2 text-sm text-red-600">Block Reason: {{ $user->admin_notes }}</p>
            @endif
            <div class="mt-4 flex gap-2 justify-center">
                @if($user->is_active)
                    <form action="{{ route('admin.users.block', $user) }}" method="POST">
                        @csrf
                        <input type="hidden" name="reason" value="Blocked by admin">
                        <button type="submit" class="bg-orange-500 text-white px-4 py-2 rounded-lg">Block User</button>
                    </form>
                @else
                    <form action="{{ route('admin.users.unblock', $user) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-lg">Unblock User</button>
                    </form>
                @endif
                @if($user->role != 'admin')
                <form action="{{ route('admin.users.delete', $user) }}" method="POST" onsubmit="return confirm('Delete this user?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-lg">Delete</button>
                </form>
                @endif
            </div>
        </div>
        @if($user->isSeller())
        <div class="bg-white rounded-xl p-6 shadow-sm mt-4">
            <h3 class="font-bold mb-2">Seller Stats</h3>
            <p>Total Products: {{ $user->products->count() }}</p>
            <p>Total Earnings: ₹{{ number_format($totalEarnings, 2) }}</p>
        </div>
        @endif
    </div>
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl p-6 shadow-sm">
            <h3 class="font-bold mb-3">Recent Orders</h3>
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr><th class="p-2 text-left">Order ID</th><th class="p-2 text-left">Date</th><th class="p-2 text-right">Total</th><th class="p-2 text-left">Status</th></tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr class="border-b">
                        <td class="p-2">#{{ $order->id }}</td>
                        <td class="p-2">{{ $order->created_at->format('d M Y') }}</td>
                        <td class="p-2 text-right">₹{{ number_format($order->total_amount, 2) }}</td>
                        <td class="p-2">{{ $order->order_status }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="mt-3 text-right font-semibold">Total Spent: ₹{{ number_format($totalSpent, 2) }}</p>
        </div>
        @if($user->isSeller() && $user->products->count() > 0)
        <div class="bg-white rounded-xl p-6 shadow-sm mt-4">
            <h3 class="font-bold mb-3">Seller Products</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                @foreach($user->products as $product)
                <div class="border rounded-lg p-2 text-center">
                    <p class="font-medium">{{ $product->name }}</p>
                    <p class="text-sm">₹{{ number_format($product->price, 2) }}</p>
                    <p class="text-xs text-gray-500">Stock: {{ $product->quantity }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection