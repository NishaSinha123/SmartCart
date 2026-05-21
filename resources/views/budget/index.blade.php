@extends('layouts.master')

@section('title', 'Budget Tracker')

@section('content')
@php
    $user = Auth::user();
    $budget = $user->budget;
    $budgetAmount = $budget?->amount ?? 0;
    
    // Get accurate breakdown from controller
    // These variables come from controller now
@endphp

<div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl p-6 text-white mb-6">
    <h1 class="text-2xl font-bold"><i class="fas fa-chart-line"></i> Your Budget Overview</h1>
    @if($budget)
        <p class="text-4xl font-bold mt-2">₹{{ number_format($budget->amount, 2) }}</p>
        <p class="opacity-90">Monthly spending limit</p>
    @else
        <p class="text-3xl font-bold mt-2">Not Set</p>
        <p class="opacity-90">Set your monthly budget to start tracking</p>
    @endif
</div>

<div class="bg-white rounded-xl p-6 shadow-sm mb-6">
    <h3 class="text-lg font-bold mb-4"><i class="fas fa-chart-simple"></i> Real-time Budget Status</h3>
    
    <!-- Four-column breakdown for better visibility -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-green-50 rounded-xl p-4 text-center">
            <p class="text-gray-500 text-sm">✅ Delivered</p>
            <p class="text-2xl font-bold text-green-600">₹{{ number_format($deliveredSpent, 2) }}</p>
            <p class="text-xs text-gray-400">Already spent</p>
        </div>
        <div class="bg-yellow-50 rounded-xl p-4 text-center">
            <p class="text-gray-500 text-sm">⏳ Pending Orders</p>
            <p class="text-2xl font-bold text-yellow-600">₹{{ number_format($activeOrders, 2) }}</p>
            <p class="text-xs text-gray-400">Being processed</p>
        </div>
        <div class="bg-blue-50 rounded-xl p-4 text-center">
            <p class="text-gray-500 text-sm">🛒 In Cart</p>
            <p class="text-2xl font-bold text-blue-600" id="cartTotal">₹{{ number_format($cartTotal, 2) }}</p>
            <p class="text-xs text-gray-400">Not yet ordered</p>
        </div>
        <div class="bg-purple-50 rounded-xl p-4 text-center">
            <p class="text-gray-500 text-sm">💰 Total Committed</p>
            <p class="text-2xl font-bold text-purple-600">₹{{ number_format($totalCommitted, 2) }}</p>
            <p class="text-xs text-gray-400">Delivered + Pending + Cart</p>
        </div>
    </div>
    
    <!-- Progress bar -->
    <div class="mb-4">
        <div class="flex justify-between text-sm mb-1">
            <span>Budget Usage</span>
            <span class="{{ $percentageUsed >= 100 ? 'text-red-600' : ($percentageUsed >= 70 ? 'text-orange-600' : 'text-green-600') }}">
                {{ $percentageUsed }}%
            </span>
        </div>
        <div class="bg-gray-200 rounded-full h-4 overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500" 
                 style="width: {{ $percentageUsed }}%; background: linear-gradient(90deg, #10b981, #667eea, #ef4444);"></div>
        </div>
    </div>
    
    <!-- Detailed breakdown -->
    <div class="text-sm text-gray-600 mb-4 space-y-1">
        <p><strong>Total Committed:</strong> ₹{{ number_format($totalCommitted) }} / ₹{{ number_format($budgetAmount) }}</p>
        @if($deliveredSpent > 0)
            <p class="text-green-600">✅ Already spent: ₹{{ number_format($deliveredSpent) }} (delivered orders)</p>
        @endif
        @if($activeOrders > 0)
            <p class="text-yellow-600">⏳ Pending orders: ₹{{ number_format($activeOrders) }} (being processed)</p>
        @endif
        @if($cartTotal > 0)
            <p class="text-blue-600">🛒 In cart: ₹{{ number_format($cartTotal) }} (not yet ordered)</p>
        @endif
    </div>
    
    <!-- Alert messages based on budget status -->
    @if($budget && $totalCommitted > $budget->amount)
        <div class="p-4 rounded-lg bg-red-100 text-red-700 mb-4">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Budget Exceeded!</strong> You have exceeded your budget by ₹{{ number_format($totalCommitted - $budget->amount) }}
        </div>
    @elseif($budget && $percentageUsed >= 70)
        <div class="p-4 rounded-lg bg-yellow-100 text-yellow-700 mb-4">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Budget Warning!</strong> You have used {{ $percentageUsed }}% of your budget.
        </div>
    @elseif(!$budget)
        <div class="p-4 rounded-lg bg-blue-100 text-blue-700 mb-4">
            <i class="fas fa-info-circle"></i>
            No budget set. Click below to set your monthly budget.
        </div>
    @endif
    
    <!-- Action buttons -->
    <div class="flex gap-3">
        <a href="{{ route('cart.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
            <i class="fas fa-shopping-cart"></i> View Cart
        </a>
        <a href="{{ route('budget.insights') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
            <i class="fas fa-chart-line"></i> View Insights
        </a>
    </div>
</div>

<!-- Set/Update Budget Form -->
<div class="bg-white rounded-xl p-6 shadow-sm">
    <h3 class="text-lg font-bold mb-4">
        <i class="fas fa-pen"></i> {{ $budget ? 'Update Your Budget' : 'Set Your Monthly Budget' }}
    </h3>
    
    <form method="POST" action="{{ route('budget.store') }}" id="budgetForm">
        @csrf
        <div class="mb-4">
            <label class="block font-medium mb-2">Monthly Budget Amount (₹)</label>
            <input type="number" name="amount" value="{{ $budget ? $budget->amount : '' }}" 
                   class="w-full p-3 border rounded-lg text-lg" placeholder="e.g., 5000" step="0.01" min="0" required>
            <p class="text-gray-500 text-sm mt-2">Set a realistic budget to help track your spending habits.</p>
        </div>
        <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-3 rounded-lg font-semibold hover:shadow-lg transition">
            <i class="fas fa-save"></i> {{ $budget ? 'Update Budget' : 'Set Budget' }}
        </button>
    </form>
</div>

@push('scripts')
<script>
    // Refresh budget status every 30 seconds for real-time updates
    function updateBudgetStatus() {
        fetch('{{ route("budget.status") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('cartTotal').innerHTML = '₹' + data.cart_total;
                    // Optionally update other elements
                    console.log('Budget status updated:', data.percentage + '% used');
                }
            })
            .catch(error => console.error('Budget status update failed:', error));
    }
    // Update every 30 seconds
    setInterval(updateBudgetStatus, 30000);
</script>
@endpush
@endsection