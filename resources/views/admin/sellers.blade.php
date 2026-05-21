@extends('layouts.master')

@section('title', 'Manage Sellers')

@section('content')
<h1 class="text-2xl font-bold mb-6"><i class="fas fa-store"></i> Manage Sellers</h1>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr><th class="p-3 text-left">Seller</th><th class="p-3 text-left">Email</th><th class="p-3 text-center">Products</th><th class="p-3 text-center">Joined</th><th class="p-3 text-center">Actions</th></tr>
        </thead>
        <tbody>
            @foreach($sellers as $seller)
            <tr class="border-b hover:bg-gray-50">
                <td class="p-3 font-medium">{{ $seller->name }}</td>
                <td class="p-3">{{ $seller->email }}</td>
                <td class="p-3 text-center">{{ $seller->products_count }}</td>
                <td class="p-3 text-center">{{ $seller->created_at->format('d M Y') }}</td>
                <td class="p-3 text-center">
                    <a href="{{ route('admin.users.show', $seller) }}" class="text-indigo-600 hover:underline">View Details</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="p-4">{{ $sellers->links() }}</div>
</div>
@endsection