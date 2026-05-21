@extends('layouts.master')

@section('title', 'All Orders')

@section('content')
<h1 class="text-2xl font-bold mb-6"><i class="fas fa-shopping-cart"></i> All Orders</h1>

<div class="flex flex-wrap gap-2 mb-4">
    <a href="{{ route('admin.orders') }}" class="px-3 py-1 rounded-full text-sm bg-gray-200">All</a>
    <a href="{{ route('admin.orders', ['status' => 'pending']) }}" class="px-3 py-1 rounded-full text-sm bg-yellow-100">Pending</a>
    <a href="{{ route('admin.orders', ['status' => 'processing']) }}" class="px-3 py-1 rounded-full text-sm bg-blue-100">Processing</a>
    <a href="{{ route('admin.orders', ['status' => 'delivered']) }}" class="px-3 py-1 rounded-full text-sm bg-green-100">Delivered</a>
    <a href="{{ route('admin.orders', ['status' => 'cancelled']) }}" class="px-3 py-1 rounded-full text-sm bg-red-100">Cancelled</a>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr><th class="p-3 text-left">Order ID</th><th class="p-3 text-left">Customer</th><th class="p-3 text-left">Date</th><th class="p-3 text-right">Total</th><th class="p-3 text-left">Status</th><th class="p-3 text-center">Action</th></tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr class="border-b hover:bg-gray-50">
                <td class="p-3 font-medium">#{{ $order->id }}</td>
                <td class="p-3">{{ $order->user->name }}</td>
                <td class="p-3">{{ $order->created_at->format('d M Y') }}</td>
                <td class="p-3 text-right font-semibold">₹{{ number_format($order->total_amount, 2) }}</td>
                <td class="p-3"><span class="px-2 py-1 rounded-full text-xs bg-yellow-100">{{ $order->order_status }}</span></td>
                <td class="p-3 text-center"><a href="{{ route('admin.orders.show', $order) }}" class="text-indigo-600 hover:underline">View</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="p-4">{{ $orders->links() }}</div>
</div>
@endsection