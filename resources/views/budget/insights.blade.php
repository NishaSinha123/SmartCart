@extends('layouts.master')

@section('title', 'Budget Insights')

@section('content')
@php
    $user = Auth::user();
    $budget = $user->budget;
    $budgetAmount = $budget->amount ?? 0;
    
    // Data from controller
    $completedOrders = $completedOrders ?? collect();
    $totalSpent = $totalSpent ?? 0;
    $averageSpent = $averageSpent ?? 0;
    $monthlySpending = $monthlySpending ?? collect();
    $commitment = $commitment ?? ['delivered' => 0, 'active_orders' => 0, 'cart_total' => 0];
    
    $remainingBudget = max(0, $budgetAmount - $commitment['total_committed']);
    $percentageUsed = $budgetAmount > 0 ? min(100, round(($commitment['total_committed'] / $budgetAmount) * 100)) : 0;
@endphp

<div class="bg-white rounded-xl p-6 shadow-sm mb-6">
    <h2 class="text-xl font-bold mb-4"><i class="fas fa-chart-pie"></i> Your Spending Insights</h2>
    
    <!-- Stats cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="text-center p-4 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl">
            <div class="text-2xl font-bold text-indigo-600">₹{{ number_format($budgetAmount, 2) }}</div>
            <div class="text-sm text-gray-500">Monthly Budget</div>
        </div>
        <div class="text-center p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl">
            <div class="text-2xl font-bold text-green-600">₹{{ number_format($totalSpent, 2) }}</div>
            <div class="text-sm text-gray-500">Total Spent</div>
            <div class="text-xs text-gray-400">(All time delivered)</div>
        </div>
        <div class="text-center p-4 bg-gradient-to-r from-blue-50 to-cyan-50 rounded-xl">
            <div class="text-2xl font-bold text-blue-600">₹{{ number_format($averageSpent, 2) }}</div>
            <div class="text-sm text-gray-500">Average per Order</div>
        </div>
        <div class="text-center p-4 bg-gradient-to-r from-orange-50 to-red-50 rounded-xl">
            <div class="text-2xl font-bold text-orange-600">{{ $completedOrders->count() }}</div>
            <div class="text-sm text-gray-500">Completed Orders</div>
        </div>
        <div class="text-center p-4 bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl">
            <div class="text-2xl font-bold text-purple-600">₹{{ number_format($commitment['delivered'], 2) }}</div>
            <div class="text-sm text-gray-500">This Month</div>
            <div class="text-xs text-gray-400">Delivered only</div>
        </div>
    </div>
    
    <!-- Spending trend chart -->
    <h3 class="font-bold mb-3">Monthly Spending Trend</h3>
    <canvas id="spendingChart" class="w-full h-64"></canvas>
</div>

<!-- Recent orders table -->
<div class="bg-white rounded-xl p-6 shadow-sm">
    <h3 class="font-bold mb-3"><i class="fas fa-clock"></i> Recent Completed Orders</h3>
    @if($completedOrders->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="p-3 text-left">Order ID</th>
                        <th class="p-3 text-left">Date</th>
                        <th class="p-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($completedOrders as $order)
                    <tr class="border-b">
                        <td class="p-3">#{{ $order->id }}</td>
                        <td class="p-3">{{ $order->created_at->format('M d, Y') }}</td>
                        <td class="p-3 text-right font-semibold">₹{{ number_format($order->total_amount, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-center text-gray-500 py-8">No completed orders yet. Start shopping to see insights!</p>
    @endif
    
    <!-- Current month budget summary -->
    <div class="mt-6 pt-4 border-t">
        <div class="bg-gray-50 p-4 rounded-lg">
            <p class="font-semibold mb-2">Budget Summary for {{ now()->format('F Y') }}</p>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>Monthly Budget:</div>
                <div class="font-semibold">₹{{ number_format($budgetAmount, 2) }}</div>
                <div>Already Spent (Delivered):</div>
                <div class="font-semibold text-green-600">₹{{ number_format($commitment['delivered'], 2) }}</div>
                <div>Pending Orders:</div>
                <div class="font-semibold text-yellow-600">₹{{ number_format($commitment['active_orders'], 2) }}</div>
                <div>Pending in Cart:</div>
                <div class="font-semibold text-blue-600">₹{{ number_format($commitment['cart_total'], 2) }}</div>
                <div>Total Committed:</div>
                <div class="font-semibold text-purple-600">₹{{ number_format($commitment['total_committed'], 2) }}</div>
                <div>Remaining:</div>
                <div class="font-semibold text-green-600">₹{{ number_format($remainingBudget, 2) }}</div>
                <div>Usage:</div>
                <div class="font-semibold {{ $percentageUsed >= 100 ? 'text-red-600' : 'text-gray-600' }}">
                    {{ $percentageUsed }}%
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4 text-center">
        <a href="{{ route('budget.index') }}" class="text-indigo-600 hover:underline">← Back to Budget Tracker</a>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('spendingChart').getContext('2d');
    const monthlyData = @json($monthlySpending);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyData.map(item => item.month).reverse(),
            datasets: [{
                label: 'Monthly Spending (₹)',
                data: monthlyData.map(item => item.total).reverse(),
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true, title: { display: true, text: 'Amount (₹)' } } }
        }
    });
</script>
@endpush
@endsection