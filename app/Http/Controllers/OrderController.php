<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ShippingAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPlacedMail;
use App\Mail\NewOrderForSellerMail;
use App\Mail\OrderStatusUpdatedMail;
use App\Mail\ReturnRequestedMail;
use App\Mail\ReturnApprovedMail;
use App\Mail\ReturnRejectedMail;
use App\Mail\RefundProcessedMail;
use App\Mail\RefundProcessedSellerMail;
use Razorpay\Api\Api;

class OrderController extends Controller
{
    /**
     * Display checkout page with budget validation
     */
    public function checkout()
    {
        $user = Auth::user();
        $cart = $user->cart;
        
        if (!$cart || $cart->items->count() == 0) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
        }
        
        $cartItems = $cart->items()->with('product')->get();
        $total = $cartItems->sum(fn($item) => $item->product->price * $item->quantity);
        
        // Stock validation
        foreach ($cartItems as $item) {
            if ($item->product->quantity < $item->quantity) {
                return redirect()->route('cart.index')->with('error', "{$item->product->name} has only {$item->product->quantity} units in stock.");
            }
        }
        
        // Budget validation - includes delivered + pending orders + cart total
        if ($user->budget) {
            $budgetCheck = $this->checkBudgetBeforeCheckout($user, $total);
            if (!$budgetCheck['allowed']) {
                return redirect()->route('cart.index')->with('error', $budgetCheck['error']);
            }
        }
        
        $addresses = $user->shippingAddresses;
        $defaultAddress = $addresses->where('is_default', true)->first();
        
        return view('orders.checkout', compact('cartItems', 'total', 'addresses', 'defaultAddress'));
    }

    /**
     * Place order (supports COD, QR, Razorpay)
     */
    public function placeOrder(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:shipping_addresses,id',
            'payment_method' => 'required|in:COD,QR,razorpay',
        ]);

        $user = Auth::user();
        $cart = $user->cart;
        
        if (!$cart || $cart->items->count() == 0) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
        }
        
        $address = ShippingAddress::findOrFail($request->address_id);
        if ($address->user_id != $user->id) abort(403);

        DB::beginTransaction();
        
        try {
            $cartItems = $cart->items()->with('product')->get();
            $total = 0;
            
            foreach ($cartItems as $item) {
                if ($item->product->quantity < $item->quantity) {
                    throw new \Exception("{$item->product->name} is out of stock!");
                }
                $total += $item->product->price * $item->quantity;
            }
            
            // Budget validation before placing order
            if ($user->budget) {
                $deliveredSpent = Order::where('user_id', $user->id)
                    ->where('order_status', Order::STATUS_DELIVERED)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('total_amount');
                
                $pendingOrders = Order::where('user_id', $user->id)
                    ->whereIn('order_status', ['pending', 'confirmed', 'processing', 'shipped', 'out_for_delivery'])
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->sum('total_amount');
                
                $newTotalCommitment = $deliveredSpent + $pendingOrders + $total;
                
                if ($newTotalCommitment > $user->budget->amount) {
                    throw new \Exception("This order would exceed your monthly budget! Your total committed amount would be ₹" . 
                        number_format($newTotalCommitment) . " (Budget: ₹" . number_format($user->budget->amount) . ").");
                }
            }
            
            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $total,
                'order_status' => Order::STATUS_PENDING,
                'payment_status' => 'pending',
                'payment_method' => $request->payment_method,
                'shipping_address_id' => $address->id,
                'shipping_phone' => $address->phone,
                'shipping_address_text' => $address->full_address,
                'transaction_id' => $request->payment_method == 'QR' ? 'QR_' . uniqid() : null,
            ]);
            
            // Seller earnings & transactions
            $sellerEarnings = 0;
            foreach ($cartItems as $item) {
                $sellerEarnings += ($item->product->price * $item->quantity) * 0.90;
                if ($item->product->seller_id) {
                    \DB::table('seller_transactions')->insert([
                        'seller_id' => $item->product->seller_id,
                        'order_id' => $order->id,
                        'amount' => ($item->product->price * $item->quantity) * 0.90,
                        'type' => 'earning',
                        'description' => 'Earning from order #' . $order->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            $order->update(['seller_earnings' => $sellerEarnings]);
            
            // Order items & stock reduction
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price
                ]);
                $item->product->decrement('quantity', $item->quantity);
            }
            $cart->items()->delete();
            DB::commit();
            
            // Email to customer
            try {
                Mail::to($user->email)->send(new OrderPlacedMail($order));
            } catch (\Exception $e) { 
                \Log::error("Order email failed: " . $e->getMessage()); 
            }
            
            // Email to sellers
            try {
                $sellerEmails = [];
                foreach ($cartItems as $item) {
                    $seller = $item->product->seller;
                    if ($seller && $seller->email && !in_array($seller->email, $sellerEmails)) {
                        $sellerEmails[] = $seller->email;
                    }
                }
                foreach ($sellerEmails as $sellerEmail) {
                    Mail::to($sellerEmail)->send(new NewOrderForSellerMail($order));
                }
            } catch (\Exception $e) { 
                \Log::error("Seller email failed: " . $e->getMessage()); 
            }
            
            // Payment flow
            if ($request->payment_method == 'razorpay') {
                $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
                $razorpayOrder = $api->order->create([
                    'receipt' => 'order_' . $order->id,
                    'amount' => $total * 100,
                    'currency' => 'INR',
                    'payment_capture' => 1
                ]);
                $order->update(['razorpay_order_id' => $razorpayOrder->id]);
                return redirect()->route('orders.payment', $order);
            }
            
            if ($request->payment_method == 'QR') {
                return redirect()->route('orders.payment', $order)->with('success', 'Order placed! Please complete payment.');
            }
            
            return redirect()->route('orders.show', $order)->with('success', 'Order placed successfully!');
            
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Check budget before showing checkout page
     * Includes delivered orders + pending orders + cart total
     */
    private function checkBudgetBeforeCheckout($user, $cartTotal)
    {
        $budget = $user->budget;
        
        if (!$budget) {
            return [
                'allowed' => true,
                'warning' => "You haven't set a monthly budget. Consider setting one to track your spending!"
            ];
        }
        
        $deliveredSpent = Order::where('user_id', $user->id)
            ->where('order_status', Order::STATUS_DELIVERED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');
        
        $pendingOrders = Order::where('user_id', $user->id)
            ->whereIn('order_status', ['pending', 'confirmed', 'processing', 'shipped', 'out_for_delivery'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');
        
        $totalCommitment = $deliveredSpent + $pendingOrders + $cartTotal;
        $remainingBudget = $budget->amount - $totalCommitment;
        
        if ($cartTotal > $remainingBudget + $cartTotal) {
            // This condition is for when cart alone exceeds remaining
        }
        
        if ($totalCommitment > $budget->amount) {
            return [
                'allowed' => false,
                'error' => "❌ Cannot checkout! Your total committed amount (₹" . number_format($totalCommitment) . 
                        ") exceeds your monthly budget (₹" . number_format($budget->amount) . ").\n" .
                        "Already spent: ₹" . number_format($deliveredSpent) . " (delivered)\n" .
                        "Pending orders: ₹" . number_format($pendingOrders)
            ];
        }
        
        return [
            'allowed' => true,
            'remaining' => $budget->amount - $totalCommitment,
            'info' => "✅ Within budget! Remaining: ₹" . number_format($remainingBudget)
        ];
    }

    /**
     * Razorpay payment verification
     */
    public function verifyPayment(Request $request)
    {
        \Log::info('Razorpay verifyPayment called', $request->all());
        
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'razorpay_payment_id' => 'required',
            'razorpay_order_id' => 'required',
            'razorpay_signature' => 'required',
        ]);

        $order = Order::findOrFail($request->order_id);
        
        if ($order->user_id != Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
        
        try {
            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature
            ];
            $api->utility->verifyPaymentSignature($attributes);
            \Log::info('Signature verified successfully for order ' . $order->id);
        } catch (\Exception $e) {
            \Log::error('Signature verification failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Invalid payment signature.']);
        }

        $order->update([
            'payment_status' => 'verified',
            'razorpay_payment_id' => $request->razorpay_payment_id,
            'razorpay_signature' => $request->razorpay_signature,
            'transaction_id' => $request->razorpay_payment_id,
            'order_status' => Order::STATUS_CONFIRMED,
        ]);
        
        \Log::info('Order ' . $order->id . ' updated to confirmed');

        return response()->json(['success' => true]);
    }

    /**
     * Display order list
     */
    public function index()
    {
        $orders = Auth::user()->orders()->with('items.product')->latest()->paginate(10);
        return view('orders.index', compact('orders'));
    }

    /**
     * Display order details
     */
    public function show(Order $order)
    {
        if ($order->user_id != Auth::id() && !Auth::user()->isAdmin()) abort(403);
        $order->load('items.product', 'shippingAddress');
        $timeline = $this->getOrderTimeline($order);
        return view('orders.show', compact('order', 'timeline'));
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request, Order $order)
    {
        if ($order->user_id != Auth::id() && !Auth::user()->isAdmin()) abort(403);
        if (!$order->canBeCancelled()) return back()->with('error', 'Order cannot be cancelled.');
        $request->validate(['cancellation_reason' => 'required|string|min:5']);
        
        DB::beginTransaction();
        try {
            foreach ($order->items as $item) $item->product->increment('quantity', $item->quantity);
            \DB::table('seller_transactions')->insert([
                'seller_id' => $order->items->first()->product->seller_id,
                'order_id' => $order->id,
                'amount' => -($order->seller_earnings),
                'type' => 'cancellation',
                'description' => 'Cancellation deduction for order #' . $order->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $order->update([
                'order_status' => Order::STATUS_CANCELLED,
                'cancellation_reason' => $request->cancellation_reason,
                'cancelled_at' => now(),
                'seller_earnings' => 0
            ]);
            DB::commit();
            return redirect()->route('orders.show', $order)->with('success', 'Order cancelled successfully.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Something went wrong.');
        }
    }

    /**
     * Request return
     */
    public function requestReturn(Request $request, Order $order)
    {
        if ($order->user_id != Auth::id()) abort(403);
        if (!$order->canRequestReturn()) return back()->with('error', 'Return not allowed.');
        $request->validate(['return_reason' => 'required|string|min:10']);
        $order->update([
            'order_status' => Order::STATUS_RETURN_REQUESTED,
            'return_reason' => $request->return_reason,
            'return_requested_at' => now()
        ]);
        
        try {
            $sellerEmails = [];
            foreach ($order->items as $item) {
                $seller = $item->product->seller;
                if ($seller && $seller->email && !in_array($seller->email, $sellerEmails)) {
                    $sellerEmails[] = $seller->email;
                }
            }
            foreach ($sellerEmails as $sellerEmail) {
                Mail::to($sellerEmail)->send(new ReturnRequestedMail($order));
            }
        } catch (\Exception $e) { 
            \Log::error("Return request email failed: " . $e->getMessage()); 
        }
        
        return redirect()->route('orders.show', $order)->with('success', 'Return request submitted.');
    }

    /**
     * Seller: Approve return request
     */
    public function approveReturn(Request $request, Order $order)
    {
        if (!Auth::user()->isSeller() && !Auth::user()->isAdmin()) abort(403);
        if (!$order->canProcessReturn()) return back()->with('error', 'Cannot process return.');
        
        DB::beginTransaction();
        try {
            foreach ($order->items as $item) $item->product->increment('quantity', $item->quantity);
            $order->update([
                'order_status' => Order::STATUS_RETURN_APPROVED,
                'return_approved_at' => now(),
                'refund_status' => Order::REFUND_PROCESSING,
                'refund_amount' => $order->total_amount
            ]);
            DB::commit();
            try { Mail::to($order->user->email)->send(new ReturnApprovedMail($order)); } catch (\Exception $e) {}
            return back()->with('success', 'Return approved.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Something went wrong.');
        }
    }

    /**
     * Seller: Reject return request
     */
    public function rejectReturn(Request $request, Order $order)
    {
        if (!Auth::user()->isSeller() && !Auth::user()->isAdmin()) abort(403);
        $request->validate(['rejection_reason' => 'required|string|min:10']);
        $order->update([
            'order_status' => Order::STATUS_RETURN_REJECTED,
            'cancellation_reason' => $request->rejection_reason
        ]);
        try { Mail::to($order->user->email)->send(new ReturnRejectedMail($order, $request->rejection_reason)); } catch (\Exception $e) {}
        return back()->with('success', 'Return rejected.');
    }

    /**
     * Seller: Process refund
     */
    public function processRefund(Order $order)
    {
        if (!Auth::user()->isSeller() && !Auth::user()->isAdmin()) abort(403);
        if ($order->order_status != Order::STATUS_RETURN_APPROVED) return back()->with('error', 'Refund not allowed.');
        
        DB::beginTransaction();
        try {
            foreach ($order->items as $item) {
                if ($item->product->seller_id) {
                    \DB::table('seller_transactions')->insert([
                        'seller_id' => $item->product->seller_id,
                        'order_id' => $order->id,
                        'amount' => -($item->price * $item->quantity * 0.90),
                        'type' => 'refund',
                        'description' => 'Refund deduction for order #' . $order->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            $order->update([
                'refund_status' => Order::REFUND_COMPLETED,
                'order_status' => Order::STATUS_RETURNED,
                'refund_processed_at' => now(),
                'seller_earnings' => 0
            ]);
            DB::commit();
            try { Mail::to($order->user->email)->send(new RefundProcessedMail($order)); } catch (\Exception $e) {}
            return back()->with('success', 'Refund processed.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Something went wrong.');
        }
    }

    /**
     * Payment page (QR or Razorpay)
     */
    public function payment(Order $order)
    {
        if ($order->user_id != Auth::id()) abort(403);
        if ($order->payment_method == 'razorpay') {
            return view('orders.razorpay-payment', compact('order'));
        }
        return view('orders.payment', compact('order'));
    }

    /**
     * Upload payment proof (QR payments) and confirm order
     */
    public function uploadPaymentProof(Request $request, Order $order)
    {
        $request->validate([
            'transaction_id' => 'required|string',
            'payment_screenshot' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $path = $request->file('payment_screenshot')->store('payments', 'public');

        $order->update([
            'transaction_id' => $request->transaction_id,
            'payment_status' => Order::PAYMENT_SUBMITTED,
            'payment_screenshot' => $path,
            'order_status' => Order::STATUS_CONFIRMED
        ]);

        return redirect()->route('orders.show', $order)->with('success', 'Payment proof submitted. Order confirmed.');
    }

    /**
     * Track order
     */
    public function track(Order $order)
    {
        if ($order->user_id != Auth::id()) abort(403);
        return view('orders.track', compact('order'));
    }

    /**
     * Seller: View all orders
     */
    public function sellerOrders(Request $request)
    {
        if (!Auth::user()->isSeller()) abort(403);
        $query = Order::whereHas('items.product', fn($q) => $q->where('seller_id', Auth::id()))->with('user', 'items.product');
        if ($request->has('status') && $request->status != 'all' && $request->status != '') {
            $query->where('order_status', $request->status);
        }
        $orders = $query->latest()->paginate(20);
        $stats = [
            'pending' => Order::whereHas('items.product', fn($q) => $q->where('seller_id', Auth::id()))->where('order_status', Order::STATUS_PENDING)->count(),
            'processing' => Order::whereHas('items.product', fn($q) => $q->where('seller_id', Auth::id()))->where('order_status', Order::STATUS_PROCESSING)->count(),
            'shipped' => Order::whereHas('items.product', fn($q) => $q->where('seller_id', Auth::id()))->where('order_status', Order::STATUS_SHIPPED)->count(),
            'delivered' => Order::whereHas('items.product', fn($q) => $q->where('seller_id', Auth::id()))->where('order_status', Order::STATUS_DELIVERED)->count(),
            'return_requested' => Order::whereHas('items.product', fn($q) => $q->where('seller_id', Auth::id()))->where('order_status', Order::STATUS_RETURN_REQUESTED)->count(),
        ];
        return view('seller.orders', compact('orders', 'stats'));
    }

    /**
     * Seller: View single order details
     */
    public function sellerOrderShow(Order $order)
    {
        if (!Auth::user()->isSeller()) abort(403);
        $hasSellerProducts = $order->items()->whereHas('product', fn($q) => $q->where('seller_id', Auth::id()))->exists();
        if (!$hasSellerProducts) abort(403);
        $order->load('user', 'items.product', 'shippingAddress');
        return view('seller.order-show', compact('order'));
    }

    /**
     * Seller: Update order status
     */
    public function updateOrderStatus(Request $request, Order $order)
    {
        if (!Auth::user()->isSeller()) abort(403);
        $cannotUpdate = in_array($order->order_status, [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED, Order::STATUS_RETURNED, Order::STATUS_RETURN_APPROVED, Order::STATUS_RETURN_REJECTED]);
        if ($cannotUpdate) return back()->with('error', 'Order cannot be updated.');
        $request->validate(['status' => 'required|in:pending,confirmed,processing,shipped,out_for_delivery,delivered', 'tracking_number' => 'nullable|string', 'courier_name' => 'nullable|string']);
        if ($order->order_status == Order::STATUS_DELIVERED && $request->status != Order::STATUS_DELIVERED) return back()->with('error', 'Delivered orders cannot be updated.');
        $order->update([
            'order_status' => $request->status,
            'tracking_number' => $request->tracking_number ?? $order->tracking_number,
            'courier_name' => $request->courier_name ?? $order->courier_name,
            'shipped_at' => $request->status == Order::STATUS_SHIPPED ? now() : $order->shipped_at,
            'delivered_at' => $request->status == Order::STATUS_DELIVERED ? now() : null,
        ]);
        try { Mail::to($order->user->email)->send(new OrderStatusUpdatedMail($order)); } catch (\Exception $e) {}
        return back()->with('success', 'Order status updated.');
    }

    /**
     * Helper: Order timeline
     */
    private function getOrderTimeline($order)
    {
        $timeline = [['status' => 'Order Placed', 'date' => $order->created_at, 'completed' => true, 'icon' => 'fa-shopping-cart']];
        if ($order->order_status != Order::STATUS_PENDING && $order->order_status != Order::STATUS_CANCELLED) {
            $timeline[] = ['status' => 'Order Confirmed', 'date' => $order->created_at->addHours(2), 'completed' => in_array($order->order_status, [Order::STATUS_CONFIRMED, Order::STATUS_PROCESSING, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED]), 'icon' => 'fa-check-circle'];
        }
        if (in_array($order->order_status, [Order::STATUS_PROCESSING, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED])) {
            $timeline[] = ['status' => 'Processing', 'date' => $order->created_at->addHours(24), 'completed' => in_array($order->order_status, [Order::STATUS_PROCESSING, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED]), 'icon' => 'fa-cogs'];
        }
        if (in_array($order->order_status, [Order::STATUS_SHIPPED, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED])) {
            $timeline[] = ['status' => 'Shipped', 'date' => $order->shipped_at ?? $order->updated_at, 'completed' => $order->order_status != Order::STATUS_PROCESSING && $order->order_status != Order::STATUS_CONFIRMED, 'icon' => 'fa-shipping-fast'];
        }
        if ($order->order_status == Order::STATUS_DELIVERED) {
            $timeline[] = ['status' => 'Delivered', 'date' => $order->delivered_at, 'completed' => true, 'icon' => 'fa-check-double'];
        }
        return $timeline;
    }
}