@extends('layouts.master')

@section('title', 'All Products')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold"><i class="fas fa-box"></i> All Products</h1>
    <div class="flex gap-2">
        <a href="{{ route('admin.products') }}" class="px-3 py-1 rounded-full text-sm bg-gray-200">All</a>
        <a href="{{ route('admin.products', ['status' => 'out_of_stock']) }}" class="px-3 py-1 rounded-full text-sm bg-red-100">Out of Stock</a>
        <a href="{{ route('admin.products', ['status' => 'deleted']) }}" class="px-3 py-1 rounded-full text-sm bg-gray-300">Deleted</a>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr><th class="p-3 text-left">Product</th><th class="p-3 text-left">Seller</th><th class="p-3 text-right">Price</th><th class="p-3 text-center">Stock</th><th class="p-3 text-center">Status</th><th class="p-3 text-center">Actions</th></tr>
        </thead>
        <tbody>
            @foreach($products as $product)
            <tr class="border-b hover:bg-gray-50">
                <td class="p-3 font-medium">{{ $product->name }}</td>
                <td class="p-3">{{ $product->seller->name ?? 'Unknown' }}</td>
                <td class="p-3 text-right">₹{{ number_format($product->price, 2) }}</td>
                <td class="p-3 text-center">{{ $product->quantity }}</td>
                <td class="p-3 text-center">
                    @if($product->trashed())
                        <span class="text-red-600">Deleted</span>
                    @elseif($product->quantity == 0)
                        <span class="text-orange-600">Out of Stock</span>
                    @else
                        <span class="text-green-600">Active</span>
                    @endif
                </td>
                <td class="p-3 text-center">
                    @if($product->trashed())
                        <form action="{{ route('admin.products.restore', $product->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-green-600 hover:underline">Restore</button>
                        </form>
                        <form action="{{ route('admin.products.force-delete', $product->id) }}" method="POST" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline ml-2" onclick="return confirm('Permanently delete?')">Force Delete</button>
                        </form>
                    @else
                        <a href="{{ route('products.show', $product) }}" class="text-indigo-600 hover:underline">View</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="p-4">{{ $products->links() }}</div>
</div>
@endsection