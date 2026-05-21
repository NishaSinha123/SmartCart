<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * Display cart page with all items
     */
    public function index()
    {
        $user = Auth::user();
        $cart = $this->getUserCart($user);
        $cartItems = $cart->items()->with('product')->get();
        $total = $this->calculateTotal($cartItems);
        $budget = $user->budget;
        
        return view('cart.index', compact('cartItems', 'total', 'budget'));
    }

    /**
     * Add product to cart with budget validation
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::findOrFail($request->product_id);
        
        // Stock validation
        if ($product->quantity < $request->quantity) {
            return back()->with('error', "Only {$product->quantity} items available for '{$product->name}'");
        }

        $user = Auth::user();
        $cart = $this->getUserCart($user);

        // Calculate new cart total after adding this item
        $currentCartItems = $cart->items()->with('product')->get();
        $currentCartTotal = $this->calculateTotal($currentCartItems);
        $newCartTotal = $currentCartTotal + ($product->price * $request->quantity);

        // Check budget including existing pending orders
        $budgetCheck = $this->checkBudgetBeforeAdding($user, $newCartTotal);
        
        if (!$budgetCheck['allowed']) {
            return back()->with('error', $budgetCheck['error']);
        }
        
        // Add or update cart item
        $cartItem = CartItem::where('cart_id', $cart->id)
                            ->where('product_id', $product->id)
                            ->first();
        
        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $request->quantity;
            if ($product->quantity < $newQuantity) {
                return back()->with('error', "Cannot add {$request->quantity} more. Only {$product->quantity} total available.");
            }
            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $request->quantity
            ]);
        }
        
        return redirect()->route('cart.index')->with('success', "{$product->name} added to cart!");
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, $cartItemId)
    {
        $request->validate(['quantity' => 'required|integer|min:1']);
        
        $cartItem = CartItem::findOrFail($cartItemId);
        $user = Auth::user();
        $cart = $this->getUserCart($user);
        
        if ($cartItem->cart_id !== $cart->id) {
            abort(403, 'Unauthorized action');
        }
        
        $product = $cartItem->product;
        
        if ($product->quantity < $request->quantity) {
            return response()->json([
                'success' => false,
                'message' => "Only {$product->quantity} items available"
            ], 400);
        }
        
        $cartItem->update(['quantity' => $request->quantity]);
        $cartItems = $cart->items()->with('product')->get();
        $newTotal = $this->calculateTotal($cartItems);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'item_total' => $product->price * $request->quantity,
                'cart_total' => $newTotal,
                'message' => 'Quantity updated'
            ]);
        }
        
        return redirect()->route('cart.index')->with('success', 'Cart updated!');
    }

    /**
     * Remove item from cart
     */
    public function remove($cartItemId)
    {
        $cartItem = CartItem::findOrFail($cartItemId);
        $user = Auth::user();
        $cart = $this->getUserCart($user);
        
        if ($cartItem->cart_id !== $cart->id) {
            abort(403);
        }
        
        $productName = $cartItem->product->name;
        $cartItem->delete();
        
        return redirect()->route('cart.index')->with('success', "'{$productName}' removed from cart");
    }

    /**
     * Clear entire cart
     */
    public function clear()
    {
        $user = Auth::user();
        $cart = $this->getUserCart($user);
        $cart->items()->delete();
        
        return redirect()->route('cart.index')->with('success', 'Cart cleared!');
    }

    /**
     * Check if adding item would exceed budget
     * Includes delivered orders + pending orders + cart total
     */
    private function checkBudgetBeforeAdding($user, $newCartTotal)
    {
        $budget = $user->budget;
        
        if (!$budget) {
            return [
                'allowed' => true,
                'warning' => "You haven't set a monthly budget. Consider setting one to track your spending!"
            ];
        }
        
        // Get delivered orders for this month
        $deliveredSpent = \App\Models\Order::where('user_id', $user->id)
            ->where('order_status', \App\Models\Order::STATUS_DELIVERED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');
        
        // Get pending orders (ordered but not delivered)
        $pendingOrders = \App\Models\Order::where('user_id', $user->id)
            ->whereIn('order_status', ['pending', 'confirmed', 'processing', 'shipped', 'out_for_delivery'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');
        
        $totalCommitment = $deliveredSpent + $pendingOrders + $newCartTotal;
        
        if ($totalCommitment > $budget->amount) {
            return [
                'allowed' => false,
                'error' => "❌ Cannot add item! Total committed amount would be ₹" . 
                        number_format($totalCommitment) . " which exceeds your budget (₹" . 
                        number_format($budget->amount) . ").\n" .
                        "Already spent: ₹" . number_format($deliveredSpent) . " (delivered)\n" .
                        "Pending orders: ₹" . number_format($pendingOrders)
            ];
        }
        
        return [
            'allowed' => true,
            'remaining' => $budget->amount - $totalCommitment
        ];
    }

    /**
     * Get or create user's cart
     */
    private function getUserCart($user)
    {
        $cart = Cart::where('user_id', $user->id)->first();
        
        if (!$cart) {
            $cart = Cart::create(['user_id' => $user->id]);
        }
        
        return $cart;
    }
    
    /**
     * Calculate total price of all items in cart
     */
    private function calculateTotal($cartItems)
    {
        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item->product->price * $item->quantity;
        }
        return $total;
    }
}