@extends('layouts.master')

@section('title', 'Order #' . $order->id)

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.orders') }}" class="text-indigo-600 hover:underline">← Back to Orders</a>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 text-white">
        <h1 class="text-xl font-bold">Order #{{ $order->id }}</h1>
        <p class="text-sm">Placed on {{ $order->created_at->format('d M Y, h:i A') }}</p>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div><h3 class="font-bold mb-2">Customer</h3><p>{{ $order->user->name }}</p><p class="text-sm text-gray-500">{{ $order->user->email }}</p></div>
            <div><h3 class="font-bold mb-2">Shipping Address</h3><p>{{ $order->shipping_address_text }}</p><p>Phone: {{ $order->shipping_phone }}</p></div>
            <div><h3 class="font-bold mb-2">Payment</h3><p>Method: {{ $order->payment_method }}</p><p>Status: {{ $order->payment_status }}</p></div>
            <div><h3 class="font-bold mb-2">Order Status</h3>
                <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="flex gap-2">
                    @csrf
                    <select name="status" class="p-2 border rounded-lg">
                        <option value="pending" {{ $order->order_status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirmed" {{ $order->order_status == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="processing" {{ $order->order_status == 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="shipped" {{ $order->order_status == 'shipped' ? 'selected' : '' }}>Shipped</option>
                        <option value="delivered" {{ $order->order_status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="cancelled" {{ $order->order_status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg">Update</button>
                </form>
            </div>
        </div>
        <h3 class="font-bold mb-3">Order Items</h3>
        <table class="w-full">
            <thead class="bg-gray-50"><tr><th class="p-3 text-left">Product</th><th class="p-3 text-center">Quantity</th><th class="p-3 text-right">Price</th><th class="p-3 text-right">Subtotal</th></tr></thead>
            <tbody>
                @foreach($order->items as $item)
                <tr class="border-b"><td class="p-3">{{ $item->product->name }}</td><td class="p-3 text-center">{{ $item->quantity }}</td><td class="p-3 text-right">₹{{ number_format($item->price, 2) }}</td><td class="p-3 text-right font-semibold">₹{{ number_format($item->price * $item->quantity, 2) }}</td></tr>
                @endforeach
            </tbody>
            <tfoot><tr class="bg-gray-50"><td colspan="3" class="p-3 text-right font-bold">Total:</td><td class="p-3 text-right font-bold text-indigo-600">₹{{ number_format($order->total_amount, 2) }}</td></tr></tfoot>
        </table>
    </div>
</div>
@endsection