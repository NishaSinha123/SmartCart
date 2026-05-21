<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\BudgetExceededMail;

class BudgetController extends Controller
{
    /**
     * Show budget page with real-time status
     * Includes delivered orders + active orders + cart total
     */
    public function index()
    {
        $user = Auth::user();
        $budget = Budget::where('user_id', $user->id)->first();
        
        // Get complete budget breakdown
        $commitment = Order::getTotalBudgetCommitment($user);
        $deliveredSpent = $commitment['delivered'];
        $activeOrders = $commitment['active_orders'];
        $cartTotal = $commitment['cart_total'];
        $totalCommitted = $commitment['total_committed'];
        
        $budgetAmount = $budget?->amount ?? 0;
        $remainingBudget = $budget ? max(0, $budgetAmount - $totalCommitted) : 0;
        $percentageUsed = $budget && $budgetAmount > 0 
            ? min(100, round(($totalCommitted / $budgetAmount) * 100)) 
            : 0;
        
        // Determine alert level for UI
        $alertLevel = $this->getAlertLevel($budget, $totalCommitted, $budgetAmount);
        
        return view('budget.index', compact(
            'budget', 'deliveredSpent', 'activeOrders', 'cartTotal', 
            'totalCommitted', 'remainingBudget', 'percentageUsed', 'alertLevel'
        ));
    }

    /**
     * Save or update user's monthly budget
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0|max:9999999.99',
        ], [
            'amount.required' => 'Please enter a budget amount.',
            'amount.numeric' => 'Budget must be a number.',
            'amount.min' => 'Budget cannot be negative.',
            'amount.max' => 'Budget amount is too high.',
        ]);

        $user = Auth::user();
        
        // Update or create budget
        $budget = Budget::updateOrCreate(
            ['user_id' => $user->id],
            ['amount' => $request->amount]
        );
        
        // Check current total commitment against new budget
        $commitment = Order::getTotalBudgetCommitment($user);
        $totalCommitted = $commitment['total_committed'];
        
        if ($totalCommitted > $request->amount) {
            session()->flash('warning', "⚠️ Your total committed amount (₹" . number_format($totalCommitted) . 
                            ") exceeds your new budget! This includes delivered orders, pending orders, and cart items.");
            $this->sendBudgetAlert($user, $totalCommitted, $request->amount, 'exceeded');
        } elseif ($totalCommitted > ($request->amount * 0.7)) {
            session()->flash('info', "📊 Note: You've already committed ₹" . number_format($totalCommitted) . 
                            " which is close to your budget limit.");
        }
        
        return redirect()->route('budget.index')->with('success', 'Budget updated successfully!');
    }

    /**
     * AJAX endpoint for real-time budget status updates
     * Used by frontend to refresh data every 30 seconds
     */
    public function getStatus()
    {
        $user = Auth::user();
        $budget = Budget::where('user_id', $user->id)->first();
        $commitment = Order::getTotalBudgetCommitment($user);
        
        $budgetAmount = $budget?->amount ?? 0;
        $totalCommitted = $commitment['total_committed'];
        $remaining = $budget ? max(0, $budgetAmount - $totalCommitted) : 0;
        $percentage = $budget && $budgetAmount > 0 
            ? min(100, round(($totalCommitted / $budgetAmount) * 100)) 
            : 0;
        
        return response()->json([
            'success' => true,
            'has_budget' => !is_null($budget),
            'budget_amount' => number_format($budgetAmount, 2),
            'delivered_spent' => number_format($commitment['delivered'], 2),
            'active_orders' => number_format($commitment['active_orders'], 2),
            'cart_total' => number_format($commitment['cart_total'], 2),
            'total_committed' => number_format($totalCommitted, 2),
            'remaining' => number_format($remaining, 2),
            'percentage' => $percentage,
            'alert_level' => $this->getAlertLevel($budget, $totalCommitted, $budgetAmount),
        ]);
    }

    /**
     * Check if adding an item would exceed budget
     * Called by CartController before adding items
     */
    public function checkCartAgainstBudget($newCartTotal)
    {
        $user = Auth::user();
        $budget = Budget::where('user_id', $user->id)->first();
        
        if (!$budget) {
            return [
                'allowed' => true,
                'warning' => "You haven't set a monthly budget. Consider setting one to track your spending!"
            ];
        }
        
        // Include existing active orders in calculation
        $activeOrders = Order::getMonthlyActiveOrdersAmount($user->id);
        $totalCommitted = $activeOrders + $newCartTotal;
        
        if ($totalCommitted > $budget->amount) {
            return [
                'allowed' => false,
                'error' => "❌ Cannot add item! This would make your total committed amount ₹" . 
                        number_format($totalCommitted) . " which exceeds your budget (₹" . 
                        number_format($budget->amount) . "). You already have ₹" . 
                        number_format($activeOrders) . " in pending orders."
            ];
        }
        
        return [
            'allowed' => true,
            'remaining' => $budget->amount - $totalCommitted
        ];
    }

    /**
     * Check if user can proceed to checkout
     * Verifies budget considering delivered + active orders + cart
     */
    public function canCheckout()
    {
        $user = Auth::user();
        $budget = Budget::where('user_id', $user->id)->first();
        
        if (!$budget) {
            return [
                'allowed' => true,
                'warning' => "You haven't set a monthly budget. Consider setting one to track your spending!"
            ];
        }
        
        $commitment = Order::getTotalBudgetCommitment($user);
        $totalCommitted = $commitment['total_committed'];
        $remainingBudget = max(0, $budget->amount - $totalCommitted);
        
        if ($commitment['cart_total'] > $remainingBudget) {
            return [
                'allowed' => false,
                'error' => "❌ Cannot checkout! Your cart total (₹" . number_format($commitment['cart_total']) . 
                        ") would exceed your remaining budget (₹" . number_format($remainingBudget) . ").\n" .
                        "Already spent: ₹" . number_format($commitment['delivered']) . " (delivered)\n" .
                        "Pending orders: ₹" . number_format($commitment['active_orders'])
            ];
        }
        
        return [
            'allowed' => true,
            'info' => "✅ Within budget! Remaining: ₹" . number_format($remainingBudget - $commitment['cart_total'])
        ];
    }

    /**
     * Display budget insights and spending analytics
     * Charts and historical data shown here
     */
    public function insights()
    {
        $user = Auth::user();
        $budget = Budget::where('user_id', $user->id)->first();
        
        if (!$budget) {
            return redirect()->route('budget.index')
                ->with('info', 'Please set a budget first to see insights.');
        }
        
        // Completed orders for analytics
        $completedOrders = $user->orders()
            ->where('order_status', Order::STATUS_DELIVERED)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        
        $totalSpent = $completedOrders->sum('total_amount');
        $averageSpent = $completedOrders->count() > 0 ? $totalSpent / $completedOrders->count() : 0;
        
        // Monthly trend for chart
        $monthlySpending = $user->orders()
            ->where('order_status', Order::STATUS_DELIVERED)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(total_amount) as total')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->take(6)
            ->get();
        
        // Current month breakdown
        $commitment = Order::getTotalBudgetCommitment($user);
        
        return view('budget.insights', compact(
            'budget', 'completedOrders', 'totalSpent', 'averageSpent', 
            'monthlySpending', 'commitment'
        ));
    }

    // ==================== PRIVATE HELPER METHODS ====================

    /**
     * Calculate current cart total for user
     */
    private function getCartTotal($user)
    {
        $cart = Cart::where('user_id', $user->id)->first();
        
        if (!$cart) {
            return 0;
        }
        
        return CartItem::where('cart_id', $cart->id)
            ->with('product')
            ->get()
            ->sum(fn($item) => $item->product->price * $item->quantity);
    }

    /**
     * Determine alert level based on budget usage
     * Used for UI color coding and warning messages
     */
    private function getAlertLevel($budget, $totalCommitted, $budgetAmount)
    {
        if (!$budget || $budgetAmount == 0) {
            return [
                'level' => 'no_budget',
                'message' => 'No budget set. Click "Set Budget" to start tracking!',
                'color' => 'gray'
            ];
        }
        
        $percentage = ($totalCommitted / $budgetAmount) * 100;
        
        if ($totalCommitted >= $budgetAmount) {
            return [
                'level' => 'exceeded',
                'message' => '🔴 BUDGET EXCEEDED! You have exceeded by ₹' . 
                            number_format($totalCommitted - $budgetAmount),
                'color' => 'red'
            ];
        } elseif ($percentage >= 90) {
            return [
                'level' => 'critical',
                'message' => '🔴 CRITICAL! You have used ' . number_format($percentage, 1) . 
                            '% of your budget. Only ₹' . number_format($budgetAmount - $totalCommitted) . ' left!',
                'color' => 'red'
            ];
        } elseif ($percentage >= 70) {
            return [
                'level' => 'warning',
                'message' => '🟡 WARNING! You have used ' . number_format($percentage, 1) . 
                            '% of your budget.',
                'color' => 'yellow'
            ];
        } elseif ($percentage >= 50) {
            return [
                'level' => 'moderate',
                'message' => '🟠 You have used ' . number_format($percentage, 1) . '% of your budget.',
                'color' => 'orange'
            ];
        } elseif ($percentage > 0) {
            return [
                'level' => 'safe',
                'message' => '🟢 Good! You have used ' . number_format($percentage, 1) . '% of your budget.',
                'color' => 'green'
            ];
        }
        
        return [
            'level' => 'empty',
            'message' => '🟢 Your cart is empty! Start shopping!',
            'color' => 'green'
        ];
    }

    /**
     * Send email notification when budget is exceeded
     * Only sends in production environment to avoid spam during development
     */
    private function sendBudgetAlert($user, $totalCommitted, $budgetAmount, $type)
    {
        \Log::info("Budget {$type} for user {$user->id}: Committed ₹{$totalCommitted} / Budget ₹{$budgetAmount}");
        
        if ($user->email && app()->environment('production')) {
            try {
                Mail::to($user->email)->send(new BudgetExceededMail($user, $totalCommitted, $budgetAmount));
                \Log::info("Budget alert email sent to: {$user->email}");
            } catch (\Exception $e) {
                \Log::error("Failed to send budget alert email: " . $e->getMessage());
            }
        } elseif ($user->email && !app()->environment('production')) {
            \Log::info("Development mode: Would send budget alert email to {$user->email}");
        }
    }
}