@extends('layouts.master')

@section('title', 'Seller Payouts')

@section('content')
<h1 class="text-2xl font-bold mb-6"><i class="fas fa-rupee-sign"></i> Seller Payouts</h1>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr><th class="p-3 text-left">Seller</th><th class="p-3 text-left">Email</th><th class="p-3 text-right">Total Earnings</th><th class="p-3 text-right">Pending</th><th class="p-3 text-center">Action</th></tr>
        </thead>
        <tbody>
            @foreach($payouts as $payout)
            <tr class="border-b hover:bg-gray-50">
                <td class="p-3 font-medium">{{ $payout->seller->name }}</td>
                <td class="p-3">{{ $payout->seller->email }}</td>
                <td class="p-3 text-right text-green-600 font-semibold">₹{{ number_format($payout->earnings, 2) }}</td>
                <td class="p-3 text-right text-orange-600">₹{{ number_format($payout->pending, 2) }}</td>
                <td class="p-3 text-center"><a href="{{ route('admin.users.show', $payout->seller) }}" class="text-indigo-600 hover:underline">View Seller</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection